<?php
declare(strict_types=1);
$pageTitle = 'Member Dashboard';
require __DIR__ . '/../partials/header.php';
?>

<style>
.text-purple { color: #7c3aed !important; }
.border-purple { border-color: #7c3aed !important; }
.btn-outline-purple { border-color: #7c3aed !important; color: #7c3aed !important; background: transparent; }
.btn-outline-purple:hover { background-color: #7c3aed !important; color: #fff !important; }
.carousel-control-prev-icon, .carousel-control-next-icon {
    background-color: rgba(0, 0, 0, 0.5);
    border-radius: 50%;
    padding: 10px;
}
/* Interest Buttons */
.interest-btn {
    font-size: 0.82rem;
    font-weight: 500;
    border-radius: 20px;
    padding: 5px 14px;
    transition: all 0.2s ease;
    cursor: pointer;
    border: 1.5px solid;
}
.interest-btn.interested {
    background: #16a34a;
    color: #fff;
    border-color: #16a34a;
}
.interest-btn.interested.selected {
    background: #15803d;
    border-color: #15803d;
    box-shadow: 0 0 0 3px rgba(22,163,74,0.2);
}
.interest-btn.not-interested {
    background: transparent;
    color: #6b7280;
    border-color: #d1d5db;
}
.interest-btn.not-interested.selected {
    background: #f3f4f6;
    border-color: #9ca3af;
    color: #374151;
    box-shadow: 0 0 0 3px rgba(156,163,175,0.2);
}
.interest-btn.selected {
    transform: scale(1.04);
}
/* Coaching card collapse */
.coaching-collapse-toggle {
    cursor: pointer;
    background: none;
    border: none;
    color: #64748b;
    font-size: 1rem;
    padding: 2px 6px;
    border-radius: 4px;
    transition: color 0.15s;
}
.coaching-collapse-toggle:hover { color: #1e293b; }
.coaching-card-body {
    transition: max-height 0.35s cubic-bezier(0.4,0,0.2,1), opacity 0.25s ease;
    overflow: hidden;
    max-height: 2000px;
    opacity: 1;
}
.coaching-card-body.collapsed {
    max-height: 0;
    opacity: 0;
    padding: 0 !important;
}
</style>

<?php

$displayName = trim(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
if ($displayName === '') $displayName = $user['fullname'] ?? 'Member';

// Calculate membership status
$isExpired = false;
$daysUntilExpiry = null;
if ($member['expiration_date']) {
    $expiryDate = new DateTime($member['expiration_date']);
    $today = new DateTime();
    $diff = $today->diff($expiryDate);
    
    if ($expiryDate < $today) {
        $isExpired = true;
        $daysUntilExpiry = -$diff->days;
    } else {
        $daysUntilExpiry = $diff->days;
    }
}

$membershipStatusColor = $isExpired ? 'danger' : ($daysUntilExpiry <= 7 ? 'warning' : 'success');
?>

<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-1">Welcome back, <?= htmlspecialchars($displayName) ?>!</h1>
            <p class="text-muted mb-0">
                Member since <?= date('M Y', strtotime($member['start_date'] ?? $member['created_at'])) ?>
                • Code: <strong><?= htmlspecialchars($member['membership_code']) ?></strong>
            </p>
        </div>
        <div class="text-end">
            <span class="badge bg-<?= $membershipStatusColor ?> fs-6">
                <?= $isExpired ? 'Expired' : 'Active' ?>
            </span>
            <?php if ($daysUntilExpiry !== null): ?>
            <div class="small text-muted mt-1">
                <?= $isExpired ? "Expired $daysUntilExpiry days ago" : "$daysUntilExpiry days remaining" ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-activity text-primary fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="text-muted small">Total Workouts</div>
                        <div class="fs-4 fw-bold text-primary"><?= (int)($workoutStats['total_sessions'] ?? 0) ?></div>
                        <div class="small text-muted">
                            <?= (int)($workoutStats['total_minutes'] ?? 0) ?> minutes total
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-calendar-check text-success fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="text-muted small">Gym Visits</div>
                        <div class="fs-4 fw-bold text-success"><?= $thisMonthVisits ?></div>
                        <div class="small text-muted">
                            This month • <?= $attendanceStreak ?> day streak
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-target text-warning fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="text-muted small">Active Goals</div>
                        <div class="fs-4 fw-bold text-warning"><?= (int)($goalStats['active'] ?? 0) ?></div>
                        <div class="small text-muted">
                            <?= (int)($goalStats['completed'] ?? 0) ?> completed
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notifications & Announcements -->
<?php if ($unreadCount > 0): ?>
<div class="alert alert-info d-flex align-items-center mb-4">
    <i class="bi bi-bell-fill me-2"></i>
    <div class="flex-grow-1">
        <strong>You have <?= $unreadCount ?> unread announcement<?= $unreadCount > 1 ? 's' : '' ?></strong>
        from your gym.
    </div>
    <a href="index.php?r=member/announcements" class="btn btn-sm btn-outline-info">View All</a>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Left Column -->
    <div class="col-lg-8">

        <!-- Live Enrollment Campaign (Builder) -->
        <div id="cbCampaignContainer" class="mb-4"></div>

        <!-- Featured Campaigns -->
        <?php if (!empty($activeCampaigns)): ?>
        <div class="card mb-4 border-0 shadow-sm overflow-hidden" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 d-flex align-items-center">
                <i class="bi bi-megaphone-fill text-purple me-2 fs-5"></i>
                <h5 class="card-title mb-0 fw-bold">Featured Updates & Events</h5>
            </div>
            <div class="card-body p-0">
                <div id="campaignCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($activeCampaigns as $index => $c): ?>
                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                <?php if (!empty($c['image_path'])): ?>
                                    <img src="public/<?= htmlspecialchars($c['image_path']) ?>" class="d-block w-100" style="max-height: 280px; object-fit: cover;" alt="<?= htmlspecialchars($c['title']) ?>">
                                <?php endif; ?>
                                <div class="p-4 <?= empty($c['image_path']) ? 'bg-light' : '' ?>">
                                    <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($c['title']) ?></h5>
                                    <p class="text-muted mb-0 small"><?= nl2br(htmlspecialchars($c['description'] ?? '')) ?></p>
                                    <div class="mt-3 text-muted small">
                                        <i class="bi bi-calendar-event me-1"></i> Valid: <?= htmlspecialchars($c['start_date']) ?> to <?= htmlspecialchars($c['end_date']) ?>
                                    </div>
                                    <?php
                                    $myInterest = $campaignInterestMap[(int)$c['id']] ?? null;
                                    ?>
                                    <div class="mt-3 d-flex gap-2 campaign-interest-btns" data-campaign-id="<?= (int)$c['id'] ?>">
                                        <button type="button"
                                            class="interest-btn interested <?= $myInterest === 'interested' ? 'selected' : '' ?>"
                                            onclick="saveInterest('campaign', <?= (int)$c['id'] ?>, 'interested', this)">
                                            <?= $myInterest === 'interested' ? '✓ ' : '✅ ' ?>I'm Interested
                                        </button>
                                        <button type="button"
                                            class="interest-btn not-interested <?= $myInterest === 'not_interested' ? 'selected' : '' ?>"
                                            onclick="saveInterest('campaign', <?= (int)$c['id'] ?>, 'not_interested', this)">
                                            <?= $myInterest === 'not_interested' ? '✓ ' : '❌ ' ?>Not Interested
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($activeCampaigns) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#campaignCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#campaignCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- My Personal Trainer & Plans -->
        <div id="coachingCard" class="card mb-4 border-0 shadow-sm overflow-hidden" style="border-radius: 14px; border: 1px solid #e2e8f0 !important;">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-dark" style="font-size: 1.05rem;">
                    <i class="bi bi-person-arms-up me-2 text-success"></i>Personal Coaching &amp; Plans
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($activeFitnessRequest): ?>
                    <span class="badge bg-<?= $activeFitnessRequest['status'] === 'assigned' ? 'success' : 'warning' ?>-subtle text-<?= $activeFitnessRequest['status'] === 'assigned' ? 'success' : 'warning-emphasis' ?> border border-<?= $activeFitnessRequest['status'] === 'assigned' ? 'success' : 'warning' ?>-subtle px-2 py-1 rounded-pill fw-semibold">
                        <?= ucfirst($activeFitnessRequest['status']) ?>
                    </span>
                    <?php endif; ?>
                    <button class="coaching-collapse-toggle" id="coachingToggleBtn" title="Toggle coaching card" aria-label="Toggle">
                        <i class="bi bi-chevron-up" id="coachingToggleIcon"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-4 coaching-card-body" id="coachingCardBody">
                <?php if (!$activeFitnessRequest): ?>
                    <!-- No Request Yet -->
                    <div class="text-center py-4">
                        <div class="mb-3">
                            <div class="rounded-circle bg-success-subtle text-success p-3 d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                <i class="bi bi-heart-pulse text-success fs-2"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">Unlock Your Fitness Potential</h5>
                        <p class="text-muted small mx-auto mb-3" style="max-width: 480px;">
                            Get a fully customized workout and nutrition plan created by our expert fitness coaches. 
                            Track your consistency and get direct professional feedback.
                        </p>
                        <a href="index.php?r=fitness/request" class="btn btn-success btn-sm px-4 fw-semibold shadow-sm" style="border-radius: 20px;">
                            <i class="bi bi-plus-circle me-1"></i> Request Training Now
                        </a>
                    </div>
                <?php elseif ($activeFitnessRequest['status'] === 'pending'): ?>
                    <!-- Request Pending -->
                    <div class="d-flex align-items-center p-3 rounded-3" style="background: #fffbeb; border: 1px solid #fef3c7;">
                        <div class="bg-warning bg-opacity-25 p-3 rounded-circle me-3 text-warning-emphasis">
                            <i class="bi bi-hourglass-split fs-3"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-bold text-dark">Coaching Request Submitted</h6>
                            <p class="text-secondary small mb-0">
                                Your training request is received and currently awaiting trainer assignment by our Administrative Officer.
                            </p>
                        </div>
                    </div>
                <?php elseif ($activeFitnessRequest['status'] === 'assigned'): ?>
                    <!-- Trainer Assigned -->
                    <div class="row g-4 align-items-center">
                        <div class="col-md-5 border-end border-light-subtle pe-md-4">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center fw-bold fs-4 me-3 flex-shrink-0" style="width: 52px; height: 52px; border: 2px solid #bbf7d0;">
                                    <?= strtoupper(substr($activeFitnessRequest['trainer_name'] ?? 'C', 0, 1)) ?>
                                </div>
                                <div class="overflow-hidden">
                                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.68rem; letter-spacing: 0.05em;">Assigned Coach</small>
                                    <h6 class="mb-0 fw-bold text-dark text-truncate"><?= htmlspecialchars($activeFitnessRequest['trainer_name'] ?? 'Your Fitness Coach') ?></h6>
                                    <?php if (!empty($activeFitnessRequest['trainer_specialization'])): ?>
                                    <small class="text-muted text-truncate d-block"><?= htmlspecialchars($activeFitnessRequest['trainer_specialization']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="index.php?r=fitness/status" class="btn btn-outline-secondary btn-sm text-start fw-medium">
                                    <i class="bi bi-clipboard-check me-2 text-success"></i>My Training Overview
                                </a>
                                <a href="index.php?r=fitness/progress&request_id=<?= $activeFitnessRequest['id'] ?>" class="btn btn-outline-success btn-sm text-start fw-medium">
                                    <i class="bi bi-graph-up me-2"></i>Track Daily Progress
                                </a>
                            </div>
                        </div>
                        
                        <div class="col-md-7 ps-md-4">
                            <?php if (!$activePlan || $activePlan['status'] === 'draft'): ?>
                                <!-- Plan Draft or Empty -->
                                <div class="text-center py-3">
                                    <i class="bi bi-journal-text text-muted fs-3 mb-2 d-block"></i>
                                    <h6 class="fw-bold text-dark">Plan in Preparation</h6>
                                    <p class="text-muted small mb-0">
                                        Your coach is currently tailoring your workout and nutrition schedules. Check back soon!
                                    </p>
                                </div>
                            <?php else: ?>
                                <!-- Active Plan Details -->
                                <h6 class="fw-bold text-dark mb-3" style="font-size: 0.95rem;"><i class="bi bi-card-checklist text-success me-2"></i>Personalized Fitness &amp; Diet Plan</h6>
                                
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <div class="p-3 rounded-3 text-center" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                            <small class="text-muted d-block fw-semibold mb-1" style="font-size: 0.72rem; text-transform: uppercase;">Target Calories</small>
                                            <strong class="text-success fs-5"><?= $activePlan['target_calories'] ? $activePlan['target_calories'] . ' kcal' : '—' ?></strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 rounded-3 text-center" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                            <small class="text-muted d-block fw-semibold mb-1" style="font-size: 0.72rem; text-transform: uppercase;">Weekly Sessions</small>
                                            <strong class="text-success fs-5"><?= $activePlan['recommended_sessions_per_week'] ?? '—' ?> sessions</strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <a href="index.php?r=fitness/plan&request_id=<?= $activeFitnessRequest['id'] ?>" class="btn btn-success btn-sm flex-grow-1 fw-semibold shadow-sm">
                                        <i class="bi bi-file-text me-1"></i> View Workouts
                                    </a>
                                    <a href="index.php?r=fitness/plan&request_id=<?= $activeFitnessRequest['id'] ?>#nutrition-section" class="btn btn-outline-primary btn-sm flex-grow-1 fw-semibold">
                                        <i class="bi bi-egg-fried me-1"></i> View Diet
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Active Goals -->
        <?php if (!empty($activeGoals)): ?>
        <div class="card mb-4 border-0 shadow-sm" style="border-radius: 14px; border: 1px solid #e2e8f0 !important;">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-dark" style="font-size: 1.05rem;">
                    <i class="bi bi-target me-2 text-warning"></i>Active Goals
                </h5>
                <a href="index.php?r=member/goals" class="btn btn-sm btn-outline-primary fw-medium px-3 rounded-pill">View All</a>
            </div>
            <div class="card-body p-4">
                <?php foreach (array_slice($activeGoals, 0, 3) as $goalData): 
                    $goal = isset($goalData['goal']) && is_array($goalData['goal']) ? $goalData['goal'] : $goalData;
                    $progress = (float)($goalData['progress_percentage'] ?? 0);
                    $isOverdue = !empty($goalData['is_overdue']);
                ?>
                <div class="mb-3 <?= $isOverdue ? 'border-start border-danger border-3 ps-3' : '' ?>">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-1 fw-bold text-dark"><?= htmlspecialchars((string)($goal['title'] ?? 'Goal')) ?></h6>
                            <small class="text-muted">
                                <?= ucfirst(str_replace('_', ' ', (string)($goal['goal_type'] ?? ''))) ?>
                                <?php if (!empty($goal['target_date'])): ?>
                                • Target: <?= date('M j, Y', strtotime((string)$goal['target_date'])) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        <span class="badge bg-<?= $isOverdue ? 'danger' : 'primary' ?>-subtle text-<?= $isOverdue ? 'danger' : 'primary' ?> border border-<?= $isOverdue ? 'danger' : 'primary' ?>-subtle rounded-pill px-2 py-1">
                            <?= number_format($progress, 1) ?>%
                        </span>
                    </div>
                    <div class="progress" style="height: 6px; border-radius: 4px;">
                        <div class="progress-bar bg-<?= $isOverdue ? 'danger' : 'primary' ?>" 
                             style="width: <?= min(100, (int)$progress) ?>%"></div>
                    </div>
                    <?php if (!empty($goal['target_value'])): ?>
                    <small class="text-muted d-block mt-1">
                        <?= number_format((float)($goal['current_value'] ?? 0), 1) ?> / <?= number_format((float)$goal['target_value'], 1) ?> <?= htmlspecialchars((string)($goal['target_unit'] ?? '')) ?>
                    </small>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Announcements -->
        <?php if (!empty($announcements)): ?>
        <div class="card mb-4 border-0 shadow-sm" style="border-radius: 14px; border: 1px solid #e2e8f0 !important;">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-dark" style="font-size: 1.05rem;">
                    <i class="bi bi-megaphone me-2 text-primary"></i>Gym Announcements
                </h5>
                <a href="index.php?r=member/announcements" class="btn btn-sm btn-outline-primary fw-medium px-3 rounded-pill">View All</a>
            </div>
            <div class="card-body p-4">
                <?php foreach (array_slice($announcements, 0, 3) as $announcement):
                    $__pri = $announcement['priority'];
                    if ($__pri === 'urgent') { $priorityColor = 'danger'; }
                    elseif ($__pri === 'high') { $priorityColor = 'warning'; }
                    elseif ($__pri === 'normal') { $priorityColor = 'primary'; }
                    else { $priorityColor = 'secondary'; }

                    $__type = $announcement['announcement_type'];
                    if ($__type === 'event') { $typeIcon = 'bi-calendar-event'; }
                    elseif ($__type === 'closure') { $typeIcon = 'bi-exclamation-triangle'; }
                    elseif ($__type === 'equipment') { $typeIcon = 'bi-tools'; }
                    elseif ($__type === 'class') { $typeIcon = 'bi-people'; }
                    elseif ($__type === 'promotion') { $typeIcon = 'bi-tag'; }
                    else { $typeIcon = 'bi-info-circle'; }
                ?>
                <div class="d-flex align-items-start mb-3 <?= !$announcement['is_viewed'] ? 'bg-light rounded-3 p-3' : '' ?>">
                    <div class="flex-shrink-0 me-3">
                        <div class="bg-<?= $priorityColor ?> bg-opacity-10 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="bi <?= $typeIcon ?> text-<?= $priorityColor ?>"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1 fw-bold text-dark">
                            <?= htmlspecialchars($announcement['title']) ?>
                            <?php if (!$announcement['is_viewed']): ?>
                            <span class="badge bg-primary rounded-pill ms-2">New</span>
                            <?php endif; ?>
                        </h6>
                        <p class="text-secondary small mb-1"><?= nl2br(htmlspecialchars($announcement['content'])) ?></p>
                        <small class="text-muted"><?= date('M j, Y g:i A', strtotime($announcement['created_at'])) ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right Column -->
    <div class="col-lg-4">
        <!-- Gym Promotions -->
        <?php if (!empty($activePromotions)): ?>
        <div class="card mb-4 border-0 shadow-sm border-start border-4 border-purple" style="border-radius: 8px;">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="bi bi-tags-fill text-purple me-2"></i>Gym Promotions
                </h5>
            </div>
            <div class="card-body">
                <?php foreach ($activePromotions as $p): ?>
                <div class="p-3 mb-3 rounded border border-light bg-light bg-opacity-50">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($p['title']) ?></h6>
                        <span class="badge bg-success">
                            <?php if ($p['discount_type'] === 'percentage'): ?>
                                <?= number_format((float)$p['discount_value'], 0) ?>% Off
                            <?php else: ?>
                                ₱<?= number_format((float)$p['discount_value'], 2) ?> Off
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if (!empty($p['image_path'])): ?>
                        <div class="mb-2">
                            <img src="public/<?= htmlspecialchars($p['image_path']) ?>" class="img-fluid rounded" style="max-height: 100px; width: 100%; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                    <p class="text-muted small mb-2"><?= nl2br(htmlspecialchars($p['description'] ?? '')) ?></p>
                    
                    <div class="d-flex align-items-center justify-content-between bg-white p-2 rounded border mb-2">
                        <code class="text-purple fw-bold fs-6" id="promoCode-<?= $p['id'] ?>"><?= htmlspecialchars($p['promo_code']) ?></code>
                        <button class="btn btn-sm btn-outline-purple py-0 px-2" onclick="copyPromoCode('<?= htmlspecialchars($p['promo_code']) ?>', this)">
                            <i class="bi bi-copy"></i> Copy
                        </button>
                    </div>
                    
                    <small class="text-muted d-block" style="font-size: 0.75rem;">
                        <i class="bi bi-calendar-check me-1"></i> Expires: <?= htmlspecialchars($p['valid_until']) ?>
                    </small>
                    <?php
                    $myPromoInterest = $promotionInterestMap[(int)$p['id']] ?? null;
                    ?>
                    <div class="mt-2 d-flex gap-2 promo-interest-btns" data-promotion-id="<?= (int)$p['id'] ?>">
                        <button type="button"
                            class="interest-btn interested <?= $myPromoInterest === 'interested' ? 'selected' : '' ?>"
                            onclick="saveInterest('promotion', <?= (int)$p['id'] ?>, 'interested', this)">
                            <?= $myPromoInterest === 'interested' ? '✓ ' : '✅ ' ?>I'm Interested
                        </button>
                        <button type="button"
                            class="interest-btn not-interested <?= $myPromoInterest === 'not_interested' ? 'selected' : '' ?>"
                            onclick="saveInterest('promotion', <?= (int)$p['id'] ?>, 'not_interested', this)">
                            <?= $myPromoInterest === 'not_interested' ? '✓ ' : '❌ ' ?>Not Interested
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <script>
        function copyPromoCode(code, btn) {
            navigator.clipboard.writeText(code).then(() => {
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check-lg"></i> Copied!';
                btn.classList.remove('btn-outline-purple');
                btn.classList.add('btn-success', 'text-white');
                setTimeout(() => {
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('btn-success', 'text-white');
                    btn.classList.add('btn-outline-purple');
                }, 2000);
            });
        }
        </script>
        <?php endif; ?>

        <!-- Membership Status -->
        <div class="card mb-4 border-0 shadow-sm" style="border-radius: 14px; border: 1px solid #e2e8f0 !important;">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold text-dark" style="font-size: 1.05rem;">
                    <i class="bi bi-card-checklist me-2 text-success"></i>Membership Status
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <div class="display-6 fw-bold text-<?= $membershipStatusColor ?>">
                        <?= $isExpired ? 'EXPIRED' : 'ACTIVE' ?>
                    </div>
                    <?php if ($member['expiration_date']): ?>
                    <div class="text-muted small mt-1">
                        <?= $isExpired ? 'Expired on' : 'Expires on' ?>
                        <strong><?= date('M j, Y', strtotime($member['expiration_date'])) ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($isExpired || ($daysUntilExpiry !== null && $daysUntilExpiry <= 7)): ?>
                <div class="alert alert-<?= $isExpired ? 'danger' : 'warning' ?> py-2 mb-3 rounded-3">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong><?= $isExpired ? 'Membership Expired!' : 'Renewal Due Soon' ?></strong>
                    <br><small>Please renew your membership to continue accessing the gym.</small>
                </div>
                <?php endif; ?>

                <a href="index.php?r=member/membership" class="btn btn-outline-success w-100 fw-semibold shadow-sm py-2">
                    <i class="bi bi-credit-card me-1"></i>
                    <?= $isExpired ? 'Renew Now' : 'Manage Membership' ?>
                </a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-4 border-0 shadow-sm" style="border-radius: 14px; border: 1px solid #e2e8f0 !important;">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold text-dark" style="font-size: 1.05rem;">
                    <i class="bi bi-lightning-charge me-2 text-warning"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="d-grid gap-2">
                    <a href="index.php?r=fitness/request" class="btn btn-outline-danger d-flex align-items-center justify-content-start p-2 rounded-3 text-decoration-none">
                        <div class="rounded-circle bg-danger bg-opacity-10 p-2 me-3 text-danger d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-person-hearts fs-5"></i>
                        </div>
                        <div class="text-start">
                            <div class="fw-semibold">Request Training</div>
                            <small class="text-muted">Get personalized fitness coaching</small>
                        </div>
                    </a>
                    <a href="index.php?r=member/equipment" class="btn btn-outline-success d-flex align-items-center justify-content-start p-2 rounded-3 text-decoration-none">
                        <div class="rounded-circle bg-success bg-opacity-10 p-2 me-3 text-success d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-tools fs-5"></i>
                        </div>
                        <div class="text-start">
                            <div class="fw-semibold">View Equipment</div>
                            <small class="text-muted">Browse available gym equipment</small>
                        </div>
                    </a>
                    <a href="index.php?r=member/workouts" class="btn btn-outline-primary d-flex align-items-center justify-content-start p-2 rounded-3 text-decoration-none">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-2 me-3 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-plus-circle fs-5"></i>
                        </div>
                        <div class="text-start">
                            <div class="fw-semibold">Log Workout</div>
                            <small class="text-muted">Track your exercise session</small>
                        </div>
                    </a>
                    <a href="index.php?r=member/goals" class="btn btn-outline-warning d-flex align-items-center justify-content-start p-2 rounded-3 text-decoration-none text-warning-emphasis">
                        <div class="rounded-circle bg-warning bg-opacity-15 p-2 me-3 text-warning-emphasis d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-bullseye fs-5"></i>
                        </div>
                        <div class="text-start">
                            <div class="fw-semibold text-warning-emphasis">Set Goal</div>
                            <small class="text-muted">Create a fitness goal</small>
                        </div>
                    </a>
                    <a href="index.php?r=membership/verifycode" class="btn btn-outline-info d-flex align-items-center justify-content-start p-2 rounded-3 text-decoration-none">
                        <div class="rounded-circle bg-info bg-opacity-10 p-2 me-3 text-info d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="bi bi-qr-code fs-5"></i>
                        </div>
                        <div class="text-start">
                            <div class="fw-semibold">Check In</div>
                            <small class="text-muted">Log your gym visit</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Attendance -->
        <?php if (!empty($recentAttendance)): ?>
        <div class="card mb-4 border-0 shadow-sm" style="border-radius: 14px; border: 1px solid #e2e8f0 !important;">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold text-dark" style="font-size: 1.05rem;">
                    <i class="bi bi-clock-history me-2 text-primary"></i>Recent Visits
                </h5>
            </div>
            <div class="card-body p-4">
                <?php foreach (array_slice($recentAttendance, 0, 5) as $visit): ?>
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-light-subtle">
                    <div>
                        <div class="fw-semibold text-dark"><?= date('M j, Y', strtotime($visit['check_in'])) ?></div>
                        <small class="text-muted"><?= date('g:i A', strtotime($visit['check_in'])) ?></small>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">Visited</span>
                </div>
                <?php endforeach; ?>
                <a href="index.php?r=member/attendance" class="btn btn-sm btn-outline-primary w-100 fw-medium mt-2">
                    View Full History
                </a>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.col-lg-4 -->
</div><!-- /.row -->


<!-- Campaign Registration Modal -->
<div class="modal fade" id="cbRegisterModal" tabindex="-1" aria-labelledby="cbRegisterModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px; overflow:hidden; border:none; box-shadow:0 10px 30px rgba(0,0,0,0.15);">
      <div class="modal-header bg-dark text-white py-3">
        <h5 class="modal-title fw-bold" id="cbRegisterModalLabel">Campaign Registration</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <form id="cbRegisterForm">
          <input type="hidden" id="cbRegCampaignTitle">
          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">Campaign Title</label>
            <div class="fw-bold fs-5 text-dark" id="cbRegTitleText"></div>
          </div>
          
          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary" for="cbRegPack">Select Pricing Tier <span class="text-danger">*</span></label>
            <select class="form-select" id="cbRegPack" required style="border-radius:8px; border:1.5px solid #e5e7eb; padding:10px;">
              <!-- Options injected by JS -->
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary" for="cbRegSched">Select Schedule Session <span class="text-danger">*</span></label>
            <select class="form-select" id="cbRegSched" required style="border-radius:8px; border:1.5px solid #e5e7eb; padding:10px;">
              <!-- Options injected by JS -->
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary">Payment Method <span class="text-danger">*</span></label>
            <div class="d-flex gap-3 mt-1">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="cbRegPaymentMode" id="cbRegPaymentOnline" value="online" checked>
                <label class="form-check-label" for="cbRegPaymentOnline">Online (PayMongo)</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="cbRegPaymentMode" id="cbRegPaymentCounter" value="cash">
                <label class="form-check-label" for="cbRegPaymentCounter">Pay at Counter (Cash)</label>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary" for="cbRegName">Full Name</label>
            <input type="text" class="form-control" id="cbRegName" value="<?= htmlspecialchars($displayName) ?>" required readonly style="background:#f3f4f6; border-radius:8px; border:1.5px solid #e5e7eb; padding:10px;">
          </div>

          <div class="mb-3">
            <label class="form-label small fw-bold text-secondary" for="cbRegEmail">Email Address</label>
            <input type="email" class="form-control" id="cbRegEmail" value="<?= htmlspecialchars($user['email']) ?>" required readonly style="background:#f3f4f6; border-radius:8px; border:1.5px solid #e5e7eb; padding:10px;">
          </div>

          <div class="d-grid mt-4">
            <button type="submit" class="btn btn-success py-2 fw-bold" style="border-radius:8px; background:#16a34a; border:none; box-shadow:0 4px 12px rgba(22,163,74,0.25);">
              Submit Registration
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>

<script>
// Database-backed campaign from PHP controller
const DB_CAMPAIGN = <?php echo json_encode($builderCampaign ?? null); ?>;

// ── Scoped Campaign builder Gradients ───────────────────────────────────
const cbGradients = {
  red:    'linear-gradient(135deg, #ef4444 0%, #b91c1c 100%)',
  purple: 'linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%)',
  blue:   'linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%)',
  green:  'linear-gradient(135deg, #10b981 0%, #059669 100%)',
  amber:  'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
  pink:   'linear-gradient(135deg, #ec4899 0%, #be185d 100%)',
};

// Helper function to escape HTML
function esc(s) {
  if (s == null) return '';
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

// Render dynamic campaign from database (or fallback to LocalStorage)
function renderCBCampaign() {
  const container = document.getElementById('cbCampaignContainer');
  if (!container) return;

  let state = { published: false, registrations: [] };

  if (typeof DB_CAMPAIGN !== 'undefined' && DB_CAMPAIGN) {
    state.published = (DB_CAMPAIGN.status === 'active' || DB_CAMPAIGN.status === 'published');
    state.title = DB_CAMPAIGN.title;
    state.desc = DB_CAMPAIGN.description;
    
    // Parse coach from description prefix "Coach: Name\nDescription"
    state.coach = '';
    if (state.desc && state.desc.startsWith('Coach: ')) {
      const nlIdx = state.desc.indexOf('\n');
      if (nlIdx > 0) {
        state.coach = state.desc.substring(7, nlIdx);
        state.desc = state.desc.substring(nlIdx + 1);
      }
    }

    state.start = DB_CAMPAIGN.start_date;
    state.end = DB_CAMPAIGN.end_date;

    if (DB_CAMPAIGN.extra_json) {
      try {
        const extra = JSON.parse(DB_CAMPAIGN.extra_json);
        state.color = extra.color || 'red';
        state.tags = extra.tags || [];
        state.pricing = extra.pricing || [];
        state.schedules = extra.schedules || [];
        state.maxSize = extra.maxSize || 20;
        state.registrations = extra.registrations || [];
      } catch(e){}
    }
  } else {
    // Fallback to LocalStorage
    try {
      state = JSON.parse(localStorage.getItem('cb_campaign_state') || '{}');
    } catch(e){}
  }

  if (!state || !state.published || !state.title) {
    // Show empty state
    container.innerHTML = `
      <div class="card border-0 shadow-sm p-4 text-center mb-4" style="border-radius: 12px; background: #fff;">
        <i class="bi bi-calendar-event text-muted fs-1 mb-2"></i>
        <h6 class="fw-bold text-dark mb-1">No Active Campaigns</h6>
        <p class="text-muted small mb-0">There are no enrollment programs running right now. Check back later!</p>
      </div>
    `;
    return;
  }

  const currentUserEmail = "<?= $user['email'] ?>";
  const myReg = (state.registrations || []).find(r => r.email === currentUserEmail);
  const totalRegistered = (state.registrations || []).length;
  const slotsLeft = Math.max(0, (state.maxSize || 20) - totalRegistered);

  const gradient = cbGradients[state.color] || cbGradients.red;

  let bannerActionHtml = '';
  if (myReg) {
    const statusClass = myReg.status === 'Paid' ? 'bg-success' : 'bg-warning text-dark';
    bannerActionHtml = `
      <div class="mt-3 bg-white bg-opacity-10 p-3 rounded border border-white border-opacity-20 text-start">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div>
            <div class="small text-white text-opacity-80">Your Enrollment Status:</div>
            <strong class="text-white fs-6">${esc(myReg.pack)}</strong>
            <div class="small text-white text-opacity-70">${esc(myReg.schedule)}</div>
          </div>
          <span class="badge ${statusClass} fs-7 px-3 py-2" style="border-radius: 20px;">
            <i class="bi ${myReg.status === 'Paid' ? 'bi-check-circle-fill' : 'bi-hourglass-split'} me-1"></i>
            ${myReg.status}
          </span>
        </div>
        ${myReg.status === 'Paid' 
          ? '<div class="small text-white text-opacity-90 mt-2"><i class="bi bi-stars me-1"></i>Payment Confirmed! Your slot is secured.</div>'
          : '<div class="small text-white text-opacity-90 mt-2"><i class="bi bi-info-circle me-1"></i>Please complete your payment of ₱' + Number(myReg.price).toLocaleString() + ' at the counter to confirm.</div>'
        }
      </div>
    `;
  } else {
    bannerActionHtml = `
      <button class="btn btn-light fw-bold px-4 py-2 mt-3" style="border-radius: 20px; color:#111827; box-shadow:0 4px 12px rgba(0,0,0,0.1);" onclick="openRegModal()">
        <i class="bi bi-check2-circle me-1"></i> I'm Interested — Register Now
      </button>
    `;
  }

  const featureTagsHtml = (state.tags || []).map(t => `
    <span class="badge bg-light text-dark px-3 py-2 small" style="border-radius:20px; font-weight:600; border:1px solid #e5e7eb;">
      ${esc(t)}
    </span>
  `).join('');

  const pricingTiersHtml = (state.pricing || []).map(p => `
    <div class="d-flex justify-content-between align-items-center p-3 rounded mb-2 text-start" style="background:#f9fafb; border:1.5px solid #f3f4f6;">
      <div>
        <div class="fw-bold text-dark fs-6">${esc(p.name)}</div>
        <div class="small text-muted">${esc(p.duration)} • ${p.sessions} Sessions</div>
      </div>
      <div class="fw-extrabold text-success fs-5">₱${Number(p.price).toLocaleString()}</div>
    </div>
  `).join('');

  const schedulesHtml = (state.schedules || []).map(s => `
    <div class="d-flex align-items-center gap-3 p-3 rounded mb-2 text-start" style="background:#f9fafb; border:1.5px solid #f3f4f6;">
      <div class="bg-warning bg-opacity-15 p-2 rounded text-warning" style="width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
        <i class="bi bi-calendar-check fs-5"></i>
      </div>
      <div>
        <div class="fw-bold text-dark" style="font-size:0.95rem;">${esc(s.dateLabel)}</div>
        <div class="small text-muted">${esc(s.timeLabel)}</div>
      </div>
    </div>
  `).join('');

  container.innerHTML = `
    <!-- Gradient Campaign Banner -->
    <div class="p-4 mb-4 text-white position-relative text-start" style="background:${gradient}; border-radius:16px; box-shadow:0 6px 20px rgba(0,0,0,0.08); overflow:hidden;">
      <!-- Decorative circles -->
      <div style="position:absolute; right:-20px; top:-20px; width:140px; height:140px; border-radius:50%; background:rgba(255,255,255,0.08);"></div>
      <div style="position:absolute; right:40px; bottom:-40px; width:100px; height:100px; border-radius:50%; background:rgba(255,255,255,0.05);"></div>
      
      <div class="row align-items-center g-3">
        <div class="col-md-8">
          <span class="badge bg-white bg-opacity-20 text-white mb-2 fs-7 px-3 py-1.5 fw-bold" style="border-radius:20px;">
            <i class="bi bi-stars me-1 text-warning"></i>NOW ENROLLING
          </span>
          <h2 class="fw-extrabold text-white mb-2" style="font-size:1.8rem; letter-spacing:-0.5px;">${esc(state.title)}</h2>
          <div class="small text-white text-opacity-80 d-flex flex-wrap gap-3 align-items-center mb-1">
            <span><i class="bi bi-calendar-range me-1.5"></i>Runs: <strong>${esc(state.start ? new Date(state.start + 'T00:00:00').toLocaleDateString(undefined, {month:'short', day:'numeric'}) : '')}</strong> to <strong>${esc(state.end ? new Date(state.end + 'T00:00:00').toLocaleDateString(undefined, {month:'short', day:'numeric', year:'numeric'}) : '')}</strong></span>
            <span><i class="bi bi-person-badge me-1.5"></i>Coach: <strong>${esc(state.coach)}</strong></span>
          </div>
          ${bannerActionHtml}
        </div>
        <div class="col-md-4 text-md-end">
          <div class="d-inline-flex gap-3 bg-black bg-opacity-20 p-3 rounded" style="backdrop-filter: blur(10px); border:1px solid rgba(255,255,255,0.1);">
            <div class="text-center" style="min-width:70px;">
              <div class="fs-4 fw-extrabold text-white">${slotsLeft}</div>
              <div class="small text-white text-opacity-70" style="font-size:0.7rem; text-transform:uppercase;">Slots Left</div>
            </div>
            <div class="border-end border-white border-opacity-20"></div>
            <div class="text-center" style="min-width:70px;">
              <div class="fs-4 fw-extrabold text-white">${totalRegistered}</div>
              <div class="small text-white text-opacity-70" style="font-size:0.7rem; text-transform:uppercase;">Registered</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Details Info Cards -->
    <div class="row g-4 mb-2">
      <!-- Pricing Card -->
      <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm" style="border-radius:12px;">
          <div class="card-header bg-white border-0 py-3">
            <h6 class="card-title fw-bold mb-0 text-dark"><i class="bi bi-currency-dollar text-success me-1.5"></i>Available Packages</h6>
          </div>
          <div class="card-body py-1">
            ${pricingTiersHtml}
          </div>
        </div>
      </div>
      <!-- Schedule Card -->
      <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm" style="border-radius:12px;">
          <div class="card-header bg-white border-0 py-3">
            <h6 class="card-title fw-bold mb-0 text-dark"><i class="bi bi-calendar3 text-warning me-1.5"></i>Session Schedules</h6>
          </div>
          <div class="card-body py-1">
            ${schedulesHtml}
          </div>
        </div>
      </div>
    </div>

    <!-- About Card -->
    <div class="card border-0 shadow-sm p-4 mt-4 text-start mb-4" style="border-radius:12px; background:#fff;">
      <h6 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle text-primary me-2"></i>About This Program</h6>
      <p class="text-secondary small mb-3" style="line-height:1.6;">${state.desc ? esc(state.desc).replace(/\n/g, '<br>') : 'No description provided.'}</p>
      <div class="d-flex flex-wrap gap-2">
        ${featureTagsHtml}
      </div>
    </div>
  `;
}

function openRegModal() {
  let state = {};
  
  if (typeof DB_CAMPAIGN !== 'undefined' && DB_CAMPAIGN) {
    state.title = DB_CAMPAIGN.title;
    if (DB_CAMPAIGN.extra_json) {
      try {
        const extra = JSON.parse(DB_CAMPAIGN.extra_json);
        state.pricing = extra.pricing || [];
        state.schedules = extra.schedules || [];
      } catch(e){}
    }
  } else {
    // Fallback to localStorage
    try {
      state = JSON.parse(localStorage.getItem('cb_campaign_state') || '{}');
    } catch(e){}
  }
  
  if (!state || !state.title) return;

  document.getElementById('cbRegTitleText').textContent = state.title;
  document.getElementById('cbRegCampaignTitle').value = state.title;

  const packSelect = document.getElementById('cbRegPack');
  packSelect.innerHTML = '<option value="">— Choose a package —</option>';
  (state.pricing || []).forEach(p => {
    packSelect.innerHTML += `<option value="${esc(p.name)}|${p.price}">${esc(p.name)} — ₱${Number(p.price).toLocaleString()} (${esc(p.duration)})</option>`;
  });

  const schedSelect = document.getElementById('cbRegSched');
  schedSelect.innerHTML = '<option value="">— Choose a session schedule —</option>';
  (state.schedules || []).forEach(s => {
    schedSelect.innerHTML += `<option value="${esc(s.dateLabel)} (${esc(s.timeLabel)})">${esc(s.dateLabel)} at ${esc(s.timeLabel)}</option>`;
  });

  const modal = new bootstrap.Modal(document.getElementById('cbRegisterModal'));
  modal.show();
}

// ── Interest button AJAX ──────────────────────────────────────────────
function saveInterest(type, id, status, clickedBtn) {
    // Determine sibling container and opposing status
    const container = clickedBtn.closest('.campaign-interest-btns, .promo-interest-btns');
    const allBtns   = container ? container.querySelectorAll('.interest-btn') : [];

    const url = (type === 'campaign')
        ? 'index.php?r=member/savecampaigninterest'
        : 'index.php?r=member/savepromotioninterest';

    const body = new FormData();
    body.append((type === 'campaign' ? 'campaign_id' : 'promotion_id'), id);
    body.append('status', status);

    // Optimistic UI update
    allBtns.forEach(btn => {
        btn.classList.remove('selected');
        if (btn.classList.contains('interested'))     btn.innerHTML = btn.innerHTML.replace(/^✓ /, '✅ ');
        if (btn.classList.contains('not-interested')) btn.innerHTML = btn.innerHTML.replace(/^✓ /, '❌ ');
    });
    clickedBtn.classList.add('selected');
    if (status === 'interested')     clickedBtn.innerHTML = clickedBtn.innerHTML.replace(/^✅ /, '✓ ');
    if (status === 'not_interested') clickedBtn.innerHTML = clickedBtn.innerHTML.replace(/^❌ /, '✓ ');

    fetch(url, { method: 'POST', body })
        .then(r => r.json())
        .catch(() => null);
}

// ── Coaching card collapse / expand ────────────────────────────────────
(function() {
    const btn  = document.getElementById('coachingToggleBtn');
    const body = document.getElementById('coachingCardBody');
    const icon = document.getElementById('coachingToggleIcon');
    if (!btn || !body || !icon) return;

    const STORAGE_KEY = 'nutrify_coaching_collapsed';

    function applyState(collapsed) {
        if (collapsed) {
            body.classList.add('collapsed');
            icon.classList.replace('bi-chevron-up', 'bi-chevron-down');
        } else {
            body.classList.remove('collapsed');
            icon.classList.replace('bi-chevron-down', 'bi-chevron-up');
        }
    }

    // Restore saved state
    applyState(localStorage.getItem(STORAGE_KEY) === '1');

    btn.addEventListener('click', function() {
        const isNowCollapsed = !body.classList.contains('collapsed');
        localStorage.setItem(STORAGE_KEY, isNowCollapsed ? '1' : '0');
        applyState(isNowCollapsed);
    });
})();

// Document Loaded
document.addEventListener('DOMContentLoaded', () => {
  renderCBCampaign();

  const regForm = document.getElementById('cbRegisterForm');
  if (regForm) {
    regForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      let state = {};
      try {
        state = JSON.parse(localStorage.getItem('cb_campaign_state') || '{}');
      } catch(e){}

      if (!state.registrations) state.registrations = [];

      const name = document.getElementById('cbRegName').value.trim();
      const email = document.getElementById('cbRegEmail').value.trim();
      const packVal = document.getElementById('cbRegPack').value;
      const schedVal = document.getElementById('cbRegSched').value;

      if (!packVal || !schedVal) {
        alert('Please select both a package and a schedule.');
        return;
      }

      const [packName, packPrice] = packVal.split('|');
      
      let paymentMode = 'cash';
      const paymentModeEls = document.getElementsByName('cbRegPaymentMode');
      for (const el of paymentModeEls) {
        if (el.checked) {
          paymentMode = el.value;
          break;
        }
      }

      if (typeof DB_CAMPAIGN !== 'undefined' && DB_CAMPAIGN) {
        const body = new FormData();
        body.append('campaign_id', DB_CAMPAIGN.id);
        body.append('pack_name', packName);
        body.append('pack_price', packPrice);
        body.append('schedule', schedVal);
        body.append('payment_mode', paymentMode);

        fetch('index.php?r=member/registercampaignbuilder', { method: 'POST', body })
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              const modalEl = document.getElementById('cbRegisterModal');
              const modal = bootstrap.Modal.getInstance(modalEl);
              if (modal) modal.hide();
              
              if (data.checkout_url) {
                  window.location.href = data.checkout_url;
              } else {
                  alert(`Registration submitted! Please pay ₱${Number(packPrice).toLocaleString()} at the counter to activate your slot.`);
                  location.reload();
              }
            } else {
              alert('Error: ' + (data.error || 'Failed to submit registration'));
            }
          })
          .catch(() => alert('Network error. Please try again.'));
        return;
      }

      const newReg = {
        id: state.registrations.length + 1,
        name: name,
        email: email,
        pack: packName,
        price: Number(packPrice),
        schedule: schedVal,
        date: new Date().toLocaleDateString(undefined, {month:'short', day:'numeric', year:'numeric'}),
        status: 'Pending'
      };

      state.registrations.push(newReg);
      localStorage.setItem('cb_campaign_state', JSON.stringify(state));

      // Close modal
      const modalEl = document.getElementById('cbRegisterModal');
      const modal = bootstrap.Modal.getInstance(modalEl);
      if (modal) modal.hide();

      // Rerender campaign view
      renderCBCampaign();

      // Show success toast or alert
      alert(`Registration submitted! Please pay ₱${Number(packPrice).toLocaleString()} at the counter to activate your slot.`);
    });
  }
});
</script>

