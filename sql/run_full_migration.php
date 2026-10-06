<?php
// sql/run_full_migration.php
// ─────────────────────────────────────────────────────────────────────────────
// FULL MIGRATION — Run this ONE TIME on the remote server to create / update
// all tables needed for Trainer Directory, Booking, Financial Dashboard, etc.
// Access via: https://yourdomain.com/sql/run_full_migration.php
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
    // Fallback for local
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname=webdev;charset=utf8mb4", 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "✅ Connected via local fallback.\n\n";
    } catch (PDOException $e2) {
        die("❌ Connection failed: " . $e2->getMessage() . "\n");
    }
}

$results = [];

// ─── Helper ──────────────────────────────────────────────────────────────────
function run(PDO $pdo, string $label, string $sql): void {
    global $results;
    try {
        $pdo->exec($sql);
        echo "✅ $label\n";
        $results[] = ['ok', $label];
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        // Ignore "duplicate column" errors (already migrated)
        if (stripos($msg, 'Duplicate column') !== false || stripos($msg, 'already exists') !== false) {
            echo "⏭  $label (already exists, skipped)\n";
            $results[] = ['skip', $label];
        } else {
            echo "❌ $label — $msg\n";
            $results[] = ['err', $label, $msg];
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

echo "=== STEP 1: financial_records table ===\n";
run($pdo, 'Create financial_records', "
    CREATE TABLE IF NOT EXISTS `financial_records` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `gym_owner_id` INT NOT NULL,
        `record_type` VARCHAR(50) NOT NULL DEFAULT 'budget',
        `description` VARCHAR(500) NOT NULL DEFAULT '',
        `category` VARCHAR(100) DEFAULT NULL,
        `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        `notes` TEXT DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_fr_owner` (`gym_owner_id`),
        KEY `idx_fr_type` (`record_type`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");
// Ensure new columns exist in case old table existed
foreach (['category VARCHAR(100) DEFAULT NULL', 'notes TEXT DEFAULT NULL'] as $colDef) {
    $col = explode(' ', $colDef)[0];
    if (!columnExists($pdo, 'financial_records', $col)) {
        run($pdo, "Add column $col to financial_records", "ALTER TABLE `financial_records` ADD COLUMN `$col` $colDef");
    }
}

echo "\n=== STEP 2: trainer_profiles table ===\n";
run($pdo, 'Create trainer_profiles', "
    CREATE TABLE IF NOT EXISTS `trainer_profiles` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL UNIQUE,
        `bio` TEXT NULL,
        `expertise` VARCHAR(500) NULL,
        `certifications` TEXT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_tp_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

echo "\n=== STEP 3: trainer_schedules table ===\n";
run($pdo, 'Create trainer_schedules', "
    CREATE TABLE IF NOT EXISTS `trainer_schedules` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `trainer_id` INT NOT NULL,
        `session_date` DATE NOT NULL,
        `session_time` VARCHAR(50) NOT NULL,
        `status` ENUM('available', 'booked') DEFAULT 'available',
        `max_capacity` INT DEFAULT 1,
        `current_bookings` INT DEFAULT 0,
        `request_id` INT DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `idx_trainer_date_time` (`trainer_id`, `session_date`, `session_time`),
        KEY `idx_trainer_schedules_trainer` (`trainer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

// Self-healing columns if table already existed
try {
    $pdo->query("SELECT `max_capacity` FROM `trainer_schedules` LIMIT 1");
} catch (\Exception $e) {
    run($pdo, 'Add max_capacity to trainer_schedules', "ALTER TABLE `trainer_schedules` ADD COLUMN `max_capacity` INT DEFAULT 1");
}
try {
    $pdo->query("SELECT `current_bookings` FROM `trainer_schedules` LIMIT 1");
} catch (\Exception $e) {
    run($pdo, 'Add current_bookings to trainer_schedules', "ALTER TABLE `trainer_schedules` ADD COLUMN `current_bookings` INT DEFAULT 0");
}

echo "\n=== STEP 4: trainer_reviews table ===\n";
run($pdo, 'Create trainer_reviews', "
    CREATE TABLE IF NOT EXISTS `trainer_reviews` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `trainer_id` INT NOT NULL,
        `member_id` INT NOT NULL,
        `rating` INT NOT NULL,
        `review_text` TEXT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_trainer_reviews_trainer` (`trainer_id`),
        KEY `idx_trainer_reviews_member` (`member_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

echo "\n=== STEP 5: trainer_assignments table ===\n";
run($pdo, 'Create trainer_assignments', "
    CREATE TABLE IF NOT EXISTS `trainer_assignments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `client_id` INT NOT NULL,
        `trainer_id` INT NOT NULL,
        `assigned_by` INT DEFAULT NULL,
        `assigned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `status` VARCHAR(50) DEFAULT 'active',
        UNIQUE KEY `uniq_client_trainer` (`client_id`, `trainer_id`),
        KEY `idx_ta_client` (`client_id`),
        KEY `idx_ta_trainer` (`trainer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

echo "\n=== STEP 6: fitness_service_requests — add missing columns ===\n";
$fsrCols = [
    'booking_date'       => 'DATE DEFAULT NULL',
    'booking_time'       => 'VARCHAR(50) DEFAULT NULL',
    'assigned_trainer_id'=> 'INT DEFAULT NULL',
    'assigned_by'        => 'INT DEFAULT NULL',
    'assigned_at'        => 'DATETIME DEFAULT NULL',
    'training_type'      => "VARCHAR(255) NOT NULL DEFAULT ''",
    'session_preference' => "VARCHAR(10) DEFAULT '1'",
    'training_preference'=> "VARCHAR(255) DEFAULT ''",
    'address'            => "VARCHAR(500) DEFAULT ''",
    'city'               => "VARCHAR(100) DEFAULT ''",
    'phone'              => "VARCHAR(50) DEFAULT ''",
    'full_name'          => "VARCHAR(255) DEFAULT ''",
    'email'              => "VARCHAR(255) DEFAULT ''",
];
foreach ($fsrCols as $col => $def) {
    if (!columnExists($pdo, 'fitness_service_requests', $col)) {
        run($pdo, "Add column $col to fitness_service_requests", "ALTER TABLE `fitness_service_requests` ADD COLUMN `$col` $def");
    } else {
        echo "⏭  Column $col already exists\n";
    }
}

echo "\n=== STEP 7: Sync trainer_assignments from existing accepted requests ===\n";
try {
    $inserted = 0;
    $stmt = $pdo->query(
        "SELECT fsr.member_id, fsr.assigned_trainer_id, fsr.assigned_by
         FROM fitness_service_requests fsr
         WHERE fsr.assigned_trainer_id IS NOT NULL
           AND fsr.status IN ('assigned', 'completed')
           AND fsr.member_id IS NOT NULL"
    );
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $pdo->prepare(
            "INSERT IGNORE INTO trainer_assignments (client_id, trainer_id, assigned_by, status)
             VALUES (:cid, :tid, :aby, 'active')"
        )->execute([
            ':cid' => $row['member_id'],
            ':tid' => $row['assigned_trainer_id'],
            ':aby' => $row['assigned_by'] ?? null
        ]);
        $inserted++;
    }
    echo "✅ Synced $inserted trainer_assignments from existing accepted requests\n";
} catch (PDOException $e) {
    echo "⚠️  trainer_assignments sync skipped: " . $e->getMessage() . "\n";
}

echo "\n=== STEP 8: Notifications table ===\n";
run($pdo, 'Create notifications', "
    CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT NOT NULL,
        `type` VARCHAR(50) DEFAULT 'info',
        `link` VARCHAR(500) DEFAULT NULL,
        `is_read` TINYINT(1) DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_notif_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

echo "\n=== STEP 9: legal_documents table columns ===\n";
$legalDocCols = [
    'cert_registration_status'  => "ENUM('pending','approved','flagged') DEFAULT 'pending'",
    'cert_registration_comment' => "TEXT DEFAULT NULL",
    'cert_registration_checked' => "TINYINT(1) DEFAULT 0",
    'mayors_permit_status'      => "ENUM('pending','approved','flagged') DEFAULT 'pending'",
    'mayors_permit_comment'     => "TEXT DEFAULT NULL",
    'mayors_permit_checked'     => "TINYINT(1) DEFAULT 0",
    'business_name_cert_status' => "ENUM('pending','approved','flagged') DEFAULT 'pending'",
    'business_name_cert_comment'=> "TEXT DEFAULT NULL",
    'business_name_cert_checked'=> "TINYINT(1) DEFAULT 0",
    'fire_safety_cert_status'   => "ENUM('pending','approved','flagged') DEFAULT 'pending'",
    'fire_safety_cert_comment'  => "TEXT DEFAULT NULL",
    'fire_safety_cert_checked'  => "TINYINT(1) DEFAULT 0",
    'street_address'            => "VARCHAR(255) DEFAULT NULL",
    'province'                  => "VARCHAR(100) DEFAULT NULL",
    'city_municipality'         => "VARCHAR(100) DEFAULT NULL",
    'barangay'                  => "VARCHAR(100) DEFAULT NULL",
    'other_staff_needed'        => "TEXT DEFAULT NULL",
    'maintenance_count'         => "INT DEFAULT 0",
    'trainer_count'             => "INT DEFAULT 0",
    'gym_description'           => "TEXT DEFAULT NULL",
    'opening_hours'             => "JSON DEFAULT NULL",
];

foreach ($legalDocCols as $col => $def) {
    if (!columnExists($pdo, 'legal_documents', $col)) {
        run($pdo, "Add column $col to legal_documents", "ALTER TABLE `legal_documents` ADD COLUMN `$col` $def");
    } else {
        echo "⏭  Column $col already exists in legal_documents\n";
    }
}

echo "\n=== STEP 10: gym_members table columns ===\n";
if (!columnExists($pdo, 'gym_members', 'gym_owner_id')) {
    run($pdo, "Add column gym_owner_id to gym_members", "ALTER TABLE `gym_members` ADD COLUMN `gym_owner_id` INT DEFAULT NULL AFTER `user_id`");
} else {
    echo "⏭  Column gym_owner_id already exists in gym_members\n";
}

echo "\n\n════════════════════════════════════════════════════════════\n";
$ok   = count(array_filter($results, fn($r) => $r[0] === 'ok'));
$skip = count(array_filter($results, fn($r) => $r[0] === 'skip'));
$err  = count(array_filter($results, fn($r) => $r[0] === 'err'));
echo "✅ $ok done  |  ⏭  $skip skipped  |  ❌ $err errors\n";
echo "Migration completed. You can delete this file now for security.\n";
