<?php
require_once __DIR__ . '/../config/bootstrap.php';

$base = '../';
require_role('admin', '../login.php');

if (is_post()) {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $restaurantId = (int)($_POST['restaurant_id'] ?? 0);
    $itemName = trim($_POST['item_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $isVeg = isset($_POST['is_veg']) ? 1 : 0;
    $imageUrl = trim($_POST['image_url'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $description = $description !== '' ? $description : null;
    $category = $category !== '' ? $category : null;
    $imageUrl = $imageUrl !== '' ? $imageUrl : null;

    if ($action === 'add' && $restaurantId > 0 && $itemName !== '' && $price > 0) {
        $stmt = $pdo->prepare('INSERT INTO menu_items (restaurant_id, item_name, description, category, price, is_veg, image_url, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$restaurantId, $itemName, $description, $category, $price, $isVeg, $imageUrl, $isActive]);
        set_flash('success', 'Menu item added.');
        redirect('menu_items.php');
    }

    if ($action === 'update' && $id > 0 && $restaurantId > 0 && $itemName !== '' && $price > 0) {
        $stmt = $pdo->prepare('UPDATE menu_items SET restaurant_id = ?, item_name = ?, description = ?, category = ?, price = ?, is_veg = ?, image_url = ?, is_active = ? WHERE id = ?');
        $stmt->execute([$restaurantId, $itemName, $description, $category, $price, $isVeg, $imageUrl, $isActive, $id]);
        set_flash('success', 'Menu item updated.');
        redirect('menu_items.php');
    }

    if ($action === 'delete' && $id > 0) {
        try {
            $stmt = $pdo->prepare('DELETE FROM menu_items WHERE id = ?');
            $stmt->execute([$id]);
            set_flash('success', 'Menu item deleted.');
        } catch (Exception $e) {
            set_flash('error', 'Cannot delete item with existing orders.');
        }
        redirect('menu_items.php');
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editItem = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT id, restaurant_id, item_name, description, category, price, is_veg, image_url, is_active FROM menu_items WHERE id = ?');
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch();
}

$restaurants = $pdo->query('SELECT id, name FROM restaurants ORDER BY name')->fetchAll();
$menuItems = $pdo->query('SELECT m.id, m.item_name, m.description, m.category, m.price, m.is_active, m.is_veg, r.name AS restaurant_name FROM menu_items m JOIN restaurants r ON m.restaurant_id = r.id ORDER BY m.id DESC')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="section-title">
    <h2>Menu Items</h2>
    <span>Manage dishes</span>
</div>

<div class="card" style="margin-bottom: 16px;">
    <h3><?php echo $editItem ? 'Edit Menu Item' : 'Add Menu Item'; ?></h3>
    <form method="post">
        <input type="hidden" name="action" value="<?php echo $editItem ? 'update' : 'add'; ?>">
        <?php if ($editItem): ?>
            <input type="hidden" name="id" value="<?php echo (int)$editItem['id']; ?>">
        <?php endif; ?>
        <div class="form-group">
            <label for="restaurant_id">Restaurant</label>
            <select name="restaurant_id" id="restaurant_id" required>
                <option value="">Select</option>
                <?php foreach ($restaurants as $r): ?>
                    <option value="<?php echo (int)$r['id']; ?>" <?php echo $editItem && (int)$editItem['restaurant_id'] === (int)$r['id'] ? 'selected' : ''; ?>>
                        <?php echo e($r['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="item_name">Item Name</label>
            <input type="text" id="item_name" name="item_name" value="<?php echo e($editItem['item_name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3"><?php echo e($editItem['description'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="category">Category</label>
            <input type="text" id="category" name="category" value="<?php echo e($editItem['category'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="price">Price</label>
            <input type="number" id="price" name="price" step="0.01" value="<?php echo e($editItem['price'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_veg" <?php echo $editItem && (int)$editItem['is_veg'] === 1 ? 'checked' : ''; ?>> Veg
            </label>
        </div>
        <div class="form-group">
            <label for="image_url">Image URL (optional)</label>
            <input type="text" id="image_url" name="image_url" value="<?php echo e($editItem['image_url'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" <?php echo $editItem && (int)$editItem['is_active'] === 1 ? 'checked' : ''; ?>> Active
            </label>
        </div>
        <button class="button" type="submit">Save</button>
        <?php if ($editItem): ?>
            <a class="button secondary" href="menu_items.php">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<table class="table">
    <thead>
        <tr>
            <th>Item</th>
            <th>Restaurant</th>
            <th>Category</th>
            <th>Veg</th>
            <th>Price</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($menuItems as $m): ?>
            <tr>
                <td><?php echo e($m['item_name']); ?></td>
                <td><?php echo e($m['restaurant_name']); ?></td>
                <td><?php echo e($m['category'] ?? ''); ?></td>
                <td>
                    <?php if ((int)$m['is_veg'] === 1): ?>
                        <span class="pill success">Veg</span>
                    <?php else: ?>
                        <span class="pill warning">Non-veg</span>
                    <?php endif; ?>
                </td>
                <td>INR <?php echo number_format((float)$m['price'], 2); ?></td>
                <td>
                    <?php if ((int)$m['is_active'] === 1): ?>
                        <span class="pill success">Active</span>
                    <?php else: ?>
                        <span class="pill warning">Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="menu_items.php?edit=<?php echo (int)$m['id']; ?>">Edit</a>
                    <form method="post" style="display:inline-block; margin-left: 8px;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
                        <button class="button secondary" type="submit" onclick="return confirm('Delete this item?');">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include __DIR__ . '/../includes/footer.php'; ?>
