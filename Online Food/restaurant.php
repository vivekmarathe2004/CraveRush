<?php
require_once __DIR__ . '/config/bootstrap.php';

$base = '';
$user = current_user();
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('error', 'Restaurant not found.');
    redirect('restaurants.php');
}

$stmt = $pdo->prepare('SELECT id, name, location, cuisine, rating, eta_minutes, price_for_two, image_url FROM restaurants WHERE id = ?');
$stmt->execute([$id]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    set_flash('error', 'Restaurant not found.');
    redirect('restaurants.php');
}

if (is_post() && isset($_POST['fav_action'])) {
    $favAction = $_POST['fav_action'] ?? '';
    if (!$user) {
        set_flash('error', 'Login to save favorites.');
        redirect('login.php?return=' . urlencode($_SERVER['REQUEST_URI']));
    }
    if ($user['role'] !== 'customer') {
        set_flash('error', 'Only customers can save favorites.');
        redirect('dashboard.php');
    }
    if ($favAction === 'add') {
        $stmt = $pdo->prepare('INSERT IGNORE INTO favorites (user_id, restaurant_id) VALUES (?, ?)');
        $stmt->execute([$user['id'], $restaurant['id']]);
        set_flash('success', 'Added to favorites.');
    } elseif ($favAction === 'remove') {
        $stmt = $pdo->prepare('DELETE FROM favorites WHERE user_id = ? AND restaurant_id = ?');
        $stmt->execute([$user['id'], $restaurant['id']]);
        set_flash('success', 'Removed from favorites.');
    }
    redirect($_SERVER['REQUEST_URI']);
}

if (is_post()) {
    $menuId = (int)($_POST['menu_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);
    if ($qty < 1) {
        $qty = 1;
    }
    if ($menuId > 0) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        $_SESSION['cart'][$menuId] = ($_SESSION['cart'][$menuId] ?? 0) + $qty;
        set_flash('success', 'Item added to cart.');
        redirect($_SERVER['REQUEST_URI']);
    }
}

$itemStmt = $pdo->prepare('SELECT id, item_name, description, category, price, is_veg, image_url FROM menu_items WHERE restaurant_id = ? AND is_active = 1 ORDER BY category, item_name');
$itemStmt->execute([$id]);
$items = $itemStmt->fetchAll();

$categories = [];
foreach ($items as $item) {
    $cat = $item['category'] ?: 'Recommended';
    if (!isset($categories[$cat])) {
        $categories[$cat] = [];
    }
    $categories[$cat][] = $item;
}

$isFavorite = false;
if ($user && $user['role'] === 'customer') {
    $favStmt = $pdo->prepare('SELECT 1 FROM favorites WHERE user_id = ? AND restaurant_id = ?');
    $favStmt->execute([$user['id'], $restaurant['id']]);
    $isFavorite = (bool)$favStmt->fetchColumn();
}

include __DIR__ . '/includes/header.php';
?>

<div class="restaurant-header">
    <?php $img = $restaurant['image_url'] ?: fallback_image($restaurant['id']); ?>
    <img src="<?php echo e($base . $img); ?>" alt="<?php echo e($restaurant['name']); ?>">
    <div class="restaurant-header-body">
        <div class="restaurant-title"><?php echo e($restaurant['name']); ?></div>
        <div class="muted"><?php echo e($restaurant['cuisine']); ?></div>
        <div class="restaurant-meta">
            <span>⭐ <?php echo display_rating($restaurant['rating'], $restaurant['id']); ?></span>
            <span><?php echo display_eta($restaurant['eta_minutes'], $restaurant['id']); ?> mins</span>
            <span>₹<?php echo display_price_for_two($restaurant['price_for_two'], $restaurant['id']); ?> for two</span>
        </div>
        <div class="muted" style="margin-top: 6px;"><?php echo e($restaurant['location']); ?></div>
        <div class="restaurant-header-actions">
            <div class="pill info">Hygiene certified</div>
            <?php if ($user && $user['role'] === 'customer'): ?>
                <form method="post">
                    <button class="fav-btn <?php echo $isFavorite ? 'active' : ''; ?>" type="submit" name="fav_action" value="<?php echo $isFavorite ? 'remove' : 'add'; ?>">
                        <?php echo $isFavorite ? 'Saved' : 'Save'; ?>
                    </button>
                </form>
            <?php else: ?>
                <a class="fav-btn" href="login.php?return=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">Save</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="category-tabs">
    <?php foreach ($categories as $cat => $list): ?>
        <a href="#cat-<?php echo urlencode($cat); ?>"><?php echo e($cat); ?></a>
    <?php endforeach; ?>
</div>

<?php foreach ($categories as $cat => $list): ?>
    <h3 id="cat-<?php echo urlencode($cat); ?>" style="margin-top: 24px;"><?php echo e($cat); ?></h3>
    <div class="menu-list">
        <?php foreach ($list as $item): ?>
            <div class="menu-item-row">
                <?php $thumb = $item['image_url'] ?: fallback_image($item['id']); ?>
                <img class="menu-thumb" src="<?php echo e($base . $thumb); ?>" alt="<?php echo e($item['item_name']); ?>">
                <div>
                    <h3><?php echo e($item['item_name']); ?></h3>
                    <?php if (!empty($item['description'])): ?>
                        <div class="muted"><?php echo e($item['description']); ?></div>
                    <?php endif; ?>
                    <div class="restaurant-meta" style="margin-top: 6px;">
                        <span class="pill <?php echo (int)$item['is_veg'] === 1 ? 'success' : 'warning'; ?>">
                            <?php echo (int)$item['is_veg'] === 1 ? 'Veg' : 'Non-veg'; ?>
                        </span>
                    </div>
                </div>
                <div class="menu-item-actions">
                    <div style="font-weight: 700;">₹<?php echo number_format((float)$item['price'], 2); ?></div>
                    <form method="post" style="display:flex; gap:10px; align-items:center;">
                        <input type="hidden" name="menu_id" value="<?php echo (int)$item['id']; ?>">
                        <div class="qty-control">
                            <button class="qty-btn" type="button" data-action="dec">-</button>
                            <span class="qty-value">1</span>
                            <button class="qty-btn" type="button" data-action="inc">+</button>
                            <input type="hidden" name="qty" value="1">
                        </div>
                        <button class="button add-bounce" type="submit">Add</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
