<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;
use PDO;

final class MembershipApplication extends Model
{
    /** Payment pricing constants (fallback — gym owner sets dynamic prices) */
    public const PRICE_STUDENT_MONTHLY  = 600.00;
    public const PRICE_REGULAR_MONTHLY  = 700.00;
    public const PRICE_WITH_TRAINER     = 1500.00;

    public static function getPriceForType(string $type): float
    {
        switch ($type) {
            case 'student_monthly': return self::PRICE_STUDENT_MONTHLY;
            case 'regular_monthly': return self::PRICE_REGULAR_MONTHLY;
            case 'with_trainer':    return self::PRICE_WITH_TRAINER;
            default:                return 0.0;
        }
    }

    private static bool $schemaChecked = false;

    public function ensureTableSchema(): void
    {
        if (self::$schemaChecked) {
            return;
        }
        self::$schemaChecked = true;

        try {
            $cols = [
                'gym_owner_id'          => 'INT DEFAULT NULL',
                'first_name'            => 'VARCHAR(100) DEFAULT NULL',
                'last_name'             => 'VARCHAR(100) DEFAULT NULL',
                'middle_initial'        => 'VARCHAR(10) DEFAULT NULL',
                'phone_number'          => 'VARCHAR(50) DEFAULT NULL',
                'preferred_trainer_id'  => 'INT DEFAULT NULL',
                'payment_type'          => 'VARCHAR(255) DEFAULT NULL',
                'membership_plan_id'    => 'INT DEFAULT NULL',
                'training_package_id'   => 'INT DEFAULT NULL',
                'service_id'            => 'INT DEFAULT NULL',
                'payment_amount'        => 'DECIMAL(10,2) DEFAULT 0.00',
                'payment_mode'          => "VARCHAR(50) DEFAULT 'cash'",
                'payment_status'        => "VARCHAR(50) DEFAULT 'pending'",
                'paymongo_payment_id'   => 'VARCHAR(255) DEFAULT NULL',
                'payment_submitted_at'  => 'DATETIME DEFAULT NULL',
                'status'                => "VARCHAR(50) DEFAULT 'pending'",
                'admin_feedback'        => 'TEXT DEFAULT NULL',
                'reviewer_id'           => 'INT DEFAULT NULL',
                'student_proof'         => 'VARCHAR(500) DEFAULT NULL',
            ];

            foreach ($cols as $col => $type) {
                $check = $this->db()->query("SHOW COLUMNS FROM `membership_applications` LIKE '$col'");
                if ($check->rowCount() === 0) {
                    $this->db()->exec("ALTER TABLE `membership_applications` ADD COLUMN `$col` $type");
                }
            }
        } catch (\Exception $e) {
            // Graceful fallback
        }
    }

    /**
     * Check if user already has an active application (pending/verified/approved).
     * Prevents duplicate membership applications.
     */
    public function hasActiveApplication(int $userId): bool
    {
        $this->ensureTableSchema();
        $stmt = $this->db()->prepare(
            "SELECT COUNT(*) FROM membership_applications
             WHERE user_id = :uid AND status IN ('pending','verified','approved')"
        );
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function create(
        int $userId,
        string $fn,
        string $ln,
        string $mi,
        string $phone,
        ?int $trainerId,
        string $paymentType = 'regular_monthly',
        ?string $studentProof = null
    ): int {
        $this->ensureTableSchema();
        $amount = self::getPriceForType($paymentType);
        $stmt = $this->db()->prepare(
            'INSERT INTO membership_applications
             (user_id, first_name, last_name, middle_initial, phone_number,
              preferred_trainer_id, payment_type, payment_amount, student_proof, status)
             VALUES (:uid, :fn, :ln, :mi, :ph, :tid, :pt, :pa, :sp, "pending")'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':fn'  => $fn,
            ':ln'  => $ln,
            ':mi'  => $mi ?: null,
            ':ph'  => $phone,
            ':tid' => $trainerId,
            ':pt'  => $paymentType,
            ':pa'  => $amount,
            ':sp'  => $studentProof,
        ]);
        return (int)$this->db()->lastInsertId();
    }

