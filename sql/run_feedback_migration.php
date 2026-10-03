<?php
declare(strict_types=1);

/**
 * Migration runner for Trainer Progress Analytics and Feedback Tables
 * Access via: http://localhost/webdev/sql/run_feedback_migration.php
 */

$steps = [];
$errors = [];

try {
    $configFile = __DIR__ . '/../app/config/config.php';
    if (file_exists($configFile)) {
        $config = require $configFile;
        $db = $config['db'] ?? [];
        $host = $db['host'] ?? '127.0.0.1';
        $name = $db['name'] ?? 'webdev';
        $user = $db['user'] ?? 'root';
        $pass = $db['pass'] ?? '';
        $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } else {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=webdev;charset=utf8mb4', 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    // 1. member_weight_logs
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `member_weight_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `member_id` INT NOT NULL,
            `weight_kg` DECIMAL(5,2) NOT NULL,
            `date_logged` DATE NOT NULL,
            `goal_type` VARCHAR(50) DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY `idx_mwl_user` (`user_id`),
            KEY `idx_mwl_member` (`member_id`),
            KEY `idx_mwl_date` (`date_logged`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $steps[] = '✅ Table <code>member_weight_logs</code> ready.';

    // 2. fitness_progress_tracking
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `fitness_progress_tracking` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `member_id` INT NOT NULL,
            `service_request_id` INT DEFAULT 0,
            `snapshot_date` DATE NOT NULL,
            `consistency_score` DECIMAL(6,2) DEFAULT 0.00,
            `current_streak` INT DEFAULT 0,
            `total_logged_days` INT DEFAULT 0,
            `total_workouts` INT DEFAULT 0,
            `total_nutrition_logs` INT DEFAULT 0,
            `workout_frequency_per_week` DECIMAL(4,2) DEFAULT 0.00,
            `sent_to_trainer` TINYINT(1) DEFAULT 0,
            `sent_at` DATETIME DEFAULT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_fpt_member` (`member_id`),
            KEY `idx_fpt_date` (`snapshot_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $steps[] = '✅ Table <code>fitness_progress_tracking</code> ready.';

    // 3. fitness_trainer_feedback
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `fitness_trainer_feedback` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `progress_tracking_id` INT DEFAULT 0,
            `trainer_id` INT NOT NULL,
            `member_id` INT NOT NULL,
            `service_request_id` INT DEFAULT 0,
            `feedback_status` VARCHAR(50) DEFAULT 'on_track',
            `feedback_subject` VARCHAR(255) DEFAULT 'Progress Review & Feedback',
            `feedback_text` TEXT NOT NULL,
            `areas_of_improvement` TEXT DEFAULT NULL,
            `encouragement` TEXT DEFAULT NULL,
            `next_steps` TEXT DEFAULT NULL,
            `is_read` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY `idx_ftf_trainer` (`trainer_id`),
            KEY `idx_ftf_member` (`member_id`),
            KEY `idx_ftf_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $steps[] = '✅ Table <code>fitness_trainer_feedback</code> ready.';

    // 4. notifications
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `notifications` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `message` TEXT NOT NULL,
            `type` VARCHAR(50) DEFAULT 'info',
            `link` VARCHAR(255) DEFAULT NULL,
            `is_read` TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY `idx_notif_user` (`user_id`),
            KEY `idx_notif_read` (`is_read`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $steps[] = '✅ Table <code>notifications</code> ready.';

} catch (Throwable $e) {
    $errors[] = $e->getMessage();
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Database Migration — Feedback & Analytics</title>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0fdf4; padding: 40px; }
    .card { background: #ffffff; border-radius: 12px; padding: 30px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); max-width: 600px; margin: 0 auto; }
    h2 { color: #166534; margin-top: 0; }
    p { margin: 8px 0; font-size: 15px; }
    .btn { display: inline-block; background: #166534; color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; margin-top: 15px; }
  </style>
</head>
<body>
  <div class="card">
    <?php if (empty($errors)): ?>
      <h2>🚀 Database Tables Created Successfully</h2>
      <?php foreach ($steps as $s): ?>
        <p><?= $s ?></p>
      <?php endforeach; ?>
      <a href="../index.php?r=programanalytics/index" class="btn">Go to Program Analytics</a>
    <?php else: ?>
      <h2 style="color: #dc2626;">❌ Migration Error</h2>
      <?php foreach ($errors as $err): ?>
        <p style="color: #dc2626;"><?= htmlspecialchars($err) ?></p>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</body>
</html>
