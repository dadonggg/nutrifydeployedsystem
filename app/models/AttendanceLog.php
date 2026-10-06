<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;
use PDO;

final class AttendanceLog extends Model
{
    public function create(int $memberId, string $code): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO attendance_log (member_id, membership_code) VALUES (:mid, :code)'
        );
        $stmt->execute([':mid' => $memberId, ':code' => $code]);
        return (int)$this->db()->lastInsertId();
    }

    /**
     * Log attendance via QR token scan (used by admin officer scan-to-check-in).
     * The token is stored in the membership_code column for backwards compatibility.
     */
    public function createByToken(int $memberId, string $token): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO attendance_log (member_id, membership_code) VALUES (:mid, :code)'
        );
        $stmt->execute([':mid' => $memberId, ':code' => 'QR:' . $token]);
        return (int)$this->db()->lastInsertId();
    }

    /**
     * Check if attendance has already been logged for this member today
     * (prevents duplicate check-ins within the same day via QR scan).
     */
    public function hasCheckedInToday(int $memberId): bool
    {
        $stmt = $this->db()->prepare(
            "SELECT COUNT(*) FROM attendance_log WHERE member_id = :mid AND DATE(check_in) = CURDATE()"
        );
        $stmt->execute([':mid' => $memberId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function findByMemberId(int $memberId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT * FROM attendance_log WHERE member_id = :mid ORDER BY check_in DESC'
        );
        $stmt->execute([':mid' => $memberId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAll(?int $ownerId = null): array
    {
        if ($ownerId !== null && $ownerId > 0) {
            $stmt = $this->db()->prepare(
                'SELECT al.*, gm.membership_code, u.fullname, u.profile_picture_url FROM attendance_log al
                 JOIN gym_members gm ON gm.id = al.member_id
                 LEFT JOIN membership_applications ma ON ma.id = gm.application_id
                 JOIN users u ON u.id = gm.user_id
                 WHERE (gm.gym_owner_id = :oid OR ma.gym_owner_id = :oid)
                 ORDER BY al.check_in DESC'
            );
            $stmt->execute([':oid' => $ownerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $this->db()->query(
            'SELECT al.*, gm.membership_code, u.fullname, u.profile_picture_url FROM attendance_log al
             JOIN gym_members gm ON gm.id = al.member_id
             JOIN users u ON u.id = gm.user_id ORDER BY al.check_in DESC'
        )->fetchAll(PDO::FETCH_ASSOC);
    }
}
