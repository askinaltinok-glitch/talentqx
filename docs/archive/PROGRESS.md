# TalentQX - Geliştirme İlerleme Raporu

**Son Güncelleme:** 2026-02-17

---

## Son Tamamlanan Sprint: SPRINT-5.6 Customer Portal Hardening & i18n

---

## SPRINT-5.6: Customer Portal Hardening & i18n

**Durum:** ✅ TAMAMLANDI (2026-02-17)

### Özet

Octopus-AI portal ve octo-admin için üç kritik geliştirme: i18n sistemi, viewer/HR rol ayrımı, ve müşteri taraflı PDF Decision Packet endpoint'i.

### A) Octo-Admin i18n Sistemi (4 Dil)

**Diller:** EN, TR, RU, AZ
**Yöntem:** React Context + localStorage (`octo_lang` key), kütüphane yok
**Kapsam:** 4 octo-admin sayfası + navigasyon

| Dosya | Açıklama |
|-------|----------|
| `talentqx-frontend/src/lib/i18n/dictionaries/{en,tr,ru,az}.ts` | ~96 anahtar, typed dictionary |
| `talentqx-frontend/src/lib/i18n/index.ts` | Context, useI18n hook, useI18nSetup |
| `talentqx-frontend/src/components/i18n/I18nProvider.tsx` | Provider wrapper |
| `talentqx-frontend/src/components/i18n/LanguageSwitcher.tsx` | EN/TR/RU/AZ toggle butonları |

**Güncellenen sayfalar:**
- `/octo-admin/candidates` - Tüm stringler `t()` ile
- `/octo-admin/jobs` - Tüm stringler `t()` ile
- `/octo-admin/interviews` - Tüm stringler `t()` ile
- `/octo-admin/certificates` - Tüm stringler `t()` ile
- `OctoAdminNav.tsx` - Nav linkleri + LanguageSwitcher
- `octo-admin/layout.tsx` - I18nProvider wrapper

### B) Viewer/HR Rol Ayrımı (Write Guard)

**Mekanizma:** `companies.settings.portal_viewers` JSON array → email match → write block

**Dosya:** `api/app/Http/Middleware/RequireCustomerScope.php`

**Mantık:**
1. Platform admin → bypass
2. Company user + email in `portal_viewers` → GET/HEAD/OPTIONS OK, POST/PATCH/DELETE → 403 `read_only_account`
3. Normal company user → path allowlist check

**DB:** UMMAN Denizcilik settings güncellendi: `{"portal_viewers": ["viewer@ummandenizcilik.com"]}`

**Guard kapsamı:**
- ✅ CSV import POST bloklanır
- ✅ Interview create POST bloklanır
- ✅ Tüm update/delete aksiyonları bloklanır
- ✅ GET (okuma) serbest

### C) PDF Decision Packet (Customer-Facing)

**Endpoint:** `GET /v1/form-interviews/{id}/decision-packet.pdf`
**Middleware:** `auth:sanctum` + `customer.scope` + `force.password.change` + `throttle:10,1`
**Tenant check:** `interview.company_id == user.company_id` (platform admin bypass)

**Migration:** `company_id` nullable UUID kolonu `form_interviews` tablosuna eklendi

**Controller:** `FormInterviewController::decisionPacketPdf()`
- Interview + answers + outcome eager load
- Completion check (400 if not completed)
- Tenant check (403 if mismatch)
- SHA256 checksum for integrity
- Same blade template as admin (`pdf.decision-packet`)

### D) Test Sonuçları

| Test | Sonuç |
|------|-------|
| `/octo-admin/candidates` (i18n) | ✅ 200, EN/TR/RU/AZ switcher |
| `/portal/login` | ✅ 200, login form |
| `/portal/crew-import` | ✅ 200, CSV upload page |
| Decision packet PDF route | ✅ 401 (auth required - correct) |
| Viewer GET `/candidates` | ✅ 200 (read allowed) |
| Viewer POST `/candidates` | ✅ 403 `read_only_account` |

### E) Dosya Yapısı

```
talentqx-frontend/src/
├── lib/i18n/
│   ├── index.ts                              # NEW: Context + hook
│   └── dictionaries/
│       ├── en.ts                             # NEW: English (96 keys)
│       ├── tr.ts                             # NEW: Turkish
│       ├── ru.ts                             # NEW: Russian
│       └── az.ts                             # NEW: Azerbaijani
├── components/i18n/
│   ├── I18nProvider.tsx                      # NEW: Provider
│   └── LanguageSwitcher.tsx                  # NEW: Language toggle
├── app/octo-admin/
│   ├── layout.tsx                            # MODIFIED: I18nProvider wrap
│   ├── candidates/page.tsx                   # MODIFIED: i18n
│   ├── jobs/page.tsx                         # MODIFIED: i18n
│   ├── interviews/page.tsx                   # MODIFIED: i18n
│   └── certificates/page.tsx                 # MODIFIED: i18n
└── components/octo-admin/
    └── OctoAdminNav.tsx                      # MODIFIED: i18n + LanguageSwitcher

api/
├── app/Http/
│   ├── Middleware/RequireCustomerScope.php    # MODIFIED: Viewer write-guard
│   └── Controllers/Api/
│       └── FormInterviewController.php       # MODIFIED: decisionPacketPdf()
├── app/Models/
│   └── FormInterview.php                     # MODIFIED: company_id fillable
├── database/migrations/
│   └── 2026_02_17_180000_add_company_id_to_form_interviews.php  # NEW
└── routes/api.php                            # MODIFIED: decision-packet route
```

---

## SPRINT-5.5 (Önceki): Maritime Template Resolution & Role-Specific Engine

---

## SPRINT-5.5: Maritime Template Resolution & Role-Specific Engine

**Durum:** ✅ TAMAMLANDI (2026-02-17)

### Özet

Maritime mülakat template resolver'ında kritik bir sorun tespit ve çözüldü: maritime roller (kaptan, mühendis vb.) için retail/mağazacılık soruları çıkıyordu. Kök sebep analizi yapılarak resolver zinciri, template envanteri ve controller güvenlik katmanları kökten yeniden yapılandırıldı.

### Kök Sebep

| Sorun | Neden |
|-------|-------|
| Maritime role → retail template | `industry_code` gönderilmediğinde `'general'` default'a düşüyor, resolver `__generic__` (retail) template seçiyordu |
| Template yokluğu | Maritime department-generic ve role-specific template'ler DB'de seed edilmemişti |
| Silent fallback | Bilinmeyen maritime role gönderildiğinde sessizce retail'e düşüyordu |

### A) Eski Bozuk Kayıtlar Düzeltildi

- 7 interview: `__generic__` (retail) → `deck___generic__` / `engine___generic__` (maritime)
- 5 interview: `deck___generic__` → `deck_captain` / `deck_bosun` / `deck_third_officer` (role-specific)

### B) Maritime Template Envanteri (88 template, 4 dil)

**Komut:** `php82 artisan maritime:seed-role-templates` (idempotent)

| Department | Role-specific | Generic (fallback) | Dil | Toplam |
|-----------|:---:|:---:|:---:|:---:|
| Deck | 7 rol × 4 | 1 × 4 | tr/en/az/ru | 32 |
| Engine | 6 rol × 4 | 1 × 4 | tr/en/az/ru | 28 |
| Galley | 3 rol × 4 | 1 × 4 | tr/en/az/ru | 16 |
| Cadet | 2 rol × 4 | 1 × 4 | tr/en/az/ru | 12 |
| **Maritime toplam** | **72** | **16** | **4** | **88** |

