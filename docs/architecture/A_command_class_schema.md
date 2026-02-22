# A — Command Class Schema

## Maritime Command Classification System

### Principle

A seafarer is NOT classified by job title.
Classification is derived from **operational profile intersection**:

```
command_class = f(vessel_type, dwt_range, trading_area, automation_level, cargo_type)
```

No two "Captains" are the same. A river barge captain and an ULCS container captain
operate in fundamentally different decision domains.

---

## 1. Command Classes

### 1.1 RIVER_COMMAND
```
code: RIVER
vessel_types: [river_barge, river_tanker, river_pusher, river_passenger]
dwt_range: [0, 5000]
trading_areas: [inland_waterway, river_estuary]
automation_level: [manual, basic]
crew_scale: [2, 15]
cargo_types: [bulk_dry, liquid_cargo, passengers, general_cargo]
risk_profile: {
    navigation_complexity: LOW,
    weather_exposure: LOW,
    regulatory_density: MEDIUM,
    port_density: HIGH,
    collision_risk: MEDIUM,
    environmental_risk: MEDIUM
}
certifications_required: [inland_navigation, basic_safety]
typical_voyage_duration_days: [0.5, 3]
```

### 1.2 COASTAL_COMMAND
```
code: COASTAL
vessel_types: [coaster, coastal_tanker, coastal_ferry, tug, supply_vessel]
dwt_range: [500, 15000]
trading_areas: [coastal_domestic, coastal_regional]
automation_level: [basic, standard]
crew_scale: [5, 25]
cargo_types: [general_cargo, bulk_dry, liquid_cargo, passengers, project_cargo]
risk_profile: {
    navigation_complexity: MEDIUM,
    weather_exposure: MEDIUM,
    regulatory_density: MEDIUM,
    port_density: HIGH,
    collision_risk: MEDIUM,
    environmental_risk: MEDIUM
}
certifications_required: [STCW_basic, coastal_master, GMDSS_ROC]
typical_voyage_duration_days: [1, 7]
```

### 1.3 SHORT_SEA_COMMAND
```
code: SHORT_SEA
vessel_types: [general_cargo, multi_purpose, small_container, ro_ro, short_sea_tanker]
dwt_range: [3000, 30000]
trading_areas: [short_sea, regional_international]
automation_level: [standard, integrated]
crew_scale: [10, 25]
cargo_types: [containers, general_cargo, ro_ro_units, project_cargo, bulk_dry]
risk_profile: {
    navigation_complexity: MEDIUM,
    weather_exposure: MEDIUM,
    regulatory_density: HIGH,
    port_density: HIGH,
    collision_risk: MEDIUM,
    environmental_risk: MEDIUM
}
certifications_required: [STCW_full, ECDIS_generic, ARPA, BRM]
typical_voyage_duration_days: [2, 14]
```

### 1.4 DEEP_SEA_COMMAND
```
code: DEEP_SEA
vessel_types: [bulk_carrier, general_cargo_ocean, reefer, heavy_lift]
dwt_range: [15000, 120000]
trading_areas: [ocean_atlantic, ocean_pacific, ocean_indian, worldwide]
automation_level: [standard, integrated]
crew_scale: [18, 30]
cargo_types: [bulk_dry, bulk_grain, steel_coils, reefer_cargo, heavy_lift]
risk_profile: {
    navigation_complexity: HIGH,
    weather_exposure: HIGH,
    regulatory_density: HIGH,
    port_density: LOW,
    collision_risk: LOW,
    environmental_risk: HIGH
}
certifications_required: [STCW_full, ECDIS_generic, ARPA, BRM, SSO, ISPS]
typical_voyage_duration_days: [14, 60]
```

### 1.5 CONTAINER_ULCS_COMMAND
```
code: CONTAINER_ULCS
vessel_types: [container_feeder, container_panamax, container_post_panamax, container_ulcs]
dwt_range: [10000, 250000]
trading_areas: [ocean_atlantic, ocean_pacific, worldwide, strait_transit]
automation_level: [integrated, high_automation]
crew_scale: [20, 30]
cargo_types: [containers, reefer_containers, dangerous_goods_containers]
risk_profile: {
    navigation_complexity: HIGH,
    weather_exposure: HIGH,
    regulatory_density: CRITICAL,
    port_density: MEDIUM,
    collision_risk: MEDIUM,
    environmental_risk: HIGH
}
certifications_required: [STCW_full, ECDIS_type_specific, ARPA, BRM, SSO, ISPS, DG_handling]
typical_voyage_duration_days: [7, 45]
special_considerations: [schedule_pressure, port_turnaround, container_stowage, parametric_rolling]
```

