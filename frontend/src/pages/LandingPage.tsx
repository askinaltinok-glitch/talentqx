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
} from '@heroicons/react/24/outline';
import { ROUTES } from '../routes';

const features = [
  { name: 'Pozisyon bazli akilli soru motoru', icon: CpuChipIcon },
  { name: 'Senaryo & davranis analizleri', icon: ChartBarIcon },
  { name: 'Kirmizi bayrak tespiti', icon: ExclamationTriangleIcon },
  { name: 'Kopya & ezber risk skoru', icon: ShieldCheckIcon },
  { name: 'Franchise merkez onay sistemi', icon: BuildingStorefrontIcon },
  { name: 'KVKK uyumlu veri yonetimi', icon: CheckCircleIcon },
];

const benefits = [
  { text: 'Yanlis ise alimi %50+ azaltir', color: 'text-green-600' },
  { text: 'Mulakat suresini %70 kisaltir', color: 'text-blue-600' },
  { text: 'Franchise standardini korur', color: 'text-purple-600' },
  { text: 'Egitim ihtiyacini net gosterir', color: 'text-orange-600' },
  { text: 'Terfi kararlarini objektiflestirir', color: 'text-indigo-600' },
];

const targetAudiences = [
  {
    title: 'Pastahaneler & Uretim Tesisleri',
    description: 'Hijyen, kalite ve talimat uyumu olculur.',
    icon: CakeIcon,
    color: 'bg-pink-50 border-pink-200',
    iconColor: 'text-pink-600',
  },
  {
    title: 'Zincir Magazalar',
    description: 'Tezgahtar, kasiyer ve magaza sorumlulari icin standart kalite.',
    icon: BuildingStorefrontIcon,
    color: 'bg-blue-50 border-blue-200',
    iconColor: 'text-blue-600',
  },
  {
    title: 'Franchise Markalar',
    description: 'Merkezden personel standardi ve onay mekanizmasi.',
    icon: UserGroupIcon,
    color: 'bg-purple-50 border-purple-200',
    iconColor: 'text-purple-600',
  },
  {
    title: 'Lojistik & Depo',
    description: 'Sofor, depo ve sevkiyat personelinde risk azaltma.',
    icon: TruckIcon,
    color: 'bg-green-50 border-green-200',
    iconColor: 'text-green-600',
  },
];

const steps = [
  {
    number: '1',
    title: 'Pozisyonu Tanimlayin',
    description: 'Yetkinlikleri, riskleri ve kultur kriterlerini belirleyin.',
  },
  {
    number: '2',
    title: 'Aday Online Mulakata Girer',
    description: 'Video, ses veya yazili cevaplarla pozisyona ozel sorulari yanitlar.',
  },
  {
    number: '3',
    title: 'Yapay Zeka Analiz Eder',
    description: 'Yetkinlik, risk, uyum ve potansiyel skorlari cikarilir.',
  },
  {
    number: '4',
    title: 'Karar Destek Raporu',
    description: 'Ise alim, egitim veya eleme karari objektif hale gelir.',
  },
];

