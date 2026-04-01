<?php
// ============================================================
//  config.example.php — Lending System Configuration
//  ============================================================
// 
// Copy this file to config.php and fill in your actual values.
// Never commit config.php to version control!
//

// ── Database Configuration ─────────────────────────────────────
define('DB_HOST', 'localhost');           // Database host
define('DB_NAME', 'your_db_name_here');   // Database name
define('DB_USER', 'your_username_here');  // Database username  
define('DB_PASS', 'your_password_here');  // Database password
define('DB_CHARSET', 'utf8mb4');          // Database charset

// ── Application Settings ───────────────────────────────────────
define('APP_NAME', 'Lending System');
define('APP_URL', 'http://localhost/lending_system');  // Base URL without trailing slash
define('APP_VERSION', '1.0.0');

// ── Security Settings ───────────────────────────────────────────
define('SESSION_LIFETIME', 3600 * 24 * 7);  // 7 days in seconds
define('HASH_COST', 12);                     // bcrypt cost factor

// ── File Upload Settings ────────────────────────────────────────
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);   // 5MB in bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// ── Email Settings ───────────────────────────────────────────────
define('MAIL_ENABLED', true);               // Enable/disable emails
define('MAIL_FROM', 'no-reply@yourdomain.com');
define('MAIL_FROM_NAME', 'Lending System');

// ── Loan Settings ────────────────────────────────────────────────
define('MAX_LOAN_AMOUNT', 10000);           // Maximum loan amount
define('MIN_LOAN_AMOUNT', 5000);            // Minimum loan amount
define('INTEREST_RATE', 0.03);              // 3% interest rate
define('PENALTY_RATE', 0.02);               // 2% penalty rate

// ── Savings Settings ─────────────────────────────────────────────
define('SAVINGS_MAX', 100000);              // Maximum savings balance
define('SAVINGS_MIN_DEPOSIT', 100);         // Minimum deposit amount
define('SAVINGS_MAX_DEPOSIT', 1000);        // Maximum deposit amount
define('SAVINGS_MIN_WITHDRAWAL', 500);      // Minimum withdrawal amount
define('SAVINGS_MAX_WITHDRAWAL', 5000);     // Maximum withdrawal amount
define('SAVINGS_MAX_WITHDRAWALS_PER_DAY', 5); // Max withdrawals per day

// ── Account Settings ────────────────────────────────────────────
define('PREMIUM_MAX_SLOTS', 50);            // Maximum Premium members
define('MONEY_BACK_PERCENTAGE', 0.02);      // 2% money back distribution

// ── Development Settings ─────────────────────────────────────────
define('DEBUG_MODE', false);                // Set to true for development
define('ERROR_REPORTING', E_ALL & ~E_DEPRECATED & ~E_STRICT);

// ── Timezone ─────────────────────────────────────────────────────
date_default_timezone_set('Asia/Manila');

// ── Error Reporting (based on DEBUG_MODE) ───────────────────────────
if (DEBUG_MODE) {
    error_reporting(ERROR_REPORTING);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// ──── DO NOT COMMIT THIS FILE TO VERSION CONTROL! ────────
// Make sure config.php is in your .gitignore file!
?>
