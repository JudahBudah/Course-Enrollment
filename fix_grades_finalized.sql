-- TC001 finalize grades: add grades_finalized flag to classes table
ALTER TABLE `classes`
    ADD COLUMN `grades_finalized` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`,
    ADD COLUMN `grades_finalized_at` TIMESTAMP NULL DEFAULT NULL AFTER `grades_finalized`;
