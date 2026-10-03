<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\GymMember;
use App\Models\WorkoutSession;
use App\Models\MemberGoal;
use App\Models\TrainerSession;
use App\Models\GymAnnouncement;
use App\Models\MemberPayment;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\GymEquipment;
use App\Models\AdCampaign;
use App\Models\GymPromotion;
use App\Models\CampaignView;
use App\Models\CampaignInterest;
use App\Models\PromotionInterest;

final class MemberController extends Controller
{
    private function requireMember(): array
    {
        if (!isset($_SESSION['user_id'])) { 
            $this->redirect('auth/login'); 
        }
        
        $user = (new User())->findById((int)$_SESSION['user_id']);
        if (!$user) { 
            unset($_SESSION['user_id']); 
            $this->redirect('auth/login'); 
        }

        // Get member record
        $member = (new GymMember())->findByUserId((int)$user['id']);
        if (!$member) {
            $this->redirect('membership/apply');
        }

        return ['user' => $user, 'member' => $member];
    }

    /** Member Dashboard - Main overview */
    public function dashboardAction(): void
    {
        $data = $this->requireMember();
        $user = $data['user'];
        $member = $data['member'];

        // Get dashboard statistics
        $workoutModel = new WorkoutSession();
        $goalModel = new MemberGoal();
        $trainerModel = new TrainerSession();
        $announcementModel = new GymAnnouncement();
        $attendanceModel = new AttendanceLog();

        // Workout stats
        $workoutStats = $workoutModel->getMemberStats((int)$member['id']);
        
        // Goal stats
        $goalStats = $goalModel->getMemberGoalStats((int)$member['id']);
        $rawActiveGoals = $goalModel->findByMemberId((int)$member['id'], 'active');
        $activeGoals = [];
        foreach ($rawActiveGoals as $goal) {
            $progress = $goalModel->getGoalProgress((int)$goal['id']);
            if (!empty($progress)) {
                $activeGoals[] = $progress;
            }
        }
        
        // Trainer session stats
        $trainerStats = $trainerModel->getMemberSessionStats((int)$member['id']);
        $upcomingSessions = $trainerModel->findUpcomingSessions((int)$member['id']);
        
        // Announcements
        $announcements = $announcementModel->findForMember((int)$member['id']);
        $unreadCount = $announcementModel->getUnreadCount((int)$member['id']);
        
        // Recent attendance
        $recentAttendance = $attendanceModel->findByMemberId((int)$member['id']);
        $recentAttendance = array_slice($recentAttendance, 0, 5); // Last 5 visits

        // Calculate attendance streak
        $attendanceStreak = $this->calculateAttendanceStreak($recentAttendance);
        
        // This month's visits
        $thisMonthVisits = count(array_filter($recentAttendance, function($visit) {
            return date('Y-m', strtotime($visit['check_in'])) === date('Y-m');
        }));

        // Fetch active fitness training request & plan status
        $fitnessRequestModel = new \App\Models\FitnessServiceRequest();
        $fitnessRequests = $fitnessRequestModel->findByMemberId((int)$member['id']);
        $activeFitnessRequest = null;
        $activePlan = null;
        if (!empty($fitnessRequests)) {
            foreach ($fitnessRequests as $req) {
                if ($req['status'] === 'assigned' || $req['status'] === 'pending') {
                    $activeFitnessRequest = $req;
                    if ($req['status'] === 'assigned') {
                        $planModel = new \App\Models\FitnessTrainerPlan();
                        $activePlan = $planModel->findByServiceRequestId((int)$req['id']);
                    }
                    break;
                }
            }
        }

        // Fetch active campaigns and promotions
        $isExpired = false;
        if ($member['expiration_date']) {
            $expiryDate = new \DateTime($member['expiration_date']);
            $today = new \DateTime();
            if ($expiryDate < $today) {
                $isExpired = true;
            }
        }

        $gymOwnerId = $this->getGymOwnerIdForMember($member);

        $activeCampaigns = [];
        $builderCampaign = null;
        $activePromotions = [];
        $campaignInterestMap = [];
        $promotionInterestMap = [];

        if ($gymOwnerId > 0) {
            $campaignModel = new AdCampaign();
            if ($campaignModel->tableExists()) {
                $allCampaigns = $campaignModel->findActiveByGym($gymOwnerId);
                foreach ($allCampaigns as $c) {
                    $target = $c['target_audience'] ?? 'all_members';
                    if ($target === 'all_members' ||
                        ($target === 'active_members' && !$isExpired) ||
                        ($target === 'inactive_members' && $isExpired)) {
                        
                        if (($c['source'] ?? '') === 'campaign_builder') {
                            $builderCampaign = $c;
                        } else {
                            $activeCampaigns[] = $c;
                        }

                        // Log view
                        $cvModel = new CampaignView();
                        if ($cvModel->tableExists()) {
                            $cvModel->log((int)$c['id'], (int)$member['id']);
                        }
                    }
                }
            }

            $promoModel = new GymPromotion();
            if ($promoModel->tableExists()) {
                $activePromotions = $promoModel->findActiveByGym($gymOwnerId);
            }
        }

        // Load member's interest responses for campaigns and promotions
        $ciModel = new CampaignInterest();
        if ($ciModel->tableExists() && !empty($activeCampaigns)) {
            $campaignIds = array_column($activeCampaigns, 'id');
            $campaignInterestMap = $ciModel->getMemberResponses((int)$member['id'], $campaignIds);
        }
        $piModel = new PromotionInterest();
        if ($piModel->tableExists() && !empty($activePromotions)) {
            $promoIds = array_column($activePromotions, 'id');
            $promotionInterestMap = $piModel->getMemberResponses((int)$member['id'], $promoIds);
        }

        // ── Progress sidebar data ─────────────────────────────────────────────
        $progressData = $this->getProgressData((int)$user['id'], (int)$member['id']);

        $this->view('member/dashboard', [
            'user' => $user,
            'member' => $member,
            'workoutStats' => $workoutStats,
            'goalStats' => $goalStats,
            'activeGoals' => $activeGoals,
            'trainerStats' => $trainerStats,
            'upcomingSessions' => $upcomingSessions,
            'announcements' => $announcements,
            'unreadCount' => $unreadCount,
            'recentAttendance' => $recentAttendance,
            'attendanceStreak' => $attendanceStreak,
            'thisMonthVisits' => $thisMonthVisits,
            'activeFitnessRequest' => $activeFitnessRequest,
            'activePlan' => $activePlan,
            'activeCampaigns'      => $activeCampaigns,
            'builderCampaign'      => $builderCampaign,
            'activePromotions'     => $activePromotions,
            'campaignInterestMap'  => $campaignInterestMap,
            'promotionInterestMap' => $promotionInterestMap,
            'progressData'         => $progressData,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PROGRESS FEATURE — PRIVATE HELPER
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Build progress sidebar data: weight history, goal, consistency score.
     */
    private function getProgressData(int $userId, int $memberId): array
    {
        $pdo = \App\Core\Database::pdo();

        // ── Weight history (all logs for graph) ───────────────────────────────
        $weightHistory = [];
        try {
            $stmt = $pdo->prepare(
                'SELECT weight_kg, date_logged, goal_type
                 FROM member_weight_logs
                 WHERE user_id = ?
                 ORDER BY date_logged ASC'
            );
            $stmt->execute([$userId]);
            $weightHistory = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $weightHistory = [];
        }


        // ── Latest entry & first entry ────────────────────────────────────────
        $latestWeight = !empty($weightHistory) ? end($weightHistory) : null;
        $firstWeight  = !empty($weightHistory) ? reset($weightHistory) : null;

        // ── Net change ────────────────────────────────────────────────────────
        $netChange = null;
        $netChangeLabel = null;
        if ($latestWeight && $firstWeight && $latestWeight['date_logged'] !== $firstWeight['date_logged']) {
            $netChange = round((float)$latestWeight['weight_kg'] - (float)$firstWeight['weight_kg'], 1);
            $sign      = $netChange >= 0 ? '+' : '';
            $firstDate = date('M j', strtotime($firstWeight['date_logged']));
            $netChangeLabel = "{$sign}{$netChange} kg since {$firstDate}";
        }

        // ── Current fitness goal from gym_members ─────────────────────────────
        $fitnessGoal = null;
        try {
            $stmt2 = $pdo->prepare('SELECT fitness_goal FROM gym_members WHERE user_id = ? LIMIT 1');
            $stmt2->execute([$userId]);
            $gmRow = $stmt2->fetch(\PDO::FETCH_ASSOC);
            $fitnessGoal = $gmRow['fitness_goal'] ?? null;
        } catch (\Exception $e) {
            // Column may not exist yet (migration not run)
        }

        // ── Consistency score (Sc = Σ B + si·w) ──────────────────────────────
        $consistencyScore = 0;
        try {
            $stmt3 = $pdo->prepare(
                "SELECT DISTINCT DATE(session_date) as d
                 FROM workout_sessions
                 WHERE member_id = ? AND status = 'completed'
                 ORDER BY d ASC"
            );
            $stmt3->execute([$memberId]);
            $sessionDates = $stmt3->fetchAll(\PDO::FETCH_COLUMN);

            $B = 10; // Base points per log
            $w = 2;  // Streak bonus weight
            $streak = 0;
            $prevDate = null;
            foreach ($sessionDates as $d) {
                if ($prevDate !== null) {
                    $diff = (int)((strtotime($d) - strtotime($prevDate)) / 86400);
                    $streak = ($diff === 1) ? $streak + 1 : 0;
                } else {
                    $streak = 1;
                }
                $consistencyScore += $B + ($streak * $w);
                $prevDate = $d;
            }
        } catch (\Exception $e) {
            $consistencyScore = 0;
        }

        return [
            'weightHistory'   => $weightHistory,
            'latestWeight'    => $latestWeight,
            'firstWeight'     => $firstWeight,
            'netChange'       => $netChange,
            'netChangeLabel'  => $netChangeLabel,
            'fitnessGoal'     => $fitnessGoal,
            'consistencyScore'=> $consistencyScore,
        ];
    }

    /** AJAX — Save campaign interest response */
    public function savecampaigninterestAction(): void
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Not logged in']);
            exit;
        }
        $campaignId = (int)($_POST['campaign_id'] ?? 0);
        $status     = $_POST['status'] ?? '';
        if ($campaignId <= 0 || !in_array($status, ['interested', 'not_interested'], true)) {
            echo json_encode(['success' => false, 'error' => 'Invalid data']);
            exit;
        }
        $memberModel = new GymMember();
        $member = $memberModel->findByUserId((int)$_SESSION['user_id']);
        if (!$member) {
            echo json_encode(['success' => false, 'error' => 'Member not found']);
            exit;
        }
        $ciModel = new CampaignInterest();
        $ok = $ciModel->saveResponse($campaignId, (int)$member['id'], $status);
        echo json_encode(['success' => $ok]);
        exit;
    }

    /** AJAX — Save promotion interest response */
    public function savepromotioninterestAction(): void
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Not logged in']);
            exit;
        }
        $promotionId = (int)($_POST['promotion_id'] ?? 0);
        $status      = $_POST['status'] ?? '';
        if ($promotionId <= 0 || !in_array($status, ['interested', 'not_interested'], true)) {
            echo json_encode(['success' => false, 'error' => 'Invalid data']);
            exit;
        }
        $memberModel = new GymMember();
        $member = $memberModel->findByUserId((int)$_SESSION['user_id']);
        if (!$member) {
            echo json_encode(['success' => false, 'error' => 'Member not found']);
            exit;
        }
        $piModel = new PromotionInterest();
        $ok = $piModel->saveResponse($promotionId, (int)$member['id'], $status);
        echo json_encode(['success' => $ok]);
        exit;
    }

    /** AJAX — Register member for a campaign builder campaign */
    public function registercampaignbuilderAction(): void
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Not logged in']);
            exit;
        }

        $campaignId = (int)($_POST['campaign_id'] ?? 0);
        $packName   = trim((string)($_POST['pack_name'] ?? ''));
        $packPrice  = (float)($_POST['pack_price'] ?? 0);
        $schedule   = trim((string)($_POST['schedule'] ?? ''));
        $paymentMode = trim((string)($_POST['payment_mode'] ?? 'cash'));

        if ($campaignId <= 0 || $packName === '' || $schedule === '') {
            echo json_encode(['success' => false, 'error' => 'Invalid registration data']);
            exit;
        }

        $user = (new User())->findById((int)$_SESSION['user_id']);
        $member = (new GymMember())->findByUserId((int)$_SESSION['user_id']);
        if (!$member) {
            echo json_encode(['success' => false, 'error' => 'Member record not found']);
            exit;
        }

        $displayName = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
        if ($displayName === '') $displayName = $user['fullname'] ?? 'Member';

        try {
            $pdo = \App\Core\Database::pdo();
            // Fetch campaign for update
            $stmt = $pdo->prepare('SELECT * FROM ad_campaigns WHERE id = :id FOR UPDATE');
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

            if (!isset($extra['registrations'])) {
                $extra['registrations'] = [];
            }

            // Check if already registered
            foreach ($extra['registrations'] as $reg) {
                if ($reg['email'] === $user['email']) {
                    echo json_encode(['success' => false, 'error' => 'You are already registered for this campaign']);
                    exit;
                }
            }

            // Generate registration ID
            $nextRegId = count($extra['registrations']) + 1;

            $newReg = [
                'id' => $nextRegId,
                'name' => $displayName,
                'email' => $user['email'],
                'pack' => $packName,
                'price' => $packPrice,
                'schedule' => $schedule,
                'payment_mode' => $paymentMode,
                'date' => date('M j, Y'),
                'status' => 'Pending'
            ];

            $checkoutUrl = null;
            if ($paymentMode === 'online') {
                $gymOwnerId = $this->getGymOwnerIdForMember($member);
                if ($gymOwnerId > 0) {
                    $checkoutUrl = $this->generatePayMongoLink($campaign, $packPrice, $gymOwnerId);
                    if ($checkoutUrl) {
                        $newReg['paymongo_url'] = $checkoutUrl;
                    }
                }
            }

            $extra['registrations'][] = $newReg;
            $updatedExtraJson = json_encode($extra);

            // Save back to DB
            $saveStmt = $pdo->prepare('UPDATE ad_campaigns SET extra_json = :extra WHERE id = :id');
            $saveStmt->execute([':extra' => $updatedExtraJson, ':id' => $campaignId]);

            $response = ['success' => true, 'registration' => $newReg];
            if ($checkoutUrl) {
                $response['checkout_url'] = $checkoutUrl;
            }
            echo json_encode($response);
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    /** Events & Promotions — full-page view of active campaigns & promotions */
    public function campaignsAction(): void
    {
        $data = $this->requireMember();
        $user = $data['user'];
        $member = $data['member'];

        $isExpired = false;
        if ($member['expiration_date']) {
            $expiryDate = new \DateTime($member['expiration_date']);
            $today = new \DateTime();
            if ($expiryDate < $today) $isExpired = true;
        }

        $gymOwnerId = $this->getGymOwnerIdForMember($member);

        $activeCampaigns = [];
        $activePromotions = [];
        $campaignInterestMap = [];
        $promotionInterestMap = [];

        if ($gymOwnerId > 0) {
            $campaignModel = new AdCampaign();
            if ($campaignModel->tableExists()) {
                $allCampaigns = $campaignModel->findActiveByGym($gymOwnerId);
                foreach ($allCampaigns as $c) {
                    $target = $c['target_audience'] ?? 'all_members';
                    if ($target === 'all_members' ||
                        ($target === 'active_members' && !$isExpired) ||
                        ($target === 'inactive_members' && $isExpired)) {
                        $activeCampaigns[] = $c;
                    }
                }
            }
            $promoModel = new GymPromotion();
            if ($promoModel->tableExists()) {
                $activePromotions = $promoModel->findActiveByGym($gymOwnerId);
            }
        }

        $ciModel = new CampaignInterest();
        if ($ciModel->tableExists() && !empty($activeCampaigns)) {
            $campaignInterestMap = $ciModel->getMemberResponses((int)$member['id'], array_column($activeCampaigns, 'id'));
        }
        $piModel = new PromotionInterest();
        if ($piModel->tableExists() && !empty($activePromotions)) {
            $promotionInterestMap = $piModel->getMemberResponses((int)$member['id'], array_column($activePromotions, 'id'));
        }

        $this->view('member/campaigns', [
            'user' => $user,
            'member' => $member,
            'activeCampaigns' => $activeCampaigns,
            'activePromotions' => $activePromotions,
            'campaignInterestMap' => $campaignInterestMap,
            'promotionInterestMap' => $promotionInterestMap,
        ]);
    }

    /** My Coach & Plan — dedicated coaching page */
    public function coachingAction(): void
    {
        $data = $this->requireMember();
        $user = $data['user'];
        $member = $data['member'];

        $fitnessRequestModel = new \App\Models\FitnessServiceRequest();
        $fitnessRequests = $fitnessRequestModel->findByMemberId((int)$member['id']);
        $activeFitnessRequest = null;
        $activePlan = null;

        if (!empty($fitnessRequests)) {
            foreach ($fitnessRequests as $req) {
                if ($req['status'] === 'assigned' || $req['status'] === 'pending') {
                    $activeFitnessRequest = $req;
                    if ($req['status'] === 'assigned') {
                        $planModel = new \App\Models\FitnessTrainerPlan();
                        $activePlan = $planModel->findByServiceRequestId((int)$req['id']);
                    }
                    break;
                }
            }
        }

        // Get all past requests for history
        $allRequests = $fitnessRequests;

        $this->view('member/coaching', [
            'user' => $user,
            'member' => $member,
            'activeFitnessRequest' => $activeFitnessRequest,
            'activePlan' => $activePlan,
            'allRequests' => $allRequests,
        ]);
    }

    /** Workout Progress & Performance Tracking */
    public function workoutsAction(): void
    {
        $data = $this->requireMember();
        $user = $data['user'];
        $member = $data['member'];

        $workoutModel = new WorkoutSession();
        $error = ''; $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'add_workout') {
                $sessionDate = $_POST['session_date'] ?? date('Y-m-d');
                $sessionType = $_POST['session_type'] ?? 'mixed';
                $duration = (int)($_POST['duration'] ?? 0);
                $calories = (int)($_POST['calories'] ?? 0);
                $notes = trim((string)($_POST['notes'] ?? ''));

                $sessionId = $workoutModel->create((int)$member['id'], $sessionDate, $sessionType, $duration, $calories, $notes);
                
                if ($sessionId > 0) {
                    // Add exercises if provided
                    $exercises = $_POST['exercises'] ?? [];
                    foreach ($exercises as $exercise) {
                        if (!empty($exercise['name'])) {
                            $workoutModel->addExercise(
                                $sessionId,
                                $exercise['name'],
                                (int)($exercise['sets'] ?? 0),
                                $exercise['reps'] ?? '',
                                (float)($exercise['weight'] ?? 0),
                                (float)($exercise['distance'] ?? 0),
                                (int)($exercise['duration'] ?? 0),
                                $exercise['notes'] ?? ''
                            );
                        }
                    }
                    $success = 'Workout session added successfully!';
                } else {
                    $error = 'Failed to add workout session.';
                }
            }
        }

        // Get workout data
        $workouts = $workoutModel->findByMemberId((int)$member['id'], 20);
        $stats = $workoutModel->getMemberStats((int)$member['id']);
        $popularExercises = $workoutModel->getPopularExercises((int)$member['id']);

        $this->view('member/workouts', [
            'user' => $user,
            'member' => $member,
            'workouts' => $workouts,
            'stats' => $stats,
            'popularExercises' => $popularExercises,
            'error' => $error,
            'success' => $success
        ]);
    }

    /** Goals Management */
    public function goalsAction(): void
    {
        $data = $this->requireMember();
        $user = $data['user'];
        $member = $data['member'];

        $goalModel = new MemberGoal();
        $error = ''; $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'add_goal') {
                $goalType = $_POST['goal_type'] ?? 'other';
                $title = trim((string)($_POST['title'] ?? ''));
                $description = trim((string)($_POST['description'] ?? ''));
                $targetValue = !empty($_POST['target_value']) ? (float)$_POST['target_value'] : null;
                $targetUnit = trim((string)($_POST['target_unit'] ?? ''));
                $targetDate = !empty($_POST['target_date']) ? $_POST['target_date'] : null;

                if ($title === '') {
                    $error = 'Goal title is required.';
                } else {
                    $goalId = $goalModel->create((int)$member['id'], $goalType, $title, $description, $targetValue, $targetUnit, $targetDate);
                    if ($goalId > 0) {
                        $success = 'Goal created successfully!';
                    } else {
                        $error = 'Failed to create goal.';
                    }
                }
            } elseif ($action === 'update_progress') {
                $goalId = (int)($_POST['goal_id'] ?? 0);
                $currentValue = (float)($_POST['current_value'] ?? 0);

                if ($goalModel->updateProgress($goalId, $currentValue)) {
                    $success = 'Goal progress updated!';
                } else {
                    $error = 'Failed to update progress.';
                }
            } elseif ($action === 'update_status') {
                $goalId = (int)($_POST['goal_id'] ?? 0);
                $status = $_POST['status'] ?? 'active';

                if ($goalModel->updateStatus($goalId, $status)) {
                    $success = 'Goal status updated!';
                } else {
                    $error = 'Failed to update status.';
                }
            }
        }

        // Get goals data
        $activeGoals = $goalModel->findByMemberId((int)$member['id'], 'active');
        $completedGoals = $goalModel->findByMemberId((int)$member['id'], 'completed');
        $stats = $goalModel->getMemberGoalStats((int)$member['id']);

        // Get progress for each active goal
        $goalsWithProgress = [];
        foreach ($activeGoals as $goal) {
            $progress = $goalModel->getGoalProgress((int)$goal['id']);
            $goalsWithProgress[] = $progress;
        }

        // Get progress data
        $progressData = $this->getProgressData((int)$user['id'], (int)$member['id']);

        $this->view('member/goals', [
            'user' => $user,
            'member' => $member,
            'activeGoals' => $goalsWithProgress,
            'completedGoals' => $completedGoals,
            'stats' => $stats,
            'error' => $error,
            'success' => $success,
            'progressData' => $progressData,
        ]);

    }

    /** Trainer Booking & Scheduling */
    public function trainersAction(): void
    {
        $data = $this->requireMember();
        $user = $data['user'];
        $member = $data['member'];

        $trainerModel = new TrainerSession();
        $employeeModel = new Employee();
        $error = ''; $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'book_session') {
                $trainerId = (int)($_POST['trainer_id'] ?? 0);
                $sessionDate = $_POST['session_date'] ?? '';
                $sessionTime = $_POST['session_time'] ?? '';
                $sessionType = $_POST['session_type'] ?? 'personal_training';
                $duration = (int)($_POST['duration'] ?? 60);
                $memberNotes = trim((string)($_POST['member_notes'] ?? ''));
                $price = (float)($_POST['price'] ?? 0);

                if ($trainerId === 0 || $sessionDate === '' || $sessionTime === '') {
                    $error = 'Please fill in all required fields.';
                } else {
                    $sessionId = $trainerModel->create((int)$member['id'], $trainerId, $sessionDate, $sessionTime, $duration, $sessionType, $memberNotes, $price);
                    if ($sessionId > 0) {
                        $success = 'Training session booked successfully!';
                    } else {
                        $error = 'Failed to book session.';
                    }
                }
            } elseif ($action === 'cancel_session') {
                $sessionId = (int)($_POST['session_id'] ?? 0);
                $reason = trim((string)($_POST['reason'] ?? ''));

                if ($trainerModel->cancelSession($sessionId, $reason)) {
                    $success = 'Session cancelled successfully.';
                } else {
                    $error = 'Failed to cancel session.';
                }
            }
        }

        // Get trainer data
        $availableTrainers = $employeeModel->findAvailableTrainers();
        $upcomingSessions = $trainerModel->findUpcomingSessions((int)$member['id']);
        $pastSessions = $trainerModel->findByMemberId((int)$member['id'], 'completed');
        $stats = $trainerModel->getMemberSessionStats((int)$member['id']);

        // Get available slots for AJAX
        $selectedDate = $_GET['date'] ?? date('Y-m-d');
        $selectedTrainer = (int)($_GET['trainer_id'] ?? 0);
        $availableSlots = [];
        if ($selectedTrainer > 0) {
            $availableSlots = $trainerModel->getAvailableSlots($selectedTrainer, $selectedDate);
        }

        $this->view('member/trainers', [
            'user' => $user,
            'member' => $member,
            'availableTrainers' => $availableTrainers,
            'upcomingSessions' => $upcomingSessions,
            'pastSessions' => $pastSessions,
            'stats' => $stats,
            'availableSlots' => $availableSlots,
            'selectedDate' => $selectedDate,
            'selectedTrainer' => $selectedTrainer,
            'error' => $error,
            'success' => $success
        ]);
    }

    /** Attendance History */
    public function attendanceAction(): void
    {
        $data = $this->requireMember();
        $user = $data['user'];
        $member = $data['member'];

        $attendanceModel = new AttendanceLog();

        // Get attendance data
        $attendanceHistory = $attendanceModel->findByMemberId((int)$member['id']);
        
        // Calculate statistics
        $totalVisits = count($attendanceHistory);
        $thisMonthVisits = count(array_filter($attendanceHistory, function($visit) {
            return date('Y-m', strtotime($visit['check_in'])) === date('Y-m');
        }));
        
        $lastVisit = !empty($attendanceHistory) ? $attendanceHistory[0]['check_in'] : null;
        $attendanceStreak = $this->calculateAttendanceStreak($attendanceHistory);
        
        // Monthly breakdown
        $monthlyStats = [];
        foreach ($attendanceHistory as $visit) {
            $month = date('Y-m', strtotime($visit['check_in']));
            if (!isset($monthlyStats[$month])) {
                $monthlyStats[$month] = 0;
            }
            $monthlyStats[$month]++;
        }
        
        // Sort by month descending
        krsort($monthlyStats);
        $monthlyStats = array_slice($monthlyStats, 0, 12, true); // Last 12 months

        $this->view('member/attendance', [
            'user' => $user,
            'member' => $member,
            'attendanceHistory' => $attendanceHistory,
            'totalVisits' => $totalVisits,
            'thisMonthVisits' => $thisMonthVisits,
            'lastVisit' => $lastVisit,
            'attendanceStreak' => $attendanceStreak,
            'monthlyStats' => $monthlyStats
        ]);
    }

    /** Gym Announcements */
    public function announcementsAction(): void
    {
        $data = $this->requireMember();
        $user = $data['user'];
        $member = $data['member'];

        $announcementModel = new GymAnnouncement();

        // Mark announcement as viewed if specified
        if (isset($_GET['view']) && is_numeric($_GET['view'])) {
            $announcementModel->markAsViewed((int)$member['id'], (int)$_GET['view']);
        }

        // Get announcements
        $announcements = $announcementModel->findForMember((int)$member['id']);
        $unreadCount = $announcementModel->getUnreadCount((int)$member['id']);

        $this->view('member/announcements', [
            'user' => $user,
            'member' => $member,
            'announcements' => $announcements,
            'unreadCount' => $unreadCount
        ]);
    }

    /** Membership Management */
    public function membershipAction(): void
    {
        $data = $this->requireMember();
        $user = $data['user'];
        $member = $data['member'];

        $paymentModel = new MemberPayment();
        $error = ''; $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'renew_membership') {
                $amount = (float)($_POST['amount'] ?? 0);
                $paymentMethod = $_POST['payment_method'] ?? 'cash';

                if ($amount <= 0) {
                    $error = 'Please enter a valid payment amount.';
                } else {
                    if ($paymentModel->recordMembershipRenewal((int)$member['id'], $amount, $paymentMethod)) {
                        $success = 'Membership renewed successfully!';
                        // Refresh member data
                        $member = (new GymMember())->findByUserId((int)$user['id']);
                    } else {
                        $error = 'Failed to process renewal.';
                    }
                }
            }
        }

        // Get payment history and summary
        $paymentHistory = $paymentModel->findByMemberId((int)$member['id']);
        $paymentSummary = $paymentModel->getMemberPaymentSummary((int)$member['id']);
        $monthlyPayments = $paymentModel->getMonthlyPayments((int)$member['id'], 12);

        // Calculate membership status
        $isExpired = false;
        $daysUntilExpiry = null;
        if ($member['expiration_date']) {
            $expiryDate = new \DateTime($member['expiration_date']);
            $today = new \DateTime();
            $diff = $today->diff($expiryDate);
            
            if ($expiryDate < $today) {
                $isExpired = true;
                $daysUntilExpiry = -$diff->days;
            } else {
                $daysUntilExpiry = $diff->days;
            }
        }

        $this->view('member/membership', [
            'user' => $user,
            'member' => $member,
            'paymentHistory' => $paymentHistory,
            'paymentSummary' => $paymentSummary,
            'monthlyPayments' => $monthlyPayments,
            'isExpired' => $isExpired,
            'daysUntilExpiry' => $daysUntilExpiry,
            'error' => $error,
            'success' => $success
        ]);
    }

    /** AJAX endpoint for trainer availability */
    public function getTrainerSlotsAction(): void
    {
        header('Content-Type: application/json');
        
        $trainerId = (int)($_GET['trainer_id'] ?? 0);
        $date = $_GET['date'] ?? date('Y-m-d');

        if ($trainerId === 0) {
            echo json_encode(['error' => 'Invalid trainer ID']);
            return;
        }

        $trainerModel = new TrainerSession();
        $slots = $trainerModel->getAvailableSlots($trainerId, $date);

        echo json_encode(['slots' => $slots]);
    }

    /** View Available Equipment */
    public function equipmentAction(): void
    {
        $data = $this->requireMember();
        $user = $data['user'];
        $member = $data['member'];

        // Get gym owner ID with robust fallbacks
        $gymOwnerId = $this->getGymOwnerIdForMember($member);

        // Get equipment from gym equipment
        $equipment = [];
        if ($gymOwnerId > 0) {
            $equipmentRaw = (new GymEquipment())->findByOwnerId($gymOwnerId);
            // Map the name field to equipment_name for view compatibility
            foreach ($equipmentRaw as $item) {
                $item['equipment_name'] = $item['name'] ?? 'Unknown Equipment';
                $equipment[] = $item;
            }
        }

        // Get equipment stats
        $totalEquipment = count($equipment);
        $categories = [];
        foreach ($equipment as $item) {
            $category = $item['category'] ?? 'Other';
            if (!isset($categories[$category])) {
                $categories[$category] = 0;
            }
            $categories[$category]++;
        }

        $this->view('member/equipment', [
            'user' => $user,
            'member' => $member,
            'equipment' => $equipment,
            'totalEquipment' => $totalEquipment,
            'categories' => $categories
        ]);
    }

    /** Calculate attendance streak */
    private function calculateAttendanceStreak(array $attendanceHistory): int
    {
        if (empty($attendanceHistory)) return 0;

        $streak = 0;
        $currentDate = new \DateTime();
        $currentDate->setTime(0, 0, 0); // Reset time to start of day

        // Sort attendance by date descending
        usort($attendanceHistory, function($a, $b) {
            return strtotime($b['check_in']) - strtotime($a['check_in']);
        });

        foreach ($attendanceHistory as $visit) {
            $visitDate = new \DateTime($visit['check_in']);
            $visitDate->setTime(0, 0, 0);
            
            $daysDiff = $currentDate->diff($visitDate)->days;
            
            if ($daysDiff <= 1) { // Today or yesterday
                $streak++;
                $currentDate = $visitDate;
            } else {
                break; // Streak broken
            }
        }

        return $streak;
    }

    /** Resolve Gym Owner ID for a member with multiple fallbacks */
    private function generatePayMongoLink(array $campaign, float $amount, int $gymOwnerId): ?string
    {
        $paymongoModel = new \App\Models\PayMongoConfig();
        if (!$paymongoModel->tableExists()) {
            return null;
        }

        $config = $paymongoModel->findByOwnerId($gymOwnerId);
        if (!$config || !$config['is_active']) {
            return null;
        }

        $secretKey = $config['secret_key'];
        $description = 'Campaign Registration - ' . ($campaign['title'] ?? 'Campaign');
        $remarks = 'campaign_reg_' . $campaign['id'];

        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://api.paymongo.com/v1/links",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "accept: application/json",
                    "authorization: Basic " . base64_encode($secretKey . ":"),
                    "content-type: application/json"
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    "data" => [
                        "attributes" => [
                            "amount" => (int)($amount * 100), // Convert to centavos
                            "description" => $description,
                            "remarks" => $remarks
                        ]
                    ]
                ])
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($httpCode === 200 || $httpCode === 201) {
                $data = json_decode($response, true);
                if (isset($data['data']['attributes']['checkout_url'])) {
                    return $data['data']['attributes']['checkout_url'];
                }
            }
        } catch (\Exception $e) {
            error_log('PayMongo API Error: ' . $e->getMessage());
        }

        return null;
    }

    private function getGymOwnerIdForMember(array $member): int
    {
        $gymOwnerId = 0;
        
        // 1. Try Membership Application
        if (!empty($member['application_id'])) {
            $appModel = new \App\Models\MembershipApplication();
            $application = $appModel->findById((int)$member['application_id']);
            if ($application && !empty($application['gym_owner_id'])) {
                $gymOwnerId = (int)$application['gym_owner_id'];
            }
        }
        
        // 2. Try Trainer's Gym Owner
        if ($gymOwnerId === 0 && !empty($member['assigned_trainer_id'])) {
            $employeeModel = new \App\Models\Employee();
            $trainer = $employeeModel->findById((int)$member['assigned_trainer_id']);
            if ($trainer && !empty($trainer['hired_by'])) {
                $gymOwnerId = (int)$trainer['hired_by'];
            }
        }
        
        // 3. Fallback: get the first verified gym owner ID from legal_documents
        if ($gymOwnerId === 0) {
            $legalDocModel = new \App\Models\LegalDocument();
            if ($legalDocModel->tableExists()) {
                $verifiedGyms = $legalDocModel->findAllVerified();
                if (!empty($verifiedGyms) && !empty($verifiedGyms[0]['user_id'])) {
                    $gymOwnerId = (int)$verifiedGyms[0]['user_id'];
                }
            }
        }

        // 4. Ultimate Fallback: get any active gym owner from users table
        if ($gymOwnerId === 0) {
            $stmt = \App\Core\Database::pdo()->query("SELECT id FROM users WHERE role = 'gym_owner' LIMIT 1");
            $owner = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($owner) {
                $gymOwnerId = (int)$owner['id'];
            }
        }
        
        return $gymOwnerId;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PROGRESS SIDEBAR — AJAX ENDPOINTS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * GET: index.php?r=member/progressData
     * Returns full weight history + consistency score as JSON.
     */
    public function progressDataAction(): void
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }
        $data   = $this->requireMember();
        $result = $this->getProgressData((int)$data['user']['id'], (int)$data['member']['id']);
        echo json_encode(['success' => true, 'data' => $result]);
    }

    /**
     * GET: index.php?r=member/progress
     * Renders the dedicated Weight Progress & Analytics page.
     */
    public function progressAction(): void
    {
        $data   = $this->requireMember();
        $user   = $data['user'];
        $member = $data['member'];

        $goalModel = new MemberGoal();
        $goalStats = $goalModel->getMemberGoalStats((int)$member['id']);
        $activeGoals = $goalModel->findByMemberId((int)$member['id'], 'active');
        $progressData = $this->getProgressData((int)$user['id'], (int)$member['id']);

        $consistencyScore = 0;
        $currentStreak = 0;
        $totalLoggedDays = 0;

        try {
            $progressModel = new \App\Models\FitnessProgressTracking();
            $curProg = $progressModel->getCurrentProgressByMemberId((int)$member['id'], 0, (int)$user['id']);
            $consistencyScore = $curProg['consistency_score'] ?? 0;
            $currentStreak = $curProg['current_streak'] ?? 0;
            $totalLoggedDays = $curProg['total_logged_days'] ?? 0;
        } catch (\Throwable $e) {
            // Ignore error gracefully
        }

        // Automatically notify trainer if member is off-track
        $this->checkAndAutoSendToTrainerIfOffTrack((int)$user['id'], (int)$member['id']);

        $this->view('member/progress', [
            'user'             => $user,
            'member'           => $member,
            'goalStats'        => $goalStats,
            'activeGoals'      => $activeGoals,
            'progressData'     => $progressData,
            'consistencyScore' => $consistencyScore,
            'currentStreak'    => $currentStreak,
            'totalLoggedDays'  => $totalLoggedDays,
        ]);
    }

    /**
     * POST: index.php?r=member/saveWeight
     * Upserts the weight entry for the current week.
     * Body: { weight_kg: float, goal_type?: string }
     */
    public function saveWeightAction(): void
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }

        $data   = $this->requireMember();
        $user   = $data['user'];
        $member = $data['member'];

        $input      = json_decode(file_get_contents('php://input'), true) ?? [];
        $weightKg   = (float)($input['weight_kg'] ?? 0);
        $goalType   = in_array($input['goal_type'] ?? '', ['bulking','cutting','maintaining'], true)
                      ? $input['goal_type'] : null;
        $dateLogged = (!empty($input['date_logged']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['date_logged']))
                      ? $input['date_logged']
                      : date('Y-m-d');

        if ($weightKg <= 0 || $weightKg > 500) {
            echo json_encode(['success' => false, 'error' => 'Invalid weight value.']);
            return;
        }

        try {
            $pdo = \App\Core\Database::pdo();

            // Check if an entry already exists for this exact date
            $stmt = $pdo->prepare(
                'SELECT id FROM member_weight_logs
                 WHERE user_id = ?
                   AND date_logged = ?
                 LIMIT 1'
            );
            $stmt->execute([(int)$user['id'], $dateLogged]);
            $existing = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($existing) {
                // Update existing entry for that date
                $upd = $pdo->prepare(
                    'UPDATE member_weight_logs
                     SET weight_kg = ?, goal_type = ?
                     WHERE id = ?'
                );
                $upd->execute([$weightKg, $goalType, (int)$existing['id']]);
                $message = 'Weight updated for ' . $dateLogged . '.';
            } else {
                // Insert new entry
                $ins = $pdo->prepare(
                    'INSERT INTO member_weight_logs (user_id, member_id, weight_kg, date_logged, goal_type)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $ins->execute([(int)$user['id'], (int)$member['id'], $weightKg, $dateLogged, $goalType]);
                $message = 'Weight logged successfully for ' . $dateLogged . '.';
            }

            // Also keep gym_members.fitness_goal in sync if provided
            if ($goalType) {
                try {
                    $pdo->prepare('UPDATE gym_members SET fitness_goal = ? WHERE user_id = ?')
                        ->execute([$goalType, (int)$user['id']]);
                } catch (\Exception $e) { /* Column may not exist yet */ }
            }

            // Check if client is experiencing off-track warning and automatically notify assigned trainer
            $this->checkAndAutoSendToTrainerIfOffTrack((int)$user['id'], (int)$member['id']);

            echo json_encode([
                'success' => true,
                'message' => $message,
                'data'    => $this->getProgressData((int)$user['id'], (int)$member['id']),
            ]);
        } catch (\Exception $e) {
            error_log('saveWeight error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Failed to save weight: ' . $e->getMessage()]);
        }
    }

    /**
     * POST: index.php?r=member/saveGoal
     * Updates the member's fitness goal.
     * Body: { goal_type: 'bulking'|'cutting'|'maintaining' }
     */
    public function saveGoalAction(): void
    {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }

        $data  = $this->requireMember();
        $user  = $data['user'];
        $member = $data['member'];
        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $goalType = $input['goal_type'] ?? '';
        if (!in_array($goalType, ['bulking', 'cutting', 'maintaining'], true)) {
            echo json_encode(['success' => false, 'error' => 'Invalid goal type.']);
            return;
        }

        try {
            $pdo = \App\Core\Database::pdo();
            $pdo->prepare('UPDATE gym_members SET fitness_goal = ? WHERE user_id = ?')
                ->execute([$goalType, (int)$user['id']]);

            // Also update the latest weight log's goal_type to keep them in sync
            $pdo->prepare(
                'UPDATE member_weight_logs SET goal_type = ?
                 WHERE user_id = ?
                 ORDER BY date_logged DESC LIMIT 1'
            )->execute([$goalType, (int)$user['id']]);

            // ── Create or update corresponding active goal in member_goals ──
            $stmtActive = $pdo->prepare(
                'SELECT id FROM member_goals 
                 WHERE member_id = ? AND title LIKE "Fitness Goal:%" AND status = "active"
                 LIMIT 1'
            );
            $stmtActive->execute([(int)$member['id']]);
            $existingActiveGoal = $stmtActive->fetch(\PDO::FETCH_ASSOC);

            $mappedType = 'other';
            $mappedTitle = 'Fitness Goal: Maintaining';
            if ($goalType === 'bulking') {
                $mappedType = 'weight_gain';
                $mappedTitle = 'Fitness Goal: Bulking';
            } elseif ($goalType === 'cutting') {
                $mappedType = 'weight_loss';
                $mappedTitle = 'Fitness Goal: Cutting';
            }

            if ($existingActiveGoal) {
                // Update existing active goal
                $updGoal = $pdo->prepare(
                    'UPDATE member_goals 
                     SET goal_type = ?, title = ?, updated_at = CURRENT_TIMESTAMP
                     WHERE id = ?'
                );
                $updGoal->execute([$mappedType, $mappedTitle, (int)$existingActiveGoal['id']]);
            } else {
                // Create new active goal
                $insGoal = $pdo->prepare(
                    'INSERT INTO member_goals (member_id, goal_type, title, description, status)
                     VALUES (?, ?, ?, ?, "active")'
                );
                $insGoal->execute([
                    (int)$member['id'],
                    $mappedType,
                    $mappedTitle,
                    'Automatically tracked progress goal based on fitness goal selection.'
                ]);
            }

            // Check if client is experiencing off-track warning and automatically notify assigned trainer
            $this->checkAndAutoSendToTrainerIfOffTrack((int)$user['id'], (int)$member['id']);

            echo json_encode(['success' => true, 'goal_type' => $goalType]);
        } catch (\Exception $e) {
            error_log('saveGoal error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Failed to save goal.']);
        }
    }

    /**
     * Automatically send progress snapshot and alert trainer if client is off-track
     */
    private function checkAndAutoSendToTrainerIfOffTrack(int $userId, int $memberId): void
    {
        try {
            $pdo = \App\Core\Database::pdo();
            
            // 1. Get member details & assigned trainer
            $stmt = $pdo->prepare('SELECT gm.*, u.fullname FROM gym_members gm JOIN users u ON u.id = gm.user_id WHERE gm.id = ? LIMIT 1');
            $stmt->execute([$memberId]);
            $member = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$member) return;

            // Find assigned trainer ID
            $trainerEmployeeId = (int)($member['assigned_trainer_id'] ?? 0);
            $serviceRequestId = 0;

            // Check if there is an active fitness service request
            $stmtReq = $pdo->prepare(
                'SELECT id, assigned_trainer_id FROM fitness_service_requests 
                 WHERE member_id = ? AND status IN ("assigned", "in_progress", "active") 
                 ORDER BY id DESC LIMIT 1'
            );
            $stmtReq->execute([$memberId]);
            $req = $stmtReq->fetch(\PDO::FETCH_ASSOC);
            if ($req) {
                $serviceRequestId = (int)$req['id'];
                if ($trainerEmployeeId === 0 && !empty($req['assigned_trainer_id'])) {
                    $trainerEmployeeId = (int)$req['assigned_trainer_id'];
                }
            }

            if ($trainerEmployeeId === 0) return; // No trainer assigned

            // 2. Evaluate if client is currently off-track
            $progressData = $this->getProgressData($userId, $memberId);
            $wHistory = $progressData['weightHistory'] ?? [];
            $firstW = $progressData['firstWeight'] ?? null;
            $latestW = $progressData['latestWeight'] ?? null;
            $netChange = $progressData['netChange'] ?? null;
            $fGoal = $progressData['fitnessGoal'] ?? null;

            if (count($wHistory) < 2 || $netChange === null || empty($fGoal)) {
                return;
            }

            $isOffTrack = false;
            $alertDesc = '';

            if ($fGoal === 'cutting' && $netChange > 0.1) {
                $isOffTrack = true;
                $alertDesc = "Active Goal is Cutting (Fat Loss), but weight has increased by +{$netChange} kg ({$firstW['weight_kg']} kg → {$latestW['weight_kg']} kg)";
            } elseif ($fGoal === 'bulking' && $netChange < -0.1) {
                $isOffTrack = true;
                $alertDesc = "Active Goal is Bulking (Mass Gain), but weight has decreased by {$netChange} kg ({$firstW['weight_kg']} kg → {$latestW['weight_kg']} kg)";
            } elseif ($fGoal === 'maintaining' && abs($netChange) > 1.0) {
                $isOffTrack = true;
                $alertDesc = "Active Goal is Maintaining, but weight drifted by " . ($netChange > 0 ? "+{$netChange}" : "{$netChange}") . " kg";
            }

            if (!$isOffTrack) return;

            // 3. Automatically record snapshot & mark sent_to_trainer = 1
            $progressModel = new \App\Models\FitnessProgressTracking();
            $progressId = $progressModel->calculateAndSave($memberId, $serviceRequestId);
            if ($progressId > 0) {
                $progressModel->sendToTrainer($progressId);
            } else {
                // If calculateAndSave returned existing or 0, ensure latest record is marked sent
                $pdo->prepare(
                    'UPDATE fitness_progress_tracking 
                     SET sent_to_trainer = 1, sent_at = NOW() 
                     WHERE member_id = ? 
                     ORDER BY id DESC LIMIT 1'
                )->execute([$memberId]);
            }

            // 4. Notify Trainer (with debounce: check if notification already sent in last 12 hours)
            $empModel = new \App\Models\Employee();
            $trainerEmp = $empModel->findById($trainerEmployeeId);
            if ($trainerEmp && !empty($trainerEmp['user_id'])) {
                $trainerUserId = (int)$trainerEmp['user_id'];
                $clientName = $member['fullname'] ?? 'Client';

                $stmtNotifCheck = $pdo->prepare(
                    'SELECT id FROM notifications 
                     WHERE user_id = ? AND title LIKE "⚠️ Goal Alert: %" AND created_at >= DATE_SUB(NOW(), INTERVAL 12 HOUR)
                     LIMIT 1'
                );
                $stmtNotifCheck->execute([$trainerUserId]);
                $alreadyNotified = $stmtNotifCheck->fetch(\PDO::FETCH_ASSOC);

                if (!$alreadyNotified) {
                    $notifModel = new \App\Models\Notification();
                    $notifModel->create(
                        $trainerUserId,
                        "⚠️ Goal Alert: {$clientName} is Off Track",
                        "{$clientName} is experiencing goal misalignment: {$alertDesc}. Please review their progress and provide feedback on what to adjust.",
                        'warning',
                        'trainer/progress'
                    );
                }
            }
        } catch (\Throwable $e) {
            error_log('checkAndAutoSendToTrainerIfOffTrack error: ' . $e->getMessage());
        }
    }
}