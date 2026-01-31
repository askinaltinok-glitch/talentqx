import type {
  InterviewSession,
  InterviewSessionQuestion,
  InterviewSessionAnswer,
  InterviewSessionCreatePayload,
  InterviewSessionAnalysis,
} from '../types';

const API_BASE = import.meta.env.VITE_API_URL || 'https://talentqx.com/api/v1';

/**
 * Interview Session API Service
 * Public endpoints - no auth required
 */
export const interviewService = {
  /**
   * Create a new interview session with consent
   */
  async createSession(payload: InterviewSessionCreatePayload): Promise<{
    session_id: string;
    status: string;
    consent_id: string;
    started_at: string;
  }> {
    const response = await fetch(`${API_BASE}/interviews/sessions`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    const data = await response.json();
    if (!data.success) {
      throw new Error(data.error?.message || 'Failed to create session');
    }
    return data.data;
  },

  /**
   * Get session info
   */
  async getSession(sessionId: string): Promise<InterviewSession> {
    const response = await fetch(`${API_BASE}/interviews/sessions/${sessionId}`, {
      headers: {
        'Accept': 'application/json',
      },
    });

    const data = await response.json();
    if (!data.success) {
      throw new Error(data.error?.message || 'Failed to get session');
    }
    return data.data;
  },

  /**
   * Get questions for the session
   */
  async getQuestions(sessionId: string): Promise<{
    session_id: string;
    role_key: string;
    locale: string;
    questions: InterviewSessionQuestion[];
    total_questions: number;
  }> {
    const response = await fetch(`${API_BASE}/interviews/sessions/${sessionId}/questions`, {
      headers: {
        'Accept': 'application/json',
      },
    });

    const data = await response.json();
    if (!data.success) {
      throw new Error(data.error?.message || 'Failed to get questions');
    }
    return data.data;
  },

  /**
   * Submit an answer
   */
  async submitAnswer(
    sessionId: string,
    payload: {
      question_id: number;
      answer_type: 'text' | 'voice';
      raw_text?: string;
      audio_path?: string;
      duration_ms?: number;
      response_time_ms?: number;
    }
  ): Promise<{ answer_id: number; question_id: number; answer_type: string }> {
    const response = await fetch(`${API_BASE}/interviews/sessions/${sessionId}/answers`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    const data = await response.json();
    if (!data.success) {
      throw new Error(data.error?.message || 'Failed to submit answer');
    }
    return data.data;
  },

  /**
   * Get all answers for a session
   */
  async getAnswers(sessionId: string): Promise<{
    session_id: string;
    status: string;
    answers: InterviewSessionAnswer[];
    total_answers: number;
  }> {
    const response = await fetch(`${API_BASE}/interviews/sessions/${sessionId}/answers`, {
      headers: {
        'Accept': 'application/json',
      },
    });

    const data = await response.json();
    if (!data.success) {
      throw new Error(data.error?.message || 'Failed to get answers');
    }
    return data.data;
  },

  /**
   * Complete the interview session
   */
  async complete(sessionId: string): Promise<{
    session_id: string;
    status: string;
    finished_at: string;
    total_answers: number;
    message: string;
  }> {
    const response = await fetch(`${API_BASE}/interviews/sessions/${sessionId}/complete`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
      },
    });

    const data = await response.json();
    if (!data.success) {
      throw new Error(data.error?.message || 'Failed to complete session');
    }
    return data.data;
  },

  /**
   * Abandon the interview session
   */
  async abandon(sessionId: string): Promise<{
    session_id: string;
    status: string;
    finished_at: string;
  }> {
    const response = await fetch(`${API_BASE}/interviews/sessions/${sessionId}/abandon`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
      },
    });

    const data = await response.json();
    if (!data.success) {
      throw new Error(data.error?.message || 'Failed to abandon session');
    }
    return data.data;
  },

  /**
   * Start AI analysis (async)
   */
  async analyze(sessionId: string, force = false): Promise<{
    session_id: string;
    status: string;
    message: string;
    analysis_id?: string;
  }> {
    const response = await fetch(`${API_BASE}/interviews/sessions/${sessionId}/analyze`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ force }),
    });

    const data = await response.json();
    if (!data.success) {
      throw new Error(data.error?.message || 'Failed to start analysis');
    }
    return data.data;
  },

  /**
   * Get analysis results
   */
  async getAnalysis(sessionId: string): Promise<InterviewSessionAnalysis> {
    const response = await fetch(`${API_BASE}/interviews/sessions/${sessionId}/analysis`, {
      headers: {
        'Accept': 'application/json',
      },
    });

    const data = await response.json();
    if (!data.success) {
      throw new Error(data.error?.message || 'Analysis not found');
    }
    return data.data;
  },

  /**
   * Get privacy metadata
   */
  async getPrivacyMeta(): Promise<{
    regime: 'KVKK' | 'GDPR' | 'GLOBAL';
    locale: string;
    country: string;
    policy_version: string;
    regime_info: {
      name: string;
      full_name: string;
      authority: string;
      authority_url: string | null;
    };
  }> {
    const response = await fetch(`${API_BASE}/privacy/meta`, {
      headers: {
        'Accept': 'application/json',
      },
    });

    const data = await response.json();
    if (!data.success) {
      throw new Error('Failed to get privacy meta');
    }
    return data.data;
  },
};

export default interviewService;
