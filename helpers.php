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
function calculateMoneyBackDistribution(PDO $pdo): float {
    // Calculate total company income from all completed payments in the current year
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_due), 0) as total_income 
        FROM billing 
        WHERE status = 'Completed' 
        AND YEAR(paid_at) = YEAR(CURRENT_DATE)
    ");
    $stmt->execute();
    $totalIncome = (float)$stmt->fetchColumn();
    
    // Calculate 2% of total income
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
    $moneyBackPool = calculateMoneyBackDistribution($pdo);
    $premiumCount = getPremiumMemberCount($pdo);
    
    if ($premiumCount === 0) {
        return 0;
    }
    
    return $moneyBackPool / $premiumCount;
}

function distributeMoneyBack(PDO $pdo): array {
    $today = date('Y-m-d');
    
    // Get all premium members who are eligible for money back
    // Eligible = premium_since + 1 year has passed AND (never received OR last_received + 1 year has passed)
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.premium_since,
               COALESCE(
                   (SELECT MAX(last_received) FROM money_back_recipients WHERE user_id = u.id),
                   NULL
               ) as last_received,
               COALESCE(
                   (SELECT MAX(next_eligible) FROM money_back_recipients WHERE user_id = u.id),
                   DATE_ADD(u.premium_since, INTERVAL 1 YEAR)
               ) as next_eligible
        FROM users u
        WHERE u.account_type = 'Premium' 
        AND u.status = 'Active'
        AND u.premium_since IS NOT NULL
        AND DATE_ADD(u.premium_since, INTERVAL 1 YEAR) <= ?  -- Been Premium for at least 1 year
        AND (
            COALESCE(
                (SELECT MAX(next_eligible) FROM money_back_recipients WHERE user_id = u.id),
                DATE_ADD(u.premium_since, INTERVAL 1 YEAR)
            ) <= ?  -- Next eligible date has passed
        )
    ");
    $stmt->execute([$today, $today]);
    $eligibleMembers = $stmt->fetchAll();
    
    if (empty($eligibleMembers)) {
        return ['distributed' => false, 'message' => 'No Premium members are eligible for money back at this time. Each member receives money back once per year on their Premium anniversary.'];
    }
    
    // Calculate pool based on total income
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_due), 0) as total_income 
        FROM billing 
        WHERE status = 'Completed' 
        AND YEAR(paid_at) = YEAR(CURRENT_DATE)
    ");
    $stmt->execute();
    $totalIncome = (float)$stmt->fetchColumn();
    
    // Get total money back already deducted this year
    $currentYear = (int)date('Y');
    $totalDeducted = getTotalMoneyBackDeducted($pdo, $currentYear);
    $remainingIncome = $totalIncome - $totalDeducted;
    $moneyBackPool = $remainingIncome * 0.02;
    
    // Get total count of ALL premium members (for calculating individual amount)
    $totalPremiumCount = getPremiumMemberCount($pdo);
    
    if ($totalPremiumCount === 0 || $moneyBackPool <= 0) {
        return ['distributed' => false, 'message' => 'No money back available for distribution'];
    }
    
    // Calculate amount per member (divide pool by ALL premium members for fairness)
    $individualAmount = $moneyBackPool / $totalPremiumCount;
    
    if ($individualAmount <= 0) {
        return ['distributed' => false, 'message' => 'Insufficient funds for distribution'];
    }
    
    $distributedMembers = [];
    $pdo->beginTransaction();
    
    try {
        // Log the distribution first to get the ID
        $stmt = $pdo->prepare("
            INSERT INTO money_back_distributions 
            (total_pool, premium_count, individual_amount, total_distributed, distribution_date) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $totalToDistribute = $individualAmount * count($eligibleMembers);
        $stmt->execute([
            $moneyBackPool,
            count($eligibleMembers),
            $individualAmount,
            $totalToDistribute
        ]);
        $distributionId = $pdo->lastInsertId();
        
        foreach ($eligibleMembers as $member) {
            // Add money back to savings
            $stmt = $pdo->prepare("
                INSERT INTO savings (user_id, balance) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE balance = balance + ?
            ");
            $stmt->execute([$member['id'], $individualAmount, $individualAmount]);
            
            // Create transaction record
            $txnId = generateStandardTransactionId('MB');
            $no = getNextNo($pdo, 'savings_transactions');
            
            $stmt = $pdo->prepare("
                INSERT INTO savings_transactions 
                (no, transaction_id, user_id, category, amount, status, note, requested_at, processed_at) 
                VALUES (?, ?, ?, 'Money Back', ?, 'Completed', ?, NOW(), NOW())
            ");
            $stmt->execute([
                $no, 
                $txnId, 
                $member['id'], 
                $individualAmount, 
                'Money Back distribution from company earnings'
            ]);
            
            // Record recipient with anniversary tracking
            $lastReceived = $today;
            $nextEligible = date('Y-m-d', strtotime($lastReceived . ' +1 year'));
            
            $stmt = $pdo->prepare("
                INSERT INTO money_back_recipients 
                (distribution_id, user_id, user_name, amount, transaction_id, premium_since, last_received, next_eligible) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $distributionId,
                $member['id'],
                $member['first_name'] . ' ' . $member['last_name'],
                $individualAmount,
                $txnId,
                $member['premium_since'],
                $lastReceived,
                $nextEligible
            ]);
            
            $distributedMembers[] = [
                'user_id' => $member['id'],
                'name' => $member['first_name'] . ' ' . $member['last_name'],
                'amount' => $individualAmount,
                'transaction_id' => $txnId,
                'premium_since' => $member['premium_since'],
                'next_eligible' => $nextEligible
            ];
        }
        
        // Deduct from company income
        $stmt = $pdo->prepare("
            INSERT INTO company_income_deductions 
            (distribution_id, amount, year) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$distributionId, $totalToDistribute, $currentYear]);
        
        $pdo->commit();
        
        return [
            'distributed' => true,
            'message' => "Successfully distributed money back to " . count($eligibleMembers) . " premium member(s) on their anniversary",
            'total_distributed' => $totalToDistribute,
            'individual_amount' => $individualAmount,
            'members' => $distributedMembers,
            'distribution_id' => $distributionId
        ];
        
    } catch (Exception $e) {
        $pdo->rollback();
        return [
            'distributed' => false, 
            'message' => 'Distribution failed: ' . $e->getMessage()
        ];
    }
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