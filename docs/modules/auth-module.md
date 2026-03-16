# Auth Module

## Path

- app/auth

## Purpose

Centralizes authentication and account access lifecycle behavior for patient, doctor, and admin users.

## Key Components

- login.php: patient login and session initialization.
- register.php: patient registration and uniqueness checks.
- forgot-password.php: generic reset request endpoint.
- change-password.php: authenticated password update endpoint.
- check-session.php: session validity check for AJAX clients.
- logout.php: session termination.

## Inbound Dependencies

- public/login.html
- public/register.html
- public/forgot-password.html
- Role dashboards that rely on valid session state

## Outbound Dependencies

- config/session-config.php
- config/db-config.php
- Tables: users, doctors, admin, activity_logs

## Data and Entities

- Identity: email, password hash, role, user id
- Session: user_id, user_role, user_name, login_time
- Audit: login and password-related activity events

## Security Notes

- Uses prepared statements for DB queries.
- Uses password_hash and password_verify.
- Session hardening comes from session-config.php.
- Forgot-password endpoint returns generic responses to reduce account enumeration.

## Observed Risks

- Forgot-password flow currently does not issue reset tokens or send email links.
- change-password.php allows password change without validating current password.
- Password policy consistency is weak across auth endpoints.
- No explicit rate limiting on auth endpoints.

## Recommended Improvements

1. Implement token-based reset flow with expiry and one-time use.
2. Enforce current-password verification in change-password.php.
3. Apply consistent password policy across register and change flows.
4. Add login throttling and lockout strategy.
5. Add CSRF token validation for sensitive form posts.
