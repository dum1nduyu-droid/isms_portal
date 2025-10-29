<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

if (isLoggedIn()) {
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

    if ($role === 'admin' || $role === 'auditor') {
        $stmt = $mysqli->prepare('SELECT user_id, action, ip_address, timestamp FROM activity_logs ORDER BY timestamp DESC');
        $stmt->execute();
        $result = $stmt->get_result();
        $logs = $result->fetch_all(MYSQLI_ASSOC);

        jsonResponse($logs);
    } else {
        jsonResponse(['error' => 'Unauthorized'], 403);
    }
} else {
    jsonResponse(['error' => 'Unauthorized'], 401);
}
?>