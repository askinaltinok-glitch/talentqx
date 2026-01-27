// User & Auth Types
export interface User {
  id: string;
  email: string;
  first_name: string;
  last_name: string;
  full_name: string;
  phone?: string;
  avatar_url?: string;
  role: string;
  permissions: string[];
  company: Company | null;
}

export interface Company {
  id: string;
  name: string;
  logo_url?: string;
}

export interface AuthResponse {
  user: User;
  token: string;
  expires_at: string;
}

// Position Template Types
export interface Competency {
  code: string;
  name: string;
  weight: number;
  description: string;
}

export interface RedFlag {
  code: string;
  description: string;
  severity: 'low' | 'medium' | 'high' | 'critical';
  keywords: string[];
}

export interface QuestionRules {
  technical_count: number;
  behavioral_count: number;
  scenario_count: number;
  culture_count: number;
  total_count: number;
  sample_questions?: SampleQuestion[];
}

export interface SampleQuestion {
  type: string;
  text: string;
  competency_code: string;
}

export interface PositionTemplate {
  id: string;
  name: string;
  slug: string;
  category: string;
  description: string;
  competencies: Competency[];
  red_flags: RedFlag[];
  question_rules: QuestionRules;
  scoring_rubric: Record<string, string>;
  critical_behaviors: string[];
}

// Job Types
export interface Job {
  id: string;
  title: string;
  slug: string;
  description?: string;
  location?: string;
  employment_type: string;
  experience_years: number;
  status: 'draft' | 'active' | 'paused' | 'closed';
  template?: {
    id: string;
    name: string;
    slug?: string;
  };
  competencies: Competency[];
  red_flags: RedFlag[];
  question_rules: QuestionRules;
  scoring_rubric: Record<string, string>;
  interview_settings: InterviewSettings;
  questions?: JobQuestion[];
  candidates_count?: number;
  interviews_completed?: number;
  published_at?: string;
  closes_at?: string;
  created_at: string;
}

export interface InterviewSettings {
  max_duration_minutes: number;
  questions_count: number;
  allow_video: boolean;
  allow_audio_only: boolean;
  time_per_question_seconds: number;
}

export interface JobQuestion {
  id: string;
  order: number;
  type: 'technical' | 'behavioral' | 'scenario' | 'culture';
  text: string;
  competency_code?: string;
  ideal_answer_points?: string[];
  time_limit_seconds: number;
  is_required: boolean;
}

// Candidate Types
export interface Candidate {
  id: string;
  first_name: string;
  last_name: string;
  email: string;
  phone?: string;
  status: CandidateStatus;
  cv_url?: string;
  cv_match_score?: number;
  cv_parsed_data?: Record<string, unknown>;
  source?: string;
  consent_given: boolean;
  consent_version?: string;
  job: {
    id: string;
    title: string;
  };
  interview?: InterviewSummary | null;
  analysis?: AnalysisSummary | null;
  internal_notes?: string;
  tags?: string[];
  created_at: string;
}

export type CandidateStatus =
  | 'applied'
  | 'interview_pending'
  | 'interview_completed'
  | 'under_review'
  | 'shortlisted'
  | 'hired'
  | 'rejected';

// Interview Types
export interface Interview {
  id: string;
  status: 'pending' | 'in_progress' | 'completed' | 'expired' | 'cancelled';
  candidate: {
    id: string;
    first_name: string;
    last_name: string;
  };
  job: {
    id: string;
    title: string;
  };
  video_url?: string;
  duration_seconds?: number;
  responses: InterviewResponse[];
  analysis?: InterviewAnalysis | null;
  started_at?: string;
  completed_at?: string;
}

export interface InterviewSummary {
  id: string;
  status: string;
  completed_at?: string;
}

export interface InterviewResponse {
  id: string;
  question: {
    id: string;
    order: number;
    text: string;
    competency_code?: string;
  };
  video_segment_url?: string;
  transcript?: string;
  duration_seconds?: number;
}

// Analysis Types
export interface InterviewAnalysis {
  id?: string;
  overall_score: number;
  competency_scores: Record<string, CompetencyScore>;
  behavior_analysis?: BehaviorAnalysis;
  red_flag_analysis: RedFlagAnalysis;
  culture_fit?: CultureFit;
  decision_snapshot: DecisionSnapshot;
  question_analyses?: QuestionAnalysis[];
  // Anti-cheat fields
  cheating_risk_score?: number;
  cheating_level?: 'low' | 'medium' | 'high';
  cheating_flags?: CheatingFlag[];
}

export interface AnalysisSummary {
  overall_score: number;
  recommendation: 'hire' | 'hold' | 'reject';
  confidence_percent: number;
  has_red_flags: boolean;
}

export interface CompetencyScore {
  score: number;
  raw_score?: number;
  max_score?: number;
  evidence: string[];
  improvement_areas?: string[];
}

export interface BehaviorAnalysis {
  clarity_score: number;
  consistency_score: number;
  stress_tolerance: number;
  communication_style: string;
  confidence_level: string;
}

