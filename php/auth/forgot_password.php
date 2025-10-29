<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Generate a unique token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $mysqli->prepare('UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?');
    $stmt->bind_param('sss', $token, $expires, $email);

    if ($stmt->execute()) {
        // In a real application, you would send an email with the reset link
        $reset_link = SITE_URL . '#reset-password?token=' . $token;
        jsonResponse(['message' => 'Password reset link sent (in a real app, this would be an email). Reset link: ' . $reset_link]);
    } else {
        jsonResponse(['error' => 'Could not process request'], 400);
    }
}
?>