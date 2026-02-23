<?php

namespace App\Providers;

use App\Models\Job;
use App\Observers\JobObserver;
use App\Services\AI\LLMProviderFactory;
use App\Services\AI\LLMProviderInterface;
use App\Services\Ais\AisProviderInterface;
use App\Services\Ais\HttpAisProvider;
use App\Services\Ais\MockAisProvider;
use App\Services\Mail\MailProviderInterface;
use App\Services\Mail\SmtpMailProvider;
use App\Services\Outbox\OutboxService;
use App\Services\QRCode\QRCodeService;
use App\Events\CandidateAvailabilityChanged;
use App\Listeners\NotifyMatchingCompaniesOnAvailability;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Dynamic LLM provider binding - uses factory to select provider based on config/company settings
        $this->app->bind(LLMProviderInterface::class, function ($app) {
            return LLMProviderFactory::createForCurrentUser();
        });

        // Register QR Code Service as singleton
        $this->app->singleton(QRCodeService::class, function ($app) {
            return new QRCodeService();
        });

        // Register Outbox Service as singleton
        $this->app->singleton(OutboxService::class, function ($app) {
            return new OutboxService();
        });

        // Mail Autopilot: bind mail provider interface to SMTP implementation
        $this->app->bind(MailProviderInterface::class, SmtpMailProvider::class);

        // AIS Verification: bind provider interface (mock or real HTTP)
        $this->app->bind(AisProviderInterface::class, function () {
            return config('maritime.ais_mock')
                ? new MockAisProvider()
                : new HttpAisProvider();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        Job::observe(JobObserver::class);

        // Event: candidate availability changes → notify matching companies
        Event::listen(CandidateAvailabilityChanged::class, NotifyMatchingCompaniesOnAvailability::class);
        // Rate limiter: contact/demo form - 3 requests per 10 minutes per IP
        RateLimiter::for('contact', function (Request $request) {
            return Limit::perMinutes(10, 3)->by('contact:' . $request->ip());
        });

        // Rate limiter: API general - 60 requests per minute per user/IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiter: English test start — 20/min per candidate_id + IP
        RateLimiter::for('english-test-start', function (Request $request) {
            $candidateId = $request->route('id') ?? 'unknown';
            return [
                Limit::perMinute(20)->by('eng-start:' . $candidateId),
                Limit::perMinute(30)->by('eng-start-ip:' . $request->ip()),
            ];
        });

        // Rate limiter: English test submit — 5/min per candidate_id (strict)
        RateLimiter::for('english-test-submit', function (Request $request) {
            $candidateId = $request->route('id') ?? 'unknown';
            return [
                Limit::perMinute(5)->by('eng-submit:' . $candidateId),
                Limit::perMinute(10)->by('eng-submit-ip:' . $request->ip()),
            ];
        });

        // Rate limiter: Behavioral interview — 30/min per candidate_id
        RateLimiter::for('behavioral', function (Request $request) {
            $candidateId = $request->route('id') ?? 'unknown';
            return [
                Limit::perMinute(30)->by('beh:' . $candidateId),
                Limit::perMinute(60)->by('beh-ip:' . $request->ip()),
            ];
        });

        // Rate limiter: Behavioral complete — 5/min per candidate (strict)
        RateLimiter::for('behavioral-complete', function (Request $request) {
            $candidateId = $request->route('id') ?? 'unknown';
            return [
                Limit::perMinute(5)->by('beh-complete:' . $candidateId),
                Limit::perMinute(10)->by('beh-complete-ip:' . $request->ip()),
            ];
        });
    }
}
