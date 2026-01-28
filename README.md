# TalentQX

**AI-Powered Workforce Assessment Platform for Retail & Production**

[![Version](https://img.shields.io/badge/version-0.9.0--mvp-blue.svg)](https://github.com/askinaltinok-glitch/talentqx/releases)
[![License](https://img.shields.io/badge/license-Proprietary-red.svg)](LICENSE)

---

## What is TalentQX?

TalentQX is an AI-powered platform that helps retail chains, franchises, and production facilities **hire better** and **develop their workforce** through scientific assessment methods.

Instead of gut feelings and unstructured interviews, TalentQX provides:
- **Standardized assessments** tailored to specific roles
- **AI-powered evaluation** with objective scoring
- **Risk detection** to prevent costly bad hires
- **Development insights** to grow your existing team

### The Problem We Solve

| Challenge | TalentQX Solution |
|-----------|-------------------|
| High turnover in retail/production | Pre-hire assessments identify flight risks |
| Inconsistent interview quality | Standardized scenario-based questions |
| Subjective hiring decisions | AI scoring with evidence-based recommendations |
| No visibility into workforce quality | Continuous assessment and analytics |
| Slow hiring process | Self-service assessments, instant results |

---

## Who Is It For?

### 🏪 Retail Chains & Franchises
- Multi-location retail operations
- Fast-food and restaurant chains
- Grocery and supermarket chains
- Fashion and apparel retailers

### 🏭 Production & Manufacturing
- Food production facilities
- Manufacturing plants
- Warehouse operations
- Logistics centers

### 👥 Target Roles
| Role | Assessment Focus |
|------|------------------|
| **Cashier / Sales Clerk** | Customer service, integrity, hygiene, stress handling |
| **Production Worker** | Safety awareness, quality focus, discipline, teamwork |
| **Store Manager** | Leadership, team management, business acumen, ethics |
| **Warehouse Worker** | Accuracy, physical endurance, safety compliance |
| **Delivery Driver** | Responsibility, time management, customer interaction |

---

## Core Modules

### 1. 🎯 Hiring Assessment
**Pre-hire evaluation for new candidates**

- AI-generated interview questions based on role
- Video/audio response recording
- Automatic transcription and analysis
- Competency scoring (0-100)
- Red flag detection
- Hire / Hold / Reject recommendations

### 2. 📊 Workforce Assessment
**Continuous evaluation for existing employees**

- 10 scenario-based questions per role
- Self-service assessment (mobile-friendly)
- Competency mapping with weighted scores
- Risk level identification
- Development plan generation
- Promotion readiness evaluation

**Assessment Templates:**
- Tezgahtar / Kasiyer (Cashier)
- Üretim Personeli (Production Worker)
- Mağaza Müdürü (Store Manager)

### 3. 📈 Sales Console (Mini CRM)
**Lead management for B2B sales**

- Demo request tracking
- Pipeline management (New → Contact → Demo → Pilot → Won/Lost)
- Activity logging (calls, meetings, notes)
- Sales script checklist
- Lead scoring algorithm
- Follow-up reminders

---

## Demo Flow

### Step 1: Create Job Position
```
HR Panel → Jobs → Create New Job
↓
Select template (e.g., "Tezgahtar")
↓
AI generates role-specific questions
↓
Publish job
```

### Step 2: Candidate Assessment
```
Add candidate → Send assessment link
↓
Candidate receives unique token URL
↓
Candidate answers scenario questions (video/text)
↓
AI analyzes responses automatically
```

### Step 3: Review Results
```
HR Panel → Candidates → View Results
↓
Overall score + Competency breakdown
↓
Red flags highlighted
↓
AI recommendation: Hire / Hold / Reject
```

### Step 4: Workforce Assessment
```
HR Panel → Employees → Create Assessment
↓
Employee receives mobile-friendly link
↓
Completes 10 scenario questions
↓
Manager sees: scores, risks, development areas
```

---

## Screenshots

| Dashboard | Assessment Results |
|-----------|-------------------|
| Pipeline overview, stats | Competency radar, recommendations |

| Lead Management | Employee Assessment |
|-----------------|---------------------|
| Sales pipeline, activities | Risk levels, development plan |

---

## Local Setup

### Prerequisites
- PHP 8.2+ with Composer
- Node.js 18+ with npm
- MySQL 8.0 or PostgreSQL 15+
- Redis (optional, for queues)

### Quick Start

```bash
# Clone repository
git clone https://github.com/askinaltinok-glitch/talentqx.git
cd talentqx

# Backend setup
cd backend
composer install
cp .env.example .env
php artisan key:generate
# Configure .env (database, OpenAI API key)
php artisan migrate --seed
php artisan serve

# Frontend setup (new terminal)
cd frontend
npm install
cp .env.example .env.local
npm run dev
```

### Access Points
| Service | URL |
|---------|-----|
| Frontend | http://localhost:5173 |
| API | http://localhost:8000 |

### Demo Credentials
```
Admin:  admin@talentqx.com / password123
HR:     hr@demo.com / password123
```

### Docker Setup (Alternative)
```bash
docker-compose up -d
docker-compose exec app php artisan migrate --seed
```

---

## Pilot Usage Scenario

### Week 1: Setup & Training
1. Configure company account
2. Import employee list (Excel/CSV)
3. HR team training (1 hour)

### Week 2-3: Assessment Rollout
1. Send assessment links to pilot group (50-100 employees)
2. Employees complete self-assessment (15-20 min each)
3. AI processes and scores responses

### Week 4: Analysis & Decision
1. Review assessment results dashboard
2. Identify high-risk employees
3. Generate development plans
4. Present ROI report to management

### Success Metrics
| Metric | Target |
|--------|--------|
| Completion rate | > 85% |
| Assessment time | < 20 min |
| Risk identification accuracy | > 80% |
| Manager satisfaction | > 4/5 |

---

## Technology Stack

| Layer | Technology |
|-------|------------|
| **Backend** | Laravel 11 (PHP 8.3) |
| **Frontend** | React 18 + TypeScript + Vite |
| **Styling** | Tailwind CSS |
| **Database** | MySQL / PostgreSQL |
| **AI Engine** | OpenAI GPT-4 |
| **Transcription** | OpenAI Whisper |
| **State Management** | Zustand |

---

## KVKK Compliance (Turkish GDPR)

- ✅ Explicit consent versioning
- ✅ Data retention policies
- ✅ Right to be forgotten
- ✅ Data export (portability)
- ✅ Audit logging
- ✅ Anonymization for expired data

---

## Support & Contact

**Product Owner:** Aşkın Altınok

For pilot inquiries, demos, or support:
- 📧 Email: [contact email]
- 🌐 Website: [website URL]

---

## License

Proprietary Software - All rights reserved.

Unauthorized copying, modification, or distribution is prohibited.
