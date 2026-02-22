# D — Resolver Design

## Resolver v2: Profile-Based Template Selection

### Current System (v1) — Being Replaced

```
role_code → department → template lookup
                         ↓
               deck_captain → deck_captain template
               deck___generic__ (fallback)
```

Problem: Treats all captains identically.
A chemical tanker captain and a cruise ship captain get the same template.

### New System (v2) — Command-Profile-Driven Resolution

```
candidate_command_profile
    → command_class_detection
        → primary_class + secondary_class
            → phase2_template_selection
                → class-specific scenario set
```

---

## 1. Resolver Pipeline

### Step 1: Profile Extraction (Phase 1 Complete)

After Phase 1 interview answers are submitted, the `ProfileExtractor` service
processes the 12 responses and builds the `candidate_command_profile`.

```php
class ProfileExtractor
{
    public function extract(FormInterview $interview): CandidateCommandProfile
    {
        $responses = $interview->commandProfileResponses;

        return new CandidateCommandProfile([
            'vessel_experience'   => $this->parseVesselHistory($responses['VESSEL_HISTORY']),
            'automation_exposure' => $this->parseBridgeTech($responses['BRIDGE_TECHNOLOGY']),
            'cargo_types_handled' => $this->parseCargoOps($responses['CARGO_OPERATIONS']),
            'crew_scale'          => $this->parseCrewMgmt($responses['CREW_MANAGEMENT']),
            'trading_areas'       => $this->parseTradingAreas($responses['TRADING_AREAS']),
            'weather_severity'    => $this->classifyWeather($responses['WEATHER_OPERATIONS']),
            'port_density'        => $this->classifyPortDensity($responses['PORT_OPERATIONS']),
            'incident_history'    => $this->parseIncidents($responses['INCIDENT_HISTORY']),
            'command_history'     => $this->parseCommandExp($responses['COMMAND_EXPERIENCE']),
            'certifications'      => $this->parseCertifications($responses['CERTIFICATION_STATUS']),
        ]);
    }
}
```

### Step 2: Command Class Detection

The `CommandClassDetector` scores the profile against all 9 command classes.

```php
class CommandClassDetector
{
    private const WEIGHTS = [
        'vessel_type'    => 0.30,
        'dwt_range'      => 0.20,
        'trading_area'   => 0.15,
        'cargo_type'     => 0.15,
        'automation'     => 0.10,
        'crew_scale'     => 0.10,
    ];

    public function detect(CandidateCommandProfile $profile): CommandClassResult
    {
        $scores = [];

        foreach (CommandClass::allActive() as $class) {
            $scores[$class->code] = $this->scoreAgainstClass($profile, $class);
        }

        arsort($scores);
        $sorted = array_keys($scores);

        return new CommandClassResult(
            primary: $sorted[0],
            primaryScore: $scores[$sorted[0]],
            secondary: $scores[$sorted[1]] >= 40.0 ? $sorted[1] : null,
            secondaryScore: $scores[$sorted[1]] ?? 0,
            allScores: $scores,
        );
    }

    private function scoreAgainstClass(
        CandidateCommandProfile $profile,
        CommandClass $class
    ): float {
        $score = 0.0;

        // Vessel type overlap
        $vesselOverlap = $this->setOverlap(
            $profile->getVesselTypes(),
            $class->vessel_types
        );
        $score += $vesselOverlap * self::WEIGHTS['vessel_type'] * 100;

        // DWT range overlap
        $dwtOverlap = $this->rangeOverlap(
            $profile->dwt_range_min, $profile->dwt_range_max,
            $class->dwt_range_min, $class->dwt_range_max
        );
        $score += $dwtOverlap * self::WEIGHTS['dwt_range'] * 100;

        // Trading area overlap
        $areaOverlap = $this->setOverlap(
            $profile->trading_areas,
            $class->trading_areas
        );
        $score += $areaOverlap * self::WEIGHTS['trading_area'] * 100;

        // Cargo type overlap
        $cargoOverlap = $this->setOverlap(
            $profile->cargo_types_handled,
            $class->cargo_types
        );
        $score += $cargoOverlap * self::WEIGHTS['cargo_type'] * 100;

        // Automation level match
        $autoMatch = $this->automationMatch(
            $profile->automation_exposure,
            $class->automation_levels
        );
        $score += $autoMatch * self::WEIGHTS['automation'] * 100;

        // Crew scale overlap
        $crewOverlap = $this->rangeOverlap(
            $profile->crew_scale_min, $profile->crew_scale_max,
            $class->crew_scale_min, $class->crew_scale_max
        );
        $score += $crewOverlap * self::WEIGHTS['crew_scale'] * 100;

        return round($score, 2);
    }

    /**
     * Set overlap ratio: |A ∩ B| / |B|
     * How much of the class requirement does the candidate cover?
     */
    private function setOverlap(array $candidate, array $class): float
    {
        if (empty($class)) return 0.0;
        $intersection = array_intersect($candidate, $class);
        return count($intersection) / count($class);
    }

    /**
     * Range overlap: what fraction of class range does candidate cover?
     * Returns 0.0-1.0
     */
    private function rangeOverlap(
        ?int $cMin, ?int $cMax,
        int $classMin, int $classMax
    ): float {
        if ($cMin === null || $cMax === null) return 0.0;

        $overlapMin = max($cMin, $classMin);
        $overlapMax = min($cMax, $classMax);

        if ($overlapMin >= $overlapMax) return 0.0;

        $classRange = $classMax - $classMin;
        if ($classRange <= 0) return 0.0;

        return ($overlapMax - $overlapMin) / $classRange;
    }
}
```

