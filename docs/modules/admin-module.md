# Admin Module

## Path

- app/admin

## Purpose

Provides administrative operations for doctor management, appointment oversight, reporting, and system-level actions.

## Key Components

- new-admin-dashboard.php: summary metrics, notifications, quick actions.
- admin-login.php and admin-logout.php: admin session entry/exit.
- admin-appointments.php: appointment listing and filters.
- manage-doctors.php: doctor CRUD and status management.
- doctor-evaluation.php: doctor-level performance and appointment analysis.
- reports.php: operational reporting views.
- admin-settings.php: admin account settings.
- backup-database.php: database export operation.

## Inbound Dependencies

- public/admin-login.html
- Admin session checks gate all pages in this module.

## Outbound Dependencies

- config/session-config.php
- config/db-config.php
- Tables: admin, doctors, appointments, notifications, activity_logs, doctor_departments, doctor_specialties

## Data and Entities

- Admin account and role state
- Doctor directory and activation status
- Appointment data across all patients and doctors
- Operational and activity reporting data

## Security Notes

- Role checks are present and redirect unauthorized users.
- Uses prepared statements in queried actions.
- Password updates use hashing.

## Observed Risks

- Admin action auditing is limited; no dedicated admin audit trail.
- Backup exports can expose sensitive records if storage handling is weak.
- Privilege model appears flat (single admin capability level).

## Recommended Improvements

1. Add dedicated admin audit logging for all critical CRUD actions.
2. Protect backup export with stricter controls and download logging.
3. Introduce admin role tiers and least-privilege access.
4. Add explicit CSRF defenses for state-changing forms.
