import { Fragment, useState } from 'react';
import { Menu, Transition } from '@headlessui/react';
import { useTranslation } from 'react-i18next';
import toast from 'react-hot-toast';
import {
  ArrowDownTrayIcon,
  DocumentTextIcon,
  TableCellsIcon,
  CodeBracketIcon,
  ChevronDownIcon,
} from '@heroicons/react/24/outline';
import api from '../services/api';
import type { ExportResponse } from '../types';

interface ExportDropdownProps {
  /** Candidate ID to export */
  candidateId: string;
  /** Optional custom label */
  label?: string;
  /** Button size variant */
  size?: 'sm' | 'md';
}

type ExportFormat = 'pdf' | 'csv' | 'json';

const formatIcons: Record<ExportFormat, typeof DocumentTextIcon> = {
  pdf: DocumentTextIcon,
  csv: TableCellsIcon,
  json: CodeBracketIcon,
};

/**
 * ExportDropdown provides PDF/CSV/JSON export options for candidate data
 * Works during both active subscription and grace period
 */
export default function ExportDropdown({
  candidateId,
  label,
  size = 'md',
}: ExportDropdownProps) {
  const { t } = useTranslation('common');
  const [isExporting, setIsExporting] = useState<ExportFormat | null>(null);

  const handleExport = async (format: ExportFormat) => {
    setIsExporting(format);

    try {
      const response = await api.get<ExportResponse>(
        `/candidates/${candidateId}/export`,
        { format }
      );

      if (response.download_url) {
        // Open download URL in new tab
        window.open(response.download_url, '_blank');

        toast.success(
          t('export.success', 'Rapor hazırlandı'),
          {
            duration: 3000,
            icon: '📥',
          }
        );

        // Show expiry note if available
        if (response.expires_at) {
          const expiryDate = new Date(response.expires_at);
          const hours = Math.round(
            (expiryDate.getTime() - Date.now()) / (1000 * 60 * 60)
          );
          if (hours > 0) {
            toast(
              t('export.expiryNote', 'Link {{hours}} saat geçerli', { hours }),
              { icon: 'ℹ️', duration: 4000 }
            );
          }
        }
      } else if (format === 'json') {
        // JSON format returns data directly
        toast.success(t('export.jsonReady', 'JSON verisi hazır'));
      }
    } catch (error) {
      const errorHandler = (
        window as unknown as { __apiErrorHandler?: (error: unknown) => void }
      ).__apiErrorHandler;
      if (errorHandler) {
        errorHandler(error);
      } else {
        toast.error(t('export.error', 'Dışa aktarma başarısız'));
      }
    } finally {
      setIsExporting(null);
    }
  };

  const buttonSizeClasses = size === 'sm'
    ? 'px-2.5 py-1.5 text-xs'
    : 'px-3 py-2 text-sm';

  const formats: { value: ExportFormat; label: string }[] = [
    { value: 'pdf', label: 'PDF' },
    { value: 'csv', label: 'CSV' },
    { value: 'json', label: 'JSON' },
  ];

  return (
    <Menu as="div" className="relative inline-block text-left">
      <div>
        <Menu.Button
          className={`inline-flex items-center justify-center gap-x-1.5 rounded-md bg-white font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 ${buttonSizeClasses}`}
          disabled={isExporting !== null}
        >
          {isExporting ? (
            <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-indigo-600" />
          ) : (
            <ArrowDownTrayIcon className="h-4 w-4 text-gray-500" aria-hidden="true" />
          )}
          {label || t('export.button', 'Dışa Aktar')}
          <ChevronDownIcon className="h-4 w-4 text-gray-400" aria-hidden="true" />
        </Menu.Button>
      </div>

      <Transition
        as={Fragment}
        enter="transition ease-out duration-100"
        enterFrom="transform opacity-0 scale-95"
        enterTo="transform opacity-100 scale-100"
        leave="transition ease-in duration-75"
        leaveFrom="transform opacity-100 scale-100"
        leaveTo="transform opacity-0 scale-95"
      >
        <Menu.Items className="absolute right-0 z-10 mt-2 w-40 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
          <div className="py-1">
            {formats.map(({ value, label: formatLabel }) => {
              const Icon = formatIcons[value];
              const isLoading = isExporting === value;

              return (
                <Menu.Item key={value}>
                  {({ active }) => (
                    <button
                      onClick={() => handleExport(value)}
                      disabled={isExporting !== null}
                      className={`${
                        active ? 'bg-gray-100 text-gray-900' : 'text-gray-700'
                      } group flex w-full items-center px-4 py-2 text-sm disabled:opacity-50`}
                    >
                      {isLoading ? (
                        <div className="mr-3 h-5 w-5 animate-spin rounded-full border-b-2 border-indigo-600" />
                      ) : (
                        <Icon
                          className="mr-3 h-5 w-5 text-gray-400 group-hover:text-gray-500"
                          aria-hidden="true"
                        />
                      )}
                      {formatLabel}
                    </button>
                  )}
                </Menu.Item>
              );
            })}
          </div>
        </Menu.Items>
      </Transition>
    </Menu>
  );
}