**18 Maritime Rol:**

| Deck | Engine | Galley | Cadet |
|------|--------|--------|-------|
| captain | chief_engineer | cook | deck_cadet |
| chief_officer | second_engineer | steward | engine_cadet |
| second_officer | third_engineer | messman | |
| third_officer | motorman | | |
| bosun | oiler | | |
| able_seaman | electrician | | |
| ordinary_seaman | | | |

**Captain & Chief Engineer bonus soruları:** COLREG scenario, passage plan, crisis command, blackout recovery, LOTO/PTW enforcement

### C) Controller Fallback Güvenliği

**Dosya:** `api/app/Http/Controllers/Api/FormInterviewController.php`

3 katmanlı koruma:

1. **Auto-detect:** `MaritimeRole::isValid($position)` → tanınan maritime role ise `industry_code='maritime'` zorlanır
2. **Seafarer check:** `pool_candidate.seafarer=1` veya `primary_industry='maritime'` ise industry zorlanır
3. **Hard guard:** Maritime industry ama tanınmayan role → 422 döner (silent fallback yok)

### D) DecisionCard Debug Pill (Frontend)

**Dosya:** `talentqx-frontend/src/components/admin/form-interviews/DecisionCard.tsx`

DecisionCard footer'ına system trace eklendi:

```
🛡 Template: deck_captain | Language: EN | Industry: maritime (auto) | Resolver: role-specific
```

### E) Kanıt Testleri

**Test 1 — Auto-detect (industry gönderilmeden):**
```
POST { "position_code": "captain", "language": "en" }
→ industry_code: maritime (auto) ✓
→ template: deck_captain ✓
```

**Test 2 — Alias resolve:**
```
POST { "position_code": "master", "language": "tr" }
→ normalize: captain → deck_captain ✓
```

**Test 3 — Typo koruması:**
```
POST { "position_code": "captan", "industry_code": "maritime" }
→ 422: unknown_maritime_role ✓
```

### F) İmzalı Kontrol Maddeleri

- [x] Resolver zinciri: role-specific → dept generic → (retail fallback yok)
- [x] Auto-detect: `MaritimeRole::isValid()` + `seafarer` / `primary_industry` ile industry forced
- [x] Snapshot güvenliği: `form_interviews.template_json` + `sha256` güncelleniyor
- [x] Bilinmeyen rol koruması: Tanınmayan maritime role → 422 (silent fallback yok)
- [x] Debug pill: DecisionCard footer'da template/language/industry/resolver bilgisi
- [x] SHA256 uyumsuzluğu: 0

### G) Operational Guarantees

- **Maritime role → retail template selection:** impossible by design
- **Industry auto-detection:** backend enforced
- **Template integrity:** SHA256 verified
- **Multi-language parity:** TR / EN / AZ / RU coverage = 100%
- **Resolver determinism:** role → department → generic → never cross-industry
- **Unknown role protection:** 422 hard-fail, no silent fallback

### H) Dosya Yapısı

```
api/app/
├── Config/
│   └── MaritimeRole.php                            # 18 rol, 20+ alias, department mapping
├── Console/Commands/
│   └── SeedMaritimeRoleTemplates.php               # NEW: 72 role-specific template üretici
├── Http/Controllers/Api/
│   └── FormInterviewController.php                 # MODIFIED: 3-layer industry auto-detect + 422 guard
├── Services/Interview/
│   ├── FormInterviewService.php                    # Maritime template resolution chain
│   └── InterviewTemplateService.php                # getMaritimeTemplate() dept-isolated resolver

talentqx-frontend/src/
├── components/admin/form-interviews/
│   └── DecisionCard.tsx                            # MODIFIED: ResolverBadge system trace
└── lib/
    └── admin-api.ts                                # MODIFIED: industry_code field added

xxx/
├── maritime_templates_v1_compact.sql               # Dept-generic seed (MySQL)
└── maritime_templates_v1_verify.sql                # Verification query
```

### I) Hızlı Referans

```bash
# Role-specific template'leri seed et (idempotent)
php82 artisan maritime:seed-role-templates

# Dry-run (sadece ne yapılacağını göster)
php82 artisan maritime:seed-role-templates --dry-run

# Tek rol seed et
php82 artisan maritime:seed-role-templates --only=deck_captain,engine_chief_engineer

# Template envanteri kontrol
mysql -u talentqx -p talentqx -e "
SELECT position_code, COUNT(*) AS langs
FROM interview_templates
WHERE version='v1' AND is_active=1
GROUP BY position_code ORDER BY position_code;"

# Bozuk maritime interview kontrol
mysql -u talentqx -p talentqx -e "
SELECT COUNT(*) AS broken
FROM form_interviews
WHERE industry_code='maritime' AND template_position_code='__generic__';"
```

---

## SPRINT-5.4: STCW & Certification Engine

---

## SPRINT-5.4: STCW & Certification Engine

**Durum:** ✅ TAMAMLANDI (2026-02-14)

### Özet

Production-grade sertifika doğrulama ve STCW uyumluluk modülü. Denizci sertifikalarını saklar, STCW gereksinimlerine eşler, sona erme/eksiklik kontrol eder, risk bayrakları üretir, matching ve scoring'i besler.

### A) Veritabanı Tabloları

| Tablo | Kayıt | Açıklama |
|-------|-------|----------|
| `certificate_types` | 35 | IMO/STCW sertifika tipleri (7 kategori) |
| `stcw_requirements` | 27 | Rank bazlı zorunlu sertifika eşlemeleri |
| `seafarer_certificates` | - | Denizci sertifikaları + doğrulama durumu |

**Sertifika Kategorileri:** STCW, OFFICER, ENGINE, SPECIAL, MEDICAL, FLAG, MLC

**Zorunlu Sertifikalar (10):** BST, PSSR, SAT, FPFF, EFA, MEDICAL_FITNESS, SEAMANS_BOOK, PASSPORT, MLC_MEDICAL + rank-specific COC'ler

### B) CertificationService

**Dosya:** `api/app/Services/Certification/CertificationService.php`

| Method | Açıklama |
|--------|----------|
| `uploadCertificate()` | Sertifika yükle, hash oluştur, status=pending |
| `verifyCertificate()` | Doğrula (expiry check + authority match) |
| `rejectCertificate()` | Reddet (zorunlu sebep) |
| `getCandidateCertificationStatus()` | Valid/missing/expired/risk flags |
| `checkSTCWCompliance()` | Rank bazlı STCW uyumluluk kontrolü |
| `getCertificationReadyCandidates()` | Talent request için uygun adaylar |
| `getCertificationSummary()` | Decision packet extension |
| `processExpiryCheck()` | Gece job: sona eren sertifika tespiti |
| `getAnalytics()` | Pool geneli sertifika analitiği |

### C) Risk Bayrakları (DecisionEngine için)

| Code | Severity | Açıklama |
|------|----------|----------|
| `RF_CERT_EXPIRED` | high | Sertifika sona ermiş |
| `RF_CERT_MISSING` | medium | Zorunlu sertifika eksik |
| `RF_CERT_FAKE_PATTERN` | critical | Aynı document hash (sahte şüphesi) |
| `RF_MEDICAL_EXPIRED` | critical | Sağlık sertifikası sona ermiş |

