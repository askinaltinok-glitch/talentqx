import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Link } from 'react-router-dom';
import toast from 'react-hot-toast';
import {
  FunnelIcon,
  UserCircleIcon,
  StarIcon,
  ClockIcon,
  CheckCircleIcon,
} from '@heroicons/react/24/outline';
import { marketplaceService } from '../../services/marketplace';
import type { MarketplaceCandidate } from '../../types';
import { localizedPath } from '../../routes';
import RequestAccessModal from './RequestAccessModal';

/**
 * MarketplaceCandidates page shows anonymous candidate profiles
 * Available only for premium subscribers with marketplace access
 */
export default function MarketplaceCandidates() {
  const { t } = useTranslation('common');
  const [candidates, setCandidates] = useState<MarketplaceCandidate[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [total, setTotal] = useState(0);

  // Filters
  const [minScore, setMinScore] = useState<number | undefined>();
  const [minExperience, setMinExperience] = useState<number | undefined>();
  const [showFilters, setShowFilters] = useState(false);

  // Request access modal
  const [selectedCandidate, setSelectedCandidate] = useState<MarketplaceCandidate | null>(null);

  const fetchCandidates = async () => {
    setIsLoading(true);
    try {
      const response = await marketplaceService.listCandidates({
        page,
        per_page: 20,
        min_score: minScore,
        min_experience: minExperience,
      });

      setCandidates(response.data);
      setTotalPages(response.meta.last_page);
      setTotal(response.meta.total);
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
    fetchCandidates();
  }, [page, minScore, minExperience]);

  const handleRequestAccessSuccess = (candidateId: string) => {
    // Update the candidate's status in the list
    setCandidates((prev) =>
      prev.map((c) =>
        c.id === candidateId ? { ...c, access_request_status: 'pending' } : c
      )
    );
    setSelectedCandidate(null);
    toast.success(t('marketplace.requestSent', 'Erişim talebi gönderildi'));
  };

  const getScoreColor = (score: number | undefined) => {
    if (!score) return 'text-gray-400';
    if (score >= 80) return 'text-green-600';
    if (score >= 60) return 'text-yellow-600';
    return 'text-red-600';
  };

  const getStatusBadge = (status: string | undefined) => {
    switch (status) {
      case 'pending':
        return (
          <span className="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
            <ClockIcon className="h-3 w-3" />
            {t('marketplace.pending', 'Beklemede')}
          </span>
        );
      case 'approved':
        return (
          <span className="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
            <CheckCircleIcon className="h-3 w-3" />
            {t('marketplace.approved', 'Onaylandı')}
          </span>
        );
      default:
        return null;
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="sm:flex sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">
            {t('marketplace.title', 'Aday Havuzu')}
          </h1>
          <p className="mt-1 text-sm text-gray-500">
            {t('marketplace.subtitle', 'Anonim aday profillerini inceleyin ve erişim talep edin')}
          </p>
        </div>
        <div className="mt-4 sm:mt-0 flex items-center gap-2">
          <Link
            to={localizedPath('/app/marketplace/my-requests')}
            className="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          >
            {t('marketplace.myRequests', 'Taleplerim')}
          </Link>
          <button
            onClick={() => setShowFilters(!showFilters)}
            className="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          >
            <FunnelIcon className="h-4 w-4 mr-2" />
            {t('common.filters', 'Filtreler')}
          </button>
        </div>
      </div>

      {/* Filters */}
      {showFilters && (
        <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('marketplace.minScore', 'Min. Puan')}
              </label>
              <input
                type="number"
                min="0"
                max="100"
                value={minScore || ''}
                onChange={(e) =>
                  setMinScore(e.target.value ? parseInt(e.target.value) : undefined)
                }
                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                placeholder="0-100"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('marketplace.minExperience', 'Min. Deneyim (yıl)')}
              </label>
              <input
                type="number"
                min="0"
                value={minExperience || ''}
                onChange={(e) =>
                  setMinExperience(e.target.value ? parseInt(e.target.value) : undefined)
                }
                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                placeholder="0+"
              />
            </div>
            <div className="flex items-end">
              <button
                onClick={() => {
                  setMinScore(undefined);
                  setMinExperience(undefined);
                  setPage(1);
                }}
                className="px-4 py-2 text-sm text-gray-600 hover:text-gray-900"
              >
                {t('common.clearFilters', 'Temizle')}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Results count */}
      <div className="text-sm text-gray-500">
        {t('marketplace.resultsCount', '{{count}} aday bulundu', { count: total })}
      </div>

      {/* Loading */}
      {isLoading && (
        <div className="flex justify-center py-12">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600" />
        </div>
      )}

      {/* Empty state */}
      {!isLoading && candidates.length === 0 && (
        <div className="text-center py-12">
          <UserCircleIcon className="mx-auto h-12 w-12 text-gray-400" />
          <h3 className="mt-2 text-sm font-medium text-gray-900">
            {t('marketplace.noResults', 'Aday bulunamadı')}
          </h3>
          <p className="mt-1 text-sm text-gray-500">
            {t('marketplace.tryDifferentFilters', 'Farklı filtreler deneyin')}
          </p>
        </div>
      )}

      {/* Candidate cards */}
      {!isLoading && candidates.length > 0 && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {candidates.map((candidate) => (
            <div
              key={candidate.id}
              className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow"
            >
              {/* Header with score */}
              <div className="flex items-start justify-between mb-4">
                <div className="flex items-center">
                  <div className="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                    <UserCircleIcon className="h-6 w-6 text-gray-400" />
                  </div>
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-900">
                      {t('marketplace.anonymousCandidate', 'Anonim Aday')}
                    </p>
                    <p className="text-xs text-gray-500">{candidate.job_title}</p>
                  </div>
                </div>
                {candidate.overall_score !== undefined && (
                  <div className="flex items-center">
                    <StarIcon className={`h-5 w-5 ${getScoreColor(candidate.overall_score)}`} />
                    <span className={`ml-1 text-lg font-semibold ${getScoreColor(candidate.overall_score)}`}>
                      {candidate.overall_score}
                    </span>
                  </div>
                )}
              </div>

              {/* Details - NO PII */}
              <div className="space-y-2 mb-4">
                {candidate.experience_years !== undefined && (
                  <div className="flex justify-between text-sm">
                    <span className="text-gray-500">{t('marketplace.experience', 'Deneyim')}</span>
                    <span className="text-gray-900">
                      {candidate.experience_years} {t('marketplace.years', 'yıl')}
                    </span>
                  </div>
                )}
                {candidate.job_location && (
                  <div className="flex justify-between text-sm">
                    <span className="text-gray-500">{t('marketplace.location', 'Lokasyon')}</span>
                    <span className="text-gray-900">{candidate.job_location}</span>
                  </div>
                )}
                {candidate.education_level && (
                  <div className="flex justify-between text-sm">
                    <span className="text-gray-500">{t('marketplace.education', 'Eğitim')}</span>
                    <span className="text-gray-900">{candidate.education_level}</span>
                  </div>
                )}
              </div>

              {/* Skills */}
              {candidate.skills && candidate.skills.length > 0 && (
                <div className="mb-4">
                  <div className="flex flex-wrap gap-1">
                    {candidate.skills.slice(0, 5).map((skill, idx) => (
                      <span
                        key={idx}
                        className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800"
                      >
                        {skill}
                      </span>
                    ))}
                    {candidate.skills.length > 5 && (
                      <span className="text-xs text-gray-500">
                        +{candidate.skills.length - 5}
                      </span>
                    )}
                  </div>
                </div>
              )}

              {/* Action button / Status */}
              <div className="border-t border-gray-100 pt-4">
                {candidate.access_request_status === 'approved' ? (
                  <Link
                    to={localizedPath(`/app/marketplace/candidates/${candidate.id}`)}
                    className="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
                  >
                    {t('marketplace.viewFullProfile', 'Tam Profili Gör')}
                  </Link>
                ) : candidate.access_request_status === 'pending' ? (
                  <div className="flex justify-center">
                    {getStatusBadge(candidate.access_request_status)}
                  </div>
                ) : (
                  <button
                    onClick={() => setSelectedCandidate(candidate)}
                    className="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                  >
                    {t('marketplace.requestAccess', 'Erişim Talep Et')}
                  </button>
                )}
              </div>
            </div>
          ))}
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

      {/* Request Access Modal */}
      {selectedCandidate && (
        <RequestAccessModal
          candidate={selectedCandidate}
          onClose={() => setSelectedCandidate(null)}
          onSuccess={() => handleRequestAccessSuccess(selectedCandidate.id)}
        />
      )}
    </div>
  );
}
