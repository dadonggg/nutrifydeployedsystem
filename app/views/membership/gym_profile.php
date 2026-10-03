<?php
declare(strict_types=1);
$gymName   = htmlspecialchars($gym['gym_name'] ?? 'Gym Profile', ENT_QUOTES, 'UTF-8');
$pageTitle = $gymName . ' — Gym Profile';
require __DIR__ . '/../partials/header.php';

/* ── Helpers ──────────────────────────────────────────────────── */
$h = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$gymId   = (int)($gym['id'] ?? 0);
$ownerId = (int)($gym['user_id'] ?? 0);

/* ── Address ──────────────────────────────────────────────────── */
$addressParts = array_filter([
    $gym['barangay']          ?? '',
    $gym['city_municipality'] ?? '',
    $gym['province']          ?? '',
]);
$fullAddress = !empty($gym['gym_address'])
    ? $gym['gym_address']
    : implode(', ', $addressParts);

/* ── Opening hours day map ────────────────────────────────────── */
$days  = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
$ohMap = [];
foreach (($openingHours ?? []) as $row) {
    $ohMap[$row['day'] ?? ''] = $row;
}
?>
<style>
/* ──────────────────────────────────────────────────────────────
   GYM PROFILE STYLES
   ────────────────────────────────────────────────────────────── */

