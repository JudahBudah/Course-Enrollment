-- Fix batch import subject issue: Allow same subject code for different courses
-- This migration adds course_id column and updates the unique constraint

-- Step 1: Check and add course_id column if it doesn't exist
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'subjects' 
    AND COLUMN_NAME = 'course_id');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE subjects ADD COLUMN course_id INT NULL AFTER subject_id, ADD INDEX idx_course_id (course_id)', 
    'SELECT "Column course_id already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 2: Check for existing duplicates that would violate the new constraint
SELECT 
    subject_code, 
    course_id, 
    COUNT(*) as count,
    GROUP_CONCAT(subject_id) as subject_ids,
    GROUP_CONCAT(subject_name) as subject_names
FROM subjects
GROUP BY subject_code, course_id
HAVING COUNT(*) > 1;

-- Step 3: Drop the old unique constraint on subject_code alone
ALTER TABLE subjects DROP INDEX IF EXISTS subject_code;

-- Step 4: Add new unique constraint on (subject_code, course_id) combination
-- This allows same subject_code for different courses but prevents duplicates within same course
ALTER TABLE subjects ADD UNIQUE KEY unique_subject_course (subject_code, course_id);

-- Step 5: Verify the new constraint
SHOW INDEX FROM subjects WHERE Key_name = 'unique_subject_course';

-- Step 6: Display subjects with same code but different courses (should work now)
SELECT 
    s.subject_code,
    c.course_code,
    s.subject_name,
    s.department
FROM subjects s
LEFT JOIN courses c ON s.course_id = c.course_id
WHERE s.subject_code IN (
    SELECT subject_code 
    FROM subjects 
    GROUP BY subject_code 
    HAVING COUNT(DISTINCT course_id) > 1
)
ORDER BY s.subject_code, c.course_code;
