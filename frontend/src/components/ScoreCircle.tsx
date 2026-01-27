import clsx from 'clsx';

interface ScoreCircleProps {
  score: number;
  size?: 'sm' | 'md' | 'lg';
  showLabel?: boolean;
  label?: string;
}

export default function ScoreCircle({
  score,
  size = 'md',
  showLabel = false,
  label,
}: ScoreCircleProps) {
  const sizeClasses = {
    sm: { container: 'w-12 h-12', text: 'text-sm', stroke: 3 },
    md: { container: 'w-20 h-20', text: 'text-xl', stroke: 4 },
    lg: { container: 'w-28 h-28', text: 'text-2xl', stroke: 5 },
  };

  const getColor = (score: number) => {
    if (score >= 80) return { stroke: '#22c55e', text: 'text-green-600' };
    if (score >= 60) return { stroke: '#3b82f6', text: 'text-blue-600' };
    if (score >= 40) return { stroke: '#f59e0b', text: 'text-yellow-600' };
    return { stroke: '#ef4444', text: 'text-red-600' };
  };

  const { stroke: strokeColor, text: textColor } = getColor(score);
  const { container, text, stroke } = sizeClasses[size];

  const radius = 40;
  const circumference = 2 * Math.PI * radius;
  const progress = ((100 - score) / 100) * circumference;

  return (
    <div className="flex flex-col items-center">
      <div className={clsx('score-circle', container)}>
        <svg viewBox="0 0 100 100" className="w-full h-full">
          <circle
            cx="50"
            cy="50"
            r={radius}
            fill="none"
            stroke="#e5e7eb"
            strokeWidth={stroke}
          />
          <circle
            cx="50"
            cy="50"
            r={radius}
            fill="none"
            stroke={strokeColor}
            strokeWidth={stroke}
            strokeLinecap="round"
            strokeDasharray={circumference}
            strokeDashoffset={progress}
            style={{ transition: 'stroke-dashoffset 0.5s ease' }}
          />
        </svg>
        <span className={clsx('score-value font-bold', text, textColor)}>
          %{Math.round(score)}
        </span>
      </div>
      {showLabel && label && (
        <span className="mt-2 text-xs text-gray-500 text-center">{label}</span>
      )}
    </div>
  );
}
