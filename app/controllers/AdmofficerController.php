<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\User;
use App\Models\MembershipApplication;
use App\Models\GymMember;
use App\Models\Employee;
use App\Models\AttendanceLog;
use App\Models\FinancialRecord;
use App\Models\Notification;
use App\Models\FitnessServiceRequest;

final class AdmofficerController extends Controller
{
    private function requireOfficer(): array
    {
        if (!isset($_SESSION['user_id'])) { $this->redirect('auth/login'); }
        $user = (new User())->findById((int)$_SESSION['user_id']);
        if (!$user || $user['role'] !== 'administrative_officer') { $this->redirect('home/index'); }
        return $user;
    }

    private function notify(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void
    {
        $n = new Notification();
        if ($n->tableExists()) { $n->create($userId, $title, $message, $type, $link); }
    }

    /** List all membership applications */
    public function membershipsAction(): void
    {
        $user = $this->requireOfficer();
        $apps = (new MembershipApplication())->findAll();
        $trainers = (new Employee())->findAvailableTrainers();
        
        $builderCampaign = null;
        try {
            $campaignModel = new \App\Models\AdCampaign();
            if ($campaignModel->tableExists()) {
                $builderCampaign = $campaignModel->findActiveCampaignBuilder();
            }
        } catch (\Throwable $e) {
            $builderCampaign = null;
        }
        
        $this->view('admofficer/memberships', [
            'user' => $user, 
            'apps' => $apps, 
            'trainers' => $trainers,
            'builderCampaign' => $builderCampaign
        ]);
    }

    /** Review a single membership application */
    public function reviewAction(): void
    {
        $user = $this->requireOfficer();
        $id = (int)($_GET['id'] ?? 0);
        $appModel = new MembershipApplication();
        $app = $appModel->findById($id);
        if (!$app) { $this->redirect('admofficer/memberships'); }

        $error = ''; $success = '';
        $trainers = (new Employee())->findAvailableTrainers();
        $employees = (new Employee())->findAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $feedback = trim((string)($_POST['feedback'] ?? ''));
            $trainerId = !empty($_POST['trainer_id']) ? (int)$_POST['trainer_id'] : null;

            if ($action === 'verify') {
                $appModel->updateStatus($id, 'verified', $feedback, (int)$user['id']);
                // Admin officer assigns trainer at this step
                if ($trainerId) {
                    $appModel->assignTrainer($id, $trainerId);
                    $trainer = (new Employee())->findById($trainerId);
                    $trainerName = $trainer ? $trainer['fullname'] : 'a fitness trainer';
                    $this->notify((int)$app['user_id'], 'Trainer Assigned',
                        "A fitness trainer ($trainerName) has been assigned to your membership.",
                        'info', 'membership/apply');
                }
                $this->notify((int)$app['user_id'], 'Application Verified',
                    'Your membership application has been verified. Please proceed with payment.',
                    'success', 'membership/apply');
                $success = 'Application verified!';
            } elseif ($action === 'paid') {
                $paymentType = $app['payment_type'] ?? 'regular_monthly';
                $paymentAmount = round((float)($app['payment_amount'] ?? 0), 2);
                $code = GymMember::generateCode();
                $memberId = (new GymMember())->create((int)$app['user_id'], $id, $code, $trainerId, $paymentType, $paymentAmount);
                
                // Stamp Digital ID Card details: qr_token, member_id_number, issue_date
                $qrToken = GymMember::generateQrToken();
                $memberIdNumber = GymMember::generateMemberId($memberId);
                $issueDate = date('Y-m-d');
                (new GymMember())->stampMemberCard($memberId, $qrToken, $memberIdNumber, $issueDate);
                
                $appModel->updateStatus($id, 'approved', 'Payment confirmed. Member ID: ' . $memberIdNumber, (int)$user['id']);
                
                // Record revenue in financial_records for gym owner
                if (!empty($app['gym_owner_id'])) {
                    $finModel = new FinancialRecord();
                    $finModel->addRevenue(
                        (int)$app['gym_owner_id'],
                        'Membership Payment - ' . $app['first_name'] . ' ' . $app['last_name'],
                        $paymentAmount,
                        'Payment for ' . $paymentType . ' membership',
                        'Membership Revenue'
                    );
                }
                
                if ($trainerId) {
                    $appModel->assignTrainer($id, $trainerId);
                    (new Employee())->setAvailability($trainerId, false);
                    $trainer = (new Employee())->findById($trainerId);
                    $trainerName = $trainer ? $trainer['fullname'] : 'a fitness trainer';
                    $this->notify((int)$app['user_id'], 'Trainer Assigned',
                        "A fitness trainer ($trainerName) has been assigned to your membership.",
                        'info', 'membership/verifycode');
                }
                $this->notify((int)$app['user_id'], 'Membership Approved!',
                    'Payment confirmed. Your Member ID: ' . $memberIdNumber,
                    'success', 'membership/verifycode');
                $success = 'Payment confirmed! Member ID: ' . $memberIdNumber;
            } elseif ($action === 'assign_trainer') {
                // Assign trainer to an existing approved member
                if ($trainerId) {
                    $appModel->assignTrainer($id, $trainerId);
                    // Also update gym_members record
                    $gymMember = (new GymMember())->findByUserId((int)$app['user_id']);
                    if ($gymMember) {
                        (new GymMember())->assignTrainer((int)$gymMember['id'], $trainerId);
                    }
                    (new Employee())->setAvailability($trainerId, false);
                    $trainer = (new Employee())->findById($trainerId);
                    $trainerName = $trainer ? $trainer['fullname'] : 'a fitness trainer';
                    $this->notify((int)$app['user_id'], 'Trainer Assigned',
                        "A fitness trainer ($trainerName) has been assigned to your membership.",
                        'success', 'membership/apply');
                    $success = "Trainer assigned successfully.";
                } else { $error = 'Select a trainer.'; }
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

        $this->view('admofficer/review', [
            'user' => $user, 'app' => $app, 'trainers' => $trainers,
            'employees' => $employees, 'error' => $error, 'success' => $success,
        ]);
    }

    public function membersAction(): void
    {
        $user = $this->requireOfficer();
        $members = (new GymMember())->findAll();
        $this->view('admofficer/members', ['user' => $user, 'members' => $members]);
    }

    public function attendanceAction(): void
    {
        $user = $this->requireOfficer();
        $logs = (new AttendanceLog())->findAll();
        $this->view('admofficer/attendance', ['user' => $user, 'logs' => $logs]);
    }

    public function employeesAction(): void
    {
        $user = $this->requireOfficer();
        $employees = (new Employee())->findAll();
        $this->view('admofficer/employees', ['user' => $user, 'employees' => $employees]);
    }

    /**
     * Fitness Training — Read-Only Oversight View for Admin Officer.
     * Admin no longer approves or assigns coaches.
     * Direct enthusiast-to-trainer booking is now handled by FitnessController/TrainerController.
     */
    public function fitnessRequestsAction(): void
    {
        $user = $this->requireOfficer();
        $requestModel = new FitnessServiceRequest();

        // Read-only stats only
        $allRequests = $requestModel->findAll();
        $stats = $requestModel->getStats();

        $this->view('admofficer/fitness_requests', [
            'user'        => $user,
            'allRequests' => $allRequests,
            'stats'       => $stats,
            'readonly'    => true,
        ]);
    }

    /** Assign Trainer to Fitness Request (AJAX) — Feature 6 Fix */
    public function assignTrainerAction(): void
    {
        // Always return JSON
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(422);
            echo json_encode(['success' => false, 'errors' => ['Invalid request method']]);
            return;
        }

        $user = $this->requireOfficer();

        // NULL checks on IDs
        $requestId = !empty($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
        $trainerId  = !empty($_POST['trainer_id'])  ? (int)$_POST['trainer_id']  : 0;

        if ($requestId === 0 || $trainerId === 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'errors' => ['Missing request_id or trainer_id']]);
            return;
        }

        $requestModel = new FitnessServiceRequest();
        $request      = $requestModel->findById($requestId);

        if (!$request) {
            http_response_code(422);
            echo json_encode(['success' => false, 'errors' => ['Fitness request not found']]);
            return;
        }

        // Verify trainer exists and is a trainer position
        $employeeModel = new Employee();
        $trainer       = $employeeModel->findById($trainerId);

        if (!$trainer || strtolower($trainer['position'] ?? '') !== 'trainer') {
            http_response_code(422);
            echo json_encode(['success' => false, 'errors' => ['Selected employee is not a valid trainer']]);
            return;
        }

        // Upsert guard: if already assigned to the SAME trainer, just return success
        if ((int)($request['assigned_trainer_id'] ?? 0) === $trainerId
            && $request['status'] === 'assigned') {
            $trainerName = $trainer['fullname'] ?? 'Trainer';
            echo json_encode([
                'success'      => true,
                'message'      => 'Trainer was already assigned.',
                'trainer_name' => $trainerName
            ]);
            return;
        }

        // Assign trainer on the fitness request (sets status = 'assigned')
        if ($requestModel->assignTrainer($requestId, $trainerId, (int)$user['id'])) {

            // Mark trainer as unavailable
            $employeeModel->setAvailability($trainerId, false);

            // Record in trainer_assignments table (upsert via INSERT IGNORE + UPDATE)
            $memberId = (int)$request['member_id'];
            try {
                $this->db()->prepare(
                    'INSERT INTO trainer_assignments (client_id, trainer_id, assigned_by, status)
                     VALUES (:cid, :tid, :aby, "active")
                     ON DUPLICATE KEY UPDATE trainer_id=:tid2, assigned_by=:aby2,
                                             assigned_at=NOW(), status="active"'
                )->execute([
                    ':cid'  => $memberId,
                    ':tid'  => $trainerId,
                    ':aby'  => (int)$user['id'],
                    ':tid2' => $trainerId,
                    ':aby2' => (int)$user['id'],
                ]);
            } catch (\PDOException $e) {
                // trainer_assignments table may not exist yet — non-fatal
            }

            // Notify member
            $memberModel = new GymMember();
            $member      = $memberModel->findById($memberId);
            $trainerName = $trainer['fullname'] ?? 'a fitness trainer';

            if ($member) {
                $this->notify(
                    (int)$member['user_id'],
                    'Trainer Assigned to Your Fitness Request',
                    "$trainerName has been assigned to your fitness training request. You can now complete your client profile.",
                    'success',
                    'fitness/status'
                );
            }

            echo json_encode([
                'success'      => true,
                'message'      => 'Trainer assigned successfully!',
                'trainer_name' => $trainerName
            ]);
        } else {
            http_response_code(422);
            echo json_encode(['success' => false, 'errors' => ['Database update failed. Please try again.']]);
        }
    }

