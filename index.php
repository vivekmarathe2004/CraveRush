<?php
require_once __DIR__ . '/config/bootstrap.php';

$base = '';

$restaurants = $pdo->query('SELECT id, name, location, cuisine, rating, eta_minutes, price_for_two, image_url FROM restaurants ORDER BY id DESC LIMIT 6')->fetchAll();
$topRated = $pdo->query('SELECT id, name, location, cuisine, rating, eta_minutes, price_for_two, image_url FROM restaurants WHERE rating IS NOT NULL ORDER BY rating DESC LIMIT 8')->fetchAll();
$topCollege = $pdo->query('SELECT id, name, location, cuisine, rating, eta_minutes, price_for_two, image_url FROM restaurants WHERE rating IS NOT NULL ORDER BY rating DESC LIMIT 8')->fetchAll();
$fastDelivery = $pdo->query('SELECT id, name, location, cuisine, rating, eta_minutes, price_for_two, image_url FROM restaurants WHERE eta_minutes IS NOT NULL AND eta_minutes <= 30 ORDER BY eta_minutes ASC LIMIT 8')->fetchAll();
$newOn = $pdo->query('SELECT id, name, location, cuisine, rating, eta_minutes, price_for_two, image_url FROM restaurants ORDER BY id DESC LIMIT 8')->fetchAll();
$breakfast = $pdo->query("SELECT m.id, m.item_name, m.price, m.image_url, m.restaurant_id, r.name AS restaurant_name FROM menu_items m JOIN restaurants r ON m.restaurant_id = r.id WHERE m.is_active = 1 AND m.category = 'Breakfast' ORDER BY m.id DESC LIMIT 8")->fetchAll();
$budget = $pdo->query('SELECT m.id, m.item_name, m.price, m.image_url, m.restaurant_id, r.name AS restaurant_name FROM menu_items m JOIN restaurants r ON m.restaurant_id = r.id WHERE m.is_active = 1 AND m.price <= 200 ORDER BY m.price ASC LIMIT 8')->fetchAll();
$menuItems = $pdo->query('SELECT m.id, m.item_name, m.price, m.image_url, m.restaurant_id, r.name AS restaurant_name, r.rating, r.eta_minutes FROM menu_items m JOIN restaurants r ON m.restaurant_id = r.id WHERE m.is_active = 1 ORDER BY m.id DESC LIMIT 6')->fetchAll();
$stats = [
    'restaurants' => $pdo->query('SELECT COUNT(*) FROM restaurants')->fetchColumn(),
    'dishes' => $pdo->query('SELECT COUNT(*) FROM menu_items WHERE is_active = 1')->fetchColumn(),
    'orders' => $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'today' => $pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()')->fetchColumn(),
];
$offers = [
    ['title' => '50% OFF up to ?50', 'desc' => 'Use code SAVE50 above ?299.', 'badge' => 'Limited'],
    ['title' => 'Free Delivery', 'desc' => 'Apply FREESHIP on all orders.', 'badge' => 'Hot'],
    ['title' => '?100 OFF', 'desc' => 'SAVE100 on orders above ?499.', 'badge' => 'Weekend'],
    ['title' => 'Flat ?75 OFF', 'desc' => 'Use TASTY75 above ?349.', 'badge' => 'New'],
    ['title' => 'Combo Deals', 'desc' => 'Save up to ?120 on combos.', 'badge' => 'Combo'],
    ['title' => 'Bank Offer', 'desc' => '5% cashback on select cards.', 'badge' => 'Bank'],
];
$categories = $pdo->query("SELECT DISTINCT category FROM menu_items WHERE category IS NOT NULL AND category <> '' ORDER BY category LIMIT 12")->fetchAll(PDO::FETCH_COLUMN);
$categories = !empty($categories) ? $categories : ['Misal', 'Thali', 'Biryani', 'South Indian', 'Chaat', 'Snacks', 'Dessert', 'Beverages'];

