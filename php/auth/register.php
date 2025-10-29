<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// Require admin privileges to register new users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    jsonResponse(['error' => 'Unauthorized'], 403);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Validate role
    $valid_roles = ['admin', 'isms_manager', 'auditor', 'employee'];
    if (!in_array($role, $valid_roles)) {
        jsonResponse(['error' => 'Invalid role'], 400);
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $mysqli->prepare('INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $username, $email, $hashed_password, $role);

    if ($stmt->execute()) {
        jsonResponse(['message' => 'User registered successfully']);
    } else {
        jsonResponse(['error' => 'Registration failed'], 400);
    }
}
?>