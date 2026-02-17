<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Job;
use App\Models\PositionTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EklerIstanbulSeeder extends Seeder
{
    /**
     * Seed Ekler İstanbul company with first job post.
     *
     * Run with: php artisan db:seed --class=EklerIstanbulSeeder
     */
    public function run(): void
    {
        $this->command->info('Creating Ekler İstanbul tenant...');

        // 1. Create Company (Tenant)
        $company = Company::firstOrCreate(
            ['slug' => 'ekler-istanbul'],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Ekler İstanbul Pastacılık San. ve Tic. A.Ş.',
                'slug' => 'ekler-istanbul',
                'address' => 'Beylikdüzü, İstanbul',
                'city' => 'İstanbul',
                'country' => 'Turkey',
                'subscription_plan' => 'pro',
                'subscription_ends_at' => now()->addYear(),
                'settings' => [
                    'locale' => 'tr',
                    'timezone' => 'Europe/Istanbul',
                    'branding' => [
                        'primary_color' => '#8B4513',
                        'accent_color' => '#D2691E',
                    ],
                ],
            ]
        );

        $this->command->info("Company created: {$company->name} (ID: {$company->id})");

        // 2. Create Branch
        $branch = Branch::firstOrCreate(
            [
                'company_id' => $company->id,
                'slug' => 'beylikduzu-merkez-osb',
            ],
            [
                'id' => Str::uuid()->toString(),
                'company_id' => $company->id,
                'name' => 'Beylikdüzü Merkez OSB',
                'slug' => 'beylikduzu-merkez-osb',
                'address' => 'Merkez OSB Mah.',
                'city' => 'İstanbul',
                'district' => 'Beylikdüzü',
                'is_active' => true,
                'settings' => [
                    'working_hours' => '08:00-22:00',
                    'manager_name' => 'Şube Müdürü',
                ],
            ]
        );

        $this->command->info("Branch created: {$branch->name} (ID: {$branch->id})");

        // 3. Create Admin User for company
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@ekler-istanbul.com'],
            [
                'id' => Str::uuid()->toString(),
                'company_id' => $company->id,
                'first_name' => 'Ekler',
                'last_name' => 'Admin',
                'email' => 'admin@ekler-istanbul.com',
                'password' => Hash::make('EklerAdmin2026!'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $this->command->info("Admin user created: {$adminUser->first_name} {$adminUser->last_name} ({$adminUser->email})");

        // 4. Find or create Tezgahtar position template
        $template = PositionTemplate::where('slug', 'tezgahtar')->first();

        if (!$template) {
            $this->command->warn('Tezgahtar template not found. Creating basic template...');
            $template = PositionTemplate::create([
                'id' => Str::uuid()->toString(),
                'name' => 'Tezgahtar',
                'slug' => 'tezgahtar',
                'description' => 'Pastane/kafe tezgahtar pozisyonu',
                'category' => 'retail',
                'competencies' => [
                    ['name' => 'Müşteri İlişkileri', 'weight' => 30],
                    ['name' => 'İletişim', 'weight' => 25],
                    ['name' => 'Hijyen Bilinci', 'weight' => 20],
                    ['name' => 'Takım Çalışması', 'weight' => 15],
                    ['name' => 'Stres Yönetimi', 'weight' => 10],
                ],
                'red_flags' => [
                    'Müşteri ile tartışma eğilimi',
                    'Hijyen kurallarına uymama',
                    'Devamsızlık geçmişi',
                ],
                'question_rules' => [
                    'max_questions' => 5,
                    'time_per_question' => 120,
                    'required_competencies' => ['customer_relations', 'communication'],
                ],
                'scoring_rubric' => [
                    'excellent' => ['min' => 80, 'label' => 'Mükemmel'],
                    'good' => ['min' => 60, 'label' => 'İyi'],
                    'average' => ['min' => 40, 'label' => 'Orta'],
                    'poor' => ['min' => 0, 'label' => 'Zayıf'],
                ],
                'is_active' => true,
            ]);
        }

        // 5. Create Job Post for Tezgahtar
        $roleCode = 'TZ';
        $applyUrl = "/apply/ekler-istanbul/beylikduzu-merkez-osb/{$roleCode}";

        $job = Job::firstOrCreate(
            [
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'role_code' => $roleCode,
            ],
            [
                'id' => Str::uuid()->toString(),
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'template_id' => $template->id,
                'created_by' => $adminUser->id,
                'title' => 'Tezgahtar',
                'slug' => 'tezgahtar-beylikduzu',
                'role_code' => $roleCode,
                'description' => 'Ekler İstanbul Beylikdüzü şubemizde çalışacak deneyimli veya deneyimsiz tezgahtar arayışındayız. Müşteri memnuniyetini ön planda tutan, hijyen kurallarına dikkat eden, takım çalışmasına yatkın adaylar başvurabilir.',
                'location' => 'Beylikdüzü Merkez OSB, İstanbul',
                'employment_type' => 'full_time',
                'experience_years' => 0,
                'competencies' => $template->competencies,
                'red_flags' => $template->red_flags,
                'interview_settings' => [
                    'max_duration_minutes' => 20,
                    'questions_count' => 5,
                    'allow_video' => true,
                    'allow_audio_only' => true,
                    'time_per_question_seconds' => 120,
                ],
                'status' => 'active',
                'published_at' => now(),
                'closes_at' => now()->addMonths(3),
                'apply_url' => $applyUrl,
            ]
        );

        $this->command->info("Job post created: {$job->title} (ID: {$job->id})");
        $this->command->info("Role Code: {$roleCode}");

        // Output summary
        $this->command->newLine();
        $this->command->info('===========================================');
        $this->command->info('EKLER İSTANBUL SETUP COMPLETE');
        $this->command->info('===========================================');
        $this->command->newLine();
        $this->command->info("Company ID:     {$company->id}");
        $this->command->info("Company Slug:   {$company->slug}");
        $this->command->info("Branch ID:      {$branch->id}");
        $this->command->info("Branch Slug:    {$branch->slug}");
        $this->command->info("Job ID:         {$job->id}");
        $this->command->info("Role Code:      {$roleCode}");
        $this->command->newLine();
        $this->command->info("Apply URL:      {$applyUrl}");
        $this->command->info("Full URL:       https://talentqx.com/api/v1{$applyUrl}");
        $this->command->newLine();
        $this->command->info("Admin Login:    admin@ekler-istanbul.com");
        $this->command->info("Admin Password: EklerAdmin2026!");
        $this->command->newLine();
    }
}
