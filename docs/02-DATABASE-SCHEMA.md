# TalentQX - Database Schema

**Versiyon:** 1.0.0
**Database:** PostgreSQL 16

---

## 1. ER Diagram (Conceptual)

```
┌──────────────┐       ┌──────────────┐       ┌──────────────┐
│    users     │       │   companies  │       │    roles     │
├──────────────┤       ├──────────────┤       ├──────────────┤
│ id           │───┐   │ id           │   ┌───│ id           │
│ company_id   │───┼──►│ name         │   │   │ name         │
│ role_id      │───┼───┼──────────────┘   │   │ permissions  │
│ email        │   │                       │   └──────────────┘
│ password     │   │                       │
└──────────────┘   │                       │
                   │                       │
┌──────────────┐   │   ┌──────────────┐   │
│position_templ│   │   │    jobs      │   │
├──────────────┤   │   ├──────────────┤   │
│ id           │◄──┼───│ template_id  │   │
│ name         │   │   │ company_id   │───┘
│ competencies │   │   │ title        │
│ red_flags    │   │   │ status       │
│ rubrics      │   │   └──────┬───────┘
└──────────────┘   │          │
                   │          ▼
┌──────────────┐   │   ┌──────────────┐
│  candidates  │   │   │  interviews  │
├──────────────┤   │   ├──────────────┤
│ id           │◄──┼───│ candidate_id │
│ job_id       │───┼───│ job_id       │
│ name         │   │   │ status       │
│ email        │   │   │ video_url    │
│ phone        │   │   │ transcript   │
│ cv_url       │   │   └──────┬───────┘
└──────────────┘   │          │
                   │          ▼
                   │   ┌──────────────┐
                   │   │  analyses    │
                   │   ├──────────────┤
                   │   │ interview_id │
                   │   │ competency_  │
                   │   │   scores     │
                   │   │ red_flags    │
                   │   │ decision     │
                   │   └──────────────┘
```

---

## 2. Tablo Detaylari

### 2.1 users
```sql
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id UUID REFERENCES companies(id) ON DELETE CASCADE,
    role_id UUID REFERENCES roles(id),

    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP,
    password VARCHAR(255) NOT NULL,

    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    avatar_url VARCHAR(500),

    is_active BOOLEAN DEFAULT true,
    last_login_at TIMESTAMP,

    remember_token VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_company ON users(company_id);
```

### 2.2 companies
```sql
CREATE TABLE companies (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    logo_url VARCHAR(500),

    address TEXT,
    city VARCHAR(100),
    country VARCHAR(100) DEFAULT 'Turkey',

    subscription_plan VARCHAR(50) DEFAULT 'free',
    subscription_ends_at TIMESTAMP,

    settings JSONB DEFAULT '{}',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_companies_slug ON companies(slug);
```

### 2.3 roles
```sql
CREATE TABLE roles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100),
    description TEXT,

    permissions JSONB DEFAULT '[]',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed data
INSERT INTO roles (name, display_name, permissions) VALUES
('admin', 'Administrator', '["*"]'),
('hr_manager', 'HR Manager', '["jobs.*", "candidates.*", "interviews.*", "reports.*"]'),
('interviewer', 'Interviewer', '["interviews.view", "candidates.view"]'),
('candidate', 'Candidate', '["interviews.take"]');
```

### 2.4 position_templates
```sql
CREATE TABLE position_templates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    category VARCHAR(100),

    -- Yetkinlik seti (JSON array)
    competencies JSONB NOT NULL,
    /*
    [
        {
            "code": "customer_communication",
            "name": "Musteri Iletisimi",
            "weight": 25,
            "description": "Musteri ile etkili iletisim kurabilme"
        }
    ]
    */

    -- Kirmizi bayraklar
    red_flags JSONB NOT NULL,
    /*
    [
        {
            "code": "cash_avoidance",
            "description": "Para ile calismaktan kacinma",
            "severity": "high",
            "keywords": ["para istemiyorum", "kasa olmaz", "nakit zor"]
        }
    ]
    */

    -- Soru uretim kurallari
    question_rules JSONB NOT NULL,
    /*
    {
        "technical_count": 4,
        "behavioral_count": 3,
        "scenario_count": 2,
        "culture_count": 1,
        "sample_questions": [...]
    }
    */

    -- Puanlama rubrigi
    scoring_rubric JSONB NOT NULL,
    /*
    {
        "0": "Cevap yok veya alakasiz",
        "1": "Cok zayif - temel anlayis yok",
        "2": "Zayif - kismi anlayis",
        "3": "Orta - kabul edilebilir",
        "4": "Iyi - beklentileri karsilar",
        "5": "Mukemmel - beklentilerin ustunde"
    }
    */

    -- Kritik davranislar
    critical_behaviors JSONB DEFAULT '[]',

    is_active BOOLEAN DEFAULT true,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_position_templates_slug ON position_templates(slug);
CREATE INDEX idx_position_templates_category ON position_templates(category);
```

