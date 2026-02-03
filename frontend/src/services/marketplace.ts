import api from './api';
import type {
  MarketplaceCandidate,
  MarketplaceCandidateFullProfile,
  MarketplaceAccessRequest,
  MarketplaceAccessRequestDetail,
} from '../types';

export interface MarketplaceCandidatesParams {
  page?: number;
  per_page?: number;
  skills?: string[];
  min_score?: number;
  min_experience?: number;
  status?: string;
}

export interface MarketplaceCandidatesResponse {
  data: MarketplaceCandidate[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface RequestAccessResponse {
  request_id: string;
  status: string;
  token_expires_at: string;
}

/**
 * Marketplace API service for anonymous candidate profiles
 */
export const marketplaceService = {
  /**
   * List marketplace candidates (anonymous profiles)
   * Requires premium subscription
   */
  async listCandidates(params: MarketplaceCandidatesParams = {}): Promise<MarketplaceCandidatesResponse> {
    const queryParams: Record<string, unknown> = {
      page: params.page || 1,
      per_page: params.per_page || 20,
    };

    if (params.skills?.length) {
      queryParams.skills = params.skills.join(',');
    }
    if (params.min_score !== undefined) {
      queryParams.min_score = params.min_score;
    }
    if (params.min_experience !== undefined) {
      queryParams.min_experience = params.min_experience;
    }
    if (params.status) {
      queryParams.status = params.status;
    }

    // This endpoint returns a paginated response structure
    const response = await api.get<MarketplaceCandidate[]>('/marketplace/candidates', queryParams);

    // The API returns { data: [...], meta: {...} } wrapped in { success: true, data: {...} }
    // Our api.get unwraps the outer data, so we need to handle the inner structure
    return response as unknown as MarketplaceCandidatesResponse;
  },

  /**
   * Request access to a candidate's full profile
   */
  async requestAccess(candidateId: string, message?: string): Promise<RequestAccessResponse> {
    return api.post<RequestAccessResponse>(
      `/marketplace/candidates/${candidateId}/request-access`,
      message ? { message } : undefined
    );
  },

  /**
   * Get full profile of a candidate (requires approved access)
   */
  async getFullProfile(candidateId: string): Promise<MarketplaceCandidateFullProfile> {
    return api.get<MarketplaceCandidateFullProfile>(
      `/marketplace/candidates/${candidateId}/full-profile`
    );
  },

  /**
   * List my access requests
   */
  async listMyRequests(params: {
    page?: number;
    per_page?: number;
    status?: string;
  } = {}): Promise<{
    data: MarketplaceAccessRequest[];
    meta: {
      current_page: number;
      per_page: number;
      total: number;
      last_page: number;
    };
  }> {
    const response = await api.get<MarketplaceAccessRequest[]>('/marketplace/my-requests', params);
    return response as unknown as {
      data: MarketplaceAccessRequest[];
      meta: { current_page: number; per_page: number; total: number; last_page: number };
    };
  },
};

/**
 * Public marketplace access API (token-based, no auth required)
 */
export const marketplaceAccessService = {
  /**
   * Get access request details by token (public)
   */
  async getRequest(token: string): Promise<MarketplaceAccessRequestDetail> {
    // Use fetch directly since this is a public endpoint
    const response = await fetch(`/api/v1/marketplace-access/${token}`);
    const data = await response.json();

    if (!response.ok) {
      throw {
        isAxiosError: true,
        response: { status: response.status, data },
      };
    }

    return data.data;
  },

  /**
   * Approve access request by token (public)
   */
  async approve(token: string, message?: string): Promise<{ status: string; responded_at: string }> {
    const response = await fetch(`/api/v1/marketplace-access/${token}/approve`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: message ? JSON.stringify({ message }) : undefined,
    });
    const data = await response.json();

    if (!response.ok) {
      throw {
        isAxiosError: true,
        response: { status: response.status, data },
      };
    }

    return data.data;
  },

  /**
   * Reject access request by token (public)
   */
  async reject(token: string, message?: string): Promise<{ status: string; responded_at: string }> {
    const response = await fetch(`/api/v1/marketplace-access/${token}/reject`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: message ? JSON.stringify({ message }) : undefined,
    });
    const data = await response.json();

    if (!response.ok) {
      throw {
        isAxiosError: true,
        response: { status: response.status, data },
      };
    }

    return data.data;
  },
};

export default marketplaceService;
