# C — Capability Matrix Model

## Principle

No single score. No composite index.
Seven independent capability dimensions, each scored and reported separately.

A candidate can score 95% on navigation_complexity and 30% on crew_leadership.
That is valid data. The system does NOT average them.

The consumer of this data (matching engine, customer, fleet manager) decides
which capabilities matter for their specific deployment.

---

## 1. The Seven Capability Dimensions

### 1.1 NAVIGATION_COMPLEXITY
```yaml
code: NAV_COMPLEX
name: Navigation Complexity Handling
description: >
  Ability to navigate in complex environments: restricted waters,
  traffic separation schemes, ice navigation, canal transits,
  high-density port approaches, multi-hazard routing.

evaluated_by:
  phase_1: [trading_areas, port_density_exposure, weather_severity]
  phase_2: scenario targeting this capability

scoring_axes:
  situational_awareness:
    weight: 0.30
    rubric:
      5: "Demonstrates 360-degree awareness; anticipates secondary hazards"
      4: "Strong awareness of primary and most secondary factors"
      3: "Adequate awareness of immediate situation"
      2: "Limited awareness; reacts rather than anticipates"
      1: "Poor situational model; misreads environment"

  passage_planning:
    weight: 0.25
    rubric:
      5: "Comprehensive plan with contingencies, abort points, and alternatives"
      4: "Solid plan with primary contingencies"
      3: "Basic plan covering main route and waypoints"
      2: "Minimal planning; relies on real-time adjustment"
      1: "No evidence of structured planning approach"

  traffic_management:
    weight: 0.25
    rubric:
      5: "Expert COLREG application; manages multiple target scenarios"
      4: "Correct COLREG application in complex situations"
      3: "Standard COLREG compliance in normal traffic"
      2: "Hesitant or delayed COLREG application"
      1: "Incorrect rule application or failure to act"

  environmental_adaptation:
    weight: 0.20
    rubric:
      5: "Proactively adjusts for weather, current, tide, and visibility"
      4: "Adapts well to changing conditions"
      3: "Responds to major environmental factors"
      2: "Slow to adapt; relies on standard procedures only"
      1: "Fails to account for environmental conditions"

command_class_weight_modifiers:
  RIVER: 0.8
  COASTAL: 1.0
  SHORT_SEA: 1.0
  DEEP_SEA: 1.0
  CONTAINER_ULCS: 1.2
  TANKER: 1.0
  LNG: 1.0
  OFFSHORE: 1.3
  PASSENGER: 1.1
```

### 1.2 COMMAND_SCALE
```yaml
code: CMD_SCALE
name: Command Scale Management
description: >
  Ability to manage operations proportional to vessel size,
  crew complement, and organizational complexity.
  A 300m ULCS with 25 crew is different from a 50m tug with 6.

evaluated_by:
  phase_1: [crew_scale, command_history, vessel_experience DWT]
  phase_2: scenario targeting this capability

scoring_axes:
  organizational_structure:
    weight: 0.25
    rubric:
      5: "Designs and maintains clear departmental structure with defined authorities"
      4: "Effective delegation across departments"
      3: "Manages direct reports adequately"
      2: "Struggles with multi-department coordination"
      1: "No evidence of structured organizational approach"

  resource_allocation:
    weight: 0.25
    rubric:
      5: "Optimizes personnel, time, and material across competing demands"
      4: "Effective resource distribution with minor gaps"
      3: "Adequate resource management for routine operations"
      2: "Reactive resource allocation; frequent shortfalls"
      1: "Poor resource management; chronic underutilization or waste"

  decision_authority:
    weight: 0.25
    rubric:
      5: "Exercises master's authority appropriately; knows when to consult vs. decide"
      4: "Good judgment on authority boundaries"
      3: "Exercises authority in standard situations"
      2: "Hesitant to exercise authority or over-delegates"
      1: "Abdicates or overreaches authority consistently"

  operational_tempo:
    weight: 0.25
    rubric:
      5: "Maintains operational pace under pressure without sacrificing safety"
      4: "Manages tempo effectively in most situations"
      3: "Adequate tempo management in normal operations"
      2: "Struggles with pace; either rushes or stalls"
      1: "Cannot manage competing time pressures"

command_class_weight_modifiers:
  RIVER: 0.6
  COASTAL: 0.8
  SHORT_SEA: 0.9
  DEEP_SEA: 1.0
  CONTAINER_ULCS: 1.3
  TANKER: 1.1
  LNG: 1.1
  OFFSHORE: 1.2
  PASSENGER: 1.4
```

