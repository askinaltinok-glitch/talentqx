# E — Scenario Bank Structure

## Principle

No behavioral HR questions.
Every question presents an operational situation requiring a decision.

The candidate must demonstrate:
- What they would do
- Why they would do it
- What regulations/procedures apply
- What they would communicate and to whom
- What risks they identified
- What alternatives they considered

---

## 1. Scenario Taxonomy

### 1.1 Scenario Domains (11 domains)

```
DOMAIN                    CODE          APPLICABLE CLASSES
─────────────────────────────────────────────────────────
Port Operations           PORT_OPS      ALL
Navigation Hazard         NAV_HAZ       ALL
Weather Decision          WX_DEC        ALL except RIVER
Engine/Machinery          ENG_MACH      ALL
Cargo Emergency           CARGO_EMG     TANKER, LNG, CONTAINER_ULCS, DEEP_SEA
Crew Emergency            CREW_EMG      ALL
Collision Avoidance       COLAV         ALL
Environmental Compliance  ENV_COMP      TANKER, LNG, OFFSHORE
Commercial Pressure       COMM_PRESS    ALL
Regulatory Encounter      REG_ENC       ALL
Multi-Factor Tradeoff     TRADEOFF      ALL (always Scenario 8)
```

### 1.2 Difficulty Tiers

```
TIER 1 — Standard Operations
  Single-factor decision
  Clear correct answer exists
  Standard procedures apply
  Time pressure: LOW

TIER 2 — Complex Operations
  Multi-factor decision
  Multiple valid approaches
  Judgment required beyond procedures
  Time pressure: MEDIUM

TIER 3 — Crisis / Extreme
  Cascading failure scenario
  No single correct answer
  Tradeoffs between safety/commercial/environmental
  Time pressure: HIGH
```

### 1.3 Distribution Per Interview

Each command class interview (Phase 2) has 8 scenarios:

```
Scenario 1: capability=NAV_COMPLEX   tier=2   domain=varies_by_class
Scenario 2: capability=CMD_SCALE     tier=2   domain=varies_by_class
Scenario 3: capability=TECH_DEPTH    tier=2   domain=varies_by_class
Scenario 4: capability=RISK_MGMT     tier=2   domain=varies_by_class
Scenario 5: capability=CREW_LEAD     tier=2   domain=CREW_EMG
Scenario 6: capability=AUTO_DEP      tier=2   domain=varies_by_class
Scenario 7: capability=CRISIS_RSP    tier=3   domain=varies_by_class
Scenario 8: TRADEOFF                 tier=3   domain=TRADEOFF
```

---

## 2. Scenario Structure Schema

