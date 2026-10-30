<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Toko ARKAMAS</title>

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Jost:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            display: grid;
            place-items: center;
            min-height: 100vh;
            padding: 24px;
            background: var(--bg);
        }
        .error-card {
            width: 100%;
            max-width: 440px;
            text-align: center;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-xl);
            padding: 40px 32px;
            box-shadow: var(--shadow-md);
        }
        .error-code {
            font-family: var(--font-display);
            font-size: 56px;
            font-weight: 700;
            line-height: 1;
            color: var(--primary);
            margin-bottom: 12px;
        }
        .error-title {
            font-family: var(--font-display);
            font-size: 20px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 8px;
        }
        .error-desc {
            font-size: 14px;
            color: var(--text-2);
            line-height: 1.5;
            margin-bottom: 24px;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">@yield('code')</div>
        <h1 class="error-title">@yield('title')</h1>
        <p class="error-desc">@yield('message')</p>
        <a href="{{ route('dashboard') }}" class="btn btn-primary" style="display:inline-flex;align-items:center;justify-content:center;gap:8px;">
            <x-icon name="arrow-left" /> Kembali ke dashboard
        </a>
    </div>
</body>
</html>
