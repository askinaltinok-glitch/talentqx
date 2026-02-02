import { Navigate, useLocation } from 'react-router-dom';
import { useAuthStore } from '../stores/authStore';
import { localizedPath } from '../routes';

/**
 * Routes allowed for company users (non-platform admins).
 * Default-deny: anything NOT in this list redirects to 403.
 * Platform admins bypass this completely.
 */
const CUSTOMER_ALLOWED_ROUTES = [
  '/app',           // Dashboard (exact)
  '/app/jobs',      // Jobs list and details
  '/app/candidates', // Candidates list and details
  '/app/interviews', // Interview details
  '/app/unauthorized', // Error page itself
];

/**
 * Check if a path is allowed for company users.
 * Matches prefix to allow sub-routes like /app/jobs/123
 */
function isRouteAllowedForCustomer(pathname: string): boolean {
  // Remove language prefix (e.g., /en/app/jobs -> /app/jobs)
  const pathWithoutLang = pathname.replace(/^\/[a-z]{2}/, '');

  // Check exact match for dashboard
  if (pathWithoutLang === '/app' || pathWithoutLang === '/app/') {
    return true;
  }

  // Check prefix matches for other routes
  return CUSTOMER_ALLOWED_ROUTES.some((route) => {
    if (route === '/app') return false; // Already handled above
    return pathWithoutLang.startsWith(route);
  });
}

interface CustomerRouteGuardProps {
  children: React.ReactNode;
}

/**
 * Route guard implementing default-deny for non-platform users.
 *
 * - Platform admins: full access to all routes
 * - Company users: only routes in CUSTOMER_ALLOWED_ROUTES
 * - Everything else: redirects to 403 Unauthorized page
 *
 * This should wrap all /app/* routes to enforce the allowlist.
 */
export default function CustomerRouteGuard({ children }: CustomerRouteGuardProps) {
  const { user, isAuthenticated } = useAuthStore();
  const location = useLocation();

  // If not authenticated, RequireAuth will handle redirect
  if (!isAuthenticated || !user) {
    return null;
  }

  // Platform admins bypass all restrictions
  if (user.is_platform_admin) {
    return <>{children}</>;
  }

  // Company user: check if current route is in allowlist
  if (!isRouteAllowedForCustomer(location.pathname)) {
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

/**
 * Export the allowlist checker for use in other components (e.g., Layout menu filtering)
 */
export { isRouteAllowedForCustomer, CUSTOMER_ALLOWED_ROUTES };
