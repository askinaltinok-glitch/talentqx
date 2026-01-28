import { useState } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
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
  StarIcon,
  DocumentTextIcon,
  UserMinusIcon,
} from '@heroicons/react/24/outline';
import { localizedPath } from '../routes';
import LanguageSwitcher from '../components/LanguageSwitcher';
import pricingHero from '../assets/pricing-hero.jpg';

export default function LandingPage() {
  const { t } = useTranslation('landing');
  const { t: tc } = useTranslation('common');
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

  // How it works steps
  const steps = [
    { number: '1', titleKey: 'howItWorks.steps.1.title', descKey: 'howItWorks.steps.1.description' },
    { number: '2', titleKey: 'howItWorks.steps.2.title', descKey: 'howItWorks.steps.2.description' },
    { number: '3', titleKey: 'howItWorks.steps.3.title', descKey: 'howItWorks.steps.3.description' },
    { number: '4', titleKey: 'howItWorks.steps.4.title', descKey: 'howItWorks.steps.4.description' },
  ];

  // Target audiences
  const audiences = [
    {
      titleKey: 'forWho.audiences.franchise.title',
      descKey: 'forWho.audiences.franchise.description',
      icon: BuildingStorefrontIcon,
      color: 'bg-purple-50 border-purple-200',
      iconColor: 'text-purple-600',
    },
    {
      titleKey: 'forWho.audiences.chain.title',
      descKey: 'forWho.audiences.chain.description',
      icon: UserGroupIcon,
      color: 'bg-blue-50 border-blue-200',
      iconColor: 'text-blue-600',
    },
    {
      titleKey: 'forWho.audiences.production.title',
      descKey: 'forWho.audiences.production.description',
      icon: CakeIcon,
      color: 'bg-pink-50 border-pink-200',
      iconColor: 'text-pink-600',
    },
    {
      titleKey: 'forWho.audiences.logistics.title',
      descKey: 'forWho.audiences.logistics.description',
      icon: TruckIcon,
      color: 'bg-green-50 border-green-200',
      iconColor: 'text-green-600',
    },
  ];

  // Features
  const features = [
    { nameKey: 'features.list.smartEngine', icon: CpuChipIcon },
    { nameKey: 'features.list.scenario', icon: ChartBarIcon },
    { nameKey: 'features.list.redFlag', icon: ExclamationTriangleIcon },
    { nameKey: 'features.list.cheating', icon: ShieldCheckIcon },
    { nameKey: 'features.list.franchise', icon: BuildingStorefrontIcon },
    { nameKey: 'features.list.kvkk', icon: CheckCircleIcon },
    { nameKey: 'features.list.cost', icon: CurrencyDollarIcon },
  ];

  // Benefits
  const benefits = [
    { textKey: 'benefits.list.reduce', icon: ArrowTrendingUpIcon },
    { textKey: 'benefits.list.time', icon: ClockIcon },
    { textKey: 'benefits.list.training', icon: AcademicCapIcon },
    { textKey: 'benefits.list.promotion', icon: ScaleIcon },
    { textKey: 'benefits.list.standards', icon: ShieldCheckIcon },
  ];

  // Solution metrics
  const solutionMetrics = [
    { labelKey: 'solution.metrics.jobFit', icon: CheckCircleIcon },
    { labelKey: 'solution.metrics.discipline', icon: ShieldCheckIcon },
    { labelKey: 'solution.metrics.stress', icon: ClockIcon },
    { labelKey: 'solution.metrics.risks', icon: ExclamationTriangleIcon },
    { labelKey: 'solution.metrics.culture', icon: UserGroupIcon },
  ];

  // Packages
  const packages = [
    { titleKey: 'packages.types.small.title', descKey: 'packages.types.small.description', icon: UserIcon },
    { titleKey: 'packages.types.chain.title', descKey: 'packages.types.chain.description', icon: BuildingStorefrontIcon },
    { titleKey: 'packages.types.franchise.title', descKey: 'packages.types.franchise.description', icon: BuildingOfficeIcon },
  ];

  // FAQ items
  const faqItems = [
    { questionKey: 'faq.items.1.question', answerKey: 'faq.items.1.answer' },
    { questionKey: 'faq.items.2.question', answerKey: 'faq.items.2.answer' },
    { questionKey: 'faq.items.3.question', answerKey: 'faq.items.3.answer' },
  ];

  // Pricing plans
  const pricingPlans = [
    {
      nameKey: 'pricing.plans.mini.name',
      assessmentsKey: 'pricing.plans.mini.assessments',
      priceTL: '9.900 TL',
      priceEUR: '199 €',
      popular: false,
    },
    {
      nameKey: 'pricing.plans.midi.name',
      assessmentsKey: 'pricing.plans.midi.assessments',
      priceTL: '19.900 TL',
      priceEUR: '399 €',
      popular: true,
    },
    {
      nameKey: 'pricing.plans.pro.name',
      assessmentsKey: 'pricing.plans.pro.assessments',
      priceTL: '49.900 TL',
      priceEUR: '999 €',
      popular: false,
    },
  ];

  // Company types for demo form
  const companyTypes = [
    { value: 'single', labelKey: 'demo.form.companyTypes.single' },
    { value: 'chain', labelKey: 'demo.form.companyTypes.chain' },
    { value: 'franchise', labelKey: 'demo.form.companyTypes.franchise' },
  ];

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
              <a href="#how-it-works" className="text-gray-600 hover:text-gray-900 transition-colors">{tc('nav.howItWorks')}</a>
              <a href="#features" className="text-gray-600 hover:text-gray-900 transition-colors">{tc('nav.features')}</a>
              <a href="#for-who" className="text-gray-600 hover:text-gray-900 transition-colors">{tc('nav.forWho')}</a>
              <a href="#pricing" className="text-gray-600 hover:text-gray-900 transition-colors">{tc('nav.pricing')}</a>
              <a href="#faq" className="text-gray-600 hover:text-gray-900 transition-colors">{tc('nav.faq')}</a>
              <Link to={localizedPath('/login')} className="text-gray-600 hover:text-gray-900 transition-colors">{tc('nav.login')}</Link>
              <LanguageSwitcher variant="light" showFullName={false} />
              <a href="#demo" className="btn-primary">{tc('nav.requestDemo')}</a>
            </div>
          </div>
        </div>
      </nav>

      {/* 1. HERO */}
      <section className="pt-32 pb-20 bg-gradient-to-b from-gray-50 to-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-4xl mx-auto">
            <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight">
              {t('hero.title')}{' '}
              <span className="text-primary-600">{t('hero.titleHighlight')}</span>
            </h1>
            <h2 className="mt-6 text-xl md:text-2xl text-gray-600 max-w-3xl mx-auto">
              {t('hero.subtitle')}
            </h2>
            <p className="mt-4 text-lg text-gray-500">
              {t('hero.description')}
            </p>
            <div className="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
              <a href="#demo" className="btn-primary text-lg px-8 py-3">
                {t('hero.cta')}
              </a>
              <a href="#how-it-works" className="btn-secondary text-lg px-8 py-3">
                {t('hero.howItWorks')}
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
                {t('problem.title')}{' '}
                <span className="text-red-400">{t('problem.titleHighlight')}</span>
              </h2>
              <p className="text-gray-300 mb-6">
                {t('problem.intro')}
              </p>
              <ul className="space-y-4 text-gray-300">
                <li className="flex items-start">
                  <ExclamationTriangleIcon className="h-6 w-6 text-red-400 mr-3 flex-shrink-0 mt-0.5" />
                  <span>{t('problem.points.1')}</span>
                </li>
                <li className="flex items-start">
                  <ExclamationTriangleIcon className="h-6 w-6 text-red-400 mr-3 flex-shrink-0 mt-0.5" />
                  <span>{t('problem.points.2')}</span>
                </li>
                <li className="flex items-start">
                  <ExclamationTriangleIcon className="h-6 w-6 text-red-400 mr-3 flex-shrink-0 mt-0.5" />
                  <span>{t('problem.points.3')}</span>
                </li>
                <li className="flex items-start">
                  <ExclamationTriangleIcon className="h-6 w-6 text-red-400 mr-3 flex-shrink-0 mt-0.5" />
                  <span>{t('problem.points.4')}</span>
                </li>
              </ul>
            </div>
            <div className="text-center">
              <div className="bg-red-500/20 border border-red-500/30 rounded-2xl p-8">
                <p className="text-gray-400 mb-2">{t('problem.costLabel')}</p>
                <p className="text-5xl md:text-6xl font-bold text-red-400">
                  {t('problem.costRange')}
                </p>
                <p className="mt-6 text-lg text-gray-300">
                  {t('problem.costNote')}
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
              {t('solution.title')} <span className="text-primary-600">{t('solution.titleHighlight')}</span>
            </h2>
            <p className="text-xl text-gray-600 max-w-3xl mx-auto">
              {t('solution.description')}
            </p>
          </div>

          <div className="mb-12">
            <p className="text-center text-lg font-medium text-gray-700 mb-8">{t('solution.metricsTitle')}</p>
            <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
              {solutionMetrics.map((item, index) => (
                <div key={index} className="text-center p-6 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                  <item.icon className="h-10 w-10 text-primary-600 mx-auto mb-3" />
                  <p className="font-medium text-gray-900">{t(item.labelKey)}</p>
                </div>
              ))}
            </div>
          </div>

          <div className="text-center">
            <p className="text-xl font-semibold text-gray-900">
              {t('solution.result')} <span className="text-primary-600">{t('solution.resultText')}</span>
            </p>
          </div>
        </div>
      </section>

      {/* 4. FOR WHO */}
      <section id="for-who" className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              {t('forWho.title')}
            </h2>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            {audiences.map((audience, index) => (
              <div key={index} className={`p-6 rounded-xl border-2 ${audience.color} hover:shadow-lg transition-shadow`}>
                <audience.icon className={`h-12 w-12 ${audience.iconColor} mb-4`} />
                <h3 className="text-lg font-semibold text-gray-900 mb-2">{t(audience.titleKey)}</h3>
                <p className="text-gray-600">{t(audience.descKey)}</p>
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
              {t('howItWorks.title')}
            </h2>
            <p className="text-xl text-gray-600">{t('howItWorks.subtitle')}</p>
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
                <h3 className="text-lg font-semibold text-gray-900 mb-2">{t(step.titleKey)}</h3>
                <p className="text-gray-600">{t(step.descKey)}</p>
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
              {t('features.title')}
            </h2>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            {features.map((feature, index) => (
              <div key={index} className="flex items-center p-4 bg-white/10 rounded-xl hover:bg-white/20 transition-colors">
                <feature.icon className="h-8 w-8 text-white mr-4 flex-shrink-0" />
                <span className="text-white font-medium">{t(feature.nameKey)}</span>
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
              {t('benefits.title')} <span className="text-primary-600">{t('benefits.titleHighlight')}</span>
            </h2>
          </div>

          <div className="grid md:grid-cols-3 lg:grid-cols-5 gap-6">
            {benefits.map((benefit, index) => (
              <div key={index} className="text-center p-6 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                <benefit.icon className="h-10 w-10 text-primary-600 mx-auto mb-3" />
                <p className="font-medium text-gray-900">{t(benefit.textKey)}</p>
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
              "{t('testimonial.quote')} <span className="text-primary-600 font-semibold">{t('testimonial.quoteHighlight')}</span> {t('testimonial.quoteEnd')}"
            </p>
            <footer className="mt-6 text-gray-500">
              {t('testimonial.source')}
            </footer>
          </blockquote>
        </div>
      </section>

      {/* 9. PLANS & PRICING */}
      <section id="pricing" className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          {/* Pricing Hero Image */}
          <div className="flex justify-center mb-12">
            <img
              src={pricingHero}
              alt="Plans & Pricing"
              className="w-full max-w-[1100px] rounded-2xl shadow-xl object-cover"
            />
          </div>

          {/* Title */}
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              {t('pricing.title')}
            </h2>
            <p className="text-xl text-gray-600">
              {t('pricing.subtitle')}
            </p>
          </div>

          {/* Pricing Cards */}
          <div className="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto mb-12">
            {pricingPlans.map((plan, index) => (
              <div
                key={index}
                className={`relative p-8 rounded-2xl border-2 transition-all ${
                  plan.popular
                    ? 'border-primary-500 bg-primary-50 shadow-lg scale-105'
                    : 'border-gray-200 bg-white hover:border-primary-200 hover:shadow-lg'
                }`}
              >
                {plan.popular && (
                  <div className="absolute -top-4 left-1/2 -translate-x-1/2">
                    <span className="inline-flex items-center gap-1 px-4 py-1 bg-primary-600 text-white text-sm font-semibold rounded-full">
                      <StarIcon className="h-4 w-4" />
                      {t('pricing.mostPopular')}
                    </span>
                  </div>
                )}
                <div className="text-center">
                  <h3 className="text-2xl font-bold text-gray-900 mb-2">{t(plan.nameKey)}</h3>
                  <p className="text-gray-600 mb-6">{t(plan.assessmentsKey)}</p>
                  <div className="mb-6">
                    <p className="text-3xl font-bold text-gray-900">{plan.priceTL}</p>
                    <p className="text-lg text-gray-500">{plan.priceEUR}</p>
                    <p className="text-sm text-gray-400 mt-1">{t('pricing.perMonth')}</p>
                  </div>
                </div>
              </div>
            ))}
          </div>

          {/* Included Features */}
          <div className="flex flex-col sm:flex-row items-center justify-center gap-6 mb-12">
            <div className="flex items-center gap-2 text-gray-700">
              <DocumentTextIcon className="h-5 w-5 text-primary-600" />
              <span>{t('pricing.features.pdfReports')}</span>
            </div>
            <div className="flex items-center gap-2 text-gray-700">
              <UserMinusIcon className="h-5 w-5 text-primary-600" />
              <span>{t('pricing.features.noPerUser')}</span>
            </div>
          </div>

          {/* CTA Buttons */}
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="#demo" className="btn-primary text-lg px-8 py-3">
              {tc('nav.requestDemo')}
            </a>
            <a href="#demo" className="btn-secondary text-lg px-8 py-3">
              {tc('nav.talkToSales')}
            </a>
          </div>
        </div>
      </section>

      {/* 10. PACKAGES */}
      <section className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              {t('packages.title')}
            </h2>
          </div>

          <div className="grid md:grid-cols-3 gap-8 max-w-4xl mx-auto">
            {packages.map((pkg, index) => (
              <div key={index} className="text-center p-8 bg-gray-50 rounded-xl border-2 border-gray-100 hover:border-primary-200 hover:shadow-lg transition-all">
                <pkg.icon className="h-12 w-12 text-primary-600 mx-auto mb-4" />
                <h3 className="text-xl font-semibold text-gray-900 mb-2">{t(pkg.titleKey)}</h3>
                <p className="text-gray-600">{t(pkg.descKey)}</p>
              </div>
            ))}
          </div>

          <p className="text-center text-gray-500 mt-8">
            {t('packages.note')}
          </p>
        </div>
      </section>

      {/* 11. DEMO CTA */}
      <section id="demo" className="py-20 bg-gradient-to-br from-primary-600 to-primary-800">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-3xl md:text-4xl font-bold text-white mb-4">
              {t('demo.title')}
            </h2>
            <p className="text-xl text-primary-100">
              {t('demo.subtitle')}
            </p>
          </div>

          {submitSuccess ? (
            <div className="bg-white rounded-2xl p-8 text-center">
              <CheckCircleIcon className="h-16 w-16 text-green-500 mx-auto mb-4" />
              <h3 className="text-2xl font-bold text-gray-900 mb-2">{t('demo.success.title')}</h3>
              <p className="text-gray-600">{t('demo.success.message')}</p>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="bg-white rounded-2xl p-8">
              <div className="grid md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    <BuildingOfficeIcon className="h-4 w-4 inline mr-1" />
                    {t('demo.form.companyName')}
                  </label>
                  <input
                    type="text"
                    name="company_name"
                    value={formData.company_name}
                    onChange={handleFormChange}
                    required
                    className="input"
                    placeholder={t('demo.form.companyName')}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    <UserIcon className="h-4 w-4 inline mr-1" />
                    {t('demo.form.contactName')}
                  </label>
                  <input
                    type="text"
                    name="contact_name"
                    value={formData.contact_name}
                    onChange={handleFormChange}
                    required
                    className="input"
                    placeholder={t('demo.form.contactName')}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    <EnvelopeIcon className="h-4 w-4 inline mr-1" />
                    {t('demo.form.email')}
                  </label>
                  <input
                    type="email"
                    name="email"
                    value={formData.email}
                    onChange={handleFormChange}
                    required
                    className="input"
                    placeholder="example@company.com"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    <PhoneIcon className="h-4 w-4 inline mr-1" />
                    {t('demo.form.phone')}
                  </label>
                  <input
                    type="tel"
                    name="phone"
                    value={formData.phone}
                    onChange={handleFormChange}
                    required
                    className="input"
                    placeholder="+1 555 123 4567"
                  />
                </div>
                <div className="md:col-span-2">
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    <BuildingStorefrontIcon className="h-4 w-4 inline mr-1" />
                    {t('demo.form.companyType')}
                  </label>
                  <select
                    name="company_type"
                    value={formData.company_type}
                    onChange={handleFormChange}
                    required
                    className="input"
                  >
                    <option value="">{t('demo.form.companyTypePlaceholder')}</option>
                    {companyTypes.map((type) => (
                      <option key={type.value} value={type.value}>{t(type.labelKey)}</option>
                    ))}
                  </select>
                </div>
              </div>
              <button
                type="submit"
                disabled={isSubmitting}
                className="mt-8 w-full btn-primary py-4 text-lg"
              >
                {isSubmitting ? t('demo.form.submitting') : t('demo.form.submit')}
              </button>
            </form>
          )}
        </div>
      </section>

      {/* 12. FAQ */}
      <section id="faq" className="py-20 bg-gray-50">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
              {t('faq.title')}
            </h2>
          </div>

          <div className="space-y-4">
            {faqItems.map((item, index) => (
              <div key={index} className="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <button
                  onClick={() => setOpenFaq(openFaq === index ? null : index)}
                  className="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors"
                >
                  <span className="font-semibold text-gray-900">{t(item.questionKey)}</span>
                  <ChevronDownIcon className={`h-5 w-5 text-gray-500 transition-transform ${openFaq === index ? 'rotate-180' : ''}`} />
                </button>
                {openFaq === index && (
                  <div className="px-6 pb-4 text-gray-600">
                    {t(item.answerKey)}
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 13. FOOTER */}
      <footer className="py-16 bg-gray-900 text-gray-400">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-4 gap-8 mb-12">
            <div>
              <span className="text-2xl font-bold text-white">Talent<span className="text-primary-400">QX</span></span>
              <p className="mt-4 text-sm">
                {t('footer.tagline')}
              </p>
              <p className="mt-2 text-sm italic">
                {t('footer.motto')}
              </p>
            </div>
            <div>
              <h4 className="font-semibold text-white mb-4">{t('footer.platform')}</h4>
              <ul className="space-y-2 text-sm">
                <li><a href="#how-it-works" className="hover:text-white transition-colors">{tc('nav.howItWorks')}</a></li>
                <li><a href="#features" className="hover:text-white transition-colors">{tc('nav.features')}</a></li>
                <li><a href="#for-who" className="hover:text-white transition-colors">{tc('nav.forWho')}</a></li>
                <li><a href="#demo" className="hover:text-white transition-colors">{tc('nav.requestDemo')}</a></li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold text-white mb-4">{t('footer.solutions')}</h4>
              <ul className="space-y-2 text-sm">
                <li><a href="#" className="hover:text-white transition-colors">{t('footer.solutionsItems.hiring')}</a></li>
                <li><a href="#" className="hover:text-white transition-colors">{t('footer.solutionsItems.assessment')}</a></li>
                <li><a href="#" className="hover:text-white transition-colors">{t('footer.solutionsItems.development')}</a></li>
                <li><a href="#" className="hover:text-white transition-colors">{t('footer.solutionsItems.franchise')}</a></li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold text-white mb-4">{t('footer.legal')}</h4>
              <ul className="space-y-2 text-sm">
                <li><a href="#" className="hover:text-white transition-colors">{t('footer.legalItems.about')}</a></li>
                <li><a href="#" className="hover:text-white transition-colors">{t('footer.legalItems.privacy')}</a></li>
                <li><a href="#" className="hover:text-white transition-colors">{t('footer.legalItems.privacyPolicy')}</a></li>
                <li><a href="#" className="hover:text-white transition-colors">{t('footer.legalItems.contact')}</a></li>
              </ul>
            </div>
          </div>
          <div className="pt-8 border-t border-gray-800 text-center text-sm">
            <p>{t('footer.copyright', { year: new Date().getFullYear() })}</p>
          </div>
        </div>
      </footer>
    </div>
  );
}
