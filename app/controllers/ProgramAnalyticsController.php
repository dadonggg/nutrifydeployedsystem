<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Employee;
use App\Models\GymMember;
use App\Models\ProgramSuccessAnalytics;
use App\Models\FitnessTrainerFeedback;
use App\Models\Notification;
use PDO;

final class ProgramAnalyticsController extends Controller
{
    private function requireAuth(): array
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('auth/login');
        }
        $user = (new User())->findById((int)$_SESSION['user_id']);
        if (!$user) {
            $this->redirect('auth/login');
        }
        $role = $user['role'] ?? '';
        if ($role !== 'gym_owner' && $role !== 'admin') {
            $this->redirect('home/index');
        }
        return $user;
    }

    public function indexAction(): void
    {
        $user = $this->requireAuth();
        
        $programFilter = $_GET['program'] ?? 'all';
        $ageGroupFilter = $_GET['age_group'] ?? 'all';
        $threshold = isset($_GET['threshold']) ? (float)$_GET['threshold'] : 10.0;

        $analyticsModel = new ProgramSuccessAnalytics();
        $results = $analyticsModel->calculateProgramSuccessRate($programFilter, $ageGroupFilter, $threshold);

        $this->view('trainer/program_analytics', [
            'pageTitle' => 'Program Success & Feedback Analytics',
            'currentUser' => $user,
            'results' => $results,
            'programFilter' => $programFilter,
            'ageGroupFilter' => $ageGroupFilter,
            'threshold' => $threshold
        ]);
    }

    public function calculateAction(): void
    {
        header('Content-Type: application/json');
        $user = $this->requireAuth();

        $programFilter = $_GET['program'] ?? 'all';
        $ageGroupFilter = $_GET['age_group'] ?? 'all';
        $threshold = isset($_GET['threshold']) ? (float)$_GET['threshold'] : 10.0;

        $analyticsModel = new ProgramSuccessAnalytics();
        $results = $analyticsModel->calculateProgramSuccessRate($programFilter, $ageGroupFilter, $threshold);

        echo json_encode([
            'success' => true,
            'data' => $results
        ]);
        exit;
    }

    /**
     * Send email-style trainer feedback directly to a client
     */
    public function sendFeedbackAction(): void
    {
        $user = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('programanalytics/index');
        }

        $memberId = (int)($_POST['member_id'] ?? 0);
        $subject = trim((string)($_POST['feedback_subject'] ?? 'Progress Review & Feedback'));
        $status = trim((string)($_POST['feedback_status'] ?? 'on_track'));
        $feedbackText = trim((string)($_POST['feedback_text'] ?? ''));
        $areasOfImprovement = trim((string)($_POST['areas_of_improvement'] ?? ''));
        $encouragement = trim((string)($_POST['encouragement'] ?? ''));
        $nextSteps = trim((string)($_POST['next_steps'] ?? ''));

        if ($memberId <= 0 || empty($feedbackText)) {
            $_SESSION['error'] = 'Please enter a feedback message for the client.';
            $this->redirect('programanalytics/index');
        }

        // Determine trainer's employee ID
        $employeeModel = new Employee();
        $employee = $employeeModel->findByUserId((int)$user['id']);
        $trainerId = $employee ? (int)$employee['id'] : (int)$user['id'];

        $feedbackModel = new FitnessTrainerFeedback();
        $feedbackId = $feedbackModel->create(
            0, // progress tracking id if ad-hoc
            $trainerId,
            $memberId,
            0, // service request id
            [
                'feedback_status' => $status,
                'feedback_subject' => $subject,
                'feedback_text' => $feedbackText,
                'areas_of_improvement' => $areasOfImprovement,
                'encouragement' => $encouragement,
                'next_steps' => $nextSteps
            ]
        );

        if ($feedbackId > 0) {
            // Send notification to the member's user account
            $member = (new GymMember())->findById($memberId);
            if ($member && !empty($member['user_id'])) {
                $statusLabels = [
                    'on_track' => '🎯 On Track',
                    'plateau' => '⚠️ Plateau Detected',
                    'needs_adjustment' => '🔄 Routine Adjustment Needed',
                    'goal_achieved' => '🏆 Goal Milestone Reached'
                ];
                $statusLabel = $statusLabels[$status] ?? 'Progress Review';

                $notificationModel = new Notification();
                $notificationModel->create(
                    (int)$member['user_id'],
                    'Coach Feedback: ' . $subject,
                    sprintf('Coach %s sent you progress feedback [%s]: "%s"', htmlspecialchars($user['fullname'] ?? 'Trainer'), $statusLabel, mb_strimwidth($feedbackText, 0, 100, '...')),
                    'info',
                    'member/dashboard'
                );
            }

            $_SESSION['success'] = 'Feedback successfully delivered to the client!';
        } else {
            $_SESSION['error'] = 'Failed to submit feedback. Please try again.';
        }

        $this->redirect('programanalytics/index');
    }

    /**
     * Fetch client weight log history and previous feedback via AJAX
     */
    public function getMemberHistoryAction(): void
    {
        header('Content-Type: application/json');
        $this->requireAuth();

        $memberId = (int)($_GET['member_id'] ?? 0);
        if ($memberId <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid member ID']);
            exit;
        }

        $analyticsModel = new ProgramSuccessAnalytics();
        $memberStatus = $analyticsModel->getMemberProgramStatus($memberId);

        $feedbackModel = new FitnessTrainerFeedback();
        $feedbackHistory = $feedbackModel->findByMemberId($memberId);

        // Fetch recent weight logs
        $weightLogs = [];
        try {
            $db = \App\Core\Database::pdo();
            $stmt = $db->prepare('SELECT weight_kg, date_logged, goal_type FROM member_weight_logs WHERE member_id = :mid ORDER BY date_logged DESC LIMIT 10');
            $stmt->execute([':mid' => $memberId]);
            $weightLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Ignore
        }

        echo json_encode([
            'success' => true,
            'member' => $memberStatus,
            'weight_logs' => $weightLogs,
            'feedback_history' => $feedbackHistory
        ]);
        exit;
    }
}
