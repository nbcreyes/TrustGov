const API_BASE = window.location.origin + '/trustgov/api';

/** GET request */
async function apiGet(endpoint) {
    try {
        const res = await fetch(API_BASE + endpoint, {
            method: 'GET',
            credentials: 'include'
        });
        return await res.json();
    } catch (err) {
        console.error('GET error:', err);
        return { status: 'error', message: err.message, data: [] };
    }
}

/** POST request */
async function apiPost(endpoint, body) {
    try {
        const res = await fetch(API_BASE + endpoint, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        return await res.json();
    } catch (err) {
        console.error('POST error:', err);
        return { status: 'error', message: err.message };
    }
}

/** PUT request */
async function apiPut(endpoint, body) {
    try {
        const res = await fetch(API_BASE + endpoint, {
            method: 'PUT',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        return await res.json();
    } catch (err) {
        console.error('PUT error:', err);
        return { status: 'error', message: err.message };
    }
}

/** DELETE request */
async function apiDelete(endpoint, body) {
    try {
        const res = await fetch(API_BASE + endpoint, {
            method: 'DELETE',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        return await res.json();
    } catch (err) {
        console.error('DELETE error:', err);
        return { status: 'error', message: err.message };
    }
}

/* =============================================================
   TOAST NOTIFICATIONS
   ============================================================= */

/** Shows a green success toast */
function toastSuccess(message) {
    Toastify({
        text: message,
        duration: 3000,
        gravity: 'top',
        position: 'right',
        style: {
            background: 'linear-gradient(135deg, #16a34a, #15803d)',
            borderRadius: '0.5rem',
            fontFamily: 'Plus Jakarta Sans, sans-serif',
            fontSize: '0.875rem',
            padding: '0.75rem 1.25rem',
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)'
        }
    }).showToast();
}

/** Shows a red error toast */
function toastError(message) {
    Toastify({
        text: message,
        duration: 4000,
        gravity: 'top',
        position: 'right',
        style: {
            background: 'linear-gradient(135deg, #dc2626, #b91c1c)',
            borderRadius: '0.5rem',
            fontFamily: 'Plus Jakarta Sans, sans-serif',
            fontSize: '0.875rem',
            padding: '0.75rem 1.25rem',
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)'
        }
    }).showToast();
}

/* =============================================================
   AUTH HELPERS
   ============================================================= */

/** Returns the current user object from localStorage */
function getUser() {
    const raw = localStorage.getItem('trustgov_user');
    return raw ? JSON.parse(raw) : null;
}

/** Saves user object to localStorage */
function saveUser(user) {
    localStorage.setItem('trustgov_user', JSON.stringify(user));
}

/** Logs out the user and redirects to login */
function logout() {
    localStorage.removeItem('trustgov_user');
    apiGet('/users/logout.php').finally(() => {
        window.location.href = '/trustgov/login.html';
    });
}

/**
 * Requires the user to be logged in.
 * Redirects to login if not authenticated.
 * Returns the user object if authenticated.
 */
function requireAuth() {
    const user = getUser();
    if (!user) {
        window.location.href = '/trustgov/login.html';
        return null;
    }
    return user;
}

/**
 * Renders the sidebar user info, avatar, role badge,
 * and shows/hides admin-only nav links.
 */
function renderNavUser(user) {
    const nameEl = document.getElementById('userNameDisplay');
    const roleEl = document.getElementById('userRoleBadge');
    if (nameEl) nameEl.textContent = user.full_name;
    if (roleEl) {
        roleEl.innerHTML = `<span class="badge-${user.role}">${user.role.toUpperCase()}</span>`;
    }
}

/**
 * Renders the sidebar footer with user name, role, barangay,
 * and first-letter avatar. Also shows admin section links.
 */
function renderSidebar(user) {
    const nameEl   = document.getElementById('sidebarUserName');
    const roleEl   = document.getElementById('sidebarUserRole');
    const avatarEl = document.getElementById('sidebarAvatar');

    if (nameEl)   nameEl.textContent   = user.full_name;
    if (roleEl)   roleEl.textContent   = user.role.charAt(0).toUpperCase() + user.role.slice(1) + ' · ' + user.barangay;
    if (avatarEl) avatarEl.textContent = user.full_name.charAt(0).toUpperCase();

    if (user.role === 'admin') {
        const adminSection = document.getElementById('adminSection');
        const usersLink    = document.getElementById('usersNavLink');
        const auditLink    = document.getElementById('auditNavLink');
        if (adminSection) adminSection.style.display = 'block';
        if (usersLink)    usersLink.style.display    = 'flex';
        if (auditLink)    auditLink.style.display    = 'flex';
    }
}