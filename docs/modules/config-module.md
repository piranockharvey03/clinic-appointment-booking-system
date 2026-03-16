# Configuration and Data Layer Module

## Path

- config

## Purpose

Provides core database/session configuration and schema definitions that all PHP modules depend on.

## Key Components

- db-config.php: connection factory, close function, activity logging helper.
- session-config.php: secure session initialization and cookie controls.
- medicare-complete-database.sql: single authoritative schema with all expected entities.

## Inbound Dependencies

- Required by nearly all PHP endpoints in app/auth, app/admin, app/doctor, app/patient, and app/includes.

## Outbound Dependencies

- MySQL server (medicare database).

## Data and Entities

- Core system entities: users, doctors, admin, appointments, notifications, patient_notifications, activity_logs, and related lookup/junction tables.

## Security Notes

- Centralized session hardening is a strong architectural point.
- Prepared statements are used by consumers with this config.

## Observed Risks

- Credentials are static in code for current setup.
- Schema drift can still happen if local DBs are changed manually without updating the master SQL file.

## Recommended Improvements

1. Move credentials to environment variables for non-local environments.
2. Declare one authoritative schema and migration path.
3. Add database migration tooling and seed version tracking.
