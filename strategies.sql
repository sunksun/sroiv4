-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 23, 2025 at 03:29 PM
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
-- Table structure for table `strategies`
--

CREATE TABLE `strategies` (
  `strategy_id` int(11) NOT NULL,
  `strategy_code` varchar(50) NOT NULL,
  `strategy_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `strategies`
--

INSERT INTO `strategies` (`strategy_id`, `strategy_code`, `strategy_name`, `description`, `created_at`) VALUES
(1, 'ยุทธศาสตร์ที่ 1', 'พัฒนาท้องถิ่น', 'ยุทธศาสตร์การพัฒนาท้องถิ่นแบบบูรณาการ', '2025-08-01 03:58:14'),
(2, 'ยุทธศาสตร์ที่ 2', 'ผลิตและพัฒนาครู', 'พัฒนาครูและบุคลากรทางการศึกษา', '2025-08-01 03:58:14'),
(3, 'ยุทธศาสตร์ที่ 3', 'ยกระดับคุณภาพการศึกษา', 'ยกระดับคุณภาพการศึกษาแบบบูรณาการ', '2025-08-22 15:55:47'),
(4, 'ยุทธศาสตร์ที่ 4', 'พัฒนาระบบบริหารจัดการ', 'พัฒนาระบบบริหารจัดการแบบบูรณาการ', '2025-08-22 15:55:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `strategies`
--
ALTER TABLE `strategies`
  ADD PRIMARY KEY (`strategy_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `strategies`
--
ALTER TABLE `strategies`
  MODIFY `strategy_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
