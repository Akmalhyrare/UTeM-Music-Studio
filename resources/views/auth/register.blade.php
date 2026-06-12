<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — UTeM Music Studio</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background-color: #1a1a2e;
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
            color: #1a1a2e;
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
            border-color: #1D9E75;
            box-shadow: 0 0 0 3px rgba(29,158,117,0.15);
        }
        .btn-register {
            background: #1D9E75;
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
        .btn-register:hover { background: #0F6E56; }
        .login-link {
            text-align: center;
            margin-top: 16px;
            font-size: 13px;
            color: #888;
        }
        .login-link a { color: #1D9E75; text-decoration: none; font-weight: 500; }
        .error-msg {
            background: #FAECE7;
            color: #712B13;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 16px;
        }
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
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Matric No.</label>
            <input type="text"
                   name="matric_no"
                   class="form-control"
                   placeholder="e.g. B032320017"
                   value="{{ old('matric_no') }}"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email"
                   name="email"
                   class="form-control"
                   placeholder="Enter your UTeM email"
                   value="{{ old('email') }}"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Phone No. <span class="text-muted">(optional)</span></label>
            <input type="text"
                   name="phone_no"
                   class="form-control"
                   placeholder="e.g. 0123456789"
                   value="{{ old('phone_no') }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password"
                   name="password"
                   class="form-control"
                   placeholder="Minimum 6 characters"
                   required>
        </div>

        <div class="mb-4">
            <label class="form-label">Confirm Password</label>
            <input type="password"
                   name="password_confirmation"
                   class="form-control"
                   placeholder="Repeat your password"
                   required>
        </div>

        <button type="submit" class="btn-register">
            Create Account →
        </button>
    </form>

    <div class="login-link">
        Already have an account? <a href="{{ route('login') }}">Login here</a>
    </div>
</div>

</body>
</html>