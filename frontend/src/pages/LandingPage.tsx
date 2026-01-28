import { useState } from 'react';
import { Link } from 'react-router-dom';
import {
  CheckCircleIcon,
  ExclamationTriangleIcon,
  CpuChipIcon,
  ShieldCheckIcon,
  ChartBarIcon,
  ClockIcon,
  UserGroupIcon,
  BuildingStorefrontIcon,
  TruckIcon,
  CakeIcon,
  ChevronDownIcon,
  EnvelopeIcon,
  PhoneIcon,
  BuildingOfficeIcon,
  UserIcon,
  CurrencyDollarIcon,
  AcademicCapIcon,
  ArrowTrendingUpIcon,
  ScaleIcon,
} from '@heroicons/react/24/outline';
import { ROUTES } from '../routes';

// How it works steps
const steps = [
  {
    number: '1',
    title: 'Pozisyonu Tanımlayın',
    description: 'Yetkinlikleri, riskleri ve kültür kriterlerini belirleyin.',
  },
  {
    number: '2',
    title: 'Online Değerlendirme',
    description: 'Aday veya çalışan, rolüne özel senaryo testine girer.',
  },
  {
    number: '3',
    title: 'Yapay Zeka Analizi',
    description: 'Cevaplar analiz edilir, skorlar ve riskler çıkarılır.',
  },
  {
    number: '4',
    title: 'Karar Destek Raporu',
    description: 'İşe alım, eğitim veya terfi kararını objektif verin.',
  },
];

// Target audiences
const audiences = [
  {
    title: 'Franchise Markalar',
    description: 'Merkezden personel standardı, şube bazlı kalite kontrol.',
    icon: BuildingStorefrontIcon,
    color: 'bg-purple-50 border-purple-200',
    iconColor: 'text-purple-600',
  },
  {
    title: 'Zincir Mağazalar',
    description: 'Tezgahtar, kasiyer ve mağaza yöneticileri için standart değerlendirme.',
    icon: UserGroupIcon,
    color: 'bg-blue-50 border-blue-200',
    iconColor: 'text-blue-600',
  },
  {
    title: 'Üretim & Pastahane Tesisleri',
    description: 'Hijyen, talimat uyumu ve kalite risklerini önceden görün.',
    icon: CakeIcon,
    color: 'bg-pink-50 border-pink-200',
    iconColor: 'text-pink-600',
  },
  {
    title: 'Lojistik & Depo',
    description: 'Şoför ve depo personelinde operasyonel riski azaltın.',
    icon: TruckIcon,
    color: 'bg-green-50 border-green-200',
    iconColor: 'text-green-600',
  },
];

// Features
const features = [
  { name: 'Pozisyon bazlı akıllı soru motoru', icon: CpuChipIcon },
  { name: 'Senaryo & davranış analizleri', icon: ChartBarIcon },
  { name: 'Kırmızı bayrak (risk) tespiti', icon: ExclamationTriangleIcon },
  { name: 'Kopya & ezber risk skoru', icon: ShieldCheckIcon },
  { name: 'Franchise merkez onay akışı', icon: BuildingStorefrontIcon },
  { name: 'KVKK uyumlu veri yönetimi', icon: CheckCircleIcon },
  { name: 'Maliyet kontrollü AI altyapısı', icon: CurrencyDollarIcon },
];

// Benefits
const benefits = [
  { text: 'Yanlış işe alımı %50+ azaltır', icon: ArrowTrendingUpIcon },
  { text: 'Mülakat süresini %70 kısaltır', icon: ClockIcon },
  { text: 'Eğitim ihtiyacını net gösterir', icon: AcademicCapIcon },
  { text: 'Terfi kararlarını objektifleştirir', icon: ScaleIcon },
  { text: 'Franchise standartlarını korur', icon: ShieldCheckIcon },
];

