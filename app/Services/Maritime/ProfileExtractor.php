<?php

namespace App\Services\Maritime;

use App\Models\CandidateCommandProfile;
use App\Models\FormInterview;
use App\Models\FormInterviewAnswer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Extracts structured maritime identity data from Phase-1 answers.
 *
 * Input: FormInterview with 12 identity answers.
 * Output: CandidateCommandProfile with parsed, structured data.
 */
class ProfileExtractor
{
    // ── Vessel type taxonomy ──
    private const VESSEL_TYPES = [
        // River
        'river_barge', 'river_tanker', 'river_pusher', 'river_passenger', 'river_cargo', 'river_ferry',
        // Coastal
        'coaster', 'coastal_tanker', 'coastal_ferry', 'tug', 'supply_vessel', 'pilot_vessel',
        // Short sea
        'general_cargo', 'multi_purpose', 'small_container', 'ro_ro', 'short_sea_tanker',
        // Deep sea
        'bulk_carrier', 'general_cargo_ocean', 'reefer', 'heavy_lift',
        // Container
        'container_feeder', 'container_panamax', 'container_post_panamax', 'container_ulcs',
        // Tanker
        'product_tanker', 'crude_tanker', 'chemical_tanker', 'VLCC', 'aframax', 'suezmax',
        // LNG
        'LNG_carrier', 'FSRU', 'LNG_bunkering_vessel',
        // Offshore
        'PSV', 'AHTS', 'DSV', 'pipe_layer', 'crane_vessel', 'FPSO', 'wind_farm_vessel', 'jack_up',
        // Passenger
        'cruise_ship', 'ro_pax', 'expedition_vessel', 'yacht_large',
    ];

    // ── Trading area taxonomy ──
    private const TRADING_AREAS = [
        'inland_waterway', 'river_estuary',
        'coastal_domestic', 'coastal_regional',
        'short_sea', 'regional_international',
        'ocean_atlantic', 'ocean_pacific', 'ocean_indian', 'worldwide',
        'persian_gulf', 'north_sea', 'gulf_of_mexico', 'southeast_asia',
        'brazil_pre_salt', 'polar_regions', 'lng_corridor', 'strait_transit',
        'west_africa', 'mediterranean', 'black_sea', 'baltic', 'red_sea',
    ];

    // ── Cargo type taxonomy ──
    private const CARGO_TYPES = [
        'bulk_dry', 'bulk_grain', 'liquid_cargo', 'general_cargo', 'project_cargo',
        'containers', 'reefer_containers', 'dangerous_goods_containers',
        'crude_oil', 'refined_products', 'chemicals', 'vegetable_oils',
        'LNG', 'LPG', 'ethane',
        'deck_cargo', 'mud', 'cement', 'fuel', 'pipes', 'subsea_equipment',
        'passengers', 'vehicles', 'limited_freight',
        'steel_coils', 'reefer_cargo', 'heavy_lift', 'ro_ro_units',
    ];

    // ── Automation keywords ──
    private const AUTOMATION_KEYWORDS = [
        'levels' => [
            'manual' => ['manual', 'traditional', 'conventional'],
            'basic' => ['basic', 'simple', 'minimal automation'],
            'standard' => ['standard', 'moderate'],
            'integrated' => ['integrated', 'integrated bridge', 'IBS'],
            'high_automation' => ['high automation', 'highly automated', 'advanced'],
            'fully_integrated' => ['fully integrated', 'full automation'],
            'DP_class_2' => ['DP2', 'DP class 2', 'DP-2', 'dynamic positioning 2'],
            'DP_class_3' => ['DP3', 'DP class 3', 'DP-3', 'dynamic positioning 3'],
        ],
        'equipment' => [
            'ECDIS', 'ARPA', 'AIS', 'GPS', 'GMDSS', 'VDR', 'radar',
            'Furuno', 'JRC', 'Transas', 'Kongsberg', 'Wartsila', 'Sperry',
            'SAM Electronics', 'Kelvin Hughes',
        ],
    ];