### 1.6 TANKER_COMMAND
```
code: TANKER
vessel_types: [product_tanker, crude_tanker, chemical_tanker, VLCC, aframax, suezmax]
dwt_range: [5000, 320000]
trading_areas: [coastal_regional, short_sea, ocean_atlantic, ocean_pacific, worldwide, persian_gulf]
automation_level: [standard, integrated, high_automation]
crew_scale: [20, 35]
cargo_types: [crude_oil, refined_products, chemicals, vegetable_oils]
risk_profile: {
    navigation_complexity: HIGH,
    weather_exposure: HIGH,
    regulatory_density: CRITICAL,
    port_density: LOW,
    collision_risk: MEDIUM,
    environmental_risk: CRITICAL
}
certifications_required: [STCW_full, tanker_familiarization, oil_tanker_specialized, chemical_tanker_specialized, ISGOTT, COW]
sub_classes: [
    { code: PRODUCT_TANKER, dwt: [5000, 60000], cargo: refined_products },
    { code: CHEMICAL_TANKER, dwt: [5000, 50000], cargo: chemicals, extra_cert: chemical_tanker_advanced },
    { code: CRUDE_TANKER, dwt: [80000, 320000], cargo: crude_oil },
]
typical_voyage_duration_days: [5, 45]
special_considerations: [cargo_compatibility, tank_cleaning, inert_gas, vapor_recovery, STS_operations]
```

### 1.7 LNG_COMMAND
```
code: LNG
vessel_types: [LNG_carrier, FSRU, LNG_bunkering_vessel]
dwt_range: [20000, 180000]
trading_areas: [ocean_atlantic, ocean_pacific, worldwide, persian_gulf, lng_corridor]
automation_level: [high_automation, fully_integrated]
crew_scale: [25, 35]
cargo_types: [LNG, LPG, ethane]
risk_profile: {
    navigation_complexity: HIGH,
    weather_exposure: HIGH,
    regulatory_density: CRITICAL,
    port_density: LOW,
    collision_risk: LOW,
    environmental_risk: CRITICAL
}
certifications_required: [STCW_full, IGC_code, gas_tanker_advanced, ESD_systems, cargo_containment_systems]
typical_voyage_duration_days: [10, 35]
special_considerations: [boil_off_management, cargo_containment, membrane_vs_moss, reliquefaction, STS_LNG, terminal_compatibility]
```

### 1.8 OFFSHORE_COMMAND
```
code: OFFSHORE
vessel_types: [PSV, AHTS, DSV, pipe_layer, crane_vessel, FPSO, wind_farm_vessel, jack_up]
dwt_range: [1000, 80000]
trading_areas: [north_sea, west_africa, gulf_of_mexico, southeast_asia, brazil_pre_salt]
automation_level: [standard, integrated, DP_class_2, DP_class_3]
crew_scale: [15, 100]
cargo_types: [deck_cargo, mud, cement, fuel, pipes, subsea_equipment]
risk_profile: {
    navigation_complexity: CRITICAL,
    weather_exposure: CRITICAL,
    regulatory_density: CRITICAL,
    port_density: LOW,
    collision_risk: HIGH,
    environmental_risk: CRITICAL
}
certifications_required: [STCW_full, DP_operator, DP_advanced, BOSIET, HUET, OPITO_basic]
typical_voyage_duration_days: [14, 42]
special_considerations: [DP_operations, anchor_handling, weather_window_management, heli_operations, subsea_coordination]
```

### 1.9 PASSENGER_COMMAND
```
code: PASSENGER
vessel_types: [cruise_ship, ro_pax, coastal_ferry, expedition_vessel, yacht_large]
dwt_range: [2000, 100000]
trading_areas: [coastal_domestic, short_sea, ocean_atlantic, worldwide, polar_regions]
automation_level: [integrated, high_automation]
crew_scale: [50, 2000]
cargo_types: [passengers, vehicles, limited_freight]
risk_profile: {
    navigation_complexity: HIGH,
    weather_exposure: MEDIUM,
    regulatory_density: CRITICAL,
    port_density: HIGH,
    collision_risk: HIGH,
    environmental_risk: HIGH
}
certifications_required: [STCW_full, crowd_management, crisis_leadership, passenger_safety, polar_code_if_applicable]
typical_voyage_duration_days: [0.5, 21]
special_considerations: [pax_safety_culture, muster_drill, medical_emergencies, tendering, environmental_compliance, port_state_inspections]
```

---

## 2. Command Profile Dimensions

Each candidate produces a `candidate_command_profile` with these dimensions:

