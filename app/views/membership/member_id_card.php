<?php
declare(strict_types=1);
$pageTitle = 'My Digital Gym ID Card';
require __DIR__ . '/../partials/header.php';

// Fallback helper for avatar initials
$fullname = $user['fullname'] ?? 'Gym Member';
$parts = explode(' ', trim($fullname));
$initials = strtoupper(substr($parts[0] ?? 'G', 0, 1) . substr($parts[count($parts)-1] ?? '', 0, 1));
if (strlen($initials) === 1) {
    $initials = strtoupper(substr($fullname, 0, 2));
}

$avatarUrl = !empty($user['profile_picture_url']) ? 'public/' . ltrim($user['profile_picture_url'], '/') : null;
$expired = false;
if (!empty($member['expiration_date']) && strtotime($member['expiration_date']) < time()) {
    $expired = true;
}
?>

<div class="container-fluid py-4 max-w-1000">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="bi bi-person-badge text-success me-2"></i>Digital Membership Card</h1>
            <p class="text-muted mb-0">Show this QR code at the reception desk to log your attendance.</p>
        </div>
        <a href="index.php?r=membership/verifycode" class="btn btn-outline-secondary px-3 rounded-pill btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Verify
        </a>
    </div>

    <div class="row g-4 justify-content-center">
        <!-- Digital Card Preview -->
        <div class="col-md-5">
            <div id="idCardContainer" class="shadow-lg mx-auto" style="max-width: 340px; border-radius: 20px; overflow: hidden; border: 1px solid rgba(27,107,42,.15); background: #ffffff;">
                <!-- Header -->
                <div class="p-3 text-white d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #0e1c12 0%, #164a20 60%, #1B6B2A 100%);">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-lightning-fill text-success fs-5"></i>
                        <span class="fw-bold tracking-wide" style="font-size: .85rem; letter-spacing: 0.8px;">NUTRIFY MEMBER</span>
                    </div>
                    <?php if ($expired): ?>
                        <span class="badge bg-danger text-white border border-danger border-opacity-35" style="font-size: .65rem; padding: 4px 8px; border-radius: 12px;">EXPIRED</span>
                    <?php else: ?>
                        <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-35" style="font-size: .65rem; padding: 4px 8px; border-radius: 12px;">ACTIVE</span>
                    <?php endif; ?>
                </div>

                <!-- Main Details -->
                <div class="p-4 text-center">
                    <div class="d-flex justify-content-center mb-3">
                        <?php if ($avatarUrl): ?>
                            <img id="memberIdPhotoImg" src="<?= htmlspecialchars($avatarUrl) ?>" alt="Member Photo" class="shadow-sm" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #1B6B2A;">
                        <?php else: ?>
                            <div class="bg-success text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 100px; height: 100px; border-radius: 50%; font-size: 2.5rem; background: linear-gradient(135deg, #1B6B2A 0%, #2E8B3E 100%); border: 3px solid #1B6B2A;">
                                <?= htmlspecialchars($initials) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <h2 class="h5 fw-bold text-dark mb-1"><?= htmlspecialchars($fullname) ?></h2>
                    <div class="text-success fw-bold font-monospace mb-4" style="font-size: 1rem; letter-spacing: 1.2px;">
                        <?= htmlspecialchars($member['member_id_number'] ?? 'NTF-PENDING') ?>
                    </div>

                    <!-- QR Code Area -->
                    <div class="d-flex justify-content-center p-3 mb-4 bg-light rounded-4" style="width: 140px; height: 140px; margin: 0 auto; border: 1px solid rgba(0,0,0,.04);">
                        <div id="memberCardQr"></div>
                    </div>

                    <!-- Details Table -->
                    <div class="row g-2 border-top pt-3 text-start text-muted" style="font-size: .8rem;">
                        <div class="col-6">
                            <span class="d-block text-uppercase text-secondary" style="font-size: .65rem; font-weight: 600; letter-spacing: 0.3px;">Plan Level</span>
                            <strong class="text-dark"><?= ucfirst(str_replace('_', ' ', $member['payment_type'] ?? 'Standard')) ?></strong>
                        </div>
                        <div class="col-6">
                            <span class="d-block text-uppercase text-secondary" style="font-size: .65rem; font-weight: 600; letter-spacing: 0.3px;">Expiration</span>
                            <strong class="text-dark <?= $expired ? 'text-danger fw-bold' : '' ?>"><?= !empty($member['expiration_date']) ? date('M d, Y', strtotime($member['expiration_date'])) : 'Never' ?></strong>
                        </div>
                        <div class="col-12 mt-2">
                            <span class="d-block text-uppercase text-secondary" style="font-size: .65rem; font-weight: 600; letter-spacing: 0.3px;">Gym Membership Code</span>
                            <code class="text-dark" style="font-size: .75rem;"><?= htmlspecialchars($member['membership_code']) ?></code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info/Export Actions -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 16px;">
                <h3 class="h6 fw-bold mb-3"><i class="bi bi-info-circle text-success me-2"></i>Gym Entry Instructions</h3>
                <ol class="text-muted small ps-3 mb-4" style="line-height: 1.6;">
                    <li>Keep your digital ID card handy when you visit the gym.</li>
                    <li>Scan this card on the tablet/webcam at the entry desk.</li>
                    <li>Ensure your plan remains active to access gym facilities.</li>
                </ol>

                <h3 class="h6 fw-bold mb-3"><i class="bi bi-cloud-download text-success me-2"></i>Save to Device</h3>
                <p class="text-muted small mb-4">Export your Member ID Card as a PNG image to save to your phone's photo library or files.</p>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success fw-bold py-2 rounded-3" onclick="exportCardAsImage()">
                        <i class="bi bi-file-image me-1"></i> Download ID Card (PNG)
                    </button>
                </div>

                <!-- Hidden canvas for generating the PNG file -->
                <canvas id="exportCanvas" style="display:none;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Load QRCode library to render the member QR code -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const qrToken = "<?= esc_attr($member['qr_token'] ?? '') ?>";
    if (qrToken) {
        new QRCode(document.getElementById("memberCardQr"), {
            text: qrToken,
            width: 108,
            height: 108,
            colorDark : "#0e1c12",
            colorLight : "#f8f9fa",
            correctLevel : QRCode.CorrectLevel.H
        });
    }
});