// Solution metrics
const solutionMetrics = [
  { label: 'İşe uygunluk', icon: CheckCircleIcon },
  { label: 'Disiplin & hijyen bilinci', icon: ShieldCheckIcon },
  { label: 'Stres ve tempo toleransı', icon: ClockIcon },
  { label: 'Riskli davranışlar', icon: ExclamationTriangleIcon },
  { label: 'Marka ve ekip uyumu', icon: UserGroupIcon },
];

// Packages
const packages = [
  {
    title: 'Küçük İşletmeler',
    description: 'Aday başı değerlendirme modeli',
    icon: UserIcon,
  },
  {
    title: 'Zincir Mağazalar',
    description: 'Mağaza bazlı abonelik',
    icon: BuildingStorefrontIcon,
  },
  {
    title: 'Franchise Markalar',
    description: 'Merkez lisans + şube kullanımı',
    icon: BuildingOfficeIcon,
  },
];

// FAQ items
const faqItems = [
  {
    question: 'Bu bir mülakat sistemi mi?',
    answer: 'Hayır. Bu bir karar destek ve değerlendirme platformudur.',
  },
  {
    question: 'Mevcut çalışanlar için kullanılabilir mi?',
    answer: 'Evet. Terfi, eğitim ve risk analizi için idealdir.',
  },
  {
    question: 'KVKK uyumlu mu?',
    answer: 'Evet. Veri saklama, silme ve export süreçleri dahildir.',
  },
];

// Company types for demo form
const companyTypes = [
  { value: 'single', label: 'Tek Şube' },
  { value: 'chain', label: 'Zincir' },
  { value: 'franchise', label: 'Franchise' },
];

