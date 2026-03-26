<?php
require_once __DIR__ . '/../config/bootstrap.php';

$base = '../';

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
        $returnUrl = $_SERVER['REQUEST_URI'] ?? 'menu.php';
        redirect($returnUrl);
    }
}

$restaurants = $pdo->query('SELECT id, name FROM restaurants ORDER BY name')->fetchAll();
$categories = $pdo->query("SELECT DISTINCT category FROM menu_items WHERE category IS NOT NULL AND category <> '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

$filterId = (int)($_GET['restaurant_id'] ?? 0);
$q = trim($_GET['q'] ?? '');
$min = (float)($_GET['min'] ?? 0);
$max = (float)($_GET['max'] ?? 0);
$category = trim($_GET['category'] ?? '');
$veg = $_GET['veg'] ?? '';
$rating4 = isset($_GET['rating4']);
$fast = isset($_GET['fast']);
$under300 = isset($_GET['under300']);
$offers = isset($_GET['offers']);
$sort = $_GET['sort'] ?? 'name';

$wheres = ['m.is_active = 1'];
$params = [];

if ($filterId > 0) {
    $wheres[] = 'r.id = ?';
    $params[] = $filterId;
}

if ($q !== '') {
    $wheres[] = '(m.item_name LIKE ? OR r.name LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}

if ($min > 0) {
    $wheres[] = 'm.price >= ?';
    $params[] = $min;
}

if ($max > 0) {
    $wheres[] = 'm.price <= ?';
    $params[] = $max;
}

if ($category !== '') {
    $wheres[] = 'm.category = ?';
    $params[] = $category;
}

if ($veg !== '') {
    $wheres[] = 'm.is_veg = ?';
    $params[] = (int)$veg;
}

if ($rating4) {
    $wheres[] = 'r.rating >= 4.0';
}

if ($fast) {
    $wheres[] = 'r.eta_minutes <= 25';
}

if ($under300) {
    $wheres[] = 'm.price <= 300';
}

if ($offers) {
    $wheres[] = 'm.price <= 200';
}

$orderBy = 'm.item_name';
if ($sort === 'price_asc') {
    $orderBy = 'm.price ASC';
} elseif ($sort === 'price_desc') {
    $orderBy = 'm.price DESC';
} elseif ($sort === 'newest') {
    $orderBy = 'm.id DESC';
}

$sql = 'SELECT m.id, m.item_name, m.description, m.category, m.price, m.is_veg, m.image_url, m.restaurant_id, r.name AS restaurant_name, r.cuisine, r.rating, r.eta_minutes, r.price_for_two FROM menu_items m JOIN restaurants r ON m.restaurant_id = r.id WHERE ' . implode(' AND ', $wheres) . ' ORDER BY ' . $orderBy;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$menuItems = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="section-title">
    <h2>Menu Marketplace</h2>
    <span><?php echo count($menuItems); ?> items available</span>
</div>

<div class="layout">
    <aside class="sidebar">
        <h3>Filters</h3>
        <form method="get">
            <div class="filter-group">
                <label>
                    <input type="checkbox" name="rating4" <?php echo $rating4 ? 'checked' : ''; ?>>
                    Rating 4+
                </label>
                <label>
                    <input type="checkbox" name="fast" <?php echo $fast ? 'checked' : ''; ?>>
                    Fast delivery
                </label>
                <label>
                    <input type="checkbox" name="veg" value="1" <?php echo $veg === '1' ? 'checked' : ''; ?>>
                    Pure veg
                </label>
                <label>
                    <input type="checkbox" name="under300" <?php echo $under300 ? 'checked' : ''; ?>>
                    Under ₹300
                </label>
            </div>
            <div class="form-group" style="margin-top: 12px;">
                <label for="category">Category</label>
                <select name="category" id="category">
                    <option value="">All</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo e($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                            <?php echo e($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="restaurant_id">Restaurant</label>
                <select name="restaurant_id" id="restaurant_id">
                    <option value="0">All</option>
                    <?php foreach ($restaurants as $r): ?>
                        <option value="<?php echo (int)$r['id']; ?>" <?php echo $filterId === (int)$r['id'] ? 'selected' : ''; ?>>
                            <?php echo e($r['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="q">Search</label>
                <input type="text" id="q" name="q" placeholder="Search items" value="<?php echo e($q); ?>">
            </div>
            <div class="form-group">
                <label for="sort">Sort</label>
                <select name="sort" id="sort">
                    <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name</option>
                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                </select>
            </div>
            <div style="display:flex; gap:12px; flex-wrap: wrap; margin-top: 12px;">
                <button class="button" type="submit">Apply</button>
                <a class="button secondary" href="menu.php">Reset</a>
            </div>
        </form>
    </aside>

    <div class="menu-list">
    <?php if (empty($menuItems)): ?>
        <div class="card">No menu items found.</div>
    <?php else: ?>
        <?php foreach ($menuItems as $item): ?>
            <div class="menu-item-row">
                <?php $thumb = $item['image_url'] ?: fallback_image($item['id']); ?>
                <img class="menu-thumb" src="<?php echo e($base . $thumb); ?>" alt="<?php echo e($item['item_name']); ?>">
                <div>
                    <h3><?php echo e($item['item_name']); ?></h3>
                    <div class="muted"><?php echo e($item['restaurant_name']); ?> · <?php echo e(display_cuisine($item['cuisine'])); ?></div>
                    <div class="restaurant-meta" style="margin-top: 6px;">
                        <span>⭐ <?php echo display_rating($item['rating'], $item['restaurant_id']); ?></span>
                        <span><?php echo display_eta($item['eta_minutes'], $item['restaurant_id']); ?> mins</span>
                        <?php if (!empty($item['category'])): ?>
                            <span><?php echo e($item['category']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($item['description'])): ?>
                        <div class="muted" style="margin-top: 6px;"><?php echo e($item['description']); ?></div>
                    <?php endif; ?>
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
    <?php endif; ?>
</div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
