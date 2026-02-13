<?php

use App\Http\Controllers\Api\AdminAnalyticsController;
use App\Http\Controllers\Api\AdminCompanyController;
use App\Http\Controllers\Api\AdminFormInterviewController;
use App\Http\Controllers\Api\AdminInterviewTemplateController;
use App\Http\Controllers\Api\AdminOutcomesController;
use App\Http\Controllers\Api\AdminPackageController;
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
use App\Http\Controllers\Api\FormInterviewController;
use App\Http\Controllers\Api\InterviewController;
use App\Http\Controllers\Api\InterviewSessionController;
use App\Http\Controllers\Api\InterviewTemplateController;
use App\Http\Controllers\Api\JobController;
use App\Http\Controllers\Api\PackageController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProfileController;
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
use App\Http\Controllers\Api\V1\PoolCandidateController;
use App\Http\Controllers\Api\Admin\PoolCompanyController;
use App\Http\Controllers\Api\Admin\TalentRequestController;
use App\Http\Controllers\Api\Admin\PresentationController;
use App\Http\Controllers\Api\Admin\AssessmentStubController;
use App\Http\Controllers\Api\Admin\ML\DatasetController;
use App\Http\Controllers\Api\Admin\ML\HealthController as MlHealthController;
use App\Http\Controllers\Api\Admin\ML\LearningController as MlLearningController;
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
    // PAYMENT CALLBACK (Public, no auth required)
    // ===========================================
    Route::post('/payments/callback', [PaymentController::class, 'callback']);

    // ===========================================
    // PUBLIC PACKAGES (No auth required)
    // ===========================================
    Route::get('/packages', [PackageController::class, 'index']);
    Route::get('/packages/{slug}', [PackageController::class, 'show']);

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

    // ===========================================
    // INTERVIEW TEMPLATES (API Token auth, no user auth required)
    // Rate limit: 60/min for all template reads
    // ===========================================
    Route::prefix('interview-templates')->middleware(['api.token', 'throttle:60,1'])->group(function () {
        Route::get('/', [InterviewTemplateController::class, 'index'])
            ->name('interview-templates.index');

        Route::get('/check/{version}/{language}/{positionCode}', [InterviewTemplateController::class, 'check'])
            ->name('interview-templates.check');

        Route::get('/{version}/{language}/{positionCode}/parsed', [InterviewTemplateController::class, 'showParsed'])
            ->name('interview-templates.show.parsed');

        Route::get('/{version}/{language}/{positionCode?}', [InterviewTemplateController::class, 'show'])
            ->name('interview-templates.show');
    });

    // ===========================================
    // FORM INTERVIEWS (API Token auth, no user auth required)
    // Template-based interview sessions with DecisionEngine scoring
    // Rate limits: create=10/min, others=60/min
    // ===========================================
    Route::prefix('form-interviews')->middleware('api.token')->group(function () {
        Route::post('/', [FormInterviewController::class, 'create'])
            ->middleware('throttle:10,1') // 10 creates per minute
            ->name('form-interviews.create');

        Route::get('/{id}', [FormInterviewController::class, 'show'])
            ->middleware('throttle:60,1')
            ->name('form-interviews.show');

        Route::post('/{id}/answers', [FormInterviewController::class, 'addAnswers'])
            ->middleware('throttle:60,1')
            ->name('form-interviews.answers');

        Route::post('/{id}/complete', [FormInterviewController::class, 'complete'])
            ->middleware('throttle:30,1') // Slightly stricter - scoring is expensive
            ->name('form-interviews.complete');

        Route::get('/{id}/score', [FormInterviewController::class, 'score'])
            ->middleware('throttle:60,1')
            ->name('form-interviews.score');
    });

    // ===========================================
    // CANDIDATE SUPPLY ENGINE (API Token auth)
    // Pool candidate management for talent supply
    // Separate from ATS /candidates routes
    // ===========================================
    Route::prefix('pool-candidates')->middleware('api.token')->group(function () {
        // Create pool candidate
        Route::post('/', [PoolCandidateController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('pool-candidates.store');

        // Get pool statistics
        Route::get('/stats', [PoolCandidateController::class, 'stats'])
            ->middleware('throttle:60,1')
            ->name('pool-candidates.stats');

        // List pool candidates with filters
        Route::get('/pool', [PoolCandidateController::class, 'pool'])
            ->middleware('throttle:60,1')
            ->name('pool-candidates.pool');

        // Get candidate details
        Route::get('/{candidate}', [PoolCandidateController::class, 'show'])
            ->middleware('throttle:60,1')
            ->name('pool-candidates.show');

        // Start interview for candidate
        Route::post('/{candidate}/start-interview', [PoolCandidateController::class, 'startInterview'])
            ->middleware('throttle:10,1')
            ->name('pool-candidates.start-interview');

        // Mark as presented to company
        Route::post('/{candidate}/present', [PoolCandidateController::class, 'present'])
            ->middleware('throttle:30,1')
            ->name('pool-candidates.present');

        // Mark as hired
        Route::post('/{candidate}/hire', [PoolCandidateController::class, 'hire'])
            ->middleware('throttle:30,1')
            ->name('pool-candidates.hire');
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

        // ===========================================
        // PROFILE ROUTES
        // ===========================================
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::put('/', [ProfileController::class, 'update']);
            Route::get('/billing', [ProfileController::class, 'getBilling']);
            Route::put('/billing', [ProfileController::class, 'updateBilling']);
            Route::post('/request-password-reset', [ProfileController::class, 'requestPasswordReset']);
        });

        // Company subscription status (needed before customer.scope check)
        Route::get('/company/subscription-status', [CompanyController::class, 'subscriptionStatus']);

        // Routes that require password to be changed first
        Route::middleware('force.password.change')->group(function () {

    // ===========================================
    // PAYMENT ROUTES (Authenticated)
    // ===========================================
    Route::prefix('payments')->group(function () {
        Route::post('/checkout', [PaymentController::class, 'checkout']);
        Route::get('/history', [PaymentController::class, 'history']);
        Route::get('/{id}', [PaymentController::class, 'show']);
    });

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
        Route::post('/improve-description', [JobController::class, 'improveDescription']);
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

        // ===========================================
        // ADMIN PACKAGE MANAGEMENT
        // ===========================================
        Route::prefix('admin/packages')->group(function () {
            Route::get('/', [AdminPackageController::class, 'index']);
            Route::post('/', [AdminPackageController::class, 'store']);
            Route::get('/stats', [AdminPackageController::class, 'stats']);
            Route::get('/{id}', [AdminPackageController::class, 'show']);
            Route::put('/{id}', [AdminPackageController::class, 'update']);
            Route::delete('/{id}', [AdminPackageController::class, 'destroy']);
            Route::patch('/{id}/toggle-active', [AdminPackageController::class, 'toggleActive']);
            Route::post('/reorder', [AdminPackageController::class, 'reorder']);
        });

        // ===========================================
        // ADMIN INTERVIEW TEMPLATES - CRUD for template management
        // Rate limits: 120/min read, 30/min write
        // ===========================================
        Route::prefix('admin/interview-templates')->group(function () {
            // Bulk operations
            Route::post('/import', [AdminInterviewTemplateController::class, 'import'])
                ->middleware('throttle:10,1'); // Lower limit for bulk

            // Read operations - 120/min
            Route::get('/', [AdminInterviewTemplateController::class, 'index'])
                ->middleware('throttle:120,1');
            Route::get('/{id}', [AdminInterviewTemplateController::class, 'show'])
                ->middleware('throttle:120,1');
            Route::get('/{id}/audit-logs', [AdminInterviewTemplateController::class, 'auditLogs'])
                ->middleware('throttle:120,1');
            Route::get('/{id}/export', [AdminInterviewTemplateController::class, 'export'])
                ->middleware('throttle:120,1');

            // Write operations - 30/min
            Route::post('/', [AdminInterviewTemplateController::class, 'store'])
                ->middleware('throttle:30,1');
            Route::put('/{id}', [AdminInterviewTemplateController::class, 'update'])
                ->middleware('throttle:30,1');
            Route::post('/{id}/activate', [AdminInterviewTemplateController::class, 'activate'])
                ->middleware('throttle:30,1');
            Route::post('/{id}/publish', [AdminInterviewTemplateController::class, 'publish'])
                ->middleware('throttle:30,1');
            Route::post('/{id}/clone', [AdminInterviewTemplateController::class, 'clone'])
                ->middleware('throttle:30,1');
            Route::delete('/{id}', [AdminInterviewTemplateController::class, 'destroy'])
                ->middleware('throttle:30,1');
        });

        // ===========================================
        // ADMIN ANALYTICS - Interview Results Dashboard
        // ===========================================
        Route::prefix('admin/analytics')->group(function () {
            Route::get('/interviews/summary', [AdminAnalyticsController::class, 'interviewsSummary'])
                ->middleware('throttle:60,1');
            Route::get('/interviews', [AdminAnalyticsController::class, 'interviewsList'])
                ->middleware('throttle:60,1');
            Route::get('/interviews/{id}', [AdminAnalyticsController::class, 'interviewDetail'])
                ->middleware('throttle:60,1');
            // Calibration baseline endpoint (legacy)
            Route::get('/positions/baseline', [AdminAnalyticsController::class, 'positionBaseline'])
                ->middleware('throttle:120,1');
            // Calibration baseline v2 (industry + rolling window)
            Route::get('/positions/baseline-v2', [AdminAnalyticsController::class, 'positionBaselineV2'])
                ->middleware('throttle:120,1');
            // Drift detection endpoint
            Route::get('/drift', [AdminAnalyticsController::class, 'driftSummary'])
                ->middleware('throttle:60,1');
            // Model health (decision→outcome accuracy)
            Route::get('/model-health', [AdminAnalyticsController::class, 'modelHealth'])
                ->middleware('throttle:60,1');
            // Candidate Supply Engine metrics
            Route::get('/candidate-supply', [AdminAnalyticsController::class, 'candidateSupplyMetrics'])
                ->middleware('throttle:60,1');
            // Company Consumption Layer metrics
            Route::get('/consumption', [AdminAnalyticsController::class, 'consumptionMetrics'])
                ->middleware('throttle:60,1');
        });

        // ===========================================
        // ADMIN ML - Machine Learning / Learning Core
        // ===========================================
        Route::prefix('admin/ml')->group(function () {
            // Dataset export (csv/jsonl)
            Route::get('/dataset/export', [DatasetController::class, 'export'])
                ->middleware('throttle:10,1');
            // ML model health metrics
            Route::get('/health', [MlHealthController::class, 'index'])
                ->middleware('throttle:60,1');
            // Learning health (closed-loop learning metrics)
            Route::get('/learning-health', [MlLearningController::class, 'health'])
                ->middleware('throttle:60,1');
            // Feature importance
            Route::get('/features', [MlLearningController::class, 'features'])
                ->middleware('throttle:60,1');
            // Learning events log
            Route::get('/learning-events', [MlLearningController::class, 'events'])
                ->middleware('throttle:60,1');
        });

        // ===========================================
        // ADMIN FORM INTERVIEWS - Operations Dashboard
        // ===========================================
        Route::prefix('admin/form-interviews')->group(function () {
            Route::get('/stats', [AdminFormInterviewController::class, 'stats'])
                ->middleware('throttle:60,1');
            Route::get('/', [AdminFormInterviewController::class, 'index'])
                ->middleware('throttle:60,1');
            Route::get('/{id}', [AdminFormInterviewController::class, 'show'])
                ->middleware('throttle:60,1');
            Route::get('/{id}/decision-packet', [AdminFormInterviewController::class, 'decisionPacket'])
                ->middleware('throttle:30,1');
            Route::get('/{id}/decision-packet.pdf', [AdminFormInterviewController::class, 'decisionPacketPdf'])
                ->middleware('throttle:10,1');
            Route::patch('/{id}/notes', [AdminFormInterviewController::class, 'updateNotes'])
                ->middleware('throttle:30,1');
            Route::delete('/{id}', [AdminFormInterviewController::class, 'destroy'])
                ->middleware('throttle:30,1');
            // Assessment stubs (Maritime)
            Route::post('/{id}/english-assessment/complete', [AssessmentStubController::class, 'completeEnglishAssessment'])
                ->middleware('throttle:30,1');
            Route::post('/{id}/video/attach', [AssessmentStubController::class, 'attachVideo'])
                ->middleware('throttle:30,1');
            Route::post('/{id}/video/complete', [AssessmentStubController::class, 'completeVideoAssessment'])
                ->middleware('throttle:30,1');
        });

        // ===========================================
        // ADMIN OUTCOMES - Ground Truth Data for Calibration
        // ===========================================
        Route::prefix('admin/outcomes')->group(function () {
            Route::get('/stats', [AdminOutcomesController::class, 'stats'])
                ->middleware('throttle:60,1');
            Route::get('/model-health', [AdminOutcomesController::class, 'modelHealth'])
                ->middleware('throttle:60,1');
            Route::get('/', [AdminOutcomesController::class, 'index'])
                ->middleware('throttle:60,1');
            Route::get('/{interview_id}', [AdminOutcomesController::class, 'show'])
                ->middleware('throttle:60,1');
            Route::post('/', [AdminOutcomesController::class, 'store'])
                ->middleware('throttle:30,1');
        });

        // ===========================================
        // COMPANY CONSUMPTION LAYER - Pool Companies
        // ===========================================
        Route::prefix('admin/pool-companies')->group(function () {
            Route::get('/stats', [PoolCompanyController::class, 'stats'])
                ->middleware('throttle:60,1');
            Route::get('/', [PoolCompanyController::class, 'index'])
                ->middleware('throttle:60,1');
            Route::post('/', [PoolCompanyController::class, 'store'])
                ->middleware('throttle:30,1');
            Route::get('/{poolCompany}', [PoolCompanyController::class, 'show'])
                ->middleware('throttle:60,1');
            Route::put('/{poolCompany}', [PoolCompanyController::class, 'update'])
                ->middleware('throttle:30,1');
        });

        // ===========================================
        // COMPANY CONSUMPTION LAYER - Talent Requests
        // ===========================================
        Route::prefix('admin/talent-requests')->group(function () {
            Route::get('/stats', [TalentRequestController::class, 'stats'])
                ->middleware('throttle:60,1');
            Route::get('/', [TalentRequestController::class, 'index'])
                ->middleware('throttle:60,1');
            Route::post('/', [TalentRequestController::class, 'store'])
                ->middleware('throttle:30,1');
            Route::get('/{talentRequest}', [TalentRequestController::class, 'show'])
                ->middleware('throttle:60,1');
            Route::put('/{talentRequest}', [TalentRequestController::class, 'update'])
                ->middleware('throttle:30,1');
            Route::post('/{talentRequest}/close', [TalentRequestController::class, 'close'])
                ->middleware('throttle:30,1');
            Route::get('/{talentRequest}/matching-candidates', [TalentRequestController::class, 'matchingCandidates'])
                ->middleware('throttle:60,1');
            Route::post('/{talentRequest}/present', [TalentRequestController::class, 'presentCandidates'])
                ->middleware('throttle:30,1');
        });

        // ===========================================
        // COMPANY CONSUMPTION LAYER - Presentations
        // ===========================================
        Route::prefix('admin/presentations')->group(function () {
            Route::get('/stats', [PresentationController::class, 'stats'])
                ->middleware('throttle:60,1');
            Route::get('/', [PresentationController::class, 'index'])
                ->middleware('throttle:60,1');
            Route::get('/{presentation}', [PresentationController::class, 'show'])
                ->middleware('throttle:60,1');
            Route::post('/{presentation}/view', [PresentationController::class, 'markViewed'])
                ->middleware('throttle:30,1');
            Route::post('/{presentation}/feedback', [PresentationController::class, 'recordFeedback'])
                ->middleware('throttle:30,1');
            Route::post('/{presentation}/reject', [PresentationController::class, 'reject'])
                ->middleware('throttle:30,1');
            Route::post('/{presentation}/interview', [PresentationController::class, 'interview'])
                ->middleware('throttle:30,1');
            Route::post('/{presentation}/hire', [PresentationController::class, 'hire'])
                ->middleware('throttle:30,1');
        });

    }); // End of platform.admin middleware group

        }); // End of force.password.change middleware group
    }); // End of auth:sanctum middleware group

}); // End of v1 prefix group
