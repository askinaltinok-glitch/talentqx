<?php

namespace App\Services\Invoice;

use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ParasutService
{
    private string $clientId;
    private string $clientSecret;
    private string $username;
    private string $password;
    private string $companyId;
    private string $baseUrl;
    private bool $sandboxMode;

    public function __construct()
    {
        // Try SystemApiKey first (encrypted DB storage), then fall back to config
        $dbCreds = $this->loadFromSystemApiKey();

        $this->clientId = $dbCreds['client_id'] ?? config('services.parasut.client_id') ?? '';
        $this->clientSecret = $dbCreds['client_secret'] ?? config('services.parasut.client_secret') ?? '';
        $this->username = $dbCreds['username'] ?? config('services.parasut.username') ?? '';
        $this->password = $dbCreds['password'] ?? config('services.parasut.password') ?? '';
        $this->companyId = $dbCreds['company_id'] ?? config('services.parasut.company_id') ?? '';
        $this->sandboxMode = config('services.parasut.sandbox', true);
        $this->baseUrl = 'https://api.parasut.com/v4';
    }

    /**
     * Load credentials from SystemApiKey table (preferred over .env).
     */
    private function loadFromSystemApiKey(): array
    {
        try {
            $key = \App\Models\SystemApiKey::where('service_name', 'parasut')
                ->where('is_active', true)
                ->first();

            if (!$key) {
                return [];
            }

            // api_key stores client_id, secret_key stores client_secret
            // metadata stores: username, password, company_id
            $meta = $key->metadata ?? [];

            return [
                'client_id' => $key->api_key ?: null,
                'client_secret' => $key->secret_key ?: null,
                'username' => $meta['username'] ?? null,
                'password' => $meta['password'] ?? null,
                'company_id' => $meta['company_id'] ?? null,
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Check if Paraşüt is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId)
            && !empty($this->clientSecret)
            && !empty($this->username)
            && !empty($this->password)
            && !empty($this->companyId);
    }

    /**
     * Get OAuth access token.
     */
    private function getAccessToken(): ?string
    {
        $cacheKey = 'parasut_access_token';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::asForm()->post('https://api.parasut.com/oauth/token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'username' => $this->username,
                'password' => $this->password,
                'grant_type' => 'password',
                'redirect_uri' => 'urn:ietf:wg:oauth:2.0:oob',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 7200;

                // Cache token with some buffer time
                Cache::put($cacheKey, $token, now()->addSeconds($expiresIn - 300));

                return $token;
            }

            Log::error('Paraşüt token request failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

        } catch (\Exception $e) {
            Log::error('Paraşüt token request exception', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Create invoice in Paraşüt.
     */
    public function createInvoice(Invoice $invoice): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Paraşüt yapılandırılmamış',
            ];
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return [
                'success' => false,
                'message' => 'Paraşüt token alınamadı',
            ];
        }

        try {
            $company = $invoice->company;
            $billingInfo = $invoice->billing_info ?? $company->getBillingSnapshot();

            // First, create or find the contact
            $contactId = $this->findOrCreateContact($token, $billingInfo);

            if (!$contactId) {
                return [
                    'success' => false,
                    'message' => 'Paraşüt müşteri oluşturulamadı',
                ];
            }

            // Create the invoice
            $invoiceData = [
                'data' => [
                    'type' => 'sales_invoices',
                    'attributes' => [
                        'item_type' => 'invoice',
                        'description' => $invoice->description,
                        'issue_date' => $invoice->issue_date->format('Y-m-d'),
                        'due_date' => $invoice->due_date->format('Y-m-d'),
                        'invoice_series' => 'TQX',
                        'invoice_id' => (int) substr($invoice->invoice_number, -4),
                        'currency' => $invoice->currency,
                        'exchange_rate' => 1.0,
                    ],
                    'relationships' => [
                        'contact' => [
                            'data' => [
                                'id' => $contactId,
                                'type' => 'contacts',
                            ],
                        ],
                        'details' => [
                            'data' => array_map(function ($item, $index) {
                                return [
                                    'id' => 'line_' . $index,
                                    'type' => 'sales_invoice_details',
                                ];
                            }, $invoice->line_items ?? [], array_keys($invoice->line_items ?? [])),
                        ],
                    ],
                ],
                'included' => array_map(function ($item, $index) use ($invoice) {
                    return [
                        'id' => 'line_' . $index,
                        'type' => 'sales_invoice_details',
                        'attributes' => [
                            'quantity' => $item['quantity'] ?? 1,
                            'unit_price' => $item['unit_price'] ?? $invoice->subtotal,
                            'vat_rate' => $invoice->tax_rate,
                            'description' => $item['description'] ?? $invoice->description,
                        ],
                    ];
                }, $invoice->line_items ?? [['description' => $invoice->description]], array_keys($invoice->line_items ?? [['description' => $invoice->description]])),
            ];

            $response = Http::withToken($token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/{$this->companyId}/sales_invoices", $invoiceData);

            if ($response->successful()) {
                $data = $response->json();
                $parasutId = $data['data']['id'] ?? null;

                $invoice->update([
                    'parasut_invoice_id' => $parasutId,
                ]);

                Log::info('Paraşüt invoice created', [
                    'invoice_id' => $invoice->id,
                    'parasut_id' => $parasutId,
                ]);

                return [
                    'success' => true,
                    'parasut_id' => $parasutId,
                ];
            }

            Log::error('Paraşüt invoice creation failed', [
                'invoice_id' => $invoice->id,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => 'Fatura oluşturulamadı',
            ];

        } catch (\Exception $e) {
            Log::error('Paraşüt invoice exception', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Find or create contact in Paraşüt.
     */
    private function findOrCreateContact(string $token, array $billingInfo): ?string
    {
        $email = $billingInfo['email'] ?? null;
        $taxNumber = $billingInfo['tax_number'] ?? null;

        // Try to find existing contact
        if ($taxNumber) {
            $response = Http::withToken($token)
                ->get("{$this->baseUrl}/{$this->companyId}/contacts", [
                    'filter[tax_number]' => $taxNumber,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['data'])) {
                    return $data['data'][0]['id'];
                }
            }
        }

        // Create new contact
        $contactData = [
            'data' => [
                'type' => 'contacts',
                'attributes' => [
                    'name' => $billingInfo['name'] ?? $billingInfo['legal_name'] ?? 'Müşteri',
                    'short_name' => mb_substr($billingInfo['name'] ?? 'Müşteri', 0, 20),
                    'contact_type' => ($billingInfo['billing_type'] ?? 'individual') === 'corporate' ? 'company' : 'person',
                    'tax_number' => $taxNumber,
                    'tax_office' => $billingInfo['tax_office'] ?? null,
                    'email' => $email,
                    'address' => $billingInfo['address'] ?? null,
                    'city' => $billingInfo['city'] ?? null,
                    'district' => $billingInfo['postal_code'] ?? null,
                    'phone' => $billingInfo['phone'] ?? null,
                    'account_type' => 'customer',
                ],
            ],
        ];

        $response = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post("{$this->baseUrl}/{$this->companyId}/contacts", $contactData);

        if ($response->successful()) {
            $data = $response->json();
            return $data['data']['id'] ?? null;
        }

        Log::error('Paraşüt contact creation failed', [
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        return null;
    }

    /**
     * Send e-invoice (e-fatura) via Paraşüt.
     */
    public function sendEInvoice(Invoice $invoice): array
    {
        if (!$invoice->parasut_invoice_id) {
            return [
                'success' => false,
                'message' => 'Paraşüt fatura ID bulunamadı',
            ];
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return [
                'success' => false,
                'message' => 'Paraşüt token alınamadı',
            ];
        }

        try {
            $response = Http::withToken($token)
                ->post("{$this->baseUrl}/{$this->companyId}/e_invoices", [
                    'data' => [
                        'type' => 'e_invoices',
                        'attributes' => [
                            'vat_withholding_code' => null,
                            'vat_exemption_reason_code' => null,
                            'vat_exemption_reason' => null,
                            'note' => 'Octopus AI Kontür Satışı',
                            'scenario' => 'commercial', // or 'basic'
                        ],
                        'relationships' => [
                            'invoice' => [
                                'data' => [
                                    'id' => $invoice->parasut_invoice_id,
                                    'type' => 'sales_invoices',
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                $invoice->markAsSent();

                Log::info('E-invoice sent via Paraşüt', [
                    'invoice_id' => $invoice->id,
                ]);

                return [
                    'success' => true,
                    'message' => 'E-fatura gönderildi',
                ];
            }

            return [
                'success' => false,
                'message' => 'E-fatura gönderilemedi',
            ];

        } catch (\Exception $e) {
            Log::error('Paraşüt e-invoice exception', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get invoice PDF URL from Paraşüt.
     */
    public function getInvoicePdf(Invoice $invoice): ?string
    {
        if (!$invoice->parasut_invoice_id) {
            return null;
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->get("{$this->baseUrl}/{$this->companyId}/sales_invoices/{$invoice->parasut_invoice_id}", [
                    'include' => 'active_e_document',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['included'][0]['attributes']['pdf_url'] ?? null;
            }

        } catch (\Exception $e) {
            Log::error('Paraşüt PDF fetch exception', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
