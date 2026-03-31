-- ============================================================
--  migrations.sql — Database schema updates
-- ============================================================

-- Add rejection_reason column to loans table (if not exists)
ALTER TABLE `loans` ADD COLUMN `rejection_reason` TEXT NULL DEFAULT NULL AFTER `approved_at`;

-- Add total_pool column to money_back_distributions table (if not exists)
ALTER TABLE `money_back_distributions` ADD COLUMN IF NOT EXISTS `total_pool` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total amount available for distribution (2% of company income)' AFTER `id`;
