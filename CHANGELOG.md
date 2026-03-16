# Changelog

All notable changes to the MediCare Clinic Hospital Management System will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.2] - 2026-03-16

### Fixed

- **Broken path — patient booking cancel link** (`public/patient-book.html`):
  - Updated cancel target from missing `patient-appointments.html` to `../app/patient/patient-appointments.php`

- **Broken path — feedback AJAX endpoint (app assets)** (`app/assets/js/feedback-form.js`):
  - Updated fetch endpoint from non-existent `../php/feedback.php` to `../../includes/feedback.php`

- **Broken path — feedback AJAX endpoint (public assets)** (`public/assets/js/feedback-form.js`):
  - Updated fetch endpoint from non-existent `../php/feedback.php` to `../../app/includes/feedback.php`

- **Missing forgot-password backend endpoint**:
  - Added `app/auth/forgot-password.php` for asynchronous forgot-password requests from `public/forgot-password.html`
  - Endpoint validates email input, checks account existence across `users`, `doctors`, and `admin`, logs request activity, and returns generic JSON responses to reduce account enumeration risk

- **Admin doctor evaluation crash on older schema** (`app/admin/doctor-evaluation.php`):
  - Prevented fatal SQL error when `appointments.cancel_reason` is missing
  - Added runtime schema check and fallback selection (`NULL AS cancel_reason`) for backward compatibility

### Database

- **Applied migration on live database**:
  - Added `appointments.cancel_reason` column to `medicare` database
  - Verified column exists as `VARCHAR(255)` nullable

- **Consolidated schema files**:
  - Updated `config/medicare-complete-database.sql` to include:
    - `appointments.cancel_reason`
    - `activity_logs` table definition
  - Deleted redundant `config/database-schema.sql`

### Documentation

- Updated docs to reflect the single authoritative SQL setup file:
  - `README.md`
  - `SDD_MediCare_Clinic_System.md`
  - `docs/modules/config-module.md`
- Added module analysis documentation set under `docs/modules/` and cross-module flow reference

## [2.0.1] - 2026-03-11

### Security

- **Broken Access Control — Doctor appointment actions** (`app/doctor/doctor-appointments.php`):
  - All 4 appointment action UPDATEs (approve, cancel, reschedule, complete) were missing ownership enforcement
  - A logged-in doctor could approve/cancel/complete/reschedule any appointment by POST-ing a known `appointment_id`
  - Fixed by adding `AND CAST(doctor_id AS UNSIGNED) = ?` bound to `(int)$_SESSION['user_id']` on every action UPDATE
  - Also tightened the pre-action SELECT (used for notification building) with the same ownership clause

- **Broken Access Control — Patient appointment actions** (`app/patient/patient-appointments.php`):
  - Cancel and reschedule UPDATEs were missing `AND patient_id = ?` ownership check
  - Any authenticated patient could cancel/reschedule another patient's appointment by knowing the `appointment_id`
  - Fixed by adding `AND patient_id = ?` bound to `(int)$_SESSION['user_id']` on both UPDATEs

## [2.0.0] - 2026-03-11

### Security (fixes applied to production code)

- **Broken Access Control — Patient notifications** (`app/includes/mark-patient-notifications-read.php`):
  - Added `require_once 'session-config.php'` — previously missing entirely
  - Added role check: requests without `$_SESSION['user_role'] === 'patient'` now receive HTTP 401
  - Added ownership enforcement: `AND patient_id = ?` bound to `$_SESSION['user_id']` in the UPDATE query; patients can only mark their own notifications as read
  - Added `intval()` cast and positive-integer filter on incoming `notification_ids[]` to prevent type-juggling abuse

- **Broken Access Control — Doctor appointment scoping** (`app/doctor/doctor-appointments.php`, `app/doctor/doctor-dashboard.php`):
  - Both files previously loaded ALL appointments from the database with no WHERE clause
  - Replaced with `WHERE doctor_id = ?` prepared statement bound to `$_SESSION['user_id']`
  - Doctors now only see their own patients' appointments and statistics

