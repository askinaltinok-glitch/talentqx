PURPOSE

This document defines the behavioral-technical DNA profile for every role in the Octopus-AI Maritime role registry.

It is derived from ROLE_CORE_REGISTRY_v1 and serves as the weighted foundation for:

scoring engine

interview engine

mismatch detection

role-fit engine

All values are on a 0.00 – 1.00 scale.

No engine may assign scores, generate questions, or evaluate fit without consulting this matrix.

DIMENSION DEFINITIONS

behavioral_weight
How much of this role's performance depends on interpersonal, leadership, stress management, and communication capability. Higher values mean behavioral assessment carries more weight in final scoring.

technical_weight
How much of this role's performance depends on domain-specific technical knowledge, procedural mastery, and equipment competence. Higher values mean technical assessment carries more weight in final scoring.

leadership_expectation
The degree to which this role is expected to lead, direct, mentor, or take command of people. 1.00 means full command authority. 0.00 means no leadership expected.

decision_exposure
The frequency and consequence of autonomous decisions this role must make. 1.00 means continuous high-consequence decisions. 0.00 means decisions are always supervised.

safety_ownership
The degree to which this role bears direct responsibility for safety outcomes. 1.00 means ultimate safety accountability. 0.00 means safety responsibility is fully delegated upward.

supervision_dependency
How much this role depends on supervision from above to execute correctly. 0.00 means fully autonomous. 1.00 means fully supervised.

BEHAVIORAL DIMENSION RELEVANCE

Each role has a primary behavioral profile indicating which of the 7 behavioral dimensions carry the most weight for role-fit evaluation.

Dimensions:
discipline
teamwork
stress_tolerance
communication
initiative
respect
conflict_handling

Relevance levels:
critical — must be above threshold, failure is disqualifying
high — strongly weighted in scoring
moderate — contributes to score but not decisive
low — minimal impact on role-fit

DECK DEPARTMENT

MASTER

role_code: MASTER
behavioral_weight: 0.45
technical_weight: 0.30
leadership_expectation: 1.00
decision_exposure: 1.00
safety_ownership: 1.00
supervision_dependency: 0.00

behavioral_profile:
discipline: critical
teamwork: high
stress_tolerance: critical
communication: critical
initiative: critical
respect: high
conflict_handling: critical

mismatch_signals:
avoids_responsibility
defers_decisions_under_pressure
poor_conflict_ownership
blames_crew_for_outcomes
low_situational_awareness

CHIEF_OFFICER

role_code: CHIEF_OFFICER
behavioral_weight: 0.40
technical_weight: 0.35
leadership_expectation: 0.85
decision_exposure: 0.80
safety_ownership: 0.90
supervision_dependency: 0.10

behavioral_profile:
discipline: critical
teamwork: critical
stress_tolerance: high
communication: critical
initiative: high
respect: high
conflict_handling: high

mismatch_signals:
cannot_coordinate_multiple_operations
poor_cargo_awareness
avoids_crew_confrontation
weak_organizational_discipline
passive_under_authority

SECOND_OFFICER

role_code: SECOND_OFFICER
behavioral_weight: 0.35
technical_weight: 0.40
leadership_expectation: 0.55
decision_exposure: 0.55
safety_ownership: 0.65
supervision_dependency: 0.30

behavioral_profile:
discipline: high
teamwork: high
stress_tolerance: high
communication: high
initiative: moderate
respect: moderate
conflict_handling: moderate

mismatch_signals:
navigation_procedural_gaps
poor_watchkeeping_focus
hesitant_in_course_correction
low_chart_discipline
over_reliance_on_automation

THIRD_OFFICER

role_code: THIRD_OFFICER
behavioral_weight: 0.35
technical_weight: 0.40
leadership_expectation: 0.45
decision_exposure: 0.45
safety_ownership: 0.70
supervision_dependency: 0.35

behavioral_profile:
discipline: high
teamwork: high
stress_tolerance: high
communication: moderate
initiative: moderate
respect: moderate
conflict_handling: moderate

mismatch_signals:
lsa_ffa_knowledge_gaps
poor_safety_drill_ownership
passive_during_emergencies
incomplete_documentation_habits
watch_handover_negligence

