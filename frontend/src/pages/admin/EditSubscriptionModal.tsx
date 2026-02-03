import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import {
  XMarkIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
  GlobeAltIcon,
} from '@heroicons/react/24/outline';
import api from '../../services/api';
import toast from 'react-hot-toast';

interface ComputedStatus {
  status: 'active' | 'grace_period' | 'expired';
  is_active: boolean;
  is_in_grace_period: boolean;
  has_marketplace_access: boolean;
}

interface Company {
  id: string;
  name: string;
  slug: string;
  subscription_plan: string | null;
  is_premium: boolean;
  subscription_ends_at: string | null;
  grace_period_ends_at: string | null;
  computed_status: ComputedStatus;
  updated_at: string;
}

interface Props {
  company: Company;
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
}

const PLANS = ['free', 'starter', 'pro', 'enterprise'];

export default function EditSubscriptionModal({ company, isOpen, onClose, onSuccess }: Props) {
  const { t } = useTranslation('common');
  const [loading, setLoading] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);

  // Form state
  const [plan, setPlan] = useState(company.subscription_plan || '');
  const [isPremium, setIsPremium] = useState(company.is_premium);
  const [subscriptionEndsAt, setSubscriptionEndsAt] = useState(
    company.subscription_ends_at ? company.subscription_ends_at.slice(0, 16) : ''
  );
  const [noEndDate, setNoEndDate] = useState(!company.subscription_ends_at);
  const [graceEndsAt, setGraceEndsAt] = useState(
    company.grace_period_ends_at ? company.grace_period_ends_at.slice(0, 16) : ''
  );
  const [autoGrace, setAutoGrace] = useState(!company.grace_period_ends_at);

  // Reset form when company changes
  useEffect(() => {
    setPlan(company.subscription_plan || '');
    setIsPremium(company.is_premium);
    setSubscriptionEndsAt(company.subscription_ends_at ? company.subscription_ends_at.slice(0, 16) : '');
    setNoEndDate(!company.subscription_ends_at);
    setGraceEndsAt(company.grace_period_ends_at ? company.grace_period_ends_at.slice(0, 16) : '');
    setAutoGrace(!company.grace_period_ends_at);
  }, [company]);

  // Compute live status preview
  const computePreviewStatus = (): ComputedStatus => {
    const now = new Date();
    const subEnd = noEndDate ? null : subscriptionEndsAt ? new Date(subscriptionEndsAt) : null;
    const graceEnd = autoGrace
      ? subEnd
        ? new Date(subEnd.getTime() + 60 * 24 * 60 * 60 * 1000) // 60 days after sub end
        : null
      : graceEndsAt
      ? new Date(graceEndsAt)
      : null;

    const isActive = subEnd === null || subEnd > now;
    const isInGrace = !isActive && graceEnd !== null && graceEnd > now;
    const status = isActive ? 'active' : isInGrace ? 'grace_period' : 'expired';
    const hasMarketplace = isPremium && isActive;

    return {
      status,
      is_active: isActive,
      is_in_grace_period: isInGrace,
      has_marketplace_access: hasMarketplace,
    };
  };

  const previewStatus = computePreviewStatus();

  const handleSave = async () => {
    setLoading(true);
    try {
      const payload: Record<string, unknown> = {
        subscription_plan: plan || null,
        is_premium: isPremium,
        subscription_ends_at: noEndDate ? null : subscriptionEndsAt || null,
        grace_period_ends_at: autoGrace ? null : graceEndsAt || null,
      };

      await api.patch(`/admin/companies/${company.id}/subscription`, payload);
      toast.success(t('admin.companies.updateSuccess', 'Subscription updated successfully'));
      onSuccess();
    } catch (error) {
      toast.error(t('admin.companies.updateError', 'Failed to update subscription'));
    } finally {
      setLoading(false);
      setShowConfirm(false);
    }
  };

  if (!isOpen) return null;

  const getStatusIcon = (status: ComputedStatus['status']) => {
    switch (status) {
      case 'active':
        return <CheckCircleIcon className="h-5 w-5 text-green-500" />;
      case 'grace_period':
        return <ExclamationTriangleIcon className="h-5 w-5 text-yellow-500" />;
      case 'expired':
        return <XCircleIcon className="h-5 w-5 text-red-500" />;
    }
  };

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="flex min-h-full items-center justify-center p-4">
        {/* Backdrop */}
        <div className="fixed inset-0 bg-gray-900/50" onClick={onClose} />

        {/* Modal */}
        <div className="relative bg-white rounded-xl shadow-xl max-w-lg w-full">
          {/* Header */}
          <div className="flex items-center justify-between px-6 py-4 border-b">
            <div>
              <h2 className="text-lg font-semibold text-gray-900">
                {t('admin.companies.editTitle', 'Edit Subscription')}
              </h2>
              <p className="text-sm text-gray-500">{company.name}</p>
            </div>
            <button
              onClick={onClose}
              className="p-2 text-gray-400 hover:text-gray-600 rounded-lg"
            >
              <XMarkIcon className="h-5 w-5" />
            </button>
          </div>

          {/* Body */}
          <div className="px-6 py-4 space-y-4">
            {/* Plan */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('admin.companies.fieldPlan', 'Plan')}
              </label>
              <select
                value={plan}
                onChange={(e) => setPlan(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
              >
                <option value="">None</option>
                {PLANS.map((p) => (
                  <option key={p} value={p}>
                    {p.charAt(0).toUpperCase() + p.slice(1)}
                  </option>
                ))}
              </select>
            </div>

            {/* Premium */}
            <div className="flex items-center justify-between">
              <div>
                <label className="block text-sm font-medium text-gray-700">
                  {t('admin.companies.fieldPremium', 'Premium')}
                </label>
                <p className="text-xs text-gray-500">
                  {t('admin.companies.premiumHint', 'Enables marketplace access')}
                </p>
              </div>
              <button
                type="button"
                onClick={() => setIsPremium(!isPremium)}
                className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                  isPremium ? 'bg-primary-600' : 'bg-gray-200'
                }`}
              >
                <span
                  className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                    isPremium ? 'translate-x-6' : 'translate-x-1'
                  }`}
                />
              </button>
            </div>

            {/* Subscription Ends At */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('admin.companies.fieldSubEnds', 'Subscription Ends At')}
              </label>
              <div className="flex items-center gap-3">
                <label className="flex items-center gap-2 text-sm text-gray-600">
                  <input
                    type="checkbox"
                    checked={noEndDate}
                    onChange={(e) => setNoEndDate(e.target.checked)}
                    className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                  />
                  {t('admin.companies.noEndDate', 'No end date (unlimited)')}
                </label>
              </div>
              {!noEndDate && (
                <input
                  type="datetime-local"
                  value={subscriptionEndsAt}
                  onChange={(e) => setSubscriptionEndsAt(e.target.value)}
                  className="mt-2 w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                />
              )}
            </div>

            {/* Grace Period Ends At */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('admin.companies.fieldGraceEnds', 'Grace Period Ends At')}
              </label>
              <div className="flex items-center gap-3">
                <label className="flex items-center gap-2 text-sm text-gray-600">
                  <input
                    type="checkbox"
                    checked={autoGrace}
                    onChange={(e) => setAutoGrace(e.target.checked)}
                    className="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                  />
                  {t('admin.companies.autoGrace', 'Auto (60 days after subscription ends)')}
                </label>
              </div>
              {!autoGrace && (
                <input
                  type="datetime-local"
                  value={graceEndsAt}
                  onChange={(e) => setGraceEndsAt(e.target.value)}
                  className="mt-2 w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                />
              )}
            </div>

            {/* Live Preview */}
            <div className="bg-gray-50 rounded-lg p-4 space-y-3">
              <h3 className="text-sm font-medium text-gray-700">
                {t('admin.companies.livePreview', 'Live Preview')}
              </h3>
              <div className="flex items-center gap-3">
                {getStatusIcon(previewStatus.status)}
                <div>
                  <p className="text-sm font-medium text-gray-900">
                    {t(`admin.companies.status.${previewStatus.status}`, previewStatus.status)}
                  </p>
                  <p className="text-xs text-gray-500">
                    {previewStatus.is_active
                      ? t('admin.companies.statusActiveDesc', 'Full access to all features')
                      : previewStatus.is_in_grace_period
                      ? t('admin.companies.statusGraceDesc', 'Export only, limited access')
                      : t('admin.companies.statusExpiredDesc', 'No access, subscription locked')}
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-3">
                <GlobeAltIcon
                  className={`h-5 w-5 ${
                    previewStatus.has_marketplace_access ? 'text-purple-500' : 'text-gray-300'
                  }`}
                />
                <div>
                  <p className="text-sm font-medium text-gray-900">
                    {t('admin.companies.marketplaceAccess', 'Marketplace Access')}
                  </p>
                  <p className="text-xs text-gray-500">
                    {previewStatus.has_marketplace_access
                      ? t('admin.companies.marketplaceYes', 'Enabled (Premium + Active)')
                      : t('admin.companies.marketplaceNo', 'Disabled')}
                  </p>
                </div>
              </div>
            </div>
          </div>

          {/* Footer */}
          <div className="flex items-center justify-end gap-3 px-6 py-4 border-t bg-gray-50 rounded-b-xl">
            <button
              onClick={onClose}
              className="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
              {t('common.cancel', 'Cancel')}
            </button>
            <button
              onClick={() => setShowConfirm(true)}
              disabled={loading}
              className="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors disabled:opacity-50"
            >
              {loading ? t('common.saving', 'Saving...') : t('common.save', 'Save')}
            </button>
          </div>

          {/* Confirm Dialog */}
          {showConfirm && (
            <div className="absolute inset-0 bg-white/95 rounded-xl flex items-center justify-center p-6">
              <div className="text-center">
                <ExclamationTriangleIcon className="h-12 w-12 text-yellow-500 mx-auto mb-4" />
                <h3 className="text-lg font-semibold text-gray-900 mb-2">
                  {t('admin.companies.confirmTitle', 'Confirm Changes')}
                </h3>
                <p className="text-sm text-gray-500 mb-6">
                  {t(
                    'admin.companies.confirmMessage',
                    "This change will affect the company's access to the platform."
                  )}
                </p>
                <div className="flex items-center justify-center gap-3">
                  <button
                    onClick={() => setShowConfirm(false)}
                    className="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                  >
                    {t('common.cancel', 'Cancel')}
                  </button>
                  <button
                    onClick={handleSave}
                    disabled={loading}
                    className="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors disabled:opacity-50"
                  >
                    {loading ? t('common.saving', 'Saving...') : t('common.confirm', 'Confirm')}
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
