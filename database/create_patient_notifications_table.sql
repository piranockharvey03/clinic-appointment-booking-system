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

-- Add a comment to explain the table
ALTER TABLE patient_notifications COMMENT 'Stores notifications for patients about their appointment status changes';
