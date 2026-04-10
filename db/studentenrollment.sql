-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2026 at 09:59 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'admin',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `email`, `password`, `role`, `created_at`) VALUES
(4, 'admin3', '234@gmail.com', '$2y$10$gMSnPiC/592xZu0BdonF4eSia32HbuL6v2fBvXMivPWiJX8i8CI46', 'admin', '2026-04-08 15:57:53'),
(2, 'admin2', 'admin2@plm.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', '2026-03-12 03:27:30');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `announcement_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL COMMENT 'Foreign key to admins table - who posted it',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `media` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`media`)),
  `target_audience` enum('all','students','applicants','faculty') DEFAULT 'all',
  `priority` enum('normal','important','urgent') DEFAULT 'normal',
  `status` enum('active','archived') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `admin_id`, `title`, `message`, `media`, `target_audience`, `priority`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Enrollment Period Extended', 'The enrollment period for 2nd Semester AY 2024-2025 has been extended until January 31, 2025. All students are encouraged to complete their enrollment before the deadline.', '[{\"file\": \"ann_69c9f0333df712.62928093.png\", \"type\": \"image\"}]', 'all', 'important', 'active', '2026-03-09 06:47:19', '2026-04-08 08:19:57');

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

CREATE TABLE `applicants` (
  `applicant_id` int(11) NOT NULL,
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
  `perm_address` text DEFAULT NULL,
  `perm_zipcode` varchar(10) DEFAULT NULL,
  `mail_region` varchar(100) DEFAULT NULL,
  `mail_province` varchar(100) DEFAULT NULL,
  `mail_municipality` varchar(100) DEFAULT NULL,
  `mail_barangay` varchar(100) DEFAULT NULL,
  `mail_address` text DEFAULT NULL,
  `mail_zipcode` varchar(10) DEFAULT NULL,
  `application_status` varchar(50) DEFAULT 'incomplete',
  `documents_submitted` tinyint(1) DEFAULT 0,
  `exam_scheduled` tinyint(1) DEFAULT 0,
  `exam_date` date DEFAULT NULL,
  `exam_time` varchar(50) DEFAULT NULL,
  `exam_venue` varchar(200) DEFAULT NULL,
  `exam_room` varchar(50) DEFAULT NULL,
  `exam_taken` tinyint(1) DEFAULT 0,
  `exam_result` varchar(20) DEFAULT NULL,
  `exam_score` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `exam_schedule_id` int(11) DEFAULT NULL,
  `exam_notified` tinyint(1) DEFAULT 0,
  `doc_form138` varchar(255) DEFAULT NULL,
  `doc_birth_cert` varchar(255) DEFAULT NULL,
  `doc_good_moral` varchar(255) DEFAULT NULL,
  `doc_our_au001` varchar(255) DEFAULT NULL,
  `doc_our_au002` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicants`
--

