<?php
declare(strict_types=1);
$pageTitle = 'Progress Reviews — Trainer Dashboard';
require __DIR__ . '/../partials/header.php';
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

:root {
  --bg-page:           #f0f2f0;
  --bg-card:           #ffffff;
  --bg-section-header: #e8f5f0;
  --bg-input:          #ffffff;
  --bg-trainer-box:    #f0fdf9;
  --border-card:       #e2e8f0;
  --border-input:      #cbd5e1;
  --border-teal:       #0d9488;
  --border-trainer:    #99f6e4;
  --accent-teal:       #0d9488;
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

/* ── Progress Item Card ── */
.progress-item {
  background: var(--bg-card);
  border: 1px solid var(--border-card);
  border-radius: 12px;
  box-shadow: var(--shadow-sm);
  transition: all .2s;
  overflow: hidden;
}
.progress-item:hover {
  border-color: var(--accent-teal);
  box-shadow: 0 4px 16px rgba(13,148,136,0.1);
}

/* ── Score / Streak Badges ── */
.score-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--bg-section-header);
  border: 1px solid var(--border-trainer);
  border-radius: 8px;
  padding: 6px 14px;
  font-weight: 800;
  font-size: 1.1rem;
  color: var(--accent-teal);
}
.streak-badge {
  background: #fff7ed;
  border: 1px solid #fed7aa;
  border-radius: 8px;
  padding: 6px 12px;
  font-size: 13px;
  color: #ea580c;
  font-weight: 600;
}
.stat-chip {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: #f1f5f9;
  border: 1px solid var(--border-card);
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 12px;
  color: var(--text-secondary);
  font-weight: 500;
}
.sent-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: #dcfce7;
  color: #16a34a;
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 600;
}

/* ── Inputs ── */
.fit-input {
  background: var(--bg-input) !important;
  border: 1px solid var(--border-input) !important;
  color: var(--text-primary) !important;
  border-radius: 8px !important;
  padding: 10px 14px !important;
  font-size: 14px !important;
  transition: border-color .2s, box-shadow .2s;
}
.fit-input:focus {
  border-color: var(--accent-teal) !important;
  box-shadow: 0 0 0 3px rgba(13,148,136,0.12) !important;
  outline: none;
}
.fit-label {
  color: var(--text-secondary);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  margin-bottom: 6px;
  display: block;
}

/* ── Feedback Form ── */
.btn-feedback {
  background: transparent;
  border: 1.5px solid #d97706;
  color: #d97706;
  border-radius: 8px;
  padding: 8px 16px;
  font-weight: 600;
  font-size: 13px;
  transition: all .2s;
  cursor: pointer;
}
.btn-feedback:hover {
  background: #fffbeb;
  border-color: #d97706;
}

.feedback-form {
  background: #f8fafc;
  border: 1px solid var(--border-card);
  border-radius: 10px;
  padding: 1.2rem;
  display: none;
  animation: fadeIn .3s ease;
  margin-top: 1rem;
}
.feedback-form.open { display: block; }
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-6px); }
  to { opacity: 1; transform: none; }
}

