-- Fix Block 1 and Create Sample BSCpE Classes
-- This script fixes the invalid school year and creates sample classes for BSCpE Year 1

-- Step 1: Fix the invalid school year for block 1
UPDATE blocks 
SET school_year = '2024-2025' 
WHERE block_id = 1;

-- Step 2: Get BSCpE subject IDs for Year 1, 1st Semester
-- (You'll need these IDs to create classes)
SELECT 
    s.subject_id,
    s.subject_code,
    s.subject_name,
    s.units,
    s.year_level,
    s.semester
FROM subjects s
LEFT JOIN courses co ON s.course_id = co.course_id
WHERE co.course_code = 'BSCpE' 
AND s.year_level = 1 
AND s.semester = '1st'
ORDER BY s.subject_code;

-- Step 3: Create sample classes for BSCpE Year 1, 1st Semester subjects
-- Note: Replace subject_id values with actual IDs from the query above
-- Note: Replace faculty_id with actual faculty IDs from your faculty table

-- Example: Create a class for CPE 0111 (adjust subject_id as needed)
-- INSERT INTO classes (subject_id, section, school_year, semester, schedule_day, schedule_time, room, max_students, status, faculty_id)
-- VALUES 
-- (291, 'A', '2024-2025', '1st', 'MWF', '8:00 AM - 9:00 AM', 'Room 101', 40, 'open', 1);

-- To create classes, you can use this template for each subject:
/*
INSERT INTO classes (subject_id, section, school_year, semester, schedule_day, schedule_time, room, max_students, status, faculty_id)
VALUES 
(SUBJECT_ID_HERE, 'A', '2024-2025', '1st', 'MWF', '8:00 AM - 9:00 AM', 'Room 101', 40, 'open', FACULTY_ID_HERE);
*/

-- Step 4: Verify classes were created
SELECT 
    c.class_id,
    c.section,
    c.school_year,
    c.semester,
    c.status,
    s.subject_code,
    s.subject_name,
    co.course_code
FROM classes c
JOIN subjects s ON c.subject_id = s.subject_id
LEFT JOIN courses co ON s.course_id = co.course_id
WHERE co.course_code = 'BSCpE'
AND c.school_year = '2024-2025'
AND c.semester = '1st';

-- Step 5: Verify block 1 can now see these classes
SELECT 
    c.class_id,
    c.section,
    s.subject_code,
    s.subject_name,
    c.schedule_day,
    c.schedule_time
FROM classes c
JOIN subjects s ON c.subject_id = s.subject_id
LEFT JOIN courses co ON s.course_id = co.course_id
WHERE c.class_id NOT IN (
    SELECT class_id FROM block_subjects WHERE block_id = 1
)
AND c.school_year = '2024-2025'
AND c.semester = '1st'
AND c.status = 'open'
AND co.course_code = 'BSCpE'
AND s.year_level = 1
ORDER BY s.subject_code;
