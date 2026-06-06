<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Disclaimer Risiko & Terms of Service</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-4 py-lg-5" style="max-width: 880px;">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body p-4 p-lg-5">
                <h1 class="h3 mb-3">Disclaimer Risiko & Terms of Service</h1>
                <p class="text-secondary mb-4">Dokumen ini wajib dibaca sebelum aktivasi bot trading.</p>

                <h2 class="h5">1. Disclaimer Risiko</h2>
                <p>Pergerakan market finansial bersifat dinamis dan tidak menentu. Tidak ada jaminan profit pasti setiap hari. Semua keputusan penggunaan tetap berada pada pengguna.</p>

                <h2 class="h5 mt-4">2. Pengaturan Default Sistem</h2>
                <p>Parameter default pada sistem dirancang dan diuji untuk kestabilan operasional. Hasil trading tetap dipengaruhi kondisi market dan manajemen modal pengguna.</p>

                <h2 class="h5 mt-4">3. Tanggung Jawab Pengguna</h2>
                <p>Jika pengguna mengubah parameter secara mandiri lalu menyebabkan kerugian, termasuk Margin Call (MC), seluruh risiko dan konsekuensi menjadi tanggung jawab penuh pengguna.</p>

                <h2 class="h5 mt-4">4. Batas Minimal Modal</h2>
                <p>Untuk penggunaan yang lebih aman, modal awal minimum yang direkomendasikan adalah <strong>Rp 3.000.000</strong>.</p>

                <h2 class="h5 mt-4">5. Batas Tanggung Jawab Penyedia</h2>
                <p>Dengan menyetujui ToS ini, pengguna menyatakan tidak menyalahkan pihak penyedia sistem atas hasil trading akibat perubahan parameter manual maupun risiko market.</p>

                <div class="mt-4 d-flex gap-2 flex-wrap">
                    <a href="{{ url('/') }}" class="btn btn-outline-secondary">Kembali ke Halaman Utama</a>
                    @auth
                        <a href="{{ route('dashboard.index') }}" class="btn btn-dark">Kembali ke Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-dark">Login</a>
                    @endauth
                </div>
            </div>
        </div>
    </main>
</body>
</html>
