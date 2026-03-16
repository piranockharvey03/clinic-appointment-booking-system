# Doctor Module

## Path

- app/doctor

## Purpose

Enables doctor-side appointment triage and management, including approval, cancellation, rescheduling, and profile/settings operations.

## Key Components

- doctor-login.php and doctor-logout.php: doctor access control.
- doctor-dashboard.php: doctor summary and active workload view.
- doctor-appointments.php: appointment actions and state transitions.
- doctor-settings.php: profile and password settings.

## Inbound Dependencies

- public/doctor-login.html
- Authenticated doctor sessions from auth/login process.

## Outbound Dependencies

- config/session-config.php
- config/db-config.php
- Tables: doctors, appointments, patient_notifications, doctor_notifications, activity_logs

## Data and Entities

- Doctor identity and status (active/inactive)
- Doctor-owned appointments only
- Notification payloads sent to patients and doctors

## Security Notes

- Doctor login checks account status before granting access.
- Appointment mutations are scoped by doctor_id ownership checks.
- Activity logging captures key actions.

## Observed Risks

- No reschedule policy or cap enforcement.
- Cancellation text can become inconsistent and hard to report on.
- Limited concurrency handling for simultaneous actions.

## Recommended Improvements

1. Add configurable reschedule limits and action cooldowns.
2. Standardize cancellation reasons with controlled categories.
3. Add optimistic locking or state checks before transitions.
4. Expand audit events for all significant doctor actions.
