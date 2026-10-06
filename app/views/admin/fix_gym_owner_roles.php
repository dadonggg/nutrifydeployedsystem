<?php
$pageTitle = "Fix Gym Owner Roles - Admin Diagnostic";
require __DIR__ . "/../partials/header.php";

if (!isset($_SESSION["user_id"])) { header("Location: index.php?r=auth/login"); exit; }
$pdo = \App\Core\Database::pdo();
$adminCheck = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$adminCheck->execute([$_SESSION["user_id"]]);
$adminRow = $adminCheck->fetch(\PDO::FETCH_ASSOC);
if (!$adminRow || $adminRow["role"] !== "admin") { header("Location: index.php?r=home/index"); exit; }

$results = []; $fixed = 0; $failed = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "fix_all") {
    $stmt = $pdo->query("SELECT ld.id as doc_id, ld.user_id, ld.gym_name, u.fullname, u.email, u.role FROM legal_documents ld JOIN users u ON u.id = ld.user_id WHERE ld.status = 'verified' AND u.role NOT IN ('gym_owner','admin') ORDER BY ld.created_at DESC");
    foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
        try {
            $upd = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $upd->execute(["gym_owner", $row["user_id"]]);
            $verify = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $verify->execute([$row["user_id"]]);
            $newRole = $verify->fetchColumn();
            if ($newRole === "gym_owner") { $results[] = ["user" => $row["fullname"], "email" => $row["email"], "gym" => $row["gym_name"], "status" => "FIXED", "cls" => "success"]; $fixed++; }
            else { $results[] = ["user" => $row["fullname"], "email" => $row["email"], "gym" => $row["gym_name"], "status" => "FAILED", "cls" => "danger"]; $failed++; }
        } catch (\Exception $e) { $results[] = ["user" => $row["fullname"], "email" => $row["email"], "gym" => $row["gym_name"], "status" => "ERROR: ".$e->getMessage(), "cls" => "danger"]; $failed++; }
    }
}

$statusStmt = $pdo->query("SELECT ld.id, ld.user_id, ld.gym_name, ld.status as doc_status, u.fullname, u.email, u.role FROM legal_documents ld JOIN users u ON u.id = ld.user_id WHERE ld.status = 'verified' ORDER BY ld.created_at DESC");
$verifiedDocs = $statusStmt->fetchAll(\PDO::FETCH_ASSOC);
$needsFix = array_filter($verifiedDocs, fn($r) => !in_array($r["role"], ["gym_owner","admin"]));
?>
<div class="mb-4">
  <a href="index.php?r=admin/legalreviews" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left"></i> Back</a>
  <h1 class="h3">Fix Gym Owner Roles</h1>
  <p class="text-muted">Finds verified gym applicants whose role was not updated to <code>gym_owner</code> due to a previous bug.</p>
</div>
<?php if (!empty($results)): ?>
<div class="alert alert-info">Fixed: <?= $fixed ?> | Failed: <?= $failed ?></div>
<table class="table table-sm table-bordered mb-4"><thead><tr><th>User</th><th>Email</th><th>Gym</th><th>Result</th></tr></thead><tbody>
<?php foreach ($results as $r): ?><tr class="table-<?= $r["cls"] ?>"><td><?= htmlspecialchars($r["user"]) ?></td><td><?= htmlspecialchars($r["email"]) ?></td><td><?= htmlspecialchars($r["gym"]) ?></td><td><strong><?= htmlspecialchars($r["status"]) ?></strong></td></tr><?php endforeach; ?>
</tbody></table><?php endif; ?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h2 class="h5 mb-0">Verified Gym Applicants (<?= count($verifiedDocs) ?>)</h2>
    <?php if (!empty($needsFix)): ?>
    <form method="POST"><input type="hidden" name="action" value="fix_all">
    <button class="btn btn-warning btn-sm" onclick="return confirm('Fix <?= count($needsFix) ?> users now?')"><i class="bi bi-wrench me-1"></i>Fix All (<?= count($needsFix) ?>)</button>
    </form><?php endif; ?>
  </div>
  <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>User</th><th>Email</th><th>Gym</th><th>Doc Status</th><th>Current Role</th><th>Fix Needed</th></tr></thead><tbody>
  <?php foreach ($verifiedDocs as $row): $nf = !in_array($row["role"], ["gym_owner","admin"]); ?>
  <tr class="<?= $nf ? "table-warning" : "" ?>">
    <td><?= htmlspecialchars($row["fullname"]) ?></td><td><?= htmlspecialchars($row["email"]) ?></td>
    <td><?= htmlspecialchars($row["gym_name"] ?? "N/A") ?></td>
    <td><span class="badge bg-success"><?= htmlspecialchars($row["doc_status"]) ?></span></td>
    <td><code><?= htmlspecialchars($row["role"]) ?></code></td>
    <td><?= $nf ? "<span class=\"badge bg-danger\">YES</span>" : "<span class=\"badge bg-success\">No</span>" ?></td>
  </tr><?php endforeach; ?>
  </tbody></table></div>
</div>
<?php require __DIR__ . "/../partials/footer.php"; ?>
