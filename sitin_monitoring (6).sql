-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2025 at 12:51 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sitin_monitoring`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_sitin`
--

CREATE TABLE `admin_sitin` (
  `id` int(11) NOT NULL,
  `lab_classroom` varchar(50) NOT NULL,
  `purpose` varchar(50) NOT NULL,
  `idno` varchar(50) NOT NULL,
  `time_in` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_sitin`
--

INSERT INTO `admin_sitin` (`id`, `lab_classroom`, `purpose`, `idno`, `time_in`) VALUES
(34, '524', 'C Programming', '123456', '2025-05-04 19:15:55'),
(55, 'Lab 544', 'JAVA Programming', '22598967', '2025-05-16 06:49:28');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `message`, `created_at`, `updated_at`) VALUES
(8, '📢 ANNOUNCEMENT: Sit-In Monitoring System is Now Live!', 'Attention all students and faculty!\r\n\r\nWe are excited to introduce the Sit-In Monitoring System, a new platform designed to streamline and enhance the process of monitoring sit-ins in lab classrooms. This system ensures a more organized and efficient way of recording sit-in sessions.', '2025-03-28 00:46:24', '2025-03-28 01:55:40');

-- --------------------------------------------------------

--
-- Table structure for table `computer_labs`
--

CREATE TABLE `computer_labs` (
  `id` int(11) NOT NULL,
  `lab_number` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `capacity` int(11) DEFAULT 25,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `computer_labs`
--

INSERT INTO `computer_labs` (`id`, `lab_number`, `description`, `capacity`, `status`) VALUES
(1, 524, 'Computer Lab 524', 25, 'active'),
(2, 526, 'Computer Lab 526', 25, 'active'),
(3, 528, 'Computer Lab 528', 25, 'active'),
(4, 530, 'Computer Lab 530', 25, 'active'),
(5, 542, 'Computer Lab 542', 25, 'active'),
(6, 544, 'Computer Lab 544', 25, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `computer_stations`
--

CREATE TABLE `computer_stations` (
  `id` int(11) NOT NULL,
  `lab_id` int(11) NOT NULL,
  `pc_number` int(11) NOT NULL,
  `specifications` text DEFAULT NULL,
  `status` enum('available','occupied','maintenance') DEFAULT 'available',
  `current_user_id` varchar(20) DEFAULT NULL,
  `reservation_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `computer_stations`
--

INSERT INTO `computer_stations` (`id`, `lab_id`, `pc_number`, `specifications`, `status`, `current_user_id`, `reservation_id`) VALUES
(1, 1, 1, NULL, 'available', '12345', 37),
(2, 2, 1, NULL, 'occupied', '12345', 34),
(3, 3, 1, NULL, 'occupied', '12345', 30),
(4, 4, 1, NULL, 'available', NULL, NULL),
(5, 5, 1, NULL, 'available', NULL, NULL),
(6, 6, 1, NULL, 'available', NULL, NULL),
(7, 1, 2, NULL, 'available', NULL, NULL),
(8, 2, 2, NULL, 'available', NULL, NULL),
(9, 3, 2, NULL, 'available', NULL, NULL),
(10, 4, 2, NULL, 'available', NULL, NULL),
(11, 5, 2, NULL, 'available', NULL, NULL),
(12, 6, 2, NULL, 'available', NULL, NULL),
(13, 1, 3, NULL, 'available', NULL, NULL),
(14, 2, 3, NULL, 'available', NULL, NULL),
(15, 3, 3, NULL, 'available', NULL, NULL),
(16, 4, 3, NULL, 'available', NULL, NULL),
(17, 5, 3, NULL, 'available', NULL, NULL),
(18, 6, 3, NULL, 'available', NULL, NULL),
(19, 1, 4, NULL, 'available', NULL, NULL),
(20, 2, 4, NULL, 'available', NULL, NULL),
(21, 3, 4, NULL, 'available', NULL, NULL),
(22, 4, 4, NULL, 'available', NULL, NULL),
(23, 5, 4, NULL, 'available', NULL, NULL),
(24, 6, 4, NULL, 'available', NULL, NULL),
(25, 1, 5, NULL, 'available', NULL, NULL),
(26, 2, 5, NULL, 'available', NULL, NULL),
(27, 3, 5, NULL, 'available', NULL, NULL),
(28, 4, 5, NULL, 'available', NULL, NULL),
(29, 5, 5, NULL, 'available', NULL, NULL),
(30, 6, 5, NULL, 'available', NULL, NULL),
(31, 1, 6, NULL, 'available', NULL, NULL),
(32, 2, 6, NULL, 'available', NULL, NULL),
(33, 3, 6, NULL, 'available', NULL, NULL),
(34, 4, 6, NULL, 'occupied', '12345', 35),
(35, 5, 6, NULL, 'available', NULL, NULL),
(36, 6, 6, NULL, 'occupied', '12345', 43),
(37, 1, 7, NULL, 'available', NULL, NULL),
(38, 2, 7, NULL, 'available', NULL, NULL),
(39, 3, 7, NULL, 'available', NULL, NULL),
(40, 4, 7, NULL, 'available', NULL, NULL),
(41, 5, 7, NULL, 'available', NULL, NULL),
(42, 6, 7, NULL, 'available', NULL, NULL),
(43, 1, 8, NULL, 'available', NULL, NULL),
(44, 2, 8, NULL, 'available', NULL, NULL),
(45, 3, 8, NULL, 'available', NULL, NULL),
(46, 4, 8, NULL, 'available', NULL, NULL),
(47, 5, 8, NULL, 'available', NULL, NULL),
(48, 6, 8, NULL, 'available', NULL, NULL),
(49, 1, 9, NULL, 'available', NULL, NULL),
(50, 2, 9, NULL, 'available', NULL, NULL),
(51, 3, 9, NULL, 'available', NULL, NULL),
(52, 4, 9, NULL, 'available', NULL, NULL),
(53, 5, 9, NULL, 'available', NULL, NULL),
(54, 6, 9, NULL, 'available', NULL, NULL),
(55, 1, 10, NULL, 'available', NULL, NULL),
(56, 2, 10, NULL, 'available', NULL, NULL),
(57, 3, 10, NULL, 'occupied', '22598967', 100),
(58, 4, 10, NULL, 'occupied', '12345', 55),
(59, 5, 10, NULL, 'available', NULL, NULL),
(60, 6, 10, NULL, 'occupied', '22598967', 106),
(61, 1, 11, NULL, 'available', NULL, NULL),
(62, 2, 11, NULL, 'available', NULL, NULL),
(63, 3, 11, NULL, 'occupied', '22598967', 96),
(64, 4, 11, NULL, 'available', NULL, NULL),
(65, 5, 11, NULL, 'occupied', '22598967', 39),
(66, 6, 11, NULL, 'available', NULL, NULL),
(67, 1, 12, NULL, 'available', NULL, NULL),
(68, 2, 12, NULL, 'available', NULL, NULL),
(69, 3, 12, NULL, 'occupied', '12345', 76),
(70, 4, 12, NULL, 'available', NULL, NULL),
(71, 5, 12, NULL, 'available', NULL, NULL),
(72, 6, 12, NULL, 'available', NULL, NULL),
(73, 1, 13, NULL, 'available', NULL, NULL),
(74, 2, 13, NULL, 'available', NULL, NULL),
(75, 3, 13, NULL, 'available', NULL, NULL),
(76, 4, 13, NULL, 'occupied', '22598967', 104),
(77, 5, 13, NULL, 'occupied', '12345', 85),
(78, 6, 13, NULL, 'available', NULL, NULL),
(79, 1, 14, NULL, 'available', NULL, NULL),
(80, 2, 14, NULL, 'available', NULL, NULL),
(81, 3, 14, NULL, 'available', NULL, NULL),
(82, 4, 14, NULL, 'occupied', '12345', 92),
(83, 5, 14, NULL, 'occupied', '12345', 36),
(84, 6, 14, NULL, 'occupied', '12345', 38),
(85, 1, 15, NULL, 'available', NULL, NULL),
(86, 2, 15, NULL, 'available', NULL, NULL),
(87, 3, 15, NULL, 'available', NULL, NULL),
(88, 4, 15, NULL, 'available', NULL, NULL),
(89, 5, 15, NULL, 'available', NULL, NULL),
(90, 6, 15, NULL, 'available', NULL, NULL),
(91, 1, 16, NULL, 'available', NULL, NULL),
(92, 2, 16, NULL, 'available', NULL, NULL),
(93, 3, 16, NULL, 'available', NULL, NULL),
(94, 4, 16, NULL, 'occupied', '12345', 58),
(95, 5, 16, NULL, 'available', NULL, NULL),
(96, 6, 16, NULL, 'available', NULL, NULL),
(97, 1, 17, NULL, 'available', NULL, NULL),
(98, 2, 17, NULL, 'available', NULL, NULL),
(99, 3, 17, NULL, 'available', NULL, NULL),
(100, 4, 17, NULL, 'available', NULL, NULL),
(101, 5, 17, NULL, 'available', NULL, NULL),
(102, 6, 17, NULL, 'available', NULL, NULL),
(103, 1, 18, NULL, 'available', NULL, NULL),
(104, 2, 18, NULL, 'available', NULL, NULL),
(105, 3, 18, NULL, 'available', NULL, NULL),
(106, 4, 18, NULL, 'available', NULL, NULL),
(107, 5, 18, NULL, 'available', NULL, NULL),
(108, 6, 18, NULL, 'available', NULL, NULL),
(109, 1, 19, NULL, 'available', NULL, NULL),
(110, 2, 19, NULL, 'available', NULL, NULL),
(111, 3, 19, NULL, 'available', NULL, NULL),
(112, 4, 19, NULL, 'available', NULL, NULL),
(113, 5, 19, NULL, 'available', NULL, NULL),
(114, 6, 19, NULL, 'available', NULL, NULL),
(115, 1, 20, NULL, 'available', NULL, NULL),
(116, 2, 20, NULL, 'available', NULL, NULL),
(117, 3, 20, NULL, 'available', NULL, NULL),
(118, 4, 20, NULL, 'available', NULL, NULL),
(119, 5, 20, NULL, 'available', NULL, NULL),
(120, 6, 20, NULL, 'occupied', '22598967', 40),
(121, 1, 21, NULL, 'available', NULL, NULL),
(122, 2, 21, NULL, 'available', NULL, NULL),
(123, 3, 21, NULL, 'available', NULL, NULL),
(124, 4, 21, NULL, 'available', NULL, NULL),
(125, 5, 21, NULL, 'available', NULL, NULL),
(126, 6, 21, NULL, 'available', NULL, NULL),
(127, 1, 22, NULL, 'available', NULL, NULL),
(128, 2, 22, NULL, 'available', NULL, NULL),
(129, 3, 22, NULL, 'available', NULL, NULL),
(130, 4, 22, NULL, 'available', NULL, NULL),
(131, 5, 22, NULL, 'occupied', '12345', 75),
(132, 6, 22, NULL, 'available', NULL, NULL),
(133, 1, 23, NULL, 'available', NULL, NULL),
(134, 2, 23, NULL, 'available', NULL, NULL),
(135, 3, 23, NULL, 'available', NULL, NULL),
(136, 4, 23, NULL, 'available', NULL, NULL),
(137, 5, 23, NULL, 'available', NULL, NULL),
(138, 6, 23, NULL, 'available', NULL, NULL),
(139, 1, 24, NULL, 'occupied', '12345', 51),
(140, 2, 24, NULL, 'available', NULL, NULL),
(141, 3, 24, NULL, 'available', NULL, NULL),
(142, 4, 24, NULL, 'available', NULL, NULL),
(143, 5, 24, NULL, 'available', NULL, NULL),
(144, 6, 24, NULL, 'available', NULL, NULL),
(145, 1, 25, NULL, 'occupied', '22598967', 50),
(146, 2, 25, NULL, 'available', NULL, NULL),
(147, 3, 25, NULL, 'occupied', '12345', 56),
(148, 4, 25, NULL, 'occupied', '22598967', 41),
(149, 5, 25, NULL, 'available', NULL, NULL),
(150, 6, 25, NULL, 'occupied', '22598967', 49);

-- --------------------------------------------------------

--
-- Table structure for table `lab_activity_log`
--

CREATE TABLE `lab_activity_log` (
  `id` int(11) NOT NULL,
  `pc_id` int(11) DEFAULT NULL,
  `user_id` varchar(20) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `related_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`, `related_id`) VALUES
(68, '22598967', 'Your reservation for Lab 530 PC 17 has been rejected.', 0, '2025-05-15 22:49:03', 105),
(69, '22598967', 'Your reservation for Lab 544 PC 10 has been approved!', 0, '2025-05-15 22:49:28', 106);

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `student_name` varchar(150) NOT NULL,
  `purpose` text NOT NULL,
  `lab_number` varchar(20) NOT NULL,
  `pc_number` varchar(20) NOT NULL,
  `date` date NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Completed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `student_id`, `student_name`, `purpose`, `lab_number`, `pc_number`, `date`, `time_in`, `time_out`, `status`, `created_at`) VALUES
