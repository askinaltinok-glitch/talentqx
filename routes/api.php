<?php

use App\Http\Controllers\Api\ApplyController;
use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\CandidateController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\InterviewController;
use App\Http\Controllers\Api\InterviewSessionController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\KVKKController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\MarketplaceController;
use App\Http\Controllers\Api\MarketplaceAccessController;
use App\Http\Controllers\Api\PositionTemplateController;
use App\Http\Controllers\Api\PrivacyController;
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

    // Password management (public)
    Route::post('/forgot-password', [PasswordController::class, 'forgotPassword'])
        ->middleware('throttle:5,1'); // 5 requests per minute
    Route::post('/verify-reset-token', [PasswordController::class, 'verifyResetToken']);
    Route::post('/reset-password', [PasswordController::class, 'resetPassword'])
        ->middleware('throttle:10,1'); // 10 requests per minute

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

    // ===========================================
    // PUBLIC PRIVACY & CONTACT ROUTES
    // ===========================================

    // Privacy endpoints (public)
    Route::prefix('privacy')->group(function () {
        Route::get('/meta', [PrivacyController::class, 'meta']);
        Route::get('/policy/{regime}/{locale?}', [PrivacyController::class, 'policy']);
    });

    // Contact form endpoints (public)
    Route::prefix('contact')->group(function () {
        Route::post('/', [ContactController::class, 'submit']);
        Route::post('/newsletter', [ContactController::class, 'newsletter']);
    });

    // ===========================================
    // PUBLIC APPLY ROUTES (QR Code Landing)
    // ===========================================
    Route::prefix('apply')->group(function () {
        // GET job info for apply page
        Route::get('/{companySlug}/{branchSlug}/{roleCode}', [ApplyController::class, 'show']);
        // POST submit application
        Route::post('/{companySlug}/{branchSlug}/{roleCode}', [ApplyController::class, 'submit'])
            ->middleware('throttle:10,1'); // Rate limit: 10 requests per minute
    });

    // ===========================================
    // INTERVIEW SESSION ROUTES (Public, token-less)
    // ===========================================
    Route::prefix('interview-sessions')->group(function () {
        Route::post('/start', [InterviewSessionController::class, 'start']);
        Route::get('/questions', [InterviewSessionController::class, 'questions']);
        Route::post('/{sessionId}/answer', [InterviewSessionController::class, 'submitAnswer']);
        Route::post('/{sessionId}/complete', [InterviewSessionController::class, 'complete']);
        Route::get('/{sessionId}/status', [InterviewSessionController::class, 'status']);
    });

    // ===========================================
    // REPORT ROUTES (Public download with report ID)
    // ===========================================
    Route::prefix('reports')->group(function () {
        Route::get('/{reportId}/download', [ReportController::class, 'download']);
        Route::get('/{reportId}/status', [ReportController::class, 'status']);
    });

    // ===========================================
    // PUBLIC MARKETPLACE ACCESS ROUTES (Token-based)
    // ===========================================
    Route::prefix('marketplace-access')->group(function () {
        Route::get('/{token}', [MarketplaceAccessController::class, 'show']);
        Route::post('/{token}/approve', [MarketplaceAccessController::class, 'approve']);
        Route::post('/{token}/reject', [MarketplaceAccessController::class, 'reject']);
    });

    // Protected routes (auth required)
    // customer.scope middleware enforces default-deny for non-platform users
    // subscription.access middleware enforces subscription and grace period restrictions
    Route::middleware(['auth:sanctum', 'customer.scope', 'subscription.access'])->group(function () {

        // Routes exempt from ForcePasswordChange (user can access even if must_change_password=true)
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
        Route::post('/change-password', [PasswordController::class, 'changePassword']);

        // Routes that require password to be changed first
        Route::middleware('force.password.change')->group(function () {

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
        // QR Code endpoints
        Route::post('/{id}/qr-code', [JobController::class, 'generateQRCode']);
        Route::get('/{id}/qr-code/preview', [JobController::class, 'previewQRCode']);
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
        Route::get('/latest-import-batch', [EmployeeController::class, 'getLatestImportBatch']);
        Route::post('/bulk-import/rollback', [EmployeeController::class, 'rollbackImport']);
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

    // Privacy consent stats (protected)
    Route::get('/privacy/consents/stats', [PrivacyController::class, 'stats']);

    // Job retention policy update
    Route::put('/jobs/{id}/retention', [KVKKController::class, 'updateRetention']);

    // ===========================================
    // MARKETPLACE MODULE (Premium only)
    // ===========================================
    Route::prefix('marketplace')->group(function () {
        Route::get('/candidates', [MarketplaceController::class, 'index']);
        Route::post('/candidates/{id}/request-access', [MarketplaceController::class, 'requestAccess']);
        Route::get('/candidates/{id}/full-profile', [MarketplaceController::class, 'fullProfile']);
        Route::get('/my-requests', [MarketplaceController::class, 'myRequests']);
    });

    // ===========================================
    // ANTI-CHEAT MODULE
    // ===========================================

    Route::prefix('interviews')->group(function () {
        Route::post('/{id}/analyze-cheating', [InterviewController::class, 'analyzeCheating']);
        Route::get('/{id}/cheating-report', [InterviewController::class, 'cheatingReport']);
    });

    Route::get('/anti-cheat/similar-responses', [InterviewController::class, 'similarResponses']);

    // ===========================================
    // INTERVIEW REPORTS (Protected)
    // ===========================================
    Route::prefix('reports')->group(function () {
        Route::post('/generate', [ReportController::class, 'generate']);
        Route::get('/session/{sessionId}', [ReportController::class, 'listForSession']);
        Route::delete('/{reportId}', [ReportController::class, 'delete']);
    });

    // ===========================================
    // PLATFORM ADMIN ONLY ROUTES
    // These routes require is_platform_admin = true
    // ===========================================

    Route::middleware('platform.admin')->group(function () {

        // ===========================================
        // SALES CONSOLE (MINI CRM) - Platform-level sales
        // ===========================================
        Route::prefix('leads')->group(function () {
            Route::get('/', [LeadController::class, 'index']);
            Route::get('/pipeline-stats', [LeadController::class, 'pipelineStats']);
            Route::get('/follow-up-stats', [LeadController::class, 'followUpStats']);
            Route::post('/', [LeadController::class, 'store']);
            Route::get('/{lead}', [LeadController::class, 'show']);
            Route::put('/{lead}', [LeadController::class, 'update']);
            Route::patch('/{lead}/status', [LeadController::class, 'updateStatus']);
            Route::delete('/{lead}', [LeadController::class, 'destroy']);

            // Activities
            Route::post('/{lead}/activities', [LeadController::class, 'addActivity']);
            Route::put('/{lead}/activities/{activity}', [LeadController::class, 'updateActivity']);

            // Checklist
            Route::patch('/{lead}/checklist/{item}', [LeadController::class, 'toggleChecklist']);
            Route::get('/{lead}/checklist-progress', [LeadController::class, 'checklistProgress']);
        });

        // ===========================================
        // PLATFORM ANALYTICS - AI costs, usage stats
        // ===========================================
        Route::prefix('platform')->group(function () {
            Route::get('/ai-costs', [AssessmentController::class, 'costStats']);
            Route::get('/usage-stats', [AssessmentController::class, 'dashboardStats']);
        });

    }); // End of platform.admin middleware group

        }); // End of force.password.change middleware group
    }); // End of auth:sanctum middleware group

}); // End of v1 prefix group