include __DIR__ . '/includes/header.php';
?>

<section class="hero-swiggy">
    <div class="hero-content">
        <div class="hero-tag">Food, fast across India</div>
        <h1>Order from your favorite restaurants</h1>
        <p>Fresh, local, and delivered in minutes.</p>
        <form class="hero-search hero-search-large" action="restaurants.php" method="get">
            <input type="text" name="q" placeholder="Search for restaurants, cuisines or dishes">
            <button class="button" type="submit">Order Now</button>
        </form>
        <div class="chip-row">
            <a class="chip" href="restaurants.php?offers=1">Offers</a>
            <a class="chip" href="restaurants.php?veg=1">Pure Veg</a>
            <a class="chip" href="restaurants.php?fast=1">Fast Delivery</a>
            <a class="chip" href="restaurants.php?rating4=1">Rating 4.0+</a>
        </div>
    </div>
    <div class="hero-visual hero-visual-grid">
        <img src="assets/images/dishes/biryani.jpg" alt="Biryani">
        <img src="assets/images/dishes/misal.jpg" alt="Misal">
        <img src="assets/images/dishes/dosa.jpg" alt="Dosa">
        <img src="assets/images/dishes/gulab-jamun.jpg" alt="Dessert">
    </div>
</section>

<div class="stats-grid">
    <div class="stat-card">
        <strong><?php echo (int)$stats['restaurants']; ?></strong>
        <span>Restaurants</span>
    </div>
    <div class="stat-card">
        <strong><?php echo (int)$stats['dishes']; ?></strong>
        <span>Dishes available</span>
    </div>
    <div class="stat-card">
        <strong><?php echo (int)$stats['orders']; ?></strong>
        <span>Orders delivered</span>
    </div>
    <div class="stat-card">
        <strong><?php echo (int)$stats['today']; ?></strong>
        <span>Orders today</span>
    </div>
</div>

<div class="section-title">
    <h2>How CraveRush Works</h2>
    <span>Order in minutes, track in real time</span>
</div>
<div class="feature-grid">
    <div class="feature-card">
        <div class="feature-icon">??</div>
        <strong>Discover</strong>
        <span class="muted">Browse verified restaurants across India and curated menus.</span>
    </div>
    <div class="feature-card">
        <div class="feature-icon">??</div>
        <strong>Deliver</strong>
        <span class="muted">Live order tracking with fast local delivery.</span>
    </div>
    <div class="feature-card">
        <div class="feature-icon">?</div>
        <strong>Delight</strong>
        <span class="muted">Smart promos, safe packing, and hot meals.</span>
    </div>
</div>

<div class="category-row">
    <?php foreach ($categories as $category): ?>
        <a class="category-pill" href="restaurants.php?q=<?php echo urlencode($category); ?>">
            <?php echo e($category); ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="section-title">
    <h2>Top rated near you</h2>
    <span>Rated 4.0 and above</span>
</div>
<div class="scroll-row">
    <?php foreach ($topCollege as $r): ?>
        <div class="restaurant-card scroll-card">
            <?php $img = $r['image_url'] ?: fallback_image($r['id']); ?>
            <img class="restaurant-image" src="<?php echo e($base . $img); ?>" alt="<?php echo e($r['name']); ?>">
            <div class="restaurant-body">
                <div class="restaurant-title"><?php echo e($r['name']); ?></div>
                <div class="restaurant-meta">
                    <span>? <?php echo display_rating($r['rating'], $r['id']); ?></span>
                    <span><?php echo display_eta($r['eta_minutes'], $r['id']); ?> mins</span>
                    <span>?<?php echo display_price_for_two($r['price_for_two'], $r['id']); ?> for two</span>
                </div>
                <div class="muted" style="margin-top: 6px;"><?php echo e($r['location']); ?> � <?php echo e(display_cuisine($r['cuisine'])); ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="banner-row">
    <div class="banner-card">
        <strong>New on CraveRush</strong>
        <span class="muted">Fresh restaurants added this week.</span>
    </div>
