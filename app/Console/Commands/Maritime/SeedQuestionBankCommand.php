<?php

namespace App\Console\Commands\Maritime;

use App\Models\InterviewQuestionSet;
use App\Models\MaritimeRoleRecord;
use App\Services\Maritime\QuestionBankAssembler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Seeds interview_question_sets from the 4-block question bank.
 *
 * For each active role × supported locale, assembles a 25-question set
 * (12 CORE + 6 ROLE + 4 DEPT + 3 ENGLISH) and upserts it as a single
 * InterviewQuestionSet row with code = 'role_question_bank_v1'.
 *
 * Idempotent: uses updateOrCreate on (code, position_code, locale).
 */
class SeedQuestionBankCommand extends Command
{
    protected $signature = 'maritime:seed-question-bank
                            {--locale=* : Locales to seed (default: en,tr)}
                            {--role=* : Specific role keys (default: all active)}
                            {--dry-run : Show what would be seeded without writing}';

    protected $description = 'Seed interview_question_sets from the role-specific question bank JSON files';

    private const CODE = 'role_question_bank_v1';
    private const VERSION = 1;
    private const DEFAULT_LOCALES = ['en', 'tr'];

    public function handle(QuestionBankAssembler $assembler): int
    {
        $locales = $this->option('locale');
        if (empty($locales)) {
            $locales = self::DEFAULT_LOCALES;
        }

        $roleKeys = $this->option('role');
        if (empty($roleKeys)) {
            $roleKeys = MaritimeRoleRecord::active()->pluck('role_key')->toArray();
        }

        $this->info("Seeding question bank: " . count($roleKeys) . " roles × " . count($locales) . " locales");

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN — no database writes.');
        }

        $seeded = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($roleKeys as $roleKey) {
                foreach ($locales as $locale) {
                    try {
                        $bank = $assembler->forRole($roleKey, $locale);

                        // Flatten all questions into a single array for questions_json
                        $allQuestions = array_merge(
                            $bank['blocks']['core'],
                            $bank['blocks']['role_specific'],
                            $bank['blocks']['dept_safety'],
                            // English gate prompts stored separately in rules_json
                        );

                        $rulesJson = [
                            'assembly_version' => $bank['version'],
                            'role_code'        => $bank['role_code'],
                            'department'       => $bank['department'],
                            'english_gate'     => $bank['blocks']['english_gate'],
                            'question_count'   => $bank['question_count'],
                        ];

                        if ($this->option('dry-run')) {
                            $this->line("  [DRY] {$roleKey} / {$locale} → {$bank['question_count']} questions");
                        } else {
                            InterviewQuestionSet::updateOrCreate(
                                [
                                    'code'          => self::CODE,
                                    'position_code' => $roleKey,
                                    'locale'        => $locale,
                                ],
                                [
                                    'version'        => self::VERSION,
                                    'industry_code'  => 'maritime',
                                    'country_code'   => null,
                                    'is_active'      => true,
                                    'rules_json'     => $rulesJson,
                                    'questions_json'  => $allQuestions,
                                ]
                            );
                        }

                        $seeded++;
                    } catch (\Throwable $e) {
                        $errors[] = "{$roleKey}/{$locale}: {$e->getMessage()}";
                        $this->error("  FAIL {$roleKey}/{$locale}: {$e->getMessage()}");
                    }
                }
            }

            if (!$this->option('dry-run')) {
                DB::commit();
            }

            $this->info("Seeded {$seeded} question sets.");

            if ($errors) {
                $this->warn(count($errors) . " errors encountered:");
                foreach ($errors as $err) {
                    $this->error("  - {$err}");
                }
                return 1;
            }

            // Clear assembler cache after seeding
            QuestionBankAssembler::clearCache();
            $this->info('Assembler cache cleared.');

            return 0;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Seeding failed: {$e->getMessage()}");
            return 1;
        }
    }
}