### Step 3: Template Resolution

```php
class CommandTemplateResolver
{
    /**
     * Phase 1 template is universal — no resolution needed.
     * All candidates get the same 12-question identity capture.
     */
    public function resolvePhase1(string $language): InterviewTemplate
    {
        return InterviewTemplate::where('version', 'v2')
            ->where('language', $language)
            ->where('position_code', '__phase1_identity__')
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * Phase 2 template selected based on detected command class.
     * NO FALLBACK to generic. If class-specific template missing → error.
     */
    public function resolvePhase2(
        string $commandClass,
        string $language
    ): InterviewTemplate {
        // Exact match required
        $template = InterviewTemplate::where('version', 'v2')
            ->where('language', $language)
            ->where('position_code', "command_{$commandClass}")
            ->where('is_active', true)
            ->first();

        // Language fallback: try English as base language
        if (!$template && $language !== 'en') {
            $template = InterviewTemplate::where('version', 'v2')
                ->where('language', 'en')
                ->where('position_code', "command_{$commandClass}")
                ->where('is_active', true)
                ->first();
        }

        if (!$template) {
            throw new TemplateNotFoundException(
                "No Phase 2 template for command class: {$commandClass}"
            );
        }

        return $template;
    }
}
```

### Step 4: Full Resolver Orchestration

```php
class InterviewResolverV2
{
    public function __construct(
        private ProfileExtractor $extractor,
        private CommandClassDetector $detector,
        private CommandTemplateResolver $templateResolver,
        private FormInterviewService $interviewService,
    ) {}

    /**
     * Start Phase 1: Create interview with identity capture template.
     */
    public function startPhase1(
        string $candidateId,
        string $language,
        array $meta = []
    ): FormInterview {
        $template = $this->templateResolver->resolvePhase1($language);

        return $this->interviewService->create(
            version: 'v2',
            language: $language,
            positionCode: '__phase1_identity__',
            meta: array_merge($meta, [
                'framework' => 'maritime_command',
                'phase' => 1,
            ]),
            industryCode: 'maritime',
        );
    }

    /**
     * Complete Phase 1 and transition to Phase 2.
     *
     * Called after all 12 Phase 1 answers are submitted.
     * Returns the Phase 2 interview (new FormInterview record).
     */
    public function completePhase1AndStartPhase2(
        FormInterview $phase1Interview
    ): array {
        // 1. Extract command profile
        $profile = $this->extractor->extract($phase1Interview);
        $profile->save();

        // 2. Detect command class
        $classResult = $this->detector->detect($profile);

        // 3. Update Phase 1 record
        $phase1Interview->update([
            'phase1_completed_at' => now(),
            'command_class_detected' => $classResult->primary,
            'command_profile_id' => $profile->id,
        ]);

        // 4. Resolve Phase 2 template
        $template = $this->templateResolver->resolvePhase2(
            $classResult->primary,
            $phase1Interview->language
        );

        // 5. Create Phase 2 interview
        $phase2Interview = $this->interviewService->create(
            version: 'v2',
            language: $phase1Interview->language,
            positionCode: "command_{$classResult->primary}",
            meta: [
                'framework' => 'maritime_command',
                'phase' => 2,
                'command_class' => $classResult->primary,
                'command_class_score' => $classResult->primaryScore,
                'secondary_class' => $classResult->secondary,
                'candidate_id' => $phase1Interview->meta['candidate_id'] ?? null,
                'phase1_interview_id' => $phase1Interview->id,
                'command_profile_id' => $profile->id,
            ],
            industryCode: 'maritime',
        );

        $phase2Interview->update([
            'command_class_detected' => $classResult->primary,
            'command_profile_id' => $profile->id,
            'phase2_template_selected_at' => now(),
        ]);

        return [
            'phase1_interview' => $phase1Interview,
            'phase2_interview' => $phase2Interview,
            'command_profile' => $profile,
            'class_result' => $classResult,
        ];
    }
}
```