BOSUN

role_code: BOSUN
behavioral_weight: 0.40
technical_weight: 0.35
leadership_expectation: 0.55
decision_exposure: 0.30
safety_ownership: 0.50
supervision_dependency: 0.40

behavioral_profile:
discipline: critical
teamwork: critical
stress_tolerance: high
communication: high
initiative: high
respect: critical
conflict_handling: high

mismatch_signals:
cannot_organize_deck_crew
poor_maintenance_planning
weak_authority_with_ratings
avoids_physical_task_leadership
inconsistent_safety_enforcement

AB_SEAMAN

role_code: AB_SEAMAN
behavioral_weight: 0.30
technical_weight: 0.40
leadership_expectation: 0.15
decision_exposure: 0.15
safety_ownership: 0.30
supervision_dependency: 0.60

behavioral_profile:
discipline: high
teamwork: high
stress_tolerance: moderate
communication: moderate
initiative: moderate
respect: high
conflict_handling: low

mismatch_signals:
poor_seamanship_fundamentals
unreliable_watch_performance
cannot_work_without_instruction
safety_shortcut_tendency
low_physical_task_endurance

ORDINARY_SEAMAN

role_code: ORDINARY_SEAMAN
behavioral_weight: 0.30
technical_weight: 0.30
leadership_expectation: 0.05
decision_exposure: 0.10
safety_ownership: 0.20
supervision_dependency: 0.75

behavioral_profile:
discipline: high
teamwork: high
stress_tolerance: moderate
communication: moderate
initiative: low
respect: high
conflict_handling: low

mismatch_signals:
unwilling_to_learn
poor_instruction_following
low_physical_capability
safety_awareness_gaps
attitude_problems_with_seniors

DECK_CADET

role_code: DECK_CADET
behavioral_weight: 0.35
technical_weight: 0.20
leadership_expectation: 0.00
decision_exposure: 0.05
safety_ownership: 0.10
supervision_dependency: 0.95

behavioral_profile:
discipline: high
teamwork: moderate
stress_tolerance: moderate
communication: moderate
initiative: high
respect: critical
conflict_handling: low

mismatch_signals:
no_learning_curiosity
poor_logbook_discipline
avoids_asking_questions
disrespectful_to_officers
cannot_follow_basic_procedures

ENGINE DEPARTMENT

CHIEF_ENGINEER

role_code: CHIEF_ENGINEER
behavioral_weight: 0.40
technical_weight: 0.45
leadership_expectation: 0.95
decision_exposure: 0.90
safety_ownership: 0.95
supervision_dependency: 0.05

behavioral_profile:
discipline: critical
teamwork: high
stress_tolerance: critical
communication: high
initiative: critical
respect: high
conflict_handling: high

mismatch_signals:
poor_machinery_diagnostic_depth
avoids_emergency_decisions
weak_spare_parts_management
cannot_coordinate_engine_team
defers_critical_maintenance

SECOND_ENGINEER

role_code: SECOND_ENGINEER
behavioral_weight: 0.35
technical_weight: 0.45
leadership_expectation: 0.70
decision_exposure: 0.65
safety_ownership: 0.75
supervision_dependency: 0.20

behavioral_profile:
discipline: critical
teamwork: high
stress_tolerance: high
communication: high
initiative: high
respect: moderate
conflict_handling: moderate

mismatch_signals:
poor_planned_maintenance_execution
weak_engine_room_resource_management
hesitant_during_machinery_failure
cannot_supervise_ratings
incomplete_technical_documentation

THIRD_ENGINEER

role_code: THIRD_ENGINEER
behavioral_weight: 0.30
technical_weight: 0.45
leadership_expectation: 0.40
decision_exposure: 0.40
safety_ownership: 0.55
supervision_dependency: 0.40

behavioral_profile:
discipline: high
teamwork: high
stress_tolerance: high
communication: moderate
initiative: moderate
respect: moderate
conflict_handling: low

mismatch_signals:
poor_watchkeeping_in_engine_room
weak_auxiliary_system_knowledge
slow_alarm_response
poor_fuel_oil_management
cannot_troubleshoot_independently

MOTOR_MAN

