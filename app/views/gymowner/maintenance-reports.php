<?php
declare(strict_types=1);
$pageTitle = 'Maintenance Reports';
require __DIR__ . '/../partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><i class="bi bi-clipboard2-check me-2"></i>Maintenance Reports</h1>
        <p class="text-muted mb-0">Review and verify equipment inspection reports from your maintenance staff</p>
    </div>
</div>

<?php if (!$tableReady): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Setup Required:</strong> The maintenance tables haven't been created yet.
        Please run <code>maintenance_setup.sql</code> in phpMyAdmin to enable this feature.
    </div>
<?php else: ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filter Tabs -->
<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="filterTabs">
            <li class="nav-item">
                <a class="nav-link <?= $filter === 'all' ? 'active' : '' ?>"
                   href="index.php?r=gymowner/maintenancereports&filter=all">
                    <i class="bi bi-list-ul me-1"></i>All Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $filter === 'pending' ? 'active' : '' ?>"
                   href="index.php?r=gymowner/maintenancereports&filter=pending">
                    <i class="bi bi-clock me-1"></i>Pending Verification
                    <?php
                    $pendingCount = count(array_filter($reports, fn($r) => $r['status'] === 'submitted'));
                    if ($filter !== 'pending' && $pendingCount > 0):
                    ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $filter === 'verified' ? 'active' : '' ?>"
                   href="index.php?r=gymowner/maintenancereports&filter=verified">
                    <i class="bi bi-check-circle me-1"></i>Verified
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body p-0">
        <?php if (empty($reports)): ?>
            <div class="text-center py-5">
                <i class="bi bi-clipboard2 display-2 text-muted"></i>
                <h5 class="text-muted mt-3">No Reports Found</h5>
                <p class="text-muted">
                    <?php if ($filter === 'pending'): ?>
                        No inspection reports are awaiting verification.
                    <?php elseif ($filter === 'verified'): ?>
                        No verified inspection reports yet.
                    <?php else: ?>
                        No inspection reports submitted by maintenance staff yet.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Staff Name</th>
                            <th>Equipment</th>
                            <th>Category</th>
                            <th>Condition</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $r):
                            $condClass = [
                                'good'         => 'success',
                                'needs_repair' => 'warning',
                                'condemned'    => 'danger',
                            ][$r['overall_condition'] ?? ''] ?? 'secondary';
                            $condLabel = [
                                'good'         => '✅ Good',
                                'needs_repair' => '⚠️ Needs Repair',
                                'condemned'    => '🔴 Condemned',
                            ][$r['overall_condition'] ?? ''] ?? '—';
                            $statusClass = [
                                'draft'     => 'secondary',
                                'submitted' => 'warning',
                                'verified'  => 'success',
                            ][$r['status'] ?? ''] ?? 'secondary';
                            $statusLabel = [
                                'draft'     => 'Draft',
                                'submitted' => 'Pending Verification',
                                'verified'  => 'Verified',
                            ][$r['status'] ?? ''] ?? ucfirst($r['status'] ?? '');
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($r['inspection_date'] ?? '') ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                                         style="width:32px;height:32px;background:linear-gradient(135deg,var(--nf-green),var(--nf-green-light));color:#fff;font-size:.75rem;font-weight:700;flex-shrink:0">
                                        <?= strtoupper(substr($r['staff_name'] ?? 'M', 0, 1)) ?>
                                    </div>
                                    <span><?= htmlspecialchars($r['staff_name'] ?? '—') ?></span>
                                </div>
                            </td>
                            <td><strong><?= htmlspecialchars($r['equipment_name'] ?? '—') ?></strong></td>
                            <td><span class="badge" style="background:rgba(27,107,42,.1);color:var(--nf-green)"><?= htmlspecialchars($r['category'] ?? '') ?></span></td>
                            <td><span class="badge bg-<?= $condClass ?>"><?= $condLabel ?></span></td>
                            <td><span class="badge bg-<?= $statusClass ?>"><?= $statusLabel ?></span></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <!-- View modal trigger -->
                                    <button class="btn btn-sm btn-outline-primary"
                                            onclick="viewReport(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)">
                                        <i class="bi bi-eye me-1"></i>View
                                    </button>
                                    <?php if ($r['status'] === 'submitted'): ?>
                                    <form method="post" class="d-inline"
                                          onsubmit="return confirm('Verify this inspection report from <?= htmlspecialchars($r['staff_name'] ?? '') ?>?')">
                                        <input type="hidden" name="action" value="verify">
                                        <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="bi bi-check-circle me-1"></i>Verify
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Report Quick-View Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clipboard2-check me-2"></i>Inspection Report Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reportModalBody">
                <!-- filled by JS -->
            </div>
            <div class="modal-footer" id="reportModalFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
