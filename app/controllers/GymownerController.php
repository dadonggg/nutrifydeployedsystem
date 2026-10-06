<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\LegalDocument;
use App\Models\MembershipApplication;
use App\Models\GymMember;
use App\Models\Employee;
use App\Models\AttendanceLog;
use App\Models\FinancialRecord;
use App\Models\Notification;
use App\Models\MembershipPlan;
use App\Models\GymService;
use App\Models\PayMongoConfig;
use App\Models\EquipmentInspection;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;

final class GymownerController extends Controller
{
    private function requireLogin(): array
    {
        if (!isset($_SESSION['user_id'])) { $this->redirect('auth/login'); }
        $user = (new User())->findById((int)$_SESSION['user_id']);
        if (!$user) { unset($_SESSION['user_id']); $this->redirect('auth/login'); }
        return $user;
    }

    private function requireGymOwner(): array
    {
        $user = $this->requireLogin();
        if ($user['role'] !== 'gym_owner') { $this->redirect('home/index'); }
        return $user;
    }

    private function notify(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void
    {
        $n = new Notification();
        if ($n->tableExists()) { $n->create($userId, $title, $message, $type, $link); }
    }

    /** Customer applies to become gym owner – upload legal documents */
    public function applyAction(): void
    {
        // Prevent browser caching to ensure fresh data
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $user = $this->requireLogin();
        // Block gym owners and admins (they don't need to apply)
        // Allow customers, fitness_enthusiast, and any other non-privileged role who may have a pending application
        $blockedRoles = ['gym_owner', 'admin', 'administrative_officer', 'trainer', 'maintenance', 'marketing_officer'];
        if (in_array($user['role'], $blockedRoles, true)) { $this->redirect('home/index'); }

        $error = ''; $success = '';
        $docModel = new LegalDocument();
        
        // Always fetch fresh data from database
        $existing = $docModel->findByUserId((int)$user['id']);

        // Auto-heal: If document is already verified, immediately ensure role is gym_owner and redirect to dashboard
        if ($existing && $existing['status'] === 'verified') {
            if ($user['role'] !== 'gym_owner') {
                (new User())->updateRole((int)$user['id'], 'gym_owner');
            }
            $this->redirect('home/index');
            return;
        }

        $docFieldLabels = [
            'cert_registration' => 'Certificate of Registration',
            'mayors_permit'     => "Mayor's Permit",
            'business_name_cert'=> 'Business Name Certificate',
            'fire_safety_cert'  => 'Fire Safety Certificate',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? 'submit_all';

            if ($action === 'resubmit_doc') {
                // Per-document resubmission: only re-upload the flagged file
                $docField = $_POST['doc_field'] ?? '';
                $allowed = ['cert_registration','mayors_permit','business_name_cert','fire_safety_cert'];
                if (!in_array($docField, $allowed, true) || !$existing) {
                    $error = 'Invalid document field.';
                } elseif (empty($_FILES[$docField]['tmp_name'])) {
                    $error = 'Please select a file to upload.';
                } else {
                    $uploadDir = BASE_PATH . '/public/uploads/legal_documents/';
                    if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }
                    $ext = strtolower(pathinfo($_FILES[$docField]['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['pdf','jpg','jpeg','png'], true)) {
                        $error = 'Only PDF, JPG, PNG files are allowed.';
                    } else {
                        $filename = $docField . '_' . $user['id'] . '_' . time() . '.' . $ext;
                        if (move_uploaded_file($_FILES[$docField]['tmp_name'], $uploadDir . $filename)) {
                            $path = 'uploads/legal_documents/' . $filename;
                            $docModel->resubmitSingleDoc((int)$existing['id'], $docField, $path);
                            $labelName = $docFieldLabels[$docField] ?? $docField;
                            $success = $labelName . ' resubmitted successfully. Waiting for admin review.';

                            // Notify all admins of the resubmission
                            $admins = (new User())->findByRole('admin');
                            foreach ($admins as $adm) {
                                $this->notify(
                                    (int)$adm['id'],
                                    'Document Resubmitted',
                                    ($user['fullname'] ?? 'Applicant') . " has resubmitted {$labelName} for review.",
                                    'info',
                                    'admin/reviewlegal?id=' . $existing['id']
                                );
                            }
                        } else {
                            $error = 'Failed to upload file.';
                        }
                    }
                    $existing = $docModel->findByUserId((int)$user['id']);
                }
            } else {
                // Full initial submission with gym details
                $gymName = trim((string)($_POST['gym_name'] ?? ''));
                $streetAddress = trim((string)($_POST['street_address'] ?? ''));
                $province = trim((string)($_POST['province'] ?? ''));
                $cityMunicipality = trim((string)($_POST['city_municipality'] ?? ''));
                $barangay = trim((string)($_POST['barangay'] ?? ''));
                $maintenanceCount = (int)($_POST['maintenance_count'] ?? 0);
                $trainerCount = (int)($_POST['trainer_count'] ?? 0);

                // Validate gym details
                if ($gymName === '' || $streetAddress === '' || $province === '' || $cityMunicipality === '' || $barangay === '') {
                    $error = 'Gym name and address fields are required.';
                } else {
                    // Concatenate the normalized address fields
                    $gymAddress = trim($streetAddress . ', ' . $barangay . ', ' . $cityMunicipality . ', ' . $province);

                    // Parse custom/other staff roles needed
                    $otherStaff = [];
                    if (isset($_POST['other_staff_roles']) && isset($_POST['other_staff_counts'])) {
                        $roles = $_POST['other_staff_roles'];
                        $counts = $_POST['other_staff_counts'];
                        for ($i = 0; $i < count($roles); $i++) {
                            $role = trim((string)($roles[$i] ?? ''));
                            $count = (int)($counts[$i] ?? 0);
                            if ($role !== '') {
                                $otherStaff[] = [
                                    'role' => $role,
                                    'count' => $count
                                ];
                            }
                        }
                    }
                    $otherStaffJson = !empty($otherStaff) ? json_encode($otherStaff) : null;

                    $uploadDir = BASE_PATH . '/public/uploads/legal_documents/';
                    if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }

                    // Handle gym logo upload
                    $gymLogo = '';
                    if (!empty($_FILES['gym_logo']['tmp_name'])) {
                        $ext = strtolower(pathinfo($_FILES['gym_logo']['name'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg','jpeg','png'], true)) {
                            $logoFilename = 'gym_logo_' . $user['id'] . '_' . time() . '.' . $ext;
                            if (move_uploaded_file($_FILES['gym_logo']['tmp_name'], $uploadDir . $logoFilename)) {
                                $gymLogo = 'uploads/legal_documents/' . $logoFilename;
                            }
                        }
                    }

                    $fields = ['cert_registration','mayors_permit','business_name_cert','fire_safety_cert'];
                    $paths = [];
                    $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];

                    foreach ($fields as $f) {
                        if (empty($_FILES[$f]['tmp_name'])) { $error = 'All four documents are required.'; break; }
                        $ext = strtolower(pathinfo($_FILES[$f]['name'], PATHINFO_EXTENSION));
                        if (!in_array($ext, ['pdf','jpg','jpeg','png'], true)) { $error = 'Only PDF, JPG, PNG allowed.'; break; }
                        
                        // Check real MIME
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = $finfo ? finfo_file($finfo, $_FILES[$f]['tmp_name']) : false;
                        if ($finfo) { finfo_close($finfo); }
                        if ($mime && !in_array($mime, $allowedMimes, true)) {
                            $error = 'Invalid file format detected for ' . htmlspecialchars($f) . '.';
                            break;
                        }

                        $filename = $f . '_' . $user['id'] . '_' . time() . '.' . $ext;
                        if (!move_uploaded_file($_FILES[$f]['tmp_name'], $uploadDir . $filename)) { $error = 'Failed to upload ' . $f; break; }
                        $paths[$f] = 'uploads/legal_documents/' . $filename;
                    }

                    if ($error === '') {
                        if ($existing && in_array($existing['status'], ['resubmit', 'rejected'], true)) {
                            // Allowed: update the existing record with new files and info
                            $logoPath = $gymLogo !== '' ? $gymLogo : ($existing['gym_logo'] ?? '');
                            $docModel->updateDocumentsAndInfo((int)$existing['id'], $paths['cert_registration'], $paths['mayors_permit'], $paths['business_name_cert'], $paths['fire_safety_cert'], $gymName, $logoPath, $gymAddress, $streetAddress, $province, $cityMunicipality, $barangay, $maintenanceCount, $trainerCount, $otherStaffJson);
                            $success = 'Documents resubmitted successfully. Waiting for admin review.';

                            $admins = (new User())->findByRole('admin');
                            foreach ($admins as $adm) {
                                $this->notify((int)$adm['id'], 'Application Resubmitted', ($user['fullname'] ?? 'Applicant') . ' has resubmitted their Gym Owner application.', 'info', 'admin/reviewlegal?id=' . $existing['id']);
                            }
                        } elseif (!$existing) {
                            // Allowed: first-time submission — create a single new record
                            $newId = $docModel->create((int)$user['id'], $paths['cert_registration'], $paths['mayors_permit'], $paths['business_name_cert'], $paths['fire_safety_cert'], $gymName, $gymLogo, $gymAddress, $maintenanceCount, $trainerCount, $streetAddress, $province, $cityMunicipality, $barangay, $otherStaffJson);
                            $success = 'Application submitted! Waiting for admin review.';

                            $admins = (new User())->findByRole('admin');
                            foreach ($admins as $adm) {
                                $this->notify((int)$adm['id'], 'New Gym Owner Application', ($user['fullname'] ?? 'Applicant') . ' submitted an application for ' . $gymName . '.', 'info', 'admin/reviewlegal?id=' . $newId);
                            }
                        } else {
                            // Block: already has an active application (pending or verified)
                            $error = 'You already have an active application (Status: ' . ucfirst($existing['status']) . '). Please wait for the admin to review your existing submission.';
                        }
                        $existing = $docModel->findByUserId((int)$user['id']);
                    }
                }
            }
        }

        // POST-Redirect-GET: after successful submission, redirect to prevent duplicate on refresh
        if ($success !== '') {
            $_SESSION['flash_success'] = $success;
            $this->redirect('gymowner/apply');
        }

        // Restore flash message if redirected
        if (isset($_SESSION['flash_success'])) {
            $success = $_SESSION['flash_success'];
            unset($_SESSION['flash_success']);
        }

        $this->view('gymowner/apply', ['user' => $user, 'error' => $error, 'success' => $success, 'legalDoc' => $existing]);
    }

    /* ─── Membership Applications ─── */
    public function membershipsAction(): void
    {
        $user = $this->requireGymOwner();
        $apps = (new MembershipApplication())->findAll((int)$user['id']);
        $trainers = (new Employee())->findAvailableTrainers();
        $this->view('gymowner/memberships', ['user' => $user, 'apps' => $apps, 'trainers' => $trainers]);
    }

    public function reviewmembershipAction(): void
    {
        $user = $this->requireGymOwner();
        $id = (int)($_GET['id'] ?? 0);
        $appModel = new MembershipApplication();
        $app = $appModel->findById($id);
        if (!$app) { $this->redirect('gymowner/memberships'); }

        $error = ''; $success = '';
        $trainers = (new Employee())->findAvailableTrainers();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $feedback = trim((string)($_POST['feedback'] ?? ''));
            $trainerId = !empty($_POST['trainer_id']) ? (int)$_POST['trainer_id'] : null;

            if ($action === 'approve') {
                $paymentType = $app['payment_type'] ?? 'regular_monthly';
                $paymentAmount = round((float)($app['payment_amount'] ?? 0), 2);
                $code = GymMember::generateCode();
                (new GymMember())->create((int)$app['user_id'], $id, $code, $trainerId, $paymentType, $paymentAmount, null, null, (int)$user['id']);
                $appModel->updateStatus($id, 'approved', 'Membership approved. Code: ' . $code, (int)$user['id']);
                $this->notify((int)$app['user_id'], 'Membership Approved!', 'Your membership code: ' . $code, 'success', 'membership/verifycode');
                if ($trainerId) { 
                    (new Employee())->setAvailability($trainerId, false); 
                    // Record trainer session revenue
                    (new FinancialRecord())->addRevenue((int)$user['id'], 'Trainer Session: ' . ($app['fullname'] ?? 'N/A'), $paymentAmount, 'Member: ' . ($app['fullname'] ?? 'N/A'), 'Trainer Sessions');
                } else {
                    // Record membership revenue
                    (new FinancialRecord())->addRevenue((int)$user['id'], 'Membership: ' . ucfirst(str_replace('_', ' ', $paymentType)), $paymentAmount, 'Member: ' . ($app['fullname'] ?? 'N/A'), 'Membership Revenue');
                }
                $success = 'Membership approved! Code: ' . $code;
            } elseif ($action === 'reject') {
                if ($feedback === '') { $error = 'Feedback is required.'; }
                else {
                    $appModel->updateStatus($id, 'rejected', $feedback, (int)$user['id']);
                    $this->notify((int)$app['user_id'], 'Membership Rejected', $feedback, 'danger', 'membership/apply');
                    $success = 'Application rejected.';
                }
            } elseif ($action === 'resubmit') {
                if ($feedback === '') { $error = 'Feedback is required.'; }
                else {
                    $appModel->updateStatus($id, 'resubmit', $feedback, (int)$user['id']);
                    $this->notify((int)$app['user_id'], 'Membership — Resubmission Required', $feedback, 'warning', 'membership/apply');
                    $success = 'Resubmission requested.';
                }
            }
            $app = $appModel->findById($id);
        }

        $this->view('gymowner/review_membership', ['user' => $user, 'app' => $app, 'trainers' => $trainers, 'error' => $error, 'success' => $success]);
    }

    public function membersAction(): void
    {
        $user = $this->requireGymOwner();
        $members = (new GymMember())->findAll((int)$user['id']);
        $this->view('gymowner/members', ['user' => $user, 'members' => $members]);
    }

    public function attendanceAction(): void
    {
        $user = $this->requireGymOwner();
        $logs = (new AttendanceLog())->findAll((int)$user['id']);
        $this->view('gymowner/attendance', ['user' => $user, 'logs' => $logs]);
    }

    /* ─── Membership Plans Management ─── */
    public function plansAction(): void
    {
        $user = $this->requireGymOwner();
        $planModel = new MembershipPlan();
        $error = ''; $success = '';

        if (!$planModel->tableExists()) {
            $this->view('gymowner/plans', ['user'=>$user, 'plans'=>[], 'error'=>'Please run migration_v5_features.sql first.', 'success'=>'']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $name = trim((string)($_POST['plan_name'] ?? ''));
            $desc = trim((string)($_POST['plan_desc'] ?? ''));
            $price = round((float)($_POST['plan_price'] ?? 0), 2);
            $duration = (int)($_POST['plan_duration'] ?? 30);

            if ($action === 'create') {
                if ($name === '' || $price <= 0) { $error = 'Name and price are required.'; }
                else { $planModel->create((int)$user['id'], $name, $desc, $price, $duration); $success = 'Plan created.'; }
            } elseif ($action === 'update') {
                $pid = (int)($_POST['plan_id'] ?? 0);
                if ($pid > 0 && $name !== '' && $price > 0) { $planModel->update($pid, $name, $desc, $price, $duration); $success = 'Plan updated.'; }
                else { $error = 'Invalid data.'; }
            } elseif ($action === 'delete') {
                $pid = (int)($_POST['plan_id'] ?? 0);
                if ($pid > 0) { $planModel->delete($pid); $success = 'Plan deleted.'; }
            }
        }

        $plans = $planModel->findByOwnerId((int)$user['id']);
        $this->view('gymowner/plans', ['user' => $user, 'plans' => $plans, 'error' => $error, 'success' => $success]);
    }

    /* ─── Gym Services Management ─── */
    public function servicesAction(): void
    {
        $user = $this->requireGymOwner();
        $svcModel = new GymService();
        $error = ''; $success = '';

        if (!$svcModel->tableExists()) {
            $this->view('gymowner/services', ['user'=>$user, 'services'=>[], 'error'=>'Please run migration_v5_features.sql first.', 'success'=>'']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $name = trim((string)($_POST['svc_name'] ?? ''));
            $desc = trim((string)($_POST['svc_desc'] ?? ''));
            // Fix: Use number_format to prevent rounding issues
            $memberPrice = (float)($_POST['svc_member_price'] ?? 0);
            $nonMemberPrice = (float)($_POST['svc_nonmember_price'] ?? 0);

            if ($action === 'create') {
                if ($name === '' || $memberPrice <= 0) { $error = 'Name and member price required.'; }
                else { $svcModel->create((int)$user['id'], $name, $desc, $memberPrice, $nonMemberPrice); $success = 'Service added.'; }
            } elseif ($action === 'update') {
                $sid = (int)($_POST['svc_id'] ?? 0);
                if ($sid > 0 && $name !== '' && $memberPrice > 0) { $svcModel->update($sid, $name, $desc, $memberPrice, $nonMemberPrice); $success = 'Service updated.'; }
                else { $error = 'Invalid data.'; }
            } elseif ($action === 'delete') {
                $sid = (int)($_POST['svc_id'] ?? 0);
                if ($sid > 0) { $svcModel->delete($sid); $success = 'Service deleted.'; }
            }
        }

        $services = $svcModel->findByOwnerId((int)$user['id']);
        $this->view('gymowner/services', ['user' => $user, 'services' => $services, 'error' => $error, 'success' => $success]);
    }

    /* ─── PayMongo Configuration ─── */
    public function paymongoAction(): void
    {
        $user = $this->requireGymOwner();
        $configModel = new PayMongoConfig();
        $error = ''; $success = '';

        if (!$configModel->tableExists()) {
            $this->view('gymowner/paymongo', [
                'user' => $user,
                'config' => null,
                'error' => 'Please run RUN_THIS_SQL_FIRST.sql to create the paymongo_config table.',
                'success' => ''
            ]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'save') {
                $publicKey = trim((string)($_POST['public_key'] ?? ''));
                $secretKey = trim((string)($_POST['secret_key'] ?? ''));
                $isActive = isset($_POST['is_active']) && $_POST['is_active'] === '1';

                if ($publicKey === '' || $secretKey === '') {
                    $error = 'Both public key and secret key are required.';
                } elseif (strncmp($publicKey, 'pk_', 3) !== 0) {
                    $error = 'Invalid public key format. Should start with "pk_"';
                } elseif (strncmp($secretKey, 'sk_', 3) !== 0) {
                    $error = 'Invalid secret key format. Should start with "sk_"';
                } else {
                    $configModel->upsert((int)$user['id'], $publicKey, $secretKey, $isActive);
                    $success = 'PayMongo configuration saved successfully!';
                }
            } elseif ($action === 'toggle') {
                $config = $configModel->findByOwnerId((int)$user['id']);
                if ($config) {
                    $newStatus = !((bool)$config['is_active']);
                    $configModel->toggleActive((int)$config['id'], $newStatus);
                    $success = $newStatus ? 'PayMongo enabled.' : 'PayMongo disabled.';
                }
            } elseif ($action === 'delete') {
                $config = $configModel->findByOwnerId((int)$user['id']);
                if ($config) {
                    $configModel->delete((int)$config['id']);
                    $success = 'PayMongo configuration deleted.';
                }
            }
        }

        $config = $configModel->findByOwnerId((int)$user['id']);
        $this->view('gymowner/paymongo', [
            'user' => $user,
            'config' => $config,
            'error' => $error,
            'success' => $success
        ]);
    }

    /* ─── Manage Users (Assign / Revoke Administrative Officer) ─── */

    /**
     * List all users so the gym owner can assign the Administrative Officer role.
     */
    public function usersAction(): void
    {
        $user     = $this->requireGymOwner();
        $allUsers = (new User())->findAllExcept('admin');

        $success = $_SESSION['assign_success'] ?? '';
        $error   = $_SESSION['assign_error']   ?? '';
        unset($_SESSION['assign_success'], $_SESSION['assign_error']);

        $this->view('gymowner/users', [
            'user'     => $user,
            'allUsers' => $allUsers,
            'success'  => $success,
            'error'    => $error,
        ]);
    }

    /**
     * Assign or revoke the Administrative Officer role.
     * POST  index.php?r=gymowner/assignofficer
     *   id     — target user ID
     *   action — 'assign' | 'revoke'
     */
    public function assignofficerAction(): void
    {
        $owner = $this->requireGymOwner();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('gymowner/users');
        }

        $targetId = (int)($_POST['id'] ?? 0);
        $action   = $_POST['action'] ?? '';

        if ($targetId <= 0) {
            $_SESSION['assign_error'] = 'Invalid user ID.';
            $this->redirect('gymowner/users');
        }

        $userModel  = new User();
        $targetUser = $userModel->findById($targetId);

        if (!$targetUser) {
            $_SESSION['assign_error'] = 'User not found.';
            $this->redirect('gymowner/users');
        }

        // Safety: never touch admin or another gym owner
        if (in_array($targetUser['role'], ['admin', 'gym_owner'], true)) {
            $_SESSION['assign_error'] = 'Cannot modify an admin or gym owner account.';
            $this->redirect('gymowner/users');
        }

        if ($action === 'assign') {
            // Enforce limit: Only 1 Administrative Officer allowed. Revoke existing officer if present.
            $existingOfficer = null;
            try {
                $stmt = \App\Core\Database::pdo()->prepare("SELECT id, fullname FROM users WHERE role = 'administrative_officer' AND id != :tid LIMIT 1");
                $stmt->execute([':tid' => $targetId]);
                $existingOfficer = $stmt->fetch(\PDO::FETCH_ASSOC);
            } catch (\Exception $e) { /* ignore */ }

            if ($existingOfficer) {
                $userModel->updateRole((int)$existingOfficer['id'], 'customer');
                $this->notify(
                    (int)$existingOfficer['id'],
                    'Administrative Officer Role Reassigned',
                    'Your Administrative Officer role has been reassigned to another user by the gym owner.',
                    'warning',
                    'home/index'
                );
            }

            $ok = $userModel->updateRole($targetId, 'administrative_officer');
            if ($ok) {
                $this->notify(
                    $targetId,
                    'You Have Been Assigned as Administrative Officer',
                    'The gym owner has assigned you the Administrative Officer role. Please log out and log back in to access the new dashboard.',
                    'success',
                    'home/index'
                );
                $msg = htmlspecialchars($targetUser['fullname']) . ' has been assigned as Administrative Officer.';
                if ($existingOfficer) {
                    $msg .= ' (Previous officer ' . htmlspecialchars($existingOfficer['fullname']) . ' was replaced).';
                }
                $_SESSION['assign_success'] = $msg;
            } else {
                $_SESSION['assign_error'] = 'Failed to assign role. Please try again.';
            }
        } elseif ($action === 'revoke') {
            $ok = $userModel->updateRole($targetId, 'fitness_enthusiast');
            if ($ok) {
                $this->notify(
                    $targetId,
                    'Administrative Officer Role Revoked',
                    'Your Administrative Officer role has been revoked.',
                    'warning',
                    'home/index'
                );
                $_SESSION['assign_success'] = htmlspecialchars($targetUser['fullname']) . "'s Administrative Officer role has been revoked.";
            } else {
                $_SESSION['assign_error'] = 'Failed to revoke role. Please try again.';
            }
        } else {
            $_SESSION['assign_error'] = 'Unknown action.';
        }

        $this->redirect('gymowner/users');
    }

    /**
     * Assign or revoke the Marketing Officer role.
     * POST  index.php?r=gymowner/assignmarketingofficer
     *   id     — target user ID
     *   action — 'assign' | 'revoke'
     */
    public function assignmarketingofficerAction(): void
    {
        $owner = $this->requireGymOwner();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('gymowner/users');
        }

        $targetId = (int)($_POST['id'] ?? 0);
        $action   = $_POST['action'] ?? '';

        if ($targetId <= 0) {
            $_SESSION['assign_error'] = 'Invalid user ID.';
            $this->redirect('gymowner/users');
        }

        $userModel  = new User();
        $targetUser = $userModel->findById($targetId);

        if (!$targetUser) {
            $_SESSION['assign_error'] = 'User not found.';
            $this->redirect('gymowner/users');
        }

        // Safety: never touch admin or another gym owner
        if (in_array($targetUser['role'], ['admin', 'gym_owner'], true)) {
            $_SESSION['assign_error'] = 'Cannot modify an admin or gym owner account.';
            $this->redirect('gymowner/users');
        }

        $moModel = new \App\Models\MarketingOfficer();

        if ($action === 'assign') {
            $ok = $userModel->updateRole($targetId, 'marketing_officer');
            if ($ok) {
                if ($moModel->tableExists()) {
                    $moModel->create($targetId, (int)$owner['id'], (int)$owner['id']);
                }
                $this->notify(
                    $targetId,
                    'You Have Been Assigned as Marketing Officer',
                    'The gym owner has assigned you the Marketing Officer role. Please log out and log back in to access the new dashboard.',
                    'success',
                    'home/index'
                );
                $_SESSION['assign_success'] = htmlspecialchars($targetUser['fullname']) . ' has been assigned as Marketing Officer.';
            } else {
                $_SESSION['assign_error'] = 'Failed to assign role. Please try again.';
            }
        } elseif ($action === 'revoke') {
            $ok = $userModel->updateRole($targetId, 'fitness_enthusiast');
            if ($ok) {
                if ($moModel->tableExists()) {
                    $moModel->deactivate($targetId, (int)$owner['id']);
                }
                $this->notify(
                    $targetId,
                    'Marketing Officer Role Revoked',
                    'Your Marketing Officer role has been revoked.',
                    'warning',
                    'home/index'
                );
                $_SESSION['assign_success'] = htmlspecialchars($targetUser['fullname']) . "'s Marketing Officer role has been revoked.";
            } else {
                $_SESSION['assign_error'] = 'Failed to revoke role. Please try again.';
            }
        } else {
            $_SESSION['assign_error'] = 'Unknown action.';
        }

        $this->redirect('gymowner/users');
    }

    /**
     * Assign or revoke the Fitness Trainer role.
     * POST  index.php?r=gymowner/assignfitnesstrainer
     *   id     — target user ID
     *   action — 'assign' | 'revoke'
     */
    public function assignfitnesstrainerAction(): void
    {
        $owner = $this->requireGymOwner();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('gymowner/users');
        }

        $targetId = (int)($_POST['id'] ?? 0);
        $action   = $_POST['action'] ?? '';

        if ($targetId <= 0) {
            $_SESSION['assign_error'] = 'Invalid user ID.';
            $this->redirect('gymowner/users');
        }

        $userModel  = new User();
        $targetUser = $userModel->findById($targetId);

        if (!$targetUser) {
            $_SESSION['assign_error'] = 'User not found.';
            $this->redirect('gymowner/users');
        }

        if (in_array($targetUser['role'], ['admin', 'gym_owner'], true)) {
            $_SESSION['assign_error'] = 'Cannot modify an admin or gym owner account.';
            $this->redirect('gymowner/users');
        }

        if ($action === 'assign') {
            $ok = $userModel->updateRole($targetId, 'trainer');
            if ($ok) {
                // Ensure employee record exists
                $empModel = new Employee();
                if (!$empModel->findByUserId($targetId)) {
                    $empModel->create($targetId, 'trainer', (int)$owner['id']);
                }
                $this->notify(
                    $targetId,
                    'Assigned as Fitness Trainer',
                    'You have been assigned as a Fitness Trainer. Logout and login again to see your trainer dashboard.',
                    'success',
                    'home/index'
                );
                $_SESSION['assign_success'] = htmlspecialchars($targetUser['fullname']) . ' has been assigned as Fitness Trainer.';
            } else {
                $_SESSION['assign_error'] = 'Failed to assign role.';
            }
        } elseif ($action === 'revoke') {
            $ok = $userModel->updateRole($targetId, 'fitness_enthusiast');
            if ($ok) {
                $this->notify(
                    $targetId,
                    'Fitness Trainer Role Revoked',
                    'Your Fitness Trainer role has been revoked.',
                    'warning',
                    'home/index'
                );
                $_SESSION['assign_success'] = htmlspecialchars($targetUser['fullname']) . "'s Fitness Trainer role has been revoked.";
            } else {
                $_SESSION['assign_error'] = 'Failed to revoke role.';
            }
        }

        $this->redirect('gymowner/users');
    }

