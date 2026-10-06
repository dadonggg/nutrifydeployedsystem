<?php
declare(strict_types=1);
$pageTitle = 'Apply as Gym Owner';
require __DIR__ . '/../partials/header.php';

$docLabels = [
    'cert_registration' => 'Certificate of Registration',
    'mayors_permit' => "Mayor's Permit",
    'business_name_cert' => 'Business Name Certificate',
    'fire_safety_cert' => 'Fire Safety Certificate',
];
?>

<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-building me-2"></i>Apply as Gym Owner</h1>
            <p class="text-muted mb-0">Submit your legal documents for verification by the admin.</p>
        </div>
        <div>
            <button onclick="window.location.reload()" class="btn btn-outline-secondary btn-sm" title="Refresh page">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                Last updated: <?= date('g:i A') ?>
            </small>
        </div>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($legalDoc && in_array($legalDoc['status'], ['pending', 'resubmit', 'verified'], true)): ?>
    <!-- ═══ Application Status Panel ═══ -->
    <div class="card mb-4 border-<?= $legalDoc['status'] === 'verified' ? 'success' : ($legalDoc['status'] === 'resubmit' ? 'danger' : 'warning') ?>">
        <div class="card-body text-center py-4">
            <?php if ($legalDoc['status'] === 'verified'): ?>
                <i class="bi bi-check-circle display-3 text-success mb-3"></i>
                <h2 class="h5 text-success">Application Verified!</h2>
                <p class="text-muted">All your documents have been verified. You now have full access to the Gym Owner dashboard.</p>
                <div class="mt-3">
                    <a href="index.php?r=home/index" class="btn btn-success"><i class="bi bi-speedometer2 me-1"></i> Go to Gym Owner Dashboard</a>
                </div>
            <?php elseif ($legalDoc['status'] === 'resubmit'): ?>
                <i class="bi bi-exclamation-triangle display-3 text-danger mb-3"></i>
                <h2 class="h5 text-danger">Action Required — Documents Flagged for Resubmission</h2>
                <p class="text-muted">One or more documents were flagged by the admin. Please upload a corrected file for each document marked below.</p>
            <?php else: ?>
                <i class="bi bi-hourglass-split display-3 text-warning mb-3"></i>
                <h2 class="h5">Application Under Review</h2>
                <p class="text-muted">Your application as <strong>Gym Owner</strong> is being reviewed by the admin.</p>
            <?php endif; ?>

            <?php
            // Per-doc summary counters
            $cntApproved = $cntFlagged = $cntPending = 0;
            foreach ($docLabels as $k => $l) {
                $s = $legalDoc[$k.'_status'] ?? 'pending';
                if ($s === 'approved') $cntApproved++;
                elseif ($s === 'flagged') $cntFlagged++;
                else $cntPending++;
            }
            ?>
            <div class="d-flex justify-content-center gap-3 mt-2">
                <span class="badge bg-success fs-6 px-3 py-2"><i class="bi bi-check-circle me-1"></i><?= $cntApproved ?> Approved</span>
                <span class="badge bg-danger fs-6 px-3 py-2"><i class="bi bi-x-circle me-1"></i><?= $cntFlagged ?> Flagged</span>
                <span class="badge bg-warning text-dark fs-6 px-3 py-2"><i class="bi bi-clock me-1"></i><?= $cntPending ?> Pending</span>
            </div>

            <?php if (!empty($legalDoc['gym_name'])): ?>
            <div class="mt-3 pt-3 border-top">
                <div class="row text-start">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Gym Name:</strong> <?= htmlspecialchars($legalDoc['gym_name']) ?></p>
                        <p class="mb-1"><strong>Address:</strong> <?= htmlspecialchars($legalDoc['gym_address'] ?? 'N/A') ?></p>
                        <?php if (!empty($legalDoc['street_address'])): ?>
                            <small class="text-muted d-block ms-3">
                                <strong>Street:</strong> <?= htmlspecialchars($legalDoc['street_address']) ?><br>
                                <strong>Barangay:</strong> <?= htmlspecialchars($legalDoc['barangay']) ?><br>
                                <strong>City:</strong> <?= htmlspecialchars($legalDoc['city_municipality']) ?><br>
                                <strong>Province:</strong> <?= htmlspecialchars($legalDoc['province']) ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Maintenance Staff:</strong> <?= (int)($legalDoc['maintenance_count'] ?? 0) ?></p>
                        <p class="mb-1"><strong>Fitness Trainers:</strong> <?= (int)($legalDoc['trainer_count'] ?? 0) ?></p>
                        <?php if (!empty($legalDoc['other_staff_needed'])): 
                            $others = json_decode($legalDoc['other_staff_needed'], true);
                            if (is_array($others) && !empty($others)): ?>
                                <div class="mt-2 small text-muted">
                                    <strong>Other Staff Needed:</strong>
                                    <div class="d-flex flex-wrap gap-2 mt-1">
                                        <?php foreach ($others as $item): ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($item['role']) ?>: <?= (int)$item['count'] ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; 
                        endif; ?>
                    </div>
                </div>
                <?php if (!empty($legalDoc['gym_logo'])): ?>
                <div class="mt-2">
                    <img src="public/<?= htmlspecialchars($legalDoc['gym_logo']) ?>" alt="Gym Logo" style="max-height: 80px; max-width: 200px;">
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Per-Document Status Panel -->
    <div class="card border-0 shadow-sm">
        <div class="card-header px-3 py-2 d-flex align-items-center justify-content-between" style="background:linear-gradient(135deg,rgba(13,110,253,.08),rgba(13,110,253,.03))">
            <h2 class="h6 mb-0"><i class="bi bi-file-earmark-check me-2 text-primary"></i>Document Status — Review Each Document Below</h2>
            <small class="text-muted">Scroll down to see all 4 documents</small>
        </div>
        <div class="card-body">
            <?php if (!empty($legalDoc['admin_feedback'])): ?>
                <div class="alert alert-warning mb-3">
                    <i class="bi bi-exclamation-triangle me-1"></i><strong>Admin Feedback:</strong><br>
                    <?= htmlspecialchars($legalDoc['admin_feedback']) ?>
                </div>
            <?php endif; ?>

            <div class="row g-3">
                <?php foreach ($docLabels as $key => $label):
                    $statusKey = $key . '_status';
                    $commentKey = $key . '_comment';
                    $docStatus = $legalDoc[$statusKey] ?? 'pending';
                    $docComment = $legalDoc[$commentKey] ?? '';

                    $isFlagged = ($docStatus === 'flagged');
                    $isApproved = ($docStatus === 'approved');
                    $canResubmit = !$isApproved && ($isFlagged || $legalDoc['status'] === 'resubmit');

                    $statusIcon = [
                        'approved' => 'bi-check-circle-fill text-success',
                        'flagged'  => 'bi-x-circle-fill text-danger',
                    ][$docStatus] ?? 'bi-dash-circle text-warning';
                    $statusLabel = [
                        'approved' => 'Accepted',
                        'flagged'  => 'Flagged for Resubmission',
                    ][$docStatus] ?? ($legalDoc['status'] === 'resubmit' ? 'Needs Resubmission' : 'Pending Review');
                    $statusBadge = [
                        'approved' => 'bg-success',
                        'flagged'  => 'bg-danger',
                    ][$docStatus] ?? ($legalDoc['status'] === 'resubmit' ? 'bg-danger' : 'bg-warning text-dark');
                ?>
                <div class="col-md-6">
                    <div class="border rounded p-3" style="border-color:<?= ($isFlagged || ($legalDoc['status'] === 'resubmit' && !$isApproved)) ? 'rgba(220,53,69,.5)' : ($isApproved ? 'rgba(25,135,84,.3)' : 'rgba(255,193,7,.3)') ?>!important">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-bold small">
                                <i class="<?= $statusIcon ?> me-1"></i><?= $label ?>
                            </div>
                            <span class="badge <?= $statusBadge ?>" style="font-size:.7rem"><?= $statusLabel ?></span>
                        </div>

                        <?php if (!empty($legalDoc[$key])): ?>
                            <a href="public/<?= htmlspecialchars($legalDoc[$key]) ?>" target="_blank" class="btn btn-outline-info btn-sm mb-2">
                                <i class="bi bi-file-earmark"></i> View Current Document
                            </a>
                        <?php endif; ?>

                        <?php if ($docComment): ?>
                            <div class="alert alert-danger py-1 px-2 mb-2 small">
                                <i class="bi bi-chat-left-text me-1"></i><strong>Admin Note:</strong> <?= htmlspecialchars($docComment) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($canResubmit): ?>
                            <!-- Resubmit file upload form -->
                            <form method="post" enctype="multipart/form-data" class="mt-2">
                                <input type="hidden" name="action" value="resubmit_doc">
                                <input type="hidden" name="doc_field" value="<?= $key ?>">
                                <label class="form-label small text-muted mb-1">Select updated file (PDF, JPG, PNG):</label>
                                <div class="input-group input-group-sm">
                                    <input type="file" class="form-control form-control-sm" name="<?= $key ?>" accept=".pdf,.jpg,.jpeg,.png" required>
                                    <button type="submit" class="btn btn-warning btn-sm text-dark fw-bold">
                                        <i class="bi bi-arrow-repeat"></i> Resubmit
                                    </button>
                                </div>
                            </form>
                        <?php elseif ($isApproved): ?>
                            <div class="small text-success mt-2">
                                <i class="bi bi-check-circle me-1"></i>Document approved
                            </div>
                        <?php else: ?>
                            <div class="small text-muted mt-2">
                                <i class="bi bi-clock me-1"></i>Awaiting review
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