---

## 2. Resolution Decision Tree

```
START
  │
  ├─ Is this a new candidate? (no existing profile)
  │    │
  │    └─ YES → Start Phase 1 (identity capture)
  │              │
  │              └─ Phase 1 complete?
  │                   │
  │                   ├─ YES → Extract profile → Detect class → Start Phase 2
  │                   │
  │                   └─ NO → Continue Phase 1 (collect remaining answers)
  │
  ├─ Does candidate have existing command profile?
  │    │
  │    └─ YES → Profile age < 6 months?
  │              │
  │              ├─ YES → Use existing profile → Detect class → Start Phase 2
  │              │
  │              └─ NO → Start new Phase 1 (profile refresh)
  │
  └─ Manual class override by admin?
       │
       └─ YES → Use admin-assigned class → Start Phase 2 directly
```

---

## 3. Edge Cases

### 3.1 Low Detection Confidence
```
if primaryScore < 50.0:
    → Flag as UNRESOLVED
    → Notify admin for manual class assignment
    → Do NOT auto-assign Phase 2 template
    → Candidate stays in Phase 1 complete state
```

### 3.2 Multi-Class Candidates
```
if secondaryScore >= 60.0 AND (primaryScore - secondaryScore) < 15:
    → Flag as MULTI_CLASS
    → Primary class used for Phase 2
    → Both classes stored for matching engine
    → Vessel fit map includes both classes
```

### 3.3 Class Not Covered By Templates
```
if no template exists for detected class:
    → Log error to system_events
    → Return 422 with class name
    → Admin must create template before candidate can proceed
    → Never fall back to wrong class template
```

### 3.4 Re-Assessment
```
A candidate can be re-assessed:
    → New Phase 1 creates new command_profile
    → Old profile preserved (versioned)
    → New Phase 2 with potentially different class
    → History maintained for trajectory analysis
```

---

## 4. Resolution Audit Trail

Every resolution decision is logged:

```sql
CREATE TABLE resolver_audit_log (
    id CHAR(36) PRIMARY KEY,
    candidate_id CHAR(36) NOT NULL,
    form_interview_id CHAR(36) NOT NULL,
    phase TINYINT NOT NULL,
    action VARCHAR(30) NOT NULL,          -- phase1_start, profile_extracted,
                                          -- class_detected, phase2_start,
                                          -- manual_override, unresolved
    command_class_detected VARCHAR(30) DEFAULT NULL,
    detection_scores JSON DEFAULT NULL,
    detection_confidence DECIMAL(5,2) DEFAULT NULL,
    template_selected VARCHAR(100) DEFAULT NULL,
    override_reason TEXT DEFAULT NULL,
    resolved_by VARCHAR(36) DEFAULT NULL,  -- null=system, uuid=admin
    created_at TIMESTAMP NOT NULL,

    INDEX idx_candidate (candidate_id),
    INDEX idx_interview (form_interview_id)
);
```

---

## 5. API Endpoints

```
POST   /v2/maritime/interviews/start-phase1
       → Creates Phase 1 interview for candidate
       → Returns: interview_id, template (12 questions)

POST   /v2/maritime/interviews/{id}/phase1-answers
       → Submit Phase 1 answers (batch or incremental)
       → Returns: progress (X/12 completed)

POST   /v2/maritime/interviews/{id}/complete-phase1
       → Triggers: extraction → detection → Phase 2 creation
       → Returns: command_profile, detected_class, phase2_interview_id

GET    /v2/maritime/interviews/{id}/command-profile
       → Returns extracted command profile + class scores

POST   /v2/maritime/interviews/{id}/phase2-answers
       → Submit Phase 2 scenario answers

POST   /v2/maritime/interviews/{id}/complete-phase2
       → Triggers: capability scoring → decision output
       → Returns: capability_profile, vessel_fit_map, deployment_suitability

POST   /v2/maritime/interviews/{id}/override-class
       → Admin override: manually assign command class
       → Requires: class_code, reason
```
