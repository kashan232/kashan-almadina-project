<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AMT AL MADINA TRADERS</title>
    <link rel="shortcut icon" type="image/png" href="{{ asset('amt-logo.png') }}">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #1e293b;
        }

        .login-wrapper {
            width: 100%;
            max-width: 440px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 36px 32px 28px;
            box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1);
            position: relative;
        }

        .brand-section {
            text-align: center;
            margin-bottom: 24px;
        }

        .brand-logo {
            max-height: 60px;
            max-width: 130px;
            object-fit: contain;
            margin-bottom: 12px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.08));
        }

        .brand-title {
            font-size: 19px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
            letter-spacing: -0.2px;
        }

        .brand-sub {
            display: block;
            font-size: 15px;
            font-weight: 600;
            color: #2563eb;
            margin-top: 2px;
            letter-spacing: 0.5px;
        }

        .dealer-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #f1f5f9;
            color: #475569;
            font-size: 11.5px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            margin-top: 8px;
            border: 1px solid #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .dealer-badge i {
            color: #2563eb;
            font-size: 13px;
        }

        .alert-box {
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 12.5px;
            margin-bottom: 18px;
            line-height: 1.4;
        }
        .alert-warning {
            background: #fef3c7;
            border: 1px solid #fde68a;
            color: #92400e;
        }
        .alert-danger {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        .alert-success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .input-group-custom {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            color: #94a3b8;
            font-size: 16px;
            pointer-events: none;
            transition: color 0.2s;
        }

        .form-control-custom {
            width: 100%;
            padding: 11px 40px 11px 40px;
            font-size: 13.5px;
            font-family: inherit;
            color: #0f172a;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .form-control-custom:focus {
            outline: none;
            background: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .form-control-custom:focus + .input-icon,
        .input-group-custom:focus-within .input-icon {
            color: #2563eb;
        }

        .btn-toggle-pass {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 16px;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
        }

        .btn-toggle-pass:hover {
            color: #334155;
        }

        .form-row-extra {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 14px;
            margin-bottom: 20px;
            font-size: 12.5px;
        }

        .remember-wrap {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #64748b;
            cursor: pointer;
            user-select: none;
        }

        .remember-wrap input[type="checkbox"] {
            width: 15px;
            height: 15px;
            accent-color: #2563eb;
            cursor: pointer;
        }

        .forgot-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .forgot-link:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            color: #ffffff;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            transition: all 0.2s ease;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
            transform: translateY(-1px);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .login-footer {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #f1f5f9;
            text-align: center;
        }

        .powered-by {
            font-size: 11.5px;
            color: #64748b;
            margin: 0;
            letter-spacing: 0.2px;
        }

        .prowave-name {
            font-weight: 700;
            color: #2563eb;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">
            
            <!-- Brand & Heading Section -->
            <div class="brand-section">
                <img src="{{ asset('amt-logo.png') }}" alt="AL Madina Traders Logo" class="brand-logo" onerror="this.style.display='none'">
                <h1 class="brand-title">Welcome to AMT</h1>
                <span class="brand-sub">AL MADINA TRADERS</span>
                <div>
                    <span class="dealer-badge">
                        <i class="bi bi-shield-check"></i> Dealer Software
                    </span>
                </div>
            </div>

            <!-- Lockdown / Maintenance Alert -->
            @if(session('lockdown'))
                <div class="alert-box alert-warning">
                    <strong>Notice:</strong> {{ session('lockdown') }}
                </div>
            @elseif(!empty($loginLockdownActive))
                <div class="alert-box alert-warning">
                    <strong>Maintenance Mode:</strong> Login is temporarily disabled for maintenance.
                </div>
            @endif

            <!-- Session Status / Errors -->
            @if (session('status'))
                <div class="alert-box alert-success">
                    {{ session('status') }}
                </div>
            @endif

            @if (isset($errors) && $errors->any())
                <div class="alert-box alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email Field -->
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <div class="input-group-custom">
                        <i class="bi bi-envelope input-icon"></i>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control-custom" placeholder="admin@example.com" required autofocus autocomplete="username">
                    </div>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group-custom">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-control-custom" placeholder="Enter your password" required autocomplete="current-password">
                        <button type="button" class="btn-toggle-pass" onclick="togglePasswordVisibility()" aria-label="Toggle password visibility">
                            <i class="bi bi-eye" id="togglePassIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Extra Row: Remember Me & Forgot Password -->
                <div class="form-row-extra">
                    <label class="remember-wrap">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <span>Remember me</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
                    @endif
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-submit">
                    <span>Sign In</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <!-- Footer: Powered by ProWave Software Solutions -->
            <div class="login-footer">
                <p class="powered-by">
                    Developed by <span class="prowave-name">ProWave Software Solutions</span>
                </p>
            </div>

        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            var passInput = document.getElementById('password');
            var icon = document.getElementById('togglePassIcon');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                passInput.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }
    </script>
</body>
</html>
