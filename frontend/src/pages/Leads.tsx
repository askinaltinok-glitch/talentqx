import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import {
  PlusIcon,
  PhoneIcon,
  EnvelopeIcon,
  ClockIcon,
  FunnelIcon,
  MagnifyingGlassIcon,
  ChevronRightIcon,
  BuildingOfficeIcon,
  CalendarIcon,
  ExclamationCircleIcon,
} from '@heroicons/react/24/outline';
import { FireIcon as FireIconSolid } from '@heroicons/react/24/solid';
import api from '../services/api';
import { leadDetailPath } from '../routes';
import type { Lead, LeadPipelineStats, LeadStatus, FollowUpStats } from '../types';
import { LEAD_STATUS_COLORS } from '../types';
import clsx from 'clsx';

const PIPELINE_STAGES: LeadStatus[] = ['new', 'contacted', 'demo', 'pilot', 'negotiation', 'won', 'lost'];

type FollowUpBadge = { labelKey: string; color: string; priority: number } | null;

const getFollowUpBadge = (nextFollowUpAt: string | undefined): FollowUpBadge => {
  if (!nextFollowUpAt) return null;
  const followUpDate = new Date(nextFollowUpAt);
  const now = new Date();
  const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const endOfToday = new Date(startOfToday.getTime() + 24 * 60 * 60 * 1000 - 1);
  const endOfWeek = new Date(startOfToday.getTime() + 7 * 24 * 60 * 60 * 1000);

  if (followUpDate < startOfToday) {
    return { labelKey: 'followUp.overdue', color: 'bg-red-100 text-red-700 border-red-200', priority: 1 };
  } else if (followUpDate <= endOfToday) {
    return { labelKey: 'followUp.today', color: 'bg-yellow-100 text-yellow-700 border-yellow-200', priority: 2 };
  } else if (followUpDate <= endOfWeek) {
    return { labelKey: 'followUp.thisWeek', color: 'bg-blue-100 text-blue-700 border-blue-200', priority: 3 };
  }
  return null;
};

type FilterType = LeadStatus | 'all' | 'hot' | 'follow_up' | 'overdue';

