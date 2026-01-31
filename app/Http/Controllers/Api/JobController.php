<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\PositionTemplate;
use App\Services\Interview\QuestionGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JobController extends Controller
{
    public function __construct(
        private QuestionGenerator $questionGenerator
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Job::with(['template', 'creator'])
            ->where('company_id', $request->user()->company_id)
            ->withCount(['candidates', 'interviews as completed_interviews_count' => function ($q) {
                $q->where('status', 'completed');
            }]);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('template_id')) {
            $query->where('template_id', $request->template_id);
        }

        $sortField = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $jobs = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $jobs->map(fn($job) => [
                'id' => $job->id,
                'title' => $job->title,
                'slug' => $job->slug,
                'status' => $job->status,
                'location' => $job->location,
                'template' => $job->template ? [
                    'id' => $job->template->id,
                    'name' => $job->template->name,
                ] : null,
                'candidates_count' => $job->candidates_count,
                'interviews_completed' => $job->completed_interviews_count,
                'published_at' => $job->published_at,
                'closes_at' => $job->closes_at,
            ]),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
                'last_page' => $jobs->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'template_id' => 'nullable|uuid|exists:position_templates,id',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|in:full_time,part_time,contract',
            'experience_years' => 'nullable|integer|min:0',
            'competencies' => 'nullable|array',
            'interview_settings' => 'nullable|array',
            'closes_at' => 'nullable|date|after:today',
        ]);

        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $counter = 1;

        while (Job::where('company_id', $request->user()->company_id)
            ->where('slug', $slug)
            ->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $job = Job::create([
            'company_id' => $request->user()->company_id,
            'created_by' => $request->user()->id,
            'template_id' => $validated['template_id'] ?? null,
            'title' => $validated['title'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
            'employment_type' => $validated['employment_type'] ?? 'full_time',
            'experience_years' => $validated['experience_years'] ?? 0,
            'competencies' => $validated['competencies'] ?? null,
            'interview_settings' => $validated['interview_settings'] ?? null,
            'closes_at' => $validated['closes_at'] ?? null,
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $job->id,
                'title' => $job->title,
                'slug' => $job->slug,
                'status' => $job->status,
                'created_at' => $job->created_at,
            ],
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $job = Job::with(['template', 'questions', 'creator'])
            ->where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $job->id,
                'title' => $job->title,
                'slug' => $job->slug,
                'description' => $job->description,
                'location' => $job->location,
                'employment_type' => $job->employment_type,
                'experience_years' => $job->experience_years,
                'status' => $job->status,
                'template' => $job->template ? [
                    'id' => $job->template->id,
                    'name' => $job->template->name,
                    'slug' => $job->template->slug,
                ] : null,
                'competencies' => $job->getEffectiveCompetencies(),
                'red_flags' => $job->getEffectiveRedFlags(),
                'question_rules' => $job->getEffectiveQuestionRules(),
                'scoring_rubric' => $job->getEffectiveScoringRubric(),
                'interview_settings' => $job->interview_settings,
                'questions' => $job->questions->map(fn($q) => [
                    'id' => $q->id,
                    'order' => $q->question_order,
                    'type' => $q->question_type,
                    'text' => $q->question_text,
                    'competency_code' => $q->competency_code,
                    'time_limit_seconds' => $q->time_limit_seconds,
                ]),
                'published_at' => $job->published_at,
                'closes_at' => $job->closes_at,
                'created_at' => $job->created_at,
            ],
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $job = Job::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|in:full_time,part_time,contract',
            'experience_years' => 'nullable|integer|min:0',
            'competencies' => 'nullable|array',
            'interview_settings' => 'nullable|array',
            'closes_at' => 'nullable|date',
        ]);

        $job->update($validated);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $job->id,
                'title' => $job->title,
                'status' => $job->status,
                'updated_at' => $job->updated_at,
            ],
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $job = Job::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        $job->delete();

        return response()->json([
            'success' => true,
            'message' => 'Is ilani silindi.',
        ]);
    }

    public function publish(Request $request, string $id): JsonResponse
    {
        $job = Job::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        if ($job->questions()->count() === 0) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NO_QUESTIONS',
                    'message' => 'Is ilanini yayinlamadan once sorular olusturmalisiniz.',
                ],
            ], 422);
        }

        $job->update([
            'status' => 'active',
            'published_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $job->id,
                'status' => $job->status,
                'published_at' => $job->published_at,
            ],
        ]);
    }

    public function generateQuestions(Request $request, string $id): JsonResponse
    {
        $job = Job::with('template')
            ->where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        $regenerate = $request->boolean('regenerate', false);

        try {
            $questions = $this->questionGenerator->generateForJob($job, $regenerate);

            return response()->json([
                'success' => true,
                'data' => [
                    'questions' => collect($questions)->map(fn($q) => [
                        'id' => $q->id ?? $q['id'] ?? null,
                        'question_order' => $q->question_order ?? $q['question_order'] ?? null,
                        'question_type' => $q->question_type ?? $q['question_type'] ?? null,
                        'question_text' => $q->question_text ?? $q['question_text'] ?? null,
                        'competency_code' => $q->competency_code ?? $q['competency_code'] ?? null,
                        'ideal_answer_points' => $q->ideal_answer_points ?? $q['ideal_answer_points'] ?? [],
                        'time_limit_seconds' => $q->time_limit_seconds ?? $q['time_limit_seconds'] ?? 180,
                    ]),
                    'generated_at' => now(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'GENERATION_FAILED',
                    'message' => 'Soru uretimi basarisiz oldu: ' . $e->getMessage(),
                ],
            ], 500);
        }
    }

    public function questions(Request $request, string $id): JsonResponse
    {
        $job = Job::with('questions')
            ->where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $job->questions->map(fn($q) => [
                'id' => $q->id,
                'order' => $q->question_order,
                'type' => $q->question_type,
                'text' => $q->question_text,
                'competency_code' => $q->competency_code,
                'ideal_answer_points' => $q->ideal_answer_points,
                'time_limit_seconds' => $q->time_limit_seconds,
                'is_required' => $q->is_required,
            ]),
        ]);
    }
}
