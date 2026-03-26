<?php
require_once __DIR__ . '/../config/bootstrap.php';

$base = '../';
require_role('customer', '../login.php');
$user = current_user();

if (is_post()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_all') {
        $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
        $stmt->execute([$user['id']]);
        set_flash('success', 'All notifications marked as read.');
    } elseif ($action === 'mark_one') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
            $stmt->execute([$id, $user['id']]);
        }
    }
    redirect('notifications.php');
}

$stmt = $pdo->prepare('SELECT id, title, message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="section-title">
    <h2>Notifications</h2>
    <span><?php echo count($notifications); ?> updates</span>
</div>

<?php if (empty($notifications)): ?>
    <div class="card cart-empty">
        <h3>No notifications yet</h3>
        <p class="muted">We will alert you when your order status changes.</p>
        <a class="button" href="<?php echo e($base); ?>restaurants.php">Explore restaurants</a>
    </div>
<?php else: ?>
    <div class="notif-actions">
        <form method="post">
            <input type="hidden" name="action" value="mark_all">
            <button class="button secondary" type="submit">Mark all read</button>
        </form>
    </div>
    <div class="notif-list">
        <?php foreach ($notifications as $n): ?>
            <div class="card notif-card <?php echo $n['is_read'] ? 'read' : 'unread'; ?>">
                <div class="notif-header">
                    <div>
                        <div class="notif-title"><?php echo e($n['title']); ?></div>
                        <div class="muted"><?php echo e($n['message']); ?></div>
                    </div>
                    <div class="notif-time"><?php echo e($n['created_at']); ?></div>
                </div>
                <div class="notif-footer">
                    <?php if (!empty($n['link'])): ?>
                        <a class="button" href="<?php echo e($base . $n['link']); ?>">View</a>
                    <?php endif; ?>
                    <?php if (!$n['is_read']): ?>
                        <form method="post">
                            <input type="hidden" name="action" value="mark_one">
                            <input type="hidden" name="id" value="<?php echo (int)$n['id']; ?>">
                            <button class="button ghost" type="submit">Mark read</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
