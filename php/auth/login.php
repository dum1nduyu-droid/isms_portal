<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';
require_once '../includes/logging.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $mysqli->prepare('SELECT id, password, role FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed_password, $role);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $role;

            // Update last login time
            $stmt_update = $mysqli->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
            $stmt_update->bind_param('i', $user_id);
            $stmt_update->execute();

            log_activity($user_id, 'User logged in');

            jsonResponse(['message' => 'Login successful']);
        } else {
            jsonResponse(['error' => 'Invalid credentials'], 401);
        }
    } else {
        jsonResponse(['error' => 'Invalid credentials'], 401);
    }
}
?>