<?php
require_once __DIR__ . '/config/bootstrap.php';

$base = '';
require_login('login.php?return=' . urlencode('/addresses.php'));

$user = current_user();

if (is_post()) {
    $action = $_POST['action'] ?? '';
    $label = trim($_POST['label'] ?? '');
    $line1 = trim($_POST['line1'] ?? '');
    $line2 = trim($_POST['line2'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    $isDefault = isset($_POST['is_default']) ? 1 : 0;

    if ($action === 'add' && $label && $line1 && $city && $pincode) {
        if ($isDefault) {
            $pdo->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?')->execute([$user['id']]);
        }
        $stmt = $pdo->prepare('INSERT INTO addresses (user_id, label, line1, line2, city, pincode, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$user['id'], $label, $line1, $line2 !== '' ? $line2 : null, $city, $pincode, $isDefault]);
        set_flash('success', 'Address added.');
        redirect('addresses.php');
    }

    if ($action === 'update' && $id > 0 && $label && $line1 && $city && $pincode) {
        if ($isDefault) {
            $pdo->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?')->execute([$user['id']]);
        }
        $stmt = $pdo->prepare('UPDATE addresses SET label = ?, line1 = ?, line2 = ?, city = ?, pincode = ?, is_default = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$label, $line1, $line2 !== '' ? $line2 : null, $city, $pincode, $isDefault, $id, $user['id']]);
        set_flash('success', 'Address updated.');
        redirect('addresses.php');
    }

    if ($action === 'delete' && $id > 0) {
        $stmt = $pdo->prepare('DELETE FROM addresses WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $user['id']]);
        set_flash('success', 'Address removed.');
        redirect('addresses.php');
    }

    if ($action === 'default' && $id > 0) {
        $pdo->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?')->execute([$user['id']]);
        $pdo->prepare('UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?')->execute([$id, $user['id']]);
        set_flash('success', 'Default address updated.');
        redirect('addresses.php');
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editAddress = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM addresses WHERE id = ? AND user_id = ?');
    $stmt->execute([$editId, $user['id']]);
    $editAddress = $stmt->fetch();
}

$stmt = $pdo->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC');
$stmt->execute([$user['id']]);
$addresses = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="section-title">
    <h2>Saved Addresses</h2>
    <span>Choose your delivery location</span>
</div>

<div class="card-grid">
    <?php if (empty($addresses)): ?>
        <div class="card">No addresses yet.</div>
    <?php else: ?>
        <?php foreach ($addresses as $addr): ?>
            <div class="card">
                <h3><?php echo e($addr['label']); ?></h3>
                <div class="muted"><?php echo e($addr['line1']); ?></div>
                <?php if (!empty($addr['line2'])): ?>
                    <div class="muted"><?php echo e($addr['line2']); ?></div>
                <?php endif; ?>
                <div class="muted"><?php echo e($addr['city']); ?> · <?php echo e($addr['pincode']); ?></div>
                <?php if ((int)$addr['is_default'] === 1): ?>
                    <div class="pill success" style="margin-top: 8px;">Default</div>
                <?php endif; ?>
                <div style="margin-top: 12px; display:flex; gap:10px; flex-wrap:wrap;">
                    <form method="post">
                        <input type="hidden" name="action" value="default">
                        <input type="hidden" name="id" value="<?php echo (int)$addr['id']; ?>">
                        <button class="button secondary" type="submit">Set Default</button>
                    </form>
                    <a class="button" href="addresses.php?edit=<?php echo (int)$addr['id']; ?>">Edit</a>
                    <form method="post">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int)$addr['id']; ?>">
                        <button class="button ghost" type="submit">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="card" style="margin-top: 16px;">
    <h3><?php echo $editAddress ? 'Edit Address' : 'Add New Address'; ?></h3>
    <form class="form" style="margin-top: 12px;" method="post">
        <input type="hidden" name="action" value="<?php echo $editAddress ? 'update' : 'add'; ?>">
        <?php if ($editAddress): ?>
            <input type="hidden" name="id" value="<?php echo (int)$editAddress['id']; ?>">
        <?php endif; ?>
        <div class="form-group">
            <label for="label">Label</label>
            <input type="text" id="label" name="label" value="<?php echo e($editAddress['label'] ?? ''); ?>" placeholder="Home / Work" required>
        </div>
        <div class="form-group">
            <label for="line1">Address line 1</label>
            <input type="text" id="line1" name="line1" value="<?php echo e($editAddress['line1'] ?? ''); ?>" placeholder="Street, area, landmark" required>
        </div>
        <div class="form-group">
            <label for="line2">Address line 2</label>
            <input type="text" id="line2" name="line2" value="<?php echo e($editAddress['line2'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="city">City</label>
            <input type="text" id="city" name="city" value="<?php echo e($editAddress['city'] ?? 'Nashik'); ?>" required>
        </div>
        <div class="form-group">
            <label for="pincode">Pincode</label>
            <input type="text" id="pincode" name="pincode" value="<?php echo e($editAddress['pincode'] ?? ''); ?>" required>
        </div>
        <label>
            <input type="checkbox" name="is_default" <?php echo $editAddress && (int)$editAddress['is_default'] === 1 ? 'checked' : ''; ?>> Set as default
        </label>
        <div style="margin-top: 12px;">
            <button class="button" type="submit">Save Address</button>
            <?php if ($editAddress): ?>
                <a class="button secondary" href="addresses.php">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
