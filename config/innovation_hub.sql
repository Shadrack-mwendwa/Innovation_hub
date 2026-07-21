-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 14, 2026 at 08:45 AM
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
-- Database: `innovation_hub`
--

-- --------------------------------------------------------

--
-- Table structure for table `ajiry_bookings`
--

CREATE TABLE `ajiry_bookings` (
  `id` int(11) NOT NULL,
  `booking_code` varchar(50) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `phone_number` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `services` text NOT NULL,
  `preferred_date` date NOT NULL,
  `additional_info` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ajiry_bookings`
--

INSERT INTO `ajiry_bookings` (`id`, `booking_code`, `full_name`, `id_number`, `phone_number`, `email`, `services`, `preferred_date`, `additional_info`, `status`, `admin_notes`, `created_at`) VALUES
(1, 'AJIRY-6A291C39BD1B3', 'shadrack mwendwa', '372953160', '0703125815', 'shadrackmwendwa477@gmail.com', 'Music Production, Guitar Recording', '2026-06-26', '', 'confirmed', '', '2026-06-10 08:11:37'),
(2, 'AJIRY-6A2930963C5C2', 'kioko munyao', '79798080', '0701252245', 'shadrackmwendwa102@gmail.com', 'Voice Over, Podcast Recording', '2026-06-30', '', 'confirmed', NULL, '2026-06-10 09:38:30'),
(3, 'AJIRY-6A2A53E88ADC0', 'kioko paul', '0769219985', '0712035815', 'kiokopaul102@gmail.com', 'Shoot Video, Podcast Recording', '2026-06-12', '', 'completed', NULL, '2026-06-11 06:21:28'),
(4, 'AJIRY-6A30FBBBBCB10', 'paul Matata', '69219985', '0712035815', 'paulmatata102@gmail.com', 'Shoot Video, Podcast Recording', '2026-07-06', '', 'confirmed', NULL, '2026-06-16 07:31:07'),
(5, 'AJIRY-6A44C05F0EC9E', 'kioko mutua', '67677878', '0712035815', 'kiokomutua102@gmail.com', 'Guitar Recording, Podcast Recording', '2026-07-14', '', 'confirmed', NULL, '2026-07-01 07:23:11');

-- --------------------------------------------------------

--
-- Table structure for table `ajiry_events`
--

CREATE TABLE `ajiry_events` (
  `id` int(11) NOT NULL,
  `event_title` varchar(200) NOT NULL,
  `event_date` date NOT NULL,
  `event_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ajiry_events`
--

INSERT INTO `ajiry_events` (`id`, `event_title`, `event_date`, `event_description`, `created_at`) VALUES
(1, 'Open Mic Night', '2026-06-19', 'Come showcase your talent at our monthly open mic night. Free entry for registered artists.', '2026-06-05 08:14:14'),
(2, 'Studio Workshop', '2026-06-26', 'Learn professional recording techniques from industry experts. Limited slots available.', '2026-06-05 08:14:14'),
(3, 'Music Production Bootcamp', '2026-07-05', 'Weekend intensive course on music production. Certificate provided.', '2026-06-05 08:14:14');

-- --------------------------------------------------------

--
-- Table structure for table `ajiry_instruments`
--

CREATE TABLE `ajiry_instruments` (
  `id` int(11) NOT NULL,
  `instrument_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ajiry_instruments`
--

INSERT INTO `ajiry_instruments` (`id`, `instrument_name`, `description`, `quantity`, `created_at`) VALUES
(1, 'Yamaha Piano', 'Digital piano with weighted keys', 1, '2026-06-05 08:14:14'),
(2, 'Fender Guitar', 'Electric guitar with amplifier', 2, '2026-06-05 08:14:14'),
(3, 'Shure Microphone', 'Professional condenser microphone', 3, '2026-06-05 08:14:14'),
(4, 'Boss Mixer', 'Audio mixing console', 1, '2026-06-05 08:14:14'),
(5, 'Studio Monitors', 'Professional studio monitor speakers', 2, '2026-06-05 08:14:14');

-- --------------------------------------------------------

--
-- Table structure for table `ajiry_rules`
--

CREATE TABLE `ajiry_rules` (
  `id` int(11) NOT NULL,
  `rule_text` text NOT NULL,
  `rule_order` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ajiry_rules`
--

INSERT INTO `ajiry_rules` (`id`, `rule_text`, `rule_order`, `created_at`) VALUES
(1, 'Monday to Friday only', 1, '2026-06-05 08:14:14'),
(2, 'Maximum 3 people TOTAL per session (including staff)', 2, '2026-06-05 08:14:14'),
(3, 'Session duration: 2 hours', 3, '2026-06-05 08:14:14'),
(4, 'Deposit: KES 500 (refundable)', 4, '2026-06-05 08:14:14'),
(5, 'Valid ID required at entry', 5, '2026-06-05 08:14:14'),
(6, 'Arrive 15 minutes before scheduled time', 6, '2026-06-05 08:14:14'),
(7, 'Cancellation requires 24 hours notice', 7, '2026-06-05 08:14:14');

-- --------------------------------------------------------

--
-- Table structure for table `ajiry_services`
--

CREATE TABLE `ajiry_services` (
  `id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ajiry_services`
--

INSERT INTO `ajiry_services` (`id`, `service_name`, `description`, `price`, `created_at`) VALUES
(1, 'Music Production', 'Professional music production and beat making', 500.00, '2026-06-05 08:14:14'),
(2, 'Guitar Recording', 'High-quality acoustic/electric guitar recording', 300.00, '2026-06-05 08:14:14'),
(3, 'Shoot Video', 'Music video and content video shooting', 1000.00, '2026-06-05 08:14:14'),
(4, 'Voice Over', 'Professional voice-over recording', 400.00, '2026-06-05 08:14:14'),
(5, 'Podcast Recording', 'Podcast recording and editing', 500.00, '2026-06-05 08:14:14'),
(6, 'Mixing & Mastering', 'Audio mixing and mastering services', 800.00, '2026-06-05 08:14:14');

-- --------------------------------------------------------

--
-- Table structure for table `ajiry_settings`
--

CREATE TABLE `ajiry_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ajiry_settings`
--

INSERT INTO `ajiry_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'paybill_number', '123456', '2026-06-04 06:47:55'),
(2, 'booking_fee', '500', '2026-06-04 06:47:55'),
(3, 'max_bookings_per_day', '5', '2026-06-04 06:47:55');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `certificate_number` varchar(50) NOT NULL,
  `issue_date` date NOT NULL,
  `completion_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `registration_id`, `student_name`, `course_name`, `certificate_number`, `issue_date`, `completion_date`, `created_at`) VALUES
(1, 2, 'lucky mutua', 'Computer Basics', 'MCIH-2026-00002', '2026-06-11', '2026-06-11', '2026-06-11 11:23:25'),
(2, 1, 'John Doe', 'Computer Basics', 'MCIH-2026-00001', '2026-06-11', '2026-06-11', '2026-06-11 11:24:49'),
(3, 9, 'Shadrack Mwendwa', 'Computer Basics', 'MCIH-2026-00009', '2026-06-17', '2026-06-17', '2026-06-17 06:26:47');

-- --------------------------------------------------------

--
-- Table structure for table `certifications`
--

CREATE TABLE `certifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `certificate_number` varchar(50) NOT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','issued','revoked') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_feedback`
--

CREATE TABLE `class_feedback` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `rating` int(11) DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_feedback`
--

INSERT INTO `class_feedback` (`id`, `registration_id`, `user_id`, `class_id`, `message`, `rating`, `created_at`) VALUES
(1, 9, 7, 1, 'sjhc  jnjkxmkv ra omlac; q oj', 5, '2026-06-15 07:19:46'),
(2, 8, 7, 2, 'dsnmnmsnm mmm m', 5, '2026-07-01 07:28:07');

-- --------------------------------------------------------

--
-- Table structure for table `class_notes`
--

CREATE TABLE `class_notes` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `week_number` int(11) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_registrations`
--

CREATE TABLE `class_registrations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `class_id` int(11) NOT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `letter_downloaded` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `certificate_issued` tinyint(1) DEFAULT 0,
  `certificate_number` varchar(50) DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `graduation_status` enum('enrolled','completed','graduated') DEFAULT 'enrolled',
  `total_fee` decimal(10,2) DEFAULT 0.00,
  `remaining_balance` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_registrations`
--

INSERT INTO `class_registrations` (`id`, `user_id`, `full_name`, `phone`, `email`, `class_id`, `payment_status`, `payment_amount`, `transaction_id`, `registration_date`, `letter_downloaded`, `created_at`, `certificate_issued`, `certificate_number`, `completion_date`, `graduation_status`, `total_fee`, `remaining_balance`) VALUES
(1, 2, 'John Doe', '0712345678', 'john@example.com', 1, '', 500.00, 'TXN123456', '2026-06-02 09:15:54', 1, '2026-06-02 09:15:54', 1, 'MCIH-2026-00001', '2026-06-11', 'graduated', 1000.00, 500.00),
(2, 4, 'lucky mutua', '0703125815', 'luckymutua102@gmail.com', 1, '', 600.00, 'TXN17803932005646', '2026-06-02 09:40:00', 1, '2026-06-02 09:40:00', 1, 'MCIH-2026-00002', '2026-06-11', 'graduated', 1000.00, 400.00),
(3, 6, 'kapunye punye', '0756789876', 'purity23@gmail.com', 7, '', 200.00, 'TXN17812540905294', '2026-06-12 08:48:10', 1, '2026-06-12 08:48:10', 0, NULL, NULL, 'enrolled', 1000.00, 800.00),
(4, 7, 'paul Matata', '0756789876', 'paulmatata@gmail.com', 7, '', 200.00, 'TXN17812686101450', '2026-06-12 12:50:10', 1, '2026-06-12 12:50:10', 0, NULL, NULL, 'enrolled', 1000.00, 800.00),
(5, 7, 'paul Matata', '0756789876', 'paulmatata@gmail.com', 7, '', 200.00, 'TXN17812696419347', '2026-06-12 13:07:21', 0, '2026-06-12 13:07:21', 0, NULL, NULL, 'enrolled', 1000.00, 800.00),
(6, 7, 'kapunye punye', '0712567835', 'purity23@gmail.com', 7, '', 200.00, 'TXN17812697441027', '2026-06-12 13:09:04', 0, '2026-06-12 13:09:04', 0, NULL, NULL, 'enrolled', 1000.00, 800.00),
(7, 7, 'kapunye punye', '0712567835', 'purity23@gmail.com', 7, '', 400.00, 'TXN17812697727203', '2026-06-12 13:09:32', 0, '2026-06-12 13:09:32', 0, NULL, NULL, 'enrolled', 1000.00, 600.00),
(8, 7, 'Shadrack Mwendwa', '0756789876', 'shadrackmwendwa477@gmail.com', 2, 'paid', 200.00, 'TXN17812719969867', '2026-06-12 13:46:36', 0, '2026-06-12 13:46:36', 0, NULL, NULL, 'enrolled', 0.00, 0.00),
(9, 7, 'Shadrack Mwendwa', '0706072020', 'shadrackmwendwa477@gmail.com', 1, 'paid', 1000.00, 'TXN17812725244894', '2026-06-12 13:55:24', 0, '2026-06-12 13:55:24', 1, 'MCIH-2026-00009', '2026-06-17', 'graduated', 0.00, 0.00),
(10, NULL, 'kioko mutuku', '0706072020', 'kiokomutumu102@gmail.com', 7, 'pending', 1000.00, NULL, '2026-06-15 11:05:29', 0, '2026-06-15 11:05:29', 0, NULL, NULL, 'enrolled', 0.00, 0.00),
(11, NULL, 'Shadrack Mwendwa', '0756789876', 'shadrackmwendwa477@gmail.com', 6, 'pending', 1000.00, NULL, '2026-06-15 13:37:57', 0, '2026-06-15 13:37:57', 0, NULL, NULL, 'enrolled', 1200.00, 700.00),
(12, 11, 'Kelvin Muendo', '0706072020', 'kelvinmu102@gmail.com', 1, 'pending', 400.00, NULL, '2026-06-15 13:45:58', 0, '2026-06-15 13:45:58', 0, NULL, NULL, 'enrolled', 1000.00, 800.00),
(13, 12, 'joseph kioko', '0789899898', 'josephkioko102@gmail.com', 1, 'pending', 900.00, NULL, '2026-06-22 09:20:41', 0, '2026-06-22 09:20:41', 0, NULL, NULL, 'enrolled', 1000.00, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `computer_classes`
--

CREATE TABLE `computer_classes` (
  `id` int(11) NOT NULL,
  `class_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `duration_weeks` int(11) DEFAULT 4,
  `fee_amount` decimal(10,2) DEFAULT 1000.00,
  `max_students` int(11) DEFAULT 30,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `schedule` varchar(255) DEFAULT NULL,
  `status` enum('active','upcoming','completed') DEFAULT 'upcoming',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `price` decimal(10,2) DEFAULT 1000.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `computer_classes`
--

INSERT INTO `computer_classes` (`id`, `class_name`, `description`, `duration_weeks`, `fee_amount`, `max_students`, `start_date`, `end_date`, `schedule`, `status`, `deleted_at`, `created_at`, `price`) VALUES
(1, 'Computer Basics', 'Learn fundamental computer skills including Windows, file management, and internet browsing', 4, 1000.00, 30, '2026-06-09', '2026-07-07', 'Mon/Wed 6PM-8PM', 'upcoming', NULL, '2026-06-02 09:15:53', 1000.00),
(2, 'Microsoft Office Mastery', 'Complete training in Word, Excel, PowerPoint, and Outlook', 8, 1000.00, 25, '2026-06-16', '2026-08-11', 'Tue/Thu 6PM-9PM', 'upcoming', NULL, '2026-06-02 09:15:53', 1000.00),
(3, 'Web Development Fundamentals', 'HTML, CSS, JavaScript basics for beginners', 10, 1000.00, 20, '2026-06-11', '2026-09-01', 'Sat/Sun 10AM-1PM', 'active', NULL, '2026-06-02 09:15:53', 1000.00),
(5, 'Computer Basics', 'Learn fundamental computer skills including Windows, file management, and internet browsing', 4, 1000.00, 30, '2026-06-09', NULL, NULL, 'active', '2026-06-11 12:19:28', '2026-06-11 12:18:48', 1000.00),
(6, 'Programming', 'learn C and C# in less than 8 months', 8, 1200.00, 30, '2026-06-18', NULL, NULL, 'active', NULL, '2026-06-11 12:21:17', 1000.00),
(7, 'forex', 'learn forex skills, strategies and we open account for you.', 8, 1000.00, 30, '2027-07-15', NULL, NULL, 'active', NULL, '2026-06-12 08:42:05', 1000.00);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `venue` varchar(255) DEFAULT 'Greenpark, Makueni',
  `max_participants` int(11) DEFAULT 100,
  `organizer_name` varchar(255) DEFAULT NULL,
  `status` enum('upcoming','ongoing','completed') DEFAULT 'upcoming',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `start_datetime` datetime DEFAULT NULL,
  `end_datetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `description`, `start_date`, `start_time`, `end_time`, `end_date`, `venue`, `max_participants`, `organizer_name`, `status`, `deleted_at`, `start_datetime`, `end_datetime`) VALUES
(1, 'database management skills', 'Event organized by Martin kioko. Expected attendees: 11-25', '2026-12-07 04:00:00', '04:00:00', '17:00:00', '0000-00-00 00:00:00', 'Greenpark, Makueni', 31, 'Martin kioko', 'completed', NULL, '2026-12-07 04:00:00', '2026-12-07 04:00:00'),
(2, 'database management skills', 'Event organized by Martin kioko. Expected attendees: 45-100', '2026-12-07 10:00:00', '10:00:00', '17:00:00', NULL, 'Greenpark, Makueni', 100, 'Martin kioko', 'upcoming', NULL, '2026-12-07 10:00:00', '2026-12-07 10:00:00'),
(3, 'cyber security, fight online crimes', 'Event organized by Master Wilton. Expected attendees: 23', '2026-06-16 00:00:00', '11:00:00', '17:00:00', '2026-06-16 00:00:00', 'Greenpark, Makueni', 100, 'Master Wilton', 'completed', NULL, '2026-06-16 11:00:00', '2026-06-16 17:00:00'),
(4, 'Web Development', 'Event organized by Martin kioko. Expected attendees: 30', '2026-06-17 00:00:00', '10:00:00', '17:00:00', '2026-06-17 00:00:00', 'Greenpark, Makueni', 100, 'Martin kioko', 'completed', NULL, '2026-06-17 10:00:00', '2026-06-17 17:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `event_bookings`
--

CREATE TABLE `event_bookings` (
  `id` int(11) NOT NULL,
  `event_name` varchar(200) NOT NULL,
  `organizer_name` varchar(100) NOT NULL,
  `organizer_type` enum('individual','group','organization','company') DEFAULT 'individual',
  `phone` varchar(20) NOT NULL,
  `alt_phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `number_of_people` int(11) DEFAULT 1,
  `event_type` varchar(50) DEFAULT 'other',
  `expected_attendees` varchar(50) DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` varchar(50) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `duration_hours` int(11) DEFAULT 2,
  `special_requests` text DEFAULT NULL,
  `booking_code` varchar(50) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_bookings`
--

INSERT INTO `event_bookings` (`id`, `event_name`, `organizer_name`, `organizer_type`, `phone`, `alt_phone`, `email`, `number_of_people`, `event_type`, `expected_attendees`, `event_date`, `event_time`, `start_time`, `end_time`, `duration_hours`, `special_requests`, `booking_code`, `status`, `created_at`) VALUES
(2, 'database management skills', 'Martin kioko', 'individual', '0712567835', '0782345789', 'shadrackmwendwa477@gmail.com', 67, 'other', '45-100', '2026-12-07', '09:45', NULL, NULL, 3, '', 'GPBK-20260608-310', 'completed', '2026-06-08 13:11:25'),
(5, 'cyber security, fight online crimes', 'Master Wilton', 'individual', '0700000023', '0723000000', NULL, 23, 'wedding', NULL, '2026-06-11', '12:00', NULL, NULL, 2, NULL, 'BK-6A2A62930CE7D', 'completed', '2026-06-11 06:24:03'),
(10, 'Web Development Class', 'Martin kioko', 'individual', '0756789876', '0723000000', 'martinkioko102@gmail.com', 30, 'workshop', '', '2026-06-13', '13:40', NULL, NULL, 2, '', 'BK-6A2A6B50C124C', 'completed', '2026-06-11 08:01:20');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `sector_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `problem_statement` text DEFAULT NULL,
  `solution` text DEFAULT NULL,
  `impact_potential` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_viewed` tinyint(1) DEFAULT 0,
  `viewed_at` timestamp NULL DEFAULT NULL,
  `likes` int(11) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `message` text DEFAULT NULL,
  `rating` int(11) DEFAULT 5,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `user_id`, `sector_id`, `title`, `description`, `problem_statement`, `solution`, `impact_potential`, `image`, `status`, `admin_viewed`, `viewed_at`, `likes`, `views`, `created_at`, `message`, `rating`, `full_name`, `phone`, `id_number`) VALUES
(7, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, 'approved', 0, NULL, 0, 0, '2026-06-08 07:25:54', 'wsdycdjnmkdsmkm dm', 5, 'shadrack mwendwa', '0706072020', ''),
(8, 5, NULL, '', NULL, NULL, NULL, NULL, NULL, 'approved', 0, NULL, 0, 0, '2026-06-08 09:07:41', 'thank you mmhh  hjhjjj n  j jbnnnnnnbbb', 4, 'kioko munyao', '0706072037', '34567890'),
(9, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, 'pending', 0, NULL, 0, 0, '2026-06-15 14:03:09', 'ttdw hfeoiweieii', 4, 'Shadrack Mwendwa', '0756789876', ''),
(10, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, 'pending', 0, NULL, 0, 0, '2026-06-15 14:05:00', 'riicfxfggv', 3, 'Shadrack Mwendwa', '0706072020', '');

-- --------------------------------------------------------

--
-- Table structure for table `feedback_comments`
--

CREATE TABLE `feedback_comments` (
  `id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_likes`
--

CREATE TABLE `feedback_likes` (
  `id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incubations`
--

CREATE TABLE `incubations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `idea_id` int(11) NOT NULL,
  `program_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `stage` enum('application','review','accepted','ongoing','graduated','dropped') DEFAULT 'application',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `funding_amount` decimal(10,2) DEFAULT NULL,
  `resources` text DEFAULT NULL,
  `progress` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mentors`
--

CREATE TABLE `mentors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `expertise` varchar(255) DEFAULT NULL,
  `years_experience` int(11) DEFAULT 0,
  `bio` text DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mentors`
--

INSERT INTO `mentors` (`id`, `user_id`, `expertise`, `years_experience`, `bio`, `linkedin_url`, `profile_image`, `is_verified`, `created_at`) VALUES
(1, 1, 'AI & Machine Learning, Startup Mentorship, Product Development', 15, 'Dr. Smith has over 15 years of experience in AI research and has mentored 50+ successful startups. He specializes in helping innovators turn their ideas into market-ready products.', 'https://linkedin.com/in/drsmith', NULL, 1, '2026-06-02 09:15:53');

-- --------------------------------------------------------

--
-- Table structure for table `mentorships`
--

CREATE TABLE `mentorships` (
  `id` int(11) NOT NULL,
  `mentor_id` int(11) NOT NULL,
  `mentee_id` int(11) NOT NULL,
  `idea_id` int(11) DEFAULT NULL,
  `objective` text DEFAULT NULL,
  `status` enum('pending','approved','ongoing','completed','rejected') DEFAULT 'pending',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `title_color` varchar(20) DEFAULT '#1a5c3a' COMMENT 'Custom title color for user side',
  `title_font_size` varchar(10) DEFAULT '28px' COMMENT 'Custom font size for user side',
  `slug` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `summary` varchar(500) DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL COMMENT 'Main featured image',
  `image_height` varchar(20) DEFAULT '400px' COMMENT 'Uniform image height for display',
  `featured_image_height` varchar(20) DEFAULT '400px',
  `gallery_image_height` varchar(20) DEFAULT '250px',
  `category` varchar(50) DEFAULT 'general',
  `status` varchar(20) DEFAULT 'draft',
  `views` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `title`, `title_color`, `title_font_size`, `slug`, `content`, `summary`, `featured_image`, `image_height`, `featured_image_height`, `gallery_image_height`, `category`, `status`, `views`, `created_by`, `created_at`, `updated_at`, `published_at`, `deleted_at`) VALUES
(1, 'MAKUENI LAUNCHES AJIRA KWA VIJANA PROGRAM AS OVER 2,200 YOUTHS SET TO BENEFIT (Wote Nziu Ward, 15th June 2026)', '#0c14ed', '22px', 'makueni-launches-ajira-kwa-vijana-program-as-over-2-200-youths-set-to-benefit-wote-nziu-ward-15th-june-2026-', '<p>The Government of Makueni County has launched the Ajira kwa Vijana program, with Wote Nziu Ward becoming the first ward to implement the initiative aimed at empowering youths through short-term employment opportunities.</p><p>Speaking during the launch, CECM for Gender, Children, Youth, Sports and Social Services, Eng. Sebastian Kyoni, announced that the county has allocated Ksh 2 million for the program, benefiting over 582 casual workers in the ward.</p><p>The initiative involves mitre drain construction, bush clearing, and unclogging of culverts and drainage systems to improve infrastructure while creating income opportunities for young people.</p><p>Kyoni noted that the program will also be rolled out in Nguu/Masumba, Makindu, Nguumo and Kikumbulyu North wards this week, bringing the total number of beneficiaries to more than 2,200 youths.</p><p>Area MCA Felix Mateso commended the initiative, saying it will empower youths and contribute to environmental conservation and community development.</p><p>The program is part of Governor Mutula Kilonzo Junior\'s commitment to creating opportunities that enhance youth livelihoods and participation in development.</p>', '', 'assets/images/news/1781615389_722037730_1613230944138273_2280554304521034579_n.jpg', '300px', '450px', '250px', 'gender', 'published', 25, 4, '2026-06-16 13:09:49', '2026-06-18 08:34:18', '2026-06-18 07:34:18', NULL),
(2, '𝐊𝐈𝐓𝐈𝐒𝐄 𝐅𝐀𝐑𝐌𝐄𝐑𝐒 𝐁𝐄𝐍𝐄𝐅𝐈𝐓 𝐅𝐑𝐎𝐌 𝐀𝐆𝐑𝐈𝐂𝐔𝐋𝐓𝐔𝐑𝐄 𝐀𝐍𝐃 𝐋𝐈𝐕𝐄𝐋𝐈𝐇𝐎𝐎𝐃 𝐒𝐔𝐏𝐏𝐎𝐑𝐓 𝐏𝐑𝐎𝐆𝐑𝐀𝐌𝐌𝐄', '#0c14ed', '22px', '-', '<p>Farmers from Kitise/Kithuki Ward on Tuesday benefited from an Agriculture and Livelihood Support Programme aimed at strengthening dairy farming, apiculture, and drought resilience to improve household incomes and agricultural productivity.</p><p>The programme saw the commissioning of a 1,000-litre solar-powered bulk milk cooler for the Kitise Farmers Cooperative Society, a move expected to benefit more than 600 dairy farmers through improved milk aggregation and reduced post-harvest losses.</p><p>Farmers also received 155 Langstroth beehives and modern beekeeping equipment, while vulnerable households were supported with livestock feed supplements to aid recovery from prolonged drought effects.</p><p>Speaking during the event, Deputy Governor Makueni-H.E Lucy Mulili said the County Government remains committed to investing in practical and sustainable interventions that increase productivity, create market opportunities, and improve farmers’ livelihoods.</p><p>Also addressing the gathering, County Assembly Speaker Douglas Mbilu praised the initiative, noting that empowering farmers and strengthening cooperatives is essential for rural economic growth and long-term prosperity.</p><p>Implemented through a partnership between the County Government, World Food Programme and National Drought Management Authority, the programme promotes sustainable, climate-smart and market-driven agriculture.</p><p>The event was attended by CECM Agriculture Damaris Kavoi, Chief Officer Livestock Japtheth Kiminza, MCA Kitise/Kithuki Ward Sebastian Muli and Clerk of the County Assembly Kevin Mutuku.</p>', '', 'assets/images/news/1781677382_725191880_1614612847333416_1219814236231931046_n.jpg', '400px', '450px', '250px', 'agriculture', 'published', 15, 4, '2026-06-17 06:23:02', '2026-06-18 08:35:08', '2026-06-18 07:35:08', NULL),
(4, '𝐌𝐚𝐤𝐮𝐞𝐧𝐢 𝐑𝐞𝐯𝐢𝐞𝐰𝐬 𝐏𝐫𝐨𝐠𝐫𝐞𝐬𝐬 𝐨𝐟 𝐍𝐮𝐭𝐫𝐢𝐭𝐢𝐨𝐧 𝐏𝐫𝐨𝐠𝐫𝐚𝐦𝐦𝐞, 𝐑𝐞𝐜𝐨𝐫𝐝𝐬 𝟗𝟖 𝐏𝐞𝐫 𝐂𝐞𝐧𝐭 𝐈𝐦𝐩𝐥𝐞𝐦𝐞𝐧𝐭𝐚𝐭𝐢𝐨𝐧 𝐑𝐚𝐭𝐞', '#0c14ed', '22px', 'news-1781771590', '<p>The County Government of Makueni, in collaboration with the Departments of Health Services, Agriculture, and Gender, and with support from Helen Keller International, has reviewed the implementation of activities under the first year of a two-year nutrition programme.</p><p>The review meeting, which brought together officers from the three departments, revealed that 98 per cent of the planned activities had been successfully implemented across the six sub-counties.</p><p>The programme, dubbed Transforming Lives Through Nutrition, has been under implementation since July 2025. It focuses on both nutrition-specific and nutrition-sensitive interventions aimed at improving the nutritional status and overall well-being of residents.</p><p>Nutrition-specific interventions are being spearheaded by the Department of Health Services, while nutrition-sensitive activities are being implemented by the Departments of Agriculture and Gender.</p><p>Helen Keller International, working in partnership with a consortium of development partners, is supporting the programme\'s implementation as part of efforts to strengthen nutrition outcomes and enhance household resilience across Makueni County.</p>', '', 'assets/images/news/1781771590_726814909_1615655673895800_1877157557725634323_n.jpg', '450px', '450px', '250px', 'agriculture', 'published', 2, 4, '2026-06-18 08:33:10', NULL, '2026-06-18 07:33:10', NULL),
(5, '𝐕𝐮𝐥𝐧𝐞𝐫𝐚𝐛𝐥𝐞 𝐆𝐫𝐨𝐮𝐩𝐬 𝐄𝐦𝐩𝐨𝐰𝐞𝐫𝐞𝐝 𝐓𝐡𝐫𝐨𝐮𝐠𝐡 𝐋𝐢𝐯𝐞𝐬𝐭𝐨𝐜𝐤 𝐒𝐮𝐩𝐩𝐨𝐫𝐭 𝐏𝐫𝐨𝐠𝐫𝐚𝐦𝐦𝐞 𝐢𝐧 𝐊𝐢𝐤𝐮𝐦𝐛𝐮𝐥𝐲𝐮 𝐖𝐚𝐫𝐝𝐬.𝟙𝟟𝕥𝕙 𝕁𝕦𝕟𝕖 , 𝟚𝟘𝟚𝟞 𝕂𝕚𝕓𝕨𝕖𝕫𝕚,.', '#0c14ed', '22px', 'news-1781774057', '<p>Vulnerable groups, including persons with disabilities, women and youth from Kikumbulyu North and Kikumbulyu South wards, on Tuesday benefited from a livestock support programme aimed at enhancing economic empowerment and improving household incomes.</p><p>Under the ward-based initiative, the Department of Agriculture distributed 51 does and 54 bucks to farmers as part of a continuous breed improvement programme targeting faster-growing goats with higher mature weights. Beneficiaries also received 100 bags of 50kg drought pellets to boost animal productivity, improve nutrition and accelerate breeding.</p><p>The exercise was officiated by Chief Officer for Livestock, Fisheries and Cooperative Development, Japheth Kiminza, who said the programme seeks to build resilient livelihoods through sustainable livestock production and improved breeds.</p><p>Kiminza urged beneficiaries to embrace proper animal husbandry practices to ensure the project\'s long-term success and sustainability.</p><p>The event was also attended by Jades Kalunda, who lauded the initiative for creating economic opportunities and strengthening community resilience</p>', '', 'assets/images/news/1781774057_725588655_2074589426824958_1804210275879929380_n.jpg', '450px', '450px', '250px', 'agriculture', 'published', 4, 4, '2026-06-18 09:14:17', NULL, '2026-06-18 08:14:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `news_gallery`
--

CREATE TABLE `news_gallery` (
  `id` int(11) NOT NULL,
  `news_id` int(11) NOT NULL COMMENT 'Reference to news article',
  `image_path` varchar(255) NOT NULL COMMENT 'Path to gallery image',
  `caption` varchar(255) DEFAULT NULL COMMENT 'Optional image caption',
  `sort_order` int(11) DEFAULT 0 COMMENT 'Order of display',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `news_gallery`
--

INSERT INTO `news_gallery` (`id`, `news_id`, `image_path`, `caption`, `sort_order`, `created_at`) VALUES
(1, 1, 'assets/images/news/gallery/1781615389_0_722078094_1613230887471612_4289341503543705339_n.jpg', '', 0, '2026-06-16 13:09:49'),
(2, 1, 'assets/images/news/gallery/1781615389_1_724270807_1613231077471593_7150413806816091602_n.jpg', '', 1, '2026-06-16 13:09:49'),
(3, 1, 'assets/images/news/gallery/1781615389_2_724613998_1613231000804934_413968623705909800_n.jpg', '', 2, '2026-06-16 13:09:49'),
(4, 2, 'assets/images/news/gallery/1781677382_0_725120948_1614612164000151_1144715693603096638_n.jpg', '', 0, '2026-06-17 06:23:02'),
(5, 2, 'assets/images/news/gallery/1781677382_1_725133257_1614612320666802_8647011299022707659_n.jpg', '', 1, '2026-06-17 06:23:02'),
(6, 2, 'assets/images/news/gallery/1781677382_2_725436351_1614612587333442_3647303546370517229_n.jpg', '', 2, '2026-06-17 06:23:02'),
(7, 2, 'assets/images/news/gallery/1781677382_3_725437308_1614612370666797_6346676159984750864_n.jpg', '', 3, '2026-06-17 06:23:02'),
(8, 4, 'assets/images/news/gallery/1781771590_0_725592956_1615655713895796_2327876217778509841_n.jpg', '', 0, '2026-06-18 08:33:10'),
(9, 4, 'assets/images/news/gallery/1781771590_1_725627294_1615655457229155_7541767252461790860_n.jpg', '', 1, '2026-06-18 08:33:10'),
(10, 4, 'assets/images/news/gallery/1781771590_2_726814909_1615655673895800_1877157557725634323_n.jpg', '', 2, '2026-06-18 08:33:10'),
(11, 4, 'assets/images/news/gallery/1781771590_3_726826502_1615655307229170_7806276826827907651_n.jpg', '', 3, '2026-06-18 08:33:10'),
(12, 4, 'assets/images/news/gallery/1781771590_4_726978713_1615655230562511_4511564463882613085_n.jpg', '', 4, '2026-06-18 08:33:10'),
(13, 5, 'assets/images/news/gallery/1781774057_0_725084283_1309328817513940_5289348724632134669_n.jpg', '', 0, '2026-06-18 09:14:17'),
(14, 5, 'assets/images/news/gallery/1781774057_1_725160031_1045637798129438_8081062438891042583_n.jpg', '', 1, '2026-06-18 09:14:17'),
(15, 5, 'assets/images/news/gallery/1781774057_2_725166301_1722846632223873_802557300605635962_n.jpg', '', 2, '2026-06-18 09:14:17'),
(16, 5, 'assets/images/news/gallery/1781774057_3_725440158_1575405780608803_7969500241381400777_n.jpg', '', 3, '2026-06-18 09:14:17'),
(17, 5, 'assets/images/news/gallery/1781774057_4_725588655_2074589426824958_1804210275879929380_n.jpg', '', 4, '2026-06-18 09:14:17'),
(18, 5, 'assets/images/news/gallery/1781774057_5_726270332_1020375230569254_6628175439388656150_n.jpg', '', 5, '2026-06-18 09:14:17'),
(19, 5, 'assets/images/news/gallery/1781774057_6_726321988_1147194580953549_2281143475081970307_n.jpg', '', 6, '2026-06-18 09:14:17');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 2, 'Welcome to Innovation Hub!', 'Thank you for joining our community. Start by exploring ideas and registering for trainings.', 'welcome', 0, '2026-06-02 09:15:54');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('mpesa','bank','cash','online') DEFAULT 'mpesa',
  `transaction_code` varchar(100) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sectors`
--

CREATE TABLE `sectors` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sectors`
--

INSERT INTO `sectors` (`id`, `name`, `slug`, `description`, `icon`, `image`, `color`, `created_at`) VALUES
(1, 'Artificial Intelligence', 'ai', 'AI and Machine Learning innovations transforming industries worldwide', 'fa-microchip', NULL, '#667eea', '2026-06-02 09:15:53'),
(2, 'FinTech', 'fintech', 'Financial Technology solutions for modern banking and payments', 'fa-chart-line', NULL, '#f59e0b', '2026-06-02 09:15:53'),
(3, 'HealthTech', 'healthtech', 'Healthcare technology innovations improving patient care', 'fa-heartbeat', NULL, '#10b981', '2026-06-02 09:15:53'),
(4, 'EdTech', 'edtech', 'Educational technology solutions for better learning outcomes', 'fa-graduation-cap', NULL, '#3b82f6', '2026-06-02 09:15:53'),
(5, 'GreenTech', 'greentech', 'Environmental and sustainable technology solutions', 'fa-leaf', NULL, '#22c55e', '2026-06-02 09:15:53'),
(6, 'AgriTech', 'agritech', 'Agricultural technology solutions for modern farming', 'fa-tractor', NULL, '#eab308', '2026-06-02 09:15:53'),
(7, 'Blockchain', 'blockchain', 'Blockchain and Web3 innovations', 'fa-link', NULL, '#8b5cf6', '2026-06-02 09:15:53'),
(8, 'IoT', 'iot', 'Internet of Things and smart devices', 'fa-wifi', NULL, '#ef4444', '2026-06-02 09:15:53');

-- --------------------------------------------------------

--
-- Table structure for table `trainings`
--

CREATE TABLE `trainings` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `objectives` text DEFAULT NULL,
  `trainer_name` varchar(100) DEFAULT NULL,
  `trainer_bio` text DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `type` enum('online','offline','hybrid') DEFAULT 'online',
  `max_participants` int(11) DEFAULT 50,
  `price` decimal(10,2) DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `materials` text DEFAULT NULL,
  `duration_hours` int(11) DEFAULT 0,
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `training_registrations`
--

CREATE TABLE `training_registrations` (
  `id` int(11) NOT NULL,
  `training_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `attendance_status` enum('pending','confirmed','attended','absent') DEFAULT 'pending',
  `status` varchar(20) DEFAULT 'pending',
  `completion_status` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `last_password_change` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `avatar`, `role`, `is_active`, `created_at`, `updated_at`, `reset_token`, `reset_token_expiry`, `last_password_change`) VALUES
(1, 'admin', 'admin@innovationhub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', NULL, NULL, 'admin', 1, '2026-06-02 09:15:53', '2026-06-30 07:34:04', NULL, NULL, '2026-06-30 10:34:04'),
(2, 'john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', '+1234567890', NULL, 'user', 1, '2026-06-02 09:15:53', '2026-06-30 07:34:04', NULL, NULL, '2026-06-30 10:34:04'),
(3, 'jane_innovator', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Innovator', '0712345678', NULL, 'user', 1, '2026-06-02 09:15:53', '2026-06-30 07:34:04', NULL, NULL, '2026-06-30 10:34:04'),
(4, 'lucky', 'luckymutua102@gmail.com', '$2y$10$iDz7rRxVtxaeV48IL/7Ss.HhJHEd3hak7HTQdjz3szLnP2ZOOkCqm', 'lucky mutua', '0703125815', NULL, 'admin', 1, '2026-06-02 09:36:04', '2026-06-30 07:34:04', NULL, NULL, '2026-06-30 10:34:04'),
(5, 'shadrack', 'shadrackmwendwa102@gmail.com', '$2y$10$BEtCvTQnBUoK7pyyMrqyluiTfxqNBkgw5FluCMLjl/tUqZwfsTTgu', 'shadrack mwendwa', '0706072020', NULL, 'user', 1, '2026-06-02 09:50:42', '2026-06-30 07:34:04', NULL, NULL, '2026-06-30 10:34:04'),
(6, 'punye', 'purity23@gmail.com', '$2y$10$mlwsroOswKqbNth8vpQBauDaGq.MxWswBbYOtWFQbcVK5jJcEUwvG', 'kapunye punye', '0756789876', NULL, 'user', 1, '2026-06-12 08:46:24', '2026-06-30 07:34:04', NULL, NULL, '2026-06-30 10:34:04'),
(7, '@paulmatata', 'paulmatata@gmail.com', '$2y$10$FgfVoVHSTQ3wSFEQLfdYju5O4ojG8f5fLxQIRIFH2vcpdrtBPJpZG', 'paul Matata', '0756789876', NULL, 'user', 1, '2026-06-12 12:48:10', '2026-06-30 07:34:04', NULL, NULL, '2026-06-30 10:34:04'),
(9, '', 'Kelvimuendo102@gmail.com', '$2y$10$DUgKN9fk9NzT5Efti8pjWO3P0H96y1SoZiBrNvGepO6pp36yTbKlW', 'kelvin Muendo', '0790807060', NULL, 'user', 1, '2026-06-15 12:42:52', '2026-06-30 07:34:04', NULL, NULL, '2026-06-30 10:34:04'),
(11, 'Kelvinmu', 'kelvinmu102@gmail.com', '$2y$10$y/r3JlqKymeZX6X1devKBO43BRQMn316gdDgj9/cGhv6wSh6QE8Ba', 'Kelvin Muendo', '0706072020', NULL, 'user', 1, '2026-06-15 13:45:58', '2026-06-30 07:34:04', NULL, NULL, '2026-06-30 10:34:04'),
(12, '@joseph', 'josephkioko102@gmail.com', '$2y$10$K74Yf0XkchILNQRPLj822.nmQ7GHV9qL3xV6XHilcjkT3u4XnReQC', 'joseph kioko', '0789899898', NULL, 'user', 1, '2026-06-22 09:20:41', '2026-06-30 07:34:04', NULL, NULL, '2026-06-30 10:34:04'),
(13, '@mwendwa', 'shadrackmwendwa477@gmail.com', '$2y$10$f8HjKsNySj2fd0sjSUSxpuEXHykyx8si4uW1nki3CLbcb/egK.2vK', 'Shadrack Mwendwa', '0706072020', NULL, 'user', 1, '2026-07-01 07:08:24', '2026-07-03 09:34:03', '3af11ce4cb1fd3734ab9659bc9ce8e973dce565dfedaac2b2f5395f72c6600f0', '2026-07-03 12:34:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `venue_bookings`
--

CREATE TABLE `venue_bookings` (
  `id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_date` date NOT NULL,
  `event_time` time NOT NULL,
  `organizer_name` varchar(255) NOT NULL,
  `phone_primary` varchar(50) NOT NULL,
  `phone_secondary` varchar(50) NOT NULL,
  `number_of_people` int(11) NOT NULL,
  `event_type` enum('wedding','conference','birthday','corporate','workshop','other') DEFAULT 'other',
  `special_requirements` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ajiry_bookings`
--
ALTER TABLE `ajiry_bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_code` (`booking_code`);

--
-- Indexes for table `ajiry_events`
--
ALTER TABLE `ajiry_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ajiry_instruments`
--
ALTER TABLE `ajiry_instruments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ajiry_rules`
--
ALTER TABLE `ajiry_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ajiry_services`
--
ALTER TABLE `ajiry_services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ajiry_settings`
--
ALTER TABLE `ajiry_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_number` (`certificate_number`),
  ADD KEY `registration_id` (`registration_id`);

--
-- Indexes for table `certifications`
--
ALTER TABLE `certifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_number` (`certificate_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `training_id` (`training_id`);

--
-- Indexes for table `class_feedback`
--
ALTER TABLE `class_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registration_id` (`registration_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `class_notes`
--
ALTER TABLE `class_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `class_registrations`
--
ALTER TABLE `class_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_number` (`certificate_number`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `computer_classes`
--
ALTER TABLE `computer_classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `event_bookings`
--
ALTER TABLE `event_bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_code` (`booking_code`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `sector_id` (`sector_id`);

--
-- Indexes for table `feedback_comments`
--
ALTER TABLE `feedback_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idea_id` (`idea_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `feedback_likes`
--
ALTER TABLE `feedback_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`idea_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `incubations`
--
ALTER TABLE `incubations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idea_id` (`idea_id`);

--
-- Indexes for table `mentors`
--
ALTER TABLE `mentors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `mentorships`
--
ALTER TABLE `mentorships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mentor_id` (`mentor_id`),
  ADD KEY `mentee_id` (`mentee_id`),
  ADD KEY `idea_id` (`idea_id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `news_gallery`
--
ALTER TABLE `news_gallery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `news_id` (`news_id`),
  ADD KEY `sort_order` (`sort_order`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registration_id` (`registration_id`);

--
-- Indexes for table `sectors`
--
ALTER TABLE `sectors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `trainings`
--
ALTER TABLE `trainings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `training_registrations`
--
ALTER TABLE `training_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_registration` (`training_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `venue_bookings`
--
ALTER TABLE `venue_bookings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ajiry_bookings`
--
ALTER TABLE `ajiry_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ajiry_events`
--
ALTER TABLE `ajiry_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ajiry_instruments`
--
ALTER TABLE `ajiry_instruments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ajiry_rules`
--
ALTER TABLE `ajiry_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `ajiry_services`
--
ALTER TABLE `ajiry_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ajiry_settings`
--
ALTER TABLE `ajiry_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `certifications`
--
ALTER TABLE `certifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_feedback`
--
ALTER TABLE `class_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `class_notes`
--
ALTER TABLE `class_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_registrations`
--
ALTER TABLE `class_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `computer_classes`
--
ALTER TABLE `computer_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `event_bookings`
--
ALTER TABLE `event_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `feedback_comments`
--
ALTER TABLE `feedback_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `feedback_likes`
--
ALTER TABLE `feedback_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incubations`
--
ALTER TABLE `incubations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mentors`
--
ALTER TABLE `mentors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `mentorships`
--
ALTER TABLE `mentorships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `news_gallery`
--
ALTER TABLE `news_gallery`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sectors`
--
ALTER TABLE `sectors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `trainings`
--
ALTER TABLE `trainings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `training_registrations`
--
ALTER TABLE `training_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `venue_bookings`
--
ALTER TABLE `venue_bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `class_registrations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `certifications`
--
ALTER TABLE `certifications`
  ADD CONSTRAINT `certifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `certifications_ibfk_2` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`);

--
-- Constraints for table `class_feedback`
--
ALTER TABLE `class_feedback`
  ADD CONSTRAINT `class_feedback_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `class_registrations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_feedback_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_feedback_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `computer_classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_notes`
--
ALTER TABLE `class_notes`
  ADD CONSTRAINT `class_notes_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `computer_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_notes_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `class_registrations`
--
ALTER TABLE `class_registrations`
  ADD CONSTRAINT `class_registrations_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `computer_classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `class_registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `feedback_comments`
--
ALTER TABLE `feedback_comments`
  ADD CONSTRAINT `feedback_comments_ibfk_1` FOREIGN KEY (`idea_id`) REFERENCES `feedback` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback_likes`
--
ALTER TABLE `feedback_likes`
  ADD CONSTRAINT `feedback_likes_ibfk_1` FOREIGN KEY (`idea_id`) REFERENCES `feedback` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `incubations`
--
ALTER TABLE `incubations`
  ADD CONSTRAINT `incubations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `incubations_ibfk_2` FOREIGN KEY (`idea_id`) REFERENCES `feedback` (`id`);

--
-- Constraints for table `mentors`
--
ALTER TABLE `mentors`
  ADD CONSTRAINT `mentors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mentorships`
--
ALTER TABLE `mentorships`
  ADD CONSTRAINT `mentorships_ibfk_1` FOREIGN KEY (`mentor_id`) REFERENCES `mentors` (`id`),
  ADD CONSTRAINT `mentorships_ibfk_2` FOREIGN KEY (`mentee_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `mentorships_ibfk_3` FOREIGN KEY (`idea_id`) REFERENCES `feedback` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `news_gallery`
--
ALTER TABLE `news_gallery`
  ADD CONSTRAINT `news_gallery_ibfk_1` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `class_registrations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `training_registrations`
--
ALTER TABLE `training_registrations`
  ADD CONSTRAINT `training_registrations_ibfk_1` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `training_registrations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
