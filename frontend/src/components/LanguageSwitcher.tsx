import { Fragment } from 'react';
import { Menu, Transition } from '@headlessui/react';
import { GlobeAltIcon, ChevronDownIcon } from '@heroicons/react/24/outline';
import { useLanguage } from '../hooks/useLanguage';
import { LANGUAGE_NAMES, SupportedLanguage } from '../i18n';
import clsx from 'clsx';

interface LanguageSwitcherProps {
  /** Variant style */
  variant?: 'light' | 'dark';
  /** Show full language name or just code */
  showFullName?: boolean;
  /** Additional CSS classes */
  className?: string;
}

/**
 * Language switcher dropdown component
 */
export default function LanguageSwitcher({
  variant = 'light',
  showFullName = true,
  className = '',
}: LanguageSwitcherProps) {
  const { currentLanguage, changeLanguage, supportedLanguages } = useLanguage();

  const buttonStyles = {
    light: 'text-gray-700 hover:text-gray-900 hover:bg-gray-100',
    dark: 'text-white/80 hover:text-white hover:bg-white/10',
  };

  const menuStyles = {
    light: 'bg-white ring-1 ring-black/5',
    dark: 'bg-gray-800 ring-1 ring-white/10',
  };

  const itemStyles = {
    light: {
      active: 'bg-gray-100 text-gray-900',
      inactive: 'text-gray-700',
      selected: 'bg-primary-50 text-primary-700',
    },
    dark: {
      active: 'bg-gray-700 text-white',
      inactive: 'text-gray-300',
      selected: 'bg-primary-600/20 text-primary-400',
    },
  };

  return (
    <Menu as="div" className={clsx('relative inline-block text-left', className)}>
      <Menu.Button
        className={clsx(
          'inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
          buttonStyles[variant]
        )}
      >
        <GlobeAltIcon className="h-4 w-4" />
        {showFullName ? LANGUAGE_NAMES[currentLanguage] : currentLanguage.toUpperCase()}
        <ChevronDownIcon className="h-4 w-4" />
      </Menu.Button>

      <Transition
        as={Fragment}
        enter="transition ease-out duration-100"
        enterFrom="transform opacity-0 scale-95"
        enterTo="transform opacity-100 scale-100"
        leave="transition ease-in duration-75"
        leaveFrom="transform opacity-100 scale-100"
        leaveTo="transform opacity-0 scale-95"
      >
        <Menu.Items
          className={clsx(
            'absolute right-0 z-50 mt-2 w-40 origin-top-right rounded-lg shadow-lg focus:outline-none',
            menuStyles[variant]
          )}
        >
          <div className="py-1">
            {supportedLanguages.map((lang) => (
              <Menu.Item key={lang}>
                {({ active }) => (
                  <button
                    onClick={() => changeLanguage(lang as SupportedLanguage)}
                    className={clsx(
                      'w-full px-4 py-2 text-left text-sm flex items-center justify-between',
                      currentLanguage === lang
                        ? itemStyles[variant].selected
                        : active
                        ? itemStyles[variant].active
                        : itemStyles[variant].inactive
                    )}
                  >
                    <span>{LANGUAGE_NAMES[lang as SupportedLanguage]}</span>
                    <span className="text-xs opacity-60">{lang.toUpperCase()}</span>
                  </button>
                )}
              </Menu.Item>
            ))}
          </div>
        </Menu.Items>
      </Transition>
    </Menu>
  );
}
