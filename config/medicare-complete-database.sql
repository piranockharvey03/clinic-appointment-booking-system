-- =====================================================
-- MediCare Clinic - Complete Database Setup
-- Version: 1.7.0
-- =====================================================
-- This single file creates the entire database from scratch.
-- Run in phpMyAdmin or via: mysql -u root < medicare-complete-database.sql
--
-- Tables created (in dependency order):
--   1. users              — Patient accounts
--   2. admin              — Admin accounts
--   3. doctors            — Doctor profiles
--   4. departments        — Department master list
--   5. doctor_departments — Doctor ↔ Department junction
--   6. specialties        — Specialty master list
--   7. doctor_specialties — Doctor ↔ Specialty junction
--   8. doctor_notifications — Notifications for doctors
--   9. appointments       — Patient appointments
--  10. notifications      — Admin notifications
--  11. patient_notifications — Patient notifications
--  12. activity_logs      — Audit trail for user actions
-- =====================================================

CREATE DATABASE IF NOT EXISTS `medicare`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `medicare`;

-- ─────────────────────────────────────────────────────
-- 1. USERS (Patients)
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `full_name`  VARCHAR(100) NOT NULL,
  `email`      VARCHAR(100) NOT NULL,
  `phone`      VARCHAR(20)  NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────
