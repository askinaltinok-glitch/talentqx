import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import {
  ClockIcon,
  CheckCircleIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
  ArrowLeftIcon,
  UserCircleIcon,
} from '@heroicons/react/24/outline';
import { marketplaceService } from '../../services/marketplace';
import type { MarketplaceAccessRequest, MarketplaceAccessRequestStatus } from '../../types';
import { localizedPath } from '../../routes';

const statusTabs: { key: MarketplaceAccessRequestStatus | 'all'; label: string }[] = [
  { key: 'all', label: 'Tümü' },
  { key: 'pending', label: 'Beklemede' },
  { key: 'approved', label: 'Onaylanan' },
  { key: 'rejected', label: 'Reddedilen' },
  { key: 'expired', label: 'Süresi Dolan' },
];

const statusIcons: Record<MarketplaceAccessRequestStatus, typeof ClockIcon> = {
  pending: ClockIcon,
  approved: CheckCircleIcon,
  rejected: XCircleIcon,
  expired: ExclamationTriangleIcon,
};

const statusColors: Record<MarketplaceAccessRequestStatus, string> = {
  pending: 'text-yellow-600 bg-yellow-100',
  approved: 'text-green-600 bg-green-100',
  rejected: 'text-red-600 bg-red-100',
  expired: 'text-gray-600 bg-gray-100',
};

export default function MarketplaceMyRequests() {
  const { t } = useTranslation('common');
  const [requests, setRequests] = useState<MarketplaceAccessRequest[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<MarketplaceAccessRequestStatus | 'all'>('all');
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  const fetchRequests = async () => {
    setIsLoading(true);
    try {
      const response = await marketplaceService.listMyRequests({
        page,
        per_page: 20,
        status: activeTab === 'all' ? undefined : activeTab,
      });

      setRequests(response.data);
      setTotalPages(response.meta.last_page);
    } catch (error) {
      const errorHandler = (
        window as unknown as { __apiErrorHandler?: (error: unknown) => void }
      ).__apiErrorHandler;
      if (errorHandler) {
        errorHandler(error);
      }
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchRequests();
  }, [page, activeTab]);

  const handleTabChange = (tab: MarketplaceAccessRequestStatus | 'all') => {
    setActiveTab(tab);
    setPage(1);
  };

  const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString('tr-TR', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="sm:flex sm:items-center sm:justify-between">
        <div className="flex items-center gap-4">
          <Link
            to={localizedPath('/app/marketplace')}
            className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
          >
            <ArrowLeftIcon className="h-4 w-4 mr-1" />
            {t('common.back', 'Geri')}
          </Link>
          <div>
            <h1 className="text-2xl font-bold text-gray-900">
              {t('marketplace.myRequestsTitle', 'Erişim Taleplerim')}
            </h1>
            <p className="mt-1 text-sm text-gray-500">
              {t('marketplace.myRequestsSubtitle', 'Gönderdiğiniz erişim taleplerini takip edin')}
            </p>
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200">
        <nav className="-mb-px flex space-x-8" aria-label="Tabs">
          {statusTabs.map((tab) => (
            <button
              key={tab.key}
              onClick={() => handleTabChange(tab.key)}
              className={`${
                activeTab === tab.key
                  ? 'border-indigo-500 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
              } whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium`}
            >
              {t(`marketplace.tab.${tab.key}`, tab.label)}
            </button>
          ))}
        </nav>
      </div>

      {/* Loading */}
      {isLoading && (
        <div className="flex justify-center py-12">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600" />
        </div>
      )}

      {/* Empty state */}
      {!isLoading && requests.length === 0 && (
        <div className="text-center py-12">
          <ClockIcon className="mx-auto h-12 w-12 text-gray-400" />
          <h3 className="mt-2 text-sm font-medium text-gray-900">
            {t('marketplace.noRequests', 'Henüz talep yok')}
          </h3>
          <p className="mt-1 text-sm text-gray-500">
            {t('marketplace.startBrowsing', 'Aday havuzundan erişim talep edebilirsiniz')}
          </p>
          <div className="mt-6">
            <Link
              to={localizedPath('/app/marketplace')}
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
            >
              {t('marketplace.browseCandidates', 'Adaylara Göz At')}
            </Link>
          </div>
        </div>
      )}

      {/* Requests list */}
      {!isLoading && requests.length > 0 && (
        <div className="bg-white shadow-sm rounded-lg overflow-hidden">
          <ul className="divide-y divide-gray-200">
            {requests.map((request) => {
              const StatusIcon = statusIcons[request.status];
              const statusColor = statusColors[request.status];

              return (
                <li key={request.id} className="p-6 hover:bg-gray-50">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                      <div className="flex-shrink-0">
                        <div className="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                          <UserCircleIcon className="h-6 w-6 text-gray-400" />
                        </div>
                      </div>
                      <div>
                        <p className="text-sm font-medium text-gray-900">
                          {request.candidate?.job_title ||
                            t('marketplace.anonymousCandidate', 'Anonim Aday')}
                        </p>
                        <p className="text-sm text-gray-500">
                          {t('marketplace.requestedAt', 'Talep tarihi')}: {formatDate(request.created_at)}
                        </p>
                        {request.response_message && (
                          <p className="mt-1 text-sm text-gray-500 italic">
                            "{request.response_message}"
                          </p>
                        )}
                      </div>
                    </div>

                    <div className="flex items-center space-x-4">
                      <span
                        className={`inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium ${statusColor}`}
                      >
                        <StatusIcon className="h-4 w-4" />
                        {t(`marketplace.status.${request.status}`, request.status)}
                      </span>

                      {request.status === 'approved' && request.candidate && (
                        <Link
                          to={localizedPath(`/app/marketplace/candidates/${request.candidate.id}`)}
                          className="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
                        >
                          {t('marketplace.viewFullProfile', 'Tam Profil')}
                        </Link>
                      )}
                    </div>
                  </div>
                </li>
              );
            })}
          </ul>
        </div>
      )}

      {/* Pagination */}
      {!isLoading && totalPages > 1 && (
        <div className="flex justify-center gap-2">
          <button
            onClick={() => setPage((p) => Math.max(1, p - 1))}
            disabled={page === 1}
            className="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            {t('common.previous', 'Önceki')}
          </button>
          <span className="px-4 py-2 text-sm text-gray-700">
            {page} / {totalPages}
          </span>
          <button
            onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
            disabled={page === totalPages}
            className="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
          >
            {t('common.next', 'Sonraki')}
          </button>
        </div>
      )}
    </div>
  );
}
