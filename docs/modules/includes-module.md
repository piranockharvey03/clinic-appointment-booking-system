# Shared Includes Module

## Path

- app/includes

## Purpose

Hosts shared backend endpoints and reusable API-like handlers consumed by frontend pages and dashboards.

## Key Components

- feedback.php: receives and persists user feedback.
- get-active-departments.php: returns departments with active doctors.
- get-doctors-by-department.php: returns doctor lists by filter.
- get-notifications.php and mark-notifications-read.php: admin notification APIs.
- get-patient-notifications.php and mark-patient-notifications-read.php: patient notification APIs.

## Inbound Dependencies

- public pages and dashboard scripts via AJAX/fetch calls.
- booking and notification interfaces.

## Outbound Dependencies

- config/db-config.php
- Tables: feedback, departments, doctors, doctor_departments, doctor_specialties, notifications, patient_notifications

## Data and Entities

- Notification records (read/unread lifecycle)
- Doctor-directory API response payloads
- Feedback records and metadata

## Security Notes

- Notification mark-read handlers include ownership checks.
- Query execution uses prepared statements.
- Basic input sanitization exists for feedback submissions.

## Observed Risks

- Public feedback endpoint can be spammed without rate limiting.
- CSRF protections are limited for state-changing calls.
- Input normalization can be tightened for array-based endpoints.

## Recommended Improvements

1. Add request throttling and anti-bot controls for feedback.
2. Add CSRF tokens for write operations.
3. Normalize and validate all list/id payloads before DB writes.
4. Add endpoint-level audit events for sensitive operations.
