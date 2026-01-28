import { useEffect } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuthStore } from '../stores/authStore';
import { loginPath } from '../routes';

interface RequireAuthProps {
  children: React.ReactNode;
}

/**
 * Auth guard component that redirects to login with next parameter
 * After successful login, user will be redirected back to the original page
 */
export default function RequireAuth({ children }: RequireAuthProps) {
  const { isAuthenticated, token, fetchUser, isLoading } = useAuthStore();
  const location = useLocation();

  useEffect(() => {
    if (token && !isAuthenticated) {
      fetchUser();
    }
  }, [token, isAuthenticated, fetchUser]);

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (!isAuthenticated && !token) {
    // Save the current location for redirect after login
    const currentPath = location.pathname + location.search;
    return <Navigate to={loginPath(currentPath)} replace />;
  }

  return <>{children}</>;
}
