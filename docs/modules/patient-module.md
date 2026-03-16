# Patient Module

## Path

- app/patient

## Purpose

Provides patient-facing appointment booking lifecycle operations, profile management, and personal dashboard views.

## Key Components

- patient-dashboard.php: patient summary and appointment highlights.
- patient-appointments.php: list, cancel, and reschedule patient-owned appointments.
- patient-profile.php: patient profile updates.
- patient-settings.php: patient account settings.
- submit-booking.php: booking endpoint with server-side validation.

## Inbound Dependencies

- public/login.html (post-login navigation)
- public/patient-book.html (booking form submission)

## Outbound Dependencies

- config/session-config.php
- config/db-config.php
- Tables: users, doctors, appointments, doctor_departments, doctor_specialties, patient_notifications

## Data and Entities

- Patient profile and role-scoped identity
- Appointment intent: doctor, department, date, time, reason, notes
- Patient notification history

## Security Notes

- Session role checks protect patient-only pages.
- Appointment updates are scoped to patient ownership checks.
- Booking validation blocks invalid dates and non-active doctors.

## Observed Risks

- No strong prevention for overlapping booking slots.
- No automated confirmation channel (email/SMS).
- Limited booking conflict handling logic from a scheduling perspective.

## Recommended Improvements

1. Enforce unique scheduling constraints per doctor/time slot.
2. Add conflict checks prior to appointment insert/update.
3. Add booking confirmation and reminders.
4. Add stronger client and server validation parity.