### 1.3 TECHNICAL_DEPTH
```yaml
code: TECH_DEPTH
name: Technical Systems Mastery
description: >
  Depth of understanding of vessel systems: propulsion, cargo systems,
  ballast management, stability calculations, safety systems,
  communications, and navigation equipment.

evaluated_by:
  phase_1: [bridge_technology, cargo_operations, automation_exposure]
  phase_2: scenario targeting this capability

scoring_axes:
  systems_knowledge:
    weight: 0.30
    rubric:
      5: "Deep understanding of interrelated ship systems; can troubleshoot cascading failures"
      4: "Strong knowledge across primary systems"
      3: "Adequate knowledge for normal operations"
      2: "Surface-level understanding; dependent on specialists"
      1: "Insufficient technical knowledge for rank held"

  cargo_competence:
    weight: 0.30
    rubric:
      5: "Expert cargo operations for vessel type; handles non-standard situations"
      4: "Competent across normal cargo operations"
      3: "Handles routine cargo work"
      2: "Limited cargo understanding; follows procedures only"
      1: "Dangerous gaps in cargo knowledge"

  stability_awareness:
    weight: 0.20
    rubric:
      5: "Calculates and anticipates stability changes; understands dynamic effects"
      4: "Good stability awareness including free surface effects"
      3: "Basic stability calculations and awareness"
      2: "Relies on loading computer without understanding"
      1: "No meaningful stability awareness"

  equipment_operation:
    weight: 0.20
    rubric:
      5: "Operates all bridge/deck equipment expertly; can use degraded modes"
      4: "Competent with all standard equipment"
      3: "Operates primary equipment adequately"
      2: "Limited equipment proficiency"
      1: "Cannot operate critical equipment independently"

command_class_weight_modifiers:
  RIVER: 0.7
  COASTAL: 0.8
  SHORT_SEA: 0.9
  DEEP_SEA: 1.0
  CONTAINER_ULCS: 1.1
  TANKER: 1.3
  LNG: 1.4
  OFFSHORE: 1.3
  PASSENGER: 0.9
```

### 1.4 RISK_MANAGEMENT
```yaml
code: RISK_MGMT
name: Risk Identification & Mitigation
description: >
  Ability to identify, assess, and mitigate operational risks.
  Includes: risk assessment methodology, safety culture enforcement,
  regulatory compliance, environmental protection, and
  commercial risk balancing.

evaluated_by:
  phase_1: [incident_history, weather_operations, regulatory_knowledge]
  phase_2: scenario targeting this capability

scoring_axes:
  risk_identification:
    weight: 0.30
    rubric:
      5: "Identifies all risk vectors including non-obvious cascading effects"
      4: "Identifies primary and most secondary risks"
      3: "Identifies obvious risks"
      2: "Misses significant risk factors"
      1: "Fails to recognize hazardous situations"

  risk_assessment:
    weight: 0.25
    rubric:
      5: "Quantifies risk; weighs probability vs. consequence; uses formal methods"
      4: "Effective qualitative risk assessment"
      3: "Basic risk ranking ability"
      2: "Binary risk thinking (safe/unsafe)"
      1: "No structured risk assessment approach"

  mitigation_quality:
    weight: 0.25
    rubric:
      5: "Multiple mitigation layers; monitors residual risk; adapts in real-time"
      4: "Effective mitigations with monitoring"
      3: "Standard mitigations applied"
      2: "Generic or incomplete mitigations"
      1: "No meaningful mitigation strategy"

  safety_culture:
    weight: 0.20
    rubric:
      5: "Creates proactive safety culture; empowers crew to stop work"
      4: "Maintains strong safety standards; encourages reporting"
      3: "Complies with safety procedures"
      2: "Safety compliance only when supervised"
      1: "Undermines safety culture or dismisses concerns"

command_class_weight_modifiers:
  RIVER: 0.8
  COASTAL: 0.9
  SHORT_SEA: 1.0
  DEEP_SEA: 1.0
  CONTAINER_ULCS: 1.1
  TANKER: 1.3
  LNG: 1.4
  OFFSHORE: 1.3
  PASSENGER: 1.2
```

