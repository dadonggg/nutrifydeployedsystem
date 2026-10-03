<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;
use PDO;

final class PurchaseRequestItem extends Model
{
    /** Check if table exists */
    public function tableExists(): bool
    {
        try {
            $this->db()->query('SELECT 1 FROM purchase_request_items LIMIT 1');
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /** Save (replace/insert) multiple items for a purchase request */
    public function saveItems(int $purchaseRequestId, array $items): void
    {
        $this->deleteByRequestId($purchaseRequestId);

        $stmt = $this->db()->prepare(
            'INSERT INTO purchase_request_items
                (purchase_request_id, item_name, quantity, unit_price, notes, is_purchased, status, is_approved, rejection_reason)
             VALUES
                (:prid, :name, :qty, :price, :notes, :purchased, :status, :approved, :reason)'
        );

        foreach ($items as $item) {
            $name = trim((string)($item['item_name'] ?? ''));
            if ($name === '') continue;

            $qty = max(1, (int)($item['quantity'] ?? 1));
            $price = max(0.0, (float)($item['unit_price'] ?? 0.0));
            $notes = !empty($item['notes']) ? trim((string)$item['notes']) : null;
            $purchased = !empty($item['is_purchased']) ? 1 : 0;
            $status = !empty($item['status']) ? (string)$item['status'] : 'pending';
            $isApproved = !empty($item['is_approved']) ? 1 : 0;
            $reason = !empty($item['rejection_reason']) ? trim((string)$item['rejection_reason']) : null;

            $stmt->execute([
                ':prid'      => $purchaseRequestId,
                ':name'      => $name,
                ':qty'       => $qty,
                ':price'     => $price,
                ':notes'     => $notes,
                ':purchased' => $purchased,
                ':status'    => $status,
                ':approved'  => $isApproved,
                ':reason'    => $reason,
            ]);
        }
    }

    /** Find all items belonging to a purchase request */
    public function findByRequestId(int $purchaseRequestId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT * FROM purchase_request_items
             WHERE purchase_request_id = :prid
             ORDER BY id ASC'
        );
        $stmt->execute([':prid' => $purchaseRequestId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Update individual item decision (approved vs rejected with reason) */
    public function updateItemDecision(int $itemId, string $status, ?string $rejectionReason = null, int $isApproved = 0): void
    {
        $stmt = $this->db()->prepare(
            'UPDATE purchase_request_items
             SET status = :status, is_approved = :approved, rejection_reason = :reason
             WHERE id = :id'
        );
        $stmt->execute([
            ':status'   => $status,
            ':approved' => $isApproved,
            ':reason'   => $rejectionReason,
            ':id'       => $itemId,
        ]);
    }

    /** Delete all items for a purchase request */
    public function deleteByRequestId(int $purchaseRequestId): void
    {
        $stmt = $this->db()->prepare(
            'DELETE FROM purchase_request_items WHERE purchase_request_id = :prid'
        );
        $stmt->execute([':prid' => $purchaseRequestId]);
    }

    /** Toggle purchased state of a specific item */
    public function togglePurchased(int $itemId, bool $isPurchased): void
    {
        $stmt = $this->db()->prepare(
            'UPDATE purchase_request_items
             SET is_purchased = :p, status = :s
             WHERE id = :id'
        );
        $status = $isPurchased ? 'purchased' : 'approved';
        $stmt->execute([':p' => $isPurchased ? 1 : 0, ':s' => $status, ':id' => $itemId]);
    }
}