- **Hardcoded DB credentials** (`app/includes/feedback.php`):
  - Removed inline `$host`, `$user`, `$password`, `$dbname` variables and raw `new mysqli()` call
  - Replaced with `require_once '../../config/db-config.php'` and `getDBConnection()`
  - DB credentials are now managed exclusively in `config/db-config.php`

- **SQL injection in notification lookup** (`app/doctor/doctor-appointments.php`):
  - Action handler was using `"... WHERE appointment_id = '$apptId'"` (raw string interpolation)
  - Replaced with a prepared statement and `bind_param("s", $apptId)`

### Removed (debug and development files)

The following files have been permanently deleted. They were either expired development tools, exposed internal system state, or created active security risks:

| File                                 | Reason for removal                                                                     |
| ------------------------------------ | -------------------------------------------------------------------------------------- |
| `app/auth/debug-password-change.php` | **Critical** — logged raw POST data including plaintext passwords to disk              |
| `app/admin/test-session.php`         | Exposed full `$_SESSION` dump to any authenticated user regardless of role             |
| `view-logs.php`                      | Debug log viewer accessible to any user with a session (no role check)                 |
| `clear-log.php`                      | Debug log file deletion accessible to any user with a session (no role check)          |
| `verify-update.php`                  | Database schema verification tool with no role check                                   |
| `apply-migration.php`                | One-time migration script left deployed after use                                      |
| `app/admin/admin-dashboard.php`      | Dead redirect — contained `exit` on line 14 followed by ~150 lines of unreachable code |

### Added

- **Admin Appointments page** (`app/admin/admin-appointments.php`):
  - Rebuilt from the previous dead-redirect stub into a fully functional page
  - Doctor filter dropdown — view all appointments or filter by a specific doctor
  - Summary stat cards scoped to the selected filter (total, pending, approved, completed, canceled, rescheduled)
  - Status tabs: All / Pending / Approved / Completed / Canceled / Rescheduled
  - Full appointment table: Patient, Doctor, Department, Date & Time, Status badge, Reason/Notes, Cancel Reason column
  - Client-side search (patient name and doctor name)
  - Added "Appointments" nav link to all admin sidebars

- **SECURITY.md** — new security reference document covering:
  - Authentication and session management controls
  - Access control model and role table
  - SQL injection and XSS prevention approach
  - Summary table of all v2.0.0 security changes
  - Production deployment checklist

### Changed

- **README.md** — complete rewrite:
  - Version badge updated to 2.0.0
  - Accurate project structure tree (reflects current file layout)
  - Correct default credentials (admin@hospital.com / admin123)
  - Security section updated and linked to SECURITY.md
  - Removed stale/duplicate content from older versions
  - Added production security checklist

---

## [1.7.0] - 2026-03-10

### Added

- **View Doctor Details Modal** (Patient Booking Page):
  - New "View Details" button on every doctor card in `patient-book.html`
  - Opens a clean slide-up modal with full doctor profile:
    - Doctor avatar icon
    - Full name, primary specialty, and qualification
    - Years of experience badge
    - Primary specialty highlighted in blue pill
    - All additional specialties as individual tags
    - All departments as green tags
  - Modal closes on backdrop click or "Close" button
  - Integrated with `showDoctorDetails(doctor)` JS function
  - No extra API calls — uses data already loaded on the page

- **Consolidated Master Database SQL**:
  - Created `config/medicare-complete-database.sql` — single file to set up the entire database
  - Includes all 11 tables in correct dependency order with full constraints
  - Replaces: `database-schema.sql`, `database-migration.sql`, `doctor-table-migration.sql`, `multi-department-migration.sql`, `multi-specialty-migration.sql`, `add-foreign-keys.sql`
  - Includes default admin account, all 24 departments, and all 30 specialties
  - Safe to re-run (`CREATE TABLE IF NOT EXISTS`, `INSERT ... ON DUPLICATE KEY UPDATE`)

### Fixed

