import { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import {
  CheckCircleIcon,
  MapPinIcon,
  BriefcaseIcon,
  BuildingOfficeIcon,
  UserIcon,
  PhoneIcon,
  EnvelopeIcon,
  CalendarIcon,
  ClockIcon,
  ShieldCheckIcon,
  ExclamationTriangleIcon,
} from '@heroicons/react/24/outline';
import { applyService, type ApplyData, type ApplyFormData } from '../services/apply';

type ViewState = 'loading' | 'form' | 'success' | 'error';

export default function PublicApply() {
  const { companySlug, branchSlug, roleCode } = useParams<{
    companySlug: string;
    branchSlug: string;
    roleCode: string;
  }>();

  const [view, setView] = useState<ViewState>('loading');
  const [error, setError] = useState<string | null>(null);
  const [data, setData] = useState<ApplyData | null>(null);
  const [submitting, setSubmitting] = useState(false);

  // Form state
  const [formData, setFormData] = useState<ApplyFormData>({
    full_name: '',
    phone: '',
    email: '',
    birth_year: undefined,
    experience_years: undefined,
    kvkk_consent: false,
    marketing_consent: false,
  });

  // Load job info on mount
  useEffect(() => {
    if (!companySlug || !branchSlug || !roleCode) {
      setError('Geçersiz başvuru bağlantısı');
      setView('error');
      return;
    }

    applyService.getJobInfo(companySlug, branchSlug, roleCode)
      .then((result) => {
        setData(result);
        setView('form');
      })
      .catch((err) => {
        setError(applyService.getErrorMessage(err));
        setView('error');
      });
  }, [companySlug, branchSlug, roleCode]);

  // Handle form input change
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked :
              type === 'number' ? (value ? parseInt(value, 10) : undefined) :
              value,
    }));
  };

  // Handle form submit
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!companySlug || !branchSlug || !roleCode) return;

    // Validate
    if (!formData.full_name.trim()) {
      setError('Ad Soyad alanı zorunludur');
      return;
    }
    if (!formData.phone.trim()) {
      setError('Telefon alanı zorunludur');
      return;
    }
    if (!formData.email?.trim()) {
      setError('E-posta alanı zorunludur');
      return;
    }
    // Basic email format validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(formData.email)) {
      setError('Geçerli bir e-posta adresi giriniz');
      return;
    }
    if (!formData.kvkk_consent) {
      setError('KVKK onayı zorunludur');
      return;
    }

    setSubmitting(true);
    setError(null);

    try {
      await applyService.submitApplication(companySlug, branchSlug, roleCode, formData);
      setView('success');
    } catch (err) {
      setError(applyService.getErrorMessage(err));
    } finally {
      setSubmitting(false);
    }
  };

  // Format phone number
  const formatPhone = (value: string) => {
    const digits = value.replace(/\D/g, '');
    if (digits.length <= 3) return digits;
    if (digits.length <= 6) return `${digits.slice(0, 3)} ${digits.slice(3)}`;
    if (digits.length <= 8) return `${digits.slice(0, 3)} ${digits.slice(3, 6)} ${digits.slice(6)}`;
    return `${digits.slice(0, 3)} ${digits.slice(3, 6)} ${digits.slice(6, 8)} ${digits.slice(8, 10)}`;
  };

  const handlePhoneChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const formatted = formatPhone(e.target.value);
    setFormData(prev => ({ ...prev, phone: formatted }));
  };

  // Employment type labels
  const employmentTypeLabels: Record<string, string> = {
    full_time: 'Tam Zamanlı',
    part_time: 'Yarı Zamanlı',
    contract: 'Sözleşmeli',
    internship: 'Staj',
    temporary: 'Geçici',
  };

  // Loading state
  if (view === 'loading') {
    return (
      <div className="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 flex items-center justify-center">
        <div className="text-center">
          <div className="w-16 h-16 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto mb-4" />
          <p className="text-gray-600">Yükleniyor...</p>
        </div>
      </div>
    );
  }

  // Error state
  if (view === 'error') {
    return (
      <div className="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 flex items-center justify-center p-4">
        <div className="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full text-center">
          <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <ExclamationTriangleIcon className="w-8 h-8 text-red-600" />
          </div>
          <h1 className="text-xl font-bold text-gray-900 mb-2">Bir Hata Oluştu</h1>
          <p className="text-gray-600 mb-6">{error || 'İlan bulunamadı veya artık aktif değil.'}</p>
          <button
            onClick={() => window.location.reload()}
            className="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
          >
            Tekrar Dene
          </button>
        </div>
      </div>
    );
  }

  // Success state
  if (view === 'success') {
    return (
      <div className="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 flex items-center justify-center p-4">
        <div className="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full text-center">
          <div className="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <CheckCircleIcon className="w-10 h-10 text-green-600" />
          </div>
          <h1 className="text-2xl font-bold text-gray-900 mb-2">Başvurunuz Alındı!</h1>
          <p className="text-gray-600 mb-6">
            {data?.company.name} - {data?.job.title} pozisyonuna başvurunuz başarıyla kaydedildi.
            En kısa sürede sizinle iletişime geçeceğiz.
          </p>
          <div className="p-4 bg-indigo-50 rounded-lg text-sm text-indigo-700">
            <ShieldCheckIcon className="w-5 h-5 inline mr-2" />
            Verileriniz KVKK kapsamında güvende tutulmaktadır.
          </div>
        </div>
      </div>
    );
  }

  // Form state
  return (
    <div className="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 py-8 px-4">
      <div className="max-w-lg mx-auto">
        {/* Company Header */}
        <div className="bg-white rounded-2xl shadow-lg overflow-hidden mb-6">
          <div className="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-8 text-white">
            <div className="flex items-center gap-4">
              {data?.company.logo_url ? (
                <img
                  src={data.company.logo_url}
                  alt={data.company.name}
                  className="w-16 h-16 rounded-xl bg-white object-contain p-2"
                />
              ) : (
                <div className="w-16 h-16 rounded-xl bg-white/20 flex items-center justify-center">
                  <BuildingOfficeIcon className="w-8 h-8 text-white" />
                </div>
              )}
              <div>
                <h1 className="text-2xl font-bold">{data?.company.name}</h1>
                <p className="text-white/80 flex items-center gap-1 mt-1">
                  <MapPinIcon className="w-4 h-4" />
                  {data?.branch.name}, {data?.branch.city}
                </p>
              </div>
            </div>
          </div>

          {/* Job Info */}
          <div className="p-6">
            <div className="flex items-center gap-2 mb-3">
              <BriefcaseIcon className="w-5 h-5 text-indigo-600" />
              <h2 className="text-xl font-semibold text-gray-900">{data?.job.title}</h2>
            </div>
            <p className="text-gray-600 text-sm mb-4">{data?.job.description}</p>
            <div className="flex flex-wrap gap-2">
              <span className="inline-flex items-center gap-1 px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm">
                <ClockIcon className="w-4 h-4" />
                {employmentTypeLabels[data?.job.employment_type || ''] || data?.job.employment_type}
              </span>
              <span className="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">
                <MapPinIcon className="w-4 h-4" />
                {data?.job.location}
              </span>
            </div>
          </div>
        </div>

        {/* Application Form */}
        <div className="bg-white rounded-2xl shadow-lg p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-6">Başvuru Formu</h3>

          <form onSubmit={handleSubmit} className="space-y-5">
            {/* Full Name */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Ad Soyad <span className="text-red-500">*</span>
              </label>
              <div className="relative">
                <UserIcon className="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                  type="text"
                  name="full_name"
                  value={formData.full_name}
                  onChange={handleInputChange}
                  placeholder="Adınız Soyadınız"
                  className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  required
                />
              </div>
            </div>

            {/* Phone */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Telefon <span className="text-red-500">*</span>
              </label>
              <div className="relative">
                <PhoneIcon className="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                  type="tel"
                  name="phone"
                  value={formData.phone}
                  onChange={handlePhoneChange}
                  placeholder="5XX XXX XX XX"
                  className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  required
                />
              </div>
            </div>

            {/* Email (Optional) */}
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                E-posta <span className="text-gray-400">(İsteğe bağlı)</span>
              </label>
              <div className="relative">
                <EnvelopeIcon className="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                  type="email"
                  name="email"
                  value={formData.email}
                  onChange={handleInputChange}
                  placeholder="ornek@email.com"
                  className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                />
              </div>
            </div>

            {/* Birth Year & Experience Row */}
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Doğum Yılı
                </label>
                <div className="relative">
                  <CalendarIcon className="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                  <input
                    type="number"
                    name="birth_year"
                    value={formData.birth_year || ''}
                    onChange={handleInputChange}
                    placeholder="1990"
                    min="1950"
                    max={new Date().getFullYear() - 15}
                    className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Deneyim (Yıl)
                </label>
                <div className="relative">
                  <BriefcaseIcon className="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                  <input
                    type="number"
                    name="experience_years"
                    value={formData.experience_years || ''}
                    onChange={handleInputChange}
                    placeholder="0"
                    min="0"
                    max="50"
                    className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>
              </div>
            </div>

            {/* KVKK Consent */}
            <div className="border-t pt-5">
              <label className="flex items-start gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  name="kvkk_consent"
                  checked={formData.kvkk_consent}
                  onChange={handleInputChange}
                  className="mt-1 h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                  required
                />
                <span className="text-sm text-gray-700">
                  <span className="text-red-500">*</span>{' '}
                  Kişisel verilerimin{' '}
                  <a
                    href="https://talentqx.com/tr/privacy"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-indigo-600 hover:underline"
                  >
                    KVKK kapsamında
                  </a>{' '}
                  işlenmesini kabul ediyorum.
                </span>
              </label>
            </div>

            {/* Marketing Consent */}
            <div>
              <label className="flex items-start gap-3 cursor-pointer">
                <input
                  type="checkbox"
                  name="marketing_consent"
                  checked={formData.marketing_consent}
                  onChange={handleInputChange}
                  className="mt-1 h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                />
                <span className="text-sm text-gray-700">
                  Yeni iş fırsatları hakkında bilgilendirilmek istiyorum.
                </span>
              </label>
            </div>

            {/* Error message */}
            {error && (
              <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                {error}
              </div>
            )}

            {/* Submit Button */}
            <button
              type="submit"
              disabled={submitting || !formData.kvkk_consent}
              className="w-full py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-lg hover:from-indigo-700 hover:to-purple-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              {submitting ? (
                <>
                  <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  Gönderiliyor...
                </>
              ) : (
                <>
                  <CheckCircleIcon className="w-5 h-5" />
                  Başvur
                </>
              )}
            </button>
          </form>
        </div>

        {/* Footer */}
        <div className="text-center mt-6 text-sm text-gray-500">
          <ShieldCheckIcon className="w-4 h-4 inline mr-1" />
          Verileriniz güvende •{' '}
          <a href="https://talentqx.com" className="text-indigo-600 hover:underline">
            TalentQX
          </a>
        </div>
      </div>
    </div>
  );
}
