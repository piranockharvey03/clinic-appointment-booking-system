-- =====================================================
-- Simplified Database Migration Script
-- Adds foreign keys and remaining indexes
-- =====================================================

USE medicare;

-- Add foreign key for appointments.patient_id -> users.id
ALTER TABLE appointments 
ADD CONSTRAINT fk_appointments_patient 
FOREIGN KEY (patient_id) REFERENCES users(id) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- Add foreign key for patient_notifications.patient_id -> users.id
ALTER TABLE patient_notifications 
ADD CONSTRAINT fk_patient_notifications_user 
FOREIGN KEY (patient_id) REFERENCES users(id) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- Add indexes for better performance
CREATE INDEX idx_patient_id ON patient_notifications(patient_id);
CREATE INDEX idx_created_at ON notifications(created_at);
CREATE INDEX idx_created_at ON patient_notifications(created_at);

-- Verify changes
SELECT 'Foreign Keys Added:' AS Status;
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'medicare'
AND REFERENCED_TABLE_NAME IS NOT NULL;
