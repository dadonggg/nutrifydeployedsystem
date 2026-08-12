<?php
/**
 * Migration: Add QR Token & Member ID columns to gym_members
 * Run once: http://localhost/webdev/sql/run_member_id_qr_migration.php
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/app/config/config.php';
$db = $config['db'];
$dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";

try {
    $pdo = new PDO($dsn, $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Local fallback
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=webdev;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

$results = [];

$migrations = [
    'Add qr_token column' => "ALTER TABLE gym_members ADD COLUMN qr_token VARCHAR(64) NULL UNIQUE AFTER membership_code",
    'Add issue_date column' => "ALTER TABLE gym_members ADD COLUMN issue_date DATE NULL AFTER expiration_date",
    'Add member_id_number column' => "ALTER TABLE gym_members ADD COLUMN member_id_number VARCHAR(30) NULL UNIQUE AFTER issue_date",
    'Add QR token index' => "CREATE INDEX idx_gym_members_qr_token ON gym_members (qr_token)",
    'Add member_id_number index' => "CREATE INDEX idx_gym_members_member_id_number ON gym_members (member_id_number)",
];

foreach ($migrations as $label => $sql) {
    try {
        $pdo->exec($sql);
        $results[] = ['status' => 'OK', 'label' => $label];
    } catch (PDOException $e) {
        // Column already exists or another non-fatal error
        $results[] = ['status' => 'SKIP', 'label' => $label, 'msg' => $e->getMessage()];
    }
}

// Backfill existing gym_members with a qr_token and member_id_number if missing
try {
    $stmt = $pdo->query("SELECT id FROM gym_members WHERE qr_token IS NULL OR qr_token = ''");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $backfilled = 0;
    foreach ($rows as $row) {
        $token = bin2hex(random_bytes(32));
        // Generate a padded member ID
        $memberId = 'NTF-' . date('Y') . '-' . str_pad((string)$row['id'], 5, '0', STR_PAD_LEFT);
        $upd = $pdo->prepare("UPDATE gym_members SET qr_token = :t, member_id_number = :mid, issue_date = IFNULL(start_date, created_at) WHERE id = :id");
        $upd->execute([':t' => $token, ':mid' => $memberId, ':id' => $row['id']]);
        $backfilled++;
    }
    $results[] = ['status' => 'OK', 'label' => "Backfilled $backfilled existing gym_members with QR tokens"];
} catch (PDOException $e) {
    $results[] = ['status' => 'ERROR', 'label' => 'Backfill', 'msg' => $e->getMessage()];
}
?>
<!doctype html>
<html>
<head><title>Member ID QR Migration</title>
<style>body{font-family:sans-serif;max-width:700px;margin:2rem auto;padding:0 1rem;}
.ok{color:#1B6B2A;font-weight:bold;} .skip{color:#888;} .error{color:#c0392b;font-weight:bold;}
table{width:100%;border-collapse:collapse;} td,th{padding:8px 12px;border:1px solid #ddd;text-align:left;}
th{background:#f5f5f5;}</style></head>
<body>
<h2>🗄️ Nutrify — Member ID QR Migration</h2>
<table>
<tr><th>Status</th><th>Migration</th><th>Notes</th></tr>
<?php foreach ($results as $r): ?>
<tr>
  <td class="<?= strtolower($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></td>
  <td><?= htmlspecialchars($r['label']) ?></td>
  <td><?= htmlspecialchars($r['msg'] ?? '') ?></td>
</tr>
<?php endforeach; ?>
</table>
<p>✅ Migration complete. You can now use the Digital Member ID Card feature.</p>
</body>
</html>
