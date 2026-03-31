<?php
// Reset Money Back System Script
// Run this to completely reset all money back data
// Place in: lending_system/admin/reset_money_back.php

require_once __DIR__ . '/../includes/admin_check.php';
require_once __DIR__ . '/../helpers.php';

try {
    $pdo->beginTransaction();
    
    // Get all money back transactions to subtract from savings
    $stmt = $pdo->query("SELECT user_id, amount FROM savings_transactions WHERE category = 'Money Back'");
    $moneyBackTxns = $stmt->fetchAll();
    
    // Subtract money back amounts from savings balances
    foreach ($moneyBackTxns as $txn) {
        $pdo->prepare("UPDATE savings SET balance = balance - ? WHERE user_id = ?")
            ->execute([$txn['amount'], $txn['user_id']]);
    }
    
    // Delete money back transactions from savings
    $pdo->exec("DELETE FROM savings_transactions WHERE category = 'Money Back'");
    
    // Delete money back recipient records
    $pdo->exec("DELETE FROM money_back_recipients");
    
    // Delete distribution history
    $pdo->exec("DELETE FROM money_back_distributions");
    
    // Delete income deductions
    $pdo->exec("DELETE FROM company_income_deductions");
    
    // Reset AUTO_INCREMENT counters
    $pdo->exec("ALTER TABLE money_back_recipients AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE money_back_distributions AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE company_income_deductions AUTO_INCREMENT = 1");
    
    $pdo->commit();
    
    // Also reset premium_since for all Premium users to simulate new registrations
    // Uncomment below if you want to reset their anniversary dates too
    // $pdo->exec("UPDATE users SET premium_since = NULL WHERE account_type = 'Premium'");
    
    echo '<div style="padding: 40px; text-align: center; font-family: sans-serif;">';
    echo '<h1 style="color: #16a34a;">✅ Money Back System Reset Complete!</h1>';
    echo '<p style="font-size: 18px;">All money back data has been cleared:</p>';
    echo '<ul style="list-style: none; padding: 0; font-size: 16px; color: #666;">';
    echo '<li>✓ Money back recipient records deleted</li>';
    echo '<li>✓ Distribution history cleared</li>';
    echo '<li>✓ Income deductions reset</li>';
    echo '<li>✓ Money back transactions removed from savings</li>';
    echo '<li>✓ Savings balances restored (subtracted money back amounts)</li>';
    echo '</ul>';
    echo '<p><a href="money_back.php" style="display: inline-block; padding: 12px 24px; background: #1a45a8; color: white; text-decoration: none; border-radius: 6px; margin-top: 20px;">Go to Money Back Page</a></p>';
    echo '</div>';
    
} catch (Exception $e) {
    $pdo->rollback();
    echo '<h1 style="color: #dc2626;">❌ Reset Failed</h1>';
    echo '<p>' . $e->getMessage() . '</p>';
}