### 1.5 CREW_LEADERSHIP
```yaml
code: CREW_LEAD
name: Crew Leadership & Human Element
description: >
  Ability to lead, train, and manage crew in maritime context.
  Includes: BRM/ERM, multicultural crew management, fatigue management,
  conflict resolution, mentoring, and maintaining morale
  during extended deployments.

evaluated_by:
  phase_1: [crew_management, command_experience, career_trajectory]
  phase_2: scenario targeting this capability

scoring_axes:
  bridge_resource_management:
    weight: 0.30
    rubric:
      5: "Exemplary BRM; promotes challenge and response; manages automation complacency"
      4: "Good BRM practices; effective team coordination"
      3: "Basic BRM compliance"
      2: "Autocratic bridge culture or poor coordination"
      1: "Dangerous BRM failures; suppresses crew input"

  crew_development:
    weight: 0.20
    rubric:
      5: "Active mentoring program; develops crew for promotion; knowledge transfer"
      4: "Regular training and constructive feedback"
      3: "Conducts required training"
      2: "Minimal development effort"
      1: "No crew development; knowledge hoarding"

  multicultural_management:
    weight: 0.20
    rubric:
      5: "Adapts leadership style to cultural context; resolves cultural friction"
      4: "Effective with diverse crews; culturally aware"
      3: "Manages multicultural crew without major issues"
      2: "Cultural friction under management"
      1: "Cultural bias affecting crew management"

  fatigue_management:
    weight: 0.15
    rubric:
      5: "Proactive fatigue risk management; monitors work-rest compliance"
      4: "Manages watch schedules with fatigue awareness"
      3: "Complies with MLC rest hour requirements"
      2: "Rest hours sometimes compromised"
      1: "Systematic rest hour violations"

  conflict_resolution:
    weight: 0.15
    rubric:
      5: "Resolves conflicts constructively; prevents escalation"
      4: "Effective mediation in most situations"
      3: "Handles minor conflicts adequately"
      2: "Avoids or escalates conflicts"
      1: "Source of crew conflict"

command_class_weight_modifiers:
  RIVER: 0.6
  COASTAL: 0.7
  SHORT_SEA: 0.8
  DEEP_SEA: 1.0
  CONTAINER_ULCS: 1.0
  TANKER: 1.0
  LNG: 1.0
  OFFSHORE: 1.2
  PASSENGER: 1.4
```

