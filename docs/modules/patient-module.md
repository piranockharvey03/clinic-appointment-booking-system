# Patient Module

## Path

- app/patient

## Purpose

Provides patient-facing appointment booking lifecycle operations, profile management, and personal dashboard views.

## Key Components

- patient-dashboard.php: patient summary and appointment highlights.
- patient-appointments.php: list patient-owned appointments and allow cancel/check-in actions.
- patient-profile.php: patient profile updates.
- patient-settings.php: patient account settings.
- submit-booking.php: booking endpoint with server-side validation.
- checkin.php: patient check-in endpoint for approved same-day appointments.
- how-appointments-work.php: patient guide for booking, statuses, notifications, rescheduling policy, and no-show behavior.

## Inbound Dependencies

- public/login.html (post-login navigation)
- public/patient-book.php (booking form submission)

## Outbound Dependencies

- config/session-config.php
- config/db-config.php
- app/includes/check-slot-availability.php (live booking pre-check)
- Tables: users, doctors, appointments, doctor_departments, doctor_specialties, patient_notifications, doctor_notifications, activity_logs

## Data and Entities

- Patient profile and role-scoped identity
- Appointment intent: doctor, department, date, time, reason, notes
- Encounter proof fields on appointments: checked_in_at, checkin_token, checked_in_by
- Canonical slot key on appointments: booking_slot_key
- Patient notification history

## Security Notes

- Session role checks protect patient-only pages.
- Appointment updates are scoped to patient ownership checks.
- Booking validation blocks invalid dates and non-active doctors.
- Patient reschedule actions are blocked; rescheduling is doctor-only.
- Check-in requires approved status, same-day date, and patient ownership.
- Check-in writes an audit trail and doctor notification with a verification token.
- No-show cleanup runs before key patient flows and returns clear no-show closure messaging.

## Observed Risks

- No-show automation is currently trigger-based from active request flows, not a background scheduler.
- No automated external confirmation channel (email/SMS).
- Complex schedule scenarios (e.g., doctor leaves) still rely on manual clinic communication.

## Recommended Improvements

1. Add a scheduled background worker for no-show processing to reduce dependence on user traffic.
2. Add booking confirmation and reminder channels (email/SMS).
3. Add patient-facing acknowledgement workflow for doctor-initiated reschedules.
4. Add end-to-end tests for booking/check-in/no-show transitions.
