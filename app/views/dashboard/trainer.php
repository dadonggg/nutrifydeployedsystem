<?php
declare(strict_types=1);
$pageTitle = 'Trainer Dashboard';
require __DIR__ . '/../partials/header.php';

$displayName = htmlspecialchars($user['fullname'] ?? 'Trainer', ENT_QUOTES, 'UTF-8');
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

:root {
  --bg-page:           #f0f2f0;
  --bg-card:           #ffffff;
  --bg-section-header: #e8f5f0;
  --border-card:       #e2e8f0;
  --border-teal:       #0d9488;
  --border-trainer:    #99f6e4;
  --accent-teal:       #0d9488;
  --accent-blue:       #06b6d4;
  --accent-green-btn:  #166534;
  --accent-green-hover:#15803d;
  --text-primary:      #1e293b;
  --text-secondary:    #64748b;
  --shadow-card: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.05);
  --shadow-sm:   0 1px 2px rgba(0,0,0,0.06);
}

body {
  background: var(--bg-page) !important;
  font-family: 'Inter', system-ui, sans-serif !important;
  color: var(--text-primary) !important;
}

/* ── Mini Stat ── */
.mini-stat {
  background: var(--bg-card);
  border: 1px solid var(--border-card);
  border-radius: 12px;
  padding: 1.2rem 1rem;
  text-align: center;
  box-shadow: var(--shadow-sm);
  height: 100%;
}
.mini-num {
  font-weight: 800;
  font-size: 1.8rem;
  line-height: 1;
}
.mini-lbl {
  font-size: 11px;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-top: 6px;
}

/* ── Cards ── */
.dashboard-card {
  background: var(--bg-card);
  border: 1px solid var(--border-card);
  border-radius: 12px;
  box-shadow: var(--shadow-sm);
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.dashboard-card-hd {
  background: var(--bg-section-header);
  border-left: 4px solid var(--accent-teal);
  border-bottom: 1px solid var(--border-card);
  padding: 14px 20px;
}

.dashboard-heading {
  color: var(--accent-teal) !important;
  font-size: 13px;
  font-weight: 800;
  letter-spacing: 2px;
  text-transform: uppercase;
  margin: 0;
}

/* ── Table Styling ── */
.table-fit {
  width: 100%;
  margin-bottom: 0;
  color: var(--text-primary);
  vertical-align: middle;
}
.table-fit th {
  background: #f8fafc;
  color: var(--text-secondary);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-card);
}
.table-fit td {
  padding: 16px;
  border-bottom: 1px solid var(--border-card);
  font-size: 14px;
}
.table-fit tr:last-child td {
  border-bottom: none;
}

