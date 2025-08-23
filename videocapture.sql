-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 20, 2025 at 02:30 PM
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
-- Database: `videocapture`
--

-- --------------------------------------------------------

--
-- Table structure for table `folders`
--

CREATE TABLE `folders` (
  `id` int(6) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `organization_id` int(6) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_size` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `folders`
--

INSERT INTO `folders` (`id`, `name`, `organization_id`, `created_at`, `file_size`) VALUES
(1, 'Production', NULL, '2025-08-19 06:25:17', NULL),
(2, 'P1', NULL, '2025-08-19 06:25:17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `organizations`
--

CREATE TABLE `organizations` (
  `id` int(6) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_size` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organizations`
--

INSERT INTO `organizations` (`id`, `name`, `created_at`, `file_size`) VALUES
(1, 'LOUBE', '2025-08-19 06:25:55', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `possible_improvements`
--

CREATE TABLE `possible_improvements` (
  `id` int(6) UNSIGNED NOT NULL,
  `cycle_number` int(6) UNSIGNED NOT NULL,
  `improvement` text NOT NULL,
  `type_of_benefits` varchar(255) NOT NULL,
  `video_id` int(6) UNSIGNED NOT NULL,
  `video_detail_id` int(6) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `possible_improvements`
--

INSERT INTO `possible_improvements` (`id`, `cycle_number`, `improvement`, `type_of_benefits`, `video_id`, `video_detail_id`) VALUES
(5, 0, 'a', 'a', 9, 6),
(6, 0, 'b', 'b', 9, 7);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(6) UNSIGNED NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`) VALUES
(1, 'LeanERPsupport@leanonus.in', '$2y$10$Xb1Q73WyoGfUmvOAsNr5tewyI/QaR3WY0OHpBNXyeSIeB0MlP6IqS');

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int(6) UNSIGNED NOT NULL,
  `video_path` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `folder_id` int(6) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_size` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `videos`
--

INSERT INTO `videos` (`id`, `video_path`, `name`, `folder_id`, `created_at`, `file_size`) VALUES
(9, '68a56c55c894f_Projects  ERPNext - Frappe (144p, h264).mp4', 'V1', NULL, '2025-08-20 06:31:10', NULL),
(10, '68a573e32122d_SampleVideo_720x480_30mb.mp4', 'V2', NULL, '2025-08-20 07:06:02', 31551484);

-- --------------------------------------------------------

--
-- Table structure for table `video_details`
--

CREATE TABLE `video_details` (
  `id` int(6) UNSIGNED NOT NULL,
  `video_id` int(6) UNSIGNED NOT NULL,
  `operator` varchar(30) NOT NULL,
  `description` varchar(255) NOT NULL,
  `va_nva_enva` varchar(30) NOT NULL,
  `start_time` varchar(30) NOT NULL,
  `end_time` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `video_details`
--

INSERT INTO `video_details` (`id`, `video_id`, `operator`, `description`, `va_nva_enva`, `start_time`, `end_time`) VALUES
(6, 9, 'a', 'a', 'VA', '00:00:00', '00:00:00'),
(7, 9, 'b', 'b', 'VA', '00:00:00', '00:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `folders`
--
ALTER TABLE `folders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `organizations`
--
ALTER TABLE `organizations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `possible_improvements`
--
ALTER TABLE `possible_improvements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_possible_improvements_video_id` (`video_id`),
  ADD KEY `fk_possible_improvements_video_detail_id` (`video_detail_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `video_details`
--
ALTER TABLE `video_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_video_details_video_id` (`video_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `folders`
--
ALTER TABLE `folders`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `organizations`
--
ALTER TABLE `organizations`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `possible_improvements`
--
ALTER TABLE `possible_improvements`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `video_details`
--
ALTER TABLE `video_details`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `possible_improvements`
--
ALTER TABLE `possible_improvements`
  ADD CONSTRAINT `fk_possible_improvements_video_detail_id` FOREIGN KEY (`video_detail_id`) REFERENCES `video_details` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_possible_improvements_video_id` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `video_details`
--
ALTER TABLE `video_details`
  ADD CONSTRAINT `fk_video_details_video_id` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
