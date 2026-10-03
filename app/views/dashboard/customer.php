<?php
declare(strict_types=1);
$pageTitle = 'Member Dashboard';
require __DIR__ . '/../partials/header.php';
$displayName = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
if ($displayName === '') $displayName = $user['fullname'] ?? 'User';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

:root {
  --gym-green: #16a34a;
  --gym-green-light: #dcfce7;
  --gym-green-dark: #15803d;
  --bg-page: #f5f7f5;
  --card-white: #ffffff;
  --text-primary: #1a1a1a;
  --text-secondary: #6b7280;
  --text-muted: #9ca3af;
  --border-light: #e5e7eb;
  --shadow-sm: 0 2px 12px rgba(0,0,0,0.07);
  --shadow-md: 0 4px 16px rgba(0,0,0,0.1);
}

body {
  background: var(--bg-page) !important;
  font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
}

/* Welcome Header */
.welcome-header {
  margin-bottom: 2rem;
}
.welcome-title {
  font-size: clamp(20px, 5vw, 28px);
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
}
.welcome-subtitle {
  color: var(--text-secondary);
  font-size: 14px;
}
.member-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--gym-green);
  color: white;
  padding: 6px 16px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 600;
}

/* Modern Card Styling */
.modern-card {
  background: var(--card-white);
  border: 1px solid var(--border-light);
  border-radius: 12px;
  box-shadow: var(--shadow-sm);
  transition: all 0.3s ease;
}
.modern-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
}

/* Stat Cards */
.stat-card-modern {
  background: var(--card-white);
  border: 1px solid var(--border-light);
  border-radius: 12px;
  padding: 1.15rem 1rem;
  box-shadow: var(--shadow-sm);
  transition: all 0.3s ease;
  height: 100%;
}
@media (min-width: 768px) {
  .stat-card-modern {
    padding: 1.5rem;
  }
}
.stat-card-modern:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-4px);
}
.stat-icon-circle {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  margin-bottom: 0.75rem;
}
@media (min-width: 768px) {
  .stat-icon-circle {
    width: 56px;
    height: 56px;
    font-size: 24px;
    margin-bottom: 1rem;
  }
}
.stat-icon-circle.green {
  background: var(--gym-green-light);
  color: var(--gym-green);
}
.stat-icon-circle.blue {
  background: #dbeafe;
  color: #3b82f6;
}
.stat-icon-circle.yellow {
  background: #fef3c7;
  color: #f59e0b;
}
.stat-label {
  font-size: 12px;
  color: var(--text-secondary);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.35rem;
}
.stat-value {
  font-size: clamp(22px, 5vw, 32px);
  font-weight: 800;
  color: var(--text-primary);
  line-height: 1.1;
  margin-bottom: 0.35rem;
}
.stat-subtitle {
  font-size: 12px;
  color: var(--text-muted);
}
.stat-progress {
  height: 4px;
  background: #e5e7eb;
  border-radius: 999px;
  overflow: hidden;
  margin-top: 0.75rem;
}
.stat-progress-bar {
  height: 100%;
  background: var(--gym-green);
  border-radius: 999px;
  transition: width 0.3s ease;
}

