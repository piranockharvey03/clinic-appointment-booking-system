-- =====================================================
-- MediCare Clinic - Appointments Table Schema
-- =====================================================
-- This SQL script creates the appointments table
-- Run this in phpMyAdmin or MySQL command line
-- =====================================================

-- Create appointments table
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
  `status` ENUM('pending', 'approved', 'rescheduled', 'canceled', 'completed') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_appointment_id` (`appointment_id`),
  KEY `idx_patient_id` (`patient_id`),
  KEY `idx_status` (`status`),
  KEY `idx_appointment_date` (`appointment_date`),
  KEY `idx_doctor_id` (`doctor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Optional: Migrate existing JSON data to database
-- =====================================================
-- If you have existing appointments in appointments.json,
-- you can use the migration script (create separately)
-- or manually import the data
-- =====================================================