- **Critical: Doctor Lookup in Appointment Booking** (`app/patient/submit-booking.php`):
  - Removed hardcoded `$doctorDirectory` map that used old string IDs (`cardio-1`, `gm-1`, etc.)
  - Now fetches doctor name, specialty, and photo from the database using the numeric doctor ID
  - Appointments now correctly record the actual doctor name instead of `"Unknown"`
  - Returns proper error and redirects if selected doctor is inactive or not found

- **Edit Doctor Modal Phone Bug** (`app/admin/manage-doctors.php`):
  - Fixed: `document.getElementById('edit_phone').value = document.phone;`
  - Corrected to: `document.getElementById('edit_phone').value = doctor.phone;`
  - Phone number now correctly pre-fills when opening the Edit Doctor modal

### Removed (Cleanup)

- **Deleted test/debug files**:
  - `clear-log.php`, `diagnose-password.php`, `form-test.php`, `test-password.php`, `verify-update.php`, `view-logs.php` (root)
  - `app/admin/test-session.php`, `app/admin/test-settings.html`
  - `app/patient/test-settings.html`

- **Deleted stale documentation files**:
  - `PASSWORD_CHANGE_FIX_SUMMARY.md`, `PATIENT_BOOKING_GUIDE.md`, `QUICK_START.txt`, `SYSTEM_PATHS.md`

- **Deleted old migration SQL files** (consolidated into `medicare-complete-database.sql`):
  - `config/database-migration.sql`
  - `config/doctor-table-migration.sql`
  - `config/multi-department-migration.sql`
  - `config/multi-specialty-migration.sql`
  - `config/add-foreign-keys.sql`
  - `config/README-MIGRATIONS.md`

---

## [1.6.0] - 2026-03-10

### Added

- **Multi-Specialty Support**: Doctors can now have multiple specialties beyond their primary specialty
  - Created `specialties` master table with 30 medical specialties
  - Created `doctor_specialties` junction table for many-to-many relationships
  - Database migration: config/multi-specialty-migration.sql
  - Specialties include: Anesthesiology, Cardiology, Dermatology, Emergency Medicine, Endocrinology, Family Medicine, Gastroenterology, General Surgery, Geriatrics, Hematology, Infectious Disease, Internal Medicine, Nephrology, Neurology, Obstetrics & Gynecology, Oncology, Ophthalmology, Orthopedics, Otolaryngology (ENT), Pain Management, Pathology, Pediatrics, Physical Medicine, Preventive Medicine, Psychiatry, Pulmonology, Radiology, Rheumatology, Sports Medicine, Urology

- **Additional Specialties Selection Interface** (Admin):
  - Added "Additional Specialties" checkbox section in manage-doctors.php
  - Appears below primary specialty dropdown
  - 3-column grid layout with 30 specialty options
  - Optional field - doctors can have 0 or more additional specialties
  - Applied to both "Add Doctor" and "Edit Doctor" modals
  - Visual feedback with hover effects and checked states
  - Success messages show count of additional specialties
  - Files modified:
    - app/admin/manage-doctors.php (backend + UI + JavaScript)

- **Additional Specialties Display**:
  - Doctor table now loads additional specialties via LEFT JOIN
  - Edit modal pre-populates additional specialties checkboxes
  - Patient booking page shows "Also: [specialties]" on doctor cards
  - Displays up to 3 additional specialties with "+N more" indicator
  - Enhanced doctor profile information across all interfaces

### Changed

- **API Enhancements**:
  - `get-doctors-by-department.php` updated to include `additional_specialties` field
  - Query now uses LEFT JOIN with `doctor_specialties` table
  - GROUP_CONCAT aggregates multiple specialties into comma-separated string
  - Backward compatible with existing code
  - Files modified:
    - app/includes/get-doctors-by-department.php

- **Database Schema**:
  - Doctor data now includes:
    - Primary specialty (specialty column in doctors table)
    - Additional specialties (doctor_specialties junction table)
  - Migration automatically populates existing specialties into junction table
  - Enables richer doctor profiles and better specialty-based filtering

