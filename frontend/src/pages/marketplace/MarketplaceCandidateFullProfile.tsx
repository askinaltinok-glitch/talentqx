import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import {
  ArrowLeftIcon,
  UserCircleIcon,
  EnvelopeIcon,
  PhoneIcon,
  MapPinIcon,
  BriefcaseIcon,
  AcademicCapIcon,
  StarIcon,
} from '@heroicons/react/24/outline';
import { marketplaceService } from '../../services/marketplace';
import type { MarketplaceCandidateFullProfile } from '../../types';
import { localizedPath } from '../../routes';

export default function MarketplaceCandidateFullProfilePage() {
  const { t } = useTranslation('common');
  const { id } = useParams<{ id: string }>();
  const [profile, setProfile] = useState<MarketplaceCandidateFullProfile | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchProfile = async () => {
      if (!id) return;

      setIsLoading(true);
      setError(null);

      try {
        const data = await marketplaceService.getFullProfile(id);
        setProfile(data);
      } catch (err) {
        const errorHandler = (
          window as unknown as { __apiErrorHandler?: (error: unknown) => void }
        ).__apiErrorHandler;
        if (errorHandler) {
          errorHandler(err);
        }
        setError(t('marketplace.errors.loadFailed', 'Profil yüklenemedi'));
      } finally {
        setIsLoading(false);
      }
    };

    fetchProfile();
  }, [id, t]);

  const getScoreColor = (score: number | undefined) => {
    if (!score) return 'text-gray-400';
    if (score >= 80) return 'text-green-600';
    if (score >= 60) return 'text-yellow-600';
    return 'text-red-600';
  };

  if (isLoading) {
    return (
      <div className="flex justify-center py-12">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600" />
      </div>
    );
  }

  if (error || !profile) {
    return (
      <div className="text-center py-12">
        <UserCircleIcon className="mx-auto h-12 w-12 text-gray-400" />
        <h3 className="mt-2 text-sm font-medium text-gray-900">
          {error || t('marketplace.profileNotFound', 'Profil bulunamadı')}
        </h3>
        <div className="mt-6">
          <Link
            to={localizedPath('/app/marketplace')}
            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
          >
            <ArrowLeftIcon className="h-4 w-4 mr-2" />
            {t('marketplace.backToList', 'Listeye Dön')}
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Back link */}
      <Link
        to={localizedPath('/app/marketplace')}
        className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700"
      >
        <ArrowLeftIcon className="h-4 w-4 mr-1" />
        {t('marketplace.backToList', 'Aday Havuzu')}
      </Link>

      {/* Profile card */}
      <div className="bg-white shadow-sm rounded-lg overflow-hidden">
        {/* Header */}
        <div className="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-8">
          <div className="flex items-center">
            <div className="h-20 w-20 rounded-full bg-white/20 flex items-center justify-center">
              <UserCircleIcon className="h-12 w-12 text-white" />
            </div>
            <div className="ml-6">
              <h1 className="text-2xl font-bold text-white">
                {profile.first_name} {profile.last_name}
              </h1>
              <p className="text-indigo-200">{profile.job?.title}</p>
            </div>
            {profile.overall_score !== undefined && (
              <div className="ml-auto text-center">
                <div className="flex items-center justify-center bg-white/20 rounded-full h-16 w-16">
                  <span className="text-2xl font-bold text-white">{profile.overall_score}</span>
                </div>
                <p className="mt-1 text-sm text-indigo-200">{t('marketplace.score', 'Puan')}</p>
              </div>
            )}
          </div>
        </div>

        {/* Contact info */}
        <div className="border-b border-gray-200 px-6 py-4">
          <h2 className="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">
            {t('marketplace.contactInfo', 'İletişim Bilgileri')}
          </h2>
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div className="flex items-center">
              <EnvelopeIcon className="h-5 w-5 text-gray-400 mr-2" />
              <a href={`mailto:${profile.email}`} className="text-sm text-indigo-600 hover:underline">
                {profile.email}
              </a>
            </div>
            {profile.phone && (
              <div className="flex items-center">
                <PhoneIcon className="h-5 w-5 text-gray-400 mr-2" />
                <a href={`tel:${profile.phone}`} className="text-sm text-indigo-600 hover:underline">
                  {profile.phone}
                </a>
              </div>
            )}
            {profile.job?.location && (
              <div className="flex items-center">
                <MapPinIcon className="h-5 w-5 text-gray-400 mr-2" />
                <span className="text-sm text-gray-900">{profile.job.location}</span>
              </div>
            )}
          </div>
        </div>

        {/* Details */}
        <div className="px-6 py-4 grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Left column - CV data */}
          <div className="space-y-6">
            <div>
              <h2 className="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">
                {t('marketplace.experience', 'Deneyim')}
              </h2>
              {profile.cv_parsed_data?.experience ? (
                <div className="space-y-2">
                  {(profile.cv_parsed_data.experience as unknown[]).map((exp: unknown, idx: number) => {
                    const expItem = exp as { title?: string; company?: string; duration?: string };
                    return (
                      <div key={idx} className="flex items-start">
                        <BriefcaseIcon className="h-5 w-5 text-gray-400 mr-2 mt-0.5" />
                        <div>
                          <p className="text-sm font-medium text-gray-900">{expItem.title}</p>
                          <p className="text-sm text-gray-500">
                            {expItem.company} {expItem.duration && `• ${expItem.duration}`}
                          </p>
                        </div>
                      </div>
                    );
                  })}
                </div>
              ) : (
                <p className="text-sm text-gray-500">
                  {t('marketplace.noExperience', 'Deneyim bilgisi yok')}
                </p>
              )}
            </div>

            <div>
              <h2 className="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">
                {t('marketplace.education', 'Eğitim')}
              </h2>
              {profile.cv_parsed_data?.education ? (
                <div className="space-y-2">
                  {(profile.cv_parsed_data.education as unknown[]).map((edu: unknown, idx: number) => {
                    const eduItem = edu as { degree?: string; school?: string; year?: string };
                    return (
                      <div key={idx} className="flex items-start">
                        <AcademicCapIcon className="h-5 w-5 text-gray-400 mr-2 mt-0.5" />
                        <div>
                          <p className="text-sm font-medium text-gray-900">{eduItem.degree}</p>
                          <p className="text-sm text-gray-500">
                            {eduItem.school} {eduItem.year && `• ${eduItem.year}`}
                          </p>
                        </div>
                      </div>
                    );
                  })}
                </div>
              ) : (
                <p className="text-sm text-gray-500">
                  {t('marketplace.noEducation', 'Eğitim bilgisi yok')}
                </p>
              )}
            </div>

            {profile.cv_parsed_data?.skills && (
              <div>
                <h2 className="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">
                  {t('marketplace.skills', 'Beceriler')}
                </h2>
                <div className="flex flex-wrap gap-2">
                  {(profile.cv_parsed_data.skills as string[]).map((skill, idx) => (
                    <span
                      key={idx}
                      className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800"
                    >
                      {skill}
                    </span>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* Right column - Analysis */}
          <div className="space-y-6">
            {profile.latest_analysis && (
              <>
                <div>
                  <h2 className="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">
                    {t('marketplace.analysisOverview', 'Değerlendirme Özeti')}
                  </h2>
                  <div className="bg-gray-50 rounded-lg p-4">
                    <div className="flex items-center justify-between mb-4">
                      <span className="text-sm text-gray-500">
                        {t('marketplace.overallScore', 'Genel Puan')}
                      </span>
                      <div className="flex items-center">
                        <StarIcon
                          className={`h-5 w-5 ${getScoreColor(profile.latest_analysis.overall_score)}`}
                        />
                        <span
                          className={`ml-1 text-lg font-semibold ${getScoreColor(
                            profile.latest_analysis.overall_score
                          )}`}
                        >
                          {profile.latest_analysis.overall_score}
                        </span>
                      </div>
                    </div>
                    {profile.latest_analysis.recommendation && (
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-gray-500">
                          {t('marketplace.recommendation', 'Öneri')}
                        </span>
                        <span
                          className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                            profile.latest_analysis.recommendation === 'hire'
                              ? 'bg-green-100 text-green-800'
                              : profile.latest_analysis.recommendation === 'hold'
                              ? 'bg-yellow-100 text-yellow-800'
                              : 'bg-red-100 text-red-800'
                          }`}
                        >
                          {t(
                            `marketplace.recommendation.${profile.latest_analysis.recommendation}`,
                            profile.latest_analysis.recommendation
                          )}
                        </span>
                      </div>
                    )}
                  </div>
                </div>

                {profile.latest_analysis.competency_scores && (
                  <div>
                    <h2 className="text-sm font-medium text-gray-500 uppercase tracking-wide mb-3">
                      {t('marketplace.competencyScores', 'Yetkinlik Puanları')}
                    </h2>
                    <div className="space-y-3">
                      {Object.entries(profile.latest_analysis.competency_scores).map(
                        ([code, score]) => (
                          <div key={code}>
                            <div className="flex justify-between text-sm mb-1">
                              <span className="text-gray-700">{code}</span>
                              <span className={getScoreColor(score)}>{score}</span>
                            </div>
                            <div className="w-full bg-gray-200 rounded-full h-2">
                              <div
                                className={`h-2 rounded-full ${
                                  score >= 80
                                    ? 'bg-green-500'
                                    : score >= 60
                                    ? 'bg-yellow-500'
                                    : 'bg-red-500'
                                }`}
                                style={{ width: `${score}%` }}
                              />
                            </div>
                          </div>
                        )
                      )}
                    </div>
                  </div>
                )}
              </>
            )}

            {/* Access info */}
            <div className="bg-green-50 rounded-lg p-4">
              <div className="flex items-center">
                <div className="flex-shrink-0">
                  <svg
                    className="h-5 w-5 text-green-400"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                  >
                    <path
                      fillRule="evenodd"
                      d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                      clipRule="evenodd"
                    />
                  </svg>
                </div>
                <div className="ml-3">
                  <p className="text-sm font-medium text-green-800">
                    {t('marketplace.accessGranted', 'Erişim Onaylandı')}
                  </p>
                  {profile.access_granted_at && (
                    <p className="mt-1 text-sm text-green-600">
                      {t('marketplace.grantedAt', 'Onay tarihi')}: {' '}
                      {new Date(profile.access_granted_at).toLocaleDateString('tr-TR')}
                    </p>
                  )}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
