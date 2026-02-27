PURPOSE

This document defines the canonical role registry for Octopus-AI Maritime.

It is the single source of truth for:

role taxonomy

department mapping

authority level

safety criticality

operational responsibility

execution depth

No scoring, interview, certification or vessel logic may be built without referencing this registry.

This file must be treated as foundational system architecture, not UI configuration.

AUTHORITY SCALE
Level    Definition
5    Ship Command Authority
4    Department Command
3    Operational Decision Authority
2    Task Execution Responsibility
1    Trainee / Assisted Execution
ROLE CATEGORIES
COMMAND ROLES

Decision ownership over vessel safety, navigation, engineering or crew.

OPERATIONAL ROLES

Operational planning, watch responsibility, risk response.

EXECUTION ROLES

Task-based physical or technical execution under procedures.

SERVICE ROLES

Crew welfare, accommodation, food, internal support.

TRAINEE ROLES

Learning, supervised execution, development stage.

SPECIALIZED ROLES

Vessel-specific technical roles not present on all ships.

MASTER ROLE REGISTRY
DECK DEPARTMENT
Command

role_code: MASTER
department: deck
category: command
authority_level: 5
safety_criticality: extreme
decision_scope: vessel-wide

role_code: CHIEF_OFFICER
department: deck
category: command
authority_level: 4
safety_criticality: extreme
decision_scope: cargo + deck operations

Operational

role_code: SECOND_OFFICER
department: deck
category: operational
authority_level: 3
safety_criticality: high
decision_scope: navigation watch

role_code: THIRD_OFFICER
department: deck
category: operational
authority_level: 3
safety_criticality: high
decision_scope: safety + watch

Execution

role_code: BOSUN
department: deck
category: execution
authority_level: 2
safety_criticality: high

role_code: AB_SEAMAN
department: deck
category: execution
authority_level: 2
safety_criticality: medium

role_code: ORDINARY_SEAMAN
department: deck
category: execution
authority_level: 2
safety_criticality: medium

Trainee

role_code: DECK_CADET
department: deck
category: trainee
authority_level: 1
safety_criticality: low

ENGINE DEPARTMENT
Command

role_code: CHIEF_ENGINEER
department: engine
category: command
authority_level: 5
safety_criticality: extreme
decision_scope: propulsion + machinery

Operational

role_code: SECOND_ENGINEER
department: engine
category: operational
authority_level: 4
safety_criticality: extreme

role_code: THIRD_ENGINEER
department: engine
category: operational
authority_level: 3
safety_criticality: high

Execution

role_code: MOTOR_MAN
department: engine
category: execution
authority_level: 2
safety_criticality: medium

role_code: OILER
department: engine
category: execution
authority_level: 2
safety_criticality: medium

role_code: FITTER
department: engine
category: execution
authority_level: 2
safety_criticality: medium

role_code: ETO
department: engine
category: operational
authority_level: 3
safety_criticality: high

Trainee

role_code: ENGINE_CADET
department: engine
category: trainee
authority_level: 1
safety_criticality: low

HOTEL / SERVICE

role_code: COOK
department: service
category: service
authority_level: 2
safety_criticality: medium

role_code: STEWARD
department: service
category: service
authority_level: 2
safety_criticality: low

SPECIALIZED ROLES (VESSEL TYPE DEPENDENT)
Tanker

role_code: PUMPMAN
department: deck
category: specialized
authority_level: 3
safety_criticality: extreme

Offshore

role_code: DP_OPERATOR
department: deck
category: specialized
authority_level: 3
safety_criticality: extreme

role_code: CRANE_OPERATOR
department: deck
category: specialized
authority_level: 3
safety_criticality: high

SYSTEM RULES

No interview question may be generated before role mapping exists.

No scoring model may run without role context.

No certification logic may be applied generically.

Behavior evaluation must be role-weighted.

Technical evaluation must be role-weighted.

Mismatch detection must compare role intent vs response patterns.

OUTPUT EXPECTATION FROM NEXT PHASE

Claude must next generate:

ROLE_DNA_MATRIX_v1

which includes per-role:

behavioral weight

technical weight

leadership expectation

decision exposure

safety ownership

supervision dependency

This matrix will drive:

scoring

interview design

mismatch detection

role-fit engine
