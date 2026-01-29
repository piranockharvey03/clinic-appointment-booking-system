# Software Design Document (SDD)
# MediCare Clinic - Hospital Management System

**Document Version:** 1.0  
**Date:** January 29, 2026  
**Prepared by:** Software Architecture Team  
**Status:** Approved

---

## Document Revision History

| Version | Date | Author | Description |
|---------|------|--------|-------------|
| 1.0 | January 29, 2026 | Architecture Team | Initial Release |

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [System Architecture](#2-system-architecture)
3. [Component Design](#3-component-design)
4. [Data Design](#4-data-design)
5. [Interface Design](#5-interface-design)
6. [Security Design](#6-security-design)
7. [Detailed Design](#7-detailed-design)
8. [Deployment Design](#8-deployment-design)
9. [Appendices](#9-appendices)

---

## 1. Introduction

### 1.1 Purpose
This Software Design Document (SDD) provides a comprehensive architectural and detailed design for the MediCare Clinic Hospital Management System. It serves as a blueprint for developers, testers, and system architects, documenting design decisions, component interactions, data structures, and implementation strategies.

**Target Audience:**
- Software Developers and Programmers
- System Architects
- Database Administrators
- Quality Assurance Engineers
- Technical Project Managers
- System Maintenance Personnel

### 1.2 Scope
This document covers the complete design of the MediCare Clinic system, including:
- High-level system architecture
- Component and module design
- Database schema and data flow
- User interface design patterns
- Security architecture
- API and interface specifications
- Deployment architecture

### 1.3 Design Goals and Constraints

**Primary Design Goals:**
1. **Simplicity:** Easy to understand and maintain
2. **Modularity:** Clear separation of concerns
3. **Security:** Protect sensitive patient data
4. **Performance:** Fast response times and efficient operations
5. **Scalability:** Support growing user base
6. **Maintainability:** Easy to update and extend
7. **Reliability:** Consistent and error-free operation

**Design Constraints:**
- Technology Stack: PHP, MySQL, Apache (XAMPP environment)
- No external framework dependencies (plain PHP)
- Browser-based interface (no native mobile apps in v1.0)
- Limited to HTTP/HTTPS communication
- Single database server architecture
- Session-based authentication (no JWT/OAuth in v1.0)

### 1.4 References
- Software Requirements Specification (SRS) - MediCare Clinic v1.0
- PHP 7.4+ Documentation
- MySQL 5.7+ Documentation
- OWASP Secure Coding Practices
- IEEE Std 1016-2009: IEEE Standard for Software Design Descriptions

### 1.5 Overview
This document follows a top-down approach, starting with high-level architecture, then drilling down into component design, data design, interface design, and finally detailed implementation specifications.

---

## 2. System Architecture

### 2.1 Architectural Style
**Architecture Pattern:** Three-Tier Architecture (Layered)

The system follows a traditional three-tier web application architecture:
1. **Presentation Layer** (Client Tier): Web browsers with HTML, CSS, JavaScript
2. **Application Layer** (Logic Tier): PHP scripts processing business logic
3. **Data Layer** (Data Tier): MySQL database

```
┌─────────────────────────────────────────────────────────┐
│                  PRESENTATION LAYER                     │
│                                                         │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────┐  │
│  │   Public    │  │   Patient    │  │    Admin     │  │
│  │   Pages     │  │  Interface   │  │  Interface   │  │
│  │  (HTML/CSS) │  │ (HTML/CSS/JS)│  │(HTML/CSS/JS) │  │
│  └─────────────┘  └──────────────┘  └──────────────┘  │
└────────────────────────┬────────────────────────────────┘
                         │ HTTP/HTTPS
                         │ (POST/GET)
┌────────────────────────┴────────────────────────────────┐
│                  APPLICATION LAYER                      │
│                                                         │
│  ┌──────────────────────────────────────────────────┐  │
│  │           PHP Processing Engine                  │  │
│  ├──────────────────────────────────────────────────┤  │
│  │  ┌─────────┐  ┌──────────┐  ┌────────────────┐  │  │
│  │  │  Auth   │  │Appointment│  │  Notification  │  │  │
│  │  │ Module  │  │  Module   │  │    Module      │  │  │
│  │  └─────────┘  └──────────┘  └────────────────┘  │  │
│  │  ┌─────────┐  ┌──────────┐  ┌────────────────┐  │  │
│  │  │  User   │  │  Session │  │   Validation   │  │  │
│  │  │ Module  │  │  Module  │  │    Module      │  │  │
│  │  └─────────┘  └──────────┘  └────────────────┘  │  │
│  └──────────────────────────────────────────────────┘  │
└────────────────────────┬────────────────────────────────┘
                         │ MySQLi
                         │ (Prepared Statements)
┌────────────────────────┴────────────────────────────────┐
│                     DATA LAYER                          │
│                                                         │
│  ┌──────────────────────────────────────────────────┐  │
│  │              MySQL Database Server               │  │
│  ├──────────────────────────────────────────────────┤  │
│  │  ┌────────┐  ┌──────────────┐  ┌────────────┐   │  │
│  │  │ Users  │  │ Appointments │  │   Admin    │   │  │
│  │  │ Table  │  │    Table     │  │   Table    │   │  │
│  │  └────────┘  └──────────────┘  └────────────┘   │  │
│  │  ┌──────────────┐  ┌────────────────────────┐   │  │
│  │  │Notifications │  │ Patient_Notifications  │   │  │
│  │  │    Table     │  │        Table           │   │  │
│  │  └──────────────┘  └────────────────────────┘   │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

### 2.2 High-Level System Components

#### 2.2.1 Presentation Layer Components

**Public Website Module:**
- Landing page (index.html)
- Services page (services.html)
- About page (about.html)
- Legal pages (terms.html, privacy.html)
- Login/Registration forms

**Patient Portal Module:**
- Patient dashboard
- Appointment booking interface
- Appointment list view
- Profile management
- Notification panel

**Admin Portal Module:**
- Admin dashboard
- Appointment management interface
- Statistics dashboard
- Admin settings
- Notification management

#### 2.2.2 Application Layer Components

**Authentication & Authorization Module:**
- User registration service
- Login service (patient/admin)
- Password management
- Session management
- Access control

**Appointment Management Module:**
- Appointment creation service
- Appointment retrieval service
- Appointment update service
- Status management service
- Appointment validation

**Notification Module:**
- Notification creation service
- Notification retrieval service
- Read status management
- Admin notifications handler
- Patient notifications handler

**User Management Module:**
- Profile retrieval service
- Profile update service
- User validation service

**Database Connectivity Module:**
- Connection pooling
- Transaction management
- Error handling
- Query execution

#### 2.2.3 Data Layer Components

**Database Tables:**
- users (patient records)
- admin (administrator records)
- appointments (appointment records)
- notifications (admin notifications)
- patient_notifications (patient notifications)

### 2.3 Component Interaction Flow

**Appointment Booking Flow:**
```
┌────────┐     1. Submit Booking     ┌──────────────┐
│Patient │ ─────────────────────────►│ submit-      │
│Browser │                           │ booking.php  │
└────────┘                           └──────┬───────┘
    ▲                                       │
    │                                       │ 2. Validate
    │                                       │    Input
    │                                       ▼
    │                                ┌──────────────┐
    │                                │  Validation  │
    │                                │   Functions  │
    │                                └──────┬───────┘
    │                                       │
    │                                       │ 3. Generate
    │                                       │    Appt ID
    │                                       ▼
    │                                ┌──────────────┐
    │                                │   Database   │
    │                                │  Connection  │
    │                                └──────┬───────┘
    │                                       │
    │                                       │ 4. Insert
    │                                       │    Record
    │                                       ▼
    │                               ┌───────────────┐
    │                               │  Appointments │
    │                               │     Table     │
    │                               └───────┬───────┘
    │                                       │
    │                                       │ 5. Create
    │                                       │    Notifications
    │                                       ▼
    │                            ┌──────────────────────┐
    │                            │  Notification Tables │
    │                            └──────────┬───────────┘
    │                                       │
    │  6. Return Confirmation               │
    │◄──────────────────────────────────────┘
```

**Admin Approval Flow:**
```
┌────────┐   1. Approve Action    ┌──────────────┐
│ Admin  │ ──────────────────────►│   admin-     │
│Browser │                        │appointments. │
└────────┘                        │     php      │
    ▲                             └──────┬───────┘
    │                                    │
    │                                    │ 2. Verify
    │                                    │    Session
    │                                    ▼
    │                             ┌──────────────┐
    │                             │check-session.│
    │                             │     php      │
    │                             └──────┬───────┘
    │                                    │
    │                                    │ 3. Update
    │                                    │    Status
    │                                    ▼
    │                             ┌──────────────┐
    │                             │  Database    │
    │                             │  Update      │
    │                             └──────┬───────┘
    │                                    │
    │                                    │ 4. Create
    │                                    │    Patient
    │                                    │    Notification
    │                                    ▼
    │                         ┌─────────────────────┐
    │                         │Patient_Notifications│
    │                         │       Table         │
    │                         └─────────┬───────────┘
    │                                   │
    │   5. Return Success               │
    │◄──────────────────────────────────┘
```

### 2.4 Deployment Architecture

**Development Environment:**
```
┌────────────────────────────────────────┐
│      Developer Machine (Local)         │
│                                        │
│  ┌──────────────────────────────────┐  │
│  │         XAMPP Stack              │  │
│  │  ┌────────────────────────────┐  │  │
│  │  │  Apache HTTP Server        │  │  │
│  │  │  Port: 80/443              │  │  │
│  │  └────────────────────────────┘  │  │
│  │  ┌────────────────────────────┐  │  │
│  │  │  PHP 7.4+ Runtime          │  │  │
│  │  └────────────────────────────┘  │  │
│  │  ┌────────────────────────────┐  │  │
│  │  │  MySQL Server 5.7+         │  │  │
│  │  │  Port: 3306                │  │  │
│  │  └────────────────────────────┘  │  │
│  └──────────────────────────────────┘  │
│                                        │
│  Application Directory:                │
│  C:\xampp\htdocs\hospital\             │
└────────────────────────────────────────┘
```

**Production Environment (Recommended):**
```
┌─────────────────────────────────────────────────────┐
│              Cloud/On-Premise Server                │
│                                                     │
│  ┌───────────────────────────────────────────────┐  │
│  │          Load Balancer (Optional)             │  │
│  └─────────────────┬─────────────────────────────┘  │
│                    │                                │
│  ┌─────────────────┴─────────────────────────────┐  │
│  │          Web Server Tier                      │  │
│  │  ┌─────────────────────────────────────────┐  │  │
│  │  │  Apache HTTP Server with SSL/TLS        │  │  │
│  │  │  Port: 443 (HTTPS)                      │  │  │
│  │  └─────────────────────────────────────────┘  │  │
│  │  ┌─────────────────────────────────────────┐  │  │
│  │  │  PHP 7.4+ with OPcache                  │  │  │
│  │  └─────────────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────┘  │
│                    │                                │
│  ┌─────────────────┴─────────────────────────────┐  │
│  │          Database Server Tier                 │  │
│  │  ┌─────────────────────────────────────────┐  │  │
│  │  │  MySQL Server 5.7+                      │  │  │
│  │  │  Port: 3306 (Internal Only)             │  │  │
│  │  │  + Replication (Optional)               │  │  │
│  │  └─────────────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────┘  │
│                                                     │
│  ┌───────────────────────────────────────────────┐  │
│  │          Backup Server                        │  │
│  │  - Daily automated backups                    │  │
│  │  - Off-site storage                           │  │
│  └───────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
```

### 2.5 Technology Stack Summary

| Layer | Technology | Version | Purpose |
|-------|------------|---------|---------|
| Frontend | HTML5 | Latest | Structure |
| Frontend | CSS3 / Tailwind CSS | Latest / 3.x | Styling |
| Frontend | JavaScript (Vanilla) | ES6+ | Client-side logic |
| Frontend | Feather Icons | Latest | Icons |
| Frontend | AOS Library | 2.3.1 | Animations |
| Backend | PHP | 7.4+ | Server-side processing |
| Database | MySQL/MariaDB | 5.7+ / 10.3+ | Data storage |
| Web Server | Apache HTTP Server | 2.4+ | Request handling |
| Development | XAMPP | 7.4+ | Local environment |

---

## 3. Component Design

### 3.1 Authentication Module

#### 3.1.1 Component Overview
**Purpose:** Manage user authentication and session management  
**Location:** `/app/auth/`  
**Dependencies:** Database module, Session module

#### 3.1.2 Component Structure

```
auth/
├── login.php              # Patient login handler
├── register.php           # Patient registration handler
├── logout.php             # Logout handler
├── change-password.php    # Password change handler
└── check-session.php      # Session validation utility
```

#### 3.1.3 Key Functions

**register.php:**
```php
Function: handleRegistration()
Input: POST data (full-name, email, phone, password, confirm-password)
Processing:
  1. Sanitize and validate input
  2. Check email uniqueness
  3. Check phone uniqueness
  4. Hash password using password_hash()
  5. Insert user record into database
Output: JSON response {success: boolean, message: string}
Error Handling:
  - Validation errors return specific messages
  - Database errors return generic error
  - Duplicate email/phone detected and reported
```

**login.php:**
```php
Function: handleLogin()
Input: POST data (email, password)
Processing:
  1. Sanitize email input
  2. Query database for user by email
  3. Verify password using password_verify()
  4. Create session with user data
  5. Set session variables (user_id, user_name, user_role)
  6. Implement cache control headers
Output: JSON response {success: boolean, redirect: string}
Error Handling:
  - Invalid credentials return generic error
  - Account not found returns error
  - Database errors logged and generic error returned
Security Measures:
  - Session regeneration after login
  - Cache-Control headers prevent caching
  - Password never logged
```

**logout.php:**
```php
Function: handleLogout()
Input: None (uses session data)
Processing:
  1. Destroy session
  2. Clear session variables
  3. Regenerate session ID
  4. Redirect to login page
Output: Redirect to login page
```

**check-session.php:**
```php
Function: validateSession($requiredRole)
Input: Required user role ('patient' or 'admin')
Processing:
  1. Check if session exists
  2. Validate user_id is set
  3. Validate user_role matches required role
  4. Check session expiry (30 minutes)
Output: Boolean (redirect if invalid)
Usage: Include at top of protected pages
```

#### 3.1.4 Security Considerations
- Password hashing: bcrypt (via password_hash with PASSWORD_DEFAULT)
- Session security: HttpOnly, Secure flags
- CSRF protection: Token validation (recommended for enhancement)
- SQL injection prevention: Prepared statements
- XSS prevention: Input sanitization, output encoding

### 3.2 Appointment Management Module

#### 3.2.1 Component Overview
**Purpose:** Handle appointment lifecycle and operations  
**Location:** `/app/patient/` and `/app/admin/`  
**Dependencies:** Database module, Notification module, Session module

#### 3.2.2 Component Structure

```
patient/
├── submit-booking.php         # Create new appointment
├── patient-appointments.php   # View patient appointments
└── patient-dashboard.php      # Patient main dashboard

admin/
├── admin-appointments.php     # Manage all appointments
└── admin-dashboard.php        # Admin main dashboard
```

#### 3.2.3 Key Functions

**submit-booking.php:**
```php
Function: createAppointment()
Input: POST data (patientName, phone, department, doctor data, date, time, reason)
Processing:
  1. Validate user session (must be logged in)
  2. Sanitize all inputs
  3. Validate appointment date (future only)
  4. Generate unique appointment ID (format: APT-YYYYMMDD-XXXXX)
  5. Extract patient_id from session
  6. Insert appointment into database
  7. Create admin notification
  8. Create patient notification
  9. Commit transaction
Output: JSON {success: boolean, message: string, appointmentId: string}
Error Handling:
  - Date validation errors
  - Database insertion failures
  - Notification creation failures (non-critical)
Business Rules:
  - Date must be today or future
  - Initial status always 'pending'
  - Patient ID linked from session
```

**admin-appointments.php:**
```php
Function: updateAppointmentStatus()
Input: POST data (appointmentId, newStatus, notes)
Processing:
  1. Verify admin session
  2. Validate appointment exists
  3. Update status in database
  4. Update updated_at timestamp
  5. Add admin notes if provided
  6. Create patient notification with status change
Output: JSON {success: boolean, message: string}
Supported Status Transitions:
  - pending → approved, canceled
  - approved → rescheduled, canceled, completed
  - rescheduled → approved, canceled
```

**patient-dashboard.php:**
```php
Function: loadPatientAppointments()
Input: Session user_id
Processing:
  1. Verify patient session
  2. Query appointments WHERE patient_id = ?
  3. Calculate statistics
  4. Format data for display
  5. Determine upcoming appointments
Output: HTML page with appointment data embedded
Statistics Calculated:
  - Total appointments
  - Upcoming (approved with future dates)
  - Approved count
  - Pending count
  - Rescheduled count
  - Canceled count
```

#### 3.2.4 Data Flow

**Appointment Creation Flow:**
```
Client Browser
    │
    ├─► submit-booking.php
    │       │
    │       ├─► Validate Session
    │       ├─► Validate Input
    │       ├─► Generate Appointment ID
    │       │
    │       ├─► INSERT INTO appointments
    │       │       ├─► appointment_id (unique)
    │       │       ├─► patient_id (from session)
    │       │       ├─► status = 'pending'
    │       │       └─► timestamps
    │       │
    │       ├─► INSERT INTO notifications (admin)
    │       │       ├─► type = 'new_appointment'
    │       │       └─► appointment_id reference
    │       │
    │       └─► INSERT INTO patient_notifications
    │               └─► type = 'appointment_submitted'
    │
    └─► Return Success Response
```

### 3.3 Notification Module

#### 3.3.1 Component Overview
**Purpose:** Manage system notifications for admins and patients  
**Location:** `/app/includes/`  
**Dependencies:** Database module

#### 3.3.2 Component Structure

```
includes/
├── get-notifications.php              # Fetch admin notifications
├── get-patient-notifications.php      # Fetch patient notifications
├── mark-notifications-read.php        # Mark admin notifs as read
└── mark-patient-notifications-read.php # Mark patient notifs as read
```

#### 3.3.3 Key Functions

**get-notifications.php (Admin):**
```php
Function: fetchAdminNotifications()
Input: None (session-based)
Processing:
  1. Verify admin session
  2. Query: SELECT * FROM notifications ORDER BY created_at DESC
  3. Optionally limit results
  4. Format as JSON array
Output: JSON array of notification objects
Notification Object Structure:
  {
    id: int,
    type: string,
    message: string,
    appointment_id: string,
    is_read: boolean,
    created_at: timestamp,
    read_at: timestamp|null
  }
```

**get-patient-notifications.php:**
```php
Function: fetchPatientNotifications()
Input: GET/POST parameter patient_id (validated against session)
Processing:
  1. Verify patient session
  2. Validate patient_id matches session user_id
  3. Query: SELECT * FROM patient_notifications 
          WHERE patient_id = ? ORDER BY created_at DESC
  4. Format as JSON array
Output: JSON array of notification objects
Security:
  - Patient can only access their own notifications
  - Session validation required
```

**mark-notifications-read.php:**
```php
Function: markAsRead()
Input: POST data (notification_ids array)
Processing:
  1. Verify session
  2. Update notifications SET is_read = TRUE, 
                              read_at = CURRENT_TIMESTAMP
          WHERE id IN (?)
  3. Return count of updated records
Output: JSON {success: boolean, updated_count: int}
```

#### 3.3.4 Notification Types

**Admin Notification Types:**
- `new_appointment` - New appointment created by patient
- `appointment_canceled` - Patient canceled appointment

**Patient Notification Types:**
- `appointment_submitted` - Confirmation of submission
- `appointment_approved` - Admin approved appointment
- `appointment_rescheduled` - Admin rescheduled appointment
- `appointment_canceled` - Admin canceled appointment
- `appointment_completed` - Appointment marked as completed

### 3.4 User Management Module

#### 3.4.1 Component Overview
**Purpose:** Manage user profiles and settings  
**Location:** `/app/patient/`  
**Dependencies:** Database module, Session module

#### 3.4.2 Component Structure

```
patient/
├── patient-profile.php    # View and edit profile
└── patient-settings.php   # Account settings
```

#### 3.4.3 Key Functions

**patient-profile.php:**
```php
Function: loadUserProfile()
Input: Session user_id
Processing:
  1. Verify patient session
  2. Query user data: SELECT * FROM users WHERE id = ?
  3. Count appointments: SELECT COUNT(*) FROM appointments 
                        WHERE patient_id = ?
  4. Display profile information
Output: HTML page with profile data

Function: updateUserProfile()
Input: POST data (full-name, phone)
Processing:
  1. Verify patient session
  2. Validate input
  3. Check phone uniqueness (exclude current user)
  4. Update: UPDATE users SET full_name = ?, phone = ? 
            WHERE id = ?
Output: JSON {success: boolean, message: string}
```

### 3.5 Database Connectivity Module

#### 3.5.1 Component Overview
**Purpose:** Centralized database connection management  
**Location:** `/config/db-config.php`  
**Dependencies:** MySQLi extension

#### 3.5.2 Key Functions

```php
Function: getDBConnection()
Input: None (uses defined constants)
Processing:
  1. Create mysqli object with credentials
  2. Check connection error
  3. Set character set to utf8mb4
  4. Return connection object
Output: mysqli connection object
Error Handling:
  - Log error to error_log
  - Throw Exception with generic message
  - Never expose connection details to user

Function: closeDBConnection($conn)
Input: mysqli connection object
Processing:
  1. Check if connection exists
  2. Close connection
Output: None
```

#### 3.5.3 Database Configuration

```php
Constants Defined:
  DB_HOST = 'localhost'
  DB_USER = 'root'
  DB_PASS = ''
  DB_NAME = 'medicare'

Character Set: utf8mb4 (full Unicode support)
Connection Method: MySQLi (procedural or OOP)
Transaction Support: Yes (for critical operations)
```

---

## 4. Data Design

### 4.1 Database Schema Design

#### 4.1.1 Entity-Relationship Model

**Detailed ER Diagram:**
```
┌─────────────────────────────────────────────────────┐
│                  USERS (Patients)                   │
├─────────────────────────────────────────────────────┤
│ PK  id                    INT(11) AUTO_INCREMENT    │
│ UK  email                 VARCHAR(100)              │
│ UK  phone                 VARCHAR(20)               │
│     full_name             VARCHAR(100)              │
│     password              VARCHAR(255)              │
│     created_at            TIMESTAMP                 │
└──────────────────┬──────────────────────────────────┘
                   │
                   │ 1
                   │
                   │ relates to
                   │
                   │ N
                   ▼
┌─────────────────────────────────────────────────────┐
│                  APPOINTMENTS                       │
├─────────────────────────────────────────────────────┤
│ PK  id                    INT(11) AUTO_INCREMENT    │
│ UK  appointment_id        VARCHAR(50)               │
│ FK  patient_id            INT(11)  → users.id       │
│     patient_name          VARCHAR(100)              │
│     phone                 VARCHAR(20)               │
│     department            VARCHAR(100)              │
│     doctor_id             VARCHAR(50)               │
│     doctor_name           VARCHAR(100)              │
│     doctor_specialty      VARCHAR(100)              │
│     doctor_photo          VARCHAR(255)              │
│     appointment_date      DATE                      │
│     appointment_time      TIME                      │
│     reason                TEXT                      │
│     notes                 TEXT                      │
│     status                ENUM (5 values)           │
│     created_at            TIMESTAMP                 │
│     updated_at            TIMESTAMP                 │
└──────────────────┬──────────────────────────────────┘
                   │
         ┌─────────┴─────────┐
         │                   │
         │ N                 │ N
         │                   │
         ▼                   ▼
┌─────────────────┐  ┌───────────────────────────────┐
│ NOTIFICATIONS   │  │  PATIENT_NOTIFICATIONS        │
├─────────────────┤  ├───────────────────────────────┤
│ PK id           │  │ PK  id                        │
│    type         │  │ FK  patient_id → users.id     │
│    message      │  │     appointment_id            │
│    appt_id      │  │     patient_name              │
│    is_read      │  │     notification_type         │
│    created_at   │  │     message                   │
│    read_at      │  │     is_read                   │
└─────────────────┘  │     created_at                │
                     │     read_at                   │
┌─────────────────┐  └───────────────────────────────┘
│     ADMIN       │
├─────────────────┤
│ PK  id          │
│ UK  email       │
│     full_name   │
│     password    │
│     created_at  │
└─────────────────┘
```

#### 4.1.2 Table Specifications

**USERS Table (Patient Accounts):**
```sql
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Design Rationale:**
- INT(11) for id: Supports up to 2 billion users
- VARCHAR(255) for password: Accommodates bcrypt/argon2 hashes
- UTF8MB4: Full Unicode support including emojis
- Indexes on email: Optimize login queries
- Unique constraints: Enforce business rules

**ADMIN Table:**
```sql
CREATE TABLE IF NOT EXISTS `admin` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

**Design Rationale:**
- Separate admin table: Role separation at database level
- Same authentication pattern as users: Consistent security
- Manual admin creation: No self-registration for admins

**APPOINTMENTS Table:**
```sql
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `appointment_id` VARCHAR(50) NOT NULL UNIQUE,
  `patient_id` INT(11) DEFAULT NULL,
  `patient_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `department` VARCHAR(100) NOT NULL,
  `doctor_id` VARCHAR(50) NOT NULL,
  `doctor_name` VARCHAR(100) NOT NULL,
  `doctor_specialty` VARCHAR(100) NOT NULL,
  `doctor_photo` VARCHAR(255) DEFAULT NULL,
  `appointment_date` DATE NOT NULL,
  `appointment_time` TIME NOT NULL,
  `reason` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rescheduled', 'canceled', 'completed') 
           NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP 
               ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_appointment_id` (`appointment_id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_status` (`status`),
  KEY `idx_appointment_date` (`appointment_date`),
  KEY `idx_doctor_id` (`doctor_id`),
  CONSTRAINT `fk_appointments_patient` 
    FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) 
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Design Rationale:**
- Denormalized patient/doctor data: Historical record preservation
- patient_id nullable with SET NULL: Preserve appointments if user deleted
- ENUM for status: Enforce valid states at database level
- Multiple indexes: Optimize common queries (by date, by status, by patient)
- appointment_id separate: User-friendly reference
- updated_at automatic: Audit trail for changes

**NOTIFICATIONS Table:**
```sql
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(50) NOT NULL,
    `message` TEXT NOT NULL,
    `appointment_id` VARCHAR(50),
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_appointment` (`appointment_id`),
    INDEX `idx_read_status` (`is_read`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Design Rationale:**
- Boolean is_read: Simple status tracking
- read_at timestamp: Audit when notification was read
- Indexes on is_read and created_at: Optimize unread count queries
- No foreign key to appointments: Soft reference, notifications persist

**PATIENT_NOTIFICATIONS Table:**
```sql
CREATE TABLE IF NOT EXISTS `patient_notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT(11) NOT NULL,
    `appointment_id` VARCHAR(50) NOT NULL,
    `patient_name` VARCHAR(255) NOT NULL,
    `notification_type` VARCHAR(50) NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` BOOLEAN DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_patient_id` (`patient_id`),
    INDEX `idx_appointment` (`appointment_id`),
    INDEX `idx_read_status` (`is_read`),
    INDEX `idx_created_at` (`created_at`),
    CONSTRAINT `fk_patient_notifications_user` 
      FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) 
      ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Design Rationale:**
- Foreign key with CASCADE: Delete notifications when patient deleted
- Patient-specific: Isolate patient notifications from admin
- Denormalized patient_name: Quick display without join
- Same indexing strategy: Consistent performance optimization

### 4.2 Data Access Patterns

#### 4.2.1 Query Design Patterns

**Pattern 1: Prepared Statements (Parameterized Queries)**
```php
// Secure query pattern - ALWAYS USE THIS
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// Type indicators:
// "s" - string
// "i" - integer
// "d" - double
// "b" - blob
```

**Pattern 2: Transaction Management**
```php
// For multi-step operations
$conn->begin_transaction();
try {
    // Step 1: Insert appointment
    $stmt1 = $conn->prepare("INSERT INTO appointments ...");
    $stmt1->execute();
    
    // Step 2: Create notification
    $stmt2 = $conn->prepare("INSERT INTO notifications ...");
    $stmt2->execute();
    
    // Commit if all successful
    $conn->commit();
} catch (Exception $e) {
    // Rollback on any error
    $conn->rollback();
    throw $e;
}
```

**Pattern 3: Aggregate Queries for Statistics**
```php
// Efficient statistics calculation
$query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rescheduled' THEN 1 ELSE 0 END) as rescheduled,
            SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled
          FROM appointments
          WHERE patient_id = ?";
```

#### 4.2.2 Common Query Examples

**User Authentication:**
```sql
-- Login query
SELECT id, full_name, email, password 
FROM users 
WHERE email = ? 
LIMIT 1;

-- Verify password in PHP after fetch
password_verify($inputPassword, $hashedPassword)
```

**Appointment Retrieval:**
```sql
-- Patient's appointments
SELECT *, appointment_id as id, 
       appointment_date as date, 
       appointment_time as time 
FROM appointments 
WHERE patient_id = ? 
ORDER BY created_at DESC;

-- All appointments (admin view)
SELECT *, appointment_id as id, 
       appointment_date as date, 
       appointment_time as time 
FROM appointments 
ORDER BY created_at DESC;
```

**Notification Queries:**
```sql
-- Unread admin notifications count
SELECT COUNT(*) as unread_count 
FROM notifications 
WHERE is_read = FALSE;

-- Patient notifications with limit
SELECT * FROM patient_notifications 
WHERE patient_id = ? 
ORDER BY created_at DESC 
LIMIT 20;
```

### 4.3 Data Integrity Rules

**Referential Integrity:**
1. `appointments.patient_id` → `users.id`
   - ON DELETE SET NULL (preserve appointment history)
   - ON UPDATE CASCADE (propagate ID changes)

2. `patient_notifications.patient_id` → `users.id`
   - ON DELETE CASCADE (remove notifications with user)
   - ON UPDATE CASCADE (propagate ID changes)

**Domain Constraints:**
1. Email format validation (application level)
2. Phone number format validation (application level)
3. Date validation: appointment_date >= CURRENT_DATE (application level)
4. Status values limited by ENUM constraint
5. Password minimum length: 8 characters (application level)

**Entity Integrity:**
1. All primary keys are AUTO_INCREMENT and NOT NULL
2. Unique constraints on email addresses
3. Unique constraints on appointment_id

---

## 5. Interface Design

### 5.1 User Interface Design Patterns

#### 5.1.1 Design System
**Framework:** Tailwind CSS (utility-first CSS framework)  
**Icons:** Feather Icons  
**Animations:** AOS (Animate On Scroll)  
**Color Scheme:**
- Primary: Blue (#3B82F6, #1D4ED8)
- Success: Green (#10B981)
- Warning: Yellow (#F59E0B)
- Error: Red (#EF4444)
- Neutral: Gray scale (#F9FAFB to #111827)

#### 5.1.2 Responsive Design Strategy

**Breakpoints:**
- Mobile: < 640px
- Tablet: 640px - 1024px
- Desktop: > 1024px

**Layout Approach:**
- Mobile-first design
- Collapsible sidebar navigation
- Responsive data tables
- Stack elements vertically on mobile
- Hamburger menu for mobile navigation

#### 5.1.3 Common UI Components

**Navigation Bar:**
```html
<nav class="bg-white shadow-sm">
  <div class="max-w-7xl mx-auto px-4">
    <!-- Logo -->
    <!-- Navigation Links (Desktop) -->
    <!-- Auth Buttons -->
    <!-- Mobile Menu Toggle -->
  </div>
</nav>
```

**Sidebar Navigation (Dashboard):**
```html
<aside class="w-64 bg-white shadow-lg">
  <div class="sidebar-header">
    <!-- Logo and Title -->
  </div>
  <nav class="sidebar-menu">
    <a href="#" class="sidebar-item active">
      <i data-feather="home"></i>
      <span>Dashboard</span>
    </a>
    <!-- More items -->
  </nav>
</aside>
```

**Data Table:**
```html
<div class="overflow-x-auto">
  <table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
      <tr>
        <th>Column 1</th>
        <!-- More columns -->
      </tr>
    </thead>
    <tbody class="bg-white divide-y">
      <!-- Data rows -->
    </tbody>
  </table>
</div>
```

**Status Badge:**
```html
<span class="px-2 py-1 text-xs font-semibold rounded-full 
             bg-yellow-100 text-yellow-800">
  Pending
</span>
<!-- Colors: yellow=pending, green=approved, blue=rescheduled, 
              red=canceled, gray=completed -->
```

**Modal Dialog:**
```html
<div id="modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 
                       overflow-y-auto h-full w-full hidden">
  <div class="relative top-20 mx-auto p-5 border w-96 
              shadow-lg rounded-md bg-white">
    <div class="modal-header">
      <!-- Title and close button -->
    </div>
    <div class="modal-body">
      <!-- Content -->
    </div>
    <div class="modal-footer">
      <!-- Action buttons -->
    </div>
  </div>
</div>
```

**Form Input:**
```html
<div class="mb-4">
  <label for="email" class="block text-sm font-medium text-gray-700">
    Email Address
  </label>
  <input type="email" id="email" name="email" required
         class="mt-1 block w-full px-3 py-2 border border-gray-300 
                rounded-md shadow-sm focus:outline-none 
                focus:ring-blue-500 focus:border-blue-500">
</div>
```

### 5.2 Page Layouts

#### 5.2.1 Public Pages Layout
```
┌────────────────────────────────────────────────┐
│              Navigation Bar                    │
│  [Logo]  Home Services Doctors About   [Login]│
└────────────────────────────────────────────────┘
│                                                │
│              Hero Section                      │
│        Welcome to MediCare Clinic             │
│           [Book Appointment]                   │
│                                                │
├────────────────────────────────────────────────┤
│                                                │
│            Services Section                    │
│    [Card] [Card] [Card] [Card]                │
│                                                │
├────────────────────────────────────────────────┤
│                                                │
│            Featured Doctors                    │
│    [Doctor] [Doctor] [Doctor]                 │
│                                                │
├────────────────────────────────────────────────┤
│              Footer                            │
│    Links | Contact | Social Media             │
└────────────────────────────────────────────────┘
```

#### 5.2.2 Patient Dashboard Layout
```
┌──────────────────────────────────────────────────┐
│   Top Bar: Logo | Notifications | Profile        │
└──────────────────────────────────────────────────┘
┌────────┬─────────────────────────────────────────┐
│        │  Welcome, [Patient Name]                │
│ Side   │  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐  │
│ bar    │  │Total │ │Upcom.│ │Appro.│ │Pend. │  │
│        │  │  12  │ │  3   │ │  8   │ │  2   │  │
│ ─────  │  └──────┘ └──────┘ └──────┘ └──────┘  │
│ Dashbd │                                         │
│ Appts  │  Recent Appointments                    │
│ Profile│  ┌───────────────────────────────────┐  │
│ Settgs │  │ ID | Doctor | Date | Status       │  │
│        │  ├───────────────────────────────────┤  │
│        │  │ APT-001 | Dr. Smith | ... │ ●    │  │
│        │  │ APT-002 | Dr. Jones | ... │ ●    │  │
│        │  └───────────────────────────────────┘  │
│        │  [Book New Appointment]                 │
└────────┴─────────────────────────────────────────┘
```

#### 5.2.3 Admin Dashboard Layout
```
┌──────────────────────────────────────────────────┐
│   Admin Panel | Notifications (5) | Logout       │
└──────────────────────────────────────────────────┘
┌────────┬─────────────────────────────────────────┐
│        │  Appointment Management                 │
│ Side   │  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐  │
│ bar    │  │Total │ │Pend. │ │Appr. │ │Cance.│  │
│        │  │ 150  │ │  25  │ │  80  │ │  15  │  │
│ ─────  │  └──────┘ └──────┘ └──────┘ └──────┘  │
│ Dashbd │                                         │
│ Appts  │  Filter: [All ▼] [Search...]           │
│ Settgs │  ┌───────────────────────────────────┐  │
│        │  │ ID | Patient | Date | Actions     │  │
│        │  ├───────────────────────────────────┤  │
│        │  │ APT-001 | John | ... │[✓][×][⟳] │  │
│        │  │ APT-002 | Jane | ... │[✓][×][⟳] │  │
│        │  └───────────────────────────────────┘  │
└────────┴─────────────────────────────────────────┘
```

### 5.3 API Endpoints (AJAX)

#### 5.3.1 Endpoint Specifications

**POST /app/auth/register.php**
```
Purpose: Register new patient account
Request Body:
  {
    "full-name": "John Doe",
    "email": "john@example.com",
    "phone": "1234567890",
    "password": "securepass123",
    "confirm-password": "securepass123"
  }
Response:
  Success: { "success": true, "message": "Registration successful" }
  Error: { "success": false, "message": "Error description" }
Status Codes: 200 OK, 400 Bad Request, 500 Internal Error
```

**POST /app/auth/login.php**
```
Purpose: Authenticate user
Request Body:
  {
    "email": "john@example.com",
    "password": "securepass123"
  }
Response:
  Success: { "success": true, "redirect": "/app/patient/patient-dashboard.php" }
  Error: { "success": false, "message": "Invalid credentials" }
Status Codes: 200 OK, 401 Unauthorized
```

**POST /app/patient/submit-booking.php**
```
Purpose: Create new appointment
Request Body:
  {
    "patientName": "John Doe",
    "phone": "1234567890",
    "department": "Cardiology",
    "doctorId": "DOC-001",
    "doctorName": "Dr. Smith",
    "doctorSpecialty": "Cardiologist",
    "doctorPhoto": "path/to/photo.jpg",
    "date": "2026-02-15",
    "time": "10:00:00",
    "reason": "Regular checkup"
  }
Response:
  Success: { 
    "success": true, 
    "message": "Appointment booked successfully",
    "appointmentId": "APT-20260215-12345"
  }
  Error: { "success": false, "message": "Booking failed" }
```

**GET /app/includes/get-patient-notifications.php?patient_id=1**
```
Purpose: Fetch patient notifications
Response:
  [
    {
      "id": 1,
      "patient_id": 1,
      "appointment_id": "APT-001",
      "patient_name": "John Doe",
      "notification_type": "appointment_approved",
      "message": "Your appointment has been approved",
      "is_read": false,
      "created_at": "2026-01-29 10:30:00",
      "read_at": null
    },
    ...
  ]
```

**POST /app/includes/mark-patient-notifications-read.php**
```
Purpose: Mark notifications as read
Request Body:
  {
    "notification_ids": [1, 2, 3]
  }
Response:
  { "success": true, "updated_count": 3 }
```

---

## 6. Security Design

### 6.1 Authentication and Authorization Architecture

#### 6.1.1 Authentication Flow
```
User Input (email + password)
    │
    ├─► Sanitize Input
    │
    ├─► Query Database (Prepared Statement)
    │       SELECT id, full_name, password, role
    │       FROM users/admin WHERE email = ?
    │
    ├─► Verify Password
    │       password_verify(input, hash)
    │
    ├─► Create Session (if valid)
    │       session_regenerate_id()
    │       $_SESSION['user_id'] = id
    │       $_SESSION['user_name'] = name
    │       $_SESSION['user_role'] = role
    │
    └─► Set Security Headers
            Cache-Control: no-store, no-cache
            Pragma: no-cache
```

#### 6.1.2 Authorization Checks
```php
// Included at top of protected pages
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

// Check authentication
if (!isset($_SESSION['user_id']) || 
    !isset($_SESSION['user_name']) || 
    !isset($_SESSION['user_role'])) {
    header('Location: /login.html');
    exit;
}

// Check authorization (for role-specific pages)
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: /unauthorized.html');
    exit;
}
```

### 6.2 Data Security

#### 6.2.1 Password Security
**Hashing Algorithm:** bcrypt (via PHP's password_hash)
```php
// Registration/Password Creation
$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
// PASSWORD_DEFAULT uses bcrypt with cost factor 10

// Login/Verification
$isValid = password_verify($plainPassword, $hashedPassword);
```

**Security Properties:**
- Salt automatically generated and stored with hash
- Computational cost makes brute-force attacks impractical
- Hash length: 60 characters
- Future-proof: PASSWORD_DEFAULT will upgrade to stronger algorithms

#### 6.2.2 SQL Injection Prevention
**Strategy:** Prepared Statements (Parameterized Queries)
```php
// NEVER DO THIS (vulnerable)
$query = "SELECT * FROM users WHERE email = '$email'";

// ALWAYS DO THIS (secure)
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
```

**Additional Measures:**
- Input validation before database operations
- Whitelist validation for enum-like values (status, role)
- Escape special characters in LIKE queries

#### 6.2.3 Cross-Site Scripting (XSS) Prevention
**Input Sanitization:**
```php
$fullName = htmlspecialchars($_POST['full-name'], ENT_QUOTES, 'UTF-8');
// Converts: < > " ' & to HTML entities
```

**Output Encoding:**
```php
// When displaying user content
echo htmlspecialchars($userContent, ENT_QUOTES, 'UTF-8');
```

**Additional Measures:**
- Content Security Policy headers (recommended)
- Validate input types (email, phone, date formats)
- Strip or escape HTML tags in user input

#### 6.2.4 Cross-Site Request Forgery (CSRF) Protection
**Current Implementation:** Session-based validation

**Recommended Enhancement:**
```php
// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Include in forms
<input type="hidden" name="csrf_token" 
       value="<?php echo $_SESSION['csrf_token']; ?>">

// Validate on submission
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF validation failed');
}
```

### 6.3 Session Security

#### 6.3.1 Session Configuration
```php
// Recommended php.ini settings
session.cookie_httponly = 1    // Prevent JavaScript access
session.cookie_secure = 1      // HTTPS only (production)
session.use_strict_mode = 1    // Prevent session fixation
session.gc_maxlifetime = 1800  // 30 minutes
session.cookie_samesite = "Strict" // CSRF protection
```

#### 6.3.2 Session Management
```php
// Session start with security
session_start();

// Regenerate ID after login (prevent fixation)
session_regenerate_id(true);

// Session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && 
    (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: /login.html');
    exit;
}
$_SESSION['last_activity'] = time();
```

### 6.4 Transport Security

#### 6.4.1 HTTPS Configuration (Production)
**Requirements:**
- SSL/TLS certificate (Let's Encrypt recommended)
- TLS 1.2 or higher
- Secure cipher suites

**Apache Configuration:**
```apache
<VirtualHost *:443>
    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem
    SSLProtocol all -SSLv2 -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite HIGH:!aNULL:!MD5
</VirtualHost>
```

#### 6.4.2 Security Headers
```php
// Content Security Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' cdn.tailwindcss.com unpkg.com; style-src 'self' 'unsafe-inline' cdn.tailwindcss.com unpkg.com;");

// X-Frame-Options (prevent clickjacking)
header("X-Frame-Options: SAMEORIGIN");

// X-Content-Type-Options
header("X-Content-Type-Options: nosniff");

// X-XSS-Protection
header("X-XSS-Protection: 1; mode=block");
```

### 6.5 Access Control Matrix

| Resource | Public | Patient | Admin |
|----------|--------|---------|-------|
| Public Pages (index, about, services) | ✓ | ✓ | ✓ |
| Login/Register Pages | ✓ | ✗ (redirect) | ✗ (redirect) |
| Patient Dashboard | ✗ | ✓ | ✗ |
| Patient Appointments | ✗ | ✓ (own only) | ✗ |
| Patient Profile | ✗ | ✓ (own only) | ✗ |
| Book Appointment | ✗ | ✓ | ✗ |
| Admin Dashboard | ✗ | ✗ | ✓ |
| Admin Appointments | ✗ | ✗ | ✓ (all) |
| Manage Appointment Status | ✗ | ✗ | ✓ |
| Admin Settings | ✗ | ✗ | ✓ |

### 6.6 Security Best Practices Implemented

1. **Principle of Least Privilege:** Users only access their own data
2. **Defense in Depth:** Multiple security layers (input validation, prepared statements, output encoding)
3. **Secure Defaults:** Sessions configured securely, passwords must be strong
4. **Fail Securely:** Errors don't expose system information
5. **Separation of Duties:** Admin and patient roles separated
6. **Audit Trail:** Timestamps on all critical operations
7. **Data Minimization:** Only necessary data collected and stored

---

## 7. Detailed Design

### 7.1 Critical Algorithms

#### 7.1.1 Appointment ID Generation Algorithm
```php
Function: generateAppointmentId()
Input: None
Output: String (format: APT-YYYYMMDD-XXXXX)

Algorithm:
  1. Get current date in YYYYMMDD format
  2. Generate random 5-digit number (10000-99999)
  3. Concatenate: "APT-" + date + "-" + random
  4. Check uniqueness in database
  5. If collision, regenerate and repeat step 4
  6. Return unique ID

Pseudocode:
  function generateAppointmentId():
      date = getCurrentDate("Ymd")
      maxAttempts = 100
      
      for i = 1 to maxAttempts:
          random = randomNumber(10000, 99999)
          appointmentId = "APT-" + date + "-" + random
          
          if isUnique(appointmentId):
              return appointmentId
      
      throw Exception("Failed to generate unique ID")
  
  function isUnique(id):
      query = "SELECT COUNT(*) FROM appointments WHERE appointment_id = ?"
      result = executeQuery(query, [id])
      return result == 0

Complexity: O(1) average case, O(n) worst case
Collision Probability: 1 in 90,000 per day
```

#### 7.1.2 Notification Creation Algorithm
```php
Function: createNotification(type, targetRole, appointmentId, message)
Input: 
  - type: string (notification type)
  - targetRole: string ('admin' or 'patient')
  - appointmentId: string
  - message: string
Output: boolean (success/failure)

Algorithm:
  1. Validate input parameters
  2. Determine target table based on role
  3. If patient notification, get patient_id from appointment
  4. Prepare INSERT statement
  5. Execute query with error handling
  6. Return success status

Pseudocode:
  function createNotification(type, targetRole, appointmentId, message):
      if targetRole == 'admin':
          table = 'notifications'
          query = "INSERT INTO notifications 
                   (type, message, appointment_id, is_read, created_at) 
                   VALUES (?, ?, ?, FALSE, NOW())"
          params = [type, message, appointmentId]
      
      else if targetRole == 'patient':
          table = 'patient_notifications'
          
          // Get patient info
          patientInfo = getPatientByAppointment(appointmentId)
          
          query = "INSERT INTO patient_notifications 
                   (patient_id, appointment_id, patient_name, 
                    notification_type, message, is_read, created_at) 
                   VALUES (?, ?, ?, ?, ?, FALSE, NOW())"
          params = [patientInfo.id, appointmentId, patientInfo.name, 
                    type, message]
      
      try:
          result = executeQuery(query, params)
          return result.success
      catch Exception e:
          logError(e)
          return false
```

#### 7.1.3 Appointment Statistics Calculation
```php
Function: calculateAppointmentStats(patientId = null)
Input: patientId (optional, null for all appointments)
Output: Object {total, pending, approved, rescheduled, canceled, completed, upcoming}

Algorithm:
  1. Build base query with conditional WHERE clause
  2. Use CASE statements for status counts
  3. Calculate upcoming appointments (approved + future date)
  4. Execute single query for efficiency
  5. Return statistics object

Pseudocode:
  function calculateAppointmentStats(patientId):
      baseQuery = "SELECT 
                     COUNT(*) as total,
                     SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                     SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                     SUM(CASE WHEN status = 'rescheduled' THEN 1 ELSE 0 END) as rescheduled,
                     SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled,
                     SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                     SUM(CASE WHEN status = 'approved' 
                              AND appointment_date >= CURDATE() 
                              THEN 1 ELSE 0 END) as upcoming
                   FROM appointments"
      
      if patientId is not null:
          query = baseQuery + " WHERE patient_id = ?"
          params = [patientId]
      else:
          query = baseQuery
          params = []
      
      result = executeQuery(query, params)
      return result.fetch()

Complexity: O(n) where n = number of appointments
Optimization: Single query with aggregations
```

### 7.2 Error Handling Strategy

#### 7.2.1 Error Handling Hierarchy
```
┌─────────────────────────────────────┐
│     User-Friendly Error Message     │
│  (Display to user)                  │
└──────────────┬──────────────────────┘
               │
┌──────────────┴──────────────────────┐
│     Specific Error Logging          │
│  (Log to file with details)         │
└──────────────┬──────────────────────┘
               │
┌──────────────┴──────────────────────┐
│     Technical Exception/Error       │
│  (PHP Exception or Database Error)  │
└─────────────────────────────────────┘
```

#### 7.2.2 Error Handling Pattern
```php
// Standard error handling pattern
try {
    $conn = getDBConnection();
    
    // Perform database operation
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception("Database operation failed");
    }
    
    // Process results
    $result = $stmt->get_result();
    
    // Return success
    $response = ['success' => true, 'data' => $data];
    
} catch (Exception $e) {
    // Log detailed error (server-side)
    error_log("Error in " . __FILE__ . ": " . $e->getMessage());
    
    // Return user-friendly error (client-side)
    $response = [
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ];
} finally {
    // Cleanup
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) closeDBConnection($conn);
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
```

#### 7.2.3 Error Categories and Responses

| Error Category | Example | User Message | Action |
|---------------|---------|--------------|--------|
| Validation Error | Empty field | "All fields are required" | Show field error |
| Authentication Error | Wrong password | "Invalid email or password" | Clear form |
| Authorization Error | Wrong role access | "Access denied" | Redirect |
| Database Error | Connection failed | "System error. Please try again later" | Log and alert |
| Business Logic Error | Past date selected | "Appointment date must be in the future" | Show specific error |
| Network Error | Timeout | "Request timeout. Please check connection" | Retry option |

### 7.3 State Management

#### 7.3.1 Session State
```php
// Session variables stored
$_SESSION = [
    'user_id' => int,          // Database user ID
    'user_name' => string,     // User's full name
    'user_role' => string,     // 'patient' or 'admin'
    'last_activity' => int,    // Unix timestamp
    'csrf_token' => string     // CSRF protection (recommended)
];
```

#### 7.3.2 Application State Transitions
```
┌─────────────┐
│ Anonymous   │
└──────┬──────┘
       │ Register/Login
       ▼
┌─────────────┐
│Authenticated│
└──────┬──────┘
       │
       ├─────► Patient Role ─────► Patient Dashboard
       │                            ├─► View Appointments
       │                            ├─► Book Appointment
       │                            └─► Manage Profile
       │
       └─────► Admin Role ───────► Admin Dashboard
                                    ├─► View All Appointments
                                    ├─► Manage Status
                                    └─► View Statistics
```

### 7.4 Performance Optimization Strategies

#### 7.4.1 Database Optimizations
1. **Indexing Strategy:**
   - Primary keys: AUTO_INCREMENT
   - Foreign keys: Indexed automatically
   - Frequently queried columns: email, appointment_id, status, date
   - Composite indexes for common query patterns

2. **Query Optimization:**
   - Use LIMIT for pagination
   - Aggregate statistics in single query
   - Avoid SELECT * (specify columns)
   - Use EXPLAIN to analyze queries

3. **Connection Management:**
   - Single connection per request
   - Close connections promptly
   - Connection pooling (server configuration)

#### 7.4.2 Application Optimizations
1. **Caching:**
   - Static assets: Browser caching (max-age headers)
   - Dynamic pages: No caching (sensitive data)
   - Session data: In-memory (default PHP session)

2. **Code Optimization:**
   - Prepared statement reuse
   - Minimal database round-trips
   - Efficient loops and conditions

3. **Asset Optimization:**
   - CDN for external libraries
   - Minified CSS/JS (production)
   - Image optimization
   - Lazy loading images

---

## 8. Deployment Design

### 8.1 Development Environment Setup

#### 8.1.1 XAMPP Installation and Configuration
```
1. Download XAMPP 7.4+ from apachefriends.org
2. Install to C:\xampp (Windows) or /opt/lampp (Linux)
3. Start Apache and MySQL services
4. Configure PHP:
   - Edit php.ini
   - Set error_reporting = E_ALL (development)
   - Enable mysqli extension
   - Configure session settings
5. Configure MySQL:
   - Set root password (recommended)
   - Create 'medicare' database
   - Import database schema
```

#### 8.1.2 Application Deployment Steps
```
1. Clone/Copy application files to C:\xampp\htdocs\hospital
2. Directory permissions:
   - Read/Write for upload directories (if any)
   - Read-only for code files
3. Database setup:
   - Access phpMyAdmin (http://localhost/phpmyadmin)
   - Create database: medicare
   - Import: config/database-schema.sql
   - Optionally import: config/database-migration.sql
   - Add foreign keys: config/add-foreign-keys.sql
4. Configure database connection:
   - Edit config/db-config.php
   - Set DB_HOST, DB_USER, DB_PASS, DB_NAME
5. Create admin account:
   - Manual INSERT into admin table
   - Hash password: password_hash('your_password', PASSWORD_DEFAULT)
6. Test access:
   - http://localhost/hospital/public/index.html
```

### 8.2 Production Environment Setup

#### 8.2.1 Server Requirements
**Minimum Hardware:**
- CPU: 2 cores, 2.0 GHz
- RAM: 4 GB
- Storage: 20 GB SSD
- Network: 100 Mbps

**Recommended Hardware:**
- CPU: 4 cores, 2.5 GHz+
- RAM: 8 GB
- Storage: 50 GB SSD
- Network: 1 Gbps

**Software Stack:**
- OS: Ubuntu 20.04 LTS or Windows Server 2019
- Apache: 2.4.x
- PHP: 7.4.x or 8.0.x
- MySQL: 8.0.x or MariaDB 10.5.x

#### 8.2.2 Production Configuration

**Apache Configuration (httpd.conf or sites-available):**
```apache
<VirtualHost *:80>
    ServerName medicare.example.com
    DocumentRoot /var/www/hospital/public
    
    # Redirect to HTTPS
    Redirect permanent / https://medicare.example.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName medicare.example.com
    DocumentRoot /var/www/hospital/public
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/medicare.crt
    SSLCertificateKeyFile /etc/ssl/private/medicare.key
    
    # Security Headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    
    # Directory Configuration
    <Directory /var/www/hospital/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/medicare_error.log
    CustomLog ${APACHE_LOG_DIR}/medicare_access.log combined
</VirtualHost>
```

**PHP Configuration (php.ini for production):**
```ini
; Error Handling
display_errors = Off
display_startup_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
log_errors = On
error_log = /var/log/php/error.log

; Security
expose_php = Off
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = "Strict"
session.use_strict_mode = 1

; Performance
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 10000

; Upload Limits
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 30
```

**MySQL Configuration (my.cnf):**
```ini
[mysqld]
# Performance
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
max_connections = 150

# Security
bind-address = 127.0.0.1
local-infile = 0

# Character Set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Logging
log_error = /var/log/mysql/error.log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

### 8.3 Deployment Checklist

**Pre-Deployment:**
- [ ] Code review completed
- [ ] All tests passed
- [ ] Security audit performed
- [ ] Database backup created
- [ ] Configuration files prepared
- [ ] SSL certificate obtained

**Deployment:**
- [ ] Application files uploaded
- [ ] File permissions set correctly
- [ ] Database schema deployed
- [ ] Database connection configured
- [ ] Admin account created
- [ ] Environment variables set
- [ ] Logging configured

**Post-Deployment:**
- [ ] Application accessible via HTTPS
- [ ] Login functionality tested
- [ ] Appointment booking tested
- [ ] Admin functions tested
- [ ] Notifications working
- [ ] Error logging verified
- [ ] Performance monitoring enabled
- [ ] Backup system activated

### 8.4 Monitoring and Maintenance

#### 8.4.1 Monitoring Strategy
**Server Monitoring:**
- CPU and memory usage
- Disk space utilization
- Network traffic
- Service uptime (Apache, MySQL)

**Application Monitoring:**
- Error logs (PHP error log)
- Access logs (Apache access log)
- Slow query logs (MySQL)
- Application-specific logs

**Metrics to Track:**
- Response times
- Database query performance
- User registration rate
- Appointment booking rate
- Error rates

#### 8.4.2 Backup Strategy
**Database Backups:**
```bash
# Daily full backup
mysqldump -u root -p medicare > backup_$(date +%Y%m%d).sql

# Automated via cron (daily at 2 AM)
0 2 * * * /usr/local/bin/backup_database.sh
```

**File Backups:**
- Application code: Version control (Git)
- Uploaded files: Daily sync to backup server
- Configuration files: Included in backups

**Retention Policy:**
- Daily backups: 30 days
- Weekly backups: 3 months
- Monthly backups: 1 year

#### 8.4.3 Update and Patch Management
**Security Updates:**
- PHP security patches: Apply within 48 hours
- MySQL security patches: Apply during maintenance window
- Apache security patches: Apply within 48 hours
- OS security patches: Monthly schedule

**Application Updates:**
- Bug fixes: Deploy after testing
- Feature updates: Quarterly release cycle
- Database migrations: Tested on staging first

---

## 9. Appendices

### 9.1 Appendix A: File Structure Reference

```
hospital/
│
├── app/                          # Application logic
│   ├── admin/                    # Admin-specific pages
│   │   ├── admin-appointments.php
│   │   ├── admin-dashboard.php
│   │   ├── admin-login.php
│   │   ├── admin-logout.php
│   │   └── admin-settings.php
│   │
│   ├── auth/                     # Authentication
│   │   ├── change-password.php
│   │   ├── check-session.php
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── register.php
│   │
│   ├── includes/                 # Shared utilities
│   │   ├── feedback.php
│   │   ├── get-notifications.php
│   │   ├── get-patient-notifications.php
│   │   ├── mark-notifications-read.php
│   │   └── mark-patient-notifications-read.php
│   │
│   └── patient/                  # Patient-specific pages
│       ├── patient-appointments.php
│       ├── patient-dashboard.php
│       ├── patient-profile.php
│       ├── patient-settings.php
│       └── submit-booking.php
│
├── config/                       # Configuration files
│   ├── add-foreign-keys.sql
│   ├── database-migration.sql
│   ├── database-schema.sql
│   └── db-config.php
│
├── public/                       # Public-facing pages
│   ├── about.html
│   ├── admin-login.html
│   ├── index.html
│   ├── login.html
│   ├── patient-book.html
│   ├── privacy.html
│   ├── register.html
│   ├── services.html
│   ├── terms.html
│   │
│   └── assets/                   # Static assets
│       ├── css/
│       │   ├── dark-mode.css
│       │   └── responsive-sidebar.css
│       ├── images/
│       └── js/
│           ├── dark-mode.js
│           ├── feedback-form.js
│           ├── init.js
│           ├── mobile-menu.js
│           └── sidebar-toggle.js
│
└── docs/                         # Documentation (this section)
    ├── SRS_MediCare_Clinic_System.md
    └── SDD_MediCare_Clinic_System.md
```

### 9.2 Appendix B: Database Schema Reference

**Complete Table Relationships:**
```
users (1) ─────────── (N) appointments
  │                         │
  │                         │
  │                   (1) ──┴── (N) patient_notifications
  │
  └────────── (1) ──────── (N) patient_notifications

admin (separate, no direct relationships)

notifications (separate, soft reference to appointments)
```

**Index Summary:**
```
users:
  - PRIMARY KEY: id
  - UNIQUE KEY: email
  - UNIQUE KEY: phone
  - INDEX: idx_email

admin:
  - PRIMARY KEY: id
  - UNIQUE KEY: email
  - INDEX: idx_email

appointments:
  - PRIMARY KEY: id
  - UNIQUE KEY: appointment_id
  - INDEX: idx_appointment_id
  - INDEX: idx_patient_id (FK)
  - INDEX: idx_status
  - INDEX: idx_appointment_date
  - INDEX: idx_doctor_id

notifications:
  - PRIMARY KEY: id
  - INDEX: idx_appointment
  - INDEX: idx_read_status
  - INDEX: idx_created_at

patient_notifications:
  - PRIMARY KEY: id
  - INDEX: idx_patient_id (FK)
  - INDEX: idx_appointment
  - INDEX: idx_read_status
  - INDEX: idx_created_at
```

### 9.3 Appendix C: Coding Standards

**PHP Coding Standards:**
```php
// File header
<?php
/**
 * File description
 * Purpose: ...
 */

// Naming Conventions
$variableName;          // camelCase for variables
function functionName() // camelCase for functions
class ClassName         // PascalCase for classes
CONSTANT_NAME           // UPPERCASE for constants

// Indentation: 4 spaces
// Braces: K&R style
if (condition) {
    // code
} else {
    // code
}

// Comments
// Single-line comment
/*
 * Multi-line comment
 * for complex logic
 */

// Database queries: Always use prepared statements
$stmt = $conn->prepare("SELECT * FROM table WHERE id = ?");
$stmt->bind_param("i", $id);

// Error handling: Always use try-catch for critical operations
try {
    // risky operation
} catch (Exception $e) {
    error_log($e->getMessage());
    // user-friendly error response
}
```

**JavaScript Coding Standards:**
```javascript
// Naming: camelCase
let variableName;
function functionName() {}

// Use strict mode
'use strict';

// Constants: UPPERCASE
const API_ENDPOINT = '/api/endpoint';

// Event listeners: Use addEventListener
element.addEventListener('click', handleClick);

// AJAX: Fetch API or XMLHttpRequest
fetch(url)
    .then(response => response.json())
    .then(data => handleData(data))
    .catch(error => handleError(error));
```

**SQL Coding Standards:**
```sql
-- Keywords: UPPERCASE
-- Identifiers: lowercase with underscores

SELECT column_name
FROM table_name
WHERE condition
ORDER BY column_name;

-- Always use table aliases for joins
SELECT u.full_name, a.appointment_date
FROM users u
INNER JOIN appointments a ON u.id = a.patient_id;

-- Comments
-- Single-line comment
/* Multi-line
   comment */
```

### 9.4 Appendix D: Testing Strategy

**Unit Testing (Recommended):**
- Test individual functions (validation, ID generation, etc.)
- Mock database connections
- PHPUnit framework recommended

**Integration Testing:**
- Test complete workflows (registration, login, booking)
- Test database interactions
- Test notification creation

**System Testing:**
- End-to-end user scenarios
- Browser compatibility testing
- Performance testing

**Security Testing:**
- SQL injection attempts
- XSS attempts
- CSRF attempts
- Session hijacking attempts
- Authentication bypass attempts

**Test Cases (Sample):**
```
TC-001: User Registration
  Input: Valid user data
  Expected: User created, success message, redirect to login
  Actual: [Pass/Fail]

TC-002: User Registration - Duplicate Email
  Input: Already registered email
  Expected: Error message "Email already registered"
  Actual: [Pass/Fail]

TC-003: Appointment Booking
  Input: Valid appointment data
  Expected: Appointment created, ID generated, notifications sent
  Actual: [Pass/Fail]

TC-004: Admin Approval
  Input: Pending appointment, approve action
  Expected: Status changed, patient notified
  Actual: [Pass/Fail]
```

### 9.5 Appendix E: Glossary of Design Terms

| Term | Definition |
|------|------------|
| Prepared Statement | SQL query with placeholders for parameters, preventing SQL injection |
| Session | Server-side storage of user state across HTTP requests |
| AJAX | Asynchronous JavaScript technique for updating page without reload |
| Hashing | One-way cryptographic function to securely store passwords |
| Salt | Random data added to password before hashing |
| CSRF Token | Random value to prevent Cross-Site Request Forgery attacks |
| ORM | Object-Relational Mapping (not used in this project) |
| MVC | Model-View-Controller architecture pattern (loosely followed) |
| API Endpoint | URL that accepts requests and returns data |
| Foreign Key | Database constraint linking two tables |
| Index | Database structure to speed up queries |
| Transaction | Group of database operations that succeed or fail together |
| Cache | Temporary storage for faster access |
| CDN | Content Delivery Network for serving static assets |
| SSL/TLS | Encryption protocols for secure communication |

### 9.6 Appendix F: Maintenance Procedures

**Daily Tasks:**
- Monitor error logs
- Check system availability
- Review failed transactions

**Weekly Tasks:**
- Verify backups successful
- Review slow query log
- Check disk space utilization
- Security updates check

**Monthly Tasks:**
- Full security audit
- Performance optimization review
- Database optimization (OPTIMIZE TABLE)
- Update documentation if needed

**Quarterly Tasks:**
- Disaster recovery drill
- Review user feedback
- Plan feature updates
- Capacity planning review

### 9.7 Appendix G: Change Management

**Version Control:**
- Git repository with feature branches
- Main branch for production
- Development branch for integration
- Feature branches for new development

**Release Process:**
1. Development and testing on feature branch
2. Code review and merge to development
3. Integration testing on development
4. Merge to staging for final testing
5. Deploy to production after approval
6. Tag release version
7. Document changes

**Rollback Procedure:**
1. Identify issue
2. Restore previous code version from Git
3. Restore database from backup if needed
4. Verify system functionality
5. Notify users of resolution

---

## Document Approval

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Lead Architect | _____________ | _____________ | __________ |
| Senior Developer | _____________ | _____________ | __________ |
| Database Administrator | _____________ | _____________ | __________ |
| Security Specialist | _____________ | _____________ | __________ |
| Project Manager | _____________ | _____________ | __________ |

---

**End of Software Design Document**

*This document is confidential and proprietary. Unauthorized distribution is prohibited.*

**Compliance:** This document adheres to IEEE Std 1016-2009 for Software Design Descriptions.
