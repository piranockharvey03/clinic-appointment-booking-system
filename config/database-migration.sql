-- =====================================================
-- Database Migration Script
-- Updates existing tables to match new schema
-- =====================================================
-- This script adds foreign keys, indexes, and missing columns
-- Run this AFTER the initial database-schema.sql
-- =====================================================

USE medicare;

-- Add patient_id column to patient_notifications if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = 'patient_notifications';
SET @columnname = 'patient_id';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname
     AND TABLE_NAME = @tablename
     AND COLUMN_NAME = @columnname
  ) > 0,
  "SELECT 'Column patient_id already exists in patient_notifications' AS ''",
  "ALTER TABLE patient_notifications ADD COLUMN patient_id INT(11) NULL AFTER id"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Update existing patient_notifications to link patient_id from appointments
UPDATE patient_notifications pn
INNER JOIN appointments a ON pn.appointment_id = a.appointment_id
SET pn.patient_id = a.patient_id
WHERE pn.patient_id IS NULL;

-- Add index on patient_id if it doesn't exist
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'patient_notifications'
       AND INDEX_NAME = 'idx_patient_id') > 0,
    "SELECT 'Index idx_patient_id already exists' AS ''",
    "CREATE INDEX idx_patient_id ON patient_notifications(patient_id)"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index on created_at for notifications if it doesn't exist
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'notifications'
       AND INDEX_NAME = 'idx_created_at') > 0,
    "SELECT 'Index idx_created_at already exists' AS ''",
    "CREATE INDEX idx_created_at ON notifications(created_at)"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index on created_at for patient_notifications if it doesn't exist
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'patient_notifications'
       AND INDEX_NAME = 'idx_created_at') > 0,
    "SELECT 'Index idx_created_at already exists' AS ''",
    "CREATE INDEX idx_created_at ON patient_notifications(created_at)"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index on email for users if it doesn't exist
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'users'
       AND INDEX_NAME = 'idx_email') > 0,
    "SELECT 'Index idx_email already exists on users' AS ''",
    "CREATE INDEX idx_email ON users(email)"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index on email for admin if it doesn't exist
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'admin'
       AND INDEX_NAME = 'idx_email') > 0,
    "SELECT 'Index idx_email already exists on admin' AS ''",
    "CREATE INDEX idx_email ON admin(email)"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraint for appointments.patient_id if it doesn't exist
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'appointments'
       AND CONSTRAINT_NAME = 'fk_appointments_patient') > 0,
    "SELECT 'Foreign key fk_appointments_patient already exists' AS ''",
    "ALTER TABLE appointments ADD CONSTRAINT fk_appointments_patient 
     FOREIGN KEY (patient_id) REFERENCES users(id) 
     ON DELETE SET NULL ON UPDATE CASCADE"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraint for patient_notifications.patient_id if it doesn't exist
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'patient_notifications'
       AND CONSTRAINT_NAME = 'fk_patient_notifications_user') > 0,
    "SELECT 'Foreign key fk_patient_notifications_user already exists' AS ''",
    "ALTER TABLE patient_notifications ADD CONSTRAINT fk_patient_notifications_user 
     FOREIGN KEY (patient_id) REFERENCES users(id) 
     ON DELETE CASCADE ON UPDATE CASCADE"
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Show summary of changes
SELECT 'Migration completed successfully!' AS Status;
SELECT 'Verification - Foreign Keys:' AS '';
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND REFERENCED_TABLE_NAME IS NOT NULL;

SELECT 'Verification - Indexes:' AS '';
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as COLUMNS
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND INDEX_NAME != 'PRIMARY'
GROUP BY TABLE_NAME, INDEX_NAME
ORDER BY TABLE_NAME, INDEX_NAME;
