import { useSubscriptionStore } from '../../stores/subscriptionStore';
import { useTranslation } from 'react-i18next';
import { ExclamationTriangleIcon } from '@heroicons/react/24/outline';

/**
 * GraceBanner displays a warning banner at the top of pages during grace period
 * Shows the grace period end date and reminds user that only exports are allowed
 */
export default function GraceBanner() {
  const { t } = useTranslation('common');
  const { state, status } = useSubscriptionStore();

  // Only show during grace period
  if (state !== 'READ_ONLY_EXPORT') {
    return null;
  }

  const graceEndDate = status?.grace_period_ends_at
    ? new Date(status.grace_period_ends_at).toLocaleDateString('tr-TR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
      })
    : null;

  return (
    <div className="bg-yellow-50 border-b border-yellow-200">
      <div className="max-w-7xl mx-auto py-3 px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between flex-wrap gap-2">
          <div className="flex items-center">
            <span className="flex p-2 rounded-lg bg-yellow-100">
              <ExclamationTriangleIcon className="h-5 w-5 text-yellow-600" aria-hidden="true" />
            </span>
            <p className="ml-3 font-medium text-yellow-700">
              <span>
                {t(
                  'subscription.graceBannerMessage',
                  'Aboneliğiniz sona erdi. Grace döneminde yalnızca verileri görüntüleyebilir ve dışa aktarabilirsiniz.'
                )}
              </span>
              {graceEndDate && (
                <span className="ml-2 text-yellow-800 font-semibold">
                  {t('subscription.graceBannerExpiry', 'Son tarih: {{date}}', { date: graceEndDate })}
                </span>
              )}
            </p>
          </div>
          <div className="flex-shrink-0">
            <a
              href="mailto:sales@talentqx.com"
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-yellow-800 bg-yellow-200 hover:bg-yellow-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
            >
              {t('subscription.renewNow', 'Paketi Yenile')}
            </a>
          </div>
        </div>
      </div>
    </div>
  );
}
