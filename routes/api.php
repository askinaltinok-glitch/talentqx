<?php

use App\Http\Controllers\Api\AdminCompanyController;
use App\Http\Controllers\Api\ApplyController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\CandidateController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\CopilotController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\InterviewController;
use App\Http\Controllers\Api\InterviewSessionController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\KVKKController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\MarketplaceAccessController;
use App\Http\Controllers\Api\MarketplaceController;
use App\Http\Controllers\Api\PositionTemplateController;
use App\Http\Controllers\Api\PrivacyController;
use App\Http\Controllers\Api\PublicApplyController;
use App\Http\Controllers\Api\QuestionImportController;
use App\Http\Controllers\Api\TaxonomyController;
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
    // QR APPLY ROUTES (Short Code Based)
    // /i/{token} frontend -> /api/v1/qr-apply/{token}
    // ===========================================
    Route::prefix('qr-apply')->group(function () {
        // GET job info for QR landing page
        Route::get('/{token}', [PublicApplyController::class, 'show']);
        // POST start application (create candidate + interview)
        Route::post('/{token}', [PublicApplyController::class, 'start'])
            ->middleware('throttle:10,1');
        // POST submit answer
        Route::post('/{token}/answers', [PublicApplyController::class, 'submitAnswer'])
            ->middleware('throttle:30,1');
        // POST complete interview
        Route::post('/{token}/complete', [PublicApplyController::class, 'complete']);
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
    // MARKETPLACE ACCESS ROUTES (Public, token-based)
    // Allows candidate owners to approve/reject access requests via email link
    // ===========================================
    Route::prefix('marketplace-access')->group(function () {
        Route::get('/{token}', [MarketplaceAccessController::class, 'show']);
        Route::post('/{token}/approve', [MarketplaceAccessController::class, 'approve']);
        Route::post('/{token}/reject', [MarketplaceAccessController::class, 'reject']);
    });

    // Protected routes (auth required)
    // customer.scope middleware enforces default-deny for non-platform users
    Route::middleware(['auth:sanctum', 'customer.scope'])->group(function () {

        // Routes exempt from ForcePasswordChange (user can access even if must_change_password=true)
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
        Route::post('/change-password', [PasswordController::class, 'changePassword']);

        // Company subscription status (needed before customer.scope check)
        Route::get('/company/subscription-status', [CompanyController::class, 'subscriptionStatus']);

        // Routes that require password to be changed first
        Route::middleware('force.password.change')->group(function () {

    // Position Templates (legacy)
    Route::prefix('positions/templates')->group(function () {
        Route::get('/', [PositionTemplateController::class, 'index']);
        Route::get('/{slug}', [PositionTemplateController::class, 'show']);
    });

    // ===========================================
    // JOB TAXONOMY SYSTEM
    // ===========================================
    Route::prefix('taxonomy')->group(function () {
        Route::get('/domains', [TaxonomyController::class, 'domains']);
        Route::get('/domains/{domainId}/subdomains', [TaxonomyController::class, 'subdomains']);
        Route::get('/subdomains/{subdomainId}/positions', [TaxonomyController::class, 'positions']);
        Route::get('/positions/search', [TaxonomyController::class, 'searchPositions']);
        Route::get('/positions/{positionId}', [TaxonomyController::class, 'positionDetail']);
        Route::get('/archetypes', [TaxonomyController::class, 'archetypes']);
        Route::get('/competencies', [TaxonomyController::class, 'competencies']);
        Route::get('/tree', [TaxonomyController::class, 'tree']);
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
        Route::get('/{id}/qr-info', [JobController::class, 'qrInfo']);
    });

    // Candidates
    Route::prefix('candidates')->group(function () {
        Route::get('/', [CandidateController::class, 'index']);
        Route::post('/', [CandidateController::class, 'store']);
        Route::post('/bulk-send-invites', [CandidateController::class, 'bulkSendInvites']);
        Route::get('/{id}', [CandidateController::class, 'show']);
        Route::patch('/{id}/status', [CandidateController::class, 'updateStatus']);
        Route::post('/{id}/cv', [CandidateController::class, 'uploadCv']);
        Route::post('/{id}/send-interview-invite', [CandidateController::class, 'sendInterviewInvite']);
        Route::delete('/{id}', [CandidateController::class, 'destroy']);
        // KVKK - Right to be Forgotten & Export
        Route::delete('/{id}/erase', [KVKKController::class, 'eraseCandidate']);
        Route::get('/{id}/export', [KVKKController::class, 'exportCandidate']);
    });

    // Interviews
    Route::prefix('interviews')->group(function () {
        Route::get('/', [InterviewController::class, 'index']);
        Route::post('/', [InterviewController::class, 'store']);
        Route::get('/{id}', [InterviewController::class, 'show']);
        Route::post('/{id}/analyze', [InterviewController::class, 'analyze']);
    });

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'stats']);
        Route::post('/compare', [DashboardController::class, 'compare']);
        Route::get('/leaderboard', [DashboardController::class, 'leaderboard']);
        Route::get('/punctuality', [DashboardController::class, 'punctuality']);
        Route::get('/punctuality/export', [DashboardController::class, 'punctualityExport']);
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
    // ANTI-CHEAT MODULE
    // ===========================================

    Route::prefix('interviews')->group(function () {
        Route::post('/{id}/analyze-cheating', [InterviewController::class, 'analyzeCheating']);
        Route::get('/{id}/cheating-report', [InterviewController::class, 'cheatingReport']);
        Route::get('/{id}/report', [InterviewController::class, 'report']);
    });

    Route::get('/anti-cheat/similar-responses', [InterviewController::class, 'similarResponses']);

    // ===========================================
    // AI COPILOT MODULE
    // ===========================================

    Route::prefix('copilot')->group(function () {
        Route::post('/chat', [CopilotController::class, 'chat']);
        Route::get('/context/{type}/{id}', [CopilotController::class, 'contextPreview']);
        Route::get('/history', [CopilotController::class, 'history']);
    });

    // ===========================================
    // MARKETPLACE MODULE (Premium Feature)
    // ===========================================

    Route::prefix('marketplace')->group(function () {
        Route::get('/candidates', [MarketplaceController::class, 'listCandidates']);
        Route::post('/candidates/{id}/request-access', [MarketplaceController::class, 'requestAccess']);
        Route::get('/candidates/{id}/full-profile', [MarketplaceController::class, 'getFullProfile']);
        Route::get('/my-requests', [MarketplaceController::class, 'myRequests']);
    });

    // ===========================================
    // INTERVIEW REPORTS (Protected)
    // ===========================================
    Route::prefix('reports')->group(function () {
        Route::post('/generate', [ReportController::class, 'generate']);
        Route::get('/session/{sessionId}', [ReportController::class, 'listForSession']);
        Route::delete('/{reportId}', [ReportController::class, 'delete']);
    });

    // ===========================================
    // AI PROVIDERS - For all authenticated users
    // ===========================================
    Route::get('/ai-providers/enabled', [AdminCompanyController::class, 'getEnabledProviders']);

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

        // ===========================================
        // ADMIN COMPANY MANAGEMENT - Subscription admin
        // ===========================================
        Route::prefix('admin/companies')->group(function () {
            Route::get('/', [AdminCompanyController::class, 'index']);
            Route::get('/{id}', [AdminCompanyController::class, 'show']);
            Route::patch('/{id}/subscription', [AdminCompanyController::class, 'updateSubscription']);
            Route::patch('/{id}/credits', [AdminCompanyController::class, 'updateCredits']);
            Route::get('/{id}/credit-history', [AdminCompanyController::class, 'creditHistory']);
            Route::patch('/{id}/ai-settings', [AdminCompanyController::class, 'updateCompanyAiSettings']);
        });

        // ===========================================
        // ADMIN AI SETTINGS - Platform-wide AI configuration
        // ===========================================
        Route::prefix('admin/ai-settings')->group(function () {
            Route::get('/', [AdminCompanyController::class, 'getAiSettings']);
            Route::patch('/', [AdminCompanyController::class, 'updateAiSettings']);
        });

        // ===========================================
        // ADMIN AI PROVIDERS - Provider management
        // ===========================================
        Route::prefix('admin/ai-providers')->group(function () {
            Route::get('/', [AdminCompanyController::class, 'getAiProviders']);
            Route::get('/enabled', [AdminCompanyController::class, 'getEnabledProviders']);
            Route::post('/{provider}/test', [AdminCompanyController::class, 'testAiProvider']);
        });

        // ===========================================
        // ADMIN QUESTION IMPORT
        // ===========================================
        Route::prefix('admin/questions')->group(function () {
            Route::get('/roles', [QuestionImportController::class, 'roles']);
            Route::post('/import/validate', [QuestionImportController::class, 'validate']);
            Route::post('/import/save', [QuestionImportController::class, 'save']);
        });

    }); // End of platform.admin middleware group

        }); // End of force.password.change middleware group
    }); // End of auth:sanctum middleware group

}); // End of v1 prefix group
