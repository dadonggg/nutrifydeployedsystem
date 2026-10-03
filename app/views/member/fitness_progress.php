<?php
declare(strict_types=1);
$pageTitle = 'Progress Tracking — Step 4';
require __DIR__ . '/../partials/header.php';
$displayName = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
if ($displayName === '') $displayName = $user['fullname'] ?? 'Member';
$requestId = $request['id'];
$score     = (float)($currentProgress['consistency_score'] ?? 0);
$streak    = (int)($currentProgress['current_streak'] ?? 0);
$totalDays = (int)($currentProgress['total_logged_days'] ?? 0);
$workouts  = (int)($currentProgress['total_workouts'] ?? 0);
$nutrition = (int)($currentProgress['total_nutrition_logs'] ?? 0);
$freq      = (float)($currentProgress['workout_frequency_per_week'] ?? 0);

// Build chart data
$chartLabels = [];
$chartValues = [];
foreach ($weeklyFrequency as $w) {
    $yr = substr((string)$w['week'], 0, 4);
    $wk = substr((string)$w['week'], 4, 2);
    $chartLabels[] = "W{$wk} '{$yr}";
    $chartValues[] = (int)$w['workout_days'];
}
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

/* ── Step Bar ── */
.step-bar {
  display: flex;
  margin-bottom: 2rem;
  position: relative;
}
.step-bar::before {
  content: '';
  position: absolute;
  top: 18px;
  left: 10%;
  right: 10%;
  height: 2px;
  background: var(--border-card);
  z-index: 0;
}
.step-item {
  flex: 1;
  text-align: center;
  position: relative;
  z-index: 1;
}
.step-circle {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 6px;
  font-weight: 700;
  font-size: .85rem;
  border: 2px solid var(--border-card);
  background: var(--bg-card);
  color: var(--text-secondary);
  transition: all .3s;
}
.step-item.done .step-circle {
  background: #22c55e;
  border-color: #22c55e;
  color: #fff;
}
.step-item.active .step-circle {
  background: var(--accent-teal);
  border-color: var(--accent-teal);
  color: #fff;
  box-shadow: 0 0 12px rgba(13,148,136,0.3);
}
.step-label {
  font-size: .75rem;
  color: var(--text-secondary);
  font-weight: 500;
}
.step-item.active .step-label {
  color: var(--accent-teal);
  font-weight: 600;
}
.step-item.done .step-label {
  color: #22c55e;
  font-weight: 600;
}

/* ── Cards ── */
.fit-card {
  background: var(--bg-card);
  border: 1px solid var(--border-card);
  border-radius: 12px;
  box-shadow: var(--shadow-card);
  margin-bottom: 1.5rem;
  overflow: hidden;
}

/* ── Section Headers ── */
.fit-card-hd {
  background: var(--bg-section-header);
  border-left: 4px solid var(--accent-teal);
  border-bottom: 1px solid var(--border-card);
  padding: 12px 18px;
}
.fit-card-hd .fit-heading {
  color: var(--accent-teal) !important;
  font-size: 13px;
  font-weight: 800;
  letter-spacing: 2px;
  text-transform: uppercase;
  margin: 0;
}

