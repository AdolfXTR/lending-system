-- Money Back Recipients Table (Updated for Anniversary-Based Distribution)
-- Tracks which users received money back and when they're next eligible

CREATE TABLE IF NOT EXISTS `money_back_recipients` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `distribution_id` int(11) NOT NULL COMMENT 'FK to money_back_distributions',
    `user_id` int(11) NOT NULL COMMENT 'FK to users table',
    `user_name` varchar(255) NOT NULL COMMENT 'Full name at time of distribution',
    `amount` decimal(15,2) NOT NULL COMMENT 'Amount received',
    `transaction_id` varchar(50) NOT NULL COMMENT 'Savings transaction ID',
    `premium_since` date NOT NULL COMMENT 'User Premium anniversary date',
    `last_received` date NOT NULL COMMENT 'When user last received money back',
    `next_eligible` date NOT NULL COMMENT 'When user is next eligible (1 year after last_received)',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_distribution_id` (`distribution_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_next_eligible` (`next_eligible`),
    UNIQUE KEY `unique_user_period` (`user_id`, `last_received`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Individual Money Back Recipients with Anniversary Tracking';

-- Company Income Deductions Table
-- Tracks money back distributions deducted from company income

CREATE TABLE IF NOT EXISTS `company_income_deductions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `distribution_id` int(11) NOT NULL COMMENT 'FK to money_back_distributions',
    `amount` decimal(15,2) NOT NULL COMMENT 'Amount deducted from income',
    `year` int(4) NOT NULL COMMENT 'Year of deduction',
    `deducted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_distribution_id` (`distribution_id`),
    KEY `idx_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Company Income Deductions for Money Back';
