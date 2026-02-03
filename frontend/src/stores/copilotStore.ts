import { create } from 'zustand';
import type {
  CopilotContext,
  CopilotMessage,
  CopilotStructuredResponse,
  CopilotContextPreview,
} from '../types';
import copilotService from '../services/copilot';

interface CopilotState {
  // Drawer state
  isOpen: boolean;
  isLoading: boolean;
  isLoadingContext: boolean;
  error: string | null;

  // Conversation state
  conversationId: string | null;
  messages: CopilotMessage[];
  context: CopilotContext | null;
  contextPreview: CopilotContextPreview | null;

  // Service unavailable state
  isUnavailable: boolean;

  // Actions
  openDrawer: (context?: CopilotContext) => void;
  closeDrawer: () => void;
  setContext: (context: CopilotContext) => void;
  clearContext: () => void;
  sendMessage: (message: string) => Promise<void>;
  loadContextPreview: () => Promise<void>;
  loadHistory: (conversationId?: string) => Promise<void>;
  clearMessages: () => void;
  reset: () => void;
}

const initialState = {
  isOpen: false,
  isLoading: false,
  isLoadingContext: false,
  error: null,
  conversationId: null,
  messages: [],
  context: null,
  contextPreview: null,
  isUnavailable: false,
};

export const useCopilotStore = create<CopilotState>((set, get) => ({
  ...initialState,

  openDrawer: (context?: CopilotContext) => {
    set({
      isOpen: true,
      context: context || get().context,
      error: null,
      isUnavailable: false,
    });

    // Load context preview if context is set
    if (context || get().context) {
      get().loadContextPreview();
    }
  },

  closeDrawer: () => {
    set({ isOpen: false });
  },

  setContext: (context: CopilotContext) => {
    const currentContext = get().context;
    // Only reset if context changed
    if (currentContext?.type !== context.type || currentContext?.id !== context.id) {
      set({
        context,
        messages: [],
        conversationId: null,
        contextPreview: null,
        error: null,
      });
    }
  },

  clearContext: () => {
    set({
      context: null,
      contextPreview: null,
      messages: [],
      conversationId: null,
    });
  },

  sendMessage: async (message: string) => {
    const { context, conversationId, messages } = get();

    // Add user message optimistically
    const userMessage: CopilotMessage = {
      id: `temp-${Date.now()}`,
      role: 'user',
      content: message,
      created_at: new Date().toISOString(),
    };

    set({
      messages: [...messages, userMessage],
      isLoading: true,
      error: null,
      isUnavailable: false,
    });

    try {
      const response = await copilotService.chat(
        message,
        context || undefined,
        conversationId || undefined
      );

      if (!response.success) {
        // Handle specific error codes
        if (response.error?.code === 'copilot_unavailable') {
          set({
            isLoading: false,
            isUnavailable: true,
            error: response.error.message,
          });
          return;
        }

        set({
          isLoading: false,
          error: response.error?.message || 'Bir hata oluştu.',
        });
        return;
      }

      const { data } = response;
      if (!data) {
        set({
          isLoading: false,
          error: 'Yanıt alınamadı.',
        });
        return;
      }

      // Create assistant message
      const assistantMessage: CopilotMessage = {
        id: data.message_id,
        role: 'assistant',
        content: data.response,
        guardrail_triggered: data.guardrail_triggered,
        created_at: new Date().toISOString(),
      };

      set({
        messages: [...get().messages, assistantMessage],
        conversationId: data.conversation_id,
        isLoading: false,
      });
    } catch {
      set({
        isLoading: false,
        error: 'Mesaj gönderilemedi. Lütfen tekrar deneyin.',
      });
    }
  },

  loadContextPreview: async () => {
    const { context } = get();
    if (!context) return;

    set({ isLoadingContext: true });

    try {
      const response = await copilotService.getContextPreview(context.type, context.id);
      if (response.success && response.data) {
        set({ contextPreview: response.data, isLoadingContext: false });
      } else {
        set({ isLoadingContext: false });
      }
    } catch {
      set({ isLoadingContext: false });
    }
  },

  loadHistory: async (conversationId?: string) => {
    set({ isLoading: true });

    try {
      const response = await copilotService.getHistory(conversationId);
      if (response.success && response.data.messages) {
        set({
          messages: response.data.messages,
          conversationId: response.data.conversation_id || null,
          isLoading: false,
        });
      } else {
        set({ isLoading: false });
      }
    } catch {
      set({ isLoading: false });
    }
  },

  clearMessages: () => {
    set({
      messages: [],
      conversationId: null,
      error: null,
    });
  },

  reset: () => {
    set(initialState);
  },
}));

// Helper function to check if content is structured response
export function isStructuredResponse(
  content: string | CopilotStructuredResponse
): content is CopilotStructuredResponse {
  return typeof content === 'object' && 'answer' in content;
}
