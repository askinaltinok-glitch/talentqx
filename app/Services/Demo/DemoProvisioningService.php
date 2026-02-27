<?php

namespace App\Services\Demo;

use App\Mail\DemoWelcomeMail;
use App\Models\Company;
use App\Models\Lead;
use App\Models\User;
use App\Support\BrandConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DemoProvisioningService
{
    /**
     * Default demo credits.
     */
    private const DEMO_CREDITS = 10;

    /**
     * Provision a demo account for a lead.
     */
    public function provisionForLead(Lead $lead): array
    {
        // Check if already provisioned (company already exists for this email)
        $existingUser = User::where('email', $lead->email)->first();
        if ($existingUser) {
            Log::info('Demo account already exists for lead', [
                'lead_id' => $lead->id,
                'email' => $lead->email,
                'user_id' => $existingUser->id,
            ]);

            return [
                'success' => false,
                'message' => 'Bu email için hesap zaten mevcut',
                'user' => $existingUser,
            ];
        }

        return DB::transaction(function () use ($lead) {
            // Generate company slug
            $baseSlug = Str::slug($lead->company_name ?: $lead->contact_name);
            $slug = $this->generateUniqueSlug($baseSlug);

            // Create company — platform is set from current brand context
            $company = Company::create([
                'name' => $lead->company_name ?: "{$lead->contact_name} Demo",
                'slug' => $slug,
                'platform' => BrandConfig::currentPlatform(),
                'city' => $lead->city,
                'subscription_plan' => 'free',
                'monthly_credits' => self::DEMO_CREDITS,
                'credits_period_start' => now()->startOfMonth(),
            ]);

            // Generate password
            $password = $this->generateSecurePassword();

            // Parse name
            $nameParts = $this->parseName($lead->contact_name);

            // Create user
            $user = User::create([
                'company_id' => $company->id,
                'email' => $lead->email,
                'password' => Hash::make($password),
                'first_name' => $nameParts['first_name'],
                'last_name' => $nameParts['last_name'],
                'phone' => $lead->phone,
                'is_active' => true,
                'must_change_password' => true,
            ]);

            // Send welcome email
            Mail::to($user->email)->send(new DemoWelcomeMail($user, $password, $company));

            try {
                app(\App\Services\AdminNotificationService::class)->notifyEmailSent(
                    'demo_welcome',
                    $user->email,
                    "Demo welcome: {$company->name}",
                    ['company_id' => $company->id]
                );
            } catch (\Throwable) {}

            // Log activity on lead
            $lead->activities()->create([
                'type' => 'note',
                'subject' => 'Demo hesabı oluşturuldu',
                'description' => "Email: {$user->email}\nŞirket: {$company->name}\nKontür: " . self::DEMO_CREDITS,
            ]);

            Log::info('Demo account provisioned', [
                'lead_id' => $lead->id,
                'company_id' => $company->id,
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return [
                'success' => true,
                'message' => 'Demo hesabı oluşturuldu ve mail gönderildi',
                'company' => $company,
                'user' => $user,
                'password' => $password, // Only returned for logging, not stored
            ];
        });
    }

    /**
     * Generate a unique company slug.
     */
    private function generateUniqueSlug(string $baseSlug): string
    {
        $slug = $baseSlug ?: 'demo';
        $counter = 1;

        while (Company::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Generate a secure random password.
     */
    private function generateSecurePassword(): string
    {
        // Generate a 10 character password with mixed case and numbers
        return Str::random(10);
    }

    /**
     * Parse a full name into first and last name.
     */
    private function parseName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);

        return [
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
        ];
    }
}
