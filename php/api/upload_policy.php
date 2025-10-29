<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/logging.php';

if (isLoggedIn() && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'isms_manager')) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = $_POST['title'];
        $owner = $_POST['owner'];
        $iso_clause = $_POST['iso_clause'];
        $review_frequency = $_POST['review_frequency'];
        $version = $_POST['version'];
        $changelog = $_POST['changelog'];
        $uploaded_by = $_SESSION['user_id'];

        // --- File Upload Handling ---
        $target_dir = "../../uploads/policies/";
        $original_filename = basename($_FILES["policyFile"]["name"]);
        $file_type = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));

        $allowed_types = ['pdf', 'docx', 'xlsx'];
        if (!in_array($file_type, $allowed_types)) {
            jsonResponse(['error' => 'Invalid file type. Only PDF, DOCX, and XLSX are allowed.'], 400);
            exit();
        }

        $new_filename = uniqid('', true) . '.' . $file_type;
        $target_file_path = $target_dir . $new_filename;
        $db_file_path = "uploads/policies/" . $new_filename; // Store a root-relative path

        if (!move_uploaded_file($_FILES["policyFile"]["tmp_name"], $target_file_path)) {
            jsonResponse(['error' => 'File upload failed.'], 500);
            exit();
        }

        // --- Database Logic ---
        $mysqli->begin_transaction();

        try {
            // 1. Find or create the parent policy document
            $stmt = $mysqli->prepare('SELECT id FROM policy_documents WHERE title = ?');
            $stmt->bind_param('s', $title);
            $stmt->execute();
            $result = $stmt->get_result();
            $document = $result->fetch_assoc();

            $document_id = null;
            if ($document) {
                $document_id = $document['id'];
                // Optional: Update metadata if it has changed
                $update_stmt = $mysqli->prepare('UPDATE policy_documents SET owner = ?, iso_clause = ?, review_frequency = ? WHERE id = ?');
                $update_stmt->bind_param('sssi', $owner, $iso_clause, $review_frequency, $document_id);
                $update_stmt->execute();
            } else {
                $insert_stmt = $mysqli->prepare('INSERT INTO policy_documents (title, owner, iso_clause, review_frequency, created_by) VALUES (?, ?, ?, ?, ?)');
                $insert_stmt->bind_param('ssssi', $title, $owner, $iso_clause, $review_frequency, $uploaded_by);
                $insert_stmt->execute();
                $document_id = $insert_stmt->insert_id;
            }

            // 2. Insert the new version
            $version_stmt = $mysqli->prepare('INSERT INTO policy_versions (document_id, version, changelog, file_path, file_type, uploaded_by, status) VALUES (?, ?, ?, ?, ?, ?, "Draft")');
            $version_stmt->bind_param('issssi', $document_id, $version, $changelog, $db_file_path, $file_type, $uploaded_by);

            if ($version_stmt->execute()) {
                $mysqli->commit();
                log_activity($uploaded_by, "Uploaded new policy version: " . $title . " (v" . $version . ")");
                jsonResponse(['message' => 'New policy version uploaded successfully as Draft.']);
            } else {
                throw new Exception('Could not save policy version.');
            }
        } catch (Exception $e) {
            $mysqli->rollback();
            // Also delete the uploaded file to prevent orphans
            if (file_exists($target_file_path)) {
                unlink($target_file_path);
            }
            jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }
} else {
    jsonResponse(['error' => 'Unauthorized'], 403);
}
?>