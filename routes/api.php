<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\QuestionnaireApiController;
use App\Http\Controllers\Api\QuestionnaireAttemptApiController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/questionnaires', [QuestionnaireApiController::class, 'index']);
    Route::post('/questionnaires', [QuestionnaireApiController::class, 'store']);
    Route::get('/questionnaires/{questionnaire}', [QuestionnaireApiController::class, 'show']);
    Route::delete('/questionnaires/{questionnaire}', [QuestionnaireApiController::class, 'destroy']);
    Route::post('/questionnaires/{questionnaire}/duplicate', [QuestionnaireApiController::class, 'duplicate']);

    Route::get('/questionnaires/{questionnaire}/titles', [QuestionnaireApiController::class, 'titles']);
    Route::post('/questionnaires/{questionnaire}/select-title', [QuestionnaireApiController::class, 'selectTitle']);
    Route::get('/questionnaires/{questionnaire}/build', [QuestionnaireApiController::class, 'build']);
    Route::post('/questionnaires/{questionnaire}/generate-more', [QuestionnaireApiController::class, 'generateMore']);
    Route::post('/questionnaires/{questionnaire}/settings', [QuestionnaireApiController::class, 'updateSettings']);
    Route::post('/questionnaires/{questionnaire}/finish', [QuestionnaireApiController::class, 'finish']);

    Route::get('/questionnaires/{questionnaire}/results', [QuestionnaireApiController::class, 'resultsOverview']);
    Route::get('/questionnaires/{questionnaire}/export-results', [QuestionnaireApiController::class, 'exportResults']);

    Route::post('/questionnaires/{questionnaire}/attempts', [QuestionnaireAttemptApiController::class, 'start']);
    Route::get('/attempts/{questionnaireAttempt}', [QuestionnaireAttemptApiController::class, 'show']);
    Route::post('/attempts/{questionnaireAttempt}/answers', [QuestionnaireAttemptApiController::class, 'saveAnswers']);
    Route::get('/attempts/{questionnaireAttempt}/results', [QuestionnaireAttemptApiController::class, 'results']);
});
