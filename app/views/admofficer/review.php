<?php
declare(strict_types=1);
$pageTitle = 'Review Membership';
require __DIR__ . '/../partials/header.php';
?>

<div class="mb-4">
    <a href="index.php?r=admofficer/memberships" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left"></i> Back</a>
    <h1 class="h3 mb-1">Review Membership #<?= $app['id'] ?></h1>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header px-3 py-2"><h2 class="h6 mb-0">Application Details</h2></div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-sm-4">Full Name</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($app['first_name'] . ' ' . ($app['middle_initial'] ? $app['middle_initial'] . '. ' : '') . $app['last_name']) ?></dd>
                    <dt class="col-sm-4">Phone</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($app['phone_number']) ?></dd>
                    <dt class="col-sm-4">Account</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($app['fullname']) ?> (<?= htmlspecialchars($app['email']) ?>)</dd>
                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <?php $badge = [
                            'pending'=>'bg-warning text-dark','verified'=>'bg-info','approved'=>'bg-success',
                            'rejected'=>'bg-danger','resubmit'=>'bg-secondary'
                        ][$app['status']] ?? 'bg-secondary'; ?>
                        <span class="badge <?= $badge ?>"><?= ucfirst($app['status']) ?></span>
                    </dd>
                    <dt class="col-sm-4">Plan</dt>
                    <dd class="col-sm-8">
                        <?= ucfirst(str_replace('_', ' ', $app['payment_type'] ?? 'N/A')) ?>
                        — <strong>₱<?= number_format((float)($app['payment_amount'] ?? 0), 2) ?></strong>
                    </dd>
                    <?php if (!empty($app['student_proof'])): ?>
                    <dt class="col-sm-4">Student Proof</dt>
                    <dd class="col-sm-8">
                        <a href="public/<?= htmlspecialchars($app['student_proof']) ?>" target="_blank" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-file-earmark"></i> View
                        </a>
                    </dd>
                    <?php endif; ?>
                    <?php if (!empty($app['preferred_trainer_id'])): ?>
                    <dt class="col-sm-4">Assigned Trainer</dt>
                    <dd class="col-sm-8">
                        <?php
                        $assignedTrainer = null;
                        foreach ($employees as $e) {
                            if ((int)$e['id'] === (int)$app['preferred_trainer_id']) { $assignedTrainer = $e; break; }
                        }
                        ?>
                        <?= $assignedTrainer ? htmlspecialchars($assignedTrainer['fullname']) : 'ID#' . $app['preferred_trainer_id'] ?>
                    </dd>
                    <?php endif; ?>
                    <dt class="col-sm-4">Applied</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($app['created_at']) ?></dd>
                </dl>
                <?php if ($app['admin_feedback']): ?>
                    <div class="mt-2 small p-2 rounded" style="background:rgba(27,107,42,.05)">
                        <span class="text-muted fw-bold">Previous Feedback:</span><br>
                        <?= htmlspecialchars($app['admin_feedback']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <?php if (in_array($app['status'], ['pending', 'resubmit'], true)): ?>
        <div class="card mb-3">
            <div class="card-header px-3 py-2"><h2 class="h6 mb-0">Review Actions</h2></div>
            <div class="card-body">
                <form method="post" class="vstack gap-3">
                    <div>
                        <label class="form-label" for="trainer_id">Assign Fitness Trainer</label>
                        <select class="form-select" name="trainer_id" id="trainer_id">
                            <option value="">— Select trainer to assign —</option>
                            <?php foreach ($trainers as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['fullname']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">The trainer will be assigned when you verify or approve.</div>
                    </div>
                    <div>
                        <label class="form-label" for="feedback">Feedback</label>
                        <textarea class="form-control" id="feedback" name="feedback" rows="3" placeholder="Provide feedback..."><?= htmlspecialchars($app['admin_feedback'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" name="action" value="verify" class="btn btn-info btn-sm text-white"><i class="bi bi-check"></i> Verify</button>
                        <button type="submit" name="action" value="resubmit" class="btn btn-warning btn-sm text-dark"><i class="bi bi-arrow-repeat"></i> Resubmit</button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm"><i class="bi bi-x-circle"></i> Reject</button>
                    </div>
                </form>
            </div>
        </div>

        <?php elseif ($app['status'] === 'verified'): ?>
        <div class="card mb-3">
            <div class="card-header px-3 py-2"><h2 class="h6 mb-0"><i class="bi bi-cash-coin me-1"></i>Payment Confirmation</h2></div>
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    <strong>Amount Due:</strong> ₱<?= number_format((float)($app['payment_amount'] ?? 0), 2) ?><br>
                    <strong>Plan:</strong> <?= ucfirst(str_replace('_', ' ', $app['payment_type'] ?? 'N/A')) ?>
                </div>
                <form method="post" class="vstack gap-3">
                    <div>
                        <label class="form-label" for="trainer_id">Assign Fitness Trainer</label>
                        <select class="form-select" name="trainer_id" id="trainer_id">
                            <option value="">— No trainer —</option>
                            <?php foreach ($trainers as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= ($app['preferred_trainer_id'] ?? '') == $t['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['fullname']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                     <button type="submit" name="action" value="paid" class="btn btn-success"><i class="bi bi-check-circle me-1"></i>Confirm Payment &amp; Generate Member ID</button>
                </form>
            </div>
        </div>

        <?php elseif ($app['status'] === 'approved'): ?>
        <?php
            $gymMemberModel = new \App\Models\GymMember();
            $mDetails = $gymMemberModel->findByUserId((int)$app['user_id']);
        ?>
        <div class="card mb-3">
            <div class="card-header bg-success text-white px-3 py-2">
                <h2 class="h6 mb-0"><i class="bi bi-shield-check me-1"></i>Membership Active</h2>
            </div>
            <div class="card-body text-center py-4">
                <i class="bi bi-check-circle display-5 text-success mb-2"></i>
                <h3 class="h5 fw-bold mb-1">Approved &amp; Paid</h3>
                <p class="text-muted small">The member record has been created and the digital ID card is active.</p>

                <?php if ($mDetails): ?>
                <!-- Digital ID Card Preview -->
                <div class="mx-auto my-4 text-start shadow-sm" style="max-width: 320px; border-radius: 16px; overflow: hidden; border: 1px solid #1B6B2A; background: #fff;">
                    <!-- Card Header -->
                    <div class="p-3 text-white d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #0e1c12 0%, #164a20 60%, #1B6B2A 100%);">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-lightning-fill text-success fs-5"></i>
                            <span class="fw-bold tracking-wide" style="font-size: .85rem; letter-spacing: 0.5px;">NUTRIFY MEMBER</span>
                        </div>
                        <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-30" style="font-size: .65rem;">ACTIVE</span>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="p-3 text-center">
                        <div class="d-flex justify-content-center mb-2">
                            <?php if (!empty($mDetails['profile_picture_url'])): ?>
                                <img src="public/<?= htmlspecialchars($mDetails['profile_picture_url']) ?>" alt="Avatar" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #1B6B2A;">
                            <?php else: ?>
                                <div class="bg-success text-white d-flex align-items-center justify-content-center fw-bold" style="width: 80px; height: 80px; border-radius: 50%; font-size: 2rem; background: linear-gradient(135deg, #1B6B2A 0%, #2E8B3E 100%); border: 3px solid #1B6B2A;">
                                    <?= strtoupper(substr($app['first_name'] ?? 'U', 0, 1) . substr($app['last_name'] ?? '', 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <h4 class="h6 fw-bold text-dark mb-1"><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></h4>
                        <div class="text-success fw-bold font-monospace mb-3" style="font-size: .85rem; letter-spacing: 1px;">
                            ID: <?= htmlspecialchars($mDetails['member_id_number'] ?? 'N/A') ?>
                        </div>
                        
                        <!-- QR Code Area -->
                        <div class="d-flex justify-content-center p-2 mb-3 bg-light rounded-3" style="width: 120px; height: 120px; margin: 0 auto;">
                            <div id="cardQrCode"></div>
                        </div>
                        
                        <!-- Details list -->
                        <div class="row g-2 border-top pt-3 text-start text-muted" style="font-size: .75rem;">
                            <div class="col-6">
                                <span class="d-block text-uppercase text-secondary" style="font-size: .6rem; font-weight: 600;">Plan Type</span>
                                <strong class="text-dark"><?= ucfirst(str_replace('_', ' ', $mDetails['payment_type'] ?? 'N/A')) ?></strong>
                            </div>
                            <div class="col-6">
                                <span class="d-block text-uppercase text-secondary" style="font-size: .6rem; font-weight: 600;">Expires</span>
                                <strong class="text-dark"><?= !empty($mDetails['expiration_date']) ? date('M d, Y', strtotime($mDetails['expiration_date'])) : 'Never' ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Load QRCode library to render preview -->
                <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const token = "<?= esc_attr($mDetails['qr_token'] ?? '') ?>";
                        if (token) {
                            new QRCode(document.getElementById("cardQrCode"), {
                                text: token,
                                width: 104,
                                height: 104,
                                colorDark : "#0e1c12",
                                colorLight : "#f8f9fa",
                                correctLevel : QRCode.CorrectLevel.H
                            });
                        }
                    });
                </script>
                <?php endif; ?>

                <?php if ($app['admin_feedback']): ?>
                    <p class="small text-muted mt-2 mb-0"><strong>Admin Note:</strong> <?= htmlspecialchars($app['admin_feedback']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <!-- Assign trainer to already-approved member -->
        <?php if (empty($app['preferred_trainer_id'])): ?>
        <div class="card">
            <div class="card-header px-3 py-2"><h2 class="h6 mb-0"><i class="bi bi-person-check me-1"></i>Assign Trainer</h2></div>
            <div class="card-body">
                <form method="post">
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" name="trainer_id" required>
                            <option value="">— Select trainer —</option>
                            <?php foreach ($trainers as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['fullname']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="action" value="assign_trainer" class="btn btn-primary btn-sm"><i class="bi bi-person-check"></i> Assign</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-4">
                <i class="bi bi-x-circle display-4 text-danger"></i>
                <p class="mt-2 mb-0">This membership was <strong>rejected</strong>.</p>
                <?php if ($app['admin_feedback']): ?>
                    <p class="small text-muted mt-2"><?= htmlspecialchars($app['admin_feedback']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
