/**
 * Switches between Login and Register tabs.
 * @param {string} tab - 'login' or 'register'
 */
function switchTab(tab) {
    const loginForm    = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const loginTab     = document.getElementById('loginTab');
    const registerTab  = document.getElementById('registerTab');

    if (tab === 'login') {
        loginForm.classList.remove('hidden');
        registerForm.classList.add('hidden');
        loginTab.style.borderBottomColor  = 'var(--accent)';
        loginTab.style.color              = 'var(--accent)';
        loginTab.style.fontWeight         = '700';
        registerTab.style.borderBottomColor = 'transparent';
        registerTab.style.color           = 'var(--text-secondary)';
        registerTab.style.fontWeight      = '500';
    } else {
        registerForm.classList.remove('hidden');
        loginForm.classList.add('hidden');
        registerTab.style.borderBottomColor = 'var(--accent)';
        registerTab.style.color             = 'var(--accent)';
        registerTab.style.fontWeight        = '700';
        loginTab.style.borderBottomColor  = 'transparent';
        loginTab.style.color              = 'var(--text-secondary)';
        loginTab.style.fontWeight         = '500';
    }
}

/**
 * Handles login form submission.
 * Validates inputs, calls the login API, saves user to
 * localStorage, and redirects to the dashboard on success.
 */
async function handleLogin() {
    const email    = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;
    const btn      = document.getElementById('loginBtn');

    if (!email || !password) {
        toastError('Please enter your email and password.');
        return;
    }

    // Show loading state
    btn.disabled     = true;
    btn.innerHTML    = '<i class="fas fa-spinner fa-spin mr-2"></i>Logging in...';

    const res = await apiPost('/users/login.php', { email, password });

    btn.disabled  = false;
    btn.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>Login';

    if (res.status === 'success') {
        saveUser(res.data);
        toastSuccess('Welcome back, ' + res.data.full_name + '!');
        setTimeout(() => {
            window.location.href = '/trustgov/pages/dashboard.html';
        }, 800);
    } else {
        toastError(res.message || 'Invalid email or password.');
    }
}

/**
 * Handles register form submission.
 * Validates inputs, calls the register API, and switches
 * to the login tab on success.
 */
async function handleRegister() {
    const full_name = document.getElementById('regName').value.trim();
    const email     = document.getElementById('regEmail').value.trim();
    const password  = document.getElementById('regPassword').value;
    const role      = document.getElementById('regRole').value;
    const barangay  = document.getElementById('regBarangay').value;
    const btn       = document.getElementById('registerBtn');

    if (!full_name || !email || !password || !role || !barangay) {
        toastError('Please fill in all fields.');
        return;
    }

    if (password.length < 6) {
        toastError('Password must be at least 6 characters.');
        return;
    }

    // Show loading state
    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Registering...';

    const res = await apiPost('/users/register.php', { full_name, email, password, role, barangay });

    btn.disabled  = false;
    btn.innerHTML = '<i class="fas fa-user-plus mr-2"></i>Register';

    if (res.status === 'success') {
        toastSuccess('Account created! Please log in.');
        setTimeout(() => switchTab('login'), 1000);
    } else {
        toastError(res.message || 'Registration failed. Please try again.');
    }
}

// Allow Enter key to trigger login
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('loginPassword')?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') handleLogin();
    });
    document.getElementById('loginEmail')?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') handleLogin();
    });
});