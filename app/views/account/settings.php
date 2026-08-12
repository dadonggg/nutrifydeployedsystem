<?php
declare(strict_types=1);
$pageTitle = 'Account & Profile Settings';
require __DIR__ . '/../partials/header.php';

// Fallback helper for initial initials avatar
$fullname = $user['fullname'] ?? 'User';
$parts = explode(' ', trim($fullname));
$initials = strtoupper(substr($parts[0] ?? 'U', 0, 1) . substr($parts[count($parts)-1] ?? '', 0, 1));
if (strlen($initials) === 1) {
    $initials = strtoupper(substr($fullname, 0, 2));
}
$avatarUrl = !empty($user['profile_picture_url']) ? 'public/' . ltrim($user['profile_picture_url'], '/') : null;
?>

<style>
.account-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid var(--nf-border);
    box-shadow: 0 4px 20px rgba(27,107,42,.06);
    overflow: hidden;
}
.account-header {
    background: linear-gradient(135deg, #0e1c12 0%, #164a20 60%, #1B6B2A 100%);
    padding: 2.2rem 2rem;
    color: #fff;
    position: relative;
}
.avatar-preview-lg {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #ffffff;
    box-shadow: 0 8px 24px rgba(0,0,0,.25);
    background: linear-gradient(135deg, #1B6B2A 0%, #2E8B3E 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    font-weight: 800;
    color: #ffffff;
}
.upload-btn-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}
.btn-photo-action {
    border-radius: 10px;
    padding: 0.6rem 1.2rem;
    font-size: 0.9rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}
.crop-preview-box {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto;
    border: 3px solid #1B6B2A;
    box-shadow: 0 4px 14px rgba(27,107,42,.2);
}
.webcam-video-box {
    width: 100%;
    max-width: 440px;
    height: 330px;
    background: #000000;
    border-radius: 12px;
    object-fit: cover;
    margin: 0 auto;
    display: block;
    box-shadow: 0 4px 18px rgba(0,0,0,0.3);
}

/* ── Document Upload Zones ─────────────────────────────── */
.doc-upload-zone {
    border: 2px dashed rgba(27,107,42,.3);
    border-radius: 14px;
    padding: 1.5rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.25s ease;
    background: rgba(27,107,42,.02);
    position: relative;
}
.doc-upload-zone:hover,
.doc-upload-zone.dragover {
    border-color: #1B6B2A;
    background: rgba(27,107,42,.06);
    box-shadow: 0 0 0 4px rgba(27,107,42,.08);
    transform: translateY(-1px);
}
.doc-upload-zone .zone-icon {
    font-size: 2rem;
    color: rgba(27,107,42,.4);
    margin-bottom: .5rem;
    transition: color 0.2s;
}
.doc-upload-zone:hover .zone-icon { color: #1B6B2A; }
.doc-upload-zone .zone-text {
    font-size: .82rem;
    color: #6b8a6b;
    line-height: 1.5;
}
.doc-upload-zone .zone-hint {
    font-size: .72rem;
    color: #aaa;
    margin-top: .25rem;
}
.doc-upload-zone input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}
.doc-file-preview {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .75rem 1rem;
    background: rgba(27,107,42,.04);
    border-radius: 10px;
    border: 1px solid rgba(27,107,42,.15);
    margin-top: .75rem;
}
.doc-file-thumb {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    object-fit: cover;
    flex-shrink: 0;
}
.doc-file-icon {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    background: rgba(27,107,42,.12);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #1B6B2A;
    flex-shrink: 0;
}
.doc-upload-progress {
    height: 4px;
    border-radius: 2px;
    background: rgba(27,107,42,.15);
    margin-top: .5rem;
    overflow: hidden;
    display: none;
}
.doc-upload-progress .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #1B6B2A, #4CAF50);
    border-radius: 2px;
    width: 0;
    transition: width 0.3s;
}
.doc-upload-status {
    font-size: .75rem;
    font-weight: 600;
    margin-top: .35rem;
}
.doc-card {
    background: #fff;
    border: 1px solid var(--nf-border);
    border-radius: 14px;
    padding: 1.4rem;
    position: relative;
    transition: box-shadow 0.2s;
}
.doc-card:hover { box-shadow: 0 4px 16px rgba(27,107,42,.1); }
.doc-card-header {
    display: flex;
    align-items: center;
    gap: .6rem;
    margin-bottom: 1rem;
    padding-bottom: .75rem;
    border-bottom: 1px solid var(--nf-border);
}
.cert-slot {
    border: 1px solid rgba(27,107,42,.15);
    border-radius: 12px;
    padding: 1rem;
    background: rgba(27,107,42,.02);
    position: relative;
}
.cert-slot + .cert-slot { margin-top: .75rem; }
.last-updated-badge {
    font-size: .68rem;
    color: #999;
    display: flex;
    align-items: center;
    gap: .25rem;
}

