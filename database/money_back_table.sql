-- Money Back Distributions Table
-- This table tracks all money back distributions to Premium members

CREATE TABLE IF NOT EXISTS `money_back_distributions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `total_pool` decimal(15,2) NOT NULL COMMENT 'Total amount available for distribution (2% of company income)',
    `premium_count` int(11) NOT NULL COMMENT 'Number of Premium members who received distribution',
    `individual_amount` decimal(15,2) NOT NULL COMMENT 'Amount each Premium member received',
    `total_distributed` decimal(15,2) NOT NULL COMMENT 'Total amount actually distributed',
    `distribution_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_distribution_date` (`distribution_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Money Back Distribution Log';
