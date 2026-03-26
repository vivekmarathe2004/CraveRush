<?php
require_once __DIR__ . '/../config/bootstrap.php';

$base = '../';
require_role('admin', '../login.php');

if (is_post()) {
    $action = $_POST['action'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $cuisine = trim($_POST['cuisine'] ?? '');
    $ratingInput = trim($_POST['rating'] ?? '');
    $etaInput = trim($_POST['eta_minutes'] ?? '');
    $priceInput = trim($_POST['price_for_two'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    $cuisine = $cuisine !== '' ? $cuisine : 'Multi-cuisine';
    $rating = $ratingInput !== '' ? (float)$ratingInput : null;
    $eta = $etaInput !== '' ? (int)$etaInput : null;
    $priceForTwo = $priceInput !== '' ? (int)$priceInput : null;
    $imageUrl = $imageUrl !== '' ? $imageUrl : null;

    if ($action === 'add' && $name !== '' && $location !== '') {
        $stmt = $pdo->prepare('INSERT INTO restaurants (name, location, cuisine, rating, eta_minutes, price_for_two, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $location, $cuisine, $rating, $eta, $priceForTwo, $imageUrl]);
        set_flash('success', 'Restaurant added.');
        redirect('restaurants.php');
    }

    if ($action === 'update' && $id > 0 && $name !== '' && $location !== '') {
        $stmt = $pdo->prepare('UPDATE restaurants SET name = ?, location = ?, cuisine = ?, rating = ?, eta_minutes = ?, price_for_two = ?, image_url = ? WHERE id = ?');
        $stmt->execute([$name, $location, $cuisine, $rating, $eta, $priceForTwo, $imageUrl, $id]);
        set_flash('success', 'Restaurant updated.');
        redirect('restaurants.php');
    }

    if ($action === 'delete' && $id > 0) {
        try {
            $stmt = $pdo->prepare('DELETE FROM restaurants WHERE id = ?');
            $stmt->execute([$id]);
            set_flash('success', 'Restaurant deleted.');
        } catch (Exception $e) {
            set_flash('error', 'Cannot delete restaurant with existing orders.');
        }
        redirect('restaurants.php');
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editRestaurant = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT id, name, location, cuisine, rating, eta_minutes, price_for_two, image_url FROM restaurants WHERE id = ?');
    $stmt->execute([$editId]);
    $editRestaurant = $stmt->fetch();
}

$restaurants = $pdo->query('SELECT id, name, location, cuisine, rating, eta_minutes, price_for_two FROM restaurants ORDER BY name')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="section-title">
    <h2>Restaurants</h2>
    <span>Manage listings</span>
</div>

<div class="card" style="margin-bottom: 16px;">
    <h3><?php echo $editRestaurant ? 'Edit Restaurant' : 'Add Restaurant'; ?></h3>
    <form method="post">
        <input type="hidden" name="action" value="<?php echo $editRestaurant ? 'update' : 'add'; ?>">
        <?php if ($editRestaurant): ?>
            <input type="hidden" name="id" value="<?php echo (int)$editRestaurant['id']; ?>">
        <?php endif; ?>
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?php echo e($editRestaurant['name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="location">Location</label>
            <input type="text" id="location" name="location" value="<?php echo e($editRestaurant['location'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="cuisine">Cuisine</label>
            <input type="text" id="cuisine" name="cuisine" value="<?php echo e($editRestaurant['cuisine'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="rating">Rating (0-5)</label>
            <input type="number" id="rating" name="rating" step="0.1" min="0" max="5" value="<?php echo e($editRestaurant['rating'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="eta_minutes">ETA (mins)</label>
            <input type="number" id="eta_minutes" name="eta_minutes" min="10" max="90" value="<?php echo e($editRestaurant['eta_minutes'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="price_for_two">Price for Two (INR)</label>
            <input type="number" id="price_for_two" name="price_for_two" min="100" step="10" value="<?php echo e($editRestaurant['price_for_two'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="image_url">Image URL (optional)</label>
            <input type="text" id="image_url" name="image_url" value="<?php echo e($editRestaurant['image_url'] ?? ''); ?>">
        </div>
        <button class="button" type="submit">Save</button>
        <?php if ($editRestaurant): ?>
            <a class="button secondary" href="restaurants.php">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<table class="table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Location</th>
            <th>Cuisine</th>
            <th>Rating</th>
            <th>ETA</th>
            <th>For Two</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($restaurants as $r): ?>
            <tr>
                <td><?php echo e($r['name']); ?></td>
                <td><?php echo e($r['location']); ?></td>
                <td><?php echo e(display_cuisine($r['cuisine'])); ?></td>
                <td><?php echo display_rating($r['rating'], $r['id']); ?></td>
                <td><?php echo display_eta($r['eta_minutes'], $r['id']); ?> mins</td>
                <td>INR <?php echo display_price_for_two($r['price_for_two'], $r['id']); ?></td>
                <td>
                    <a href="restaurants.php?edit=<?php echo (int)$r['id']; ?>">Edit</a>
                    <form method="post" style="display:inline-block; margin-left: 8px;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                        <button class="button secondary" type="submit" onclick="return confirm('Delete this restaurant?');">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