(100, '22598967', 'Olandag, Ronel Veranga', 'Digital Logic & Design', '528', '10', '2025-05-16', '13:46:48', NULL, 'Approved', '2025-05-15 11:46:48'),
(101, '22598967', 'Olandag, Ronel Veranga', 'System Integration & Architechture', '528', '16', '2025-05-16', '13:47:14', NULL, 'Rejected', '2025-05-15 11:47:14'),
(102, '22598967', 'Olandag, Ronel Veranga', 'Embedded Systems & Iot', '530', '9', '2025-05-16', '13:48:29', NULL, 'Rejected', '2025-05-15 11:48:29'),
(103, '22598967', 'Olandag, Ronel Veranga', 'Embedded Systems & Iot', '542', '13', '2025-05-16', '13:49:29', NULL, 'Rejected', '2025-05-15 11:49:29'),
(104, '22598967', 'Olandag, Ronel Veranga', 'System Integration & Architechture', '530', '13', '2025-05-17', '13:49:48', NULL, 'Approved', '2025-05-15 11:49:48'),
(105, '22598967', 'Olandag, Ronel Veranga', 'System Integration & Architechture', '530', '17', '2025-05-17', '00:48:44', NULL, 'Rejected', '2025-05-15 22:48:44'),
(106, '22598967', 'Olandag, Ronel Veranga', 'JAVA Programming', '544', '10', '2025-05-17', '00:49:23', NULL, 'Approved', '2025-05-15 22:49:23');

