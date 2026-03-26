<?php
require_once __DIR__ . '/config/bootstrap.php';

$base = '';

$user = current_user();

if (is_post()) {
    $favAction = $_POST['fav_action'] ?? '';
    $restaurantId = (int)($_POST['restaurant_id'] ?? 0);
    $returnTo = $_POST['return'] ?? 'restaurants.php';
    if ($returnTo === '' || strpos($returnTo, '://') !== false || strpos($returnTo, '\\\\') !== false || strpos($returnTo, '//') === 0) {
        $returnTo = 'restaurants.php';
    }

    if (!$user) {
        set_flash('error', 'Login to save favorites.');
        redirect('login.php?return=' . urlencode($returnTo));
    }
    if ($user['role'] !== 'customer') {
        set_flash('error', 'Only customers can save favorites.');
        redirect('dashboard.php');
    }
    if ($restaurantId > 0) {
        if ($favAction === 'add') {
            $stmt = $pdo->prepare('INSERT IGNORE INTO favorites (user_id, restaurant_id) VALUES (?, ?)');
            $stmt->execute([$user['id'], $restaurantId]);
            set_flash('success', 'Added to favorites.');
        } elseif ($favAction === 'remove') {
            $stmt = $pdo->prepare('DELETE FROM favorites WHERE user_id = ? AND restaurant_id = ?');
            $stmt->execute([$user['id'], $restaurantId]);
            set_flash('success', 'Removed from favorites.');
        }
    }
    redirect($returnTo);
}

$q = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'relevance';
$rating4 = isset($_GET['rating4']);
$fast = isset($_GET['fast']);
$veg = isset($_GET['veg']);
$offers = isset($_GET['offers']);
$price = $_GET['price'] ?? '';

$wheres = ['1=1'];
$params = [];

if ($q !== '') {
    $wheres[] = '(r.name LIKE ? OR r.cuisine LIKE ? OR EXISTS (SELECT 1 FROM menu_items mi WHERE mi.restaurant_id = r.id AND mi.item_name LIKE ?))';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}

if ($rating4) {
    $wheres[] = 'r.rating >= 4.0';
}

if ($fast) {
    $wheres[] = 'r.eta_minutes <= 30';
}

$priceClause = '';
if ($price === '200') {
    $priceClause = 'HAVING min_price <= 200';
} elseif ($price === '400') {
    $priceClause = 'HAVING min_price <= 400';
}

$orderBy = 'r.rating DESC';
if ($sort === 'delivery') {
    $orderBy = 'r.eta_minutes ASC';
} elseif ($sort === 'cost_low') {
    $orderBy = 'min_price ASC';
} elseif ($sort === 'rating') {
    $orderBy = 'r.rating DESC';
} elseif ($sort === 'new') {
    $orderBy = 'r.id DESC';
}

$sql = "
    SELECT r.id, r.name, r.location, r.cuisine, r.rating, r.eta_minutes, r.price_for_two, r.image_url,
           MIN(m.price) AS min_price,
           SUM(CASE WHEN m.is_veg = 0 THEN 1 ELSE 0 END) AS non_veg_count
    FROM restaurants r
    LEFT JOIN menu_items m ON m.restaurant_id = r.id AND m.is_active = 1
    WHERE " . implode(' AND ', $wheres) . "
    GROUP BY r.id
";

if ($veg) {
    $sql .= " HAVING non_veg_count = 0";
    if ($offers) {
        $sql .= " AND min_price <= 200";
    }
    if ($priceClause !== '') {
        $sql .= " AND " . substr($priceClause, 7);
    }
} else {
    if ($offers || $priceClause !== '') {
        $clauses = [];
        if ($offers) {
            $clauses[] = "min_price <= 200";
        }
        if ($priceClause !== '') {
            $clauses[] = substr($priceClause, 7);
        }
        if (!empty($clauses)) {
            $sql .= " HAVING " . implode(' AND ', $clauses);
        }
    }
}

$sql .= " ORDER BY $orderBy";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$restaurants = $stmt->fetchAll();

$favoriteMap = [];
if ($user && $user['role'] === 'customer') {
    $favStmt = $pdo->prepare('SELECT restaurant_id FROM favorites WHERE user_id = ?');
    $favStmt->execute([$user['id']]);
    $favoriteIds = $favStmt->fetchAll(PDO::FETCH_COLUMN);
    $favoriteMap = array_flip($favoriteIds ?: []);
}

include __DIR__ . '/includes/header.php';
?>

<div class="section-title">
    <h2>Restaurants</h2>
    <span><?php echo count($restaurants); ?> results</span>
</div>

