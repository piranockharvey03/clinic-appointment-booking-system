# Configuration and Data Layer Module

## Path

- config

## Purpose

Provides core database/session configuration and schema definitions that all PHP modules depend on.

## Key Components

- db-config.php: connection factory, close function, activity logging helper, slot-key normalization helpers, and no-show automation helper.
- session-config.php: secure session initialization and cookie controls.
- medicare-complete-database.sql: single authoritative schema with all expected entities.

## Inbound Dependencies

- Required by nearly all PHP endpoints in app/auth, app/admin, app/doctor, app/patient, and app/includes.

## Outbound Dependencies

- MySQL server (medicare database).

## Data and Entities

- Core system entities: users, doctors, admin, appointments, notifications, patient_notifications, activity_logs, and related lookup/junction tables.
- Appointments include encounter verification fields: checked_in_at, checkin_token, checked_in_by.
- Appointments use canonical booking_slot_key generation to protect active doctor/date/time uniqueness.

## Security Notes

- Centralized session hardening is a strong architectural point.
- Prepared statements are used by consumers with this config.
- Duplicate-key detection helper is used by workflows to surface conflict-safe user messaging.

## Observed Risks

- Credentials are static in code for current setup.
- Schema drift can still happen if local DBs are changed manually without updating the master SQL file.
- No-show automation currently runs from request-entry hooks rather than a dedicated scheduler.

## Recommended Improvements

1. Move credentials to environment variables for non-local environments.
2. Declare one authoritative schema and migration path.
3. Add database migration tooling and seed version tracking.
4. Add a scheduled execution path for no-show automation.