```json
{
  "scenario_id": "TANKER_S04_RISK_001",
  "command_class": "TANKER",
  "slot": 4,
  "domain": "CARGO_EMG",
  "primary_capability": "RISK_MGMT",
  "secondary_capabilities": ["TECH_DEPTH", "CRISIS_RSP"],
  "difficulty_tier": 2,
  "language": "en",

  "briefing": {
    "situation": "You are Chief Officer on a 47,000 DWT chemical tanker carrying a full cargo of methanol in center tanks and caustic soda in wing tanks. During discharge at a terminal in Rotterdam, the shore-side loading arm develops a visible leak at the manifold connection. The terminal operator has not noticed. Discharge rate is 800 m3/hr. Wind is onshore at 15 knots.",
    "your_position": "Chief Officer on cargo watch",
    "available_resources": [
      "Bridge team (OOW + lookout)",
      "Duty engineer in ECR",
      "2 ABs on deck for mooring watch",
      "Shore gangway watchman",
      "Terminal control room (VHF Ch 12)"
    ],
    "current_conditions": {
      "weather": "Overcast, 15kt onshore wind, good visibility",
      "time": "02:30 local, night operations",
      "tide": "Falling, 1.5hr to low water"
    }
  },

  "decision_prompt": "Describe your immediate actions, the communications you would make, and your decision-making process for the next 30 minutes.",

  "evaluation_axes": [
    {
      "axis": "risk_identification",
      "weight": 0.35,
      "scoring_rubric": {
        "5": "Identifies: methanol vapor risk (toxic + flammable), wind carrying vapor to accommodation/shore, potential cargo contamination if mixed, environmental spill risk, personnel safety zone requirements. Considers cascade: what if leak worsens during ESD.",
        "4": "Identifies primary chemical hazards and environmental risk. Mentions vapor direction. May miss cascade scenario.",
        "3": "Identifies leak hazard and need to stop operations. Basic safety awareness.",
        "2": "Recognizes leak but underestimates chemical-specific risks. Treats as routine spill.",
        "1": "Fails to recognize severity. Continues operations or delays response."
      }
    },
    {
      "axis": "decision_quality",
      "weight": 0.35,
      "scoring_rubric": {
        "5": "Immediate ESD activation. Crew mustered to safe zone upwind. Shore notified via VHF and emergency signal. Master called. Considers cargo tank pressure monitoring. Prepares for potential fire/toxic gas emergency. Documents in deck log.",
        "4": "Stops cargo operations promptly. Notifies shore. Musters crew. Most correct procedures followed.",
        "3": "Stops operations. Notifies some parties. Basic response.",
        "2": "Hesitates. Tries to fix leak before stopping operations. Incomplete notification.",
        "1": "Continues operations. Attempts unauthorized repair. Fails to notify."
      }
    },
    {
      "axis": "procedural_compliance",
      "weight": 0.30,
      "scoring_rubric": {
        "5": "References: ISGOTT, ship/shore safety checklist, company SMS cargo procedures, MSDS for methanol, terminal emergency procedures. Knows to activate ship emergency signal and inform port authority if spill reaches water.",
        "4": "References most applicable procedures. Follows SMS structure.",
        "3": "Follows basic emergency procedures.",
        "2": "Vague procedural references.",
        "1": "No procedural framework evident."
      }
    }
  ],

  "critical_omission_flags": [
    "Does not stop cargo operations immediately",
    "Does not consider methanol vapor toxicity (different from petroleum)",
    "Does not notify terminal/shore",
    "Does not consider wind direction for vapor drift",
    "Does not muster crew to safe zone",
    "Does not call Master"
  ],

  "expected_references": [
    "ISGOTT",
    "IBC Code (chemical tanker)",
    "MSDS / Safety Data Sheet",
    "Company SMS emergency procedures",
    "Ship/Shore Safety Checklist"
  ],

  "red_flags": [
    {
      "code": "RF_SAFETY_BYPASS",
      "trigger": "Suggests continuing operations while addressing leak",
      "severity": "critical"
    },
    {
      "code": "RF_AUTHORITY_FAIL",
      "trigger": "Waits for shore instruction instead of taking independent action",
      "severity": "major"
    }
  ]
}
```

---

## 3. Scenario Bank Per Command Class

### 3.1 RIVER Command Scenarios

```
RIVER_S01_NAV  Port congestion: departure from river lock with
               downstream current and approaching convoy.

RIVER_S02_CMD  Night transit: managing bridge team of 2 during
               restricted visibility in narrow channel with
               oncoming traffic.

RIVER_S03_TECH Shallow water: grounding risk management during
               low water period with cargo deadweight decisions.

RIVER_S04_RISK Barge breakaway: securing cargo barges during
               sudden current change near bridge piers.

RIVER_S05_CREW Single-handed watch: crew member incapacitated
               during river transit near populated area.

RIVER_S06_AUTO GPS failure during river transit: switch to
               visual navigation with limited aids.

RIVER_S07_CRIS Flooding: hull breach from submerged object in
               river channel. Grounding vs. sinking decision.

RIVER_S08_TRADE Schedule vs. safety: charterer demands transit
               during flood warning. Commercial penalty vs. risk.
```

### 3.2 COASTAL Command Scenarios

```
COASTAL_S01_NAV  Coastal approach: fog rolling in during approach
                 to unfamiliar port with narrow entrance channel.

COASTAL_S02_CMD  Multiple taskings: vessel ordered to divert for
                 SAR while on charter time with tight schedule.

COASTAL_S03_TECH Engine room alarm: main engine cooling water
                 temp high during coastal passage near lee shore.

COASTAL_S04_RISK Cargo shift: suspected cargo shift in heavy
                 weather during coastal passage. Stability concern.

COASTAL_S05_CREW Crew injury: serious injury during anchoring
                 operation. Medical evacuation decisions.

COASTAL_S06_AUTO Radar failure: ARPA overlay lost during night
                 coastal passage in traffic separation scheme.

COASTAL_S07_CRIS Fire: accommodation fire discovered during night
                 watch. Crew of 12, nearest port 4 hours.

COASTAL_S08_TRADE Weather delay: continue coastal passage in
                 worsening weather or seek shelter and miss
                 layday. Cargo perishable.
```

