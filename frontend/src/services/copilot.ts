import axios from 'axios';
import type {
  CopilotContext,
  CopilotChatResponse,
  CopilotContextPreview,
  CopilotHistoryResponse,
} from '../types';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api/v1';

/**
 * Copilot API Service
 * Handles AI copilot chat, context preview, and history
 */
class CopilotService {
  private getAuthHeaders() {
    const token = localStorage.getItem('token');
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    };
  }

  /**
   * Send a chat message to the Copilot
   */
  async chat(
    message: string,
    context?: CopilotContext,
    conversationId?: string
  ): Promise<CopilotChatResponse> {
    try {
      const response = await axios.post<CopilotChatResponse>(
        `${API_BASE_URL}/copilot/chat`,
        {
          message,
          context: context || null,
          conversation_id: conversationId || null,
        },
        { headers: this.getAuthHeaders() }
      );
      return response.data;
    } catch (error) {
      if (axios.isAxiosError(error)) {
        // Handle 503 Service Unavailable
        if (error.response?.status === 503) {
          return {
            success: false,
            error: {
              code: 'copilot_unavailable',
              message: 'AI Copilot geçici olarak kullanılamıyor. Lütfen daha sonra tekrar deneyin.',
            },
          };
        }

        // Handle guardrail or other errors from API
        if (error.response?.data) {
          return error.response.data as CopilotChatResponse;
        }

        return {
          success: false,
          error: {
            code: 'NETWORK_ERROR',
            message: 'Bağlantı hatası. Lütfen internet bağlantınızı kontrol edin.',
          },
        };
      }

      return {
        success: false,
        error: {
          code: 'UNKNOWN_ERROR',
          message: 'Beklenmeyen bir hata oluştu.',
        },
      };
    }
  }

  /**
   * Get context preview for an entity
   */
  async getContextPreview(
    type: CopilotContext['type'],
    id: string
  ): Promise<{ success: boolean; data?: CopilotContextPreview; error?: { code: string; message: string } }> {
    try {
      const response = await axios.get(
        `${API_BASE_URL}/copilot/context/${type}/${id}`,
        { headers: this.getAuthHeaders() }
      );
      return response.data;
    } catch (error) {
      if (axios.isAxiosError(error) && error.response?.data) {
        return error.response.data;
      }
      return {
        success: false,
        error: {
          code: 'CONTEXT_ERROR',
          message: 'Bağlam bilgisi alınamadı.',
        },
      };
    }
  }

  /**
   * Get conversation history
   */
  async getHistory(
    conversationId?: string,
    limit: number = 20
  ): Promise<CopilotHistoryResponse> {
    try {
      const params = new URLSearchParams();
      if (conversationId) params.set('conversation_id', conversationId);
      params.set('limit', limit.toString());

      const response = await axios.get<CopilotHistoryResponse>(
        `${API_BASE_URL}/copilot/history?${params.toString()}`,
        { headers: this.getAuthHeaders() }
      );
      return response.data;
    } catch (error) {
      if (axios.isAxiosError(error) && error.response?.data) {
        return error.response.data as CopilotHistoryResponse;
      }
      return {
        success: false,
        data: { conversations: [] },
      };
    }
  }
}

export const copilotService = new CopilotService();
export default copilotService;
