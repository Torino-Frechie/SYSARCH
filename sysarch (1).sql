-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 26, 2026 at 06:23 PM
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
-- Database: `sysarch`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `admin_name` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `admin_name`, `message`, `created_at`) VALUES
(1, 'CCS Admin', 'asasasasa', '2026-04-27 00:15:30');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `id_number` varchar(50) NOT NULL,
  `sitin_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `id_number`, `sitin_id`, `message`, `created_at`) VALUES
(1, '21420450', 5, 'good', '2026-04-27 00:17:07');

-- --------------------------------------------------------

--
-- Table structure for table `lab_config`
--

CREATE TABLE `lab_config` (
  `id` int(11) NOT NULL,
  `lab_name` varchar(50) NOT NULL,
  `total_pcs` int(11) NOT NULL DEFAULT 50,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab_config`
--

INSERT INTO `lab_config` (`id`, `lab_name`, `total_pcs`, `created_at`, `updated_at`) VALUES
(1, '524', 50, '2026-04-26 05:04:56', '2026-04-26 05:04:56'),
(2, '526', 50, '2026-04-26 05:04:56', '2026-04-26 05:23:31'),
(3, '528', 50, '2026-04-26 05:04:56', '2026-04-26 15:46:18'),
(4, '530', 50, '2026-04-26 05:04:56', '2026-04-26 05:23:51'),
(5, '544', 50, '2026-04-26 05:04:56', '2026-04-26 05:04:56');

-- --------------------------------------------------------

--
-- Table structure for table `lab_pcs`
--

