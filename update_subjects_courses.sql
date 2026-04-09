-- Update existing subjects to link with courses based on department field

-- Update BSIT subjects (course_id = 10 for BSIT, needs to be added to courses table first)
-- First, add BSIT to courses table if not exists
INSERT INTO courses (college_code, college_name, course_code, course_name) VALUES
('CE', 'College of Engineering', 'BSIT', 'Bachelor of Science in Information Technology')
ON DUPLICATE KEY UPDATE course_code = course_code;

-- Get the course_id for BSIT
SET @bsit_id = (SELECT course_id FROM courses WHERE course_code = 'BSIT' LIMIT 1);

-- Update all BSIT subjects
UPDATE subjects SET course_id = @bsit_id WHERE department = 'BSIT';

-- Get the course_id for BSCpE
SET @bscpe_id = (SELECT course_id FROM courses WHERE course_code = 'BSCpE' LIMIT 1);

-- Update all BSCPE subjects
UPDATE subjects SET course_id = @bscpe_id WHERE department = 'BSCPE';
