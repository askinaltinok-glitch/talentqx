# B — Maritime Interview Framework v2

## Architecture: Two-Phase Written Interview

The interview is NOT a questionnaire.
It is a structured operational intelligence extraction pipeline.

---

## Phase 1: Maritime Identity Capture (Layer 1)

### Purpose
Extract structured operational data to build `candidate_command_profile`.
This phase runs BEFORE any scenario evaluation.

### Structure: 12 Extraction Fields

```
SECTION A — VESSEL EXPERIENCE (4 questions)

Q1: VESSEL_HISTORY
"List every vessel type you have served on. For each, state:
vessel type, approximate DWT, your rank, duration of service."

→ Extracts: vessel_experience[]
→ Parser: multi-line, semicolon or newline delimited
→ Validation: at least 1 vessel entry required

Q2: BRIDGE_TECHNOLOGY
"Describe the bridge equipment you have operated.
Include: ECDIS manufacturer and version, radar/ARPA systems,
DP class (if any), AIS integration, bridge automation level."

→ Extracts: automation_exposure[]
→ Parser: keyword extraction (ECDIS, Furuno, JRC, Transas, Kongsberg, Wartsila, DP1/2/3)

Q3: CARGO_OPERATIONS
"What cargo types have you handled? Describe your direct
involvement in cargo planning, loading sequences, or
tank/hold preparation."

→ Extracts: cargo_types_handled[]
→ Parser: cargo taxonomy matching

Q4: CREW_MANAGEMENT
"What is the largest crew you have managed directly?
Describe your supervisory role and the department structure."

→ Extracts: crew_scale_min, crew_scale_max, command_history
→ Parser: numeric extraction + rank context


SECTION B — OPERATIONAL CONTEXT (4 questions)

Q5: TRADING_AREAS
"List the trading areas and routes you have operated in.
Include specific seas, straits, canals, and port regions."

→ Extracts: trading_areas[]
→ Parser: geographic taxonomy matching
→ Taxonomy: inland_waterway, coastal_domestic, short_sea,
            ocean_atlantic, ocean_pacific, ocean_indian,
            persian_gulf, north_sea, polar_regions, etc.

Q6: WEATHER_OPERATIONS
"Describe the most severe weather conditions you have
navigated through. Include sea state, wind force,
and the decisions you made."

→ Extracts: weather_severity_exposure (LOW/MEDIUM/HIGH/CRITICAL)
→ Secondary: crisis_response quality indicator

Q7: PORT_OPERATIONS
"How many different ports have you called at in your career?
Describe your experience with congested anchorages,
narrow approach channels, or high-traffic port areas."

→ Extracts: port_density_exposure (LOW/MEDIUM/HIGH)
→ Secondary: navigation_complexity indicator

Q8: INCIDENT_HISTORY
"Describe any maritime incidents, near-misses, or emergency
situations you have been involved in. Include your role
and the outcome."

→ Extracts: incident_history { total, severity_max, types[] }
→ Parser: incident taxonomy matching
→ Red flag detection: underreporting pattern analysis


SECTION C — COMMAND & CERTIFICATION (4 questions)

Q9: COMMAND_EXPERIENCE
"Have you held independent command of a vessel?
If yes: vessel type, DWT, duration, trading area.
If no: describe your highest level of independent
decision-making authority."

→ Extracts: command_history { total_months, highest_rank, independent_command }
→ Critical for command_readiness_level calculation

Q10: CERTIFICATION_STATUS
"List your current certificates: STCW, CoC, tanker endorsements,
DP certificates, flag state endorsements.
For each, state: issuing authority, date of issue, expiry date."

→ Extracts: certifications {}
→ Cross-reference with StcwRequirement model
→ Expired cert detection triggers review flag

Q11: REGULATORY_KNOWLEDGE
"Which flag state regulations are you most familiar with?
Describe your experience with PSC inspections, ISM audits,
or SIRE/CDI vettings."

→ Extracts: regulatory_exposure depth
→ Scored in procedure_discipline capability

Q12: CAREER_TRAJECTORY
"Describe your rank progression. How long at each rank?
What determined your promotions?"

→ Extracts: career_velocity, command_readiness indicators
→ Red flag: stagnation patterns (>5 years same rank without context)
```