-- --------------------------------------------------------

--
-- Table structure for table `resources`
--

CREATE TABLE `resources` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(512) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `available_to` enum('all','students','admins') NOT NULL DEFAULT 'all',
  `uploaded_by` int(11) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resources`
--

INSERT INTO `resources` (`id`, `title`, `file_name`, `file_path`, `file_size`, `file_type`, `description`, `available_to`, `uploaded_by`, `upload_date`, `is_deleted`) VALUES
(1, 'fsdfdca', 'REACTION PAPER.pdf', 'uploads/resources/67fe60572bdbc_REACTION PAPER.pdf', 283200, 'pdf', 'sdfsdfsf', 'all', 1, '2025-04-15 13:34:15', 0),
(2, 'asasdad', 'CHAPTER II.docx', 'uploads/resources/67fe61d41df07_CHAPTER II.docx', 13866, 'docx', 'asdasdasd', 'all', 1, '2025-04-15 13:40:36', 0),
(3, 'asasdad', 'CHAPTER II.docx', 'uploads/resources/67fe691a1312f_CHAPTER II.docx', 13866, 'docx', 'asdasdasd', 'all', 1, '2025-04-15 14:11:38', 0),
(4, 'asasdad', 'CHAPTER II.docx', 'uploads/resources/67fe6926dfc8d_CHAPTER II.docx', 13866, 'docx', 'asdasdasd', 'all', 1, '2025-04-15 14:11:50', 0),
(5, 'ronel', 'Event capture.pdf', 'uploads/resources/68039549815ec_Event capture.pdf', 356705, 'pdf', 'hello world', 'all', 1, '2025-04-19 12:21:29', 0);

-- --------------------------------------------------------

--
-- Table structure for table `resource_downloads`
--

CREATE TABLE `resource_downloads` (
  `id` int(11) NOT NULL,
  `resource_id` int(11) NOT NULL,
  `downloaded_by` int(11) DEFAULT NULL,
  `download_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reward_conversions`
--

CREATE TABLE `reward_conversions` (
  `id` int(11) NOT NULL,
  `student_idno` varchar(50) NOT NULL,
  `points_converted` int(11) NOT NULL,
  `sessions_granted` int(11) NOT NULL,
  `conversion_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reward_conversions`
--

INSERT INTO `reward_conversions` (`id`, `student_idno`, `points_converted`, `sessions_granted`, `conversion_date`) VALUES
(1, '12345', 3, 1, '2025-04-13 22:01:44'),
(2, '12345', 3, 1, '2025-04-13 22:02:45'),
(3, '22598967', 3, 1, '2025-04-13 22:05:37'),
(4, '123456', 3, 1, '2025-04-13 22:05:37'),
(5, '12345', 3, 1, '2025-04-13 22:06:19'),
(6, '22598967', 3, 1, '2025-05-13 23:42:26'),
(7, '12345', 3, 1, '2025-05-14 00:09:50'),
(8, '12345', 3, 1, '2025-05-14 20:15:55'),
(9, '22598967', 3, 1, '2025-05-16 06:47:18');

-- --------------------------------------------------------

--
-- Table structure for table `reward_history`
--

CREATE TABLE `reward_history` (
  `id` int(11) NOT NULL,
  `student_idno` varchar(50) NOT NULL,
  `points_awarded` int(11) NOT NULL,
  `awarded_by` varchar(50) DEFAULT NULL,
  `award_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reward_history`
--

INSERT INTO `reward_history` (`id`, `student_idno`, `points_awarded`, `awarded_by`, `award_date`) VALUES
(1, '22598967', 1, NULL, '2025-04-13 13:04:40'),
(2, '22598967', 1, NULL, '2025-04-13 13:09:35'),
(3, '22598967', 1, NULL, '2025-04-13 13:09:45'),
(4, '22598967', 1, NULL, '2025-04-13 13:09:51'),
(5, '22598967', 1, NULL, '2025-04-13 13:14:52'),
(6, '22598967', 1, NULL, '2025-04-13 13:15:03');

-- --------------------------------------------------------

--
-- Table structure for table `sitin_history`
--

CREATE TABLE `sitin_history` (
  `id` int(11) NOT NULL,
  `student_idno` varchar(50) NOT NULL,
  `lab_classroom` varchar(100) NOT NULL,
  `purpose` text NOT NULL,
  `sitin_time` datetime NOT NULL DEFAULT current_timestamp(),
  `feedback` text DEFAULT NULL,
  `time_in` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitin_history`
