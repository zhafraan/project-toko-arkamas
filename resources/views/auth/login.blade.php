<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Arkamas Website</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(180deg, #D0E1F2 0%, #9FC0E3 22%, #6E9ECC 48%, #4D79A8 72%, #223A5E 100%);
            display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden;
        }
        /* Animated ambient Holst shapes */
        body::before, body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }
        body::before {
            width: 520px; height: 520px;
            background: radial-gradient(circle, rgba(208, 225, 242, 0.6) 0%, rgba(159, 192, 227, 0) 70%);
            top: -150px; left: -100px;
            animation: float 12s ease-in-out infinite;
        }
        body::after {
            width: 480px; height: 480px;
            background: radial-gradient(circle, rgba(34, 58, 94, 0.45) 0%, rgba(53, 89, 130, 0) 70%);
            bottom: -150px; right: -100px;
            animation: float 14s ease-in-out infinite reverse;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-30px) scale(1.05); }
        }

        .login-container {
            position: relative; z-index: 10;
            width: 100%; max-width: 440px;
            padding: 24px;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            border-radius: 22px;
            padding: 42px 38px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 24px 70px rgba(34, 58, 94, 0.35), 0 8px 24px rgba(34, 58, 94, 0.15);
        }
        .login-brand {
            text-align: center; margin-bottom: 30px;
        }
        .brand-icon {
            width: 72px; height: 72px;
            background: transparent;
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
            box-shadow: 0 10px 24px rgba(91, 95, 151, 0.35);
        }
        .brand-icon svg {
            width: 100%; height: 100%;
            display: block;
        }
        .login-brand h1 { font-size: 22px; font-weight: 800; color: #223A5E; letter-spacing: .3px; }
        .login-brand p { font-size: 13px; color: #4D79A8; margin-top: 5px; font-weight: 500; }

        .form-group { margin-bottom: 18px; }
        .form-label {
            display: block; font-size: 11.5px; font-weight: 700;
            color: #355982; margin-bottom: 6px;
            text-transform: uppercase; letter-spacing: .6px;
        }
        .input-wrapper { position: relative; }
        .input-icon {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%);
            color: #6E9ECC; font-size: 15px;
        }
        .form-control {
            width: 100%; padding: 12px 14px 12px 40px;
            border: 1.5px solid #D0E1F2;
            background: #F3F8FD;
            border-radius: 10px; font-size: 14px;
            color: #1a2a40;
            font-family: 'Inter', sans-serif;
            transition: all .2s;
        }
        .form-control:focus {
            outline: none;
            background: #ffffff;
            border-color: #4D79A8;
            box-shadow: 0 0 0 4px rgba(110, 158, 204, 0.25);
        }
        .form-control.is-invalid { border-color: #ef4444; }

        .remember-row {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 22px;
        }
        .remember-label {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: #355982; cursor: pointer; font-weight: 500;
        }
        .remember-label input { accent-color: #223A5E; width: 16px; height: 16px; }

        .btn-login {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #223A5E 0%, #355982 50%, #4D79A8 100%);
            color: #fff; border: none; border-radius: 10px;
            font-size: 15px; font-weight: 700;
            cursor: pointer; letter-spacing: .4px;
            box-shadow: 0 8px 24px rgba(34, 58, 94, 0.32);
            transition: transform .2s, box-shadow .2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(34, 58, 94, 0.42);
        }

        .login-footer {
            text-align: center; margin-top: 24px;
            font-size: 12px; color: #4D79A8;
            font-weight: 500;
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <div class="login-brand">
            <div class="brand-icon">
                <svg width="72" height="72" viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="agLoginGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#5b5f97"/>
                            <stop offset="100%" stop-color="#3c4071"/>
                        </linearGradient>
                        <linearGradient id="agGoldLoginGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#ffe394"/>
                            <stop offset="100%" stop-color="#ffc145"/>
                        </linearGradient>
                    </defs>
                    <rect width="72" height="72" rx="18" fill="url(#agLoginGrad)"/>
                    <rect x="1" y="1" width="70" height="70" rx="17" stroke="rgba(255,255,255,0.22)" stroke-width="2"/>
                    <text x="34" y="48" text-anchor="middle" font-family="'Plus Jakarta Sans', system-ui, -apple-system, sans-serif" font-weight="900" font-size="32" letter-spacing="-1px">
                        <tspan fill="#FFFFFB">A</tspan><tspan fill="url(#agGoldLoginGrad)" dx="1">G</tspan>
                    </text>
                    <circle cx="56" cy="23" r="4" fill="url(#agGoldLoginGrad)"/>
                </svg>
            </div>
            <h1>Arkamas Gas</h1>
            <p>Sistem Manajemen Toko Gas ARKAMAS</p>
        </div>

        @if(session('info'))
            <div style="background:#e0f2fe;color:#0369a1;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;border-left:3px solid #0284c7;">
                <i class="fa-solid fa-circle-info"></i> {{ session('info') }}
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div style="background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;border-left:3px solid #ef4444;">
                <i class="fa-solid fa-circle-xmark"></i> {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Email</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input type="email" name="email" id="emailInput" value="{{ old('email', $rememberedEmail ?? '') }}"
                           class="form-control {{ (isset($errors) && $errors->has('email')) ? 'is-invalid' : '' }}"
                           placeholder="email@contoh.com" autocomplete="email" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" name="password" id="passwordInput"
                           class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <div class="remember-row">
                <label class="remember-label">
                    <input type="checkbox" name="remember" {{ !empty($rememberedEmail) ? 'checked' : '' }}>
                    Ingat saya
                </label>
            </div>

            <button type="submit" class="btn-login">
                <i class="fa-solid fa-right-to-bracket"></i> Masuk ke Sistem
            </button>
        </form>

        <div class="login-footer">© {{ date('Y') }} Arkamas Gas </div>
    </div>
</div>
</body>
</html>
