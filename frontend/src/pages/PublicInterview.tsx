import { useState, useEffect, useRef } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import {
  CheckCircleIcon,
  ClockIcon,
  ChatBubbleLeftRightIcon,
  ShieldCheckIcon,
  ArrowRightIcon,
  ArrowLeftIcon,
  ExclamationTriangleIcon,
} from '@heroicons/react/24/outline';
import { interviewService } from '../services/interview';
import PrivacyModal from '../components/PrivacyModal';
import type {
  InterviewSession,
  InterviewSessionQuestion,
} from '../types';

type ViewState = 'loading' | 'welcome' | 'interview' | 'completed' | 'error';

interface PrivacyMeta {
  regime: 'KVKK' | 'GDPR' | 'GLOBAL';
  locale: string;
  country: string;
  policy_version: string;
  regime_info: {
    name: string;
    full_name: string;
    authority: string;
    authority_url: string | null;
  };
}

// Role labels are now in i18n: interview.roles.*

export default function PublicInterview() {
  const { role } = useParams<{ role: string }>();
  const [searchParams] = useSearchParams();
  const { t, i18n } = useTranslation('common');
  const locale = i18n.language?.slice(0, 2) || 'tr';

  // State
  const [view, setView] = useState<ViewState>('loading');
  const [error, setError] = useState<string | null>(null);
  const [privacyMeta, setPrivacyMeta] = useState<PrivacyMeta | null>(null);
  const [showPrivacyModal, setShowPrivacyModal] = useState(false);
  const [hasScrolledToEnd, setHasScrolledToEnd] = useState(false);

  // Session state
  const [session, setSession] = useState<InterviewSession | null>(null);
  const [questions, setQuestions] = useState<InterviewSessionQuestion[]>([]);
  const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
  const [answers, setAnswers] = useState<Record<number, string>>({});
  const [submitting, setSubmitting] = useState(false);

  // Timing
  const [questionStartTime, setQuestionStartTime] = useState<number>(Date.now());
  const textareaRef = useRef<HTMLTextAreaElement>(null);

  // Get candidate_id and context from URL params
  const candidateId = searchParams.get('candidate') || crypto.randomUUID();
  const contextKey = searchParams.get('context') || undefined;
  const roleKey = role || 'store_manager';
  const roleLabel = t(`interview.roles.${roleKey}`, roleKey);
  const contextLabel = contextKey ? t(`interview.contexts.${contextKey}`, contextKey) : null;

  // Load privacy meta on mount
  useEffect(() => {
    interviewService.getPrivacyMeta()
      .then(setPrivacyMeta)
      .catch(() => {
        // Use defaults
        setPrivacyMeta({
          regime: 'KVKK',
          locale: 'tr',
          country: 'TR',
          policy_version: '2026-01',
          regime_info: {
            name: 'KVKK',
            full_name: 'Kişisel Verilerin Korunması Kanunu',
            authority: 'KVKK Kurumu',
            authority_url: null,
          },
        });
      })
      .finally(() => setView('welcome'));
  }, []);

  // Focus textarea when question changes
  useEffect(() => {
    if (view === 'interview' && textareaRef.current) {
      textareaRef.current.focus();
    }
    setQuestionStartTime(Date.now());
  }, [currentQuestionIndex, view]);

  // Handle consent accept
  const handleAcceptConsent = async () => {
    if (!privacyMeta) return;

    setShowPrivacyModal(false);
    setSubmitting(true);
    setError(null);

    try {
      // Create session
      const result = await interviewService.createSession({
        candidate_id: candidateId,
        role_key: roleKey,
        context_key: contextKey,
        locale,
        consent_accepted: true,
        regime: privacyMeta.regime,
        policy_version: privacyMeta.policy_version,
        data_categories: ['text', 'ai_analysis', 'response_time'],
        retention_days: 365,
        automated_decision: true,
      });

      // Get questions
      const questionsData = await interviewService.getQuestions(result.session_id);

      setSession({
        id: result.session_id,
        candidate_id: candidateId,
        role_key: roleKey,
        context_key: contextKey,
        locale,
        status: 'started',
        started_at: result.started_at,
      });
      setQuestions(questionsData.questions);
      setView('interview');
    } catch (err) {
      setError(err instanceof Error ? err.message : t('interview.errorMessage'));
      setView('error');
    } finally {
      setSubmitting(false);
    }
  };

  // Handle answer change
  const handleAnswerChange = (value: string) => {
    if (!questions[currentQuestionIndex]) return;
    setAnswers(prev => ({
      ...prev,
      [questions[currentQuestionIndex].id]: value,
    }));
  };

  // Submit current answer and move to next
  const handleNextQuestion = async () => {
    if (!session || !questions[currentQuestionIndex]) return;

    const currentQuestion = questions[currentQuestionIndex];
    const answerText = answers[currentQuestion.id] || '';

    if (!answerText.trim()) {
      setError(t('interview.answerRequired'));
      return;
    }

    setSubmitting(true);
    setError(null);

    try {
      const responseTime = Date.now() - questionStartTime;

      await interviewService.submitAnswer(session.id, {
        question_id: currentQuestion.id,
        answer_type: 'text',
        raw_text: answerText.trim(),
        response_time_ms: responseTime,
      });

      if (currentQuestionIndex < questions.length - 1) {
        setCurrentQuestionIndex(prev => prev + 1);
      } else {
        // Complete interview
        await interviewService.complete(session.id);
        setView('completed');
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : t('interview.networkError'));
    } finally {
      setSubmitting(false);
    }
  };

  // Go to previous question
  const handlePrevQuestion = () => {
    if (currentQuestionIndex > 0) {
      setCurrentQuestionIndex(prev => prev - 1);
    }
  };

  // Render loading
  if (view === 'loading') {
    return (
      <div className="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 flex items-center justify-center">
        <div className="text-center">
          <div className="w-16 h-16 border-4 border-primary-600 border-t-transparent rounded-full animate-spin mx-auto mb-4" />
          <p className="text-gray-600">{t('interview.loading')}</p>
        </div>
      </div>
    );
  }

  // Render error
  if (view === 'error') {
    return (
      <div className="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 flex items-center justify-center p-4">
        <div className="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full text-center">
          <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <ExclamationTriangleIcon className="w-8 h-8 text-red-600" />
          </div>
          <h1 className="text-xl font-bold text-gray-900 mb-2">
            {t('interview.errorTitle')}
          </h1>
          <p className="text-gray-600 mb-6">{error || t('interview.errorMessage')}</p>
          <button
            onClick={() => window.location.reload()}
            className="btn btn-primary"
          >
            {t('interview.tryAgain')}
          </button>
        </div>
      </div>
    );
  }

  // Render welcome/consent screen
  if (view === 'welcome') {
    return (
      <div className="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 flex items-center justify-center p-4">
        <div className="bg-white rounded-2xl shadow-xl p-8 max-w-lg w-full">
          {/* Header */}
          <div className="text-center mb-8">
            <div className="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <ChatBubbleLeftRightIcon className="w-10 h-10 text-primary-600" />
            </div>
            <h1 className="text-2xl font-bold text-gray-900 mb-2">
              {t('interview.title')}
            </h1>
            <p className="text-gray-600">
              {t('interview.welcomeSubtitle', { role: roleLabel })}
            </p>
            {contextLabel && (
              <p className="text-sm text-primary-600 mt-1 font-medium">
                {locale === 'tr' ? 'Bağlam: ' : 'Context: '}{contextLabel}
              </p>
            )}
          </div>

          {/* Info cards */}
          <div className="space-y-3 mb-8">
            <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
              <ClockIcon className="w-5 h-5 text-gray-500" />
              <span className="text-sm text-gray-700">
                {locale === 'tr' ? 'Yaklaşık 10-15 dakika' : 'Approximately 10-15 minutes'}
              </span>
            </div>
            <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
              <ChatBubbleLeftRightIcon className="w-5 h-5 text-gray-500" />
              <span className="text-sm text-gray-700">
                {locale === 'tr' ? '8 soru cevaplayacaksınız' : 'You will answer 8 questions'}
              </span>
            </div>
            <div className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
              <ShieldCheckIcon className="w-5 h-5 text-gray-500" />
              <span className="text-sm text-gray-700">
                {locale === 'tr'
                  ? 'Verileriniz güvende (KVKK uyumlu)'
                  : 'Your data is secure (GDPR compliant)'}
              </span>
            </div>
          </div>

          {/* Consent section */}
          <div className="border-t pt-6">
            <label className="flex items-start gap-3 cursor-pointer mb-4">
              <input
                type="checkbox"
                checked={hasScrolledToEnd}
                onChange={(e) => setHasScrolledToEnd(e.target.checked)}
                className="mt-1 h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
              />
              <span className="text-sm text-gray-700">
                {locale === 'tr' ? (
                  <>
                    <button
                      type="button"
                      onClick={() => setShowPrivacyModal(true)}
                      className="text-primary-600 hover:underline font-medium"
                    >
                      Gizlilik politikasını
                    </button>{' '}
                    okudum ve kabul ediyorum. Verilerimin yapay zeka ile analiz edileceğini anlıyorum.
                  </>
                ) : (
                  <>
                    I have read and accept the{' '}
                    <button
                      type="button"
                      onClick={() => setShowPrivacyModal(true)}
                      className="text-primary-600 hover:underline font-medium"
                    >
                      privacy policy
                    </button>
                    . I understand my data will be analyzed using AI.
                  </>
                )}
              </span>
            </label>

            <button
              onClick={handleAcceptConsent}
              disabled={!hasScrolledToEnd || submitting}
              className="w-full btn btn-primary py-3 text-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
            >
              {submitting ? (
                <>
                  <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                  {t('buttons.loading')}
                </>
              ) : (
                <>
                  {t('interview.startInterview')}
                  <ArrowRightIcon className="w-5 h-5" />
                </>
              )}
            </button>
          </div>
        </div>

        {/* Privacy Modal */}
        <PrivacyModal
          isOpen={showPrivacyModal}
          onClose={() => setShowPrivacyModal(false)}
          onAccept={() => {
            setShowPrivacyModal(false);
            setHasScrolledToEnd(true);
          }}
          requireScrollToEnd={true}
          privacyMeta={privacyMeta}
        />
      </div>
    );
  }

  // Render interview questions
  if (view === 'interview' && questions.length > 0) {
    const currentQuestion = questions[currentQuestionIndex];
    const currentAnswer = answers[currentQuestion.id] || '';
    const progress = ((currentQuestionIndex + 1) / questions.length) * 100;

    return (
      <div className="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 flex flex-col">
        {/* Progress bar */}
        <div className="bg-white border-b sticky top-0 z-10">
          <div className="max-w-3xl mx-auto px-4 py-4">
            <div className="flex items-center justify-between mb-2">
              <span className="text-sm font-medium text-gray-700">
                {t('interview.questionProgress', { current: currentQuestionIndex + 1, total: questions.length })}
              </span>
              <span className="text-sm text-gray-500">
                {Math.round(progress)}%
              </span>
            </div>
            <div className="h-2 bg-gray-200 rounded-full overflow-hidden">
              <div
                className="h-full bg-primary-600 transition-all duration-300"
                style={{ width: `${progress}%` }}
              />
            </div>
          </div>
        </div>

        {/* Question content */}
        <div className="flex-1 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl shadow-xl p-8 max-w-3xl w-full">
            {/* Question type badge */}
            <div className="flex items-center gap-2 mb-4">
              <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                currentQuestion.type === 'scenario'
                  ? 'bg-purple-100 text-purple-800'
                  : 'bg-blue-100 text-blue-800'
              }`}>
                {currentQuestion.type === 'scenario'
                  ? (locale === 'tr' ? 'Senaryo' : 'Scenario')
                  : (locale === 'tr' ? 'Genel' : 'General')}
              </span>
            </div>

            {/* Question */}
            <h2 className="text-xl font-semibold text-gray-900 mb-6">
              {currentQuestion.prompt}
            </h2>

            {/* Answer input */}
            <div className="mb-6">
              <textarea
                ref={textareaRef}
                value={currentAnswer}
                onChange={(e) => handleAnswerChange(e.target.value)}
                placeholder={t('interview.answerPlaceholder')}
                className="w-full h-40 p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none text-gray-900"
                disabled={submitting}
              />
              <div className="flex justify-between mt-2 text-sm text-gray-500">
                <span>{currentAnswer.length} {locale === 'tr' ? 'karakter' : 'characters'}</span>
                <span>
                  {locale === 'tr' ? 'Detaylı cevaplar daha iyi değerlendirilir' : 'Detailed answers are evaluated better'}
                </span>
              </div>
            </div>

            {/* Error message */}
            {error && (
              <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                {error}
              </div>
            )}

            {/* Navigation */}
            <div className="flex items-center justify-between">
              <button
                onClick={handlePrevQuestion}
                disabled={currentQuestionIndex === 0 || submitting}
                className="flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-gray-900 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <ArrowLeftIcon className="w-5 h-5" />
                {t('interview.previousQuestion')}
              </button>

              <button
                onClick={handleNextQuestion}
                disabled={!currentAnswer.trim() || submitting}
                className="btn btn-primary flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {submitting ? (
                  <>
                    <div className="w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                    {t('interview.submitting')}
                  </>
                ) : currentQuestionIndex === questions.length - 1 ? (
                  <>
                    {t('interview.finishInterview')}
                    <CheckCircleIcon className="w-5 h-5" />
                  </>
                ) : (
                  <>
                    {t('interview.nextQuestion')}
                    <ArrowRightIcon className="w-5 h-5" />
                  </>
                )}
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  // Render completed
  if (view === 'completed') {
    return (
      <div className="min-h-screen bg-gradient-to-b from-gray-50 to-gray-100 flex items-center justify-center p-4">
        <div className="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full text-center">
          <div className="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <CheckCircleIcon className="w-10 h-10 text-green-600" />
          </div>
          <h1 className="text-2xl font-bold text-gray-900 mb-2">
            {t('interview.completedTitle')}
          </h1>
          <p className="text-gray-600 mb-6">
            {t('interview.completedMessage')}
          </p>
          <div className="p-4 bg-gray-50 rounded-lg text-sm text-gray-600">
            {t('interview.completedSubtext')}
          </div>
        </div>
      </div>
    );
  }

  return null;
}
