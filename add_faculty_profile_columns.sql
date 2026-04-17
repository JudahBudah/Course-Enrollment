-- TC026/TC027/TC028: add emergency contact and educational background columns to faculty table
ALTER TABLE `faculty`
    ADD COLUMN IF NOT EXISTS `emergency_name`         VARCHAR(150) DEFAULT NULL AFTER `mailing_zip_code`,
    ADD COLUMN IF NOT EXISTS `emergency_relationship` VARCHAR(100) DEFAULT NULL AFTER `emergency_name`,
    ADD COLUMN IF NOT EXISTS `emergency_phone`        VARCHAR(20)  DEFAULT NULL AFTER `emergency_relationship`,
    ADD COLUMN IF NOT EXISTS `emergency_address`      VARCHAR(255) DEFAULT NULL AFTER `emergency_phone`,
    ADD COLUMN IF NOT EXISTS `highest_education`      VARCHAR(150) DEFAULT NULL AFTER `emergency_address`,
    ADD COLUMN IF NOT EXISTS `degree`                 VARCHAR(150) DEFAULT NULL AFTER `highest_education`,
    ADD COLUMN IF NOT EXISTS `school`                 VARCHAR(200) DEFAULT NULL AFTER `degree`,
    ADD COLUMN IF NOT EXISTS `year_graduated`         VARCHAR(10)  DEFAULT NULL AFTER `school`;
