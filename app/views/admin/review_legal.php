<?php
declare(strict_types=1);
$pageTitle = 'Review Legal Documents';
require __DIR__ . '/../partials/header.php';
?>

<div class="mb-4">
    <a href="index.php?r=admin/legalreviews" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left"></i> Back</a>
    <h1 class="h3 mb-1">Review Application #<?= $doc['id'] ?></h1>
    <p class="text-muted">Applicant: <strong><?= htmlspecialchars($applicant['fullname'] ?? '') ?></strong> (<?= htmlspecialchars($applicant['email'] ?? '') ?>) &bull; Role: <span class="badge <?= ($applicant['role'] ?? '') === 'gym_owner' ? 'bg-success' : 'bg-primary' ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $applicant['role'] ?? 'user'))) ?></span></p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle me-1"></i><?= $success ?>
        <?php
        // Re-fetch applicant from DB to confirm live role
        $liveApplicant = (new \App\Models\User())->findById((int)$doc['user_id']);
        $liveRole = $liveApplicant['role'] ?? 'unknown';
        ?>
        <br><small>Live role in database: <strong class="badge <?= $liveRole === 'gym_owner' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $liveRole))) ?></strong></small>
        <?php if ($liveRole !== 'gym_owner'): ?>
        <br><small class="text-danger"><i class="bi bi-exclamation-triangle"></i> Role was NOT updated. Use the "Convert to Gym Owner" button below to manually convert.</small>
        <?php else: ?>
        <br><small class="text-success"><i class="bi bi-check-circle"></i> The applicant just needs to refresh their page or go to <strong>home/index</strong> — no logout required.</small>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
$docFields = [
    'cert_registration' => 'Certificate of Registration',
    'mayors_permit' => "Mayor's Permit",
    'business_name_cert' => 'Business Name Certificate',
    'fire_safety_cert' => 'Fire Safety Certificate',
];
?>

