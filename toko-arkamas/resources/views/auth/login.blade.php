<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · Arkamas Gas</title>
    <script>
        (function () {
            try {
                var t = localStorage.getItem('arkamas_theme');
                if (!t) { t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
                document.documentElement.dataset.theme = t;
            } catch (e) {}
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<div class="auth">

    <aside class="auth-aside" aria-hidden="true">
        <div class="row" style="gap:12px;">
            <svg width="40" height="40" viewBox="0 0 72 72" fill="none">
                <defs><linearGradient id="agGoldL" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#ffe394"/><stop offset="100%" stop-color="#ffc145"/></linearGradient></defs>
                <rect width="72" height="72" rx="18" fill="rgba(255,255,255,.16)"/>
                <text x="34" y="48" text-anchor="middle" font-family="'Plus Jakarta Sans', system-ui, sans-serif" font-weight="900" font-size="32" letter-spacing="-1"><tspan fill="#FFFFFB">A</tspan><tspan fill="url(#agGoldL)" dx="1">G</tspan></text>
                <circle cx="56" cy="23" r="4" fill="url(#agGoldL)"/>
            </svg>
            <strong style="font-size:17px;letter-spacing:-.01em;">Arkamas Gas</strong>
        </div>
        <div>
            <h2>Kasir, stok tabung, dan kasbon dalam satu tempat.</h2>
            <p>Catat penjualan, pantau tabung isi–kosong–dipinjam, dan tagih piutang pelanggan tanpa buku catatan.</p>
        </div>
        <div style="font-size:13px;color:rgba(255,255,255,.7);">© {{ date('Y') }} Arkamas Gas</div>
        <svg class="auth-flame" viewBox="0 0 24 24" fill="currentColor"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>
    </aside>

    <main class="auth-main">
        <div class="auth-card">
            <h1>Masuk</h1>
            <p class="muted" style="margin:4px 0 24px;">Gunakan akun kasir atau pemilik toko.</p>

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
                    <label class="label" for="password">Password</label>
                    <div class="input-affix">
                        <input type="password" name="password" id="password" class="input" style="padding-left:12px;padding-right:44px;" autocomplete="current-password" required>
                        <button type="button" class="btn btn-ghost btn-icon btn-xs affix-btn" id="togglePw" aria-label="Tampilkan password" title="Tampilkan / sembunyikan password">
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
    this.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
});
</script>
</body>
</html>
