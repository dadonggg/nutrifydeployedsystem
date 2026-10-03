<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\GymEquipment;
use App\Models\EquipmentInspection;
use App\Models\InspectionChecklist;
use App\Models\MaintenanceStaff;
use App\Models\Notification;
use App\Models\LegalDocument;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;

final class MaintenanceController extends Controller
{
    /* ─── Auth Guards ─── */

    private function requireLogin(): array
    {
        if (!isset($_SESSION['user_id'])) { $this->redirect('auth/login'); }
        $user = (new User())->findById((int)$_SESSION['user_id']);
        if (!$user) { unset($_SESSION['user_id']); $this->redirect('auth/login'); }
        return $user;
    }

    private function requireMaintenance(): array
    {
        $user = $this->requireLogin();
        if ($user['role'] !== 'maintenance') { $this->redirect('home/index'); }
        return $user;
    }

    private function notify(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void
    {
        $n = new Notification();
        if ($n->tableExists()) { $n->create($userId, $title, $message, $type, $link); }
    }

    /* ─── Helper: get gym owner id from maintenance staff record ─── */
    private function getGymOwnerId(int $userId): int
    {
        $ms = (new MaintenanceStaff())->findByUserId($userId);
        return $ms ? (int)$ms['gym_id'] : 0;
    }

    /* ─────────────────────────────────────────────────────────────
       PAGE 1 — MAINTENANCE DASHBOARD
       Route: index.php?r=maintenance/dashboard
    ───────────────────────────────────────────────────────────── */
    public function dashboardAction(): void
    {
        $user = $this->requireMaintenance();
        $userId = (int)$user['id'];

        $gymOwnerId = $this->getGymOwnerId($userId);

        $inspectionModel = new EquipmentInspection();
        $equipmentModel  = new GymEquipment();

        $totalEquipment   = 0;
        $inspectedToday   = 0;
        $pendingReports   = 0;
        $submittedReports = 0;
        $recentInspections = [];

        if ($inspectionModel->tableExists()) {
            $inspectedToday   = $inspectionModel->countInspectedToday($userId);
            $pendingReports   = $inspectionModel->countDraft($userId);
            $submittedReports = $inspectionModel->countSubmitted($userId);
            $recentInspections = $inspectionModel->getRecent($userId, 5);
        }

        if ($gymOwnerId > 0) {
            $equipment = $equipmentModel->findByOwnerId($gymOwnerId);
            $totalEquipment = count($equipment);
        }

        $this->view('maintenance/dashboard', [
            'user'             => $user,
            'totalEquipment'   => $totalEquipment,
            'inspectedToday'   => $inspectedToday,
            'pendingReports'   => $pendingReports,
            'submittedReports' => $submittedReports,
            'recentInspections'=> $recentInspections,
        ]);
    }

    /* ─────────────────────────────────────────────────────────────
       PAGE 2 — EQUIPMENT LIST
       Route: index.php?r=maintenance/equipment
    ───────────────────────────────────────────────────────────── */
    public function equipmentAction(): void
    {
        $user = $this->requireMaintenance();
        $userId = (int)$user['id'];
        $gymOwnerId = $this->getGymOwnerId($userId);

        $equipment = [];
        $lastInspections = [];

        if ($gymOwnerId > 0) {
            $equipment = (new GymEquipment())->findByOwnerId($gymOwnerId);
            $inspectionModel = new EquipmentInspection();
            if ($inspectionModel->tableExists()) {
                foreach ($equipment as $eq) {
                    $last = $inspectionModel->findLatestForEquipment((int)$eq['id']);
                    $lastInspections[$eq['id']] = $last;
                }
            }
        }

        $this->view('maintenance/equipment-list', [
            'user'            => $user,
            'equipment'       => $equipment,
            'lastInspections' => $lastInspections,
        ]);
    }

    /* ─────────────────────────────────────────────────────────────
       PAGE 3 — INSPECTION FORM
       Route: index.php?r=maintenance/inspect&equipment_id=X
              POST: save draft or submit
    ───────────────────────────────────────────────────────────── */
    public function inspectAction(): void
    {
        $user = $this->requireMaintenance();
        $userId = (int)$user['id'];
        $gymOwnerId = $this->getGymOwnerId($userId);

        $equipmentId = (int)($_GET['equipment_id'] ?? 0);
        $inspectionId = (int)($_GET['inspection_id'] ?? 0); // for editing draft

        $equipment = null;
        if ($equipmentId > 0) {
            $equipment = (new GymEquipment())->findById($equipmentId);
        }

        if (!$equipment) {
            $this->redirect('maintenance/equipment');
        }

        $error   = '';
        $success = '';
        $existingInspection      = null;
        $existingChecklist       = [];
        $existingPurchaseRequest = null;
        $existingPrItems         = [];

        $inspectionModel = new EquipmentInspection();
        $checklistModel  = new InspectionChecklist();
        $prModel         = new PurchaseRequest();
        $prItemModel     = new PurchaseRequestItem();

        // Load existing draft if editing
        if ($inspectionId > 0 && $inspectionModel->tableExists()) {
            $existingInspection = $inspectionModel->findById($inspectionId);
            if ($existingInspection) {
                if ($checklistModel->tableExists()) {
                    $existingChecklist = $checklistModel->findByInspection($inspectionId);
                }
                if ($prModel->tableExists()) {
                    $existingPurchaseRequest = $prModel->findByInspectionId($inspectionId);
                    if ($existingPurchaseRequest && $prItemModel->tableExists()) {
                        $existingPrItems = $prItemModel->findByRequestId((int)$existingPurchaseRequest['id']);
                    }
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action        = $_POST['action'] ?? 'draft';
            $condition     = $_POST['overall_condition'] ?? 'good';
            $remarks       = trim((string)($_POST['remarks'] ?? ''));
            $signatureData = trim((string)($_POST['signature_data'] ?? ''));
            $inspDate      = date('Y-m-d');

            // Build checklist items from POST
            $descriptions = $_POST['item_description'] ?? [];
            $doneFlags    = $_POST['item_done'] ?? [];
            $notesList    = $_POST['item_notes'] ?? [];

            $items = [];
            foreach ($descriptions as $i => $desc) {
                $desc = trim((string)$desc);
                if ($desc === '') continue;
                $items[] = [
                    'description' => $desc,
                    'done'        => isset($doneFlags[$i]) ? true : false,
                    'notes'       => trim((string)($notesList[$i] ?? '')),
                ];
            }

            // Build Purchase Request items from POST (if condition is needs_repair or condemned)
            $prItems = [];
            $prTotal = 0.0;
            if (in_array($condition, ['needs_repair', 'condemned'], true)) {
                $prNames       = $_POST['pr_item_name'] ?? [];
                $prQuantities  = $_POST['pr_quantity'] ?? [];
                $prUnitPrices  = $_POST['pr_unit_price'] ?? [];
                $prItemNotes   = $_POST['pr_notes'] ?? [];
                $prPurchased   = $_POST['pr_is_purchased'] ?? [];

                foreach ($prNames as $k => $pname) {
                    $pname = trim((string)$pname);
                    if ($pname === '') continue;
                    $qty   = max(1, (int)($prQuantities[$k] ?? 1));
                    $price = max(0.0, (float)($prUnitPrices[$k] ?? 0.0));
                    $sub   = $qty * $price;
                    $prTotal += $sub;

                    $prItems[] = [
                        'item_name'    => $pname,
                        'quantity'     => $qty,
                        'unit_price'   => $price,
                        'notes'        => trim((string)($prItemNotes[$k] ?? '')),
                        'is_purchased' => isset($prPurchased[$k]) ? 1 : 0,
                    ];
                }
            }

            if (!in_array($condition, ['good','needs_repair','condemned'], true)) {
                $error = 'Please select a valid overall condition.';
            } else {
                if (!$inspectionModel->tableExists()) {
                    $error = 'Database tables not set up. Please run the migration script first.';
                } else {
                    if ($existingInspection && $existingInspection['status'] === 'draft') {
                        // Update existing draft
                        $inspectionModel->update(
                            (int)$existingInspection['id'],
                            $condition,
                            $remarks,
                            $signatureData ?: null
                        );
                        $iid = (int)$existingInspection['id'];
                    } else {
                        // Create new inspection
                        $iid = $inspectionModel->create(
                            $equipmentId,
                            $userId,
                            $gymOwnerId,
                            $inspDate,
                            $condition,
                            $remarks,
                            $signatureData ?: null
                        );
                    }

                    // Save checklist items
                    if ($checklistModel->tableExists() && !empty($items)) {
                        $checklistModel->saveItems($iid, $items);
                    }

                    // Save Purchase Request (if any items provided)
                    if (!empty($prItems)) {
                        $prModel->ensureTables();
                        if ($existingPurchaseRequest) {
                            $prId = (int)$existingPurchaseRequest['id'];
                            $prModel->update($prId, $prTotal, 'pending');
                        } else {
                            $prId = $prModel->create(
                                $equipmentId,
                                $userId,
                                $gymOwnerId,
                                $prTotal,
                                $iid,
                                'pending'
                            );
                        }
                        $prItemModel->saveItems($prId, $prItems);
                    }

                    if ($action === 'submit') {
                        $inspectionModel->submit($iid);

                        // Notify gym owner
                        if ($gymOwnerId > 0) {
                            $this->notify(
                                $gymOwnerId,
                                'Inspection Report Submitted',
                                $user['fullname'] . ' submitted an inspection report for ' . $equipment['name'],
                                'info',
                                'gymowner/maintenancereports'
                            );

                            if (!empty($prItems)) {
                                $this->notify(
                                    $gymOwnerId,
                                    'Purchase Request Submitted',
                                    $user['fullname'] . ' requested parts/equipment for ' . $equipment['name'] . ' (Estimated Total: ₱' . number_format($prTotal, 2) . ')',
                                    'warning',
                                    'gymowner/purchaserequests'
                                );
                            }
                        }
                        $success = 'Inspection report and purchase request submitted to gym owner!';
                        $_SESSION['flash_success'] = $success;
                        $this->redirect('maintenance/reports');
                    } else {
                        $success = 'Draft saved successfully.';
                        // Redirect back with inspection_id so they can continue editing
                        $_SESSION['flash_success'] = $success;
                        $this->redirect('maintenance/inspect&equipment_id=' . $equipmentId . '&inspection_id=' . $iid);
                    }
                }
            }
        }

        // Flash messages
        if (isset($_SESSION['flash_success'])) {
            $success = $_SESSION['flash_success'];
            unset($_SESSION['flash_success']);
        }

        $this->view('maintenance/inspect-form', [
            'user'                    => $user,
            'equipment'               => $equipment,
            'existingInspection'      => $existingInspection,
            'existingChecklist'       => $existingChecklist,
            'existingPurchaseRequest' => $existingPurchaseRequest,
            'existingPrItems'         => $existingPrItems,
            'error'                   => $error,
            'success'                 => $success,
        ]);
    }

    /* ─────────────────────────────────────────────────────────────
       PAGE 4 — MY REPORTS LIST
       Route: index.php?r=maintenance/reports
    ───────────────────────────────────────────────────────────── */
    public function reportsAction(): void
    {
        $user = $this->requireMaintenance();
        $userId = (int)$user['id'];

        $reports = [];
        $inspectionModel = new EquipmentInspection();
        if ($inspectionModel->tableExists()) {
            $reports = $inspectionModel->findByMaintenanceUser($userId);
        }

        // Flash message
        $success = $_SESSION['flash_success'] ?? '';
        unset($_SESSION['flash_success']);

        $this->view('maintenance/reports', [
            'user'    => $user,
            'reports' => $reports,
            'success' => $success,
        ]);
    }

    /* ─────────────────────────────────────────────────────────────
       PAGE 5 — REPORT DETAIL
       Route: index.php?r=maintenance/reportdetail&id=X
    ───────────────────────────────────────────────────────────── */
    public function reportdetailAction(): void
    {
        $user = $this->requireMaintenance();
        $userId = (int)$user['id'];

        $id = (int)($_GET['id'] ?? 0);
        $inspectionModel = new EquipmentInspection();
        $inspection = $inspectionModel->tableExists() ? $inspectionModel->findById($id) : null;

        if (!$inspection || (int)$inspection['maintenance_id'] !== $userId) {
            $this->redirect('maintenance/reports');
        }

        $checklist = [];
        $checklistModel = new InspectionChecklist();
        if ($checklistModel->tableExists()) {
            $checklist = $checklistModel->findByInspection($id);
        }

        $purchaseRequest = null;
        $purchaseRequestItems = [];
        $prModel = new PurchaseRequest();
        if ($prModel->tableExists()) {
            $purchaseRequest = $prModel->findByInspectionId($id);
            if ($purchaseRequest) {
                $purchaseRequestItems = (new PurchaseRequestItem())->findByRequestId((int)$purchaseRequest['id']);
            }
        }

        // Handle submit action from detail page
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit') {
            if ($inspection['status'] === 'draft' || $inspection['status'] === 'submitted') {
                $inspectionModel->submit($id);
                // Notify gym owner
                $gymOwnerId = $this->getGymOwnerId($userId);
                if ($gymOwnerId > 0) {
                    $this->notify(
                        $gymOwnerId,
                        'Inspection Report Submitted',
                        $user['fullname'] . ' submitted an inspection report for ' . $inspection['equipment_name'],
                        'info',
                        'gymowner/maintenancereports'
                    );
                    if ($purchaseRequest) {
                        $this->notify(
                            $gymOwnerId,
                            'Purchase Request Submitted',
                            $user['fullname'] . ' submitted a purchase request for ' . $inspection['equipment_name'] . ' (₱' . number_format((float)$purchaseRequest['total_amount'], 2) . ')',
                            'warning',
                            'gymowner/purchaserequests'
                        );
                    }
                }
                $_SESSION['flash_success'] = 'Report submitted to gym owner!';
                $this->redirect('maintenance/reportdetail&id=' . $id);
            }
        }

        $success = $_SESSION['flash_success'] ?? '';
        unset($_SESSION['flash_success']);

        $this->view('maintenance/report-detail', [
            'user'                 => $user,
            'inspection'           => $inspection,
            'checklist'            => $checklist,
            'purchaseRequest'      => $purchaseRequest,
            'purchaseRequestItems' => $purchaseRequestItems,
            'error'                => $error,
            'success'              => $success,
        ]);
    }

    /* ─────────────────────────────────────────────────────────────
       PAGE 6 — PURCHASE REQUESTS (Maintenance Staff View)
       Route: index.php?r=maintenance/purchaserequests
    ───────────────────────────────────────────────────────────── */
    public function purchaserequestsAction(): void
    {
        $user = $this->requireMaintenance();
        $userId = (int)$user['id'];

        $prModel = new PurchaseRequest();
        $requests = $prModel->tableExists() ? $prModel->findByMaintenanceUser($userId) : [];

        // Load items for each request for detailed view
        $itemsByRequest = [];
        $prItemModel = new PurchaseRequestItem();
        if ($prItemModel->tableExists()) {
            foreach ($requests as $req) {
                $itemsByRequest[$req['id']] = $prItemModel->findByRequestId((int)$req['id']);
            }
        }

        $success = $_SESSION['flash_success'] ?? '';
        $error   = $_SESSION['flash_error'] ?? '';
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $this->view('maintenance/purchase-requests', [
            'user'           => $user,
            'requests'       => $requests,
            'itemsByRequest' => $itemsByRequest,
            'success'        => $success,
            'error'          => $error,
        ]);
    }

    /* ─────────────────────────────────────────────────────────────
       HISTORY
       Route: index.php?r=maintenance/history
    ───────────────────────────────────────────────────────────── */
    public function historyAction(): void
    {
        $user = $this->requireMaintenance();
        $userId = (int)$user['id'];

        $reports = [];
        $inspectionModel = new EquipmentInspection();
        if ($inspectionModel->tableExists()) {
            $reports = $inspectionModel->findByMaintenanceUser($userId);
        }

        $this->view('maintenance/reports', [
            'user'    => $user,
            'reports' => $reports,
            'success' => '',
            'isHistory' => true,
        ]);
    }
}
