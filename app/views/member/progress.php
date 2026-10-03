<?php
declare(strict_types=1);
$pageTitle = 'Weight Progress & Analytics';
require __DIR__ . '/../partials/header.php';
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

  /* Borders */
  --border-card:       #e2e8f0;
  --border-input:      #cbd5e1;
  --border-teal:       #0d9488;

  /* Accent Colors */
  --accent-teal:       #0d9488;
  --accent-teal-light: #14b8a6;
  --accent-blue:       #06b6d4;
  --accent-green-btn:  #166534;
  --accent-green-hover:#15803d;

  /* Text */
  --text-primary:      #1e293b;
  --text-secondary:    #64748b;

  /* Shadows */
  --shadow-card: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.05);
  --shadow-sm:   0 1px 2px rgba(0,0,0,0.06);
}

body {
  background: var(--bg-page) !important;
  font-family: 'Inter', system-ui, sans-serif !important;
  color: var(--text-primary) !important;
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

.fit-card-header {
  background: var(--bg-section-header);
  border-left: 4px solid var(--accent-teal);
  border-bottom: 1px solid var(--border-card);
  padding: 14px 20px;
}
.fit-card-header.types-hd {
  background: #f8fafc;
  border-left: 4px solid var(--accent-blue);
}

.fit-heading {
  color: var(--accent-teal) !important;
  font-size: 13px;
  font-weight: 800;
  letter-spacing: 2px;
  text-transform: uppercase;
  margin: 0;
}
.fit-card-header.types-hd .fit-heading {
  color: var(--text-primary) !important;
}

/* ── Buttons & Inputs ── */
.btn-fit-primary {
  background: var(--accent-green-btn);
  color: #fff !important;
  border: none;
  border-radius: 8px;
  padding: 10px 18px;
  font-weight: 600;
  font-size: 14px;
  transition: all .2s;
  box-shadow: var(--shadow-sm);
  display: inline-block;
}
.btn-fit-primary:hover {
  background: var(--accent-green-hover);
  transform: translateY(-1px);
}

.fit-input {
  background: var(--bg-input) !important;
  border: 1px solid var(--border-input) !important;
  color: var(--text-primary) !important;
  border-radius: 8px !important;
  padding: 10px 14px !important;
  font-size: 14px !important;
}
.fit-input:focus {
  border-color: var(--accent-teal) !important;
  box-shadow: 0 0 0 3px rgba(13,148,136,0.12) !important;
  outline: none;
}

/* ── Responsive 2-Column Progress Grid ── */
.prg-page-grid {
  display: grid;
  grid-template-columns: minmax(320px, 380px) 1fr;
  gap: 1.5rem;
  align-items: start;
}

@media (max-width: 991.98px) {
  .prg-page-grid {
    grid-template-columns: 1fr;
    gap: 1.25rem;
  }
}
</style>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h1 class="fw-extrabold mb-1" style="color: var(--text-primary); font-size: 26px; font-weight: 800;">
            <i class="bi bi-graph-up-arrow me-2" style="color: var(--accent-teal)"></i>Weight Progress &amp; Analytics
        </h1>
        <p class="mb-0" style="color: var(--text-secondary); font-size: 14px;">Monitor weekly weight trends, fitness goals, and consistency scores</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-success fw-bold px-3 rounded-3" data-bs-toggle="modal" data-bs-target="#logWeightModal">
            <i class="bi bi-plus-circle me-1"></i>Log Weight
        </button>
        <a href="index.php?r=member/goals" class="btn-fit-primary text-decoration-none" style="white-space:nowrap;">
            <i class="bi bi-target me-1"></i>Manage Fitness Goals &rarr;
        </a>
    </div>
</div>

<?php
$pd          = $progressData ?? [];
$wHistory    = $pd['weightHistory']   ?? [];
$latestW     = $pd['latestWeight']    ?? null;
$firstW      = $pd['firstWeight']     ?? null;
$netChLbl    = $pd['netChangeLabel']  ?? null;
$netChVal    = $pd['netChange']       ?? null;
$fGoal       = $pd['fitnessGoal']     ?? null;
$cScore      = (int)($pd['consistencyScore'] ?? 0);
$hasWeight   = !empty($latestW);
$goalColors  = ['bulking' => '#16a34a', 'cutting' => '#ea580c', 'maintaining' => '#2563eb'];
$goalIcons   = ['bulking' => '📈', 'cutting' => '📉', 'maintaining' => '➡️'];
$goalDescs   = ['bulking' => 'Build Mass & Strength', 'cutting' => 'Lose Body Fat & Lean Out', 'maintaining' => 'Maintain Weight & Balance'];
$activeColor = $goalColors[$fGoal] ?? 'var(--accent-teal)';

// ── Goal Alignment & Trajectory Analysis ──
$goalAlignment = [
    'status'     => 'none', // 'on_track', 'warning', 'plateau', 'none'
    'badge'      => '',
    'badgeClass' => 'bg-secondary',
    'title'      => '',
    'message'    => '',
    'bg'         => '#f8fafc',
    'border'     => '#e2e8f0',
    'textColor'  => '#475569',
    'icon'       => 'bi-info-circle',
];

if (count($wHistory) >= 2 && $netChVal !== null && !empty($fGoal)) {
    if ($fGoal === 'cutting') {
        if ($netChVal < -0.1) {
            // Decreasing weight during cutting -> ON TRACK
            $goalAlignment = [
                'status'     => 'on_track',
                'badge'      => '✅ On Track',
                'badgeClass' => 'bg-success text-white',
                'title'      => 'Cutting Phase: On Track (Weight Decreasing)',
                'message'    => 'Great progress! Your weight is down by <strong>' . abs($netChVal) . ' kg</strong> since your starting log, perfectly aligning with your Fat Loss (Cutting) goal.',
                'bg'         => '#f0fdf4',
                'border'     => '#86efac',
                'textColor'  => '#166534',
                'icon'       => 'bi-check-circle-fill text-success',
            ];
        } elseif ($netChVal > 0.1) {
            // Increasing weight during cutting -> WARNING / MISALIGNED
            $goalAlignment = [
                'status'     => 'warning',
                'badge'      => '⚠️ Off Track / Warning',
                'badgeClass' => 'bg-danger text-white',
                'title'      => 'Goal Misalignment Warning: Weight is Increasing',
                'message'    => 'Your active goal is <strong>Cutting (Fat Loss)</strong>, but your weight has increased by <strong>+' . $netChVal . ' kg</strong> since starting. For cutting, your weight should be trending downwards. Consider reviewing your caloric deficit or consulting your fitness coach.',
                'bg'         => '#fef2f2',
                'border'     => '#fca5a5',
                'textColor'  => '#991b1b',
                'icon'       => 'bi-exclamation-triangle-fill text-danger',
            ];
        } else {
            // Plateau
            $goalAlignment = [
                'status'     => 'plateau',
                'badge'      => '⚖️ Weight Plateau',
                'badgeClass' => 'bg-warning text-dark',
                'title'      => 'Cutting Notice: Weight is Stable / Plateaued',
                'message'    => 'Your weight has remained unchanged (<strong>' . ($netChVal >= 0 ? '+' : '') . $netChVal . ' kg</strong>). To continue cutting fat, ensure you are in a steady caloric deficit.',
                'bg'         => '#fffbeb',
                'border'     => '#fde68a',
                'textColor'  => '#92400e',
                'icon'       => 'bi-dash-circle-fill text-warning',
            ];
        }
    } elseif ($fGoal === 'bulking') {
        if ($netChVal > 0.1) {
            // Increasing weight during bulking -> ON TRACK
            $goalAlignment = [
                'status'     => 'on_track',
                'badge'      => '✅ On Track',
                'badgeClass' => 'bg-success text-white',
                'title'      => 'Bulking Phase: On Track (Weight Increasing)',
                'message'    => 'Great progress! Your weight is up by <strong>+' . $netChVal . ' kg</strong> since your starting log, aligning with your Muscle Mass (Bulking) goal.',
                'bg'         => '#f0fdf4',
                'border'     => '#86efac',
                'textColor'  => '#166534',
                'icon'       => 'bi-check-circle-fill text-success',
            ];
        } elseif ($netChVal < -0.1) {
            // Decreasing weight during bulking -> WARNING / MISALIGNED
            $goalAlignment = [
                'status'     => 'warning',
                'badge'      => '⚠️ Off Track / Warning',
                'badgeClass' => 'bg-danger text-white',
                'title'      => 'Goal Misalignment Warning: Weight is Decreasing',
                'message'    => 'Your active goal is <strong>Bulking (Mass Gain)</strong>, but your weight has decreased by <strong>' . $netChVal . ' kg</strong> since starting. For bulking, your weight should be trending upwards. Consider increasing your daily caloric surplus and protein intake.',
                'bg'         => '#fef2f2',
                'border'     => '#fca5a5',
                'textColor'  => '#991b1b',
                'icon'       => 'bi-exclamation-triangle-fill text-danger',
            ];
        } else {
            // Plateau / No gain
            $goalAlignment = [
                'status'     => 'plateau',
                'badge'      => '⚖️ Weight Steady',
                'badgeClass' => 'bg-warning text-dark',
                'title'      => 'Bulking Notice: Weight Unchanged',
                'message'    => 'Your weight has remained unchanged. To gain mass effectively, ensure you maintain a caloric surplus combined with progressive resistance training.',
                'bg'         => '#fffbeb',
                'border'     => '#fde68a',
                'textColor'  => '#92400e',
                'icon'       => 'bi-dash-circle-fill text-warning',
            ];
        }
    } elseif ($fGoal === 'maintaining') {
        if (abs($netChVal) <= 1.0) {
            // Within ±1 kg maintenance threshold -> ON TRACK
            $goalAlignment = [
                'status'     => 'on_track',
                'badge'      => '✅ On Track',
                'badgeClass' => 'bg-primary text-white',
                'title'      => 'Maintenance Phase: On Track (Weight Stable)',
                'message'    => 'Excellent balance! Your weight fluctuation (<strong>' . ($netChVal >= 0 ? '+' : '') . $netChVal . ' kg</strong>) is within the optimal ±1.0 kg maintenance window.',
                'bg'         => '#eff6ff',
                'border'     => '#bfdbfe',
                'textColor'  => '#1e40af',
                'icon'       => 'bi-check-circle-fill text-primary',
            ];
        } else {
            // Drifted outside maintenance
            $driftText = $netChVal > 0 ? '+' . $netChVal . ' kg (trending up)' : $netChVal . ' kg (trending down)';
            $goalAlignment = [
                'status'     => 'warning',
                'badge'      => '⚠️ Maintenance Drift',
                'badgeClass' => 'bg-warning text-dark',
                'title'      => 'Maintenance Notice: Weight Drifting Out of Range',
                'message'    => 'Your weight has shifted by <strong>' . $driftText . '</strong> beyond the ±1.0 kg maintenance threshold. Monitor your daily caloric balance to stay consistent.',
                'bg'         => '#fffbeb',
                'border'     => '#fde68a',
                'textColor'  => '#92400e',
                'icon'       => 'bi-exclamation-circle-fill text-warning',
            ];
        }
    }
}
?>

<!-- ── Dedicated 2-Column Layout ── -->
<div class="prg-page-grid">

    <!-- ── Left Column: Progress Status Card ── -->
    <div class="prg-col-left">
        <div class="fit-card p-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:36px;height:36px;border-radius:10px;background:rgba(13,148,136,0.1);display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-speedometer2" style="color:var(--accent-teal);font-size:1rem;"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-bold" style="color:var(--text-primary);font-size:15px;letter-spacing:0.3px;">Progress Status</h6>
                        <small style="color:var(--text-secondary);font-size:11px;">Current Weight &amp; Active Goal</small>
                    </div>
                </div>
                <?php if ($cScore > 0): ?>
                <span style="background:rgba(13,148,136,0.1);border:1px solid rgba(13,148,136,0.25);color:var(--accent-teal);border-radius:20px;padding:3px 12px;font-size:11px;font-weight:700;">
                    ⚡ Consistency: <?= $cScore ?> pts
                </span>
                <?php endif; ?>
            </div>

            <!-- Current Weight Display / Inline Edit -->
            <div class="mb-3">
                <?php if ($hasWeight): ?>
                <div class="d-flex align-items-end gap-2 mb-1">
                    <span style="color:var(--text-primary);font-size:2.4rem;line-height:1;font-weight:800;letter-spacing:-0.5px;">
                        <?= number_format((float)$latestW['weight_kg'], 1) ?>
                    </span>
                    <span style="color:var(--text-secondary);font-size:13px;" class="mb-1">kg current weight</span>
                    <button class="btn btn-link btn-sm ms-auto p-0" style="color:var(--text-secondary);font-size:14px;"
                            onclick="prgOpenWeightEdit()" title="Edit weight">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                </div>
                <?php if ($netChLbl): ?>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <small class="fw-semibold" style="color:<?= $netChVal > 0 ? '#16a34a' : ($netChVal < 0 ? '#dc2626' : '#64748b') ?>">
                        <?= htmlspecialchars($netChLbl) ?>
                    </small>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div id="prgFirstWeightWrap">
                    <p style="color:var(--text-secondary);font-size:12px;" class="mb-2">
                        <i class="bi bi-info-circle me-1"></i>Enter your starting weight to kickstart your progress graph.
                    </p>
                    <div class="d-flex gap-2">
                        <input type="number" id="prgFirstWeightInput" step="0.1" min="20" max="500"
                                placeholder="e.g. 74.5"
                                class="form-control fit-input form-control-sm">
                        <span style="color:var(--text-secondary);font-size:13px;" class="align-self-center">kg</span>
                        <button class="btn btn-sm px-3 fw-semibold" id="prgSaveFirstWeightBtn"
                                onclick="prgSaveFirstWeight()"
                                style="background:var(--accent-teal);color:#fff;border-radius:8px;border:none;">
                            Save
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Inline edit weight form -->
                <div id="prgWeightEditWrap" class="mt-2" style="display:none;">
                    <div class="d-flex gap-2">
                        <input type="number" id="prgWeightEditInput" step="0.1" min="20" max="500"
                               value="<?= $hasWeight ? number_format((float)$latestW['weight_kg'],1) : '' ?>"
                               class="form-control fit-input form-control-sm">
                        <span style="color:var(--text-secondary);font-size:13px;" class="align-self-center">kg</span>
                        <button class="btn btn-sm px-3 fw-semibold" onclick="prgSaveEditedWeight()"
                                style="background:#16a34a;color:#fff;border:none;border-radius:8px;">Save</button>
                        <button class="btn btn-sm btn-link p-0" style="color:var(--text-secondary);" onclick="prgCloseWeightEdit()">Cancel</button>
                    </div>
                </div>
            </div>

            <hr style="border-color:var(--border-card);margin:16px 0;">

            <!-- Goal selector (bulking/cutting/maintaining) -->
            <div>
                <small style="color:var(--text-secondary);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:1.2px;" class="d-block mb-2">Goal Type Select</small>
                <div class="d-flex gap-2 mb-2">
                    <?php
                    $activeStyles = [
                        'bulking'     => 'border-color:#22c55e; background:#f0fdf4; color:#15803d;',
                        'cutting'     => 'border-color:#f97316; background:#fff7ed; color:#c2410c;',
                        'maintaining' => 'border-color:#3b82f6; background:#eff6ff; color:#1d4ed8;'
                    ];
                    $inactiveStyle = 'border-color:var(--border-card); background:#f8fafc; color:var(--text-secondary);';
                    ?>
                    <?php foreach (['bulking','cutting','maintaining'] as $g): ?>
                    <button type="button"
                            id="prgGoalBtn-<?= $g ?>"
                            onclick="prgSelectGoal('<?= $g ?>')"
                            class="flex-grow-1"
                            style="padding:10px 6px;border-radius:8px;font-size:12px;font-weight:700;border:1.5px solid;
                                   <?= $fGoal === $g ? ($activeStyles[$g] ?? '') : $inactiveStyle ?>
                                   cursor:pointer;transition:all 0.2s;text-align:center;">
                        <?= $goalIcons[$g] ?> <?= ucfirst($g) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php if ($fGoal): ?>
                <div class="mt-2 pt-2 border-top">
                    <div class="d-flex align-items-center justify-content-between">
                        <small style="color:var(--text-secondary);font-size:12px;">
                            🎯 Active Goal: <strong style="color:var(--text-primary);"><?= ucfirst($fGoal) ?></strong>
                        </small>
                        <?php if (!empty($goalAlignment['badge'])): ?>
                        <span class="badge <?= $goalAlignment['badgeClass'] ?>" style="font-size:10px; font-weight:700;">
                            <?= $goalAlignment['badge'] ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <small style="color:var(--text-secondary);font-size:11px;" class="d-block text-muted mt-1">
                        <?= $goalDescs[$fGoal] ?>
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Consistency Score & Streak Card ── -->
        <div class="fit-card p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <small style="color:var(--text-secondary);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">
                    <i class="bi bi-trophy-fill text-warning me-1"></i>Consistency Score & Streaks
                </small>
                <a href="index.php?r=fitness/status" class="btn btn-link btn-sm p-0 text-decoration-none" style="font-size:12px;color:var(--accent-teal);font-weight:600;">
                    View Plan &rarr;
                </a>
            </div>
            <div class="row g-2 text-center mt-1">
                <div class="col-4">
                    <div class="p-2 rounded bg-light border border-light">
                        <small class="text-muted d-block" style="font-size:10px;font-weight:700;text-transform:uppercase;">Score</small>
                        <span class="fs-4 fw-extrabold" style="color:var(--accent-teal);"><?= number_format((float)($consistencyScore ?? 0), 1) ?></span>
                        <small class="d-block text-muted" style="font-size:10px;">pts</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded bg-light border border-light">
                        <small class="text-muted d-block" style="font-size:10px;font-weight:700;text-transform:uppercase;">Streak</small>
                        <span class="fs-4 fw-extrabold text-danger">🔥 <?= (int)($currentStreak ?? 0) ?></span>
                        <small class="d-block text-muted" style="font-size:10px;">days</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 rounded bg-light border border-light">
                        <small class="text-muted d-block" style="font-size:10px;font-weight:700;text-transform:uppercase;">Logs</small>
                        <span class="fs-4 fw-extrabold text-dark"><?= (int)($totalLoggedDays ?? 0) ?></span>
                        <small class="d-block text-muted" style="font-size:10px;">days</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Right Column: Weight Progress & Analytics Card ── -->
    <div class="prg-col-right">
        <div class="fit-card">
            <div class="fit-card-header types-hd d-flex align-items-center justify-content-between">
                <h5 class="fit-heading mb-0">
                    <i class="bi bi-activity me-2" style="color:var(--text-primary)"></i>Weight Progress Trends &amp; Analytics
                </h5>
                <button type="button" class="btn btn-sm btn-outline-success fw-bold px-3 rounded-pill" data-bs-toggle="modal" data-bs-target="#logWeightModal">
                    <i class="bi bi-plus-circle me-1"></i>Log Entry
                </button>
            </div>
            <div class="p-4">
                
                <?php if (count($wHistory) >= 1): ?>
                <!-- Summary Stats Bar -->
                <div class="row g-3 mb-4 text-center">
                    <div class="col-4">
                        <div class="p-3 bg-light rounded border border-light">
                            <small class="text-muted d-block text-uppercase" style="font-size:11px;font-weight:700;letter-spacing:0.8px;">Starting Weight</small>
                            <span class="fs-5 fw-bold text-dark"><?= number_format((float)$firstW['weight_kg'], 1) ?> kg</span>
                            <div class="small text-muted" style="font-size:11px;"><?= date('M j, Y', strtotime($firstW['date_logged'])) ?></div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-light rounded border border-light">
                            <small class="text-muted d-block text-uppercase" style="font-size:11px;font-weight:700;letter-spacing:0.8px;">Current Weight</small>
                            <span class="fs-5 fw-bold" style="color:<?= $activeColor ?> !important;"><?= number_format((float)$latestW['weight_kg'], 1) ?> kg</span>
                            <div class="small text-muted" style="font-size:11px;"><?= date('M j, Y', strtotime($latestW['date_logged'])) ?></div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-light rounded border border-light">
                            <small class="text-muted d-block text-uppercase" style="font-size:11px;font-weight:700;letter-spacing:0.8px;">Net Change</small>
                            <?php if ($netChVal !== null): ?>
                            <span class="fs-5 fw-bold" style="color:<?= $netChVal > 0 ? '#10b981' : ($netChVal < 0 ? '#ef4444' : '#6b7280') ?>;">
                                <?= $netChVal > 0 ? '+' : '' ?><?= $netChVal ?> kg
                            </span>
                            <div class="mt-1">
                                <?php if (!empty($goalAlignment['badge'])): ?>
                                    <span class="badge <?= $goalAlignment['badgeClass'] ?>" style="font-size: 10px; font-weight: 700;">
                                        <?= $goalAlignment['badge'] ?>
                                    </span>
                                <?php else: ?>
                                    <div class="small text-muted" style="font-size:11px;">Fluctuation</div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <span class="fs-5 fw-bold text-muted">&mdash;</span>
                            <div class="small text-muted" style="font-size:11px;">Need more logs</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ── Goal Alignment Alert Banner ── -->
                <?php if (!empty($goalAlignment['title'])): ?>
                <div class="p-3 mb-4 rounded-3 d-flex align-items-start gap-3 shadow-sm" 
                     style="background: <?= $goalAlignment['bg'] ?>; border: 1.5px solid <?= $goalAlignment['border'] ?>; color: <?= $goalAlignment['textColor'] ?>;">
                    <i class="bi <?= $goalAlignment['icon'] ?> fs-4 mt-1 flex-shrink-0"></i>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-1">
                            <h6 class="fw-bold mb-0" style="font-size: 14px; color: <?= $goalAlignment['textColor'] ?>;">
                                <?= $goalAlignment['title'] ?>
                            </h6>
                            <span class="badge <?= $goalAlignment['badgeClass'] ?> px-2 py-1" style="font-size: 11px;">
                                <?= $goalAlignment['badge'] ?>
                            </span>
                        </div>
                        <div class="small" style="font-size: 13px; line-height: 1.5;">
                            <?= $goalAlignment['message'] ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Weight Line Graph Canvas -->
                <?php if (count($wHistory) >= 2): ?>
                <div class="prg-chart-wrap" style="position:relative;height:300px;width:100%;">
                    <canvas id="prgWeightChart"></canvas>
                </div>
                <?php else: ?>
                <div class="text-center py-5 border border-dashed rounded bg-light">
                    <i class="bi bi-info-circle display-4 text-muted"></i>
                    <h6 class="mt-3 fw-bold text-dark">Need more logs to render graph</h6>
                    <p class="text-muted small mb-0 px-3">Keep logging your weight weekly after workout completion to construct a trend line analysis.</p>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <!-- Empty state when no weight logs exist -->
                <div class="text-center py-5 bg-light rounded border border-dashed">
                    <i class="bi bi-graph-up display-2 text-muted"></i>
                    <h5 class="mt-3 fw-bold text-dark">No weight analytics yet</h5>
                    <p class="text-muted mb-0 px-3" style="max-width: 480px; margin: 0 auto; font-size:13px;">
                        Enter your starting weight in the Progress Status card on the left to activate your Weight Progress graph.
                    </p>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

</div>

<!-- ── Log Weight Entry Modal ── -->
<div class="modal fade" id="logWeightModal" tabindex="-1" aria-labelledby="logWeightModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow-lg">
      <div class="modal-header" style="background:var(--accent-teal);color:#fff;">
        <h5 class="modal-title fw-bold" id="logWeightModalLabel">
            <i class="bi bi-journal-plus me-2"></i>Log Weight Progress Entry
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="mb-3">
            <label for="lw_date_input" class="form-label fw-semibold text-secondary small">Date Logged</label>
            <input type="date" id="lw_date_input" class="form-control fit-input" value="<?= date('Y-m-d') ?>">
            <div class="form-text mt-1">Select the date for this weight measurement (defaults to today).</div>
        </div>
        <div class="mb-3">
            <label for="lw_weight_input" class="form-label fw-semibold text-secondary small">Body Weight (kg)</label>
            <div class="input-group">
                <input type="number" id="lw_weight_input" step="0.1" min="20" max="500" placeholder="e.g. 74.5"
                       value="<?= $hasWeight ? number_format((float)$latestW['weight_kg'], 1) : '' ?>"
                       class="form-control fit-input">
                <span class="input-group-text bg-light text-muted fw-bold">kg</span>
            </div>
        </div>
      </div>
      <div class="modal-footer bg-light border-0 p-3">
        <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-fit-primary" onclick="prgSubmitLogModal()">
            <i class="bi bi-check-circle-fill me-1"></i>Save Weight
        </button>
      </div>
    </div>
  </div>
</div>

<script>
window.PRG = {
    history:  <?= json_encode(array_values($wHistory)) ?>,
    goal:     <?= json_encode($fGoal) ?>,
    hasWeight:<?= json_encode($hasWeight) ?>
};
</script>

<!-- Load Chart.js line graph logic -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function() {
    'use strict';
    
    const PRG = window.PRG || { history: [], goal: null, hasWeight: false };

    /* ── Weight Chart Rendering ── */
    let prgChart = null;

    function prgInitChart() {
        const canvas = document.getElementById('prgWeightChart');
        if (!canvas || !PRG.history || PRG.history.length < 2) return;

        const labels = PRG.history.map(h => {
            const d = new Date(h.date_logged);
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        const weights = PRG.history.map(h => parseFloat(h.weight_kg));

        // Color and trend line calculation based on selected fitness goal:
        const firstW = weights[0];
        const latestW = weights[weights.length - 1];
        const netChange = latestW - firstW;

        let lineColor = '#3b82f6';
        let fillColor = 'rgba(59,130,246,0.12)';
        let pointColor = '#3b82f6';
        let datasetLabel = 'Weight (kg)';

        if (PRG.goal === 'bulking') {
            if (netChange > 0.1) {
                lineColor = '#10b981';
                fillColor = 'rgba(16,185,129,0.12)';
                pointColor = '#10b981';
                datasetLabel = 'Weight (kg) — ✅ On Track (Bulking)';
            } else if (netChange < -0.1) {
                lineColor = '#ef4444';
                fillColor = 'rgba(239,68,68,0.12)';
                pointColor = '#ef4444';
                datasetLabel = 'Weight (kg) — ⚠️ Off Track (Weight Decreasing)';
            } else {
                lineColor = '#f59e0b';
                fillColor = 'rgba(245,158,11,0.12)';
                pointColor = '#f59e0b';
                datasetLabel = 'Weight (kg) — ⚖️ Steady (Plateau)';
            }
        } else if (PRG.goal === 'cutting') {
            if (netChange < -0.1) {
                lineColor = '#10b981';
                fillColor = 'rgba(16,185,129,0.12)';
                pointColor = '#10b981';
                datasetLabel = 'Weight (kg) — ✅ On Track (Cutting)';
            } else if (netChange > 0.1) {
                lineColor = '#ef4444';
                fillColor = 'rgba(239,68,68,0.12)';
                pointColor = '#ef4444';
                datasetLabel = 'Weight (kg) — ⚠️ Off Track (Weight Increasing)';
            } else {
                lineColor = '#f59e0b';
                fillColor = 'rgba(245,158,11,0.12)';
                pointColor = '#f59e0b';
                datasetLabel = 'Weight (kg) — ⚖️ Steady (Plateau)';
            }
        } else if (PRG.goal === 'maintaining') {
            if (Math.abs(netChange) <= 1.0) {
                lineColor = '#0d9488';
                fillColor = 'rgba(13,148,136,0.12)';
                pointColor = '#0d9488';
                datasetLabel = 'Weight (kg) — ✅ On Track (Maintained)';
            } else {
                lineColor = '#f97316';
                fillColor = 'rgba(249,115,22,0.12)';
                pointColor = '#f97316';
                datasetLabel = 'Weight (kg) — ⚠️ Weight Drift Warning';
            }
        }

        if (prgChart) prgChart.destroy();

        prgChart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: datasetLabel,
                    data: weights,
                    borderColor: lineColor,
                    backgroundColor: fillColor,
                    pointBackgroundColor: pointColor,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        titleColor: '#9ca3af',
                        bodyColor: '#ffffff',
                        borderWidth: 1,
                        borderColor: '#374151',
                        callbacks: {
                            label: function(ctx) { return ` ${ctx.raw.toFixed(1)} kg`; }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b', font: { size: 11 } }
                    },
                    y: {
                        grid: { color: '#e2e8f0' },
                        ticks: { color: '#64748b', font: { size: 11 }, callback: v => v + ' kg' },
                        grace: '5%'
                    }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', prgInitChart);

    /* ── Weight saving and AJAX logic ── */
    window.prgSelectGoal = function(goal) {
        fetch('index.php?r=member/saveGoal', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ goal_type: goal })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                PRG.goal = goal;
                location.reload();
            }
        })
        .catch(console.error);
    };

    window.prgOpenWeightEdit = function() {
        document.getElementById('prgWeightEditWrap').style.display = 'block';
    };
    window.prgCloseWeightEdit = function() {
        document.getElementById('prgWeightEditWrap').style.display = 'none';
    };

    window.prgSaveEditedWeight = function() {
        const val = parseFloat(document.getElementById('prgWeightEditInput').value);
        if (!val || val <= 0) { alert('Please enter a valid weight.'); return; }
        prgSendWeight(val, null, null, () => location.reload());
    };

    window.prgSaveFirstWeight = function() {
        const val = parseFloat(document.getElementById('prgFirstWeightInput').value);
        if (!val || val <= 0) { alert('Please enter a valid weight.'); return; }
        const btn = document.getElementById('prgSaveFirstWeightBtn');
        btn.disabled = true;
        btn.textContent = 'Saving...';
        prgSendWeight(val, PRG.goal, null, () => location.reload());
    };

    window.prgSubmitLogModal = function() {
        const weightVal = parseFloat(document.getElementById('lw_weight_input').value);
        const dateVal = document.getElementById('lw_date_input').value;
        if (!weightVal || weightVal <= 0) { alert('Please enter a valid weight.'); return; }
        prgSendWeight(weightVal, PRG.goal, dateVal, () => location.reload());
    };

    function prgSendWeight(weightKg, goalType, dateLogged, onSuccess) {
        fetch('index.php?r=member/saveWeight', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ weight_kg: weightKg, goal_type: goalType, date_logged: dateLogged })
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                if (typeof onSuccess === 'function') onSuccess(d);
            } else {
                alert('Error: ' + (d.error || 'Could not save weight.'));
            }
        })
        .catch(err => { console.error(err); alert('Network error. Please try again.'); });
    }
})();
</script>
<?php require __DIR__ . '/../partials/footer.php'; ?>