### 3.3 SHORT_SEA Command Scenarios

```
SHORT_SEA_S01_NAV  Strait transit: Bosphorus/Turkish Straits
                   passage with significant cross-current and
                   opposing traffic. Pilot on board but giving
                   questionable advice.

SHORT_SEA_S02_CMD  Port turnaround: managing simultaneous cargo
                   operations, crew change, and PSC inspection.

SHORT_SEA_S03_TECH Container stowage: lashing failure risk in
                   Bay of Biscay weather. Stack weight vs.
                   GM decision.

SHORT_SEA_S04_RISK Route planning: passage through piracy risk
                   area (Gulf of Aden approach). Speed vs. fuel
                   vs. security measures.

SHORT_SEA_S05_CREW Multinational crew: language barrier causing
                   safety briefing comprehension issues before
                   critical cargo operation.

SHORT_SEA_S06_AUTO ECDIS discrepancy: chart data doesn't match
                   visual observation. Wreck not on chart near
                   approach channel.

SHORT_SEA_S07_CRIS Collision: contact with fishing vessel in fog.
                   Own vessel minor damage, fishing vessel taking
                   water. SAR coordination as on-scene commander.

SHORT_SEA_S08_TRADE Emissions compliance: approaching SECA zone
                    with fuel changeover delayed due to engine
                    room maintenance. Risk fine vs. risk engine
                    issue during changeover.
```

### 3.4 DEEP_SEA Command Scenarios

```
DEEP_SEA_S01_NAV  Ocean routing: typhoon developing on planned
                  route across Pacific. Weather routing service
                  recommends 800nm diversion. Charter party has
                  hire/off-hire implications.

DEEP_SEA_S02_CMD  Mid-ocean: managing crew morale during 45-day
                  passage with communication system failure
                  (no internet/phone for crew).

DEEP_SEA_S03_TECH Hold flooding: ballast system failure causing
                  flooding in cargo hold during ocean passage.
                  Stability calculations required.

DEEP_SEA_S04_RISK Structural: crack discovered in deck plating
                  during heavy weather. Continue to discharge
                  port (5 days) or divert to nearest port
                  (2 days, no repair facilities).

DEEP_SEA_S05_CREW Medical: crew member with suspected appendicitis
                  12 days from port. TMAS consultation. Helicopter
                  range exceeded. Diversion decision.

DEEP_SEA_S06_AUTO Gyro failure: both gyro compasses failed during
                  ocean passage. Magnetic compass last swung 6
                  months ago. GPS heading available.

DEEP_SEA_S07_CRIS Abandon ship: progressive flooding after hull
                  breach in remote ocean. 28 crew. Nearest vessel
                  8 hours. Liferaft vs. stay on vessel decision
                  timing.

DEEP_SEA_S08_TRADE Cargo care vs. schedule: reefer cargo temperature
                   rising due to power limitations. Slow down for
                   power or maintain speed for delivery deadline.
                   Insurance implications.
```

### 3.5 CONTAINER_ULCS Command Scenarios

```
ULCS_S01_NAV  Port approach: 400m vessel in narrow approach
              channel with 1.5m UKC. Squat calculation.
              Tidal window critical. Second tug delayed.

ULCS_S02_CMD  Multi-terminal operation: simultaneous cargo
              operations fore and aft with different terminal
              operators. Conflicting stowage plans.

ULCS_S03_TECH Parametric rolling: onset of parametric rolling
              in head seas. Container stack forces exceeding
              design limits. Speed/course change analysis.

ULCS_S04_RISK DG container: mis-declared dangerous goods
              container discovered. Adjacent to reefer stack.
              Mid-ocean, 3 days from port.

ULCS_S05_CREW Stevedore injury: shore worker injured during
              cargo operations. Responsibility, investigation,
              port authority notification, operations impact.

ULCS_S06_AUTO ECDIS route check: automatic route check flags
              shallow water on approved route. Chart update
              not applied. 2 hours to waypoint.

ULCS_S07_CRIS Container fire: fire in container hold. 15,000
              TEU vessel. CO2 fixed system vs. boundary cooling.
              Crew safety vs. cargo/vessel protection.

ULCS_S08_TRADE Schedule vs. weather: liner schedule demands
              departure but wind forecast exceeds safe departure
              criteria for vessel size. Terminal says go. You say?
```

### 3.6 TANKER Command Scenarios

