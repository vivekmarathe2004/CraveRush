<?php
require_once __DIR__ . '/../config/bootstrap.php';

$base = '../';
require_role('customer', '../login.php');

$stmt = $pdo->prepare('
    SELECT o.id, o.status, o.total, o.payment_method, o.payment_label, o.created_at, o.delivery_status,
           COALESCE(SUM(oi.quantity), 0) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
');
$stmt->execute([$_SESSION['user']['id']]);
$orders = $stmt->fetchAll();

$pillClass = function ($status) {
    $status = strtolower((string)$status);
    if ($status === 'delivered') {
        return 'success';
    }
    if ($status === 'dispatched' || $status === 'out_for_delivery' || $status === 'assigned') {
        return 'info';
    }
    return 'warning';
};

include __DIR__ . '/../includes/header.php';
?>

<div class="section-title">
    <h2>My Orders</h2>
    <span><?php echo count($orders); ?> orders</span>
</div>

<?php if (empty($orders)): ?>
    <div class="card">No orders yet.</div>
<?php else: ?>
    <table class="table">
        <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Status</th>
                    <th>Delivery</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Placed</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td>#<?php echo (int)$o['id']; ?></td>
                        <td><span class="pill <?php echo $pillClass($o['status']); ?>"><?php echo status_label($o['status']); ?></span></td>
                        <td><span class="pill <?php echo $pillClass($o['delivery_status']); ?>"><?php echo status_label($o['delivery_status']); ?></span></td>
                        <td><?php echo (int)$o['item_count']; ?></td>
                        <td>INR <?php echo number_format((float)$o['total'], 2); ?></td>
                        <td><?php echo e($o['payment_label'] ?? strtoupper(e($o['payment_method']))); ?></td>
                        <td><?php echo e($o['created_at']); ?></td>
                        <td><a class="button secondary" href="track.php?id=<?php echo (int)$o['id']; ?>">Track</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
    </table>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
