<?php
declare(strict_types=1);
$pageTitle = 'Membership & Renewal';
require __DIR__ . '/../partials/header.php';

$member          = $member ?? [];
$user            = $user ?? [];
$gym             = $gym ?? null;
$plans           = $plans ?? [];
$assignedTrainer = $assignedTrainer ?? null;
$paymentHistory  = $paymentHistory ?? [];
$paymentSummary  = $paymentSummary ?? [];
$isExpired       = $isExpired ?? false;
$daysUntilExpiry = $daysUntilExpiry ?? null;
$error           = $error ?? '';
$success         = $success ?? '';

// Format dates
$startDate = !empty($member['start_date']) ? date('M j, Y', strtotime($member['start_date'])) : 'N/A';
$expirationDate = !empty($member['expiration_date']) ? date('M j, Y', strtotime($member['expiration_date'])) : 'N/A';
$renewalDate = !empty($member['renewal_date']) ? date('M j, Y', strtotime($member['renewal_date'])) : null;
$memberCode = $member['membership_code'] ?? 'N/A';
$memberIdNumber = $member['member_id_number'] ?? ('MEM-' . str_pad((string)($member['id'] ?? 0), 5, '0', STR_PAD_LEFT));

$isExpiringSoon = !$isExpired && $daysUntilExpiry !== null && $daysUntilExpiry <= 7;
$statusBadgeClass = $isExpired ? 'bg-danger' : ($isExpiringSoon ? 'bg-warning text-dark' : 'bg-success');
$statusText = $isExpired ? 'EXPIRED' : ($isExpiringSoon ? 'EXPIRING SOON' : 'ACTIVE');

// Default price from member record or first plan
$defaultAmount = (float)($member['payment_amount'] ?? 0);
if ($defaultAmount <= 0 && !empty($plans)) {
    $defaultAmount = (float)($plans[0]['price'] ?? 1000);
}
if ($defaultAmount <= 0) {
    $defaultAmount = 1000.00;
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.membership-page {
    font-family: 'Inter', system-ui, sans-serif;
    color: #1e293b;
}

/* Hero Header */
.membership-hero {
    background: linear-gradient(135deg, #0f2117 0%, #1B6B2A 60%, #2E8B3E 100%);
    border-radius: 16px;
    padding: 2rem 2.2rem;
    color: #ffffff;
    position: relative;
    overflow: hidden;
    margin-bottom: 1.75rem;
    box-shadow: 0 4px 20px rgba(27,107,42,0.15);
}
.membership-hero::after {
    content: '';
    position: absolute;
    top: -50px; right: -50px;
    width: 200px; height: 200px;
    background: rgba(255,255,255,0.06);
    border-radius: 50%;
    pointer-events: none;
}
.membership-hero h1 {
    font-size: 1.75rem;
    font-weight: 800;
    margin-bottom: 0.35rem;
    color: #ffffff !important;
}
.membership-hero p {
    opacity: 0.9;
    font-size: 0.95rem;
    margin-bottom: 0;
    color: #ffffff !important;
}

/* Cards */
.mem-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    overflow: hidden;
    margin-bottom: 1.5rem;
    transition: box-shadow 0.2s;
}
.mem-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
}
.mem-card-header {
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    padding: 1.1rem 1.4rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.mem-card-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.mem-card-body {
    padding: 1.4rem;
}

/* Status Indicator Big Box */
.status-spotlight {
    border-radius: 12px;
    padding: 1.3rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.status-spotlight.expired {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border: 1.5px solid #fca5a5;
    color: #991b1b;
}
.status-spotlight.expiring {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border: 1.5px solid #fcd34d;
    color: #92400e;
}
.status-spotlight.active {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1.5px solid #86efac;
    color: #166534;
}

/* Plan Option Radio Cards */
.plan-radio-group {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 0.85rem;
    margin-bottom: 1.25rem;
}
.plan-radio-card {
    position: relative;
}
.plan-radio-card input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}
.plan-radio-card label {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 100%;
    padding: 1rem 0.9rem;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
    user-select: none;
}
.plan-radio-card input[type="radio"]:checked + label {
    border-color: #1B6B2A;
    background: #f0fdf4;
    box-shadow: 0 4px 12px rgba(27,107,42,0.15);
    transform: translateY(-2px);
}
.plan-radio-card label:hover {
    border-color: #86efac;
    background: #f8fafc;
}
.plan-card-title {
    font-size: 0.85rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.3rem;
}
.plan-card-price {
    font-size: 1.15rem;
    font-weight: 800;
    color: #1B6B2A;
}
.plan-card-duration {
    font-size: 0.72rem;
    color: #64748b;
}

/* Info Data Row */
.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.65rem 0;
    border-bottom: 1px dashed #e2e8f0;
    font-size: 0.9rem;
}
.info-row:last-child {
    border-bottom: none;
}
.info-label {
    color: #64748b;
    font-weight: 500;
}
.info-val {
    color: #1e293b;
    font-weight: 600;
}

