<?php
function log_activity($user_id, $action) {
    global $mysqli;
    $ip_address = $_SERVER['REMOTE_ADDR'];

    $stmt = $mysqli->prepare('INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)');
    $stmt->bind_param('iss', $user_id, $action, $ip_address);
    $stmt->execute();
}
?>