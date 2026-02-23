<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Mail\DemoRequestReceivedMail;
use App\Models\DemoRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class DemoRequestController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'company'   => ['required', 'string', 'max:180'],
            'email'     => ['required', 'email', 'max:180'],
            'country'   => ['nullable', 'string', 'max:80'],
            'message'   => ['nullable', 'string', 'max:5000'],
            'locale'    => ['nullable', 'string', 'max:12'],
            'source'    => ['nullable', 'string', 'max:60'],
        ]);

        $demo = DemoRequest::create([
            ...$data,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        $to = config('services.demo_request_to');
        if ($to) {
            Mail::to($to)->queue(new DemoRequestReceivedMail($demo));
        }

        return response()->json(['ok' => true]);
    }
}