```
TANKER_S01_NAV  STS operation: ship-to-ship transfer approach
                in open sea. Swell increasing. Fendering check.
                Approach abort criteria.

TANKER_S02_CMD  SIRE vetting: preparing for unannounced SIRE
                inspection during cargo operations. Managing
                crew readiness while maintaining safe operations.

TANKER_S03_TECH Tank cleaning: cargo changeover from dirty
                petroleum to clean product. Tank cleaning plan.
                Wall wash test failure after first cleaning.

TANKER_S04_RISK Manifold leak: (detailed scenario from Section 2
                example above)

TANKER_S05_CREW Pump room entry: AB found unconscious near pump
                room entrance. H2S suspected. Rescue procedures
                vs. additional casualty prevention.

TANKER_S06_AUTO Cargo monitoring: integrated cargo monitoring
                system failure during discharge. Manual gauging
                and calculations required for 12-tank operation.

TANKER_S07_CRIS Explosion: cargo tank overpressure alarm during
                loading. IG system malfunction. Potential
                explosive atmosphere. Evacuation vs. system
                intervention.

TANKER_S08_TRADE VOC emissions: voyage instructions to maximize
                loading but VOC management system at capacity.
                Commercial optimization vs. environmental/safety
                compliance.
```

### 3.7 LNG Command Scenarios

```
LNG_S01_NAV   Terminal approach: FSRU approach in confined
              waters. Exclusion zone management. Small craft
              incursion into safety zone.

LNG_S02_CMD   Cargo transfer: managing LNG cargo transfer with
              terminal disagreement on flow rates. Boil-off
              rate increasing. Pressure management.

LNG_S03_TECH  Containment: cargo containment system alarm.
              Primary barrier temperature anomaly in membrane
              tank. Cargo management decisions.

LNG_S04_RISK  Boil-off management: reliquefaction plant failure
              during laden voyage. Gas-up / cool-down decisions.
              Boil-off routing (GCU vs. engine consumption).

LNG_S05_CREW  Emergency drill: realistic abandon ship drill
              planning for crew of 30 with trainees. Previous
              drill showed poor performance. Confidence building.

LNG_S06_AUTO  ESD link failure: emergency shutdown link between
              ship and terminal not functioning. Manual ESD
              procedures. Continue cargo operations or stop.

LNG_S07_CRIS  Gas detection: gas detected in motor room below
              cargo area. Suspected cargo leak. Vessel in port
              with terminal connected. Cascade response.

LNG_S08_TRADE Heel management: charterer requests minimum heel
              for next cargo. Current heel insufficient for
              cool-down. Purchase LNG from terminal or arrive
              warm and delay next loading.
```

### 3.8 OFFSHORE Command Scenarios

```
OFFSHORE_S01_NAV  DP operations: maintaining station in
                  deteriorating weather. DP footprint analysis.
                  Decide: continue operation or disconnect.

OFFSHORE_S02_CMD  Multi-vessel operation: coordinating with
                  2 supply vessels, ROV vessel, and platform
                  crane during subsea installation.

OFFSHORE_S03_TECH Anchor handling: anchor deployment goes wrong.
                  Chain twist, windlass overload alarm. Controlled
                  stop vs. emergency release decision.

OFFSHORE_S04_RISK Dropped object: container dropped from platform
                  crane into water near vessel. Potential hull
                  damage. Dive survey vs. immediate departure
                  from 500m zone.

OFFSHORE_S05_CREW Helicopter operations: helicopter inbound for
                  crew change but deck motion exceeds limits.
                  Captain refuses to restrict motion. Wave-off
                  procedures vs. authority assertion.

OFFSHORE_S06_AUTO DP reference loss: two of three position
                  reference systems lost simultaneously. Single
                  reference operations. Continue pipelay or
                  abandon and re-initiate.

OFFSHORE_S07_CRIS Blowout scenario: platform reports well control
                  issue. Standby vessel role. Evacuation
                  preparation. ERRV duties. Communication with
                  multiple parties.

OFFSHORE_S08_TRADE Weather window: 6-hour weather window for
                   critical lift. Equipment ready but crew
                   approaching fatigue limits (10hr worked).
                   Miss window = 5 day delay. Client pressure.
```

### 3.9 PASSENGER Command Scenarios

