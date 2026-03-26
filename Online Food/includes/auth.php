<?php
function require_login($redirectPath = 'login.php') {
    if (!isset($_SESSION['user'])) {
        redirect($redirectPath);
    }
}

function require_role($role, $redirectPath = 'login.php') {
    require_login($redirectPath);
    if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== $role) {
        set_flash('error', 'Access denied.');
        redirect($redirectPath);
    }
}
?>
