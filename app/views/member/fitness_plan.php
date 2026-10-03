<?php
declare(strict_types=1);
$pageTitle = 'Fitness & Nutrition Plan';
require __DIR__ . '/../partials/header.php';

$displayName = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
if ($displayName === '') $displayName = $user['fullname'] ?? 'Member';
$requestId = $request['id'];

// Parse workout plan from JSON
$workoutPlan = [];
$workoutPlanJSON = $plan['fitness_plan_monday'] ?? '';
if (!empty($workoutPlanJSON)) {
    $decoded = json_decode($workoutPlanJSON, true);
    if (is_array($decoded)) {
        $workoutPlan = $decoded;
    }
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

:root {
  --bg-page: #f0f2f0;
  --bg-card: #ffffff;
  --bg-section-header: #e8f5f0;
  --border-card: #e2e8f0;
  --accent-teal: #0d9488;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --shadow-card: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.05);
}

body {
  background: var(--bg-page) !important;
  font-family: 'Inter', system-ui, sans-serif !important;
  color: var(--text-primary) !important;
}

.fit-card {
  background: var(--bg-card);
  border: 1px solid var(--border-card);
  border-radius: 12px;
  box-shadow: var(--shadow-card);
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.fit-card-hd {
  background: var(--bg-section-header);
  border-left: 4px solid var(--accent-teal);
  border-bottom: 1px solid var(--border-card);
  padding: 12px 18px;
}

.fit-heading {
  color: var(--accent-teal) !important;
  font-size: 13px;
  font-weight: 800;
  letter-spacing: 2px;
  text-transform: uppercase;
  margin: 0;
}

.day-card {
  background: #f8fafc;
  border: 1px solid var(--border-card);
  border-radius: 10px;
  padding: 1rem;
  height: 100%;
}

.day-label {
  color: var(--accent-teal);
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  margin-bottom: 10px;
}

.exercise-item {
  background: white;
  border: 1px solid var(--border-card);
  border-radius: 8px;
  padding: 10px;
  margin-bottom: 8px;
}

.exercise-name {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 14px;
}

.exercise-meta {
  font-size: 11px;
  color: var(--text-secondary);
  margin-top: 4px;
}

.custom-badge {
  background: #22c55e;
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: bold;
  margin-left: 6px;
}

.macro-box {
  background: #f8fafc;
  border: 1px solid var(--border-card);
  border-radius: 10px;
  padding: 1.2rem 0.5rem;
  text-align: center;
}

.macro-val {
  font-size: 2rem;
  font-weight: 800;
  line-height: 1.2;
}

.macro-lbl {
  font-size: 11px;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-top: 4px;
}

.btn-fit {
  background: #166534;
  color: #fff !important;
  border: none;
  border-radius: 8px;
  padding: 12px 20px;
  font-weight: 600;
  font-size: 14px;
  transition: all .2s;
}

.btn-fit:hover {
  background: #15803d;
  transform: translateY(-1px);
}

.fit-input {
  background: #ffffff !important;
  border: 1px solid #cbd5e1 !important;
  color: var(--text-primary) !important;
  border-radius: 8px !important;
  padding: 10px 14px !important;
  font-size: 14px !important;
}

.fit-input:focus {
  border-color: var(--accent-teal) !important;
  box-shadow: 0 0 0 3px rgba(13,148,136,0.12) !important;
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

.tab-nav {
  display: flex;
  gap: 8px;
  margin-bottom: 1.5rem;
  background: #f1f5f9;
  border-radius: 12px;
  padding: 6px;
}

.tab-btn {
  flex: 1;
  padding: 10px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: var(--text-secondary);
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all .2s;
}

.tab-btn.active {
  background: #ffffff;
  color: var(--accent-teal);
  box-shadow: 0 1px 2px rgba(0,0,0,0.06);
}

.tab-pane {
  display: none;
}

.tab-pane.active {
  display: block;
}

.log-table {
  width: 100%;
  color: var(--text-primary);
}

.log-table thead th {
  color: var(--text-secondary);
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  border-bottom: 2px solid var(--border-card);
  padding: 12px 10px;
}

.log-table td {
  border-bottom: 1px solid var(--border-card);
  vertical-align: middle;
  font-size: 14px;
  padding: 12px 10px;
}

.log-table tbody tr:hover {
  background: #f8fafc;
}

.food-suggestions-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid var(--border-card);
  border-radius: 8px;
  max-height: 300px;
  overflow-y: auto;
  z-index: 1000;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  display: none;
  margin-top: 4px;
}

.food-suggestion-item {
  padding: 10px 12px;
  cursor: pointer;
  border-bottom: 1px solid #f1f3f5;
  transition: background 0.2s;
}

.food-suggestion-item:hover {
  background: #f8f9fa;
}

.food-suggestion-item:last-child {
  border-bottom: none;
}

.food-suggestion-name {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 13px;
}

.food-suggestion-brand {
  font-size: 11px;
  color: var(--text-secondary);
  margin-top: 2px;
}

.food-suggestion-nutrients {
  font-size: 10px;
  color: var(--accent-teal);
  margin-top: 4px;
}

/* ═══════════════════════════════════════════════════════
   WORKOUT SESSION MODAL STYLES
   ═══════════════════════════════════════════════════════ */

/* Session Modal Base */
.session-container {
  max-width: 900px;
  margin: 0 auto;
  padding: 20px;
}

/* Top Bar */
.session-topbar {
  background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
  color: white;
  padding: 16px 24px;
  border-radius: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.session-progress-info {
  flex: 1;
  min-width: 200px;
}

.session-counter {
  display: block;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 8px;
  opacity: 0.9;
}

.session-progress-bar {
  background: rgba(255,255,255,0.2);
  height: 8px;
  border-radius: 10px;
  overflow: hidden;
}

.session-progress-fill {
  background: white;
  height: 100%;
  width: 0%;
  transition: width 0.3s ease;
  border-radius: 10px;
}

.session-elapsed {
  font-size: 20px;
  font-weight: 700;
  white-space: nowrap;
}

.session-end-btn {
  background: rgba(255,255,255,0.2);
  color: white;
  border: 2px solid white;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.session-end-btn:hover {
  background: white;
  color: #0d9488;
}

/* Exercise Area */
.session-exercise-area {
  background: white;
  border-radius: 16px;
  padding: 32px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.1);
}

.session-ex-header {
  text-align: center;
  margin-bottom: 24px;
}

.session-ex-name {
  font-size: 28px;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 12px;
}

.session-ex-meta {
  display: flex;
  justify-content: center;
  gap: 16px;
  flex-wrap: wrap;
}

.session-meta-badge {
  background: #f1f5f9;
  color: #64748b;
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

/* GIF Container */
.session-gif-container {
  text-align: center;
  margin-bottom: 32px;
}

.session-gif {
  max-width: 100%;
  height: auto;
  max-height: 300px;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0,0,0,0.1);
}

.session-no-gif {
  background: #f8fafc;
  color: #cbd5e1;
  padding: 60px 20px;
  border-radius: 12px;
  border: 2px dashed #e2e8f0;
}

/* Set Tracker */
.set-tracker-container {
  margin-bottom: 32px;
}

.set-tracker-title {
  font-size: 16px;
  font-weight: 700;
  color: #0d9488;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
}

.set-tracker-table {
  background: #f8fafc;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
}

.set-tracker-header {
  display: grid;
  grid-template-columns: 60px 100px 100px 100px 60px;
  background: #0d9488;
  color: white;
  padding: 12px 16px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1px;
  text-align: center;
}

.set-rows {
  padding: 8px 0;
}

.set-row {
  display: grid;
  grid-template-columns: 60px 100px 100px 100px 60px;
  padding: 12px 16px;
  text-align: center;
  align-items: center;
  border-bottom: 1px solid #e2e8f0;
  transition: background 0.2s;
}

.set-row:last-child {
  border-bottom: none;
}

.set-row.set-done {
  background: #f0fdf9;
}

.set-num {
  font-weight: 700;
  color: #0d9488;
}

.set-target {
  color: #64748b;
}

.set-actual, .set-weight {
  font-weight: 600;
  color: #1e293b;
}

.set-check {
  font-size: 18px;
  color: #cbd5e1;
}

/* Current Set Action */
.current-set-action {
  background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
  border-radius: 16px;
  padding: 32px;
  text-align: center;
  color: white;
}

.set-label-display {
  font-size: 16px;
  font-weight: 700;
  letter-spacing: 2px;
  margin-bottom: 24px;
  opacity: 0.9;
}

/* Rep Counter */
.rep-counter-area {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 24px;
  margin-bottom: 24px;
}

.rep-adj-btn {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: rgba(255,255,255,0.2);
  border: 2px solid white;
  color: white;
  font-size: 24px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.rep-adj-btn:hover {
  background: white;
  color: #0d9488;
  transform: scale(1.1);
}

.rep-adj-btn:active {
  transform: scale(0.95);
}

.rep-display {
  background: white;
  color: #0d9488;
  border-radius: 20px;
  padding: 16px 48px;
  min-width: 160px;
}

.rep-count-num {
  display: block;
  font-size: 48px;
  font-weight: 800;
  line-height: 1;
  margin-bottom: 4px;
}

.rep-label {
  font-size: 14px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  opacity: 0.7;
}

/* Weight Input */
.weight-input-area {
  margin-bottom: 24px;
}

.weight-label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 8px;
  opacity: 0.9;
}

.weight-input {
  background: white;
  color: #1e293b;
  border: 2px solid white;
  border-radius: 10px;
  padding: 12px 20px;
  font-size: 18px;
  font-weight: 700;
  text-align: center;
  max-width: 200px;
  width: 100%;
}

.weight-input:focus {
  outline: none;
  box-shadow: 0 0 0 4px rgba(255,255,255,0.3);
}

/* Complete Set Button */
.complete-set-btn {
  background: white;
  color: #0d9488;
  border: none;
  border-radius: 12px;
  padding: 16px 48px;
  font-size: 18px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.complete-set-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.complete-set-btn:active {
  transform: translateY(0);
}

/* Rest Timer Overlay */
.rest-timer-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
}

.rest-timer-box {
  background: white;
  border-radius: 24px;
  padding: 48px;
  text-align: center;
  max-width: 400px;
  width: 90%;
}

.rest-title {
  font-size: 24px;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 32px;
}

.timer-circle-container {
  position: relative;
  width: 200px;
  height: 200px;
  margin: 0 auto 32px;
}

.timer-svg {
  width: 100%;
  height: 100%;
  transform: rotate(-90deg);
}

.timer-bg-circle {
  fill: none;
  stroke: #e2e8f0;
  stroke-width: 8;
}

.timer-progress-circle {
  fill: none;
  stroke: #0d9488;
  stroke-width: 8;
  stroke-linecap: round;
  transition: stroke-dashoffset 1s linear;
}

.timer-number {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 56px;
  font-weight: 800;
  color: #0d9488;
}

.rest-next {
  font-size: 16px;
  color: #64748b;
  margin-bottom: 24px;
}

.rest-next strong {
  color: #1e293b;
}

.skip-rest-btn {
  background: #0d9488;
  color: white;
  border: none;
  border-radius: 12px;
  padding: 14px 32px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.skip-rest-btn:hover {
  background: #0f766e;
  transform: translateY(-2px);
}

/* Session Complete */
.session-complete {
  background: white;
  border-radius: 24px;
  padding: 48px 32px;
  text-align: center;
  max-width: 700px;
  margin: 0 auto;
}

.complete-animation {
  font-size: 80px;
  margin-bottom: 16px;
  animation: celebrate 0.6s ease;
}

@keyframes celebrate {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.2); }
}

.complete-title {
  font-size: 32px;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 8px;
}

.complete-subtitle {
  font-size: 16px;
  color: #64748b;
  margin-bottom: 32px;
}

.complete-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.complete-stat {
  background: #f8fafc;
  border-radius: 12px;
  padding: 24px 16px;
  border: 1px solid #e2e8f0;
}

.stat-icon {
  font-size: 32px;
  margin-bottom: 8px;
}

.stat-value {
  display: block;
  font-size: 28px;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 4px;
}

.stat-label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* Calorie Breakdown */
.calorie-breakdown {
  background: #f8fafc;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 24px;
  border: 1px solid #e2e8f0;
}

.breakdown-title {
  font-size: 16px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 16px;
}

.breakdown-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.breakdown-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: white;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.breakdown-name {
  font-weight: 600;
  color: #1e293b;
  flex: 1;
}

.breakdown-sets {
  font-size: 13px;
  color: #64748b;
  margin: 0 16px;
}

.breakdown-cals {
  font-weight: 700;
  color: #ef4444;
}

/* Session Notes */
.session-notes-input {
  width: 100%;
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 16px;
  font-size: 14px;
  color: #1e293b;
  min-height: 100px;
  margin-bottom: 24px;
  resize: vertical;
}

.session-notes-input:focus {
  outline: none;
  border-color: #0d9488;
}

/* Complete Actions */
.complete-actions {
  display: flex;
  gap: 12px;
  justify-content: center;
}

.save-session-btn {
  background: #0d9488;
  color: white;
  border: none;
  border-radius: 12px;
  padding: 16px 48px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  flex: 1;
  max-width: 300px;
}

.save-session-btn:hover {
  background: #0f766e;
  transform: translateY(-2px);
}

.save-session-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.close-session-btn {
  background: #e2e8f0;
  color: #64748b;
  border: none;
  border-radius: 12px;
  padding: 16px 32px;
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.close-session-btn:hover {
  background: #cbd5e1;
}

/* Mobile Responsive */
@media (max-width: 768px) {
  .session-topbar {
    flex-direction: column;
    text-align: center;
  }
  
  .session-ex-name {
    font-size: 22px;
  }
  
  .set-tracker-header,
  .set-row {
    grid-template-columns: 50px 80px 80px 80px 50px;
    font-size: 11px;
  }
  
  .rep-count-num {
    font-size: 36px;
  }
  
  .complete-stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .complete-actions {
    flex-direction: column;
  }
  
  .save-session-btn,
  .close-session-btn {
    max-width: 100%;
  }
}
</style>

<div class="container-fluid p-4">
  <div class="d-flex justify-content-between align-items-start mb-4">
    <div>
      <h1 class="fw-bold mb-1" style="color: var(--text-primary); font-size: 26px;">
        <i class="bi bi-file-earmark-check me-2" style="color: var(--accent-teal)"></i>Your Fitness & Nutrition Plan
        <?php if ($plan['is_ai_plan'] ?? false): ?>
        <span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-left: 8px;">
          ✨ AI POWERED
        </span>
        <?php endif; ?>
      </h1>
      <p style="color: var(--text-secondary); font-size: 14px;">
        Created by <strong style="color: var(--text-primary)"><?= htmlspecialchars($plan['trainer_name'] ?? 'Your Trainer') ?></strong>
        <?php if ($plan['fitness_level']): ?>
          · <span style="color: var(--accent-teal); text-transform: capitalize; font-weight: 600;"><?= htmlspecialchars($plan['fitness_level']) ?> Level</span>
        <?php endif; ?>
        <?php if ($plan['is_ai_plan'] ?? false): ?>
          · <span style="color: #667eea; font-weight: 600;">Generated with <?= htmlspecialchars($plan['ai_model'] ?? 'AI') ?></span>
        <?php endif; ?>
      </p>
    </div>
    <a href="index.php?r=fitness/status" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Back
    </a>
  </div>

  <?php
  // Get today's exercises
  $today = strtolower(date('l')); // 'monday', 'tuesday', etc.
  $todayKey = substr($today, 0, 3); // 'mon', 'tue', etc.
  $todayExercises = $workoutPlan[$todayKey] ?? [];

  // Get workout streak (check if table exists first)
  $streakCount = 0;
  try {
      $pdo = \App\Core\Database::pdo();
      $stmtStreak = $pdo->prepare(
          'SELECT COUNT(DISTINCT session_date) as streak
           FROM workout_sessions
           WHERE member_id = ?
           AND status = "completed"
           AND session_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)'
      );
      $stmtStreak->execute([$member['id']]);
      $streakData = $stmtStreak->fetch(\PDO::FETCH_ASSOC);
      $streakCount = (int)($streakData['streak'] ?? 0);
  } catch (\PDOException $e) {
      // Table doesn't exist yet - that's okay, streak will be 0
      $streakCount = 0;
  }

  // Estimate session duration
  $estMinutes = 0;
  foreach ($todayExercises as $ex) {
      $sets = (int)($ex['sets'] ?? 3);
      $estMinutes += ($sets * 2) + ($sets - 1);
  }
  ?>

  <!-- TODAY'S WORKOUT CARD -->
  <div class="fit-card mb-4" style="background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); border: none;">
      <div class="p-4">
          <div class="d-flex justify-content-between align-items-start mb-3">
              <div>
                  <div class="mb-2">
                      <span style="background: rgba(255,255,255,0.2); color: white; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: 1px;">
                          📅 TODAY - <?= strtoupper($today) ?>
                      </span>
                  </div>
                  <h3 class="text-white mb-2" style="font-size: 22px; font-weight: 800;">
                      <?= date('F j, Y') ?>
                  </h3>
                  <div class="d-flex gap-3 flex-wrap">
                      <span class="text-white" style="font-size: 13px;">
                          <i class="bi bi-lightning-fill me-1"></i>
                          <strong><?= count($todayExercises) ?></strong> exercises
                      </span>
                      <span class="text-white" style="font-size: 13px;">
                          <i class="bi bi-clock-fill me-1"></i>
                          <strong>~<?= $estMinutes ?></strong> min
                      </span>
                      <?php if ($streakCount > 0): ?>
                      <span class="text-white" style="font-size: 13px;">
                          <i class="bi bi-fire me-1"></i>
                          <strong><?= $streakCount ?></strong>-day streak
                      </span>
                      <?php endif; ?>
                  </div>
              </div>
          </div>
          
          <?php if (empty($todayExercises)): ?>
          <div class="text-center py-4">
              <div style="font-size: 3rem; opacity: 0.5;">😴</div>
              <p class="text-white mb-0" style="font-size: 15px; font-weight: 600;">Rest Day - No exercises planned</p>
          </div>
          <?php else: ?>
          <div class="mb-3">
              <?php foreach ($todayExercises as $ex): ?>
              <div class="d-flex align-items-center py-2" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                  <span class="text-white me-2" style="font-size: 18px; opacity: 0.7;">•</span>
                  <span class="text-white flex-grow-1" style="font-size: 14px; font-weight: 600;">
                      <?= htmlspecialchars($ex['name'] ?? 'Exercise') ?>
                  </span>
                  <span class="text-white" style="font-size: 12px; opacity: 0.8;">
                      <?= htmlspecialchars($ex['category'] ?? '') ?> · 
                      <?= (int)($ex['sets'] ?? 3) ?>×<?= (int)($ex['reps'] ?? 10) ?>
                  </span>
              </div>
              <?php endforeach; ?>
          </div>
          
          <button type="button"
                  id="start-workout-btn"
                  onclick="checkAndStartWorkout()"
                  class="btn w-100"
                  style="background: white; color: #0d9488; font-weight: 700; font-size: 16px; padding: 14px; border-radius: 10px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
              <i class="bi bi-play-circle-fill me-2" style="font-size: 20px;"></i>
              START WORKOUT
          </button>
          <?php endif; ?>
      </div>
  </div>

  <!-- Weekly Workout Schedule -->
  <div class="fit-card">
    <div class="fit-card-hd">
      <h5 class="fit-heading">
        <i class="bi bi-calendar-week me-2"></i>WEEKLY WORKOUT SCHEDULE
      </h5>
    </div>
    <div class="p-4">
      <div class="row g-3">
        <?php
        $days = ['mon' => 'MONDAY', 'tue' => 'TUESDAY', 'wed' => 'WEDNESDAY', 'thu' => 'THURSDAY', 'fri' => 'FRIDAY', 'sat' => 'SATURDAY', 'sun' => 'SUNDAY'];
        foreach ($days as $dayKey => $dayLabel):
          $exercises = $workoutPlan[$dayKey] ?? [];
        ?>
        <div class="col-lg-3 col-md-4 col-sm-6">
          <div class="day-card">
            <div class="day-label"><?= $dayLabel ?></div>
            <?php if (empty($exercises)): ?>
              <div style="color: var(--text-secondary); font-size: 13px; font-style: italic;">Rest day</div>
            <?php else: ?>
              <?php foreach ($exercises as $exercise): ?>
                <div class="exercise-item">
                  <div class="exercise-name">
                    <?= htmlspecialchars($exercise['name'] ?? 'Exercise') ?>
                    <?php if ($exercise['isCustom'] ?? false): ?>
                      <span class="custom-badge">CUSTOM</span>
                    <?php endif; ?>
                  </div>
                  <div class="exercise-meta">
                    <i class="bi bi-tag me-1"></i><?= htmlspecialchars($exercise['category'] ?? 'General') ?>
                  </div>
                  <div class="exercise-meta">
                    <i class="bi bi-tools me-1"></i><?= htmlspecialchars($exercise['equipment'] ?? 'No equipment') ?>
                  </div>
                  <?php if (!empty($exercise['sets']) || !empty($exercise['reps'])): ?>
                    <div class="exercise-meta mt-1">
                      <i class="bi bi-trophy me-1"></i>
                      <?= htmlspecialchars((string)($exercise['sets'] ?? '3')) ?> sets × 
                      <?= htmlspecialchars((string)($exercise['reps'] ?? '10')) ?> reps
                    </div>
                  <?php endif; ?>
                  <button type="button" class="btn btn-outline-success btn-sm w-100 mt-2 py-1 px-2 fw-semibold" style="font-size: 11px; border-radius: 6px;"
                          onclick="openExerciseGuide('<?= htmlspecialchars(addslashes($exercise['name'] ?? 'Exercise'), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($exercise['equipment'] ?? 'Equipment'), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($exercise['category'] ?? 'Category'), ENT_QUOTES) ?>')">
                    <i class="bi bi-play-circle-fill me-1"></i>Form Guide &amp; Video 🎥
                  </button>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      
      <?php if (!empty($plan['fitness_plan_notes'])): ?>
      <div class="mt-4 p-3" style="background: #f0fdf9; border-radius:10px; border:1px solid #99f6e4;">
        <p class="mb-0" style="font-size: 13px; color: var(--text-primary);">
          <strong style="color: var(--accent-teal);">Trainer Notes:</strong> 
          <?= nl2br(htmlspecialchars($plan['fitness_plan_notes'])) ?>
        </p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Nutrition Plan -->
  <div class="fit-card">
    <div class="fit-card-hd" style="background: #fffbeb; border-left-color: #f59e0b;">
      <h5 class="fit-heading" style="color: #d97706 !important;">
        <i class="bi bi-egg-fried me-2"></i>NUTRITION TARGETS
      </h5>
    </div>
    <div class="p-4">
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="macro-box">
            <div class="macro-val" style="color: #ef4444"><?= $plan['target_calories'] ?? '—' ?></div>
            <div class="macro-lbl">DAILY CALORIES</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="macro-box">
            <div class="macro-val" style="color: #f59e0b"><?= $plan['target_protein_g'] ?? '—' ?><small style="font-size:1rem;">g</small></div>
            <div class="macro-lbl">PROTEIN</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="macro-box">
            <div class="macro-val" style="color: #3b82f6"><?= $plan['target_carbs_g'] ?? '—' ?><small style="font-size:1rem;">g</small></div>
            <div class="macro-lbl">CARBS</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="macro-box">
            <div class="macro-val" style="color: #0d9488"><?= $plan['target_fats_g'] ?? '—' ?><small style="font-size:1rem;">g</small></div>
            <div class="macro-lbl">FATS</div>
          </div>
        </div>
      </div>
      
      <?php if (!empty($plan['meal_suggestions'])): ?>
      <div class="p-3" style="background: #fffbeb; border-radius:10px; border:1px solid #fef3c7;">
        <p class="mb-1" style="color: #d97706; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:1px;">
          Meal Suggestions
        </p>
        <p class="mb-0" style="font-size:13px; color: var(--text-primary); white-space: pre-wrap;"><?= htmlspecialchars($plan['meal_suggestions']) ?></p>
      </div>
      <?php endif; ?>
      
      <?php if (!empty($plan['nutrition_notes'])): ?>
      <div class="mt-3 p-3" style="background: #f0fdf9; border-radius:10px; border:1px solid #99f6e4;">
        <p class="mb-1" style="color: var(--accent-teal); font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:1px;">
          Nutrition Notes
        </p>
        <p class="mb-0" style="font-size:13px; color: var(--text-primary); white-space: pre-wrap;"><?= htmlspecialchars($plan['nutrition_notes']) ?></p>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Logging Tabs -->
  <div class="fit-card">
    <div class="fit-card-hd" style="background: #f8fafc; border-left-color: #06b6d4;">
      <h5 class="fit-heading" style="color: var(--text-primary) !important;">
        <i class="bi bi-journal-check me-2" style="color: var(--accent-teal)"></i>DAILY LOGS
      </h5>
    </div>
    <div class="p-4">
      <div class="tab-nav">
        <button class="tab-btn active" onclick="switchTab('workout')">
          <i class="bi bi-lightning me-1"></i>Workout Log
        </button>
        <button class="tab-btn" onclick="switchTab('nutrition')">
          <i class="bi bi-egg-fried me-1"></i>Nutrition Log
        </button>
      </div>

      <!-- Workout Tab -->
      <div id="tab-workout" class="tab-pane active">
        <form id="workoutForm" class="mb-4">
          <input type="hidden" name="request_id" value="<?= $requestId ?>">
          <div class="row g-2 align-items-end">
            <div class="col-md-2">
              <label class="fit-label">Date</label>
              <input type="date" name="log_date" class="form-control fit-input" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-3">
              <label class="fit-label">Exercise</label>
              <input type="text" name="exercise_name" class="form-control fit-input" placeholder="e.g. Bench Press" required>
            </div>
            <div class="col-md-1">
              <label class="fit-label">Sets</label>
              <input type="number" name="sets" class="form-control fit-input" min="0" value="3">
            </div>
            <div class="col-md-1">
              <label class="fit-label">Reps</label>
              <input type="number" name="reps" class="form-control fit-input" min="0" value="10">
            </div>
            <div class="col-md-1">
              <label class="fit-label">Weight (kg)</label>
              <input type="number" name="weight_kg" class="form-control fit-input" min="0" step="0.5" value="0">
            </div>
            <div class="col-md-1">
              <label class="fit-label">Minutes</label>
              <input type="number" name="duration_minutes" class="form-control fit-input" min="0" value="30">
            </div>
            <div class="col-md-2">
              <label class="fit-label">Notes</label>
              <input type="text" name="notes" class="form-control fit-input" placeholder="Optional">
            </div>
            <div class="col-md-1">
              <button type="submit" class="btn-fit btn w-100">
                <i class="bi bi-plus-lg"></i>
              </button>
            </div>
          </div>
        </form>
        
        <div class="table-responsive">
          <table class="table log-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Exercise</th>
                <th>Sets</th>
                <th>Reps</th>
                <th>Weight</th>
                <th>Duration</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody id="workoutTableBody">
              <?php foreach ($workoutLogs as $log): ?>
              <tr>
                <td><?= htmlspecialchars($log['log_date']) ?></td>
                <td><strong><?= htmlspecialchars($log['exercise_name']) ?></strong></td>
                <td><?= $log['sets'] ?></td>
                <td><?= $log['reps'] ?></td>
                <td><?= $log['weight_kg'] ?> kg</td>
                <td><?= $log['duration_minutes'] ?> min</td>
                <td style="color: var(--text-secondary)"><?= htmlspecialchars($log['notes'] ?? '') ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($workoutLogs)): ?>
              <tr>
                <td colspan="7" class="text-center" style="color: var(--text-secondary); padding: 2rem;">
                  No workout logs yet. Add your first entry above!
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Nutrition Tab -->
      <div id="tab-nutrition" class="tab-pane">
        <form id="nutritionForm" class="mb-4">
          <input type="hidden" name="request_id" value="<?= $requestId ?>">
          <div class="row g-2 align-items-end">
            <div class="col-md-2">
              <label class="fit-label">Date</label>
              <input type="date" name="log_date" class="form-control fit-input" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-2">
              <label class="fit-label">Meal Type</label>
              <select name="meal_type" class="form-select fit-input" required>
                <option value="breakfast">Breakfast</option>
                <option value="lunch">Lunch</option>
                <option value="dinner">Dinner</option>
                <option value="snack">Snack</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="fit-label">Food Item</label>
              <div class="position-relative">
                <input type="text" name="food_item" id="foodItemInput" class="form-control fit-input" 
                       placeholder="Search food..." required autocomplete="off">
                <div class="food-suggestions-dropdown" id="foodSuggestionsDropdown"></div>
              </div>
            </div>
            <div class="col-md-1">
              <label class="fit-label">Amount</label>
              <input type="number" name="quantity" id="quantityInput" class="form-control fit-input" 
                     min="0" step="0.1" value="100" onchange="recalculateNutrients()">
            </div>
            <div class="col-md-1">
              <label class="fit-label">Unit</label>
              <select name="unit" id="unitInput" class="form-select fit-input" onchange="recalculateNutrients()">
                <option value="grams">grams</option>
                <option value="cups">cups</option>
                <option value="pieces">pieces</option>
                <option value="oz">oz</option>
              </select>
            </div>
            <div class="col-md-1">
              <label class="fit-label">Calories</label>
              <input type="number" name="calories" id="caloriesInput" class="form-control fit-input" 
                     min="0" value="0" readonly style="background: #f8f9fa;">
            </div>
            <div class="col-md-1">
              <label class="fit-label">Protein (g)</label>
              <input type="number" name="protein_g" id="proteinInput" class="form-control fit-input" 
                     min="0" step="0.1" value="0" readonly style="background: #f8f9fa;">
            </div>
            <div class="col-md-1">
              <label class="fit-label">Carbs (g)</label>
              <input type="number" name="carbs_g" id="carbsInput" class="form-control fit-input" 
                     min="0" step="0.1" value="0" readonly style="background: #f8f9fa;">
            </div>
            <div class="col-md-1">
              <label class="fit-label">Fats (g)</label>
              <input type="number" name="fats_g" id="fatsInput" class="form-control fit-input" 
                     min="0" step="0.1" value="0" readonly style="background: #f8f9fa;">
            </div>
            <div class="col-md-1">
              <button type="submit" class="btn-fit btn w-100">
                <i class="bi bi-plus-lg"></i>
              </button>
            </div>
          </div>
        </form>
        
        <div class="table-responsive">
          <table class="table log-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Meal</th>
                <th>Food</th>
                <th>Calories</th>
                <th>Protein</th>
                <th>Carbs</th>
                <th>Fats</th>
              </tr>
            </thead>
            <tbody id="nutritionTableBody">
              <?php foreach ($nutritionLogs as $log): ?>
              <tr>
                <td><?= htmlspecialchars($log['log_date']) ?></td>
                <td><span class="badge bg-info"><?= ucfirst($log['meal_type']) ?></span></td>
                <td><strong><?= htmlspecialchars($log['food_item']) ?></strong></td>
                <td><?= $log['calories'] ?></td>
                <td><?= $log['protein_g'] ?>g</td>
                <td><?= $log['carbs_g'] ?>g</td>
                <td><?= $log['fats_g'] ?>g</td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($nutritionLogs)): ?>
              <tr>
                <td colspan="7" class="text-center" style="color: var(--text-secondary); padding: 2rem;">
                  No nutrition logs yet. Add your first entry above!
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     WORKOUT SESSION MODAL
     ═══════════════════════════════════════════════════════ -->
