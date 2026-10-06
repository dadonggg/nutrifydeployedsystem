<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

final class User extends Model
{
    /**
     * @return array{0: string, 1: string} firstname, lastname
     */
    public static function splitDisplayName(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return ['', ''];
        }
        if (preg_match('/^(.+?)\s+(.+)$/u', $name, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        return [$name, ''];
    }

    public static function composeFullname(string $firstname, string $lastname, string $middleInitial): string
    {
        $mi = trim($middleInitial);
        if ($mi !== '' && !str_ends_with($mi, '.')) {
            $mi = mb_strlen($mi) === 1 ? $mi . '.' : $mi;
        }

        $parts = [];
        $fn = trim($firstname);
        $ln = trim($lastname);
        if ($fn !== '') {
            $parts[] = $fn;
        }
        if ($mi !== '') {
            $parts[] = $mi;
        }
        if ($ln !== '') {
            $parts[] = $ln;
        }

        return trim(implode(' ', $parts));
    }

    /**
     * Compute age from a birth date string (Y-m-d).
     */
    public static function computeAge(string $birthDate): int
    {
        $birth = new \DateTime($birthDate);
        $today = new \DateTime('today');
        return (int)$birth->diff($today)->y;
    }

    /**
     * @param non-empty-string $email
     */
    public function create(
        string $firstname,
        string $lastname,
        string $middleInitial,
        string $birthDate,
        float $heightCm,
        float $weightKg,
        string $email,
        string $passwordHash
    ): int {
        $fullname = self::composeFullname($firstname, $lastname, $middleInitial);
        $mi = trim($middleInitial);
        $middleDb = $mi === '' ? null : $mi;
        $age = self::computeAge($birthDate);

        $stmt = $this->db()->prepare(
            'INSERT INTO users (
                firstname, lastname, middle_initial, birth_date, age, height_cm, weight_kg,
                fullname, email, password, is_verified, created_at
            ) VALUES (
                :firstname, :lastname, :middle_initial, :birth_date, :age, :height_cm, :weight_kg,
                :fullname, :email, :password, 0, NOW()
            )'
        );
        $stmt->execute([
            ':firstname' => $firstname,
            ':lastname' => $lastname,
            ':middle_initial' => $middleDb,
            ':birth_date' => $birthDate,
            ':age' => $age,
            ':height_cm' => $heightCm,
            ':weight_kg' => $weightKg,
            ':fullname' => $fullname,
            ':email' => $email,
            ':password' => $passwordHash,
        ]);

        return (int)$this->db()->lastInsertId();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function setVerified(int $userId): void
    {
        $stmt = $this->db()->prepare('UPDATE users SET is_verified = 1 WHERE id = :id');
        $stmt->execute([':id' => $userId]);
    }

    public function updateProfilePicture(int $userId, string $url): bool
    {
        $stmt = $this->db()->prepare('UPDATE users SET profile_picture_url = :url WHERE id = :id');
        return $stmt->execute([':url' => $url, ':id' => $userId]);
    }

    public function updateRole(int $userId, string $role): bool
    {
        try {
            $stmt = $this->db()->prepare('UPDATE users SET role = :role WHERE id = :id');
            $success = $stmt->execute([':role' => $role, ':id' => $userId]);
            if (!$success) {
                error_log("updateRole: Execute failed for user ID $userId, role $role");
                return false;
            }
            
            // Verify that the user exists
            $user = $this->findById($userId);
            if (!$user) {
                error_log("updateRole: User ID $userId not found");
                return false;
            }
            
            // If current user in session is this user, update session role as well
            if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $userId && isset($_SESSION['user'])) {
                $_SESSION['user']['role'] = $role;
            }
            
            error_log("updateRole: Successfully updated/verified user ID $userId with role $role");
            return true;
        } catch (\Exception $e) {
            error_log("updateRole failed for user ID $userId: " . $e->getMessage());
            return false;
        }
    }

    public function findByRole(string $role): array
    {
        $stmt = $this->db()->prepare('SELECT * FROM users WHERE role = :role ORDER BY fullname');
        $stmt->execute([':role' => $role]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return all users whose role is NOT the given role.
     * Used by the admin to list assignable users.
     */
    public function findAllExcept(string $excludeRole): array
    {
        $stmt = $this->db()->prepare(
            'SELECT id, fullname, email, role, created_at FROM users WHERE role != :role ORDER BY fullname'
        );
        $stmt->execute([':role' => $excludeRole]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createOauthVerified(string $fullname, string $email, string $firstname, string $lastname): int
    {
        $randomPassword = bin2hex(random_bytes(32));
        $passwordHash = password_hash($randomPassword, PASSWORD_DEFAULT);

        $stmt = $this->db()->prepare(
            'INSERT INTO users (
                firstname, lastname, middle_initial, birth_date, age, height_cm, weight_kg,
                fullname, email, password, is_verified, created_at
            ) VALUES (
                :firstname, :lastname, NULL, NULL, NULL, NULL, NULL,
                :fullname, :email, :password, 1, NOW()
            )'
        );
        $stmt->execute([
            ':firstname' => $firstname,
            ':lastname' => $lastname,
            ':fullname' => $fullname,
            ':email' => $email,
            ':password' => $passwordHash,
        ]);

        return (int)$this->db()->lastInsertId();
    }

    public function createOauthUnverified(string $fullname, string $email, string $firstname, string $lastname): int
    {
        $randomPassword = bin2hex(random_bytes(32));
        $passwordHash = password_hash($randomPassword, PASSWORD_DEFAULT);

        return $this->createWithProfileOauthUnverified($firstname, $lastname, $fullname, $email, $passwordHash);
    }

    private function createWithProfileOauthUnverified(
        string $firstname,
        string $lastname,
        string $fullname,
        string $email,
        string $passwordHash
    ): int {
        $stmt = $this->db()->prepare(
            'INSERT INTO users (
                firstname, lastname, middle_initial, birth_date, age, height_cm, weight_kg,
                fullname, email, password, is_verified, created_at
            ) VALUES (
                :firstname, :lastname, NULL, NULL, NULL, NULL, NULL,
                :fullname, :email, :password, 0, NOW()
            )'
        );
        $stmt->execute([
            ':firstname' => $firstname,
            ':lastname' => $lastname,
            ':fullname' => $fullname,
            ':email' => $email,
            ':password' => $passwordHash,
        ]);

        return (int)$this->db()->lastInsertId();
    }
}