    /** Helper: get PDO instance for direct queries in this controller */
    private function db(): \PDO
    {
        return Database::pdo();
    }

    /** AJAX — Administrative Officer confirms a campaign registration payment */
    public function confirmPaymentAction(): void
    {
        header('Content-Type: application/json');
        $user = $this->requireOfficer();

        $campaignId = (int)($_POST['campaign_id'] ?? 0);
        $regId      = (int)($_POST['registration_id'] ?? 0);

        if ($campaignId <= 0 || $regId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid campaign or registration ID']);
            exit;
        }

        try {
            $pdo = \App\Core\Database::pdo();
            // Fetch the campaign directly by ID
            $stmt = $pdo->prepare('SELECT * FROM ad_campaigns WHERE id = :id');
            $stmt->execute([':id' => $campaignId]);
            $campaign = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$campaign) {
                echo json_encode(['success' => false, 'error' => 'Campaign not found']);
                exit;
            }

            $extra = [];
            if ($campaign['extra_json']) {
                $extra = json_decode($campaign['extra_json'], true) ?: [];
            }

            if (!isset($extra['registrations']) || empty($extra['registrations'])) {
                echo json_encode(['success' => false, 'error' => 'No registrations found for this campaign']);
                exit;
            }

            $found = false;
            $gymOwnerId = (int)($campaign['gym_id'] ?? 0);
            foreach ($extra['registrations'] as &$reg) {
                if ((int)$reg['id'] === $regId) {
                    $reg['status'] = 'Paid';
                    $found = true;
                    
                    // Also record revenue in financial records!
                    if ($gymOwnerId > 0) {
                        $finModel = new FinancialRecord();
                        $finModel->addRevenue(
                            $gymOwnerId,
                            'Campaign: ' . ($campaign['title'] ?? 'N/A') . ' (' . ($reg['pack'] ?? 'N/A') . ')',
                            (float)($reg['price'] ?? 0),
                            'Member: ' . ($reg['name'] ?? 'N/A'),
                            'Others'
                        );
                    }
                    break;
                }
            }

            if (!$found) {
                echo json_encode(['success' => false, 'error' => 'Registration not found']);
                exit;
            }

            $updatedExtraJson = json_encode($extra);
            $saveStmt = $pdo->prepare('UPDATE ad_campaigns SET extra_json = :extra WHERE id = :id');
            $saveStmt->execute([':extra' => $updatedExtraJson, ':id' => $campaignId]);

            echo json_encode(['success' => true]);
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Show QR Scanner view for Admin Officer.
     * GET: index.php?r=admofficer/scanqr
     */
    public function scanqrAction(): void
    {
        $user = $this->requireOfficer();
        $this->view('admofficer/scan_qr', ['user' => $user]);
    }

    /**
     * AJAX endpoint to look up a member's QR token, return info + status,
     * and automatically check them in (log attendance) if active.
     * POST: index.php?r=admofficer/qrlookup
     */
    public function qrlookupAction(): void
    {
        header('Content-Type: application/json');
        $user = $this->requireOfficer();

        $token = trim((string)($_POST['token'] ?? ''));
        if ($token === '') {
            echo json_encode(['success' => false, 'error' => 'Invalid QR token provided.']);
            exit;
        }

        $memberModel = new GymMember();
        $member = $memberModel->findByQrToken($token);

        if (!$member) {
            echo json_encode(['success' => false, 'error' => 'Member not found with this ID card.']);
            exit;
        }

        // Expiration check
        $expired = false;
        if (!empty($member['expiration_date']) && strtotime($member['expiration_date']) < time()) {
            $expired = true;
        }

        // Format dates
        $issue = !empty($member['issue_date']) ? date('M d, Y', strtotime($member['issue_date'])) : 'N/A';
        $expiry = !empty($member['expiration_date']) ? date('M d, Y', strtotime($member['expiration_date'])) : 'Never';

        // Check if already checked in today
        $attendanceModel = new AttendanceLog();
        $alreadyCheckedIn = $attendanceModel->hasCheckedInToday((int)$member['id']);

        $res = [
            'success' => true,
            'member_id' => $member['id'],
            'member_id_number' => $member['member_id_number'] ?? 'N/A',
            'fullname' => $member['fullname'],
            'photo' => !empty($member['profile_picture_url']) ? 'public/' . ltrim($member['profile_picture_url'], '/') : null,
            'plan' => ucfirst(str_replace('_', ' ', $member['payment_type'])),
            'issue_date' => $issue,
            'expiration_date' => $expiry,
            'status' => $expired ? 'Expired' : 'Active',
            'already_checked_in' => $alreadyCheckedIn
        ];

        echo json_encode($res);
        exit;
    }

    /**
     * AJAX endpoint to log attendance for a scanned active member.
     * POST: index.php?r=admofficer/logattendance
     */
    public function logattendanceAction(): void
    {
        header('Content-Type: application/json');
        $user = $this->requireOfficer();

        $memberId = (int)($_POST['member_id'] ?? 0);
        if ($memberId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid member ID.']);
            exit;
        }

        $memberModel = new GymMember();
        $member = $memberModel->findById($memberId);

        if (!$member) {
            echo json_encode(['success' => false, 'error' => 'Member not found.']);
            exit;
        }

        // Prevent check-in if expired
        if (!empty($member['expiration_date']) && strtotime($member['expiration_date']) < time()) {
            echo json_encode(['success' => false, 'error' => 'Membership has expired. Cannot log attendance.']);
            exit;
        }

        $attendanceModel = new AttendanceLog();
        
        // Prevent duplicate logs for the same day
        if ($attendanceModel->hasCheckedInToday($memberId)) {
            echo json_encode(['success' => false, 'error' => 'Attendance already logged for today.']);
            exit;
        }

        $logId = $attendanceModel->createByToken($memberId, $member['qr_token'] ?? 'DIRECT_CHECKIN');

        if ($logId > 0) {
            echo json_encode(['success' => true, 'message' => 'Attendance logged successfully! Welcome, ' . $member['fullname']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to record attendance in logs.']);
        }
        exit;
    }
}
