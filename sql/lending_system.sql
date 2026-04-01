-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 01, 2026 at 12:11 PM
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
-- Database: `lending_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(150) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `email`, `created_at`) VALUES
(2, 'admin', '$2y$10$H9kxypKkojwl7L0wLwQRhOIbqZBeL2PQ62/72I9GRmkAxfDcSua6y', 'admin@lendingsystem.com', '2026-03-29 01:59:15');

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `loan_id` int(11) DEFAULT NULL,
  `month_number` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `amount_due` decimal(12,2) NOT NULL DEFAULT 0.00,
  `interest` decimal(12,2) NOT NULL DEFAULT 0.00,
  `penalty` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_due` decimal(12,2) NOT NULL DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blocked_emails`
--

CREATE TABLE `blocked_emails` (
  `id` int(10) UNSIGNED NOT NULL,
  `email` varchar(150) NOT NULL,
  `reason` text DEFAULT NULL,
  `blocked_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `company_income`
--

CREATE TABLE `company_income` (
  `id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `earned_at` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company_income`
--

INSERT INTO `company_income` (`id`, `amount`, `description`, `earned_at`, `created_at`) VALUES
(2, 1.00, 'what', '2026-03-31', '2026-03-31 13:37:01'),
(3, 1.00, '1', '2026-03-31', '2026-03-31 13:39:41'),
(4, 1.00, '1', '2026-03-31', '2026-03-31 14:16:21');

-- --------------------------------------------------------

--
-- Table structure for table `company_income_deductions`
--

CREATE TABLE `company_income_deductions` (
  `id` int(11) NOT NULL,
  `distribution_id` int(11) NOT NULL COMMENT 'FK to money_back_distributions',
  `amount` decimal(15,2) NOT NULL COMMENT 'Amount deducted from income',
  `year` int(4) NOT NULL COMMENT 'Year of deduction',
  `deducted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Company Income Deductions for Money Back';

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `applied_amount` decimal(10,2) NOT NULL,
  `term_months` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `received_amount` decimal(12,2) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `applied_at` datetime DEFAULT current_timestamp(),
  `approved_at` datetime DEFAULT NULL,
  `interest` decimal(5,2) DEFAULT 3.00,
  `term` int(11) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_transactions`
--

CREATE TABLE `loan_transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `no` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `transaction_id` varchar(20) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `moneyback_transactions`
--

CREATE TABLE `moneyback_transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `year` year(4) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `moneyback_transactions`
--

INSERT INTO `moneyback_transactions` (`id`, `user_id`, `amount`, `year`, `created_at`) VALUES
(1, 1, 10000.00, '2026', '2026-03-30 02:24:02');

-- --------------------------------------------------------

--
-- Table structure for table `money_back_distributions`
--

CREATE TABLE `money_back_distributions` (
  `id` int(11) NOT NULL,
  `total_pool` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total amount available for distribution (2% of company income)',
  `premium_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of Premium members who received distribution',
  `individual_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Amount each Premium member received',
  `total_distributed` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total amount actually distributed',
  `user_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `year` year(4) NOT NULL,
  `distributed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','completed') DEFAULT 'completed',
  `distribution_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `money_back_recipients`
--

CREATE TABLE `money_back_recipients` (
  `id` int(11) NOT NULL,
  `distribution_id` int(11) NOT NULL COMMENT 'FK to money_back_distributions',
  `user_id` int(11) NOT NULL COMMENT 'FK to users table',
  `user_name` varchar(255) NOT NULL COMMENT 'Full name at time of distribution',
  `amount` decimal(15,2) NOT NULL COMMENT 'Amount received',
  `transaction_id` varchar(50) NOT NULL COMMENT 'Savings transaction ID',
  `year` int(4) NOT NULL COMMENT 'Year of distribution',
  `premium_since` date DEFAULT NULL COMMENT 'User Premium anniversary date',
  `last_received` date DEFAULT NULL COMMENT 'When user last received money back',
  `next_eligible` date DEFAULT NULL COMMENT 'When user is next eligible',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Individual Money Back Recipients';

-- --------------------------------------------------------

--
-- Table structure for table `savings`
--

CREATE TABLE `savings` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `last_deposit_date` date DEFAULT NULL,
  `zero_since` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `savings`
--

INSERT INTO `savings` (`id`, `user_id`, `balance`, `last_deposit_date`, `zero_since`, `created_at`, `updated_at`) VALUES
(1, 1, 0.00, NULL, NULL, '2026-03-31 17:26:08', '2026-04-01 18:04:05');

-- --------------------------------------------------------

--
-- Table structure for table `savings_transactions`
--

CREATE TABLE `savings_transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `no` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `transaction_id` varchar(20) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category` enum('Deposit','Withdrawal') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('Pending','Completed','Failed','Rejected') NOT NULL DEFAULT 'Pending',
  `note` text DEFAULT NULL,
  `requested_at` datetime DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `account_type` enum('Basic','Premium') NOT NULL DEFAULT 'Basic',
  `premium_since` date DEFAULT NULL COMMENT 'Date when user became Premium',
  `status` enum('Pending','Active','Disabled','Rejected') NOT NULL DEFAULT 'Pending',
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `birthday` date NOT NULL,
  `age` tinyint(3) UNSIGNED NOT NULL,
  `email` varchar(150) NOT NULL,
  `contact_number` varchar(15) NOT NULL,
  `bank_name` varchar(150) NOT NULL,
  `bank_account_number` varchar(50) NOT NULL,
  `card_holder_name` varchar(150) NOT NULL,
  `tin_number` varchar(20) NOT NULL,
  `company_name` varchar(150) NOT NULL,
  `company_address` text NOT NULL,
  `company_phone` varchar(15) NOT NULL,
  `position` varchar(100) NOT NULL,
  `monthly_earnings` decimal(12,2) NOT NULL,
  `proof_of_billing` varchar(255) NOT NULL,
  `valid_id` varchar(255) NOT NULL,
  `coe` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `loan_limit` decimal(12,2) NOT NULL DEFAULT 10000.00,
  `max_term_months` int(10) UNSIGNED NOT NULL DEFAULT 12,
  `rejection_reason` text DEFAULT NULL,
  `delete_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `account_type`, `premium_since`, `status`, `first_name`, `last_name`, `address`, `gender`, `birthday`, `age`, `email`, `contact_number`, `bank_name`, `bank_account_number`, `card_holder_name`, `tin_number`, `company_name`, `company_address`, `company_phone`, `position`, `monthly_earnings`, `proof_of_billing`, `valid_id`, `coe`, `is_verified`, `loan_limit`, `max_term_months`, `rejection_reason`, `delete_at`, `rejected_at`, `created_at`, `updated_at`) VALUES
(1, 'Franceadolf', '$2y$10$4tM8ChhrcIrtgxcHm3FLGuwY8Awo7BAK0LGm0bngrpepKAMg.XA82', 'Premium', NULL, 'Active', 'France Adolf', 'Borja', 'Brgy. Colon,  City Of Naga, Cebu.', 'Female', '2005-11-22', 20, 'Adolfborja132@gmail.com', '09633814281', 'BDO', '1234567890', 'BORJA FRANCE ADOLF P', '111-111-111', 'St. Cecilia\'s College - Cebu, Inc.', 'Minglanillia', '02-8123-4567', 'Teacher', 20000.00, 'proof_of_billing/69cb923a8651e3.89727469.jpg', 'valid_id/69cb923a869287.15297067.jpg', 'coe/69cb923a86d406.78888115.jpg', 1, 10000.00, 12, NULL, NULL, NULL, '2026-03-31 17:22:02', '2026-04-01 18:04:05'),
(2, 'Rhexzel', '$2y$10$q.IJEGz6zlc16ZPOFiX3rOj2Zr9OXKTESS7MyWRrtVM47tqWDPw36', 'Basic', NULL, 'Active', 'Rhexzel', 'Delima', 'Brgy. Tangke 1, City Of naga, Cebu', 'Male', '2005-11-22', 20, 'Delima@gmail.com', '09633814281', 'BDO', '123123123', 'Delima', '222-222-222', 'APO CEMENT CORP', 'Tinaan, City Of Naga', '123456789', 'Welder', 15000.00, 'proof_of_billing/69cce154221972.05093521.pdf', 'valid_id/69cce154227a96.01632267.pdf', 'coe/69cce15422b669.37747014.pdf', 1, 10000.00, 12, NULL, NULL, NULL, '2026-04-01 17:11:48', '2026-04-01 17:41:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blocked_emails`
--
ALTER TABLE `blocked_emails`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `company_income`
--
ALTER TABLE `company_income`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `company_income_deductions`
--
ALTER TABLE `company_income_deductions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_distribution_id` (`distribution_id`),
  ADD KEY `idx_year` (`year`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `loan_transactions`
--
ALTER TABLE `loan_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `moneyback_transactions`
--
ALTER TABLE `moneyback_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `money_back_distributions`
--
ALTER TABLE `money_back_distributions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `money_back_recipients`
--
ALTER TABLE `money_back_recipients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_year` (`user_id`,`year`),
  ADD KEY `idx_distribution_id` (`distribution_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_year` (`year`);

--
-- Indexes for table `savings`
--
ALTER TABLE `savings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `savings_transactions`
--
ALTER TABLE `savings_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `blocked_emails`
--
ALTER TABLE `blocked_emails`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `company_income`
--
ALTER TABLE `company_income`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `company_income_deductions`
--
ALTER TABLE `company_income_deductions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `loan_transactions`
--
ALTER TABLE `loan_transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `moneyback_transactions`
--
ALTER TABLE `moneyback_transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `money_back_distributions`
--
ALTER TABLE `money_back_distributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `money_back_recipients`
--
ALTER TABLE `money_back_recipients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `savings`
--
ALTER TABLE `savings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `savings_transactions`
--
ALTER TABLE `savings_transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `savings`
--
ALTER TABLE `savings`
  ADD CONSTRAINT `savings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `savings_transactions`
--
ALTER TABLE `savings_transactions`
  ADD CONSTRAINT `savings_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