- **Doctor Management**:
  - Add doctor: Handles both primary specialty and additional specialties arrays
  - Edit doctor: Deletes and re-inserts additional specialties on update
  - Database updates use prepared statements for security
  - Success messages show specialty counts

- **Patient Booking Experience**:
  - Doctor cards now display additional areas of expertise
  - Helps patients make informed decisions about doctor selection
  - Compact display: "Also: Cardiology, Oncology +2 more"
  - Files modified:
    - public/patient-book.html (JavaScript rendering function)

- **UI Components**:
  - 3-column checkbox grid (30 specialties)
  - Max-height with scrollbar for long lists
  - CSS classes: `.edit-spec-checkbox` for targeting in JavaScript
  - Hover effects and smooth transitions

## [1.5.0] - 2026-03-10

### Added

- **Doctor Search Functionality** (Admin):
  - Added search by doctor name in `manage-doctors.php`
  - Search input with icon and enter-to-search functionality
  - Real-time name matching with SQL LIKE query
  - Search preserves department and status filters
  - Combined filtering: status + department + search name
  - Clear filters button (appears when any filter is active)
  - Enhanced filter info display showing active filters and result count
  - Files modified:
    - app/admin/manage-doctors.php (backend + UI + JavaScript)

- **Doctor Search API Enhancement**:
  - Updated `get-doctors-by-department.php` to support search parameter
  - API accepts `?search=name` parameter for name-based filtering
  - Dynamic query builder supports department + search combinations
  - Backward compatible with existing department-only filtering
  - Files modified:
    - app/includes/get-doctors-by-department.php

- **Dynamic Patient Booking Page**:
  - Complete redesign of `patient-book.html` from static to dynamic
  - Departments load automatically from database via API
  - Department cards show real doctor count
  - Department descriptions displayed for all 20 departments
  - Doctor search input with real-time filtering
  - Search by doctor name while viewing department doctors
  - Debounced search (300ms) for optimal performance
  - Multiple loading states:
    - Initial department loading spinner
    - Doctor loading spinner when selecting department
    - "Select department" prompt when no department chosen
    - "No doctors found" message with retry suggestion
  - Doctor cards display:
    - Full name, specialty, and experience years
    - Multiple departments (up to 2 shown, +count for more)
    - Responsive grid layout with hover effects
    - Selected state with blue highlight
  - Proper form integration:
    - `doctorId` field sends database ID (not hardcoded values)
    - `department` field sends selected department name
  - Error handling:
    - API error messages with retry button
    - URL parameter error display with auto-dismiss
  - Files modified:
    - public/patient-book.html (complete UI overhaul + JavaScript)

### Changed

- **Admin Filter Tabs**:
  - Status tabs (All/Active/Inactive) now preserve both department and search filters
  - Dynamic parameter building in PHP for tab URLs
  - Maintains user's filter context when switching between tabs
  - Files modified:
    - app/admin/manage-doctors.php

- **Filter UI Layout**:
  - Reorganized filters section with responsive flex layout
  - Department filter and search input side-by-side on larger screens
  - Stack vertically on mobile devices
  - Clear filters button repositioned for better UX
  - Files modified:
    - app/admin/manage-doctors.php

### Technical Details

- **Backend Query Optimization**:
  - Dynamic WHERE clause builder in both admin and API
  - Prepared statements with variable parameter binding
  - Support for 0, 1, 2, or 3 simultaneous filters (status/department/search)
  - GROUP BY with GROUP_CONCAT for multi-department aggregation

- **Frontend Architecture**:
  - Vanilla JavaScript (no framework dependencies)
  - Async/await for API calls
  - Debouncing for search input optimization
  - Feather icons integration with dynamic replacement
  - Responsive Tailwind CSS styling maintained

- **API Response Format**:
  ```json
  {
    "success": true,
    "doctors": [
      {
        "id": 1,
        "full_name": "Dr. John Smith",
        "specialty": "Cardiology",
        "qualification": "MD, FACC",
        "experience_years": 15,
        "departments": "Cardiology Department, Emergency Department"
      }
    ],
    "count": 1
  }
  ```

