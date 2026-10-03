<?php
declare(strict_types=1);
$pageTitle = ucfirst($role) . ' Dashboard';
require __DIR__ . '/../partials/header.php';
?>

<div class="mb-4">
    <h1 class="h3 mb-1"><?= ucfirst($role) ?> Dashboard</h1>
    <p class="text-muted mb-0">Welcome, <?= htmlspecialchars($user['fullname'] ?? '', ENT_QUOTES, 'UTF-8') ?>. You are currently assigned as a <strong><?= ucfirst($role) ?></strong>.</p>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi <?= $role === 'trainer' ? 'bi-person-arms-up' : 'bi-wrench-adjustable' ?> display-1 text-info opacity-50 mb-3"></i>
                <h2 class="h5">You are a <?= $role === 'trainer' ? 'Fitness Trainer' : 'Maintenance Supervisor' ?></h2>
                <p class="text-muted small">Your role has been verified and approved. Contact your gym owner for work assignments.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Your Details</h2>
                <dl class="row mb-0 small">
                    <dt class="col-sm-4">Name</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($user['fullname'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-4">Role</dt>
                    <dd class="col-sm-8"><span class="badge bg-info"><?= ucfirst($role) ?></span></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
