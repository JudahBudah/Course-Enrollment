-- Verify the unique constraint exists and is correct
SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX,
    NON_UNIQUE
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'studentenrollment' 
AND TABLE_NAME = 'subjects'
AND INDEX_NAME = 'unique_subject_course'
ORDER BY SEQ_IN_INDEX;

-- Check if old subject_code constraint still exists
SELECT 
    INDEX_NAME,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = 'studentenrollment' 
AND TABLE_NAME = 'subjects'
AND COLUMN_NAME = 'subject_code';

-- Test: Try to find subjects with same code but different courses
SELECT 
    s.subject_code,
    s.subject_name,
    s.course_id,
    c.course_code
FROM subjects s
LEFT JOIN courses c ON s.course_id = c.course_id
WHERE s.subject_code IN (
    SELECT subject_code 
    FROM subjects 
    GROUP BY subject_code 
    HAVING COUNT(*) > 1
)
ORDER BY s.subject_code, c.course_code;
