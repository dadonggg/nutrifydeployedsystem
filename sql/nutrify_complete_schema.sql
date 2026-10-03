-- ═══════════════════════════════════════════════════════════════════════════
-- NUTRIFY GYM MANAGEMENT SYSTEM — COMPLETE DATABASE SCHEMA
-- Database: if0_42266462_nutrify
-- Host:     sql104.infinityfree.com
--
-- HOW TO USE:
--   1. Open phpMyAdmin → select database "if0_42266462_nutrify"
--   2. Click "SQL" tab → paste this entire file → click "Go"
--   OR: Run sql/run_full_migration.php via browser (safer, adds missing columns)
-- ═══════════════════════════════════════════════════════════════════════════

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 1: users
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
    `id`                  INT AUTO_INCREMENT PRIMARY KEY,
    `fullname`            VARCHAR(255) NOT NULL,
    `email`               VARCHAR(255) NOT NULL UNIQUE,
    `password`            VARCHAR(255) NOT NULL,
    `role`                VARCHAR(50)  DEFAULT 'member',
    `is_verified`         TINYINT(1)   DEFAULT 0,
    `profile_picture_url` VARCHAR(500) DEFAULT NULL,
    `created_at`          DATETIME     DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_users_email` (`email`),
    KEY `idx_users_role`  (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 2: email_verifications
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `email_verifications` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT          NOT NULL,
    `token`      VARCHAR(255) NOT NULL UNIQUE,
    `created_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_ev_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 3: otp_codes
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `otp_codes` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT         NOT NULL,
    `otp_code`   VARCHAR(6)  NOT NULL,
    `expires_at` DATETIME    NOT NULL,
    `created_at` DATETIME    DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_otp_user`    (`user_id`),
    KEY `idx_otp_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 4: legal_documents (Gym Owner Registration Profiles)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `legal_documents` (
    `id`                    INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`               INT          NOT NULL UNIQUE,
    `gym_name`              VARCHAR(255) DEFAULT NULL,
    `gym_address`           TEXT         DEFAULT NULL,
    `business_permit`       VARCHAR(500) DEFAULT NULL,
    `sanitary_permit`       VARCHAR(500) DEFAULT NULL,
    `fire_safety_cert`      VARCHAR(500) DEFAULT NULL,
    `dti_sec_registration`  VARCHAR(500) DEFAULT NULL,
    `bir_registration`      VARCHAR(500) DEFAULT NULL,
    `lease_contract`        VARCHAR(500) DEFAULT NULL,
    `gym_photo`             VARCHAR(500) DEFAULT NULL,
    `status`                VARCHAR(50)  DEFAULT 'pending',
    `rejection_reason`      TEXT         DEFAULT NULL,
    `submitted_at`          DATETIME     DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at`           DATETIME     DEFAULT NULL,
    KEY `idx_ld_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 5: employees (Trainers, Officers, Staff)
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `employees` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT          NOT NULL,
    `hired_by`      INT          DEFAULT NULL,
    `fullname`      VARCHAR(255) DEFAULT NULL,
    `position`      VARCHAR(100) DEFAULT 'trainer',
    `status`        VARCHAR(50)  DEFAULT 'active',
    `is_available`  TINYINT(1)   DEFAULT 1,
    `joined_at`     DATETIME     DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_emp_user`     (`user_id`),
    KEY `idx_emp_hired_by` (`hired_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 6: gym_members
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `gym_members` (
    `id`                  INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`             INT          NOT NULL UNIQUE,
    `gym_owner_id`        INT          DEFAULT NULL,
    `membership_code`     VARCHAR(50)  DEFAULT NULL,
    `membership_status`   VARCHAR(50)  DEFAULT 'pending',
    `membership_type`     VARCHAR(100) DEFAULT NULL,
    `assigned_trainer_id` INT          DEFAULT NULL,
    `phone`               VARCHAR(50)  DEFAULT NULL,
    `address`             TEXT         DEFAULT NULL,
    `joined_at`           DATETIME     DEFAULT CURRENT_TIMESTAMP,
    `expires_at`          DATETIME     DEFAULT NULL,
    KEY `idx_gm_user`      (`user_id`),
    KEY `idx_gm_owner`     (`gym_owner_id`),
    KEY `idx_gm_trainer`   (`assigned_trainer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 7: membership_plans
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `membership_plans` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `gym_owner_id`  INT             NOT NULL,
    `name`          VARCHAR(255)    NOT NULL,
    `description`   TEXT            DEFAULT NULL,
    `price`         DECIMAL(10,2)   DEFAULT 0.00,
    `duration_days` INT             DEFAULT 30,
    `is_active`     TINYINT(1)      DEFAULT 1,
    `created_at`    DATETIME        DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_mp_owner` (`gym_owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 8: membership_applications
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `membership_applications` (
    `id`                  INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`             INT          NOT NULL,
    `gym_owner_id`        INT          DEFAULT NULL,
    `plan_id`             INT          DEFAULT NULL,
    `training_package_id` INT          DEFAULT NULL,
    `full_name`           VARCHAR(255) DEFAULT NULL,
    `email`               VARCHAR(255) DEFAULT NULL,
    `phone`               VARCHAR(50)  DEFAULT NULL,
    `address`             TEXT         DEFAULT NULL,
    `status`              VARCHAR(50)  DEFAULT 'pending',
    `payment_status`      VARCHAR(50)  DEFAULT 'unpaid',
    `payment_method`      VARCHAR(100) DEFAULT NULL,
    `payment_proof`       VARCHAR(500) DEFAULT NULL,
    `rejection_reason`    TEXT         DEFAULT NULL,
    `created_at`          DATETIME     DEFAULT CURRENT_TIMESTAMP,
    `approved_at`         DATETIME     DEFAULT NULL,
    KEY `idx_ma_user`  (`user_id`),
    KEY `idx_ma_owner` (`gym_owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 9: fitness_trainer_packages
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `fitness_trainer_packages` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `gym_owner_id`  INT             NOT NULL,
    `package_name`  VARCHAR(255)    NOT NULL,
    `session_count` INT             DEFAULT 1,
    `price`         DECIMAL(10,2)   DEFAULT 0.00,
    `description`   TEXT            DEFAULT NULL,
    `is_active`     TINYINT(1)      DEFAULT 1,
    `created_at`    DATETIME        DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_ftp_owner` (`gym_owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 10: fitness_service_requests
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `fitness_service_requests` (
    `id`                      INT AUTO_INCREMENT PRIMARY KEY,
    `member_id`               INT          NOT NULL,
    `full_name`               VARCHAR(255) DEFAULT '',
    `email`                   VARCHAR(255) DEFAULT '',
    `phone`                   VARCHAR(50)  DEFAULT '',
    `address`                 VARCHAR(500) DEFAULT '',
    `city`                    VARCHAR(100) DEFAULT '',
    `training_type`           VARCHAR(255) DEFAULT '',
    `training_type_custom`    VARCHAR(255) DEFAULT NULL,
    `session_preference`      VARCHAR(10)  DEFAULT '1',
    `training_preference`     VARCHAR(255) DEFAULT '',
    `schedule_preference_json`TEXT         DEFAULT NULL,
    `specific_trainer_request`VARCHAR(255) DEFAULT NULL,
    `status`                  VARCHAR(50)  DEFAULT 'pending',
    `booking_date`            DATE         DEFAULT NULL,
    `booking_time`            VARCHAR(50)  DEFAULT NULL,
    `assigned_trainer_id`     INT          DEFAULT NULL,
    `assigned_by`             INT          DEFAULT NULL,
    `assigned_at`             DATETIME     DEFAULT NULL,
    `created_at`              DATETIME     DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_fsr_member`  (`member_id`),
    KEY `idx_fsr_trainer` (`assigned_trainer_id`),
    KEY `idx_fsr_status`  (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 11: fitness_client_profiles
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `fitness_client_profiles` (
    `id`                  INT AUTO_INCREMENT PRIMARY KEY,
    `service_request_id`  INT          NOT NULL UNIQUE,
    `member_id`           INT          NOT NULL,
    `age`                 INT          DEFAULT NULL,
    `gender`              VARCHAR(20)  DEFAULT NULL,
    `height_cm`           DECIMAL(5,2) DEFAULT NULL,
    `weight_kg`           DECIMAL(5,2) DEFAULT NULL,
    `fitness_goals`       TEXT         DEFAULT NULL,
    `medical_conditions`  TEXT         DEFAULT NULL,
    `activity_level`      VARCHAR(100) DEFAULT NULL,
    `dietary_preferences` TEXT         DEFAULT NULL,
    `created_at`          DATETIME     DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_fcp_request` (`service_request_id`),
    KEY `idx_fcp_member`  (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 12: fitness_trainer_plans
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `fitness_trainer_plans` (
    `id`                  INT AUTO_INCREMENT PRIMARY KEY,
    `service_request_id`  INT     NOT NULL,
    `trainer_id`          INT     DEFAULT NULL,
    `plan_data`           LONGTEXT DEFAULT NULL,
    `meal_plan_data`      LONGTEXT DEFAULT NULL,
    `status`              VARCHAR(50)  DEFAULT 'draft',
    `created_at`          DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_ftp_request` (`service_request_id`),
    KEY `idx_ftp_trainer` (`trainer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 13: trainer_profiles
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `trainer_profiles` (
    `id`              INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`         INT          NOT NULL UNIQUE,
    `bio`             TEXT         DEFAULT NULL,
    `expertise`       VARCHAR(500) DEFAULT NULL,
    `certifications`  TEXT         DEFAULT NULL,
    `created_at`      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_tp_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 14: trainer_schedules
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `trainer_schedules` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `trainer_id`       INT         NOT NULL,
    `session_date`     DATE        NOT NULL,
    `session_time`     VARCHAR(50) NOT NULL,
    `status`           ENUM('available','booked') DEFAULT 'available',
    `max_capacity`     INT         DEFAULT 1,
    `current_bookings` INT         DEFAULT 0,
    `request_id`       INT         DEFAULT NULL,
    `created_at`       DATETIME    DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_trainer_slot` (`trainer_id`,`session_date`,`session_time`),
    KEY `idx_ts_trainer` (`trainer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 15: trainer_assignments
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `trainer_assignments` (
    `id`          INT AUTO_INCREMENT PRIMARY KEY,
    `client_id`   INT         NOT NULL,
    `trainer_id`  INT         NOT NULL,
    `assigned_by` INT         DEFAULT NULL,
    `assigned_at` DATETIME    DEFAULT CURRENT_TIMESTAMP,
    `status`      VARCHAR(50) DEFAULT 'active',
    UNIQUE KEY `uniq_client_trainer` (`client_id`,`trainer_id`),
    KEY `idx_ta_client`  (`client_id`),
    KEY `idx_ta_trainer` (`trainer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 16: financial_records
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `financial_records` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `gym_owner_id` INT           NOT NULL,
    `record_type`  VARCHAR(50)   NOT NULL DEFAULT 'budget',
    `description`  VARCHAR(500)  NOT NULL DEFAULT '',
    `category`     VARCHAR(100)  DEFAULT NULL,
    `amount`       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `notes`        TEXT          DEFAULT NULL,
    `created_at`   DATETIME      DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_fr_owner` (`gym_owner_id`),
    KEY `idx_fr_type`  (`record_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 17: notifications
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT          NOT NULL,
    `title`      VARCHAR(255) NOT NULL,
    `message`    TEXT         NOT NULL,
    `type`       VARCHAR(50)  DEFAULT 'info',
    `link`       VARCHAR(500) DEFAULT NULL,
    `is_read`    TINYINT(1)   DEFAULT 0,
    `created_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_notif_user`    (`user_id`),
    KEY `idx_notif_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 18: messages
-- ─────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `messages` (
    `id`           INT AUTO_INCREMENT PRIMARY KEY,
    `sender_id`    INT      NOT NULL,
    `receiver_id`  INT      NOT NULL,
    `request_id`   INT      DEFAULT NULL,
    `message_text` TEXT     NOT NULL,
    `read_at`      DATETIME DEFAULT NULL,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_msg_sender`   (`sender_id`),
    KEY `idx_msg_receiver` (`receiver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 19: purchase_requests
-- ─────────────────────────────────────────────────────────────────────────────
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

-- ─────────────────────────────────────────────────────────────────────────────
-- TABLE 20: purchase_request_items
-- ─────────────────────────────────────────────────────────────────────────────
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
