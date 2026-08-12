<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;
use PDO;

/**
 * Stores professional documents uploaded by users to their Profile & Settings.
 * doc_type values: 'resume', 'certification', 'medical_certificate'
 */
final class UserDocument extends Model
{
    /**
     * Check if the user_documents table exists (graceful fallback before migration).
     */
    public function tableExists(): bool
    {
        try {
            $this->db()->query('SELECT 1 FROM user_documents LIMIT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Insert a new document record.
     */
    public function create(int $userId, string $docType, string $docPath, ?string $specialization = null): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO user_documents (user_id, doc_type, doc_path, specialization)
             VALUES (:uid, :type, :path, :spec)'
        );
        $stmt->execute([
            ':uid'  => $userId,
            ':type' => $docType,
            ':path' => $docPath,
            ':spec' => $specialization,
        ]);
        return (int)$this->db()->lastInsertId();
    }

    /**
     * Get all documents for a user, keyed by doc_type for easy lookup.
     * If multiple entries of the same type exist, the most recent is used.
     */
    public function findByUserId(int $userId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT * FROM user_documents WHERE user_id = :uid ORDER BY updated_at DESC'
        );
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Key by doc_type, keeping first (most recent)
        $keyed = [];
        foreach ($rows as $row) {
            $type = $row['doc_type'];
            if (!isset($keyed[$type])) {
                $keyed[$type] = $row;
            }
        }
        return $keyed;
    }

    /**
     * Get all document rows for a user (unkeyed list, for display).
     */
    public function findAllByUserId(int $userId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT * FROM user_documents WHERE user_id = :uid ORDER BY doc_type, updated_at DESC'
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find the most recent document of a given type for a user.
     */
    public function findByUserAndType(int $userId, string $docType): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT * FROM user_documents WHERE user_id = :uid AND doc_type = :type
             ORDER BY updated_at DESC LIMIT 1'
        );
        $stmt->execute([':uid' => $userId, ':type' => $docType]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Insert or update a document of a given type for a user.
     * Updates doc_path (and specialization if provided) for existing records.
     */
    public function upsert(int $userId, string $docType, string $docPath, ?string $specialization = null): void
    {
        $existing = $this->findByUserAndType($userId, $docType);
        if ($existing) {
            $stmt = $this->db()->prepare(
                'UPDATE user_documents SET doc_path = :path, specialization = :spec, updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                ':path' => $docPath,
                ':spec' => $specialization,
                ':id'   => $existing['id'],
            ]);
        } else {
            $this->create($userId, $docType, $docPath, $specialization);
        }
    }

    /**
     * Update only the specialization field without touching the file path.
     */
    public function updateSpecialization(int $userId, string $specialization): void
    {
        // Update existing resume or certification record's specialization
        $stmt = $this->db()->prepare(
            'UPDATE user_documents SET specialization = :spec, updated_at = NOW()
             WHERE user_id = :uid AND doc_type IN (\'certification\', \'resume\')
             ORDER BY updated_at DESC LIMIT 1'
        );
        $stmt->execute([':spec' => $specialization, ':uid' => $userId]);

        // If no existing record, create a placeholder certification row
        if ($stmt->rowCount() === 0) {
            $stmt2 = $this->db()->prepare(
                'INSERT INTO user_documents (user_id, doc_type, doc_path, specialization)
                 VALUES (:uid, \'certification\', \'\', :spec)
                 ON DUPLICATE KEY UPDATE specialization = :spec2'
            );
            $stmt2->execute([':uid' => $userId, ':spec' => $specialization, ':spec2' => $specialization]);
        }
    }

    /**
     * Delete a document by its ID, enforcing user ownership.
     * Returns the doc_path of the deleted record so the caller can remove the file.
     */
    public function deleteById(int $docId, int $userId): ?string
    {
        // Fetch to verify ownership and get path
        $stmt = $this->db()->prepare('SELECT doc_path FROM user_documents WHERE id = :id AND user_id = :uid LIMIT 1');
        $stmt->execute([':id' => $docId, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null; // Not found or not owned by this user
        }
        $this->db()->prepare('DELETE FROM user_documents WHERE id = :id AND user_id = :uid')
             ->execute([':id' => $docId, ':uid' => $userId]);
        return $row['doc_path'];
    }

    /**
     * Return ALL certification rows for a user (unkeyed), ordered newest-first.
     * Used to render multiple certification upload slots.
     */
    public function findAllCertsByUserId(int $userId): array
    {
        $stmt = $this->db()->prepare(
            "SELECT * FROM user_documents WHERE user_id = :uid AND doc_type = 'certification' ORDER BY updated_at DESC"
        );
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