### D) API Endpoints

**Candidate-Facing (Public):**

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| POST | `/v1/certificates/upload` | Sertifika yükle |
| GET | `/v1/certificates/{candidateId}` | Sertifika durumu |

**Admin (Authenticated):**

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/v1/admin/certificates` | Liste (filtreler + pagination) |
| POST | `/v1/admin/certificates/{id}/verify` | Doğrula |
| POST | `/v1/admin/certificates/{id}/reject` | Reddet |
| GET | `/v1/admin/certificate-types` | Sertifika tipleri |
| GET | `/v1/admin/candidates/{id}/certification-status` | Aday sertifika durumu |
| GET | `/v1/admin/candidates/{id}/stcw-compliance` | STCW uyumluluk kontrolü |
| GET | `/v1/admin/candidates/{id}/certification-summary` | Decision packet eki |
| GET | `/v1/admin/talent-requests/{id}/certification-ready` | Uyumlu adaylar |
| GET | `/v1/admin/certification-analytics` | Pool analitiği |

### E) Gece Job (Cron)

**Komut:** `php82 artisan certificates:check-expiry --days=90`
**Zamanlama:** Her gece 04:00
**İşlev:** Sona eren sertifikaları expired olarak işaretle, 90 gün içinde sona erecekleri logla

### F) Admin UI (Next.js)

| Route | Açıklama |
|-------|----------|
| `/admin/certifications` | Dashboard: analytics kartları, filtreler, tablo, verify/reject modal |
| `/admin/certifications/candidate?id=UUID` | Aday detay: STCW compliance, valid/missing/expired, risk flags |

### G) Seeders

```bash
php82 artisan db:seed --class=CertificateTypeSeeder --force   # 35 sertifika tipi
php82 artisan db:seed --class=StcwRequirementSeeder --force    # 27 STCW gereksinim
```

### H) Bonus Fix

**Circular Dependency Çözüldü:** `PoolCandidateService` ↔ `FormInterviewService` döngüsel bağımlılığı lazy resolution ile çözüldü. Bu fix aynı zamanda `/api/v1/maritime/ranks` ve `/api/v1/maritime/certificates` endpointlerindeki 500 hatasını da düzeltti.

### I) Dosya Yapısı

```
api/app/
├── Console/Commands/
│   └── CheckCertificateExpiry.php           # NEW: Gece sertifika kontrolü
├── Http/Controllers/Api/
│   ├── CertificateController.php            # NEW: Candidate-facing
│   └── Admin/
│       └── CertificationController.php      # NEW: Admin endpoints
├── Models/
│   ├── CertificateType.php                  # NEW: 35 sertifika tipi
│   ├── StcwRequirement.php                  # NEW: Rank-sertifika eşleme
│   ├── SeafarerCertificate.php              # NEW: Denizci sertifikaları
│   └── PoolCandidate.php                    # MODIFIED: certificates() relation
├── Services/
│   ├── Certification/
│   │   └── CertificationService.php         # NEW: Core service
│   └── PoolCandidateService.php             # MODIFIED: Lazy resolution fix
├── database/
│   ├── migrations/
│   │   ├── 2026_02_14_100000_create_certificate_types_table.php
│   │   ├── 2026_02_14_100001_create_stcw_requirements_table.php
│   │   └── 2026_02_14_100002_create_seafarer_certificates_table.php
│   └── seeders/
│       ├── CertificateTypeSeeder.php        # NEW: 35 IMO/STCW sertifika
│       └── StcwRequirementSeeder.php        # NEW: 27 rank gereksinim
└── routes/
    ├── api.php                              # MODIFIED: 12 yeni endpoint
    └── console.php                          # MODIFIED: Gece job eklendi

talentqx-frontend/src/
├── app/admin/certifications/
│   ├── page.tsx                             # NEW: Admin dashboard
│   └── candidate/page.tsx                   # NEW: Aday sertifika detay
└── lib/
    └── admin-api.ts                         # MODIFIED: Certification types + functions
```

---

## SPRINT-5.3: Maritime Homepage + Global Entry Window

**Durum:** ✅ TAMAMLANDI (2026-02-14)

### A) Marketing Site (talentqx.com/maritime)

**Statik HTML sayfaları:**

| URL | Açıklama |
|-----|----------|
| `/maritime/` | Investor-grade landing page (22KB) |
| `/maritime/privacy.html` | GDPR/KVKK gizlilik politikası |
| `/maritime/terms.html` | Kullanım koşulları |
| `/maritime/retention.html` | Veri saklama politikası |
| `/maritime/contact.html` | İletişim sayfası |

### B) App Maritime Pages (app.talentqx.com)

**i18n desteği (EN/TR/RU) — `?lang=` query param:**

| URL | Açıklama |
|-----|----------|
| `/maritime` | Landing page (i18n) |
| `/maritime?lang=tr` | Türkçe landing |
| `/maritime?lang=ru` | Rusça landing |
| `/maritime/apply` | 3-step kayıt wizard (i18n) |
| `/maritime/apply?lang=tr` | Türkçe kayıt |
| `/maritime/apply?lang=ru` | Rusça kayıt |

### C) i18n Sistemi

**Dosya:** `talentqx-frontend/src/lib/maritime-i18n.ts`
- ~80 çeviri anahtarı (EN/TR/RU)
- `t(key, lang)` fonksiyonu
- `MaritimeLang` type, `SUPPORTED_LANGS` array

### D) Güvenlik Başlıkları

**Dosya:** `nginx/snippets/security-headers.conf`
- HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy
- Tüm location bloklarına dahil edildi (nginx add_header override sorunu çözüldü)
- `app.talentqx.com` için CSP eklendi

### E) Nginx Yapılandırması

- `talentqx.com.conf`: `/maritime` location bloğu + güvenlik başlıkları
- `app.talentqx.com.conf`: Güvenlik başlıkları + CSP
- `maritime.talentqx.com.conf.disabled`: Subdomain geçişine hazır

### F) Dosya Yapısı

```
/www/wwwroot/talentqx.com/maritime/
├── index.html                               # NEW: Marketing homepage
├── privacy.html                             # NEW: GDPR/KVKK privacy
├── terms.html                               # NEW: Terms of service
├── retention.html                           # NEW: Data retention
└── contact.html                             # NEW: Contact page

talentqx-frontend/src/
├── app/maritime/
│   ├── page.tsx                             # REWRITTEN: i18n + OG tags
│   ├── lang-switcher.tsx                    # NEW: EN/TR/RU switcher
│   └── apply/page.tsx                       # REWRITTEN: i18n + field errors + loading
└── lib/
    └── maritime-i18n.ts                     # NEW: i18n dictionary

