<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * English language templates seeder
 * Adds language='en' versions of existing templates
 */
class EnglishTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            $this->getGenericTemplateEN(),
            $this->getRetailCashierTemplateEN(),
        ];

        foreach ($templates as $template) {
            $this->upsertTemplate($template);
        }

        echo "\n========================================\n";
        echo "ENGLISH TEMPLATES SEEDED\n";
        echo "========================================\n";

        $rows = DB::table('interview_templates')
            ->select('position_code', 'language', 'title', 'is_active')
            ->where('is_active', true)
            ->orderBy('language')
            ->orderBy('position_code')
            ->get();

        foreach ($rows as $row) {
            echo sprintf("  [%s] %-20s | %s\n", $row->language, $row->position_code, $row->title);
        }

        echo "\nTotal active templates: " . count($rows) . "\n";
    }

    private function upsertTemplate(array $data): void
    {
        $existing = DB::table('interview_templates')
            ->where('version', 'v1')
            ->where('language', 'en')
            ->where('position_code', $data['position_code'])
            ->first();

        $templateJson = json_encode($data['template'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if (!$existing) {
            DB::table('interview_templates')->insert([
                'id' => (string) Str::uuid(),
                'version' => 'v1',
                'language' => 'en',
                'position_code' => $data['position_code'],
                'title' => $data['title'],
                'template_json' => $templateJson,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "  [INSERT] en/{$data['position_code']}\n";
        } else {
            DB::table('interview_templates')
                ->where('id', $existing->id)
                ->update([
                    'title' => $data['title'],
                    'template_json' => $templateJson,
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
            echo "  [UPDATE] en/{$data['position_code']}\n";
        }
    }

    private function getGenericTemplateEN(): array
    {
        return [
            'position_code' => '__generic__',
            'title' => 'Generic Interview Template (English)',
            'template' => [
                'version' => 'v1',
                'language' => 'en',
                'generic_template' => [
                    'questions' => [
                        [
                            'slot' => 1,
                            'competency' => 'communication',
                            'question' => 'Can you describe a situation where you had to explain a complex topic in simple terms? What did you do and what was the outcome?',
                            'method' => 'STAR',
                            'scoring_rubric' => [
                                '1' => 'Failed to explain, no listener perspective, confused and scattered explanation',
                                '2' => 'Basic information conveyed but unstructured, no adaptation to listener',
                                '3' => 'Clear explanation, basic structure present, open to feedback',
                                '4' => 'Clear and organized explanation, adapted to listener level, open to questions',
                                '5' => 'Excellent structure, empathetic listener-focused explanation, effective feedback loop',
                            ],
                            'positive_signals' => [
                                'Asked about listener knowledge level',
                                'Used examples and analogies',
                                'Checked for understanding',
                                'Adapted approach based on feedback',
                            ],
                            'red_flag_hooks' => [
                                ['code' => 'RF_AVOID', 'trigger_guidance' => 'Avoiding communication responsibility: "not my job", "someone else should handle it"', 'severity' => 'medium'],
                            ],
                        ],
                        [
                            'slot' => 2,
                            'competency' => 'accountability',
                            'question' => 'Can you describe a situation at work where you made a mistake or something went wrong? How did you handle it?',
                            'method' => 'BEI',
                            'scoring_rubric' => [
                                '1' => 'Denied the mistake or blamed others entirely, took no responsibility',
                                '2' => 'Acknowledged mistake but took no action to fix it, remained passive',
                                '3' => 'Acknowledged mistake and took basic corrective steps',
                                '4' => 'Took full responsibility, proactively found solution, informed stakeholders',
                                '5' => 'Owned the mistake, developed systematic solution, proposed process improvement',
                            ],
                            'positive_signals' => [
                                'Clearly acknowledged the mistake',
                                'Did not blame others',
                                'Described concrete corrective actions',
                                'Shared lessons learned',
                            ],
                            'red_flag_hooks' => [
                                ['code' => 'RF_BLAME', 'trigger_guidance' => 'Constantly referring to external factors: "team didn\'t support me", "manager gave wrong direction"', 'severity' => 'high'],
                                ['code' => 'RF_INCONSIST', 'trigger_guidance' => 'Inconsistencies in story: first blaming others then taking ownership, contradictory details', 'severity' => 'high'],
                            ],
                        ],
                        [
                            'slot' => 3,
                            'competency' => 'teamwork',
                            'question' => 'Can you describe a project where you worked with team members who had different viewpoints? How did you manage the different perspectives?',
                            'method' => 'STAR',
                            'scoring_rubric' => [
                                '1' => 'Avoided teamwork or imposed own view, did not seek consensus',
                                '2' => 'Passive participation, did not express views or ignored conflict',
                                '3' => 'Listened to different views, made basic consensus efforts',
                                '4' => 'Actively integrated different perspectives, created constructive discussion environment',
                                '5' => 'Created synergy from differences, ensured everyone\'s participation, guided toward common goal',
                            ],
                            'positive_signals' => [
                                'Actively asked for others\' ideas',
                                'Open to changing own view',
                                'Managed conflict constructively',
                                'Put team success before individual success',
                            ],
                            'red_flag_hooks' => [
                                ['code' => 'RF_EGO', 'trigger_guidance' => 'Taking credit for team success: "they actually implemented my idea", "they couldn\'t do it without me"', 'severity' => 'medium'],
                                ['code' => 'RF_AGGRESSION', 'trigger_guidance' => 'Demeaning expressions toward team members: insults, personal attacks, angry tone', 'severity' => 'critical'],
                            ],
                        ],
                        [
                            'slot' => 4,
                            'competency' => 'stress_resilience',
                            'question' => 'Can you describe a period when you worked under intense pressure with multiple priorities at once? How did you cope?',
                            'method' => 'BEI',
                            'scoring_rubric' => [
                                '1' => 'Collapsed under stress, couldn\'t complete tasks, panic or avoidance behavior',
                                '2' => 'Completed with difficulty, no stress management strategy, reactive approach',
                                '3' => 'Completed tasks, did basic prioritization, moderate stress management',
                                '4' => 'Effective prioritization, stayed calm with systematic approach, maintained quality',
                                '5' => 'Outstanding performance under pressure, calmed others, used stress as motivation',
                            ],
                            'positive_signals' => [
                                'Described concrete prioritization method',
                                'Showed emotional control',
                                'Asked for help when needed',
                                'Learned lessons for the future',
                            ],
                            'red_flag_hooks' => [
                                ['code' => 'RF_UNSTABLE', 'trigger_guidance' => 'Uncontrolled reactions to stress: "I exploded", "I walked out", "I completely lost control"', 'severity' => 'medium'],
                                ['code' => 'RF_AVOID', 'trigger_guidance' => 'Systematic avoidance of stressful situations: "that\'s not my kind of work", "I don\'t take that kind of responsibility"', 'severity' => 'medium'],
                            ],
                        ],
                        [
                            'slot' => 5,
                            'competency' => 'adaptability',
                            'question' => 'How did you adapt when there was an unexpected change at your workplace? Can you give an example?',
                            'method' => 'STAR',
                            'scoring_rubric' => [
                                '1' => 'Resisted change, didn\'t adapt, complained or obstructed',
                                '2' => 'Adapted reluctantly, maintained negative attitude',
                                '3' => 'Accepted the change, adapted in reasonable time',
                                '4' => 'Quickly embraced change, worked efficiently in new situation, helped others adapt',
                                '5' => 'Turned change into opportunity, offered proactive suggestions, took change leader role',
                            ],
                            'positive_signals' => [
                                'Tried to understand reason for change',
                                'Quickly acquired new skills',
                                'Maintained positive attitude',
                                'Supported others\' adaptation',
                            ],
                            'red_flag_hooks' => [
                                ['code' => 'RF_AVOID', 'trigger_guidance' => 'Escape and rejection of change: "I don\'t do that", "not my job to learn new system"', 'severity' => 'medium'],
                            ],
                        ],
                        [
                            'slot' => 6,
                            'competency' => 'learning_agility',
                            'question' => 'Can you describe a situation where you needed to learn a completely new topic or skill quickly? How did you approach it?',
                            'method' => 'STAR',
                            'scoring_rubric' => [
                                '1' => 'Learning reluctance, passive attitude, remained dependent on others',
                                '2' => 'Learned at basic level but couldn\'t deepen, did only what was required',
                                '3' => 'Active learning effort, used standard resources, learned in reasonable time',
                                '4' => 'Fast and effective learning, used multiple resources, immediately applied learning',
                                '5' => 'Outstanding learning speed, improved what was learned, trained others',
                            ],
                            'positive_signals' => [
                                'Used multiple learning resources',
                                'Not afraid to ask questions',
                                'Applied learning in practice',
                                'Expressed enjoyment of learning process',
                            ],
                            'red_flag_hooks' => [
                                ['code' => 'RF_AVOID', 'trigger_guidance' => 'Avoiding learning responsibility: "not my job to learn new things", "someone else should teach me"', 'severity' => 'medium'],
                            ],
                        ],
                        [
                            'slot' => 7,
                            'competency' => 'integrity',
                            'question' => 'Can you describe a situation where you faced an ethically difficult decision? How did you behave?',
                            'method' => 'BEI',
                            'scoring_rubric' => [
                                '1' => 'Described unethical behavior or normalized bending rules',
                                '2' => 'Recognized ethical dilemma but didn\'t act, remained passive',
                                '3' => 'Did the right thing but only because required, internal motivation unclear',
                                '4' => 'Stayed true to ethical principles, made right decision even in difficult situation, consistent behavior',
                                '5' => 'Showed ethical leadership, guided others to right behavior, took risk to defend what\'s right',
                            ],
                            'positive_signals' => [
                                'Described clear and consistent ethical framework',
                                'Did right thing despite personal cost',
                                'Emphasized transparency and honesty',
                                'Resisted unethical pressure',
                            ],
                            'red_flag_hooks' => [
                                ['code' => 'RF_INCONSIST', 'trigger_guidance' => 'Ethical inconsistency: situational rules, "everyone does it" normalization', 'severity' => 'high'],
                                ['code' => 'RF_BLAME', 'trigger_guidance' => 'Blaming others for ethical violations: "manager forced me", "system is set up that way"', 'severity' => 'high'],
                            ],
                        ],
                        [
                            'slot' => 8,
                            'competency' => 'role_competence',
                            'question' => 'Can you describe an experience where you performed one of the core requirements of this position? What approach did you use and what was the outcome?',
                            'method' => 'STAR',
                            'scoring_rubric' => [
                                '1' => 'No relevant experience or very superficial, showed lack of understanding of basic requirements',
                                '2' => 'Limited experience, knows basic concepts but weak in application',
                                '3' => 'Adequate experience, correctly applied standard processes, acceptable results',
                                '4' => 'Strong experience, produced quality and measurable results, improved process',
                                '5' => 'Outstanding performance, developed innovative approaches, capable of training others',
                            ],
                            'positive_signals' => [
                                'Shared concrete and measurable results',
                                'Listed process steps correctly and logically',
                                'Explained how problems were solved',
                                'Gave examples of continuous improvement',
                            ],
                            'red_flag_hooks' => [
                                ['code' => 'RF_INCONSIST', 'trigger_guidance' => 'Competency exaggeration: inconsistencies when details asked, vague answers when explanation requested', 'severity' => 'high'],
                                ['code' => 'RF_EGO', 'trigger_guidance' => 'Unrealistic confidence: "I do this job best", "nobody knows as much as me"', 'severity' => 'medium'],
                            ],
                        ],
                    ],
                ],
                'positions' => [],
            ],
        ];
    }

    private function getRetailCashierTemplateEN(): array
    {
        return [
            'position_code' => 'retail_cashier',
            'title' => 'Cashier Interview Template (English)',
            'template' => [
                'version' => 'v1',
                'language' => 'en',
                'position' => [
                    'position_code' => 'retail_cashier',
                    'title_tr' => 'Kasiyer',
                    'title_en' => 'Cashier',
                    'category' => 'Retail',
                    'skill_gate' => ['gate' => 45, 'action' => 'HOLD', 'safety_critical' => false],
                ],
                'questions' => [
                    [
                        'slot' => 1,
                        'competency' => 'communication',
                        'question' => 'Can you describe a situation where you had difficulty communicating with a customer? How did you resolve it?',
                        'method' => 'STAR',
                        'scoring_rubric' => [
                            '1' => 'Couldn\'t communicate with customer, defensive or indifferent attitude',
                            '2' => 'Basic communication but ineffective, customer satisfaction not achieved',
                            '3' => 'Listened to problem and offered standard solution, acceptable communication',
                            '4' => 'Listened with empathy, gave clear explanation, satisfied customer',
                            '5' => 'Excellent customer communication, turned difficult situation into opportunity, created customer loyalty',
                        ],
                        'positive_signals' => [
                            'Actively listened to customer',
                            'Was patient and respectful',
                            'Solution-focused approach',
                            'Received feedback',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_AGGRESSION', 'trigger_guidance' => 'Demeaning expressions toward customers: insults, condescension, "stupid customer"', 'severity' => 'critical'],
                        ],
                    ],
                    [
                        'slot' => 2,
                        'competency' => 'accountability',
                        'question' => 'How did you behave when you made a mistake at the register or there was a cash discrepancy?',
                        'method' => 'BEI',
                        'scoring_rubric' => [
                            '1' => 'Hid mistake or blamed someone else, took no responsibility',
                            '2' => 'Acknowledged mistake but didn\'t produce solution, remained passive',
                            '3' => 'Reported mistake and followed procedure',
                            '4' => 'Reported immediately, investigated cause, took corrective action',
                            '5' => 'Full transparency, root cause analysis, proposed prevention measures',
                        ],
                        'positive_signals' => [
                            'Reported mistake immediately',
                            'Apologized and took ownership',
                            'Took action to correct',
                            'Followed procedure',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_BLAME', 'trigger_guidance' => 'Blaming cash discrepancy on external factors: "because of system error", "because of customer"', 'severity' => 'high'],
                            ['code' => 'RF_INCONSIST', 'trigger_guidance' => 'Inconsistent explanations: contradictory information about numbers or events', 'severity' => 'high'],
                        ],
                    ],
                    [
                        'slot' => 3,
                        'competency' => 'teamwork',
                        'question' => 'Can you describe how you supported your team members on a busy day?',
                        'method' => 'STAR',
                        'scoring_rubric' => [
                            '1' => 'Focused only on own work, didn\'t support team',
                            '2' => 'Helped when asked but not proactive',
                            '3' => 'Noticed team needs and provided basic support',
                            '4' => 'Proactively offered help, shared workload',
                            '5' => 'Provided team coordination, balanced everyone\'s load, increased motivation',
                        ],
                        'positive_signals' => [
                            'Offered help',
                            'Showed flexibility',
                            'Emphasized team success',
                            'Stayed in communication',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_EGO', 'trigger_guidance' => 'Highlighting individual success: "I worked the most", "they couldn\'t do it without me"', 'severity' => 'medium'],
                        ],
                    ],
                    [
                        'slot' => 4,
                        'competency' => 'stress_resilience',
                        'question' => 'How do you behave when there\'s a long queue at the register and customers are getting impatient?',
                        'method' => 'BEI',
                        'scoring_rubric' => [
                            '1' => 'Panicked and made mistakes or treated customers poorly',
                            '2' => 'Looked stressed, had slowdowns, showed irritation',
                            '3' => 'Tried to stay calm, continued at standard speed',
                            '4' => 'Maintained composure, balanced speed and accuracy, informed customers',
                            '5' => 'Outstanding performance under pressure, calmed customers, organized team',
                        ],
                        'positive_signals' => [
                            'Was calm and in control',
                            'Kept customers informed',
                            'Maintained speed and accuracy',
                            'Asked for help when needed',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_UNSTABLE', 'trigger_guidance' => 'Uncontrolled stress reactions: "I exploded", "I started crying", "I walked out"', 'severity' => 'medium'],
                            ['code' => 'RF_AGGRESSION', 'trigger_guidance' => 'Harsh reactions to customers or team under stress', 'severity' => 'critical'],
                        ],
                    ],
                    [
                        'slot' => 5,
                        'competency' => 'adaptability',
                        'question' => 'How did you adapt when a new register system or sales procedure changed?',
                        'method' => 'STAR',
                        'scoring_rubric' => [
                            '1' => 'Resisted change, continued using old method',
                            '2' => 'Learned reluctantly, complained, slow adaptation',
                            '3' => 'Accepted change, learned in reasonable time',
                            '4' => 'Quickly adapted, saw advantages of new system',
                            '5' => 'Embraced change, helped others adapt, suggested improvements',
                        ],
                        'positive_signals' => [
                            'Open to learning',
                            'Asked questions',
                            'Practiced',
                            'Helped others',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_AVOID', 'trigger_guidance' => 'Avoiding change and learning responsibility: "not my job to learn new system"', 'severity' => 'medium'],
                        ],
                    ],
                    [
                        'slot' => 6,
                        'competency' => 'learning_agility',
                        'question' => 'Can you describe a situation in retail where you needed to quickly learn about a new product group or campaign?',
                        'method' => 'STAR',
                        'scoring_rubric' => [
                            '1' => 'Made no learning effort, gave wrong information to customers',
                            '2' => 'Learned basics but inadequate, lacking in details',
                            '3' => 'Learned necessary information, gave correct information to customers',
                            '4' => 'Learned quickly, provided detailed information to customers, increased sales',
                            '5' => 'Became expert, trained team, received positive feedback from customers',
                        ],
                        'positive_signals' => [
                            'Researched on own initiative',
                            'Took notes',
                            'Asked questions',
                            'Shared knowledge',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_AVOID', 'trigger_guidance' => 'Avoiding learning responsibility: "not my job to know product info"', 'severity' => 'medium'],
                        ],
                    ],
                    [
                        'slot' => 7,
                        'competency' => 'integrity',
                        'question' => 'What did you do when you noticed extra money or product discrepancy at the register? Or what would you do if you faced such a situation?',
                        'method' => 'BEI',
                        'scoring_rubric' => [
                            '1' => 'Implied wouldn\'t report or would benefit from discrepancy',
                            '2' => 'Unsure what to do, no clear ethical stance',
                            '3' => 'Stated would do the right thing but motivation unclear',
                            '4' => 'Clearly explained reporting and correction procedure, consistent ethical approach',
                            '5' => 'Strong ethical stance, supported with examples, showed consistency in other situations',
                        ],
                        'positive_signals' => [
                            'Immediate reporting reflex',
                            'Emphasis on honesty',
                            'Procedure knowledge',
                            'Consistent ethical approach',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_INCONSIST', 'trigger_guidance' => 'Ethical inconsistency: "depends on situation", "if it\'s small amount it doesn\'t matter"', 'severity' => 'high'],
                        ],
                    ],
                    [
                        'slot' => 8,
                        'competency' => 'role_competence',
                        'question' => 'Can you describe your daily register operations? What do you do during opening, transactions, and closing processes?',
                        'method' => 'Direct',
                        'scoring_rubric' => [
                            '1' => 'Doesn\'t know basic register operations, no or very limited experience',
                            '2' => 'Knows some operations but has gaps, needs guidance',
                            '3' => 'Can perform standard operations: cash, credit card, return procedure',
                            '4' => 'Knows all operations, can apply campaigns, troubleshoot',
                            '5' => 'Expert level: complex transactions, can train others, system troubleshooting',
                        ],
                        'positive_signals' => [
                            'Listed transaction steps correctly',
                            'Knew security protocols',
                            'Gave troubleshooting example',
                            'Focused on customer satisfaction',
                        ],
                        'red_flag_hooks' => [
                            ['code' => 'RF_INCONSIST', 'trigger_guidance' => 'Experience exaggeration: wrong answers to basic questions, vagueness when details requested', 'severity' => 'high'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
