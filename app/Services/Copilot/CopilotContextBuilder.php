<?php

namespace App\Services\Copilot;

use App\Models\Candidate;
use App\Models\Interview;
use App\Models\Job;
use Illuminate\Support\Collection;

/**
 * KVKK-Safe Context Builder
 *
 * IMPORTANT: This class builds context for the LLM.
 * NO PII (names, emails, phones, addresses) should be included.
 * Use anonymized labels like "Candidate ABC123" instead of real names.
 */
class CopilotContextBuilder
{
    /**
     * Build KVKK-safe context data for a given entity type and ID.
     * Returns context with kvkk_safe=true to confirm no PII is included.
     */
    public function buildContext(string $type, string $id, ?string $companyId): array
    {
        $context = match ($type) {
            'candidate' => $this->buildCandidateContext($id, $companyId),
            'interview' => $this->buildInterviewContext($id, $companyId),
            'job' => $this->buildJobContext($id, $companyId),
            'comparison' => $this->buildComparisonContext($id, $companyId),
            default => $this->buildEmptyContext($type),
        };

        // Mark context as KVKK-safe (no PII) - this is sent to LLM
        $context['kvkk_safe'] = true;

        return $context;
    }

    /**
     * Get a preview summary of context for an entity (UI display - can include name).
     * Returns preview with ui_safe=true to confirm this is for display only, NOT for LLM.
     */
    public function getContextPreview(string $type, string $id, ?string $companyId): array
    {
        $preview = match ($type) {
            'candidate' => $this->getCandidatePreview($id, $companyId),
            'interview' => $this->getInterviewPreview($id, $companyId),
            'job' => $this->getJobPreview($id, $companyId),
            default => [
                'type' => $type,
                'id' => $id,
                'summary' => 'Unknown context type',
                'available_data' => [],
            ],
        };

        // Mark as UI-safe (may contain PII for display) - NOT for LLM
        $preview['ui_safe'] = true;

        return $preview;
    }

    /**
     * Generate anonymized label for candidate (KVKK-safe).
     */
    private function getAnonymizedLabel(string $id): string
    {
        return 'Candidate ' . strtoupper(substr($id, 0, 6));
    }

    /**
     * Check if transcripts should be included.
     */
    private function shouldIncludeTranscripts(): bool
    {
        return config('copilot.include_transcripts', false);
    }