nginx/
├── talentqx.com.conf                       # MODIFIED: /maritime block + headers
├── app.talentqx.com.conf                   # MODIFIED: Security headers + CSP
└── snippets/security-headers.conf           # NEW: Reusable header snippet
```

---

## SPRINT-5.1: Learning Hardening + Maritime Assessment Binding

**Durum:** ✅ TAMAMLANDI (2026-02-12)

### Yapılan İşler:
- Learning Core Phase-2 hardening
- Maritime industry assessment binding
- Seafarer-specific competency mappings

---

## SPRINT-5.2: Maritime Supply MVP

**Durum:** ✅ TAMAMLANDI (2026-02-13)

### A) Public Maritime Candidate Intake API

**Dosya:** `api/app/Http/Controllers/Api/Maritime/MaritimeCandidateController.php`

**Endpoints:**

| Method | Endpoint | Auth | Açıklama |
|--------|----------|------|----------|
| POST | `/v1/maritime/apply` | Public | Denizci self-registration |
| GET | `/v1/maritime/ranks` | Public | Desteklenen rank'lar |
| GET | `/v1/maritime/certificates` | Public | STCW sertifika listesi |

**Özellikler:**
- `seafarer=true` otomatik set edilir
- `english_assessment_required=true` (denizcilik için zorunlu)
- `video_assessment_required=true`
- GDPR consent handling (EU = GDPR, TR = KVKK)
- Opsiyonel `auto_start_interview=true` ile otomatik interview başlatma

**Seafarer Ranks:**
```
master, chief_officer, second_officer, third_officer, deck_cadet,
bosun, ab_seaman, ordinary_seaman, chief_engineer, second_engineer,
third_engineer, fourth_engineer, engine_cadet, electrician, oiler,
fitter, motorman, cook, chief_cook, steward, chief_steward,
messman, cabin_steward
```

**STCW Certificates:**
```
basic_safety, advanced_firefighting, medical_first_aid, survival_craft,
ship_security_officer, gmdss, tanker_familiarization, oil_tanker,
chemical_tanker, lng_tanker, passenger_ship_safety, crowd_management
```

### B) Maritime Assessment UX - Admin Candidate Pool

**Dosya:** `api/app/Http/Controllers/Api/Admin/CandidatePoolController.php`

**Endpoints:**

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/v1/admin/candidate-pool` | Liste (filters, pagination) |
| GET | `/v1/admin/candidate-pool/{id}` | Detay (with interviews) |
| GET | `/v1/admin/candidate-pool/stats` | Pool istatistikleri |
| GET | `/v1/admin/candidate-pool/action-required` | Aksiyon bekleyenler |

**Action Required Kategorileri:**
- `needs_english_assessment` - English değerlendirmesi bekleniyor
- `needs_video_assessment` - Video değerlendirmesi bekleniyor
- `stale_candidates` - 30+ gün pool'da bekleyen
- `new_unassessed` - Yeni, henüz değerlendirilmemiş

### C) Company-side Consumption Flow Polish

**Dosya:** `api/app/Services/ConsumptionService.php`

**Industry Defaults:**
```php
'maritime' => [
    'english_required' => true,
    'min_english_level' => 'B1',
    'min_score' => 50,
    'meta' => [
        'seafarer_only' => true,
        'video_preferred' => true,
    ],
],
'hospitality' => [
    'english_required' => true,
    'min_english_level' => 'A2',
    'min_score' => 45,
],
```

**Smart Matching Algorithm (findBestMatches):**
- Interview score (40% weight)
- English level match bonus (+15 base, +5 per level above)
- Completed English assessment bonus (+10)
- Video assessment bonus (+10)
- Video completed bonus (+5)
- Freshness bonus (assessed within 30 days)

### D) Investor-grade Analytics

**Dosya:** `api/app/Services/Analytics/FunnelAnalyticsService.php`

**Controller:** `api/app/Http/Controllers/Api/Admin/Analytics/SupplyAnalyticsController.php`

**Endpoints:**

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/v1/admin/analytics/supply/funnel` | Funnel metrikleri |
| GET | `/v1/admin/analytics/supply/channels` | Channel quality (CAC) |
| GET | `/v1/admin/analytics/supply/time-to-hire` | Zaman metrikleri |
| GET | `/v1/admin/analytics/supply/pool-health` | Pool sağlık durumu |

**Funnel Stages:**
```
registrations → started_interviews → completed_interviews →
passed_assessment → added_to_pool → presented → hired
```

**Channel Quality Metrics:**
- `registration_count` - Kayıt sayısı
- `completion_count` - Tamamlayan sayısı
- `pass_count` - Geçen sayısı
- `hire_count` - İşe alınan sayısı
- `completion_rate` - Tamamlama oranı
- `pass_rate` - Geçme oranı
- `quality_score` - Channel kalite skoru (0-100)

### E) Smoke Test Command

**Dosya:** `api/app/Console/Commands/MaritimeSmokeCommand.php`

**Kullanım:**
```bash
# Temel smoke test
php artisan maritime:smoke

# Tam test (ML learning dahil)
php artisan maritime:smoke --full

# API endpoint testi
php artisan maritime:smoke --api

# Test sonrası temizlik
php artisan maritime:smoke --cleanup
```

**Test Aşamaları:**
1. Maritime Registration (POST /maritime/apply)
2. Interview Start
3. Answer Submission (8 questions)
4. Interview Completion
5. Scoring & Decision
6. Feature Extraction
7. Learning Signal
8. Pool Addition
9. Prediction (--full mode)

### F) Frontend Maritime Apply Page

**Dosya:** `talentqx-frontend/src/app/maritime/apply/page.tsx`

**3-Step Registration Wizard:**
1. **Kişisel Bilgiler** - Ad, soyad, email, telefon
2. **Denizcilik Bilgileri** - Rank, İngilizce seviyesi, sertifikalar, deneyim
3. **Kaynak & Onay** - Nereden duydunuz, GDPR consent

**Özellikler:**
- Responsive design (mobile-first)
- Client-side validation
- Progress indicator
- Auto-redirect to interview on success
- Turkish UI (maritime vertical)

### G) Landing Page Updates

**Dosya:** `talentqx-frontend/src/app/maritime/page.tsx`

**Değişiklikler:**
- CTA butonları `/maritime/apply` yönlendirmesi
- "Apply Now" ve "Start Free Assessment" butonları

---

## Tamamlanan Modüller

### 1. Decision Engine (Karar Motoru)

**Durum:** ✅ TAMAMLANDI

**Dosyalar:**
- `api/app/Services/DecisionEngine/DecisionEngineAudit.php` - Ana audit sınıfı
- `api/app/Console/Commands/AuditDecisionEngine.php` - Artisan komutu

**Özellikler:**
- 8 temel yetkinlik (communication, accountability, teamwork, stress_resilience, adaptability, learning_agility, integrity, role_competence)
- Normalize edilmiş ağırlıklar (130 → 100%)
- Evidence-based red flag detection (RF_BLAME, RF_INCONSIST, RF_EGO, RF_AVOID, RF_AGGRESSION, RF_UNSTABLE)
- Configurable skill gates (pozisyon bazlı)
- Risk scoring (WARNING: 1pt, CRITICAL: 3pt)

**Test Sonucu:**
```
strong_hire              → HIRE   (94%)
average_hire             → HOLD   (66%)
risky_skilled            → HOLD   (61%)
high_integrity_low_skill → HOLD   (78%, skill gate fail)
toxic_skilled            → REJECT (14%, red flags)
```

**Komut:** `php artisan decision-engine:audit`

---

### 2. Interview Templates (Mülakat Şablonları)

**Durum:** ✅ TAMAMLANDI

#### 2.1 Veritabanı Standardı

**Tablo:** `interview_templates`

| Kolon | Tip | Açıklama |
|-------|-----|----------|
| id | UUID | Primary key |
| version | VARCHAR(10) | "v1" |
| language | VARCHAR(5) | "tr", ileride "en" |
| position_code | VARCHAR(100) | NOT NULL, "__generic__" for system template |
| title | VARCHAR(200) | Şablon başlığı |
| template_json | LONGTEXT | EXACT JSON string |
| is_active | BOOLEAN | Aktif mi |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**Unique Index:** `itpl_vlp_unique` (version, language, position_code)

**Önemli:** `position_code` artık NULL olamaz. Generic template için `__generic__` kullanılıyor.

#### 2.2 Mevcut Veriler

| ID | position_code | title | is_active |
|----|---------------|-------|-----------|
| 4dd16c2d-... | `__generic__` | Generic Interview Template (Exact JSON) | YES |
| 8c4ce67a-... | `retail_cashier` | Kasiyer Interview Template (Exact JSON) | YES |
| a10bec30-... | `__generic___v0` | Genel Mulakat Sablonu | NO |
| a10bec30-... | `retail_cashier_v0` | Kasiyer Mulakat Sablonu | NO |

#### 2.3 API Endpoints

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/v1/interview-templates` | Tüm aktif şablonları listele |
| GET | `/v1/interview-templates/{version}/{language}/{positionCode?}` | Şablon getir (fallback ile) |
| GET | `/v1/interview-templates/{version}/{language}/{positionCode}/parsed` | Parsed JSON olarak getir |
| GET | `/v1/interview-templates/check/{version}/{language}/{positionCode}` | Şablon var mı kontrol et |

