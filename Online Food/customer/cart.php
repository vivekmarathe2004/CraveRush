<?php
require_once __DIR__ . '/../config/bootstrap.php';

$base = '../';
$user = current_user();

if (is_post()) {
    $action = $_POST['action'] ?? '';
    if ($user && $user['role'] !== 'customer') {
        set_flash('error', 'Only customers can place orders.');
        redirect('../dashboard.php');
    }
    if ($action === 'update') {
        $qtys = $_POST['qty'] ?? [];
        foreach ($qtys as $menuId => $qty) {
            $menuId = (int)$menuId;
            $qty = (int)$qty;
            if ($menuId > 0) {
                if ($qty > 0) {
                    $_SESSION['cart'][$menuId] = $qty;
                } else {
                    unset($_SESSION['cart'][$menuId]);
                }
            }
        }
        set_flash('success', 'Cart updated.');
        redirect('cart.php');
    }
    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        unset($_SESSION['promo']);
        set_flash('success', 'Cart cleared.');
        redirect('cart.php');
    }
    if ($action === 'apply_promo') {
        $code = strtoupper(trim($_POST['promo_code'] ?? ''));
        if ($code === '') {
            unset($_SESSION['promo']);
            set_flash('error', 'Enter a promo code.');
        } else {
            $promoStmt = $pdo->prepare('SELECT code FROM coupons WHERE code = ? AND is_active = 1');
            $promoStmt->execute([$code]);
            $promoRow = $promoStmt->fetch();
            if ($promoRow) {
                $_SESSION['promo'] = $code;
                set_flash('success', 'Promo code saved. It will apply if eligible.');
            } else {
                unset($_SESSION['promo']);
                set_flash('error', 'Invalid promo code.');
            }
        }
        redirect('cart.php');
    }
    if ($action === 'remove_promo') {
        unset($_SESSION['promo']);
        set_flash('success', 'Promo removed.');
        redirect('cart.php');
    }
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    unset($_SESSION['promo']);
}
$items = [];
$subtotal = 0.00;

if (!empty($cart)) {
    $ids = implode(',', array_fill(0, count($cart), '?'));
    $stmt = $pdo->prepare("SELECT m.id, m.item_name, m.price, m.image_url, r.name AS restaurant_name FROM menu_items m JOIN restaurants r ON m.restaurant_id = r.id WHERE m.id IN ($ids)");
    $stmt->execute(array_keys($cart));
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        $qty = $cart[$item['id']];
        $subtotal += ((float)$item['price']) * $qty;
    }
}

$promoCode = $_SESSION['promo'] ?? null;
$totals = compute_order_totals($subtotal, $promoCode);

$coupons = [];
$couponStmt = $pdo->query('SELECT code, title, description, min_order FROM coupons WHERE is_active = 1 ORDER BY id DESC');
$coupons = $couponStmt->fetchAll();