--

INSERT INTO `sitin_history` (`id`, `student_idno`, `lab_classroom`, `purpose`, `sitin_time`, `feedback`, `time_in`) VALUES
(1, '123456', '528', 'C Programming', '2025-05-04 19:14:41', NULL, '2025-04-13 22:12:45'),
(2, '12345', '544', 'Mobile Application', '2025-05-04 19:20:02', NULL, '2025-04-13 22:11:56'),
(3, '22598967', '542', 'JAVA Programming', '2025-05-07 21:59:57', NULL, '2025-04-13 22:11:27'),
(4, '22598967', 'Lab 528', 'Thesis Research', '2025-05-08 19:56:44', NULL, '2025-05-08 19:54:11'),
(5, '22598967', 'Lab 524', 'Thesis Research', '2025-05-13 20:11:44', NULL, '2025-05-08 21:18:18'),
(6, '12345', '524', 'C Programming', '2025-05-13 22:58:59', NULL, '2025-05-04 19:20:12'),
(7, '22598967', 'Lab 544', 'Self Study', '2025-05-13 23:42:23', NULL, '2025-05-13 23:08:59'),
(8, '22598967', 'Lab 524', 'Thesis Research', '2025-05-13 23:56:27', NULL, '2025-05-13 23:42:46'),
(9, '12345', 'Lab 544', 'Thesis Research', '2025-05-14 00:00:34', NULL, '2025-05-13 23:04:56'),
(10, '12345', 'Lab 544', 'Class Project', '2025-05-14 00:08:58', NULL, '2025-05-14 00:08:01'),
(11, '12345', 'Lab 526', 'Self Study', '2025-05-14 00:09:47', NULL, '2025-05-14 00:09:14'),
(12, '12345', 'Lab 528', 'Other', '2025-05-14 16:39:46', NULL, '2025-05-14 00:15:52'),
(13, '12345', 'Lab 530', 'JAVA Programming', '2025-05-14 16:51:13', NULL, '2025-05-14 16:51:05'),
(14, '12345', 'Lab 528', 'JAVA Programming', '2025-05-14 20:15:53', NULL, '2025-05-14 20:15:31'),
(15, '12345', 'Lab 542', 'Digital Logic & Design', '2025-05-14 21:38:13', NULL, '2025-05-14 21:37:44'),
(16, '12345', 'Lab 530', 'JAVA Programming', '2025-05-14 22:06:41', NULL, '2025-05-14 22:06:23'),
(17, '22598967', 'Lab 544', 'Thesis Research', '2025-05-15 18:35:02', NULL, '2025-05-14 00:04:24'),
(18, '22598967', 'Lab 528', 'Embedded Systems & Iot', '2025-05-15 18:54:21', NULL, '2025-05-15 18:44:44'),
(19, '22598967', 'Lab 528', 'Digital Logic & Design', '2025-05-15 19:47:01', NULL, '2025-05-15 19:46:52'),
(20, '22598967', 'Lab 530', 'System Integration & Architechture', '2025-05-15 19:50:08', 'hello boss', '2025-05-15 19:49:57'),
(21, '12345', '524', 'Digital Logic & Design', '2025-05-15 20:03:41', NULL, '2025-05-15 20:02:32'),
(22, '22598967', '530', 'Mobile Application', '2025-05-16 06:47:16', NULL, '2025-05-15 20:03:34'),
(23, '12345', '544', 'C# Programming', '2025-05-16 06:48:20', NULL, '2025-05-15 20:03:57');

