<?php

namespace App\Services\SMS;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VerimorService
{
    private string $username;
    private string $password;
    private string $sourceAddr;
    private string $baseUrl = 'https://sms.verimor.com.tr/v2';
    private bool $enabled;

    public function __construct()
    {
        $this->username = config('services.verimor.username', '');
        $this->password = config('services.verimor.password', '');
        $this->sourceAddr = config('services.verimor.source_addr', 'TALENTQX');
        $this->enabled = config('services.verimor.enabled', false);
    }

    /**
     * Check if Verimor is configured.
     */
    public function isConfigured(): bool
    {
        return $this->enabled && !empty($this->username) && !empty($this->password);
    }

    /**
     * Send SMS to a single recipient.
     */
    public function send(string $phone, string $message): array
    {
        return $this->sendBulk([
            ['phone' => $phone, 'message' => $message],
        ]);
    }

    /**
     * Send bulk SMS.
     *
     * @param array $messages Array of ['phone' => '...', 'message' => '...']
     */
    public function sendBulk(array $messages): array
    {
        if (!$this->isConfigured()) {
            Log::warning('Verimor SMS not configured, skipping send');
            return [
                'success' => false,
                'message' => 'SMS servisi yapılandırılmamış',
            ];
        }

        // Normalize phone numbers
        $normalizedMessages = array_map(function ($item) {
            return [
                'msg' => $item['message'],
                'dest' => $this->normalizePhone($item['phone']),
            ];
        }, $messages);

        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->asJson()
                ->post("{$this->baseUrl}/send.json", [
                    'source_addr' => $this->sourceAddr,
                    'messages' => $normalizedMessages,
                    'valid_for' => '48:00', // 48 hours validity
                ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('Verimor SMS sent', [
                    'count' => count($messages),
                    'response' => $data,
                ]);

                return [
                    'success' => true,
                    'message_id' => $data['id'] ?? null,
                    'sent_count' => count($messages),
                ];
            }

            Log::error('Verimor SMS failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => $response->json()['message'] ?? 'SMS gönderilemedi',
                'error_code' => $response->status(),
            ];

        } catch (\Exception $e) {
            Log::error('Verimor SMS exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send OTP SMS.
     */
    public function sendOtp(string $phone, string $code): array
    {
        $message = "TalentQX doğrulama kodunuz: {$code}. Bu kodu kimseyle paylaşmayın.";
        return $this->send($phone, $message);
    }

    /**
     * Send interview invitation SMS.
     */
    public function sendInterviewInvite(string $phone, string $candidateName, string $companyName, string $link): array
    {
        $message = "Sayın {$candidateName}, {$companyName} mülakatınız hazır. Başlamak için: {$link}";
        return $this->send($phone, $message);
    }

    /**
     * Send interview reminder SMS.
     */
    public function sendInterviewReminder(string $phone, string $candidateName, string $companyName, string $link): array
    {
        $message = "Sayın {$candidateName}, {$companyName} mülakatınızı henüz tamamlamadınız. Link: {$link}";
        return $this->send($phone, $message);
    }

    /**
     * Send payment confirmation SMS.
     */
    public function sendPaymentConfirmation(string $phone, int $credits, string $amount): array
    {
        $message = "TalentQX ödemeniz alındı. {$credits} kontür hesabınıza eklendi. Tutar: {$amount}";
        return $this->send($phone, $message);
    }

    /**
     * Send low credit warning SMS.
     */
    public function sendLowCreditWarning(string $phone, int $remaining): array
    {
        $message = "TalentQX: Kalan kontürünüz {$remaining} adet. Kontür satın almak için: talentqx.com/platform/credits";
        return $this->send($phone, $message);
    }

    /**
     * Get account balance/credit.
     */
    public function getBalance(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'SMS servisi yapılandırılmamış',
            ];
        }

        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->get("{$this->baseUrl}/balance.json");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'balance' => $data['balance'] ?? 0,
                    'currency' => 'TRY',
                ];
            }

            return [
                'success' => false,
                'message' => 'Bakiye alınamadı',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get SMS delivery status.
     */
    public function getStatus(string $messageId): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'SMS servisi yapılandırılmamış',
            ];
        }

        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->get("{$this->baseUrl}/status", [
                    'id' => $messageId,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Durum alınamadı',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Normalize Turkish phone number to international format.
     */
    private function normalizePhone(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Handle Turkish numbers
        if (str_starts_with($phone, '0')) {
            $phone = '90' . substr($phone, 1);
        } elseif (str_starts_with($phone, '5')) {
            $phone = '90' . $phone;
        } elseif (!str_starts_with($phone, '90')) {
            // Assume it's already in international format
            // but if it starts with something else, prepend 90
            if (strlen($phone) === 10) {
                $phone = '90' . $phone;
            }
        }

        return $phone;
    }
}
