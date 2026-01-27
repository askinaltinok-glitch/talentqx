import clsx from 'clsx';
import {
  CheckCircleIcon,
  PauseCircleIcon,
  XCircleIcon,
} from '@heroicons/react/24/solid';

interface RecommendationBadgeProps {
  recommendation: 'hire' | 'hold' | 'reject' | string;
  confidence?: number;
  size?: 'sm' | 'md' | 'lg';
}

const config = {
  hire: {
    label: 'Ise Al',
    color: 'bg-green-100 text-green-800 border-green-200',
    icon: CheckCircleIcon,
    iconColor: 'text-green-500',
  },
  hold: {
    label: 'Beklet',
    color: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    icon: PauseCircleIcon,
    iconColor: 'text-yellow-500',
  },
  reject: {
    label: 'Reddet',
    color: 'bg-red-100 text-red-800 border-red-200',
    icon: XCircleIcon,
    iconColor: 'text-red-500',
  },
};

export default function RecommendationBadge({
  recommendation,
  confidence,
  size = 'md',
}: RecommendationBadgeProps) {
  const rec = config[recommendation as keyof typeof config] || config.hold;
  const Icon = rec.icon;

  const sizeClasses = {
    sm: 'px-2 py-1 text-xs',
    md: 'px-3 py-1.5 text-sm',
    lg: 'px-4 py-2 text-base',
  };

  const iconSizes = {
    sm: 'h-4 w-4',
    md: 'h-5 w-5',
    lg: 'h-6 w-6',
  };

  return (
    <span
      className={clsx(
        'inline-flex items-center gap-1.5 rounded-lg font-medium border',
        rec.color,
        sizeClasses[size]
      )}
    >
      <Icon className={clsx(iconSizes[size], rec.iconColor)} />
      {rec.label}
      {confidence !== undefined && (
        <span className="text-xs opacity-75">(%{confidence})</span>
      )}
    </span>
  );
}