/* ── Score Ring ── */
.score-ring-wrap {
  position: relative;
  width: 160px;
  height: 160px;
  margin: 0 auto;
}
.score-ring-wrap svg {
  transform: rotate(-90deg);
}
.score-center {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
}
.score-val {
  font-weight: 800;
  font-size: 2.2rem;
  color: var(--accent-teal);
  line-height: 1;
}
.score-lbl {
  font-size: 11px;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* ── Stat Pill ── */
.stat-pill {
  background: #f8fafc;
  border: 1px solid var(--border-card);
  border-radius: 10px;
  padding: 1.2rem 1rem;
  text-align: center;
}
.stat-num {
  font-weight: 800;
  font-size: 1.8rem;
  line-height: 1;
}
.stat-lbl {
  font-size: 11px;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-top: 6px;
}

/* ── Streak ── */
.streak-fire {
  animation: flicker 1.5s ease-in-out infinite alternate;
}
@keyframes flicker {
  from { text-shadow: 0 0 8px #f97316, 0 0 16px #f97316; color: #f97316; }
  to { text-shadow: 0 0 16px #ef4444, 0 0 24px #ef4444; color: #ef4444; }
}

/* ── Feedback ── */
.feedback-item {
  background: #f8fafc;
  border: 1px solid var(--border-card);
  border-radius: 10px;
  padding: 1.2rem;
  margin-bottom: 1rem;
}
.feedback-item:last-child {
  margin-bottom: 0;
}
.feedback-meta {
  font-size: 12px;
  color: var(--text-secondary);
  margin-bottom: .5rem;
}
.feedback-text {
  font-size: 14px;
  color: var(--text-primary);
}

/* ── Send Progress Button ── */
.btn-send {
  background: #d97706;
  color: #fff !important;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  padding: 12px 20px;
  transition: all .2s;
  box-shadow: var(--shadow-sm);
  cursor: pointer;
}
.btn-send:hover {
  background: #b45309;
  transform: translateY(-1px);
}
.btn-send:disabled {
  opacity: .5;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
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

.formula-box {
  background: var(--bg-trainer-box);
  border: 1px solid var(--border-trainer);
  border-radius: 12px;
  padding: 1rem;
  font-family: monospace;
  font-size: 12px;
  color: var(--text-primary);
}
</style>

<div class="p-1">
  <!-- Step Bar -->
  <div class="step-bar">
    <div class="step-item done"><div class="step-circle"><i class="bi bi-check-lg"></i></div><div class="step-label">Request</div></div>
    <div class="step-item done"><div class="step-circle"><i class="bi bi-check-lg"></i></div><div class="step-label">Profile</div></div>
    <div class="step-item done"><div class="step-circle"><i class="bi bi-check-lg"></i></div><div class="step-label">Your Plan</div></div>
    <div class="step-item active"><div class="step-circle">4</div><div class="step-label">Progress</div></div>
  </div>

  <div class="d-flex justify-content-between align-items-start mb-4">
    <div>
      <h1 class="fw-extrabold mb-1" style="color: var(--text-primary); font-size: 26px; font-weight: 800;">
        <i class="bi bi-graph-up-arrow me-2" style="color: var(--accent-teal)"></i>Progress Tracking
      </h1>
      <p style="color: var(--text-secondary); font-size: 14px;">Consistency Score · Streaks · Weekly Frequency</p>
    </div>
    <a href="index.php?r=fitness/plan&request_id=<?= $requestId ?>" class="btn-back btn text-decoration-none">
      <i class="bi bi-arrow-left me-1"></i>Back to Plan
    </a>
  </div>

  <?php if (isset($_SESSION['success'])): ?>
  <div class="alert alert-success mb-4" style="border-radius: 12px;">
    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
  </div>
  <?php unset($_SESSION['success']); endif; ?>

  <!-- Score + Stats -->
  <div class="row g-4 mb-4">
    <!-- Consistency Score Ring -->
    <div class="col-md-4">
      <div class="fit-card p-4 text-center h-100">
        <p class="mb-3" style="font-size:11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--text-secondary);">CONSISTENCY SCORE</p>
        <div class="score-ring-wrap mb-3">
          <?php
          $maxScore = max($score, 1);
          $pct = min(100, $score / max(1, $totalDays * 12) * 100);
          $r = 68; $circ = 2 * M_PI * $r;
          $dash = $circ * ($pct / 100);
          ?>
          <svg width="160" height="160" viewBox="0 0 160 160">
            <circle cx="80" cy="80" r="<?= $r ?>" fill="none" stroke="rgba(0,0,0,.06)" stroke-width="10"/>
            <circle cx="80" cy="80" r="<?= $r ?>" fill="none" stroke="url(#scoreGrad)" stroke-width="10"
                    stroke-dasharray="<?= round($dash,2) ?> <?= round($circ - $dash,2) ?>"
                    stroke-linecap="round"/>
            <defs>
              <linearGradient id="scoreGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="#0d9488"/>
                <stop offset="100%" stop-color="#14b8a6"/>
              </linearGradient>
            </defs>
          </svg>
          <div class="score-center">
            <div class="score-val" id="liveScore"><?= number_format($score, 1) ?></div>
            <div class="score-lbl">pts</div>
          </div>
        </div>
        <div class="formula-box text-start">
          <span style="color: var(--accent-teal); font-weight: 600;">Sc</span> = Σ(<span style="color:#d97706; font-weight: 600;">B</span> + (<span style="color:#3b82f6; font-weight: 600;">si</span> · <span style="color:#0ea5e9; font-weight: 600;">w</span>))<br>
          <span style="color: var(--text-secondary);">B=10, w=2, si=streak on day i</span>
        </div>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="col-md-8">
      <div class="row g-3 h-100">
        <div class="col-6 col-lg-3">
          <div class="stat-pill h-100">
            <div class="stat-num" style="color:<?= $streak > 0 ? '#f97316' : 'var(--text-secondary)' ?>">
              <?php if ($streak > 0): ?><i class="bi bi-fire streak-fire"></i><?php endif; ?>
              <?= $streak ?>
            </div>
            <div class="stat-lbl">Current Streak</div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-pill h-100">
            <div class="stat-num" style="color:#16a34a"><?= $totalDays ?></div>
            <div class="stat-lbl">Total Days</div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-pill h-100">
            <div class="stat-num" style="color:#3b82f6"><?= $workouts ?></div>
            <div class="stat-lbl">Workouts</div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="stat-pill h-100">
            <div class="stat-num" style="color:#d97706"><?= $nutrition ?></div>
            <div class="stat-lbl">Meals Logged</div>
          </div>
        </div>
        <div class="col-12">
          <div class="stat-pill" style="border-color: var(--border-trainer); background: var(--bg-trainer-box);">
            <div class="d-flex align-items-center justify-content-between">
              <span style="color: var(--text-secondary); font-size:13px; font-weight: 500;">Avg workout days/week:</span>
              <span class="fw-bold" style="font-size:1.4rem; color: var(--accent-teal)"><?= number_format($freq, 1) ?></span>
            </div>
          </div>
        </div>
        <!-- Send Progress -->
        <div class="col-12">
          <form method="POST" action="index.php?r=fitness/progress&request_id=<?= $requestId ?>">
            <input type="hidden" name="send_progress" value="1">
            <button type="submit" class="btn-send w-100" <?= $totalDays === 0 ? 'disabled' : '' ?>>
              <i class="bi bi-send-check me-2"></i>Send Progress to Trainer
            </button>
            <?php if ($totalDays === 0): ?>
            <p class="text-center mt-2" style="color: var(--text-secondary); font-size: 12px;">Log at least one workout or meal to send progress.</p>
            <?php endif; ?>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Weekly Frequency Chart -->
  <div class="fit-card">
    <div class="fit-card-hd">
      <h5 class="fit-heading">
        <i class="bi bi-bar-chart me-2"></i>WORKOUT FREQUENCY — LAST 8 WEEKS
      </h5>
    </div>
    <div class="p-4" style="height:260px;">
      <?php if (empty($weeklyFrequency)): ?>
      <div class="text-center" style="padding-top:4rem; color: var(--text-secondary);">
        <i class="bi bi-bar-chart" style="font-size:2rem; opacity:.3;"></i>
        <p class="mt-2" style="font-size:14px;">No workout data yet. Start logging!</p>
      </div>
      <?php else: ?>
      <canvas id="freqChart"></canvas>
      <?php endif; ?>
    </div>
  </div>

  <!-- Trainer Feedback -->
  <?php if (!empty($feedbacks)): ?>
  <div class="fit-card">
    <div class="fit-card-hd">
      <h5 class="fit-heading">
        <i class="bi bi-chat-left-quote me-2"></i>TRAINER FEEDBACK
      </h5>
    </div>
    <div class="p-4">
      <div class="d-flex flex-column gap-3">
        <?php foreach ($feedbacks as $fb): ?>
        <div class="feedback-item mb-3 p-3 rounded" style="background:#f8fafc; border:1px solid #e2e8f0;">
          <div class="feedback-meta d-flex justify-content-between align-items-center mb-2">
            <span class="fw-semibold text-dark"><i class="bi bi-person-badge me-1 text-success"></i><?= htmlspecialchars($fb['trainer_name'] ?? 'Trainer') ?></span>
            <div class="d-flex align-items-center gap-2">
              <?php if (!empty($fb['feedback_status'])): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><?= htmlspecialchars(str_replace('_', ' ', strtoupper((string)$fb['feedback_status']))) ?></span>
              <?php endif; ?>
              <small class="text-muted"><?= date('M j, Y', strtotime($fb['created_at'])) ?></small>
            </div>
          </div>
          <?php if (!empty($fb['feedback_subject'])): ?>
            <h6 class="fw-bold text-dark mb-2"><?= htmlspecialchars($fb['feedback_subject']) ?></h6>
          <?php endif; ?>
          <div class="feedback-text text-secondary mb-2"><?= nl2br(htmlspecialchars($fb['feedback_text'])) ?></div>
          <?php if (!empty($fb['areas_of_improvement'])): ?>
          <div class="mt-2 p-2 rounded" style="background:#fffbeb; border:1px solid #fef3c7;">
            <small style="color:#d97706; font-weight:600; text-transform: uppercase; font-size: 11px;">Areas to Improve:</small>
            <p class="mb-0 text-dark" style="font-size:13px;"><?= nl2br(htmlspecialchars($fb['areas_of_improvement'])) ?></p>
          </div>
          <?php endif; ?>
          <?php if (!empty($fb['next_steps'])): ?>
          <div class="mt-2 p-2 rounded" style="background:#eff6ff; border:1px solid #bfdbfe;">
            <small style="color:#3b82f6; font-weight:600; text-transform: uppercase; font-size: 11px;">Suggested Next Steps / Program Tweaks:</small>
            <p class="mb-0 text-dark" style="font-size:13px;"><?= nl2br(htmlspecialchars($fb['next_steps'])) ?></p>
          </div>
          <?php endif; ?>
          <?php if (!empty($fb['encouragement'])): ?>
          <div class="mt-2 p-2 rounded" style="background:#f0fdf4; border:1px solid #bbf7d0;">
            <small style="color:#16a34a; font-weight:600; text-transform: uppercase; font-size: 11px;">Coach Encouragement:</small>
            <p class="mb-0 text-dark" style="font-size:13px;"><?= nl2br(htmlspecialchars($fb['encouragement'])) ?></p>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php if (!empty($weeklyFrequency)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('freqChart').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($chartLabels) ?>,
    datasets: [{
      label: 'Workout Days',
      data: <?= json_encode($chartValues) ?>,
      backgroundColor: 'rgba(13,148,136,0.25)',
      borderColor: '#0d9488',
      borderWidth: 2,
      borderRadius: 8,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color:'rgba(0,0,0,0.05)' }, ticks: { color:'#64748b', font:{size:11} } },
      y: { grid: { color:'rgba(0,0,0,0.05)' }, ticks: { color:'#64748b', font:{size:11}, stepSize:1, beginAtZero:true } }
    }
  }
});
</script>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
