<?php

use App\Http\Controllers\Api\AdminAnalyticsController;
use App\Http\Controllers\Api\AdminCompanyController;
use App\Http\Controllers\Api\AdminFormInterviewController;
use App\Http\Controllers\Api\AdminInterviewTemplateController;
use App\Http\Controllers\Api\AdminOutcomesController;
use App\Http\Controllers\Api\AdminPackageController;
use App\Http\Controllers\Api\ApplyController;
use App\Http\Controllers\Api\CompanyApplyLinkController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CompanyOnboardController;
use App\Http\Controllers\Api\CompanyVesselController;
use App\Http\Controllers\Api\I18nController;
use App\Http\Controllers\Api\AssessmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\CandidateController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\PublicStatsController;
use App\Http\Controllers\Api\CopilotController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\FormInterviewController;
use App\Http\Controllers\Api\FormInterviewVoiceController;
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
use App\Http\Controllers\Api\Admin\CandidatePoolController;
use App\Http\Controllers\Api\Admin\ML\DatasetController;
use App\Http\Controllers\Api\Admin\ML\HealthController as MlHealthController;
use App\Http\Controllers\Api\Admin\ML\LearningController as MlLearningController;
use App\Http\Controllers\Api\Admin\Analytics\SupplyAnalyticsController;
use App\Http\Controllers\Api\Maritime\CompanyApplyResolverController;
use App\Http\Controllers\Api\Maritime\MaritimeCandidateController;
use App\Http\Controllers\Api\Maritime\CandidateLifecycleController;
use App\Http\Controllers\Api\Maritime\CandidateNotificationController;
use App\Http\Controllers\Api\Maritime\VesselReviewController;
use App\Http\Controllers\Api\Maritime\ProfileActivityController;
use App\Http\Controllers\Api\Maritime\MaritimeJobController;
use App\Http\Controllers\Api\Maritime\CandidatePushTokenController;
use App\Http\Controllers\Api\Admin\MaritimeJobAdminController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\Admin\CertificationController;
use App\Http\Controllers\Api\Admin\DemoController;
use App\Http\Controllers\Api\Admin\Crm\CrmCompanyController;
use App\Http\Controllers\Api\Admin\Crm\CrmContactController;
use App\Http\Controllers\Api\Admin\Crm\CrmLeadController;
use App\Http\Controllers\Api\Admin\Crm\CrmTemplateController;
use App\Http\Controllers\Api\Admin\Crm\ResearchController;
use App\Http\Controllers\Api\Admin\Crm\CrmInboxController;
use App\Http\Controllers\Api\Admin\Crm\CrmSequenceController;
use App\Http\Controllers\Api\Admin\Crm\CrmOutboundQueueController;
use App\Http\Controllers\Api\Admin\Crm\CrmInboundWebhookController;
use App\Http\Controllers\Api\Admin\Crm\CrmDealController;
use App\Http\Controllers\Api\Admin\Crm\CrmAnalyticsController;
use App\Http\Controllers\Api\Admin\Crm\VesselReviewAdminController;
use App\Http\Controllers\Api\Admin\SystemHealthController;
use App\Http\Controllers\Api\PublicDemoController;
use App\Http\Controllers\Api\PublicLeadController;
use App\Http\Controllers\Api\Maritime\ApplyTrackingController;
use App\Http\Controllers\Api\Maritime\MaritimeV2Controller;
use App\Http\Controllers\Api\Admin\GeoAnalyticsController;
use App\Http\Controllers\Api\Admin\JobListingController;
use App\Http\Controllers\Api\Admin\JobApplicantsController;
use App\Http\Controllers\Api\Public\JobListingController as PublicJobListingController;
use App\Http\Controllers\Api\Public\JobApplyController;
use App\Http\Controllers\Api\Public\DemoRequestController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AdminDemoRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Health check
    Route::get('/health', fn() => response()->json(['status' => 'ok', 'timestamp' => now()]));

    // Public platform stats (cached, no auth)
    Route::get('/stats', PublicStatsController::class)
        ->middleware('throttle:60,1')
        ->name('public.stats');

    // Translations (public, cached)
    Route::get('/i18n', [I18nController::class, 'index'])
        ->middleware('throttle:120,1')
        ->name('i18n.index');

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
    // PUBLIC LEAD INTAKE (Website form → CRM)
    // ===========================================
    // Public demo (no auth, IP rate-limited 10/day)
    Route::post('/public/demo/start', [PublicDemoController::class, 'start'])
        ->middleware('throttle:10,1440')
        ->name('public.demo.start');

    Route::post('/public/leads', [PublicLeadController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('public.leads.store');

    // ===========================================
    // PUBLIC JOB LISTINGS (No auth)
    // ===========================================
    Route::prefix('public/jobs')->group(function () {
        Route::get('/', [PublicJobListingController::class, 'index'])
            ->middleware('throttle:60,1');
        Route::get('/{slug}', [PublicJobListingController::class, 'show'])
            ->middleware('throttle:60,1');
    });

    Route::post('public/jobs/{jobListing}/apply', [JobApplyController::class, 'store'])
        ->middleware('throttle:20,1');

    Route::post('public/demo-requests', [DemoRequestController::class, 'store'])
        ->middleware('throttle:20,1');

    // ===========================================
    // PASSIVE CANDIDATE REGISTRATION (Public, no auth)
    // ===========================================
    Route::post('/candidates/register-passive', [\App\Http\Controllers\Api\CandidateProfileController::class, 'registerPassive'])
        ->middleware('throttle:10,1')
        ->name('candidates.register-passive');

    // ===========================================
    // PUBLIC MARITIME CANDIDATE INTAKE (No auth)
    // Self-registration for maritime candidates
    // ===========================================
    Route::prefix('maritime')->group(function () {
        // Company apply link resolver (public, no auth)
        Route::get('/apply/resolve', [CompanyApplyResolverController::class, 'resolve'])
            ->middleware('throttle:60,1')
            ->name('maritime.apply.resolve');

        // Registration and application
        Route::post('/apply', [MaritimeCandidateController::class, 'apply'])
            ->middleware('throttle:10,1')
            ->name('maritime.apply');

        // Start interview for existing candidate
        Route::post('/candidates/{id}/start-interview', [MaritimeCandidateController::class, 'startInterview'])
            ->middleware('throttle:10,1')
            ->name('maritime.start-interview');

        // Check application status (public - for candidate self-service)
        Route::get('/candidates/{id}/status', [MaritimeCandidateController::class, 'status'])
            ->middleware('throttle:60,1')
            ->name('maritime.status');

        // Update candidate preferred language (public, token-verified)
        Route::post('/candidates/{id}/locale', [MaritimeCandidateController::class, 'updateLocale'])
            ->middleware('throttle:30,1')
            ->name('maritime.locale');

        // Email verification (public, token in URL)
        Route::get('/candidates/verify-email', [MaritimeCandidateController::class, 'verifyEmail'])
            ->middleware('throttle:30,1')
            ->name('maritime.verify-email');

        // Resend verification email
        Route::post('/candidates/{id}/resend-verification', [MaritimeCandidateController::class, 'resendVerification'])
            ->middleware('throttle:5,1')
            ->name('maritime.resend-verification');

        // OTP verification (immediate verification v1)
        Route::post('/candidates/{id}/verify-otp', [MaritimeCandidateController::class, 'verifyOtp'])
            ->middleware('throttle:10,1')
            ->name('maritime.verify-otp');
        Route::post('/candidates/{id}/resend-otp', [MaritimeCandidateController::class, 'resendOtp'])
            ->middleware('throttle:5,1')
            ->name('maritime.resend-otp');

        // GDPR/KVKK — Candidate self-service data rights
        Route::get('/candidates/{id}/data-export', [MaritimeCandidateController::class, 'dataExport'])
            ->middleware('throttle:3,60')
            ->name('maritime.data-export');
        Route::post('/candidates/{id}/erasure-request', [MaritimeCandidateController::class, 'erasureRequest'])
            ->middleware('throttle:2,1440')
            ->name('maritime.erasure-request');

        // Referral system
        Route::get('/candidates/{id}/referral', [MaritimeCandidateController::class, 'getReferral'])
            ->name('maritime.referral');
        Route::get('/candidates/{id}/referral/stats', [MaritimeCandidateController::class, 'referralStats'])
            ->name('maritime.referral.stats');

        // Candidate-facing assessment attach (public, after interview completion)
        Route::post('/candidates/{id}/english/attach', [MaritimeCandidateController::class, 'attachEnglish'])
            ->middleware('throttle:10,1')
            ->name('maritime.english.attach');

        Route::post('/candidates/{id}/video/attach', [MaritimeCandidateController::class, 'attachVideo'])
            ->middleware('throttle:10,1')
            ->name('maritime.video.attach');

        // Candidate notifications (tiered)
        Route::get('/candidates/{id}/notifications', [CandidateNotificationController::class, 'index'])
            ->middleware('throttle:60,1')
            ->name('maritime.notifications');

        Route::post('/candidates/{id}/notifications/read', [CandidateNotificationController::class, 'markRead'])
            ->middleware('throttle:30,1')
            ->name('maritime.notifications.read');

        Route::get('/candidates/{id}/views', [CandidateNotificationController::class, 'viewStats'])
            ->middleware('throttle:60,1')
            ->name('maritime.views');

        // Candidate lifecycle dashboard
        Route::get('/candidates/{id}/lifecycle', [CandidateLifecycleController::class, 'status'])
            ->middleware('throttle:60,1')
            ->name('maritime.lifecycle');
        Route::post('/candidates/{id}/availability', [CandidateLifecycleController::class, 'updateAvailability'])
            ->middleware('throttle:30,1')
            ->name('maritime.availability');
        Route::post('/candidates/{id}/logbook-activate', [CandidateLifecycleController::class, 'logbookActivate'])
            ->middleware('throttle:10,1')
            ->name('maritime.logbook-activate');

        // Vessel reviews (public)
        Route::post('/reviews', [VesselReviewController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('maritime.reviews.store');

        Route::get('/reviews', [VesselReviewController::class, 'index'])
            ->middleware('throttle:60,1')
            ->name('maritime.reviews.index');

        Route::post('/reviews/{id}/report', [VesselReviewController::class, 'report'])
            ->middleware('throttle:5,1')
            ->name('maritime.reviews.report');

        // Profile Activity (tier-gated)
        Route::get('/candidates/{id}/profile-activity', [ProfileActivityController::class, 'index'])
            ->middleware(['throttle:60,1', 'feature.access'])
            ->name('maritime.profile-activity');

        // Push Tokens
        Route::post('/candidates/{id}/push-token', [CandidatePushTokenController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('maritime.push-token.store');
        Route::delete('/candidates/{id}/push-token', [CandidatePushTokenController::class, 'destroy'])
            ->middleware('throttle:10,1')
            ->name('maritime.push-token.destroy');

        // Candidate credentials (wallet)
        Route::get('/candidates/{id}/credentials', [\App\Http\Controllers\Api\CandidateProfileController::class, 'credentials'])
            ->middleware('throttle:60,1')
            ->name('maritime.credentials.index');
        Route::post('/candidates/{id}/credentials', [\App\Http\Controllers\Api\CandidateProfileController::class, 'storeCredential'])
            ->middleware('throttle:20,1')
            ->name('maritime.credentials.store');
        Route::put('/candidates/{candidateId}/credentials/{credentialId}', [\App\Http\Controllers\Api\CandidateProfileController::class, 'updateCredential'])
            ->middleware('throttle:20,1')
            ->name('maritime.credentials.update');

        // Candidate timeline (public — safe types only)
        Route::get('/candidates/{id}/timeline', [\App\Http\Controllers\Api\CandidateProfileController::class, 'timeline'])
            ->middleware('throttle:60,1')
            ->name('maritime.timeline');

        // Maritime Job Feed
        Route::get('/jobs', [MaritimeJobController::class, 'index'])
            ->middleware('throttle:60,1')
            ->name('maritime.jobs.index');
        Route::get('/jobs/{id}', [MaritimeJobController::class, 'show'])
            ->middleware('throttle:60,1')
            ->name('maritime.jobs.show');
        Route::post('/jobs/{id}/apply', [MaritimeJobController::class, 'apply'])
            ->middleware('throttle:10,1')
            ->name('maritime.jobs.apply');

        // Apply Form Event Tracking (drop-off analysis)
        Route::post('/apply-events', [ApplyTrackingController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('maritime.apply-events');

        // Behavioral interview (public, candidate-facing, token-verified)
        Route::prefix('candidates/{id}/behavioral')->whereUuid('id')->group(function () {
            Route::get('template', [\App\Http\Controllers\Api\Maritime\BehavioralInterviewController::class, 'template'])
                ->middleware('throttle:behavioral')
                ->name('maritime.behavioral.template');
            Route::post('answers', [\App\Http\Controllers\Api\Maritime\BehavioralInterviewController::class, 'answers'])
                ->middleware('throttle:behavioral')
                ->name('maritime.behavioral.answers');
            Route::post('complete', [\App\Http\Controllers\Api\Maritime\BehavioralInterviewController::class, 'complete'])
                ->middleware('throttle:behavioral-complete')
                ->name('maritime.behavioral.complete');
        });

        // Interview Engine v2 (public, candidate-facing, token-verified)
        Route::prefix('candidates/{id}/interview-v2')->whereUuid('id')->group(function () {
            Route::post('start', [\App\Http\Controllers\Api\Maritime\InterviewV2Controller::class, 'start'])
                ->middleware('throttle:behavioral')
                ->name('maritime.interview-v2.start');
            Route::post('answer', [\App\Http\Controllers\Api\Maritime\InterviewV2Controller::class, 'answer'])
                ->middleware('throttle:behavioral')
                ->name('maritime.interview-v2.answer');
            Route::post('complete', [\App\Http\Controllers\Api\Maritime\InterviewV2Controller::class, 'complete'])
                ->middleware('throttle:behavioral-complete')
                ->name('maritime.interview-v2.complete');
        });

        // Voice token for interview dictation (public, invitation-token verified)
        Route::post('voice/token', [\App\Http\Controllers\Api\Maritime\VoiceTokenController::class, 'issue'])
            ->middleware('throttle:30,1')
            ->name('maritime.voice.token');
        Route::post('voice/log', [\App\Http\Controllers\Api\Maritime\VoiceTokenController::class, 'log'])
            ->middleware('throttle:60,1')
            ->name('maritime.voice.log');

        // Interview invite signed URL redirect (public, signature-verified)
        Route::get('interview/invite/{invitationId}', [\App\Http\Controllers\Api\Maritime\InterviewInviteController::class, 'redirect'])
            ->middleware(['signed', 'throttle:30,1'])
            ->name('maritime.interview.invite');

        // Clean Interview Workflow v1 (public, invitation-token verified)
        Route::prefix('interview')->group(function () {
            Route::post('start', [\App\Http\Controllers\Api\Maritime\CleanInterviewController::class, 'start'])
                ->middleware('throttle:10,1')
                ->name('maritime.clean-interview.start');
            Route::post('answer', [\App\Http\Controllers\Api\Maritime\CleanInterviewController::class, 'answer'])
                ->middleware('throttle:behavioral')
                ->name('maritime.clean-interview.answer');
            Route::post('complete', [\App\Http\Controllers\Api\Maritime\CleanInterviewController::class, 'complete'])
                ->middleware('throttle:behavioral-complete')
                ->name('maritime.clean-interview.complete');
            Route::post('voice', [\App\Http\Controllers\Api\Maritime\CleanInterviewController::class, 'voice'])
                ->middleware('throttle:30,60')
                ->name('maritime.clean-interview.voice');

            // Voice answer upload + polling (async Whisper transcription via AiModelsPanel)
            Route::post('voice-answers', [\App\Http\Controllers\Api\Maritime\VoiceAnswerController::class, 'store'])
                ->middleware('throttle:5,1')
                ->name('maritime.voice-answers.store');
            Route::get('voice-answers/{questionId}', [\App\Http\Controllers\Api\Maritime\VoiceAnswerController::class, 'show'])
                ->middleware('throttle:30,1')
                ->name('maritime.voice-answers.show');
        });

        // Crew Feedback (public, seafarer submits after contract ends)
        Route::post('/candidates/{id}/crew-feedback', [\App\Http\Controllers\Api\Maritime\CrewFeedbackPublicController::class, 'store'])
            ->whereUuid('id')
            ->middleware('throttle:30,1')
            ->name('maritime.crew-feedback');

        // English test (public, candidate-facing, token-verified + attempt_id)
        Route::prefix('candidates/{id}/english-test')->whereUuid('id')->group(function () {
            Route::post('start', [\App\Http\Controllers\Api\Maritime\EnglishTestController::class, 'start'])
                ->middleware('throttle:english-test-start')
                ->name('maritime.english-test.start');
            Route::post('submit', [\App\Http\Controllers\Api\Maritime\EnglishTestController::class, 'submit'])
                ->middleware('throttle:english-test-submit')
                ->name('maritime.english-test.submit');
        });

        // Form dropdown data
        Route::get('/ranks', [MaritimeCandidateController::class, 'ranks'])
            ->middleware('throttle:120,1')
            ->name('maritime.ranks');

        Route::get('/roles', [MaritimeCandidateController::class, 'roles'])
            ->middleware('throttle:120,1')
            ->name('maritime.roles');

        Route::get('/certificates', [MaritimeCandidateController::class, 'certificates'])
            ->middleware('throttle:120,1')
            ->name('maritime.certificates');

        Route::get('/countries', function () {
            $countries = \Illuminate\Support\Facades\DB::table('countries')
                ->where('active', 1)
                ->orderBy('name')
                ->get(['code', 'name', 'dial_code', 'flag']);
            return response()->json(['data' => $countries]);
        })->middleware('throttle:120,1')->name('maritime.countries');
    });

    // ===========================================
    // MARITIME V2 — Resolver Engine (D+E pipeline)
    // ===========================================
    Route::prefix('maritime/v2')->middleware('throttle:30,1')->group(function () {
        Route::post('/apply', [MaritimeV2Controller::class, 'apply'])->name('maritime.v2.apply');
        Route::post('/phase1/answers', [MaritimeV2Controller::class, 'phase1Answers'])->name('maritime.v2.phase1.answers');
        Route::post('/phase1/complete', [MaritimeV2Controller::class, 'phase1Complete'])->name('maritime.v2.phase1.complete');
        Route::post('/phase2/start', [MaritimeV2Controller::class, 'phase2Start'])->name('maritime.v2.phase2.start');
        Route::post('/phase2/answers', [MaritimeV2Controller::class, 'phase2Answers'])->name('maritime.v2.phase2.answers');
        Route::post('/phase2/complete', [MaritimeV2Controller::class, 'phase2Complete'])->name('maritime.v2.phase2.complete');
    });

    // ===========================================
    // CERTIFICATE UPLOAD (Candidate-facing)
    // ===========================================
    Route::prefix('certificates')->group(function () {
        Route::post('/upload', [CertificateController::class, 'upload'])
            ->middleware('throttle:10,1')
            ->name('certificates.upload');

        Route::get('/{candidateId}', [CertificateController::class, 'status'])
            ->middleware('throttle:60,1')
            ->name('certificates.status');
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
        // Company positions listing (must be before /{token} to avoid slug collision)
        Route::get('/company/{slug}', [PublicApplyController::class, 'companyPositions']);
        // Resume interview from access_token (must be before /{token})
        Route::get('/resume/{accessToken}', [PublicApplyController::class, 'resume']);
        // GET job info for QR landing page
        Route::get('/{token}', [PublicApplyController::class, 'show']);
        // POST start application (create candidate + interview)
        Route::post('/{token}', [PublicApplyController::class, 'start'])
            ->middleware('throttle:10,1');
        // POST verify email (OTP)
        Route::post('/{token}/verify-email', [PublicApplyController::class, 'verifyEmail'])
            ->middleware('throttle:10,1');
        // POST resend verification code
        Route::post('/{token}/resend-code', [PublicApplyController::class, 'resendCode'])
            ->middleware('throttle:3,10');
        // POST schedule interview
        Route::post('/{token}/schedule', [PublicApplyController::class, 'schedule'])
            ->middleware('throttle:5,1');
        // POST submit answer
        Route::post('/{token}/answers', [PublicApplyController::class, 'submitAnswer'])
            ->middleware('throttle:30,1');
        // POST complete interview
        Route::post('/{token}/complete', [PublicApplyController::class, 'complete']);
        // Voice answers (Whisper transcription)
        Route::post('/{token}/voice-answers', [PublicApplyController::class, 'voiceUpload'])
            ->middleware('throttle:30,1');
        Route::get('/{token}/voice-answers/{questionId}', [PublicApplyController::class, 'voicePoll'])
            ->middleware('throttle:60,1');
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

        // Voice answer upload + transcription polling (Whisper)
        Route::post('/{id}/voice-answers', [FormInterviewVoiceController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('form-interviews.voice-answers.store');
        Route::get('/{id}/voice-answers/{questionId}', [FormInterviewVoiceController::class, 'show'])
            ->middleware('throttle:30,1')
            ->name('form-interviews.voice-answers.show');
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

        // Company onboarding flow
        Route::prefix('company/onboard')->group(function () {
            Route::post('/vessel', [CompanyOnboardController::class, 'addVessel']);
            Route::post('/vessel/{vesselId}/ranks', [CompanyOnboardController::class, 'setRankRequirements']);
            Route::post('/vessel/{vesselId}/compliance', [CompanyOnboardController::class, 'activateCompliance']);
            Route::post('/vessel/{vesselId}/crew-analysis', [CompanyOnboardController::class, 'runCrewAnalysis']);
        });

        // Company vessel management (tenant-scoped)
        Route::apiResource('company/vessels', CompanyVesselController::class)
            ->only(['index', 'store', 'destroy']);

        // Company apply links (tenant-scoped)
        Route::apiResource('company/apply-links', CompanyApplyLinkController::class)
            ->only(['index', 'store', 'show', 'destroy']);

        // Company certificate rules (tenant-scoped validity overrides)
        Route::get('company/certificate-rules', [\App\Http\Controllers\Api\CompanyCertificateRuleController::class, 'index']);
        Route::post('company/certificate-rules', [\App\Http\Controllers\Api\CompanyCertificateRuleController::class, 'store']);
        Route::delete('company/certificate-rules/{id}', [\App\Http\Controllers\Api\CompanyCertificateRuleController::class, 'destroy']);

        // Company Competency Models (self-service, tenant-scoped)
        Route::get('competency-library', [\App\Http\Controllers\Api\CompetencyModelSelfServiceController::class, 'competencies']);
        Route::get('competency-models', [\App\Http\Controllers\Api\CompetencyModelSelfServiceController::class, 'index']);
        Route::post('competency-models', [\App\Http\Controllers\Api\CompetencyModelSelfServiceController::class, 'store']);
        Route::put('competency-models/{id}', [\App\Http\Controllers\Api\CompetencyModelSelfServiceController::class, 'update']);
        Route::delete('competency-models/{id}', [\App\Http\Controllers\Api\CompetencyModelSelfServiceController::class, 'destroy']);
        Route::post('competency-models/{id}/set-default', [\App\Http\Controllers\Api\CompetencyModelSelfServiceController::class, 'setDefault']);

        // ===================================================
        // PORTAL — Fleet, Manning, Roster, Crew Planning
        // ===================================================
        Route::prefix('portal')->group(function () {
            // Company profile (logo + settings)
            Route::get('/company-profile', [\App\Http\Controllers\Api\CompanyProfileController::class, 'show']);
            Route::put('/company-profile', [\App\Http\Controllers\Api\CompanyProfileController::class, 'update']);
            Route::post('/company-profile/logo', [\App\Http\Controllers\Api\CompanyProfileController::class, 'uploadLogo']);
            Route::delete('/company-profile/logo', [\App\Http\Controllers\Api\CompanyProfileController::class, 'deleteLogo']);

            // Company permanent apply link (QR for all positions)
            Route::get('/company-apply-link', [JobController::class, 'companyApplyLink']);

            // Portal Job Management (uses existing JobController with company_id filtering)
            Route::prefix('jobs')->group(function () {
                Route::get('/', [JobController::class, 'index']);
                Route::post('/', [JobController::class, 'store']);
                Route::get('/{id}', [JobController::class, 'show']);
                Route::put('/{id}', [JobController::class, 'update']);
                Route::delete('/{id}', [JobController::class, 'destroy']);
                Route::post('/{id}/publish', [JobController::class, 'publish']);
                Route::post('/{id}/close', [JobController::class, 'close']);
                Route::post('/{id}/reactivate', [JobController::class, 'reactivate']);
                Route::post('/{id}/qr-code', [JobController::class, 'generateQRCode']);
                Route::get('/{id}/qr-info', [JobController::class, 'qrInfo']);
                Route::get('/{id}/questions', [JobController::class, 'questions']);
                Route::post('/{id}/generate-questions', [JobController::class, 'generateQuestions']);
            });

            // Portal Candidates (company's own candidates)
            Route::get('/candidates', [\App\Http\Controllers\Api\Portal\PortalCandidateController::class, 'index']);
            Route::get('/candidates/{id}', [\App\Http\Controllers\Api\Portal\PortalCandidateController::class, 'show']);

            // Onboarding finalize
            Route::get('/onboarding', [\App\Http\Controllers\Api\Portal\OnboardingFinalizeController::class, 'show']);
            Route::put('/onboarding', [\App\Http\Controllers\Api\Portal\OnboardingFinalizeController::class, 'update']);

            // Fleet vessels CRUD
            Route::apiResource('vessels', \App\Http\Controllers\Api\Portal\FleetVesselController::class)
                ->names(['index' => 'portal.vessels.index', 'store' => 'portal.vessels.store', 'show' => 'portal.vessels.show', 'update' => 'portal.vessels.update', 'destroy' => 'portal.vessels.destroy']);

            // Vessel registry IMO lookup (local cache)
            Route::get('/vessel-registry/lookup', [\App\Http\Controllers\Api\Portal\FleetVesselController::class, 'registryLookup']);

            // Manning plan per vessel
            Route::get('/vessels/{vesselId}/manning', [\App\Http\Controllers\Api\Portal\VesselManningController::class, 'show']);
            Route::put('/vessels/{vesselId}/manning', [\App\Http\Controllers\Api\Portal\VesselManningController::class, 'update']);

            // Roster / assignments per vessel
            Route::get('/vessels/{vesselId}/roster', [\App\Http\Controllers\Api\Portal\VesselRosterController::class, 'index']);
            Route::post('/vessels/{vesselId}/roster', [\App\Http\Controllers\Api\Portal\VesselRosterController::class, 'store']);
            Route::put('/roster/{assignmentId}', [\App\Http\Controllers\Api\Portal\VesselRosterController::class, 'update']);
            Route::delete('/roster/{assignmentId}', [\App\Http\Controllers\Api\Portal\VesselRosterController::class, 'destroy']);

            // Crew analysis (gap + recommendations)
            Route::get('/vessels/{vesselId}/crew-analysis', [\App\Http\Controllers\Api\Portal\CrewAnalysisController::class, 'show']);
            Route::get('/vessels/{vesselId}/future-pool', [\App\Http\Controllers\Api\Portal\CrewAnalysisController::class, 'futurePool']);

            // Crew planning KPIs
            Route::get('/crew-kpis', [\App\Http\Controllers\Api\Portal\CrewAnalysisController::class, 'kpis']);

            // Candidate search for roster
            Route::get('/candidates/search', [\App\Http\Controllers\Api\Portal\VesselRosterController::class, 'searchCandidates']);

            // Decision Room
            Route::prefix('vessels/{vesselId}/decision-room')->group(function () {
                Route::get('/snapshot', [\App\Http\Controllers\Api\Portal\DecisionRoomController::class, 'vesselSnapshot']);
                Route::get('/shortlist', [\App\Http\Controllers\Api\Portal\DecisionRoomController::class, 'shortlist']);
                Route::get('/compatibility/{candidateId}', [\App\Http\Controllers\Api\Portal\DecisionRoomController::class, 'compatibility']);
                Route::post('/simulate', [\App\Http\Controllers\Api\Portal\DecisionRoomController::class, 'simulate']);
                Route::post('/decide', [\App\Http\Controllers\Api\Portal\DecisionRoomController::class, 'decide']);
                Route::get('/history', [\App\Http\Controllers\Api\Portal\DecisionRoomController::class, 'history']);
                Route::get('/packet/{candidateId}', [\App\Http\Controllers\Api\Portal\DecisionRoomController::class, 'downloadDecisionPacket']);
            });

            // Captain Profiles
            Route::get('/captains/{candidateId}/profile', [\App\Http\Controllers\Api\Portal\CaptainProfileController::class, 'show']);

            // Crew Outcomes (company logs outcome per vessel)
            Route::get('/vessels/{vesselId}/outcomes', [\App\Http\Controllers\Api\Portal\CrewOutcomeController::class, 'index']);
            Route::post('/vessels/{vesselId}/outcomes', [\App\Http\Controllers\Api\Portal\CrewOutcomeController::class, 'store']);

            // Crew Conflict Reports
            Route::post('/vessels/{vesselId}/conflicts', [\App\Http\Controllers\Api\Portal\CrewConflictController::class, 'store']);

            // Synergy Weights (learning status)
            Route::get('/synergy/weights', [\App\Http\Controllers\Api\Portal\SynergyWeightsController::class, 'show']);
            Route::post('/synergy/retrain', [\App\Http\Controllers\Api\Portal\SynergyWeightsController::class, 'retrain']);

            // Vessel Requirement Templates (company overrides)
            Route::get('/vessel-requirements', [\App\Http\Controllers\Api\Portal\VesselRequirementController::class, 'index']);
            Route::get('/vessel-requirements/{typeKey}', [\App\Http\Controllers\Api\Portal\VesselRequirementController::class, 'show']);
            Route::put('/vessel-requirements/{typeKey}', [\App\Http\Controllers\Api\Portal\VesselRequirementController::class, 'update']);
            Route::delete('/vessel-requirements/{typeKey}', [\App\Http\Controllers\Api\Portal\VesselRequirementController::class, 'destroy']);
        });

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
        Route::delete('/{id}', [InterviewController::class, 'destroy']);
        Route::get('/{id}/report.pdf', [InterviewController::class, 'reportPdf']);
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
    // CUSTOMER DECISION PACKET (PDF download)
    // ===========================================
    Route::get('/form-interviews/{id}/decision-packet.pdf', [FormInterviewController::class, 'decisionPacketPdf'])
        ->middleware('throttle:10,1');

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
        Route::get('/', [ReportController::class, 'index']);
        Route::post('/generate', [ReportController::class, 'generate']);
        Route::get('/session/{sessionId}', [ReportController::class, 'listForSession']);
        Route::delete('/{reportId}', [ReportController::class, 'delete']);
    });

    // ===========================================
    // AI PROVIDERS - For all authenticated users
    // ===========================================
    Route::get('/ai-providers/enabled', [AdminCompanyController::class, 'getEnabledProviders']);

    // ===========================================
    // ADMIN FORM INTERVIEWS - Company & Platform Admin
    // Company users see only their own interviews (scoped by company_id)
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
        Route::get('/{id}/candidate-report.pdf', [AdminFormInterviewController::class, 'candidateReportPdf'])
            ->middleware('throttle:10,1');
        Route::patch('/{id}/notes', [AdminFormInterviewController::class, 'updateNotes'])
            ->middleware('throttle:30,1');
        Route::delete('/{id}', [AdminFormInterviewController::class, 'destroy'])
            ->middleware('throttle:30,1');
        // Maritime Decision Engine
        Route::get('/{id}/decision', [\App\Http\Controllers\Api\Admin\FormInterviewDecisionController::class, 'show'])
            ->middleware('throttle:60,1');
        // Assessment stubs (Maritime)
        Route::get('/{id}/assessment-status', [AssessmentStubController::class, 'assessmentStatus'])
            ->middleware('throttle:60,1');
        Route::post('/{id}/english-assessment/complete', [AssessmentStubController::class, 'completeEnglishAssessment'])
            ->middleware('throttle:30,1');
        Route::post('/{id}/video/attach', [AssessmentStubController::class, 'attachVideo'])
            ->middleware('throttle:30,1');
        Route::post('/{id}/video/complete', [AssessmentStubController::class, 'completeVideoAssessment'])
            ->middleware('throttle:30,1');
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
            Route::match(['put', 'patch'], '/{lead}', [LeadController::class, 'update']);
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
            Route::post('/{id}/logo', [AdminCompanyController::class, 'uploadLogo']);
            Route::delete('/{id}/logo', [AdminCompanyController::class, 'deleteLogo']);
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
            Route::get('/interviews/filters', [AdminAnalyticsController::class, 'interviewFilters'])
                ->middleware('throttle:60,1');
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
            // Model versions list
            Route::get('/versions', [MlLearningController::class, 'versions'])
                ->middleware('throttle:60,1');
            // Rollback to specific version
            Route::post('/rollback', [MlLearningController::class, 'rollback'])
                ->middleware('throttle:10,1');
            // Model weight freeze/unfreeze
            Route::post('/versions/{id}/freeze', [MlLearningController::class, 'freeze'])
                ->middleware('throttle:10,1');
            Route::post('/versions/{id}/unfreeze', [MlLearningController::class, 'unfreeze'])
                ->middleware('throttle:10,1');
            // ML stability metrics
            Route::get('/stability', [MlLearningController::class, 'stability'])
                ->middleware('throttle:60,1');
            // Fairness metrics
            Route::get('/fairness', [MlLearningController::class, 'fairness'])
                ->middleware('throttle:60,1');
            // Generate fairness report (for daily job)
            Route::post('/fairness/generate', [MlLearningController::class, 'generateFairnessReport'])
                ->middleware('throttle:5,1');
        });

        // ===========================================
        // ADMIN SUPPLY ANALYTICS - Investor-grade metrics
        // ===========================================
        Route::prefix('admin/analytics/supply')->group(function () {
            // Funnel metrics (registration → hire)
            Route::get('/funnel', [SupplyAnalyticsController::class, 'funnel'])
                ->middleware('throttle:60,1');

            // Channel quality (CAC optimization)
            Route::get('/channel-quality', [SupplyAnalyticsController::class, 'channelQuality'])
                ->middleware('throttle:60,1');

            // Time-to-hire metrics
            Route::get('/time-to-hire', [SupplyAnalyticsController::class, 'timeToHire'])
                ->middleware('throttle:60,1');

            // Pool health metrics
            Route::get('/pool-health', [SupplyAnalyticsController::class, 'poolHealth'])
                ->middleware('throttle:60,1');

            // Company consumption metrics
            Route::get('/company', [SupplyAnalyticsController::class, 'companyMetrics'])
                ->middleware('throttle:60,1');

            // Weekly trends (for charts)
            Route::get('/trends', [SupplyAnalyticsController::class, 'trends'])
                ->middleware('throttle:60,1');

            // Combined dashboard (single call)
            Route::get('/dashboard', [SupplyAnalyticsController::class, 'dashboard'])
                ->middleware('throttle:30,1');
        });

        // ===========================================
        // GEO ANALYTICS + DROP-OFF ANALYSIS
        // ===========================================
        Route::prefix('admin/analytics/geo')->group(function () {
            Route::get('/dashboard', [GeoAnalyticsController::class, 'dashboard'])
                ->middleware('throttle:30,1');
            Route::get('/candidates', [GeoAnalyticsController::class, 'candidatesByCountry'])
                ->middleware('throttle:60,1');
            Route::get('/companies', [GeoAnalyticsController::class, 'companiesByCountry'])
                ->middleware('throttle:60,1');
            Route::get('/hourly', [GeoAnalyticsController::class, 'hourlyDistribution'])
                ->middleware('throttle:60,1');
            Route::get('/heatmap', [GeoAnalyticsController::class, 'heatMap'])
                ->middleware('throttle:60,1');
            Route::get('/drop-off', [GeoAnalyticsController::class, 'dropOff'])
                ->middleware('throttle:60,1');
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
        // ADMIN CANDIDATE POOL - Assessment UX
        // ===========================================
        Route::prefix('admin/candidate-pool')->group(function () {
            Route::get('/stats', [CandidatePoolController::class, 'stats'])
                ->middleware('throttle:60,1');
            Route::get('/action-required', [CandidatePoolController::class, 'actionRequired'])
                ->middleware('throttle:60,1');
            Route::get('/', [CandidatePoolController::class, 'index'])
                ->middleware('throttle:60,1');
            Route::get('/{id}', [CandidatePoolController::class, 'show'])
                ->middleware('throttle:60,1');
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

        // ===========================================
        // STCW & CERTIFICATION ENGINE
        // ===========================================
        Route::prefix('admin/certificates')->group(function () {
            Route::get('/', [CertificationController::class, 'index'])
                ->middleware('throttle:60,1');
            Route::post('/{id}/verify', [CertificationController::class, 'verify'])
                ->middleware('throttle:30,1');
            Route::post('/{id}/reject', [CertificationController::class, 'reject'])
                ->middleware('throttle:30,1');
        });

        Route::get('admin/certificate-types', [CertificationController::class, 'types'])
            ->middleware('throttle:120,1');

        Route::prefix('admin/candidates')->group(function () {
            Route::get('/{id}/certification-status', [CertificationController::class, 'candidateStatus'])
                ->middleware('throttle:60,1');
            Route::get('/{id}/stcw-compliance', [CertificationController::class, 'stcwCompliance'])
                ->middleware('throttle:60,1');
            Route::get('/{id}/certification-summary', [CertificationController::class, 'certificationSummary'])
                ->middleware('throttle:60,1');
        });

        Route::get('admin/talent-requests/{id}/certification-ready', [CertificationController::class, 'certificationReady'])
            ->middleware('throttle:60,1');

        Route::get('admin/certification-analytics', [CertificationController::class, 'analytics'])
            ->middleware('throttle:60,1');

        // ===========================================
        // SALES CRM - Companies, Contacts, Leads, Email, Files
        // ===========================================
        Route::prefix('admin/crm')->group(function () {
            // Companies
            Route::get('/companies', [CrmCompanyController::class, 'index'])
                ->middleware('throttle:120,1');
            Route::post('/companies', [CrmCompanyController::class, 'store'])
                ->middleware('throttle:30,1');
            Route::get('/companies/{id}', [CrmCompanyController::class, 'show'])
                ->middleware('throttle:120,1');
            Route::put('/companies/{id}', [CrmCompanyController::class, 'update'])
                ->middleware('throttle:30,1');

            // Company Crew Import (CSV)
            Route::post('/companies/{companyId}/crew-import/preview', [\App\Http\Controllers\Api\Admin\CompanyCrewImportController::class, 'preview'])
                ->middleware('throttle:30,1');
            Route::post('/companies/{companyId}/crew-import/import', [\App\Http\Controllers\Api\Admin\CompanyCrewImportController::class, 'import'])
                ->middleware('throttle:30,1');

            // Contacts
            Route::post('/contacts', [CrmContactController::class, 'store'])
                ->middleware('throttle:30,1');
            Route::put('/contacts/{id}', [CrmContactController::class, 'update'])
                ->middleware('throttle:30,1');
            Route::delete('/contacts/{id}', [CrmContactController::class, 'destroy'])
                ->middleware('throttle:30,1');

            // Leads
            Route::get('/leads/stats', [CrmLeadController::class, 'stats'])
                ->middleware('throttle:120,1');
            Route::get('/leads', [CrmLeadController::class, 'index'])
                ->middleware('throttle:120,1');
            Route::post('/leads', [CrmLeadController::class, 'store'])
                ->middleware('throttle:30,1');
            Route::get('/leads/{id}', [CrmLeadController::class, 'show'])
                ->middleware('throttle:120,1');
            Route::patch('/leads/{id}', [CrmLeadController::class, 'update'])
                ->middleware('throttle:30,1');
            Route::post('/leads/{id}/note', [CrmLeadController::class, 'addNote'])
                ->middleware('throttle:30,1');
            Route::post('/leads/{id}/tasks', [CrmLeadController::class, 'addTask'])
                ->middleware('throttle:30,1');
            Route::post('/leads/{id}/files', [CrmLeadController::class, 'uploadFile'])
                ->middleware('throttle:30,1');
            Route::post('/leads/{id}/send-email', [CrmLeadController::class, 'sendEmail'])
                ->middleware('throttle:30,1');

            // Tasks
            Route::patch('/tasks/{id}/done', [CrmLeadController::class, 'completeTask'])
                ->middleware('throttle:30,1');

            // Email Templates
            Route::get('/templates', [CrmTemplateController::class, 'index'])
                ->middleware('throttle:120,1');
            Route::post('/templates', [CrmTemplateController::class, 'store'])
                ->middleware('throttle:30,1');
            Route::put('/templates/{id}', [CrmTemplateController::class, 'update'])
                ->middleware('throttle:30,1');
            Route::delete('/templates/{id}', [CrmTemplateController::class, 'destroy'])
                ->middleware('throttle:30,1');
            Route::post('/templates/{id}/preview', [CrmTemplateController::class, 'preview'])
                ->middleware('throttle:60,1');

            // Research (Sprint-6: Jobs + Candidates)
            Route::get('/research/stats', [ResearchController::class, 'stats'])
                ->middleware('throttle:60,1');
            Route::get('/research/jobs', [ResearchController::class, 'jobs'])
                ->middleware('throttle:60,1');
            Route::post('/research/jobs', [ResearchController::class, 'createJob'])
                ->middleware('throttle:30,1');
            Route::get('/research/jobs/{id}', [ResearchController::class, 'showJob'])
                ->middleware('throttle:60,1');
            Route::get('/research/jobs/{id}/candidates', [ResearchController::class, 'candidates'])
                ->middleware('throttle:60,1');
            Route::post('/research/jobs/{id}/candidates', [ResearchController::class, 'addCandidate'])
                ->middleware('throttle:30,1');
            Route::post('/research/candidates/{id}/accept', [ResearchController::class, 'acceptCandidate'])
                ->middleware('throttle:30,1');
            Route::post('/research/candidates/{id}/reject', [ResearchController::class, 'rejectCandidate'])
                ->middleware('throttle:30,1');

            // Research Intelligence (Sprint-7: Companies + Agents)
            Route::post('/research/companies/import', [ResearchController::class, 'importCompanies'])
                ->middleware('throttle:10,1');
            Route::get('/research/companies', [ResearchController::class, 'companies'])
                ->middleware('throttle:60,1');
            Route::get('/research/companies/{id}', [ResearchController::class, 'companyDetail'])
                ->middleware('throttle:60,1');
            Route::post('/research/companies/{id}/push', [ResearchController::class, 'pushCompany'])
                ->middleware('throttle:30,1');
            Route::post('/research/companies/{id}/ignore', [ResearchController::class, 'ignoreCompany'])
                ->middleware('throttle:30,1');
            Route::post('/research/companies/{id}/classify', [ResearchController::class, 'classifyCompany'])
                ->middleware('throttle:30,1');
            Route::get('/research/runs', [ResearchController::class, 'runs'])
                ->middleware('throttle:60,1');
            Route::post('/research/run-agent', [ResearchController::class, 'runAgent'])
                ->middleware('throttle:10,1');

            // Sales Analytics
            Route::get('/analytics/sales', [CrmAnalyticsController::class, 'salesDashboard'])
                ->middleware('throttle:60,1');
            Route::get('/analytics/shipping-campaign', [CrmAnalyticsController::class, 'shippingCampaignMetrics'])
                ->middleware('throttle:60,1');

            // Vessel Reviews (Admin moderation)
            Route::get('/reviews/stats', [VesselReviewAdminController::class, 'stats'])
                ->middleware('throttle:60,1');
            Route::get('/reviews', [VesselReviewAdminController::class, 'index'])
                ->middleware('throttle:60,1');
            Route::post('/reviews/{id}/approve', [VesselReviewAdminController::class, 'approve'])
                ->middleware('throttle:30,1');
            Route::post('/reviews/{id}/reject', [VesselReviewAdminController::class, 'reject'])
                ->middleware('throttle:30,1');
            Route::delete('/reviews/{id}', [VesselReviewAdminController::class, 'destroy'])
                ->middleware('throttle:30,1');

            // Deals Pipeline
            Route::get('/deals/stats', [CrmDealController::class, 'stats'])
                ->middleware('throttle:60,1');
            Route::get('/deals/pipeline', [CrmDealController::class, 'pipeline'])
                ->middleware('throttle:60,1');
            Route::get('/deals', [CrmDealController::class, 'index'])
                ->middleware('throttle:60,1');
            Route::post('/deals', [CrmDealController::class, 'store'])
                ->middleware('throttle:30,1');
            Route::get('/deals/{id}', [CrmDealController::class, 'show'])
                ->middleware('throttle:60,1');
            Route::patch('/deals/{id}', [CrmDealController::class, 'update'])
                ->middleware('throttle:30,1');
            Route::post('/deals/{id}/advance', [CrmDealController::class, 'advanceStage'])
                ->middleware('throttle:30,1');
            Route::post('/deals/{id}/win', [CrmDealController::class, 'win'])
                ->middleware('throttle:30,1');
            Route::post('/deals/{id}/lose', [CrmDealController::class, 'lose'])
                ->middleware('throttle:30,1');

            // Mail Autopilot — Inbox (Sprint-8)
            Route::get('/inbox/stats', [CrmInboxController::class, 'stats'])
                ->middleware('throttle:60,1');
            Route::get('/inbox/threads', [CrmInboxController::class, 'threads'])
                ->middleware('throttle:60,1');
            Route::get('/inbox/threads/{id}', [CrmInboxController::class, 'threadDetail'])
                ->middleware('throttle:60,1');
            Route::patch('/inbox/threads/{id}', [CrmInboxController::class, 'updateThread'])
                ->middleware('throttle:30,1');
            Route::post('/inbox/drafts/{id}/approve', [CrmInboxController::class, 'approveDraft'])
                ->middleware('throttle:30,1');
            Route::post('/inbox/drafts/{id}/reject', [CrmInboxController::class, 'rejectDraft'])
                ->middleware('throttle:30,1');
            Route::patch('/inbox/drafts/{id}', [CrmInboxController::class, 'editDraft'])
                ->middleware('throttle:30,1');
            Route::post('/inbox/threads/{id}/regenerate-draft', [CrmInboxController::class, 'regenerateDraft'])
                ->middleware('throttle:10,1');

            // Mail Autopilot — Sequences (Sprint-8)
            Route::get('/sequences', [CrmSequenceController::class, 'index'])
                ->middleware('throttle:60,1');
            Route::post('/sequences', [CrmSequenceController::class, 'store'])
                ->middleware('throttle:30,1');
            Route::get('/sequences/{id}', [CrmSequenceController::class, 'show'])
                ->middleware('throttle:60,1');
            Route::put('/sequences/{id}', [CrmSequenceController::class, 'update'])
                ->middleware('throttle:30,1');
            Route::post('/sequences/{id}/enroll', [CrmSequenceController::class, 'enroll'])
                ->middleware('throttle:30,1');
            Route::post('/enrollments/{id}/cancel', [CrmSequenceController::class, 'cancelEnrollment'])
                ->middleware('throttle:30,1');

            // Mail Autopilot — Outbound Queue (Sprint-8)
            Route::get('/outbound-queue', [CrmOutboundQueueController::class, 'index'])
                ->middleware('throttle:60,1');
            Route::post('/outbound-queue/{id}/approve', [CrmOutboundQueueController::class, 'approve'])
                ->middleware('throttle:30,1');
            Route::post('/outbound-queue/bulk-approve', [CrmOutboundQueueController::class, 'bulkApprove'])
                ->middleware('throttle:30,1');
            Route::post('/outbound-queue/{id}/reject', [CrmOutboundQueueController::class, 'reject'])
                ->middleware('throttle:30,1');
        });

        // ===========================================
        // ADMIN MARITIME JOBS
        // ===========================================
        Route::prefix('admin/maritime-jobs')->group(function () {
            Route::get('/', [MaritimeJobAdminController::class, 'index'])
                ->middleware('throttle:60,1');
            Route::post('/', [MaritimeJobAdminController::class, 'store'])
                ->middleware('throttle:30,1');
            Route::get('/{id}', [MaritimeJobAdminController::class, 'show'])
                ->middleware('throttle:60,1');
            Route::put('/{id}', [MaritimeJobAdminController::class, 'update'])
                ->middleware('throttle:30,1');
        });

        // ===========================================
        // ADMIN JOB LISTINGS
        // ===========================================
        Route::prefix('admin/jobs')->group(function () {
            Route::get('/', [JobListingController::class, 'index'])
                ->middleware('throttle:60,1');
            Route::post('/', [JobListingController::class, 'store'])
                ->middleware('throttle:30,1');
            Route::get('/{jobListing}', [JobListingController::class, 'show'])
                ->middleware('throttle:60,1');
            Route::patch('/{jobListing}', [JobListingController::class, 'update'])
                ->middleware('throttle:30,1');
            Route::post('/{jobListing}/publish', [JobListingController::class, 'publish'])
                ->middleware('throttle:30,1');
            Route::post('/{jobListing}/unpublish', [JobListingController::class, 'unpublish'])
                ->middleware('throttle:30,1');
            Route::get('/{jobListing}/applicants', [JobApplicantsController::class, 'index'])
                ->middleware('throttle:60,1');
        });

        // ===========================================
        // SYSTEM HEALTH & OBSERVABILITY
        // ===========================================
        Route::prefix('admin/system')->group(function () {
            Route::get('/health', [SystemHealthController::class, 'health'])
                ->middleware('throttle:60,1');
            Route::get('/events', [SystemHealthController::class, 'events'])
                ->middleware('throttle:60,1');
        });

        // ===========================================
        // DEMO MODE - Investor Demo Candidate Creation
        // ===========================================
        Route::prefix('admin/demo')->group(function () {
            Route::post('/create-candidate', [DemoController::class, 'createCandidate'])
                ->middleware('throttle:10,1');
            Route::get('/candidates', [DemoController::class, 'candidates'])
                ->middleware('throttle:60,1');
            Route::delete('/cleanup', [DemoController::class, 'cleanup'])
                ->middleware('throttle:5,1');
        });

        // ===========================================
        // ADMIN DASHBOARD - Superadmin KPI stats
        // ===========================================
        Route::get('admin/dashboard/stats', [AdminDashboardController::class, 'stats'])
            ->middleware('throttle:60,1');

        // ===========================================
        // ADMIN DEMO REQUESTS - TalentQX demo request management
        // ===========================================
        Route::prefix('admin/demo-requests')->group(function () {
            Route::get('/stats', [AdminDemoRequestController::class, 'stats'])
                ->middleware('throttle:60,1');
            Route::get('/', [AdminDemoRequestController::class, 'index'])
                ->middleware('throttle:60,1');
            Route::get('/{id}', [AdminDemoRequestController::class, 'show'])
                ->middleware('throttle:60,1');
            Route::patch('/{id}/status', [AdminDemoRequestController::class, 'updateStatus'])
                ->middleware('throttle:30,1');
        });

        // ---- Superadmin OrgHealth ----
        Route::prefix('superadmin/orghealth')->group(function () {
            Route::get('/tenants', [\App\Http\Controllers\V1\Superadmin\OrgHealthEmployeeImportController::class, 'tenants']);
            Route::get('/employees/template.csv', [\App\Http\Controllers\V1\Superadmin\OrgHealthEmployeeImportController::class, 'template']);
            Route::post('/employees/import', [\App\Http\Controllers\V1\Superadmin\OrgHealthEmployeeImportController::class, 'import']);
        });

    }); // End of platform.admin middleware group

        }); // End of force.password.change middleware group
    }); // End of auth:sanctum middleware group

    // ===========================================
    // ORGHEALTH MODULE (WorkStyle v1)
    // ===========================================
    Route::middleware(['auth:sanctum'])
        ->prefix('orghealth')
        ->group(function () {
            Route::get('/questionnaires/workstyle/active', [\App\Http\Controllers\V1\OrgHealth\WorkstyleQuestionnaireController::class, 'active']);

            Route::post('/employees/{employeeId}/consents', [\App\Http\Controllers\V1\OrgHealth\OrgEmployeeConsentController::class, 'upsert']);

            Route::post('/employees/{employeeId}/assessments/workstyle/start', [\App\Http\Controllers\V1\OrgHealth\WorkstyleAssessmentController::class, 'start']);
            Route::post('/assessments/{assessmentId}/answers', [\App\Http\Controllers\V1\OrgHealth\WorkstyleAssessmentController::class, 'saveAnswers']);
            Route::post('/assessments/{assessmentId}/complete', [\App\Http\Controllers\V1\OrgHealth\WorkstyleAssessmentController::class, 'complete']);

            Route::get('/employees/{employeeId}/workstyle/profile/latest', [\App\Http\Controllers\V1\OrgHealth\WorkstyleProfileController::class, 'latest']);
            Route::get('/employees/{employeeId}/workstyle/profile/history', [\App\Http\Controllers\V1\OrgHealth\WorkstyleProfileController::class, 'history']);

            // HR Aggregate — WorkStyle
            Route::get('/hr/workstyle/aggregate', [\App\Http\Controllers\V1\OrgHealth\WorkstyleAggregateController::class, 'index']);

            // ---- Culture v1 ----
            Route::get('/questionnaires/culture/active', [\App\Http\Controllers\V1\OrgHealth\CultureQuestionnaireController::class, 'active']);
            Route::post('/employees/{employeeId}/assessments/culture/start', [\App\Http\Controllers\V1\OrgHealth\CultureAssessmentController::class, 'start']);
            Route::post('/assessments/{assessmentId}/culture-answers', [\App\Http\Controllers\V1\OrgHealth\CultureAssessmentController::class, 'saveAnswers']);
            Route::post('/assessments/{assessmentId}/culture-complete', [\App\Http\Controllers\V1\OrgHealth\CultureAssessmentController::class, 'complete']);

            // HR Aggregate — Culture
            Route::get('/hr/culture/aggregate', [\App\Http\Controllers\V1\OrgHealth\CultureAggregateController::class, 'index']);

            // HR Culture Invites
            Route::post('/hr/culture/invites/send', [\App\Http\Controllers\V1\OrgHealth\HrCultureInviteController::class, 'send']);
            Route::get('/hr/culture/invites/status', [\App\Http\Controllers\V1\OrgHealth\HrCultureInviteController::class, 'status']);

            // ---- Pulse v1 ----
            Route::get('/questionnaires/pulse/active', [\App\Http\Controllers\V1\OrgHealth\PulseQuestionnaireController::class, 'active']);
            Route::post('/employees/{employeeId}/assessments/pulse/start', [\App\Http\Controllers\V1\OrgHealth\PulseAssessmentController::class, 'start']);
            Route::post('/assessments/{assessmentId}/pulse-answers', [\App\Http\Controllers\V1\OrgHealth\PulseAssessmentController::class, 'saveAnswers']);
            Route::post('/assessments/{assessmentId}/pulse-complete', [\App\Http\Controllers\V1\OrgHealth\PulseAssessmentController::class, 'complete']);

            // Employee pulse profile (own data, no risk)
            Route::get('/employees/{employeeId}/pulse/profile/latest', [\App\Http\Controllers\V1\OrgHealth\PulseProfileController::class, 'latest']);
            Route::get('/employees/{employeeId}/pulse/profile/history', [\App\Http\Controllers\V1\OrgHealth\PulseProfileController::class, 'history']);

            // HR Pulse Risk Dashboard
            Route::get('/hr/pulse/risk', [\App\Http\Controllers\V1\OrgHealth\HrPulseRiskController::class, 'index']);
            Route::get('/hr/pulse/risk/{employeeId}', [\App\Http\Controllers\V1\OrgHealth\HrPulseRiskController::class, 'show']);
        });

    // Culture invite validate — public (no auth), employee clicks magic link
    Route::post('orghealth/culture/invites/validate', [\App\Http\Controllers\V1\OrgHealth\CultureInviteController::class, 'validate']);

}); // End of v1 prefix group

// ===========================================
// OCTOPUS ADMIN ROUTES (separate auth scope)
// ===========================================
Route::prefix('v1/octopus/admin')->group(function () {
    // Public login (rate limited)
    Route::post('/login', [\App\Http\Controllers\Api\OctopusAdmin\AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    // Protected routes (scoped octopus admin token)
    Route::middleware(['auth:sanctum', 'platform.octopus_admin'])->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\OctopusAdmin\AuthController::class, 'logout']);
        Route::get('/me', [\App\Http\Controllers\Api\OctopusAdmin\AuthController::class, 'me']);
        Route::get('/dashboard', \App\Http\Controllers\Api\OctopusAdmin\DashboardController::class);
        Route::post('/onboard', \App\Http\Controllers\Api\OctopusAdmin\OnboardController::class);

        // Candidates (maritime pool)
        Route::get('/candidates', [\App\Http\Controllers\Api\OctopusAdmin\CandidateController::class, 'index']);
        Route::get('/candidates/stats', [\App\Http\Controllers\Api\OctopusAdmin\CandidateController::class, 'stats']);
        Route::get('/candidates/{id}', [\App\Http\Controllers\Api\OctopusAdmin\CandidateController::class, 'show'])->whereUuid('id');
        Route::get('/candidates/{id}/timeline', [\App\Http\Controllers\Api\OctopusAdmin\CandidateController::class, 'timeline'])->whereUuid('id');
        Route::post('/candidates/{id}/present', [\App\Http\Controllers\Api\OctopusAdmin\CandidateController::class, 'present']);
        Route::post('/candidates/{id}/hire', [\App\Http\Controllers\Api\OctopusAdmin\CandidateController::class, 'hire']);
        Route::get('/candidates/{id}/compliance-pack.pdf', [\App\Http\Controllers\Api\OctopusAdmin\CandidateController::class, 'compliancePdf'])
            ->whereUuid('id')
            ->middleware('throttle:10,1');
        Route::get('/candidates/{id}/decision-packet.pdf', [\App\Http\Controllers\Api\OctopusAdmin\CandidateController::class, 'decisionPacketPdf'])
            ->whereUuid('id')
            ->middleware('throttle:10,1');
        Route::post('/candidates/{id}/executive-decision-override', [\App\Http\Controllers\Api\OctopusAdmin\CandidateController::class, 'executiveDecisionOverride'])
            ->whereUuid('id');
        Route::post('/candidates/{id}/send-interview-invite', [\App\Http\Controllers\Api\OctopusAdmin\CandidateController::class, 'sendInterviewInvite'])
            ->whereUuid('id');
        Route::delete('/candidates/{id}/erase', [\App\Http\Controllers\Api\OctopusAdmin\CandidateController::class, 'eraseCandidate'])
            ->whereUuid('id');
        Route::get('/candidates/{id}/data-export', [\App\Http\Controllers\Api\OctopusAdmin\CandidateController::class, 'exportCandidateData'])
            ->whereUuid('id');

        // Contracts (Trust Core)
        Route::get('/candidates/{id}/contracts', [\App\Http\Controllers\Api\OctopusAdmin\ContractController::class, 'index'])->whereUuid('id');
        Route::post('/candidates/{id}/contracts', [\App\Http\Controllers\Api\OctopusAdmin\ContractController::class, 'store'])->whereUuid('id');
        Route::put('/candidates/{id}/contracts/{contractId}', [\App\Http\Controllers\Api\OctopusAdmin\ContractController::class, 'update'])->whereUuid('id')->whereUuid('contractId');
        Route::delete('/candidates/{id}/contracts/{contractId}', [\App\Http\Controllers\Api\OctopusAdmin\ContractController::class, 'destroy'])->whereUuid('id')->whereUuid('contractId');
        Route::get('/candidates/{id}/cri', [\App\Http\Controllers\Api\OctopusAdmin\CandidateController::class, 'cri'])->whereUuid('id');
        Route::post('/candidates/{id}/contracts/{contractId}/verify', [\App\Http\Controllers\Api\OctopusAdmin\ContractController::class, 'verify'])->whereUuid('id')->whereUuid('contractId');

        // AIS Verification
        Route::post('/candidates/{id}/contracts/{contractId}/ais/verify', [\App\Http\Controllers\Api\OctopusAdmin\ContractController::class, 'aisVerify'])->whereUuid('id')->whereUuid('contractId');
        Route::put('/candidates/{id}/contracts/{contractId}/vessel-imo', [\App\Http\Controllers\Api\OctopusAdmin\ContractController::class, 'setVesselImo'])->whereUuid('id')->whereUuid('contractId');
        Route::post('/vessels/{imo}/refresh', [\App\Http\Controllers\Api\OctopusAdmin\ContractController::class, 'refreshVessel']);

        // Jobs (maritime)
        Route::get('/jobs', [\App\Http\Controllers\Api\OctopusAdmin\JobController::class, 'index']);

        // Interviews (maritime)
        Route::get('/interviews', [\App\Http\Controllers\Api\OctopusAdmin\InterviewController::class, 'index']);
        Route::get('/interviews/{id}', [\App\Http\Controllers\Api\OctopusAdmin\InterviewController::class, 'show'])
            ->whereUuid('id');
        Route::get('/interviews/{id}/report.pdf', [\App\Http\Controllers\Api\OctopusAdmin\InterviewController::class, 'reportPdf'])
            ->middleware('throttle:10,1');
        Route::post('/interviews/{id}/override-class', [\App\Http\Controllers\Api\OctopusAdmin\InterviewController::class, 'overrideClass'])
            ->whereUuid('id');

        // Certificates (seafarer)
        Route::get('/certificates', [\App\Http\Controllers\Api\OctopusAdmin\CertificateController::class, 'index']);

        // Analytics
        Route::get('/analytics/dashboard', [\App\Http\Controllers\Api\OctopusAdmin\AnalyticsController::class, 'dashboard']);
        Route::get('/analytics/country-map', [\App\Http\Controllers\Api\OctopusAdmin\AnalyticsController::class, 'countryMap']);
        Route::get('/analytics/trends', [\App\Http\Controllers\Api\OctopusAdmin\AnalyticsController::class, 'trends']);
        Route::get('/analytics/kpi', [\App\Http\Controllers\Api\OctopusAdmin\AnalyticsController::class, 'kpi']);

        // Company Credits
        Route::get('/credits', [\App\Http\Controllers\Api\OctopusAdmin\CompanyCreditController::class, 'index']);
        Route::patch('/credits/{id}', [\App\Http\Controllers\Api\OctopusAdmin\CompanyCreditController::class, 'update']);
        Route::post('/credits/{id}/bonus', [\App\Http\Controllers\Api\OctopusAdmin\CompanyCreditController::class, 'addBonus']);
        Route::post('/credits/{id}/reset', [\App\Http\Controllers\Api\OctopusAdmin\CompanyCreditController::class, 'resetUsage']);

        // Command Engine v2 (debug/inspection)
        Route::get('/command-classes', [\App\Http\Controllers\Api\OctopusAdmin\CommandProfileController::class, 'classes']);
        Route::get('/command-profiles/{candidateId}', [\App\Http\Controllers\Api\OctopusAdmin\CommandProfileController::class, 'show']);

        // Scenario Bank
        Route::prefix('scenario-bank')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\OctopusAdmin\ScenarioBankController::class, 'index']);
            Route::get('/{id}', [\App\Http\Controllers\Api\OctopusAdmin\ScenarioBankController::class, 'show'])->whereUuid('id');
            Route::put('/{id}', [\App\Http\Controllers\Api\OctopusAdmin\ScenarioBankController::class, 'update'])->whereUuid('id');
            Route::post('/{id}/activate', [\App\Http\Controllers\Api\OctopusAdmin\ScenarioBankController::class, 'activate'])->whereUuid('id');
            Route::post('/{id}/deactivate', [\App\Http\Controllers\Api\OctopusAdmin\ScenarioBankController::class, 'deactivate'])->whereUuid('id');
        });

        // Fleet Risk Map
        Route::get('/fleet/overview', [\App\Http\Controllers\Api\OctopusAdmin\FleetController::class, 'overview']);
        Route::get('/fleet/vessels/{id}/risk-map', [\App\Http\Controllers\Api\OctopusAdmin\FleetController::class, 'vesselRiskMap'])->whereUuid('id');

        // Crew Synergy
        Route::get('/candidates/{id}/crew-synergy', [\App\Http\Controllers\Api\OctopusAdmin\FleetController::class, 'crewSynergy'])->whereUuid('id');

        // Crew Planning
        Route::get('/fleet/crew-gaps', [\App\Http\Controllers\Api\OctopusAdmin\FleetController::class, 'crewGaps']);
        Route::get('/fleet/vessels/{id}/gaps', [\App\Http\Controllers\Api\OctopusAdmin\FleetController::class, 'vesselGaps'])->whereUuid('id');
        Route::get('/fleet/vessels/{id}/recommend', [\App\Http\Controllers\Api\OctopusAdmin\FleetController::class, 'recommendCandidates'])->whereUuid('id');

        // Crew Synergy Engine V2
        Route::get('/fleet/vessels/{id}/compatibility/{candidateId}', [\App\Http\Controllers\Api\OctopusAdmin\FleetController::class, 'candidateCompatibility'])->whereUuid('id')->whereUuid('candidateId');
        Route::get('/fleet/vessels/{id}/shortlist', [\App\Http\Controllers\Api\OctopusAdmin\FleetController::class, 'shortlistCandidates'])->whereUuid('id');
        Route::get('/fleet/vessels/{id}/gaps-v2', [\App\Http\Controllers\Api\OctopusAdmin\FleetController::class, 'vesselGapsV2'])->whereUuid('id');
        Route::get('/fleet/vessels/{id}/crew-history', [\App\Http\Controllers\Api\OctopusAdmin\FleetController::class, 'vesselCrewHistory'])->whereUuid('id');

        // Crew Availability Insights (Admin)
        Route::get('/fleet/availability-insights', [\App\Http\Controllers\Api\OctopusAdmin\FleetController::class, 'availabilityInsights']);

        // Crew Feedback (Admin)
        Route::get('/fleet/crew-feedback', [\App\Http\Controllers\Api\OctopusAdmin\CrewFeedbackController::class, 'index']);
        Route::get('/candidates/{id}/crew-feedback', [\App\Http\Controllers\Api\OctopusAdmin\CrewFeedbackController::class, 'forCandidate'])->whereUuid('id');
        Route::patch('/fleet/crew-feedback/{id}', [\App\Http\Controllers\Api\OctopusAdmin\CrewFeedbackController::class, 'moderate'])->whereUuid('id');

        // Captain Learning (Admin)
        Route::get('/learning/suspicious-feedback', [\App\Http\Controllers\Api\OctopusAdmin\CaptainLearningAdminController::class, 'suspiciousFeedback']);
        Route::get('/learning/metrics', [\App\Http\Controllers\Api\OctopusAdmin\CaptainLearningAdminController::class, 'metrics']);
        Route::post('/learning/retrain-global', [\App\Http\Controllers\Api\OctopusAdmin\CaptainLearningAdminController::class, 'retrainGlobal']);

        // Language Assessment
        Route::get('/candidates/{id}/language-assessment', [\App\Http\Controllers\Api\OctopusAdmin\LanguageAssessmentController::class, 'show'])->whereUuid('id');
        Route::post('/candidates/{id}/language-assessment/start', [\App\Http\Controllers\Api\OctopusAdmin\LanguageAssessmentController::class, 'start'])->whereUuid('id');
        Route::post('/candidates/{id}/language-assessment/submit', [\App\Http\Controllers\Api\OctopusAdmin\LanguageAssessmentController::class, 'submit'])->whereUuid('id');
        Route::post('/candidates/{id}/language-assessment/interview-verify', [\App\Http\Controllers\Api\OctopusAdmin\LanguageAssessmentController::class, 'interviewVerify'])->whereUuid('id');
        Route::post('/candidates/{id}/language-assessment/lock', [\App\Http\Controllers\Api\OctopusAdmin\LanguageAssessmentController::class, 'lock'])->whereUuid('id');

        // Decision Panel
        Route::get('/candidates/{id}/decision-panel', \App\Http\Controllers\Api\OctopusAdmin\CandidateDecisionPanelController::class)->whereUuid('id');
        Route::post('/candidates/{id}/qualifications/{qualificationKey}', [\App\Http\Controllers\Api\OctopusAdmin\QualificationCheckController::class, 'upsert'])->whereUuid('id');
        Route::post('/candidates/{id}/phases/{phaseKey}/review', [\App\Http\Controllers\Api\OctopusAdmin\PhaseReviewController::class, 'review'])->whereUuid('id');

        // Maritime Insights (Phase D)
        Route::get('/maritime/insights', [\App\Http\Controllers\Api\OctopusAdmin\MaritimeInsightsController::class, 'insights']);
        Route::get('/maritime/invite-runs', [\App\Http\Controllers\Api\OctopusAdmin\MaritimeInsightsController::class, 'inviteRuns']);
        Route::get('/candidates/{id}/signals', [\App\Http\Controllers\Api\OctopusAdmin\MaritimeInsightsController::class, 'candidateSignals'])->whereUuid('id');

        // Role-Fit Metrics (observability)
        Route::get('/maritime/role-fit/metrics', \App\Http\Controllers\Api\OctopusAdmin\RoleFitMetricsController::class);

        // Demo Requests Admin (Phase D)
        Route::get('/demo-requests', [\App\Http\Controllers\Api\OctopusAdmin\DemoRequestAdminController::class, 'index']);
        Route::get('/demo-requests/stats', [\App\Http\Controllers\Api\OctopusAdmin\DemoRequestAdminController::class, 'stats']);

        // Unverified QR Applications (email verification report)
        Route::get('/unverified-applications', [\App\Http\Controllers\Api\OctopusAdmin\UnverifiedApplicationController::class, 'index']);
        Route::get('/unverified-applications/stats', [\App\Http\Controllers\Api\OctopusAdmin\UnverifiedApplicationController::class, 'stats']);

        // Country Certificate Rules (Validity Map)
        Route::get('/country-certificate-rules', [\App\Http\Controllers\Api\OctopusAdmin\CountryCertificateRuleController::class, 'index']);
        Route::post('/country-certificate-rules', [\App\Http\Controllers\Api\OctopusAdmin\CountryCertificateRuleController::class, 'store']);
        Route::delete('/country-certificate-rules/{id}', [\App\Http\Controllers\Api\OctopusAdmin\CountryCertificateRuleController::class, 'destroy']);

        // Interview Engine v2 Admin — question set management
        Route::get('/interview-v2/question-sets', [\App\Http\Controllers\Api\OctopusAdmin\InterviewV2AdminController::class, 'index']);
        Route::post('/interview-v2/question-sets/{id}/activate', [\App\Http\Controllers\Api\OctopusAdmin\InterviewV2AdminController::class, 'activate'])->whereUuid('id');
        Route::post('/interview-v2/question-sets/{id}/deactivate', [\App\Http\Controllers\Api\OctopusAdmin\InterviewV2AdminController::class, 'deactivate'])->whereUuid('id');

        // Tenant Feature Flags (admin toggle + audit)
        Route::get('/tenants/{tenantId}/features', [\App\Http\Controllers\Api\OctopusAdmin\TenantFeatureFlagController::class, 'index']);
        Route::put('/tenants/{tenantId}/features/{featureKey}', [\App\Http\Controllers\Api\OctopusAdmin\TenantFeatureFlagController::class, 'upsert']);

        // System Status (lightweight health summary for status pill)
        Route::get('/system/status', \App\Http\Controllers\Api\OctopusAdmin\SystemStatusController::class);

        // Vessel Requirement Templates (draft/publish workflow with validation)
        Route::get('/vessel-requirement-templates', [\App\Http\Controllers\Api\OctopusAdmin\VesselRequirementTemplateController::class, 'index']);
        Route::get('/vessel-requirement-templates/{id}', [\App\Http\Controllers\Api\OctopusAdmin\VesselRequirementTemplateController::class, 'show']);
        Route::post('/vessel-requirement-templates', [\App\Http\Controllers\Api\OctopusAdmin\VesselRequirementTemplateController::class, 'store']);
        Route::put('/vessel-requirement-templates/{id}', [\App\Http\Controllers\Api\OctopusAdmin\VesselRequirementTemplateController::class, 'update']);
        Route::post('/vessel-requirement-templates/{id}/publish', [\App\Http\Controllers\Api\OctopusAdmin\VesselRequirementTemplateController::class, 'publish']);
        Route::post('/vessel-requirement-templates/{id}/revert', [\App\Http\Controllers\Api\OctopusAdmin\VesselRequirementTemplateController::class, 'revert']);

        // Company Vessel Requirement Overrides
        Route::get('/company-vessel-overrides', [\App\Http\Controllers\Api\OctopusAdmin\CompanyVesselRequirementOverrideController::class, 'index']);
        Route::post('/company-vessel-overrides', [\App\Http\Controllers\Api\OctopusAdmin\CompanyVesselRequirementOverrideController::class, 'store']);
        Route::delete('/company-vessel-overrides/{id}', [\App\Http\Controllers\Api\OctopusAdmin\CompanyVesselRequirementOverrideController::class, 'destroy']);

        // Marketplace Admin (access request management)
        Route::prefix('marketplace')->group(function () {
            Route::get('/requests', [\App\Http\Controllers\Api\Admin\MarketplaceAdminController::class, 'index']);
            Route::get('/stats', [\App\Http\Controllers\Api\Admin\MarketplaceAdminController::class, 'stats']);
            Route::post('/requests/{id}/approve', [\App\Http\Controllers\Api\Admin\MarketplaceAdminController::class, 'approve'])->whereUuid('id');
            Route::post('/requests/{id}/reject', [\App\Http\Controllers\Api\Admin\MarketplaceAdminController::class, 'reject'])->whereUuid('id');
        });

        // Admin Notifications + Push Subscriptions
        Route::get('/notifications', [\App\Http\Controllers\Api\OctopusAdmin\AdminNotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [\App\Http\Controllers\Api\OctopusAdmin\AdminNotificationController::class, 'unreadCount']);
        Route::post('/notifications/mark-read', [\App\Http\Controllers\Api\OctopusAdmin\AdminNotificationController::class, 'markRead']);
        Route::post('/notifications/push-subscriptions', [\App\Http\Controllers\Api\OctopusAdmin\AdminNotificationController::class, 'subscribe']);
        Route::delete('/notifications/push-subscriptions', [\App\Http\Controllers\Api\OctopusAdmin\AdminNotificationController::class, 'unsubscribe']);

        // Crew Roster Import (Excel)
        Route::prefix('imports/crew-roster')->group(function () {
            Route::post('/preview', [\App\Http\Controllers\Api\OctopusAdmin\CrewRosterImportController::class, 'preview'])
                ->middleware('throttle:30,1');
            Route::post('/', [\App\Http\Controllers\Api\OctopusAdmin\CrewRosterImportController::class, 'import'])
                ->middleware('throttle:30,1');
            Route::get('/history', [\App\Http\Controllers\Api\OctopusAdmin\CrewRosterImportController::class, 'history']);
        });

        // Company Competency Models (admin manages per-company models)
        Route::get('/competencies', [\App\Http\Controllers\Api\OctoAdmin\CompetencyModelController::class, 'competencies']);
        Route::get('/companies/{companyId}/competency-models', [\App\Http\Controllers\Api\OctoAdmin\CompetencyModelController::class, 'index']);
        Route::post('/companies/{companyId}/competency-models', [\App\Http\Controllers\Api\OctoAdmin\CompetencyModelController::class, 'store']);
        Route::put('/competency-models/{id}', [\App\Http\Controllers\Api\OctoAdmin\CompetencyModelController::class, 'update']);
        Route::delete('/competency-models/{id}', [\App\Http\Controllers\Api\OctoAdmin\CompetencyModelController::class, 'destroy']);
        Route::post('/competency-models/{id}/set-default', [\App\Http\Controllers\Api\OctoAdmin\CompetencyModelController::class, 'setDefault']);
    });
});

