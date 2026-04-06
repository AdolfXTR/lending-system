<?php
// ============================================================
//  helpers.php — place at root: lending_system/helpers.php
// ============================================================

// ── Transaction ID Generator ──────────────────────────────────
function generateTransactionId(string $prefix = 'TXN'): string {
    return strtoupper($prefix) . '-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// ── Auto-increment display No. ────────────────────────────────
function getNextNo(PDO $pdo, string $table): int {
    $stmt = $pdo->query("SELECT MAX(no) FROM `$table`");
    $max  = $stmt->fetchColumn();
    return $max ? (int)$max + 1 : 1;
}

// ── Age Calculator ────────────────────────────────────────────
function calculateAge(string $birthday): int {
    return (int)(new DateTime($birthday))->diff(new DateTime('today'))->y;
}

// ── Money Formatter ───────────────────────────────────────────
function formatMoney(float $amount): string {
    return '₱ ' . number_format($amount, 2);
}

// ── Loan: received amount after 3% deduction ─────────────────
function getLoanReceivedAmount(float $amount): float {
    return $amount - ($amount * LOAN_INTEREST_RATE);
}

// ── Loan: monthly due (no interest, just principal) ───────────
function getMonthlyDue(float $amount, int $months): float {
    return round($amount / $months, 2);
}

// ── Billing due date: 28 days from now ───────────────────────
function getBillingDueDate(string $fromDate = 'today'): string {
    return (new DateTime($fromDate))->modify('+' . LOAN_DUE_DAYS . ' days')->format('Y-m-d');
}

// ── Sanitize input ────────────────────────────────────────────
function clean(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// ── Redirect ──────────────────────────────────────────────────
function redirect(string $url): void {
    // If already a full URL (starts with http), use as-is
    if (str_starts_with($url, 'http')) {
        header('Location: ' . $url);
    } else {
        header('Location: ' . APP_URL . '/' . ltrim($url, '/'));
    }
    exit;
}

// ── Flash Messages ────────────────────────────────────────────
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ── Check if user is logged in ────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

// ── Check if admin is logged in ──────────────────────────────
function isAdmin(): bool {
    return isset($_SESSION['admin_id']);
}

// ── Check Premium account ─────────────────────────────────────
function isPremium(): bool {
    // Always check fresh data from database if user is logged in
    if (isset($_SESSION['user_id']) && isset($pdo)) {
        $stmt = $pdo->prepare("SELECT account_type FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        return $user && $user['account_type'] === 'Premium';
    }
    // Fallback to session data (for cases where $pdo is not available)
    return isset($_SESSION['account_type']) && $_SESSION['account_type'] === 'Premium';
}

// ── Status Badge Generator ───────────────────────────────────────
function getStatusBadge(string $status, string $type = 'default'): string {
    $badgeConfig = [
        // Loan statuses
        'loan' => [
            'Pending'   => ['bg' => 'warning', 'text' => 'dark', 'icon' => '⏳'],
            'Approved'  => ['bg' => 'info', 'text' => 'white', 'icon' => '✓'],
            'Active'    => ['bg' => 'success', 'text' => 'white', 'icon' => '🔵'],
            'Rejected'  => ['bg' => 'danger', 'text' => 'white', 'icon' => '❌'],
            'Completed' => ['bg' => 'secondary', 'text' => 'white', 'icon' => '✅'],
        ],
        // Billing statuses
        'billing' => [
            'Pending'   => ['bg' => 'warning', 'text' => 'dark', 'icon' => '⏳'],
            'Overdue'   => ['bg' => 'danger', 'text' => 'white', 'icon' => '⚠️'],
            'Completed' => ['bg' => 'success', 'text' => 'white', 'icon' => '✅'],
        ],
        // Transaction statuses
        'transaction' => [
            'Pending'   => ['bg' => 'warning', 'text' => 'dark', 'icon' => '⏳'],
            'Completed' => ['bg' => 'success', 'text' => 'white', 'icon' => '✅'],
            'Failed'    => ['bg' => 'danger', 'text' => 'white', 'icon' => '❌'],
            'Rejected'  => ['bg' => 'danger', 'text' => 'white', 'icon' => '🚫'],
            'Approved'  => ['bg' => 'primary', 'text' => 'white', 'icon' => '✓'],
        ],
        // Savings transaction statuses
        'savings' => [
            'Pending'   => ['bg' => 'warning', 'text' => 'dark', 'icon' => '⏳'],
            'Completed' => ['bg' => 'success', 'text' => 'white', 'icon' => '✅'],
            'Failed'    => ['bg' => 'danger', 'text' => 'white', 'icon' => '❌'],
            'Rejected'  => ['bg' => 'danger', 'text' => 'white', 'icon' => '🚫'],
        ],
        // Default fallback
        'default' => [
            'Pending'   => ['bg' => 'warning', 'text' => 'dark', 'icon' => '⏳'],
            'Completed' => ['bg' => 'success', 'text' => 'white', 'icon' => '✅'],
            'Failed'    => ['bg' => 'danger', 'text' => 'white', 'icon' => '❌'],
            'Rejected'  => ['bg' => 'danger', 'text' => 'white', 'icon' => '🚫'],
            'Approved'  => ['bg' => 'primary', 'text' => 'white', 'icon' => '✓'],
            'Active'    => ['bg' => 'success', 'text' => 'white', 'icon' => '🔵'],
            'Overdue'   => ['bg' => 'warning', 'text' => 'dark', 'icon' => '⚠️'],
        ]
    ];

    $config = $badgeConfig[$type][$status] ?? $badgeConfig['default'][$status] ?? ['bg' => 'secondary', 'text' => 'white', 'icon' => ''];
    
    $textClass = $config['text'] === 'dark' ? 'text-dark' : '';
    $icon = $config['icon'] ? $config['icon'] . ' ' : '';
    
    return "<span class=\"badge bg-{$config['bg']} {$textClass}\">{$icon}{$status}</span>";
}

// ── Standardized Transaction ID Generator ───────────────────────────
function generateStandardTransactionId(string $type = 'TXN'): string {
    // Format: TXN-YYYYMMDD-XXXXXXXX (8 digits)
    $date = date('Ymd');
    $random = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
    return strtoupper($type) . '-' . $date . '-' . $random;
}

// ── Money Back System Functions ────────────────────────────────
function getLastDistributionDate(PDO $pdo): ?string {
    $stmt = $pdo->query("
        SELECT MAX(distributed_up_to_date) as last_date 
        FROM money_back_distributions 
        WHERE distributed_up_to_date IS NOT NULL
    ");
    $lastDate = $stmt->fetchColumn();
    return $lastDate ?: null;
}

function calculateNewMoneyBackIncome(PDO $pdo): float {
    $lastDistributionDate = getLastDistributionDate($pdo);
    
    if ($lastDistributionDate) {
        // Only count interest from bills paid AFTER the last distribution
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(interest), 0) as total_income 
            FROM billing 
            WHERE status = 'Completed' 
            AND paid_at > ?
        ");
        $stmt->execute([$lastDistributionDate]);
    } else {
        // No previous distribution - count all completed billing interest
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(interest), 0) as total_income 
            FROM billing 
            WHERE status = 'Completed'
        ");
        $stmt->execute();
    }
    
    return (float)$stmt->fetchColumn();
}

/**
 * @deprecated Use calculateNewMoneyBackIncome() instead
 * Calculate total interest income from billing table in the current year
 */
function calculateMoneyBackDistribution(PDO $pdo): float {
    // Calculate total interest income from billing table in the current year
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(interest), 0) as total_income 
        FROM billing 
        WHERE status = 'Completed' 
        AND YEAR(paid_at) = YEAR(CURRENT_DATE)
    ");
    $stmt->execute();
    $totalIncome = (float)$stmt->fetchColumn();
    
    // Calculate 2% of total interest income
    $moneyBackPool = $totalIncome * 0.02;
    
    return $moneyBackPool;
}

function getPremiumMemberCount(PDO $pdo): int {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE account_type = 'Premium' 
        AND status = 'Active'
    ");
    $stmt->execute();
    return (int)$stmt->fetchColumn();
}

function calculateIndividualMoneyBack(PDO $pdo): float {
    $newIncome = calculateNewMoneyBackIncome($pdo);
    $moneyBackPool = $newIncome * 0.02;
    $premiumCount = getPremiumMemberCount($pdo);
    
    if ($premiumCount === 0) {
        return 0;
    }
    
    return $moneyBackPool / $premiumCount;
}

function distributeMoneyBack(PDO $pdo): array {
    // Get the last distribution date to calculate new income since then
    $lastDistributionDate = getLastDistributionDate($pdo);
    
    // Calculate only NEW interest income (since last distribution)
    $newIncome = calculateNewMoneyBackIncome($pdo);
    
    if ($newIncome <= 0) {
        return ['distributed' => false, 'message' => 'No new interest income available for distribution. All interest has already been distributed or no completed billing payments found.'];
    }
    
    // Calculate distribution: (New Income x 0.02) ÷ ALL Premium members
    $moneyBackPool = $newIncome * 0.02;
    
    // Get ALL active Premium members
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email,
               COALESCE(s.balance, 0) as savings_balance
        FROM users u
        LEFT JOIN savings s ON s.user_id = u.id
        WHERE u.account_type = 'Premium' 
        AND u.status = 'Active'
    ");
    $stmt->execute();
    $premiumMembers = $stmt->fetchAll();
    
    if (empty($premiumMembers)) {
        return ['distributed' => false, 'message' => 'No active Premium members found for distribution.'];
    }
    
    $individualAmount = $moneyBackPool / count($premiumMembers);
    
    if ($individualAmount <= 0) {
        return ['distributed' => false, 'message' => 'Individual distribution amount is too low to process.'];
    }
    
    // Find the latest paid_at date from the bills we're distributing
    // This will be our new distributed_up_to_date
    if ($lastDistributionDate) {
        $stmt = $pdo->prepare("
            SELECT MAX(paid_at) as latest_paid 
            FROM billing 
            WHERE status = 'Completed' 
            AND paid_at > ?
        ");
        $stmt->execute([$lastDistributionDate]);
    } else {
        $stmt = $pdo->query("
            SELECT MAX(paid_at) as latest_paid 
            FROM billing 
            WHERE status = 'Completed'
        ");
    }
    $latestPaidDate = $stmt->fetchColumn();
    
    // Create distribution record with distributed_up_to_date
    $stmt = $pdo->prepare("
        INSERT INTO money_back_distributions 
        (total_pool, premium_count, individual_amount, total_distributed, distribution_date, distributed_up_to_date) 
        VALUES (?, ?, ?, ?, NOW(), ?)
    ");
    $stmt->execute([
        $moneyBackPool,
        count($premiumMembers),
        $individualAmount,
        $moneyBackPool,
        $latestPaidDate
    ]);
    $distributionId = $pdo->lastInsertId();
    
    // Distribute to ALL Premium members
    $distributedCount = 0;
    foreach ($premiumMembers as $member) {
        // Check for duplicate transaction today
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM savings_transactions 
            WHERE user_id = ? AND category = 'Deposit' AND DATE(requested_at) = CURDATE()
        ");
        $stmt->execute([$member['id']]);
        $alreadyExists = $stmt->fetchColumn() > 0;
        
        if ($alreadyExists) {
            continue; // Skip if already has a deposit transaction today
        }
        
        // Create savings transaction
        $txnId = generateTransactionId('MBK');
        $no = getNextNo($pdo, 'savings_transactions');
        
        $stmt = $pdo->prepare("
            INSERT INTO savings_transactions 
            (no, transaction_id, user_id, category, amount, status, requested_at, processed_at) 
            VALUES (?, ?, ?, 'Deposit', ?, 'Completed', NOW(), NOW())
        ");
        $stmt->execute([$no, $txnId, $member['id'], $individualAmount]);
        
        // Update or create savings balance
        $stmt = $pdo->prepare("
            INSERT INTO savings (user_id, balance) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE balance = balance + ?
        ");
        $stmt->execute([$member['id'], $individualAmount, $individualAmount]);
        
        // Add to recipients
        $stmt = $pdo->prepare("
            INSERT INTO money_back_recipients 
            (distribution_id, user_id, user_name, amount, transaction_id, last_received, next_eligible) 
            VALUES (?, ?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR))
        ");
        $stmt->execute([
            $distributionId,
            $member['id'],
            $member['first_name'] . ' ' . $member['last_name'],
            $individualAmount,
            $txnId
        ]);
        
        $distributedCount++;
    }
    
    return [
        'distributed' => true, 
        'message' => "Successfully distributed " . formatMoney($moneyBackPool) . " to {$distributedCount} Premium members. Each received " . formatMoney($individualAmount) . "."
    ];
}

function getMoneyBackHistory(PDO $pdo): array {
    $stmt = $pdo->prepare("
        SELECT * FROM money_back_distributions 
        ORDER BY distribution_date DESC 
        LIMIT 12
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getUserMoneyBackTransactions(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("
        SELECT * FROM savings_transactions 
        WHERE user_id = ? AND category = 'Money Back' 
        ORDER BY requested_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getDistributionRecipients(PDO $pdo, int $distributionId): array {
    $stmt = $pdo->prepare("
        SELECT r.*, u.email 
        FROM money_back_recipients r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.distribution_id = ?
        ORDER BY r.user_name
    ");
    $stmt->execute([$distributionId]);
    return $stmt->fetchAll();
}

function getTotalMoneyBackDeducted(PDO $pdo, int $year): float {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) FROM company_income_deductions WHERE year = ?
    ");
    $stmt->execute([$year]);
    return (float)$stmt->fetchColumn();
}

// ──── File Upload Handler ───────────────────────────────────────
function uploadFile(array $file, string $folder): string|false {
    $allowed  = unserialize(UPLOAD_ALLOWED);
    $maxBytes = UPLOAD_MAX_MB * 1024 * 1024;

    if ($file['error'] !== UPLOAD_ERR_OK)         return false;
    if ($file['size'] > $maxBytes)                 return false;
    if (!in_array($file['type'], $allowed))        return false;

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('', true) . '.' . $ext;
    $dest     = UPLOAD_PATH . $folder . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return $folder . '/' . $filename;
    }
    return false;
}

// ── Premium slot checker ──────────────────────────────────────
function premiumSlotsAvailable(PDO $pdo): bool {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE account_type = 'Premium' AND status != 'Disabled'");
    return (int)$stmt->fetchColumn() < PREMIUM_MAX_SLOTS;
}

// ── Display flash message HTML ────────────────────────────────
function showFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';

    $type = $flash['type'];
    $msg  = $flash['message'];
    return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$msg}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}