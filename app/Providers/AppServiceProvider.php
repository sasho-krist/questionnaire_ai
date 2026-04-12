<?php

namespace App\Providers;

use App\Models\Questionnaire;
use App\Models\QuestionnaireAttempt;
use Illuminate\Pagination\Paginator;
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
