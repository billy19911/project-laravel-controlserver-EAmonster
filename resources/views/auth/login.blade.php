<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login Dashboard EA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: radial-gradient(circle at 20% 0%, #1f3b66 0%, #0b1526 55%);
            color: #e6eefc;
        }

        .login-card {
            border: 1px solid rgba(130, 170, 230, 0.25);
            border-radius: 18px;
            background: rgba(10, 22, 40, 0.88);
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.35);
            backdrop-filter: blur(8px);
        }

        .form-control {
            border-radius: 12px;
            min-height: 46px;
            background: #091327;
            color: #e6eefc;
            border-color: rgba(145, 182, 240, 0.35);
        }

        .form-control:focus {
            background: #091327;
            color: #e6eefc;
            border-color: #5b93e0;
            box-shadow: 0 0 0 0.2rem rgba(91, 147, 224, 0.2);
        }

        .text-soft {
            color: #a7bddf;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center px-3">
<div class="login-card p-4 p-md-5" style="width:min(440px, 100%);">
    <h1 class="h4 mb-2 fw-bold">EA Cloud Controller</h1>
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
        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold">Login</button>
    </form>

    <div class="small text-soft mt-3 text-center">
        Belum punya akun? <a href="{{ url('/?register=1') }}" class="link-light">Daftar dari landing page</a>
    </div>
</div>
</body>
</html>