export interface RedFlagAnalysis {
  flags_detected: boolean;
  flags: DetectedRedFlag[];
  overall_risk: 'low' | 'medium' | 'high';
}

export interface DetectedRedFlag {
  code: string;
  detected_phrase: string;
  severity: 'low' | 'medium' | 'high';
  question_order: number;
}

export interface CultureFit {
  discipline_fit: number;
  hygiene_quality_fit: number;
  schedule_tempo_fit: number;
  overall_fit: number;
  notes?: string;
}

export interface DecisionSnapshot {
  recommendation: 'hire' | 'hold' | 'reject';
  confidence_percent: number;
  reasons: string[];
  suggested_questions?: string[];
}

export interface QuestionAnalysis {
  question_order: number;
  score: number;
  competency_code: string;
  analysis: string;
  positive_points: string[];
  negative_points: string[];
}

// Dashboard Types
export interface DashboardStats {
  total_jobs: number;
  active_jobs: number;
  total_candidates: number;
  interviews_completed: number;
  interviews_pending: number;
  average_score: number;
  hire_rate: number;
  red_flag_rate: number;
  by_status: Record<CandidateStatus, number>;
}

export interface CandidateComparison {
  id: string;
  name: string;
  overall_score: number;
  recommendation: string;
  confidence_percent: number;
  competency_scores: Record<string, number>;
  red_flags_count: number;
  culture_fit: number;
}

// API Response Types
export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
  error?: ApiError;
}

export interface ApiError {
  code: string;
  message: string;
  details?: Record<string, string[]>;
}

