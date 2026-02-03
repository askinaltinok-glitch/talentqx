import { Fragment, useRef, useEffect, useState, FormEvent } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import {
  XMarkIcon,
  PaperAirplaneIcon,
  SparklesIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  LightBulbIcon,
  ShieldExclamationIcon,
  ArrowPathIcon,
} from '@heroicons/react/24/outline';
import { useCopilotStore, isStructuredResponse } from '../../stores/copilotStore';
import type { CopilotStructuredResponse, CopilotMessage, CopilotConfidence } from '../../types';
import { COPILOT_CONFIDENCE_COLORS, COPILOT_CONFIDENCE_LABELS, COPILOT_CATEGORY_LABELS } from '../../types';

// Safe markdown renderer - only renders basic formatting
function renderMarkdown(text: string): string {
  return text
    // Bold
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    // Italic
    .replace(/\*(.*?)\*/g, '<em>$1</em>')
    // Code
    .replace(/`(.*?)`/g, '<code class="bg-gray-100 px-1 rounded text-sm">$1</code>')
    // Line breaks
    .replace(/\n/g, '<br />');
}

// Confidence Badge Component
function ConfidenceBadge({ confidence }: { confidence: CopilotConfidence }) {
  const colorClass = COPILOT_CONFIDENCE_COLORS[confidence];
  const label = COPILOT_CONFIDENCE_LABELS[confidence].tr;

  return (
    <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium border ${colorClass}`}>
      {confidence === 'high' && <CheckCircleIcon className="w-3 h-3 mr-1" />}
      {confidence === 'medium' && <LightBulbIcon className="w-3 h-3 mr-1" />}
      {confidence === 'low' && <ExclamationTriangleIcon className="w-3 h-3 mr-1" />}
      {label}
    </span>
  );
}

// Human Decision Warning Component
function HumanDecisionWarning() {
  return (
    <div className="flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm">
      <ShieldExclamationIcon className="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
      <div>
        <p className="font-medium text-amber-800">İnsan Kararı Gerekli</p>
        <p className="text-amber-700 text-xs mt-0.5">
          Bu analiz karar desteği içindir. Nihai işe alım kararı yetkili personel tarafından verilmelidir.
        </p>
      </div>
    </div>
  );
}

// Structured Response Component
function StructuredResponseDisplay({ response }: { response: CopilotStructuredResponse }) {
  const categoryLabel = COPILOT_CATEGORY_LABELS[response.category]?.tr || 'Genel';

  return (
    <div className="space-y-3">
      {/* Header with confidence and category */}
      <div className="flex items-center gap-2 flex-wrap">
        <ConfidenceBadge confidence={response.confidence} />
        <span className="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
          {categoryLabel}
        </span>
      </div>

      {/* Main answer with markdown */}
      <div
        className="text-sm text-gray-700 leading-relaxed prose prose-sm max-w-none"
        dangerouslySetInnerHTML={{ __html: renderMarkdown(response.answer) }}
      />

      {/* Bullets */}
      {response.bullets.length > 0 && (
        <div className="space-y-1">
          <p className="text-xs font-medium text-gray-500 uppercase tracking-wide">Öne Çıkanlar</p>
          <ul className="space-y-1">
            {response.bullets.map((bullet, i) => (
              <li key={i} className="flex items-start gap-2 text-sm text-gray-600">
                <span className="text-primary-500 mt-1">•</span>
                {bullet}
              </li>
            ))}
          </ul>
        </div>
      )}

      {/* Risks */}
      {response.risks.length > 0 && (
        <div className="space-y-1">
          <p className="text-xs font-medium text-red-600 uppercase tracking-wide flex items-center gap-1">
            <ExclamationTriangleIcon className="w-3 h-3" />
            Riskler
          </p>
          <ul className="space-y-1">
            {response.risks.map((risk, i) => (
              <li key={i} className="flex items-start gap-2 text-sm text-red-700 bg-red-50 p-2 rounded">
                <ExclamationTriangleIcon className="w-4 h-4 flex-shrink-0 mt-0.5" />
                {risk}
              </li>
            ))}
          </ul>
        </div>
      )}

      {/* Next Best Actions */}
      {response.next_best_actions.length > 0 && (
        <div className="space-y-1">
          <p className="text-xs font-medium text-primary-600 uppercase tracking-wide flex items-center gap-1">
            <LightBulbIcon className="w-3 h-3" />
            Önerilen Adımlar
          </p>
          <ul className="space-y-1">
            {response.next_best_actions.map((action, i) => (
              <li key={i} className="flex items-start gap-2 text-sm text-gray-600 bg-primary-50 p-2 rounded">
                <span className="text-primary-500 font-bold">{i + 1}.</span>
                {action}
              </li>
            ))}
          </ul>
        </div>
      )}

      {/* Human Decision Warning */}
      {response.needs_human && <HumanDecisionWarning />}
    </div>
  );
}

// Message Component
function MessageBubble({ message }: { message: CopilotMessage }) {
  const isUser = message.role === 'user';
  const isAssistant = message.role === 'assistant';

  if (isUser) {
    return (
      <div className="flex justify-end">
        <div className="max-w-[85%] bg-primary-600 text-white rounded-2xl rounded-tr-sm px-4 py-2">
          <p className="text-sm">{message.content as string}</p>
        </div>
      </div>
    );
  }

  if (isAssistant) {
    const content = message.content;

    return (
      <div className="flex gap-2">
        <div className="w-8 h-8 rounded-full bg-gradient-to-br from-primary-500 to-indigo-600 flex items-center justify-center flex-shrink-0">
          <SparklesIcon className="w-4 h-4 text-white" />
        </div>
        <div className="max-w-[90%] bg-white border border-gray-200 rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm">
          {message.guardrail_triggered && (
            <div className="flex items-center gap-1 text-xs text-amber-600 mb-2">
              <ShieldExclamationIcon className="w-4 h-4" />
              <span>Güvenlik filtresi uygulandı</span>
            </div>
          )}
          {isStructuredResponse(content) ? (
            <StructuredResponseDisplay response={content} />
          ) : (
            <p className="text-sm text-gray-700">{content}</p>
          )}
        </div>
      </div>
    );
  }

  return null;
}

// Loading Indicator
function LoadingIndicator() {
  return (
    <div className="flex gap-2">
      <div className="w-8 h-8 rounded-full bg-gradient-to-br from-primary-500 to-indigo-600 flex items-center justify-center flex-shrink-0">
        <SparklesIcon className="w-4 h-4 text-white animate-pulse" />
      </div>
      <div className="bg-white border border-gray-200 rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm">
        <div className="flex items-center gap-2">
          <div className="flex gap-1">
            <span className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0ms' }} />
            <span className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '150ms' }} />
            <span className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '300ms' }} />
          </div>
          <span className="text-sm text-gray-500">Düşünüyor...</span>
        </div>
      </div>
    </div>
  );
}

// Unavailable State
function UnavailableState({ onRetry }: { onRetry: () => void }) {
  return (
    <div className="flex flex-col items-center justify-center h-full text-center p-6">
      <div className="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
        <ExclamationTriangleIcon className="w-8 h-8 text-gray-400" />
      </div>
      <h3 className="text-lg font-medium text-gray-900 mb-2">Copilot Kullanılamıyor</h3>
      <p className="text-sm text-gray-500 mb-4">
        AI Copilot şu anda geçici olarak kullanılamıyor. Lütfen daha sonra tekrar deneyin.
      </p>
      <button
        onClick={onRetry}
        className="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
      >
        <ArrowPathIcon className="w-4 h-4" />
        Tekrar Dene
      </button>
    </div>
  );
}

// Welcome State
function WelcomeState({ contextPreview, onSuggestionClick }: {
  contextPreview: { summary: string; preview: { position?: string; overall_score?: number; risk_flags?: number } } | null;
  onSuggestionClick: (message: string) => void;
}) {
  const suggestions = [
    'Bu aday hakkında risk analizi yap',
    'Güçlü ve zayıf yönlerini özetle',
    'Mülakat için sorular öner',
    'İşe alım kararı için ne düşünüyorsun?',
  ];

  return (
    <div className="flex flex-col items-center justify-center h-full text-center p-6">
      <div className="w-16 h-16 rounded-full bg-gradient-to-br from-primary-100 to-indigo-100 flex items-center justify-center mb-4">
        <SparklesIcon className="w-8 h-8 text-primary-600" />
      </div>
      <h3 className="text-lg font-medium text-gray-900 mb-2">TalentQX Copilot</h3>
      <p className="text-sm text-gray-500 mb-6">
        Aday analizi, risk değerlendirmesi ve karar desteği için size yardımcı olabilirim.
      </p>

      {contextPreview && (
        <div className="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-6 w-full max-w-sm text-left">
          <p className="text-xs text-gray-500 mb-1">Aktif Bağlam</p>
          <p className="text-sm font-medium text-gray-900">{contextPreview.summary}</p>
          {contextPreview.preview.overall_score !== undefined && (
            <p className="text-xs text-gray-500 mt-1">
              Genel Puan: {contextPreview.preview.overall_score}/100
              {contextPreview.preview.risk_flags !== undefined && contextPreview.preview.risk_flags > 0 && (
                <span className="text-red-600 ml-2">• {contextPreview.preview.risk_flags} risk</span>
              )}
            </p>
          )}
        </div>
      )}

      <div className="space-y-2 w-full max-w-sm">
        <p className="text-xs text-gray-500 mb-2">Önerilen sorular:</p>
        {suggestions.map((suggestion, i) => (
          <button
            key={i}
            onClick={() => onSuggestionClick(suggestion)}
            className="w-full text-left px-3 py-2 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:border-gray-300 transition-colors"
          >
            {suggestion}
          </button>
        ))}
      </div>
    </div>
  );
}

// Main Drawer Component
export default function CopilotDrawer() {
  const {
    isOpen,
    isLoading,
    error,
    messages,
    contextPreview,
    isUnavailable,
    closeDrawer,
    sendMessage,
    clearMessages,
  } = useCopilotStore();

  const [input, setInput] = useState('');
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLTextAreaElement>(null);

  // Scroll to bottom when messages change
  useEffect(() => {
    if (messagesEndRef.current) {
      messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
    }
  }, [messages, isLoading]);

  // Focus input when drawer opens
  useEffect(() => {
    if (isOpen && inputRef.current) {
      setTimeout(() => inputRef.current?.focus(), 300);
    }
  }, [isOpen]);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    if (!input.trim() || isLoading) return;

    const message = input.trim();
    setInput('');
    await sendMessage(message);
  };

  const handleSuggestionClick = (suggestion: string) => {
    sendMessage(suggestion);
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSubmit(e);
    }
  };

  const handleRetry = () => {
    clearMessages();
  };

  return (
    <Transition.Root show={isOpen} as={Fragment}>
      <Dialog as="div" className="relative z-50" onClose={closeDrawer}>
        {/* Backdrop */}
        <Transition.Child
          as={Fragment}
          enter="ease-in-out duration-300"
          enterFrom="opacity-0"
          enterTo="opacity-100"
          leave="ease-in-out duration-300"
          leaveFrom="opacity-100"
          leaveTo="opacity-0"
        >
          <div className="fixed inset-0 bg-gray-500/20 backdrop-blur-sm transition-opacity" />
        </Transition.Child>

        <div className="fixed inset-0 overflow-hidden">
          <div className="absolute inset-0 overflow-hidden">
            <div className="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
              <Transition.Child
                as={Fragment}
                enter="transform transition ease-in-out duration-300"
                enterFrom="translate-x-full"
                enterTo="translate-x-0"
                leave="transform transition ease-in-out duration-300"
                leaveFrom="translate-x-0"
                leaveTo="translate-x-full"
              >
                <Dialog.Panel className="pointer-events-auto w-screen max-w-md">
                  <div className="flex h-full flex-col bg-gray-50 shadow-xl">
                    {/* Header */}
                    <div className="bg-white border-b border-gray-200 px-4 py-4">
                      <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                          <div className="w-10 h-10 rounded-full bg-gradient-to-br from-primary-500 to-indigo-600 flex items-center justify-center">
                            <SparklesIcon className="w-5 h-5 text-white" />
                          </div>
                          <div>
                            <Dialog.Title className="text-lg font-semibold text-gray-900">
                              Copilot
                            </Dialog.Title>
                            <p className="text-xs text-gray-500">AI Karar Desteği</p>
                          </div>
                        </div>
                        <button
                          onClick={closeDrawer}
                          className="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                          <XMarkIcon className="w-5 h-5" />
                        </button>
                      </div>
                    </div>

                    {/* Messages Area */}
                    <div className="flex-1 overflow-y-auto p-4 space-y-4">
                      {isUnavailable ? (
                        <UnavailableState onRetry={handleRetry} />
                      ) : messages.length === 0 ? (
                        <WelcomeState
                          contextPreview={contextPreview}
                          onSuggestionClick={handleSuggestionClick}
                        />
                      ) : (
                        <>
                          {messages.map((message) => (
                            <MessageBubble key={message.id} message={message} />
                          ))}
                          {isLoading && <LoadingIndicator />}
                          {error && !isUnavailable && (
                            <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700">
                              {error}
                            </div>
                          )}
                          <div ref={messagesEndRef} />
                        </>
                      )}
                    </div>

                    {/* Input Area */}
                    {!isUnavailable && (
                      <div className="bg-white border-t border-gray-200 p-4">
                        <form onSubmit={handleSubmit} className="flex gap-2">
                          <div className="flex-1 relative">
                            <textarea
                              ref={inputRef}
                              value={input}
                              onChange={(e) => setInput(e.target.value)}
                              onKeyDown={handleKeyDown}
                              placeholder="Bir soru sorun..."
                              rows={1}
                              className="w-full resize-none rounded-xl border border-gray-300 px-4 py-3 pr-12 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                              style={{ maxHeight: '120px' }}
                              disabled={isLoading}
                            />
                          </div>
                          <button
                            type="submit"
                            disabled={!input.trim() || isLoading}
                            className="p-3 bg-primary-600 text-white rounded-xl hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                          >
                            <PaperAirplaneIcon className="w-5 h-5" />
                          </button>
                        </form>
                        <p className="text-xs text-gray-400 mt-2 text-center">
                          Copilot karar desteği sağlar. Nihai kararlar yetkili personel tarafından verilmelidir.
                        </p>
                      </div>
                    )}
                  </div>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </div>
      </Dialog>
    </Transition.Root>
  );
}