### 1.6 AUTOMATION_DEPENDENCY
```yaml
code: AUTO_DEP
name: Automation Proficiency & Independence
description: >
  Balance between effective use of automation and ability to
  operate without it. Measures: ECDIS proficiency, DP operations,
  manual navigation capability, degraded mode operations,
  and automation complacency awareness.

evaluated_by:
  phase_1: [bridge_technology, automation_exposure]
  phase_2: scenario targeting this capability

scoring_axes:
  automation_proficiency:
    weight: 0.30
    rubric:
      5: "Expert use of all bridge automation; optimizes system capabilities"
      4: "Proficient with standard automation suite"
      3: "Operates automation in normal modes"
      2: "Limited automation use; avoids advanced features"
      1: "Cannot effectively use available automation"

  manual_capability:
    weight: 0.30
    rubric:
      5: "Full manual navigation capability; celestial if applicable; radar plotting"
      4: "Strong manual skills including radar navigation"
      3: "Can navigate manually for short periods"
      2: "Heavily dependent on automation; manual skills atrophied"
      1: "Cannot navigate without ECDIS/GPS"

  degraded_mode_operations:
    weight: 0.25
    rubric:
      5: "Prepared for and practiced in all degraded modes; seamless transition"
      4: "Can operate in most degraded modes"
      3: "Handles single-system failures"
      2: "Panics or stops operations on system failure"
      1: "No degraded mode preparedness"

  complacency_awareness:
    weight: 0.15
    rubric:
      5: "Active monitoring of automation; independent verification routines"
      4: "Regular cross-checks of automated systems"
      3: "Occasional verification"
      2: "Over-trusts automation; minimal verification"
      1: "Complete automation dependency; no independent checks"

command_class_weight_modifiers:
  RIVER: 0.5
  COASTAL: 0.7
  SHORT_SEA: 0.9
  DEEP_SEA: 1.0
  CONTAINER_ULCS: 1.2
  TANKER: 1.0
  LNG: 1.3
  OFFSHORE: 1.4
  PASSENGER: 1.1
```

### 1.7 CRISIS_RESPONSE
```yaml
code: CRISIS_RSP
name: Crisis Response & Emergency Management
description: >
  Ability to manage emergencies: fire, flooding, grounding,
  collision, man overboard, abandon ship, cargo emergency,
  piracy, medical emergency. Includes: decision-making under
  extreme pressure, communication during crisis, and
  post-incident management.

evaluated_by:
  phase_1: [incident_history, weather_operations]
  phase_2: scenario targeting this capability (always highest difficulty)

scoring_axes:
  initial_response:
    weight: 0.30
    rubric:
      5: "Immediate correct response; activates emergency procedures without delay"
      4: "Quick response with correct prioritization"
      3: "Responds adequately; minor delays"
      2: "Delayed or partially incorrect initial response"
      1: "Freezes, panics, or takes incorrect action"

  decision_under_pressure:
    weight: 0.30
    rubric:
      5: "Clear, decisive, and correct decisions under extreme pressure"
      4: "Good decisions with minor hesitation"
      3: "Adequate decisions; some uncertainty"
      2: "Indecisive or delayed; relies on others"
      1: "Poor decisions or decision paralysis"

  communication_in_crisis:
    weight: 0.20
    rubric:
      5: "Clear, structured communication; all parties informed; priorities stated"
      4: "Effective communication with minor gaps"
      3: "Basic communications maintained"
      2: "Confused or incomplete communications"
      1: "Communication breakdown during crisis"

  post_incident:
    weight: 0.20
    rubric:
      5: "Complete post-incident management: investigation, reporting, learning, crew welfare"
      4: "Good post-incident procedures"
      3: "Basic reporting and immediate follow-up"
      2: "Minimal post-incident actions"
      1: "No post-incident management"

command_class_weight_modifiers:
  RIVER: 0.7
  COASTAL: 0.8
  SHORT_SEA: 0.9
  DEEP_SEA: 1.0
  CONTAINER_ULCS: 1.1
  TANKER: 1.3
  LNG: 1.4
  OFFSHORE: 1.3
  PASSENGER: 1.4
```

---

## 2. Scoring Computation

### Per-Capability Score (0-100)

```
capability_score = sum(axis_score * axis_weight) * 20

where:
  axis_score = 0-5 (from rubric evaluation)
  axis_weight = axis-specific weight (sum = 1.0)
  * 20 converts 0-5 to 0-100 scale
```

### Command Class Adjusted Score

```
adjusted_score = raw_score * command_class_weight_modifier

// Clamped to [0, 100]
// Modifier > 1.0 means this capability is MORE critical for the class
// Score can exceed 100 before clamping, representing exceptional fit
```

### Capability Profile Output