<?php elseif ($legalDoc && $legalDoc['status'] === 'pending'): ?>
    <!-- Pending state — hide form completely to prevent duplicate submissions -->
    <div class="card border-warning">
        <div class="card-body text-center py-5">
            <i class="bi bi-shield-lock display-4 text-warning mb-3"></i>
            <h2 class="h5">Application Already Submitted</h2>
            <p class="text-muted mb-1">Your gym owner application is currently <strong class="text-warning">under review</strong>.</p>
            <p class="text-muted small">You cannot submit another application while one is pending. The admin will notify you once a decision is made.</p>
            <hr>
            <p class="mb-0 small text-muted">
                <i class="bi bi-info-circle me-1"></i>Check the <strong>Document Status</strong> panel above to see if any documents have been reviewed already.
            </p>
        </div>
    </div>

<?php else: ?>

    <!-- Unified First-time / Rejected Application Form -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom px-3 py-3">
            <h2 class="h5 mb-0 text-primary">
                <i class="bi bi-file-earmark-person me-2"></i>
                <?= ($legalDoc && $legalDoc['status'] === 'rejected') ? 'Resubmit Application as Gym Owner' : 'Apply as Gym Owner' ?>
            </h2>
        </div>
        <div class="card-body">
            <?php if ($legalDoc && $legalDoc['status'] === 'rejected'): ?>
                <div class="alert alert-danger mb-4">
                    <div class="d-flex">
                        <i class="bi bi-x-circle-fill me-2 fs-5"></i>
                        <div>
                            <strong>Application Rejected:</strong><br>
                            <?= htmlspecialchars($legalDoc['admin_feedback'] ?? 'Your application has been rejected.') ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" class="vstack gap-3">
                <input type="hidden" name="action" value="submit_all">
                
                <!-- Gym Details Section -->
                <div class="pb-3 mb-3">
                    <h3 class="h6 mb-3 text-secondary border-bottom pb-2"><i class="bi bi-building me-2 text-primary"></i>Gym Information</h3>
                    
                    <div class="mb-3">
                        <label class="form-label" for="gym_name">Gym Name <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" id="gym_name" name="gym_name" value="<?= htmlspecialchars($legalDoc['gym_name'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" for="gym_logo">Gym Logo (Optional)</label>
                        <?php if (!empty($legalDoc['gym_logo'])): ?>
                            <div class="mb-2">
                                <img src="public/<?= htmlspecialchars($legalDoc['gym_logo']) ?>" alt="Current Gym Logo" style="max-height: 60px; max-width: 150px;" class="border rounded">
                                <span class="small text-muted d-block mt-1">Current logo shown above. Upload a new file to replace it.</span>
                            </div>
                        <?php endif; ?>
                        <input class="form-control" type="file" id="gym_logo" name="gym_logo" accept=".jpg,.jpeg,.png">
                        <small class="text-muted">Accepted formats: JPG, PNG</small>
                    </div>
                    
                    <!-- Normalized Address fields -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="street_address">Street Address / Building / Unit No. <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" id="street_address" name="street_address" value="<?= htmlspecialchars($legalDoc['street_address'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="province">Province <span class="text-danger">*</span></label>
                            <select class="form-select" id="province" name="province" required>
                                <option value="" disabled <?= empty($legalDoc['province']) ? 'selected' : '' ?>>Select Province</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="city_municipality">City / Municipality <span class="text-danger">*</span></label>
                            <select class="form-select" id="city_municipality" name="city_municipality" required <?= empty($legalDoc['city_municipality']) ? 'disabled' : '' ?>>
                                <option value="" disabled <?= empty($legalDoc['city_municipality']) ? 'selected' : '' ?>>Select City / Municipality</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="barangay">Barangay <span class="text-danger">*</span></label>
                            <select class="form-select" id="barangay" name="barangay" required <?= empty($legalDoc['barangay']) ? 'disabled' : '' ?>>
                                <option value="" disabled <?= empty($legalDoc['barangay']) ? 'selected' : '' ?>>Select Barangay</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Staff Count section -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="maintenance_count">Number of Maintenance Staff Needed</label>
                            <input class="form-control" type="number" id="maintenance_count" name="maintenance_count" min="0" value="<?= (int)($legalDoc['maintenance_count'] ?? 0) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="trainer_count">Number of Fitness Trainers Needed</label>
                            <input class="form-control" type="number" id="trainer_count" name="trainer_count" min="0" value="<?= (int)($legalDoc['trainer_count'] ?? 0) ?>">
                        </div>
                    </div>

                    <!-- Dynamic Staff section -->
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between align-items-center">
                            <span>Other Staff Needed (Optional)</span>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-add-staff">
                                <i class="bi bi-plus-lg me-1"></i>Add Other Staff Needed
                            </button>
                        </label>
                        <div id="dynamic-staff-container" class="vstack gap-2">
                            <!-- Dynamic rows will be inserted here -->
                        </div>
                    </div>
                </div>
                
                <!-- Legal Documents Section -->
                <div>
                    <h3 class="h6 mb-3 text-secondary border-bottom pb-2"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Legal Documents</h3>
                    <?php if ($legalDoc && $legalDoc['status'] === 'rejected'): ?>
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="bi bi-info-circle me-1"></i>Since your previous application was rejected, please re-upload all required documents.
                        </div>
                    <?php endif; ?>
                    <?php foreach ($docLabels as $key => $label): ?>
                    <div class="mb-3">
                        <label class="form-label" for="<?= $key ?>"><?= $label ?> <span class="text-danger">*</span></label>
                        <input class="form-control" type="file" id="<?= $key ?>" name="<?= $key ?>" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <?php endforeach; ?>
                    <small class="text-muted">Accepted formats: PDF, JPG, PNG</small>
                </div>
                
                <div class="mt-4 pt-3 border-top text-end">
                    <button class="btn btn-primary px-4 py-2" type="submit">
                        <i class="bi bi-cloud-upload me-1"></i>
                        <?= ($legalDoc && $legalDoc['status'] === 'rejected') ? 'Resubmit Application' : 'Submit Application' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
// Auto-refresh page when notification is clicked or when page regains focus
let lastNotificationCheck = Date.now();

// Check if we should refresh based on URL parameter
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('refresh') === '1') {
    // Remove the refresh parameter and reload
    window.history.replaceState({}, document.title, window.location.pathname + '?r=gymowner/apply');
}

// Listen for notification clicks
document.addEventListener('click', function(e) {
    const notifLink = e.target.closest('a[href*="gymowner/apply"]');
    if (notifLink) {
        // Add refresh parameter to force reload
        e.preventDefault();
        window.location.href = notifLink.href + (notifLink.href.includes('?') ? '&' : '?') + 'refresh=1';
    }
});

// Auto-refresh when page regains focus (user comes back from another tab)
let pageHidden = false;
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        pageHidden = true;
    } else if (pageHidden) {
        // Page became visible again, check if we should refresh
        const timeSinceLastCheck = Date.now() - lastNotificationCheck;
        if (timeSinceLastCheck > 5000) { // 5 seconds
            // Silently reload the page to get fresh data
            window.location.reload();
        }
        pageHidden = false;
    }
});

