-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 07, 2025 at 09:53 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sroiv4`
--

-- --------------------------------------------------------

--
-- Table structure for table `project_impact_ratios`
--

CREATE TABLE `project_impact_ratios` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `benefit_number` int(11) NOT NULL,
  `attribution` decimal(5,2) DEFAULT 0.00,
  `deadweight` decimal(5,2) DEFAULT 0.00,
  `displacement` decimal(5,2) DEFAULT 0.00,
  `impact_ratio` decimal(5,4) DEFAULT 1.0000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `benefit_detail` text DEFAULT NULL,
  `benefit_note` int(11) NOT NULL,
  `year` varchar(10) DEFAULT NULL COMMENT 'ปี พ.ศ. ของการประเมิน'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_impact_ratios`
--

INSERT INTO `project_impact_ratios` (`id`, `project_id`, `benefit_number`, `attribution`, `deadweight`, `displacement`, `impact_ratio`, `created_at`, `updated_at`, `benefit_detail`, `benefit_note`, `year`) VALUES
(2, 7, 1, 20.00, 10.00, 30.00, 0.4000, '2025-08-07 03:24:35', '2025-08-07 03:24:35', 'รายได้จากการเป็นวิทยากร', 1800, NULL),
(4, 4, 1, 20.00, 10.00, 30.00, 0.4000, '2025-08-07 04:27:16', '2025-08-07 04:27:16', 'รายได้จากการเป็นวิทยากร', 3600, NULL),
(6, 3, 1, 20.00, 10.00, 30.00, 0.4000, '2025-08-07 07:52:26', '2025-08-07 07:52:26', 'รายได้จากการเป็นวิทยากร', 7200, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `project_impact_ratios`
--
ALTER TABLE `project_impact_ratios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_project_benefit` (`project_id`,`benefit_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `project_impact_ratios`
--
ALTER TABLE `project_impact_ratios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `project_impact_ratios`
--
ALTER TABLE `project_impact_ratios`
  ADD CONSTRAINT `project_impact_ratios_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
