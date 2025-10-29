<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

if (isLoggedIn() && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'isms_manager')) {
    $document_id = $_GET['document_id'] ?? 0;

    if ($document_id > 0) {
        $query = "
            SELECT
                pv.id,
                pv.version,
                pv.status,
                pv.changelog,
                u_upload.username as uploaded_by,
                pv.uploaded_at,
                u_approve.username as approved_by,
                pv.approved_at
            FROM policy_versions pv
            JOIN users u_upload ON pv.uploaded_by = u_upload.id
            LEFT JOIN users u_approve ON pv.approved_by = u_approve.id
            WHERE pv.document_id = ?
            ORDER BY pv.uploaded_at DESC
        ";

        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('i', $document_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $history = $result->fetch_all(MYSQLI_ASSOC);

        jsonResponse($history);
    } else {
        jsonResponse(['error' => 'Invalid document ID.'], 400);
    }
} else {
    jsonResponse(['error' => 'Unauthorized'], 403);
}
?>