-- ═══════════════════════════════════════════════════════════════════════════
-- NUTRIFY MAINTENANCE SYSTEM: PURCHASE REQUESTS SCHEMA MIGRATION
-- ═══════════════════════════════════════════════════════════════════════════

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