### Database

- No schema changes required (uses existing tables and indexes)
- Query performance: Uses LEFT JOIN with indexed columns
- Filter combinations optimized with WHERE clause ordering

## [1.4.0] - 2026-03-10

### Added

- **Multi-Department Support**: Doctors can now belong to multiple departments
  - Created `doctor_departments` junction table for many-to-many relationships
  - Created `departments` master table with 20 standard hospital departments
  - Database migration: config/multi-department-migration.sql
  - Backward compatible: original `department` column retained in `doctors` table

- **Department Selection Interface** (Admin):
  - Replaced single department dropdown with multi-select checkboxes
  - Checkbox grid with 20 departments in scrollable container
  - Applied to both "Add Doctor" and "Edit Doctor" modals
  - Visual feedback: hover effects and checked states
  - Validation: At least one department must be selected
  - Success messages show number of departments assigned

- **Department-Based Doctor APIs**:
  - Created `get-doctors-by-department.php` API endpoint
    - GET without parameters: Returns all active doctors with their departments
    - GET with `?department=Name`: Returns doctors in specific department
    - Returns doctor details: id, name, specialty, qualification, experience
  - Created `get-active-departments.php` API endpoint
    - Returns all departments with active doctors
    - Includes doctor count per department
  - Files created:
    - app/includes/get-doctors-by-department.php
    - app/includes/get-active-departments.php

### Changed

- **Admin Doctor Management**:
  - Add doctor now accepts multiple departments via checkboxes
  - Edit doctor now accepts multiple departments via checkboxes
  - Doctor table displays all departments (abbreviated, comma-separated)
  - Backend inserts/updates departments in junction table
  - Delete operations cascade to remove department associations
  - Files modified:
    - app/admin/manage-doctors.php (backend + UI)

- **Department Display**:
  - Doctor listings show all assigned departments
  - Department names abbreviated (removes " Department" suffix)
  - Multi-line support for doctors with many departments

### Technical Details

- **Database Schema**:
  - `doctor_departments` table: id, doctor_id, department, created_at
  - Unique constraint on (doctor_id, department) prevents duplicates
  - Foreign key with CASCADE delete ensures data integrity
  - Indexed on doctor_id and department for query performance
  - `departments` table: id, name, description, status, created_at
  - Unique constraint on department name

- **Backend Logic**:
  - Add doctor: Inserts into `doctors` table, then loops through selected departments inserting into `doctor_departments`
  - Edit doctor: Deletes existing department associations, then inserts new selections
  - Load doctors: Uses LEFT JOIN with GROUP_CONCAT to get all departments as comma-separated string
  - Departments array passed to JavaScript via `departments_array` field

- **Frontend Updates**:
  - Checkbox grid uses `name="departments[]"` for array submission
  - Edit modal JavaScript unchecks all boxes, then checks doctor's current departments
  - Uses `.edit-dept-checkbox` class for targeted selection
  - Department container has max-height with scroll for better UX

### Patient Features (Prepared)

- **Infrastructure Ready**:
  - APIs ready for patient-side department selection
  - patient-book.html already has department UI (currently static)
  - Future update will connect UI to dynamic APIs
  - Patients will be able to:
    - Select department first
    - See only doctors in that department
    - Search/filter departments
    - View doctor details before booking

## [1.3.1] - 2026-03-10

### Added

- **Edit Doctor Functionality**: Admin can now edit doctor details
  - Added "Edit" button in doctor management table for each doctor
  - Created edit doctor modal with pre-populated form fields
  - Backend handler processes doctor updates (all fields except password)
  - Email uniqueness validation during edit (prevents duplicate emails)
  - Changes reflect immediately on both admin and doctor sides
  - Files modified:
    - app/admin/manage-doctors.php (added edit modal, JavaScript functions, backend handler)

