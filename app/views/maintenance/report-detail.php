<?php
declare(strict_types=1);
$pageTitle = 'Inspection Report #' . ($inspection['id'] ?? '');
require __DIR__ . '/../partials/header.php';

$__cond = $inspection['overall_condition'] ?? '';
if ($__cond === 'good') { $condLabel = '✅ Good'; }
elseif ($__cond === 'needs_repair') { $condLabel = '⚠️ Needs Repair'; }
elseif ($__cond === 'condemned') { $condLabel = '🔴 Condemned'; }
else { $condLabel = '—'; }

if ($__cond === 'good') { $condClass = 'success'; }
elseif ($__cond === 'needs_repair') { $condClass = 'warning'; }
elseif ($__cond === 'condemned') { $condClass = 'danger'; }
else { $condClass = 'secondary'; }

$__st = $inspection['status'] ?? '';
if ($__st === 'draft') { $statusClass = 'secondary'; }
elseif ($__st === 'submitted') { $statusClass = 'primary'; }
elseif ($__st === 'verified') { $statusClass = 'success'; }
else { $statusClass = 'secondary'; }
?>

<!-- Toolbar -->
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h1 class="h3 mb-1"><i class="bi bi-file-earmark-text me-2"></i>Inspection Report</h1>
        <p class="text-muted mb-0">
            Report #<?= $inspection['id'] ?> &mdash;
            <span class="badge bg-<?= $statusClass ?>"><?= ucfirst($inspection['status'] ?? '') ?></span>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?r=maintenance/reports" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button class="btn btn-outline-primary" onclick="exportPDF()">
            <i class="bi bi-file-pdf me-1"></i> Export PDF
        </button>
        <?php if ($inspection['status'] === 'draft'): ?>
        <form method="post" class="d-inline"
              onsubmit="return confirm('Submit this report to the Gym Owner?')">
            <input type="hidden" name="action" value="submit">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-send me-1"></i> Submit to Gym Owner
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show no-print">
        <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Printable / PDF Report -->