</div>

<div class="section-title" id="offers">
    <h2>Offers for you</h2>
    <span>Best value near you</span>
</div>
<div class="scroll-row">
    <?php foreach ($offers as $offer): ?>
            <div class="offer-card scroll-card">
                <div class="offer-badge"><?php echo e($offer['badge']); ?></div>
                <strong><?php echo e($offer['title']); ?></strong>
                <div class="muted"><?php echo e($offer['desc']); ?></div>
                <div style="margin-top: 10px;">
                    <a class="button ghost ripple" href="restaurants.php?offers=1">Grab offer</a>
                </div>
            </div>
    <?php endforeach; ?>
</div>

<div class="section-title">
    <h2>Fast delivery</h2>
    <span>Under 30 minutes</span>
</div>
<div class="scroll-row">
    <?php foreach ($fastDelivery as $r): ?>
        <div class="restaurant-card scroll-card">
            <?php $img = $r['image_url'] ?: fallback_image($r['id']); ?>
            <img class="restaurant-image" src="<?php echo e($base . $img); ?>" alt="<?php echo e($r['name']); ?>">
            <div class="restaurant-body">
                <div class="restaurant-title"><?php echo e($r['name']); ?></div>
                <div class="restaurant-meta">
                    <span>? <?php echo display_rating($r['rating'], $r['id']); ?></span>
                    <span><?php echo display_eta($r['eta_minutes'], $r['id']); ?> mins</span>
                    <span>?<?php echo display_price_for_two($r['price_for_two'], $r['id']); ?> for two</span>
                </div>
                <div class="muted" style="margin-top: 6px;"><?php echo e($r['location']); ?> � <?php echo e(display_cuisine($r['cuisine'])); ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="banner-row">
    <div class="banner-card">
        <strong>Breakfast specials</strong>
        <span class="muted">Start your day with nationwide favorites.</span>
    </div>
</div>

<div class="section-title">
    <h2>Pocket-friendly under ?200</h2>
    <span>Great meals, great value</span>
</div>
<div class="scroll-row">
    <?php foreach ($budget as $item): ?>
        <?php $img = $item['image_url'] ?: fallback_image($item['id']); ?>
        <div class="dish-card scroll-card">
            <img src="<?php echo e($base . $img); ?>" alt="<?php echo e($item['item_name']); ?>">
            <div class="dish-body">
                <div class="dish-title"><?php echo e($item['item_name']); ?></div>
                <div class="muted"><?php echo e($item['restaurant_name']); ?></div>
                <div class="dish-price">?<?php echo number_format((float)$item['price'], 2); ?></div>
                <div class="dish-actions">
                    <a class="button ghost ripple" href="restaurant.php?id=<?php echo (int)$item['restaurant_id']; ?>">View Menu</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="section-title">
    <h2>New on CraveRush</h2>
    <span>Recently added restaurants</span>
</div>
<div class="scroll-row">
    <?php foreach ($newOn as $r): ?>
        <div class="restaurant-card scroll-card">
            <?php $img = $r['image_url'] ?: fallback_image($r['id']); ?>
            <img class="restaurant-image" src="<?php echo e($base . $img); ?>" alt="<?php echo e($r['name']); ?>">
            <div class="restaurant-body">
                <div class="restaurant-title"><?php echo e($r['name']); ?></div>
                <div class="restaurant-meta">
                    <span>? <?php echo display_rating($r['rating'], $r['id']); ?></span>
                    <span><?php echo display_eta($r['eta_minutes'], $r['id']); ?> mins</span>
                    <span>?<?php echo display_price_for_two($r['price_for_two'], $r['id']); ?> for two</span>
                </div>
                <div class="muted" style="margin-top: 6px;"><?php echo e($r['location']); ?> � <?php echo e(display_cuisine($r['cuisine'])); ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="section-title">
    <h2>Breakfast specials</h2>
    <span>Morning cravings sorted</span>