// Periodic check for updates (every 30 seconds)
setInterval(function() {
    // Check if there are unread notifications
    const unreadBadge = document.querySelector('.notification-badge');
    if (unreadBadge && parseInt(unreadBadge.textContent) > 0) {
        // Show a subtle notification that updates are available
        const existingBanner = document.getElementById('update-banner');
        if (!existingBanner) {
            const banner = document.createElement('div');
            banner.id = 'update-banner';
            banner.className = 'alert alert-info alert-dismissible fade show position-fixed';
            banner.style.cssText = 'top: 80px; right: 20px; z-index: 1050; max-width: 350px;';
            banner.innerHTML = `
                <i class="bi bi-info-circle me-2"></i>
                <strong>Updates Available</strong><br>
                <small>Your document status may have changed. <a href="javascript:window.location.reload()" class="alert-link">Refresh page</a></small>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(banner);
        }
    }
}, 30000); // Check every 30 seconds

// Location cascading and dynamic staff initializers
document.addEventListener('DOMContentLoaded', function() {
    const prefilledProvince = <?= json_encode($legalDoc['province'] ?? '') ?>;
    const prefilledCity = <?= json_encode($legalDoc['city_municipality'] ?? '') ?>;
    const prefilledBarangay = <?= json_encode($legalDoc['barangay'] ?? '') ?>;
    const prefilledOtherStaff = <?= !empty($legalDoc['other_staff_needed']) ? $legalDoc['other_staff_needed'] : '[]' ?>;

    const provinceSelect = document.getElementById('province');
    const citySelect = document.getElementById('city_municipality');
    const barangaySelect = document.getElementById('barangay');
    const container = document.getElementById('dynamic-staff-container');
    const btnAdd = document.getElementById('btn-add-staff');

    if (provinceSelect) {
        initAddressDropdowns();
    }
    if (btnAdd) {
        initStaffRowBuilder();
    }

    async function initAddressDropdowns() {
        try {
            const [provinces, districts] = await Promise.all([
                fetch('https://psgc.gitlab.io/api/provinces/').then(r => r.json()),
                fetch('https://psgc.gitlab.io/api/districts/').then(r => r.json())
            ]);
            
            const combined = [
                ...provinces.map(p => ({ ...p, type: 'provinces' })),
                ...districts.map(d => ({ ...d, type: 'districts', name: 'Metro Manila - ' + d.name }))
            ];
            
            combined.sort((a, b) => a.name.localeCompare(b.name));
            
            combined.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.name;
                opt.textContent = item.name;
                opt.dataset.code = item.code;
                opt.dataset.type = item.type;
                if (prefilledProvince && prefilledProvince === item.name) {
                    opt.selected = true;
                }
                provinceSelect.appendChild(opt);
            });
            
            if (provinceSelect.value) {
                await handleProvinceChange(true);
            }
        } catch (err) {
            console.error('Failed to load provinces/districts:', err);
        }
        
        provinceSelect.addEventListener('change', () => handleProvinceChange(false));
        citySelect.addEventListener('change', () => handleCityChange(false));
        
        async function handleProvinceChange(isInitial = false) {
            citySelect.innerHTML = '<option value="" disabled selected>Select City / Municipality</option>';
            citySelect.disabled = true;
            barangaySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
            barangaySelect.disabled = true;
            
            const selectedOpt = provinceSelect.options[provinceSelect.selectedIndex];
            if (!selectedOpt || !selectedOpt.value) return;
            
            const code = selectedOpt.dataset.code;
            const type = selectedOpt.dataset.type;
            
            try {
                const cities = await fetch(`https://psgc.gitlab.io/api/${type}/${code}/cities-municipalities/`).then(r => r.json());
                cities.sort((a, b) => a.name.localeCompare(b.name));
                
                cities.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.name;
                    opt.textContent = item.name;
                    opt.dataset.code = item.code;
                    if (prefilledCity && prefilledCity === item.name && isInitial) {
                        opt.selected = true;
                    }
                    citySelect.appendChild(opt);
                });
                
                citySelect.disabled = false;
                
                if (citySelect.value && isInitial) {
                    await handleCityChange(true);
                }
            } catch (err) {
                console.error('Failed to load cities:', err);
            }
        }
        
        async function handleCityChange(isInitial = false) {
            barangaySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
            barangaySelect.disabled = true;
            
            const selectedOpt = citySelect.options[citySelect.selectedIndex];
            if (!selectedOpt || !selectedOpt.value) return;
            
            const code = selectedOpt.dataset.code;
            
            try {
                const barangays = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${code}/barangays/`).then(r => r.json());
                barangays.sort((a, b) => a.name.localeCompare(b.name));
                
                barangays.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = item.name;
                    opt.textContent = item.name;
                    if (prefilledBarangay && prefilledBarangay === item.name && isInitial) {
                        opt.selected = true;
                    }
                    barangaySelect.appendChild(opt);
                });
                
                barangaySelect.disabled = false;
            } catch (err) {
                console.error('Failed to load barangays:', err);
            }
        }
    }

    function initStaffRowBuilder() {
        function createStaffRow(role = '', count = 0) {
            const row = document.createElement('div');
            row.className = 'row g-2 align-items-center mb-2 dynamic-staff-row';
            row.innerHTML = `
                <div class="col-7">
                    <input type="text" name="other_staff_roles[]" class="form-control form-control-sm" placeholder="Role Title (e.g. Receptionist)" value="${escapeHtml(role)}" required>
                </div>
                <div class="col-4">
                    <input type="number" name="other_staff_counts[]" class="form-control form-control-sm" min="0" value="${count}" required>
                </div>
                <div class="col-1 text-end">
                    <button type="button" class="btn btn-outline-danger btn-sm btn-remove-staff" title="Remove role">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
            
            row.querySelector('.btn-remove-staff').addEventListener('click', () => {
                row.remove();
            });
            
            container.appendChild(row);
        }

        btnAdd.addEventListener('click', () => createStaffRow('', 0));

        if (Array.isArray(prefilledOtherStaff)) {
            prefilledOtherStaff.forEach(item => {
                createStaffRow(item.role, item.count);
            });
        }
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
