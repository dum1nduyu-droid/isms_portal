<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';

if (isLoggedIn()) {
    jsonResponse([
        'loggedIn' => true,
        'role' => isset($_SESSION['role']) ? $_SESSION['role'] : 'user'
    ]);
} else {
    jsonResponse(['loggedIn' => false]);
}
?>
