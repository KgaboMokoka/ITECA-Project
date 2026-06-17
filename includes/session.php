<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: /handyman-hub/auth/login.php');
        exit();
    }
}

function requireRole($role)
{
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        header('Location: /handyman-hub/index.php');
        exit();
    }
}

function redirectByRole()
{
    if (!isLoggedIn()) return;
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: /handyman-hub/pages/admin/dashboard.php');
            break;
        case 'provider':
            header('Location: /handyman-hub/pages/provider/dashboard.php');
            break;
        case 'client':
            header('Location: /handyman-hub/pages/client/dashboard.php');
            break;
    }
    exit();
}