- **Password Reset for Doctors**: Admin can reset doctor passwords
  - Added optional "New Password" field in edit doctor modal
  - Critical for password recovery when doctors forget their passwords
  - Password field is optional - leave blank to keep current password
  - Enter new password (min 6 characters) to reset doctor's password
  - Password is securely hashed using bcrypt before storage
  - Doctor must use new password to login after reset
  - Warning message displayed to admin about password reset consequences
  - Files modified:
    - app/admin/manage-doctors.php (added password reset field and backend logic)

- **Favicon Support**: Added favicon to all new pages
  - Added favicon links to new admin pages:
    - app/admin/new-admin-dashboard.php
    - app/admin/manage-doctors.php
    - app/admin/reports.php
  - Verified favicon on all doctor pages (already present)
  - Favicon path: `../../public/assets/images/favicon.svg`

### Changed

- **Specialty and Department Dropdowns**: Converted to predefined select options
  - Changed specialty field from text input to dropdown with 21 medical specialties
  - Changed department field from text input to dropdown with 20 hospital departments
  - Prevents typos and ensures consistency in doctor records
  - Available specialties: Cardiology, Dermatology, Emergency Medicine, Endocrinology, Family Medicine, Gastroenterology, General Surgery, Gynecology, Internal Medicine, Neurology, Obstetrics, Oncology, Ophthalmology, Orthopedics, Otolaryngology (ENT), Pediatrics, Psychiatry, Pulmonology, Radiology, Rheumatology, Urology
  - Available departments: Cardiology, Dermatology, Emergency, Endocrinology, General Medicine, Gastroenterology, General Surgery, OB/GYN, Internal Medicine, Neurology, Oncology, Ophthalmology, Orthopedics, ENT, Pediatrics, Psychiatry, Pulmonology, Radiology, Rheumatology, Urology
  - Applied to both "Add Doctor" and "Edit Doctor" modals
  - Edit modal automatically selects current specialty/department when editing
  - Files modified:
    - app/admin/manage-doctors.php (replaced text inputs with select dropdowns)

### Technical Details

- **Edit Doctor Operation**:
  - SQL UPDATE query updates all doctor fields
  - Optional password update - checks if new_password field is populated
  - If password provided: hashes with PASSWORD_DEFAULT and updates database
  - If password empty: updates all fields except password
  - Email validation prevents conflicts with existing doctor emails
  - Form validation ensures required fields (name, email, phone, specialty, department) are filled
  - Changes persist in database and reflect in real-time
  - Doctor can continue using system with updated details on next page load

- **Security**:
  - All passwords hashed using bcrypt (PASSWORD_DEFAULT)
  - Prepared statements prevent SQL injection in all operations
  - Password reset requires admin authentication
  - Minimum password length: 6 characters

## [1.3.0] - 2026-03-10

### Added

- **3-Tier Architecture**: Implemented new role-based system structure
  - **Admin Role** (NEW): Super-user role for managing doctors and viewing system reports
    - Created new admin dashboard (new-admin-dashboard.php) with doctor statistics and system overview
    - Created doctor management interface (manage-doctors.php) for adding/viewing/managing doctors
    - Admin can add new doctors with full details (name, email, phone, specialty, department, qualification, experience)
    - Admin can activate/deactivate doctor accounts
    - Admin has access to system-wide reports and analytics
    - Files added:
      - app/admin/new-admin-dashboard.php
      - app/admin/manage-doctors.php
  - **Doctor Role** (TRANSFORMED FROM ADMIN): Middle-tier role for managing appointments
    - Transformed existing admin section into doctors section
    - Doctors now handle all appointment management (approve, cancel, reschedule, complete)
    - Created doctor login portal (public/doctor-login.html)
    - Created doctor authentication handler (app/doctor/doctor-login.php)
    - Doctors have dedicated dashboard showing appointment statistics
    - Doctor notifications stored in separate table (doctor_notifications)
    - Files created/modified:
      - public/doctor-login.html
      - app/doctor/doctor-login.php
      - app/doctor/doctor-dashboard.php
      - app/doctor/doctor-appointments.php
      - app/doctor/doctor-settings.php
      - app/doctor/doctor-logout.php
  - **Patient Role** (EXISTING): End-users who book appointments
    - No changes to patient functionality
    - Patients continue to use existing interfaces

