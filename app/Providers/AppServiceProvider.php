<?php

namespace App\Providers;

use App\Models\Questionnaire;
use App\Models\QuestionnaireAttempt;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
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
        Schema::defaultStringLength(191);
        Paginator::useBootstrapFive();

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip().'|'.$request->string('email'));
        });

        RateLimiter::for('password-email', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip().'|'.$request->string('email'));
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('verification-send', function (Request $request) {
            return Limit::perMinute(6)->by($request->user()?->id ?: $request->ip());
        });

        Route::bind('questionnaire', function (string $value) {
            return Questionnaire::query()
                ->where('uuid', $value)
                ->firstOrFail();
        });

        Route::bind('questionnaireAttempt', function (string $value) {
            return QuestionnaireAttempt::query()
                ->where('uuid', $value)
                ->firstOrFail();
        });
    }
}
