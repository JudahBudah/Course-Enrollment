-- Check all indexes on subjects table
SHOW INDEX FROM `subjects`;

-- Check table structure
DESCRIBE `subjects`;

-- Check for any unique constraints
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'studentenrollment' 
AND TABLE_NAME = 'subjects';