**Fallback Mantığı:**
1. `position_code` verilmezse → `__generic__` döner
2. `position_code` bulunamazsa → `__generic__` fallback

#### 2.4 Service

**Dosya:** `api/app/Services/Interview/InterviewTemplateService.php`

```php
// Kullanım
$service = app(InterviewTemplateService::class);

// Template getir (fallback ile)
$template = $service->getTemplate('v1', 'tr', 'retail_cashier');

// Generic template
$generic = $service->getGenericTemplate('v1', 'tr');

// Template var mı?
$exists = $service->hasPositionTemplate('retail_cashier', 'tr', 'v1');
```

**Önemli:** API'da `$template->template_json` kullanılmalı (exact string). `$template->template` accessor'ı array döner.

#### 2.5 İlgili Dosyalar

```
api/
├── app/
│   ├── Http/Controllers/Api/
│   │   └── InterviewTemplateController.php
│   ├── Models/
│   │   └── InterviewTemplate.php
│   ├── Services/Interview/
│   │   └── InterviewTemplateService.php
│   └── Console/Commands/
│       ├── ListInterviewTemplates.php
│       └── TestInterviewTemplateApi.php
├── database/
│   ├── migrations/
│   │   ├── 2026_02_10_151338_create_interview_templates_table.php
│   │   └── 2026_02_10_154646_alter_interview_templates_generic_position_code_and_unique_index.php
│   └── seeders/
│       ├── InterviewTemplateSeeder.php
│       └── InterviewTemplateExactJsonSeeder.php
└── routes/
    └── api.php (interview-templates routes)
```

#### 2.6 Komutlar

```bash
# Şablonları listele
php artisan interview-templates:list

# Fallback testi ile listele
php artisan interview-templates:list --test-fallback

# API testleri
php artisan interview-templates:test

# Seeder çalıştır (idempotent)
php artisan db:seed --class=InterviewTemplateExactJsonSeeder --force
```

---

## Test Sonuçları

```
╔═══════════════════════════════════════════════════════════════════════════════╗
║               INTERVIEW TEMPLATE API TEST SUITE                              ║
╚═══════════════════════════════════════════════════════════════════════════════╝

TEST 1: retail_cashier → retail_cashier           ✓ PASSED
TEST 2: nonexistent → __generic__ (fallback)      ✓ PASSED
TEST 3: __generic__ directly                       ✓ PASSED
TEST 4: template_json is string                    ✓ PASSED
TEST 5: template_json matches DB exactly           ✓ PASSED (21789 bytes)
TEST 6: JSON valid with expected keys              ✓ PASSED
TEST 7: accessor vs raw field                      ✓ PASSED

TEST SUMMARY: 7/7 passed - All tests PASSED!
```

---

### 3. Frontend (Next.js)

**Durum:** ✅ TAMAMLANDI (MVP UI + Auth)

**Teknolojiler:**
- Next.js 16 (App Router)
- TypeScript
- Tailwind CSS v4
- shadcn/ui
- Bearer Token Auth (Proxy ile)

**Dizin:** `/www/wwwroot/talentqx-frontend/` (Ayrı repo)

#### 3.1 Mimari

```
Browser ──► Next.js /api/* ──► Backend /v1/*
                │
                └── Bearer token server-side eklenir
                    (token client'a asla düşmez)
```

#### 3.2 Proje Yapısı

```
talentqx-frontend/
├── src/
│   ├── app/
│   │   ├── api/                        # Proxy routes
│   │   │   ├── interview-templates/    # Template proxy'leri
│   │   │   └── interviews/             # Interview proxy'leri
│   │   ├── interviews/
│   │   │   ├── new/page.tsx            # Pozisyon seç → mülakat başlat
│   │   │   └── [id]/page.tsx           # Mülakat detay/özet
│   │   └── page.tsx                    # Landing page
│   ├── components/
│   │   ├── ui/                         # shadcn/ui components
│   │   ├── PositionPicker.tsx
│   │   ├── QuestionCard.tsx
│   │   └── InterviewRunner.tsx
│   ├── lib/
│   │   ├── api.ts                      # API client (proxy kullanır)
│   │   ├── backend-proxy.ts            # Proxy helper (server-side)
│   │   └── utils.ts
│   └── types.ts
├── .env.local                          # TALENTQX_API_TOKEN (server-side)
└── .env.example
```

#### 3.2 Sayfalar

| Route | Açıklama |
|-------|----------|
| `/` | Landing page, "Mülakat Başlat" butonu |
| `/interviews/new` | Pozisyon seç → template çek → 8 soru akışı |
| `/interviews/[id]` | Kaydedilmiş mülakat görüntüleme |

#### 3.3 Componentler

**PositionPicker:** Pozisyon seçimi için radio group
- DEFAULT_POSITIONS: `__generic__`, `retail_cashier`

**QuestionCard:** Tek soru gösterimi
- Yetkinlik badge, metod badge
- Cevap textarea
- Olumlu sinyaller listesi
- Önceki/Sonraki navigasyon

**InterviewRunner:** Mülakat akış yönetimi
- Template API'dan soruları çeker
- Cevapları toplar
- Progress bar
- Tamamlama özeti

#### 3.4 API Client

```typescript
// src/lib/api.ts
const api = new ApiClient(API_BASE_URL);

// Template getir
const template = await api.getTemplate('v1', 'tr', 'retail_cashier');

// Soruları parse et
const questions = api.parseTemplateQuestions(template);
```

#### 3.5 Komutlar

```bash
cd frontend

# Development
npm run dev

# Build
npm run build

# Production
npm start
```

#### 3.6 Environment

```env
# .env.local
NEXT_PUBLIC_API_URL=https://talentqx.com/api/v1
```

