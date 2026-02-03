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
 * Platform admins bypass subscription checks entirely.
 */
export default function SubscriptionProvider({ children }: SubscriptionProviderProps) {
  const { isAuthenticated, user } = useAuthStore();
  const { state, isLoading, fetchStatus } = useSubscriptionStore();

  // Platform admins bypass all subscription checks
  const isPlatformAdmin = user?.is_platform_admin === true;

  useEffect(() => {
    // Don't fetch subscription status for platform admins - they bypass everything
    if (isAuthenticated && !isPlatformAdmin) {
      fetchStatus();
    }
  }, [isAuthenticated, isPlatformAdmin, fetchStatus]);

  // Platform admins always get through
  if (isPlatformAdmin) {
    return <>{children}</>;
  }

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
