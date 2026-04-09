-- Migration: Add program/college info columns to courses table
-- MySQL-compatible version (no IF NOT EXISTS on ADD COLUMN)

ALTER TABLE `courses`
    ADD COLUMN `curriculum_url`       VARCHAR(500)  NULL AFTER `college_name`,
    ADD COLUMN `description`          TEXT          NULL AFTER `curriculum_url`,
    ADD COLUMN `program_objectives`   TEXT          NULL AFTER `description`,
    ADD COLUMN `career_opportunities` TEXT          NULL AFTER `program_objectives`,
    ADD COLUMN `college_description`  TEXT          NULL AFTER `career_opportunities`,
    ADD COLUMN `college_history`      TEXT          NULL AFTER `college_description`,
    ADD COLUMN `college_vision`       TEXT          NULL AFTER `college_history`,
    ADD COLUMN `college_mission`      TEXT          NULL AFTER `college_vision`,
    ADD COLUMN `college_objectives`   TEXT          NULL AFTER `college_mission`,
    ADD COLUMN `college_location`     VARCHAR(255)  NULL AFTER `college_objectives`,
    ADD COLUMN `college_local_number` VARCHAR(100)  NULL AFTER `college_location`;

-- Update BSCpE with its known curriculum URL
UPDATE `courses`
SET `curriculum_url` = 'https://web13.plm.edu.ph/media/courses/Bachelor_of_Science_in_Computer_Engineering.pdf'
WHERE `course_code` = 'BSCpE';
