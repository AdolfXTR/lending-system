<?php
// ============================================================
//  admin/money_back.php - Money Back Distribution Management
// ============================================================
require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../helpers.php';

$adminId = $_SESSION['admin_id'];

// Handle distribute action
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
$premiumCount = getPremiumMemberCount($pdo);

// Calculate NEW undistributed income (only interest from bills paid after last distribution)
$newIncome = calculateNewMoneyBackIncome($pdo);
$totalIncome = $newIncome; // This is now only the undistributed income

// Calculate distribution: (New Income x 0.02) ÷ ALL Premium members
$moneyBackPool = $newIncome * 0.02;
$individualAmount = $premiumCount > 0 ? $moneyBackPool / $premiumCount : 0;

// Get last distribution date for display
$lastDistributionDate = getLastDistributionDate($pdo);

// Check if there's new income to distribute
$hasNewIncome = $newIncome > 0;

// Get all Premium members
$premiumMembers = [];
if ($premiumCount > 0) {
    $stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email,
               COALESCE(s.balance, 0) as savings_balance
        FROM users u
        LEFT JOIN savings s ON s.user_id = u.id
        WHERE u.account_type = 'Premium' 
        AND u.status = 'Active'
        ORDER BY u.first_name, u.last_name
    ");
    $stmt->execute();
    $premiumMembers = $stmt->fetchAll();
}

// Get distribution history
$distributions = getMoneyBackHistory($pdo);

$pageTitle = 'Money Back Distribution';
require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0"><i class="bi bi-cash-stack text-success"></i> Money Back Distribution</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#distributionModal" <?= !$hasNewIncome || $premiumCount === 0 ? 'disabled' : '' ?>>
            <i class="bi bi-send"></i> <?= !$hasNewIncome ? 'No New Income to Distribute' : 'Distribute Money Back' ?>
        </button>
    </div>
</div>

<?= showFlash() ?>

<?php if (!$hasNewIncome): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <strong>No New Income:</strong> All interest income has already been distributed, or no new completed billing payments have been received since the last distribution.
    <?php if ($lastDistributionDate): ?>
    <br><small class="text-muted">Last distribution: <?= date('M d, Y', strtotime($lastDistributionDate)) ?></small>
    <?php endif; ?>
</div>
<?php elseif ($premiumCount === 0): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle-fill"></i>
    <strong>No Premium Members:</strong> There are no active Premium members to distribute money back to.
</div>
<?php else: ?>
<div class="alert alert-success">
    <i class="bi bi-check-circle-fill"></i>
    <strong>Ready to Distribute:</strong> <?= formatMoney($moneyBackPool) ?> (2% of new income) will be distributed equally among <?= $premiumCount ?> Premium members. Each will receive <?= formatMoney($individualAmount) ?>.
    <?php if ($lastDistributionDate): ?>
    <br><small class="text-muted">Calculating income since last distribution: <?= date('M d, Y', strtotime($lastDistributionDate)) ?></small>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-top: 4px solid #1e40af;">
            <div class="card-body text-center">
                <div class="text-muted small mb-2">New Undistributed Income</div>
                <h4 class="fw-bold text-primary"><?= formatMoney($totalIncome) ?></h4>
                <div class="text-muted small">Interest since last distribution</div>
                <?php if ($lastDistributionDate): ?>
                <div class="text-muted small mt-1"><i class="bi bi-clock-history"></i> Since <?= date('M d, Y', strtotime($lastDistributionDate)) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-top: 4px solid #16a34a;">
            <div class="card-body text-center">
                <div class="text-muted small mb-2">Distribution Pool (2%)</div>
                <h4 class="fw-bold text-success"><?= formatMoney($moneyBackPool) ?></h4>
                <div class="text-muted small">Available for distribution</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-top: 4px solid #0891b2;">
            <div class="card-body text-center">
                <div class="text-muted small mb-2">Premium Members</div>
                <h4 class="fw-bold text-info"><?= $premiumCount ?></h4>
                <div class="text-muted small">Active members</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="border-top: 4px solid #ca8a04;">
            <div class="card-body text-center">
                <div class="text-muted small mb-2">Each Receives</div>
                <h4 class="fw-bold text-warning"><?= formatMoney($individualAmount) ?></h4>
                <div class="text-muted small">Equal share per member</div>
            </div>
        </div>
    </div>
</div>

<!-- Premium Members List -->
<?php if (!empty($premiumMembers)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0">
        <h6 class="mb-0"><i class="bi bi-people-fill text-primary"></i> Premium Members (Will Receive Equal Share)</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Member</th>
                        <th>Email</th>
                        <th>Current Savings</th>
                        <th>Will Receive</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($premiumMembers as $member): 
                        $initials = strtoupper(substr($member['first_name'], 0, 1) . substr($member['last_name'], 0, 1));
                    ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 14px; font-weight: bold;">
                                    <?= $initials ?>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?= clean($member['first_name'] . ' ' . $member['last_name']) ?></div>
                                    <small class="text-muted">ID: <?= $member['id'] ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?= clean($member['email']) ?></td>
                        <td><?= formatMoney($member['savings_balance']) ?></td>
                        <td class="fw-bold text-success"><?= formatMoney($individualAmount) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Distribution History -->
<?php if (!empty($distributions)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0">
        <h6 class="mb-0"><i class="bi bi-clock-history text-warning"></i> Distribution History</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Year</th>
                        <th>Date Distributed</th>
                        <th>Total Distributed</th>
                        <th>Members Count</th>
                        <th>Per Member Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($distributions as $dist): ?>
                    <tr>
                        <td><strong><?= date('Y', strtotime($dist['distribution_date'])) ?></strong></td>
                        <td><?= date('M d, Y H:i', strtotime($dist['distribution_date'])) ?></td>
                        <td class="fw-bold text-success"><?= formatMoney($dist['total_distributed']) ?></td>
                        <td><?= $dist['premium_count'] ?> members</td>
                        <td class="fw-bold text-info"><?= formatMoney($dist['individual_amount']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Distribution Confirmation Modal -->
<div class="modal fade" id="distributionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-send-fill text-success me-2"></i>
                    Confirm Money Back Distribution
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div>
                        <strong>Important:</strong> This will distribute <strong><?= formatMoney($moneyBackPool) ?></strong> from new undistributed interest only. Once distributed, this interest cannot be distributed again.
                    </div>
                </div>
                
                <h6 class="fw-bold mb-3">Distribution Summary</h6>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Total Income:</span>
                                    <strong class="text-primary"><?= formatMoney($totalIncome) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Distribution Pool (2%):</span>
                                    <strong class="text-success"><?= formatMoney($moneyBackPool) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card bg-light mb-4">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="text-muted small">Premium Members</div>
                                <div class="fs-4 fw-bold text-info"><?= $premiumCount ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Each Receives</div>
                                <div class="fs-4 fw-bold text-warning"><?= formatMoney($individualAmount) ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small">Total to Distribute</div>
                                <div class="fs-4 fw-bold text-success"><?= formatMoney($moneyBackPool) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($premiumMembers)): ?>
                <h6 class="fw-bold mb-3">Premium Members Who Will Receive</h6>
                <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                    <table class="table table-sm">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Member</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($premiumMembers as $member): ?>
                            <tr>
                                <td><?= clean($member['first_name'] . ' ' . $member['last_name']) ?></td>
                                <td class="text-end fw-bold text-success"><?= formatMoney($individualAmount) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="distribute">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-send-fill me-1"></i>
                        Confirm Distribution
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
