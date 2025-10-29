<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

if (isLoggedIn()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $notification_id = $_POST['notification_id'];
        $user_id = $_SESSION['user_id'];

        // Ensure the notification belongs to the current user before marking as read
        $stmt = $mysqli->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $notification_id, $user_id);

        if ($stmt->execute()) {
            jsonResponse(['message' => 'Notification marked as read.']);
        } else {
            jsonResponse(['error' => 'Database error.'], 500);
        }
    }
} else {
    jsonResponse(['error' => 'Unauthorized'], 403);
}
?>