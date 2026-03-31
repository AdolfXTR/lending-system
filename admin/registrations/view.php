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
/* Improved Application Review Styles */
.app-header {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    border: 1px solid rgba(0,0,0,0.05);
}

.app-title {
    font-size: 24px;
    font-weight: 700;
    color: #1a202c;
    margin: 0;
}

.app-subtitle {
    color: #64748b;
    font-size: 14px;
    margin: 4px 0 0 0;
}

.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-action {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-approve {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.btn-reject {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
}

.btn-direct-reject {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}

/* Info Cards */
.info-card {
    background: white;
    border-radius: 12px;
    border: 1px solid rgba(0,0,0,0.08);
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: box-shadow 0.2s;
}

.info-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.info-card-header {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    padding: 16px 20px;
    border-bottom: 1px solid rgba(0,0,0,0.06);
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-card-title {
    font-size: 15px;
    font-weight: 700;
    color: #374151;
    margin: 0;
}

.info-card-body {
    padding: 20px;
}

/* Compact Info Table */
.info-table {
    width: 100%;
    border-collapse: collapse;
}

.info-table tr {
    border-bottom: 1px solid #f1f5f9;
}

.info-table tr:last-child {
    border-bottom: none;
}

.info-table th {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 12px 16px 12px 0;
    width: 35%;
    vertical-align: top;
}

.info-table td {
    font-size: 14px;
    color: #1e293b;
    padding: 12px 0;
    font-weight: 500;
}

/* Document Cards */
.document-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}

.document-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    transition: all 0.2s;
}

.document-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}

.document-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    color: white;
    font-size: 20px;
}

.document-title {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 12px;
}

.document-preview {
    margin: 12px 0;
    border-radius: 8px;
    overflow: hidden;
    background: #f8fafc;
    min-height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.document-preview img {
    max-width: 100%;
    max-height: 180px;
    object-fit: cover;
}

.document-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: #f1f5f9;
    color: #475569;
    text-decoration: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
}

.document-link:hover {
    background: #e2e8f0;
    color: #334155;
}

/* Verification Cards */
.verification-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
    gap: 24px;
}

.verification-card {
    border-radius: 16px;
    padding: 28px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
}

.verification-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
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

.verification-tin {
    background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%);
    border: 1px solid #fecaca;
    --color-start: #dc2626;
    --color-end: #ef4444;
}

.verification-employment {
    background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);
    border: 1px solid #bfdbfe;
    --color-start: #2563eb;
    --color-end: #3b82f6;
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
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.verification-tin .verification-icon {
    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
    color: white;
}

.verification-employment .verification-icon {
    background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
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

.verification-tin .verification-alert {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
    border: 1px solid #f59e0b;
}

.verification-employment .verification-alert {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1e40af;
    border: 1px solid #3b82f6;
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
    border: none;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.verification-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.verification-btn:hover::before {
    left: 100%;
}

.verification-tin .verification-btn {
    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
    color: white;
    box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.3);
}

.verification-employment .verification-btn {
    background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
    color: white;
    box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3);
}

.verification-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.status-badge {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
}
</style>

