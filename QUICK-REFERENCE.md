# Quick Reference Guide

## 🚀 Getting Started

### Access the Application
1. Start XAMPP (Apache + MySQL)
2. Navigate to: `http://localhost/hospital/`
3. The root redirects to the homepage automatically

## 📁 File Locations

### HTML Files → `html/` folder
- All static pages and patient-facing HTML files
- Forms that submit to PHP handlers

### PHP Files → `php/` folder  
- All backend logic and session-protected pages
- Database operations and authentication

### JavaScript → `assets/js/` folder
- `init.js` - Initializes Feather icons & AOS
- `dark-mode.js` - Dark mode toggle
- `mobile-menu.js` - Mobile navigation
- `sidebar-toggle.js` - Sidebar collapse/expand
- `feedback-form.js` - Feedback form handling

### CSS → `assets/css/` folder
- `dark-mode.css` - Dark mode styles
- `responsive-sidebar.css` - Sidebar responsive styles

## 🔗 Key URLs

| Page | URL |
|------|-----|
| Homepage | `http://localhost/hospital/html/index.html` |
| Patient Login | `http://localhost/hospital/html/login.html` |
| Patient Register | `http://localhost/hospital/html/register.html` |
| Admin Login | `http://localhost/hospital/html/admin-login.html` |
| Book Appointment | `http://localhost/hospital/html/patient-book.html` |
| Patient Dashboard | `http://localhost/hospital/php/patient-dashboard.php` |
| Admin Dashboard | `http://localhost/hospital/php/admin-dashboard.php` |

## 🔄 How Files Connect

### HTML → PHP (Form Submissions)
```
html/login.html → php/login.php → php/patient-dashboard.php
html/register.html → php/register.php → html/login.html
html/patient-book.html → php/submit-booking.php → php/patient-dashboard.php
```

### PHP → HTML (Redirects)
```
php/logout.php → html/login.html
php/admin-logout.php → html/admin-login.html
```

### Asset Loading
```
HTML files: ../assets/css/style.css, ../assets/js/script.js
PHP files: ../assets/css/style.css, ../assets/js/script.js
```

## 🛠️ Common Tasks

### Adding a New HTML Page
1. Create file in `html/` folder
2. Reference assets: `href="../assets/css/..."`
3. Link to PHP: `action="../php/handler.php"`
4. Link to other HTML: `href="other-page.html"` (relative)

### Adding a New PHP Page
1. Create file in `php/` folder
2. Add session check if needed
3. Include: `require_once 'db-config.php';`
4. Reference assets: `href="../assets/css/..."`
5. Link to HTML: `href="../html/page.html"`
6. Link to PHP: `href="other-page.php"` (relative)

### Adding JavaScript Functionality
1. Create file in `assets/js/` folder
2. Include in HTML: `<script src="../assets/js/your-file.js"></script>`
3. Include in PHP: `<script src="../assets/js/your-file.js"></script>`

## 📊 Database

- **Config**: `php/db-config.php`
- **Schema**: `database-schema.sql` (root)
- **Setup Guide**: `DATABASE-SETUP-INSTRUCTIONS.md` (root)

## 🔐 Authentication Flow

### Patient Login
1. User visits `html/login.html`
2. Form submits to `php/login.php`
3. PHP validates credentials
4. Redirects to `php/patient-dashboard.php`
5. Session maintained across pages

### Admin Login
1. User visits `html/admin-login.html`
2. Form submits to `php/admin-login.php`
3. PHP validates credentials
4. Redirects to `php/admin-dashboard.php`
5. Session maintained across pages

## 📝 Path Patterns

| From | To HTML | To PHP | To Assets |
|------|---------|--------|-----------|
| HTML file | `page.html` | `../php/file.php` | `../assets/` |
| PHP file | `../html/page.html` | `file.php` | `../assets/` |
| Root | `html/page.html` | `php/file.php` | `assets/` |

## ⚡ Quick Fixes

### Assets not loading?
- Check path uses `../assets/` from HTML/PHP folders
- Verify file exists in `assets/css/` or `assets/js/`

### Form not submitting?
- Check action points to `../php/handler.php`
- Verify PHP file exists in `php/` folder

### Redirect not working?
- Check PHP uses `../html/` for HTML redirects
- Check PHP uses relative path for PHP redirects

### Page shows old content?
- Clear browser cache (Ctrl+F5)
- Check you're editing files in correct folder (html/ or php/)

## 📦 Folder Summary

```
hospital/
├── html/          → 13 HTML files (all user-facing pages)
├── php/           → 16 PHP files (all backend logic)
├── assets/
│   ├── css/       → 2 CSS files
│   └── js/        → 5 JavaScript files
├── data/          → JSON data files
└── [root files]   → Config, docs, schema
```

## ✅ Testing Checklist

- [ ] Homepage loads with all assets
- [ ] Login/Register works
- [ ] Dashboards load after login
- [ ] Booking form submits
- [ ] Navigation links work
- [ ] Logout redirects correctly
- [ ] Mobile menu works
- [ ] Dark mode toggles

---
**Last Updated**: 2025-10-07
