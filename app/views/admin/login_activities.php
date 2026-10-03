<?php
/**
 * Admin - Login Activities View
 * Security Feature: Logging and Monitoring
 * Displays all user login/logout activities for security auditing
 */

$activities = $activities ?? [];
$stats = $stats ?? [];
$pageTitle = 'Login Activities - Admin';
require __DIR__ . '/../partials/header.php';
?>
<style>
    .activity-success { color: #28a745; }
    .activity-failed { color: #dc3545; }
    .activity-logout { color: #6c757d; }
    .activity-otp { color: #007bff; }
    .stat-card {
        border-left: 4px solid;
        margin-bottom: 1rem;
    }
    .stat-card.success { border-left-color: #28a745; }
    .stat-card.failed { border-left-color: #dc3545; }
    .stat-card.logout { border-left-color: #6c757d; }
    .stat-card.otp { border-left-color: #007bff; }
</style>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-shield-lock"></i> Login Activity Monitor</h2>
            <a href="index.php?r=home/index" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <?php
            $statTypes = [
                'login_success' => ['icon' => 'check-circle-fill', 'label' => 'Successful Logins', 'class' => 'success'],
                'login_failed' => ['icon' => 'x-circle-fill', 'label' => 'Failed Logins', 'class' => 'failed'],
                'logout' => ['icon' => 'box-arrow-right', 'label' => 'Logouts', 'class' => 'logout'],
                'otp_sent' => ['icon' => 'envelope-fill', 'label' => 'OTP Sent', 'class' => 'otp'],
            ];

            foreach ($statTypes as $type => $config):
                $count = 0;
                foreach ($stats as $stat) {
                    if ($stat['activity_type'] === $type) {
                        $count += (int)$stat['count'];
                    }
                }
            ?>
            <div class="col-md-3">
                <div class="card stat-card <?= htmlspecialchars($config['class']) ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1"><?= htmlspecialchars($config['label']) ?></h6>
                                <h3 class="mb-0"><?= $count ?></h3>
                                <small class="text-muted">Last 7 days</small>
                            </div>
                            <i class="bi bi-<?= htmlspecialchars($config['icon']) ?> fs-1 text-<?= htmlspecialchars($config['class']) ?>"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Activity Log Table -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Recent Activity Log</h5>
            </div>
            <div class="card-body">
                <?php if (empty($activities)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No login activities recorded yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Activity</th>
                                    <th>IP Address</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activities as $activity): ?>
                                <tr>
                                    <td>
                                        <small><?= htmlspecialchars($activity['created_at']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($activity['fullname']): ?>
                                            <strong><?= htmlspecialchars($activity['fullname']) ?></strong>
                                            <br><small class="text-muted"><?= htmlspecialchars($activity['role'] ?? 'N/A') ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Unknown User</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($activity['email']) ?></td>
                                    <td>
                                        <?php
                                        $activityType = $activity['activity_type'];
                                        $iconMap = [
                                            'login_success' => ['icon' => 'check-circle-fill', 'class' => 'success', 'label' => 'Login Success'],
                                            'login_failed' => ['icon' => 'x-circle-fill', 'class' => 'failed', 'label' => 'Login Failed'],
                                            'logout' => ['icon' => 'box-arrow-right', 'class' => 'logout', 'label' => 'Logout'],
                                            'otp_sent' => ['icon' => 'envelope-fill', 'class' => 'otp', 'label' => 'OTP Sent'],
                                            'otp_failed' => ['icon' => 'envelope-x', 'class' => 'failed', 'label' => 'OTP Failed'],
                                        ];
                                        $config = $iconMap[$activityType] ?? ['icon' => 'question-circle', 'class' => 'secondary', 'label' => $activityType];
                                        ?>
                                        <span class="activity-<?= htmlspecialchars($config['class']) ?>">
                                            <i class="bi bi-<?= htmlspecialchars($config['icon']) ?>"></i>
                                            <?= htmlspecialchars($config['label']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code><?= htmlspecialchars($activity['ip_address'] ?? 'N/A') ?></code>
                                    </td>
                                    <td>
                                        <?php if ($activity['failure_reason']): ?>
                                            <span class="badge bg-danger">
                                                <?= htmlspecialchars($activity['failure_reason']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($activity['user_agent']): ?>
                                            <small class="text-muted d-block" title="<?= htmlspecialchars($activity['user_agent']) ?>">
                                                <?= htmlspecialchars(substr($activity['user_agent'], 0, 50)) ?>...
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Security Alerts -->
        <?php
        // Check for suspicious activity (multiple failed attempts)
        $suspiciousEmails = [];
        foreach ($activities as $activity) {
            if ($activity['activity_type'] === 'login_failed') {
                $email = $activity['email'];
                if (!isset($suspiciousEmails[$email])) {
                    $suspiciousEmails[$email] = 0;
                }
                $suspiciousEmails[$email]++;
            }
        }
        $suspiciousEmails = array_filter($suspiciousEmails, fn($count) => $count >= 3);
        ?>

        <?php if (!empty($suspiciousEmails)): ?>
        <div class="alert alert-warning mt-4">
            <h5><i class="bi bi-exclamation-triangle-fill"></i> Security Alert</h5>
            <p>The following emails have multiple failed login attempts:</p>
            <ul>
                <?php foreach ($suspiciousEmails as $email => $count): ?>
                    <li><strong><?= htmlspecialchars($email) ?></strong>: <?= $count ?> failed attempts</li>
                <?php endforeach; ?>
            </ul>
            <small class="text-muted">Consider implementing account lockout or CAPTCHA for these accounts.</small>
        </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../partials/footer.php'; ?>
