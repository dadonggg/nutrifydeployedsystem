<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;
use PDO;

final class PurchaseRequest extends Model
{
    /** Check if table exists */
    public function tableExists(): bool
    {
        try {
            $this->db()->query('SELECT 1 FROM purchase_requests LIMIT 1');
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /** Ensure tables exist (graceful fallback) */
    public function ensureTables(): void
    {
        try {
            $this->db()->exec("
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
            ");

            // Ensure new columns exist on existing database
            try { $this->db()->exec("ALTER TABLE `purchase_request_items` ADD COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'pending'"); } catch (\Exception $e) {}
            try { $this->db()->exec("ALTER TABLE `purchase_request_items` ADD COLUMN `rejection_reason` TEXT DEFAULT NULL"); } catch (\Exception $e) {}
            try { $this->db()->exec("ALTER TABLE `purchase_request_items` ADD COLUMN `is_approved` TINYINT(1) NOT NULL DEFAULT 0"); } catch (\Exception $e) {}
        } catch (\Exception $e) {
            // Ignore if already created
        }
    }

    /** Create a new purchase request */
    public function create(
        int $equipmentId,
        int $requestedBy,
        int $gymId,
        float $totalAmount,
        ?int $inspectionId = null,
        string $status = 'pending'
    ): int {
        $this->ensureTables();
        $stmt = $this->db()->prepare(
            'INSERT INTO purchase_requests
                (equipment_id, requested_by, gym_id, total_amount, inspection_id, status)
             VALUES
                (:eid, :req_by, :gid, :total, :iid, :status)'
        );
        $stmt->execute([
            ':eid'    => $equipmentId,
            ':req_by' => $requestedBy,
            ':gid'    => $gymId,
            ':total'  => $totalAmount,
            ':iid'    => $inspectionId,
            ':status' => $status,
        ]);
        return (int)$this->db()->lastInsertId();
    }

    /** Update an existing purchase request */
    public function update(int $id, float $totalAmount, string $status = 'pending'): void
    {
        $this->ensureTables();
        $stmt = $this->db()->prepare(
            'UPDATE purchase_requests
             SET total_amount = :total, status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            ':total'  => $totalAmount,
            ':status' => $status,
            ':id'     => $id,
        ]);
    }

    /** Find by ID with related equipment, inspection, and user info */
    public function findById(int $id): ?array
    {
        $this->ensureTables();
        $stmt = $this->db()->prepare(
            'SELECT pr.*, ge.name AS equipment_name, ge.category AS equipment_category, ge.brand AS equipment_brand,
                    ge.image_path AS equipment_image,
                    u.fullname AS requester_name, u.email AS requester_email,
                    rby.fullname AS reviewer_name,
                    ei.overall_condition, ei.inspection_date, ei.remarks AS inspection_remarks
             FROM purchase_requests pr
             JOIN gym_equipment ge ON ge.id = pr.equipment_id
             JOIN users u ON u.id = pr.requested_by
             LEFT JOIN users rby ON rby.id = pr.reviewed_by
             LEFT JOIN equipment_inspections ei ON ei.id = pr.inspection_id
             WHERE pr.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Find by inspection ID */
    public function findByInspectionId(int $inspectionId): ?array
    {
        $this->ensureTables();
        $stmt = $this->db()->prepare(
            'SELECT pr.*, ge.name AS equipment_name, ge.category AS equipment_category
             FROM purchase_requests pr
             JOIN gym_equipment ge ON ge.id = pr.equipment_id
             WHERE pr.inspection_id = :iid LIMIT 1'
        );
        $stmt->execute([':iid' => $inspectionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Find all purchase requests created by a maintenance user */
    public function findByMaintenanceUser(int $userId): array
    {
        $this->ensureTables();
        $stmt = $this->db()->prepare(
            'SELECT pr.*, ge.name AS equipment_name, ge.category AS equipment_category,
                    ei.overall_condition, ei.inspection_date,
                    rby.fullname AS reviewer_name,
                    (SELECT COUNT(*) FROM purchase_request_items WHERE purchase_request_id = pr.id) AS item_count
             FROM purchase_requests pr
             JOIN gym_equipment ge ON ge.id = pr.equipment_id
             LEFT JOIN users rby ON rby.id = pr.reviewed_by
             LEFT JOIN equipment_inspections ei ON ei.id = pr.inspection_id
             WHERE pr.requested_by = :uid
             ORDER BY pr.created_at DESC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Find purchase requests for Gym Owner view with optional status filter */
    public function findByGymOwner(int $gymId, string $status = 'all'): array
    {
        $this->ensureTables();
        $sql = 'SELECT pr.*, ge.name AS equipment_name, ge.category AS equipment_category,
                       u.fullname AS requester_name,
                       rby.fullname AS reviewer_name,
                       ei.overall_condition, ei.inspection_date,
                       (SELECT COUNT(*) FROM purchase_request_items WHERE purchase_request_id = pr.id) AS item_count
                FROM purchase_requests pr
                JOIN gym_equipment ge ON ge.id = pr.equipment_id
                JOIN users u ON u.id = pr.requested_by
                LEFT JOIN users rby ON rby.id = pr.reviewed_by
                LEFT JOIN equipment_inspections ei ON ei.id = pr.inspection_id
                WHERE pr.gym_id = :gid';

        if ($status !== 'all' && in_array($status, ['pending', 'approved', 'rejected', 'purchased'], true)) {
            $sql .= ' AND pr.status = :status';
        }
        $sql .= ' ORDER BY pr.created_at DESC';

        $stmt = $this->db()->prepare($sql);
        $params = [':gid' => $gymId];
        if ($status !== 'all' && in_array($status, ['pending', 'approved', 'rejected', 'purchased'], true)) {
            $params[':status'] = $status;
        }
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Approve a purchase request */
    public function approve(int $id, int $reviewedBy, ?string $adminNotes = null): void
    {
        $this->ensureTables();
        $stmt = $this->db()->prepare(
            'UPDATE purchase_requests
             SET status = "approved", reviewed_by = :rby, reviewed_at = NOW(), admin_notes = :notes
             WHERE id = :id'
        );
        $stmt->execute([
            ':rby'   => $reviewedBy,
            ':notes' => $adminNotes,
            ':id'    => $id,
        ]);
    }

    /** Reject a purchase request */
    public function reject(int $id, int $reviewedBy, ?string $adminNotes = null): void
    {
        $this->ensureTables();
        $stmt = $this->db()->prepare(
            'UPDATE purchase_requests
             SET status = "rejected", reviewed_by = :rby, reviewed_at = NOW(), admin_notes = :notes
             WHERE id = :id'
        );
        $stmt->execute([
            ':rby'   => $reviewedBy,
            ':notes' => $adminNotes,
            ':id'    => $id,
        ]);
    }

    /** Mark a purchase request as purchased */
    public function markPurchased(int $id, ?string $adminNotes = null): void
    {
        $this->ensureTables();
        $stmt = $this->db()->prepare(
            'UPDATE purchase_requests
             SET status = "purchased", admin_notes = COALESCE(:notes, admin_notes)
             WHERE id = :id'
        );
        $stmt->execute([
            ':notes' => $adminNotes,
            ':id'    => $id,
        ]);

        // Also mark all items as purchased
        $stmtItems = $this->db()->prepare(
            'UPDATE purchase_request_items SET is_purchased = 1 WHERE purchase_request_id = :prid'
        );
        $stmtItems->execute([':prid' => $id]);
    }

    /** Count pending requests for gym owner */
    public function countPendingByOwner(int $gymId): int
    {
        $this->ensureTables();
        $stmt = $this->db()->prepare(
            'SELECT COUNT(*) FROM purchase_requests WHERE gym_id = :gid AND status = "pending"'
        );
        $stmt->execute([':gid' => $gymId]);
        return (int)$stmt->fetchColumn();
    }

    /** Delete a purchase request */
    public function delete(int $id): void
    {
        $this->ensureTables();
        $stmtItems = $this->db()->prepare('DELETE FROM purchase_request_items WHERE purchase_request_id = :id');
        $stmtItems->execute([':id' => $id]);

        $stmt = $this->db()->prepare('DELETE FROM purchase_requests WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
