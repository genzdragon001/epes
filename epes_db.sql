-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 06, 2026 at 04:50 AM
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
-- Database: `epes_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int(30) NOT NULL,
  `comment_text` text NOT NULL,
  `employee_id` int(30) NOT NULL,
  `rater_id` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `comment_text`, `employee_id`, `rater_id`) VALUES
(3, 'TEST', 23, 4);

-- --------------------------------------------------------

--
-- Table structure for table `department_list`
--

CREATE TABLE `department_list` (
  `id` int(30) NOT NULL,
  `department` varchar(200) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `department_list`
--

INSERT INTO `department_list` (`id`, `department`, `description`) VALUES
(1, 'BSCS', 'Bachelor of Science in Computer Science'),
(3, 'CAS', 'College of Arts and Sciences'),
(4, 'BPA', 'Bachelor of Public Administration'),
(5, 'AB Econ', 'Bachelor of Arts in Economics'),
(6, 'BSIS', 'Bachelor of Science in Information Systems'),
(7, 'BS Entrep', 'Bachelor of Science in Entrepreneurship');

-- --------------------------------------------------------

--
-- Table structure for table `designation_list`
--

CREATE TABLE `designation_list` (
  `id` int(30) NOT NULL,
  `designation` varchar(200) NOT NULL DEFAULT 'Faculty',
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `designation_list`
--

INSERT INTO `designation_list` (`id`, `designation`, `description`) VALUES
(1, 'Dean', 'CAS Dean'),
(2, 'Department Head', 'Dept Head'),
(3, 'Faculty', 'Faculty of College of Arts and Sciences'),
(4, 'Vice President for Administration and Finance\r\n', 'VPAF'),
(6, 'Director, TAEx', 'Director for Technical Advisory and Extension Services ');

-- --------------------------------------------------------

--
-- Table structure for table `efficiency_attendance`
--

CREATE TABLE `efficiency_attendance` (
  `id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `target_id` int(11) NOT NULL,
  `rating_period` varchar(50) NOT NULL,
  `activity_title` varchar(255) NOT NULL,
  `date_conducted` date NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `rating` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `efficiency_attendance`
--

INSERT INTO `efficiency_attendance` (`id`, `faculty_id`, `target_id`, `rating_period`, `activity_title`, `date_conducted`, `percentage`, `rating`, `created_at`) VALUES
(1, 23, 23, '1st Semester 2025-2026', 'graduation', '2025-11-11', 100.00, 5, '2026-04-04 15:25:58'),
(2, 15, 23, '1st Semester 2025-2026', 'Aug 2025', '2025-09-15', 90.00, 4, '2026-04-06 01:43:11'),
(3, 15, 23, '1st Semester 2025-2026', 'SEP 2025', '2025-10-15', 100.00, 5, '2026-04-06 01:44:04');

-- --------------------------------------------------------

--
-- Table structure for table `employee_list`
--

