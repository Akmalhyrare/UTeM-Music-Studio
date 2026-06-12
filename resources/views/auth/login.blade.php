<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — UTeM Music Studio</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background-color: #1a1a2e;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo h4 {
            font-size: 20px;
            font-weight: 700;
            color: #1a1a2e;
            margin: 10px 0 4px;
        }
        .login-logo p {
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
        .btn-login {
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
        .btn-login:hover {
            background: #0F6E56;
        }
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

<div class="login-card">
    <div class="login-logo">
        <span style="font-size:40px;">🎵</span>
        <h4>UTeM Music Studio</h4>
        <p>Inventory & Management System</p>
    </div>

    {{-- Error message --}}
    @if ($errors->any())
        <div class="error-msg">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Email address</label>
            <input type="email"
                   name="email"
                   class="form-control"
                   placeholder="Enter your email"
                   value="{{ old('email') }}"
                   required autofocus>
        </div>

        <div class="mb-4">
            <label class="form-label">Password</label>
            <input type="password"
                   name="password"
                   class="form-control"
                   placeholder="Enter your password"
                   required>
        </div>

        <button type="submit" class="btn-login">
            Login →
        </button>
    </form>
    <div style="text-align:center; margin-top:16px; font-size:13px; color:#888;">
    New Student? <a href="{{ route('register') }}" style="color:#1D9E75;font-weight:500;text-decoration:none;">
        Register here
    </a>
</div>
</div>

</body>
</html>