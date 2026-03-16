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
3. Booking is submitted to app/patient/submit-booking.php.
4. Backend validates doctor availability constraints and stores appointment.
5. Patient sees updated appointment data in patient module.

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

1. Role dashboard scripts request unread notifications from app/includes.
2. Backend returns role-scoped notification records.
3. Client marks items read via mark-read endpoints.
4. Notification badge and state sync in UI.

## Cross-Cutting Controls

- Session and role checks at role module entry points.
- DB access via shared config helper.
- Activity logging on sensitive operations.
- Prepared statements for SQL safety.
