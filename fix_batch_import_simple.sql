-- STEP 1: Check for existing duplicates
-- Run this first to see if you have any duplicates
SELECT 
    subject_code, 
    course_id, 
    COUNT(*) as count,
    GROUP_CONCAT(subject_id) as subject_ids,
    GROUP_CONCAT(subject_name) as subject_names
FROM subjects
GROUP BY subject_code, course_id
HAVING COUNT(*) > 1;

-- STEP 2: Drop the old unique constraint (if it exists)
-- This will allow duplicate subject codes
ALTER TABLE subjects DROP INDEX IF EXISTS subject_code;

-- STEP 3: Add new unique constraint on (subject_code, course_id)
-- This allows same subject_code for different courses
ALTER TABLE subjects ADD UNIQUE KEY unique_subject_course (subject_code, course_id);

-- STEP 4: Verify the constraint was added
SHOW INDEX FROM subjects WHERE Key_name = 'unique_subject_course';

-- STEP 5: Test - Show subjects with same code but different courses
SELECT 
    s.subject_code,
    c.course_code,
    s.subject_name,
    s.department
FROM subjects s
LEFT JOIN courses c ON s.course_id = c.course_id
ORDER BY s.subject_code, c.course_code;
