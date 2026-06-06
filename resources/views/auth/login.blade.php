<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login Dashboard EA</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}">
    <script>
        try {
            const saved = localStorage.getItem('ea_dashboard_theme');
            if (saved === 'dark' || saved === 'light') {
                document.documentElement.setAttribute('data-theme', saved);
            }
        } catch (e) {}
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --login-bg: radial-gradient(circle at 8% 0%, #ffe9bd 0%, #fff4df 32%, #f3f7ff 100%);
            --login-card-bg: rgba(255, 255, 255, 0.92);
            --login-card-border: rgba(251, 191, 36, 0.35);
            --login-ink: #0f172a;
            --login-muted: #475569;
            --login-input-bg: #ffffff;
            --login-input-border: #cbd5e1;
            --login-input-focus: #f59e0b;
        }

        html[data-theme='dark'] {
            --login-bg: radial-gradient(circle at 20% 0%, #1f3b66 0%, #0b1526 55%);
            --login-card-bg: rgba(10, 22, 40, 0.88);
            --login-card-border: rgba(130, 170, 230, 0.25);
            --login-ink: #e6eefc;
            --login-muted: #a7bddf;
            --login-input-bg: #091327;
            --login-input-border: rgba(145, 182, 240, 0.35);
            --login-input-focus: #5b93e0;
        }

        body {
            min-height: 100vh;
            background: var(--login-bg);
            color: var(--login-ink);
        }

        .login-shell {
            width: min(980px, 100%);
        }

        .login-side {
            border-radius: 24px;
            background: linear-gradient(140deg, rgba(245, 158, 11, 0.2), rgba(59, 130, 246, 0.2));
            border: 1px solid rgba(148, 163, 184, 0.25);
            padding: 2rem;
        }

        html[data-theme='dark'] .login-side {
            background: linear-gradient(140deg, rgba(245, 158, 11, 0.16), rgba(59, 130, 246, 0.14));
        }

        .login-card {
            border: 1px solid var(--login-card-border);
            border-radius: 24px;
            background: var(--login-card-bg);
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(8px);
        }

        .form-control {
            border-radius: 12px;
            min-height: 46px;
            background: var(--login-input-bg);
            color: var(--login-ink);
            border-color: var(--login-input-border);
        }

        .form-control:focus {
            background: var(--login-input-bg);
            color: var(--login-ink);
            border-color: var(--login-input-focus);
            box-shadow: 0 0 0 0.2rem rgba(245, 158, 11, 0.2);
        }

        .text-soft {
            color: var(--login-muted);
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .45rem .8rem;
            border-radius: 999px;
            font-size: .78rem;
            border: 1px solid rgba(245, 158, 11, 0.4);
            color: #92400e;
            background: rgba(255, 247, 237, 0.92);
        }

        html[data-theme='dark'] .brand-badge {
            color: #fde68a;
            background: rgba(60, 37, 8, 0.92);
        }

        .btn-login {
            background: linear-gradient(120deg, #f59e0b, #f97316);
            border: none;
            color: #fff;
        }

        .btn-login:hover {
            color: #fff;
            filter: brightness(1.03);
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center px-3">
<div class="login-shell row g-4 g-lg-5 align-items-stretch">
    <div class="col-lg-6 d-none d-lg-block">
        <div class="login-side h-100 d-flex flex-column justify-content-between">
            <div>
                <span class="brand-badge">EA Monster Cloud</span>
                <h2 class="h3 fw-bold mt-3 mb-2">Selamat datang kembali</h2>
                <p class="text-soft mb-0">Masuk ke akun kamu untuk lanjut atur bot, cek status lisensi, dan pantau performa tanpa ribet.</p>
            </div>
            <p class="small text-soft mb-0">Tips: cek kembali account aktif sebelum mulai setting agar perubahan tidak salah akun.</p>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="login-card p-4 p-md-5 h-100 d-flex flex-column justify-content-center" style="width:min(520px, 100%); margin-inline:auto;">
            <h1 class="h4 mb-2 fw-bold">Login Dashboard EA</h1>
            <p class="text-soft small mb-4">Masuk untuk membuka dashboard pengaturan bot Anda.</p>

            @if ($errors->any())
                <div class="alert alert-danger py-2 small">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="post" action="{{ route('login.attempt') }}">
                @csrf
                <div class="mb-3">
                    <label for="login" class="form-label small text-soft mb-1">Email / Username</label>
                    <input id="login" name="login" type="text" class="form-control" value="{{ old('login') }}" required autofocus>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label small text-soft mb-1">Password</label>
                    <input id="password" name="password" type="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-login w-100 rounded-pill py-2 fw-semibold">Login</button>
            </form>

            <div class="small text-soft mt-3 text-center">
                Belum punya akun? <a href="{{ url('/?register=1') }}">Daftar dari Halaman Utama</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