```json
{
  "vessel_experience": [
    {
      "vessel_type": "chemical_tanker",
      "dwt": 45000,
      "years": 4.5,
      "rank_held": "chief_officer",
      "automation_level": "integrated"
    }
  ],
  "trading_areas": ["short_sea", "ocean_atlantic"],
  "dwt_range_commanded": { "min": 12000, "max": 45000 },
  "crew_scale_managed": { "min": 18, "max": 24 },
  "cargo_types_handled": ["chemicals", "vegetable_oils"],
  "automation_exposure": ["ECDIS", "integrated_bridge", "cargo_monitoring"],
  "incident_history": {
    "total_incidents": 1,
    "severity_max": "minor",
    "types": ["near_miss_collision"]
  },
  "weather_severity_exposure": "HIGH",
  "port_density_exposure": "MEDIUM",
  "command_history": {
    "total_command_months": 36,
    "highest_rank": "chief_officer",
    "independent_command": false
  },
  "certifications": {
    "stcw": true,
    "tanker_specialized": true,
    "chemical_advanced": true,
    "ecdis_type_specific": false,
    "dp_operator": false
  }
}
```

---

## 3. Command Class Detection Algorithm

```
function detectCommandClass(profile):
    scores = {}

    for each command_class in ALL_CLASSES:
        score = 0

        // Vessel type match (weight: 30%)
        vessel_overlap = intersect(profile.vessel_types, class.vessel_types)
        score += (vessel_overlap.count / class.vessel_types.count) * 30

        // DWT range overlap (weight: 20%)
        dwt_overlap = rangeOverlap(profile.dwt_range, class.dwt_range)
        score += dwt_overlap * 20

        // Trading area match (weight: 15%)
        area_overlap = intersect(profile.trading_areas, class.trading_areas)
        score += (area_overlap.count / class.trading_areas.count) * 15

        // Automation level match (weight: 10%)
        auto_match = profile.automation_level in class.automation_level
        score += auto_match * 10

        // Cargo type match (weight: 15%)
        cargo_overlap = intersect(profile.cargo_types, class.cargo_types)
        score += (cargo_overlap.count / class.cargo_types.count) * 15

        // Crew scale match (weight: 10%)
        crew_overlap = rangeOverlap(profile.crew_scale, class.crew_scale)
        score += crew_overlap * 10

        scores[class.code] = score

    // Return primary + secondary classes
    sorted = sortDesc(scores)
    return {
        primary: sorted[0],        // highest match
        secondary: sorted[1],      // next best (if > 40%)
        all_scores: scores
    }
```

---

## 4. Database Schema

```sql
CREATE TABLE command_classes (
    code VARCHAR(30) PRIMARY KEY,
    name_en VARCHAR(100) NOT NULL,
    name_tr VARCHAR(100) NOT NULL,
    vessel_types JSON NOT NULL,
    dwt_range_min INT NOT NULL,
    dwt_range_max INT NOT NULL,
    trading_areas JSON NOT NULL,
    automation_levels JSON NOT NULL,
    crew_scale_min INT NOT NULL,
    crew_scale_max INT NOT NULL,
    cargo_types JSON NOT NULL,
    risk_profile JSON NOT NULL,
    certifications_required JSON NOT NULL,
    special_considerations JSON DEFAULT NULL,
    sub_classes JSON DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE candidate_command_profiles (
    id CHAR(36) PRIMARY KEY,
    candidate_id CHAR(36) NOT NULL,
    form_interview_id CHAR(36) DEFAULT NULL,

    -- Extracted profile
    vessel_experience JSON NOT NULL,
    trading_areas JSON NOT NULL,
    dwt_range_min INT DEFAULT NULL,
    dwt_range_max INT DEFAULT NULL,
    crew_scale_min INT DEFAULT NULL,
    crew_scale_max INT DEFAULT NULL,
    cargo_types_handled JSON DEFAULT NULL,
    automation_exposure JSON DEFAULT NULL,
    incident_history JSON DEFAULT NULL,
    weather_severity VARCHAR(10) DEFAULT NULL,
    port_density VARCHAR(10) DEFAULT NULL,
    command_history JSON DEFAULT NULL,
    certifications JSON DEFAULT NULL,

    -- Detected command class
    primary_command_class VARCHAR(30) DEFAULT NULL,
    primary_class_score DECIMAL(5,2) DEFAULT NULL,
    secondary_command_class VARCHAR(30) DEFAULT NULL,
    secondary_class_score DECIMAL(5,2) DEFAULT NULL,
    all_class_scores JSON DEFAULT NULL,

    -- Metadata
    profile_version VARCHAR(10) DEFAULT 'v1',
    extracted_at TIMESTAMP DEFAULT NULL,
    extraction_source VARCHAR(20) DEFAULT 'form_interview',

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (candidate_id) REFERENCES candidates(id),
    INDEX idx_primary_class (primary_command_class),
    INDEX idx_candidate (candidate_id)
);
```
