# MediCare Clinic - Hospital Management System

![Version](https://img.shields.io/badge/version-1.1.0-blue.svg)
![Status](https://img.shields.io/badge/status-active-success.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1.svg)

A comprehensive web-based hospital management system designed to streamline healthcare operations through efficient appointment management, patient registration, and administrative oversight.

## 🚀 Features

### Patient Portal

- **User Registration & Authentication** - Secure account creation with email verification
- **Appointment Booking** - Schedule appointments with preferred doctors
- **Appointment Management** - View, reschedule, or cancel appointments
- **Real-time Notifications** - Get instant updates on appointment status
- **Profile Management** - Update personal information and preferences
- **Dark Mode Support** - Eye-friendly interface option

### Admin Dashboard

- **Appointment Oversight** - View and manage all clinic appointments
- **Status Updates** - Approve, reject, or reschedule appointments
- **Patient Management** - Access patient information and history
- **Analytics Dashboard** - View appointment statistics and trends
- **System Settings** - Configure clinic preferences and notifications

## 🛠️ Technology Stack

- **Frontend**: HTML5, TailwindCSS, JavaScript (Vanilla)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Server**: Apache (XAMPP)
- **Icons**: Feather Icons
- **Animations**: AOS (Animate On Scroll)

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server
- Modern web browser (Chrome, Firefox, Edge, Safari)

## 🔧 Installation

1. **Clone or download** the project to your XAMPP htdocs directory:

   ```bash
   cd C:\xampp\htdocs
   git clone [repository-url] hospital
   ```

2. **Import database**:
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `hospital_db`
   - Import the SQL files in order:
     - `config/database-schema.sql`
     - `config/database-migration.sql`
     - `config/add-foreign-keys.sql`

3. **Configure database connection**:
   - Open `config/db-config.php`
   - Update credentials if needed (default: root with no password)

4. **Access the application**:
   - Patient Portal: http://localhost/hospital/public/index.html
   - Admin Login: http://localhost/hospital/public/admin-login.html

## 📁 Project Structure

```
hospital/
├── app/
│   ├── admin/          # Admin dashboard pages
│   ├── auth/           # Authentication logic
│   ├── includes/       # Reusable components
│   └── patient/        # Patient portal pages
├── config/             # Database configuration and schema
├── public/             # Public-facing pages
│   ├── assets/
│   │   ├── css/       # Stylesheets
│   │   ├── images/    # Images and icons
│   │   └── js/        # JavaScript files
│   └── *.html         # HTML pages
├── CHANGELOG.md        # Version history and updates
├── SDD_MediCare_Clinic_System.md  # Software Design Document
└── SRS_MediCare_Clinic_System.md  # Software Requirements Specification
```

## 🔐 Default Credentials

### Admin Account

- **Email**: admin@medicare.com
- **Password**: Admin@123

### Test Patient Account

- **Email**: patient@example.com
- **Password**: Patient@123

> **Note**: Change default passwords in production environment

## 📝 Recent Updates

### Version 1.1.0 (February 13, 2026)

#### Bug Fixes

- ✅ Fixed favicon display across all public pages
- ✅ Resolved registration form submission issues
- ✅ Corrected logout functionality for admin and patient portals
- ✅ Eliminated PHP header warnings
- ✅ Improved JavaScript error handling

See [CHANGELOG.md](CHANGELOG.md) for detailed update history.

## 📚 Documentation

Comprehensive documentation is available:

- **[Software Requirements Specification (SRS)](SRS_MediCare_Clinic_System.md)** - Detailed functional and non-functional requirements
- **[Software Design Document (SDD)](SDD_MediCare_Clinic_System.md)** - System architecture and design specifications
- **[CHANGELOG](CHANGELOG.md)** - Version history and updates

## 🧪 Testing

All components have been tested for:

- ✅ User registration and login flows
- ✅ Appointment booking and management
- ✅ Admin appointment oversight
- ✅ Notification system
- ✅ Session management and security
- ✅ Cross-browser compatibility
- ✅ Responsive design on mobile devices

## 🔒 Security Features

- Password hashing using PHP's `password_hash()`
- Session-based authentication
- SQL injection prevention using prepared statements
- XSS protection through input sanitization
- CSRF token implementation
- Secure session management
- Access control for admin routes

## 🤝 Contributing

This is a production system. For maintenance and updates:

1. Review existing documentation (SRS, SDD)
2. Test changes in development environment
3. Update CHANGELOG.md with changes
4. Submit changes with detailed documentation

## 📞 Support

For technical support or questions:

- Review documentation in SRS and SDD
- Check CHANGELOG.md for known issues
- Contact system administrator

## 📄 License

Proprietary - MediCare Clinic Management System
All rights reserved.

## 🙏 Acknowledgments

- TailwindCSS for the utility-first CSS framework
- Feather Icons for the icon set
- AOS library for scroll animations

---

**Last Updated**: February 13, 2026  
**Current Version**: 1.1.0  
**Status**: Active Development
