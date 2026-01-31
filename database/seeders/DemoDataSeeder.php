<?php

namespace Database\Seeders;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\Job;
use App\Models\PositionTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $company = $this->createDemoCompany();
        $this->createDemoUsers($company);
        $this->createDemoJobs($company);
    }

    private function createDemoCompany(): Company
    {
        return Company::updateOrCreate(
            ['slug' => 'demo-sirket'],
            [
                'name' => 'Demo Sirket A.S.',
                'logo_url' => null,
                'address' => 'Levent, Istanbul',
                'city' => 'Istanbul',
                'country' => 'Turkey',
                'subscription_plan' => 'premium',
                'subscription_ends_at' => now()->addYear(),
                'settings' => [
                    'default_interview_duration' => 30,
                    'default_question_time' => 180,
                    'branding' => [
                        'primary_color' => '#2563eb',
                    ],
                ],
            ]
        );
    }

    private function createDemoUsers(Company $company): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $hrRole = Role::where('name', 'hr_manager')->first();

        User::updateOrCreate(
            ['email' => 'admin@talentqx.com'],
            [
                'company_id' => $company->id,
                'role_id' => $adminRole->id,
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'hr@demo.com'],
            [
                'company_id' => $company->id,
                'role_id' => $hrRole->id,
                'first_name' => 'Ayse',
                'last_name' => 'Yilmaz',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
    }

    private function createDemoJobs(Company $company): void
    {
        $templates = PositionTemplate::all();
        $creator = User::where('email', 'hr@demo.com')->first();

        $jobsData = [
            [
                'template_slug' => 'tezgahtar-kasiyer',
                'title' => 'Tezgahtar - Kadikoy Subesi',
                'location' => 'Istanbul, Kadikoy',
                'description' => 'Kadikoy subesinde calisacak deneyimli tezgahtar ariyoruz.',
            ],
            [
                'template_slug' => 'sofor',
                'title' => 'Dagitim Soforu - Istanbul Anadolu',
                'location' => 'Istanbul, Anadolu Yakasi',
                'description' => 'Anadolu yakasinda dagitim yapacak sofor ariyoruz.',
            ],
            [
                'template_slug' => 'depocu',
                'title' => 'Depo Personeli - Merkez Depo',
                'location' => 'Istanbul, Tuzla',
                'description' => 'Merkez depomuzda calisacak depocu ariyoruz.',
            ],
            [
                'template_slug' => 'imalat-personeli',
                'title' => 'Pastahane Usta Yardimcisi',
                'location' => 'Istanbul, Besiktas',
                'description' => 'Pastahanemizde calisacak usta yardimcisi ariyoruz.',
            ],
            [
                'template_slug' => 'uretim-sefi',
                'title' => 'Uretim Sefi - Gida Fabrikasi',
                'location' => 'Istanbul, Hadimkoy',
                'description' => 'Gida uretim tesisimizde calisacak uretim sefi ariyoruz.',
            ],
        ];

        foreach ($jobsData as $jobData) {
            $template = $templates->firstWhere('slug', $jobData['template_slug']);

            if (!$template) {
                continue;
            }

            $job = Job::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'slug' => Str::slug($jobData['title']),
                ],
                [
                    'template_id' => $template->id,
                    'created_by' => $creator?->id,
                    'title' => $jobData['title'],
                    'description' => $jobData['description'],
                    'location' => $jobData['location'],
                    'employment_type' => 'full_time',
                    'experience_years' => rand(0, 3),
                    'status' => 'active',
                    'published_at' => now()->subDays(rand(1, 30)),
                    'closes_at' => now()->addMonths(1),
                ]
            );

            $this->createDemoCandidates($job);
        }
    }

    private function createDemoCandidates(Job $job): void
    {
        $names = [
            ['Mehmet', 'Yilmaz'],
            ['Ayse', 'Demir'],
            ['Ali', 'Kaya'],
            ['Fatma', 'Celik'],
            ['Hasan', 'Sahin'],
        ];

        $statuses = [
            Candidate::STATUS_APPLIED,
            Candidate::STATUS_INTERVIEW_PENDING,
            Candidate::STATUS_INTERVIEW_COMPLETED,
            Candidate::STATUS_UNDER_REVIEW,
            Candidate::STATUS_SHORTLISTED,
        ];

        foreach ($names as $index => $name) {
            $email = strtolower($name[0]) . '.' . strtolower($name[1]) . $index . '@email.com';

            Candidate::updateOrCreate(
                [
                    'job_id' => $job->id,
                    'email' => $email,
                ],
                [
                    'first_name' => $name[0],
                    'last_name' => $name[1],
                    'phone' => '+9055' . rand(10000000, 99999999),
                    'source' => ['website', 'linkedin', 'referral'][rand(0, 2)],
                    'status' => $statuses[$index],
                    'cv_match_score' => rand(60, 100),
                    'consent_given' => true,
                    'consent_version' => '1.0',
                    'consent_given_at' => now()->subDays(rand(1, 10)),
                ]
            );
        }
    }
}
