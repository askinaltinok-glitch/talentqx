<?php

namespace App\Services\Payment;

use App\Models\Company;
use App\Models\CreditPackage;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\CreditUsageLog;
use App\Services\Invoice\ParasutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IyzicoService
{
    private string $apiKey;
    private string $secretKey;
    private string $baseUrl;
    private bool $sandboxMode;

    public function __construct()
    {
        $this->apiKey = config('services.iyzico.api_key') ?? '';
        $this->secretKey = config('services.iyzico.secret_key') ?? '';
        $this->sandboxMode = config('services.iyzico.sandbox', true);
        $this->baseUrl = $this->sandboxMode
            ? 'https://sandbox-api.iyzipay.com'
            : 'https://api.iyzipay.com';
    }

    /**
     * Check if İyzico is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->secretKey);
    }

    /**
     * Initialize checkout form for a package purchase.
     */
    public function initializeCheckout(
        Company $company,
        User $user,
        CreditPackage $package,
        string $currency = 'TRY'
    ): array {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Ödeme sistemi yapılandırılmamış',
            ];
        }

        $conversationId = 'TQX-' . Str::uuid();
        $price = $package->getPrice($currency);

        // Create pending payment record
        $payment = Payment::create([
            'company_id' => $company->id,
            'package_id' => $package->id,
            'payment_provider' => Payment::PROVIDER_IYZICO,
            'conversation_id' => $conversationId,
            'status' => Payment::STATUS_PENDING,
            'amount' => $price,
            'currency' => $currency,
            'credits_added' => $package->credits,
            'metadata' => [
                'package_name' => $package->name,
                'user_id' => $user->id,
                'user_email' => $user->email,
            ],
        ]);

        try {
            $checkoutRequest = $this->buildCheckoutRequest(
                company: $company,
                user: $user,
                package: $package,
                payment: $payment,
                conversationId: $conversationId,
                currency: $currency
            );

            $response = $this->makeRequest('/payment/iyzipos/checkoutform/initialize/auth/ecom', $checkoutRequest);

            if ($response['status'] !== 'success') {
                $payment->markAsFailed($response['errorMessage'] ?? 'İyzico error', $response);
                return [
                    'success' => false,
                    'message' => $response['errorMessage'] ?? 'Ödeme başlatılamadı',
                ];
            }

            $payment->update([
                'status' => Payment::STATUS_PROCESSING,
                'provider_response' => $response,
            ]);

            return [
                'success' => true,
                'checkout_form' => $response['checkoutFormContent'] ?? null,
                'token' => $response['token'] ?? null,
                'payment_id' => $payment->id,
                'conversation_id' => $conversationId,
            ];

        } catch (\Exception $e) {
            Log::error('İyzico checkout initialization failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            $payment->markAsFailed($e->getMessage());

            return [
                'success' => false,
                'message' => 'Ödeme başlatılırken hata oluştu',
            ];
        }
    }

    /**
     * Handle callback from İyzico after payment.
     */
    public function handleCallback(string $token): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Ödeme sistemi yapılandırılmamış',
            ];
        }

        try {
            $response = $this->makeRequest('/payment/iyzipos/checkoutform/auth/ecom/detail', [
                'locale' => 'tr',
                'token' => $token,
            ]);

            if ($response['status'] !== 'success' || $response['paymentStatus'] !== 'SUCCESS') {
                Log::warning('İyzico callback failed', [
                    'token' => $token,
                    'response' => $response,
                ]);

                // Find and update payment
                $conversationId = $response['conversationId'] ?? null;
                if ($conversationId) {
                    $payment = Payment::where('conversation_id', $conversationId)->first();
                    if ($payment) {
                        $payment->markAsFailed($response['errorMessage'] ?? 'Payment failed', $response);
                    }
                }

                return [
                    'success' => false,
                    'message' => $response['errorMessage'] ?? 'Ödeme başarısız',
                ];
            }

            // Find payment by conversation ID
            $conversationId = $response['conversationId'];
            $payment = Payment::where('conversation_id', $conversationId)->first();

            if (!$payment) {
                Log::error('Payment not found for callback', [
                    'conversation_id' => $conversationId,
                ]);
                return [
                    'success' => false,
                    'message' => 'Ödeme kaydı bulunamadı',
                ];
            }

            // Mark payment as completed and add credits
            return DB::transaction(function () use ($payment, $response) {
                $payment->markAsCompleted($response['paymentId'] ?? $response['token'], $response);

                // Log credit addition
                CreditUsageLog::create([
                    'company_id' => $payment->company_id,
                    'interview_id' => null,
                    'action' => CreditUsageLog::ACTION_ADD,
                    'amount' => $payment->credits_added,
                    'balance_before' => $payment->company->getRemainingCredits() - $payment->credits_added,
                    'balance_after' => $payment->company->getRemainingCredits(),
                    'reason' => "Kontür satın alımı: {$payment->package->name}",
                    'created_at' => now(),
                ]);

                // Create invoice
                $this->createInvoice($payment);

                Log::info('Payment completed', [
                    'payment_id' => $payment->id,
                    'company_id' => $payment->company_id,
                    'credits_added' => $payment->credits_added,
                ]);

                return [
                    'success' => true,
                    'payment_id' => $payment->id,
                    'credits_added' => $payment->credits_added,
                ];
            });

        } catch (\Exception $e) {
            Log::error('İyzico callback processing failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Ödeme işlenirken hata oluştu',
            ];
        }
    }

    /**
     * Create invoice for completed payment.
     */
    private function createInvoice(Payment $payment): Invoice
    {
        $company = $payment->company;
        $package = $payment->package;

        $taxCalculation = Invoice::calculateTax($payment->amount);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'payment_id' => $payment->id,
            'status' => Invoice::STATUS_PAID,
            'subtotal' => $taxCalculation['subtotal'],
            'tax_rate' => $taxCalculation['tax_rate'],
            'tax_amount' => $taxCalculation['tax_amount'],
            'total_amount' => $taxCalculation['total_amount'],
            'currency' => $payment->currency,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'description' => "Octopus AI Kontür Paketi: {$package->name} ({$package->credits} Kontür)",
            'line_items' => [
                [
                    'description' => "{$package->name} - {$package->credits} Kontür",
                    'quantity' => 1,
                    'unit_price' => $payment->amount,
                    'total' => $payment->amount,
                ],
            ],
            'billing_info' => $company->getBillingSnapshot(),
        ]);

        // Try to send to Paraşüt if configured
        try {
            $parasutService = app(ParasutService::class);
            if ($parasutService->isConfigured()) {
                $parasutService->createInvoice($invoice);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to create Paraşüt invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $invoice;
    }

    /**
     * Build İyzico checkout request.
     */
    private function buildCheckoutRequest(
        Company $company,
        User $user,
        CreditPackage $package,
        Payment $payment,
        string $conversationId,
        string $currency
    ): array {
        $price = number_format($package->getPrice($currency), 2, '.', '');
        $callbackUrl = config('app.url') . '/api/v1/payments/callback';

        return [
            'locale' => 'tr',
            'conversationId' => $conversationId,
            'price' => $price,
            'paidPrice' => $price,
            'currency' => $currency,
            'basketId' => $payment->id,
            'paymentGroup' => 'PRODUCT',
            'callbackUrl' => $callbackUrl,
            'enabledInstallments' => [1, 2, 3, 6],
            'buyer' => [
                'id' => $user->id,
                'name' => $user->first_name ?? 'Kullanıcı',
                'surname' => $user->last_name ?? '',
                'gsmNumber' => $user->phone ?? '+905000000000',
                'email' => $user->email,
                'identityNumber' => $company->tax_number ?? '11111111111',
                'registrationAddress' => $company->billing_address ?? $company->address ?? 'Türkiye',
                'ip' => request()->ip() ?? '127.0.0.1',
                'city' => $company->billing_city ?? 'İstanbul',
                'country' => 'Turkey',
            ],
            'shippingAddress' => [
                'contactName' => $user->full_name ?? $user->email,
                'city' => $company->billing_city ?? 'İstanbul',
                'country' => 'Turkey',
                'address' => $company->billing_address ?? 'Türkiye',
            ],
            'billingAddress' => [
                'contactName' => $company->legal_name ?? $company->name,
                'city' => $company->billing_city ?? 'İstanbul',
                'country' => 'Turkey',
                'address' => $company->billing_address ?? 'Türkiye',
            ],
            'basketItems' => [
                [
                    'id' => $package->id,
                    'name' => "Octopus AI {$package->name}",
                    'category1' => 'Yazılım',
                    'category2' => 'SaaS',
                    'itemType' => 'VIRTUAL',
                    'price' => $price,
                ],
            ],
        ];
    }

    /**
     * Make request to İyzico API.
     */
    private function makeRequest(string $endpoint, array $data): array
    {
        $randomString = $this->generateRandomString(8);
        $jsonData = json_encode($data);

        $hashString = $this->apiKey . $randomString . $this->secretKey . $jsonData;
        $signature = base64_encode(sha1($hashString, true));
        $authorizationString = 'IYZWS ' . $this->apiKey . ':' . $signature;

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: ' . $authorizationString,
            'x-iyzi-rnd: ' . $randomString,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('İyzico API error: ' . $error);
        }

        return json_decode($response, true) ?? [];
    }

    /**
     * Generate random string for İyzico auth.
     */
    private function generateRandomString(int $length): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}