INSERT INTO `applicants` (`applicant_id`, `email`, `password`, `lrn`, `first_choice`, `second_choice`, `third_choice`, `last_name`, `first_name`, `middle_name`, `suffix`, `married_name`, `birthdate`, `nationality`, `place_of_birth`, `civil_status`, `contact_number`, `religion`, `gender`, `disability`, `perm_region`, `perm_province`, `perm_municipality`, `perm_barangay`, `perm_address`, `perm_zipcode`, `mail_region`, `mail_province`, `mail_municipality`, `mail_barangay`, `mail_address`, `mail_zipcode`, `application_status`, `documents_submitted`, `exam_scheduled`, `exam_date`, `exam_time`, `exam_venue`, `exam_room`, `exam_taken`, `exam_result`, `exam_score`, `created_at`, `updated_at`, `exam_schedule_id`, `exam_notified`, `doc_form138`, `doc_birth_cert`, `doc_good_moral`, `doc_our_au001`, `doc_our_au002`) VALUES
(2, '124@gmail.com', '$2y$10$KMppsxxZUlKrSE74rS6qvu.iCxO9HbLfwso3emP5.jVoVoAUKif0S', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'approved', 0, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-03-27 12:02:16', '2026-03-30 04:55:54', 1, 0, NULL, NULL, NULL, NULL, NULL),
(4, 'softdevset@gmail.com', '$2y$10$pJSk16nv8F.kfnnHswcWlenESYXo7ZveToupT6iHEk84AsRn2pOwS', '123456789123', 'BS Computer Science', 'BS Information Technology', 'BS Business Administration', 'Muncada', 'Louie', 'Lopez', '', '', '2005-11-17', 'Filipino', 'Manila', 'single', '09543447352', 'Roman Catholic', 'male', 'Madami', 'NCR', 'Metro Manila', 'Manila', '903', 'B1-401 Jaime Cardinal Sin Village', '1009', 'NCR', 'Metro Manila', 'Manila', '903', 'B1-401 Jaime Cardinal Sin Village', '1009', 'approved', 1, 0, NULL, NULL, NULL, NULL, 0, NULL, NULL, '2026-04-08 09:12:06', '2026-04-08 16:15:27', NULL, 0, 'form138_1775642478.png', 'birth_cert_1775642470.png', 'good_moral_1775642470.png', 'our_au001_1775642470.png', 'our_au002_1775642470.png');

-- --------------------------------------------------------

--
-- Table structure for table `blocks`
--

CREATE TABLE `blocks` (
  `block_id` int(11) NOT NULL,
  `block_name` varchar(50) NOT NULL COMMENT 'e.g., 1A, 1B, 2A, 2B',
  `course` varchar(100) NOT NULL COMMENT 'e.g., BS Computer Science, BS Information Technology',
  `year_level` enum('1','2','3','4') NOT NULL,
  `semester` enum('1st','2nd','summer') NOT NULL,
  `school_year` varchar(20) NOT NULL COMMENT 'e.g., 2024-2025',
  `max_students` int(11) DEFAULT 40 COMMENT 'Maximum students per block',
  `current_students` int(11) DEFAULT 0 COMMENT 'Current number of students',
  `status` enum('active','inactive','full') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blocks`
--

INSERT INTO `blocks` (`block_id`, `block_name`, `course`, `year_level`, `semester`, `school_year`, `max_students`, `current_students`, `status`, `created_at`, `updated_at`) VALUES
(1, 'panget', 'BSCpE', '1', '1st', '2026-2027', 40, 3, 'active', '2026-03-12 02:43:43', '2026-04-09 03:33:23'),
(2, 'asdas', 'BS Information Technology', '2', '1st', 'asda', 40, 0, 'active', '2026-04-08 16:22:10', '2026-04-08 16:22:10');

-- --------------------------------------------------------

--
-- Table structure for table `block_subjects`
--

CREATE TABLE `block_subjects` (
  `block_subject_id` int(11) NOT NULL,
  `block_id` int(11) NOT NULL COMMENT 'Foreign key to blocks table',
  `class_id` int(11) NOT NULL COMMENT 'Foreign key to classes table',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `block_subjects`
--

INSERT INTO `block_subjects` (`block_subject_id`, `block_id`, `class_id`, `created_at`) VALUES
(2, 1, 6, '2026-04-08 18:27:42');

-- --------------------------------------------------------

--
-- Table structure for table `calendar_events`
--

CREATE TABLE `calendar_events` (
  `event_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `event_time` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT '#8C1C24',
  `audience` enum('all','students','faculty','applicants') DEFAULT 'all',
  `image` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL COMMENT 'Foreign key to subjects table',
  `faculty_id` int(11) DEFAULT NULL COMMENT 'Foreign key to faculty table',
  `section` varchar(20) NOT NULL COMMENT 'e.g., A, B, C, 1A, 2B',
  `school_year` varchar(20) NOT NULL COMMENT 'e.g., 2024-2025',
  `semester` enum('1st','2nd','summer') NOT NULL,
  `schedule_day` varchar(50) DEFAULT NULL COMMENT 'e.g., Monday, Tuesday, MW, TTH',
  `schedule_time` varchar(50) DEFAULT NULL COMMENT 'e.g., 8:00 AM - 10:00 AM',
  `room` varchar(50) DEFAULT NULL COMMENT 'Room number or location',
  `max_slots` int(11) DEFAULT 40 COMMENT 'Maximum number of students',
  `enrolled_count` int(11) DEFAULT 0 COMMENT 'Current number of enrolled students',
  `status` enum('open','closed','cancelled') DEFAULT 'open',
  `grades_finalized` tinyint(1) NOT NULL DEFAULT 0,
  `grades_finalized_at` timestamp NULL DEFAULT NULL,
  `specific_department` varchar(100) DEFAULT NULL COMMENT 'Department restriction - NULL means available to all departments',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`class_id`, `subject_id`, `faculty_id`, `section`, `school_year`, `semester`, `schedule_day`, `schedule_time`, `room`, `max_slots`, `enrolled_count`, `status`, `grades_finalized`, `grades_finalized_at`, `specific_department`, `created_at`, `updated_at`) VALUES
(6, 731, 4, 'BSCpE 1-A', '2026-2027', '1st', 'MWF', '12:00PM - 3:00PM', '', 40, 2, 'open', 0, NULL, NULL, '2026-04-08 18:27:11', '2026-04-09 04:52:21');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `college_code` varchar(10) NOT NULL,
  `college_name` varchar(100) NOT NULL,
  `curriculum_url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `program_objectives` text DEFAULT NULL,
  `career_opportunities` text DEFAULT NULL,
  `college_description` text DEFAULT NULL,
  `college_history` text DEFAULT NULL,
  `college_vision` text DEFAULT NULL,
  `college_mission` text DEFAULT NULL,
  `college_objectives` text DEFAULT NULL,
  `college_location` varchar(255) DEFAULT NULL,
  `college_local_number` varchar(100) DEFAULT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(200) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `college_code`, `college_name`, `curriculum_url`, `description`, `program_objectives`, `career_opportunities`, `college_description`, `college_history`, `college_vision`, `college_mission`, `college_objectives`, `college_location`, `college_local_number`, `course_code`, `course_name`, `status`, `created_at`) VALUES
(1, 'CE', 'College of Engineering', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'BSChE', 'Bachelor of Science in Chemical Engineering', 'active', '2026-04-08 16:35:27'),
(2, 'CE', 'College of Engineering', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'BSCE', 'Bachelor of Science in Civil Engineering', 'active', '2026-04-08 16:35:27'),
(5, 'CE', 'College of Engineering', 'https://web13.plm.edu.ph/media/courses/Bachelor_of_Science_in_Computer_Engineering.pdf', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'BSCpE', 'Bachelor of Science in Computer Engineering', 'active', '2026-04-08 16:35:27'),
(6, 'CE', 'College of Engineering', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'BSEE', 'Bachelor of Science in Electrical Engineering', 'active', '2026-04-08 16:35:27'),
(7, 'CE', 'College of Engineering', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'BSECE', 'Bachelor of Science in Electronics Engineering', 'active', '2026-04-08 16:35:27'),
(8, 'CE', 'College of Engineering', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'BSMfgE', 'Bachelor of Science in Manufacturing Engineering', 'active', '2026-04-08 16:35:27'),
(9, 'CE', 'College of Engineering', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'BSME', 'Bachelor of Science in Mechanical Engineering', 'active', '2026-04-08 16:35:27'),
(10, 'CA', 'College of Accountancy', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'BSA', 'Bachelor of Science in Accountancy', 'active', '2026-04-08 16:35:27'),
(11, 'CASBE', 'College of Architecture and Sustainable Built Environment', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'BS Arch', 'Bachelor of Science in Architecture', 'active', '2026-04-08 16:35:27'),
(12, 'GSL', 'Graduate School of Law', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'LL.M.', 'Master of Laws', 'active', '2026-04-08 16:35:27'),
(13, 'CE', 'College of Engineering', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'BSIT', 'Bachelor of Science in Information Technology', 'active', '2026-04-08 16:37:36');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL COMMENT 'Foreign key to classes table',
  `school_year` varchar(20) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('reserved','confirmed','ongoing','drop_requested','dropped','completed') DEFAULT 'reserved',
  `previous_status` varchar(20) DEFAULT NULL,
  `grade` decimal(4,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`enrollment_id`, `student_id`, `class_id`, `school_year`, `semester`, `enrollment_date`, `status`, `previous_status`, `grade`, `remarks`) VALUES
(1, 1, 1, '2024-2025', 1, '2026-03-27 12:25:09', 'dropped', NULL, NULL, NULL),
(2, 2, 1, '2024-2025', 1, '2026-03-27 12:27:41', 'ongoing', NULL, NULL, NULL),
(3, 1, 2, '2024-2025', 1, '2026-03-28 14:18:02', 'dropped', NULL, NULL, NULL),
(4, 1, 2, '2024-2025', 1, '2026-03-28 14:18:13', 'confirmed', NULL, NULL, NULL),
(5, 1, 2, '2024-2025', 1, '2026-03-28 14:18:32', 'dropped', NULL, NULL, NULL),
(6, 2, 2, '2024-2025', 1, '2026-03-28 14:26:44', 'dropped', NULL, NULL, NULL),
(7, 2, 2, '2024-2025', 1, '2026-03-28 14:26:51', 'dropped', NULL, NULL, NULL),
(8, 2, 2, '2024-2025', 1, '2026-03-28 14:28:55', 'confirmed', NULL, NULL, NULL),
(9, 1, 1, '2024-2025', 1, '2026-04-01 08:11:31', 'reserved', NULL, NULL, NULL),
(10, 1, 4, '2313123123', 1, '2026-04-08 18:14:54', 'dropped', NULL, NULL, NULL),
(12, 1, 6, '2026-2027', 1, '2026-04-08 18:33:07', 'dropped', NULL, NULL, NULL),
(13, 3, 6, '2026-2027', 1, '2026-04-08 18:33:10', 'dropped', NULL, NULL, NULL),
(14, 2, 6, '2026-2027', 1, '2026-04-09 03:33:23', 'dropped', NULL, NULL, NULL),
(15, 1, 6, '2026-2027', 1, '2026-04-09 03:33:40', 'dropped', NULL, NULL, NULL),
(16, 1, 6, '2026-2027', 1, '2026-04-09 03:38:22', 'dropped', NULL, NULL, NULL),
(17, 1, 6, '2026-2027', 1, '2026-04-09 03:38:48', 'dropped', NULL, NULL, NULL),
(18, 1, 6, '2026-2027', 1, '2026-04-09 03:40:59', 'dropped', 'confirmed', NULL, NULL),
(19, 1, 6, '2026-2027', 1, '2026-04-09 03:43:40', 'dropped', NULL, NULL, NULL),
(20, 1, 6, '2026-2027', 1, '2026-04-09 03:44:44', 'dropped', NULL, NULL, NULL),
(21, 1, 6, '2026-2027', 1, '2026-04-09 03:46:27', 'dropped', NULL, NULL, NULL),
(22, 1, 6, '2026-2027', 1, '2026-04-09 03:49:36', 'dropped', NULL, NULL, NULL),
(23, 1, 6, '2026-2027', 1, '2026-04-09 03:50:12', 'dropped', NULL, NULL, NULL),
(24, 1, 6, '2026-2027', 1, '2026-04-09 03:52:54', 'dropped', NULL, NULL, NULL),
(25, 1, 6, '2026-2027', 1, '2026-04-09 03:53:55', 'dropped', NULL, NULL, NULL),
(26, 1, 6, '2026-2027', 1, '2026-04-09 03:56:36', 'dropped', NULL, NULL, NULL),
(27, 1, 6, '2026-2027', 1, '2026-04-09 03:57:08', 'dropped', NULL, NULL, NULL),
(28, 1, 6, '2026-2027', 1, '2026-04-09 03:57:59', 'dropped', NULL, NULL, NULL),
(29, 1, 6, '2026-2027', 1, '2026-04-09 04:01:53', 'dropped', NULL, NULL, NULL),
(30, 1, 6, '2026-2027', 1, '2026-04-09 04:08:29', 'dropped', NULL, NULL, NULL),
(31, 1, 6, '2026-2027', 1, '2026-04-09 04:12:28', 'dropped', NULL, NULL, NULL),
(32, 1, 6, '2026-2027', 1, '2026-04-09 04:17:08', 'dropped', NULL, NULL, NULL),
(33, 1, 6, '2026-2027', 1, '2026-04-09 04:17:35', 'dropped', NULL, NULL, NULL),
(34, 1, 6, '2026-2027', 1, '2026-04-09 04:19:01', 'dropped', NULL, NULL, NULL),
(35, 1, 6, '2026-2027', 1, '2026-04-09 04:21:02', 'dropped', NULL, NULL, NULL),
(36, 1, 6, '2026-2027', 1, '2026-04-09 04:22:33', 'dropped', NULL, NULL, NULL),
(37, 1, 6, '2026-2027', 1, '2026-04-09 04:31:39', 'dropped', NULL, NULL, NULL),
(38, 1, 6, '2026-2027', 1, '2026-04-09 04:32:16', 'dropped', NULL, NULL, NULL),
(39, 1, 6, '2026-2027', 1, '2026-04-09 04:32:59', 'dropped', NULL, NULL, NULL),
(40, 1, 6, '2026-2027', 1, '2026-04-09 04:38:08', 'dropped', NULL, NULL, NULL),
(41, 1, 6, '2026-2027', 1, '2026-04-09 04:40:53', 'dropped', NULL, NULL, NULL),
(42, 1, 6, '2026-2027', 1, '2026-04-09 04:45:37', 'dropped', NULL, NULL, NULL),
(43, 1, 6, '2026-2027', 1, '2026-04-09 04:46:07', 'dropped', NULL, NULL, NULL),
(44, 1, 6, '2026-2027', 1, '2026-04-09 04:49:11', 'confirmed', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `exam_schedules`
--

CREATE TABLE `exam_schedules` (
  `schedule_id` int(11) NOT NULL,
  `exam_date` date NOT NULL,
  `exam_time` varchar(50) NOT NULL,
  `location` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_schedules`
--

INSERT INTO `exam_schedules` (`schedule_id`, `exam_date`, `exam_time`, `location`, `notes`, `created_by`, `created_at`) VALUES
(1, '2026-03-16', '5:00 AM', 'doon lang', 'punta ka na', 2, '2026-03-30 04:55:54');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `faculty_id` int(11) NOT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
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
  `mailing_same_as_permanent` tinyint(1) DEFAULT 1,
  `mailing_region` varchar(100) DEFAULT NULL,
  `mailing_province` varchar(100) DEFAULT NULL,
  `mailing_municipality` varchar(100) DEFAULT NULL,
  `mailing_barangay` varchar(100) DEFAULT NULL,
  `mailing_address` varchar(255) DEFAULT NULL,
  `mailing_zip_code` varchar(10) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `grades` (
  `grade_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `grade` varchar(5) NOT NULL,
  `status` varchar(20) NOT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_entries`
--

CREATE TABLE `grade_entries` (
  `entry_id` int(11) NOT NULL,
  `enrollment_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_standing` decimal(5,2) DEFAULT NULL,
  `quiz` decimal(5,2) DEFAULT NULL,
  `midterms` decimal(5,2) DEFAULT NULL,
  `finals` decimal(5,2) DEFAULT NULL,
  `computed_grade` decimal(5,2) GENERATED ALWAYS AS (round(coalesce(`class_standing`,0) * 0.30 + coalesce(`quiz`,0) * 0.30 + coalesce(`midterms`,0) * 0.20 + coalesce(`finals`,0) * 0.20,2)) STORED,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grade_entries`
--

INSERT INTO `grade_entries` (`entry_id`, `enrollment_id`, `class_id`, `student_id`, `class_standing`, `quiz`, `midterms`, `finals`, `updated_at`) VALUES
(1, 2, 1, 2, 90.00, 80.00, 89.00, 80.00, '2026-04-01 08:10:29'),
(2, 44, 6, 1, 100.00, 100.00, 100.00, 44.00, '2026-04-09 05:14:20');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `token` varchar(64) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `registrations` (
  `registration_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `portal` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
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
  `year_level` int(11) DEFAULT NULL,
  `block_id` int(11) DEFAULT NULL COMMENT 'Foreign key to blocks table',
  `password` varchar(255) NOT NULL,
  `account_status` enum('active','inactive') DEFAULT 'active',
  `profile_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `suffix_name` varchar(20) DEFAULT NULL,
  `registration_status` enum('Regular','Irregular') DEFAULT 'Regular',
  `status` varchar(50) NOT NULL DEFAULT 'Not Enrolled'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `student_number`, `first_name`, `last_name`, `middle_name`, `gender`, `birthdate`, `email`, `contact_number`, `college`, `course`, `year_level`, `block_id`, `password`, `account_status`, `profile_photo`, `created_at`, `suffix_name`, `registration_status`, `status`) VALUES
(1, '202412685', 'John Louie', 'Muncada', 'Lopez', 'Male', '2005-11-17', 'xsurethingx@gmail.com', '09543447352', 'CE', 'BSCpE', 1, 1, '$2y$10$Bz0gMIypT.CH4I4Ab64cvO7pI0VWooeHYxByvmwYTcBQ58VEWPHOq', 'active', 'uploads/69c1b0f24fe16_Y0_STEAM_WALLPAPER_3_1600X900.jpg', '2026-03-04 09:48:58', '', 'Regular', 'Enrolled'),
(2, '2024-1244', 'John Louie', 'Muncada', 'Lopez', '', '2005-11-17', 'vnoir17@gmail.com', '09543447352', 'CE', 'BSCpE', 1, 1, '$2y$10$XWXT31AObvg0Q1DnXjd2EO2u99ctm6bpgMvIvNcA8/sHju2akx296', 'active', NULL, '2026-03-27 04:02:32', '', '', 'Not Enrolled'),
(3, 'b,', 'Louie', 'Muncada', 'Lopez', '', '2005-11-17', 'softdevset@gmail.com', '09543447352', 'b,', 'BSCpE', 1, 1, '$2y$10$pJSk16nv8F.kfnnHswcWlenESYXo7ZveToupT6iHEk84AsRn2pOwS', 'active', NULL, '2026-04-08 16:15:26', '', '', 'Not Enrolled');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `subject_code` varchar(20) NOT NULL COMMENT 'e.g., CS101, IT201',
  `subject_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `units` int(11) NOT NULL DEFAULT 3 COMMENT 'Credit units',
  `lecture_hours` decimal(3,1) DEFAULT 3.0,
  `lab_hours` decimal(3,1) DEFAULT 0.0,
  `department` varchar(100) DEFAULT NULL,
  `year_level` enum('1','2','3','4') DEFAULT NULL COMMENT 'Recommended year level',
  `semester` enum('1st','2nd','summer') DEFAULT NULL COMMENT 'Recommended semester',
  `prerequisite` varchar(255) DEFAULT NULL COMMENT 'Prerequisite subject codes',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `hours` int(11) GENERATED ALWAYS AS (`lecture_hours` + `lab_hours`) VIRTUAL,
  `schedule` varchar(255) DEFAULT NULL,
  `faculty_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `course_id`, `subject_code`, `subject_name`, `description`, `units`, `lecture_hours`, `lab_hours`, `department`, `year_level`, `semester`, `prerequisite`, `status`, `created_at`, `updated_at`, `schedule`, `faculty_id`) VALUES
(500, 10, 'CBM 0016', 'The Entrepreneurial Mind', NULL, 3, 3.0, 0.0, 'CBM', '1', '1st', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(501, 10, 'STS 0002', 'Science, Technology and Society', NULL, 3, 3.0, 0.0, 'STS', '1', '1st', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(502, 10, 'ECO 3105', 'Managerial Economics', NULL, 3, 3.0, 0.0, 'ECO', '1', '1st', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(503, 10, 'PCM 0006', 'Purposive Communication', NULL, 3, 3.0, 0.0, 'PCM', '1', '1st', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(504, 10, 'ACN 1101', 'Financial Accounting and Reporting', NULL, 3, 3.0, 0.0, 'ACN', '1', '1st', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(505, 10, 'CBM 0018', 'Mathematics of Investment', NULL, 3, 3.0, 0.0, 'CBM', '1', '1st', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(506, 10, 'AAP 0007', 'Art Appreciation', NULL, 3, 3.0, 0.0, 'AAP', '1', '1st', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(507, 10, 'IPP 0010', 'Interdisiplinaryong Pagbasa at Pagsulat Tungo', NULL, 3, 3.0, 0.0, 'IPP', '1', '1st', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(508, 10, 'NSTP 01', 'National Service Training Program 1-(ROTC/CWTS/LITCY', NULL, 3, 3.0, 0.0, 'NSTP', '1', '1st', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(509, 10, 'PED 0001', 'Foundation of Physical Activities', NULL, 2, 2.0, 0.0, 'PED', '1', '1st', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(510, 10, 'RVA 123', 'Reading Visual Arts', NULL, 3, 3.0, 0.0, 'RVA', '1', '2nd', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(511, 10, 'MMW 0001', 'Mathematics in the Modern World', NULL, 3, 3.0, 0.0, 'MMW', '1', '2nd', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(512, 10, 'ACN 3222', 'Professional Elective 1 (Partnership)', NULL, 3, 3.0, 0.0, 'ACN', '1', '2nd', 'ACN 1101', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(513, 10, 'ACN 4127', 'Professional Electives 2 (Corporation)', NULL, 3, 3.0, 0.0, 'ACN', '1', '2nd', 'ACN 1101', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(514, 10, 'CBM 0011', 'Operations Management (TQM)', NULL, 3, 3.0, 0.0, 'CBM', '1', '2nd', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(515, 10, 'ECO 4112', 'Economic Development', NULL, 3, 3.0, 0.0, 'ECO', '1', '2nd', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(516, 10, 'UTS 0003', 'Understanding the Self', NULL, 3, 3.0, 0.0, 'UTS', '1', '2nd', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(517, 10, 'ETH 0008', 'Ethics', NULL, 3, 3.0, 0.0, 'ETH', '1', '2nd', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(518, 10, 'NSTP 02', 'National Service Training Program 1- (ROTC/CWTS/LITCY)', NULL, 3, 3.0, 0.0, 'NSTP', '1', '2nd', 'NSTP 11', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(519, 10, 'PED 0002', 'Foundation of Physical Education', NULL, 2, 2.0, 0.0, 'PED', '1', '2nd', 'PED 0001', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(520, 10, 'ACN 0003', 'Business Logic', NULL, 3, 3.0, 0.0, 'ACN', '2', '1st', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(521, 10, 'ECO 0015', 'Management Science', NULL, 3, 3.0, 0.0, 'ECO', '2', '1st', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(522, 10, 'TCW 0005', 'The Contemporary World', NULL, 3, 3.0, 0.0, 'TCW', '2', '1st', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(523, 10, 'ACN 1203', 'Intermediate Accounting I', NULL, 3, 3.0, 0.0, 'ACN', '2', '1st', 'ACN 3222/4127', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(524, 10, 'ACN 1202', 'Conceptual Framework and Accounting Standard', NULL, 3, 3.0, 0.0, 'ACN', '2', '1st', 'ACN 3222/4127', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(525, 10, 'LWR 0009', 'Life and Works of Rizal', NULL, 3, 3.0, 0.0, 'LWR', '2', '1st', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(526, 10, 'RPH 0004', 'Readings in Philippine History', NULL, 3, 3.0, 0.0, 'RPH', '2', '1st', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(527, 10, 'FIN 0013', 'Financial Markets', NULL, 3, 3.0, 0.0, 'FIN', '2', '1st', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(528, 10, 'PED 0033', 'Arnis', NULL, 2, 2.0, 0.0, 'PED', '2', '1st', 'PE 0002', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(529, 10, 'ECO 0014', 'Statistical Analysis with Software Application', NULL, 3, 3.0, 0.0, 'ECO', '2', '2nd', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(530, 10, 'ACN 2212', 'Governance, Business Ethics, Risk Management and Internal Control', NULL, 3, 3.0, 0.0, 'ACN', '2', '2nd', 'ACN 1202/1203', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(531, 10, 'ACN 2105', 'Intermediate Accounting 2', NULL, 2, 2.0, 0.0, 'ACN', '2', '2nd', 'ACN 1202/1203', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(532, 10, 'ACN 4128', 'Professional Elective 3', NULL, 3, 3.0, 0.0, 'ACN', '2', '2nd', 'ACN 1202/1203', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(533, 10, 'ACN 2108', 'IT Application Tools in Business', NULL, 3, 3.0, 0.0, 'ACN', '2', '2nd', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(534, 10, 'CBM 0015A', 'International Business and Trade', NULL, 3, 3.0, 0.0, 'CBM', '2', '2nd', 'ACN 1202/1203', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(535, 10, 'ACN 2107', 'Financial Management', NULL, 2, 2.0, 0.0, 'ACN', '2', '2nd', 'PARCOR', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(536, 10, 'PED 0043', 'Badminton', NULL, 2, 2.0, 0.0, 'PED', '2', '2nd', 'PED 0003', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(537, 10, 'ACN 3118', 'Accounting for Specialized Transactions', NULL, 3, 3.0, 0.0, 'ACN', '3', '1st', 'ACN 2105', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(538, 10, 'ACN 2213', 'Accounting Information System', NULL, 3, 3.0, 0.0, 'ACN', '3', '1st', 'ACN 2108', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(539, 10, 'ACN 2211', 'Intermediate Accounting 3', NULL, 3, 3.0, 0.0, 'ACN', '3', '1st', 'ACN 2105', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(540, 10, 'ACN 2209', 'Income Taxation', NULL, 3, 3.0, 0.0, 'ACN', '3', '1st', 'PARCOR', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(541, 10, 'ACN 2104', 'Cost Accounting & Control', NULL, 3, 3.0, 0.0, 'ACN', '3', '1st', 'ACN2105/4128', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(542, 10, 'ACN 4127A', 'Professional Elective 4 (Principles and Methods of Teaching Accounting)', NULL, 3, 3.0, 0.0, 'ACN', '3', '1st', 'ACN2105/4128', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(543, 10, 'ACN 2106', 'Law on Obligations and Contracts', NULL, 3, 3.0, 0.0, 'ACN', '3', '1st', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(544, 10, 'ACN 2210A', 'Regulatory Framework and Legal Issues in Business', NULL, 6, 6.0, 0.0, 'ACN', '3', '2nd', 'ACN 2106', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(545, 10, 'ACN 2214', 'Strategic Cost Management', NULL, 3, 3.0, 0.0, 'ACN', '3', '2nd', 'ACN 2104', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(546, 10, 'ACN 3119', 'Accounting for Business Combinations', NULL, 3, 3.0, 0.0, 'ACN', '3', '2nd', 'ACN 3118', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(547, 10, 'ACN 3116', 'Auditing and Assurance Principles', NULL, 3, 3.0, 0.0, 'ACN', '3', '2nd', 'ACN 2211/2212', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(548, 10, 'ACN 3121', 'Strategic Business Analysis', NULL, 3, 3.0, 0.0, 'ACN', '3', '2nd', 'ACN 2104', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(549, 10, 'ACN 3120', 'Business Taxation', NULL, 3, 3.0, 0.0, 'ACN', '3', '2nd', 'ACN 2209', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(550, 10, 'ACN 3226', 'Auditing in a CIS Environment', NULL, 3, 3.0, 0.0, 'ACN', '3', '2nd', 'ACN 2213', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(551, 10, 'ACN 3117', 'Auditing and Assurance Principle: Concepts and Application 1', NULL, 3, 3.0, 0.0, 'ACN', '4', '1st', 'ACN 3116', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(552, 10, 'ACN 3224', 'Auditing and Assurance: Concepts and Application 2', NULL, 3, 3.0, 0.0, 'ACN', '4', '1st', 'ACN 3116', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(553, 10, 'ACN 4129', 'Accounting Research Methods', NULL, 3, 3.0, 0.0, 'ACN', '4', '1st', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(554, 10, 'ACN 3225', 'Auditing and Assurance: Specialized Industries', NULL, 3, 3.0, 0.0, 'ACN', '4', '1st', 'ACN 3116', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(555, 10, 'CBM 0012', 'Strategic Management', NULL, 3, 3.0, 0.0, 'CBM', '4', '1st', '', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(556, 10, 'ACN 3115', 'Business Law & Regulations', NULL, 3, 3.0, 0.0, 'ACN', '4', '1st', 'ACN 2210', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(557, 10, 'ACN 3223', 'Accounting for Government and Not for Profit Organization', NULL, 3, 3.0, 0.0, 'ACN', '4', '1st', 'ACN 3119', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(558, 10, 'ACN 3116A', 'Business Law and Regulations 2', NULL, 3, 3.0, 0.0, 'ACN', '4', '2nd', 'ACN 3115', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(559, 10, 'ACN 4231', 'Accountancy Research', NULL, 3, 3.0, 0.0, 'ACN', '4', '2nd', 'ACN 4129', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(560, 10, 'ACN 4232A', 'Accounting Internship (400 hours)', NULL, 6, 6.0, 0.0, 'ACN', '4', '2nd', '80% Course Completed', 'active', '2026-04-08 17:52:44', '2026-04-08 17:52:44', NULL, NULL),
(561, NULL, 'CET 0111', 'Calculus 1', NULL, 3, 3.0, 0.0, 'CET', '1', '1st', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(562, NULL, 'CET 0112', 'Chemistry for Engineers', NULL, 3, 3.0, 0.0, 'CET', '1', '1st', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(563, NULL, 'CET 0112.1', 'Chemistry for Engineers', NULL, 1, 0.0, 1.0, 'CET', '1', '1st', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(564, NULL, 'CIV 0111', 'Civil Engineering Orientation', NULL, 2, 2.0, 0.0, 'CIV', '1', '1st', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(565, NULL, 'MMW 001', 'Mathematics in the Modern World', NULL, 3, 3.0, 0.0, 'MMW', '1', '1st', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(566, NULL, 'STS 002', 'Science, Technology and Society', NULL, 3, 3.0, 0.0, 'STS', '1', '1st', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(567, NULL, 'ITE 0001', 'Living in the IT ERA', NULL, 3, 3.0, 0.0, 'ITE', '1', '1st', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(568, NULL, 'PED 0001', 'Foundation of Physical Activities', NULL, 2, 2.0, 0.0, 'PED', '1', '1st', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(569, NULL, 'NSTP 1', 'National Service Training Program - ROTC 1/CWTS 1', NULL, 3, 3.0, 0.0, 'NSTP', '1', '1st', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(570, NULL, 'CET 0121', 'Calculus 2', NULL, 2, 2.0, 0.0, 'CET', '1', '2nd', 'CET 0111', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(571, NULL, 'CET 0122A', 'Physics for Engineers', NULL, 4, 4.0, 0.0, 'CET', '1', '2nd', 'CET 0111, CET 0121', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(572, NULL, 'CET 0122.1', 'Physics for Engineers', NULL, 1, 0.0, 1.0, 'CET', '1', '2nd', 'CET 0122', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(573, NULL, 'CIV 0121', 'Engineering Drawing and Plans', NULL, 1, 1.0, 0.0, 'CIV', '1', '2nd', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(574, NULL, 'EIT 0121.1', 'Computer Fundamentals and Programming 1', NULL, 1, 1.0, 0.0, 'EIT', '1', '2nd', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(575, NULL, 'UTS 0003', 'Understanding the Self', NULL, 3, 3.0, 0.0, 'UTS', '1', '2nd', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(576, NULL, 'TCW 0005', 'The Contemporary World', NULL, 3, 3.0, 0.0, 'TCW', '1', '2nd', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(577, NULL, 'PCM 0006', 'Purposive Communication', NULL, 3, 3.0, 0.0, 'PCM', '1', '2nd', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(578, NULL, 'IPP 0010A', 'Interdisiplinaryong Pagbasa at Pagsulat sa mga Diskurso ng Pagpapahayag', NULL, 3, 3.0, 0.0, 'IPP', '1', '2nd', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(579, NULL, 'PED 0012', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'PED', '1', '2nd', 'PED 0001', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(580, NULL, 'NSTP2', 'National Service Training Program - ROTC 1/CWTS 1', NULL, 3, 3.0, 0.0, 'NSTP', '1', '2nd', 'NSTP1', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(581, NULL, 'CET 0214', 'Statics of Rigid Bodies', NULL, 3, 3.0, 0.0, 'CET', '2', '1st', 'CET 0121, CET 0122', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(582, NULL, 'CET 0216', 'Engineering Economics', NULL, 3, 3.0, 0.0, 'CET', '2', '1st', '2nd Year Standing', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(583, NULL, 'CIV 0211A', 'Fundamentals of Surveying', NULL, 4, 4.0, 0.0, 'CIV', '2', '1st', 'CIV 0121', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(584, NULL, 'CIV 0211.1', 'Fundamentals of Surveying', NULL, 1, 0.0, 1.0, 'CIV', '2', '1st', 'CIV 0211', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(585, NULL, 'EIT 0211.1', 'Computer Fundamentals and Programming 2', NULL, 1, 1.0, 0.0, 'EIT', '2', '1st', 'EIT 0121.1', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(586, NULL, 'ETH 0008', 'Ethics', NULL, 3, 3.0, 0.0, 'ETH', '2', '1st', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(587, NULL, 'LWR 0009', 'Life and Works of Rizal', NULL, 3, 3.0, 0.0, 'LWR', '2', '1st', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(588, NULL, 'RPH 0004', 'Readings in Philippine History', NULL, 3, 3.0, 0.0, 'RPH', '2', '1st', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(589, NULL, 'PED 0013', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'PED', '2', '1st', 'PED 0001', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(590, NULL, 'CET 0123.1', 'Computer-Aided Drafting', NULL, 1, 1.0, 0.0, 'CET', '2', '2nd', 'CIV 0121', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(591, NULL, 'CET 0211', 'Differential Equations', NULL, 3, 3.0, 0.0, 'CET', '2', '2nd', 'CET 0121', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(592, NULL, 'CET 0212', 'Engineering Data Analysis', NULL, 3, 3.0, 0.0, 'CET', '2', '2nd', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(593, NULL, 'CET 0221', 'Engineering Management', NULL, 2, 2.0, 0.0, 'CET', '2', '2nd', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(594, NULL, 'CET 0223', 'Dynamics of Rigid Bodies', NULL, 2, 2.0, 0.0, 'CET', '2', '2nd', 'CET 0214', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(595, NULL, 'CIV 0221', 'Geology for Civil Engineers', NULL, 2, 2.0, 0.0, 'CIV', '2', '2nd', 'CET 0112', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(596, NULL, 'CIV 0222', 'Mechanics of Deformable Bodies', NULL, 4, 4.0, 0.0, 'CIV', '2', '2nd', 'CET 0214', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(597, NULL, 'AAP 0007', 'Art Appreciation', NULL, 3, 3.0, 0.0, 'AAP', '2', '2nd', '', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(598, NULL, 'PED 0014', 'PE Elective (12, 13, OR 14)', NULL, 2, 2.0, 0.0, 'PED', '2', '2nd', 'PED 001', 'active', '2026-04-08 17:52:50', '2026-04-08 17:52:50', NULL, NULL),
(599, NULL, 'GTB 121', 'Great Books', NULL, 3, 3.0, 0.0, 'GTB', '3', '1st', '', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(600, NULL, 'CIV 0311', 'Highway and Railroad Engineering', NULL, 3, 3.0, 0.0, 'CIV', '3', '1st', 'CIV 0211', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(601, NULL, 'CIV 0312', 'Hydrology', NULL, 2, 2.0, 0.0, 'CIV', '3', '1st', '', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(602, NULL, 'CIV 0313', 'Numerical Solutions to CE Problems', NULL, 2, 2.0, 0.0, 'CIV', '3', '1st', 'CET 0121', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(603, NULL, 'CIV 0313.1', 'Numerical Solutions to CE Problems', NULL, 1, 0.0, 1.0, 'CIV', '3', '1st', 'CET 0313', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(604, NULL, 'CIV 0314', 'Structural Theory', NULL, 3, 3.0, 0.0, 'CIV', '3', '1st', 'CIV 0222', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(605, NULL, 'CIV 0314.1', 'Structural Theory', NULL, 1, 0.0, 1.0, 'CIV', '3', '1st', 'CET 0314', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(606, NULL, 'ELE 0318', 'Engineering Utilities 1', NULL, 3, 3.0, 0.0, 'ELE', '3', '1st', 'CET 0122', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(607, NULL, 'MEC 0317', 'Engineering Utilities 2', NULL, 3, 3.0, 0.0, 'MEC', '3', '1st', 'CET 0122', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(608, NULL, 'CET 0411', 'Technopreneurship 101', NULL, 3, 3.0, 0.0, 'CET', '3', '2nd', '3rd Year Standing', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(609, NULL, 'CIV 0321', 'Building Systems Design', NULL, 2, 2.0, 0.0, 'CIV', '3', '2nd', 'CIV 0121', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(610, NULL, 'CIV 0321.1', 'Building Systems Design', NULL, 1, 0.0, 1.0, 'CIV', '3', '2nd', 'CIV 0321', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(611, NULL, 'CIV 0322', 'Civil Engineering Law, Ethics and Contracts', NULL, 2, 2.0, 0.0, 'CIV', '3', '2nd', '3rd Year Standing', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(612, NULL, 'CIV 0323A', 'Hydraulics', NULL, 5, 5.0, 0.0, 'CIV', '3', '2nd', 'CET 0223', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(613, NULL, 'CIV 0323.1', 'Hydraulics', NULL, 1, 0.0, 1.0, 'CIV', '3', '2nd', 'CIV 0323', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(614, NULL, 'CIV 0324', 'Principles of Reinforced and Prestressed Concrete', NULL, 3, 3.0, 0.0, 'CIV', '3', '2nd', 'CIV 0314', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(615, NULL, 'CIV 0324.1', 'Principles of Reinforced and Prestressed Concrete', NULL, 1, 0.0, 1.0, 'CIV', '3', '2nd', 'CIV 0324', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(616, NULL, 'CIV 0325', 'Principles of Transportation Engineering', NULL, 3, 3.0, 0.0, 'CIV', '3', '2nd', 'CIV 0311', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(617, NULL, 'CIV 0331A', 'On-the-Job Training -240 hrs', NULL, 3, 3.0, 0.0, 'CIV', '3', 'summer', 'Incoming 4th Year Standing', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(618, NULL, 'CIV 0331.1', 'On-the-Job Training -240 hrs', NULL, 1, 0.0, 1.0, 'CIV', '3', 'summer', 'CIV 0331', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(619, NULL, 'PPC 122', 'Philippine Popular Culture', NULL, 3, 3.0, 0.0, 'PPC', '4', '1st', '', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(620, NULL, 'CIV 0411', 'CE Project 1', NULL, 1, 1.0, 0.0, 'CIV', '4', '1st', '4th Year Standing', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(621, NULL, 'CIV 0411.1', 'CE Project 1', NULL, 1, 0.0, 1.0, 'CIV', '4', '1st', 'CIV 0411', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(622, NULL, 'CIV 0412A', 'Geotechnical Engineering 1 - Soil Mechanics', NULL, 4, 4.0, 0.0, 'CIV', '4', '1st', 'CIV 0221, CIV 0222', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(623, NULL, 'CIV 0412.1', 'Geotechnical Engineering 1 - Soil Mechanics', NULL, 1, 0.0, 1.0, 'CIV', '4', '1st', 'CIV 0412', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(624, NULL, 'CIV 0413', 'Quantity Surveying', NULL, 1, 1.0, 0.0, 'CIV', '4', '1st', 'CIV 0321', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(625, NULL, 'CIV 0413.1', 'Quantity Surveying', NULL, 1, 0.0, 1.0, 'CIV', '4', '1st', 'CIV 0413', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(626, NULL, 'CIV 0414', 'Principles of Steel Design', NULL, 2, 2.0, 0.0, 'CIV', '4', '1st', 'CIV 0314', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(627, NULL, 'CIV 0414.1', 'Principles of Steel Design', NULL, 1, 0.0, 1.0, 'CIV', '4', '1st', 'CIV 0414', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(628, NULL, 'CIVCM 0415', 'Construction Cost Engineering', NULL, 3, 3.0, 0.0, 'CIVCM', '4', '1st', '4th Year Standing', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(629, NULL, 'CIVCM 0416', 'Advanced Construction Methods and Equipment', NULL, 3, 3.0, 0.0, 'CIVCM', '4', '1st', '4th Year Standing', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(630, NULL, 'CIV 0421', 'CE Project 2', NULL, 1, 1.0, 0.0, 'CIV', '4', '2nd', 'CIV 0411', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(631, NULL, 'CIV 0421.1', 'CE Project 2', NULL, 1, 0.0, 1.0, 'CIV', '4', '2nd', 'CIV 0421', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(632, NULL, 'CIV 0422', 'Construction Methods and Project Management', NULL, 3, 3.0, 0.0, 'CIV', '4', '2nd', '4th Year Standing', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(633, NULL, 'CIV 0423', 'Construction Materials and Testing', NULL, 3, 3.0, 0.0, 'CIV', '4', '2nd', 'CIV 0222', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(634, NULL, 'CIV 0423.1', 'Construction Materials and Testing', NULL, 2, 0.0, 2.0, 'CIV', '4', '2nd', 'CIV 0423', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(635, NULL, 'CIVCM 0424', 'Project Construction and Management', NULL, 3, 3.0, 0.0, 'CIVCM', '4', '2nd', '4th Year Standing', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(636, NULL, 'CIVCM 0425', 'Total Quantity Management', NULL, 3, 3.0, 0.0, 'CIVCM', '4', '2nd', '4th Year Standing', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(637, NULL, 'CIVCM 0426', 'Construction Occupational Safety and Health', NULL, 3, 3.0, 0.0, 'CIVCM', '4', '2nd', '4th Year Standing', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(638, NULL, 'CIV 0427.1', 'Special Topics in Civil Engineering', NULL, 1, 1.0, 0.0, 'CIV', '4', '2nd', 'Graduating Status', 'active', '2026-04-08 17:52:51', '2026-04-08 17:52:51', NULL, NULL),
(639, NULL, 'CIVST 0415', 'Matrix Analysis of Structures', NULL, 3, 3.0, 0.0, 'CIVST', '4', '1st', '4th Year Standing', 'active', '2026-04-08 17:52:57', '2026-04-08 17:52:57', NULL, NULL),
(640, NULL, 'CIVST 0416', 'Earthquake Engineering', NULL, 3, 3.0, 0.0, 'CIVST', '4', '1st', '4th Year Standing', 'active', '2026-04-08 17:52:57', '2026-04-08 17:52:57', NULL, NULL),
(641, NULL, 'CIVST 0424', 'Structural Design of Towers and Other Vertical Structures', NULL, 3, 3.0, 0.0, 'CIVST', '4', '2nd', '4th Year Standing', 'active', '2026-04-08 17:52:57', '2026-04-08 17:52:57', NULL, NULL),
(642, NULL, 'CIVST 0425', 'Bridge Engineering', NULL, 3, 3.0, 0.0, 'CIVST', '4', '2nd', '4th Year Standing', 'active', '2026-04-08 17:52:57', '2026-04-08 17:52:57', NULL, NULL),
(643, NULL, 'CIVST 0426', 'Foundation and Retaining Wall Design', NULL, 3, 3.0, 0.0, 'CIVST', '4', '2nd', '4th Year Standing', 'active', '2026-04-08 17:52:57', '2026-04-08 17:52:57', NULL, NULL),
(644, 1, 'CET 0111', 'Calculus 1', NULL, 3, 3.0, 0.0, 'CET', '1', '1st', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(645, 1, 'CET 0112', 'Chemistry for Engineers', NULL, 3, 3.0, 0.0, 'CET', '1', '1st', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(646, 1, 'CET 0112.1', 'Chemistry for Engineers', NULL, 1, 0.0, 1.0, 'CET', '1', '1st', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(647, 1, 'CET 0113.1', 'Engineering Drawing', NULL, 1, 1.0, 0.0, 'CET', '1', '1st', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(648, 1, 'MMW 0001', 'Mathematics in the Modern World', NULL, 3, 3.0, 0.0, 'MMW', '1', '1st', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(649, 1, 'RPH 0004', 'Readings in Philippine History', NULL, 3, 3.0, 0.0, 'RPH', '1', '1st', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(650, 1, 'UTS 0003', 'Understanding the Self', NULL, 3, 3.0, 0.0, 'UTS', '1', '1st', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(651, 1, 'LWR 0009', 'Life and Works of Rizal', NULL, 3, 3.0, 0.0, 'LWR', '1', '1st', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(652, 1, 'ITE 0001', 'Living in the IT Era', NULL, 3, 3.0, 0.0, 'ITE', '1', '1st', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(653, 1, 'PED 0001', 'Foundation of Physical Activities', NULL, 2, 2.0, 0.0, 'PED', '1', '1st', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(654, 1, 'NSTP 01', 'National Service Training Program 2-ROTC 1/CWTS 1', NULL, 3, 3.0, 0.0, 'NSTP', '1', '1st', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(655, 1, 'CHE 0121', 'Analytical Chemistry', NULL, 4, 4.0, 0.0, 'CHE', '1', '2nd', 'CET 0112', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(656, 1, 'CHE 0121.1', 'Analytical Chemistry', NULL, 1, 0.0, 1.0, 'CHE', '1', '2nd', 'CET 0112.1, CHE 0121', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(657, 1, 'CET 0121', 'Calculus 2', NULL, 3, 3.0, 0.0, 'CET', '1', '2nd', 'CET 0111', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(658, 1, 'CET 0122A', 'Physics for Engineers', NULL, 4, 4.0, 0.0, 'CET', '1', '2nd', 'CET 0111', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(659, 1, 'CET 0122.1', 'Physics for Engineers', NULL, 1, 0.0, 1.0, 'CET', '1', '2nd', 'CET 0111, CET 0122', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(660, 1, 'PCM 0006', 'Purposive Communication', NULL, 3, 3.0, 0.0, 'PCM', '1', '2nd', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(661, 1, 'AAP 0007', 'Art Appreciation', NULL, 3, 3.0, 0.0, 'AAP', '1', '2nd', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(662, 1, 'IPP 0010A', 'Interdisiplinaryong Pagbasa at Pagsulat sa', NULL, 3, 3.0, 0.0, 'IPP', '1', '2nd', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(663, 1, 'PED 0012', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'PED', '1', '2nd', 'PED 0001', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(664, 1, 'NSTP 02', 'National Service Training Program 2-ROTC 2/CWTS 2', NULL, 3, 3.0, 0.0, 'NSTP', '1', '2nd', 'NSTP 1', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(665, 1, 'CHE 0211', 'Organic Chemistry', NULL, 4, 4.0, 0.0, 'CHE', '2', '1st', 'CHE 0121', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(666, 1, 'CHE 0211.1', 'Organic Chemistry', NULL, 1, 0.0, 1.0, 'CHE', '2', '1st', 'CHE 0121.1, CET 0211', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(667, 1, 'CHE 0212', 'Chemical Engineering Calculations', NULL, 2, 2.0, 0.0, 'CHE', '2', '1st', 'CHE 0121', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(668, 1, 'CHE 0212.1', 'Chemical Engineering Calculations', NULL, 1, 0.0, 1.0, 'CHE', '2', '1st', 'CHE 0121', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(669, 1, 'CET 0211', 'Differential Equations', NULL, 3, 3.0, 0.0, 'CET', '2', '1st', 'CET 0121', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(670, 1, 'CET 0212', 'Engineering Data Analysis', NULL, 3, 3.0, 0.0, 'CET', '2', '1st', 'CET 0111', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(671, 1, 'CET 0213A', 'Engineering Mechanics', NULL, 4, 4.0, 0.0, 'CET', '2', '1st', 'CET 0122', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(672, 1, 'EIT 0121.1', 'Computer Fundamentals and Programming 1', NULL, 1, 1.0, 0.0, 'EIT', '2', '1st', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(673, 1, 'PPC 122', 'Philippine Popular Culture', NULL, 3, 3.0, 0.0, 'PPC', '2', '1st', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(674, 1, 'PED 0093', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'PED', '2', '1st', 'PED 0001', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(675, 1, 'CHE 0221', 'Physical Chemistry for Engineers 1', NULL, 2, 2.0, 0.0, 'CHE', '2', '2nd', 'CET 0211, CHE 0211', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(676, 1, 'CHE 0221.1', 'Physical Chemistry for Engineers 1', NULL, 1, 0.0, 1.0, 'CHE', '2', '2nd', 'CHE 0221', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(677, 1, 'CHE 0222', 'Momentum Transfer', NULL, 2, 2.0, 0.0, 'CHE', '2', '2nd', 'CHE 0212, CET 0211', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(678, 1, 'CHE 0222.1', 'Momentum Transfer', NULL, 1, 0.0, 1.0, 'CHE', '2', '2nd', 'CHE 0222', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(679, 1, 'CHE 0223', 'Advanced Engineering Mathematics in CHE', NULL, 3, 3.0, 0.0, 'CHE', '2', '2nd', 'CET 0211', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(680, 1, 'CET 0123.1', 'Computer-Aided Drafting', NULL, 1, 1.0, 0.0, 'CET', '2', '2nd', 'CET 0113.1', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(681, 1, 'CET 0222', 'Fundamentals of Material Science and', NULL, 3, 3.0, 0.0, 'CET', '2', '2nd', 'CHE 0211', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(682, 1, 'STS 0002', 'Science, Technology and Society', NULL, 3, 3.0, 0.0, 'STS', '2', '2nd', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(683, 1, 'ELE 0229', 'Basic Electrical and Electronics Engineering', NULL, 2, 2.0, 0.0, 'ELE', '2', '2nd', 'CET 0122', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(684, 1, 'ELE 0229.1', 'Basic Electrical and Electronics Engineering', NULL, 1, 0.0, 1.0, 'ELE', '2', '2nd', 'CET 0122.1, ELE 0229', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(685, 1, 'GTB 121', 'Great Books', NULL, 3, 3.0, 0.0, 'GTB', '2', '2nd', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(686, 1, 'PED 0014', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'PED', '2', '2nd', 'PED 0001', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(687, 1, 'CHE 0311', 'Physical Chemistry for Engineers 2', NULL, 2, 2.0, 0.0, 'CHE', '3', '1st', 'CHE 0221', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(688, 1, 'CHE 0311.1', 'Physical Chemistry for Engineers 2', NULL, 1, 0.0, 1.0, 'CHE', '3', '1st', 'CHE 0221.1, CHE 0311', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(689, 1, 'CHE 0312', 'Chemical Engineering Thermodynamics', NULL, 2, 2.0, 0.0, 'CHE', '3', '1st', 'CHE 0212, CHE 0221', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(690, 1, 'CHE 0312.1', 'Chemical Engineering Thermodynamics', NULL, 1, 0.0, 1.0, 'CHE', '3', '1st', 'CHE 0312', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(691, 1, 'CHE 0313', 'Heat and Mass Transfer', NULL, 3, 3.0, 0.0, 'CHE', '3', '1st', 'CHE 0222', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(692, 1, 'CHE 0313.1', 'Heat and Mass Transfer', NULL, 1, 0.0, 1.0, 'CHE', '3', '1st', 'CHE 0313', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(693, 1, 'CHE 0314.1', 'Computer Application in CHE', NULL, 1, 1.0, 0.0, 'CHE', '3', '1st', 'CHE 0223, EIT 0211.1', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(694, 1, 'CHE 0315', 'Environmental Science and Engineering', NULL, 3, 3.0, 0.0, 'CHE', '3', '1st', 'CET 0112', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(695, 1, 'CHE 0316.1', 'Methods of Research', NULL, 1, 1.0, 0.0, 'CHE', '3', '1st', 'CHE 0212, CHE 0221', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(696, 1, 'CHE 0317', 'Chemical Process Industries', NULL, 3, 3.0, 0.0, 'CHE', '3', '1st', 'CHE 0211, CHE 0222, CET 0222', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(697, 1, 'ETH 0008', 'Ethics', NULL, 3, 3.0, 0.0, 'ETH', '3', '1st', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(698, 1, 'CHE 0321', 'Solution Thermodynamics', NULL, 2, 2.0, 0.0, 'CHE', '3', '2nd', 'CHE 0223, CHE 0312, CHE 0314.1', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(699, 1, 'CHE 0321.1', 'Solution Thermodynamics', NULL, 1, 0.0, 1.0, 'CHE', '3', '2nd', 'CHE 0321', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(700, 1, 'CHE 0322', 'Chemical Reaction Engineering', NULL, 3, 3.0, 0.0, 'CHE', '3', '2nd', 'CHE 0223, CHE 0311, CHE 0313', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(701, 1, 'CHE 0322.1', 'Chemical Reaction Engineering', NULL, 1, 0.0, 1.0, 'CHE', '3', '2nd', 'CHE 0322', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(702, 1, 'CHE 0323', 'Separation Processes', NULL, 2, 2.0, 0.0, 'CHE', '3', '2nd', 'CHE 0312, CHE 0313', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(703, 1, 'CHE 0323.1', 'Separation Processes', NULL, 1, 0.0, 1.0, 'CHE', '3', '2nd', 'CHE 0323', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(704, 1, 'CHE 0324', 'Particle Technology', NULL, 2, 2.0, 0.0, 'CHE', '3', '2nd', 'CHE 0222', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(705, 1, 'CHE 0324.1', 'Particle Technology', NULL, 1, 0.0, 1.0, 'CHE', '3', '2nd', 'CHE 0324', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(706, 1, 'CHE 0325.1', 'Chemical Engineering Laboratory 1', NULL, 1, 0.0, 1.0, 'CHE', '3', '2nd', 'CHE 0222, CHE 0313', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(707, 1, 'CET 0216', 'Engineering Economics', NULL, 3, 3.0, 0.0, 'CET', '3', '2nd', '3rd Year Standing', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(708, 1, 'CHE 0331.1', 'Chemical Engineering Immersion (240 hrs)', NULL, 2, 2.0, 0.0, 'CHE', '3', 'summer', 'CHE 0322, CHE 0323, CHE 0324', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(709, 1, 'CHE 0411', 'Chemical Engineering Design 1', NULL, 1, 1.0, 0.0, 'CHE', '4', '1st', 'CHE 0317, CHE 0322, CHE 0323, CHE 0324', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(710, 1, 'CHE 0411.1', 'Chemical Engineering Design 1', NULL, 2, 0.0, 1.0, 'CHE', '4', '1st', 'CHE 0411', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(711, 1, 'CHE 0412', 'Biochemical Engineering', NULL, 3, 3.0, 0.0, 'CHE', '4', '1st', 'CHE 0211, CHE 0322', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(712, 1, 'CHE 0413', 'Chemical Engineering Laws and Ethics', NULL, 1, 1.0, 0.0, 'CHE', '4', '1st', 'ETH 0008', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(713, 1, 'CHE 0414', 'Process Safety', NULL, 1, 1.0, 0.0, 'CHE', '4', '1st', '4th Year Standing', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(714, 1, 'CHE 0415.1', 'Chemical Engineering Laboratory 2', NULL, 1, 0.0, 1.0, 'CHE', '4', '1st', 'CHE 0325.1', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(715, 1, 'CHE 0416.1', 'Chemical Process Laboratory', NULL, 1, 0.0, 1.0, 'CHE', '4', '1st', 'CHE 0411', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(716, 1, 'CHE 0417', 'Track Specialization 1', NULL, 3, 3.0, 0.0, 'CHE', '4', '1st', '4th Year Standing', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(717, 1, 'CHE 0418.1', 'ChE Thesis 1', NULL, 1, 1.0, 0.0, 'CHE', '4', '1st', 'CHE 0317, CHE 0322, CHE 0323, CHE 0324', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(718, 1, 'CET 0221', 'Engineering Management', NULL, 2, 2.0, 0.0, 'CET', '4', '1st', 'CET 0216', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(719, 1, 'TCW 0005', 'The Contemporary World', NULL, 3, 3.0, 0.0, 'TCW', '4', '1st', '', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(720, 1, 'CHE 0421', 'Chemical Engineering Design 2', NULL, 5, 3.0, 0.0, 'CHE', '4', '2nd', 'CHE 0411', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(721, 1, 'CHE 0422', 'Process Dynamics and Control', NULL, 5, 3.0, 0.0, 'CHE', '4', '2nd', 'CHE 0223', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(722, 1, 'CHE 0423', 'Industrial Waste Management and Control', NULL, 2, 2.0, 0.0, 'CHE', '4', '2nd', 'CHE 0315, CHE 0324', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(723, 1, 'CHE 0424.1', 'Plant Inspection and Seminars', NULL, 3, 3.0, 0.0, 'CHE', '4', '2nd', 'CHE 0411', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(724, 1, 'CHE 0425', 'Track Specialization 2', NULL, 3, 3.0, 0.0, 'CHE', '4', '2nd', '4th Year Standing', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(725, 1, 'CHE 0426', 'Track Specialization 3', NULL, 3, 3.0, 0.0, 'CHE', '4', '2nd', '4th Year Standing', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(726, 1, 'CHE 0428.1', 'ChE Thesis 2', NULL, 1, 1.0, 0.0, 'CHE', '4', '2nd', 'CHE 0418.1', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(727, 1, 'CET 0411A', 'Technopreneurship', NULL, 3, 3.0, 0.0, 'CET', '4', '2nd', '4th Year Standing', 'active', '2026-04-08 17:53:01', '2026-04-08 17:53:01', NULL, NULL),
(728, 5, 'CET 0111', 'Calculus 1', NULL, 3, 3.0, 0.0, 'CET', '1', '1st', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(729, 5, 'CET 0112', 'Chemistry for Engineers', NULL, 3, 3.0, 0.0, 'CET', '1', '1st', 'CET 0112.1', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(730, 5, 'CET 0112.1', 'Chemistry for Engineers', NULL, 1, 0.0, 1.0, 'CET', '1', '1st', 'CET 0112.1', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(731, 5, 'CPE 0111', 'Computer Engineering as a Discipline', NULL, 1, 1.0, 0.0, 'CPE', '1', '1st', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(732, 5, 'CPE 0112.1', 'Programming Logic and Design (Laboratory)', NULL, 2, 0.0, 2.0, 'CPE', '1', '1st', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(733, 5, 'MMW 0001', 'Mathematics in the Modern World', NULL, 3, 3.0, 0.0, 'MMW', '1', '1st', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(734, 5, 'STS 0002', 'Science, Technology and Society', NULL, 3, 3.0, 0.0, 'STS', '1', '1st', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(735, 5, 'UTS 0003', 'Understanding the Self', NULL, 3, 3.0, 0.0, 'UTS', '1', '1st', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(736, 5, 'GTB 121', 'Great Books', NULL, 3, 3.0, 0.0, 'GTB', '1', '1st', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(737, 5, 'PED 0001', 'Foundation of Physical Activities', NULL, 2, 2.0, 0.0, 'PED', '1', '1st', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(738, 5, 'NSTP 01', 'National Service Training Program 1- ROTC 1/ CWTS 1', NULL, 3, 3.0, 0.0, 'NSTP', '1', '1st', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(739, 5, 'CET 0121', 'Calculus 2', NULL, 3, 3.0, 0.0, 'CET', '1', '2nd', 'CET 0111', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(740, 5, 'CET 0122A', 'Physics for Engineers', NULL, 4, 4.0, 0.0, 'CET', '1', '2nd', 'CET 0111, CET 0122.1', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(741, 5, 'CET 0122A.1', 'Physics for Engineers', NULL, 1, 0.0, 1.0, 'CET', '1', '2nd', 'CET 0111, CET 0122', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(742, 5, 'CET 0216', 'Engineering Economics', NULL, 3, 3.0, 0.0, 'CET', '1', '2nd', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(743, 5, 'CPE 0121.1', 'Object Oriented Programming (Laboratory)', NULL, 2, 0.0, 2.0, 'CPE', '1', '2nd', 'CPE 0112.1', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(744, 5, 'CPE 0122', 'Discrete Mathematics', NULL, 3, 3.0, 0.0, 'CPE', '1', '2nd', 'CET 0111', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(745, 5, 'ITE 0001', 'Living in the IT Era', NULL, 3, 3.0, 0.0, 'ITE', '1', '2nd', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(746, 5, 'PCM 0006', 'Purposive Communication', NULL, 3, 3.0, 0.0, 'PCM', '1', '2nd', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(747, 5, 'PED 0043', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'PED', '1', '2nd', 'PED 0001', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(748, 5, 'NSTP 02', 'National Service Training Program 2 - ROTC 2/ CWTS 2', NULL, 3, 3.0, 0.0, 'NSTP', '1', '2nd', 'NSTP 1', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(749, 5, 'CET 0123.1', 'Computer-Aided Drafting', NULL, 1, 1.0, 0.0, 'CET', '2', '1st', '2nd Year Standing', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(750, 5, 'CET 0211', 'Differential Equations', NULL, 3, 3.0, 0.0, 'CET', '2', '1st', 'CET 0121', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(751, 5, 'CET 0212', 'Engineering Data Analysis', NULL, 3, 3.0, 0.0, 'CET', '2', '1st', 'CET 0111', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(752, 5, 'CPE 0211.1', 'Data Structures and Algorithms (Laboratory)', NULL, 2, 0.0, 2.0, 'CPE', '2', '1st', 'CET 0121.1', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(753, 5, 'CPE 0212', 'Fundamentals of Electrical Circuits', NULL, 3, 3.0, 0.0, 'CPE', '2', '1st', 'CET 0122, CPE 0212.1', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(754, 5, 'CPE 0212.1', 'Fundamentals of Electrical Circuits', NULL, 1, 0.0, 1.0, 'CPE', '2', '1st', 'CET 0122, CPE 0212', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(755, 5, 'AAP 0007', 'Art Appreciation', NULL, 3, 3.0, 0.0, 'AAP', '2', '1st', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(756, 5, 'RPH 0004', 'Readings in Philippine History', NULL, 3, 3.0, 0.0, 'RPH', '2', '1st', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(757, 5, 'IPP 0010', 'Interdisiplinaryong Pagbasa at Pagsulat Tungo sa Mabisang Pagpapahayag', NULL, 3, 3.0, 0.0, 'IPP', '2', '1st', 'New Mandated Course', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(758, 5, 'PED 0013', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'PED', '2', '1st', 'PED 0001', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(759, 5, 'CPE 0221', 'Numerical Methods', NULL, 3, 3.0, 0.0, 'CPE', '2', '2nd', 'CET 0211', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(760, 5, 'CPE 0222', 'Software Design', NULL, 3, 3.0, 0.0, 'CPE', '2', '2nd', 'CET 0211.1, CPE 0222.1', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(761, 5, 'CPE 0222.1', 'Software Design', NULL, 1, 0.0, 1.0, 'CPE', '2', '2nd', 'CET 0211.1, CPE 0222', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(762, 5, 'CPE 0223', 'Fundamentals of Electronic Circuits', NULL, 3, 3.0, 0.0, 'CPE', '2', '2nd', 'CPE 0212, CPE 0223.1', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(763, 5, 'CPE 0223.1', 'Fundamentals of Electronic Circuits', NULL, 1, 0.0, 1.0, 'CPE', '2', '2nd', 'CPE 0212, CPE 0223', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(764, 5, 'LWR 0009', 'Life and Works of Rizal', NULL, 3, 3.0, 0.0, 'LWR', '2', '2nd', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(765, 5, 'TCW 0005', 'The Contemporary World', NULL, 3, 3.0, 0.0, 'TCW', '2', '2nd', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(766, 5, 'PPC 122', 'GEC ELECTIVE 3-Philippine Popular Culture', NULL, 3, 3.0, 0.0, 'PPC', '2', '2nd', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(767, 5, 'ETH 0008', 'Ethics', NULL, 3, 3.0, 0.0, 'ETH', '2', '2nd', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(768, 5, 'PED 0074', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'PED', '2', '2nd', 'PED 0001', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(769, 5, 'CPE 0311', 'Logic Circuits and Design', NULL, 3, 3.0, 0.0, 'CPE', '3', '1st', 'CPE 0223, CPE 0311.1', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(770, 5, 'CPE 0311.1', 'Logic Circuits and Design', NULL, 1, 0.0, 1.0, 'CPE', '3', '1st', 'CPE 0223, CPE 0311', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(771, 5, 'CPE 0312.1', 'Introduction to HDL (Laboratory)', NULL, 1, 0.0, 1.0, 'CPE', '3', '1st', 'CPE 0112.1, CPE 0223', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(772, 5, 'CPE 0313', 'Operating Systems', NULL, 3, 3.0, 0.0, 'CPE', '3', '1st', 'CPE 0211.1', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(773, 5, 'CPE 0314', 'Data and Digital Communications', NULL, 3, 3.0, 0.0, 'CPE', '3', '1st', 'CPE 0223', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(774, 5, 'CPE 0315', 'Feedback and Control Systems', NULL, 3, 3.0, 0.0, 'CPE', '3', '1st', 'CPE 0221, CPE 0212', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(775, 5, 'CPE 0316', 'Fundamentals of Mixed Signals and Sensors', NULL, 3, 3.0, 0.0, 'CPE', '3', '1st', 'CPE 0223', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(776, 5, 'CPE 0317.1', 'Computer Engineering Drafting and Design (Design)', NULL, 1, 1.0, 0.0, 'CPE', '3', '1st', 'CPE 0223', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(777, 5, 'CPE 0318', 'Elective 1 (Lecture)', NULL, 2, 2.0, 0.0, 'CPE', '3', '1st', 'CPE 0222, CPE 0318.1', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(778, 5, 'CPE 0318.1', 'Elective 1 (Lecture)', NULL, 1, 0.0, 1.0, 'CPE', '3', '1st', 'CPE 0222, CPE 0318', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(779, 5, 'CET 0411', 'Technopreneurship 101', NULL, 3, 3.0, 0.0, 'CET', '3', '2nd', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(780, 5, 'CPE 0321', 'Basic Occupational Health and Safety', NULL, 3, 3.0, 0.0, 'CPE', '3', '2nd', '3rd Year Standing', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(781, 5, 'CPE 0322', 'Computer Networks and Security (Lecture)', NULL, 3, 3.0, 0.0, 'CPE', '3', '2nd', 'CPE 0314, CPE 0322.1', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(782, 5, 'CPE 0322.1', 'Computer Networks and Security (Lecture)', NULL, 1, 0.0, 1.0, 'CPE', '3', '2nd', 'CPE 0314, CPE 0322', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(783, 5, 'CPE 0323', 'Computer Architecture and Organization (Lecture)', NULL, 3, 3.0, 0.0, 'CPE', '3', '2nd', 'CPE 0311, CPE 0323.1', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(784, 5, 'CPE 0323.1', 'Computer Architecture and Organization (Lecture)', NULL, 1, 0.0, 1.0, 'CPE', '3', '2nd', 'CPE 0311, CPE 0323', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(785, 5, 'CPE 0324', 'Methods of Research', NULL, 2, 2.0, 0.0, 'CPE', '3', '2nd', 'CET 0212, PCM 0006, CPE 0311', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(786, 5, 'CPE 0325', 'CPE Laws and Professional Practice', NULL, 2, 2.0, 0.0, 'CPE', '3', '2nd', '3rd Year Standing', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(787, 5, 'CPE 0326', 'Elective 2', NULL, 3, 3.0, 0.0, 'CPE', '3', '2nd', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(788, 5, 'CPE 0331', 'Seminars and Field Trips (fld)', NULL, 1, 1.0, 0.0, 'CPE', '3', 'summer', '', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL);
INSERT INTO `subjects` (`subject_id`, `course_id`, `subject_code`, `subject_name`, `description`, `units`, `lecture_hours`, `lab_hours`, `department`, `year_level`, `semester`, `prerequisite`, `status`, `created_at`, `updated_at`, `schedule`, `faculty_id`) VALUES
(789, 5, 'CPE 0332', 'Elective 3', NULL, 2, 2.0, 0.0, 'CPE', '3', 'summer', 'CPE 0222, CPE 0332.1', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(790, 5, 'CPE 0332.1', 'Elective 3', NULL, 1, 0.0, 1.0, 'CPE', '3', 'summer', 'CPE 0222, CPE 0332', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(791, 5, 'CPE 0411', 'Embedded Systems', NULL, 3, 3.0, 0.0, 'CPE', '4', '1st', 'CPE 0323, CPE 0411.1', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(792, 5, 'CPE 0411.1', 'Embedded Systems', NULL, 1, 0.0, 1.0, 'CPE', '4', '1st', 'CPE 0323, CPE 0411', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(793, 5, 'CPE 0412', 'Microprocessors', NULL, 3, 3.0, 0.0, 'CPE', '4', '1st', 'CPE 0323, CPE 0412.1', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(794, 5, 'CPE 0412.1', 'Microprocessors', NULL, 1, 0.0, 1.0, 'CPE', '4', '1st', 'CPE 0323, CPE 0412', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(795, 5, 'CPE 0413', 'Emerging Technologies in CPE', NULL, 3, 3.0, 0.0, 'CPE', '4', '1st', '4th Year Standing', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(796, 5, 'CPE 0414.1', 'CPE Practice and Design 1 (Design)', NULL, 1, 1.0, 0.0, 'CPE', '4', '1st', 'CPE 0323, CPE 0324', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(797, 5, 'CPE 0415', 'Digital Signal Processing', NULL, 3, 3.0, 0.0, 'CPE', '4', '1st', 'CPE 0315, CPE 0415.1', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(798, 5, 'CPE 0415.1', 'Digital Signal Processing', NULL, 1, 0.0, 1.0, 'CPE', '4', '1st', 'CPE 0315, CPE 0415', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(799, 5, 'CPE 0421.1', 'CPE Practice and Design 2 (Design)', NULL, 2, 2.0, 0.0, 'CPE', '4', '2nd', 'CPE 0414.1', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(800, 5, 'CPE 0422.1', 'On-The-Job Training for CPE (240 hrs)', NULL, 3, 3.0, 0.0, 'CPE', '4', '2nd', '4th Year Standing', 'active', '2026-04-08 17:53:10', '2026-04-08 17:53:10', NULL, NULL),
(801, 7, 'CET 0111', 'Calculus 1', NULL, 3, 3.0, 0.0, 'CET', '1', '1st', '', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(802, 7, 'CET 0112', 'Chemistry for Engineers', NULL, 4, 3.0, 0.0, 'CET', '1', '1st', '', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(803, 7, 'CET 0123.1', 'Computer-Aided Drafting', NULL, 1, 1.0, 0.0, 'CET', '1', '1st', '', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(804, 7, 'PCM 0006', 'Purposive Communication', NULL, 3, 3.0, 0.0, 'PCM', '1', '1st', '', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(805, 7, 'MMW 0001', 'Mathematics in the Modern World', NULL, 3, 3.0, 0.0, 'MMW', '1', '1st', '', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(806, 7, 'UTS 0003', 'Understanding the Self', NULL, 3, 3.0, 0.0, 'UTS', '1', '1st', '', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(807, 7, 'IPP 0010', 'Interdisiplinaryong Pagbasa at Pagsulat Tungo sa Mabisang Pagpapahayag', NULL, 3, 3.0, 0.0, 'IPP', '1', '1st', '', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(808, 7, 'ITE 0001', 'Living in the IT Era', NULL, 3, 3.0, 0.0, 'ITE', '1', '1st', '', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(809, 7, 'PED 0001', 'Foundation of Physical Activities', NULL, 2, 2.0, 0.0, 'PED', '1', '1st', '', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(810, 7, 'NSTP 01', 'National Service Training Program 1-ROTC 1/CWTS 1', NULL, 3, 3.0, 0.0, 'NSTP', '1', '1st', '', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(811, 7, 'CET 0121', 'Calculus 2', NULL, 3, 3.0, 0.0, 'CET', '1', '2nd', 'CET 0111', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(812, 7, 'CET 0122A', 'Physics for Engineers', NULL, 4, 4.0, 0.0, 'CET', '1', '2nd', 'CET 0111', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(813, 7, 'CET 0122.1', 'Physics for Engineers', NULL, 1, 0.0, 1.0, 'CET', '1', '2nd', 'CET 0122', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(814, 7, 'CET 0217', 'Physics 2', NULL, 3, 3.0, 0.0, 'CET', '1', '2nd', 'CET 0122', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(815, 7, 'CET 0217.1', 'Physics 2', NULL, 1, 0.0, 1.0, 'CET', '1', '2nd', 'CET 0122.1, CET 0217', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(816, 7, 'CSE 0121.1', 'Computer Programming 1', NULL, 1, 1.0, 0.0, 'CSE', '1', '2nd', '', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(817, 7, 'STS 0002', 'Science, Technology and Society', NULL, 3, 3.0, 0.0, 'STS', '1', '2nd', '', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(818, 7, 'GTB 121', 'Great Books', NULL, 3, 3.0, 0.0, 'GTB', '1', '2nd', '', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(819, 7, 'PED 0012', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'PED', '1', '2nd', 'PED 0001', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(820, 7, 'NSTP 02', 'National Service Training Program 2- ROTC 2/ CWTS 2', NULL, 3, 3.0, 0.0, 'NSTP', '1', '2nd', 'NSTP1', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(821, 7, 'CET 0211', 'Differential Equations', NULL, 3, 3.0, 0.0, 'CET', '2', '1st', 'CET 0121', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(822, 7, 'CET 0212', 'Engineering Data Analysis', NULL, 3, 3.0, 0.0, 'CET', '2', '1st', 'CET 0121', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(823, 7, 'CSE 0211.1', 'Computer Programming 2', NULL, 1, 1.0, 0.0, 'CSE', '2', '1st', 'CSE 0121.1', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(824, 7, 'ECE 0211', 'Circuits 1', NULL, 4, 3.0, 0.0, 'ECE', '2', '1st', 'CET 0122', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(825, 7, 'ECE 0211.1', 'Circuits 1', NULL, 1, 0.0, 1.0, 'ECE', '2', '1st', 'ECE 0211, CET 0122.1', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(826, 7, 'ECE 0212', 'Electronics Devices and Circuits', NULL, 3, 3.0, 0.0, 'ECE', '2', '1st', 'CET 0122, ECE 0211', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(827, 7, 'ECE 0212.1', 'Electronics Devices and Circuits', NULL, 1, 0.0, 1.0, 'ECE', '2', '1st', 'CET 0122.1, CET 0212', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(828, 7, 'PPC 122', 'Philippine Popular Culture', NULL, 3, 3.0, 0.0, 'PPC', '2', '1st', '', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(829, 7, 'CET 0222', 'Fundamentals of Material Science and Engineering', NULL, 3, 3.0, 0.0, 'CET', '2', '1st', 'CET 0112', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(830, 7, 'PED 0014', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'PED', '2', '1st', 'PED 0001', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(831, 7, 'ECE 0221', 'Circuits 2', NULL, 3, 3.0, 0.0, 'ECE', '2', '2nd', 'ECE 0211', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(832, 7, 'ECE 0221.1', 'Circuits 2', NULL, 1, 0.0, 1.0, 'ECE', '2', '2nd', 'ECE 0221, ECE 0221.1', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(833, 7, 'ECE 0222', 'Electronics Circuits Analysis and Design', NULL, 4, 3.0, 0.0, 'ECE', '2', '2nd', 'ECE 0212', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(834, 7, 'ECE 0222.1', 'Electronics Circuits Analysis and Design', NULL, 1, 0.0, 1.0, 'ECE', '2', '2nd', 'ECE 0212.1. ECE 0222', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(835, 7, 'ECE 0223', 'Advanced Engineering Mathematics for ECE', NULL, 4, 3.0, 0.0, 'ECE', '2', '2nd', 'CET 0211', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(836, 7, 'ECE 0223.1', 'Advanced Engineering Mathematics for ECE', NULL, 1, 0.0, 1.0, 'ECE', '2', '2nd', 'ECE 0223', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(837, 7, 'ECE 0224', 'Electromagnetics', NULL, 4, 0.0, 0.0, 'ECE', '2', '2nd', 'CET 0211', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(838, 7, 'CET 0216', 'Engineering Economics', NULL, 3, 0.0, 0.0, 'CET', '2', '2nd', 'CET 0212', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(839, 7, 'PED 0023', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'PED', '2', '2nd', 'PED 0001', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(840, 7, 'ECE 0311', 'Logic Circuits and Switching Theory', NULL, 3, 3.0, 0.0, 'ECE', '3', '1st', 'ECE 0212', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(841, 7, 'ECE 0311.1', 'Logic Circuits and Switching Theory', NULL, 1, 0.0, 1.0, 'ECE', '3', '1st', 'ECE 0212.1, ECE 0311', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(842, 7, 'ECE 0312', 'Electronics Systems and Design', NULL, 3, 3.0, 0.0, 'ECE', '3', '1st', 'ECE 0222', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(843, 7, 'ECE 0312.1', 'Electronics Systems and Design', NULL, 1, 0.0, 1.0, 'ECE', '3', '1st', 'ECE 0312, ECE 0222.1', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(844, 7, 'ECE 0313', 'Signals, Spectra, Signal Processing', NULL, 3, 3.0, 0.0, 'ECE', '3', '1st', 'ECE 0223', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(845, 7, 'ECE 0313.1', 'Signals, Spectra, Signal Processing', NULL, 1, 0.0, 1.0, 'ECE', '3', '1st', 'ECE 0313, ECE 0223.1', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(846, 7, 'ECE 0314', 'Principle of Communication System', NULL, 3, 3.0, 0.0, 'ECE', '3', '1st', 'ECE 0223, ECE 0222', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(847, 7, 'ECE 0314.1', 'Principle of Communication System', NULL, 1, 0.0, 1.0, 'ECE', '3', '1st', 'ECE 0312, ECE 0223.1,', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(848, 7, 'CET 0411', 'Technopreneurship 101', NULL, 3, 3.0, 0.0, 'CET', '3', '1st', '3rd Year Standing', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(849, 7, 'ECE 0213', 'ECE Laws, Contracts, Ethics, Standards & Safety', NULL, 3, 3.0, 0.0, 'ECE', '3', '1st', '2nd Year Standing', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(850, 7, 'ECE 0321', 'Transmission Media and Antenna System & Design', NULL, 3, 3.0, 0.0, 'ECE', '3', '2nd', 'ECE 0314, ECE 0221', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(851, 7, 'ECE 0321.1', 'Transmission Media and Antenna System & Design', NULL, 1, 0.0, 1.0, 'ECE', '3', '2nd', 'ECE 0314.1, ECE 0321', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(852, 7, 'ECE 0322', 'Microprocessor, Microcontroller System and Design', NULL, 3, 3.0, 0.0, 'ECE', '3', '2nd', 'ECE 0311', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(853, 7, 'ECE 0322.1', 'Microprocessor, Microcontroller System and Design', NULL, 1, 0.0, 1.0, 'ECE', '3', '2nd', 'ECE 0322, ECE 0311.1', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(854, 7, 'ECE 0323', 'Feedback and Control System', NULL, 3, 3.0, 0.0, 'ECE', '3', '2nd', 'ECE 0223, ECE 0221', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(855, 7, 'ECE 0323.1', 'Feedback and Control System', NULL, 1, 0.0, 1.0, 'ECE', '3', '2nd', 'ECE 0223.1, ECE 0323', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(856, 7, 'ECE 0324', 'Modulation and Coding Techniques', NULL, 3, 3.0, 0.0, 'ECE', '3', '2nd', 'ECE 0314', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(857, 7, 'ECE 0324.1', 'Modulation and Coding Techniques', NULL, 1, 0.0, 1.0, 'ECE', '3', '2nd', 'ECE 0314.1, ECE 0324', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(858, 7, 'ECE 0412', 'Methods of Research', NULL, 3, 3.0, 0.0, 'ECE', '3', '2nd', 'CET 0411', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(859, 7, 'ECE 0411', 'Data Communications', NULL, 3, 3.0, 0.0, 'ECE', '3', '2nd', 'ECE 324, ECE 314', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(860, 7, 'ECE 0411.1', 'Data Communications', NULL, 1, 0.0, 1.0, 'ECE', '3', '2nd', 'ECE 0324.1, ECE 0411', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(861, 7, 'ECE 0331A', 'On The Job Training for ECE (240 hrs)', NULL, 6, 6.0, 0.0, 'ECE', '3', 'summer', '3rd Year Standing', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(862, 7, 'ECE 0412.1', 'Design 1/Capstone Project 1', NULL, 1, 1.0, 0.0, 'ECE', '4', '1st', 'ECE 0331', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(863, 7, 'AAP 0007', 'Art Appreciation', NULL, 3, 3.0, 0.0, 'AAP', '4', '1st', '', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(864, 7, 'LWR 0009', 'Life and Works of Rizal', NULL, 3, 3.0, 0.0, 'LWR', '4', '1st', '', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(865, 7, 'CHE 0315', 'Environmental Science and Engineering', NULL, 3, 3.0, 0.0, 'CHE', '4', '1st', 'CET 0112', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(866, 7, 'TCW 0005', 'The Contemporary World', NULL, 3, 3.0, 0.0, 'TCW', '4', '1st', '', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(867, 7, 'ECE 0316', 'Analog IC Design', NULL, 3, 3.0, 0.0, 'ECE', '4', '1st', 'ECE 0322', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(868, 7, 'ECE 0316.1', 'Analog IC Design', NULL, 1, 0.0, 1.0, 'ECE', '4', '1st', 'ECE 0322.1. ECE 0316', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(869, 7, 'ECE 0315', 'Advanced Communication System and Design', NULL, 3, 3.0, 0.0, 'ECE', '4', '1st', 'ECE 0324', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(870, 7, 'ECE 0315.1', 'Advanced Communication System and Design', NULL, 1, 0.0, 1.0, 'ECE', '4', '1st', 'ECE 0324.1. ECE 315', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(871, 7, 'CET 0221', 'Engineering Management', NULL, 2, 2.0, 0.0, 'CET', '4', '2nd', 'CET 0216', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(872, 7, 'ECE 0421.1', 'Design 2/Capstone Project 2', NULL, 1, 1.0, 0.0, 'ECE', '4', '2nd', 'ECE 412.1', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(873, 7, 'ECE 0423.1', 'Seminars/Colloquium', NULL, 1, 1.0, 0.0, 'ECE', '4', '2nd', 'ECE 0412', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(874, 7, 'RPH 0004', 'Readings in Philippine History', NULL, 3, 3.0, 0.0, 'RPH', '4', '2nd', '', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(875, 7, 'ETH 0008', 'Ethics', NULL, 3, 3.0, 0.0, 'ETH', '4', '2nd', '', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(876, 7, 'ECE 0326', 'Digital IC Design', NULL, 3, 3.0, 0.0, 'ECE', '4', '2nd', 'ECE 0316', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(877, 7, 'ECE 0326.1', 'Digital IC Design', NULL, 1, 0.0, 1.0, 'ECE', '4', '2nd', 'ECE 0316.1, ECE0326', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(878, 7, 'ECE 0325', 'Advanced Networking', NULL, 3, 3.0, 0.0, 'ECE', '4', '2nd', 'ECE 0315', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(879, 7, 'ECE 0325.1', 'Advanced Networking', NULL, 1, 0.0, 1.0, 'ECE', '4', '2nd', 'ECE 315.1, ECE 0325', 'active', '2026-04-08 17:53:15', '2026-04-08 17:53:15', NULL, NULL),
(880, 9, 'CET 0111', 'Calculus 1', NULL, 3, 3.0, 0.0, 'CET', '1', '1st', '', 'active', '2026-04-08 17:53:19', '2026-04-08 17:53:19', NULL, NULL),
(881, 9, 'CET 0112', 'Chemistry for Engineers', NULL, 4, 3.0, 0.0, 'CET', '1', '1st', '', 'active', '2026-04-08 17:53:19', '2026-04-08 17:53:19', NULL, NULL),
(882, 9, 'CET 0113.1', 'Engineering Drawing', NULL, 1, 1.0, 0.0, 'CET', '1', '1st', '', 'active', '2026-04-08 17:53:19', '2026-04-08 17:53:19', NULL, NULL),
(883, 9, 'MEC 0111', 'Mechanical Engineering Orientation', NULL, 1, 1.0, 0.0, 'MEC', '1', '1st', '', 'active', '2026-04-08 17:53:19', '2026-04-08 17:53:19', NULL, NULL),
(884, 9, 'PCM 0006', 'Purposive Communication', NULL, 3, 3.0, 0.0, 'PCM', '1', '1st', '', 'active', '2026-04-08 17:53:19', '2026-04-08 17:53:19', NULL, NULL),
(885, 9, 'MMW 0001', 'Mathematics in the Modern World', NULL, 3, 3.0, 0.0, 'MMW', '1', '1st', '', 'active', '2026-04-08 17:53:19', '2026-04-08 17:53:19', NULL, NULL),
(886, 9, 'UTS 0003', 'Understanding the Self', NULL, 3, 3.0, 0.0, 'UTS', '1', '1st', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(887, 9, 'IPP 0010', 'Interdisiplinaryong Pagbasa at Pagsulat Tungo sa Mabisang', NULL, 3, 3.0, 0.0, 'IPP', '1', '1st', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(888, 9, 'PED 0001', 'Foundation of Physical Activities', NULL, 2, 2.0, 0.0, 'PED', '1', '1st', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(889, 9, 'NSTP 01', 'National Service Training Program 1-ROTC 1/CWTS 1', NULL, 3, 2.0, 0.0, 'NSTP', '1', '1st', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(890, 9, 'CET 0121', 'Calculus 2', NULL, 3, 3.0, 0.0, 'CET', '1', '2nd', 'CET 0111', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(891, 9, 'CET 0122A', 'Physics for Engineers', NULL, 5, 4.0, 0.0, 'CET', '1', '2nd', 'CET 0111, CET 0121', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(892, 9, 'CET 0123.1', 'Computer-Aided Drafting', NULL, 1, 1.0, 0.0, 'CET', '1', '2nd', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(893, 9, 'AAP 0007', 'Art Appreciation', NULL, 3, 3.0, 0.0, 'AAP', '1', '2nd', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(894, 9, 'LWR 0009', 'Life and Works of Rizal', NULL, 3, 3.0, 0.0, 'LWR', '1', '2nd', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(895, 9, 'RPH 0004', 'Readings in Philippine History', NULL, 3, 3.0, 0.0, 'RPH', '1', '2nd', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(896, 9, 'TCW 0005', 'The Contemporary World', NULL, 3, 3.0, 0.0, 'TCW', '1', '2nd', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(897, 9, 'ITE 0001', 'Living in the IT Era', NULL, 3, 3.0, 0.0, 'ITE', '1', '2nd', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(898, 9, 'PED 0033', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'PED', '1', '2nd', 'PED 0001', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(899, 9, 'NSTP 02', 'National Service Training Program 2- ROTC 2/ CWTS 2', NULL, 3, 3.0, 0.0, 'NSTP', '1', '2nd', 'NSTP 1', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(900, 9, 'CET 0211', 'Differential Equations', NULL, 3, 3.0, 0.0, 'CET', '2', '1st', 'CET 0121', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(901, 9, 'CET 0214', 'Statics of Rigid Bodies', NULL, 3, 3.0, 0.0, 'CET', '2', '1st', 'CET 0122, CET 0121', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(902, 9, 'STS 0002', 'Science, Technology and Society', NULL, 3, 3.0, 0.0, 'STS', '2', '1st', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(903, 9, 'ELE 0219', 'Basic Electrical Engineering', NULL, 3, 2.0, 0.0, 'ELE', '2', '1st', 'CET 0122, CET 0121', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(904, 9, 'MEC 0211', 'Thermodynamics 1', NULL, 3, 3.0, 0.0, 'MEC', '2', '1st', 'CET 0122, CET 0121', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(905, 9, 'MEC 0212.1', 'Workshop, Theory and Practice', NULL, 1, 1.0, 0.0, 'MEC', '2', '1st', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(906, 9, 'GTB 121', 'Great Books', NULL, 3, 3.0, 0.0, 'GTB', '2', '1st', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(907, 9, 'PED 0103', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'PED', '2', '1st', 'PED 0001', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(908, 9, 'CET 0212', 'Engineering Data Analysis', NULL, 3, 3.0, 0.0, 'CET', '2', '2nd', 'CET 0121', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(909, 9, 'CET 0221', 'Engineering Management', NULL, 2, 2.0, 0.0, 'CET', '2', '2nd', 'CET 0214', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(910, 9, 'CET 0223', 'Dynamics of Rigid Bodies', NULL, 2, 2.0, 0.0, 'CET', '2', '2nd', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(911, 9, 'ECE 0225', 'Basic Electronics', NULL, 3, 2.0, 0.0, 'ECE', '2', '2nd', 'ELE 219', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(912, 9, 'MEC 0221', 'Thermodynamics 2', NULL, 3, 3.0, 0.0, 'MEC', '2', '2nd', 'MEC 0211', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(913, 9, 'MEC 0222.1', 'Machine Shop Theory', NULL, 2, 2.0, 0.0, 'MEC', '2', '2nd', 'MEC 0212.1', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(914, 9, 'MEC 0223', 'Advanced Mathematics for ME', NULL, 3, 3.0, 0.0, 'MEC', '2', '2nd', 'CET 0211', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(915, 9, 'PPC 122', 'Philippine Popular Culture', NULL, 3, 3.0, 0.0, 'PPC', '2', '2nd', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(916, 9, 'PED 0012', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'PED', '2', '2nd', 'PED 0001', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(917, 9, 'CET 0216', 'Engineering Economics', NULL, 3, 3.0, 0.0, 'CET', '3', '1st', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(918, 9, 'MEC 0311', 'Mechanics of Deformable Bodies', NULL, 3, 3.0, 0.0, 'MEC', '3', '1st', 'CET 0223', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(919, 9, 'ELE 0319', 'DC and AC Machinery', NULL, 3, 2.0, 0.0, 'ELE', '3', '1st', 'ELE 0219', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(920, 9, 'MEC 0312', 'Heat Transfer', NULL, 2, 2.0, 0.0, 'MEC', '3', '1st', 'MEC 0221', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(921, 9, 'MEC 0313', 'Fluid Mechanics', NULL, 3, 3.0, 0.0, 'MEC', '3', '1st', 'MEC 0211', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(922, 9, 'MEC 0314', 'Machine Elements', NULL, 3, 2.0, 0.0, 'MEC', '3', '1st', 'CET 0223', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(923, 9, 'MEC 0315', 'Vibration Engineering', NULL, 2, 2.0, 0.0, 'MEC', '3', '1st', 'CET 0211', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(924, 9, 'EIT 0329.1', 'Computer Fundamentals and Programming', NULL, 1, 1.0, 0.0, 'EIT', '3', '1st', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(925, 9, 'MEC 0316.1', 'Computer Applications for ME', NULL, 1, 1.0, 0.0, 'MEC', '3', '2nd', 'EIT 0329.1', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(926, 9, 'MEC 0321', 'Methods of Research for ME', NULL, 1, 1.0, 0.0, 'MEC', '3', '2nd', 'CET 0212', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(927, 9, 'MEC 0322', 'Refrigeration Systems', NULL, 3, 3.0, 0.0, 'MEC', '3', '2nd', 'MEC 0312', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(928, 9, 'MEC 0323', 'Fluid Machineries', NULL, 3, 3.0, 0.0, 'MEC', '3', '2nd', 'MEC 313', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(929, 9, 'MEC 0324', 'Combustion Engineering', NULL, 2, 2.0, 0.0, 'MEC', '3', '2nd', 'MEC 0221', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(930, 9, 'MEC 0325', 'Material Science & Engineering for ME', NULL, 3, 2.0, 0.0, 'MEC', '3', '2nd', 'MEC 0311', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(931, 9, 'MEC 0326.1', 'Mechanical Engineering Laboratory 1', NULL, 1, 1.0, 0.0, 'MEC', '3', '2nd', 'MEC 0221', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(932, 9, 'MEC 0327', 'Mechanical Engineering Elective 1', NULL, 2, 2.0, 0.0, 'MEC', '3', '2nd', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(933, 9, 'ETH 0008', 'Ethics', NULL, 3, 3.0, 0.0, 'ETH', '3', '2nd', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(934, 9, 'MEC 0330.1', 'On The Job Training (240 hrs)', NULL, 1, 1.0, 0.0, 'MEC', '3', 'summer', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(935, 9, 'CET 0411', 'Technopreneurship 101', NULL, 3, 3.0, 0.0, 'CET', '4', '1st', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(936, 9, 'MEC 0411', 'Mechanical Engineering Elective 2', NULL, 2, 2.0, 0.0, 'MEC', '4', '1st', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(937, 9, 'MEC 0412.1', 'ME Project Study 1', NULL, 1, 1.0, 0.0, 'MEC', '4', '1st', 'MEC 0321', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(938, 9, 'MEC 0413', 'Air-Condition and Ventilation Systems', NULL, 3, 3.0, 0.0, 'MEC', '4', '1st', 'MEC 0322', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(939, 9, 'MEC 0414', 'Control Engineering', NULL, 3, 2.0, 0.0, 'MEC', '4', '1st', 'ECE 0225', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(940, 9, 'MEC 0415', 'Power Plant Design with Renewable Energy', NULL, 4, 3.0, 0.0, 'MEC', '4', '1st', 'MEC 0324', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(941, 9, 'MEC 0416', 'Machine Design 1', NULL, 3, 3.0, 0.0, 'MEC', '4', '1st', 'MEC 0314', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(942, 9, 'MEC 0417.1', 'Mechanical Engineering Laboratory 2', NULL, 2, 2.0, 0.0, 'MEC', '4', '1st', 'MEC 0323, MEC 0326.1', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(943, 9, 'MEC 0421', 'Industrial Plant Engineering', NULL, 4, 3.0, 0.0, 'MEC', '4', '2nd', 'MEC 0413', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(944, 9, 'MEC 0422.1', 'ME Project Study 2', NULL, 1, 1.0, 0.0, 'MEC', '4', '2nd', 'MEC 0412.1', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(945, 9, 'MEC 0423', 'Machine Design 2', NULL, 3, 2.0, 0.0, 'MEC', '4', '2nd', 'MEC 0416', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(946, 9, 'MEC 0424', 'Basic Occupational Safety & Health', NULL, 3, 3.0, 0.0, 'MEC', '4', '2nd', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(947, 9, 'MEC 0425A', 'Manufacturing & Industrial Processes with Plant', NULL, 1, 1.0, 0.0, 'MEC', '4', '2nd', '', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(948, 9, 'MEC 0426.1', 'Mechanical Engineering Laboratory 3', NULL, 2, 2.0, 0.0, 'MEC', '4', '2nd', 'MEC 0415', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(949, 9, 'MEC 0427', 'ME Laws. Ethics, Contracts, Codes & Standards', NULL, 2, 2.0, 0.0, 'MEC', '4', '2nd', 'ETH 0008', 'active', '2026-04-08 17:53:20', '2026-04-08 17:53:20', NULL, NULL),
(950, 8, 'CET 0111', 'Calculus 1', NULL, 3, 3.0, 0.0, 'CET', '1', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(951, 8, 'CET 0112', 'Chemistry for Engineers', NULL, 4, 3.0, 0.0, 'CET', '1', '1st', 'CET 0112', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(952, 8, 'CET 0113.1', 'Engineering Drawing', NULL, 1, 1.0, 0.0, 'CET', '1', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(953, 8, 'PCM 0006', 'Purposive Communication', NULL, 3, 3.0, 0.0, 'PCM', '1', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(954, 8, 'MMW 0001', 'Mathematics in the Modern World', NULL, 3, 3.0, 0.0, 'MMW', '1', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(955, 8, 'UTS 0003', 'Understanding the Self', NULL, 3, 3.0, 0.0, 'UTS', '1', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(956, 8, 'IPP 0010', 'Interdisiplinaryong Pagbasa at Pagsulat Tungo', NULL, 3, 3.0, 0.0, 'IPP', '1', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(957, 8, 'PED 0001', 'Foundation of Physical Activities', NULL, 2, 2.0, 0.0, 'PED', '1', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(958, 8, 'NSTP 01', 'National Service Training Program 1- ROTC 1/ CWTS 1', NULL, 3, 3.0, 0.0, 'NSTP', '1', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(959, 8, 'CET 0121', 'Calculus 2', NULL, 3, 3.0, 0.0, 'CET', '1', '2nd', 'CET 0111', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(960, 8, 'CET 0122A', 'Physics for Engineers', NULL, 5, 4.0, 0.0, 'CET', '1', '2nd', 'CET 0111, CET 0121', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(961, 8, 'CET 0123.1', 'Computer-Aided Drafting', NULL, 1, 1.0, 0.0, 'CET', '1', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(962, 8, 'AAP 0007', 'Art Appreciation', NULL, 3, 3.0, 0.0, 'AAP', '1', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(963, 8, 'LWR 0009', 'Life and Works of Rizal', NULL, 3, 3.0, 0.0, 'LWR', '1', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(964, 8, 'RPH 0004', 'Readings in Philippine History', NULL, 3, 3.0, 0.0, 'RPH', '1', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(965, 8, 'TCW 0005', 'The Contemporary World', NULL, 3, 3.0, 0.0, 'TCW', '1', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(966, 8, 'ITE 0001', 'Living in the IT Era', NULL, 3, 3.0, 0.0, 'ITE', '1', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(967, 8, 'PED 0012', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'PED', '1', '2nd', 'PED 0001', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(968, 8, 'NSTP 02', 'National Service Training Program 2- ROTC 2/ CWTS 2', NULL, 3, 3.0, 0.0, 'NSTP', '1', '2nd', 'NSTP 1', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(969, 8, 'CET 0211', 'Differential Equations', NULL, 3, 3.0, 0.0, 'CET', '2', '1st', 'CET 0121', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(970, 8, 'CET 0214', 'Statics of Rigid Bodies', NULL, 3, 3.0, 0.0, 'CET', '2', '1st', 'CET 0122, CET 0121', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(971, 8, 'STS 0002', 'Science, Technology and Society', NULL, 3, 3.0, 0.0, 'STS', '2', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(972, 8, 'ELE 0219', 'Basic Electrical Engineering', NULL, 3, 2.0, 0.0, 'ELE', '2', '1st', 'CET 0122, CET 0121', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(973, 8, 'MEC 0211', 'Thermodynamics 1', NULL, 3, 3.0, 0.0, 'MEC', '2', '1st', 'CET 0122, CET 0121', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(974, 8, 'MEC 0212.1', 'Workshop, Theory and Practice', NULL, 1, 1.0, 0.0, 'MEC', '2', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(975, 8, 'GTB 121', 'Great Books', NULL, 3, 3.0, 0.0, 'GTB', '2', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(976, 8, 'PED 0013', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'PED', '2', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(977, 8, 'CET 0212', 'Engineering Data Analysis', NULL, 3, 3.0, 0.0, 'CET', '2', '2nd', 'CET 0111', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(978, 8, 'CET 0221', 'Engineering Management', NULL, 3, 3.0, 0.0, 'CET', '2', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(979, 8, 'CET 0223', 'Dynamics of Rigid Bodies', NULL, 3, 3.0, 0.0, 'CET', '2', '2nd', 'CET 0214', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(980, 8, 'ECE 0225', 'Basic Electronics', NULL, 3, 2.0, 0.0, 'ECE', '2', '2nd', 'ELE 0219', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(981, 8, 'MEC 0222.1', 'Machine Shop Theory', NULL, 3, 3.0, 0.0, 'MEC', '2', '2nd', 'MEC 0212.1', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(982, 8, 'MEC 0223', 'Advanced Mathematics for ME', NULL, 1, 1.0, 0.0, 'MEC', '2', '2nd', 'CET 0211', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(983, 8, 'PPC 122', 'Philippine Popular Culture', NULL, 3, 3.0, 0.0, 'PPC', '2', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(984, 8, 'PED 0014', 'PE Elective (12, 13, or 14)', NULL, 2, 2.0, 0.0, 'PED', '2', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(985, 8, 'MEC 0311', 'Mechanics of Deformable Bodies', NULL, 3, 3.0, 0.0, 'MEC', '3', '1st', 'CET 0223', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(986, 8, 'MEC 0315', 'Vibration Engineering', NULL, 2, 2.0, 0.0, 'MEC', '3', '1st', 'CET 0211', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(987, 8, 'MEC 0313', 'Fluid Mechanics', NULL, 2, 2.0, 0.0, 'MEC', '3', '1st', 'MEC 0211', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(988, 8, 'MFG 0311', 'Engineering Production Management', NULL, 3, 3.0, 0.0, 'MFG', '3', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(989, 8, 'MFG 0312', 'Kinematics of Machinery', NULL, 4, 3.0, 0.0, 'MFG', '3', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(990, 8, 'MFG 0313', 'Manufacturing Processes 1', NULL, 2, 2.0, 0.0, 'MFG', '3', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(991, 8, 'MFG 0314', 'MFG Elective 1', NULL, 2, 2.0, 0.0, 'MFG', '3', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(992, 8, 'CET 0216', 'Engineering Economics', NULL, 3, 3.0, 0.0, 'CET', '3', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(993, 8, 'MEC 0321', 'Methods of Research for ME', NULL, 1, 1.0, 0.0, 'MEC', '3', '2nd', 'CET 0212', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(994, 8, 'MEC 0325', 'Material Science & Engineering for ME', NULL, 3, 2.0, 0.0, 'MEC', '3', '2nd', 'MEC 0311', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(995, 8, 'MFG 0321.1', 'Manufacturing Processes 2', NULL, 2, 2.0, 0.0, 'MFG', '3', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(996, 8, 'MFG 0322', 'Ergonomics', NULL, 2, 2.0, 0.0, 'MFG', '3', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(997, 8, 'MFG 0323', 'Lean Manufacturing', NULL, 3, 3.0, 0.0, 'MFG', '3', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(998, 8, 'MFG 0324', 'MFG Elective 2', NULL, 2, 2.0, 0.0, 'MFG', '3', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(999, 8, 'EITH 0329.1', 'Computer Fundamentals and Programming', NULL, 1, 1.0, 0.0, 'EITH', '3', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(1000, 8, 'ETH 0008', 'Ethics', NULL, 3, 3.0, 0.0, 'ETH', '3', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(1001, 8, 'MFGE 0330.1', 'On The Job Training 1 (240 hrs)', NULL, 1, 1.0, 0.0, 'MFGE', '3', 'summer', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(1002, 8, 'CET 0411', 'Technopreneurship 101', NULL, 3, 3.0, 0.0, 'CET', '4', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(1003, 8, 'MEC 0414', 'Control Engineering', NULL, 3, 2.0, 0.0, 'MEC', '4', '1st', 'ECE 0225', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(1004, 8, 'MFG 0411', 'Computer-Aided Manufacturing', NULL, 2, 1.0, 0.0, 'MFG', '4', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(1005, 8, 'MFG 0412', 'Design of Jigs and Fixtures', NULL, 3, 3.0, 0.0, 'MFG', '4', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(1006, 8, 'MFG 0413', 'Design of Machinery', NULL, 2, 2.0, 0.0, 'MFG', '4', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(1007, 8, 'MFG 0413.1', 'Design of Machinery', NULL, 3, 0.0, 1.0, 'MFG', '4', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(1008, 8, 'MFG 0414', 'Computer-Aided Engineering', NULL, 1, 1.0, 0.0, 'MFG', '4', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(1009, 8, 'MFG 0414.1', 'Computer-Aided Engineering', NULL, 2, 0.0, 1.0, 'MFG', '4', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(1010, 8, 'MFG 0416', 'Machine Elements', NULL, 3, 3.0, 0.0, 'MFG', '4', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(1011, 8, 'MEC 0425', 'Manufacturing & Industrial Processes', NULL, 1, 1.0, 0.0, 'MEC', '4', '1st', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(1012, 8, 'MEC 0424', 'Basic Occupational Safety & Health', NULL, 3, 3.0, 0.0, 'MEC', '4', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(1013, 8, 'MFG 0421.1', 'Project Study', NULL, 2, 2.0, 0.0, 'MFG', '4', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(1014, 8, 'MFG 0422', 'Engineering Ethics, Codes and Standards', NULL, 2, 2.0, 0.0, 'MFG', '4', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(1015, 8, 'MFG 0423', 'Computer-Integrated Manufacturing (Lecture)', NULL, 1, 1.0, 0.0, 'MFG', '4', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(1016, 8, 'MFG 0423.1', 'Computer-Integrated Manufacturing', NULL, 1, 1.0, 0.0, 'MFG', '4', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(1017, 8, 'MFG 0424', 'Mechatronics (Lecture)', NULL, 3, 2.0, 0.0, 'MFG', '4', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL),
(1018, 8, 'MFG 0425', 'Product Design (Lecture)', NULL, 3, 2.0, 0.0, 'MFG', '4', '2nd', '', 'active', '2026-04-08 17:53:24', '2026-04-08 17:53:24', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subject_blocks`
--

CREATE TABLE `subject_blocks` (
  `subject_block_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `block_name` varchar(20) NOT NULL,
  `capacity` int(11) DEFAULT 40
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `applicants`
--
ALTER TABLE `applicants`
  ADD PRIMARY KEY (`applicant_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_lrn` (`lrn`),
  ADD KEY `idx_application_status` (`application_status`);

--
-- Indexes for table `blocks`
--
ALTER TABLE `blocks`
  ADD PRIMARY KEY (`block_id`),
  ADD UNIQUE KEY `unique_block` (`block_name`,`course`,`year_level`,`semester`,`school_year`);

--
-- Indexes for table `block_subjects`
--
ALTER TABLE `block_subjects`
  ADD PRIMARY KEY (`block_subject_id`),
  ADD UNIQUE KEY `unique_block_class` (`block_id`,`class_id`),
  ADD KEY `block_id` (`block_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `unique_course_code` (`course_code`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  ADD PRIMARY KEY (`schedule_id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`faculty_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`grade_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `grade_entries`
--
ALTER TABLE `grade_entries`
  ADD PRIMARY KEY (`entry_id`),
  ADD UNIQUE KEY `uq_enroll` (`enrollment_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`registration_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD UNIQUE KEY `unique_subject_course` (`subject_code`,`course_id`),
  ADD KEY `idx_subjects_course` (`course_id`);

--
-- Indexes for table `subject_blocks`
--
ALTER TABLE `subject_blocks`
  ADD PRIMARY KEY (`subject_block_id`),
  ADD KEY `subject_id` (`class_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `applicant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `blocks`
--
ALTER TABLE `blocks`
  MODIFY `block_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `block_subjects`
--
ALTER TABLE `block_subjects`
  MODIFY `block_subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade_entries`
--
ALTER TABLE `grade_entries`
  MODIFY `entry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1019;

--
-- AUTO_INCREMENT for table `subject_blocks`
--
ALTER TABLE `subject_blocks`
  MODIFY `subject_block_id` int(11) NOT NULL AUTO_INCREMENT;

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

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
