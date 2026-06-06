<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>EA Monster Cloud Controller</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg: #08101f;
            --bg-soft: #0f172a;
            --panel: rgba(15, 23, 42, 0.82);
            --line: rgba(148, 163, 184, 0.18);
            --text: #e5eefc;
            --muted: #94a3b8;
            --accent: #f59e0b;
            --accent-2: #38bdf8;
            --accent-3: #22c55e;
        }
        html { scroll-behavior: smooth; }
        body {
            font-family: Sora, system-ui, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 20% 20%, rgba(56, 189, 248, 0.22), transparent 30%),
                radial-gradient(circle at 80% 10%, rgba(245, 158, 11, 0.18), transparent 24%),
                linear-gradient(180deg, #050816 0%, #09111f 45%, #101827 100%);
            min-height: 100vh;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(110deg, rgba(56, 189, 248, 0.09), transparent 34%),
                linear-gradient(290deg, rgba(245, 158, 11, 0.08), transparent 36%),
                repeating-linear-gradient(
                    90deg,
                    rgba(148, 163, 184, 0.045) 0,
                    rgba(148, 163, 184, 0.045) 1px,
                    transparent 1px,
                    transparent 26px
                );
            opacity: .45;
            z-index: 0;
        }
        .hero,
        section,
        footer {
            position: relative;
            z-index: 1;
        }
        .hero {
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid var(--line);
        }
        .hero::before, .hero::after {
            content: '';
            position: absolute;
            inset: auto;
            border-radius: 999px;
            filter: blur(18px);
            opacity: 0.5;
            pointer-events: none;
        }
        .hero .container { position: relative; z-index: 1; }
        .hero::before { width: 320px; height: 320px; top: -80px; right: -80px; background: rgba(56, 189, 248, 0.22); }
        .hero::after { width: 260px; height: 260px; bottom: -80px; left: -60px; background: rgba(245, 158, 11, 0.18); }
        .glass {
            background: var(--panel);
            border: 1px solid var(--line);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.24);
            backdrop-filter: blur(14px);
        }
        .badge-soft {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .45rem .8rem;
            border-radius: 999px;
            background: rgba(56, 189, 248, 0.12);
            color: #cfeeff;
            font-weight: 700;
            letter-spacing: .02em;
        }
        .cta-primary {
            background: linear-gradient(135deg, var(--accent) 0%, #fb7185 100%);
            border: 0;
            color: #111827;
            font-weight: 800;
        }
        .cta-secondary {
            border-color: rgba(255,255,255,.18);
            color: var(--text);
        }
        .section-title {
            font-size: clamp(1.5rem, 2vw, 2.25rem);
            font-weight: 800;
            letter-spacing: -0.03em;
        }
        .section-subtitle {
            color: var(--muted);
            margin-top: .35rem;
            margin-bottom: 0;
        }
        .feature-card, .tutorial-card, .pricing-card {
            height: 100%;
            border-radius: 22px;
            padding: 1.35rem;
            background: rgba(15, 23, 42, 0.75);
            border: 1px solid var(--line);
            transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease;
        }
        .feature-card:hover,
        .tutorial-card:hover,
        .pricing-card:hover {
            transform: translateY(-4px);
            border-color: rgba(56, 189, 248, 0.42);
            box-shadow: 0 18px 40px rgba(2, 6, 23, 0.32);
        }
        .metric {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.04em;
        }
        a { color: inherit; text-decoration: none; }
        .tutorial-step {
            display: grid;
            grid-template-columns: 40px 1fr;
            gap: .9rem;
            align-items: start;
            padding: .8rem 0;
            border-top: 1px solid rgba(148,163,184,.16);
        }
        .tutorial-step:first-child { border-top: 0; }
        .step-num {
            width: 40px; height: 40px;
            border-radius: 12px;
            display: grid; place-items: center;
            background: rgba(245, 158, 11, 0.14);
            color: #ffd38f;
            font-weight: 800;
        }
        .floating-card {
            animation: floaty 6s ease-in-out infinite;
        }
        .trust-strip {
            margin-top: 1.2rem;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: rgba(2, 6, 23, 0.42);
            padding: .75rem 1rem;
        }
        .trust-pill {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            background: rgba(15, 23, 42, 0.62);
            padding: .35rem .7rem;
            color: #dbeafe;
            font-size: .78rem;
            font-weight: 600;
        }
        .pay-card {
            height: 100%;
            border-radius: 18px;
            border: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.85), rgba(15, 23, 42, 0.62));
            padding: 1.15rem;
        }
        .pay-card.is-disabled {
            opacity: 0.56;
            filter: grayscale(0.2);
            border-style: dashed;
        }
        .pay-card-note {
            margin-top: .65rem;
            font-size: .78rem;
            color: #94a3b8;
            font-weight: 600;
            letter-spacing: .01em;
        }
        .pay-badge {
            display: inline-block;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .03em;
            border-radius: 999px;
            padding: .25rem .55rem;
            margin-bottom: .55rem;
        }
        .pay-badge.manual { background: rgba(56, 189, 248, 0.16); color: #7dd3fc; }
        .pay-badge.auto { background: rgba(34, 197, 94, 0.16); color: #86efac; }
        .signal-ribbon {
            border-radius: 18px;
            border: 1px solid var(--line);
            background:
                radial-gradient(circle at 0% 50%, rgba(56, 189, 248, 0.16), transparent 38%),
                radial-gradient(circle at 100% 50%, rgba(245, 158, 11, 0.18), transparent 42%),
                rgba(8, 16, 31, 0.85);
            padding: 1.05rem 1.15rem;
        }
        .signal-ribbon-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
        }
        .signal-chip {
            border: 1px solid rgba(148, 163, 184, 0.24);
            border-radius: 13px;
            background: rgba(15, 23, 42, 0.58);
            padding: .8rem;
        }
        .signal-chip .label {
            font-size: .72rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .signal-chip .value {
            margin-top: .35rem;
            font-weight: 800;
            letter-spacing: -.02em;
            color: #dbeafe;
        }
        @media (max-width: 991.98px) {
            .signal-ribbon-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .register-close-btn {
            width: 2.05rem;
            height: 2.05rem;
            padding: 0;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.45);
            opacity: 1;
            background-color: rgba(15, 23, 42, 0.7);
            background-position: center;
            background-size: 14px 14px;
            background-repeat: no-repeat;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none'%3E%3Cpath d='M3.5 3.5L12.5 12.5M12.5 3.5L3.5 12.5' stroke='%23e2e8f0' stroke-width='1.9' stroke-linecap='round'/%3E%3C/svg%3E");
            box-shadow: none;
        }

        .register-close-btn:hover {
            opacity: 1;
            transform: translateY(-1px);
            border-color: rgba(125, 211, 252, 0.72);
            background-color: rgba(30, 41, 59, 0.9);
        }

        .register-close-btn:focus {
            box-shadow: 0 0 0 0.22rem rgba(56, 189, 248, 0.24);
        }

        @keyframes floaty {
            0%,100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
    </style>
</head>
<body>
    @php
        $openRegisterModal = request()->boolean('register') || $errors->hasAny(['name', 'username', 'email', 'password']);
    @endphp
    <section class="hero py-4 py-lg-5">
        <div class="container py-2 py-lg-4">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
                <div class="badge-soft">EA Monster Cloud Controller</div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('guides.operasional-bot') }}" class="btn btn-sm cta-secondary">Panduan Lengkap</a>
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-sm cta-secondary">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-sm cta-secondary">Login</a>
                        <button type="button" class="btn btn-sm cta-primary" data-open-register-modal="1">Register</button>
                    @endauth
                </div>
            </div>

            <div class="row align-items-center g-4 g-lg-5">
                <div class="col-lg-7">
                    <h1 class="display-5 fw-black lh-1 mb-3" style="font-weight: 800; letter-spacing: -0.05em;">
                        Kelola akun MT5 Anda dalam satu dashboard: jelas, aman, dan mudah dipakai setiap hari.
                    </h1>
                    <p class="lead text-white-50 mb-4">
                        Semua proses penting sudah disiapkan: pilih akun, cek status lisensi, lanjut pembayaran, dan pantau bot tanpa langkah yang membingungkan.
                    </p>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <button type="button" class="btn btn-lg cta-primary px-4" data-open-register-modal="1">Mulai Sekarang</button>
                        <a class="btn btn-lg btn-outline-light px-4" href="https://www.hfmtrade-ind.com/sv/id/?refid=30516200" target="_blank" rel="noopener noreferrer">Buka Akun HFM</a>
                        <a class="btn btn-lg cta-secondary px-4" href="#tutorial">Cara Mulai</a>
                        <a class="btn btn-lg cta-secondary px-4" href="{{ route('guides.operasional-bot') }}">Panduan End-to-End</a>
                    </div>
                    <div class="trust-strip d-flex flex-wrap gap-2">
                        <span class="trust-pill">Status akun real-time</span>
                        <span class="trust-pill">Lisensi per account</span>
                        <span class="trust-pill">Proses aktivasi jelas</span>
                        <span class="trust-pill">Kontrol admin terpusat</span>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="feature-card">
                                <div class="text-white-50 small">Lisensi</div>
                                <div class="metric">Per Akun</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="feature-card">
                                <div class="text-white-50 small">Durasi</div>
                                <div class="metric">Monthly</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="feature-card">
                                <div class="text-white-50 small">Keamanan</div>
                                <div class="metric">Stop Saat Expired</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="glass rounded-4 p-4 p-lg-5 floating-card">
                        <div class="small text-uppercase text-white-50 mb-2">Langkah Cepat</div>
                        <div class="h3 fw-bold mb-3">Belum punya akun broker? Mulai dari sini</div>
                        <p class="text-white-50 mb-4">
                            Gunakan link resmi ini untuk buka akun, lalu lanjut sambungkan ke dashboard Anda.
                        </p>
                        <a class="btn btn-warning w-100 fw-bold mb-3" href="https://www.hfmtrade-ind.com/sv/id/?refid=30516200" target="_blank" rel="noopener noreferrer">Buka Link Referral HFM Resmi</a>
                        <div class="text-white-50 small">
                            Bisa dipakai untuk akun demo maupun live sesuai kebutuhan.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container py-2 py-lg-4">
            <div class="row g-3 g-lg-4">
                <div class="col-lg-4">
                    <div class="feature-card">
                        <div class="text-warning fw-bold mb-2">01</div>
                        <h2 class="h4 fw-bold">Satu akun, satu status lisensi</h2>
                        <p class="text-white-50 mb-0">Setiap account MT5 dikelola terpisah agar statusnya jelas dan tidak tercampur.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="feature-card">
                        <div class="text-info fw-bold mb-2">02</div>
                        <h2 class="h4 fw-bold">Pembayaran sederhana dan rapi</h2>
                        <p class="text-white-50 mb-0">Tagihan dan aktivasi diproses dari dashboard, jadi alurnya mudah diikuti.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="feature-card">
                        <div class="text-success fw-bold mb-2">03</div>
                        <h2 class="h4 fw-bold">Aman saat masa aktif habis</h2>
                        <p class="text-white-50 mb-0">Saat lisensi habis, sistem otomatis berhenti untuk mencegah aktivitas yang tidak diinginkan.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="tutorial" class="py-5">
        <div class="container py-2 py-lg-4">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="tutorial-card">
                        <div class="section-title mb-3">Langkah Buka Akun</div>
                        <div class="tutorial-step">
                            <div class="step-num">1</div>
                            <div>
                                <div class="fw-bold">Daftar akun</div>
                                <div class="text-white-50">Isi data utama Anda, lalu selesaikan verifikasi yang diminta.</div>
                            </div>
                        </div>
                        <div class="tutorial-step">
                            <div class="step-num">2</div>
                            <div>
                                <div class="fw-bold">Lengkapi profil</div>
                                <div class="text-white-50">Pastikan data profil sesuai agar proses aktivasi berjalan lancar.</div>
                            </div>
                        </div>
                        <div class="tutorial-step">
                            <div class="step-num">3</div>
                            <div>
                                <div class="fw-bold">Deposit dan pilih akun trading</div>
                                <div class="text-white-50">Anda bisa mulai dari demo atau langsung live sesuai kebutuhan.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="tutorial-card">
                        <div class="section-title mb-3">Langkah Pakai Dashboard</div>
                        <div class="tutorial-step">
                            <div class="step-num">1</div>
                            <div>
                                <div class="fw-bold">Masuk ke dashboard</div>
                                <div class="text-white-50">Login lalu pilih account MT5 yang ingin Anda atur.</div>
                            </div>
                        </div>
                        <div class="tutorial-step">
                            <div class="step-num">2</div>
                            <div>
                                <div class="fw-bold">Ajukan aktivasi lisensi</div>
                                <div class="text-white-50">Lanjutkan proses pembayaran sesuai metode yang tersedia.</div>
                            </div>
                        </div>
                        <div class="tutorial-step">
                            <div class="step-num">3</div>
                            <div>
                                <div class="fw-bold">Mulai monitoring harian</div>
                                <div class="text-white-50">Pantau status akun dan lakukan penyesuaian dari satu tempat.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container py-2 py-lg-4">
            <div class="mb-4">
                <h2 class="section-title">Metode Pembayaran</h2>
                <p class="section-subtitle">Pilih metode yang paling nyaman. Semua status pembayaran bisa dipantau langsung.</p>
            </div>
            <div class="row g-3 g-lg-4 mb-4">
                <div class="col-lg-4">
                    <div class="pay-card">
                        <span class="pay-badge manual">MANUAL</span>
                        <div class="h5 fw-bold">Transfer Bank</div>
                        <p class="text-white-50 mb-0">Transfer lalu kirim detail pembayaran, kemudian diverifikasi dari dashboard.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="pay-card is-disabled" aria-disabled="true">
                        <span class="pay-badge auto">AUTO</span>
                        <div class="h5 fw-bold">QRIS Otomatis</div>
                        <p class="text-white-50 mb-0">QR dibuat otomatis per invoice dan status ter-update otomatis saat pembayaran sukses.</p>
                        <div class="pay-card-note">Belum aktif saat ini</div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="pay-card is-disabled" aria-disabled="true">
                        <span class="pay-badge auto">AUTO</span>
                        <div class="h5 fw-bold">Virtual Account</div>
                        <p class="text-white-50 mb-0">Nomor VA unik membantu proses pembayaran lebih rapi dan minim salah transfer.</p>
                        <div class="pay-card-note">Belum aktif saat ini</div>
                    </div>
                </div>
            </div>
            <div class="row g-3 g-lg-4 align-items-stretch">
                <div class="col-lg-4">
                    <div class="pricing-card">
                        <div class="text-uppercase text-warning small fw-bold mb-2">Client Flow</div>
                        <div class="h4 fw-bold">Daftar, pilih akun, lalu aktivasi</div>
                        <p class="text-white-50 mb-0">Alur dibuat ringkas agar pengguna baru cepat memahami langkah berikutnya.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="pricing-card">
                        <div class="text-uppercase text-info small fw-bold mb-2">Admin Flow</div>
                        <div class="h4 fw-bold">Verifikasi dan aktifkan lisensi</div>
                        <p class="text-white-50 mb-0">Admin dapat memvalidasi pembayaran dan mengatur masa aktif akun dengan cepat.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="pricing-card">
                        <div class="text-uppercase text-success small fw-bold mb-2">Security Flow</div>
                        <div class="h4 fw-bold">Status jelas dan aman</div>
                        <p class="text-white-50 mb-0">Saat status tidak aktif, sistem langsung membatasi akses sesuai kebijakan.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-2 pb-5">
        <div class="container">
            <div class="signal-ribbon">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div class="fw-bold">Kenapa tampilan ini beda dari panel trading biasa?</div>
                    <div class="small text-white-50">Fokus ke keputusan cepat, bukan layar yang ramai.</div>
                </div>
                <div class="signal-ribbon-grid">
                    <div class="signal-chip">
                        <div class="label">Realtime Mindset</div>
                        <div class="value">Status langsung terbaca</div>
                    </div>
                    <div class="signal-chip">
                        <div class="label">Guided Flow</div>
                        <div class="value">User baru tetap paham langkahnya</div>
                    </div>
                    <div class="signal-chip">
                        <div class="label">Operational Ready</div>
                        <div class="value">Admin approve lebih cepat</div>
                    </div>
                    <div class="signal-chip">
                        <div class="label">Secure by Default</div>
                        <div class="value">Akses dibatasi saat expired</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @guest
    <div class="modal fade" id="register-modal" tabindex="-1" aria-labelledby="register-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: #0b1526; border: 1px solid rgba(148, 163, 184, 0.25); border-radius: 20px; color: #e5eefc;">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <div class="small text-uppercase text-white-50">Registrasi Akun</div>
                        <h2 id="register-modal-title" class="h4 fw-bold mb-1">Buat akun baru</h2>
                        <p class="text-white-50 small mb-0">Lengkapi data berikut untuk mulai menggunakan dashboard.</p>
                    </div>
                    <button type="button" class="btn-close register-close-btn" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    @if ($errors->hasAny(['name', 'username', 'email', 'password']))
                        <div class="alert alert-danger py-2 small">
                            {{ $errors->first() }}
                        </div>
                    @endif
                    <form method="post" action="{{ route('register.attempt') }}" class="d-grid gap-3">
                        @csrf
                        <div>
                            <label for="register_name" class="form-label small text-white-50 mb-1">Nama Lengkap</label>
                            <input id="register_name" name="name" type="text" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div>
                            <label for="register_username" class="form-label small text-white-50 mb-1">Username</label>
                            <input id="register_username" name="username" type="text" class="form-control" value="{{ old('username') }}" required>
                        </div>
                        <div>
                            <label for="register_email" class="form-label small text-white-50 mb-1">Email</label>
                            <input id="register_email" name="email" type="email" class="form-control" value="{{ old('email') }}" required>
                        </div>
                        <div>
                            <label for="register_password" class="form-label small text-white-50 mb-1">Password</label>
                            <input id="register_password" name="password" type="password" class="form-control" minlength="8" required>
                        </div>
                        <div>
                            <label for="register_password_confirmation" class="form-label small text-white-50 mb-1">Konfirmasi Password</label>
                            <input id="register_password_confirmation" name="password_confirmation" type="password" class="form-control" minlength="8" required>
                        </div>
                        <button type="submit" class="btn cta-primary w-100 rounded-pill py-2">Daftar Sekarang</button>
                        <a href="{{ route('login') }}" class="btn btn-outline-light rounded-pill py-2">Saya sudah punya akun</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endguest

    <footer class="py-4 border-top" style="border-color: var(--line) !important;">
        <div class="container d-flex flex-wrap justify-content-between gap-2 text-white-50 small">
            <div>EA Monster Cloud Controller</div>
            <div>Promo HFM referral: <a class="text-warning text-decoration-none" href="https://www.hfmtrade-ind.com/sv/id/?refid=30516200" target="_blank" rel="noopener noreferrer">Buka Link Resmi</a></div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @guest
    <script>
        (() => {
            const modalEl = document.getElementById('register-modal');
            if (!modalEl) return;

            const modal = new bootstrap.Modal(modalEl);
            document.querySelectorAll('[data-open-register-modal="1"]').forEach((node) => {
                node.addEventListener('click', () => modal.show());
            });

            const shouldAutoOpen = @json($openRegisterModal);
            if (shouldAutoOpen) {
                modal.show();
            }
        })();
    </script>
    @endguest
</body>
</html>
