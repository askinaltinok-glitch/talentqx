import { useState, useRef, useCallback } from 'react';
import {
  XMarkIcon,
  ArrowUpTrayIcon,
  ArrowDownTrayIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  DocumentTextIcon,
} from '@heroicons/react/24/outline';
import { useTranslation } from 'react-i18next';
import api from '../services/api';

interface BulkUploadResult {
  imported_count: number;
  skipped_count: number;
  errors: Array<{
    row: number;
    reason: string;
  }>;
}

interface BulkUploadModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
}

export default function BulkUploadModal({ isOpen, onClose, onSuccess }: BulkUploadModalProps) {
  const { t } = useTranslation('common');
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [isUploading, setIsUploading] = useState(false);
  const [result, setResult] = useState<BulkUploadResult | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isDragging, setIsDragging] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleFileSelect = (file: File) => {
    // Validate file type
    if (!file.name.toLowerCase().endsWith('.csv')) {
      setError(t('bulkUpload.invalidFileType'));
      return;
    }
    // Validate file size (max 2MB)
    if (file.size > 2 * 1024 * 1024) {
      setError(t('bulkUpload.fileTooLarge'));
      return;
    }
    setSelectedFile(file);
    setError(null);
    setResult(null);
  };

  const handleDragOver = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(true);
  }, []);

  const handleDragLeave = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);
  }, []);

  const handleDrop = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    setIsDragging(false);
    const file = e.dataTransfer.files[0];
    if (file) {
      handleFileSelect(file);
    }
  }, [t]);

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      handleFileSelect(file);
    }
  };

  const handleUpload = async () => {
    if (!selectedFile) return;

    setIsUploading(true);
    setError(null);

    try {
      const formData = new FormData();
      formData.append('file', selectedFile);

      const response = await api.upload<BulkUploadResult>('/employees/bulk-import', formData);
      setResult(response);

      if (response.imported_count > 0) {
        onSuccess();
      }
    } catch (err) {
      setError(api.getErrorMessage(err));
    } finally {
      setIsUploading(false);
    }
  };

  const handleDownloadSample = () => {
    const csvContent = `first_name,last_name,phone,email,role,store,notes
Ahmet,Yilmaz,05551234567,ahmet@example.com,Satis Temsilcisi,Istanbul Kadikoy,Deneyimli calisan
Mehmet,Demir,05559876543,mehmet@example.com,Magaza Muduru,Ankara Kizilay,
Ayse,Kaya,05552223344,ayse@example.com,Kasiyer,Izmir Alsancak,Yari zamanli`;

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'calisan_sablonu.csv';
    link.click();
    URL.revokeObjectURL(link.href);
  };

  const handleClose = () => {
    setSelectedFile(null);
    setResult(null);
    setError(null);
    onClose();
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200">
          <h2 className="text-xl font-semibold text-gray-900">
            {t('bulkUpload.title')}
          </h2>
          <button
            onClick={handleClose}
            className="text-gray-400 hover:text-gray-500 transition-colors"
          >
            <XMarkIcon className="h-6 w-6" />
          </button>
        </div>

        {/* Body */}
        <div className="p-6 space-y-6">
          {/* Instructions */}
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 className="text-sm font-medium text-blue-800 mb-2">
              {t('bulkUpload.instructions')}
            </h3>
            <ul className="text-sm text-blue-700 space-y-1 list-disc list-inside">
              <li>{t('bulkUpload.columnInfo')}</li>
              <li>{t('bulkUpload.requiredFields')}</li>
              <li>{t('bulkUpload.duplicateSkip')}</li>
            </ul>
          </div>

          {/* Sample Download */}
          <button
            onClick={handleDownloadSample}
            className="flex items-center gap-2 text-sm text-primary-600 hover:text-primary-700 transition-colors"
          >
            <ArrowDownTrayIcon className="h-5 w-5" />
            {t('bulkUpload.downloadSample')}
          </button>

          {/* Drop Zone */}
          <div
            onDragOver={handleDragOver}
            onDragLeave={handleDragLeave}
            onDrop={handleDrop}
            className={`border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors ${
              isDragging
                ? 'border-primary-500 bg-primary-50'
                : selectedFile
                ? 'border-green-500 bg-green-50'
                : 'border-gray-300 hover:border-gray-400'
            }`}
            onClick={() => fileInputRef.current?.click()}
          >
            <input
              ref={fileInputRef}
              type="file"
              accept=".csv"
              onChange={handleInputChange}
              className="hidden"
            />
            {selectedFile ? (
              <div className="flex flex-col items-center">
                <DocumentTextIcon className="h-12 w-12 text-green-500 mb-3" />
                <p className="text-sm font-medium text-gray-900">{selectedFile.name}</p>
                <p className="text-xs text-gray-500 mt-1">
                  {(selectedFile.size / 1024).toFixed(1)} KB
                </p>
              </div>
            ) : (
              <div className="flex flex-col items-center">
                <ArrowUpTrayIcon className="h-12 w-12 text-gray-400 mb-3" />
                <p className="text-sm text-gray-600">
                  {t('bulkUpload.dropZoneText')}
                </p>
                <p className="text-xs text-gray-400 mt-1">
                  {t('bulkUpload.maxFileSize')}
                </p>
              </div>
            )}
          </div>

          {/* Error Message */}
          {error && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-4 flex items-start gap-3">
              <ExclamationCircleIcon className="h-5 w-5 text-red-500 flex-shrink-0 mt-0.5" />
              <p className="text-sm text-red-700">{error}</p>
            </div>
          )}

          {/* Result */}
          {result && (
            <div className="space-y-4">
              <div className="bg-green-50 border border-green-200 rounded-lg p-4 flex items-start gap-3">
                <CheckCircleIcon className="h-5 w-5 text-green-500 flex-shrink-0 mt-0.5" />
                <div>
                  <p className="text-sm font-medium text-green-800">
                    {t('bulkUpload.successMessage', { count: result.imported_count })}
                  </p>
                  {result.skipped_count > 0 && (
                    <p className="text-sm text-green-700 mt-1">
                      {t('bulkUpload.skippedMessage', { count: result.skipped_count })}
                    </p>
                  )}
                </div>
              </div>

              {/* Error Details */}
              {result.errors.length > 0 && (
                <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                  <h4 className="text-sm font-medium text-yellow-800 mb-2">
                    {t('bulkUpload.errorDetails')}
                  </h4>
                  <div className="max-h-32 overflow-y-auto">
                    <ul className="text-sm text-yellow-700 space-y-1">
                      {result.errors.slice(0, 10).map((err, idx) => (
                        <li key={idx}>
                          {t('bulkUpload.rowError', { row: err.row })}: {err.reason}
                        </li>
                      ))}
                      {result.errors.length > 10 && (
                        <li className="font-medium">
                          {t('bulkUpload.moreErrors', { count: result.errors.length - 10 })}
                        </li>
                      )}
                    </ul>
                  </div>
                </div>
              )}
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="flex items-center justify-end gap-3 p-6 border-t border-gray-200">
          <button
            onClick={handleClose}
            className="btn-secondary"
          >
            {result ? t('buttons.close') : t('buttons.cancel')}
          </button>
          {!result && (
            <button
              onClick={handleUpload}
              disabled={!selectedFile || isUploading}
              className="btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isUploading ? (
                <>
                  <span className="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent mr-2"></span>
                  {t('bulkUpload.uploading')}
                </>
              ) : (
                <>
                  <ArrowUpTrayIcon className="h-5 w-5 mr-2" />
                  {t('bulkUpload.uploadButton')}
                </>
              )}
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