/* Hero banner */
.gp-hero {
    position: relative;
    border-radius: 18px;
    overflow: hidden;
    background: linear-gradient(135deg, #0e1c12 0%, #164a20 50%, #1B6B2A 100%);
    color: #fff;
    margin-bottom: 2.2rem;
    box-shadow: 0 8px 32px rgba(27,107,42,.2);
    z-index: 1;
}
.gp-hero-cover {
    width: 100%; height: 260px;
    object-fit: cover; display: block;
    z-index: 1; position: relative;
}
.gp-hero-placeholder {
    height: 260px;
    background: linear-gradient(135deg, #0d1a10 0%, #164a20 60%, #1B6B2A 100%);
    position: relative;
    overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    z-index: 1;
}
.gp-hero-placeholder::before {
    content: '';
    position: absolute;
    top: -50%; left: -50%; width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(76,175,80,.12) 10%, transparent 20%),
                radial-gradient(circle, rgba(255,255,255,.05) 15%, transparent 25%);
    background-size: 40px 40px, 60px 60px;
    opacity: .7;
}
.gp-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0,0,0,0.15) 0%, rgba(8,20,12,0.7) 45%, rgba(8,20,12,0.95) 100%);
    z-index: 2;
    pointer-events: none;
}
.gp-hero-body {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    padding: 1.75rem 2rem;
    z-index: 3;
}
.gp-logo {
    width: 88px; height: 88px;
    border-radius: 16px;
    object-fit: cover;
    border: 3.5px solid #ffffff;
    box-shadow: 0 8px 24px rgba(0,0,0,.35);
    background: #ffffff;
    flex-shrink: 0;
}
.gp-logo-ph {
    width: 88px; height: 88px;
    border-radius: 16px;
    border: 3.5px solid #ffffff;
    background: linear-gradient(135deg, #1B6B2A 0%, #2E8B3E 100%);
    box-shadow: 0 8px 24px rgba(0,0,0,.35);
    display: flex; align-items: center; justify-content: center;
    font-size: 2.3rem; color: #ffffff;
    flex-shrink: 0;
}

/* Section card */
.gp-sec {
    background: #fff;
    border-radius: 14px;
    padding: 1.6rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 10px rgba(27,107,42,.06);
    border: 1px solid var(--nf-border);
}
.gp-sec-title {
    font-size: .88rem;
    font-weight: 700;
    color: var(--nf-green);
    letter-spacing: .06em;
    text-transform: uppercase;
    margin-bottom: 1.1rem;
    display: flex; align-items: center; gap: .5rem;
}
.gp-sec-title::after {
    content: '';
    flex: 1; height: 2px;
    background: linear-gradient(to right, var(--nf-green-light), transparent);
    border-radius: 2px;
    margin-left: .3rem;
    opacity: .3;
}

/* Equipment chips */
.eq-chip {
    display: inline-flex; align-items: center; gap: .35rem;
    background: linear-gradient(135deg, #e8f5e9, #f1f8e9);
    color: #1B6B2A;
    border: 1px solid rgba(27,107,42,.18);
    border-radius: 8px;
    padding: .32rem .7rem;
    font-size: .82rem; font-weight: 500;
    margin: .2rem;
    transition: background .15s;
}
.eq-chip:hover { background: linear-gradient(135deg, #c8e6c9, #dcedc8); }

/* Service row */
.svc-row {
    display: flex; align-items: flex-start; gap: .9rem;
    padding: .75rem;
    border-radius: 10px;
    border: 1px solid var(--nf-border);
    margin-bottom: .55rem;
    transition: box-shadow .15s;
}
.svc-row:hover { box-shadow: 0 3px 12px rgba(27,107,42,.1); }
.svc-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    display: flex; align-items: center; justify-content: center;
    color: var(--nf-green); font-size: 1.1rem; flex-shrink: 0;
}

/* Plan / training cards */
.plan-card {
    border-radius: 12px;
    border: 2px solid var(--nf-border);
    padding: 1.2rem;
    text-align: center;
    position: relative; overflow: hidden;
    background: #fff;
    height: 100%;
    transition: border-color .2s, box-shadow .2s, transform .2s;
}
.plan-card::before {
    content: ''; position: absolute;
    top: 0; left: 0; right: 0; height: 4px;
    background: linear-gradient(90deg, var(--nf-green), var(--nf-accent));
}
.plan-card:hover {
    border-color: var(--nf-green-light);
    box-shadow: 0 6px 20px rgba(27,107,42,.14);
    transform: translateY(-3px);
}
.plan-price {
    font-size: 1.75rem; font-weight: 800;
    color: var(--nf-green); line-height: 1;
}
.plan-duration { font-size: .8rem; color: var(--nf-muted); margin-top: .25rem; }

/* Hours */
.hours-table td, .hours-table th { padding: .42rem .65rem; vertical-align: middle; }
.hbadge-open   { background:#d4edda; color:#155724; font-size:.75rem; border-radius:5px; padding:.18rem .5rem; }
.hbadge-closed { background:#f8d7da; color:#721c24; font-size:.75rem; border-radius:5px; padding:.18rem .5rem; }

/* Sticky mobile CTA */
.gp-mob-cta {
    position: sticky; bottom: 0;
    background: rgba(255,255,255,.96);
    backdrop-filter: blur(10px);
    border-top: 1px solid var(--nf-border);
    padding: .85rem 0;
    z-index: 90;
    box-shadow: 0 -4px 16px rgba(27,107,42,.08);
}

@media(max-width:575px){
    .gp-hero-body { padding: 1.2rem 1.2rem; }
    .gp-logo, .gp-logo-ph { width: 68px; height: 68px; }
    .gp-hero-cover, .gp-hero-placeholder { height: 200px; }
}
</style>

<!-- ── Back link ─────────────────────────────────────────────── -->
<div class="mb-3">
    <a href="index.php?r=membership/gyms" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Gyms
    </a>
</div>

<!-- ════════════════════════════════════════════════════════════
     HERO HEADER
     ════════════════════════════════════════════════════════════ -->
<div class="gp-hero">
    <?php if (!empty($gym['gym_cover'])): ?>
        <img src="public/<?= $h($gym['gym_cover']) ?>" class="gp-hero-cover" alt="<?= $gymName ?>">
    <?php else: ?>
        <div class="gp-hero-placeholder">
            <i class="bi bi-building-fill text-white" style="font-size: 6rem; opacity: 0.12;"></i>
        </div>
    <?php endif; ?>
    <div class="gp-hero-overlay"></div>

    <div class="gp-hero-body">
        <div class="d-flex align-items-end gap-3 flex-wrap">
            <?php if (!empty($gym['gym_logo'])): ?>
                <img src="public/<?= $h($gym['gym_logo']) ?>" class="gp-logo" alt="logo">
            <?php else: ?>
                <div class="gp-logo-ph"><i class="bi bi-building-fill"></i></div>
            <?php endif; ?>

            <div class="flex-grow-1">
                <h1 class="fw-bold mb-1 text-white" style="font-size: clamp(1.4rem, 3.2vw, 2rem); text-shadow: 0 2px 8px rgba(0,0,0,0.8), 0 1px 2px rgba(0,0,0,0.9); font-weight: 800;">
                    <?= $gymName ?>
                </h1>
                <div class="d-flex flex-wrap gap-3" style="font-size: 0.92rem; font-weight: 600;">
                    <?php if ($fullAddress): ?>
                    <span style="color: #ffffff; text-shadow: 0 1px 6px rgba(0,0,0,0.9), 0 0 12px rgba(0,0,0,0.7);"><i class="bi bi-geo-alt-fill me-1" style="color: #69f0ae;"></i><?= $h($fullAddress) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($owner['fullname'])): ?>
                    <span style="color: #ffffff; text-shadow: 0 1px 6px rgba(0,0,0,0.9), 0 0 12px rgba(0,0,0,0.7);"><i class="bi bi-person-fill me-1" style="color: #69f0ae;"></i>Owner: <?= $h($owner['fullname']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <?php if (!empty($programSuccessRate)): ?>
                <div class="px-3 py-2 shadow-sm d-flex align-items-center gap-2" style="background: rgba(10, 30, 18, 0.85); border: 1.5px solid rgba(250, 204, 21, 0.7); border-radius: 12px; backdrop-filter: blur(8px); color: #fff;">
                    <i class="bi bi-award-fill text-warning" style="font-size: 1.35rem;"></i>
                    <div>
                        <div style="font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; color: #fef08a;">Program Success Rate</div>
                        <div style="font-size: 1.05rem; font-weight: 800; line-height: 1.1; color: #ffffff;">
                            <span style="color: #facc15;"><?= number_format((float)($programSuccessRate['Ir'] ?? 0), 1) ?>%</span>
                            <span style="font-size: 0.75rem; font-weight: 500; opacity: 0.9; margin-left: 3px; color: #f1f5f9;">(<?= (int)($programSuccessRate['ns'] ?? 0) ?>/<?= (int)($programSuccessRate['N'] ?? 0) ?> Goals Met)</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <span class="badge px-3 py-2 shadow-sm" style="background: rgba(46,139,62,0.95); border: 1px solid rgba(255,255,255,0.3); border-radius: 10px; font-size: 0.85rem; color: #fff; text-shadow: 0 1px 2px rgba(0,0,0,0.4); height: fit-content;">
                    <i class="bi bi-patch-check-fill me-1"></i>Verified
                </span>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     MAIN CONTENT GRID
     ════════════════════════════════════════════════════════════ -->
<div class="row g-4">

    <!-- ── LEFT / MAIN COLUMN ─────────────────────────────────── -->
    <div class="col-lg-8">

        <!-- ABOUT ─────────────────────────────────────────────── -->
        <div class="gp-sec">
            <div class="gp-sec-title"><i class="bi bi-info-circle-fill"></i>About This Gym</div>
            <?php if (!empty($gym['gym_description'])): ?>
            <div class="mb-3 p-3 rounded bg-light border-start border-success border-3">
                <small class="text-muted d-block fw-semibold mb-1">OVERVIEW</small>
                <div class="small text-secondary" style="line-height:1.6;"><?= nl2br($h($gym['gym_description'])) ?></div>
            </div>
            <?php endif; ?>
            <div class="row g-3">
                <div class="col-sm-6">
                    <small class="text-muted d-block mb-1">Gym Name</small>
                    <strong><?= $gymName ?></strong>
                </div>
                <?php if ($fullAddress): ?>
                <div class="col-sm-6">
                    <small class="text-muted d-block mb-1"><i class="bi bi-geo-alt me-1"></i>Full Address</small>
                    <span><?= $h($fullAddress) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($gym['barangay'])): ?>
                <div class="col-sm-6">
                    <small class="text-muted d-block mb-1">Barangay</small>
                    <?= $h($gym['barangay']) ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($gym['city_municipality'])): ?>
                <div class="col-sm-6">
                    <small class="text-muted d-block mb-1">City / Municipality</small>
                    <?= $h($gym['city_municipality']) ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($gym['province'])): ?>
                <div class="col-sm-6">
                    <small class="text-muted d-block mb-1">Province</small>
                    <?= $h($gym['province']) ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($owner['fullname'])): ?>
                <div class="col-sm-6">
                    <small class="text-muted d-block mb-1">Gym Owner</small>
                    <strong><?= $h($owner['fullname']) ?></strong>
                </div>
                <?php endif; ?>
                <?php if (!empty($gym['trainer_count']) || !empty($gym['maintenance_count'])): ?>
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-2 mt-1">
                        <?php if (!empty($gym['trainer_count'])): ?>
                        <span class="badge bg-success-subtle text-success px-3 py-2" style="font-size:.82rem;">
                            <i class="bi bi-person-arms-up me-1"></i><?= (int)$gym['trainer_count'] ?> Trainer<?= $gym['trainer_count'] != 1 ? 's' : '' ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($gym['maintenance_count'])): ?>
                        <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-2" style="font-size:.82rem;">
                            <i class="bi bi-tools me-1"></i><?= (int)$gym['maintenance_count'] ?> Maintenance Staff
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- EQUIPMENT ─────────────────────────────────────────── -->
        <div class="gp-sec">
            <div class="gp-sec-title"><i class="bi bi-gear-fill"></i>Available Equipment</div>
            <?php if (empty($equipment)): ?>
                <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>No equipment listed yet.</p>
            <?php else:
                $byCategory = [];
                foreach ($equipment as $eq) {
                    $cat = !empty($eq['category']) ? $eq['category'] : 'General';
                    $byCategory[$cat][] = $eq;
                }
                foreach ($byCategory as $cat => $items): ?>
                <div class="mb-3">
                    <div class="text-muted fw-semibold small mb-1 text-uppercase" style="letter-spacing:.05em;">
                        <?= $h($cat) ?>
                    </div>
                    <div class="d-flex flex-wrap">
                        <?php foreach ($items as $eq): ?>
                        <span class="eq-chip" title="<?= $h(($eq['brand'] ?? '') . ' — Qty: ' . ($eq['quantity'] ?? 1)) ?>">
                            <i class="bi bi-check-circle-fill text-success" style="font-size:.8rem;"></i>
                            <?= $h($eq['name']) ?>
                            <?php if (!empty($eq['quantity']) && $eq['quantity'] > 1): ?>
                            <span class="badge rounded-pill" style="background:rgba(27,107,42,.14);color:var(--nf-green);font-size:.68rem;">
                                ×<?= (int)$eq['quantity'] ?>
                            </span>
                            <?php endif; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach;
            endif; ?>
        </div>

        <!-- SERVICES ──────────────────────────────────────────── -->
        <div class="gp-sec">
            <div class="gp-sec-title"><i class="bi bi-stars"></i>Available Services</div>
            <?php if (empty($services)): ?>
                <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>No services listed yet.</p>
            <?php else:
                $svcIconMap = [
                    'personal'  => 'bi-person-arms-up',
                    'group'     => 'bi-people-fill',
                    'sauna'     => 'bi-thermometer-sun',
                    'pool'      => 'bi-water',
                    'yoga'      => 'bi-activity',
                    'zumba'     => 'bi-music-note-beamed',
                    'pilates'   => 'bi-heart-pulse',
                    'boxing'    => 'bi-shield-fill',
                    'spinning'  => 'bi-bicycle',
                    'crossfit'  => 'bi-trophy-fill',
                ];
                foreach ($services as $s):
                    $icon = 'bi-lightning-charge-fill';
                    foreach ($svcIconMap as $kw => $ic) {
                        if (stripos($s['name'], $kw) !== false) { $icon = $ic; break; }
                    }
            ?>
            <div class="svc-row">
                <div class="svc-icon"><i class="bi <?= $icon ?>"></i></div>
                <div class="flex-grow-1">
                    <div class="fw-semibold"><?= $h($s['name']) ?></div>
                    <?php if (!empty($s['description'])): ?>
                    <small class="text-muted"><?= $h($s['description']) ?></small>
                    <?php endif; ?>
                </div>
                <div class="text-end text-nowrap ms-2">
                    <?php if (!empty($s['member_price'])): ?>
                    <div class="small fw-semibold text-success">
                        ₱<?= number_format((float)$s['member_price'], 2) ?>
                        <span class="text-muted fw-normal">/ member</span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($s['non_member_price'])): ?>
                    <div class="small text-muted">₱<?= number_format((float)$s['non_member_price'], 2) ?> non-member</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach;
            endif; ?>
        </div>

        <!-- MEMBERSHIP PLANS ──────────────────────────────────── -->
        <div class="gp-sec">
            <div class="gp-sec-title"><i class="bi bi-card-checklist"></i>Membership Plans</div>
            <?php if (empty($plans)): ?>
                <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>No membership plans listed yet.</p>
            <?php else: ?>
            <div class="row g-3">
                <?php foreach ($plans as $plan):
                    $d = (int)($plan['duration_days'] ?? 0);
                    if ($d >= 365)      $durLabel = '📅 ' . round($d/365, 1) . ' Year' . ($d >= 730 ? 's' : '');
                    elseif ($d >= 30)   $durLabel = '📅 ' . round($d/30) . ' Month' . ($d >= 60 ? 's' : '');
                    else                $durLabel = '📅 ' . $d . ' Day' . ($d !== 1 ? 's' : '');
                ?>
                <div class="col-sm-6 col-md-4">
                    <div class="plan-card">
                        <div class="fw-bold mb-2"><?= $h($plan['name']) ?></div>
                        <div class="plan-price">₱<?= number_format((float)$plan['price'], 2) ?></div>
                        <div class="plan-duration"><?= $durLabel ?></div>
                        <?php if (!empty($plan['description'])): ?>
                        <p class="text-muted mt-2 mb-0" style="font-size:.77rem;"><?= $h($plan['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- TRAINING PACKAGES ─────────────────────────────────── -->
        <div class="gp-sec">
            <div class="gp-sec-title"><i class="bi bi-lightning-fill"></i>Training Packages</div>
            <?php if (empty($trainingPackages)): ?>
                <p class="text-muted small mb-0"><i class="bi bi-info-circle me-1"></i>No training packages available.</p>
            <?php else: ?>
            <div class="row g-3">
                <?php foreach ($trainingPackages as $pkg): ?>
                <div class="col-sm-6 col-md-4">
                    <div class="plan-card">
                        <div class="fw-bold mb-1"><?= $h($pkg['package_name']) ?></div>
                        <?php if (!empty($pkg['training_type']) && $pkg['training_type'] !== 'all'): ?>
                        <span class="badge bg-success-subtle text-success small mb-2 d-inline-block">
                            <?= $h(ucfirst($pkg['training_type'])) ?>
                        </span>
                        <?php endif; ?>
                        <div class="plan-price">₱<?= number_format((float)$pkg['price'], 2) ?></div>
                        <div class="plan-duration">
                            🏋️ <?= (int)$pkg['session_count'] ?> session<?= $pkg['session_count'] != 1 ? 's' : '' ?>
                            <?php if (!empty($pkg['duration_minutes'])): ?>
                            · ⏱ <?= (int)$pkg['duration_minutes'] ?> min
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($pkg['description'])): ?>
                        <p class="text-muted mt-2 mb-0" style="font-size:.77rem;"><?= $h($pkg['description']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /col-lg-8 -->

    <!-- ── RIGHT SIDEBAR ──────────────────────────────────────── -->
    <div class="col-lg-4">

        <!-- OPENING HOURS ───────────────────────────────────────── -->
        <div class="gp-sec">
            <div class="gp-sec-title"><i class="bi bi-clock-fill"></i>Opening Hours</div>
            <?php if (!empty($openingHours)): ?>
            <table class="table table-sm hours-table mb-0">
                <tbody>
                <?php foreach ($days as $day):
                    $row    = $ohMap[$day] ?? null;
                    $isOpen = $row
                        && !empty($row['open_time'])
                        && !empty($row['close_time'])
                        && ($row['is_closed'] ?? 0) == 0;
                    $today  = (date('l') === $day);
                ?>
                <tr class="<?= $today ? 'fw-bold' : '' ?>">
                    <td style="width:42%;">
                        <?= $day ?>
                        <?php if ($today): ?>
                        <span class="badge bg-success ms-1" style="font-size:.6rem;">Today</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($isOpen): ?>
                            <span class="hbadge-open">Open</span>
                            <small class="ms-1 text-muted">
                                <?= date('g:i A', strtotime($row['open_time'])) ?>
                                – <?= date('g:i A', strtotime($row['close_time'])) ?>
                            </small>
                        <?php elseif ($row && ($row['is_closed'] ?? 0) == 1): ?>
                            <span class="hbadge-closed">Closed</span>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="text-center py-3">
                <i class="bi bi-clock text-muted" style="font-size:2rem;"></i>
                <p class="text-muted small mt-2 mb-0">
                    Opening hours not specified.<br>
                    Please contact the gym directly.
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- QUICK INFO ──────────────────────────────────────────── -->
        <div class="gp-sec">
            <div class="gp-sec-title"><i class="bi bi-info-circle-fill"></i>Quick Info</div>
            <ul class="list-unstyled mb-0 small">
                <li class="d-flex align-items-center gap-2 py-2 border-bottom">
                    <i class="bi bi-person-fill text-success"></i>
                    <span class="text-muted">Owner</span>
                    <strong class="ms-auto"><?= $h($owner['fullname'] ?? 'N/A') ?></strong>
                </li>
                <li class="d-flex align-items-center gap-2 py-2 border-bottom">
                    <i class="bi bi-card-checklist text-success"></i>
                    <span class="text-muted">Plans</span>
                    <strong class="ms-auto"><?= count($plans) ?></strong>
                </li>
                <li class="d-flex align-items-center gap-2 py-2 border-bottom">
                    <i class="bi bi-gear-fill text-success"></i>
                    <span class="text-muted">Equipment</span>
                    <strong class="ms-auto"><?= count($equipment) ?> item<?= count($equipment) !== 1 ? 's' : '' ?></strong>
                </li>
                <li class="d-flex align-items-center gap-2 py-2 border-bottom">
                    <i class="bi bi-stars text-success"></i>
                    <span class="text-muted">Services</span>
                    <strong class="ms-auto"><?= count($services) ?></strong>
                </li>
                <li class="d-flex align-items-center gap-2 py-2">
                    <i class="bi bi-lightning-fill text-success"></i>
                    <span class="text-muted">Training Pkgs</span>
                    <strong class="ms-auto"><?= count($trainingPackages) ?></strong>
                </li>
            </ul>
        </div>

        <!-- DESKTOP CTA BUTTONS ─────────────────────────────────── -->
        <div class="d-grid gap-2 d-none d-lg-grid">
            <a href="index.php?r=membership/apply&gym_id=<?= $gymId ?>"
               class="btn btn-primary btn-lg d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-person-plus-fill"></i>Apply for Membership
            </a>
            <?php if (!empty($trainingPackages)): ?>
            <a href="index.php?r=fitness/request"
               class="btn btn-outline-success btn-lg d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-lightning-fill"></i>Request Training
            </a>
            <?php endif; ?>
            <a href="index.php?r=membership/gyms"
               class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-arrow-left"></i>Back to Gyms
            </a>
        </div>

    </div><!-- /col-lg-4 -->

</div><!-- /row -->

<!-- STICKY MOBILE CTA ─────────────────────────────────────────── -->
<div class="d-lg-none gp-mob-cta">
    <div class="container-fluid px-3">
        <div class="d-flex gap-2">
            <a href="index.php?r=membership/apply&gym_id=<?= $gymId ?>"
               class="btn btn-primary flex-fill fw-semibold">
                <i class="bi bi-person-plus me-1"></i>Apply Membership
            </a>
            <?php if (!empty($trainingPackages)): ?>
            <a href="index.php?r=fitness/request"
               class="btn btn-outline-success flex-fill fw-semibold">
                <i class="bi bi-lightning me-1"></i>Request Training
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../partials/footer.php'; ?>