/* Coaching Card */
.coaching-card {
  background: var(--card-white);
  border: 1px solid var(--border-light);
  border-radius: 12px;
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}
.coaching-header {
  background: linear-gradient(135deg, var(--gym-green) 0%, var(--gym-green-dark) 100%);
  padding: 1.25rem 1.5rem;
  color: white;
}
.coaching-header h3 {
  font-size: 18px;
  font-weight: 700;
  margin: 0;
}
.coaching-body {
  padding: 1.5rem;
}
.coach-avatar-wrapper {
  position: relative;
  width: 80px;
  height: 80px;
  margin-bottom: 1rem;
}
.coach-avatar {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  border: 4px solid var(--gym-green);
  object-fit: cover;
}
.coach-name {
  font-size: 18px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
}
.coach-title {
  font-size: 13px;
  color: var(--text-secondary);
  margin-bottom: 1rem;
}
.plan-stats {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  margin: 1.5rem 0;
}
@media (min-width: 400px) {
  .plan-stats { gap: 2rem; }
}
.plan-stat-item {
  text-align: center;
}
.plan-stat-value {
  font-size: 24px;
  font-weight: 700;
  color: var(--gym-green);
}
.plan-stat-label {
  font-size: 11px;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.coaching-buttons {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-top: 1.5rem;
}
.btn-green {
  background: var(--gym-green);
  color: white;
  border: none;
  padding: 0.625rem 1.25rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.2s;
  flex: 1;
}
.btn-green:hover {
  background: var(--gym-green-dark);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
}
.btn-green-outline {
  background: transparent;
  color: var(--gym-green);
  border: 2px solid var(--gym-green);
  padding: 0.625rem 1.25rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  transition: all 0.2s;
  flex: 1;
}
.btn-green-outline:hover {
  background: var(--gym-green-light);
  border-color: var(--gym-green-dark);
  color: var(--gym-green-dark);
}

/* Quick Actions Card */
.quick-actions-card {
  background: var(--card-white);
  border: 1px solid var(--border-light);
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: var(--shadow-sm);
  height: 100%;
}
.quick-actions-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 1.25rem;
}
.quick-action-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border-left: 3px solid transparent;
  border-radius: 8px;
  margin-bottom: 0.75rem;
  transition: all 0.2s;
  cursor: pointer;
  text-decoration: none;
  color: inherit;
}
.quick-action-item:hover {
  background: #f9fafb;
  transform: translateX(4px);
}
.quick-action-item.green {
  border-left-color: var(--gym-green);
}
.quick-action-item.blue {
  border-left-color: #3b82f6;
}
.quick-action-item.yellow {
  border-left-color: #f59e0b;
}
.quick-action-item.purple {
  border-left-color: #8b5cf6;
}
.quick-action-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}
.quick-action-icon.green {
  background: var(--gym-green-light);
  color: var(--gym-green);
}
.quick-action-icon.blue {
  background: #dbeafe;
  color: #3b82f6;
}
.quick-action-icon.yellow {
  background: #fef3c7;
  color: #f59e0b;
}
.quick-action-icon.purple {
  background: #e9d5ff;
  color: #8b5cf6;
}
.quick-action-text {
  flex: 1;
}
.quick-action-title {
  font-size: 14px;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.125rem;
}
.quick-action-subtitle {
  font-size: 12px;
  color: var(--text-secondary);
}

/* Recent Visits Card */
.visits-card {
  background: var(--card-white);
  border: 1px solid var(--border-light);
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: var(--shadow-sm);
  margin-bottom: 1.5rem;
}
.visits-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.visit-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f3f4f6;
}
.visit-item:last-child {
  border-bottom: none;
}
.visit-date {
  font-size: 13px;
  color: var(--text-secondary);
  font-weight: 500;
}
.visit-badge {
  background: var(--gym-green-light);
  color: var(--gym-green);
  padding: 4px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
}
.btn-view-history {
  width: 100%;
  margin-top: 1rem;
  background: transparent;
  border: 1px solid var(--border-light);
  color: var(--text-secondary);
  padding: 0.625rem;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  transition: all 0.2s;
}
.btn-view-history:hover {
  border-color: var(--gym-green);
  color: var(--gym-green);
  background: var(--gym-green-light);
}

/* Membership Status Card */
.membership-card {
  background: var(--card-white);
  border: 1px solid var(--border-light);
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: var(--shadow-sm);
  text-align: center;
}
.membership-title {
  font-size: 16px;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}
.membership-status-badge {
  background: var(--gym-green);
  color: white;
  font-size: 32px;
  font-weight: 800;
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 1rem;
  letter-spacing: 0.05em;
}
.membership-expiry {
  font-size: 13px;
  color: var(--text-secondary);
  margin-bottom: 1.5rem;
}
.btn-manage-membership {
  width: 100%;
  background: transparent;
  border: 2px solid var(--gym-green);
  color: var(--gym-green);
  padding: 0.75rem;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  transition: all 0.2s;
}
.btn-manage-membership:hover {
  background: var(--gym-green);
  color: white;
}

/* Member Code Display */
.member-code-display {
  background: #f9fafb;
  border: 2px dashed var(--border-light);
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
  text-align: center;
}
.member-code-label {
  font-size: 11px;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
}
.member-code-value {
  font-size: 20px;
  font-weight: 700;
  color: var(--text-primary);
  font-family: 'Courier New', monospace;
  letter-spacing: 0.1em;
}
</style>