### 2.5 jobs
```sql
CREATE TABLE jobs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id UUID NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    template_id UUID REFERENCES position_templates(id),
    created_by UUID REFERENCES users(id),

    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,

    location VARCHAR(255),
    employment_type VARCHAR(50), -- full_time, part_time, contract
    experience_years INTEGER DEFAULT 0,

    -- Template'den override edilebilir
    competencies JSONB,
    red_flags JSONB,
    question_rules JSONB,
    scoring_rubric JSONB,

    -- Mulakat ayarlari
    interview_settings JSONB DEFAULT '{
        "max_duration_minutes": 30,
        "questions_count": 10,
        "allow_video": true,
        "allow_audio_only": true,
        "time_per_question_seconds": 180
    }',

    status VARCHAR(20) DEFAULT 'draft', -- draft, active, paused, closed

    published_at TIMESTAMP,
    closes_at TIMESTAMP,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE(company_id, slug)
);

CREATE INDEX idx_jobs_company ON jobs(company_id);
CREATE INDEX idx_jobs_status ON jobs(status);
CREATE INDEX idx_jobs_template ON jobs(template_id);
```

### 2.6 job_questions
```sql
CREATE TABLE job_questions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    job_id UUID NOT NULL REFERENCES jobs(id) ON DELETE CASCADE,

    question_order INTEGER NOT NULL,
    question_type VARCHAR(50) NOT NULL, -- technical, behavioral, scenario, culture

    question_text TEXT NOT NULL,
    question_text_tts VARCHAR(500), -- Text-to-speech audio URL

    -- Olctugu yetkinlik
    competency_code VARCHAR(100),

    -- Ideal cevap maddeleri
    ideal_answer_points JSONB DEFAULT '[]',
    /*
    [
        "Musteri ile goz temasi kurar",
        "Sakin ve profesyonel kalir",
        "Cozum odakli yaklasir"
    ]
    */

    -- Soru bazli rubrik (opsiyonel override)
    scoring_rubric JSONB,

    time_limit_seconds INTEGER DEFAULT 180,
    is_required BOOLEAN DEFAULT true,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_job_questions_job ON job_questions(job_id);
CREATE INDEX idx_job_questions_order ON job_questions(job_id, question_order);
```

### 2.7 candidates
```sql
CREATE TABLE candidates (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    job_id UUID NOT NULL REFERENCES jobs(id) ON DELETE CASCADE,

    -- Kisisel bilgiler (PII - sifrelenmeli)
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,

    -- Ozgecmis
    cv_url VARCHAR(500),
    cv_parsed_data JSONB,
    cv_match_score DECIMAL(5,2), -- 0-100

    -- Basvuru bilgileri
    source VARCHAR(100), -- website, linkedin, referral, etc.
    referrer_name VARCHAR(255),

    -- Durum
    status VARCHAR(50) DEFAULT 'applied',
    -- applied, interview_pending, interview_completed,
    -- under_review, shortlisted, hired, rejected

    status_changed_at TIMESTAMP,
    status_changed_by UUID REFERENCES users(id),
    status_note TEXT,

    -- KVKK
    consent_given BOOLEAN DEFAULT false,
    consent_version VARCHAR(20),
    consent_given_at TIMESTAMP,
    consent_ip VARCHAR(45),

    -- Internal notes
    internal_notes TEXT,
    tags JSONB DEFAULT '[]',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_candidates_job ON candidates(job_id);
CREATE INDEX idx_candidates_email ON candidates(email);
CREATE INDEX idx_candidates_status ON candidates(status);
CREATE INDEX idx_candidates_created ON candidates(created_at DESC);
```

