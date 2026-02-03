import { useEffect, ReactNode } from 'react';
import { useSubscriptionStore } from '../../stores/subscriptionStore';
import { useAuthStore } from '../../stores/authStore';
import SubscriptionLockedPage from './SubscriptionLockedPage';

interface SubscriptionProviderProps {
  children: ReactNode;
}

/**
 * SubscriptionProvider fetches and caches subscription status
 * and renders locked page if subscription is expired (not in grace period)
 */
export default function SubscriptionProvider({ children }: SubscriptionProviderProps) {
  const { isAuthenticated } = useAuthStore();
  const { state, isLoading, fetchStatus } = useSubscriptionStore();

  useEffect(() => {
    if (isAuthenticated) {
      fetchStatus();
    }
  }, [isAuthenticated, fetchStatus]);

  // Show loading state only on initial fetch
  if (isLoading && !state) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600" />
      </div>
    );
  }

  // If locked (expired + grace period ended), show locked page
  if (state === 'LOCKED' && isAuthenticated) {
    return <SubscriptionLockedPage />;
  }

  return <>{children}</>;
}
