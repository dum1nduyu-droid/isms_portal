<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/logging.php';

if (isLoggedIn() && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'isms_manager')) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $version_id = $_POST['version_id'];
        $user_id = $_SESSION['user_id'];

        $mysqli->begin_transaction();

        try {
            // Check if the policy is in 'Draft' status
            $stmt = $mysqli->prepare('SELECT pv.status, pd.title FROM policy_versions pv JOIN policy_documents pd ON pv.document_id = pd.id WHERE pv.id = ?');
            $stmt->bind_param('i', $version_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $version = $result->fetch_assoc();

            if (!$version || $version['status'] !== 'Draft') {
                throw new Exception('Policy is not in a submittable state.');
            }

            // Update the status
            $update_stmt = $mysqli->prepare('UPDATE policy_versions SET status = "Review" WHERE id = ?');
            $update_stmt->bind_param('i', $version_id);
            $update_stmt->execute();

            // Create notifications for all admin users
            $admins_stmt = $mysqli->prepare("SELECT id FROM users WHERE role = 'admin'");
            $admins_stmt->execute();
            $admins_result = $admins_stmt->get_result();
            $admins = $admins_result->fetch_all(MYSQLI_ASSOC);

            $notification_msg = "Policy '" . htmlspecialchars($version['title']) . "' has been submitted for review.";
            $notification_link = "#policy-repository"; // Link to the main page
            $notify_stmt = $mysqli->prepare('INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)');

            foreach ($admins as $admin) {
                $notify_stmt->bind_param('iss', $admin['id'], $notification_msg, $notification_link);
                $notify_stmt->execute();
            }

            $mysqli->commit();
            log_activity($user_id, "Submitted policy version ID " . $version_id . " for review.");
            jsonResponse(['message' => 'Policy submitted for review. Admins have been notified.']);

        } catch (Exception $e) {
            $mysqli->rollback();
            jsonResponse(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }
} else {
    jsonResponse(['error' => 'Unauthorized'], 403);
}
?>