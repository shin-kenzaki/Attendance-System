-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 13, 2025 at 09:32 AM
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
-- Database: `attendancesystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendances`
--

CREATE TABLE `attendances` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `time_in` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendances`
--

INSERT INTO `attendances` (`id`, `user_id`, `subject_id`, `time_in`) VALUES
(44, 2, 21, '2025-03-12 11:51:44'),
(45, 7, 21, '2025-03-12 12:13:46'),
(46, 2, 21, '2025-03-13 02:03:12');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `format` varchar(10) NOT NULL,
  `generated_by` varchar(100) NOT NULL,
  `generated_at` datetime DEFAULT current_timestamp(),
  `file_path` varchar(255) NOT NULL,
  `parameters` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `name`, `type`, `format`, `generated_by`, `generated_at`, `file_path`, `parameters`, `status`, `created_at`) VALUES
(1, 'Daily Attendance Report', 'attendance', 'pdf', 'System Admin', '2025-03-13 00:54:20', 'reports/attendance_20250312.pdf', '{\"date\": \"2025-03-12\"}', 'completed', '2025-03-13 07:51:10'),
(2, 'Monthly Student Report', 'student', 'excel', 'System Admin', '2025-03-13 00:54:20', 'reports/student_202503.xlsx', '{\"month\": \"March\", \"year\": \"2025\"}', 'completed', '2025-03-13 07:51:10');

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `room` varchar(225) DEFAULT NULL,
  `day` varchar(225) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`id`, `subject_id`, `room`, `day`, `start_time`, `end_time`) VALUES
(3, 21, '406', 'Monday', '08:45:00', '11:45:00'),
(4, 38, '406', 'Monday', '13:00:00', '15:00:00'),
(5, 35, '406', 'Monday', '15:30:00', '17:30:00'),
(6, 13, '405', 'Monday', '07:00:00', '09:00:00'),
(7, 13, '405', 'Tuesday', '07:00:00', '09:00:00'),
(8, 13, '405', 'Wednesday', '07:00:00', '09:00:00'),
(9, 13, '405', 'Thursday', '07:00:00', '09:00:00'),
(10, 13, '405', 'Friday', '07:00:00', '09:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `code` varchar(225) DEFAULT NULL,
  `name` varchar(225) DEFAULT NULL,
  `faculty_id` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `joincode` varchar(225) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `code`, `name`, `faculty_id`, `status`, `joincode`) VALUES
(7, 'GED 202', 'Mathematics in Modern World', NULL, 1, '47LQU7'),
(8, 'CSC 221', 'Object-Oriented Programming', NULL, 1, '577U4M'),
(9, 'CSC 222', 'Discrete Structures', NULL, 1, 'ZPZKHZ'),
(10, 'PED 201', 'Physical Education3', NULL, 1, 'W9LJN5'),
(11, 'CSC 223', 'Computer Systems and Organization with Assembly Language', NULL, 1, 'EB66DU'),
(12, 'CSC 211', 'Data Structures and Algorithm', NULL, 1, 'HSNW2K'),
(13, 'ACC 211', 'Management Science(Quantitative Methods)', NULL, 1, 'T4ZC7V'),
(14, 'CSC 212', 'Information Management', NULL, 1, 'GXBPTC'),
(15, 'CSC 225', 'Technology Entrepreneurship', NULL, 1, '95DXQY'),
(16, 'GED 208', 'People and the Earth\'s Ecosystem', NULL, 1, 'V87EAN'),
(17, 'PED 202', 'Physical Education 4', NULL, 1, 'S9CQMK'),
(18, 'CSC 224', 'Design and Analysis of Algorithms', NULL, 1, 'TWT4UW'),
(19, 'GED 207', 'Great Books', NULL, 1, '2MWC3F'),
(20, 'THS 321', 'Thesis Writing 1', NULL, 1, 'LTRVJS'),
(21, 'CSC 323', 'Mobile Device Application Principles', 3, 1, 'TCKUPX'),
(22, 'CSC 321', 'Fundamentals of Human Computer Interaction', NULL, 1, 'B965EQ'),
(23, 'CSC 311', 'Applications Development and Emerging Technologies', NULL, 1, 'HNHSM3'),
(24, 'CSC 322', 'Automata Theory and Formal Languages', NULL, 1, 'PED5U9'),
(25, 'CSC 324', 'Structures of Programming Languages', NULL, 1, '67Q58Z'),
(26, 'CSC 331', 'CS Elective 1 (Track Based)', NULL, 1, '8S8V7J'),
(27, 'CSC 325', 'Operating Systems', NULL, 1, 'K64DW2'),
(28, 'GED 205', 'Kontekstuwalisadong Komunikasyon sa Filipino (KOMFIL)', NULL, 1, 'SA64GM'),
(29, 'GED 206', 'Sosyedad at Literatura/Panitikang Panlipunan (SOSLIT)', NULL, 1, '2D483N'),
(30, 'CSC 332', 'CS Elective 2 (Track Based)', NULL, 1, 'BVW4AZ'),
(31, 'CSC 327', 'Software Engineering', NULL, 1, 'S7EDRW'),
(32, 'CSC 328', 'Information Assurance and Security', NULL, 1, 'DZ2XTE'),
(33, 'CSC 326', 'Computer Networks and Data Communications', NULL, 1, 'CDUTC9'),
(34, 'CSC 329', 'Cyber Security and Internet of Things', NULL, 1, 'CU96L8'),
(35, 'CSC 422', 'Mobile Application and Development', 3, 1, 'PUAYMG'),
(36, 'CSC 431', 'CS Elective 3 (Track Based)', NULL, 1, 'HGJM5U'),
(37, 'GED 104', 'Science, Technology and Society', NULL, 1, 'MW84PF'),
(38, 'CSC 421', 'Social Issues and Professional Practice', 3, 1, 'M4PNSX'),
(39, 'CSC 423', 'Operational Systems and Computer Security', NULL, 1, '85BQEF'),
(40, 'PED 101', 'Physical Education 1', NULL, 1, 'XTPW3G');

-- --------------------------------------------------------

--
-- Table structure for table `updates`
--

CREATE TABLE `updates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(225) DEFAULT NULL,
  `message` varchar(225) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `updates`
--

