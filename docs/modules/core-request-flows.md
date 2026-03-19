# Core Request Flows

This document summarizes how core user journeys move across modules.

## 1) Login Flow

1. User submits credentials from public login page.
2. app/auth validates credentials and role context.
3. Session variables are initialized through shared config.
4. User is redirected to role-specific dashboard module.

## 2) Appointment Booking Flow

1. Patient interacts with public booking page.
2. Frontend requests department and doctor lists from app/includes endpoints.
3. Frontend checks slot availability using app/includes/check-slot-availability.php when doctor/date/time changes.
4. Booking is submitted to app/patient/submit-booking.php.
5. Backend validates doctor availability constraints, applies canonical slot key generation, and stores appointment.
6. Database uniqueness checks prevent conflicting active bookings for the same doctor slot.
7. Patient sees updated appointment data in patient module.

## 3) Feedback Flow

1. Visitor submits feedback form from public interface.
2. Frontend posts payload to app/includes/feedback.php.
3. Backend validates/sanitizes and writes to feedback table.
4. UI shows success/failure state.

## 4) Password Change Flow

1. Authenticated user opens settings page in role module.
2. Form posts to app/auth/change-password.php.
3. Backend validates payload and updates password hash in role table.
4. User continues with new credentials for future logins.

## 5) Notification Flow

1. Role dashboards initialize shared notification client logic from app/assets/js/notification-dropdown.js.
2. Dashboard scripts request unread notifications from role-scoped endpoints in app/includes.
3. Backend returns role-scoped notification records for admin, doctor, or patient users.
4. Client marks items read via corresponding mark-read endpoints.
5. Notification badge and dropdown state remain synchronized through polling.

## 6) Appointment Encounter Verification Flow

1. A doctor approves a patient appointment.
2. On appointment day, patient checks in from patient appointments page.
3. app/patient/checkin.php validates ownership, approved state, and same-day date.
4. Backend stores checked_in_at/checkin_token/checked_in_by and logs activity.
5. Doctor notification is emitted with the verification token.
6. Doctor can mark appointment completed only after checked_in_at exists.

## 7) Doctor Reschedule Flow

1. Doctor initiates reschedule from doctor appointments page.
2. Backend validates doctor ownership and requested date/time.
3. Backend checks for slot conflicts in active appointments for that doctor.
4. On success, appointment is updated to rescheduled with canonical slot key.
5. Patient receives a reschedule notification containing old and new schedule values.

## 8) No-Show Auto-Cancel Flow

1. Before key patient and doctor flows, backend runs no-show processing with grace period.
2. Approved appointments without check-in past grace window are auto-canceled.
3. Auto-cancel action clears booking_slot_key so the slot becomes available again.
4. Patient and doctor notifications are emitted for the no-show cancellation event.

## Cross-Cutting Controls

- Session and role checks at role module entry points.
- DB access via shared config helper.
- Activity logging on sensitive operations.
- Prepared statements for SQL safety.