---

---

### 4. Form Interview Sessions (Backend)

**Durum:** ✅ TAMAMLANDI (MVP Scoring)

**Tablolar:**
- `form_interviews` - Session verileri + template snapshot + skorlar
- `form_interview_answers` - Slot bazlı cevaplar

**Modeller:**
- `FormInterview` - Session modeli
- `FormInterviewAnswer` - Cevap modeli

**Service:**
- `FormInterviewService` - Session oluşturma, cevap kaydetme, MVP scoring

**API Endpoints:**

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| POST | `/v1/form-interviews` | Yeni session oluştur |
| GET | `/v1/form-interviews/{id}` | Session detayı |
| POST | `/v1/form-interviews/{id}/answers` | Cevap ekle/güncelle |
| POST | `/v1/form-interviews/{id}/complete` | Tamamla + skor hesapla |
| GET | `/v1/form-interviews/{id}/score` | Skor/karar al |

**MVP Scoring:**
- Tamamlama bazlı: 8 sorudan kaç tanesi cevaplanmış
- Decision: ≥75 HIRE, ≥50 HOLD, <50 REJECT
- TODO: DecisionEngine entegrasyonu (red flags, skill gates)

**Dosyalar:**
```
api/
├── app/Models/
│   ├── FormInterview.php
│   └── FormInterviewAnswer.php
├── app/Services/Interview/
│   └── FormInterviewService.php
├── app/Http/Controllers/Api/
│   └── FormInterviewController.php
└── database/migrations/
    ├── 2026_02_11_000001_create_form_interviews_table.php
    └── 2026_02_11_000002_create_form_interview_answers_table.php
```

---

### 5. DecisionEngine Entegrasyonu

**Durum:** ✅ TAMAMLANDI

**Dosyalar:**
- `api/app/Services/DecisionEngine/FormInterviewDecisionEngineAdapter.php` - Adapter sınıfı
- `api/app/Console/Commands/TestFormInterviewDecisionEngine.php` - Test komutu

**Özellikler:**
- Heuristic competency scoring (answer length based)
- Evidence-based red flag detection (keyword matching)
- Risk score calculation (integrity, team, stability)
- Weighted base score calculation (8 competency, sum = 100%)
- Per-position skill gate validation
- Final decision: HIRE (≥75), HOLD (≥60), REJECT (<60)

**Red Flags:**
| Code | Name | Severity | Penalty |
|------|------|----------|---------|
| RF_BLAME | Sorumluluk Atma | high | -8 |
| RF_INCONSIST | Tutarsızlık | high | -8 |
| RF_EGO | Ego Baskınlığı | medium | -4 |
| RF_AVOID | Kaçınma / Sorumluluk Reddi | medium | -4 |
| RF_AGGRESSION | Agresif Dil | critical | -15 (auto-reject) |
| RF_UNSTABLE | İstikrarsızlık | medium | -4 |

**Skill Gates:**
| Position | Gate | Action | Safety Critical |
|----------|------|--------|-----------------|
| `__generic__` | 45% | HOLD | No |
| `retail_cashier` | 45% | HOLD | No |
| `sales_associate` | 50% | HOLD | No |
| `customer_support` | 55% | HOLD | No |
| `warehouse_picker` | 45% | HOLD | Yes |
| `software_developer` | 65% | HOLD | No |
| `driver` | 60% | REJECT | Yes |

**Komut:** `php artisan form-interview:test-decision-engine`

---

### 6. Production Hardening & Smoke Tests

**Durum:** ✅ TAMAMLANDI (2026-02-12)

#### 6.1 Prod Smoke Test Sonuçları

| Endpoint | Beklenen | Sonuç |
|----------|----------|-------|
| `POST /v1/form-interviews` | 201 | ✅ 201 |
| `POST /v1/form-interviews/{id}/answers` | 200 | ✅ 200 |
| `POST /v1/form-interviews/{id}/complete` | 200 | ✅ 200 |
| `GET /v1/form-interviews/{id}/score` | 200 | ✅ 200 |

**Örnek Score Response:**
```json
{
  "final_score": 66,
  "decision": "HOLD",
  "decision_reason": "Genel skor 66% (60-74 arasi)",
  "competency_scores": {
    "communication": 70,
    "accountability": 70,
    "teamwork": 70,
    "stress_resilience": 70,
    "adaptability": 70,
    "learning_agility": 70,
    "integrity": 50,
    "role_competence": 70
  },
  "risk_flags": []
}
```

#### 6.2 Güvenlik Testleri

| Test | Beklenen | Sonuç |
|------|----------|-------|
| Token olmadan POST | 401 | ✅ 401 `{"error":"Unauthorized","message":"Missing Authorization header"}` |
| Rate limit (10/min create) | İlk 10: 201, sonrası: 429 | ✅ Çalışıyor |

#### 6.3 Header Sanitization

**Durum:** ✅ Zaten uygulanmış

Frontend proxy route'ları backend response'tan sadece JSON alıp yeni Response oluşturuyor:
- `set-cookie` forward edilmiyor
- `server`, `x-powered-by` forward edilmiyor
- Sadece `Content-Type: application/json` dönüyor

#### 6.4 Yapılan İyileştirmeler

**Create Response'a SHA256 Eklendi:**

```php
// FormInterviewController.php - create() response
return response()->json([
    'id' => $interview->id,
    'status' => $interview->status,
    'version' => $interview->version,
    'language' => $interview->language,
    'position_code' => $interview->position_code,
    'template_position_code' => $interview->template_position_code,
    'template_json_sha256' => $interview->template_json_sha256,  // YENİ
    'created_at' => $interview->created_at,
], 201);
```

**Örnek Response:**
```json
{
  "id": "a1102cb1-e015-4567-9d5c-0d794da08781",
  "template_json_sha256": "452ec6bbe891d89928e006117d63587b0287724db921afc9acdc3d0595b31035",
  ...
}
```

#### 6.5 Rate Limiting Yapılandırması

| Endpoint | Limit | Açıklama |
|----------|-------|----------|
| `POST /form-interviews` | 10/min | Session oluşturma |
| `POST /{id}/answers` | 60/min | Cevap gönderme |
| `POST /{id}/complete` | 30/min | Tamamlama (scoring maliyetli) |
| `GET /{id}`, `GET /{id}/score` | 60/min | Okuma işlemleri |

---

### 7. Strict JSON API & Yeni Pozisyon Şablonları

**Durum:** ✅ TAMAMLANDI (2026-02-12)

#### 7.1 Strict JSON API (302 Riski Giderildi)

**Middleware:** `App\Http\Middleware\ForceJsonResponse`

API route'larında `Accept: application/json` header'ı yoksa bile artık 302 redirect yerine uygun JSON error dönüyor:
- 400: Bad Request
- 401: Unauthorized
- 404: Not Found
- 422: Validation Error
- 429: Too Many Requests

**Dosya:** `api/app/Http/Middleware/ForceJsonResponse.php`

#### 7.2 Yeni Pozisyon Şablonları (TR)

| Position Code | Title | Skill Gate | Category |
|---------------|-------|------------|----------|
| `sales_associate` | Mağaza Satış Temsilcisi | 50% | Perakende |
| `customer_support` | Müşteri Hizmetleri | 55% | Destek |
| `warehouse_picker` | Depo Toplama Elemanı | 45% (safety) | Lojistik |