export default function Leads() {
  const { t } = useTranslation('sales');
  const { i18n } = useTranslation();
  const [leads, setLeads] = useState<Lead[]>([]);
  const [stats, setStats] = useState<LeadPipelineStats | null>(null);
  const [followUpStats, setFollowUpStats] = useState<FollowUpStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [activeFilter, setActiveFilter] = useState<FilterType>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [showNewLeadModal, setShowNewLeadModal] = useState(false);

  useEffect(() => {
    loadData();
  }, [activeFilter, searchQuery]);

  const loadData = async () => {
    try {
      setLoading(true);
      const params: Record<string, unknown> = {};

      if (activeFilter !== 'all' && activeFilter !== 'hot' && activeFilter !== 'follow_up' && activeFilter !== 'overdue') {
        params.status = activeFilter;
      }
      if (activeFilter === 'hot') {
        params.is_hot = true;
      }
      if (activeFilter === 'follow_up' || activeFilter === 'overdue') {
        params.needs_follow_up = true;
      }
      if (searchQuery) {
        params.search = searchQuery;
      }

      const [leadsData, statsData, followUpData] = await Promise.all([
        api.get<Lead[]>('/leads', params),
        api.get<LeadPipelineStats>('/leads/pipeline-stats'),
        api.get<FollowUpStats>('/leads/follow-up-stats'),
      ]);

      // If overdue filter, filter client-side for overdue only
      let filteredLeads = leadsData;
      if (activeFilter === 'overdue') {
        const startOfToday = new Date();
        startOfToday.setHours(0, 0, 0, 0);
        filteredLeads = leadsData.filter(lead => {
          if (!lead.next_follow_up_at) return false;
          return new Date(lead.next_follow_up_at) < startOfToday;
        });
      }

      setLeads(filteredLeads);
      setStats(statsData);
      setFollowUpStats(followUpData);
    } catch (error) {
      console.error('Error loading leads:', error);
    } finally {
      setLoading(false);
    }
  };

  const formatCurrency = (value: number | undefined) => {
    if (!value) return '-';
    return new Intl.NumberFormat(i18n.language === 'tr' ? 'tr-TR' : 'en-US', {
      style: 'currency',
      currency: i18n.language === 'tr' ? 'TRY' : 'EUR',
      minimumFractionDigits: 0,
    }).format(value);
  };

  const formatDate = (dateString: string | undefined) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString(i18n.language, {
      day: 'numeric',
      month: 'short',
    });
  };

  const getDaysAgo = (dateString: string) => {
    const days = Math.floor((Date.now() - new Date(dateString).getTime()) / (1000 * 60 * 60 * 24));
    if (days === 0) return t('followUp.today');
    if (days === 1) return i18n.language === 'tr' ? 'Dün' : 'Yesterday';
    return `${days} ${i18n.language === 'tr' ? 'gün önce' : 'days ago'}`;
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('leads.title')}</h1>
          <p className="text-gray-500 mt-1">{t('leads.subtitle')}</p>
        </div>
        <button
          onClick={() => setShowNewLeadModal(true)}
          className="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
        >
          <PlusIcon className="h-5 w-5" />
          {t('leads.newLead')}
        </button>
      </div>

      {/* Pipeline Stats */}
      {stats && (
        <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
          {PIPELINE_STAGES.map((status) => {
            const stageStats = stats.by_status[status];
            const count = stageStats?.count || 0;
            const value = stageStats?.total_value || 0;

            return (
              <button
                key={status}
                onClick={() => setActiveFilter(status)}
                className={clsx(
                  'p-3 rounded-lg text-left transition-all',
                  activeFilter === status
                    ? 'ring-2 ring-primary-500 bg-primary-50'
                    : 'bg-white hover:bg-gray-50 border border-gray-200'
                )}
              >
                <span className={clsx('text-xs font-medium px-2 py-0.5 rounded-full', LEAD_STATUS_COLORS[status])}>
                  {t(`status.${status}`)}
                </span>
                <div className="mt-2">
                  <span className="text-2xl font-bold text-gray-900">{count}</span>
                </div>
                <div className="text-xs text-gray-500 truncate">
                  {formatCurrency(value)}
                </div>
              </button>
            );
          })}
        </div>
      )}

      {/* Quick Stats */}
      {stats && (
        <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
          <div className="bg-white rounded-lg border p-4">
            <div className="flex items-center gap-2 text-gray-500 text-sm">
              <BuildingOfficeIcon className="h-4 w-4" />
              {t('stats.totalLeads')}
            </div>
            <div className="text-2xl font-bold mt-1">{stats.total_leads}</div>
          </div>
          <button
            onClick={() => setActiveFilter('hot')}
            className={clsx(
              'bg-white rounded-lg border p-4 text-left transition-all',
              activeFilter === 'hot' && 'ring-2 ring-orange-500'
            )}
          >
            <div className="flex items-center gap-2 text-orange-500 text-sm">
              <FireIconSolid className="h-4 w-4" />
              {t('stats.hotLeads')}
            </div>
            <div className="text-2xl font-bold mt-1">{stats.hot_leads}</div>
          </button>
          <button
            onClick={() => setActiveFilter('overdue')}
            className={clsx(
              'bg-white rounded-lg border p-4 text-left transition-all',
              activeFilter === 'overdue' && 'ring-2 ring-red-500'
            )}
          >
            <div className="flex items-center gap-2 text-red-600 text-sm">
              <ExclamationCircleIcon className="h-4 w-4" />
              {t('stats.overdue')}
            </div>
            <div className="text-2xl font-bold mt-1">{followUpStats?.overdue || 0}</div>
          </button>
          <button
            onClick={() => setActiveFilter('follow_up')}
            className={clsx(
              'bg-white rounded-lg border p-4 text-left transition-all',
              activeFilter === 'follow_up' && 'ring-2 ring-yellow-500'
            )}
          >
            <div className="flex items-center gap-2 text-yellow-600 text-sm">
              <ClockIcon className="h-4 w-4" />
              {t('stats.followUpNeeded')}
            </div>
            <div className="text-2xl font-bold mt-1">{stats.needs_follow_up}</div>
          </button>
          <div className="bg-white rounded-lg border p-4">
            <div className="flex items-center gap-2 text-green-600 text-sm">
              <CalendarIcon className="h-4 w-4" />
              {t('stats.wonThisMonth')}
            </div>
            <div className="text-2xl font-bold mt-1">{stats.won_this_month}</div>
            <div className="text-xs text-gray-500">%{stats.conversion_rate} {t('stats.conversion')}</div>
          </div>
        </div>
      )}

      {/* Filters & Search */}
      <div className="flex flex-col sm:flex-row gap-3">
        <div className="relative flex-1">
          <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
          <input
            type="text"
            placeholder={t('filters.search')}
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          />
        </div>
        <button
          onClick={() => setActiveFilter('all')}
          className={clsx(
            'flex items-center gap-2 px-4 py-2 rounded-lg border transition-colors',
            activeFilter === 'all'
              ? 'bg-primary-50 border-primary-500 text-primary-700'
              : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'
          )}
        >
          <FunnelIcon className="h-5 w-5" />
          {t('filters.all')}
        </button>
      </div>

      {/* Leads List */}
      <div className="bg-white rounded-lg border overflow-hidden">
        {loading ? (
          <div className="p-8 text-center text-gray-500">
            <div className="animate-spin h-8 w-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto mb-4"></div>
            {t('leads.loading')}
          </div>
        ) : leads.length === 0 ? (
          <div className="p-8 text-center text-gray-500">
            <BuildingOfficeIcon className="h-12 w-12 mx-auto mb-4 text-gray-300" />
            <p className="font-medium">{t('leads.noLeads')}</p>
            <p className="text-sm mt-1">{t('leads.noLeadsHint')}</p>
          </div>
        ) : (
          <div className="divide-y divide-gray-100">
            {leads.map((lead) => (
              <Link
                key={lead.id}
                to={leadDetailPath(lead.id)}
                className="flex items-center gap-4 p-4 hover:bg-gray-50 transition-colors"
              >
                {/* Lead Score Circle */}
                <div
                  className={clsx(
                    'flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center text-sm font-bold',
                    lead.lead_score >= 70
                      ? 'bg-green-100 text-green-700'
                      : lead.lead_score >= 40
                      ? 'bg-yellow-100 text-yellow-700'
                      : 'bg-gray-100 text-gray-600'
                  )}
                >
                  {lead.lead_score}
                </div>

                {/* Lead Info */}
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2">
                    <span className="font-semibold text-gray-900 truncate">
                      {lead.company_name}
                    </span>
                    {lead.is_hot && (
                      <FireIconSolid className="h-4 w-4 text-orange-500 flex-shrink-0" />
                    )}
                    <span className={clsx('text-xs px-2 py-0.5 rounded-full', LEAD_STATUS_COLORS[lead.status])}>
                      {t(`status.${lead.status}`)}
                    </span>
                  </div>
                  <div className="flex items-center gap-3 mt-1 text-sm text-gray-500">
                    <span>{lead.contact_name}</span>
                    {lead.email && (
                      <span className="flex items-center gap-1">
                        <EnvelopeIcon className="h-3.5 w-3.5" />
                        {lead.email}
                      </span>
                    )}
                    {lead.phone && (
                      <span className="flex items-center gap-1">
                        <PhoneIcon className="h-3.5 w-3.5" />
                        {lead.phone}
                      </span>
                    )}
                  </div>
                  {lead.next_follow_up_at && (() => {
                    const badge = getFollowUpBadge(lead.next_follow_up_at);
                    return (
                      <div className="flex items-center gap-2 mt-1">
                        {badge && (
                          <span className={clsx('text-xs px-2 py-0.5 rounded-full border', badge.color)}>
                            {t(badge.labelKey)}
                          </span>
                        )}
                        <span className="text-xs text-gray-500 flex items-center gap-1">
                          <ClockIcon className="h-3.5 w-3.5" />
                          {t('followUp.follow')}: {formatDate(lead.next_follow_up_at)}
                        </span>
                      </div>
                    );
                  })()}
                </div>

                {/* Value & Date */}
                <div className="flex-shrink-0 text-right">
                  {lead.estimated_value && (
                    <div className="font-semibold text-gray-900">
                      {formatCurrency(lead.estimated_value)}
                    </div>
                  )}
                  <div className="text-xs text-gray-500">
                    {getDaysAgo(lead.created_at)}
                  </div>
                  {lead.activities_count !== undefined && lead.activities_count > 0 && (
                    <div className="text-xs text-gray-400 mt-1">
                      {lead.activities_count} {lead.activities_count === 1 ? t('leads.activity') : t('leads.activities')}
                    </div>
                  )}
                </div>

                <ChevronRightIcon className="h-5 w-5 text-gray-400 flex-shrink-0" />
              </Link>
            ))}
          </div>
        )}
      </div>

      {/* New Lead Modal */}
      {showNewLeadModal && (
        <NewLeadModal
          onClose={() => setShowNewLeadModal(false)}
          onCreated={() => {
            setShowNewLeadModal(false);
            loadData();
          }}
        />
      )}
    </div>
  );
}