CREATE TABLE `employee_list` (
  `id` int(30) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `firstname` varchar(200) NOT NULL,
  `middlename` varchar(200) NOT NULL,
  `lastname` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `is_activated` int(10) NOT NULL DEFAULT 0,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expires` varchar(30) DEFAULT NULL,
  `failed_login` int(11) NOT NULL DEFAULT 0,
  `isBlocked` int(11) NOT NULL DEFAULT 0,
  `password` text NOT NULL,
  `department_id` int(30) NOT NULL,
  `position_id` int(10) NOT NULL,
  `designation_id` int(30) NOT NULL DEFAULT 3,
  `evaluator_id` int(30) NOT NULL,
  `avatar` text DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_list`
--

INSERT INTO `employee_list` (`id`, `employee_id`, `firstname`, `middlename`, `lastname`, `email`, `is_activated`, `reset_token`, `reset_expires`, `failed_login`, `isBlocked`, `password`, `department_id`, `position_id`, `designation_id`, `evaluator_id`, `avatar`, `date_created`) VALUES
(2, '', 'Daisy Jean', 'A.', 'Castillo', 'mwilliams@sample.com', 0, NULL, NULL, 0, 0, 'a88df23ac492e6e2782df6586a0c645f', 1, 1, 2, 1, 'no-image-available.png', '2021-03-02 13:52:48'),
(3, '215', 'genesis', '', 'tumbaga', 'g@g.com', 0, NULL, NULL, 0, 0, '289ffeb2a745ccf51ca89a297f47e382', 1, 0, 5, 1, 'no-image-available.png', '2025-08-26 10:37:18'),
(6, '440', 'Nonalyn', 'D.', 'Tombocon', 'nonalyn@gmail.com', 0, '5ae6639f29180a0705eadc391f7cd49a06c6fde0b38b47b2b54b887c3de1025898c8f69c9c0de99177b0688bee735ecc47da', NULL, 0, 0, 'bfd7ae9bf685cc9ba318d542ffed1d39', 7, 4, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(7, '186', 'Sharmaena', 'M.', 'Villester', 'smvillester@debesmscat.edu.ph', 0, 'ffa3db2bdcfb590f8051e18105605000b9551d5f6410d9b446828dcdfa698e72fd16d798cf2ec13cda57dcf15843ea6a9df2', NULL, 0, 0, '$2y$10$ylo33jcm81l0vMD7u.VEaO5kvzed0mtcIFBG7KL489zgpDq9RD2sS', 5, 4, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(8, '255', 'Jerryl', 'Q.', 'Romero', '', 0, NULL, NULL, 0, 0, '', 4, 1, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(9, '60', 'Rowelyn', 'M.', 'Ramiso', '', 0, NULL, NULL, 0, 0, '', 1, 12, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(10, '41', 'Emeros', 'R.', 'Castillo', '', 0, NULL, NULL, 0, 0, '', 1, 12, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(11, '25', 'Wilfred', 'C.', 'Pagorogon', '', 0, NULL, NULL, 0, 0, '', 1, 11, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(12, '106', 'Rony', 'S.', 'Toling', '', 0, NULL, NULL, 0, 0, '', 7, 1, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(13, '377', 'Al Wilson', 'T.', 'Velarde', '', 0, NULL, NULL, 0, 0, '', 7, 4, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(14, '506', 'Teresa', 'A.', 'Aguilar', 'taguilar@gmail.com', 0, '91b33b7e8ab3895388ca2f8459a2a6440225ae3ea8cd04241f49c379e441f4b9633835047eaec8368c5519e66292ac8db727', NULL, 0, 0, '$2y$10$s/q6EXovCdHZEV/IjAFcUubgp6EigcfdXi8U24Sj/V0YxTDMo95Dm', 7, 1, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(15, '268', 'Randy', 'D.', 'Bello', 'rbello@gmail.com', 0, '95aafc9687ee4a8c6eb8ba8d735b4c13548018bc5834ac43775977049387079cc9f3079316dbaba0de5098a50102f990bd0e', '2026-04-05 01:00:02', 0, 0, '163218e536c13ff2fc9a4d76e34be085', 6, 1, 2, 1, 'no-image-available.png', '2025-10-24 16:50:41'),
(16, '350', 'Dennis', 'G.', 'Corlet', 'dcorlet@gmail.com', 0, NULL, NULL, 0, 0, '7daacea5f373b4c1c054158b126d317f', 6, 4, 6, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(17, '806', 'Marjobel', 'R.', 'Castro', 'mcastro@gmail.com', 0, '38f6ed036d6c511ce90d65d42adac57b46fdcef1d330028320a8c6bb9fa90db97795367a0db624654b0420cb58808ca9e748', '2026-04-05 01:05:25', 0, 0, '$2y$10$3Yft9Gw5Qu0srZZ/PsYth.0Ka7ANmSIVNaXDD.H.DhAZm5kG79RtW', 5, 1, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(18, '857', 'Florence Joy', 'M.', 'Capinig', 'fcapinig@gmail.com', 0, '3c6fb1c434ae867ff80eb47ec9ed9f4f0bcff43972d2e3c96035844d1ade9a4467ca1d040b91f9700daa951539d1dc08a5e5', NULL, 0, 0, '$2y$10$Xhefv1CCdLxasEdsyKa7W.axta3qaf7lD9tlFJNk5J/Itz6l.eaK2', 4, 5, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(19, '1020', 'Maria Jessabelle', 'P.', 'Romano', '', 0, NULL, NULL, 0, 0, '', 1, 18, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(20, '', 'Al', '', 'Escorel', '', 0, NULL, NULL, 0, 0, '', 1, 18, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(21, '', 'Alfred', '', 'Punay', '', 0, NULL, NULL, 0, 0, '', 1, 18, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(22, '197', 'Rodulfo', '', 'Gabrito', '', 0, NULL, NULL, 0, 0, '', 1, 18, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(23, '', 'Marlon', 'R.', 'Hermoso', 'mhermoso@debesmscat.edu.ph', 0, 'c8f759a539858b08e9e46251b1ae9f09', NULL, 0, 0, 'c8f759a539858b08e9e46251b1ae9f09', 6, 19, 3, 4, '1775303760_NOA 3rd IRODEO.jpg', '2025-10-24 16:50:41'),
(24, '1132', 'Raise Torm', 'C.', 'Dalanon', '', 0, NULL, NULL, 0, 0, '', 6, 18, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(25, '1038', 'Nielyn', 'A.', 'Lalaguna', '', 0, NULL, NULL, 0, 0, '', 6, 18, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(26, '1081', 'Clarejy', 'A.', 'Duyag', 'cduyag@gmail.com', 0, NULL, NULL, 2, 0, 'babc2860c134a097abd7b53f2a2a4193', 6, 19, 3, 4, 'no-image-available.png', '2025-10-24 16:50:41'),
(27, '777', 'Rosaleth', 'D.', 'Macadat', '', 0, NULL, NULL, 0, 0, '', 7, 18, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(28, '1166', 'Kimberly', '', 'Vibas', '', 0, NULL, NULL, 0, 0, '', 7, 18, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(29, '1026', 'Jelyn', 'C.', 'Sabaulan', '', 0, NULL, NULL, 0, 0, '', 5, 18, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(30, '703', 'Leo', 'C.', 'Laurio', '', 0, NULL, NULL, 0, 0, '', 5, 18, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(31, '1143', 'Cklainch Alfe', 'R.', 'Jordan', '', 0, NULL, NULL, 0, 0, '', 4, 18, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41'),
(32, '1151', 'Rachelle Nicole', 'B.', 'Lotivio', '', 0, NULL, NULL, 0, 0, '', 4, 18, 0, 0, 'no-image-available.png', '2025-10-24 16:50:41');

-- --------------------------------------------------------

--
-- Table structure for table `evaluator_list`
--

CREATE TABLE `evaluator_list` (
  `id` int(30) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `department_id` int(10) NOT NULL,
  `type` int(11) NOT NULL DEFAULT 0,
  `firstname` varchar(200) NOT NULL,
  `middlename` varchar(200) NOT NULL,
  `lastname` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expires` varchar(30) DEFAULT NULL,
  `failed_login` int(11) NOT NULL DEFAULT 0,
  `isBlocked` int(11) NOT NULL DEFAULT 0,
  `password` text NOT NULL,
  `avatar` text DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluator_list`
--

INSERT INTO `evaluator_list` (`id`, `employee_id`, `department_id`, `type`, `firstname`, `middlename`, `lastname`, `email`, `reset_token`, `reset_expires`, `failed_login`, `isBlocked`, `password`, `avatar`, `date_created`) VALUES
(1, '', 3, 1, 'Rowelyn', 'M.', 'Ramiso', 'rmramiso@debesmscat.edu.ph', NULL, NULL, 0, 0, '163218e536c13ff2fc9a4d76e34be085', '1607136060_47446233-clean-noir-et-gradient-sombre-image-de-fond-abstrait-.jpg', '2020-12-05 10:41:34'),
(4, '', 6, 0, 'Randy', '', 'Bello', 'rbello@gmail.com', NULL, NULL, 0, 0, '163218e536c13ff2fc9a4d76e34be085', 'no-image-available.png', '2026-03-23 15:52:19');

-- --------------------------------------------------------

--
-- Table structure for table `function_categories`
--

CREATE TABLE `function_categories` (
  `id` int(11) NOT NULL,
  `category` varchar(50) NOT NULL COMMENT 'strategic, core, support',
  `sub_category` varchar(50) DEFAULT NULL COMMENT 'instructions, research, extension (for core only)',
  `name` varchar(200) NOT NULL,
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `function_categories`
--

INSERT INTO `function_categories` (`id`, `category`, `sub_category`, `name`, `percentage`, `is_active`, `date_created`) VALUES
(1, 'strategic', NULL, 'Strategic Function', 20.00, 1, '2026-03-30 10:55:44'),
(2, 'core', 'instructions', 'Instructions', 26.67, 1, '2026-03-30 10:55:44'),
(3, 'core', 'research', 'Research', 26.67, 1, '2026-03-30 10:55:44'),
(4, 'core', 'extension', 'Extension', 26.66, 1, '2026-03-30 10:55:44'),
(5, 'support', NULL, 'Support Function', 20.00, 1, '2026-03-30 10:55:44');

-- --------------------------------------------------------

--
-- Table structure for table `function_list`
--

CREATE TABLE `function_list` (
  `id` int(11) NOT NULL,
  `function_category_id` int(11) NOT NULL,
  `designation_id` int(30) DEFAULT NULL COMMENT 'NULL means applies to all',
  `name` varchar(500) NOT NULL,
  `description` text DEFAULT NULL,
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `function_list`
--

INSERT INTO `function_list` (`id`, `function_category_id`, `designation_id`, `name`, `description`, `percentage`, `is_active`, `date_created`) VALUES
(1, 1, NULL, 'Strategic Planning & Management', NULL, 10.00, 1, '2026-03-30 10:55:44'),
(2, 1, NULL, 'Policy Development', NULL, 5.00, 1, '2026-03-30 10:55:44'),
(3, 1, NULL, 'Stakeholder Engagement', NULL, 5.00, 1, '2026-03-30 10:55:44'),
(4, 2, NULL, 'Curriculum Development', NULL, 8.89, 1, '2026-03-30 10:55:44'),
(5, 2, NULL, 'Classroom Teaching', NULL, 8.89, 1, '2026-03-30 10:55:44'),
(6, 2, NULL, 'Student Advising', NULL, 4.44, 1, '2026-03-30 10:55:44'),
(7, 2, NULL, 'Laboratory/Skills Development', NULL, 4.45, 1, '2026-03-30 10:55:44'),
(8, 3, NULL, 'Research Publication', NULL, 13.34, 1, '2026-03-30 10:55:44'),
(9, 3, NULL, 'Research Presentation', NULL, 6.67, 1, '2026-03-30 10:55:44'),
(10, 3, NULL, 'Research Community Extension', NULL, 6.66, 1, '2026-03-30 10:55:44'),
(11, 4, NULL, 'Community Outreach Programs', NULL, 8.89, 1, '2026-03-30 10:55:44'),
(12, 4, NULL, 'Technical Assistance', NULL, 8.88, 1, '2026-03-30 10:55:44'),
(13, 4, NULL, 'Partnership Development', NULL, 8.89, 1, '2026-03-30 10:55:44'),
(14, 5, NULL, 'Administrative Tasks', NULL, 10.00, 1, '2026-03-30 10:55:44'),
(15, 5, NULL, 'Committee Work', NULL, 5.00, 1, '2026-03-30 10:55:44'),
(16, 5, NULL, 'Professional Development', NULL, 5.00, 1, '2026-03-30 10:55:44');

-- --------------------------------------------------------

--
-- Table structure for table `login_audit_trail`
--

CREATE TABLE `login_audit_trail` (
  `audit_id` bigint(20) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `username` varchar(150) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `login_status` enum('SUCCESS','FAILED') NOT NULL,
  `failure_reason` text DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `session_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_audit_trail`
--

INSERT INTO `login_audit_trail` (`audit_id`, `user_id`, `username`, `ip_address`, `user_agent`, `login_status`, `failure_reason`, `login_time`, `session_id`) VALUES
(1, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', NULL, '2025-09-09 01:41:23', NULL),
(2, NULL, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2025-09-09 01:41:57', NULL),
(3, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Wrong password', '2025-09-09 01:43:30', NULL),
(4, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', NULL, '2025-09-09 01:43:51', NULL),
(5, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Account blocked', '2025-09-09 01:44:18', NULL),
(6, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', NULL, '2025-09-09 01:45:43', NULL),
(7, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 01:45:45', NULL),
(8, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', NULL, '2025-09-09 01:47:34', NULL),
(9, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 01:48:52', NULL),
(10, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', NULL, '2025-09-09 01:49:52', NULL),
(11, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 01:50:28', NULL),
(12, NULL, 'rmramiso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2025-09-09 01:50:52', NULL),
(13, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', NULL, '2025-09-09 01:51:06', NULL),
(14, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 01:51:34', NULL),
(15, NULL, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2025-09-09 01:51:54', NULL),
(16, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', NULL, '2025-09-09 01:59:11', NULL),
(17, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', NULL, '2025-09-09 01:59:25', NULL),
(18, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', NULL, '2025-09-09 01:59:43', NULL),
(19, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 01:59:46', NULL),
(20, NULL, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2025-09-09 02:05:51', NULL),
(21, NULL, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2025-09-09 02:05:53', NULL),
(22, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 02:09:10', NULL),
(23, 1, 'rmramiso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 02:12:30', NULL),
(24, 1, 'rmramiso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Wrong password', '2025-09-09 02:17:54', NULL),
(25, 1, 'rmramiso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Wrong password', '2025-09-09 02:17:58', NULL),
(26, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Wrong password', '2025-09-09 02:18:06', NULL),
(27, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Wrong password', '2025-09-09 02:19:01', NULL),
(28, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Wrong password', '2025-09-09 02:19:04', NULL),
(29, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Wrong password', '2025-09-09 02:21:20', NULL),
(30, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Account blocked', '2025-09-09 02:21:43', NULL),
(31, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', NULL, '2025-09-09 02:22:23', NULL),
(32, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 02:22:28', NULL),
(33, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', NULL, '2025-09-09 02:23:09', NULL),
(34, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 02:23:13', NULL),
(35, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', NULL, '2025-09-09 02:24:33', NULL),
(36, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 02:24:54', NULL),
(37, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', NULL, '2025-09-09 02:25:03', NULL),
(38, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 02:25:06', NULL),
(39, NULL, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2025-09-09 02:27:07', NULL),
(40, NULL, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2025-09-09 02:27:38', NULL),
(41, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 02:32:06', NULL),
(42, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 02:33:24', NULL),
(43, NULL, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2025-09-09 02:35:40', NULL),
(44, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 02:35:50', NULL),
(45, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 02:36:02', NULL),
(46, NULL, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2025-09-09 02:36:15', NULL),
(47, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 02:36:17', NULL),
(48, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 02:37:08', NULL),
(49, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 02:43:31', NULL),
(50, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 02:44:55', NULL),
(51, NULL, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2025-09-09 02:46:51', NULL),
(52, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 02:46:56', NULL),
(53, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '', NULL, '2025-09-09 02:46:59', NULL),
(54, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 02:48:54', NULL),
(55, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '', NULL, '2025-09-09 02:48:55', NULL),
(56, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 02:50:37', NULL),
(57, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 02:50:40', NULL),
(58, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 02:50:59', NULL),
(59, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 02:51:47', NULL),
(60, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 02:51:54', NULL),
(61, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 02:52:07', NULL),
(62, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 02:52:31', NULL),
(63, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 02:56:46', NULL),
(64, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 02:58:56', NULL),
(65, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 03:03:15', NULL),
(66, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 03:03:22', NULL),
(67, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 03:04:36', NULL),
(68, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 03:04:42', NULL),
(70, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 03:07:16', NULL),
(71, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 03:07:22', NULL),
(72, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 03:14:44', NULL),
(73, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '', 'Session expired due to inactivity', '2025-09-09 03:18:08', NULL),
(74, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 03:18:14', NULL),
(75, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '', 'Session expired due to inactivity', '2025-09-09 03:18:49', NULL),
(76, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 03:19:39', NULL),
(77, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2025-09-09 03:19:52', NULL),
(78, NULL, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2025-09-09 03:20:45', NULL),
(79, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 03:20:50', NULL),
(80, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 03:54:52', NULL),
(81, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 03:55:51', NULL),
(82, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 03:58:14', NULL),
(83, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 03:58:23', NULL),
(84, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2025-09-09 05:05:21', NULL),
(85, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 05:05:31', NULL),
(86, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 06:13:50', NULL),
(87, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 06:14:01', NULL),
(88, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 06:15:47', NULL),
(89, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 06:15:55', NULL),
(90, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 06:16:31', NULL),
(91, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 06:16:39', NULL),
(92, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 06:30:59', NULL),
(93, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Wrong password', '2025-09-09 06:31:09', NULL),
(94, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 06:31:14', NULL),
(95, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-09 06:31:32', NULL),
(96, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-09 06:32:18', NULL),
(97, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-14 08:50:34', NULL),
(98, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-14 08:50:38', NULL),
(99, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-14 08:50:50', NULL),
(100, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-14 08:50:56', NULL),
(101, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-14 08:51:02', NULL),
(102, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-14 08:52:51', NULL),
(103, NULL, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2025-09-14 08:53:06', NULL),
(104, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-14 08:53:10', NULL),
(105, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-14 08:54:59', NULL),
(106, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-14 08:55:07', NULL),
(107, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-14 09:00:08', NULL),
(108, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-14 09:00:53', NULL),
(109, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-14 09:02:21', NULL),
(110, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-14 09:02:41', NULL),
(111, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-14 09:03:04', NULL),
(112, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-14 09:03:09', NULL),
(113, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-14 09:08:34', NULL),
(114, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-14 09:08:42', NULL),
(115, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-14 09:09:09', NULL),
(116, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-14 09:09:15', NULL),
(117, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-14 09:28:11', NULL),
(118, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2025-09-14 10:49:19', NULL),
(119, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-14 10:49:29', NULL),
(120, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-14 11:53:07', NULL),
(121, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-14 11:53:17', NULL),
(122, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2025-09-14 13:03:04', NULL),
(123, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-14 13:03:12', NULL),
(124, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-14 14:35:58', NULL),
(125, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-14 14:36:04', NULL),
(126, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-14 14:48:49', NULL),
(127, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-14 14:48:57', NULL),
(128, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-14 14:49:10', NULL),
(129, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-14 14:49:17', NULL),
(130, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-14 14:52:57', NULL),
(131, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-14 14:53:08', NULL),
(132, 2, 'Unknown', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', NULL, '2025-09-14 14:53:13', NULL),
(133, 2, 'Unknown', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', NULL, '2025-09-14 14:53:17', NULL),
(134, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', NULL, '2025-09-14 14:55:59', NULL),
(135, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Changed status from For Verification to Verified', '2025-09-14 15:03:14', NULL),
(136, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Changed status from Upload Another MOV to Verifiedof Employee ID 12', '2025-09-14 15:04:48', NULL),
(137, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-22 07:33:29', NULL),
(138, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2025-09-22 08:22:12', NULL),
(139, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-22 08:22:26', NULL),
(140, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Changed status from Verified to For Verificationof Employee ID 12', '2025-09-22 08:24:19', NULL),
(141, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Changed status from For Verification to Verifiedof Employee ID 12', '2025-09-22 08:24:22', NULL),
(142, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-22 08:25:16', NULL),
(143, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-22 08:25:21', NULL),
(144, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-22 08:29:44', NULL),
(145, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-22 08:29:57', NULL),
(146, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Changed status from Verified to Upload Another MOVof Employee ID 12', '2025-09-22 08:30:28', NULL),
(147, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-22 08:30:34', NULL),
(148, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-22 08:30:42', NULL),
(149, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-22 08:31:30', NULL),
(150, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-22 08:31:42', NULL),
(151, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Changed status from Upload Another MOV to Verifiedof Employee ID 12', '2025-09-22 08:31:50', NULL),
(152, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-22 08:31:53', NULL),
(153, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-22 08:32:00', NULL),
(154, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'SUCCESS', 'Login successful', '2025-09-22 08:33:29', NULL),
(155, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'SUCCESS', 'Changed status from Verified to Upload Another MOVof Employee ID 12', '2025-09-22 08:33:40', NULL),
(156, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-22 09:15:21', NULL),
(157, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2025-09-22 09:15:27', NULL),
(158, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'SUCCESS', 'Login successful', '2025-09-22 09:15:37', NULL),
(159, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'SUCCESS', 'User logged out', '2025-09-22 09:16:43', NULL),
(160, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', 'SUCCESS', 'Login successful', '2025-09-22 09:16:49', NULL),
(161, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-22 09:24:40', NULL),
(162, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-24 06:45:37', NULL),
(163, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Deleted file successfuly', '2025-09-24 06:49:58', NULL),
(164, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Deleted file successfuly', '2025-09-24 06:51:18', NULL),
(165, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Deleted file successfuly', '2025-09-24 06:53:33', NULL),
(166, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Deleted file successfuly', '2025-09-24 06:53:45', NULL),
(167, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Deleted file successfuly', '2025-09-24 06:54:21', NULL),
(168, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Deleted file successfuly', '2025-09-24 06:58:24', NULL),
(169, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Deleted file successfuly', '2025-09-24 06:59:21', NULL),
(170, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-24 07:12:15', NULL),
(171, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-24 07:12:21', NULL),
(172, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Changed status from For Verification to Verifiedof Employee ID 19', '2025-09-24 07:12:32', NULL),
(173, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-24 07:12:35', NULL),
(174, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-24 07:12:39', NULL),
(175, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-24 07:13:26', NULL),
(176, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-24 07:13:40', NULL),
(177, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-24 07:18:23', NULL),
(178, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Wrong password', '2025-09-24 07:18:28', NULL),
(179, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-24 07:20:44', NULL),
(180, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-24 07:21:18', NULL),
(181, NULL, 'depthead@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2025-09-24 07:21:27', NULL),
(182, 3, 'depthead@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-24 07:21:32', NULL),
(183, 3, 'depthead@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-24 07:32:09', NULL),
(184, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-24 07:32:13', NULL),
(185, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-09-24 07:37:40', NULL),
(186, NULL, 'depthead@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2025-09-24 07:37:47', NULL),
(187, 3, 'depthead@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-09-24 07:37:50', NULL),
(188, 3, 'depthead@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2025-09-24 08:27:44', NULL),
(189, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'Login successful', '2025-10-24 07:54:46', NULL),
(190, 3, 'g@g.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'Login successful', '2025-10-24 07:55:19', NULL),
(191, 3, 'g@g.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'User logged out', '2025-10-24 08:04:12', NULL),
(192, 3, 'g@g.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'Login successful', '2025-10-24 08:10:07', NULL),
(193, 3, 'g@g.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'User logged out', '2025-10-24 08:10:24', NULL),
(194, 3, 'g@g.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'Login successful', '2025-10-24 08:10:29', NULL),
(195, 3, 'g@g.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'User logged out', '2025-10-24 08:21:18', NULL),
(196, NULL, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'FAILED', 'Email not found', '2025-10-24 08:21:35', NULL),
(197, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'Login successful', '2025-10-24 08:21:38', NULL),
(198, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-10-30 12:59:22', NULL),
(199, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-10-30 13:03:21', NULL),
(200, NULL, 'admin@admin.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2025-10-30 13:03:30', NULL),
(201, NULL, 'admin@admin.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2025-10-30 13:03:36', NULL),
(202, NULL, 'admin@admin.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2025-10-30 13:03:43', NULL),
(203, NULL, 'admin@admin.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2025-10-30 13:06:33', NULL),
(204, NULL, 'admin@admin.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2025-10-30 13:06:40', NULL),
(205, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-10-30 13:06:54', NULL),
(206, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'Login successful', '2025-10-30 13:08:28', NULL),
(207, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'User logged out', '2025-10-30 13:20:08', NULL),
(208, 3, 'depthead@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'Login successful', '2025-10-30 13:20:27', NULL),
(209, 3, 'depthead@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'User logged out', '2025-10-30 13:23:04', NULL),
(210, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'Login successful', '2025-10-30 13:23:09', NULL),
(211, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2025-10-30 14:28:11', NULL),
(212, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2025-10-30 14:28:22', NULL),
(213, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'User logged out', '2025-10-30 14:41:08', NULL),
(214, 1, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2025-10-30 14:47:34', NULL),
(215, 6, 'nonalyn@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'Login successful', '2025-10-30 14:52:15', NULL),
(216, 6, 'nonalyn@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'User logged out', '2025-10-30 14:59:47', NULL),
(217, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'Login successful', '2025-10-30 15:01:50', NULL),
(218, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'User logged out', '2025-10-30 15:04:34', NULL),
(219, 6, 'nonalyn@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'Login successful', '2025-10-30 15:04:43', NULL),
(220, 6, 'nonalyn@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'User logged out', '2025-10-30 15:06:19', NULL),
(221, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'Login successful', '2025-10-30 15:06:27', NULL),
(222, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'User logged out', '2025-10-30 15:06:39', NULL),
(223, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'Login successful', '2025-10-30 15:06:44', NULL),
(224, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'User logged out', '2025-10-30 15:07:38', NULL),
(225, 3, 'depthead@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'Login successful', '2025-10-30 15:07:42', NULL),
(226, 3, 'depthead@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', 'SUCCESS', 'User logged out', '2025-10-30 15:08:00', NULL),
(227, NULL, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2026-03-01 14:01:44', NULL),
(228, NULL, 'genz.tumbaga@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2026-03-01 14:01:47', NULL),
(229, NULL, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2026-03-01 14:03:13', NULL),
(230, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-01 14:03:16', NULL),
(231, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-03-01 14:23:43', NULL),
(232, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-01 14:23:54', NULL),
(233, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2026-03-01 14:32:50', NULL);
INSERT INTO `login_audit_trail` (`audit_id`, `user_id`, `username`, `ip_address`, `user_agent`, `login_status`, `failure_reason`, `login_time`, `session_id`) VALUES
(234, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-01 14:33:24', NULL),
(235, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-03-01 15:05:49', NULL),
(236, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-01 15:06:01', NULL),
(237, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2026-03-01 15:40:44', NULL),
(238, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'FAILED', 'Wrong password', '2026-03-11 02:36:15', NULL),
(239, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-11 02:36:18', NULL),
(240, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-23 03:15:39', NULL),
(241, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2026-03-23 03:26:38', NULL),
(242, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-23 03:26:45', NULL),
(243, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-03-23 05:09:04', NULL),
(244, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-23 05:09:22', NULL),
(245, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-03-23 07:33:41', NULL),
(246, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-23 07:33:48', NULL),
(247, 23, 'mhermoso@debesmscat.edu.ph', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-23 07:48:17', NULL),
(248, 4, 'faculty@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2026-03-23 07:50:55', NULL),
(249, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-23 07:51:16', NULL),
(250, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2026-03-23 07:52:24', NULL),
(251, NULL, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2026-03-23 07:52:31', NULL),
(252, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-23 07:52:33', NULL),
(253, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-23 07:53:11', NULL),
(254, NULL, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2026-03-23 14:32:50', NULL),
(255, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-23 14:32:54', NULL),
(256, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-23 14:33:27', NULL),
(257, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 23 (task ID 6)', '2026-03-23 15:20:11', NULL),
(258, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Changed status from \'Verified\' to \'For Verification\' for faculty ID 23 (task ID 3)', '2026-03-23 15:20:17', NULL),
(259, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-23 15:21:29', NULL),
(260, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-23 15:21:43', NULL),
(261, 26, 'cduyag@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', 'SUCCESS', 'Login successful', '2026-03-23 15:22:50', NULL),
(262, 26, 'cduyag@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', 'SUCCESS', 'Deleted file successfuly', '2026-03-23 15:27:28', NULL),
(263, 26, 'cduyag@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', 'SUCCESS', 'Deleted file successfuly', '2026-03-23 15:29:11', NULL),
(264, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 26 (task ID 3)', '2026-03-23 15:30:15', NULL),
(265, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Changed status from \'For Verification\' to \'For Verification\' for faculty ID 26 (task ID 2)', '2026-03-23 15:30:32', NULL),
(266, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 26 (task ID 2)', '2026-03-23 15:30:57', NULL),
(267, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 26 (task ID 1)', '2026-03-23 15:31:39', NULL),
(268, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Changed status from \'Verified\' to \'For Verification\' for faculty ID 26 (task ID 1)', '2026-03-23 15:36:54', NULL),
(269, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Changed status from \'For Verification\' to \'For Verification\' for faculty ID 26 (task ID 1)', '2026-03-23 15:41:26', NULL),
(270, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2026-03-23 15:43:07', NULL),
(271, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-23 15:43:39', NULL),
(272, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2026-03-23 15:47:57', NULL),
(273, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-23 15:48:04', NULL),
(274, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2026-03-23 15:48:10', NULL),
(275, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-23 15:48:16', NULL),
(276, 2, 'dean@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2026-03-23 15:48:46', NULL),
(277, 1, 'rmramiso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-23 15:48:54', NULL),
(278, NULL, 'admin@admin.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2026-03-23 23:45:12', NULL),
(279, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-23 23:45:18', NULL),
(280, 26, 'cduyag@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-23 23:47:33', NULL),
(281, 23, 'mhermoso@debesmscat.edu.ph', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-23 23:52:03', NULL),
(282, 23, 'mhermoso@debesmscat.edu.ph', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'User logged out', '2026-03-23 23:52:39', NULL),
(283, 15, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-23 23:52:51', NULL),
(284, 15, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'User logged out', '2026-03-23 23:52:58', NULL),
(285, 4, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-23 23:53:08', NULL),
(286, 4, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'Verified\' to \'For Verification\' for faculty ID 23 (task ID 6)', '2026-03-23 23:54:01', NULL),
(287, 4, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'For Verification\' for faculty ID 23 (task ID 6)', '2026-03-23 23:54:15', NULL),
(288, 4, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 23 (task ID 3)', '2026-03-23 23:56:09', NULL),
(289, 4, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 23 (task ID 6)', '2026-03-23 23:56:42', NULL),
(290, 4, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'User logged out', '2026-03-23 23:58:14', NULL),
(291, 23, 'mhermoso@debesmscat.edu.ph', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-23 23:58:18', NULL),
(292, 4, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-24 00:06:21', NULL),
(293, 4, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 23 (task ID 1)', '2026-03-24 00:07:18', NULL),
(294, 4, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 23 (task ID 24)', '2026-03-24 00:07:23', NULL),
(295, 4, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 23 (task ID 23)', '2026-03-24 00:07:35', NULL),
(296, 4, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 23 (task ID 2)', '2026-03-24 00:08:00', NULL),
(297, 4, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 23 (task ID 22)', '2026-03-24 00:08:11', NULL),
(298, 4, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 23 (task ID 7)', '2026-03-24 00:08:22', NULL),
(299, 4, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 23 (task ID 5)', '2026-03-24 00:08:31', NULL),
(300, 4, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 23 (task ID 4)', '2026-03-24 00:08:38', NULL),
(301, 4, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'Verified\' to \'For Verification\' for faculty ID 23 (task ID 2)', '2026-03-24 00:27:35', NULL),
(302, 4, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 23 (task ID 2)', '2026-03-24 00:31:12', NULL),
(303, 23, 'mhermoso@debesmscat.edu.ph', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-24 00:57:35', NULL),
(304, 4, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-24 01:55:37', NULL),
(305, 15, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-24 01:57:33', NULL),
(306, 15, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'User logged out', '2026-03-24 01:57:39', NULL),
(307, 4, 'rbello@gmail.com', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-24 01:57:46', NULL),
(308, 23, 'mhermoso@debesmscat.edu.ph', '192.168.100.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-24 01:57:59', NULL),
(309, 26, 'cduyag@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', 'SUCCESS', 'Login successful', '2026-03-24 02:02:45', NULL),
(310, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-03-24 02:03:24', NULL),
(311, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-24 02:03:34', NULL),
(312, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-24 02:33:06', NULL),
(313, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-24 02:33:06', NULL),
(314, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-24 02:33:14', NULL),
(315, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 26 (task ID 1)', '2026-03-24 02:33:33', NULL),
(316, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'Verified\' to \'For Verification\' for faculty ID 26 (task ID 1)', '2026-03-24 02:33:57', NULL),
(317, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 26 (task ID 1)', '2026-03-24 02:34:19', NULL),
(318, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'Verified\' to \'For Verification\' for faculty ID 26 (task ID 1)', '2026-03-24 02:37:37', NULL),
(319, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 26 (task ID 1)', '2026-03-24 02:37:42', NULL),
(320, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'Verified\' to \'For Verification\' for faculty ID 26 (task ID 2)', '2026-03-24 02:38:51', NULL),
(321, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 26 (task ID 2)', '2026-03-24 02:39:29', NULL),
(322, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'Verified\' to \'For Verification\' for faculty ID 26 (task ID 2)', '2026-03-24 02:40:34', NULL),
(323, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'Verified\' to \'Verified\' for faculty ID 26 (task ID 1)', '2026-03-24 02:42:20', NULL),
(324, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'Verified\' to \'For Verification\' for faculty ID 26 (task ID 1)', '2026-03-24 02:42:25', NULL),
(325, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 26 (task ID 1)', '2026-03-24 02:42:30', NULL),
(326, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 26 (task ID 2)', '2026-03-24 02:43:46', NULL),
(327, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'Verified\' to \'For Verification\' for faculty ID 23 (task ID 24)', '2026-03-24 02:52:24', NULL),
(328, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'User logged out', '2026-03-24 02:52:37', NULL),
(329, 1, 'rmramiso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-24 02:53:19', NULL),
(330, 26, 'cduyag@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-24 03:13:28', NULL),
(331, 4, 'rbello@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', 'SUCCESS', 'Login successful', '2026-03-24 03:13:40', NULL),
(332, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-03-24 03:24:19', NULL),
(333, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-24 03:24:29', NULL),
(334, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2026-03-24 03:25:50', NULL),
(335, 26, 'cduyag@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-24 03:26:08', NULL),
(336, 4, 'rbello@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-24 03:31:03', NULL),
(337, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-24 03:49:47', NULL),
(338, 23, 'mhermoso@debesmscat.edu.ph', '172.168.10.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-24 03:54:24', NULL),
(339, 26, 'cduyag@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', 'SUCCESS', 'Login successful', '2026-03-24 03:54:28', NULL),
(340, 26, 'cduyag@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', 'SUCCESS', 'User logged out', '2026-03-24 04:02:15', NULL),
(341, 4, 'rbello@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', 'SUCCESS', 'Login successful', '2026-03-24 04:02:27', NULL),
(342, 4, 'rbello@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', 'SUCCESS', 'User logged out', '2026-03-24 04:18:17', NULL),
(343, 15, 'rbello@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', 'SUCCESS', 'Login successful', '2026-03-24 04:18:26', NULL),
(344, 23, 'mhermoso@debesmscat.edu.ph', '172.168.10.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-24 04:18:52', NULL),
(345, 23, 'mhermoso@debesmscat.edu.ph', '172.168.10.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-24 04:18:55', NULL),
(346, 4, 'rbello@gmail.com', '172.168.10.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-24 04:20:11', NULL),
(347, 4, 'rbello@gmail.com', '172.168.10.45', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 23 (task ID 24)', '2026-03-24 04:20:42', NULL),
(348, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2026-03-24 04:20:55', NULL),
(349, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-24 04:21:05', NULL),
(350, 15, 'rbello@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', 'SUCCESS', 'User logged out', '2026-03-24 04:22:21', NULL),
(351, 4, 'rbello@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', 'SUCCESS', 'Login successful', '2026-03-24 04:22:36', NULL),
(352, 1, 'rmramiso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 15 (task ID 1)', '2026-03-24 04:24:22', NULL),
(353, 4, 'rbello@gmail.com', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-24 04:39:35', NULL),
(354, 1, 'rmramiso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-24 04:39:39', NULL),
(355, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-24 04:39:48', NULL),
(356, 1, 'rmramiso@debesmscat.edu.ph', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', 'SUCCESS', 'Login successful', '2026-03-24 04:40:20', NULL),
(357, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-03-24 06:20:26', NULL),
(358, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-25 02:09:56', NULL),
(359, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Changed status from \'For Verification\' to \'Verified\' for faculty ID 2 (task ID 6)', '2026-03-25 02:12:33', NULL),
(360, 26, 'cduyag@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-25 02:13:52', NULL),
(361, 26, 'cduyag@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-30 02:34:02', NULL),
(362, 1, 'admin@gmail.com', '127.0.0.1', 'CLI', 'SUCCESS', 'Login successful', '2026-03-30 02:34:08', NULL),
(363, 1, 'admin@gmail.com', '127.0.0.1', 'Unknown', 'SUCCESS', 'Login successful', '2026-03-30 02:34:23', NULL),
(364, 26, 'cduyag@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2026-03-30 02:34:39', NULL),
(365, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-30 02:34:50', NULL),
(366, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2026-03-30 02:45:16', NULL),
(367, 26, 'cduyag@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-30 02:45:23', NULL),
(368, 26, 'cduyag@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-30 02:49:45', NULL),
(369, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-30 02:50:01', NULL),
(370, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-30 04:34:01', NULL),
(371, NULL, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'FAILED', 'Email not found', '2026-03-30 05:40:58', NULL),
(372, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-30 05:41:00', NULL),
(373, 26, 'cduyag@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-03-30 06:03:37', NULL),
(374, 26, 'cduyag@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-30 06:03:46', NULL),
(375, 26, 'cduyag@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-03-30 06:20:54', NULL),
(376, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-30 08:03:40', NULL),
(377, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-30 08:03:48', NULL),
(378, 26, 'cduyag@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-30 08:10:32', NULL),
(379, 26, 'cduyag@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-30 08:26:32', NULL),
(380, 26, 'cduyag@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-03-30 09:00:51', NULL),
(381, 26, 'cduyag@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-30 09:01:00', NULL),
(382, 26, 'cduyag@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-30 11:56:14', NULL),
(383, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-30 12:02:27', NULL),
(384, 26, 'cduyag@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'User logged out', '2026-03-30 12:03:26', NULL),
(385, 26, 'cduyag@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-30 12:03:31', NULL),
(386, 26, 'cduyag@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'User logged out', '2026-03-30 12:04:38', NULL),
(387, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'FAILED', 'Wrong password', '2026-03-30 12:04:45', NULL),
(388, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'FAILED', 'Wrong password', '2026-03-30 12:05:28', NULL),
(389, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-30 12:05:36', NULL),
(390, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-30 13:10:41', NULL),
(391, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-30 13:10:57', NULL),
(392, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-30 13:50:52', NULL),
(393, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-30 13:51:00', NULL),
(394, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-30 14:30:34', NULL),
(395, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-30 14:30:44', NULL),
(396, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-03-30 14:46:14', NULL),
(397, NULL, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2026-03-30 14:46:24', NULL),
(398, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-30 14:46:27', NULL),
(399, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-03-30 15:33:53', NULL),
(400, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-30 15:34:00', NULL),
(401, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-30 15:34:09', NULL),
(402, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-30 15:50:31', NULL),
(403, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-03-30 16:19:00', NULL),
(404, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-30 16:19:08', NULL),
(405, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'User logged out', '2026-03-30 16:38:04', NULL),
(406, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-30 16:38:15', NULL),
(407, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-30 23:29:51', NULL),
(408, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-30 23:30:25', NULL),
(409, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'User logged out', '2026-03-30 23:30:29', NULL),
(410, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-30 23:30:35', NULL),
(411, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-03-30 23:58:06', NULL),
(412, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-30 23:58:17', NULL),
(413, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-03-31 00:29:17', NULL),
(414, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-31 00:29:24', NULL),
(415, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-03-31 00:59:09', NULL),
(416, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-31 00:59:17', NULL),
(417, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-03-31 02:19:58', NULL),
(418, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'FAILED', 'Wrong password', '2026-03-31 02:20:08', NULL),
(419, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-31 02:20:12', NULL),
(420, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2026-03-31 02:20:27', NULL),
(421, 1, 'rmramiso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-31 02:20:36', NULL),
(422, 1, 'rmramiso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2026-03-31 03:31:49', NULL),
(423, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-31 03:31:57', NULL),
(424, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-31 05:24:33', NULL),
(425, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-31 05:24:42', NULL),
(426, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-31 05:45:03', NULL),
(427, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-31 05:45:25', NULL),
(428, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-31 06:07:32', NULL),
(429, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-31 06:07:44', NULL),
(430, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-03-31 06:51:12', NULL),
(431, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-31 06:51:17', NULL),
(432, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'User logged out', '2026-03-31 09:51:18', NULL),
(433, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-31 09:51:26', NULL),
(434, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2026-03-31 09:51:53', NULL),
(435, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-03-31 09:52:03', NULL),
(436, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'User logged out', '2026-03-31 09:52:05', NULL),
(437, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-03-31 09:52:11', NULL),
(438, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-04-01 16:32:50', NULL),
(439, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-04-02 03:29:30', NULL),
(440, NULL, 'test@test.com', '127.0.0.1', 'Unknown', 'FAILED', 'Email not found', '2026-04-02 03:30:51', NULL),
(441, NULL, 'admin@epes.com', '127.0.0.1', 'Unknown', 'FAILED', 'Email not found', '2026-04-02 03:30:58', NULL),
(442, 1, 'admin@gmail.com', '127.0.0.1', 'Unknown', 'FAILED', 'Wrong password', '2026-04-02 03:31:08', NULL),
(443, 1, 'admin@gmail.com', '127.0.0.1', 'Unknown', 'SUCCESS', 'Login successful', '2026-04-02 03:32:52', NULL),
(444, 1, 'admin@gmail.com', '127.0.0.1', 'Unknown', 'SUCCESS', 'Login successful', '2026-04-02 03:40:38', NULL),
(445, 1, 'admin@gmail.com', '127.0.0.1', 'Unknown', 'SUCCESS', 'Login successful', '2026-04-02 03:40:43', NULL),
(446, 26, 'cduyag@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'FAILED', 'Wrong password', '2026-04-02 03:41:46', NULL),
(447, 26, 'cduyag@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'FAILED', 'Wrong password', '2026-04-02 03:41:49', NULL),
(448, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-04-02 03:41:55', NULL),
(449, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-04-02 03:41:55', NULL);
INSERT INTO `login_audit_trail` (`audit_id`, `user_id`, `username`, `ip_address`, `user_agent`, `login_status`, `failure_reason`, `login_time`, `session_id`) VALUES
(450, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-04-02 03:42:04', NULL),
(451, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-04-02 03:42:48', NULL),
(452, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-04-02 04:45:14', NULL),
(453, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-04-02 04:45:23', NULL),
(454, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-04-02 05:01:02', NULL),
(455, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-04-02 05:01:09', NULL),
(456, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-04-02 07:32:09', NULL),
(457, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-04-02 07:32:21', NULL),
(458, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2026-04-02 07:33:18', NULL),
(459, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-04-02 07:33:23', NULL),
(460, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-04-04 07:20:22', NULL),
(461, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-04-04 07:21:54', NULL),
(462, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-04-04 07:39:43', NULL),
(463, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-04-04 07:39:53', NULL),
(464, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-04-04 07:41:02', NULL),
(465, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-04-04 07:41:10', NULL),
(466, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-04-04 08:09:44', NULL),
(467, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'FAILED', 'Wrong password', '2026-04-04 08:09:54', NULL),
(468, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-04-04 08:09:59', NULL),
(469, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-04-04 08:16:51', NULL),
(470, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-04-04 08:17:01', NULL),
(471, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-04-04 08:58:06', NULL),
(472, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-04-04 08:58:15', NULL),
(473, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Session expired due to inactivity', '2026-04-04 09:18:05', NULL),
(474, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-04-04 09:18:24', NULL),
(475, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-04-04 11:10:23', NULL),
(476, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-04-04 11:34:09', NULL),
(477, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-04-04 11:34:18', NULL),
(478, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-04-04 14:19:04', NULL),
(479, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-04-04 14:19:09', NULL),
(480, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'User logged out', '2026-04-04 15:49:12', NULL),
(481, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-04-04 15:49:20', NULL),
(482, 23, 'mhermoso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'User logged out', '2026-04-04 15:49:31', NULL),
(483, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-04-04 15:53:50', NULL),
(484, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'User logged out', '2026-04-04 15:53:52', NULL),
(485, 1, 'admin@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-04-04 16:01:56', NULL),
(486, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-04-04 16:14:55', NULL),
(487, 4, 'rbello@gmail.com', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'User logged out', '2026-04-04 16:29:04', NULL),
(488, 1, 'rmramiso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'FAILED', 'Wrong password', '2026-04-04 16:30:01', NULL),
(489, 1, 'rmramiso@debesmscat.edu.ph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-04-04 16:30:18', NULL),
(490, 23, 'mhermoso@debesmscat.edu.ph', '192.168.100.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-04-06 01:15:24', NULL),
(491, 23, 'mhermoso@debesmscat.edu.ph', '192.168.100.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'User logged out', '2026-04-06 01:17:25', NULL),
(492, 15, 'rbello@gmail.com', '192.168.100.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-04-06 01:17:36', NULL),
(493, 15, 'rbello@gmail.com', '192.168.100.233', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-04-06 01:29:26', NULL),
(494, 15, 'rbello@gmail.com', '192.168.100.233', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2026-04-06 01:49:08', NULL),
(495, 1, 'admin@gmail.com', '192.168.100.233', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-04-06 01:49:19', NULL),
(496, 15, 'rbello@gmail.com', '192.168.100.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'User logged out', '2026-04-06 01:54:56', NULL),
(497, 15, 'rbello@gmail.com', '192.168.100.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-04-06 01:55:12', NULL),
(498, 15, 'rbello@gmail.com', '192.168.100.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'User logged out', '2026-04-06 01:59:25', NULL),
(499, NULL, 'rramiso@debesmscat.edu.ph', '192.168.100.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'FAILED', 'Email not found', '2026-04-06 02:00:34', NULL),
(500, NULL, 'rramiso@debesmscat.edu.ph', '192.168.100.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'FAILED', 'Email not found', '2026-04-06 02:00:55', NULL),
(501, 1, 'admin@gmail.com', '192.168.100.233', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'User logged out', '2026-04-06 02:01:04', NULL),
(502, 1, 'rmramiso@debesmscat.edu.ph', '192.168.100.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-04-06 02:01:05', NULL),
(503, NULL, 'rmramiso@debesmscat.edu.ph', '192.168.100.233', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'FAILED', 'Email not found', '2026-04-06 02:01:06', NULL),
(504, 1, 'rmramiso@debesmscat.edu.ph', '192.168.100.233', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', 'SUCCESS', 'Login successful', '2026-04-06 02:01:13', NULL),
(505, 1, 'rmramiso@debesmscat.edu.ph', '192.168.100.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Session expired due to inactivity', '2026-04-06 02:41:44', NULL),
(506, NULL, 'rmramiso@debesmscat.edu.ph', '192.168.100.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'FAILED', 'Email not found', '2026-04-06 02:41:49', NULL),
(507, 1, 'rmramiso@debesmscat.edu.ph', '192.168.100.231', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', 'SUCCESS', 'Login successful', '2026-04-06 02:41:55', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `mov_summary`
--

CREATE TABLE `mov_summary` (
  `id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `rating_period` varchar(100) NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `total_movs` int(11) DEFAULT 0,
  `verified_movs` int(11) DEFAULT 0,
  `pending_movs` int(11) DEFAULT 0,
  `rejected_movs` int(11) DEFAULT 0,
  `total_file_size` bigint(20) DEFAULT 0,
  `last_submission` datetime DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mov_summary`
--

INSERT INTO `mov_summary` (`id`, `faculty_id`, `rating_period`, `target_id`, `total_movs`, `verified_movs`, `pending_movs`, `rejected_movs`, `total_file_size`, `last_submission`, `date_created`, `date_updated`) VALUES
(1, 23, '1st Semester 2025-2026', 22, 2, 0, 2, 0, 704416, '2025-10-19 16:40:00', '2026-04-04 16:47:08', '2026-04-04 22:40:53'),
(7, 23, '1st Semester 2025-2026', 2, 2, 0, 2, 0, 2119259, '2025-09-04 16:33:00', '2026-04-04 22:20:45', '2026-04-04 22:34:14'),
(12, 23, '1st Semester 2025-2026', 24, 2, 0, 2, 0, 5077396, '2025-10-14 16:45:00', '2026-04-04 22:44:55', '2026-04-04 22:46:31'),
(14, 23, '1st Semester 2025-2026', 23, 3, 0, 3, 0, 1584389, '2026-04-04 17:25:00', '2026-04-04 22:59:17', '2026-04-04 23:25:58'),
(18, 23, '1st Semester 2025-2026', 14, 1, 0, 1, 0, 4650048, '2026-04-04 17:44:00', '2026-04-04 23:44:26', '2026-04-04 23:44:26'),
(19, 15, '1st Semester 2025-2026', 22, 2, 0, 2, 0, 1353484, '2025-10-07 03:29:00', '2026-04-06 09:22:49', '2026-04-06 09:30:01'),
(25, 15, '1st Semester 2025-2026', 23, 2, 0, 2, 0, 1353484, '2026-04-06 03:43:00', '2026-04-06 09:43:11', '2026-04-06 09:44:04');

-- --------------------------------------------------------

--
-- Table structure for table `mov_uploads`
--

CREATE TABLE `mov_uploads` (
  `id` int(11) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `date_submitted` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Verified','Rejected') DEFAULT 'Pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_date` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `rating_period` varchar(100) DEFAULT NULL,
  `mov_type` varchar(50) DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mov_uploads`
--

INSERT INTO `mov_uploads` (`id`, `faculty_id`, `task_id`, `target_id`, `title`, `description`, `file_path`, `file_type`, `file_name`, `file_size`, `date_submitted`, `status`, `verified_by`, `verified_date`, `remarks`, `rating_period`, `mov_type`, `date_created`, `date_updated`) VALUES
(24, 15, 0, 22, 'AUG 2025', '', 'uploads/mov/e3ac744b40e2650440fde403831852e8', 'docx', '0', 1080704, '2025-09-06 03:29:00', 'Pending', NULL, NULL, NULL, '1st Semester 2025-2026', 'timeliness', '2026-04-06 09:29:30', '2026-04-06 09:29:30'),
(25, 15, 0, 22, 'SEPT 2026', '', 'uploads/mov/1649bf9cc5181768e339339391684025', 'jpg', '0', 272780, '2025-10-07 03:29:00', 'Pending', NULL, NULL, NULL, '1st Semester 2025-2026', 'timeliness', '2026-04-06 09:30:01', '2026-04-06 09:30:01'),
(26, 15, 0, 23, 'Aug 2025', '', 'uploads/mov/1af8cb8bf10b270a0280c540e4e6fa02', 'docx', '0', 1080704, '2026-04-06 03:40:00', 'Pending', NULL, NULL, NULL, '1st Semester 2025-2026', 'efficiency', '2026-04-06 09:43:11', '2026-04-06 09:43:11'),
(27, 15, 0, 23, 'SEP 2025', '', 'uploads/mov/54e4dd4226b9a94a8ffbdcaee22457e6', 'jpg', '0', 272780, '2026-04-06 03:43:00', 'Pending', NULL, NULL, NULL, '1st Semester 2025-2026', 'efficiency', '2026-04-06 09:44:04', '2026-04-06 09:44:04');

-- --------------------------------------------------------

--
-- Table structure for table `percentage_allocation`
--

CREATE TABLE `percentage_allocation` (
  `id` int(11) NOT NULL,
  `position_id` int(11) DEFAULT NULL COMMENT 'Academic rank (NULL = all)',
  `designation_id` int(30) DEFAULT 3 COMMENT 'Designation (NULL = no designation/all)',
  `category` varchar(50) NOT NULL COMMENT 'strategic, core, support',
  `sub_category` varchar(50) DEFAULT NULL COMMENT 'instructions, research, extension (for core)',
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `percentage_allocation`
--

INSERT INTO `percentage_allocation` (`id`, `position_id`, `designation_id`, `category`, `sub_category`, `percentage`, `is_active`, `date_created`) VALUES
(434, 8, 3, 'support', NULL, 10.00, 1, '2026-03-30 13:54:09'),
(436, 9, 3, 'support', NULL, 10.00, 1, '2026-03-30 13:54:09'),
(438, 10, 3, 'support', NULL, 10.00, 1, '2026-03-30 13:54:09'),
(440, 11, 3, 'support', NULL, 10.00, 1, '2026-03-30 13:54:09'),
(442, 12, 3, 'support', NULL, 10.00, 1, '2026-03-30 13:54:09'),
(469, 8, 3, 'core', 'total', 90.00, 1, '2026-03-30 13:56:47'),
(471, 9, 3, 'core', 'total', 90.00, 1, '2026-03-30 13:56:47'),
(473, 10, 3, 'core', 'total', 90.00, 1, '2026-03-30 13:56:47'),
(475, 11, 3, 'core', 'total', 90.00, 1, '2026-03-30 13:56:47'),
(477, 12, 3, 'core', 'total', 90.00, 1, '2026-03-30 13:56:47'),
(533, 8, 3, 'core', 'research', 40.00, 1, '2026-03-30 13:57:16'),
(534, 8, 3, 'core', 'extension', 20.00, 1, '2026-03-30 13:57:16'),
(539, 9, 3, 'core', 'research', 40.00, 1, '2026-03-30 13:57:16'),
(540, 9, 3, 'core', 'extension', 20.00, 1, '2026-03-30 13:57:16'),
(545, 10, 3, 'core', 'research', 40.00, 1, '2026-03-30 13:57:16'),
(546, 10, 3, 'core', 'extension', 20.00, 1, '2026-03-30 13:57:16'),
(551, 11, 3, 'core', 'research', 40.00, 1, '2026-03-30 13:57:16'),
(552, 11, 3, 'core', 'extension', 20.00, 1, '2026-03-30 13:57:16'),
(557, 12, 3, 'core', 'research', 40.00, 1, '2026-03-30 13:57:16'),
(558, 12, 3, 'core', 'extension', 20.00, 1, '2026-03-30 13:57:16'),
(602, 1, 3, 'core', 'total', 90.00, 1, '2026-03-30 14:03:26'),
(603, 1, 3, 'support', NULL, 10.00, 1, '2026-03-30 14:03:26'),
(608, 2, 3, 'core', 'total', 90.00, 1, '2026-03-30 14:03:26'),
(609, 2, 3, 'support', NULL, 10.00, 1, '2026-03-30 14:03:26'),
(614, 3, 3, 'core', 'total', 90.00, 1, '2026-03-30 14:03:26'),
(615, 3, 3, 'support', NULL, 10.00, 1, '2026-03-30 14:03:26'),
(620, 4, 3, 'core', 'total', 90.00, 1, '2026-03-30 14:03:26'),
(621, 4, 3, 'support', NULL, 10.00, 1, '2026-03-30 14:03:26'),
(626, 5, 3, 'core', 'total', 90.00, 1, '2026-03-30 14:03:26'),
(627, 5, 3, 'support', NULL, 10.00, 1, '2026-03-30 14:03:26'),
(632, 6, 3, 'core', 'total', 90.00, 1, '2026-03-30 14:03:26'),
(633, 6, 3, 'support', NULL, 10.00, 1, '2026-03-30 14:03:26'),
(638, 7, 3, 'core', 'total', 90.00, 1, '2026-03-30 14:03:26'),
(639, 7, 3, 'support', NULL, 10.00, 1, '2026-03-30 14:03:26'),
(644, 1, 3, 'core', 'research', 20.00, 1, '2026-03-30 14:03:38'),
(645, 1, 3, 'core', 'extension', 20.00, 1, '2026-03-30 14:03:38'),
(650, 2, 3, 'core', 'research', 20.00, 1, '2026-03-30 14:03:38'),
(651, 2, 3, 'core', 'extension', 20.00, 1, '2026-03-30 14:03:38'),
(656, 3, 3, 'core', 'research', 20.00, 1, '2026-03-30 14:03:38'),
(657, 3, 3, 'core', 'extension', 20.00, 1, '2026-03-30 14:03:38'),
(662, 4, 3, 'core', 'research', 20.00, 1, '2026-03-30 14:03:38'),
(663, 4, 3, 'core', 'extension', 20.00, 1, '2026-03-30 14:03:38'),
(668, 5, 3, 'core', 'research', 20.00, 1, '2026-03-30 14:03:38'),
(669, 5, 3, 'core', 'extension', 20.00, 1, '2026-03-30 14:03:38'),
(674, 6, 3, 'core', 'research', 20.00, 1, '2026-03-30 14:03:38'),
(675, 6, 3, 'core', 'extension', 20.00, 1, '2026-03-30 14:03:38'),
(680, 7, 3, 'core', 'research', 20.00, 1, '2026-03-30 14:03:38'),
(681, 7, 3, 'core', 'extension', 20.00, 1, '2026-03-30 14:03:38'),
(697, 19, 3, 'core', 'total', 90.00, 1, '2026-03-30 14:08:39'),
(698, 19, 3, 'support', NULL, 10.00, 1, '2026-03-30 14:08:39'),
(700, 19, 3, 'core', 'research', 20.00, 1, '2026-03-30 14:08:39'),
(701, 19, 3, 'core', 'extension', 20.00, 1, '2026-03-30 14:08:39'),
(762, 8, 2, 'strategic', NULL, 30.00, 1, '2026-03-30 14:16:02'),
(763, 8, 2, 'core', 'total', 60.00, 1, '2026-03-30 14:16:02'),
(765, 8, 2, 'core', 'research', 40.00, 1, '2026-03-30 14:16:02'),
(766, 8, 2, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:02'),
(767, 8, 2, 'support', NULL, 10.00, 1, '2026-03-30 14:16:02'),
(768, 9, 2, 'strategic', NULL, 30.00, 1, '2026-03-30 14:16:02'),
(769, 9, 2, 'core', 'total', 60.00, 1, '2026-03-30 14:16:02'),
(771, 9, 2, 'core', 'research', 40.00, 1, '2026-03-30 14:16:02'),
(772, 9, 2, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:02'),
(773, 9, 2, 'support', NULL, 10.00, 1, '2026-03-30 14:16:02'),
(774, 10, 2, 'strategic', NULL, 30.00, 1, '2026-03-30 14:16:02'),
(775, 10, 2, 'core', 'total', 60.00, 1, '2026-03-30 14:16:02'),
(777, 10, 2, 'core', 'research', 40.00, 1, '2026-03-30 14:16:02'),
(778, 10, 2, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:02'),
(779, 10, 2, 'support', NULL, 10.00, 1, '2026-03-30 14:16:02'),
(780, 11, 2, 'strategic', NULL, 30.00, 1, '2026-03-30 14:16:02'),
(781, 11, 2, 'core', 'total', 60.00, 1, '2026-03-30 14:16:02'),
(783, 11, 2, 'core', 'research', 40.00, 1, '2026-03-30 14:16:02'),
(784, 11, 2, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:02'),
(785, 11, 2, 'support', NULL, 10.00, 1, '2026-03-30 14:16:02'),
(786, 12, 2, 'strategic', NULL, 30.00, 1, '2026-03-30 14:16:02'),
(787, 12, 2, 'core', 'total', 60.00, 1, '2026-03-30 14:16:02'),
(789, 12, 2, 'core', 'research', 40.00, 1, '2026-03-30 14:16:02'),
(790, 12, 2, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:02'),
(791, 12, 2, 'support', NULL, 10.00, 1, '2026-03-30 14:16:02'),
(793, 13, 3, 'core', 'total', 90.00, 1, '2026-03-30 14:16:21'),
(795, 13, 3, 'core', 'research', 50.00, 1, '2026-03-30 14:16:21'),
(796, 13, 3, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:21'),
(797, 13, 3, 'support', NULL, 10.00, 1, '2026-03-30 14:16:21'),
(799, 14, 3, 'core', 'total', 90.00, 1, '2026-03-30 14:16:21'),
(801, 14, 3, 'core', 'research', 50.00, 1, '2026-03-30 14:16:21'),
(802, 14, 3, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:21'),
(803, 14, 3, 'support', NULL, 10.00, 1, '2026-03-30 14:16:21'),
(805, 15, 3, 'core', 'total', 90.00, 1, '2026-03-30 14:16:21'),
(807, 15, 3, 'core', 'research', 50.00, 1, '2026-03-30 14:16:21'),
(808, 15, 3, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:21'),
(809, 15, 3, 'support', NULL, 10.00, 1, '2026-03-30 14:16:21'),
(811, 16, 3, 'core', 'total', 90.00, 1, '2026-03-30 14:16:21'),
(813, 16, 3, 'core', 'research', 50.00, 1, '2026-03-30 14:16:21'),
(814, 16, 3, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:21'),
(815, 16, 3, 'support', NULL, 10.00, 1, '2026-03-30 14:16:21'),
(817, 17, 3, 'core', 'total', 90.00, 1, '2026-03-30 14:16:21'),
(819, 17, 3, 'core', 'research', 50.00, 1, '2026-03-30 14:16:21'),
(820, 17, 3, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:21'),
(821, 17, 3, 'support', NULL, 10.00, 1, '2026-03-30 14:16:21'),
(823, 18, 3, 'core', 'total', 90.00, 1, '2026-03-30 14:16:21'),
(825, 18, 3, 'core', 'research', 50.00, 1, '2026-03-30 14:16:21'),
(826, 18, 3, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:21'),
(827, 18, 3, 'support', NULL, 10.00, 1, '2026-03-30 14:16:21'),
(828, 13, 2, 'strategic', NULL, 30.00, 1, '2026-03-30 14:16:21'),
(829, 13, 2, 'core', 'total', 60.00, 1, '2026-03-30 14:16:21'),
(831, 13, 2, 'core', 'research', 50.00, 1, '2026-03-30 14:16:21'),
(832, 13, 2, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:21'),
(833, 13, 2, 'support', NULL, 10.00, 1, '2026-03-30 14:16:21'),
(834, 14, 2, 'strategic', NULL, 30.00, 1, '2026-03-30 14:16:21'),
(835, 14, 2, 'core', 'total', 60.00, 1, '2026-03-30 14:16:21'),
(837, 14, 2, 'core', 'research', 50.00, 1, '2026-03-30 14:16:21'),
(838, 14, 2, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:21'),
(839, 14, 2, 'support', NULL, 10.00, 1, '2026-03-30 14:16:21'),
(840, 15, 2, 'strategic', NULL, 30.00, 1, '2026-03-30 14:16:21'),
(841, 15, 2, 'core', 'total', 60.00, 1, '2026-03-30 14:16:21'),
(843, 15, 2, 'core', 'research', 50.00, 1, '2026-03-30 14:16:21'),
(844, 15, 2, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:21'),
(845, 15, 2, 'support', NULL, 10.00, 1, '2026-03-30 14:16:21'),
(846, 16, 2, 'strategic', NULL, 30.00, 1, '2026-03-30 14:16:21'),
(847, 16, 2, 'core', 'total', 60.00, 1, '2026-03-30 14:16:21'),
(849, 16, 2, 'core', 'research', 50.00, 1, '2026-03-30 14:16:21'),
(850, 16, 2, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:21'),
(851, 16, 2, 'support', NULL, 10.00, 1, '2026-03-30 14:16:21'),
(852, 17, 2, 'strategic', NULL, 30.00, 1, '2026-03-30 14:16:21'),
(853, 17, 2, 'core', 'total', 60.00, 1, '2026-03-30 14:16:21'),
(855, 17, 2, 'core', 'research', 50.00, 1, '2026-03-30 14:16:21'),
(856, 17, 2, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:21'),
(857, 17, 2, 'support', NULL, 10.00, 1, '2026-03-30 14:16:21'),
(858, 18, 2, 'strategic', NULL, 30.00, 1, '2026-03-30 14:16:21'),
(859, 18, 2, 'core', 'total', 60.00, 1, '2026-03-30 14:16:21'),
(861, 18, 2, 'core', 'research', 50.00, 1, '2026-03-30 14:16:21'),
(862, 18, 2, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:21'),
(863, 18, 2, 'support', NULL, 10.00, 1, '2026-03-30 14:16:21'),
(865, 1, 2, 'core', 'total', 60.00, 1, '2026-03-30 14:16:44'),
(867, 1, 2, 'core', 'research', 20.00, 1, '2026-03-30 14:16:44'),
(868, 1, 2, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:44'),
(869, 1, 2, 'support', NULL, 10.00, 1, '2026-03-30 14:16:44'),
(871, 2, 2, 'core', 'total', 60.00, 1, '2026-03-30 14:16:44'),
(873, 2, 2, 'core', 'research', 20.00, 1, '2026-03-30 14:16:44'),
(874, 2, 2, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:44'),
(875, 2, 2, 'support', NULL, 10.00, 1, '2026-03-30 14:16:44'),
(877, 3, 2, 'core', 'total', 60.00, 1, '2026-03-30 14:16:44'),
(879, 3, 2, 'core', 'research', 20.00, 1, '2026-03-30 14:16:44'),
(880, 3, 2, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:44'),
(881, 3, 2, 'support', NULL, 10.00, 1, '2026-03-30 14:16:44'),
(883, 4, 2, 'core', 'total', 60.00, 1, '2026-03-30 14:16:44'),
(885, 4, 2, 'core', 'research', 20.00, 1, '2026-03-30 14:16:44'),
(886, 4, 2, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:44'),
(887, 4, 2, 'support', NULL, 10.00, 1, '2026-03-30 14:16:44'),
(889, 5, 2, 'core', 'total', 60.00, 1, '2026-03-30 14:16:44'),
(891, 5, 2, 'core', 'research', 20.00, 1, '2026-03-30 14:16:44'),
(892, 5, 2, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:44'),
(893, 5, 2, 'support', NULL, 10.00, 1, '2026-03-30 14:16:44'),
(895, 6, 2, 'core', 'total', 60.00, 1, '2026-03-30 14:16:44'),
(897, 6, 2, 'core', 'research', 20.00, 1, '2026-03-30 14:16:44'),
(898, 6, 2, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:44'),
(899, 6, 2, 'support', NULL, 10.00, 1, '2026-03-30 14:16:44'),
(901, 7, 2, 'core', 'total', 60.00, 1, '2026-03-30 14:16:44'),
(903, 7, 2, 'core', 'research', 20.00, 1, '2026-03-30 14:16:44'),
(904, 7, 2, 'core', 'extension', 20.00, 1, '2026-03-30 14:16:44'),
(905, 7, 2, 'support', NULL, 10.00, 1, '2026-03-30 14:16:44'),
(906, 8, 3, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(907, 9, 3, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(908, 10, 3, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(909, 11, 3, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(910, 12, 3, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(911, 1, 3, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(912, 2, 3, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(913, 3, 3, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(914, 4, 3, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(915, 5, 3, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(916, 6, 3, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(917, 7, 3, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(918, 19, 3, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(919, 8, 2, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(920, 9, 2, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(921, 10, 2, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(922, 11, 2, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(923, 12, 2, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(924, 13, 3, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(925, 14, 3, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(926, 15, 3, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(927, 16, 3, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(928, 17, 3, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(929, 18, 3, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(930, 13, 2, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(931, 14, 2, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(932, 15, 2, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(933, 16, 2, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(934, 17, 2, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(935, 18, 2, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(936, 1, 2, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(937, 2, 2, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(938, 3, 2, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(939, 4, 2, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(940, 5, 2, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(941, 6, 2, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(942, 7, 2, 'core', 'ter', 50.00, 1, '2026-03-30 14:22:22'),
(969, 8, 3, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(970, 9, 3, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(971, 10, 3, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(972, 11, 3, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(973, 12, 3, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(974, 1, 3, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(975, 2, 3, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(976, 3, 3, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(977, 4, 3, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(978, 5, 3, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(979, 6, 3, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(980, 7, 3, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(981, 19, 3, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(982, 8, 2, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(983, 9, 2, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(984, 10, 2, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(985, 11, 2, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(986, 12, 2, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(987, 13, 3, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(988, 14, 3, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(989, 15, 3, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(990, 16, 3, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(991, 17, 3, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(992, 18, 3, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(993, 13, 2, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(994, 14, 2, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(995, 15, 2, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(996, 16, 2, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(997, 17, 2, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(998, 18, 2, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(999, 1, 2, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(1000, 2, 2, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(1001, 3, 2, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(1002, 4, 2, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(1003, 5, 2, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(1004, 6, 2, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(1005, 7, 2, 'core', 'instruction', 50.00, 1, '2026-03-30 14:22:22'),
(1032, 8, 3, 'core', 'instructions', 40.00, 1, '2026-03-30 14:26:58'),
(1033, 9, 3, 'core', 'instructions', 40.00, 1, '2026-03-30 14:26:58'),
(1034, 10, 3, 'core', 'instructions', 40.00, 1, '2026-03-30 14:26:58'),
(1035, 11, 3, 'core', 'instructions', 40.00, 1, '2026-03-30 14:26:58'),
(1036, 12, 3, 'core', 'instructions', 40.00, 1, '2026-03-30 14:26:58'),
(1037, 1, 3, 'core', 'instructions', 60.00, 1, '2026-03-30 14:26:58'),
(1038, 2, 3, 'core', 'instructions', 60.00, 1, '2026-03-30 14:26:58'),
(1039, 3, 3, 'core', 'instructions', 60.00, 1, '2026-03-30 14:26:58'),
(1040, 4, 3, 'core', 'instructions', 60.00, 1, '2026-03-30 14:26:58'),
(1041, 5, 3, 'core', 'instructions', 60.00, 1, '2026-03-30 14:26:58'),
(1042, 6, 3, 'core', 'instructions', 60.00, 1, '2026-03-30 14:26:58'),
(1043, 7, 3, 'core', 'instructions', 60.00, 1, '2026-03-30 14:26:58'),
(1044, 19, 3, 'core', 'instructions', 100.00, 1, '2026-03-30 14:26:58'),
(1045, 8, 2, 'core', 'instructions', 40.00, 1, '2026-03-30 14:26:58'),
(1046, 9, 2, 'core', 'instructions', 40.00, 1, '2026-03-30 14:26:58'),
(1047, 10, 2, 'core', 'instructions', 40.00, 1, '2026-03-30 14:26:58'),
(1048, 11, 2, 'core', 'instructions', 40.00, 1, '2026-03-30 14:26:58'),
(1049, 12, 2, 'core', 'instructions', 40.00, 1, '2026-03-30 14:26:58'),
(1050, 13, 3, 'core', 'instructions', 30.00, 1, '2026-03-30 14:26:58'),
(1051, 14, 3, 'core', 'instructions', 30.00, 1, '2026-03-30 14:26:58'),
(1052, 15, 3, 'core', 'instructions', 30.00, 1, '2026-03-30 14:26:58'),
(1053, 16, 3, 'core', 'instructions', 30.00, 1, '2026-03-30 14:26:58'),
(1054, 17, 3, 'core', 'instructions', 30.00, 1, '2026-03-30 14:26:58'),
(1055, 18, 3, 'core', 'instructions', 30.00, 1, '2026-03-30 14:26:58'),
(1056, 13, 2, 'core', 'instructions', 30.00, 1, '2026-03-30 14:26:58'),
(1057, 14, 2, 'core', 'instructions', 30.00, 1, '2026-03-30 14:26:58'),
(1058, 15, 2, 'core', 'instructions', 30.00, 1, '2026-03-30 14:26:58'),
(1059, 16, 2, 'core', 'instructions', 30.00, 1, '2026-03-30 14:26:58'),
(1060, 17, 2, 'core', 'instructions', 30.00, 1, '2026-03-30 14:26:58'),
(1061, 18, 2, 'core', 'instructions', 30.00, 1, '2026-03-30 14:26:58'),
(1062, 1, 2, 'core', 'instructions', 60.00, 1, '2026-03-30 14:26:58'),
(1063, 2, 2, 'core', 'instructions', 60.00, 1, '2026-03-30 14:26:58'),
(1064, 3, 2, 'core', 'instructions', 60.00, 1, '2026-03-30 14:26:58'),
(1065, 4, 2, 'core', 'instructions', 60.00, 1, '2026-03-30 14:26:58'),
(1066, 5, 2, 'core', 'instructions', 60.00, 1, '2026-03-30 14:26:58'),
(1067, 6, 2, 'core', 'instructions', 60.00, 1, '2026-03-30 14:26:58'),
(1068, 7, 2, 'core', 'instructions', 60.00, 1, '2026-03-30 14:26:58'),
(1095, 1, 2, 'strategic', NULL, 30.00, 1, '2026-03-30 14:28:35'),
(1096, 2, 2, 'strategic', NULL, 30.00, 1, '2026-03-30 14:28:35'),
(1097, 3, 2, 'strategic', NULL, 30.00, 1, '2026-03-30 14:28:35'),
(1098, 4, 2, 'strategic', NULL, 30.00, 1, '2026-03-30 14:28:35'),
(1099, 5, 2, 'strategic', NULL, 30.00, 1, '2026-03-30 14:28:35'),
(1100, 6, 2, 'strategic', NULL, 30.00, 1, '2026-03-30 14:28:35'),
(1101, 7, 2, 'strategic', NULL, 30.00, 1, '2026-03-30 14:28:35');

-- --------------------------------------------------------

--
-- Table structure for table `position_list`
--

CREATE TABLE `position_list` (
  `id` int(10) NOT NULL,
  `position` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `position_list`
--

INSERT INTO `position_list` (`id`, `position`) VALUES
(1, 'Instructor I'),
(2, 'Instructor II'),
(3, 'Instructor III'),
(4, 'Assistant Professor I'),
(5, 'Assistant Professor II'),
(6, 'Assistant Professor III'),
(7, 'Assistant Professor IV'),
(8, 'Associate Professor I'),
(9, 'Associate Professor II'),
(10, 'Associate Professor III'),
(11, 'Associate Professor IV'),
(12, 'Associate Professor V'),
(13, 'Professor I'),
(14, 'Professor II'),
(15, 'Professor III'),
(16, 'Professor IV'),
(17, 'Professor V'),
(18, 'Professor VI'),
(19, 'Contract of Service');

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(30) NOT NULL,
  `employee_id` int(30) NOT NULL,
  `task_id` int(30) NOT NULL,
  `evaluator_id` int(30) NOT NULL,
  `efficiency` float NOT NULL,
  `timeliness` float NOT NULL,
  `quality` float NOT NULL,
  `remarks` text NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `ratedOn` datetime NOT NULL DEFAULT current_timestamp(),
  `rating_period` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ratings`
--

INSERT INTO `ratings` (`id`, `employee_id`, `task_id`, `evaluator_id`, `efficiency`, `timeliness`, `quality`, `remarks`, `date_created`, `ratedOn`, `rating_period`) VALUES
(2, 1, 1, 1, 5, 4, 5, 'Sample', '2020-12-05 15:18:40', '2025-09-22 17:25:35', ''),
(20, 23, 3, 0, 0, 3, 0, '', '2026-03-23 23:21:02', '2026-03-23 23:21:02', ''),
(21, 26, 3, 0, 0, 5, 0, '', '2026-03-23 23:30:09', '2026-03-23 23:30:09', ''),
(26, 23, 6, 0, 0, 4, 0, '', '2026-03-24 07:56:19', '2026-03-24 07:56:19', ''),
(27, 23, 1, 0, 4.56, 0, 0, '', '2026-03-24 08:07:00', '2026-03-24 08:07:00', ''),
(29, 23, 23, 0, 5, 0, 0, '', '2026-03-24 08:07:31', '2026-03-24 08:07:31', ''),
(31, 23, 22, 0, 0, 5, 0, '', '2026-03-24 08:08:06', '2026-03-24 08:08:06', ''),
(32, 23, 7, 0, 0, 5, 0, '', '2026-03-24 08:08:19', '2026-03-24 08:08:19', ''),
(33, 23, 5, 0, 0, 5, 0, '', '2026-03-24 08:08:29', '2026-03-24 08:08:29', ''),
(34, 23, 4, 0, 0, 5, 0, '', '2026-03-24 08:08:36', '2026-03-24 08:08:36', ''),
(35, 23, 2, 0, 5, 0, 0, '', '2026-03-24 08:28:34', '2026-03-24 08:28:34', ''),
(39, 26, 1, 0, 5, 0, 0, '', '2026-03-24 10:42:27', '2026-03-24 10:42:27', ''),
(40, 26, 2, 0, 5, 0, 0, '', '2026-03-24 10:43:44', '2026-03-24 10:43:44', ''),
(41, 23, 24, 0, 0, 5, 0, '', '2026-03-24 12:17:00', '2026-03-24 12:17:00', ''),
(42, 15, 1, 0, 5, 0, 0, '', '2026-03-24 12:24:19', '2026-03-24 12:24:19', ''),
(43, 2, 6, 0, 0, 5, 0, '', '2026-03-25 10:12:30', '2026-03-25 10:12:30', '');

-- --------------------------------------------------------

--
-- Table structure for table `rating_period`
--

CREATE TABLE `rating_period` (
  `id` int(10) NOT NULL,
  `semester` varchar(30) NOT NULL,
  `year` varchar(30) NOT NULL,
  `code` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rating_period`
--

INSERT INTO `rating_period` (`id`, `semester`, `year`, `code`) VALUES
(1, '1st Semester', '2025-2026', '1-2526');

-- --------------------------------------------------------

--
-- Table structure for table `renewal_recommendations`
--

CREATE TABLE `renewal_recommendations` (
  `id` int(30) NOT NULL,
  `faculty_id` int(30) NOT NULL,
  `evaluator_id` int(30) NOT NULL,
  `rating_period` varchar(100) NOT NULL,
  `overall_score` decimal(5,2) NOT NULL,
  `instruction_ave` decimal(5,2) DEFAULT NULL,
  `support_ave` decimal(5,2) DEFAULT NULL,
  `total_tasks` int(11) NOT NULL DEFAULT 0,
  `verified_tasks` int(11) NOT NULL DEFAULT 0,
  `avg_efficiency` decimal(3,2) DEFAULT NULL,
  `avg_timeliness` decimal(3,2) DEFAULT NULL,
  `avg_quality` decimal(3,2) DEFAULT NULL,
  `recommendation_status` enum('Pending','Recommended','Not Recommended','For Review') DEFAULT 'Pending',
  `system_generated_reason` text NOT NULL,
  `dean_reason` text DEFAULT NULL,
  `dean_decision` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `dean_decision_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `renewal_recommendations`
--

INSERT INTO `renewal_recommendations` (`id`, `faculty_id`, `evaluator_id`, `rating_period`, `overall_score`, `instruction_ave`, `support_ave`, `total_tasks`, `verified_tasks`, `avg_efficiency`, `avg_timeliness`, `avg_quality`, `recommendation_status`, `system_generated_reason`, `dean_reason`, `dean_decision`, `dean_decision_date`, `created_at`, `updated_at`) VALUES
(2, 26, 1, '1-2526', 4.50, 5.00, 0.00, 3, 0, 3.33, 1.67, 0.00, 'Recommended', 'The faculty has demonstrated VERY SATISFACTORY performance with an overall weighted score of 4.50 out of 5.0. For Contract of Service (COS) Faculty: Instruction Average: 5.00 (OUTSTANDING) - Weighted at 90%. Support Function Average: 0.00 (POOR) - Weighted at 10%. A total of 3 rating(s) were submitted for evaluation. Based on the satisfactory performance indicators, this faculty member is RECOMMENDED for contract renewal.', NULL, 'Pending', NULL, '2026-03-24 11:40:16', '2026-03-24 11:40:16'),
(3, 23, 1, '1-2526', 4.53, 4.48, 5.00, 10, 0, 1.46, 3.20, 0.00, 'Recommended', 'The faculty has demonstrated VERY SATISFACTORY performance with an overall weighted score of 4.53 out of 5.0. For Contract of Service (COS) Faculty: Instruction Average: 4.48 (VERY SATISFACTORY) - Weighted at 90%. Support Function Average: 5 (OUTSTANDING) - Weighted at 10%. A total of 10 rating(s) were submitted for evaluation. Based on the satisfactory performance indicators, this faculty member is RECOMMENDED for contract renewal.', NULL, 'Pending', NULL, '2026-03-24 11:40:51', '2026-04-06 10:06:38'),
(4, 22, 1, '1-2526', 0.00, 0.00, 0.00, 0, 0, NULL, NULL, NULL, 'Not Recommended', 'No ratings found for this evaluation period. Faculty has no performance data available for renewal assessment.', NULL, 'Pending', NULL, '2026-03-24 11:41:00', '2026-03-24 11:41:00');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(30) NOT NULL,
  `name` text NOT NULL,
  `email` varchar(200) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `cover_img` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `name`, `email`, `contact`, `address`, `cover_img`) VALUES
(1, 'Faculty Performance Documentation and Evaluation System', '', '+6948 8542 623', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `target_deadlines`
--

CREATE TABLE `target_deadlines` (
  `id` int(11) NOT NULL,
  `target_id` int(11) NOT NULL,
  `deadline` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `target_deadlines`
--

INSERT INTO `target_deadlines` (`id`, `target_id`, `deadline`, `created_at`) VALUES
(1, 22, '2025-09-15', '2026-04-04 14:40:02'),
(2, 22, '2025-10-15', '2026-04-04 14:40:53'),
(3, 24, '2025-10-15', '2026-04-04 14:44:55'),
(4, 24, '2025-10-15', '2026-04-04 14:46:31'),
(5, 22, '2026-09-15', '2026-04-06 01:22:49'),
(6, 22, '2026-10-15', '2026-04-06 01:23:32'),
(7, 22, '2025-09-15', '2026-04-06 01:25:58'),
(8, 22, '2026-10-15', '2026-04-06 01:26:28'),
(9, 22, '2026-09-15', '2026-04-06 01:29:30'),
(10, 22, '2025-10-15', '2026-04-06 01:30:01');

-- --------------------------------------------------------

--
-- Table structure for table `target_exemptions`
--

CREATE TABLE `target_exemptions` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `position_id` int(11) DEFAULT NULL,
  `designation_id` int(11) DEFAULT NULL,
  `sub_category` varchar(50) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `target_exemptions`
--

INSERT INTO `target_exemptions` (`id`, `task_id`, `position_id`, `designation_id`, `sub_category`, `reason`, `created_by`, `date_created`) VALUES
(1, 18, 19, NULL, NULL, '', 1, '2026-03-30 16:39:27'),
(2, 17, 19, NULL, NULL, '', 1, '2026-03-30 16:41:01'),
(4, 16, 19, NULL, NULL, '', 1, '2026-03-30 16:41:34'),
(5, 15, 19, NULL, NULL, '', 1, '2026-03-30 16:41:42'),
(6, 14, 19, NULL, NULL, '', 1, '2026-03-30 16:41:51'),
(7, 13, 19, NULL, NULL, '', 1, '2026-03-30 16:41:58'),
(8, 12, 19, NULL, NULL, NULL, NULL, '2026-03-30 16:51:07'),
(9, 11, 19, NULL, NULL, NULL, NULL, '2026-03-30 23:03:39');

-- --------------------------------------------------------

--
-- Table structure for table `task_list`
--

CREATE TABLE `task_list` (
  `id` int(11) NOT NULL,
  `mfo` int(11) NOT NULL,
  `designation_id` int(30) DEFAULT NULL,
  `academic_rank_id` int(30) DEFAULT NULL,
  `category` varchar(20) DEFAULT NULL,
  `sub_category` varchar(50) DEFAULT NULL,
  `major_output` varchar(255) DEFAULT NULL,
  `success_indicators` text DEFAULT NULL,
  `targets_measures` text DEFAULT NULL,
  `quality` enum('Applicable','Not Applicable') DEFAULT 'Applicable',
  `timeliness` enum('Applicable','Not Applicable') DEFAULT 'Applicable',
  `efficiency` enum('Applicable','Not Applicable') DEFAULT 'Applicable',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_list`
--

INSERT INTO `task_list` (`id`, `mfo`, `designation_id`, `academic_rank_id`, `category`, `sub_category`, `major_output`, `success_indicators`, `targets_measures`, `quality`, `timeliness`, `efficiency`, `is_active`, `created_by`, `date_created`, `date_updated`) VALUES
(1, 1, 0, 0, 'core', 'ter', NULL, 'Teaching Effectiveness  ', 'Obtain a rating in the teaching effectiveness during the rating period', '', '', 'Applicable', 1, NULL, '2025-10-30 21:07:55', '2026-04-02 13:07:17'),
(2, 1, 0, 0, 'core', 'instructions', NULL, 'Attendance to Classes', 'Percentage of classes attended (outside of attendance not covered by deloading or designation must be supported of evidence of make up class or advanced classes)\r\n\r\nEFFICIENCY: \r\n5 - 91% to 100%,\r\n4 - 86% to 90%,\r\n3 - 80%-85%,\r\n2 - 51-79%,\r\n1 -  50 and below\r\n', '', '', 'Applicable', 1, NULL, '2025-10-30 21:10:48', '2026-03-30 16:51:29'),
(3, 1, 0, 0, 'core', 'instructions', NULL, 'Timeliness in submission of Test Questions and Table of Specification ', 'Timeliness:\n5 - 5 working days before the exam,\n4 - 4 working days before the exam,\n3 - 3 working days before the exam,\n2 - 1-2 working days before the exam,\n1 -  on the day of exam\n', '', 'Applicable', '', 1, NULL, '2025-10-30 21:13:06', '2026-04-04 23:27:48'),
(4, 1, 0, 0, 'core', 'instructions', NULL, 'Timeliness of Syllabi Submission ', 'Timeliness:\r\n5 - 1 week after the start of classes,\r\n4  - ,	\r\n3 - 2 weeks after the start of classes,\r\n2 - 3 weeks after the start of classes,\r\n1 - 4 weeks or more days after the start of classes\r\n', '', 'Applicable', '', 1, NULL, '2025-10-30 21:14:17', '2026-03-30 16:50:55'),
(5, 1, 0, 0, 'core', 'instructions', NULL, 'Timeliness of Course Outline Distribution to Students  ', 'Timeliness:\r\n5 - Within the 1st week  of classes,\r\n4 - ,\r\n3 - Within the 2nd week  of classes,\r\n2 - Within the Semester,\r\n1 - None\r\n', '', 'Applicable', '', 1, NULL, '2025-10-30 21:15:08', '2026-03-30 16:50:47'),
(6, 1, 0, 0, 'core', 'instructions', NULL, 'Timeliness in returning of outputs (Midterm Exam only)', 'Timeliness:\r\n5 - Within the semester,\r\n4 - \r\n3 - \r\n2 - After the semester,\r\n1 - None\r\n', '', 'Applicable', '', 1, NULL, '2025-10-30 21:15:57', '2026-03-30 16:50:35'),
(7, 1, 0, 0, 'core', 'instructions', NULL, 'Timeliness of Grade Submission ( 10 WDs after the final exam)', 'Timeliness:\r\n5 - 1-7 days after the Final Exam,\r\n4 - 8-9 days after the Final Exam,\r\n3 - 10 days after the Final Exam,\r\n2 - 11 to 15 days after the final Exam,\r\n1 - 16 days or more\r\n', '', 'Applicable', '', 1, NULL, '2025-10-30 21:16:41', '2026-03-30 16:48:30'),
(11, 2, 0, 0, 'core', 'instructions', NULL, 'Development of  Instructional Materials  every end of the rating period----per commitment only', 'Quality\r\n5	Approved for implementation by the Academic Council\r\n4	Approved by IM Committee\r\n3	Approved by College IM Committee\r\n2	Approved by Department IM Committee\r\n1	Not reviewed\r\n', 'Applicable', '', '', 1, NULL, '2025-10-30 21:31:33', '2026-03-30 16:47:37'),
(12, 3, 0, 0, 'core', 'research', NULL, 'Research Engagement and Productivity ', 'At least 1 completed research by a faculty (or more authors) during the rating period.\r\n\r\nQuality ,\r\n5	Approved completed research\r\n4	Research Performance Reviewed\r\n3	Research implemented\r\n2	Approved Research proposal in the review committee\r\n1	 None submitted\r\n\r\nEFFICIENCY\r\nE	% of CONTRIBUTION\r\n5	60% and above total contribution\r\n4	40-59%\r\n3	30-39%\r\n2	11-29%\r\n1	1-10%\r\n', 'Applicable', '', 'Applicable', 1, NULL, '2025-10-30 21:36:12', '2026-03-30 16:47:25'),
(13, 2, 0, 0, 'core', 'research', NULL, 'Research published in reputable journal', 'At least 1 published research by a faculty during the rating period.\r\n\r\nQuality:\r\n5	Published or accepted in Scopus/WoS/ACI \r\n4	Published or accepted in a university journal \r\n3	With results of peer-review by a reputable journal \r\n2	Submitted and acknowledge\r\n1	 No published research\r\n\r\n', 'Applicable', '', '', 1, NULL, '2025-10-30 21:37:16', '2026-03-30 16:47:15'),
(14, 2, 0, 0, 'core', 'research', NULL, 'Research presented in International and Local Fora', 'At least 1 completed research presented by faculty in the  during the rating period. Note: Approved by the R&D as completed research. Only the presenter will earn points.\r\nQuality \r\n5	Presented International\r\n4	Presented National and Regional\r\n3	Presented Regional\r\n2	Presented in In-House\r\n1	No presentation\r\n', 'Applicable', '', '', 1, NULL, '2025-10-30 21:37:42', '2026-03-30 16:47:00'),
(15, 4, 0, 0, 'core', 'extension', NULL, 'Extension Services Engagement (Research-Based)', 'At least 1 research-based extension service for the rating period. Can be done in group, rating will be the same to all extensionist involved.\r\n\r\nQuality \r\n5	With approved extension terminal report\r\n4	With reviewed extension terminal report\r\n3	Extension Program implemented\r\n2	Approved Extension Proposal\r\n1	None submitted\r\n', 'Applicable', '', '', 1, NULL, '2025-10-30 21:42:20', '2026-03-30 16:46:52'),
(16, 4, 0, 0, 'core', 'extension', NULL, 'Community Outreach Engagement (Technical Advisory Services requested): ', 'At least 1 community outreach for the rating period. Can be done in group, rating will be the same to all extensionist involved\r\nQuality \r\n5	With approved community outreach terminal report\r\n4	With reviewed community outreach terminal report\r\n3	Community Outreach Program implemented\r\n2	Approved community outreach  Proposal\r\n1	None submitted\r\n', 'Applicable', '', '', 1, NULL, '2025-10-30 21:43:18', '2026-03-30 16:46:41'),
(17, 4, 0, 0, 'core', 'extension', NULL, 'Percentage of persons trained weighted by the length of training per program ', '( Percentage = Number clients served/ total number of target)\r\nEfficiency \r\n5	90%-100%\r\n4	80%-89%\r\n3	75%-79%\r\n2	70%-74%\r\n1	69% and below\r\n', '', '', 'Applicable', 1, NULL, '2025-10-30 21:44:00', '2026-03-30 16:46:20'),
(18, 2, 0, 0, 'support', NULL, NULL, 'Timely Submission of duly notarized SALN and PDS deadline is every 31st day of March.', 'Timeliness\r\n5	before the deadline\r\n4	\r\n3	On the deadline\r\n2	Beyond the deadline\r\n1	No Submission\r\n', '', 'Applicable', '', 1, NULL, '2025-10-30 21:44:30', '2026-03-30 16:37:26'),
(22, 0, 0, 0, 'support', NULL, NULL, 'Timely Submission of DTR  with complete attachments ', 'Deadline is every 15th of the ensuing month\nTimeliness\n5	before the deadline\n4	\n3	On the deadline\n2	Beyond the deadline\n1	No Submission\n', '', 'Applicable', '', 1, NULL, '2025-10-30 22:29:04', '2026-04-04 16:59:13'),
(23, 0, 0, 0, 'support', NULL, NULL, 'Percentage of attendance to College Wide Activities', '(Charter Day, Sports events, Year end activity, Council Meetings (applicable to Asst Prof to Prof), Graduation Ceremony\r\nEfficiency\r\n5	100%\r\n4	75% to 99%\r\n3	63% to 74%\r\n2	51% to 62%\r\n1	Below 50%\r\n', '', '', 'Applicable', 1, NULL, '2025-10-30 22:29:28', '2026-03-30 16:33:36'),
(24, 0, 0, 0, 'support', NULL, NULL, 'Timeliness in submission of quarterly accomplishment report', 'Every 15th of January, April, July and October\r\nTimeliness\r\n5	before the deadline\r\n4	\r\n3	On the deadline\r\n2	Beyond the deadline\r\n1	No Submission\r\n', '', 'Applicable', '', 1, NULL, '2025-10-30 22:29:57', '2026-03-30 16:33:27'),
(26, 0, 2, 0, 'strategic', '', NULL, '53% of graduates in the program (2 years prior) are employed', 'EFFICIENCY\r\n5	69% and above\r\n4	61% to 68%\r\n3	 53%-60%\r\n2	 27% to 52.%\r\n1	26% and below\r\n', '', '', 'Applicable', 1, NULL, '2026-03-30 16:54:45', '2026-03-30 16:54:45'),
(27, 0, 2, 0, 'strategic', '', NULL, '100% of students in the program underwent admission and classification process', 'EFFICIENCY\r\n5	100%\r\n4	90-99%\r\n3	80-89%\r\n2	70-79%\r\n1	69% and below\r\n', '', '', 'Applicable', 1, NULL, '2026-03-30 16:55:03', '2026-03-30 16:55:03'),
(28, 0, 2, 0, 'strategic', '', NULL, 'Submitted report on the appraisal /evaluation conducted to assigned faculty of the department  before the start of the next semester', 'EFFICIENCY\r\n5	100% of faculty\r\n4	90-99%\r\n3	80-89%\r\n2	70-79%\r\n1	69% and below\r\n', '', '', 'Applicable', 1, NULL, '2026-03-30 16:55:30', '2026-03-30 16:55:30'),
(29, 0, 2, 0, 'strategic', '', NULL, 'Facilitate the conduct of Student evaluation (within the program)  at the end of academic year', 'QUALITY\r\n5	Student evaluation conducted with complete reports\r\n4	-\r\n3	Student evaluation conducted \r\n2	-\r\n1	None\r\n', 'Applicable', '', '', 1, NULL, '2026-03-30 16:55:54', '2026-03-30 16:55:54'),
(30, 0, 2, 0, 'strategic', '', NULL, 'Conducted at least one per semester Class Monitoring of ALL assigned faculty (Regular and COS) using Class/Faculty Observation form', 'EFFICIENCY\r\n5	100% of faculty for  2 semesters\r\n4	90-99%\r\n3	80-89%\r\n2	70-79%\r\n1	69% and below\r\n', '', '', 'Applicable', 1, NULL, '2026-03-30 16:56:22', '2026-03-30 16:56:22'),
(31, 0, 2, 0, 'strategic', '', NULL, 'Conducted PMC and Submitted at least 3 PMC Report within the rating period', 'EFFICIENCY\r\n5 - 5\r\n4 - 4\r\n3 - 3\r\n2 - 1-2\r\n1 - None\r\n', '', '', 'Applicable', 1, NULL, '2026-03-30 16:57:32', '2026-03-30 16:57:32');

-- --------------------------------------------------------

--
-- Table structure for table `task_progress`
--

CREATE TABLE `task_progress` (
  `id` int(11) NOT NULL,
  `login_type` int(11) NOT NULL DEFAULT 0,
  `task_id` int(30) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `mfo` text NOT NULL,
  `progress` text NOT NULL,
  `is_complete` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=no,1=Yes',
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(50) NOT NULL,
  `file_type` varchar(10) NOT NULL,
  `date_submitted` datetime NOT NULL DEFAULT current_timestamp(),
  `date_verified` datetime DEFAULT NULL,
  `rating_period` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task_progress`
--

INSERT INTO `task_progress` (`id`, `login_type`, `task_id`, `faculty_id`, `mfo`, `progress`, `is_complete`, `date_created`, `file_path`, `file_type`, `date_submitted`, `date_verified`, `rating_period`) VALUES
(20, 0, 6, 2, '', 'Verified', 0, '2025-09-24 15:14:21', 'uploads/file_68d39a4d56a8c8.57583051', 'pdf', '2025-09-24 15:14:21', '2026-03-25 10:12:33', ''),
(42, 0, 3, 23, '', 'Verified', 0, '2026-03-23 15:50:43', 'uploads/9682912c973619a9d083cc24c78a58c9', 'docx', '2026-03-23 15:50:43', '2026-03-24 10:36:06', '1-2526'),
(43, 0, 6, 23, '', 'Verified', 0, '2026-03-23 15:56:01', 'uploads/939b2c7887c78c5ac299daf3f91115f6', 'jpg', '2026-03-23 15:56:01', '2026-03-24 10:36:06', '1-2526'),
(44, 0, 2, 26, '', 'Verified', 0, '2026-03-23 23:24:19', 'uploads/file_69c1da9dd86451.86615635', 'qgz', '2026-03-23 23:24:19', '2026-03-24 10:43:46', '1-2526'),
(47, 0, 1, 26, '', 'Verified', 0, '2026-03-23 23:29:22', 'uploads/578fee9730517e01a18859691304031c', 'pdf', '2026-03-23 23:29:22', '2026-03-24 10:42:30', ''),
(48, 0, 3, 26, '', 'Verified', 0, '2026-03-23 23:29:37', 'uploads/fbd5b99222c86edd8701025259c8285b', 'docx', '2026-03-23 23:29:37', '2026-03-24 10:36:06', ''),
(49, 0, 4, 23, '', 'Verified', 0, '2026-03-24 08:01:56', 'uploads/7a5262fbc26980d4679db4cd51a26045', 'jpeg', '2026-03-24 08:01:56', '2026-03-24 10:36:06', '1-2526'),
(50, 0, 5, 23, '', 'Verified', 0, '2026-03-24 08:02:23', 'uploads/6642f610980a06943b2836867fa401c6', 'png', '2026-03-24 08:02:23', '2026-03-24 10:36:06', '1-2526'),
(51, 0, 7, 23, '', 'Verified', 0, '2026-03-24 08:02:47', 'uploads/d4ff322aa32334b67bb4067d42c19bf3', 'jpg', '2026-03-24 08:02:47', '2026-03-24 10:36:06', '1-2526'),
(52, 0, 22, 23, '', 'Verified', 0, '2026-03-24 08:03:42', 'uploads/b4a702d83374a4ca147fba27c1d67d65', 'pdf', '2026-03-24 08:03:42', '2026-03-24 10:36:06', '1-2526'),
(53, 0, 2, 23, '', 'Verified', 0, '2026-03-24 08:03:54', 'uploads/file_69c1da9dd86451.86615635', 'qgz', '2026-03-24 08:03:54', '2026-03-24 10:36:06', '1-2526'),
(54, 0, 23, 23, '', 'Verified', 0, '2026-03-24 08:04:06', 'uploads/ae3955498dba51ec60d22d35742c221d', 'jpg', '2026-03-24 08:04:06', '2026-03-24 10:36:06', '1-2526'),
(55, 0, 24, 23, '', 'Verified', 0, '2026-03-24 08:04:19', 'uploads/0e4aef03d0e180d041b1d9edb735e4de', 'png', '2026-03-24 08:04:19', '2026-03-24 12:20:42', '1-2526'),
(56, 0, 1, 23, '', 'Verified', 0, '2026-03-24 08:04:43', 'uploads/6295237b14d22a957193dd7c2b292588', 'png', '2026-03-24 08:04:43', '2026-03-24 10:36:06', '1-2526'),
(57, 0, 1, 15, '', 'Verified', 0, '2026-03-24 12:18:36', 'uploads/40195bd03bd350e97bb365dd015d2fd0', 'png', '2026-03-24 12:18:36', '2026-03-24 12:24:22', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(30) NOT NULL,
  `firstname` varchar(200) NOT NULL,
  `lastname` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` text NOT NULL,
  `avatar` text DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(100) NOT NULL,
  `reset_expires` varchar(30) NOT NULL,
  `failed_login` int(11) NOT NULL,
  `isBlocked` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`, `avatar`, `date_created`, `reset_token`, `reset_expires`, `failed_login`, `isBlocked`) VALUES
(1, 'Administrator', '', 'admin@gmail.com', '21232f297a57a5a743894a0e4a801fc3', '1607135820_avatar.jpg', '2020-11-26 10:57:04', '', '', 0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `department_list`
--
ALTER TABLE `department_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `designation_list`
--
ALTER TABLE `designation_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `efficiency_attendance`
--
ALTER TABLE `efficiency_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `faculty_id` (`faculty_id`),
  ADD KEY `target_id` (`target_id`);

--
-- Indexes for table `employee_list`
--
ALTER TABLE `employee_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `evaluator_list`
--
ALTER TABLE `evaluator_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `function_categories`
--
ALTER TABLE `function_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `function_list`
--
ALTER TABLE `function_list`
  ADD PRIMARY KEY (`id`),
  ADD KEY `function_category_id` (`function_category_id`),
  ADD KEY `designation_id` (`designation_id`);

--
-- Indexes for table `login_audit_trail`
--
ALTER TABLE `login_audit_trail`
  ADD PRIMARY KEY (`audit_id`);

--
-- Indexes for table `mov_summary`
--
ALTER TABLE `mov_summary`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `faculty_period_target` (`faculty_id`,`rating_period`,`target_id`),
  ADD KEY `faculty_id` (`faculty_id`),
  ADD KEY `rating_period` (`rating_period`);

--
-- Indexes for table `mov_uploads`
--
ALTER TABLE `mov_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `faculty_id` (`faculty_id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `target_id` (`target_id`),
  ADD KEY `status` (`status`),
  ADD KEY `rating_period` (`rating_period`),
  ADD KEY `idx_date_submitted` (`date_submitted`),
  ADD KEY `idx_faculty_status` (`faculty_id`,`status`);

--
-- Indexes for table `percentage_allocation`
--
ALTER TABLE `percentage_allocation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `position_id` (`position_id`),
  ADD KEY `designation_id` (`designation_id`);

--
-- Indexes for table `position_list`
--
ALTER TABLE `position_list`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rating_period`
--
ALTER TABLE `rating_period`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `renewal_recommendations`
--
ALTER TABLE `renewal_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `faculty_id` (`faculty_id`),
  ADD KEY `evaluator_id` (`evaluator_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `target_deadlines`
--
ALTER TABLE `target_deadlines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `target_id` (`target_id`);

--
-- Indexes for table `target_exemptions`
--
ALTER TABLE `target_exemptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_exemption` (`task_id`,`position_id`,`designation_id`);

--
-- Indexes for table `task_list`
--
ALTER TABLE `task_list`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `task_progress`
--
ALTER TABLE `task_progress`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `department_list`
--
ALTER TABLE `department_list`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `designation_list`
--
ALTER TABLE `designation_list`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `efficiency_attendance`
--
ALTER TABLE `efficiency_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employee_list`
--
ALTER TABLE `employee_list`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `evaluator_list`
--
ALTER TABLE `evaluator_list`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `function_categories`
--
ALTER TABLE `function_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `function_list`
--
ALTER TABLE `function_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `login_audit_trail`
--
ALTER TABLE `login_audit_trail`
  MODIFY `audit_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=508;

--
-- AUTO_INCREMENT for table `mov_summary`
--
ALTER TABLE `mov_summary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `mov_uploads`
--
ALTER TABLE `mov_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `percentage_allocation`
--
ALTER TABLE `percentage_allocation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1102;

--
-- AUTO_INCREMENT for table `position_list`
--
ALTER TABLE `position_list`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `rating_period`
--
ALTER TABLE `rating_period`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `renewal_recommendations`
--
ALTER TABLE `renewal_recommendations`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `target_deadlines`
--
ALTER TABLE `target_deadlines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `target_exemptions`
--
ALTER TABLE `target_exemptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `task_list`
--
ALTER TABLE `task_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `task_progress`
--
ALTER TABLE `task_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(30) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `efficiency_attendance`
--
ALTER TABLE `efficiency_attendance`
  ADD CONSTRAINT `efficiency_attendance_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `employee_list` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `efficiency_attendance_ibfk_2` FOREIGN KEY (`target_id`) REFERENCES `task_list` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `function_list`
--
ALTER TABLE `function_list`
  ADD CONSTRAINT `function_list_cat_fk` FOREIGN KEY (`function_category_id`) REFERENCES `function_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `function_list_desig_fk` FOREIGN KEY (`designation_id`) REFERENCES `designation_list` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `percentage_allocation`
--
ALTER TABLE `percentage_allocation`
  ADD CONSTRAINT `alloc_designation_fk` FOREIGN KEY (`designation_id`) REFERENCES `designation_list` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `alloc_position_fk` FOREIGN KEY (`position_id`) REFERENCES `position_list` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `target_deadlines`
--
ALTER TABLE `target_deadlines`
  ADD CONSTRAINT `target_deadlines_ibfk_1` FOREIGN KEY (`target_id`) REFERENCES `task_list` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_list`
--
ALTER TABLE `task_list`
  ADD CONSTRAINT `task_list_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