/* Profile photo drag zone */
.photo-drop-zone {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    border: 3px dashed rgba(27,107,42,.4);
    cursor: pointer;
    overflow: hidden;
    position: relative;
    margin: 0 auto;
    transition: all 0.25s;
    background: rgba(27,107,42,.05);
    display: flex;
    align-items: center;
    justify-content: center;
}
.photo-drop-zone:hover,
.photo-drop-zone.dragover {
    border-color: #1B6B2A;
    background: rgba(27,107,42,.1);
}
.photo-drop-zone img {
    width: 100%; height: 100%; object-fit: cover;
    position: absolute; inset: 0;
}
.photo-drop-zone .photo-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.45);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #fff;
    opacity: 0;
    transition: opacity 0.2s;
    font-size: .75rem;
    gap: .3rem;
}
.photo-drop-zone:hover .photo-overlay { opacity: 1; }
.photo-drop-zone input[type="file"] {
    position: absolute; inset: 0;
    opacity: 0; cursor: pointer; width: 100%; height: 100%;
}
</style>

<div class="container-fluid max-w-1000 py-3">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="bi bi-person-gear text-success me-2"></i>Account & Profile Settings</h1>
            <p class="text-muted mb-0">Manage your profile picture, personal information, and platform credentials.</p>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 rounded-3 mb-4 shadow-sm">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 rounded-3 mb-4 shadow-sm">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Left Column: Profile Picture Card -->
        <div class="col-lg-5">
            <div class="account-card">
                <div class="account-header text-center">
                    <div class="d-flex justify-content-center mb-3">
                        <?php if ($avatarUrl): ?>
                            <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" class="avatar-preview-lg" id="currentAvatarImg">
                        <?php else: ?>
                            <div class="avatar-preview-lg" id="currentAvatarFallback"><?= htmlspecialchars($initials) ?></div>
                        <?php endif; ?>
                    </div>
                    <h2 class="h5 fw-bold mb-1 text-white"><?= htmlspecialchars($fullname) ?></h2>
                    <p class="small text-white-50 mb-0"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                    <span class="badge bg-success border border-white mt-2 px-3 py-1"><?= ucfirst(str_replace('_',' ',$user['role'] ?? 'user')) ?></span>
                </div>

                <div class="p-4">
                    <h3 class="h6 fw-bold mb-3"><i class="bi bi-camera-fill text-success me-2"></i>Update Profile Picture</h3>
                    <p class="text-muted small mb-3">
                        Upload a new photo using your camera or pick a file from your device. Supported formats: JPG, PNG, WEBP (Max 5MB).
                    </p>

                    <form id="avatarUploadForm" action="index.php?r=account/uploadpicture" method="POST" enctype="multipart/form-data">

                        <!-- Hidden File Inputs -->
                        <!-- 1. Main File Input for form submission -->
                        <input type="file" id="mainFileInput" name="profile_picture" accept="image/*" style="display:none;" onchange="handleFileSelect(this)">
                        
                        <!-- 2. Mobile Native Camera Capture Input -->
                        <input type="file" id="cameraNativeInput" accept="image/*" capture="user" style="display:none;" onchange="handleFileSelect(this)">

                        <div class="upload-btn-group mb-3">
                            <button type="button" class="btn btn-outline-success btn-photo-action" onclick="openLiveCamera()">
                                <i class="bi bi-camera-video-fill"></i> Take Photo (Camera)
                            </button>
                            <button type="button" class="btn btn-success btn-photo-action text-white" onclick="document.getElementById('mainFileInput').click()">
                                <i class="bi bi-upload"></i> Choose File
                            </button>
                        </div>

                        <!-- Preview & Submit Container -->
                        <div id="previewContainer" style="display:none;" class="p-3 border rounded-3 bg-light text-center mb-3">
                            <div class="text-muted small fw-semibold mb-2">Crop Preview</div>
                            <div class="crop-preview-box mb-2">
                                <img id="croppedPreviewImg" src="" style="width:100%;height:100%;object-fit:cover;">
                            </div>
                            <div class="d-flex justify-content-center gap-2">
                                <button type="submit" class="btn btn-success btn-sm px-4 rounded-pill fw-bold">
                                    <i class="bi bi-check-lg me-1"></i> Save Picture
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill" onclick="cancelPreview()">
                                    Cancel
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column: Personal Information -->
        <div class="col-lg-7">
            <div class="account-card p-4">
                <h3 class="h6 fw-bold mb-4 pb-2 border-bottom"><i class="bi bi-person-text text-success me-2"></i>Personal Details</h3>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label text-muted small fw-semibold mb-1">Full Name</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" readonly>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label text-muted small fw-semibold mb-1">Email Address</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label text-muted small fw-semibold mb-1">Role</label>
                        <input type="text" class="form-control bg-light" value="<?= ucfirst(str_replace('_',' ',$user['role'] ?? '')) ?>" readonly>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label text-muted small fw-semibold mb-1">Height (cm)</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars((string)($user['height_cm'] ?? 'N/A')) ?>" readonly>
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label text-muted small fw-semibold mb-1">Weight (kg)</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars((string)($user['weight_kg'] ?? 'N/A')) ?>" readonly>
                    </div>
                    <div class="col-12 mt-4">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="d-flex align-items-center gap-2 text-success fw-bold small mb-1">
                                <i class="bi bi-shield-check"></i> Account Verification
                            </div>
                            <div class="small text-muted">
                                Status: <?= !empty($user['is_verified']) ? '<span class="text-success fw-semibold">Verified Member ✓</span>' : '<span class="text-warning fw-semibold">Pending Verification</span>' ?>
                            </div>
                        </div>
                          <!-- Documents & Certifications Section -->
            <?php
            $showDocs = false;
            $appPosition = '';
            
            if (in_array($user['role'], ['trainer', 'maintenance', 'fitness_trainer', 'maintenance_officer'], true)) {
                $showDocs = true;
                $appPosition = in_array($user['role'], ['trainer', 'fitness_trainer'], true) ? 'trainer' : 'maintenance';
            } elseif ($staffApp && in_array($staffApp['status'], ['pending', 'approved'], true)) {
                $showDocs = true;
                $appPosition = $staffApp['application_type'];
            }
            ?>
            
            <?php if ($showDocs): ?>
            <div class="account-card p-4 mt-4" id="docs-section">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <h3 class="h6 fw-bold mb-0"><i class="bi bi-file-earmark-check text-success me-2"></i>Documents &amp; Certifications</h3>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 small">Professional Credentials</span>
                </div>
                <p class="text-muted small mb-4">Upload your professional credentials. Drag &amp; drop files or click the zone to browse. All uploads are private and only visible to gym owners.</p>

                <div class="row g-4">

                    <!-- ── Profile Photo Upload Card ── -->
                    <?php if ($appPosition === 'trainer'): ?>
                    <div class="col-12">
                        <div class="doc-card">
                            <div class="doc-card-header">
                                <div style="width:32px;height:32px;border-radius:8px;background:rgba(27,107,42,.1);display:flex;align-items:center;justify-content:center;">
                                    <i class="bi bi-person-circle text-success"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small">Profile Photo</div>
                                    <div class="text-muted" style="font-size:.72rem;">Shown to members on your trainer card</div>
                                </div>
                            </div>
                            <div class="row g-3 align-items-center">
                                <div class="col-auto">
                                    <form id="avatarUploadFormDocs" action="index.php?r=account/uploadpicture" method="POST" enctype="multipart/form-data">
                                        <div class="photo-drop-zone" id="photoDropZone" title="Drag &amp; drop or click to change photo"
                                             ondragover="photoZoneDrag(event,true)" ondragleave="photoZoneDrag(event,false)" ondrop="photoZoneDrop(event)">
                                            <?php if ($avatarUrl): ?>
                                                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Profile" id="photoPreviewImg">
                                            <?php else: ?>
                                                <div id="photoInitials" style="font-size:2rem;font-weight:800;color:#1B6B2A;"><?= htmlspecialchars($initials) ?></div>
                                            <?php endif; ?>
                                            <div class="photo-overlay">
                                                <i class="bi bi-camera-fill fs-5"></i>
                                                <span>Change Photo</span>
                                            </div>
                                            <input type="file" name="profile_picture" id="photoFileInputDocs" accept="image/jpeg,image/png,image/webp"
                                                   onchange="handlePhotoSelectDocs(this)">
                                        </div>
                                    </form>
                                </div>
                                <div class="col">
                                    <div class="fw-semibold small mb-1"><?= htmlspecialchars($user['fullname'] ?? '') ?></div>
                                    <div class="text-muted small mb-2">JPG, PNG, WEBP · Max 5MB</div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button class="btn btn-success btn-sm" onclick="document.getElementById('photoFileInputDocs').click()" type="button">
                                            <i class="bi bi-upload me-1"></i>Upload Photo
                                        </button>
                                        <?php if ($avatarUrl): ?>
                                        <form action="index.php?r=account/uploadpicture" method="POST" class="d-inline">
                                            <input type="hidden" name="remove_photo" value="1">
                                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Remove your profile photo?')">
                                                <i class="bi bi-trash me-1"></i>Remove
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                    <div id="photoDocStatus" class="doc-upload-status mt-2" style="display:none;"></div>
                                    <div id="photoDocProgress" class="doc-upload-progress mt-2" style="width:100%;">
                                        <div class="progress-fill" id="photoDocProgressFill"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($appPosition === 'trainer'): ?>
                    <!-- ── Certifications Card (multiple) ── -->
                    <div class="col-12">
                        <div class="doc-card">
                            <div class="doc-card-header">
                                <div style="width:32px;height:32px;border-radius:8px;background:rgba(27,107,42,.1);display:flex;align-items:center;justify-content:center;">
                                    <i class="bi bi-award text-success"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold small">Certifications</div>
                                    <div class="text-muted" style="font-size:.72rem;">Upload all your professional credentials</div>
                                </div>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="addCertSlot()">
                                    <i class="bi bi-plus-circle me-1"></i>Add Certification
                                </button>
                            </div>

                            <div id="certSlotsContainer">
                            <?php if (!empty($allCerts)): ?>
                                <?php foreach ($allCerts as $cert): ?>
                                <div class="cert-slot" id="cert-saved-<?= $cert['id'] ?>">
                                    <div class="d-flex align-items-start gap-2">
                                        <?php
                                            $ext = strtolower(pathinfo($cert['doc_path'] ?? '', PATHINFO_EXTENSION));
                                            $isImg = in_array($ext, ['jpg','jpeg','png','webp']);
                                        ?>
                                        <?php if ($isImg && !empty($cert['doc_path'])): ?>
                                            <img src="public/<?= htmlspecialchars($cert['doc_path']) ?>" class="doc-file-thumb" alt="cert">
                                        <?php else: ?>
                                            <div class="doc-file-icon"><i class="bi bi-file-earmark-pdf"></i></div>
                                        <?php endif; ?>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="fw-semibold small text-truncate"><?= htmlspecialchars(basename($cert['doc_path'] ?? 'Certification')) ?></div>
                                            <?php if (!empty($cert['specialization'])): ?>
                                                <div class="text-muted small"><?= htmlspecialchars($cert['specialization']) ?></div>
                                            <?php endif; ?>
                                            <div class="last-updated-badge mt-1">
                                                <i class="bi bi-clock"></i>
                                                Updated <?= !empty($cert['updated_at']) ? date('M d, Y', strtotime($cert['updated_at'])) : 'N/A' ?>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1 flex-shrink-0">
                                            <?php if (!empty($cert['doc_path'])): ?>
                                            <a href="public/<?= htmlspecialchars($cert['doc_path']) ?>" target="_blank" class="btn btn-outline-info btn-sm py-1 px-2" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2" title="Delete"
                                                    onclick="deleteCert(<?= $cert['id'] ?>, this)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <!-- Replace form -->
                                    <form class="mt-2" action="index.php?r=account/uploaddocument" method="POST" enctype="multipart/form-data"
                                          onsubmit="return handleDocFormSubmit(event, this)">
                                        <input type="hidden" name="doc_type" value="certification">
                                        <input type="hidden" name="specialization" value="<?= htmlspecialchars($cert['specialization'] ?? '') ?>">
                                        <div class="doc-upload-zone" onclick="this.querySelector('input').click()" style="padding:.75rem;">
                                            <input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png" onchange="previewDocFile(event, this)">
                                            <i class="bi bi-arrow-repeat zone-icon" style="font-size:1.2rem;"></i>
                                            <div class="zone-text">Replace file</div>
                                        </div>
                                        <div class="doc-upload-progress mt-1"><div class="progress-fill"></div></div>
                                        <div class="doc-upload-status"></div>
                                        <div class="d-flex justify-content-end mt-1">
                                            <button class="btn btn-success btn-sm" type="submit"><i class="bi bi-arrow-repeat me-1"></i>Replace</button>
                                        </div>
                                    </form>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Empty state: show one upload zone -->
                                <div class="cert-slot" id="cert-new-0">
                                    <form action="index.php?r=account/uploaddocument" method="POST" enctype="multipart/form-data"
                                          onsubmit="return handleDocFormSubmit(event, this)">
                                        <input type="hidden" name="doc_type" value="certification">
                                        <div class="mb-2">
                                            <input type="text" class="form-control form-control-sm" name="specialization" placeholder="Specialization (e.g. Strength & Conditioning)">
                                        </div>
                                        <div class="doc-upload-zone" onclick="this.querySelector('input[type=file]').click()"
                                             ondragover="zoneDrag(event,true,this)" ondragleave="zoneDrag(event,false,this)" ondrop="zoneDrop(event,this)">
                                            <input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png" onchange="previewDocFile(event, this)">
                                            <i class="bi bi-cloud-upload zone-icon"></i>
                                            <div class="zone-text"><strong>Drag &amp; drop</strong> or <span class="text-success">click to upload</span></div>
                                            <div class="zone-hint">PDF, JPG, PNG · Max 10MB</div>
                                        </div>
                                        <div class="doc-upload-progress mt-1"><div class="progress-fill"></div></div>
                                        <div class="doc-upload-status"></div>
                                        <div class="d-flex justify-content-end mt-2">
                                            <button class="btn btn-success btn-sm" type="submit"><i class="bi bi-upload me-1"></i>Upload</button>
                                        </div>
                                    </form>
                                </div>
                            <?php endif; ?>
                            </div><!-- /certSlotsContainer -->

                            <!-- Hidden template for new cert slots -->
                            <template id="certSlotTemplate">
                                <div class="cert-slot" id="cert-new-__IDX__">
                                    <form action="index.php?r=account/uploaddocument" method="POST" enctype="multipart/form-data"
                                          onsubmit="return handleDocFormSubmit(event, this)">
                                        <input type="hidden" name="doc_type" value="certification">
                                        <div class="d-flex align-items-start gap-2 mb-2">
                                            <input type="text" class="form-control form-control-sm flex-grow-1" name="specialization" placeholder="Specialization (e.g. ISSA Certified, CPR/AED)">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="removeCertSlot(this)" title="Remove this slot">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                        <div class="doc-upload-zone" onclick="this.querySelector('input[type=file]').click()"
                                             ondragover="zoneDrag(event,true,this)" ondragleave="zoneDrag(event,false,this)" ondrop="zoneDrop(event,this)">
                                            <input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png" onchange="previewDocFile(event, this)">
                                            <i class="bi bi-cloud-upload zone-icon"></i>
                                            <div class="zone-text"><strong>Drag &amp; drop</strong> or <span class="text-success">click to upload</span></div>
                                            <div class="zone-hint">PDF, JPG, PNG · Max 10MB</div>
                                        </div>
                                        <div class="doc-upload-progress mt-1"><div class="progress-fill"></div></div>
                                        <div class="doc-upload-status"></div>
                                        <div class="d-flex justify-content-end mt-2">
                                            <button class="btn btn-success btn-sm" type="submit"><i class="bi bi-upload me-1"></i>Upload Certification</button>
                                        </div>
                                    </form>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- ── Resume/CV Card ── -->
                    <div class="col-12">
                        <div class="doc-card">
                            <div class="doc-card-header">
                                <div style="width:32px;height:32px;border-radius:8px;background:rgba(27,107,42,.1);display:flex;align-items:center;justify-content:center;">
                                    <i class="bi bi-file-earmark-person text-success"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small">Resume / CV</div>
                                    <div class="text-muted" style="font-size:.72rem;">Your professional work history</div>
                                </div>
                                <?php if (!empty($userDocs['resume']['updated_at'])): ?>
                                <div class="last-updated-badge ms-auto">
                                    <i class="bi bi-clock"></i> Updated <?= date('M d, Y', strtotime($userDocs['resume']['updated_at'])) ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($userDocs['resume']['doc_path'])): ?>
                            <div class="doc-file-preview mb-3">
                                <?php
                                    $rExt = strtolower(pathinfo($userDocs['resume']['doc_path'], PATHINFO_EXTENSION));
                                    $rIsImg = in_array($rExt, ['jpg','jpeg','png','webp']);
                                ?>
                                <?php if ($rIsImg): ?>
                                    <img src="public/<?= htmlspecialchars($userDocs['resume']['doc_path']) ?>" class="doc-file-thumb" alt="Resume">
                                <?php else: ?>
                                    <div class="doc-file-icon"><i class="bi bi-file-earmark-text"></i></div>
                                <?php endif; ?>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold small text-truncate"><?= htmlspecialchars(basename($userDocs['resume']['doc_path'])) ?></div>
                                    <div class="text-muted" style="font-size:.72rem;">Current Resume/CV</div>
                                </div>
                                <div class="d-flex gap-1">
                                    <a href="public/<?= htmlspecialchars($userDocs['resume']['doc_path']) ?>" target="_blank" class="btn btn-outline-info btn-sm py-1 px-2" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2" title="Delete"
                                            onclick="deleteCert(<?= $userDocs['resume']['id'] ?>, this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>

                            <form action="index.php?r=account/uploaddocument" method="POST" enctype="multipart/form-data"
                                  onsubmit="return handleDocFormSubmit(event, this)">
                                <input type="hidden" name="doc_type" value="resume">
                                <div class="doc-upload-zone" id="resumeZone" onclick="this.querySelector('input[type=file]').click()"
                                     ondragover="zoneDrag(event,true,this)" ondragleave="zoneDrag(event,false,this)" ondrop="zoneDrop(event,this)">
                                    <input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" onchange="previewDocFile(event, this)">
                                    <i class="bi bi-cloud-upload zone-icon"></i>
                                    <div class="zone-text"><strong>Drag &amp; drop</strong> or <span class="text-success">click to browse</span></div>
                                    <div class="zone-hint">PDF, JPG, PNG, DOC · Max 10MB</div>
                                </div>
                                <div class="doc-upload-progress mt-2" id="resumeProgress"><div class="progress-fill"></div></div>
                                <div class="doc-upload-status" id="resumeStatus"></div>
                                <div class="d-flex justify-content-end mt-2">
                                    <button class="btn btn-success btn-sm" type="submit"><i class="bi bi-upload me-1"></i><?= !empty($userDocs['resume']['doc_path']) ? 'Replace Resume' : 'Upload Resume' ?></button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php else: ?>
                    <!-- Maintenance / Other role: single certificate upload -->
                    <div class="col-12">
                        <div class="doc-card">
                            <div class="doc-card-header">
                                <div style="width:32px;height:32px;border-radius:8px;background:rgba(27,107,42,.1);display:flex;align-items:center;justify-content:center;">
                                    <i class="bi bi-file-earmark-medical text-success"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small">Certificate / Medical Certificate</div>
                                    <div class="text-muted" style="font-size:.72rem;">Required for your staff role</div>
                                </div>
                                <?php if (!empty($userDocs['medical_certificate']['updated_at'])): ?>
                                <div class="last-updated-badge ms-auto">
                                    <i class="bi bi-clock"></i> Updated <?= date('M d, Y', strtotime($userDocs['medical_certificate']['updated_at'])) ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($userDocs['medical_certificate']['doc_path'])): ?>
                            <div class="doc-file-preview mb-3">
                                <div class="doc-file-icon"><i class="bi bi-file-earmark-check"></i></div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold small text-truncate"><?= htmlspecialchars(basename($userDocs['medical_certificate']['doc_path'])) ?></div>
                                    <div class="text-muted" style="font-size:.72rem;">Current Certificate</div>
                                </div>
                                <div class="d-flex gap-1">
                                    <a href="public/<?= htmlspecialchars($userDocs['medical_certificate']['doc_path']) ?>" target="_blank" class="btn btn-outline-info btn-sm py-1 px-2">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2"
                                            onclick="deleteCert(<?= $userDocs['medical_certificate']['id'] ?>, this)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>

                            <form action="index.php?r=account/uploaddocument" method="POST" enctype="multipart/form-data"
                                  onsubmit="return handleDocFormSubmit(event, this)">
                                <input type="hidden" name="doc_type" value="medical_certificate">
                                <div class="doc-upload-zone" onclick="this.querySelector('input[type=file]').click()"
                                     ondragover="zoneDrag(event,true,this)" ondragleave="zoneDrag(event,false,this)" ondrop="zoneDrop(event,this)">
                                    <input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png" onchange="previewDocFile(event, this)">
                                    <i class="bi bi-cloud-upload zone-icon"></i>
                                    <div class="zone-text"><strong>Drag &amp; drop</strong> or <span class="text-success">click to upload</span></div>
                                    <div class="zone-hint">PDF, JPG, PNG · Max 10MB</div>
                                </div>
                                <div class="doc-upload-progress mt-2"><div class="progress-fill"></div></div>
                                <div class="doc-upload-status"></div>
                                <div class="d-flex justify-content-end mt-2">
                                    <button class="btn btn-success btn-sm" type="submit"><i class="bi bi-upload me-1"></i>Upload Certificate</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Common: Resume/CV for maintenance -->
                    <div class="col-12">
                        <div class="doc-card">
                            <div class="doc-card-header">
                                <div style="width:32px;height:32px;border-radius:8px;background:rgba(27,107,42,.1);display:flex;align-items:center;justify-content:center;">
                                    <i class="bi bi-file-earmark-person text-success"></i>
                                </div>
                                <div>
                                    <div class="fw-bold small">Resume / CV</div>
                                    <div class="text-muted" style="font-size:.72rem;">Your professional work history</div>
                                </div>
                                <?php if (!empty($userDocs['resume']['updated_at'])): ?>
                                <div class="last-updated-badge ms-auto">
                                    <i class="bi bi-clock"></i> Updated <?= date('M d, Y', strtotime($userDocs['resume']['updated_at'])) ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($userDocs['resume']['doc_path'])): ?>
                            <div class="doc-file-preview mb-3">
                                <div class="doc-file-icon"><i class="bi bi-file-earmark-text"></i></div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small text-truncate"><?= htmlspecialchars(basename($userDocs['resume']['doc_path'])) ?></div>
                                    <div class="text-muted" style="font-size:.72rem;">Current Resume</div>
                                </div>
                                <div class="d-flex gap-1">
                                    <a href="public/<?= htmlspecialchars($userDocs['resume']['doc_path']) ?>" target="_blank" class="btn btn-outline-info btn-sm py-1 px-2"><i class="bi bi-eye"></i></a>
                                    <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2" onclick="deleteCert(<?= $userDocs['resume']['id'] ?>, this)"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                            <?php endif; ?>

                            <form action="index.php?r=account/uploaddocument" method="POST" enctype="multipart/form-data"
                                  onsubmit="return handleDocFormSubmit(event, this)">
                                <input type="hidden" name="doc_type" value="resume">
                                <div class="doc-upload-zone" onclick="this.querySelector('input[type=file]').click()"
                                     ondragover="zoneDrag(event,true,this)" ondragleave="zoneDrag(event,false,this)" ondrop="zoneDrop(event,this)">
                                    <input type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" onchange="previewDocFile(event, this)">
                                    <i class="bi bi-cloud-upload zone-icon"></i>
                                    <div class="zone-text"><strong>Drag &amp; drop</strong> or <span class="text-success">click to browse</span></div>
                                    <div class="zone-hint">PDF, JPG, PNG, DOC · Max 10MB</div>
                                </div>
                                <div class="doc-upload-progress mt-2"><div class="progress-fill"></div></div>
                                <div class="doc-upload-status"></div>
                                <div class="d-flex justify-content-end mt-2">
                                    <button class="btn btn-success btn-sm" type="submit"><i class="bi bi-upload me-1"></i><?= !empty($userDocs['resume']['doc_path']) ? 'Replace Resume' : 'Upload Resume' ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                </div><!-- /row -->
            </div><!-- /docs-section -->
            <?php endif; ?>
            </div>
        </div>
    </div>

    </div>