    /**
     * Create membership application with service selection and payment mode
     */
    public function createWithService(
        int $userId,
        string $fn,
        string $ln,
        string $mi,
        string $phone,
        ?int $trainerId,
        string $paymentType,
        ?int $serviceId,
        float $paymentAmount,
        string $paymentMode,
        int $gymOwnerId
    ): int {
        $this->ensureTableSchema();
        $stmt = $this->db()->prepare(
            'INSERT INTO membership_applications
             (user_id, gym_owner_id, first_name, last_name, middle_initial, phone_number,
              preferred_trainer_id, payment_type, service_id, payment_amount, payment_mode, payment_status, status)
             VALUES (:uid, :goid, :fn, :ln, :mi, :ph, :tid, :pt, :sid, :pa, :pm, "pending", "pending")'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':goid' => $gymOwnerId,
            ':fn'  => $fn,
            ':ln'  => $ln,
            ':mi'  => $mi ?: null,
            ':ph'  => $phone,
            ':tid' => $trainerId,
            ':pt'  => $paymentType,
            ':sid' => $serviceId,
            ':pa'  => $paymentAmount,
            ':pm'  => $paymentMode,
        ]);
        return (int)$this->db()->lastInsertId();
    }

    /**
     * Create membership application with both membership plan and training package
     */
    public function createWithPlanAndPackage(
        int $userId,
        string $fn,
        string $ln,
        string $mi,
        string $phone,
        ?int $trainerId,
        string $paymentType,
        int $membershipPlanId,
        ?int $trainingPackageId,
        float $paymentAmount,
        string $paymentMode,
        int $gymOwnerId
    ): int {
        $this->ensureTableSchema();
        $stmt = $this->db()->prepare(
            'INSERT INTO membership_applications
             (user_id, gym_owner_id, first_name, last_name, middle_initial, phone_number,
              preferred_trainer_id, payment_type, membership_plan_id, training_package_id, 
              payment_amount, payment_mode, payment_status, status)
             VALUES (:uid, :goid, :fn, :ln, :mi, :ph, :tid, :pt, :mpid, :tpid, :pa, :pm, "pending", "pending")'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':goid' => $gymOwnerId,
            ':fn'  => $fn,
            ':ln'  => $ln,
            ':mi'  => $mi ?: null,
            ':ph'  => $phone,
            ':tid' => $trainerId,
            ':pt'  => $paymentType,
            ':mpid' => $membershipPlanId,
            ':tpid' => $trainingPackageId,
            ':pa'  => $paymentAmount,
            ':pm'  => $paymentMode,
        ]);
        return (int)$this->db()->lastInsertId();
    }

    public function findByUserId(int $userId): ?array
    {
        $this->ensureTableSchema();
        $stmt = $this->db()->prepare('SELECT * FROM membership_applications WHERE user_id = :uid ORDER BY id DESC LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $this->ensureTableSchema();
        $stmt = $this->db()->prepare(
            'SELECT ma.*, u.fullname, u.email, u.firstname, u.lastname, u.middle_initial as user_mi
             FROM membership_applications ma
             JOIN users u ON u.id = ma.user_id WHERE ma.id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findAll(?int $gymOwnerId = null): array
    {
        $this->ensureTableSchema();
        if ($gymOwnerId !== null && $gymOwnerId > 0) {
            $stmt = $this->db()->prepare(
                'SELECT ma.*, u.fullname, u.email FROM membership_applications ma
                 JOIN users u ON u.id = ma.user_id 
                 WHERE ma.gym_owner_id = :goid
                 ORDER BY ma.created_at DESC'
            );
            $stmt->execute([':goid' => $gymOwnerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $this->db()->query(
            'SELECT ma.*, u.fullname, u.email FROM membership_applications ma
             JOIN users u ON u.id = ma.user_id ORDER BY ma.created_at DESC'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update status with feedback. Properly handles the full workflow.
     */
    public function updateStatus(int $id, string $status, string $feedback = '', ?int $reviewerId = null): void
    {
        $stmt = $this->db()->prepare(
            'UPDATE membership_applications SET status=:s, admin_feedback=:f, reviewer_id=:rid WHERE id=:id'
        );
        $stmt->execute([':s' => $status, ':f' => $feedback, ':rid' => $reviewerId, ':id' => $id]);
    }

    public function assignTrainer(int $id, int $trainerId): void
    {
        $stmt = $this->db()->prepare('UPDATE membership_applications SET preferred_trainer_id=:tid WHERE id=:id');
        $stmt->execute([':tid' => $trainerId, ':id' => $id]);
    }

    /**
     * Reset application for resubmission — keeps the same record, sets status back to pending.
     */
    public function resubmit(int $id, string $fn, string $ln, string $mi, string $phone,
                              ?int $trainerId, string $paymentType, ?string $studentProof = null): void
    {
        $amount = self::getPriceForType($paymentType);
        $stmt = $this->db()->prepare(
            'UPDATE membership_applications SET
             first_name=:fn, last_name=:ln, middle_initial=:mi, phone_number=:ph,
             preferred_trainer_id=:tid, payment_type=:pt, payment_amount=:pa, student_proof=:sp,
             status="pending", admin_feedback=NULL
             WHERE id=:id'
        );
        $stmt->execute([
            ':fn'  => $fn, ':ln'  => $ln, ':mi'  => $mi ?: null, ':ph'  => $phone,
            ':tid' => $trainerId, ':pt'  => $paymentType, ':pa'  => $amount,
            ':sp'  => $studentProof, ':id'  => $id,
        ]);
    }

    /**
     * Resubmit membership application with service selection and payment mode
     */
    public function resubmitWithService(
        int $id,
        string $fn,
        string $ln,
        string $mi,
        string $phone,
        ?int $trainerId,
        string $paymentType,
        ?int $serviceId,
        float $paymentAmount,
        string $paymentMode,
        int $gymOwnerId
    ): void {
        $stmt = $this->db()->prepare(
            'UPDATE membership_applications SET
             gym_owner_id=:goid, first_name=:fn, last_name=:ln, middle_initial=:mi, phone_number=:ph,
             preferred_trainer_id=:tid, payment_type=:pt, service_id=:sid, payment_amount=:pa, 
             payment_mode=:pm, payment_status="pending", status="pending", admin_feedback=NULL
             WHERE id=:id'
        );
        $stmt->execute([
            ':goid' => $gymOwnerId,
            ':fn'  => $fn,
            ':ln'  => $ln,
            ':mi'  => $mi ?: null,
            ':ph'  => $phone,
            ':tid' => $trainerId,
            ':pt'  => $paymentType,
            ':sid' => $serviceId,
            ':pa'  => $paymentAmount,
            ':pm'  => $paymentMode,
            ':id'  => $id,
        ]);
    }

    /**
     * Resubmit membership application with both membership plan and training package
     */
    public function resubmitWithPlanAndPackage(
        int $id,
        string $fn,
        string $ln,
        string $mi,
        string $phone,
        ?int $trainerId,
        string $paymentType,
        int $membershipPlanId,
        ?int $trainingPackageId,
        float $paymentAmount,
        string $paymentMode,
        int $gymOwnerId
    ): void {
        $stmt = $this->db()->prepare(
            'UPDATE membership_applications SET
             gym_owner_id=:goid, first_name=:fn, last_name=:ln, middle_initial=:mi, phone_number=:ph,
             preferred_trainer_id=:tid, payment_type=:pt, membership_plan_id=:mpid, training_package_id=:tpid,
             payment_amount=:pa, payment_mode=:pm, payment_status="pending", status="pending", admin_feedback=NULL
             WHERE id=:id'
        );
        $stmt->execute([
            ':goid' => $gymOwnerId,
            ':fn'  => $fn,
            ':ln'  => $ln,
            ':mi'  => $mi ?: null,
            ':ph'  => $phone,
            ':tid' => $trainerId,
            ':pt'  => $paymentType,
            ':mpid' => $membershipPlanId,
            ':tpid' => $trainingPackageId,
            ':pa'  => $paymentAmount,
            ':pm'  => $paymentMode,
            ':id'  => $id,
        ]);
    }

    /**
     * Get count of active (pending/verified) applications per user.
     */
    public function countActiveByUserId(int $userId): int
    {
        $stmt = $this->db()->prepare(
            "SELECT COUNT(*) FROM membership_applications
             WHERE user_id = :uid AND status IN ('pending','verified')"
        );
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function markPaymentSubmitted(int $id): void
    {
        $stmt = $this->db()->prepare('UPDATE membership_applications SET payment_submitted_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
