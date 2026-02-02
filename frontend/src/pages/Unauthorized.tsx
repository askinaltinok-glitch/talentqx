import { Link, useLocation } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { HomeIcon, ArrowLeftIcon, ShieldExclamationIcon } from '@heroicons/react/24/outline';
import { localizedPath } from '../routes';

/**
 * 403 Unauthorized page for users without platform admin access.
 * Displayed when a company user tries to access platform-admin-only routes.
 */
export default function Unauthorized() {
  const { t } = useTranslation('common');
  const location = useLocation();
  const attemptedPath = (location.state as { from?: string })?.from;

  return (
    <div className="flex flex-col items-center justify-center py-16 px-4">
      <div className="text-center max-w-md">
        <div className="flex justify-center mb-6">
          <div className="rounded-full bg-red-100 p-4">
            <ShieldExclamationIcon className="h-16 w-16 text-red-600" />
          </div>
        </div>
        <h1 className="text-6xl font-bold text-red-600">403</h1>
        <h2 className="mt-4 text-2xl font-bold text-gray-900">
          {t('errors.unauthorized', 'Yetkisiz Erisim')}
        </h2>
        <p className="mt-2 text-gray-600">
          {t(
            'errors.unauthorizedDescription',
            'Bu sayfaya erisim yetkiniz bulunmuyor. Bu ozellik sadece platform yoneticileri icindir.'
          )}
        </p>
        {attemptedPath && (
          <p className="mt-2 text-sm text-gray-400">
            {t('errors.attemptedPath', 'Denenen yol')}: {attemptedPath}
          </p>
        )}
        <div className="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
          <Link
            to={localizedPath('/app')}
            className="btn-primary inline-flex items-center justify-center"
          >
            <HomeIcon className="h-5 w-5 mr-2" />
            {t('errors.backToDashboard', "Dashboard'a Don")}
          </Link>
          <button
            onClick={() => window.history.back()}
            className="btn-secondary inline-flex items-center justify-center"
          >
            <ArrowLeftIcon className="h-5 w-5 mr-2" />
            {t('errors.goBack', 'Geri Don')}
          </button>
        </div>
      </div>
    </div>
  );
}
