<?php
declare(strict_types=1);
$pageTitle = 'Scan Gym Member ID';
require __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid py-3 max-w-1000">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="bi bi-qr-code-scan text-success me-2"></i>QR Code Attendance Scanner</h1>
            <p class="text-muted mb-0">Scan a member's digital ID card using your camera to log their attendance.</p>
        </div>
        <a href="index.php?r=admofficer/attendance" class="btn btn-outline-secondary px-3 rounded-pill btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Log
        </a>
    </div>

    <div class="row g-4">
        <!-- Scanner Column -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 16px;">
                <div class="card-header bg-dark text-white p-3 d-flex align-items-center justify-content-between">
                    <span class="fw-semibold small"><i class="bi bi-camera-video me-2 text-success"></i>Live Scanner Feed</span>
                    <span class="badge bg-secondary" id="scannerStatus">Initializing...</span>
                </div>
                <div class="card-body p-0 bg-black position-relative" style="aspect-ratio: 4/3; min-height: 280px; display: flex; align-items: center; justify-content: center;">
                    <!-- Scanner Viewport -->
                    <div id="reader" style="width: 100%; height: 100%;"></div>
                    
                    <!-- Scanner Laser Overlay -->
                    <div id="scannerLaser" class="position-absolute w-100 bg-success bg-opacity-25" style="height: 2px; top: 0; left: 0; display: none; box-shadow: 0 0 12px #28a745, 0 0 4px #28a745; animation: laserMove 2s infinite linear;"></div>
                </div>
                <div class="card-footer bg-light p-3">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success fw-bold py-2 rounded-3" id="btnToggleCamera" onclick="toggleCamera()">
                            <i class="bi bi-play-fill me-1"></i> Start Camera
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Result / Member Details Column -->
        <div class="col-lg-6">
            <!-- Instructions placeholder -->
            <div class="card border-0 shadow-sm h-100 p-4 text-center d-flex flex-column align-items-center justify-content-center" id="scanInstructions" style="border-radius: 16px; border: 2px dashed rgba(27,107,42,.15) !important;">
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle mb-3" style="width: 64px; height: 64px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-qr-code fs-3"></i>
                </div>
                <h2 class="h5 fw-bold text-dark">Awaiting Member QR Scan</h2>
                <p class="text-muted small max-w-320">Hold the member's digital ID card in front of your camera. Make sure the QR code is centered and well lit.</p>
            </div>

            <!-- Scanned Member Details Card (Hidden initially) -->
            <div class="card border-0 shadow-sm overflow-hidden d-none" id="memberDetailsCard" style="border-radius: 16px; border: 1px solid rgba(27,107,42,.15) !important;">
                <!-- Header -->
                <div class="p-3 text-white d-flex align-items-center justify-content-between" id="memberHeader" style="background: linear-gradient(135deg, #0e1c12 0%, #164a20 60%, #1B6B2A 100%);">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-person-badge text-success fs-5"></i>
                        <span class="fw-bold tracking-wide" style="font-size: .85rem; letter-spacing: 0.5px;">MEMBER IDENTIFIED</span>
                    </div>
                    <span class="badge" id="memberStatusBadge">ACTIVE</span>
                </div>
                
                <div class="card-body p-4 text-center">
                    <div class="d-flex justify-content-center mb-3">
                        <img id="memberPhoto" src="" alt="Profile" class="shadow-sm" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #1B6B2A; display: none;">
                        <div id="memberInitials" class="bg-success text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 100px; height: 100px; border-radius: 50%; font-size: 2.5rem; background: linear-gradient(135deg, #1B6B2A 0%, #2E8B3E 100%); border: 3px solid #1B6B2A;">
                            U
                        </div>
                    </div>
                    
                    <h3 class="h5 fw-bold text-dark mb-1" id="memberName">John Doe</h3>
                    <div class="text-success fw-bold font-monospace mb-4" id="memberIdNumber" style="font-size: .95rem; letter-spacing: 1px;">
                        NTF-2026-00001
                    </div>
                    
                    <div class="row g-3 text-start bg-light p-3 rounded-3 mb-4" style="font-size: .85rem;">
                        <div class="col-6">
                            <span class="text-muted d-block small">Membership Plan</span>
                            <span class="fw-bold text-dark" id="memberPlan">Student Monthly</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small">Expiry Date</span>
                            <span class="fw-bold text-dark" id="memberExpiry">Sep 12, 2026</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small">Card Issued</span>
                            <span class="fw-bold text-dark" id="memberIssued">Aug 12, 2026</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block small">Check-in Status</span>
                            <span class="fw-bold text-dark" id="checkinStatusText">Not Logged</span>
                        </div>
                    </div>

                    <!-- Alert message container -->
                    <div id="alertMessage" class="alert d-none mb-4 py-2 px-3 small rounded-3" role="alert"></div>

                    <!-- Actions -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success fw-bold py-2" id="btnLogAttendance" onclick="logScannedAttendance()">
                            <i class="bi bi-calendar-check me-1"></i> Log Attendance Check-In
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetScannerResult()">
                            <i class="bi bi-x-circle me-1"></i> Scan Next Member
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles for Scanning Laser animation -->
<style>
@keyframes laserMove {
    0% { top: 0%; }
    50% { top: 100%; }
    100% { top: 0%; }
}
#reader video {
    object-fit: cover !important;
    width: 100% !important;
    height: 100% !important;
}
</style>