<div class="container-fluid px-2 px-sm-4 py-3 py-sm-4">
  <!-- Welcome Header -->
  <div class="welcome-header d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div class="min-w-0">
      <h1 class="welcome-title">Welcome back, <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>!</h1>
      <p class="welcome-subtitle">
        Member since May 2026 • Code: 
        <strong style="color: var(--gym-green);"><?= $gymMember ? htmlspecialchars($gymMember['membership_code']) : 'GYM-B137FBAB' ?></strong>
      </p>
    </div>
    <span class="member-badge flex-shrink-0">
      <i class="bi bi-check-circle-fill"></i>
      Active
    </span>
  </div>

  <!-- Stats Row -->
  <div class="row g-2 g-sm-3 g-md-4 mb-3 mb-md-4">
    <div class="col-12 col-sm-4">
      <div class="stat-card-modern">
        <div class="stat-icon-circle green">
          <i class="bi bi-activity"></i>
        </div>
        <div class="stat-label">Total Workouts</div>
        <div class="stat-value">0</div>
        <div class="stat-subtitle">0 minutes total</div>
        <div class="stat-progress">
          <div class="stat-progress-bar" style="width: 0%"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-4">
      <div class="stat-card-modern">
        <div class="stat-icon-circle blue">
          <i class="bi bi-calendar-check"></i>
        </div>
        <div class="stat-label">Gym Visits</div>
        <div class="stat-value">1</div>
        <div class="stat-subtitle">This month • 1 day streak</div>
        <div class="stat-progress">
          <div class="stat-progress-bar" style="width: 10%"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-4">
      <div class="stat-card-modern">
        <div class="stat-icon-circle yellow">
          <i class="bi bi-trophy"></i>
        </div>
        <div class="stat-label">Active Goals</div>
        <div class="stat-value">0</div>
        <div class="stat-subtitle">0 completed</div>
        <div class="stat-progress">
          <div class="stat-progress-bar" style="width: 0%"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Main Content Grid -->
  <div class="row g-4">
    <!-- Left Column: Coaching Card -->
    <div class="col-lg-5">
      <div class="coaching-card">
        <div class="coaching-header">
          <h3><i class="bi bi-person-hearts me-2"></i>Personal Coaching & Plans</h3>
        </div>
        <div class="coaching-body">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="coach-avatar-wrapper">
              <img src="https://ui-avatars.com/api/?name=Leonard+Alfanta&background=16a34a&color=fff&size=80" 
                   alt="Coach" class="coach-avatar">
            </div>
            <div>
              <div class="coach-name">leonard v. alfanta</div>
              <div class="coach-title">Assigned Coach</div>
            </div>
          </div>

          <div style="background: #f9fafb; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
            <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 0.5rem;">
              <i class="bi bi-clipboard-check me-1"></i>My Training Overview
            </div>
            <div style="font-size: 12px; color: var(--text-muted);">
              <i class="bi bi-graph-up me-1"></i>Track Daily Progress
            </div>
          </div>

          <div class="plan-stats">
            <div class="plan-stat-item">
              <div class="plan-stat-value">2000</div>
              <div class="plan-stat-label">Target Calories</div>
            </div>
            <div class="plan-stat-item">
              <div class="plan-stat-value">3</div>
              <div class="plan-stat-label">Weekly Sessions</div>
            </div>
          </div>

          <div class="coaching-buttons">
            <button class="btn-green">
              <i class="bi bi-dumbbell me-1"></i>View Workouts
            </button>
            <button class="btn-green-outline">
              <i class="bi bi-egg-fried me-1"></i>View Diet
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Middle Column: Quick Actions -->
    <div class="col-lg-4">
      <div class="quick-actions-card">
        <h3 class="quick-actions-title">
          <i class="bi bi-lightning-charge me-2"></i>Quick Actions
        </h3>

        <a href="index.php?r=membership/apply" class="quick-action-item green">
          <div class="quick-action-icon green">
            <i class="bi bi-card-checklist"></i>
          </div>
          <div class="quick-action-text">
            <div class="quick-action-title">Gym Membership</div>
            <div class="quick-action-subtitle">Apply or manage membership</div>
          </div>
          <i class="bi bi-chevron-right" style="color: var(--text-muted);"></i>
        </a>

        <a href="index.php?r=membership/verifycode" class="quick-action-item blue">
          <div class="quick-action-icon blue">
            <i class="bi bi-qr-code"></i>
          </div>
          <div class="quick-action-text">
            <div class="quick-action-title">Check In</div>
            <div class="quick-action-subtitle">Verify code & log attendance</div>
          </div>
          <i class="bi bi-chevron-right" style="color: var(--text-muted);"></i>
        </a>

        <a href="index.php?r=gymowner/apply" class="quick-action-item yellow">
          <div class="quick-action-icon yellow">
            <i class="bi bi-building"></i>
          </div>
          <div class="quick-action-text">
            <div class="quick-action-title">Become Gym Owner</div>
            <div class="quick-action-subtitle">Submit legal documents</div>
          </div>
          <i class="bi bi-chevron-right" style="color: var(--text-muted);"></i>
        </a>

        <a href="index.php?r=staff/gyms" class="quick-action-item purple">
          <div class="quick-action-icon purple">
            <i class="bi bi-person-badge"></i>
          </div>
          <div class="quick-action-text">
            <div class="quick-action-title">Apply as Staff</div>
            <div class="quick-action-subtitle">Trainer or maintenance officer</div>
          </div>
          <i class="bi bi-chevron-right" style="color: var(--text-muted);"></i>
        </a>
      </div>
    </div>

    <!-- Right Column: Active Campaigns & Events -->
    <div class="col-lg-3">
      <!-- Active Campaigns -->
      <div class="visits-card">
        <h3 class="visits-title">
          <i class="bi bi-megaphone-fill"></i>
          Active Campaigns
        </h3>
        
        <?php
        // Fetch active campaigns for this member
        try {
            $pdo = \App\Core\Database::pdo();
            $stmt = $pdo->prepare(
                "SELECT id, title, service_description, start_date, end_date, 
                        instructor_id, image_path, status
                 FROM ad_campaigns 
                 WHERE status = 'published'
                 AND CURDATE() BETWEEN start_date AND end_date
                 ORDER BY created_at DESC
                 LIMIT 3"
            );
            $stmt->execute();
            $activeCampaigns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $activeCampaigns = [];
        }
        ?>
        
        <?php if (empty($activeCampaigns)): ?>
        <div class="text-center py-4">
          <i class="bi bi-calendar-event" style="font-size: 2.5rem; color: var(--text-muted);"></i>
          <p style="font-size: 13px; color: var(--text-muted); margin-top: 0.5rem; margin-bottom: 0;">
            No active campaigns right now
          </p>
        </div>
        <?php else: ?>
          <?php foreach ($activeCampaigns as $campaign): ?>
          <div class="visit-item" style="flex-direction: column; align-items: flex-start; gap: 0.5rem;">
            <?php if (!empty($campaign['image_path'])): ?>
            <img src="<?= htmlspecialchars($campaign['image_path']) ?>" 
                 alt="Campaign" 
                 style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px; margin-bottom: 0.5rem;">
            <?php endif; ?>
            <div style="width: 100%;">
              <div style="font-size: 14px; font-weight: 600; color: var(--text-primary); margin-bottom: 0.25rem;">
                <?= htmlspecialchars($campaign['title']) ?>
              </div>
              <div style="font-size: 12px; color: var(--text-secondary); margin-bottom: 0.5rem;">
                <?= htmlspecialchars(substr($campaign['service_description'], 0, 60)) . '...' ?>
              </div>
              <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 11px; color: var(--text-muted);">
                  <i class="bi bi-calendar-check"></i>
                  Valid until <?= date('M d', strtotime($campaign['end_date'])) ?>
                </span>
                <a href="index.php?r=member/campaigns" 
                   class="visit-badge" 
                   style="text-decoration: none; cursor: pointer;">
                  View
                </a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
        
        <a href="index.php?r=member/campaigns" class="btn-view-history">
          <i class="bi bi-grid-3x3-gap me-1"></i>View All Events
        </a>
      </div>

      <!-- Membership Status -->
      <div class="membership-card">
        <h3 class="membership-title">
          <i class="bi bi-shield-check"></i>
          Membership Status
        </h3>
        <div class="membership-status-badge">
          ACTIVE
        </div>
        <div class="membership-expiry">
          Expires on <strong>Jun 27, 2026</strong>
        </div>
        <button class="btn-manage-membership">
          <i class="bi bi-gear me-1"></i>Manage Membership
        </button>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
