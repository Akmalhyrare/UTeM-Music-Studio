<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — UTeM Music Studio</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background-color: #211C36;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .register-logo {
            text-align: center;
            margin-bottom: 24px;
        }
        .register-logo h4 {
            font-size: 20px;
            font-weight: 700;
            color: #211C36;
            margin: 10px 0 4px;
        }
        .register-logo p {
            font-size: 13px;
            color: #888;
            margin: 0;
        }
        .form-label {
            font-size: 13px;
            font-weight: 500;
            color: #444;
        }
        .form-control {
            font-size: 13px;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .form-control:focus {
            border-color: #7C3AED;
            box-shadow: 0 0 0 3px rgba(124,58,237,0.15);
        }
        .btn-register {
            background: #7C3AED;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 11px;
            font-size: 14px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-register:hover { background: #6D28D9; }
        .login-link {
            text-align: center;
            margin-top: 16px;
            font-size: 13px;
            color: #888;
        }
        .login-link a { color: #7C3AED; text-decoration: none; font-weight: 500; }
        .error-msg {
            background: #FEE2E2;
            color: #991B1B;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 16px;
        }
        .password-wrapper {
            position: relative;
        }
        .password-wrapper .form-control {
            padding-right: 42px;
        }
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            color: #888;
            padding: 0;
            line-height: 1;
        }
        .toggle-password:hover { color: #444; }
        .password-requirements {
            list-style: none;
            padding: 0;
            margin: 8px 0 0;
            font-size: 12px;
        }
        .password-requirements li {
            color: #9CA3AF;
            margin-bottom: 2px;
            transition: color 0.15s;
        }
        .password-requirements li::before {
            content: "○ ";
        }
        .password-requirements li.met {
            color: #16A34A;
        }
        .password-requirements li.met::before {
            content: "✓ ";
        }
        .strength-meter {
            height: 6px;
            border-radius: 4px;
            background: #E5E7EB;
            margin-top: 8px;
            overflow: hidden;
        }
        .strength-meter-bar {
            height: 100%;
            width: 0%;
            border-radius: 4px;
            background: #DC2626;
            transition: width 0.2s, background-color 0.2s;
        }
        .strength-label {
            font-size: 12px;
            font-weight: 600;
            margin-top: 4px;
        }
        .match-feedback {
            font-size: 12px;
            margin-top: 4px;
        }
        .match-feedback.match { color: #16A34A; }
        .match-feedback.no-match { color: #DC2626; }
    </style>
</head>
<body>

<div class="register-card">
    <div class="register-logo">
        <span style="font-size:40px;">🎵</span>
        <h4>UTeM Music Studio</h4>
        <p>Student Registration</p>
    </div>

    @if ($errors->any())
        <div class="error-msg">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.post') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text"
                   name="full_name"
                   class="form-control"
                   placeholder="Enter your full name"
                   value="{{ old('full_name') }}"
                   maxlength="100"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Matric No.</label>
            <input type="text"
                   name="matric_no"
                   class="form-control"
                   placeholder="e.g. B032320017"
                   value="{{ old('matric_no') }}"
                   maxlength="20"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email"
                   name="email"
                   class="form-control"
                   placeholder="Enter your UTeM email"
                   value="{{ old('email') }}"
                   maxlength="100"
                   pattern="[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}"
                   title="Please enter a valid email address with a domain extension (e.g. .com, .edu.my)."
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Phone No. <span class="text-muted">(optional)</span></label>
            <input type="text"
                   name="phone_no"
                   class="form-control"
                   placeholder="e.g. 0123456789"
                   value="{{ old('phone_no') }}"
                   maxlength="20">
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="password-wrapper">
                <input type="password"
                       id="password"
                       name="password"
                       class="form-control"
                       placeholder="Minimum 8 characters"
                       autocomplete="new-password"
                       required>
                <button type="button" class="toggle-password" data-target="password" aria-label="Show password">👁️</button>
            </div>

            <div class="strength-meter">
                <div id="strength-bar" class="strength-meter-bar"></div>
            </div>
            <div id="strength-label" class="strength-label"></div>

            <ul class="password-requirements">
                <li id="req-length">At least 8 characters long</li>
                <li id="req-upper">At least one uppercase letter</li>
                <li id="req-lower">At least one lowercase letter</li>
                <li id="req-number">At least one number</li>
                <li id="req-symbol">At least one special character</li>
            </ul>
        </div>

        <div class="mb-4">
            <label class="form-label">Confirm Password</label>
            <div class="password-wrapper">
                <input type="password"
                       id="password_confirmation"
                       name="password_confirmation"
                       class="form-control"
                       placeholder="Repeat your password"
                       autocomplete="new-password"
                       required>
                <button type="button" class="toggle-password" data-target="password_confirmation" aria-label="Show password">👁️</button>
            </div>
            <div id="match-feedback" class="match-feedback"></div>
        </div>

        <button type="submit" class="btn-register">
            Create Account →
        </button>
    </form>

    <div class="login-link">
        Already have an account? <a href="{{ route('login') }}">Login here</a>
    </div>
</div>

<script>
    // Show/hide password toggles
    document.querySelectorAll('.toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.dataset.target);
            var showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            btn.textContent = showing ? '👁️' : '🙈';
            btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
        });
    });

    // Live password requirements + strength meter
    var passwordInput = document.getElementById('password');
    var confirmInput = document.getElementById('password_confirmation');
    var strengthBar = document.getElementById('strength-bar');
    var strengthLabel = document.getElementById('strength-label');
    var matchFeedback = document.getElementById('match-feedback');

    var requirements = {
        'req-length': function (v) { return v.length >= 8; },
        'req-upper':  function (v) { return /[A-Z]/.test(v); },
        'req-lower':  function (v) { return /[a-z]/.test(v); },
        'req-number': function (v) { return /[0-9]/.test(v); },
        'req-symbol': function (v) { return /[^A-Za-z0-9]/.test(v); }
    };

    function updatePasswordUI() {
        var value = passwordInput.value;
        var metCount = 0;

        Object.keys(requirements).forEach(function (id) {
            var el = document.getElementById(id);
            var met = requirements[id](value);
            el.classList.toggle('met', met);
            if (met) metCount++;
        });

        var percent = (metCount / 5) * 100;
        strengthBar.style.width = percent + '%';

        if (value.length === 0) {
            strengthBar.style.width = '0%';
            strengthLabel.textContent = '';
        } else if (metCount <= 2) {
            strengthBar.style.backgroundColor = '#DC2626';
            strengthLabel.textContent = 'Weak';
            strengthLabel.style.color = '#DC2626';
        } else if (metCount <= 4) {
            strengthBar.style.backgroundColor = '#F59E0B';
            strengthLabel.textContent = 'Medium';
            strengthLabel.style.color = '#F59E0B';
        } else {
            strengthBar.style.backgroundColor = '#16A34A';
            strengthLabel.textContent = 'Strong';
            strengthLabel.style.color = '#16A34A';
        }

        updateMatchFeedback();
    }

    function updateMatchFeedback() {
        if (confirmInput.value.length === 0) {
            matchFeedback.textContent = '';
            matchFeedback.className = 'match-feedback';
            return;
        }

        if (confirmInput.value === passwordInput.value) {
            matchFeedback.textContent = '✓ Passwords match';
            matchFeedback.className = 'match-feedback match';
        } else {
            matchFeedback.textContent = '✗ Passwords do not match';
            matchFeedback.className = 'match-feedback no-match';
        }
    }

    passwordInput.addEventListener('input', updatePasswordUI);
    confirmInput.addEventListener('input', updateMatchFeedback);
</script>

</body>
</html>