<!-- Load html5-qrcode library from CDN -->
<script src="https://unpkg.com/html5-qrcode"></script>

<script>
let html5QrcodeScanner = null;
let isCameraActive = false;
let lastScannedToken = "";
let currentScannedMemberId = 0;

document.addEventListener("DOMContentLoaded", function() {
    const statusBadge = document.getElementById("scannerStatus");
    statusBadge.className = "badge bg-secondary";
    statusBadge.textContent = "Camera Standby";
});

function toggleCamera() {
    const btn = document.getElementById("btnToggleCamera");
    const statusBadge = document.getElementById("scannerStatus");
    const laser = document.getElementById("scannerLaser");

    if (isCameraActive) {
        // Stop Camera
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop().then(() => {
                isCameraActive = false;
                btn.className = "btn btn-success fw-bold py-2 rounded-3";
                btn.innerHTML = '<i class="bi bi-play-fill me-1"></i> Start Camera';
                statusBadge.className = "badge bg-secondary";
                statusBadge.textContent = "Camera Standby";
                laser.style.display = "none";
            }).catch(err => console.error("Error stopping camera", err));
        }
    } else {
        // Start Camera
        isCameraActive = true;
        btn.className = "btn btn-danger fw-bold py-2 rounded-3";
        btn.innerHTML = '<i class="bi bi-stop-fill me-1"></i> Stop Camera';
        statusBadge.className = "badge bg-warning text-dark";
        statusBadge.textContent = "Starting Feed...";
        laser.style.display = "block";

        html5QrcodeScanner = new Html5Qrcode("reader");
        html5QrcodeScanner.start(
            { facingMode: "environment" },
            {
                fps: 10,
                qrbox: function(width, height) {
                    const side = Math.min(width, height) * 0.7;
                    return { width: side, height: side };
                }
            },
            onScanSuccess,
            onScanError
        ).then(() => {
            statusBadge.className = "badge bg-success";
            statusBadge.textContent = "Scanning Active";
        }).catch(err => {
            console.error("Camera start failed", err);
            alert("Could not start camera feed. Please ensure camera permission is granted.");
            isCameraActive = false;
            btn.className = "btn btn-success fw-bold py-2 rounded-3";
            btn.innerHTML = '<i class="bi bi-play-fill me-1"></i> Start Camera';
            statusBadge.className = "badge bg-danger";
            statusBadge.textContent = "Failed";
            laser.style.display = "none";
        });
    }
}

function onScanSuccess(decodedText, decodedResult) {
    if (decodedText === lastScannedToken) return; // Prevent spamming
    lastScannedToken = decodedText;

    // Trigger success audio cue (optional beep)
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = "sine";
        osc.frequency.setValueAtTime(880, audioCtx.currentTime); // A5 note
        gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.15);
    } catch(e) {}

    // Look up the member token via AJAX
    fetch("index.php?r=admofficer/qrlookup", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: "token=" + encodeURIComponent(decodedText)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showMemberDetails(data);
        } else {
            showScanErrorAlert(data.error || "Failed to recognize QR code.");
        }
    })
    .catch(err => {
        console.error("AJAX Error", err);
        showScanErrorAlert("Server error lookup failed. Check database connection.");
    });
}

function onScanError(err) {
    // Suppress console spam for frame analysis errors
}