</div>
<div class="scroll-row">
    <?php foreach ($breakfast as $item): ?>
        <?php $img = $item['image_url'] ?: fallback_image($item['id']); ?>
        <div class="dish-card scroll-card">
            <img src="<?php echo e($base . $img); ?>" alt="<?php echo e($item['item_name']); ?>">
            <div class="dish-body">
                <div class="dish-title"><?php echo e($item['item_name']); ?></div>
                <div class="muted"><?php echo e($item['restaurant_name']); ?></div>
                <div class="dish-price">?<?php echo number_format((float)$item['price'], 2); ?></div>
                <div class="dish-actions">
                    <a class="button ghost ripple" href="restaurant.php?id=<?php echo (int)$item['restaurant_id']; ?>">View Menu</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="section-title">
    <h2>Popular Restaurants across India</h2>
    <span>Top picks near you</span>
</div>
<div class="restaurant-grid">
    <?php if (empty($restaurants)): ?>
        <div class="card">No restaurants yet. Admins can add some.</div>
    <?php else: ?>
        <?php foreach ($restaurants as $r): ?>
            <div class="restaurant-card">
                <?php $img = $r['image_url'] ?: fallback_image($r['id']); ?>
                <img class="restaurant-image" src="<?php echo e($base . $img); ?>" alt="<?php echo e($r['name']); ?>">
                <div class="restaurant-body">
                    <div class="restaurant-title"><?php echo e($r['name']); ?></div>
                    <div class="restaurant-meta">
                        <span>? <?php echo display_rating($r['rating'], $r['id']); ?></span>
                        <span><?php echo display_eta($r['eta_minutes'], $r['id']); ?> mins</span>
                        <span>?<?php echo display_price_for_two($r['price_for_two'], $r['id']); ?> for two</span>
                    </div>
                    <div class="muted" style="margin-top: 6px;"><?php echo e($r['location']); ?> � <?php echo e(display_cuisine($r['cuisine'])); ?></div>
                    <div style="margin-top: 12px;">
                        <a class="button" href="restaurant.php?id=<?php echo (int)$r['id']; ?>">View Menu</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="section-title">
    <h2>Popular Dishes</h2>
    <span>Most loved this week</span>
</div>
<div class="dish-grid">
    <?php foreach ($menuItems as $item): ?>
        <?php $img = $item['image_url'] ?: fallback_image($item['id']); ?>
    <div class="dish-card">
        <img src="<?php echo e($base . $img); ?>" alt="<?php echo e($item['item_name']); ?>">
        <div class="dish-body">
            <div class="dish-title"><?php echo e($item['item_name']); ?></div>
            <div class="muted"><?php echo e($item['restaurant_name']); ?></div>
            <div class="dish-meta">
                <span>? <?php echo display_rating($item['rating'], $item['id']); ?></span>
                <span><?php echo display_eta($item['eta_minutes'], $item['id']); ?> mins</span>
            </div>
            <div class="dish-price">?<?php echo number_format((float)$item['price'], 2); ?></div>
            <div class="dish-actions">
                <a class="button ghost ripple" href="restaurant.php?id=<?php echo (int)$item['restaurant_id']; ?>">View Menu</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="app-strip">
    <div>
        <h3>Get the CraveRush App</h3>
        <p class="muted">Exclusive offers, real-time tracking, and faster checkout.</p>
    </div>
    <div class="app-buttons">
        <a class="button ghost" href="#">App Store</a>
        <a class="button ghost" href="#">Google Play</a>
    </div>
</div>

<div class="cta-band">
    <div>
        <h3>Ready to order?</h3>
        <p>Discover India뭩 best food in minutes with live tracking.</p>
    </div>
    <a class="button" href="customer/menu.php">Browse Menu</a>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
