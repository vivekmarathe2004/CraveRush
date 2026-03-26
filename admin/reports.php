<?php
require_once __DIR__ . '/../config/bootstrap.php';

$base = '../';
require_role('admin', '../login.php');

$totalOrders = $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalRevenue = $pdo->query('SELECT COALESCE(SUM(total),0) FROM orders')->fetchColumn();

$statusRows = $pdo->query('SELECT status, COUNT(*) as count FROM orders GROUP BY status')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="section-title">
    <h2>Reports</h2>
    <span>Performance overview</span>
</div>

<div class="card-grid">
    <div class="card">
        <h3><?php echo (int)$totalOrders; ?></h3>
        <div>Total Orders</div>
    </div>
    <div class="card">
        <h3>INR <?php echo number_format((float)$totalRevenue, 2); ?></h3>
        <div>Total Revenue</div>
    </div>
</div>

<h3 style="margin-top: 16px;">Orders by Status</h3>
<table class="table">
    <thead>
        <tr>
            <th>Status</th>
            <th>Count</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($statusRows as $row): ?>
            <tr>
                <td><?php echo status_label($row['status']); ?></td>
                <td><?php echo (int)$row['count']; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