export default function LandingPage() {
  const [openFaq, setOpenFaq] = useState<number | null>(null);
  const [formData, setFormData] = useState({
    company_name: '',
    contact_name: '',
    email: '',
    phone: '',
    company_type: '',
  });
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitSuccess, setSubmitSuccess] = useState(false);

  const handleFormChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000));
    setIsSubmitting(false);
    setSubmitSuccess(true);
    setFormData({ company_name: '', contact_name: '', email: '', phone: '', company_type: '' });
  };

  return (
    <div className="min-h-screen bg-white">
      {/* Navigation */}
      <nav className="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-sm border-b border-gray-100">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center">
              <span className="text-2xl font-bold text-gray-900">Talent<span className="text-primary-600">QX</span></span>
            </div>
            <div className="hidden md:flex items-center space-x-8">
              <a href="#how-it-works" className="text-gray-600 hover:text-gray-900 transition-colors">Nasıl Çalışır</a>
              <a href="#features" className="text-gray-600 hover:text-gray-900 transition-colors">Özellikler</a>
              <a href="#for-who" className="text-gray-600 hover:text-gray-900 transition-colors">Kimler İçin</a>
              <a href="#faq" className="text-gray-600 hover:text-gray-900 transition-colors">SSS</a>
              <Link to={ROUTES.LOGIN} className="text-gray-600 hover:text-gray-900 transition-colors">Giriş Yap</Link>
              <a href="#demo" className="btn-primary">Demo Talep Et</a>
            </div>
          </div>
        </div>
      </nav>

      {/* 1. HERO */}
      <section className="pt-32 pb-20 bg-gradient-to-b from-gray-50 to-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-4xl mx-auto">
            <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight">
              Doğru Personeli,{' '}
              <span className="text-primary-600">İşe Almadan Önce</span>{' '}
              Tanıyın.
            </h1>
            <h2 className="mt-6 text-xl md:text-2xl text-gray-600 max-w-3xl mx-auto">
              Zincir mağazalar ve franchise yapıları için AI destekli işe alım ve çalışan değerlendirme platformu.
            </h2>
            <p className="mt-4 text-lg text-gray-500">
              Yanlış işe alımı azaltın, personel standardınızı merkezden yönetin.
            </p>
            <div className="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
              <a href="#demo" className="btn-primary text-lg px-8 py-3">
                Ücretsiz Demo Talep Et
              </a>
              <a href="#how-it-works" className="btn-secondary text-lg px-8 py-3">
                Nasıl Çalışır?
              </a>
            </div>
          </div>
        </div>
      </section>

      {/* 2. PROBLEM */}
      <section className="py-20 bg-gray-900 text-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-2 gap-12 items-center">
            <div>
              <h2 className="text-3xl md:text-4xl font-bold mb-6">
                Yanlış Personel,{' '}
                <span className="text-red-400">En Pahalı Operasyon Hatasıdır.</span>
              </h2>
              <p className="text-gray-300 mb-6">
                İşletmelerin çoğu şu sorunları yaşıyor:
              </p>
              <ul className="space-y-4 text-gray-300">
                <li className="flex items-start">
                  <ExclamationTriangleIcon className="h-6 w-6 text-red-400 mr-3 flex-shrink-0 mt-0.5" />
                  <span>CV'ler birbirinin aynısı</span>
                </li>
                <li className="flex items-start">
                  <ExclamationTriangleIcon className="h-6 w-6 text-red-400 mr-3 flex-shrink-0 mt-0.5" />
                  <span>Mülakatlar kişiye göre değişiyor</span>
                </li>
                <li className="flex items-start">
                  <ExclamationTriangleIcon className="h-6 w-6 text-red-400 mr-3 flex-shrink-0 mt-0.5" />
                  <span>Hijyen, disiplin ve tempo sahada bozuluyor</span>
                </li>
                <li className="flex items-start">
                  <ExclamationTriangleIcon className="h-6 w-6 text-red-400 mr-3 flex-shrink-0 mt-0.5" />
                  <span>Yanlış işe alım aylarca zarar yazıyor</span>
                </li>
              </ul>
            </div>
            <div className="text-center">
              <div className="bg-red-500/20 border border-red-500/30 rounded-2xl p-8">
                <p className="text-gray-400 mb-2">Yanlış bir personelin gerçek maliyeti:</p>
                <p className="text-5xl md:text-6xl font-bold text-red-400">
                  50.000 – 500.000 TL
                </p>
                <p className="mt-6 text-lg text-gray-300">
                  Ama bu riski, işe almadan önce ölçmek mümkün.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 3. SOLUTION */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              CV Değil, <span className="text-primary-600">İnsanı Okuyan</span> Sistem.
            </h2>
            <p className="text-xl text-gray-600 max-w-3xl mx-auto">
              TalentQX; pozisyona özel sorular, senaryo testleri ve yapay zeka analiziyle adayları ve mevcut çalışanları objektif olarak değerlendirir.
            </p>
          </div>

          <div className="mb-12">
            <p className="text-center text-lg font-medium text-gray-700 mb-8">Ölçtüğümüz şeyler:</p>
            <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
              {solutionMetrics.map((item, index) => (
                <div key={index} className="text-center p-6 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                  <item.icon className="h-10 w-10 text-primary-600 mx-auto mb-3" />
                  <p className="font-medium text-gray-900">{item.label}</p>
                </div>
              ))}
            </div>
          </div>

          <div className="text-center">
            <p className="text-xl font-semibold text-gray-900">
              Sonuç: <span className="text-primary-600">Daha doğru kararlar. Daha güçlü ekipler.</span>
            </p>
          </div>
        </div>
      </section>

      {/* 4. FOR WHO */}
      <section id="for-who" className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              Kimler İçin?
            </h2>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            {audiences.map((audience, index) => (
              <div key={index} className={`p-6 rounded-xl border-2 ${audience.color} hover:shadow-lg transition-shadow`}>
                <audience.icon className={`h-12 w-12 ${audience.iconColor} mb-4`} />
                <h3 className="text-lg font-semibold text-gray-900 mb-2">{audience.title}</h3>
                <p className="text-gray-600">{audience.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 5. HOW IT WORKS */}
      <section id="how-it-works" className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              Nasıl Çalışır?
            </h2>
            <p className="text-xl text-gray-600">4 adımda daha doğru personel kararları</p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            {steps.map((step, index) => (
              <div key={index} className="relative">
                <div className="flex items-center justify-center w-14 h-14 bg-primary-600 text-white rounded-full text-2xl font-bold mb-4">
                  {step.number}
                </div>
                {index < steps.length - 1 && (
                  <div className="hidden lg:block absolute top-7 left-14 w-full h-0.5 bg-primary-200" />
                )}
                <h3 className="text-lg font-semibold text-gray-900 mb-2">{step.title}</h3>
                <p className="text-gray-600">{step.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 6. FEATURES */}
      <section id="features" className="py-20 bg-primary-600">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-white mb-4">
              Öne Çıkan Özellikler
            </h2>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            {features.map((feature, index) => (
              <div key={index} className="flex items-center p-4 bg-white/10 rounded-xl hover:bg-white/20 transition-colors">
                <feature.icon className="h-8 w-8 text-white mr-4 flex-shrink-0" />
                <span className="text-white font-medium">{feature.name}</span>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 7. WHY TALENTQX */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              Personel Kalitesi Artık <span className="text-primary-600">Tesadüf Değil.</span>
            </h2>
          </div>

          <div className="grid md:grid-cols-3 lg:grid-cols-5 gap-6">
            {benefits.map((benefit, index) => (
              <div key={index} className="text-center p-6 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                <benefit.icon className="h-10 w-10 text-primary-600 mx-auto mb-3" />
                <p className="font-medium text-gray-900">{benefit.text}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 8. SOCIAL PROOF */}
      <section className="py-16 bg-gray-50">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <blockquote className="text-center">
            <p className="text-2xl md:text-3xl italic text-gray-700">
              "Kendi zincirimizde uyguladık. 6 ayda personel hatalarını <span className="text-primary-600 font-semibold">%40</span> azalttık."
            </p>
            <footer className="mt-6 text-gray-500">
              — İç Kullanım Referansı
            </footer>
          </blockquote>
        </div>
      </section>

      {/* 9. PACKAGES */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              Paketler & Model
            </h2>
          </div>

          <div className="grid md:grid-cols-3 gap-8 max-w-4xl mx-auto">
            {packages.map((pkg, index) => (
              <div key={index} className="text-center p-8 bg-gray-50 rounded-xl border-2 border-gray-100 hover:border-primary-200 hover:shadow-lg transition-all">
                <pkg.icon className="h-12 w-12 text-primary-600 mx-auto mb-4" />
                <h3 className="text-xl font-semibold text-gray-900 mb-2">{pkg.title}</h3>
                <p className="text-gray-600">{pkg.description}</p>
              </div>
            ))}
          </div>

          <p className="text-center text-gray-500 mt-8">
            Detaylı fiyatlandırma demo sonrası paylaşılır.
          </p>
        </div>
      </section>

      {/* 10. DEMO CTA */}
      <section id="demo" className="py-20 bg-gradient-to-br from-primary-600 to-primary-800">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl font-bold text-white mb-4">
              Personel Kalitenizi Bugün Ölçmeye Başlayın.
            </h2>
            <p className="text-xl text-primary-100">
              Kurulum 1 gün. İlk sonuçlar ilk haftada.
            </p>
          </div>

          {submitSuccess ? (
            <div className="bg-white rounded-2xl p-8 text-center">
              <CheckCircleIcon className="h-16 w-16 text-green-500 mx-auto mb-4" />
              <h3 className="text-2xl font-bold text-gray-900 mb-2">Talebiniz Alındı!</h3>
              <p className="text-gray-600">En kısa sürede sizinle iletişime geçeceğiz.</p>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="bg-white rounded-2xl p-8">
              <div className="grid md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    <BuildingOfficeIcon className="h-4 w-4 inline mr-1" />
                    Firma Adı
                  </label>
                  <input
                    type="text"
                    name="company_name"
                    value={formData.company_name}
                    onChange={handleFormChange}
                    required
                    className="input"
                    placeholder="Şirket Adı"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    <UserIcon className="h-4 w-4 inline mr-1" />
                    Yetkili Adı
                  </label>
                  <input
                    type="text"
                    name="contact_name"
                    value={formData.contact_name}
                    onChange={handleFormChange}
                    required
                    className="input"
                    placeholder="Ad Soyad"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    <EnvelopeIcon className="h-4 w-4 inline mr-1" />
                    E-posta
                  </label>
                  <input
                    type="email"
                    name="email"
                    value={formData.email}
                    onChange={handleFormChange}
                    required
                    className="input"
                    placeholder="ornek@sirket.com"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    <PhoneIcon className="h-4 w-4 inline mr-1" />
                    Telefon
                  </label>
                  <input
                    type="tel"
                    name="phone"
                    value={formData.phone}
                    onChange={handleFormChange}
                    required
                    className="input"
                    placeholder="05XX XXX XX XX"
                  />
                </div>
                <div className="md:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    <BuildingStorefrontIcon className="h-4 w-4 inline mr-1" />
                    Firma Tipi
                  </label>
                  <select
                    name="company_type"
                    value={formData.company_type}
                    onChange={handleFormChange}
                    required
                    className="input"
                  >
                    <option value="">Seçin...</option>
                    {companyTypes.map((type) => (
                      <option key={type.value} value={type.value}>{type.label}</option>
                    ))}
                  </select>
                </div>
              </div>
              <button
                type="submit"
                disabled={isSubmitting}
                className="mt-8 w-full btn-primary py-4 text-lg"
              >
                {isSubmitting ? 'Gönderiliyor...' : 'Ücretsiz Demo Talep Et'}
              </button>
            </form>
          )}
        </div>
      </section>

      {/* 11. FAQ */}
      <section id="faq" className="py-20 bg-gray-50">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              Sıkça Sorulan Sorular
            </h2>
          </div>

          <div className="space-y-4">
            {faqItems.map((item, index) => (
              <div key={index} className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <button
                  onClick={() => setOpenFaq(openFaq === index ? null : index)}
                  className="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors"
                >
                  <span className="font-semibold text-gray-900">{item.question}</span>
                  <ChevronDownIcon className={`h-5 w-5 text-gray-500 transition-transform ${openFaq === index ? 'rotate-180' : ''}`} />
                </button>
                {openFaq === index && (
                  <div className="px-6 pb-4 text-gray-600">
                    {item.answer}
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 12. FOOTER */}
      <footer className="py-16 bg-gray-900 text-gray-400">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-4 gap-8 mb-12">
            <div>
              <span className="text-2xl font-bold text-white">Talent<span className="text-primary-400">QX</span></span>
              <p className="mt-4 text-sm">
                AI Destekli İnsan Kalitesi Platformu
              </p>
              <p className="mt-2 text-sm italic">
                İnsan kaynağınız, markanızın görünmeyen vitrini.
              </p>
            </div>
            <div>
              <h4 className="font-semibold text-white mb-4">Platform</h4>
              <ul className="space-y-2 text-sm">
                <li><a href="#how-it-works" className="hover:text-white transition-colors">Nasıl Çalışır</a></li>
                <li><a href="#features" className="hover:text-white transition-colors">Özellikler</a></li>
                <li><a href="#for-who" className="hover:text-white transition-colors">Kimler İçin</a></li>
                <li><a href="#demo" className="hover:text-white transition-colors">Demo Talep Et</a></li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold text-white mb-4">Çözümler</h4>
              <ul className="space-y-2 text-sm">
                <li><a href="#" className="hover:text-white transition-colors">İşe Alım</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Değerlendirme</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Gelişim</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Franchise Standartları</a></li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold text-white mb-4">Yasal</h4>
              <ul className="space-y-2 text-sm">
                <li><a href="#" className="hover:text-white transition-colors">Hakkımızda</a></li>
                <li><a href="#" className="hover:text-white transition-colors">KVKK Aydınlatma Metni</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Gizlilik Politikası</a></li>
                <li><a href="#" className="hover:text-white transition-colors">İletişim</a></li>
              </ul>
            </div>
          </div>
          <div className="pt-8 border-t border-gray-800 text-center text-sm">
            <p>&copy; {new Date().getFullYear()} TalentQX. Tüm hakları saklıdır.</p>
          </div>
        </div>
      </footer>
    </div>
  );
}