/* Stat Widgets */
.stat-pill-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
}
.stat-pill-number {
    font-size: 1.35rem;
    font-weight: 800;
    color: #1B6B2A;
}
.stat-pill-label {
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-weight: 600;
}

/* Copy code button */
.copy-code-btn {
    border: none;
    background: transparent;
    color: #1B6B2A;
    cursor: pointer;
    font-size: 0.85rem;
    padding: 0 0.3rem;
    transition: color 0.2s;
}
.copy-code-btn:hover {
    color: #2E8B3E;
}
</style>

<div class="container-fluid py-4 px-md-4 membership-page">

    <!-- Breadcrumb & Top Bar -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="index.php?r=member/dashboard" class="text-decoration-none text-muted">Dashboard</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Membership &amp; Renewal</li>
        </ol>
    </nav>

    <!-- Alerts -->
    <?php if ($error !== ''): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-octagon-fill fs-5 me-2 text-danger"></i>
            <div><strong>Notice:</strong> <?= htmlspecialchars($error) ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
            <div><strong>Success!</strong> <?= htmlspecialchars($success) ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Hero Header -->
    <div class="membership-hero">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1><i class="bi bi-card-checklist me-2"></i>Gym Membership &amp; Renewal</h1>
                <p>Manage your membership status, view access duration, and easily renew or extend your gym privileges.</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="index.php?r=membership/myid" class="btn btn-light text-success fw-bold shadow-sm rounded-3 px-3 py-2">
                    <i class="bi bi-qr-code-scan me-1"></i> Digital ID Card
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- LEFT COLUMN: Status & Renewal -->
        <div class="col-lg-7">

            <!-- Current Status Card -->
            <div class="mem-card">
                <div class="mem-card-header">
                    <h5 class="mem-card-title">
                        <i class="bi bi-shield-check text-success"></i> Membership Status Overview
                    </h5>
                    <span class="badge <?= $statusBadgeClass ?> px-3 py-2 rounded-pill fw-bold" style="font-size: 0.8rem;">
                        <?= $statusText ?>
                    </span>
                </div>
                <div class="mem-card-body">
                    <!-- Status Spotlight Box -->
                    <div class="status-spotlight <?= $isExpired ? 'expired' : ($isExpiringSoon ? 'expiring' : 'active') ?> mb-4">
                        <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                            <i class="bi <?= $isExpired ? 'bi-exclamation-triangle-fill fs-3 text-danger' : ($isExpiringSoon ? 'bi-hourglass-split fs-3 text-warning' : 'bi-check-circle-fill fs-3 text-success') ?>"></i>
                            <h4 class="fw-bold mb-0" style="letter-spacing: 0.5px;">
                                <?= $isExpired ? 'Membership Expired' : ($isExpiringSoon ? 'Expiring Soon' : 'Active Membership') ?>
                            </h4>
                        </div>
                        <div class="small opacity-85">
                            <?php if ($isExpired): ?>
                                Your access expired on <strong><?= $expirationDate ?></strong> (<?= abs((int)$daysUntilExpiry) ?> days ago). Please renew below to continue gym access.
                            <?php elseif ($isExpiringSoon): ?>
                                Expires in <strong><?= $daysUntilExpiry ?> day(s)</strong> on <?= $expirationDate ?>. Renew early to avoid interruption.
                            <?php else: ?>
                                Valid until <strong><?= $expirationDate ?></strong> (<?= $daysUntilExpiry ?> days remaining).
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Membership Details Info List -->
                    <div class="info-row">
                        <span class="info-label"><i class="bi bi-building me-1"></i> Assigned Gym</span>
                        <span class="info-val"><?= htmlspecialchars($gym['gym_name'] ?? 'Nutrify Fitness Center') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="bi bi-upc me-1"></i> Membership Code</span>
                        <span class="info-val d-inline-flex align-items-center">
                            <code><?= htmlspecialchars($memberCode) ?></code>
                            <button class="copy-code-btn" onclick="copyText('<?= htmlspecialchars($memberCode) ?>', this)" title="Copy Code">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="bi bi-person-badge me-1"></i> Member ID</span>
                        <span class="info-val"><?= htmlspecialchars($memberIdNumber) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="bi bi-calendar-check me-1"></i> Member Since</span>
                        <span class="info-val"><?= $startDate ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="bi bi-calendar-x me-1"></i> Expiration Date</span>
                        <span class="info-val <?= $isExpired ? 'text-danger fw-bold' : '' ?>"><?= $expirationDate ?></span>
                    </div>
                    <?php if ($renewalDate): ?>
                    <div class="info-row">
                        <span class="info-label"><i class="bi bi-arrow-repeat me-1"></i> Last Renewal</span>
                        <span class="info-val"><?= date('M j, Y', strtotime($renewalDate)) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($assignedTrainer): ?>
                    <div class="info-row">
                        <span class="info-label"><i class="bi bi-person-heart me-1"></i> Personal Coach</span>
                        <span class="info-val text-success fw-bold"><?= htmlspecialchars($assignedTrainer['fullname'] ?? 'Trainer') ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex gap-2 mt-4">
                        <a href="index.php?r=membership/myid" class="btn btn-outline-success flex-fill py-2 fw-semibold rounded-3">
                            <i class="bi bi-qr-code me-1"></i> View Digital ID
                        </a>
                        <a href="index.php?r=membership/fitnessprogram" class="btn btn-outline-secondary flex-fill py-2 fw-semibold rounded-3">
                            <i class="bi bi-fire me-1"></i> Workout Program
                        </a>
                    </div>
                </div>
            </div>

            <!-- Renew Membership Card -->
            <div class="mem-card border-success" id="renew-section">
                <div class="mem-card-header bg-success bg-opacity-10 border-success border-opacity-25">
                    <h5 class="mem-card-title text-success">
                        <i class="bi bi-arrow-clockwise"></i> Renew / Extend Membership
                    </h5>
                    <span class="badge bg-success text-white px-2 py-1 rounded">Instant Renewal</span>
                </div>
                <div class="mem-card-body">
                    <p class="text-muted small mb-3">
                        Select your preferred renewal duration or plan below. Your expiration date will be extended immediately upon confirmation.
                    </p>

                    <form method="POST" action="index.php?r=member/membership" id="renewalForm" onsubmit="return confirmRenewal();">
                        <input type="hidden" name="action" value="renew_membership">
                        
                        <label class="form-label fw-bold small text-dark mb-2">1. Choose Renewal Duration / Plan</label>
                        
                        <div class="plan-radio-group">
                            <!-- 1 Month (30 Days) -->
                            <div class="plan-radio-card">
                                <input type="radio" name="plan_option" id="plan_1m" value="<?= $defaultAmount ?>" data-days="30" checked onchange="updateRenewalTotal(this)">
                                <label for="plan_1m">
                                    <div class="plan-card-title">1 Month</div>
                                    <div class="plan-card-price">₱<?= number_format($defaultAmount, 2) ?></div>
                                    <div class="plan-card-duration">+30 Days Access</div>
                                </label>
                            </div>

                            <!-- 3 Months (90 Days) -->
                            <?php $price3m = round($defaultAmount * 3 * 0.95, 2); ?>
                            <div class="plan-radio-card">
                                <input type="radio" name="plan_option" id="plan_3m" value="<?= $price3m ?>" data-days="90" onchange="updateRenewalTotal(this)">
                                <label for="plan_3m">
                                    <div class="plan-card-title">3 Months <span class="badge bg-warning text-dark" style="font-size:0.6rem;">5% OFF</span></div>
                                    <div class="plan-card-price">₱<?= number_format($price3m, 2) ?></div>
                                    <div class="plan-card-duration">+90 Days Access</div>
                                </label>
                            </div>

                            <!-- 6 Months (180 Days) -->
                            <?php $price6m = round($defaultAmount * 6 * 0.90, 2); ?>
                            <div class="plan-radio-card">
                                <input type="radio" name="plan_option" id="plan_6m" value="<?= $price6m ?>" data-days="180" onchange="updateRenewalTotal(this)">
                                <label for="plan_6m">
                                    <div class="plan-card-title">6 Months <span class="badge bg-danger text-white" style="font-size:0.6rem;">10% OFF</span></div>
                                    <div class="plan-card-price">₱<?= number_format($price6m, 2) ?></div>
                                    <div class="plan-card-duration">+180 Days Access</div>
                                </label>
                            </div>

                            <!-- 1 Year (365 Days) -->
                            <?php $price1y = round($defaultAmount * 12 * 0.80, 2); ?>
                            <div class="plan-radio-card">
                                <input type="radio" name="plan_option" id="plan_1y" value="<?= $price1y ?>" data-days="365" onchange="updateRenewalTotal(this)">
                                <label for="plan_1y">
                                    <div class="plan-card-title">1 Year <span class="badge bg-success text-white" style="font-size:0.6rem;">20% OFF</span></div>
                                    <div class="plan-card-price">₱<?= number_format($price1y, 2) ?></div>
                                    <div class="plan-card-duration">+365 Days Access</div>
                                </label>
                            </div>
                        </div>

                        <!-- Hidden fields for submission -->
                        <input type="hidden" name="amount" id="form_amount" value="<?= $defaultAmount ?>">
                        <input type="hidden" name="duration_days" id="form_days" value="30">

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-dark mb-1">2. Payment Method</label>
                            <select name="payment_method" id="payment_method" class="form-select rounded-3 py-2 border-secondary-subtle">
                                <option value="cash">💵 Cash at Front Desk / Counter</option>
                                <option value="gcash">📱 GCash / E-Wallet</option>
                                <option value="credit_card">💳 Debit / Credit Card</option>
                                <option value="online">🌐 Online Payment Link</option>
                            </select>
                        </div>

                        <!-- Renewal Summary Box -->
                        <div class="p-3 rounded-3 bg-light border mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">Renewal Extension:</span>
                                <strong id="summary_extension" class="text-dark">+30 Days</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">New Estimated Expiration:</span>
                                <strong id="summary_new_expiry" class="text-success"><?= date('M j, Y', strtotime('+30 days')) ?></strong>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark">Total Amount:</span>
                                <span class="fs-5 fw-bold text-success" id="summary_total">₱<?= number_format($defaultAmount, 2) ?></span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-3 fw-bold shadow-sm rounded-3 fs-6">
                            <i class="bi bi-shield-fill-check me-2"></i> Confirm &amp; Submit Membership Renewal
                        </button>
                    </form>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN: Metrics & Payment History -->
        <div class="col-lg-5">

            <!-- Spending / Membership Stats -->
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <div class="stat-pill-box">
                        <div class="stat-pill-number">₱<?= number_format((float)($paymentSummary['totals']['total_spent'] ?? 0), 2) ?></div>
                        <div class="stat-pill-label">Total Spent</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-pill-box">
                        <div class="stat-pill-number"><?= (int)($paymentSummary['totals']['total_payments'] ?? count($paymentHistory)) ?></div>
                        <div class="stat-pill-label">Renewals / Payments</div>
                    </div>
                </div>
            </div>

            <!-- Transaction & Payment History Card -->
            <div class="mem-card">
                <div class="mem-card-header">
                    <h5 class="mem-card-title">
                        <i class="bi bi-clock-history text-primary"></i> Payment &amp; Renewal History
                    </h5>
                    <span class="badge bg-secondary-subtle text-secondary border rounded-pill">
                        <?= count($paymentHistory) ?> Records
                    </span>
                </div>
                <div class="mem-card-body p-0">
                    <?php if (empty($paymentHistory)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-receipt fs-1 d-block mb-2 text-secondary opacity-50"></i>
                        <div class="fw-semibold">No payment records found yet.</div>
                        <small class="text-muted">Your payment and renewal history will show here.</small>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th class="ps-3 py-2">Date</th>
                                    <th class="py-2">Description</th>
                                    <th class="py-2">Method</th>
                                    <th class="py-2 text-end pe-3">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paymentHistory as $pay): ?>
                                <tr>
                                    <td class="ps-3 py-2 text-nowrap">
                                        <div class="fw-semibold text-dark"><?= !empty($pay['payment_date']) ? date('M j, Y', strtotime($pay['payment_date'])) : date('M j, Y', strtotime($pay['created_at'])) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars(ucfirst($pay['payment_type'] ?? 'membership')) ?></small>
                                    </td>
                                    <td class="py-2">
                                        <div class="text-dark"><?= htmlspecialchars($pay['description'] ?: 'Membership Payment') ?></div>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:0.65rem;">
                                            <?= htmlspecialchars(ucfirst($pay['payment_status'] ?? 'completed')) ?>
                                        </span>
                                    </td>
                                    <td class="py-2 text-muted">
                                        <?= htmlspecialchars(ucfirst($pay['payment_method'] ?? 'cash')) ?>
                                    </td>
                                    <td class="py-2 text-end pe-3 fw-bold text-success">
                                        ₱<?= number_format((float)($pay['amount'] ?? 0), 2) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Gym Perks & Info Card -->
            <div class="mem-card" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                <div class="mem-card-body p-4">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-stars text-warning me-2"></i>Gym Perks &amp; Inclusions</h6>
                    <ul class="list-unstyled small text-muted mb-3" style="line-height: 1.8;">
                        <li><i class="bi bi-check2 text-success me-2"></i>Full gym equipment &amp; free weights access</li>
                        <li><i class="bi bi-check2 text-success me-2"></i>Locker &amp; shower amenities access</li>
                        <li><i class="bi bi-check2 text-success me-2"></i>AI-Powered personalized workout program generator</li>
                        <li><i class="bi bi-check2 text-success me-2"></i>QR Code attendance tracker &amp; digital ID</li>
                    </ul>
                    <div class="small text-secondary bg-white p-2 rounded-2 border">
                        <i class="bi bi-info-circle text-primary me-1"></i> Need assistance or custom plan? Ask your front desk administrative officer or gym owner.
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<script>
function updateRenewalTotal(radio) {
    const amount = parseFloat(radio.value) || 0;
    const days = parseInt(radio.getAttribute('data-days')) || 30;
    
    document.getElementById('form_amount').value = amount;
    document.getElementById('form_days').value = days;
    document.getElementById('summary_total').innerText = '₱' + amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('summary_extension').innerText = '+' + days + ' Days';
    
    // Calculate new date
    const today = new Date();
    const newDate = new Date(today.getTime() + (days * 24 * 60 * 60 * 1000));
    const options = { month: 'short', day: 'numeric', year: 'numeric' };
    document.getElementById('summary_new_expiry').innerText = newDate.toLocaleDateString('en-US', options);
}

function confirmRenewal() {
    const amount = document.getElementById('form_amount').value;
    const days = document.getElementById('form_days').value;
    const method = document.getElementById('payment_method').value;
    
    return confirm(`Are you sure you want to renew your membership for ${days} days (₱${parseFloat(amount).toLocaleString()}) via ${method.toUpperCase()}?`);
}

function copyText(text, btn) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2 text-success"></i>';
            setTimeout(() => { btn.innerHTML = original; }, 2000);
        });
    }
}
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
