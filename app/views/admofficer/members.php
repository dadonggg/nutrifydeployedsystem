<?php
declare(strict_types=1);
$pageTitle = 'Gym Members';
require __DIR__ . '/../partials/header.php';
?>

<div class="mb-4">
    <h1 class="h3 mb-1"><i class="bi bi-people-fill me-2"></i>Gym Members</h1>
    <p class="text-muted">All verified gym members and their membership codes.</p>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>ID</th><th>Member</th><th>Email</th><th>Membership Code</th><th>Active</th><th>Joined</th></tr></thead>
            <tbody>
                <?php if (empty($members)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No members yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($members as $m): ?>
                    <tr>
                        <td><?= $m['id'] ?></td>
                        <td><?= htmlspecialchars($m['fullname']) ?></td>
                        <td class="small"><?= htmlspecialchars($m['email']) ?></td>
                        <td><code style="color:#1B6B2A"><?= htmlspecialchars($m['membership_code']) ?></code></td>
                        <td><span class="badge <?= $m['is_active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $m['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                        <td class="small"><?= htmlspecialchars($m['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
