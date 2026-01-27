import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import {
  ChartBarIcon,
  ExclamationTriangleIcon,
  ArrowUpIcon,
  DocumentArrowDownIcon,
  ShieldExclamationIcon,
  CurrencyDollarIcon,
  ExclamationCircleIcon,
} from '@heroicons/react/24/outline';
import api from '../services/api';
import type { AssessmentResult, AssessmentDashboardStats, AssessmentCostStats } from '../types';

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

const promotionLabels: Record<string, string> = {
  not_ready: 'Hazir Degil',
  developing: 'Gelisiyor',
  ready: 'Hazir',
  highly_ready: 'Cok Hazir',
};

const cheatingColors: Record<string, string> = {
  low: 'bg-green-100 text-green-800',
  medium: 'bg-yellow-100 text-yellow-800',
  high: 'bg-red-100 text-red-800',
};

export default function AssessmentResults() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [results, setResults] = useState<AssessmentResult[]>([]);
  const [stats, setStats] = useState<AssessmentDashboardStats | null>(null);
  const [costStats, setCostStats] = useState<AssessmentCostStats | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [selectedResults, setSelectedResults] = useState<string[]>([]);

  const riskLevel = searchParams.get('risk_level') || '';
  const levelLabel = searchParams.get('level_label') || '';
  const promotableOnly = searchParams.get('promotable_only') === 'true';
  const minScore = searchParams.get('min_score') || '';
  const cheatingLevel = searchParams.get('cheating_level') || '';

  useEffect(() => {
    loadResults();
    loadStats();
    loadCostStats();
  }, [riskLevel, levelLabel, promotableOnly, minScore, cheatingLevel]);

  const loadResults = async () => {
    setIsLoading(true);
    try {
      const params: Record<string, string | boolean> = {};
      if (riskLevel) params.risk_level = riskLevel;
      if (levelLabel) params.level_label = levelLabel;
      if (promotableOnly) params.promotable_only = true;
      if (minScore) params.min_score = minScore;
      if (cheatingLevel) params.cheating_level = cheatingLevel;

      const response = await api.get<AssessmentResult[]>('/assessment-results', params);
      setResults(response);
    } catch (error) {
      console.error('Failed to load results:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const loadStats = async () => {
    try {
      const response = await api.get<AssessmentDashboardStats>('/assessment-results/dashboard-stats');
      setStats(response);
    } catch (error) {
      console.error('Failed to load stats:', error);
    }
  };

  const loadCostStats = async () => {
    try {
      const response = await api.get<AssessmentCostStats>('/assessment-results/cost-stats');
      setCostStats(response);
    } catch (error) {
      console.error('Failed to load cost stats:', error);
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

  const toggleResultSelection = (id: string) => {
    setSelectedResults(prev =>
      prev.includes(id) ? prev.filter(x => x !== id) : [...prev, id]
    );
  };

  const compareSelected = async () => {
    if (selectedResults.length < 2) return;
    // Navigate to compare page or open modal
    window.location.href = `/app/assessments/compare?ids=${selectedResults.join(',')}`;
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Degerlendirme Sonuclari</h1>
          <p className="text-gray-500 mt-1">
            Tum calisan degerlendirme sonuclari ve analizleri
          </p>
        </div>
        <div className="flex gap-3">
          {selectedResults.length >= 2 && (
            <button onClick={compareSelected} className="btn-secondary">
              <ChartBarIcon className="h-5 w-5 mr-2" />
              Karsilastir ({selectedResults.length})
            </button>
          )}
          <button className="btn-primary">
            <DocumentArrowDownIcon className="h-5 w-5 mr-2" />
            Rapor Indir
          </button>
        </div>
      </div>

      {/* Stats Cards */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-6 gap-4">
          <div className="card p-4">
            <div className="text-sm text-gray-500">Toplam Degerlendirme</div>
            <div className="text-2xl font-bold text-gray-900">{stats.total_sessions}</div>
          </div>
          <div className="card p-4">
            <div className="text-sm text-gray-500">Tamamlanan</div>
            <div className="text-2xl font-bold text-green-600">{stats.completed_sessions}</div>
          </div>
          <div className="card p-4">
            <div className="text-sm text-gray-500">Bekleyen</div>
            <div className="text-2xl font-bold text-yellow-600">{stats.pending_sessions}</div>
          </div>
          <div className="card p-4">
            <div className="text-sm text-gray-500">Ortalama Puan</div>
            <div className="text-2xl font-bold text-blue-600">{stats.average_score}</div>
          </div>
          <div className="card p-4">
            <div className="text-sm text-gray-500 flex items-center gap-1">
              <ShieldExclamationIcon className="h-4 w-4 text-red-500" />
              Yuksek Kopya Riski
            </div>
            <div className="text-2xl font-bold text-red-600">{stats.high_cheating_risk_count || 0}</div>
          </div>
          <div className="card p-4">
            <div className="text-sm text-gray-500 flex items-center gap-1">
              <ExclamationCircleIcon className="h-4 w-4 text-orange-500" />
              Analiz Hatasi
            </div>
            <div className="text-2xl font-bold text-orange-600">{stats.analysis_failed_count || 0}</div>
          </div>
        </div>
      )}

      {/* Cost Stats Card */}
      {costStats && (
        <div className="card p-4">
          <div className="flex items-center gap-2 mb-3">
            <CurrencyDollarIcon className="h-5 w-5 text-green-600" />
            <h3 className="font-semibold text-gray-900">AI Maliyet Istatistikleri</h3>
          </div>
          <div className="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
            <div>
              <div className="text-gray-500">Toplam Maliyet</div>
              <div className="font-bold text-green-600">${costStats.total_cost_usd.toFixed(4)}</div>
            </div>
            <div>
              <div className="text-gray-500">Bu Ay</div>
              <div className="font-bold text-blue-600">${costStats.monthly_cost_usd.toFixed(4)}</div>
            </div>
            <div>
              <div className="text-gray-500">Oturum Basina Ort.</div>
              <div className="font-bold">${costStats.average_cost_per_session.toFixed(4)}</div>
            </div>
            <div>
              <div className="text-gray-500">Maliyet Limitli</div>
              <div className="font-bold text-yellow-600">{costStats.cost_limited_sessions}</div>
            </div>
            <div>
              <div className="text-gray-500">Toplam Token</div>
              <div className="font-bold">{costStats.token_usage.total_tokens.toLocaleString()}</div>
            </div>
          </div>
        </div>
      )}

      {/* Distribution Charts */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div className="card p-6">
            <h3 className="font-semibold text-gray-900 mb-4">Risk Dagilimi</h3>
            <div className="space-y-2">
              {Object.entries(stats.risk_distribution).map(([level, count]) => (
                <div key={level} className="flex items-center gap-3">
                  <span className={`px-2 py-1 text-xs rounded-full ${riskColors[level]}`}>{level}</span>
                  <div className="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div
                      className={`h-full ${
                        level === 'low' ? 'bg-green-500' :
                        level === 'medium' ? 'bg-yellow-500' :
                        level === 'high' ? 'bg-orange-500' :
                        'bg-red-500'
                      }`}
                      style={{ width: `${(count / stats.completed_sessions) * 100}%` }}
                    />
                  </div>
                  <span className="text-sm font-medium text-gray-600 w-8">{count}</span>
                </div>
              ))}
            </div>
          </div>
          <div className="card p-6">
            <h3 className="font-semibold text-gray-900 mb-4">Seviye Dagilimi</h3>
            <div className="space-y-2">
              {Object.entries(stats.level_distribution).map(([level, count]) => (
                <div key={level} className="flex items-center gap-3">
                  <span className={`px-2 py-1 text-xs rounded-full ${levelColors[level] || 'bg-gray-100'}`}>
                    {level.replace('_', ' ')}
                  </span>
                  <div className="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div
                      className="h-full bg-primary-500"
                      style={{ width: `${(count / stats.completed_sessions) * 100}%` }}
                    />
                  </div>
                  <span className="text-sm font-medium text-gray-600 w-8">{count}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Filters */}
      <div className="card p-4">
        <div className="flex flex-wrap gap-4">
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

          <select
            value={levelLabel}
            onChange={(e) => updateFilter('level_label', e.target.value)}
            className="input w-auto"
          >
            <option value="">Tum Seviyeler</option>
            <option value="basarisiz">Basarisiz</option>
            <option value="gelisime_acik">Gelisime Acik</option>
            <option value="yeterli">Yeterli</option>
            <option value="iyi">Iyi</option>
            <option value="mukemmel">Mukemmel</option>
          </select>

          <select
            value={minScore}
            onChange={(e) => updateFilter('min_score', e.target.value)}
            className="input w-auto"
          >
            <option value="">Min Puan</option>
            <option value="50">50+</option>
            <option value="60">60+</option>
            <option value="70">70+</option>
            <option value="80">80+</option>
            <option value="90">90+</option>
          </select>

          <button
            onClick={() => updateFilter('promotable_only', promotableOnly ? '' : 'true')}
            className={`btn-secondary ${promotableOnly ? 'bg-green-50 border-green-200 text-green-700' : ''}`}
          >
            <ArrowUpIcon className="h-5 w-5 mr-2" />
            Terfi Uygun
          </button>

          <button
            onClick={() => updateFilter('risk_level', riskLevel === 'high' ? '' : 'high')}
            className={`btn-secondary ${riskLevel === 'high' || riskLevel === 'critical' ? 'bg-red-50 border-red-200 text-red-700' : ''}`}
          >
            <ExclamationTriangleIcon className="h-5 w-5 mr-2" />
            Yuksek Riskli
          </button>

          <select
            value={cheatingLevel}
            onChange={(e) => updateFilter('cheating_level', e.target.value)}
            className="input w-auto"
          >
            <option value="">Tum Kopya Risk Seviyeleri</option>
            <option value="low">Dusuk Kopya Riski</option>
            <option value="medium">Orta Kopya Riski</option>
            <option value="high">Yuksek Kopya Riski</option>
          </select>

          <button
            onClick={() => updateFilter('cheating_level', cheatingLevel === 'high' ? '' : 'high')}
            className={`btn-secondary ${cheatingLevel === 'high' ? 'bg-red-50 border-red-200 text-red-700' : ''}`}
          >
            <ShieldExclamationIcon className="h-5 w-5 mr-2" />
            Kopya Suphesi
          </button>
        </div>
      </div>

      {/* Results Table */}
      {isLoading ? (
        <div className="flex items-center justify-center h-64">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
        </div>
      ) : results.length === 0 ? (
        <div className="card p-12 text-center">
          <ChartBarIcon className="h-12 w-12 text-gray-300 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">
            Sonuc bulunamadi
          </h3>
          <p className="text-gray-500">
            Secili filtrelere uygun degerlendirme sonucu bulunmuyor.
          </p>
        </div>
      ) : (
        <div className="card overflow-hidden">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-4 py-3 text-left">
                  <input
                    type="checkbox"
                    onChange={(e) => {
                      if (e.target.checked) {
                        setSelectedResults(results.map(r => r.id));
                      } else {
                        setSelectedResults([]);
                      }
                    }}
                    checked={selectedResults.length === results.length && results.length > 0}
                    className="rounded"
                  />
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Calisan
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Rol
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Puan
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Seviye
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Risk
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Terfi
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Kopya Riski
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Tarih
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {results.map((result) => (
                <tr
                  key={result.id}
                  className={`hover:bg-gray-50 cursor-pointer ${
                    selectedResults.includes(result.id) ? 'bg-primary-50' : ''
                  }`}
                  onClick={() => window.location.href = `/app/employees/${result.session?.employee?.id}`}
                >
                  <td className="px-4 py-4" onClick={(e) => e.stopPropagation()}>
                    <input
                      type="checkbox"
                      checked={selectedResults.includes(result.id)}
                      onChange={() => toggleResultSelection(result.id)}
                      className="rounded"
                    />
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="flex items-center">
                      <div className="flex-shrink-0 h-10 w-10 bg-primary-100 rounded-full flex items-center justify-center">
                        <span className="text-primary-700 font-medium">
                          {result.session?.employee?.first_name?.[0]}
                          {result.session?.employee?.last_name?.[0]}
                        </span>
                      </div>
                      <div className="ml-4">
                        <div className="text-sm font-medium text-gray-900">
                          {result.session?.employee?.first_name} {result.session?.employee?.last_name}
                        </div>
                        <div className="text-sm text-gray-500">
                          {result.session?.employee?.department}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900">{result.session?.employee?.current_role}</div>
                    <div className="text-sm text-gray-500">{result.session?.template?.name}</div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-center">
                    <span className="text-lg font-bold text-gray-900">{result.overall_score}</span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-center">
                    <span className={`px-2 py-1 text-xs font-medium rounded-full ${levelColors[result.level_label] || 'bg-gray-100'}`}>
                      {result.level_label.replace('_', ' ')}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-center">
                    <span className={`px-2 py-1 text-xs font-medium rounded-full ${riskColors[result.risk_level]}`}>
                      {result.risk_level}
                      {result.risk_flags.length > 0 && ` (${result.risk_flags.length})`}
                    </span>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-center">
                    {result.promotion_suitable ? (
                      <span className="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                        {promotionLabels[result.promotion_readiness]}
                      </span>
                    ) : (
                      <span className="text-gray-400">-</span>
                    )}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-center">
                    {result.cheating_level ? (
                      <span className={`inline-flex items-center px-2 py-1 text-xs font-medium rounded-full ${cheatingColors[result.cheating_level]}`}>
                        {result.cheating_level === 'high' && (
                          <ShieldExclamationIcon className="h-3 w-3 mr-1" />
                        )}
                        {result.cheating_risk_score ?? 0}
                        {result.cheating_flags && result.cheating_flags.length > 0 && (
                          <span className="ml-1">({result.cheating_flags.length})</span>
                        )}
                      </span>
                    ) : (
                      <span className="text-gray-400">-</span>
                    )}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div className="flex items-center gap-1">
                      {result.status === 'analysis_failed' && (
                        <ExclamationCircleIcon className="h-4 w-4 text-red-500" title="Analiz basarisiz" />
                      )}
                      {result.cost_limited && (
                        <CurrencyDollarIcon className="h-4 w-4 text-yellow-500" title="Maliyet limiti asildi" />
                      )}
                      {new Date(result.analyzed_at).toLocaleDateString('tr-TR')}
                    </div>
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