// New Lead Modal Component
function NewLeadModal({
  onClose,
  onCreated,
}: {
  onClose: () => void;
  onCreated: () => void;
}) {
  const { t } = useTranslation('sales');
  const [formData, setFormData] = useState({
    company_name: '',
    contact_name: '',
    email: '',
    phone: '',
    company_type: '',
    company_size: '',
    industry: '',
    city: '',
    source: 'website',
    notes: '',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      await api.post('/leads', formData);
      onCreated();
    } catch (err) {
      setError(api.getErrorMessage(err));
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="flex min-h-full items-center justify-center p-4">
        <div className="fixed inset-0 bg-black/50" onClick={onClose} />
        <div className="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6">
          <h2 className="text-xl font-bold text-gray-900 mb-4">{t('modal.newLead.title')}</h2>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {t('modal.newLead.companyName')} *
                </label>
                <input
                  type="text"
                  required
                  value={formData.company_name}
                  onChange={(e) => setFormData({ ...formData, company_name: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {t('modal.newLead.contactName')} *
                </label>
                <input
                  type="text"
                  required
                  value={formData.contact_name}
                  onChange={(e) => setFormData({ ...formData, contact_name: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {t('modal.newLead.email')} *
                </label>
                <input
                  type="email"
                  required
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {t('modal.newLead.phone')}
                </label>
                <input
                  type="tel"
                  value={formData.phone}
                  onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {t('modal.newLead.companyType')}
                </label>
                <select
                  value={formData.company_type}
                  onChange={(e) => setFormData({ ...formData, company_type: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                  <option value="">{t('modal.newLead.select')}</option>
                  <option value="single">{t('company.types.single')}</option>
                  <option value="chain">{t('company.types.chain')}</option>
                  <option value="franchise">{t('company.types.franchise')}</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {t('modal.newLead.companySize')}
                </label>
                <select
                  value={formData.company_size}
                  onChange={(e) => setFormData({ ...formData, company_size: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                  <option value="">{t('modal.newLead.select')}</option>
                  <option value="1-10">{t('modal.newLead.sizes.1-10')}</option>
                  <option value="11-50">{t('modal.newLead.sizes.11-50')}</option>
                  <option value="51-200">{t('modal.newLead.sizes.51-200')}</option>
                  <option value="200+">{t('modal.newLead.sizes.200+')}</option>
                </select>
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {t('modal.newLead.industry')}
                </label>
                <input
                  type="text"
                  value={formData.industry}
                  onChange={(e) => setFormData({ ...formData, industry: e.target.value })}
                  placeholder={t('modal.newLead.industryPlaceholder')}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {t('modal.newLead.city')}
                </label>
                <input
                  type="text"
                  value={formData.city}
                  onChange={(e) => setFormData({ ...formData, city: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('modal.newLead.source')}
              </label>
              <select
                value={formData.source}
                onChange={(e) => setFormData({ ...formData, source: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              >
                <option value="website">{t('modal.newLead.sources.website')}</option>
                <option value="referral">{t('modal.newLead.sources.referral')}</option>
                <option value="linkedin">{t('modal.newLead.sources.linkedin')}</option>
                <option value="cold_call">{t('modal.newLead.sources.coldCall')}</option>
                <option value="event">{t('modal.newLead.sources.event')}</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('modal.newLead.notes')}
              </label>
              <textarea
                rows={3}
                value={formData.notes}
                onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>

            {error && (
              <div className="p-3 bg-red-50 text-red-700 rounded-lg text-sm">
                {error}
              </div>
            )}

            <div className="flex justify-end gap-3 pt-4">
              <button
                type="button"
                onClick={onClose}
                className="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                {t('modal.newLead.select') === 'Seçiniz' ? 'İptal' : 'Cancel'}
              </button>
              <button
                type="submit"
                disabled={loading}
                className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50"
              >
                {loading ? (t('modal.newLead.select') === 'Seçiniz' ? 'Kaydediliyor...' : 'Saving...') : (t('modal.newLead.select') === 'Seçiniz' ? 'Kaydet' : 'Save')}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