/* ── Submit Button ── */
.btn-fit {
  background: var(--accent-green-btn);
  color: #fff !important;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  padding: 10px 20px;
  transition: all .2s;
  cursor: pointer;
}
.btn-fit:hover {
  background: var(--accent-green-hover);
  transform: translateY(-1px);
  color: #fff !important;
}
.btn-cancel-feedback {
  background: #ffffff;
  border: 1px solid var(--border-card);
  color: var(--text-secondary);
  border-radius: 8px;
  padding: 8px 14px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all .2s;
}
.btn-cancel-feedback:hover {
  border-color: var(--accent-teal);
  color: var(--accent-teal);
  background: #f0fdf9;
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
</style>

<div class="p-1">
  <div class="d-flex justify-content-between align-items-start mb-4">
    <div>
      <h1 class="fw-extrabold mb-1" style="color: var(--text-primary); font-size: 26px; font-weight: 800;">
        <i class="bi bi-graph-up-arrow me-2" style="color: #d97706"></i>Progress Reviews
      </h1>
      <p style="color: var(--text-secondary); font-size: 14px;">Client progress submissions sent to you for review</p>
    </div>
    <a href="index.php?r=trainer/clients" class="btn-back btn text-decoration-none">
      <i class="bi bi-arrow-left me-1"></i>My Clients
    </a>
  </div>

  <?php if (!empty($_SESSION['success'])): ?>
  <div class="alert alert-success mb-4" style="border-radius: 12px;">
    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
  </div>
  <?php unset($_SESSION['success']); endif; ?>

  <?php if (empty($progressList)): ?>
  <div class="progress-item p-5 text-center">
    <i class="bi bi-graph-up" style="font-size:3rem; color: var(--text-secondary); opacity:.3;"></i>
    <h5 class="mt-3 mb-2 fw-bold" style="color: var(--text-primary);">No Progress Submissions Yet</h5>
    <p style="color: var(--text-secondary); font-size: 14px;">Clients will appear here once they send their progress to you.</p>
  </div>
  <?php else: ?>
  <div class="d-flex flex-column gap-3">
    <?php foreach ($progressList as $idx => $prog): 
        $align = $prog['alignment'] ?? [];
        $isWarning = ($align['status'] ?? '') === 'warning';
        $isOnTrack = ($align['status'] ?? '') === 'on_track';
        $coachHint = $align['coach_hint'] ?? '';
    ?>
    <div class="progress-item" style="<?= $isWarning ? 'border-left: 4px solid #ef4444;' : ($isOnTrack ? 'border-left: 4px solid #10b981;' : '') ?>">
      <div class="p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
          <div>
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
              <h5 class="mb-0 fw-bold" style="font-size: 1.15rem; color: var(--text-primary);"><?= htmlspecialchars($prog['client_name'] ?? $prog['member_fullname'] ?? 'Client') ?></h5>
              <?php if (!empty($prog['fitness_goal'])): ?>
              <span class="badge" style="background:#f1f5f9; color:#334155; font-size:11px; border:1px solid #cbd5e1;">
                🎯 Goal: <?= ucfirst($prog['fitness_goal']) ?>
              </span>
              <?php endif; ?>
              <?php if ($isWarning): ?>
              <span class="badge bg-danger text-white" style="font-size:11px; font-weight:700;">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>Off Track / Warning
              </span>
              <?php elseif ($isOnTrack): ?>
              <span class="badge bg-success text-white" style="font-size:11px; font-weight:700;">
                <i class="bi bi-check-circle-fill me-1"></i>On Track
              </span>
              <?php endif; ?>
              <?php if (!empty($prog['feedback_given'])): ?>
              <span class="sent-badge"><i class="bi bi-check-circle-fill me-1"></i>Feedback Sent</span>
              <?php endif; ?>
            </div>
            <div class="d-flex gap-2 flex-wrap">
              <span class="stat-chip"><i class="bi bi-calendar me-1"></i><?= date('M j, Y', strtotime($prog['sent_at'] ?? $prog['snapshot_date'])) ?></span>
              <span class="stat-chip"><i class="bi bi-journal me-1"></i><?= $prog['total_logged_days'] ?> logged days</span>
              <span class="stat-chip"><i class="bi bi-lightning me-1"></i><?= $prog['total_workouts'] ?> workouts</span>
              <span class="stat-chip"><i class="bi bi-egg-fried me-1"></i><?= $prog['total_nutrition_logs'] ?> meals</span>
              <span class="stat-chip"><i class="bi bi-activity me-1"></i><?= number_format((float)$prog['workout_frequency_per_week'],1) ?>/wk avg</span>
            </div>
          </div>
          <div class="d-flex align-items-center gap-3">
            <div>
              <div class="score-badge">
                <i class="bi bi-star-fill" style="font-size:.85rem;"></i>
                <?= number_format((float)$prog['consistency_score'], 1) ?>
              </div>
              <div style="color: var(--text-secondary); font-size: 11px; text-align: center; margin-top: 3px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Score</div>
            </div>
            <div>
              <div class="streak-badge">
                🔥 <?= $prog['current_streak'] ?> day streak
              </div>
            </div>
          </div>
        </div>

        <!-- ── Client Goal Misalignment Alert Banner ── -->
        <?php if (!empty($align['alert'])): ?>
        <div class="p-3 my-3 rounded-3 d-flex align-items-start gap-2 shadow-sm" 
             style="background: <?= $isWarning ? '#fef2f2' : '#f0fdf4' ?>; border: 1.5px solid <?= $isWarning ? '#fca5a5' : '#86efac' ?>; color: <?= $isWarning ? '#991b1b' : '#166534' ?>;">
          <i class="bi <?= $isWarning ? 'bi-exclamation-triangle-fill text-danger' : 'bi-check-circle-fill text-success' ?> fs-5 mt-0 flex-shrink-0"></i>
          <div class="flex-grow-1">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
              <strong style="font-size: 13.5px;"><?= htmlspecialchars($align['title'] ?? 'Goal Alignment Notice') ?></strong>
              <span class="badge <?= $align['badgeClass'] ?? 'bg-secondary' ?> px-2 py-1" style="font-size: 11px;">
                <?= $align['badge'] ?? '' ?>
              </span>
            </div>
            <div class="small" style="font-size: 12.5px; line-height: 1.4;">
              <?= htmlspecialchars($align['alert']) ?>
            </div>
            <?php if (!empty($prog['first_weight']) && !empty($prog['latest_weight'])): ?>
            <div class="mt-2 pt-2 border-top d-flex gap-3 text-muted small flex-wrap" style="font-size: 11.5px; border-color: rgba(0,0,0,0.08) !important;">
              <span>Starting: <strong class="text-dark"><?= number_format((float)$prog['first_weight']['weight_kg'], 1) ?> kg</strong> (<?= date('M j', strtotime($prog['first_weight']['date_logged'])) ?>)</span>
              <span>&rarr;</span>
              <span>Current: <strong class="text-dark"><?= number_format((float)$prog['latest_weight']['weight_kg'], 1) ?> kg</strong> (<?= date('M j', strtotime($prog['latest_weight']['date_logged'])) ?>)</span>
              <span class="fw-bold" style="color:<?= (float)$prog['net_change'] > 0 ? ($prog['fitness_goal'] === 'cutting' ? '#ef4444' : '#10b981') : ($prog['fitness_goal'] === 'bulking' ? '#ef4444' : '#10b981') ?>;">
                Net Change: <?= (float)$prog['net_change'] > 0 ? '+' : '' ?><?= $prog['net_change'] ?> kg
              </span>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Feedback Button + Form -->
        <div class="mt-3 pt-3" style="border-top: 1px solid var(--border-card);">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <button class="btn-feedback <?= $isWarning ? 'btn-danger text-white border-0' : '' ?>" 
                    style="<?= $isWarning ? 'background:#ef4444; color:#fff !important;' : '' ?>"
                    onclick="toggleFeedback(<?= $idx ?>)">
              <i class="bi bi-chat-left-text me-1"></i><?= $isWarning ? 'Provide Corrective Feedback' : 'Send Feedback' ?>
            </button>
            <?php if ($isWarning): ?>
            <small class="text-danger fw-semibold"><i class="bi bi-bell-fill me-1"></i>Client requires guidance on weight trajectory</small>
            <?php endif; ?>
          </div>

          <div class="feedback-form" id="fbForm<?= $idx ?>">
            <form method="POST" action="index.php?r=trainer/sendFeedback">
              <input type="hidden" name="progress_id" value="<?= $prog['id'] ?>">
              <input type="hidden" name="service_request_id" value="<?= $prog['service_request_id'] ?>">
              <input type="hidden" name="member_id" value="<?= $prog['member_id'] ?>">
              
              <?php if (!empty($coachHint)): ?>
              <div class="mb-3 p-2 rounded-2 bg-white border border-warning d-flex align-items-center justify-content-between gap-2">
                <small class="text-muted" style="font-size: 11.5px;">
                  <i class="bi bi-lightbulb-fill text-warning me-1"></i><strong>Coach Suggestion:</strong> <?= htmlspecialchars(mb_strimwidth($coachHint, 0, 100, '...')) ?>
                </small>
                <button type="button" class="btn btn-sm btn-outline-warning text-dark py-0 px-2 fw-semibold" style="font-size:11px; white-space:nowrap;"
                        onclick="autofillFeedback(<?= $idx ?>, <?= htmlspecialchars(json_encode($coachHint), ENT_QUOTES) ?>)">
                  ⚡ Autofill Suggestion
                </button>
              </div>
              <?php endif; ?>

              <div class="row g-3">
                <div class="col-md-8">
                  <label class="fit-label">Feedback Status</label>
                  <select name="feedback_status" class="form-select fit-input form-select-sm">
                    <option value="needs_adjustment" <?= $isWarning ? 'selected' : '' ?>>⚠️ Needs Adjustment / Off Track</option>
                    <option value="on_track" <?= $isOnTrack ? 'selected' : '' ?>>✅ On Track</option>
                    <option value="plateau">⚖️ Plateau / Steady</option>
                    <option value="goal_achieved">🏆 Goal Achieved</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="fit-label">Feedback Message <span style="color:#ef4444">*</span></label>
                  <textarea name="feedback_text" id="fbText<?= $idx ?>" class="form-control fit-input" rows="3" required
                            placeholder="Overall assessment of the client's progress, what they're doing well, and what to adjust..."></textarea>
                </div>
                <div class="col-md-4">
                  <label class="fit-label">Areas of Improvement</label>
                  <textarea name="areas_of_improvement" id="fbAreas<?= $idx ?>" class="form-control fit-input" rows="3"
                            placeholder="e.g. Daily caloric deficit tracking, portion size control, cardio frequency..."></textarea>
                </div>
                <div class="col-md-4">
                  <label class="fit-label">Encouragement</label>
                  <textarea name="encouragement" class="form-control fit-input" rows="3"
                            placeholder="Words of motivation..."></textarea>
                </div>
                <div class="col-md-4">
                  <label class="fit-label">Next Steps</label>
                  <textarea name="next_steps" class="form-control fit-input" rows="3"
                            placeholder="e.g. Log every meal daily, weigh in twice weekly, adjust calorie target..."></textarea>
                </div>
                <div class="col-12 d-flex gap-2 justify-content-end">
                  <button type="button" class="btn-cancel-feedback" onclick="toggleFeedback(<?= $idx ?>)">Cancel</button>
                  <button type="submit" class="btn-fit btn">
                    <i class="bi bi-send-check me-1"></i>Submit Feedback
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<script>
function toggleFeedback(idx) {
  const form = document.getElementById('fbForm' + idx);
  if (form) form.classList.toggle('open');
}

function autofillFeedback(idx, hintText) {
  const txt = document.getElementById('fbText' + idx);
  if (txt) {
    txt.value = hintText;
    txt.focus();
  }
}
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
