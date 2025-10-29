# ISMS Portal

## Setup

1.  **Database:** Import the `database.sql` file into your MySQL database.
2.  **Configuration:** Create a `php/includes/config.php` file by copying the `php/includes/config.template.php` file and updating the database credentials.
3.  **Create Admin User:** Run the `create_admin.php` script from the command line to create the first admin user: `php create_admin.php`

## Security Considerations

### CSRF (Cross-Site Request Forgery)

This is a prototype and does not currently implement CSRF protection. In a production environment, this would be addressed by:

1.  **Generating a unique, per-session token** and embedding it in a hidden input field in all state-changing forms (e.g., login, registration, password reset).
2.  **Verifying this token on the server-side** for all POST requests. If the token is missing or invalid, the request is rejected.
