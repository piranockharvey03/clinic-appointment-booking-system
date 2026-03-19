# Doctor Module

## Path

- app/doctor

## Purpose

Enables doctor-side appointment triage and management, including approval, cancellation, rescheduling, check-in verification, and completion gating.

## Key Components

- doctor-login.php and doctor-logout.php: doctor access control.
- doctor-dashboard.php: doctor summary and active workload view.
- doctor-appointments.php: appointment actions, conflict-safe rescheduling, check-in visibility, and state transitions.
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
- Encounter verification context (checked-in timestamp and token display)
- Canonical scheduling slot keys used for conflict-safe approve/reschedule actions

## Security Notes

- Doctor login checks account status before granting access.
- Appointment mutations are scoped by doctor_id ownership checks.
- Activity logging captures key actions.
- Appointment completion is blocked until checked_in_at is present.
- Reschedule actions enforce server-side slot conflict checks and duplicate-key fallback handling.
- No-show cleanup executes before doctor data loads and action processing.

## Observed Risks

- No-show automation is currently request-triggered, not timer-driven.
- Cancellation text can become inconsistent and hard to report on.
- Multi-user concurrent state transitions still need broader workflow tests.

## Recommended Improvements

1. Add configurable reschedule limits and action cooldowns.
2. Standardize cancellation reasons with controlled categories.
3. Add a scheduler/cron path for no-show processing independent of dashboard traffic.
4. Expand audit events for all significant doctor actions.
