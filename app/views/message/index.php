<?php
declare(strict_types=1);
$pageTitle = 'Messages — Fitness Coaching';
require __DIR__ . '/../partials/header.php';

$userRole = $user['role'] ?? 'customer';
?>

<style>
.chat-wrapper {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid var(--nf-border);
    box-shadow: 0 4px 24px rgba(27,107,42,.08);
    display: flex;
    height: calc(100vh - 140px);
    min-height: 520px;
    overflow: hidden;
}

/* Left Sidebar: Threads */
.chat-threads-sidebar {
    width: 320px;
    border-right: 1px solid var(--nf-border);
    background: #f8fbf8;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}
.threads-header {
    padding: 1.2rem;
    border-bottom: 1px solid var(--nf-border);
    background: #ffffff;
}
.threads-list {
    flex: 1;
    overflow-y: auto;
}
.thread-item {
    padding: 0.9rem 1.1rem;
    border-bottom: 1px solid rgba(27,107,42,.06);
    display: flex;
    align-items: center;
    gap: 0.8rem;
    cursor: pointer;
    transition: background 0.15s;
    text-decoration: none !important;
    color: inherit;
}
.thread-item:hover, .thread-item.active {
    background: #eef7ef;
}
.thread-item.active {
    border-left: 4px solid #1B6B2A;
}

/* User Avatar Thumbnail */
.avatar-thumb {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
    background: linear-gradient(135deg, #1B6B2A 0%, #2E8B3E 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(27,107,42,.2);
}
.avatar-thumb-sm {
    width: 34px;
    height: 34px;
    font-size: 0.85rem;
}

/* Right Panel: Chat Room */
.chat-room {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #ffffff;
}
.room-header {
    padding: 1rem 1.4rem;
    border-bottom: 1px solid var(--nf-border);
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.messages-container {
    flex: 1;
    padding: 1.5rem;
    overflow-y: auto;
    background: #fcfdfe;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Message Bubbles */
.msg-row {
    display: flex;
    gap: 0.6rem;
    max-width: 75%;
}
.msg-row.outbound {
    align-self: flex-end;
    flex-direction: row-reverse;
}
.msg-row.inbound {
    align-self: flex-start;
}
.msg-bubble {
    padding: 0.75rem 1.1rem;
    border-radius: 18px;
    font-size: 0.92rem;
    line-height: 1.45;
    word-break: break-word;
}
.msg-row.outbound .msg-bubble {
    background: linear-gradient(135deg, #1B6B2A 0%, #2E8B3E 100%);
    color: #ffffff;
    border-bottom-right-radius: 4px;
    box-shadow: 0 2px 8px rgba(27,107,42,.25);
}
.msg-row.inbound .msg-bubble {
    background: #f0f4f1;
    color: #1a2e1a;
    border-bottom-left-radius: 4px;
    border: 1px solid rgba(27,107,42,.1);
}
.msg-meta {
    font-size: 0.7rem;
    margin-top: 3px;
    color: #888888;
}
.msg-row.outbound .msg-meta {
    text-align: right;
}

/* Input Area */
.chat-input-box {
    padding: 1rem 1.4rem;
    border-top: 1px solid var(--nf-border);
    background: #ffffff;
}

@media(max-width: 768px) {
    .chat-wrapper { 
        flex-direction: column; 
        height: auto; 
        min-height: calc(100dvh - var(--bottom-nav-h, 62px) - 120px);
    }
    .chat-threads-sidebar { width: 100%; height: 200px; }
}
</style>

<div class="container-fluid py-2">

    <?php if ($errorNotice): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 rounded-3 mb-3">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <span><?= htmlspecialchars($errorNotice) ?></span>
        </div>
    <?php else: ?>

    <div class="chat-wrapper">

        <!-- Left Column: Threads list (for Trainer or Gym Owner) -->
        <aside class="chat-threads-sidebar">
            <div class="threads-header">
                <h2 class="h6 fw-bold mb-0">
                    <i class="bi bi-chat-dots-fill text-success me-2"></i>
                    <?= $userRole === 'gym_owner' ? 'Conversations Oversight' : ($userRole === 'trainer' ? 'Assigned Clients' : 'My Trainer') ?>
                </h2>
                <?php if ($userRole === 'gym_owner'): ?>
                    <small class="text-muted d-block mt-1" style="font-size:0.75rem;">Read-only staff moderation log</small>
                <?php endif; ?>
            </div>

            <div class="threads-list">
                <?php if ($userRole === 'customer' && $activePartner): ?>
                    <a href="#" class="thread-item active">
                        <?php if (!empty($activePartner['profile_picture_url'])): ?>
                            <img src="public/<?= htmlspecialchars(ltrim($activePartner['profile_picture_url'], '/')) ?>" class="avatar-thumb" alt="Avatar">
                        <?php else: ?>
                            <div class="avatar-thumb"><?= strtoupper(substr($activePartner['fullname'], 0, 2)) ?></div>
                        <?php endif; ?>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fw-bold text-truncate" style="font-size:0.9rem;"><?= htmlspecialchars($activePartner['fullname']) ?></div>
                            <div class="text-success small fw-semibold">Fitness Trainer</div>
                        </div>
                    </a>
                <?php elseif (empty($threads)): ?>
                    <div class="p-4 text-center text-muted small">No active conversations found.</div>
                <?php else: ?>
                    <?php foreach ($threads as $th): ?>
                        <?php
                            $partnerName = $th['client_name'] ?? $th['trainer_name'] ?? 'User';
                            $partnerAvatar = $th['client_avatar'] ?? $th['trainer_avatar'] ?? null;
                            $partnerId = (int)($th['client_user_id'] ?? $th['trainer_user_id'] ?? 0);
                            $isActive = ($targetUserId === $partnerId);
                            $unread = (int)($th['unread_count'] ?? 0);
                        ?>
                        <a href="index.php?r=message/index&with=<?= $partnerId ?><?= isset($th['client_user_id']) && $userRole === 'gym_owner' ? '&client=' . $th['client_user_id'] : '' ?>" 
                           class="thread-item <?= $isActive ? 'active' : '' ?>">
                            <?php if ($partnerAvatar): ?>
                                <img src="public/<?= htmlspecialchars(ltrim($partnerAvatar, '/')) ?>" class="avatar-thumb" alt="Avatar">
                            <?php else: ?>
                                <div class="avatar-thumb"><?= strtoupper(substr($partnerName, 0, 2)) ?></div>
                            <?php endif; ?>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-truncate" style="font-size:0.9rem;"><?= htmlspecialchars($partnerName) ?></span>
                                    <?php if ($unread > 0): ?>
                                        <span class="badge bg-danger rounded-pill"><?= $unread ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted text-truncate small">
                                    <?= htmlspecialchars($th['last_message'] ?? 'Start conversation...') ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Right Column: Active Chat Room -->
        <main class="chat-room">
            <?php if ($activePartner): ?>
                <div class="room-header">
                    <div class="d-flex align-items-center gap-3">
                        <?php if (!empty($activePartner['profile_picture_url'])): ?>
                            <img src="public/<?= htmlspecialchars(ltrim($activePartner['profile_picture_url'], '/')) ?>" class="avatar-thumb" alt="Avatar">
                        <?php else: ?>
                            <div class="avatar-thumb"><?= strtoupper(substr($activePartner['fullname'], 0, 2)) ?></div>
                        <?php endif; ?>
                        <div>
                            <h3 class="h6 fw-bold mb-0"><?= htmlspecialchars($activePartner['fullname']) ?></h3>
                            <span class="text-muted small"><?= ucfirst(str_replace('_',' ',$activePartner['role'])) ?></span>
                        </div>
                    </div>

                    <?php if ($userRole === 'gym_owner'): ?>
                        <span class="badge bg-secondary"><i class="bi bi-eye-fill me-1"></i> Read-Only Log</span>
                    <?php endif; ?>
                </div>

                <!-- Messages Thread -->
                <div class="messages-container" id="msgContainer">
                    <?php foreach ($activeMessages as $m): ?>
                        <?php
                            $isOutbound = ((int)$m['sender_id'] === (int)$user['id']);
                            $avatar = $m['sender_avatar'] ?? null;
                            $senderName = $m['sender_name'] ?? 'User';
                            $timeStr = date('M j, g:i A', strtotime($m['created_at']));
                        ?>
                        <div class="msg-row <?= $isOutbound ? 'outbound' : 'inbound' ?>">
                            <?php if ($avatar): ?>
                                <img src="public/<?= htmlspecialchars(ltrim($avatar, '/')) ?>" class="avatar-thumb avatar-thumb-sm" alt="Avatar">
                            <?php else: ?>
                                <div class="avatar-thumb avatar-thumb-sm"><?= strtoupper(substr($senderName, 0, 2)) ?></div>
                            <?php endif; ?>

                            <div>
                                <div class="msg-bubble">
                                    <?= nl2br(htmlspecialchars($m['message_text'])) ?>
                                </div>
                                <div class="msg-meta">
                                    <?= $timeStr ?>
                                    <?php if ($isOutbound): ?>
                                        <?= !empty($m['read_at']) ? ' · <span class="text-success">Seen</span>' : ' · Delivered' ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Chat Input Form -->
                <?php if ($userRole !== 'gym_owner'): ?>
                <div class="chat-input-box">
                    <form id="chatForm" onsubmit="sendMessage(event)">
                        <input type="hidden" id="receiverId" value="<?= (int)$activePartner['id'] ?>">
                        <div class="input-group">
                            <input type="text" id="messageInput" class="form-control rounded-start-pill py-2 px-3" 
                                   placeholder="Type your message here..." autocomplete="off" required>
                            <button type="submit" class="btn btn-success rounded-end-pill px-4 fw-bold">
                                <i class="bi bi-send-fill me-1"></i> Send
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="flex-grow-1 d-flex align-items-center justify-content-center text-muted">
                    Select a conversation thread to view messages.
                </div>
            <?php endif; ?>
        </main>

    </div>

    <?php endif; ?>

</div>

<script>
const currentUserId = <?= (int)$user['id'] ?>;
const partnerId = <?= (int)($activePartner['id'] ?? 0) ?>;

function scrollToBottom() {
    const container = document.getElementById('msgContainer');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}

// Auto-scroll on initial load
scrollToBottom();

async function sendMessage(e) {
    e.preventDefault();
    const input = document.getElementById('messageInput');
    const text = input.value.trim();
    if (!text || partnerId <= 0) return;

    input.value = '';

    const fd = new FormData();
    fd.append('receiver_id', partnerId);
    fd.append('message_text', text);

    try {
        const res = await fetch('index.php?r=message/send', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if (data.success) {
            fetchMessages();
        } else {
            alert(data.error || 'Failed to send message.');
        }
    } catch (err) {
        console.error('Send message error:', err);
    }
}

async function fetchMessages() {
    if (partnerId <= 0) return;
    try {
        const res = await fetch(`index.php?r=message/fetch&partner_id=${partnerId}`);
        const data = await res.json();
        if (data.success && data.messages) {
            renderMessages(data.messages);
        }
    } catch (err) {
        console.error('Fetch messages error:', err);
    }
}

function renderMessages(messages) {
    const container = document.getElementById('msgContainer');
    if (!container) return;

    let html = '';
    messages.forEach(m => {
        const isOutbound = (parseInt(m.sender_id) === currentUserId);
        const avatar = m.sender_avatar ? `public/${m.sender_avatar.replace(/^\//, '')}` : null;
        const initials = (m.sender_name || 'U').substring(0, 2).toUpperCase();
        const avatarHtml = avatar 
            ? `<img src="${avatar}" class="avatar-thumb avatar-thumb-sm" alt="Avatar">`
            : `<div class="avatar-thumb avatar-thumb-sm">${initials}</div>`;

        const date = new Date(m.created_at);
        const timeStr = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const readStatus = isOutbound ? (m.read_at ? ' · <span class="text-success">Seen</span>' : ' · Delivered') : '';

        html += `
            <div class="msg-row ${isOutbound ? 'outbound' : 'inbound'}">
                ${avatarHtml}
                <div>
                    <div class="msg-bubble">${escapeHtml(m.message_text)}</div>
                    <div class="msg-meta">${timeStr}${readStatus}</div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    scrollToBottom();
}

function escapeHtml(str) {
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

// Poll every 4 seconds for real-time update
if (partnerId > 0) {
    setInterval(fetchMessages, 4000);
}
</script>

<?php require __DIR__ . '/../partials/footer.php'; ?>
