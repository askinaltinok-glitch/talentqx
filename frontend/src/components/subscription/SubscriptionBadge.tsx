import { useSubscriptionStore } from '../../stores/subscriptionStore';
import { useTranslation } from 'react-i18next';
import { CheckCircleIcon, ClockIcon, XCircleIcon } from '@heroicons/react/24/outline';

/**
 * SubscriptionBadge displays subscription status in the header
 * - Active: Green badge "Aktif"
 * - Grace: Yellow badge "Grace – X gün (Export-only)"
 * - Expired: Red badge "Sona Erdi"
 */
export default function SubscriptionBadge() {
  const { t } = useTranslation('common');
  const { state, getGraceDaysRemaining } = useSubscriptionStore();

  if (state === 'FULL') {
    return (
      <span className="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
        <CheckCircleIcon className="h-3.5 w-3.5" />
        {t('subscription.active', 'Aktif')}
      </span>
    );
  }

  if (state === 'READ_ONLY_EXPORT') {
    const daysRemaining = getGraceDaysRemaining();
    return (
      <span className="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
        <ClockIcon className="h-3.5 w-3.5" />
        {t('subscription.grace', 'Grace')} – {daysRemaining} {t('subscription.days', 'gün')}
        <span className="text-yellow-600">({t('subscription.exportOnly', 'Export-only')})</span>
      </span>
    );
  }

  return (
    <span className="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
      <XCircleIcon className="h-3.5 w-3.5" />
      {t('subscription.expired', 'Sona Erdi')}
    </span>
  );
}
