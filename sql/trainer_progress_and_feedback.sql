-- ============================================================================
-- Fitness Trainer Progress Analytics & Email-Style Feedback Database Schema
-- ============================================================================

-- 1. Client Weight Logs Table (Tracks daily/weekly weight check-ins)
CREATE TABLE IF NOT EXISTS `member_weight_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `member_id` INT NOT NULL,
    `weight_kg` DECIMAL(5,2) NOT NULL,
    `date_logged` DATE NOT NULL,
    `goal_type` VARCHAR(50) DEFAULT NULL COMMENT 'weight_loss | weight_gain | maintaining | cutting | bulking',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_mwl_user` (`user_id`),
    KEY `idx_mwl_member` (`member_id`),
    KEY `idx_mwl_date` (`date_logged`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Client Fitness Progress Tracking Snapshots
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

-- 3. Trainer Email-Style Progress Feedback Table
CREATE TABLE IF NOT EXISTS `fitness_trainer_feedback` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `progress_tracking_id` INT DEFAULT 0,
    `trainer_id` INT NOT NULL,
    `member_id` INT NOT NULL,
    `service_request_id` INT DEFAULT 0,
    `feedback_status` VARCHAR(50) DEFAULT 'on_track' COMMENT 'on_track | plateau | needs_adjustment | goal_achieved',
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

-- 4. In-App Notifications Table (for instant delivery to client)
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
