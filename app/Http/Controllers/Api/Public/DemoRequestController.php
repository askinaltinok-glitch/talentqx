<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Mail\DemoRequestReceivedMail;
use App\Models\DemoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DemoRequestController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name'     => ['required', 'string', 'max:120'],
            'company'       => ['required', 'string', 'max:180'],
            'email'         => ['required', 'email', 'max:180'],
            'country'       => ['nullable', 'string', 'max:80'],
            'company_type'  => ['nullable', 'string', 'max:40'],
            'fleet_size'    => ['nullable', 'string', 'max:20'],
            'active_crew'   => ['nullable', 'string', 'max:20'],
            'monthly_hires' => ['nullable', 'string', 'max:20'],
            'vessel_types'  => ['nullable', 'array'],
            'vessel_types.*'=> ['string', 'max:40'],
            'main_ranks'    => ['nullable', 'array'],
            'main_ranks.*'  => ['string', 'max:60'],
            'message'       => ['nullable', 'string', 'max:5000'],
            'locale'        => ['nullable', 'string', 'max:12'],
            'source'        => ['nullable', 'string', 'max:60'],
        ]);

        $demo = DemoRequest::create([
            ...$data,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        $to = config('services.demo_request_to');
        if ($to) {
            Mail::to($to)->queue(new DemoRequestReceivedMail($demo));

            try {
                app(\App\Services\AdminNotificationService::class)->notifyEmailSent(
                    'demo_request_received',
                    $to,
                    "Demo request email: {$data['company']}",
                    ['demo_request_id' => $demo->id]
                );
            } catch (\Throwable) {}
        }

        // Admin push notification (fail-safe)
        try {
            app(\App\Services\AdminNotificationService::class)->notifyDemoRequest(
                $data['full_name'],
                $data['company'],
            );
        } catch (\Throwable $e) {
            Log::warning('Admin notification failed (demo_request)', ['error' => $e->getMessage()]);
        }

        return response()->json(['ok' => true]);
    }
}
