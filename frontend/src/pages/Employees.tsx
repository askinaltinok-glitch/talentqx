import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import {
  MagnifyingGlassIcon,
  PlusIcon,
  ExclamationTriangleIcon,
  UserGroupIcon,
  ArrowUpTrayIcon,
} from '@heroicons/react/24/outline';
import api from '../services/api';
import type { Employee, EmployeeStats } from '../types';

const riskColors: Record<string, string> = {
  low: 'bg-green-100 text-green-800',
  medium: 'bg-yellow-100 text-yellow-800',
  high: 'bg-orange-100 text-orange-800',
  critical: 'bg-red-100 text-red-800',
};

const levelColors: Record<string, string> = {
  basarisiz: 'bg-red-100 text-red-800',
  gelisime_acik: 'bg-orange-100 text-orange-800',
  yeterli: 'bg-yellow-100 text-yellow-800',
  iyi: 'bg-blue-100 text-blue-800',
  mukemmel: 'bg-green-100 text-green-800',
};

export default function Employees() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [employees, setEmployees] = useState<Employee[]>([]);
  const [stats, setStats] = useState<EmployeeStats | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');

  const role = searchParams.get('role') || '';
  const department = searchParams.get('department') || '';
  const riskLevel = searchParams.get('risk_level') || '';
  const highRiskOnly = searchParams.get('high_risk_only') === 'true';

  useEffect(() => {
    loadEmployees();
    loadStats();
  }, [role, department, riskLevel, highRiskOnly]);

  const loadEmployees = async () => {
    setIsLoading(true);
    try {
      const params: Record<string, string | boolean> = {};
      if (role) params.role = role;
      if (department) params.department = department;
      if (riskLevel) params.risk_level = riskLevel;
      if (highRiskOnly) params.high_risk_only = true;

      const response = await api.get<Employee[]>('/employees', params);
      setEmployees(response);
    } catch (error) {
      console.error('Failed to load employees:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const loadStats = async () => {
    try {
      const response = await api.get<EmployeeStats>('/employees/stats');
      setStats(response);
    } catch (error) {
      console.error('Failed to load stats:', error);
    }
  };

  const updateFilter = (key: string, value: string) => {
    if (value) {
      searchParams.set(key, value);
    } else {
      searchParams.delete(key);
    }
    setSearchParams(searchParams);
  };

  const uniqueRoles = [...new Set(employees.map((e) => e.current_role))].filter(Boolean);
  const uniqueDepartments = [...new Set(employees.map((e) => e.department))].filter(Boolean);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Calisanlar</h1>
          <p className="text-gray-500 mt-1">
            Calisan listesi ve yetkinlik degerlendirmeleri
          </p>
        </div>
        <div className="flex gap-3">
          <button
            onClick={() => alert('Toplu yukleme yakinda eklenecek')}
            className="btn-secondary"
          >
            <ArrowUpTrayIcon className="h-5 w-5 mr-2" />
            Toplu Yukle
          </button>
          <button
            onClick={() => alert('Calisan ekleme yakinda eklenecek')}
            className="btn-primary"
          >
            <PlusIcon className="h-5 w-5 mr-2" />
            Calisan Ekle
          </button>
        </div>
      </div>

      {/* Stats Cards */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div className="card p-4">
            <div className="text-sm text-gray-500">Toplam Calisan</div>
            <div className="text-2xl font-bold text-gray-900">{stats.total_employees}</div>
          </div>
          <div className="card p-4">
            <div className="text-sm text-gray-500">Aktif</div>
            <div className="text-2xl font-bold text-green-600">{stats.active_employees}</div>
          </div>
          <div className="card p-4">
            <div className="text-sm text-gray-500">Degerlendirildi</div>
            <div className="text-2xl font-bold text-blue-600">{stats.assessed_count}</div>
          </div>
          <div className="card p-4">
            <div className="text-sm text-gray-500">Degerlendirme Orani</div>
            <div className="text-2xl font-bold text-primary-600">%{stats.assessment_rate}</div>
          </div>
          <div className="card p-4">
            <div className="text-sm text-gray-500">Yuksek Riskli</div>
            <div className="text-2xl font-bold text-red-600">{stats.high_risk_count}</div>
          </div>
        </div>
      )}

      {/* Filters */}
      <div className="card p-4">
        <div className="flex flex-wrap gap-4">
          <div className="flex-1 min-w-[200px]">
            <div className="relative">
              <MagnifyingGlassIcon className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
              <input
                type="text"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && loadEmployees()}
                placeholder="Isim veya sicil no ara..."
                className="input pl-10"
              />
            </div>
          </div>

          <select
            value={role}
            onChange={(e) => updateFilter('role', e.target.value)}
            className="input w-auto"
          >
            <option value="">Tum Roller</option>
            {uniqueRoles.map((r) => (
              <option key={r} value={r}>{r}</option>
            ))}
          </select>

          <select
            value={department}
            onChange={(e) => updateFilter('department', e.target.value)}
            className="input w-auto"
          >
            <option value="">Tum Departmanlar</option>
            {uniqueDepartments.map((d) => (
              <option key={d} value={d}>{d}</option>
            ))}
          </select>

          <select
            value={riskLevel}
            onChange={(e) => updateFilter('risk_level', e.target.value)}
            className="input w-auto"
          >
            <option value="">Tum Risk Seviyeleri</option>
            <option value="low">Dusuk Risk</option>
            <option value="medium">Orta Risk</option>
            <option value="high">Yuksek Risk</option>
            <option value="critical">Kritik Risk</option>
          </select>

          <button
            onClick={() => updateFilter('high_risk_only', highRiskOnly ? '' : 'true')}
            className={`btn-secondary ${highRiskOnly ? 'bg-red-50 border-red-200 text-red-700' : ''}`}
          >
            <ExclamationTriangleIcon className="h-5 w-5 mr-2" />
            Riskli Calisanlar
          </button>
        </div>
      </div>

      {/* Employees Table */}
      {isLoading ? (
        <div className="flex items-center justify-center h-64">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
        </div>
      ) : employees.length === 0 ? (
        <div className="card p-12 text-center">
          <UserGroupIcon className="h-12 w-12 text-gray-300 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">
            Calisan bulunamadi
          </h3>
          <p className="text-gray-500">
            Secili filtrelere uygun calisan bulunmuyor.
          </p>
        </div>
      ) : (
        <div className="card overflow-hidden">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Calisan
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Rol / Departman
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Sube
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Son Puan
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Seviye
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Risk
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {employees.map((employee) => (
                <tr
                  key={employee.id}
                  className="hover:bg-gray-50 cursor-pointer"
                  onClick={() => window.location.href = `/app/employees/${employee.id}`}
                >
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="flex items-center">
                      <div className="flex-shrink-0 h-10 w-10 bg-primary-100 rounded-full flex items-center justify-center">
                        <span className="text-primary-700 font-medium">
                          {employee.first_name[0]}{employee.last_name[0]}
                        </span>
                      </div>
                      <div className="ml-4">
                        <div className="text-sm font-medium text-gray-900">
                          {employee.first_name} {employee.last_name}
                        </div>
                        <div className="text-sm text-gray-500">
                          {employee.employee_code || employee.email}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900">{employee.current_role}</div>
                    <div className="text-sm text-gray-500">{employee.department || '-'}</div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900">{employee.branch || '-'}</div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-center">
                    {employee.latest_assessment?.result?.overall_score !== undefined ? (
                      <span className="text-lg font-bold text-gray-900">
                        {employee.latest_assessment.result.overall_score}
                      </span>
                    ) : (
                      <span className="text-gray-400 text-sm">-</span>
                    )}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-center">
                    {employee.latest_assessment?.result?.level_label ? (
                      <span className={`px-2 py-1 text-xs font-medium rounded-full ${levelColors[employee.latest_assessment.result.level_label] || 'bg-gray-100 text-gray-800'}`}>
                        {employee.latest_assessment.result.level_label.replace('_', ' ')}
                      </span>
                    ) : (
                      <span className="text-gray-400 text-sm">-</span>
                    )}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-center">
                    {employee.latest_assessment?.result?.risk_level ? (
                      <span className={`px-2 py-1 text-xs font-medium rounded-full ${riskColors[employee.latest_assessment.result.risk_level]}`}>
                        {employee.latest_assessment.result.risk_level}
                      </span>
                    ) : (
                      <span className="text-gray-400 text-sm">-</span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
