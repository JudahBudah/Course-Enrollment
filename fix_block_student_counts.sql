-- Fix TC010: Recalculate accurate student counts for all blocks
-- This script corrects any discrepancies between current_students and actual enrolled students

-- Update current_students to match actual count from students table
UPDATE blocks b
SET current_students = (
    SELECT COUNT(*) 
    FROM students s 
    WHERE s.block_id = b.block_id
);

-- Verify the fix
SELECT 
    b.block_id,
    b.block_name,
    b.course,
    b.year_level,
    b.current_students AS stored_count,
    (SELECT COUNT(*) FROM students s WHERE s.block_id = b.block_id) AS actual_count,
    CASE 
        WHEN b.current_students = (SELECT COUNT(*) FROM students s WHERE s.block_id = b.block_id) 
        THEN 'OK' 
        ELSE 'MISMATCH' 
    END AS status
FROM blocks b
ORDER BY b.course, b.year_level, b.block_name;