- **Database Schema**: New tables for doctor management
  - Created `doctors` table with fields:
    - id, full_name, email, phone, password (hashed)
    - specialty, department, photo, qualification, experience_years
    - status (active/inactive), created_at, updated_at
    - Indexes on specialty, department, status for performance
  - Created `doctor_notifications` table for doctor-specific notifications
    - Similar structure to patient_notifications
    - Foreign key relationship with doctors and appointments tables
  - Migration file: config/doctor-table-migration.sql

### Changed

- **Authentication System**: Separated doctor authentication from admin
  - Doctor login checks status='active' before allowing access
  - Doctor sessions include specialty and department information
  - All doctor pages check for role='doctor' instead of role='admin'
  - Session redirects point to appropriate login pages by role

- **Notification System**: Separated doctor notifications from admin notifications
  - Doctor appointment actions create entries in doctor_notifications table
  - Patient notifications remain unchanged
  - Supports multi-level notification tracking

- **Navigation & UI**: Updated interfaces to reflect new roles
  - Admin dashboard focuses on doctor management and system reports
  - Doctor dashboard focuses on appointment management
  - All navigation links updated to reflect new structure
  - Page titles and headings changed from "Admin" to "Doctor" in doctor section

### Technical Details

- **Password Security**: All doctor passwords use bcrypt hashing (PASSWORD_DEFAULT)
- **Session Management**: Centralized session configuration used across all roles
- **Database Integrity**: Foreign key constraints maintain referential integrity
- **Status Management**: Inactive doctors cannot log in; existing sessions remain valid until logout

## [1.2.0] - 2026-03-10

### Fixed

- **Session Persistence Issue**: Fixed sessions expiring when switching browser tabs
  - Created centralized session configuration (config/session-config.php)
  - Set proper session cookie parameters to persist until browser closes
  - Configured session garbage collection to 24 hours (86400 seconds)
  - Enabled security features: httponly, SameSite=Lax
  - Updated all PHP files to use centralized session management
  - Sessions now remain active across all tabs and page navigation
  - Prevents "please login again" issue when returning to tabs

### Changed

- **Password Change Simplified**: Removed current password verification requirement
  - Users can now change their password without entering their current password
  - Streamlined password change form (removed "Current Password" field)
  - Applies to both admin and patient users
  - Backend validation updated to skip current password check
  - Files modified:
    - app/auth/change-password.php
    - app/admin/admin-settings.php
    - app/patient/patient-settings.php
  - **Note**: This reduces security but improves user experience. Users only need to be logged in to change their password.

### Fixed

- **Password Change Feature**: Resolved issues with password change functionality for both admin and patient users
  - Added output buffering to prevent accidental whitespace/output before JSON responses
  - Implemented comprehensive error handling with proper HTTP status codes
  - Added explicit buffer cleaning (ob_clean) before each JSON response
  - Fixed potential session-related issues with proper exit statements
  - Created dedicated error log file (password-change-errors.log) for debugging
  - Enhanced frontend JavaScript error handling:
    - Added empty response detection
    - Implemented network error handlers (onerror, ontimeout)
    - Improved session expiration handling with automatic redirect
    - Added detailed console logging for debugging
    - Better user-facing error messages
  - Files modified:
    - app/auth/change-password.php
    - app/admin/admin-settings.php
    - app/patient/patient-settings.php

### Added

- Created comprehensive diagnostic tool (diagnose-password.php) for troubleshooting password change issues
- Added XHR timeout and network error handlers in both admin and patient settings

### Changed

- Improved error logging in password change backend with dedicated log file
- Enhanced JSON response handling with output buffer management
- Updated HTTP status code handling in frontend (401 for unauthorized, 0 for network errors)

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
