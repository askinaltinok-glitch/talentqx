import clsx from 'clsx';
import type { CandidateStatus } from '../types';

interface StatusBadgeProps {
  status: CandidateStatus | string;
  size?: 'sm' | 'md';
}

const statusConfig: Record<
  string,
  { label: string; color: string }
> = {
  applied: { label: 'Basvurdu', color: 'badge-gray' },
  interview_pending: { label: 'Mulakat Bekliyor', color: 'badge-yellow' },
  interview_completed: { label: 'Mulakat Tamamlandi', color: 'badge-blue' },
  under_review: { label: 'Incelemede', color: 'badge-blue' },
  shortlisted: { label: 'Kisa Liste', color: 'badge-green' },
  hired: { label: 'Ise Alindi', color: 'badge-green' },
  rejected: { label: 'Reddedildi', color: 'badge-red' },
  draft: { label: 'Taslak', color: 'badge-gray' },
  active: { label: 'Aktif', color: 'badge-green' },
  paused: { label: 'Durduruldu', color: 'badge-yellow' },
  closed: { label: 'Kapali', color: 'badge-red' },
  pending: { label: 'Bekliyor', color: 'badge-yellow' },
  in_progress: { label: 'Devam Ediyor', color: 'badge-blue' },
  completed: { label: 'Tamamlandi', color: 'badge-green' },
  expired: { label: 'Suresi Doldu', color: 'badge-red' },
  cancelled: { label: 'Iptal', color: 'badge-red' },
};

export default function StatusBadge({ status, size = 'md' }: StatusBadgeProps) {
  const config = statusConfig[status] || {
    label: status,
    color: 'badge-gray',
  };

  return (
    <span
      className={clsx(
        config.color,
        size === 'sm' && 'text-xs px-2 py-0.5'
      )}
    >
      {config.label}
    </span>
  );
}