// ===========================================
// COMPANY PANEL ROUTES (internal management)
// ===========================================
Route::prefix('v1/company-panel')->middleware('force.default_db')->group(function () {
    // Public login (rate limited)
    Route::post('/login', [\App\Http\Controllers\Api\CompanyPanel\AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    // Protected routes — force.default_db already active, then sanctum checks against mysql
    Route::middleware(['force.default_db', 'auth:sanctum', 'platform.company_panel'])->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\CompanyPanel\AuthController::class, 'logout']);
        Route::get('/me', [\App\Http\Controllers\Api\CompanyPanel\AuthController::class, 'me']);
        Route::post('/change-password', [\App\Http\Controllers\Api\CompanyPanel\AuthController::class, 'changePassword']);

        // Phase 2: User management (super_admin only)
        Route::prefix('users')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\CompanyPanel\UserController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\CompanyPanel\UserController::class, 'store']);
            Route::get('/{id}', [\App\Http\Controllers\Api\CompanyPanel\UserController::class, 'show'])->whereUuid('id');
            Route::put('/{id}', [\App\Http\Controllers\Api\CompanyPanel\UserController::class, 'update'])->whereUuid('id');
        });

        // Phase 2: API Key management (super_admin only)
        Route::prefix('api-keys')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\CompanyPanel\SystemApiKeyController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\Api\CompanyPanel\SystemApiKeyController::class, 'store']);
            Route::put('/{id}', [\App\Http\Controllers\Api\CompanyPanel\SystemApiKeyController::class, 'update'])->whereUuid('id');
            Route::delete('/{id}', [\App\Http\Controllers\Api\CompanyPanel\SystemApiKeyController::class, 'destroy'])->whereUuid('id');
            Route::post('/{id}/test', [\App\Http\Controllers\Api\CompanyPanel\SystemApiKeyController::class, 'test'])->whereUuid('id');
        });

        // Phase 3: CRM (sales_rep + super_admin)
        Route::prefix('crm')->group(function () {
            Route::get('/leads', [\App\Http\Controllers\Api\CompanyPanel\CrmController::class, 'index']);
            Route::post('/leads', [\App\Http\Controllers\Api\CompanyPanel\CrmController::class, 'store']);
            Route::get('/leads/pipeline', [\App\Http\Controllers\Api\CompanyPanel\CrmController::class, 'pipeline']);
            Route::get('/leads/{id}', [\App\Http\Controllers\Api\CompanyPanel\CrmController::class, 'show'])->whereUuid('id');
            Route::put('/leads/{id}', [\App\Http\Controllers\Api\CompanyPanel\CrmController::class, 'update'])->whereUuid('id');
            Route::post('/leads/{id}/activity', [\App\Http\Controllers\Api\CompanyPanel\CrmController::class, 'addActivity'])->whereUuid('id');
            Route::post('/leads/{id}/checklist/{itemId}/toggle', [\App\Http\Controllers\Api\CompanyPanel\CrmController::class, 'toggleChecklist'])->whereUuid('id')->whereUuid('itemId');
        });

        // Phase 3: Demo flow
        Route::prefix('demo')->group(function () {
            Route::get('/leads/{id}/context', [\App\Http\Controllers\Api\CompanyPanel\DemoController::class, 'context'])->whereUuid('id');
            Route::post('/leads/{id}/create-account', [\App\Http\Controllers\Api\CompanyPanel\DemoController::class, 'createAccount'])->whereUuid('id');
            Route::post('/leads/{id}/package', [\App\Http\Controllers\Api\CompanyPanel\DemoController::class, 'setPackage'])->whereUuid('id');
            Route::post('/leads/{id}/appointment', [\App\Http\Controllers\Api\CompanyPanel\DemoController::class, 'scheduleAppointment'])->whereUuid('id');
        });

        // Phase 4: Calendar & Appointments
        Route::prefix('calendar')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\CompanyPanel\CalendarController::class, 'index']);
            Route::get('/availability', [\App\Http\Controllers\Api\CompanyPanel\CalendarController::class, 'availability']);
            Route::post('/appointments', [\App\Http\Controllers\Api\CompanyPanel\CalendarController::class, 'store']);
            Route::put('/appointments/{id}', [\App\Http\Controllers\Api\CompanyPanel\CalendarController::class, 'update'])->whereUuid('id');
            Route::delete('/appointments/{id}', [\App\Http\Controllers\Api\CompanyPanel\CalendarController::class, 'destroy'])->whereUuid('id');
        });

        // Phase 5: Payments
        Route::prefix('payments')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\CompanyPanel\PaymentController::class, 'index']);
            Route::get('/packages', [\App\Http\Controllers\Api\CompanyPanel\PaymentController::class, 'packages']);
            Route::get('/companies', [\App\Http\Controllers\Api\CompanyPanel\PaymentController::class, 'companies']);
            Route::post('/send-link', [\App\Http\Controllers\Api\CompanyPanel\PaymentController::class, 'sendLink']);
        });

        // Phase 5: Billing (accounting view)
        Route::prefix('billing')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\CompanyPanel\BillingController::class, 'index']);
            Route::get('/stats', [\App\Http\Controllers\Api\CompanyPanel\BillingController::class, 'stats']);
            Route::get('/companies/{id}', [\App\Http\Controllers\Api\CompanyPanel\BillingController::class, 'show'])->whereUuid('id');
            Route::put('/companies/{id}/billing-info', [\App\Http\Controllers\Api\CompanyPanel\BillingController::class, 'updateBillingInfo'])->whereUuid('id');
        });
    });
});

// ===========================================
// PUBLIC WEBHOOKS (no auth required)
// ===========================================
Route::prefix('v1')->group(function () {
    Route::post('/webhooks/inbound-email', CrmInboundWebhookController::class)
        ->middleware('throttle:60,1');
});
