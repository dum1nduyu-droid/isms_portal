<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/logging.php';

if (isLoggedIn() && $_SESSION['role'] === 'admin') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $version_id = $_POST['version_id'];
        $user_id = $_SESSION['user_id'];
        $approved_at = date('Y-m-d H:i:s');

        // Check if the policy is in 'Review' status
        $stmt = $mysqli->prepare('SELECT status FROM policy_versions WHERE id = ?');
        $stmt->bind_param('i', $version_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $version = $result->fetch_assoc();

        if ($version && $version['status'] === 'Review') {
            $update_stmt = $mysqli->prepare('UPDATE policy_versions SET status = "Approved", approved_by = ?, approved_at = ? WHERE id = ?');
            $update_stmt->bind_param('isi', $user_id, $approved_at, $version_id);
            if ($update_stmt->execute()) {
                log_activity($user_id, "Approved policy version ID " . $version_id);
                // Future step: Create notification for the uploader
                jsonResponse(['message' => 'Policy approved.']);
            } else {
                jsonResponse(['error' => 'Database error.'], 500);
            }
        } else {
            jsonResponse(['error' => 'Policy is not in an approvable state.'], 400);
        }
    }
} else {
    jsonResponse(['error' => 'Unauthorized'], 403);
}
?>