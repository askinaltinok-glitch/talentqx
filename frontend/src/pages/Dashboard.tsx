import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  BriefcaseIcon,
  UsersIcon,
  VideoCameraIcon,
  ChartBarIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  ClockIcon,
  EnvelopeIcon,
  ChevronRightIcon,
} from '@heroicons/react/24/outline';
import { useTranslation } from 'react-i18next';
import api from '../services/api';
import { useAuthStore } from '../stores/authStore';
import type { DashboardStats, FollowUpStats } from '../types';
import { LEAD_STATUS_COLORS } from '../types';
import { leadDetailPath, localizedPath } from '../routes';
import ScoreCircle from '../components/ScoreCircle';
import clsx from 'clsx';

export default function Dashboard() {
  const { t } = useTranslation('common');
  const { t: tSales } = useTranslation('sales');
  const { user } = useAuthStore();
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [followUpStats, setFollowUpStats] = useState<FollowUpStats | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    loadStats();
  }, []);

  const loadStats = async () => {
    try {
      // Load dashboard stats (available for all users)
      const dashboardData = await api.get<DashboardStats>('/dashboard/stats');
      setStats(dashboardData);

      // Load follow-up stats only for platform admins
      if (user?.is_platform_admin) {
        try {
          const followUpData = await api.get<FollowUpStats>('/leads/follow-up-stats');
          setFollowUpStats(followUpData);
        } catch {
          // Silently fail - follow-up widget just won't show
        }
      }
    } catch (error) {
      console.error('Failed to load stats:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const getFollowUpBadge = (nextFollowUpAt: string | undefined) => {
    if (!nextFollowUpAt) return null;
    const followUpDate = new Date(nextFollowUpAt);
    const now = new Date();
    const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const endOfToday = new Date(startOfToday.getTime() + 24 * 60 * 60 * 1000 - 1);

    if (followUpDate < startOfToday) {
      return { label: t('dashboard.overdue'), color: 'bg-red-100 text-red-700' };
    } else if (followUpDate <= endOfToday) {
      return { label: t('dashboard.today'), color: 'bg-yellow-100 text-yellow-700' };
    }
    return null;
  };

  const copyToClipboard = async (text: string, e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    try {
      await navigator.clipboard.writeText(text);
    } catch (err) {
      console.error('Failed to copy:', err);
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (!stats) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-500">{t('dashboard.loadError')}</p>
      </div>
    );
  }

  const statCards = [
    {
      name: t('dashboard.totalJobs'),
      value: stats.total_jobs,
      subValue: `${stats.active_jobs} ${t('dashboard.active')}`,
      icon: BriefcaseIcon,
      color: 'bg-blue-500',
      link: localizedPath('/app/jobs'),
    },
    {
      name: t('dashboard.totalCandidates'),
      value: stats.total_candidates,
      subValue: `${stats.by_status.hired || 0} ${t('dashboard.hired')}`,
      icon: UsersIcon,
      color: 'bg-green-500',
      link: localizedPath('/app/candidates'),
    },
    {
      name: t('dashboard.completedInterviews'),
      value: stats.interviews_completed,
      subValue: `${stats.interviews_pending} ${t('dashboard.pending')}`,
      icon: VideoCameraIcon,
      color: 'bg-purple-500',
      link: localizedPath('/app/candidates?status=interview_completed'),
    },
    {
      name: t('dashboard.averageScore'),
      value: stats.average_score.toFixed(1),
      subValue: `%${(stats.hire_rate * 100).toFixed(0)} ${t('dashboard.hireRate')}`,
      icon: ChartBarIcon,
      color: 'bg-yellow-500',
      link: localizedPath('/app/candidates'),
    },
  ];

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">{t('dashboard.title')}</h1>
        <p className="text-gray-500 mt-1">
          {t('dashboard.subtitle')}
        </p>
      </div>

      {/* Stat Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {statCards.map((stat) => (
          <Link
            key={stat.name}
            to={stat.link}
            className="card p-6 hover:shadow-md transition-shadow"
          >
            <div className="flex items-center gap-4">
              <div className={`p-3 rounded-lg ${stat.color}`}>
                <stat.icon className="h-6 w-6 text-white" />
              </div>
              <div>
                <p className="text-sm text-gray-500">{stat.name}</p>
                <p className="text-2xl font-bold text-gray-900">
                  {stat.value}
                </p>
                <p className="text-xs text-gray-400">{stat.subValue}</p>
              </div>
            </div>
          </Link>
        ))}
      </div>

      {/* Today's Follow-ups Widget - Platform Admin Only */}
      {user?.is_platform_admin && followUpStats && followUpStats.total_due > 0 && (
        <div className="card p-6 border-l-4 border-l-yellow-500">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-2">
              <ClockIcon className="h-5 w-5 text-yellow-600" />
              <h2 className="text-lg font-semibold text-gray-900">
                {t('dashboard.todayFollowUps')}
              </h2>
            </div>
            <div className="flex items-center gap-2 text-sm">
              {followUpStats.overdue > 0 && (
                <span className="px-2 py-1 bg-red-100 text-red-700 rounded-full font-medium">
                  {followUpStats.overdue} {t('dashboard.overdue')}
                </span>
              )}
              {followUpStats.today > 0 && (
                <span className="px-2 py-1 bg-yellow-100 text-yellow-700 rounded-full font-medium">
                  {followUpStats.today} {t('dashboard.today')}
                </span>
              )}
            </div>
          </div>

          <div className="space-y-2">
            {followUpStats.due_leads.map((lead) => {
              const badge = getFollowUpBadge(lead.next_follow_up_at);
              return (
                <Link
                  key={lead.id}
                  to={leadDetailPath(lead.id)}
                  className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                >
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <span className="font-medium text-gray-900 truncate">
                        {lead.company_name}
                      </span>
                      {badge && (
                        <span className={clsx('text-xs px-2 py-0.5 rounded-full', badge.color)}>
                          {badge.label}
                        </span>
                      )}
                      <span className={clsx('text-xs px-2 py-0.5 rounded-full', LEAD_STATUS_COLORS[lead.status])}>
                        {tSales(`status.${lead.status}`)}
                      </span>
                    </div>
                    <p className="text-sm text-gray-500 truncate">{lead.contact_name}</p>
                  </div>

                  <div className="flex items-center gap-1">
                    <button
                      onClick={(e) => copyToClipboard(lead.email, e)}
                      className="p-1.5 text-gray-400 hover:text-primary-600 hover:bg-white rounded"
                      title={t('dashboard.copyEmail')}
                    >
                      <EnvelopeIcon className="h-4 w-4" />
                    </button>
                    <ChevronRightIcon className="h-4 w-4 text-gray-400" />
                  </div>
                </Link>
              );
            })}
          </div>

          {followUpStats.total_due > followUpStats.due_leads.length && (
            <Link
              to={localizedPath('/app/leads?filter=follow_up')}
              className="block text-center text-sm text-primary-600 hover:underline mt-3"
            >
              {t('dashboard.viewAll')} ({followUpStats.total_due} lead)
            </Link>
          )}
        </div>
      )}

      {/* Status Distribution & Red Flag Rate */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Status Distribution */}
        <div className="card p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">
            {t('dashboard.candidateStatuses')}
          </h2>
          <div className="space-y-3">
            {[
              { key: 'applied', label: t('dashboard.applied'), color: 'bg-gray-400' },
              { key: 'interview_pending', label: t('dashboard.interviewPending'), color: 'bg-yellow-400' },
              { key: 'interview_completed', label: t('dashboard.interviewCompleted'), color: 'bg-blue-400' },
              { key: 'under_review', label: t('dashboard.underReview'), color: 'bg-purple-400' },
              { key: 'shortlisted', label: t('dashboard.shortlisted'), color: 'bg-indigo-400' },
              { key: 'hired', label: t('dashboard.hiredStatus'), color: 'bg-green-400' },
              { key: 'rejected', label: t('dashboard.rejected'), color: 'bg-red-400' },
            ].map((status) => {
              const count = stats.by_status[status.key as keyof typeof stats.by_status] || 0;
              const percentage = stats.total_candidates > 0
                ? (count / stats.total_candidates) * 100
                : 0;

              return (
                <div key={status.key}>
                  <div className="flex justify-between text-sm mb-1">
                    <span className="text-gray-600">{status.label}</span>
                    <span className="font-medium">{count}</span>
                  </div>
                  <div className="h-2 bg-gray-100 rounded-full overflow-hidden">
                    <div
                      className={`h-full ${status.color} transition-all duration-500`}
                      style={{ width: `${percentage}%` }}
                    />
                  </div>
                </div>
              );
            })}
          </div>
        </div>

        {/* Key Metrics */}
        <div className="card p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">
            {t('dashboard.keyMetrics')}
          </h2>
          <div className="grid grid-cols-2 gap-6">
            <div className="flex flex-col items-center">
              <ScoreCircle
                score={stats.average_score}
                size="lg"
                showLabel
                label={t('dashboard.avgInterviewScore')}
              />
            </div>
            <div className="flex flex-col items-center">
              <ScoreCircle
                score={stats.hire_rate * 100}
                size="lg"
                showLabel
                label={t('dashboard.hireRateLabel')}
              />
            </div>
          </div>

          <div className="mt-6 pt-6 border-t">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <ExclamationTriangleIcon className="h-5 w-5 text-red-500" />
                <span className="text-sm text-gray-600">{t('dashboard.redFlagRate')}</span>
              </div>
              <span className="text-lg font-bold text-red-600">
                %{(stats.red_flag_rate * 100).toFixed(0)}
              </span>
            </div>
            <div className="flex items-center justify-between mt-3">
              <div className="flex items-center gap-2">
                <CheckCircleIcon className="h-5 w-5 text-green-500" />
                <span className="text-sm text-gray-600">{t('dashboard.successfulAssessment')}</span>
              </div>
              <span className="text-lg font-bold text-green-600">
                %{((1 - stats.red_flag_rate) * 100).toFixed(0)}
              </span>
            </div>
          </div>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="card p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">
          {t('dashboard.quickActions')}
        </h2>
        <div className="flex flex-wrap gap-3">
          <Link to={localizedPath('/app/jobs')} className="btn-primary">
            {t('dashboard.createJobListing')}
          </Link>
          <Link to={localizedPath('/app/candidates')} className="btn-secondary">
            {t('dashboard.viewCandidates')}
          </Link>
          <Link to={localizedPath('/app/candidates?has_red_flags=true')} className="btn-secondary">
            {t('dashboard.redFlagCandidates')}
          </Link>
        </div>
      </div>
    </div>
  );
}
