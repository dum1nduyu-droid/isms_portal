<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

if (isLoggedIn()) {
    $search = $_GET['search'] ?? '';
    $owner = $_GET['owner'] ?? '';

    $query = "
        SELECT
            pd.id as document_id,
            pd.title,
            pd.owner,
            pv.id as version_id,
            pv.version,
            pv.status,
            u.username as uploaded_by,
            pv.uploaded_at
        FROM policy_versions pv
        JOIN policy_documents pd ON pv.document_id = pd.id
        JOIN users u ON pv.uploaded_by = u.id
        WHERE pv.id IN (
            -- This subquery finds the most recent version ID for each document
            SELECT MAX(id)
            FROM policy_versions
            GROUP BY document_id
        )
    ";

    $params = [];
    $types = '';

    if (!empty($search)) {
        $query .= " AND pd.title LIKE ?";
        $params[] = "%$search%";
        $types .= 's';
    }
    if (!empty($owner)) {
        $query .= " AND pd.owner LIKE ?";
        $params[] = "%$owner%";
        $types .= 's';
    }
    if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'isms_manager') {
        // Non-admins only see Published policies
        $query .= " AND pv.status = 'Published'";
    } else {
        // Admins can see all latest versions regardless of status
        $query .= " AND pv.status IN ('Draft', 'Review', 'Approved', 'Published')";
    }


    $query .= " ORDER BY pd.title ASC";

    $stmt = $mysqli->prepare($query);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $policies = $result->fetch_all(MYSQLI_ASSOC);

    jsonResponse($policies);

} else {
    jsonResponse(['error' => 'Unauthorized'], 403);
}
?>