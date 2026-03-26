<?php
require_once __DIR__ . '/config/bootstrap.php';

$base = '';

if (is_post()) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        set_flash('success', 'Welcome back.');
        $returnTo = $_GET['return'] ?? '';
        if ($returnTo !== '' && strpos($returnTo, '://') === false && strpos($returnTo, '\\\\') === false && strpos($returnTo, '//') !== 0) {
            redirect($returnTo);
        }
        redirect('dashboard.php');
    } else {
        set_flash('error', 'Invalid credentials.');
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="section-title">
    <h2>Login</h2>
    <span>Welcome back</span>
</div>
<div class="auth-shell">
    <div class="card auth-card">
        <div class="auth-header">
            <div>
                <h3>Sign in to CraveRush</h3>
                <div class="muted">Use your email and password to continue.</div>
            </div>
        </div>
        <form class="form auth-form" method="post" action="login.php">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" required>
                    <button class="password-toggle" type="button" data-target="password">Show</button>
                </div>
            </div>
            <button class="button" type="submit">Login</button>
        </form>
        <div class="auth-footer">
            <span class="muted">New here?</span>
            <a class="button ghost" href="register.php">Create account</a>
        </div>
    </div>

    <div class="card auth-side">
        <h3>Test Credentials</h3>
        <div class="muted">Admin: admin@nashik.test / admin123</div>
        <div class="muted">Customer: customer@nashik.test / customer123</div>
        <div class="auth-tip">Tip: use the customer account to place orders.</div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
