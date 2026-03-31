<?php
// ============================================================
//  db.php — place at root: lending_system/db.php
// ============================================================

// Prevent direct access
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

} catch (PDOException $e) {
    // Show friendly error (hide details in production)
    die('<div style="font-family:sans-serif;padding:2rem;color:#c0392b;">
        <h2>Database Connection Failed</h2>
        <p>Please make sure XAMPP MySQL is running and the database <strong>' . DB_NAME . '</strong> exists.</p>
        <small>' . $e->getMessage() . '</small>
    </div>');
}

// Auto-run pending migrations
try {
    // Check if rejection_reason column exists in loans table
    $dbName = DB_NAME;
    $checkCol = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='loans' AND COLUMN_NAME='rejection_reason' AND TABLE_SCHEMA='$dbName'")->fetch();
    if (!$checkCol) {
        $pdo->exec("ALTER TABLE `loans` ADD COLUMN `rejection_reason` TEXT NULL DEFAULT NULL AFTER `approved_at`");
    }
    
    // Check if is_verified column exists in users table
    $checkVerified = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' AND COLUMN_NAME='is_verified' AND TABLE_SCHEMA='$dbName'")->fetch();
    if (!$checkVerified) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `is_verified` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Admin verification status' AFTER `status`");
    }
    $checkPoolCol = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='money_back_distributions' AND COLUMN_NAME='total_pool' AND TABLE_SCHEMA='$dbName'")->fetch();
    if (!$checkPoolCol) {
        $pdo->exec("ALTER TABLE `money_back_distributions` ADD COLUMN `total_pool` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total amount available for distribution (2% of company income)' AFTER `id`");
    }
    
    // Check if premium_count column exists
    $checkPremiumCount = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='money_back_distributions' AND COLUMN_NAME='premium_count' AND TABLE_SCHEMA='$dbName'")->fetch();
    if (!$checkPremiumCount) {
        $pdo->exec("ALTER TABLE `money_back_distributions` ADD COLUMN `premium_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of Premium members who received distribution' AFTER `total_pool`");
    }
    
    // Check if individual_amount column exists
    $checkIndividual = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='money_back_distributions' AND COLUMN_NAME='individual_amount' AND TABLE_SCHEMA='$dbName'")->fetch();
    if (!$checkIndividual) {
        $pdo->exec("ALTER TABLE `money_back_distributions` ADD COLUMN `individual_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Amount each Premium member received' AFTER `premium_count`");
    }
    
    // Check if total_distributed column exists
    $checkTotal = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='money_back_distributions' AND COLUMN_NAME='total_distributed' AND TABLE_SCHEMA='$dbName'")->fetch();
    if (!$checkTotal) {
        $pdo->exec("ALTER TABLE `money_back_distributions` ADD COLUMN `total_distributed` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total amount actually distributed' AFTER `individual_amount`");
    }
    
    // Check if distribution_date column exists
    $checkDate = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='money_back_distributions' AND COLUMN_NAME='distribution_date' AND TABLE_SCHEMA='$dbName'")->fetch();
    if (!$checkDate) {
        $pdo->exec("ALTER TABLE `money_back_distributions` ADD COLUMN `distribution_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When distribution occurred' AFTER `total_distributed`");
    }
    
    // Check if premium_since column exists in users table
    $checkPremiumSince = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' AND COLUMN_NAME='premium_since' AND TABLE_SCHEMA='$dbName'")->fetch();
    if (!$checkPremiumSince) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `premium_since` date NULL DEFAULT NULL COMMENT 'Date when user became Premium' AFTER `account_type`");
        // Set premium_since for existing Premium users based on created_at
        $pdo->exec("UPDATE users SET premium_since = DATE(created_at) WHERE account_type = 'Premium' AND premium_since IS NULL");
    }
    
    // Create money_back_recipients table if not exists (anniversary-based)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `money_back_recipients` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `distribution_id` int(11) NOT NULL COMMENT 'FK to money_back_distributions',
        `user_id` int(11) NOT NULL COMMENT 'FK to users table',
        `user_name` varchar(255) NOT NULL COMMENT 'Full name at time of distribution',
        `amount` decimal(15,2) NOT NULL COMMENT 'Amount received',
        `transaction_id` varchar(50) NOT NULL COMMENT 'Savings transaction ID',
        `year` int(4) NULL DEFAULT NULL COMMENT 'Year of distribution (deprecated)',
        `premium_since` date NULL DEFAULT NULL COMMENT 'User Premium anniversary date',
        `last_received` date NULL DEFAULT NULL COMMENT 'When user last received money back',
        `next_eligible` date NULL DEFAULT NULL COMMENT 'When user is next eligible (1 year after last_received)',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_distribution_id` (`distribution_id`),
        KEY `idx_user_id` (`user_id`),
        KEY `idx_next_eligible` (`next_eligible`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Individual Money Back Recipients with Anniversary Tracking'");
    
    // Add new columns to existing table if not present
    $checkCol = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='money_back_recipients' AND COLUMN_NAME='premium_since' AND TABLE_SCHEMA='$dbName'")->fetch();
    if (!$checkCol) {
        $pdo->exec("ALTER TABLE `money_back_recipients` ADD COLUMN `premium_since` date NULL DEFAULT NULL COMMENT 'User Premium anniversary date' AFTER `year`");
    }
    
    $checkCol = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='money_back_recipients' AND COLUMN_NAME='last_received' AND TABLE_SCHEMA='$dbName'")->fetch();
    if (!$checkCol) {
        $pdo->exec("ALTER TABLE `money_back_recipients` ADD COLUMN `last_received` date NULL DEFAULT NULL COMMENT 'When user last received money back' AFTER `premium_since`");
    }
    
    $checkCol = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='money_back_recipients' AND COLUMN_NAME='next_eligible' AND TABLE_SCHEMA='$dbName'")->fetch();
    if (!$checkCol) {
        $pdo->exec("ALTER TABLE `money_back_recipients` ADD COLUMN `next_eligible` date NULL DEFAULT NULL COMMENT 'When user is next eligible' AFTER `last_received`");
    }
    
    // Create company_income_deductions table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS `company_income_deductions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `distribution_id` int(11) NOT NULL COMMENT 'FK to money_back_distributions',
        `amount` decimal(15,2) NOT NULL COMMENT 'Amount deducted from income',
        `year` int(4) NOT NULL COMMENT 'Year of deduction',
        `deducted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_distribution_id` (`distribution_id`),
        KEY `idx_year` (`year`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Company Income Deductions for Money Back'");
    // Check if rejection_reason column exists in users table
    $checkRejectionReason = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' AND COLUMN_NAME='rejection_reason' AND TABLE_SCHEMA='$dbName'")->fetch();
    if (!$checkRejectionReason) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `rejection_reason` TEXT NULL DEFAULT NULL COMMENT 'Reason for rejection' AFTER `status`");
    }
    
    // Check if delete_at column exists in users table
    $checkDeleteAt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' AND COLUMN_NAME='delete_at' AND TABLE_SCHEMA='$dbName'")->fetch();
    if (!$checkDeleteAt) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `delete_at` datetime NULL DEFAULT NULL COMMENT 'Auto-delete date for rejected accounts' AFTER `rejection_reason`");
    }
    
} catch (Exception $e) {
    // Migration errors are non-critical, just log them
}