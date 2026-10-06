<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;
use PDO;

final class MemberPayment extends Model
{
    public function create(int $memberId, string $paymentType, float $amount, string $paymentMethod = 'cash',
                          ?string $transactionId = null, ?string $paymentDate = null, string $description = ''): int
    {
        $this->ensureTableExists();
        try {
            \App\Core\Database::beginTransaction();

            if ($paymentDate === null) {
                $paymentDate = date('Y-m-d');
            }

            $stmt = $this->db()->prepare(
                'INSERT INTO member_payments (member_id, payment_type, amount, payment_method, transaction_id, payment_date, description)
                 VALUES (:mid, :type, :amount, :method, :txn, :date, :desc)'
            );
            $stmt->execute([
                ':mid' => $memberId,
                ':type' => $paymentType,
                ':amount' => $amount,
                ':method' => $paymentMethod,
                ':txn' => $transactionId,
                ':date' => $paymentDate,
                ':desc' => $description
            ]);

            $paymentId = (int)$this->db()->lastInsertId();
            \App\Core\Database::commit();
            return $paymentId;

        } catch (\Exception $e) {
            \App\Core\Database::rollback();
            $this->logError("Failed to create payment record: " . $e->getMessage());
            return 0;
        }
    }

    public function findByMemberId(int $memberId): array
    {
        $this->ensureTableExists();
        try {
            $stmt = $this->db()->prepare(
                'SELECT * FROM member_payments 
                 WHERE member_id = :mid 
                 ORDER BY payment_date DESC, created_at DESC'
            );
            $stmt->execute([':mid' => $memberId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getMemberPaymentSummary(int $memberId): array
    {
        $this->ensureTableExists();
        try {
            $stmt = $this->db()->prepare(
                'SELECT 
                    payment_type,
                    COUNT(*) as payment_count,
                    SUM(amount) as total_amount,
                    MAX(payment_date) as last_payment_date,
                    MIN(payment_date) as first_payment_date
                 FROM member_payments 
                 WHERE member_id = :mid AND payment_status = "completed"
                 GROUP BY payment_type'
            );
            $stmt->execute([':mid' => $memberId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get overall totals
            $stmt = $this->db()->prepare(
                'SELECT 
                    COUNT(*) as total_payments,
                    SUM(amount) as total_spent,
                    AVG(amount) as avg_payment,
                    MAX(payment_date) as last_payment,
                    MIN(payment_date) as first_payment
                 FROM member_payments 
                 WHERE member_id = :mid AND payment_status = "completed"'
            );
            $stmt->execute([':mid' => $memberId]);
            $totals = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'by_type' => $results,
                'totals' => $totals ?: []
            ];
        } catch (\Exception $e) {
            return [
                'by_type' => [],
                'totals' => ['total_payments' => 0, 'total_spent' => 0, 'avg_payment' => 0]
            ];
        }
    }

    public function getMonthlyPayments(int $memberId, int $months = 12): array
    {
        $stmt = $this->db()->prepare(
            "SELECT 
                DATE_FORMAT(payment_date, '%Y-%m') as month,
                payment_type,
                COUNT(*) as payment_count,
                SUM(amount) as total_amount
             FROM member_payments 
             WHERE member_id = :mid 
               AND payment_status = 'completed'
               AND payment_date >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
             GROUP BY DATE_FORMAT(payment_date, '%Y-%m'), payment_type
             ORDER BY month DESC, payment_type"
        );
        $stmt->execute([':mid' => $memberId, ':months' => $months]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updatePaymentStatus(int $paymentId, string $status): bool
    {
        try {
            $stmt = $this->db()->prepare(
                'UPDATE member_payments SET payment_status = :status WHERE id = :id'
            );
            $stmt->execute([':status' => $status, ':id' => $paymentId]);
            return true;
        } catch (\Exception $e) {
            $this->logError("Failed to update payment status: " . $e->getMessage());
            return false;
        }
    }

    public function findPendingPayments(int $memberId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT * FROM member_payments 
             WHERE member_id = :mid AND payment_status = "pending"
             ORDER BY payment_date DESC'
        );
        $stmt->execute([':mid' => $memberId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUpcomingRenewals(): array
    {
        $stmt = $this->db()->prepare(
            'SELECT gm.*, u.fullname, u.email, mp.amount as last_payment_amount
             FROM gym_members gm
             JOIN users u ON u.id = gm.user_id
             LEFT JOIN member_payments mp ON mp.member_id = gm.id AND mp.payment_type = "membership"
             WHERE gm.membership_status = "active"
               AND gm.expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
             GROUP BY gm.id
             ORDER BY gm.expiration_date ASC'
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ensureTableExists(): void
    {
        try {
            $this->db()->exec("
                CREATE TABLE IF NOT EXISTS `member_payments` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `member_id` INT NOT NULL,
                    `payment_type` VARCHAR(50) NOT NULL DEFAULT 'membership',
                    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `payment_method` VARCHAR(50) NOT NULL DEFAULT 'cash',
                    `payment_status` VARCHAR(50) NOT NULL DEFAULT 'completed',
                    `transaction_id` VARCHAR(100) NULL,
                    `payment_date` DATE NULL,
                    `description` VARCHAR(255) NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_mp_member` (`member_id`),
                    INDEX `idx_mp_date` (`payment_date`),
                    INDEX `idx_mp_status` (`payment_status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (\Throwable $e) {
            // Table creation safeguard
        }
    }

    public function recordMembershipRenewal(int $memberId, float $amount, string $paymentMethod = 'cash',
                                           ?string $transactionId = null, int $days = 30): bool
    {
        $this->ensureTableExists();
        try {
            \App\Core\Database::beginTransaction();

            // Create payment record
            $paymentId = $this->create($memberId, 'membership', $amount, $paymentMethod, $transactionId, null, 'Membership renewal (' . $days . ' days)');

            if ($paymentId > 0) {
                // Update member's expiration date:
                // If expiration_date is past/null, extend $days from CURDATE().
                // If expiration_date is in the future, add $days to current expiration_date.
                $stmt = $this->db()->prepare(
                    'UPDATE gym_members 
                     SET expiration_date = DATE_ADD(
                         CASE 
                             WHEN expiration_date IS NULL OR expiration_date < CURDATE() THEN CURDATE() 
                             ELSE expiration_date 
                         END, 
                         INTERVAL :days DAY
                     ),
                     renewal_date = CURDATE(),
                     is_active = 1
                     WHERE id = :mid'
                );
                $stmt->execute([':days' => $days, ':mid' => $memberId]);

                \App\Core\Database::commit();
                return true;
            }

            \App\Core\Database::rollback();
            return false;

        } catch (\Exception $e) {
            \App\Core\Database::rollback();
            $this->logError("Failed to record membership renewal: " . $e->getMessage());
            return false;
        }
    }

    private function logError(string $message): void
    {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir) && !@mkdir($logDir, 0755, true)) {
            return; // Can't create log directory
        }

        $logFile = $logDir . '/payments.log';
        $logMessage = sprintf(
            "[%s] MemberPayment: %s\n",
            date('Y-m-d H:i:s'),
            $message
        );

        @error_log($logMessage, 3, $logFile);
    }
}