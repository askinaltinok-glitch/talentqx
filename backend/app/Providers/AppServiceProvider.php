<?php

namespace App\Providers;

use App\Services\AI\LLMProviderInterface;
use App\Services\AI\OpenAIProvider;
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
        $this->app->bind(LLMProviderInterface::class, OpenAIProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
