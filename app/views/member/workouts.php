<?php
declare(strict_types=1);
$pageTitle = 'Workout Progress & Performance';
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

.fit-card {
  background: var(--bg-card);
  border: 1px solid var(--border-card);
  border-radius: 12px;
  box-shadow: var(--shadow-card);
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.fit-card-header {
  background: var(--bg-section-header);
  border-left: 4px solid var(--accent-teal);
  border-bottom: 1px solid var(--border-card);
  padding: 14px 20px;
}
.fit-heading {
  color: var(--accent-teal) !important;
  font-size: 13px;
  font-weight: 800;
  letter-spacing: 2px;
  text-transform: uppercase;
  margin: 0;
}

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

.btn-fit-primary {
  background: var(--accent-green-btn);
  color: #fff !important;
  border: none;
  border-radius: 8px;
  padding: 10px 18px;
  font-weight: 600;
  font-size: 14px;
  transition: all .2s;
}
.btn-fit-primary:hover { background: var(--accent-green-hover); transform: translateY(-1px); }

.btn-fit-outline {
  background: #ffffff;
  border: 1px solid var(--border-card);
  color: var(--text-secondary) !important;
  border-radius: 8px;
  padding: 10px 18px;
  font-weight: 600;
  font-size: 14px;
  transition: all .2s;
}
.btn-fit-outline:hover { border-color: var(--accent-teal); color: var(--accent-teal) !important; background: #f0fdf9; }

/* Workout table */
.workout-table { width: 100%; }
.workout-table thead th {
  color: var(--text-secondary);
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  border-bottom: 2px solid var(--border-card);
  padding: 12px 10px;
}
.workout-table td {
  border-bottom: 1px solid var(--border-card);
  vertical-align: middle;
  font-size: 14px;
  padding: 12px 10px;
}
.workout-table tbody tr:hover { background: #f8fafc; }

/* Exercise row */
.exercise-row {
  border: 1px solid var(--border-card);
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: .75rem;
  background: #f8fafc;
}
.exercise-row:last-child { margin-bottom: 0; }

/* Popular exercise item */
.pop-exercise-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid var(--border-card);
}
.pop-exercise-item:last-child { border-bottom: none; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="fw-extrabold mb-1" style="color: var(--text-primary); font-size: 26px; font-weight: 800;">
            <i class="bi bi-activity me-2" style="color: var(--accent-teal)"></i>Workout Progress & Performance
        </h1>
        <p class="text-muted mb-0" style="font-size: 14px;">Track your exercise sessions and monitor your fitness progress</p>
    </div>
    <button class="btn-fit-primary" data-bs-toggle="modal" data-bs-target="#addWorkoutModal">
        <i class="bi bi-plus-circle me-1"></i>Log Workout
    </button>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger" style="border-radius: 12px;"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success" style="border-radius: 12px;"><i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($success) ?></div>
    <script>
    // Prompt weekly weight log after a manual workout is saved
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.showWeightLogPromptIfNeeded === 'function') {
            window.showWeightLogPromptIfNeeded();
        }
    });
    </script>
<?php endif; ?>


