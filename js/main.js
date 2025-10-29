document.addEventListener('DOMContentLoaded', () => {
    const mainContent = document.getElementById('main-content');
    const loadingScreen = document.getElementById('loading-screen');
    const appContainer = document.getElementById('app-container');
    const navLinks = document.getElementById('nav-links');

    const routes = {
        '#dashboard': 'pages/dashboard.html',
        '#dashboard_admin': 'pages/dashboard_admin.html',
        '#dashboard_isms_manager': 'pages/dashboard_isms_manager.html',
        '#dashboard_auditor': 'pages/dashboard_auditor.html',
        '#dashboard_employee': 'pages/dashboard_employee.html',
        '#login': 'pages/login.html',
        '#register': 'pages/register.html',
        '#forgot-password': 'pages/forgot_password.html',
        '#reset-password': 'pages/reset_password.html',
        '#access-denied': 'pages/access_denied.html',
    };

    async function loadContent(url, route) {
        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error('Page not found');
            }
            mainContent.innerHTML = await response.text();
            initializeScripts(route);
        } catch (error) {
            mainContent.innerHTML = `<p>Error loading page: ${error.message}</p>`;
        }
    }

    async function handleRouting() {
        const hash = window.location.hash || '#dashboard';
        const route = hash.split('?')[0];
        const page = route.substring(1); // remove #

        const accessResponse = await fetch(`php/api/check_access.php?page=${page}`);
        if (!accessResponse.ok) {
            window.location.hash = '#access-denied';
            return;
        }

        const url = routes[route];
        if (url) {
            loadContent(url, route);
        } else {
            mainContent.innerHTML = '<p>Page not found</p>';
        }
    }

    async function updateNav() {
        const response = await fetch('php/api/session_status.php');
        const status = await response.json();

        if (status.loggedIn) {
            let nav = `<li><a href="#dashboard_${status.role}"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>`;
            if (status.role === 'admin') {
                nav += '<li><a href="#register"><i class="fas fa-user-plus"></i> Register User</a></li>';
            }
            nav += `<li><a href="#" id="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>`;
            navLinks.innerHTML = nav;

            const logoutLink = document.getElementById('logout-link');
            if (logoutLink) {
                logoutLink.addEventListener('click', async (e) => {
                    e.preventDefault();
                    await fetch('php/auth/logout.php');
                    window.location.hash = '#login';
                    updateNav();
                });
            }
        } else {
            navLinks.innerHTML = `
                <li><a href="#dashboard"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="#login"><i class="fas fa-sign-in-alt"></i> Login</a></li>
            `;
        }
    }

    function initializeScripts(route) {
        if (route === '#login') {
            const loginForm = document.getElementById('login-form');
            if (loginForm) {
                loginForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(loginForm);
                    const response = await fetch('php/auth/login.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (response.ok) {
                        showNotification(result.message);
                        const sessionResponse = await fetch('php/api/session_status.php');
                        const status = await sessionResponse.json();
                        window.location.hash = `#dashboard_${status.role}`;
                        updateNav();
                    } else {
                        showNotification(result.error, 'error');
                    }
                });
            }
        } else if (route === '#register') {
            const registerForm = document.getElementById('register-form');
            if (registerForm) {
                registerForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(registerForm);
                    const response = await fetch('php/auth/register.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (response.ok) {
                        showNotification(result.message);
                        window.location.hash = '#login';
                    } else {
                        showNotification(result.error, 'error');
                    }
                });
            }
        } else if (route === '#forgot-password') {
            const forgotPasswordForm = document.getElementById('forgot-password-form');
            if (forgotPasswordForm) {
                forgotPasswordForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(forgotPasswordForm);
                    const response = await fetch('php/auth/forgot_password.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    showNotification(result.message);
                });
            }
        } else if (route === '#reset-password') {
            const resetPasswordForm = document.getElementById('reset-password-form');
            if (resetPasswordForm) {
                // Extract token from URL
                const hash = window.location.hash;
                const token = new URLSearchParams(hash.substring(hash.indexOf('?'))).get('token');

                if (token) {
                    const tokenInput = document.getElementById('token');
                    if (tokenInput) {
                        tokenInput.value = token;
                    }
                }

                resetPasswordForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(resetPasswordForm);
                    const response = await fetch('php/auth/reset_password.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (response.ok) {
                        showNotification(result.message);
                        window.location.hash = '#login';
                    } else {
                        showNotification(result.error, 'error');
                    }
                });
            }
        }
    }

    function showNotification(message, type = 'success') {
        const container = document.getElementById('notification-container');
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        container.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    window.addEventListener('hashchange', handleRouting);

    // Initial load
    setTimeout(() => {
        loadingScreen.style.display = 'none';
        appContainer.style.display = 'flex';
        updateNav();
        handleRouting();
    }, 1500);
});
