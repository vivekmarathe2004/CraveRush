<?php
require_once __DIR__ . '/config/bootstrap.php';

$base = '';
require_login('login.php?return=' . urlencode('/profile.php'));

$user = current_user();
$userStmt = $pdo->prepare('SELECT name, email, phone, address_line, city FROM users WHERE id = ?');
$userStmt->execute([$user['id']]);
$userRow = $userStmt->fetch() ?: [];
$user = array_merge($user, $userRow);

if (is_post()) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $addressLine = trim($_POST['address_line'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '') {
        set_flash('error', 'Name is required.');
        redirect('profile.php');
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Enter a valid email.');
        redirect('profile.php');
    }

    $emailCheck = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
    $emailCheck->execute([$email, $user['id']]);
    if ($emailCheck->fetch()) {
        set_flash('error', 'Email already in use.');
        redirect('profile.php');
    }

    $updatePassword = ($currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '');
    if ($updatePassword) {
        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            set_flash('error', 'Fill all password fields to change your password.');
            redirect('profile.php');
        }
        if (strlen($newPassword) < 6) {
            set_flash('error', 'New password must be at least 6 characters.');
            redirect('profile.php');
        }
        if ($newPassword !== $confirmPassword) {
            set_flash('error', 'New passwords do not match.');
            redirect('profile.php');
        }
        $pwStmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $pwStmt->execute([$user['id']]);
        $hash = $pwStmt->fetchColumn();
        if (!$hash || !password_verify($currentPassword, $hash)) {
            set_flash('error', 'Current password is incorrect.');
            redirect('profile.php');
        }
    }

    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ?, address_line = ?, city = ? WHERE id = ?');
    $stmt->execute([
        $name,
        $email,
        $phone !== '' ? $phone : null,
        $addressLine !== '' ? $addressLine : null,
        $city !== '' ? $city : null,
        $user['id']
    ]);
    if ($updatePassword) {
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$newHash, $user['id']]);
    }

    $_SESSION['user']['name'] = $name;
    $_SESSION['user']['email'] = $email;
    set_flash('success', $updatePassword ? 'Profile and password updated.' : 'Profile updated.');
    redirect('profile.php');
}

$counts = [
    'addresses' => $pdo->prepare('SELECT COUNT(*) FROM addresses WHERE user_id = ?'),
    'payments' => $pdo->prepare('SELECT COUNT(*) FROM payments WHERE user_id = ?'),
    'orders' => $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?')
];
$counts['addresses']->execute([$user['id']]);
$counts['payments']->execute([$user['id']]);
$counts['orders']->execute([$user['id']]);
$addressCount = (int)$counts['addresses']->fetchColumn();
$paymentCount = (int)$counts['payments']->fetchColumn();
$orderCount = (int)$counts['orders']->fetchColumn();

include __DIR__ . '/includes/header.php';
?>

<div class="section-title">
    <h2>My Profile</h2>
    <span>Manage your account</span>
</div>

<div class="profile-layout">
    <section class="profile-main">
        <div class="card profile-hero">
            <div class="profile-avatar"><?php echo e(strtoupper(substr($user['name'], 0, 1))); ?></div>
            <div class="profile-meta">
                <h3><?php echo e($user['name']); ?></h3>
                <div class="muted"><?php echo e($user['email']); ?></div>
                <div class="muted"><?php echo e($user['phone'] ?? ''); ?></div>
                <?php if (!empty($user['address_line']) || !empty($user['city'])): ?>
                    <div class="muted"><?php echo e(trim(($user['address_line'] ?? '') . ' ' . ($user['city'] ?? ''))); ?></div>
                <?php endif; ?>
                <div class="pill info">Member</div>
            </div>
        </div>

        <div class="card profile-form-card">
            <h3>Update Profile</h3>
            <form class="form profile-form" method="post">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="<?php echo e($user['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo e($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?php echo e($user['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="address_line">Address</label>
                    <input type="text" id="address_line" name="address_line" value="<?php echo e($user['address_line'] ?? ''); ?>" placeholder="Street, area">
                </div>
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="<?php echo e($user['city'] ?? ''); ?>">
                </div>
                <div class="profile-divider"></div>
                <h4>Change Password</h4>
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <div class="password-field">
                        <input type="password" id="current_password" name="current_password">
                        <button class="password-toggle" type="button" data-target="current_password">Show</button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="password-field">
                        <input type="password" id="new_password" name="new_password">
                        <button class="password-toggle" type="button" data-target="new_password">Show</button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Repeat New Password</label>
                    <div class="password-field">
                        <input type="password" id="confirm_password" name="confirm_password">
                        <button class="password-toggle" type="button" data-target="confirm_password">Show</button>
                    </div>
                </div>
                <div class="muted profile-note">Leave password fields empty to keep your current password.</div>
                <button class="button" type="submit">Save Changes</button>
            </form>
        </div>
    </section>

    <aside class="profile-side">
        <div class="card profile-stats">
            <div class="profile-stat">
                <strong><?php echo $orderCount; ?></strong>
                <span class="muted">Orders</span>
            </div>
            <div class="profile-stat">
                <strong><?php echo $addressCount; ?></strong>
                <span class="muted">Addresses</span>
            </div>
            <div class="profile-stat">
                <strong><?php echo $paymentCount; ?></strong>
                <span class="muted">Payments</span>
            </div>
        </div>

        <div class="card profile-actions">
            <h3>Quick Actions</h3>
            <a class="button" href="addresses.php">Manage Addresses</a>
            <a class="button secondary" href="payments.php">Payment Methods</a>
            <a class="button ghost" href="customer/orders.php">Order History</a>
            <a class="button ghost" href="help.php">Help & Support</a>
        </div>
    </aside>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
