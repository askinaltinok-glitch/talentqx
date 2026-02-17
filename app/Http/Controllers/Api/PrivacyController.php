<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Privacy\ConsentService;
use App\Services\Privacy\RegimeResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrivacyController extends Controller
{
    private RegimeResolver $regimeResolver;
    private ConsentService $consentService;

    public function __construct(RegimeResolver $regimeResolver, ConsentService $consentService)
    {
        $this->regimeResolver = $regimeResolver;
        $this->consentService = $consentService;
    }

    /**
     * GET /api/v1/privacy/meta
     * Returns resolved regime and policy information based on request context
     */
    public function meta(Request $request): JsonResponse
    {
        $regimeData = $this->regimeResolver->resolveFromRequest($request);

        return response()->json([
            'success' => true,
            'data' => [
                'regime' => $regimeData['regime'],
                'locale' => $regimeData['locale'],
                'country' => $regimeData['country'],
                'policy_version' => $regimeData['policy_version'],
                'policy_urls' => $regimeData['policy_urls'],
                'regime_info' => $regimeData['regime_info'],
            ],
        ]);
    }

    /**
     * GET /api/v1/privacy/policy/{regime}/{locale}
     * Returns the policy content for a specific regime and locale
     */
    public function policy(Request $request, string $regime, string $locale = 'en'): JsonResponse
    {
        $regime = strtoupper($regime);
        $locale = strtolower($locale);

        // Validate regime
        if (!in_array($regime, ['KVKK', 'GDPR', 'GLOBAL'])) {
            return response()->json([
                'success' => false,
                'error' => ['message' => 'Invalid regime'],
            ], 400);
        }

        // Validate locale
        if (!in_array($locale, ['tr', 'en', 'de', 'fr'])) {
            $locale = 'en';
        }

        // Load policy content
        $content = $this->loadPolicyContent($regime, $locale);

        return response()->json([
            'success' => true,
            'data' => [
                'regime' => $regime,
                'locale' => $locale,
                'version' => config('privacy.current_version', '2026-01'),
                'content' => $content,
                'last_updated' => '2026-01-30',
            ],
        ]);
    }

    /**
     * GET /api/v1/privacy/consents/stats (protected)
     * Returns consent statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = $this->consentService->getStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Load policy content from storage
     */
    private function loadPolicyContent(string $regime, string $locale): array
    {
        // Try to load from storage
        $path = storage_path("app/policies/{$regime}/{$locale}.json");

        if (file_exists($path)) {
            $content = json_decode(file_get_contents($path), true);
            if ($content) {
                return $content;
            }
        }

        // Return default content structure
        return $this->getDefaultPolicyContent($regime, $locale);
    }

    /**
     * Get default policy content
     */
    private function getDefaultPolicyContent(string $regime, string $locale): array
    {
        $titles = [
            'KVKK' => [
                'tr' => 'Kişisel Verilerin Korunması Aydınlatma Metni',
                'en' => 'Personal Data Protection Notice (KVKK)',
            ],
            'GDPR' => [
                'tr' => 'Genel Veri Koruma Yönetmeliği Bildirimi',
                'en' => 'General Data Protection Regulation Notice',
            ],
            'GLOBAL' => [
                'tr' => 'Gizlilik Politikası',
                'en' => 'Privacy Policy',
            ],
        ];

        return [
            'title' => $titles[$regime][$locale] ?? $titles[$regime]['en'] ?? 'Privacy Policy',
            'sections' => $this->getDefaultSections($regime, $locale),
        ];
    }

    /**
     * Get default sections for policy
     */
    private function getDefaultSections(string $regime, string $locale): array
    {
        if ($locale === 'tr') {
            return match ($regime) {
                'KVKK' => [
                    [
                        'title' => '1. Veri Sorumlusu',
                        'content' => 'TalentQX olarak, 6698 sayılı Kişisel Verilerin Korunması Kanunu ("KVKK") kapsamında veri sorumlusu sıfatıyla kişisel verilerinizi işlemekteyiz.',
                    ],
                    [
                        'title' => '2. İşlenen Kişisel Veriler',
                        'content' => 'Kimlik bilgileri (ad, soyad), iletişim bilgileri (e-posta, telefon), şirket bilgileri, değerlendirme yanıtları ve analiz sonuçları işlenmektedir.',
                    ],
                    [
                        'title' => '3. İşleme Amaçları',
                        'content' => 'Verileriniz; hizmet sunumu, iletişim, analiz ve raporlama, yasal yükümlülükler ve hizmet iyileştirme amaçlarıyla işlenmektedir.',
                    ],
                    [
                        'title' => '4. Veri Aktarımı',
                        'content' => 'Kişisel verileriniz, yasal yükümlülükler ve hizmet gereksinimleri çerçevesinde yurt içi ve yurt dışındaki üçüncü taraflara aktarılabilir.',
                    ],
                    [
                        'title' => '5. Haklarınız',
                        'content' => 'KVKK\'nın 11. maddesi uyarınca; verilerinize erişim, düzeltme, silme, aktarım ve itiraz haklarına sahipsiniz.',
                    ],
                    [
                        'title' => '6. İletişim',
                        'content' => 'Haklarınızı kullanmak için privacy@talentqx.com adresine başvurabilirsiniz.',
                    ],
                ],
                default => [
                    [
                        'title' => '1. Genel Bilgiler',
                        'content' => 'Bu gizlilik politikası, TalentQX tarafından kişisel verilerinizin nasıl toplandığını, kullanıldığını ve korunduğunu açıklar.',
                    ],
                    [
                        'title' => '2. Toplanan Veriler',
                        'content' => 'Kimlik, iletişim, şirket bilgileri ve hizmet kullanım verileri toplanmaktadır.',
                    ],
                    [
                        'title' => '3. Kullanım Amaçları',
                        'content' => 'Verileriniz hizmet sunumu, iletişim ve analiz amaçlarıyla kullanılmaktadır.',
                    ],
                    [
                        'title' => '4. Haklarınız',
                        'content' => 'Verilerinize erişim, düzeltme ve silme haklarına sahipsiniz.',
                    ],
                ],
            };
        }

        return match ($regime) {
            'KVKK' => [
                [
                    'title' => '1. Data Controller',
                    'content' => 'TalentQX processes your personal data as a data controller under the Turkish Personal Data Protection Law No. 6698 ("KVKK").',
                ],
                [
                    'title' => '2. Personal Data Processed',
                    'content' => 'We process identity information (name, surname), contact details (email, phone), company information, assessment responses, and analysis results.',
                ],
                [
                    'title' => '3. Processing Purposes',
                    'content' => 'Your data is processed for service delivery, communication, analysis and reporting, legal obligations, and service improvement.',
                ],
                [
                    'title' => '4. Data Transfers',
                    'content' => 'Your personal data may be transferred to third parties domestically and abroad within the scope of legal obligations and service requirements.',
                ],
                [
                    'title' => '5. Your Rights',
                    'content' => 'Under Article 11 of KVKK, you have the right to access, rectify, delete, transfer, and object to the processing of your data.',
                ],
                [
                    'title' => '6. Contact',
                    'content' => 'To exercise your rights, please contact privacy@talentqx.com.',
                ],
            ],
            'GDPR' => [
                [
                    'title' => '1. Data Controller',
                    'content' => 'TalentQX is the data controller for your personal data under the General Data Protection Regulation (GDPR).',
                ],
                [
                    'title' => '2. Personal Data Collected',
                    'content' => 'We collect identity data, contact data, company information, assessment responses, and usage data.',
                ],
                [
                    'title' => '3. Legal Basis',
                    'content' => 'We process your data based on: (a) your consent, (b) contract performance, (c) legitimate interests, and (d) legal obligations.',
                ],
                [
                    'title' => '4. Data Retention',
                    'content' => 'We retain your data only for as long as necessary to fulfill the purposes for which it was collected.',
                ],
                [
                    'title' => '5. Your Rights',
                    'content' => 'You have the right to access, rectify, erase, restrict processing, data portability, object, and not be subject to automated decision-making.',
                ],
                [
                    'title' => '6. International Transfers',
                    'content' => 'Your data may be transferred outside the EEA with appropriate safeguards in place.',
                ],
                [
                    'title' => '7. Contact & Complaints',
                    'content' => 'Contact our DPO at privacy@talentqx.com. You may also lodge a complaint with your supervisory authority.',
                ],
            ],
            default => [
                [
                    'title' => '1. Introduction',
                    'content' => 'This privacy policy explains how TalentQX collects, uses, and protects your personal data.',
                ],
                [
                    'title' => '2. Data We Collect',
                    'content' => 'We collect identity, contact, company information, and service usage data.',
                ],
                [
                    'title' => '3. How We Use Your Data',
                    'content' => 'Your data is used for service delivery, communication, and analytics.',
                ],
                [
                    'title' => '4. Your Rights',
                    'content' => 'You have the right to access, correct, and delete your personal data.',
                ],
                [
                    'title' => '5. Contact',
                    'content' => 'For privacy inquiries, contact privacy@talentqx.com.',
                ],
            ],
        };
    }
}
