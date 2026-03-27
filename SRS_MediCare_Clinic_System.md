# Software Requirements Specification (SRS)

# MediCare Clinic - Hospital Management System

**Document Version:** 1.1  
**Date:** February 13, 2026  
**Prepared by:** Software Engineering Team  
**Status:** Approved

---

## Document Revision History

| Version | Date              | Author           | Description                                                    |
| ------- | ----------------- | ---------------- | -------------------------------------------------------------- |
| 1.3     | March 27, 2026    | Development Team | Real-time messaging, dark mode, enhanced profile fields        |
| 1.2     | March 19, 2026    | Development Team | Patient profile enhancements (gender, DOB, address, insurance) |
| 1.1     | February 13, 2026 | Development Team | Bug fixes and maintenance updates                              |
| 1.0     | January 29, 2026  | Development Team | Initial Release                                                |

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Overall Description](#2-overall-description)
3. [System Features and Requirements](#3-system-features-and-requirements)
4. [External Interface Requirements](#4-external-interface-requirements)
5. [Non-Functional Requirements](#5-non-functional-requirements)
6. [System Models](#6-system-models)
7. [Data Requirements](#7-data-requirements)
8. [Appendices](#8-appendices)

---

## 1. Introduction

### 1.1 Purpose

This Software Requirements Specification (SRS) document provides a comprehensive description of the MediCare Clinic Hospital Management System. It details the functional and non-functional requirements, system interfaces, and operational constraints. This document is intended for:

- Development and testing teams
- Project managers and stakeholders
- System administrators and maintenance personnel
- Quality assurance teams

### 1.2 Scope

**Product Name:** MediCare Clinic - Hospital Management System

**Product Description:**  
MediCare Clinic is a web-based hospital management system designed to streamline healthcare operations by providing comprehensive appointment management, patient registration, and administrative oversight. The system facilitates efficient communication between patients and healthcare administrators through a secure, user-friendly platform.

**Key Benefits:**

- Automated appointment scheduling and management
- Real-time notification system for appointment updates
- Secure patient data management
- Administrative dashboard for healthcare operations
- Reduced manual paperwork and processing time
- Enhanced patient experience through digital self-service

**Major Features:**

- Patient registration and authentication
- Online appointment booking system
- Multi-role user management (Patients, Administrators)
- Real-time notification system
- Appointment lifecycle management
- Administrative analytics dashboard
- Responsive web interface

### 1.3 Definitions, Acronyms, and Abbreviations

| Term       | Definition                                      |
| ---------- | ----------------------------------------------- |
| SRS        | Software Requirements Specification             |
| SDD        | Software Design Document                        |
| UI         | User Interface                                  |
| CRUD       | Create, Read, Update, Delete                    |
| DBMS       | Database Management System                      |
| PHP        | Hypertext Preprocessor                          |
| MySQL      | Relational Database Management System           |
| XAMPP      | Cross-Platform, Apache, MySQL, PHP, Perl        |
| SSL/TLS    | Secure Sockets Layer / Transport Layer Security |
| AJAX       | Asynchronous JavaScript and XML                 |
| API        | Application Programming Interface               |
| HTTP/HTTPS | Hypertext Transfer Protocol / Secure            |
| SQL        | Structured Query Language                       |
| MVC        | Model-View-Controller                           |

### 1.4 References

- IEEE Std 830-1998: IEEE Recommended Practice for Software Requirements Specifications
- PHP 7.4+ Documentation (https://www.php.net/docs.php)
- MySQL 5.7+ Documentation (https://dev.mysql.com/doc/)
- Web Content Accessibility Guidelines (WCAG) 2.1
- OWASP Top 10 Web Application Security Risks

### 1.5 Overview

This document is organized into eight main sections covering introduction, system description, functional requirements, interface requirements, non-functional requirements, system models, data requirements, and appendices. Each section provides detailed specifications necessary for system development and validation.

---

## 2. Overall Description

### 2.1 Product Perspective

MediCare Clinic is a standalone web-based application operating within a hospital environment. The system integrates with:

- **Web Browsers:** Chrome, Firefox, Safari, Edge (latest versions)
- **Web Server:** Apache HTTP Server
- **Database Server:** MySQL/MariaDB
- **Operating System:** Platform-independent (Windows, Linux, macOS)

**System Context:**

```
┌─────────────┐         ┌──────────────────────┐         ┌──────────────┐
│   Patients  │◄────────┤   MediCare Clinic    │────────►│ Administrators│
│  (Web Users)│         │   Web Application    │         │  (Web Users) │
└─────────────┘         └──────────────────────┘         └──────────────┘
                                  │
                                  │
                                  ▼
                        ┌──────────────────┐
                        │  MySQL Database  │
                        │   (Data Store)   │
                        └──────────────────┘
```

### 2.2 Product Functions

The major functions of the MediCare Clinic system include:

1. **User Management**
   - Patient registration with email verification
   - Secure authentication (login/logout)
   - Password management and recovery
   - Profile management
   - Session management

2. **Appointment Management**
   - Online appointment booking
   - Appointment scheduling with date/time selection
   - Department and doctor selection
   - Appointment status tracking (Pending, Approved, Rescheduled, Canceled, Completed)
   - Appointment history viewing
   - Appointment modification and cancellation

3. **Administrative Functions**
   - Centralized appointment dashboard
   - Appointment approval/rejection workflow
   - Appointment rescheduling
   - Patient information viewing
   - System analytics and reporting
   - Notification management

4. **Notification System**
   - Real-time appointment status notifications
   - Patient notification delivery
   - Admin notification delivery
   - Read/unread status tracking
   - Notification history

5. **Reporting and Analytics**
   - Appointment statistics
   - Status-based filtering
   - Department-wise analysis
   - Patient activity tracking

### 2.3 User Classes and Characteristics

#### 2.3.1 Patients

- **Description:** End-users seeking medical appointments
- **Technical Expertise:** Basic computer literacy
- **Frequency of Use:** Occasional to moderate
- **Key Activities:**
  - Register and login
  - Book appointments
  - View appointment status
  - Manage profile
  - Receive notifications

#### 2.3.2 Administrators

- **Description:** Healthcare staff managing system operations
- **Technical Expertise:** Moderate to advanced computer skills
- **Frequency of Use:** Daily, extensive usage
- **Key Activities:**
  - Review appointment requests
  - Approve/reject appointments
  - Reschedule appointments
  - Monitor system statistics
  - Manage notifications

### 2.4 Operating Environment

**Hardware Requirements:**

- **Server:** Minimum 2GB RAM, 10GB storage, dual-core processor
- **Client:** Any device with web browser (Desktop, Tablet, Mobile)

**Software Requirements:**

- **Server OS:** Windows Server 2016+, Linux (Ubuntu 18.04+, CentOS 7+)
- **Web Server:** Apache 2.4+
- **Database:** MySQL 5.7+ or MariaDB 10.3+
- **PHP:** Version 7.4 or higher
- **Browser:** Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

**Network Requirements:**

- Internet connectivity for cloud deployment
- Local network for on-premise deployment
- HTTPS support recommended

### 2.5 Design and Implementation Constraints

1. **Technology Constraints:**
   - Must use PHP for server-side processing
   - MySQL/MariaDB for database management
   - No external payment gateway integration required
   - Must work on XAMPP development environment

2. **Regulatory Constraints:**
   - Patient data privacy compliance
   - Secure authentication mechanisms
   - Audit trail for critical operations

3. **Business Rules:**
   - Appointments can only be booked for future dates
   - One appointment per patient per time slot
   - Admin approval required before appointment confirmation

4. **Security Constraints:**
   - Password complexity requirements
   - Session timeout implementation
   - SQL injection prevention
   - XSS protection

### 2.6 Assumptions and Dependencies

**Assumptions:**

1. Users have basic internet connectivity
2. Administrators are trained on system usage
3. Server infrastructure is maintained and available
4. Regular database backups are performed
5. Doctors' information is manually maintained by administrators

**Dependencies:**

1. Apache web server availability
2. MySQL database server functionality
3. PHP runtime environment
4. Client browser JavaScript enabled
5. XAMPP stack components properly configured

---

## 3. System Features and Requirements

### 3.1 User Authentication and Authorization

#### 3.1.1 Description and Priority

**Priority:** HIGH  
Secure user authentication mechanism for patients and administrators with role-based access control.

#### 3.1.2 Functional Requirements

**FR-AUTH-001:** Patient Registration

- **Description:** System shall allow new patients to register with personal information
- **Input:** Full name, email, phone number, password, password confirmation
- **Processing:**
  - Validate all input fields
  - Check email uniqueness
  - Check phone number uniqueness
  - Hash password using secure algorithm
  - Create user record in database
- **Output:** Success message or error notification
- **Validation Rules:**
  - Email must be valid format and unique
  - Phone number must be unique
  - Password minimum 8 characters
  - All fields required

**FR-AUTH-002:** User Login (Patient)

- **Description:** Registered patients can securely login to access their dashboard
- **Input:** Email and password
- **Processing:**
  - Validate credentials against database
  - Create secure session
  - Set session variables (user_id, user_name, user_role)
  - Implement session timeout
- **Output:** Redirect to patient dashboard or error message

**FR-AUTH-003:** Admin Login

- **Description:** Administrators can login with separate authentication flow
- **Input:** Admin email and password
- **Processing:**
  - Validate admin credentials
  - Create admin session with role verification
  - Set admin-specific session variables
- **Output:** Redirect to admin dashboard or error message

**FR-AUTH-004:** Password Change

- **Description:** Users can change their password while logged in
- **Input:** Current password, new password, password confirmation
- **Processing:**
  - Verify current password
  - Validate new password strength
  - Update password in database
- **Output:** Success or error message

**FR-AUTH-005:** Logout Functionality

- **Description:** Users can securely logout from the system
- **Processing:**
  - Destroy user session
  - Clear session variables
  - Redirect to login page
- **Output:** Confirmation and redirect

**FR-AUTH-006:** Session Management

- **Description:** System maintains secure user sessions
- **Requirements:**
  - Session timeout after 30 minutes of inactivity
  - Session regeneration on privilege escalation
  - Prevent session fixation attacks
  - Cache control headers to prevent page caching

### 3.2 Appointment Management

#### 3.2.1 Description and Priority

**Priority:** HIGH  
Core functionality enabling patients to book appointments and administrators to manage them.

#### 3.2.2 Functional Requirements

**FR-APPT-001:** Appointment Booking

- **Description:** Patients can book appointments with selected doctors
- **Input:**
  - Department selection
  - Doctor selection (ID, name, specialty, photo)
  - Appointment date
  - Appointment time
  - Reason for visit
  - Patient contact information
- **Processing:**
  - Generate unique appointment ID
  - Validate date (future dates only)
  - Check time slot availability
  - Link to logged-in patient account
  - Set initial status as "pending"
  - Store in database
  - Create notification for admin
  - Create notification for patient
- **Output:** Appointment confirmation with unique ID
- **Validation Rules:**
  - Date must be today or future date
  - All required fields must be filled
  - Doctor must be available

**FR-APPT-002:** View Patient Appointments

- **Description:** Patients can view all their appointments
- **Processing:**
  - Retrieve appointments for logged-in patient
  - Display in reverse chronological order
  - Show appointment details and status
  - Calculate statistics (total, upcoming, pending, approved, etc.)
- **Output:** List of appointments with filtering options

**FR-APPT-003:** View All Appointments (Admin)

- **Description:** Administrators can view all system appointments
- **Processing:**
  - Retrieve all appointments from database
  - Display comprehensive appointment list
  - Show patient information
  - Calculate system-wide statistics
- **Output:** Comprehensive appointment dashboard

**FR-APPT-004:** Appointment Status Management

- **Description:** Administrators can update appointment status
- **Status Options:**
  - Pending (initial state)
  - Approved (confirmed by admin)
  - Rescheduled (date/time changed)
  - Canceled (appointment canceled)
  - Completed (appointment finished)
- **Processing:**
  - Update status in database
  - Update timestamp
  - Create notification for patient
  - Log status change
- **Output:** Confirmation message and updated view

**FR-APPT-005:** Appointment Cancellation

- **Description:** Patients and admins can cancel appointments
- **Input:** Appointment ID, cancellation reason (optional)
- **Processing:**
  - Verify user authorization
  - Update status to "canceled"
  - Record cancellation timestamp
  - Notify relevant parties
- **Output:** Cancellation confirmation

**FR-APPT-006:** Appointment Details View

- **Description:** View comprehensive details of specific appointment
- **Display Information:**
  - Appointment ID
  - Patient information
  - Doctor information with photo
  - Department and specialty
  - Date and time
  - Reason for visit
  - Notes (admin only)
  - Status and timestamps
  - Creation date

**FR-APPT-007:** Appointment Statistics

- **Description:** System provides statistical analysis of appointments
- **Metrics:**
  - Total appointments
  - Status breakdown (pending, approved, rescheduled, canceled, completed)
  - Upcoming appointments count
  - Department-wise distribution
- **Output:** Dashboard widgets with real-time counts

### 3.3 Notification System

#### 3.3.1 Description and Priority

**Priority:** MEDIUM  
Real-time notification system for appointment updates and system events.

#### 3.3.2 Functional Requirements

**FR-NOTIF-001:** Admin Notifications

- **Description:** Administrators receive notifications for new appointments
- **Trigger Events:**
  - New appointment booking
  - Appointment cancellation by patient
- **Notification Content:**
  - Notification type
  - Message text
  - Associated appointment ID
  - Timestamp
  - Read/unread status
- **Storage:** Notifications table in database

**FR-NOTIF-002:** Patient Notifications

- **Description:** Patients receive notifications for appointment status changes
- **Trigger Events:**
  - Appointment approved
  - Appointment rescheduled
  - Appointment canceled by admin
  - Appointment completed
- **Notification Content:**
  - Patient ID
  - Appointment ID
  - Patient name
  - Notification type
  - Message text
  - Read status
  - Timestamp

**FR-NOTIF-003:** Notification Display

- **Description:** Display notifications in user interface
- **Features:**
  - Unread notification count badge
  - Dropdown notification panel
  - Notification list with timestamps
  - Read/unread visual distinction
  - Link to related appointment

**FR-NOTIF-004:** Mark Notifications as Read

- **Description:** Users can mark notifications as read
- **Processing:**
  - Update is_read flag to TRUE
  - Set read_at timestamp
  - Update UI to reflect read status
- **Scope:** Individual or bulk marking

**FR-NOTIF-005:** Notification Retrieval

- **Description:** Fetch notifications via AJAX
- **Parameters:**
  - User role (admin/patient)
  - Patient ID (for patient notifications)
  - Limit (number of notifications to retrieve)
- **Response:** JSON array of notifications

### 3.4 User Profile Management

#### 3.4.1 Description and Priority

**Priority:** MEDIUM  
User profile viewing and management capabilities.

#### 3.4.2 Functional Requirements

**FR-PROF-001:** View Patient Profile

- **Description:** Patients can view their profile information
- **Display Information:**
  - Full name
  - Email address
  - Phone number
  - Account creation date
  - Total appointments
  - Account status

**FR-PROF-002:** Edit Patient Profile

- **Description:** Patients can update their profile information
- **Editable Fields:**
  - Full name
  - Phone number
- **Validation:**
  - Phone number uniqueness check
  - Input sanitization
- **Output:** Success or error message

**FR-PROF-003:** View Patient Settings

- **Description:** Access to account settings and preferences
- **Features:**
  - Password change option
  - Notification preferences
  - Account information display

### 3.5 Administrative Dashboard

#### 3.5.1 Description and Priority

**Priority:** HIGH  
Comprehensive administrative interface for system management.

#### 3.5.2 Functional Requirements

**FR-ADMIN-001:** Admin Dashboard Overview

- **Description:** Central admin interface with key metrics
- **Display Components:**
  - Total appointments count
  - Pending appointments count
  - Approved appointments count
  - Rescheduled appointments count
  - Canceled appointments count
  - Recent appointments list
  - Quick action buttons

**FR-ADMIN-002:** Appointment Filtering

- **Description:** Filter appointments by various criteria
- **Filter Options:**
  - Status (All, Pending, Approved, Rescheduled, Canceled, Completed)
  - Date range
  - Department
  - Doctor selection filter
  - Search by patient name or appointment ID
- **Output:** Filtered appointment list with proper status handling

**FR-ADMIN-003:** Bulk Operations

- **Description:** Perform actions on multiple appointments
- **Operations:**
  - Bulk status update
  - Bulk notification sending
  - Export appointment data

**FR-ADMIN-004:** Admin Settings

- **Description:** System configuration and admin preferences
- **Features:**
  - Admin profile management
  - System preferences
  - Department management
  - Doctor information management

### 3.6 Patient-Doctor Communication System

#### 3.6.1 Description and Priority

**Priority:** HIGH  
Real-time messaging system enabling direct communication between patients and doctors.

#### 3.6.2 Functional Requirements

**FR-MSG-001:** Real-Time Messaging

- **Description:** Enable direct messaging between patients and assigned doctors
- **Features:**
  - Send and receive messages in real-time
  - Message history with timestamps
  - Unread message indicators
  - Typing status indicators
  - Message read receipts
- **Input:** Message text content
- **Output:** Instant message delivery with live updates

**FR-MSG-002:** Conversation Management

- **Description:** Manage multiple conversations between patients and doctors
- **Features:**
  - Separate conversation thread per patient-doctor pair
  - Conversation list with last message preview
  - Search conversations
  - Conversation timestamps
  - Active status indicators
- **Access Control:**
  - Patients can only message assigned doctors
  - Doctors can message their assigned patients
  - Admin cannot access messaging (feature not available for admin role)

**FR-MSG-003:** Dark Mode Support

- **Description:** Optional dark mode UI for messaging pages
- **Features:**
  - Toggle dark mode for better viewing experience
  - Persistent dark mode preference
  - Full dark mode styling for messages interface
  - Improved readability in low-light conditions

**FR-MSG-004:** Mobile-Responsive Messaging

- **Description:** Responsive messaging interface for all devices
- **Features:**
  - Optimized layout for mobile devices
  - Touch-friendly controls
  - Responsive conversation list sizing
  - Hidden/collapsed panels on mobile for better space utilization
  - Back button for mobile navigation
  - Adaptive font sizes and button dimensions

**FR-MSG-005:** Message Notifications

- **Description:** Notify users of new messages
- **Features:**
  - In-app notification indicators
  - Unread message badge on navigation
  - Desktop notification support
  - Email notification option

### 3.7 Patient Profile and Account Management

#### 3.7.1 Description and Priority

**Priority:** MEDIUM  
Enhanced patient profile management with additional personal information fields.

#### 3.7.2 Functional Requirements

**FR-PROFILE-001:** Extended Profile Information

- **Description:** Patients can view and update comprehensive profile information
- **Editable Fields:**
  - Phone number
  - Gender (Male, Female, Other)
  - Date of Birth
  - Home Address
  - Insurance Provider/Plan
- **Display:**
  - Profile summary with all information displayed in formatted cards
  - Separate edit form for modifications
  - Success confirmation after updates
  - Data persistence across sessions

**FR-PROFILE-002:** Profile Information Display

- **Description:** Display patient information prominently after updates
- **Features:**
  - Information cards showing current field values
  - Color-coded background for better visibility
  - Field labels and formatted values
  - Auto-refresh to show updates immediately

### 3.8 Public Website Features

#### 3.8.1 Description and Priority

**Priority:** MEDIUM  
Public-facing pages for information and registration.

#### 3.8.2 Functional Requirements

**FR-PUBLIC-001:** Home Page

- **Description:** Landing page with system overview
- **Content:**
  - Hero section with call-to-action
  - Services overview
  - Featured doctors
  - Statistics
  - Contact information
  - Navigation menu

**FR-PUBLIC-002:** Services Page

- **Description:** Display available medical services
- **Content:**
  - Service categories
  - Service descriptions
  - Booking call-to-action

**FR-PUBLIC-003:** About Page

- **Description:** Information about the clinic
- **Content:**
  - Clinic history
  - Mission and vision
  - Team information
  - Facility details

**FR-PUBLIC-004:** Terms and Privacy Pages

- **Description:** Legal and privacy information
- **Content:**
  - Terms of service
  - Privacy policy
  - Data protection information

---

## 4. External Interface Requirements

### 4.1 User Interfaces

#### 4.1.1 General UI Requirements

- **UI-001:** Responsive design supporting desktop, tablet, and mobile devices
- **UI-002:** Consistent navigation across all pages
- **UI-003:** Intuitive form layouts with clear labels
- **UI-004:** Visual feedback for user actions (loading indicators, success/error messages)
- **UI-005:** Accessibility compliance (WCAG 2.1 Level AA)
- **UI-006:** Dark mode support for messaging pages
- **UI-007:** Mobile-optimized interface with responsive sidebar collapsing

#### 4.1.2 Patient Interface Components

**Login/Registration Pages:**

- Clean, centered form design
- Email and password fields
- Remember me option
- Forgot password link
- Registration link/form
- Input validation with error messages

**Patient Dashboard:**

- Welcome message with user name
- Navigation sidebar with icons
- Statistics cards (total, upcoming, approved appointments)
- Recent appointments table
- Book appointment button
- Notification bell with badge
- Profile and logout options

**Appointment Booking Page:**

- Multi-step booking form
- Department selection dropdown
- Doctor selection with photos
- Date picker (future dates only)
- Time slot selection
- Reason for visit text area
- Submit and cancel buttons
- Booking confirmation modal

**Patient Profile Page:**

- Profile information display
- Edit profile form
- Password change section
- Account statistics
- Appointment history

**Patient Messaging Page:**

- Conversation list with doctor names and last message preview
- Chat area with message thread history
- Message input box and send button
- Typing indicators showing when doctor is typing
- Dark mode toggle
- Mobile-responsive panel layout with back button
- Unread message indicators
- Timestamps on all messages

#### 4.1.3 Doctor Interface Components

**Doctor Dashboard:**

- Welcome message with doctor name
- Navigation sidebar with icons
- Statistics cards (total patients, upcoming appointments)
- Recent appointments list with patient names
- Quick action button to message patients
- Appointment management options

**Doctor Messaging Page:**

- Conversation list with patient names and appointment status
- Chat area with message thread history
- Message input box and send button
- Typing indicators showing when patient is typing
- Dark mode toggle
- Mobile-responsive panel layout with back button
- Unread message indicators
- Timestamps on all messages

#### 4.1.4 Admin Interface Components

**Admin Dashboard:**

- Top navigation bar
- Statistics overview (5+ metric cards)
- Appointments table with filtering
- Status badges with color coding
- Action buttons (Approve, Reschedule, Cancel)
- Doctor filter dropdown
- Status tab filters (All, Pending, Approved, Completed, Canceled, Rescheduled)
- Search functionality
- Date range filters
- Notification panel
- Logout option

**Appointment Management:**

- Detailed appointment view modal
- Status dropdown selector
- Notes text area
- Patient contact information
- Doctor details with photo
- Timeline of status changes
- Bulk action checkboxes

### 4.2 Hardware Interfaces

**HW-001:** System shall operate on standard web servers without specialized hardware  
**HW-002:** Database shall be hosted on standard database servers  
**HW-003:** Client access via standard computing devices (PC, laptop, tablet, smartphone)

### 4.3 Software Interfaces

#### 4.3.1 Database Interface

- **Interface:** MySQL/MariaDB RDBMS
- **Version:** MySQL 5.7+ or MariaDB 10.3+
- **Connection:** MySQLi extension in PHP
- **Character Set:** UTF-8 (utf8mb4)
- **Access Method:** Prepared statements for security
- **Connection Pooling:** Managed by PHP database connection functions

#### 4.3.2 Web Server Interface

- **Interface:** Apache HTTP Server
- **Version:** 2.4+
- **Modules Required:**
  - mod_rewrite (URL rewriting)
  - mod_ssl (HTTPS support)
  - mod_php (PHP processing)
- **Configuration:**
  - .htaccess support enabled
  - File upload limits configured
  - Session settings optimized

#### 4.3.3 PHP Runtime

- **Version:** PHP 7.4+
- **Extensions Required:**
  - mysqli (database connectivity)
  - session (session management)
  - json (JSON processing)
  - filter (input validation)
  - hash (password hashing)
- **Configuration:**
  - error_reporting enabled for development
  - display_errors off for production
  - session.cookie_httponly enabled
  - session.cookie_secure enabled (for HTTPS)

#### 4.3.4 External Libraries

- **Tailwind CSS:** UI styling framework (CDN)
- **Feather Icons:** Icon library (CDN)
- **AOS (Animate On Scroll):** Animation library (CDN)
- **JavaScript:** Vanilla JS for interactivity

### 4.4 Communications Interfaces

**COM-001:** HTTP/HTTPS Protocol

- Primary communication protocol
- HTTPS recommended for production
- SSL/TLS 1.2+ for encryption

**COM-002:** AJAX Communication

- Asynchronous data exchange
- JSON data format
- RESTful-style endpoints
- Error handling and retry logic

**COM-003:** Form Submission

- POST method for sensitive data
- GET method for queries
- Multipart/form-data for file uploads
- CSRF protection

---

## 5. Non-Functional Requirements

### 5.1 Performance Requirements

**NFR-PERF-001:** Response Time

- Page load time: < 3 seconds on standard broadband
- Database query execution: < 1 second for standard queries
- AJAX requests: < 2 seconds response time
- Form submission processing: < 3 seconds

**NFR-PERF-002:** Throughput

- Support minimum 100 concurrent users
- Handle 1000+ appointments in database
- Process 50 appointment bookings per hour
- Support 500+ registered patients

**NFR-PERF-003:** Resource Utilization

- Database size: < 1GB for 10,000 appointments
- Server memory: < 512MB per concurrent user session
- Page size: < 2MB including assets
- API response size: < 100KB per request

### 5.2 Safety Requirements

**NFR-SAFE-001:** Data Backup

- Daily automated database backups
- Backup retention for 30 days minimum
- Backup verification procedures
- Disaster recovery plan documented

**NFR-SAFE-002:** Data Integrity

- Database transactions for critical operations
- Foreign key constraints enforced
- Referential integrity maintained
- Data validation before storage

**NFR-SAFE-003:** Failure Recovery

- Graceful error handling
- User-friendly error messages
- System state recovery on failure
- Session data preservation where possible

### 5.3 Security Requirements

**NFR-SEC-001:** Authentication

- Secure password hashing (bcrypt or argon2)
- Minimum password length: 8 characters
- Account lockout after 5 failed attempts (recommended)
- Session timeout: 30 minutes of inactivity

**NFR-SEC-002:** Authorization

- Role-based access control (Patient, Admin)
- Session validation on each page load
- Prevent unauthorized access to admin functions
- User-specific data isolation

**NFR-SEC-003:** Data Protection

- SQL injection prevention (prepared statements)
- XSS protection (input sanitization, output encoding)
- CSRF protection (tokens for forms)
- Sensitive data not logged or exposed
- Password never stored in plain text

**NFR-SEC-004:** Communication Security

- HTTPS recommended for production
- Secure cookie flags (HttpOnly, Secure)
- Cache control headers for sensitive pages
- No sensitive data in URL parameters

**NFR-SEC-005:** Privacy

- Patient data accessible only to authorized users
- Personal information not shared with third parties
- Compliance with data protection regulations
- Audit trail for data access (recommended)

### 5.4 Software Quality Attributes

**NFR-QUAL-001:** Availability

- System uptime: 99% minimum
- Planned maintenance windows communicated in advance
- Maximum unplanned downtime: 4 hours per month

**NFR-QUAL-002:** Maintainability

- Code documentation and comments
- Modular architecture
- Consistent coding standards
- Version control (Git)
- Clear separation of concerns

**NFR-QUAL-003:** Reliability

- Error rate: < 1% of all transactions
- Data consistency maintained
- No data loss during normal operations
- Automated error logging

**NFR-QUAL-004:** Usability

- Intuitive navigation (maximum 3 clicks to any function)
- Consistent UI patterns
- Clear error messages with actionable guidance
- Help documentation available
- Minimal training required for basic operations

**NFR-QUAL-005:** Scalability

- Horizontal scaling supported (multiple web servers)
- Database optimization for growth
- Efficient query design with proper indexing
- Support for 10x user growth without architecture changes

**NFR-QUAL-006:** Portability

- Cross-browser compatibility
- Platform-independent (Windows, Linux, macOS server)
- Minimal server-specific dependencies
- Standard web technologies

**NFR-QUAL-007:** Testability

- Unit testing capability for business logic
- Integration testing for database operations
- UI testing for critical workflows
- Test data generation scripts

### 5.5 Business Rules

**BR-001:** Appointment Scheduling

- Appointments can only be booked for future dates and times
- One patient can have multiple appointments
- Appointment ID must be unique across the system

**BR-002:** User Registration

- Email addresses must be unique
- Phone numbers must be unique
- Patients can self-register; admins are created manually

**BR-003:** Status Workflow

- New appointments start in "pending" status
- Only admins can change appointment status
- Status transitions: Pending → Approved/Canceled
- Approved appointments can be → Rescheduled/Canceled/Completed

**BR-004:** Data Retention

- Patient accounts retained indefinitely unless deleted
- Appointment history maintained permanently
- Notifications retained for 90 days (recommended)

---

## 6. System Models

### 6.1 Use Case Diagrams

```
Patient Use Cases:
┌──────────────────────────────────────────────────────────┐
│                    MediCare System                       │
│                                                          │
│  ┌────────────┐                     ┌────────────┐     │
│  │  Register  │                     │   Login    │     │
│  └─────┬──────┘                     └─────┬──────┘     │
│        │                                  │            │
│        └────────┬─────────────────────────┘            │
│                 │                                      │
│                 ▼                                      │
│    ┌────────────────────────┐                         │
│    │   Patient Dashboard    │                         │
│    └────────────────────────┘                         │
│             │      │      │                            │
│    ┌────────┘      │      └────────┐                  │
│    │               │               │                  │
│    ▼               ▼               ▼                  │
│ ┌──────┐    ┌────────────┐   ┌─────────┐            │
│ │ Book │    │    View    │   │  View   │            │
│ │Appt. │    │    Appt.   │   │ Profile │            │
│ └──────┘    └────────────┘   └─────────┘            │
│                   │                 │                 │
│                   ▼                 ▼                 │
│            ┌────────────┐    ┌──────────┐           │
│            │   Cancel   │    │   Edit   │           │
│            │    Appt.   │    │  Profile │           │
│            └────────────┘    └──────────┘           │
└──────────────────────────────────────────────────────┘

Administrator Use Cases:
┌──────────────────────────────────────────────────────────┐
│                    MediCare System                       │
│                                                          │
│              ┌────────────┐                             │
│              │Admin Login │                             │
│              └─────┬──────┘                             │
│                    │                                    │
│                    ▼                                    │
│          ┌──────────────────┐                          │
│          │ Admin Dashboard  │                          │
│          └──────────────────┘                          │
│                    │                                    │
│         ┌──────────┼──────────┐                        │
│         │          │          │                        │
│         ▼          ▼          ▼                        │
│    ┌────────┐ ┌────────┐ ┌────────┐                  │
│    │  View  │ │Manage  │ │ View   │                  │
│    │  All   │ │ Appt.  │ │ Stats  │                  │
│    │ Appts  │ │Status  │ │        │                  │
│    └────────┘ └────┬───┘ └────────┘                  │
│                    │                                   │
│         ┌──────────┼──────────┐                       │
│         │          │          │                       │
│         ▼          ▼          ▼                       │
│    ┌────────┐ ┌────────┐ ┌────────┐                 │
│    │Approve │ │Resched.│ │ Cancel │                 │
│    │  Appt  │ │  Appt  │ │  Appt  │                 │
│    └────────┘ └────────┘ └────────┘                 │
└──────────────────────────────────────────────────────┘
```

### 6.2 Data Flow Diagrams

**Level 0 DFD (Context Diagram):**

```
                    ┌─────────────┐
                    │   Patient   │
                    └──────┬──────┘
                           │
            ┌──────────────┼──────────────┐
            │              │              │
     Registration     Booking        View Status
        Data          Request         Request
            │              │              │
            └──────────────┼──────────────┘
                           │
                           ▼
                  ┌────────────────┐
                  │   MediCare     │
                  │     System     │
                  └────────────────┘
                           │
                           │
            ┌──────────────┼──────────────┐
            │              │              │
      Appointment    Status Update   Appointment
         List          Request          Data
            │              │              │
            └──────────────┼──────────────┘
                           │
                           ▼
                    ┌─────────────┐
                    │Administrator│
                    └─────────────┘
```

**Level 1 DFD (Main Processes):**

```
Patient ──Registration──> [1.0 User Management] ──User Data──> (Users DB)
                                    │
Patient ──Login──> [2.0 Authentication] ──Session──> Patient Dashboard
                                    │
Patient ──Booking Info──> [3.0 Appointment] ──Appt Data──> (Appointments DB)
                          Management            │
                                               │
                          ┌────────────────────┘
                          │
                          └──> [4.0 Notification] ──Notification──> Admin
                               System               Data          Patient
                                    │
                                    ▼
                            (Notifications DB)
                                    │
Admin ──Status Update──> [5.0 Status Management] ──Updated Data──> (Appointments DB)
                                    │
                                    └──> [4.0 Notification System]
```

### 6.3 State Transition Diagrams

**Appointment Status State Diagram:**

```
                    ┌─────────┐
                    │   NEW   │
                    └────┬────┘
                         │
                         ▼
                   ┌──────────┐
            ┌─────►│ PENDING  │◄─────┐
            │      └────┬─────┘      │
            │           │            │
            │      ┌────┼─────┐      │
            │      │    │     │      │
            │      ▼    ▼     ▼      │
            │  ┌────┐ ┌──────────┐  │
            │  │CANCEL│ │ APPROVED │  │
            │  │ED  │ └────┬─────┘  │
            │  └────┘      │        │
            │              │        │
            │         ┌────┼────┐   │
            │         │    │    │   │
            │         ▼    ▼    ▼   │
            │    ┌────────┐ ┌────────┐
            └────│RESCHED.│ │COMPLET.│
                 │  ULED  │ │   ED   │
                 └────────┘ └────────┘
```

**User Session State Diagram:**

```
  ┌──────────┐
  │ LOGGED   │
  │   OUT    │
  └────┬─────┘
       │
       │ Login Success
       ▼
  ┌──────────┐
  │ LOGGED   │──────> Session Active
  │   IN     │        │
  └────┬─────┘        │
       │              │
       │ ◄────────────┘
       │ Activity
       │
       │ Timeout or Logout
       ▼
  ┌──────────┐
  │ LOGGED   │
  │   OUT    │
  └──────────┘
```

---

## 7. Data Requirements

### 7.1 Logical Data Model

**Entity Relationship Diagram:**

```
┌─────────────────┐
│     USERS       │
│  (Patients)     │
├─────────────────┤
│ • id (PK)       │
│ • full_name     │
│ • email (UK)    │
│ • phone (UK)    │
│ • password      │
│ • created_at    │
└────────┬────────┘
         │
         │ 1:N
         │
         ▼
┌─────────────────────────┐
│    APPOINTMENTS         │
├─────────────────────────┤
│ • id (PK)               │
│ • appointment_id (UK)   │
│ • patient_id (FK)       │
│ • patient_name          │
│ • phone                 │
│ • department            │
│ • doctor_id             │
│ • doctor_name           │
│ • doctor_specialty      │
│ • doctor_photo          │
│ • appointment_date      │
│ • appointment_time      │
│ • reason                │
│ • notes                 │
│ • status                │
│ • created_at            │
│ • updated_at            │
└──────────┬──────────────┘
           │
           │ 1:N
     ┌─────┴─────┐
     │           │
     ▼           ▼
┌────────────────┐  ┌────────────────────┐
│NOTIFICATIONS   │  │PATIENT_NOTIFICATIONS│
├────────────────┤  ├────────────────────┤
│• id (PK)       │  │• id (PK)           │
│• type          │  │• patient_id (FK)   │
│• message       │  │• appointment_id(FK)│
│• appt_id (FK)  │  │• patient_name      │
│• is_read       │  │• notif_type        │
│• created_at    │  │• message           │
│• read_at       │  │• is_read           │
└────────────────┘  │• created_at        │
                    │• read_at           │
                    └────────────────────┘

┌─────────────────┐
│     ADMIN       │
├─────────────────┤
│ • id (PK)       │
│ • full_name     │
│ • email (UK)    │
│ • password      │
│ • created_at    │
└─────────────────┘
```

### 7.2 Data Dictionary

#### 7.2.1 USERS Table

| Column     | Type         | Constraints                 | Description                |
| ---------- | ------------ | --------------------------- | -------------------------- |
| id         | INT(11)      | PRIMARY KEY, AUTO_INCREMENT | Unique patient identifier  |
| full_name  | VARCHAR(100) | NOT NULL                    | Patient's full name        |
| email      | VARCHAR(100) | NOT NULL, UNIQUE            | Patient's email address    |
| phone      | VARCHAR(20)  | NOT NULL, UNIQUE            | Patient's contact number   |
| password   | VARCHAR(255) | NOT NULL                    | Hashed password            |
| created_at | TIMESTAMP    | DEFAULT CURRENT_TIMESTAMP   | Account creation timestamp |

**Indexes:**

- PRIMARY KEY on id
- UNIQUE KEY on email
- INDEX on email

#### 7.2.2 ADMIN Table

| Column     | Type         | Constraints                 | Description                |
| ---------- | ------------ | --------------------------- | -------------------------- |
| id         | INT(11)      | PRIMARY KEY, AUTO_INCREMENT | Unique admin identifier    |
| full_name  | VARCHAR(100) | NOT NULL                    | Administrator's full name  |
| email      | VARCHAR(100) | NOT NULL, UNIQUE            | Administrator's email      |
| password   | VARCHAR(255) | NOT NULL                    | Hashed password            |
| created_at | TIMESTAMP    | DEFAULT CURRENT_TIMESTAMP   | Account creation timestamp |

**Indexes:**

- PRIMARY KEY on id
- UNIQUE KEY on email
- INDEX on email

#### 7.2.3 APPOINTMENTS Table

| Column           | Type         | Constraints                      | Description                |
| ---------------- | ------------ | -------------------------------- | -------------------------- |
| id               | INT(11)      | PRIMARY KEY, AUTO_INCREMENT      | Internal ID                |
| appointment_id   | VARCHAR(50)  | NOT NULL, UNIQUE                 | User-facing appointment ID |
| patient_id       | INT(11)      | FOREIGN KEY → users.id, SET NULL | Reference to patient       |
| patient_name     | VARCHAR(100) | NOT NULL                         | Patient name snapshot      |
| phone            | VARCHAR(20)  | NULL                             | Patient phone snapshot     |
| department       | VARCHAR(100) | NOT NULL                         | Medical department         |
| doctor_id        | VARCHAR(50)  | NOT NULL                         | Doctor identifier          |
| doctor_name      | VARCHAR(100) | NOT NULL                         | Doctor's name              |
| doctor_specialty | VARCHAR(100) | NOT NULL                         | Doctor's specialty         |
| doctor_photo     | VARCHAR(255) | NULL                             | Doctor's photo path        |
| appointment_date | DATE         | NOT NULL                         | Scheduled date             |
| appointment_time | TIME         | NOT NULL                         | Scheduled time             |
| reason           | TEXT         | NULL                             | Reason for visit           |
| notes            | TEXT         | NULL                             | Admin notes                |
| status           | ENUM         | NOT NULL, DEFAULT 'pending'      | Appointment status         |
| created_at       | TIMESTAMP    | DEFAULT CURRENT_TIMESTAMP        | Creation timestamp         |
| updated_at       | TIMESTAMP    | ON UPDATE CURRENT_TIMESTAMP      | Last update timestamp      |

**Indexes:**

- PRIMARY KEY on id
- UNIQUE KEY on appointment_id
- INDEX on patient_id
- INDEX on status
- INDEX on appointment_date
- INDEX on doctor_id

**Status Values:**

- pending
- approved
- rescheduled
- canceled
- completed

#### 7.2.4 NOTIFICATIONS Table

| Column         | Type        | Constraints                 | Description          |
| -------------- | ----------- | --------------------------- | -------------------- |
| id             | INT         | PRIMARY KEY, AUTO_INCREMENT | Notification ID      |
| type           | VARCHAR(50) | NOT NULL                    | Notification type    |
| message        | TEXT        | NOT NULL                    | Notification message |
| appointment_id | VARCHAR(50) | NULL                        | Related appointment  |
| is_read        | BOOLEAN     | DEFAULT FALSE               | Read status          |
| created_at     | TIMESTAMP   | DEFAULT CURRENT_TIMESTAMP   | Creation time        |
| read_at        | TIMESTAMP   | NULL                        | Read timestamp       |

**Indexes:**

- PRIMARY KEY on id
- INDEX on appointment_id
- INDEX on is_read
- INDEX on created_at

#### 7.2.5 PATIENT_NOTIFICATIONS Table

| Column            | Type         | Constraints                     | Description           |
| ----------------- | ------------ | ------------------------------- | --------------------- |
| id                | INT          | PRIMARY KEY, AUTO_INCREMENT     | Notification ID       |
| patient_id        | INT(11)      | FOREIGN KEY → users.id, CASCADE | Patient reference     |
| appointment_id    | VARCHAR(50)  | NOT NULL                        | Appointment reference |
| patient_name      | VARCHAR(255) | NOT NULL                        | Patient name          |
| notification_type | VARCHAR(50)  | NOT NULL                        | Type of notification  |
| message           | TEXT         | NOT NULL                        | Notification message  |
| is_read           | BOOLEAN      | DEFAULT FALSE                   | Read status           |
| created_at        | TIMESTAMP    | DEFAULT CURRENT_TIMESTAMP       | Creation time         |
| read_at           | TIMESTAMP    | NULL                            | Read timestamp        |

**Indexes:**

- PRIMARY KEY on id
- INDEX on patient_id
- INDEX on appointment_id
- INDEX on is_read
- INDEX on created_at

### 7.3 Data Integrity and Constraints

**Referential Integrity:**

1. appointments.patient_id → users.id (ON DELETE SET NULL, ON UPDATE CASCADE)
2. patient_notifications.patient_id → users.id (ON DELETE CASCADE, ON UPDATE CASCADE)

**Business Constraints:**

1. Email addresses must be unique system-wide
2. Phone numbers must be unique for patients
3. Appointment IDs must be unique
4. Appointment dates cannot be in the past (application logic)
5. One patient can have multiple appointments
6. Status can only be one of predefined values

### 7.4 Data Backup and Recovery

**Backup Strategy:**

- Daily full database backups
- Transaction log backups every 6 hours
- Backup retention: 30 days
- Off-site backup storage recommended

**Recovery Procedures:**

- Point-in-time recovery capability
- Maximum acceptable data loss: 6 hours
- Maximum recovery time: 2 hours

---

## 8. Appendices

### 8.1 Appendix A: Glossary

| Term           | Definition                                                 |
| -------------- | ---------------------------------------------------------- |
| Administrator  | Healthcare staff member with system management privileges  |
| Appointment    | Scheduled meeting between patient and doctor               |
| Authentication | Process of verifying user identity                         |
| Authorization  | Process of determining user access rights                  |
| Dashboard      | Main interface showing system overview and key information |
| Department     | Medical specialty division within the clinic               |
| HTTPS          | Secure version of HTTP protocol using SSL/TLS encryption   |
| Notification   | System message informing users of events or updates        |
| Patient        | End-user seeking medical services through the system       |
| Session        | Period of authenticated user interaction with the system   |
| Status         | Current state of an appointment in its lifecycle           |
| Validation     | Process of checking data correctness and completeness      |

### 8.2 Appendix B: Analysis Models

**User Persona - Patient:**

- **Name:** Sarah Johnson
- **Age:** 34
- **Occupation:** Marketing Manager
- **Tech Savviness:** Moderate
- **Goals:**
  - Book appointments quickly
  - Track appointment status
  - Manage personal health records
- **Pain Points:**
  - Long phone wait times
  - Difficulty scheduling appointments
  - No visibility into appointment status
- **Usage Pattern:** Books 2-3 appointments per year, prefers online services

**User Persona - Administrator:**

- **Name:** Dr. Michael Chen
- **Age:** 42
- **Occupation:** Clinic Administrator
- **Tech Savviness:** High
- **Goals:**
  - Efficiently manage appointments
  - Reduce no-shows
  - Optimize doctor schedules
  - Improve patient satisfaction
- **Pain Points:**
  - Manual appointment management
  - Communication delays
  - Scheduling conflicts
- **Usage Pattern:** Uses system daily, 4-6 hours per day

### 8.3 Appendix C: Sample Use Cases (Detailed)

**Use Case UC-001: Patient Books Appointment**

| Field             | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Use Case ID       | UC-001                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| Use Case Name     | Patient Books Appointment                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| Actor             | Registered Patient                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| Preconditions     | Patient is logged in                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      |
| Trigger           | Patient clicks "Book Appointment" button                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| Normal Flow       | 1. System displays appointment booking form<br>2. Patient selects department<br>3. System displays available doctors<br>4. Patient selects doctor<br>5. Patient selects date (future date)<br>6. Patient selects time slot<br>7. Patient enters reason for visit<br>8. Patient reviews and submits booking<br>9. System validates data<br>10. System generates unique appointment ID<br>11. System creates appointment record<br>12. System creates notifications<br>13. System displays confirmation with appointment ID |
| Alternative Flows | 2a. Patient exits without completing<br>9a. Validation fails - display errors, return to step 2<br>12a. Notification creation fails - log error, continue                                                                                                                                                                                                                                                                                                                                                                 |
| Postconditions    | Appointment created with "pending" status<br>Admin notification created<br>Patient notification created                                                                                                                                                                                                                                                                                                                                                                                                                   |
| Business Rules    | - Only future dates allowed<br>- All required fields must be filled<br>- Doctor must be from selected department                                                                                                                                                                                                                                                                                                                                                                                                          |

**Use Case UC-002: Admin Approves Appointment**

| Field             | Description                                                                                                                                                                                                                                                                                                       |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Use Case ID       | UC-002                                                                                                                                                                                                                                                                                                            |
| Use Case Name     | Admin Approves Appointment                                                                                                                                                                                                                                                                                        |
| Actor             | Administrator                                                                                                                                                                                                                                                                                                     |
| Preconditions     | - Admin is logged in<br>- Appointment exists in "pending" status                                                                                                                                                                                                                                                  |
| Trigger           | Admin clicks "Approve" button for an appointment                                                                                                                                                                                                                                                                  |
| Normal Flow       | 1. System displays appointment details<br>2. Admin reviews appointment information<br>3. Admin confirms approval<br>4. System updates status to "approved"<br>5. System updates timestamp<br>6. System creates patient notification<br>7. System displays success message<br>8. System refreshes appointment list |
| Alternative Flows | 3a. Admin cancels action - return to dashboard<br>4a. Update fails - display error, log issue<br>6a. Notification creation fails - log error, continue                                                                                                                                                            |
| Postconditions    | - Appointment status = "approved"<br>- Patient notified of approval<br>- Updated_at timestamp recorded                                                                                                                                                                                                            |
| Business Rules    | - Only pending appointments can be approved<br>- Admin must have authorization<br>- Notification must be sent to patient                                                                                                                                                                                          |

### 8.4 Appendix D: Security Considerations

**Password Policy:**

- Minimum 8 characters
- Hashed using bcrypt or argon2
- Salt added before hashing
- Never transmitted or stored in plain text

**Session Security:**

- Session ID regenerated after login
- HttpOnly flag set on session cookies
- Secure flag set (HTTPS)
- 30-minute inactivity timeout
- Session destroyed on logout

**Input Validation:**

- All user input sanitized
- HTML special characters encoded
- SQL injection prevention via prepared statements
- File upload validation (if implemented)

**Access Control:**

- Role-based authorization checks on every page
- Patient data isolation (users see only their data)
- Admin functions restricted to admin role
- Direct URL access prevented without authentication

### 8.5 Appendix E: Future Enhancements

**Potential Features for Future Versions:**

1. Email notifications system
2. SMS notification integration
3. Video consultation capability
4. Prescription management
5. Medical records storage
6. Payment integration
7. Multiple language support
8. Mobile application (iOS/Android)
9. Patient feedback and ratings
10. Analytics and reporting dashboard
11. Calendar integration (Google Calendar, Outlook)
12. Automated appointment reminders
13. Doctor availability management
14. Patient medical history
15. Lab results integration

### 8.6 Appendix F: Testing Requirements

**Test Categories:**

1. **Unit Testing:** Individual function validation
2. **Integration Testing:** Component interaction testing
3. **System Testing:** End-to-end workflow testing
4. **Security Testing:** Penetration testing and vulnerability assessment
5. **Performance Testing:** Load and stress testing
6. **Usability Testing:** User experience evaluation
7. **Compatibility Testing:** Browser and device testing

**Critical Test Scenarios:**

- User registration and login
- Appointment booking workflow
- Status update notifications
- Data validation and error handling
- Session management and security
- Database transactions and integrity
- Concurrent user operations

---

## Document Approval

| Role              | Name               | Signature          | Date             |
| ----------------- | ------------------ | ------------------ | ---------------- |
| Project Manager   | **\*\***\_**\*\*** | **\*\***\_**\*\*** | \***\*\_\_\*\*** |
| Lead Developer    | **\*\***\_**\*\*** | **\*\***\_**\*\*** | \***\*\_\_\*\*** |
| Quality Assurance | **\*\***\_**\*\*** | **\*\***\_**\*\*** | \***\*\_\_\*\*** |
| Stakeholder       | **\*\***\_**\*\*** | **\*\***\_**\*\*** | \***\*\_\_\*\*** |

---

## 9. Maintenance and Updates Log

### Version 1.1 - Bug Fixes and Improvements (February 13, 2026)

This section documents post-release maintenance activities and bug fixes performed on the MediCare Clinic system.

#### 9.1 Defect Corrections

**Defect #1: Favicon Display Issues**

- **Severity:** Low
- **Priority:** Medium
- **Requirement Impact:** NFR-5.2 (User Interface Requirements)
- **Description:** All public-facing HTML pages had incorrect favicon file paths, preventing proper display of the MediCare brand icon in browser tabs
- **Resolution:** Updated favicon paths to correct relative path `assets/images/favicon.svg` across all 9 public HTML files
- **Verification:** Visual inspection confirmed favicon displays correctly on all pages

**Defect #2: Registration Form Submission Failure**

- **Severity:** Critical
- **Priority:** High
- **Requirement Impact:** FR-1.1.1 (Patient Registration)
- **Description:** Register button on signup page was non-functional due to:
  - Malformed HTML attributes with escaped quotes
  - JavaScript runtime error from undefined function call
  - Conflicting event listeners causing form submission failures
- **Resolution:**
  - Corrected HTML attribute syntax
  - Refactored validation flow to eliminate undefined function
  - Removed duplicate event listener
  - Integrated proper AJAX form submission with loading states
- **Verification:** End-to-end testing confirmed successful registration with proper validation and error handling

**Defect #3: Logout Functionality Errors**

- **Severity:** High
- **Priority:** High
- **Requirement Impact:** FR-1.2.2 (Logout Functionality), NFR-5.5 (Security)
- **Description:** Both admin and patient logout functions generated errors:
  - Patient pages referenced non-existent logout file path (404 error)
  - PHP "headers already sent" warnings from redundant header calls
- **Resolution:**
  - Updated patient logout links to correct path `../auth/logout.php`
  - Removed duplicate cache control headers from logout scripts
  - Streamlined session destruction and redirect logic
- **Verification:** Tested logout from all admin and patient dashboard pages, confirmed clean redirects without errors

#### 9.2 Requirements Compliance Status

Post-fix verification confirms all corrected defects now meet their respective requirements:

| Requirement ID | Status      | Notes                                               |
| -------------- | ----------- | --------------------------------------------------- |
| FR-1.1.1       | ✓ Compliant | Patient registration working as specified           |
| FR-1.2.2       | ✓ Compliant | Logout functionality operational for all user types |
| NFR-5.2        | ✓ Compliant | UI elements (favicon) display consistently          |
| NFR-5.5        | ✓ Compliant | Session management and logout secure and functional |

#### 9.3 Testing Summary

**Test Scope:** Regression testing of affected components

- Registration workflow: 5 test cases - All passed
- Logout functionality: 8 test cases - All passed
- UI consistency: 9 pages verified - All passed
- Cross-browser testing: Chrome, Firefox, Edge - All passed

**No new defects introduced**

---

**End of Software Requirements Specification**

_This document is confidential and proprietary. Unauthorized distribution is prohibited._
