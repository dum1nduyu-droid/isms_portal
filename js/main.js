document.addEventListener('DOMContentLoaded', () => {
    const mainContent = document.getElementById('main-content');
    const loadingScreen = document.getElementById('loading-screen');
    const appContainer = document.getElementById('app-container');
    const navLinks = document.getElementById('nav-links');
    const sidebar = document.querySelector('.sidebar');
    const burgerMenu = document.getElementById('burger-menu');

    burgerMenu.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });

    const routes = {
        '#dashboard_admin': 'pages/dashboard_admin.html',
        '#dashboard_isms_manager': 'pages/dashboard_isms_manager.html',
        '#dashboard_auditor': 'pages/dashboard_auditor.html',
        '#dashboard_employee': 'pages/dashboard_employee.html',
        '#login': 'pages/login.html',
        '#register': 'pages/register.html',
        '#forgot-password': 'pages/forgot_password.html',
        '#reset-password': 'pages/reset_password.html',
        '#access-denied': 'pages/access_denied.html',
        '#activity-logs': 'pages/activity_logs.html',
        '#ai-chat': 'pages/ai_chat.html',
        '#policy-repository': 'pages/policy_repository.html',
        '#policy-details': 'pages/policy_details.html',
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
            const p = document.createElement('p');
            p.textContent = `Error loading page: ${error.message}`;
            mainContent.innerHTML = '';
            mainContent.appendChild(p);
        }
    }

    async function handleRouting() {
        try {
            const hash = window.location.hash || '#login';
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
                 const p = document.createElement('p');
                 p.textContent = 'Page not found';
                 mainContent.innerHTML = '';
                 mainContent.appendChild(p);
            }
        } catch (error) {
            showNotification('An error occurred. Please try again.', 'error');
        }
    }

    // Helper function to create a nav link
    function createNavLink(href, iconClass, text, id = null) {
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.href = href;
        if (id) a.id = id;

        const i = document.createElement('i');
        i.className = iconClass;

        a.appendChild(i);
        a.append(` ${text}`); // Appends text safely
        li.appendChild(a);
        return li;
    }

    async function updateNav() {
        try {
            const response = await fetch('php/api/session_status.php');
            const status = await response.json();

            navLinks.innerHTML = ''; // Clear previous links

            if (status.loggedIn) {
                navLinks.appendChild(createNavLink(`#dashboard_${status.role}`, 'fas fa-tachometer-alt', 'Dashboard'));
                navLinks.appendChild(createNavLink('#policy-repository', 'fas fa-file-alt', 'Policy Repository'));
                navLinks.appendChild(createNavLink('#ai-chat', 'fas fa-robot', 'AI Chat'));

                if (status.role === 'admin') {
                    navLinks.appendChild(createNavLink('#register', 'fas fa-user-plus', 'Register User'));
                    navLinks.appendChild(createNavLink('#activity-logs', 'fas fa-history', 'Activity Logs'));
                }
                if (status.role === 'auditor') {
                    navLinks.appendChild(createNavLink('#activity-logs', 'fas fa-history', 'Activity Logs'));
                }

                const logoutLi = createNavLink('#', 'fas fa-sign-out-alt', 'Logout', 'logout-link');
                navLinks.appendChild(logoutLi);

                logoutLi.querySelector('a').addEventListener('click', async (e) => {
                    e.preventDefault();
                    await fetch('php/auth/logout.php');
                    window.location.hash = '#login';
                    updateNav();
                });
            } else {
                navLinks.appendChild(createNavLink('#login', 'fas fa-sign-in-alt', 'Login'));
            }
        } catch (error) {
            showNotification('An error occurred while updating navigation.', 'error');
        }
    }

    async function initializeScripts(route) {
        if (route.startsWith('#dashboard')) {
            loadDashboardData();
        }
        if (route === '#activity-logs') {
            loadActivityLogs();
        }
        if (route === '#ai-chat') {
            handleAIChat();
        }
        if (route === '#policy-repository') {
            handlePolicyRepository();
            handleAIPolicyWriter();
        }
        if (route === '#policy-details') {
            handlePolicyDetailsPage();
        }
        if (route === '#access-denied') {
            const returnLink = document.getElementById('return-to-dashboard-link');
            if (returnLink) {
                fetch('php/api/session_status.php')
                    .then(res => res.json())
                    .then(status => {
                        returnLink.href = status.loggedIn ? `#dashboard_${status.role}` : '#login';
                    });
            }
        }
        // Form initializations
        setupForms(route);
    }

    function setupForms(route) {
        const formHandlers = {
            '#login': handleLoginForm,
            '#register': handleRegisterForm,
            '#forgot-password': handleForgotPasswordForm,
            '#reset-password': handleResetPasswordForm,
        };
        if (formHandlers[route]) {
            formHandlers[route]();
        }
    }

    function handleLoginForm() {
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                try {
                    const formData = new FormData(loginForm);
                    const response = await fetch('php/auth/login.php', { method: 'POST', body: formData });
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
                } catch (error) {
                    showNotification('An error occurred. Please try again.', 'error');
                }
            });
        }
    }

    function handleRegisterForm() {
        const registerForm = document.getElementById('register-form');
        if (registerForm) {
            registerForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                try {
                    const formData = new FormData(registerForm);
                    const response = await fetch('php/auth/register.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    if (response.ok) {
                        showNotification(result.message);
                        window.location.hash = '#login';
                    } else {
                        showNotification(result.error, 'error');
                    }
                } catch (error) {
                    showNotification('An error occurred. Please try again.', 'error');
                }
            });
        }
    }

    function handleForgotPasswordForm() {
        const forgotPasswordForm = document.getElementById('forgot-password-form');
        if (forgotPasswordForm) {
            forgotPasswordForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                try {
                    const formData = new FormData(forgotPasswordForm);
                    const response = await fetch('php/auth/forgot_password.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    showNotification(result.message);
                } catch (error) {
                    showNotification('An error occurred. Please try again.', 'error');
                }
            });
        }
    }

    function handleResetPasswordForm() {
        const resetPasswordForm = document.getElementById('reset-password-form');
        if (resetPasswordForm) {
            const hash = window.location.hash;
            const token = new URLSearchParams(hash.substring(hash.indexOf('?'))).get('token');
            if (token) {
                const tokenInput = document.getElementById('token');
                if (tokenInput) tokenInput.value = token;
            }

            resetPasswordForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                try {
                    const formData = new FormData(resetPasswordForm);
                    const response = await fetch('php/auth/reset_password.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    if (response.ok) {
                        showNotification(result.message);
                        window.location.hash = '#login';
                    } else {
                        showNotification(result.error, 'error');
                    }
                } catch (error) {
                    showNotification('An error occurred. Please try again.', 'error');
                }
            });
        }
    }

    async function loadDashboardData() {
        try {
            const response = await fetch('php/api/dashboard_data.php');
            const data = await response.json();

            if (data.user) {
                const usernameEl = document.getElementById('profile-username');
                const emailEl = document.getElementById('profile-email');
                const lastLoginEl = document.getElementById('profile-last-login');

                if (usernameEl) usernameEl.textContent = data.user.username;
                if (emailEl) emailEl.textContent = data.user.email;
                if (lastLoginEl) lastLoginEl.textContent = data.user.last_login;
            }
            if (data.stats) {
                const statsContainer = document.querySelector('.quick-stats .stats-container');
                if (statsContainer) {
                    statsContainer.querySelector('.fa-users + .stat-number').textContent = data.stats.users;
                    statsContainer.querySelector('.fa-exclamation-triangle + .stat-number').textContent = data.stats.risks;
                    statsContainer.querySelector('.fa-file-alt + .stat-number').textContent = data.stats.policies;
                    statsContainer.querySelector('.fa-search + .stat-number').textContent = data.stats.audits;
                }
            }
        } catch (error) {
            showNotification('Could not load dashboard data.', 'error');
        }
    }

    async function loadActivityLogs() {
        try {
            const response = await fetch('php/api/get_activity_logs.php');
            const logs = await response.json();
            const logsTableBody = document.getElementById('logs-table-body');

            if (logsTableBody) {
                logsTableBody.innerHTML = '';
                logs.forEach(log => {
                    const row = document.createElement('tr');

                    const userIdCell = document.createElement('td');
                    userIdCell.textContent = log.user_id;
                    row.appendChild(userIdCell);

                    const actionCell = document.createElement('td');
                    actionCell.textContent = log.action;
                    row.appendChild(actionCell);

                    const ipAddressCell = document.createElement('td');
                    ipAddressCell.textContent = log.ip_address;
                    row.appendChild(ipAddressCell);

                    const timestampCell = document.createElement('td');
                    timestampCell.textContent = log.timestamp;
                    row.appendChild(timestampCell);

                    logsTableBody.appendChild(row);
                });
            }
        } catch (error) {
            showNotification('Could not load activity logs.', 'error');
        }
    }

    function handleAIChat() {
        const aiChatForm = document.getElementById('ai-chat-form');
        const aiResponseArea = document.getElementById('ai-response-area');
        const aiPromptInput = document.getElementById('ai-prompt');

        if (aiChatForm) {
            aiChatForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const prompt = aiPromptInput.value;
                aiPromptInput.value = '';

                const userPromptEl = document.createElement('p');
                userPromptEl.textContent = `You: ${prompt}`;
                aiResponseArea.appendChild(userPromptEl);

                try {
                    const formData = new FormData();
                    formData.append('prompt', prompt);
                    await fetch('php/api/save_ai_prompt.php', { method: 'POST', body: formData });
                } catch (error) {
                    showNotification('Could not save your prompt.', 'error');
                }

                const aiResponseEl = document.createElement('p');
                aiResponseEl.textContent = 'AI: This is a placeholder response.';
                aiResponseArea.appendChild(aiResponseEl);
            });
        }
    }

    // Helper to create a table cell with text content
    function createTableCell(text) {
        const cell = document.createElement('td');
        cell.textContent = text;
        return cell;
    }

    function handlePolicyRepository() {
        const uploadForm = document.getElementById('upload-policy-form');
        const filterForm = document.getElementById('filter-policies-form');
        const policiesTableBody = document.getElementById('policies-table-body');
        let currentUserRole = '';
        fetch('php/api/session_status.php').then(res => res.json()).then(status => {
            if (status.loggedIn) currentUserRole = status.role;
        });

        async function handleWorkflowAction(action, versionId) {
            const urlMap = {
                'submit': 'php/api/submit_for_review.php',
                'approve': 'php/api/approve_policy.php',
                'publish': 'php/api/publish_policy.php',
            };
            if (!urlMap[action]) return;

            try {
                const formData = new FormData();
                formData.append('version_id', versionId);
                const response = await fetch(urlMap[action], { method: 'POST', body: formData });
                const result = await response.json();
                if (response.ok) {
                    showNotification(result.message);
                    fetchAndDisplayPolicies();
                } else {
                    showNotification(result.error, 'error');
                }
            } catch (error) {
                showNotification('An error occurred.', 'error');
            }
        }

        async function fetchAndDisplayPolicies(params = {}) {
            try {
                const query = new URLSearchParams(params).toString();
                const response = await fetch(`php/api/get_policies.php?${query}`);
                const policies = await response.json();

                policiesTableBody.innerHTML = '';
                policies.forEach(policy => {
                    const row = document.createElement('tr');
                    row.dataset.documentId = policy.document_id;

                    row.appendChild(createTableCell(policy.title));
                    row.appendChild(createTableCell(policy.version));

                    const statusCell = createTableCell(policy.status);
                    const statusSpan = document.createElement('span');
                    statusSpan.className = `status-${policy.status.toLowerCase()}`;
                    statusSpan.textContent = policy.status;
                    statusCell.innerHTML = ''; // Clear the text content
                    statusCell.appendChild(statusSpan);
                    row.appendChild(statusCell);

                    row.appendChild(createTableCell(policy.owner));
                    row.appendChild(createTableCell(policy.uploaded_by));
                    row.appendChild(createTableCell(policy.uploaded_at));

                    const actionsCell = document.createElement('td');

                    const historyLink = document.createElement('a');
                    historyLink.textContent = 'History';
                    historyLink.className = 'btn btn-secondary';
                    historyLink.href = `#policy-details?id=${policy.document_id}`;
                    actionsCell.appendChild(historyLink);

                    if ((currentUserRole === 'admin' || currentUserRole === 'isms_manager') && policy.status === 'Draft') {
                        const submitBtn = document.createElement('button');
                        submitBtn.textContent = 'Submit for Review';
                        submitBtn.className = 'btn';
                        submitBtn.dataset.action = 'submit';
                        submitBtn.dataset.versionId = policy.version_id;
                        actionsCell.appendChild(submitBtn);
                    }
                    if (currentUserRole === 'admin') {
                        if (policy.status === 'Review') {
                            const approveBtn = document.createElement('button');
                            approveBtn.textContent = 'Approve';
                            approveBtn.className = 'btn';
                            approveBtn.dataset.action = 'approve';
                            approveBtn.dataset.versionId = policy.version_id;
                            actionsCell.appendChild(approveBtn);
                        } else if (policy.status === 'Approved') {
                            const publishBtn = document.createElement('button');
                            publishBtn.textContent = 'Publish';
                            publishBtn.className = 'btn';
                            publishBtn.dataset.action = 'publish';
                            publishBtn.dataset.versionId = policy.version_id;
                            actionsCell.appendChild(publishBtn);
                        }
                    }
                    row.appendChild(actionsCell);
                    policiesTableBody.appendChild(row);
                });
            } catch (error) {
                showNotification('Could not load policies.', 'error');
            }
        }

        policiesTableBody.addEventListener('click', (e) => {
            if (e.target.tagName === 'BUTTON') {
                const action = e.target.dataset.action;
                const versionId = e.target.dataset.versionId;
                handleWorkflowAction(action, versionId);
            }
        });

        if (uploadForm) {
            uploadForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                try {
                    const formData = new FormData(uploadForm);
                    const response = await fetch('php/api/upload_policy.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    if (response.ok) {
                        showNotification(result.message);
                        fetchAndDisplayPolicies();
                        uploadForm.reset();
                    } else {
                        showNotification(result.error, 'error');
                    }
                } catch (error) {
                    showNotification('An error occurred during upload.', 'error');
                }
            });
        }
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                const formData = new FormData(filterForm);
                const params = Object.fromEntries(formData.entries());
                fetchAndDisplayPolicies(params);
            });
        }

        fetchAndDisplayPolicies();
    }

    function handleAIPolicyWriter() {
        const aiForm = document.getElementById('ai-policy-writer-form');
        const aiResultContainer = document.getElementById('ai-generated-policy');
        const aiPolicyText = document.getElementById('ai-policy-text');
        const useDraftBtn = document.getElementById('use-ai-draft-btn');

        if (aiForm) {
            aiForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                try {
                    const formData = new FormData(aiForm);
                    const response = await fetch('php/api/generate_ai_policy.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    if (response.ok) {
                        aiPolicyText.textContent = result.policy_text;
                        aiResultContainer.style.display = 'block';
                    } else {
                        showNotification(result.error, 'error');
                    }
                } catch (error) {
                    showNotification('An error occurred.', 'error');
                }
            });
        }
        if (useDraftBtn) {
            useDraftBtn.addEventListener('click', () => {
                const keywords = document.getElementById('ai-keywords').value;
                document.getElementById('title').value = keywords;
                document.getElementById('changelog').value = aiPolicyText.textContent;
                showNotification('Draft content has been populated into the upload form.');
            });
        }
    }

    async function handlePolicyDetailsPage() {
        const hash = window.location.hash;
        const documentId = new URLSearchParams(hash.substring(hash.indexOf('?'))).get('id');
        const historyBody = document.getElementById('version-history-body');
        const comparisonContainer = document.getElementById('comparison-container');

        if (!documentId) {
            window.location.hash = '#policy-repository';
            return;
        }

        try {
            const response = await fetch(`php/api/get_policy_history.php?document_id=${documentId}`);
            const history = await response.json();

            if (history && history.length > 0) {
                 document.getElementById('policy-title').textContent = `Policy Details: ${history[0].title || ''}`;
            }

            historyBody.innerHTML = '';
            history.forEach(v => {
                const row = document.createElement('tr');
                row.appendChild(createTableCell(v.version));
                row.appendChild(createTableCell(v.status));
                row.appendChild(createTableCell(v.changelog));
                row.appendChild(createTableCell(v.uploaded_by));
                row.appendChild(createTableCell(v.uploaded_at));
                row.appendChild(createTableCell(v.approved_by || 'N/A'));
                row.appendChild(createTableCell(v.approved_at || 'N/A'));

                const actionsCell = document.createElement('td');
                const downloadLink = document.createElement('a');
                downloadLink.href = `php/api/download_policy.php?version_id=${v.id}`;
                downloadLink.className = 'btn btn-secondary';
                downloadLink.textContent = 'Download';
                actionsCell.appendChild(downloadLink);

                const compareCheckbox = document.createElement('input');
                compareCheckbox.type = 'checkbox';
                compareCheckbox.className = 'compare-checkbox';
                compareCheckbox.dataset.versionId = v.id;
                compareCheckbox.title = 'Select to compare';
                actionsCell.appendChild(compareCheckbox);

                row.appendChild(actionsCell);
                historyBody.appendChild(row);
            });
        } catch (error) {
            showNotification('Could not load version history.', 'error');
        }

        historyBody.addEventListener('change', async (e) => {
            if (e.target.classList.contains('compare-checkbox')) {
                const checked = historyBody.querySelectorAll('.compare-checkbox:checked');
                if (checked.length === 2) {
                    const v1 = checked[0].dataset.versionId;
                    const v2 = checked[1].dataset.versionId;

                    const response = await fetch(`php/api/get_policy_version_comparison.php?v1=${v1}&v2=${v2}`);
                    const data = await response.json();

                    document.getElementById('compare-v1-title').textContent = `Version ${data.version1.version}`;
                    document.getElementById('compare-v1-details').textContent = JSON.stringify(data.version1, null, 2);

                    document.getElementById('compare-v2-title').textContent = `Version ${data.version2.version}`;
                    document.getElementById('compare-v2-details').textContent = JSON.stringify(data.version2, null, 2);

                    comparisonContainer.style.display = 'flex';
                } else {
                    comparisonContainer.style.display = 'none';
                }
            }
        });
    }

    function showNotification(message, type = 'success') {
        const container = document.getElementById('notification-container');
        if (!container) return;
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        container.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    window.addEventListener('hashchange', handleRouting);

    setTimeout(() => {
        loadingScreen.style.display = 'none';
        appContainer.style.display = 'flex';
        updateNav();
        initializeNotifications();
        handleRouting();
    }, 1500);

    async function initializeNotifications() {
        const bell = document.getElementById('notification-bell');
        const countBadge = document.getElementById('notification-count');
        const dropdown = document.getElementById('notification-dropdown');
        if (!bell) return;

        async function fetchNotifications() {
            try {
                const response = await fetch('php/api/get_notifications.php');
                if (!response.ok) return;
                const notifications = await response.json();

                dropdown.innerHTML = '';
                if (notifications.length > 0) {
                    countBadge.textContent = notifications.length;
                    countBadge.style.display = 'block';
                    notifications.forEach(n => {
                        const link = document.createElement('a');
                        link.href = n.link;
                        link.textContent = n.message;
                        link.dataset.id = n.id;
                        dropdown.appendChild(link);
                    });
                } else {
                    countBadge.style.display = 'none';
                    const noNotif = document.createElement('a');
                    noNotif.textContent = 'No new notifications';
                    dropdown.appendChild(noNotif);
                }
            } catch (error) { /* Silently fail for notifications */ }
        }

        bell.addEventListener('click', () => {
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        });

        dropdown.addEventListener('click', async (e) => {
            if (e.target.tagName === 'A' && e.target.dataset.id) {
                const id = e.target.dataset.id;
                const formData = new FormData();
                formData.append('notification_id', id);
                await fetch('php/api/mark_notification_read.php', { method: 'POST', body: formData });
                fetchNotifications(); // Refresh
            }
        });

        fetchNotifications();
        setInterval(fetchNotifications, 30000);
    }
});
