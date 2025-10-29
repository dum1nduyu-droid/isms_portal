<?php
require_once '../includes/session.php';
require_once '../includes/access_control.php';
require_once '../includes/functions.php';

if (isLoggedIn()) {
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : 'employee';
    $page = isset($_GET['page']) ? $_GET['page'] : '';

    if (hasAccess($role, $page)) {
        jsonResponse(['authorized' => true]);
    } else {
        jsonResponse(['authorized' => false], 403);
    }
} else {
    // For public pages
    $public_pages = ['dashboard', 'login', 'forgot-password', 'reset-password'];
    $page = isset($_GET['page']) ? $_GET['page'] : '';
    if (in_array($page, $public_pages)) {
        jsonResponse(['authorized' => true]);
    } else {
        jsonResponse(['authorized' => false], 403);
    }
}
?>