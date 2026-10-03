<?php
$currentUser = $currentUser ?? (!empty($_SESSION['user_id']));
if (!empty($_SESSION['user_id'])) {
    $currentUser = true;
}
?>
<?php if ($currentUser): ?>
    </div><!-- .main-content -->
</div><!-- .d-flex -->

<!-- Mobile Bottom Navigation Bar (Visible only on < 768px for logged-in users) -->
<nav class="mobile-bottom-nav d-md-none" aria-label="Quick Mobile Navigation">
    <a href="index.php?r=home/index" class="mobile-bottom-item <?= (!isset($_GET['r']) || $_GET['r']==='home/index' || str_contains($_GET['r'], 'dashboard')) ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard</span>
    </a>

    <?php if (($userRole ?? '') === 'gym_owner'): ?>
        <a href="index.php?r=equipment/budget" class="mobile-bottom-item <?= (isset($_GET['r']) && str_contains($_GET['r'], 'budget')) ? 'active' : '' ?>">
            <i class="bi bi-wallet2"></i>
            <span>Finance</span>
        </a>
        <a href="index.php?r=gymowner/members" class="mobile-bottom-item <?= (isset($_GET['r']) && str_contains($_GET['r'], 'members')) ? 'active' : '' ?>">
            <i class="bi bi-people"></i>
            <span>Members</span>
        </a>
    <?php elseif (($userRole ?? '') === 'customer'): ?>
        <a href="index.php?r=member/coaching" class="mobile-bottom-item <?= (isset($_GET['r']) && str_contains($_GET['r'], 'coaching')) ? 'active' : '' ?>">
            <i class="bi bi-lightning-charge"></i>
            <span>Workouts</span>
        </a>
        <a href="index.php?r=member/progress" class="mobile-bottom-item <?= (isset($_GET['r']) && str_contains($_GET['r'], 'progress')) ? 'active' : '' ?>">
            <i class="bi bi-graph-up-arrow"></i>
            <span>Progress</span>
        </a>
    <?php elseif (($userRole ?? '') === 'trainer' || ($userRole ?? '') === 'fitness_trainer'): ?>
        <a href="index.php?r=trainer/clients" class="mobile-bottom-item <?= (isset($_GET['r']) && str_contains($_GET['r'], 'clients')) ? 'active' : '' ?>">
            <i class="bi bi-people"></i>
            <span>Clients</span>
        </a>
        <a href="index.php?r=trainer/requests" class="mobile-bottom-item <?= (isset($_GET['r']) && str_contains($_GET['r'], 'requests')) ? 'active' : '' ?>">
            <i class="bi bi-inbox"></i>
            <span>Bookings</span>
        </a>
    <?php elseif (($userRole ?? '') === 'maintenance' || ($userRole ?? '') === 'maintenance_officer'): ?>
        <a href="index.php?r=maintenance/equipment" class="mobile-bottom-item <?= (isset($_GET['r']) && str_contains($_GET['r'], 'equipment')) ? 'active' : '' ?>">
            <i class="bi bi-box-seam"></i>
            <span>Equipment</span>
        </a>
    <?php elseif (($userRole ?? '') === 'marketing_officer'): ?>
        <a href="index.php?r=marketing/campaigns" class="mobile-bottom-item <?= (isset($_GET['r']) && str_contains($_GET['r'], 'campaigns')) ? 'active' : '' ?>">
            <i class="bi bi-megaphone"></i>
            <span>Campaigns</span>
        </a>
    <?php elseif (($userRole ?? '') === 'administrative_officer'): ?>
        <a href="index.php?r=admofficer/members" class="mobile-bottom-item <?= (isset($_GET['r']) && str_contains($_GET['r'], 'members')) ? 'active' : '' ?>">
            <i class="bi bi-people"></i>
            <span>Members</span>
        </a>
    <?php else: ?>
        <a href="index.php?r=notification/index" class="mobile-bottom-item <?= (isset($_GET['r']) && str_contains($_GET['r'], 'notification')) ? 'active' : '' ?>">
            <i class="bi bi-bell"></i>
            <span>Alerts</span>
        </a>
    <?php endif; ?>

    <a href="index.php?r=message/index" class="mobile-bottom-item position-relative <?= (isset($_GET['r']) && str_contains($_GET['r'], 'message')) ? 'active' : '' ?>">
        <i class="bi bi-chat-dots"></i>
        <span>Messages</span>
        <?php if (!empty($_msgUnreadCount) && $_msgUnreadCount > 0): ?>
            <span class="badge bg-danger position-absolute" style="top:4px; right:calc(50% - 18px); font-size:0.6rem; padding:2px 4px;"><?= $_msgUnreadCount ?></span>
        <?php endif; ?>
    </a>

    <!-- Menu Button: Toggles the Offcanvas Sidebar Drawer -->
    <button type="button" class="mobile-bottom-item border-0 bg-transparent" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-label="Open navigation menu">
        <i class="bi bi-grid-fill"></i>
        <span>Menu</span>
    </button>
