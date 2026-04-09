-- Fix: Allow Same Subject Code for Different Courses
-- This adds a unique constraint on (subject_code, course_id) combination
-- instead of just subject_code alone

-- Step 1: Check for existing duplicates that would violate the new constraint
SELECT 
    subject_code, 
    course_id, 
    COUNT(*) as count,
    GROUP_CONCAT(subject_id) as subject_ids
FROM subjects
GROUP BY subject_code, course_id
HAVING COUNT(*) > 1;

-- Step 2: Drop existing unique constraint on subject_code alone (if exists)
-- Note: This will error if the index doesn't exist, which is fine
-- ALTER TABLE subjects DROP INDEX subject_code;

-- Step 3: Add unique constraint on (subject_code, course_id) combination
-- This allows same subject_code for different courses
-- First, check if it already exists by trying to add it
ALTER TABLE subjects ADD UNIQUE KEY unique_subject_course (subject_code, course_id);

-- Step 4: Verify the constraint was added
SHOW INDEX FROM subjects WHERE Key_name = 'unique_subject_course';

-- Step 5: Test - This should show subjects with same code but different courses
SELECT 
    s.subject_code,
    c.course_code,
    s.subject_name
FROM subjects s
LEFT JOIN courses c ON s.course_id = c.course_id
WHERE s.subject_code IN (
    SELECT subject_code 
    FROM subjects 
    GROUP BY subject_code 
    HAVING COUNT(*) > 1
)
ORDER BY s.subject_code, c.course_code;
