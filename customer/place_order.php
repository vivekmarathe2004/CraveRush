<?php
require_once __DIR__ . '/../config/bootstrap.php';

$user = current_user();
if (!$user) {
    set_flash('error', 'Please login to place your order.');
    redirect('../login.php?return=' . urlencode('/customer/cart.php'));
}
if ($user['role'] !== 'customer') {
    set_flash('error', 'Only customers can place orders.');
    redirect('../dashboard.php');
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    set_flash('error', 'Cart is empty.');
    redirect('cart.php');
}

$paymentMethod = $_POST['payment_method'] ?? 'cod';
if (!in_array($paymentMethod, ['cod', 'upi'], true)) {
    $paymentMethod = 'cod';
}

$addressId = (int)($_POST['address_id'] ?? 0);
$paymentId = (int)($_POST['payment_id'] ?? 0);

if ($addressId <= 0) {
    set_flash('error', 'Please select a delivery address.');
    redirect('cart.php');
}

$addrStmt = $pdo->prepare('SELECT label, line1, line2, city, pincode FROM addresses WHERE id = ? AND user_id = ?');
$addrStmt->execute([$addressId, $_SESSION['user']['id']]);
$address = $addrStmt->fetch();
if (!$address) {
    set_flash('error', 'Invalid address selected.');
    redirect('cart.php');
}

if ($paymentId <= 0) {
    set_flash('error', 'Please select a payment method.');
    redirect('cart.php');
}

$payStmt = $pdo->prepare('SELECT method, label FROM payments WHERE id = ? AND user_id = ?');
$payStmt->execute([$paymentId, $_SESSION['user']['id']]);
$payment = $payStmt->fetch();
if (!$payment) {
    set_flash('error', 'Invalid payment method.');
    redirect('cart.php');
}

$methodLower = strtolower($payment['method']);
if ($methodLower === 'upi') {
    $paymentMethod = 'upi';
} elseif ($methodLower === 'card') {
    $paymentMethod = 'card';
} elseif ($methodLower === 'wallet') {
    $paymentMethod = 'wallet';
} else {
    $paymentMethod = 'cod';
}
$addressText = trim($address['label'] . ', ' . $address['line1'] . ' ' . ($address['line2'] ?? '') . ', ' . $address['city'] . ' ' . $address['pincode']);

$ids = implode(',', array_fill(0, count($cart), '?'));
$stmt = $pdo->prepare("SELECT id, item_name, price FROM menu_items WHERE id IN ($ids) AND is_active = 1");
$stmt->execute(array_keys($cart));
$items = $stmt->fetchAll();

if (empty($items)) {
    set_flash('error', 'No valid items found.');
    redirect('cart.php');
}

$total = 0.00;
foreach ($items as $item) {
    $qty = $cart[$item['id']] ?? 0;
    $total += ((float)$item['price']) * $qty;
}

$promoCode = $_SESSION['promo'] ?? null;
$totals = compute_order_totals($total, $promoCode);
$etaMinutes = 40;
$estimatedAt = date('Y-m-d H:i:s', time() + ($etaMinutes * 60));

try {
    $pdo->beginTransaction();

    $orderStmt = $pdo->prepare('INSERT INTO orders (user_id, status, total, payment_method, payment_id, payment_label, address_id, delivery_address, estimated_delivery_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $orderStmt->execute([$_SESSION['user']['id'], 'pending', $totals['grand_total'], $paymentMethod, $paymentId, $payment['label'], $addressId, $addressText, $estimatedAt]);
    $orderId = (int)$pdo->lastInsertId();

    $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, menu_id, quantity, price) VALUES (?, ?, ?, ?)');
    foreach ($items as $item) {
        $qty = (int)$cart[$item['id']];
        if ($qty > 0) {
            $itemStmt->execute([$orderId, $item['id'], $qty, $item['price']]);
        }
    }

    add_order_event($pdo, $orderId, 'placed', 'Order placed');
    add_notification(
        $pdo,
        $_SESSION['user']['id'],
        'Order placed',
        'Your order #' . $orderId . ' has been placed.',
        'customer/track.php?id=' . $orderId
    );

    $pdo->commit();
    $_SESSION['cart'] = [];
    unset($_SESSION['promo']);
    set_flash('success', 'Order placed successfully.');
    redirect('orders.php');
} catch (Exception $e) {
    $pdo->rollBack();
    set_flash('error', 'Failed to place order.');
    redirect('cart.php');
}
?>