```
PAX_S01_NAV   Port maneuver: 300m cruise ship in port with
              sudden wind shift during berthing. Tug
              repositioning. Passenger on external decks.

PAX_S02_CMD   Mass event: norovirus outbreak on board.
              2000 passengers. Port authority notification.
              Quarantine decisions. Itinerary changes.

PAX_S03_TECH  Blackout: total power failure during departure.
              Emergency generator starts. Priority systems.
              Passenger communication. Anchor deployment.

PAX_S04_RISK  Port state control: PSC inspector finds
              deficiency during passenger operations. Potential
              detention. Managing inspection while maintaining
              passenger schedule and safety.

PAX_S05_CREW  Crowd management: fire alarm (false) in theater
              during show. 800 passengers. Muster station
              management. Crew response evaluation.

PAX_S06_AUTO  Bridge integration: integrated bridge system
              malfunction shows incorrect heading. Discovered
              by visual bearing check. System trust decision.

PAX_S07_CRIS  Flooding: hull breach below waterline from
              grounding. Progressive flooding. 3000 passengers.
              Abandon ship threshold decision. Timing critical.

PAX_S08_TRADE Port of refuge: vessel needs shelter due to
              weather but destination port closed. Alternative
              port has no tender facilities. 1200 passengers
              miss shore excursion. Compensation vs. safety vs.
              corporate pressure.
```

---

## 4. Scenario Bank Database Schema

```sql
CREATE TABLE scenario_bank (
    id CHAR(36) PRIMARY KEY,
    scenario_id VARCHAR(50) NOT NULL UNIQUE,    -- e.g., TANKER_S04_RISK_001
    command_class VARCHAR(30) NOT NULL,
    slot TINYINT NOT NULL,                       -- 1-8
    domain VARCHAR(20) NOT NULL,                 -- PORT_OPS, NAV_HAZ, etc.
    primary_capability VARCHAR(20) NOT NULL,     -- NAV_COMPLEX, RISK_MGMT, etc.
    secondary_capabilities JSON DEFAULT NULL,
    difficulty_tier TINYINT NOT NULL,            -- 1, 2, 3
    language VARCHAR(5) NOT NULL DEFAULT 'en',

    -- Content
    briefing JSON NOT NULL,                      -- situation, position, resources, conditions
    decision_prompt TEXT NOT NULL,
    evaluation_axes JSON NOT NULL,               -- [{axis, weight, rubric}]
    critical_omission_flags JSON NOT NULL,
    expected_references JSON DEFAULT NULL,
    red_flags JSON DEFAULT NULL,

    -- Metadata
    version VARCHAR(10) DEFAULT 'v2',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_class_slot (command_class, slot),
    INDEX idx_class_lang (command_class, language),
    INDEX idx_domain (domain),
    INDEX idx_capability (primary_capability)
);
```

---

## 5. Scenario Selection Logic

```php
class ScenarioSelector
{
    /**
     * Select 8 scenarios for a Phase 2 interview.
     *
     * Guarantees:
     * - Exactly 1 scenario per capability (slots 1-7)
     * - Slot 8 is always TRADEOFF
     * - All scenarios match the detected command class
     * - Tier 3 only for slots 7 (crisis) and 8 (tradeoff)
     * - No duplicate domains if possible
     */
    public function select(
        string $commandClass,
        string $language
    ): array {
        $scenarios = [];

        $capabilitySlotMap = [
            1 => 'NAV_COMPLEX',
            2 => 'CMD_SCALE',
            3 => 'TECH_DEPTH',
            4 => 'RISK_MGMT',
            5 => 'CREW_LEAD',
            6 => 'AUTO_DEP',
            7 => 'CRISIS_RSP',
            8 => null,  // TRADEOFF (multi-capability)
        ];

        $usedDomains = [];

        for ($slot = 1; $slot <= 8; $slot++) {
            $capability = $capabilitySlotMap[$slot];

            $query = ScenarioBank::where('command_class', $commandClass)
                ->where('slot', $slot)
                ->where('is_active', true);

            // Language with fallback
            $scenario = (clone $query)->where('language', $language)->first()
                ?? (clone $query)->where('language', 'en')->first();

            if (!$scenario) {
                throw new ScenarioNotFoundException(
                    "Missing scenario: class={$commandClass}, slot={$slot}"
                );
            }

            // Prefer unused domain if multiple scenarios exist for slot
            if (!empty($usedDomains)) {
                $alternative = (clone $query)
                    ->where('language', $language)
                    ->whereNotIn('domain', $usedDomains)
                    ->first();

                if ($alternative) {
                    $scenario = $alternative;
                }
            }

            $scenarios[] = $scenario;
            $usedDomains[] = $scenario->domain;
        }

        return $scenarios;
    }
}
```