<div class="listing-toolbar">
    <form method="get" class="listing-search">
        <input type="text" name="q" placeholder="Search within results" value="<?php echo e($q); ?>">
        <?php if ($rating4): ?><input type="hidden" name="rating4" value="1"><?php endif; ?>
        <?php if ($fast): ?><input type="hidden" name="fast" value="1"><?php endif; ?>
        <?php if ($veg): ?><input type="hidden" name="veg" value="1"><?php endif; ?>
        <?php if ($offers): ?><input type="hidden" name="offers" value="1"><?php endif; ?>
        <?php if ($price !== ''): ?><input type="hidden" name="price" value="<?php echo e($price); ?>"><?php endif; ?>
        <?php if ($sort !== ''): ?><input type="hidden" name="sort" value="<?php echo e($sort); ?>"><?php endif; ?>
        <button class="button" type="submit">Search</button>
    </form>
    <form method="get" class="listing-sort">
        <input type="hidden" name="q" value="<?php echo e($q); ?>">
        <?php if ($rating4): ?><input type="hidden" name="rating4" value="1"><?php endif; ?>
        <?php if ($fast): ?><input type="hidden" name="fast" value="1"><?php endif; ?>
        <?php if ($veg): ?><input type="hidden" name="veg" value="1"><?php endif; ?>
        <?php if ($offers): ?><input type="hidden" name="offers" value="1"><?php endif; ?>
        <?php if ($price !== ''): ?><input type="hidden" name="price" value="<?php echo e($price); ?>"><?php endif; ?>
        <label for="sort">Sort</label>
        <select name="sort" id="sort" onchange="this.form.submit()">
            <option value="relevance" <?php echo $sort === 'relevance' ? 'selected' : ''; ?>>Relevance</option>
            <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Rating</option>
            <option value="delivery" <?php echo $sort === 'delivery' ? 'selected' : ''; ?>>Delivery time</option>
            <option value="cost_low" <?php echo $sort === 'cost_low' ? 'selected' : ''; ?>>Cost low to high</option>
            <option value="new" <?php echo $sort === 'new' ? 'selected' : ''; ?>>New</option>
        </select>
    </form>
</div>

<div class="layout">
    <aside class="sidebar">
        <h3>Filters</h3>
        <form method="get" class="filter-group">
            <input type="hidden" name="q" value="<?php echo e($q); ?>">
            <input type="hidden" name="sort" value="<?php echo e($sort); ?>">
            <label><input type="checkbox" name="rating4" <?php echo $rating4 ? 'checked' : ''; ?>> Rating 4+</label>
            <label><input type="checkbox" name="fast" <?php echo $fast ? 'checked' : ''; ?>> Fast delivery</label>
            <label><input type="checkbox" name="veg" <?php echo $veg ? 'checked' : ''; ?>> Pure veg</label>
            <label><input type="checkbox" name="offers" <?php echo $offers ? 'checked' : ''; ?>> Offers</label>
            <div class="form-group">
                <label for="price">Price</label>
                <select name="price" id="price">
                    <option value="">Any</option>
                    <option value="200" <?php echo $price === '200' ? 'selected' : ''; ?>>Under ₹200</option>
                    <option value="400" <?php echo $price === '400' ? 'selected' : ''; ?>>Under ₹400</option>
                </select>
            </div>
            <button class="button" type="submit">Apply</button>
        </form>
    </aside>

    <div class="restaurant-grid">
        <?php if (empty($restaurants)): ?>
            <div class="card">No restaurants found.</div>
        <?php else: ?>
            <?php foreach ($restaurants as $r): ?>
                <?php $img = $r['image_url'] ?: fallback_image($r['id']); ?>
                <div class="restaurant-card">
                    <div class="restaurant-card-top">
                        <?php if ($user && $user['role'] === 'customer'): ?>
                            <?php $isFav = isset($favoriteMap[$r['id']]); ?>
                            <form method="post" class="fav-form">
                                <input type="hidden" name="restaurant_id" value="<?php echo (int)$r['id']; ?>">
                                <input type="hidden" name="return" value="<?php echo e($_SERVER['REQUEST_URI']); ?>">
                                <button class="fav-btn <?php echo $isFav ? 'active' : ''; ?>" type="submit" name="fav_action" value="<?php echo $isFav ? 'remove' : 'add'; ?>">
                                    <?php echo $isFav ? 'Saved' : 'Save'; ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <a class="fav-btn" href="login.php?return=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">Save</a>
                        <?php endif; ?>
                    </div>
                    <img class="restaurant-image" src="<?php echo e($base . $img); ?>" alt="<?php echo e($r['name']); ?>">
                    <div class="restaurant-body">
                        <div class="restaurant-title"><?php echo e($r['name']); ?></div>
                        <div class="muted"><?php echo e($r['cuisine']); ?></div>
                    <div class="restaurant-meta">
                        <span>⭐ <?php echo display_rating($r['rating'], $r['id']); ?></span>
                        <span><?php echo display_eta($r['eta_minutes'], $r['id']); ?> mins</span>
                        <span>₹<?php echo display_price_for_two($r['price_for_two'], $r['id']); ?> for two</span>
                    </div>
                    <?php if (!empty($r['min_price']) && (float)$r['min_price'] <= 200): ?>
                        <div class="card-offer">Save up to ₹50 · Offers</div>
                    <?php endif; ?>
                    <div style="margin-top: 10px;">
                        <a class="button" href="restaurant.php?id=<?php echo (int)$r['id']; ?>">View Menu</a>
                    </div>
                </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
