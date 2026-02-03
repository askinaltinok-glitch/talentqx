import { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import toast from 'react-hot-toast';
import {
  UserCircleIcon,
  CheckCircleIcon,
  XCircleIcon,
  ClockIcon,
  BuildingOfficeIcon,
  EnvelopeIcon,
} from '@heroicons/react/24/outline';
import { marketplaceAccessService } from '../services/marketplace';
import type { MarketplaceAccessRequestDetail } from '../types';

type PageState = 'loading' | 'pending' | 'approved' | 'rejected' | 'expired' | 'error';

/**
 * Public page for approving/rejecting marketplace access requests
 * Accessed via token - no authentication required
 */
export default function PublicMarketplaceAccess() {
  const { t } = useTranslation('common');
  const { token } = useParams<{ token: string }>();
  const [pageState, setPageState] = useState<PageState>('loading');
  const [request, setRequest] = useState<MarketplaceAccessRequestDetail | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [responseMessage, setResponseMessage] = useState('');
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchRequest = async () => {
      if (!token) {
        setPageState('error');
        setError(t('marketplace.errors.invalidToken', 'Geçersiz erişim linki'));
        return;
      }

      try {
        const data = await marketplaceAccessService.getRequest(token);
        setRequest(data);
        setPageState('pending');
      } catch (err: unknown) {
        const errorResponse = err as { response?: { status?: number; data?: { error?: { code?: string } } } };
        const status = errorResponse.response?.status;
        const code = errorResponse.response?.data?.error?.code;

        if (status === 410 || code === 'token_expired') {
          setPageState('expired');
        } else if (status === 400 && code === 'already_processed') {
          // Try to get the status from the error
          setPageState('error');
          setError(t('marketplace.errors.alreadyProcessed', 'Bu talep zaten işlenmiş'));
        } else {
          setPageState('error');
          setError(t('marketplace.errors.loadFailed', 'Talep bilgileri yüklenemedi'));
        }
      }
    };

    fetchRequest();
  }, [token, t]);

  const handleApprove = async () => {
    if (!token) return;

    setIsSubmitting(true);
    try {
      await marketplaceAccessService.approve(token, responseMessage || undefined);
      setPageState('approved');
      toast.success(t('marketplace.accessApproved', 'Erişim onaylandı'));
    } catch (err) {
      toast.error(t('marketplace.errors.approveFailed', 'Onaylama başarısız'));
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleReject = async () => {
    if (!token) return;

    setIsSubmitting(true);
    try {
      await marketplaceAccessService.reject(token, responseMessage || undefined);
      setPageState('rejected');
      toast.success(t('marketplace.accessRejected', 'Talep reddedildi'));
    } catch (err) {
      toast.error(t('marketplace.errors.rejectFailed', 'Reddetme başarısız'));
    } finally {
      setIsSubmitting(false);
    }
  };

  // Loading state
  if (pageState === 'loading') {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600" />
      </div>
    );
  }

  // Error state
  if (pageState === 'error') {
    return (
      <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">
        <div className="max-w-md w-full bg-white rounded-lg shadow-sm p-8 text-center">
          <XCircleIcon className="mx-auto h-12 w-12 text-red-500" />
          <h1 className="mt-4 text-xl font-semibold text-gray-900">
            {t('marketplace.errorTitle', 'Bir Hata Oluştu')}
          </h1>
          <p className="mt-2 text-gray-500">{error}</p>
        </div>
      </div>
    );
  }

  // Expired state
  if (pageState === 'expired') {
    return (
      <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">
        <div className="max-w-md w-full bg-white rounded-lg shadow-sm p-8 text-center">
          <ClockIcon className="mx-auto h-12 w-12 text-yellow-500" />
          <h1 className="mt-4 text-xl font-semibold text-gray-900">
            {t('marketplace.expiredTitle', 'Süre Doldu')}
          </h1>
          <p className="mt-2 text-gray-500">
            {t('marketplace.expiredMessage', 'Bu erişim talebinin süresi dolmuş.')}
          </p>
        </div>
      </div>
    );
  }

  // Approved state
  if (pageState === 'approved') {
    return (
      <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">
        <div className="max-w-md w-full bg-white rounded-lg shadow-sm p-8 text-center">
          <CheckCircleIcon className="mx-auto h-12 w-12 text-green-500" />
          <h1 className="mt-4 text-xl font-semibold text-gray-900">
            {t('marketplace.approvedTitle', 'Erişim Onaylandı')}
          </h1>
          <p className="mt-2 text-gray-500">
            {t(
              'marketplace.approvedMessage',
              'Teşekkürler! Profil paylaşımı açıldı. Bu pencereyi kapatabilirsiniz.'
            )}
          </p>
        </div>
      </div>
    );
  }

  // Rejected state
  if (pageState === 'rejected') {
    return (
      <div className="min-h-screen bg-gray-50 flex flex-col items-center justify-center p-4">
        <div className="max-w-md w-full bg-white rounded-lg shadow-sm p-8 text-center">
          <XCircleIcon className="mx-auto h-12 w-12 text-gray-400" />
          <h1 className="mt-4 text-xl font-semibold text-gray-900">
            {t('marketplace.rejectedTitle', 'Talep Reddedildi')}
          </h1>
          <p className="mt-2 text-gray-500">
            {t('marketplace.rejectedMessage', 'Erişim talebi reddedildi. Bu pencereyi kapatabilirsiniz.')}
          </p>
        </div>
      </div>
    );
  }

  // Pending state - show approval form
  return (
    <div className="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-lg mx-auto">
        {/* Header */}
        <div className="text-center mb-8">
          <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-indigo-100">
            <UserCircleIcon className="h-8 w-8 text-indigo-600" />
          </div>
          <h1 className="mt-4 text-2xl font-bold text-gray-900">
            {t('marketplace.accessRequestTitle', 'Profil Erişim Talebi')}
          </h1>
          <p className="mt-2 text-sm text-gray-500">
            {t(
              'marketplace.accessRequestDescription',
              'Bir şirket profilinize erişim talep ediyor.'
            )}
          </p>
        </div>

        {/* Request details */}
        <div className="bg-white rounded-lg shadow-sm overflow-hidden">
          <div className="px-6 py-4 border-b border-gray-200">
            <h2 className="text-sm font-medium text-gray-500 uppercase tracking-wide">
              {t('marketplace.requesterInfo', 'Talep Eden')}
            </h2>
          </div>

          <div className="px-6 py-4 space-y-4">
            <div className="flex items-center">
              <BuildingOfficeIcon className="h-5 w-5 text-gray-400 mr-3" />
              <div>
                <p className="text-sm font-medium text-gray-900">
                  {request?.requesting_company.name}
                </p>
                <p className="text-xs text-gray-500">{t('marketplace.company', 'Şirket')}</p>
              </div>
            </div>

            <div className="flex items-center">
              <UserCircleIcon className="h-5 w-5 text-gray-400 mr-3" />
              <div>
                <p className="text-sm font-medium text-gray-900">
                  {request?.requesting_user.name}
                </p>
                <p className="text-xs text-gray-500">{t('marketplace.contact', 'İletişim')}</p>
              </div>
            </div>

            <div className="flex items-center">
              <EnvelopeIcon className="h-5 w-5 text-gray-400 mr-3" />
              <div>
                <p className="text-sm text-gray-900">{request?.requesting_user.email}</p>
              </div>
            </div>

            {request?.request_message && (
              <div className="mt-4 p-4 bg-gray-50 rounded-md">
                <p className="text-sm font-medium text-gray-700 mb-1">
                  {t('marketplace.messageFromRequester', 'Mesaj')}:
                </p>
                <p className="text-sm text-gray-600 italic">"{request.request_message}"</p>
              </div>
            )}
          </div>

          {/* Response message */}
          <div className="px-6 py-4 border-t border-gray-200">
            <label
              htmlFor="response"
              className="block text-sm font-medium text-gray-700 mb-2"
            >
              {t('marketplace.responseMessage', 'Yanıt Mesajı (opsiyonel)')}
            </label>
            <textarea
              id="response"
              rows={3}
              value={responseMessage}
              onChange={(e) => setResponseMessage(e.target.value)}
              maxLength={500}
              className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder={t('marketplace.responsePlaceholder', 'İsterseniz bir mesaj ekleyebilirsiniz...')}
            />
          </div>

          {/* Action buttons */}
          <div className="px-6 py-4 bg-gray-50 flex gap-3">
            <button
              onClick={handleApprove}
              disabled={isSubmitting}
              className="flex-1 inline-flex justify-center items-center px-4 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
            >
              {isSubmitting ? (
                <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-white" />
              ) : (
                <>
                  <CheckCircleIcon className="h-5 w-5 mr-2" />
                  {t('marketplace.approve', 'Onayla')}
                </>
              )}
            </button>

            <button
              onClick={handleReject}
              disabled={isSubmitting}
              className="flex-1 inline-flex justify-center items-center px-4 py-3 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
            >
              {isSubmitting ? (
                <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-gray-600" />
              ) : (
                <>
                  <XCircleIcon className="h-5 w-5 mr-2" />
                  {t('marketplace.reject', 'Reddet')}
                </>
              )}
            </button>
          </div>
        </div>

        {/* Privacy note */}
        <div className="mt-6 text-center">
          <p className="text-xs text-gray-500">
            {t(
              'marketplace.privacyNote',
              'Onaylamanız durumunda iletişim bilgileriniz talep eden firma ile paylaşılacaktır.'
            )}
          </p>
        </div>
      </div>
    </div>
  );
}
