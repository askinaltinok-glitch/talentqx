import { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import {
  CheckCircleIcon,
  ExclamationCircleIcon,
  ClockIcon,
  ArrowRightIcon,
} from '@heroicons/react/24/outline';

interface Question {
  id: string;
  order: number;
  type: string;
  text: string;
  text_en?: string;
  max_score: number;
}

interface Template {
  id: string;
  name: string;
  time_limit_minutes: number;
  questions: Question[];
  scoring_config?: {
    instructions?: {
      tr?: string;
      en?: string;
    };
  };
}

interface Session {
  session_id: string;
  status: 'pending' | 'in_progress' | 'completed' | 'analyzed';
  employee_name: string;
  template: Template;
  responses?: Record<string, unknown>[];
  started_at?: string;
}

type ViewState = 'loading' | 'welcome' | 'in_progress' | 'completed' | 'error';

const API_BASE = import.meta.env.VITE_API_URL || 'https://talentqx.com/api/v1';

export default function PublicAssessment() {
  const { token } = useParams<{ token: string }>();
  const [session, setSession] = useState<Session | null>(null);
  const [viewState, setViewState] = useState<ViewState>('loading');
  const [error, setError] = useState<string>('');
  const [currentQuestion, setCurrentQuestion] = useState(0);
  const [answers, setAnswers] = useState<Record<number, string>>({});
  const [questionStartTime, setQuestionStartTime] = useState<number>(Date.now());
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [kvkkAccepted, setKvkkAccepted] = useState(false);

  // Fetch session on mount
  useEffect(() => {
    if (!token) return;

    fetch(`${API_BASE}/assessments/public/${token}`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          setSession(data.data);
          if (data.data.status === 'pending') {
            setViewState('welcome');
          } else if (data.data.status === 'in_progress') {
            // Resume from where they left off
            const responses = data.data.responses || [];
            setCurrentQuestion(responses.length);
            setViewState('in_progress');
          } else if (data.data.status === 'completed' || data.data.status === 'analyzed') {
            setViewState('completed');
          }
        } else {
          setError(data.error?.message || 'Değerlendirme bulunamadı.');
          setViewState('error');
        }
      })
      .catch(() => {
        setError('Bağlantı hatası. Lütfen tekrar deneyin.');
        setViewState('error');
      });
  }, [token]);

  const startAssessment = async () => {
    if (!token || !kvkkAccepted) return;

    setIsSubmitting(true);
    try {
      const res = await fetch(`${API_BASE}/assessments/public/${token}/start`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
      });
      const data = await res.json();

      if (data.success) {
        setSession(prev => prev ? { ...prev, status: 'in_progress' } : null);
        setViewState('in_progress');
        setQuestionStartTime(Date.now());
      } else {
        setError(data.error?.message || 'Başlatılamadı.');
        setViewState('error');
      }
    } catch {
      setError('Bağlantı hatası.');
      setViewState('error');
    } finally {
      setIsSubmitting(false);
    }
  };

  const submitAnswer = async () => {
    if (!token || !session) return;

    const currentAnswer = answers[currentQuestion];
    if (!currentAnswer || currentAnswer.trim().length < 10) {
      alert('Lütfen en az 2-3 cümle yazın.');
      return;
    }

    const elapsed = Math.round((Date.now() - questionStartTime) / 1000);
    setIsSubmitting(true);

    try {
      const res = await fetch(`${API_BASE}/assessments/public/${token}/responses`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          question_order: currentQuestion + 1,
          answer: currentAnswer,
          time_spent: elapsed,
        }),
      });
      const data = await res.json();

      if (data.success) {
        const questions = session.template.questions;
        if (currentQuestion + 1 < questions.length) {
          setCurrentQuestion(currentQuestion + 1);
          setQuestionStartTime(Date.now());
        } else {
          // Complete the assessment
          await completeAssessment();
        }
      } else {
        alert(data.error?.message || 'Hata oluştu.');
      }
    } catch {
      alert('Bağlantı hatası.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const completeAssessment = async () => {
    if (!token) return;

    try {
      const res = await fetch(`${API_BASE}/assessments/public/${token}/complete`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
      });
      const data = await res.json();

      if (data.success) {
        setViewState('completed');
      } else {
        setError(data.error?.message || 'Tamamlanamadı.');
        setViewState('error');
      }
    } catch {
      setError('Bağlantı hatası.');
      setViewState('error');
    }
  };

  // Loading state
  if (viewState === 'loading') {
    return (
      <div className="min-h-screen bg-gradient-to-br from-indigo-50 to-white flex items-center justify-center">
        <div className="text-center">
          <div className="w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto" />
          <p className="mt-4 text-gray-600">Yükleniyor...</p>
        </div>
      </div>
    );
  }

  // Error state
  if (viewState === 'error') {
    return (
      <div className="min-h-screen bg-gradient-to-br from-red-50 to-white flex items-center justify-center p-4">
        <div className="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center">
          <ExclamationCircleIcon className="w-16 h-16 text-red-500 mx-auto" />
          <h1 className="mt-4 text-2xl font-bold text-gray-900">Bir Hata Oluştu</h1>
          <p className="mt-2 text-gray-600">{error}</p>
        </div>
      </div>
    );
  }

  // Completed state
  if (viewState === 'completed') {
    return (
      <div className="min-h-screen bg-gradient-to-br from-green-50 to-white flex items-center justify-center p-4">
        <div className="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center">
          <CheckCircleIcon className="w-16 h-16 text-green-500 mx-auto" />
          <h1 className="mt-4 text-2xl font-bold text-gray-900">Değerlendirme Tamamlandı</h1>
          <p className="mt-2 text-gray-600">
            Cevaplarınız kaydedildi ve analiz ediliyor. Teşekkür ederiz!
          </p>
          <p className="mt-4 text-sm text-gray-500">
            Bu sayfayı güvenle kapatabilirsiniz.
          </p>
        </div>
      </div>
    );
  }

  // Welcome state
  if (viewState === 'welcome' && session) {
    const instructions = session.template.scoring_config?.instructions?.tr ||
      'Lütfen her soruya kısa ama net şekilde, gerçek hayatta nasıl davranacağınızı anlatarak cevap verin.';

    return (
      <div className="min-h-screen bg-gradient-to-br from-indigo-50 to-white flex items-center justify-center p-4">
        <div className="max-w-lg w-full bg-white rounded-2xl shadow-xl p-8">
          <div className="text-center">
            <div className="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto">
              <ClockIcon className="w-8 h-8 text-indigo-600" />
            </div>
            <h1 className="mt-4 text-2xl font-bold text-gray-900">
              Yetkinlik Değerlendirmesi
            </h1>
            <p className="mt-2 text-gray-600">
              Merhaba <span className="font-semibold">{session.employee_name?.split(' ')[0] || 'Değerli Katılımcı'}</span>!
            </p>
          </div>

          <div className="mt-6 bg-indigo-50 rounded-xl p-4">
            <h2 className="font-semibold text-indigo-900">{session.template.name}</h2>
            <div className="mt-2 flex items-center gap-4 text-sm text-indigo-700">
              <span>{session.template.questions.length} Soru</span>
              <span>•</span>
              <span>{session.template.time_limit_minutes} Dakika</span>
            </div>
          </div>

          <div className="mt-6">
            <h3 className="font-medium text-gray-900">Talimatlar</h3>
            <p className="mt-2 text-sm text-gray-600">{instructions}</p>
          </div>

          <div className="mt-6 bg-gray-50 rounded-xl p-4">
            <label className="flex items-start gap-3 cursor-pointer">
              <input
                type="checkbox"
                checked={kvkkAccepted}
                onChange={(e) => setKvkkAccepted(e.target.checked)}
                className="mt-1 w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500"
              />
              <span className="text-sm text-gray-600">
                Değerlendirme kapsamında paylaştığım bilgilerin işlenmesini ve analiz edilmesini
                kabul ediyorum. (KVKK Aydınlatma Metni)
              </span>
            </label>
          </div>

          <button
            onClick={startAssessment}
            disabled={!kvkkAccepted || isSubmitting}
            className="mt-6 w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300
                     text-white font-semibold py-3 px-6 rounded-xl transition-colors
                     flex items-center justify-center gap-2"
          >
            {isSubmitting ? (
              <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
            ) : (
              <>
                Değerlendirmeyi Başlat
                <ArrowRightIcon className="w-5 h-5" />
              </>
            )}
          </button>
        </div>
      </div>
    );
  }

  // In progress state
  if (viewState === 'in_progress' && session) {
    const questions = session.template.questions;
    const question = questions[currentQuestion];
    const progress = ((currentQuestion) / questions.length) * 100;

    return (
      <div className="min-h-screen bg-gradient-to-br from-indigo-50 to-white py-8 px-4">
        <div className="max-w-2xl mx-auto">
          {/* Progress bar */}
          <div className="bg-white rounded-full h-2 mb-6 overflow-hidden">
            <div
              className="bg-indigo-600 h-full transition-all duration-300"
              style={{ width: `${progress}%` }}
            />
          </div>

          <div className="bg-white rounded-2xl shadow-xl p-6 md:p-8">
            {/* Question header */}
            <div className="flex items-center justify-between mb-6">
              <span className="text-sm font-medium text-indigo-600">
                Soru {currentQuestion + 1} / {questions.length}
              </span>
              <span className="text-sm text-gray-500">
                {session.template.name}
              </span>
            </div>

            {/* Question text */}
            <h2 className="text-xl font-semibold text-gray-900 mb-6">
              {question.text}
            </h2>

            {/* Answer textarea */}
            <textarea
              value={answers[currentQuestion] || ''}
              onChange={(e) => setAnswers(prev => ({ ...prev, [currentQuestion]: e.target.value }))}
              placeholder="Cevabınızı buraya yazın... (En az 2-3 cümle)"
              className="w-full h-48 p-4 border border-gray-200 rounded-xl resize-none
                       focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                       placeholder:text-gray-400"
              disabled={isSubmitting}
            />

            {/* Character count */}
            <div className="mt-2 text-sm text-gray-500 text-right">
              {(answers[currentQuestion] || '').length} karakter
            </div>

            {/* Submit button */}
            <button
              onClick={submitAnswer}
              disabled={isSubmitting || !answers[currentQuestion] || answers[currentQuestion].trim().length < 10}
              className="mt-6 w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300
                       text-white font-semibold py-3 px-6 rounded-xl transition-colors
                       flex items-center justify-center gap-2"
            >
              {isSubmitting ? (
                <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
              ) : currentQuestion + 1 < questions.length ? (
                <>
                  Sonraki Soru
                  <ArrowRightIcon className="w-5 h-5" />
                </>
              ) : (
                <>
                  Değerlendirmeyi Tamamla
                  <CheckCircleIcon className="w-5 h-5" />
                </>
              )}
            </button>
          </div>
        </div>
      </div>
    );
  }

  return null;
}
