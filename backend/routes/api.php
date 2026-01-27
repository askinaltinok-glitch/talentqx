<?php

use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CandidateController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\InterviewController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\KVKKController;
use App\Http\Controllers\Api\PositionTemplateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Health check
    Route::get('/health', fn() => response()->json(['status' => 'ok', 'timestamp' => now()]));

    // Public routes
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Public interview routes (token-based)
    Route::prefix('interviews/public')->group(function () {
        Route::get('/{token}', [InterviewController::class, 'showPublic']);
        Route::post('/{token}/start', [InterviewController::class, 'startPublic']);
        Route::post('/{token}/responses', [InterviewController::class, 'submitResponse']);
        Route::post('/{token}/complete', [InterviewController::class, 'completePublic']);
    });

    // Public assessment routes (token-based for employees) - with rate limiting
    Route::prefix('assessments/public')
        ->middleware(\App\Http\Middleware\AssessmentRateLimiter::class)
        ->group(function () {
            Route::get('/{token}', [AssessmentController::class, 'publicShow']);
            Route::post('/{token}/start', [AssessmentController::class, 'publicStart']);
            Route::post('/{token}/responses', [AssessmentController::class, 'publicSubmitResponse']);
            Route::post('/{token}/complete', [AssessmentController::class, 'publicComplete']);
        });

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Position Templates
    Route::prefix('positions/templates')->group(function () {
        Route::get('/', [PositionTemplateController::class, 'index']);
        Route::get('/{slug}', [PositionTemplateController::class, 'show']);
    });

    // Jobs
    Route::prefix('jobs')->group(function () {
        Route::get('/', [JobController::class, 'index']);
        Route::post('/', [JobController::class, 'store']);
        Route::get('/{id}', [JobController::class, 'show']);
        Route::put('/{id}', [JobController::class, 'update']);
        Route::delete('/{id}', [JobController::class, 'destroy']);
        Route::post('/{id}/publish', [JobController::class, 'publish']);
        Route::post('/{id}/generate-questions', [JobController::class, 'generateQuestions']);
        Route::get('/{id}/questions', [JobController::class, 'questions']);
    });

    // Candidates
    Route::prefix('candidates')->group(function () {
        Route::get('/', [CandidateController::class, 'index']);
        Route::post('/', [CandidateController::class, 'store']);
        Route::get('/{id}', [CandidateController::class, 'show']);
        Route::patch('/{id}/status', [CandidateController::class, 'updateStatus']);
        Route::post('/{id}/cv', [CandidateController::class, 'uploadCv']);
        Route::delete('/{id}', [CandidateController::class, 'destroy']);
        // KVKK - Right to be Forgotten & Export
        Route::delete('/{id}/erase', [KVKKController::class, 'eraseCandidate']);
        Route::get('/{id}/export', [KVKKController::class, 'exportCandidate']);
    });

    // Interviews
    Route::prefix('interviews')->group(function () {
        Route::post('/', [InterviewController::class, 'store']);
        Route::get('/{id}', [InterviewController::class, 'show']);
        Route::post('/{id}/analyze', [InterviewController::class, 'analyze']);
    });

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'stats']);
        Route::post('/compare', [DashboardController::class, 'compare']);
        Route::get('/leaderboard', [DashboardController::class, 'leaderboard']);
    });

    // ===========================================
    // WORKFORCE ASSESSMENT MODULE
    // ===========================================

    // Employees
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::post('/', [EmployeeController::class, 'store']);
        Route::get('/stats', [EmployeeController::class, 'stats']);
        Route::get('/retention-stats', [EmployeeController::class, 'retentionStats']);
        Route::post('/bulk-import', [EmployeeController::class, 'bulkImport']);
        Route::get('/{id}', [EmployeeController::class, 'show']);
        Route::put('/{id}', [EmployeeController::class, 'update']);
        Route::delete('/{id}', [EmployeeController::class, 'destroy']);
        // KVKK - Right to be Forgotten & Data Portability
        Route::delete('/{id}/erase', [EmployeeController::class, 'erase']);
        Route::get('/{id}/export', [EmployeeController::class, 'export']);
        Route::put('/{id}/retention', [EmployeeController::class, 'updateRetention']);
    });

    // Assessment Templates
    Route::prefix('assessment-templates')->group(function () {
        Route::get('/', [AssessmentController::class, 'templates']);
        Route::get('/{slug}', [AssessmentController::class, 'templateShow']);
    });

    // Assessment Sessions
    Route::prefix('assessment-sessions')->group(function () {
        Route::get('/', [AssessmentController::class, 'sessions']);
        Route::post('/', [AssessmentController::class, 'createSession']);
        Route::post('/bulk', [AssessmentController::class, 'bulkCreateSessions']);
        Route::get('/{id}', [AssessmentController::class, 'sessionShow']);
    });

    // Assessment Results
    Route::prefix('assessment-results')->group(function () {
        Route::get('/', [AssessmentController::class, 'results']);
        Route::post('/compare', [AssessmentController::class, 'compare']);
        Route::get('/role-stats', [AssessmentController::class, 'roleStats']);
        Route::get('/dashboard-stats', [AssessmentController::class, 'dashboardStats']);
        Route::get('/cost-stats', [AssessmentController::class, 'costStats']);
        Route::get('/cheating-risk', [AssessmentController::class, 'cheatingRiskResults']);
        Route::get('/similar-responses', [AssessmentController::class, 'similarResponses']);
        Route::get('/{id}', [AssessmentController::class, 'resultShow']);
    });

    // ===========================================
    // KVKK / DATA RETENTION MODULE
    // ===========================================

    Route::prefix('kvkk')->group(function () {
        Route::get('/retention-stats', [KVKKController::class, 'retentionStats']);
        Route::get('/audit-logs', [KVKKController::class, 'auditLogs']);
        Route::post('/erasure-requests', [KVKKController::class, 'createErasureRequest']);
        Route::get('/erasure-requests', [KVKKController::class, 'listErasureRequests']);
    });

    // Job retention policy update
    Route::put('/jobs/{id}/retention', [KVKKController::class, 'updateRetention']);

    // ===========================================
    // ANTI-CHEAT MODULE
    // ===========================================

    Route::prefix('interviews')->group(function () {
        Route::post('/{id}/analyze-cheating', [InterviewController::class, 'analyzeCheating']);
        Route::get('/{id}/cheating-report', [InterviewController::class, 'cheatingReport']);
    });

    Route::get('/anti-cheat/similar-responses', [InterviewController::class, 'similarResponses']);
});

}); // End of v1 prefix group