### 2.8 interviews
```sql
CREATE TABLE interviews (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    candidate_id UUID NOT NULL REFERENCES candidates(id) ON DELETE CASCADE,
    job_id UUID NOT NULL REFERENCES jobs(id),

    -- Gizli token (URL icin)
    access_token VARCHAR(64) NOT NULL UNIQUE,
    token_expires_at TIMESTAMP NOT NULL,

    -- Durum
    status VARCHAR(50) DEFAULT 'pending',
    -- pending, in_progress, completed, expired, cancelled

    started_at TIMESTAMP,
    completed_at TIMESTAMP,

    -- Medya
    video_url VARCHAR(500),
    audio_url VARCHAR(500),
    video_duration_seconds INTEGER,

    -- Teknik bilgiler
    device_info JSONB,
    ip_address VARCHAR(45),
    browser_info VARCHAR(255),

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_interviews_candidate ON interviews(candidate_id);
CREATE INDEX idx_interviews_job ON interviews(job_id);
CREATE INDEX idx_interviews_token ON interviews(access_token);
CREATE INDEX idx_interviews_status ON interviews(status);
```

### 2.9 interview_responses
```sql
CREATE TABLE interview_responses (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    interview_id UUID NOT NULL REFERENCES interviews(id) ON DELETE CASCADE,
    question_id UUID NOT NULL REFERENCES job_questions(id),

    response_order INTEGER NOT NULL,

    -- Video/audio segment
    video_segment_url VARCHAR(500),
    audio_segment_url VARCHAR(500),
    duration_seconds INTEGER,

    -- Transkript
    transcript TEXT,
    transcript_confidence DECIMAL(5,4), -- 0-1
    transcript_language VARCHAR(10) DEFAULT 'tr',

    -- Timestamps
    started_at TIMESTAMP,
    ended_at TIMESTAMP,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_interview_responses_interview ON interview_responses(interview_id);
CREATE INDEX idx_interview_responses_question ON interview_responses(question_id);
```

### 2.10 interview_analyses
```sql
CREATE TABLE interview_analyses (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    interview_id UUID NOT NULL REFERENCES interviews(id) ON DELETE CASCADE,

    -- AI model bilgisi
    ai_model VARCHAR(100),
    ai_model_version VARCHAR(50),
    analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Yetkinlik bazli puanlar
    competency_scores JSONB NOT NULL,
    /*
    {
        "customer_communication": {
            "score": 85,
            "raw_score": 4.25,
            "max_score": 5,
            "evidence": ["Musteri odakli yaklasim gosterdi", "Empati kurdu"],
            "improvement_areas": ["Daha fazla ornek verebilirdi"]
        }
    }
    */

    -- Agirlikli toplam skor
    overall_score DECIMAL(5,2), -- 0-100

    -- Davranis analizi
    behavior_analysis JSONB,
    /*
    {
        "clarity_score": 78,
        "consistency_score": 85,
        "stress_tolerance": 72,
        "communication_style": "professional",
        "confidence_level": "medium-high"
    }
    */

    -- Kirmizi bayrak analizi
    red_flag_analysis JSONB,
    /*
    {
        "flags_detected": true,
        "flags": [
            {
                "code": "cash_avoidance",
                "detected_phrase": "kasada calismayi pek sevmem",
                "severity": "medium",
                "question_id": "uuid",
                "timestamp": "01:23"
            }
        ],
        "overall_risk": "medium"
    }
    */

    -- Kultur uyum skoru
    culture_fit JSONB,
    /*
    {
        "discipline_fit": 80,
        "hygiene_quality_fit": 90,
        "schedule_tempo_fit": 75,
        "overall_fit": 82,
        "notes": "Vardiya esnekligi konusunda cekinceli"
    }
    */

    -- Karar snapshot
    decision_snapshot JSONB,
    /*
    {
        "recommendation": "hire", -- hire, hold, reject
        "confidence_percent": 78,
        "reasons": [
            "Musteri iletisiminde guclu",
            "Hijyen bilinci yuksek",
            "Vardiya esnekliginde kucuk cekince"
        ],
        "suggested_questions": [
            "Vardiya degisiklikleri konusunda esnekligi tekrar sorgulayiniz"
        ]
    }
    */

    -- Raw AI response (debugging icin)
    raw_ai_response JSONB,

    -- Soru bazli analizler
    question_analyses JSONB,
    /*
    [
        {
            "question_id": "uuid",
            "score": 4,
            "competency_code": "customer_communication",
            "analysis": "Aday somut ornek verdi...",
            "positive_points": [...],
            "negative_points": [...]
        }
    ]
    */

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_interview_analyses_interview ON interview_analyses(interview_id);
CREATE INDEX idx_interview_analyses_score ON interview_analyses(overall_score DESC);
```

