import { SparklesIcon } from '@heroicons/react/24/outline';
import { useCopilotStore } from '../../stores/copilotStore';
import type { CopilotContext } from '../../types';

interface CopilotButtonProps {
  context?: CopilotContext;
  variant?: 'primary' | 'secondary' | 'ghost';
  size?: 'sm' | 'md' | 'lg';
  label?: string;
  className?: string;
}

export default function CopilotButton({
  context,
  variant = 'primary',
  size = 'md',
  label = 'Copilot',
  className = '',
}: CopilotButtonProps) {
  const { openDrawer, setContext } = useCopilotStore();

  const handleClick = () => {
    if (context) {
      setContext(context);
    }
    openDrawer(context);
  };

  const baseClasses = 'inline-flex items-center gap-2 font-medium rounded-lg transition-all';

  const sizeClasses = {
    sm: 'px-2.5 py-1.5 text-xs',
    md: 'px-3 py-2 text-sm',
    lg: 'px-4 py-2.5 text-base',
  };

  const variantClasses = {
    primary: 'bg-gradient-to-r from-primary-600 to-indigo-600 text-white hover:from-primary-700 hover:to-indigo-700 shadow-sm hover:shadow',
    secondary: 'bg-white text-primary-600 border border-primary-200 hover:bg-primary-50 hover:border-primary-300',
    ghost: 'text-primary-600 hover:bg-primary-50',
  };

  const iconSizes = {
    sm: 'w-3.5 h-3.5',
    md: 'w-4 h-4',
    lg: 'w-5 h-5',
  };

  return (
    <button
      onClick={handleClick}
      className={`${baseClasses} ${sizeClasses[size]} ${variantClasses[variant]} ${className}`}
    >
      <SparklesIcon className={iconSizes[size]} />
      {label}
    </button>
  );
}
