@extends('app')

@section('content')
    <div style="max-width:400px; margin:60px auto; font-family:sans-serif;">
        <div id="messageBox" style="margin-bottom:15px;"></div>

        <div id="logoutSection" style="display:none; margin-bottom:20px;">
            <p>You are currently logged in.</p>
            <button id="logoutBtn">Logout</button>
        </div>

        <div id="registerForm">
            <h2>Register</h2>
            <label>Email:</label><br>
            <input type="email" id="regEmail" style="width:100%; margin-bottom:8px;"><br>
            <label>Username (optional):</label><br>
            <input type="text" id="regUsername" style="width:100%; margin-bottom:8px;"><br>
            <label>Password (min 8 characters):</label><br>
            <input type="password" id="regPassword" style="width:100%; margin-bottom:8px;"><br>
            <button id="registerBtn">Register</button>
        </div>

        <hr style="margin:30px 0;">

        <div id="loginForm">
            <h2>Login</h2>
            <label>Email or Username:</label><br>
            <input type="text" id="loginIdentifier" style="width:100%; margin-bottom:8px;"><br>
            <label>Password:</label><br>
            <input type="password" id="loginPassword" style="width:100%; margin-bottom:8px;"><br>
            <button id="loginBtn">Login</button>
        </div>
    </div>

    <script>
        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        var messageBox = document.getElementById('messageBox');

        function showMessage(text, isError) {
            messageBox.textContent = text;
            messageBox.style.color = isError ? 'red' : 'green';
        }

        document.getElementById('logoutBtn').addEventListener('click', function () {
            fetch('/api/auth/logout', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                showMessage('Logged out. You can now register or login again.', false);
                window.location.reload();
            })
            .catch(error => showMessage('Logout failed: ' + error, true));
        });

        document.getElementById('registerBtn').addEventListener('click', function () {
            var email = document.getElementById('regEmail').value;
            var username = document.getElementById('regUsername').value;
            var password = document.getElementById('regPassword').value;

            fetch('/api/auth/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ email: email, username: username, password: password })
            })
            .then(response => response.json())
            .then(data => {
                if (data.token) {
                    localStorage.setItem('authToken', data.token);
                    showMessage('Registered successfully! Redirecting to map...', false);
                    setTimeout(function () { window.location.href = '/map'; }, 1000);
                } else {
                    showMessage(JSON.stringify(data), true);
                }
            })
            .catch(error => showMessage('Registration failed: ' + error, true));
        });

        document.getElementById('loginBtn').addEventListener('click', function () {
            var identifier = document.getElementById('loginIdentifier').value;
            var password = document.getElementById('loginPassword').value;

            fetch('/api/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ identifier: identifier, password: password })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Logged in successfully! Redirecting to map...', false);
                    setTimeout(function () { window.location.href = '/map'; }, 1000);
                } else {
                    showMessage(JSON.stringify(data), true);
                }
            })
            .catch(error => showMessage('Login failed: ' + error, true));
        });

        // Check login status by hitting a protected-ish endpoint
        fetch('/api/markers')
            .then(() => {}); // markers is public, so this doesn't tell us login status directly

        // Simple check: try validate-token (requires auth:sanctum, but that's token-based not session-based)
        // Instead, we just always show the logout option available manually below the forms
        document.getElementById('logoutSection').style.display = 'block';
    </script>
@endsection