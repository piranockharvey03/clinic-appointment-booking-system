# MediCare Clinic — Security Reference

**Version**: 2.0.0  
**Last Updated**: March 11, 2026

This document describes the security posture of the MediCare Clinic system: what controls are in place, what was fixed in the v2.0.0 hardening pass, and what to do before deploying to a production environment.

---

## 1. Authentication & Session Management

### What is implemented

| Control                     | Details                                                                        |
| --------------------------- | ------------------------------------------------------------------------------ |
| Password hashing            | `password_hash($password, PASSWORD_DEFAULT)` — bcrypt with auto-generated salt |
| Password verification       | `password_verify()` — constant-time comparison; safe against timing attacks    |
| Session fixation prevention | `session.use_strict_mode = 1` in `config/session-config.php`                   |
| Session cookie flags        | `httponly = true`, `samesite = Lax`                                            |
| Role enforcement            | Every protected page checks `$_SESSION['user_role']` before processing         |
| Inactive doctor lockout     | Doctor login checks `status = 'active'` before allowing access                 |
| Login audit logging         | Doctor logins (success and failure) written to `activity_logs`                 |

### Roles

| Role    | Session value | Table     |
| ------- | ------------- | --------- |
| Patient | `patient`     | `users`   |
| Doctor  | `doctor`      | `doctors` |
| Admin   | `admin`       | `admin`   |

### Session timeout

Session lifetime is set to 24 hours via `session.gc_maxlifetime`. The cookie itself is a browser-session cookie (`lifetime = 0`) — it is destroyed when the browser closes. For stricter timeout requirements, uncomment the `login_time` check in `app/auth/check-session.php`.

### Production recommendation

Set `'secure' => true` in `config/session-config.php` once HTTPS is enabled. This prevents session cookies from being sent over plain HTTP.

---

## 2. SQL Injection Prevention

All database interaction uses MySQLi prepared statements with bound parameters throughout the codebase. No raw string interpolation is used in any query that touches user-supplied data.

**Examples:**

```php
// Patient login
$stmt = $conn->prepare("SELECT id, full_name, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);

// Doctor-scoped appointments
$stmt = $conn->prepare("SELECT * FROM appointments WHERE doctor_id = ? ORDER BY ...");
$stmt->bind_param("s", $doctorId);

// Admin appointment filter
$stmt = $conn->prepare("SELECT * FROM appointments WHERE doctor_id = ? ORDER BY ...");
```

The one legacy exception was `app/includes/feedback.php`, which used a hardcoded inline connection. This was fixed in v2.0.0 to use the centralised `getDBConnection()` from `config/db-config.php`.

---

## 3. Access Control (Broken Access Control — OWASP A01)

### Role-based page guards

Every PHP page that is not public begins with a guard block:

```php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../../public/admin-login.html');
    exit;
}
```

### API endpoint guards

| Endpoint                              | Auth requirement         |
| ------------------------------------- | ------------------------ |
| `get-notifications.php`               | Admin role               |
| `mark-notifications-read.php`         | Admin role               |
| `get-patient-notifications.php`       | Patient role             |
| `mark-patient-notifications-read.php` | Patient role + ownership |
| `backup-database.php`                 | Admin role + POST method |
| `feedback.php`                        | None (public form)       |

### Ownership enforcement — fixed in v2.0.0

**Problem identified:** `app/includes/mark-patient-notifications-read.php` had no session check and no ownership validation. Any unauthenticated request (or any user of any role) could submit a `notification_ids[]` array and silently mark any patient's notifications as read — a broken access control vulnerability (OWASP A01).

**Fix applied:**

1. Added `require_once '../../config/session-config.php'`
2. Added role check: `$_SESSION['user_role'] !== 'patient'` → HTTP 401
3. Added `AND patient_id = ?` to the UPDATE query, bound to `$_SESSION['user_id']`
4. Added `intval()` cast and positive-integer filter on all incoming notification IDs

The result: a patient can only mark their own notifications as read, and unauthenticated requests are rejected before any DB query runs.

### Doctor appointment scoping — fixed in v2.0.0

**Problem identified:** `doctor-appointments.php` and `doctor-dashboard.php` loaded ALL appointments from the database with no `WHERE doctor_id` filter. Any logged-in doctor could see every patient's appointments across the entire clinic.

**Fix applied:** Both files now use a parameterized `WHERE doctor_id = ?` clause bound to `$_SESSION['user_id']`. Doctors only see records assigned to them.

---

## 4. XSS Prevention (OWASP A03)

All user-controlled data is escaped at output time using `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` before being rendered into HTML. PHP variables are never interpolated directly into HTML attributes or tag content without escaping.

Input is validated (length, type, format) on arrival; **output** is always escaped — following the recommended "escape on output, validate on input" model.

---

## 5. Sensitive Data Exposure (OWASP A02)

- Passwords are never stored in plaintext — only bcrypt hashes
- Database credentials are centralised in `config/db-config.php` — not repeated inline (fixed in v2.0.0)
- Error details are written to PHP's error log, never to the HTTP response
- No credentials are included in URL query strings

### Production recommendation

