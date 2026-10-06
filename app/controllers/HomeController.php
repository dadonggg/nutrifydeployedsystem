<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\LegalDocument;
use App\Models\GymMember;
use App\Models\StaffApplication;
use App\Models\MembershipApplication;
use App\Models\Employee;
use App\Models\FinancialRecord;
use App\Models\GymInventory;
use App\Models\AttendanceLog;
use App\Models\FitnessServiceRequest;

final class HomeController extends Controller
{
    /** Public landing page — shown when not logged in */
    public function landingAction(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('home/index');
        }
        $this->view('home/landing');
    }

    public function indexAction(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('home/landing');
        }

        $userModel = new User();
        $user = $userModel->findById((int)$_SESSION['user_id']);

        if (!$user) {
            unset($_SESSION['user_id']);
            $this->redirect('auth/login');
        }

        $role = $user['role'] ?? 'customer';

        switch ($role) {
            case 'admin':
                $this->adminDashboard($user);
                break;
            case 'administrative_officer':
                $this->admOfficerDashboard($user);
                break;
            case 'gym_owner':
                $this->gymOwnerDashboard($user);
                break;
            case 'trainer':
                $this->staffDashboard($user, $role);
                break;
            case 'maintenance':
                $this->redirect('maintenance/dashboard');
                break;
            case 'marketing_officer':
                $this->redirect('marketing/dashboard');
                break;
            default:
                $this->customerDashboard($user);
                break;
        }
    }

    private function customerDashboard(array $user): void
    {
        $legalDoc = (new LegalDocument())->findByUserId((int)$user['id']);

        // If user's legal document was verified, auto-promote to gym_owner and load gym owner dashboard
        if ($legalDoc && $legalDoc['status'] === 'verified') {
            (new User())->updateRole((int)$user['id'], 'gym_owner');
            $this->redirect('home/index');
            return;
        }

        $staffApp = (new StaffApplication())->findByUserId((int)$user['id']);
        $memberApp = (new MembershipApplication())->findByUserId((int)$user['id']);
        $gymMember = (new GymMember())->findByUserId((int)$user['id']);

        // If user is an active gym member, redirect to member dashboard
        if ($gymMember && ($gymMember['membership_status'] ?? 'active') === 'active') {
            $this->redirect('member/dashboard');
        }

        $this->view('dashboard/customer', [
            'user' => $user,
            'legalDoc' => $legalDoc,
            'staffApp' => $staffApp,
            'memberApp' => $memberApp,
            'gymMember' => $gymMember,
        ]);
    }

    private function gymOwnerDashboard(array $user): void
    {
        $finModel = new FinancialRecord();
        $invModel = new GymInventory();
        $memberModel = new GymMember();

        $ownerId = (int)$user['id'];
        $budget = $finModel->getBudget($ownerId);
        $totalExpenses = $finModel->getTotalExpenses($ownerId);
        $totalEquipment = $invModel->getTotalSpent($ownerId);
        $staffApps = (new StaffApplication())->findAllPending();

        // Revenue & Members data for dashboard - strictly scoped to this gym owner!
        $totalRevenue = $finModel->getTotalRevenue($ownerId);
        $revenueBreakdown = $finModel->getRevenueBreakdown($ownerId);
        $monthlyProfit = $finModel->getMonthlyProfit($ownerId);
        $members = $memberModel->findAll($ownerId);
        $activeMembers = $memberModel->findAllActive($ownerId);
        $monthlyMemberRevenue = $memberModel->getMonthlyRevenue(null, $ownerId);
        $revenueByMonth = $memberModel->getRevenueByMonth(6, $ownerId);
        $memberApps = (new MembershipApplication())->findAll($ownerId);
        $pendingMemberApps = array_filter($memberApps, fn($a) => in_array($a['status'], ['pending', 'resubmit'], true));

        $builderCampaign = null;
        $campaignModel = new \App\Models\AdCampaign();
        if ($campaignModel->tableExists()) {
            try {
                $pdo = \App\Core\Database::pdo();
                $stmt = $pdo->prepare(
                    'SELECT * FROM ad_campaigns WHERE gym_id = :gym_id AND source = "campaign_builder" ORDER BY updated_at DESC LIMIT 1'
                );
                $stmt->execute([':gym_id' => (int)$user['id']]);
                $builderCampaign = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
            } catch (\PDOException $e) {}
        }

        $this->view('dashboard/gymowner', [
            'user' => $user,
            'budget' => $budget,
            'totalExpenses' => $totalExpenses,
            'totalEquipment' => $totalEquipment,
            'remaining' => $budget - $totalExpenses - $totalEquipment,
            'staffApps' => $staffApps,
            'totalRevenue' => $totalRevenue,
            'revenueBreakdown' => $revenueBreakdown,
            'monthlyProfit' => $monthlyProfit,
            'members' => $members,
            'activeMembers' => $activeMembers,
            'monthlyMemberRevenue' => $monthlyMemberRevenue,
            'revenueByMonth' => $revenueByMonth,
            'pendingMemberApps' => $pendingMemberApps,
            'builderCampaign' => $builderCampaign,
        ]);
    }

    private function adminDashboard(array $user): void
    {
        $legalDocs = (new LegalDocument())->findAllPending();

        $this->view('dashboard/admin', [
            'user' => $user,
            'legalDocs' => $legalDocs,
        ]);
    }

    private function admOfficerDashboard(array $user): void
    {
        $memberApps = (new MembershipApplication())->findAll();
        $gymMembers = (new GymMember())->findAll();
        $employees = (new Employee())->findAll();
        $pendingApps = array_filter($memberApps, fn($a) => in_array($a['status'], ['pending', 'resubmit'], true));
        
        // Get fitness training request stats
        $fitnessRequestModel = new FitnessServiceRequest();
        $fitnessStats = $fitnessRequestModel->getStats();
        
        $builderCampaign = null;
        try {
            $campaignModel = new \App\Models\AdCampaign();
            if ($campaignModel->tableExists()) {
                $builderCampaign = $campaignModel->findActiveCampaignBuilder();
            }
        } catch (\Throwable $e) {
            $builderCampaign = null;
        }

        $this->view('dashboard/admofficer', [
            'user' => $user,
            'memberApps' => $memberApps,
            'gymMembers' => $gymMembers,
            'employees' => $employees,
            'pendingCount' => count($pendingApps),
            'fitnessStats' => $fitnessStats,
            'builderCampaign' => $builderCampaign,
        ]);
    }

    private function staffDashboard(array $user, string $role): void
    {
        // If trainer, show trainer-specific dashboard with fitness features
        if ($role === 'trainer') {
            $this->trainerDashboard($user);
            return;
        }
        
        // Otherwise show generic staff dashboard
        $this->view('dashboard/staff', [
            'user' => $user,
            'role' => $role,
        ]);
    }

    private function trainerDashboard(array $user): void
    {
        // Get trainer's employee record
        $employeeModel = new Employee();
        $employee = $employeeModel->findByUserId((int)$user['id']);
        
        if (!$employee) {
            // Fallback to generic staff dashboard
            $this->view('dashboard/staff', [
                'user' => $user,
                'role' => 'trainer',
            ]);
            return;
        }

        // Get assigned clients (fitness service requests)
        $requestModel = new FitnessServiceRequest();
        $assignedClients = $requestModel->findByTrainerId((int)$employee['id']);
        
        // Get statistics
        $totalClients = count($assignedClients);
        $activeClients = count(array_filter($assignedClients, fn($c) => $c['status'] === 'assigned'));
        $completedClients = count(array_filter($assignedClients, fn($c) => $c['status'] === 'completed'));
        
        // Get pending progress reviews (clients who sent progress)
        $progressModel = new \App\Models\FitnessProgressTracking();
        $pendingReviews = [];
        foreach ($assignedClients as $client) {
            $progress = $progressModel->findPendingByServiceRequestId((int)$client['id']);
            if ($progress) {
                $pendingReviews[] = array_merge($client, ['progress' => $progress]);
            }
        }
        
        // Get gym equipment (from gym owner who hired this trainer)
        $equipment = [];
        if (!empty($employee['hired_by'])) {
            $inventoryModel = new GymInventory();
            $equipment = $inventoryModel->findByOwnerId((int)$employee['hired_by']);
        }

        $this->view('dashboard/trainer', [
            'user' => $user,
            'employee' => $employee,
            'assignedClients' => $assignedClients,
            'totalClients' => $totalClients,
            'activeClients' => $activeClients,
            'completedClients' => $completedClients,
            'pendingReviews' => $pendingReviews,
            'equipment' => $equipment,
        ]);
    }

    public function logoutAction(): void
    {
        // Log logout activity before destroying session
        if (isset($_SESSION['user_id'])) {
            $userModel = new User();
            $user = $userModel->findById((int)$_SESSION['user_id']);
            
            if ($user) {
                $loginActivityModel = new \App\Models\LoginActivity();
                $loginActivityModel->logLogout((int)$user['id'], (string)$user['email']);
            }
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        $this->redirect('home/landing');
    }
}
