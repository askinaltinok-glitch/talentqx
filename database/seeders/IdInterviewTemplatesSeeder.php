<?php

namespace Database\Seeders;

use App\Models\InterviewTemplate;
use Illuminate\Database\Seeder;

class IdInterviewTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedGeneric();

        $this->seedDeckGeneric();
        $this->seedDeckCaptain();
        $this->seedDeckChiefOfficer();
        $this->seedDeckSecondOfficer();
        $this->seedDeckThirdOfficer();
        $this->seedDeckBosun();
        $this->seedDeckAbleSeaman();
        $this->seedDeckOrdinarySeaman();

        $this->seedEngineGeneric();
        $this->seedEngineChiefEngineer();
        $this->seedEngineSecondEngineer();
        $this->seedEngineThirdEngineer();
        $this->seedEngineMotorman();
        $this->seedEngineOiler();
        $this->seedEngineElectrician();

        $this->seedGalleyGeneric();
        $this->seedGalleyCook();
        $this->seedGalleySteward();
        $this->seedGalleyMessman();

        $this->seedCadetGeneric();
        $this->seedCadetDeckCadet();
        $this->seedCadetEngineCadet();

        $this->command->info('Indonesian (id) interview templates seeded: 23 templates.');
    }

    /* ================================================================
     *  GENERIC TEMPLATE
     * ================================================================ */

    private function seedGeneric(): void
    {
        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => '__generic__'],
            [
                'title' => 'Generic Interview Template (Indonesian)',
                'template_json' => json_encode([
                    'version' => 'v1',
                    'language' => 'id',
                    'generic_template' => [
                        'questions' => [
                            [
                                'slot' => 1,
                                'competency' => 'communication',
                                'question' => 'Bisakah Anda menjelaskan situasi di mana Anda harus menjelaskan topik yang rumit dengan cara sederhana? Apa yang Anda lakukan dan apa hasilnya?',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'Tidak mampu menjelaskan, tidak ada perspektif pendengar, penjelasan membingungkan dan tanpa arah',
                                    '2' => 'Menyampaikan informasi dasar tetapi tanpa struktur, tidak disesuaikan dengan pendengar',
                                    '3' => 'Penjelasan jelas, struktur dasar ada, terbuka terhadap umpan balik',
                                    '4' => 'Jelas dan terorganisir, disesuaikan dengan tingkat pendengar, siap menjawab pertanyaan',
                                    '5' => 'Struktur sangat baik, penjelasan empatik yang berfokus pada pendengar, umpan balik loop yang efektif',
                                ],
                                'positive_signals' => [
                                    'Menanyakan tingkat pengetahuan pendengar',
                                    'Menggunakan contoh dan perbandingan',
                                    'Memastikan pesan dipahami',
                                    'Menyesuaikan pendekatan berdasarkan umpan balik',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_AVOID',
                                        'trigger_guidance' => 'Menghindari tanggung jawab komunikasi: "itu bukan tugas saya", "biar orang lain saja yang urus"',
                                        'severity' => 'medium',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 2,
                                'competency' => 'accountability',
                                'question' => 'Bisakah Anda menjelaskan situasi di tempat kerja di mana Anda melakukan kesalahan atau sesuatu berjalan tidak sesuai rencana? Bagaimana Anda mengatasinya?',
                                'method' => 'BEI',
                                'scoring_rubric' => [
                                    '1' => 'Menyangkal kesalahan atau menyalahkan orang lain, tidak mengambil tanggung jawab',
                                    '2' => 'Mengakui kesalahan tetapi tidak mengambil tindakan, tetap pasif',
                                    '3' => 'Mengakui kesalahan dan mengambil langkah dasar untuk memperbaiki',
                                    '4' => 'Bertanggung jawab penuh, proaktif mencari solusi, memberi tahu pemangku kepentingan',
                                    '5' => 'Mengakui kesalahan sepenuhnya, membangun solusi sistematis, mengusulkan perbaikan proses',
                                ],
                                'positive_signals' => [
                                    'Mengakui kesalahan dengan jelas',
                                    'Tidak menyalahkan orang lain',
                                    'Menyampaikan langkah perbaikan yang konkret',
                                    'Membagikan pelajaran yang dipetik',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_BLAME',
                                        'trigger_guidance' => 'Selalu menunjuk penyebab eksternal: "tim tidak mendukung saya", "perintah manajer yang salah"',
                                        'severity' => 'high',
                                    ],
                                    [
                                        'code' => 'RF_INCONSIST',
                                        'trigger_guidance' => 'Cerita tidak konsisten: awalnya menyalahkan orang lain lalu mengakui, detail yang saling bertentangan',
                                        'severity' => 'high',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 3,
                                'competency' => 'teamwork',
                                'question' => 'Bisakah Anda menjelaskan proyek di mana Anda bekerja dengan anggota tim yang memiliki sudut pandang berbeda? Bagaimana Anda mengelola perbedaan perspektif?',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'Menghindari kerja tim atau memaksakan pandangan sendiri, tidak mencari konsensus',
                                    '2' => 'Partisipasi pasif, tidak menyampaikan pendapat atau mengabaikan konflik',
                                    '3' => 'Mendengarkan berbagai pandangan, melakukan upaya dasar untuk mencapai kesepakatan',
                                    '4' => 'Aktif mengintegrasikan berbagai perspektif, menciptakan lingkungan diskusi yang konstruktif',
                                    '5' => 'Menciptakan sinergi dari perbedaan, memastikan semua terlibat, mengarahkan menuju tujuan bersama',
                                ],
                                'positive_signals' => [
                                    'Aktif meminta ide dari orang lain',
                                    'Terbuka mengubah pandangan sendiri',
                                    'Mengelola konflik secara konstruktif',
                                    'Mengutamakan keberhasilan tim di atas kepentingan pribadi',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_EGO',
                                        'trigger_guidance' => 'Mengklaim keberhasilan tim: "sebenarnya itu ide saya", "mereka tidak bisa tanpa saya"',
                                        'severity' => 'medium',
                                    ],
                                    [
                                        'code' => 'RF_AGGRESSION',
                                        'trigger_guidance' => 'Ekspresi yang menyinggung terhadap rekan tim: hinaan, serangan pribadi, nada marah',
                                        'severity' => 'critical',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 4,
                                'competency' => 'stress_resilience',
                                'question' => 'Bisakah Anda menjelaskan periode saat Anda bekerja di bawah tekanan tinggi dengan banyak prioritas sekaligus? Bagaimana Anda mengatasinya?',
                                'method' => 'BEI',
                                'scoring_rubric' => [
                                    '1' => 'Menyerah di bawah tekanan, tidak menyelesaikan tugas, panik atau menghindar',
                                    '2' => 'Menyelesaikan dengan kesulitan, tidak ada strategi manajemen stres, pendekatan reaktif',
                                    '3' => 'Menyelesaikan tugas, prioritas dasar, manajemen stres yang memadai',
                                    '4' => 'Prioritas yang efektif, tetap tenang dengan pendekatan sistematis, menjaga kualitas',
                                    '5' => 'Kinerja luar biasa di bawah tekanan, menenangkan orang lain, menggunakan stres sebagai motivasi',
                                ],
                                'positive_signals' => [
                                    'Menyampaikan cara prioritas yang konkret',
                                    'Menunjukkan pengendalian emosi',
                                    'Meminta bantuan saat diperlukan',
                                    'Mengambil pelajaran untuk masa depan',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_UNSTABLE',
                                        'trigger_guidance' => 'Reaksi tidak terkendali terhadap stres: "saya meledak", "saya pergi begitu saja", "saya kehilangan kendali"',
                                        'severity' => 'medium',
                                    ],
                                    [
                                        'code' => 'RF_AVOID',
                                        'trigger_guidance' => 'Secara sistematis menghindari situasi stres: "itu bukan tipe pekerjaan saya", "saya tidak mau tanggung jawab seperti itu"',
                                        'severity' => 'medium',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 5,
                                'competency' => 'adaptability',
                                'question' => 'Bagaimana Anda beradaptasi ketika ada perubahan mendadak di tempat kerja? Bisakah memberikan contoh?',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'Menolak perubahan, tidak beradaptasi, mengeluh atau menghalangi',
                                    '2' => 'Beradaptasi dengan enggan, mempertahankan sikap negatif',
                                    '3' => 'Menerima perubahan, beradaptasi dalam waktu yang wajar',
                                    '4' => 'Cepat menerima perubahan, bekerja efektif dalam situasi baru, membantu orang lain beradaptasi',
                                    '5' => 'Menjadikan perubahan sebagai peluang, memberikan saran proaktif, memimpin perubahan',
                                ],
                                'positive_signals' => [
                                    'Berusaha memahami alasan perubahan',
                                    'Cepat mempelajari keterampilan baru',
                                    'Mempertahankan sikap positif',
                                    'Membantu orang lain beradaptasi',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_AVOID',
                                        'trigger_guidance' => 'Melarikan diri dan menolak perubahan: "saya tidak mau melakukan itu", "bukan tugas saya untuk belajar sistem baru"',
                                        'severity' => 'medium',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 6,
                                'competency' => 'learning_agility',
                                'question' => 'Bisakah Anda menjelaskan situasi di mana Anda perlu mempelajari topik atau keterampilan baru dengan cepat? Bagaimana pendekatan Anda?',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'Tidak mau belajar, sikap pasif, bergantung pada orang lain',
                                    '2' => 'Belajar di tingkat dasar tetapi tidak mengembangkan, hanya melakukan yang diperlukan',
                                    '3' => 'Upaya aktif dalam belajar, menggunakan sumber daya umum, belajar dalam waktu yang wajar',
                                    '4' => 'Belajar cepat dan efektif, menggunakan berbagai sumber daya, segera menerapkan dalam praktik',
                                    '5' => 'Kecepatan belajar luar biasa, menyempurnakan yang dipelajari, mengajarkan kepada orang lain',
                                ],
                                'positive_signals' => [
                                    'Menggunakan berbagai sumber belajar',
                                    'Tidak takut bertanya',
                                    'Menerapkan yang dipelajari dalam praktik',
                                    'Menunjukkan antusiasme dalam belajar',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_AVOID',
                                        'trigger_guidance' => 'Menghindari tanggung jawab belajar: "bukan tugas saya untuk belajar hal baru", "biar orang lain saja yang mengajari saya"',
                                        'severity' => 'medium',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 7,
                                'competency' => 'integrity',
                                'question' => 'Bisakah Anda menjelaskan situasi di mana Anda menghadapi keputusan etis yang sulit? Bagaimana Anda bertindak?',
                                'method' => 'BEI',
                                'scoring_rubric' => [
                                    '1' => 'Menunjukkan perilaku tidak etis atau menormalisasi pelanggaran aturan',
                                    '2' => 'Mengenali dilema etis tetapi tidak bertindak, tetap pasif',
                                    '3' => 'Melakukan hal yang benar tetapi hanya karena diwajibkan, motivasi internal tidak jelas',
                                    '4' => 'Berpegang teguh pada prinsip etika, membuat keputusan yang benar meskipun dalam situasi sulit, perilaku konsisten',
                                    '5' => 'Menunjukkan kepemimpinan etis, membimbing orang lain dalam perilaku yang benar, mengambil risiko untuk membela kebenaran',
                                ],
                                'positive_signals' => [
                                    'Menyampaikan kerangka etika yang jelas dan konsisten',
                                    'Melakukan hal yang benar meskipun ada biaya pribadi',
                                    'Menekankan transparansi dan kejujuran',
                                    'Melawan tekanan yang tidak etis',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_INCONSIST',
                                        'trigger_guidance' => 'Tidak konsisten dalam etika: aturan yang berubah tergantung situasi, normalisasi "semua orang juga melakukan itu"',
                                        'severity' => 'high',
                                    ],
                                    [
                                        'code' => 'RF_BLAME',
                                        'trigger_guidance' => 'Menyalahkan orang lain atas pelanggaran etika: "manajer yang memaksa saya", "memang sistemnya begitu"',
                                        'severity' => 'high',
                                    ],
                                ],
                            ],
                            [
                                'slot' => 8,
                                'competency' => 'role_competence',
                                'question' => 'Bisakah Anda menjelaskan pengalaman di mana Anda melakukan salah satu persyaratan inti dari posisi ini? Pendekatan apa yang Anda gunakan dan apa hasilnya?',
                                'method' => 'STAR',
                                'scoring_rubric' => [
                                    '1' => 'Tidak ada pengalaman yang relevan atau sangat dangkal, menunjukkan kurangnya pemahaman terhadap persyaratan inti',
                                    '2' => 'Pengalaman terbatas, memahami konsep dasar tetapi lemah dalam penerapan',
                                    '3' => 'Pengalaman memadai, penggunaan proses umum yang benar, hasil yang dapat diterima',
                                    '4' => 'Pengalaman yang kuat, hasil berkualitas dan terukur, memperbaiki proses',
                                    '5' => 'Kinerja luar biasa, membangun pendekatan inovatif, mampu mengajarkan kepada orang lain',
                                ],
                                'positive_signals' => [
                                    'Membagikan hasil yang konkret dan terukur',
                                    'Menjelaskan langkah-langkah proses dengan benar dan logis',
                                    'Menjelaskan bagaimana masalah diselesaikan',
                                    'Memberikan contoh perbaikan berkelanjutan',
                                ],
                                'red_flag_hooks' => [
                                    [
                                        'code' => 'RF_INCONSIST',
                                        'trigger_guidance' => 'Melebih-lebihkan kemampuan: tidak konsisten saat diminta detail, jawaban kabur saat diminta penjelasan',
                                        'severity' => 'high',
                                    ],
                                    [
                                        'code' => 'RF_EGO',
                                        'trigger_guidance' => 'Kepercayaan diri yang tidak realistis: "saya yang paling ahli dalam pekerjaan ini", "tidak ada yang sehebat saya"',
                                        'severity' => 'medium',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'positions' => [],
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] __generic__');
    }

    /* ================================================================
     *  DECK DEPARTMENT
     * ================================================================ */

    private function deckQuestions(): array
    {
        return [
            'screening' => [
                ['id' => 'deck_id_s1', 'type' => 'open', 'text' => 'Jenis kapal apa yang pernah Anda kerjakan? tonase/bendera/rute/durasi.'],
                ['id' => 'deck_id_s2', 'type' => 'open', 'text' => 'Jelaskan tugas dan sistem jaga Anda.'],
                ['id' => 'deck_id_s3', 'type' => 'scale', 'text' => 'Nilai Bridge English (SMCP) 1–5.', 'scale' => ['min' => 1, 'max' => 5]],
            ],
            'technical' => [
                ['id' => 'deck_id_t1', 'type' => 'open', 'text' => 'COLREG crossing: logika keputusan + skenario.'],
                ['id' => 'deck_id_t2', 'type' => 'open', 'text' => 'Apa yang Anda serahkan saat pergantian jaga?'],
                ['id' => 'deck_id_t3', 'type' => 'open', 'text' => 'Pengaturan keselamatan ECDIS apa yang Anda verifikasi?'],
                ['id' => 'deck_id_t4', 'type' => 'open', 'text' => '3 risiko mooring teratas dan pengendaliannya?'],
            ],
            'safety' => [
                ['id' => 'deck_id_sa1', 'type' => 'open', 'text' => 'MOB: tindakan 60 detik pertama?'],
                ['id' => 'deck_id_sa2', 'type' => 'open', 'text' => 'Alarm kebakaran: peran tim anjungan/dek?'],
                ['id' => 'deck_id_sa3', 'type' => 'open', 'text' => 'Di mana PTW wajib? 3 contoh.'],
            ],
            'behaviour' => [
                ['id' => 'deck_id_b1', 'type' => 'open', 'text' => 'Bagaimana Anda mengeskalasi masalah keselamatan kepada atasan?'],
                ['id' => 'deck_id_b2', 'type' => 'open', 'text' => 'Bagaimana Anda mengelola kelelahan dalam praktik?'],
            ],
        ];
    }

    private function deckSections(): array
    {
        $q = $this->deckQuestions();
        return [
            ['key' => 'screening',  'title' => 'Penyaringan',              'questions' => $q['screening']],
            ['key' => 'technical',  'title' => 'Operasional / Teknis',     'questions' => $q['technical']],
            ['key' => 'safety',     'title' => 'Keselamatan / Darurat',    'questions' => $q['safety']],
            ['key' => 'behaviour',  'title' => 'Perilaku / Disiplin',      'questions' => $q['behaviour']],
        ];
    }

    private function deckScoring(): array
    {
        return ['weights' => ['screening' => 0.2, 'technical' => 0.4, 'safety' => 0.3, 'behaviour' => 0.1]];
    }

    private function seedDeckGeneric(): void
    {
        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'deck___generic__'],
            [
                'title' => 'Deck Department Generic Template (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Deck / Generic',
                    'department' => 'deck',
                    'language' => 'id',
                    'role_scope' => '__generic__',
                    'sections' => $this->deckSections(),
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] deck___generic__');
    }

    private function seedDeckCaptain(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_captain_id_s1', 'type' => 'open', 'text' => 'Sebagai Kapten, apa tanggung jawab penting harian Anda? Berikan contoh nyata dari kapal terakhir.'];

        $sections[1]['questions'][] = ['id' => 'rs_captain_id_t1', 'type' => 'open', 'text' => 'Dalam peran Kapten, apa 3 risiko operasional teratas yang paling sering Anda temui dan langkah pengendaliannya?'];
        $sections[1]['questions'][] = ['id' => 'rs_captain_id_t2', 'type' => 'open', 'text' => 'Jelaskan skenario COLREG yang sulit (crossing/visibility). Data apa yang mendorong keputusan Anda?'];
        $sections[1]['questions'][] = ['id' => 'rs_captain_id_t3', 'type' => 'open', 'text' => 'Dalam perencanaan pelayaran, bagaimana Anda mengelola no-go areas, UKC, dan weather windows?'];

        $sections[2]['questions'][] = ['id' => 'rs_captain_id_sa2', 'type' => 'open', 'text' => 'Dalam keadaan darurat (kebakaran/MOB/blackout), apa 5 perintah pertama Anda sebagai Kapten dan mengapa?'];

        $sections[3]['questions'][] = ['id' => 'rs_captain_id_b1', 'type' => 'open', 'text' => 'Sebagai Kapten, ceritakan saat Anda turun tangan ketika melihat kesalahan atau risiko dalam tim.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'deck_captain'],
            [
                'title' => 'Maritime / Role / Kapten (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Kapten',
                    'department' => 'deck',
                    'language' => 'id',
                    'role_scope' => 'captain',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] deck_captain');
    }

    private function seedDeckChiefOfficer(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_chief_officer_id_s1', 'type' => 'open', 'text' => 'Sebagai Chief Officer, apa tanggung jawab penting harian Anda? Berikan contoh nyata dari kapal terakhir.'];
        $sections[1]['questions'][] = ['id' => 'rs_chief_officer_id_t1', 'type' => 'open', 'text' => 'Dalam peran Chief Officer, apa 3 risiko operasional teratas yang paling sering Anda temui dan langkah pengendaliannya?'];
        $sections[3]['questions'][] = ['id' => 'rs_chief_officer_id_b1', 'type' => 'open', 'text' => 'Sebagai Chief Officer, ceritakan saat Anda turun tangan ketika melihat kesalahan atau risiko dalam tim.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'deck_chief_officer'],
            [
                'title' => 'Maritime / Role / Chief Officer (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Chief Officer',
                    'department' => 'deck',
                    'language' => 'id',
                    'role_scope' => 'chief_officer',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] deck_chief_officer');
    }

    private function seedDeckSecondOfficer(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_second_officer_id_s1', 'type' => 'open', 'text' => 'Sebagai Perwira Dua, apa tanggung jawab penting harian Anda? Berikan contoh nyata dari kapal terakhir.'];
        $sections[1]['questions'][] = ['id' => 'rs_second_officer_id_t1', 'type' => 'open', 'text' => 'Dalam peran Perwira Dua, apa 3 risiko operasional teratas yang paling sering Anda temui dan langkah pengendaliannya?'];
        $sections[3]['questions'][] = ['id' => 'rs_second_officer_id_b1', 'type' => 'open', 'text' => 'Sebagai Perwira Dua, ceritakan saat Anda turun tangan ketika melihat kesalahan atau risiko dalam tim.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'deck_second_officer'],
            [
                'title' => 'Maritime / Role / Perwira Dua (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Perwira Dua',
                    'department' => 'deck',
                    'language' => 'id',
                    'role_scope' => 'second_officer',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] deck_second_officer');
    }

    private function seedDeckThirdOfficer(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_third_officer_id_s1', 'type' => 'open', 'text' => 'Sebagai Perwira Tiga, apa tanggung jawab penting harian Anda? Berikan contoh nyata dari kapal terakhir.'];
        $sections[1]['questions'][] = ['id' => 'rs_third_officer_id_t1', 'type' => 'open', 'text' => 'Dalam peran Perwira Tiga, apa 3 risiko operasional teratas yang paling sering Anda temui dan langkah pengendaliannya?'];
        $sections[3]['questions'][] = ['id' => 'rs_third_officer_id_b1', 'type' => 'open', 'text' => 'Sebagai Perwira Tiga, ceritakan saat Anda turun tangan ketika melihat kesalahan atau risiko dalam tim.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'deck_third_officer'],
            [
                'title' => 'Maritime / Role / Perwira Tiga (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Perwira Tiga',
                    'department' => 'deck',
                    'language' => 'id',
                    'role_scope' => 'third_officer',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] deck_third_officer');
    }

    private function seedDeckBosun(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_bosun_id_s1', 'type' => 'open', 'text' => 'Sebagai Bosun, apa tanggung jawab penting harian Anda? Berikan contoh nyata dari kapal terakhir.'];
        $sections[1]['questions'][] = ['id' => 'rs_bosun_id_t1', 'type' => 'open', 'text' => 'Dalam peran Bosun, apa 3 risiko operasional teratas yang paling sering Anda temui dan langkah pengendaliannya?'];
        $sections[3]['questions'][] = ['id' => 'rs_bosun_id_b1', 'type' => 'open', 'text' => 'Sebagai Bosun, ceritakan saat Anda turun tangan ketika melihat kesalahan atau risiko dalam tim.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'deck_bosun'],
            [
                'title' => 'Maritime / Role / Bosun (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Bosun',
                    'department' => 'deck',
                    'language' => 'id',
                    'role_scope' => 'bosun',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] deck_bosun');
    }

    private function seedDeckAbleSeaman(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_able_seaman_id_s1', 'type' => 'open', 'text' => 'Sebagai AB Seaman, apa tanggung jawab penting harian Anda? Berikan contoh nyata dari kapal terakhir.'];
        $sections[1]['questions'][] = ['id' => 'rs_able_seaman_id_t1', 'type' => 'open', 'text' => 'Dalam peran AB Seaman, apa 3 risiko operasional teratas yang paling sering Anda temui dan langkah pengendaliannya?'];
        $sections[3]['questions'][] = ['id' => 'rs_able_seaman_id_b1', 'type' => 'open', 'text' => 'Sebagai AB Seaman, ceritakan saat Anda turun tangan ketika melihat kesalahan atau risiko dalam tim.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'deck_able_seaman'],
            [
                'title' => 'Maritime / Role / AB Seaman (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / AB Seaman',
                    'department' => 'deck',
                    'language' => 'id',
                    'role_scope' => 'able_seaman',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] deck_able_seaman');
    }

    private function seedDeckOrdinarySeaman(): void
    {
        $sections = $this->deckSections();

        $sections[0]['questions'][] = ['id' => 'rs_ordinary_seaman_id_s1', 'type' => 'open', 'text' => 'Sebagai OS, apa tanggung jawab penting harian Anda? Berikan contoh nyata dari kapal terakhir.'];
        $sections[1]['questions'][] = ['id' => 'rs_ordinary_seaman_id_t1', 'type' => 'open', 'text' => 'Dalam peran OS, apa 3 risiko operasional teratas yang paling sering Anda temui dan langkah pengendaliannya?'];
        $sections[3]['questions'][] = ['id' => 'rs_ordinary_seaman_id_b1', 'type' => 'open', 'text' => 'Sebagai OS, ceritakan saat Anda turun tangan ketika melihat kesalahan atau risiko dalam tim.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'deck_ordinary_seaman'],
            [
                'title' => 'Maritime / Role / OS (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / OS',
                    'department' => 'deck',
                    'language' => 'id',
                    'role_scope' => 'ordinary_seaman',
                    'sections' => $sections,
                    'scoring' => $this->deckScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] deck_ordinary_seaman');
    }

    /* ================================================================
     *  ENGINE DEPARTMENT
     * ================================================================ */

    private function engineQuestions(): array
    {
        return [
            'screening' => [
                ['id' => 'eng_id_s1', 'type' => 'open', 'text' => 'Mesin/sistem bahan bakar apa yang pernah Anda kerjakan?'],
                ['id' => 'eng_id_s2', 'type' => 'open', 'text' => 'Pernah menggunakan PMS? Jelaskan satu pekerjaan dari awal sampai akhir.'],
                ['id' => 'eng_id_s3', 'type' => 'scale', 'text' => 'Nilai disiplin pelaporan kamar mesin 1–5.', 'scale' => ['min' => 1, 'max' => 5]],
            ],
            'technical' => [
                ['id' => 'eng_id_t1', 'type' => 'open', 'text' => 'Tekanan LO turun: urutan troubleshooting yang aman?'],
                ['id' => 'eng_id_t2', 'type' => 'open', 'text' => 'Suhu jacket water tinggi: 3 penyebab + pemeriksaan?'],
                ['id' => 'eng_id_t3', 'type' => 'open', 'text' => 'Alarm/getaran purifier: diagnosa + shutdown aman?'],
                ['id' => 'eng_id_t4', 'type' => 'open', 'text' => 'Hal yang tidak bisa ditawar dalam isolasi listrik/LOTO?'],
            ],
            'safety' => [
                ['id' => 'eng_id_sa1', 'type' => 'open', 'text' => 'Kebocoran/kebakaran FO: prioritas pertama?'],
                ['id' => 'eng_id_sa2', 'type' => 'open', 'text' => 'Blackout: tindakan 2 menit pertama?'],
                ['id' => 'eng_id_sa3', 'type' => 'open', 'text' => 'Checklist masuk ruang tertutup?'],
            ],
            'behaviour' => [
                ['id' => 'eng_id_b1', 'type' => 'open', 'text' => 'Ditekan untuk membypass keselamatan: apa yang Anda lakukan?'],
                ['id' => 'eng_id_b2', 'type' => 'open', 'text' => 'Bagaimana Anda membimbing junior motorman?'],
            ],
        ];
    }

    private function engineSections(): array
    {
        $q = $this->engineQuestions();
        return [
            ['key' => 'screening',  'title' => 'Penyaringan',               'questions' => $q['screening']],
            ['key' => 'technical',  'title' => 'Teknis / Permesinan',       'questions' => $q['technical']],
            ['key' => 'safety',     'title' => 'Keselamatan / Darurat',     'questions' => $q['safety']],
            ['key' => 'behaviour',  'title' => 'Perilaku / Disiplin',       'questions' => $q['behaviour']],
        ];
    }

    private function engineScoring(): array
    {
        return ['weights' => ['screening' => 0.2, 'technical' => 0.45, 'safety' => 0.25, 'behaviour' => 0.1]];
    }

    private function seedEngineGeneric(): void
    {
        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'engine___generic__'],
            [
                'title' => 'Engine Department Generic Template (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Engine / Generic',
                    'department' => 'engine',
                    'language' => 'id',
                    'role_scope' => '__generic__',
                    'sections' => $this->engineSections(),
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] engine___generic__');
    }

    private function seedEngineChiefEngineer(): void
    {
        $sections = $this->engineSections();

        $sections[0]['questions'][] = ['id' => 'rs_chief_engineer_id_s1', 'type' => 'open', 'text' => 'Sebagai Chief Engineer, apa tanggung jawab penting harian Anda? Berikan contoh nyata dari kapal terakhir.'];

        $sections[1]['questions'][] = ['id' => 'rs_chief_engineer_id_t1', 'type' => 'open', 'text' => 'Dalam peran Chief Engineer, apa 3 risiko operasional teratas yang paling sering Anda temui dan langkah pengendaliannya?'];
        $sections[1]['questions'][] = ['id' => 'rs_ce_id_t2', 'type' => 'open', 'text' => 'Setelah blackout, apa urutan pemulihan Anda? Sistem mana yang hidup duluan dan mengapa?'];
        $sections[1]['questions'][] = ['id' => 'rs_ce_id_t3', 'type' => 'open', 'text' => 'Jika PMS tertunda, bagaimana Anda memulihkan? Bagaimana Anda memprioritaskan dan memimpin tim?'];

        $sections[2]['questions'][] = ['id' => 'rs_ce_id_sa2', 'type' => 'open', 'text' => 'Jika Anda melihat pelanggaran LOTO/PTW, apa yang Anda lakukan? Bagaimana Anda menegakkan stop-work authority?'];

        $sections[3]['questions'][] = ['id' => 'rs_chief_engineer_id_b1', 'type' => 'open', 'text' => 'Sebagai Chief Engineer, ceritakan saat Anda turun tangan ketika melihat kesalahan atau risiko dalam tim.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'engine_chief_engineer'],
            [
                'title' => 'Maritime / Role / Chief Engineer (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Chief Engineer',
                    'department' => 'engine',
                    'language' => 'id',
                    'role_scope' => 'chief_engineer',
                    'sections' => $sections,
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] engine_chief_engineer');
    }

    private function seedEngineSecondEngineer(): void
    {
        $sections = $this->engineSections();

        $sections[0]['questions'][] = ['id' => 'rs_second_engineer_id_s1', 'type' => 'open', 'text' => 'Sebagai Masinis Dua, apa tanggung jawab penting harian Anda? Berikan contoh nyata dari kapal terakhir.'];
        $sections[1]['questions'][] = ['id' => 'rs_second_engineer_id_t1', 'type' => 'open', 'text' => 'Dalam peran Masinis Dua, apa 3 risiko operasional teratas yang paling sering Anda temui dan langkah pengendaliannya?'];
        $sections[3]['questions'][] = ['id' => 'rs_second_engineer_id_b1', 'type' => 'open', 'text' => 'Sebagai Masinis Dua, ceritakan saat Anda turun tangan ketika melihat kesalahan atau risiko dalam tim.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'engine_second_engineer'],
            [
                'title' => 'Maritime / Role / Masinis Dua (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Masinis Dua',
                    'department' => 'engine',
                    'language' => 'id',
                    'role_scope' => 'second_engineer',
                    'sections' => $sections,
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] engine_second_engineer');
    }

    private function seedEngineThirdEngineer(): void
    {
        $sections = $this->engineSections();

        $sections[0]['questions'][] = ['id' => 'rs_third_engineer_id_s1', 'type' => 'open', 'text' => 'Sebagai Masinis Tiga, apa tanggung jawab penting harian Anda? Berikan contoh nyata dari kapal terakhir.'];
        $sections[1]['questions'][] = ['id' => 'rs_third_engineer_id_t1', 'type' => 'open', 'text' => 'Dalam peran Masinis Tiga, apa 3 risiko operasional teratas yang paling sering Anda temui dan langkah pengendaliannya?'];
        $sections[3]['questions'][] = ['id' => 'rs_third_engineer_id_b1', 'type' => 'open', 'text' => 'Sebagai Masinis Tiga, ceritakan saat Anda turun tangan ketika melihat kesalahan atau risiko dalam tim.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'engine_third_engineer'],
            [
                'title' => 'Maritime / Role / Masinis Tiga (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Masinis Tiga',
                    'department' => 'engine',
                    'language' => 'id',
                    'role_scope' => 'third_engineer',
                    'sections' => $sections,
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] engine_third_engineer');
    }

    private function seedEngineMotorman(): void
    {
        $sections = $this->engineSections();

        $sections[0]['questions'][] = ['id' => 'rs_motorman_id_s1', 'type' => 'open', 'text' => 'Sebagai Motorman, apa tanggung jawab penting harian Anda? Berikan contoh nyata dari kapal terakhir.'];
        $sections[1]['questions'][] = ['id' => 'rs_motorman_id_t1', 'type' => 'open', 'text' => 'Dalam peran Motorman, apa 3 risiko operasional teratas yang paling sering Anda temui dan langkah pengendaliannya?'];
        $sections[3]['questions'][] = ['id' => 'rs_motorman_id_b1', 'type' => 'open', 'text' => 'Sebagai Motorman, ceritakan saat Anda turun tangan ketika melihat kesalahan atau risiko dalam tim.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'engine_motorman'],
            [
                'title' => 'Maritime / Role / Motorman (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Motorman',
                    'department' => 'engine',
                    'language' => 'id',
                    'role_scope' => 'motorman',
                    'sections' => $sections,
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] engine_motorman');
    }

    private function seedEngineOiler(): void
    {
        $sections = $this->engineSections();

        $sections[0]['questions'][] = ['id' => 'rs_oiler_id_s1', 'type' => 'open', 'text' => 'Sebagai Oiler, apa tanggung jawab penting harian Anda? Berikan contoh nyata dari kapal terakhir.'];
        $sections[1]['questions'][] = ['id' => 'rs_oiler_id_t1', 'type' => 'open', 'text' => 'Dalam peran Oiler, apa 3 risiko operasional teratas yang paling sering Anda temui dan langkah pengendaliannya?'];
        $sections[3]['questions'][] = ['id' => 'rs_oiler_id_b1', 'type' => 'open', 'text' => 'Sebagai Oiler, ceritakan saat Anda turun tangan ketika melihat kesalahan atau risiko dalam tim.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'engine_oiler'],
            [
                'title' => 'Maritime / Role / Oiler (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Oiler',
                    'department' => 'engine',
                    'language' => 'id',
                    'role_scope' => 'oiler',
                    'sections' => $sections,
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] engine_oiler');
    }

    private function seedEngineElectrician(): void
    {
        $sections = $this->engineSections();

        $sections[0]['questions'][] = ['id' => 'rs_electrician_id_s1', 'type' => 'open', 'text' => 'Sebagai Electrician, apa tanggung jawab penting harian Anda? Berikan contoh nyata dari kapal terakhir.'];
        $sections[1]['questions'][] = ['id' => 'rs_electrician_id_t1', 'type' => 'open', 'text' => 'Dalam peran Electrician, apa 3 risiko operasional teratas yang paling sering Anda temui dan langkah pengendaliannya?'];
        $sections[3]['questions'][] = ['id' => 'rs_electrician_id_b1', 'type' => 'open', 'text' => 'Sebagai Electrician, ceritakan saat Anda turun tangan ketika melihat kesalahan atau risiko dalam tim.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'engine_electrician'],
            [
                'title' => 'Maritime / Role / Electrician (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Electrician',
                    'department' => 'engine',
                    'language' => 'id',
                    'role_scope' => 'electrician',
                    'sections' => $sections,
                    'scoring' => $this->engineScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] engine_electrician');
    }

    /* ================================================================
     *  GALLEY DEPARTMENT
     * ================================================================ */

    private function galleyQuestions(): array
    {
        return [
            'screening' => [
                ['id' => 'gal_id_s1', 'type' => 'open', 'text' => 'Peran Anda di kapal dan berapa kru yang dilayani?'],
                ['id' => 'gal_id_s2', 'type' => 'open', 'text' => 'Bagaimana Anda menerapkan HACCP/log suhu/pengendalian kontaminasi silang?'],
                ['id' => 'gal_id_s3', 'type' => 'open', 'text' => 'Perencanaan menu dengan stok terbatas dalam pelayaran panjang?'],
            ],
            'technical' => [
                ['id' => 'gal_id_t1', 'type' => 'open', 'text' => 'Pengendalian suhu cold chain dan hot holding?'],
                ['id' => 'gal_id_t2', 'type' => 'open', 'text' => 'Manajemen alergen dan pendekatan pelabelan?'],
                ['id' => 'gal_id_t3', 'type' => 'open', 'text' => 'Tindakan pertama jika dicurigai keracunan makanan?'],
            ],
            'safety' => [
                ['id' => 'gal_id_sa1', 'type' => 'open', 'text' => 'Respons yang benar terhadap kebakaran minyak?'],
                ['id' => 'gal_id_sa2', 'type' => 'open', 'text' => 'Prosedur dan pelaporan untuk luka/cedera?'],
            ],
            'behaviour' => [
                ['id' => 'gal_id_b1', 'type' => 'open', 'text' => 'Mengelola konflik dalam kru multikultural?'],
                ['id' => 'gal_id_b2', 'type' => 'open', 'text' => 'Menjaga kualitas saat puncak port-call?'],
            ],
        ];
    }

    private function galleySections(): array
    {
        $q = $this->galleyQuestions();
        return [
            ['key' => 'screening',  'title' => 'Penyaringan',               'questions' => $q['screening']],
            ['key' => 'technical',  'title' => 'Teknis / Tata Boga',        'questions' => $q['technical']],
            ['key' => 'safety',     'title' => 'Keselamatan / Darurat',     'questions' => $q['safety']],
            ['key' => 'behaviour',  'title' => 'Perilaku / Disiplin',       'questions' => $q['behaviour']],
        ];
    }

    private function galleyScoring(): array
    {
        return ['weights' => ['screening' => 0.25, 'technical' => 0.35, 'safety' => 0.25, 'behaviour' => 0.15]];
    }

    private function seedGalleyGeneric(): void
    {
        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'galley___generic__'],
            [
                'title' => 'Galley Department Generic Template (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Galley / Generic',
                    'department' => 'galley',
                    'language' => 'id',
                    'role_scope' => '__generic__',
                    'sections' => $this->galleySections(),
                    'scoring' => $this->galleyScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] galley___generic__');
    }

    private function seedGalleyCook(): void
    {
        $sections = $this->galleySections();

        $sections[0]['questions'][] = ['id' => 'rs_cook_id_s1', 'type' => 'open', 'text' => 'Sebagai Koki, apa tanggung jawab penting harian Anda? Berikan contoh nyata dari kapal terakhir.'];
        $sections[1]['questions'][] = ['id' => 'rs_cook_id_t1', 'type' => 'open', 'text' => 'Dalam peran Koki, apa 3 risiko operasional teratas yang paling sering Anda temui dan langkah pengendaliannya?'];
        $sections[3]['questions'][] = ['id' => 'rs_cook_id_b1', 'type' => 'open', 'text' => 'Sebagai Koki, ceritakan saat Anda turun tangan ketika melihat kesalahan atau risiko dalam tim.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'galley_cook'],
            [
                'title' => 'Maritime / Role / Koki (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Koki',
                    'department' => 'galley',
                    'language' => 'id',
                    'role_scope' => 'cook',
                    'sections' => $sections,
                    'scoring' => $this->galleyScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] galley_cook');
    }

    private function seedGalleySteward(): void
    {
        $sections = $this->galleySections();

        $sections[0]['questions'][] = ['id' => 'rs_steward_id_s1', 'type' => 'open', 'text' => 'Sebagai Steward, apa tanggung jawab penting harian Anda? Berikan contoh nyata dari kapal terakhir.'];
        $sections[1]['questions'][] = ['id' => 'rs_steward_id_t1', 'type' => 'open', 'text' => 'Dalam peran Steward, apa 3 risiko operasional teratas yang paling sering Anda temui dan langkah pengendaliannya?'];
        $sections[3]['questions'][] = ['id' => 'rs_steward_id_b1', 'type' => 'open', 'text' => 'Sebagai Steward, ceritakan saat Anda turun tangan ketika melihat kesalahan atau risiko dalam tim.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'galley_steward'],
            [
                'title' => 'Maritime / Role / Steward (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Steward',
                    'department' => 'galley',
                    'language' => 'id',
                    'role_scope' => 'steward',
                    'sections' => $sections,
                    'scoring' => $this->galleyScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] galley_steward');
    }

    private function seedGalleyMessman(): void
    {
        $sections = $this->galleySections();

        $sections[0]['questions'][] = ['id' => 'rs_messman_id_s1', 'type' => 'open', 'text' => 'Sebagai Messman, apa tanggung jawab penting harian Anda? Berikan contoh nyata dari kapal terakhir.'];
        $sections[1]['questions'][] = ['id' => 'rs_messman_id_t1', 'type' => 'open', 'text' => 'Dalam peran Messman, apa 3 risiko operasional teratas yang paling sering Anda temui dan langkah pengendaliannya?'];
        $sections[3]['questions'][] = ['id' => 'rs_messman_id_b1', 'type' => 'open', 'text' => 'Sebagai Messman, ceritakan saat Anda turun tangan ketika melihat kesalahan atau risiko dalam tim.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'galley_messman'],
            [
                'title' => 'Maritime / Role / Messman (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Messman',
                    'department' => 'galley',
                    'language' => 'id',
                    'role_scope' => 'messman',
                    'sections' => $sections,
                    'scoring' => $this->galleyScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] galley_messman');
    }

    /* ================================================================
     *  CADET DEPARTMENT
     * ================================================================ */

    private function cadetQuestions(): array
    {
        return [
            'screening' => [
                ['id' => 'cad_id_s1', 'type' => 'open', 'text' => 'Sekolah/program apa? Target sea-time?'],
                ['id' => 'cad_id_s2', 'type' => 'open', 'text' => 'Pembelajaran/harapan dari pelatihan laut?'],
                ['id' => 'cad_id_s3', 'type' => 'scale', 'text' => 'Nilai disiplin rutinitas harian 1–5.', 'scale' => ['min' => 1, 'max' => 5]],
            ],
            'technical' => [
                ['id' => 'cad_id_t1', 'type' => 'open', 'text' => 'Jelaskan hierarki kapal dan jalur pelaporan.'],
                ['id' => 'cad_id_t2', 'type' => 'open', 'text' => 'Tanggung jawab dasar watchkeeping?'],
                ['id' => 'cad_id_t3', 'type' => 'open', 'text' => 'Mengapa PPE dan toolbox talks penting?'],
            ],
            'safety' => [
                ['id' => 'cad_id_sa1', 'type' => 'open', 'text' => 'Bahaya ruang tertutup dan mengapa tidak boleh masuk sendiri?'],
                ['id' => 'cad_id_sa2', 'type' => 'open', 'text' => 'Peran Anda saat muster/penghitungan saat alarm?'],
            ],
            'behaviour' => [
                ['id' => 'cad_id_b1', 'type' => 'open', 'text' => 'Bagaimana Anda menerima umpan balik setelah kesalahan?'],
                ['id' => 'cad_id_b2', 'type' => 'open', 'text' => 'Jika komunikasi sulit dalam kru multinasional, apa yang Anda lakukan?'],
            ],
        ];
    }

    private function cadetSections(): array
    {
        $q = $this->cadetQuestions();
        return [
            ['key' => 'screening',  'title' => 'Penyaringan',               'questions' => $q['screening']],
            ['key' => 'technical',  'title' => 'Teknis / Pengetahuan',      'questions' => $q['technical']],
            ['key' => 'safety',     'title' => 'Keselamatan / Darurat',     'questions' => $q['safety']],
            ['key' => 'behaviour',  'title' => 'Perilaku / Disiplin',       'questions' => $q['behaviour']],
        ];
    }

    private function cadetScoring(): array
    {
        return ['weights' => ['screening' => 0.3, 'technical' => 0.3, 'safety' => 0.25, 'behaviour' => 0.15]];
    }

    private function seedCadetGeneric(): void
    {
        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'cadet___generic__'],
            [
                'title' => 'Cadet Department Generic Template (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Cadet / Generic',
                    'department' => 'cadet',
                    'language' => 'id',
                    'role_scope' => '__generic__',
                    'sections' => $this->cadetSections(),
                    'scoring' => $this->cadetScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] cadet___generic__');
    }

    private function seedCadetDeckCadet(): void
    {
        $sections = $this->cadetSections();

        $sections[0]['questions'][] = ['id' => 'rs_deck_cadet_id_s1', 'type' => 'open', 'text' => 'Sebagai Deck Cadet, apa tanggung jawab penting harian Anda? Berikan contoh nyata dari kapal terakhir.'];
        $sections[1]['questions'][] = ['id' => 'rs_deck_cadet_id_t1', 'type' => 'open', 'text' => 'Dalam peran Deck Cadet, apa 3 risiko operasional teratas yang paling sering Anda temui dan langkah pengendaliannya?'];
        $sections[3]['questions'][] = ['id' => 'rs_deck_cadet_id_b1', 'type' => 'open', 'text' => 'Sebagai Deck Cadet, ceritakan saat Anda turun tangan ketika melihat kesalahan atau risiko dalam tim.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'cadet_deck_cadet'],
            [
                'title' => 'Maritime / Role / Deck Cadet (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Deck Cadet',
                    'department' => 'cadet',
                    'language' => 'id',
                    'role_scope' => 'deck_cadet',
                    'sections' => $sections,
                    'scoring' => $this->cadetScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] cadet_deck_cadet');
    }

    private function seedCadetEngineCadet(): void
    {
        $sections = $this->cadetSections();

        $sections[0]['questions'][] = ['id' => 'rs_engine_cadet_id_s1', 'type' => 'open', 'text' => 'Sebagai Engine Cadet, apa tanggung jawab penting harian Anda? Berikan contoh nyata dari kapal terakhir.'];
        $sections[1]['questions'][] = ['id' => 'rs_engine_cadet_id_t1', 'type' => 'open', 'text' => 'Dalam peran Engine Cadet, apa 3 risiko operasional teratas yang paling sering Anda temui dan langkah pengendaliannya?'];
        $sections[3]['questions'][] = ['id' => 'rs_engine_cadet_id_b1', 'type' => 'open', 'text' => 'Sebagai Engine Cadet, ceritakan saat Anda turun tangan ketika melihat kesalahan atau risiko dalam tim.'];

        InterviewTemplate::updateOrCreate(
            ['version' => 'v1', 'language' => 'id', 'position_code' => 'cadet_engine_cadet'],
            [
                'title' => 'Maritime / Role / Engine Cadet (Indonesian)',
                'template_json' => json_encode([
                    'name' => 'Maritime / Role / Engine Cadet',
                    'department' => 'cadet',
                    'language' => 'id',
                    'role_scope' => 'engine_cadet',
                    'sections' => $sections,
                    'scoring' => $this->cadetScoring(),
                ], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
            ]
        );

        $this->command->info('  [id] cadet_engine_cadet');
    }
}
