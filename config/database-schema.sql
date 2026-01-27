-- =====================================================
-- MediCare Clinic - Complete Database Schema
-- =====================================================
-- This SQL script creates all necessary tables including:
-- - appointments table
-- - users table (patients)
-- - admin table (administrators)
-- - notifications table (for admins)
-- - patient_notifications table (for patients)
-- Run this in phpMyAdmin or MySQL command line
-- =====================================================

-- Create users table (for patients)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create admin table (for administrators)
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- Create notifications table (for admin users)
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    appointment_id VARCHAR(50),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_appointment (appointment_id),
    INDEX idx_read_status (is_read)
);

-- Create patient notifications table (for patient users)
CREATE TABLE IF NOT EXISTS patient_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id VARCHAR(50) NOT NULL,
    patient_name VARCHAR(255) NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_appointment (appointment_id),
    INDEX idx_read_status (is_read)
);

-- Add comments to explain the tables
ALTER TABLE notifications COMMENT 'Stores system notifications, primarily for admin users';
ALTER TABLE patient_notifications COMMENT 'Stores notifications for patients about their appointment status changes';

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO `admin` (`full_name`, `email`, `password`)
VALUES ('Admin', 'admin@hospital.com', '$2y$12$NZMY5ff1cOYntTre7ReZie.FBpj6QGhlsgx6ds0rg9MfaQo/YlWai');

-- Show the table structures for verification
DESCRIBE users;
DESCRIBE admin;
DESCRIBE appointments;
DESCRIBE notifications;
DESCRIBE patient_notifications;
