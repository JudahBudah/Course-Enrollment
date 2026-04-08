-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Mar 30, 2026 at 04:58 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `studentenrollment`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'admin',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `email`, `password`, `role`, `created_at`) VALUES
(2, 'admin2', 'admin2@plm.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-03-12 03:27:30');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE IF NOT EXISTS `announcements` (
  `announcement_id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int DEFAULT NULL COMMENT 'Foreign key to admins table - who posted it',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `media` json DEFAULT NULL,
  `target_audience` enum('all','students','applicants','faculty') DEFAULT 'all',
  `priority` enum('normal','important','urgent') DEFAULT 'normal',
  `status` enum('active','archived') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`announcement_id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `admin_id`, `title`, `message`, `media`, `target_audience`, `priority`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Enrollment Period Extended', 'The enrollment period for 2nd Semester AY 2024-2025 has been extended until January 31, 2025. All students are encouraged to complete their enrollment before the deadline.', '[{\"file\": \"ann_69c9f0333df712.62928093.png\", \"type\": \"image\"}]', 'all', 'important', 'active', '2026-03-09 06:47:19', '2026-03-30 03:38:27'),
(2, NULL, 'Midterm Examination Schedule', 'Midterm exams will be held from February 10-14, 2025. Check your class schedules for specific dates and times. Please prepare accordingly.', NULL, 'students', 'normal', 'active', '2026-03-06 06:47:19', '2026-03-11 06:47:19'),
(3, NULL, 'System Maintenance Notice', 'The student portal will undergo maintenance on January 20, 2025 from 12:00 AM to 6:00 AM. The system will be temporarily unavailable during this period.', NULL, 'all', 'urgent', 'active', '2026-03-04 06:47:19', '2026-03-11 06:47:19'),
(4, NULL, 'Admission Test Schedule', 'The admission test for incoming freshmen will be held on February 5, 2025. Applicants will receive their test schedules via email.', NULL, 'applicants', 'important', 'active', '2026-03-01 06:47:19', '2026-03-11 06:47:19');

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

DROP TABLE IF EXISTS `applicants`;
CREATE TABLE IF NOT EXISTS `applicants` (
  `applicant_id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `lrn` varchar(12) DEFAULT NULL,
  `first_choice` varchar(200) DEFAULT NULL,
  `second_choice` varchar(200) DEFAULT NULL,
  `third_choice` varchar(200) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `married_name` varchar(100) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `place_of_birth` varchar(200) DEFAULT NULL,
  `civil_status` varchar(20) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `disability` varchar(200) DEFAULT NULL,
  `perm_region` varchar(100) DEFAULT NULL,
  `perm_province` varchar(100) DEFAULT NULL,
  `perm_municipality` varchar(100) DEFAULT NULL,
  `perm_barangay` varchar(100) DEFAULT NULL,
  `perm_address` text,
  `perm_zipcode` varchar(10) DEFAULT NULL,
  `mail_region` varchar(100) DEFAULT NULL,
  `mail_province` varchar(100) DEFAULT NULL,
  `mail_municipality` varchar(100) DEFAULT NULL,
  `mail_barangay` varchar(100) DEFAULT NULL,
  `mail_address` text,
  `mail_zipcode` varchar(10) DEFAULT NULL,
  `application_status` varchar(50) DEFAULT 'incomplete',
  `documents_submitted` tinyint(1) DEFAULT '0',
  `exam_scheduled` tinyint(1) DEFAULT '0',
  `exam_date` date DEFAULT NULL,
  `exam_time` varchar(50) DEFAULT NULL,
  `exam_venue` varchar(200) DEFAULT NULL,
  `exam_room` varchar(50) DEFAULT NULL,
  `exam_taken` tinyint(1) DEFAULT '0',
  `exam_result` varchar(20) DEFAULT NULL,
  `exam_score` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `exam_schedule_id` int DEFAULT NULL,
  `exam_notified` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`applicant_id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_lrn` (`lrn`),
  KEY `idx_application_status` (`application_status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `applicants`
--

INSERT INTO `applicants` (`applicant_id`, `email`, `password`, `lrn`, `first_choice`, `second_choice`, `third_choice`, `last_name`, `first_name`, `middle_name`, `suffix`, `married_name`, `birthdate`, `nationality`, `place_of_birth`, `civil_status`, `contact_number`, `religion`, `gender`, `disability`, `perm_region`, `perm_province`, `perm_municipality`, `perm_barangay`, `perm_address`, `perm_zipcode`, `mail_region`, `mail_province`, `mail_municipality`, `mail_barangay`, `mail_address`, `mail_zipcode`, `application_status`, `documents_submitted`, `exam_scheduled`, `exam_date`, `exam_time`, `exam_venue`, `exam_room`, `exam_taken`, `exam_result`, `exam_score`, `created_at`, `updated_at`, `exam_schedule_id`, `exam_notified`) VALUES
(1, 'vnoir17@gmail.com', '$2y$10$XWXT31AObvg0Q1DnXjd2EO2u99ctm6bpgMvIvNcA8/sHju2akx296', '123456789123', 'BS Computer Science', 'BS Information Technology', 'BS Business Administration', 'Muncada', 'John Louie', 'Lopez', '', '', '2005-11-17', 'Filipino', 'Manila', 'single', '09543447352', 'Roman Catholic', 'male', 'Madami', 'NCR', 'Metro Manila', 'Manila', '903', 'B1-401 Jaime Cardinal Sin Village', '1009', 'NCR', 'Metro Manila', 'Manila', '903', 'B1-401 Jaime Cardinal Sin Village', '1009', 'enrolled', 1, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-11 06:02:11', '2026-03-27 04:02:32', NULL, 0),
(2, '124@gmail.com', '$2y$10$KMppsxxZUlKrSE74rS6qvu.iCxO9HbLfwso3emP5.jVoVoAUKif0S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'approved', 0, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-27 12:02:16', '2026-03-30 04:55:54', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `blocks`
--

DROP TABLE IF EXISTS `blocks`;
CREATE TABLE IF NOT EXISTS `blocks` (
  `block_id` int NOT NULL AUTO_INCREMENT,
  `block_name` varchar(50) NOT NULL COMMENT 'e.g., 1A, 1B, 2A, 2B',
  `course` varchar(100) NOT NULL COMMENT 'e.g., BS Computer Science, BS Information Technology',
  `year_level` enum('1','2','3','4') NOT NULL,
  `semester` enum('1st','2nd','summer') NOT NULL,
  `school_year` varchar(20) NOT NULL COMMENT 'e.g., 2024-2025',
  `max_students` int DEFAULT '40' COMMENT 'Maximum students per block',
  `current_students` int DEFAULT '0' COMMENT 'Current number of students',
  `status` enum('active','inactive','full') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`block_id`),
  UNIQUE KEY `unique_block` (`block_name`,`course`,`year_level`,`semester`,`school_year`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `blocks`
--

INSERT INTO `blocks` (`block_id`, `block_name`, `course`, `year_level`, `semester`, `school_year`, `max_students`, `current_students`, `status`, `created_at`, `updated_at`) VALUES
(1, 'panget', 'BS Computer Science', '1', '1st', '2313123123', 40, 2, 'active', '2026-03-12 02:43:43', '2026-03-27 06:13:55');

-- --------------------------------------------------------

--
-- Table structure for table `block_subjects`
--

DROP TABLE IF EXISTS `block_subjects`;
CREATE TABLE IF NOT EXISTS `block_subjects` (
  `block_subject_id` int NOT NULL AUTO_INCREMENT,
  `block_id` int NOT NULL COMMENT 'Foreign key to blocks table',
  `class_id` int NOT NULL COMMENT 'Foreign key to classes table',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`block_subject_id`),
  UNIQUE KEY `unique_block_class` (`block_id`,`class_id`),
  KEY `block_id` (`block_id`),
  KEY `class_id` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

DROP TABLE IF EXISTS `calendar_events`;
CREATE TABLE IF NOT EXISTS `calendar_events` (
  `event_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `event_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `event_time` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT '#8C1C24',
  `audience` enum('all','students','faculty','applicants') DEFAULT 'all',
  `image` varchar(255) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`event_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `calendar_events`
--

INSERT INTO `calendar_events` (`event_id`, `title`, `description`, `event_date`, `end_date`, `event_time`, `color`, `audience`, `image`, `created_by`, `created_at`) VALUES
(1, 'Deadline ni Kevin', 'Ngayon daw', '2026-03-30', '2026-03-30', '', '#0B1F5B', 'all', 'event_69c9fc2ea666c9.63673763.jpg', 2, '2026-03-30 04:29:34'),
(2, 'Tiktok Paylater ko', 'Wala pa kong pera', '2026-03-30', NULL, '', '#8C1C24', 'all', 'event_69c9fe82cc8264.65426342.jpg', 2, '2026-03-30 04:39:30');

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
CREATE TABLE IF NOT EXISTS `classes` (
  `class_id` int NOT NULL AUTO_INCREMENT,
  `subject_id` int NOT NULL COMMENT 'Foreign key to subjects table',
  `faculty_id` int DEFAULT NULL COMMENT 'Foreign key to faculty table',
  `section` varchar(20) NOT NULL COMMENT 'e.g., A, B, C, 1A, 2B',
  `school_year` varchar(20) NOT NULL COMMENT 'e.g., 2024-2025',
  `semester` enum('1st','2nd','summer') NOT NULL,
  `schedule_day` varchar(50) DEFAULT NULL COMMENT 'e.g., Monday, Tuesday, MW, TTH',
  `schedule_time` varchar(50) DEFAULT NULL COMMENT 'e.g., 8:00 AM - 10:00 AM',
  `room` varchar(50) DEFAULT NULL COMMENT 'Room number or location',
  `max_slots` int DEFAULT '40' COMMENT 'Maximum number of students',
  `enrolled_count` int DEFAULT '0' COMMENT 'Current number of enrolled students',
  `status` enum('open','closed','cancelled') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`class_id`),
  KEY `subject_id` (`subject_id`),
  KEY `faculty_id` (`faculty_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`class_id`, `subject_id`, `faculty_id`, `section`, `school_year`, `semester`, `schedule_day`, `schedule_time`, `room`, `max_slots`, `enrolled_count`, `status`, `created_at`, `updated_at`) VALUES
(1, 64, 4, 'BSCpE 1-A', '2024-2025', '1st', 'MWF', '8:00AM - 10:00AM', '', 40, 1, 'open', '2026-03-27 12:17:28', '2026-03-28 14:22:54'),
(2, 11, 3, 'BSCpE 1-A', '2024-2025', '1st', 'TF', '12:00PM - 3:00PM', '', 40, 1, 'open', '2026-03-28 14:17:48', '2026-03-28 14:33:47');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

DROP TABLE IF EXISTS `enrollments`;
CREATE TABLE IF NOT EXISTS `enrollments` (
  `enrollment_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `class_id` int NOT NULL COMMENT 'Foreign key to classes table',
  `school_year` varchar(20) DEFAULT NULL,
  `semester` int DEFAULT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('reserved','confirmed','ongoing','drop_requested','dropped','completed') DEFAULT 'reserved',
  `grade` decimal(4,2) DEFAULT NULL,
  `remarks` text,
  PRIMARY KEY (`enrollment_id`),
  KEY `student_id` (`student_id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`enrollment_id`, `student_id`, `class_id`, `school_year`, `semester`, `enrollment_date`, `status`, `grade`, `remarks`) VALUES
(1, 1, 1, '2024-2025', 1, '2026-03-27 12:25:09', 'dropped', NULL, NULL),
(2, 2, 1, '2024-2025', 1, '2026-03-27 12:27:41', 'ongoing', NULL, NULL),
(3, 1, 2, '2024-2025', 1, '2026-03-28 14:18:02', 'dropped', NULL, NULL),
(4, 1, 2, '2024-2025', 1, '2026-03-28 14:18:13', 'confirmed', NULL, NULL),
(5, 1, 2, '2024-2025', 1, '2026-03-28 14:18:32', 'dropped', NULL, NULL),
(6, 2, 2, '2024-2025', 1, '2026-03-28 14:26:44', 'dropped', NULL, NULL),
(7, 2, 2, '2024-2025', 1, '2026-03-28 14:26:51', 'dropped', NULL, NULL),
(8, 2, 2, '2024-2025', 1, '2026-03-28 14:28:55', 'reserved', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `exam_schedules`
--

DROP TABLE IF EXISTS `exam_schedules`;
CREATE TABLE IF NOT EXISTS `exam_schedules` (
  `schedule_id` int NOT NULL AUTO_INCREMENT,
  `exam_date` date NOT NULL,
  `exam_time` varchar(50) NOT NULL,
  `location` varchar(255) NOT NULL,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`schedule_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `exam_schedules`
--

INSERT INTO `exam_schedules` (`schedule_id`, `exam_date`, `exam_time`, `location`, `notes`, `created_by`, `created_at`) VALUES
(1, '2026-03-16', '5:00 AM', 'doon lang', 'punta ka na', 2, '2026-03-30 04:55:54');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

DROP TABLE IF EXISTS `faculty`;
CREATE TABLE IF NOT EXISTS `faculty` (
  `faculty_id` int NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL COMMENT 'e.g., Professor, Associate Professor, Instructor',
  `specialization` varchar(255) DEFAULT NULL,
  `employment_status` enum('full-time','part-time','contractual') DEFAULT 'full-time',
  `status` enum('active','inactive','on-leave') DEFAULT 'active',
  `date_hired` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `suffix_name` varchar(20) DEFAULT NULL,
  `college` varchar(100) DEFAULT NULL,
  `personal_email` varchar(150) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `place_of_birth` varchar(150) DEFAULT NULL,
  `sex` enum('Male','Female','Other') DEFAULT NULL,
  `civil_status` enum('Single','Married','Divorced','Widowed','Other') DEFAULT NULL,
  `religion` varchar(100) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `disability` varchar(255) DEFAULT NULL,
  `permanent_region` varchar(100) DEFAULT NULL,
  `permanent_province` varchar(100) DEFAULT NULL,
  `permanent_municipality` varchar(100) DEFAULT NULL,
  `permanent_barangay` varchar(100) DEFAULT NULL,
  `permanent_address` varchar(255) DEFAULT NULL,
  `permanent_zip_code` varchar(10) DEFAULT NULL,
  `mailing_same_as_permanent` tinyint(1) DEFAULT '1',
  `mailing_region` varchar(100) DEFAULT NULL,
  `mailing_province` varchar(100) DEFAULT NULL,
  `mailing_municipality` varchar(100) DEFAULT NULL,
  `mailing_barangay` varchar(100) DEFAULT NULL,
  `mailing_address` varchar(255) DEFAULT NULL,
  `mailing_zip_code` varchar(10) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`faculty_id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`faculty_id`, `employee_id`, `first_name`, `middle_name`, `last_name`, `email`, `password`, `phone`, `department`, `position`, `specialization`, `employment_status`, `status`, `date_hired`, `created_at`, `updated_at`, `suffix_name`, `college`, `personal_email`, `date_of_birth`, `place_of_birth`, `sex`, `civil_status`, `religion`, `nationality`, `disability`, `permanent_region`, `permanent_province`, `permanent_municipality`, `permanent_barangay`, `permanent_address`, `permanent_zip_code`, `mailing_same_as_permanent`, `mailing_region`, `mailing_province`, `mailing_municipality`, `mailing_barangay`, `mailing_address`, `mailing_zip_code`, `profile_photo`) VALUES
(3, 'EMP20260323002', 'Sarah', 'Michelle', 'Johnson', 'sarah.johnson@university.edu', '$2y$10$HMHOQ94MITLMutBlzRSfGOx8dKPlZeG3C0V1U5u51rW6sZL6It6Ke', '555-0201', 'Information Technology', 'Assistant Professor', 'Network Security', 'full-time', 'active', '2023-06-15', '2026-03-23 09:20:23', '2026-03-23 09:20:23', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'EMP20260323003', 'Robert', 'James', 'Martinez', 'robert.martinez@university.edu', '$2y$10$WaSrslQ1GeAKa03oDZcC/OMHyP8GR17TdcZFzjlECdji55/L40dTm', '555-0202', 'Engineering', 'Instructor', 'Civil Engineering', 'part-time', 'active', '2024-01-10', '2026-03-23 09:20:23', '2026-03-23 09:20:23', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
CREATE TABLE IF NOT EXISTS `grades` (
  `grade_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `grade` varchar(5) NOT NULL,
  `status` varchar(20) NOT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`grade_id`),
  KEY `student_id` (`student_id`),
  KEY `subject_id` (`subject_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_entries`
--

DROP TABLE IF EXISTS `grade_entries`;
CREATE TABLE IF NOT EXISTS `grade_entries` (
  `entry_id` int NOT NULL AUTO_INCREMENT,
  `enrollment_id` int NOT NULL,
  `class_id` int NOT NULL,
  `student_id` int NOT NULL,
  `class_standing` decimal(5,2) DEFAULT NULL,
  `quiz` decimal(5,2) DEFAULT NULL,
  `midterms` decimal(5,2) DEFAULT NULL,
  `finals` decimal(5,2) DEFAULT NULL,
  `computed_grade` decimal(5,2) GENERATED ALWAYS AS (round(((((coalesce(`class_standing`,0) * 0.30) + (coalesce(`quiz`,0) * 0.30)) + (coalesce(`midterms`,0) * 0.20)) + (coalesce(`finals`,0) * 0.20)),2)) STORED,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`entry_id`),
  UNIQUE KEY `uq_enroll` (`enrollment_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `grade_entries`
--

INSERT INTO `grade_entries` (`entry_id`, `enrollment_id`, `class_id`, `student_id`, `class_standing`, `quiz`, `midterms`, `finals`, `updated_at`) VALUES
(1, 2, 1, 2, NULL, NULL, NULL, NULL, '2026-03-30 03:45:20');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `token` varchar(64) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `student_id`, `token`, `expires_at`, `created_at`) VALUES
(2, 1, '44a822000cfd6f87a31aa06424160a317dd90de65db72290e29f3810150ba758', '2026-03-04 11:21:13', '2026-03-04 10:21:13'),
(3, 1, '2ca92fd2336316fb9c49a588d73020d5a833d2f8842179d3f1e453b8c93288f3', '2026-03-04 11:22:59', '2026-03-04 10:22:59'),
(4, 1, '6f5fcb51eb9cc17c88a660882fe7ee0267918ee9abcb0613a5137f035acf1a3d', '2026-03-04 11:45:04', '2026-03-04 10:45:04'),
(5, 1, 'c1350f912459bdadaca1d375348e4379c6681784f259df6f50d451e9f67667fb', '2026-03-04 11:54:16', '2026-03-04 10:54:16'),
(6, 1, 'ebc6e396ef1825cf35e17b284a1fd4cdffaa58a1f98e9ae5e29b8cea21f47815', '2026-03-04 11:56:46', '2026-03-04 10:56:46'),
(7, 1, 'aab296a758e2358c341a09e65d1b5fc73aa6a221600a526cdad05e8ffc8b71f6', '2026-03-04 11:59:55', '2026-03-04 10:59:55'),
(8, 1, '60361ebadf235c61420625992d4fccc7d60f2ea26b9bcf5f9331e040f67e516a', '2026-03-04 12:00:01', '2026-03-04 11:00:01'),
(9, 1, '019bc14b417d550b64a784e43ffb5b4bd897c80d56996e33dd2eb5e6d97d37df', '2026-03-04 12:00:25', '2026-03-04 11:00:25'),
(10, 1, 'e107d41dd19d7b251583e07a4f4e66de462de0aae1487c17b9ec28b312f2a64d', '2026-03-04 12:04:06', '2026-03-04 11:04:06'),
(11, 1, 'd869c97c00776abd1f386ac30d3f6595d09f47c56bd9e04e63cb15eab8ced61d', '2026-03-04 12:04:08', '2026-03-04 11:04:08'),
(12, 1, '8b9bda8078a3b8c05a1ed38d8dfe063a5c5f5e1053bd022ae64527878ac09a0b', '2026-03-04 12:06:27', '2026-03-04 11:06:27'),
(13, 1, '415daef751959a5f8ece14e94ab786bd207b06e1fe2001dc1da886fdaf5f4a03', '2026-03-04 12:08:38', '2026-03-04 11:08:38'),
(14, 1, '42c7c6b9816e71fbc8ead6dd6c8b7a20104704b5478d5e59328539902b6a457e', '2026-03-04 12:08:42', '2026-03-04 11:08:42'),
(15, 1, '3dba85cb751ed5ffe5b88e1ce74a24c609af8210f332ea55937bd001d48a2a4b', '2026-03-04 12:12:26', '2026-03-04 11:12:26'),
(16, 1, '0f49393056773003a706ded81326745497bd8cf4ce2dc13c1e1f3084c520804d', '2026-03-04 12:13:44', '2026-03-04 11:13:44'),
(17, 1, 'ca20e65767577f856c068035b84b97a0f4a096babb5e88abcdcc7a7b6e01b081', '2026-03-04 12:14:05', '2026-03-04 11:14:05'),
(18, 1, '496f65b4139b60d69627deec7cc20dec1fb827999ab070cbffe213ab13838f22', '2026-03-04 12:14:07', '2026-03-04 11:14:07'),
(20, 1, '1761d80c9f5a7b05da0b680721e9252f1c8c09dfbd664d8ba4c6a62e935ffcd8', '2026-03-08 08:29:08', '2026-03-08 07:29:08'),
(21, 1, 'a9de556e3456e40c32e1a00d4deb9fd69a393a08b188e205945cf608b00be4e5', '2026-03-08 08:29:12', '2026-03-08 07:29:12'),
(22, 1, '5a6cb01a27e8814043de1203b41b04e617bc4749fdd13ad18a72bf13504e28a3', '2026-03-08 08:29:15', '2026-03-08 07:29:15');

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

DROP TABLE IF EXISTS `registrations`;
CREATE TABLE IF NOT EXISTS `registrations` (
  `registration_id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `subject_id` int NOT NULL,
  PRIMARY KEY (`registration_id`),
  KEY `student_id` (`student_id`),
  KEY `subject_id` (`subject_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
CREATE TABLE IF NOT EXISTS `students` (
  `student_id` int NOT NULL AUTO_INCREMENT,
  `student_number` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `birthdate` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `college` varchar(100) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `year_level` int DEFAULT NULL,
  `block_id` int DEFAULT NULL COMMENT 'Foreign key to blocks table',
  `password` varchar(255) NOT NULL,
  `account_status` enum('active','inactive') DEFAULT 'active',
  `profile_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `suffix_name` varchar(20) DEFAULT NULL,
  `registration_status` enum('Regular','Irregular') DEFAULT 'Regular',
  `status` varchar(50) NOT NULL DEFAULT 'Not Enrolled',
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `student_number` (`student_number`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `student_number`, `first_name`, `last_name`, `middle_name`, `gender`, `birthdate`, `email`, `contact_number`, `college`, `course`, `year_level`, `block_id`, `password`, `account_status`, `profile_photo`, `created_at`, `suffix_name`, `registration_status`, `status`) VALUES
(1, '202412685', 'John Louie', 'Muncada', 'Lopez', 'Male', '2005-11-17', 'xsurethingx@gmail.com', '09543447352', 'CE', 'BS Computer Science', 1, 1, '$2y$10$Bz0gMIypT.CH4I4Ab64cvO7pI0VWooeHYxByvmwYTcBQ58VEWPHOq', 'active', 'uploads/69c1b0f24fe16_Y0_STEAM_WALLPAPER_3_1600X900.jpg', '2026-03-04 09:48:58', '', '', 'Not Enrolled'),
(2, '2024-1244', 'John Louie', 'Muncada', 'Lopez', 'male', '2005-11-17', 'vnoir17@gmail.com', '09543447352', 'CE', 'BS Computer Science', 1, 1, '$2y$10$XWXT31AObvg0Q1DnXjd2EO2u99ctm6bpgMvIvNcA8/sHju2akx296', 'active', NULL, '2026-03-27 04:02:32', NULL, 'Regular', 'Not Enrolled');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE IF NOT EXISTS `subjects` (
  `subject_id` int NOT NULL AUTO_INCREMENT,
  `subject_code` varchar(20) NOT NULL COMMENT 'e.g., CS101, IT201',
  `subject_name` varchar(255) NOT NULL,
  `description` text,
  `units` int NOT NULL DEFAULT '3' COMMENT 'Credit units',
  `lecture_hours` decimal(3,1) DEFAULT '3.0',
  `lab_hours` decimal(3,1) DEFAULT '0.0',
  `department` varchar(100) DEFAULT NULL,
  `year_level` enum('1','2','3','4') DEFAULT NULL COMMENT 'Recommended year level',
  `semester` enum('1st','2nd','summer') DEFAULT NULL COMMENT 'Recommended semester',
  `prerequisite` varchar(255) DEFAULT NULL COMMENT 'Prerequisite subject codes',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `hours` int GENERATED ALWAYS AS ((`lecture_hours` + `lab_hours`)) VIRTUAL,
  `schedule` varchar(255) DEFAULT NULL,
  `faculty_id` int DEFAULT NULL,
  PRIMARY KEY (`subject_id`),
  UNIQUE KEY `subject_code` (`subject_code`)
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_code`, `subject_name`, `description`, `units`, `lecture_hours`, `lab_hours`, `department`, `year_level`, `semester`, `prerequisite`, `status`, `created_at`, `updated_at`, `schedule`, `faculty_id`) VALUES
(2, 'STS 0002', 'Science, Technology and Society', NULL, 3, 3.0, 0.0, 'BSIT', '1', '1st', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(3, 'AAP 0007', 'Art Appreciation', NULL, 3, 3.0, 0.0, 'BSIT', '1', '1st', '', 'active', '2026-03-27 05:43:38', '2026-03-27 12:09:16', NULL, NULL),
(4, 'PCM 0006', 'Purposive Communication', NULL, 3, 3.0, 0.0, 'BSIT', '1', '1st', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(5, 'MMW 0001', 'Mathematics in the Modern World', NULL, 3, 3.0, 0.0, 'BSIT', '1', '1st', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(6, 'IPP 0010', 'Interdisiplinaryong Pagbasa at Pagsulat Tungo sa Mabisang', NULL, 3, 3.0, 0.0, 'BSIT', '1', '1st', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(7, 'ICC 0101, ICC 0101.1', 'Introduction to Computing', NULL, 3, 2.0, 1.0, 'BSIT', '1', '1st', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(8, 'ICC 0102, ICC 0102.1', 'Fundamentals of Programming', NULL, 3, 2.0, 1.0, 'BSIT', '1', '1st', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(9, 'PED 0001', 'Foundation of Physical Activities', NULL, 2, 2.0, 0.0, 'BSIT', '1', '1st', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(10, 'NSTP 01', 'National Service Training Program 1- ROTC 1/CWTS 1', NULL, 3, 3.0, 0.0, 'BSIT', '1', '1st', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(11, 'CET 0111', 'Calculus 1', NULL, 3, 3.0, 0.0, 'BSIT', '1', '2nd', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(12, 'CET 0114, CET 0114.1', 'General Chemistry', NULL, 4, 3.0, 1.0, 'BSIT', '1', '2nd', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(13, 'EIT 0121, EIT 0121.1', 'Introduction to Computer Human Interaction', NULL, 3, 2.0, 1.0, 'BSIT', '1', '2nd', 'ICC 0101', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(14, 'EIT 0122', 'Discrete Mathematics', NULL, 3, 3.0, 0.0, 'BSIT', '1', '2nd', 'ICC 0101', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(15, 'EIT 0123, EIT 0123.1', 'Web Systems Technology', NULL, 3, 2.0, 1.0, 'BSIT', '1', '2nd', 'ICC 0102, ICC 0103', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(16, 'ICC 0103, ICC 0103.1', 'Intermediate Programming', NULL, 3, 2.0, 1.0, 'BSIT', '1', '2nd', 'ICC 0101, ICC 0102', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(17, 'GTB 121', 'Great Books', NULL, 3, 3.0, 0.0, 'BSIT', '1', '2nd', 'EIT 0123', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(18, 'PED 0013', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'BSIT', '1', '2nd', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(19, 'NSTP 02', 'National Service Training Program 2-ROTC 2/CWTS 2', NULL, 3, 3.0, 0.0, 'BSIT', '1', '2nd', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(20, 'CET 0121', 'Calculus 2', NULL, 3, 3.0, 0.0, 'BSIT', '2', '1st', 'CET 0111', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(21, 'CET 0225, CET 0225.1', 'Physics for IT', NULL, 4, 3.0, 1.0, 'BSIT', '2', '1st', 'CET 0121', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(22, 'TCW 0005', 'The Contemporary World', NULL, 3, 3.0, 0.0, 'BSIT', '2', '1st', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(23, 'ICC 0104, ICC 0104.1', 'Data Structures and Algorithms', NULL, 3, 2.0, 1.0, 'BSIT', '2', '1st', 'ICC 0103', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(24, 'EIT 0211, EIT 0211.1', 'Object Oriented Programming', NULL, 3, 2.0, 1.0, 'BSIT', '2', '1st', 'ICC 0104', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(25, 'PPC 122', 'Philippine Popular Culture', NULL, 3, 3.0, 0.0, 'BSIT', '2', '1st', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(26, 'EIT ELECTIVE 1', 'Professional Elective 1', NULL, 3, 3.0, 0.0, 'BSIT', '2', '1st', '2nd Year Standing', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(27, 'PED 0054', 'Soccer', NULL, 2, 2.0, 0.0, 'BSIT', '2', '1st', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(28, 'UTS 0003', 'Understanding the Self', NULL, 3, 3.0, 0.0, 'BSIT', '2', '2nd', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(29, 'RPH 0004', 'Readings in Philippine History', NULL, 3, 3.0, 0.0, 'BSIT', '2', '2nd', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(30, 'EIT 0212A', 'Platform Technology (Operating System)', NULL, 3, 3.0, 0.0, 'BSIT', '2', '2nd', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(31, 'ICC 0105, ICC 0105.1', 'Information Management', NULL, 3, 2.0, 1.0, 'BSIT', '2', '2nd', '2nd Year Standing', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(32, 'EIT 0221', 'Quantitative Methods', NULL, 3, 3.0, 0.0, 'BSIT', '2', '2nd', 'ICC 0104', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(33, 'EIT 0222, EIT 0222.1', 'Networking 1', NULL, 3, 2.0, 1.0, 'BSIT', '2', '2nd', 'EIT 0212', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(34, 'ENS 111', 'Environmental Science', NULL, 3, 3.0, 0.0, 'BSIT', '2', '2nd', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(35, 'EIT ELECTIVE 2', 'Professional Elective 2', NULL, 3, 3.0, 0.0, 'BSIT', '2', '2nd', '2nd Year Standing', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(36, 'PED 0074', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'BSIT', '2', '2nd', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(37, 'ICC 0335, ICC 0335.1', 'Application and Emerging Technologies', NULL, 3, 2.0, 1.0, 'BSIT', '3', '1st', 'ICC 0103', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(38, 'EIT 0311, EIT 0311.1', 'Advanced Database Systems', NULL, 3, 2.0, 1.0, 'BSIT', '3', '1st', 'EIT 0211', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(39, 'EIT ELECTIVE 3', 'Professional Elective 3', NULL, 3, 3.0, 0.0, 'BSIT', '3', '1st', 'EIT 0222', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(40, 'EIT 0312, EIT 0312.1', 'Networking 2', NULL, 3, 2.0, 1.0, 'BSIT', '3', '1st', '3rd Year Standing', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(41, 'LWR 0009', 'Life and Works of Rizal', NULL, 3, 3.0, 0.0, 'BSIT', '3', '1st', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(42, 'EIT 0321, EIT 0321.1', 'Information Assurance and Security 1', NULL, 3, 2.0, 1.0, 'BSIT', '3', '2nd', 'EIT 0312', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(43, 'EIT 0322', 'System Integration and Architecture 1', NULL, 3, 2.0, 0.0, 'BSIT', '3', '2nd', 'EIT 0311', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(44, 'EIT 0322.1, EIT 0323', 'Integrative Programming and Technologies', NULL, 3, 2.0, 1.0, 'BSIT', '3', '2nd', 'EIT 0322, ICC 0335, EIT 0311', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(45, 'ETH 0008', 'Ethics', NULL, 3, 3.0, 0.0, 'BSIT', '3', '2nd', '', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(46, 'EIT 0331, EIT 0331.1', 'System Integration and Architecture 2', NULL, 3, 2.0, 1.0, 'BSIT', '3', 'summer', 'EIT 0322', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(47, 'CAP 0101', 'Capstone Project 1', NULL, 3, 3.0, 0.0, 'BSIT', '3', 'summer', '3rd Year Standing', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(48, 'EIT ELECTIVE 4', 'Professional Elective 4', NULL, 3, 3.0, 0.0, 'BSIT', '4', '1st', '4th Year Standing', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(49, 'EIT ELECTIVE 5', 'Professional Elective 5', NULL, 3, 3.0, 0.0, 'BSIT', '4', '1st', '4th Year Standing', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(50, 'EIT ELECTIVE 6', 'Professional Elective 6', NULL, 3, 3.0, 0.0, 'BSIT', '4', '1st', '4th Year Standing', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(51, 'CAP 0102', 'Capstone Project', NULL, 3, 3.0, 0.0, 'BSIT', '4', '1st', 'CAP 0101', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(52, 'IIP 0101A', 'Practicum (Lecture)', NULL, 2, 2.0, 0.0, 'BSIT', '4', '2nd', '4th Year Standing', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(53, 'IIP 0101.1', 'Practicum (Immersion)', NULL, 4, 0.0, 4.0, 'BSIT', '4', '2nd', '4th Year Standing', 'active', '2026-03-27 05:43:38', '2026-03-27 05:43:38', NULL, NULL),
(54, 'CET 0112, CET 0112.1', 'Chemistry for Engineers', NULL, 4, 3.0, 1.0, 'BSCPE', '1', '1st', 'CET 0112.1', 'active', '2026-03-27 05:47:49', '2026-03-27 05:51:59', NULL, NULL),
(55, 'CPE 0111', 'Computer Engineering as a Discipline', NULL, 1, 1.0, 0.0, 'BSCPE', '1', '1st', '', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(56, 'CPE 0112.1', 'Programming Logic and Design (Laboratory)', NULL, 2, 0.0, 2.0, 'BSCPE', '1', '1st', '', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(57, 'CET 0122A, CET 0122A', 'Physics for Engineers', NULL, 5, 4.0, 1.0, 'BSCPE', '1', '2nd', 'CET 0111, CET 0122.1', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(58, 'CET 0216', 'Engineering Economics', NULL, 3, 3.0, 0.0, 'BSCPE', '1', '2nd', '', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(59, 'CPE 0121.1', 'Object Oriented Programming (Laboratory)', NULL, 2, 0.0, 2.0, 'BSCPE', '1', '2nd', 'CPE 0112.1', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(60, 'CPE 0122', 'Discrete Mathematics', NULL, 3, 3.0, 0.0, 'BSCPE', '1', '2nd', 'CET 0111', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(61, 'ITE 0001', 'Living in the IT Era', NULL, 3, 3.0, 0.0, 'BSCPE', '1', '2nd', '', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(62, 'PED 0043', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'BSCPE', '1', '2nd', 'PED 0001', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(63, 'CET 0123.1', 'Computer-Aided Drafting', NULL, 1, 1.0, 0.0, 'BSCPE', '2', '1st', '2nd Year Standing', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(64, 'CET 0211', 'Differential Equations', NULL, 3, 3.0, 0.0, 'BSCPE', '2', '1st', 'CET 0121', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(65, 'CET 0212', 'Engineering Data Analysis', NULL, 3, 3.0, 0.0, 'BSCPE', '2', '1st', 'CET 0111', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(66, 'CPE 0211.1', 'Data Structures and Algorithms (Laboratory)', NULL, 2, 0.0, 2.0, 'BSCPE', '2', '1st', 'CET 0121.1', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(67, 'CPE 0212, CPE 0212.1', 'Fundamentals of Electrical Circuits', NULL, 4, 3.0, 1.0, 'BSCPE', '2', '1st', 'CET 0122, CPE 0212.1', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(68, 'CPE 0221', 'Numerical Methods', NULL, 3, 3.0, 0.0, 'BSCPE', '2', '2nd', 'CET 0211', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(69, 'CPE 0222, CPE 0222.1', 'Software Design', NULL, 4, 3.0, 1.0, 'BSCPE', '2', '2nd', 'CET 0211.1, CPE 0222.1', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(70, 'CPE 0223, CPE 0223.1', 'Fundamentals of Electronic Circuits', NULL, 4, 3.0, 1.0, 'BSCPE', '2', '2nd', 'CPE 0212, CPE 0223.1', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(71, 'CPE 0311, CPE 0311.1', 'Logic Circuits and Design', NULL, 4, 3.0, 1.0, 'BSCPE', '3', '1st', 'CPE 0223, CPE 0311.1', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(72, 'CPE 0312.1', 'Introduction to HDL (Laboratory)', NULL, 1, 0.0, 1.0, 'BSCPE', '3', '1st', 'CPE 0112.1, CPE 0223', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(73, 'CPE 0313', 'Operating Systems', NULL, 3, 3.0, 0.0, 'BSCPE', '3', '1st', 'CPE 0211.1', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(74, 'CPE 0314', 'Data and Digital Communications', NULL, 3, 3.0, 0.0, 'BSCPE', '3', '1st', 'CPE 0223', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(75, 'CPE 0315', 'Feedback and Control Systems', NULL, 3, 3.0, 0.0, 'BSCPE', '3', '1st', 'CPE 0221, CPE 0212', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(76, 'CPE 0316', 'Fundamentals of Mixed Signals and Sensors', NULL, 3, 3.0, 0.0, 'BSCPE', '3', '1st', 'CPE 0223', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(77, 'CPE 0317.1', 'Computer Engineering Drafting and Design (Design)', NULL, 1, 1.0, 0.0, 'BSCPE', '3', '1st', 'CPE 0223', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(78, 'CPE 0318, CPE 0318.1', 'Elective 1 (Lecture)', NULL, 3, 2.0, 1.0, 'BSCPE', '3', '1st', 'CPE 0222, CPE 0318.1', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(79, 'CET 0411', 'Technopreneurship 101', NULL, 3, 3.0, 0.0, 'BSCPE', '3', '2nd', '', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(80, 'CPE 0321', 'Basic Occupational Health and Safety', NULL, 3, 3.0, 0.0, 'BSCPE', '3', '2nd', '3rd Year Standing', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(81, 'CPE 0322, CPE 0322.1', 'Computer Networks and Security', NULL, 4, 3.0, 1.0, 'BSCPE', '3', '2nd', 'CPE 0314, CPE 0322.1', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(82, 'CPE 0323, CPE 0323.1', 'Computer Architecture and Organization', NULL, 4, 3.0, 1.0, 'BSCPE', '3', '2nd', 'CPE 0311, CPE 0323.1', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(83, 'CPE 0324', 'Methods of Research', NULL, 2, 2.0, 0.0, 'BSCPE', '3', '2nd', 'CET 0212, PCM 0006, CPE 0311', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(84, 'CPE 0325', 'CPE Laws and Professional Practice', NULL, 2, 2.0, 0.0, 'BSCPE', '3', '2nd', '3rd Year Standing', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(85, 'CPE 0326', 'Elective 2', NULL, 3, 3.0, 0.0, 'BSCPE', '3', '2nd', '', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(86, 'CPE 0331', 'Seminars and Field Trips', NULL, 1, 1.0, 0.0, 'BSCPE', '3', 'summer', '', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(87, 'CPE 0332, CPE 0332.1', 'Elective 3', NULL, 3, 2.0, 1.0, 'BSCPE', '3', 'summer', 'CPE 0222, CPE 0332.1', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(88, 'CPE 0411, CPE 0411.1', 'Embedded Systems', NULL, 4, 3.0, 1.0, 'BSCPE', '4', '1st', 'CPE 0323, CPE 0411.1', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(89, 'CPE 0412, CPE 0412.1', 'Microprocessors', NULL, 4, 3.0, 1.0, 'BSCPE', '4', '1st', 'CPE 0323, CPE 0412.1', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(90, 'CPE 0413', 'Emerging Technologies in CPE', NULL, 3, 3.0, 0.0, 'BSCPE', '4', '1st', '4th Year Standing', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(91, 'CPE 0414.1', 'CPE Practice and Design 1 (Design)', NULL, 1, 1.0, 0.0, 'BSCPE', '4', '1st', 'CPE 0323, CPE 0324', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(92, 'CPE 0415, CPE 0415.1', 'Digital Signal Processing', NULL, 4, 3.0, 1.0, 'BSCPE', '4', '1st', 'CPE 0315, CPE 0415.1', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(93, 'CPE 0421.1', 'CPE Practice and Design 2 (Design)', NULL, 2, 2.0, 0.0, 'BSCPE', '4', '2nd', 'CPE 0414.1', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL),
(94, 'CPE 0422.1', 'On-The-Job Training for CPE (240 hrs)', NULL, 3, 3.0, 0.0, 'BSCPE', '4', '2nd', '4th Year Standing', 'active', '2026-03-27 05:47:49', '2026-03-27 05:47:49', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subject_blocks`
--

DROP TABLE IF EXISTS `subject_blocks`;
CREATE TABLE IF NOT EXISTS `subject_blocks` (
  `subject_block_id` int NOT NULL AUTO_INCREMENT,
  `class_id` int NOT NULL,
  `block_name` varchar(20) NOT NULL,
  `capacity` int DEFAULT '40',
  PRIMARY KEY (`subject_block_id`),
  KEY `subject_id` (`class_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `block_subjects`
--
ALTER TABLE `block_subjects`
  ADD CONSTRAINT `block_subjects_ibfk_1` FOREIGN KEY (`block_id`) REFERENCES `blocks` (`block_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `block_subjects_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`) ON DELETE CASCADE;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `classes_ibfk_2` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
