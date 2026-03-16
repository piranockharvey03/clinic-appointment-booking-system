# MediCare Clinic - Hospital Management System

![Version](https://img.shields.io/badge/version-2.0.2-blue.svg)
![Status](https://img.shields.io/badge/status-active-success.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1.svg)

A comprehensive web-based hospital management system designed to streamline healthcare operations through efficient appointment management, patient registration, and administrative oversight.

## 🚀 Features

### Patient Portal

- **User Registration & Authentication** — Secure account creation and login
- **Dynamic Appointment Booking** — Select department, browse doctors, view their details, and choose a time slot
- **View Doctor Details** — See primary specialty, all additional specialties, departments, qualification, and experience
- **Appointment Management** — View, reschedule, or cancel appointments
- **Real-time Notifications** — Get instant updates on appointment status changes
- **Profile Management** — Update personal information and preferences
- **Dark Mode Support** — Eye-friendly interface option

### Admin Dashboard

- **Doctor Management** — Add, edit, activate/deactivate doctors with full profile including multiple departments and specialties
- **Appointment Oversight** — Browse all appointments by doctor and status via the Appointments page
- **Doctor Evaluation** — Per-doctor stats with appointment drill-down including cancel reasons
- **Analytics & Reports** — View appointment statistics and trends
- **Database Backup** — One-click backup from the Settings page
- **Activity Logs** — Full audit trail of doctor actions
- **System Settings** — Configure admin password and preferences

### Doctor Portal

- **Doctor Login** — Separate authentication for doctors
- **Scoped Appointments** — Doctors only see their own patients' appointments
- **Appointment Actions** — Approve, cancel (with reason), reschedule, or complete appointments
- **Patient Notifications** — Status changes automatically notify patients
- **Settings** — Update profile and password

## 🛠️ Technology Stack

- **Frontend**: HTML5, TailwindCSS (CDN), Vanilla JavaScript
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Server**: Apache (XAMPP)
- **Icons**: Feather Icons

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server (XAMPP recommended)
- Modern web browser (Chrome, Firefox, Edge, Safari)

## 🔧 Installation

1. **Clone or download** the project to your XAMPP `htdocs` directory:

   ```
   C:\xampp\htdocs\hospital\
   ```

2. **Import the database** (single file — sets up the complete schema):
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Import `config/medicare-complete-database.sql`
   - This creates the `medicare` database with all tables and default data

3. **Configure database connection** (if needed):
   - Open `config/db-config.php`
   - Default credentials: host=`localhost`, user=`root`, password=`""`, database=`medicare`

4. **Access the application**:
   - Public Home: http://localhost/hospital/public/index.html
   - Patient Login: http://localhost/hospital/public/login.html
   - Patient Register: http://localhost/hospital/public/register.html
   - Admin Login: http://localhost/hospital/public/admin-login.html
   - Doctor Login: http://localhost/hospital/public/doctor-login.html

## 🔑 Default Credentials

| Role  | Email              | Password |
| ----- | ------------------ | -------- |
| Admin | admin@hospital.com | admin123 |

> Doctors are created through the Admin panel. Patients register themselves.  
> **Change the default admin password immediately after first login.**

## 📁 Project Structure

```
hospital/
├── app/
│   ├── admin/          # Admin dashboard pages
│   │   ├── new-admin-dashboard.php
│   │   ├── manage-doctors.php
│   │   ├── admin-appointments.php
│   │   ├── doctor-evaluation.php
│   │   ├── reports.php
│   │   ├── admin-settings.php
│   │   ├── backup-database.php
│   │   ├── admin-login.php
│   │   └── admin-logout.php
│   ├── assets/         # Shared CSS and JS (dark mode, sidebar, mobile menu)
│   ├── auth/           # Authentication (login, logout, register, password change)
│   │   ├── login.php
│   │   ├── logout.php
│   │   ├── register.php
│   │   ├── change-password.php
│   │   └── check-session.php
│   ├── doctor/         # Doctor portal pages
│   │   ├── doctor-dashboard.php
│   │   ├── doctor-appointments.php
│   │   ├── doctor-login.php
│   │   ├── doctor-logout.php
│   │   └── doctor-settings.php
│   ├── includes/       # JSON API endpoints
│   │   ├── feedback.php
│   │   ├── get-active-departments.php
│   │   ├── get-doctors-by-department.php
│   │   ├── get-notifications.php
│   │   ├── get-patient-notifications.php
│   │   ├── mark-notifications-read.php
│   │   └── mark-patient-notifications-read.php
│   └── patient/        # Patient portal pages
│       ├── patient-dashboard.php
│       ├── patient-appointments.php
│       ├── patient-profile.php
│       ├── patient-settings.php
│       └── submit-booking.php
├── backups/            # Database backup files (git-ignored)
├── config/
│   ├── db-config.php                  # Database connection + logActivity()
│   ├── session-config.php             # Session security settings
│   └── medicare-complete-database.sql # ★ MASTER — use this to set up the DB
├── public/             # Public-facing HTML pages (no auth required)
├── CHANGELOG.md        # Version history
├── SECURITY.md         # Security controls and hardening guide
├── SDD_MediCare_Clinic_System.md
└── SRS_MediCare_Clinic_System.md
```

## 🗄️ Database Schema

All tables are defined in `config/medicare-complete-database.sql`:

| Table                   | Purpose                                  |
| ----------------------- | ---------------------------------------- |
| `users`                 | Patient accounts                         |
| `admin`                 | Administrator accounts                   |
| `doctors`               | Doctor profiles (primary specialty/dept) |
| `departments`           | Department master list (24 departments)  |
| `doctor_departments`    | Doctor ↔ Department (many-to-many)       |
| `specialties`           | Specialty master list (30 specialties)   |
| `doctor_specialties`    | Doctor ↔ Specialty (many-to-many)        |
| `appointments`          | Patient appointments                     |
| `notifications`         | Admin notifications                      |
| `patient_notifications` | Patient notifications                    |
| `doctor_notifications`  | Doctor notifications                     |
| `activity_logs`         | Audit trail for all user actions         |

## 🔒 Security

See [SECURITY.md](SECURITY.md) for the full security reference. Summary:

- Passwords hashed with bcrypt (`PASSWORD_DEFAULT`)
- All queries use prepared statements — no SQL injection possible
- Session cookies: `httponly=true`, `samesite=Lax`, strict mode
- Role-based access control on every protected page and API endpoint
- Appointment data scoped per doctor — doctors cannot see other doctors' patients
- Patient notification endpoints enforce ownership (`patient_id = session user`)
- All debug/dev/test files removed from the codebase

### Production checklist

- [ ] Change default admin password
- [ ] Set a strong MySQL password for the `root` user (or create a dedicated DB user)
- [ ] Enable HTTPS and set `'secure' => true` in `config/session-config.php`
- [ ] Vendor CDN assets locally (`tailwindcss`, `feather-icons`, `jquery`)
- [ ] Add brute-force protection on login endpoints
- [ ] Review `backup-database.php` if a DB password is configured

## 📚 Documentation

- [SECURITY.md](SECURITY.md) — Security controls, hardening guide, and v2.x fixes
- [SRS_MediCare_Clinic_System.md](SRS_MediCare_Clinic_System.md) — Software Requirements Specification
- [SDD_MediCare_Clinic_System.md](SDD_MediCare_Clinic_System.md) — Software Design Document
- [CHANGELOG.md](CHANGELOG.md) — Version history

## 📄 License

Proprietary — MediCare Clinic Management System. All rights reserved.

---

**Last Updated**: March 16, 2026  
**Current Version**: 2.0.2
