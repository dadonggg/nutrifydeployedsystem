<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\GymMember;
use App\Models\FitnessServiceRequest;
use App\Models\FitnessClientProfile;
use App\Models\FitnessTrainerPlan;
use App\Models\FitnessWorkoutLog;
use App\Models\FitnessNutritionLog;
use App\Models\FitnessProgressTracking;
use App\Models\FitnessTrainerFeedback;

final class FitnessController extends Controller
{
    /**
     * Require active gym membership to access fitness training features
     */
    private function requireActiveMember(): array
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('auth/login');
        }
        
        $user = (new User())->findById((int)$_SESSION['user_id']);
        if (!$user) {
            unset($_SESSION['user_id']);
            $this->redirect('auth/login');
        }

        // Check if user has active gym membership
        $member = (new GymMember())->findByUserId((int)$user['id']);
        if (!$member || ($member['membership_status'] ?? '') !== 'active') {
            // Redirect to membership application
            $_SESSION['error'] = 'You must have an active gym membership to access fitness training services.';
            $this->redirect('membership/apply');
        }

        return ['user' => $user, 'member' => $member];
    }

    /** Step 1: Service Request Form */
    public function requestAction(): void
    {
        $data = $this->requireActiveMember();
        $user   = $data['user'];
        $member = $data['member'];

        $requestModel = new FitnessServiceRequest();
        $error   = '';
        $success = '';

        // Check if user already has an active request
        if ($requestModel->hasActiveRequest((int)$member['id'])) {
            $_SESSION['info'] = 'You already have an active fitness training request.';
            $this->redirect('fitness/status');
        }

        // ── Fetch assigned trainer name for this member ──────────────────
        // The trainer may have been assigned through gym_members.assigned_trainer_id
        $trainerName = null;
        $assignedTrainerId = (int)($member['assigned_trainer_id'] ?? 0);
        if ($assignedTrainerId > 0) {
            $emp = (new \App\Models\Employee())->findById($assignedTrainerId);
            $trainerName = $emp['fullname'] ?? null;
        }

        // ── Get actual session count from training package ──────────────────
        // Fetch the membership application to get training_package_id
        $membershipApp = null;
        $autoSessions = null;
        $packageLabel = null;
        
        try {
            $pdo = \App\Core\Database::pdo();
            $stmt = $pdo->prepare(
                'SELECT ma.*, ftp.session_count, ftp.package_name, ftp.price as package_price
                 FROM membership_applications ma
                 LEFT JOIN fitness_trainer_packages ftp ON ma.training_package_id = ftp.id
                 WHERE ma.user_id = :user_id AND ma.status = "approved"
                 ORDER BY ma.created_at DESC LIMIT 1'
            );
            $stmt->execute([':user_id' => $user['id']]);
            $membershipApp = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($membershipApp && !empty($membershipApp['session_count'])) {
                $autoSessions = (int)$membershipApp['session_count'];
                $packagePrice = (float)($membershipApp['package_price'] ?? 0);
                $packageName = $membershipApp['package_name'] ?? 'Training Package';
                $packageLabel = $packageName . ' — ' . $autoSessions . ' Session' . ($autoSessions > 1 ? 's' : '');
                if ($packagePrice > 0) {
                    $packageLabel = '₱' . number_format($packagePrice, 0) . ' — ' . $packageLabel;
                }
            }
        } catch (\PDOException $e) {
            // Fallback to old method if query fails
            error_log('Error fetching training package: ' . $e->getMessage());
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // ── Training types ───────────────────────────────────────────
            $trainingTypes = [];
            if (!empty($_POST['training_personal'])) $trainingTypes[] = 'personal_training';
            if (!empty($_POST['training_pilates']))  $trainingTypes[] = 'pilates';
            if (!empty($_POST['training_yoga']))     $trainingTypes[] = 'yoga';
            if (!empty($_POST['training_strength'])) $trainingTypes[] = 'strength';
            if (!empty($_POST['training_cardio']))   $trainingTypes[] = 'cardio';

            // "Others" handling — save custom text, not the word "Others"
            $trainingCustom = trim((string)($_POST['training_type_custom'] ?? ''));
            if (!empty($_POST['training_others'])) {
                if ($trainingCustom === '') {
                    $error = 'Please specify your training type.';
                } else {
                    $trainingTypes[] = $trainingCustom; // save the custom text
                }
            }

            // ── Schedule: day + time JSON ─────────────────────────────────
            $scheduleJson = '[]';
            $scheduleArr  = [];
            $daysPosted   = $_POST['day'] ?? [];
            $timesPosted  = $_POST['time'] ?? [];
            $allDays      = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

            foreach ($allDays as $day) {
                if (!empty($daysPosted[$day])) {
                    $timeSlot = $timesPosted[$day] ?? '';
                    if ($timeSlot === '') {
                        $error = 'Please select a time slot for every checked day.';
                        break;
                    }
                    $scheduleArr[] = ['day' => $day, 'time' => $timeSlot];
                }
            }

            if ($error === '' && empty($scheduleArr)) {
                $error = 'Please select at least one training day.';
            }

            if ($error === '' && empty($trainingTypes)) {
                $error = 'Please select at least one training type.';
            }

            if ($error === '') {
                $scheduleJson = json_encode($scheduleArr, JSON_UNESCAPED_UNICODE);

                // ── Build normalised address string for backward-compat address col ──
                $street   = trim((string)($_POST['street']   ?? ''));
                $barangay = trim((string)($_POST['barangay'] ?? ''));
                $district = trim((string)($_POST['district'] ?? ''));
                $city     = trim((string)($_POST['city']     ?? ''));
                $province = trim((string)($_POST['province'] ?? ''));
                $fullAddress = implode(', ', array_filter([$street, $barangay, $district, $city, $province]));

                $requestData = [
                    'full_name'              => trim((string)($_POST['full_name'] ?? '')),
                    'address'                => $fullAddress,          // legacy col
                    'street'                 => $street,
                    'barangay'               => $barangay,
                    'district'               => $district,
                    'city'                   => $city,
                    'province'               => $province,
                    'phone'                  => trim((string)($_POST['phone'] ?? '')),
                    'email'                  => trim((string)($_POST['email'] ?? '')),
                    'training_type'          => implode(',', $trainingTypes),
                    'training_type_custom'   => $trainingCustom,
                    'session_preference'     => $_POST['session_preference'] ?? '1',
                    'training_preference'    => implode(',', array_column($scheduleArr, 'day')), // legacy
                    'schedule_preference_json' => $scheduleJson,
                    'specific_trainer_request' => trim((string)($_POST['specific_trainer_request'] ?? ''))
                ];

                $requestId = $requestModel->create((int)$member['id'], $requestData);
                if ($requestId > 0) {
                    $success = 'Your fitness training request has been submitted! An admin officer will assign a trainer soon.';
                    $_SESSION['success'] = $success;
                    $this->redirect('fitness/status');
                } else {
                    $error = 'Failed to submit request. Please try again.';
                }
            }
        }

        $this->view('member/fitness_request', [
            'user'         => $user,
            'member'       => $member,
            'trainerName'  => $trainerName,
            'autoSessions' => $autoSessions,
            'packageLabel' => $packageLabel,
            'error'        => $error,
            'success'      => $success
        ]);
    }

    /** View request status and access next steps */
    public function statusAction(): void
    {
        $data = $this->requireActiveMember();
        $user = $data['user'];
        $member = $data['member'];

        $requestModel = new FitnessServiceRequest();
        $requests = $requestModel->findByMemberId((int)$member['id']);

        $this->view('member/fitness_status', [
            'user' => $user,
            'member' => $member,
            'requests' => $requests
        ]);
    }

    /** Step 2: Client Profile Form (unlocked after trainer assigned) or Trainer Profile */
    public function profileAction(): void
    {
        if (isset($_GET['trainer_id'])) {
            $this->showTrainerPublicProfile();
        } else {
            $this->showClientProfileForm();
        }
    }

    private function showClientProfileForm(): void
    {
        $data = $this->requireActiveMember();
        $user = $data['user'];
        $member = $data['member'];

        $requestId = (int)($_GET['request_id'] ?? 0);
        if ($requestId === 0) {
            $this->redirect('fitness/status');
        }

        $requestModel = new FitnessServiceRequest();
        $request = $requestModel->findById($requestId);

        if (!$request || (int)$request['member_id'] !== (int)$member['id']) {
            $this->redirect('fitness/status');
        }

        // Check if trainer is assigned
        if ($request['status'] !== 'assigned') {
            $_SESSION['error'] = 'Please wait for a trainer to be assigned before filling your profile.';
            $this->redirect('fitness/status');
        }

        $profileModel = new FitnessClientProfile();
        $profile = $profileModel->findByServiceRequestId($requestId);
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fitnessGoals = [];
            if (!empty($_POST['goal_weight_loss'])) $fitnessGoals[] = 'weight_loss';
            if (!empty($_POST['goal_muscle_gain'])) $fitnessGoals[] = 'muscle_gain';
            if (!empty($_POST['goal_endurance'])) $fitnessGoals[] = 'endurance';
            if (!empty($_POST['goal_flexibility'])) $fitnessGoals[] = 'flexibility';
            if (!empty($_POST['goal_wellness'])) $fitnessGoals[] = 'general_wellness';

            $profileData = [
                'age' => !empty($_POST['age']) ? (int)$_POST['age'] : null,
                'gender' => $_POST['gender'] ?? null,
                'height_cm' => !empty($_POST['height_cm']) ? (float)$_POST['height_cm'] : null,
                'weight_kg' => !empty($_POST['weight_kg']) ? (float)$_POST['weight_kg'] : null,
                'fitness_goals' => implode(',', $fitnessGoals),
                'medical_conditions' => trim((string)($_POST['medical_conditions'] ?? '')),
                'activity_level' => $_POST['activity_level'] ?? null,
                'dietary_preferences' => trim((string)($_POST['dietary_preferences'] ?? ''))
            ];

            if ($profile) {
                // Update existing profile
                if ($profileModel->update((int)$profile['id'], $profileData)) {
                    $success = 'Profile updated successfully!';
                    $_SESSION['success'] = $success;
                    $this->redirect('fitness/status');
                } else {
                    $error = 'Failed to update profile.';
                }
            } else {
                // Create new profile
                $profileId = $profileModel->create($requestId, (int)$member['id'], $profileData);
                if ($profileId > 0) {
                    $success = 'Profile created successfully! Your trainer will now create a personalized plan for you.';
                    $_SESSION['success'] = $success;
                    $this->redirect('fitness/status');
                } else {
                    $error = 'Failed to create profile.';
                }
            }
        }

        $this->view('member/fitness_profile', [
            'user' => $user,
            'member' => $member,
            'request' => $request,
            'profile' => $profile,
            'error' => $error,
            'success' => $success
        ]);
    }

    /** Step 3: View Plan & Log Workouts/Nutrition */
    public function planAction(): void
    {
        $data = $this->requireActiveMember();
        $user = $data['user'];
        $member = $data['member'];

        $requestId = (int)($_GET['request_id'] ?? 0);
        if ($requestId === 0) {
            $this->redirect('fitness/status');
        }

        $requestModel = new FitnessServiceRequest();
        $request = $requestModel->findById($requestId);

        if (!$request || (int)$request['member_id'] !== (int)$member['id']) {
            $this->redirect('fitness/status');
        }

        $pdo = \App\Core\Database::pdo();
        
        // Check for trainer-edited AI plan (new system - Phase 3)
        $stmt = $pdo->prepare(
            'SELECT 
                ftwp.workout_schedule,
                ftmp.daily_macros,
                ftmp.meals,
                ftwp.sent_at,
                fas.ai_notes,
                fas.ai_model
             FROM fitness_trainer_workout_plans ftwp
             LEFT JOIN fitness_trainer_meal_plans ftmp 
                ON ftmp.service_request_id = ftwp.service_request_id
             LEFT JOIN fitness_ai_suggestions fas
                ON fas.service_request_id = ftwp.service_request_id
             WHERE ftwp.service_request_id = :request_id 
                AND ftwp.status = "sent"
             LIMIT 1'
        );
        $stmt->execute([':request_id' => $requestId]);
        $aiPlan = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($aiPlan) {
            // Use new AI-edited plan
            $plan = [
                'id' => $requestId,
                'fitness_plan_monday' => $aiPlan['workout_schedule'],
                'target_calories' => json_decode($aiPlan['daily_macros'] ?? '{}', true)['calories'] ?? 2000,
                'target_protein_g' => json_decode($aiPlan['daily_macros'] ?? '{}', true)['protein'] ?? 150,
                'target_carbs_g' => json_decode($aiPlan['daily_macros'] ?? '{}', true)['carbs'] ?? 200,
                'target_fats_g' => json_decode($aiPlan['daily_macros'] ?? '{}', true)['fats'] ?? 65,
                'meal_suggestions' => $this->formatMealSuggestions(json_decode($aiPlan['meals'] ?? '[]', true)),
                'nutrition_notes' => 'AI-powered personalized plan',
                'fitness_plan_notes' => $aiPlan['ai_notes'] ?? '',
                'trainer_name' => $request['trainer_name'] ?? 'Your Trainer',
                'fitness_level' => 'Personalized',
                'status' => 'sent',
                'is_ai_plan' => true,
                'ai_model' => $aiPlan['ai_model'] ?? 'llama3.2'
            ];
        } else {
            // Fall back to legacy plan system
            $planModel = new FitnessTrainerPlan();
            $plan = $planModel->findByServiceRequestId($requestId);

            if (!$plan || $plan['status'] !== 'sent') {
                $_SESSION['error'] = 'Please wait for your trainer to create and send your personalized plan.';
                $this->redirect('fitness/status');
            }
            $plan['is_ai_plan'] = false;
        }

        // Get workout and nutrition logs
        $workoutModel = new FitnessWorkoutLog();
        $nutritionModel = new FitnessNutritionLog();
        $workoutLogs = $workoutModel->findByServiceRequestId($requestId, 20);
        $nutritionLogs = $nutritionModel->findByServiceRequestId($requestId, 20);

        $this->view('member/fitness_plan', [
            'user' => $user,
            'member' => $member,
            'request' => $request,
            'plan' => $plan,
            'workoutLogs' => $workoutLogs,
            'nutritionLogs' => $nutritionLogs
        ]);
    }
    
    /**
     * Format meal suggestions from array to text
     */
    private function formatMealSuggestions(array $meals): string
    {
        $formatted = '';
        $currentType = '';
        
        foreach ($meals as $meal) {
            $type = $meal['type'] ?? 'Meal';
            if ($type !== $currentType) {
                $formatted .= "\n" . strtoupper($type) . ":\n";
                $currentType = $type;
            }
            $formatted .= sprintf(
                "- %s: %s (%d %s)\n",
                $meal['time'] ?? '',
                $meal['foodName'] ?? '',
                $meal['amount'] ?? 0,
                $meal['unit'] ?? 'g'
            );
        }
        
        return trim($formatted);
    }

    /** Add workout log entry (AJAX) */
    public function addWorkoutAction(): void
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            return;
        }

        $data = $this->requireActiveMember();
        $member = $data['member'];

        $requestId = (int)($_POST['request_id'] ?? 0);
        $workoutData = [
            'log_date' => $_POST['log_date'] ?? date('Y-m-d'),
            'exercise_name' => trim((string)($_POST['exercise_name'] ?? '')),
            'sets' => (int)($_POST['sets'] ?? 0),
            'reps' => (int)($_POST['reps'] ?? 0),
            'weight_kg' => (float)($_POST['weight_kg'] ?? 0),
            'duration_minutes' => (int)($_POST['duration_minutes'] ?? 0),
            'notes' => trim((string)($_POST['notes'] ?? ''))
        ];

        if (empty($workoutData['exercise_name'])) {
            echo json_encode(['success' => false, 'error' => 'Exercise name is required']);
            return;
        }

        $workoutModel = new FitnessWorkoutLog();
        $logId = $workoutModel->create((int)$member['id'], $requestId, $workoutData);

        if ($logId > 0) {
            echo json_encode(['success' => true, 'message' => 'Workout logged successfully!', 'id' => $logId]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to log workout']);
        }
    }

    /** Add nutrition log entry (AJAX) */
    public function addNutritionAction(): void
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            return;
        }

        $data = $this->requireActiveMember();
        $member = $data['member'];

        $requestId = (int)($_POST['request_id'] ?? 0);
        $nutritionData = [
            'log_date' => $_POST['log_date'] ?? date('Y-m-d'),
            'meal_type' => $_POST['meal_type'] ?? 'breakfast',
            'food_item' => trim((string)($_POST['food_item'] ?? '')),
            'calories' => (int)($_POST['calories'] ?? 0),
            'protein_g' => (float)($_POST['protein_g'] ?? 0),
            'carbs_g' => (float)($_POST['carbs_g'] ?? 0),
            'fats_g' => (float)($_POST['fats_g'] ?? 0),
            'notes' => trim((string)($_POST['notes'] ?? ''))
        ];

        if (empty($nutritionData['food_item'])) {
            echo json_encode(['success' => false, 'error' => 'Food item is required']);
            return;
        }

        $nutritionModel = new FitnessNutritionLog();
        $logId = $nutritionModel->create((int)$member['id'], $requestId, $nutritionData);

        if ($logId > 0) {
            echo json_encode(['success' => true, 'message' => 'Nutrition logged successfully!', 'id' => $logId]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to log nutrition']);
        }
    }

    /** Step 4: Progress Tracking */
    public function progressAction(): void
    {
        $data = $this->requireActiveMember();
        $user = $data['user'];
        $member = $data['member'];

        $requestId = (int)($_GET['request_id'] ?? 0);
        if ($requestId === 0) {
            $this->redirect('fitness/status');
        }

        $requestModel = new FitnessServiceRequest();
        $request = $requestModel->findById($requestId);

        if (!$request || (int)$request['member_id'] !== (int)$member['id']) {
            $this->redirect('fitness/status');
        }

        // Get current progress
        $progressModel = new FitnessProgressTracking();
        $currentProgress = $progressModel->getCurrentProgress($requestId);
        
        // Get workout frequency data
        $workoutModel = new FitnessWorkoutLog();
        $weeklyFrequency = $workoutModel->getWeeklyFrequency($requestId, 8);
        
        // Get feedback from trainer
        $feedbackModel = new FitnessTrainerFeedback();
        $feedbacks = $feedbackModel->findByServiceRequestId($requestId);

        // Handle send progress to trainer
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_progress'])) {
            $progressId = $progressModel->calculateAndSave((int)$member['id'], $requestId);
            if ($progressId > 0 && $progressModel->sendToTrainer($progressId)) {
                // Notify the trainer
                $trainerId = (int)($request['assigned_trainer_id'] ?? 0);
                if ($trainerId > 0) {
                    $employeeModel = new \App\Models\Employee();
                    $trainer = $employeeModel->findById($trainerId);
                    if ($trainer) {
                        $trainerUser = (new User())->findById((int)$trainer['user_id']);
                        if ($trainerUser) {
                            $notificationModel = new \App\Models\Notification();
                            $notificationModel->create(
                                (int)$trainerUser['id'],
                                'New Progress Update! 📊',
                                $user['fullname'] . ' has sent their progress update. Review their consistency score and provide feedback!',
                                'info',
                                'trainer/progress'
                            );
                        }
                    }
                }
                
                $_SESSION['success'] = 'Progress sent to your trainer successfully!';
                $this->redirect('fitness/progress&request_id=' . $requestId);
            } else {
                $_SESSION['error'] = 'Failed to send progress. Please try again.';
                $this->redirect('fitness/progress&request_id=' . $requestId);
            }
        }

        $this->view('member/fitness_progress', [
            'user' => $user,
            'member' => $member,
            'request' => $request,
            'currentProgress' => $currentProgress,
            'weeklyFrequency' => $weeklyFrequency,
            'feedbacks' => $feedbacks
        ]);
    }

    /**
     * Generate AI fitness plan from client profile
     * POST: index.php?r=fitness/generateAiPlan
     */
    public function generateAiPlanAction(): void
    {
        // Disable display_errors to prevent HTML in JSON response
        ini_set('display_errors', '0');
        error_reporting(E_ALL);
        ini_set('log_errors', '1');
        
        header('Content-Type: application/json');
        
        // Check authentication
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            return;
        }
        
        try {
            // Get request data
            $input = json_decode(file_get_contents('php://input'), true);
            $requestId = (int)($input['request_id'] ?? 0);
            
            if ($requestId === 0) {
                echo json_encode(['success' => false, 'error' => 'Invalid request ID']);
                return;
            }
            
            // Get service request
            $requestModel = new FitnessServiceRequest();
            $request = $requestModel->findById($requestId);
            
            if (!$request) {
                echo json_encode(['success' => false, 'error' => 'Service request not found']);
                return;
            }
            
            // Get client profile
            $profileModel = new FitnessClientProfile();
            $profile = $profileModel->findByServiceRequestId($requestId);
            
            if (!$profile) {
                echo json_encode(['success' => false, 'error' => 'Profile not found. Please ask member to complete their profile first.']);
                return;
            }
            
            // Use Gemini AI Service (cloud-based, always available)
            $ollamaService = new \App\Services\GeminiService();
            
            // Prepare client data for AI
            $clientData = [
                'name' => $request['member_name'] ?? 'Member',
                'age' => $profile['age'] ?? 30,
                'fitness_goals' => $profile['fitness_goals'] ?? 'general_fitness',
                'activity_level' => $profile['activity_level'] ?? 'Moderate',
                'medical_conditions' => $profile['medical_conditions'] ?? '',
                'dietary_preferences' => $profile['dietary_preferences'] ?? '',
                'sessions_per_week' => 3
            ];
            
            // Generate AI plan via Gemini
            $result = $ollamaService->generateFitnessPlan($clientData);
            
            if (!$result['success']) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to generate AI plan: ' . ($result['error'] ?? 'Unknown error')
                ]);
                return;
            }
            
            // Success - return plan
            echo json_encode([
                'success' => true,
                'message' => 'AI plan generated successfully!',
                'generation_time' => $result['generationTime'] ?? 0,
                'model' => $result['model'] ?? 'llama3.2',
                'workoutPlan' => $result['plan']['workoutRoutine'] ?? null,
                'mealPlan' => $result['plan']['mealPlan'] ?? null,
                'aiNotes' => $result['plan']['aiNotes'] ?? null
            ]);
            
        } catch (\Error $e) {
            // Fatal errors (missing classes, etc.)
            error_log('Fatal error in generateAiPlan: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            echo json_encode([
                'success' => false,
                'error' => 'Server configuration error',
                'details' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            // Regular exceptions
            error_log('Exception in generateAiPlan: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            echo json_encode([
                'success' => false,
                'error' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Check AI service status
     * GET: index.php?r=fitness/checkAiStatus
     */
    public function checkAiStatusAction(): void
    {
        header('Content-Type: application/json');
        
        try {
            $ollamaService = new \App\Services\GeminiService();
            $status = $ollamaService->checkOllamaStatus();
            
            echo json_encode($status);
        } catch (\Exception $e) {
            echo json_encode([
                'available' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * View List of Trainers Directory
     * GET: index.php?r=fitness/directory
     */
    public function directoryAction(): void
    {
        // Any logged-in user can browse the trainer directory
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('auth/login');
        }
        $user = (new \App\Models\User())->findById((int)$_SESSION['user_id']);
        if (!$user) {
            $this->redirect('auth/login');
        }

        $pdo = \App\Core\Database::pdo();
        $trainers = [];

        try {
            $stmt = $pdo->query(
                "SELECT e.id as employee_id, u.id as user_id, u.fullname, u.profile_picture_url, 
                        tp.bio, tp.expertise, tp.certifications,
                        ld.gym_name,
                        COALESCE(AVG(tr.rating), 0) as avg_rating,
                        COUNT(DISTINCT tr.id) as review_count,
                        COUNT(DISTINCT ta.id) as client_count
                 FROM users u
                 JOIN employees e ON e.user_id = u.id
                 LEFT JOIN trainer_profiles tp ON tp.user_id = u.id
                 LEFT JOIN users owner ON owner.id = e.hired_by
                 LEFT JOIN legal_documents ld ON ld.user_id = owner.id
                 LEFT JOIN trainer_reviews tr ON tr.trainer_id = e.id
                 LEFT JOIN trainer_assignments ta ON ta.trainer_id = e.id
                 WHERE u.role IN ('trainer', 'fitness_trainer') AND e.position = 'trainer'
                 GROUP BY e.id"
            );
            $trainers = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Compute feedback response rate for each trainer
            $analyticsModel = new \App\Models\ProgramSuccessAnalytics();
            foreach ($trainers as &$t) {
                $m = $analyticsModel->getTrainerFeedbackMetrics((int)$t['employee_id'], (int)($t['user_id'] ?? 0));
                $t['feedback_response_rate'] = $m['R_fb'] ?? 0.0;
                $t['feedback_count'] = $m['N_fb'] ?? 0;
            }
            unset($t);
        } catch (\Exception $e) {
            // Tables may not exist yet — show empty directory with a notice
            $_SESSION['info'] = 'Trainer directory tables not set up yet. Please run the database migration first.';
        }

        $this->view('trainer/directory', [
            'user'     => $user,
            'trainers' => $trainers
        ]);
    }

    private function showTrainerPublicProfile(): void
    {
        $memberData = $this->requireActiveMember();
        $user = $memberData['user'];

        $trainerId = (int)($_GET['trainer_id'] ?? 0);
        if ($trainerId <= 0) {
            $this->redirect('fitness/directory');
        }

        $pdo = \App\Core\Database::pdo();
        
        $stmt = $pdo->prepare(
            "SELECT e.id as employee_id, u.id as user_id, u.fullname, u.profile_picture_url, u.email,
                    COALESCE(AVG(tr.rating), 0) as avg_rating,
                    COUNT(DISTINCT tr.id) as review_count
             FROM users u
             JOIN employees e ON e.user_id = u.id
             LEFT JOIN trainer_reviews tr ON tr.trainer_id = e.id
             WHERE e.id = :id
             GROUP BY e.id"
        );
        $stmt->execute([':id' => $trainerId]);
        $trainer = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$trainer) {
            $this->redirect('fitness/directory');
        }

        // Fetch bio/expertise/certifications
        $stmtProf = $pdo->prepare("SELECT * FROM trainer_profiles WHERE user_id = (SELECT user_id FROM employees WHERE id = :id)");
        $stmtProf->execute([':id' => $trainerId]);
        $profile = $stmtProf->fetch(\PDO::FETCH_ASSOC) ?: [];

        // Fetch availability slots
        $stmtSched = $pdo->prepare("SELECT * FROM trainer_schedules WHERE trainer_id = :id ORDER BY session_date ASC, session_time ASC");
        $stmtSched->execute([':id' => $trainerId]);
        $schedules = $stmtSched->fetchAll(\PDO::FETCH_ASSOC);

        // Fetch reviews
        $stmtRev = $pdo->prepare(
            "SELECT tr.*, u.fullname FROM trainer_reviews tr
             JOIN gym_members gm ON gm.id = tr.member_id
             JOIN users u ON u.id = gm.user_id
             WHERE tr.trainer_id = :id
             ORDER BY tr.created_at DESC"
        );
        $stmtRev->execute([':id' => $trainerId]);
        $reviews = $stmtRev->fetchAll(\PDO::FETCH_ASSOC);

        // Fetch specific trainer feedback response rate metrics
        $feedbackMetrics = null;
        try {
            $analyticsModel = new \App\Models\ProgramSuccessAnalytics();
            $feedbackMetrics = $analyticsModel->getTrainerFeedbackMetrics((int)$trainer['employee_id'], (int)($trainer['user_id'] ?? 0));
        } catch (\Throwable $e) {
            $feedbackMetrics = null;
        }

        $success = $_SESSION['success'] ?? '';
        $error = $_SESSION['error'] ?? '';
        unset($_SESSION['success'], $_SESSION['error']);

        $this->view('trainer/profile', [
            'user' => $user,
            'trainer' => $trainer,
            'profile' => $profile,
            'schedules' => $schedules,
            'reviews' => $reviews,
            'feedbackMetrics' => $feedbackMetrics,
            'success' => $success,
            'error' => $error
        ]);
    }

    /**
     * Submit a direct booking request to a trainer
     * POST: index.php?r=fitness/booktrainer
     */
    public function booktrainerAction(): void
    {
        $memberData = $this->requireActiveMember();
        $member = $memberData['member'];
        $user = $memberData['user'];

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('fitness/directory');
        }

        $scheduleId = (int)($_POST['schedule_id'] ?? 0);
        $trainerId = (int)($_POST['trainer_id'] ?? 0);

        if ($scheduleId <= 0 || $trainerId <= 0) {
            $_SESSION['error'] = 'Invalid booking parameters.';
            $this->redirect('fitness/profile&trainer_id=' . $trainerId);
        }

        $pdo = \App\Core\Database::pdo();
        // Check slot availability
        $stmtSlot = $pdo->prepare("SELECT * FROM trainer_schedules WHERE id = :id AND trainer_id = :tid AND status = 'available'");
        $stmtSlot->execute([':id' => $scheduleId, ':tid' => $trainerId]);
        $slot = $stmtSlot->fetch(\PDO::FETCH_ASSOC);

        if (!$slot) {
            $_SESSION['error'] = 'Selected slot is no longer available.';
            $this->redirect('fitness/profile&trainer_id=' . $trainerId);
        }

        // Insert direct request into fitness_service_requests
        $trainingType = trim((string)($_POST['training_type'] ?? 'personal_training'));
        if ($trainingType === '') $trainingType = 'personal_training';

        $stmtReq = $pdo->prepare(
            "INSERT INTO fitness_service_requests
                (member_id, full_name, email, phone, status, booking_date, booking_time,
                 assigned_trainer_id, training_type, session_preference, training_preference, address, city, created_at)
             VALUES
                (:mid, :fullname, :email, :phone, 'pending', :bdate, :btime,
                 :tid, :ttype, '1', '', '', '', NOW())"
        );
        $stmtReq->execute([
            ':mid'      => (int)$member['id'],
            ':fullname' => $user['fullname'],
            ':email'    => $user['email'],
            ':phone'    => array_key_exists('phone', $member) ? $member['phone'] : '',
            ':bdate'    => $slot['session_date'],
            ':btime'    => $slot['session_time'],
            ':tid'      => $trainerId,
            ':ttype'    => $trainingType
        ]);
        $requestId = (int)$pdo->lastInsertId();

        // Update slot to associate request (stay available until trainer accepts)
        $stmtSlotUpdate = $pdo->prepare("UPDATE trainer_schedules SET request_id = :rid WHERE id = :id");
        $stmtSlotUpdate->execute([':rid' => $requestId, ':id' => $scheduleId]);

        // Get trainer user_id for notifications
        $stmtTrainerUser = $pdo->prepare("SELECT user_id FROM employees WHERE id = :tid");
        $stmtTrainerUser->execute([':tid' => $trainerId]);
        $trainerUserId = (int)$stmtTrainerUser->fetchColumn();

        // Notify Trainer
        $n = new \App\Models\Notification();
        if ($n->tableExists()) {
            $n->create(
                $trainerUserId,
                'New Coaching Booking Request',
                htmlspecialchars($user['fullname']) . ' requested a coaching session on ' . date('M d, Y', strtotime($slot['session_date'])) . ' at ' . $slot['session_time'] . '.',
                'info',
                'trainer/requests'
            );
        }

        $_SESSION['success'] = 'Booking request submitted successfully! Pending trainer acceptance.';
        $this->redirect('fitness/profile&trainer_id=' . $trainerId);
    }
}