    /**
     * Build KVKK-safe context for a candidate.
     * NO PII: No name, email, phone, address.
     */
    private function buildCandidateContext(string $id, ?string $companyId): array
    {
        $query = Candidate::with([
            'job',
            'latestInterview.analysis',
            'latestInterview.responses.question',
        ]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $candidate = $query->find($id);

        if (!$candidate) {
            return $this->buildEmptyContext('candidate');
        }

        $context = [
            'type' => 'candidate',
            'entity_id' => $candidate->id,
            'display_label' => $this->getAnonymizedLabel($candidate->id),
            'candidate' => [
                'id' => $candidate->id,
                'display_label' => $this->getAnonymizedLabel($candidate->id),
                'status' => $candidate->status,
                'applied_at' => $candidate->created_at?->toIso8601String(),
                'source' => $candidate->source,
                'tags' => $candidate->tags ?? [],
                // NO: name, email, phone, address
            ],
            'position' => $this->buildPositionContext($candidate->job),
            'assessment' => null,
            'risk_factors' => [],
            'available_data' => [
                'has_cv' => !empty($candidate->cv_url),
                'has_cv_analysis' => !empty($candidate->cv_parsed_data),
                'has_interview' => false,
                'has_assessment' => false,
            ],
        ];

        // Add CV analysis if available (no PII from CV)
        if (!empty($candidate->cv_parsed_data)) {
            $context['cv_analysis'] = [
                'match_score' => $candidate->cv_match_score,
                // Only include non-PII data from CV
                'skills' => $candidate->cv_parsed_data['skills'] ?? [],
                'experience_years' => $candidate->cv_parsed_data['experience_years'] ?? null,
                'education_level' => $candidate->cv_parsed_data['education_level'] ?? null,
                // NO: name, email, phone, address from CV
            ];
        }

        // Add interview data if available
        if ($candidate->latestInterview) {
            $interview = $candidate->latestInterview;
            $context['available_data']['has_interview'] = true;

            $context['interview'] = [
                'id' => $interview->id,
                'status' => $interview->status,
                'started_at' => $interview->started_at?->toIso8601String(),
                'completed_at' => $interview->completed_at?->toIso8601String(),
                'duration_minutes' => $interview->getDurationInMinutes(),
            ];

            // Add responses ONLY if transcripts are enabled
            if ($this->shouldIncludeTranscripts()) {
                $context['interview_responses'] = $this->buildInterviewResponses($interview);
            }

            // Add assessment/analysis if available (this is safe - derived data)
            if ($interview->analysis) {
                $context['available_data']['has_assessment'] = true;
                $context['assessment'] = $this->buildAssessmentContext($interview->analysis);
                $context['risk_factors'] = $this->buildRiskFactors($interview->analysis);
            }
        }

        return $context;
    }

    /**
     * Build KVKK-safe context for an interview.
     */
    private function buildInterviewContext(string $id, ?string $companyId): array
    {
        $query = Interview::with([
            'candidate',
            'job',
            'analysis',
            'responses.question',
        ]);

        if ($companyId) {
            $query->whereHas('candidate', fn($q) => $q->where('company_id', $companyId));
        }

        $interview = $query->find($id);

        if (!$interview) {
            return $this->buildEmptyContext('interview');
        }

        $context = [
            'type' => 'interview',
            'entity_id' => $interview->id,
            'display_label' => $this->getAnonymizedLabel($interview->candidate->id),
            'candidate' => [
                'id' => $interview->candidate->id,
                'display_label' => $this->getAnonymizedLabel($interview->candidate->id),
                'status' => $interview->candidate->status,
                // NO: name, email, phone
            ],
            'position' => $this->buildPositionContext($interview->job),
            'interview' => [
                'status' => $interview->status,
                'started_at' => $interview->started_at?->toIso8601String(),
                'completed_at' => $interview->completed_at?->toIso8601String(),
                'duration_minutes' => $interview->getDurationInMinutes(),
            ],
            'assessment' => $interview->analysis ? $this->buildAssessmentContext($interview->analysis) : null,
            'risk_factors' => $interview->analysis ? $this->buildRiskFactors($interview->analysis) : [],
            'available_data' => [
                'has_interview' => true,
                'has_assessment' => $interview->analysis !== null,
                'has_responses' => $interview->responses->isNotEmpty(),
            ],
        ];

        // Add responses ONLY if transcripts are enabled
        if ($this->shouldIncludeTranscripts()) {
            $context['interview_responses'] = $this->buildInterviewResponses($interview);
        }

        return $context;
    }

    /**
     * Build context for a job position (no PII here).
     */
    private function buildJobContext(string $id, ?string $companyId): array
    {
        $query = Job::with(['candidates.latestInterview.analysis']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $job = $query->find($id);

        if (!$job) {
            return $this->buildEmptyContext('job');
        }

        $candidatesWithAnalysis = $job->candidates->filter(
            fn($c) => $c->latestInterview?->analysis !== null
        );

        return [
            'type' => 'job',
            'entity_id' => $job->id,
            'position' => $this->buildPositionContext($job),
            'candidates_summary' => [
                'total' => $job->candidates->count(),
                'with_assessment' => $candidatesWithAnalysis->count(),
                'by_status' => $job->candidates->groupBy('status')->map->count(),
            ],
            'top_candidates' => $this->buildTopCandidatesSummary($candidatesWithAnalysis),
            'available_data' => [
                'has_candidates' => $job->candidates->isNotEmpty(),
                'has_assessments' => $candidatesWithAnalysis->isNotEmpty(),
            ],
        ];
    }

    /**
     * Build KVKK-safe comparison context for multiple candidates.
     */
    private function buildComparisonContext(string $ids, ?string $companyId): array
    {
        $candidateIds = explode(',', $ids);

        $query = Candidate::with([
            'job',
            'latestInterview.analysis',
        ]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $candidates = $query->whereIn('id', $candidateIds)->get();

        if ($candidates->isEmpty()) {
            return $this->buildEmptyContext('comparison');
        }

        $comparisonData = [];
        foreach ($candidates as $candidate) {
            $analysis = $candidate->latestInterview?->analysis;

            $comparisonData[] = [
                'id' => $candidate->id,
                'display_label' => $this->getAnonymizedLabel($candidate->id),
                'status' => $candidate->status,
                'overall_score' => $analysis?->overall_score,
                'competency_scores' => $analysis?->competency_scores ?? [],
                'risk_flags_count' => $analysis?->getRedFlagsCount() ?? 0,
                'cheating_risk_score' => $analysis?->cheating_risk_score,
                'recommendation' => $analysis?->getRecommendation(),
                'confidence' => $analysis?->getConfidencePercent(),
                // NO: name, email, phone
            ];
        }

        $job = $candidates->first()?->job;

        return [
            'type' => 'comparison',
            'candidate_ids' => $candidateIds,
            'position' => $job ? $this->buildPositionContext($job) : null,
            'candidates' => $comparisonData,
            'available_data' => [
                'candidate_count' => $candidates->count(),
                'with_assessment' => $candidates->filter(fn($c) => $c->latestInterview?->analysis !== null)->count(),
            ],
        ];
    }

    /**
     * Build position/job context (no PII).
     */
    private function buildPositionContext(?Job $job): ?array
    {
        if (!$job) {
            return null;
        }

        return [
            'id' => $job->id,
            'title' => $job->title,
            'description' => $job->description,
            'location' => $job->location,
            'employment_type' => $job->employment_type,
            'experience_years' => $job->experience_years,
            'competencies' => $job->getEffectiveCompetencies(),
            'red_flags' => $job->getEffectiveRedFlags(),
            'scoring_rubric' => $job->getEffectiveScoringRubric(),
        ];
    }

    /**
     * Build interview responses context (only if transcripts enabled).
     * Excludes transcript content by default.
     */
    private function buildInterviewResponses(Interview $interview): array
    {
        $responses = [];

        foreach ($interview->responses as $response) {
            $responseData = [
                'order' => $response->response_order,
                'question' => [
                    'id' => $response->question?->id,
                    'text' => $response->question?->question_text,
                    'type' => $response->question?->question_type,
                    'competency' => $response->question?->competency_code,
                ],
                'answer' => [
                    'duration_seconds' => $response->duration_seconds,
                    'confidence' => $response->transcript_confidence,
                ],
            ];

            // Only include transcript if explicitly enabled
            if ($this->shouldIncludeTranscripts() && $response->transcript) {
                $responseData['answer']['transcript'] = $response->transcript;
            }

            $responses[] = $responseData;
        }

        return $responses;
    }

    /**
     * Build assessment context from analysis (derived data, no PII).
     */
    private function buildAssessmentContext($analysis): array
    {
        return [
            'overall_score' => $analysis->overall_score,
            'competency_scores' => $analysis->competency_scores ?? [],
            'behavior_analysis' => $analysis->behavior_analysis ?? [],
            'culture_fit' => $analysis->culture_fit ?? [],
            'recommendation' => $analysis->getRecommendation(),
            'confidence_percent' => $analysis->getConfidencePercent(),
            'reasons' => $analysis->getReasons(),
            'suggested_questions' => $analysis->getSuggestedQuestions(),
            'analyzed_at' => $analysis->analyzed_at?->toIso8601String(),
            'cheating_risk_score' => $analysis->cheating_risk_score,
            'cheating_level' => $analysis->cheating_level,
        ];
    }

    /**
     * Build risk factors from analysis (derived data, no PII).
     */
    private function buildRiskFactors($analysis): array
    {
        $riskFactors = [];

        // Red flags from analysis
        if ($analysis->hasRedFlags()) {
            foreach ($analysis->red_flag_analysis['flags'] ?? [] as $flag) {
                $riskFactors[] = [
                    'type' => 'red_flag',
                    'category' => $flag['category'] ?? 'unknown',
                    'description' => $flag['description'] ?? '',
                    'severity' => $flag['severity'] ?? 'medium',
                    // NO: evidence that might contain PII
                ];
            }
        }

        // Cheating risk
        if ($analysis->hasCheatingRisk()) {
            $riskFactors[] = [
                'type' => 'cheating_risk',
                'category' => 'integrity',
                'description' => 'Potential integrity concerns detected',
                'severity' => $analysis->cheating_level,
                'score' => $analysis->cheating_risk_score,
            ];
        }

        // Low competency scores
        foreach ($analysis->competency_scores ?? [] as $code => $data) {
            $score = $data['score'] ?? 0;
            if ($score < 50) {
                $competencyName = $data['name'] ?? $code;
                $riskFactors[] = [
                    'type' => 'low_competency',
                    'category' => $code,
                    'description' => "Low score in {$competencyName} competency",
                    'severity' => $score < 30 ? 'high' : 'medium',
                    'score' => $score,
                ];
            }
        }

        return $riskFactors;
    }

    /**
     * Build KVKK-safe summary of top candidates.
     */
    private function buildTopCandidatesSummary(Collection $candidates): array
    {
        return $candidates
            ->sortByDesc(fn($c) => $c->latestInterview?->analysis?->overall_score ?? 0)
            ->take(10)
            ->map(fn($c) => [
                'id' => $c->id,
                'display_label' => $this->getAnonymizedLabel($c->id),
                'overall_score' => $c->latestInterview?->analysis?->overall_score,
                'risk_flags' => $c->latestInterview?->analysis?->getRedFlagsCount() ?? 0,
                'recommendation' => $c->latestInterview?->analysis?->getRecommendation(),
                // NO: name
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get preview for a candidate (UI display - CAN include name for display).
     */
    private function getCandidatePreview(string $id, ?string $companyId): array
    {
        $query = Candidate::with(['job', 'latestInterview.analysis']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $candidate = $query->find($id);

        if (!$candidate) {
            // Check if candidate exists but belongs to different company
            $exists = Candidate::where('id', $id)->exists();
            return [
                'type' => 'candidate',
                'id' => $id,
                'summary' => $exists ? 'Bu adaya erişim yetkiniz yok' : 'Aday bulunamadı',
                'error' => $exists ? 'access_denied' : 'not_found',
                'available_data' => [],
            ];
        }

        $analysis = $candidate->latestInterview?->analysis;

        // Preview is for UI display, so name is OK here
        // Include overall_score at root level for frontend compatibility
        return [
            'type' => 'candidate',
            'id' => $candidate->id,
            'summary' => "Candidate for {$candidate->job?->title} position",
            'overall_score' => $analysis?->overall_score,
            'risk_flags' => $analysis?->getRedFlagsCount() ?? 0,
            'recommendation' => $analysis?->getRecommendation(),
            'available_data' => [
                'has_assessment' => $analysis !== null,
                'has_interview' => $candidate->latestInterview !== null,
                'has_cv_analysis' => !empty($candidate->cv_parsed_data),
            ],
            'preview' => [
                'name' => $candidate->full_name, // OK for UI display
                'position' => $candidate->job?->title,
                'status' => $candidate->status,
                'overall_score' => $analysis?->overall_score,
                'risk_flags' => $analysis?->getRedFlagsCount() ?? 0,
                'recommendation' => $analysis?->getRecommendation(),
            ],
        ];
    }

    /**
     * Get preview for an interview (UI display).
     */
    private function getInterviewPreview(string $id, ?string $companyId): array
    {
        $query = Interview::with(['candidate', 'job', 'analysis']);

        if ($companyId) {
            $query->whereHas('candidate', fn($q) => $q->where('company_id', $companyId));
        }

        $interview = $query->find($id);

        if (!$interview) {
            $exists = Interview::where('id', $id)->exists();
            return [
                'type' => 'interview',
                'id' => $id,
                'summary' => $exists ? 'Bu mülakata erişim yetkiniz yok' : 'Mülakat bulunamadı',
                'error' => $exists ? 'access_denied' : 'not_found',
                'available_data' => [],
            ];
        }

        // Include overall_score at root level for frontend compatibility
        return [
            'type' => 'interview',
            'id' => $interview->id,
            'summary' => "Interview for {$interview->candidate->full_name}",
            'overall_score' => $interview->analysis?->overall_score,
            'available_data' => [
                'has_assessment' => $interview->analysis !== null,
                'has_responses' => $interview->responses()->exists(),
            ],
            'preview' => [
                'candidate_name' => $interview->candidate->full_name, // OK for UI
                'position' => $interview->job?->title,
                'status' => $interview->status,
                'overall_score' => $interview->analysis?->overall_score,
                'completed_at' => $interview->completed_at?->toIso8601String(),
            ],
        ];
    }

    /**
     * Get preview for a job (UI display).
     */
    private function getJobPreview(string $id, ?string $companyId): array
    {
        $query = Job::withCount('candidates');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $job = $query->find($id);

        if (!$job) {
            $exists = Job::where('id', $id)->exists();
            return [
                'type' => 'job',
                'id' => $id,
                'summary' => $exists ? 'Bu ilana erişim yetkiniz yok' : 'İlan bulunamadı',
                'error' => $exists ? 'access_denied' : 'not_found',
                'available_data' => [],
            ];
        }

        return [
            'type' => 'job',
            'id' => $job->id,
            'summary' => "Job posting: {$job->title}",
            'available_data' => [
                'has_candidates' => $job->candidates_count > 0,
            ],
            'preview' => [
                'title' => $job->title,
                'status' => $job->status,
                'candidates_count' => $job->candidates_count,
                'location' => $job->location,
            ],
        ];
    }

    /**
     * Build empty context for unknown types.
     */
    private function buildEmptyContext(string $type): array
    {
        return [
            'type' => $type,
            'entity_id' => null,
            'error' => 'Context not found or not accessible',
            'available_data' => [],
        ];
    }
}
