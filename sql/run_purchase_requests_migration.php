<?php
// sql/run_purchase_requests_migration.php
// ─────────────────────────────────────────────────────────────────────────────
// Run this file once to create/update the purchase requests tables:
// 1. `purchase_requests`
// 2. `purchase_request_items`
// ─────────────────────────────────────────────────────────────────────────────
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/app/config/config.php';
$db = $config['db'];
$dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";

try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "✅ Connected to database '{$db['name']}' successfully.\n\n";
} catch (PDOException $e) {
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=webdev;charset=utf8mb4", 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "✅ Connected via local fallback.\n\n";
    } catch (PDOException $e2) {
        die("❌ Connection failed: " . $e2->getMessage() . "\n");
    }
}

function run(PDO $pdo, string $label, string $sql): void {
    try {
        $pdo->exec($sql);
        echo "✅ $label\n";
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'Duplicate column') !== false || stripos($msg, 'already exists') !== false) {
            echo "⏭  $label (already exists, skipped)\n";
        } else {
            echo "❌ $label — $msg\n";
        }
    }
}

function columnExists(PDO $pdo, string $table, string $col): bool {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
        return $stmt->rowCount() > 0;
    } catch (\Exception $e) {
        return false;
    }
}

echo "=== STEP 1: purchase_requests table ===\n";
run($pdo, 'Create purchase_requests', "
    CREATE TABLE IF NOT EXISTS `purchase_requests` (
        `id`             INT AUTO_INCREMENT PRIMARY KEY,
        `inspection_id`  INT           DEFAULT NULL,
        `equipment_id`   INT           NOT NULL,
        `gym_id`         INT           NOT NULL,
        `requested_by`   INT           NOT NULL,
        `status`         VARCHAR(50)   NOT NULL DEFAULT 'pending',
        `total_amount`   DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `admin_notes`    TEXT          DEFAULT NULL,
        `reviewed_by`    INT           DEFAULT NULL,
        `reviewed_at`    DATETIME      DEFAULT NULL,
        `created_at`     DATETIME      DEFAULT CURRENT_TIMESTAMP,
        `updated_at`     DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_pr_equipment`    (`equipment_id`),
        KEY `idx_pr_inspection`   (`inspection_id`),
        KEY `idx_pr_gym`          (`gym_id`),
        KEY `idx_pr_requested_by` (`requested_by`),
        KEY `idx_pr_status`       (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

echo "\n=== STEP 2: purchase_request_items table ===\n";
run($pdo, 'Create purchase_request_items', "
    CREATE TABLE IF NOT EXISTS `purchase_request_items` (
        `id`                  INT AUTO_INCREMENT PRIMARY KEY,
        `purchase_request_id` INT           NOT NULL,
        `item_name`           VARCHAR(255)  NOT NULL,
        `quantity`            INT           NOT NULL DEFAULT 1,
        `unit_price`          DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `notes`               TEXT          DEFAULT NULL,
        `is_purchased`        TINYINT(1)    NOT NULL DEFAULT 0,
        `status`              VARCHAR(50)   NOT NULL DEFAULT 'pending',
        `rejection_reason`    TEXT          DEFAULT NULL,
        `is_approved`         TINYINT(1)    NOT NULL DEFAULT 0,
        `created_at`          DATETIME      DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_pri_request` (`purchase_request_id`),
        KEY `idx_pri_status`  (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

// Add missing columns if table previously created
$cols = [
    'status'           => "VARCHAR(50) NOT NULL DEFAULT 'pending'",
    'rejection_reason' => "TEXT DEFAULT NULL",
    'is_approved'      => "TINYINT(1) NOT NULL DEFAULT 0",
];
foreach ($cols as $col => $def) {
    if (!columnExists($pdo, 'purchase_request_items', $col)) {
        run($pdo, "Add column $col to purchase_request_items", "ALTER TABLE `purchase_request_items` ADD COLUMN `$col` $def");
    }
}

echo "\n════════════════════════════════════════════════════════════\n";
echo "Purchase requests migration completed successfully!\n";
