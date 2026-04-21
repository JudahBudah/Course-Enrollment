CREATE TABLE IF NOT EXISTS `admin_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int DEFAULT NULL,
  `admin_username` varchar(50) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity` varchar(50) DEFAULT NULL,
  `detail` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
