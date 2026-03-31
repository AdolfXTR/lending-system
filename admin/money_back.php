<?php
// ============================================================
//  admin/money_back.php - Money Back Distribution Management
// ============================================================
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../helpers.php';

$adminId = $_SESSION['admin_id'];

// Handle add income action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_income') {
    $amount = (float)($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $earnedAt = $_POST['earned_at'] ?? date('Y-m-d');
    
    if ($amount <= 0) {
        setFlash('danger', 'Please enter a valid amount greater than 0.');
    } else {
        // Insert into company_income table
        $stmt = $pdo->prepare("
            INSERT INTO company_income (amount, description, earned_at, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$amount, $description ?: 'Manual income entry', $earnedAt]);
        setFlash('success', formatMoney($amount) . ' has been added to company income.');
    }
    
    header('Location: money_back.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'distribute') {
    $result = distributeMoneyBack($pdo);
    
    if ($result['distributed']) {
        setFlash('success', $result['message']);
    } else {
        setFlash('danger', $result['message']);
    }
    
    header('Location: money_back.php');
    exit;
}

// Get current stats
$currentYear = (int)date('Y');
$premiumCount = getPremiumMemberCount($pdo);

$totalIncome = 0;
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total_due), 0) as total_income 
    FROM billing 
    WHERE status = 'Completed' 
    AND YEAR(paid_at) = YEAR(CURRENT_DATE)
");
$stmt->execute();
$totalIncome = (float)$stmt->fetchColumn();

// Get total money back deducted this year
$totalDeducted = getTotalMoneyBackDeducted($pdo, $currentYear);

// Calculate remaining income after deductions
$remainingIncome = $totalIncome - $totalDeducted;

// Calculate pool and individual amount
$moneyBackPool = $remainingIncome * 0.02;
$individualAmount = $premiumCount > 0 ? $moneyBackPool / $premiumCount : 0;

// Get all Premium members with their money back status
$premiumMembers = [];
$today = date('Y-m-d');
$eligibleCount = 0;

if ($premiumCount > 0) {
    // Use a simpler query to avoid MySQL forward reference issues
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.premium_since,
               (SELECT balance FROM savings WHERE user_id = u.id LIMIT 1) as savings_balance
        FROM users u
        WHERE u.account_type = 'Premium' AND u.status = 'Active'
        ORDER BY u.premium_since ASC
    ");
    $stmt->execute();
    $premiumMembers = $stmt->fetchAll();
    
    // Get money back status for each member separately
    foreach ($premiumMembers as &$m) {
        // Get last received date
        $stmt2 = $pdo->prepare("SELECT MAX(last_received) as last_rcvd, MAX(next_eligible) as next_el FROM money_back_recipients WHERE user_id = ?");
        $stmt2->execute([$m['id']]);
        $recipientData = $stmt2->fetch();
        
        $m['last_received'] = $recipientData['last_rcvd'] ?? null;
        
        if ($recipientData['next_el']) {
            $m['next_eligible'] = $recipientData['next_el'];
        } else {
            // First time - eligible 1 year after premium_since
            $m['next_eligible'] = date('Y-m-d', strtotime($m['premium_since'] . ' +1 year'));
        }
        
        // Check if eligible
        if ($m['next_eligible'] <= $today) {
            $eligibleCount++;
        }
    }
    unset($m);
}

// Get distribution history
$distributions = getMoneyBackHistory($pdo);

