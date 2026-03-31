<?php
// ============================================================
//  config.php — Railway Deployment Configuration
//  ============================================================
// 
// This version uses environment variables for cloud deployment
// on platforms like Railway, Heroku, Render, etc.
// 
// For local development, copy this to config.php and update
// the values or set up your local environment variables.
//

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Database Configuration (Environment Variables) ─────────────────
define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'lending_system');
define('DB_PORT', getenv('MYSQLPORT') ?: '3306');
define('DB_CHARSET', 'utf8mb4');

// ── Application Settings (Environment Variables) ────────────────────
define('APP_NAME',    'Lending System');
define('APP_URL',     getenv('APP_URL') ?: 'http://localhost/lending_system');
define('APP_VERSION', '1.0.0');

// ── Database Connection (with port support) ───────────────────────────
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Log error but don't expose database details in production
    error_log("Database connection failed: " . $e->getMessage());
    
    // Show user-friendly error
    if (getenv('RAILWAY_ENVIRONMENT') || getenv('HEROKU')) {
        die("🔧 Database connection failed. Please check your environment variables.");
    } else {
        die("🔧 Database connection failed: " . $e->getMessage());
    }
}

// ── Loan Rules ───────────────────────────────────────────────────────
define('LOAN_MIN',              5000);
define('LOAN_MAX',              10000);
define('LOAN_INCREMENT',        5000);   // increases by 5k on good standing
define('LOAN_ABSOLUTE_MAX',     50000);  // can never exceed this
define('LOAN_INTEREST_RATE',    0.03);   // 3%
define('LOAN_PENALTY_RATE',     0.02);   // 2% for missed payment
define('LOAN_DUE_DAYS',         28);     // due date = release date + 28 days

// ── Billing Rules ─────────────────────────────────────────────────────
define('BILLING_DUE_DAYS',     7);      // billing due after loan release + 7 days
define('PENALTY_RATE_PER_DAY', 0.02);   // 2% penalty per day on overdue

// ── Savings Rules ─────────────────────────────────────────────────────
define('SAVINGS_MIN_DEPOSIT',      100);    // minimum deposit per transaction
define('SAVINGS_MAX_DEPOSIT',      1000);   // maximum deposit per transaction
define('SAVINGS_MAX',              100000); // maximum savings balance
define('SAVINGS_MAX_WITHDRAW_DAY', 5000);   // max withdrawal amount per day
define('SAVINGS_MAX_WITHDRAW_COUNT', 5);    // max withdrawal count per day

// ── Premium Member Rules ───────────────────────────────────────────────
define('PREMIUM_MAX_SLOTS',        50);     // maximum premium members
define('PREMIUM_MONTHLY_FEE',      100);    // monthly premium fee

// ── Money Back Rules ───────────────────────────────────────────────────
define('MONEY_BACK_PERCENTAGE',    0.02);   // 2% of company income
define('MONEY_BACK_MIN_INTERVAL',  30);     // minimum days between distributions

// ── File Upload Settings ───────────────────────────────────────────────
define('UPLOAD_MAX_SIZE',          5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES',     ['jpg', 'jpeg', 'png', 'pdf']);

// ── Security Settings ───────────────────────────────────────────────────
define('SESSION_LIFETIME',         3600);       // 1 hour
define('MAX_LOGIN_ATTEMPTS',       5);          // lockout after 5 attempts
define('LOCKOUT_DURATION',         900);        // 15 minutes

// ── Email Settings (Optional) ───────────────────────────────────────────
define('SMTP_HOST',     getenv('SMTP_HOST') ?: '');
define('SMTP_PORT',     getenv('SMTP_PORT') ?: '587');
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_FROM',     getenv('SMTP_FROM') ?: 'noreply@lendingsystem.com');

// ── Debug Settings ─────────────────────────────────────────────────────
define('DEBUG_MODE', getenv('DEBUG') ?: false);

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ERROR | E_PARSE);
}

// ── Timezone ───────────────────────────────────────────────────────────
date_default_timezone_set('Asia/Manila');

// ── Application Constants ───────────────────────────────────────────────
define('ASSETS_URL', APP_URL . '/assets');
define('UPLOADS_URL', APP_URL . '/uploads');
define('UPLOADS_PATH', __DIR__ . '/uploads');

// ── Ensure uploads directory exists ─────────────────────────────────────
if (!file_exists(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0755, true);
}

// ============================================================
//  END OF CONFIGURATION
//  ============================================================
