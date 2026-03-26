<?php
require_once __DIR__ . '/../config/bootstrap.php';

$base = '../';
require_role('customer', '../login.php');

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    set_flash('error', 'Order not found.');
    redirect('orders.php');
}

$stmt = $pdo->prepare('SELECT id, status, total, payment_method, payment_label, delivery_address, created_at, delivery_agent, delivery_status, estimated_delivery_at FROM orders WHERE id = ? AND user_id = ?');
$stmt->execute([$orderId, $_SESSION['user']['id']]);
$order = $stmt->fetch();

if (!$order) {
    set_flash('error', 'Order not found.');
    redirect('orders.php');
}

$itemStmt = $pdo->prepare('SELECT oi.quantity, oi.price, m.item_name FROM order_items oi JOIN menu_items m ON oi.menu_id = m.id WHERE oi.order_id = ?');
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll();

$eventStmt = $pdo->prepare('SELECT status, note, created_at FROM order_tracking_events WHERE order_id = ? ORDER BY created_at ASC');
$eventStmt->execute([$orderId]);
$events = $eventStmt->fetchAll();

$status = strtolower((string)$order['status']);
$delivery = strtolower((string)$order['delivery_status']);
$statusClass = $status === 'delivered' ? 'success' : (in_array($status, ['dispatched'], true) ? 'info' : 'warning');
$deliveryClass = $delivery === 'delivered' ? 'success' : (in_array($delivery, ['out_for_delivery', 'assigned'], true) ? 'info' : 'warning');
$steps = [
    ['key' => 'placed', 'label' => 'Order placed', 'active' => true, 'desc' => 'We have received the order'],
    ['key' => 'preparing', 'label' => 'Preparing', 'active' => in_array($status, ['preparing', 'dispatched', 'delivered'], true), 'desc' => 'Kitchen is working on it'],
    ['key' => 'dispatched', 'label' => 'Dispatched', 'active' => in_array($status, ['dispatched', 'delivered'], true), 'desc' => 'Packed and ready'],
    ['key' => 'out_for_delivery', 'label' => 'Out for delivery', 'active' => in_array($delivery, ['out_for_delivery', 'delivered'], true), 'desc' => 'Delivery partner en route'],
    ['key' => 'delivered', 'label' => 'Delivered', 'active' => $delivery === 'delivered' || $status === 'delivered', 'desc' => 'Enjoy your meal'],
];

include __DIR__ . '/../includes/header.php';
?>

<div class="section-title">
    <h2>Order #<?php echo (int)$order['id']; ?></h2>
    <span><?php echo e($order['created_at']); ?></span>
</div>
<div class="card">
    <div class="card-row">
        <div>
            <div class="muted">Status</div>
            <div class="pill <?php echo e($statusClass); ?>"><?php echo status_label($order['status']); ?></div>
        </div>
        <div>
            <div class="muted">Delivery</div>
            <div class="pill <?php echo e($deliveryClass); ?>"><?php echo status_label($order['delivery_status']); ?></div>
        </div>
        <div>
            <div class="muted">Agent</div>
            <div><?php echo e($order['delivery_agent'] ?? 'Not assigned'); ?></div>
        </div>
        <div>
            <div class="muted">Total</div>
            <div><strong>INR <?php echo number_format((float)$order['total'], 2); ?></strong></div>
        </div>
        <div>
            <div class="muted">Payment</div>
            <div><?php echo e($order['payment_label'] ?? strtoupper(e($order['payment_method']))); ?></div>
        </div>
        <div>
            <div class="muted">Estimated</div>
            <div><?php echo e($order['estimated_delivery_at'] ?? 'Calculating'); ?></div>
        </div>
    </div>
    <?php if (!empty($order['delivery_address'])): ?>
        <div class="muted" style="margin-top: 10px;"><strong>Deliver to:</strong> <?php echo e($order['delivery_address']); ?></div>
    <?php endif; ?>
</div>

<div class="card" style="margin-top: 16px;">
    <h3>Live Tracking</h3>
    <div class="timeline" style="margin-top: 12px;">
        <?php if (!empty($events)): ?>
            <?php foreach ($events as $event): ?>
                <div class="timeline-step active">
                    <div class="dot"></div>
                    <div>
                        <div class="label"><?php echo e(status_label($event['status'])); ?></div>
                        <?php if (!empty($event['note'])): ?>
                            <div class="muted"><?php echo e($event['note']); ?></div>
                        <?php endif; ?>
                        <div class="timeline-time"><?php echo e($event['created_at']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <?php foreach ($steps as $step): ?>
                <div class="timeline-step <?php echo $step['active'] ? 'active' : ''; ?>">
                    <div class="dot"></div>
                    <div>
                        <div class="label"><?php echo e($step['label']); ?></div>
                        <div class="muted"><?php echo e($step['desc']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<h3 style="margin-top: 16px;">Items</h3>
<table class="table">
    <thead>
        <tr>
            <th>Item</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Line Total</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo e($item['item_name']); ?></td>
                <td><?php echo (int)$item['quantity']; ?></td>
                <td>INR <?php echo number_format((float)$item['price'], 2); ?></td>
                <td>INR <?php echo number_format((float)$item['price'] * (int)$item['quantity'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
