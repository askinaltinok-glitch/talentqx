import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { ArrowLeftIcon, ShieldCheckIcon, MapPinIcon, PhoneIcon, EnvelopeIcon } from '@heroicons/react/24/outline';
import api from '../services/api';

interface PolicySection {
  title: string;
  content: string;
}

interface PolicyData {
  regime: string;
  locale: string;
  version: string;
  content: {
    title: string;
    sections: PolicySection[];
  };
  last_updated: string;
}

interface PrivacyMeta {
  regime: string;
  locale: string;
  country: string;
}

export default function PublicPrivacy() {
  const { lang } = useParams<{ lang?: string }>();
  const [policy, setPolicy] = useState<PolicyData | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  // Determine locale from URL param or default to 'tr'
  const locale = lang && ['en', 'tr', 'de', 'fr'].includes(lang) ? lang : 'tr';

  useEffect(() => {
    loadPrivacyPolicy();
  }, [locale]);

  const loadPrivacyPolicy = async () => {
    setIsLoading(true);
    setError(null);

    try {
      // First get the detected regime
      const metaResponse = await api.get<PrivacyMeta>('/privacy/meta');
      const regime = metaResponse.regime || 'KVKK';

      // Then fetch the policy content
      const policyResponse = await api.get<PolicyData>(`/privacy/policy/${regime}/${locale}`);
      setPolicy(policyResponse);
    } catch (err) {
      console.error('Failed to load privacy policy:', err);
      setError(locale === 'tr'
        ? 'Gizlilik politikası yüklenemedi. Lütfen daha sonra tekrar deneyin.'
        : 'Failed to load privacy policy. Please try again later.'
      );
    } finally {
      setIsLoading(false);
    }
  };

  const translations = {
    tr: {
      backToHome: 'Ana Sayfaya Dön',
      loading: 'Yükleniyor...',
      lastUpdated: 'Son güncelleme',
      version: 'Versiyon',
      regimeLabels: {
        KVKK: 'KVKK - Kişisel Verilerin Korunması Kanunu',
        GDPR: 'GDPR - Genel Veri Koruma Yönetmeliği',
        GLOBAL: 'Global Gizlilik Standartları',
      },
      dataController: {
        title: 'Veri Sorumlusu İletişim',
        description: 'Kişisel verilerinizle ilgili talepleriniz için aşağıdaki iletişim bilgilerinden bize ulaşabilirsiniz:',
        address: 'Adres',
        phone: 'Telefon',
        email: 'E-posta',
      },
    },
    en: {
      backToHome: 'Back to Home',
      loading: 'Loading...',
      lastUpdated: 'Last updated',
      version: 'Version',
      regimeLabels: {
        KVKK: 'KVKK - Turkish Data Protection Law',
        GDPR: 'GDPR - General Data Protection Regulation',
        GLOBAL: 'Global Privacy Standards',
      },
      dataController: {
        title: 'Data Controller Contact',
        description: 'For requests regarding your personal data, you can reach us through the following contact information:',
        address: 'Address',
        phone: 'Phone',
        email: 'Email',
      },
    },
  };

  const t = translations[locale as keyof typeof translations] || translations.tr;

  if (isLoading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">{t.loading}</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
        <div className="text-center max-w-md">
          <ShieldCheckIcon className="h-16 w-16 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-600 mb-6">{error}</p>
          <Link
            to={`/${locale}`}
            className="inline-flex items-center gap-2 text-primary-600 hover:text-primary-700"
          >
            <ArrowLeftIcon className="h-4 w-4" />
            {t.backToHome}
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white border-b border-gray-200">
        <div className="max-w-4xl mx-auto px-4 py-4">
          <div className="flex items-center justify-between">
            <Link
              to={`/${locale}`}
              className="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900"
            >
              <ArrowLeftIcon className="h-4 w-4" />
              {t.backToHome}
            </Link>
            <span className="text-xl font-bold text-primary-600">TalentQX</span>
          </div>
        </div>
      </header>

      {/* Content */}
      <main className="max-w-4xl mx-auto px-4 py-8">
        <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          {/* Policy Header */}
          <div className="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-8 text-white">
            <div className="flex items-center gap-3 mb-4">
              <ShieldCheckIcon className="h-10 w-10" />
              <div>
                <h1 className="text-2xl font-bold">
                  {policy?.content?.title || 'Privacy Policy'}
                </h1>
                {policy?.regime && (
                  <p className="text-primary-100 text-sm mt-1">
                    {t.regimeLabels[policy.regime as keyof typeof t.regimeLabels] || policy.regime}
                  </p>
                )}
              </div>
            </div>
            <div className="flex items-center gap-4 text-sm text-primary-100">
              {policy?.version && (
                <span>{t.version}: {policy.version}</span>
              )}
              {policy?.last_updated && (
                <span>{t.lastUpdated}: {policy.last_updated}</span>
              )}
            </div>
          </div>

          {/* Policy Sections */}
          <div className="px-6 py-8 space-y-8">
            {policy?.content?.sections?.map((section, index) => (
              <section key={index} className="prose prose-gray max-w-none">
                <h2 className="text-lg font-semibold text-gray-900 mb-3">
                  {section.title}
                </h2>
                <p className="text-gray-600 leading-relaxed whitespace-pre-line">
                  {section.content}
                </p>
              </section>
            ))}

            {/* Data Controller Contact Section */}
            <section className="prose prose-gray max-w-none pt-6 border-t border-gray-200">
              <h2 className="text-lg font-semibold text-gray-900 mb-3">
                {t.dataController.title}
              </h2>
              <p className="text-gray-600 mb-4">
                {t.dataController.description}
              </p>
              <div className="bg-gray-50 rounded-lg p-6 space-y-4">
                <div className="flex items-start gap-3">
                  <MapPinIcon className="h-5 w-5 text-primary-600 flex-shrink-0 mt-0.5" />
                  <div>
                    <span className="font-medium text-gray-700">{t.dataController.address}:</span>
                    <p className="text-gray-600">
                      Atatürk Mah. Ertuğrul Gazi Sok. Metropol İstanbul Sitesi,<br />
                      Ataşehir / İstanbul / Türkiye
                    </p>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <PhoneIcon className="h-5 w-5 text-primary-600 flex-shrink-0" />
                  <div>
                    <span className="font-medium text-gray-700">{t.dataController.phone}:</span>
                    <a href="tel:+902164561144" className="text-gray-600 hover:text-primary-600 ml-1">
                      +90 216 456 11 44
                    </a>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <EnvelopeIcon className="h-5 w-5 text-primary-600 flex-shrink-0" />
                  <div>
                    <span className="font-medium text-gray-700">{t.dataController.email}:</span>
                    <a href="mailto:info@talentqx.com" className="text-gray-600 hover:text-primary-600 ml-1">
                      info@talentqx.com
                    </a>
                  </div>
                </div>
              </div>
            </section>
          </div>

          {/* Footer */}
          <div className="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <p className="text-sm text-gray-500 text-center">
              {locale === 'tr'
                ? 'Sorularınız için: privacy@talentqx.com'
                : 'For questions: privacy@talentqx.com'
              }
            </p>
          </div>
        </div>
      </main>
    </div>
  );
}
