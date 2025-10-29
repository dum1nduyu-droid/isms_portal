<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];

    // Fetch user data
    $stmt = $mysqli->prepare('SELECT username, email, last_login FROM users WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Placeholder data for other widgets
    $stats = [
        'users' => 1, // Example data
        'risks' => 0,
        'policies' => 0,
        'audits' => 0,
    ];

    jsonResponse([
        'user' => $user,
        'stats' => $stats,
    ]);
} else {
    jsonResponse(['error' => 'Unauthorized'], 401);
}
?>