function exportCardAsImage() {
    const canvas = document.getElementById("exportCanvas");
    const ctx = canvas.getContext("2d");

    // Set dimensions for the exported ID card
    canvas.width = 400;
    canvas.height = 550;

    // Background round card shape
    ctx.fillStyle = "#ffffff";
    ctx.beginPath();
    ctx.roundRect(0, 0, 400, 550, 24);
    ctx.fill();

    // Draw header gradient
    const grad = ctx.createLinearGradient(0, 0, 400, 0);
    grad.addColorStop(0, '#0e1c12');
    grad.addColorStop(0.6, '#164a20');
    grad.addColorStop(1, '#1B6B2A');
    ctx.fillStyle = grad;
    
    // Path for rounded top header
    ctx.beginPath();
    ctx.roundRect(0, 0, 400, 70, [24, 24, 0, 0]);
    ctx.fill();

    // Header Text
    ctx.fillStyle = "#ffffff";
    ctx.font = "bold 16px sans-serif";
    ctx.fillText("NUTRIFY GYM MEMBER", 24, 40);

    ctx.fillStyle = "<?= $expired ? '#dc3545' : '#28a745' ?>";
    ctx.font = "bold 12px sans-serif";
    ctx.fillText("<?= $expired ? 'EXPIRED' : 'ACTIVE' ?>", 310, 40);

    // Profile Photo or Initials
    const photoImg = document.getElementById("memberIdPhotoImg");
    if (photoImg && photoImg.complete && photoImg.naturalWidth !== 0) {
        // Draw circular profile image
        ctx.save();
        ctx.beginPath();
        ctx.arc(200, 150, 50, 0, Math.PI * 2);
        ctx.closePath();
        ctx.clip();
        ctx.drawImage(photoImg, 150, 100, 100, 100);
        ctx.restore();

        // Border around photo
        ctx.strokeStyle = "#1B6B2A";
        ctx.lineWidth = 4;
        ctx.beginPath();
        ctx.arc(200, 150, 50, 0, Math.PI * 2);
        ctx.stroke();
    } else {
        // Draw initials placeholder
        ctx.fillStyle = "#1B6B2A";
        ctx.beginPath();
        ctx.arc(200, 150, 50, 0, Math.PI * 2);
        ctx.fill();

        ctx.fillStyle = "#ffffff";
        ctx.font = "bold 36px sans-serif";
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.fillText("<?= esc_attr($initials) ?>", 200, 150);
    }

    // Name & Member ID Number
    ctx.textAlign = "center";
    ctx.fillStyle = "#212529";
    ctx.font = "bold 20px sans-serif";
    ctx.fillText("<?= esc_attr($fullname) ?>", 200, 235);

    ctx.fillStyle = "#1B6B2A";
    ctx.font = "bold 16px Courier, monospace";
    ctx.fillText("<?= esc_attr($member['member_id_number'] ?? 'NTF-PENDING') ?>", 200, 265);

    // Generate QR Code on Canvas
    // Find the canvas/image inside the qrcode container
    const qrImg = document.querySelector("#memberCardQr img");
    if (qrImg) {
        ctx.drawImage(qrImg, 130, 290, 140, 140);
    }

    // Horizontal Separator Line
    ctx.strokeStyle = "#e9ecef";
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(30, 460);
    ctx.lineTo(370, 460);
    ctx.stroke();

    // Details Grid
    ctx.textAlign = "left";
    ctx.fillStyle = "#6c757d";
    ctx.font = "bold 11px sans-serif";
    ctx.fillText("MEMBERSHIP PLAN", 35, 485);
    ctx.fillText("EXPIRATION DATE", 220, 485);

    ctx.fillStyle = "#212529";
    ctx.font = "bold 13px sans-serif";
    ctx.fillText("<?= esc_attr(ucfirst(str_replace('_', ' ', $member['payment_type'] ?? 'Standard'))) ?>", 35, 510);
    ctx.fillText("<?= esc_attr(!empty($member['expiration_date']) ? date('M d, Y', strtotime($member['expiration_date'])) : 'Never') ?>", 220, 510);

    // Trigger file download
    const dataUrl = canvas.toDataURL("image/png");
    const link = document.createElement("a");
    link.download = "Nutrify_ID_Card_" + "<?= esc_attr($member['member_id_number'] ?? 'Pending') ?>" + ".png";
    link.href = dataUrl;
    link.click();
}
</script>
<?php require __DIR__ . '/../partials/footer.php'; ?>
