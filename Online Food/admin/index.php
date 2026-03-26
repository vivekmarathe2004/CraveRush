<?php
require_once __DIR__ . '/../config/bootstrap.php';

$base = '../';
require_role('admin', '../login.php');

$stats = [
    'restaurants' => $pdo->query('SELECT COUNT(*) FROM restaurants')->fetchColumn(),
    'menu_items' => $pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn(),
    'orders' => $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'revenue' => $pdo->query('SELECT COALESCE(SUM(total),0) FROM orders')->fetchColumn(),
];

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-banner">
    <h2>Admin Control Room</h2>
    <p class="muted">Manage restaurants, menus, orders, and revenue in one place.</p>
</div>

<div class="stat-grid" style="margin-top: 16px;">
    <div class="stat-card">
        <h3><?php echo (int)$stats['restaurants']; ?></h3>
        <div class="muted">Restaurants</div>
    </div>
    <div class="stat-card">
        <h3><?php echo (int)$stats['menu_items']; ?></h3>
        <div class="muted">Menu items</div>
    </div>
    <div class="stat-card">
        <h3><?php echo (int)$stats['orders']; ?></h3>
        <div class="muted">Orders</div>
    </div>
    <div class="stat-card">
        <h3>INR <?php echo number_format((float)$stats['revenue'], 2); ?></h3>
        <div class="muted">Revenue</div>
    </div>
</div>

<h2 style="margin-top: 24px;">Admin Panel</h2>
<div class="card-grid">
    <div class="card">
        <h3>Restaurants</h3>
        <p>Manage restaurant listings.</p>
        <a class="button" href="restaurants.php">Manage</a>
    </div>
    <div class="card">
        <h3>Menu Items</h3>
        <p>Add or update menu items.</p>
        <a class="button" href="menu_items.php">Manage</a>
    </div>
    <div class="card">
        <h3>Orders</h3>
        <p>Update order and delivery status.</p>
        <a class="button" href="orders.php">Manage</a>
    </div>
    <div class="card">
        <h3>Reports</h3>
        <p>View totals and revenue.</p>
        <a class="button" href="reports.php">View</a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
