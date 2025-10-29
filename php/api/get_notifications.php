<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];

    $query = "
        SELECT
            id,
            message,
            link,
            is_read,
            created_at
        FROM notifications
        WHERE user_id = ? AND is_read = 0
        ORDER BY created_at DESC
    ";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);

    jsonResponse($notifications);
} else {
    jsonResponse(['error' => 'Unauthorized'], 403);
}
?>