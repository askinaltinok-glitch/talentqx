<?php

namespace App\Services\Interview;

use App\Models\Job;
use App\Models\JobQuestion;
use App\Services\AI\LLMProviderInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuestionGenerator
{
    public function __construct(
        private LLMProviderInterface $llmProvider
    ) {}

    private const SUPPORTED_QUESTION_LOCALES = ['tr', 'en', 'de', 'fr', 'ar'];

    /**
     * Generate questions for a job posting.
     *
     * @param bool $regenerate  Delete existing questions and regenerate
     * @param string $locale    Override locale (auto-detected from job if not provided)
     * @param bool $useAi       Whether to attempt AI generation (false = taxonomy/defaults only, memory-safe)
     */
    public function generateForJob(Job $job, bool $regenerate = false, string $locale = 'tr', bool $useAi = false): array
    {
        // Auto-detect locale from job if not explicitly provided
        $locale = $job->locale ?? $locale;
        if (!in_array($locale, self::SUPPORTED_QUESTION_LOCALES)) {
            $locale = 'tr';
        }

        if (!$regenerate && $job->questions()->exists()) {
            return $job->questions->toArray();
        }

        // If job has a taxonomy position, use pre-defined questions
        if ($job->job_position_id) {
            $taxonomyQuestions = $this->getQuestionsFromTaxonomy($job, $locale);
            if (!empty($taxonomyQuestions)) {
                return $this->saveQuestions($job, $taxonomyQuestions, $regenerate);
            }
        }

        // AI generation only when explicitly requested (separate endpoint)
        // Skipped in store() to avoid memory exhaustion in synchronous request
        if ($useAi) {
            $competencies = $job->getEffectiveCompetencies();
            $questionRules = $job->getEffectiveQuestionRules();

            // Build rich context with sector info for AI
            $context = [
                'position_name' => $job->template?->name ?? $job->title,
                'sample_questions' => $job->template?->question_rules['sample_questions'] ?? [],
                'locale' => $locale,
                'job_title' => $job->title,
                'job_description' => $job->description,
            ];

            // Add taxonomy domain/subdomain if available
            if ($job->job_position_id) {
                $job->loadMissing('jobPosition.subdomain.domain');
                $context['domain'] = $job->jobPosition?->subdomain?->domain?->name_tr;
                $context['subdomain'] = $job->jobPosition?->subdomain?->name_tr;
                $context['position'] = $job->jobPosition?->name_tr;
            }

            try {
                $generatedQuestions = $this->llmProvider->generateQuestions(
                    $competencies,
                    $questionRules,
                    $context
                );

                $questions = $generatedQuestions['questions'] ?? [];
                if (!empty($questions)) {
                    return $this->saveQuestions($job, $questions, $regenerate);
                }
            } catch (\Exception $e) {
                Log::warning('AI question generation failed, using defaults', [
                    'job_id' => $job->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Final fallback: default generic questions based on job title and locale
        return $this->saveQuestions($job, $this->getDefaultQuestions($job->title, $locale), $regenerate);
    }

    /**
     * Pull pre-defined questions from position_questions taxonomy.
     * Resolves question text based on locale with fallback chain: requested → tr → en.
     */
    private function getQuestionsFromTaxonomy(Job $job, string $locale = 'tr'): array
    {
        $questionColumn = in_array($locale, self::SUPPORTED_QUESTION_LOCALES)
            ? "pq.question_{$locale}"
            : 'pq.question_tr';

        // COALESCE: requested locale → TR fallback → EN fallback
        $coalesce = "COALESCE({$questionColumn}, pq.question_tr, pq.question_en)";

        $positionQuestions = DB::table('position_questions as pq')
            ->join('competencies as c', 'c.id', '=', 'pq.competency_id')
            ->where('pq.position_id', $job->job_position_id)
            ->where('pq.is_active', true)
            ->orderBy('pq.sort_order')
            ->select(
                DB::raw("{$coalesce} as question_localized"),
                'pq.question_tr',
                'pq.question_en',
                'pq.question_type',
                'c.code as competency_code',
                'pq.expected_indicators',
                'pq.red_flag_indicators',
                'pq.time_limit_seconds',
                'pq.is_mandatory',
                'pq.sort_order'
            )
            ->get();

        if ($positionQuestions->isEmpty()) {
            return [];
        }

        return $positionQuestions->map(fn($q) => [
            'question_type' => $q->question_type,
            'question_text' => $q->question_localized ?? $q->question_tr ?? $q->question_en,
            'competency_code' => $q->competency_code,
            'ideal_answer_points' => json_decode($q->expected_indicators, true) ?? [],
            'scoring_rubric' => json_decode($q->red_flag_indicators, true) ?? [],
            'time_limit_seconds' => $q->time_limit_seconds ?? 120,
            'is_required' => $q->is_mandatory,
        ])->toArray();
    }

    private function saveQuestions(Job $job, array $questions, bool $regenerate): array
    {
        return DB::transaction(function () use ($job, $questions, $regenerate) {
            if ($regenerate) {
                $job->questions()->delete();
            }

            $savedQuestions = [];
            $order = 1;

            foreach ($questions as $questionData) {
                $question = JobQuestion::create([
                    'job_id' => $job->id,
                    'question_order' => $order++,
                    'question_type' => $questionData['question_type'],
                    'question_text' => $questionData['question_text'],
                    'competency_code' => $questionData['competency_code'] ?? null,
                    'ideal_answer_points' => $questionData['ideal_answer_points'] ?? [],
                    'time_limit_seconds' => $questionData['time_limit_seconds'] ?? 180,
                ]);

                $savedQuestions[] = $question;
            }

            return $savedQuestions;
        });
    }

    /**
     * Default generic interview questions when taxonomy and AI are unavailable.
     * Supports all 5 TalentQX locales with proper UTF-8 encoding.
     */
    private function getDefaultQuestions(string $jobTitle, string $locale = 'tr'): array
    {
        $questionSets = [
            'tr' => [
                ['competency_code' => 'motivation', 'question_text' => "Bu pozisyon ({$jobTitle}) için neden başvurduğunuzu ve bu alanda kendinizi nasıl geliştirdiğinizi anlatır mısınız?"],
                ['competency_code' => 'experience', 'question_text' => 'Daha önce benzer bir pozisyonda çalışma deneyiminiz var mı? Varsa günlük iş akışınızı ve sorumluluklarınızı anlatır mısınız?'],
                ['competency_code' => 'problem_solving', 'question_text' => 'İş hayatınızda karşılaştığınız en zorlu durumu ve bunu nasıl çözdüğünüzü anlatır mısınız?'],
                ['competency_code' => 'teamwork', 'question_text' => 'Takım çalışmasında size en çok katkıda bulunan özelliğiniz nedir? Bir örnekle açıklar mısınız?'],
                ['competency_code' => 'customer_relations', 'question_text' => 'Zor veya şikayetçi bir müşteri ile karşılaştığınız bir durumu anlatır mısınız? Nasıl bir yaklaşım sergilediniz ve sonuç ne oldu?'],
                ['competency_code' => 'stress_management', 'question_text' => 'Yoğun tempolu ve stresli bir iş gününüzü nasıl yönetirsiniz? Önceliklendirme konusunda nasıl bir yöntem izlersiniz?'],
                ['competency_code' => 'career_goals', 'question_text' => 'Gelecek 3-5 yıl içindeki kariyer hedefleriniz nelerdir ve bu pozisyon bu hedeflerinize nasıl katkıda bulunur?'],
                ['competency_code' => 'adaptability', 'question_text' => 'Yeni bir iş ortamına, farklı çalışma arkadaşlarına veya değişen iş süreçlerine uyum sağlamanız gereken bir durumu anlatır mısınız?'],
            ],
            'en' => [
                ['competency_code' => 'motivation', 'question_text' => "Why did you apply for this position ({$jobTitle}) and how have you developed yourself in this field?"],
                ['competency_code' => 'experience', 'question_text' => 'Do you have experience working in a similar position? If so, could you describe your daily workflow and responsibilities?'],
                ['competency_code' => 'problem_solving', 'question_text' => 'Can you describe the most challenging situation you faced in your career and how you resolved it?'],
                ['competency_code' => 'teamwork', 'question_text' => 'What is your strongest quality in teamwork? Can you explain with an example?'],
                ['competency_code' => 'customer_relations', 'question_text' => 'Can you describe a situation where you dealt with a difficult or complaining customer? What approach did you take and what was the outcome?'],
                ['competency_code' => 'stress_management', 'question_text' => 'How do you manage a high-paced and stressful workday? What method do you use for prioritization?'],
                ['competency_code' => 'career_goals', 'question_text' => 'What are your career goals for the next 3-5 years and how does this position contribute to those goals?'],
                ['competency_code' => 'adaptability', 'question_text' => 'Can you describe a situation where you had to adapt to a new work environment, different colleagues, or changing processes?'],
            ],
            'de' => [
                ['competency_code' => 'motivation', 'question_text' => "Warum haben Sie sich für diese Position ({$jobTitle}) beworben und wie haben Sie sich in diesem Bereich weiterentwickelt?"],
                ['competency_code' => 'experience', 'question_text' => 'Haben Sie Erfahrung in einer ähnlichen Position? Wenn ja, könnten Sie Ihren täglichen Arbeitsablauf und Ihre Aufgaben beschreiben?'],
                ['competency_code' => 'problem_solving', 'question_text' => 'Können Sie die größte Herausforderung in Ihrem Berufsleben beschreiben und wie Sie diese gelöst haben?'],
                ['competency_code' => 'teamwork', 'question_text' => 'Was ist Ihre stärkste Eigenschaft in der Teamarbeit? Können Sie dies mit einem Beispiel erläutern?'],
                ['competency_code' => 'customer_relations', 'question_text' => 'Können Sie eine Situation beschreiben, in der Sie mit einem schwierigen oder unzufriedenen Kunden umgehen mussten? Wie sind Sie vorgegangen und was war das Ergebnis?'],
                ['competency_code' => 'stress_management', 'question_text' => 'Wie bewältigen Sie einen stressigen Arbeitstag mit hohem Tempo? Welche Methode nutzen Sie zur Priorisierung?'],
                ['competency_code' => 'career_goals', 'question_text' => 'Was sind Ihre Karriereziele für die nächsten 3-5 Jahre und wie trägt diese Position dazu bei?'],
                ['competency_code' => 'adaptability', 'question_text' => 'Können Sie eine Situation beschreiben, in der Sie sich an ein neues Arbeitsumfeld, andere Kollegen oder veränderte Prozesse anpassen mussten?'],
            ],
            'fr' => [
                ['competency_code' => 'motivation', 'question_text' => "Pourquoi avez-vous postulé pour ce poste ({$jobTitle}) et comment vous êtes-vous développé dans ce domaine ?"],
                ['competency_code' => 'experience', 'question_text' => 'Avez-vous une expérience dans un poste similaire ? Si oui, pourriez-vous décrire votre flux de travail quotidien et vos responsabilités ?'],
                ['competency_code' => 'problem_solving', 'question_text' => 'Pouvez-vous décrire la situation la plus difficile que vous avez rencontrée dans votre carrière et comment vous l\'avez résolue ?'],
                ['competency_code' => 'teamwork', 'question_text' => 'Quelle est votre qualité la plus importante dans le travail d\'équipe ? Pouvez-vous l\'illustrer par un exemple ?'],
                ['competency_code' => 'customer_relations', 'question_text' => 'Pouvez-vous décrire une situation où vous avez dû gérer un client difficile ou mécontent ? Quelle approche avez-vous adoptée et quel a été le résultat ?'],
                ['competency_code' => 'stress_management', 'question_text' => 'Comment gérez-vous une journée de travail intense et stressante ? Quelle méthode utilisez-vous pour hiérarchiser vos priorités ?'],
                ['competency_code' => 'career_goals', 'question_text' => 'Quels sont vos objectifs de carrière pour les 3 à 5 prochaines années et comment ce poste y contribue-t-il ?'],
                ['competency_code' => 'adaptability', 'question_text' => 'Pouvez-vous décrire une situation où vous avez dû vous adapter à un nouvel environnement de travail, de nouveaux collègues ou des processus changeants ?'],
            ],
            'ar' => [
                ['competency_code' => 'motivation', 'question_text' => "لماذا تقدمت لهذه الوظيفة ({$jobTitle}) وكيف طورت نفسك في هذا المجال؟"],
                ['competency_code' => 'experience', 'question_text' => 'هل لديك خبرة في العمل بوظيفة مماثلة؟ إذا كان الأمر كذلك، هل يمكنك وصف سير عملك اليومي ومسؤولياتك؟'],
                ['competency_code' => 'problem_solving', 'question_text' => 'هل يمكنك وصف أصعب موقف واجهته في حياتك المهنية وكيف تعاملت معه؟'],
                ['competency_code' => 'teamwork', 'question_text' => 'ما هي أقوى صفة لديك في العمل الجماعي؟ هل يمكنك توضيح ذلك بمثال؟'],
                ['competency_code' => 'customer_relations', 'question_text' => 'هل يمكنك وصف موقف تعاملت فيه مع عميل صعب أو غاضب؟ ما النهج الذي اتبعته وما كانت النتيجة؟'],
                ['competency_code' => 'stress_management', 'question_text' => 'كيف تدير يوم عمل مكثف ومليء بالضغوط؟ ما الطريقة التي تستخدمها لتحديد الأولويات؟'],
                ['competency_code' => 'career_goals', 'question_text' => 'ما هي أهدافك المهنية للسنوات الثلاث إلى الخمس القادمة وكيف تساهم هذه الوظيفة في تحقيقها؟'],
                ['competency_code' => 'adaptability', 'question_text' => 'هل يمكنك وصف موقف اضطررت فيه للتكيف مع بيئة عمل جديدة أو زملاء مختلفين أو عمليات متغيرة؟'],
            ],
        ];

        $questions = $questionSets[$locale] ?? $questionSets['tr'];

        return array_map(fn($q) => [
            'question_type' => 'open_ended',
            'question_text' => $q['question_text'],
            'competency_code' => $q['competency_code'],
            'ideal_answer_points' => [],
            'time_limit_seconds' => 180,
            'is_required' => true,
        ], $questions);
    }

    public function generateSampleQuestions(array $competencies, array $questionRules): array
    {
        return $this->llmProvider->generateQuestions($competencies, $questionRules);
    }
}
