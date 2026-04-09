-- Create courses table
CREATE TABLE IF NOT EXISTS courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    college_code VARCHAR(10) NOT NULL,
    college_name VARCHAR(100) NOT NULL,
    course_code VARCHAR(20) NOT NULL,
    course_name VARCHAR(200) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert courses data
INSERT INTO courses (college_code, college_name, course_code, course_name) VALUES
-- College of Engineering
('CE', 'College of Engineering', 'BSChE', 'Bachelor of Science in Chemical Engineering'),
('CE', 'College of Engineering', 'BSCE', 'Bachelor of Science in Civil Engineering'),
('CE', 'College of Engineering', 'BSCE-CM', 'Bachelor of Science in Civil Engineering with Specialization in Construction Management'),
('CE', 'College of Engineering', 'BSCE-SE', 'Bachelor of Science in Civil Engineering with Specialization in Structural Engineering'),
('CE', 'College of Engineering', 'BSCpE', 'Bachelor of Science in Computer Engineering'),
('CE', 'College of Engineering', 'BSEE', 'Bachelor of Science in Electrical Engineering'),
('CE', 'College of Engineering', 'BSECE', 'Bachelor of Science in Electronics Engineering'),
('CE', 'College of Engineering', 'BSMfgE', 'Bachelor of Science in Manufacturing Engineering'),
('CE', 'College of Engineering', 'BSME', 'Bachelor of Science in Mechanical Engineering'),
('CE', 'College of Engineering', 'BSIT', 'Bachelor of Science in Information Technology'),

-- College of Accountancy
('CA', 'College of Accountancy', 'BSA', 'Bachelor of Science in Accountancy'),

-- College of Architecture and Sustainable Built Environment
('CASBE', 'College of Architecture and Sustainable Built Environment', 'BS Arch', 'Bachelor of Science in Architecture'),

-- Graduate School of Law
('GSL', 'Graduate School of Law', 'LL.M.', 'Master of Laws');

-- Remove duplicate courses before adding unique constraint
DELETE c1 FROM courses c1
INNER JOIN courses c2 
WHERE c1.course_id > c2.course_id 
AND c1.course_code = c2.course_code;

-- Add unique constraint to course_code
ALTER TABLE courses ADD UNIQUE KEY unique_course_code (course_code);

-- Check if course_id column already exists in subjects table before adding
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'subjects' 
    AND COLUMN_NAME = 'course_id');

SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE subjects ADD COLUMN course_id INT NULL AFTER subject_id', 
    'SELECT "Column course_id already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add foreign key constraint if not exists
SET @fk_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'subjects' 
    AND CONSTRAINT_NAME = 'subjects_ibfk_1');

SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE subjects ADD CONSTRAINT subjects_ibfk_1 FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE SET NULL', 
    'SELECT "Foreign key already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index if not exists
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'subjects' 
    AND INDEX_NAME = 'idx_subjects_course');

SET @sql = IF(@idx_exists = 0, 
    'CREATE INDEX idx_subjects_course ON subjects(course_id)', 
    'SELECT "Index already exists" AS message');

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