-- 2. ADMIN
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admin` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `full_name`  VARCHAR(100) NOT NULL,
  `email`      VARCHAR(100) NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Stores system administrators who manage doctors and view reports';

-- ─────────────────────────────────────────────────────
-- 3. DOCTORS
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `doctors` (
  `id`               INT(11)      NOT NULL AUTO_INCREMENT,
  `full_name`        VARCHAR(100) NOT NULL,
  `email`            VARCHAR(100) NOT NULL,
  `phone`            VARCHAR(20)  NOT NULL,
  `password`         VARCHAR(255) NOT NULL,
  `specialty`        VARCHAR(100) NOT NULL,
  `department`       VARCHAR(100) NOT NULL,
  `photo`            VARCHAR(255) DEFAULT NULL,
  `qualification`    VARCHAR(255) DEFAULT NULL,
  `experience_years` INT          DEFAULT 0,
  `status`           ENUM('active','inactive') DEFAULT 'active',
  `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  INDEX `idx_email`       (`email`),
  INDEX `idx_specialty`   (`specialty`),
  INDEX `idx_department`  (`department`),
  INDEX `idx_status`      (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────
-- 4. DEPARTMENTS (master list)
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `departments` (
  `id`          INT          NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `departments` (`name`, `description`) VALUES
('Cardiology Department',              'Heart and cardiovascular system'),
('Dermatology Department',             'Skin, hair, and nail conditions'),
('Emergency Department',               'Urgent and emergency care'),
('Endocrinology Department',           'Hormones and metabolic disorders'),
('General Medicine Department',        'General health for all ages'),
('Gastroenterology Department',        'Digestive system and liver diseases'),
('General Surgery Department',         'Surgical procedures and operations'),
('Geriatrics Department',              'Healthcare for elderly patients'),
('Hematology Department',              'Blood disorders and diseases'),
('Infectious Disease Department',      'Infectious and communicable diseases'),
('Internal Medicine Department',       'Adult medicine and complex diagnoses'),
('Nephrology Department',              'Kidney diseases and dialysis'),
('Neurology Department',               'Brain and nervous system disorders'),
('Obstetrics & Gynecology (OB/GYN)',   'Women\'s health and pregnancy'),
('Oncology Department',                'Cancer diagnosis and treatment'),
('Ophthalmology Department',           'Eye diseases and vision care'),
('Orthopedics Department',             'Bones, joints, and musculoskeletal system'),
('Otolaryngology (ENT) Department',    'Ear, nose, and throat conditions'),
('Pediatrics Department',              'Children\'s health and development'),
('Psychiatry Department',              'Mental health and behavioral disorders'),
('Pulmonology Department',             'Lung and respiratory diseases'),
('Radiology Department',               'Medical imaging and diagnostics'),
('Rheumatology Department',            'Autoimmune and joint diseases'),
('Urology Department',                 'Urinary system and male reproductive health')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- ─────────────────────────────────────────────────────
-- 5. DOCTOR_DEPARTMENTS (junction)
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `doctor_departments` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `doctor_id`  INT          NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_doctor_department` (`doctor_id`, `department`),
  INDEX `idx_doctor`     (`doctor_id`),
  INDEX `idx_department` (`department`),
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Many-to-many: doctors can belong to multiple departments';

-- ─────────────────────────────────────────────────────
-- 6. SPECIALTIES (master list)
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `specialties` (
  `id`          INT          NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `specialties` (`name`, `description`) VALUES
('Anesthesiology',          'Pain management and anesthesia'),
('Cardiology',              'Heart and cardiovascular system diseases'),
('Dermatology',             'Skin, hair, and nail conditions'),
('Emergency Medicine',      'Urgent and emergency care'),
('Endocrinology',           'Hormones and metabolic disorders'),
('Family Medicine',         'General health for all ages'),
('Gastroenterology',        'Digestive system and liver diseases'),
('General Surgery',         'Surgical procedures and operations'),
('Geriatrics',              'Healthcare for elderly patients'),
('Hematology',              'Blood disorders and diseases'),
('Infectious Disease',      'Infectious and communicable diseases'),
('Internal Medicine',       'Adult medicine and complex diagnoses'),
('Nephrology',              'Kidney diseases and dialysis'),
('Neurology',               'Brain and nervous system disorders'),
('Obstetrics & Gynecology', 'Women\'s health and pregnancy'),
('Oncology',                'Cancer diagnosis and treatment'),
('Ophthalmology',           'Eye diseases and vision care'),
('Orthopedics',             'Bones, joints, and musculoskeletal system'),
('Otolaryngology (ENT)',    'Ear, nose, and throat conditions'),
('Pain Management',         'Chronic pain treatment and management'),
('Pathology',               'Disease diagnosis through lab analysis'),
('Pediatrics',              'Children\'s health and development'),
('Physical Medicine',       'Rehabilitation and physical therapy'),
('Preventive Medicine',     'Disease prevention and health promotion'),
('Psychiatry',              'Mental health and behavioral disorders'),
('Pulmonology',             'Lung and respiratory diseases'),
('Radiology',               'Medical imaging and diagnostics'),
('Rheumatology',            'Autoimmune and joint diseases'),
('Sports Medicine',         'Athletic injuries and performance'),
('Urology',                 'Urinary system and male reproductive health')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- ─────────────────────────────────────────────────────
-- 7. DOCTOR_SPECIALTIES (junction)
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `doctor_specialties` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `doctor_id`  INT          NOT NULL,
  `specialty`  VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_doctor_specialty` (`doctor_id`, `specialty`),
  INDEX `idx_doctor`    (`doctor_id`),
  INDEX `idx_specialty` (`specialty`),
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Many-to-many: doctors can have multiple additional specialties';

-- ─────────────────────────────────────────────────────
-- 8. DOCTOR_NOTIFICATIONS
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `doctor_notifications` (
  `id`             INT          NOT NULL AUTO_INCREMENT,
  `doctor_id`      INT(11)      NOT NULL,
  `type`           VARCHAR(50)  NOT NULL,
  `message`        TEXT         NOT NULL,
  `appointment_id` VARCHAR(50),
  `is_read`        BOOLEAN      DEFAULT FALSE,
  `created_at`     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `read_at`        TIMESTAMP    NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_doctor_id`   (`doctor_id`),
  INDEX `idx_appointment` (`appointment_id`),
  INDEX `idx_read_status` (`is_read`),
  INDEX `idx_created_at`  (`created_at`),
  CONSTRAINT `fk_doctor_notifications_doctor` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────
-- 9. APPOINTMENTS
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `appointments` (
  `id`               INT(11)      NOT NULL AUTO_INCREMENT,
  `appointment_id`   VARCHAR(50)  NOT NULL UNIQUE,
  `patient_id`       INT(11)      DEFAULT NULL,
  `patient_name`     VARCHAR(100) NOT NULL,
  `phone`            VARCHAR(20)  DEFAULT NULL,
  `department`       VARCHAR(100) NOT NULL,
  `doctor_id`        VARCHAR(50)  NOT NULL,
  `doctor_name`      VARCHAR(100) NOT NULL,
  `doctor_specialty` VARCHAR(100) NOT NULL,
  `doctor_photo`     VARCHAR(255) DEFAULT NULL,
  `appointment_date` DATE         NOT NULL,
  `appointment_time` TIME         NOT NULL,
  `booking_slot_key` VARCHAR(191) DEFAULT NULL,
  `reason`           TEXT         DEFAULT NULL,
  `notes`            TEXT         DEFAULT NULL,
  `status`           ENUM('pending','approved','rescheduled','canceled','completed') NOT NULL DEFAULT 'pending',
  `cancel_reason`    VARCHAR(255) DEFAULT NULL,
  `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `checked_in_at`    DATETIME     DEFAULT NULL,
  `checkin_token`    VARCHAR(8)   DEFAULT NULL,
  `checked_in_by`    VARCHAR(50)  DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_appointment_id`   (`appointment_id`),
  KEY `idx_patient_id`       (`patient_id`),
  KEY `idx_status`           (`status`),
  KEY `idx_appointment_date` (`appointment_date`),
  KEY `idx_doctor_id`        (`doctor_id`),
  KEY `idx_doctor_slot`      (`doctor_id`, `appointment_date`, `appointment_time`, `status`),
  UNIQUE KEY `uq_appointments_booking_slot_key` (`booking_slot_key`),
  CONSTRAINT `fk_appointments_patient` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────
-- 10. NOTIFICATIONS (Admin)
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `notifications` (
  `id`             INT         NOT NULL AUTO_INCREMENT,
  `type`           VARCHAR(50) NOT NULL,
  `message`        TEXT        NOT NULL,
  `appointment_id` VARCHAR(50),
  `is_read`        BOOLEAN     DEFAULT FALSE,
  `created_at`     TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
  `read_at`        TIMESTAMP   NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_appointment` (`appointment_id`),
  INDEX `idx_read_status` (`is_read`),
  INDEX `idx_created_at`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Stores system notifications, primarily for admin users';

-- ─────────────────────────────────────────────────────
-- 11. PATIENT_NOTIFICATIONS
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `patient_notifications` (
  `id`                INT(11)      NOT NULL AUTO_INCREMENT,
  `patient_id`        INT(11)      NOT NULL,
  `appointment_id`    VARCHAR(50)  NOT NULL,
  `patient_name`      VARCHAR(255) NOT NULL,
  `notification_type` VARCHAR(50)  NOT NULL,
  `message`           TEXT         NOT NULL,
  `is_read`           BOOLEAN      DEFAULT FALSE,
  `created_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `read_at`           TIMESTAMP    NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_patient_id`  (`patient_id`),
  INDEX `idx_appointment` (`appointment_id`),
  INDEX `idx_read_status` (`is_read`),
  INDEX `idx_created_at`  (`created_at`),
  CONSTRAINT `fk_patient_notifications_user` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Stores notifications for patients about their appointment status changes';

-- ─────────────────────────────────────────────────────
-- 12. ACTIVITY_LOGS
-- ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`     INT(11)      NOT NULL DEFAULT 0,
  `user_name`   VARCHAR(100) NOT NULL,
  `user_role`   VARCHAR(20)  NOT NULL DEFAULT 'doctor',
  `action`      VARCHAR(100) NOT NULL,
  `description` TEXT         DEFAULT NULL,
  `ip_address`  VARCHAR(45)  DEFAULT NULL,
  `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_user_role` (`user_role`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Audit trail for doctor (and other user) actions';

-- ─────────────────────────────────────────────────────
-- DEFAULT DATA
-- ─────────────────────────────────────────────────────

-- Default admin account (password: admin123)
INSERT IGNORE INTO `admin` (`full_name`, `email`, `password`)
VALUES ('Admin', 'admin@hospital.com', '$2y$12$NZMY5ff1cOYntTre7ReZie.FBpj6QGhlsgx6ds0rg9MfaQo/YlWai');

-- =====================================================
-- APPOINTMENT SLOT INTEGRITY (existing DB-safe)
-- This keeps active slots unique for pending/approved/rescheduled.
-- =====================================================

SELECT `doctor_id`, `appointment_date`, `appointment_time`, COUNT(*) AS `active_appointments`
FROM `appointments`
WHERE `status` IN ('pending', 'approved', 'rescheduled')
GROUP BY `doctor_id`, `appointment_date`, `appointment_time`
HAVING COUNT(*) > 1;

SET @schema_name = DATABASE();

SET @add_booking_slot_column = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `appointments` ADD COLUMN `booking_slot_key` VARCHAR(191) DEFAULT NULL AFTER `appointment_time`',
    'SELECT ''booking_slot_key already exists'''
  )
  FROM `INFORMATION_SCHEMA`.`COLUMNS`
  WHERE `TABLE_SCHEMA` = @schema_name
    AND `TABLE_NAME` = 'appointments'
    AND `COLUMN_NAME` = 'booking_slot_key'
);
PREPARE stmt FROM @add_booking_slot_column;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `appointments`
SET `booking_slot_key` = CASE
  WHEN `status` IN ('pending', 'approved', 'rescheduled')
    THEN CONCAT(`doctor_id`, '|', DATE_FORMAT(`appointment_date`, '%Y-%m-%d'), '|', TIME_FORMAT(`appointment_time`, '%H:%i:%s'))
  ELSE NULL
END;

SET @add_doctor_slot_index = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `appointments` ADD KEY `idx_doctor_slot` (`doctor_id`, `appointment_date`, `appointment_time`, `status`)',
    'SELECT ''idx_doctor_slot already exists'''
  )
  FROM `INFORMATION_SCHEMA`.`STATISTICS`
  WHERE `TABLE_SCHEMA` = @schema_name
    AND `TABLE_NAME` = 'appointments'
    AND `INDEX_NAME` = 'idx_doctor_slot'
);
PREPARE stmt FROM @add_doctor_slot_index;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_booking_slot_unique = (
  SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE `appointments` ADD UNIQUE KEY `uq_appointments_booking_slot_key` (`booking_slot_key`)',
    'SELECT ''uq_appointments_booking_slot_key already exists'''
  )
  FROM `INFORMATION_SCHEMA`.`STATISTICS`
  WHERE `TABLE_SCHEMA` = @schema_name
    AND `TABLE_NAME` = 'appointments'
    AND `INDEX_NAME` = 'uq_appointments_booking_slot_key'
);
PREPARE stmt FROM @add_booking_slot_unique;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- SETUP COMPLETE
-- =====================================================
-- Default credentials:
--   Admin:   admin@hospital.com / admin123
--
-- After setup:
--   1. Log in as admin at /public/admin-login.html
--   2. Add doctors via Admin > Manage Doctors
--   3. Patients register at /public/register.html
-- =====================================================
