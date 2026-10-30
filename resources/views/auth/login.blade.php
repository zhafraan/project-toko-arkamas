<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · Toko ARKAMAS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="auth">

    <aside class="auth-aside" aria-hidden="true">
        <div class="row" style="gap:12px;">
            <svg width="40" height="40" viewBox="0 0 72 72" fill="none">
                <defs><linearGradient id="agGoldL" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#F2B66B"/><stop offset="100%" stop-color="#D9822B"/></linearGradient></defs>
                <rect width="72" height="72" rx="18" fill="rgba(255,255,255,.16)"/>
                <text x="34" y="48" text-anchor="middle" font-family="'Plus Jakarta Sans', system-ui, sans-serif" font-weight="900" font-size="32" letter-spacing="-1"><tspan fill="#FFFFFB">A</tspan><tspan fill="url(#agGoldL)" dx="1">G</tspan></text>
                <circle cx="56" cy="23" r="4" fill="url(#agGoldL)"/>
            </svg>
            <strong style="font-size:17px;letter-spacing:-.01em;">Toko ARKAMAS</strong>
        </div>
        <div>
            <h2>Sistem pengelolaan Toko Arkamas. Penjualan Gas dan Telur.</h2>
            <p>Kelola penjualan, stok tabung, dan piutang dalam satu sistem.</p>
        </div>
        <div style="font-size:13px;color:rgba(255,255,255,.7);">© {{ date('Y') }} Toko ARKAMAS</div>

        <svg class="auth-deco" viewBox="0 0 120 200" fill="currentColor" aria-hidden="true">
            <rect x="30" y="50" width="60" height="140" rx="26"/>
            <rect x="48" y="26" width="24" height="30" rx="6"/>
            <rect x="40" y="12" width="40" height="14" rx="7"/>
        </svg>

    </aside>

    <main class="auth-main">
        <div class="auth-card">
            <h1>Masuk</h1>
            <p class="muted" style="margin:4px 0 24px;">Masuk menggunakan akun yang diberikan oleh pemilik toko.</p>

            @if(session('info'))
            <div class="alert alert-info" role="status"><x-icon name="info" /><div class="alert-body">{{ session('info') }}</div></div>
            @endif

            @if(isset($errors) && $errors->any())
            <div class="alert alert-error" role="alert"><x-icon name="x-circle" /><div class="alert-body">{{ $errors->first() }}</div></div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf
                <div class="field">
                    <label class="label" for="email">Email</label>
                    <input type="email" name="email" id="email" class="input {{ (isset($errors) && $errors->has('email')) ? 'is-invalid' : '' }}"
                           value="{{ old('email', $rememberedEmail ?? '') }}" placeholder="email@contoh.com" autocomplete="email" autofocus required>
                </div>
                <div class="field">
                    <label class="label" for="password">Kata sandi</label>
                    <div class="input-affix">
                        <input type="password" name="password" id="password" class="input" style="padding-left:12px;padding-right:44px;" autocomplete="current-password" required>
                        <button type="button" class="btn btn-ghost btn-icon btn-xs affix-btn" id="togglePw" aria-label="Tampilkan kata sandi" title="Tampilkan / sembunyikan kata sandi">
                            <x-icon name="eye" size="17" data-pw-show="" />
                            <x-icon name="eye-off" size="17" data-pw-hide="" hidden />
                        </button>
                    </div>
                </div>
                <label class="row" style="gap:8px;margin-bottom:20px;cursor:pointer;font-weight:500;">
                    <input type="checkbox" name="remember" style="width:16px;height:16px;accent-color:var(--primary);" {{ !empty($rememberedEmail) ? 'checked' : '' }}>
                    Ingat saya
                </label>
                <button type="submit" class="btn btn-primary btn-lg btn-block">Masuk</button>
            </form>
        </div>
    </main>
</div>

<script>
document.getElementById('togglePw').addEventListener('click', function () {
    var input = document.getElementById('password'), show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    this.querySelector('[data-pw-show]').toggleAttribute('hidden', show);
    this.querySelector('[data-pw-hide]').toggleAttribute('hidden', !show);
    this.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
});
</script>
</body>
</html>
