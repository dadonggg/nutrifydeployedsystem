<?php
declare(strict_types=1);
$pageTitle = 'Gym Owner Dashboard';
require __DIR__ . '/../partials/header.php';
?>

<!-- Dashboard Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3 mb-md-4">
    <div>
        <h1 class="h4 h3-md fw-bold mb-1 text-dark">Gym Owner Dashboard</h1>
        <p class="text-muted small mb-0">Manage your gym operations — budget, equipment, staff, members & revenue.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="index.php?r=gymowner/managegym" class="btn btn-outline-success btn-sm"><i class="bi bi-building-gear me-1"></i> Gym Profile</a>
    </div>
</div>

<!-- Financial Summary Grid (2x2 on mobile, 4 in a row on desktop) -->
<div class="row g-2 g-sm-3 mb-3 mb-md-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card p-2.5 p-sm-3 h-100">
            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-wallet2"></i></div>
                <div class="min-w-0 flex-grow-1">
                    <div class="text-muted small text-truncate">Total Budget</div>
                    <div class="stat-value text-dark text-truncate" title="₱<?= number_format($budget, 2) ?>">₱<?= number_format($budget, 2) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card p-2.5 p-sm-3 h-100">
            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-cash-stack"></i></div>
                <div class="min-w-0 flex-grow-1">
                    <div class="text-muted small text-truncate">Expenses</div>
                    <div class="stat-value text-dark text-truncate" title="₱<?= number_format($totalExpenses, 2) ?>">₱<?= number_format($totalExpenses, 2) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card p-2.5 p-sm-3 h-100">
            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-graph-up-arrow"></i></div>
                <div class="min-w-0 flex-grow-1">
                    <div class="text-muted small text-truncate">Total Revenue</div>
                    <div class="stat-value text-success text-truncate" title="₱<?= number_format($totalRevenue ?? 0, 2) ?>">₱<?= number_format($totalRevenue ?? 0, 2) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card p-2.5 p-sm-3 h-100">
            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-piggy-bank"></i></div>
                <div class="min-w-0 flex-grow-1">
                    <div class="text-muted small text-truncate">Net Profit</div>
                    <div class="stat-value text-truncate <?= ($monthlyProfit ?? 0) < 0 ? 'text-danger' : 'text-success' ?>" title="₱<?= number_format($monthlyProfit ?? 0, 2) ?>">₱<?= number_format($monthlyProfit ?? 0, 2) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Members & Revenue Row -->
<div class="row g-2 g-sm-3 mb-3 mb-md-4">
    <div class="col-6 col-md-4">
        <div class="stat-card p-2.5 p-sm-3 h-100">
            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-people-fill"></i></div>
                <div class="min-w-0 flex-grow-1">
                    <div class="text-muted small text-truncate">Active Members</div>
                    <div class="stat-value text-dark"><?= count($activeMembers ?? []) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card p-2.5 p-sm-3 h-100">
            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <div class="stat-icon bg-warning bg-opacity-15 text-warning"><i class="bi bi-person-plus"></i></div>
                <div class="min-w-0 flex-grow-1">
                    <div class="text-muted small text-truncate">Pending Apps</div>
                    <div class="stat-value text-dark"><?= count($pendingMemberApps ?? []) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="stat-card p-2.5 p-sm-3 h-100">
            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-calendar-month"></i></div>
                <div class="min-w-0 flex-grow-1">
                    <div class="text-muted small text-truncate">This Month Revenue</div>
                    <div class="stat-value text-success text-truncate">₱<?= number_format($monthlyMemberRevenue ?? 0, 2) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Revenue Breakdown -->
