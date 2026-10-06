<?php
declare(strict_types=1);
$pageTitle = 'Manage Gym Profile';
require __DIR__ . '/../partials/header.php';

$h = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$gymId = (int)($gym['id'] ?? 0);

// Opening hours map lookup
$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$ohMap = [];
foreach (($openingHours ?? []) as $row) {
    if (!empty($row['day'])) {
        $ohMap[$row['day']] = $row;
    }
}
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><i class="bi bi-building-gear me-2"></i>Manage Gym Profile</h1>
        <p class="text-muted mb-0">Update your gym details, opening hours, equipment, services, and pricing.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($gymId > 0): ?>
        <a href="index.php?r=membership/gymprofile&gym_id=<?= $gymId ?>" target="_blank" class="btn btn-outline-success">
            <i class="bi bi-box-arrow-up-right me-1"></i>View Public Profile
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $h($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= $h($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ── Navigation Tabs ────────────────────────────────────────── -->
<ul class="nav nav-tabs mb-4" id="gymManageTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-semibold" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic-pane" type="button" role="tab">
            <i class="bi bi-info-circle me-1"></i>Basic Info &amp; Hours
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="equip-tab" data-bs-toggle="tab" data-bs-target="#equip-pane" type="button" role="tab">
            <i class="bi bi-tools me-1"></i>Equipment (<?= count($equipment) ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="svc-tab" data-bs-toggle="tab" data-bs-target="#svc-pane" type="button" role="tab">
            <i class="bi bi-stars me-1"></i>Services (<?= count($services) ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="plans-tab" data-bs-toggle="tab" data-bs-target="#plans-pane" type="button" role="tab">
            <i class="bi bi-card-checklist me-1"></i>Plans (<?= count($plans) ?>)
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="pricing-tab" data-bs-toggle="tab" data-bs-target="#pricing-pane" type="button" role="tab">
            <i class="bi bi-person-badge me-1"></i>Training Pricing (<?= count($trainingPackages) ?>)
        </button>
    </li>
</ul>

<div class="tab-content" id="gymManageTabsContent">

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- TAB 1: BASIC INFO & OPENING HOURS                          -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div class="tab-pane fade show active" id="basic-pane" role="tabpanel">
        <form action="index.php?r=gymowner/managegym" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update_profile">

            <div class="row g-4">
                <!-- Gym Details -->
                <div class="col-lg-7">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom fw-bold py-3">
                            <i class="bi bi-building text-success me-2"></i>Gym Details &amp; Logo
                        </div>
                        <div class="card-body">
                            <!-- Logo Preview -->
                            <?php
                            $rawLogo = trim((string)($gym['gym_logo'] ?? ''));
                            $logoSrc = '';
                            if ($rawLogo !== '') {
                                if (str_starts_with($rawLogo, 'http://') || str_starts_with($rawLogo, 'https://')) {
                                    $logoSrc = $rawLogo;
                                } elseif (str_starts_with($rawLogo, 'public/')) {
                                    $logoSrc = $rawLogo;
                                } elseif (str_starts_with($rawLogo, 'uploads/')) {
                                    $logoSrc = 'public/' . $rawLogo;
                                } elseif (str_starts_with($rawLogo, '/')) {
                                    $logoSrc = 'public' . $rawLogo;
                                } else {
                                    $logoSrc = 'public/uploads/legal_documents/' . $rawLogo;
                                }
                            }
                            ?>
                            <div class="mb-4 d-flex align-items-center gap-3">
                                <div class="position-relative rounded border overflow-hidden" style="width: 80px; height: 80px; background: #f8fafc; flex-shrink: 0;">
                                    <?php if ($logoSrc !== ''): ?>
                                        <img id="gymLogoPreview" src="<?= $h($logoSrc) ?>" alt="Gym Logo" class="w-100 h-100 p-1" style="object-fit: cover;" onerror="this.style.display='none'; const fb = document.getElementById('gymLogoFallback'); if(fb) { fb.classList.remove('d-none'); fb.classList.add('d-flex'); }">
                                        <div id="gymLogoFallback" class="rounded bg-light d-none align-items-center justify-content-center text-muted w-100 h-100">
                                            <i class="bi bi-image fs-2"></i>
                                        </div>
                                    <?php else: ?>
                                        <img id="gymLogoPreview" src="" alt="Gym Logo" class="w-100 h-100 p-1 d-none" style="object-fit: cover;">
                                        <div id="gymLogoFallback" class="rounded bg-light d-flex align-items-center justify-content-center text-muted w-100 h-100">
                                            <i class="bi bi-image fs-2"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <label for="gym_logo" class="form-label fw-semibold mb-1">Gym Logo / Cover Image</label>
                                    <input type="file" name="gym_logo" id="gym_logo" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp,image/jpg" onchange="previewGymLogo(this)">
                                    <small class="text-muted">Allowed formats: JPG, PNG, WEBP (Max: 5MB)</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="gym_name" class="form-label fw-semibold">Gym Name <span class="text-danger">*</span></label>
                                <input type="text" name="gym_name" id="gym_name" class="form-control" value="<?= $h($gym['gym_name'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="gym_description" class="form-label fw-semibold">About / Overview (Short Description)</label>
                                <textarea name="gym_description" id="gym_description" class="form-control" rows="3" placeholder="Describe your gym, facilities, vibe, specializations..."><?= $h($gym['gym_description'] ?? '') ?></textarea>
                            </div>

                            <h6 class="fw-bold mt-4 mb-3 text-success"><i class="bi bi-geo-alt me-1"></i>Gym Address</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="street_address" class="form-label small fw-semibold">Street / Purok</label>
                                    <input type="text" name="street_address" id="street_address" class="form-control form-control-sm" value="<?= $h($gym['street_address'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="barangay" class="form-label small fw-semibold">Barangay</label>
                                    <input type="text" name="barangay" id="barangay" class="form-control form-control-sm" value="<?= $h($gym['barangay'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="city_municipality" class="form-label small fw-semibold">City / Municipality</label>
                                    <input type="text" name="city_municipality" id="city_municipality" class="form-control form-control-sm" value="<?= $h($gym['city_municipality'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="province" class="form-label small fw-semibold">Province</label>
                                    <input type="text" name="province" id="province" class="form-control form-control-sm" value="<?= $h($gym['province'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Opening Hours -->
                <div class="col-lg-5">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white border-bottom fw-bold py-3">
                            <i class="bi bi-clock text-success me-2"></i>Opening Hours (Mon–Sun)
                        </div>
                        <div class="card-body p-3">
                            <p class="small text-muted mb-3">Set opening and closing times for each day of the week, or toggle "Closed".</p>
                            
                            <?php foreach ($days as $day):
                                $info = $ohMap[$day] ?? ['open_time' => '06:00', 'close_time' => '22:00', 'is_closed' => 0];
                                $isClosed = !empty($info['is_closed']);
                                $openVal  = !empty($info['open_time'])  ? substr($info['open_time'], 0, 5)  : '06:00';
                                $closeVal = !empty($info['close_time']) ? substr($info['close_time'], 0, 5) : '22:00';
                            ?>
                            <div class="p-2 mb-2 rounded bg-light border day-schedule-row">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="fw-semibold small"><?= $day ?></span>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input closed-toggle" type="checkbox" 
                                               name="oh_closed_<?= $day ?>" 
                                               id="closed_<?= $day ?>" 
                                               <?= $isClosed ? 'checked' : '' ?>
                                               onchange="toggleDayHours('<?= $day ?>', this.checked)">
                                        <label class="form-check-label small text-muted" for="closed_<?= $day ?>">Closed</label>
                                    </div>
                                </div>
                                <div class="row g-2 align-items-center hours-inputs-<?= $day ?>" style="<?= $isClosed ? 'display:none;' : '' ?>">
                                    <div class="col-6">
                                        <label class="form-label text-muted" style="font-size: 0.72rem;">Open</label>
                                        <input type="time" name="oh_open_<?= $day ?>" class="form-control form-control-sm" value="<?= $openVal ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label text-muted" style="font-size: 0.72rem;">Close</label>
                                        <input type="time" name="oh_close_<?= $day ?>" class="form-control form-control-sm" value="<?= $closeVal ?>">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-success btn-lg px-4">
                        <i class="bi bi-save me-1"></i>Save Basic Info &amp; Hours
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- TAB 2: EQUIPMENT                                           -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div class="tab-pane fade" id="equip-pane" role="tabpanel">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <span class="fw-bold"><i class="bi bi-tools text-success me-2"></i>Gym Equipment</span>
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addEquipModal">
                    <i class="bi bi-plus-lg me-1"></i>Add Equipment
                </button>
            </div>
            <div class="card-body p-0">
                <?php if (empty($equipment)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-box-seam display-4 d-block mb-2"></i>
                        No equipment added yet. Click "Add Equipment" above to list items.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Brand</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($equipment as $eq): ?>
                                <tr>
                                    <td class="fw-semibold"><?= $h($eq['name']) ?></td>
                                    <td><span class="badge bg-secondary-subtle text-secondary"><?= $h($eq['category'] ?? 'General') ?></span></td>
                                    <td><?= $h($eq['brand'] ?? '—') ?></td>
                                    <td><span class="badge bg-success-subtle text-success"><?= (int)($eq['quantity'] ?? 1) ?></span></td>
                                    <td>₱<?= number_format((float)($eq['price'] ?? 0), 2) ?></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                onclick="editEquip(<?= htmlspecialchars(json_encode($eq), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="index.php?r=gymowner/managegym" method="POST" class="d-inline" onsubmit="return confirm('Remove this equipment?');">
                                            <input type="hidden" name="action" value="delete_equipment">
                                            <input type="hidden" name="equipment_id" value="<?= (int)$eq['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- TAB 3: SERVICES                                            -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div class="tab-pane fade" id="svc-pane" role="tabpanel">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <span class="fw-bold"><i class="bi bi-stars text-success me-2"></i>Gym Services</span>
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addSvcModal">
                    <i class="bi bi-plus-lg me-1"></i>Add Service
                </button>
            </div>
            <div class="card-body p-0">
                <?php if (empty($services)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-stars display-4 d-block mb-2"></i>
                        No services added yet. Click "Add Service" to list offered services.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Service Name</th>
                                    <th>Description</th>
                                    <th>Member Price</th>
                                    <th>Non-Member Price</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $s): ?>
                                <tr>
                                    <td class="fw-semibold"><?= $h($s['name']) ?></td>
                                    <td class="small text-muted"><?= $h($s['description'] ?? '—') ?></td>
                                    <td class="fw-bold text-success">₱<?= number_format((float)($s['member_price'] ?? 0), 2) ?></td>
                                    <td>₱<?= number_format((float)($s['non_member_price'] ?? 0), 2) ?></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick="editSvc(<?= htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="index.php?r=gymowner/managegym" method="POST" class="d-inline" onsubmit="return confirm('Delete this service?');">
                                            <input type="hidden" name="action" value="delete_service">
                                            <input type="hidden" name="svc_id" value="<?= (int)$s['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- TAB 4: MEMBERSHIP PLANS                                   -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div class="tab-pane fade" id="plans-pane" role="tabpanel">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <span class="fw-bold"><i class="bi bi-card-checklist text-success me-2"></i>Membership Plans</span>
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addPlanModal">
                    <i class="bi bi-plus-lg me-1"></i>Add Plan
                </button>
            </div>
            <div class="card-body p-0">
                <?php if (empty($plans)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-card-list display-4 d-block mb-2"></i>
                        No membership plans created yet. Click "Add Plan" to create pricing options.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Plan Name</th>
                                    <th>Duration</th>
                                    <th>Price</th>
                                    <th>Description</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($plans as $p): ?>
                                <tr>
                                    <td class="fw-semibold"><?= $h($p['name']) ?></td>
                                    <td><span class="badge bg-info-subtle text-info-emphasis"><?= (int)$p['duration_days'] ?> Days</span></td>
                                    <td class="fw-bold text-success">₱<?= number_format((float)($p['price'] ?? 0), 2) ?></td>
                                    <td class="small text-muted"><?= $h($p['description'] ?? '—') ?></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick="editPlan(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="index.php?r=gymowner/managegym" method="POST" class="d-inline" onsubmit="return confirm('Delete this plan?');">
                                            <input type="hidden" name="action" value="delete_plan">
                                            <input type="hidden" name="plan_id" value="<?= (int)$p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════ -->
    <!-- TAB 5: TRAINING PRICING                                    -->
    <!-- ══════════════════════════════════════════════════════════ -->
    <div class="tab-pane fade" id="pricing-pane" role="tabpanel">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <span class="fw-bold"><i class="bi bi-person-badge text-success me-2"></i>Training Pricing / Packages</span>
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addPackageModal">
                    <i class="bi bi-plus-lg me-1"></i>Add Package
                </button>
            </div>
            <div class="card-body p-0">
                <?php if (empty($trainingPackages)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-lightning display-4 d-block mb-2"></i>
                        No training packages added yet. Click "Add Package" to offer fitness training packages.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Package Name</th>
                                    <th>Training Type</th>
                                    <th>Sessions</th>
                                    <th>Duration</th>
                                    <th>Price</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trainingPackages as $pkg): ?>
                                <tr>
                                    <td class="fw-semibold"><?= $h($pkg['package_name']) ?></td>
                                    <td><span class="badge bg-secondary-subtle text-secondary"><?= $h(ucfirst($pkg['training_type'] ?? 'all')) ?></span></td>
                                    <td><span class="badge bg-success-subtle text-success"><?= (int)$pkg['session_count'] ?> sessions</span></td>
                                    <td class="small"><?= (int)($pkg['duration_minutes'] ?? 60) ?> mins</td>
                                    <td class="fw-bold text-success">₱<?= number_format((float)($pkg['price'] ?? 0), 2) ?></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick="editPackage(<?= htmlspecialchars(json_encode($pkg), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="index.php?r=gymowner/managegym" method="POST" class="d-inline" onsubmit="return confirm('Delete this package?');">
                                            <input type="hidden" name="action" value="delete_package">
                                            <input type="hidden" name="package_id" value="<?= (int)$pkg['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ════════════════════════════════════════════════════════════
     MODALS FOR ADD/EDIT EQUIPMENT, SERVICE, PLAN, PACKAGE
     ════════════════════════════════════════════════════════════ -->

<!-- Equipment Modal -->
<div class="modal fade" id="addEquipModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="index.php?r=gymowner/managegym" method="POST" class="modal-content">
            <input type="hidden" name="action" id="equip_action" value="add_equipment">
            <input type="hidden" name="equipment_id" id="equip_id" value="0">
            <div class="modal-header">
                <h5 class="modal-title" id="equipModalTitle">Add Equipment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Equipment Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="eq_name" class="form-control" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Category</label>
                        <input type="text" name="category" id="eq_cat" class="form-control" placeholder="Cardio, Strength, etc.">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Brand</label>
                        <input type="text" name="brand" id="eq_brand" class="form-control">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Quantity</label>
                        <input type="number" name="quantity" id="eq_qty" class="form-control" value="1" min="1">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Price (₱)</label>
                        <input type="number" step="0.01" name="price" id="eq_price" class="form-control" value="0">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description / Notes</label>
                    <textarea name="description" id="eq_desc" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save Equipment</button>
            </div>
        </form>
    </div>
</div>

<!-- Service Modal -->
<div class="modal fade" id="addSvcModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="index.php?r=gymowner/managegym" method="POST" class="modal-content">
            <input type="hidden" name="action" id="svc_action" value="add_service">
            <input type="hidden" name="svc_id" id="svc_id" value="0">
            <div class="modal-header">
                <h5 class="modal-title" id="svcModalTitle">Add Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Service Name <span class="text-danger">*</span></label>
                    <input type="text" name="svc_name" id="svc_name" class="form-control" required placeholder="Personal Training, Sauna, Group Classes...">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="svc_desc" id="svc_desc" class="form-control" rows="2"></textarea>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Member Price (₱) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="svc_member_price" id="svc_member_price" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Non-Member Price (₱)</label>
                        <input type="number" step="0.01" name="svc_nonmember_price" id="svc_nonmember_price" class="form-control" value="0">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save Service</button>
            </div>
        </form>
    </div>
</div>

<!-- Membership Plan Modal -->
<div class="modal fade" id="addPlanModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="index.php?r=gymowner/managegym" method="POST" class="modal-content">
            <input type="hidden" name="action" id="plan_action" value="add_plan">
            <input type="hidden" name="plan_id" id="plan_id" value="0">
            <div class="modal-header">
                <h5 class="modal-title" id="planModalTitle">Add Membership Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Plan Name <span class="text-danger">*</span></label>
                    <input type="text" name="plan_name" id="plan_name" class="form-control" required placeholder="Monthly Pass, Annual VIP...">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Duration (Days) <span class="text-danger">*</span></label>
                        <input type="number" name="plan_duration" id="plan_duration" class="form-control" value="30" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Price (₱) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="plan_price" id="plan_price" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="plan_desc" id="plan_desc" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save Plan</button>
            </div>
        </form>
    </div>
</div>

<!-- Package Modal -->
<div class="modal fade" id="addPackageModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="index.php?r=gymowner/managegym" method="POST" class="modal-content">
            <input type="hidden" name="action" id="pkg_action" value="add_package">
            <input type="hidden" name="package_id" id="pkg_id" value="0">
            <div class="modal-header">
                <h5 class="modal-title" id="pkgModalTitle">Add Training Package</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Package Name <span class="text-danger">*</span></label>
                    <input type="text" name="package_name" id="pkg_name" class="form-control" required placeholder="10 Sessions Starter, 1-on-1 Personal Training...">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Training Type</label>
                        <select name="training_type" id="pkg_type" class="form-select">
                            <option value="all">All / General</option>
                            <option value="weight_loss">Weight Loss</option>
                            <option value="muscle_building">Muscle Building</option>
                            <option value="functional">Functional Training</option>
                            <option value="cardio">Cardio & Fitness</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Session Count <span class="text-danger">*</span></label>
                        <input type="number" name="session_count" id="pkg_sessions" class="form-control" value="1" min="1" required>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Duration (Mins)</label>
                        <input type="number" name="duration_minutes" id="pkg_duration" class="form-control" value="60">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Price (₱) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="price" id="pkg_price" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" id="pkg_desc" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success">Save Package</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleDayHours(day, isClosed) {
    const box = document.querySelector('.hours-inputs-' + day);
    if (box) {
        box.style.display = isClosed ? 'none' : 'flex';
    }
}

function editEquip(item) {
    document.getElementById('equip_action').value = 'update_equipment';
    document.getElementById('equip_id').value = item.id;
    document.getElementById('equipModalTitle').innerText = 'Edit Equipment';
    document.getElementById('eq_name').value = item.name || '';
    document.getElementById('eq_cat').value = item.category || '';
    document.getElementById('eq_brand').value = item.brand || '';
    document.getElementById('eq_qty').value = item.quantity || 1;
    document.getElementById('eq_price').value = item.price || 0;
    document.getElementById('eq_desc').value = item.description || '';
    new bootstrap.Modal(document.getElementById('addEquipModal')).show();
}

function editSvc(item) {
    document.getElementById('svc_action').value = 'update_service';
    document.getElementById('svc_id').value = item.id;
    document.getElementById('svcModalTitle').innerText = 'Edit Service';
    document.getElementById('svc_name').value = item.name || '';
    document.getElementById('svc_desc').value = item.description || '';
    document.getElementById('svc_member_price').value = item.member_price || 0;
    document.getElementById('svc_nonmember_price').value = item.non_member_price || 0;
    new bootstrap.Modal(document.getElementById('addSvcModal')).show();
}

function editPlan(item) {
    document.getElementById('plan_action').value = 'update_plan';
    document.getElementById('plan_id').value = item.id;
    document.getElementById('planModalTitle').innerText = 'Edit Membership Plan';
    document.getElementById('plan_name').value = item.name || '';
    document.getElementById('plan_duration').value = item.duration_days || 30;
    document.getElementById('plan_price').value = item.price || 0;
    document.getElementById('plan_desc').value = item.description || '';
    new bootstrap.Modal(document.getElementById('addPlanModal')).show();
}

function editPackage(item) {
    document.getElementById('pkg_action').value = 'update_package';
    document.getElementById('pkg_id').value = item.id;
    document.getElementById('pkgModalTitle').innerText = 'Edit Training Package';
    document.getElementById('pkg_name').value = item.package_name || '';
    document.getElementById('pkg_type').value = item.training_type || 'all';
    document.getElementById('pkg_sessions').value = item.session_count || 1;
    document.getElementById('pkg_duration').value = item.duration_minutes || 60;
    document.getElementById('pkg_price').value = item.price || 0;
    document.getElementById('pkg_desc').value = item.description || '';
    new bootstrap.Modal(document.getElementById('addPackageModal')).show();
}

function previewGymLogo(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 5 * 1024 * 1024) {
            alert('Gym Logo exceeds maximum file size of 5MB.');
            input.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('gymLogoPreview');
            const fallback = document.getElementById('gymLogoFallback');
            if (preview) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
                preview.style.display = 'block';
            }
            if (fallback) {
                fallback.classList.add('d-none');
                fallback.classList.remove('d-flex');
            }
        };
        reader.readAsDataURL(file);
    }
}
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
