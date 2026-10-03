<?php
declare(strict_types=1);
$pageTitle = 'Find a Coach';
require __DIR__ . '/../partials/header.php';
?>

<div class="mb-4">
    <h1 class="h3 mb-1"><i class="bi bi-search text-success me-2"></i>Find a Coach</h1>
    <p class="text-muted">Browse our qualified fitness trainers and request a direct coaching session.</p>
</div>

<?php if (empty($trainers)): ?>
    <div class="card p-5 text-center">
        <i class="bi bi-people display-3 text-muted mb-3"></i>
        <h3 class="h5 text-muted">No Coaches Available</h3>
        <p class="text-muted mb-0">There are currently no active coaches registered in this gym.</p>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($trainers as $t): ?>
            <?php
            $avatar = !empty($t['profile_picture_url']) ? 'public/' . ltrim($t['profile_picture_url'], '/') : null;
            $nameParts = explode(' ', trim($t['fullname'] ?? 'Trainer'));
            $initials = strtoupper(substr($nameParts[0] ?? 'T', 0, 1) . substr($nameParts[count($nameParts)-1] ?? '', 0, 1));
            
            $ratingVal = floatval($t['avg_rating'] ?? 0);
            $reviewCount = intval($t['review_count'] ?? 0);
            $clientCount = intval($t['client_count'] ?? 0);
            $expertiseTags = !empty($t['expertise']) ? explode(',', $t['expertise']) : [];
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-success">
                    <div class="card-body text-center d-flex flex-column align-items-center">
                        <div class="mb-3 position-relative">
                            <?php if ($avatar): ?>
                                <img src="<?= htmlspecialchars($avatar) ?>" alt="Coach Avatar" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover; border: 3px solid var(--nf-green);">
                            <?php else: ?>
                                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold fs-3" style="width: 100px; height: 100px; border: 3px solid var(--nf-green);">
                                    <?= htmlspecialchars($initials) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <h3 class="h5 fw-bold mb-1"><?= htmlspecialchars($t['fullname']) ?></h3>
                        <p class="text-success small mb-2"><i class="bi bi-building me-1"></i>Hired at <?= htmlspecialchars($t['gym_name'] ?? 'Nutrify Gym') ?></p>
                        
                        <!-- Ratings & Response Rate -->
                        <div class="d-flex align-items-center justify-content-center gap-1 mb-2">
                            <div class="text-warning">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi <?= $i <= round($ratingVal) ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="small fw-semibold ms-1"><?= number_format($ratingVal, 1) ?></span>
                            <span class="text-muted small">(<?= $reviewCount ?> reviews)</span>
                        </div>

                        <?php if (isset($t['feedback_response_rate'])): ?>
                        <div class="mb-3">
                            <span class="badge bg-info-subtle text-dark border border-info-subtle px-2 py-1 small" title="Feedback Response Rate">
                                <i class="bi bi-chat-heart-fill text-info me-1"></i><strong><?= number_format((float)$t['feedback_response_rate'], 1) ?>%</strong> Response Rate
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Expertise Tags -->
                        <div class="mb-3 text-center flex-grow-1">
                            <?php if (empty($expertiseTags)): ?>
                                <span class="badge bg-light text-muted border">General Fitness</span>
                            <?php else: ?>
                                <?php foreach ($expertiseTags as $tag): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-20 mb-1 me-1">
                                        <?= htmlspecialchars(trim($tag)) ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="w-100 border-top pt-3 mt-auto d-flex justify-content-between align-items-center small">
                            <span class="text-muted"><i class="bi bi-people me-1"></i><?= $clientCount ?> clients trained</span>
                            <a href="index.php?r=fitness/profile&trainer_id=<?= $t['employee_id'] ?>" class="btn btn-sm btn-success px-3">
                                View Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../partials/footer.php'; ?>