function showMemberDetails(member) {
    document.getElementById("scanInstructions").classList.add("d-none");
    const card = document.getElementById("memberDetailsCard");
    card.classList.remove("d-none");

    currentScannedMemberId = member.member_id;

    // Set text fields
    document.getElementById("memberName").textContent = member.fullname;
    document.getElementById("memberIdNumber").textContent = member.member_id_number;
    document.getElementById("memberPlan").textContent = member.plan;
    document.getElementById("memberExpiry").textContent = member.expiration_date;
    document.getElementById("memberIssued").textContent = member.issue_date;

    // Photo/Initials
    const photo = document.getElementById("memberPhoto");
    const initials = document.getElementById("memberInitials");
    if (member.photo) {
        photo.src = member.photo;
        photo.style.display = "block";
        initials.style.display = "none";
    } else {
        photo.style.display = "none";
        initials.style.display = "flex";
        // Initials first letters
        const names = member.fullname.split(" ");
        initials.textContent = ((names[0] ? names[0][0] : "") + (names[1] ? names[1][0] : "")).toUpperCase() || "U";
    }

    // Status Badge & Header Background
    const header = document.getElementById("memberHeader");
    const badge = document.getElementById("memberStatusBadge");
    const logBtn = document.getElementById("btnLogAttendance");

    if (member.status === "Expired") {
        header.style.background = "linear-gradient(135deg, #420f0f 0%, #7d1717 60%, #b22222 100%)";
        badge.className = "badge bg-danger border border-danger border-opacity-35";
        badge.textContent = "EXPIRED";
        
        // Disable check in
        logBtn.disabled = true;
        logBtn.className = "btn btn-secondary fw-bold py-2";
        logBtn.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i> Attendance Blocked (Expired)';
        
        showAlertMessage("danger", "This membership has EXPIRED. Please renew the membership plan before allowing attendance check-in.");
    } else {
        header.style.background = "linear-gradient(135deg, #0e1c12 0%, #164a20 60%, #1B6B2A 100%)";
        badge.className = "badge bg-success bg-opacity-20 text-success border border-success border-opacity-35";
        badge.textContent = "ACTIVE";

        if (member.already_checked_in) {
            logBtn.disabled = true;
            logBtn.className = "btn btn-info text-white fw-bold py-2";
            logBtn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Already Checked-In Today';
            showAlertMessage("info", "This member has already been checked in today.");
        } else {
            logBtn.disabled = false;
            logBtn.className = "btn btn-success fw-bold py-2";
            logBtn.innerHTML = '<i class="bi bi-calendar-check me-1"></i> Log Attendance Check-In';
            document.getElementById("alertMessage").classList.add("d-none");
        }
    }
}

function logScannedAttendance() {
    if (currentScannedMemberId <= 0) return;

    fetch("index.php?r=admofficer/logattendance", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: "member_id=" + encodeURIComponent(currentScannedMemberId)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlertMessage("success", data.message);
            // Update button status
            const logBtn = document.getElementById("btnLogAttendance");
            logBtn.disabled = true;
            logBtn.className = "btn btn-info text-white fw-bold py-2";
            logBtn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Checked-In Successfully';
        } else {
            showAlertMessage("danger", data.error || "Failed to log attendance.");
        }
    })
    .catch(err => {
        console.error("Attendance Log Error", err);
        showAlertMessage("danger", "Server failed to log attendance record.");
    });
}

function showScanErrorAlert(msg) {
    document.getElementById("scanInstructions").classList.add("d-none");
    const card = document.getElementById("memberDetailsCard");
    card.classList.remove("d-none");

    currentScannedMemberId = 0;
    
    // Hide details
    document.getElementById("memberName").textContent = "Unrecognized QR Code";
    document.getElementById("memberIdNumber").textContent = "UNKNOWN TOKEN";
    document.getElementById("memberPhoto").style.display = "none";
    
    const initials = document.getElementById("memberInitials");
    initials.style.display = "flex";
    initials.textContent = "?";
    initials.style.background = "linear-gradient(135deg, #444 0%, #222 100%)";

    const header = document.getElementById("memberHeader");
    const badge = document.getElementById("memberStatusBadge");
    const logBtn = document.getElementById("btnLogAttendance");

    header.style.background = "linear-gradient(135deg, #222 0%, #444 100%)";
    badge.className = "badge bg-secondary";
    badge.textContent = "INVALID";

    logBtn.disabled = true;
    logBtn.className = "btn btn-secondary fw-bold py-2";
    logBtn.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i> Log Attendance Disabled';

    showAlertMessage("danger", msg);
}

function showAlertMessage(type, msg) {
    const alertBox = document.getElementById("alertMessage");
    alertBox.className = `alert alert-${type} mb-4 py-2 px-3 small rounded-3`;
    alertBox.textContent = msg;
    alertBox.classList.remove("d-none");
}

function resetScannerResult() {
    lastScannedToken = "";
    currentScannedMemberId = 0;
    document.getElementById("memberDetailsCard").classList.add("d-none");
    document.getElementById("scanInstructions").classList.remove("d-none");
}
</script>
<?php require __DIR__ . '/../partials/footer.php'; ?>
