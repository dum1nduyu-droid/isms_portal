<?php
function hasAccess($role, $page) {
    $permissions = [
        'admin' => ['dashboard_admin', 'register'],
        'isms_manager' => ['dashboard_isms_manager'],
        'auditor' => ['dashboard_auditor'],
        'employee' => ['dashboard_employee'],
    ];

    // Public pages accessible to all logged-in users
    $public_pages = ['dashboard'];

    if (in_array($page, $public_pages)) {
        return true;
    }

    if (isset($permissions[$role]) && in_array($page, $permissions[$role])) {
        return true;
    }

    return false;
}
?>