// Get recent money back transactions
$recentTransactions = [];
if ($premiumCount > 0) {
    $stmt = $pdo->prepare("
        SELECT st.*, u.first_name, u.last_name 
        FROM savings_transactions st
        JOIN users u ON st.user_id = u.id
        WHERE st.category = 'Money Back' 
        ORDER BY st.requested_at DESC 
        LIMIT 20
    ");
    $stmt->execute();
    $recentTransactions = $stmt->fetchAll();
}

$pageTitle = 'Money Back Distribution';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-cash-stack text-success"></i> Money Back Distribution</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addIncomeModal">
            <i class="bi bi-plus-lg"></i> Add Income
        </button>
        <button class="btn btn-success" onclick="confirmDistribution()" <?= $eligibleCount === 0 ? 'disabled' : '' ?>>
            <i class="bi bi-send"></i> <?= $eligibleCount === 0 ? 'All Members Paid for ' . $currentYear : 'Distribute Money Back (' . $eligibleCount . ' eligible)' ?>
        </button>
    </div>
</div>

<?= showFlash() ?>

<?php if ($eligibleCount === 0 && !empty($premiumMembers)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle-fill"></i>
    <strong>No Eligible Members:</strong> All Premium members have received their money back for their current anniversary period. Next distributions will be available when each member reaches their 1-year anniversary (based on their Premium registration date).
</div>
<?php elseif ($eligibleCount > 0): ?>
<div class="alert alert-success">
    <i class="bi bi-check-circle-fill"></i>
    <strong>Ready to Distribute:</strong> <?= $eligibleCount ?> member(s) are eligible to receive money back on their anniversary date. Each will receive <?= formatMoney($individualAmount) ?>.
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-4" style="border-top: 4px solid #1a45a8;">
            <div class="text-muted small mb-1">Total Company Income (Year)</div>
            <div class="fs-2 fw-bold text-primary"><?= formatMoney($totalIncome) ?></div>
            <div class="text-muted small mt-1">From completed payments</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-4" style="border-top: 4px solid #dc2626;">
            <div class="text-muted small mb-1">Money Back Distributed</div>
            <div class="fs-2 fw-bold text-danger"><?= formatMoney($totalDeducted) ?></div>
            <div class="text-muted small mt-1">Already paid out this year</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-4" style="border-top: 4px solid #16a34a;">
            <div class="text-muted small mb-1">Money Back Pool (2%)</div>
            <div class="fs-2 fw-bold text-success"><?= formatMoney($moneyBackPool) ?></div>
            <div class="text-muted small mt-1"><?= $eligibleCount > 0 ? formatMoney($individualAmount) . ' per member' : 'Waiting for anniversaries' ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center p-4" style="border-top: 4px solid #ea7c0a;">
            <div class="text-muted small mb-1">Premium Members</div>
            <div class="fs-2 fw-bold text-warning"><?= $premiumCount ?></div>
            <div class="text-muted small mt-1"><?= $eligibleCount > 0 ? '<span class="text-success">' . $eligibleCount . ' eligible now</span>' : 'None currently eligible' ?></div>
        </div>
    </div>
</div>

<!-- Premium Members List (Recipients) -->
<?php if (!empty($premiumMembers)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <span class="fw-bold"><i class="bi bi-people-fill text-primary"></i> Premium Members & Anniversary Status</span>
        <span class="badge bg-primary ms-2"><?= count($premiumMembers) ?> members</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Member Name</th>
                        <th>Email</th>
                        <th>Premium Since</th>
                        <th>Last Received</th>
                        <th>Next Eligible</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($premiumMembers as $i => $m): 
                        $isEligible = $m['next_eligible'] <= $today;
                        $statusClass = $isEligible ? 'success' : 'secondary';
                        $statusText = $isEligible ? 'Eligible Now' : 'Pending';
                        $anniversary = date('M d', strtotime($m['premium_since']));
                    ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td>
                            <a href="<?= APP_URL ?>/admin/users/view.php?id=<?= $m['id'] ?>" class="text-decoration-none fw-semibold">
                                <?= clean($m['first_name'] . ' ' . $m['last_name']) ?>
                            </a>
                        </td>
                        <td class="small text-muted"><?= clean($m['email']) ?></td>
                        <td>
                            <small class="text-muted"><?= date('M d, Y', strtotime($m['premium_since'])) ?></small><br>
                            <small class="text-info">Anniversary: <?= $anniversary ?> yearly</small>
                        </td>
                        <td>
                            <?php if ($m['last_received']): ?>
                                <small class="text-success"><?= date('M d, Y', strtotime($m['last_received'])) ?></small>
                            <?php else: ?>
                                <small class="text-muted">Never</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isEligible): ?>
                                <span class="badge bg-success">Now!</span>
                            <?php else: ?>
                                <small class="text-warning"><?= date('M d, Y', strtotime($m['next_eligible'])) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $statusClass ?>"><?= $statusText ?></span>
                            <?php if ($isEligible): ?>
                                <br><small class="text-success">+<?= formatMoney($individualAmount) ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Distribution History -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <span class="fw-bold"><i class="bi bi-clock-history text-muted"></i> Distribution History</span>
    </div>
    <div class="card-body p-0">
        <div class="accordion" id="distributionAccordion">
            <?php if (empty($distributions)): ?>
                <div class="text-center py-5">
                    <div style="font-size:48px;margin-bottom:12px;">📊</div>
                    <div style="font-size:16px;font-weight:600;color:#6b7280;margin-bottom:6px;">No distributions yet</div>
                    <div style="font-size:13px;color:#9ca3af;">Distribute money back to premium members to see history here.</div>
                </div>
            <?php else: ?>
                <?php foreach ($distributions as $i => $d): 
                    $recipients = getDistributionRecipients($pdo, $d['id']);
                    $accordionId = 'dist' . $d['id'];
                ?>
                <div class="accordion-item border-0">
                    <h2 class="accordion-header">
                        <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $accordionId ?>">
                            <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                <div>
                                    <span class="badge bg-secondary me-2">#<?= $i + 1 ?></span>
                                    <strong><?= date('M d, Y h:i A', strtotime($d['distribution_date'])) ?></strong>
                                </div>
                                <div class="d-flex gap-3 text-end">
                                    <div>
                                        <small class="text-muted d-block">Total Pool</small>
                                        <strong><?= formatMoney($d['total_pool']) ?></strong>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Members</small>
                                        <strong class="text-warning"><?= $d['premium_count'] ?></strong>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Distributed</small>
                                        <strong class="text-success"><?= formatMoney($d['total_distributed']) ?></strong>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </h2>
                    <div id="<?= $accordionId ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#distributionAccordion">
                        <div class="accordion-body p-0">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Recipient</th>
                                        <th>Email</th>
                                        <th>Amount Received</th>
                                        <th>Transaction ID</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recipients as $r): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= APP_URL ?>/admin/users/view.php?id=<?= $r['user_id'] ?>" class="text-decoration-none fw-semibold">
                                                <?= clean($r['user_name']) ?>
                                            </a>
                                        </td>
                                        <td class="small text-muted"><?= clean($r['email'] ?? 'N/A') ?></td>
                                        <td class="text-success fw-bold">+<?= formatMoney($r['amount']) ?></td>
                                        <td class="font-monospace small"><?= clean($r['transaction_id']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Divider -->
<div class="row my-4">
    <div class="col-12">
        <hr style="border-color: rgba(0,0,0,0.1); margin: 2rem 0;">
    </div>
</div>

<!-- Recent Money Back Transactions -->
<?php if (!empty($recentTransactions)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3">
        <span class="fw-bold"><i class="bi bi-list-ul text-muted"></i> Recent Money Back Transactions</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Transaction ID</th>
                        <th>Member</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $t): ?>
                    <tr>
                        <td class="font-monospace small"><?= clean($t['transaction_id']) ?></td>
                        <td><?= clean($t['first_name'] . ' ' . $t['last_name']) ?></td>
                        <td class="text-success fw-bold">+<?= formatMoney($t['amount']) ?></td>
                        <td class="small text-muted"><?= date('M d, Y h:i A', strtotime($t['requested_at'])) ?></td>
                        <td class="small text-muted"><?= clean($t['note'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Distribution Confirmation Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-warning"><i class="bi bi-exclamation-triangle"></i> Confirm Distribution</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>Warning:</strong> This will distribute <?= formatMoney($individualAmount) ?> to each of the <?= $premiumCount ?> Premium members. This action cannot be undone. Proceed?
                </div>
                
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="text-muted small">Total Pool</div>
                        <div class="fw-bold"><?= formatMoney($moneyBackPool) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Premium Members</div>
                        <div class="fw-bold"><?= $premiumCount ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Individual Amount</div>
                        <div class="fw-bold text-success"><?= formatMoney($individualAmount) ?></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Total to Distribute</div>
                        <div class="fw-bold text-primary"><?= formatMoney($individualAmount * $premiumCount) ?></div>
                    </div>
                </div>
                
                <div class="form-text">
                    Each Premium member will receive <?= formatMoney($individualAmount) ?> added to their savings account.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="distribute">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-send"></i> Confirm Distribution
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Income Modal -->
<div class="modal fade" id="addIncomeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title text-primary"><i class="bi bi-plus-lg"></i> Add Company Income</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" name="amount" class="form-control" required min="0.01" step="0.01" placeholder="Enter income amount">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g., Loan interest, fees, etc.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Date Earned</label>
                        <input type="date" name="earned_at" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <input type="hidden" name="action" value="add_income">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Add Income
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDistribution() {
    if (<?= $premiumCount ?> === 0) {
        alert('No Premium members to distribute to.');
        return;
    }
    
    if (<?= $individualAmount ?> <= 0) {
        alert('No money back available for distribution.');
        return;
    }
    
    new bootstrap.Modal(document.getElementById('confirmModal')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