<?php if (!empty($revenueByMonth)): ?>
<div class="card mb-3 mb-md-4">
    <div class="card-header px-3 py-2.5"><h2 class="h6 mb-0 fw-bold"><i class="bi bi-bar-chart me-2 text-success"></i>Monthly Revenue Breakdown</h2></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Month</th><th>New Members</th><th>Revenue</th></tr></thead>
                <tbody>
                    <?php foreach ($revenueByMonth as $r): ?>
                    <tr>
                        <td class="fw-medium"><?= htmlspecialchars($r['month']) ?></td>
                        <td><?= (int)$r['member_count'] ?></td>
                        <td class="fw-bold text-success">₱<?= number_format((float)$r['total'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Revenue Tracking (Category Breakdown) -->
<?php if (!empty($revenueBreakdown)): ?>
<div class="card mb-3 mb-md-4">
    <div class="card-header px-3 py-2.5"><h2 class="h6 mb-0 fw-bold"><i class="bi bi-graph-up me-2 text-primary"></i>Revenue Tracking</h2></div>
    <div class="card-body p-3">
        <div class="row g-2 g-sm-3">
            <div class="col-12 col-sm-4">
                <div class="p-3 border rounded-3 bg-light bg-opacity-50">
                    <div class="text-muted small mb-1">Membership Revenue</div>
                    <div class="h5 mb-0 text-success fw-bold">₱<?= number_format($revenueBreakdown['Membership Revenue'] ?? 0, 2) ?></div>
                </div>
            </div>
            <div class="col-12 col-sm-4">
                <div class="p-3 border rounded-3 bg-light bg-opacity-50">
                    <div class="text-muted small mb-1">Trainer Sessions</div>
                    <div class="h5 mb-0 text-primary fw-bold">₱<?= number_format($revenueBreakdown['Trainer Sessions'] ?? 0, 2) ?></div>
                </div>
            </div>
            <div class="col-12 col-sm-4">
                <div class="p-3 border rounded-3 bg-light bg-opacity-50">
                    <div class="text-muted small mb-1">Others</div>
                    <div class="h5 mb-0 text-info fw-bold">₱<?= number_format($revenueBreakdown['Others'] ?? 0, 2) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="row g-3 mb-3 mb-md-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-header px-3 py-2.5"><h2 class="h6 mb-0 fw-bold"><i class="bi bi-wallet2 me-2 text-success"></i>Budget & Expenses</h2></div>
            <div class="card-body d-flex flex-column justify-content-between p-3">
                <p class="small text-muted mb-3">Set your total budget and track operational expenses.</p>
                <a href="index.php?r=equipment/budget" class="btn btn-primary w-100"><i class="bi bi-arrow-right me-1"></i> Manage</a>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-header px-3 py-2.5"><h2 class="h6 mb-0 fw-bold"><i class="bi bi-cart3 me-2 text-warning"></i>Equipment Shop</h2></div>
            <div class="card-body d-flex flex-column justify-content-between p-3">
                <p class="small text-muted mb-3">Browse and purchase gym equipment from suppliers.</p>
                <a href="index.php?r=equipment/shop" class="btn btn-warning w-100 text-dark fw-bold"><i class="bi bi-arrow-right me-1"></i> Shop</a>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-header px-3 py-2.5"><h2 class="h6 mb-0 fw-bold"><i class="bi bi-people me-2 text-info"></i>Staff Applications</h2></div>
            <div class="card-body d-flex flex-column justify-content-between p-3">
                <div>
                    <p class="small text-muted mb-2">Review applications from fitness enthusiasts wanting to join as staff.</p>
                    <span class="badge bg-info mb-3"><?= count($staffApps) ?> pending</span>
                </div>
                <a href="index.php?r=staff/applications" class="btn btn-success w-100"><i class="bi bi-arrow-right me-1"></i> Review</a>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card h-100">
            <div class="card-header px-3 py-2.5"><h2 class="h6 mb-0 fw-bold"><i class="bi bi-person-plus me-2 text-primary"></i>Memberships</h2></div>
            <div class="card-body d-flex flex-column justify-content-between p-3">
                <p class="small text-muted mb-3">Review membership applications, view approved members, and track attendance.</p>
                <div class="d-flex flex-column gap-2">
                    <a href="index.php?r=gymowner/memberships" class="btn btn-outline-primary"><i class="bi bi-arrow-right me-1"></i> Applications</a>
                    <a href="index.php?r=gymowner/members" class="btn btn-outline-success"><i class="bi bi-arrow-right me-1"></i> Members</a>
                    <a href="index.php?r=gymowner/attendance" class="btn btn-outline-info"><i class="bi bi-arrow-right me-1"></i> Attendance</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Plans & Services Quick Links -->
<div class="row g-3 mb-3 mb-md-4">
    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-header px-3 py-2.5"><h2 class="h6 mb-0 fw-bold"><i class="bi bi-calendar-check me-2 text-success"></i>Membership Plans</h2></div>
            <div class="card-body d-flex flex-column justify-content-between p-3">
                <p class="small text-muted mb-3">Set up duration-based membership plans (Monthly, Quarterly, etc.) with pricing.</p>
                <a href="index.php?r=gymowner/plans" class="btn btn-success w-100"><i class="bi bi-arrow-right me-1"></i> Manage Plans</a>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-header px-3 py-2.5"><h2 class="h6 mb-0 fw-bold"><i class="bi bi-person-badge me-2 text-warning"></i>Training Pricing</h2></div>
            <div class="card-body d-flex flex-column justify-content-between p-3">
                <p class="small text-muted mb-3">Add session-based training packages (Personal Training, Pilates, Yoga) with pricing.</p>
                <a href="index.php?r=gymowner/trainerpricing" class="btn btn-warning w-100 text-dark fw-bold"><i class="bi bi-arrow-right me-1"></i> Manage Training</a>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card h-100">
            <div class="card-header px-3 py-2.5"><h2 class="h6 mb-0 fw-bold"><i class="bi bi-tags me-2 text-info"></i>Gym Services</h2></div>
            <div class="card-body d-flex flex-column justify-content-between p-3">
                <p class="small text-muted mb-3">Add additional gym services with separate member/non-member pricing.</p>
                <a href="index.php?r=gymowner/services" class="btn btn-info w-100 text-white"><i class="bi bi-arrow-right me-1"></i> Manage Services</a>
            </div>
        </div>
    </div>
</div>

<!-- PayMongo Setup -->
<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card border-primary">
            <div class="card-header px-3 py-2.5 bg-primary text-white">
                <h2 class="h6 mb-0"><i class="bi bi-credit-card-2-front me-2"></i>PayMongo Setup</h2>
            </div>
            <div class="card-body p-3">
                <div class="row align-items-center g-3">
                    <div class="col-12 col-md-8">
                        <p class="small text-muted mb-1">Configure PayMongo API keys to accept online payments from members.</p>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i> Required for online payments during membership applications
                        </small>
                    </div>
                    <div class="col-12 col-md-4 text-md-end">
                        <a href="index.php?r=gymowner/paymongo" class="btn btn-primary w-100 w-md-auto">
                            <i class="bi bi-gear me-1"></i> Configure PayMongo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