### Phase 1 Output

```json
{
  "candidate_command_profile": {
    "vessel_experience": [...],
    "trading_areas": [...],
    "dwt_range": { "min": 12000, "max": 45000 },
    "crew_scale": { "min": 18, "max": 24 },
    "cargo_types_handled": [...],
    "automation_exposure": [...],
    "incident_history": {...},
    "weather_severity_exposure": "HIGH",
    "port_density_exposure": "MEDIUM",
    "command_history": {...},
    "certifications": {...}
  },
  "detected_command_class": {
    "primary": "TANKER",
    "primary_score": 87.5,
    "secondary": "DEEP_SEA",
    "secondary_score": 42.0
  },
  "extraction_confidence": 0.85,
  "extraction_warnings": []
}
```

### Phase 1 → Phase 2 Trigger

Once Phase 1 is complete:
1. Run Command Class Detection algorithm
2. Select Phase 2 template based on detected primary command class
3. If detection confidence < 0.5, flag for manual class assignment

---

## Phase 2: Capability Assessment via Scenario Engine

### Template Selection

```
detected_command_class → scenario_template_set
```

Each command class has its OWN scenario set.
No sharing between classes.

### Structure: 8 Scenario Questions

Each scenario question evaluates ONE primary capability dimension
and may contribute to secondary dimensions.

```
SCENARIO STRUCTURE:

{
  "scenario_id": "TANKER_S01",
  "command_class": "TANKER",
  "primary_capability": "risk_management",
  "secondary_capabilities": ["navigation_complexity", "crisis_response"],
  "difficulty_tier": 2,           // 1-3
  "scenario_context": "...",      // operational situation description
  "decision_prompt": "...",       // what the candidate must decide
  "evaluation_axes": [
    {
      "axis": "risk_identification",
      "weight": 0.4,
      "scoring_rubric": {
        "5": "Identifies all risk vectors including secondary cascading effects",
        "4": "Identifies primary risks and most secondary factors",
        "3": "Identifies obvious risks but misses cascade potential",
        "2": "Partial risk identification, significant gaps",
        "1": "Fails to identify primary risk or misidentifies situation"
      }
    },
    {
      "axis": "decision_quality",
      "weight": 0.35,
      "scoring_rubric": {...}
    },
    {
      "axis": "procedural_compliance",
      "weight": 0.25,
      "scoring_rubric": {...}
    }
  ],
  "critical_omission_flags": [
    "Fails to mention notifying charterer/owner",
    "Ignores environmental risk",
    "Does not reference applicable regulation"
  ],
  "expected_references": [
    "MARPOL Annex I",
    "Company SMS procedures",
    "VDR recording"
  ]
}
```

### Scenario Distribution Per Command Class

Each class gets 8 scenarios, distributed across capabilities:

```
Scenario 1: navigation_complexity     (primary)
Scenario 2: command_scale             (primary)
Scenario 3: technical_depth           (primary)
Scenario 4: risk_management           (primary)
Scenario 5: crew_leadership           (primary)
Scenario 6: automation_dependency     (primary)
Scenario 7: crisis_response           (primary)
Scenario 8: tradeoff_decision         (multi-capability)
```

Scenario 8 is always a multi-axis tradeoff: safety vs. schedule,
environment vs. commercial, crew welfare vs. operational demand.

---

## Interview Flow Architecture

```
┌─────────────────────────────────────────────────┐
│ PHASE 1: IDENTITY CAPTURE (12 questions)        │
│                                                 │
│ Candidate writes operational history            │
│ System extracts structured data                 │
│ Command profile built                           │
│ Command class detected                          │
└───────────────────────┬─────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────┐
│ COMMAND CLASS DETECTION                         │
│                                                 │
│ profile → class scoring → primary + secondary   │
│ Template selection for Phase 2                  │
└───────────────────────┬─────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────┐
│ PHASE 2: SCENARIO ASSESSMENT (8 scenarios)      │
│                                                 │
│ Class-specific operational scenarios            │
│ Each scores one primary capability              │
│ Multi-axis evaluation per scenario              │
│ Crisis and tradeoff scenarios included          │
└───────────────────────┬─────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────┐
│ CAPABILITY SCORING ENGINE                       │
│                                                 │
│ 7 independent capability scores                 │
│ No single combined score                        │
│ Risk profile generated                          │
│ Command readiness level assigned                │
└───────────────────────┬─────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────┐
│ DECISION OUTPUT                                 │
│                                                 │
│ candidate_vessel_fit_map                        │
│ risk_profile                                    │
│ command_readiness_level                         │
│ deployment_suitability                          │
└─────────────────────────────────────────────────┘
```

