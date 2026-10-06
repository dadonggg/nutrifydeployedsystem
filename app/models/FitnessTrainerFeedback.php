<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;
use PDO;

final class FitnessTrainerFeedback extends Model
{
    public function __construct()
    {
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        try {
            $this->db()->exec("
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

            // Add columns if they don't exist yet
            $cols = $this->db()->query("SHOW COLUMNS FROM `fitness_trainer_feedback`")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('feedback_status', $cols, true)) {
                $this->db()->exec("ALTER TABLE `fitness_trainer_feedback` ADD COLUMN `feedback_status` VARCHAR(50) DEFAULT 'on_track' AFTER `service_request_id`");
            }
            if (!in_array('feedback_subject', $cols, true)) {
                $this->db()->exec("ALTER TABLE `fitness_trainer_feedback` ADD COLUMN `feedback_subject` VARCHAR(255) DEFAULT 'Progress Review & Feedback' AFTER `feedback_status`");
            }
        } catch (\Throwable $e) {
            $this->logError("Table check failed: " . $e->getMessage());
        }
    }

    public function create(int $progressTrackingId, int $trainerId, int $memberId, int $serviceRequestId, array $data): int
    {
        try {
            $stmt = $this->db()->prepare(
                'INSERT INTO fitness_trainer_feedback 
                (progress_tracking_id, trainer_id, member_id, service_request_id, feedback_status, feedback_subject, 
                 feedback_text, areas_of_improvement, encouragement, next_steps)
                VALUES (:prog_id, :tid, :mid, :req_id, :status, :subject, :feedback, :improve, :encourage, :next)'
            );
            
            $stmt->execute([
                ':prog_id' => $progressTrackingId,
                ':tid' => $trainerId,
                ':mid' => $memberId,
                ':req_id' => $serviceRequestId,
                ':status' => $data['feedback_status'] ?? 'on_track',
                ':subject' => !empty($data['feedback_subject']) ? $data['feedback_subject'] : 'Progress Review & Feedback',
                ':feedback' => $data['feedback_text'],
                ':improve' => $data['areas_of_improvement'] ?? '',
                ':encourage' => $data['encouragement'] ?? '',
                ':next' => $data['next_steps'] ?? ''
            ]);
            
            return (int)$this->db()->lastInsertId();
        } catch (\Exception $e) {
            $this->logError("Failed to create feedback: " . $e->getMessage());
            return 0;
        }
    }

    public function findByServiceRequestId(int $serviceRequestId): array
    {
        try {
            $stmt = $this->db()->prepare(
                'SELECT ftf.*, u.fullname as trainer_name, fpt.snapshot_date, fpt.consistency_score
                 FROM fitness_trainer_feedback ftf
                 LEFT JOIN employees e ON e.id = ftf.trainer_id
                 LEFT JOIN users u ON u.id = e.user_id
                 LEFT JOIN fitness_progress_tracking fpt ON fpt.id = ftf.progress_tracking_id
                 WHERE ftf.service_request_id = :req_id
                 ORDER BY ftf.created_at DESC'
            );
            $stmt->execute([':req_id' => $serviceRequestId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function findByMemberId(int $memberId): array
    {
        try {
            $stmt = $this->db()->prepare(
                'SELECT ftf.*, COALESCE(u.fullname, "Assigned Fitness Coach") as trainer_name, 
                        fpt.snapshot_date, fpt.consistency_score
                 FROM fitness_trainer_feedback ftf
                 LEFT JOIN employees e ON e.id = ftf.trainer_id
                 LEFT JOIN users u ON u.id = e.user_id
                 LEFT JOIN fitness_progress_tracking fpt ON fpt.id = ftf.progress_tracking_id
                 WHERE ftf.member_id = :mid
                 ORDER BY ftf.created_at DESC'
            );
            $stmt->execute([':mid' => $memberId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getTotalFeedbackCount(?int $trainerId = null, ?int $ownerId = null): int
    {
        try {
            if ($ownerId !== null && $ownerId > 0) {
                $stmt = $this->db()->prepare(
                    'SELECT COUNT(ftf.id) FROM fitness_trainer_feedback ftf
                     JOIN gym_members gm ON gm.id = ftf.member_id
                     LEFT JOIN membership_applications ma ON ma.id = gm.application_id
                     WHERE (gm.gym_owner_id = :oid1 OR ma.gym_owner_id = :oid2)'
                );
                $stmt->execute([':oid1' => $ownerId, ':oid2' => $ownerId]);
                return (int)$stmt->fetchColumn();
            }
            if ($trainerId !== null && $trainerId > 0) {
                $stmt = $this->db()->prepare('SELECT COUNT(*) FROM fitness_trainer_feedback WHERE trainer_id = :tid');
                $stmt->execute([':tid' => $trainerId]);
                return (int)$stmt->fetchColumn();
            }
            $stmt = $this->db()->query('SELECT COUNT(*) FROM fitness_trainer_feedback');
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function getLatestFeedbackForMember(int $memberId): ?array
    {
        try {
            $stmt = $this->db()->prepare(
                'SELECT ftf.*, COALESCE(u.fullname, "Trainer") as trainer_name
                 FROM fitness_trainer_feedback ftf
                 LEFT JOIN employees e ON e.id = ftf.trainer_id
                 LEFT JOIN users u ON u.id = e.user_id
                 WHERE ftf.member_id = :mid
                 ORDER BY ftf.created_at DESC
                 LIMIT 1'
            );
            $stmt->execute([':mid' => $memberId]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function logError(string $message): void
    {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/fitness_service.log';
        $logMessage = sprintf("[%s] FitnessTrainerFeedback: %s\n", date('Y-m-d H:i:s'), $message);
        @error_log($logMessage, 3, $logFile);
    }
}
