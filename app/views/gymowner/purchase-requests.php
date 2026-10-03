<?php
declare(strict_types=1);
$pageTitle = 'Purchase Requests';
require __DIR__ . '/../partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><i class="bi bi-cart-check me-2"></i>Purchase Requests</h1>
        <p class="text-muted mb-0">Review, approve, or reject equipment parts, tools, and maintenance purchase requests</p>
    </div>
</div>

<?php if (!$tableReady): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Setup Required:</strong> The purchase requests tables haven't been created yet.
        Please run <code>sql/run_purchase_requests_migration.php</code> or the migration SQL to enable this feature.
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

<!-- Summary Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="background:#ffffff;border-left:4px solid var(--nf-green)!important;border-radius:10px;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted text-uppercase fw-bold" style="font-size:.7rem;letter-spacing:1px">Total Requests</span>
                        <h3 class="fw-bold mb-0 mt-1"><?= $totalCount ?></h3>
                    </div>
                    <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="background:rgba(27,107,42,.1);width:48px;height:48px">
                        <i class="bi bi-cart3 text-success fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="background:#ffffff;border-left:4px solid #ffc107!important;border-radius:10px;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted text-uppercase fw-bold" style="font-size:.7rem;letter-spacing:1px">Pending Approval</span>
                        <h3 class="fw-bold mb-0 mt-1 text-warning"><?= $pendingCount ?></h3>
                    </div>
                    <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="background:rgba(255,193,7,.15);width:48px;height:48px">
                        <i class="bi bi-clock-history text-warning fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="background:#ffffff;border-left:4px solid #198754!important;border-radius:10px;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted text-uppercase fw-bold" style="font-size:.7rem;letter-spacing:1px">Approved / Purchased</span>
                        <h3 class="fw-bold mb-0 mt-1 text-success"><?= $approvedCount + $purchasedCount ?></h3>
                    </div>
                    <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="background:rgba(25,135,84,.1);width:48px;height:48px">
                        <i class="bi bi-check2-circle text-success fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm" style="background:#ffffff;border-left:4px solid #0dcaf0!important;border-radius:10px;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted text-uppercase fw-bold" style="font-size:.7rem;letter-spacing:1px">Total Value</span>
                        <h4 class="fw-bold mb-0 mt-1 text-primary">₱<?= number_format($totalValue, 2) ?></h4>
                    </div>
                    <div class="rounded-circle p-3 d-flex align-items-center justify-content-center" style="background:rgba(13,202,240,.1);width:48px;height:48px">
                        <i class="bi bi-wallet2 text-primary fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Tabs & Table Card -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-bottom">
        <ul class="nav nav-tabs card-header-tabs" id="prFilterTabs">
            <li class="nav-item">
                <a class="nav-link <?= $filter === 'all' ? 'active fw-bold' : '' ?>"
                   href="index.php?r=gymowner/purchaserequests&filter=all">
                    <i class="bi bi-list-ul me-1"></i>All Requests (<?= $totalCount ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $filter === 'pending' ? 'active fw-bold' : '' ?>"
                   href="index.php?r=gymowner/purchaserequests&filter=pending">
                    <i class="bi bi-clock me-1"></i>Pending
                    <?php if ($pendingCount > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $filter === 'approved' ? 'active fw-bold' : '' ?>"
                   href="index.php?r=gymowner/purchaserequests&filter=approved">
                    <i class="bi bi-check-circle me-1"></i>Approved (<?= $approvedCount ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $filter === 'purchased' ? 'active fw-bold' : '' ?>"
                   href="index.php?r=gymowner/purchaserequests&filter=purchased">
                    <i class="bi bi-bag-check me-1"></i>Purchased (<?= $purchasedCount ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $filter === 'rejected' ? 'active fw-bold' : '' ?>"
                   href="index.php?r=gymowner/purchaserequests&filter=rejected">
                    <i class="bi bi-x-circle me-1"></i>Rejected (<?= $rejectedCount ?>)
                </a>
            </li>
        </ul>
    </div>

    <div class="card-body p-0">
        <?php if (empty($requests)): ?>
            <div class="text-center py-5">
                <i class="bi bi-cart-x display-3 text-muted"></i>
                <h5 class="text-muted mt-3">No Purchase Requests Found</h5>
                <p class="text-muted">
                    <?php if ($filter === 'pending'): ?>
                        There are no pending purchase requests awaiting your review.
                    <?php elseif ($filter === 'approved'): ?>
                        No approved purchase requests in this tab.
                    <?php elseif ($filter === 'purchased'): ?>
                        No purchased equipment items recorded yet.
                    <?php else: ?>
                        Maintenance staff have not submitted any purchase requests yet.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:70px">ID</th>
                            <th>Date</th>
                            <th>Equipment</th>
                            <th>Requested By</th>
                            <th>Condition</th>
                            <th>Items</th>
                            <th>Total Estimated</th>
                            <th>Status</th>
                            <th style="width:160px;text-align:center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $r):
                            $items = $itemsByRequest[$r['id']] ?? [];
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

                            $statusClass = [
                                'pending'   => 'warning',
                                'approved'  => 'success',
                                'rejected'  => 'danger',
                                'purchased' => 'info',
                            ][$r['status'] ?? 'pending'] ?? 'secondary';

                            $statusLabel = ucfirst($r['status'] ?? 'pending');
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
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                                         style="width:30px;height:30px;background:linear-gradient(135deg,var(--nf-green),var(--nf-green-light));color:#fff;font-size:.7rem;font-weight:700;flex-shrink:0">
                                        <?= strtoupper(substr($r['requester_name'] ?? 'M', 0, 1)) ?>
                                    </div>
                                    <span><?= htmlspecialchars($r['requester_name'] ?? '—') ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?= $condClass ?> text-capitalize"><?= $condLabel ?></span>
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
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick='openPrModal(<?= json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, <?= json_encode($items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                        <i class="bi bi-eye me-1"></i>View
                                    </button>
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

<!-- Purchase Request Detailed Review Modal -->
<div class="modal fade" id="prDetailModal" tabindex="-1" aria-labelledby="prDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background:linear-gradient(135deg,var(--nf-green),var(--nf-green-light))">
                <h5 class="modal-title" id="prDetailModalLabel">
                    <i class="bi bi-cart-check me-2"></i>Purchase Request #<span id="modalPrId"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Info banner -->
                <div class="row g-3 mb-4 p-3 bg-light rounded border">
                    <div class="col-md-4">
                        <small class="text-muted d-block fw-bold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Equipment</small>
                        <strong id="modalEquipName" class="fs-6">—</strong>
                        <div class="small text-muted" id="modalEquipCategory"></div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block fw-bold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Requested By</small>
                        <strong id="modalRequesterName">—</strong>
                        <div class="small text-muted" id="modalRequestedDate"></div>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block fw-bold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Current Status</small>
                        <span id="modalStatusBadge" class="badge bg-secondary fs-6 mt-1">Pending</span>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0"><i class="bi bi-list-task me-2 text-success"></i>Requested Parts / Tools</h6>
                    <div id="modalSelectAllContainer" class="d-none">
                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" onclick="toggleAllItems(true)">
                            <i class="bi bi-check-all me-1"></i>Select All
                        </button>
                        <span class="text-muted mx-1">|</span>
                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none text-danger" onclick="toggleAllItems(false)">
                            <i class="bi bi-dash-square me-1"></i>Deselect All
                        </button>
                    </div>
                </div>

                <div class="table-responsive mb-3 border rounded">
                    <table class="table table-hover align-middle mb-0" id="modalItemsTable" style="font-size:.9rem">
                        <thead class="table-light">
                            <tr id="modalTableHeader">
                                <th style="width:35px">#</th>
                                <th>Item Name</th>
                                <th style="width:60px;text-align:center">Qty</th>
                                <th style="width:110px;text-align:right">Price (₱)</th>
                                <th style="width:110px;text-align:right">Subtotal (₱)</th>
                                <th style="width:140px">Staff Note</th>
                                <th style="width:120px;text-align:center" id="colDecisionHeader">Approve / Buy</th>
                                <th style="width:180px" id="colReasonHeader">Reason if Not Buying</th>
                            </tr>
                        </thead>
                        <tbody id="modalItemsBody">
                            <!-- Populated via JS -->
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="4" class="text-end fw-bold text-uppercase">Total Estimated Cost:</td>
                                <td class="text-end fw-bold text-muted fs-6" id="modalTotalAmount">₱0.00</td>
                                <td colspan="3"></td>
                            </tr>
                            <tr id="modalApprovedTotalRow" class="table-success d-none">
                                <td colspan="4" class="text-end fw-bold text-uppercase text-success">Approved Total to Buy:</td>
                                <td class="text-end fw-bold text-success fs-5" id="modalApprovedTotalAmount">₱0.00</td>
                                <td colspan="3"><span class="badge bg-success" id="modalApprovedCountBadge">0 items approved</span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Existing Admin General Notes -->
                <div id="modalAdminNotesBox" class="mb-4 p-3 rounded bg-light border d-none">
                    <small class="text-muted fw-bold d-block text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Owner / Admin General Note:</small>
                    <div id="modalAdminNotesText" class="mt-1" style="font-size:.9rem;white-space:pre-wrap"></div>
                </div>

                <!-- Action Forms (Approve / Reject / Mark Purchased) -->
                <div id="modalActionSection" class="p-3 border rounded" style="background:#fcfdfd">
                    <h6 class="fw-bold mb-2"><i class="bi bi-sliders me-2 text-primary"></i>Decision &amp; Response</h6>
                    
                    <div class="mb-3">
                        <label for="adminResponseNotes" class="form-label small fw-bold text-muted text-uppercase">General Comment / Overall Instruction</label>
                        <textarea id="adminResponseNotes" class="form-control form-control-sm" rows="2"
                                  placeholder="Optional overall comment for maintenance staff…"></textarea>
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-end" id="modalButtonContainer">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Action Forms for POST submission -->
<form id="actionForm" method="post" style="display:none;">
    <input type="hidden" name="action" id="actionFormAction">
    <input type="hidden" name="request_id" id="actionFormRequestId">
    <input type="hidden" name="admin_notes" id="actionFormNotes">
    <div id="actionFormItemInputs"></div>
</form>

<?php endif; ?>

<script>
let currentRequest = null;
let currentItems = [];

function openPrModal(req, items) {
    currentRequest = req;
    currentItems = items || [];

    document.getElementById('modalPrId').textContent = req.id;
    document.getElementById('modalEquipName').textContent = req.equipment_name || '—';
    document.getElementById('modalEquipCategory').textContent = req.equipment_category ? 'Category: ' + req.equipment_category : '';
    document.getElementById('modalRequesterName').textContent = req.requester_name || '—';
    document.getElementById('modalRequestedDate').textContent = req.created_at || '';
    
    // Status Badge
    const statusMap = {
        pending: { label: 'Pending Approval', class: 'bg-warning text-dark' },
        approved: { label: 'Approved', class: 'bg-success' },
        rejected: { label: 'Rejected', class: 'bg-danger' },
        purchased: { label: 'Purchased', class: 'bg-info' },
    };
    const s = statusMap[req.status] || { label: req.status, class: 'bg-secondary' };
    const badge = document.getElementById('modalStatusBadge');
    badge.className = 'badge fs-6 mt-1 ' + s.class;
    badge.textContent = s.label;

    const isPending = (req.status === 'pending');
    const selectAllContainer = document.getElementById('modalSelectAllContainer');
    const approvedTotalRow = document.getElementById('modalApprovedTotalRow');

    if (isPending) {
        selectAllContainer.classList.remove('d-none');
        approvedTotalRow.classList.remove('d-none');
    } else {
        selectAllContainer.classList.add('d-none');
        approvedTotalRow.classList.add('d-none');
    }

    // Render Items table
    renderModalItems();

    // Admin notes display
    const notesBox = document.getElementById('modalAdminNotesBox');
    const notesText = document.getElementById('modalAdminNotesText');
    if (req.admin_notes && req.admin_notes.trim() !== '') {
        notesBox.classList.remove('d-none');
        notesText.textContent = req.admin_notes;
    } else {
        notesBox.classList.add('d-none');
    }

    // Button controls
    const btnContainer = document.getElementById('modalButtonContainer');
    btnContainer.innerHTML = '';
    document.getElementById('adminResponseNotes').value = '';

    if (isPending) {
        btnContainer.innerHTML = `
            <button type="button" class="btn btn-outline-danger" onclick="submitDecision('reject')">
                <i class="bi bi-x-circle me-1"></i>Reject All
            </button>
            <button type="button" class="btn btn-success" onclick="submitDecision('approve')">
                <i class="bi bi-check-circle me-1"></i>Save &amp; Approve Selected
            </button>
        `;
    } else if (req.status === 'approved') {
        btnContainer.innerHTML = `
            <button type="button" class="btn btn-info text-white" onclick="submitDecision('mark_purchased')">
                <i class="bi bi-bag-check me-1"></i>Mark as Purchased
            </button>
        `;
    } else {
        btnContainer.innerHTML = `<span class="text-muted small">This request is <strong>${req.status}</strong>. No further actions needed.</span>`;
    }

    new bootstrap.Modal(document.getElementById('prDetailModal')).show();
}

function renderModalItems() {
    const tbody = document.getElementById('modalItemsBody');
    tbody.innerHTML = '';
    let grandTotal = 0;
    const isPending = (currentRequest && currentRequest.status === 'pending');

    if (currentItems && currentItems.length > 0) {
        currentItems.forEach((item, idx) => {
            const qty = parseFloat(item.quantity) || 1;
            const price = parseFloat(item.unit_price) || 0;
            const sub = qty * price;
            grandTotal += sub;

            const tr = document.createElement('tr');
            tr.id = 'modalItemRow_' + item.id;
            tr.setAttribute('data-item-id', item.id);
            tr.setAttribute('data-subtotal', sub);

            if (isPending) {
                // By default item is checked unless explicitly marked rejected
                const isChecked = (item.status !== 'rejected');
                tr.className = isChecked ? 'table-success bg-opacity-25' : 'table-light opacity-75';

                tr.innerHTML = `
                    <td>${idx + 1}</td>
                    <td><strong>${escapeHtml(item.item_name || '—')}</strong></td>
                    <td class="text-center">${qty}</td>
                    <td class="text-end">₱${price.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                    <td class="text-end fw-bold text-success">₱${sub.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                    <td><small class="text-muted">${escapeHtml(item.notes || '—')}</small></td>
                    <td class="text-center">
                        <div class="form-check form-switch d-inline-block">
                            <input class="form-check-input item-approve-checkbox" type="checkbox"
                                   id="chk_item_${item.id}" data-item-id="${item.id}"
                                   ${isChecked ? 'checked' : ''} onchange="handleItemCheckboxChange(${item.id})">
                            <label class="form-check-label small fw-bold ms-1" id="lbl_chk_${item.id}" for="chk_item_${item.id}">
                                ${isChecked ? '<span class="text-success">Buy</span>' : '<span class="text-danger">Skip</span>'}
                            </label>
                        </div>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm item-rejection-reason"
                               id="reason_item_${item.id}" data-item-id="${item.id}"
                               placeholder="Why not buying? (e.g., Too expensive, have spare)…"
                               value="${escapeHtml(item.rejection_reason || '')}"
                               style="${isChecked ? 'display:none;' : 'display:block;'}">
                        <span class="small text-success fw-bold item-approved-text" id="approved_text_${item.id}" style="${isChecked ? 'display:block;' : 'display:none;'}">
                            <i class="bi bi-check-circle me-1"></i>Approved to buy
                        </span>
                    </td>
                `;
            } else {
                // Read-only view for reviewed requests
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
            }

            tbody.appendChild(tr);
        });
    } else {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-3 text-muted">No individual items recorded.</td></tr>`;
    }

    document.getElementById('modalTotalAmount').textContent = '₱' + parseFloat(currentRequest.total_amount || grandTotal).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});

    if (isPending) {
        updateApprovedCalculation();
    }
}

function handleItemCheckboxChange(itemId) {
    const chk = document.getElementById('chk_item_' + itemId);
    const row = document.getElementById('modalItemRow_' + itemId);
    const lbl = document.getElementById('lbl_chk_' + itemId);
    const reasonInput = document.getElementById('reason_item_' + itemId);
    const approvedText = document.getElementById('approved_text_' + itemId);

    if (chk.checked) {
        row.className = 'table-success bg-opacity-25';
        lbl.innerHTML = '<span class="text-success">Buy</span>';
        if (reasonInput) reasonInput.style.display = 'none';
        if (approvedText) approvedText.style.display = 'block';
    } else {
        row.className = 'table-light opacity-75';
        lbl.innerHTML = '<span class="text-danger">Skip</span>';
        if (reasonInput) {
            reasonInput.style.display = 'block';
            reasonInput.focus();
        }
        if (approvedText) approvedText.style.display = 'none';
    }

    updateApprovedCalculation();
}

function toggleAllItems(selectAll) {
    const checkboxes = document.querySelectorAll('.item-approve-checkbox');
    checkboxes.forEach(chk => {
        chk.checked = selectAll;
        const itemId = chk.getAttribute('data-item-id');
        handleItemCheckboxChange(itemId);
    });
}

function updateApprovedCalculation() {
    let approvedTotal = 0;
    let approvedCount = 0;
    const checkboxes = document.querySelectorAll('.item-approve-checkbox');

    checkboxes.forEach(chk => {
        if (chk.checked) {
            const itemId = chk.getAttribute('data-item-id');
            const row = document.getElementById('modalItemRow_' + itemId);
            const sub = parseFloat(row.getAttribute('data-subtotal')) || 0;
            approvedTotal += sub;
            approvedCount++;
        }
    });

    const totalEl = document.getElementById('modalApprovedTotalAmount');
    const badgeEl = document.getElementById('modalApprovedCountBadge');
    if (totalEl) {
        totalEl.textContent = '₱' + approvedTotal.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
    }
    if (badgeEl) {
        badgeEl.textContent = approvedCount + ' of ' + checkboxes.length + ' item(s) to buy';
    }
}

function submitDecision(action) {
    if (!currentRequest) return;
    const notes = document.getElementById('adminResponseNotes').value.trim();
    const itemInputsContainer = document.getElementById('actionFormItemInputs');
    itemInputsContainer.innerHTML = '';

    if (action === 'approve') {
        const checkboxes = document.querySelectorAll('.item-approve-checkbox');
        let approvedCount = 0;

        checkboxes.forEach(chk => {
            const itemId = chk.getAttribute('data-item-id');
            if (chk.checked) {
                approvedCount++;
                const hiddenApproved = document.createElement('input');
                hiddenApproved.type = 'hidden';
                hiddenApproved.name = 'approved_items[]';
                hiddenApproved.value = itemId;
                itemInputsContainer.appendChild(hiddenApproved);
            } else {
                const reasonInput = document.getElementById('reason_item_' + itemId);
                const reasonVal = reasonInput ? reasonInput.value.trim() : '';

                const hiddenReason = document.createElement('input');
                hiddenReason.type = 'hidden';
                hiddenReason.name = `item_rejection_reasons[${itemId}]`;
                hiddenReason.value = reasonVal;
                itemInputsContainer.appendChild(hiddenReason);
            }
        });

        const confirmMsg = approvedCount > 0
            ? `Approve ${approvedCount} selected item(s) for purchase?`
            : `No items were selected to buy. This will reject all items in this request. Proceed?`;

        if (!confirm(confirmMsg)) return;
    } else if (action === 'reject') {
        if (!confirm('Are you sure you want to reject this entire purchase request?')) return;
    } else if (action === 'mark_purchased') {
        if (!confirm('Mark all approved items in this purchase request as purchased?')) return;
    }

    document.getElementById('actionFormAction').value = action;
    document.getElementById('actionFormRequestId').value = currentRequest.id;
    document.getElementById('actionFormNotes').value = notes;
    document.getElementById('actionForm').submit();
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
