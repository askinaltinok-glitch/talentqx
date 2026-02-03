<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Interview;
use App\Models\InterviewAnalysis;
use App\Models\Job;
use App\Models\MarketplaceAccessRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SalesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating Sales Demo Data...');

        // 1. Create Company A: Demo Retail Group (Premium + Active)
        $demoCompanyA = $this->createDemoCompanyA();
        $this->command->info("✓ Company A created: {$demoCompanyA->name} (Premium + Active)");

        // 2. Create Company B: Demo Cafe Chain (Grace Period)
        $demoCompanyB = $this->createDemoCompanyB();
        $this->command->info("✓ Company B created: {$demoCompanyB->name} (Grace Period)");

        // 3. Create Demo Users for both companies
        $demoUserA = $this->createDemoUserA($demoCompanyA);
        $this->command->info("✓ Demo user A created: {$demoUserA->email}");

        $demoUserB = $this->createDemoUserB($demoCompanyB);
        $this->command->info("✓ Demo user B created: {$demoUserB->email}");

        // 4. Create Jobs for Company A
        $jobs = $this->createJobs($demoCompanyA);
        $this->command->info("✓ Created " . count($jobs) . " job positions");

        // 5. Create 10 Candidates with Interviews and Analyses (3 strong, 4 borderline, 3 unsuitable)
        $candidates = $this->createCandidatesWithAnalyses($demoCompanyA, $jobs);
        $this->command->info("✓ Created " . count($candidates) . " candidates with analyses");

        // 6. Create THIRD Company (Marketplace Source) and Candidates
        $marketplaceCompany = $this->createMarketplaceCompany();
        $marketplaceCandidates = $this->createMarketplaceCandidates($marketplaceCompany);
        $this->command->info("✓ Created " . count($marketplaceCandidates) . " marketplace candidates from third company");

        // 7. Create pending access request for demo (with memorable token)
        $accessRequest = $this->createPendingAccessRequest($demoCompanyA, $demoUserA, $marketplaceCandidates[0]);
        $this->command->info("✓ Created pending marketplace access request");

        $this->command->newLine();
        $this->command->info('╔═══════════════════════════════════════════════════════════════╗');
        $this->command->info('║                    DEMO CREDENTIALS                           ║');
        $this->command->info('╠═══════════════════════════════════════════════════════════════╣');
        $this->command->info('║ COMPANY A (Premium + Active Subscription)                     ║');
        $this->command->info('║   Email:    demo@demoretail.com                               ║');
        $this->command->info('║   Password: Demo2024!                                         ║');
        $this->command->info('║   Company:  Demo Retail Group                                 ║');
        $this->command->info('╠═══════════════════════════════════════════════════════════════╣');
        $this->command->info('║ COMPANY B (Grace Period - Read-only)                          ║');
        $this->command->info('║   Email:    demo@democafe.com                                 ║');
        $this->command->info('║   Password: Demo2024!                                         ║');
        $this->command->info('║   Company:  Demo Cafe Chain                                   ║');
        $this->command->info('╠═══════════════════════════════════════════════════════════════╣');
        $this->command->info('║ MARKETPLACE ACCESS TOKEN (for approval demo)                  ║');
        $this->command->info("║   Token: {$accessRequest->approval_token}");
        $this->command->info('╚═══════════════════════════════════════════════════════════════╝');
        $this->command->newLine();
    }

    private function createDemoCompanyA(): Company
    {
        return Company::updateOrCreate(
            ['slug' => 'demo-retail-group'],
            [
                'name' => 'Demo Retail Group',
                'brand_email_reply_to' => 'hr@demoretail.com',
                'subscription_ends_at' => now()->addYear(),
                'is_premium' => true,
                'grace_period_ends_at' => null,
                'brand_primary_color' => '#2563eb',
                'logo_url' => null,
            ]
        );
    }

    private function createDemoCompanyB(): Company
    {
        // Company B: Grace Period - Subscription expired, 30 days left in grace period
        return Company::updateOrCreate(
            ['slug' => 'demo-cafe-chain'],
            [
                'name' => 'Demo Cafe Chain',
                'brand_email_reply_to' => 'hr@democafe.com',
                'subscription_ends_at' => now()->subDays(30), // Expired 30 days ago
                'is_premium' => false,
                'grace_period_ends_at' => now()->addDays(30), // 30 days left in grace
                'brand_primary_color' => '#059669',
                'logo_url' => null,
            ]
        );
    }

    private function createDemoUserA(Company $company): User
    {
        return User::updateOrCreate(
            ['email' => 'demo@demoretail.com'],
            [
                'company_id' => $company->id,
                'first_name' => 'Ahmet',
                'last_name' => 'Yılmaz',
                'password' => Hash::make('Demo2024!'),
                'email_verified_at' => now(),
                'is_platform_admin' => false,
            ]
        );
    }

    private function createDemoUserB(Company $company): User
    {
        return User::updateOrCreate(
            ['email' => 'demo@democafe.com'],
            [
                'company_id' => $company->id,
                'first_name' => 'Mehmet',
                'last_name' => 'Kaya',
                'password' => Hash::make('Demo2024!'),
                'email_verified_at' => now(),
                'is_platform_admin' => false,
            ]
        );
    }

    private function createJobs(Company $company): array
    {
        $jobsData = [
            [
                'title' => 'Mağaza Müdürü',
                'slug' => 'magaza-muduru',
                'description' => 'Mağaza operasyonlarını yönetecek deneyimli müdür aranıyor.',
                'location' => 'İstanbul - Kadıköy',
                'role_code' => 'retail_manager',
                'employment_type' => 'full_time',
            ],
            [
                'title' => 'Satış Danışmanı',
                'slug' => 'satis-danismani',
                'description' => 'Müşteri ilişkileri konusunda deneyimli satış danışmanı.',
                'location' => 'İstanbul - Beşiktaş',
                'role_code' => 'sales',
                'employment_type' => 'full_time',
            ],
            [
                'title' => 'Kasiyer',
                'slug' => 'kasiyer',
                'description' => 'Kasa operasyonları ve müşteri hizmetleri.',
                'location' => 'İstanbul - Şişli',
                'role_code' => 'cashier',
                'employment_type' => 'full_time',
            ],
        ];

        $jobs = [];
        foreach ($jobsData as $data) {
            $jobs[] = Job::updateOrCreate(
                ['company_id' => $company->id, 'slug' => $data['slug']],
                array_merge($data, [
                    'company_id' => $company->id,
                    'status' => 'active',
                ])
            );
        }

        return $jobs;
    }

    private function createCandidatesWithAnalyses(Company $company, array $jobs): array
    {
        // DEMO DATA SPEC: 3 strong (80-88), 4 borderline (60-72), 3 unsuitable (40-55)
        $candidatesData = [
            // ══════════════════════════════════════════════════════════════════
            // STRONG CANDIDATES (3) - Score: 80-88, Recommendation: HIRE
            // ══════════════════════════════════════════════════════════════════
            [
                'first_name' => 'Elif',
                'last_name' => 'Yıldırım',
                'email' => 'elif.yildirim@email.com',
                'phone' => '+90 532 111 2233',
                'status' => 'shortlisted',
                'job_index' => 0, // Mağaza Müdürü
                'cv_match_score' => 92.5,
                'source' => 'linkedin',
                'analysis' => [
                    'overall_score' => 88,
                    'recommendation' => 'hire',
                    'competencies' => [
                        'Liderlik' => 92,
                        'Stres Yönetimi' => 85,
                        'İletişim' => 88,
                        'Müşteri Odaklılık' => 90,
                        'Takım Yönetimi' => 86,
                    ],
                    'strengths' => ['Güçlü liderlik becerileri', 'Müşteri memnuniyeti odaklı', '5 yıl perakende deneyimi'],
                    'concerns' => ['Stres altında karar verme geliştirilmeli'],
                    'summary' => 'Elif Hanım, perakende sektöründe 5 yıllık deneyimi ve güçlü liderlik becerileriyle mağaza müdürü pozisyonu için ideal bir aday. Müşteri odaklı yaklaşımı ve takım yönetimi konusundaki yetkinliği öne çıkıyor.',
                ],
                'cv_data' => [
                    'skills' => ['Liderlik', 'Satış Yönetimi', 'Müşteri İlişkileri', 'Stok Yönetimi', 'Excel'],
                    'experience_years' => 5,
                    'education_level' => 'Lisans',
                    'experience' => [
                        ['title' => 'Mağaza Müdür Yardımcısı', 'company' => 'XYZ Retail', 'duration' => '3 yıl'],
                        ['title' => 'Kıdemli Satış Danışmanı', 'company' => 'ABC Mağazaları', 'duration' => '2 yıl'],
                    ],
                    'education' => [
                        ['degree' => 'İşletme Lisans', 'school' => 'İstanbul Üniversitesi', 'year' => '2018'],
                    ],
                ],
            ],
            [
                'first_name' => 'Fatma',
                'last_name' => 'Aksoy',
                'email' => 'fatma.aksoy@email.com',
                'phone' => '+90 538 777 8899',
                'status' => 'shortlisted',
                'job_index' => 1, // Satış Danışmanı
                'cv_match_score' => 91.0,
                'source' => 'employee_referral',
                'analysis' => [
                    'overall_score' => 85,
                    'recommendation' => 'hire',
                    'competencies' => [
                        'İletişim' => 92,
                        'Satış Teknikleri' => 88,
                        'Müşteri İlişkileri' => 90,
                        'Stres Yönetimi' => 82,
                        'Takım Çalışması' => 85,
                    ],
                    'strengths' => ['Olağanüstü iletişim becerileri', 'Yüksek motivasyon', '4 yıl satış deneyimi'],
                    'concerns' => [],
                    'summary' => 'Fatma Hanım, satış danışmanlığı için mükemmel bir aday. İletişim becerileri ve satış teknikleri konusundaki uzmanlığı ile hemen katkı sağlayabilir.',
                ],
                'cv_data' => [
                    'skills' => ['Satış', 'CRM', 'Sunum', 'Müzakere', 'Excel'],
                    'experience_years' => 4,
                    'education_level' => 'Lisans',
                    'experience' => [
                        ['title' => 'Satış Uzmanı', 'company' => 'Turkcell', 'duration' => '2 yıl'],
                        ['title' => 'Satış Danışmanı', 'company' => 'MediaMarkt', 'duration' => '2 yıl'],
                    ],
                ],
            ],
            [
                'first_name' => 'Zeynep',
                'last_name' => 'Demir',
                'email' => 'zeynep.demir@email.com',
                'phone' => '+90 534 333 4455',
                'status' => 'shortlisted',
                'job_index' => 2, // Kasiyer
                'cv_match_score' => 85.5,
                'source' => 'referral',
                'analysis' => [
                    'overall_score' => 82,
                    'recommendation' => 'hire',
                    'competencies' => [
                        'Dikkat' => 88,
                        'Hız' => 85,
                        'İletişim' => 80,
                        'Stres Yönetimi' => 78,
                        'Nakit Yönetimi' => 90,
                    ],
                    'strengths' => ['Dikkatli ve hatasız çalışma', 'Hızlı işlem yapabilme', 'Güvenilir'],
                    'concerns' => ['Yoğun saatlerde stres yönetimi geliştirilmeli'],
                    'summary' => 'Zeynep Hanım, kasa operasyonları için uygun bir aday. Dikkatli çalışma tarzı ve nakit yönetimi konusundaki yetkinliği ile değerli bir ekip üyesi olabilir.',
                ],
                'cv_data' => [
                    'skills' => ['Kasa Kullanımı', 'Para Sayma', 'Müşteri Hizmetleri'],
                    'experience_years' => 2,
                    'education_level' => 'Lise',
                    'experience' => [
                        ['title' => 'Kasiyer', 'company' => 'Migros', 'duration' => '2 yıl'],
                    ],
                ],
            ],

            // ══════════════════════════════════════════════════════════════════
            // BORDERLINE CANDIDATES (4) - Score: 60-72, Recommendation: HOLD
            // ══════════════════════════════════════════════════════════════════
            [
                'first_name' => 'Emre',
                'last_name' => 'Öztürk',
                'email' => 'emre.ozturk@email.com',
                'phone' => '+90 535 444 5566',
                'status' => 'under_review',
                'job_index' => 0, // Mağaza Müdürü
                'cv_match_score' => 72.0,
                'source' => 'indeed',
                'analysis' => [
                    'overall_score' => 72,
                    'recommendation' => 'hold',
                    'competencies' => [
                        'Liderlik' => 70,
                        'Stres Yönetimi' => 68,
                        'İletişim' => 75,
                        'Müşteri Odaklılık' => 72,
                        'Takım Yönetimi' => 68,
                    ],
                    'strengths' => ['Sektör deneyimi var', 'Öğrenmeye açık', 'Pozitif tutum'],
                    'concerns' => ['Liderlik deneyimi yetersiz', 'Takım yönetimi geliştirilmeli'],
                    'summary' => 'Emre Bey potansiyel gösteriyor ancak mağaza müdürü pozisyonu için deneyimi yetersiz. Müdür yardımcısı olarak değerlendirilebilir veya gelişim programı sonrası yeniden değerlendirilebilir.',
                ],
                'cv_data' => [
                    'skills' => ['Satış', 'Müşteri Hizmetleri', 'Stok Takibi'],
                    'experience_years' => 4,
                    'education_level' => 'Lisans',
                ],
            ],
            [
                'first_name' => 'Ayşe',
                'last_name' => 'Çelik',
                'email' => 'ayse.celik@email.com',
                'phone' => '+90 536 555 6677',
                'status' => 'interview_completed',
                'job_index' => 1, // Satış Danışmanı
                'cv_match_score' => 68.5,
                'source' => 'linkedin',
                'analysis' => [
                    'overall_score' => 68,
                    'recommendation' => 'hold',
                    'competencies' => [
                        'İletişim' => 72,
                        'Satış Teknikleri' => 65,
                        'Müşteri İlişkileri' => 70,
                        'Stres Yönetimi' => 62,
                        'Takım Çalışması' => 72,
                    ],
                    'strengths' => ['İyi takım oyuncusu', 'Pozitif tutum'],
                    'concerns' => ['Satış deneyimi az', 'Stres yönetimi geliştirilmeli'],
                    'summary' => 'Ayşe Hanım, satış danışmanlığı için temel yetkinliklere sahip ancak deneyim eksikliği var. Eğitim programı ile desteklenirse başarılı olabilir.',
                ],
                'cv_data' => [
                    'skills' => ['İletişim', 'Takım Çalışması', 'Office'],
                    'experience_years' => 1,
                    'education_level' => 'Lisans',
                ],
            ],
            [
                'first_name' => 'Burak',
                'last_name' => 'Şahin',
                'email' => 'burak.sahin@email.com',
                'phone' => '+90 539 666 7788',
                'status' => 'interview_completed',
                'job_index' => 0, // Mağaza Müdürü
                'cv_match_score' => 65.0,
                'source' => 'kariyer.net',
                'analysis' => [
                    'overall_score' => 65,
                    'recommendation' => 'hold',
                    'competencies' => [
                        'Liderlik' => 62,
                        'Stres Yönetimi' => 70,
                        'İletişim' => 68,
                        'Müşteri Odaklılık' => 65,
                        'Takım Yönetimi' => 60,
                    ],
                    'strengths' => ['Stres altında soğukkanlı', 'Çalışkan'],
                    'concerns' => ['Liderlik becerileri yetersiz', 'Deneyim eksikliği'],
                    'summary' => 'Burak Bey, pozisyon için temel yetkinlikleri karşılıyor ancak liderlik deneyimi yetersiz. Daha alt seviye bir pozisyonda değerlendirilebilir.',
                ],
                'cv_data' => [
                    'skills' => ['Satış', 'Excel', 'Raporlama'],
                    'experience_years' => 3,
                    'education_level' => 'Lisans',
                ],
            ],
            [
                'first_name' => 'Selin',
                'last_name' => 'Aydın',
                'email' => 'selin.aydin@email.com',
                'phone' => '+90 530 999 0011',
                'status' => 'under_review',
                'job_index' => 2, // Kasiyer
                'cv_match_score' => 62.0,
                'source' => 'linkedin',
                'analysis' => [
                    'overall_score' => 60,
                    'recommendation' => 'hold',
                    'competencies' => [
                        'Dikkat' => 65,
                        'Hız' => 58,
                        'İletişim' => 62,
                        'Stres Yönetimi' => 55,
                        'Nakit Yönetimi' => 60,
                    ],
                    'strengths' => ['Öğrenmeye istekli', 'Düzenli'],
                    'concerns' => ['Deneyim eksikliği', 'Hız geliştirilmeli', 'Stres yönetimi zayıf'],
                    'summary' => 'Selin Hanım, kasiyer pozisyonu için potansiyel gösteriyor ancak deneyimsiz. Eğitim programı ile gelişebilir, ancak yoğun dönemlerde zorluk yaşayabilir.',
                ],
                'cv_data' => [
                    'skills' => ['Bilgisayar', 'İletişim'],
                    'experience_years' => 0,
                    'education_level' => 'Lise',
                ],
            ],

            // ══════════════════════════════════════════════════════════════════
            // UNSUITABLE CANDIDATES (3) - Score: 40-55, Recommendation: REJECT
            // ══════════════════════════════════════════════════════════════════
            [
                'first_name' => 'Ali',
                'last_name' => 'Yılmaz',
                'email' => 'ali.yilmaz@email.com',
                'phone' => '+90 537 111 2233',
                'status' => 'rejected',
                'job_index' => 0, // Mağaza Müdürü
                'cv_match_score' => 48.0,
                'source' => 'walk-in',
                'analysis' => [
                    'overall_score' => 45,
                    'recommendation' => 'reject',
                    'competencies' => [
                        'Liderlik' => 38,
                        'Stres Yönetimi' => 42,
                        'İletişim' => 52,
                        'Müşteri Odaklılık' => 45,
                        'Takım Yönetimi' => 40,
                    ],
                    'strengths' => ['Perakende sektörünü tanıyor'],
                    'concerns' => ['Liderlik yetkinliği çok düşük', 'İletişim sorunları var', 'Müşteri odaklılık eksik', 'Referanslar olumsuz'],
                    'summary' => 'Ali Bey, mağaza müdürü pozisyonu için gereken yetkinliklerin çok altında. Liderlik ve iletişim becerileri yetersiz. Bu pozisyon için uygun değil.',
                ],
                'cv_data' => [
                    'skills' => ['Satış'],
                    'experience_years' => 2,
                    'education_level' => 'Lise',
                ],
            ],
            [
                'first_name' => 'Hakan',
                'last_name' => 'Kara',
                'email' => 'hakan.kara@email.com',
                'phone' => '+90 531 222 3344',
                'status' => 'rejected',
                'job_index' => 1, // Satış Danışmanı
                'cv_match_score' => 52.0,
                'source' => 'indeed',
                'analysis' => [
                    'overall_score' => 52,
                    'recommendation' => 'reject',
                    'competencies' => [
                        'İletişim' => 48,
                        'Satış Teknikleri' => 45,
                        'Müşteri İlişkileri' => 55,
                        'Stres Yönetimi' => 50,
                        'Takım Çalışması' => 58,
                    ],
                    'strengths' => ['Takım çalışmasına yatkın'],
                    'concerns' => ['İletişim becerileri zayıf', 'Satış teknikleri yetersiz', 'Motivasyon düşük görünüyor'],
                    'summary' => 'Hakan Bey, satış danışmanlığı pozisyonu için gereken iletişim ve satış becerilerine sahip değil. Mülakat sırasında motivasyon eksikliği gözlemlendi.',
                ],
                'cv_data' => [
                    'skills' => ['Office', 'Veri Girişi'],
                    'experience_years' => 1,
                    'education_level' => 'Lise',
                ],
            ],
            [
                'first_name' => 'Derya',
                'last_name' => 'Koç',
                'email' => 'derya.koc@email.com',
                'phone' => '+90 532 333 4455',
                'status' => 'rejected',
                'job_index' => 2, // Kasiyer
                'cv_match_score' => 42.0,
                'source' => 'walk-in',
                'analysis' => [
                    'overall_score' => 40,
                    'recommendation' => 'reject',
                    'competencies' => [
                        'Dikkat' => 35,
                        'Hız' => 40,
                        'İletişim' => 45,
                        'Stres Yönetimi' => 38,
                        'Nakit Yönetimi' => 42,
                    ],
                    'strengths' => [],
                    'concerns' => ['Dikkat eksikliği ciddi risk', 'Para sayma hatalarına yatkın', 'Stres altında çok düşük performans', 'İş disiplini zayıf'],
                    'summary' => 'Derya Hanım, kasiyer pozisyonu için uygun değil. Dikkat eksikliği ve stres altında düşük performans ciddi risk oluşturuyor. Nakit yönetimi sorumluluğu verilemez.',
                ],
                'cv_data' => [
                    'skills' => [],
                    'experience_years' => 0,
                    'education_level' => 'Lise',
                ],
            ],
        ];

        $candidates = [];
        foreach ($candidatesData as $data) {
            $job = $jobs[$data['job_index']];

            $candidate = Candidate::updateOrCreate(
                ['email' => $data['email']],
                [
                    'company_id' => $company->id,
                    'job_id' => $job->id,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone' => $data['phone'],
                    'status' => $data['status'],
                    'cv_match_score' => $data['cv_match_score'],
                    'source' => $data['source'],
                    'cv_parsed_data' => $data['cv_data'],
                    'consent_given' => true,
                    'consent_given_at' => now()->subDays(rand(1, 30)),
                ]
            );

            // Create interview and analysis if data exists
            if ($data['analysis']) {
                $interview = Interview::updateOrCreate(
                    ['candidate_id' => $candidate->id],
                    [
                        'job_id' => $job->id,
                        'status' => 'completed',
                        'started_at' => now()->subDays(rand(1, 14)),
                        'completed_at' => now()->subDays(rand(0, 7)),
                        'token_expires_at' => now()->addDays(30),
                    ]
                );

                InterviewAnalysis::updateOrCreate(
                    ['interview_id' => $interview->id],
                    [
                        'overall_score' => $data['analysis']['overall_score'],
                        'competency_scores' => $data['analysis']['competencies'],
                        'decision_snapshot' => [
                            'recommendation' => $data['analysis']['recommendation'],
                            'strengths' => $data['analysis']['strengths'],
                            'concerns' => $data['analysis']['concerns'],
                            'summary' => $data['analysis']['summary'],
                        ],
                        'analyzed_at' => now()->subDays(rand(0, 5)),
                    ]
                );
            }

            $candidates[] = $candidate;
        }

        return $candidates;
    }

    private function createMarketplaceCompany(): Company
    {
        // THIRD COMPANY - Source of marketplace candidates (NOT Company A or B)
        return Company::updateOrCreate(
            ['slug' => 'tech-talent-hub'],
            [
                'name' => 'Tech Talent Hub',
                'brand_email_reply_to' => 'hr@techtalenthub.com',
                'subscription_ends_at' => now()->addYear(),
                'is_premium' => true,
                'brand_primary_color' => '#7c3aed',
            ]
        );
    }

    private function createMarketplaceCandidates(Company $company): array
    {
        // Create jobs for marketplace company
        $salesJob = Job::updateOrCreate(
            ['company_id' => $company->id, 'slug' => 'satis-muduru'],
            [
                'title' => 'Satış Müdürü',
                'description' => 'B2B satış ekibini yönetecek deneyimli müdür',
                'location' => 'İstanbul - Levent',
                'status' => 'active',
                'role_code' => 'sales_manager',
                'employment_type' => 'full_time',
            ]
        );

        $techJob = Job::updateOrCreate(
            ['company_id' => $company->id, 'slug' => 'yazilim-uzmani'],
            [
                'title' => 'Yazılım Uzmanı',
                'description' => 'Full-stack geliştirici pozisyonu',
                'location' => 'Remote',
                'status' => 'active',
                'role_code' => 'developer',
                'employment_type' => 'full_time',
            ]
        );

        // 2 marketplace-visible candidates (per DEMO DATA SPEC)
        $candidatesData = [
            [
                'first_name' => 'Can',
                'last_name' => 'Özkan',
                'email' => 'can.ozkan@techtalent.com',
                'phone' => '+90 542 111 2233',
                'job' => $salesJob,
                'cv_match_score' => 94.0,
                'analysis' => [
                    'overall_score' => 91,
                    'recommendation' => 'hire',
                    'competencies' => [
                        'Liderlik' => 92,
                        'Satış Teknikleri' => 95,
                        'İletişim' => 88,
                        'Müşteri İlişkileri' => 90,
                        'Stres Yönetimi' => 85,
                    ],
                    'strengths' => ['7 yıl B2B satış deneyimi', 'Güçlü liderlik', 'Hedef odaklı'],
                    'concerns' => [],
                    'summary' => 'Can Bey, satış müdürlüğü için ideal bir aday. B2B satış deneyimi ve liderlik becerileri çok güçlü.',
                ],
                'cv_data' => [
                    'skills' => ['B2B Satış', 'Ekip Yönetimi', 'CRM', 'Müzakere', 'İngilizce'],
                    'experience_years' => 7,
                    'education_level' => 'Yüksek Lisans',
                    'experience' => [
                        ['title' => 'Satış Müdür Yardımcısı', 'company' => 'Oracle Türkiye', 'duration' => '3 yıl'],
                        ['title' => 'Kıdemli Satış Uzmanı', 'company' => 'SAP', 'duration' => '4 yıl'],
                    ],
                ],
            ],
            [
                'first_name' => 'Merve',
                'last_name' => 'Güneş',
                'email' => 'merve.gunes@techtalent.com',
                'phone' => '+90 544 333 4455',
                'job' => $techJob,
                'cv_match_score' => 90.0,
                'analysis' => [
                    'overall_score' => 88,
                    'recommendation' => 'hire',
                    'competencies' => [
                        'Teknik Beceri' => 92,
                        'Problem Çözme' => 90,
                        'İletişim' => 85,
                        'Takım Çalışması' => 88,
                        'Öğrenme Yeteneği' => 90,
                    ],
                    'strengths' => ['6 yıl full-stack deneyimi', 'Modern teknoloji stack', 'Takım oyuncusu'],
                    'concerns' => [],
                    'summary' => 'Merve Hanım, yazılım geliştirme pozisyonu için çok güçlü bir aday. React ve Node.js konularında uzman.',
                ],
                'cv_data' => [
                    'skills' => ['React', 'Node.js', 'TypeScript', 'PostgreSQL', 'AWS', 'Docker'],
                    'experience_years' => 6,
                    'education_level' => 'Lisans',
                    'experience' => [
                        ['title' => 'Senior Developer', 'company' => 'Trendyol', 'duration' => '3 yıl'],
                        ['title' => 'Full-Stack Developer', 'company' => 'Getir', 'duration' => '3 yıl'],
                    ],
                ],
            ],
        ];

        $candidates = [];
        foreach ($candidatesData as $data) {
            $candidate = Candidate::updateOrCreate(
                ['email' => $data['email']],
                [
                    'company_id' => $company->id,
                    'job_id' => $data['job']->id,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone' => $data['phone'],
                    'status' => 'shortlisted',
                    'cv_match_score' => $data['cv_match_score'],
                    'source' => 'linkedin',
                    'cv_parsed_data' => $data['cv_data'],
                    'consent_given' => true,
                    'consent_given_at' => now()->subDays(rand(1, 30)),
                    'visibility_scope' => 'marketplace_anonymous',
                    'marketplace_consent' => true,
                    'marketplace_consent_at' => now()->subDays(rand(1, 14)),
                ]
            );

            // Create interview and analysis
            $interview = Interview::updateOrCreate(
                ['candidate_id' => $candidate->id],
                [
                    'job_id' => $data['job']->id,
                    'status' => 'completed',
                    'started_at' => now()->subDays(rand(7, 21)),
                    'completed_at' => now()->subDays(rand(5, 14)),
                    'token_expires_at' => now()->addDays(30),
                ]
            );

            InterviewAnalysis::updateOrCreate(
                ['interview_id' => $interview->id],
                [
                    'overall_score' => $data['analysis']['overall_score'],
                    'competency_scores' => $data['analysis']['competencies'],
                    'decision_snapshot' => [
                        'recommendation' => $data['analysis']['recommendation'],
                        'strengths' => $data['analysis']['strengths'],
                        'concerns' => $data['analysis']['concerns'],
                        'summary' => $data['analysis']['summary'],
                    ],
                    'analyzed_at' => now()->subDays(rand(3, 10)),
                ]
            );

            $candidates[] = $candidate;
        }

        return $candidates;
    }

    private function createPendingAccessRequest(Company $company, User $user, Candidate $candidate): MarketplaceAccessRequest
    {
        // Create a memorable token for demo purposes
        $demoToken = 'DEMO-' . strtoupper(Str::random(8)) . '-TOKEN';

        return MarketplaceAccessRequest::updateOrCreate(
            [
                'requesting_company_id' => $company->id,
                'candidate_id' => $candidate->id,
            ],
            [
                'requesting_user_id' => $user->id,
                'request_message' => 'Bu adayın profilini inceledik ve pozisyonumuz için çok uygun görünüyor. Detaylı bilgi almak ve iletişime geçmek istiyoruz.',
                'status' => 'pending',
                'approval_token' => $demoToken,
                'token_expires_at' => now()->addDays(7),
            ]
        );
    }
}
