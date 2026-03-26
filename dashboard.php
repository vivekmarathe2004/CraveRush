<?php
require_once __DIR__ . '/config/bootstrap.php';

$base = '';
require_login('login.php');

$user = current_user();

$stats = null;
if ($user['role'] === 'admin') {
    $stats = [
        'restaurants' => $pdo->query('SELECT COUNT(*) FROM restaurants')->fetchColumn(),
        'menu_items' => $pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn(),
        'orders' => $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
        'revenue' => $pdo->query('SELECT COALESCE(SUM(total),0) FROM orders')->fetchColumn(),
    ];
} else {
    $counts = [
        'orders' => $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?'),
        'favorites' => $pdo->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = ?'),
        'addresses' => $pdo->prepare('SELECT COUNT(*) FROM addresses WHERE user_id = ?'),
        'notifications' => $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0'),
    ];
    foreach ($counts as $stmt) {
        $stmt->execute([$user['id']]);
    }
    $orderCount = (int)$counts['orders']->fetchColumn();
    $favoriteCount = (int)$counts['favorites']->fetchColumn();
    $addressCount = (int)$counts['addresses']->fetchColumn();
    $notifCount = (int)$counts['notifications']->fetchColumn();
}

include __DIR__ . '/includes/header.php';
?>

<?php if ($user['role'] === 'admin'): ?>
    <div class="dashboard-banner">
        <h2>Dashboard</h2>
        <p class="muted">A single view of operations, performance, and ordering status.</p>
    </div>
    <div class="card-grid">
        <div class="card">
            <h3><?php echo (int)$stats['restaurants']; ?></h3>
            <div>Restaurants</div>
        </div>
        <div class="card">
            <h3><?php echo (int)$stats['menu_items']; ?></h3>
            <div>Menu Items</div>
        </div>
        <div class="card">
            <h3><?php echo (int)$stats['orders']; ?></h3>
            <div>Total Orders</div>
        </div>
        <div class="card">
            <h3>INR <?php echo number_format((float)$stats['revenue'], 2); ?></h3>
            <div>Revenue</div>
        </div>
    </div>
    <div style="margin-top: 16px;">
        <a class="button" href="admin/index.php">Go to Admin Panel</a>
    </div>
<?php else: ?>
    <div class="dashboard-hero">
        <div>
            <h2>Welcome back, <?php echo e($user['name']); ?>.</h2>
            <p class="muted">Browse the menu, add items to cart, and track your orders in one place.</p>
            <div class="dashboard-actions">
                <a class="button" href="customer/menu.php">Start Ordering</a>
                <a class="button secondary" href="customer/orders.php">Track Orders</a>
            </div>
        </div>
        <div class="dashboard-stats">
            <div class="dashboard-stat">
                <strong><?php echo $orderCount; ?></strong>
                <span class="muted">Orders</span>
            </div>
            <div class="dashboard-stat">
                <strong><?php echo $favoriteCount; ?></strong>
                <span class="muted">Favorites</span>
            </div>
            <div class="dashboard-stat">
                <strong><?php echo $addressCount; ?></strong>
                <span class="muted">Addresses</span>
            </div>
            <div class="dashboard-stat">
                <strong><?php echo $notifCount; ?></strong>
                <span class="muted">Alerts</span>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <a class="dashboard-tile" href="customer/menu.php">
            <h3>Explore Menu</h3>
            <p class="muted">Find dishes and top restaurants.</p>
        </a>
        <a class="dashboard-tile" href="customer/orders.php">
            <h3>Order History</h3>
            <p class="muted">Track delivery status and receipts.</p>
        </a>
        <a class="dashboard-tile" href="customer/favorites.php">
            <h3>Saved Favorites</h3>
            <p class="muted">Quick reorder from saved restaurants.</p>
        </a>
        <a class="dashboard-tile" href="customer/notifications.php">
            <h3>Notifications</h3>
            <p class="muted">Latest updates and offers.</p>
        </a>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
