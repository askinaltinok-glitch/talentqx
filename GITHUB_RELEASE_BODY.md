# TalentQX Workforce Assessment MVP

First public release of TalentQX - an AI-powered workforce assessment platform for retail chains, franchises, and production facilities.

## 🎉 Highlights

### Core Modules
- **Hiring Assessment** - AI-powered candidate evaluation with video interviews
- **Workforce Assessment** - Scenario-based employee assessments with competency scoring
- **Sales Console** - Mini CRM for lead management and sales pipeline

### Assessment Templates
- 🛒 **Tezgahtar / Kasiyer** (Cashier) - Customer service, integrity, hygiene focus
- 🏭 **Üretim Personeli** (Production Worker) - Safety, quality, discipline focus
- 👔 **Mağaza Müdürü** (Store Manager) - Leadership, team management, business acumen

### Key Features
- 10 scenario-based questions per role with 0-5 scoring rubrics
- Weighted competency scoring (6 competencies per role)
- Red flag detection with severity levels (Critical/High/Medium/Low)
- AI-generated manager summaries and hiring recommendations
- KVKK (Turkish GDPR) compliance built-in

## 📊 Technical Stack
- **Backend:** Laravel 11 (PHP 8.3)
- **Frontend:** React 18 + TypeScript + Vite + Tailwind CSS
- **AI:** OpenAI GPT-4 & Whisper
- **Database:** MySQL / PostgreSQL

## 🚀 Quick Start
```bash
git clone https://github.com/askinaltinok-glitch/talentqx.git
cd talentqx

# Backend
cd backend && composer install && cp .env.example .env
php artisan key:generate && php artisan migrate --seed && php artisan serve

# Frontend
cd frontend && npm install && npm run dev
```

## 📋 Documentation
- [README.md](README.md) - Product overview and setup
- [RELEASE_NOTES.md](RELEASE_NOTES.md) - Detailed release notes
- [ROADMAP.md](ROADMAP.md) - v1.0 roadmap

## 🔜 Coming in v1.0
- PDF assessment reports
- Franchise center dashboard
- Advanced analytics & benchmarking
- Email/SMS notifications

---

**Full Changelog:** See [RELEASE_NOTES.md](RELEASE_NOTES.md)