/* ── Badges ── */
.badge-fit {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
}
.badge-assigned { background: #e0f2fe; color: #0369a1; }
.badge-completed { background: #dcfce7; color: #16a34a; }
.badge-pending { background: #fef9c3; color: #854d0e; }
.badge-info { background: #eff6ff; color: #2563eb; }

.badge-tag {
  display: inline-block;
  background: var(--bg-page);
  color: var(--text-secondary);
  border-radius: 4px;
  padding: 2px 8px;
  font-size: 11px;
  font-weight: 600;
  margin-right: 4px;
}

/* ── Buttons ── */
.btn-action-teal {
  border: 1.5px solid var(--accent-teal);
  color: var(--accent-teal);
  background: transparent;
  border-radius: 8px;
  padding: 6px 12px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  transition: all .2s;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: .3rem;
}
.btn-action-teal:hover { background: rgba(13,148,136,0.08); color: var(--accent-teal); }

.btn-action-green {
  border: 1.5px solid #16a34a;
  color: #16a34a;
  background: transparent;
  border-radius: 8px;
  padding: 6px 12px;
  font-weight: 600;
  font-size: 13px;
  cursor: pointer;
  transition: all .2s;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: .3rem;
}
.btn-action-green:hover { background: #f0fdf4; color: #16a34a; }

.btn-quick {
  background: #ffffff;
  border: 1px solid var(--border-card);
  border-radius: 10px;
  padding: 12px 16px;
  color: var(--text-primary);
  transition: all .2s;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 12px;
}
.btn-quick:hover {
  border-color: var(--accent-teal);
  background: #f0fdf9;
  transform: translateY(-1px);
}
</style>

<div class="p-1">
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
    <div>
      <h1 class="fw-extrabold mb-1" style="color: var(--text-primary); font-size: clamp(20px, 4vw, 26px); font-weight: 800;">
        <i class="bi bi-person-arms-up me-2" style="color: var(--accent-teal)"></i>Trainer Dashboard
      </h1>
      <p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 0;">Welcome, <?= $displayName ?>. Manage your clients and fitness programs.</p>
    </div>
    <span class="badge-fit badge-info fs-6">
      <i class="bi bi-person-badge me-1"></i>Fitness Trainer
    </span>
  </div>

  <!-- Statistics Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="mini-num" style="color: var(--accent-teal)"><?= $totalClients ?></div>
        <div class="mini-lbl">Total Clients</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="mini-num" style="color: #3b82f6"><?= $activeClients ?></div>
        <div class="mini-lbl">Active Clients</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="mini-num" style="color: #d97706"><?= count($pendingReviews) ?></div>
        <div class="mini-lbl">Pending Reviews</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="mini-num" style="color: #16a34a"><?= $completedClients ?></div>
        <div class="mini-lbl">Completed</div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Left Column -->
    <div class="col-lg-8">
      <!-- My Clients -->
      <div class="dashboard-card">
        <div class="dashboard-card-hd">
          <h5 class="dashboard-heading"><i class="bi bi-people-fill me-2"></i>My Clients</h5>
        </div>
        <div class="p-0">
          <?php if (empty($assignedClients)): ?>
          <div class="text-center py-5">
            <i class="bi bi-inbox text-muted" style="font-size: 3rem; opacity: .3;"></i>
            <h6 class="mt-3 fw-bold" style="color: var(--text-primary);">No Clients Assigned Yet</h6>
            <p style="color: var(--text-secondary); font-size: 13px;">You'll see your assigned clients here once the administrative officer assigns them to you.</p>
          </div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table-fit">
              <thead>
                <tr>
                  <th>Client Name</th>
                  <th>Training Type</th>
                  <th>Sessions/Week</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($assignedClients as $client): 
                    $trainingTypes = explode(',', $client['training_type']);
                    $statusClass = $client['status'] === 'assigned' ? 'badge-assigned' : ($client['status'] === 'completed' ? 'badge-completed' : 'badge-pending');
                ?>
                <tr>
                  <td>
                    <div class="fw-semibold" style="color: var(--text-primary);"><?= htmlspecialchars($client['member_name']) ?></div>
                    <small style="color: var(--text-secondary); font-size: 12px;"><?= htmlspecialchars($client['membership_code']) ?></small>
                  </td>
                  <td>
                    <?php foreach (array_slice($trainingTypes, 0, 2) as $type): ?>
                    <span class="badge-tag">
                      <?= ucwords(str_replace('_', ' ', trim($type))) ?>
                    </span>
                    <?php endforeach; ?>
                  </td>
                  <td style="color: var(--text-secondary);"><?= $client['session_preference'] ?> sessions</td>
                  <td>
                    <span class="badge-fit <?= $statusClass ?>">
                      <?= ucfirst($client['status']) ?>
                    </span>
                  </td>
                  <td>
                    <div class="d-flex gap-2">
                      <a href="index.php?r=trainer/clients" class="btn-action-teal" title="View Profile & Details">
                        <i class="bi bi-eye"></i>
                      </a>
                      <a href="index.php?r=trainer/createPlan&request_id=<?= $client['id'] ?>" class="btn-action-green" title="Create/Edit Plan">
                        <i class="bi bi-file-earmark-text"></i>
                      </a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Pending Progress Reviews -->
      <?php if (!empty($pendingReviews)): ?>
      <div class="dashboard-card">
        <div class="dashboard-card-hd" style="background: #fffbeb; border-left: 4px solid #f59e0b;">
          <h5 class="dashboard-heading" style="color: #d97706 !important;">
            <i class="bi bi-clipboard-check me-2"></i>Pending Progress Reviews
            <span class="badge bg-warning ms-2" style="font-size: 12px;"><?= count($pendingReviews) ?></span>
          </h5>
        </div>
        <div class="p-4">
          <?php foreach ($pendingReviews as $review): 
              $progress = $review['progress'];
          ?>
          <div class="d-flex align-items-start mb-3 pb-3" style="border-bottom: 1px solid var(--border-card);">
            <div class="flex-shrink-0 me-3">
              <div style="width:40px;height:40px;background:#fef3c7;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <i class="bi bi-person-circle text-warning fs-5"></i>
              </div>
            </div>
            <div class="flex-grow-1">
              <h6 class="mb-1 fw-bold" style="color: var(--text-primary);"><?= htmlspecialchars($review['member_name']) ?></h6>
              <div class="d-flex gap-3 flex-wrap mb-2">
                <small style="color: var(--text-secondary);">
                  <i class="bi bi-trophy me-1"></i>
                  Score: <strong style="color: var(--text-primary);"><?= number_format((float)$progress['consistency_score'], 1) ?></strong>
                </small>
                <small style="color: var(--text-secondary);">
                  <i class="bi bi-fire me-1"></i>
                  Streak: <strong style="color: var(--text-primary);"><?= (int)$progress['current_streak'] ?> days</strong>
                </small>
                <small style="color: var(--text-secondary);">
                  <i class="bi bi-calendar-check me-1"></i>
                  Logged: <strong style="color: var(--text-primary);"><?= (int)$progress['total_logged_days'] ?> days</strong>
                </small>
              </div>
              <a href="index.php?r=trainer/progress" class="btn-action-teal mt-1">
                <i class="bi bi-pencil-square me-1"></i>Review & Send Feedback
              </a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Right Column -->
    <div class="col-lg-4">
      <!-- Quick Actions -->
      <div class="dashboard-card">
        <div class="dashboard-card-hd">
          <h5 class="dashboard-heading"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h5>
        </div>
        <div class="p-4 d-flex flex-column gap-2">
          <a href="index.php?r=trainer/clients" class="btn-quick">
            <i class="bi bi-people fs-5" style="color: var(--accent-teal);"></i>
            <div class="text-start">
              <div class="fw-semibold" style="font-size: 14px; color: var(--text-primary);">View All Clients</div>
              <small style="color: var(--text-secondary); font-size: 12px;">Manage client list</small>
            </div>
          </a>
          <a href="index.php?r=trainer/progress" class="btn-quick">
            <i class="bi bi-graph-up fs-5" style="color: #d97706;"></i>
            <div class="text-start">
              <div class="fw-semibold" style="font-size: 14px; color: var(--text-primary);">Progress Reviews</div>
              <small style="color: var(--text-secondary); font-size: 12px;">View & review submissions</small>
            </div>
          </a>
          <a href="index.php?r=trainer/equipment" class="btn-quick">
            <i class="bi bi-tools fs-5" style="color: var(--accent-blue);"></i>
            <div class="text-start">
              <div class="fw-semibold" style="font-size: 14px; color: var(--text-primary);">View Equipment</div>
              <small style="color: var(--text-secondary); font-size: 12px;">Browse gym equipment</small>
            </div>
          </a>
        </div>
      </div>

      <!-- Trainer Info -->
      <div class="dashboard-card">
        <div class="dashboard-card-hd" style="background: #f8fafc; border-left: 4px solid var(--text-secondary);">
          <h5 class="dashboard-heading" style="color: var(--text-secondary) !important;"><i class="bi bi-person-badge me-2"></i>Your Profile</h5>
        </div>
        <div class="p-4">
          <div class="d-flex flex-column gap-2" style="font-size: 14px;">
            <div class="d-flex justify-content-between">
              <span style="color: var(--text-secondary);">Name</span>
              <strong style="color: var(--text-primary);"><?= $displayName ?></strong>
            </div>
            <div class="d-flex justify-content-between">
              <span style="color: var(--text-secondary);">Email</span>
              <strong style="color: var(--text-primary);"><?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div class="d-flex justify-content-between">
              <span style="color: var(--text-secondary);">Role</span>
              <strong style="color: var(--text-primary);">Fitness Trainer</strong>
            </div>
            <div class="d-flex justify-content-between">
              <span style="color: var(--text-secondary);">Status</span>
              <strong style="color: #16a34a;">Active</strong>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
