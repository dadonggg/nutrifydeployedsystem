<?php
declare(strict_types=1);
$pageTitle = 'Attendance Log';
require __DIR__ . '/../partials/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1"><i class="bi bi-calendar-check me-2"></i>Attendance Log</h1>
        <p class="text-muted mb-0">Member check-in records from membership code verification and QR scans.</p>
    </div>
    <a href="index.php?r=admofficer/scanqr" class="btn btn-success px-3 rounded-pill fw-bold">
        <i class="bi bi-qr-code-scan me-1"></i> Scan Member QR
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>ID</th><th>Member</th><th>Code</th><th>Check-in Time</th></tr></thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No attendance records.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                    <tr>
                        <td><?= $l['id'] ?></td>
                        <td><?= htmlspecialchars($l['fullname'] ?? '') ?></td>
                        <td><code style="color:#1B6B2A"><?= htmlspecialchars($l['membership_code']) ?></code></td>
                        <td><?= htmlspecialchars($l['check_in']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
