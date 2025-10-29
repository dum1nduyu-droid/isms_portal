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

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $reset_link = "http://localhost:8000/#reset-password?token=$token";

        $subject = 'Password Reset Request for ISMS Portal';
        $message = "
        <html>
        <head>
          <title>Password Reset Request</title>
        </head>
        <body>
          <p>You requested a password reset. Click the link below to reset your password:</p>
          <p><a href='{$reset_link}'>Reset Password</a></p>
          <p>This link is valid for 1 hour.</p>
        </body>
        </html>
        ";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: <no-reply@ismsportal.com>' . "\r\n";

        // mail($email, $subject, $message, $headers); // Disabled for local testing

        // In a real application, you would send an email with the reset link.
        // For this prototype, the link is not sent to avoid email server setup.
        // The token is stored in the database and can be retrieved for testing.
        jsonResponse(['message' => 'If an account with that email exists, a password reset link has been generated.']);
    } else {
        // To prevent user enumeration, always return a success-like message
        jsonResponse(['message' => 'If an account with that email exists, a password reset link has been generated.']);
    }
}
?>