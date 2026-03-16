# Shared Assets Module

## Path

- app/assets
- public/assets

## Purpose

Contains shared frontend CSS/JS resources that power layout behavior, dark mode, sidebar interactions, and form UX.

## Key Components

- CSS: dark-mode.css, responsive-sidebar.css
- JS: custom-modal.js, dark-mode.js, feedback-form.js, init.js, mobile-menu.js, sidebar-toggle.js

## Inbound Dependencies

- Included by public pages and role-specific dashboards.

## Outbound Dependencies

- Calls backend endpoints in app/includes and role modules.
- Depends on browser APIs and third-party front-end libraries loaded in pages.

## Data and Entities

- UI state: dark mode preference, sidebar visibility, form validation state
- Client-request payloads for feedback and notifications

## Security Notes

- Frontend scripts avoid embedding sensitive credentials.
- Sensitive authorization remains server-side.

## Observed Risks

- Duplicated assets under app/assets and public/assets can drift over time.
- Client-side validation is inconsistent if backend contracts evolve.

## Recommended Improvements

1. Consolidate assets into one canonical source with build/sync process.
2. Add versioned asset strategy to reduce cache/staleness issues.
3. Add smoke tests for key JS flows (feedback, notifications, menus).
