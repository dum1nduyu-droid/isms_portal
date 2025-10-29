<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';

if (isLoggedIn() && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'isms_manager')) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $keywords = $_POST['keywords'] ?? 'Untitled Policy';
        $iso_clause = $_POST['iso_clause'] ?? 'A.1.1';

        // --- AI Placeholder Logic ---
        // In a real application, this would be a call to an AI API (e.g., OpenAI's GPT).
        // For this prototype, we'll return a pre-defined template.

        $policy_text = "
## DRAFT: {$keywords} ##

### 1. Purpose ###
The purpose of this policy is to establish the framework for managing access control in alignment with ISO 27001 clause {$iso_clause}.

### 2. Scope ###
This policy applies to all employees, contractors, and third-party users who access the organization's information systems.

### 3. Policy ###
- **User Access Management:** All user access shall be approved by management.
- **Password Requirements:** Passwords must meet a minimum complexity requirement.
- **Regular Reviews:** User access rights shall be reviewed on a quarterly basis.

### 4. Responsibilities ###
The IT department is responsible for implementing and maintaining access control mechanisms.
        ";

        jsonResponse(['policy_text' => trim($policy_text)]);
    }
} else {
    jsonResponse(['error' => 'Unauthorized'], 403);
}
?>