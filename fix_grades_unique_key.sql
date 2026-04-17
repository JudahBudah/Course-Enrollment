-- TC024/TC025: ensure grades table has unique key for upsert on finalize
ALTER TABLE `grades`
    ADD UNIQUE KEY IF NOT EXISTS `uq_student_subject` (`student_id`, `subject_id`);
