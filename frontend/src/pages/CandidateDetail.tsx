import { useEffect, useState } from 'react';
import { useParams, Link } from 'react-router-dom';
import { ArrowLeftIcon, PlayIcon } from '@heroicons/react/24/outline';
import api from '../services/api';
import type { Candidate } from '../types';
import StatusBadge from '../components/StatusBadge';
import ScoreCircle from '../components/ScoreCircle';
import RecommendationBadge from '../components/RecommendationBadge';
import { CopilotDrawer, CopilotButton } from '../components/copilot';
import { useCopilotStore } from '../stores/copilotStore';
import toast from 'react-hot-toast';

export default function CandidateDetail() {
  const { id } = useParams<{ id: string }>();
  const [candidate, setCandidate] = useState<Candidate | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const { setContext } = useCopilotStore();

  useEffect(() => {
    if (id) loadCandidate();
  }, [id]);

  // Set Copilot context when candidate is loaded
  useEffect(() => {
    if (id) {
      setContext({ type: 'candidate', id });
    }
  }, [id, setContext]);

  const loadCandidate = async () => {
    try {
      const data = await api.get<Candidate>(`/candidates/${id}`);
      setCandidate(data);
    } catch (error) {
      toast.error('Aday bilgileri yuklenemedi.');
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

  if (!candidate) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-500">Aday bulunamadi.</p>
        <Link to="/candidates" className="btn-primary mt-4">
          Adaylara Don
        </Link>
      </div>
    );
  }

  const analysis = (candidate as any).analysis;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center gap-4">
        <Link
          to="/candidates"
          className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
        >
          <ArrowLeftIcon className="h-5 w-5" />
        </Link>
        <div className="flex-1">
          <div className="flex items-center gap-3">
            <h1 className="text-2xl font-bold text-gray-900">
              {candidate.first_name} {candidate.last_name}
            </h1>
            <StatusBadge status={candidate.status} />
          </div>
          <p className="text-gray-500">
            {candidate.job.title} &bull; {candidate.email}
          </p>
        </div>
        <div className="flex items-center gap-2">
          <CopilotButton
            context={{ type: 'candidate', id: candidate.id }}
            variant="primary"
            size="md"
          />
          {(candidate as any).interview && (
            <Link
              to={`/interviews/${(candidate as any).interview.id}`}
              className="btn-primary"
            >
              <PlayIcon className="h-5 w-5 mr-2" />
              Mulakati Izle
            </Link>
          )}
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Main Content */}
        <div className="lg:col-span-2 space-y-6">
          {/* AI Analysis Summary */}
          {analysis && (
            <div className="card p-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-4">
                AI Degerlendirme Ozeti
              </h2>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div className="text-center">
                  <ScoreCircle
                    score={analysis.overall_score || 0}
                    size="lg"
                    showLabel
                    label="Genel Puan"
                  />
                </div>
                <div className="text-center">
                  <ScoreCircle
                    score={analysis.culture_fit?.overall_fit || 0}
                    size="lg"
                    showLabel
                    label="Kultur Uyumu"
                  />
                </div>
                <div className="flex flex-col items-center justify-center">
                  <RecommendationBadge
                    recommendation={
                      analysis.decision_snapshot?.recommendation || 'hold'
                    }
                    confidence={
                      analysis.decision_snapshot?.confidence_percent
                    }
                    size="lg"
                  />
                  <p className="text-sm text-gray-500 mt-2">AI Onerisi</p>
                </div>
              </div>

              {/* Reasons */}
              {analysis.decision_snapshot?.reasons && (
                <div className="border-t pt-4">
                  <h3 className="text-sm font-medium text-gray-700 mb-2">
                    Degerlendirme Nedenleri
                  </h3>
                  <ul className="space-y-1">
                    {analysis.decision_snapshot.reasons.map(
                      (reason: string, i: number) => (
                        <li
                          key={i}
                          className="text-sm text-gray-600 flex items-start gap-2"
                        >
                          <span className="text-primary-500">•</span>
                          {reason}
                        </li>
                      )
                    )}
                  </ul>
                </div>
              )}
            </div>
          )}

          {/* Competency Scores */}
          {analysis?.competency_scores && (
            <div className="card p-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-4">
                Yetkinlik Puanlari
              </h2>
              <div className="space-y-4">
                {Object.entries(analysis.competency_scores).map(
                  ([code, data]: [string, any]) => (
                    <div key={code}>
                      <div className="flex justify-between text-sm mb-1">
                        <span className="font-medium text-gray-900">
                          {code.replace(/_/g, ' ')}
                        </span>
                        <span className="text-gray-600">
                          {data.score}/100
                        </span>
                      </div>
                      <div className="h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div
                          className={`h-full transition-all ${
                            data.score >= 70
                              ? 'bg-green-500'
                              : data.score >= 50
                              ? 'bg-yellow-500'
                              : 'bg-red-500'
                          }`}
                          style={{ width: `${data.score}%` }}
                        />
                      </div>
                      {data.evidence && data.evidence.length > 0 && (
                        <p className="text-xs text-gray-500 mt-1">
                          {data.evidence[0]}
                        </p>
                      )}
                    </div>
                  )
                )}
              </div>
            </div>
          )}

          {/* Red Flags */}
          {analysis?.red_flag_analysis?.flags_detected && (
            <div className="card p-6 border-red-200 bg-red-50">
              <h2 className="text-lg font-semibold text-red-900 mb-4">
                Kirmizi Bayraklar
              </h2>
              <div className="space-y-3">
                {analysis.red_flag_analysis.flags.map(
                  (flag: any, i: number) => (
                    <div
                      key={i}
                      className="bg-white p-3 rounded-lg border border-red-200"
                    >
                      <p className="text-sm font-medium text-red-800">
                        {flag.code.replace(/_/g, ' ')}
                      </p>
                      <p className="text-sm text-red-600 mt-1">
                        "{flag.detected_phrase}"
                      </p>
                      <p className="text-xs text-red-500 mt-1">
                        Soru #{flag.question_order} &bull; Ciddiyet:{' '}
                        {flag.severity}
                      </p>
                    </div>
                  )
                )}
              </div>
            </div>
          )}
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Contact Info */}
          <div className="card p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">
              Iletisim Bilgileri
            </h3>
            <div className="space-y-3 text-sm">
              <div>
                <p className="text-gray-500">E-posta</p>
                <p className="font-medium">{candidate.email}</p>
              </div>
              {candidate.phone && (
                <div>
                  <p className="text-gray-500">Telefon</p>
                  <p className="font-medium">{candidate.phone}</p>
                </div>
              )}
              {candidate.source && (
                <div>
                  <p className="text-gray-500">Kaynak</p>
                  <p className="font-medium">{candidate.source}</p>
                </div>
              )}
            </div>
          </div>

          {/* CV Match */}
          {candidate.cv_match_score !== undefined && (
            <div className="card p-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">
                Ozgecmis Uyumu
              </h3>
              <div className="flex justify-center">
                <ScoreCircle
                  score={candidate.cv_match_score}
                  size="lg"
                  showLabel
                  label="Uyum Orani"
                />
              </div>
            </div>
          )}

          {/* Status Actions */}
          <div className="card p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">
              Durum Guncelle
            </h3>
            <div className="space-y-2">
              <StatusUpdateButton
                candidateId={candidate.id}
                status="shortlisted"
                label="Kisa Listeye Al"
                onUpdate={loadCandidate}
              />
              <StatusUpdateButton
                candidateId={candidate.id}
                status="hired"
                label="Ise Al"
                onUpdate={loadCandidate}
              />
              <StatusUpdateButton
                candidateId={candidate.id}
                status="rejected"
                label="Reddet"
                variant="danger"
                onUpdate={loadCandidate}
              />
            </div>
          </div>
        </div>
      </div>

      {/* Copilot Drawer */}
      <CopilotDrawer />
    </div>
  );
}

interface StatusUpdateButtonProps {
  candidateId: string;
  status: string;
  label: string;
  variant?: 'primary' | 'danger';
  onUpdate: () => void;
}

function StatusUpdateButton({
  candidateId,
  status,
  label,
  variant = 'primary',
  onUpdate,
}: StatusUpdateButtonProps) {
  const [isLoading, setIsLoading] = useState(false);

  const handleClick = async () => {
    setIsLoading(true);
    try {
      await api.patch(`/candidates/${candidateId}/status`, { status });
      toast.success('Durum guncellendi!');
      onUpdate();
    } catch (error) {
      toast.error(api.getErrorMessage(error));
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <button
      onClick={handleClick}
      disabled={isLoading}
      className={`w-full ${variant === 'danger' ? 'btn-danger' : 'btn-secondary'}`}
    >
      {isLoading ? 'Guncelleniyor...' : label}
    </button>
  );
}