<div id="workout-session-modal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; z-index:9999; background:rgba(0,0,0,0.95);">
<div class="session-container" style="height:100%; overflow-y:auto;">

    <!-- Top Bar -->
    <div class="session-topbar">
        <div class="session-progress-info">
            <span id="session-ex-counter" class="session-counter">Exercise 1 of 3</span>
            <div class="session-progress-bar">
                <div id="session-progress-fill" class="session-progress-fill"></div>
            </div>
        </div>
        <div class="session-elapsed">
            ⏱️ <span id="session-elapsed">00:00</span>
        </div>
        <button type="button" onclick="confirmEndSession()" class="session-end-btn">
            <i class="bi bi-x-lg"></i> End Session
        </button>
    </div>

    <!-- Main Exercise Area -->
    <div class="session-exercise-area" id="session-ex-area">
        
        <!-- Exercise Header -->
        <div class="session-ex-header">
            <h2 id="session-ex-name" class="session-ex-name">Exercise Name</h2>
            <div class="session-ex-meta">
                <span id="session-ex-target" class="session-meta-badge">
                    <i class="bi bi-bullseye"></i> <span id="target-text">Target Muscle</span>
                </span>
                <span id="session-ex-equipment" class="session-meta-badge">
                    <i class="bi bi-tools"></i> <span id="equipment-text">Equipment</span>
                </span>
            </div>
        </div>

        <!-- GIF Demo (if available) -->
        <div class="session-gif-container">
            <img id="session-gif" src="" alt="" class="session-gif" style="display:none;" />
            <div id="session-no-gif" class="session-no-gif">
                <i class="bi bi-person-arms-up" style="font-size: 4rem;"></i>
            </div>
        </div>

        <!-- SET TRACKER TABLE -->
        <div class="set-tracker-container">
            <h3 class="set-tracker-title">
                <i class="bi bi-list-check me-2"></i>Set Tracker
            </h3>
            <div class="set-tracker-table">
                <div class="set-tracker-header">
                    <span>SET</span>
                    <span>TARGET</span>
                    <span>ACTUAL</span>
                    <span>WEIGHT</span>
                    <span>✓</span>
                </div>
                <div id="set-rows" class="set-rows">
                    <!-- Generated by JS -->
                </div>
            </div>
        </div>

        <!-- Current Set Action Area -->
        <div class="current-set-action" id="current-set-action">
            <div class="set-label-display">
                SET <span id="current-set-num">1</span> OF <span id="total-sets-display">3</span>
            </div>

            <!-- Rep Counter -->
            <div class="rep-counter-area">
                <button type="button" onclick="adjustReps(-1)" class="rep-adj-btn rep-minus">
                    <i class="bi bi-dash-lg"></i>
                </button>
                <div class="rep-display">
                    <span id="rep-count" class="rep-count-num">10</span>
                    <small class="rep-label">reps</small>
                </div>
                <button type="button" onclick="adjustReps(1)" class="rep-adj-btn rep-plus">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>

            <!-- Weight Input -->
            <div class="weight-input-area">
                <label class="weight-label">
                    <i class="bi bi-hourglass-split me-2"></i>Weight Used (kg)
                </label>
                <input type="number" id="weight-input" min="0" step="0.5" value="0" class="weight-input" placeholder="0.0" />
            </div>

            <!-- Complete Set Button -->
            <button type="button" id="complete-set-btn" onclick="completeSet()" class="complete-set-btn">
                <i class="bi bi-check-circle-fill me-2"></i>Complete Set
            </button>
        </div>

    </div>

    <!-- REST TIMER OVERLAY -->
    <div class="rest-timer-overlay" id="rest-timer-overlay" style="display:none;">
        <div class="rest-timer-box">
            <h3 class="rest-title">💤 Rest Time</h3>
            <div class="timer-circle-container">
                <svg class="timer-svg" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="54" class="timer-bg-circle"/>
                    <circle cx="60" cy="60" r="54" class="timer-progress-circle" id="timer-svg-circle"/>
                </svg>
                <div class="timer-number" id="rest-seconds">60</div>
            </div>
            <p class="rest-next">Next: <strong id="rest-next-action">Set 2</strong></p>
            <button type="button" onclick="skipRest()" class="skip-rest-btn">
                <i class="bi bi-skip-forward-fill me-2"></i>Skip Rest
            </button>
        </div>
    </div>

    <!-- WORKOUT COMPLETE SUMMARY -->
    <div class="session-complete" id="session-complete" style="display:none;">
        <div class="complete-animation">🎉</div>
        <h2 class="complete-title">Workout Complete!</h2>
        <p class="complete-subtitle">Amazing job! You crushed it today.</p>
        
        <div class="complete-stats-grid">
            <div class="complete-stat">
                <i class="bi bi-clock-fill stat-icon" style="color: #0d9488;"></i>
                <span id="final-duration" class="stat-value">--</span>
                <label class="stat-label">Duration</label>
            </div>
            <div class="complete-stat">
                <i class="bi bi-fire stat-icon" style="color: #ef4444;"></i>
                <span id="final-calories" class="stat-value">--</span>
                <label class="stat-label">Calories Burned</label>
            </div>
            <div class="complete-stat">
                <i class="bi bi-check-circle-fill stat-icon" style="color: #22c55e;"></i>
                <span id="final-sets" class="stat-value">--</span>
                <label class="stat-label">Sets Completed</label>
            </div>
            <div class="complete-stat">
                <i class="bi bi-lightning-fill stat-icon" style="color: #f59e0b;"></i>
                <span id="final-exercises" class="stat-value">--</span>
                <label class="stat-label">Exercises Done</label>
            </div>
        </div>

        <!-- Calorie Breakdown -->
        <div class="calorie-breakdown">
            <h4 class="breakdown-title">
                <i class="bi bi-fire me-2"></i>Calorie Breakdown
            </h4>
            <div id="calorie-breakdown-list" class="breakdown-list">
                <!-- Generated by JS -->
            </div>
        </div>

        <!-- Session Notes -->
        <textarea id="session-final-notes" class="session-notes-input" placeholder="How did the workout feel? Any notes for your trainer..."></textarea>

        <!-- Action Buttons -->
        <div class="complete-actions">
            <button type="button" onclick="saveSession()" class="save-session-btn">
                <i class="bi bi-save-fill me-2"></i>Save Progress
            </button>
            <button type="button" onclick="closeSession()" class="close-session-btn">
                <i class="bi bi-x-lg me-2"></i>Close
            </button>
        </div>
    </div>

