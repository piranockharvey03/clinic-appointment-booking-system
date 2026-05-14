# Public Frontend Module

## Path

- public

## Purpose

Provides public-facing pages for marketing, login entry points, account creation, password recovery, and pre-auth booking interactions.

## Key Components

- index.html: landing page and feedback UI.
- login.html, register.html: patient auth entry points.
- admin-login.html, doctor-login.html: role-specific login entry points.
- patient-book.php: booking UI and dynamic doctor lookup.
- forgot-password.html: password reset request UI.
- about.html, doctors.php, services.html, privacy.html, terms.html: informational pages.

## Inbound Dependencies

- Direct browser traffic.

## Outbound Dependencies

- app/auth endpoints via forms/fetch.
- app/includes endpoints for dynamic lists and feedback.
- Shared public/assets CSS/JS files.

## Data and Entities

- Public user input for auth, booking, and feedback.
- Static informational content and navigation.

## Security Notes

- Sensitive operations are delegated to backend handlers.
- Public forms require backend-side validation and abuse protections.

## Observed Risks

- Form flows can break if endpoint paths are changed without coordinated updates.
- Public interfaces are exposed to automated traffic/spam.

## Recommended Improvements

1. Add integration tests for all public form action paths.
2. Add anti-automation protections for open forms.
3. Consider centralized routing helpers for robust path management.