    /**
     * Assign or revoke the Maintenance Officer role.
     * POST  index.php?r=gymowner/assignmaintenanceofficer
     *   id     — target user ID
     *   action — 'assign' | 'revoke'
     */
    public function assignmaintenanceofficerAction(): void
    {
        $owner = $this->requireGymOwner();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('gymowner/users');
        }

        $targetId = (int)($_POST['id'] ?? 0);
        $action   = $_POST['action'] ?? '';

        if ($targetId <= 0) {
            $_SESSION['assign_error'] = 'Invalid user ID.';
            $this->redirect('gymowner/users');
        }

        $userModel  = new User();
        $targetUser = $userModel->findById($targetId);

        if (!$targetUser) {
            $_SESSION['assign_error'] = 'User not found.';
            $this->redirect('gymowner/users');
        }

        if (in_array($targetUser['role'], ['admin', 'gym_owner'], true)) {
            $_SESSION['assign_error'] = 'Cannot modify an admin or gym owner account.';
            $this->redirect('gymowner/users');
        }

        if ($action === 'assign') {
            $ok = $userModel->updateRole($targetId, 'maintenance');
            if ($ok) {
                // Ensure employee record exists
                $empModel = new Employee();
                if (!$empModel->findByUserId($targetId)) {
                    $empModel->create($targetId, 'maintenance', (int)$owner['id']);
                }
                // Ensure MaintenanceStaff entry exists
                $mStaffModel = new \App\Models\MaintenanceStaff();
                if ($mStaffModel->tableExists()) {
                    $mStaffModel->create($targetId, (int)$owner['id'], (int)$owner['id']);
                }
                
                $this->notify(
                    $targetId,
                    'Assigned as Maintenance Officer',
                    'You have been assigned as a Maintenance Officer. Logout and login again to see your dashboard.',
                    'success',
                    'home/index'
                );
                $_SESSION['assign_success'] = htmlspecialchars($targetUser['fullname']) . ' has been assigned as Maintenance Officer.';
            } else {
                $_SESSION['assign_error'] = 'Failed to assign role.';
            }
        } elseif ($action === 'revoke') {
            $ok = $userModel->updateRole($targetId, 'fitness_enthusiast');
            if ($ok) {
                $this->notify(
                    $targetId,
                    'Maintenance Officer Role Revoked',
                    'Your Maintenance Officer role has been revoked.',
                    'warning',
                    'home/index'
                );
                $_SESSION['assign_success'] = htmlspecialchars($targetUser['fullname']) . "'s Maintenance Officer role has been revoked.";
            } else {
                $_SESSION['assign_error'] = 'Failed to revoke role.';
            }
        }