<div class="row g-4">
    <!-- Left: Gym details and per-document review cards -->
    <div class="col-lg-8">
        <!-- Gym details summary -->
        <div class="card mb-4">
            <div class="card-header px-3 py-2">
                <h2 class="h6 mb-0"><i class="bi bi-building me-1"></i>Gym Details</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center mb-3 mb-md-0">
                        <?php if (!empty($doc['gym_logo'])): ?>
                            <img src="public/<?= htmlspecialchars($doc['gym_logo']) ?>" alt="Gym Logo" class="img-fluid rounded border" style="max-height: 120px; object-fit: contain;">
                        <?php else: ?>
                            <div class="bg-light rounded border d-flex align-items-center justify-content-center" style="height: 120px;">
                                <i class="bi bi-building display-6 text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-9">
                        <h3 class="h5 mb-2"><?= htmlspecialchars($doc['gym_name'] ?? 'N/A') ?></h3>
                        <p class="mb-2"><strong>Address:</strong> <?= htmlspecialchars($doc['gym_address'] ?? 'N/A') ?></p>
                        <?php if (!empty($doc['street_address'])): ?>
                            <div class="row small text-muted mb-3 ms-1">
                                <div class="col-sm-6 ps-0">
                                    <strong>Street:</strong> <?= htmlspecialchars($doc['street_address']) ?><br>
                                    <strong>Barangay:</strong> <?= htmlspecialchars($doc['barangay']) ?>
                                </div>
                                <div class="col-sm-6 ps-0">
                                    <strong>City:</strong> <?= htmlspecialchars($doc['city_municipality']) ?><br>
                                    <strong>Province:</strong> <?= htmlspecialchars($doc['province']) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <h4 class="h6 mb-2">Staff Requested</h4>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-warning text-dark">Maintenance Staff: <?= (int)($doc['maintenance_count'] ?? 0) ?></span>
                            <span class="badge bg-success">Fitness Trainers: <?= (int)($doc['trainer_count'] ?? 0) ?></span>
                            <?php if (!empty($doc['other_staff_needed'])): 
                                $others = json_decode($doc['other_staff_needed'], true);
                                if (is_array($others) && !empty($others)): 
                                    foreach ($others as $item): ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($item['role']) ?>: <?= (int)$item['count'] ?></span>
                                    <?php endforeach; 
                                endif; 
                            endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header px-3 py-2"><h2 class="h6 mb-0"><i class="bi bi-file-earmark-check me-1"></i>Document Verification Checklist</h2></div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($docFields as $key => $label):
                        $statusKey = $key . '_status';
                        $commentKey = $key . '_comment';
                        $checkedKey = $key . '_checked';
                        $docStatus = $doc[$statusKey] ?? 'pending';
                        $docComment = $doc[$commentKey] ?? '';
                        $docChecked = (int)($doc[$checkedKey] ?? 0);

                        $statusBadge = [
                            'approved' => 'bg-success',
                            'flagged' => 'bg-danger',
                        ][$docStatus] ?? 'bg-warning text-dark';
                        $statusIcon = [
                            'approved' => 'bi-check-circle-fill text-success',
                            'flagged' => 'bi-exclamation-triangle-fill text-danger',
                        ][$docStatus] ?? 'bi-clock-fill text-warning';
                    ?>
                    <div class="col-md-6">
                        <div class="border rounded p-3" style="border-color:rgba(27,107,42,.15)!important">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="fw-bold small"><i class="<?= $statusIcon ?> me-1"></i><?= $label ?></div>
                                <span class="badge <?= $statusBadge ?>" style="font-size:.7rem"><?= ucfirst($docStatus) ?></span>
                            </div>

                            <?php if (!empty($doc[$key])): ?>
                                <a href="public/<?= htmlspecialchars($doc[$key]) ?>" target="_blank" class="btn btn-outline-info btn-sm mb-2">
                                    <i class="bi bi-file-earmark"></i> View Document
                                </a>
                            <?php else: ?>
                                <span class="text-danger small d-block mb-2">Not uploaded</span>
                            <?php endif; ?>

                            <!-- Per-document action form -->
                            <form method="post" class="mt-2">
                                <input type="hidden" name="action" value="update_doc_status">
                                <input type="hidden" name="doc_field" value="<?= $key ?>">

                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="doc_checked" id="check_<?= $key ?>" value="1" <?= $docChecked ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="check_<?= $key ?>">Verified / Reviewed</label>
                                </div>

                                <div class="mb-2">
                                    <textarea class="form-control form-control-sm" name="doc_comment" rows="2" placeholder="Comment for this document..."><?= htmlspecialchars($docComment) ?></textarea>
                                </div>

                                <div class="d-flex gap-1">
                                    <button type="submit" name="doc_status" value="approved" class="btn btn-success btn-sm" style="font-size:.75rem">
                                        <i class="bi bi-check"></i> Approve
                                    </button>
                                    <button type="submit" name="doc_status" value="flagged" class="btn btn-danger btn-sm" style="font-size:.75rem">
                                        <i class="bi bi-flag"></i> Flag Issue
                                    </button>
                                    <button type="submit" name="doc_status" value="pending" class="btn btn-outline-secondary btn-sm" style="font-size:.75rem">
                                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Overall status & bulk actions -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header px-3 py-2"><h2 class="h6 mb-0"><i class="bi bi-clipboard-check me-1"></i>Overall Status</h2></div>
            <div class="card-body">
                <?php
                $badge = [
                    'pending' => 'bg-warning text-dark',
                    'verified' => 'bg-success',
                    'resubmit' => 'bg-danger',
                    'rejected' => 'bg-dark',
                ][$doc['status']] ?? 'bg-secondary';
                ?>
                <div class="text-center mb-3">
                    <span class="badge <?= $badge ?>" style="font-size:.9rem;padding:8px 16px"><?= ucfirst($doc['status']) ?></span>
                </div>

                <!-- Summary of per-document statuses -->
                <div class="small mb-3">
                    <?php foreach ($docFields as $key => $label):
                        $s = $doc[$key . '_status'] ?? 'pending';
                        $icon = [ 'approved'=>'bi-check-circle text-success', 'flagged'=>'bi-x-circle text-danger' ][$s] ?? 'bi-dash-circle text-warning';
                    ?>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi <?= $icon ?>"></i>
                        <span><?= $label ?>: <strong><?= ucfirst($s) ?></strong></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($doc['admin_feedback']): ?>
                    <div class="mt-2 small p-2 rounded" style="background:rgba(27,107,42,.05)">
                        <span class="text-muted fw-bold">Feedback:</span><br>
                        <?= htmlspecialchars($doc['admin_feedback']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header px-3 py-2"><h2 class="h6 mb-0"><i class="bi bi-lightning me-1"></i>Bulk Actions</h2></div>
            <div class="card-body">
                <form method="post" class="vstack gap-3">
                    <div>
                        <label class="form-label" for="feedback">Overall Feedback</label>
                        <textarea class="form-control" id="feedback" name="feedback" rows="3" placeholder="Optional feedback..."><?= htmlspecialchars($doc['admin_feedback'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php
                        // Check if there are any flagged documents
                        $hasFlaggedDocs = false;
                        $flaggedDocsList = [];
                        foreach ($docFields as $key => $label) {
                            $statusKey = $key . '_status';
                            if (($doc[$statusKey] ?? 'pending') === 'flagged') {
                                $hasFlaggedDocs = true;
                                $flaggedDocsList[] = $label;
                            }
                        }
                        ?>
                        
                        <button type="submit" name="action" value="verify" class="btn btn-success btn-sm" 
                                <?= $hasFlaggedDocs ? 'disabled title="Cannot verify while documents are flagged: ' . htmlspecialchars(implode(', ', $flaggedDocsList)) . '"' : '' ?>>
                            <i class="bi bi-check-circle"></i> Verify All & Convert to Gym Owner
                        </button>
                        
                        <button type="submit" name="action" value="resubmit" class="btn btn-warning btn-sm text-dark"><i class="bi bi-arrow-repeat"></i> Request Resubmit</button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm"><i class="bi bi-x-circle"></i> Reject</button>
                        <?php if (($applicant['role'] ?? '') !== 'gym_owner'): ?>
                            <button type="submit" name="action" value="convert" class="btn btn-primary btn-sm"><i class="bi bi-person-check"></i> Convert to Gym Owner</button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($hasFlaggedDocs): ?>
                    <div class="alert alert-warning mt-3 mb-0" style="font-size: 0.875rem;">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Cannot Verify All:</strong> The following documents are flagged and must be resolved first: 
                        <strong><?= htmlspecialchars(implode(', ', $flaggedDocsList)) ?></strong>. 
                        Please approve or reset these documents before using "Verify All".
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
