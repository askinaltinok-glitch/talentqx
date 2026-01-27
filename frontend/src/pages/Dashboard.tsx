import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  BriefcaseIcon,
  UsersIcon,
  VideoCameraIcon,
  ChartBarIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
} from '@heroicons/react/24/outline';
import api from '../services/api';
import type { DashboardStats } from '../types';
import ScoreCircle from '../components/ScoreCircle';

export default function Dashboard() {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    loadStats();
  }, []);

  const loadStats = async () => {
    try {
      const data = await api.get<DashboardStats>('/dashboard/stats');
      setStats(data);
    } catch (error) {
      console.error('Failed to load stats:', error);
    } finally {
      setIsLoading(false);
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
        <p className="text-gray-500">Veriler yuklenemedi.</p>
      </div>
    );
  }

  const statCards = [
    {
      name: 'Toplam Is Ilani',
      value: stats.total_jobs,
      subValue: `${stats.active_jobs} aktif`,
      icon: BriefcaseIcon,
      color: 'bg-blue-500',
      link: '/jobs',
    },
    {
      name: 'Toplam Aday',
      value: stats.total_candidates,
      subValue: `${stats.by_status.hired || 0} ise alindi`,
      icon: UsersIcon,
      color: 'bg-green-500',
      link: '/candidates',
    },
    {
      name: 'Tamamlanan Mulakat',
      value: stats.interviews_completed,
      subValue: `${stats.interviews_pending} bekliyor`,
      icon: VideoCameraIcon,
      color: 'bg-purple-500',
      link: '/candidates?status=interview_completed',
    },
    {
      name: 'Ortalama Puan',
      value: stats.average_score.toFixed(1),
      subValue: `%${(stats.hire_rate * 100).toFixed(0)} ise alim orani`,
      icon: ChartBarIcon,
      color: 'bg-yellow-500',
      link: '/candidates',
    },
  ];

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p className="text-gray-500 mt-1">
          Genel bakis ve istatistikler
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

      {/* Status Distribution & Red Flag Rate */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Status Distribution */}
        <div className="card p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">
            Aday Durumlari
          </h2>
          <div className="space-y-3">
            {[
              { key: 'applied', label: 'Basvurdu', color: 'bg-gray-400' },
              { key: 'interview_pending', label: 'Mulakat Bekliyor', color: 'bg-yellow-400' },
              { key: 'interview_completed', label: 'Mulakat Tamamlandi', color: 'bg-blue-400' },
              { key: 'under_review', label: 'Incelemede', color: 'bg-purple-400' },
              { key: 'shortlisted', label: 'Kisa Liste', color: 'bg-indigo-400' },
              { key: 'hired', label: 'Ise Alindi', color: 'bg-green-400' },
              { key: 'rejected', label: 'Reddedildi', color: 'bg-red-400' },
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
            Temel Metrikler
          </h2>
          <div className="grid grid-cols-2 gap-6">
            <div className="flex flex-col items-center">
              <ScoreCircle
                score={stats.average_score}
                size="lg"
                showLabel
                label="Ort. Mulakat Puani"
              />
            </div>
            <div className="flex flex-col items-center">
              <ScoreCircle
                score={stats.hire_rate * 100}
                size="lg"
                showLabel
                label="Ise Alim Orani"
              />
            </div>
          </div>

          <div className="mt-6 pt-6 border-t">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <ExclamationTriangleIcon className="h-5 w-5 text-red-500" />
                <span className="text-sm text-gray-600">Kirmizi Bayrak Orani</span>
              </div>
              <span className="text-lg font-bold text-red-600">
                %{(stats.red_flag_rate * 100).toFixed(0)}
              </span>
            </div>
            <div className="flex items-center justify-between mt-3">
              <div className="flex items-center gap-2">
                <CheckCircleIcon className="h-5 w-5 text-green-500" />
                <span className="text-sm text-gray-600">Basarili Degerlendirme</span>
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
          Hizli Islemler
        </h2>
        <div className="flex flex-wrap gap-3">
          <Link to="/jobs" className="btn-primary">
            Yeni Is Ilani Olustur
          </Link>
          <Link to="/candidates" className="btn-secondary">
            Adaylari Goruntule
          </Link>
          <Link to="/candidates?has_red_flags=true" className="btn-secondary">
            Kirmizi Bayrakli Adaylar
          </Link>
        </div>
      </div>
    </div>
  );
}
