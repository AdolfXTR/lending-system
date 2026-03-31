<?php
// ============================================================
//  example_dashboard_modern.php
//  Example modern dashboard layout
//  Shows how to integrate modern design with your data
// ============================================================

// This is a TEMPLATE showing how to use the modern design
// Copy structure to your actual dashboard.php files

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/includes/session_check.php';

$pageTitle = 'Dashboard';

// ── Mock data for demonstration ──────────────────────────
// In your real code, fetch from database
$stats = [
    'active_loans' => 3,
    'total_owed' => 45250,
    'next_payment_due' => '1,500',
    'savings_balance' => 25000,
];

$recentTransactions = [
    ['id' => 1, 'type' => 'Loan Payment', 'amount' => '1,500.00', 'date' => '2026-03-28', 'status' => 'Completed'],
    ['id' => 2, 'type' => 'Interest Charged', 'amount' => '450.00', 'date' => '2026-03-27', 'status' => 'Pending'],
    ['id' => 3, 'type' => 'Savings Deposit', 'amount' => '5,000.00', 'date' => '2026-03-26', 'status' => 'Completed'],
];

$loans = [
    ['id' => 1, 'amount' => 50000, 'balance' => 35000, 'term' => '12 months', 'status' => 'Active', 'due' => '2026-04-15'],
    ['id' => 2, 'amount' => 30000, 'balance' => 28500, 'term' => '9 months', 'status' => 'Active', 'due' => '2026-04-20'],
];
?>

<?php require_once 'includes/modern_header.php'; ?>

<div class="page-header">
    <div class="page-title">
        <h1>Welcome back, <?= htmlspecialchars($_SESSION['first_name']) ?>! 👋</h1>
        <p>Here's your financial overview</p>
    </div>
    <div class="page-actions">
        <button class="btn btn--primary">
            <span>➕</span> Apply for Loan
        </button>
    </div>
</div>

<!-- ── Quick Stats Grid ────────────────────────────── -->
<div class="grid grid--4col">
    <!-- Stat Card 1: Active Loans -->
    <div class="card card--stat">
        <div class="stat-value">
            <div class="stat-label">Active Loans</div>
            <div class="stat-number"><?= $stats['active_loans'] ?></div>
            <div class="stat-change positive">
                <span>📈</span> 2 active
            </div>
        </div>
    </div>

    <!-- Stat Card 2: Total Owed -->
    <div class="card card--stat">
        <div class="stat-value">
            <div class="stat-label">Total Owed</div>
            <div class="stat-number">₱<?= number_format($stats['total_owed'], 0) ?></div>
            <div class="stat-change neutral">
                <span>📊</span> Due in 30 days
            </div>
        </div>
    </div>

    <!-- Stat Card 3: Next Payment -->
    <div class="card card--stat">
        <div class="stat-value">
            <div class="stat-label">Next Payment</div>
            <div class="stat-number">₱<?= $stats['next_payment_due'] ?></div>
            <div class="stat-change positive">
                <span>✓</span> On schedule
            </div>
        </div>
    </div>

    <!-- Stat Card 4: Savings -->
    <div class="card card--stat">
        <div class="stat-value">
            <div class="stat-label">Savings Balance</div>
            <div class="stat-number">₱<?= number_format($stats['savings_balance'], 0) ?></div>
            <div class="stat-change positive">
                <span>💰</span> Growing
            </div>
        </div>
    </div>
</div>

<!-- ── Content Row: 2 Columns ─────────────────────── -->
<div class="grid grid--2col">
    <!-- Left: Recent Transactions -->
    <div class="card">
        <h2>Recent Transactions</h2>
        <p style="color: var(--text-tertiary); font-size: 13px; margin-bottom: 16px;">Activity from the last 7 days</p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $txn): ?>
                    <tr>
                        <td>
                            <span><?= htmlspecialchars($txn['type']) ?></span>
                        </td>
                        <td>
                            <span class="fw-semibold">₱<?= $txn['amount'] ?></span>
                        </td>
                        <td>
                            <span class="text-muted"><?= date('M d, Y', strtotime($txn['date'])) ?></span>
                        </td>
                        <td>
                            <?php 
                                $statusClass = $txn['status'] === 'Completed' ? 'success' : 'warning';
                            ?>
                            <span class="badge badge--<?= $statusClass ?>"><?= $txn['status'] ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right: Quick Actions / Info -->
    <div class="card">
        <h2>Quick Actions</h2>
        <p style="color: var(--text-tertiary); font-size: 13px; margin-bottom: 20px;">Manage your account</p>

        <div class="flex flex-col gap-12" style="display: flex; flex-direction: column; gap: 12px;">
            <button class="btn btn--primary btn--full" style="justify-content: flex-start;">
                <span>📋</span> Make a Payment
            </button>
            <button class="btn btn--secondary btn--full" style="justify-content: flex-start;">
                <span>🏦</span> Top Up Savings
            </button>
            <button class="btn btn--secondary btn--full" style="justify-content: flex-start;">
                <span>📄</span> View Statements
            </button>
        </div>

        <!-- Info Boxes -->
        <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--border);">
            <h4 style="margin-bottom: 12px;">Account Status</h4>
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--success-light); border-radius: var(--radius-md);">
                <span style="font-size: 20px;">✓</span>
                <span style="color: var(--success); font-weight: 600;">All payments current</span>
            </div>
        </div>
    </div>
</div>

<!-- ── Active Loans Table ──────────────────────────── -->
<div class="card">
    <h2>Active Loans</h2>
    <p style="color: var(--text-tertiary); font-size: 13px; margin-bottom: 16px;">Manage your active loan accounts</p>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Loan ID</th>
                    <th>Amount</th>
                    <th>Balance</th>
                    <th>Term</th>
                    <th>Status</th>
                    <th>Due Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($loans as $loan): ?>
                <tr>
                    <td>
                        <span class="fw-semibold">#<?= str_pad($loan['id'], 6, '0', STR_PAD_LEFT) ?></span>
                    </td>
                    <td>
                        <span>₱<?= number_format($loan['amount'], 0) ?></span>
                    </td>
                    <td>
                        <span class="fw-semibold">₱<?= number_format($loan['balance'], 0) ?></span>
                    </td>
                    <td>
                        <span class="text-muted"><?= $loan['term'] ?></span>
                    </td>
                    <td>
                        <span class="badge badge--success"><?= $loan['status'] ?></span>
                    </td>
                    <td>
                        <span class="text-muted"><?= date('M d, Y', strtotime($loan['due'])) ?></span>
                    </td>
                    <td>
                        <a href="#" class="btn btn--sm btn--outline" style="font-size: 12px;">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/modern_footer.php'; ?>
