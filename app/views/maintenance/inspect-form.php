<?php
declare(strict_types=1);
$pageTitle = 'Inspect Equipment';
require __DIR__ . '/../partials/header.php';

// Default checklist items
$defaultItems = [
    'Check for any cracks or signs of wear',
    'Check for loose or unstable parts',
    'Wipe down with disinfectant',
    'Check safety labels and signage',
    'Test functionality and moving parts',
    'Check for rust or corrosion',
    'Verify weight/load markings are visible',
    'Check cables/straps for fraying',
];

// Use existing checklist if editing a draft
$checklistItems = [];
if (!empty($existingChecklist)) {
    foreach ($existingChecklist as $ci) {
        $checklistItems[] = [
            'description' => $ci['item_description'],
            'done'        => (bool)$ci['is_done'],
            'notes'       => $ci['notes'] ?? '',
        ];
    }
} else {
    foreach ($defaultItems as $d) {
        $checklistItems[] = ['description' => $d, 'done' => false, 'notes' => ''];
    }
}

$ei = $existingInspection;
$prItems = $existingPrItems ?? [];
?>

<style>
.checklist-row .form-check-input {
    width: 24px;
    height: 24px;
    border: 2px solid #1B6B2A !important;
    background-color: #ffffff !important;
    cursor: pointer;
    margin-top: 0.1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}
