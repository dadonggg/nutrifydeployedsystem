<?php
declare(strict_types=1);
$pageTitle = 'Program Success & Feedback Analytics';
require __DIR__ . '/../partials/header.php';
?>

<style>
.analytics-hero-card {
    background: linear-gradient(135deg, #0a2f18 0%, #14532d 50%, #0d5f57 100%) !important;
    border-radius: 16px;
    color: #ffffff !important;
}
.hero-kpi-box {
    background: rgba(255, 255, 255, 0.12) !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    backdrop-filter: blur(8px);
}
.hero-kpi-label-yellow {
    color: #fef08a !important;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.6px;
    text-transform: uppercase;
}
.hero-kpi-label-cyan {
    color: #bae6fd !important;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.6px;
    text-transform: uppercase;
}
.hero-kpi-val-yellow {
    color: #facc15 !important;
    font-weight: 800;
    text-shadow: 0 2px 10px rgba(0,0,0,0.35);
}
.hero-kpi-val-cyan {
    color: #38bdf8 !important;
    font-weight: 800;
    text-shadow: 0 2px 10px rgba(0,0,0,0.35);
}
.hero-kpi-subtext {
    color: #f8fafc !important;
    font-weight: 600;
    font-size: 0.85rem;
}
.kpi-card {
    border-radius: 14px;
    transition: transform .2s ease, box-shadow .2s ease;
}
.kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 24px rgba(0,0,0,0.08) !important;
}
.feedback-badge-sent {
    background-color: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
    font-weight: 600;
}
.feedback-badge-pending {
    background-color: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
    font-weight: 600;
}
.email-modal-header {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #ffffff;
    border-top-left-radius: 14px;
    border-top-right-radius: 14px;
}
.weight-trajectory-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 0.85rem;
}
</style>

