<?php
require_once __DIR__ . '/../config/bootstrap.php';

$base = '../';
require_role('admin', '../login.php');

if (is_post()) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'pending');
    $deliveryStatus = trim($_POST['delivery_status'] ?? 'not_assigned');
    $deliveryAgent = trim($_POST['delivery_agent'] ?? '');

    if ($orderId > 0) {
        $prevStmt = $pdo->prepare('SELECT user_id, status, delivery_status FROM orders WHERE id = ?');
        $prevStmt->execute([$orderId]);
        $prev = $prevStmt->fetch();

        $stmt = $pdo->prepare('UPDATE orders SET status = ?, delivery_status = ?, delivery_agent = ? WHERE id = ?');
        $stmt->execute([$status, $deliveryStatus, $deliveryAgent !== '' ? $deliveryAgent : null, $orderId]);

        if ($prev) {
            if ($prev['status'] !== $status) {
                add_order_event($pdo, $orderId, $status, 'Status updated to ' . status_label($status));
                add_notification(
                    $pdo,
                    (int)$prev['user_id'],
                    'Order status updated',
                    'Order #' . $orderId . ' status is now ' . status_label($status) . '.',
                    'customer/track.php?id=' . $orderId
                );
            }
            if ($prev['delivery_status'] !== $deliveryStatus) {
                add_order_event($pdo, $orderId, $deliveryStatus, 'Delivery status: ' . status_label($deliveryStatus));
                add_notification(
                    $pdo,
                    (int)$prev['user_id'],
                    'Delivery update',
                    'Order #' . $orderId . ' delivery is ' . status_label($deliveryStatus) . '.',
                    'customer/track.php?id=' . $orderId
                );
            }
        }
        set_flash('success', 'Order updated.');
        redirect('orders.php');
    }
}

$orders = $pdo->query('SELECT o.id, o.status, o.total, o.payment_method, o.payment_label, o.delivery_address, o.created_at, o.delivery_status, o.delivery_agent, u.name AS customer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC')->fetchAll();

$statuses = ['pending', 'preparing', 'dispatched', 'delivered'];
$deliveryStatuses = ['not_assigned', 'assigned', 'out_for_delivery', 'delivered'];
$pillClass = function ($status) {
    $status = strtolower((string)$status);
    if ($status === 'delivered') {
        return 'success';
    }
    if (in_array($status, ['dispatched', 'out_for_delivery', 'assigned'], true)) {
        return 'info';
    }
    return 'warning';
};

include __DIR__ . '/../includes/header.php';
?>

<div class="section-title">
    <h2>Orders</h2>
    <span>Manage delivery status</span>
</div>

<table class="table">
    <thead>
        <tr>
            <th>Order</th>
            <th>Customer</th>
            <th>Status</th>
            <th>Delivery</th>
            <th>Total</th>
            <th>Address</th>
            <th>Payment</th>
            <th>Placed</th>
            <th>Update</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td>#<?php echo (int)$o['id']; ?></td>
                <td><?php echo e($o['customer_name']); ?></td>
                <td><span class="pill <?php echo $pillClass($o['status']); ?>"><?php echo status_label($o['status']); ?></span></td>
                <td><span class="pill <?php echo $pillClass($o['delivery_status']); ?>"><?php echo status_label($o['delivery_status']); ?></span></td>
                <td>INR <?php echo number_format((float)$o['total'], 2); ?></td>
                <td><?php echo e($o['delivery_address'] ?? '-'); ?></td>
                <td><?php echo e($o['payment_label'] ?? strtoupper(e($o['payment_method']))); ?></td>
                <td><?php echo e($o['created_at']); ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>">
                        <div style="display:flex; gap:8px; flex-direction: column;">
                            <select name="status">
                                <?php foreach ($statuses as $s): ?>
                                    <option value="<?php echo e($s); ?>" <?php echo $o['status'] === $s ? 'selected' : ''; ?>><?php echo e($s); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="delivery_status">
                                <?php foreach ($deliveryStatuses as $d): ?>
                                    <option value="<?php echo e($d); ?>" <?php echo $o['delivery_status'] === $d ? 'selected' : ''; ?>><?php echo e($d); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="delivery_agent" placeholder="Agent name" value="<?php echo e($o['delivery_agent']); ?>">
                            <button class="button" type="submit">Update</button>
                        </div>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
