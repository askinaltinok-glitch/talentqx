import { useState, useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import { XMarkIcon, ShieldCheckIcon } from '@heroicons/react/24/outline';

interface PolicySection {
  title: string;
  content: string;
}

interface PolicyContent {
  title: string;
  version: string;
  last_updated: string;
  sections: PolicySection[];
}

interface PrivacyMeta {
  regime: 'KVKK' | 'GDPR' | 'GLOBAL';
  locale: string;
  country: string;
  policy_version: string;
  regime_info: {
    name: string;
    full_name: string;
    authority: string;
    authority_url: string | null;
  };
}

interface PrivacyModalProps {
  isOpen: boolean;
  onClose: () => void;
  onAccept?: () => void;
  requireScrollToEnd?: boolean;
  privacyMeta?: PrivacyMeta | null;
}

const API_BASE = import.meta.env.VITE_API_URL || 'https://talentqx.com/api/v1';

export default function PrivacyModal({
  isOpen,
  onClose,
  onAccept,
  requireScrollToEnd = false,
  privacyMeta,
}: PrivacyModalProps) {
  const { t, i18n } = useTranslation('common');
  const [policy, setPolicy] = useState<PolicyContent | null>(null);
  const [loading, setLoading] = useState(true);
  const [hasScrolledToEnd, setHasScrolledToEnd] = useState(!requireScrollToEnd);
  const contentRef = useRef<HTMLDivElement>(null);

  const regime = privacyMeta?.regime || 'GLOBAL';
  const locale = i18n.language?.slice(0, 2) || 'en';

  // Fetch policy content when modal opens
  useEffect(() => {
    if (!isOpen) return;

    setLoading(true);
    fetch(`${API_BASE}/privacy/policy/${regime}/${locale}`)
      .then(res => res.json())
      .then(data => {
        if (data.success && data.data?.content) {
          setPolicy(data.data.content);
        }
      })
      .catch(() => {
        // Fallback to default content
        setPolicy(null);
      })
      .finally(() => {
        setLoading(false);
      });
  }, [isOpen, regime, locale]);

  // Handle scroll tracking
  const handleScroll = () => {
    if (!requireScrollToEnd || hasScrolledToEnd) return;

    const content = contentRef.current;
    if (!content) return;

    const scrolledToBottom =
      content.scrollHeight - content.scrollTop <= content.clientHeight + 50;

    if (scrolledToBottom) {
      setHasScrolledToEnd(true);
    }
  };

  // Reset scroll state when modal closes
  useEffect(() => {
    if (!isOpen) {
      setHasScrolledToEnd(!requireScrollToEnd);
    }
  }, [isOpen, requireScrollToEnd]);

  if (!isOpen) return null;

  const regimeLabels = {
    KVKK: { name: 'KVKK', fullName: 'Kisisel Verilerin Korunmasi Kanunu' },
    GDPR: { name: 'GDPR', fullName: 'General Data Protection Regulation' },
    GLOBAL: { name: 'Global', fullName: 'Global Privacy Standards' },
  };

  const regimeInfo = regimeLabels[regime] || regimeLabels.GLOBAL;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-black/50 transition-opacity"
        onClick={onClose}
      />

      {/* Modal */}
      <div className="flex min-h-full items-center justify-center p-4">
        <div className="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl transform transition-all">
          {/* Header */}
          <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div className="flex items-center gap-3">
              <div className="p-2 bg-primary-100 rounded-lg">
                <ShieldCheckIcon className="h-6 w-6 text-primary-600" />
              </div>
              <div>
                <h2 className="text-lg font-semibold text-gray-900">
                  {t('privacy.modalTitle')}
                </h2>
                <div className="flex items-center gap-2 mt-0.5">
                  <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-800">
                    {regimeInfo.name}
                  </span>
                  <span className="text-xs text-gray-500">
                    v{privacyMeta?.policy_version || '2026-01'}
                  </span>
                </div>
              </div>
            </div>
            <button
              onClick={onClose}
              className="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
            >
              <XMarkIcon className="h-5 w-5" />
            </button>
          </div>

          {/* Content */}
          <div
            ref={contentRef}
            onScroll={handleScroll}
            className="px-6 py-4 max-h-[60vh] overflow-y-auto"
          >
            {loading ? (
              <div className="flex items-center justify-center py-12">
                <div className="w-8 h-8 border-4 border-primary-600 border-t-transparent rounded-full animate-spin" />
              </div>
            ) : policy ? (
              <div className="prose prose-sm max-w-none">
                <h3 className="text-xl font-bold text-gray-900 mb-4">
                  {policy.title}
                </h3>
                {policy.sections.map((section, index) => (
                  <div key={index} className="mb-6">
                    <h4 className="text-base font-semibold text-gray-900 mb-2">
                      {section.title}
                    </h4>
                    <div className="text-gray-600 whitespace-pre-line text-sm">
                      {section.content}
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-gray-600">
                <p className="mb-4">
                  {locale === 'tr'
                    ? 'Kisisel verilerinizin korunmasi bizim icin onemlidir. Verileriniz ilgili veri koruma mevzuatina uygun olarak islenmektedir.'
                    : 'Your privacy is important to us. Your data is processed in accordance with applicable data protection regulations.'}
                </p>
                <p>
                  {locale === 'tr'
                    ? 'Detayli bilgi icin: privacy@talentqx.com'
                    : 'For details, contact: privacy@talentqx.com'}
                </p>
              </div>
            )}
          </div>

          {/* Footer */}
          <div className="px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl">
            {requireScrollToEnd && !hasScrolledToEnd && (
              <p className="text-sm text-amber-600 mb-3 text-center">
                {t('privacy.scrollToEnd')}
              </p>
            )}
            <div className="flex items-center justify-between">
              <div className="text-xs text-gray-500">
                {privacyMeta?.country && (
                  <span>
                    {t('privacy.detectedCountry')}: {privacyMeta.country}
                  </span>
                )}
              </div>
              <div className="flex gap-3">
                <button
                  onClick={onClose}
                  className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                >
                  {t('privacy.close')}
                </button>
                {onAccept && (
                  <button
                    onClick={onAccept}
                    disabled={requireScrollToEnd && !hasScrolledToEnd}
                    className="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed"
                  >
                    {t('privacy.accept')}
                  </button>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