CREATE TABLE `lab_pcs` (
  `id` int(11) NOT NULL,
  `lab` varchar(50) NOT NULL,
  `pc_number` int(11) NOT NULL,
  `status` enum('available','unavailable','maintenance') NOT NULL DEFAULT 'available',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_pcs`
--

INSERT INTO `lab_pcs` (`id`, `lab`, `pc_number`, `status`, `updated_at`) VALUES
(1, '524', 1, 'maintenance', '2026-04-26 16:04:25'),
(2, '524', 2, 'available', '2026-04-22 12:26:10'),
(3, '524', 3, 'available', '2026-04-22 12:26:10'),
(4, '524', 4, 'available', '2026-04-22 12:26:10'),
(5, '524', 5, 'available', '2026-04-22 12:26:10'),
(6, '524', 6, 'available', '2026-04-22 12:26:10'),
(7, '524', 7, 'available', '2026-04-22 12:26:10'),
(8, '524', 8, 'available', '2026-04-22 12:26:10'),
(9, '524', 9, 'available', '2026-04-22 12:26:10'),
(10, '524', 10, 'available', '2026-04-22 12:26:10'),
(11, '524', 11, 'available', '2026-04-22 12:26:10'),
(12, '524', 12, 'available', '2026-04-22 12:26:10'),
(13, '524', 13, 'available', '2026-04-22 12:26:10'),
(14, '524', 14, 'available', '2026-04-22 12:26:10'),
(15, '524', 15, 'available', '2026-04-22 12:26:10'),
(16, '524', 16, 'available', '2026-04-22 12:26:10'),
(17, '524', 17, 'available', '2026-04-22 12:26:10'),
(18, '524', 18, 'available', '2026-04-22 12:26:10'),
(19, '524', 19, 'available', '2026-04-22 12:26:10'),
(20, '524', 20, 'available', '2026-04-22 12:26:10'),
(21, '524', 21, 'available', '2026-04-22 12:26:10'),
(22, '524', 22, 'available', '2026-04-22 12:26:10'),
(23, '524', 23, 'available', '2026-04-22 12:26:10'),
(24, '524', 24, 'available', '2026-04-22 12:26:10'),
(25, '524', 25, 'available', '2026-04-22 12:26:10'),
(26, '524', 26, 'available', '2026-04-22 12:26:10'),
(27, '524', 27, 'available', '2026-04-22 12:26:10'),
(28, '524', 28, 'available', '2026-04-22 12:26:10'),
(29, '524', 29, 'available', '2026-04-22 12:26:10'),
(30, '524', 30, 'available', '2026-04-22 12:26:10'),
(31, '524', 31, 'available', '2026-04-22 12:26:10'),
(32, '524', 32, 'available', '2026-04-22 12:26:10'),
(33, '524', 33, 'available', '2026-04-22 12:26:10'),
(34, '524', 34, 'available', '2026-04-22 12:26:10'),
(35, '524', 35, 'available', '2026-04-22 12:26:10'),
(36, '524', 36, 'available', '2026-04-22 12:26:10'),
(37, '524', 37, 'available', '2026-04-22 12:26:10'),
(38, '524', 38, 'available', '2026-04-22 12:26:10'),
(39, '524', 39, 'available', '2026-04-22 12:26:10'),
(40, '524', 40, 'available', '2026-04-22 12:26:10'),
(64, '526', 1, 'available', '2026-04-22 12:26:11'),
(65, '526', 2, 'available', '2026-04-22 12:26:11'),
(66, '526', 3, 'available', '2026-04-22 12:26:11'),
(67, '526', 4, 'available', '2026-04-22 12:26:11'),
(68, '526', 5, 'available', '2026-04-22 12:26:11'),
(69, '526', 6, 'available', '2026-04-22 12:26:11'),
(70, '526', 7, 'available', '2026-04-22 12:26:11'),
(71, '526', 8, 'available', '2026-04-22 12:26:11'),
(72, '526', 9, 'available', '2026-04-22 12:26:11'),
(73, '526', 10, 'available', '2026-04-22 12:26:11'),
(74, '526', 11, 'available', '2026-04-22 12:26:11'),
(75, '526', 12, 'available', '2026-04-22 12:26:11'),
(76, '526', 13, 'available', '2026-04-22 12:26:11'),
(77, '526', 14, 'available', '2026-04-22 12:26:11'),
(78, '526', 15, 'available', '2026-04-22 12:26:11'),
(79, '526', 16, 'available', '2026-04-22 12:26:11'),
(80, '526', 17, 'available', '2026-04-22 12:26:11'),
(81, '526', 18, 'available', '2026-04-22 12:26:11'),
(82, '526', 19, 'available', '2026-04-22 12:26:11'),
(83, '526', 20, 'available', '2026-04-22 12:26:11'),
(84, '526', 21, 'available', '2026-04-22 12:26:11'),
(85, '526', 22, 'available', '2026-04-22 12:26:11'),
(86, '526', 23, 'available', '2026-04-22 12:26:11'),
(87, '526', 24, 'available', '2026-04-22 12:26:11'),
(88, '526', 25, 'available', '2026-04-22 12:26:11'),
(89, '526', 26, 'available', '2026-04-22 12:26:11'),
(90, '526', 27, 'available', '2026-04-22 12:26:11'),
(91, '526', 28, 'available', '2026-04-22 12:26:11'),
(92, '526', 29, 'available', '2026-04-22 12:26:11'),
(93, '526', 30, 'available', '2026-04-22 12:26:11'),
(94, '526', 31, 'available', '2026-04-22 12:26:11'),
(95, '526', 32, 'available', '2026-04-22 12:26:11'),
(96, '526', 33, 'available', '2026-04-22 12:26:11'),
(97, '526', 34, 'available', '2026-04-22 12:26:11'),
(98, '526', 35, 'available', '2026-04-22 12:26:11'),
(99, '526', 36, 'available', '2026-04-22 12:26:11'),
(100, '526', 37, 'available', '2026-04-22 12:26:11'),
(101, '526', 38, 'available', '2026-04-22 12:26:11'),
(102, '526', 39, 'available', '2026-04-22 12:26:11'),
(103, '526', 40, 'available', '2026-04-22 12:26:11'),
(127, '528', 1, 'available', '2026-04-22 12:26:11'),
(128, '528', 2, 'available', '2026-04-22 12:26:11'),
(129, '528', 3, 'available', '2026-04-22 12:26:11'),
(130, '528', 4, 'available', '2026-04-22 12:26:11'),
(131, '528', 5, 'available', '2026-04-22 12:26:11'),
(132, '528', 6, 'available', '2026-04-22 12:26:11'),
(133, '528', 7, 'available', '2026-04-22 12:26:11'),
(134, '528', 8, 'available', '2026-04-22 12:26:11'),
(135, '528', 9, 'available', '2026-04-22 12:26:11'),
(136, '528', 10, 'available', '2026-04-22 12:26:11'),
(137, '528', 11, 'available', '2026-04-22 12:26:11'),
(138, '528', 12, 'available', '2026-04-22 12:26:11'),
(139, '528', 13, 'available', '2026-04-22 12:26:11'),
(140, '528', 14, 'available', '2026-04-22 12:26:11'),
(141, '528', 15, 'available', '2026-04-22 12:26:11'),
(142, '528', 16, 'available', '2026-04-22 12:26:11'),
(143, '528', 17, 'available', '2026-04-22 12:26:11'),
(144, '528', 18, 'available', '2026-04-22 12:26:11'),
(145, '528', 19, 'available', '2026-04-22 12:26:11'),
(146, '528', 20, 'available', '2026-04-22 12:26:11'),
(147, '528', 21, 'available', '2026-04-22 12:26:11'),
(148, '528', 22, 'available', '2026-04-22 12:26:11'),
(149, '528', 23, 'available', '2026-04-22 12:26:11'),
(150, '528', 24, 'available', '2026-04-22 12:26:11'),
(151, '528', 25, 'available', '2026-04-22 12:26:11'),
(152, '528', 26, 'available', '2026-04-22 12:26:11'),
(153, '528', 27, 'available', '2026-04-22 12:26:11'),
(154, '528', 28, 'available', '2026-04-22 12:26:11'),
(155, '528', 29, 'available', '2026-04-22 12:26:11'),
(156, '528', 30, 'available', '2026-04-22 12:26:11'),
(157, '528', 31, 'available', '2026-04-22 12:26:11'),
(158, '528', 32, 'available', '2026-04-22 12:26:11'),
(159, '528', 33, 'available', '2026-04-22 12:26:11'),
(160, '528', 34, 'available', '2026-04-22 12:26:11'),
(161, '528', 35, 'available', '2026-04-22 12:26:11'),
(162, '528', 36, 'available', '2026-04-22 12:26:11'),
(163, '528', 37, 'available', '2026-04-22 12:26:11'),
(164, '528', 38, 'available', '2026-04-22 12:26:11'),
(165, '528', 39, 'available', '2026-04-22 12:26:11'),
(166, '528', 40, 'available', '2026-04-22 12:26:11'),
(190, '530', 1, 'available', '2026-04-22 12:26:11'),
(191, '530', 2, 'available', '2026-04-22 12:26:11'),
(192, '530', 3, 'available', '2026-04-22 12:26:11'),
(193, '530', 4, 'available', '2026-04-22 12:26:11'),
(194, '530', 5, 'available', '2026-04-22 12:26:11'),
(195, '530', 6, 'available', '2026-04-22 12:26:11'),
(196, '530', 7, 'available', '2026-04-22 12:26:11'),
(197, '530', 8, 'available', '2026-04-22 12:26:11'),
(198, '530', 9, 'available', '2026-04-22 12:26:11'),
(199, '530', 10, 'available', '2026-04-22 12:26:11'),
(200, '530', 11, 'available', '2026-04-22 12:26:11'),
(201, '530', 12, 'available', '2026-04-22 12:26:11'),
(202, '530', 13, 'available', '2026-04-22 12:26:11'),
(203, '530', 14, 'available', '2026-04-22 12:26:11'),
(204, '530', 15, 'available', '2026-04-22 12:26:11'),
(205, '530', 16, 'available', '2026-04-22 12:26:11'),
(206, '530', 17, 'available', '2026-04-22 12:26:11'),
(207, '530', 18, 'available', '2026-04-22 12:26:11'),
(208, '530', 19, 'available', '2026-04-22 12:26:11'),
(209, '530', 20, 'available', '2026-04-22 12:26:11'),
(210, '530', 21, 'available', '2026-04-22 12:26:11'),
(211, '530', 22, 'available', '2026-04-22 12:26:11'),
(212, '530', 23, 'available', '2026-04-22 12:26:11'),
(213, '530', 24, 'available', '2026-04-22 12:26:11'),
(214, '530', 25, 'available', '2026-04-22 12:26:11'),
(215, '530', 26, 'available', '2026-04-22 12:26:11'),
(216, '530', 27, 'available', '2026-04-22 12:26:11'),
(217, '530', 28, 'available', '2026-04-22 12:26:11'),
(218, '530', 29, 'available', '2026-04-22 12:26:11'),
(219, '530', 30, 'available', '2026-04-22 12:26:11'),
(220, '530', 31, 'available', '2026-04-22 12:26:11'),
(221, '530', 32, 'available', '2026-04-22 12:26:11'),
(222, '530', 33, 'available', '2026-04-22 12:26:11'),
(223, '530', 34, 'available', '2026-04-22 12:26:11'),
(224, '530', 35, 'available', '2026-04-22 12:26:11'),
(225, '530', 36, 'available', '2026-04-22 12:26:11'),
(226, '530', 37, 'available', '2026-04-22 12:26:11'),
(227, '530', 38, 'available', '2026-04-22 12:26:11'),
(228, '530', 39, 'available', '2026-04-22 12:26:11'),
(229, '530', 40, 'available', '2026-04-22 12:26:11'),
(253, '544', 1, 'available', '2026-04-22 12:26:11'),
(254, '544', 2, 'available', '2026-04-22 12:26:11'),
(255, '544', 3, 'available', '2026-04-22 12:26:11'),
(256, '544', 4, 'available', '2026-04-22 12:26:11'),
(257, '544', 5, 'available', '2026-04-22 12:26:11'),
(258, '544', 6, 'available', '2026-04-22 12:26:11'),
(259, '544', 7, 'available', '2026-04-22 12:26:11'),
(260, '544', 8, 'available', '2026-04-22 12:26:11'),
(261, '544', 9, 'available', '2026-04-22 12:26:11'),
(262, '544', 10, 'available', '2026-04-22 12:26:11'),
(263, '544', 11, 'available', '2026-04-22 12:26:11'),
(264, '544', 12, 'available', '2026-04-22 12:26:11'),
(265, '544', 13, 'available', '2026-04-22 12:26:11'),
(266, '544', 14, 'available', '2026-04-22 12:26:11'),
(267, '544', 15, 'available', '2026-04-22 12:26:11'),
(268, '544', 16, 'available', '2026-04-22 12:26:11'),
(269, '544', 17, 'available', '2026-04-22 12:26:11'),
(270, '544', 18, 'available', '2026-04-22 12:26:11'),
(271, '544', 19, 'available', '2026-04-22 12:26:11'),
(272, '544', 20, 'available', '2026-04-22 12:26:11'),
(273, '544', 21, 'available', '2026-04-22 12:26:11'),
(274, '544', 22, 'available', '2026-04-22 12:26:11'),
(275, '544', 23, 'available', '2026-04-22 12:26:11'),
(276, '544', 24, 'available', '2026-04-22 12:26:11'),
(277, '544', 25, 'available', '2026-04-22 12:26:11'),
(278, '544', 26, 'available', '2026-04-22 12:26:11'),
(279, '544', 27, 'available', '2026-04-22 12:26:11'),
(280, '544', 28, 'available', '2026-04-22 12:26:11'),
(281, '544', 29, 'available', '2026-04-22 12:26:11'),
(282, '544', 30, 'available', '2026-04-22 12:26:11'),
(283, '544', 31, 'available', '2026-04-22 12:26:11'),
(284, '544', 32, 'available', '2026-04-22 12:26:11'),
(285, '544', 33, 'available', '2026-04-22 12:26:11'),
(286, '544', 34, 'available', '2026-04-22 12:26:11'),
(287, '544', 35, 'available', '2026-04-22 12:26:11'),
(288, '544', 36, 'available', '2026-04-22 12:26:11'),
(289, '544', 37, 'available', '2026-04-22 12:26:11'),
(290, '544', 38, 'available', '2026-04-22 12:26:11'),
(291, '544', 39, 'available', '2026-04-22 12:26:11'),
(292, '544', 40, 'available', '2026-04-22 12:26:11'),
(316, 'Mac Lab', 1, 'available', '2026-04-22 12:26:11'),
(317, 'Mac Lab', 2, 'available', '2026-04-22 12:26:11'),
(318, 'Mac Lab', 3, 'available', '2026-04-22 12:26:11'),
(319, 'Mac Lab', 4, 'available', '2026-04-22 12:26:11'),
(320, 'Mac Lab', 5, 'available', '2026-04-22 12:26:11'),
(321, 'Mac Lab', 6, 'available', '2026-04-22 12:26:11'),
(322, 'Mac Lab', 7, 'available', '2026-04-22 12:26:11'),
(323, 'Mac Lab', 8, 'available', '2026-04-22 12:26:11'),
(324, 'Mac Lab', 9, 'available', '2026-04-22 12:26:11'),
(325, 'Mac Lab', 10, 'available', '2026-04-22 12:26:11'),
(326, 'Mac Lab', 11, 'available', '2026-04-22 12:26:11'),
(327, 'Mac Lab', 12, 'available', '2026-04-22 12:26:11'),
(328, 'Mac Lab', 13, 'available', '2026-04-22 12:26:11'),
(329, 'Mac Lab', 14, 'available', '2026-04-22 12:26:11'),
(330, 'Mac Lab', 15, 'available', '2026-04-22 12:26:11'),
(331, 'Mac Lab', 16, 'available', '2026-04-22 12:26:11'),
(332, 'Mac Lab', 17, 'available', '2026-04-22 12:26:11'),
(333, 'Mac Lab', 18, 'available', '2026-04-22 12:26:11'),
(334, 'Mac Lab', 19, 'available', '2026-04-22 12:26:11'),
(335, 'Mac Lab', 20, 'available', '2026-04-22 12:26:11'),
(336, 'Mac Lab', 21, 'available', '2026-04-22 12:26:11'),
(337, 'Mac Lab', 22, 'available', '2026-04-22 12:26:11'),
(338, 'Mac Lab', 23, 'available', '2026-04-22 12:26:11'),
(339, 'Mac Lab', 24, 'available', '2026-04-22 12:26:11');

-- --------------------------------------------------------

--
-- Table structure for table `pc_status`
--

CREATE TABLE `pc_status` (
  `id` int(11) NOT NULL,
  `lab_name` varchar(50) NOT NULL,
  `pc_number` int(11) NOT NULL,
  `status` enum('available','maintenance','not_available','in_use') DEFAULT 'available',
  `updated_by` varchar(50) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pc_status`
--

INSERT INTO `pc_status` (`id`, `lab_name`, `pc_number`, `status`, `updated_by`, `last_updated`, `notes`) VALUES
(662, '524', 1, 'available', 'Admin', '2026-04-26 16:19:25', ''),
(663, '524', 2, 'available', 'Admin', '2026-04-26 16:19:20', ''),
(664, '524', 3, 'available', 'Admin', '2026-04-26 16:19:16', 'Used by 21420450'),
(665, '524', 4, 'available', 'Admin', '2026-04-26 16:19:12', 'Used by 21420450'),
(666, '524', 5, 'available', NULL, '2026-04-26 16:16:02', ''),
(667, '524', 6, 'available', NULL, '2026-04-26 16:03:52', NULL),
(668, '524', 7, 'available', NULL, '2026-04-26 16:03:52', NULL),
(669, '524', 8, 'available', NULL, '2026-04-26 16:03:52', NULL),
(670, '524', 9, 'available', NULL, '2026-04-26 16:03:52', NULL),
(671, '524', 10, 'available', NULL, '2026-04-26 16:03:52', NULL),
(672, '524', 11, 'available', NULL, '2026-04-26 16:03:52', NULL),
(673, '524', 12, 'available', NULL, '2026-04-26 16:03:52', NULL),
(674, '524', 13, 'available', NULL, '2026-04-26 16:03:52', NULL),
(675, '524', 14, 'available', NULL, '2026-04-26 16:03:52', NULL),
(676, '524', 15, 'available', NULL, '2026-04-26 16:03:52', NULL),
(677, '524', 16, 'available', NULL, '2026-04-26 16:03:52', NULL),
(678, '524', 17, 'available', NULL, '2026-04-26 16:03:52', NULL),
(679, '524', 18, 'available', NULL, '2026-04-26 16:03:52', NULL),
(680, '524', 19, 'available', NULL, '2026-04-26 16:03:52', NULL),
(681, '524', 20, 'available', NULL, '2026-04-26 16:03:52', NULL),
(682, '524', 21, 'available', NULL, '2026-04-26 16:03:52', NULL),
(683, '524', 22, 'available', NULL, '2026-04-26 16:03:52', NULL),
(684, '524', 23, 'available', NULL, '2026-04-26 16:03:52', NULL),
(685, '524', 24, 'available', NULL, '2026-04-26 16:03:52', NULL),
(686, '524', 25, 'available', NULL, '2026-04-26 16:03:52', NULL),
(687, '524', 26, 'available', NULL, '2026-04-26 16:03:52', NULL),
(688, '524', 27, 'available', NULL, '2026-04-26 16:03:52', NULL),
(689, '524', 28, 'available', NULL, '2026-04-26 16:03:52', NULL),
(690, '524', 29, 'available', NULL, '2026-04-26 16:03:52', NULL),
(691, '524', 30, 'available', NULL, '2026-04-26 16:03:52', NULL),
(692, '524', 31, 'available', NULL, '2026-04-26 16:03:52', NULL),
(693, '524', 32, 'available', NULL, '2026-04-26 16:03:52', NULL),
(694, '524', 33, 'available', NULL, '2026-04-26 16:03:52', NULL),
(695, '524', 34, 'available', NULL, '2026-04-26 16:03:52', NULL),
(696, '524', 35, 'available', NULL, '2026-04-26 16:03:52', NULL),
(697, '524', 36, 'available', NULL, '2026-04-26 16:03:52', NULL),
(698, '524', 37, 'available', NULL, '2026-04-26 16:03:52', NULL),
(699, '524', 38, 'available', NULL, '2026-04-26 16:03:52', NULL),
(700, '524', 39, 'available', NULL, '2026-04-26 16:03:52', NULL),
(701, '524', 40, 'available', NULL, '2026-04-26 16:03:52', NULL),
(702, '526', 1, 'available', NULL, '2026-04-26 16:03:52', NULL),
(703, '526', 2, 'available', NULL, '2026-04-26 16:03:52', NULL),
(704, '526', 3, 'available', NULL, '2026-04-26 16:03:52', NULL),
(705, '526', 4, 'available', NULL, '2026-04-26 16:03:52', NULL),
(706, '526', 5, 'available', NULL, '2026-04-26 16:03:52', NULL),
(707, '526', 6, 'available', NULL, '2026-04-26 16:03:52', NULL),
(708, '526', 7, 'available', NULL, '2026-04-26 16:03:52', NULL),
(709, '526', 8, 'available', NULL, '2026-04-26 16:03:52', NULL),
(710, '526', 9, 'available', NULL, '2026-04-26 16:03:52', NULL),
(711, '526', 10, 'available', NULL, '2026-04-26 16:03:52', NULL),
(712, '526', 11, 'available', NULL, '2026-04-26 16:03:52', NULL),
(713, '526', 12, 'available', NULL, '2026-04-26 16:03:52', NULL),
(714, '526', 13, 'available', NULL, '2026-04-26 16:03:52', NULL),
(715, '526', 14, 'available', NULL, '2026-04-26 16:03:52', NULL),
(716, '526', 15, 'available', NULL, '2026-04-26 16:03:52', NULL),
(717, '526', 16, 'available', NULL, '2026-04-26 16:03:52', NULL),
(718, '526', 17, 'available', NULL, '2026-04-26 16:03:52', NULL),
(719, '526', 18, 'available', NULL, '2026-04-26 16:03:52', NULL),
(720, '526', 19, 'available', NULL, '2026-04-26 16:03:52', NULL),
(721, '526', 20, 'available', NULL, '2026-04-26 16:03:52', NULL),
(722, '526', 21, 'available', NULL, '2026-04-26 16:03:52', NULL),
(723, '526', 22, 'available', NULL, '2026-04-26 16:03:52', NULL),
(724, '526', 23, 'available', NULL, '2026-04-26 16:03:52', NULL),
(725, '526', 24, 'available', NULL, '2026-04-26 16:03:52', NULL),
(726, '526', 25, 'available', NULL, '2026-04-26 16:03:52', NULL),
(727, '526', 26, 'available', NULL, '2026-04-26 16:03:52', NULL),
(728, '526', 27, 'available', NULL, '2026-04-26 16:03:52', NULL),
(729, '526', 28, 'available', NULL, '2026-04-26 16:03:52', NULL),
(730, '526', 29, 'available', NULL, '2026-04-26 16:03:52', NULL),
(731, '526', 30, 'available', NULL, '2026-04-26 16:03:52', NULL),
(732, '526', 31, 'available', NULL, '2026-04-26 16:03:52', NULL),
(733, '526', 32, 'available', NULL, '2026-04-26 16:03:52', NULL),
(734, '526', 33, 'available', NULL, '2026-04-26 16:03:52', NULL),
(735, '526', 34, 'available', NULL, '2026-04-26 16:03:52', NULL),
(736, '526', 35, 'available', NULL, '2026-04-26 16:03:52', NULL),
(737, '526', 36, 'available', NULL, '2026-04-26 16:03:52', NULL),
(738, '526', 37, 'available', NULL, '2026-04-26 16:03:52', NULL),
(739, '526', 38, 'available', NULL, '2026-04-26 16:03:52', NULL),
(740, '526', 39, 'available', NULL, '2026-04-26 16:03:52', NULL),
(741, '526', 40, 'available', NULL, '2026-04-26 16:03:52', NULL),
(742, '528', 1, 'available', NULL, '2026-04-26 16:03:52', NULL),
(743, '528', 2, 'available', NULL, '2026-04-26 16:03:52', NULL),
(744, '528', 3, 'available', NULL, '2026-04-26 16:03:52', NULL),
(745, '528', 4, 'available', NULL, '2026-04-26 16:03:52', NULL),
(746, '528', 5, 'available', NULL, '2026-04-26 16:03:52', NULL),
(747, '528', 6, 'available', NULL, '2026-04-26 16:03:52', NULL),
(748, '528', 7, 'available', NULL, '2026-04-26 16:03:52', NULL),
(749, '528', 8, 'available', NULL, '2026-04-26 16:03:52', NULL),
(750, '528', 9, 'available', NULL, '2026-04-26 16:03:52', NULL),
(751, '528', 10, 'available', NULL, '2026-04-26 16:03:52', NULL),
(752, '528', 11, 'available', NULL, '2026-04-26 16:03:52', NULL),
(753, '528', 12, 'available', NULL, '2026-04-26 16:03:52', NULL),
(754, '528', 13, 'available', NULL, '2026-04-26 16:03:52', NULL),
(755, '528', 14, 'available', NULL, '2026-04-26 16:03:52', NULL),
(756, '528', 15, 'available', NULL, '2026-04-26 16:03:52', NULL),
(757, '528', 16, 'available', NULL, '2026-04-26 16:03:52', NULL),
(758, '528', 17, 'available', NULL, '2026-04-26 16:03:52', NULL),
(759, '528', 18, 'available', NULL, '2026-04-26 16:03:52', NULL),
(760, '528', 19, 'available', NULL, '2026-04-26 16:03:52', NULL),
(761, '528', 20, 'available', NULL, '2026-04-26 16:03:52', NULL),
(762, '528', 21, 'available', NULL, '2026-04-26 16:03:52', NULL),
(763, '528', 22, 'available', NULL, '2026-04-26 16:03:52', NULL),
(764, '528', 23, 'available', NULL, '2026-04-26 16:03:52', NULL),
(765, '528', 24, 'available', NULL, '2026-04-26 16:03:52', NULL),
(766, '528', 25, 'available', NULL, '2026-04-26 16:03:52', NULL),
(767, '528', 26, 'available', NULL, '2026-04-26 16:03:52', NULL),
(768, '528', 27, 'available', NULL, '2026-04-26 16:03:52', NULL),
(769, '528', 28, 'available', NULL, '2026-04-26 16:03:52', NULL),
(770, '528', 29, 'available', NULL, '2026-04-26 16:03:52', NULL),
(771, '528', 30, 'available', NULL, '2026-04-26 16:03:52', NULL),
(772, '528', 31, 'available', NULL, '2026-04-26 16:03:52', NULL),
(773, '528', 32, 'available', NULL, '2026-04-26 16:03:52', NULL),
(774, '528', 33, 'available', NULL, '2026-04-26 16:03:52', NULL),
(775, '528', 34, 'available', NULL, '2026-04-26 16:03:52', NULL),
(776, '528', 35, 'available', NULL, '2026-04-26 16:03:52', NULL),
(777, '528', 36, 'available', NULL, '2026-04-26 16:03:52', NULL),
(778, '528', 37, 'available', NULL, '2026-04-26 16:03:52', NULL),
(779, '528', 38, 'available', NULL, '2026-04-26 16:03:52', NULL),
(780, '528', 39, 'available', NULL, '2026-04-26 16:03:52', NULL),
(781, '528', 40, 'available', NULL, '2026-04-26 16:03:52', NULL),
(782, '530', 1, 'available', NULL, '2026-04-26 16:03:52', NULL),
(783, '530', 2, 'available', NULL, '2026-04-26 16:03:52', NULL),
(784, '530', 3, 'available', NULL, '2026-04-26 16:03:52', NULL),
(785, '530', 4, 'available', NULL, '2026-04-26 16:03:52', NULL),
(786, '530', 5, 'available', NULL, '2026-04-26 16:03:52', NULL),
(787, '530', 6, 'available', NULL, '2026-04-26 16:03:52', NULL),
(788, '530', 7, 'available', NULL, '2026-04-26 16:03:52', NULL),
(789, '530', 8, 'available', NULL, '2026-04-26 16:03:52', NULL),
(790, '530', 9, 'available', NULL, '2026-04-26 16:03:52', NULL),
(791, '530', 10, 'available', NULL, '2026-04-26 16:03:52', NULL),
(792, '530', 11, 'available', NULL, '2026-04-26 16:03:52', NULL),
(793, '530', 12, 'available', NULL, '2026-04-26 16:03:52', NULL),
(794, '530', 13, 'available', NULL, '2026-04-26 16:03:52', NULL),
(795, '530', 14, 'available', NULL, '2026-04-26 16:03:52', NULL),
(796, '530', 15, 'available', NULL, '2026-04-26 16:03:52', NULL),
(797, '530', 16, 'available', NULL, '2026-04-26 16:03:52', NULL),
(798, '530', 17, 'available', NULL, '2026-04-26 16:03:52', NULL),
(799, '530', 18, 'available', NULL, '2026-04-26 16:03:52', NULL),
(800, '530', 19, 'available', NULL, '2026-04-26 16:03:52', NULL),
(801, '530', 20, 'available', NULL, '2026-04-26 16:03:52', NULL),
(802, '530', 21, 'available', NULL, '2026-04-26 16:03:52', NULL),
(803, '530', 22, 'available', NULL, '2026-04-26 16:03:52', NULL),
(804, '530', 23, 'available', NULL, '2026-04-26 16:03:52', NULL),
(805, '530', 24, 'available', NULL, '2026-04-26 16:03:52', NULL),
(806, '530', 25, 'available', NULL, '2026-04-26 16:03:52', NULL),
(807, '530', 26, 'available', NULL, '2026-04-26 16:03:52', NULL),
(808, '530', 27, 'available', NULL, '2026-04-26 16:03:52', NULL),
(809, '530', 28, 'available', NULL, '2026-04-26 16:03:52', NULL),
(810, '530', 29, 'available', NULL, '2026-04-26 16:03:52', NULL),
(811, '530', 30, 'available', NULL, '2026-04-26 16:03:52', NULL),
(812, '530', 31, 'available', NULL, '2026-04-26 16:03:52', NULL),
(813, '530', 32, 'available', NULL, '2026-04-26 16:03:52', NULL),
(814, '530', 33, 'available', NULL, '2026-04-26 16:03:52', NULL),
(815, '530', 34, 'available', NULL, '2026-04-26 16:03:52', NULL),
(816, '530', 35, 'available', NULL, '2026-04-26 16:03:52', NULL),
(817, '530', 36, 'available', NULL, '2026-04-26 16:03:52', NULL),
(818, '530', 37, 'available', NULL, '2026-04-26 16:03:52', NULL),
(819, '530', 38, 'available', NULL, '2026-04-26 16:03:52', NULL),
(820, '530', 39, 'available', NULL, '2026-04-26 16:03:52', NULL),
(821, '530', 40, 'available', NULL, '2026-04-26 16:03:52', NULL),
(822, '544', 1, 'available', NULL, '2026-04-26 16:03:52', NULL),
(823, '544', 2, 'available', NULL, '2026-04-26 16:03:52', NULL),
(824, '544', 3, 'available', NULL, '2026-04-26 16:03:52', NULL),
(825, '544', 4, 'available', NULL, '2026-04-26 16:03:52', NULL),
(826, '544', 5, 'available', NULL, '2026-04-26 16:03:52', NULL),
(827, '544', 6, 'available', NULL, '2026-04-26 16:03:52', NULL),
(828, '544', 7, 'available', NULL, '2026-04-26 16:03:52', NULL),
(829, '544', 8, 'available', NULL, '2026-04-26 16:03:52', NULL),
(830, '544', 9, 'available', NULL, '2026-04-26 16:03:52', NULL),
(831, '544', 10, 'available', NULL, '2026-04-26 16:03:52', NULL),
(832, '544', 11, 'available', NULL, '2026-04-26 16:03:52', NULL),
(833, '544', 12, 'available', NULL, '2026-04-26 16:03:52', NULL),
(834, '544', 13, 'available', NULL, '2026-04-26 16:03:52', NULL),
(835, '544', 14, 'available', NULL, '2026-04-26 16:03:52', NULL),
(836, '544', 15, 'available', NULL, '2026-04-26 16:03:52', NULL),
(837, '544', 16, 'available', NULL, '2026-04-26 16:03:52', NULL),
(838, '544', 17, 'available', NULL, '2026-04-26 16:03:52', NULL),
(839, '544', 18, 'available', NULL, '2026-04-26 16:03:52', NULL),
(840, '544', 19, 'available', NULL, '2026-04-26 16:03:52', NULL),
(841, '544', 20, 'available', NULL, '2026-04-26 16:03:52', NULL),
(842, '544', 21, 'available', NULL, '2026-04-26 16:03:52', NULL),
(843, '544', 22, 'available', NULL, '2026-04-26 16:03:52', NULL),
(844, '544', 23, 'available', NULL, '2026-04-26 16:03:52', NULL),
(845, '544', 24, 'available', NULL, '2026-04-26 16:03:52', NULL),
(846, '544', 25, 'available', NULL, '2026-04-26 16:03:52', NULL),
(847, '544', 26, 'available', NULL, '2026-04-26 16:03:52', NULL),
(848, '544', 27, 'available', NULL, '2026-04-26 16:03:52', NULL),
(849, '544', 28, 'available', NULL, '2026-04-26 16:03:52', NULL),
(850, '544', 29, 'available', NULL, '2026-04-26 16:03:52', NULL),
(851, '544', 30, 'available', NULL, '2026-04-26 16:03:52', NULL),
(852, '544', 31, 'available', NULL, '2026-04-26 16:03:52', NULL),
(853, '544', 32, 'available', NULL, '2026-04-26 16:03:52', NULL),
(854, '544', 33, 'available', NULL, '2026-04-26 16:03:52', NULL),
(855, '544', 34, 'available', NULL, '2026-04-26 16:03:52', NULL),
(856, '544', 35, 'available', NULL, '2026-04-26 16:03:52', NULL),
(857, '544', 36, 'available', NULL, '2026-04-26 16:03:52', NULL),
(858, '544', 37, 'available', NULL, '2026-04-26 16:03:52', NULL),
(859, '544', 38, 'available', NULL, '2026-04-26 16:03:52', NULL),
(860, '544', 39, 'available', NULL, '2026-04-26 16:03:52', NULL),
(861, '544', 40, 'available', NULL, '2026-04-26 16:03:52', NULL),
(862, 'Mac Lab', 1, 'available', NULL, '2026-04-26 16:03:52', NULL),
(863, 'Mac Lab', 2, 'available', NULL, '2026-04-26 16:03:52', NULL),
(864, 'Mac Lab', 3, 'available', NULL, '2026-04-26 16:03:52', NULL),
(865, 'Mac Lab', 4, 'available', NULL, '2026-04-26 16:03:52', NULL),
(866, 'Mac Lab', 5, 'available', NULL, '2026-04-26 16:03:52', NULL),
(867, 'Mac Lab', 6, 'available', NULL, '2026-04-26 16:03:52', NULL),
(868, 'Mac Lab', 7, 'available', NULL, '2026-04-26 16:03:52', NULL),
(869, 'Mac Lab', 8, 'available', NULL, '2026-04-26 16:03:52', NULL),
(870, 'Mac Lab', 9, 'available', NULL, '2026-04-26 16:03:52', NULL),
(871, 'Mac Lab', 10, 'available', NULL, '2026-04-26 16:03:52', NULL),
(872, 'Mac Lab', 11, 'available', NULL, '2026-04-26 16:03:52', NULL),
(873, 'Mac Lab', 12, 'available', NULL, '2026-04-26 16:03:52', NULL),
(874, 'Mac Lab', 13, 'available', NULL, '2026-04-26 16:03:52', NULL),
(875, 'Mac Lab', 14, 'available', NULL, '2026-04-26 16:03:52', NULL),
(876, 'Mac Lab', 15, 'available', NULL, '2026-04-26 16:03:52', NULL),
(877, 'Mac Lab', 16, 'available', NULL, '2026-04-26 16:03:52', NULL),
(878, 'Mac Lab', 17, 'available', NULL, '2026-04-26 16:03:52', NULL),
(879, 'Mac Lab', 18, 'available', NULL, '2026-04-26 16:03:52', NULL),
(880, 'Mac Lab', 19, 'available', NULL, '2026-04-26 16:03:52', NULL),
(881, 'Mac Lab', 20, 'available', NULL, '2026-04-26 16:03:52', NULL),
(882, 'Mac Lab', 21, 'available', NULL, '2026-04-26 16:03:52', NULL),
(883, 'Mac Lab', 22, 'available', NULL, '2026-04-26 16:03:52', NULL),
(884, 'Mac Lab', 23, 'available', NULL, '2026-04-26 16:03:52', NULL),
(885, 'Mac Lab', 24, 'available', NULL, '2026-04-26 16:03:52', NULL),
(957, '524', 41, 'available', NULL, '2026-04-26 16:20:12', NULL),
(958, '524', 42, 'available', NULL, '2026-04-26 16:20:12', NULL),
(959, '524', 43, 'available', NULL, '2026-04-26 16:20:12', NULL),
(960, '524', 44, 'available', NULL, '2026-04-26 16:20:12', NULL),
(961, '524', 45, 'available', NULL, '2026-04-26 16:20:12', NULL),
(962, '524', 46, 'available', NULL, '2026-04-26 16:20:12', NULL),
(963, '524', 47, 'available', NULL, '2026-04-26 16:20:12', NULL),
(964, '524', 48, 'available', NULL, '2026-04-26 16:20:12', NULL),
(965, '524', 49, 'available', NULL, '2026-04-26 16:20:12', NULL),
(966, '524', 50, 'available', NULL, '2026-04-26 16:20:12', NULL),
(1007, '526', 41, 'available', NULL, '2026-04-26 16:20:19', NULL),
(1008, '526', 42, 'available', NULL, '2026-04-26 16:20:19', NULL),
(1009, '526', 43, 'available', NULL, '2026-04-26 16:20:19', NULL),
(1010, '526', 44, 'available', NULL, '2026-04-26 16:20:19', NULL),
(1011, '526', 45, 'available', NULL, '2026-04-26 16:20:19', NULL),
(1012, '526', 46, 'available', NULL, '2026-04-26 16:20:19', NULL),
(1013, '526', 47, 'available', NULL, '2026-04-26 16:20:19', NULL),
(1014, '526', 48, 'available', NULL, '2026-04-26 16:20:19', NULL),
(1015, '526', 49, 'available', NULL, '2026-04-26 16:20:19', NULL),
(1016, '526', 50, 'available', NULL, '2026-04-26 16:20:19', NULL),
(1057, '528', 41, 'available', NULL, '2026-04-26 16:20:21', NULL),
(1058, '528', 42, 'available', NULL, '2026-04-26 16:20:21', NULL),
(1059, '528', 43, 'available', NULL, '2026-04-26 16:20:21', NULL),
(1060, '528', 44, 'available', NULL, '2026-04-26 16:20:21', NULL),
(1061, '528', 45, 'available', NULL, '2026-04-26 16:20:21', NULL),
(1062, '528', 46, 'available', NULL, '2026-04-26 16:20:21', NULL),
(1063, '528', 47, 'available', NULL, '2026-04-26 16:20:21', NULL),
(1064, '528', 48, 'available', NULL, '2026-04-26 16:20:21', NULL),
(1065, '528', 49, 'available', NULL, '2026-04-26 16:20:21', NULL),
(1066, '528', 50, 'available', NULL, '2026-04-26 16:20:21', NULL),
(1107, '530', 41, 'available', NULL, '2026-04-26 16:20:23', NULL),
(1108, '530', 42, 'available', NULL, '2026-04-26 16:20:23', NULL),
(1109, '530', 43, 'available', NULL, '2026-04-26 16:20:23', NULL),
(1110, '530', 44, 'available', NULL, '2026-04-26 16:20:23', NULL),
(1111, '530', 45, 'available', NULL, '2026-04-26 16:20:23', NULL),
(1112, '530', 46, 'available', NULL, '2026-04-26 16:20:23', NULL),
(1113, '530', 47, 'available', NULL, '2026-04-26 16:20:23', NULL),
(1114, '530', 48, 'available', NULL, '2026-04-26 16:20:23', NULL),
(1115, '530', 49, 'available', NULL, '2026-04-26 16:20:23', NULL),
(1116, '530', 50, 'available', NULL, '2026-04-26 16:20:23', NULL),
(1157, '544', 41, 'available', NULL, '2026-04-26 16:20:24', NULL),
(1158, '544', 42, 'available', NULL, '2026-04-26 16:20:24', NULL),
(1159, '544', 43, 'available', NULL, '2026-04-26 16:20:24', NULL),
(1160, '544', 44, 'available', NULL, '2026-04-26 16:20:24', NULL),
(1161, '544', 45, 'available', NULL, '2026-04-26 16:20:24', NULL),
(1162, '544', 46, 'available', NULL, '2026-04-26 16:20:24', NULL),
(1163, '544', 47, 'available', NULL, '2026-04-26 16:20:24', NULL),
(1164, '544', 48, 'available', NULL, '2026-04-26 16:20:24', NULL),
(1165, '544', 49, 'available', NULL, '2026-04-26 16:20:24', NULL),
(1166, '544', 50, 'available', NULL, '2026-04-26 16:20:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pc_status_history`
--

CREATE TABLE `pc_status_history` (
  `id` int(11) NOT NULL,
  `lab_name` varchar(50) NOT NULL,
  `pc_number` int(11) NOT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `changed_by` varchar(50) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `lab` varchar(50) DEFAULT NULL,
  `preferred_time` varchar(50) DEFAULT NULL,
  `reservation_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `seat_number` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `room` varchar(10) DEFAULT 'Lab 524'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sitin_records`
--

CREATE TABLE `sitin_records` (
  `id` int(11) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `purpose` varchar(100) DEFAULT NULL,
  `lab` varchar(50) DEFAULT NULL,
  `pc_number` int(11) DEFAULT NULL,
  `login_time` datetime DEFAULT current_timestamp(),
  `logout_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sitin_records`
--

INSERT INTO `sitin_records` (`id`, `id_number`, `purpose`, `lab`, `pc_number`, `login_time`, `logout_time`) VALUES
(4, '123456', 'C++', '528', NULL, '2026-03-20 12:54:06', '2026-03-20 12:54:11'),
(5, '21420450', 'C Programming', '524', 5, '2026-04-27 00:15:57', '2026-04-27 00:16:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `id_number` varchar(100) NOT NULL,
  `last_name` varchar(150) NOT NULL,
  `first_name` varchar(150) NOT NULL,
  `middle_name` varchar(150) DEFAULT NULL,
  `course` varchar(50) DEFAULT NULL,
  `year_level` varchar(10) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `profile_pic` varchar(255) DEFAULT NULL,
  `remaining_session` int(11) DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `id_number`, `last_name`, `first_name`, `middle_name`, `course`, `year_level`, `email`, `password`, `address`, `created_at`, `profile_pic`, `remaining_session`) VALUES
(4, '21420450', 'Torino', 'Frechie', 'Ann', 'Information Technology', '2', 'frechieannt@gmail.com', '$2y$10$FH5XsIRcuh47rfF.yVU.Wuc62EuAQvZYjxZwfuuDZGosLPw/bJ1Wy', 'asadad', '2026-04-04 20:26:43', 'user_21420450_1777220240.png', 29),
(5, '123456', 'Seaborg', 'Ancline', 'C', 'Computer Engineering', '2', 'ancline@gmail.com', '$2y$10$8feAfjSyiRDLL6c2mIr3se3iBAyKVcvZ49B/23nPEIkBz30wIjSAe', 'SAASA', '2026-04-04 20:39:43', NULL, 30);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_config`
--
ALTER TABLE `lab_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_lab` (`lab_name`);

--
-- Indexes for table `lab_pcs`
--
ALTER TABLE `lab_pcs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lab_pc_unique` (`lab`,`pc_number`);

--
-- Indexes for table `pc_status`
--
ALTER TABLE `pc_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_lab_pc` (`lab_name`,`pc_number`),
  ADD KEY `idx_lab` (`lab_name`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `pc_status_history`
--
ALTER TABLE `pc_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lab_pc` (`lab_name`,`pc_number`),
  ADD KEY `idx_changed_at` (`changed_at`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sitin_records`
--
ALTER TABLE `sitin_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_number` (`id_number`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `lab_config`
--
ALTER TABLE `lab_config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `lab_pcs`
--
ALTER TABLE `lab_pcs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=340;

--
-- AUTO_INCREMENT for table `pc_status`
--
ALTER TABLE `pc_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1167;

--
-- AUTO_INCREMENT for table `pc_status_history`
--
ALTER TABLE `pc_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sitin_records`
--
ALTER TABLE `sitin_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
