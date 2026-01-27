# TalentQX - API Contract

**Base URL:** `https://api.talentqx.com/v1`
**Auth:** Bearer Token (JWT)
**Content-Type:** `application/json`

---

## 1. Authentication

### 1.1 Login
```http
POST /auth/login
```

**Request:**
```json
{
    "email": "hr@company.com",
    "password": "secret123"
}
```

**Response (200):**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": "uuid",
            "email": "hr@company.com",
            "first_name": "Ahmet",
            "last_name": "Yilmaz",
            "role": "hr_manager",
            "company": {
                "id": "uuid",
                "name": "ABC Sirket"
            }
        },
        "token": "eyJ...",
        "refresh_token": "eyJ...",
        "expires_at": "2026-01-28T15:00:00Z"
    }
}
```

### 1.2 Refresh Token
```http
POST /auth/refresh
```

**Request:**
```json
{
    "refresh_token": "eyJ..."
}
```

### 1.3 Logout
```http
POST /auth/logout
Authorization: Bearer {token}
```

### 1.4 Current User
```http
GET /auth/me
Authorization: Bearer {token}
```

---

## 2. Position Templates

### 2.1 List Templates
```http
GET /positions/templates
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": "uuid",
            "name": "Magaza Tezgahtar / Kasiyer",
            "slug": "tezgahtar-kasiyer",
            "category": "retail",
            "description": "Magaza ve kasiyerlik pozisyonlari icin",
            "competencies_count": 5,
            "created_at": "2026-01-01T00:00:00Z"
        }
    ]
}
```

### 2.2 Get Template Detail
```http
GET /positions/templates/{slug}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "success": true,
    "data": {
        "id": "uuid",
        "name": "Magaza Tezgahtar / Kasiyer",
        "slug": "tezgahtar-kasiyer",
        "category": "retail",
        "description": "...",
        "competencies": [
            {
                "code": "customer_communication",
                "name": "Musteri Iletisimi",
                "weight": 25,
                "description": "Musteri ile etkili iletisim kurabilme"
            },
            {
                "code": "attention_speed",
                "name": "Dikkat & Hiz",
                "weight": 25,
                "description": "..."
            }
        ],
        "red_flags": [
            {
                "code": "cash_avoidance",
                "description": "Para ile calismaktan kacinma",
                "severity": "high",
                "keywords": ["para istemiyorum", "kasa olmaz"]
            }
        ],
        "question_rules": {
            "technical_count": 4,
            "behavioral_count": 3,
            "scenario_count": 2,
            "culture_count": 1
        },
        "scoring_rubric": {
            "0": "Cevap yok veya alakasiz",
            "1": "Cok zayif",
            "2": "Zayif",
            "3": "Orta",
            "4": "Iyi",
            "5": "Mukemmel"
        },
        "sample_questions": [
            {
                "type": "scenario",
                "text": "Yogun bir gunde musteri sizden yanlis fiyat etiketi konusunda sikayet ediyor...",
                "competency_code": "customer_communication"
            }
        ]
    }
}
```

---

## 3. Jobs

### 3.1 List Jobs
```http
GET /jobs
Authorization: Bearer {token}
```

**Query Parameters:**
- `status`: draft, active, paused, closed
- `template_id`: UUID
- `page`: integer
- `per_page`: integer (max 100)
- `sort`: created_at, title, candidates_count
- `order`: asc, desc

**Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": "uuid",
            "title": "Tezgahtar - Kadikoy Subesi",
            "slug": "tezgahtar-kadikoy",
            "status": "active",
            "location": "Istanbul, Kadikoy",
            "template": {
                "id": "uuid",
                "name": "Magaza Tezgahtar / Kasiyer"
            },
            "candidates_count": 15,
            "interviews_completed": 8,
            "published_at": "2026-01-15T10:00:00Z",
            "closes_at": "2026-02-15T23:59:59Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total": 45,
        "last_page": 3
    }
}
```

### 3.2 Create Job
```http
POST /jobs
Authorization: Bearer {token}
```

**Request:**
```json
{
    "title": "Tezgahtar - Kadikoy Subesi",
    "template_id": "uuid",
    "description": "Kadikoy subesinde calisacak tezgahtar ariyoruz...",
    "location": "Istanbul, Kadikoy",
    "employment_type": "full_time",
    "experience_years": 1,
    "competencies": null,
    "interview_settings": {
        "max_duration_minutes": 30,
        "questions_count": 10,
        "allow_video": true,
        "allow_audio_only": false,
        "time_per_question_seconds": 180
    },
    "closes_at": "2026-02-15T23:59:59Z"
}
```

