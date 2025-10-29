<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';

// This script checks the current session status and returns it as JSON.

if (isLoggedIn()) {
    // If the user is logged in, return their status, role, username, and ID.
    jsonResponse([
        'loggedIn' => true,
        'role' => $_SESSION['role'],
        'username' => $_SESSION['username'],
        'user_id' => $_SESSION['user_id']
    ]);
} else {
    // If the user is not logged in, reflect that in the response.
    jsonResponse(['loggedIn' => false]);
}
?>
=======
<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';

if (isLoggedIn()) {
    jsonResponse([
        'loggedIn' => true,
        'role' => isset($_SESSION['role']) ? $_SESSION['role'] : 'user'
    ]);
} else {
    jsonResponse(['loggedIn' => false]);
}
?>
