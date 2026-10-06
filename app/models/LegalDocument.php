<?php
declare(strict_types=1);
namespace App\Models;
use App\Core\Model;
use PDO;

final class LegalDocument extends Model
{
    public function create(
        int $userId, 
        string $certReg, 
        string $mayors, 
        string $bizName, 
        string $fireSafety, 
        string $gymName = '', 
        string $gymLogo = '', 
        string $gymAddress = '', 
        int $maintenanceCount = 0, 
        int $trainerCount = 0,
        string $streetAddress = '',
        string $province = '',
        string $cityMunicipality = '',
        string $barangay = '',
        ?string $otherStaff = null
    ): int {
        try {
            \App\Core\Database::beginTransaction();

            $stmt = $this->db()->prepare(
                'INSERT INTO legal_documents (
                    user_id, gym_name, gym_logo, gym_address, maintenance_count, trainer_count, 
                    cert_registration, mayors_permit, business_name_cert, fire_safety_cert, status,
                    street_address, province, city_municipality, barangay, other_staff_needed
                 ) VALUES (
                    :uid, :gn, :gl, :ga, :mc, :tc, 
                    :cr, :mp, :bn, :fs, "pending",
                    :street, :prov, :city, :bar, :other_staff
                 )'
            );
            $stmt->execute([
                ':uid' => $userId, 
                ':gn' => $gymName,
                ':gl' => $gymLogo,
                ':ga' => $gymAddress,
                ':mc' => $maintenanceCount,
                ':tc' => $trainerCount,
                ':cr' => $certReg, 
                ':mp' => $mayors, 
                ':bn' => $bizName, 
                ':fs' => $fireSafety,
                ':street' => $streetAddress,
                ':prov' => $province,
                ':city' => $cityMunicipality,
                ':bar' => $barangay,
                ':other_staff' => $otherStaff
            ]);
            
            $id = (int)$this->db()->lastInsertId();

            \App\Core\Database::commit();
            return $id;

        } catch (\Exception $e) {
            \App\Core\Database::rollback();
            $this->logError("create failed for user ID $userId: " . $e->getMessage());
            return 0;
        }
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM legal_documents WHERE user_id = :uid ORDER BY id DESC LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM legal_documents WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findAllPending(): array
    {
        $stmt = $this->db()->query(
            'SELECT ld.*, u.fullname, u.email
             FROM legal_documents ld
             JOIN users u ON u.id = ld.user_id
             WHERE ld.id IN (
                 SELECT MAX(id) FROM legal_documents GROUP BY user_id
             )
             ORDER BY ld.created_at DESC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find all verified gyms for membership application listing
     */
    public function findAllVerified(): array
    {
        $stmt = $this->db()->query(
            'SELECT ld.*, u.fullname, u.email FROM legal_documents ld
             JOIN users u ON u.id = ld.user_id 
             WHERE ld.status = "verified"
             ORDER BY ld.updated_at DESC'
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus(int $id, string $status, string $feedback = ''): void
    {
        $stmt = $this->db()->prepare('UPDATE legal_documents SET status = :s, admin_feedback = :f WHERE id = :id');
        $stmt->execute([':s' => $status, ':f' => $feedback, ':id' => $id]);
    }

    public function updateDocuments(int $id, string $certReg, string $mayors, string $bizName, string $fireSafety): bool
    {
        try {
            \App\Core\Database::beginTransaction();

            $stmt = $this->db()->prepare(
                'UPDATE legal_documents SET cert_registration=:cr, mayors_permit=:mp, business_name_cert=:bn,
                 fire_safety_cert=:fs, status="pending", admin_feedback=NULL,
                 cert_registration_status="pending", mayors_permit_status="pending",
                 business_name_cert_status="pending", fire_safety_cert_status="pending",
                 cert_registration_comment=NULL, mayors_permit_comment=NULL,
                 business_name_cert_comment=NULL, fire_safety_cert_comment=NULL,
                 cert_registration_checked=0, mayors_permit_checked=0,
                 business_name_cert_checked=0, fire_safety_cert_checked=0
                 WHERE id=:id'
            );
            $stmt->execute([':cr' => $certReg, ':mp' => $mayors, ':bn' => $bizName, ':fs' => $fireSafety, ':id' => $id]);

            $rowCount = $stmt->rowCount();
            if ($rowCount === 0) {
                $this->logError("updateDocuments: No rows affected for document ID $id");
                \App\Core\Database::rollback();
                return false;
            }

            \App\Core\Database::commit();
            return true;

        } catch (\Exception $e) {
            \App\Core\Database::rollback();
            $this->logError("updateDocuments failed for document ID $id: " . $e->getMessage());
            return false;
        }
    }

    public function updateDocumentsAndInfo(
        int $id, 
        string $certReg, 
        string $mayors, 
        string $bizName, 
        string $fireSafety,
        string $gymName,
        string $gymLogo,
        string $gymAddress,
        string $streetAddress,
        string $province,
        string $cityMunicipality,
        string $barangay,
        int $maintenanceCount,
        int $trainerCount,
        ?string $otherStaff
    ): bool {
        try {
            \App\Core\Database::beginTransaction();

            $sql = 'UPDATE legal_documents SET 
                cert_registration=:cr, mayors_permit=:mp, business_name_cert=:bn, fire_safety_cert=:fs,
                gym_name=:gn, gym_address=:ga, street_address=:street, province=:prov, 
                city_municipality=:city, barangay=:bar, maintenance_count=:mc, trainer_count=:tc, 
                other_staff_needed=:other_staff, status="pending", admin_feedback=NULL,
                cert_registration_status="pending", mayors_permit_status="pending",
                business_name_cert_status="pending", fire_safety_cert_status="pending",
                cert_registration_comment=NULL, mayors_permit_comment=NULL,
                business_name_cert_comment=NULL, fire_safety_cert_comment=NULL,
                cert_registration_checked=0, mayors_permit_checked=0,
                business_name_cert_checked=0, fire_safety_cert_checked=0';

            $params = [
                ':cr' => $certReg,
                ':mp' => $mayors,
                ':bn' => $bizName,
                ':fs' => $fireSafety,
                ':gn' => $gymName,
                ':ga' => $gymAddress,
                ':street' => $streetAddress,
                ':prov' => $province,
                ':city' => $cityMunicipality,
                ':bar' => $barangay,
                ':mc' => $maintenanceCount,
                ':tc' => $trainerCount,
                ':other_staff' => $otherStaff,
                ':id' => $id
            ];

            if ($gymLogo !== '') {
                $sql .= ', gym_logo=:gl';
                $params[':gl'] = $gymLogo;
            }

            $sql .= ' WHERE id=:id';

            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);

            \App\Core\Database::commit();
            return true;

        } catch (\Exception $e) {
            \App\Core\Database::rollback();
            $this->logError("updateDocumentsAndInfo failed for document ID $id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ensure all required columns exist in legal_documents table.
     * Self-healing schema migration that runs transparently.
     */
    public function ensureColumnsExist(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }
        $checked = true;

        $columns = [
            'cert_registration_status'  => "ENUM('pending','approved','flagged') DEFAULT 'pending'",
            'cert_registration_comment' => "TEXT DEFAULT NULL",
            'cert_registration_checked' => "TINYINT(1) DEFAULT 0",
            'mayors_permit_status'      => "ENUM('pending','approved','flagged') DEFAULT 'pending'",
            'mayors_permit_comment'     => "TEXT DEFAULT NULL",
            'mayors_permit_checked'     => "TINYINT(1) DEFAULT 0",
            'business_name_cert_status' => "ENUM('pending','approved','flagged') DEFAULT 'pending'",
            'business_name_cert_comment'=> "TEXT DEFAULT NULL",
            'business_name_cert_checked'=> "TINYINT(1) DEFAULT 0",
            'fire_safety_cert_status'   => "ENUM('pending','approved','flagged') DEFAULT 'pending'",
            'fire_safety_cert_comment'  => "TEXT DEFAULT NULL",
            'fire_safety_cert_checked'  => "TINYINT(1) DEFAULT 0",
            'street_address'            => "VARCHAR(255) DEFAULT NULL",
            'province'                  => "VARCHAR(100) DEFAULT NULL",
            'city_municipality'         => "VARCHAR(100) DEFAULT NULL",
            'barangay'                  => "VARCHAR(100) DEFAULT NULL",
            'other_staff_needed'        => "TEXT DEFAULT NULL",
            'maintenance_count'         => "INT DEFAULT 0",
            'trainer_count'             => "INT DEFAULT 0",
            'gym_description'           => "TEXT DEFAULT NULL",
            'opening_hours'             => "JSON DEFAULT NULL",
        ];

        try {
            $existing = [];
            $stmt = $this->db()->query("SHOW COLUMNS FROM legal_documents");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existing[strtolower((string)$row['Field'])] = true;
            }

            foreach ($columns as $col => $definition) {
                if (!isset($existing[strtolower($col)])) {
                    try {
                        $this->db()->exec("ALTER TABLE legal_documents ADD COLUMN `{$col}` {$definition}");
                    } catch (\Throwable $e) {
                        // Ignore duplicate or already-added error
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->logError("ensureColumnsExist error: " . $e->getMessage());
        }
    }

    public function ensureProfileColumns(): void
    {
        $this->ensureColumnsExist();
    }

    /**
     * Update gym profile details (name, description, address, opening hours, logo) by document ID
     */
    public function updateGymProfile(
        int $id,
        string $gymName,
        string $gymDescription,
        string $streetAddress,
        string $province,
        string $cityMunicipality,
        string $barangay,
        string $openingHoursJson,
        ?string $gymLogo = null
    ): bool {
        $this->ensureColumnsExist();
        try {
            $fullAddress = trim(implode(', ', array_filter([$streetAddress, $barangay, $cityMunicipality, $province])));

            $sql = 'UPDATE legal_documents SET 
                gym_name = :gn,
                gym_description = :desc,
                street_address = :street,
                province = :prov,
                city_municipality = :city,
                barangay = :bar,
                gym_address = :ga,
                opening_hours = :oh';

            $params = [
                ':gn'     => $gymName,
                ':desc'   => $gymDescription,
                ':street' => $streetAddress,
                ':prov'   => $province,
                ':city'   => $cityMunicipality,
                ':bar'    => $barangay,
                ':ga'     => $fullAddress,
                ':oh'     => $openingHoursJson,
                ':id'     => $id
            ];

            if ($gymLogo !== null && $gymLogo !== '') {
                $sql .= ', gym_logo = :gl';
                $params[':gl'] = $gymLogo;
            }

            $sql .= ' WHERE id = :id';

            $stmt = $this->db()->prepare($sql);
            return $stmt->execute($params);

        } catch (\Exception $e) {
            $this->logError("updateGymProfile failed for document ID $id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all unapproved documents as flagged when admin requests bulk resubmission.
     * This ensures the applicant sees the upload field on gymowner/apply for each document needing attention.
     */
    public function flagUnapprovedDocs(int $id, string $feedback = ''): void
    {
        $this->ensureColumnsExist();
        $fields = ['cert_registration', 'mayors_permit', 'business_name_cert', 'fire_safety_cert'];
        $doc = $this->findById($id);
        if (!$doc) return;

        foreach ($fields as $f) {
            $statusKey = $f . '_status';
            $commentKey = $f . '_comment';
            if (($doc[$statusKey] ?? 'pending') !== 'approved') {
                $comment = !empty($doc[$commentKey]) ? $doc[$commentKey] : $feedback;
                try {
                    $st = $this->db()->prepare("UPDATE legal_documents SET {$f}_status = 'flagged', {$f}_comment = :c, {$f}_checked = 0 WHERE id = :id");
                    $st->execute([':c' => $comment, ':id' => $id]);
                } catch (\Throwable $e) {}
            }
        }
    }

    /**
     * Verify all documents, set all statuses to approved, and promote user to gym_owner.
     * Returns true on success, false on failure
     */
    public function verifyAll(int $id, string $feedback = ''): bool
    {
        $this->ensureColumnsExist();

        try {
            $stmt = $this->db()->prepare(
                "UPDATE legal_documents SET
                    status = 'verified',
                    admin_feedback = :f,
                    cert_registration_status = 'approved',
                    mayors_permit_status = 'approved',
                    business_name_cert_status = 'approved',
                    fire_safety_cert_status = 'approved',
                    cert_registration_checked = 1,
                    mayors_permit_checked = 1,
                    business_name_cert_checked = 1,
                    fire_safety_cert_checked = 1
                 WHERE id = :id"
            );
            $stmt->execute([':f' => $feedback, ':id' => $id]);

            // Promote user to gym_owner
            $getUserIdStmt = $this->db()->prepare('SELECT user_id FROM legal_documents WHERE id = :id');
            $getUserIdStmt->execute([':id' => $id]);
            $userId = (int)$getUserIdStmt->fetchColumn();
            if ($userId > 0) {
                (new User())->updateRole($userId, 'gym_owner');
                try {
                    $pdo = \App\Core\Database::pdo();
                    $st = $pdo->prepare("UPDATE users SET role = 'gym_owner' WHERE id = ?");
                    $st->execute([$userId]);
                } catch (\Throwable $e) {}
            }

            return true;
        } catch (\Exception $e) {
            $this->logError("verifyAll primary failed for document ID $id: " . $e->getMessage());

            // Resilient fallback: update status directly and promote user
            try {
                $this->db()->prepare("UPDATE legal_documents SET status = 'verified', admin_feedback = :f WHERE id = :id")->execute([':f' => $feedback, ':id' => $id]);
                $getUserIdStmt = $this->db()->prepare('SELECT user_id FROM legal_documents WHERE id = :id');
                $getUserIdStmt->execute([':id' => $id]);
                $userId = (int)$getUserIdStmt->fetchColumn();
                if ($userId > 0) {
                    (new User())->updateRole($userId, 'gym_owner');
                }
                return true;
            } catch (\Exception $e2) {
                $this->logError("verifyAll fallback failed for document ID $id: " . $e2->getMessage());
                return false;
            }
        }
    }

    public function updateDocStatus(int $id, string $docField, string $status, string $comment, bool $checked): bool
    {
        $allowed = ['cert_registration', 'mayors_permit', 'business_name_cert', 'fire_safety_cert'];
        if (!in_array($docField, $allowed, true)) {
            $this->logError("Invalid document field: $docField");
            return false;
        }

        $this->ensureColumnsExist();

        try {
            $sql = "UPDATE legal_documents SET
                {$docField}_status = :status,
                {$docField}_comment = :comment,
                {$docField}_checked = :checked
                WHERE id = :id";
            
            $stmt = $this->db()->prepare($sql);
            return $stmt->execute([
                ':status' => $status,
                ':comment' => $comment,
                ':checked' => $checked ? 1 : 0,
                ':id' => $id,
            ]);

        } catch (\Exception $e) {
            $this->logError("updateDocStatus failed for document ID $id, field $docField: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Recompute overall status from per-document statuses.
     * If all are 'approved' => 'verified'. If any is 'flagged' => 'resubmit'. Else 'pending'.
     * Returns true on success, false on failure
     */
    public function recomputeOverallStatus(int $id): bool
    {
        $this->ensureColumnsExist();
        $doc = $this->findById($id);
        if (!$doc) {
            $this->logError("recomputeOverallStatus: Document ID $id not found");
            return false;
        }

        $fields = ['cert_registration_status', 'mayors_permit_status', 'business_name_cert_status', 'fire_safety_cert_status'];
        $allApproved = true;
        $anyFlagged = false;
        
        foreach ($fields as $f) {
            if (($doc[$f] ?? 'pending') === 'flagged') { 
                $anyFlagged = true; 
            }
            if (($doc[$f] ?? 'pending') !== 'approved') { 
                $allApproved = false; 
            }
        }

        try {
            if ($allApproved) {
                $this->updateStatusInternal($id, 'verified', 'All documents verified.');
            } elseif ($anyFlagged) {
                // Collect flagged document comments
                $feedback = [];
                $labels = [
                    'cert_registration' => 'Certificate of Registration',
                    'mayors_permit' => "Mayor's Permit",
                    'business_name_cert' => 'Business Name Certificate',
                    'fire_safety_cert' => 'Fire Safety Certificate',
                ];
                foreach ($labels as $key => $label) {
                    if (($doc[$key . '_status'] ?? 'pending') === 'flagged') {
                        $comment = $doc[$key . '_comment'] ?? '';
                        $feedback[] = $label . ': ' . ($comment !== '' ? $comment : 'Flagged for resubmission');
                    }
                }
                $this->updateStatusInternal($id, 'resubmit', implode(' | ', $feedback));
            } else {
                $this->updateStatusInternal($id, 'pending', '');
            }

            return true;

        } catch (\Exception $e) {
            $this->logError("recomputeOverallStatus failed for document ID $id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Internal method to update status and promote role if verified
     */
    private function updateStatusInternal(int $id, string $status, string $feedback = ''): void
    {
        try {
            $stmt = $this->db()->prepare('UPDATE legal_documents SET status = :s, admin_feedback = :f WHERE id = :id');
            $stmt->execute([':s' => $status, ':f' => $feedback, ':id' => $id]);

            if ($status === 'verified') {
                $getUserIdStmt = $this->db()->prepare('SELECT user_id FROM legal_documents WHERE id = :id');
                $getUserIdStmt->execute([':id' => $id]);
                $userId = (int)$getUserIdStmt->fetchColumn();
                if ($userId > 0) {
                    (new User())->updateRole($userId, 'gym_owner');
                    try {
                        $pdo = \App\Core\Database::pdo();
                        $updRoleStmt = $pdo->prepare("UPDATE users SET role = 'gym_owner' WHERE id = ?");
                        $updRoleStmt->execute([$userId]);
                    } catch (\Throwable $ex) {}
                }
            }
        } catch (\Throwable $e) {
            $this->logError("updateStatusInternal failed for doc ID $id: " . $e->getMessage());
        }
    }

    /**
     * Resubmit a single document — update just that file and reset its per-doc status.
     * Returns true on success, false on failure
     */
    public function resubmitSingleDoc(int $id, string $docField, string $newPath): bool
    {
        $allowed = ['cert_registration', 'mayors_permit', 'business_name_cert', 'fire_safety_cert'];
        if (!in_array($docField, $allowed, true)) {
            $this->logError("Invalid document field in resubmitSingleDoc: $docField");
            return false;
        }

        $this->ensureColumnsExist();

        try {
            $sql = "UPDATE legal_documents SET
                {$docField} = :path,
                {$docField}_status = 'pending',
                {$docField}_comment = NULL,
                {$docField}_checked = 0
                WHERE id = :id";
            
            $stmt = $this->db()->prepare($sql);
            $stmt->execute([':path' => $newPath, ':id' => $id]);

            // Recompute overall status after successful resubmission
            $this->recomputeOverallStatus($id);

            return true;
        } catch (\Exception $e) {
            $this->logError("resubmitSingleDoc failed for document ID $id, field $docField: " . $e->getMessage());

            // Resilient fallback: update document path directly and set overall status to pending
            try {
                $this->db()->prepare("UPDATE legal_documents SET {$docField} = :path, status = 'pending' WHERE id = :id")->execute([':path' => $newPath, ':id' => $id]);
                return true;
            } catch (\Exception $e2) {
                return false;
            }
        }
    }

    /**
     * Increment staff count for a gym owner
     * @param int $gymOwnerId The gym owner's user ID
     * @param string $staffType Either 'maintenance' or 'trainer'
     * @return bool Success status
     */
    public function incrementStaffCount(int $gymOwnerId, string $staffType): bool
    {
        if (!in_array($staffType, ['maintenance', 'trainer'], true)) {
            $this->logError("Invalid staff type: $staffType");
            return false;
        }

        $column = $staffType === 'maintenance' ? 'maintenance_count' : 'trainer_count';

        try {
            $stmt = $this->db()->prepare(
                "UPDATE legal_documents SET {$column} = {$column} + 1 WHERE user_id = :uid"
            );
            $stmt->execute([':uid' => $gymOwnerId]);
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            $this->logError("incrementStaffCount failed for gym owner ID $gymOwnerId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Decrement staff count for a gym owner
     * @param int $gymOwnerId The gym owner's user ID
     * @param string $staffType Either 'maintenance' or 'trainer'
     * @return bool Success status
     */
    public function decrementStaffCount(int $gymOwnerId, string $staffType): bool
    {
        if (!in_array($staffType, ['maintenance', 'trainer'], true)) {
            $this->logError("Invalid staff type: $staffType");
            return false;
        }

        $column = $staffType === 'maintenance' ? 'maintenance_count' : 'trainer_count';

        try {
            $stmt = $this->db()->prepare(
                "UPDATE legal_documents SET {$column} = GREATEST(0, {$column} - 1) WHERE user_id = :uid"
            );
            $stmt->execute([':uid' => $gymOwnerId]);
            return $stmt->rowCount() > 0;
        } catch (\Exception $e) {
            $this->logError("decrementStaffCount failed for gym owner ID $gymOwnerId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log errors to database log file
     */
    private function logError(string $message): void
    {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/database.log';
        $logMessage = sprintf(
            "[%s] LegalDocument: %s\n",
            date('Y-m-d H:i:s'),
            $message
        );

        @error_log($logMessage, 3, $logFile);
    }
}