**Response (201):**
```json
{
    "success": true,
    "data": {
        "id": "uuid",
        "title": "Tezgahtar - Kadikoy Subesi",
        "slug": "tezgahtar-kadikoy",
        "status": "draft",
        "questions": [],
        "created_at": "2026-01-27T15:00:00Z"
    }
}
```

### 3.3 Get Job Detail
```http
GET /jobs/{id}
Authorization: Bearer {token}
```

### 3.4 Update Job
```http
PUT /jobs/{id}
Authorization: Bearer {token}
```

### 3.5 Delete Job
```http
DELETE /jobs/{id}
Authorization: Bearer {token}
```

### 3.6 Publish Job
```http
POST /jobs/{id}/publish
Authorization: Bearer {token}
```

### 3.7 Generate Questions (AI)
```http
POST /jobs/{id}/generate-questions
Authorization: Bearer {token}
```

**Request:**
```json
{
    "regenerate": false
}
```

**Response (200):**
```json
{
    "success": true,
    "data": {
        "questions": [
            {
                "id": "uuid",
                "question_order": 1,
                "question_type": "technical",
                "question_text": "Musteri kasada beklerken para ustu verirken nelere dikkat edersiniz?",
                "competency_code": "cash_discipline",
                "ideal_answer_points": [
                    "Parayi yuksek sesle sayar",
                    "Musteri onunde kontrol eder",
                    "Fisi verir"
                ],
                "time_limit_seconds": 180
            }
        ],
        "generated_at": "2026-01-27T15:05:00Z"
    }
}
```

### 3.8 Get Job Questions
```http
GET /jobs/{id}/questions
Authorization: Bearer {token}
```

### 3.9 Update Question
```http
PUT /jobs/{jobId}/questions/{questionId}
Authorization: Bearer {token}
```

---

## 4. Candidates

### 4.1 List Candidates
```http
GET /candidates
Authorization: Bearer {token}
```

**Query Parameters:**
- `job_id`: UUID (required or optional based on role)
- `status`: applied, interview_pending, interview_completed, under_review, shortlisted, hired, rejected
- `has_red_flags`: boolean
- `min_score`: integer (0-100)
- `max_score`: integer (0-100)
- `search`: string (name, email)
- `page`, `per_page`, `sort`, `order`

**Response (200):**
```json
{
    "success": true,
    "data": [
        {
            "id": "uuid",
            "first_name": "Mehmet",
            "last_name": "Demir",
            "email": "mehmet@email.com",
            "phone": "+905551234567",
            "status": "interview_completed",
            "cv_match_score": 85.5,
            "job": {
                "id": "uuid",
                "title": "Tezgahtar - Kadikoy"
            },
            "interview": {
                "id": "uuid",
                "status": "completed",
                "completed_at": "2026-01-20T14:30:00Z"
            },
            "analysis": {
                "overall_score": 78.5,
                "recommendation": "hire",
                "confidence_percent": 82,
                "has_red_flags": false
            },
            "created_at": "2026-01-18T10:00:00Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total": 127
    }
}
```

### 4.2 Create Candidate
```http
POST /candidates
Authorization: Bearer {token}
```

**Request:**
```json
{
    "job_id": "uuid",
    "first_name": "Mehmet",
    "last_name": "Demir",
    "email": "mehmet@email.com",
    "phone": "+905551234567",
    "source": "website"
}
```

### 4.3 Get Candidate Detail
```http
GET /candidates/{id}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "success": true,
    "data": {
        "id": "uuid",
        "first_name": "Mehmet",
        "last_name": "Demir",
        "email": "mehmet@email.com",
        "phone": "+905551234567",
        "status": "interview_completed",
        "cv_url": "https://storage.../cv.pdf",
        "cv_match_score": 85.5,
        "cv_parsed_data": {
            "experience_years": 3,
            "education": "Lise",
            "skills": ["Musteri iliskileri", "POS kullanimi"]
        },
        "source": "website",
        "consent_given": true,
        "consent_version": "1.2",
        "job": {
            "id": "uuid",
            "title": "Tezgahtar - Kadikoy"
        },
        "interview": {
            "id": "uuid",
            "status": "completed",
            "video_url": "https://storage.../video.mp4",
            "completed_at": "2026-01-20T14:30:00Z"
        },
        "analysis": {
            "id": "uuid",
            "overall_score": 78.5,
            "competency_scores": {
                "customer_communication": {
                    "score": 85,
                    "evidence": ["..."]
                }
            },
            "red_flag_analysis": {
                "flags_detected": false,
                "flags": []
            },
            "decision_snapshot": {
                "recommendation": "hire",
                "confidence_percent": 82,
                "reasons": ["..."]
            }
        },
        "internal_notes": "...",
        "tags": ["deneyimli", "hemen-baslayabilir"],
        "created_at": "2026-01-18T10:00:00Z"
    }
}
```