        $this->redirect('gymowner/users');
    }

    /** Manage Fitness Trainer Pricing Packages */
    public function trainerPricingAction(): void
    {
        $user = $this->requireGymOwner();
        
        $packageModel = new \App\Models\FitnessTrainerPackage();
        $error = '';
        $success = '';

        // Handle form submissions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'add_package') {
                $packageData = [
                    'package_name' => trim((string)($_POST['package_name'] ?? '')),
                    'training_type' => $_POST['training_type'] ?? 'all',
                    'session_count' => (int)($_POST['session_count'] ?? 0),
                    'price' => round((float)($_POST['price'] ?? 0), 2),
                    'duration_minutes' => (int)($_POST['duration_minutes'] ?? 60),
                    'description' => trim((string)($_POST['description'] ?? '')),
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                ];

                if (empty($packageData['package_name'])) {
                    $error = 'Package name is required.';
                } elseif ($packageData['session_count'] <= 0) {
                    $error = 'Session count must be greater than 0.';
                } elseif ($packageData['price'] <= 0) {
                    $error = 'Price must be greater than 0.';
                } else {
                    $packageId = $packageModel->create((int)$user['id'], $packageData);
                    if ($packageId > 0) {
                        $success = 'Training package added successfully!';
                    } else {
                        $error = 'Failed to add package.';
                    }
                }
            } elseif ($action === 'update_package') {
                $packageId = (int)($_POST['package_id'] ?? 0);
                $packageData = [
                    'package_name' => trim((string)($_POST['package_name'] ?? '')),
                    'training_type' => $_POST['training_type'] ?? 'all',
                    'session_count' => (int)($_POST['session_count'] ?? 0),
                    'price' => round((float)($_POST['price'] ?? 0), 2),
                    'duration_minutes' => (int)($_POST['duration_minutes'] ?? 60),
                    'description' => trim((string)($_POST['description'] ?? '')),
                    'is_active' => isset($_POST['is_active']) ? 1 : 0
                ];

                if ($packageModel->update($packageId, (int)$user['id'], $packageData)) {
                    $success = 'Package updated successfully!';
                } else {
                    $error = 'Failed to update package.';
                }
            } elseif ($action === 'toggle_active') {
                $packageId = (int)($_POST['package_id'] ?? 0);
                if ($packageModel->toggleActive($packageId, (int)$user['id'])) {
                    $success = 'Package status updated!';
                } else {
                    $error = 'Failed to update status.';
                }
            } elseif ($action === 'delete_package') {
                $packageId = (int)($_POST['package_id'] ?? 0);
                if ($packageModel->delete($packageId, (int)$user['id'])) {
                    $success = 'Package deleted successfully!';
                } else {
                    $error = 'Failed to delete package.';
                }
            }
        }

        // Get all packages for this gym owner
        $packages = $packageModel->findByGymOwner((int)$user['id']);

        $this->view('gymowner/trainer_pricing', [
            'user' => $user,
            'packages' => $packages,
            'error' => $error,
            'success' => $success
        ]);
    }

    /** Maintenance Reports — view and verify inspection reports */
    public function maintenancereportsAction(): void
    {
        $user = $this->requireGymOwner();
        $gymOwnerId = (int)$user['id'];

        $filter = $_GET['filter'] ?? 'all';

        $inspectionModel = new EquipmentInspection();
        $reports   = [];
        $tableReady = $inspectionModel->tableExists();

        if ($tableReady) {
            $reports = $inspectionModel->findByGymOwner($gymOwnerId, $filter);
        }

        $success = '';
        $error   = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verify') {
            $rid = (int)($_POST['report_id'] ?? 0);
            if ($rid > 0 && $tableReady) {
                $report = $inspectionModel->findById($rid);
                if ($report && $report['status'] === 'submitted') {
                    $inspectionModel->verify($rid, $gymOwnerId);
                    $n = new Notification();
                    if ($n->tableExists()) {
                        $n->create(
                            (int)$report['maintenance_id'],
                            'Inspection Report Verified',
                            'Your inspection report for ' . $report['equipment_name'] . ' has been verified by ' . $user['fullname'],
                            'success',
                            'maintenance/reportdetail&id=' . $rid
                        );
                    }
                    $success = 'Report #' . $rid . ' has been verified.';
                    $reports = $inspectionModel->findByGymOwner($gymOwnerId, $filter);
                } else {
                    $error = 'Report not found or already verified.';
                }
            }
        }

        $this->view('gymowner/maintenance-reports', [
            'user'       => $user,
            'reports'    => $reports,
            'filter'     => $filter,
            'tableReady' => $tableReady,
            'success'    => $success,
            'error'      => $error,
        ]);
    }

    /** Purchase Requests — view, approve, reject and manage equipment parts purchase requests */
    public function purchaserequestsAction(): void
    {
        $user = $this->requireGymOwner();
        $gymOwnerId = (int)$user['id'];

        $filter = $_GET['filter'] ?? 'all';

        $prModel     = new PurchaseRequest();
        $prItemModel = new PurchaseRequestItem();
        $tableReady  = $prModel->tableExists();

        $success = '';
        $error   = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tableReady) {
            $action     = $_POST['action'] ?? '';
            $requestId  = (int)($_POST['request_id'] ?? 0);
            $adminNotes = trim((string)($_POST['admin_notes'] ?? ''));

            if ($requestId > 0) {
                $req = $prModel->findById($requestId);
                if ($req && (int)$req['gym_id'] === $gymOwnerId) {
                    $existingItems = $prItemModel->findByRequestId($requestId);

                    if ($action === 'approve') {
                        $approvedItemIds = array_map('intval', (array)($_POST['approved_items'] ?? []));
                        $itemReasons     = (array)($_POST['item_rejection_reasons'] ?? []);

                        $approvedCount  = 0;
                        $approvedAmount = 0.0;
                        $totalItemCount = count($existingItems);

                        foreach ($existingItems as $itm) {
                            $itmId = (int)$itm['id'];
                            $sub   = (float)($itm['quantity'] ?? 1) * (float)($itm['unit_price'] ?? 0);

                            if (in_array($itmId, $approvedItemIds, true)) {
                                $prItemModel->updateItemDecision($itmId, 'approved', null, 1);
                                $approvedCount++;
                                $approvedAmount += $sub;
                            } else {
                                $reason = trim((string)($itemReasons[$itmId] ?? ''));
                                if ($reason === '') {
                                    $reason = $adminNotes ?: 'Not approved for this purchase cycle';
                                }
                                $prItemModel->updateItemDecision($itmId, 'rejected', $reason, 0);
                            }
                        }

                        if ($approvedCount > 0) {
                            $prModel->approve($requestId, $gymOwnerId, $adminNotes ?: null);
                            $msg = 'Your purchase request for ' . $req['equipment_name'] . ' was approved (' . $approvedCount . '/' . $totalItemCount . ' items approved, ₱' . number_format($approvedAmount, 2) . ') by ' . $user['fullname'] . ($adminNotes ? ': "' . $adminNotes . '"' : '');
                            $this->notify((int)$req['requested_by'], 'Purchase Request Decision', $msg, 'success', 'maintenance/purchaserequests');
                            $success = 'Purchase Request #' . $requestId . ' processed: ' . $approvedCount . ' item(s) approved (₱' . number_format($approvedAmount, 2) . ').';
                        } else {
                            $prModel->reject($requestId, $gymOwnerId, $adminNotes ?: null);
                            $msg = 'Your purchase request for ' . $req['equipment_name'] . ' was not approved by ' . $user['fullname'] . ($adminNotes ? ': "' . $adminNotes . '"' : '');
                            $this->notify((int)$req['requested_by'], 'Purchase Request Rejected', $msg, 'danger', 'maintenance/purchaserequests');
                            $success = 'All items in Purchase Request #' . $requestId . ' were rejected.';
                        }
                    } elseif ($action === 'reject') {
                        $itemReasons = (array)($_POST['item_rejection_reasons'] ?? []);
                        foreach ($existingItems as $itm) {
                            $itmId = (int)$itm['id'];
                            $reason = trim((string)($itemReasons[$itmId] ?? ''));
                            if ($reason === '') {
                                $reason = $adminNotes ?: 'Request rejected by owner';
                            }
                            $prItemModel->updateItemDecision($itmId, 'rejected', $reason, 0);
                        }
                        $prModel->reject($requestId, $gymOwnerId, $adminNotes ?: null);
                        $this->notify(
                            (int)$req['requested_by'],
                            'Purchase Request Rejected',
                            'Your purchase request for ' . $req['equipment_name'] . ' was rejected by ' . $user['fullname'] . ($adminNotes ? ': "' . $adminNotes . '"' : ''),
                            'danger',
                            'maintenance/purchaserequests'
                        );
                        $success = 'Purchase Request #' . $requestId . ' has been rejected.';
                    } elseif ($action === 'mark_purchased') {
                        $prModel->markPurchased($requestId, $adminNotes ?: null);
                        $this->notify(
                            (int)$req['requested_by'],
                            'Purchase Request Marked as Purchased',
                            'Approved items for ' . $req['equipment_name'] . ' have been marked as purchased.',
                            'info',
                            'maintenance/purchaserequests'
                        );
                        $success = 'Purchase Request #' . $requestId . ' has been marked as purchased.';
                    }
                } else {
                    $error = 'Purchase request not found.';
                }
            }
        }

        $allRequests = $tableReady ? $prModel->findByGymOwner($gymOwnerId, 'all') : [];
        $requests    = $tableReady ? $prModel->findByGymOwner($gymOwnerId, $filter) : [];

        // Preload items for modal view
        $itemsByRequest = [];
        if ($tableReady) {
            foreach ($requests as $r) {
                $itemsByRequest[$r['id']] = $prItemModel->findByRequestId((int)$r['id']);
            }
        }

        // Compute stat counters
        $totalCount     = count($allRequests);
        $pendingCount   = count(array_filter($allRequests, fn($r) => $r['status'] === 'pending'));
        $approvedCount  = count(array_filter($allRequests, fn($r) => $r['status'] === 'approved'));
        $rejectedCount  = count(array_filter($allRequests, fn($r) => $r['status'] === 'rejected'));
        $purchasedCount = count(array_filter($allRequests, fn($r) => $r['status'] === 'purchased'));
        $totalValue     = array_reduce($allRequests, fn($sum, $r) => $sum + (float)$r['total_amount'], 0.0);

        $this->view('gymowner/purchase-requests', [
            'user'           => $user,
            'requests'       => $requests,
            'itemsByRequest' => $itemsByRequest,
            'filter'         => $filter,
            'tableReady'     => $tableReady,
            'totalCount'     => $totalCount,
            'pendingCount'   => $pendingCount,
            'approvedCount'  => $approvedCount,
            'rejectedCount'  => $rejectedCount,
            'purchasedCount' => $purchasedCount,
            'totalValue'     => $totalValue,
            'success'        => $success,
            'error'          => $error,
        ]);
    }

    /**
     * Gym Owner: Manage Gym Profile (Basic Info, Opening Hours, Equipment, Services, Plans, Training Pricing, Logo)
     */
    public function managegymAction(): void
    {
        $user = $this->requireGymOwner();
        $gymOwnerId = (int)$user['id'];

        $docModel = new LegalDocument();
        $gym = $docModel->findByUserId($gymOwnerId);

        if (!$gym) {
            $_SESSION['flash_error'] = 'Gym record not found. Please submit your gym registration documents first.';
            $this->redirect('gymowner/apply');
        }

        $error   = '';
        $success = '';

        $equipModel   = new \App\Models\GymEquipment();
        $svcModel     = new GymService();
        $planModel    = new MembershipPlan();
        $packageModel = new \App\Models\FitnessTrainerPackage();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? 'update_profile';

            if ($action === 'update_profile') {
                $gymName          = trim((string)($_POST['gym_name'] ?? ''));
                $gymDesc          = trim((string)($_POST['gym_description'] ?? ''));
                $streetAddress    = trim((string)($_POST['street_address'] ?? ''));
                $barangay         = trim((string)($_POST['barangay'] ?? ''));
                $cityMunicipality = trim((string)($_POST['city_municipality'] ?? ''));
                $province         = trim((string)($_POST['province'] ?? ''));

                if ($gymName === '') {
                    $error = 'Gym Name is required.';
                } else {
                    // Logo upload
                    $gymLogo = null;
                    if (!empty($_FILES['gym_logo']['tmp_name'])) {
                        $uploadDir = BASE_PATH . '/public/uploads/legal_documents/';
                        if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }
                        $ext = strtolower(pathinfo($_FILES['gym_logo']['name'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                            $logoFilename = 'gym_logo_' . $gymOwnerId . '_' . time() . '.' . $ext;
                            if (move_uploaded_file($_FILES['gym_logo']['tmp_name'], $uploadDir . $logoFilename)) {
                                $gymLogo = 'uploads/legal_documents/' . $logoFilename;
                            }
                        }
                    }

                    // Opening hours processing
                    $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                    $openingHours = [];
                    foreach ($days as $day) {
                        $isClosed = isset($_POST['oh_closed_' . $day]) ? 1 : 0;
                        $openTime  = trim((string)($_POST['oh_open_'  . $day] ?? '06:00'));
                        $closeTime = trim((string)($_POST['oh_close_' . $day] ?? '22:00'));
                        $openingHours[] = [
                            'day'        => $day,
                            'open_time'  => $openTime,
                            'close_time' => $closeTime,
                            'is_closed'  => $isClosed,
                        ];
                    }
                    $ohJson = json_encode($openingHours, JSON_UNESCAPED_UNICODE);

                    $updated = $docModel->updateGymProfile(
                        (int)$gym['id'],
                        $gymName,
                        $gymDesc,
                        $streetAddress,
                        $province,
                        $cityMunicipality,
                        $barangay,
                        $ohJson,
                        $gymLogo
                    );

                    if ($updated) {
                        $success = 'Gym profile and opening hours updated successfully!';
                        $gym = $docModel->findByUserId($gymOwnerId);
                    } else {
                        $error = 'Failed to update gym profile.';
                    }
                }
            } elseif ($action === 'add_equipment') {
                $name        = trim((string)($_POST['name'] ?? ''));
                $category    = trim((string)($_POST['category'] ?? 'General'));
                $brand       = trim((string)($_POST['brand'] ?? ''));
                $dimensions  = trim((string)($_POST['dimensions'] ?? ''));
                $weightKg    = !empty($_POST['weight_kg']) ? (float)$_POST['weight_kg'] : null;
                $quantity    = max(1, (int)($_POST['quantity'] ?? 1));
                $price       = (float)($_POST['price'] ?? 0);
                $description = trim((string)($_POST['description'] ?? ''));

                if ($name === '') {
                    $error = 'Equipment name is required.';
                } else {
                    $equipModel->listEquipment($gymOwnerId, $name, $category, $brand, $dimensions, $weightKg, $quantity, $price, $description, null);
                    $success = 'Equipment added successfully!';
                }
            } elseif ($action === 'update_equipment') {
                $eqId        = (int)($_POST['equipment_id'] ?? 0);
                $name        = trim((string)($_POST['name'] ?? ''));
                $category    = trim((string)($_POST['category'] ?? 'General'));
                $brand       = trim((string)($_POST['brand'] ?? ''));
                $dimensions  = trim((string)($_POST['dimensions'] ?? ''));
                $weightKg    = !empty($_POST['weight_kg']) ? (float)$_POST['weight_kg'] : null;
                $quantity    = max(1, (int)($_POST['quantity'] ?? 1));
                $price       = (float)($_POST['price'] ?? 0);
                $description = trim((string)($_POST['description'] ?? ''));

                if ($eqId > 0 && $name !== '') {
                    $equipModel->updateEquipment($eqId, $name, $category, $brand, $dimensions, $weightKg, $quantity, $price, $description);
                    $success = 'Equipment updated successfully!';
                } else {
                    $error = 'Invalid equipment details.';
                }
            } elseif ($action === 'delete_equipment') {
                $eqId = (int)($_POST['equipment_id'] ?? 0);
                if ($eqId > 0) {
                    $equipModel->deactivate($eqId);
                    $success = 'Equipment removed.';
                }
            } elseif ($action === 'add_service') {
                $name            = trim((string)($_POST['svc_name'] ?? ''));
                $desc            = trim((string)($_POST['svc_desc'] ?? ''));
                $memberPrice     = (float)($_POST['svc_member_price'] ?? 0);
                $nonMemberPrice  = (float)($_POST['svc_nonmember_price'] ?? 0);

                if ($name === '' || $memberPrice <= 0) {
                    $error = 'Service name and member price are required.';
                } else {
                    $svcModel->create($gymOwnerId, $name, $desc, $memberPrice, $nonMemberPrice);
                    $success = 'Service added successfully!';
                }
            } elseif ($action === 'update_service') {
                $sid            = (int)($_POST['svc_id'] ?? 0);
                $name           = trim((string)($_POST['svc_name'] ?? ''));
                $desc           = trim((string)($_POST['svc_desc'] ?? ''));
                $memberPrice    = (float)($_POST['svc_member_price'] ?? 0);
                $nonMemberPrice = (float)($_POST['svc_nonmember_price'] ?? 0);

                if ($sid > 0 && $name !== '' && $memberPrice > 0) {
                    $svcModel->update($sid, $name, $desc, $memberPrice, $nonMemberPrice);
                    $success = 'Service updated successfully!';
                } else {
                    $error = 'Invalid service data.';
                }
            } elseif ($action === 'delete_service') {
                $sid = (int)($_POST['svc_id'] ?? 0);
                if ($sid > 0) {
                    $svcModel->delete($sid);
                    $success = 'Service deleted.';
                }
            } elseif ($action === 'add_plan') {
                $name     = trim((string)($_POST['plan_name'] ?? ''));
                $desc     = trim((string)($_POST['plan_desc'] ?? ''));
                $price    = round((float)($_POST['plan_price'] ?? 0), 2);
                $duration = (int)($_POST['plan_duration'] ?? 30);

                if ($name === '' || $price <= 0) {
                    $error = 'Plan name and price are required.';
                } else {
                    $planModel->create($gymOwnerId, $name, $desc, $price, $duration);
                    $success = 'Membership plan created!';
                }
            } elseif ($action === 'update_plan') {
                $pid      = (int)($_POST['plan_id'] ?? 0);
                $name     = trim((string)($_POST['plan_name'] ?? ''));
                $desc     = trim((string)($_POST['plan_desc'] ?? ''));
                $price    = round((float)($_POST['plan_price'] ?? 0), 2);
                $duration = (int)($_POST['plan_duration'] ?? 30);

                if ($pid > 0 && $name !== '' && $price > 0) {
                    $planModel->update($pid, $name, $desc, $price, $duration);
                    $success = 'Membership plan updated!';
                } else {
                    $error = 'Invalid plan data.';
                }
            } elseif ($action === 'delete_plan') {
                $pid = (int)($_POST['plan_id'] ?? 0);
                if ($pid > 0) {
                    $planModel->delete($pid);
                    $success = 'Membership plan deleted.';
                }
            } elseif ($action === 'add_package') {
                $pkgData = [
                    'package_name'     => trim((string)($_POST['package_name'] ?? '')),
                    'training_type'    => $_POST['training_type'] ?? 'all',
                    'session_count'    => (int)($_POST['session_count'] ?? 1),
                    'price'            => round((float)($_POST['price'] ?? 0), 2),
                    'duration_minutes' => (int)($_POST['duration_minutes'] ?? 60),
                    'description'      => trim((string)($_POST['description'] ?? '')),
                    'is_active'        => 1
                ];
                if ($pkgData['package_name'] === '' || $pkgData['price'] <= 0) {
                    $error = 'Package name and price are required.';
                } else {
                    $packageModel->create($gymOwnerId, $pkgData);
                    $success = 'Training package added!';
                }
            } elseif ($action === 'update_package') {
                $pkgId   = (int)($_POST['package_id'] ?? 0);
                $pkgData = [
                    'package_name'     => trim((string)($_POST['package_name'] ?? '')),
                    'training_type'    => $_POST['training_type'] ?? 'all',
                    'session_count'    => (int)($_POST['session_count'] ?? 1),
                    'price'            => round((float)($_POST['price'] ?? 0), 2),
                    'duration_minutes' => (int)($_POST['duration_minutes'] ?? 60),
                    'description'      => trim((string)($_POST['description'] ?? '')),
                    'is_active'        => isset($_POST['is_active']) ? 1 : 0
                ];
                if ($pkgId > 0 && $pkgData['package_name'] !== '') {
                    $packageModel->update($pkgId, $gymOwnerId, $pkgData);
                    $success = 'Training package updated!';
                } else {
                    $error = 'Invalid package data.';
                }
            } elseif ($action === 'delete_package') {
                $pkgId = (int)($_POST['package_id'] ?? 0);
                if ($pkgId > 0) {
                    $packageModel->delete($pkgId, $gymOwnerId);
                    $success = 'Training package deleted.';
                }
            }
        }

        // Pre-fill data
        $equipment        = $equipModel->findByOwnerId($gymOwnerId);
        $services         = $svcModel->tableExists() ? $svcModel->findByOwnerId($gymOwnerId) : [];
        $plans            = $planModel->tableExists() ? $planModel->findByOwnerId($gymOwnerId) : [];
        $trainingPackages = $packageModel->findByGymOwner($gymOwnerId);

        // Decode opening hours
        $openingHours = [];
        if (!empty($gym['opening_hours'])) {
            $decoded = json_decode($gym['opening_hours'], true);
            if (is_array($decoded)) {
                $openingHours = $decoded;
            }
        }

        $this->view('gymowner/manage_gym', [
            'user'             => $user,
            'gym'              => $gym,
            'equipment'        => $equipment,
            'services'         => $services,
            'plans'            => $plans,
            'trainingPackages' => $trainingPackages,
            'openingHours'     => $openingHours,
            'error'            => $error,
            'success'          => $success,
        ]);
    }
}
