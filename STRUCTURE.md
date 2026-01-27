# Hospital Management System - File Structure

## 📁 New Organized Structure

```
hospital/
│
├── 📁 config/                      # Configuration files
│   ├── db-config.php              # Database connection & functions
│   └── database-schema.sql        # Complete database schema
│
├── 📁 app/                        # Application logic (Backend)
│   │
│   ├── 📁 admin/                  # Administrator features
│   │   ├── admin-dashboard.php    # Admin dashboard
│   │   ├── admin-appointments.php # Appointment management
│   │   ├── admin-login.php        # Admin authentication handler
│   │   ├── admin-logout.php       # Admin logout handler
│   │   └── admin-settings.php     # Admin settings
│   │
│   ├── 📁 patient/                # Patient features
│   │   ├── patient-dashboard.php  # Patient dashboard
│   │   ├── patient-appointments.php # View appointments
│   │   ├── patient-profile.php    # Profile management
│   │   ├── patient-settings.php   # Patient settings
│   │   └── submit-booking.php     # Appointment booking handler
│   │
│   ├── 📁 auth/                   # Authentication & authorization
│   │   ├── login.php              # Patient login handler
│   │   ├── register.php           # Registration handler
│   │   ├── logout.php             # Patient logout handler
│   │   ├── change-password.php    # Password change handler
│   │   └── check-session.php      # Session validation
│   │
│   └── 📁 includes/               # Shared utilities & helpers
│       ├── feedback.php           # Feedback handler
│       ├── get-notifications.php  # Admin notifications API
│       ├── get-patient-notifications.php # Patient notifications API
│       ├── mark-notifications-read.php   # Mark admin notifications as read
│       └── mark-patient-notifications-read.php # Mark patient notifications as read
│
├── 📁 public/                     # Publicly accessible files
│   │
│   ├── 📁 assets/                 # Static resources
│   │   ├── 📁 css/                # Stylesheets
│   │   │   ├── dark-mode.css
│   │   │   └── responsive-sidebar.css
│   │   │
│   │   ├── 📁 js/                 # JavaScript files
│   │   │   ├── dark-mode.js
│   │   │   ├── feedback-form.js
│   │   │   ├── init.js
│   │   │   ├── mobile-menu.js
│   │   │   └── sidebar-toggle.js
│   │   │
│   │   └── 📁 images/             # Images & icons
│   │       └── favicon.svg
│   │
│   ├── index.html                 # Homepage
│   ├── about.html                 # About page
│   ├── services.html              # Services page
│   ├── privacy.html               # Privacy policy
│   ├── terms.html                 # Terms of service
│   │
│   ├── login.html                 # Patient login form
│   ├── register.html              # Registration form
│   ├── admin-login.html           # Admin login form
│   │
│   ├── patient-book.html          # Appointment booking form
│   ├── patient-messages.html      # Patient messages
│   ├── patient-prescriptions.html # Patient prescriptions
│   └── patient-records.html       # Medical records
│
├── 📁 docs/                       # Documentation
│   ├── readme.md                  # Setup instructions
│   └── QUICK-REFERENCE.md         # Quick reference guide
│
└── index.php                      # Root entry point (redirects to public/index.html)
```

## 🎯 Structure Benefits

### 1. **Separation of Concerns**

- **config/** - All configuration in one place
- **app/** - Backend logic organized by feature
- **public/** - Frontend files separate from backend

### 2. **Security**

- Sensitive files (config, app logic) are separate from public files
- Easier to configure web server to restrict access to non-public directories

### 3. **Maintainability**

- Feature-based organization (admin, patient, auth)
- Easy to locate and update related files
- Clear distinction between user types

### 4. **Scalability**

- Easy to add new features in appropriate directories
- Can add new user roles by creating new directories
- Simple to implement access control

## 🔄 Path Updates Required

After reorganization, you'll need to update file paths in your code:

### PHP Files (require/include statements)

```php
// OLD: require_once 'db-config.php';
// NEW: require_once __DIR__ . '/../../config/db-config.php';

// Or use absolute path from root
require_once $_SERVER['DOCUMENT_ROOT'] . '/hospital/config/db-config.php';
```

### HTML Files (links and form actions)

```html
<!-- OLD: href="../assets/css/style.css" -->
<!-- NEW: href="assets/css/style.css" -->

<!-- OLD: action="../php/login.php" -->
<!-- NEW: action="../app/auth/login.php" -->
```

### Redirects in PHP

```php
// OLD: header('Location: ../html/login.html');
// NEW: header('Location: ../../public/login.html');
```

## 📋 Next Steps

1. **Update all file paths** in PHP and HTML files
2. **Test all pages** to ensure links work correctly
3. **Update .htaccess** if using Apache mod_rewrite
4. **Update documentation** with new paths
5. **Configure web server** to serve from public/ directory (optional but recommended)

## 🌐 Web Server Configuration (Optional - Recommended)

For production, configure your web server document root to `public/` directory:

### Apache (.htaccess in root)

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^$ public/ [L]
    RewriteRule (.*) public/$1 [L]
</IfModule>
```

### Nginx

```nginx
location / {
    root /path/to/hospital/public;
    try_files $uri $uri/ =404;
}

location /app {
    deny all;
}

location /config {
    deny all;
}
```

## 🔒 Security Recommendations

1. Ensure **config/** and **app/** directories are not web-accessible
2. Add `.htaccess` to deny direct access to config/ and app/
3. Keep all sensitive data in config files outside public directory
4. Use environment variables for database credentials in production
