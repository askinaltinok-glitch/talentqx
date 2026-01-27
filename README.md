# TalentQX - AI Destekli Online Mulakat ve Aday Degerlendirme Platformu

**Proje Sahibi:** Askin Altinok
**Versiyon:** 1.0.0

## Genel Bakis

TalentQX, pozisyona ozel otomatik mulakat sorulari ureten, aday cevaplarini AI ile analiz eden, yetkinlik bazli puanlama yapan ve IK'ya karar destegi veren tam entegre bir platformdur.

### Hedef Pozisyonlar
- Magaza Tezgahtar / Kasiyer
- Sofor (Dagitim / Sevkiyat)
- Depocu
- Imalat Personeli (Pastahane / Uretim)
- Uretim Sefi

## Teknoloji Stack

### Backend
- Laravel 11 (PHP 8.3)
- PostgreSQL 16
- Redis (Queue & Cache)
- S3 Compatible Storage (MinIO/AWS)

### Frontend
- React 18 + TypeScript
- Vite
- Tailwind CSS
- Zustand (State Management)

### AI
- OpenAI GPT-4 (Analiz & Soru Uretimi)
- OpenAI Whisper (Transkripsiyon)

## Hizli Baslangic

### Docker ile (Onerilir)

```bash
# Projeyi klonla
git clone <repo-url>
cd talentqx

# Docker containerlarini baslat
docker-compose up -d

# Migrasyonlari calistir
docker-compose exec app php artisan migrate --seed

# Frontend ve API'ye erisin:
# Frontend: http://localhost:5173
# API: http://localhost:8000
# MinIO Console: http://localhost:9001
```

### Manuel Kurulum

#### Backend
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# .env dosyasini duzenleyin (DB, Redis, OpenAI ayarlari)

php artisan migrate --seed
php artisan serve
```

#### Frontend
```bash
cd frontend
npm install
cp .env.example .env.local
npm run dev
```

## Demo Hesaplar

```
Admin: admin@talentqx.com / password123
HR: hr@demo.com / password123
```

## API Dokumantasyonu

API kontrati icin `docs/03-API-CONTRACT.md` dosyasina bakiniz.

### Temel Endpointler
- `POST /api/v1/auth/login` - Giris
- `GET /api/v1/positions/templates` - Pozisyon sablonlari
- `GET /api/v1/jobs` - Is ilanlari
- `GET /api/v1/candidates` - Adaylar
- `POST /api/v1/interviews` - Mulakat olustur
- `GET /api/v1/dashboard/stats` - Dashboard istatistikleri

## Proje Yapisi

```
talentqx/
├── docs/                    # Dokumantasyon
│   ├── 01-ARCHITECTURE.md
│   ├── 02-DATABASE-SCHEMA.md
│   ├── 03-API-CONTRACT.md
│   └── 04-SETUP-GUIDE.md
│
├── backend/                 # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   ├── Models/
│   │   ├── Services/
│   │   │   ├── AI/
│   │   │   └── Interview/
│   │   └── Jobs/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── routes/api.php
│
├── frontend/                # React SPA
│   ├── src/
│   │   ├── components/
│   │   ├── pages/
│   │   ├── services/
│   │   ├── stores/
│   │   └── types/
│   └── index.html
│
└── docker-compose.yml
```

## Ozellikler

### Pozisyon Sablonlari
- 5 hazir pozisyon sablonu
- Yetkinlik setleri ve agirliklari
- Kirmizi bayrak tanimlari
- Otomatik soru uretim kurallari

### Mulakat Motoru
- AI destekli soru uretimi
- Video/ses kaydi
- Otomatik transkripsiyon
- Token bazli guvenli erisim

### AI Analiz
- Yetkinlik bazli puanlama (0-100)
- Davranis analizi
- Kirmizi bayrak tespiti
- Kultur uyum degerlendirmesi
- Karar onerisi (Hire/Hold/Reject)

### HR Dashboard
- Job bazli aday listesi
- Skor siralama ve filtreleme
- Aday karsilastirma
- Video oynatma + transkript
- PDF rapor export

## KVKK Uyumlulugu

- Acik riza versiyonlama
- Veri saklama suresi yonetimi
- Silme hakki (Right to be forgotten)
- Audit log

## Lisans

Proprietary - Tum haklari saklidir.
