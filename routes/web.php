<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\QuestionnairePlayController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');

Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])
        ->middleware('throttle:login');

    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])->name('register.store');

    Route::get('forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:password-email')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:password-reset')
        ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', [VerifyEmailController::class, 'notice'])->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', [VerifyEmailController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('email/verification-notification', [VerifyEmailController::class, 'send'])
        ->middleware('throttle:verification-send')
        ->name('verification.send');

    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/questionnaires', [QuestionnaireController::class, 'index'])->name('questionnaires.index');
    Route::get('/questionnaires/create', [QuestionnaireController::class, 'create'])->name('questionnaires.create');
    Route::post('/questionnaires', [QuestionnaireController::class, 'store'])->name('questionnaires.store');
    Route::delete('/questionnaires/{questionnaire}', [QuestionnaireController::class, 'destroy'])->name('questionnaires.destroy');
    Route::post('/questionnaires/{questionnaire}/duplicate', [QuestionnaireController::class, 'duplicate'])->name('questionnaires.duplicate');

    Route::get('/questionnaires/{questionnaire}/export-results', [QuestionnaireController::class, 'exportResults'])
        ->name('questionnaires.export-results');

    Route::get('/questionnaires/{questionnaire}/results', [QuestionnaireController::class, 'results'])->name('questionnaires.results');

    Route::get('/questionnaires/{questionnaire}/titles', [QuestionnaireController::class, 'titles'])->name('questionnaires.titles');
    Route::post('/questionnaires/{questionnaire}/titles', [QuestionnaireController::class, 'selectTitle'])->name('questionnaires.select-title');

    Route::get('/questionnaires/{questionnaire}/build', [QuestionnaireController::class, 'build'])->name('questionnaires.build');
    Route::post('/questionnaires/{questionnaire}/settings', [QuestionnaireController::class, 'updateSettings'])->name('questionnaires.settings');
    Route::post('/questionnaires/{questionnaire}/generate-more', [QuestionnaireController::class, 'generateMore'])->name('questionnaires.generate-more');
    Route::post('/questionnaires/{questionnaire}/finish', [QuestionnaireController::class, 'finish'])->name('questionnaires.finish');

    Route::get('/questionnaires/{questionnaire}/play/start', [QuestionnairePlayController::class, 'start'])->name('questionnaires.play.start');
    Route::get('/play/{questionnaireAttempt}/results', [QuestionnairePlayController::class, 'results'])->name('questionnaires.play.results');
    Route::get('/play/{questionnaireAttempt}', [QuestionnairePlayController::class, 'show'])->name('questionnaires.play.show');
    Route::post('/play/{questionnaireAttempt}', [QuestionnairePlayController::class, 'saveAnswers'])->name('questionnaires.play.save');
});