</nav>
<?php else: ?>
</main>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="public/assets/responsive.js"></script>
<script>
document.addEventListener('click', function(e) {
    var drop = document.getElementById('notifDrop');
    var bell = document.getElementById('notifBellBtn');
    if (drop && bell && !bell.contains(e.target)) { drop.classList.remove('show'); }
});

// Auto-close offcanvas drawer on navigation item tap
document.addEventListener('DOMContentLoaded', function() {
    var offcanvasEl = document.getElementById('sidebarMenu');
    if (offcanvasEl) {
        offcanvasEl.querySelectorAll('.nav-link').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth < 768 && typeof bootstrap !== 'undefined') {
                    var instance = bootstrap.Offcanvas.getInstance(offcanvasEl);
                    if (instance) { instance.hide(); }
                }
            });
        });
    }
});
</script>

<?php if ($currentUser): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     GLOBAL: Weekly Weight Log Prompt Modal
     Shown once per calendar week after a workout session is completed.
     Triggered by: window.showWeightLogPromptIfNeeded()
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="prgWeightLogModal" tabindex="-1"
     aria-labelledby="prgWeightLogModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content" style="background:linear-gradient(145deg,#0f172a,#1e293b);border:1px solid rgba(255,255,255,.1);border-radius:20px;overflow:hidden;">

            <!-- Header -->
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,.07);padding:20px 24px 14px;">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div style="width:32px;height:32px;border-radius:9px;background:rgba(99,102,241,.25);display:flex;align-items:center;justify-content:center;">
                            <i class="bi bi-graph-up-arrow" style="color:#818cf8;"></i>
                        </div>
                        <h6 class="modal-title mb-0 text-white fw-bold" id="prgWeightLogModalLabel" style="font-size:.9rem;">
                            🎉 Great workout! Log your weight?
                        </h6>
                    </div>
                    <small class="text-muted" style="font-size:.72rem;">Once-a-week check-in to track your progress</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Skip"></button>
            </div>

            <!-- Body -->
            <div class="modal-body" style="padding:20px 24px;">
                <!-- Weight input -->
                <label class="text-muted mb-2 d-block" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.8px;">
                    Current Body Weight
                </label>
                <div class="d-flex gap-2 mb-4">
                    <input type="number" id="prgModalWeightInput"
                           step="0.1" min="20" max="500"
                           placeholder="e.g. 74.5"
                           class="form-control"
                           style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.18);color:#f1f5f9;border-radius:10px;font-size:1.1rem;font-weight:600;text-align:center;">
                    <span class="align-self-center text-muted fw-bold" style="font-size:.9rem;">kg</span>
                </div>

                <!-- Goal selector -->
                <label class="text-muted mb-2 d-block" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.8px;">
                    Fitness Goal
                </label>
                <div class="d-flex gap-2 mb-1" id="prgModalGoalBtns">
                    <button type="button" onclick="prgModalSelectGoal('bulking')" data-goal="bulking"
                            class="prgm-goal-btn flex-1"
                            style="flex:1;padding:8px 4px;border-radius:10px;font-size:.75rem;font-weight:700;border:1.5px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:#94a3b8;cursor:pointer;transition:all .2s;">
                        📈 Bulking
                    </button>
                    <button type="button" onclick="prgModalSelectGoal('cutting')" data-goal="cutting"
                            class="prgm-goal-btn flex-1"
                            style="flex:1;padding:8px 4px;border-radius:10px;font-size:.75rem;font-weight:700;border:1.5px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:#94a3b8;cursor:pointer;transition:all .2s;">
                        📉 Cutting
                    </button>
                    <button type="button" onclick="prgModalSelectGoal('maintaining')" data-goal="maintaining"
                            class="prgm-goal-btn flex-1"
                            style="flex:1;padding:8px 4px;border-radius:10px;font-size:.75rem;font-weight:700;border:1.5px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:#94a3b8;cursor:pointer;transition:all .2s;">
                        ➡️ Maintain
                    </button>
                </div>
                <small class="text-muted d-block mb-2" style="font-size:.68rem;" id="prgModalGoalHint"></small>

                <!-- Error -->
                <div id="prgModalError" class="text-danger" style="font-size:.78rem;display:none;"></div>
            </div>

            <!-- Footer -->
            <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,.07);padding:14px 24px;gap:8px;">
                <button type="button" class="btn btn-sm btn-link text-muted" data-bs-dismiss="modal"
                        style="font-size:.78rem;">Skip for now</button>
                <button type="button" id="prgModalSaveBtn"
                        onclick="prgModalSaveWeight()"
                        style="background:linear-gradient(135deg,#6366f1,#818cf8);color:#fff;border:none;border-radius:10px;padding:9px 22px;font-weight:700;font-size:.82rem;cursor:pointer;transition:opacity .2s;">
                    <i class="bi bi-save me-1"></i>Save Weight
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const MODAL_GOAL_COLORS = {
        bulking:    { text:'#22c55e', bg:'rgba(34,197,94,.18)',   border:'rgba(34,197,94,.5)'  },
        cutting:    { text:'#f97316', bg:'rgba(249,115,22,.18)',  border:'rgba(249,115,22,.5)' },
        maintaining:{ text:'#3b82f6', bg:'rgba(59,130,246,.18)', border:'rgba(59,130,246,.5)' },
    };
    const MODAL_GOAL_HINTS = {
        bulking:     'Building mass — tracking weight trending up ↗',
        cutting:     'Losing fat — tracking weight trending down ↘',
        maintaining: 'Staying stable — tracking small fluctuations ↔',
    };

    let _modalGoal = (window.PRG && window.PRG.goal) ? window.PRG.goal : null;

    // Pre-select goal if already set
    document.addEventListener('DOMContentLoaded', function() {
        if (_modalGoal) prgModalSelectGoal(_modalGoal);
    });

    window.prgModalSelectGoal = function(goal) {
        _modalGoal = goal;
        document.querySelectorAll('.prgm-goal-btn').forEach(function(btn) {
            const g = btn.dataset.goal;
            const c = MODAL_GOAL_COLORS[g];
            if (g === goal) {
                btn.style.borderColor  = c.border;
                btn.style.background   = c.bg;
                btn.style.color        = c.text;
            } else {
                btn.style.borderColor  = 'rgba(255,255,255,.12)';
                btn.style.background   = 'rgba(255,255,255,.04)';
                btn.style.color        = '#94a3b8';
            }
        });
        const hint = document.getElementById('prgModalGoalHint');
        if (hint) hint.textContent = MODAL_GOAL_HINTS[goal] || '';
    };

    window.prgModalSaveWeight = function() {
        const inputEl  = document.getElementById('prgModalWeightInput');
        const errEl    = document.getElementById('prgModalError');
        const saveBtn  = document.getElementById('prgModalSaveBtn');
        const weightKg = parseFloat(inputEl ? inputEl.value : 0);

        if (!weightKg || weightKg <= 0 || weightKg > 500) {
            if (errEl) { errEl.textContent = 'Please enter a valid weight between 20 and 500 kg.'; errEl.style.display = 'block'; }
            return;
        }
        if (errEl) errEl.style.display = 'none';
        if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Saving…'; }

        fetch('index.php?r=member/saveWeight', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ weight_kg: weightKg, goal_type: _modalGoal })
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                const modalEl = document.getElementById('prgWeightLogModal');
                const modal   = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modal.hide();
                // Brief success toast if possible
                if (typeof showToast === 'function') {
                    showToast('Weight logged! 💪', 'success');
                }
                // Refresh dashboard progress card data without full reload (soft)
                setTimeout(function() { location.reload(); }, 800);
            } else {
                if (errEl)  { errEl.textContent = d.error || 'Failed to save.'; errEl.style.display = 'block'; }
                if (saveBtn){ saveBtn.disabled = false; saveBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save Weight'; }
            }
        })
        .catch(function(err) {
            console.error(err);
            if (errEl)  { errEl.textContent = 'Network error. Please try again.'; errEl.style.display = 'block'; }
            if (saveBtn){ saveBtn.disabled = false; saveBtn.innerHTML = '<i class="bi bi-save me-1"></i>Save Weight'; }
        });
    };
})();
</script>
<?php endif; ?>

<script>
// Global anti-duplicate submission & slow connection spinner
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            // If form has already been submitted, prevent multiple submits
            if (form.dataset.submitting === 'true') {
                e.preventDefault();
                return false;
            }
            
            // Do not lock forms that are marked data-no-lock
            if (form.dataset.noLock === 'true') return;

            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn) {
                form.dataset.submitting = 'true';
                const originalText = submitBtn.innerHTML || submitBtn.value;
                submitBtn.disabled = true;
                if (submitBtn.tagName === 'BUTTON') {
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...';
                } else {
                    submitBtn.value = 'Processing...';
                }

                // Safety timeout: re-enable after 15 seconds in case of validation interruption
                setTimeout(function() {
                    delete form.dataset.submitting;
                    submitBtn.disabled = false;
                    if (submitBtn.tagName === 'BUTTON') {
                        submitBtn.innerHTML = originalText;
                    } else {
                        submitBtn.value = originalText;
                    }
                }, 15000);
            }
        });
    });
});
</script>
</body>
</html>