<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="fit-card text-center p-4">
            <div class="display-6 fw-bold" style="color: #3b82f6;"><?= (int)($stats['total_sessions'] ?? 0) ?></div>
            <div style="color: var(--text-secondary); font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px;">Total Workouts</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="fit-card text-center p-4">
            <div class="display-6 fw-bold" style="color: #16a34a;"><?= number_format((float)($stats['total_minutes'] ?? 0) / 60, 1) ?></div>
            <div style="color: var(--text-secondary); font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px;">Hours Trained</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="fit-card text-center p-4">
            <div class="display-6 fw-bold" style="color: #d97706;"><?= number_format((float)($stats['avg_duration'] ?? 0), 0) ?></div>
            <div style="color: var(--text-secondary); font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px;">Avg Mins/Session</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="fit-card text-center p-4">
            <div class="display-6 fw-bold" style="color: #06b6d4;"><?= number_format((float)($stats['total_calories'] ?? 0)) ?></div>
            <div style="color: var(--text-secondary); font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px;">Calories Burned</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column - Workout History -->
    <div class="col-lg-8">
        <!-- Progress Chart -->
        <?php if (!empty($stats['monthly_progress'])): ?>
        <div class="fit-card">
            <div class="fit-card-header">
                <h5 class="fit-heading"><i class="bi bi-graph-up me-2"></i>Monthly Progress</h5>
            </div>
            <div class="p-4">
                <canvas id="progressChart" height="100"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Workouts -->
        <div class="fit-card">
            <div class="fit-card-header">
                <h5 class="fit-heading"><i class="bi bi-list-ul me-2"></i>Recent Workouts</h5>
            </div>
            <div class="p-4">
                <?php if (empty($workouts)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-activity display-1 text-muted"></i>
                    <h5 class="mt-3 fw-bold" style="color: var(--text-primary);">No workouts logged yet</h5>
                    <p class="text-muted mb-4">Start tracking your fitness journey by logging your first workout!</p>
                    <button class="btn-fit-primary" data-bs-toggle="modal" data-bs-target="#addWorkoutModal">
                        <i class="bi bi-plus-circle me-1"></i>Log Your First Workout
                    </button>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table workout-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Duration</th>
                                <th>Calories</th>
                                <th>Exercises</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($workouts as $workout):
                                $__st = $workout['session_type'];
                                if ($__st === 'cardio') { $typeIcon = 'bi-heart-pulse'; }
                                elseif ($__st === 'strength') { $typeIcon = 'bi-lightning'; }
                                elseif ($__st === 'mixed') { $typeIcon = 'bi-activity'; }
                                else { $typeIcon = 'bi-circle'; }

                                if ($__st === 'cardio') { $typeBg = '#fee2e2'; }
                                elseif ($__st === 'strength') { $typeBg = '#dbeafe'; }
                                elseif ($__st === 'mixed') { $typeBg = '#dcfce7'; }
                                else { $typeBg = '#e2e8f0'; }

                                if ($__st === 'cardio') { $typeColor = '#ef4444'; }
                                elseif ($__st === 'strength') { $typeColor = '#3b82f6'; }
                                elseif ($__st === 'mixed') { $typeColor = '#16a34a'; }
                                else { $typeColor = '#64748b'; }
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold" style="color: var(--text-primary);"><?= date('M j, Y', strtotime($workout['session_date'])) ?></div>
                                    <small style="color: var(--text-secondary);"><?= date('l', strtotime($workout['session_date'])) ?></small>
                                </td>
                                <td>
                                    <span style="background: <?= $typeBg ?>; color: <?= $typeColor ?>; border-radius: 6px; padding: 4px 10px; font-size: 12px; font-weight: 600;">
                                        <i class="bi <?= $typeIcon ?> me-1"></i>
                                        <?= ucfirst($workout['session_type']) ?>
                                    </span>
                                </td>
                                <td><strong><?= $workout['duration_minutes'] ?></strong> min</td>
                                <td style="color: var(--text-primary);"><?= $workout['calories_burned'] ? number_format($workout['calories_burned']) : '—' ?></td>
                                <td>
                                    <div class="small">
                                        <?php if ($workout['exercise_count'] > 0): ?>
                                        <strong><?= $workout['exercise_count'] ?></strong> exercises
                                        <?php if ($workout['exercises']): ?>
                                        <br><span style="color: var(--text-secondary);"><?= htmlspecialchars(substr($workout['exercises'], 0, 30)) ?><?= strlen($workout['exercises']) > 30 ? '...' : '' ?></span>
                                        <?php endif; ?>
                                        <?php else: ?>
                                        <span style="color: var(--text-secondary);">No exercises logged</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <button class="btn-fit-outline" style="padding: 6px 12px; font-size: 13px;" onclick="viewWorkout(<?= $workout['id'] ?>)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column - Popular Exercises & Quick Stats -->
    <div class="col-lg-4">
        <!-- Popular Exercises -->
        <?php if (!empty($popularExercises)): ?>
        <div class="fit-card">
            <div class="fit-card-header">
                <h5 class="fit-heading"><i class="bi bi-star me-2"></i>Popular Exercises</h5>
            </div>
            <div class="p-4">
                <?php foreach (array_slice($popularExercises, 0, 8) as $exercise): ?>
                <div class="pop-exercise-item">
                    <div>
                        <div class="fw-semibold" style="font-size: 14px; color: var(--text-primary);"><?= htmlspecialchars($exercise['exercise_name']) ?></div>
                        <small style="color: var(--text-secondary);">
                            <?= $exercise['frequency'] ?> times
                            <?php if ($exercise['max_weight'] > 0): ?>
                            · Max: <?= number_format($exercise['max_weight'], 1) ?>kg
                            <?php endif; ?>
                        </small>
                    </div>
                    <span style="background: var(--bg-section-header); color: var(--accent-teal); border-radius: 6px; padding: 4px 10px; font-size: 12px; font-weight: 600;"><?= $exercise['total_sets'] ?> sets</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Log -->
        <div class="fit-card">
            <div class="fit-card-header">
                <h5 class="fit-heading"><i class="bi bi-lightning-charge me-2"></i>Quick Log</h5>
            </div>
            <div class="p-4">
                <p style="color: var(--text-secondary); font-size: 13px; margin-bottom: 1rem;">Log a quick workout session</p>
                <form method="post" class="quick-workout-form">
                    <input type="hidden" name="action" value="add_workout">
                    <input type="hidden" name="session_date" value="<?= date('Y-m-d') ?>">

                    <div class="mb-3">
                        <label class="fit-label">Type</label>
                        <select name="session_type" class="form-select fit-input">
                            <option value="mixed">Mixed</option>
                            <option value="cardio">Cardio</option>
                            <option value="strength">Strength</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="fit-label">Duration (minutes)</label>
                        <input type="number" name="duration" class="form-control fit-input" min="1" max="300" value="30">
                    </div>

                    <div class="mb-3">
                        <label class="fit-label">Calories (optional)</label>
                        <input type="number" name="calories" class="form-control fit-input" min="0" max="2000">
                    </div>

                    <button type="submit" class="btn-fit-primary w-100">
                        <i class="bi bi-plus-circle me-1"></i>Quick Log
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Workout Modal -->
<div class="modal fade" id="addWorkoutModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 12px; border: none; background: #ffffff;">
            <div class="modal-header" style="border-bottom: 1px solid var(--border-card); background: #f8fafc; padding: 16px 24px;">
                <h5 class="modal-title fw-bold" style="color: var(--text-primary);">
                    <i class="bi bi-plus-circle me-2" style="color: var(--accent-teal);"></i>Log Workout Session
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body" style="padding: 24px;">
                    <input type="hidden" name="action" value="add_workout">

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="fit-label">Date</label>
                            <input type="date" name="session_date" class="form-control fit-input" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="fit-label">Workout Type</label>
                            <select name="session_type" class="form-select fit-input" required>
                                <option value="mixed">Mixed Training</option>
                                <option value="cardio">Cardio</option>
                                <option value="strength">Strength Training</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="fit-label">Duration (minutes)</label>
                            <input type="number" name="duration" class="form-control fit-input" min="1" max="300" required>
                        </div>
                        <div class="col-md-6">
                            <label class="fit-label">Calories Burned (optional)</label>
                            <input type="number" name="calories" class="form-control fit-input" min="0" max="2000">
                        </div>
                        <div class="col-12">
                            <label class="fit-label">Notes</label>
                            <textarea name="notes" class="form-control fit-input" rows="2" placeholder="How did you feel? Any achievements?"></textarea>
                        </div>
                    </div>

                    <!-- Exercises Section -->
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold mb-0" style="font-size: 13px; color: var(--text-primary); text-transform: uppercase; letter-spacing: 1px;">
                            <i class="bi bi-list-check me-2" style="color: var(--accent-teal);"></i>Exercises
                        </h6>
                        <button type="button" class="btn-fit-outline" style="padding: 6px 12px; font-size: 13px;" onclick="addExerciseRow()">
                            <i class="bi bi-plus me-1"></i>Add Exercise
                        </button>
                    </div>

                    <div id="exerciseContainer">
                        <div class="exercise-row">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="fit-label">Exercise Name</label>
                                    <input type="text" name="exercises[0][name]" class="form-control fit-input" placeholder="e.g., Bench Press">
                                </div>
                                <div class="col-md-2">
                                    <label class="fit-label">Sets</label>
                                    <input type="number" name="exercises[0][sets]" class="form-control fit-input" min="0" max="20">
                                </div>
                                <div class="col-md-2">
                                    <label class="fit-label">Reps</label>
                                    <input type="text" name="exercises[0][reps]" class="form-control fit-input" placeholder="12,10,8">
                                </div>
                                <div class="col-md-2">
                                    <label class="fit-label">Weight (kg)</label>
                                    <input type="number" name="exercises[0][weight]" class="form-control fit-input" step="0.5" min="0">
                                </div>
                                <div class="col-md-2">
                                    <label class="fit-label">Distance (km)</label>
                                    <input type="number" name="exercises[0][distance]" class="form-control fit-input" step="0.1" min="0">
                                </div>
                            </div>
                            <div class="row g-2 mt-2">
                                <div class="col-md-3">
                                    <label class="fit-label">Duration (min)</label>
                                    <input type="number" name="exercises[0][duration]" class="form-control fit-input" min="0">
                                </div>
                                <div class="col-md-7">
                                    <label class="fit-label">Notes</label>
                                    <input type="text" name="exercises[0][notes]" class="form-control fit-input" placeholder="Form notes, difficulty, etc.">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" class="btn w-100" style="background: #fee2e2; border: 1px solid #f87171; color: #ef4444; border-radius: 8px; padding: 10px;" onclick="removeExerciseRow(this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--border-card); background: #f8fafc; padding: 16px 24px;">
                    <button type="button" class="btn-fit-outline" data-bs-dismiss="modal" style="padding: 8px 16px;">Cancel</button>
                    <button type="submit" class="btn-fit-primary" style="padding: 8px 16px;">
                        <i class="bi bi-save me-1"></i>Save Workout
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let exerciseCount = 1;

function addExerciseRow() {
    const container = document.getElementById('exerciseContainer');
    const newRow = document.createElement('div');
    newRow.className = 'exercise-row';
    newRow.innerHTML = `
        <div class="row g-2">
            <div class="col-md-4">
                <label class="fit-label">Exercise Name</label>
                <input type="text" name="exercises[${exerciseCount}][name]" class="form-control fit-input" placeholder="e.g., Bench Press">
            </div>
            <div class="col-md-2">
                <label class="fit-label">Sets</label>
                <input type="number" name="exercises[${exerciseCount}][sets]" class="form-control fit-input" min="0" max="20">
            </div>
            <div class="col-md-2">
                <label class="fit-label">Reps</label>
                <input type="text" name="exercises[${exerciseCount}][reps]" class="form-control fit-input" placeholder="12,10,8">
            </div>
            <div class="col-md-2">
                <label class="fit-label">Weight (kg)</label>
                <input type="number" name="exercises[${exerciseCount}][weight]" class="form-control fit-input" step="0.5" min="0">
            </div>
            <div class="col-md-2">
                <label class="fit-label">Distance (km)</label>
                <input type="number" name="exercises[${exerciseCount}][distance]" class="form-control fit-input" step="0.1" min="0">
            </div>
        </div>
        <div class="row g-2 mt-2">
            <div class="col-md-3">
                <label class="fit-label">Duration (min)</label>
                <input type="number" name="exercises[${exerciseCount}][duration]" class="form-control fit-input" min="0">
            </div>
            <div class="col-md-7">
                <label class="fit-label">Notes</label>
                <input type="text" name="exercises[${exerciseCount}][notes]" class="form-control fit-input" placeholder="Form notes, difficulty, etc.">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn w-100" style="background: #fee2e2; border: 1px solid #f87171; color: #ef4444; border-radius: 8px; padding: 10px;" onclick="removeExerciseRow(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newRow);
    exerciseCount++;
}

function removeExerciseRow(button) {
    const row = button.closest('.exercise-row');
    if (document.querySelectorAll('.exercise-row').length > 1) {
        row.remove();
    } else {
        alert('At least one exercise row must remain.');
    }
}

function viewWorkout(workoutId) {
    alert('Workout details view - ID: ' + workoutId);
}

// Progress Chart
<?php if (!empty($stats['monthly_progress'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('progressChart').getContext('2d');
    const monthlyData = <?= json_encode($stats['monthly_progress']) ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyData.map(d => d.month),
            datasets: [{
                label: 'Workouts',
                data: monthlyData.map(d => d.session_count),
                borderColor: '#0d9488',
                backgroundColor: 'rgba(13,148,136,0.1)',
                tension: 0.4
            }, {
                label: 'Hours',
                data: monthlyData.map(d => Math.round(d.total_minutes / 60 * 10) / 10),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                x: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#64748b', font: { size: 11 } } },
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { color: '#64748b', font: { size: 11 } } }
            }
        }
    });
});
<?php endif; ?>
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>