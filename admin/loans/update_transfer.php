<?php
// ============================================================
//  admin/loans/update_transfer.php - AJAX endpoint for transfer status
// ============================================================
require_once __DIR__ . '/../../includes/admin_check.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['loan_id']) || !isset($input['transfer_done'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$loanId = (int)$input['loan_id'];
$transferDone = (int)$input['transfer_done'] ? 1 : 0;

// Verify loan exists and admin has access
$stmt = $pdo->prepare("SELECT id FROM loans WHERE id = ? LIMIT 1");
$stmt->execute([$loanId]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Loan not found']);
    exit;
}

// Update the transfer status
try {
    $stmt = $pdo->prepare("UPDATE loans SET transfer_done = ? WHERE id = ?");
    $stmt->execute([$transferDone, $loanId]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Transfer status updated',
        'transfer_done' => $transferDone
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