export default function LandingPage() {
  return (
    <div className="min-h-screen bg-white">
      {/* Navigation */}
      <nav className="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-sm border-b border-gray-100">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center">
              <span className="text-2xl font-bold text-primary-600">TalentQX</span>
            </div>
            <div className="hidden md:flex items-center space-x-8">
              <a href="#features" className="text-gray-600 hover:text-gray-900">Ozellikler</a>
              <a href="#how-it-works" className="text-gray-600 hover:text-gray-900">Nasil Calisir</a>
              <a href="#for-who" className="text-gray-600 hover:text-gray-900">Kimler Icin</a>
              <Link to={ROUTES.LOGIN} className="btn-secondary">Giris Yap</Link>
              <a href="#contact" className="btn-primary">Demo Talep Et</a>
            </div>
          </div>
        </div>
      </nav>

      {/* Hero Section */}
      <section className="pt-32 pb-20 bg-gradient-to-br from-primary-50 via-white to-blue-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-4xl mx-auto">
            <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight">
              Dogru Personeli,{' '}
              <span className="text-primary-600">Ise Almadan Once</span>{' '}
              Taniyin.
            </h1>
            <p className="mt-6 text-xl text-gray-600 max-w-3xl mx-auto">
              Pastahane, zincir magaza ve franchise yapilari icin gelistirilen
              AI destekli ise alim ve personel kalite platformu.
            </p>
            <p className="mt-4 text-lg text-gray-500">
              Yanlis ise alim maliyetini azaltin. Marka standardinizi personelinizle koruyun.
            </p>
            <div className="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
              <a href="#contact" className="btn-primary text-lg px-8 py-3">
                Demo Talep Et
              </a>
              <a href="#how-it-works" className="btn-secondary text-lg px-8 py-3">
                Sistemi Kesfet
              </a>
            </div>
          </div>
        </div>
      </section>

      {/* Problem Section */}
      <section className="py-20 bg-gray-900 text-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-2 gap-12 items-center">
            <div>
              <h2 className="text-3xl md:text-4xl font-bold mb-6">
                Yanlis Personel,{' '}
                <span className="text-red-400">En Pahali Hatadir.</span>
              </h2>
              <ul className="space-y-4 text-gray-300">
                <li className="flex items-start">
                  <ExclamationTriangleIcon className="h-6 w-6 text-red-400 mr-3 flex-shrink-0 mt-0.5" />
                  <span>CV'ler birbirine benziyor</span>
                </li>
                <li className="flex items-start">
                  <ExclamationTriangleIcon className="h-6 w-6 text-red-400 mr-3 flex-shrink-0 mt-0.5" />
                  <span>Mulakatlar subjektif</span>
                </li>
                <li className="flex items-start">
                  <ExclamationTriangleIcon className="h-6 w-6 text-red-400 mr-3 flex-shrink-0 mt-0.5" />
                  <span>Yanlis ise alim aylarca zarar yaziyor</span>
                </li>
                <li className="flex items-start">
                  <ExclamationTriangleIcon className="h-6 w-6 text-red-400 mr-3 flex-shrink-0 mt-0.5" />
                  <span>Hijyen, disiplin ve tempo sahada bozuluyor</span>
                </li>
              </ul>
            </div>
            <div className="text-center">
              <div className="bg-red-500/20 border border-red-500/30 rounded-2xl p-8">
                <p className="text-gray-400 mb-2">Yanlis bir personelin maliyeti:</p>
                <p className="text-5xl md:text-6xl font-bold text-red-400">
                  50.000 - 500.000 TL
                </p>
                <p className="mt-4 text-lg text-gray-300">
                  Ama bunu onceden olcmek mumkun.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Solution Section */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              CV Degil, <span className="text-primary-600">Insani Okuyan</span> Sistem.
            </h2>
            <p className="text-xl text-gray-600 max-w-3xl mx-auto">
              TalentQX, pozisyona ozel mulakat ve degerlendirme motoru ile
              adaylari ise almadan once analiz eder.
            </p>
          </div>

          <div className="grid md:grid-cols-3 lg:grid-cols-5 gap-6">
            {[
              { label: 'Yetkinlik profili', icon: ChartBarIcon },
              { label: 'Disiplin & hijyen bilinci', icon: ShieldCheckIcon },
              { label: 'Stres ve tempo toleransi', icon: ClockIcon },
              { label: 'Riskli davranislar', icon: ExclamationTriangleIcon },
              { label: 'Marka kulturu uyumu', icon: UserGroupIcon },
            ].map((item, index) => (
              <div key={index} className="text-center p-6 bg-gray-50 rounded-xl">
                <item.icon className="h-10 w-10 text-primary-600 mx-auto mb-3" />
                <p className="font-medium text-gray-900">{item.label}</p>
              </div>
            ))}
          </div>

          <div className="mt-12 text-center">
            <p className="text-xl font-semibold text-gray-900">
              Sonuc: <span className="text-primary-600">Daha dogru ise alim. Daha az hata. Daha guclu ekip.</span>
            </p>
          </div>
        </div>
      </section>

      {/* For Who Section */}
      <section id="for-who" className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              Kimler Icin?
            </h2>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            {targetAudiences.map((audience, index) => (
              <div key={index} className={`p-6 rounded-xl border-2 ${audience.color}`}>
                <audience.icon className={`h-12 w-12 ${audience.iconColor} mb-4`} />
                <h3 className="text-lg font-semibold text-gray-900 mb-2">{audience.title}</h3>
                <p className="text-gray-600">{audience.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* How It Works Section */}
      <section id="how-it-works" className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              Nasil Calisir?
            </h2>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
            {steps.map((step, index) => (
              <div key={index} className="relative">
                <div className="flex items-center justify-center w-12 h-12 bg-primary-600 text-white rounded-full text-xl font-bold mb-4">
                  {step.number}
                </div>
                {index < steps.length - 1 && (
                  <div className="hidden lg:block absolute top-6 left-12 w-full h-0.5 bg-primary-200" />
                )}
                <h3 className="text-lg font-semibold text-gray-900 mb-2">{step.title}</h3>
                <p className="text-gray-600">{step.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section id="features" className="py-20 bg-primary-600">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-white mb-4">
              One Cikan Ozellikler
            </h2>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            {features.map((feature, index) => (
              <div key={index} className="flex items-center p-4 bg-white/10 rounded-xl">
                <feature.icon className="h-8 w-8 text-white mr-4 flex-shrink-0" />
                <span className="text-white font-medium">{feature.name}</span>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Why TalentQX Section */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              Personel Kalitesi Artik <span className="text-primary-600">Tesaduf Degil.</span>
            </h2>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-5 gap-6">
            {benefits.map((benefit, index) => (
              <div key={index} className="text-center p-6 bg-gray-50 rounded-xl">
                <CheckCircleIcon className={`h-10 w-10 ${benefit.color} mx-auto mb-3`} />
                <p className="font-medium text-gray-900">{benefit.text}</p>
              </div>
            ))}
          </div>

          {/* Testimonial */}
          <div className="mt-16 max-w-3xl mx-auto">
            <blockquote className="text-center">
              <p className="text-xl italic text-gray-600">
                "Kendi zincirimizde uyguladik. 6 ayda personel hatalarini %40 azalttik."
              </p>
              <footer className="mt-4 text-gray-500">
                - Ic Kullanim Referansi
              </footer>
            </blockquote>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section id="contact" className="py-20 bg-gradient-to-br from-primary-600 to-primary-800">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-3xl md:text-4xl font-bold text-white mb-6">
            Personel Kalitenizi Olcmeye Bugun Baslayin.
          </h2>

          <div className="flex flex-col sm:flex-row gap-4 justify-center mb-8">
            <a href="mailto:demo@talentqx.com" className="bg-white text-primary-600 hover:bg-gray-100 font-semibold px-8 py-3 rounded-lg transition-colors">
              Ucretsiz Demo Talep Et
            </a>
            <a href="#for-who" className="bg-primary-700 text-white hover:bg-primary-800 font-semibold px-8 py-3 rounded-lg border border-primary-500 transition-colors">
              Franchise Cozumunu Incele
            </a>
          </div>

          <p className="text-primary-200">
            Kurulum 1 gun. Ilk sonuclar ilk haftada.
          </p>
        </div>
      </section>

      {/* Footer */}
      <footer className="py-12 bg-gray-900 text-gray-400">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-4 gap-8">
            <div>
              <span className="text-2xl font-bold text-white">TalentQX</span>
              <p className="mt-4 text-sm">
                AI Destekli Insan Kalitesi Platformu
              </p>
              <p className="mt-2 text-sm italic">
                Insan kaynaginiz, markanizin gorunmeyen vitrini.
              </p>
            </div>
            <div>
              <h4 className="font-semibold text-white mb-4">Platform</h4>
              <ul className="space-y-2 text-sm">
                <li><a href="#" className="hover:text-white">Ise alim</a></li>
                <li><a href="#" className="hover:text-white">Degerlendirme</a></li>
                <li><a href="#" className="hover:text-white">Gelisim</a></li>
                <li><a href="#" className="hover:text-white">Franchise standartlari</a></li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold text-white mb-4">Kaynaklar</h4>
              <ul className="space-y-2 text-sm">
                <li><a href="#" className="hover:text-white">Dokumantasyon</a></li>
                <li><a href="#" className="hover:text-white">API</a></li>
                <li><a href="#" className="hover:text-white">Destek</a></li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold text-white mb-4">Yasal</h4>
              <ul className="space-y-2 text-sm">
                <li><a href="#" className="hover:text-white">Gizlilik Politikasi</a></li>
                <li><a href="#" className="hover:text-white">KVKK Aydinlatma Metni</a></li>
                <li><a href="#" className="hover:text-white">Kullanim Kosullari</a></li>
              </ul>
            </div>
          </div>
          <div className="mt-12 pt-8 border-t border-gray-800 text-center text-sm">
            <p>&copy; {new Date().getFullYear()} TalentQX. Tum haklari saklidir.</p>
          </div>
        </div>
      </footer>
    </div>
  );
}