**Seeder:** `php artisan db:seed --class=NewPositionTemplatesSeeder --force`

---

### 8. Çoklu Dil Desteği (EN)

**Durum:** ✅ TAMAMLANDI (2026-02-12)

#### 8.1 İngilizce Şablonlar

| Language | Position Code | Title |
|----------|---------------|-------|
| en | `__generic__` | Generic Interview Template (English) |
| en | `retail_cashier` | Cashier Interview Template (English) |

**Seeder:** `php artisan db:seed --class=EnglishTemplatesSeeder --force`

#### 8.2 Frontend Dil Seçimi

- URL Parameter: `/interviews/new?lang=en`
- Toggle butonu: Sağ üst köşede TR/EN geçişi
- UI metinleri: Dinamik olarak dile göre değişiyor

**Toplam Aktif Şablonlar:**
```
[en] __generic__          | Generic Interview Template (English)
[en] retail_cashier       | Cashier Interview Template (English)
[tr] __generic__          | Generic Interview Template (Exact JSON)
[tr] customer_support     | Musteri Hizmetleri Temsilcisi Interview Template
[tr] retail_cashier       | Kasiyer Interview Template (Exact JSON)
[tr] sales_associate      | Magaza Satis Temsilcisi Interview Template
[tr] warehouse_picker     | Depo Toplama Elemani Interview Template
```

---

## Sıradaki Adımlar (TODO)

### ~~Öncelik 0: Production Hardening~~ ✅ TAMAMLANDI (2026-02-12)
- [x] Smoke test (create → answers → complete → score akışı)
- [x] Token güvenlik doğrulaması (401 kontrolü)
- [x] Rate limiting doğrulaması (429 kontrolü)
- [x] Frontend proxy header sanitization (zaten uygulanmış)
- [x] Create response'a `template_json_sha256` eklendi
- [x] Strict JSON API (302 riski giderildi)

### ~~Öncelik 1: Yeni Pozisyon Şablonları~~ ✅ TAMAMLANDI (2026-02-12)
- [x] sales_associate (Mağaza Satış Temsilcisi)
- [x] customer_support (Müşteri Hizmetleri)
- [x] warehouse_picker (Depo Toplama Elemanı)
- [x] Her pozisyon için skill_gate değerleri ayarlandı

### ~~Öncelik 2: Çoklu Dil Desteği~~ ✅ TAMAMLANDI (2026-02-12)
- [x] `language='en'` için şablonlar eklendi (__generic__, retail_cashier)
- [x] Frontend'de dil seçimi (?lang=en URL param + toggle)

### ~~Öncelik 3: Admin Panel~~ ✅ TAMAMLANDI (2026-02-12)
- [x] Template CRUD işlemleri (API + UI)
- [x] Template versiyonlama (Clone mekanizması)
- [x] JSON validasyonu (Frontend + Backend)
- [x] Activate/Deactivate toggle
- [x] Admin authentication (Sanctum + platform.admin)

### ~~Öncelik 4: SPRINT-5.1 Learning Hardening~~ ✅ TAMAMLANDI (2026-02-12)
- [x] Learning Core Phase-2 hardening
- [x] Maritime industry assessment binding

### ~~Öncelik 5: SPRINT-5.2 Maritime Supply MVP~~ ✅ TAMAMLANDI (2026-02-13)
- [x] Public Maritime Candidate Intake API (`/v1/maritime/apply`)
- [x] Maritime Assessment UX (Admin Candidate Pool)
- [x] Company-side Consumption Flow (Industry defaults, smart matching)
- [x] Investor-grade Analytics (Funnel, Channel quality, Time-to-hire)
- [x] Smoke test command (`php artisan maritime:smoke`)
- [x] Frontend Maritime Apply page (3-step wizard)

### Öncelik 6: Portal Token Migration (localStorage → httpOnly Cookie) 🔴 TOMORROW

**Durum:** 📋 PLANLANMIŞ (2026-02-18)

**Problem:** Portal login token'ı şu anda localStorage'da tutuluyor. XSS riski.

**Hedef:** httpOnly, Secure, SameSite=Lax cookie'ye geçiş.

**Plan:**
1. **Backend:** Yeni login response'da `Set-Cookie` header'ı ekle (httpOnly, Secure, SameSite=Lax, path=/api)
2. **Backend:** `auth:sanctum` middleware cookie'den de token okuyabilmeli (Laravel Sanctum SPA auth zaten bunu destekliyor)
3. **Frontend:** Login sonrası localStorage'a token yazmayı kaldır, cookie otomatik gönderilir
4. **Frontend:** API client'ı `credentials: 'include'` ile fetch yapacak şekilde güncelle
5. **Frontend:** Logout endpoint'i cookie'yi temizlesin (`Set-Cookie` with `Max-Age=0`)
6. **CSRF:** Sanctum SPA auth için `/sanctum/csrf-cookie` endpoint'i kullanılmalı
7. **Test:** Portal login → cookie set → API call → cookie sent → 200

**Dosyalar (tahmini):**
- `api/app/Http/Controllers/Api/AuthController.php` - Login/logout cookie handling
- `api/config/sanctum.php` - SPA stateful domains ayarı
- `api/config/cors.php` - `supports_credentials: true`
- `talentqx-frontend/src/lib/customer-api.ts` - Cookie-based auth
- `talentqx-frontend/src/app/portal/login/page.tsx` - localStorage kaldır

**Risk:** Mevcut token'lar invalidate olmaz, geriye uyumlu geçiş yapılabilir.

---

### Öncelik 7: Diğer İngilizce Şablonlar
- [ ] EN: sales_associate
- [ ] EN: customer_support
- [ ] EN: warehouse_picker

### Öncelik 7: SPRINT-5.3 (Sonraki)
- [ ] Maritime dashboard (company view)
- [ ] Candidate profile pages
- [ ] Interview replay/review
- [ ] Email notifications

---

### 9. Admin Panel - Template Yönetimi

**Durum:** ✅ TAMAMLANDI (2026-02-12)

#### 9.1 Backend API Endpoints

| Method | Endpoint | Açıklama | Rate Limit |
|--------|----------|----------|------------|
| GET | `/v1/admin/interview-templates` | Liste (filters, pagination) | 120/min |
| GET | `/v1/admin/interview-templates/{id}` | Detay (full JSON) | 120/min |
| POST | `/v1/admin/interview-templates` | Yeni oluştur | 30/min |
| PUT | `/v1/admin/interview-templates/{id}` | Güncelle | 30/min |
| POST | `/v1/admin/interview-templates/{id}/activate` | Aktif/pasif toggle | 30/min |
| POST | `/v1/admin/interview-templates/{id}/clone` | Klonla (yeni versiyon) | 30/min |
| DELETE | `/v1/admin/interview-templates/{id}` | Sil (force=true gerekli) | 30/min |

**Auth:** `auth:sanctum` + `platform.admin` middleware
**Dosya:** `api/app/Http/Controllers/Api/AdminInterviewTemplateController.php`

#### 9.2 Frontend Admin UI

| Route | Açıklama |
|-------|----------|
| `/admin/login` | Admin girişi |
| `/admin/interview-templates` | Template listesi (filter, search) |
| `/admin/interview-templates/new` | Yeni template oluştur |
| `/admin/interview-templates/[id]` | Template düzenle (JSON editor) |

