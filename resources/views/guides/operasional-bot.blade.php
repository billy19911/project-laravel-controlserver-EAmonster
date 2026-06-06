<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panduan Lengkap Pengoperasian Bot</title>
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
            --guide-accent: #f59e0b;
            --guide-accent-soft: #fff7e6;
            --guide-accent-ink: #78350f;
            --guide-ink: #0f172a;
            --guide-muted: #475569;
            --guide-border: #e2e8f0;
            --guide-surface: #ffffff;
            --guide-surface-alt: #f8fafc;
            --guide-bg: radial-gradient(circle at top right, #fff4cc 0, #f8fafc 38%, #f1f5f9 100%);
            --guide-chip-border: #fdba74;
            --guide-chip-text: #9a3412;
            --guide-chip-bg: #ffedd5;
            --guide-hero-border: #fde68a;
            --guide-hero-bg: linear-gradient(135deg, #fff8e1 0%, #fff 45%, #fff7ed 100%);
            --guide-tip-bg: #fffbeb;
            --guide-tip-text: #78350f;
            --guide-main-tab-ink: #1e293b;
            --guide-main-tab-bg: #fff;
            --guide-main-tab-active-ink: #78350f;
            --guide-main-tab-active-bg: #fffbeb;
            --guide-step-tab-hover-ink: #92400e;
            --guide-step-tab-hover-bg: #fff7e6;
        }

        html[data-theme='dark'] {
            --guide-ink: #e2e8f0;
            --guide-muted: #94a3b8;
            --guide-border: #334155;
            --guide-surface: #0f172a;
            --guide-surface-alt: #111827;
            --guide-bg: radial-gradient(circle at top right, #1f2937 0, #0f172a 45%, #020617 100%);
            --guide-chip-border: #b45309;
            --guide-chip-text: #fcd34d;
            --guide-chip-bg: #3f2a12;
            --guide-hero-border: #7c2d12;
            --guide-hero-bg: linear-gradient(135deg, #1f2937 0%, #0f172a 45%, #1e293b 100%);
            --guide-tip-bg: #2b2111;
            --guide-tip-text: #fde68a;
            --guide-main-tab-ink: #e2e8f0;
            --guide-main-tab-bg: #0f172a;
            --guide-main-tab-active-ink: #fde68a;
            --guide-main-tab-active-bg: #3f2a12;
            --guide-accent-soft: #2a2216;
            --guide-accent-ink: #fde68a;
            --guide-step-tab-hover-ink: #fde68a;
            --guide-step-tab-hover-bg: #3f2a12;
        }

        body {
            background: var(--guide-bg);
            color: var(--guide-ink);
        }

        .guide-shell {
            max-width: 980px;
        }

        .guide-hero {
            border: 1px solid var(--guide-hero-border);
            background: var(--guide-hero-bg);
        }

        .guide-chip {
            font-size: .78rem;
            letter-spacing: .04em;
            border: 1px solid var(--guide-chip-border);
            color: var(--guide-chip-text);
            background: var(--guide-chip-bg);
            padding: .35rem .65rem;
            border-radius: 999px;
            display: inline-block;
        }

        .guide-toc a {
            color: #1d4ed8;
            text-decoration: none;
        }

        .guide-toc a:hover {
            text-decoration: underline;
        }

        .step-card {
            border: 1px solid var(--guide-border);
            border-radius: 1rem;
            background: var(--guide-surface);
        }

        .step-head {
            border-bottom: 1px solid var(--guide-border);
            background: var(--guide-accent-soft);
            color: var(--guide-accent-ink);
            border-top-left-radius: 1rem;
            border-top-right-radius: 1rem;
        }

        .step-head .meta-note {
            color: color-mix(in srgb, var(--guide-accent-ink) 72%, #94a3b8 28%);
        }

        .img-slot {
            border: 2px dashed #cbd5e1;
            border-radius: .85rem;
            background: var(--guide-surface-alt);
            color: #64748b;
            min-height: 170px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1rem;
            font-weight: 600;
        }

        .tips-box {
            border-left: 4px solid var(--guide-accent);
            background: var(--guide-tip-bg);
            color: var(--guide-tip-text);
            border-radius: .5rem;
            padding: .9rem 1rem;
        }

        .meta-note {
            color: var(--guide-muted);
            font-size: .94rem;
        }

        .section-list li {
            margin-bottom: .5rem;
        }

        .step-tabs-wrap {
            border: 1px solid var(--guide-border);
            border-radius: 1rem;
            background: var(--guide-surface);
            padding: 1rem;
        }

        .step-tabs .nav-link {
            border-radius: 999px;
            border: 1px solid var(--guide-border);
            color: var(--guide-main-tab-ink);
            background: var(--guide-main-tab-bg);
            font-weight: 600;
            font-size: .92rem;
        }

        .step-tabs .nav-link:hover,
        .step-tabs .nav-link:focus {
            border-color: #fbbf24;
            color: var(--guide-step-tab-hover-ink);
            background: var(--guide-step-tab-hover-bg);
        }

        .step-tabs .nav-link.active {
            border-color: #f59e0b;
            color: var(--guide-main-tab-active-ink);
            background: var(--guide-main-tab-active-bg);
        }

        .step-pane {
            scroll-margin-top: 1rem;
        }

        .guide-step-image {
            width: 100%;
            max-height: 420px;
            object-fit: contain;
            background: var(--guide-surface-alt);
        }

        .guide-image-trigger {
            cursor: zoom-in;
        }

        .admin-upload-card {
            border: 1px dashed #fdba74;
            background: #fffbeb;
            border-radius: .85rem;
            padding: .9rem;
        }

        .guide-modal-image {
            max-height: 80vh;
            object-fit: contain;
            width: 100%;
            background: #0f172a;
            border-radius: .75rem;
        }

        .main-tabs .nav-link {
            border-radius: 999px;
            border: 1px solid var(--guide-border);
            color: var(--guide-main-tab-ink);
            background: var(--guide-main-tab-bg);
            font-weight: 700;
        }

        .main-tabs .nav-link.active {
            border-color: #f59e0b;
            color: var(--guide-main-tab-active-ink);
            background: var(--guide-main-tab-active-bg);
        }

        .faq-card {
            border: 1px solid var(--guide-border);
            border-radius: 1rem;
            background: var(--guide-surface);
        }

        .guide-checklist {
            border: 1px solid var(--guide-border);
            border-radius: 1rem;
            background: var(--guide-surface);
        }

        .guide-checklist .card-body {
            color: var(--guide-ink);
        }

        .accordion-item {
            overflow: hidden;
        }

        .accordion-button {
            background: var(--guide-surface);
            color: var(--guide-ink);
            font-weight: 600;
            box-shadow: none;
        }

        .accordion-button:not(.collapsed) {
            color: var(--guide-main-tab-active-ink);
            background: var(--guide-main-tab-active-bg);
            box-shadow: none;
        }

        .accordion-body {
            background: var(--guide-surface);
            color: var(--guide-ink);
        }

        .safe-mode-box {
            border: 1px solid var(--guide-border);
            border-left: 4px solid var(--guide-accent);
            border-radius: .9rem;
            background: var(--guide-surface-alt);
            padding: .95rem 1rem;
        }

        .safe-mode-box p,
        .safe-mode-box li {
            color: var(--guide-ink);
        }

        .faq-question {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: .6rem;
            color: #0f172a;
        }

        .faq-answer p:last-child,
        .faq-answer ul:last-child {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    @php
        $stepImages = is_array($stepImages ?? null) ? $stepImages : [];
        $isGuideAdmin = (bool) ($isGuideAdmin ?? false);
        $requestedStep = max(1, min(6, (int) ($requestedStep ?? 1)));
    @endphp
    <main class="container guide-shell py-4 py-lg-5">
        <section class="guide-hero rounded-4 p-4 p-lg-5 shadow-sm mb-4">
            <span class="guide-chip">Panduan End-to-End</span>
            <h1 class="h2 mt-3 mb-2">Panduan Lengkap Pengoperasian Bot</h1>
            <p class="mb-2">Dokumen ini dibuat khusus untuk pengguna baru. Ikuti urutan langkah dari awal sampai akhir agar akun dan bot aktif tanpa kendala.</p>
            <p class="meta-note mb-0">Estimasi waktu setup: 30-60 menit (tergantung proses verifikasi akun broker).</p>
        </section>

        @if($isGuideAdmin && session('guide_upload_success'))
            <div class="alert alert-success shadow-sm">{{ session('guide_upload_success') }}</div>
        @endif

        @if($isGuideAdmin && $errors->any())
            <div class="alert alert-danger shadow-sm">{{ $errors->first() }}</div>
        @endif

        <section class="mb-4">
            <ul class="nav nav-pills main-tabs gap-2 mb-3" id="guide-main-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="guide-main-tab" data-bs-toggle="pill" data-bs-target="#guide-main-pane" type="button" role="tab" aria-controls="guide-main-pane" aria-selected="true">Panduan Step-by-Step</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="faq-main-tab" data-bs-toggle="pill" data-bs-target="#faq-main-pane" type="button" role="tab" aria-controls="faq-main-pane" aria-selected="false">FAQ</button>
                </li>
            </ul>

            <div class="tab-content" id="guide-main-tab-content">
                <div class="tab-pane fade show active" id="guide-main-pane" role="tabpanel" aria-labelledby="guide-main-tab" tabindex="0">
                    <section class="step-tabs-wrap shadow-sm mb-4">
            <ul class="nav nav-pills step-tabs gap-2 mb-3" id="guide-step-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $requestedStep === 1 ? 'active' : '' }}" id="step-tab-1" data-bs-toggle="pill" data-bs-target="#tab-step-1" type="button" role="tab" aria-controls="tab-step-1" aria-selected="{{ $requestedStep === 1 ? 'true' : 'false' }}">Step 1</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $requestedStep === 2 ? 'active' : '' }}" id="step-tab-2" data-bs-toggle="pill" data-bs-target="#tab-step-2" type="button" role="tab" aria-controls="tab-step-2" aria-selected="{{ $requestedStep === 2 ? 'true' : 'false' }}">Step 2</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $requestedStep === 3 ? 'active' : '' }}" id="step-tab-3" data-bs-toggle="pill" data-bs-target="#tab-step-3" type="button" role="tab" aria-controls="tab-step-3" aria-selected="{{ $requestedStep === 3 ? 'true' : 'false' }}">Step 3</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $requestedStep === 4 ? 'active' : '' }}" id="step-tab-4" data-bs-toggle="pill" data-bs-target="#tab-step-4" type="button" role="tab" aria-controls="tab-step-4" aria-selected="{{ $requestedStep === 4 ? 'true' : 'false' }}">Step 4</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $requestedStep === 5 ? 'active' : '' }}" id="step-tab-5" data-bs-toggle="pill" data-bs-target="#tab-step-5" type="button" role="tab" aria-controls="tab-step-5" aria-selected="{{ $requestedStep === 5 ? 'true' : 'false' }}">Step 5</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $requestedStep === 6 ? 'active' : '' }}" id="step-tab-6" data-bs-toggle="pill" data-bs-target="#tab-step-6" type="button" role="tab" aria-controls="tab-step-6" aria-selected="{{ $requestedStep === 6 ? 'true' : 'false' }}">Step 6</button>
                </li>
            </ul>

            <div class="tab-content" id="guide-step-content">
                <section class="tab-pane fade {{ $requestedStep === 1 ? 'show active' : '' }} step-pane" id="tab-step-1" role="tabpanel" aria-labelledby="step-tab-1" tabindex="0">
                    <div class="step-card shadow-sm">
                        <div class="step-head p-3 p-lg-4">
                            <h3 class="h5 mb-1">Langkah 1: Registrasi di Website Resmi</h3>
                            <p class="meta-note mb-0">Tujuan: membuat akun utama untuk akses dashboard dan lisensi bot.</p>
                        </div>
                        <div class="p-3 p-lg-4">
                            <ol class="section-list">
                                <li>Buka website resmi layanan bot.</li>
                                <li>Klik tombol Daftar atau Register.</li>
                                <li>Isi data: nama lengkap, email aktif, username, dan password kuat.</li>
                                <li>Pastikan email dan nomor kontak yang kamu masukkan benar-benar aktif.</li>
                                <li>Selesaikan verifikasi email jika diminta, lalu login ke dashboard.</li>
                            </ol>
                            @if(!empty($stepImages[1]))
                                <button type="button" class="btn p-0 border-0 bg-transparent w-100 text-start guide-image-trigger mt-3" data-guide-image-src="{{ $stepImages[1] }}" data-guide-image-alt="Screenshot form registrasi website resmi">
                                    <img src="{{ $stepImages[1] }}" alt="Screenshot form registrasi website resmi" class="img-fluid rounded-3 border guide-step-image">
                                </button>
                            @endif
                            @if($isGuideAdmin)
                                <div class="admin-upload-card mt-3">
                                    <div class="small fw-semibold">Kelola gambar Step 1 (admin only)</div>
                                    @if(empty($stepImages[1]))
                                        <div class="img-slot mt-2">[Tempatkan Gambar Di Sini]<br>Screenshot form registrasi website resmi</div>
                                    @endif
                                    <form method="post" action="{{ route('guides.operasional-bot.upload-image') }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 mt-2">
                                        @csrf
                                        <input type="hidden" name="step" value="1">
                                        <input type="file" name="step_image" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp" required>
                                        <button type="submit" class="btn btn-sm btn-dark">{{ empty($stepImages[1]) ? 'Upload Gambar' : 'Ganti Gambar' }}</button>
                                    </form>
                                </div>
                            @endif
                            <div class="tips-box mt-3">
                                <strong>Tips sukses:</strong> pakai email khusus trading agar notifikasi penting (billing, status lisensi, reset password) tidak tertumpuk dengan email lain.
                            </div>
                        </div>
                    </div>
                </section>

                <section class="tab-pane fade {{ $requestedStep === 2 ? 'show active' : '' }} step-pane" id="tab-step-2" role="tabpanel" aria-labelledby="step-tab-2" tabindex="0">
                    <div class="step-card shadow-sm">
                        <div class="step-head p-3 p-lg-4">
                            <h3 class="h5 mb-1">Langkah 2: Pendaftaran di Broker Mitra</h3>
                            <p class="meta-note mb-0">Tujuan: menyiapkan akun broker yang akan dipakai oleh EA di MT5.</p>
                        </div>
                        <div class="p-3 p-lg-4">
                            <ol class="section-list">
                                <li>Masuk ke halaman broker mitra dari link referral resmi.</li>
                                <li>Klik buka akun baru, lalu isi data identitas dengan benar.</li>
                                <li>Lakukan verifikasi identitas (KYC) sesuai instruksi broker.</li>
                                <li>Aktifkan area member broker sampai status akun dinyatakan valid.</li>
                                <li>Simpan data penting: email broker, nomor akun, dan server login.</li>
                            </ol>
                            @if(!empty($stepImages[2]))
                                <button type="button" class="btn p-0 border-0 bg-transparent w-100 text-start guide-image-trigger mt-3" data-guide-image-src="{{ $stepImages[2] }}" data-guide-image-alt="Screenshot halaman pendaftaran broker mitra">
                                    <img src="{{ $stepImages[2] }}" alt="Screenshot halaman pendaftaran broker mitra" class="img-fluid rounded-3 border guide-step-image">
                                </button>
                            @endif
                            @if($isGuideAdmin)
                                <div class="admin-upload-card mt-3">
                                    <div class="small fw-semibold">Kelola gambar Step 2 (admin only)</div>
                                    @if(empty($stepImages[2]))
                                        <div class="img-slot mt-2">[Tempatkan Gambar Di Sini]<br>Screenshot halaman pendaftaran broker mitra</div>
                                    @endif
                                    <form method="post" action="{{ route('guides.operasional-bot.upload-image') }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 mt-2">
                                        @csrf
                                        <input type="hidden" name="step" value="2">
                                        <input type="file" name="step_image" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp" required>
                                        <button type="submit" class="btn btn-sm btn-dark">{{ empty($stepImages[2]) ? 'Upload Gambar' : 'Ganti Gambar' }}</button>
                                    </form>
                                </div>
                            @endif
                            <div class="tips-box mt-3">
                                <strong>Tips sukses:</strong> jangan campur data pribadi palsu. Jika data tidak konsisten, proses WD atau verifikasi lanjutan bisa tertahan.
                            </div>
                        </div>
                    </div>
                </section>

                <section class="tab-pane fade {{ $requestedStep === 3 ? 'show active' : '' }} step-pane" id="tab-step-3" role="tabpanel" aria-labelledby="step-tab-3" tabindex="0">
                    <div class="step-card shadow-sm">
                        <div class="step-head p-3 p-lg-4">
                            <h3 class="h5 mb-1">Langkah 3: Cara Membuka Akun Trading tipe MT5 Cent (Cent Account)</h3>
                            <p class="meta-note mb-0">Tujuan: memilih jenis akun yang sesuai untuk nominal modal kecil-menengah.</p>
                        </div>
                        <div class="p-3 p-lg-4">
                            <ol class="section-list">
                                <li>Di area member broker, pilih menu Buka Akun Trading.</li>
                                <li>Pilih platform <strong>MT5</strong>.</li>
                                <li>Pilih tipe akun <strong>Cent Account</strong> (bukan Standard/ECN jika ingin mode cent).</li>
                                <li>Tentukan leverage sesuai profil risiko pribadi.</li>
                                <li>Setelah akun jadi, catat: nomor akun MT5, server, dan password trader.</li>
                                <li>Login akun tersebut di aplikasi MT5 untuk memastikan akun aktif.</li>
                            </ol>
                            @if(!empty($stepImages[3]))
                                <button type="button" class="btn p-0 border-0 bg-transparent w-100 text-start guide-image-trigger mt-3" data-guide-image-src="{{ $stepImages[3] }}" data-guide-image-alt="Screenshot pemilihan tipe akun MT5 Cent">
                                    <img src="{{ $stepImages[3] }}" alt="Screenshot pemilihan tipe akun MT5 Cent" class="img-fluid rounded-3 border guide-step-image">
                                </button>
                            @endif
                            @if($isGuideAdmin)
                                <div class="admin-upload-card mt-3">
                                    <div class="small fw-semibold">Kelola gambar Step 3 (admin only)</div>
                                    @if(empty($stepImages[3]))
                                        <div class="img-slot mt-2">[Tempatkan Gambar Di Sini]<br>Screenshot pemilihan tipe akun MT5 Cent</div>
                                    @endif
                                    <form method="post" action="{{ route('guides.operasional-bot.upload-image') }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 mt-2">
                                        @csrf
                                        <input type="hidden" name="step" value="3">
                                        <input type="file" name="step_image" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp" required>
                                        <button type="submit" class="btn btn-sm btn-dark">{{ empty($stepImages[3]) ? 'Upload Gambar' : 'Ganti Gambar' }}</button>
                                    </form>
                                </div>
                            @endif
                            <div class="tips-box mt-3">
                                <strong>Tips sukses:</strong> cek nama server MT5 dengan teliti. Salah server adalah penyebab paling sering gagal login akun di terminal MT5.
                            </div>
                        </div>
                    </div>
                </section>

                <section class="tab-pane fade {{ $requestedStep === 4 ? 'show active' : '' }} step-pane" id="tab-step-4" role="tabpanel" aria-labelledby="step-tab-4" tabindex="0">
                    <div class="step-card shadow-sm">
                        <div class="step-head p-3 p-lg-4">
                            <h3 class="h5 mb-1">Langkah 4: Cara Menambahkan (Menghubungkan) Akun MT5 ke dalam Sistem Web</h3>
                            <p class="meta-note mb-0">Tujuan: memastikan account MT5 sudah muncul di dashboard agar bisa dipilih saat setting dan billing.</p>
                        </div>
                        <div class="p-3 p-lg-4">
                            <ol class="section-list">
                                <li>Di dashboard, buka menu tambah akun atau Add Account.</li>
                                <li>Masukkan Account ID MT5 dan Pair Symbol sesuai akun chart aktif.</li>
                                <li>Pastikan EA terpasang di chart MT5 yang benar dan auto trading aktif.</li>
                                <li>Pastikan URL API sudah masuk whitelist WebRequest di MT5.</li>
                                <li>Tunggu status account berubah online di monitor dashboard.</li>
                                <li>Lakukan verifikasi: nilai floating, layer, dan history report bergerak otomatis.</li>
                            </ol>
                            @if(!empty($stepImages[4]))
                                <button type="button" class="btn p-0 border-0 bg-transparent w-100 text-start guide-image-trigger mt-3" data-guide-image-src="{{ $stepImages[4] }}" data-guide-image-alt="Screenshot form add account dan status online monitor">
                                    <img src="{{ $stepImages[4] }}" alt="Screenshot form add account dan status online monitor" class="img-fluid rounded-3 border guide-step-image">
                                </button>
                            @endif
                            @if($isGuideAdmin)
                                <div class="admin-upload-card mt-3">
                                    <div class="small fw-semibold">Kelola gambar Step 4 (admin only)</div>
                                    @if(empty($stepImages[4]))
                                        <div class="img-slot mt-2">[Tempatkan Gambar Di Sini]<br>Screenshot form add account + status online monitor</div>
                                    @endif
                                    <form method="post" action="{{ route('guides.operasional-bot.upload-image') }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 mt-2">
                                        @csrf
                                        <input type="hidden" name="step" value="4">
                                        <input type="file" name="step_image" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp" required>
                                        <button type="submit" class="btn btn-sm btn-dark">{{ empty($stepImages[4]) ? 'Upload Gambar' : 'Ganti Gambar' }}</button>
                                    </form>
                                </div>
                            @endif
                            <div class="tips-box mt-3">
                                <strong>Tips sukses:</strong> tambah account MT5 dulu sebelum masuk menu billing, supaya account muncul di dropdown form billing.
                            </div>
                        </div>
                    </div>
                </section>

                <section class="tab-pane fade {{ $requestedStep === 5 ? 'show active' : '' }} step-pane" id="tab-step-5" role="tabpanel" aria-labelledby="step-tab-5" tabindex="0">
                    <div class="step-card shadow-sm">
                        <div class="step-head p-3 p-lg-4">
                            <h3 class="h5 mb-1">Langkah 5: Sistem Payment Billing & Panduan Cara Mengisi Form Billing Pembayaran</h3>
                            <p class="meta-note mb-0">Tujuan: mengaktifkan lisensi agar menu setting bisa dipakai penuh.</p>
                        </div>
                        <div class="p-3 p-lg-4">
                            <ol class="section-list">
                                <li>Masuk ke menu Billing atau Langganan di website resmi.</li>
                                <li>Pilih paket lisensi sesuai kebutuhan (misal bulanan/tahunan).</li>
                                <li>Isi form billing: nama, email, nomor WhatsApp, account ID MT5, dan paket dipilih.</li>
                                <li>Pilih metode pembayaran, lalu lakukan transfer sesuai nominal invoice.</li>
                                <li>Upload bukti bayar jika sistem meminta konfirmasi manual.</li>
                                <li>Tunggu status billing berubah menjadi Paid/Active.</li>
                            </ol>
                            @if(!empty($stepImages[5]))
                                <button type="button" class="btn p-0 border-0 bg-transparent w-100 text-start guide-image-trigger mt-3" data-guide-image-src="{{ $stepImages[5] }}" data-guide-image-alt="Screenshot form billing dan contoh invoice">
                                    <img src="{{ $stepImages[5] }}" alt="Screenshot form billing dan contoh invoice" class="img-fluid rounded-3 border guide-step-image">
                                </button>
                            @endif
                            @if($isGuideAdmin)
                                <div class="admin-upload-card mt-3">
                                    <div class="small fw-semibold">Kelola gambar Step 5 (admin only)</div>
                                    @if(empty($stepImages[5]))
                                        <div class="img-slot mt-2">[Tempatkan Gambar Di Sini]<br>Screenshot form billing dan contoh invoice</div>
                                    @endif
                                    <form method="post" action="{{ route('guides.operasional-bot.upload-image') }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 mt-2">
                                        @csrf
                                        <input type="hidden" name="step" value="5">
                                        <input type="file" name="step_image" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp" required>
                                        <button type="submit" class="btn btn-sm btn-dark">{{ empty($stepImages[5]) ? 'Upload Gambar' : 'Ganti Gambar' }}</button>
                                    </form>
                                </div>
                            @endif
                            <div class="tips-box mt-3">
                                <strong>Tips sukses:</strong> gunakan email yang sama antara akun website dan billing agar verifikasi lisensi lebih cepat.
                            </div>
                        </div>
                    </div>
                </section>

                <section class="tab-pane fade {{ $requestedStep === 6 ? 'show active' : '' }} step-pane" id="tab-step-6" role="tabpanel" aria-labelledby="step-tab-6" tabindex="0">
                    <div class="step-card shadow-sm">
                        <div class="step-head p-3 p-lg-4">
                            <h3 class="h5 mb-1">Langkah 6: Cara Melakukan Setting Awal di Dashboard Web</h3>
                            <p class="meta-note mb-0">Tujuan: menyiapkan parameter bot setelah lisensi sudah aktif.</p>
                        </div>
                        <div class="p-3 p-lg-4">
                            <ol class="section-list">
                                <li>Login ke dashboard web menggunakan akun resmi yang sudah terdaftar.</li>
                                <li>Pastikan status lisensi account MT5 sudah Active/Paid.</li>
                                <li>Pilih account MT5 yang ingin disetup.</li>
                                <li>Isi parameter dasar: base lot, batas maksimal layer, target TP, dan batas drawdown.</li>
                                <li>Aktifkan filter penting: sesi trading, news filter, dan guard proteksi.</li>
                                <li>Simpan pengaturan lalu cek status tersimpan (save success).</li>
                            </ol>
                            @if(!empty($stepImages[6]))
                                <button type="button" class="btn p-0 border-0 bg-transparent w-100 text-start guide-image-trigger mt-3" data-guide-image-src="{{ $stepImages[6] }}" data-guide-image-alt="Screenshot menu settings awal di dashboard">
                                    <img src="{{ $stepImages[6] }}" alt="Screenshot menu settings awal di dashboard" class="img-fluid rounded-3 border guide-step-image">
                                </button>
                            @endif
                            @if($isGuideAdmin)
                                <div class="admin-upload-card mt-3">
                                    <div class="small fw-semibold">Kelola gambar Step 6 (admin only)</div>
                                    @if(empty($stepImages[6]))
                                        <div class="img-slot mt-2">[Tempatkan Gambar Di Sini]<br>Screenshot menu settings awal di dashboard</div>
                                    @endif
                                    <form method="post" action="{{ route('guides.operasional-bot.upload-image') }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 mt-2">
                                        @csrf
                                        <input type="hidden" name="step" value="6">
                                        <input type="file" name="step_image" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.webp" required>
                                        <button type="submit" class="btn btn-sm btn-dark">{{ empty($stepImages[6]) ? 'Upload Gambar' : 'Ganti Gambar' }}</button>
                                    </form>
                                </div>
                            @endif
                            <div class="tips-box mt-3">
                                <strong>Tips sukses:</strong> kalau belum paham setting detail, pakai default dulu karena itu baseline paling aman. Setelah hasil monitor stabil, baru lakukan penyesuaian bertahap.
                            </div>
                        </div>
                    </div>
                </section>
            </div>
                    </section>

                    <section class="guide-checklist shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <h2 class="h5">Checklist Akhir Sebelum Live</h2>
                            <ul class="section-list mb-0">
                                <li>Akun website aktif dan bisa login.</li>
                                <li>Billing sudah Paid/Active.</li>
                                <li>Akun MT5 Cent sudah login di terminal yang benar.</li>
                                <li>EA berjalan dan AutoTrading aktif.</li>
                                <li>Dashboard monitor, analysis, dan report update otomatis tanpa refresh manual.</li>
                            </ul>
                        </div>
                    </section>
                </div>

                <div class="tab-pane fade" id="faq-main-pane" role="tabpanel" aria-labelledby="faq-main-tab" tabindex="0">
                    <section class="faq-card shadow-sm p-3 p-lg-4 mb-3">
                        <h2 class="h5 mb-2">FAQ Pengguna Bot Trading</h2>
                        <p class="text-secondary mb-0">Klik pertanyaan untuk membuka jawaban. Supaya ringkas, hanya satu jawaban yang terbuka dalam satu waktu.</p>
                        <div class="safe-mode-box mt-3">
                            <div class="fw-semibold mb-1">Mode Aman (Rekomendasi)</div>
                            <p class="mb-2">Kalau masih baru atau belum nyaman dengan setting detail, pakai default dulu dan fokus ke kestabilan akun.</p>
                            <ul class="section-list mb-2">
                                <li>Utamakan sesi market yang lebih tenang.</li>
                                <li>Kalau tetap trading di jam rawan, pahami bahwa risikonya meningkat.</li>
                                <li>Ubah setting bertahap setelah ada hasil evaluasi yang jelas.</li>
                            </ul>
                            <p class="mb-0">Untuk panduan praktis tanpa ribet, lihat <strong>Panduan Cepat</strong> di menu <strong>Setting</strong> pada dashboard.</p>
                        </div>
                    </section>

                    <div class="accordion" id="faq-accordion-main">
                        <div class="accordion-item faq-card mb-3">
                            <h3 class="accordion-header" id="faq-heading-1">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-1" aria-expanded="false" aria-controls="faq-collapse-1">
                                    1) Apakah bot ini pasti profit 100%?
                                </button>
                            </h3>
                            <div id="faq-collapse-1" class="accordion-collapse collapse" aria-labelledby="faq-heading-1" data-bs-parent="#faq-accordion-main">
                                <div class="accordion-body faq-answer">
                                    <p>Tidak ada bot trading yang bisa menjamin profit 100%. Kalau ada yang janji seperti itu, biasanya terlalu bagus untuk jadi kenyataan.</p>
                                    <p>Bot ini dibuat untuk membantu eksekusi jadi lebih konsisten, cepat, dan disiplin. Tapi hasil tetap dipengaruhi kondisi market, spread, slippage, news besar, dan cara kamu atur risikonya.</p>
                                    <p>Anggap bot sebagai alat bantu, bukan mesin uang tanpa risiko. Fokus yang sehat itu bukan "menang terus", tapi menjaga akun tetap hidup, rugi terkontrol, dan profit bertahap dalam jangka panjang.</p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item faq-card mb-3">
                            <h3 class="accordion-header" id="faq-heading-2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-2" aria-expanded="false" aria-controls="faq-collapse-2">
                                    2) Apa itu akun MT5 Cent dan kenapa harus pakai akun itu, bukan akun standar?
                                </button>
                            </h3>
                            <div id="faq-collapse-2" class="accordion-collapse collapse" aria-labelledby="faq-heading-2" data-bs-parent="#faq-accordion-main">
                                <div class="accordion-body faq-answer">
                                    <p>Akun MT5 Cent adalah jenis akun di mana saldo ditampilkan dalam satuan cent. Jadi pergerakan lot kecil terasa lebih aman untuk latihan atau modal terbatas.</p>
                                    <p>Contoh sederhana: saat akun standar masih terasa terlalu berat untuk risk management, akun cent memberi ruang napas lebih besar karena ukuran transaksinya lebih kecil.</p>
                                    <p>Kenapa direkomendasikan:</p>
                                    <ul class="section-list mb-3">
                                        <li>Lebih ramah untuk pemula dan akun kecil.</li>
                                        <li>Lebih mudah menguji setting tanpa tekanan besar.</li>
                                        <li>Lebih fleksibel untuk strategi bertahap sebelum naik ke akun lebih besar.</li>
                                    </ul>
                                    <p>Intinya bukan soal "lebih hebat", tapi soal kontrol risiko yang lebih aman di fase awal.</p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item faq-card mb-3">
                            <h3 class="accordion-header" id="faq-heading-3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-3" aria-expanded="false" aria-controls="faq-collapse-3">
                                    3) Kenapa bot harus dimatikan atau dihindari saat jam US Session (19.00 - 21.00)? Apa bahayanya News?
                                </button>
                            </h3>
                            <div id="faq-collapse-3" class="accordion-collapse collapse" aria-labelledby="faq-heading-3" data-bs-parent="#faq-accordion-main">
                                <div class="accordion-body faq-answer">
                                    <p>Di jam itu market sering lebih liar, terutama kalau berbarengan dengan news berdampak tinggi. Spread bisa melebar, candle bisa "loncat", dan eksekusi sering kurang ideal.</p>
                                    <p>Risiko yang biasanya muncul:</p>
                                    <ul class="section-list mb-3">
                                        <li>Entry kena harga yang jelek karena slippage.</li>
                                        <li>Layer terbuka terlalu cepat saat candle ekstrem.</li>
                                        <li>Floating mendadak membesar karena gerakan tajam dua arah.</li>
                                    </ul>
                                    <p>Kalau mau tetap dinyalakan di jam ini sebenarnya tetap bisa, tapi kamu harus siap dengan risiko yang lebih tinggi dan sebaiknya turunkan agresivitas setting terlebih dulu.</p>
                                    <p>Jadi ini bukan larangan mutlak, melainkan peringatan manajemen risiko supaya keputusan tetap sadar risiko.</p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item faq-card mb-3">
                            <h3 class="accordion-header" id="faq-heading-4">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-4" aria-expanded="false" aria-controls="faq-collapse-4">
                                    4) Bagaimana kalau modal saya di bawah rekomendasi minimum? Apakah tetap bisa jalan?
                                </button>
                            </h3>
                            <div id="faq-collapse-4" class="accordion-collapse collapse" aria-labelledby="faq-heading-4" data-bs-parent="#faq-accordion-main">
                                <div class="accordion-body faq-answer">
                                    <p>Secara teknis bisa saja jalan, tapi risikonya naik cukup tajam. Ruang napas akun jadi tipis, sehingga gerak market yang normal pun bisa terasa berat.</p>
                                    <p>Kalau modal masih di bawah rekomendasi, pendekatan yang lebih aman:</p>
                                    <ul class="section-list mb-3">
                                        <li>Pakai setting paling konservatif.</li>
                                        <li>Kurangi lot awal dan batasi jumlah layer.</li>
                                        <li>Hindari jam news dan sesi volatil tinggi.</li>
                                        <li>Jangan paksa target harian besar.</li>
                                    </ul>
                                    <p>Tujuan awalnya bukan agresif, tapi bertahan sambil bangun kestabilan akun.</p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item faq-card mb-3">
                            <h3 class="accordion-header" id="faq-heading-5">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-5" aria-expanded="false" aria-controls="faq-collapse-5">
                                    5) Apa yang terjadi jika floating minus menyentuh Max Drawdown?
                                </button>
                            </h3>
                            <div id="faq-collapse-5" class="accordion-collapse collapse" aria-labelledby="faq-heading-5" data-bs-parent="#faq-accordion-main">
                                <div class="accordion-body faq-answer">
                                    <p>Saat floating menyentuh batas Max Drawdown, sistem proteksi akan aktif sesuai logika EA yang dipakai. Umumnya bot akan berhenti membuka posisi baru, dan pada konfigurasi tertentu bisa menutup posisi untuk mencegah kerusakan akun lebih dalam.</p>
                                    <p>Dengan kata lain, Max Drawdown itu "rem darurat". Memang tidak enak saat terpicu, tapi justru itu fungsinya: melindungi akun dari skenario yang lebih buruk.</p>
                                    <p>Yang penting setelah kejadian ini adalah evaluasi setting, bukan langsung balas dendam entry.</p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item faq-card mb-3">
                            <h3 class="accordion-header" id="faq-heading-6">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-6" aria-expanded="false" aria-controls="faq-collapse-6">
                                    6) Bagaimana cara aman menarik keuntungan (withdraw) saat bot masih berjalan?
                                </button>
                            </h3>
                            <div id="faq-collapse-6" class="accordion-collapse collapse" aria-labelledby="faq-heading-6" data-bs-parent="#faq-accordion-main">
                                <div class="accordion-body faq-answer">
                                    <p>Cara paling aman adalah jangan WD saat posisi lagi berat atau floating masih besar. Idealnya WD dilakukan saat exposure ringan dan margin masih lega.</p>
                                    <p>Langkah aman yang disarankan:</p>
                                    <ul class="section-list mb-3">
                                        <li>Pilih momen saat posisi terbuka sedikit atau sudah close cycle.</li>
                                        <li>Sisakan modal kerja yang cukup sesuai setting bot.</li>
                                        <li>Setelah WD, cek ulang margin level dan parameter risiko.</li>
                                        <li>Kalau perlu, turunkan agresivitas setting setelah saldo berkurang.</li>
                                    </ul>
                                    <p>Prinsipnya sederhana: WD boleh, tapi jangan sampai "menguras bensin" yang dibutuhkan bot untuk tetap stabil.</p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item faq-card mb-3">
                            <h3 class="accordion-header" id="faq-heading-7">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-7" aria-expanded="false" aria-controls="faq-collapse-7">
                                    7) Berapa pair ideal untuk dijalankan bersamaan di satu akun?
                                </button>
                            </h3>
                            <div id="faq-collapse-7" class="accordion-collapse collapse" aria-labelledby="faq-heading-7" data-bs-parent="#faq-accordion-main">
                                <div class="accordion-body faq-answer">
                                    <p>Untuk user baru, jangan langsung banyak pair. Mulai dari sedikit pair dulu sampai kamu paham pola floating dan ritme bot di akun kamu.</p>
                                    <p>Setelah stabil, baru tambah bertahap. Lebih sedikit pair biasanya lebih gampang dikontrol daripada banyak pair tapi kewalahan.</p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item faq-card mb-3">
                            <h3 class="accordion-header" id="faq-heading-8">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-8" aria-expanded="false" aria-controls="faq-collapse-8">
                                    8) Seberapa sering saya harus ubah setting bot?
                                </button>
                            </h3>
                            <div id="faq-collapse-8" class="accordion-collapse collapse" aria-labelledby="faq-heading-8" data-bs-parent="#faq-accordion-main">
                                <div class="accordion-body faq-answer">
                                    <p>Tidak perlu sering-sering diutak-atik. Kalau kamu belum benar-benar paham setting detail, pakai saja default yang sudah tersedia.</p>
                                    <p>Default ini disiapkan sebagai baseline paling aman dan stabil untuk mayoritas user, jadi cocok untuk fase awal sambil belajar karakter bot.</p>
                                    <p>Ubah setting hanya jika ada alasan jelas: kondisi market berubah, modal berubah, atau hasil evaluasi memang butuh penyesuaian. Terlalu sering ganti setting justru bikin hasil sulit dievaluasi karena datanya tidak konsisten.</p>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item faq-card mb-4">
                            <h3 class="accordion-header" id="faq-heading-9">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq-collapse-9" aria-expanded="false" aria-controls="faq-collapse-9">
                                    9) Kapan waktu terbaik menjalankan bot supaya lebih aman?
                                </button>
                            </h3>
                            <div id="faq-collapse-9" class="accordion-collapse collapse" aria-labelledby="faq-heading-9" data-bs-parent="#faq-accordion-main">
                                <div class="accordion-body faq-answer">
                                    <p>Fokus ke jam market yang lebih tenang dan hindari periode news besar, terutama saat volatilitas mendadak naik. Prinsipnya: cari kondisi yang lebih stabil, bukan yang paling heboh.</p>
                                    <p>Kalau kamu memutuskan tetap jalan di jam rawan, tetap bisa, tetapi risikonya memang lebih besar. Cara lebih aman: kecilkan lot, batasi layer, dan aktifkan filter proteksi sebelum lanjut trading.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="d-flex gap-2 flex-wrap pb-2">
            @auth
                <a href="{{ route('dashboard.index') }}" class="btn btn-dark">Buka Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-dark">Login</a>
            @endauth
            <a href="{{ url('/') }}" class="btn btn-outline-secondary">Kembali ke Halaman Utama</a>
        </div>
    </main>

    <div class="modal fade" id="guide-image-modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 bg-transparent">
                <div class="d-flex justify-content-end mb-2">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal" aria-label="Close">Close</button>
                </div>
                <div class="modal-body p-0">
                    <img id="guide-image-modal-img" src="" alt="Preview gambar panduan" class="guide-modal-image">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const requestedStep = @json($requestedStep);

            const activateStepFromHash = (hashValue) => {
                if (!hashValue || !hashValue.startsWith('#tab-step-')) {
                    return;
                }

                const pane = document.querySelector(hashValue);
                if (!pane) {
                    return;
                }

                const tabTrigger = document.querySelector(`[data-bs-target="${hashValue}"]`);
                if (!tabTrigger) {
                    return;
                }

                const tab = bootstrap.Tab.getOrCreateInstance(tabTrigger);
                tab.show();
                pane.scrollIntoView({ behavior: 'smooth', block: 'start' });
            };

            const stepFromQuery = Number(new URLSearchParams(window.location.search).get('step'));
            const initialStep = Number.isInteger(stepFromQuery) && stepFromQuery >= 1 && stepFromQuery <= 6
                ? stepFromQuery
                : requestedStep;

            if (window.location.hash) {
                activateStepFromHash(window.location.hash);
            } else {
                activateStepFromHash(`#tab-step-${initialStep}`);
            }

            const modalEl = document.getElementById('guide-image-modal');
            const modalImg = document.getElementById('guide-image-modal-img');
            const imageModal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;

            document.querySelectorAll('.guide-image-trigger').forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    if (!imageModal || !modalImg) {
                        return;
                    }

                    modalImg.src = trigger.getAttribute('data-guide-image-src') || '';
                    modalImg.alt = trigger.getAttribute('data-guide-image-alt') || 'Preview gambar panduan';
                    imageModal.show();
                });
            });

            modalEl?.addEventListener('hidden.bs.modal', () => {
                if (modalImg) {
                    modalImg.src = '';
                }
            });
        });
    </script>
</body>
</html>
