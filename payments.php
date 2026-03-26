<?php
require_once __DIR__ . '/config/bootstrap.php';

$base = '';
require_login('login.php?return=' . urlencode('/payments.php'));

$user = current_user();

if (is_post()) {
    $action = $_POST['action'] ?? '';
    $method = trim($_POST['method'] ?? '');
    $label = trim($_POST['label'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    $isDefault = isset($_POST['is_default']) ? 1 : 0;

    if ($action === 'add' && $method !== '' && $label !== '') {
        if ($isDefault) {
            $pdo->prepare('UPDATE payments SET is_default = 0 WHERE user_id = ?')->execute([$user['id']]);
        }
        $stmt = $pdo->prepare('INSERT INTO payments (user_id, method, label, is_default) VALUES (?, ?, ?, ?)');
        $stmt->execute([$user['id'], $method, $label, $isDefault]);
        set_flash('success', 'Payment method added.');
        redirect('payments.php');
    }

    if ($action === 'default' && $id > 0) {
        $pdo->prepare('UPDATE payments SET is_default = 0 WHERE user_id = ?')->execute([$user['id']]);
        $pdo->prepare('UPDATE payments SET is_default = 1 WHERE id = ? AND user_id = ?')->execute([$id, $user['id']]);
        set_flash('success', 'Default payment updated.');
        redirect('payments.php');
    }

    if ($action === 'delete' && $id > 0) {
        $pdo->prepare('DELETE FROM payments WHERE id = ? AND user_id = ?')->execute([$id, $user['id']]);
        set_flash('success', 'Payment removed.');
        redirect('payments.php');
    }
}

$stmt = $pdo->prepare('SELECT * FROM payments WHERE user_id = ? ORDER BY is_default DESC, id DESC');
$stmt->execute([$user['id']]);
$payments = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="section-title">
    <h2>Payment Methods</h2>
    <span>Manage your saved options</span>
</div>

<div class="card payment-hero">
    <div>
        <h3>Safe and quick payments</h3>
        <div class="muted">Save your preferred options to check out faster every time.</div>
    </div>
    <div class="payment-hero-badges">
        <span class="pill info">Secure</span>
        <span class="pill success">Instant</span>
    </div>
</div>

<?php if (empty($payments)): ?>
    <div class="card cart-empty">
        <h3>No payment methods yet</h3>
        <p class="muted">Add UPI, Card, or Wallet to speed up checkout.</p>
    </div>
<?php else: ?>
    <div class="payment-grid">
        <?php foreach ($payments as $pay): ?>
            <div class="card payment-card">
                <div class="payment-card-top">
                    <div>
                        <div class="payment-method"><?php echo e($pay['method']); ?></div>
                        <div class="payment-label"><?php echo e($pay['label']); ?></div>
                    </div>
                    <?php if ((int)$pay['is_default'] === 1): ?>
                        <span class="pill success">Default</span>
                    <?php endif; ?>
                </div>
                <div class="payment-actions">
                    <?php if ((int)$pay['is_default'] !== 1): ?>
                        <form method="post">
                            <input type="hidden" name="action" value="default">
                            <input type="hidden" name="id" value="<?php echo (int)$pay['id']; ?>">
                            <button class="button secondary" type="submit">Set Default</button>
                        </form>
                    <?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int)$pay['id']; ?>">
                        <button class="button ghost" type="submit">Remove</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card payment-form-card">
    <h3>Add Payment Method</h3>
    <form class="form payment-form" method="post">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <label for="method">Method</label>
            <select id="method" name="method">
                <option value="UPI">UPI</option>
                <option value="Card">Card</option>
                <option value="Wallet">Wallet</option>
            </select>
        </div>
        <div class="form-group">
            <label for="label">Details</label>
            <input type="text" id="label" name="label" placeholder="name@upi / Visa **** 4286 / Paytm" required>
        </div>
        <label class="muted">
            <input type="checkbox" name="is_default"> Set as default
        </label>
        <div class="payment-submit">
            <button class="button" type="submit">Save Payment</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
