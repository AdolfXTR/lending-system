<?php
require_once 'config.php';
require_once 'includes/header.php';
?>

<div class="page-wrapper">
    <div class="page-header">
        <div>
            <h1 class="page-title">Dark Mode Test</h1>
            <p class="page-subtitle">Test your dark mode toggle functionality</p>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card-lending">
            <div class="stat-icon-lending blue">💰</div>
            <div class="stat-info-lending">
                <div class="stat-label">Total Balance</div>
                <div class="stat-value blue">$12,450</div>
                <div class="stat-sub">+12.5% from last month</div>
            </div>
        </div>
        
        <div class="stat-card-lending">
            <div class="stat-icon-lending gold">🏦</div>
            <div class="stat-info-lending">
                <div class="stat-label">Active Loans</div>
                <div class="stat-value gold">3</div>
                <div class="stat-sub">2 pending approval</div>
            </div>
        </div>
        
        <div class="stat-card-lending">
            <div class="stat-icon-lending green">📈</div>
            <div class="stat-info-lending">
                <div class="stat-label">Credit Score</div>
                <div class="stat-value green">750</div>
                <div class="stat-sub">Excellent rating</div>
            </div>
        </div>
    </div>

    <div class="card-lending">
        <div class="card-header-lending">
            <h3 class="card-title-lending">
                <span class="card-title-dot blue"></span>
                Recent Transactions
            </h3>
        </div>
        <div class="card-body-lending">
            <table class="table-lending">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="txn-id-mono">TXN001</span></td>
                        <td>2024-03-15</td>
                        <td>Loan Payment</td>
                        <td class="text-success">+$500.00</td>
                        <td><span class="badge-lending completed">Completed</span></td>
                    </tr>
                    <tr>
                        <td><span class="txn-id-mono">TXN002</span></td>
                        <td>2024-03-14</td>
                        <td>Loan Disbursement</td>
                        <td class="text-danger">-$2,000.00</td>
                        <td><span class="badge-lending active">Active</span></td>
                    </tr>
                    <tr>
                        <td><span class="txn-id-mono">TXN003</span></td>
                        <td>2024-03-13</td>
                        <td>Interest Payment</td>
                        <td class="text-danger">-$45.50</td>
                        <td><span class="badge-lending completed">Completed</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-lending">
        <div class="card-header-lending">
            <h3 class="card-title-lending">
                <span class="card-title-dot gold"></span>
                Quick Actions
            </h3>
        </div>
        <div class="card-body-lending">
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <button class="btn-lending-primary">
                    <i class="bi bi-plus-circle"></i>
                    Apply for Loan
                </button>
                <button class="btn-lending-outline">
                    <i class="bi bi-eye"></i>
                    View Statements
                </button>
                <button class="btn-lending-gold">
                    <i class="bi bi-calculator"></i>
                    Loan Calculator
                </button>
            </div>
        </div>
    </div>

    <div class="alert-lending info">
        <i class="bi bi-info-circle"></i>
        <div>
            <strong>Dark Mode Test:</strong> Click the 🌙/☀️ toggle button in the navbar to test dark mode functionality. Your preference will be saved automatically.
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
