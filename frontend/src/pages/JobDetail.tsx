import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import {
  ArrowLeftIcon,
  SparklesIcon,
  PlayIcon,
} from '@heroicons/react/24/outline';
import api from '../services/api';
import type { Job } from '../types';
import StatusBadge from '../components/StatusBadge';
import toast from 'react-hot-toast';

export default function JobDetail() {
  const { id } = useParams<{ id: string }>();
  const [job, setJob] = useState<Job | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isGenerating, setIsGenerating] = useState(false);
  const [isPublishing, setIsPublishing] = useState(false);

  useEffect(() => {
    if (id) loadJob();
  }, [id]);

  const loadJob = async () => {
    try {
      const data = await api.get<Job>(`/jobs/${id}`);
      setJob(data);
    } catch (error) {
      toast.error('Is ilani yuklenemedi.');
    } finally {
      setIsLoading(false);
    }
  };

  const generateQuestions = async () => {
    if (!job) return;
    setIsGenerating(true);

    try {
      const result = await api.post<{ questions: unknown[] }>(
        `/jobs/${job.id}/generate-questions`,
        { regenerate: (job.questions?.length || 0) > 0 }
      );
      toast.success(`${result.questions.length} soru olusturuldu!`);
      loadJob();
    } catch (error) {
      toast.error(api.getErrorMessage(error));
    } finally {
      setIsGenerating(false);
    }
  };

  const publishJob = async () => {
    if (!job) return;
    setIsPublishing(true);

    try {
      await api.post(`/jobs/${job.id}/publish`);
      toast.success('Is ilani yayinlandi!');
      loadJob();
    } catch (error) {
      toast.error(api.getErrorMessage(error));
    } finally {
      setIsPublishing(false);
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (!job) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-500">Is ilani bulunamadi.</p>
        <Link to="/jobs" className="btn-primary mt-4">
          Is Ilanlarina Don
        </Link>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Link
          to="/jobs"
          className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <ArrowLeftIcon className="h-5 w-5" />
        </Link>
        <div className="flex-1">
          <div className="flex items-center gap-3">
            <h1 className="text-2xl font-bold text-gray-900">{job.title}</h1>
            <StatusBadge status={job.status} />
          </div>
          <p className="text-gray-500">
            {job.template?.name} &bull; {job.location || 'Konum belirtilmemis'}
          </p>
        </div>
        <div className="flex gap-3">
          {job.status === 'draft' && (job.questions?.length || 0) > 0 && (
            <button
              onClick={publishJob}
              disabled={isPublishing}
              className="btn-primary"
            >
              <PlayIcon className="h-5 w-5 mr-2" />
              {isPublishing ? 'Yayinlaniyor...' : 'Yayinla'}
            </button>
          )}
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Main Content */}
        <div className="lg:col-span-2 space-y-6">
          {/* Questions Section */}
          <div className="card p-6">
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-lg font-semibold text-gray-900">
                Mulakat Sorulari
              </h2>
              <button
                onClick={generateQuestions}
                disabled={isGenerating}
                className="btn-secondary text-sm"
              >
                <SparklesIcon className="h-4 w-4 mr-2" />
                {isGenerating
                  ? 'Olusturuluyor...'
                  : (job.questions?.length || 0) > 0
                  ? 'Yeniden Olustur'
                  : 'AI ile Olustur'}
              </button>
            </div>

            {!job.questions || job.questions.length === 0 ? (
              <div className="text-center py-8 text-gray-500">
                <SparklesIcon className="h-12 w-12 mx-auto mb-3 text-gray-300" />
                <p>Henuz soru olusturulmamis.</p>
                <p className="text-sm">
                  AI ile otomatik soru olusturmak icin butonu kullanin.
                </p>
              </div>
            ) : (
              <div className="space-y-4">
                {job.questions.map((question, index) => (
                  <div
                    key={question.id}
                    className="border rounded-lg p-4"
                  >
                    <div className="flex items-start gap-3">
                      <span className="flex-shrink-0 w-8 h-8 bg-primary-100 text-primary-700 rounded-full flex items-center justify-center text-sm font-medium">
                        {index + 1}
                      </span>
                      <div className="flex-1">
                        <p className="text-gray-900">{question.text}</p>
                        <div className="flex items-center gap-2 mt-2">
                          <span className="badge-blue">{question.type}</span>
                          {question.competency_code && (
                            <span className="badge-gray">
                              {question.competency_code}
                            </span>
                          )}
                          <span className="text-xs text-gray-400">
                            {question.time_limit_seconds}s
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Competencies */}
          <div className="card p-6">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">
              Yetkinlikler
            </h2>
            <div className="space-y-3">
              {job.competencies?.map((comp) => (
                <div
                  key={comp.code}
                  className="flex items-center justify-between"
                >
                  <div>
                    <p className="font-medium text-gray-900">{comp.name}</p>
                    <p className="text-sm text-gray-500">{comp.description}</p>
                  </div>
                  <span className="text-lg font-bold text-primary-600">
                    %{comp.weight}
                  </span>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Stats */}
          <div className="card p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">
              Istatistikler
            </h3>
            <div className="space-y-4">
              <div className="flex justify-between">
                <span className="text-gray-500">Toplam Aday</span>
                <span className="font-semibold">
                  {job.candidates_count || 0}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">Tamamlanan Mulakat</span>
                <span className="font-semibold">
                  {job.interviews_completed || 0}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-500">Soru Sayisi</span>
                <span className="font-semibold">
                  {job.questions?.length || 0}
                </span>
              </div>
            </div>
          </div>

          {/* Red Flags */}
          <div className="card p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">
              Kirmizi Bayraklar
            </h3>
            <div className="space-y-2">
              {job.red_flags?.map((flag) => (
                <div
                  key={flag.code}
                  className="text-sm p-2 bg-red-50 text-red-700 rounded-lg"
                >
                  {flag.description}
                </div>
              ))}
            </div>
          </div>

          {/* Actions */}
          <div className="card p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">
              Islemler
            </h3>
            <div className="space-y-2">
              <Link
                to={`/candidates?job_id=${job.id}`}
                className="btn-secondary w-full"
              >
                Adaylari Gor
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