---

## Database Schema Additions

```sql
-- Phase 1 answers stored separately from Phase 2
CREATE TABLE command_profile_responses (
    id CHAR(36) PRIMARY KEY,
    form_interview_id CHAR(36) NOT NULL,
    question_code VARCHAR(30) NOT NULL,      -- e.g., VESSEL_HISTORY, BRIDGE_TECHNOLOGY
    section CHAR(1) NOT NULL,                -- A, B, C
    answer_text LONGTEXT NOT NULL,
    extracted_data JSON DEFAULT NULL,         -- parsed structured output
    extraction_confidence DECIMAL(3,2) DEFAULT NULL,
    extraction_warnings JSON DEFAULT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (form_interview_id) REFERENCES form_interviews(id),
    UNIQUE KEY uq_interview_question (form_interview_id, question_code)
);

-- Phase 2 scenario responses
CREATE TABLE scenario_responses (
    id CHAR(36) PRIMARY KEY,
    form_interview_id CHAR(36) NOT NULL,
    scenario_id VARCHAR(50) NOT NULL,        -- e.g., TANKER_S01
    command_class VARCHAR(30) NOT NULL,
    primary_capability VARCHAR(30) NOT NULL,
    answer_text LONGTEXT NOT NULL,
    axis_scores JSON DEFAULT NULL,           -- per evaluation axis
    capability_score DECIMAL(3,1) DEFAULT NULL,  -- 0-5
    critical_omissions JSON DEFAULT NULL,
    referenced_regulations JSON DEFAULT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (form_interview_id) REFERENCES form_interviews(id),
    UNIQUE KEY uq_interview_scenario (form_interview_id, scenario_id)
);

-- Updated form_interviews table additions
ALTER TABLE form_interviews ADD COLUMN interview_phase TINYINT DEFAULT 1;
ALTER TABLE form_interviews ADD COLUMN command_class_detected VARCHAR(30) DEFAULT NULL;
ALTER TABLE form_interviews ADD COLUMN command_profile_id CHAR(36) DEFAULT NULL;
ALTER TABLE form_interviews ADD COLUMN phase1_completed_at TIMESTAMP DEFAULT NULL;
ALTER TABLE form_interviews ADD COLUMN phase2_template_selected_at TIMESTAMP DEFAULT NULL;
```

---

## Template Storage Format

```json
{
  "version": "v2",
  "framework": "maritime_command",
  "command_class": "TANKER",
  "language": "en",
  "phase": 2,
  "scenarios": [
    {
      "scenario_id": "TANKER_S01",
      "slot": 1,
      "primary_capability": "navigation_complexity",
      "secondary_capabilities": ["risk_management"],
      "difficulty_tier": 2,
      "context": "You are navigating a laden Aframax tanker...",
      "prompt": "Describe your decision process and actions.",
      "evaluation_axes": [...],
      "critical_omission_flags": [...],
      "expected_references": [...]
    }
  ],
  "metadata": {
    "min_answers_required": 6,
    "time_limit_per_scenario_minutes": 15,
    "total_time_limit_minutes": 120
  }
}
```

Phase 1 template is universal (same 12 questions for all candidates):

```json
{
  "version": "v2",
  "framework": "maritime_command",
  "command_class": null,
  "language": "en",
  "phase": 1,
  "sections": [
    {
      "code": "A",
      "title": "Vessel Experience",
      "questions": [
        {
          "code": "VESSEL_HISTORY",
          "slot": 1,
          "prompt": "List every vessel type you have served on...",
          "extraction_target": "vessel_experience",
          "parser": "multi_entry_vessel",
          "min_length": 50
        }
      ]
    }
  ]
}
```
