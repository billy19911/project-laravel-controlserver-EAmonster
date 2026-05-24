<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register Dashboard EA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: radial-gradient(circle at 85% 10%, #15395f 0%, #0a1628 52%);
            color: #e6eefc;
        }

        .register-card {
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
<body class="d-flex align-items-center justify-content-center px-3 py-4">
<div class="register-card p-4 p-md-5" style="width:min(540px, 100%);">
    <h1 class="h4 mb-2 fw-bold">Create Account EA Cloud Controller</h1>
    <p class="text-soft small mb-4">Daftar akun baru untuk mengakses dashboard pengaturan bot.</p>

    @if ($errors->any())
        <div class="alert alert-danger py-2 small">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="post" action="{{ route('register.attempt') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label small text-soft mb-1">Nama</label>
                <input id="name" name="name" type="text" class="form-control" value="{{ old('name') }}" required>
            </div>
            <div class="col-md-6">
                <label for="username" class="form-label small text-soft mb-1">Username</label>
                <input id="username" name="username" type="text" class="form-control" value="{{ old('username') }}" required>
            </div>
            <div class="col-12">
                <label for="email" class="form-label small text-soft mb-1">Email</label>
                <input id="email" name="email" type="email" class="form-control" value="{{ old('email') }}" required>
            </div>
            <div class="col-md-6">
                <label for="password" class="form-label small text-soft mb-1">Password</label>
                <input id="password" name="password" type="password" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="password_confirmation" class="form-label small text-soft mb-1">Konfirmasi Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold mt-4">Daftar dan Masuk</button>
    </form>

    <div class="small text-soft mt-3 text-center">
        Sudah punya akun? <a href="{{ route('login') }}" class="link-light">Login di sini</a>
    </div>
</div>
</body>
</html>
