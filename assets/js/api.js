// Base URL for all API calls — adjust if hosted elsewhere
const API_BASE = window.location.origin + '/trustgov/api';

/**
 * Sends a GET request to the given endpoint.
 * @param {string} endpoint - API path e.g. '/budgets/read.php'
 * @returns {Promise<object>} Parsed JSON response
 */
async function apiGet(endpoint) {
    try {
        const response = await fetch(API_BASE + endpoint, {
            method: 'GET',
            credentials: 'include'
        });
        return await response.json();
    } catch (error) {
        console.error('GET error:', error);
        return { status: 'error', message: 'Network error.', data: null };
    }
}

/**
 * Sends a POST request with a JSON body to the given endpoint.
 * @param {string} endpoint - API path e.g. '/users/login.php'
 * @param {object} body - Data to send as JSON
 * @returns {Promise<object>} Parsed JSON response
 */
async function apiPost(endpoint, body) {
    try {
        const response = await fetch(API_BASE + endpoint, {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        return await response.json();
    } catch (error) {
        console.error('POST error:', error);
        return { status: 'error', message: 'Network error.', data: null };
    }
}

/**
 * Sends a PUT request with a JSON body to the given endpoint.
 * @param {string} endpoint - API path e.g. '/budgets/update.php'
 * @param {object} body - Data to send as JSON
 * @returns {Promise<object>} Parsed JSON response
 */
async function apiPut(endpoint, body) {
    try {
        const response = await fetch(API_BASE + endpoint, {
            method: 'PUT',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        return await response.json();
    } catch (error) {
        console.error('PUT error:', error);
        return { status: 'error', message: 'Network error.', data: null };
    }
}

/**
 * Sends a DELETE request with a JSON body to the given endpoint.
 * @param {string} endpoint - API path e.g. '/budgets/delete.php'
 * @param {object} body - Data to send as JSON
 * @returns {Promise<object>} Parsed JSON response
 */
async function apiDelete(endpoint, body) {
    try {
        const response = await fetch(API_BASE + endpoint, {
            method: 'DELETE',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        return await response.json();
    } catch (error) {
        console.error('DELETE error:', error);
        return { status: 'error', message: 'Network error.', data: null };
    }
}

/**
 * Shows a Toastify success notification.
 * @param {string} message - Message to display
 */
function toastSuccess(message) {
    Toastify({
        text: message,
        duration: 3000,
        gravity: 'top',
        position: 'right',
        style: { background: '#22c55e', borderRadius: '8px', fontWeight: '600' }
    }).showToast();
}

/**
 * Shows a Toastify error notification.
 * @param {string} message - Message to display
 */
function toastError(message) {
    Toastify({
        text: message,
        duration: 3000,
        gravity: 'top',
        position: 'right',
        style: { background: '#ef4444', borderRadius: '8px', fontWeight: '600' }
    }).showToast();
}

/**
 * Returns the currently logged-in user from localStorage.
 * @returns {object|null} User object or null if not logged in
 */
function getUser() {
    const user = localStorage.getItem('trustgov_user');
    return user ? JSON.parse(user) : null;
}

/**
 * Saves the logged-in user object to localStorage.
 * @param {object} user - User data returned from login API
 */
function saveUser(user) {
    localStorage.setItem('trustgov_user', JSON.stringify(user));
}

/**
 * Clears the user session from localStorage and redirects to login.
 */
function logout() {
    localStorage.removeItem('trustgov_user');
    window.location.href = '/trustgov/login.html';
}

/**
 * Redirects to login if no user session is found.
 * Call this at the top of every protected page.
 */
function requireAuth() {
    const user = getUser();
    if (!user) {
        window.location.href = '/trustgov/login.html';
    }
    return user;
}

/**
 * Renders the navbar user role badge and logout button.
 * Call this on every protected page after requireAuth().
 * @param {object} user - The logged-in user object
 */
function renderNavUser(user) {
    const roleEl  = document.getElementById('userRoleBadge');
    const nameEl  = document.getElementById('userNameDisplay');

    if (roleEl) {
        roleEl.className = `badge-${user.role}`;
        roleEl.textContent = user.role.toUpperCase();
    }

    if (nameEl) {
        nameEl.textContent = user.full_name;
    }
}