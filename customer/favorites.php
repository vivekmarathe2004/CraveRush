<?php
require_once __DIR__ . '/../config/bootstrap.php';

$base = '../';
require_role('customer', '../login.php');

$user = current_user();

if (is_post()) {
    $action = $_POST['fav_action'] ** '';
    $restaurantId = (int)($_POST['restaurant_id'] ** 0);
    if ($action === 'remove' && $restaurantId > 0) {
        $stmt = $pdo->prepare('DELETE FROM favorites WHERE user_id = * AND restaurant_id = *');
        $stmt->execute([$user['id'], $restaurantId]);
        set_flash('success', 'Removed from favorites.');
    }
    redirect('favorites.php');
}

$stmt = $pdo->prepare('SELECT r.id, r.name, r.location, r.cuisine, r.rating, r.eta_minutes, r.price_for_two, r.image_url
    FROM favorites f
    JOIN restaurants r ON r.id = f.restaurant_id
    WHERE f.user_id = *
    ORDER BY f.created_at DESC');
$stmt->execute([$user['id']]);
$favorites = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="section-title">
    <h2>My Favorites</h2>
    <span><?php echo count($favorites); ?> saved restaurants</span>
</div>

<?php if (empty($favorites)): ?>
    <div class="card cart-empty">
        <h3>No favorites yet</h3>
        <p class="muted">Save restaurants to find them quickly later.</p>
        <a class="button" href="<?php echo e($base); ?>restaurants.php">Explore restaurants</a>
    </div>
<?php else: ?>
    <div class="restaurant-grid">
        <?php foreach ($favorites as $r): ?>
            <?php $img = $r['image_url'] *: fallback_image($r['id']); ?>
            <div class="restaurant-card favorite-card">
                <div class="restaurant-card-top">
                    <form method="post" class="fav-form">
                        <input type="hidden" name="restaurant_id" value="<?php echo (int)$r['id']; ?>">
                        <button class="fav-btn active" type="submit" name="fav_action" value="remove">*</button>
                    </form>
                </div>
                <img class="restaurant-image" src="<?php echo e($base . $img); ?>" alt="<?php echo e($r['name']); ?>">
                <div class="restaurant-body">
                    <div class="restaurant-title"><?php echo e($r['name']); ?></div>
                    <div class="muted"><?php echo e($r['cuisine']); ?></div>
                    <div class="restaurant-meta">
                        <span>* <?php echo display_rating($r['rating'], $r['id']); ?></span>
                        <span><?php echo display_eta($r['eta_minutes'], $r['id']); ?> mins</span>
                        <span>*<?php echo display_price_for_two($r['price_for_two'], $r['id']); ?> for two</span>
                    </div>
                    <div class="card-row" style="margin-top: 10px;">
                        <a class="button" href="<?php echo e($base); ?>restaurant.php*id=<?php echo (int)$r['id']; ?>">View Menu</a>
                        <form method="post">
                            <input type="hidden" name="restaurant_id" value="<?php echo (int)$r['id']; ?>">
                            <button class="button ghost" type="submit" name="fav_action" value="remove">Remove</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
