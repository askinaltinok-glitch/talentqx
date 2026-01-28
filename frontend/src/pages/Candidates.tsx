import { useEffect, useState } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import {
  MagnifyingGlassIcon,
  FunnelIcon,
  ExclamationTriangleIcon,
  ShieldExclamationIcon,
} from '@heroicons/react/24/outline';
import api from '../services/api';
import type { Candidate, Job } from '../types';
import { candidateDetailPath } from '../routes';
import StatusBadge from '../components/StatusBadge';
import ScoreCircle from '../components/ScoreCircle';
import RecommendationBadge from '../components/RecommendationBadge';

export default function Candidates() {
  const [searchParams, setSearchParams] = useSearchParams();
  const [candidates, setCandidates] = useState<Candidate[]>([]);
  const [jobs, setJobs] = useState<Job[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [search, setSearch] = useState('');
  const navigate = useNavigate();

  const jobId = searchParams.get('job_id') || '';
  const status = searchParams.get('status') || '';
  const hasRedFlags = searchParams.get('has_red_flags') === 'true';

  useEffect(() => {
    loadCandidates();
    loadJobs();
  }, [jobId, status, hasRedFlags]);

  const loadCandidates = async () => {
    setIsLoading(true);
    try {
      const params: Record<string, string | boolean> = {};
      if (jobId) params.job_id = jobId;
      if (status) params.status = status;
      if (hasRedFlags) params.has_red_flags = true;
      if (search) params.search = search;

      const response = await api.get<Candidate[]>('/candidates', params);
      setCandidates(response);
    } catch (error) {
      console.error('Failed to load candidates:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const loadJobs = async () => {
    try {
      const response = await api.get<Job[]>('/jobs');
      setJobs(response);
    } catch (error) {
      console.error('Failed to load jobs:', error);
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

  const handleRowClick = (candidateId: string) => {
    navigate(candidateDetailPath(candidateId));
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Adaylar</h1>
        <p className="text-gray-500 mt-1">
          Tum aday basvurulari ve degerlendirmeleri
        </p>
      </div>

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
                onKeyDown={(e) => e.key === 'Enter' && loadCandidates()}
                placeholder="Isim veya e-posta ara..."
                className="input pl-10"
              />
            </div>
          </div>

          <select
            value={jobId}
            onChange={(e) => updateFilter('job_id', e.target.value)}
            className="input w-auto"
          >
            <option value="">Tum Is Ilanlari</option>
            {jobs.map((job) => (
              <option key={job.id} value={job.id}>
                {job.title}
              </option>
            ))}
          </select>

          <select
            value={status}
            onChange={(e) => updateFilter('status', e.target.value)}
            className="input w-auto"
          >
            <option value="">Tum Durumlar</option>
            <option value="applied">Basvurdu</option>
            <option value="interview_pending">Mulakat Bekliyor</option>
            <option value="interview_completed">Mulakat Tamamlandi</option>
            <option value="under_review">Incelemede</option>
            <option value="shortlisted">Kisa Liste</option>
            <option value="hired">Ise Alindi</option>
            <option value="rejected">Reddedildi</option>
          </select>

          <button
            onClick={() =>
              updateFilter('has_red_flags', hasRedFlags ? '' : 'true')
            }
            className={`btn-secondary ${
              hasRedFlags ? 'bg-red-50 border-red-200 text-red-700' : ''
            }`}
          >
            <ExclamationTriangleIcon className="h-5 w-5 mr-2" />
            Kirmizi Bayraklar
          </button>
        </div>
      </div>

      {/* Candidates List */}
      {isLoading ? (
        <div className="flex items-center justify-center h-64">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
        </div>
      ) : candidates.length === 0 ? (
        <div className="card p-12 text-center">
          <FunnelIcon className="h-12 w-12 text-gray-300 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">
            Aday bulunamadi
          </h3>
          <p className="text-gray-500">
            Secili filtrelere uygun aday bulunmuyor.
          </p>
        </div>
      ) : (
        <div className="card overflow-hidden">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Aday
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Pozisyon
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Durum
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Puan
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Oneri
                </th>
                <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Kopya Riski
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {candidates.map((candidate) => (
                <tr
                  key={candidate.id}
                  className="hover:bg-gray-50 cursor-pointer"
                  onClick={() => handleRowClick(candidate.id)}
                >
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="flex items-center">
                      <div className="flex-shrink-0 h-10 w-10 bg-primary-100 rounded-full flex items-center justify-center">
                        <span className="text-primary-700 font-medium">
                          {candidate.first_name[0]}
                          {candidate.last_name[0]}
                        </span>
                      </div>
                      <div className="ml-4">
                        <div className="text-sm font-medium text-gray-900">
                          {candidate.first_name} {candidate.last_name}
                        </div>
                        <div className="text-sm text-gray-500">
                          {candidate.email}
                        </div>
                      </div>
                      {candidate.analysis?.has_red_flags && (
                        <ExclamationTriangleIcon className="h-5 w-5 text-red-500 ml-2" />
                      )}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <div className="text-sm text-gray-900">
                      {candidate.job.title}
                    </div>
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap">
                    <StatusBadge status={candidate.status} size="sm" />
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-center">
                    {candidate.analysis?.overall_score !== undefined ? (
                      <ScoreCircle
                        score={candidate.analysis.overall_score}
                        size="sm"
                      />
                    ) : (
                      <span className="text-gray-400 text-sm">-</span>
                    )}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-center">
                    {candidate.analysis?.recommendation ? (
                      <RecommendationBadge
                        recommendation={candidate.analysis.recommendation}
                        confidence={candidate.analysis.confidence_percent}
                        size="sm"
                      />
                    ) : (
                      <span className="text-gray-400 text-sm">-</span>
                    )}
                  </td>
                  <td className="px-6 py-4 whitespace-nowrap text-center">
                    {(candidate.interview as { analysis?: { cheating_level?: string; cheating_risk_score?: number } })?.analysis?.cheating_level ? (
                      <span className={`inline-flex items-center px-2 py-1 text-xs font-medium rounded-full ${
                        (candidate.interview as { analysis?: { cheating_level?: string } })?.analysis?.cheating_level === 'high'
                          ? 'bg-red-100 text-red-700'
                          : (candidate.interview as { analysis?: { cheating_level?: string } })?.analysis?.cheating_level === 'medium'
                          ? 'bg-yellow-100 text-yellow-700'
                          : 'bg-green-100 text-green-700'
                      }`}>
                        {(candidate.interview as { analysis?: { cheating_level?: string } })?.analysis?.cheating_level === 'high' && (
                          <ShieldExclamationIcon className="h-3 w-3 mr-1" />
                        )}
                        {(candidate.interview as { analysis?: { cheating_risk_score?: number } })?.analysis?.cheating_risk_score ?? 0}
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
