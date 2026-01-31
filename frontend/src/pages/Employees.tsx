import { useEffect, useState } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import {
  MagnifyingGlassIcon,
  ExclamationTriangleIcon,
  UserGroupIcon,
  ArrowUpTrayIcon,
  TrashIcon,
  ArrowUturnLeftIcon,
  UserPlusIcon,
} from '@heroicons/react/24/outline';
import { useTranslation } from 'react-i18next';
import toast from 'react-hot-toast';
import api from '../services/api';
import type { Employee, EmployeeStats } from '../types';
import { employeeDetailPath } from '../routes';
import BulkUploadModal from '../components/BulkUploadModal';
import AddEmployeeModal from '../components/AddEmployeeModal';

interface ImportBatch {
  id: number;
  filename: string;
  imported_count: number;
  created_at: string;
}

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
  const { t } = useTranslation('common');
  const [searchParams, setSearchParams] = useSearchParams();
  const [employees, setEmployees] = useState<Employee[]>([]);
  const [stats, setStats] = useState<EmployeeStats | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [isBulkUploadOpen, setIsBulkUploadOpen] = useState(false);
  const [isAddEmployeeOpen, setIsAddEmployeeOpen] = useState(false);
  const [deleteTarget, setDeleteTarget] = useState<Employee | null>(null);
  const [isDeleting, setIsDeleting] = useState(false);
  const [latestBatch, setLatestBatch] = useState<ImportBatch | null>(null);
  const [showRollbackModal, setShowRollbackModal] = useState(false);
  const [isRollingBack, setIsRollingBack] = useState(false);
  const navigate = useNavigate();

  const role = searchParams.get('role') || '';
  const department = searchParams.get('department') || '';
  const riskLevel = searchParams.get('risk_level') || '';
  const highRiskOnly = searchParams.get('high_risk_only') === 'true';

  useEffect(() => {
    loadEmployees();
    loadStats();
    loadLatestBatch();
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

  const loadLatestBatch = async () => {
    try {
      const response = await api.get<ImportBatch | null>('/employees/latest-import-batch');
      setLatestBatch(response);
    } catch (error) {
      console.error('Failed to load latest batch:', error);
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

  const handleRowClick = (employeeId: string) => {
    navigate(employeeDetailPath(employeeId));
  };

  const handleDeleteClick = (e: React.MouseEvent, employee: Employee) => {
    e.stopPropagation();
    setDeleteTarget(employee);
  };

  const handleDeleteConfirm = async () => {
    if (!deleteTarget) return;

    setIsDeleting(true);
    try {
      await api.delete(`/employees/${deleteTarget.id}`);
      toast.success(t('employees.deleteSuccess'));
      setEmployees(employees.filter(e => e.id !== deleteTarget.id));
      loadStats();
      loadLatestBatch();
    } catch (error) {
      toast.error(api.getErrorMessage(error));
    } finally {
      setIsDeleting(false);
      setDeleteTarget(null);
    }
  };

  const handleRollbackConfirm = async () => {
    if (!latestBatch) return;

    setIsRollingBack(true);
    try {
      const response = await api.post<{ deleted_count: number }>('/employees/bulk-import/rollback', {
        import_batch_id: latestBatch.id,
      });
      toast.success(t('employees.rollbackSuccess', { count: response.deleted_count }));
      setShowRollbackModal(false);
      loadEmployees();
      loadStats();
      loadLatestBatch();
    } catch (error) {
      toast.error(api.getErrorMessage(error));
    } finally {
      setIsRollingBack(false);
    }
  };

  const uniqueRoles = [...new Set(employees.map((e) => e.current_role))].filter(Boolean);
  const uniqueDepartments = [...new Set(employees.map((e) => e.department))].filter(Boolean);

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString(undefined, {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('employees.title')}</h1>
          <p className="text-gray-500 mt-1">
            {t('employees.subtitle')}
          </p>
        </div>
        <div className="flex gap-3">
          {latestBatch && (
            <button
              onClick={() => setShowRollbackModal(true)}
              className="btn-secondary text-orange-600 border-orange-200 hover:bg-orange-50"
            >
              <ArrowUturnLeftIcon className="h-5 w-5 mr-2" />
              {t('employees.rollbackImport')}
            </button>
          )}
          <button
            onClick={() => setIsAddEmployeeOpen(true)}
            className="btn-secondary"
          >
            <UserPlusIcon className="h-5 w-5 mr-2" />
            {t('employees.addEmployee')}
          </button>
          <button
            onClick={() => setIsBulkUploadOpen(true)}
            className="btn-primary"
          >
            <ArrowUpTrayIcon className="h-5 w-5 mr-2" />
            {t('bulkUpload.buttonText')}
          </button>
        </div>
      </div>

      {/* Stats Cards */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div className="card p-4">
            <div className="text-sm text-gray-500">{t('employees.totalEmployees')}</div>
            <div className="text-2xl font-bold text-gray-900">{stats.total_employees}</div>
          </div>
          <div className="card p-4">
            <div className="text-sm text-gray-500">{t('employees.active')}</div>
            <div className="text-2xl font-bold text-green-600">{stats.active_employees}</div>
          </div>
          <div className="card p-4">
            <div className="text-sm text-gray-500">{t('employees.assessed')}</div>
            <div className="text-2xl font-bold text-blue-600">{stats.assessed_count}</div>
          </div>
          <div className="card p-4">
            <div className="text-sm text-gray-500">{t('employees.assessmentRate')}</div>
            <div className="text-2xl font-bold text-primary-600">%{stats.assessment_rate}</div>
          </div>
          <div className="card p-4">
            <div className="text-sm text-gray-500">{t('employees.highRisk')}</div>
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
                placeholder={t('employees.searchPlaceholder')}
                className="input pl-10"
              />
            </div>
          </div>

          <select
            value={role}
            onChange={(e) => updateFilter('role', e.target.value)}
            className="input w-auto"
          >
            <option value="">{t('employees.allRoles')}</option>
            {uniqueRoles.map((r) => (
              <option key={r} value={r}>{r}</option>
            ))}
          </select>

          <select
            value={department}
            onChange={(e) => updateFilter('department', e.target.value)}
            className="input w-auto"
          >
            <option value="">{t('employees.allDepartments')}</option>
            {uniqueDepartments.map((d) => (
              <option key={d} value={d}>{d}</option>
            ))}
          </select>

          <select
            value={riskLevel}
            onChange={(e) => updateFilter('risk_level', e.target.value)}
            className="input w-auto"
          >
            <option value="">{t('employees.allRiskLevels')}</option>
            <option value="low">{t('employees.lowRisk')}</option>
            <option value="medium">{t('employees.mediumRisk')}</option>
            <option value="high">{t('employees.highRiskOption')}</option>
            <option value="critical">{t('employees.criticalRisk')}</option>
          </select>

          <button
            onClick={() => updateFilter('high_risk_only', highRiskOnly ? '' : 'true')}
            className={`btn-secondary ${highRiskOnly ? 'bg-red-50 border-red-200 text-red-700' : ''}`}
          >
            <ExclamationTriangleIcon className="h-5 w-5 mr-2" />
            {t('employees.riskEmployees')}
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
            {t('employees.noEmployees')}
          </h3>
          <p className="text-gray-500">
            {t('employees.noEmployeesDesc')}
          </p>
        </div>
      ) : (
        <div className="card overflow-hidden">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {t('employees.employee')}
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {t('employees.roleDepartment')}
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {t('employees.branch')}
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {t('employees.lastScore')}
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {t('employees.level')}
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {t('employees.risk')}
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {t('employees.actions')}
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {employees.map((employee) => (
                <tr
                  key={employee.id}
                  className="hover:bg-gray-50 cursor-pointer"
                  onClick={() => handleRowClick(employee.id)}
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
                  <td className="px-6 py-4 whitespace-nowrap text-center">
                    <button
                      onClick={(e) => handleDeleteClick(e, employee)}
                      className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                      title={t('employees.delete')}
                    >
                      <TrashIcon className="h-5 w-5" />
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Bulk Upload Modal */}
      <BulkUploadModal
        isOpen={isBulkUploadOpen}
        onClose={() => setIsBulkUploadOpen(false)}
        onSuccess={() => {
          loadEmployees();
          loadStats();
          loadLatestBatch();
        }}
      />

      {/* Add Employee Modal */}
      <AddEmployeeModal
        isOpen={isAddEmployeeOpen}
        onClose={() => setIsAddEmployeeOpen(false)}
        onCreated={() => {
          loadEmployees();
          loadStats();
        }}
      />

      {/* Delete Confirmation Modal */}
      {deleteTarget && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="fixed inset-0 bg-gray-900/50" onClick={() => setDeleteTarget(null)} />
          <div className="flex min-h-full items-center justify-center p-4">
            <div className="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
              <div className="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                <TrashIcon className="h-6 w-6 text-red-600" />
              </div>
              <h3 className="text-lg font-semibold text-gray-900 text-center mb-2">
                {t('employees.deleteConfirmTitle')}
              </h3>
              <p className="text-gray-500 text-center mb-6">
                {t('employees.deleteConfirmMessage', {
                  name: `${deleteTarget.first_name} ${deleteTarget.last_name}`
                })}
              </p>
              <div className="flex gap-3">
                <button
                  onClick={() => setDeleteTarget(null)}
                  className="flex-1 btn-secondary"
                  disabled={isDeleting}
                >
                  {t('buttons.cancel')}
                </button>
                <button
                  onClick={handleDeleteConfirm}
                  className="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 disabled:opacity-50"
                  disabled={isDeleting}
                >
                  {isDeleting ? t('buttons.loading') : t('employees.delete')}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Rollback Confirmation Modal */}
      {showRollbackModal && latestBatch && (
        <div className="fixed inset-0 z-50 overflow-y-auto">
          <div className="fixed inset-0 bg-gray-900/50" onClick={() => setShowRollbackModal(false)} />
          <div className="flex min-h-full items-center justify-center p-4">
            <div className="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
              <div className="flex items-center justify-center w-12 h-12 mx-auto bg-orange-100 rounded-full mb-4">
                <ArrowUturnLeftIcon className="h-6 w-6 text-orange-600" />
              </div>
              <h3 className="text-lg font-semibold text-gray-900 text-center mb-2">
                {t('employees.rollbackConfirmTitle')}
              </h3>
              <div className="bg-gray-50 rounded-lg p-4 mb-4">
                <div className="text-sm text-gray-600">
                  <div><strong>{t('employees.filename')}:</strong> {latestBatch.filename}</div>
                  <div><strong>{t('employees.importedCount')}:</strong> {latestBatch.imported_count}</div>
                  <div><strong>{t('employees.importDate')}:</strong> {formatDate(latestBatch.created_at)}</div>
                </div>
              </div>
              <p className="text-gray-500 text-center mb-6">
                {t('employees.rollbackConfirmMessage', { count: latestBatch.imported_count })}
              </p>
              <div className="flex gap-3">
                <button
                  onClick={() => setShowRollbackModal(false)}
                  className="flex-1 btn-secondary"
                  disabled={isRollingBack}
                >
                  {t('buttons.cancel')}
                </button>
                <button
                  onClick={handleRollbackConfirm}
                  className="flex-1 bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700 disabled:opacity-50"
                  disabled={isRollingBack}
                >
                  {isRollingBack ? t('buttons.loading') : t('employees.rollback')}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
