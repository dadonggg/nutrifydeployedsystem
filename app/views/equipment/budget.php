<?php
declare(strict_types=1);
$pageTitle = 'Financial Dashboard';
// Backward-compat: remote server may still pass $investment or these may be null
$investment      = (float)($investment      ?? $totalRevenue ?? 0);
$monthlyProfit   = (float)($monthlyProfit   ?? 0);
$totalRevenue    = (float)($totalRevenue    ?? 0);
$totalOpex       = (float)($totalOpex       ?? 0);
require __DIR__ . '/../partials/header.php';
?>


<div class="mb-4">
    <h1 class="h3 mb-1"><i class="bi bi-wallet2 me-2"></i>Financial Dashboard</h1>
    <p class="text-muted">Track operational expenses, revenue &amp; monthly profit.</p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- ─── Stat Cards ─── -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon <?= $monthlyProfit >= 0 ? 'bg-success bg-opacity-25 text-success' : 'bg-danger bg-opacity-25 text-danger' ?>">
                    <i class="bi bi-piggy-bank"></i>
                </div>
                <div>
                    <div class="text-muted small">Monthly Profit</div>
                    <div class="fw-bold <?= $monthlyProfit < 0 ? 'text-danger' : '' ?>">₱<?= number_format($monthlyProfit, 2) ?></div>
                    <div class="text-muted" style="font-size:.7rem">Revenue − Op. Expenses</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-25 text-info"><i class="bi bi-graph-up-arrow"></i></div>
                <div>
                    <div class="text-muted small">Total Revenue</div>
                    <div class="fw-bold">₱<?= number_format($totalRevenue, 2) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-danger bg-opacity-25 text-danger"><i class="bi bi-receipt-cutoff"></i></div>
                <div>
                    <div class="text-muted small">Total Op. Expenses</div>
                    <div class="fw-bold">₱<?= number_format($totalOpex, 2) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ─── Forms: Operational Expense & Revenue ─── -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header px-3 py-2"><h2 class="h6 mb-0"><i class="bi bi-receipt me-1"></i>Add Operational Expense</h2></div>
            <div class="card-body">
                <form method="post" class="vstack gap-3">
                    <input type="hidden" name="action" value="add_operational_expense">
                    <div><label class="form-label">Name</label><input class="form-control" name="expense_name" placeholder="e.g. Electricity bill" required></div>
                    <div>
                        <label class="form-label">Category</label>
                        <input class="form-control" name="expense_category" list="expense-categories" placeholder="e.g. Utilities, Rent, Salaries">
                        <datalist id="expense-categories">
                            <option value="Utilities">
                            <option value="Rent / Lease">
                            <option value="Salaries">
                            <option value="Maintenance">
                            <option value="Marketing">
                            <option value="Supplies">
                            <option value="Insurance">
                            <option value="Other">
                        </datalist>
                    </div>
                    <div><label class="form-label">Amount (₱)</label><input class="form-control" type="number" name="expense_amount" step="0.01" min="0" required></div>
                    <div><label class="form-label">Notes</label><textarea class="form-control" name="expense_notes" rows="2" placeholder="Optional notes…"></textarea></div>
                    <button class="btn btn-primary btn-sm" type="submit">Add Expense</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header px-3 py-2"><h2 class="h6 mb-0"><i class="bi bi-graph-up me-1"></i>Record Revenue</h2></div>
            <div class="card-body">
                <form method="post" class="vstack gap-3">
                    <input type="hidden" name="action" value="add_revenue">
                    <div><label class="form-label">Description</label><input class="form-control" name="revenue_name" placeholder="e.g. Membership fees, Class fees" required></div>
                    <div><label class="form-label">Amount (₱)</label><input class="form-control" type="number" name="revenue_amount" step="0.01" min="0" required></div>
                    <div><label class="form-label">Notes</label><textarea class="form-control" name="revenue_notes" rows="2" placeholder="Optional notes…"></textarea></div>
                    <button class="btn btn-primary btn-sm" type="submit">Record Revenue</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ─── Operational Expenses History ─── -->
<?php if (!empty($operationalExpenses)): ?>
<div class="card mb-4">
    <div class="card-header px-3 py-2 d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0"><i class="bi bi-receipt-cutoff me-1"></i>Operational Expenses</h2>
        <div class="small text-muted">Total: <strong class="text-danger">₱<?= number_format($totalOpex, 2) ?></strong></div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Description</th><th>Category</th><th>Amount</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach ($operationalExpenses as $e): ?>
                <tr>
                    <td><?= htmlspecialchars($e['description']) ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($e['category'] ?? '') ?></span></td>
                    <td class="text-danger fw-semibold">₱<?= number_format((float)$e['amount'], 2) ?></td>
                    <td class="small text-muted"><?= htmlspecialchars($e['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ─── Revenue History ─── -->
<?php if (!empty($revenues)): ?>
<div class="card mb-4">
    <div class="card-header px-3 py-2 d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0"><i class="bi bi-graph-up-arrow me-1"></i>Revenue History</h2>
        <div class="small text-muted">Total: <strong class="text-success">₱<?= number_format($totalRevenue, 2) ?></strong></div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Description</th><th>Amount</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach ($revenues as $e): ?>
                <tr>
                    <td><?= htmlspecialchars($e['description']) ?></td>
                    <td class="text-success fw-semibold">₱<?= number_format((float)$e['amount'], 2) ?></td>
                    <td class="small text-muted"><?= htmlspecialchars($e['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
