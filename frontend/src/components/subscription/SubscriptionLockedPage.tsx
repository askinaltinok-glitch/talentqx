import { useTranslation } from 'react-i18next';
import { LockClosedIcon, PhoneIcon, EnvelopeIcon } from '@heroicons/react/24/outline';
import { useAuthStore } from '../../stores/authStore';

/**
 * SubscriptionLockedPage is shown when subscription and grace period have both expired
 * User can only renew subscription or contact sales
 */
export default function SubscriptionLockedPage() {
  const { t } = useTranslation('common');
  const { logout, user } = useAuthStore();

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
      <div className="sm:mx-auto sm:w-full sm:max-w-md">
        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
          <LockClosedIcon className="h-8 w-8 text-red-600" aria-hidden="true" />
        </div>
        <h2 className="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900">
          {t('subscription.lockedTitle', 'Abonelik Gerekli')}
        </h2>
        <p className="mt-2 text-center text-sm text-gray-600">
          {user?.company?.name && (
            <span className="font-medium text-gray-900">{user.company.name}</span>
          )}
        </p>
      </div>

      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div className="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
          <div className="space-y-6">
            <div className="text-center">
              <p className="text-sm text-gray-700">
                {t(
                  'subscription.lockedMessage',
                  'Aboneliğiniz ve grace döneminiz sona ermiştir. Panele erişim için aboneliğinizi yenilemeniz gerekmektedir.'
                )}
              </p>
            </div>

            <div className="border-t border-gray-200 pt-6">
              <h3 className="text-sm font-medium text-gray-900 mb-4">
                {t('subscription.contactSales', 'Satış Ekibi ile İletişim')}
              </h3>

              <div className="space-y-3">
                <a
                  href="mailto:sales@talentqx.com"
                  className="w-full flex items-center justify-center gap-2 px-4 py-3 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                  <EnvelopeIcon className="h-5 w-5" />
                  {t('subscription.emailSales', 'E-posta Gönder')}
                </a>

                <a
                  href="tel:+902121234567"
                  className="w-full flex items-center justify-center gap-2 px-4 py-3 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                  <PhoneIcon className="h-5 w-5" />
                  {t('subscription.callSales', 'Ara: +90 212 123 45 67')}
                </a>
              </div>
            </div>

            <div className="border-t border-gray-200 pt-6">
              <div className="bg-gray-50 rounded-md p-4">
                <h4 className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">
                  {t('subscription.dataNote', 'Veri Güvenliği')}
                </h4>
                <p className="text-xs text-gray-600">
                  {t(
                    'subscription.dataMessage',
                    'Verileriniz güvende tutulmaktadır. Abonelik yenilendikten sonra tüm verilerinize erişebilirsiniz.'
                  )}
                </p>
              </div>
            </div>

            <div className="border-t border-gray-200 pt-6">
              <button
                type="button"
                onClick={() => logout()}
                className="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                {t('auth.logout', 'Çıkış Yap')}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
