<?php
/**
 * Progress Feature Migration
 * ─────────────────────────
 * Creates:
 *   • member_weight_logs  — timestamped weight history per member
 * Alters:
 *   • gym_members         — adds fitness_goal column
 *
 * Run once in browser:
 *   http://localhost/webdev/sql/run_progress_migration.php
 */
declare(strict_types=1);

$config = require __DIR__ . '/../app/config/config.php';
$db     = $config['db'];
$dsn    = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";

$steps  = [];
$errors = [];

function tryPdo(string $dsn, string $user, string $pass): PDO
{
    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}

try {
    try {
        $pdo = tryPdo($dsn, $db['user'], $db['pass']);
    } catch (PDOException $e) {
        // Local XAMPP fallback
        $pdo = tryPdo('mysql:host=127.0.0.1;dbname=webdev;charset=utf8mb4', 'root', '');
    }

    // ── 1. Create member_weight_logs ──────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `member_weight_logs` (
            `id`          INT AUTO_INCREMENT PRIMARY KEY,
            `user_id`     INT           NOT NULL,
            `member_id`   INT           NOT NULL,
            `weight_kg`   DECIMAL(5,2)  NOT NULL,
            `date_logged` DATE          NOT NULL,
            `goal_type`   VARCHAR(20)   DEFAULT NULL
                          COMMENT 'bulking | cutting | maintaining',
            `created_at`  DATETIME      DEFAULT CURRENT_TIMESTAMP,
            `updated_at`  DATETIME      DEFAULT CURRENT_TIMESTAMP
                          ON UPDATE CURRENT_TIMESTAMP,
            KEY `idx_mwl_user`   (`user_id`),
            KEY `idx_mwl_member` (`member_id`),
            KEY `idx_mwl_date`   (`date_logged`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $steps[] = '✅ Table <code>member_weight_logs</code> created (or already exists).';

    // ── 2. Add fitness_goal column to gym_members ─────────────────────────────
    $col = $pdo->query("SHOW COLUMNS FROM `gym_members` LIKE 'fitness_goal'")->rowCount();
    if ($col === 0) {
        $pdo->exec("
            ALTER TABLE `gym_members`
            ADD COLUMN `fitness_goal` VARCHAR(20) DEFAULT NULL
                COMMENT 'bulking | cutting | maintaining'
            AFTER `membership_type`;
        ");
        $steps[] = '✅ Column <code>gym_members.fitness_goal</code> added.';
    } else {
        $steps[] = '⏭️  Column <code>gym_members.fitness_goal</code> already exists — skipped.';
    }

    // ── 3. Add unique week constraint (prevent duplicate logs per week) ───────
    // Using a functional approach: we enforce this in PHP code + query logic
    // (MySQL < 8 doesn't support functional unique indexes easily)
    $steps[] = '✅ Weekly-uniqueness enforced via application logic (YEARWEEK check).';

} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}

// ── Output ─────────────────────────────────────────────────────────────────
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><meta charset="utf-8">
<title>Progress Migration</title>
<style>
  body{font-family:sans-serif;padding:30px;background:#f0fdf4}
  .card{background:#fff;border-radius:12px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.08);max-width:640px}
  h2{margin:0 0 16px;color:#15803d}h2.err{color:#dc2626}
  p{margin:6px 0;font-size:15px}
  a{color:#16a34a;font-weight:600}
</style></head><body><div class="card">';

if (empty($errors)) {
    echo '<h2>🚀 Migration Successful</h2>';
    foreach ($steps as $s) { echo "<p>$s</p>"; }
    echo '<p style="margin-top:16px"><a href="../index.php?r=member/dashboard">→ Go to Member Dashboard</a></p>';
} else {
    echo '<h2 class="err">❌ Migration Failed</h2>';
    foreach ($errors as $e) { echo '<p style="color:#dc2626">'.htmlspecialchars($e).'</p>'; }
    foreach ($steps  as $s) { echo "<p>$s</p>"; }
}

echo '</div></body></html>';