</div>
</div>

<script>
let foodSearchTimeout = null;
let selectedFoodNutrients = null;

const NUTRIENT_IDS = {
    CALORIES: 1008,
    PROTEIN: 1003,
    CARBS: 1005,
    FATS: 1004
};

const UNIT_MULTIPLIERS = {
    'grams': 1,
    'cups': 240,
    'pieces': 50,
    'oz': 28.35
};

function switchTab(tab) {
  // Hide all tabs
  document.querySelectorAll('.tab-pane').forEach(pane => {
    pane.classList.remove('active');
  });
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.classList.remove('active');
  });
  
  // Show selected tab
  document.getElementById('tab-' + tab).classList.add('active');
  event.target.classList.add('active');
}

// Food search with USDA API
document.getElementById('foodItemInput').addEventListener('input', function(e) {
    const query = e.target.value.trim();
    const dropdown = document.getElementById('foodSuggestionsDropdown');
    
    if (query.length < 2) {
        dropdown.style.display = 'none';
        return;
    }
    
    clearTimeout(foodSearchTimeout);
    foodSearchTimeout = setTimeout(async () => {
        try {
            dropdown.innerHTML = '<div class="food-suggestion-item text-muted">Searching...</div>';
            dropdown.style.display = 'block';
            
            const response = await fetch(`index.php?r=foodapi/search&query=${encodeURIComponent(query)}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const text = await response.text();
            console.log('Raw API Response:', text); // Debug log
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                console.error('JSON Parse Error:', parseError);
                console.error('Response text:', text);
                throw new Error('Invalid JSON response from server');
            }
            
            console.log('Parsed API Response:', data); // Debug log
            
            if (data.error) {
                dropdown.innerHTML = `<div class="food-suggestion-item text-danger">Error: ${data.error}</div>`;
                dropdown.style.display = 'block';
                return;
            }
            
            if (data.foods && data.foods.length > 0) {
                displayFoodDropdown(data.foods, dropdown);
            } else {
                dropdown.innerHTML = '<div class="food-suggestion-item text-muted">No results found</div>';
                dropdown.style.display = 'block';
            }
        } catch (error) {
            console.error('Error searching food:', error);
            const errorMsg = error.message || 'Unknown error occurred';
            dropdown.innerHTML = `<div class="food-suggestion-item text-danger">Error: ${errorMsg}</div>`;
            dropdown.style.display = 'block';
        }
    }, 500);
});

function displayFoodDropdown(foods, dropdown) {
    dropdown.innerHTML = '';
    
    foods.slice(0, 10).forEach(food => {
        const item = document.createElement('div');
        item.className = 'food-suggestion-item';
        
        // Extract nutrients safely
        const nutrients = extractNutrients(food.foodNutrients || []);
        
        item.innerHTML = `
            <div class="food-suggestion-name">${food.description}</div>
            <div class="food-suggestion-brand">${food.brandName || 'Generic'}</div>
            <div class="food-suggestion-nutrients">
                ${nutrients.calories} cal · ${nutrients.protein}g protein · ${nutrients.carbs}g carbs · ${nutrients.fats}g fat
            </div>
        `;
        
        item.onclick = () => selectFoodItem(food);
        dropdown.appendChild(item);
    });
    
    dropdown.style.display = 'block';
}

function extractNutrients(foodNutrients) {
    if (!foodNutrients || foodNutrients.length === 0) {
        return {
            calories: 0,
            protein: '0.0',
            carbs: '0.0',
            fats: '0.0'
        };
    }
    
    const calories = foodNutrients.find(n => n.nutrientId === NUTRIENT_IDS.CALORIES)?.value || 0;
    const protein = foodNutrients.find(n => n.nutrientId === NUTRIENT_IDS.PROTEIN)?.value || 0;
    const carbs = foodNutrients.find(n => n.nutrientId === NUTRIENT_IDS.CARBS)?.value || 0;
    const fats = foodNutrients.find(n => n.nutrientId === NUTRIENT_IDS.FATS)?.value || 0;
    
    return {
        calories: Math.round(calories),
        protein: protein.toFixed(1),
        carbs: carbs.toFixed(1),
        fats: fats.toFixed(1)
    };
}

function selectFoodItem(food) {
    const dropdown = document.getElementById('foodSuggestionsDropdown');
    const foodInput = document.getElementById('foodItemInput');
    
    // Set food name
    foodInput.value = food.description;
    dropdown.style.display = 'none';
    
    // Store nutrients for calculation
    selectedFoodNutrients = extractNutrients(food.foodNutrients || []);
    
    console.log('Selected food nutrients:', selectedFoodNutrients); // Debug log
    
    // Calculate and fill nutrients based on current quantity/unit
    recalculateNutrients();
}

function recalculateNutrients() {
    if (!selectedFoodNutrients) {
        console.log('No nutrients selected yet');
        return;
    }
    
    const quantity = parseFloat(document.getElementById('quantityInput').value) || 0;
    const unit = document.getElementById('unitInput').value;
    
    console.log('Recalculating:', { quantity, unit, selectedFoodNutrients }); // Debug log
    
    // Convert to grams
    const totalGrams = quantity * (UNIT_MULTIPLIERS[unit] || 1);
    const ratio = totalGrams / 100; // USDA data is per 100g
    
    // Calculate nutrients
    const calories = Math.round(parseFloat(selectedFoodNutrients.calories) * ratio);
    const protein = (parseFloat(selectedFoodNutrients.protein) * ratio).toFixed(1);
    const carbs = (parseFloat(selectedFoodNutrients.carbs) * ratio).toFixed(1);
    const fats = (parseFloat(selectedFoodNutrients.fats) * ratio).toFixed(1);
    
    console.log('Calculated nutrients:', { calories, protein, carbs, fats }); // Debug log
    
    // Fill inputs
    document.getElementById('caloriesInput').value = calories;
    document.getElementById('proteinInput').value = protein;
    document.getElementById('carbsInput').value = carbs;
    document.getElementById('fatsInput').value = fats;
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#foodItemInput') && !e.target.closest('#foodSuggestionsDropdown')) {
        document.getElementById('foodSuggestionsDropdown').style.display = 'none';
    }
});

// Workout form submission
document.getElementById('workoutForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  
  fetch('index.php?r=fitness/addWorkout', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Workout logged successfully!');
      location.reload();
    } else {
      alert('Error: ' + (data.error || 'Failed to log workout'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Error logging workout');
  });
});

// Nutrition form submission
document.getElementById('nutritionForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  
  fetch('index.php?r=fitness/addNutrition', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Nutrition logged successfully!');
      location.reload();
    } else {
      alert('Error: ' + (data.error || 'Failed to log nutrition'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Error logging nutrition');
  });
});

// ══════════════════════════════════════════════════════════
// WORKOUT SESSION ENGINE
// ══════════════════════════════════════════════════════════

// Check if workout tables exist before starting
async function checkAndStartWorkout() {
    try {
        // Try to check if the API endpoint exists
        const response = await fetch('index.php?r=workoutSession/start', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                request_id: <?= $requestId ?>,
                day_of_week: 'test'
            })
        });
        
        const data = await response.json();
        
        if (data.error && data.error.includes('not found')) {
            alert('⚠️ Workout Session Tracker Not Set Up Yet!\n\n' +
                  'Please run this SQL command first:\n' +
                  'mysql -u root -p webdev < sql/create_workout_session_tables.sql\n\n' +
                  'Or copy the SQL from sql/create_workout_session_tables.sql and run it in your MySQL client.\n\n' +
                  'This will create the necessary database tables for workout tracking.');
            return;
        }
        
        // If no error about missing tables, start the workout
        startWorkoutSession();
        
    } catch (error) {
        console.error('Error checking workout system:', error);
        alert('⚠️ Workout Session Tracker Not Set Up Yet!\n\n' +
              'Please run this SQL command first:\n' +
              'mysql -u root -p webdev < sql/create_workout_session_tables.sql\n\n' +
              'This will create the necessary database tables for workout tracking.');
    }
}

// ══════════════════════════════════════════════════════════
// MET VALUES FOR CALORIE CALCULATION (No API key needed!)
// ══════════════════════════════════════════════════════════
const MET_TABLE = {
    'default':     3.5,
    'push':        3.8,  // Push-ups, push press
    'pull':        4.0,  // Pull-ups, pulldowns
    'squat':       5.0,
    'deadlift':    5.0,
    'lunge':       4.0,
    'plank':       3.0,
    'crunch':      3.0,
    'sit':         3.5,  // Sit-ups
    'press':       4.0,  // Bench press, overhead press
    'curl':        3.0,  // Bicep curls
    'row':         4.5,  // Rows
    'fly':         3.5,  // Chest fly
    'dip':         4.5,
    'jump':        7.0,  // Jumping exercises
    'run':         7.0,  // Running
    'cardio':      6.5,
    'burpee':      8.0,
    'hiit':        8.0,
    'stretch':     2.5,
    'yoga':        2.5,
    'handstand':   5.0,  // Handstands
    'backlap':     4.0,  // Core exercises
    'spread':      4.0,
};

// Get MET value by matching exercise name keywords
function getMET(exerciseName) {
    const name = exerciseName.toLowerCase();
    for (const [keyword, met] of Object.entries(MET_TABLE)) {
        if (name.includes(keyword)) return met;
    }
    return MET_TABLE['default'];
}

// Calculate calories burned
// Formula: Calories = MET × weight(kg) × time(hours)
function calculateCalories(exerciseName, weightKg, durationSeconds) {
    const met      = getMET(exerciseName);
    const hours    = durationSeconds / 3600;
    const calories = met * weightKg * hours;
    return Math.round(calories * 10) / 10;
}

// ══════════════════════════════════════════════════════════
// SESSION STATE
// ══════════════════════════════════════════════════════════
const SESSION = {
    exercises:        [],   // Today's exercise list
    currentExIndex:   0,
    currentSetIndex:  0,
    currentReps:      0,
    setLog:           [],   // [{reps, weight, duration}]
    exerciseLog:      [],   // Completed exercise summaries
    sessionStartTime: null,
    setStartTime:     null,
    elapsedTimer:     null,
    restTimer:        null,
    restDuration:     60,
    totalCalories:    0,
    clientWeightKg:   70,   // Default, update from PHP if available
    sessionId:        null,
};

// ══════════════════════════════════════════════════════════
// START WORKOUT SESSION
// ══════════════════════════════════════════════════════════
async function startWorkoutSession() {
    try {
        // Get today's exercises from workout plan
        const today = new Date().toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();
        const dayKey = today.substring(0, 3); // 'mon', 'tue', etc.
        
        const workoutPlan = <?= json_encode($workoutPlan) ?>;
        SESSION.exercises = workoutPlan[dayKey] || [];
        
        if (SESSION.exercises.length === 0) {
            alert('No exercises scheduled for today!');
            return;
        }
        
        // Create session in database
        const response = await fetch('index.php?r=workoutSession/start', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                request_id: <?= $requestId ?>,
                day_of_week: today
            })
        });
        
        const data = await response.json();
        
        if (!data.success) {
            // Check if it's a database error
            if (data.error && (data.error.includes('not found') || data.error.includes('exist') || data.error.includes('Table'))) {
                alert('⚠️ Database tables not created yet!\n\n' +
                      'Please run this SQL command:\n' +
                      'mysql -u root -p webdev < sql/create_workout_session_tables.sql\n\n' +
                      'Then refresh the page and try again.');
            } else {
                alert('Could not start session: ' + data.error);
            }
            return;
        }
        
        SESSION.sessionId = data.session_id;
        SESSION.sessionStartTime = Date.now();
        
        // Open modal
        document.getElementById('workout-session-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Start elapsed timer
        SESSION.elapsedTimer = setInterval(updateElapsedTimer, 1000);
        
        // Load first exercise
        loadExercise(0);
        
    } catch (error) {
        console.error('Error starting workout:', error);
        alert('Failed to start workout session');
    }
}

// ══════════════════════════════════════════════════════════
// LOAD EXERCISE
// ══════════════════════════════════════════════════════════
function loadExercise(index) {
    SESSION.currentExIndex  = index;
    SESSION.currentSetIndex = 0;
    SESSION.setLog          = [];
    SESSION.setStartTime    = Date.now();
    
    const ex    = SESSION.exercises[index];
    const total = SESSION.exercises.length;
    
    // Update progress bar
    const progressPercent = (index / total) * 100;
    document.getElementById('session-ex-counter').textContent = 
        `Exercise ${index+1} of ${total}`;
    document.getElementById('session-progress-fill').style.width = 
        `${progressPercent}%`;
    
    // Set exercise info
    document.getElementById('session-ex-name').textContent = 
        ex.name || 'Exercise';
    document.getElementById('target-text').textContent = 
        ex.category || 'General';
    document.getElementById('equipment-text').textContent = 
        ex.equipment || 'Bodyweight';
    
    // Set GIF (if available)
    const gifEl = document.getElementById('session-gif');
    const noGifEl = document.getElementById('session-no-gif');
    if (ex.gifUrl) {
        gifEl.src = ex.gifUrl;
        gifEl.style.display = 'block';
        noGifEl.style.display = 'none';
    } else {
        gifEl.style.display = 'none';
        noGifEl.style.display = 'flex';
    }
    
    // Set default reps
    const targetReps = parseInt(ex.reps) || 10;
    SESSION.currentReps = targetReps;
    document.getElementById('rep-count').textContent = targetReps;
    document.getElementById('current-set-num').textContent = '1';
    document.getElementById('total-sets-display').textContent = ex.sets || 3;
    
    // Build set rows table
    buildSetRows(parseInt(ex.sets) || 3, targetReps);
    
    // Reset weight input
    document.getElementById('weight-input').value = '0';
    
    // Parse rest time (default 60s)
    SESSION.restDuration = 60;
}

// Build set tracker table rows
function buildSetRows(totalSets, targetReps) {
    const rows = document.getElementById('set-rows');
    rows.innerHTML = '';
    
    for (let i = 1; i <= totalSets; i++) {
        const row = document.createElement('div');
        row.className = 'set-row';
        row.id = `set-row-${i}`;
        row.innerHTML = `
            <span class="set-num">${i}</span>
            <span class="set-target">${targetReps}</span>
            <span class="set-actual" id="actual-${i}">—</span>
            <span class="set-weight" id="weight-${i}">—</span>
            <span class="set-check" id="check-${i}">○</span>
        `;
        rows.appendChild(row);
    }
}

// ══════════════════════════════════════════════════════════
// REP COUNTER
// ══════════════════════════════════════════════════════════
function adjustReps(delta) {
    SESSION.currentReps = Math.max(0, SESSION.currentReps + delta);
    document.getElementById('rep-count').textContent = SESSION.currentReps;
}

// ══════════════════════════════════════════════════════════
// COMPLETE A SET
// ══════════════════════════════════════════════════════════
function completeSet() {
    const ex          = SESSION.exercises[SESSION.currentExIndex];
    const setNum      = SESSION.currentSetIndex + 1;
    const reps        = SESSION.currentReps;
    const weight      = parseFloat(document.getElementById('weight-input').value) || 0;
    const setDuration = Math.round((Date.now() - SESSION.setStartTime) / 1000);
    
    // Log this set
    SESSION.setLog.push({ reps, weight, duration: setDuration });
    
    // Update set row UI
    document.getElementById(`actual-${setNum}`).textContent = reps;
    document.getElementById(`weight-${setNum}`).textContent = 
        weight > 0 ? `${weight}kg` : 'BW';
    document.getElementById(`check-${setNum}`).textContent = '✅';
    document.getElementById(`set-row-${setNum}`).classList.add('set-done');
    
    const totalSets = parseInt(ex.sets) || 3;
    SESSION.currentSetIndex++;
    
    if (SESSION.currentSetIndex < totalSets) {
        // More sets — show rest timer
        const nextSet = SESSION.currentSetIndex + 1;
        document.getElementById('rest-next-action').textContent = 
            `Set ${nextSet} of ${totalSets}`;
        document.getElementById('current-set-num').textContent = nextSet;
        SESSION.currentReps = parseInt(ex.reps) || 10;
        document.getElementById('rep-count').textContent = SESSION.currentReps;
        document.getElementById('weight-input').value = weight;
        
        startRestTimer(SESSION.restDuration);
    } else {
        // All sets done — move to next exercise or finish
        completeExercise();
    }
}

// ══════════════════════════════════════════════════════════
// REST TIMER
// ══════════════════════════════════════════════════════════
function startRestTimer(seconds) {
    const overlay = document.getElementById('rest-timer-overlay');
    const display = document.getElementById('rest-seconds');
    const circle  = document.getElementById('timer-svg-circle');
    
    overlay.style.display = 'flex';
    
    const circumference = 2 * Math.PI * 54; // r=54
    circle.style.strokeDasharray = circumference;
    circle.style.strokeDashoffset = 0;
    
    let remaining = seconds;
    display.textContent = remaining;
    
    SESSION.restTimer = setInterval(() => {
        remaining--;
        display.textContent = remaining;
        
        // Update SVG circle
        const progress = remaining / seconds;
        circle.style.strokeDashoffset = circumference * (1 - progress);
        
        if (remaining <= 0) {
            skipRest();
        }
    }, 1000);
}

function skipRest() {
    clearInterval(SESSION.restTimer);
    document.getElementById('rest-timer-overlay').style.display = 'none';
    SESSION.setStartTime = Date.now();
}

// ══════════════════════════════════════════════════════════
// COMPLETE EXERCISE
// ══════════════════════════════════════════════════════════
function completeExercise() {
    const ex = SESSION.exercises[SESSION.currentExIndex];
    
    // Calculate total exercise duration
    const totalSetTime = SESSION.setLog.reduce((sum, s) => sum + s.duration, 0);
    const totalRestTime = (SESSION.setLog.length - 1) * SESSION.restDuration;
    const totalExerciseTime = totalSetTime + totalRestTime;
    
    // Calculate calories for this exercise
    const calories = calculateCalories(
        ex.name,
        SESSION.clientWeightKg,
        totalExerciseTime
    );
    
    // Save exercise log
    SESSION.exerciseLog.push({
        exercise_id:    '',
        exercise_name:  ex.name,
        sets_completed: SESSION.setLog.length,
        set_data:       SESSION.setLog,
        duration_secs:  totalSetTime,
        calories_burned: calories
    });
    
    SESSION.totalCalories += calories;
    
    const nextIndex = SESSION.currentExIndex + 1;
    
    if (nextIndex < SESSION.exercises.length) {
        // Show rest before next exercise
        document.getElementById('rest-next-action').textContent = 
            SESSION.exercises[nextIndex].name;
        
        // Start rest timer and load next exercise after
        startRestTimer(SESSION.restDuration);
        
        const originalSkip = window.skipRest;
        window.skipRest = function() {
            clearInterval(SESSION.restTimer);
            document.getElementById('rest-timer-overlay').style.display = 'none';
            loadExercise(nextIndex);
            window.skipRest = originalSkip;
        };
    } else {
        // ALL EXERCISES DONE
        showSessionComplete();
    }
}

// ══════════════════════════════════════════════════════════
// SESSION COMPLETE SUMMARY
// ══════════════════════════════════════════════════════════
function showSessionComplete() {
    clearInterval(SESSION.elapsedTimer);
    
    const elapsed  = Math.round((Date.now() - SESSION.sessionStartTime) / 1000);
    const minutes  = Math.floor(elapsed / 60);
    const seconds  = elapsed % 60;
    const totalSets = SESSION.exerciseLog.reduce(
        (sum, e) => sum + e.sets_completed, 0
    );
    
    // Update progress bar to 100%
    document.getElementById('session-progress-fill').style.width = '100%';
    
    // Fill summary
    document.getElementById('final-duration').textContent = 
        `${minutes}m ${seconds}s`;
    document.getElementById('final-calories').textContent = 
        `${Math.round(SESSION.totalCalories)} kcal`;
    document.getElementById('final-sets').textContent = totalSets;
    document.getElementById('final-exercises').textContent = 
        SESSION.exerciseLog.length;
    
    // Per-exercise calorie breakdown
    const breakdownList = document.getElementById('calorie-breakdown-list');
    breakdownList.innerHTML = SESSION.exerciseLog.map(e => `
        <div class="breakdown-row">
            <span class="breakdown-name">${e.exercise_name}</span>
            <span class="breakdown-sets">${e.sets_completed} sets</span>
            <span class="breakdown-cals">🔥 ${Math.round(e.calories_burned)} kcal</span>
        </div>
    `).join('');
    
    // Show complete panel
    document.getElementById('session-ex-area').style.display = 'none';
    document.getElementById('session-complete').style.display = 'block';
}

// ══════════════════════════════════════════════════════════
// SAVE SESSION TO DATABASE
// ══════════════════════════════════════════════════════════
async function saveSession() {
    const notes = document.getElementById('session-final-notes').value;
    const elapsed = Math.round((Date.now() - SESSION.sessionStartTime) / 1000);
    
    const btn = document.querySelector('.save-session-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass me-2"></i>Saving...';
    
    const payload = {
        session_id:      SESSION.sessionId,
        request_id:      <?= $requestId ?>,
        total_duration:  elapsed,
        total_calories:  SESSION.totalCalories,
        exercise_log:    SESSION.exerciseLog,
        notes:           notes
    };
    
    try {
        const response = await fetch('index.php?r=workoutSession/complete', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (data.success) {
            btn.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Saved!';
            // Prompt weekly weight log (once per calendar week)
            if (typeof window.showWeightLogPromptIfNeeded === 'function') {
                window.showWeightLogPromptIfNeeded();
            }
            setTimeout(() => {
                closeSession();
                location.reload(); // Refresh to show updated logs
            }, 1500);

        } else {
            alert('Save failed: ' + data.error);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-save-fill me-2"></i>Save Progress';
        }
    } catch (error) {
        console.error('Error saving session:', error);
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save-fill me-2"></i>Save Progress';
    }
}

// ══════════════════════════════════════════════════════════
// UTILITY FUNCTIONS
// ══════════════════════════════════════════════════════════
function updateElapsedTimer() {
    const elapsed = Math.round((Date.now() - SESSION.sessionStartTime) / 1000);
    const m = Math.floor(elapsed / 60).toString().padStart(2, '0');
    const s = (elapsed % 60).toString().padStart(2, '0');
    document.getElementById('session-elapsed').textContent = `${m}:${s}`;
}

function confirmEndSession() {
    if (confirm('End workout early? Progress will be saved.')) {
        showSessionComplete();
    }
}

function closeSession() {
    document.getElementById('workout-session-modal').style.display = 'none';
    document.body.style.overflow = '';
    clearInterval(SESSION.elapsedTimer);
    clearInterval(SESSION.restTimer);
}
</script>

<!-- ══ EXERCISE FORM GUIDE & VIDEO MODAL ══ -->
<div class="modal fade" id="exerciseFormGuideModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
      <div class="modal-header bg-dark text-white border-0 py-3">
        <h5 class="modal-title fw-bold" id="exGuideTitle"><i class="bi bi-journal-text me-2 text-success"></i>Exercise Guide</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4 bg-light">
        <div class="d-flex flex-wrap gap-2 mb-3">
          <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6 rounded-pill" id="exGuideCategory">Category</span>
          <span class="badge bg-secondary-subtle text-secondary border px-3 py-2 fs-6 rounded-pill" id="exGuideEquipment">Equipment</span>
        </div>

        <div class="card border-0 shadow-sm rounded-3 mb-4">
          <div class="card-body">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-list-check me-2 text-success"></i>Step-by-Step Form &amp; Execution Guide</h6>
            <ol class="ps-3 mb-3 text-secondary" id="exGuideSteps" style="line-height: 1.7; font-size: 0.92rem;"></ol>
            <div class="alert alert-success border-0 bg-success-subtle text-success mb-0 rounded-3 p-3" style="font-size: 0.88rem;">
              <i class="bi bi-lightbulb-fill me-2"></i><strong id="exGuideTips">Pro Form Tip: Keep core engaged and movement controlled.</strong>
            </div>
          </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
          <div class="card-body text-center p-4">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-youtube me-2 text-danger"></i>Video Demonstration &amp; Form Tutorial</h6>
            <p class="text-muted small mb-3">Watch proper posture, grip, and motion video guide below:</p>
            <div class="d-grid gap-2 col-md-10 mx-auto">
              <a href="#" id="exGuideYoutubeBtn" target="_blank" class="btn btn-danger btn-lg rounded-pill fw-bold py-2 shadow-sm">
                <i class="bi bi-play-btn-fill me-2"></i>Watch HD Video Tutorial on YouTube 🎥
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const FORM_GUIDES = {
  'bench press': {
    steps: [
      "Lie flat on bench with feet planted on floor. Retract shoulder blades back and down.",
      "Grip barbell slightly wider than shoulder-width with wrists firm and straight.",
      "Unrack bar under control and lower it steadily to your mid-chest while inhaling.",
      "Press bar powerfully upward until arms extend, driving through palms while exhaling."
    ],
    tips: "Keep your elbows tucked at ~45° to protect shoulder joints; avoid flaring elbows wide."
  },
  'squat': {
    steps: [
      "Place barbell on upper traps with feet shoulder-width apart, toes pointed slightly outward.",
      "Brace core tightly, push hips back, and bend knees to lower into a deep squat.",
      "Descend until thighs are parallel to the floor, keeping chest elevated.",
      "Drive through heels and mid-foot to stand back up powerfully while exhaling."
    ],
    tips: "Ensure knees track over toes and avoid letting knees buckle inward."
  },
  'deadlift': {
    steps: [
      "Stand with feet hip-width apart, bar over mid-foot. Hinge at hips to grab the bar.",
      "Engage lats, straighten lower back, and take a deep breath to brace core.",
      "Drive floor away with legs and pull hips forward to stand tall at lockout.",
      "Hinge at hips to lower bar back to the floor under complete control."
    ],
    tips: "Keep the bar close to your body throughout; do not round your lumbar spine."
  },
  'lat pulldown': {
    steps: [
      "Sit securely at lat pulldown machine with knees locked under pads. Grip bar wide.",
      "Lean back slightly (~10-15°) and initiate pull by pulling shoulder blades downward.",
      "Pull bar down until it lightly touches your upper chest while exhaling.",
      "Slowly extend arms upward to full overhead stretch while inhaling."
    ],
    tips: "Focus on pulling with your elbows rather than yanking with hands."
  },
  'overhead press': {
    steps: [
      "Hold barbell at collarbone level with elbows slightly forward.",
      "Brace core and squeeze glutes. Press bar vertically overhead, clearing your head.",
      "Lock arms overhead with head slightly forward at top of motion.",
      "Lower bar back down under control to upper chest."
    ],
    tips: "Avoid arching lower back excessively; keep core locked tight."
  },
  'bicep curl': {
    steps: [
      "Stand tall holding weights with palms facing forward.",
      "Keep upper arms stationary at sides, curl weights toward shoulders.",
      "Squeeze biceps tightly at top peak contraction for 1 second.",
      "Lower weights steadily back down to full extension."
    ],
    tips: "Do not swing hips or use body momentum to raise weights."
  },
  'tricep pushdown': {
    steps: [
      "Attach bar or rope to high cable pulley. Hinge slightly forward at hips.",
      "Pin elbows tightly to your sides throughout whole set.",
      "Extend arms down until fully locked out, flexing triceps.",
      "Slowly return hands to chest height under control."
    ],
    tips: "Keep elbows fixed; do not let shoulders swing."
  }
};

function openExerciseGuide(name, equipment, category) {
  document.getElementById('exGuideTitle').innerHTML = '<i class="bi bi-journal-text me-2 text-success"></i>' + name;
  document.getElementById('exGuideCategory').textContent = '🏷️ ' + (category || 'General');
  document.getElementById('exGuideEquipment').textContent = '🔧 ' + (equipment || 'Equipment Needed');

  const lower = name.toLowerCase();
  let guide = null;
  for (let k in FORM_GUIDES) {
    if (lower.includes(k)) { guide = FORM_GUIDES[k]; break; }
  }

  const stepsEl = document.getElementById('exGuideSteps');
  const tipsEl = document.getElementById('exGuideTips');
  
  if (guide) {
    stepsEl.innerHTML = guide.steps.map(s => `<li>${s}</li>`).join('');
    tipsEl.textContent = 'Pro Form Tip: ' + guide.tips;
  } else {
    stepsEl.innerHTML = `
      <li>Position yourself with proper posture and firm grip on ${equipment || 'the equipment'}.</li>
      <li>Perform full range of motion under steady, controlled speed.</li>
      <li>Exhale during effort phase and inhale while returning to start position.</li>
      <li>Maintain core tightness and neutral spine alignment throughout.</li>
    `;
    tipsEl.textContent = `Pro Form Tip: Perform ${name} with smooth cadence and controlled breathing.`;
  }

  const ytQuery = encodeURIComponent(name + ' exercise proper form execution tutorial');
  document.getElementById('exGuideYoutubeBtn').href = 'https://www.youtube.com/results?search_query=' + ytQuery;

  const modal = new bootstrap.Modal(document.getElementById('exerciseFormGuideModal'));
  modal.show();
}
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