.checklist-row .form-check-input:checked {
    background-color: #1B6B2A !important;
    border-color: #1B6B2A !important;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1"><i class="bi bi-clipboard2-plus me-2"></i>Inspect Equipment</h1>
        <p class="text-muted mb-0">
            <?= htmlspecialchars($equipment['name'] ?? '') ?>
            <?php if (!empty($equipment['category'])): ?>
                &mdash; <span class="text-muted"><?= htmlspecialchars($equipment['category']) ?></span>
            <?php endif; ?>
        </p>
    </div>
    <a href="index.php?r=maintenance/equipment" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Equipment List
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

<form method="post" id="inspectionForm">
    <?php if ($ei): ?>
        <input type="hidden" name="inspection_id" value="<?= (int)$ei['id'] ?>">
    <?php endif; ?>

    <div class="row g-4">
        <!-- Left Column: Auto-fill Info + Checklist -->
        <div class="col-lg-8">

            <!-- Auto-fill Info Card -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="card-title mb-0"><i class="bi bi-info-circle me-2"></i>Inspection Details</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Equipment Name</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($equipment['name'] ?? '') ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Inspection Date</label>
                            <input type="text" class="form-control" value="<?= date('Y-m-d') ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Maintenance Staff</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" readonly>
                        </div>
                        <?php if (!empty($equipment['brand'])): ?>
                        <div class="col-md-4">
                            <label class="form-label">Brand</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($equipment['brand']) ?>" readonly>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($equipment['weight_kg'])): ?>
                        <div class="col-md-4">
                            <label class="form-label">Weight (kg)</label>
                            <input type="text" class="form-control" value="<?= $equipment['weight_kg'] ?>" readonly>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($equipment['category'])): ?>
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($equipment['category']) ?>" readonly>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Checklist Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="bi bi-list-check me-2"></i>Inspection Checklist</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">
                        <i class="bi bi-plus-circle me-1"></i>Add Item
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="checklistTable">
                            <thead>
                                <tr>
                                    <th style="width:40px">Done</th>
                                    <th>Checklist Item</th>
                                    <th style="width:200px">Notes</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody id="checklistBody">
                                <?php foreach ($checklistItems as $idx => $ci): ?>
                                <tr class="checklist-row">
                                    <td class="text-center">
                                        <input type="checkbox" name="item_done[<?= $idx ?>]"
                                               class="form-check-input" value="1"
                                               <?= $ci['done'] ? 'checked' : '' ?>>
                                    </td>
                                    <td>
                                        <input type="text" name="item_description[<?= $idx ?>]"
                                               class="form-control form-control-sm"
                                               value="<?= htmlspecialchars($ci['description']) ?>" required>
                                    </td>
                                    <td>
                                        <input type="text" name="item_notes[<?= $idx ?>]"
                                               class="form-control form-control-sm"
                                               placeholder="Optional note…"
                                               value="<?= htmlspecialchars($ci['notes'] ?? '') ?>">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-link text-danger remove-row" title="Remove">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Overall Condition -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="card-title mb-0"><i class="bi bi-activity me-2"></i>Overall Condition</h5></div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-4">
                        <div class="form-check">
                            <input class="form-check-input condition-radio" type="radio" name="overall_condition" id="cond_good" value="good"
                                <?= (!$ei || $ei['overall_condition'] === 'good') ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold text-success" for="cond_good">
                                ✅ Good
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input condition-radio" type="radio" name="overall_condition" id="cond_repair" value="needs_repair"
                                <?= ($ei && $ei['overall_condition'] === 'needs_repair') ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold text-warning" for="cond_repair">
                                ⚠️ Needs Repair
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input condition-radio" type="radio" name="overall_condition" id="cond_condemned" value="condemned"
                                <?= ($ei && $ei['overall_condition'] === 'condemned') ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold text-danger" for="cond_condemned">
                                🔴 Condemned
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Purchase Request Panel (appears when condition is Needs Repair or Condemned) -->
            <div class="card mb-4" id="purchaseRequestCard" style="<?= (!$ei || $ei['overall_condition'] === 'good') && empty($prItems) ? 'display:none;' : '' ?>">
                <div class="card-header d-flex justify-content-between align-items-center bg-light border-bottom">
                    <div>
                        <h5 class="card-title mb-0 text-dark">
                            <i class="bi bi-cart-plus me-2 text-warning"></i>Purchase Request (Parts, Tools &amp; Equipment)
                        </h5>
                        <small class="text-muted">Itemize the replacement parts, repair kits, or new equipment required for approval.</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addPrRowBtn">
                        <i class="bi bi-plus-circle me-1"></i>Add Item
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="prTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:28%">Item Name / Description <span class="text-danger">*</span></th>
                                    <th style="width:12%">Qty</th>
                                    <th style="width:18%">Estimated Price (₱)</th>
                                    <th style="width:16%">Subtotal (₱)</th>
                                    <th style="width:20%">Reason / Notes</th>
                                    <th style="width:6%;text-align:center" title="Mark if already approved or purchased">Purchased</th>
                                    <th style="width:40px"></th>
                                </tr>
                            </thead>
                            <tbody id="prTableBody">
                                <?php if (!empty($prItems)): ?>
                                    <?php foreach ($prItems as $pidx => $pItem): ?>
                                    <tr class="pr-row">
                                        <td>
                                            <input type="text" name="pr_item_name[<?= $pidx ?>]"
                                                   class="form-control form-control-sm pr-name"
                                                   placeholder="e.g., Replacement cable, pulley wheel"
                                                   value="<?= htmlspecialchars($pItem['item_name'] ?? '') ?>" required>
                                        </td>
                                        <td>
                                            <input type="number" name="pr_quantity[<?= $pidx ?>]"
                                                   class="form-control form-control-sm pr-qty"
                                                   min="1" step="1" value="<?= (int)($pItem['quantity'] ?? 1) ?>" required>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" name="pr_unit_price[<?= $pidx ?>]"
                                                       class="form-control form-control-sm pr-price"
                                                       min="0" step="0.01" value="<?= htmlspecialchars((string)($pItem['unit_price'] ?? '0.00')) ?>" required>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success pr-subtotal">₱<?= number_format((float)($pItem['quantity'] ?? 1) * (float)($pItem['unit_price'] ?? 0), 2) ?></span>
                                        </td>
                                        <td>
                                            <input type="text" name="pr_notes[<?= $pidx ?>]"
                                                   class="form-control form-control-sm"
                                                   placeholder="Optional reason…"
                                                   value="<?= htmlspecialchars($pItem['notes'] ?? '') ?>">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" name="pr_is_purchased[<?= $pidx ?>]"
                                                   class="form-check-input" value="1"
                                                   <?= !empty($pItem['is_purchased']) ? 'checked' : '' ?>>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-link text-danger remove-pr-row" title="Remove row">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty notice (shown when no rows added) -->
                    <div id="prEmptyNotice" class="text-center py-3 text-muted <?= !empty($prItems) ? 'd-none' : '' ?>">
                        <i class="bi bi-info-circle me-1"></i>No purchase request items added yet. Click <strong>"Add Item"</strong> above to add replacement parts or tools.
                    </div>

                    <!-- Total Amount Banner -->
                    <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            <i class="bi bi-info-circle me-1"></i>This request will be sent to the Owner/Admin for approval.
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted fw-bold text-uppercase" style="font-size:.8rem;letter-spacing:.5px">Estimated Total:</span>
                            <span class="fs-5 fw-bold text-success" id="prRunningTotal">₱0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Remarks -->
            <div class="card mb-4">
                <div class="card-header"><h5 class="card-title mb-0"><i class="bi bi-chat-text me-2"></i>Remarks</h5></div>
                <div class="card-body">
                    <textarea name="remarks" class="form-control" rows="4"
                              placeholder="Additional observations or notes about this inspection…"><?= htmlspecialchars($ei['remarks'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Right Column: Signature Pad -->
        <div class="col-lg-4">
            <div class="card mb-4 sticky-top" style="top:70px">
                <div class="card-header"><h5 class="card-title mb-0"><i class="bi bi-pen me-2"></i>Signature</h5></div>
                <div class="card-body">
                    <p class="small text-muted mb-2">Draw your signature below to authenticate this report.</p>
                    <div style="border:2px dashed var(--nf-border);border-radius:8px;background:#fafafa;">
                        <canvas id="signaturePad" style="width:100%;height:180px;touch-action:none;display:block;"></canvas>
                    </div>
                    <input type="hidden" name="signature_data" id="signatureData"
                           value="<?= htmlspecialchars($ei['signature_data'] ?? '') ?>">
                    <div class="d-flex gap-2 mt-2">
                        <button type="button" id="clearSig" class="btn btn-sm btn-outline-danger w-100">
                            <i class="bi bi-eraser me-1"></i>Clear
                        </button>
                    </div>
                    <?php if ($ei && $ei['signature_data']): ?>
                        <div class="mt-2">
                            <small class="text-success"><i class="bi bi-check-circle me-1"></i>Signature on file</small>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-footer">
                    <div class="d-grid gap-2">
                        <button type="submit" name="action" value="draft" class="btn btn-outline-primary">
                            <i class="bi bi-floppy me-1"></i>Save as Draft
                        </button>
                        <button type="submit" name="action" value="submit" class="btn btn-primary"
                                onclick="return confirm('Submit this report and purchase request to the Gym Owner? You cannot edit it after submitting.')">
                            <i class="bi bi-send me-1"></i>Submit to Gym Owner
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Signature Pad Library -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
// ─── Signature Pad Setup ───
const canvas = document.getElementById('signaturePad');
const sigInput = document.getElementById('signatureData');

function resizeCanvas() {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    canvas.width = canvas.offsetWidth * ratio;
    canvas.height = canvas.offsetHeight * ratio;
    canvas.getContext('2d').scale(ratio, ratio);
    signaturePad.clear();
    // Restore existing signature
    const existing = sigInput.value;
    if (existing && existing.startsWith('data:image')) {
        signaturePad.fromDataURL(existing);
    }
}

const signaturePad = new SignaturePad(canvas, {
    backgroundColor: 'rgba(255, 255, 255, 0)',
    penColor: '#1B6B2A',
});

window.addEventListener('resize', resizeCanvas);
resizeCanvas();

document.getElementById('clearSig').addEventListener('click', function () {
    signaturePad.clear();
    sigInput.value = '';
});

// Save signature data before form submit
document.getElementById('inspectionForm').addEventListener('submit', function () {
    if (!signaturePad.isEmpty()) {
        sigInput.value = signaturePad.toDataURL();
    }
});

// ─── Checklist Dynamic Rows ───
let rowIndex = <?= count($checklistItems) ?>;

document.getElementById('addRowBtn').addEventListener('click', function () {
    const tbody = document.getElementById('checklistBody');
    const tr = document.createElement('tr');
    tr.className = 'checklist-row';
    tr.innerHTML = `
        <td class="text-center">
            <input type="checkbox" name="item_done[${rowIndex}]" class="form-check-input" value="1">
        </td>
        <td>
            <input type="text" name="item_description[${rowIndex}]"
                   class="form-control form-control-sm" placeholder="Checklist item…" required>
        </td>
        <td>
            <input type="text" name="item_notes[${rowIndex}]"
                   class="form-control form-control-sm" placeholder="Optional note…">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-link text-danger remove-row" title="Remove">
                <i class="bi bi-trash"></i>
            </button>
        </td>`;
    tbody.appendChild(tr);
    rowIndex++;
    attachRemove(tr);
});

function attachRemove(row) {
    row.querySelector('.remove-row').addEventListener('click', function () {
        row.remove();
    });
}
document.querySelectorAll('.remove-row').forEach(btn => {
    attachRemove(btn.closest('tr'));
});

// ─── Purchase Request Dynamic Logic & Calculation ───
let prRowIndex = <?= count($prItems) ?>;
const prCard = document.getElementById('purchaseRequestCard');
const prTableBody = document.getElementById('prTableBody');
const prEmptyNotice = document.getElementById('prEmptyNotice');
const prRunningTotal = document.getElementById('prRunningTotal');

function calculatePrTotal() {
    let grandTotal = 0;
    const rows = prTableBody.querySelectorAll('.pr-row');
    if (rows.length === 0) {
        if (prEmptyNotice) prEmptyNotice.classList.remove('d-none');
    } else {
        if (prEmptyNotice) prEmptyNotice.classList.add('d-none');
    }

    rows.forEach(row => {
        const qtyInput = row.querySelector('.pr-qty');
        const priceInput = row.querySelector('.pr-price');
        const subtotalSpan = row.querySelector('.pr-subtotal');

        const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
        const price = parseFloat(priceInput ? priceInput.value : 0) || 0;
        const subtotal = qty * price;
        grandTotal += subtotal;

        if (subtotalSpan) {
            subtotalSpan.textContent = '₱' + subtotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    });

    if (prRunningTotal) {
        prRunningTotal.textContent = '₱' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

function addPrRow() {
    const tr = document.createElement('tr');
    tr.className = 'pr-row';
    tr.innerHTML = `
        <td>
            <input type="text" name="pr_item_name[${prRowIndex}]"
                   class="form-control form-control-sm pr-name"
                   placeholder="e.g., Replacement cable, pulley, bolt" required>
        </td>
        <td>
            <input type="number" name="pr_quantity[${prRowIndex}]"
                   class="form-control form-control-sm pr-qty"
                   min="1" step="1" value="1" required>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">₱</span>
                <input type="number" name="pr_unit_price[${prRowIndex}]"
                       class="form-control form-control-sm pr-price"
                       min="0" step="0.01" value="0.00" required>
            </div>
        </td>
        <td>
            <span class="fw-bold text-success pr-subtotal">₱0.00</span>
        </td>
        <td>
            <input type="text" name="pr_notes[${prRowIndex}]"
                   class="form-control form-control-sm"
                   placeholder="Optional reason…">
        </td>
        <td class="text-center">
            <input type="checkbox" name="pr_is_purchased[${prRowIndex}]"
                   class="form-check-input" value="1">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-link text-danger remove-pr-row" title="Remove row">
                <i class="bi bi-trash"></i>
            </button>
        </td>`;
    prTableBody.appendChild(tr);
    prRowIndex++;
    attachPrRowEvents(tr);
    calculatePrTotal();
}

function attachPrRowEvents(row) {
    const removeBtn = row.querySelector('.remove-pr-row');
    if (removeBtn) {
        removeBtn.addEventListener('click', function () {
            row.remove();
            calculatePrTotal();
        });
    }

    const inputs = row.querySelectorAll('.pr-qty, .pr-price');
    inputs.forEach(input => {
        input.addEventListener('input', calculatePrTotal);
        input.addEventListener('change', calculatePrTotal);
    });
}

document.getElementById('addPrRowBtn').addEventListener('click', addPrRow);

document.querySelectorAll('.pr-row').forEach(row => {
    attachPrRowEvents(row);
});

// Condition radio changes toggle Purchase Request card
function updatePrVisibility() {
    const isNeedsRepair = document.getElementById('cond_repair').checked;
    const isCondemned   = document.getElementById('cond_condemned').checked;

    if (isNeedsRepair || isCondemned) {
        prCard.style.display = 'block';
        // If no rows exist yet, auto-add first row for convenience
        if (prTableBody.querySelectorAll('.pr-row').length === 0) {
            addPrRow();
        }
    } else {
        prCard.style.display = 'none';
    }
}

document.querySelectorAll('.condition-radio').forEach(radio => {
    radio.addEventListener('change', updatePrVisibility);
});

// Initial calculation
calculatePrTotal();
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