role_code: MOTOR_MAN
behavioral_weight: 0.25
technical_weight: 0.45
leadership_expectation: 0.10
decision_exposure: 0.15
safety_ownership: 0.30
supervision_dependency: 0.65

behavioral_profile:
discipline: high
teamwork: high
stress_tolerance: moderate
communication: moderate
initiative: moderate
respect: high
conflict_handling: low

mismatch_signals:
poor_machinery_operation_basics
cannot_identify_abnormal_sounds
weak_lubrication_procedures
low_engine_room_awareness
safety_gear_negligence

OILER

role_code: OILER
behavioral_weight: 0.25
technical_weight: 0.40
leadership_expectation: 0.05
decision_exposure: 0.10
safety_ownership: 0.25
supervision_dependency: 0.70

behavioral_profile:
discipline: high
teamwork: high
stress_tolerance: moderate
communication: low
initiative: low
respect: high
conflict_handling: low

mismatch_signals:
poor_lubrication_knowledge
unreliable_routine_execution
cannot_read_gauges_properly
low_cleanliness_standards
weak_bilge_management

FITTER

role_code: FITTER
behavioral_weight: 0.25
technical_weight: 0.50
leadership_expectation: 0.10
decision_exposure: 0.15
safety_ownership: 0.30
supervision_dependency: 0.55

behavioral_profile:
discipline: high
teamwork: moderate
stress_tolerance: moderate
communication: moderate
initiative: high
respect: moderate
conflict_handling: low

mismatch_signals:
poor_fabrication_quality
weak_pipe_fitting_skills
cannot_read_technical_drawings
improper_tool_usage
low_precision_in_measurements

ETO

role_code: ETO
behavioral_weight: 0.25
technical_weight: 0.55
leadership_expectation: 0.30
decision_exposure: 0.40
safety_ownership: 0.50
supervision_dependency: 0.35

behavioral_profile:
discipline: high
teamwork: moderate
stress_tolerance: high
communication: high
initiative: high
respect: moderate
conflict_handling: low

mismatch_signals:
poor_electrical_safety_awareness
cannot_troubleshoot_automation
weak_communication_system_knowledge
poor_ecdis_radar_maintenance
over_reliance_on_manufacturer_support

ENGINE_CADET

role_code: ENGINE_CADET
behavioral_weight: 0.35
technical_weight: 0.20
leadership_expectation: 0.00
decision_exposure: 0.05
safety_ownership: 0.10
supervision_dependency: 0.95

behavioral_profile:
discipline: high
teamwork: moderate
stress_tolerance: moderate
communication: moderate
initiative: high
respect: critical
conflict_handling: low

mismatch_signals:
no_learning_motivation
poor_engine_room_safety_habits
avoids_hands_on_work
cannot_follow_technical_instructions
disrespectful_to_engineers

HOTEL / SERVICE

COOK

role_code: COOK
behavioral_weight: 0.45
technical_weight: 0.30
leadership_expectation: 0.25
decision_exposure: 0.20
safety_ownership: 0.35
supervision_dependency: 0.45

behavioral_profile:
discipline: critical
teamwork: high
stress_tolerance: high
communication: moderate
initiative: high
respect: high
conflict_handling: moderate

mismatch_signals:
poor_hygiene_standards
cannot_manage_provisions
low_menu_variety_capability
galley_fire_safety_ignorance
crew_complaint_pattern
poor_time_management_for_meals

STEWARD

role_code: STEWARD
behavioral_weight: 0.50
technical_weight: 0.15
leadership_expectation: 0.10
decision_exposure: 0.10
safety_ownership: 0.15
supervision_dependency: 0.65

behavioral_profile:
discipline: high
teamwork: high
stress_tolerance: moderate
communication: high
initiative: moderate
respect: critical
conflict_handling: moderate

mismatch_signals:
poor_cleanliness_standards
low_service_attitude
cannot_manage_cabin_supplies
weak_laundry_management
disrespectful_to_crew
poor_mess_room_organization

SPECIALIZED ROLES

PUMPMAN

role_code: PUMPMAN
behavioral_weight: 0.25
technical_weight: 0.55
leadership_expectation: 0.25
decision_exposure: 0.40
safety_ownership: 0.85
supervision_dependency: 0.30