    /**
     * Extract structured profile from Phase-1 form interview.
     */
    public function extract(FormInterview $interview): CandidateCommandProfile
    {
        $answers = $this->getAnswersMap($interview);
        $candidateId = $interview->pool_candidate_id
            ?? $interview->meta['candidate_id']
            ?? null;

        if (!$candidateId) {
            throw new \InvalidArgumentException('No candidate_id found on interview');
        }

        $vesselExp = $this->parseVesselHistory($answers['VESSEL_HISTORY'] ?? '');
        $dwtHistory = $this->extractDwtHistory($vesselExp);
        $autoExposure = $this->parseAutomationExposure(
            ($answers['BRIDGE_TECHNOLOGY'] ?? '') . ' ' .
            ($answers['PROPULSION_SYSTEMS'] ?? '') . ' ' .
            ($answers['MANUAL_NAVIGATION'] ?? '')
        );
        $cargoHistory = $this->parseCargoHistory($answers['CARGO_OPERATIONS'] ?? '');
        $tradingAreas = $this->parseTradingAreas(
            ($answers['TRADING_AREAS'] ?? '') . ' ' .
            ($answers['PORT_COMPLEXITY'] ?? '')
        );
        $crewScale = $this->parseCrewScale($answers['CREW_MANAGEMENT'] ?? '');
        $incidentHistory = $this->parseIncidentHistory(
            ($answers['INCIDENT_HISTORY'] ?? '') . ' ' .
            ($answers['CARGO_INCIDENTS'] ?? '') . ' ' .
            ($answers['WEATHER_OPERATIONS'] ?? '')
        );

        // Calculate identity confidence
        $confidenceScore = $this->calculateConfidence($answers);

        // Calculate completeness from available fields (0-100)
        $completeness = $this->calculateCompleteness(
            $vesselExp, $dwtHistory, $autoExposure, $cargoHistory,
            $tradingAreas, $crewScale, $incidentHistory
        );

        $profile = CandidateCommandProfile::updateOrCreate(
            ['candidate_id' => $candidateId],
            [
                'raw_identity_answers' => $answers,
                'vessel_experience' => $vesselExp,
                'dwt_history' => $dwtHistory,
                'automation_exposure' => $autoExposure,
                'cargo_history' => $cargoHistory,
                'trading_areas' => $tradingAreas,
                'crew_scale_history' => $crewScale,
                'incident_history' => $incidentHistory,
                'identity_confidence_score' => $confidenceScore,
                'source' => 'phase1',
                'completeness_pct' => $completeness,
                'generated_at' => now(),
            ]
        );

        Log::info('ProfileExtractor: profile extracted', [
            'candidate_id' => $candidateId,
            'interview_id' => $interview->id,
            'confidence' => $confidenceScore,
            'vessel_types_count' => count($vesselExp),
            'trading_areas_count' => count($tradingAreas),
        ]);

        return $profile;
    }

    /**
     * Validate Phase-1 answers are sufficient for completion.
     * Returns array of missing required fields.
     */
    public function validateForCompletion(FormInterview $interview): array
    {
        $answers = $this->getAnswersMap($interview);
        $required = ['VESSEL_HISTORY', 'TRADING_AREAS', 'CARGO_OPERATIONS', 'CREW_MANAGEMENT', 'CERTIFICATION_STATUS'];
        $missing = [];

        foreach ($required as $code) {
            $text = trim($answers[$code] ?? '');
            if (mb_strlen($text) < 20) {
                $missing[] = $code;
            }
        }

        return $missing;
    }

    // ── Private parsers ──

    private function getAnswersMap(FormInterview $interview): array
    {
        $map = [];
        $template = $interview->template_json ? json_decode($interview->template_json, true) : null;

        // Build slot → code mapping from template
        $slotToCode = [];
        if ($template && isset($template['sections'])) {
            foreach ($template['sections'] as $section) {
                foreach ($section['questions'] ?? [] as $q) {
                    $slotToCode[$q['slot']] = $q['code'];
                }
            }
        }

        // Map answers by question code
        foreach ($interview->answers as $answer) {
            $code = $slotToCode[$answer->slot] ?? "SLOT_{$answer->slot}";
            $map[$code] = $answer->answer_text ?? '';
        }

        return $map;
    }

    /**
     * Parse vessel history text into structured entries.
     */
    private function parseVesselHistory(string $text): array
    {
        if (empty(trim($text))) return [];

        $vessels = [];
        $textLower = mb_strtolower($text);

        foreach (self::VESSEL_TYPES as $type) {
            $searchTerms = [
                strtolower($type),
                strtolower(str_replace('_', ' ', $type)),
            ];

            foreach ($searchTerms as $term) {
                if (str_contains($textLower, $term)) {
                    $vessels[] = [
                        'vessel_type' => $type,
                        'raw_mention' => $this->extractContext($text, $term),
                    ];
                    break;
                }
            }
        }

        // Extract DWT numbers
        preg_match_all('/(\d{1,3}[.,]?\d{0,3})\s*(?:dwt|DWT|tons?|tonne?s?|k\s*dwt)/i', $text, $dwtMatches);
        foreach ($dwtMatches[1] as $idx => $dwtStr) {
            $dwt = (int) str_replace([',', '.'], '', $dwtStr);
            // Handle "k DWT" → multiply by 1000
            if (stripos($dwtMatches[0][$idx], 'k') !== false && $dwt < 1000) {
                $dwt *= 1000;
            }
            if ($dwt > 0 && isset($vessels[$idx])) {
                $vessels[$idx]['dwt'] = $dwt;
            }
        }

        return $vessels;
    }

