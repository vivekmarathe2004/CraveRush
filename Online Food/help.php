<?php
require_once __DIR__ . '/config/bootstrap.php';

$base = '';
require_login('login.php?return=' . urlencode('/help.php'));

$topics = [
    ['title' => 'Order not delivered', 'desc' => 'Track your order and report delivery issues.'],
    ['title' => 'Wrong order received', 'desc' => 'Get help with incorrect items.'],
    ['title' => 'Refund status', 'desc' => 'Check refund eligibility and timelines.'],
    ['title' => 'Payment failed', 'desc' => 'Resolve payment and wallet issues.'],
    ['title' => 'Account and login', 'desc' => 'Recover access or update details.'],
];

include __DIR__ . '/includes/header.php';
?>

<div class="section-title">
    <h2>Help & Support</h2>
    <span>How can we help you today?</span>
</div>

<div class="card-grid">
    <?php foreach ($topics as $topic): ?>
        <div class="card">
            <h3><?php echo e($topic['title']); ?></h3>
            <div class="muted"><?php echo e($topic['desc']); ?></div>
            <div style="margin-top: 12px;">
                <button class="button">View</button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card" style="margin-top: 16px;">
    <h3>Need more help?</h3>
    <p class="muted">Chat with us or raise a ticket. We reply within 24 hours.</p>
    <div style="margin-top: 12px; display:flex; gap:10px; flex-wrap:wrap;">
        <button class="button">Start Chat</button>
        <button class="button secondary">Raise Ticket</button>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
