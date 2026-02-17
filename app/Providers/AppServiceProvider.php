<?php

namespace App\Providers;

use App\Models\Job;
use App\Observers\JobObserver;
use App\Services\AI\LLMProviderFactory;
use App\Services\AI\LLMProviderInterface;
use App\Services\Mail\MailProviderInterface;
use App\Services\Mail\SmtpMailProvider;
use App\Services\Outbox\OutboxService;
use App\Services\QRCode\QRCodeService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        Job::observe(JobObserver::class);
        // Rate limiter: contact/demo form - 3 requests per 10 minutes per IP
        RateLimiter::for('contact', function (Request $request) {
            return Limit::perMinutes(10, 3)->by('contact:' . $request->ip());
        });

        // Rate limiter: API general - 60 requests per minute per user/IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
