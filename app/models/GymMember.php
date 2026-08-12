<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;
use PDO;

final class GymMember extends Model
{
    public function create(
        int $userId, int $appId, string $code, ?int $trainerId,
        string $paymentType = 'regular_monthly', float $paymentAmount = 0.0,
        ?string $startDate = null, ?string $expirationDate = null
    ): int {
        if ($startDate === null) { $startDate = date('Y-m-d'); }
        if ($expirationDate === null) {
            $days = 30;
            $expirationDate = date('Y-m-d', strtotime("+{$days} days"));
        }
        $stmt = $this->db()->prepare(
            'INSERT INTO gym_members
             (user_id, application_id, membership_code, assigned_trainer_id,
              payment_type, payment_amount, start_date, expiration_date)
             VALUES (:uid, :aid, :code, :tid, :pt, :pa, :sd, :ed)'
        );
        $stmt->execute([
            ':uid'=>$userId, ':aid'=>$appId, ':code'=>$code, ':tid'=>$trainerId,
            ':pt'=>$paymentType, ':pa'=>$paymentAmount, ':sd'=>$startDate, ':ed'=>$expirationDate,
        ]);
        return (int)$this->db()->lastInsertId();
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM gym_members WHERE user_id = :uid ORDER BY id DESC LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM gym_members WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByMembershipCode(string $code): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT gm.*, u.fullname, u.email FROM gym_members gm
             JOIN users u ON u.id = gm.user_id WHERE gm.membership_code = :c LIMIT 1'
        );
        $stmt->execute([':c' => $code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Find a member by their QR token (for scan-based attendance check-in).
     * Returns member info joined with user's full name and photo.
     */
    public function findByQrToken(string $token): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT gm.*, u.fullname, u.email, u.profile_picture_url
             FROM gym_members gm
             JOIN users u ON u.id = gm.user_id
             WHERE gm.qr_token = :t LIMIT 1'
        );
        $stmt->execute([':t' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findAll(): array
    {
        return $this->db()->query(
            'SELECT gm.*, u.fullname, u.email, u.profile_picture_url FROM gym_members gm
             JOIN users u ON u.id = gm.user_id ORDER BY gm.created_at DESC'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find active members – gracefully handles missing expiration_date column.
     */
    public function findAllActive(): array
    {
        try {
            return $this->db()->query(
                'SELECT gm.*, u.fullname, u.email FROM gym_members gm
                 JOIN users u ON u.id = gm.user_id
                 WHERE gm.is_active = 1 AND (gm.expiration_date IS NULL OR gm.expiration_date >= CURDATE())
                 ORDER BY gm.created_at DESC'
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            // Fallback if expiration_date column doesn't exist yet
            return $this->db()->query(
                'SELECT gm.*, u.fullname, u.email FROM gym_members gm
                 JOIN users u ON u.id = gm.user_id
                 WHERE gm.is_active = 1 ORDER BY gm.created_at DESC'
            )->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function getMonthlyRevenue(?string $monthYear = null): float
    {
        if ($monthYear === null) { $monthYear = date('Y-m'); }
        try {
            $stmt = $this->db()->prepare(
                "SELECT COALESCE(SUM(payment_amount),0) as total FROM gym_members
                 WHERE DATE_FORMAT(created_at, '%Y-%m') = :my"
            );
            $stmt->execute([':my' => $monthYear]);
            return (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (\PDOException $e) { return 0.0; }
    }

    public function getRevenueByMonth(int $months = 6): array
    {
        try {
            $stmt = $this->db()->prepare(
                "SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                        COALESCE(SUM(payment_amount),0) as total,
                        COUNT(*) as member_count
                 FROM gym_members
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :m MONTH)
                 GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                 ORDER BY month DESC"
            );
            $stmt->execute([':m' => $months]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) { return []; }
    }

    public function assignTrainer(int $memberId, int $trainerId): void
    {
        $stmt = $this->db()->prepare('UPDATE gym_members SET assigned_trainer_id = :tid WHERE id = :id');
        $stmt->execute([':tid' => $trainerId, ':id' => $memberId]);
    }

    /**
     * Update the QR token for a member (used on renewal if new token is desired).
     */
    public function updateQrToken(int $memberId, string $token): void
    {
        $stmt = $this->db()->prepare('UPDATE gym_members SET qr_token = :t WHERE id = :id');
        $stmt->execute([':t' => $token, ':id' => $memberId]);
    }

    /**
     * Stamp a member record with QR token + member ID number + issue date.
     * Called immediately after create() in AdmofficerController.
     */
    public function stampMemberCard(int $memberId, string $qrToken, string $memberIdNumber, string $issueDate): void
    {
        try {
            $stmt = $this->db()->prepare(
                'UPDATE gym_members SET qr_token = :t, member_id_number = :mid, issue_date = :isd WHERE id = :id'
            );
            $stmt->execute([':t' => $qrToken, ':mid' => $memberIdNumber, ':isd' => $issueDate, ':id' => $memberId]);
        } catch (\PDOException $e) {
            // Gracefully handle if columns don't exist yet (migration not run)
        }
    }

    public static function generateCode(): string
    {
        return 'GYM-' . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * Generate a cryptographically secure QR token (64-char hex string).
     */
    public static function generateQrToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Generate a human-readable member ID in the format NTF-YYYY-NNNNN.
     * The suffix is derived from the given DB auto-increment ID.
     */
    public static function generateMemberId(int $dbId): string
    {
        return 'NTF-' . date('Y') . '-' . str_pad((string)$dbId, 5, '0', STR_PAD_LEFT);
    }
}
