/**
 * Switches between the Login and Register tabs on login.html.
 * @param {string} tab - 'login' or 'register'
 */
function switchTab(tab) {
    const loginTab    = document.getElementById('loginTab');
    const registerTab = document.getElementById('registerTab');
    const loginForm   = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    if (tab === 'login') {
        loginTab.classList.add('border-b-2', 'border-blue-500', 'text-blue-600', 'font-semibold');
        registerTab.classList.remove('border-b-2', 'border-blue-500', 'text-blue-600', 'font-semibold');
        loginForm.classList.remove('hidden');
        registerForm.classList.add('hidden');
    } else {
        registerTab.classList.add('border-b-2', 'border-blue-500', 'text-blue-600', 'font-semibold');
        loginTab.classList.remove('border-b-2', 'border-blue-500', 'text-blue-600', 'font-semibold');
        registerForm.classList.remove('hidden');
        loginForm.classList.add('hidden');
    }
}

/**
 * Handles the login form submission.
 * Sends credentials to the login API and saves user to localStorage.
 */
async function handleLogin() {
    const email    = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;

    // Validate inputs before sending
    if (!email || !password) {
        toastError('Please enter your email and password.');
        return;
    }

    // Show loading state on button
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.textContent = 'Logging in...';

    // Send POST request to login endpoint
    const result = await apiPost('/users/login.php', { email, password });

    btn.disabled = false;
    btn.textContent = 'Login';

    if (result.status === 'success') {
        // Save user data to localStorage
        saveUser(result.data);
        toastSuccess('Login successful! Redirecting...');
        setTimeout(() => {
            window.location.href = '/trustgov/pages/dashboard.html';
        }, 1000);
    } else {
        toastError(result.message || 'Login failed.');
    }
}

/**
 * Handles the registration form submission.
 * Sends new user data to the register API.
 */
async function handleRegister() {
    const full_name = document.getElementById('regName').value.trim();
    const email     = document.getElementById('regEmail').value.trim();
    const password  = document.getElementById('regPassword').value;
    const role      = document.getElementById('regRole').value;
    const barangay  = document.getElementById('regBarangay').value.trim();

    // Validate all fields are filled
    if (!full_name || !email || !password || !role || !barangay) {
        toastError('Please fill in all fields.');
        return;
    }

    // Validate password length
    if (password.length < 6) {
        toastError('Password must be at least 6 characters.');
        return;
    }

    // Show loading state on button
    const btn = document.getElementById('registerBtn');
    btn.disabled = true;
    btn.textContent = 'Registering...';

    // Send POST request to register endpoint
    const result = await apiPost('/users/register.php', {
        full_name, email, password, role, barangay
    });

    btn.disabled = false;
    btn.textContent = 'Register';

    if (result.status === 'success') {
        toastSuccess('Registered successfully! Please log in.');
        setTimeout(() => switchTab('login'), 1500);
    } else {
        toastError(result.message || 'Registration failed.');
    }
}