<?php
declare(strict_types=1);
$pageTitle = 'Employee List';
require __DIR__ . '/../partials/header.php';
?>

<div class="mb-4">
    <h1 class="h3 mb-1"><i class="bi bi-person-badge me-2"></i>Employee List</h1>
    <p class="text-muted">All hired staff — fitness trainers and maintenance officers. Check availability for membership assignments.</p>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Position</th><th>Available</th><th>Hired</th></tr></thead>
            <tbody>
                <?php if (empty($employees)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No employees yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($employees as $e): ?>
                    <tr>
                        <td><?= $e['id'] ?></td>
                        <td><?= htmlspecialchars($e['fullname']) ?></td>
                        <td class="small"><?= htmlspecialchars($e['email']) ?></td>
                        <td><span class="badge bg-info"><?= ucfirst($e['position']) ?></span></td>
                        <td><span class="badge <?= $e['is_available'] ? 'bg-success' : 'bg-secondary' ?>"><?= $e['is_available'] ? 'Available' : 'Assigned' ?></span></td>
                        <td class="small"><?= htmlspecialchars($e['hired_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
