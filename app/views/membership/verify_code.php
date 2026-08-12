<?php
declare(strict_types=1);
$pageTitle = 'Verify Membership';
require __DIR__ . '/../partials/header.php';
?>

<div class="mb-4">
    <h1 class="h3 mb-1"><i class="bi bi-qr-code me-2"></i>Verify Membership Code</h1>
    <p class="text-muted">Enter your membership code to verify identity and log attendance.</p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header px-3 py-2"><h2 class="h6 mb-0">Enter Membership Code</h2></div>
            <div class="card-body">
                <?php if ($gymMember): ?>
                    <div class="mb-3 p-3 rounded border border-success border-opacity-20" style="background:rgba(27,107,42,.03)">
                        <small class="text-muted d-block mb-1">Your Registered Membership Code:</small>
                        <div class="fw-bold fs-5 mb-3" style="color:#1B6B2A"><?= htmlspecialchars($gymMember['membership_code']) ?></div>
                        
                        <div class="d-grid">
                            <a href="index.php?r=membership/myid" class="btn btn-success rounded-pill fw-bold btn-sm">
                                <i class="bi bi-person-badge me-1"></i> View My Digital ID Card
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                <form method="post" class="vstack gap-3">
                    <div>
                        <label class="form-label" for="membership_code">Membership Code</label>
                        <input class="form-control form-control-lg text-center" id="membership_code" type="text" name="membership_code" placeholder="GYM-XXXXXXXX" value="<?= htmlspecialchars($gymMember['membership_code'] ?? '') ?>" required>
                    </div>
                    <button class="btn btn-primary" type="submit">Verify &amp; Check In</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header px-3 py-2"><h2 class="h6 mb-0">Attendance History</h2></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>#</th><th>Check-in Time</th></tr></thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="2" class="text-center text-muted py-4">No records yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $i => $l): ?>
                            <tr><td><?= $i + 1 ?></td><td><?= htmlspecialchars($l['check_in']) ?></td></tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
