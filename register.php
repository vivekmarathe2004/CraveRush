<?php
require_once __DIR__ . '/config/bootstrap.php';

$base = '';

if (is_post()) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
        set_flash('error', 'All fields are required.');
    } elseif ($password !== $confirmPassword) {
        set_flash('error', 'Passwords do not match.');
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            set_flash('error', 'Email already registered.');
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $insert = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, "customer")');
            $insert->execute([$name, $email, $hash]);
            set_flash('success', 'Account created. Please login.');
            redirect('login.php');
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="section-title">
    <h2>Create Account</h2>
    <span>Start ordering in minutes</span>
</div>
<div class="auth-shell">
    <div class="card auth-card">
        <div class="auth-header">
            <div>
                <h3>Create your CraveRush account</h3>
                <div class="muted">Join to save addresses, payments, and order faster.</div>
            </div>
        </div>
        <form class="form auth-form" method="post" action="register.php">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required>
            </div>
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
            <div class="form-group">
                <label for="confirm_password">Repeat Password</label>
                <div class="password-field">
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <button class="password-toggle" type="button" data-target="confirm_password">Show</button>
                </div>
            </div>
            <button class="button" type="submit">Create Account</button>
        </form>
        <div class="auth-footer">
            <span class="muted">Already have an account?</span>
            <a class="button ghost" href="login.php">Sign in</a>
        </div>
    </div>

    <div class="card auth-side">
        <h3>Why create an account?</h3>
        <div class="muted">Save delivery addresses and payment methods.</div>
        <div class="muted">Track orders and manage preferences easily.</div>
        <div class="auth-tip">We keep your data safe and secure.</div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
