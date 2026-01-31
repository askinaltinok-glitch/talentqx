import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { PlusIcon, MagnifyingGlassIcon } from '@heroicons/react/24/outline';
import { useTranslation } from 'react-i18next';
import api from '../services/api';
import type { Job, PositionTemplate } from '../types';
import { jobDetailPath } from '../routes';
import StatusBadge from '../components/StatusBadge';
import toast from 'react-hot-toast';

export default function Jobs() {
  const { t } = useTranslation('common');
  const [jobs, setJobs] = useState<Job[]>([]);
  const [templates, setTemplates] = useState<PositionTemplate[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [statusFilter, setStatusFilter] = useState<string>('');

  useEffect(() => {
    loadJobs();
    loadTemplates();
  }, [statusFilter]);

  const loadJobs = async () => {
    try {
      const params: Record<string, string> = {};
      if (statusFilter) params.status = statusFilter;

      const response = await api.get<Job[]>('/jobs', params);
      setJobs(response);
    } catch (error) {
      console.error('Failed to load jobs:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const loadTemplates = async () => {
    try {
      const response = await api.get<PositionTemplate[]>('/positions/templates');
      setTemplates(response);
    } catch (error) {
      console.error('Failed to load templates:', error);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('jobs.title')}</h1>
          <p className="text-gray-500 mt-1">
            {t('jobs.subtitle')}
          </p>
        </div>
        <button
          onClick={() => setShowCreateModal(true)}
          className="btn-primary"
        >
          <PlusIcon className="h-5 w-5 mr-2" />
          {t('jobs.newJob')}
        </button>
      </div>

      {/* Filters */}
      <div className="card p-4">
        <div className="flex flex-wrap gap-4">
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="input w-auto"
          >
            <option value="">{t('jobs.allStatuses')}</option>
            <option value="draft">{t('jobs.draft')}</option>
            <option value="active">{t('jobs.active')}</option>
            <option value="paused">{t('jobs.paused')}</option>
            <option value="closed">{t('jobs.closed')}</option>
          </select>
        </div>
      </div>

      {/* Jobs List */}
      {isLoading ? (
        <div className="flex items-center justify-center h-64">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
        </div>
      ) : jobs.length === 0 ? (
        <div className="card p-12 text-center">
          <MagnifyingGlassIcon className="h-12 w-12 text-gray-300 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">
            {t('jobs.noJobs')}
          </h3>
          <p className="text-gray-500 mb-4">
            {t('jobs.noJobsDesc')}
          </p>
          <button
            onClick={() => setShowCreateModal(true)}
            className="btn-primary"
          >
            <PlusIcon className="h-5 w-5 mr-2" />
            {t('jobs.createFirstJob')}
          </button>
        </div>
      ) : (
        <div className="grid gap-4">
          {jobs.map((job) => (
            <Link
              key={job.id}
              to={jobDetailPath(job.id)}
              className="card p-6 hover:shadow-md transition-shadow"
            >
              <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div className="flex-1">
                  <div className="flex items-center gap-3">
                    <h3 className="text-lg font-semibold text-gray-900">
                      {job.title}
                    </h3>
                    <StatusBadge status={job.status} size="sm" />
                  </div>
                  <p className="text-sm text-gray-500 mt-1">
                    {job.template?.name} &bull; {job.location || t('jobs.noLocationSpecified')}
                  </p>
                </div>
                <div className="flex items-center gap-6 text-sm">
                  <div className="text-center">
                    <p className="text-2xl font-bold text-gray-900">
                      {job.candidates_count || 0}
                    </p>
                    <p className="text-gray-500">{t('jobs.candidate')}</p>
                  </div>
                  <div className="text-center">
                    <p className="text-2xl font-bold text-primary-600">
                      {job.interviews_completed || 0}
                    </p>
                    <p className="text-gray-500">{t('jobs.interview')}</p>
                  </div>
                </div>
              </div>
            </Link>
          ))}
        </div>
      )}

      {/* Create Modal */}
      {showCreateModal && (
        <CreateJobModal
          templates={templates}
          onClose={() => setShowCreateModal(false)}
          onCreated={(job) => {
            setJobs([job, ...jobs]);
            setShowCreateModal(false);
            toast.success(t('jobs.jobCreated'));
          }}
        />
      )}
    </div>
  );
}

interface CreateJobModalProps {
  templates: PositionTemplate[];
  onClose: () => void;
  onCreated: (job: Job) => void;
}

function CreateJobModal({ templates, onClose, onCreated }: CreateJobModalProps) {
  const { t } = useTranslation('common');
  const [title, setTitle] = useState('');
  const [templateId, setTemplateId] = useState('');
  const [location, setLocation] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);

    try {
      const job = await api.post<Job>('/jobs', {
        title,
        template_id: templateId || null,
        location: location || null,
      });
      onCreated(job);
    } catch (error) {
      toast.error(api.getErrorMessage(error));
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div
        className="fixed inset-0 bg-gray-900/50"
        onClick={onClose}
      />
      <div className="flex min-h-full items-center justify-center p-4">
        <div className="relative bg-white rounded-xl shadow-xl max-w-lg w-full p-6">
          <h2 className="text-xl font-bold text-gray-900 mb-6">
            {t('jobs.createJobModal')}
          </h2>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('jobs.positionTemplate')}
              </label>
              <select
                value={templateId}
                onChange={(e) => setTemplateId(e.target.value)}
                className="input"
              >
                <option value="">{t('jobs.selectTemplate')}</option>
                {templates.map((tmpl) => (
                  <option key={tmpl.id} value={tmpl.id}>
                    {tmpl.name}
                  </option>
                ))}
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('jobs.jobTitleLabel')}
              </label>
              <input
                type="text"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                required
                className="input"
                placeholder={t('jobs.jobTitlePlaceholder')}
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('jobs.locationLabel')}
              </label>
              <input
                type="text"
                value={location}
                onChange={(e) => setLocation(e.target.value)}
                className="input"
                placeholder={t('jobs.locationPlaceholder')}
              />
            </div>

            <div className="flex justify-end gap-3 pt-4">
              <button
                type="button"
                onClick={onClose}
                className="btn-secondary"
              >
                {t('buttons.cancel')}
              </button>
              <button
                type="submit"
                disabled={isLoading || !title}
                className="btn-primary"
              >
                {isLoading ? t('jobs.creating') : t('jobs.create')}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
