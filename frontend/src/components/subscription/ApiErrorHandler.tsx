import { useEffect, useCallback } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useTranslation } from 'react-i18next';
import { AxiosError } from 'axios';
import { localizedPath } from '../../routes';

/**
 * Public routes that should NOT receive subscription-related error handling.
 * These routes are accessible without authentication and subscription checks.
 */
const PUBLIC_ROUTE_PATTERNS = [
  /^\/apply\//,              // /apply/:companySlug/:branchSlug/:roleCode
  /^\/assessment\//,         // /assessment/:token
  /^\/a\//,                  // /a/:token (short form)
  /^\/interview\//,          // /interview/:role
  /^\/i\//,                  // /i/:role (short form)
  /^\/marketplace-access\//, // /marketplace-access/:token
  /^\/privacy$/,             // /privacy
  /^\/[a-z]{2}$/,            // /{lang} (landing page)
  /^\/[a-z]{2}\/login$/,     // /{lang}/login
  /^\/[a-z]{2}\/privacy$/,   // /{lang}/privacy
];

function isPublicRoute(pathname: string): boolean {
  return PUBLIC_ROUTE_PATTERNS.some((pattern) => pattern.test(pathname));
}

interface ApiErrorResponse {
  success: boolean;
  error?: {
    code: string;
    message: string;
  };
  message?: string;
}

/**
 * Maps API error codes to user-friendly messages and actions.
 *
 * IMPORTANT: Subscription-related errors are only handled on protected routes (/app/*).
 * Public routes (apply, assessment, interview, etc.) receive generic error messages only.
 * This prevents subscription UI leaking into public-facing pages.
 */
export function useApiErrorHandler() {
  const { t } = useTranslation('common');
  const navigate = useNavigate();
  const location = useLocation();

  const handleError = useCallback(
    (error: unknown) => {
      if (!isAxiosError(error)) {
        toast.error(t('errors.unexpected', 'Beklenmeyen bir hata oluştu'));
        return;
      }

      const status = error.response?.status;
      const errorCode = error.response?.data?.error?.code;
      const errorMessage = error.response?.data?.error?.message || error.response?.data?.message;

      // Check if we're on a public route - skip subscription error handling
      const onPublicRoute = isPublicRoute(location.pathname);

      // Handle subscription-related 403 errors (only on protected routes)
      if (status === 403) {
        // On public routes, show generic error message only - no subscription UI
        if (onPublicRoute) {
          toast.error(
            errorMessage || t('errors.generic', 'Bir hata oluştu. Lütfen tekrar deneyin.')
          );
          return;
        }

        switch (errorCode) {
          case 'subscription_required':
            toast.error(
              t(
                'subscription.errors.required',
                'Aboneliğiniz sona erdi. Erişim kapalı.'
              ),
              { duration: 5000 }
            );
            navigate(localizedPath('/app'), { replace: true });
            return;

          case 'grace_period_restricted':
            toast.error(
              t(
                'subscription.errors.graceRestricted',
                'Grace döneminde sadece rapor indirebilirsiniz.'
              ),
              { duration: 4000 }
            );
            return;

          case 'premium_required':
            toast.error(
              t(
                'subscription.errors.premiumRequired',
                'Bu özellik premium abonelik gerektirmektedir.'
              ),
              { duration: 4000 }
            );
            return;

          case 'access_not_granted':
            toast.error(
              t(
                'marketplace.errors.accessNotGranted',
                'Bu aday profili için erişim izniniz yok.'
              ),
              { duration: 4000 }
            );
            return;

          default:
            toast.error(
              errorMessage || t('errors.forbidden', 'Bu işlem için yetkiniz yok')
            );
            return;
        }
      }

      // Handle marketplace-related 400 errors
      if (status === 400) {
        switch (errorCode) {
          case 'request_pending':
            toast.error(
              t(
                'marketplace.errors.requestPending',
                'Bu aday için zaten bekleyen bir erişim talebiniz var.'
              )
            );
            return;

          case 'already_approved':
            toast(
              t(
                'marketplace.errors.alreadyApproved',
                'Bu aday profili için zaten erişim izniniz var.'
              ),
              { icon: 'ℹ️' }
            );
            return;

          case 'already_processed':
            toast.error(
              t('marketplace.errors.alreadyProcessed', 'Bu talep zaten işlenmiş.')
            );
            return;

          default:
            toast.error(errorMessage || t('errors.badRequest', 'Geçersiz istek'));
            return;
        }
      }

      // Handle expired token (410)
      if (status === 410) {
        if (errorCode === 'token_expired') {
          toast.error(
            t('marketplace.errors.tokenExpired', 'Bu erişim talebi süresi dolmuş.'),
            { duration: 5000 }
          );
          return;
        }
      }

      // Handle other errors
      if (status === 401) {
        // Auth errors are handled by API interceptor
        return;
      }

      if (status === 404) {
        toast.error(errorMessage || t('errors.notFound', 'Kayıt bulunamadı'));
        return;
      }

      if (status && status >= 500) {
        toast.error(t('errors.server', 'Sunucu hatası. Lütfen daha sonra tekrar deneyin.'));
        return;
      }

      // Default error
      toast.error(errorMessage || t('errors.unexpected', 'Beklenmeyen bir hata oluştu'));
    },
    [navigate, t, location.pathname]
  );

  return { handleError };
}

function isAxiosError(error: unknown): error is AxiosError<ApiErrorResponse> {
  return (
    typeof error === 'object' &&
    error !== null &&
    'isAxiosError' in error &&
    (error as AxiosError).isAxiosError === true
  );
}

/**
 * Component that sets up global API error handling
 * Place this at the app root level (renders nothing)
 */
export default function ApiErrorHandler() {
  const { handleError } = useApiErrorHandler();

  useEffect(() => {
    // Store handler globally for use in API service
    (window as unknown as { __apiErrorHandler?: (error: unknown) => void }).__apiErrorHandler =
      handleError;

    return () => {
      delete (window as unknown as { __apiErrorHandler?: (error: unknown) => void })
        .__apiErrorHandler;
    };
  }, [handleError]);

  return null;
}
