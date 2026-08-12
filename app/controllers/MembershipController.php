<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\MembershipApplication;
use App\Models\GymMember;
use App\Models\AttendanceLog;
use App\Models\MembershipPlan;
use App\Models\GymService;
use App\Models\FitnessProgram;
use App\Models\LegalDocument;

final class MembershipController extends Controller
{
    private function requireLogin(): array
    {
        if (!isset($_SESSION['user_id'])) { $this->redirect('auth/login'); }
        $user = (new User())->findById((int)$_SESSION['user_id']);
        if (!$user) { unset($_SESSION['user_id']); $this->redirect('auth/login'); }
        return $user;
    }

    /** Customer applies for gym membership */
    public function applyAction(): void
    {
        $user = $this->requireLogin();
        
        // Get gym_id from URL parameter
        $gymId = (int)($_GET['gym_id'] ?? 0);
        if ($gymId === 0) {
            $this->redirect('membership/gyms');
        }
        
        // Get gym details from legal_documents
        $legalDocModel = new \App\Models\LegalDocument();
        $gym = $legalDocModel->findById($gymId);
        
        if (!$gym || $gym['status'] !== 'verified') {
            $this->redirect('membership/gyms');
        }
        
        $error = ''; $success = '';
        $appModel = new MembershipApplication();
        $existing = $appModel->findByUserId((int)$user['id']);

        // Load dynamic plans and services for this gym
        $planModel = new MembershipPlan();
        $svcModel = new GymService();
        $packageModel = new \App\Models\FitnessTrainerPackage();
        $plans = $planModel->tableExists() ? $planModel->findByOwnerId((int)$gym['user_id']) : [];
        $services = $svcModel->tableExists() ? $svcModel->findByOwnerId((int)$gym['user_id']) : [];
        $trainerPackages = $packageModel->findByGymOwner((int)$gym['user_id'], true); // Only active packages

        // Generate PayMongo payment link if application is verified and payment mode is online
        $paymongoLink = null;
        if ($existing && $existing['status'] === 'verified' && ($existing['payment_mode'] ?? 'cash') === 'online') {
            $paymongoLink = $this->generatePayMongoLink($existing, (int)$gym['user_id']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $fn = trim((string)($_POST['first_name'] ?? ''));
            $ln = trim((string)($_POST['last_name'] ?? ''));
            $mi = trim((string)($_POST['middle_initial'] ?? ''));
            $phone = trim((string)($_POST['phone_number'] ?? ''));
            $membershipPlanId = !empty($_POST['membership_plan_id']) ? (int)$_POST['membership_plan_id'] : null;
            $trainingPackageId = !empty($_POST['training_package_id']) ? (int)$_POST['training_package_id'] : null;
            $paymentMode = trim((string)($_POST['payment_mode'] ?? 'cash'));
            $action = $_POST['action'] ?? 'submit';

            // Calculate total price and build payment description
            $paymentAmount = 0.0;
            $paymentType = '';
            $paymentDetails = [];
            
            // Get membership plan details
            if ($membershipPlanId) {
                $plan = $planModel->findById($membershipPlanId);
                if ($plan) {
                    $planPrice = round((float)$plan['price'], 2);
                    $paymentAmount += $planPrice;
                    $paymentDetails[] = $plan['name'] . ' (₱' . number_format($planPrice, 2) . ')';
                } else {
                    $error = 'Invalid membership plan selected.';
                }
            } else {
                $error = 'Please select a membership plan.';
            }
            
            // Get training package details (optional)
            if ($trainingPackageId && $error === '') {
                $package = $packageModel->findById($trainingPackageId);
                if ($package) {
                    $packagePrice = round((float)$package['price'], 2);
                    $paymentAmount += $packagePrice;
                    $paymentDetails[] = $package['package_name'] . ' (₱' . number_format($packagePrice, 2) . ')';
                } else {
                    $error = 'Invalid training package selected.';
                }
            }

            // Get gym service details (optional, e.g. Zumba, Sauna, Yoga, Group Classes)
            $gymServiceId = !empty($_POST['gym_service_id']) ? (int)$_POST['gym_service_id'] : null;
            if ($gymServiceId && $error === '') {
                $svc = $svcModel->findById($gymServiceId);
                if ($svc) {
                    $svcPrice = round((float)($svc['member_price'] ?? 0), 2);
                    $paymentAmount += $svcPrice;
                    $paymentDetails[] = $svc['name'] . ' (₱' . number_format($svcPrice, 2) . ')';
                } else {
                    $error = 'Invalid gym service selected.';
                }
            }
            
            // Build payment type description
            if ($error === '') {
                $paymentType = implode(' + ', $paymentDetails);
            }

            if ($error === '') {
                if ($fn === '' || $ln === '') { $error = 'First and last name are required.'; }
                elseif ($phone === '') { $error = 'Phone number is required.'; }
                else {
                    // Trainer will be assigned by admin officer if training package is selected
                    $trainerId = null;
                    if ($action === 'resubmit' && $existing && in_array($existing['status'], ['rejected', 'resubmit'], true)) {
                        $appModel->resubmitWithPlanAndPackage(
                            (int)$existing['id'], 
                            $fn, $ln, $mi, $phone, 
                            $trainerId, 
                            $paymentType, 
                            $membershipPlanId, 
                            $trainingPackageId, 
                            $paymentAmount, 
                            $paymentMode, 
                            (int)$gym['user_id']
                        );
                        $success = 'Application resubmitted! Waiting for review.';
                    } else {
                        if ($appModel->hasActiveApplication((int)$user['id'])) {
                            $error = 'You already have an active membership application.';
                        } else {
                            $appModel->createWithPlanAndPackage(
                                (int)$user['id'], 
                                $fn, $ln, $mi, $phone, 
                                $trainerId, 
                                $paymentType, 
                                $membershipPlanId, 
                                $trainingPackageId, 
                                $paymentAmount, 
                                $paymentMode, 
                                (int)$gym['user_id']
                            );
                            $success = 'Membership application submitted! Waiting for approval.';
                        }
                    }
                    $existing = $appModel->findByUserId((int)$user['id']);
                }
            }
        }

        $this->view('membership/apply', [
            'user' => $user, 'error' => $error, 'success' => $success,
            'memberApp' => $existing, 'plans' => $plans, 'services' => $services,
            'gym' => $gym, 'paymongoLink' => $paymongoLink,
            'trainerPackages' => $trainerPackages,
        ]);
    }

    /**
     * Generate PayMongo payment link for verified application
     */
    private function generatePayMongoLink(array $application, int $gymOwnerId): ?string
    {
        // Check if PayMongo is configured for this gym owner
        $paymongoModel = new \App\Models\PayMongoConfig();
        if (!$paymongoModel->tableExists()) {
            return null;
        }

        $config = $paymongoModel->findByOwnerId($gymOwnerId);
        if (!$config || !$config['is_active']) {
            return null;
        }

        $secretKey = $config['secret_key'];
        $amount = (float)($application['payment_amount'] ?? 0);
        $description = 'Gym Membership - ' . ($application['payment_type'] ?? 'Membership');
        $remarks = 'membership_app_' . $application['id'];

        // Create PayMongo payment link
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
                    // Save payment reference
                    $paymentId = $data['data']['id'] ?? null;
                    if ($paymentId) {
                        $db = \App\Core\Database::pdo();
                        $stmt = $db->prepare(
                            'UPDATE membership_applications SET paymongo_payment_id = ? WHERE id = ?'
                        );
                        $stmt->execute([$paymentId, $application['id']]);
                    }
                    return $data['data']['attributes']['checkout_url'];
                }
            }
        } catch (\Exception $e) {
            // Log error but don't show to user
            error_log('PayMongo API Error: ' . $e->getMessage());
        }

        return null;
    }

    /** List all verified gyms for membership application */
    public function gymsAction(): void
    {
        $user = $this->requireLogin();
        
        $legalDocModel = new \App\Models\LegalDocument();
        $gyms = $legalDocModel->findAllVerified();
        
        $this->view('membership/gyms', [
            'user' => $user,
            'gyms' => $gyms,
        ]);
    }

    /** Show the full Gym Profile page with all details */
    public function gymprofileAction(): void
    {
        $user  = $this->requireLogin();
        $gymId = (int)($_GET['gym_id'] ?? 0);

        if ($gymId === 0) {
            $this->redirect('membership/gyms');
        }

        $legalDocModel = new \App\Models\LegalDocument();
        $gym = $legalDocModel->findById($gymId);

        // Must be verified
        if (!$gym || $gym['status'] !== 'verified') {
            $this->redirect('membership/gyms');
        }

        // Enrich with owner's full name
        try {
            $ownerRow = \App\Core\Database::pdo()
                ->prepare('SELECT fullname, email FROM users WHERE id = :uid LIMIT 1');
            $ownerRow->execute([':uid' => $gym['user_id']]);
            $owner = $ownerRow->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $owner = null;
        }

        $ownerId = (int)$gym['user_id'];

        // Equipment
        $equipment = [];
        try {
            $equipment = (new \App\Models\GymEquipment())->findByOwnerId($ownerId);
        } catch (\Exception $e) { /* graceful */ }

        // Services
        $services = [];
        try {
            $svcModel = new \App\Models\GymService();
            if ($svcModel->tableExists()) {
                $services = $svcModel->findByOwnerId($ownerId);
            }
        } catch (\Exception $e) { /* graceful */ }

        // Membership plans
        $plans = [];
        try {
            $planModel = new MembershipPlan();
            if ($planModel->tableExists()) {
                $plans = $planModel->findByOwnerId($ownerId);
            }
        } catch (\Exception $e) { /* graceful */ }

        // Training packages
        $trainingPackages = [];
        try {
            $pkgModel = new \App\Models\FitnessTrainerPackage();
            $trainingPackages = $pkgModel->findByGymOwner($ownerId, true);
        } catch (\Exception $e) { /* graceful */ }

        // Opening hours – stored as JSON in legal_documents if column exists
        $openingHours = [];
        try {
            $pdo  = \App\Core\Database::pdo();
            $cols = $pdo->query('SHOW COLUMNS FROM legal_documents LIKE \'opening_hours\'');
            if ($cols->rowCount() > 0 && !empty($gym['opening_hours'])) {
                $decoded = json_decode($gym['opening_hours'], true);
                if (is_array($decoded)) {
                    $openingHours = $decoded;
                }
            }
        } catch (\Exception $e) { /* column may not exist yet */ }

        $this->view('membership/gym_profile', [
            'user'             => $user,
            'gym'              => $gym,
            'owner'            => $owner,
            'equipment'        => $equipment,
            'services'         => $services,
            'plans'            => $plans,
            'trainingPackages' => $trainingPackages,
            'openingHours'     => $openingHours,
        ]);
    }

    /** Customer verifies membership code */
    public function verifycodeAction(): void
    {
        $user = $this->requireLogin();
        $error = ''; $success = '';
        $gymMember = (new GymMember())->findByUserId((int)$user['id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = trim((string)($_POST['membership_code'] ?? ''));
            if ($code === '') { $error = 'Enter your membership code.'; }
            else {
                $member = (new GymMember())->findByMembershipCode($code);
                if (!$member || (int)$member['user_id'] !== (int)$user['id']) {
                    $error = 'Invalid membership code.';
                } else {
                    (new AttendanceLog())->create((int)$member['id'], $code);
                    $success = 'Membership verified! Welcome, ' . htmlspecialchars($member['fullname']) . '!';
                    $gymMember = $member;
                }
            }
        }

        $logs = [];
        if ($gymMember) { $logs = (new AttendanceLog())->findByMemberId((int)$gymMember['id']); }

        $this->view('membership/verify_code', [
            'user' => $user, 'error' => $error, 'success' => $success,
            'gymMember' => $gymMember, 'logs' => $logs,
        ]);
    }

    /**
     * View member's premium Digital ID Card.
     * GET: index.php?r=membership/myid
     */
    public function myidAction(): void
    {
        $user = $this->requireLogin();
        
        $memberModel = new GymMember();
        $member = $memberModel->findByUserId((int)$user['id']);

        if (!$member) {
            $_SESSION['error'] = 'Active membership is required to access your Digital ID Card.';
            $this->redirect('membership/verifycode');
        }

        $this->view('membership/member_id_card', [
            'user' => $user,
            'member' => $member
        ]);
    }

    public function notifypaymentAction(): void
    {
        $user = $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $appModel = new MembershipApplication();
            $app = $appModel->findById($id);
            
            if ($app && (int)$app['user_id'] === (int)$user['id'] && $app['status'] === 'verified') {
                $appModel->markPaymentSubmitted($id);
                
                // Notify administrative_officer and gym_owner
                $n = new \App\Models\Notification();
                if ($n->tableExists()) {
                    $adminStmt = \App\Core\Database::pdo()->query("SELECT id FROM users WHERE role = 'administrative_officer' LIMIT 1");
                    $admin = $adminStmt->fetch(\PDO::FETCH_ASSOC);
                    if ($admin) {
                        $n->create((int)$admin['id'], 'Payment Submitted', 'Applicant ' . htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) . ' has submitted their online payment.', 'success', 'admofficer/review&id=' . $id);
                    }
                    if (!empty($app['gym_owner_id'])) {
                        $n->create((int)$app['gym_owner_id'], 'Payment Submitted', 'Applicant ' . htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) . ' has submitted their online payment.', 'success');
                    }
                }
                
                $_SESSION['flash_success'] = 'Payment marked as submitted! The Administrative Officer will verify and generate your code shortly.';
            }
        }
        $gymId = $app['gym_owner_id'] ?? 0;
        $this->redirect('membership/apply&gym_id=' . $gymId);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GEMINI FITNESS PROGRAM
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Show the Fitness Program page (form + saved program if any).
     * GET  index.php?r=membership/fitnessprogram
     */
    public function fitnessprogramAction(): void
    {
        $user      = $this->requireLogin();
        $gymMember = (new GymMember())->findByUserId((int)$user['id']);

        if (!$gymMember) {
            $_SESSION['fp_error'] = 'You need an approved gym membership to create a fitness program.';
            $this->redirect('membership/gyms');
        }

        $programModel = new FitnessProgram();
        $program      = null;
        $tableReady   = $programModel->tableExists();
        if ($tableReady) {
            $program = $programModel->findByMemberId((int)$gymMember['id']);
        }

        // ── Resolve gym name & owner ID via membership application ─────────────
        $gymName      = 'Your Gym';
        $gymOwnerId   = 0;
        if (!empty($gymMember['application_id'])) {
            $app = (new MembershipApplication())->findById((int)$gymMember['application_id']);
            if ($app && !empty($app['gym_owner_id'])) {
                $gymOwnerId = (int)$app['gym_owner_id'];
                $doc = (new LegalDocument())->findByUserId($gymOwnerId);
                if ($doc) { $gymName = $doc['gym_name'] ?? $gymName; }
            }
        }

        // ── Fetch gym equipment list from DB ────────────────────────────────────
        $gymEquipmentList = [];
        if ($gymOwnerId > 0) {
            try {
                $equipRows = (new \App\Models\GymEquipment())->findByOwnerId($gymOwnerId);
                foreach ($equipRows as $row) {
                    $label = trim($row['name']);
                    if ($label !== '') {
                        $gymEquipmentList[] = $label;
                    }
                }
            } catch (\Exception $e) {
                error_log('fitnessprogram: equipment fetch failed: ' . $e->getMessage());
            }
        }

        $flashError   = $_SESSION['fp_error']   ?? '';
        $flashSuccess = $_SESSION['fp_success'] ?? '';
        unset($_SESSION['fp_error'], $_SESSION['fp_success']);

        $this->view('membership/fitness_program', [
            'user'             => $user,
            'gymMember'        => $gymMember,
            'gymName'          => $gymName,
            'gymOwnerId'       => $gymOwnerId,
            'gymEquipmentList' => $gymEquipmentList,
            'program'          => $program,
            'tableReady'       => $tableReady,
            'error'            => $flashError,
            'success'          => $flashSuccess,
        ]);
    }

    /**
     * Generate a fitness program via Gemini API and save it.
     * POST  index.php?r=membership/generateprogram
     */
    public function generateprogramAction(): void
    {
        $user      = $this->requireLogin();
        $gymMember = (new GymMember())->findByUserId((int)$user['id']);

        if (!$gymMember || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('membership/fitnessprogram');
        }

        // ── Collect & validate input ─────────────────────────────────────────
        $goal         = trim((string)($_POST['goal']             ?? ''));
        $expLevel     = trim((string)($_POST['experience_level'] ?? ''));
        $sessionLen   = (int)($_POST['session_length']            ?? 60);
        $injuries     = trim((string)($_POST['injuries_limitations'] ?? ''));
        $weekdays     = (array)($_POST['weekdays']                ?? []);

        $allowedGoals    = ['Bulking', 'Cutting', 'Maintaining'];
        $allowedExp      = ['Beginner', 'Intermediate', 'Advanced'];
        $allowedLengths  = [30, 45, 60, 90];
        $allowedDays     = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

        $weekdays = array_values(array_filter($weekdays, fn($d) => in_array($d, $allowedDays, true)));

        if (!in_array($goal,      $allowedGoals,   true)) { $_SESSION['fp_error'] = 'Invalid goal.';              $this->redirect('membership/fitnessprogram'); }
        if (!in_array($expLevel,  $allowedExp,     true)) { $_SESSION['fp_error'] = 'Invalid experience level.';  $this->redirect('membership/fitnessprogram'); }
        if (!in_array($sessionLen,$allowedLengths, true)) { $_SESSION['fp_error'] = 'Invalid session length.';    $this->redirect('membership/fitnessprogram'); }
        if (empty($weekdays))                             { $_SESSION['fp_error'] = 'Select at least one training day.'; $this->redirect('membership/fitnessprogram'); }

        $availableDays  = count($weekdays);
        $listOfWeekdays = implode(', ', $weekdays);

        // ── Resolve gym name & owner ID via membership application ────────────
        $gymName    = 'Your Gym';
        $gymOwnerId = 0;
        if (!empty($gymMember['application_id'])) {
            $app = (new MembershipApplication())->findById((int)$gymMember['application_id']);
            if ($app && !empty($app['gym_owner_id'])) {
                $gymOwnerId = (int)$app['gym_owner_id'];
                $doc = (new LegalDocument())->findByUserId($gymOwnerId);
                if ($doc) { $gymName = $doc['gym_name'] ?? $gymName; }
            }
        }

        // ── Fetch gym equipment from DB (fallback to textarea value) ──────────
        $gymEquipmentArray = [];
        if ($gymOwnerId > 0) {
            try {
                $equipRows = (new \App\Models\GymEquipment())->findByOwnerId($gymOwnerId);
                foreach ($equipRows as $row) {
                    $label = trim($row['name']);
                    // Include quantity info if > 1
                    if ((int)($row['quantity'] ?? 1) > 1) {
                        $label .= ' (x' . (int)$row['quantity'] . ')';
                    }
                    if ($label !== '') { $gymEquipmentArray[] = $label; }
                }
            } catch (\Exception $e) {
                error_log('generateprogram: equipment fetch failed: ' . $e->getMessage());
            }
        }

        // Fall back to POST textarea if DB returned nothing
        $equipmentFallback = trim((string)($_POST['equipment'] ?? ''));
        if (empty($gymEquipmentArray) && $equipmentFallback !== '') {
            // Convert free-text to array (split by comma/newline)
            $gymEquipmentArray = array_filter(
                array_map('trim', preg_split('/[,\n]+/', $equipmentFallback))
            );
        }

        if (empty($gymEquipmentArray)) {
            $_SESSION['fp_error'] = 'No equipment information found. Please add equipment in the gym management panel.';
            $this->redirect('membership/fitnessprogram');
        }

        // Flat string for storage
        $equipmentForStorage = implode(', ', $gymEquipmentArray);
        // JSON array string for the prompt
        $equipmentJson = json_encode(array_values($gymEquipmentArray), JSON_UNESCAPED_UNICODE);

        // ── Build enhanced prompt ─────────────────────────────────────────────
        $injuriesText = $injuries !== '' ? $injuries : 'None';
        $promptText   = <<<PROMPT
You are a certified strength & conditioning coach generating a personalized weekly workout program for a member of "Nutrify," a fitness membership app.

MEMBER PROFILE:
- Goal: {$goal}
- Experience level: {$expLevel}
- Days available: {$availableDays} ({$listOfWeekdays})
- Session length: {$sessionLen} minutes
- Injuries/limitations: {$injuriesText}

GYM ASSIGNED: {$gymName}
AVAILABLE EQUIPMENT AT THIS GYM: {$equipmentJson}

TASK:
1. Suggest 2-3 candidate training splits suitable for the days available (e.g. Push/Pull/Legs, Bro Split, Upper/Lower, Full Body). For each candidate, briefly explain why it fits the goal and schedule, and list which of the gym's available equipment it primarily relies on.
2. Recommend ONE best-fit split from those candidates and build the full weekly schedule using ONLY exercises that this specific gym's equipment list supports.
3. If an ideal exercise requires equipment NOT in the gym equipment list, substitute it with the closest equivalent that IS available, and note the substitution reason in the exercise "notes" field.
4. Adjust volume/intensity by goal: Bulking = higher volume/compound focus; Cutting = maintain volume + add conditioning; Maintaining = balanced.
5. If injuries/limitations are listed, avoid or modify exercises affecting that area and explain the substitution in the exercise "notes" field.
6. Keep total session time realistic for the given session_length (assume roughly 3-5 minutes per working set including rest).

OUTPUT FORMAT — return ONLY valid JSON, no markdown, no code fences, no text outside the JSON:

{
  "split_options": [
    {
      "name": "string",
      "why_it_fits": "string",
      "equipment_used": ["string"]
    }
  ],
  "recommended_split": "string",
  "split_name": "string",
  "rationale": "string",
  "weekly_schedule": [
    {
      "day": "string",
      "focus": "string",
      "exercises": [
        {
          "name": "string",
          "equipment_needed": "string",
          "sets": 0,
          "reps": "string",
          "rest_seconds": 0,
          "notes": "string"
        }
      ]
    }
  ],
  "progression_notes": "string",
  "nutrition_note": "string"
}

RULES:
- Every "equipment_needed" value must match an item in the gym equipment list. Never invent equipment the gym does not have.
- Match the exact number of training days requested.
- Never prescribe specific calorie/macro numbers.
- Never give medical advice — recommend consulting the gym's trainer or a physician for serious injuries.
- Do not include any text outside the JSON object.
PROMPT;

        // ── Call Gemini ───────────────────────────────────────────────────────
        $programData = $this->callGeminiApi($promptText);

        if (!$programData || empty($programData['weekly_schedule'])) {
            $reason = $_SESSION['gemini_error'] ?? 'Gemini API returned an unparseable response or hit a limit.';
            unset($_SESSION['gemini_error']);
            $_SESSION['fp_error'] = 'AI generation failed: ' . $reason . ' You can also use "Build My Own Program" to construct your schedule manually.';
            $this->redirect('membership/fitnessprogram');
        }

        // ── Normalise: ensure split_name exists (copy from recommended_split) ─
        if (empty($programData['split_name'])) {
            $programData['split_name'] = $programData['recommended_split'] ?? 'Custom Split';
        }

        // ── Save to DB ────────────────────────────────────────────────────────
        $programModel = new FitnessProgram();
        if (!$programModel->tableExists()) {
            $_SESSION['fp_error'] = 'Database table not ready. Please run sql/create_fitness_programs.sql first.';
            $this->redirect('membership/fitnessprogram');
        }

        $profile = [
            'goal'                 => $goal,
            'experience_level'     => $expLevel,
            'available_days'       => $availableDays,
            'list_of_weekdays'     => $listOfWeekdays,
            'session_length'       => $sessionLen,
            'equipment'            => $equipmentForStorage,
            'injuries_limitations' => $injuries !== '' ? $injuries : null,
            'gym_name'             => $gymName,
        ];

        $programModel->upsert((int)$gymMember['id'], (int)$user['id'], $profile, $programData);

        $_SESSION['fp_success'] = 'Your personalized fitness program has been generated!';
        $this->redirect('membership/fitnessprogram');
    }

    /**
     * Save a manually-built fitness program (no AI).
     * POST  index.php?r=membership/savemanualprogram
     */
    public function savemanualprogramAction(): void
    {
        $user      = $this->requireLogin();
        $gymMember = (new GymMember())->findByUserId((int)$user['id']);

        if (!$gymMember || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('membership/fitnessprogram');
        }

        $manualJson = trim((string)($_POST['manual_program_json'] ?? ''));
        if ($manualJson === '') {
            $_SESSION['fp_error'] = 'No program data submitted.';
            $this->redirect('membership/fitnessprogram');
        }

        $weeklySchedule = json_decode($manualJson, true);
        if (!is_array($weeklySchedule) || empty($weeklySchedule)) {
            $_SESSION['fp_error'] = 'Invalid program structure. Please try again.';
            $this->redirect('membership/fitnessprogram');
        }

        // ── Resolve gym name ──────────────────────────────────────────────────
        $gymName    = 'Your Gym';
        $gymOwnerId = 0;
        if (!empty($gymMember['application_id'])) {
            $app = (new MembershipApplication())->findById((int)$gymMember['application_id']);
            if ($app && !empty($app['gym_owner_id'])) {
                $gymOwnerId = (int)$app['gym_owner_id'];
                $doc = (new LegalDocument())->findByUserId($gymOwnerId);
                if ($doc) { $gymName = $doc['gym_name'] ?? $gymName; }
            }
        }

        // Collect distinct weekdays from schedule
        $weekdays = array_column($weeklySchedule, 'day');
        $listOfWeekdays = implode(', ', $weekdays);
        $availableDays  = count($weekdays);

        $programData = [
            'split_name'        => 'Manual Program',
            'recommended_split' => 'Manual Program',
            'split_options'     => [],
            'rationale'         => 'This program was built manually by the member.',
            'weekly_schedule'   => $weeklySchedule,
            'progression_notes' => 'Progress at your own pace. Add weight or reps when current level feels easy.',
            'nutrition_note'    => 'Consult a registered dietitian or your gym\'s nutrition coach for personalised meal guidance.',
        ];

        $programModel = new FitnessProgram();
        if (!$programModel->tableExists()) {
            $_SESSION['fp_error'] = 'Database table not ready. Please run sql/create_fitness_programs.sql first.';
            $this->redirect('membership/fitnessprogram');
        }

        $profile = [
            'goal'                 => trim((string)($_POST['goal'] ?? 'Maintaining')),
            'experience_level'     => trim((string)($_POST['experience_level'] ?? 'Beginner')),
            'available_days'       => $availableDays,
            'list_of_weekdays'     => $listOfWeekdays,
            'session_length'       => (int)($_POST['session_length'] ?? 60),
            'equipment'            => 'Custom (manually built)',
            'injuries_limitations' => null,
            'gym_name'             => $gymName,
        ];

        $programModel->upsert((int)$gymMember['id'], (int)$user['id'], $profile, $programData);

        $_SESSION['fp_success'] = 'Your manual fitness program has been saved!';
        $this->redirect('membership/fitnessprogram');
    }

    /**
     * Call the Gemini generateContent API.
     * The API key is read from config — never exposed to the client.
     *
     * @param string $promptText  The full prompt to send
     * @return array|null         Decoded JSON response or null on failure
     */
    private function callGeminiApi(string $promptText): ?array
    {
        $config = require BASE_PATH . '/app/config/config.php';
        $apiKey = $config['gemini']['api_key'] ?? '';
        $model  = $config['gemini']['model']   ?? 'gemini-1.5-flash';

        if ($apiKey === '') {
            error_log('callGeminiApi: API key is empty — check config.php [gemini][api_key]');
            return null;
        }

        // Try primary model, then fallbacks if model not found
        $modelsToTry = array_unique([$model, 'gemini-1.5-flash', 'gemini-2.0-flash', 'gemini-1.5-pro']);

        foreach ($modelsToTry as $currentModel) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$currentModel}:generateContent?key={$apiKey}";

            $body = json_encode([
                'contents'         => [['parts' => [['text' => $promptText]]]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'maxOutputTokens'  => 8192,
                ],
            ]);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT        => 90,           // extended for free hosting
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response  = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // ── cURL-level failure ────────────────────────────────────────────────
            if ($curlError) {
                $_SESSION['gemini_error'] = "Network/cURL error: {$curlError}";
                error_log("callGeminiApi ({$currentModel}): cURL error — {$curlError}");
                continue;
            }

            // ── HTTP error ────────────────────────────────────────────────────────
            if ($httpCode !== 200 && $httpCode !== 201) {
                $errBody = (string)$response;
                $errData = json_decode($errBody, true);
                $errMsg  = $errData['error']['message'] ?? $errBody;
                $_SESSION['gemini_error'] = "HTTP {$httpCode} ({$currentModel}): {$errMsg}";
                error_log("callGeminiApi ({$currentModel}): HTTP {$httpCode} — {$errMsg}");
                
                // If model not found (404/400), try next model in fallback list
                if ($httpCode === 404 || strpos($errMsg, 'not found') !== false) {
                    continue;
                }
                return null;
            }

            $envelope = json_decode((string)$response, true);

            // ── Safety / empty candidates guard ──────────────────────────────────
            if (empty($envelope['candidates'])) {
                $blockReason  = $envelope['promptFeedback']['blockReason'] ?? 'unknown';
                $finishReason = $envelope['candidates'][0]['finishReason'] ?? 'NO_CANDIDATES';
                error_log("callGeminiApi ({$currentModel}): empty candidates — blockReason={$blockReason}, finishReason={$finishReason}");
                return null;
            }

            $finishReason = $envelope['candidates'][0]['finishReason'] ?? '';
            if ($finishReason === 'MAX_TOKENS') {
                error_log("callGeminiApi ({$currentModel}): response truncated (MAX_TOKENS)");
            }

            // ── Extract inner text ────────────────────────────────────────────────
            $text = $envelope['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if ($text === null || $text === '') {
                error_log("callGeminiApi ({$currentModel}): text is empty");
                return null;
            }

            // ── Strip markdown fences ─────────────────────────────────────────────
            $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
            $text = preg_replace('/\s*```\s*$/', '', trim($text));
            $text = trim($text);

            // ── Decode and validate ───────────────────────────────────────────────
            $programData = json_decode($text, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("callGeminiApi ({$currentModel}): json_decode failed — " . json_last_error_msg());
                return null;
            }

            return is_array($programData) ? $programData : null;
        }

        return null;
    }
}

