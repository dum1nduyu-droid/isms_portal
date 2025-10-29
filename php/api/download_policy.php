<?php
require_once '../includes/db.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

if (isLoggedIn()) {
    if (isset($_GET['version_id'])) {
        $version_id = $_GET['version_id'];

        $stmt = $mysqli->prepare('SELECT file_path, file_type FROM policy_versions WHERE id = ?');
        $stmt->bind_param('i', $version_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($file_path, $file_type);
            $stmt->fetch();

            $full_path = realpath(__DIR__ . '/../../' . $file_path);

            if ($full_path && file_exists($full_path)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($full_path));
                flush(); // Flush the system output buffer
                readfile($full_path);
                exit;
            } else {
                http_response_code(404);
                echo 'File not found on the server.';
            }
        } else {
            http_response_code(404);
            echo 'Policy version not found.';
        }
    } else {
        http_response_code(400);
        echo 'Invalid request. Version ID is required.';
    }
} else {
    http_response_code(401);
    echo 'Unauthorized.';
}
?>