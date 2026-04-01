<?php
// ============================================================
//  admin/registrations/view.php
// ============================================================
require_once __DIR__ . '/../../includes/admin_check.php';
require_once __DIR__ . '/../../helpers.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(APP_URL . '/admin/registrations/index.php');

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) redirect(APP_URL . '/admin/registrations/index.php');

$pageTitle = 'Review Application — ' . $user['first_name'] . ' ' . $user['last_name'];

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<style>
/* Enhanced Application Review Styles */
.app-header {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 32px;
    border: 1px solid rgba(0,0,0,0.05);
    position: relative;
}

.sticky-actions {
    position: sticky;
    top: 70px;
    z-index: 100;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.app-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 700;
    margin-right: 20px;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.app-title {
    font-size: 28px;
    font-weight: 800;
    color: #1a202c;
    margin: 0;
    display: flex;
    align-items: center;
}

.app-subtitle {
    color: #64748b;
    font-size: 15px;
    margin: 4px 0 0 0;
}

.status-badge-active {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.pulsing-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #10b981;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.action-buttons {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn-action {
    padding: 12px 24px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    text-decoration: none;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.btn-approve {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-reject {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-direct-reject {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.btn-request-info {
    background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
}

/* Sticky Bottom Action Bar */
.sticky-bottom-actions {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-top: 1px solid #e2e8f0;
    padding: 16px 32px;
    z-index: 1000;
    display: flex;
    justify-content: center;
    gap: 16px;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
}

/* Enhanced Info Cards */
.info-card {
    background: white;
    border-radius: 16px;
    border: 1px solid rgba(0,0,0,0.08);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
    margin-bottom: 24px;
}

.info-card:hover {
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

.info-card-header {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 20px 24px;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    display: flex;
    align-items: center;
    gap: 12px;
}

.info-card-title {
    font-size: 16px;
    font-weight: 700;
    color: #374151;
    margin: 0;
}

.info-card-body {
    padding: 24px;
}

/* Enhanced 2-Column Grid Layout */
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.info-grid-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    border-radius: 12px;
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.2s ease;
}

.info-grid-item:hover {
    background: #f8fafc;
}

.info-grid-item.important {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 1px solid #f59e0b;
}

.info-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: white;
    flex-shrink: 0;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

.info-icon.phone { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.info-icon.email { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
.info-icon.address { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.info-icon.money { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.info-icon.id { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }

.info-label {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.info-value {
    font-size: 15px;
    color: #1e293b;
    font-weight: 500;
    word-break: break-word;
}

/* Document Cards with Lightbox */
.document-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
}

.document-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    padding: 24px;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.document-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.15);
    transform: translateY(-4px);
}

.document-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-required {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #f59e0b;
}

.badge-verified {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.document-icon {
    width: 56px;
    height: 56px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    color: white;
    font-size: 24px;
    transition: all 0.3s ease;
}

.document-card:hover .document-icon {
    transform: scale(1.1) rotate(5deg);
}

.document-title {
    font-size: 15px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 16px;
}

.document-preview {
    margin: 16px 0;
    border-radius: 12px;
    overflow: hidden;
    background: #f8fafc;
    min-height: 140px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.document-preview:hover {
    transform: scale(1.05);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.document-preview img {
    max-width: 100%;
    max-height: 200px;
    object-fit: cover;
    transition: all 0.3s ease;
}

.document-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: #f1f5f9;
    color: #475569;
    text-decoration: none;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
    cursor: pointer;
}

.document-link:hover {
    background: #e2e8f0;
    color: #334155;
    transform: translateY(-1px);
}

/* Lightbox Modal */
.lightbox-modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    backdrop-filter: blur(5px);
}

.lightbox-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    max-width: 90%;
    max-height: 90%;
}

.lightbox-content img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 8px;
}

.lightbox-close {
    position: absolute;
    top: 20px;
    right: 40px;
    color: white;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
}

.lightbox-close:hover {
    color: #f1f5f9;
}

/* Enhanced Verification Cards */
.verification-progress {
    text-align: center;
    margin-bottom: 24px;
    padding: 16px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.progress-text {
    font-size: 16px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
    transition: width 0.3s ease;
}

.verification-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
}

.verification-card {
    border-radius: 16px;
    padding: 28px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.verification-card.pending {
    border-color: #f59e0b;
    background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%);
}

.verification-card.verified {
    border-color: #10b981;
    background: linear-gradient(135deg, #ffffff 0%, #ecfdf5 100%);
}

.verification-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.verification-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--color-start) 0%, var(--color-end) 100%);
}

.verification-card.pending::before {
    --color-start: #f59e0b;
    --color-end: #d97706;
}

.verification-card.verified::before {
    --color-start: #10b981;
    --color-end: #059669;
}

.verification-checkbox {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 24px;
    height: 24px;
    border: 2px solid #d1d5db;
    border-radius: 6px;
    background: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.verification-checkbox.checked {
    background: #10b981;
    border-color: #10b981;
    color: white;
}

.verification-checkbox:hover {
    border-color: #3b82f6;
}

.verification-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}

.verification-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.verification-card.pending .verification-icon {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}

.verification-card.verified .verification-icon {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.verification-title {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.verification-content {
    margin-bottom: 20px;
    background: rgba(255, 255, 255, 0.7);
    border-radius: 12px;
    padding: 16px;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.verification-label {
    font-size: 11px;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 6px;
}

.verification-value {
    font-size: 16px;
    font-weight: 600;
    color: #111827;
    word-break: break-all;
}

.verification-alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    font-size: 14px;
    line-height: 1.5;
}

.verification-card.pending .verification-alert {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
    border: 1px solid #f59e0b;
}

.verification-card.verified .verification-alert {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
    border: 1px solid #10b981;
}

.verification-btn {
    width: 100%;
    padding: 14px 20px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 15px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all 0.3s ease;
    border: 2px solid;
    background: transparent;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.verification-card.pending .verification-btn {
    border-color: #f59e0b;
    color: #92400e;
}

.verification-card.pending .verification-btn:hover {
    background: #f59e0b;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(245, 158, 11, 0.3);
}

.verification-card.verified .verification-btn {
    border-color: #10b981;
    color: #065f46;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.verification-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

/* Custom Approval Modal */
.approval-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1050;
    backdrop-filter: blur(4px);
}

.approval-modal-content {
    background: white;
    border-radius: 16px;
    padding: 0;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
    animation: approvalModalSlideIn 0.3s ease-out;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

@keyframes approvalModalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.approval-modal-header {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    padding: 24px 24px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.approval-modal-title {
    font-size: 20px;
    font-weight: 700;
    color: #ffffff;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.approval-modal-body {
    padding: 24px;
    background: white;
    color: #374151;
}

.approval-warning {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.approval-warning i {
    color: #ef4444;
    font-size: 18px;
    margin-top: 2px;
    flex-shrink: 0;
}

.approval-warning-text {
    color: #991b1b;
    font-size: 14px;
    line-height: 1.5;
    font-weight: 500;
}

.approval-modal-footer {
    padding: 20px 24px 24px;
    background: white;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    border-top: 1px solid #e5e7eb;
}

.approval-modal-btn {
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    font-family: inherit;
    min-width: 120px;
}

.approval-modal-btn-cancel {
    background: transparent;
    color: #6b7280;
    border: 1.5px solid #d1d5db;
}

.approval-modal-btn-cancel:hover {
    background: #f9fafb;
    color: #374151;
    border-color: #9ca3af;
}

.approval-modal-btn-confirm {
    background: #10b981;
    color: #ffffff;
    font-weight: 700;
}

.approval-modal-btn-confirm:hover {
    background: #059669;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

/* Disabled Approve Button */
.btn-approve:disabled {
    background: #9ca3af !important;
    color: #e5e7eb !important;
    cursor: not-allowed !important;
    opacity: 0.6 !important;
    transform: none !important;
    box-shadow: none !important;
}

.btn-approve:disabled:hover {
    transform: none !important;
    box-shadow: none !important;
}

.verification-tooltip {
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #1f2937;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 12px;
    white-space: nowrap;
    z-index: 1000;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
    margin-top: 4px;
}

.verification-tooltip.show {
    opacity: 1;
}

.verification-tooltip::before {
    content: '';
    position: absolute;
    top: -4px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 4px solid transparent;
    border-right: 4px solid transparent;
    border-bottom: 4px solid #1f2937;
}

/* Responsive Design */
@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .verification-grid {
        grid-template-columns: 1fr;
    }
    
    .sticky-bottom-actions {
        padding: 12px 16px;
        gap: 8px;
    }
    
    .btn-action {
        padding: 10px 16px;
        font-size: 13px;
    }
    
    .app-header {
        padding: 24px;
    }
    
    .app-title {
        font-size: 22px;
    }
    
    .sticky-actions {
        position: static;
        margin-top: 16px;
    }
}
</style>

<!-- Application Header -->
<div class="app-header">
    <div style="display: flex; align-items: center;">
        <div class="app-avatar">
            <?= strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1)) ?>
        </div>
        <div>
            <a href="index.php" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left"></i> Back to Applications
            </a>
            <h1 class="app-title">
                <?= clean($user['first_name'] . ' ' . $user['last_name']) ?>
            </h1>
            <p class="app-subtitle">Application Review • ID #<?= $user['id'] ?> • Applied <?= date('F j, Y', strtotime($user['created_at'])) ?></p>
        </div>
    </div>
    <?php if ($user['status'] === 'Pending'): ?>
        <div class="sticky-actions">
            <form method="POST" action="approve.php" id="approveForm" style="display: inline;">
                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                <button type="button" id="approveBtn" class="btn-action btn-approve" disabled onclick="handleApproveClick(event)" style="position: relative;">
                    <i class="bi bi-check-circle"></i> Approve
                    <span class="verification-tooltip" id="approveTooltip">Complete all verifications before approving</span>
                </button>
            </form>
            <button class="btn-action btn-reject" data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="bi bi-x-circle"></i> Reject
            </button>
        </div>
    <?php else: ?>
        <div class="status-badge-active">
            <span class="pulsing-dot"></span>
            <?= $user['status'] ?>
        </div>
    <?php endif; ?>
</div>

<?= showFlash() ?>

<div class="row g-3">

    <!-- Personal Info & Employment -->
    <div class="row g-4 mb-4">
        <!-- Personal Information -->
        <div class="col-md-6">
            <div class="info-card h-100">
                <div class="info-card-header">
                    <i class="bi bi-person text-primary"></i>
                    <h3 class="info-card-title">Personal Information</h3>
                </div>
                <div class="info-card-body">
                    <div class="info-grid">
                        <div class="info-grid-item">
                            <div class="info-icon">
                                <i class="bi bi-person"></i>
                            </div>
                            <div>
                                <div class="info-label">Full Name</div>
                                <div class="info-value"><?= clean($user['first_name'] . ' ' . $user['last_name']) ?></div>
                            </div>
                        </div>
                        <div class="info-grid-item">
                            <div class="info-icon">
                                <i class="bi bi-gender-ambiguous"></i>
                            </div>
                            <div>
                                <div class="info-label">Gender</div>
                                <div class="info-value"><?= clean($user['gender'] ?? '—') ?></div>
                            </div>
                        </div>
                        <div class="info-grid-item">
                            <div class="info-icon">
                                <i class="bi bi-calendar"></i>
                            </div>
                            <div>
                                <div class="info-label">Birthday</div>
                                <div class="info-value"><?= $user['birthday'] ? date('F d, Y', strtotime($user['birthday'])) : '—' ?></div>
                            </div>
                        </div>
                        <div class="info-grid-item">
                            <div class="info-icon">
                                <i class="bi bi-hourglass"></i>
                            </div>
                            <div>
                                <div class="info-label">Age</div>
                                <div class="info-value"><?= $user['age'] ?? '—' ?></div>
                            </div>
                        </div>
                        <div class="info-grid-item">
                            <div class="info-icon address">
                                <i class="bi bi-geo-alt"></i>
                            </div>
                            <div>
                                <div class="info-label">Address</div>
                                <div class="info-value"><?= clean($user['address'] ?? '—') ?></div>
                            </div>
                        </div>
                        <div class="info-grid-item">
                            <div class="info-icon email">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <div>
                                <div class="info-label">Email</div>
                                <div class="info-value"><?= clean($user['email']) ?></div>
                            </div>
                        </div>
                        <div class="info-grid-item">
                            <div class="info-icon phone">
                                <i class="bi bi-telephone"></i>
                            </div>
                            <div>
                                <div class="info-label">Contact</div>
                                <div class="info-value"><?= clean($user['contact_number'] ?? '—') ?></div>
                            </div>
                        </div>
                        <div class="info-grid-item important">
                            <div class="info-icon id">
                                <i class="bi bi-building"></i>
                            </div>
                            <div>
                                <div class="info-label">TIN</div>
                                <div class="info-value"><?= clean($user['tin_number'] ?? '—') ?></div>
                            </div>
                        </div>
                        <div class="info-grid-item">
                            <div class="info-icon">
                                <i class="bi bi-award"></i>
                            </div>
                            <div>
                                <div class="info-label">Account Type</div>
                                <div class="info-value">
                                    <span class="badge bg-<?= $user['account_type']==='Premium'?'success':'secondary' ?>">
                                        <?= $user['account_type'] ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employment & Bank Details -->
        <div class="col-md-6">
            <div class="info-card h-100">
                <div class="info-card-header">
                    <i class="bi bi-briefcase text-success"></i>
                    <h3 class="info-card-title">Employment & Bank Details</h3>
                </div>
                <div class="info-card-body">
                    <div class="mb-4">
                        <h4 class="text-muted text-uppercase small mb-3">Employment</h4>
                        <div class="info-grid">
                            <div class="info-grid-item">
                                <div class="info-icon">
                                    <i class="bi bi-building"></i>
                                </div>
                                <div>
                                    <div class="info-label">Company</div>
                                    <div class="info-value"><?= clean($user['company_name'] ?? '—') ?></div>
                                </div>
                            </div>
                            <div class="info-grid-item">
                                <div class="info-icon address">
                                    <i class="bi bi-geo-alt"></i>
                                </div>
                                <div>
                                    <div class="info-label">Address</div>
                                    <div class="info-value"><?= clean($user['company_address'] ?? '—') ?></div>
                                </div>
                            </div>
                            <div class="info-grid-item">
                                <div class="info-icon phone">
                                    <i class="bi bi-telephone"></i>
                                </div>
                                <div>
                                    <div class="info-label">Phone</div>
                                    <div class="info-value"><?= clean($user['company_phone'] ?? '—') ?></div>
                                </div>
                            </div>
                            <div class="info-grid-item">
                                <div class="info-icon">
                                    <i class="bi bi-briefcase"></i>
                                </div>
                                <div>
                                    <div class="info-label">Position</div>
                                    <div class="info-value"><?= clean($user['position'] ?? '—') ?></div>
                                </div>
                            </div>
                            <div class="info-grid-item important">
                                <div class="info-icon money">
                                    <i class="bi bi-currency-dollar"></i>
                                </div>
                                <div>
                                    <div class="info-label">Monthly Income</div>
                                    <div class="info-value"><?= !empty($user['monthly_earnings']) ? formatMoney((float)$user['monthly_earnings']) : '—' ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-muted text-uppercase small mb-3">Bank Information</h4>
                        <div class="info-grid">
                            <div class="info-grid-item">
                                <div class="info-icon">
                                    <i class="bi bi-bank"></i>
                                </div>
                                <div>
                                    <div class="info-label">Bank Name</div>
                                    <div class="info-value"><?= clean($user['bank_name'] ?? '—') ?></div>
                                </div>
                            </div>
                            <div class="info-grid-item">
                                <div class="info-icon">
                                    <i class="bi bi-credit-card"></i>
                                </div>
                                <div>
                                    <div class="info-label">Account No.</div>
                                    <div class="info-value"><?= clean($user['bank_account_number'] ?? '—') ?></div>
                                </div>
                            </div>
                            <div class="info-grid-item">
                                <div class="info-icon">
                                    <i class="bi bi-person-badge"></i>
                                </div>
                                <div>
                                    <div class="info-label">Card Holder</div>
                                    <div class="info-value"><?= clean($user['card_holder_name'] ?? '—') ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submitted Documents -->
    <div class="info-card mb-4">
        <div class="info-card-header">
            <i class="bi bi-file-earmark-text text-warning"></i>
            <h3 class="info-card-title">Submitted Documents</h3>
        </div>
        <div class="info-card-body">
            <div class="document-grid">
                <?php
                $docs = [
                    'proof_of_billing' => ['Proof of Billing', 'bi-file-earmark-text', 'required'],
                    'valid_id'         => ['Valid ID (Primary)', 'bi-person-badge', 'required'],
                    'coe'              => ['Certificate of Employment', 'bi-briefcase', 'verified'],
                ];
                foreach ($docs as $field => $info):
                    $file = $user[$field] ?? null;
                ?>
                <div class="document-card">
                    <div class="document-badge badge-<?= $info[2] ?>" id="badge-<?= $field ?>">
                        <?= $info[2] ?>
                    </div>
                    <div class="document-icon">
                        <i class="<?= $info[1] ?>"></i>
                    </div>
                    <div class="document-title"><?= $info[0] ?></div>
                    <?php if ($file): ?>
                        <?php
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        $url = APP_URL . '/uploads/' . $file;
                        ?>
                        <div class="document-preview" onclick="openLightbox('<?= $url ?>')">
                            <?php if (in_array($ext, ['jpg','jpeg','png','gif'])): ?>
                                <img src="<?= $url ?>" alt="<?= $info[0] ?>"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <div style="display:none; color:#64748b; font-size:13px;">
                                    <i class="bi bi-image"></i><br>Image not available
                                </div>
                            <?php else: ?>
                                <a href="<?= $url ?>" target="_blank" class="document-link" onclick="event.stopPropagation();">
                                    <i class="bi bi-file-earmark-arrow-down"></i> View Document
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="document-link" onclick="openLightbox('<?= $url ?>')">
                            <i class="bi bi-eye"></i> View Full Size
                        </div>
                    <?php else: ?>
                        <div class="document-preview">
                            <div style="color:#94a3b8; font-size:13px;">
                                <i class="bi bi-dash-circle"></i><br>Not uploaded
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Verification Checklist -->
    <div class="info-card">
        <div class="info-card-header">
            <i class="bi bi-check2-square text-danger"></i>
            <h3 class="info-card-title">Verification Checklist</h3>
            <span class="badge bg-warning text-dark ms-auto">Manual Steps Required</span>
        </div>
        <div class="info-card-body">
            <div class="verification-progress">
                <div class="progress-text" id="progressText">0 of 2 verifications complete</div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill" style="width: 0%;"></div>
                </div>
            </div>
            <div class="verification-grid">
                
                <!-- TIN Verification -->
                <div class="verification-card pending" id="tinVerification">
                    <div class="verification-checkbox" onclick="toggleVerification('tinVerification', this)">
                        <i class="bi bi-check" style="display: none;"></i>
                    </div>
                    <div class="verification-header">
                        <div class="verification-icon">
                            <i class="bi bi-building"></i>
                        </div>
                        <h4 class="verification-title">TIN Verification</h4>
                    </div>
                    <div class="verification-content">
                        <div class="verification-label">Applicant TIN Number</div>
                        <div class="verification-value"><?= clean($user['tin_number'] ?? 'Not provided') ?></div>
                    </div>
                    <div class="verification-alert">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>
                            <strong>Action Required:</strong> Please manually verify this TIN at <strong>bir.gov.ph</strong> before approving this application.
                        </div>
                    </div>
                    <a href="https://www.bir.gov.ph" target="_blank" class="verification-btn">
                        <i class="bi bi-box-arrow-up-right"></i> Verify at BIR Website
                    </a>
                </div>
                
                <!-- Employment Verification -->
                <div class="verification-card pending" id="employmentVerification">
                    <div class="verification-checkbox" onclick="toggleVerification('employmentVerification', this)">
                        <i class="bi bi-check" style="display: none;"></i>
                    </div>
                    <div class="verification-header">
                        <div class="verification-icon">
                            <i class="bi bi-telephone-outbound"></i>
                        </div>
                        <h4 class="verification-title">Employment Verification</h4>
                    </div>
                    <div class="verification-content">
                        <div class="verification-label">Company Name</div>
                        <div class="verification-value"><?= clean($user['company_name'] ?? 'Not provided') ?></div>
                        <div class="verification-label mt-3">HR Phone Number</div>
                        <div class="verification-value"><?= clean($user['company_phone'] ?? 'Not provided') ?></div>
                    </div>
                    <div class="verification-alert">
                        <i class="bi bi-info-circle-fill"></i>
                        <div>
                            <strong>Action Required:</strong> Please call this HR number to confirm the applicant is an actual employee before approving.
                        </div>
                    </div>
                    <button class="verification-btn" onclick="prompt('Call this number to verify employment: <?= clean($user['company_phone'] ?? 'Not provided') ?>')">
                        <i class="bi bi-telephone"></i> Call to Verify
                    </button>
                </div>
                
            </div>
        </div>
    </div>

    <!-- Sticky Bottom Action Bar -->
    <?php if ($user['status'] === 'Pending'): ?>
    <div class="sticky-bottom-actions">
        <button type="button" id="bottomApproveBtn" class="btn-action btn-approve" disabled onclick="handleApproveClick(event)" style="position: relative;">
            <i class="bi bi-check-circle"></i> Approve
            <span class="verification-tooltip" id="bottomApproveTooltip">Complete all verifications before approving</span>
        </button>
        <button class="btn-action btn-reject" data-bs-toggle="modal" data-bs-target="#rejectModal">
            <i class="bi bi-x-circle"></i> Reject
        </button>
        <button class="btn-action btn-request-info" onclick="alert('Request more info feature coming soon!')">
            <i class="bi bi-envelope"></i> Request More Info
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Lightbox Modal -->
<div id="lightboxModal" class="lightbox-modal" onclick="closeLightbox()">
    <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
    <div class="lightbox-content">
        <img id="lightboxImage" src="" alt="Document Preview">
    </div>
</div>

<script>
// localStorage key for this application
const STORAGE_KEY = `verification_<?= $user['id'] ?>`;

// Load verification state from localStorage on page load
function loadVerificationState() {
    const savedState = localStorage.getItem(STORAGE_KEY);
    if (savedState) {
        const state = JSON.parse(savedState);
        
        // Restore TIN verification state
        if (state.tinVerification) {
            const tinCard = document.getElementById('tinVerification');
            const tinCheckbox = tinCard.querySelector('.verification-checkbox');
            const tinCheckIcon = tinCheckbox.querySelector('i');
            
            tinCard.classList.remove('pending');
            tinCard.classList.add('verified');
            tinCheckbox.classList.add('checked');
            tinCheckIcon.style.display = 'block';
        }
        
        // Restore Employment verification state
        if (state.employmentVerification) {
            const empCard = document.getElementById('employmentVerification');
            const empCheckbox = empCard.querySelector('.verification-checkbox');
            const empCheckIcon = empCheckbox.querySelector('i');
            
            empCard.classList.remove('pending');
            empCard.classList.add('verified');
            empCheckbox.classList.add('checked');
            empCheckIcon.style.display = 'block';
        }
        
        // Update document badges if both are verified
        if (state.tinVerification && state.employmentVerification) {
            updateDocumentBadges();
        }
    }
    
    updateProgress();
    updateApproveButton();
}

// Save verification state to localStorage
function saveVerificationState() {
    const tinVerified = document.getElementById('tinVerification').classList.contains('verified');
    const empVerified = document.getElementById('employmentVerification').classList.contains('verified');
    
    const state = {
        tinVerification: tinVerified,
        employmentVerification: empVerified
    };
    
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
}

// Verification Toggle Function
function toggleVerification(cardId, checkbox) {
    const card = document.getElementById(cardId);
    const checkIcon = checkbox.querySelector('i');
    
    if (card.classList.contains('pending')) {
        // Mark as verified
        card.classList.remove('pending');
        card.classList.add('verified');
        checkbox.classList.add('checked');
        checkIcon.style.display = 'block';
    } else {
        // Mark as pending
        card.classList.remove('verified');
        card.classList.add('pending');
        checkbox.classList.remove('checked');
        checkIcon.style.display = 'none';
    }
    
    saveVerificationState();
    updateProgress();
    updateApproveButton();
    
    // Update document badges if both are verified
    const tinVerified = document.getElementById('tinVerification').classList.contains('verified');
    const empVerified = document.getElementById('employmentVerification').classList.contains('verified');
    if (tinVerified && empVerified) {
        updateDocumentBadges();
    } else {
        resetDocumentBadges();
    }
}

// Update Progress Bar
function updateProgress() {
    const totalCards = 2;
    const verifiedCards = document.querySelectorAll('.verification-card.verified').length;
    const percentage = (verifiedCards / totalCards) * 100;
    
    document.getElementById('progressText').textContent = `${verifiedCards} of ${totalCards} verifications complete`;
    document.getElementById('progressFill').style.width = percentage + '%';
}

// Update Approve Button State
function updateApproveButton() {
    const totalCards = 2;
    const verifiedCards = document.querySelectorAll('.verification-card.verified').length;
    const approveBtn = document.getElementById('approveBtn');
    const bottomApproveBtn = document.getElementById('bottomApproveBtn');
    const approveTooltip = document.getElementById('approveTooltip');
    const bottomApproveTooltip = document.getElementById('bottomApproveTooltip');
    
    const allVerified = verifiedCards === totalCards;
    
    if (allVerified) {
        approveBtn.disabled = false;
        bottomApproveBtn.disabled = false;
        approveTooltip.classList.remove('show');
        bottomApproveTooltip.classList.remove('show');
    } else {
        approveBtn.disabled = true;
        bottomApproveBtn.disabled = true;
    }
}

// Show tooltip when clicking disabled approve button
function handleDisabledApproveClick(event) {
    if (event.target.disabled) {
        event.preventDefault();
        event.stopPropagation();
        
        const tooltip = event.target.querySelector('.verification-tooltip');
        tooltip.classList.add('show');
        
        setTimeout(() => {
            tooltip.classList.remove('show');
        }, 3000);
    }
}

// Update document badges to VERIFIED
function updateDocumentBadges() {
    const badges = document.querySelectorAll('.document-badge');
    badges.forEach(badge => {
        if (badge.classList.contains('badge-required')) {
            badge.classList.remove('badge-required');
            badge.classList.add('badge-verified');
            badge.textContent = 'VERIFIED';
        }
    });
}

// Reset document badges to REQUIRED
function resetDocumentBadges() {
    const badges = document.querySelectorAll('.document-badge');
    badges.forEach(badge => {
        if (badge.classList.contains('badge-verified') && badge.id !== 'badge-coe') {
            badge.classList.remove('badge-verified');
            badge.classList.add('badge-required');
            badge.textContent = 'REQUIRED';
        }
    });
}

// Lightbox Functions
function openLightbox(imageUrl) {
    const modal = document.getElementById('lightboxModal');
    const img = document.getElementById('lightboxImage');
    
    img.src = imageUrl;
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    const modal = document.getElementById('lightboxModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close lightbox on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeLightbox();
        hideApprovalModal();
    }
});

// Custom Approval Modal
function handleApproveClick(event) {
    event.preventDefault();
    
    // Check if all verifications are complete
    const totalCards = 2;
    const verifiedCards = document.querySelectorAll('.verification-card.verified').length;
    
    if (verifiedCards !== totalCards) {
        handleDisabledApproveClick(event);
        return;
    }
    
    const approvalModal = document.getElementById('approvalModal');
    approvalModal.style.display = 'block';
}

function hideApprovalModal() {
    const approvalModal = document.getElementById('approvalModal');
    approvalModal.style.display = 'none';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadVerificationState();
    
    // Add click handlers for disabled approve buttons
    const approveBtn = document.getElementById('approveBtn');
    const bottomApproveBtn = document.getElementById('bottomApproveBtn');
    
    approveBtn.addEventListener('click', handleApproveClick);
    bottomApproveBtn.addEventListener('click', handleApproveClick);
});
</script>

<!-- Custom Approval Modal -->
<div id="approvalModal" class="approval-modal-overlay">
    <div class="approval-modal-content">
        <div class="approval-modal-header">
            <h3 class="approval-modal-title">
                <i class="bi bi-check-circle-fill"></i>
                Confirm Approval
            </h3>
        </div>
        <div class="approval-modal-body">
            <div style="margin-bottom: 20px;">
                <div style="font-size: 14px; color: #6b7280; margin-bottom: 4px;">Applicant Name</div>
                <div style="font-size: 18px; font-weight: 600; color: #111827;"><?= clean($user['first_name'] . ' ' . $user['last_name']) ?></div>
            </div>
            
            <div class="approval-warning">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div class="approval-warning-text">
                    I confirm that I have manually verified the TIN at bir.gov.ph and confirmed employment with the company HR.
                </div>
            </div>
        </div>
        <div class="approval-modal-footer">
            <button type="button" class="approval-modal-btn approval-modal-btn-cancel" onclick="hideApprovalModal()">
                Cancel
            </button>
            <form method="POST" action="approve.php" style="display: inline;">
                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                <button type="submit" class="approval-modal-btn approval-modal-btn-confirm">
                    Yes, Approve Application
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="reject.php">
                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="bi bi-x-circle"></i> Reject Application</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Rejecting this application will notify the applicant via email.
                        The record will be automatically deleted after 30 days.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="4"
                            placeholder="e.g. Submitted documents are invalid or incomplete..."
                            required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
