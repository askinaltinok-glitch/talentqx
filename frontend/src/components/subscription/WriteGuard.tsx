import { ReactNode } from 'react';
import { useSubscriptionStore } from '../../stores/subscriptionStore';
import { useTranslation } from 'react-i18next';

interface WriteGuardProps {
  children: ReactNode;
  /** Custom tooltip message */
  tooltip?: string;
  /** If true, renders children as disabled instead of hiding */
  showDisabled?: boolean;
  /** Wrapper element type */
  as?: 'div' | 'span';
}

/**
 * WriteGuard wraps buttons/actions and disables them during grace period
 * - FULL state: renders children normally
 * - READ_ONLY_EXPORT state: renders children with disabled state and tooltip
 * - LOCKED state: handled by SubscriptionProvider (shows locked page)
 */
export default function WriteGuard({
  children,
  tooltip,
  showDisabled = true,
  as: Wrapper = 'span',
}: WriteGuardProps) {
  const { t } = useTranslation('common');
  const { canWrite } = useSubscriptionStore();

  const isAllowed = canWrite();

  if (isAllowed) {
    return <>{children}</>;
  }

  if (!showDisabled) {
    return null;
  }

  const defaultTooltip = t(
    'subscription.writeDisabledTooltip',
    'Grace döneminde bu işlem yapılamaz. Yalnızca görüntüleme ve dışa aktarma işlemleri aktif.'
  );

  return (
    <Wrapper
      className="inline-block cursor-not-allowed"
      title={tooltip || defaultTooltip}
    >
      <div className="pointer-events-none opacity-50">{children}</div>
    </Wrapper>
  );
}

/**
 * Hook to check if write operations are allowed
 */
export function useCanWrite(): boolean {
  const { canWrite } = useSubscriptionStore();
  return canWrite();
}