behavioral_profile:
discipline: critical
teamwork: high
stress_tolerance: critical
communication: high
initiative: high
respect: moderate
conflict_handling: low

mismatch_signals:
poor_cargo_pump_knowledge
weak_tank_cleaning_procedures
cannot_manage_loading_discharge
low_gas_detection_awareness
valve_operation_errors
ignores_cargo_safety_protocols

DP_OPERATOR

role_code: DP_OPERATOR
behavioral_weight: 0.30
technical_weight: 0.55
leadership_expectation: 0.30
decision_exposure: 0.55
safety_ownership: 0.85
supervision_dependency: 0.25

behavioral_profile:
discipline: critical
teamwork: high
stress_tolerance: critical
communication: critical
initiative: high
respect: moderate
conflict_handling: moderate

mismatch_signals:
poor_dp_system_understanding
slow_reaction_to_position_drift
weak_reference_system_knowledge
cannot_manage_dp_alarms
poor_bridge_team_communication
over_reliance_on_automatic_mode

CRANE_OPERATOR

role_code: CRANE_OPERATOR
behavioral_weight: 0.30
technical_weight: 0.50
leadership_expectation: 0.20
decision_exposure: 0.35
safety_ownership: 0.70
supervision_dependency: 0.35

behavioral_profile:
discipline: critical
teamwork: high
stress_tolerance: high
communication: high
initiative: moderate
respect: moderate
conflict_handling: low

mismatch_signals:
poor_load_calculation
weak_rigging_knowledge
ignores_wind_weather_limits
poor_hand_signal_communication
cannot_assess_lift_geometry
low_pre_operation_check_discipline

SCORING ENGINE INTEGRATION

behavioral_weight and technical_weight define the split between behavioral and technical assessment in the final score for each role. The remaining weight is distributed to certification, experience, and availability as defined in vessel requirement profiles.

For roles where behavioral_weight + technical_weight < 1.00, the remaining capacity is allocated to:
certification_fit
experience_fit
availability_fit

These allocations are defined in the vessel requirement engine, not in this matrix.

INTERVIEW ENGINE INTEGRATION

behavioral_weight determines the proportion of behavioral questions in an interview.

technical_weight determines the proportion of technical questions in an interview.

leadership_expectation determines whether leadership scenario questions are included.

decision_exposure determines whether decision-under-pressure scenarios are included.

safety_ownership determines whether safety-critical scenario questions are included.

behavioral_profile dimensions at level critical must each have at least one dedicated question.

behavioral_profile dimensions at level high must be covered by at least one shared question.

MISMATCH DETECTION INTEGRATION

mismatch_signals are the canonical red flags for each role.

During interview scoring, response patterns are compared against mismatch_signals.

A match between candidate response patterns and mismatch_signals triggers:
score penalty on the relevant dimension
mismatch flag in the scoring output
risk note in the decision panel

Three or more mismatch signals triggered in a single interview flags the candidate as high_risk for the target role.

ROLE-FIT ENGINE INTEGRATION

When a candidate applies for a role:

1. Load the role DNA from this matrix.
2. Compare candidate behavioral scores against behavioral_profile thresholds.
3. Compare candidate technical scores against technical_weight expectation.
4. Evaluate supervision_dependency against candidate experience level.
5. Evaluate leadership_expectation against candidate demonstrated leadership.
6. Compute role-fit score as weighted composite.

role_fit = (behavioral_match * behavioral_weight) + (technical_match * technical_weight) + (leadership_match * 0.15) + (safety_match * 0.10)

Where leadership_match and safety_match are derived from:
leadership_expectation vs candidate leadership evidence
safety_ownership vs candidate safety track record

SYSTEM RULES

This matrix must be loaded before any scoring computation.

This matrix must be loaded before any interview question selection.

This matrix must be loaded before any mismatch evaluation.

Role-fit scores without DNA matrix consultation are invalid.

Any new role added to ROLE_CORE_REGISTRY must have a corresponding entry in this matrix before it can be used in any engine.

Behavioral dimension relevance levels must not be overridden at the tenant level. They are system-wide constants.

Weight values (behavioral_weight, technical_weight) may be adjusted per vessel type through the vessel requirement engine. This matrix defines the defaults.
