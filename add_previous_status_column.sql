-- Add previous_status column to enrollments table to track status before drop request
ALTER TABLE enrollments ADD COLUMN previous_status VARCHAR(20) DEFAULT NULL AFTER status;