-- --------------------------------------------------------

--
-- Table structure for table `student_rewards`
--

CREATE TABLE `student_rewards` (
  `id` int(11) NOT NULL,
  `student_idno` varchar(50) NOT NULL,
  `points` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_rewards`
--

INSERT INTO `student_rewards` (`id`, `student_idno`, `points`, `last_updated`) VALUES
(13, '22598967', 0, '2025-05-15 22:47:18'),
(17, '123456', 2, '2025-05-04 11:14:41'),
(26, '12345', 1, '2025-05-15 22:48:20');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `idno` varchar(20) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `midname` varchar(50) DEFAULT NULL,
  `course` varchar(100) NOT NULL,
  `yearlevel` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `remaining_sessions` int(11) DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `idno`, `lastname`, `firstname`, `midname`, `course`, `yearlevel`, `email`, `username`, `password`, `remaining_sessions`) VALUES
(6, '22598967', 'Olandag', 'Ronel', 'Veranga', 'Bachelor of Science in Information Technology', 3, 'ronelolandag123@gmail.com', 'Ronzy21', '$2y$10$rlxrPtNu1nSkwcAE53ba6eeZ4tIlhPGK8FHSSpMvkWmDI4m9cPVQG', 6),
(7, '12345', 'garcia', 'mark', 'ragas', 'Bachelor of Science in Computer Science', 3, 'mark@gmail.com', 'marky', '$2y$10$EhEmxQ19lCjZnlXdZyfpROUxVaLPoR8.yPwlUTJnMjqTV37VvBHhW', 28),
(8, '123456', 'Garcia', 'Erik', 'Itom', 'Bachelor of Science in Civil Engineering', 3, 'erik@gmail.com', 'erik123', '$2y$10$N1KEG4rtlXZT86OISqmXmunJnA/HX4nxi1rPdxnr6UoX.TU/Qpzrm', 16);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_sitin`
--
ALTER TABLE `admin_sitin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `computer_labs`
--
ALTER TABLE `computer_labs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lab_number` (`lab_number`);

--
-- Indexes for table `computer_stations`
--
ALTER TABLE `computer_stations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lab_pc_unique` (`lab_id`,`pc_number`);

--
-- Indexes for table `lab_activity_log`
--
ALTER TABLE `lab_activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reservation` (`lab_number`,`pc_number`,`date`,`time_in`),
  ADD UNIQUE KEY `prevent_duplicate_reservations` (`lab_number`,`pc_number`,`date`,`time_in`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `resources`
--
ALTER TABLE `resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `available_to` (`available_to`),
  ADD KEY `upload_date` (`upload_date`);

--
-- Indexes for table `resource_downloads`
--
ALTER TABLE `resource_downloads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resource_id` (`resource_id`),
  ADD KEY `downloaded_by` (`downloaded_by`);

--
-- Indexes for table `reward_conversions`
--
ALTER TABLE `reward_conversions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reward_history`
--
ALTER TABLE `reward_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_idno` (`student_idno`);

--
-- Indexes for table `sitin_history`
--
ALTER TABLE `sitin_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_rewards`
--
ALTER TABLE `student_rewards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_idno_2` (`student_idno`),
  ADD KEY `student_idno` (`student_idno`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idno` (`idno`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_sitin`
--
ALTER TABLE `admin_sitin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `computer_labs`
--
ALTER TABLE `computer_labs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `computer_stations`
--
ALTER TABLE `computer_stations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=256;

--
-- AUTO_INCREMENT for table `lab_activity_log`
--
ALTER TABLE `lab_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `resources`
--
ALTER TABLE `resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `resource_downloads`
--
ALTER TABLE `resource_downloads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reward_conversions`
--
ALTER TABLE `reward_conversions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `reward_history`
--
ALTER TABLE `reward_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sitin_history`
--
ALTER TABLE `sitin_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `student_rewards`
--
ALTER TABLE `student_rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `computer_stations`
--
ALTER TABLE `computer_stations`
  ADD CONSTRAINT `computer_stations_ibfk_1` FOREIGN KEY (`lab_id`) REFERENCES `computer_labs` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`idno`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`idno`) ON DELETE CASCADE;

--
-- Constraints for table `reward_history`
--
ALTER TABLE `reward_history`
  ADD CONSTRAINT `reward_history_ibfk_1` FOREIGN KEY (`student_idno`) REFERENCES `users` (`idno`);

--
-- Constraints for table `student_rewards`
--
ALTER TABLE `student_rewards`
  ADD CONSTRAINT `student_rewards_ibfk_1` FOREIGN KEY (`student_idno`) REFERENCES `users` (`idno`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
