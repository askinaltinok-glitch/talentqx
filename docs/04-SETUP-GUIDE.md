# TalentQX - Kurulum Rehberi

---

## 1. Gereksinimler

### Backend
- PHP 8.3+
- Composer 2.x
- PostgreSQL 16+
- Redis 7+
- Node.js 20+ (asset build icin)

### Frontend
- Node.js 20+
- npm 10+ veya pnpm

### Servisler
- OpenAI API Key (GPT-4 + Whisper)
- S3 uyumlu storage (AWS S3, MinIO, DigitalOcean Spaces)
- SMTP (email icin)

---

## 2. Backend Kurulumu

### 2.1 Repo'yu Klonla
```bash
cd /path/to/projects
git clone https://github.com/your-org/talentqx.git
cd talentqx/backend
```

### 2.2 Composer Paketlerini Yukle
```bash
composer install
```

### 2.3 Environment Dosyasi
```bash
cp .env.example .env
php artisan key:generate
```

### 2.4 .env Ayarlari
```env
APP_NAME=TalentQX
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=talentqx
DB_USERNAME=postgres
DB_PASSWORD=secret

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# S3 Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=eu-central-1
AWS_BUCKET=talentqx-storage
AWS_USE_PATH_STYLE_ENDPOINT=false

# MinIO (local development)
# AWS_ENDPOINT=http://localhost:9000
# AWS_USE_PATH_STYLE_ENDPOINT=true

# OpenAI
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4-turbo-preview
OPENAI_WHISPER_MODEL=whisper-1

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@talentqx.com"
MAIL_FROM_NAME="${APP_NAME}"

# JWT
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
SESSION_DOMAIN=localhost
```

### 2.5 Database Kurulumu
```bash
# PostgreSQL'de database olustur
psql -U postgres -c "CREATE DATABASE talentqx;"

# Migrasyonlari calistir
php artisan migrate

# Seed data yukle (pozisyon sablonlari dahil)
php artisan db:seed
```

### 2.6 Storage Link
```bash
php artisan storage:link
```

### 2.7 Queue Worker Baslat
```bash
# Development
php artisan queue:work

# Production (supervisor ile)
# /etc/supervisor/conf.d/talentqx-worker.conf
```

### 2.8 Sunucuyu Baslat
```bash
php artisan serve
# API: http://localhost:8000
```

---

## 3. Frontend Kurulumu

### 3.1 Dizine Gec
```bash
cd talentqx/frontend
```

### 3.2 Bagimliliklari Yukle
```bash
npm install
# veya
pnpm install
```

### 3.3 Environment Dosyasi
```bash
cp .env.example .env.local
```

### 3.4 .env.local Ayarlari
```env
VITE_API_BASE_URL=http://localhost:8000/api/v1
VITE_APP_NAME=TalentQX
VITE_INTERVIEW_DOMAIN=http://localhost:5173
```

### 3.5 Development Server
```bash
npm run dev
# Frontend: http://localhost:5173
```

### 3.6 Production Build
```bash
npm run build
```

---

## 4. Docker ile Kurulum (Onerilir)

### 4.1 docker-compose.yml
```yaml
version: '3.8'

services:
  app:
    build:
      context: ./backend
      dockerfile: Dockerfile
    ports:
      - "8000:8000"
    volumes:
      - ./backend:/var/www/html
    depends_on:
      - postgres
      - redis
    environment:
      - DB_HOST=postgres
      - REDIS_HOST=redis

  postgres:
    image: postgres:16
    ports:
      - "5432:5432"
    environment:
      POSTGRES_DB: talentqx
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secret
    volumes:
      - postgres_data:/var/lib/postgresql/data

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data

  minio:
    image: minio/minio
    ports:
      - "9000:9000"
      - "9001:9001"
    environment:
      MINIO_ROOT_USER: minioadmin
      MINIO_ROOT_PASSWORD: minioadmin
    command: server /data --console-address ":9001"
    volumes:
      - minio_data:/data

  queue:
    build:
      context: ./backend
      dockerfile: Dockerfile
    command: php artisan queue:work --tries=3
    volumes:
      - ./backend:/var/www/html
    depends_on:
      - postgres
      - redis

  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    ports:
      - "5173:5173"
    volumes:
      - ./frontend:/app
      - /app/node_modules

volumes:
  postgres_data:
  redis_data:
  minio_data:
```

### 4.2 Calistir
```bash
docker-compose up -d
docker-compose exec app php artisan migrate --seed
```

---

## 5. Ilk Kurulum Sonrasi

### 5.1 Admin Kullanici Olustur
```bash
php artisan tinker
```

```php
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;

$company = Company::create([
    'name' => 'Demo Sirket',
    'slug' => 'demo-sirket'
]);

User::create([
    'company_id' => $company->id,
    'role_id' => \App\Models\Role::where('name', 'admin')->first()->id,
    'email' => 'admin@talentqx.com',
    'password' => Hash::make('password123'),
    'first_name' => 'Admin',
    'last_name' => 'User',
    'email_verified_at' => now()
]);
```

### 5.2 Pozisyon Sablonlarini Kontrol Et
```bash
php artisan tinker
```

```php
\App\Models\PositionTemplate::pluck('name', 'slug');
// Cikti:
// "tezgahtar-kasiyer" => "Magaza Tezgahtar / Kasiyer"
// "sofor" => "Sofor (Dagitim / Sevkiyat)"
// "depocu" => "Depocu"
// "imalat-personeli" => "Imalat Personeli"
// "uretim-sefi" => "Uretim Sefi"
```

---

## 6. Production Checklist

### Guvenlik
- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] Guclu APP_KEY
- [ ] SSL/TLS aktif
- [ ] CORS ayarlari dogru
- [ ] Rate limiting aktif

### Performance
- [ ] Config cache: `php artisan config:cache`
- [ ] Route cache: `php artisan route:cache`
- [ ] View cache: `php artisan view:cache`
- [ ] OPcache aktif
- [ ] Redis cache aktif

### Monitoring
- [ ] Error tracking (Sentry)
- [ ] Log rotation
- [ ] Queue monitoring
- [ ] Uptime monitoring

### Backup
- [ ] Database gunluk backup
- [ ] S3 storage backup
- [ ] Disaster recovery plani

---

## 7. Troubleshooting

### Queue Calismiyor
```bash
# Queue tablosunu kontrol et
php artisan queue:failed

# Hatalilari tekrar dene
php artisan queue:retry all

# Queue'yu temizle ve yeniden baslat
php artisan queue:flush
php artisan queue:work --tries=3
```

### OpenAI Hatasi
```bash
# API key kontrol
php artisan tinker
>>> config('services.openai.api_key')

# Test cagri
>>> app(\App\Services\AI\OpenAIProvider::class)->test()
```

### Storage Hatasi
```bash
# Izinleri kontrol et
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# S3 baglantisini test et
php artisan tinker
>>> Storage::disk('s3')->put('test.txt', 'hello')
>>> Storage::disk('s3')->get('test.txt')
```

---

## 8. Guncellemeler

### Backend Guncelleme
```bash
cd backend
git pull origin main
composer install --no-dev
php artisan migrate
php artisan config:cache
php artisan route:cache
php artisan queue:restart
```

### Frontend Guncelleme
```bash
cd frontend
git pull origin main
npm install
npm run build
```
