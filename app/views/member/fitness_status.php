<?php
declare(strict_types=1);
$pageTitle = 'Fitness Training Status';
require __DIR__ . '/../partials/header.php';

$displayName = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
if ($displayName === '') $displayName = $user['fullname'] ?? 'Member';
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

:root {
  /* Backgrounds */
  --bg-page:           #f0f2f0;
  --bg-card:           #ffffff;
  --bg-section-header: #e8f5f0;
  --bg-input:          #ffffff;
  --bg-info-banner:    #eff6ff;
  --bg-trainer-box:    #f0fdf9;
  --bg-tag:            #f1f5f9;

  /* Borders */
  --border-card:       #e2e8f0;
  --border-input:      #cbd5e1;
  --border-teal:       #0d9488;
  --border-info:       #bfdbfe;
  --border-trainer:    #99f6e4;

  /* Accent Colors */
  --accent-teal:       #0d9488;
  --accent-teal-light: #14b8a6;
  --accent-blue:       #06b6d4;
  --accent-green-btn:  #166534;
  --accent-green-hover:#15803d;

  /* Text */
  --text-primary:      #1e293b;
  --text-secondary:    #64748b;
  --text-teal:         #0d9488;
  --text-blue:         #06b6d4;
  --text-white:        #ffffff;

  /* Badges */
  --badge-assigned-bg:   #06b6d4;
  --badge-assigned-text: #ffffff;
  --badge-personal-bg:   #3b82f6;
  --badge-personal-text: #ffffff;
  --tag-bg:              #e2e8f0;
  --tag-text:            #475569;

  /* Shadows */
  --shadow-card: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.05);
  --shadow-sm:   0 1px 2px rgba(0,0,0,0.06);
}

body {
  background: var(--bg-page) !important;
  font-family: 'Inter', system-ui, sans-serif !important;
  color: var(--text-primary) !important;
}

.fit-page {
  max-width: 900px;
  margin: 0 auto;
  padding: 1.5rem 1rem;
}

/* ── Cards ── */
.fit-card {
  background: var(--bg-card);
  border: 1px solid var(--border-card);
  border-radius: 12px;
  box-shadow: var(--shadow-card);
  margin-bottom: 1.5rem;
  overflow: hidden;
  padding: 0;
}

/* ── Card Headers ── */
.fit-card-header {
  padding: 14px 24px;
  border-bottom: 1px solid var(--border-card);
}
.fit-card-header.status-pending {
  background: #fffbeb;
  border-left: 4px solid #f59e0b;
}
.fit-card-header.status-assigned {
  background: #e6fffa;
  border-left: 4px solid var(--accent-teal);
}
.fit-card-header.status-completed {
  background: #dcfce7;
  border-left: 4px solid #16a34a;
}
.fit-card-header.status-cancelled {
  background: #fee2e2;
  border-left: 4px solid #ef4444;
}

