<?php

namespace Database\Seeders;

use App\Models\CommandClass;
use Illuminate\Database\Seeder;

class CommandClassSeeder extends Seeder
{
    public function run(): void
    {
        $classes = $this->getDefinitions();

        foreach ($classes as $def) {
            CommandClass::updateOrCreate(
                ['code' => $def['code']],
                $def
            );
        }

        $this->command->info('Seeded ' . count($classes) . ' command classes.');
    }

    private function getDefinitions(): array
    {
        return [
            // ─── RIVER ───
            [
                'code' => 'RIVER',
                'name_en' => 'River Command',
                'name_tr' => 'Nehir Komutanlığı',
                'vessel_types' => [
                    'river_barge', 'river_tanker', 'river_pusher', 'river_passenger',
                    'river_cargo', 'river_ferry',
                ],
                'dwt_min' => 0,
                'dwt_max' => 5000,
                'trading_areas' => ['inland_waterway', 'river_estuary'],
                'automation_levels' => ['manual', 'basic'],
                'crew_min' => 2,
                'crew_max' => 15,
                'cargo_types' => ['bulk_dry', 'liquid_cargo', 'passengers', 'general_cargo'],
                'risk_profile' => [
                    'navigation_complexity' => 'LOW',
                    'weather_exposure' => 'LOW',
                    'regulatory_density' => 'MEDIUM',
                    'port_density' => 'HIGH',
                    'collision_risk' => 'MEDIUM',
                    'environmental_risk' => 'MEDIUM',
                ],
                'certifications_required' => ['inland_navigation', 'basic_safety'],
                'weight_vector' => [
                    'NAV_COMPLEX' => 0.8,
                    'CMD_SCALE' => 0.6,
                    'TECH_DEPTH' => 0.7,
                    'RISK_MGMT' => 0.8,
                    'CREW_LEAD' => 0.6,
                    'AUTO_DEP' => 0.5,
                    'CRISIS_RSP' => 0.7,
                ],
                'special_considerations' => null,
                'sub_classes' => null,
            ],

            // ─── COASTAL ───
            [
                'code' => 'COASTAL',
                'name_en' => 'Coastal Command',
                'name_tr' => 'Kıyı Komutanlığı',
                'vessel_types' => [
                    'coaster', 'coastal_tanker', 'coastal_ferry', 'tug',
                    'supply_vessel', 'pilot_vessel',
                ],
                'dwt_min' => 500,
                'dwt_max' => 15000,
                'trading_areas' => ['coastal_domestic', 'coastal_regional'],
                'automation_levels' => ['basic', 'standard'],
                'crew_min' => 5,
                'crew_max' => 25,
                'cargo_types' => [
                    'general_cargo', 'bulk_dry', 'liquid_cargo',
                    'passengers', 'project_cargo',
                ],
                'risk_profile' => [
                    'navigation_complexity' => 'MEDIUM',
                    'weather_exposure' => 'MEDIUM',
                    'regulatory_density' => 'MEDIUM',
                    'port_density' => 'HIGH',
                    'collision_risk' => 'MEDIUM',
                    'environmental_risk' => 'MEDIUM',
                ],
                'certifications_required' => ['STCW_basic', 'coastal_master', 'GMDSS_ROC'],
                'weight_vector' => [
                    'NAV_COMPLEX' => 1.0,
                    'CMD_SCALE' => 0.8,
                    'TECH_DEPTH' => 0.8,
                    'RISK_MGMT' => 0.9,
                    'CREW_LEAD' => 0.7,
                    'AUTO_DEP' => 0.7,
                    'CRISIS_RSP' => 0.8,
                ],
                'special_considerations' => null,
                'sub_classes' => null,
            ],

            // ─── SHORT SEA ───
            [
                'code' => 'SHORT_SEA',
                'name_en' => 'Short Sea Command',
                'name_tr' => 'Kısa Mesafe Deniz Komutanlığı',
                'vessel_types' => [
                    'general_cargo', 'multi_purpose', 'small_container',
                    'ro_ro', 'short_sea_tanker',
                ],
                'dwt_min' => 3000,
                'dwt_max' => 30000,
                'trading_areas' => ['short_sea', 'regional_international'],
                'automation_levels' => ['standard', 'integrated'],
                'crew_min' => 10,
                'crew_max' => 25,
                'cargo_types' => [
                    'containers', 'general_cargo', 'ro_ro_units',
                    'project_cargo', 'bulk_dry',
                ],
                'risk_profile' => [
                    'navigation_complexity' => 'MEDIUM',
                    'weather_exposure' => 'MEDIUM',
                    'regulatory_density' => 'HIGH',
                    'port_density' => 'HIGH',
                    'collision_risk' => 'MEDIUM',
                    'environmental_risk' => 'MEDIUM',
                ],
                'certifications_required' => ['STCW_full', 'ECDIS_generic', 'ARPA', 'BRM'],
                'weight_vector' => [
                    'NAV_COMPLEX' => 1.0,
                    'CMD_SCALE' => 0.9,
                    'TECH_DEPTH' => 0.9,
                    'RISK_MGMT' => 1.0,
                    'CREW_LEAD' => 0.8,
                    'AUTO_DEP' => 0.9,
                    'CRISIS_RSP' => 0.9,
                ],
                'special_considerations' => null,
                'sub_classes' => null,
            ],

            // ─── DEEP SEA ───
            [
                'code' => 'DEEP_SEA',
                'name_en' => 'Deep Sea Command',
                'name_tr' => 'Açık Deniz Komutanlığı',
                'vessel_types' => [
                    'bulk_carrier', 'general_cargo_ocean', 'reefer', 'heavy_lift',
                ],
                'dwt_min' => 15000,
                'dwt_max' => 120000,
                'trading_areas' => [
                    'ocean_atlantic', 'ocean_pacific', 'ocean_indian', 'worldwide',
                ],
                'automation_levels' => ['standard', 'integrated'],
                'crew_min' => 18,
                'crew_max' => 30,
                'cargo_types' => [
                    'bulk_dry', 'bulk_grain', 'steel_coils', 'reefer_cargo', 'heavy_lift',
                ],
                'risk_profile' => [
                    'navigation_complexity' => 'HIGH',
                    'weather_exposure' => 'HIGH',
                    'regulatory_density' => 'HIGH',
                    'port_density' => 'LOW',
                    'collision_risk' => 'LOW',
                    'environmental_risk' => 'HIGH',
                ],
                'certifications_required' => [
                    'STCW_full', 'ECDIS_generic', 'ARPA', 'BRM', 'SSO', 'ISPS',
                ],
                'weight_vector' => [
                    'NAV_COMPLEX' => 1.0,
                    'CMD_SCALE' => 1.0,
                    'TECH_DEPTH' => 1.0,
                    'RISK_MGMT' => 1.0,
                    'CREW_LEAD' => 1.0,
                    'AUTO_DEP' => 1.0,
                    'CRISIS_RSP' => 1.0,
                ],
                'special_considerations' => null,
                'sub_classes' => null,
            ],

            // ─── CONTAINER ULCS ───
            [
                'code' => 'CONTAINER_ULCS',
                'name_en' => 'Container / ULCS Command',
                'name_tr' => 'Konteyner / ULCS Komutanlığı',
                'vessel_types' => [
                    'container_feeder', 'container_panamax',
                    'container_post_panamax', 'container_ulcs',
                ],
                'dwt_min' => 10000,
                'dwt_max' => 250000,
                'trading_areas' => [
                    'ocean_atlantic', 'ocean_pacific', 'worldwide', 'strait_transit',
                ],
                'automation_levels' => ['integrated', 'high_automation'],
                'crew_min' => 20,
                'crew_max' => 30,
                'cargo_types' => [
                    'containers', 'reefer_containers', 'dangerous_goods_containers',
                ],
                'risk_profile' => [
                    'navigation_complexity' => 'HIGH',
                    'weather_exposure' => 'HIGH',
                    'regulatory_density' => 'CRITICAL',
                    'port_density' => 'MEDIUM',
                    'collision_risk' => 'MEDIUM',
                    'environmental_risk' => 'HIGH',
                ],
                'certifications_required' => [
                    'STCW_full', 'ECDIS_type_specific', 'ARPA', 'BRM', 'SSO', 'ISPS', 'DG_handling',
                ],
                'weight_vector' => [
                    'NAV_COMPLEX' => 1.2,
                    'CMD_SCALE' => 1.3,
                    'TECH_DEPTH' => 1.1,
                    'RISK_MGMT' => 1.1,
                    'CREW_LEAD' => 1.0,
                    'AUTO_DEP' => 1.2,
                    'CRISIS_RSP' => 1.1,
                ],
                'special_considerations' => [
                    'schedule_pressure', 'port_turnaround',
                    'container_stowage', 'parametric_rolling',
                ],
                'sub_classes' => null,
            ],

            // ─── TANKER ───
            [
                'code' => 'TANKER',
                'name_en' => 'Tanker Command',
                'name_tr' => 'Tanker Komutanlığı',
                'vessel_types' => [
                    'product_tanker', 'crude_tanker', 'chemical_tanker',
                    'VLCC', 'aframax', 'suezmax',
                ],
                'dwt_min' => 5000,
                'dwt_max' => 320000,
                'trading_areas' => [
                    'coastal_regional', 'short_sea', 'ocean_atlantic',
                    'ocean_pacific', 'worldwide', 'persian_gulf',
                ],
                'automation_levels' => ['standard', 'integrated', 'high_automation'],
                'crew_min' => 20,
                'crew_max' => 35,
                'cargo_types' => [
                    'crude_oil', 'refined_products', 'chemicals', 'vegetable_oils',
                ],
                'risk_profile' => [
                    'navigation_complexity' => 'HIGH',
                    'weather_exposure' => 'HIGH',
                    'regulatory_density' => 'CRITICAL',
                    'port_density' => 'LOW',
                    'collision_risk' => 'MEDIUM',
                    'environmental_risk' => 'CRITICAL',
                ],
                'certifications_required' => [
                    'STCW_full', 'tanker_familiarization', 'oil_tanker_specialized',
                    'chemical_tanker_specialized', 'ISGOTT', 'COW',
                ],
                'weight_vector' => [
                    'NAV_COMPLEX' => 1.0,
                    'CMD_SCALE' => 1.1,
                    'TECH_DEPTH' => 1.3,
                    'RISK_MGMT' => 1.3,
                    'CREW_LEAD' => 1.0,
                    'AUTO_DEP' => 1.0,
                    'CRISIS_RSP' => 1.3,
                ],
                'special_considerations' => [
                    'cargo_compatibility', 'tank_cleaning', 'inert_gas',
                    'vapor_recovery', 'STS_operations',
                ],
                'sub_classes' => [
                    ['code' => 'PRODUCT_TANKER', 'dwt_min' => 5000, 'dwt_max' => 60000, 'cargo' => 'refined_products'],
                    ['code' => 'CHEMICAL_TANKER', 'dwt_min' => 5000, 'dwt_max' => 50000, 'cargo' => 'chemicals'],
                    ['code' => 'CRUDE_TANKER', 'dwt_min' => 80000, 'dwt_max' => 320000, 'cargo' => 'crude_oil'],
                ],
            ],

            // ─── LNG ───
            [
                'code' => 'LNG',
                'name_en' => 'LNG Command',
                'name_tr' => 'LNG Komutanlığı',
                'vessel_types' => [
                    'LNG_carrier', 'FSRU', 'LNG_bunkering_vessel',
                ],
                'dwt_min' => 20000,
                'dwt_max' => 180000,
                'trading_areas' => [
                    'ocean_atlantic', 'ocean_pacific', 'worldwide',
                    'persian_gulf', 'lng_corridor',
                ],
                'automation_levels' => ['high_automation', 'fully_integrated'],
                'crew_min' => 25,
                'crew_max' => 35,
                'cargo_types' => ['LNG', 'LPG', 'ethane'],
                'risk_profile' => [
                    'navigation_complexity' => 'HIGH',
                    'weather_exposure' => 'HIGH',
                    'regulatory_density' => 'CRITICAL',
                    'port_density' => 'LOW',
                    'collision_risk' => 'LOW',
                    'environmental_risk' => 'CRITICAL',
                ],
                'certifications_required' => [
                    'STCW_full', 'IGC_code', 'gas_tanker_advanced',
                    'ESD_systems', 'cargo_containment_systems',
                ],
                'weight_vector' => [
                    'NAV_COMPLEX' => 1.0,
                    'CMD_SCALE' => 1.1,
                    'TECH_DEPTH' => 1.4,
                    'RISK_MGMT' => 1.4,
                    'CREW_LEAD' => 1.0,
                    'AUTO_DEP' => 1.3,
                    'CRISIS_RSP' => 1.4,
                ],
                'special_considerations' => [
                    'boil_off_management', 'cargo_containment',
                    'membrane_vs_moss', 'reliquefaction',
                    'STS_LNG', 'terminal_compatibility',
                ],
                'sub_classes' => null,
            ],

            // ─── OFFSHORE ───
            [
                'code' => 'OFFSHORE',
                'name_en' => 'Offshore Command',
                'name_tr' => 'Açık Deniz Operasyon Komutanlığı',
                'vessel_types' => [
                    'PSV', 'AHTS', 'DSV', 'pipe_layer', 'crane_vessel',
                    'FPSO', 'wind_farm_vessel', 'jack_up',
                ],
                'dwt_min' => 1000,
                'dwt_max' => 80000,
                'trading_areas' => [
                    'north_sea', 'west_africa', 'gulf_of_mexico',
                    'southeast_asia', 'brazil_pre_salt',
                ],
                'automation_levels' => ['standard', 'integrated', 'DP_class_2', 'DP_class_3'],
                'crew_min' => 15,
                'crew_max' => 100,
                'cargo_types' => [
                    'deck_cargo', 'mud', 'cement', 'fuel',
                    'pipes', 'subsea_equipment',
                ],
                'risk_profile' => [
                    'navigation_complexity' => 'CRITICAL',
                    'weather_exposure' => 'CRITICAL',
                    'regulatory_density' => 'CRITICAL',
                    'port_density' => 'LOW',
                    'collision_risk' => 'HIGH',
                    'environmental_risk' => 'CRITICAL',
                ],
                'certifications_required' => [
                    'STCW_full', 'DP_operator', 'DP_advanced',
                    'BOSIET', 'HUET', 'OPITO_basic',
                ],
                'weight_vector' => [
                    'NAV_COMPLEX' => 1.3,
                    'CMD_SCALE' => 1.2,
                    'TECH_DEPTH' => 1.3,
                    'RISK_MGMT' => 1.3,
                    'CREW_LEAD' => 1.2,
                    'AUTO_DEP' => 1.4,
                    'CRISIS_RSP' => 1.3,
                ],
                'special_considerations' => [
                    'DP_operations', 'anchor_handling',
                    'weather_window_management', 'heli_operations',
                    'subsea_coordination',
                ],
                'sub_classes' => null,
            ],

            // ─── PASSENGER ───
            [
                'code' => 'PASSENGER',
                'name_en' => 'Passenger Command',
                'name_tr' => 'Yolcu Gemisi Komutanlığı',
                'vessel_types' => [
                    'cruise_ship', 'ro_pax', 'coastal_ferry',
                    'expedition_vessel', 'yacht_large',
                ],
                'dwt_min' => 2000,
                'dwt_max' => 100000,
                'trading_areas' => [
                    'coastal_domestic', 'short_sea', 'ocean_atlantic',
                    'worldwide', 'polar_regions',
                ],
                'automation_levels' => ['integrated', 'high_automation'],
                'crew_min' => 50,
                'crew_max' => 2000,
                'cargo_types' => ['passengers', 'vehicles', 'limited_freight'],
                'risk_profile' => [
                    'navigation_complexity' => 'HIGH',
                    'weather_exposure' => 'MEDIUM',
                    'regulatory_density' => 'CRITICAL',
                    'port_density' => 'HIGH',
                    'collision_risk' => 'HIGH',
                    'environmental_risk' => 'HIGH',
                ],
                'certifications_required' => [
                    'STCW_full', 'crowd_management', 'crisis_leadership',
                    'passenger_safety', 'polar_code',
                ],
                'weight_vector' => [
                    'NAV_COMPLEX' => 1.1,
                    'CMD_SCALE' => 1.4,
                    'TECH_DEPTH' => 0.9,
                    'RISK_MGMT' => 1.2,
                    'CREW_LEAD' => 1.4,
                    'AUTO_DEP' => 1.1,
                    'CRISIS_RSP' => 1.4,
                ],
                'special_considerations' => [
                    'pax_safety_culture', 'muster_drill',
                    'medical_emergencies', 'tendering',
                    'environmental_compliance', 'port_state_inspections',
                ],
                'sub_classes' => null,
            ],
        ];
    }
}
