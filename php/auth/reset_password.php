<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];

    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    $stmt = $mysqli->prepare('UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE reset_token = ? AND reset_token_expires > NOW()');
    $stmt->bind_param('ss', $hashed_password, $token);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        jsonResponse(['message' => 'Password reset successfully']);
    } else {
        jsonResponse(['error' => 'Invalid or expired token'], 400);
    }
}
?>