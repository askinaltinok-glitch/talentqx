# TalentQX - Mimari Doküman

**Proje Sahibi:** Askin Altinok
**Versiyon:** 1.0.0
**Tarih:** 2026-01-27

---

## 1. Sistem Genel Bakis

TalentQX, pozisyona ozel otomatik mulakat sorulari ureten, aday cevaplarini AI ile analiz eden, yetkinlik bazli puanlama yapan ve IK'ya karar destegi veren tam entegre bir platformdur.

### Hedef Pozisyonlar
- Magaza Tezgahtar / Kasiyer
- Sofor (Dagitim / Sevkiyat)
- Depocu
- Imalat Personeli (Pastahane / Uretim)
- Uretim Sefi

---

## 2. Teknoloji Stack

### Backend
```
Framework    : Laravel 11 (PHP 8.3)
Authentication: Laravel Sanctum (JWT)
Queue        : Redis
Storage      : S3 Compatible (MinIO/AWS)
Database     : PostgreSQL 16
Cache        : Redis
```

### Frontend
```
Framework    : React 18
Language     : TypeScript
Build Tool   : Vite
UI Library   : Tailwind CSS + Headless UI
State        : Zustand
HTTP Client  : Axios
Video Player : Video.js
```

### AI Katmani
```
LLM Provider      : OpenAI GPT-4 (degistirilebilir)
Transcription     : OpenAI Whisper
Response Format   : JSON Schema Validated
```

---

## 3. Sistem Mimarisi

```
┌─────────────────────────────────────────────────────────────────────┐
│                          FRONTEND (React)                           │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐  │
│  │ Auth     │ │ Jobs     │ │Candidates│ │Interview │ │Dashboard │  │
│  │ Module   │ │ Module   │ │ Module   │ │ Module   │ │ Module   │  │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘  │
└─────────────────────────────────────────────────────────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         API GATEWAY (Laravel)                        │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │                    REST API + JWT Auth                        │   │
│  └──────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
                                   │
           ┌───────────────────────┼───────────────────────┐
           ▼                       ▼                       ▼
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   PostgreSQL    │     │     Redis       │     │   S3 Storage    │
│   - Users       │     │   - Queue       │     │   - Videos      │
│   - Jobs        │     │   - Cache       │     │   - Audios      │
│   - Candidates  │     │   - Sessions    │     │   - Documents   │
│   - Interviews  │     │                 │     │                 │
│   - Analyses    │     │                 │     │                 │
└─────────────────┘     └─────────────────┘     └─────────────────┘
                                   │
                                   ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         AI SERVICES LAYER                            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐               │
│  │ LLM Provider │  │ Transcription│  │ Analysis     │               │
│  │ (OpenAI)     │  │ (Whisper)    │  │ Engine       │               │
│  └──────────────┘  └──────────────┘  └──────────────┘               │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 4. Modul Yapisi

### 4.1 Authentication Module
- JWT token bazli kimlik dogrulama
- Role-based access control (Admin, HR Manager, Interviewer, Candidate)
- Refresh token mekanizmasi
- KVKK riza yonetimi

### 4.2 Position Templates Module
- 5 hazir pozisyon sablonu
- Yetkinlik setleri ve agirliklari
- Kirmizi bayrak tanimlari
- Otomatik soru uretim kurallari
- Puanlama rubrikleri

### 4.3 Jobs Module
- Is ilani olusturma
- Pozisyon sablonundan kalitim
- Ozel yetkinlik ayarlari
- Mulakat konfigurasyonu

### 4.4 Candidates Module
- Aday kayit ve basvuru
- Ozgecmis yukleme ve parse
- Durumn takibi
- KVKK riza kaydi

### 4.5 Interview Module
- Token bazli mulakat linki
- Video/ses kaydi
- Gercek zamanli transkripsiyon
- Soru navigasyonu

### 4.6 Analysis Module
- Cevap transkripsiyon
- AI yetkinlik analizi
- Kirmizi bayrak tespiti
- Kultur uyum degerlendirmesi
- Karar onerileri

### 4.7 Dashboard Module
- Job bazli aday listesi
- Skor siralama
- Karsilastirma ekrani
- Rapor ve export

---

## 5. Guvenlik Mimarisi

### 5.1 Kimlik Dogrulama
```
- Laravel Sanctum JWT
- Token expiry: 24 saat
- Refresh token: 7 gun
- Rate limiting: 60 req/min
```

### 5.2 Veri Guvenligi
```
- PII alanlari AES-256 sifreleme
- Video/audio sifreleme at-rest
- SSL/TLS zorunlu
- CORS politikasi
```

### 5.3 KVKK Uyumlulugu
```
- Acik riza versiyonlama
- Veri saklama suresi (retention)
- Silme hakki (right to be forgotten)
- Veri tasima hakki
- Audit log
```

---

## 6. Queue ve Async Islemler

### 6.1 Queue Jobs
```php
// Yuksek oncelikli
- TranscribeAudioJob
- AnalyzeInterviewJob

// Normal oncelikli
- GenerateReportJob
- SendNotificationJob

// Dusuk oncelikli
- DataRetentionJob
- CleanupExpiredTokensJob
```

### 6.2 Event-Driven Architecture
```
InterviewCompleted -> TranscribeAudioJob -> AnalyzeInterviewJob -> NotifyHRJob
```

---

## 7. Olceklenebilirlik

### 7.1 Horizontal Scaling
- Stateless API tasarimi
- Redis session store
- S3 distributed storage
- Load balancer ready

### 7.2 Performance Hedefleri
```
- API response time: < 200ms (p95)
- Video upload: < 30s (100MB)
- AI analysis: < 60s
- Dashboard load: < 2s
```

---

## 8. Monitoring ve Logging

### 8.1 Application Logs
```
- Laravel Log (daily rotation)
- Error tracking (Sentry ready)
- Audit logs (database)
```

### 8.2 Metrics
```
- API response times
- Queue job durations
- AI provider latency
- Storage usage
```

---

## 9. Deployment Yapisi

### Development
```
- Docker Compose
- Local PostgreSQL
- MinIO (S3 compatible)
- Redis
```

### Production
```
- Kubernetes ready
- Managed PostgreSQL
- AWS S3 / DigitalOcean Spaces
- Redis Cluster
```

---

## 10. Dizin Yapisi

```
talentqx/
├── docs/
│   ├── 01-ARCHITECTURE.md
│   ├── 02-DATABASE-SCHEMA.md
│   ├── 03-API-CONTRACT.md
│   └── 04-SETUP-GUIDE.md
│
├── backend/
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   ├── Middleware/
│   │   │   └── Requests/
│   │   ├── Models/
│   │   ├── Services/
│   │   │   ├── AI/
│   │   │   │   ├── LLMProviderInterface.php
│   │   │   │   ├── OpenAIProvider.php
│   │   │   │   └── TranscriptionService.php
│   │   │   ├── Interview/
│   │   │   │   ├── QuestionGenerator.php
│   │   │   │   └── AnalysisEngine.php
│   │   │   └── Report/
│   │   ├── Jobs/
│   │   └── Events/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   └── config/
│
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   ├── pages/
│   │   ├── services/
│   │   ├── stores/
│   │   └── types/
│   └── public/
│
└── docker/
    ├── docker-compose.yml
    ├── nginx/
    └── php/
```
