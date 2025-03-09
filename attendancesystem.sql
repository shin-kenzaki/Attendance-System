-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 09, 2025 at 04:39 PM
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
  `time_in` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 5, '404', 'Monday', '10:30:00', '12:30:00'),
(2, 6, '406', 'Monday', '09:30:00', '11:30:00');

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
(5, 'CSC424', 'Application Development', 3, 1, '36MXXR'),
(6, 'CSC323', 'Structures of Programming Languages', NULL, 1, '8DR5DR');

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
(115, 3, 'Login', 'User logged in successfully', '2025-03-09 15:07:29');

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
(1, 'Kenneth Laurence ', 'P', 'Bonaagua', 'bonaagua@gmail.com', '$2y$10$cPLowJgoOIexhPjWSTaU0uV8BjvYsuBu9YN0Lwun.2RMs.qcHLfqO', 'admin', 'BSCS', 'active', 'profile_img/profile_1_1741523751.jpg', 'male', '2025-03-09 12:35:51', '2025-03-09 14:33:34'),
(2, 'Shin', '', 'Kenzaki', 'shinkenzaki@gmail.com', '$2y$10$RqTTCqHvbguR.7qOnsowxO54.JIwchwy/2QaIVcgIMYYyQsaF9H.S', 'student', 'BSCS', 'active', NULL, 'male', '2025-03-09 13:27:03', '2025-03-09 13:27:09'),
(3, 'Vincent', '', 'Dais', 'vdais@nbscollege.edu.ph', '$2y$10$8wLNTi1WCi/vftzVfL6R5eCrH9kk/W9kab9UCcaiSdHC0I/3fGQq2', 'faculty', 'BSCS', 'active', NULL, 'male', '2025-03-09 15:07:24', '2025-03-09 15:07:29');

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
-- Indexes for dumped tables
--

--
-- Indexes for table `attendances`
--
ALTER TABLE `attendances`
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
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `updates`
--
ALTER TABLE `updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `usersubjects`
--
ALTER TABLE `usersubjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
