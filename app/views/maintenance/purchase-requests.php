<?php
declare(strict_types=1);
$pageTitle = 'My Purchase Requests';
require __DIR__ . '/../partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><i class="bi bi-cart-check me-2"></i>My Purchase Requests</h1>
        <p class="text-muted mb-0">Track status and review owner decisions on your equipment parts purchase requests</p>
    </div>
    <a href="index.php?r=maintenance/equipment" class="btn btn-primary">
        <i class="bi bi-clipboard2-plus me-1"></i>Inspect Equipment
    </a>
</div>

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

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($requests)): ?>
            <div class="text-center py-5">
                <i class="bi bi-cart-x display-3 text-muted"></i>
                <h5 class="text-muted mt-3">No Purchase Requests Found</h5>
                <p class="text-muted mb-3">You haven't submitted any purchase requests yet. When inspecting equipment that needs repair, you can itemize required replacement parts.</p>
                <a href="index.php?r=maintenance/equipment" class="btn btn-outline-primary">
                    <i class="bi bi-tools me-1"></i>Go to Equipment List
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:70px">ID</th>
                            <th>Date</th>
                            <th>Equipment</th>
                            <th>Condition</th>
                            <th>Items</th>
                            <th>Total Estimated</th>
                            <th>Status</th>
                            <th>Owner Feedback</th>
                            <th style="width:120px;text-align:center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $r):
                            $items = $itemsByRequest[$r['id']] ?? [];
                            $statusClass = [
                                'pending'   => 'warning',
                                'approved'  => 'success',
                                'rejected'  => 'danger',
                                'purchased' => 'info',
                            ][$r['status'] ?? 'pending'] ?? 'secondary';
                            $statusLabel = ucfirst($r['status'] ?? 'pending');

                            $condClass = [
                                'good'         => 'success',
                                'needs_repair' => 'warning',
                                'condemned'    => 'danger',
                            ][$r['overall_condition'] ?? ''] ?? 'secondary';
                            $condLabel = [
                                'good'         => 'Good',
                                'needs_repair' => 'Needs Repair',
                                'condemned'    => 'Condemned',
                            ][$r['overall_condition'] ?? ''] ?? '—';
                        ?>
                        <tr>
                            <td><span class="text-muted fw-bold">#<?= (int)$r['id'] ?></span></td>
                            <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($r['equipment_name'] ?? '—') ?></strong>
                                <?php if (!empty($r['equipment_category'])): ?>
                                    <div class="small text-muted"><?= htmlspecialchars($r['equipment_category']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $condClass ?>"><?= $condLabel ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= count($items) ?> item<?= count($items) === 1 ? '' : 's' ?></span>
                            </td>
                            <td>
                                <strong class="text-success fs-6">₱<?= number_format((float)$r['total_amount'], 2) ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-<?= $statusClass ?>"><?= $statusLabel ?></span>
                            </td>
                            <td>
                                <?php if (!empty($r['admin_notes'])): ?>
                                    <span class="text-truncate d-inline-block" style="max-width:200px;" title="<?= htmlspecialchars($r['admin_notes']) ?>">
                                        <?= htmlspecialchars($r['admin_notes']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        onclick='viewStaffPrModal(<?= json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, <?= json_encode($items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                    <i class="bi bi-eye me-1"></i>View
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal for Maintenance Officer Details View -->
<div class="modal fade" id="staffPrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background:linear-gradient(135deg,var(--nf-green),var(--nf-green-light))">
                <h5 class="modal-title"><i class="bi bi-cart-check me-2"></i>Purchase Request #<span id="staffModalPrId"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3 mb-4 p-3 bg-light rounded border">
                    <div class="col-md-4">
                        <small class="text-muted d-block fw-bold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Equipment</small>
                        <strong id="staffModalEquipName">—</strong>
                        <div class="small text-muted" id="staffModalEquipCat"></div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block fw-bold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Submitted Date</small>
                        <strong id="staffModalDate">—</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block fw-bold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Approval Status</small>
                        <span id="staffModalStatus" class="badge fs-6 mt-1 bg-secondary">Pending</span>
                    </div>
                </div>

                <h6 class="fw-bold mb-2"><i class="bi bi-list-task me-2 text-success"></i>Requested Items &amp; Owner Decisions</h6>
                <div class="table-responsive mb-4 border rounded">
                    <table class="table table-hover align-middle mb-0" style="font-size:.9rem">
                        <thead class="table-light">
                            <tr>
                                <th style="width:35px">#</th>
                                <th>Item Name</th>
                                <th style="width:60px;text-align:center">Qty</th>
                                <th style="width:110px;text-align:right">Price (₱)</th>
                                <th style="width:110px;text-align:right">Subtotal (₱)</th>
                                <th style="width:130px">Staff Note</th>
                                <th style="width:120px;text-align:center">Decision</th>
                                <th style="width:180px">Owner Reason</th>
                            </tr>
                        </thead>
                        <tbody id="staffModalItemsBody"></tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end fw-bold text-uppercase">Total Estimated Cost:</td>
                                <td class="text-end fw-bold text-success fs-6" id="staffModalTotal">₱0.00</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div id="staffModalNotesBox" class="p-3 rounded bg-light border d-none">
                    <small class="text-muted fw-bold d-block text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Owner / Admin General Response:</small>
                    <div id="staffModalNotesText" class="mt-1" style="font-size:.9rem;white-space:pre-wrap"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewStaffPrModal(req, items) {
    document.getElementById('staffModalPrId').textContent = req.id;
    document.getElementById('staffModalEquipName').textContent = req.equipment_name || '—';
    document.getElementById('staffModalEquipCat').textContent = req.equipment_category ? 'Category: ' + req.equipment_category : '';
    document.getElementById('staffModalDate').textContent = req.created_at || '—';

    const statusMap = {
        pending: { label: 'Pending Approval', class: 'bg-warning text-dark' },
        approved: { label: 'Approved', class: 'bg-success' },
        rejected: { label: 'Rejected', class: 'bg-danger' },
        purchased: { label: 'Purchased', class: 'bg-info' },
    };
    const s = statusMap[req.status] || { label: req.status, class: 'bg-secondary' };
    const badge = document.getElementById('staffModalStatus');
    badge.className = 'badge fs-6 mt-1 ' + s.class;
    badge.textContent = s.label;

    const tbody = document.getElementById('staffModalItemsBody');
    tbody.innerHTML = '';
    let grandTotal = 0;

    if (items && items.length > 0) {
        items.forEach((item, idx) => {
            const qty = parseFloat(item.quantity) || 1;
            const price = parseFloat(item.unit_price) || 0;
            const sub = qty * price;
            grandTotal += sub;

            let statusHtml = '';
            if (item.status === 'approved' || item.is_approved == 1) {
                statusHtml = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Approved to Buy</span>';
            } else if (item.status === 'purchased' || item.is_purchased == 1) {
                statusHtml = '<span class="badge bg-info"><i class="bi bi-bag-check me-1"></i>Purchased</span>';
            } else if (item.status === 'rejected') {
                statusHtml = '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Not Buying</span>';
            } else {
                statusHtml = '<span class="badge bg-secondary">Pending</span>';
            }

            const reasonHtml = (item.rejection_reason && item.rejection_reason.trim() !== '')
                ? `<div class="p-1 rounded bg-danger bg-opacity-10 border border-danger-subtle small text-danger"><i class="bi bi-info-circle me-1"></i>${escapeHtml(item.rejection_reason)}</div>`
                : '<span class="text-muted">—</span>';

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${idx + 1}</td>
                <td><strong>${escapeHtml(item.item_name || '—')}</strong></td>
                <td class="text-center">${qty}</td>
                <td class="text-end">₱${price.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                <td class="text-end fw-bold text-success">₱${sub.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                <td><small class="text-muted">${escapeHtml(item.notes || '—')}</small></td>
                <td class="text-center">${statusHtml}</td>
                <td>${reasonHtml}</td>
            `;
            tbody.appendChild(tr);
        });
    } else {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-3 text-muted">No individual items recorded.</td></tr>`;
    }

    document.getElementById('staffModalTotal').textContent = '₱' + parseFloat(req.total_amount || grandTotal).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});

    const notesBox = document.getElementById('staffModalNotesBox');
    const notesText = document.getElementById('staffModalNotesText');
    if (req.admin_notes && req.admin_notes.trim() !== '') {
        notesBox.classList.remove('d-none');
        notesText.textContent = req.admin_notes;
    } else {
        notesBox.classList.add('d-none');
    }

    new bootstrap.Modal(document.getElementById('staffPrModal')).show();
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