### 2.11 consent_logs
```sql
CREATE TABLE consent_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    candidate_id UUID NOT NULL REFERENCES candidates(id) ON DELETE CASCADE,

    consent_type VARCHAR(50) NOT NULL, -- kvkk, video_recording, data_processing
    consent_version VARCHAR(20) NOT NULL,
    consent_text TEXT NOT NULL,

    action VARCHAR(20) NOT NULL, -- given, withdrawn

    ip_address VARCHAR(45),
    user_agent TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_consent_logs_candidate ON consent_logs(candidate_id);
CREATE INDEX idx_consent_logs_type ON consent_logs(consent_type);
```

### 2.12 audit_logs
```sql
CREATE TABLE audit_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    user_id UUID REFERENCES users(id),
    company_id UUID REFERENCES companies(id),

    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100),
    entity_id UUID,

    old_values JSONB,
    new_values JSONB,

    ip_address VARCHAR(45),
    user_agent TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_audit_logs_user ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_company ON audit_logs(company_id);
CREATE INDEX idx_audit_logs_entity ON audit_logs(entity_type, entity_id);
CREATE INDEX idx_audit_logs_created ON audit_logs(created_at DESC);
```

### 2.13 notifications
```sql
CREATE TABLE notifications (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,

    type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    body TEXT,

    data JSONB DEFAULT '{}',

    read_at TIMESTAMP,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_unread ON notifications(user_id) WHERE read_at IS NULL;
```

---

## 3. Views

### 3.1 candidate_summary_view
```sql
CREATE OR REPLACE VIEW candidate_summary_view AS
SELECT
    c.id,
    c.job_id,
    c.first_name,
    c.last_name,
    c.email,
    c.status,
    c.cv_match_score,
    c.created_at,
    j.title as job_title,
    j.company_id,
    i.id as interview_id,
    i.status as interview_status,
    i.completed_at as interview_completed_at,
    ia.overall_score,
    ia.decision_snapshot->>'recommendation' as ai_recommendation,
    (ia.decision_snapshot->>'confidence_percent')::INTEGER as ai_confidence,
    (ia.red_flag_analysis->>'flags_detected')::BOOLEAN as has_red_flags
FROM candidates c
LEFT JOIN jobs j ON c.job_id = j.id
LEFT JOIN interviews i ON i.candidate_id = c.id
LEFT JOIN interview_analyses ia ON ia.interview_id = i.id;
```

---

## 4. Functions

### 4.1 Calculate Weighted Score
```sql
CREATE OR REPLACE FUNCTION calculate_weighted_score(
    competency_scores JSONB,
    competencies JSONB
) RETURNS DECIMAL(5,2) AS $$
DECLARE
    total_score DECIMAL(10,4) := 0;
    total_weight INTEGER := 0;
    comp JSONB;
    score_data JSONB;
BEGIN
    FOR comp IN SELECT * FROM jsonb_array_elements(competencies)
    LOOP
        score_data := competency_scores->(comp->>'code');
        IF score_data IS NOT NULL THEN
            total_score := total_score + (
                (score_data->>'score')::DECIMAL * (comp->>'weight')::INTEGER
            );
            total_weight := total_weight + (comp->>'weight')::INTEGER;
        END IF;
    END LOOP;

    IF total_weight > 0 THEN
        RETURN total_score / total_weight;
    ELSE
        RETURN 0;
    END IF;
END;
$$ LANGUAGE plpgsql;
```

---

## 5. Indexes Summary

Performans icin olusturulan tum indexler:

```sql
-- Primary lookups
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_companies_slug ON companies(slug);
CREATE INDEX idx_position_templates_slug ON position_templates(slug);

-- Foreign key lookups
CREATE INDEX idx_users_company ON users(company_id);
CREATE INDEX idx_jobs_company ON jobs(company_id);
CREATE INDEX idx_candidates_job ON candidates(job_id);
CREATE INDEX idx_interviews_candidate ON interviews(candidate_id);

-- Status filters
CREATE INDEX idx_jobs_status ON jobs(status);
CREATE INDEX idx_candidates_status ON candidates(status);
CREATE INDEX idx_interviews_status ON interviews(status);

-- Sorting
CREATE INDEX idx_candidates_created ON candidates(created_at DESC);
CREATE INDEX idx_interview_analyses_score ON interview_analyses(overall_score DESC);

-- Token lookups
CREATE INDEX idx_interviews_token ON interviews(access_token);
```
