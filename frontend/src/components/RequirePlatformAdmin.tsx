import { Navigate, useLocation } from 'react-router-dom';
import { useAuthStore } from '../stores/authStore';
import { localizedPath } from '../routes';

interface RequirePlatformAdminProps {
  children: React.ReactNode;
}

/**
 * Route guard component that only allows platform admins (is_platform_admin = true).
 * Redirects non-platform-admin users to a 403 Unauthorized page.
 *
 * This component should wrap platform-admin-only routes like:
 * - Sales Console (Leads)
 * - AI Costs / Billing
 * - System Settings
 * - Global Analytics
 */
export default function RequirePlatformAdmin({ children }: RequirePlatformAdminProps) {
  const { user, isAuthenticated } = useAuthStore();
  const location = useLocation();

  // If not authenticated, RequireAuth will handle redirect
  if (!isAuthenticated || !user) {
    return null;
  }

  // Check platform admin access
  if (!user.is_platform_admin) {
    // Redirect to 403 page with original path for context
    return (
      <Navigate
        to={localizedPath('/app/unauthorized')}
        state={{ from: location.pathname }}
        replace
      />
    );
  }

  return <>{children}</>;
}