<!-- Application Header -->
<div class="app-header">
    <div>
        <a href="index.php" class="text-decoration-none text-muted small">
            <i class="bi bi-arrow-left"></i> Back to Applications
        </a>
        <h1 class="app-title">
            <i class="bi bi-person-badge"></i>
            <?= clean($user['first_name'] . ' ' . $user['last_name']) ?>
        </h1>
        <p class="app-subtitle">Application Review • ID #<?= $user['id'] ?> • Applied <?= date('F j, Y', strtotime($user['created_at'])) ?></p>
    </div>
    <?php if ($user['status'] === 'Pending'): ?>
    <div class="action-buttons">
        <form method="POST" action="approve.php" onsubmit="return confirm('Approve this application?')">
            <input type="hidden" name="id" value="<?= $user['id'] ?>">
            <button type="submit" class="btn-action btn-approve">
                <i class="bi bi-check-circle"></i> Approve
            </button>
        </form>
        <button class="btn-action btn-reject" data-bs-toggle="modal" data-bs-target="#rejectModal">
            <i class="bi bi-x-circle"></i> Reject
        </button>
        <a href="reject.php?id=<?= $user['id'] ?>&reason=test" class="btn-action btn-direct-reject" 
           onclick="if(!confirm('Direct reject test?')) return false">
            <i class="bi bi-hammer"></i> Test Reject
        </a>
    </div>
    <?php else: ?>
        <span class="status-badge fs-6 bg-<?= $user['status']==='Active'?'success':($user['status']==='Disabled'?'danger':($user['status']==='Rejected'?'danger':'secondary')) ?>">
            <?= $user['status'] ?>
        </span>
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
                    <table class="info-table">
                        <tr>
                            <th>Full Name</th>
                            <td><?= clean($user['first_name'] . ' ' . $user['last_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Gender</th>
                            <td><?= clean($user['gender'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <th>Birthday</th>
                            <td><?= $user['birthday'] ? date('F d, Y', strtotime($user['birthday'])) : '—' ?></td>
                        </tr>
                        <tr>
                            <th>Age</th>
                            <td><?= $user['age'] ?? '—' ?></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td><?= clean($user['address'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?= clean($user['email']) ?></td>
                        </tr>
                        <tr>
                            <th>Contact</th>
                            <td><?= clean($user['contact_number'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <th>TIN</th>
                            <td><?= clean($user['tin_number'] ?? '—') ?></td>
                        </tr>
                        <tr>
                            <th>Account Type</th>
                            <td>
                                <span class="badge bg-<?= $user['account_type']==='Premium'?'success':'secondary' ?>">
                                    <?= $user['account_type'] ?>
                                </span>
                            </td>
                        </tr>
                    </table>
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
                        <table class="info-table">
                            <tr>
                                <th>Company</th>
                                <td><?= clean($user['company_name'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th>Address</th>
                                <td><?= clean($user['company_address'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th>Phone</th>
                                <td><?= clean($user['company_phone'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th>Position</th>
                                <td><?= clean($user['position'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th>Monthly Income</th>
                                <td><?= !empty($user['monthly_earnings']) ? formatMoney((float)$user['monthly_earnings']) : '—' ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div>
                        <h4 class="text-muted text-uppercase small mb-3">Bank Information</h4>
                        <table class="info-table">
                            <tr>
                                <th>Bank Name</th>
                                <td><?= clean($user['bank_name'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th>Account No.</th>
                                <td><?= clean($user['bank_account_number'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th>Card Holder</th>
                                <td><?= clean($user['card_holder_name'] ?? '—') ?></td>
                            </tr>
                        </table>
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
                    'proof_of_billing' => ['Proof of Billing', 'bi-file-earmark-text'],
                    'valid_id'         => ['Valid ID (Primary)', 'bi-person-badge'],
                    'coe'              => ['Certificate of Employment', 'bi-briefcase'],
                ];
                foreach ($docs as $field => $info):
                    $file = $user[$field] ?? null;
                ?>
                <div class="document-card">
                    <div class="document-icon">
                        <i class="<?= $info[1] ?>"></i>
                    </div>
                    <div class="document-title"><?= $info[0] ?></div>
                    <?php if ($file): ?>
                        <?php
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        $url = APP_URL . '/uploads/' . $file;
                        ?>
                        <div class="document-preview">
                            <?php if (in_array($ext, ['jpg','jpeg','png','gif'])): ?>
                                <a href="<?= $url ?>" target="_blank">
                                    <img src="<?= $url ?>" alt="<?= $info[0] ?>"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <div style="display:none; color:#64748b; font-size:13px;">
                                        <i class="bi bi-image"></i><br>Image not available
                                    </div>
                                </a>
                            <?php else: ?>
                                <a href="<?= $url ?>" target="_blank" class="document-link">
                                    <i class="bi bi-file-earmark-arrow-down"></i> View Document
                                </a>
                            <?php endif; ?>
                        </div>
                        <a href="<?= $url ?>" target="_blank" class="document-link">
                            <i class="bi bi-eye"></i> View Full Size
                        </a>
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
            <div class="verification-grid">
                
                <!-- TIN Verification -->
                <div class="verification-card verification-tin">
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
                <div class="verification-card verification-employment">
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