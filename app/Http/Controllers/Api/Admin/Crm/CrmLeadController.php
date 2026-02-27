<?php

namespace App\Http\Controllers\Api\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmLead;
use App\Models\CrmActivity;
use App\Models\CrmTask;
use App\Models\CrmFile;
use App\Models\CrmEmailMessage;
use App\Models\CrmEmailTemplate;
use App\Models\CrmAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class CrmLeadController extends Controller
{
    /**
     * POST /v1/admin/crm/leads
     */
    public function store(Request $request): JsonResponse
    {
        $v = $request->validate([
            'industry_code' => ['nullable', 'string', 'max:32'],
            'source_channel' => ['required', Rule::in(CrmLead::SOURCE_CHANNELS)],
            'source_meta' => ['nullable', 'array'],
            'company_id' => ['nullable', 'uuid', 'exists:crm_companies,id'],
            'contact_id' => ['nullable', 'uuid', 'exists:crm_contacts,id'],
            'lead_name' => ['required', 'string', 'max:500'],
            'stage' => ['nullable', Rule::in(CrmLead::STAGES)],
            'priority' => ['nullable', Rule::in(CrmLead::PRIORITIES)],
            'notes' => ['nullable', 'string'],
        ]);

        $v['last_activity_at'] = now();

        $lead = CrmLead::create($v);

        $lead->addActivity(CrmActivity::TYPE_SYSTEM, [
            'action' => 'lead_created',
            'source_channel' => $v['source_channel'],
        ], $request->user()?->id);

        CrmAuditLog::log('lead.created', 'lead', $lead->id, null, $v, $request->user()?->id, $request->ip());

        return response()->json(['success' => true, 'data' => $lead->load('company', 'contact')], 201);
    }

    /**
     * GET /v1/admin/crm/leads
     */
    public function index(Request $request): JsonResponse
    {
        $query = CrmLead::with('company:id,name,industry_code,domain', 'contact:id,full_name,email');

        if ($request->filled('stage')) {
            $query->stage($request->stage);
        }
        if ($request->filled('industry')) {
            $query->industry($request->industry);
        }
        if ($request->filled('q')) {
            $query->search($request->q);
        }
        if ($request->filled('source_channel')) {
            $query->where('source_channel', $request->source_channel);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $leads = $query->orderByDesc('last_activity_at')
                       ->paginate(min((int) $request->get('per_page', 25), 100));

        return response()->json([
            'success' => true,
            'data' => $leads->items(),
            'meta' => [
                'total' => $leads->total(),
                'page' => $leads->currentPage(),
                'per_page' => $leads->perPage(),
                'last_page' => $leads->lastPage(),
            ],
        ]);
    }

    /**
     * GET /v1/admin/crm/leads/{id}
     * Full detail: timeline + emails + files + tasks.
     */
    public function show(string $id): JsonResponse
    {
        $lead = CrmLead::with([
            'company.contacts',
            'contact',
            'activities' => fn($q) => $q->orderByDesc('occurred_at')->limit(50),
            'emails' => fn($q) => $q->orderByDesc('created_at')->limit(20),
            'files',
            'tasks' => fn($q) => $q->orderBy('due_at'),
        ])->find($id);

        if (!$lead) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Lead not found.']], 404);
        }

        return response()->json(['success' => true, 'data' => $lead]);
    }

    /**
     * PATCH /v1/admin/crm/leads/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $lead = CrmLead::find($id);
        if (!$lead) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Lead not found.']], 404);
        }

        $old = $lead->toArray();

        $v = $request->validate([
            'stage' => ['sometimes', Rule::in(CrmLead::STAGES)],
            'priority' => ['sometimes', Rule::in(CrmLead::PRIORITIES)],
            'notes' => ['nullable', 'string'],
            'company_id' => ['nullable', 'uuid', 'exists:crm_companies,id'],
            'contact_id' => ['nullable', 'uuid', 'exists:crm_contacts,id'],
        ]);

        // Stage change activity
        if (isset($v['stage']) && $v['stage'] !== $lead->stage) {
            $lead->addActivity(CrmActivity::TYPE_SYSTEM, [
                'action' => 'stage_changed',
                'from' => $lead->stage,
                'to' => $v['stage'],
            ], $request->user()?->id);
        }

        $lead->update($v);

        CrmAuditLog::log('lead.updated', 'lead', $lead->id, $old, $v, $request->user()?->id, $request->ip());

        return response()->json(['success' => true, 'data' => $lead->fresh()->load('company', 'contact')]);
    }

    /**
     * POST /v1/admin/crm/leads/{id}/note
     */
    public function addNote(Request $request, string $id): JsonResponse
    {
        $lead = CrmLead::find($id);
        if (!$lead) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Lead not found.']], 404);
        }

        $v = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $activity = $lead->addActivity(CrmActivity::TYPE_NOTE, [
            'body' => $v['body'],
        ], $request->user()?->id);

        return response()->json(['success' => true, 'data' => $activity], 201);
    }

    /**
     * POST /v1/admin/crm/leads/{id}/tasks
     */
    public function addTask(Request $request, string $id): JsonResponse
    {
        $lead = CrmLead::find($id);
        if (!$lead) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Lead not found.']], 404);
        }

        $v = $request->validate([
            'type' => ['required', Rule::in(CrmTask::TYPES)],
            'title' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
        ]);

        $v['lead_id'] = $id;
        $v['created_by'] = $request->user()?->id;

        $task = CrmTask::create($v);

        $lead->addActivity(CrmActivity::TYPE_TASK, [
            'task_id' => $task->id,
            'title' => $task->title,
            'type' => $task->type,
        ], $request->user()?->id);

        return response()->json(['success' => true, 'data' => $task], 201);
    }

    /**
     * POST /v1/admin/crm/leads/{id}/files
     */
    public function uploadFile(Request $request, string $id): JsonResponse
    {
        $lead = CrmLead::find($id);
        if (!$lead) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Lead not found.']], 404);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:10240'], // 10MB max
        ]);

        $uploadedFile = $request->file('file');
        $path = $uploadedFile->store("crm/leads/{$id}", 'local');

        $file = CrmFile::create([
            'lead_id' => $id,
            'company_id' => $lead->company_id,
            'storage_disk' => 'local',
            'path' => $path,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'mime' => $uploadedFile->getMimeType(),
            'size' => $uploadedFile->getSize(),
            'sha256' => hash_file('sha256', $uploadedFile->getRealPath()),
        ]);

        return response()->json(['success' => true, 'data' => $file], 201);
    }

    /**
     * POST /v1/admin/crm/leads/{id}/send-email
     */
    public function sendEmail(Request $request, string $id): JsonResponse
    {
        $lead = CrmLead::with('contact', 'company')->find($id);
        if (!$lead) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Lead not found.']], 404);
        }

        $v = $request->validate([
            'to_email' => ['required', 'email', 'max:255'],
            'template_key' => ['nullable', 'string', 'max:64'],
            'subject' => ['required_without:template_key', 'nullable', 'string', 'max:500'],
            'body_text' => ['required_without:template_key', 'nullable', 'string'],
            'body_html' => ['nullable', 'string'],
            'attachment_ids' => ['nullable', 'array'],
            'attachment_ids.*' => ['uuid', 'exists:crm_files,id'],
        ]);

        $fromEmail = config('mail.from.address', 'crew@octopus-ai.net');
        $subject = $v['subject'] ?? '';
        $bodyText = $v['body_text'] ?? '';
        $bodyHtml = $v['body_html'] ?? null;

        // If template, render it
        if (!empty($v['template_key'])) {
            $industry = $lead->industry_code ?? 'general';
            $lang = $lead->contact?->preferred_language ?? 'en';
            $template = CrmEmailTemplate::findTemplate($v['template_key'], $industry, $lang);

            if ($template) {
                $user = $request->user();
                $vars = [
                    'contact_name' => $lead->contact?->full_name ?? 'there',
                    'company_name' => $lead->company?->name ?? $lead->lead_name,
                    'lead_name' => $lead->lead_name,
                    'sender_name' => $request->input('sender_name', $user?->name ?? 'Octopus AI Team'),
                    'sender_title' => $request->input('sender_title', $user?->title ?? ''),
                    'package_name' => $request->input('package_name', 'Starter'),
                    'price' => $request->input('price', ''),
                    'trial_days' => $request->input('trial_days', '14'),
                ];
                $rendered = $template->render($vars);
                $subject = $rendered['subject'];
                $bodyText = $rendered['body_text'];
                $bodyHtml = $rendered['body_html'];
            }
        }

        // Create message record
        $messageId = '<' . str()->uuid() . '@octopus-ai.net>';

        $emailMsg = CrmEmailMessage::create([
            'lead_id' => $id,
            'direction' => CrmEmailMessage::DIRECTION_OUTBOUND,
            'provider' => 'smtp',
            'message_id' => $messageId,
            'from_email' => $fromEmail,
            'to_email' => $v['to_email'],
            'subject' => $subject,
            'body_text' => $bodyText,
            'body_html' => $bodyHtml,
            'attachments' => $v['attachment_ids'] ?? null,
            'status' => CrmEmailMessage::STATUS_QUEUED,
        ]);

        // Send via Laravel Mail
        try {
            $attachmentPaths = [];
            if (!empty($v['attachment_ids'])) {
                $files = CrmFile::whereIn('id', $v['attachment_ids'])->get();
                foreach ($files as $file) {
                    $attachmentPaths[] = [
                        'path' => storage_path("app/{$file->path}"),
                        'name' => $file->original_name,
                        'mime' => $file->mime,
                    ];
                }
            }

            Mail::raw($bodyText, function ($message) use ($fromEmail, $v, $subject, $messageId, $attachmentPaths) {
                $message->from($fromEmail, 'Octopus AI')
                        ->to($v['to_email'])
                        ->subject($subject);

                // Set Message-ID using IdentificationHeader (Symfony Mailer requirement)
                $headers = $message->getHeaders();
                if ($headers->has('Message-ID')) {
                    $headers->remove('Message-ID');
                }
                $headers->addIdHeader('Message-ID', trim($messageId, '<>'));

                foreach ($attachmentPaths as $att) {
                    $message->attach($att['path'], ['as' => $att['name'], 'mime' => $att['mime']]);
                }
            });

            $emailMsg->update([
                'status' => CrmEmailMessage::STATUS_SENT,
                'sent_at' => now(),
            ]);

            $lead->addActivity(CrmActivity::TYPE_EMAIL_SENT, [
                'subject' => $subject,
                'to' => $v['to_email'],
                'message_id' => $messageId,
                'snippet' => mb_substr($bodyText, 0, 200),
            ], $request->user()?->id);

            CrmAuditLog::log('email.sent', 'email', $emailMsg->id, null, [
                'to' => $v['to_email'],
                'subject' => $subject,
            ], $request->user()?->id, $request->ip());

            Log::info('CRM email sent', ['lead_id' => $id, 'to' => $v['to_email'], 'msg_id' => $messageId]);

            return response()->json(['success' => true, 'data' => $emailMsg->fresh()]);

        } catch (\Exception $e) {
            $emailMsg->update(['status' => 'failed']);
            Log::error('CRM email send failed', ['lead_id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => ['code' => 'send_failed', 'message' => 'Failed to send email: ' . $e->getMessage()],
            ], 500);
        }
    }

    /**
     * GET /v1/admin/crm/leads/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $industry = $request->get('industry');

        $query = CrmLead::query();
        if ($industry) {
            $query->industry($industry);
        }

        $byStage = (clone $query)->select('stage', DB::raw('count(*) as count'))
            ->groupBy('stage')
            ->pluck('count', 'stage');

        $bySource = (clone $query)->select('source_channel', DB::raw('count(*) as count'))
            ->groupBy('source_channel')
            ->pluck('count', 'source_channel');

        $total = (clone $query)->count();
        $active = (clone $query)->active()->count();
        $recent = (clone $query)->where('created_at', '>=', now()->subDays(7))->count();

        $overdueTasks = CrmTask::overdue()->count();
        $emailsSent = CrmEmailMessage::outbound()
            ->where('sent_at', '>=', now()->subDays(30))
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'active' => $active,
                'recent_7d' => $recent,
                'by_stage' => $byStage,
                'by_source' => $bySource,
                'overdue_tasks' => $overdueTasks,
                'emails_sent_30d' => $emailsSent,
            ],
        ]);
    }

    /**
     * PATCH /v1/admin/crm/tasks/{id}/done
     */
    public function completeTask(string $id): JsonResponse
    {
        $task = CrmTask::find($id);
        if (!$task) {
            return response()->json(['success' => false, 'error' => ['code' => 'not_found', 'message' => 'Task not found.']], 404);
        }

        $task->complete();

        return response()->json(['success' => true, 'data' => $task->fresh()]);
    }
}