INSERT INTO `updates` (`id`, `user_id`, `title`, `message`, `timestamp`) VALUES
(1, 1, 'Login', 'User logged in successfully', '2025-03-08 12:06:42'),
(2, 1, 'Profile Updated', 'User profile information was updated', '2025-03-08 12:13:37'),
(3, 1, 'Profile Updated', 'User profile information was updated', '2025-03-08 12:17:05'),
(4, 1, 'Profile Updated', 'User profile information was updated', '2025-03-08 12:17:23'),
(5, 1, 'Profile Picture Reset', 'User profile picture was reset to default', '2025-03-08 12:19:31'),
(6, 1, 'Profile Updated', 'User profile information was updated', '2025-03-08 12:19:46'),
(7, 1, 'Profile Updated', 'User profile information was updated', '2025-03-08 12:22:56'),
(8, 1, 'Profile Picture Reset', 'User profile picture was reset to default', '2025-03-08 12:28:38'),
(9, 1, 'Profile Updated', 'User profile information was updated', '2025-03-08 12:31:06'),
(10, 1, 'Profile Picture Reset', 'User profile picture was reset to default', '2025-03-08 12:31:11'),
(11, 1, 'Login', 'User logged in successfully', '2025-03-08 12:34:12'),
(12, 1, 'Profile Updated', 'User profile information was updated', '2025-03-08 12:34:33'),
(13, 1, 'Profile Updated', 'User profile information was updated', '2025-03-08 12:36:50'),
(14, 1, 'Profile Picture Updated', 'User profile picture was updated', '2025-03-08 12:36:54'),
(15, 1, 'Profile Picture Reset', 'User profile picture was reset to default', '2025-03-08 12:38:30'),
(16, 1, 'Profile Updated', 'User profile information was updated', '2025-03-08 12:38:41'),
(17, 1, 'Profile Picture Updated', 'User profile picture was updated', '2025-03-08 13:11:02'),
(18, 1, 'Profile Picture Reset', 'User profile picture was reset to default', '2025-03-08 13:12:05'),
(19, 1, 'Profile Picture Reset', 'User profile picture was reset to default', '2025-03-08 13:13:21'),
(20, 1, 'Profile Picture Updated', 'User profile picture was updated', '2025-03-08 13:13:25'),
(21, 1, 'Profile Picture Reset', 'User profile picture was reset to default', '2025-03-08 13:13:46'),
(22, 1, 'Profile Picture Reset', 'User profile picture was reset to default', '2025-03-08 13:14:28'),
(23, 1, 'Profile Picture Reset', 'User profile picture was reset to default', '2025-03-08 13:14:30'),
(24, 1, 'Profile Picture Updated', 'User profile picture was updated', '2025-03-08 13:18:12'),
(25, 1, 'Login', 'User logged in successfully', '2025-03-08 13:34:26'),
(26, 1, 'Login', 'User logged in successfully', '2025-03-08 13:46:42'),
(27, 1, 'Login', 'User logged in successfully', '2025-03-08 13:47:25'),
(28, 1, 'Login', 'User logged in successfully', '2025-03-08 13:49:26'),
(29, 2, 'New User Added', 'User Shin Kenzaki has been added with auto-generated password.', '2025-03-08 08:51:11'),
(30, 2, 'Login', 'User logged in successfully', '2025-03-08 15:52:08'),
(31, 1, 'Login', 'User logged in successfully', '2025-03-08 15:54:07'),
(32, 1, 'Password Reset', 'Password has been reset for user ID: 1', '2025-03-08 08:56:24'),
(33, 2, 'Password Reset', 'Password has been reset for user ID: 2', '2025-03-08 08:56:57'),
(34, 2, 'Login', 'User logged in successfully', '2025-03-08 15:57:22'),
(35, 1, 'Login', 'User logged in successfully', '2025-03-08 15:58:20'),
(36, 1, 'New Subject Added', 'Subject CSC424: Application Development has been added with join code: ', '2025-03-08 09:05:35'),
(37, 1, 'New Subject Added', 'Subject CSC424: Application Development has been added with join code: 99UJCP', '2025-03-08 09:07:02'),
(38, 1, 'Join Code Changed', 'Join code for subject CSC424: Application Development has been changed to DYCEHV', '2025-03-08 09:10:39'),
(39, 1, 'User Updated', 'User Kenneth Laurence  Bonaagua (ID: 1) information has been updated', '2025-03-08 09:13:24'),
(40, 1, 'Login', 'User logged in successfully', '2025-03-08 16:13:57'),
(41, 2, 'Password Reset', 'Password has been reset for user ID: 2', '2025-03-08 09:23:09'),
(42, 3, 'New User Added', 'User Vincent Dais has been added with auto-generated password.', '2025-03-08 09:33:40'),
(43, 1, 'Join Code Changed', 'Join code for subject CSC424: Application Development has been changed to LZ233E', '2025-03-08 09:33:58'),
(44, 1, 'Subject Deleted', 'Subject CSC424: Application Development has been deleted', '2025-03-08 09:39:56'),
(45, 1, 'Subject Deleted', 'Subject CSC424: Application Development has been deleted', '2025-03-08 09:40:00'),
(46, 3, 'User Updated', 'User Vincent Dais (ID: 3) information has been updated', '2025-03-08 09:40:06'),
(47, 3, 'New Subject Added', 'Subject CSC424: Application Development has been added with join code: ', '2025-03-08 09:40:50'),
(48, 1, 'Subject Deleted', 'Subject CSC424: Application Development has been deleted', '2025-03-08 09:41:06'),
(49, 3, 'New Subject Added', 'Subject CSC424: Application Development has been added with join code: ', '2025-03-08 09:41:23'),
(50, 1, 'Subject Deleted', 'Subject CSC424: Application Development has been deleted', '2025-03-08 09:43:48'),
(51, 1, 'New Subject Added', 'Subject CSC424: Application Development has been added with join code: 36MXXR', '2025-03-08 09:44:05'),
(52, 1, 'Subject Updated', 'Subject CSC424: Application Development (ID: 5) information has been updated', '2025-03-08 09:46:05'),
(53, 1, 'Login', 'User logged in successfully', '2025-03-09 03:57:58'),
(54, 1, 'New Subject Added', 'Subject CSC323: Structures of Programming Languages has been added with join code: 8DR5DR', '2025-03-08 21:07:05'),
(55, 1, 'Login', 'User logged in successfully', '2025-03-09 04:55:02'),
(56, 1, 'Login', 'User logged in successfully', '2025-03-09 06:21:15'),
(57, 1, 'Login', 'User logged in successfully', '2025-03-09 07:35:41'),
(58, 2, 'Password Reset', 'Password has been reset for user ID: 2', '2025-03-09 00:36:22'),
(59, 2, 'Login', 'User logged in successfully', '2025-03-09 07:36:31'),
(60, 1, 'Login', 'User logged in successfully', '2025-03-09 08:00:08'),
(61, 1, 'Login', 'User logged in successfully', '2025-03-09 08:12:41'),
(62, 1, 'Login', 'User logged in successfully', '2025-03-09 08:23:43'),
(63, 2, 'Password Reset', 'Password has been reset for user ID: 2', '2025-03-09 01:32:46'),
(64, 2, 'Login', 'User logged in successfully', '2025-03-09 08:32:51'),
(65, 2, 'Password Reset', 'Password has been reset for user ID: 2', '2025-03-09 04:26:18'),
(66, 2, 'Login', 'User logged in successfully', '2025-03-09 11:26:25'),
(67, 2, 'Login', 'User logged in successfully', '2025-03-09 11:27:16'),
(68, 2, 'Login', 'User logged in successfully', '2025-03-09 11:27:38'),
(69, 1, 'Login', 'User logged in successfully', '2025-03-09 11:32:02'),
(70, 1, 'Login', 'User logged in successfully', '2025-03-09 11:32:55'),
(71, 2, 'Password Reset', 'Password has been reset for user ID: 2', '2025-03-09 04:42:22'),
(72, 2, 'Login', 'User logged in successfully', '2025-03-09 11:42:27'),
(73, 2, 'Password Reset', 'Password has been reset for user ID: 2', '2025-03-09 04:48:26'),
(74, 2, 'Login', 'User logged in successfully', '2025-03-09 11:48:31'),
(75, 2, 'Login', 'User logged in successfully', '2025-03-09 11:57:40'),
(76, 3, 'Password Reset', 'Password has been reset for user ID: 3', '2025-03-09 04:58:04'),
(77, 3, 'Login', 'User logged in successfully', '2025-03-09 11:58:10'),
(78, 2, 'Password Reset', 'Password has been reset for user ID: 2', '2025-03-09 05:04:54'),
(79, 2, 'Login', 'User logged in successfully', '2025-03-09 12:05:00'),
(80, 3, 'Password Reset', 'Password has been reset for user ID: 3', '2025-03-09 05:05:17'),
(81, 3, 'Login', 'User logged in successfully', '2025-03-09 12:05:22'),
(82, 3, 'Password Reset', 'Password has been reset for user ID: 3', '2025-03-09 05:11:28'),
(83, 3, 'Login', 'User logged in successfully', '2025-03-09 12:11:33'),
(84, 2, 'Password Reset', 'Password has been reset for user ID: 2', '2025-03-09 05:14:21'),
(85, 2, 'Login', 'User logged in successfully', '2025-03-09 12:15:34'),
(86, 1, 'Profile Picture Reset', 'User profile picture was reset to default', '2025-03-09 12:24:34'),
(87, 1, 'Profile Picture Reset', 'User profile picture was reset to default', '2025-03-09 12:24:56'),
(88, 1, 'Profile Updated', 'User profile information was updated', '2025-03-09 12:25:03'),
(89, 1, 'Profile Picture Updated', 'User profile picture was updated', '2025-03-09 12:31:15'),
(90, 1, 'Profile Picture Reset', 'User profile picture was reset to default', '2025-03-09 12:32:40'),
(91, 1, 'Profile Picture Updated', 'User profile picture was updated', '2025-03-09 12:32:45'),
(92, 1, 'Profile Picture Updated', 'User profile picture was updated', '2025-03-09 12:32:56'),
(93, 1, 'Profile Picture Reset', 'User profile picture was reset to default', '2025-03-09 12:35:47'),
(94, 1, 'Profile Picture Updated', 'User profile picture was updated', '2025-03-09 12:35:51'),
(95, 2, 'Password Reset', 'Password has been reset for user ID: 2', '2025-03-09 05:36:33'),
(96, 2, 'Login', 'User logged in successfully', '2025-03-09 12:36:38'),
(97, 3, 'Password Reset', 'Password has been reset for user ID: 3', '2025-03-09 05:50:27'),
(98, 3, 'Login', 'User logged in successfully', '2025-03-09 12:50:32'),
(99, 1, 'Login', 'User logged in successfully', '2025-03-09 12:56:42'),
(100, 2, 'Password Reset', 'Password has been reset for user ID: 2', '2025-03-09 05:56:59'),
(101, 2, 'Login', 'User logged in successfully', '2025-03-09 12:57:14'),
(102, 2, 'Login', 'User logged in successfully', '2025-03-09 12:58:27'),
(103, 2, 'Password Reset', 'Password has been reset for user ID: 2', '2025-03-09 05:58:46'),
(104, 2, 'Login', 'User logged in successfully', '2025-03-09 12:58:51'),
(105, 1, 'Login', 'User logged in successfully', '2025-03-09 13:04:06'),
(106, 1, 'Login', 'User logged in successfully', '2025-03-09 13:04:47'),
(107, 2, 'Password Reset', 'Password has been reset for user ID: 2', '2025-03-09 06:05:08'),
(108, 2, 'Login', 'User logged in successfully', '2025-03-09 13:05:12'),
(109, 3, 'Password Reset', 'Password has been reset for user ID: 3', '2025-03-09 06:20:26'),
(110, 3, 'Login', 'User logged in successfully', '2025-03-09 13:20:31'),
(111, 2, 'Password Reset', 'Password has been reset for user ID: 2', '2025-03-09 06:27:03'),
(112, 2, 'Login', 'User logged in successfully', '2025-03-09 13:27:09'),
(113, 1, 'Login', 'User logged in successfully', '2025-03-09 14:33:34'),
(114, 3, 'Password Reset', 'Password has been reset for user ID: 3', '2025-03-09 08:07:24'),
(115, 3, 'Login', 'User logged in successfully', '2025-03-09 15:07:29'),
(116, 3, 'Password Reset', 'Password has been reset for user ID: 3', '2025-03-09 08:43:55'),
(117, 1, 'Login', 'User logged in successfully', '2025-03-10 02:18:42'),
(118, 2, 'Password Reset', 'Password has been reset for user ID: 2', '2025-03-09 19:26:58'),
(119, 2, 'Login', 'User logged in successfully', '2025-03-10 02:27:09'),
(120, 3, 'Password Reset', 'Password has been reset for user ID: 3', '2025-03-09 20:15:30'),
(121, 3, 'Login', 'User logged in successfully', '2025-03-10 03:15:41'),
(122, 2, 'Login', 'User logged in successfully', '2025-03-10 03:19:14'),
(123, 3, 'Login', 'User logged in successfully', '2025-03-10 03:19:51'),
(124, 2, 'Login', 'User logged in successfully', '2025-03-10 03:26:37'),
(125, 3, 'Login', 'User logged in successfully', '2025-03-10 03:29:39'),
(126, 3, 'Login', 'User logged in successfully', '2025-03-10 04:13:53'),
(127, 2, 'Login', 'User logged in successfully', '2025-03-10 04:14:39'),
(128, 1, 'Login', 'User logged in successfully', '2025-03-10 04:16:00'),
(129, 3, 'Login', 'User logged in successfully', '2025-03-10 04:16:17'),
(130, 2, 'Login', 'User logged in successfully', '2025-03-10 04:19:38'),
(131, 3, 'Login', 'User logged in successfully', '2025-03-10 04:20:03'),
(132, 3, 'Login', 'User logged in successfully', '2025-03-10 05:17:04'),
(133, 3, 'Login', 'User logged in successfully', '2025-03-10 05:43:28'),
(134, 3, 'Login', 'User logged in successfully', '2025-03-10 06:31:39'),
(135, 3, 'Login', 'User logged in successfully', '2025-03-10 07:01:06'),
(136, 1, 'Login', 'User logged in successfully', '2025-03-10 07:01:58'),
(137, 3, 'Login', 'User logged in successfully', '2025-03-10 07:02:25'),
(138, 1, 'Login', 'User logged in successfully', '2025-03-10 07:12:55'),
(139, 1, 'Login', 'User logged in successfully', '2025-03-10 07:22:47'),
(140, 1, 'Login', 'User logged in successfully', '2025-03-10 07:24:21'),
(141, 1, 'Login', 'User logged in successfully', '2025-03-10 07:32:41'),
(142, 1, 'Login', 'User logged in successfully', '2025-03-10 08:22:02'),
(143, 1, 'Login', 'User logged in successfully', '2025-03-10 08:44:14'),
(144, 1, 'Subject Updated', 'Subject CSC323: Structures of Programming Languages (ID: 6) information has been updated', '2025-03-10 04:06:33'),
(145, 1, 'Subject Deleted', 'Subject CSC323: Structures of Programming Languages has been deleted', '2025-03-10 04:19:44'),
(146, 1, 'Subject Deleted', 'Subject CSC424: Application Development has been deleted', '2025-03-10 04:19:47'),
(147, 1, 'New Subject Added', 'Subject GED 202: Mathematics in Modern World has been added with join code: 47LQU7', '2025-03-10 04:21:36'),
(148, 1, 'New Subject Added', 'Subject CSC 221: Object-Oriented Programming has been added with join code: 577U4M', '2025-03-10 04:23:35'),
(149, 1, 'New Subject Added', 'Subject CSC 222: Discrete Structures has been added with join code: ZPZKHZ', '2025-03-10 04:23:54'),
(150, 1, 'New Subject Added', 'Subject PED 201: Physical Education3 has been added with join code: W9LJN5', '2025-03-10 04:24:21'),
(151, 1, 'New Subject Added', 'Subject CSC 223: Computer Systems and Organization with Assembly Language has been added with join code: EB66DU', '2025-03-10 04:24:54'),
(152, 1, 'New Subject Added', 'Subject CSC 211: Data Structures and Algorithm has been added with join code: HSNW2K', '2025-03-10 04:25:16'),
(153, 1, 'New Subject Added', 'Subject ACC 211: Management Science(Quantitative Methods) has been added with join code: T4ZC7V', '2025-03-10 04:26:29'),
(154, 1, 'New Subject Added', 'Subject CSC 212: Information Management has been added with join code: GXBPTC', '2025-03-10 04:26:50'),
(155, 1, 'New Subject Added', 'Subject CSC 225: Technology Entrepreneurship has been added with join code: 95DXQY', '2025-03-10 04:34:12'),
(156, 1, 'New Subject Added', 'Subject GED 208: People and the Earth\'s Ecosystem has been added with join code: V87EAN', '2025-03-10 04:36:13'),
(157, 1, 'New Subject Added', 'Subject PED 202: Physical Education 4 has been added with join code: S9CQMK', '2025-03-10 04:37:25'),
(158, 1, 'New Subject Added', 'Subject CSC 224: Design and Analysis of Algorithms has been added with join code: TWT4UW', '2025-03-10 04:37:25'),
(159, 1, 'New Subject Added', 'Subject GED 207: Great Books has been added with join code: 2MWC3F', '2025-03-10 04:37:25'),
(160, 1, 'New Subject Added', 'Subject THS 321: Thesis Writing 1 has been added with join code: LTRVJS', '2025-03-10 04:43:12'),
(161, 1, 'New Subject Added', 'Subject CSC 323: Mobile Device Application Principles has been added with join code: TCKUPX', '2025-03-10 04:43:12'),
(162, 1, 'New Subject Added', 'Subject CSC 321: Fundamentals of Human Computer Interaction has been added with join code: B965EQ', '2025-03-10 04:43:12'),
(163, 1, 'New Subject Added', 'Subject CSC 311: Applications Development and Emerging Technologies has been added with join code: HNHSM3', '2025-03-10 04:43:12'),
(164, 1, 'New Subject Added', 'Subject CSC 322: Automata Theory and Formal Languages has been added with join code: PED5U9', '2025-03-10 04:43:12'),
(165, 1, 'New Subject Added', 'Subject CSC 324: Structures of Programming Languages has been added with join code: 67Q58Z', '2025-03-10 04:43:12'),
(166, 1, 'New Subject Added', 'Subject CSC 331: CS Elective 1 (Track Based) has been added with join code: 8S8V7J', '2025-03-10 04:43:12'),
(167, 1, 'New Subject Added', 'Subject CSC 325: Operating Systems has been added with join code: K64DW2', '2025-03-10 04:43:12'),
(168, 1, 'New Subject Added', 'Subject GED 205: Kontekstuwalisadong Komunikasyon sa Filipino (KOMFIL) has been added with join code: SA64GM', '2025-03-10 04:43:12'),
(169, 1, 'New Subject Added', 'Subject GED 206: Sosyedad at Literatura/Panitikang Panlipunan (SOSLIT) has been added with join code: 2D483N', '2025-03-10 04:43:12'),
(170, 1, 'New Subject Added', 'Subject CSC 332: CS Elective 2 (Track Based) has been added with join code: BVW4AZ', '2025-03-10 04:51:39'),
(171, 1, 'New Subject Added', 'Subject CSC 327: Software Engineering has been added with join code: S7EDRW', '2025-03-10 04:51:39'),
(172, 1, 'New Subject Added', 'Subject CSC 328: Information Assurance and Security has been added with join code: DZ2XTE', '2025-03-10 04:51:39'),
(173, 1, 'New Subject Added', 'Subject CSC 326: Computer Networks and Data Communications has been added with join code: CDUTC9', '2025-03-10 04:51:39'),
(174, 1, 'New Subject Added', 'Subject CSC 329: Cyber Security and Internet of Things has been added with join code: CU96L8', '2025-03-10 05:10:12'),
(175, 1, 'New Subject Added', 'Subject CSC 422: Mobile Application and Development has been added with join code: PUAYMG', '2025-03-10 05:10:12'),
(176, 1, 'New Subject Added', 'Subject CSC 431: CS Elective 3 (Track Based) has been added with join code: HGJM5U', '2025-03-10 05:14:16'),
(177, 1, 'New Subject Added', 'Subject GED 104: Science, Technology and Society has been added with join code: MW84PF', '2025-03-10 05:14:16'),
(178, 1, 'New Subject Added', 'Subject CSC 421: Social Issues and Professional Practice has been added with join code: M4PNSX', '2025-03-10 05:14:16'),
(179, 1, 'New Subject Added', 'Subject CSC 423: Operational Systems and Computer Security has been added with join code: 85BQEF', '2025-03-10 05:14:16'),
(180, 1, 'New Subject Added', 'Subject PED 101: Physical Education 1 has been added with join code: XTPW3G', '2025-03-10 05:14:37'),
(181, 1, 'Subject Updated', 'Subject CSC 421: Social Issues and Professional Practice (ID: 38) information has been updated', '2025-03-10 05:15:08'),
(182, 1, 'Subject Updated', 'Subject CSC 323: Mobile Device Application Principles (ID: 21) information has been updated', '2025-03-10 05:15:38'),
(183, 1, 'Subject Updated', 'Subject CSC 422: Mobile Application and Development (ID: 35) information has been updated', '2025-03-10 05:15:48'),
(184, 3, 'User Updated', 'User Vincent Dais (ID: 3) information has been updated', '2025-03-10 05:18:12'),
(185, 2, 'User Updated', 'User Kenneth Laurence Bonaagua (ID: 2) information has been updated', '2025-03-10 05:18:52'),
(186, 3, 'User Updated', 'User Vincent Dais (ID: 3) information has been updated', '2025-03-10 06:01:50'),
(187, 2, 'Login', 'User logged in successfully', '2025-03-10 13:25:35'),
(188, 2, 'Login', 'User logged in successfully', '2025-03-10 13:33:13'),
(189, 2, 'Login', 'User logged in successfully', '2025-03-10 14:10:19'),
(190, 1, 'Login', 'User logged in successfully', '2025-03-10 14:10:39'),
(191, 2, 'Login', 'User logged in successfully', '2025-03-10 14:12:53'),
(192, 2, 'Subject Enrollment', 'Enrolled in subject with code: TCKUPX', '2025-03-10 14:13:34'),
(193, 1, 'Login', 'User logged in successfully', '2025-03-10 14:14:36'),
(194, 3, 'Login', 'User logged in successfully', '2025-03-10 14:21:57'),
(195, 2, 'Login', 'User logged in successfully', '2025-03-11 01:55:31'),
(196, 1, 'Login', 'User logged in successfully', '2025-03-11 01:56:17'),
(197, 2, 'Login', 'User logged in successfully', '2025-03-11 02:07:07'),
(198, 3, 'Login', 'User logged in successfully', '2025-03-11 02:08:51'),
(199, 4, 'New User Added', 'User Jenepir Jabillo has been added with auto-generated password.', '2025-03-10 19:44:27'),
(200, 1, 'User Deleted', 'User Jenepir Jabillo has been deleted', '2025-03-10 20:05:34'),
(201, 5, 'New User Added', 'User Jenepir Jabillo has been added', '2025-03-10 20:07:10'),
(202, 6, 'New User Added', 'User Cayce Evangelista has been added', '2025-03-10 20:07:10'),
(203, 7, 'New User Added', 'User Carlos Miguel Autor has been added', '2025-03-10 20:07:10'),
(204, 2, 'Login', 'User logged in successfully', '2025-03-11 03:09:11'),
(205, 2, 'Subject Enrollment', 'Enrolled in subject with code: TCKUPX', '2025-03-11 03:10:58'),
(206, 2, 'Subject Enrollment', 'Enrolled in subject with code: M4PNSX', '2025-03-11 03:11:05'),
(207, 2, 'Subject Enrollment', 'Enrolled in subject with code: PUAYMG', '2025-03-11 03:11:11'),
(208, 3, 'Login', 'User logged in successfully', '2025-03-11 03:29:08'),
(209, 3, 'Login', 'User logged in successfully', '2025-03-11 04:28:44'),
(210, 3, 'Login', 'User logged in successfully', '2025-03-11 04:32:41'),
(211, 1, 'Login', 'User logged in successfully', '2025-03-11 04:39:11'),
(212, 3, 'Login', 'User logged in successfully', '2025-03-11 04:39:43'),
(213, 3, 'Login', 'User logged in successfully', '2025-03-11 04:42:01'),
(214, 1, 'Login', 'User logged in successfully', '2025-03-11 04:48:26'),
(215, 7, 'Password Reset', 'Password has been reset for user ID: 7', '2025-03-10 21:49:07'),
(216, 6, 'Password Reset', 'Password has been reset for user ID: 6', '2025-03-10 21:49:15'),
(217, 5, 'Password Reset', 'Password has been reset for user ID: 5', '2025-03-10 21:49:22'),
(218, 7, 'Login', 'User logged in successfully', '2025-03-11 04:49:59'),
(219, 6, 'Login', 'User logged in successfully', '2025-03-11 04:50:20'),
(220, 1, 'Login', 'User logged in successfully', '2025-03-11 04:50:30'),
(221, 5, 'Login', 'User logged in successfully', '2025-03-11 04:50:55'),
(222, 3, 'Login', 'User logged in successfully', '2025-03-11 04:51:19'),
(223, 5, 'Subject Enrollment', 'Enrolled in subject with code: TCKUPX', '2025-03-11 05:34:31'),
(224, 5, 'Subject Enrollment', 'Enrolled in subject with code: PUAYMG', '2025-03-11 05:34:38'),
(225, 5, 'Subject Enrollment', 'Enrolled in subject with code: M4PNSX', '2025-03-11 05:34:44'),
(226, 7, 'Login', 'User logged in successfully', '2025-03-11 05:35:04'),
(227, 7, 'Subject Enrollment', 'Enrolled in subject with code: TCKUPX', '2025-03-11 05:35:13'),
(228, 7, 'Subject Enrollment', 'Enrolled in subject with code: PUAYMG', '2025-03-11 05:35:18'),
(229, 7, 'Subject Enrollment', 'Enrolled in subject with code: M4PNSX', '2025-03-11 05:35:24'),
(230, 6, 'Login', 'User logged in successfully', '2025-03-11 05:35:37'),
(231, 6, 'Subject Enrollment', 'Enrolled in subject with code: TCKUPX', '2025-03-11 05:35:48'),
(232, 6, 'Subject Enrollment', 'Enrolled in subject with code: PUAYMG', '2025-03-11 05:35:53'),
(233, 6, 'Subject Enrollment', 'Enrolled in subject with code: M4PNSX', '2025-03-11 05:36:22'),
(234, 3, 'Login', 'User logged in successfully', '2025-03-11 05:43:19'),
(235, 1, 'Login', 'User logged in successfully', '2025-03-11 05:44:03'),
(236, 2, 'Login', 'User logged in successfully', '2025-03-11 09:22:33'),
(237, 1, 'Login', 'User logged in successfully', '2025-03-11 09:23:55'),
(238, 3, 'Login', 'User logged in successfully', '2025-03-11 09:24:17'),
(239, 2, 'Login', 'User logged in successfully', '2025-03-11 09:25:52'),
(240, 1, 'Login', 'User logged in successfully', '2025-03-11 09:44:22'),
(241, 2, 'Login', 'User logged in successfully', '2025-03-11 09:50:55'),
(242, 3, 'Login', 'User logged in successfully', '2025-03-11 09:51:38'),
(243, 1, 'Login', 'User logged in successfully', '2025-03-11 11:24:20'),
(244, 2, 'Login', 'User logged in successfully', '2025-03-11 11:24:41'),
(245, 3, 'Login', 'User logged in successfully', '2025-03-11 11:25:04'),
(246, 3, 'Attendance Recorded', 'Attendance recorded for student ID: 2 in subject ID: 21', '2025-03-11 11:32:37'),
(247, 3, 'Attendance Recorded', 'Attendance recorded for student ID: 2 in subject ID: 21', '2025-03-11 11:34:30'),
(248, 2, 'Login', 'User logged in successfully', '2025-03-11 11:40:27'),
(249, 3, 'Attendance Recorded', 'Attendance recorded for student ID: 2 in subject ID: 21', '2025-03-11 11:41:20'),
(250, 2, 'Login', 'User logged in successfully', '2025-03-11 11:51:14'),
(251, 2, 'Self-Attendance', 'Student self-recorded attendance for subject ID: 21', '2025-03-11 11:56:36'),
(252, 1, 'Login', 'User logged in successfully', '2025-03-11 11:58:31'),
(253, 3, 'Login', 'User logged in successfully', '2025-03-11 12:06:39'),
(254, 1, 'Login', 'User logged in successfully', '2025-03-11 12:12:32'),
(255, 2, 'Login', 'User logged in successfully', '2025-03-11 12:13:23'),
(256, 1, 'Login', 'User logged in successfully', '2025-03-11 12:28:44'),
(257, 2, 'Login', 'User logged in successfully', '2025-03-11 12:33:55'),
(258, 1, 'Login', 'User logged in successfully', '2025-03-11 12:40:42'),
(259, 2, 'Login', 'User logged in successfully', '2025-03-11 12:44:08'),
(260, 2, 'Login', 'User logged in successfully', '2025-03-11 12:47:33'),
(261, 3, 'Login', 'User logged in successfully', '2025-03-11 12:58:24'),
(262, 2, 'Login', 'User logged in successfully', '2025-03-11 13:04:57'),
(263, 1, 'Login', 'User logged in successfully', '2025-03-11 13:22:48'),
(264, 2, 'Login', 'User logged in successfully', '2025-03-11 13:23:08'),
(265, 2, 'Login', 'User logged in successfully', '2025-03-11 13:29:29'),
(266, 2, 'Login', 'User logged in successfully', '2025-03-11 13:47:43'),
(267, 1, 'Login', 'User logged in successfully', '2025-03-11 14:11:01'),
(268, 3, 'Login', 'User logged in successfully', '2025-03-11 14:11:47'),
(269, 2, 'Login', 'User logged in successfully', '2025-03-11 14:23:26'),
(270, 3, 'Login', 'User logged in successfully', '2025-03-11 14:27:52'),
(271, 1, 'Login', 'User logged in successfully', '2025-03-11 14:33:05'),
(272, 1, 'Login', 'User logged in successfully', '2025-03-12 00:28:35'),
(273, 2, 'Login', 'User logged in successfully', '2025-03-12 00:29:48'),
(274, 3, 'Login', 'User logged in successfully', '2025-03-12 00:30:08'),
(275, 2, 'Login', 'User logged in successfully', '2025-03-12 01:55:01'),
(276, 1, 'Login', 'User logged in successfully', '2025-03-12 02:09:22'),
(277, 3, 'Attendance Recorded', 'Attendance recorded for student ID: 2 in subject ID: 21', '2025-03-12 02:15:27'),
(278, 2, 'Login', 'User logged in successfully', '2025-03-12 02:23:26'),
(279, 2, 'Attendance Recorded', 'Attendance recorded for user ID: 2 in subject ID: 21', '2025-03-12 02:52:41'),
(280, 2, 'Attendance Recorded', 'Attendance recorded for user ID: 2 in subject ID: 21', '2025-03-12 02:53:02'),
(281, 1, 'Login', 'User logged in successfully', '2025-03-12 03:07:54'),
(282, 2, 'Login', 'User logged in successfully', '2025-03-12 03:12:38'),
(283, 2, 'Attendance Recorded', 'Attendance recorded for user ID: 2 in subject ID: 21', '2025-03-12 03:21:46'),
(284, 2, 'Attendance Recorded', 'Attendance recorded for user ID: 2 in subject ID: 21', '2025-03-12 03:25:05'),
(285, 2, 'Attendance Recorded', 'Attendance recorded for user ID: 2 in subject ID: 21', '2025-03-12 03:26:02'),
(286, 2, 'Attendance Recorded', 'Attendance recorded for user ID: 2 in subject ID: 21', '2025-03-12 03:28:04'),
(287, 2, 'Attendance Recorded', 'Attendance recorded for user ID: 2 in subject ID: 21', '2025-03-12 03:31:30'),
(288, 2, 'Attendance Recorded', 'Attendance recorded for user ID: 2 in subject ID: 21', '2025-03-12 03:32:13'),
(289, 2, 'Attendance Recorded', 'Attendance recorded for user ID: 2 in subject ID: 21', '2025-03-12 03:32:54'),
(290, 2, 'Attendance Recorded', 'Attendance recorded for user ID: 2 in subject ID: 21', '2025-03-12 03:37:21'),
(291, 2, 'Attendance Recorded', 'Attendance recorded for user ID: 2 in subject ID: 21', '2025-03-12 03:40:14'),
(292, 2, 'Attendance Recorded', 'Attendance recorded for user ID: 2 in subject ID: 21', '2025-03-12 03:41:41'),
(293, 7, 'Login', 'User logged in successfully', '2025-03-12 03:59:49'),
(294, 6, 'Login', 'User logged in successfully', '2025-03-12 04:00:20'),
(295, 5, 'Login', 'User logged in successfully', '2025-03-12 04:00:46'),
(296, 5, 'Login', 'User logged in successfully', '2025-03-12 04:00:54'),
(297, 3, 'Login', 'User logged in successfully', '2025-03-12 04:02:59'),
(298, 7, 'Login', 'User logged in successfully', '2025-03-12 04:03:28'),
(299, 7, 'Attendance Recorded', 'Attendance recorded for user ID: 7 in subject ID: 21', '2025-03-12 04:03:45'),
(300, 6, 'Login', 'User logged in successfully', '2025-03-12 04:04:45'),
(301, 3, 'Attendance Recorded', 'Attendance recorded for student ID: 6 in subject ID: 21', '2025-03-12 04:05:04'),
(302, 5, 'Login', 'User logged in successfully', '2025-03-12 04:05:23'),
(303, 5, 'Attendance Recorded', 'Attendance recorded for user ID: 5 in subject ID: 21', '2025-03-12 04:05:31'),
(304, 5, 'Attendance Recorded', 'Attendance recorded for user ID: 5 in subject ID: 21', '2025-03-12 04:31:27'),
(305, 2, 'Login', 'User logged in successfully', '2025-03-12 04:31:45'),
(306, 3, 'Attendance Recorded', 'Attendance recorded for student ID: 2 in subject ID: 21', '2025-03-12 04:31:58'),
(307, 1, 'Login', 'User logged in successfully', '2025-03-12 04:50:06'),
(308, 3, 'Login', 'User logged in successfully', '2025-03-12 05:16:49'),
(309, 3, 'Attendance Recorded', 'Attendance recorded for student ID: 2 in subject ID: 21', '2025-03-12 05:19:04'),
(310, 3, 'Attendance Recorded', 'Attendance recorded for student ID: 2 in subject ID: 21', '2025-03-12 05:19:27'),
(311, 2, 'Attendance Recorded', 'Attendance recorded for user ID: 2 in subject ID: 21', '2025-03-12 05:31:19'),
(312, 3, 'Attendance Recorded', 'Attendance recorded for student ID: 2 in subject ID: 21', '2025-03-12 05:32:38'),
(313, 7, 'Login', 'User logged in successfully', '2025-03-12 05:33:02'),
(314, 3, 'Attendance Recorded', 'Attendance recorded for student ID: 7 in subject ID: 21', '2025-03-12 05:33:13'),
(315, 3, 'Attendance Recorded', 'Attendance recorded for student ID: 7 in subject ID: 21', '2025-03-12 05:35:27'),
(316, 1, 'Login', 'User logged in successfully', '2025-03-12 05:35:40'),
(317, 2, 'Login', 'User logged in successfully', '2025-03-12 05:35:55'),
(318, 2, 'Attendance Recorded', 'Attendance recorded for user ID: 2 in subject ID: 21', '2025-03-12 05:36:06'),
(319, 3, 'Login', 'User logged in successfully', '2025-03-12 09:52:21'),
(320, 2, 'Login', 'User logged in successfully', '2025-03-12 09:55:46'),
(321, 2, 'Login', 'User logged in successfully', '2025-03-12 09:57:51'),
(322, 3, 'Login', 'User logged in successfully', '2025-03-12 11:17:11'),
(323, 1, 'Login', 'User logged in successfully', '2025-03-12 11:19:30'),
(324, 2, 'Login', 'User logged in successfully', '2025-03-12 11:21:01'),
(325, 3, 'Login', 'User logged in successfully', '2025-03-12 11:22:46'),
(326, 2, 'Attendance Recorded', 'Attendance recorded for user ID: 2 in subject ID: 21', '2025-03-12 11:22:58'),
(327, 3, 'Attendance Recorded', 'Attendance recorded for student ID: 2 in subject ID: 21', '2025-03-12 11:23:49'),
(328, 2, 'Login', 'User logged in successfully', '2025-03-12 11:25:05'),
(329, 3, 'Attendance Recorded', 'Attendance recorded for student ID: 2 in subject ID: 21', '2025-03-12 11:26:22'),
(330, 7, 'Login', 'User logged in successfully', '2025-03-12 11:34:04'),
(331, 7, 'Attendance Recorded', 'Attendance recorded for user ID: 7 in subject ID: 21', '2025-03-12 11:45:23'),
(332, 7, 'Attendance Recorded', 'Attendance recorded for user ID: 7 in subject ID: 21', '2025-03-16 23:59:57'),
(333, 2, 'Login', 'User logged in successfully', '2025-03-12 11:51:29'),
(334, 2, 'Attendance Recorded', 'Attendance recorded for user ID: 2 in subject ID: 21', '2025-03-12 11:51:45'),
(335, 7, 'Login', 'User logged in successfully', '2025-03-12 12:05:22'),
(336, 3, 'Login', 'User logged in successfully', '2025-03-12 12:12:18'),
(337, 2, 'Login', 'User logged in successfully', '2025-03-12 12:13:06'),
(338, 7, 'Login', 'User logged in successfully', '2025-03-12 12:13:20'),
(339, 3, 'Attendance Recorded', 'Attendance recorded for student ID: 7 in subject ID: 21', '2025-03-12 12:13:46'),
(340, 2, 'Login', 'User logged in successfully', '2025-03-12 12:15:07'),
(341, 5, 'Login', 'User logged in successfully', '2025-03-12 12:27:29'),
(342, 2, 'Login', 'User logged in successfully', '2025-03-12 12:29:56'),
(343, 1, 'Login', 'User logged in successfully', '2025-03-12 13:00:33'),
(344, 1, 'Login', 'User logged in successfully', '2025-03-12 15:37:43'),
(345, 1, 'Batch User Action', 'Deactivated users: 2,5,6,7', '2025-03-12 16:35:58'),
(346, 1, 'Batch User Action', 'Activated users: 2,5,6,7', '2025-03-12 16:36:14'),
(347, 1, 'Login', 'User logged in successfully', '2025-03-13 02:00:15'),
(348, 2, 'Login', 'User logged in successfully', '2025-03-13 02:02:51'),
(349, 2, 'Attendance Recorded', 'Attendance recorded for user ID: 2 in subject ID: 21', '2025-03-13 02:03:13'),
(350, 2, 'Login', 'User logged in successfully', '2025-03-13 04:11:00'),
(351, 3, 'Login', 'User logged in successfully', '2025-03-13 04:11:22'),
(352, 1, 'Login', 'User logged in successfully', '2025-03-13 05:57:47'),
(353, 3, 'Login', 'User logged in successfully', '2025-03-13 06:57:02'),
(354, 2, 'Login', 'User logged in successfully', '2025-03-13 07:55:39'),
(355, 1, 'Login', 'User logged in successfully', '2025-03-13 08:00:12');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstname` varchar(225) DEFAULT NULL,
  `middle_init` varchar(225) DEFAULT NULL,
  `lastname` varchar(225) DEFAULT NULL,
  `email` varchar(225) DEFAULT NULL,
  `password` varchar(225) DEFAULT NULL,
  `usertype` varchar(225) DEFAULT NULL,
  `department` varchar(225) DEFAULT NULL,
  `status` varchar(225) DEFAULT NULL,
  `image` varchar(225) DEFAULT NULL,
  `gender` varchar(225) DEFAULT NULL,
  `last_update` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `middle_init`, `lastname`, `email`, `password`, `usertype`, `department`, `status`, `image`, `gender`, `last_update`, `last_login`) VALUES
(1, 'Kenneth Laurence ', 'P', 'Bonaagua', 'bonaagua@gmail.com', '$2y$10$cPLowJgoOIexhPjWSTaU0uV8BjvYsuBu9YN0Lwun.2RMs.qcHLfqO', 'admin', 'BSCS', 'active', 'profile_img/profile_1_1741523751.jpg', 'male', '2025-03-09 12:35:51', '2025-03-13 08:00:12'),
(2, 'Kenneth Laurence', 'P', 'Bonaagua', 'kbonaagua2021@student.nbscollege.edu.ph', '$2y$10$jcs3mnu5yAinM4TiIjXwWe8BO.zcpA6hxg9R46NsAI19xTTWa1sNC', 'student', 'BSCS', 'active', NULL, 'male', '2025-03-10 05:18:52', '2025-03-13 07:55:39'),
(3, 'Vincent', '', 'Dais', 'vincentpaul.dais@nbscollege.edu.ph', '$2y$10$IEVHbQ/AoNZmdxJmU.uUPO6FJYce5G0bezUxEzyB9i08wc3Docmeu', 'faculty', 'BSCS', 'active', NULL, 'male', '2025-03-10 06:01:50', '2025-03-13 06:57:02'),
(5, 'Jenepir', '', 'Jabillo', 'jjabillo2021@student.nbscollege.edu.ph', '$2y$10$gI.ZoAqGLMmKUW2ZXKfNn.usZnqAgU05kTesAIRRo5.xqZcZFrHlC', 'student', 'BSCS', 'active', NULL, 'female', '2025-03-11 04:49:22', '2025-03-12 12:27:29'),
(6, 'Cayce', '', 'Evangelista', 'cevangelista2021@student.nbscollege.edu.ph', '$2y$10$KI7M/eUI6MfLSuKh.EH/3Od27I4r/.TGBxsYkKfwK/zdoTP3gkvLK', 'student', 'BSCS', 'active', NULL, 'male', '2025-03-11 04:49:15', '2025-03-12 04:04:45'),
(7, 'Carlos Miguel', '', 'Autor', 'cautor2021@student.nbscollege.edu.ph', '$2y$10$vkyBuLsnq1Ew4AgYTJq.zeX3UiXPPTHPUE3ktJTT6ot/KKj6ARHqC', 'student', 'BSCS', 'active', NULL, 'male', '2025-03-11 04:49:07', '2025-03-12 12:13:20');

-- --------------------------------------------------------

--
-- Table structure for table `usersubjects`
--

CREATE TABLE `usersubjects` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usersubjects`
--

INSERT INTO `usersubjects` (`id`, `user_id`, `subject_id`) VALUES
(2, 2, 21),
(3, 2, 38),
(5, 5, 21),
(6, 5, 35),
(8, 7, 21),
(10, 7, 38),
(11, 6, 21),
(12, 6, 35);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendances`
--
ALTER TABLE `attendances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `updates`
--
ALTER TABLE `updates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `usersubjects`
--
ALTER TABLE `usersubjects`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendances`
--
ALTER TABLE `attendances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `updates`
--
ALTER TABLE `updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=356;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `usersubjects`
--
ALTER TABLE `usersubjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
