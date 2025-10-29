<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

if (isLoggedIn() && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'isms_manager')) {
    $version_id1 = $_GET['v1'] ?? 0;
    $version_id2 = $_GET['v2'] ?? 0;

    if ($version_id1 > 0 && $version_id2 > 0) {
        $query = "
            SELECT
                pd.title,
                pd.owner,
                pd.iso_clause,
                pv.id,
                pv.version,
                pv.status,
                pv.changelog,
                u_upload.username as uploaded_by,
                pv.uploaded_at
            FROM policy_versions pv
            JOIN policy_documents pd ON pv.document_id = pd.id
            JOIN users u_upload ON pv.uploaded_by = u_upload.id
            WHERE pv.id IN (?, ?)
        ";

        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('ii', $version_id1, $version_id2);
        $stmt->execute();
        $result = $stmt->get_result();
        $versions = $result->fetch_all(MYSQLI_ASSOC);

        $comparison_data = [
            'version1' => null,
            'version2' => null
        ];

        foreach($versions as $v) {
            if ($v['id'] == $version_id1) {
                $comparison_data['version1'] = $v;
            } else if ($v['id'] == $version_id2) {
                $comparison_data['version2'] = $v;
            }
        }

        jsonResponse($comparison_data);
    } else {
        jsonResponse(['error' => 'Invalid version IDs provided.'], 400);
    }
} else {
    jsonResponse(['error' => 'Unauthorized'], 403);
}
?>