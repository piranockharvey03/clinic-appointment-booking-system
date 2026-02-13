# Changelog

All notable changes to the MediCare Clinic Hospital Management System will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-02-13

### Fixed

- **Favicon Display**: Corrected favicon paths across all 9 public HTML pages from `../favicon.svg` to `assets/images/favicon.svg`
  - Affected files: index.html, about.html, login.html, register.html, services.html, privacy.html, terms.html, admin-login.html, patient-book.html
- **Registration Form**: Fixed non-functional register button on signup page
  - Corrected escaped quotes in HTML button attributes
  - Removed undefined `handleFormSubmit()` function call
  - Eliminated duplicate form submission event listeners
  - Improved validation flow and AJAX submission handling
- **Logout Functionality**: Resolved errors on both admin and patient logout
  - Updated patient logout links from `logout.php` to `../auth/logout.php` in:
    - patient-dashboard.php
    - patient-appointments.php
    - patient-profile.php
    - patient-settings.php
  - Removed redundant cache control headers in logout.php and admin-logout.php
  - Eliminated "headers already sent" PHP warnings

### Changed

- Updated SDD document version to 1.1
- Updated SRS document version to 1.1
- Added comprehensive maintenance logs to both documentation files

### Verified

- ✓ All public pages display favicon correctly
- ✓ Registration form validates and submits successfully
- ✓ Admin logout redirects properly without errors
- ✓ Patient logout redirects properly without errors
- ✓ No browser console errors
- ✓ No PHP warnings or errors

## [1.0.0] - 2026-01-29

### Added

- Initial release of MediCare Clinic Hospital Management System
- Patient registration and authentication
- Appointment booking system
- Admin dashboard with appointment management
- Patient dashboard with appointment history
- Real-time notification system
- Dark mode support
- Responsive design for mobile devices
- Database schema with complete relationships
- Security features (password hashing, session management)
- Comprehensive documentation (SRS, SDD)

### Documentation

- Software Requirements Specification (SRS) v1.0
- Software Design Document (SDD) v1.0
- Database schema documentation
- API endpoints documentation
