<?php

namespace App\Services\Maritime;

use App\Models\CandidateCommandProfile;
use App\Models\FormInterview;
use App\Services\Interview\FormInterviewService;
use Illuminate\Support\Facades\Log;

/**
 * Manages Phase-1 Maritime Identity Capture interviews.
 *
 * Replaces InterviewTemplateService::getMaritimeTemplate() entry point
 * for the v2 command engine pipeline.
 */
class MaritimeIdentityInterviewService
{
    public function __construct(
        private FormInterviewService $formService,
        private ProfileExtractor $extractor,
        private CommandClassDetector $detector,
    ) {}

    /**
     * Start a Phase-1 identity capture interview.
     */
    public function startPhase1(
        string $candidateId,
        string $language = 'en',
        array $meta = []
    ): FormInterview {
        $templatePath = resource_path("templates/maritime/identity_v2_{$language}.json");

        if (!file_exists($templatePath)) {
            $templatePath = resource_path('templates/maritime/identity_v2_en.json');
        }

        $templateJson = file_get_contents($templatePath);
        $template = json_decode($templateJson, true);

        $interview = FormInterview::create([
            'version' => 'v2',
            'language' => $language,
            'position_code' => '__phase1_identity__',
            'template_position_code' => '__phase1_identity__',
            'industry_code' => 'maritime',
            'status' => FormInterview::STATUS_IN_PROGRESS,
            'type' => 'maritime_identity_v2',
            'interview_phase' => 1,
            'template_json' => $templateJson,
            'template_json_sha256' => hash('sha256', $templateJson),
            'meta' => array_merge($meta, [
                'framework' => 'maritime_command',
                'phase' => 1,
                'candidate_id' => $candidateId,
            ]),
        ]);

        Log::info('Maritime identity interview started', [
            'interview_id' => $interview->id,
            'candidate_id' => $candidateId,
            'language' => $language,
        ]);

        return $interview;
    }

    /**
     * Submit Phase-1 answers (batch upsert).
     *
     * @param array $answers [{slot: int, answer_text: string}, ...]
     */
    public function submitPhase1Answers(FormInterview $interview, array $answers): void
    {
        if ($interview->type !== 'maritime_identity_v2' || $interview->interview_phase !== 1) {
            throw new \InvalidArgumentException('Not a Phase-1 identity interview');
        }

        if ($interview->status === FormInterview::STATUS_COMPLETED) {
            throw new \InvalidArgumentException('Interview already completed');
        }

        $this->formService->upsertAnswers($interview, $answers);
    }

    /**
     * Complete Phase-1: extract profile, detect command class.
     *
     * Returns [profile, detection_result] or throws if validation fails.
     */
    public function completePhase1(FormInterview $interview): array
    {
        if ($interview->type !== 'maritime_identity_v2') {
            throw new \InvalidArgumentException('Not a maritime identity interview');
        }

        // Reload answers
        $interview->load('answers');

        // Validate required fields
        $missing = $this->extractor->validateForCompletion($interview);
        if (!empty($missing)) {
            return [
                'success' => false,
                'error' => 'incomplete_identity',
                'missing_fields' => $missing,
            ];
        }

        // Extract structured profile
        $profile = $this->extractor->extract($interview);

        // Run command class detection
        $detection = $this->detector->detect($profile);

        // Update interview record
        $interview->update([
            'status' => FormInterview::STATUS_COMPLETED,
            'completed_at' => now(),
            'phase1_completed_at' => now(),
            'command_class_detected' => $detection['command_class'],
            'command_profile_id' => $profile->id,
        ]);

        Log::info('Maritime Phase-1 completed', [
            'interview_id' => $interview->id,
            'candidate_id' => $profile->candidate_id,
            'command_class' => $detection['command_class'],
            'confidence' => $detection['confidence'],
        ]);

        return [
            'success' => true,
            'profile' => $profile,
            'detection' => $detection,
        ];
    }

    /**
     * Get the Phase-1 template questions for a language.
     */
    public function getTemplateQuestions(string $language = 'en'): array
    {
        $templatePath = resource_path("templates/maritime/identity_v2_{$language}.json");

        if (!file_exists($templatePath)) {
            $templatePath = resource_path('templates/maritime/identity_v2_en.json');
        }

        $template = json_decode(file_get_contents($templatePath), true);

        $questions = [];
        foreach ($template['sections'] as $section) {
            foreach ($section['questions'] as $q) {
                $questions[] = [
                    'slot' => $q['slot'],
                    'code' => $q['code'],
                    'section' => $section['code'],
                    'section_title' => $section['title'],
                    'prompt' => $q['prompt'],
                    'required' => $q['required'],
                    'min_length' => $q['min_length'],
                ];
            }
        }

        return $questions;
    }
}
