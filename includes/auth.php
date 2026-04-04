<?php
session_start();

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function require_user() {
    require_login();
    $role = $_SESSION['user_role'] ?? 'user';
    if ($role !== 'user') {
        header('Location: admin-dashboard.php');
        exit;
    }
}

function require_admin() {
    require_login();
    $role = $_SESSION['user_role'] ?? 'user';
    if ($role !== 'admin') {
        header('Location: index.php');
        exit;
    }
}
?>

