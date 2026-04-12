<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\QuestionnaireController;
use App\Http\Controllers\QuestionnairePlayController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);

    Route::get('register', [RegisterController::class, 'create'])->name('register');
    Route::post('register', [RegisterController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', function () {
        return redirect()->route('questionnaires.index');
    });

    Route::get('/questionnaires', [QuestionnaireController::class, 'index'])->name('questionnaires.index');
    Route::get('/questionnaires/create', [QuestionnaireController::class, 'create'])->name('questionnaires.create');
    Route::post('/questionnaires', [QuestionnaireController::class, 'store'])->name('questionnaires.store');
    Route::delete('/questionnaires/{questionnaire}', [QuestionnaireController::class, 'destroy'])->name('questionnaires.destroy');

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
