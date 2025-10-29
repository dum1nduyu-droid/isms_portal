<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

if (isLoggedIn()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user_id = $_SESSION['user_id'];
        $prompt = $_POST['prompt'];

        $stmt = $mysqli->prepare('INSERT INTO ai_prompts (user_id, prompt) VALUES (?, ?)');
        $stmt->bind_param('is', $user_id, $prompt);

        if ($stmt->execute()) {
            jsonResponse(['message' => 'Prompt saved successfully.']);
        } else {
            jsonResponse(['error' => 'Could not save prompt'], 400);
        }
    }
} else {
    jsonResponse(['error' => 'Unauthorized'], 401);
}
?>