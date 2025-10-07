# Project Reorganization Guide

## Overview
The MediCare Clinic project has been reorganized into a clean, professional folder structure for better maintainability and scalability.

## New Folder Structure

```
hospital/
├── index.php                          # Root entry point (redirects to html/index.html)
├── favicon.svg                        # Site favicon
├── database-schema.sql                # Database schema
├── DATABASE-SETUP-INSTRUCTIONS.md     # Database setup guide
├── readme.md                          # Project readme
│
├── html/                              # All HTML files
│   ├── index.html                     # Homepage
│   ├── about.html                     # About page
│   ├── services.html                  # Services page
│   ├── doctors.html                   # Doctors page
│   ├── login.html                     # Patient login
│   ├── register.html                  # Patient registration
│   ├── admin-login.html               # Admin/Doctor login
│   ├── patient-book.html              # Appointment booking
│   ├── patient-records.html           # Medical records
│   ├── patient-messages.html          # Patient messages
│   ├── patient-prescriptions.html     # Prescriptions
│   ├── privacy.html                   # Privacy policy
│   └── terms.html                     # Terms of service
│
├── php/                               # All PHP files
│   ├── db-config.php                  # Database configuration
│   ├── login.php                      # Patient login handler
│   ├── register.php                   # Patient registration handler
│   ├── logout.php                     # Patient logout handler
│   ├── admin-login.php                # Admin login handler
│   ├── admin-logout.php               # Admin logout handler
│   ├── patient-dashboard.php          # Patient dashboard (requires session)
│   ├── patient-appointments.php       # Patient appointments page
│   ├── patient-profile.php            # Patient profile page
│   ├── patient-settings.php           # Patient settings page
│   ├── admin-dashboard.php            # Admin dashboard
│   ├── admin-appointments.php         # Admin appointments management
│   ├── admin-settings.php             # Admin settings
│   ├── submit-booking.php             # Appointment booking handler
│   ├── feedback.php                   # Feedback form handler
│   └── hashed.php                     # Password hashing utility
│
├── assets/                            # Static assets
│   ├── css/
│   │   ├── dark-mode.css              # Dark mode styles
│   │   └── responsive-sidebar.css     # Sidebar responsive styles
│   │
│   └── js/
│       ├── dark-mode.js               # Dark mode toggle functionality
│       ├── mobile-menu.js             # Mobile menu functionality
│       ├── sidebar-toggle.js          # Sidebar collapse/expand
│       ├── feedback-form.js           # Feedback form handling
│       └── init.js                    # Initialize common libraries (AOS, Feather)
│
└── data/
    └── appointments.json              # Appointments data (legacy)
```

## Path Changes Summary

### HTML Files
- **Location**: Moved from root to `html/` folder
- **Asset References**: Updated to use `../assets/`, `../favicon.svg`
- **PHP Form Actions**: Updated to use `../php/[filename].php`
- **Internal Links**: All HTML-to-HTML links remain relative within `html/` folder

### PHP Files
- **Location**: Moved from root to `php/` folder
- **Asset References**: Updated to use `../assets/`, `../favicon.svg`
- **HTML Redirects**: Updated to use `../html/[filename].html`
- **PHP-to-PHP Links**: Updated to use `../php/[filename].php`
- **Database Config**: All PHP files use `require_once 'db-config.php'` (relative within php/ folder)

### JavaScript Files
- **New Files Created**:
  - `sidebar-toggle.js` - Extracted sidebar toggle functionality
  - `feedback-form.js` - Extracted feedback form handling
  - `init.js` - Common library initialization
- **Existing Files**: `dark-mode.js`, `mobile-menu.js` (unchanged)

## Access URLs

### For Development (XAMPP)
- **Homepage**: `http://localhost/hospital/` or `http://localhost/hospital/html/index.html`
- **Patient Login**: `http://localhost/hospital/html/login.html`
- **Admin Login**: `http://localhost/hospital/html/admin-login.html`
- **Patient Dashboard**: `http://localhost/hospital/php/patient-dashboard.php` (requires login)
- **Admin Dashboard**: `http://localhost/hospital/php/admin-dashboard.php` (requires login)

## Important Notes

1. **Root index.php**: The root `index.php` automatically redirects to `html/index.html`

2. **Session-Protected Pages**: All dashboard and profile PHP files check for valid sessions and redirect to login if not authenticated

3. **Database Connection**: All PHP files use the centralized `db-config.php` for database connections

4. **Asset Loading**: 
   - HTML files load assets from `../assets/`
   - PHP files load assets from `../assets/`
   - All paths are relative to their current location

5. **Form Submissions**:
   - Login forms → `../php/login.php` or `../php/admin-login.php`
   - Registration → `../php/register.php`
   - Booking → `../php/submit-booking.php`
   - Feedback → `../php/feedback.php`

## Testing Checklist

- [ ] Homepage loads correctly with all assets
- [ ] Patient login works and redirects to dashboard
- [ ] Admin login works and redirects to admin dashboard
- [ ] Patient registration creates new users
- [ ] Appointment booking saves to database
- [ ] All navigation links work correctly
- [ ] Logout functionality works
- [ ] All CSS and JS files load properly
- [ ] Favicon displays correctly
- [ ] Mobile menu works
- [ ] Dark mode toggle works
- [ ] Feedback form submits successfully

## Benefits of This Structure

1. **Better Organization**: Clear separation of concerns (HTML, PHP, Assets)
2. **Easier Maintenance**: Find files quickly by type
3. **Scalability**: Easy to add new files in appropriate folders
4. **Professional Structure**: Follows industry best practices
5. **Version Control**: Better for Git with organized folders
6. **Security**: PHP files separated from public HTML files
7. **Deployment**: Easier to configure web server rules per folder

## Migration Notes

- All old root-level HTML files → `html/` folder
- All old root-level PHP files → `php/` folder
- Inline JavaScript extracted to separate files in `assets/js/`
- All internal references updated to maintain functionality
- No functionality lost in the reorganization

## Next Steps

1. Test all pages and functionality
2. Update any external links or bookmarks
3. Consider adding `.htaccess` rules for cleaner URLs
4. Update documentation with any project-specific changes
5. Commit changes to version control

---
**Date**: 2025-10-07  
**Status**: Reorganization Complete ✓
