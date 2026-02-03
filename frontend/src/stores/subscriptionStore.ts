import { create } from 'zustand';
import type { SubscriptionStatus, SubscriptionState } from '../types';
import api from '../services/api';

interface SubscriptionStore {
  status: SubscriptionStatus | null;
  state: SubscriptionState;
  isLoading: boolean;
  error: string | null;
  lastFetched: number | null;
  fetchStatus: () => Promise<void>;
  clearStatus: () => void;
  getState: () => SubscriptionState;
  canWrite: () => boolean;
  hasMarketplaceAccess: () => boolean;
  getGraceDaysRemaining: () => number | null;
}

// Cache duration: 5 minutes
const CACHE_DURATION = 5 * 60 * 1000;

function computeState(status: SubscriptionStatus | null): SubscriptionState {
  if (!status) return 'LOCKED';

  if (status.status === 'active') return 'FULL';
  if (status.status === 'grace_period') return 'READ_ONLY_EXPORT';
  return 'LOCKED';
}

export const useSubscriptionStore = create<SubscriptionStore>((set, get) => ({
  status: null,
  state: 'LOCKED',
  isLoading: false,
  error: null,
  lastFetched: null,

  fetchStatus: async () => {
    const { lastFetched, isLoading } = get();

    // Don't fetch if already loading
    if (isLoading) return;

    // Use cache if still valid
    if (lastFetched && Date.now() - lastFetched < CACHE_DURATION) {
      return;
    }

    set({ isLoading: true, error: null });

    try {
      const status = await api.get<SubscriptionStatus>('/company/subscription-status');
      const state = computeState(status);

      set({
        status,
        state,
        isLoading: false,
        lastFetched: Date.now(),
      });
    } catch (error) {
      // On error, assume locked state for safety
      set({
        status: null,
        state: 'LOCKED',
        isLoading: false,
        error: api.getErrorMessage(error),
      });
    }
  },

  clearStatus: () => {
    set({
      status: null,
      state: 'LOCKED',
      isLoading: false,
      error: null,
      lastFetched: null,
    });
  },

  getState: () => get().state,

  canWrite: () => {
    const { state } = get();
    return state === 'FULL';
  },

  hasMarketplaceAccess: () => {
    const { status, state } = get();
    return state === 'FULL' && status?.has_marketplace_access === true;
  },

  getGraceDaysRemaining: () => {
    const { status } = get();
    if (!status?.grace_period_ends_at) return null;

    const graceEnd = new Date(status.grace_period_ends_at);
    const now = new Date();
    const diffMs = graceEnd.getTime() - now.getTime();
    const diffDays = Math.ceil(diffMs / (1000 * 60 * 60 * 24));

    return diffDays > 0 ? diffDays : 0;
  },
}));
