<?php
function hasAccess($role, $page) {
    $permissions = [
        'admin' => ['dashboard_admin', 'register', 'activity-logs'],
        'isms_manager' => ['dashboard_isms_manager'],
        'auditor' => ['dashboard_auditor', 'activity-logs'],
        'employee' => ['dashboard_employee'],
    ];

    // Public pages accessible to all logged-in users
    $public_pages = ['ai-chat'];

    if (in_array($page, $public_pages)) {
        return true;
    }

    if (isset($permissions[$role]) && in_array($page, $permissions[$role])) {
        return true;
    }

    return false;
}
?>