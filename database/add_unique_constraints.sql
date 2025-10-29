-- Add unique constraints to prevent duplicate emails and phone numbers
ALTER TABLE users
ADD UNIQUE INDEX idx_unique_email (email),
ADD UNIQUE INDEX idx_unique_phone (phone);
