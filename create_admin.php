<?php
// This script should be run from the command line

require_once 'php/includes/db.php';

if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// Get user input
$username = readline('Enter username: ');
$email = readline('Enter email: ');
$password = readline('Enter password: ');
$role = readline('Enter role (admin, isms_manager, auditor, employee): ');

// Validate role
$valid_roles = ['admin', 'isms_manager', 'auditor', 'employee'];
if (!in_array($role, $valid_roles)) {
    die("Invalid role. Please choose one of: " . implode(', ', $valid_roles) . "\n");
}

$hashed_password = password_hash($password, PASSWORD_BCRYPT);

$stmt = $mysqli->prepare('INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)');
$stmt->bind_param('ssss', $username, $email, $hashed_password, $role);

if ($stmt->execute()) {
    echo "User created successfully.\n";
} else {
    echo "Error creating user: " . $stmt->error . "\n";
}