</div>


<!-- ─── Live Camera Modal ─── -->
<div class="modal fade" id="cameraModal" tabindex="-1" aria-labelledby="cameraModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title fw-bold" id="cameraModalLabel"><i class="bi bi-camera-video-fill me-2 text-success"></i>Take Live Photo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="closeCameraStream()"></button>
            </div>
            <div class="modal-body bg-dark text-center p-4">
                <video id="webcamStream" class="webcam-video-box" autoplay playsinline></video>
                <canvas id="snapshotCanvas" style="display:none;"></canvas>
            </div>
            <div class="modal-footer bg-dark border-0 justify-content-center gap-2">
                <button type="button" class="btn btn-success px-4 rounded-pill fw-bold" onclick="capturePhotoFromStream()">
                    <i class="bi bi-camera-fill me-1"></i> Snap Photo
                </button>
                <button type="button" class="btn btn-outline-light px-3 rounded-pill" data-bs-dismiss="modal" onclick="closeCameraStream()">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let mediaStream = null;

function openLiveCamera() {
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480, facingMode: "user" } })
            .then(function(stream) {
                mediaStream = stream;
                const videoEl = document.getElementById('webcamStream');
                videoEl.srcObject = stream;
                const modal = new bootstrap.Modal(document.getElementById('cameraModal'));
                modal.show();
            })
            .catch(function(err) {
                console.warn('Webcam stream error:', err);
                document.getElementById('cameraNativeInput').click();
            });
    } else {
        document.getElementById('cameraNativeInput').click();
    }
}

