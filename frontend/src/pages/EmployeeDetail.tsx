import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import {
  ArrowLeftIcon,
  ClipboardDocumentListIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  ChartBarIcon,
} from '@heroicons/react/24/outline';
import api from '../services/api';
import type { Employee, AssessmentTemplate, AssessmentSession } from '../types';
import toast from 'react-hot-toast';

const riskColors: Record<string, string> = {
  low: 'bg-green-100 text-green-800 border-green-200',
  medium: 'bg-yellow-100 text-yellow-800 border-yellow-200',
  high: 'bg-orange-100 text-orange-800 border-orange-200',
  critical: 'bg-red-100 text-red-800 border-red-200',
};

const levelColors: Record<string, string> = {
  basarisiz: 'text-red-600',
  gelisime_acik: 'text-orange-600',
  yeterli: 'text-yellow-600',
  iyi: 'text-blue-600',
  mukemmel: 'text-green-600',
};

export default function EmployeeDetail() {
  const { id } = useParams<{ id: string }>();
  const [employee, setEmployee] = useState<Employee | null>(null);
  const [templates, setTemplates] = useState<AssessmentTemplate[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [selectedTemplate, setSelectedTemplate] = useState<string>('');
  const [isCreatingSession, setIsCreatingSession] = useState(false);

  useEffect(() => {
    if (id) {
      loadEmployee();
      loadTemplates();
    }
  }, [id]);

  const loadEmployee = async () => {
    setIsLoading(true);
    try {
      const response = await api.get<Employee>(`/employees/${id}`);
      setEmployee(response);
    } catch (error) {
      console.error('Failed to load employee:', error);
      toast.error('Calisan bilgileri yuklenemedi');
    } finally {
      setIsLoading(false);
    }
  };

  const loadTemplates = async () => {
    try {
      const response = await api.get<AssessmentTemplate[]>('/assessment-templates');
      setTemplates(response);
    } catch (error) {
      console.error('Failed to load templates:', error);
    }
  };

  const createSession = async () => {
    if (!selectedTemplate || !employee) return;

    setIsCreatingSession(true);
    try {
      await api.post<AssessmentSession>('/assessment-sessions', {
        employee_id: employee.id,
        template_id: selectedTemplate,
      });
      toast.success('Degerlendirme oturumu olusturuldu');
      loadEmployee();
      setSelectedTemplate('');
    } catch (error) {
      console.error('Failed to create session:', error);
      toast.error('Oturum olusturulamadi');
    } finally {
      setIsCreatingSession(false);
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (!employee) {
    return (
      <div className="text-center py-12">
        <h2 className="text-xl text-gray-600">Calisan bulunamadi</h2>
      </div>
    );
  }

  const latestResult = employee.latest_assessment?.result;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <Link to="/employees" className="btn-secondary p-2">
          <ArrowLeftIcon className="h-5 w-5" />
        </Link>
        <div className="flex-1">
          <h1 className="text-2xl font-bold text-gray-900">
            {employee.first_name} {employee.last_name}
          </h1>
          <p className="text-gray-500">
            {employee.current_role} {employee.department && `- ${employee.department}`}
          </p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left Column - Employee Info */}
        <div className="space-y-6">
          {/* Basic Info */}
          <div className="card p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Calisan Bilgileri</h3>
            <dl className="space-y-3">
              <div>
                <dt className="text-sm text-gray-500">Sicil No</dt>
                <dd className="text-sm font-medium text-gray-900">{employee.employee_code || '-'}</dd>
              </div>
              <div>
                <dt className="text-sm text-gray-500">E-posta</dt>
                <dd className="text-sm font-medium text-gray-900">{employee.email || '-'}</dd>
              </div>
              <div>
                <dt className="text-sm text-gray-500">Telefon</dt>
                <dd className="text-sm font-medium text-gray-900">{employee.phone || '-'}</dd>
              </div>
              <div>
                <dt className="text-sm text-gray-500">Sube</dt>
                <dd className="text-sm font-medium text-gray-900">{employee.branch || '-'}</dd>
              </div>
              <div>
                <dt className="text-sm text-gray-500">Ise Giris</dt>
                <dd className="text-sm font-medium text-gray-900">
                  {employee.hire_date ? new Date(employee.hire_date).toLocaleDateString('tr-TR') : '-'}
                </dd>
              </div>
              <div>
                <dt className="text-sm text-gray-500">Yonetici</dt>
                <dd className="text-sm font-medium text-gray-900">{employee.manager_name || '-'}</dd>
              </div>
            </dl>
          </div>

          {/* Create Assessment */}
          <div className="card p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Yeni Degerlendirme</h3>
            <div className="space-y-4">
              <select
                value={selectedTemplate}
                onChange={(e) => setSelectedTemplate(e.target.value)}
                className="input w-full"
              >
                <option value="">Sablon Secin</option>
                {templates.map((t) => (
                  <option key={t.id} value={t.id}>{t.name}</option>
                ))}
              </select>
              <button
                onClick={createSession}
                disabled={!selectedTemplate || isCreatingSession}
                className="btn-primary w-full"
              >
                <ClipboardDocumentListIcon className="h-5 w-5 mr-2" />
                {isCreatingSession ? 'Olusturuluyor...' : 'Degerlendirme Baslat'}
              </button>
            </div>
          </div>
        </div>

        {/* Middle Column - Latest Assessment Result */}
        <div className="lg:col-span-2 space-y-6">
          {latestResult ? (
            <>
              {/* Score Overview */}
              <div className="card p-6">
                <div className="flex items-center justify-between mb-6">
                  <h3 className="text-lg font-semibold text-gray-900">Son Degerlendirme Sonucu</h3>
                  <span className="text-sm text-gray-500">
                    {new Date(latestResult.analyzed_at).toLocaleDateString('tr-TR')}
                  </span>
                </div>

                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                  <div className="text-center p-4 bg-gray-50 rounded-lg">
                    <div className="text-3xl font-bold text-gray-900">{latestResult.overall_score}</div>
                    <div className="text-sm text-gray-500">Genel Puan</div>
                  </div>
                  <div className="text-center p-4 bg-gray-50 rounded-lg">
                    <div className={`text-xl font-bold ${levelColors[latestResult.level_label] || 'text-gray-900'}`}>
                      {latestResult.level_label.replace('_', ' ')}
                    </div>
                    <div className="text-sm text-gray-500">Seviye</div>
                  </div>
                  <div className="text-center p-4 bg-gray-50 rounded-lg">
                    <div className={`inline-block px-3 py-1 rounded-full text-sm font-medium ${riskColors[latestResult.risk_level]}`}>
                      {latestResult.risk_level}
                    </div>
                    <div className="text-sm text-gray-500 mt-1">Risk</div>
                  </div>
                  <div className="text-center p-4 bg-gray-50 rounded-lg">
                    <div className="text-xl font-bold text-gray-900">
                      {latestResult.promotion_suitable ? (
                        <CheckCircleIcon className="h-8 w-8 text-green-500 mx-auto" />
                      ) : (
                        <span className="text-gray-400">-</span>
                      )}
                    </div>
                    <div className="text-sm text-gray-500">Terfi Uygun</div>
                  </div>
                </div>

                {/* Competency Scores */}
                <h4 className="font-medium text-gray-900 mb-3">Yetkinlik Puanlari</h4>
                <div className="space-y-3">
                  {Object.entries(latestResult.competency_scores).map(([code, data]) => (
                    <div key={code} className="flex items-center gap-4">
                      <div className="w-32 text-sm text-gray-600 truncate">{code}</div>
                      <div className="flex-1">
                        <div className="h-2 bg-gray-200 rounded-full overflow-hidden">
                          <div
                            className="h-full bg-primary-500 rounded-full"
                            style={{ width: `${data.score}%` }}
                          />
                        </div>
                      </div>
                      <div className="w-12 text-sm font-medium text-gray-900 text-right">
                        {data.score}
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              {/* Risk Flags */}
              {latestResult.risk_flags.length > 0 && (
                <div className="card p-6 border-l-4 border-red-500">
                  <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <ExclamationTriangleIcon className="h-5 w-5 text-red-500 mr-2" />
                    Risk Bayraklari ({latestResult.risk_flags.length})
                  </h3>
                  <ul className="space-y-3">
                    {latestResult.risk_flags.map((flag, index) => (
                      <li key={index} className={`p-3 rounded-lg border ${riskColors[flag.severity]}`}>
                        <div className="font-medium">{flag.label}</div>
                        <div className="text-sm mt-1">{flag.evidence}</div>
                      </li>
                    ))}
                  </ul>
                </div>
              )}

              {/* Strengths & Improvements */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="card p-6">
                  <h4 className="font-medium text-gray-900 mb-3 flex items-center">
                    <CheckCircleIcon className="h-5 w-5 text-green-500 mr-2" />
                    Guclu Yanlar
                  </h4>
                  <ul className="space-y-2">
                    {latestResult.strengths.map((s, i) => (
                      <li key={i} className="text-sm text-gray-600">
                        <span className="font-medium">{s.competency}:</span> {s.description}
                      </li>
                    ))}
                  </ul>
                </div>
                <div className="card p-6">
                  <h4 className="font-medium text-gray-900 mb-3 flex items-center">
                    <ChartBarIcon className="h-5 w-5 text-orange-500 mr-2" />
                    Gelistirme Alanlari
                  </h4>
                  <ul className="space-y-2">
                    {latestResult.improvement_areas.map((a, i) => (
                      <li key={i} className="text-sm text-gray-600">
                        <span className="font-medium">{a.competency}:</span> {a.description}
                      </li>
                    ))}
                  </ul>
                </div>
              </div>

              {/* Development Plan */}
              {latestResult.development_plan.length > 0 && (
                <div className="card p-6">
                  <h3 className="text-lg font-semibold text-gray-900 mb-4">Gelisim Plani</h3>
                  <div className="space-y-4">
                    {latestResult.development_plan.map((item, index) => (
                      <div key={index} className="border-l-4 border-primary-500 pl-4">
                        <div className="flex items-center gap-2 mb-2">
                          <h4 className="font-medium text-gray-900">{item.area}</h4>
                          <span className={`px-2 py-0.5 text-xs rounded ${
                            item.priority === 'high' ? 'bg-red-100 text-red-700' :
                            item.priority === 'medium' ? 'bg-yellow-100 text-yellow-700' :
                            'bg-gray-100 text-gray-700'
                          }`}>
                            {item.priority}
                          </span>
                          <span className="text-sm text-gray-500">{item.timeline}</span>
                        </div>
                        <ul className="list-disc list-inside text-sm text-gray-600">
                          {item.actions.map((action, i) => (
                            <li key={i}>{action}</li>
                          ))}
                        </ul>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </>
          ) : (
            <div className="card p-12 text-center">
              <ClipboardDocumentListIcon className="h-12 w-12 text-gray-300 mx-auto mb-4" />
              <h3 className="text-lg font-medium text-gray-900 mb-2">
                Henuz degerlendirme yapilmamis
              </h3>
              <p className="text-gray-500">
                Sol taraftan bir degerlendirme sablonu secip baslatin.
              </p>
            </div>
          )}

          {/* Assessment History */}
          {employee.assessment_sessions && employee.assessment_sessions.length > 0 && (
            <div className="card p-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Degerlendirme Gecmisi</h3>
              <table className="min-w-full divide-y divide-gray-200">
                <thead>
                  <tr>
                    <th className="text-left text-xs font-medium text-gray-500 uppercase py-2">Tarih</th>
                    <th className="text-left text-xs font-medium text-gray-500 uppercase py-2">Sablon</th>
                    <th className="text-center text-xs font-medium text-gray-500 uppercase py-2">Durum</th>
                    <th className="text-center text-xs font-medium text-gray-500 uppercase py-2">Puan</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200">
                  {employee.assessment_sessions.map((session) => (
                    <tr key={session.id} className="hover:bg-gray-50">
                      <td className="py-3 text-sm text-gray-900">
                        {new Date(session.created_at).toLocaleDateString('tr-TR')}
                      </td>
                      <td className="py-3 text-sm text-gray-600">
                        {session.template?.name || '-'}
                      </td>
                      <td className="py-3 text-center">
                        <span className={`px-2 py-1 text-xs rounded-full ${
                          session.status === 'completed' ? 'bg-green-100 text-green-700' :
                          session.status === 'in_progress' ? 'bg-blue-100 text-blue-700' :
                          session.status === 'expired' ? 'bg-gray-100 text-gray-700' :
                          'bg-yellow-100 text-yellow-700'
                        }`}>
                          {session.status}
                        </span>
                      </td>
                      <td className="py-3 text-center text-sm font-medium">
                        {session.result?.overall_score || '-'}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
