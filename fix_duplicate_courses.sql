-- Fix duplicate courses before adding unique constraint

-- First, check for duplicates
SELECT course_code, COUNT(*) as count 
FROM courses 
GROUP BY course_code 
HAVING count > 1;

-- Keep only the first occurrence of each course_code and delete duplicates
DELETE c1 FROM courses c1
INNER JOIN courses c2 
WHERE c1.course_id > c2.course_id 
AND c1.course_code = c2.course_code;

-- Now add the unique constraint
ALTER TABLE courses ADD UNIQUE KEY unique_course_code (course_code);
