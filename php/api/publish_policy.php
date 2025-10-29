<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/logging.php';

if (isLoggedIn() && $_SESSION['role'] === 'admin') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $version_id = $_POST['version_id'];
        $user_id = $_SESSION['user_id'];

        // Start a transaction to ensure atomicity
        $mysqli->begin_transaction();

        try {
            // Check if the target version is in 'Approved' status
            $stmt = $mysqli->prepare('SELECT document_id, status FROM policy_versions WHERE id = ?');
            $stmt->bind_param('i', $version_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $version = $result->fetch_assoc();

            if (!$version || $version['status'] !== 'Approved') {
                throw new Exception('Policy is not in a publishable state.');
            }

            $document_id = $version['document_id'];

            // 1. Set any currently 'Published' versions of this document to 'Archived'
            $archive_stmt = $mysqli->prepare('UPDATE policy_versions SET status = "Archived" WHERE document_id = ? AND status = "Published"');
            $archive_stmt->bind_param('i', $document_id);
            $archive_stmt->execute();

            // 2. Set the target version to 'Published'
            $publish_stmt = $mysqli->prepare('UPDATE policy_versions SET status = "Published" WHERE id = ?');
            $publish_stmt->bind_param('i', $version_id);
            if (!$publish_stmt->execute()) {
                throw new Exception('Could not publish the new version.');
            }

            $mysqli->commit();
            log_activity($user_id, "Published policy version ID " . $version_id);
            jsonResponse(['message' => 'Policy published successfully. The previous version has been archived.']);

        } catch (Exception $e) {
            $mysqli->rollback();
            jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }
} else {
    jsonResponse(['error' => 'Unauthorized'], 403);
}
?>