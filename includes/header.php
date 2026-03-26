<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION['user'] ?? null;
$base = $base ?? '';
$flash = function_exists('get_flash') ? get_flash() : null;
$searchValue = $_GET['q'] ?? '';
$cartTotal = 0.0;
if (!empty($_SESSION['cart']) && isset($pdo)) {
    $cart = $_SESSION['cart'];
    $ids = implode(',', array_fill(0, count($cart), '?'));
    $stmt = $pdo->prepare("SELECT id, price FROM menu_items WHERE id IN ($ids)");
    $stmt->execute(array_keys($cart));
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        $qty = (int)($cart[$row['id']] ?? 0);
        $cartTotal += ((float)$row['price']) * $qty;
    }
}
$userInitial = $user ? strtoupper(substr($user['name'], 0, 1)) : '';
$notifCount = 0;
if ($user && $user['role'] === 'customer' && isset($pdo)) {
    $nstmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $nstmt->execute([$user['id']]);
    $notifCount = (int)$nstmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CraveRush | Online Food Management</title>
    <link rel="stylesheet" href="<?php echo e($base); ?>assets/css/style.css">
</head>
<body>
<div class="scroll-progress" id="scrollProgress"></div>
<header class="navbar">
    <div class="navbar-inner">
        <div class="nav-left">
            <a class="logo" href="<?php echo e($base); ?>index.php">CraveRush</a>
            <div class="nav-location">
                <div class="location-title">India <span class="caret">?</span></div>
                <div class="location-sub">All Cities</div>
            </div>
        </div>
        <form class="nav-search" action="<?php echo e($base); ?>restaurants.php" method="get">
            <span class="search-icon icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <circle cx="11" cy="11" r="6"></circle>
                    <path d="M16 16l4 4"></path>
                </svg>
            </span>
            <input type="text" name="q" placeholder="Search restaurants or dishes" value="<?php echo e($searchValue); ?>">
        </form>
        <div class="nav-right">
            <?php if ($user && $user['role'] === 'customer'): ?>
                <a class="nav-link" href="<?php echo e($base); ?>customer/orders.php">
                    <span class="icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M6 4h9l3 3v13H6z"></path>
                            <path d="M15 4v4h4"></path>
                            <path d="M9 12h6M9 16h6"></path>
                        </svg>
                    </span>
                    Orders
                </a>
                <a class="nav-link" href="<?php echo e($base); ?>customer/favorites.php">
                    <span class="icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M12 20s-7-4.5-9-8.5C1.5 8 3 5 6 5c2 0 3 1 4 2 1-1 2-2 4-2 3 0 4.5 3 3 6.5-2 4-9 8.5-9 8.5z"></path>
                        </svg>
                    </span>
                    Favorites
                </a>
                <a class="nav-link" href="<?php echo e($base); ?>customer/notifications.php">
                    <span class="icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path d="M6 18h12l-1.5-2V11a4.5 4.5 0 0 0-9 0v5L6 18z"></path>
                            <path d="M10 18a2 2 0 0 0 4 0"></path>
                        </svg>
                    </span>
                    Notifications<?php if ($notifCount > 0): ?> <span class="nav-badge"><?php echo (int)$notifCount; ?></span><?php endif; ?>
                </a>
            <?php endif; ?>
            <button class="cart-link" id="cartToggle" type="button">
                <span class="nav-ico icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M6 6h14l-2 8H8L6 6z"></path>
                        <path d="M3 4h2l1 4"></path>
                        <circle cx="9" cy="19" r="1.5"></circle>
                        <circle cx="17" cy="19" r="1.5"></circle>
                    </svg>
                </span>
                <span>Cart</span>
                <span class="cart-badge"><?php echo (int)cart_count(); ?></span>
                <?php if ($cartTotal > 0): ?>
                    <span class="cart-total">?<?php echo number_format($cartTotal, 0); ?></span>
                <?php endif; ?>
            </button>
            <?php if ($user): ?>
                <div class="nav-profile">
                    <a class="avatar" href="<?php echo e($base); ?>profile.php"><?php echo e($userInitial); ?></a>
                    <a class="nav-link" href="<?php echo e($base); ?>profile.php">Profile</a>
                    <a class="nav-link" href="<?php echo e($base); ?>logout.php">Logout</a>
                </div>
            <?php else: ?>
                <a class="button ghost" href="<?php echo e($base); ?>register.php">Register</a>
                <a class="button nav-cta" href="<?php echo e($base); ?>login.php">Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="container">
<?php if ($flash): ?>
    <div class="alert <?php echo e($flash['type']); ?>">
        <?php echo e($flash['message']); ?>
    </div>
<?php endif; ?>