export interface PaginatedResponse<T> {
  success: boolean;
  data: T[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

// ===========================================
// WORKFORCE ASSESSMENT TYPES
// ===========================================

export interface Employee {
  id: string;
  employee_code?: string;
  first_name: string;
  last_name: string;
  full_name: string;
  email?: string;
  phone?: string;
  department?: string;
  current_role: string;
  branch?: string;
  hire_date?: string;
  manager_name?: string;
  status: 'active' | 'inactive' | 'terminated';
  latest_assessment?: AssessmentSession;
  assessment_sessions?: AssessmentSession[];
  created_at: string;
}

export interface AssessmentTemplate {
  id: string;
  name: string;
  slug: string;
  role_category: string;
  description?: string;
  competencies: AssessmentCompetency[];
  red_flags: AssessmentRedFlag[];
  questions: AssessmentQuestion[];
  scoring_config: {
    levels: ScoringLevel[];
    pass_threshold: number;
  };
  time_limit_minutes: number;
  is_active: boolean;
}

export interface AssessmentCompetency {
  code: string;
  name: string;
  weight: number;
  description: string;
}

export interface AssessmentRedFlag {
  code: string;
  label: string;
  severity: 'low' | 'medium' | 'high' | 'critical';
  indicators: string[];
}

export interface AssessmentQuestion {
  order: number;
  type: 'scenario' | 'behavioral' | 'knowledge';
  competency_code: string;
  text: string;
  options?: QuestionOption[];
  correct_answer?: string;
  time_limit_seconds?: number;
}

export interface QuestionOption {
  value: string;
  text: string;
  score: number;
}

export interface ScoringLevel {
  min: number;
  max: number;
  label: string;
  numeric: number;
}

export interface AssessmentSession {
  id: string;
  employee_id: string;
  template_id: string;
  access_token: string;
  token_expires_at: string;
  status: 'pending' | 'in_progress' | 'completed' | 'expired';
  started_at?: string;
  completed_at?: string;
  time_spent_seconds?: number;
  responses?: AssessmentResponse[];
  employee?: Employee;
  template?: AssessmentTemplate;
  result?: AssessmentResult;
  initiator?: User;
  created_at: string;
}

export interface AssessmentResponse {
  question_order: number;
  answer: string;
  time_spent: number;
  answered_at: string;
}

export interface AssessmentResult {
  id: string;
  session_id: string;
  status: 'completed' | 'analysis_failed' | 'pending_retry';
  ai_model: string;
  input_tokens?: number;
  output_tokens?: number;
  cost_usd?: number;
  cost_limited?: boolean;
  used_prompt_version_id?: string;
  analyzed_at: string;
  overall_score: number;
  competency_scores: Record<string, AssessmentCompetencyScore>;
  risk_flags: AssessmentRiskFlag[];
  risk_level: 'low' | 'medium' | 'high' | 'critical';
  level_label: string;
  level_numeric: number;
  development_plan: DevelopmentItem[];
  strengths: StrengthItem[];
  improvement_areas: ImprovementItem[];
  promotion_suitable: boolean;
  promotion_readiness: 'not_ready' | 'developing' | 'ready' | 'highly_ready';
  promotion_notes?: string;
  // Anti-cheat fields
  cheating_risk_score?: number;
  cheating_level?: 'low' | 'medium' | 'high';
  cheating_flags?: AssessmentCheatingFlag[];
  validation_errors?: string[];
  retry_count?: number;
  question_analyses?: QuestionAnalysisItem[];
  session?: AssessmentSession;
  prompt_version?: PromptVersion;
}

export interface AssessmentCheatingFlag {
  type: string;
  question_order?: number;
  severity: 'low' | 'medium' | 'high' | 'critical';
  description: string;
  [key: string]: unknown;
}

export interface PromptVersion {
  id: string;
  name: string;
  role_type?: string;
  version: number;
  is_active: boolean;
  created_at: string;
}

export interface AssessmentCompetencyScore {
  score: number;
  weight: number;
  weighted_score: number;
  feedback: string;
  evidence: string[];
}

export interface AssessmentRiskFlag {
  code: string;
  label: string;
  severity: 'low' | 'medium' | 'high' | 'critical';
  detected_in_question?: number;
  evidence: string;
}

export interface DevelopmentItem {
  area: string;
  actions: string[];
  priority: 'high' | 'medium' | 'low';
  timeline: string;
}

export interface StrengthItem {
  competency: string;
  description: string;
}

export interface ImprovementItem {
  competency: string;
  description: string;
  priority: 'high' | 'medium' | 'low';
}

export interface QuestionAnalysisItem {
  question_order: number;
  competency_code: string;
  score: number;
  max_score: number;
  analysis: string;
  positive_points: string[];
  negative_points: string[];
}

export interface AssessmentDashboardStats {
  total_sessions: number;
  completed_sessions: number;
  pending_sessions: number;
  completion_rate: number;
  average_score: number;
  risk_distribution: Record<string, number>;
  level_distribution: Record<string, number>;
  cheating_distribution?: Record<string, number>;
  high_cheating_risk_count?: number;
  analysis_failed_count?: number;
}

export interface AssessmentCostStats {
  total_cost_usd: number;
  monthly_cost_usd: number;
  average_cost_per_session: number;
  cost_limited_sessions: number;
  cost_by_model: {
    ai_model: string;
    count: number;
    total_cost: number;
    avg_cost: number;
  }[];
  token_usage: {
    total_input_tokens: number;
    total_output_tokens: number;
    total_tokens: number;
  };
}

export interface AssessmentResponseSimilarity {
  id: string;
  session_a_id: string;
  session_b_id: string;
  question_order: number;
  similarity_score: number;
  similarity_type: string;
  flagged: boolean;
  session_a?: AssessmentSession;
  session_b?: AssessmentSession;
}

export interface EmployeeStats {
  total_employees: number;
  active_employees: number;
  assessed_count: number;
  assessment_rate: number;
  high_risk_count: number;
  by_role: Record<string, number>;
  by_department: Record<string, number>;
}

// ===========================================
// KVKK & ANTI-CHEAT TYPES
// ===========================================

export interface AuditLog {
  id: string;
  action: string;
  entity_type: string;
  entity_id?: string;
  user_id?: string;
  old_values?: Record<string, unknown>;
  new_values?: Record<string, unknown>;
  metadata?: Record<string, unknown>;
  ip_address?: string;
  user_agent?: string;
  erased_by_request: boolean;
  erasure_reason?: string;
  created_at: string;
}

export interface DataErasureRequest {
  id: string;
  candidate_id: string;
  requested_by?: string;
  request_type: 'kvkk_request' | 'candidate_request' | 'company_policy' | 'retention_expired';
  status: 'pending' | 'processing' | 'completed' | 'failed';
  erased_data_types?: string[];
  processed_at?: string;
  notes?: string;
  candidate?: Candidate;
  created_at: string;
}

export interface RetentionStats {
  total_erased: number;
  erasure_requests_pending: number;
  by_retention_period: {
    retention_days: number;
    job_count: number;
    candidates_approaching_expiry: number;
  }[];
}

export interface CheatingAnalysis {
  cheating_risk_score: number;
  cheating_level: 'low' | 'medium' | 'high';
  cheating_flags: CheatingFlag[];
  timing_analysis?: {
    response_times: {
      question_order: number;
      duration: number;
      word_count: number;
      wpm: number;
    }[];
    anomalies: string[];
    risk_contribution: number;
  };
  similarity_analysis?: {
    similar_responses: {
      question_order: number;
      other_interview_id: string;
      similarity: number;
    }[];
    max_similarity: number;
    risk_contribution: number;
  };
  consistency_analysis?: {
    inconsistencies: {
      questions: number[];
      description: string;
    }[];
    risk_contribution: number;
  };
}

export interface CheatingFlag {
  type: string;
  question_order?: number;
  severity: 'low' | 'medium' | 'high' | 'critical';
  description: string;
  [key: string]: unknown;
}

export interface SimilarResponse {
  id: string;
  question_order: number;
  similarity_percent: number;
  candidate_a: {
    id: string;
    name: string;
  };
  candidate_b: {
    id: string;
    name: string;
  };
  flagged: boolean;
}
