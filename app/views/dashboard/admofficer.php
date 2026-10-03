<?php
declare(strict_types=1);
$pageTitle = 'Administrative Officer Dashboard';
require __DIR__ . '/../partials/header.php';
?>

<div class="mb-4">
    <h1 class="h3 mb-1">Administrative Officer Dashboard</h1>
    <p class="text-muted mb-0">Verify membership registrations, assign trainers, and manage gym members.</p>
</div>

<div class="row g-2 g-sm-3 mb-3 mb-md-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card p-2.5 p-sm-3 h-100">
            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <div class="stat-icon bg-warning bg-opacity-15 text-warning"><i class="bi bi-person-plus"></i></div>
                <div class="min-w-0 flex-grow-1"><div class="text-muted small text-truncate">Pending Apps</div><div class="fw-bold stat-value text-dark"><?= $pendingCount ?></div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card p-2.5 p-sm-3 h-100">
            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <div class="stat-icon bg-success bg-opacity-15 text-success"><i class="bi bi-people-fill"></i></div>
                <div class="min-w-0 flex-grow-1"><div class="text-muted small text-truncate">Gym Members</div><div class="fw-bold stat-value text-dark"><?= count($gymMembers) ?></div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card p-2.5 p-sm-3 h-100">
            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <div class="stat-icon bg-info bg-opacity-15 text-info"><i class="bi bi-person-badge"></i></div>
                <div class="min-w-0 flex-grow-1"><div class="text-muted small text-truncate">Employees</div><div class="fw-bold stat-value text-dark"><?= count($employees) ?></div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card p-2.5 p-sm-3 h-100">
            <div class="d-flex align-items-center gap-2 gap-sm-3">
                <div class="stat-icon bg-danger bg-opacity-15 text-danger"><i class="bi bi-person-hearts"></i></div>
                <div class="min-w-0 flex-grow-1"><div class="text-muted small text-truncate">Fitness Reqs</div><div class="fw-bold stat-value text-dark"><?= (int)($fitnessStats['pending'] ?? 0) ?></div></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header px-3 py-2"><h2 class="h6 mb-0"><i class="bi bi-person-plus me-2"></i>Membership Applications</h2></div>
            <div class="card-body">
                <p class="small text-muted">Review membership forms from fitness enthusiasts. Verify, assign trainers, confirm payment, and generate membership codes.</p>
                <a href="index.php?r=admofficer/memberships" class="btn btn-primary btn-sm"><i class="bi bi-arrow-right"></i> Review Applications</a>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header px-3 py-2 bg-danger bg-opacity-10">
                <h2 class="h6 mb-0">
                    <i class="bi bi-person-hearts me-2"></i>Fitness Training Requests
                    <?php if ((int)($fitnessStats['pending'] ?? 0) > 0): ?>
                    <span class="badge bg-danger ms-2"><?= (int)$fitnessStats['pending'] ?> Pending</span>
                    <?php endif; ?>
                </h2>
            </div>
            <div class="card-body">
                <p class="small text-muted">Review fitness training requests from gym members and assign qualified trainers to provide personalized coaching.</p>
                <div class="d-flex gap-2 mb-3">
                    <div class="flex-fill text-center p-2 bg-light rounded">
                        <div class="fw-bold text-warning"><?= (int)($fitnessStats['pending'] ?? 0) ?></div>
                        <small class="text-muted">Pending</small>
                    </div>
                    <div class="flex-fill text-center p-2 bg-light rounded">
                        <div class="fw-bold text-info"><?= (int)($fitnessStats['assigned'] ?? 0) ?></div>
                        <small class="text-muted">Assigned</small>
                    </div>
                    <div class="flex-fill text-center p-2 bg-light rounded">
                        <div class="fw-bold text-success"><?= (int)($fitnessStats['completed'] ?? 0) ?></div>
                        <small class="text-muted">Completed</small>
                    </div>
                </div>
                <a href="index.php?r=admofficer/fitnessRequests" class="btn btn-danger btn-sm">
                    <i class="bi bi-arrow-right"></i> Manage Requests
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header px-3 py-2"><h2 class="h6 mb-0"><i class="bi bi-person-badge me-2"></i>Employee List</h2></div>
            <div class="card-body">
                <p class="small text-muted">View all hired employees (trainers and maintenance) and their availability for assignment.</p>
                <a href="index.php?r=admofficer/employees" class="btn btn-info btn-sm"><i class="bi bi-arrow-right"></i> View Employees</a>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header px-3 py-2"><h2 class="h6 mb-0"><i class="bi bi-people-fill me-2"></i>Gym Members</h2></div>
            <div class="card-body">
                <p class="small text-muted">View all verified gym members and their membership codes.</p>
                <a href="index.php?r=admofficer/members" class="btn btn-success btn-sm"><i class="bi bi-arrow-right"></i> View Members</a>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header px-3 py-2"><h2 class="h6 mb-0"><i class="bi bi-calendar-check me-2"></i>Attendance Log</h2></div>
            <div class="card-body">
                <p class="small text-muted">Monitor member check-ins and attendance records.</p>
                <a href="index.php?r=admofficer/attendance" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-right"></i> View Log</a>
            </div>
        </div>
    </div>