/* ── Badges ── */
.badge-status {
  border-radius: 20px;
  padding: 5px 16px;
  font-size: 12px;
  font-weight: 600;
  color: #fff;
}
.badge-status.bg-warning { background-color: #f59e0b !important; }
.badge-status.bg-info { background-color: #06b6d4 !important; }
.badge-status.bg-success { background-color: #16a34a !important; }
.badge-status.bg-danger { background-color: #ef4444 !important; }

.badge-tag {
  background: #e2e8f0;
  color: #475569;
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 500;
  display: inline-block;
  margin-right: 4px;
  margin-bottom: 4px;
  text-transform: capitalize;
}

.badge-training-type {
  background: #3b82f6;
  color: #fff;
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 600;
  display: inline-block;
  margin-right: 4px;
  margin-bottom: 4px;
}

/* ── Buttons ── */
.btn-fit-primary {
  background: var(--accent-green-btn);
  color: #fff !important;
  border: none;
  border-radius: 8px;
  padding: 12px 20px;
  font-weight: 600;
  font-size: 14px;
  text-align: center;
  transition: all .2s;
  box-shadow: var(--shadow-sm);
  display: inline-block;
}
.btn-fit-primary:hover {
  background: var(--accent-green-hover);
  transform: translateY(-1px);
}

.btn-fit-outline {
  background: #ffffff;
  border: 1px solid var(--border-card);
  color: var(--text-secondary) !important;
  border-radius: 8px;
  padding: 12px 20px;
  font-weight: 600;
  font-size: 14px;
  text-align: center;
  transition: all .2s;
  display: inline-block;
}
.btn-fit-outline:hover {
  border-color: var(--accent-teal);
  color: var(--accent-teal) !important;
  background: #f0fdf9;
}

.btn-fit-ghost {
  background: transparent;
  border: 1px solid var(--accent-teal);
  color: var(--accent-teal) !important;
  border-radius: 8px;
  padding: 10px 24px;
  font-weight: 600;
  font-size: 14px;
  transition: all .2s;
  display: inline-block;
}
.btn-fit-ghost:hover {
  background: rgba(13,148,136,0.08);
}

.btn-back {
  background: #ffffff;
  border: 1px solid var(--border-card);
  border-radius: 8px;
  padding: 8px 14px;
  color: var(--text-secondary);
  font-size: 13px;
  transition: all .2s;
}
.btn-back:hover {
  border-color: var(--accent-teal);
  color: var(--accent-teal);
  background: #f0fdf9;
}

/* ── Notification Badge ── */
.notification-badge {
  position: absolute;
  top: -6px;
  right: -8px;
  background: #ef4444;
  color: white;
  font-size: 10px;
  font-weight: 700;
  min-width: 18px;
  height: 18px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 5px;
  box-shadow: 0 2px 4px rgba(239, 68, 68, 0.4);
}

/* ── Labels ── */
.detail-label {
  color: var(--text-secondary);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  margin-bottom: 4px;
  display: block;
}
</style>

<div class="fit-page">
  <div class="mb-4">
      <div class="d-flex justify-content-between align-items-center">
          <div>
              <h1 class="fw-extrabold mb-1" style="color: var(--text-primary); font-size: 26px; font-weight: 800;">
                  <i class="bi bi-clipboard-check me-2" style="color: var(--accent-teal)"></i>Fitness Training Status
              </h1>
              <p class="mb-0" style="color: var(--text-secondary); font-size: 14px;">Track your fitness training requests and progress</p>
          </div>
          <div class="d-flex gap-2">
              <a href="index.php?r=notification/index" class="btn-back btn text-decoration-none position-relative">
                  <i class="bi bi-bell me-1"></i>Notifications
                  <span class="notification-badge" id="notificationBadge" style="display:none;"></span>
              </a>
              <a href="index.php?r=member/dashboard" class="btn-back btn text-decoration-none">
                  <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
              </a>
          </div>
      </div>
  </div>

  <?php if (isset($_SESSION['success'])): ?>
  <div class="alert alert-success alert-dismissible fade show mb-4" style="border-radius: 12px;">
      <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php unset($_SESSION['success']); endif; ?>

  <?php if (isset($_SESSION['info'])): ?>
  <div class="alert alert-info alert-dismissible fade show mb-4" style="border-radius: 12px;">
      <i class="bi bi-info-circle me-2"></i><?= htmlspecialchars($_SESSION['info']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php unset($_SESSION['info']); endif; ?>

  <?php if (empty($requests)): ?>
  <!-- No Requests -->
  <div class="fit-card p-4 p-md-5 text-center">
      <div class="mb-3">
          <i class="bi bi-clipboard-x text-muted" style="font-size: 3.5rem;"></i>
      </div>
      <h5 class="mb-2" style="color: var(--text-primary); font-weight: 700;">No Active Personal Trainer Assigned Yet</h5>
      <p class="mb-4 text-muted small">You haven't requested a personal trainer yet. While you decide, you can generate an AI workout program or start tracking your meal plan right away!</p>
      
      <!-- Choices cards -->
      <div class="row g-3 justify-content-center mb-4 text-start">
          <div class="col-md-5">
              <div class="p-3 border rounded-3 bg-light h-100 shadow-sm">
                  <div class="fw-bold text-success mb-1"><i class="bi bi-lightning-charge-fill me-1"></i>AI Workout Program</div>
                  <div class="small text-muted mb-3">Generate a personalized workout program matched to your gym's equipment.</div>
                  <a href="index.php?r=membership/fitnessprogram" class="btn btn-sm btn-success w-100 rounded-pill font-weight-semibold">
                      <i class="bi bi-magic me-1"></i>Create AI Program
                  </a>
              </div>
          </div>
          <div class="col-md-5">
              <div class="p-3 border rounded-3 bg-light h-100 shadow-sm">
                  <div class="fw-bold text-teal mb-1" style="color:#0d9488;"><i class="bi bi-egg-fried me-1"></i>Meal Plan & Nutrition</div>
                  <div class="small text-muted mb-3">Submit a training request to unlock your personalized meal plan and log daily macros.</div>
                  <a href="index.php?r=fitness/request" class="btn btn-sm btn-outline-dark w-100 rounded-pill font-weight-semibold">
                      <i class="bi bi-person-hearts me-1"></i>Request Personal Trainer
                  </a>
              </div>
          </div>
      </div>
  </div>
  <?php else: ?>

  <!-- Request Cards -->
  <?php foreach ($requests as $request): 
      $__st = $request['status'];
      if ($__st === 'pending') { $statusColor = 'warning'; }
      elseif ($__st === 'assigned') { $statusColor = 'info'; }
      elseif ($__st === 'completed') { $statusColor = 'success'; }
      elseif ($__st === 'cancelled') { $statusColor = 'danger'; }
      else { $statusColor = 'secondary'; }

      if ($__st === 'pending') { $statusIcon = 'bi-clock-history'; }
      elseif ($__st === 'assigned') { $statusIcon = 'bi-person-check'; }
      elseif ($__st === 'completed') { $statusIcon = 'bi-check-circle'; }
      elseif ($__st === 'cancelled') { $statusIcon = 'bi-x-circle'; }
      else { $statusIcon = 'bi-question-circle'; }
      $statusText = ucfirst($request['status']);
      
      $trainingTypes = explode(',', $request['training_type']);
      $trainingPrefs = explode(',', $request['training_preference']);
  ?>
  <div class="fit-card">
      <div class="fit-card-header status-<?= $request['status'] ?> d-flex justify-content-between align-items-center">
          <div>
              <h5 class="mb-1 fw-bold" style="font-size: 1.1rem; color: var(--text-primary);">
                  <i class="bi <?= $statusIcon ?> me-2"></i>Request #<?= $request['id'] ?>
              </h5>
              <small style="color: var(--text-secondary); font-size: 12px;">
                  Submitted: <?= date('M j, Y g:i A', strtotime($request['created_at'])) ?>
              </small>
          </div>
          <span class="badge-status bg-<?= $statusColor ?>"><?= $statusText ?></span>
      </div>
      <div class="p-4">
          <div class="row g-4">
              <div class="col-md-6">
                  <h6 class="fw-bold mb-3" style="color: var(--accent-teal); font-size: 14px;">Request Details</h6>
                  <div class="mb-3">
                      <span class="detail-label">Training Types</span>
                      <div class="mt-1">
                          <?php foreach ($trainingTypes as $type): ?>
                          <span class="badge-training-type">
                              <?= ucwords(str_replace('_', ' ', trim($type))) ?>
                          </span>
                          <?php endforeach; ?>
                      </div>
                  </div>
                  <div class="mb-3">
                      <span class="detail-label">Sessions</span>
                      <span style="font-size: 14px; font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars((string)$request['session_preference']) ?> session<?= (int)$request['session_preference'] !== 1 ? 's' : '' ?></span>
                  </div>
                  <div class="mb-3">
                      <span class="detail-label">Schedule Preference</span>
                      <div class="mt-1">
                          <?php
                          // Prefer new JSON schedule, fall back to legacy string
                          $schedItems = [];
                          if (!empty($request['schedule_preference_json'])) {
                              $decoded = json_decode($request['schedule_preference_json'], true);
                              if (is_array($decoded)) {
                                  foreach ($decoded as $s) {
                                      $schedItems[] = ($s['day'] ?? '') . ' ' . ($s['time'] ?? '');
                                  }
                              }
                          }
                          if (empty($schedItems) && !empty($request['training_preference'])) {
                              $schedItems = array_map('trim', explode(',', $request['training_preference']));
                          }
                          foreach ($schedItems as $item):
                          ?>
                          <span class="badge-tag">
                              <?= htmlspecialchars(ucwords($item)) ?>
                          </span>
                          <?php endforeach; ?>
                      </div>
                  </div>
                  <?php
                  // Show normalised address if available, else legacy address
                  $addrParts = array_filter([
                      $request['street']   ?? '',
                      $request['barangay'] ?? '',
                      $request['city']     ?? ($request['city'] ?? ''),
                      $request['province'] ?? '',
                  ]);
                  $addrStr = $addrParts ? implode(', ', $addrParts) : ($request['address'] ?? '');
                  if ($addrStr):
                  ?>
                  <div class="mb-3">
                      <span class="detail-label">Address</span>
                      <span style="font-size: 14px; font-weight: 500; color: var(--text-primary);"><?= htmlspecialchars($addrStr) ?></span>
                  </div>
                  <?php endif; ?>
              </div>

              <div class="col-md-6">
                  <h6 class="fw-bold mb-3" style="color: var(--accent-teal); font-size: 14px;">Status Information</h6>
                  <?php if ($request['status'] === 'pending'): ?>
                  <div class="alert alert-warning mb-3" style="border-radius: 8px;">
                      <i class="bi bi-hourglass-split me-2"></i>
                      <strong>Waiting for Trainer Assignment</strong>
                      <p class="mb-0 small mt-1">An administrative officer is reviewing your request to assign a qualified coach.</p>
                  </div>

                  <!-- Self-Guided Choices while waiting for a coach -->
                  <div class="card p-3 border-0 bg-light rounded-3 mb-2" style="border-left: 4px solid #1B6B2A !important;">
                      <h6 class="fw-bold mb-1 text-success" style="font-size: 13px;">
                          <i class="bi bi-lightning-charge-fill me-1"></i> While You Wait For Your Coach:
                      </h6>
                      <p class="small text-muted mb-2">You don't have to wait! Choose an action below to start training right now:</p>
                      <div class="d-flex flex-column gap-2">
                          <a href="index.php?r=membership/fitnessprogram" class="btn btn-sm btn-success rounded-pill fw-semibold text-start px-3">
                              <i class="bi bi-play-circle-fill me-2"></i>🚀 Start Today's Workout & Track Sets
                          </a>
                          <a href="index.php?r=membership/fitnessprogram" class="btn btn-sm btn-outline-success rounded-pill fw-semibold text-start px-3">
                              <i class="bi bi-pencil-square me-2"></i>Build / Customize Workout Program
                          </a>
                          <a href="index.php?r=fitness/plan&request_id=<?= $request['id'] ?>" class="btn btn-sm btn-outline-dark rounded-pill fw-semibold text-start px-3">
                              <i class="bi bi-egg-fried me-2"></i>Create & Track Meal Plan
                          </a>
                      </div>
                  </div>
                  <?php elseif ($request['status'] === 'assigned'): ?>
                  <div class="alert alert-info mb-3" style="border-radius: 8px;">
                      <i class="bi bi-person-check me-2"></i>
                      <strong>Trainer Assigned!</strong>
                      <p class="mb-0 small mt-1">
                          Trainer: <strong><?= htmlspecialchars($request['trainer_name'] ?? 'N/A') ?></strong>
                          <?php if (!empty($request['trainer_specialization'])): ?>
                          <br>Specialization: <?= htmlspecialchars($request['trainer_specialization']) ?>
                          <?php endif; ?>
                      </p>
                  </div>
                  <div class="d-grid gap-2">
                      <a href="index.php?r=membership/fitnessprogram" 
                         class="btn-fit-primary text-decoration-none bg-success">
                          <i class="bi bi-play-circle-fill me-2"></i>🚀 Start Today's Workout Session
                      </a>
                      <a href="index.php?r=fitness/profile&request_id=<?= $request['id'] ?>" 
                         class="btn-fit-outline text-decoration-none">
                          <i class="bi bi-pencil-square me-2"></i>Complete Your Profile
                      </a>
                      <a href="index.php?r=fitness/plan&request_id=<?= $request['id'] ?>" 
                         class="btn-fit-outline text-decoration-none">
                          <i class="bi bi-file-text me-2"></i>View Training Plan & Meal Plan
                      </a>
                      <a href="index.php?r=fitness/progress&request_id=<?= $request['id'] ?>" 
                         class="btn-fit-outline text-decoration-none">
                          <i class="bi bi-graph-up me-2"></i>Track Progress Log
                      </a>
                  </div>
                  <?php elseif ($request['status'] === 'completed'): ?>
                  <div class="alert alert-success mb-0" style="border-radius: 8px;">
                      <i class="bi bi-check-circle me-2"></i>
                      <strong>Training Completed</strong>
                      <p class="mb-0 small mt-1">You have successfully completed this training program!</p>
                  </div>
                  <?php elseif ($request['status'] === 'cancelled'): ?>
                  <div class="alert alert-danger mb-0" style="border-radius: 8px;">
                      <i class="bi bi-x-circle me-2"></i>
                      <strong>Request Cancelled</strong>
                      <p class="mb-0 small mt-1">This training request has been cancelled.</p>
                  </div>
                  <?php endif; ?>
              </div>
          </div>

          <?php if (!empty($request['specific_trainer_request'])): ?>
          <div class="mt-3 pt-3 border-top">
              <h6 class="fw-bold mb-2" style="color: var(--text-secondary); font-size: 12px; text-transform: uppercase;">Special Requests</h6>
              <p class="mb-0 small text-dark"><?= nl2br(htmlspecialchars($request['specific_trainer_request'])) ?></p>
          </div>
          <?php endif; ?>
      </div>
  </div>
  <?php endforeach; ?>

  <!-- New Request Button -->
  <div class="text-center mt-4 mb-4">
      <a href="index.php?r=fitness/request" class="btn-fit-ghost text-decoration-none">
          <i class="bi bi-plus-circle me-2"></i>Submit Another Request
      </a>
  </div>

  <?php endif; ?>
</div>

<script>
// Check unread notifications on page load
async function checkUnreadNotifications() {
    try {
        const response = await fetch('index.php?r=notification/getUnreadCount');
        const data = await response.json();
        
        if (data.success && data.count > 0) {
            const badge = document.getElementById('notificationBadge');
            badge.textContent = data.count;
            badge.style.display = 'flex';
        }
    } catch (error) {
        console.error('Error checking notifications:', error);
    }
}

// Run on page load
document.addEventListener('DOMContentLoaded', checkUnreadNotifications);

</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>

