-- TC001 fix: Convert grade_entries from MyISAM to InnoDB.
-- MyISAM does not support STORED generated columns (MySQL 9.x requirement).
ALTER TABLE `grade_entries` ENGINE = InnoDB;
