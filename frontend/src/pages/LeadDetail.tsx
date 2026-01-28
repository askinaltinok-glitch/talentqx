import { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import {
  ArrowLeftIcon,
  PhoneIcon,
  EnvelopeIcon,
  BuildingOfficeIcon,
  MapPinIcon,
  CalendarIcon,
  ClockIcon,
  PlusIcon,
  CheckCircleIcon,
  ChatBubbleLeftIcon,
  VideoCameraIcon,
  LinkIcon,
} from '@heroicons/react/24/outline';
import { FireIcon as FireIconSolid, CheckCircleIcon as CheckCircleSolid } from '@heroicons/react/24/solid';
import api from '../services/api';
import { ROUTES } from '../routes';
import type {
  Lead,
  LeadActivity,
  LeadChecklistItem,
  LeadChecklistProgress,
  LeadStatus,
  LeadActivityType,
  LeadChecklistStage,
} from '../types';
import {
  LEAD_STATUS_LABELS,
  LEAD_STATUS_COLORS,
  LEAD_ACTIVITY_TYPE_LABELS,
  LEAD_CHECKLIST_STAGE_LABELS,
} from '../types';
import clsx from 'clsx';

const ACTIVITY_ICONS: Record<LeadActivityType, React.ComponentType<{ className?: string }>> = {
  note: ChatBubbleLeftIcon,
  call: PhoneIcon,
  email: EnvelopeIcon,
  meeting: VideoCameraIcon,
  demo: VideoCameraIcon,
  status_change: CheckCircleIcon,
  task: ClockIcon,
};

const STATUS_ORDER: LeadStatus[] = ['new', 'contacted', 'demo', 'pilot', 'negotiation', 'won', 'lost'];

export default function LeadDetail() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [lead, setLead] = useState<Lead | null>(null);
  const [checklistProgress, setChecklistProgress] = useState<LeadChecklistProgress | null>(null);
  const [loading, setLoading] = useState(true);
  const [showActivityModal, setShowActivityModal] = useState(false);
  const [showStatusModal, setShowStatusModal] = useState(false);
  const [activeTab, setActiveTab] = useState<'activities' | 'checklist'>('activities');

  useEffect(() => {
    if (id) {
      loadLead();
    }
  }, [id]);

  const loadLead = async () => {
    try {
      setLoading(true);
      const [leadData, progressData] = await Promise.all([
        api.get<Lead>(`/leads/${id}`),
        api.get<LeadChecklistProgress>(`/leads/${id}/checklist-progress`),
      ]);
      setLead(leadData);
      setChecklistProgress(progressData);
    } catch (error) {
      console.error('Error loading lead:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleToggleChecklist = async (item: LeadChecklistItem) => {
    try {
      await api.patch(`/leads/${id}/checklist/${item.id}`, {});
      loadLead();
    } catch (error) {
      console.error('Error toggling checklist:', error);
    }
  };

  const formatDate = (dateString: string | undefined) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('tr-TR', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    });
  };

  const formatCurrency = (value: number | undefined) => {
    if (!value) return '-';
    return new Intl.NumberFormat('tr-TR', {
      style: 'currency',
      currency: 'TRY',
      minimumFractionDigits: 0,
    }).format(value);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin h-8 w-8 border-4 border-primary-500 border-t-transparent rounded-full"></div>
      </div>
    );
  }

  if (!lead) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-500">Lead bulunamadı</p>
        <Link to={ROUTES.LEADS} className="text-primary-600 hover:underline mt-2 inline-block">
          Geri dön
        </Link>
      </div>
    );
  }

  const groupedChecklist: Partial<Record<LeadChecklistStage, LeadChecklistItem[]>> = {};
  lead.checklist_items?.forEach((item) => {
    if (!groupedChecklist[item.stage]) {
      groupedChecklist[item.stage] = [];
    }
    groupedChecklist[item.stage]!.push(item);
  });

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <button
          onClick={() => navigate(ROUTES.LEADS)}
          className="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg"
        >
          <ArrowLeftIcon className="h-5 w-5" />
        </button>
        <div className="flex-1">
          <div className="flex items-center gap-3">
            <h1 className="text-2xl font-bold text-gray-900">{lead.company_name}</h1>
            {lead.is_hot && <FireIconSolid className="h-6 w-6 text-orange-500" />}
            <span className={clsx('px-3 py-1 rounded-full text-sm font-medium', LEAD_STATUS_COLORS[lead.status])}>
              {LEAD_STATUS_LABELS[lead.status]}
            </span>
          </div>
          <p className="text-gray-500 mt-1">{lead.contact_name}</p>
        </div>
        <div className="flex items-center gap-2">
          <button
            onClick={() => setShowStatusModal(true)}
            className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm font-medium"
          >
            Durum Değiştir
          </button>
          <button
            onClick={() => setShowActivityModal(true)}
            className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm font-medium flex items-center gap-2"
          >
            <PlusIcon className="h-4 w-4" />
            Aktivite Ekle
          </button>
        </div>
      </div>

      {/* Pipeline Progress */}
      <div className="bg-white rounded-lg border p-4">
        <div className="flex items-center justify-between">
          {STATUS_ORDER.filter(s => s !== 'lost').map((status, index) => {
            const isActive = STATUS_ORDER.indexOf(lead.status) >= index;
            const isCurrent = lead.status === status;
            const isLost = lead.status === 'lost';

            return (
              <div key={status} className="flex items-center flex-1">
                <div
                  className={clsx(
                    'flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold',
                    isLost && status !== 'won'
                      ? 'bg-gray-200 text-gray-500'
                      : isCurrent
                      ? 'bg-primary-600 text-white ring-4 ring-primary-100'
                      : isActive
                      ? 'bg-green-500 text-white'
                      : 'bg-gray-200 text-gray-500'
                  )}
                >
                  {isActive && !isCurrent && !isLost ? (
                    <CheckCircleIcon className="h-5 w-5" />
                  ) : (
                    index + 1
                  )}
                </div>
                {index < STATUS_ORDER.filter(s => s !== 'lost').length - 1 && (
                  <div
                    className={clsx(
                      'flex-1 h-1 mx-2',
                      isActive && !isLost ? 'bg-green-500' : 'bg-gray-200'
                    )}
                  />
                )}
              </div>
            );
          })}
        </div>
        <div className="flex justify-between mt-2">
          {STATUS_ORDER.filter(s => s !== 'lost').map((status) => (
            <div key={status} className="text-xs text-gray-500 text-center" style={{ width: '14%' }}>
              {LEAD_STATUS_LABELS[status]}
            </div>
          ))}
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left Column - Lead Info */}
        <div className="space-y-6">
          {/* Contact Info Card */}
          <div className="bg-white rounded-lg border p-4">
            <h3 className="font-semibold text-gray-900 mb-4">İletişim Bilgileri</h3>
            <div className="space-y-3">
              <div className="flex items-center gap-3 text-sm">
                <EnvelopeIcon className="h-5 w-5 text-gray-400" />
                <a href={`mailto:${lead.email}`} className="text-primary-600 hover:underline">
                  {lead.email}
                </a>
              </div>
              {lead.phone && (
                <div className="flex items-center gap-3 text-sm">
                  <PhoneIcon className="h-5 w-5 text-gray-400" />
                  <a href={`tel:${lead.phone}`} className="text-primary-600 hover:underline">
                    {lead.phone}
                  </a>
                </div>
              )}
              {lead.city && (
                <div className="flex items-center gap-3 text-sm">
                  <MapPinIcon className="h-5 w-5 text-gray-400" />
                  <span className="text-gray-700">{lead.city}</span>
                </div>
              )}
              {lead.industry && (
                <div className="flex items-center gap-3 text-sm">
                  <BuildingOfficeIcon className="h-5 w-5 text-gray-400" />
                  <span className="text-gray-700">{lead.industry}</span>
                </div>
              )}
            </div>
          </div>

          {/* Company Info Card */}
          <div className="bg-white rounded-lg border p-4">
            <h3 className="font-semibold text-gray-900 mb-4">Firma Bilgileri</h3>
            <dl className="space-y-3 text-sm">
              <div className="flex justify-between">
                <dt className="text-gray-500">Firma Tipi</dt>
                <dd className="text-gray-900 font-medium">
                  {lead.company_type === 'single'
                    ? 'Tekil Şube'
                    : lead.company_type === 'chain'
                    ? 'Zincir'
                    : lead.company_type === 'franchise'
                    ? 'Franchise'
                    : '-'}
                </dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-gray-500">Firma Büyüklüğü</dt>
                <dd className="text-gray-900 font-medium">{lead.company_size || '-'}</dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-gray-500">Kaynak</dt>
                <dd className="text-gray-900 font-medium">{lead.source}</dd>
              </div>
              <div className="flex justify-between">
                <dt className="text-gray-500">Lead Skoru</dt>
                <dd className="text-gray-900 font-medium">
                  <span
                    className={clsx(
                      'px-2 py-0.5 rounded-full text-xs',
                      lead.lead_score >= 70
                        ? 'bg-green-100 text-green-700'
                        : lead.lead_score >= 40
                        ? 'bg-yellow-100 text-yellow-700'
                        : 'bg-gray-100 text-gray-600'
                    )}
                  >
                    {lead.lead_score}/100
                  </span>
                </dd>
              </div>
            </dl>
          </div>

          {/* Financial Info Card */}
          <div className="bg-white rounded-lg border p-4">
            <h3 className="font-semibold text-gray-900 mb-4">Finansal Bilgiler</h3>
            <dl className="space-y-3 text-sm">
              <div className="flex justify-between">
                <dt className="text-gray-500">Tahmini Değer</dt>
                <dd className="text-gray-900 font-medium">{formatCurrency(lead.estimated_value)}</dd>
              </div>
              {lead.actual_value && (
                <div className="flex justify-between">
                  <dt className="text-gray-500">Gerçek Değer</dt>
                  <dd className="text-gray-900 font-medium">{formatCurrency(lead.actual_value)}</dd>
                </div>
              )}
            </dl>
          </div>

          {/* Dates Card */}
          <div className="bg-white rounded-lg border p-4">
            <h3 className="font-semibold text-gray-900 mb-4">Önemli Tarihler</h3>
            <dl className="space-y-3 text-sm">
              <div className="flex justify-between">
                <dt className="text-gray-500">Oluşturulma</dt>
                <dd className="text-gray-900">{formatDate(lead.created_at)}</dd>
              </div>
              {lead.first_contact_at && (
                <div className="flex justify-between">
                  <dt className="text-gray-500">İlk İletişim</dt>
                  <dd className="text-gray-900">{formatDate(lead.first_contact_at)}</dd>
                </div>
              )}
              {lead.demo_scheduled_at && (
                <div className="flex justify-between">
                  <dt className="text-gray-500">Demo Tarihi</dt>
                  <dd className="text-gray-900">{formatDate(lead.demo_scheduled_at)}</dd>
                </div>
              )}
              {lead.next_follow_up_at && (
                <div className="flex justify-between">
                  <dt className="text-gray-500 flex items-center gap-1">
                    <ClockIcon className="h-4 w-4 text-yellow-500" />
                    Sonraki Takip
                  </dt>
                  <dd className="text-yellow-600 font-medium">{formatDate(lead.next_follow_up_at)}</dd>
                </div>
              )}
            </dl>
          </div>

          {/* Notes Card */}
          {lead.notes && (
            <div className="bg-white rounded-lg border p-4">
              <h3 className="font-semibold text-gray-900 mb-2">Notlar</h3>
              <p className="text-sm text-gray-700 whitespace-pre-wrap">{lead.notes}</p>
            </div>
          )}
        </div>

        {/* Right Column - Activities & Checklist */}
        <div className="lg:col-span-2 space-y-6">
          {/* Tabs */}
          <div className="bg-white rounded-lg border">
            <div className="border-b">
              <nav className="flex -mb-px">
                <button
                  onClick={() => setActiveTab('activities')}
                  className={clsx(
                    'px-6 py-3 text-sm font-medium border-b-2 transition-colors',
                    activeTab === 'activities'
                      ? 'border-primary-500 text-primary-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700'
                  )}
                >
                  Aktiviteler ({lead.activities?.length || 0})
                </button>
                <button
                  onClick={() => setActiveTab('checklist')}
                  className={clsx(
                    'px-6 py-3 text-sm font-medium border-b-2 transition-colors',
                    activeTab === 'checklist'
                      ? 'border-primary-500 text-primary-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700'
                  )}
                >
                  Checklist ({checklistProgress?.overall_percentage || 0}%)
                </button>
              </nav>
            </div>

            <div className="p-4">
              {activeTab === 'activities' ? (
                <div className="space-y-4">
                  {!lead.activities || lead.activities.length === 0 ? (
                    <div className="text-center py-8 text-gray-500">
                      <ChatBubbleLeftIcon className="h-12 w-12 mx-auto mb-2 text-gray-300" />
                      <p>Henüz aktivite yok</p>
                      <button
                        onClick={() => setShowActivityModal(true)}
                        className="mt-2 text-primary-600 hover:underline text-sm"
                      >
                        İlk aktiviteyi ekle
                      </button>
                    </div>
                  ) : (
                    <div className="relative">
                      <div className="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200" />
                      {lead.activities.map((activity) => (
                        <ActivityItem key={activity.id} activity={activity} />
                      ))}
                    </div>
                  )}
                </div>
              ) : (
                <div className="space-y-6">
                  {/* Progress Overview */}
                  {checklistProgress && (
                    <div className="bg-gray-50 rounded-lg p-4">
                      <div className="flex items-center justify-between mb-2">
                        <span className="text-sm font-medium text-gray-700">Genel İlerleme</span>
                        <span className="text-sm font-bold text-primary-600">
                          {checklistProgress.completed}/{checklistProgress.total}
                        </span>
                      </div>
                      <div className="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div
                          className="h-full bg-primary-500 transition-all"
                          style={{ width: `${checklistProgress.overall_percentage}%` }}
                        />
                      </div>
                    </div>
                  )}

                  {/* Checklist by Stage */}
                  {(['discovery', 'demo', 'pilot', 'closing'] as LeadChecklistStage[]).map((stage) => {
                    const items = groupedChecklist[stage] || [];
                    const stageProgress = checklistProgress?.by_stage[stage];
                    if (items.length === 0) return null;

                    return (
                      <div key={stage}>
                        <div className="flex items-center justify-between mb-2">
                          <h4 className="font-medium text-gray-900">
                            {LEAD_CHECKLIST_STAGE_LABELS[stage]}
                          </h4>
                          {stageProgress && (
                            <span className="text-xs text-gray-500">
                              {stageProgress.completed}/{stageProgress.total}
                            </span>
                          )}
                        </div>
                        <div className="space-y-2">
                          {items.map((item) => (
                            <button
                              key={item.id}
                              onClick={() => handleToggleChecklist(item)}
                              className={clsx(
                                'w-full flex items-center gap-3 p-3 rounded-lg border transition-colors text-left',
                                item.is_completed
                                  ? 'bg-green-50 border-green-200'
                                  : 'bg-white border-gray-200 hover:bg-gray-50'
                              )}
                            >
                              {item.is_completed ? (
                                <CheckCircleSolid className="h-5 w-5 text-green-500 flex-shrink-0" />
                              ) : (
                                <div className="h-5 w-5 rounded-full border-2 border-gray-300 flex-shrink-0" />
                              )}
                              <span
                                className={clsx(
                                  'text-sm',
                                  item.is_completed ? 'text-gray-500 line-through' : 'text-gray-900'
                                )}
                              >
                                {item.item}
                              </span>
                            </button>
                          ))}
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Modals */}
      {showActivityModal && (
        <ActivityModal
          leadId={lead.id}
          onClose={() => setShowActivityModal(false)}
          onCreated={() => {
            setShowActivityModal(false);
            loadLead();
          }}
        />
      )}

      {showStatusModal && (
        <StatusModal
          lead={lead}
          onClose={() => setShowStatusModal(false)}
          onUpdated={() => {
            setShowStatusModal(false);
            loadLead();
          }}
        />
      )}
    </div>
  );
}

// Activity Item Component
function ActivityItem({ activity }: { activity: LeadActivity }) {
  const Icon = ACTIVITY_ICONS[activity.type] || ChatBubbleLeftIcon;

  const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString('tr-TR', {
      day: 'numeric',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  return (
    <div className="relative pl-10 pb-6">
      <div
        className={clsx(
          'absolute left-0 w-8 h-8 rounded-full flex items-center justify-center',
          activity.type === 'status_change'
            ? 'bg-purple-100'
            : activity.type === 'demo'
            ? 'bg-orange-100'
            : activity.type === 'call'
            ? 'bg-green-100'
            : activity.type === 'email'
            ? 'bg-blue-100'
            : 'bg-gray-100'
        )}
      >
        <Icon
          className={clsx(
            'h-4 w-4',
            activity.type === 'status_change'
              ? 'text-purple-600'
              : activity.type === 'demo'
              ? 'text-orange-600'
              : activity.type === 'call'
              ? 'text-green-600'
              : activity.type === 'email'
              ? 'text-blue-600'
              : 'text-gray-600'
          )}
        />
      </div>

      <div className="bg-gray-50 rounded-lg p-3">
        <div className="flex items-center justify-between mb-1">
          <span className="text-sm font-medium text-gray-900">
            {LEAD_ACTIVITY_TYPE_LABELS[activity.type]}
            {activity.subject && `: ${activity.subject}`}
          </span>
          <span className="text-xs text-gray-500">{formatDateTime(activity.created_at)}</span>
        </div>

        {activity.type === 'status_change' && (
          <p className="text-sm text-gray-600">
            {LEAD_STATUS_LABELS[activity.old_status as LeadStatus] || activity.old_status} →{' '}
            <span className="font-medium">
              {LEAD_STATUS_LABELS[activity.new_status as LeadStatus] || activity.new_status}
            </span>
          </p>
        )}

        {activity.description && (
          <p className="text-sm text-gray-700 mt-1 whitespace-pre-wrap">{activity.description}</p>
        )}

        {activity.meeting_link && (
          <a
            href={activity.meeting_link}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-1 mt-2 text-sm text-primary-600 hover:underline"
          >
            <LinkIcon className="h-4 w-4" />
            Toplantı Linki
          </a>
        )}

        {activity.scheduled_at && (
          <p className="text-xs text-gray-500 mt-1">
            <CalendarIcon className="h-3.5 w-3.5 inline mr-1" />
            {new Date(activity.scheduled_at).toLocaleString('tr-TR')}
            {activity.duration_minutes && ` (${activity.duration_minutes} dk)`}
          </p>
        )}

        {activity.user && (
          <p className="text-xs text-gray-400 mt-2">
            {activity.user.full_name}
          </p>
        )}
      </div>
    </div>
  );
}

// Activity Modal Component
function ActivityModal({
  leadId,
  onClose,
  onCreated,
}: {
  leadId: string;
  onClose: () => void;
  onCreated: () => void;
}) {
  const [formData, setFormData] = useState({
    type: 'note' as LeadActivityType,
    subject: '',
    description: '',
    meeting_link: '',
    scheduled_at: '',
    duration_minutes: '',
    due_at: '',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const data: Record<string, unknown> = {
        type: formData.type,
        subject: formData.subject || null,
        description: formData.description || null,
      };

      if (formData.meeting_link) data.meeting_link = formData.meeting_link;
      if (formData.scheduled_at) data.scheduled_at = formData.scheduled_at;
      if (formData.duration_minutes) data.duration_minutes = parseInt(formData.duration_minutes);
      if (formData.due_at) data.due_at = formData.due_at;

      await api.post(`/leads/${leadId}/activities`, data);
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
          <h2 className="text-xl font-bold text-gray-900 mb-4">Aktivite Ekle</h2>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Aktivite Tipi *
              </label>
              <select
                value={formData.type}
                onChange={(e) => setFormData({ ...formData, type: e.target.value as LeadActivityType })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              >
                <option value="note">Not</option>
                <option value="call">Arama</option>
                <option value="email">E-posta</option>
                <option value="meeting">Toplantı</option>
                <option value="demo">Demo</option>
                <option value="task">Görev</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Konu
              </label>
              <input
                type="text"
                value={formData.subject}
                onChange={(e) => setFormData({ ...formData, subject: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Açıklama
              </label>
              <textarea
                rows={3}
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              />
            </div>

            {['meeting', 'demo', 'call'].includes(formData.type) && (
              <>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">
                    Toplantı Linki (Zoom/Meet)
                  </label>
                  <input
                    type="url"
                    value={formData.meeting_link}
                    onChange={(e) => setFormData({ ...formData, meeting_link: e.target.value })}
                    placeholder="https://zoom.us/j/..."
                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                  />
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Tarih & Saat
                    </label>
                    <input
                      type="datetime-local"
                      value={formData.scheduled_at}
                      onChange={(e) => setFormData({ ...formData, scheduled_at: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      Süre (dk)
                    </label>
                    <input
                      type="number"
                      value={formData.duration_minutes}
                      onChange={(e) => setFormData({ ...formData, duration_minutes: e.target.value })}
                      placeholder="30"
                      className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    />
                  </div>
                </div>
              </>
            )}

            {formData.type === 'task' && (
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Teslim Tarihi
                </label>
                <input
                  type="datetime-local"
                  value={formData.due_at}
                  onChange={(e) => setFormData({ ...formData, due_at: e.target.value })}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
            )}

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
                İptal
              </button>
              <button
                type="submit"
                disabled={loading}
                className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50"
              >
                {loading ? 'Kaydediliyor...' : 'Kaydet'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}

// Status Modal Component
function StatusModal({
  lead,
  onClose,
  onUpdated,
}: {
  lead: Lead;
  onClose: () => void;
  onUpdated: () => void;
}) {
  const [selectedStatus, setSelectedStatus] = useState<LeadStatus>(lead.status);
  const [lostReason, setLostReason] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      await api.patch(`/leads/${lead.id}/status`, {
        status: selectedStatus,
        lost_reason: selectedStatus === 'lost' ? lostReason : null,
      });
      onUpdated();
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
        <div className="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
          <h2 className="text-xl font-bold text-gray-900 mb-4">Durum Değiştir</h2>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-2 gap-2">
              {STATUS_ORDER.map((status) => (
                <button
                  key={status}
                  type="button"
                  onClick={() => setSelectedStatus(status)}
                  className={clsx(
                    'p-3 rounded-lg border text-sm font-medium transition-colors',
                    selectedStatus === status
                      ? 'border-primary-500 bg-primary-50 text-primary-700'
                      : 'border-gray-200 hover:bg-gray-50'
                  )}
                >
                  <span className={clsx('inline-block px-2 py-0.5 rounded-full text-xs mb-1', LEAD_STATUS_COLORS[status])}>
                    {LEAD_STATUS_LABELS[status]}
                  </span>
                </button>
              ))}
            </div>

            {selectedStatus === 'lost' && (
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Kayıp Nedeni *
                </label>
                <textarea
                  rows={3}
                  required
                  value={lostReason}
                  onChange={(e) => setLostReason(e.target.value)}
                  placeholder="Müşteri neden kaybedildi?"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                />
              </div>
            )}

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
                İptal
              </button>
              <button
                type="submit"
                disabled={loading || selectedStatus === lead.status}
                className="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50"
              >
                {loading ? 'Kaydediliyor...' : 'Güncelle'}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