function closeCameraStream() {
    if (mediaStream) {
        mediaStream.getTracks().forEach(track => track.stop());
        mediaStream = null;
    }
}

function capturePhotoFromStream() {
    const video = document.getElementById('webcamStream');
    const canvas = document.getElementById('snapshotCanvas');
    const context = canvas.getContext('2d');
    const width = video.videoWidth || 640;
    const height = video.videoHeight || 480;
    canvas.width = width;
    canvas.height = height;
    context.drawImage(video, 0, 0, width, height);
    canvas.toBlob(function(blob) {
        const file = new File([blob], "camera_photo.jpg", { type: "image/jpeg" });
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        const mainInput = document.getElementById('mainFileInput');
        mainInput.files = dataTransfer.files;
        handleFileSelect(mainInput);
        closeCameraStream();
        const modalEl = document.getElementById('cameraModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }, 'image/jpeg', 0.9);
}

function handleFileSelect(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (input.id !== 'mainFileInput') {
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        document.getElementById('mainFileInput').files = dataTransfer.files;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('croppedPreviewImg').src = e.target.result;
        document.getElementById('previewContainer').style.display = 'block';
    };
    reader.readAsDataURL(file);
}

function cancelPreview() {
    document.getElementById('mainFileInput').value = '';
    if (document.getElementById('cameraNativeInput')) document.getElementById('cameraNativeInput').value = '';
    document.getElementById('previewContainer').style.display = 'none';
}

/* ─── Document Upload Zone helpers ─────────────────── */
const MAX_DOC_SIZE = 10 * 1024 * 1024; // 10MB
const ALLOWED_EXTS = ['pdf','jpg','jpeg','png','doc','docx'];

function zoneDrag(e, active, zone) {
    e.preventDefault();
    zone.classList.toggle('dragover', active);
}

function zoneDrop(e, zone) {
    e.preventDefault();
    zone.classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files.length) {
        const inp = zone.querySelector('input[type=file]');
        const dt = new DataTransfer();
        dt.items.add(files[0]);
        inp.files = dt.files;
        previewDocFile({ target: inp }, inp);
    }
}

function previewDocFile(e, input) {
    const file = (e.target || input).files && (e.target || input).files[0] ? (e.target || input).files[0] : null;
    if (!file) return;
    const zone = input.closest ? input.closest('.doc-upload-zone') : null;
    if (!zone) return;

    // Validate size
    if (file.size > MAX_DOC_SIZE) {
        showDocStatus(zone, 'error', `File too large (${formatBytes(file.size)}). Max 10MB.`);
        input.value = '';
        return;
    }
    // Validate extension
    const ext = file.name.split('.').pop().toLowerCase();
    if (!ALLOWED_EXTS.includes(ext)) {
        showDocStatus(zone, 'error', `Invalid format (.${ext}). Allowed: PDF, JPG, PNG, DOC.`);
        input.value = '';
        return;
    }

    // Show file info inside the zone
    const isImg = ['jpg','jpeg','png','webp'].includes(ext);
    const iconHtml = isImg
        ? `<img src="${URL.createObjectURL(file)}" style="width:36px;height:36px;border-radius:6px;object-fit:cover;" alt="">`
        : `<div class="doc-file-icon" style="width:36px;height:36px;font-size:1rem;"><i class="bi bi-file-earmark-pdf"></i></div>`;

    zone.innerHTML = `
        <div class="d-flex align-items-center gap-2">
            ${iconHtml}
            <div class="text-start">
                <div class="fw-semibold small text-truncate" style="max-width:180px;">${escHtml(file.name)}</div>
                <div class="text-muted" style="font-size:.72rem;">${formatBytes(file.size)}</div>
            </div>
            <i class="bi bi-check-circle-fill text-success ms-auto"></i>
        </div>
    `;
    // Re-add hidden file input so form can still submit
    const newInp = document.createElement('input');
    newInp.type = 'file';
    newInp.name = input.name;
    newInp.accept = input.accept;
    newInp.style.display = 'none';
    const dt2 = new DataTransfer();
    dt2.items.add(file);
    newInp.files = dt2.files;
    newInp.addEventListener('change', function(ev){ previewDocFile(ev, newInp); });
    zone.appendChild(newInp);
}

function showDocStatus(zone, type, msg) {
    const statusEl = zone.parentElement ? zone.parentElement.querySelector('.doc-upload-status') : null;
    if (!statusEl) return;
    statusEl.style.display = 'block';
    statusEl.innerHTML = type === 'success'
        ? `<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>${escHtml(msg)}</span>`
        : `<span class="text-danger"><i class="bi bi-exclamation-circle-fill me-1"></i>${escHtml(msg)}</span>`;
}

function handleDocFormSubmit(e, form) {
    const inp = form.querySelector('input[type=file]');
    if (!inp || !inp.files || !inp.files[0]) return true; // Let normal submit handle it
    e.preventDefault();

    const progressBar = form.querySelector('.doc-upload-progress');
    const progressFill = form.querySelector('.progress-fill');
    const statusEl = form.querySelector('.doc-upload-status');

    if (progressBar) progressBar.style.display = 'block';
    if (progressFill) progressFill.style.width = '0%';
    if (statusEl) { statusEl.style.display = 'block'; statusEl.innerHTML = '<span class="text-muted"><i class="bi bi-hourglass-split me-1"></i>Uploading...</span>'; }

    const formData = new FormData(form);
    const xhr = new XMLHttpRequest();
    xhr.open('POST', form.action);

    xhr.upload.addEventListener('progress', function(ev) {
        if (ev.lengthComputable && progressFill) {
            const pct = Math.round((ev.loaded / ev.total) * 100);
            progressFill.style.width = pct + '%';
        }
    });

    xhr.addEventListener('load', function() {
        if (progressFill) progressFill.style.width = '100%';
        if (statusEl) statusEl.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Uploaded successfully! Refreshing...</span>';
        setTimeout(() => location.reload(), 1200);
    });

    xhr.addEventListener('error', function() {
        if (statusEl) statusEl.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>Upload failed. Please try again.</span>';
        if (progressBar) progressBar.style.display = 'none';
    });

    xhr.send(formData);
    return false;
}

/* ─── Multiple certification slots ─── */
let certSlotIdx = <?= max(count($allCerts ?? []), 1) ?>;

function addCertSlot() {
    const tmpl = document.getElementById('certSlotTemplate');
    if (!tmpl) return;
    const clone = tmpl.content.cloneNode(true);
    clone.querySelector('[id^="cert-new-"]').id = 'cert-new-' + certSlotIdx;
    document.getElementById('certSlotsContainer').appendChild(clone);
    certSlotIdx++;
}

function removeCertSlot(btn) {
    const slot = btn.closest('.cert-slot');
    if (slot) slot.remove();
}

function deleteCert(docId, btn) {
    if (!confirm('Delete this document? This cannot be undone.')) return;
    const slot = btn ? btn.closest('.cert-slot, .doc-file-preview') : null;
    fetch('index.php?r=account/deletedocument', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: 'doc_id=' + encodeURIComponent(docId)
    }).then(r => r.json()).then(data => {
        if (data.success) {
            if (slot) {
                slot.style.opacity = '0';
                slot.style.transition = 'opacity 0.3s';
                setTimeout(() => { slot.remove(); }, 320);
            } else {
                location.reload();
            }
        } else {
            alert(data.error || 'Failed to delete document.');
        }
    }).catch(() => { location.reload(); });
}

/* ─── Profile photo in docs section ─── */
function photoZoneDrag(e, active) {
    e.preventDefault();
    document.getElementById('photoDropZone').classList.toggle('dragover', active);
}

function photoZoneDrop(e) {
    e.preventDefault();
    document.getElementById('photoDropZone').classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (!file) return;
    const inp = document.getElementById('photoFileInputDocs');
    const dt = new DataTransfer();
    dt.items.add(file);
    inp.files = dt.files;
    handlePhotoSelectDocs(inp);
}

function handlePhotoSelectDocs(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.size > 5 * 1024 * 1024) {
        alert('Image exceeds 5MB limit.');
        return;
    }

    // Preview
    const reader = new FileReader();
    reader.onload = function(ev) {
        const zone = document.getElementById('photoDropZone');
        let img = zone.querySelector('img');
        if (!img) {
            img = document.createElement('img');
            zone.insertBefore(img, zone.firstChild);
        }
        img.src = ev.target.result;
        img.style.display = 'block';
        const initDiv = document.getElementById('photoInitials');
        if (initDiv) initDiv.style.display = 'none';
    };
    reader.readAsDataURL(file);

    // Auto-submit
    const status = document.getElementById('photoDocStatus');
    const progress = document.getElementById('photoDocProgress');
    const fill = document.getElementById('photoDocProgressFill');
    if (status) { status.style.display = 'block'; status.innerHTML = '<span class="text-muted"><i class="bi bi-hourglass-split me-1"></i>Uploading...</span>'; }
    if (progress) progress.style.display = 'block';
    if (fill) fill.style.width = '0%';

    const formData = new FormData(document.getElementById('avatarUploadFormDocs'));
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'index.php?r=account/uploadpicture');
    xhr.upload.addEventListener('progress', function(ev) {
        if (ev.lengthComputable && fill) fill.style.width = Math.round((ev.loaded/ev.total)*100) + '%';
    });
    xhr.addEventListener('load', function() {
        if (fill) fill.style.width = '100%';
        if (status) status.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Photo updated!</span>';
        setTimeout(() => location.reload(), 1000);
    });
    xhr.addEventListener('error', function() {
        if (status) status.innerHTML = '<span class="text-danger">Upload failed.</span>';
    });
    xhr.send(formData);
}

/* ─── Utilities ─── */
function formatBytes(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes/1024).toFixed(1) + ' KB';
    return (bytes/1048576).toFixed(1) + ' MB';
}
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