`config/db-config.php` currently uses `root` with no password — acceptable on a local XAMPP development server. Before any production deployment, assign a dedicated MySQL user with minimum privileges (`SELECT`, `INSERT`, `UPDATE`, `DELETE` on the `medicare` database only) and set a strong password.

---

## 6. Security Misconfiguration (OWASP A05)

### Debug and development files removed in v2.0.0

The following files were deleted because they exposed internal system state or could be abused:

| File                                 | Risk                                                                |
| ------------------------------------ | ------------------------------------------------------------------- |
| `app/auth/debug-password-change.php` | **Critical** — logged all POST data including raw passwords to disk |
| `app/admin/test-session.php`         | Exposed full `$_SESSION` dump to any authenticated user             |
| `view-logs.php`                      | Exposed debug log files to any user with a session (no role check)  |
| `clear-log.php`                      | Allowed any session user to delete log files                        |
| `verify-update.php`                  | Exposed database schema info with no role check                     |
| `apply-migration.php`                | One-time migration script left accessible after use                 |
| `app/admin/admin-dashboard.php`      | Dead redirect file with ~150 lines of unreachable code              |

### Backup command security

`backup-database.php` uses `mysqldump` with `--password` on the command line. On Linux/Mac this value would briefly be visible in the system process list. The default XAMPP configuration uses an empty password so there is no secret to expose; however, if a password is added, the backup script should be updated to use a `--defaults-extra-file` approach:

```php
$tmpOptions = tempnam(sys_get_temp_dir(), 'mysqldump_');
file_put_contents($tmpOptions, "[client]\npassword={$dbPass}\n");
chmod($tmpOptions, 0600);
$command = "mysqldump --defaults-extra-file=" . escapeshellarg($tmpOptions) . " ...";
// ... exec() ...
unlink($tmpOptions);
```

---

## 7. CSRF Considerations

The application uses `samesite = Lax` on the session cookie. This provides baseline CSRF protection for top-level navigation POST requests. Sensitive state-changing endpoints (`backup-database.php`, `mark-notifications-read.php`, etc.) also check `$_SERVER['REQUEST_METHOD'] === 'POST'`.

For full CSRF token protection on all forms (e.g., appointment cancellation), consider generating a per-session CSRF token in `session-config.php` and verifying it on every POST.

---

## 8. Rate Limiting

There is no built-in brute-force protection on the login endpoints (`login.php`, `admin-login.php`, `doctor-login.php`). For production, add:

- Account lockout after N failed attempts (track in `activity_logs` or a dedicated table)
- Or configure Apache/Nginx `mod_ratelimit` / fail2ban at the server level

---

## 9. Logging & Monitoring (OWASP A09)

Doctor actions (login, approve, cancel, reschedule, complete appointment) are written to the `activity_logs` table via `logActivity()` in `config/db-config.php`. The admin panel exposes this log.

Admin login events are written to PHP's error log. Consider extending `logActivity()` to cover admin and patient login events for a complete audit trail.

---

## 10. Dependency & Component Risk (OWASP A06)

All frontend dependencies are loaded from public CDNs:

| Library       | Source               |
| ------------- | -------------------- |
| TailwindCSS   | cdn.tailwindcss.com  |
| Feather Icons | unpkg.com            |
| jQuery 3.6.0  | code.jquery.com      |
| Animate.css   | cdnjs.cloudflare.com |

For production, vendor these assets locally (download and serve from `public/assets/`) to avoid:

- CDN outages breaking the UI
- CDN supply-chain attacks injecting malicious JS

---

## 11. HTTPS

The application currently runs on plain HTTP. For any non-development deployment:

1. Install an SSL certificate (free via Let's Encrypt)
2. Configure Apache virtual host to redirect HTTP → HTTPS
3. Set `'secure' => true` in `config/session-config.php`
4. Add `Strict-Transport-Security` header in Apache config

---

## Summary of v2.0.0 Security Changes

| #   | Category                  | Change                                                                                      |
| --- | ------------------------- | ------------------------------------------------------------------------------------------- |
| 1   | Broken Access Control     | `mark-patient-notifications-read.php`: added session auth + ownership `AND patient_id = ?`  |
| 2   | Broken Access Control     | `doctor-appointments.php` + `doctor-dashboard.php`: scoped queries to `WHERE doctor_id = ?` |
| 3   | Security Misconfiguration | Deleted `debug-password-change.php` (logged passwords)                                      |
| 4   | Security Misconfiguration | Deleted `test-session.php` (exposed session dump)                                           |
| 5   | Security Misconfiguration | Deleted `view-logs.php` + `clear-log.php` (weak-auth debug tools)                           |
| 6   | Security Misconfiguration | Deleted `verify-update.php` (no role check, exposed DB info)                                |
| 7   | Security Misconfiguration | Deleted `apply-migration.php` (stale one-time script)                                       |
| 8   | Security Misconfiguration | Deleted `admin-dashboard.php` (dead redirect with unreachable code)                         |
| 9   | Cryptographic / Config    | `feedback.php`: removed hardcoded DB credentials; centralised to `getDBConnection()`        |