<div id="report" class="card">
    <!-- Report Header -->
    <div class="card-body border-bottom" style="background:linear-gradient(135deg,var(--nf-green),var(--nf-green-light));border-radius:12px 12px 0 0;color:#fff;padding:2rem">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="fw-bold mb-1" style="color:#fff">
                    <i class="bi bi-clipboard2-check me-2"></i>Equipment Inspection Report
                </h2>
                <p class="mb-0 opacity-75" style="color:rgba(255,255,255,.85)">Nutrify Gym Management System</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <div class="badge bg-light text-dark fs-6 px-3 py-2">
                    <?= $condLabel ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Info Grid -->
        <div class="row g-3 mb-4 pb-3 border-bottom">
            <div class="col-md-3">
                <small class="text-muted d-block fw-bold text-uppercase" style="font-size:.7rem;letter-spacing:1px">Equipment</small>
                <strong><?= htmlspecialchars($inspection['equipment_name'] ?? '—') ?></strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block fw-bold text-uppercase" style="font-size:.7rem;letter-spacing:1px">Category</small>
                <?= htmlspecialchars($inspection['category'] ?? '—') ?>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block fw-bold text-uppercase" style="font-size:.7rem;letter-spacing:1px">Inspection Date</small>
                <?= htmlspecialchars($inspection['inspection_date'] ?? '—') ?>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block fw-bold text-uppercase" style="font-size:.7rem;letter-spacing:1px">Report #</small>
                <?= $inspection['id'] ?>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block fw-bold text-uppercase" style="font-size:.7rem;letter-spacing:1px">Completed By</small>
                <?= htmlspecialchars($inspection['staff_name'] ?? '—') ?>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block fw-bold text-uppercase" style="font-size:.7rem;letter-spacing:1px">Status</small>
                <span class="badge bg-<?= $statusClass ?>"><?= ucfirst($inspection['status'] ?? '') ?></span>
            </div>
            <?php if ($inspection['submitted_at']): ?>
            <div class="col-md-3">
                <small class="text-muted d-block fw-bold text-uppercase" style="font-size:.7rem;letter-spacing:1px">Submitted At</small>
                <?= htmlspecialchars($inspection['submitted_at']) ?>
            </div>
            <?php endif; ?>
            <?php if ($inspection['verified_at']): ?>
            <div class="col-md-3">
                <small class="text-muted d-block fw-bold text-uppercase" style="font-size:.7rem;letter-spacing:1px">Verified At</small>
                <?= htmlspecialchars($inspection['verified_at']) ?>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block fw-bold text-uppercase" style="font-size:.7rem;letter-spacing:1px">Verified By</small>
                <?= htmlspecialchars($inspection['verified_by_name'] ?? '—') ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Checklist Table -->
        <h5 class="fw-bold mb-3"><i class="bi bi-list-check me-2"></i>Inspection Checklist</h5>
        <?php if (!empty($checklist)): ?>
        <div class="table-responsive mb-4">
            <table class="table table-bordered" style="font-size:.9rem">
                <thead style="background:rgba(27,107,42,.06)">
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Checklist Item</th>
                        <th style="width:100px;text-align:center">Done</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($checklist as $i => $ci): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($ci['item_description'] ?? '') ?></td>
                        <td class="text-center">
                            <?php if ($ci['is_done']): ?>
                                <span class="badge bg-success"><i class="bi bi-check-lg"></i> Done</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="bi bi-x-lg"></i> Not Done</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($ci['notes'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <p class="text-muted mb-4"><i class="bi bi-info-circle me-1"></i>No checklist items recorded.</p>
        <?php endif; ?>

        <!-- Attached Purchase Request Section (if exists) -->
        <?php if (!empty($purchaseRequest)):
            $prStatusClass = [
                'pending'   => 'warning',
                'approved'  => 'success',
                'rejected'  => 'danger',
                'purchased' => 'info',
            ][$purchaseRequest['status'] ?? 'pending'] ?? 'secondary';
        ?>
        <div class="mb-4 p-3 rounded border" style="background:#fdfefe">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="bi bi-cart-check me-2 text-warning"></i>Attached Purchase Request #<?= (int)$purchaseRequest['id'] ?>
                </h5>
                <span class="badge bg-<?= $prStatusClass ?> fs-6 px-3 py-1">
                    <?= ucfirst($purchaseRequest['status'] ?? 'pending') ?>
                </span>
            </div>

            <?php if (!empty($purchaseRequestItems)): ?>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered mb-0" style="font-size:.9rem">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Item Name</th>
                            <th style="width:60px;text-align:center">Qty</th>
                            <th style="width:110px;text-align:right">Price (₱)</th>
                            <th style="width:110px;text-align:right">Subtotal (₱)</th>
                            <th style="width:130px">Staff Note</th>
                            <th style="width:110px;text-align:center">Decision</th>
                            <th>Owner Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchaseRequestItems as $pi => $item):
                            $sub = (float)($item['quantity'] ?? 1) * (float)($item['unit_price'] ?? 0);
                            if ($item['status'] === 'approved' || !empty($item['is_approved'])) {
                                $itemBadge = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Approved</span>';
                            } elseif ($item['status'] === 'purchased' || !empty($item['is_purchased'])) {
                                $itemBadge = '<span class="badge bg-info"><i class="bi bi-bag-check me-1"></i>Purchased</span>';
                            } elseif ($item['status'] === 'rejected') {
                                $itemBadge = '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Not Buying</span>';
                            } else {
                                $itemBadge = '<span class="badge bg-secondary">Pending</span>';
                            }
                        ?>
                        <tr>
                            <td><?= $pi + 1 ?></td>
                            <td><strong><?= htmlspecialchars($item['item_name'] ?? '') ?></strong></td>
                            <td class="text-center"><?= (int)($item['quantity'] ?? 1) ?></td>
                            <td class="text-end">₱<?= number_format((float)($item['unit_price'] ?? 0), 2) ?></td>
                            <td class="text-end fw-bold text-success">₱<?= number_format($sub, 2) ?></td>
                            <td><?= htmlspecialchars($item['notes'] ?? '—') ?></td>
                            <td class="text-center"><?= $itemBadge ?></td>
                            <td>
                                <?php if (!empty($item['rejection_reason'])): ?>
                                    <span class="text-danger small"><i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($item['rejection_reason']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="4" class="text-end fw-bold text-uppercase">Total Estimated Cost:</td>
                            <td class="text-end fw-bold text-success fs-6">₱<?= number_format((float)($purchaseRequest['total_amount'] ?? 0), 2) ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>

            <?php if (!empty($purchaseRequest['admin_notes'])): ?>
            <div class="p-2 rounded bg-light border">
                <small class="text-muted fw-bold d-block text-uppercase" style="font-size:.7rem;letter-spacing:1px">Owner / Admin Response Notes:</small>
                <div class="mt-1" style="font-size:.9rem;white-space:pre-wrap"><?= htmlspecialchars($purchaseRequest['admin_notes']) ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Condition & Remarks -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <h6 class="fw-bold text-uppercase" style="font-size:.75rem;letter-spacing:1px;color:var(--nf-muted)">Overall Condition</h6>
                <div class="p-3 rounded border text-center">
                    <span class="fs-5 fw-bold text-<?= $condClass ?>"><?= $condLabel ?></span>
                </div>
            </div>
            <div class="col-md-8">
                <h6 class="fw-bold text-uppercase" style="font-size:.75rem;letter-spacing:1px;color:var(--nf-muted)">Remarks</h6>
                <div class="p-3 rounded border" style="min-height:80px;white-space:pre-wrap"><?= htmlspecialchars($inspection['remarks'] ?? 'No remarks provided.') ?></div>
            </div>
        </div>

        <!-- Signature -->
        <div class="row">
            <div class="col-md-5">
                <h6 class="fw-bold text-uppercase" style="font-size:.75rem;letter-spacing:1px;color:var(--nf-muted)">Maintenance Staff Signature</h6>
                <?php if (!empty($inspection['signature_data']) && strncmp($inspection['signature_data'], 'data:image', 10) === 0): ?>
                    <div style="border:2px solid var(--nf-border);border-radius:8px;padding:8px;background:#fafafa;display:inline-block">
                        <img src="<?= htmlspecialchars($inspection['signature_data']) ?>"
                             alt="Signature" style="max-height:100px;display:block">
                    </div>
                    <p class="small text-muted mt-1">
                        <i class="bi bi-person-check me-1"></i><?= htmlspecialchars($inspection['staff_name'] ?? '') ?>
                    </p>
                <?php else: ?>
                    <div style="border:2px dashed var(--nf-border);border-radius:8px;padding:20px;text-align:center;color:var(--nf-muted)">
                        <i class="bi bi-pen"></i> No signature
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print { display: none !important; }
    .sidebar, .top-navbar { display: none !important; }
    .main-content { padding: 0 !important; }
    body { background: #fff; }
}
</style>

<!-- html2pdf Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function exportPDF() {
    const element = document.getElementById('report');
    const opt = {
        margin: 0.5,
        filename: 'inspection-report-<?= $inspection['id'] ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