<div class="container-fluid py-3 px-2 px-md-3">
        <!-- Page Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h1 class="h3 fw-bold text-success mb-1">
                    <i class="bi bi-graph-up-arrow me-2"></i>Program Success &amp; Feedback Analytics
                </h1>
                <p class="text-muted small mb-0">
                    Track participant performance, monitor weight trajectories, and deliver asynchronous email-style coaching feedback.
                </p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-success btn-sm rounded-pill px-3 fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#researchVariablesCollapse">
                    <i class="bi bi-journal-text me-1"></i> View Variable Definitions
                </button>
                <div class="badge bg-success-subtle text-success border border-success p-2 px-3 rounded-pill fs-6 fw-semibold">
                    <i class="bi bi-shield-check me-1"></i> Active Coaching Hub
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Collapsible Research Variable Definition Reference -->
        <div class="collapse mb-4" id="researchVariablesCollapse">
            <div class="card border-0 shadow-sm" style="border-radius: 14px; background: #f8fafc; border: 1px solid #e2e8f0 !important;">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="bi bi-book-half text-success me-2"></i>Variable Definitions &amp; Research Model Mapping
                    </h6>
                    <span class="badge bg-secondary-subtle text-secondary small">Study Methodology</span>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0 bg-white">
                            <thead class="table-light">
                                <tr>
                                    <th class="fw-bold" style="width: 22%;">Source Variable</th>
                                    <th class="fw-bold" style="width: 28%;">Proposed System Variable</th>
                                    <th class="fw-bold">Description &amp; System Implementation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-semibold text-primary">Feedback Response Rate</td>
                                    <td><code>Trainer Feedback Response Rate (R_fb)</code></td>
                                    <td class="small text-muted">The percentage of client progress check-in touchpoints that received an official review and feedback note from the fitness trainer ($R_{fb} = \frac{N_{fb}}{N_{co}} \times 100\%$).</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-primary">Completed Feedback Instances</td>
                                    <td><code>Total Trainer Feedback Sent (N_fb)</code></td>
                                    <td class="small text-muted">The actual count of structured feedback messages, program evaluations, and routine adjustments submitted by trainers to participants.</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-primary">Total Coaching Opportunities</td>
                                    <td><code>Total Client Check-In Logs (N_co)</code></td>
                                    <td class="small text-muted">The total count of progress snapshots, weight check-ins, and performance touchpoints logged by enrolled fitness enthusiasts eligible for feedback.</td>
                                </tr>
                                <tr>
                                    <td class="fw-semibold text-success">Program Improvement Rate</td>
                                    <td><code>Overall Success Rate (I_r)</code></td>
                                    <td class="small text-muted">The percentage of enrolled participants who reached or surpassed their target benchmark threshold ($I_r = \frac{n_s}{N} \times 100\%$).</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance & Feedback Summary Hero Card -->
        <div class="card mb-4 border-0 shadow-sm text-white analytics-hero-card">
            <div class="card-body p-4">
                <div class="row align-items-center g-4">
                    <div class="col-lg-6">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-warning text-dark px-2 py-1 fw-bold">Performance &amp; Feedback Hub</span>
                            <span class="small" style="color: #e2e8f0; font-weight: 500;">Evaluated against target benchmark</span>
                        </div>
                        <h4 class="fw-bold mb-2 text-white" style="font-size: 1.45rem;">Program Outcome &amp; Feedback Tracking</h4>
                        <p class="mb-0 small" style="max-width: 580px; color: #f1f5f9; line-height: 1.6;">
                            Monitors member weight progression towards defined goals, assesses program success rates, and guarantees on-time trainer feedback delivery.
                        </p>
                    </div>
                    
                    <div class="col-lg-3 col-sm-6 text-center">
                        <div class="p-3 rounded-3 hero-kpi-box text-white h-100 shadow-sm">
                            <div class="hero-kpi-label-yellow">Program Success Rate</div>
                            <div class="display-6 my-1 hero-kpi-val-yellow"><?= number_format((float)$results['Ir'], 1) ?>%</div>
                            <div class="hero-kpi-subtext"><?= $results['ns'] ?> of <?= $results['N'] ?> Met Goal Target</div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-sm-6 text-center">
                        <div class="p-3 rounded-3 hero-kpi-box text-white h-100 shadow-sm">
                            <div class="hero-kpi-label-cyan">Feedback Response Rate</div>
                            <div class="display-6 my-1 hero-kpi-val-cyan"><?= number_format((float)$results['R_fb'], 1) ?>%</div>
                            <div class="hero-kpi-subtext"><?= $results['N_fb'] ?> / <?= $results['N_co'] ?> Check-Ins Reviewed</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="card mb-4 shadow-sm border-0" style="border-radius: 14px;">
            <div class="card-body p-3 p-md-4">
                <form method="GET" action="index.php" class="row g-3 align-items-end">
                    <input type="hidden" name="r" value="programanalytics/index">
                    
                    <div class="col-md-4">
                        <label class="form-label fw-semibold text-secondary small">Fitness Program</label>
                        <select name="program" class="form-select">
                            <option value="all" <?= ($programFilter === 'all') ? 'selected' : '' ?>>All Fitness Programs</option>
                            <option value="weight loss" <?= (stripos($programFilter, 'weight loss') !== false) ? 'selected' : '' ?>>Weight Loss &amp; Fat Burn</option>
                            <option value="muscle" <?= (stripos($programFilter, 'muscle') !== false) ? 'selected' : '' ?>>Muscle Gain &amp; Strength</option>
                            <option value="cardio" <?= (stripos($programFilter, 'cardio') !== false) ? 'selected' : '' ?>>Cardio &amp; Endurance</option>
                            <option value="general" <?= (stripos($programFilter, 'general') !== false) ? 'selected' : '' ?>>General Wellness</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-secondary small">Age Group</label>
                        <select name="age_group" class="form-select">
                            <option value="all" <?= ($ageGroupFilter === 'all') ? 'selected' : '' ?>>All Age Groups</option>
                            <option value="18-25" <?= ($ageGroupFilter === '18-25') ? 'selected' : '' ?>>18 – 25 years old</option>
                            <option value="26-35" <?= ($ageGroupFilter === '26-35') ? 'selected' : '' ?>>26 – 35 years old</option>
                            <option value="36-50" <?= ($ageGroupFilter === '36-50') ? 'selected' : '' ?>>36 – 50 years old</option>
                            <option value="51+" <?= ($ageGroupFilter === '51+') ? 'selected' : '' ?>>51+ years old</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold text-secondary small">Target Benchmark Threshold</label>
                        <div class="input-group">
                            <input type="number" step="0.5" min="1" max="100" name="threshold" class="form-control" value="<?= htmlspecialchars((string)$threshold) ?>">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>

                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-success fw-semibold py-2">
                            <i class="bi bi-funnel-fill me-1"></i> Apply Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 4 Summary KPI Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm text-center p-3 h-100 kpi-card">
                    <div class="text-muted small fw-semibold text-uppercase">Total Participants</div>
                    <div class="fs-2 fw-bold text-dark my-1"><?= $results['N'] ?></div>
                    <div class="small text-muted">Active Enrolled Members</div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm text-center p-3 h-100 kpi-card">
                    <div class="text-muted small fw-semibold text-uppercase">Goal Achievers</div>
                    <div class="fs-2 fw-bold text-success my-1"><?= $results['ns'] ?></div>
                    <div class="small text-muted">Progress &ge; <?= (float)$results['threshold'] ?>%</div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm text-center p-3 h-100 kpi-card">
                    <div class="text-muted small fw-semibold text-uppercase">Coaching Opportunities</div>
                    <div class="fs-2 fw-bold text-primary my-1"><?= $results['N_co'] ?></div>
                    <div class="small text-muted">Client Check-Ins Logged</div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm text-center p-3 h-100 kpi-card">
                    <div class="text-muted small fw-semibold text-uppercase">Feedback Completed</div>
                    <div class="fs-2 fw-bold text-info my-1"><?= $results['N_fb'] ?></div>
                    <div class="small text-muted">Trainer Reviews Sent</div>
                </div>
            </div>
        </div>

        <!-- Participant Progress & Feedback Matrix Table -->
        <div class="card shadow-sm border-0" style="border-radius: 14px; overflow: hidden;">
            <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="bi bi-people-fill me-2 text-success"></i>Participant Progress &amp; Coaching Matrix
                    </h5>
                    <span class="text-muted small">View client weight trajectory, program performance, and dispatch async feedback.</span>
                </div>
                <span class="badge bg-light text-dark border px-3 py-2">
                    Program: <?= htmlspecialchars($results['program_filter']) ?> | Age: <?= htmlspecialchars($results['age_group_filter']) ?>
                </span>
            </div>
            
            <div class="card-body p-0">
                <?php if (empty($results['participants'])): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted display-4"></i>
                        <p class="mt-2 text-secondary">No participants found matching the selected filter criteria.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary small text-uppercase">
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Participant</th>
                                    <th>Program</th>
                                    <th>Weight Trajectory</th>
                                    <th style="min-width: 170px;">Calculated Progress</th>
                                    <th>Goal Status</th>
                                    <th>Feedback Status</th>
                                    <th class="text-end" style="min-width: 150px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results['participants'] as $index => $p): ?>
                                    <tr>
                                        <td class="fw-bold text-muted"><?= $index + 1 ?></td>
                                        
                                        <!-- Participant Info -->
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px;">
                                                    <?= strtoupper(substr($p['fullname'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($p['fullname']) ?></div>
                                                    <div class="text-muted small"><?= $p['age'] ?> yrs old &bull; <?= $p['total_logs'] ?> logs</div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Program Name -->
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary px-2 py-1 fw-medium">
                                                <?= htmlspecialchars($p['program_name']) ?>
                                            </span>
                                        </td>

                                        <!-- Weight Trajectory -->
                                        <td>
                                            <div class="weight-trajectory-box">
                                                <div class="d-flex justify-content-between align-items-center gap-2">
                                                    <span>Start: <strong><?= $p['start_weight'] ?>kg</strong></span>
                                                    <i class="bi bi-arrow-right text-muted"></i>
                                                    <span>Now: <strong class="text-dark"><?= $p['current_weight'] ?>kg</strong></span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-1 small">
                                                    <?php if ($p['target_weight']): ?>
                                                        <span class="text-primary">Target: <?= $p['target_weight'] ?>kg</span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Target: &mdash;</span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($p['weight_change'] < 0): ?>
                                                        <span class="badge bg-success-subtle text-success"><?= $p['weight_change'] ?> kg</span>
                                                    <?php elseif ($p['weight_change'] > 0): ?>
                                                        <span class="badge bg-warning-subtle text-dark">+<?= $p['weight_change'] ?> kg</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-muted">0.0 kg</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Progress Bar -->
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 8px; border-radius: 4px;">
                                                    <div class="progress-bar <?= $p['is_successful'] ? 'bg-success' : 'bg-warning' ?>" 
                                                         role="progressbar" 
                                                         style="width: <?= min(100, (float)$p['progress']) ?>%"></div>
                                                </div>
                                                <span class="fw-bold small text-dark" style="min-width: 42px;"><?= number_format((float)$p['progress'], 1) ?>%</span>
                                            </div>
                                            <div class="text-muted small mt-1">Benchmark: &ge; <?= (float)$p['threshold'] ?>%</div>
                                        </td>

                                        <!-- Goal Status Badge -->
                                        <td>
                                            <?php if ($p['status'] === 'SUCCESSFUL'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success px-2 py-1 rounded-pill">
                                                    <i class="bi bi-check-circle-fill me-1"></i> ON TRACK
                                                </span>
                                            <?php elseif ($p['status'] === 'IN_PROGRESS'): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1 rounded-pill">
                                                    <i class="bi bi-hourglass-split me-1"></i> IN PROGRESS
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger px-2 py-1 rounded-pill">
                                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> BEHIND
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Feedback Status -->
                                        <td>
                                            <?php if ($p['feedback_count'] > 0): ?>
                                                <span class="badge feedback-badge-sent rounded-pill px-2 py-1" title="Last sent on <?= htmlspecialchars($p['latest_feedback']['created_at'] ?? '') ?>">
                                                    <i class="bi bi-chat-check-fill me-1"></i> Sent (<?= $p['feedback_count'] ?>)
                                                </span>
                                                <?php if (!empty($p['latest_feedback']['feedback_status'])): ?>
                                                    <div class="small text-muted mt-1 text-truncate" style="max-width: 140px;">
                                                        <?= htmlspecialchars(str_replace('_', ' ', $p['latest_feedback']['feedback_status'])) ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge feedback-badge-pending rounded-pill px-2 py-1">
                                                    <i class="bi bi-clock-history me-1"></i> Needs Review
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Actions -->
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" 
                                                        class="btn btn-outline-success fw-semibold"
                                                        onclick="openFeedbackModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)">
                                                    <i class="bi bi-send me-1"></i> Feedback
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-outline-secondary"
                                                        title="View History & Logs"
                                                        onclick="viewMemberHistory(<?= (int)$p['member_id'] ?>, '<?= htmlspecialchars(addslashes($p['fullname'])) ?>')">
                                                    <i class="bi bi-clock-history"></i>
                                                </button>
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
    </div>
</div>

<!-- ==========================================
     EMAIL-STYLE FEEDBACK DISPATCH MODAL
=========================================== -->
<div class="modal fade" id="emailFeedbackModal" tabindex="-1" aria-labelledby="emailFeedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header email-modal-header px-4 py-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-success text-white p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-envelope-paper-heart fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="emailFeedbackModalLabel">Compose Coaching Feedback</h5>
                        <small class="text-light opacity-75">Send personalized advice, adjustments, or praise directly to the client</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form method="POST" action="index.php?r=programanalytics/sendFeedback">
                <input type="hidden" name="member_id" id="modalMemberId">

                <div class="modal-body p-4">
                    <!-- Recipient & Progress Info Banner -->
                    <div class="card bg-light border-0 p-3 mb-3" style="border-radius: 10px;">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-6">
                                <div class="small text-muted">Recipient (Client)</div>
                                <div class="fw-bold text-dark fs-6" id="modalClientName">Client Name</div>
                                <div class="small text-muted" id="modalProgramName">Program Name</div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="small text-muted">Current Weight Progress</div>
                                <div class="fw-semibold text-success" id="modalWeightProgress">70.0 kg &rarr; 68.0 kg</div>
                                <div class="small text-muted" id="modalCalculatedScore">Progress Score: 75.0%</div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Evaluation Status -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Evaluation Status <span class="text-danger">*</span></label>
                        <select name="feedback_status" id="modalFeedbackStatus" class="form-select" required>
                            <option value="on_track">🎯 On Track &mdash; Client is making solid progress towards goal</option>
                            <option value="plateau">⚠️ Plateau Detected &mdash; Weight or progress has stalled, needs routine push</option>
                            <option value="needs_adjustment">🔄 Routine Adjustment Needed &mdash; Modify workout sets or calories</option>
                            <option value="goal_achieved">🏆 Goal Milestone Reached &mdash; Target achieved, transition to next phase</option>
                        </select>
                    </div>

                    <!-- Subject -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Email / Review Subject <span class="text-danger">*</span></label>
                        <input type="text" name="feedback_subject" id="modalSubject" class="form-control" placeholder="e.g., Progress Review: Great consistency, let's adjust your reps" required>
                    </div>

                    <!-- Feedback Message -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-secondary small">Coaching Feedback &amp; Evaluation <span class="text-danger">*</span></label>
                        <textarea name="feedback_text" id="modalFeedbackText" class="form-control" rows="4" placeholder="Write your assessment of their recent logs, adherence, workout intensity, and weight trend..." required></textarea>
                    </div>

                    <!-- Additional Guidance (Row) -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Suggested Next Steps / Program Tweaks</label>
                            <textarea name="next_steps" class="form-control form-control-sm" rows="2" placeholder="e.g., Increase cardio by 10 mins; add 2 sets on Bench Press"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary small">Motivational Encouragement</label>
                            <textarea name="encouragement" class="form-control form-control-sm" rows="2" placeholder="e.g., You are doing fantastic! Keep up the momentum for next week!"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light px-4 py-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-semibold px-4">
                        <i class="bi bi-send-check-fill me-1"></i> Send Feedback to Client
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================
     CLIENT HISTORY & LOGS MODAL
=========================================== -->
<div class="modal fade" id="clientHistoryModal" tabindex="-1" aria-labelledby="clientHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 14px; overflow: hidden;">
            <div class="modal-header bg-dark text-white px-4 py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-success fs-5"></i>
                    <h5 class="modal-title fw-bold mb-0" id="historyModalClientName">Client Log &amp; Feedback History</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="historyModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-success" role="status"></div>
                    <p class="mt-2 text-muted small">Loading log history...</p>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function openFeedbackModal(participant) {
    document.getElementById('modalMemberId').value = participant.member_id;
    document.getElementById('modalClientName').textContent = participant.fullname + ' (' + participant.age + ' yrs old)';
    document.getElementById('modalProgramName').textContent = participant.program_name;
    
    let targetStr = participant.target_weight ? ` (Target: ${participant.target_weight}kg)` : '';
    document.getElementById('modalWeightProgress').textContent = `Start: ${participant.start_weight}kg → Current: ${participant.current_weight}kg${targetStr}`;
    document.getElementById('modalCalculatedScore').textContent = `Progress Score: ${participant.progress}% (${participant.status})`;

    // Smart default subject & status based on progress
    let statusSelect = document.getElementById('modalFeedbackStatus');
    let subjectInput = document.getElementById('modalSubject');
    let feedbackText = document.getElementById('modalFeedbackText');

    if (participant.status === 'SUCCESSFUL') {
        statusSelect.value = 'on_track';
        subjectInput.value = `[Progress Review] Outstanding Progress on your ${participant.program_name}!`;
        feedbackText.value = `Hi ${participant.fullname},\n\nI've reviewed your recent weight logs and workout consistency. You're currently at ${participant.current_weight}kg and hitting your target milestones. Excellent work! Keep pushing your sets with high intensity.`;
    } else if (participant.status === 'IN_PROGRESS') {
        statusSelect.value = 'on_track';
        subjectInput.value = `[Progress Review] Weekly Check-in & Program Tips`;
        feedbackText.value = `Hi ${participant.fullname},\n\nGood effort on logging your check-ins. Your current weight is ${participant.current_weight}kg. To accelerate progress towards your goal, make sure you stay consistent with your scheduled workout days and hydration.`;
    } else {
        statusSelect.value = 'plateau';
        subjectInput.value = `[Progress Review] Let's get your progress back on track!`;
        feedbackText.value = `Hi ${participant.fullname},\n\nI noticed your progress has slowed down recently. Let's make a few adjustments to your program routine and nutrition to break through this plateau.`;
    }

    let modal = new bootstrap.Modal(document.getElementById('emailFeedbackModal'));
    modal.show();
}

function viewMemberHistory(memberId, memberName) {
    document.getElementById('historyModalClientName').textContent = `${memberName} — Log & Feedback History`;
    let modalBody = document.getElementById('historyModalBody');
    modalBody.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-success" role="status"></div>
            <p class="mt-2 text-muted small">Fetching client logs & past reviews...</p>
        </div>
    `;

    let modal = new bootstrap.Modal(document.getElementById('clientHistoryModal'));
    modal.show();

    fetch(`index.php?r=programanalytics/getMemberHistory&member_id=${memberId}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                modalBody.innerHTML = `<div class="alert alert-danger">Could not load client history.</div>`;
                return;
            }

            let weightLogsHtml = '';
            if (data.weight_logs && data.weight_logs.length > 0) {
                weightLogsHtml = `
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-speedometer2 text-success me-1"></i> Recent Weight Logs</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light small">
                                <tr><th>Date Logged</th><th>Weight</th><th>Goal Strategy</th></tr>
                            </thead>
                            <tbody>
                                ${data.weight_logs.map(log => `
                                    <tr>
                                        <td>${log.date_logged}</td>
                                        <td class="fw-bold">${log.weight_kg} kg</td>
                                        <td><span class="badge bg-light text-dark">${log.goal_type || 'General'}</span></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            } else {
                weightLogsHtml = `<p class="text-muted small mb-4">No individual weight logs recorded yet.</p>`;
            }

            let feedbackHtml = '';
            if (data.feedback_history && data.feedback_history.length > 0) {
                feedbackHtml = `
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-chat-left-dots text-primary me-1"></i> Past Trainer Feedback</h6>
                    <div class="list-group">
                        ${data.feedback_history.map(fb => `
                            <div class="list-group-item list-group-item-action p-3 mb-2 rounded border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold mb-0 text-dark">${fb.feedback_subject || 'Feedback Review'}</h6>
                                    <small class="text-muted">${fb.created_at}</small>
                                </div>
                                <div class="badge bg-secondary-subtle text-secondary mb-2">${fb.feedback_status || 'on_track'}</div>
                                <p class="mb-2 text-secondary small">${fb.feedback_text}</p>
                                ${fb.next_steps ? `<div class="small text-primary"><strong>Next Steps:</strong> ${fb.next_steps}</div>` : ''}
                                ${fb.encouragement ? `<div class="small text-success"><strong>Encouragement:</strong> ${fb.encouragement}</div>` : ''}
                            </div>
                        `).join('')}
                    </div>
                `;
            } else {
                feedbackHtml = `<p class="text-muted small">No previous feedback submitted for this member yet.</p>`;
            }

            modalBody.innerHTML = weightLogsHtml + feedbackHtml;
        })
        .catch(err => {
            modalBody.innerHTML = `<div class="alert alert-danger">Error loading history: ${err.message}</div>`;
        });
}
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