function viewReport(r) {
    const condMap = {good:'✅ Good', needs_repair:'⚠️ Needs Repair', condemned:'🔴 Condemned'};
    const condClassMap = {good:'success', needs_repair:'warning', condemned:'danger'};
    const statusMap = {draft:'Draft', submitted:'Pending Verification', verified:'Verified'};
    const statusClassMap = {draft:'secondary', submitted:'warning', verified:'success'};

    const cond  = condMap[r.overall_condition] || '—';
    const condC = condClassMap[r.overall_condition] || 'secondary';
    const stat  = statusMap[r.status] || r.status;
    const statC = statusClassMap[r.status] || 'secondary';

    document.getElementById('reportModalBody').innerHTML = `
        <div class="row g-3 mb-3">
            <div class="col-6"><small class="text-muted d-block fw-bold" style="font-size:.7rem;text-transform:uppercase;letter-spacing:1px">Equipment</small><strong>${r.equipment_name||'—'}</strong></div>
            <div class="col-6"><small class="text-muted d-block fw-bold" style="font-size:.7rem;text-transform:uppercase;letter-spacing:1px">Category</small>${r.category||'—'}</div>
            <div class="col-6"><small class="text-muted d-block fw-bold" style="font-size:.7rem;text-transform:uppercase;letter-spacing:1px">Staff</small>${r.staff_name||'—'}</div>
            <div class="col-6"><small class="text-muted d-block fw-bold" style="font-size:.7rem;text-transform:uppercase;letter-spacing:1px">Date</small>${r.inspection_date||'—'}</div>
            <div class="col-6"><small class="text-muted d-block fw-bold" style="font-size:.7rem;text-transform:uppercase;letter-spacing:1px">Condition</small><span class="badge bg-${condC}">${cond}</span></div>
            <div class="col-6"><small class="text-muted d-block fw-bold" style="font-size:.7rem;text-transform:uppercase;letter-spacing:1px">Status</small><span class="badge bg-${statC}">${stat}</span></div>
        </div>
        <div class="mb-3">
            <small class="text-muted fw-bold" style="font-size:.7rem;text-transform:uppercase;letter-spacing:1px">Remarks</small>
            <div class="p-3 rounded border mt-1" style="white-space:pre-wrap;min-height:60px">${r.remarks||'No remarks.'}</div>
        </div>
        ${(r.overall_condition === 'needs_repair' || r.overall_condition === 'condemned') ? `
        <div class="mb-3 p-2 bg-light border rounded d-flex justify-content-between align-items-center">
            <div>
                <strong class="text-warning"><i class="bi bi-cart-plus me-1"></i>Purchase Request:</strong>
                <span class="small text-muted ms-1">This equipment may have replacement parts requested.</span>
            </div>
            <a href="index.php?r=gymowner/purchaserequests" class="btn btn-sm btn-outline-warning">
                View Purchase Requests <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        ` : ''}
        ${r.submitted_at ? `<div class="text-muted small"><i class="bi bi-clock me-1"></i>Submitted: ${r.submitted_at}</div>` : ''}
        ${r.verified_at ? `<div class="text-muted small"><i class="bi bi-check-circle me-1"></i>Verified: ${r.verified_at}</div>` : ''}
    `;

    const footer = document.getElementById('reportModalFooter');
    footer.innerHTML = `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>`;
    if (r.status === 'submitted') {
        footer.innerHTML += `
            <form method="post" onsubmit="return confirm('Verify this report?')">
                <input type="hidden" name="action" value="verify">
                <input type="hidden" name="report_id" value="${r.id}">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle me-1"></i>Verify Report
                </button>
            </form>`;
    }

    new bootstrap.Modal(document.getElementById('reportModal')).show();
}
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