```json
{
  "capability_profile": {
    "command_class": "TANKER",
    "scores": {
      "NAV_COMPLEX": {
        "raw": 72,
        "adjusted": 72,
        "modifier": 1.0,
        "axes": {
          "situational_awareness": 4,
          "passage_planning": 3,
          "traffic_management": 4,
          "environmental_adaptation": 3
        }
      },
      "CMD_SCALE": {
        "raw": 65,
        "adjusted": 71,
        "modifier": 1.1,
        "axes": {...}
      },
      "TECH_DEPTH": {
        "raw": 88,
        "adjusted": 100,
        "modifier": 1.3,
        "axes": {...}
      },
      "RISK_MGMT": {
        "raw": 78,
        "adjusted": 100,
        "modifier": 1.3,
        "axes": {...}
      },
      "CREW_LEAD": {
        "raw": 55,
        "adjusted": 55,
        "modifier": 1.0,
        "axes": {...}
      },
      "AUTO_DEP": {
        "raw": 60,
        "adjusted": 60,
        "modifier": 1.0,
        "axes": {...}
      },
      "CRISIS_RSP": {
        "raw": 82,
        "adjusted": 100,
        "modifier": 1.3,
        "axes": {...}
      }
    },
    "scored_at": "2026-02-17T14:30:00Z",
    "scoring_version": "v2"
  }
}
```

---

## 3. Database Schema

```sql
CREATE TABLE capability_scores (
    id CHAR(36) PRIMARY KEY,
    form_interview_id CHAR(36) NOT NULL,
    candidate_id CHAR(36) NOT NULL,
    command_class VARCHAR(30) NOT NULL,

    -- 7 raw scores (0-100)
    nav_complex_raw DECIMAL(5,1) NOT NULL DEFAULT 0,
    cmd_scale_raw DECIMAL(5,1) NOT NULL DEFAULT 0,
    tech_depth_raw DECIMAL(5,1) NOT NULL DEFAULT 0,
    risk_mgmt_raw DECIMAL(5,1) NOT NULL DEFAULT 0,
    crew_lead_raw DECIMAL(5,1) NOT NULL DEFAULT 0,
    auto_dep_raw DECIMAL(5,1) NOT NULL DEFAULT 0,
    crisis_rsp_raw DECIMAL(5,1) NOT NULL DEFAULT 0,

    -- 7 adjusted scores (after command class modifier)
    nav_complex_adj DECIMAL(5,1) NOT NULL DEFAULT 0,
    cmd_scale_adj DECIMAL(5,1) NOT NULL DEFAULT 0,
    tech_depth_adj DECIMAL(5,1) NOT NULL DEFAULT 0,
    risk_mgmt_adj DECIMAL(5,1) NOT NULL DEFAULT 0,
    crew_lead_adj DECIMAL(5,1) NOT NULL DEFAULT 0,
    auto_dep_adj DECIMAL(5,1) NOT NULL DEFAULT 0,
    crisis_rsp_adj DECIMAL(5,1) NOT NULL DEFAULT 0,

    -- Full axis breakdown
    axis_scores JSON NOT NULL,

    -- Metadata
    scoring_version VARCHAR(10) DEFAULT 'v2',
    scored_at TIMESTAMP NOT NULL,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (form_interview_id) REFERENCES form_interviews(id),
    FOREIGN KEY (candidate_id) REFERENCES candidates(id),
    INDEX idx_candidate_class (candidate_id, command_class),
    INDEX idx_interview (form_interview_id)
);
```

---

## 4. No Composite Score Rule

The system NEVER produces a single "total score."

Consumers query capabilities independently:

```sql
-- Find candidates strong in risk management for tanker deployment
SELECT cs.*, ccp.primary_command_class
FROM capability_scores cs
JOIN candidate_command_profiles ccp ON cs.candidate_id = ccp.candidate_id
WHERE cs.risk_mgmt_adj >= 75
  AND cs.tech_depth_adj >= 70
  AND cs.command_class = 'TANKER';
```

The matching engine (Layer 6) uses per-capability thresholds
defined by the requesting company or vessel requirement.