### 4.4 Update Candidate Status
```http
PATCH /candidates/{id}/status
Authorization: Bearer {token}
```

**Request:**
```json
{
    "status": "hired",
    "note": "Basarili mulakat, ise alindi"
}
```

### 4.5 Upload CV
```http
POST /candidates/{id}/cv
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

### 4.6 Delete Candidate (KVKK - Right to be forgotten)
```http
DELETE /candidates/{id}
Authorization: Bearer {token}
```

---

## 5. Interviews

### 5.1 Create Interview Link
```http
POST /interviews
Authorization: Bearer {token}
```

**Request:**
```json
{
    "candidate_id": "uuid",
    "expires_in_hours": 72
}
```

**Response (201):**
```json
{
    "success": true,
    "data": {
        "id": "uuid",
        "candidate_id": "uuid",
        "access_token": "abc123xyz",
        "interview_url": "https://interview.talentqx.com/i/abc123xyz",
        "expires_at": "2026-01-30T15:00:00Z",
        "status": "pending"
    }
}
```

### 5.2 Get Interview (Public - Token Based)
```http
GET /interviews/public/{token}
```

**Response (200):**
```json
{
    "success": true,
    "data": {
        "id": "uuid",
        "status": "pending",
        "job": {
            "title": "Tezgahtar - Kadikoy Subesi",
            "company_name": "ABC Market"
        },
        "candidate": {
            "first_name": "Mehmet"
        },
        "settings": {
            "max_duration_minutes": 30,
            "questions_count": 10,
            "allow_video": true,
            "allow_audio_only": false
        },
        "consent_required": true,
        "consent_text": "KVKK kapsaminda..."
    }
}
```

### 5.3 Start Interview (Public)
```http
POST /interviews/public/{token}/start
```

**Request:**
```json
{
    "consent_given": true,
    "device_info": {
        "browser": "Chrome",
        "os": "Windows",
        "has_camera": true,
        "has_microphone": true
    }
}
```

**Response (200):**
```json
{
    "success": true,
    "data": {
        "interview_id": "uuid",
        "questions": [
            {
                "id": "uuid",
                "order": 1,
                "text": "Kendinizden kisaca bahseder misiniz?",
                "time_limit_seconds": 180
            }
        ],
        "upload_url": "https://upload.talentqx.com/...",
        "started_at": "2026-01-27T15:10:00Z"
    }
}
```

### 5.4 Submit Response (Public)
```http
POST /interviews/public/{token}/responses
Content-Type: multipart/form-data
```

**Form Data:**
- `question_id`: UUID
- `video`: File (video/webm)
- `started_at`: ISO timestamp
- `ended_at`: ISO timestamp

### 5.5 Complete Interview (Public)
```http
POST /interviews/public/{token}/complete
```

### 5.6 Get Interview Detail (HR)
```http
GET /interviews/{id}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
    "success": true,
    "data": {
        "id": "uuid",
        "status": "completed",
        "candidate": {
            "id": "uuid",
            "first_name": "Mehmet",
            "last_name": "Demir"
        },
        "job": {
            "id": "uuid",
            "title": "Tezgahtar - Kadikoy"
        },
        "video_url": "https://storage.../video.mp4",
        "duration_seconds": 1250,
        "responses": [
            {
                "id": "uuid",
                "question": {
                    "id": "uuid",
                    "order": 1,
                    "text": "Kendinizden kisaca bahseder misiniz?",
                    "competency_code": "general"
                },
                "video_segment_url": "https://...",
                "transcript": "Merhaba, ben Mehmet. 3 yildir perakende sektorunde...",
                "duration_seconds": 95
            }
        ],
        "analysis": {
            "overall_score": 78.5,
            "competency_scores": {...},
            "red_flag_analysis": {...},
            "decision_snapshot": {...}
        },
        "started_at": "2026-01-20T14:00:00Z",
        "completed_at": "2026-01-20T14:30:00Z"
    }
}
```

### 5.7 Trigger Analysis
```http
POST /interviews/{id}/analyze
Authorization: Bearer {token}
```

**Request:**
```json
{
    "force_reanalyze": false
}
```

**Response (202):**
```json
{
    "success": true,
    "message": "Analysis queued",
    "data": {
        "job_id": "queue-job-uuid",
        "estimated_seconds": 45
    }
}
```

### 5.8 Get Analysis Report
```http
GET /interviews/{id}/report
Authorization: Bearer {token}
```

**Query Parameters:**
- `format`: json, pdf

---

## 6. Dashboard

### 6.1 Dashboard Stats
```http
GET /dashboard/stats
Authorization: Bearer {token}
```

**Query Parameters:**
- `job_id`: UUID (optional)
- `date_from`: ISO date
- `date_to`: ISO date

**Response (200):**
```json
{
    "success": true,
    "data": {
        "total_jobs": 12,
        "active_jobs": 5,
        "total_candidates": 234,
        "interviews_completed": 156,
        "interviews_pending": 45,
        "average_score": 72.5,
        "hire_rate": 0.23,
        "red_flag_rate": 0.15,
        "by_status": {
            "applied": 33,
            "interview_pending": 45,
            "interview_completed": 78,
            "under_review": 34,
            "shortlisted": 22,
            "hired": 15,
            "rejected": 7
        }
    }
}
```

### 6.2 Compare Candidates
```http
POST /dashboard/compare
Authorization: Bearer {token}
```

**Request:**
```json
{
    "candidate_ids": ["uuid1", "uuid2", "uuid3"]
}
```

**Response (200):**
```json
{
    "success": true,
    "data": {
        "candidates": [
            {
                "id": "uuid1",
                "name": "Mehmet Demir",
                "overall_score": 78.5,
                "recommendation": "hire",
                "competency_scores": {
                    "customer_communication": 85,
                    "attention_speed": 72,
                    "cash_discipline": 80,
                    "stress_management": 75,
                    "hygiene_order": 82
                },
                "red_flags_count": 0,
                "culture_fit": 82
            }
        ],
        "comparison": {
            "best_overall": "uuid1",
            "best_by_competency": {
                "customer_communication": "uuid2",
                "attention_speed": "uuid1"
            },
            "recommendation_summary": "Mehmet Demir en yuksek puana sahip ve kirmizi bayrak yok"
        }
    }
}
```

### 6.3 Leaderboard
```http
GET /dashboard/leaderboard
Authorization: Bearer {token}
```

**Query Parameters:**
- `job_id`: UUID (required)
- `limit`: integer (default 10)

---

## 7. Reports

### 7.1 Export Candidates
```http
GET /reports/candidates/export
Authorization: Bearer {token}
```

**Query Parameters:**
- `job_id`: UUID
- `format`: csv, xlsx, pdf
- `include_analysis`: boolean

### 7.2 Interview Report PDF
```http
GET /reports/interviews/{id}/pdf
Authorization: Bearer {token}
```

---

## 8. Settings

### 8.1 Get Company Settings
```http
GET /settings
Authorization: Bearer {token}
```

### 8.2 Update Company Settings
```http
PUT /settings
Authorization: Bearer {token}
```

**Request:**
```json
{
    "default_interview_duration": 30,
    "default_question_time": 180,
    "notification_emails": ["hr@company.com"],
    "branding": {
        "logo_url": "https://...",
        "primary_color": "#2563eb"
    }
}
```

---

## 9. KVKK Endpoints

### 9.1 Get Consent Text
```http
GET /kvkk/consent
```

**Query Parameters:**
- `version`: string (optional, latest if not provided)
- `type`: kvkk, video_recording, data_processing

### 9.2 Export Candidate Data (GDPR/KVKK)
```http
GET /kvkk/export/{candidate_id}
Authorization: Bearer {token}
```

### 9.3 Delete All Candidate Data
```http
DELETE /kvkk/forget/{candidate_id}
Authorization: Bearer {token}
```

---

## 10. Error Responses

### Validation Error (422)
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Validation failed",
        "details": {
            "email": ["Email alani zorunludur"],
            "phone": ["Gecersiz telefon formati"]
        }
    }
}
```

### Not Found (404)
```json
{
    "success": false,
    "error": {
        "code": "NOT_FOUND",
        "message": "Kayit bulunamadi"
    }
}
```

### Unauthorized (401)
```json
{
    "success": false,
    "error": {
        "code": "UNAUTHORIZED",
        "message": "Gecersiz veya suresi dolmus token"
    }
}
```

### Forbidden (403)
```json
{
    "success": false,
    "error": {
        "code": "FORBIDDEN",
        "message": "Bu islemi yapmaya yetkiniz yok"
    }
}
```

### Rate Limit (429)
```json
{
    "success": false,
    "error": {
        "code": "RATE_LIMIT_EXCEEDED",
        "message": "Cok fazla istek gonderdiniz",
        "retry_after": 60
    }
}
```

---

## 11. Webhooks (Optional)

### Events
- `candidate.created`
- `interview.completed`
- `analysis.completed`
- `candidate.status_changed`

### Payload Format
```json
{
    "event": "interview.completed",
    "timestamp": "2026-01-27T15:30:00Z",
    "data": {
        "interview_id": "uuid",
        "candidate_id": "uuid",
        "job_id": "uuid"
    }
}
```
