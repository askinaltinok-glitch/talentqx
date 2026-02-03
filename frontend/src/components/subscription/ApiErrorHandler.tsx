import { useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import toast from 'react-hot-toast';
import { useTranslation } from 'react-i18next';
import { AxiosError } from 'axios';
import { localizedPath } from '../../routes';

interface ApiErrorResponse {
  success: boolean;
  error?: {
    code: string;
    message: string;
  };
  message?: string;
}

/**
 * Maps API error codes to user-friendly messages and actions
 */
export function useApiErrorHandler() {
  const { t } = useTranslation('common');
  const navigate = useNavigate();

  const handleError = useCallback(
    (error: unknown) => {
      if (!isAxiosError(error)) {
        toast.error(t('errors.unexpected', 'Beklenmeyen bir hata oluştu'));
        return;
      }

      const status = error.response?.status;
      const errorCode = error.response?.data?.error?.code;
      const errorMessage = error.response?.data?.error?.message || error.response?.data?.message;

      // Handle subscription-related 403 errors
      if (status === 403) {
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
    [navigate, t]
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
 * Place this at the app root level
 */
export default function ApiErrorHandler({ children }: { children: React.ReactNode }) {
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

  return <>{children}</>;
}
