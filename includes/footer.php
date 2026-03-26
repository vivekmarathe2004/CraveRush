</main>

<footer class="footer" id="footer">
    <div class="footer-wrap">
        <div class="footer-brand">
            <h4>CraveRush</h4>
            <p class="muted">Fast, crave-worthy food delivery for India's favorite kitchens.</p>
            <div class="footer-apps">
                <span class="footer-badge">App Store</span>
                <span class="footer-badge">Google Play</span>
            </div>
            <div class="footer-social">
                <a href="#" class="footer-link">
                    <span class="icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <rect x="5" y="5" width="14" height="14" rx="4"></rect>
                            <circle cx="12" cy="12" r="4"></circle>
                            <circle cx="16" cy="8" r="1"></circle>
                        </svg>
                    </span>
                    Instagram
                </a>
                <a href="#" class="footer-link">
                    <span class="icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M6 5l12 14"></path>
                            <path d="M18 5l-12 14"></path>
                        </svg>
                    </span>
                    X
                </a>
                <a href="#" class="footer-link">
                    <span class="icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M14 8h2V5h-2c-2 0-4 1-4 4v2H8v3h2v5h3v-5h2.5l.5-3H13V9c0-.7.3-1 1-1z"></path>
                        </svg>
                    </span>
                    Facebook
                </a>
            </div>
        </div>
        <div class="footer-links">
            <div class="footer-col">
                <h4>Explore</h4>
                <a href="<?php echo e($base); ?>index.php">Home</a>
                <a href="<?php echo e($base); ?>customer/menu.php">Menu</a>
                <a href="<?php echo e($base); ?>customer/cart.php">Cart</a>
                <a href="<?php echo e($base); ?>restaurants.php">Restaurants</a>
            </div>
            <div class="footer-col">
                <h4>For Restaurants</h4>
                <span>Menu management</span>
                <span>Delivery workflow</span>
                <span>Sales insights</span>
            </div>
            <div class="footer-col">
                <h4>Contact</h4>
                <span>support@craverush.local</span>
                <span>+91 98XXXXXX21</span>
                <span>Pan-India</span>
            </div>
            <div class="footer-col">
                <h4>Account</h4>
                <a href="<?php echo e($base); ?>profile.php">Profile</a>
                <a href="<?php echo e($base); ?>addresses.php">Addresses</a>
                <a href="<?php echo e($base); ?>payments.php">Payments</a>
                <a href="<?php echo e($base); ?>help.php">Help</a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div>(c) <?php echo date('Y'); ?> CraveRush</div>
        <div class="muted">Crafted for India. Fresh deliveries daily.</div>
    </div>
</footer>
<?php
$cartItems = [];
$cartTotal = 0.00;
if (!empty($_SESSION['cart']) && isset($pdo)) {
    $cart = $_SESSION['cart'];
    $ids = implode(',', array_fill(0, count($cart), '?'));
    $stmt = $pdo->prepare("SELECT m.id, m.item_name, m.price, m.image_url FROM menu_items m WHERE m.id IN ($ids)");
    $stmt->execute(array_keys($cart));
    $cartItems = $stmt->fetchAll();
    foreach ($cartItems as $item) {
        $qty = $cart[$item['id']];
        $cartTotal += ((float)$item['price']) * $qty;
    }
}
?>
<div class="cart-overlay" id="cartOverlay"></div>
<aside class="cart-drawer" id="cartDrawer">
    <div class="cart-header">
        <div>
            <strong>Your Cart</strong>
            <div class="muted"><?php echo (int)cart_count(); ?> items</div>
        </div>
        <button class="button ghost" id="cartClose" type="button">Close</button>
    </div>
    <div class="cart-items">
        <?php if (empty($cartItems)): ?>
            <div class="muted">Your cart is empty.</div>
        <?php else: ?>
            <?php foreach ($cartItems as $item): ?>
                <?php $qty = (int)($_SESSION['cart'][$item['id']] ?? 0); ?>
                <?php $thumb = $item['image_url'] ?: 'assets/images/dishes/misal.jpg'; ?>
                <div class="cart-mini-item">
                    <div class="cart-mini-thumb">
                        <img src="<?php echo e($base . $thumb); ?>" alt="<?php echo e($item['item_name']); ?>">
                    </div>
                    <div class="cart-mini-body">
                        <div class="cart-mini-title"><?php echo e($item['item_name']); ?></div>
                        <div class="cart-mini-meta">x<?php echo (int)$qty; ?> � ?<?php echo number_format((float)$item['price'], 0); ?></div>
                    </div>
                    <div class="cart-mini-total">?<?php echo number_format(((float)$item['price']) * $qty, 2); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="cart-footer">
        <div class="cart-summary-line">
            <span>Subtotal</span>
            <strong>?<?php echo number_format($cartTotal, 2); ?></strong>
        </div>
        <div class="muted" style="margin-top: 6px;">Taxes and fees calculated at checkout.</div>
        <div style="margin-top: 12px;">
            <a class="button" href="<?php echo e($base); ?>customer/cart.php">Go to Cart</a>
        </div>
    </div>
</aside>
<?php if (!empty($_SESSION['cart'])): ?>
    <div class="sticky-cart">
        <div class="sticky-cart-inner">
            <div>
                <strong><?php echo (int)cart_count(); ?> items</strong>
                <span class="muted">?<?php echo number_format($cartTotal, 2); ?></span>
            </div>
            <a class="button" href="<?php echo e($base); ?>customer/cart.php">View Cart</a>
        </div>
    </div>
<?php endif; ?>
<button class="back-to-top" id="backToTop" type="button" aria-label="Back to top">
    <span>Top</span>
</button>
<script src="<?php echo e($base); ?>assets/js/main.js"></script>
</body>
</html>
