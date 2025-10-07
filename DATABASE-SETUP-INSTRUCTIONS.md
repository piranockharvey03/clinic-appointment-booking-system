# Database Migration Instructions

## Overview
The appointment system has been migrated from JSON file storage to MySQL database storage for improved security, data integrity, and performance.

## Setup Steps

### 1. Create the Appointments Table

Open **phpMyAdmin** (http://localhost/phpmyadmin) and follow these steps:

1. Select the `medicare` database from the left sidebar
2. Click on the **SQL** tab at the top
3. Copy and paste the contents of `database-schema.sql` into the SQL query box
4. Click **Go** to execute the query

Alternatively, you can run the SQL file directly from the command line:
```bash
mysql -u root -p medicare < database-schema.sql
```

### 2. Verify the Table Creation

After running the SQL script, you should see a new table called `appointments` in your `medicare` database with the following columns:

- `id` (Primary Key, Auto Increment)
- `appointment_id` (Unique identifier)
- `patient_id` (Foreign key reference)
- `patient_name`
- `phone`
- `department`
- `doctor_id`
- `doctor_name`
- `doctor_specialty`
- `doctor_photo`
- `appointment_date`
- `appointment_time`
- `reason`
- `notes`
- `status` (ENUM: pending, approved, rescheduled, canceled, completed)
- `created_at` (Timestamp)
- `updated_at` (Timestamp)

### 3. Database Configuration

The database connection settings are stored in `db-config.php`:

```php
DB_HOST: localhost
DB_USER: root
DB_PASS: (empty)
DB_NAME: medicare
```

If your database credentials are different, update the constants in `db-config.php`.

## Files Modified

The following files have been updated to use database storage:

1. **db-config.php** (NEW) - Database configuration and connection functions
2. **database-schema.sql** (NEW) - SQL script to create the appointments table
3. **submit-booking.php** - Now inserts appointments into the database
4. **admin-appointments.php** - Reads and updates appointments from the database
5. **patient-appointments.php** - Reads and updates appointments from the database
6. **admin-dashboard.php** - Displays statistics from the database
7. **patient-dashboard.php** - Displays patient appointments from the database

## Migration from JSON (Optional)

If you have existing appointments in `data/appointments.json` that you want to migrate to the database, you can:

1. Manually insert the data through phpMyAdmin
2. Create a migration script (PHP) to read the JSON and insert into the database

Example migration script structure:
```php
<?php
require_once 'db-config.php';

$jsonFile = __DIR__ . '/data/appointments.json';
$appointments = json_decode(file_get_contents($jsonFile), true);

$conn = getDBConnection();

foreach ($appointments as $appt) {
    // Insert each appointment into the database
    // ... prepared statement code ...
}

closeDBConnection($conn);
```

## Security Improvements

✅ **SQL Injection Protection**: All queries use prepared statements with parameter binding
✅ **No File System Access**: Appointments are stored in the database, not in publicly accessible JSON files
✅ **Data Integrity**: Database constraints ensure data consistency
✅ **Transaction Support**: Database supports atomic operations
✅ **Better Access Control**: Database-level permissions can be configured
✅ **Audit Trail**: Automatic timestamps track when records are created/updated

## Testing

After setup, test the following functionality:

1. **Book an appointment** - Visit the patient booking page and create a new appointment
2. **View appointments** - Check both admin and patient dashboards
3. **Approve/Cancel** - Test admin actions on appointments
4. **Reschedule** - Test rescheduling functionality
5. **Statistics** - Verify dashboard statistics are accurate

## Troubleshooting

### Connection Error
If you see "Database connection failed", check:
- XAMPP MySQL service is running
- Database credentials in `db-config.php` are correct
- The `medicare` database exists

### Table Not Found
If you see "Table 'medicare.appointments' doesn't exist":
- Run the `database-schema.sql` script in phpMyAdmin
- Verify the table was created successfully

### No Appointments Showing
If appointments aren't displaying:
- Check the browser console for JavaScript errors
- Check PHP error logs in `xampp/php/logs/php_error_log`
- Verify appointments exist in the database using phpMyAdmin

## Backup Recommendation

Always backup your database regularly:
```bash
mysqldump -u root -p medicare > backup_medicare_$(date +%Y%m%d).sql
```

## Next Steps (Optional Enhancements)

- Add patient-specific filtering (filter appointments by logged-in patient ID)
- Implement email notifications for appointment status changes
- Add appointment reminders
- Create admin reports and analytics
- Implement appointment search and filtering
