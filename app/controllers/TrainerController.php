<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Employee;
use App\Models\GymEquipment;
use App\Models\FitnessServiceRequest;
use App\Models\FitnessClientProfile;
use App\Models\FitnessTrainerPlan;
use App\Models\FitnessProgressTracking;
use App\Models\FitnessTrainerFeedback;
use App\Models\Notification;
use App\Models\GymMember;
use App\Models\CustomExercise;
use App\Models\CustomEquipment;

final class TrainerController extends Controller
{
    private function requireTrainer(): array
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('auth/login');
        }
        
        $user = (new User())->findById((int)$_SESSION['user_id']);
        $trainerRoles = ['trainer', 'fitness_trainer'];
        if (!$user || !in_array($user['role'], $trainerRoles, true)) {
            $this->redirect('home/index');
        }

        // Get trainer's employee record
        $employeeModel = new Employee();
        $employee = $employeeModel->findByUserId((int)$user['id']);
        
        if (!$employee) {
            $_SESSION['error'] = 'Your trainer profile setup is incomplete. Please ask the gym owner to re-assign your trainer role.';
            $this->redirect('home/index');
        }

        return ['user' => $user, 'employee' => $employee];
    }

    private function notify(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void
    {
        $n = new Notification();
        if ($n->tableExists()) {
            $n->create($userId, $title, $message, $type, $link);
        }
    }

    /** View Equipment - Shows gym equipment from the gym owner who hired this trainer */
    public function equipmentAction(): void
    {
        $data = $this->requireTrainer();
        $user = $data['user'];
        $employee = $data['employee'];

        // Get equipment from the gym owner who hired this trainer
        $equipment = [];
        $gymOwnerId = (int)($employee['hired_by'] ?? 0);
        
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

        $this->view('trainer/equipment', [
            'user' => $user,
            'employee' => $employee,
            'equipment' => $equipment,
            'totalEquipment' => $totalEquipment,
            'categories' => $categories
        ]);
    }

    /** View All Clients with expanded profile data */
    public function clientsAction(): void
    {
        $data = $this->requireTrainer();
        $user = $data['user'];
        $employee = $data['employee'];

        // Get assigned clients
        $requestModel = new FitnessServiceRequest();
        $assignedClients = $requestModel->findByTrainerIdWithProfiles((int)$employee['id']);

        $this->view('trainer/clients', [
            'user' => $user,
            'employee' => $employee,
            'assignedClients' => $assignedClients
        ]);
    }

    /** Show Create/Edit Plan form for a specific client */
    public function createPlanAction(): void
    {
        $data = $this->requireTrainer();
        $user = $data['user'];
        $employee = $data['employee'];

        $requestId = (int)($_GET['request_id'] ?? 0);
        if ($requestId === 0) {
            $this->redirect('trainer/clients');
        }

        $requestModel = new FitnessServiceRequest();
        $clientRequest = $requestModel->findById($requestId);

        // Verify this client belongs to this trainer
        if (!$clientRequest || (int)$clientRequest['assigned_trainer_id'] !== (int)$employee['id']) {
            $_SESSION['error'] = 'Client not found or not assigned to you.';
            $this->redirect('trainer/clients');
        }

        $profileModel = new FitnessClientProfile();
        $clientProfile = $profileModel->findByServiceRequestId($requestId);

        $planModel = new FitnessTrainerPlan();
        $plan = $planModel->findByServiceRequestId($requestId);

        $this->view('trainer/create_plan', [
            'user' => $user,
            'employee' => $employee,
            'clientRequest' => $clientRequest,
            'clientProfile' => $clientProfile,
            'plan' => $plan
        ]);
    }

    /** Save Fitness & Nutrition Plan */
    public function savePlanAction(): void
    {
        $data = $this->requireTrainer();
        $user = $data['user'];
        $employee = $data['employee'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('trainer/clients');
        }

        $requestId = (int)($_GET['request_id'] ?? 0);
        if ($requestId === 0) {
            $this->redirect('trainer/clients');
        }

        $requestModel = new FitnessServiceRequest();
        $clientRequest = $requestModel->findById($requestId);

        if (!$clientRequest || (int)$clientRequest['assigned_trainer_id'] !== (int)$employee['id']) {
            $_SESSION['error'] = 'Unauthorized.';
            $this->redirect('trainer/clients');
        }

        $profileModel = new FitnessClientProfile();
        $clientProfile = $profileModel->findByServiceRequestId($requestId);
        $profileId = $clientProfile ? (int)$clientProfile['id'] : 0;

        $planAction = $_POST['plan_action'] ?? 'draft'; // 'draft' or 'send'
        $status = $planAction === 'send' ? 'sent' : 'draft';

        $planData = [
            'fitness_level' => $_POST['fitness_level'] ?? 'beginner',
            'primary_goals' => trim((string)($_POST['primary_goals'] ?? '')),
            'limitations_notes' => trim((string)($_POST['limitations_notes'] ?? '')),
            'recommended_sessions_per_week' => (int)($_POST['recommended_sessions_per_week'] ?? 3),
            'fitness_plan_monday' => trim((string)($_POST['fitness_plan_monday'] ?? '')),
            'fitness_plan_tuesday' => trim((string)($_POST['fitness_plan_tuesday'] ?? '')),
            'fitness_plan_wednesday' => trim((string)($_POST['fitness_plan_wednesday'] ?? '')),
            'fitness_plan_thursday' => trim((string)($_POST['fitness_plan_thursday'] ?? '')),
            'fitness_plan_friday' => trim((string)($_POST['fitness_plan_friday'] ?? '')),
            'fitness_plan_saturday' => trim((string)($_POST['fitness_plan_saturday'] ?? '')),
            'fitness_plan_sunday' => trim((string)($_POST['fitness_plan_sunday'] ?? '')),
            'fitness_plan_notes' => trim((string)($_POST['fitness_plan_notes'] ?? '')),
            'target_calories' => !empty($_POST['target_calories']) ? (int)$_POST['target_calories'] : null,
            'target_protein_g' => !empty($_POST['target_protein_g']) ? (int)$_POST['target_protein_g'] : null,
            'target_carbs_g' => !empty($_POST['target_carbs_g']) ? (int)$_POST['target_carbs_g'] : null,
            'target_fats_g' => !empty($_POST['target_fats_g']) ? (int)$_POST['target_fats_g'] : null,
            'meal_suggestions' => trim((string)($_POST['meal_suggestions'] ?? '')),
            'nutrition_notes' => trim((string)($_POST['nutrition_notes'] ?? '')),
            'status' => $status,
        ];

        $planModel = new FitnessTrainerPlan();
        $existingPlan = $planModel->findByServiceRequestId($requestId);

        if ($existingPlan) {
            $planData['status'] = $status; // preserve status override
            $success = $planModel->update((int)$existingPlan['id'], $planData);
            if ($success && $planAction === 'send') {
                $planModel->sendToClient((int)$existingPlan['id']);
            }
        } else {
            if ($profileId === 0) {
                // Create a placeholder profile if none exists
                $profileId = 1; // fallback — will fail FK gracefully if needed
            }
            $planId = $planModel->create($requestId, (int)$employee['id'], $profileId, $planData);
            if ($planId > 0 && $planAction === 'send') {
                $planModel->sendToClient($planId);
            }
        }

        // Notify the member
        if ($planAction === 'send') {
            $member = (new \App\Models\GymMember())->findById((int)$clientRequest['member_id']);
            if ($member) {
                $memberUser = (new User())->findById((int)$member['user_id']);
                if ($memberUser) {
                    $this->notify(
                        (int)$memberUser['id'],
                        'Your Fitness Plan is Ready! 🎯',
                        'Your trainer ' . ($user['fullname'] ?? '') . ' has created and sent your personalized fitness & nutrition plan. Check it now!',
                        'success',
                        'fitness/status'
                    );
                }
            }
            $_SESSION['success'] = 'Plan saved and sent to client successfully!';
        } else {
            $_SESSION['success'] = 'Plan saved as draft.';
        }

        $this->redirect('trainer/createPlan&request_id=' . $requestId);
    }

    /** Progress Review Panel */
    public function progressAction(): void
    {
        $data = $this->requireTrainer();
        $user = $data['user'];
        $employee = $data['employee'];

        $progressModel = new FitnessProgressTracking();
        $progressList = $progressModel->findSentToTrainer((int)$employee['id']);

        // Enrich progress list with weight history and goal alignment status
        $pdo = \App\Core\Database::pdo();
        foreach ($progressList as &$prog) {
            $mId = (int)($prog['member_id'] ?? 0);
            $uId = (int)($prog['user_id'] ?? 0);
            
            // Get weight history for this member
            $wHistory = [];
            try {
                $stmtW = $pdo->prepare(
                    'SELECT weight_kg, date_logged, goal_type 
                     FROM member_weight_logs 
                     WHERE member_id = ? OR user_id = ? 
                     ORDER BY date_logged ASC'
                );
                $stmtW->execute([$mId, $uId]);
                $wHistory = $stmtW->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\Exception $e) {
                $wHistory = [];
            }
            
            $prog['weight_history'] = $wHistory;
            $firstW = !empty($wHistory) ? reset($wHistory) : null;
            $latestW = !empty($wHistory) ? end($wHistory) : null;
            $prog['first_weight'] = $firstW;
            $prog['latest_weight'] = $latestW;
            
            $fGoal = $prog['member_goal'] ?? null;
            if (!$fGoal && $latestW && !empty($latestW['goal_type'])) {
                $fGoal = $latestW['goal_type'];
            }
            $prog['fitness_goal'] = $fGoal;
            
            $netChange = null;
            if ($firstW && $latestW && $firstW['date_logged'] !== $latestW['date_logged']) {
                $netChange = round((float)$latestW['weight_kg'] - (float)$firstW['weight_kg'], 1);
            }
            $prog['net_change'] = $netChange;
            
            // Determine Goal Alignment for Trainer Alert
            $alignment = [
                'status'     => 'none', // 'warning', 'on_track', 'none'
                'badge'      => '',
                'badgeClass' => 'bg-secondary',
                'title'      => '',
                'alert'      => '',
                'coach_hint' => '',
            ];
            
            if (count($wHistory) >= 2 && $netChange !== null && !empty($fGoal)) {
                if ($fGoal === 'cutting') {
                    if ($netChange > 0.1) {
                        $alignment = [
                            'status'     => 'warning',
                            'badge'      => '⚠️ Off Track Alert',
                            'badgeClass' => 'bg-danger text-white',
                            'title'      => '⚠️ Goal Misalignment: Weight is Increasing during Cutting Phase',
                            'alert'      => "Client is on a Cutting (Fat Loss) goal, but weight has INCREASED by +{$netChange} kg (Starting: {$firstW['weight_kg']} kg → Current: {$latestW['weight_kg']} kg). Caloric deficit adjustment or cardio recommendation needed.",
                            'coach_hint' => "Hi {$prog['client_name']}, I noticed your weight has increased by +{$netChange} kg while on your Cutting phase. Let's audit your daily calorie intake, track food portions more strictly, and add 20 minutes of cardio.",
                        ];
                    } elseif ($netChange < -0.1) {
                        $alignment = [
                            'status'     => 'on_track',
                            'badge'      => '✅ On Track',
                            'badgeClass' => 'bg-success text-white',
                            'title'      => 'Cutting Phase On Track',
                            'alert'      => "Client weight decreased by " . abs($netChange) . " kg, aligning with fat loss.",
                            'coach_hint' => "Great consistency! Your weight is dropping steadily. Keep maintaining your daily routine and protein intake.",
                        ];
                    }
                } elseif ($fGoal === 'bulking') {
                    if ($netChange < -0.1) {
                        $alignment = [
                            'status'     => 'warning',
                            'badge'      => '⚠️ Off Track Alert',
                            'badgeClass' => 'bg-danger text-white',
                            'title'      => '⚠️ Goal Misalignment: Weight is Decreasing during Bulking Phase',
                            'alert'      => "Client is on a Bulking (Mass Gain) goal, but weight has DECREASED by {$netChange} kg (Starting: {$firstW['weight_kg']} kg → Current: {$latestW['weight_kg']} kg). Calorie and protein surplus increase needed.",
                            'coach_hint' => "Hi {$prog['client_name']}, your weight is dropping (-" . abs($netChange) . " kg) despite a Bulking goal. Let's increase your daily calories by +300-500 kcal with extra complex carbs and protein.",
                        ];
                    } elseif ($netChange > 0.1) {
                        $alignment = [
                            'status'     => 'on_track',
                            'badge'      => '✅ On Track',
                            'badgeClass' => 'bg-success text-white',
                            'title'      => 'Bulking Phase On Track',
                            'alert'      => "Client weight increased by +{$netChange} kg, successfully building mass.",
                            'coach_hint' => "Solid progress! Your weight gain is on track. Focus on progressive overload in your lifts.",
                        ];
                    }
                } elseif ($fGoal === 'maintaining') {
                    if (abs($netChange) > 1.0) {
                        $driftText = $netChange > 0 ? "+{$netChange} kg" : "{$netChange} kg";
                        $alignment = [
                            'status'     => 'warning',
                            'badge'      => '⚠️ Maintenance Drift',
                            'badgeClass' => 'bg-warning text-dark',
                            'title'      => '⚠️ Maintenance Window Drift Alert',
                            'alert'      => "Client weight has drifted by {$driftText} beyond the ±1.0 kg maintenance window.",
                            'coach_hint' => "Hi {$prog['client_name']}, your weight has fluctuated by {$driftText}. Let's re-balance your daily maintenance caloric intake to stabilize.",
                        ];
                    } else {
                        $alignment = [
                            'status'     => 'on_track',
                            'badge'      => '✅ On Track',
                            'badgeClass' => 'bg-primary text-white',
                            'title'      => 'Maintenance On Track',
                            'alert'      => "Client weight is steady within the ±1.0 kg maintenance window.",
                            'coach_hint' => "Excellent stability! Your maintenance calories are well-balanced.",
                        ];
                    }
                }
            }
            $prog['alignment'] = $alignment;
        }
        unset($prog);

        $this->view('trainer/progress_review', [
            'user' => $user,
            'employee' => $employee,
            'progressList' => $progressList
        ]);
    }

    /** Send Feedback to Client */
    public function sendFeedbackAction(): void
    {
        $data = $this->requireTrainer();
        $user = $data['user'];
        $employee = $data['employee'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('trainer/progress');
        }

        $progressId = (int)($_POST['progress_id'] ?? 0);
        $serviceRequestId = (int)($_POST['service_request_id'] ?? 0);
        $memberId = (int)($_POST['member_id'] ?? 0);

        if ($progressId === 0 || $serviceRequestId === 0 || $memberId === 0) {
            $_SESSION['error'] = 'Invalid request data.';
            $this->redirect('trainer/progress');
        }

        $feedbackData = [
            'feedback_text' => trim((string)($_POST['feedback_text'] ?? '')),
            'areas_of_improvement' => trim((string)($_POST['areas_of_improvement'] ?? '')),
            'encouragement' => trim((string)($_POST['encouragement'] ?? '')),
            'next_steps' => trim((string)($_POST['next_steps'] ?? '')),
        ];

        if (empty($feedbackData['feedback_text'])) {
            $_SESSION['error'] = 'Feedback text is required.';
            $this->redirect('trainer/progress');
        }

        $feedbackModel = new FitnessTrainerFeedback();
        $feedbackId = $feedbackModel->create($progressId, (int)$employee['id'], $memberId, $serviceRequestId, $feedbackData);

        if ($feedbackId > 0) {
            // Notify the client
            $member = (new \App\Models\GymMember())->findById($memberId);
            if ($member) {
                $memberUser = (new User())->findById((int)$member['user_id']);
                if ($memberUser) {
                    $this->notify(
                        (int)$memberUser['id'],
                        'Trainer Feedback Received! 💬',
                        'Your trainer ' . ($user['fullname'] ?? '') . ' has reviewed your progress and left feedback. Check your progress page!',
                        'success',
                        'fitness/status'
                    );
                }
            }
            $_SESSION['success'] = 'Feedback submitted successfully!';
        } else {
            $_SESSION['error'] = 'Failed to submit feedback.';
        }

        $this->redirect('trainer/progress');
    }

    /** View Client Feedback (history) */
    public function feedbackAction(): void
    {
        $data = $this->requireTrainer();
        $user = $data['user'];
        $employee = $data['employee'];

        $feedbackModel = new FitnessTrainerFeedback();
        // Redirect to progress which now handles feedback
        $this->redirect('trainer/progress');
    }

    /** Get custom exercises (AJAX) */
    public function getCustomExercisesAction(): void
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }

        $user = (new User())->findById((int)$_SESSION['user_id']);
        if (!$user || !in_array($user['role'], ['trainer', 'fitness_trainer'], true)) {
            echo json_encode(['success' => false, 'error' => 'Not authorized']);
            return;
        }

        $employeeModel = new Employee();
        $employee = $employeeModel->findByUserId((int)$user['id']);
        
        if (!$employee) {
            echo json_encode(['success' => false, 'error' => 'Employee record not found']);
            return;
        }

        $customExerciseModel = new \App\Models\CustomExercise();
        $exercises = $customExerciseModel->findByTrainerId((int)$employee['id']);

        echo json_encode(['success' => true, 'exercises' => $exercises]);
    }

    /** Create custom exercise (AJAX) */
    public function createCustomExerciseAction(): void
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }

        $user = (new User())->findById((int)$_SESSION['user_id']);
        if (!$user || !in_array($user['role'], ['trainer', 'fitness_trainer'], true)) {
            echo json_encode(['success' => false, 'error' => 'Not authorized']);
            return;
        }

        $employeeModel = new Employee();
        $employee = $employeeModel->findByUserId((int)$user['id']);
        
        if (!$employee) {
            echo json_encode(['success' => false, 'error' => 'Employee record not found']);
            return;
        }

        $exerciseName = trim($_POST['exercise_name'] ?? '');
        $bodyPart = trim($_POST['body_part'] ?? '');
        $equipment = trim($_POST['equipment'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');
        $sets = (int)($_POST['sets'] ?? 3);
        $reps = (int)($_POST['reps'] ?? 10);

        if (empty($exerciseName) || empty($bodyPart) || empty($equipment)) {
            echo json_encode(['success' => false, 'error' => 'Exercise name, body part, and equipment are required']);
            return;
        }

        try {
            $customExerciseModel = new CustomExercise();
            $exerciseId = $customExerciseModel->create((int)$employee['id'], [
                'exercise_name' => $exerciseName,
                'body_part' => $bodyPart,
                'equipment' => $equipment,
                'instructions' => $instructions,
                'default_sets' => $sets,
                'default_reps' => $reps
            ]);

            if ($exerciseId > 0) {
                $exercise = $customExerciseModel->findById($exerciseId);
                echo json_encode(['success' => true, 'exercise' => $exercise, 'message' => 'Custom exercise created successfully!']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to create custom exercise']);
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    /** Create Meal Plan */
    public function createMealPlanAction(): void
    {
        $data = $this->requireTrainer();
        $user = $data['user'];
        $employee = $data['employee'];

        $requestId = (int)($_GET['request_id'] ?? 0);
        if ($requestId === 0) {
            $this->redirect('trainer/clients');
        }

        $requestModel = new FitnessServiceRequest();
        $clientRequest = $requestModel->findById($requestId);

        // Verify this client belongs to this trainer
        if (!$clientRequest || (int)$clientRequest['assigned_trainer_id'] !== (int)$employee['id']) {
            $_SESSION['error'] = 'Client not found or not assigned to you.';
            $this->redirect('trainer/clients');
        }

        $profileModel = new FitnessClientProfile();
        $clientProfile = $profileModel->findByServiceRequestId($requestId);

        $planModel = new FitnessTrainerPlan();
        $plan = $planModel->findByServiceRequestId($requestId);

        $this->view('trainer/create_meal_plan', [
            'user' => $user,
            'employee' => $employee,
            'clientRequest' => $clientRequest,
            'clientProfile' => $clientProfile,
            'plan' => $plan
        ]);
    }

    /** Save Complete Plan (Workout + Meal) and Send to Client */
    public function saveCompletePlanAction(): void
    {
        $data = $this->requireTrainer();
        $user = $data['user'];
        $employee = $data['employee'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Invalid request method.';
            $this->redirect('trainer/clients');
        }

        $requestId = (int)($_POST['request_id'] ?? 0);
        if ($requestId === 0) {
            $_SESSION['error'] = 'Invalid request ID.';
            $this->redirect('trainer/clients');
        }

        $requestModel = new FitnessServiceRequest();
        $clientRequest = $requestModel->findById($requestId);

        if (!$clientRequest || (int)$clientRequest['assigned_trainer_id'] !== (int)$employee['id']) {
            $_SESSION['error'] = 'Unauthorized access to this client.';
            $this->redirect('trainer/clients');
        }

        $profileModel = new FitnessClientProfile();
        $clientProfile = $profileModel->findByServiceRequestId($requestId);
        $profileId = $clientProfile ? (int)$clientProfile['id'] : 0;

        // Get workout plan from POST
        $workoutPlanJSON = $_POST['workout_plan'] ?? '';
        
        // Validate workout plan exists
        if (empty($workoutPlanJSON)) {
            $_SESSION['error'] = 'Workout plan is missing. Please create a workout plan first.';
            $this->redirect('trainer/createPlan&request_id=' . $requestId);
        }
        
        // Get meal plan data from POST
        $mealPlanData = [
            'target_calories' => !empty($_POST['target_calories']) ? (int)$_POST['target_calories'] : null,
            'target_protein_g' => !empty($_POST['target_protein_g']) ? (int)$_POST['target_protein_g'] : null,
            'target_carbs_g' => !empty($_POST['target_carbs_g']) ? (int)$_POST['target_carbs_g'] : null,
            'target_fats_g' => !empty($_POST['target_fats_g']) ? (int)$_POST['target_fats_g'] : null,
            'meal_suggestions' => trim((string)($_POST['meal_suggestions'] ?? '')),
            'nutrition_notes' => trim((string)($_POST['nutrition_notes'] ?? '')),
        ];

        // Combine workout plan JSON into fitness_plan_notes
        $planData = [
            'fitness_level' => $_POST['fitness_level'] ?? 'intermediate',
            'primary_goals' => trim((string)($_POST['primary_goals'] ?? 'General fitness and health')),
            'limitations_notes' => trim((string)($_POST['limitations_notes'] ?? 'None specified')),
            'recommended_sessions_per_week' => (int)($_POST['recommended_sessions_per_week'] ?? 3),
            'fitness_plan_monday' => $workoutPlanJSON, // Store as JSON
            'fitness_plan_tuesday' => '',
            'fitness_plan_wednesday' => '',
            'fitness_plan_thursday' => '',
            'fitness_plan_friday' => '',
            'fitness_plan_saturday' => '',
            'fitness_plan_sunday' => '',
            'fitness_plan_notes' => 'Workout plan created with exercise builder',
            'target_calories' => $mealPlanData['target_calories'],
            'target_protein_g' => $mealPlanData['target_protein_g'],
            'target_carbs_g' => $mealPlanData['target_carbs_g'],
            'target_fats_g' => $mealPlanData['target_fats_g'],
            'meal_suggestions' => $mealPlanData['meal_suggestions'],
            'nutrition_notes' => $mealPlanData['nutrition_notes'],
            'status' => 'sent',
        ];

        $planModel = new FitnessTrainerPlan();
        $existingPlan = $planModel->findByServiceRequestId($requestId);

        $success = false;
        if ($existingPlan) {
            $success = $planModel->update((int)$existingPlan['id'], $planData);
            if ($success) {
                $planModel->sendToClient((int)$existingPlan['id']);
            }
        } else {
            if ($profileId === 0) {
                // Create a minimal profile if none exists
                $profileId = 1; // fallback - will use FK constraint
            }
            $planId = $planModel->create($requestId, (int)$employee['id'], $profileId, $planData);
            if ($planId > 0) {
                $planModel->sendToClient($planId);
                $success = true;
            }
        }

        if (!$success) {
            $_SESSION['error'] = 'Failed to save the complete plan. Please try again.';
            $this->redirect('trainer/createMealPlan&request_id=' . $requestId);
        }

        // Notify the member
        $member = (new \App\Models\GymMember())->findById((int)$clientRequest['member_id']);
        if ($member) {
            $memberUser = (new User())->findById((int)$member['user_id']);
            if ($memberUser) {
                $this->notify(
                    (int)$memberUser['id'],
                    'Your Complete Fitness Plan is Ready! 🎯💪',
                    'Your trainer ' . ($user['fullname'] ?? '') . ' has created your personalized workout and meal plan. Check it now!',
                    'success',
                    'member/fitnessPlan&request_id=' . $requestId
                );
            }
        }

        $_SESSION['success'] = 'Complete plan (workout + meal) saved and sent to client successfully!';
        $this->redirect('trainer/clients');
    }

    /** Get custom equipment (AJAX) */
    public function getCustomEquipmentAction(): void
    {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }

        $user = (new User())->findById((int)$_SESSION['user_id']);
        if (!$user || !in_array($user['role'], ['trainer', 'fitness_trainer'], true)) {
            echo json_encode(['success' => false, 'error' => 'Not authorized']);
            return;
        }

        $employeeModel = new Employee();
        $employee = $employeeModel->findByUserId((int)$user['id']);
        
        if (!$employee) {
            echo json_encode(['success' => false, 'error' => 'Employee record not found']);
            return;
        }

        $customEquipmentModel = new CustomEquipment();
        $equipment = $customEquipmentModel->findByTrainerId((int)$employee['id']);

        echo json_encode(['success' => true, 'equipment' => $equipment]);
    }

    /** Create custom equipment (AJAX) */
    public function createCustomEquipmentAction(): void
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            return;
        }

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }

        $user = (new User())->findById((int)$_SESSION['user_id']);
        if (!$user || $user['role'] !== 'trainer') {
            echo json_encode(['success' => false, 'error' => 'Not authorized']);
            return;
        }

        $employeeModel = new Employee();
        $employee = $employeeModel->findByUserId((int)$user['id']);
        
        if (!$employee) {
            echo json_encode(['success' => false, 'error' => 'Employee record not found']);
            return;
        }

        $equipmentName = trim($_POST['equipment_name'] ?? '');

        if (empty($equipmentName)) {
            echo json_encode(['success' => false, 'error' => 'Equipment name is required']);
            return;
        }

        try {
            $customEquipmentModel = new CustomEquipment();
            $equipmentId = $customEquipmentModel->create((int)$employee['id'], $equipmentName);

            if ($equipmentId > 0) {
                echo json_encode([
                    'success' => true, 
                    'equipment' => [
                        'id' => $equipmentId,
                        'equipment_name' => $equipmentName
                    ],
                    'message' => 'Custom equipment added successfully!'
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Equipment already exists or failed to create']);
            }
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    /**
     * View trainer profile and schedule config page
     * GET: index.php?r=trainer/manageprofile
     */
    public function manageprofileAction(): void
    {
        $data = $this->requireTrainer();
        $user = $data['user'];
        $employee = $data['employee'];

        $pdo = \App\Core\Database::pdo();
        $profile = [];
        $schedules = [];

        try {
            // Fetch bio/specializations
            $stmtProf = $pdo->prepare("SELECT * FROM trainer_profiles WHERE user_id = :uid");
            $stmtProf->execute([':uid' => $user['id']]);
            $profile = $stmtProf->fetch(\PDO::FETCH_ASSOC) ?: [];

            // Fetch trainer's active availability slots
            $stmtSched = $pdo->prepare("SELECT * FROM trainer_schedules WHERE trainer_id = :tid ORDER BY session_date ASC, session_time ASC");
            $stmtSched->execute([':tid' => $employee['id']]);
            $schedules = $stmtSched->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $_SESSION['info'] = 'Trainer tables are not set up yet. Please run the database migration: <a href="sql/run_trainer_directory_migration.php">Run Migration</a>';
        }

        $success = $_SESSION['success'] ?? '';
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['success'], $_SESSION['error']);

        $this->view('trainer/manage_profile', [
            'user' => $user,
            'employee' => $employee,
            'profile' => $profile,
            'schedules' => $schedules,
            'success' => $success,
            'error' => $error
        ]);
    }

    /**
     * Save trainer profile information
     * POST: index.php?r=trainer/saveprofile
     */
    public function saveprofileAction(): void
    {
        $data = $this->requireTrainer();
        $user = $data['user'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('trainer/manageprofile');
        }

        $bio = trim($_POST['bio'] ?? '');
        $expertise = trim($_POST['expertise'] ?? '');
        $certifications = trim($_POST['certifications'] ?? '');

        $pdo = \App\Core\Database::pdo();
        $stmt = $pdo->prepare(
            "INSERT INTO trainer_profiles (user_id, bio, expertise, certifications)
             VALUES (:uid, :bio, :exp, :certs)
             ON DUPLICATE KEY UPDATE bio = :bio2, expertise = :exp2, certifications = :certs2"
        );
        $stmt->execute([
            ':uid' => $user['id'],
            ':bio' => $bio,
            ':exp' => $expertise,
            ':certs' => $certifications,
            ':bio2' => $bio,
            ':exp2' => $expertise,
            ':certs2' => $certifications
        ]);

        $_SESSION['success'] = 'Profile details updated successfully!';
        $this->redirect('trainer/manageprofile');
    }

    /**
     * Add open availability slot
     * POST: index.php?r=trainer/addavailability
     */
    public function addavailabilityAction(): void
    {
        $data = $this->requireTrainer();
        $employee = $data['employee'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('trainer/manageprofile');
        }

        $date = $_POST['session_date'] ?? '';
        $startTimeRaw = $_POST['start_time'] ?? '';
        $endTimeRaw = $_POST['end_time'] ?? '';
        $maxCapacity = max(1, (int)($_POST['max_capacity'] ?? 1));

        if (empty($date) || empty($startTimeRaw) || empty($endTimeRaw)) {
            $_SESSION['error'] = 'Date, start time, and end time are required.';
            $this->redirect('trainer/manageprofile');
        }

        // Format times (e.g. "08:30" -> "08:30 AM")
        $startTimeFormatted = date("h:i A", strtotime($startTimeRaw));
        $endTimeFormatted = date("h:i A", strtotime($endTimeRaw));
        $time = $startTimeFormatted . ' - ' . $endTimeFormatted;

        $pdo = \App\Core\Database::pdo();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO trainer_schedules (trainer_id, session_date, session_time, status, max_capacity, current_bookings)
                 VALUES (:tid, :date, :time, 'available', :max_cap, 0)"
            );
            $stmt->execute([
                ':tid' => $employee['id'],
                ':date' => $date,
                ':time' => $time,
                ':max_cap' => $maxCapacity
            ]);
            $_SESSION['success'] = 'Availability slot added successfully!';
        } catch (\PDOException $e) {
            $_SESSION['error'] = 'This slot has already been added.';
        }

        $this->redirect('trainer/manageprofile');
    }

    /**
     * Remove availability slot
     * POST: index.php?r=trainer/deleteslot
     */
    public function deleteslotAction(): void
    {
        $data = $this->requireTrainer();
        $employee = $data['employee'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('trainer/manageprofile');
        }

        $slotId = (int)($_POST['slot_id'] ?? 0);
        if ($slotId > 0) {
            $pdo = \App\Core\Database::pdo();
            $stmt = $pdo->prepare("DELETE FROM trainer_schedules WHERE id = :id AND trainer_id = :tid");
            $stmt->execute([':id' => $slotId, ':tid' => $employee['id']]);
            $_SESSION['success'] = 'Slot removed successfully.';
        }

        $this->redirect('trainer/manageprofile');
    }

    /**
     * View incoming direct booking requests inbox
     * GET: index.php?r=trainer/requests
     */
    public function requestsAction(): void
    {
        $data = $this->requireTrainer();
        $user = $data['user'];
        $employee = $data['employee'];

        $pdo = \App\Core\Database::pdo();
        $requests = [];

        try {
            $stmt = $pdo->prepare(
                "SELECT fsr.*, u.fullname as member_name, u.email
                 FROM fitness_service_requests fsr
                 JOIN gym_members gm ON gm.id = fsr.member_id
                 JOIN users u ON u.id = gm.user_id
                 WHERE fsr.assigned_trainer_id = :tid AND fsr.status = 'pending'
                 ORDER BY fsr.created_at DESC"
            );
            $stmt->execute([':tid' => $employee['id']]);
            $requests = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $_SESSION['info'] = 'Database migration required. Please run <a href="sql/run_trainer_directory_migration.php">the migration script</a> first.';
        }

        $success = $_SESSION['success'] ?? '';
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['success'], $_SESSION['error']);

        $this->view('trainer/requests', [
            'user'     => $user,
            'employee' => $employee,
            'requests' => $requests,
            'success'  => $success,
            'error'    => $error
        ]);
    }

    /**
     * Process trainer decision on a direct booking request (Accept / Decline)
     * POST: index.php?r=trainer/decisionrequest
     */
    public function decisionrequestAction(): void
    {
        $data = $this->requireTrainer();
        $user = $data['user'];
        $employee = $data['employee'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('trainer/requests');
        }

        $requestId = (int)($_POST['request_id'] ?? 0);
        $decision = $_POST['decision'] ?? '';

        if ($requestId <= 0 || !in_array($decision, ['accept', 'decline'], true)) {
            $_SESSION['error'] = 'Invalid request parameters.';
            $this->redirect('trainer/requests');
        }

        $pdo = \App\Core\Database::pdo();
        
        // Find the request details
        $stmtReq = $pdo->prepare("SELECT * FROM fitness_service_requests WHERE id = :id AND assigned_trainer_id = :tid");
        $stmtReq->execute([':id' => $requestId, ':tid' => $employee['id']]);
        $request = $stmtReq->fetch(\PDO::FETCH_ASSOC);

        if (!$request) {
            $_SESSION['error'] = 'Booking request not found.';
            $this->redirect('trainer/requests');
        }

        // Find the requester user_id for notification
        $stmtMember = $pdo->prepare("SELECT user_id FROM gym_members WHERE id = :mid");
        $stmtMember->execute([':mid' => $request['member_id']]);
        $memberUserId = (int)$stmtMember->fetchColumn();

        $n = new \App\Models\Notification();

        if ($decision === 'accept') {
            // Update request status to 'assigned'
            $pdo->prepare("UPDATE fitness_service_requests SET status = 'assigned' WHERE id = :id")->execute([':id' => $requestId]);

            // Fetch current slot capacity details
            $stmtSlot = $pdo->prepare(
                "SELECT * FROM trainer_schedules 
                 WHERE trainer_id = :tid AND session_date = :bdate AND session_time = :btime"
            );
            $stmtSlot->execute([
                ':tid' => $employee['id'],
                ':bdate' => $request['booking_date'],
                ':btime' => $request['booking_time']
            ]);
            $slot = $stmtSlot->fetch(\PDO::FETCH_ASSOC);

            if ($slot) {
                $newBookings = (int)($slot['current_bookings'] ?? 0) + 1;
                $maxCap = (int)($slot['max_capacity'] ?? 1);
                $isFullyBooked = $newBookings >= $maxCap;

                try {
                    $pdo->prepare(
                        "UPDATE trainer_schedules 
                         SET current_bookings = :cb, status = :status 
                         WHERE id = :id"
                    )->execute([
                        ':cb' => $newBookings,
                        ':status' => $isFullyBooked ? 'booked' : 'available',
                        ':id' => $slot['id']
                    ]);
                } catch (\PDOException $e) {
                    $pdo->prepare(
                        "UPDATE trainer_schedules 
                         SET status = 'booked' 
                         WHERE id = :id"
                    )->execute([':id' => $slot['id']]);
                }

                // Decline other pending requests on this slot ONLY if it is now fully booked
                if ($isFullyBooked) {
                    try {
                        $pdo->prepare(
                            "UPDATE fitness_service_requests SET status = 'cancelled' 
                             WHERE assigned_trainer_id = :tid AND booking_date = :bdate AND booking_time = :btime AND id != :id AND status = 'pending'"
                        )->execute([
                            ':tid' => $employee['id'],
                            ':bdate' => $request['booking_date'],
                            ':btime' => $request['booking_time'],
                            ':id' => $requestId
                        ]);
                    } catch (\Throwable $e) {}
                }
            } else {
                // Fallback for safety
                $pdo->prepare(
                    "UPDATE trainer_schedules SET status = 'booked' 
                     WHERE trainer_id = :tid AND session_date = :bdate AND session_time = :btime"
                )->execute([
                    ':tid' => $employee['id'],
                    ':bdate' => $request['booking_date'],
                    ':btime' => $request['booking_time']
                ]);
            }

            // Create trainer_assignments entry
            try {
                $pdo->prepare(
                    "INSERT INTO trainer_assignments (client_id, trainer_id, assigned_by, status)
                     VALUES (:cid, :tid, :aby, 'active')
                     ON DUPLICATE KEY UPDATE status = 'active'"
                )->execute([
                    ':cid' => $request['member_id'],
                    ':tid' => $employee['id'],
                    ':aby' => $user['id']
                ]);
            } catch (\Exception $e) {}

            // Send success notification to enthusiast
            if ($n->tableExists()) {
                $n->create(
                    $memberUserId,
                    'Coaching Request Accepted!',
                    'Coach ' . htmlspecialchars($user['fullname']) . ' has accepted your coaching request for ' . date('M d, Y', strtotime($request['booking_date'])) . ' at ' . $request['booking_time'] . '.',
                    'success',
                    'fitness/status'
                );
            }

            $_SESSION['success'] = 'Booking request accepted successfully!';

        } else {
            // Decline: update status to 'cancelled'
            $pdo->prepare("UPDATE fitness_service_requests SET status = 'cancelled' WHERE id = :id")->execute([':id' => $requestId]);

            // Free the slot back to available in trainer_schedules (decrement booking count)
            $stmtSlot = $pdo->prepare(
                "SELECT * FROM trainer_schedules 
                 WHERE trainer_id = :tid AND session_date = :bdate AND session_time = :btime"
            );
            $stmtSlot->execute([
                ':tid' => $employee['id'],
                ':bdate' => $request['booking_date'],
                ':btime' => $request['booking_time']
            ]);
            $slot = $stmtSlot->fetch(\PDO::FETCH_ASSOC);

            if ($slot) {
                $newBookings = max(0, (int)($slot['current_bookings'] ?? 0) - 1);
                try {
                    $pdo->prepare(
                        "UPDATE trainer_schedules 
                         SET current_bookings = :cb, status = 'available' 
                         WHERE id = :id"
                    )->execute([
                        ':cb' => $newBookings,
                        ':id' => $slot['id']
                    ]);
                } catch (\PDOException $e) {
                    $pdo->prepare(
                        "UPDATE trainer_schedules 
                         SET status = 'available' 
                         WHERE id = :id"
                    )->execute([':id' => $slot['id']]);
                }
            } else {
                $pdo->prepare(
                    "UPDATE trainer_schedules SET status = 'available', request_id = NULL 
                     WHERE trainer_id = :tid AND session_date = :bdate AND session_time = :btime"
                )->execute([
                    ':tid' => $employee['id'],
                    ':bdate' => $request['booking_date'],
                    ':btime' => $request['booking_time']
                ]);
            }

            // Send notification to enthusiast
            if ($n->tableExists()) {
                $n->create(
                    $memberUserId,
                    'Coaching Request Declined',
                    'Coach ' . htmlspecialchars($user['fullname']) . ' was unable to accept your coaching request for ' . date('M d, Y', strtotime($request['booking_date'])) . ' at ' . $request['booking_time'] . '.',
                    'danger',
                    'fitness/directory'
                );
            }

            $_SESSION['success'] = 'Booking request declined successfully.';
        }

        $this->redirect('trainer/requests');
    }
}