    /**
     * Extract DWT min/max from vessel experience.
     */
    private function extractDwtHistory(array $vesselExp): array
    {
        $dwts = array_filter(array_column($vesselExp, 'dwt'));

        if (empty($dwts)) {
            return ['min' => null, 'max' => null];
        }

        return [
            'min' => min($dwts),
            'max' => max($dwts),
        ];
    }

    /**
     * Parse automation exposure from bridge technology text.
     */
    private function parseAutomationExposure(string $text): array
    {
        if (empty(trim($text))) return ['levels' => [], 'equipment' => []];

        $textLower = mb_strtolower($text);
        $levels = [];
        $equipment = [];

        foreach (self::AUTOMATION_KEYWORDS['levels'] as $level => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($textLower, strtolower($kw))) {
                    $levels[] = $level;
                    break;
                }
            }
        }

        foreach (self::AUTOMATION_KEYWORDS['equipment'] as $eq) {
            if (stripos($text, $eq) !== false) {
                $equipment[] = $eq;
            }
        }

        return [
            'levels' => array_unique($levels),
            'equipment' => array_unique($equipment),
        ];
    }

    /**
     * Parse cargo types from cargo operations text.
     */
    private function parseCargoHistory(string $text): array
    {
        if (empty(trim($text))) return [];

        $textLower = mb_strtolower($text);
        $found = [];

        foreach (self::CARGO_TYPES as $type) {
            $searchTerms = [
                strtolower($type),
                strtolower(str_replace('_', ' ', $type)),
            ];

            foreach ($searchTerms as $term) {
                if (str_contains($textLower, $term)) {
                    $found[] = $type;
                    break;
                }
            }
        }

        return array_unique($found);
    }

    /**
     * Parse trading areas from text.
     */
    private function parseTradingAreas(string $text): array
    {
        if (empty(trim($text))) return [];

        $textLower = mb_strtolower($text);
        $found = [];

        // Direct taxonomy match
        foreach (self::TRADING_AREAS as $area) {
            $searchTerms = [
                strtolower($area),
                strtolower(str_replace('_', ' ', $area)),
            ];

            foreach ($searchTerms as $term) {
                if (str_contains($textLower, $term)) {
                    $found[] = $area;
                    break;
                }
            }
        }

        // Keyword-based inference
        $areaKeywords = [
            'inland_waterway' => ['river', 'canal', 'inland', 'barge'],
            'coastal_domestic' => ['coastal', 'cabotage', 'domestic'],
            'short_sea' => ['short sea', 'intra-european', 'feeder'],
            'ocean_atlantic' => ['atlantic', 'transatlantic'],
            'ocean_pacific' => ['pacific', 'transpacific'],
            'ocean_indian' => ['indian ocean'],
            'persian_gulf' => ['persian gulf', 'arabian gulf', 'gulf region', 'middle east'],
            'north_sea' => ['north sea', 'norwegian sea'],
            'mediterranean' => ['mediterranean', 'med sea'],
            'black_sea' => ['black sea', 'bosphorus', 'turkish straits'],
            'baltic' => ['baltic'],
            'red_sea' => ['red sea', 'suez'],
            'strait_transit' => ['strait', 'suez canal', 'panama canal', 'malacca'],
            'polar_regions' => ['arctic', 'antarctic', 'ice navigation', 'polar'],
        ];

        foreach ($areaKeywords as $area => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($textLower, $kw) && !in_array($area, $found)) {
                    $found[] = $area;
                    break;
                }
            }
        }

        return array_unique($found);
    }

    /**
     * Parse crew scale from crew management text.
     */
    private function parseCrewScale(string $text): array
    {
        if (empty(trim($text))) return ['min' => null, 'max' => null];

        // Extract numbers that could be crew counts
        preg_match_all('/(\d{1,4})\s*(?:crew|person|people|man|men|sailor|seafarer|member)/i', $text, $matches);

        $numbers = array_map('intval', $matches[1]);

        // Also catch standalone numbers in context of "managed X"
        preg_match_all('/(?:managed|supervised|led|commanded|oversaw)\s+(\d{1,4})/i', $text, $matches2);
        $numbers = array_merge($numbers, array_map('intval', $matches2[1]));

        // Filter reasonable crew sizes (2-2000)
        $numbers = array_filter($numbers, fn($n) => $n >= 2 && $n <= 2000);

        if (empty($numbers)) {
            return ['min' => null, 'max' => null];
        }

        return [
            'min' => min($numbers),
            'max' => max($numbers),
        ];
    }

    /**
     * Parse incident history from text.
     */
    private function parseIncidentHistory(string $text): array
    {
        if (empty(trim($text))) return ['total' => 0, 'types' => [], 'severity_max' => null];

        $textLower = mb_strtolower($text);

        $incidentTypes = [
            'grounding' => ['grounding', 'grounded', 'ran aground'],
            'collision' => ['collision', 'collided', 'contact with'],
            'near_miss' => ['near miss', 'near-miss', 'close call', 'close quarter'],
            'fire' => ['fire', 'explosion', 'ignition'],
            'flooding' => ['flooding', 'water ingress', 'hull breach'],
            'man_overboard' => ['man overboard', 'mob', 'person overboard'],
            'spill' => ['spill', 'oil spill', 'cargo spill', 'pollution'],
            'mechanical_failure' => ['engine failure', 'blackout', 'power failure', 'breakdown'],
            'cargo_damage' => ['cargo damage', 'cargo loss', 'contamination'],
            'piracy' => ['piracy', 'pirate', 'armed robbery'],
            'medical' => ['medical emergency', 'medevac', 'injury'],
        ];

        $found = [];
        foreach ($incidentTypes as $type => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($textLower, $kw)) {
                    $found[] = $type;
                    break;
                }
            }
        }

        // Severity classification
        $severityMap = [
            'critical' => ['collision', 'grounding', 'fire', 'flooding', 'man_overboard', 'spill'],
            'major' => ['mechanical_failure', 'piracy', 'cargo_damage'],
            'minor' => ['near_miss', 'medical'],
        ];

        $maxSeverity = null;
        foreach (['critical', 'major', 'minor'] as $sev) {
            if (!empty(array_intersect($found, $severityMap[$sev]))) {
                $maxSeverity = $sev;
                break;
            }
        }

        return [
            'total' => count($found),
            'types' => array_unique($found),
            'severity_max' => $maxSeverity,
        ];
    }

    /**
     * Calculate identity confidence score (0-100).
     */
    private function calculateConfidence(array $answers): float
    {
        $requiredCodes = [
            'VESSEL_HISTORY', 'BRIDGE_TECHNOLOGY', 'TRADING_AREAS',
            'CARGO_OPERATIONS', 'CREW_MANAGEMENT', 'INCIDENT_HISTORY',
            'CERTIFICATION_STATUS',
        ];

        $optionalCodes = [
            'PROPULSION_SYSTEMS', 'PORT_COMPLEXITY', 'CARGO_INCIDENTS',
            'WEATHER_OPERATIONS', 'MANUAL_NAVIGATION',
        ];

        $score = 0.0;

        // Required fields: 10 points each (7 * 10 = 70 max)
        foreach ($requiredCodes as $code) {
            $text = trim($answers[$code] ?? '');
            $len = mb_strlen($text);

            if ($len >= 100) $score += 10;
            elseif ($len >= 50) $score += 7;
            elseif ($len >= 20) $score += 4;
            elseif ($len > 0) $score += 1;
        }

        // Optional fields: 6 points each (5 * 6 = 30 max)
        foreach ($optionalCodes as $code) {
            $text = trim($answers[$code] ?? '');
            $len = mb_strlen($text);

            if ($len >= 50) $score += 6;
            elseif ($len >= 20) $score += 3;
            elseif ($len > 0) $score += 1;
        }

        return min(100, round($score, 2));
    }

    /**
     * Calculate completeness percentage for Phase-1 extracted profile.
     * Max 100; realistic range 70-95 for a well-completed interview.
     */
    private function calculateCompleteness(
        array $vesselExp, ?array $dwtHistory, ?array $autoExposure,
        array $cargoHistory, array $tradingAreas, ?array $crewScale,
        ?array $incidentHistory
    ): int {
        $score = 0;

        // Vessel experience (max 25)
        if (!empty($vesselExp)) $score += min(25, count($vesselExp) * 5);

        // DWT history (max 10)
        if (!empty($dwtHistory)) $score += 10;

        // Automation (max 10)
        if (!empty($autoExposure)) $score += 10;

        // Cargo (max 15)
        if (!empty($cargoHistory)) $score += min(15, count($cargoHistory) * 5);

        // Trading areas (max 15)
        if (!empty($tradingAreas)) $score += min(15, count($tradingAreas) * 5);

        // Crew scale (max 10)
        if (!empty($crewScale)) $score += 10;

        // Incidents (max 15)
        if ($incidentHistory !== null) $score += 15;

        return min(100, max(70, $score)); // Phase-1 floor at 70%
    }

    /**
     * Extract surrounding context of a keyword mention.
     */
    private function extractContext(string $text, string $keyword, int $chars = 60): string
    {
        $pos = mb_stripos($text, $keyword);
        if ($pos === false) return '';

        $start = max(0, $pos - 20);
        $length = mb_strlen($keyword) + $chars;

        return trim(mb_substr($text, $start, $length));
    }
}
