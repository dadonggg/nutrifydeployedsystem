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
use App\Models\Notification;
use App\Models\LoginActivity;

final class AdminController extends Controller
{
    private function requireAdmin(): array
    {
        if (!isset($_SESSION['user_id'])) { $this->redirect('auth/login'); }
        $user = (new User())->findById((int)$_SESSION['user_id']);
        if (!$user || $user['role'] !== 'admin') { $this->redirect('home/index'); }
        return $user;
    }

    private function notify(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): int
    {
        $notif = new Notification();
        if ($notif->tableExists()) {
            return $notif->create($userId, $title, $message, $type, $link);
        }
        return 0;
    }

    /** List all legal document applications */
    public function legalreviewsAction(): void
    {
        $user = $this->requireAdmin();
        $docs = (new LegalDocument())->findAllPending();
        $this->view('admin/legal_reviews', ['user' => $user, 'docs' => $docs]);
    }

    /** Review a single legal document application */
    public function reviewlegalAction(): void
    {
        // Add cache-control headers to prevent stale data
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $user = $this->requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $docModel = new LegalDocument();
        $doc = $docModel->findById($id);
        if (!$doc) { $this->redirect('admin/legalreviews'); }

        $error = ''; $success = '';
        $docLabels = [
            'cert_registration' => 'Certificate of Registration',
            'mayors_permit' => "Mayor's Permit",
            'business_name_cert' => 'Business Name Certificate',
            'fire_safety_cert' => 'Fire Safety Certificate',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $feedback = trim((string)($_POST['feedback'] ?? ''));

            if ($action === 'verify') {
                // Check if there are any flagged documents before allowing verify all
                $docFields = ['cert_registration', 'mayors_permit', 'business_name_cert', 'fire_safety_cert'];
                $flaggedDocs = [];
                
                foreach ($docFields as $field) {
                    $statusField = $field . '_status';
                    if (($doc[$statusField] ?? 'pending') === 'flagged') {
                        $flaggedDocs[] = $docLabels[$field];
                    }
                }
                
                if (!empty($flaggedDocs)) {
                    $error = 'Cannot verify all documents. The following documents are flagged and must be resolved first: ' . implode(', ', $flaggedDocs) . '. Please approve or reset these documents before using "Verify All".';
                } else {
                    $verifySuccess = $docModel->verifyAll($id, $feedback);
                    if (!$verifySuccess) {
                        $error = 'Failed to verify documents. Please try again.';
                    } else {
                        $targetUserId = (int)$doc['user_id'];

                        // === TRIPLE-LAYER role promotion ===
                        // Layer 1: via User model method
                        (new User())->updateRole($targetUserId, 'gym_owner');

                        // Layer 2: direct PDO, no wrapper
                        try {
                            $pdo = \App\Core\Database::pdo();
                            $pdo->exec("UPDATE users SET role = 'gym_owner' WHERE id = " . (int)$targetUserId);
                        } catch (\Throwable $ex) {
                            error_log('[VerifyAll] Layer-2 PDO exec failed for user ' . $targetUserId . ': ' . $ex->getMessage());
                        }

                        // Layer 3: prepared statement as ultimate fallback
                        try {
                            $pdo2 = \App\Core\Database::pdo();
                            $st = $pdo2->prepare('UPDATE users SET role = ? WHERE id = ?');
                            $st->execute(['gym_owner', $targetUserId]);
                            error_log('[VerifyAll] Layer-3 prepared, rowCount=' . $st->rowCount() . ', userId=' . $targetUserId);
                        } catch (\Throwable $ex2) {
                            error_log('[VerifyAll] Layer-3 prepared failed for user ' . $targetUserId . ': ' . $ex2->getMessage());
                        }

                        // Confirm final DB state
                        $confirmedUser = (new User())->findById($targetUserId);
                        $roleOk = ($confirmedUser && $confirmedUser['role'] === 'gym_owner');
                        error_log('[VerifyAll] Final role check for userId=' . $targetUserId . ': role=' . ($confirmedUser['role'] ?? 'NOT FOUND') . ', roleOk=' . ($roleOk ? 'YES' : 'NO'));

                        $this->notify($targetUserId,
                            'Congratulations! You are now a Gym Owner',
                            'All your documents have been verified. Your role has been updated. Please go to your dashboard to access Gym Owner features.',
                            'success', 'home/index');

                        if ($roleOk) {
                            $success = '✅ Documents verified and user successfully converted to Gym Owner! Role confirmed in database.';
                        } else {
                            $success = '⚠️ Documents verified, but role update could not be confirmed. Check error logs. Current role: ' . ($confirmedUser['role'] ?? 'unknown');
                        }
                        $this->logAdminAction((int)$user['id'], 'verify_all_documents', $id, $roleOk ? 'converted_to_gym_owner' : 'role_update_unconfirmed');
                    }
                }
            } elseif ($action === 'convert') {
                $targetUserId = (int)$doc['user_id'];
                (new User())->updateRole($targetUserId, 'gym_owner');
                
                try {
                    $pdo = \App\Core\Database::pdo();
                    $stmt = $pdo->prepare("UPDATE users SET role = 'gym_owner' WHERE id = ?");
                    $stmt->execute([$targetUserId]);
                } catch (\Throwable $ex) {
                    error_log('Direct role update (convert) failed: ' . $ex->getMessage());
                }

                // Keep legal_documents in sync
                $docModel->verifyAll($id, 'Approved and converted to Gym Owner by Administrator');

                $updatedUser = (new User())->findById($targetUserId);
                $roleOk = ($updatedUser && $updatedUser['role'] === 'gym_owner');

                if ($roleOk) {
                    $this->notify($targetUserId, 'Congratulations! You are now a Gym Owner',
                        'You have been converted to a Gym Owner. You can now access the Gym Owner dashboard.',
                        'success', 'home/index');
                    $success = '✅ User successfully converted to Gym Owner! Role confirmed in database.';
                    $this->logAdminAction((int)$user['id'], 'convert_to_gym_owner', $id, 'user_' . $targetUserId);
                } else {
                    $error = 'Failed to convert user role. Please check database permissions and try again.';
                    $this->logAdminAction((int)$user['id'], 'convert_to_gym_owner_failed', $id, 'user_' . $targetUserId);
                }
            } elseif ($action === 'resubmit') {
                if ($feedback === '') { $error = 'Feedback is required when requesting resubmission.'; }
                else {
                    $docModel->updateStatus($id, 'resubmit', $feedback);
                    // Flag unapproved documents so applicant sees upload inputs immediately
                    $docModel->flagUnapprovedDocs($id, $feedback);
                    $this->notify((int)$doc['user_id'], 'Resubmission Required',
                        $feedback, 'warning', 'gymowner/apply');
                    $success = 'Resubmission requested and documents flagged for applicant upload.';
                }
            } elseif ($action === 'reject') {
                if ($feedback === '') { $error = 'Feedback is required when rejecting.'; }
                else {
                    $docModel->updateStatus($id, 'rejected', $feedback);
                    $this->notify((int)$doc['user_id'], 'Application Rejected',
                        $feedback, 'danger', 'gymowner/apply');
                    $success = 'Application rejected.';
                }
            } elseif ($action === 'update_doc_status') {
                // Per-document status update with comprehensive error handling
                $docField = $_POST['doc_field'] ?? '';
                $docStatus = $_POST['doc_status'] ?? 'pending';
                $docComment = trim((string)($_POST['doc_comment'] ?? ''));
                $docChecked = !empty($_POST['doc_checked']);

                // Validate that we have a valid document field
                if (in_array($docField, array_keys($docLabels), true)) {
                    try {
                        // Update the document status first
                        $updateSuccess = $docModel->updateDocStatus($id, $docField, $docStatus, $docComment, $docChecked);
                        
                        if (!$updateSuccess) {
                            $error = 'Failed to update document status. Please try again.';
                            $this->logAdminAction((int)$user['id'], 'update_doc_status_failed', $id, $docStatus);
                        } else {
                            // Recompute overall status
                            $recomputeSuccess = $docModel->recomputeOverallStatus($id);
                            
                            if (!$recomputeSuccess) {
                                $error = 'Document updated but overall status computation failed.';
                                $this->logAdminAction((int)$user['id'], 'recompute_status_failed', $id, $docStatus);
                            } else {
                                // Refresh the document data to get updated values
                                $doc = $docModel->findById($id);

                                // Send notification for per-doc status - ALWAYS send for approved or flagged
                                $label = $docLabels[$docField];
                                
                                if ($docStatus === 'approved') {
                                    $notifMessage = "Your $label has been approved.";
                                    if ($docComment !== '') {
                                        $notifMessage .= " Note: $docComment";
                                    }
                                    $notifId = $this->notify((int)$doc['user_id'], "$label Approved", $notifMessage, 'success', 'gymowner/apply');
                                    
                                    if (($doc['status'] ?? '') === 'verified') {
                                        // All docs approved — update user role to gym_owner
                                        $targetUserId = (int)$doc['user_id'];
                                        (new User())->updateRole($targetUserId, 'gym_owner');
                                        // Verify by re-fetching
                                        $updatedUser = (new User())->findById($targetUserId);
                                        if (!$updatedUser || $updatedUser['role'] !== 'gym_owner') {
                                            // Fallback direct update
                                            try {
                                                $pdo = \App\Core\Database::pdo();
                                                $stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
                                                $stmt->execute(['gym_owner', $targetUserId]);
                                            } catch (\Exception $ex) {
                                                error_log('Direct role update (approve_doc) failed: ' . $ex->getMessage());
                                            }
                                        }
                                        $this->notify($targetUserId, 'Congratulations! You are now a Gym Owner',
                                            'All your documents have been verified. You can now access the Gym Owner dashboard. Please logout and login again to see your new role.',
                                            'success', 'home/index');
                                        $success = "Document approved! All documents verified and user converted to Gym Owner.";
                                        $this->logAdminAction((int)$user['id'], 'approve_document_all_verified', $id, $docField);
                                    } else {
                                        if ($notifId > 0) {
                                            $success = "Document approved and gym owner notified.";
                                            $this->logAdminAction((int)$user['id'], 'approve_document', $id, $docField);
                                        } else {
                                            $success = "Document approved but notification failed.";
                                            $this->logAdminAction((int)$user['id'], 'approve_document_notif_failed', $id, $docField);
                                        }
                                    }
                                } elseif ($docStatus === 'flagged') {
                                    // Always send notification when flagged, even if comment is empty
                                    if ($docComment !== '') {
                                        $notifMessage = $docComment;
                                    } else {
                                        $notifMessage = "Your $label was flagged for issues. Please review and upload a corrected copy.";
                                    }
                                    $notifId = $this->notify((int)$doc['user_id'], "$label Flagged - Action Required", $notifMessage, 'danger', 'gymowner/apply');
                                    
                                    if ($notifId > 0) {
                                        $success = "Document flagged and gym owner notified.";
                                        $this->logAdminAction((int)$user['id'], 'flag_document', $id, $docField);
                                    } else {
                                        $success = "Document flagged but notification failed.";
                                        $this->logAdminAction((int)$user['id'], 'flag_document_notif_failed', $id, $docField);
                                    }
                                } else {
                                    $success = 'Document status updated.';
                                    $this->logAdminAction((int)$user['id'], 'update_doc_status', $id, $docField);
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        $error = 'An error occurred while updating the document.';
                        $this->logAdminAction((int)$user['id'], 'update_doc_status_exception', $id, $e->getMessage());
                    }
                } else {
                    $error = 'Invalid document field.';
                }
            }
            $doc = $docModel->findById($id);
        }

        $applicant = (new User())->findById((int)$doc['user_id']);
        $this->view('admin/review_legal', ['user' => $user, 'doc' => $doc, 'applicant' => $applicant, 'error' => $error, 'success' => $success]);
    }


    /**
     * Log admin actions for audit trail
     */
    private function logAdminAction(int $adminId, string $action, int $targetId, string $details): void
    {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/admin_actions.log';
        $message = sprintf(
            "[%s] Admin ID %d: %s on target ID %d - %s\n",
            date('Y-m-d H:i:s'),
            $adminId,
            $action,
            $targetId,
            $details
        );

        @error_log($message, 3, $logFile);
    }

    /** List membership applications */
    public function membershipsAction(): void
    {
        $user = $this->requireAdmin();
        $apps = (new MembershipApplication())->findAll();
        $trainers = (new Employee())->findAvailableTrainers();
        $this->view('admin/memberships', ['user' => $user, 'apps' => $apps, 'trainers' => $trainers]);
    }

    /** Review a membership application */
    public function reviewmembershipAction(): void
    {
        $user = $this->requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $appModel = new MembershipApplication();
        $app = $appModel->findById($id);
        if (!$app) { $this->redirect('admin/memberships'); }

        $error = ''; $success = '';
        $trainers = (new Employee())->findAvailableTrainers();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $feedback = trim((string)($_POST['feedback'] ?? ''));
            $trainerId = !empty($_POST['trainer_id']) ? (int)$_POST['trainer_id'] : null;

            if ($action === 'approve') {
                $paymentType = $app['payment_type'] ?? 'regular_monthly';
                $paymentAmount = (float)($app['payment_amount'] ?? 0);
                $code = GymMember::generateCode();
                (new GymMember())->create(
                    (int)$app['user_id'], $id, $code, $trainerId,
                    $paymentType, $paymentAmount
                );
                $appModel->updateStatus($id, 'approved', 'Membership approved. Code: ' . $code, (int)$user['id']);
                $this->notify((int)$app['user_id'], 'Membership Approved!',
                    'Your membership has been approved. Your code: ' . $code,
                    'success', 'membership/verifycode');
                if ($trainerId) {
                    (new Employee())->setAvailability($trainerId, false);
                }
                $success = 'Membership approved! Code: ' . $code;
            } elseif ($action === 'reject') {
                if ($feedback === '') { $error = 'Feedback is required when rejecting.'; }
                else {
                    $appModel->updateStatus($id, 'rejected', $feedback, (int)$user['id']);
                    $this->notify((int)$app['user_id'], 'Membership Rejected',
                        $feedback, 'danger', 'membership/apply');
                    $success = 'Membership application rejected.';
                }
            } elseif ($action === 'resubmit') {
                if ($feedback === '') { $error = 'Feedback is required when requesting resubmission.'; }
                else {
                    $appModel->updateStatus($id, 'resubmit', $feedback, (int)$user['id']);
                    $this->notify((int)$app['user_id'], 'Membership — Resubmission Required',
                        $feedback, 'warning', 'membership/apply');
                    $success = 'Resubmission requested.';
                }
            }
            $app = $appModel->findById($id);
        }

        $this->view('admin/review_membership', ['user' => $user, 'app' => $app, 'trainers' => $trainers, 'error' => $error, 'success' => $success]);
    }

    public function membersAction(): void
    {
        $user = $this->requireAdmin();
        $members = (new GymMember())->findAll();
        $this->view('admin/members', ['user' => $user, 'members' => $members]);
    }

    public function attendanceAction(): void
    {
        $user = $this->requireAdmin();
        $logs = (new AttendanceLog())->findAll();
        $this->view('admin/attendance', ['user' => $user, 'logs' => $logs]);
    }

    /**
     * List all users — admin can assign / revoke Administrative Officer role here.
     */
    public function usersAction(): void
    {
        $user      = $this->requireAdmin();
        $userModel = new User();
        $allUsers  = $userModel->findAllExcept('admin');

        $success = $_SESSION['assign_success'] ?? '';
        $error   = $_SESSION['assign_error']   ?? '';
        unset($_SESSION['assign_success'], $_SESSION['assign_error']);

        $this->view('admin/users', [
            'user'     => $user,
            'allUsers' => $allUsers,
            'success'  => $success,
            'error'    => $error,
        ]);
    }

    /**
     * Assign or revoke the Administrative Officer role for a given user.
     * POST  index.php?r=admin/assignofficer
     *   id     — target user ID
     *   action — 'assign' | 'revoke'
     */
    public function assignofficerAction(): void
    {
        $admin = $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('admin/users');
        }

        $targetId = (int)($_POST['id'] ?? 0);
        $action   = $_POST['action'] ?? '';

        if ($targetId <= 0) {
            $_SESSION['assign_error'] = 'Invalid user ID.';
            $this->redirect('admin/users');
        }

        $userModel  = new User();
        $targetUser = $userModel->findById($targetId);

        if (!$targetUser) {
            $_SESSION['assign_error'] = 'User not found.';
            $this->redirect('admin/users');
        }

        // Prevent demoting another admin
        if ($targetUser['role'] === 'admin') {
            $_SESSION['assign_error'] = 'Cannot modify another admin account.';
            $this->redirect('admin/users');
        }

        if ($action === 'assign') {
            $ok = $userModel->updateRole($targetId, 'administrative_officer');
            if ($ok) {
                $this->notify(
                    $targetId,
                    'You Have Been Assigned as Administrative Officer',
                    'The system administrator has assigned you the Administrative Officer role. Please log out and log back in to access the new dashboard.',
                    'success',
                    'home/index'
                );
                $_SESSION['assign_success'] = htmlspecialchars($targetUser['fullname']) . ' has been assigned as Administrative Officer.';
            } else {
                $_SESSION['assign_error'] = 'Failed to assign role. Please try again.';
            }
        } elseif ($action === 'revoke') {
            $ok = $userModel->updateRole($targetId, 'fitness_enthusiast');
            if ($ok) {
                $this->notify(
                    $targetId,
                    'Administrative Officer Role Revoked',
                    'Your Administrative Officer role has been revoked by the system administrator.',
                    'warning',
                    'home/index'
                );
                $_SESSION['assign_success'] = htmlspecialchars($targetUser['fullname']) . '\'s Administrative Officer role has been revoked.';
            } else {
                $_SESSION['assign_error'] = 'Failed to revoke role. Please try again.';
            }
        } else {
            $_SESSION['assign_error'] = 'Unknown action.';
        }

        $this->redirect('admin/users');
    }

    /**
     * View login/logout activities
     * Security Feature: Logging and Monitoring
     */
    public function loginactivitiesAction(): void
    {
        $user = $this->requireAdmin();
        $loginActivityModel = new LoginActivity();
        
        // Check if table exists
        if (!$loginActivityModel->tableExists()) {
            $error = 'Login activity tracking is not set up. Please run the SQL migration: sql/create_login_activity_table.sql';
            $this->view('admin/login_activities', [
                'user' => $user,
                'activities' => [],
                'stats' => [],
                'error' => $error
            ]);
            return;
        }
        
        // Get recent activities (last 100)
        $activities = $loginActivityModel->findAll(100, 0);
        
        // Get statistics (last 7 days)
        $stats = $loginActivityModel->getStatistics(7);
        
        $this->view('admin/login_activities', [
            'user' => $user,
            'activities' => $activities,
            'stats' => $stats
        ]);
    }

    /**
     * Admin diagnostic tool: fix gym owner roles that were not updated due to the previous bug.
     * Access: GET/POST index.php?r=admin/fixgymownerroles
     */
    public function fixgymownerrolesAction(): void
    {
        $this->requireAdmin();
        $this->view('admin/fix_gym_owner_roles', []);
    }
}

