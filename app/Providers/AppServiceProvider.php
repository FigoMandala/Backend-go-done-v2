<?php

namespace App\Providers;

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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('auth-login', function (Request $request) {
            $identifier = strtolower((string) $request->input('emailOrUsername', 'guest'));

            return Limit::perMinute(10)->by($identifier.'|'.$request->ip());
        });

        RateLimiter::for('auth-register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('account-delete', function (Request $request) {
            $userId = optional($request->user())->id ?? 'guest';

            return Limit::perMinute(5)->by($userId.'|'.$request->ip());
        });

        RateLimiter::for('api-authenticated', function (Request $request) {
            $userId = optional($request->user())->id ?? 'guest';

            return Limit::perMinute(120)->by($userId.'|'.$request->ip());
        });
    }
}
