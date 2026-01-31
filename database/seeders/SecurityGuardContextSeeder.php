<?php

namespace Database\Seeders;

use App\Models\InterviewQuestion;
use App\Models\JobContext;
use Illuminate\Database\Seeder;

class SecurityGuardContextSeeder extends Seeder
{
    public function run(): void
    {
        // Create contexts with weight multipliers
        $contexts = [
            [
                'role_key' => 'security_guard',
                'context_key' => 'night_shift',
                'label_tr' => 'Gece Vardiyası',
                'label_en' => 'Night Shift',
                'description_tr' => 'Gece saatlerinde tek başına çalışma',
                'description_en' => 'Working alone during night hours',
                'default_weights' => [
                    'clarity' => 1.0,
                    'ownership' => 1.2,
                    'problem' => 1.3,
                    'stress' => 1.5,
                    'consistency' => 1.2,
                ],
                'risk_level' => 'high',
            ],
            [
                'role_key' => 'security_guard',
                'context_key' => 'mall_daytime',
                'label_tr' => 'AVM Gündüz',
                'label_en' => 'Mall Daytime',
                'description_tr' => 'AVM içinde gündüz vardiyası',
                'description_en' => 'Daytime shift in shopping mall',
                'default_weights' => [
                    'clarity' => 1.3,
                    'ownership' => 1.0,
                    'problem' => 1.2,
                    'stress' => 1.0,
                    'consistency' => 1.1,
                ],
                'risk_level' => 'medium',
            ],
            [
                'role_key' => 'security_guard',
                'context_key' => 'stadium_event',
                'label_tr' => 'Stadyum / Etkinlik',
                'label_en' => 'Stadium Event',
                'description_tr' => 'Kalabalık etkinliklerde güvenlik',
                'description_en' => 'Security at crowded events',
                'default_weights' => [
                    'clarity' => 1.2,
                    'ownership' => 1.1,
                    'problem' => 1.4,
                    'stress' => 1.5,
                    'consistency' => 1.3,
                ],
                'risk_level' => 'high',
            ],
        ];

        foreach ($contexts as $ctx) {
            JobContext::updateOrCreate(
                ['role_key' => $ctx['role_key'], 'context_key' => $ctx['context_key']],
                $ctx
            );
        }

        // Shared questions (6)
        $sharedQuestions = [
            ['key' => 'sg_conflict', 'dimension' => 'problem_solving', 'order' => 1,
             'tr' => 'Bir ziyaretçi ile tartışma yaşandığını düşünün. Bu durumu nasıl yönetirsiniz?',
             'en' => 'Imagine a dispute arises with a visitor. How would you handle this situation?'],
            ['key' => 'sg_emergency', 'dimension' => 'stress_tolerance', 'order' => 2,
             'tr' => 'Acil bir durum (yangın alarmı, sağlık sorunu) olduğunda ilk adımlarınız neler olur?',
             'en' => 'What would be your first steps in an emergency (fire alarm, health issue)?'],
            ['key' => 'sg_unauthorized', 'dimension' => 'integrity', 'order' => 3,
             'tr' => 'Yetkisiz bir kişinin güvenlik alanına girmeye çalıştığını fark ettiniz. Ne yaparsınız?',
             'en' => 'You notice an unauthorized person trying to enter a secure area. What do you do?'],
            ['key' => 'sg_teamwork', 'dimension' => 'teamwork', 'order' => 4,
             'tr' => 'Bir güvenlik ekibinde çalışırken ekip arkadaşlarınızla nasıl koordinasyon sağlarsınız?',
             'en' => 'How do you coordinate with your teammates when working in a security team?'],
            ['key' => 'sg_boredom', 'dimension' => 'adaptability', 'order' => 5,
             'tr' => 'Uzun ve sakin bir vardiya sırasında dikkatinizi nasıl korursunuz?',
             'en' => 'How do you maintain your attention during a long and quiet shift?'],
            ['key' => 'sg_report', 'dimension' => 'communication', 'order' => 6,
             'tr' => 'Vardiya sonunda bir olay raporu yazmanız gerekiyor. Bu raporda neler yer almalı?',
             'en' => 'You need to write an incident report at the end of your shift. What should it include?'],
        ];

        // Context-specific questions (2 per context)
        $contextQuestions = [
            'night_shift' => [
                ['key' => 'sg_night_alone', 'dimension' => 'stress_tolerance', 'order' => 7,
                 'tr' => 'Gece vardiyasında tek başınıza çalışırken şüpheli bir ses duydunuz. Nasıl tepki verirsiniz?',
                 'en' => 'You hear a suspicious sound while working alone on night shift. How do you react?'],
                ['key' => 'sg_night_fatigue', 'dimension' => 'adaptability', 'order' => 8,
                 'tr' => 'Gece vardiyasında uyanık ve dikkatli kalmak için neler yaparsınız?',
                 'en' => 'What do you do to stay awake and alert during night shifts?'],
            ],
            'mall_daytime' => [
                ['key' => 'sg_mall_crowd', 'dimension' => 'communication', 'order' => 7,
                 'tr' => 'AVM\'de kayıp bir çocuk ihbarı aldınız. Nasıl bir prosedür izlersiniz?',
                 'en' => 'You receive a report of a lost child in the mall. What procedure do you follow?'],
                ['key' => 'sg_mall_theft', 'dimension' => 'problem_solving', 'order' => 8,
                 'tr' => 'Bir mağazadan hırsızlık şüphesi bildirildi. Bu durumda nasıl hareket edersiniz?',
                 'en' => 'A theft suspicion is reported from a store. How do you act in this situation?'],
            ],
            'stadium_event' => [
                ['key' => 'sg_stadium_crowd', 'dimension' => 'stress_tolerance', 'order' => 7,
                 'tr' => 'Stadyumda iki taraftar grubu arasında gerginlik başladı. Bu durumu nasıl kontrol edersiniz?',
                 'en' => 'Tension begins between two fan groups in the stadium. How do you control this situation?'],
                ['key' => 'sg_stadium_entry', 'dimension' => 'integrity', 'order' => 8,
                 'tr' => 'Giriş kontrolünde yasaklı madde tespit ettiniz. Prosedürünüz nedir?',
                 'en' => 'You detect a prohibited item at entry control. What is your procedure?'],
            ],
        ];

        // Insert shared questions
        foreach (['tr', 'en'] as $locale) {
            foreach ($sharedQuestions as $q) {
                $exists = InterviewQuestion::where('role_key', 'security_guard')
                    ->whereNull('context_key')
                    ->where('locale', $locale)
                    ->whereJsonContains('meta->key', $q['key'])
                    ->exists();

                if (!$exists) {
                    InterviewQuestion::create([
                        'role_key' => 'security_guard',
                        'context_key' => null,
                        'locale' => $locale,
                        'type' => 'text',
                        'prompt' => $q[$locale],
                        'order_no' => $q['order'],
                        'is_active' => true,
                        'meta' => [
                            'key' => $q['key'],
                            'dimension' => $q['dimension'],
                        ],
                    ]);
                }
            }
        }

        // Insert context-specific questions
        foreach ($contextQuestions as $contextKey => $questions) {
            foreach (['tr', 'en'] as $locale) {
                foreach ($questions as $q) {
                    $exists = InterviewQuestion::where('role_key', 'security_guard')
                        ->where('context_key', $contextKey)
                        ->where('locale', $locale)
                        ->whereJsonContains('meta->key', $q['key'])
                        ->exists();

                    if (!$exists) {
                        InterviewQuestion::create([
                            'role_key' => 'security_guard',
                            'context_key' => $contextKey,
                            'locale' => $locale,
                            'type' => 'text',
                            'prompt' => $q[$locale],
                            'order_no' => $q['order'],
                            'is_active' => true,
                            'meta' => [
                                'key' => $q['key'],
                                'dimension' => $q['dimension'],
                            ],
                        ]);
                    }
                }
            }
        }
    }
}
