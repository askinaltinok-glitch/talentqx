import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import api from '../services/api';
import type { Interview } from '../types';
import StatusBadge from '../components/StatusBadge';
import { CopilotDrawer, CopilotButton } from '../components/copilot';
import { useCopilotStore } from '../stores/copilotStore';
import toast from 'react-hot-toast';

export default function InterviewDetail() {
  const { id } = useParams<{ id: string }>();
  const [interview, setInterview] = useState<Interview | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [activeResponse, setActiveResponse] = useState(0);
  const { setContext } = useCopilotStore();

  useEffect(() => {
    if (id) loadInterview();
  }, [id]);

  // Set Copilot context when interview is loaded
  useEffect(() => {
    if (id) {
      setContext({ type: 'interview', id });
    }
  }, [id, setContext]);

  const loadInterview = async () => {
    try {
      const data = await api.get<Interview>(`/interviews/${id}`);
      setInterview(data);
    } catch (error) {
      toast.error('Mulakat bilgileri yuklenemedi.');
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

  if (!interview) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-500">Mulakat bulunamadi.</p>
        <Link to="/candidates" className="btn-primary mt-4">
          Adaylara Don
        </Link>
      </div>
    );
  }

  const currentResponse = interview.responses[activeResponse];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <Link
          to={`/candidates/${interview.candidate.id}`}
          className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <ArrowLeftIcon className="h-5 w-5" />
        </Link>
        <div className="flex-1">
          <div className="flex items-center gap-3">
            <h1 className="text-2xl font-bold text-gray-900">
              Mulakat Sonuclari
            </h1>
            <StatusBadge status={interview.status} />
          </div>
          <p className="text-gray-500">
            {interview.candidate.first_name} {interview.candidate.last_name} &bull;{' '}
            {interview.job.title}
          </p>
        </div>
        <CopilotButton
          context={{ type: 'interview', id: interview.id }}
          variant="primary"
          size="md"
        />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Video Player & Transcript */}
        <div className="lg:col-span-2 space-y-6">
          {/* Video Player */}
          <div className="card overflow-hidden">
            <div className="aspect-video bg-gray-900 relative">
              {currentResponse?.video_segment_url ? (
                <video
                  key={currentResponse.video_segment_url}
                  className="w-full h-full object-contain"
                  controls
                >
                  <source src={currentResponse.video_segment_url} />
                </video>
              ) : (
                <div className="flex items-center justify-center h-full text-white">
                  <p>Video mevcut degil</p>
                </div>
              )}
            </div>

            {/* Question Navigation */}
            <div className="p-4 border-t">
              <div className="flex items-center gap-2 overflow-x-auto pb-2">
                {interview.responses.map((response, index) => (
                  <button
                    key={response.id}
                    onClick={() => setActiveResponse(index)}
                    className={`flex-shrink-0 px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                      index === activeResponse
                        ? 'bg-primary-600 text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    }`}
                  >
                    Soru {index + 1}
                  </button>
                ))}
              </div>
            </div>
          </div>

          {/* Current Question & Transcript */}
          {currentResponse && (
            <div className="card p-6">
              <div className="mb-4">
                <span className="badge-blue">
                  Soru {activeResponse + 1}
                </span>
                <h3 className="text-lg font-semibold text-gray-900 mt-2">
                  {currentResponse.question.text}
                </h3>
                {currentResponse.question.competency_code && (
                  <p className="text-sm text-gray-500 mt-1">
                    Yetkinlik: {currentResponse.question.competency_code}
                  </p>
                )}
              </div>

              <div className="border-t pt-4">
                <h4 className="text-sm font-medium text-gray-700 mb-2">
                  Transkript
                </h4>
                {currentResponse.transcript ? (
                  <p className="text-gray-600 whitespace-pre-wrap">
                    {currentResponse.transcript}
                  </p>
                ) : (
                  <p className="text-gray-400 italic">
                    Transkript henuz olusturulmamis.
                  </p>
                )}
              </div>

              {currentResponse.duration_seconds && (
                <p className="text-xs text-gray-400 mt-4">
                  Sure: {Math.floor(currentResponse.duration_seconds / 60)}:
                  {(currentResponse.duration_seconds % 60)
                    .toString()
                    .padStart(2, '0')}
                </p>
              )}
            </div>
          )}

          {/* Question Analysis */}
          {interview.analysis?.question_analyses && (
            <div className="card p-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-4">
                Soru {activeResponse + 1} Analizi
              </h2>
              {(() => {
                const qa = interview.analysis?.question_analyses?.find(
                  (q: any) => q.question_order === activeResponse + 1
                );
                if (!qa) return <p className="text-gray-500">Analiz bulunamadi.</p>;

                return (
                  <div className="space-y-4">
                    <div className="flex items-center gap-4">
                      <div className="text-3xl font-bold text-primary-600">
                        {qa.score}/5
                      </div>
                      <div className="flex-1">
                        <div className="h-2 bg-gray-100 rounded-full overflow-hidden">
                          <div
                            className="h-full bg-primary-500"
                            style={{ width: `${(qa.score / 5) * 100}%` }}
                          />
                        </div>
                      </div>
                    </div>

                    <p className="text-gray-600">{qa.analysis}</p>

                    {qa.positive_points?.length > 0 && (
                      <div>
                        <h4 className="text-sm font-medium text-green-700 mb-1">
                          Olumlu Noktalar
                        </h4>
                        <ul className="text-sm text-green-600 space-y-1">
                          {qa.positive_points.map((point: string, i: number) => (
                            <li key={i}>+ {point}</li>
                          ))}
                        </ul>
                      </div>
                    )}

                    {qa.negative_points?.length > 0 && (
                      <div>
                        <h4 className="text-sm font-medium text-red-700 mb-1">
                          Gelistirilecek Noktalar
                        </h4>
                        <ul className="text-sm text-red-600 space-y-1">
                          {qa.negative_points.map((point: string, i: number) => (
                            <li key={i}>- {point}</li>
                          ))}
                        </ul>
                      </div>
                    )}
                  </div>
                );
              })()}
            </div>
          )}
        </div>

        {/* Sidebar - All Questions */}
        <div className="space-y-6">
          <div className="card p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">
              Tum Sorular
            </h3>
            <div className="space-y-2">
              {interview.responses.map((response, index) => {
                const qa = interview.analysis?.question_analyses?.find(
                  (q: any) => q.question_order === index + 1
                );

                return (
                  <button
                    key={response.id}
                    onClick={() => setActiveResponse(index)}
                    className={`w-full text-left p-3 rounded-lg border transition-colors ${
                      index === activeResponse
                        ? 'border-primary-500 bg-primary-50'
                        : 'border-gray-200 hover:border-gray-300'
                    }`}
                  >
                    <div className="flex items-center justify-between">
                      <span className="text-sm font-medium">
                        Soru {index + 1}
                      </span>
                      {qa && (
                        <span
                          className={`text-sm font-bold ${
                            qa.score >= 4
                              ? 'text-green-600'
                              : qa.score >= 3
                              ? 'text-yellow-600'
                              : 'text-red-600'
                          }`}
                        >
                          {qa.score}/5
                        </span>
                      )}
                    </div>
                    <p className="text-xs text-gray-500 truncate mt-1">
                      {response.question.text}
                    </p>
                  </button>
                );
              })}
            </div>
          </div>

          {/* Interview Info */}
          <div className="card p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">
              Mulakat Bilgileri
            </h3>
            <div className="space-y-3 text-sm">
              <div className="flex justify-between">
                <span className="text-gray-500">Durum</span>
                <StatusBadge status={interview.status} size="sm" />
              </div>
              {interview.started_at && (
                <div className="flex justify-between">
                  <span className="text-gray-500">Baslangic</span>
                  <span>
                    {new Date(interview.started_at).toLocaleString('tr-TR')}
                  </span>
                </div>
              )}
              {interview.completed_at && (
                <div className="flex justify-between">
                  <span className="text-gray-500">Bitis</span>
                  <span>
                    {new Date(interview.completed_at).toLocaleString('tr-TR')}
                  </span>
                </div>
              )}
              {interview.duration_seconds && (
                <div className="flex justify-between">
                  <span className="text-gray-500">Toplam Sure</span>
                  <span>
                    {Math.floor(interview.duration_seconds / 60)} dakika
                  </span>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Copilot Drawer */}
      <CopilotDrawer />
    </div>
  );
}