**Özellikler:**
- localStorage tabanlı token yönetimi
- JSON validation (slot, competency, question zorunlu)
- Format/Validate butonları
- Clone modal
- Activate/Deactivate toggle
- Delete confirmation

**Dosyalar:**
```
talentqx-frontend/src/
├── app/admin/
│   ├── login/page.tsx
│   └── interview-templates/
│       ├── page.tsx (liste)
│       ├── new/page.tsx (yeni oluştur)
│       └── [id]/page.tsx (düzenle)
└── lib/
    └── admin-api.ts (API client)
```

#### 9.3 Clone/Version Mekanizması

**Kullanım:**
1. Mevcut template'i seç
2. "Clone" butonuna tıkla
3. `new_version` gir (örn: "v2")
4. Opsiyonel: `new_title` gir
5. Yeni template `is_active=false` olarak oluşturulur
6. Test et, sonra activate et

**API:**
```bash
curl -X POST ".../admin/interview-templates/{id}/clone" \
  -H "Authorization: Bearer TOKEN" \
  -d '{"new_version": "v2", "new_title": "Test Version"}'
```

**Validation:**
- En az biri farklı olmalı: version, language, position_code
- Unique constraint: (version, language, position_code)

---

## Notlar

### JSON Yapısı (Exact Storage)

Template JSON şu yapıda saklanıyor:

```json
{
  "version": "v1",
  "language": "tr",
  "generic_template": {
    "questions": [
      {
        "slot": 1,
        "competency": "communication",
        "question": "...",
        "method": "STAR",
        "scoring_rubric": { "1": "...", "2": "...", ... },
        "positive_signals": [...],
        "red_flag_hooks": [{ "code": "RF_AVOID", "trigger_guidance": "...", "severity": "medium" }]
      },
      // ... 8 soru toplam
    ]
  },
  "positions": [
    {
      "position_code": "retail_cashier",
      "title_tr": "Kasiyer",
      "title_en": "Cashier",
      "category": "Perakende",
      "skill_gate": { "gate": 45, "action": "HOLD", "safety_critical": false },
      "template": { "questions": [...] }
    }
  ]
}
```

### Önemli Kurallar

1. **template_json** her zaman RAW string olarak saklanır ve döndürülür
2. **position_code** artık NULL olamaz, generic için `__generic__` kullan
3. **Unique constraint:** (version, language, position_code)
4. **Fallback:** Pozisyon bulunamazsa `__generic__` döner
5. **Seeder idempotent:** Tekrar çalıştırılabilir, duplicate oluşturmaz

---

## Hızlı Referans

```bash
# Decision Engine
php artisan decision-engine:audit
php artisan form-interview:test-decision-engine

# Interview Templates
php artisan interview-templates:list
php artisan interview-templates:test

# Route listesi
php artisan route:list --path=interview-templates
php artisan route:list --path=form-interviews

# Seeder
php artisan db:seed --class=InterviewTemplateExactJsonSeeder --force

# Frontend (ayrı dizin)
cd /www/wwwroot/talentqx-frontend
npm run dev    # Development (port 3000)
npm run build  # Production build

# Token oluştur
openssl rand -hex 32
# Sonucu hem frontend .env.local hem backend .env'e ekle

# Prod Smoke Test (curl)
export TOKEN="your-api-token"

# 1. Session oluştur
curl -X POST "https://talentqx.com/api/v1/form-interviews" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"version":"v1","language":"tr","position_code":"retail_cashier"}'

# 2. Token olmadan 401 testi
curl -X POST "https://talentqx.com/api/v1/form-interviews" \
  -H "Content-Type: application/json" \
  -d '{"version":"v1","language":"tr","position_code":"retail_cashier"}'
# Beklenen: {"error":"Unauthorized","message":"Missing Authorization header"}

# 3. Rate limit testi (10/min)
for i in {1..12}; do
  curl -s -o /dev/null -w "%{http_code}\n" \
    -X POST "https://talentqx.com/api/v1/form-interviews" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -d '{"version":"v1","language":"tr","position_code":"retail_cashier"}'
done
# Beklenen: ilk 10 → 201, sonrası → 429
```

---

---

## SPRINT-5.2 API Endpoints Summary

### Public Maritime API (No Auth)
```bash
# Denizci kaydı
POST /v1/maritime/apply
{
  "first_name": "Ahmet",
  "last_name": "Yılmaz",
  "email": "ahmet@example.com",
  "phone": "+905551234567",
  "seafarer_rank": "chief_officer",
  "english_level_self": "B2",
  "certificates": ["basic_safety", "gmdss"],
  "experience_years": 8,
  "source_channel": "maritime_fair",
  "gdpr_consent": true,
  "auto_start_interview": true
}

# Rank listesi
GET /v1/maritime/ranks

# Sertifika listesi
GET /v1/maritime/certificates
```

### Admin Candidate Pool API
```bash
# Pool listesi
GET /v1/admin/candidate-pool?industry=maritime&status=in_pool&per_page=20

# Aday detayı
GET /v1/admin/candidate-pool/{id}

# İstatistikler
GET /v1/admin/candidate-pool/stats

# Aksiyon bekleyenler
GET /v1/admin/candidate-pool/action-required?industry=maritime
```

### Admin Analytics API
```bash
# Funnel metrikleri
GET /v1/admin/analytics/supply/funnel?start_date=2026-01-01&end_date=2026-02-13&industry=maritime

# Channel quality
GET /v1/admin/analytics/supply/channels?start_date=2026-01-01&end_date=2026-02-13

# Time to hire
GET /v1/admin/analytics/supply/time-to-hire?start_date=2026-01-01&end_date=2026-02-13

# Pool health
GET /v1/admin/analytics/supply/pool-health?industry=maritime
```

### Admin Talent Request API (Enhanced)
```bash
# Matching candidates (simple)
GET /v1/admin/talent-requests/{id}/matching-candidates?limit=20

# Matching candidates (ranked with scores)
GET /v1/admin/talent-requests/{id}/matching-candidates?ranked=true&limit=10
```

---

## SPRINT-5.2 File Structure

```
api/app/
├── Console/Commands/
│   └── MaritimeSmokeCommand.php          # NEW: maritime:smoke command
├── Http/Controllers/Api/
│   ├── Admin/
│   │   ├── Analytics/
│   │   │   └── SupplyAnalyticsController.php  # NEW: Investor analytics
│   │   ├── CandidatePoolController.php        # NEW: Pool management
│   │   └── TalentRequestController.php        # MODIFIED: Smart matching
│   └── Maritime/
│       └── MaritimeCandidateController.php    # NEW: Public intake API
├── Services/
│   ├── Analytics/
│   │   └── FunnelAnalyticsService.php         # NEW: Funnel metrics
│   └── ConsumptionService.php                 # MODIFIED: Industry defaults
└── routes/
    └── api.php                                # MODIFIED: New routes

talentqx-frontend/src/app/
├── maritime/
│   ├── page.tsx                              # MODIFIED: CTA links
│   └── apply/
│       └── page.tsx                          # NEW: 3-step wizard
```

---

*Bu dosya Claude Code oturumları arasında ilerlemeyi takip etmek için oluşturulmuştur.*
