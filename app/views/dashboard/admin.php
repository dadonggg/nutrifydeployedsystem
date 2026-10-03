<?php
declare(strict_types=1);
$pageTitle = 'Admin Dashboard';
require __DIR__ . '/../partials/header.php';
$pendingLegal = array_filter($legalDocs, fn($d) => $d['status'] === 'pending');
?>

<div class="mb-4">
    <h1 class="h3 mb-1">Admin Dashboard</h1>
    <p class="text-muted mb-0">Administrative Officer — verify gym owner applications.</p>
</div>

<div class="row g-2 g-sm-3 mb-3 mb-md-4">
    <div class="col-12 col-sm-4">
        <div class="stat-card p-2.5 p-sm-3 h-100">
            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <div class="stat-icon bg-warning bg-opacity-15 text-warning"><i class="bi bi-file-earmark-check"></i></div>
                <div class="min-w-0 flex-grow-1">
                    <div class="text-muted small text-truncate">Pending Legal Docs</div>
                    <div class="fw-bold stat-value text-dark"><?= count($pendingLegal) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="stat-card p-2.5 p-sm-3 h-100">
            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <div class="stat-icon bg-success bg-opacity-15 text-success"><i class="bi bi-file-earmark-check"></i></div>
                <div class="min-w-0 flex-grow-1">
                    <div class="text-muted small text-truncate">Total Applications</div>
                    <div class="fw-bold stat-value text-dark"><?= count($legalDocs) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="stat-card p-2.5 p-sm-3 h-100">
            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <div class="stat-icon bg-info bg-opacity-15 text-info"><i class="bi bi-shield-lock"></i></div>
                <div class="min-w-0 flex-grow-1">
                    <div class="text-muted small text-truncate">Security Monitoring</div>
                    <div class="fw-bold stat-value text-success">Active</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header px-3 py-2"><h2 class="h6 mb-0"><i class="bi bi-file-earmark-check me-2"></i>Legal Document Reviews</h2></div>
            <div class="card-body">
                <p class="small text-muted">Review gym owner applications and verify their submitted legal documents. Once verified, convert the customer to a Gym Owner.</p>
                <a href="index.php?r=admin/legalreviews" class="btn btn-primary btn-sm"><i class="bi bi-arrow-right"></i> Review Applications</a>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header px-3 py-2"><h2 class="h6 mb-0"><i class="bi bi-shield-lock me-2"></i>Login Activity Monitor</h2></div>
            <div class="card-body">
                <p class="small text-muted">Monitor all user login and logout activities for security auditing. Track failed login attempts and detect suspicious behavior.</p>
                <a href="index.php?r=admin/loginactivities" class="btn btn-info btn-sm"><i class="bi bi-arrow-right"></i> View Activity Log</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-2">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-3">Your Role</h2>
                <p class="small text-muted mb-2">As an Administrative Officer, you are responsible for:</p>
                <ul class="list-unstyled mb-0 small vstack gap-2">
                    <li class="d-flex gap-2"><span style="color:#1B6B2A">•</span> Reviewing legal documents</li>
                    <li class="d-flex gap-2"><span style="color:#1B6B2A">•</span> Verifying gym owner applications</li>
                    <li class="d-flex gap-2"><span style="color:#1B6B2A">•</span> Converting customers to Gym Owners</li>
                    <li class="d-flex gap-2"><span style="color:#1B6B2A">•</span> Monitoring login activities for security</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