---

## 6. Decision Output Structure (Layer 6)

After Phase 2 scoring is complete, the system produces:

```json
{
  "decision_output": {
    "candidate_id": "uuid",
    "form_interview_id": "uuid",
    "command_class": "TANKER",
    "scored_at": "2026-02-17T15:00:00Z",

    "candidate_vessel_fit_map": {
      "TANKER": {
        "fit_score": 82,
        "fit_level": "STRONG",
        "sub_class_fit": {
          "PRODUCT_TANKER": 88,
          "CHEMICAL_TANKER": 92,
          "CRUDE_TANKER": 45
        },
        "limiting_factors": ["crew_scale for VLCC operations"]
      },
      "SHORT_SEA": {
        "fit_score": 65,
        "fit_level": "MODERATE",
        "limiting_factors": ["container-specific experience missing"]
      },
      "DEEP_SEA": {
        "fit_score": 42,
        "fit_level": "LOW",
        "limiting_factors": ["no deep sea ocean experience", "DWT range mismatch"]
      }
    },

    "risk_profile": {
      "overall_risk_level": "LOW",
      "risk_vectors": {
        "safety_compliance": { "level": "LOW", "score": 88 },
        "decision_quality": { "level": "LOW", "score": 82 },
        "authority_exercise": { "level": "MEDIUM", "score": 62 },
        "environmental": { "level": "LOW", "score": 90 },
        "regulatory": { "level": "LOW", "score": 85 }
      },
      "critical_flags": [],
      "major_flags": [],
      "minor_flags": ["Hesitant authority exercise in multi-party scenario"]
    },

    "command_readiness_level": {
      "level": "CRL_3",
      "label": "Ready for supervised command",
      "definition": "Can command vessel type under company mentoring program",
      "levels": {
        "CRL_1": "Not ready — significant capability gaps",
        "CRL_2": "Development needed — targeted training required",
        "CRL_3": "Ready for supervised command",
        "CRL_4": "Ready for independent command",
        "CRL_5": "Ready for complex/high-value command"
      },
      "promotion_path": [
        "Strengthen command_scale through senior officer mentoring",
        "Gain VLCC experience for crude tanker deployment"
      ]
    },

    "deployment_suitability": {
      "immediate_deployment": [
        {
          "vessel_type": "chemical_tanker",
          "dwt_range": "5000-50000",
          "rank": "chief_officer",
          "confidence": 0.92,
          "restrictions": []
        },
        {
          "vessel_type": "product_tanker",
          "dwt_range": "5000-60000",
          "rank": "chief_officer",
          "confidence": 0.88,
          "restrictions": []
        }
      ],
      "with_development": [
        {
          "vessel_type": "chemical_tanker",
          "dwt_range": "5000-50000",
          "rank": "captain",
          "confidence": 0.72,
          "development_required": ["Command assessment program", "6 months mentored command"],
          "timeline_months": 6
        }
      ],
      "not_suitable": [
        {
          "vessel_type": "VLCC",
          "reason": "No large crude carrier experience. DWT gap too large."
        }
      ]
    },

    "video_interview_trigger": {
      "recommended": true,
      "reason": "Command profile strong but authority_exercise score below threshold for independent command recommendation",
      "focus_areas": ["decision_reasoning", "crew_command_presence", "pressure_response"]
    }
  }
}
```

### Command Readiness Level Calculation

```
CRL_1: Any capability < 30 OR crisis_response < 40
CRL_2: All capabilities >= 30 AND avg >= 50 AND crisis_response >= 40
CRL_3: All capabilities >= 50 AND avg >= 65 AND crisis_response >= 60
CRL_4: All capabilities >= 60 AND avg >= 75 AND crisis_response >= 70
CRL_5: All capabilities >= 70 AND avg >= 85 AND crisis_response >= 80
```

### Video Interview Trigger Rules

```
Video recommended when:
  1. CRL >= 3 AND any single capability score is borderline (50-60)
  2. Customer explicitly requests validation
  3. Command profile shows class transition (e.g., coastal→deep sea)
  4. Risk profile has any MEDIUM or higher flag
  5. Deployment recommendation is "with_development" for target rank

Video NOT triggered when:
  1. CRL < 3 (candidate needs development first, video adds no value)
  2. All capabilities >= 70 with no flags (clear strong candidate)
```