$addresses = [];
$payments = [];
if ($user) {
    $stmt = $pdo->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC');
    $stmt->execute([$user['id']]);
    $addresses = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT * FROM payments WHERE user_id = ? ORDER BY is_default DESC, id DESC');
    $stmt->execute([$user['id']]);
    $payments = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="section-title">
    <h2>Your Cart</h2>
    <span>Review and checkout</span>
</div>

<?php if (empty($cart)): ?>
    <div class="card cart-empty">
        <h3>Your cart is empty</h3>
        <p class="muted">Add items from the menu to get started.</p>
        <a class="button" href="<?php echo e($base); ?>customer/menu.php">Browse menu</a>
    </div>
<?php else: ?>
    <div class="cart-layout">
        <section class="cart-items-section">
            <div class="card cart-items-card">
                <div class="cart-header-row">
                    <div>
                        <h3>Items in your cart</h3>
                        <div class="muted"><?php echo count($items); ?> items · Ready in 25-35 min</div>
                    </div>
                    <span class="pill info">Fast checkout</span>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="update">
                    <div class="cart-items-list">
                        <?php foreach ($items as $item): ?>
                            <?php $qty = $cart[$item['id']]; ?>
                            <?php $thumb = $item['image_url'] ?: 'assets/images/dishes/misal.jpg'; ?>
                            <div class="cart-item">
                                <div class="cart-item-thumb">
                                    <img src="<?php echo e($base . $thumb); ?>" alt="<?php echo e($item['item_name']); ?>">
                                </div>
                                <div class="cart-item-body">
                                    <div class="cart-item-title"><?php echo e($item['item_name']); ?></div>
                                    <div class="cart-item-meta">
                                        <?php echo e($item['restaurant_name']); ?> · ₹<?php echo number_format((float)$item['price'], 0); ?>
                                    </div>
                                </div>
                                <div class="cart-item-actions">
                                    <div class="cart-item-total">₹<?php echo number_format(((float)$item['price']) * $qty, 2); ?></div>
                                    <div class="cart-qty">
                                        <div class="qty-control">
                                            <button class="qty-btn" type="button" data-action="dec">-</button>
                                            <span class="qty-value"><?php echo (int)$qty; ?></span>
                                            <button class="qty-btn" type="button" data-action="inc">+</button>
                                            <input type="hidden" name="qty[<?php echo (int)$item['id']; ?>]" value="<?php echo (int)$qty; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="cart-actions">
                        <button class="button" type="submit">Update Cart</button>
                        <a class="button ghost" href="<?php echo e($base); ?>customer/menu.php">Add more items</a>
                    </div>
                </form>
                <form method="post" class="cart-clear-form">
                    <input type="hidden" name="action" value="clear">
                    <button class="button secondary" type="submit">Clear Cart</button>
                </form>
            </div>
        </section>

        <aside class="cart-sidebar">
            <div class="card cart-summary-card">
                <h3>Bill Details</h3>
                <div class="summary-list">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₹<?php echo number_format($totals['subtotal'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Delivery fee</span>
                        <span>₹<?php echo number_format($totals['delivery_fee'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Packaging</span>
                        <span>₹<?php echo number_format($totals['packaging_fee'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Taxes</span>
                        <span>₹<?php echo number_format($totals['taxes'], 2); ?></span>
                    </div>
                    <div class="summary-row summary-discount">
                        <span>Discount</span>
                        <span>-₹<?php echo number_format($totals['discount'], 2); ?></span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <span>₹<?php echo number_format($totals['grand_total'], 2); ?></span>
                    </div>
                </div>
            </div>

            <div class="card cart-trust-card">
                <h3>Why order here</h3>
                <div class="trust-list">
                    <div class="trust-item">Live order tracking</div>
                    <div class="trust-item">Secure payments</div>
                    <div class="trust-item">Hygiene checked partners</div>
                </div>
            </div>

            <div class="card cart-promo-card">
                <div class="card-row">
                    <h3>Promo Code</h3>
                    <?php if (!empty($totals['promo_code'])): ?>
                        <span class="pill success">Applied</span>
                    <?php endif; ?>
                </div>
                <form method="post" class="promo-form">
                    <input type="hidden" name="action" value="apply_promo">
                    <div class="promo-input">
                        <label for="promo_code">Enter code</label>
                        <input type="text" id="promo_code" name="promo_code" placeholder="SAVE50, SAVE100, FREESHIP" value="<?php echo e($totals['promo_code']); ?>">
                    </div>
                    <button class="button secondary" type="submit">Apply</button>
                </form>
                <?php if (!empty($totals['promo_code'])): ?>
                    <form method="post" class="promo-remove-form">
                        <input type="hidden" name="action" value="remove_promo">
                        <button class="button ghost" type="submit">Remove promo</button>
                    </form>
                <?php endif; ?>
                <?php if ($totals['promo_note']): ?>
                    <div class="muted promo-note"><?php echo e($totals['promo_note']); ?></div>
                <?php endif; ?>
            </div>

            <?php if (!empty($coupons)): ?>
                <div class="card cart-offers-card">
                    <h3>Available Offers</h3>
                    <div class="coupon-grid">
                        <?php foreach ($coupons as $c): ?>
                            <div class="coupon-card">
                                <div class="coupon-code"><?php echo e($c['code']); ?></div>
                                <div class="coupon-title"><?php echo e($c['title']); ?></div>
                                <div class="muted"><?php echo e($c['description']); ?></div>
                                <div class="muted">Min order ₹<?php echo number_format((float)$c['min_order'], 0); ?></div>
                                <div class="coupon-action">
                                    <?php if (!empty($totals['promo_code']) && $totals['promo_code'] === $c['code']): ?>
                                        <span class="pill success">Applied</span>
                                    <?php else: ?>
                                        <form method="post">
                                            <input type="hidden" name="action" value="apply_promo">
                                            <input type="hidden" name="promo_code" value="<?php echo e($c['code']); ?>">
                                            <button class="button secondary small" type="submit">Apply</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card cart-checkout-card">
                <?php if (!$user): ?>
                    <div class="muted">Login is required to place your order.</div>
                    <div style="margin-top: 12px;">
                        <a class="button" href="../login.php?return=<?php echo urlencode('/customer/cart.php'); ?>">Login to Checkout</a>
                    </div>
                <?php else: ?>
                    <form method="post" action="place_order.php">
                        <div class="form-group">
                            <label for="address_id">Delivery Address</label>
                            <select name="address_id" id="address_id" required>
                                <option value="">Select address</option>
                                <?php foreach ($addresses as $addr): ?>
                                    <option value="<?php echo (int)$addr['id']; ?>">
                                        <?php echo e($addr['label']); ?> · <?php echo e($addr['line1']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($addresses)): ?>
                                <div class="muted" style="margin-top: 6px;">No saved addresses. <a href="../addresses.php">Add one</a>.</div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="payment_id">Payment Method</label>
                            <select name="payment_id" id="payment_id" required>
                                <option value="">Select payment</option>
                                <?php foreach ($payments as $pay): ?>
                                    <option value="<?php echo (int)$pay['id']; ?>">
                                        <?php echo e($pay['method']); ?> · <?php echo e($pay['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($payments)): ?>
                                <div class="muted" style="margin-top: 6px;">No payment methods. <a href="../payments.php">Add one</a>.</div>
                            <?php endif; ?>
                        </div>
                        <button class="button" type="submit" <?php echo empty($addresses) || empty($payments) ? 'disabled' : ''; ?>>
                            Place Order · ₹<?php echo number_format($totals['grand_total'], 2); ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </aside>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