</div>

<!-- Campaign Enrollment Manager (Builder) -->
<div id="cbOfficerCampaignContainer" class="mt-4 mb-4"></div>

<script>
// Database-backed campaign from PHP controller
const DB_CAMPAIGN = <?php echo json_encode($builderCampaign ?? null); ?>;

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

function renderOfficerCampaignManager() {
  const container = document.getElementById('cbOfficerCampaignContainer');
  if (!container) return;

  let state = { published: false, registrations: [] };

  if (typeof DB_CAMPAIGN !== 'undefined' && DB_CAMPAIGN) {
    state.published = (DB_CAMPAIGN.status === 'active' || DB_CAMPAIGN.status === 'published');
    state.title = DB_CAMPAIGN.title;
    state.desc = DB_CAMPAIGN.description;
    state.start = DB_CAMPAIGN.start_date;
    state.end = DB_CAMPAIGN.end_date;
    
    // Parse coach from description prefix
    state.coach = '';
    if (state.desc && state.desc.startsWith('Coach: ')) {
      const nlIdx = state.desc.indexOf('\n');
      if (nlIdx > 0) {
        state.coach = state.desc.substring(7, nlIdx);
        state.desc = state.desc.substring(nlIdx + 1);
      }
    }

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
  }

  if (!state || !state.published || !state.title) {
    container.innerHTML = '';
    return;
  }

  const regs = state.registrations || [];
  const searchEl = document.getElementById('cbOfficerSearch');
  const searchVal = (searchEl ? searchEl.value : '').toLowerCase();

  const filtered = regs.filter(r => 
    r.name.toLowerCase().includes(searchVal) ||
    r.email.toLowerCase().includes(searchVal) ||
    r.pack.toLowerCase().includes(searchVal)
  );

  // Calculate metrics
  const totalRegistered = regs.length;
  const confirmedPaidCount = regs.filter(r => r.status === 'Paid').length;
  const pendingCount = totalRegistered - confirmedPaidCount;
  
  const totalCollected = regs.reduce((sum, r) => r.status === 'Paid' ? sum + r.price : sum, 0);
  const expectedTotal = regs.reduce((sum, r) => sum + r.price, 0);

  // Revenue breakdown by pack type
  const packs = {};
  regs.forEach(r => {
    if (!packs[r.pack]) packs[r.pack] = { collected: 0, pending: 0 };
    if (r.status === 'Paid') packs[r.pack].collected += r.price;
    else packs[r.pack].pending += r.price;
  });

  let packsHtml = '';
  Object.keys(packs).forEach(p => {
    packsHtml += `
      <div class="col-md-4">
        <div class="p-3 border rounded text-start">
          <div class="fw-bold text-dark small mb-1">${esc(p)}</div>
          <div class="text-success small">Collected: <strong>₱${Number(packs[p].collected).toLocaleString()}</strong></div>
          <div class="text-warning small">Pending: <strong>₱${Number(packs[p].pending).toLocaleString()}</strong></div>
        </div>
      </div>
    `;
  });
  if (packsHtml === '') {
    packsHtml = '<div class="col-12 text-center text-muted small py-3">No package statistics available.</div>';
  }

  let tableRowsHtml = '';
  filtered.forEach(r => {
    const isPending = r.status !== 'Paid';
    const statusClass = isPending ? 'bg-warning text-dark' : 'bg-success text-white';
    const actionButton = isPending 
      ? `<button class="btn btn-sm btn-success fw-bold px-3" onclick="confirmPayment(${r.id})">Confirm</button>`
      : `<span class="text-success fw-bold"><i class="bi bi-check-circle-fill"></i> Paid</span>`;
    
    // Check if it's an online payment
    const isOnline = r.payment_mode === 'online';
    const paymentModeBadge = isOnline ? `<span class="badge bg-info ms-1">Online</span>` : `<span class="badge bg-secondary ms-1">Cash</span>`;

    tableRowsHtml += `
      <tr>
        <td>
          <div class="fw-bold text-dark">${esc(r.name)}</div>
          <div class="small text-muted">${esc(r.email)}</div>
        </td>
        <td>${esc(r.pack)}</td>
        <td>
            ₱${Number(r.price).toLocaleString()}
            <br>${paymentModeBadge}
        </td>
        <td><span class="badge ${statusClass} px-2.5 py-1.5" style="border-radius:20px;">${r.status}</span></td>
        <td>${esc(r.schedule)}</td>
        <td class="small text-muted">${esc(r.date)}</td>
        <td>${actionButton}</td>
      </tr>
    `;
  });

  if (tableRowsHtml === '') {
    tableRowsHtml = `
      <tr>
        <td colspan="7" class="text-center text-muted py-4">
          <i class="bi bi-inbox fs-3 d-block mb-1"></i>
          No registration records found.
        </td>
      </tr>
    `;
  }

  const pricingTiersHtml = (state.pricing || []).map(p => `
    <div class="d-flex justify-content-between align-items-center p-2 rounded mb-2 border text-start" style="background:#f9fafb;">
      <div>
        <div class="fw-bold text-dark small">${esc(p.name)}</div>
        <div class="text-muted" style="font-size:0.75rem;">${esc(p.duration)} • ${p.sessions} sessions</div>
      </div>
      <div class="fw-bold text-success">₱${Number(p.price).toLocaleString()}</div>
    </div>
  `).join('');

  container.innerHTML = `
    <div class="card border-0 shadow-sm mb-4" style="border-radius:12px; overflow:hidden; border:1px solid #e5e7eb;">
      <div class="card-header bg-dark text-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0 fw-bold"><i class="bi bi-megaphone-fill text-success me-2"></i>Active Campaign: ${esc(state.title)}</h5>
        <span class="badge bg-success fs-7 px-3 py-1.5" style="border-radius:20px;">Live Program</span>
      </div>
      <div class="card-body p-4">
        
        <!-- Metrics Row -->
        <div class="row g-4 mb-4">
          <div class="col-md-3">
            <div class="p-3 border rounded text-start" style="background:#f0fdf4; border-color:#bbf7d0!important;">
              <div class="text-muted small">Total Collected</div>
              <div class="fs-4 fw-extrabold text-success">₱${Number(totalCollected).toLocaleString()}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 border rounded text-start" style="background:#f8fafc;">
              <div class="text-muted small">Expected Total</div>
              <div class="fs-4 fw-extrabold text-dark">₱${Number(expectedTotal).toLocaleString()}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 border rounded text-start" style="background:#f0fdf4;">
              <div class="text-muted small">Confirmed Paid</div>
              <div class="fs-4 fw-extrabold text-success">${confirmedPaidCount} members</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="p-3 border rounded text-start" style="background:#fff7ed; border-color:#ffedd5!important;">
              <div class="text-muted small">Pending Payment</div>
              <div class="fs-4 fw-extrabold text-warning">${pendingCount} members</div>
            </div>
          </div>
        </div>

        <!-- Revenue Breakdown Grouped by Pack -->
        <h6 class="fw-bold text-dark mb-3 text-start"><i class="bi bi-bar-chart-fill text-success me-2"></i>Revenue by Package Type</h6>
        <div class="row g-3 mb-4">
          ${packsHtml}
        </div>

        <!-- Registered Members Table -->
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
          <h6 class="fw-bold text-dark mb-0"><i class="bi bi-people-fill text-primary me-2"></i>Registration Records</h6>
          <div style="max-width:300px; width:100%;">
            <input type="text" class="form-control form-control-sm" id="cbOfficerSearch" placeholder="Search name or package..." value="${esc(searchVal)}" oninput="renderOfficerCampaignManager()">
          </div>
        </div>
        <div class="table-responsive mb-4">
          <table class="table table-hover align-middle">
            <thead>
              <tr class="table-light">
                <th>Member</th>
                <th>Package</th>
                <th>Price / Method</th>
                <th>Status</th>
                <th>Schedule</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              ${tableRowsHtml}
            </tbody>
          </table>
        </div>

        <!-- Campaign Details Read-only Section -->
        <hr class="my-4">
        <h6 class="fw-bold text-dark mb-3 text-start"><i class="bi bi-info-circle-fill text-info me-2"></i>Program Configuration Details</h6>
        <div class="row g-4">
          <div class="col-md-7 text-start">
            <div class="row g-3">
              <div class="col-6">
                <small class="text-muted d-block">Coach / Instructor</small>
                <strong>${esc(state.coach)}</strong>
              </div>
              <div class="col-6">
                <small class="text-muted d-block">Max Size Limit</small>
                <strong>${state.maxSize || 20} members</strong>
              </div>
              <div class="col-6">
                <small class="text-muted d-block">Start Date</small>
                <strong>${esc(state.start)}</strong>
              </div>
              <div class="col-6">
                <small class="text-muted d-block">End Date</small>
                <strong>${esc(state.end)}</strong>
              </div>
            </div>
            <div class="mt-3">
              <small class="text-muted d-block">Description</small>
              <p class="small text-secondary mb-0">${state.desc ? esc(state.desc).replace(/\n/g, '<br>') : 'No description.'}</p>
            </div>
          </div>
          <div class="col-md-5 col-12">
            <small class="text-muted d-block mb-2 text-start">Configured Pricing Tiers</small>
            ${pricingTiersHtml}
          </div>
        </div>

      </div>
    </div>
  `;
}

function confirmPayment(regId) {
  if (typeof DB_CAMPAIGN !== 'undefined' && DB_CAMPAIGN) {
    if (confirm(`Confirm payment for registration #${regId}?`)) {
      const body = new FormData();
      body.append('campaign_id', DB_CAMPAIGN.id);
      body.append('registration_id', regId);

      fetch('index.php?r=admofficer/confirmpayment', { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            alert('Payment confirmed successfully!');
            location.reload();
          } else {
            alert('Error: ' + (data.error || 'Failed to confirm payment'));
          }
        })
        .catch(() => alert('Network error. Please try again.'));
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  renderOfficerCampaignManager();
});
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
