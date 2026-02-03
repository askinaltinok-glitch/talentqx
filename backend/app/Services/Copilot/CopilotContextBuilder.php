<?php

namespace App\Services\Copilot;

use App\Models\Candidate;
use App\Models\Interview;
use App\Models\Job;
use Illuminate\Support\Collection;

class CopilotContextBuilder
{
    /**
     * Build context data for a given entity type and ID.
     */
    public function buildContext(string $type, string $id, string $companyId): array
    {
        return match ($type) {
            'candidate' => $this->buildCandidateContext($id, $companyId),
            'interview' => $this->buildInterviewContext($id, $companyId),
            'job' => $this->buildJobContext($id, $companyId),
            'comparison' => $this->buildComparisonContext($id, $companyId),
            default => $this->buildEmptyContext($type),
        };
    }

    /**
     * Get a preview summary of context for an entity.
     */
    public function getContextPreview(string $type, string $id, string $companyId): array
    {
        return match ($type) {
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
    }

    /**
     * Build full context for a candidate.
     */
    private function buildCandidateContext(string $id, string $companyId): array
    {
        $candidate = Candidate::with([
            'job',
            'latestInterview.analysis',
            'latestInterview.responses.question',
        ])
            ->where('company_id', $companyId)
            ->find($id);

        if (!$candidate) {
            return $this->buildEmptyContext('candidate');
        }

        $context = [
            'type' => 'candidate',
            'id' => $candidate->id,
            'candidate' => [
                'id' => $candidate->id,
                'name' => $candidate->full_name,
                'status' => $candidate->status,
                'applied_at' => $candidate->created_at?->toIso8601String(),
                'source' => $candidate->source,
                'tags' => $candidate->tags ?? [],
            ],
            'position' => $this->buildPositionContext($candidate->job),
            'assessment' => null,
            'interview_responses' => [],
            'risk_factors' => [],
            'available_data' => [
                'has_cv' => !empty($candidate->cv_url),
                'has_cv_analysis' => !empty($candidate->cv_parsed_data),
                'has_interview' => false,
                'has_assessment' => false,
            ],
        ];

        // Add CV analysis if available
        if (!empty($candidate->cv_parsed_data)) {
            $context['cv_analysis'] = [
                'match_score' => $candidate->cv_match_score,
                'parsed_data' => $candidate->cv_parsed_data,
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

            // Add responses (questions and answers)
            $context['interview_responses'] = $this->buildInterviewResponses($interview);

            // Add assessment/analysis if available
            if ($interview->analysis) {
                $context['available_data']['has_assessment'] = true;
                $context['assessment'] = $this->buildAssessmentContext($interview->analysis);
                $context['risk_factors'] = $this->buildRiskFactors($interview->analysis);
            }
        }

        return $context;
    }

    /**
     * Build full context for an interview.
     */
    private function buildInterviewContext(string $id, string $companyId): array
    {
        $interview = Interview::with([
            'candidate',
            'job',
            'analysis',
            'responses.question',
        ])
            ->whereHas('candidate', fn($q) => $q->where('company_id', $companyId))
            ->find($id);

        if (!$interview) {
            return $this->buildEmptyContext('interview');
        }

        return [
            'type' => 'interview',
            'id' => $interview->id,
            'candidate' => [
                'id' => $interview->candidate->id,
                'name' => $interview->candidate->full_name,
                'status' => $interview->candidate->status,
            ],
            'position' => $this->buildPositionContext($interview->job),
            'interview' => [
                'status' => $interview->status,
                'started_at' => $interview->started_at?->toIso8601String(),
                'completed_at' => $interview->completed_at?->toIso8601String(),
                'duration_minutes' => $interview->getDurationInMinutes(),
            ],
            'interview_responses' => $this->buildInterviewResponses($interview),
            'assessment' => $interview->analysis ? $this->buildAssessmentContext($interview->analysis) : null,
            'risk_factors' => $interview->analysis ? $this->buildRiskFactors($interview->analysis) : [],
            'available_data' => [
                'has_interview' => true,
                'has_assessment' => $interview->analysis !== null,
                'has_responses' => $interview->responses->isNotEmpty(),
            ],
        ];
    }

    /**
     * Build context for a job position.
     */
    private function buildJobContext(string $id, string $companyId): array
    {
        $job = Job::with(['candidates.latestInterview.analysis'])
            ->where('company_id', $companyId)
            ->find($id);

        if (!$job) {
            return $this->buildEmptyContext('job');
        }

        $candidatesWithAnalysis = $job->candidates->filter(
            fn($c) => $c->latestInterview?->analysis !== null
        );

        return [
            'type' => 'job',
            'id' => $job->id,
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
     * Build comparison context for multiple candidates.
     * The ID should be comma-separated candidate IDs.
     */
    private function buildComparisonContext(string $ids, string $companyId): array
    {
        $candidateIds = explode(',', $ids);

        $candidates = Candidate::with([
            'job',
            'latestInterview.analysis',
        ])
            ->where('company_id', $companyId)
            ->whereIn('id', $candidateIds)
            ->get();

        if ($candidates->isEmpty()) {
            return $this->buildEmptyContext('comparison');
        }

        $comparisonData = [];
        foreach ($candidates as $candidate) {
            $analysis = $candidate->latestInterview?->analysis;

            $comparisonData[] = [
                'id' => $candidate->id,
                'name' => $candidate->full_name,
                'status' => $candidate->status,
                'overall_score' => $analysis?->overall_score,
                'competency_scores' => $analysis?->competency_scores ?? [],
                'risk_flags_count' => $analysis?->getRedFlagsCount() ?? 0,
                'cheating_risk_score' => $analysis?->cheating_risk_score,
                'recommendation' => $analysis?->getRecommendation(),
                'confidence' => $analysis?->getConfidencePercent(),
            ];
        }

        // Get the job from the first candidate for position context
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
     * Build position/job context.
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
     * Build interview responses context.
     */
    private function buildInterviewResponses(Interview $interview): array
    {
        $responses = [];

        foreach ($interview->responses as $response) {
            $responses[] = [
                'order' => $response->response_order,
                'question' => [
                    'id' => $response->question?->id,
                    'text' => $response->question?->question_text,
                    'type' => $response->question?->question_type,
                    'competency' => $response->question?->competency_code,
                ],
                'answer' => [
                    'transcript' => $response->transcript,
                    'duration_seconds' => $response->duration_seconds,
                    'confidence' => $response->transcript_confidence,
                ],
            ];
        }

        return $responses;
    }

    /**
     * Build assessment context from analysis.
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
        ];
    }

    /**
     * Build risk factors from analysis.
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
                    'evidence' => $flag['evidence'] ?? '',
                ];
            }
        }

        // Cheating risk
        if ($analysis->hasCheatingRisk()) {
            $riskFactors[] = [
                'type' => 'cheating_risk',
                'category' => 'integrity',
                'description' => 'Potential integrity concerns detected in responses',
                'severity' => $analysis->cheating_level,
                'score' => $analysis->cheating_risk_score,
                'flags' => $analysis->cheating_flags ?? [],
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
     * Build summary of top candidates.
     */
    private function buildTopCandidatesSummary(Collection $candidates): array
    {
        return $candidates
            ->sortByDesc(fn($c) => $c->latestInterview?->analysis?->overall_score ?? 0)
            ->take(10)
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->full_name,
                'overall_score' => $c->latestInterview?->analysis?->overall_score,
                'risk_flags' => $c->latestInterview?->analysis?->getRedFlagsCount() ?? 0,
                'recommendation' => $c->latestInterview?->analysis?->getRecommendation(),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get preview for a candidate.
     */
    private function getCandidatePreview(string $id, string $companyId): array
    {
        $candidate = Candidate::with(['job', 'latestInterview.analysis'])
            ->where('company_id', $companyId)
            ->find($id);

        if (!$candidate) {
            return [
                'type' => 'candidate',
                'id' => $id,
                'summary' => 'Candidate not found',
                'available_data' => [],
            ];
        }

        $analysis = $candidate->latestInterview?->analysis;

        return [
            'type' => 'candidate',
            'id' => $candidate->id,
            'summary' => "Candidate for {$candidate->job?->title} position",
            'available_data' => [
                'has_assessment' => $analysis !== null,
                'has_interview' => $candidate->latestInterview !== null,
                'has_cv_analysis' => !empty($candidate->cv_parsed_data),
            ],
            'preview' => [
                'name' => $candidate->full_name,
                'position' => $candidate->job?->title,
                'status' => $candidate->status,
                'overall_score' => $analysis?->overall_score,
                'risk_flags' => $analysis?->getRedFlagsCount() ?? 0,
                'recommendation' => $analysis?->getRecommendation(),
            ],
        ];
    }

    /**
     * Get preview for an interview.
     */
    private function getInterviewPreview(string $id, string $companyId): array
    {
        $interview = Interview::with(['candidate', 'job', 'analysis'])
            ->whereHas('candidate', fn($q) => $q->where('company_id', $companyId))
            ->find($id);

        if (!$interview) {
            return [
                'type' => 'interview',
                'id' => $id,
                'summary' => 'Interview not found',
                'available_data' => [],
            ];
        }

        return [
            'type' => 'interview',
            'id' => $interview->id,
            'summary' => "Interview for {$interview->candidate->full_name}",
            'available_data' => [
                'has_assessment' => $interview->analysis !== null,
                'has_responses' => $interview->responses()->exists(),
            ],
            'preview' => [
                'candidate_name' => $interview->candidate->full_name,
                'position' => $interview->job?->title,
                'status' => $interview->status,
                'overall_score' => $interview->analysis?->overall_score,
                'completed_at' => $interview->completed_at?->toIso8601String(),
            ],
        ];
    }

    /**
     * Get preview for a job.
     */
    private function getJobPreview(string $id, string $companyId): array
    {
        $job = Job::withCount('candidates')
            ->where('company_id', $companyId)
            ->find($id);

        if (!$job) {
            return [
                'type' => 'job',
                'id' => $id,
                'summary' => 'Job not found',
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
            'id' => null,
            'error' => 'Context not found or not accessible',
            'available_data' => [],
        ];
    }
}
