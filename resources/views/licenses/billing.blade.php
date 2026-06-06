<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Billing Lisensi MT5</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.svg') }}">
    <script>
        (function () {
            const saved = localStorage.getItem('ea_dashboard_theme');
            const normalized = saved === 'dark' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', normalized);
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root,
        html[data-theme='light'] {
            --bg: #f8f4ef;
            --card: #ffffff;
            --ink: #1f2937;
            --muted: #6b7280;
            --accent: #b45309;
            --accent-soft: #fed7aa;
            --ok: #166534;
            --warn: #b91c1c;
            --chip-ok-bg: #dcfce7;
            --chip-off-bg: #fee2e2;
            --table-stripe: rgba(17, 24, 39, 0.03);
            --input-bg: #ffffff;
            --input-border: rgba(31, 41, 55, 0.18);
            --input-placeholder: #9ca3af;
            --btn-outline: #1f2937;
            --btn-outline-hover-bg: #1f2937;
            --btn-outline-hover-ink: #f9fafb;
            --shadow: 0 12px 30px rgba(0,0,0,0.07);
        }
        html[data-theme='dark'] {
            --bg: #0b111b;
            --card: #121a27;
            --ink: #e5e7eb;
            --muted: #9ca3af;
            --accent: #f59e0b;
            --accent-soft: #7c2d12;
            --ok: #4ade80;
            --warn: #f87171;
            --chip-ok-bg: rgba(74, 222, 128, 0.2);
            --chip-off-bg: rgba(248, 113, 113, 0.2);
            --table-stripe: rgba(148, 163, 184, 0.08);
            --input-bg: #0f172a;
            --input-border: rgba(148, 163, 184, 0.35);
            --input-placeholder: #6b7280;
            --btn-outline: #cbd5e1;
            --btn-outline-hover-bg: #334155;
            --btn-outline-hover-ink: #f8fafc;
            --shadow: 0 14px 34px rgba(0,0,0,0.35);
        }
        body {
            font-family: 'Space Grotesk', sans-serif;
            background: radial-gradient(circle at top right, #fde68a 0%, transparent 40%), var(--bg);
            color: var(--ink);
            min-height: 100vh;
        }
        html[data-theme='dark'] body {
            background: radial-gradient(circle at top right, rgba(245, 158, 11, 0.18) 0%, transparent 44%), var(--bg);
        }
        .panel { background: var(--card); border-radius: 18px; box-shadow: var(--shadow); }
        .text-secondary { color: var(--muted) !important; }
        .table {
            --bs-table-color: var(--ink);
            --bs-table-bg: transparent;
            --bs-table-border-color: var(--table-stripe);
            --bs-table-striped-bg: transparent;
            --bs-table-hover-bg: transparent;
            color: var(--ink);
            background-color: transparent;
        }
        .table thead th {
            color: var(--ink);
            border-bottom-color: var(--table-stripe);
        }
        .table > :not(caption) > * > * {
            background-color: transparent !important;
            border-bottom-color: var(--table-stripe);
        }
        .form-control,
        .form-select {
            background-color: var(--input-bg);
            border-color: var(--input-border);
            color: var(--ink);
        }
        .form-control::placeholder { color: var(--input-placeholder); }
        .form-control:focus,
        .form-select:focus {
            background-color: var(--input-bg);
            color: var(--ink);
            border-color: var(--accent);
            box-shadow: 0 0 0 0.2rem color-mix(in srgb, var(--accent) 22%, transparent);
        }
        .form-control:disabled,
        .form-select:disabled {
            background-color: color-mix(in srgb, var(--input-bg) 76%, #94a3b8 24%);
            border-color: var(--input-border);
            color: var(--muted);
            opacity: 1;
        }
        html[data-theme='dark'] .form-control:disabled,
        html[data-theme='dark'] .form-select:disabled {
            background-color: rgba(92, 125, 175, 0.24);
            color: rgba(203, 213, 225, 0.82);
            border-color: rgba(148, 163, 184, 0.38);
        }
        .btn-outline-dark {
            color: var(--btn-outline);
            border-color: var(--btn-outline);
        }
        .btn-outline-dark:hover {
            color: var(--btn-outline-hover-ink);
            background-color: var(--btn-outline-hover-bg);
            border-color: var(--btn-outline-hover-bg);
        }
        .license-chip { border-radius: 999px; padding: 4px 12px; font-size: 12px; font-weight: 700; }
        .license-chip.ok { background: var(--chip-ok-bg); color: var(--ok); }
        .license-chip.off { background: var(--chip-off-bg); color: var(--warn); }
        .bank-box {
            border: 1px dashed var(--input-border);
            border-radius: 14px;
            background: color-mix(in srgb, var(--card) 84%, transparent);
            padding: .75rem;
            margin-bottom: .75rem;
        }
        .bank-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: .75rem;
            border-top: 1px solid var(--table-stripe);
            padding-top: .35rem;
            margin-top: .35rem;
        }
        .bank-row:first-of-type {
            border-top: 0;
            padding-top: 0;
            margin-top: 0;
        }
        .bank-label { color: var(--muted); font-size: .82rem; }
        .bank-value { font-weight: 700; letter-spacing: .01em; }
        .support-widget {
            border: 1px solid color-mix(in srgb, var(--accent) 28%, transparent);
            border-radius: 16px;
            background: linear-gradient(135deg, color-mix(in srgb, var(--card) 92%, #fff 8%), color-mix(in srgb, var(--accent-soft) 28%, var(--card) 72%));
            padding: .9rem;
            margin-bottom: .9rem;
        }
        .support-widget-title {
            font-size: .95rem;
            font-weight: 700;
            margin-bottom: .2rem;
        }
        .support-widget-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            margin-top: .8rem;
        }
        .trial-redeem-box {
            border: 1px solid color-mix(in srgb, var(--accent) 28%, transparent);
            border-radius: 14px;
            background: color-mix(in srgb, var(--card) 94%, transparent);
            margin-top: .85rem;
            overflow: visible;
        }
        .trial-redeem-box > summary {
            list-style: none;
            cursor: pointer;
            padding: .78rem .9rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
        }
        .trial-redeem-box > summary::-webkit-details-marker {
            display: none;
        }
        .trial-redeem-box > summary::after {
            content: '+';
            color: var(--accent);
            font-size: 1.05rem;
            line-height: 1;
        }
        .trial-redeem-box[open] > summary::after {
            content: '-';
        }
        .trial-redeem-content {
            border-top: 1px solid color-mix(in srgb, var(--accent) 24%, transparent);
            padding: .82rem .9rem .9rem;
            position: relative;
            z-index: 3;
        }
        .billing-account-picker {
            width: 100%;
            position: relative;
            z-index: 4;
        }
        .billing-account-picker-toggle {
            width: 100%;
            min-height: 42px;
            border-radius: 12px;
            border: 1px solid var(--input-border);
            background: var(--input-bg);
            color: var(--ink);
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.4rem;
            font-size: 0.92rem;
            font-weight: 700;
            padding: 0.55rem 0.78rem;
        }
        .billing-account-picker-toggle:hover {
            border-color: color-mix(in srgb, var(--accent) 52%, var(--input-border));
        }
        .billing-account-picker-toggle:focus,
        .billing-account-picker-toggle.show {
            border-color: var(--accent);
            box-shadow: 0 0 0 0.2rem color-mix(in srgb, var(--accent) 22%, transparent);
        }
        .billing-account-picker-menu {
            width: 100%;
            min-width: 100%;
            padding: 0.55rem;
            border-radius: 12px;
            border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);
            box-shadow: 0 18px 30px rgba(15, 23, 42, 0.24);
            margin-top: 0.3rem;
            z-index: 25;
        }
        .billing-account-picker-search {
            min-height: 38px;
            border-radius: 10px;
            font-size: 0.84rem;
            margin-bottom: 0.5rem;
            border-width: 1px;
            box-shadow: 0 0 0 1px color-mix(in srgb, var(--accent) 24%, transparent);
        }
        .billing-account-picker-options {
            max-height: 260px;
            overflow-y: auto;
            display: grid;
            gap: 0.2rem;
            padding-top: 0.35rem;
            border-top: 1px solid var(--table-stripe);
            border-radius: 10px;
            background: color-mix(in srgb, var(--card) 90%, transparent);
        }
        .billing-account-picker-item {
            border-radius: 9px;
            font-size: 0.84rem;
            white-space: normal;
            line-height: 1.35;
            padding: 0.48rem 0.62rem;
            text-align: left;
            color: var(--ink);
        }
        .billing-account-picker-item:hover {
            background: color-mix(in srgb, var(--accent) 16%, transparent);
            color: var(--ink);
        }
        .billing-account-picker-item.active,
        .billing-account-picker-item:active {
            background: color-mix(in srgb, var(--accent) 24%, transparent);
            color: var(--ink);
        }
        html[data-theme='dark'] .billing-account-picker-menu {
            background: rgba(9, 19, 39, 0.98);
            border-color: color-mix(in srgb, var(--accent) 42%, rgba(148, 163, 184, 0.32));
            box-shadow: 0 16px 30px rgba(2, 6, 23, 0.55);
        }
        html[data-theme='dark'] .billing-account-picker-search {
            background: rgba(9, 19, 39, 0.96);
            border-color: rgba(245, 158, 11, 0.52);
            color: #f8fafc;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.18);
        }
        html[data-theme='dark'] .billing-account-picker-search::placeholder {
            color: rgba(203, 213, 225, 0.66);
        }
        html[data-theme='dark'] .billing-account-picker-options {
            background: rgba(9, 19, 39, 0.72);
            border-top-color: rgba(130, 163, 214, 0.28);
        }
        html[data-theme='dark'] .billing-account-picker-item {
            color: #e2e8f0;
        }
        html[data-theme='dark'] .billing-account-picker-item:hover {
            background: rgba(245, 158, 11, 0.2);
            color: #fff7ed;
        }
        html[data-theme='dark'] .billing-account-picker-item.active,
        html[data-theme='dark'] .billing-account-picker-item:active {
            background: rgba(251, 191, 36, 0.26);
            color: #ffffff;
        }
        .desktop-compact-note {
            font-size: .78rem;
            color: var(--muted);
            margin-top: .35rem;
        }
        .chat-float-btn {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 1030;
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .8rem 1rem;
            border-radius: 999px;
            box-shadow: 0 14px 24px rgba(0,0,0,0.18);
        }
        @media (max-width: 767.98px) {
            .chat-float-btn {
                right: .75rem;
                left: .75rem;
                justify-content: center;
                bottom: .75rem;
            }
        }
        .web-chat-panel {
            border: 1px solid var(--table-stripe);
            border-radius: 16px;
            overflow: hidden;
            display: grid;
            grid-template-rows: auto 1fr auto;
            min-height: 520px;
        }
        .web-chat-head,
        .web-chat-form {
            padding: 1rem;
            border-bottom: 1px solid var(--table-stripe);
        }
        .web-chat-form {
            border-bottom: 0;
            border-top: 1px solid var(--table-stripe);
        }
        .web-chat-messages {
            padding: 1rem;
            display: grid;
            gap: .8rem;
            align-content: start;
            overflow-y: auto;
            max-height: 100%;
        }
        .web-chat-row {
            display: flex;
        }
        .web-chat-row.is-self {
            justify-content: flex-end;
        }
        .web-chat-bubble {
            max-width: min(88%, 520px);
            border-radius: 16px;
            padding: .8rem .9rem;
            background: rgba(148, 163, 184, 0.12);
        }
        .web-chat-row.is-self .web-chat-bubble {
            background: color-mix(in srgb, var(--accent-soft) 34%, var(--card) 66%);
        }
        .web-chat-meta {
            font-size: .72rem;
            color: var(--muted);
            margin-bottom: .35rem;
            font-weight: 700;
        }
        .web-chat-text {
            white-space: pre-wrap;
            line-height: 1.5;
        }
        .web-chat-empty {
            border: 1px dashed var(--table-stripe);
            border-radius: 14px;
            padding: 1rem;
            color: var(--muted);
        }
        .floating-chat-toggle {
            position: fixed;
            right: 1rem;
            bottom: max(.55rem, calc(.55rem + env(safe-area-inset-bottom)));
            z-index: 1060;
            width: 36px;
            height: 36px;
            border-radius: 11px;
            border: 1px solid rgba(217, 119, 6, 0.34);
            background: rgba(255, 251, 235, 0.95);
            color: #d97706;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.22);
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .floating-chat-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.3);
        }
        .floating-chat-unread {
            position: absolute;
            top: -4px;
            right: -4px;
            min-width: 22px;
            height: 22px;
            border-radius: 999px;
            background: #ef4444;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 6px;
            font-size: .72rem;
            font-weight: 700;
            border: 2px solid rgba(255, 255, 255, 0.95);
        }
        .floating-chat-unread.is-hidden {
            display: none;
        }
        .floating-chat-card {
            position: fixed;
            right: 1rem;
            bottom: max(4.9rem, calc(4.9rem + env(safe-area-inset-bottom)));
            z-index: 1060;
            width: min(420px, calc(100vw - 1.5rem));
            height: min(72vh, 560px);
            border: 1px solid var(--table-stripe);
            border-radius: 16px;
            background: var(--card);
            box-shadow: var(--shadow);
            overflow: hidden;
            display: none;
            grid-template-rows: auto 1fr auto;
        }
        .floating-chat-card.is-open {
            display: grid;
        }
        .floating-chat-head,
        .floating-chat-form {
            padding: .85rem .95rem;
            border-bottom: 1px solid var(--table-stripe);
        }
        .floating-chat-form {
            border-bottom: 0;
            border-top: 1px solid var(--table-stripe);
        }
        .floating-chat-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
        }
        .floating-chat-close {
            border: 0;
            background: transparent;
            color: var(--muted);
            font-size: 1.15rem;
            line-height: 1;
            padding: .15rem .35rem;
        }
        .floating-chat-messages {
            padding: .85rem .95rem;
            overflow-y: auto;
            display: grid;
            gap: .7rem;
            align-content: start;
            min-height: 0;
            overscroll-behavior: contain;
            touch-action: pan-y;
            -webkit-overflow-scrolling: touch;
        }
        .floating-chat-row {
            display: flex;
        }
        .floating-chat-row.is-self {
            justify-content: flex-end;
        }
        .floating-chat-bubble {
            max-width: min(90%, 310px);
            border-radius: 14px;
            padding: .65rem .75rem;
            background: rgba(148, 163, 184, 0.12);
        }
        .floating-chat-row.is-self .floating-chat-bubble {
            background: color-mix(in srgb, var(--accent-soft) 35%, var(--card) 65%);
        }
        .floating-chat-meta {
            color: var(--muted);
            font-size: .7rem;
            margin-bottom: .25rem;
            font-weight: 700;
        }
        .floating-chat-text {
            white-space: pre-wrap;
            line-height: 1.45;
            font-size: .92rem;
        }
        .floating-chat-compose {
            position: relative;
        }
        .floating-chat-compose textarea {
            padding-right: 3.2rem;
            min-height: 86px;
            resize: vertical;
        }
        .floating-chat-send-icon {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 11px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 12px 30px rgba(245, 158, 11, 0.35);
            transition: transform .16s ease, box-shadow .16s ease, opacity .16s ease;
            position: absolute;
            right: .55rem;
            bottom: .55rem;
            z-index: 2;
        }
        .floating-chat-send-icon:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 16px 34px rgba(245, 158, 11, 0.44);
        }
        .floating-chat-send-icon:disabled {
            opacity: .55;
            cursor: not-allowed;
            box-shadow: none;
        }
        .floating-chat-send-icon svg {
            width: 16px;
            height: 16px;
        }
        .floating-chat-empty {
            border: 1px dashed var(--table-stripe);
            border-radius: 12px;
            padding: .85rem;
            color: var(--muted);
            font-size: .88rem;
        }
        @media (max-width: 767.98px) {
            .floating-chat-card {
                right: .75rem;
                width: calc(100vw - 1.5rem);
                height: min(70vh, 560px);
            }
            .floating-chat-toggle {
                right: .75rem;
            }
        }
        .floating-chat-icon {
            width: 22px;
            height: 22px;
            display: block;
            transition: opacity .2s ease, transform .2s ease;
        }
        .floating-chat-icon.is-close {
            display: none;
            width: 18px;
            height: 18px;
        }
        html[data-theme='dark'] .floating-chat-toggle,
        body[data-theme='dark'] .floating-chat-toggle {
            color: #fdba74;
            background: rgba(15, 23, 42, 0.88);
            border-color: rgba(251, 146, 60, 0.45);
            box-shadow: 0 9px 20px rgba(2, 6, 23, 0.55);
        }
        .floating-chat-toggle.is-open .floating-chat-icon.is-chat {
            display: none;
        }
        .floating-chat-toggle.is-open .floating-chat-icon.is-close {
            display: block;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
@php
    $billingCfg = $billingConfig ?? [];
    $autoQrisEnabled = (bool) ($billingCfg['auto_qris_enabled'] ?? false);
    $autoVaEnabled = (bool) ($billingCfg['auto_va_enabled'] ?? false);
    $monthlyPrice = max(0, round((float) ($billingCfg['monthly_price'] ?? 0), 2));
    $discount3 = max(0, min(95, (float) ($billingCfg['discount_3_month_pct'] ?? 0)));
    $discount6 = max(0, min(95, (float) ($billingCfg['discount_6_month_pct'] ?? 0)));
    $discount12 = max(0, min(95, (float) ($billingCfg['discount_12_month_pct'] ?? 0)));
    $discount24 = max(0, min(95, (float) ($billingCfg['discount_24_month_pct'] ?? 0)));
    $trialDays = max(1, min(30, (int) ($billingCfg['trial_days'] ?? 3)));
    $contactName = trim((string) ($billingCfg['contact_name'] ?? 'Admin Billing'));
    $contactPhone = preg_replace('/[^0-9+]/', '', trim((string) ($billingCfg['contact_phone'] ?? ''))) ?? '';
    $waPhone = ltrim($contactPhone, '+');
    if (str_starts_with($waPhone, '0')) {
        $waPhone = '62' . substr($waPhone, 1);
    }
    if (!preg_match('/^62[1-9][0-9]{7,13}$/', $waPhone)) {
        $waPhone = '';
    }
    $waMessage = rawurlencode('Halo admin, saya ingin tanya / follow up billing lisensi MT5.');
    $waUrl = $waPhone !== '' ? ('https://wa.me/' . $waPhone . '?text=' . $waMessage) : null;
@endphp
<div class="container py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">Billing Lisensi Per Account MT5</h1>
            <div class="text-secondary">Pantau status lisensi dan ajukan perpanjangan lisensi bulanan.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('guides.operasional-bot') }}" class="btn btn-outline-dark">Panduan Setup</a>
            <a href="{{ route('dashboard.index') }}" class="btn btn-outline-dark">Dashboard</a>
            @php $isAdmin = (bool) (auth()->user()->is_admin || (string)(auth()->user()->role ?? '') === 'admin'); @endphp
            @if($isAdmin)
                <a href="{{ route('licenses.admin.page') }}" class="btn btn-dark">Admin Lisensi</a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="panel p-3 p-lg-4">
                <h2 class="h5 mb-3">Daftar Account & Timer Lisensi</h2>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Account</th><th>Status</th><th>Sisa Waktu</th><th>Plan</th></tr></thead>
                        <tbody>
                        @forelse($accounts as $account)
                            @php $snap = $licenses[$account->account_id] ?? []; @endphp
                            <tr>
                                <td>{{ $account->account_id }}</td>
                                <td>
                                    <span class="license-chip {{ !empty($snap['license_active']) ? 'ok' : 'off' }}">{{ strtoupper((string)($snap['license_status'] ?? 'unlicensed')) }}</span>
                                </td>
                                <td>
                                    <span
                                        class="billing-license-timer"
                                        data-license-remaining-seconds="{{ (int) ($snap['license_remaining_seconds'] ?? 0) }}"
                                        data-license-is-perpetual="{{ !empty($snap['license_is_perpetual']) ? '1' : '0' }}"
                                        data-license-active="{{ !empty($snap['license_active']) ? '1' : '0' }}"
                                    >{{ (string)($snap['license_remaining_text'] ?? 'No license') }}</span>
                                </td>
                                <td>{{ (string)($snap['license_plan_name'] ?? '-') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-secondary">Belum ada account terdaftar.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="panel p-3 p-lg-4">
                <h2 class="h5 mb-3">Ajukan Billing Lisensi</h2>
                <div class="support-widget">
                    <div class="support-widget-title">Butuh bantuan admin?</div>
                    <div class="small text-secondary">Hubungi admin lebih cepat untuk tanya status billing, konfirmasi transfer, atau kendala lisensi.</div>
                    <div class="bank-row mt-3">
                        <span class="bank-label">Kontak Admin</span>
                        <span class="bank-value">{{ $contactName !== '' ? $contactName : 'Admin Billing' }}</span>
                    </div>
                    <div class="bank-row">
                        <span class="bank-label">No Kontak</span>
                        <span class="bank-value">{{ $contactPhone !== '' ? $contactPhone : '-' }}</span>
                    </div>
                    <div class="support-widget-actions">
                        @if($waUrl)
                            <a href="{{ $waUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-success fw-semibold">Live Chat Admin</a>
                        @endif
                    </div>
                </div>
                <div class="bank-box">
                    <div class="fw-semibold mb-1">Pembayaran Transfer (Rekening Pribadi)</div>
                    <div class="bank-row">
                        <span class="bank-label">Bank</span>
                        <span class="bank-value">{{ (string) ($billingCfg['bank_name'] ?? 'BCA') }}</span>
                    </div>
                    <div class="bank-row">
                        <span class="bank-label">Nama Rekening</span>
                        <span class="bank-value">{{ (string) ($billingCfg['bank_account_name'] ?? 'Nama Pemilik Rekening') }}</span>
                    </div>
                    <div class="bank-row">
                        <span class="bank-label">Nomor Rekening</span>
                        <span class="bank-value">{{ (string) ($billingCfg['bank_account_number'] ?? '-') }}</span>
                    </div>
                    <div class="small text-secondary mt-2">{{ (string) ($billingCfg['bank_note'] ?? 'Transfer ke rekening pribadi di atas lalu isi referensi pembayaran.') }}</div>
                </div>
                <form id="billing-request-form" method="post" action="{{ route('licenses.billing.store') }}" class="vstack gap-2" autocomplete="off">
                    @csrf
                    <input type="hidden" name="requested_plan" value="monthly">
                    <input type="hidden" id="billing-account-id" name="account_id" value="{{ old('account_id', '') }}" required>
                    <label class="form-label mb-0">Account ID</label>
                    <div class="dropdown billing-account-picker">
                        <button id="billing-account-picker-toggle" class="btn billing-account-picker-toggle dropdown-toggle" type="button" aria-expanded="false">Pilih account</button>
                        <div class="dropdown-menu billing-account-picker-menu">
                            <input id="billing-account-picker-search" type="text" class="form-control billing-account-picker-search" placeholder="Cari account / owner" aria-label="Cari account billing">
                            <div id="billing-account-picker-options" class="billing-account-picker-options"></div>
                        </div>
                    </div>

                    <label class="form-label mb-0 mt-2">Paket</label>
                    <input type="text" class="form-control billing-plan-input" value="Bulanan" disabled>

                    <div class="small text-secondary mt-2">Harga per bulan: <strong>Rp {{ number_format($monthlyPrice, 2, ',', '.') }}</strong></div>
                    <div class="small text-secondary">Diskon aktif: 3B {{ number_format($discount3, 2, ',', '.') }}% • 6B {{ number_format($discount6, 2, ',', '.') }}% • 12B {{ number_format($discount12, 2, ',', '.') }}% • 24B {{ number_format($discount24, 2, ',', '.') }}%</div>

                    <label class="form-label mb-0 mt-2">Jumlah Bulan</label>
                    <select id="months-input" name="requested_months" class="form-select">
                        <option value="1">1 Bulan</option>
                        <option value="3">3 Bulan</option>
                        <option value="6">6 Bulan</option>
                        <option value="12">12 Bulan</option>
                        <option value="24">24 Bulan</option>
                    </select>
                    <div id="months-discount-hint" class="desktop-compact-note">Pilih durasi untuk melihat diskon dan estimasi total.</div>

                    <label class="form-label mb-0 mt-2">Nominal Transfer</label>
                    <input type="hidden" id="requested-amount" name="requested_amount">
                    <input type="text" id="requested-amount-display" class="form-control" placeholder="Nominal otomatis" readonly>
                    <div id="discount-summary" class="form-text text-secondary">Nominal dihitung otomatis berdasarkan jumlah bulan dan diskon paket (jika ada).</div>

                    <label class="form-label mb-0 mt-2">Metode Pembayaran</label>
                    <select id="payment-method" name="payment_method" class="form-select">
                        <option value="transfer_manual" selected>Transfer Bank (Manual Verifikasi)</option>
                        <option value="qris_auto" @if(!$autoQrisEnabled) disabled @endif>QRIS (Otomatis - Gateway) @if(!$autoQrisEnabled)- Menunggu Aktivasi @endif</option>
                        <option value="va_auto" @if(!$autoVaEnabled) disabled @endif>Virtual Account (Otomatis - Gateway) @if(!$autoVaEnabled)- Menunggu Aktivasi @endif</option>
                    </select>
                    <div class="form-text text-secondary">Pilih metode otomatis jika invoice gateway sudah diaktifkan oleh admin.</div>

                    <label class="form-label mb-0 mt-2">Ref Pembayaran</label>
                    <input type="text" name="payment_reference" class="form-control" placeholder="TRX-ID / catatan transfer">

                    <label class="form-label mb-0 mt-2">Server MT5 (Koneksi Bot)</label>
                    <input type="text" name="mt5_server" class="form-control" placeholder="Contoh: HFMarketsGlobal-Live15" value="" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false">

                    <label class="form-label mb-0 mt-2">Password Master MT5 (Koneksi Bot)</label>
                    <input type="password" name="mt5_password" class="form-control" placeholder="Wajib isi password master untuk interaksi buy/sell" value="" autocomplete="new-password" required>
                    <div class="form-text text-secondary">Gunakan password <strong>master</strong> (bukan investor) agar bot bisa melakukan eksekusi buy/sell.</div>

                    <label class="form-label mb-0 mt-2">Catatan</label>
                    <textarea name="notes" rows="3" class="form-control" placeholder="Kirim info tambahan jika perlu"></textarea>

                    <div class="small text-secondary mt-2">Minimal modal awal yang direkomendasikan untuk penggunaan aman: <strong>Rp 3.000.000</strong>.</div>
                    <div class="form-check mt-1">
                        <input class="form-check-input" type="checkbox" id="billing_tos_ack" name="tos_accepted" value="1" required>
                        <label class="form-check-label small" for="billing_tos_ack">Saya memahami risiko market dan menyetujui <a href="{{ route('legal.risk') }}" target="_blank" rel="noopener noreferrer">Disclaimer Risiko & ToS</a>.</label>
                    </div>

                    <button type="submit" class="btn btn-warning fw-semibold mt-2">Kirim Request Billing</button>
                </form>

                <details class="trial-redeem-box">
                    <summary>Redeem Code Trial</summary>
                    <div class="trial-redeem-content">
                        <div class="small text-secondary">Masukkan redeem code untuk aktivasi trial otomatis {{ $trialDays }} hari.</div>
                        <form id="billing-redeem-form" method="post" action="{{ route('licenses.redeem.store') }}" class="vstack gap-2 mt-2">
                            @csrf
                            <input type="hidden" id="redeem-account-id" name="account_id" value="{{ old('account_id', '') }}" required>
                            <label class="form-label mb-0">Account ID Trial</label>
                            <div class="dropdown billing-account-picker">
                                <button id="redeem-account-picker-toggle" class="btn billing-account-picker-toggle dropdown-toggle" type="button" aria-expanded="false">Pilih account trial</button>
                                <div class="dropdown-menu billing-account-picker-menu">
                                    <input id="redeem-account-picker-search" type="text" class="form-control billing-account-picker-search" placeholder="Cari account / owner" aria-label="Cari account redeem">
                                    <div id="redeem-account-picker-options" class="billing-account-picker-options"></div>
                                </div>
                            </div>
                            <label class="form-label mb-0">Redeem Code</label>
                            <input type="text" name="redeem_code" class="form-control" placeholder="Contoh: TRIAL-AB12CD34" required>
                            <button type="submit" class="btn btn-outline-warning fw-semibold mt-1">Aktifkan Trial</button>
                        </form>
                    </div>
                </details>
            </div>
        </div>
    </div>

    <div class="panel p-3 p-lg-4 mt-3">
        <h2 class="h5 mb-3">Riwayat Billing</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>#</th><th>Account</th><th>Plan</th><th>Bulan</th><th>Nominal</th><th>Status</th><th>MT5 Server</th><th>MT5 Password</th><th>Request At</th><th>Processed At</th><th>Catatan</th></tr></thead>
                <tbody>
                @forelse($billings as $item)
                    @php
                        $rawNotes = trim((string) ($item->notes ?? ''));
                        $safeNotes = trim((string) preg_replace('/^MT5\s+Password\s*:\s*.*$/mi', 'MT5 Password: [MASKED]', $rawNotes));
                    @endphp
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->account_id }}</td>
                        <td>{{ strtoupper($item->requested_plan) }}</td>
                        <td>{{ $item->requested_months }}</td>
                        <td>
                            @if($item->requested_amount !== null)
                                Rp {{ number_format((float) $item->requested_amount, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $item->status === 'approved' ? 'bg-success' : ($item->status === 'rejected' ? 'bg-danger' : 'bg-secondary') }}">
                                {{ strtoupper((string) $item->status) }}
                            </span>
                        </td>
                        <td>{{ trim((string) ($item->mt5_server ?? '')) !== '' ? (string) $item->mt5_server : '-' }}</td>
                        <td>{{ !empty($item->mt5_password_encrypted) ? 'Provided (Encrypted)' : '-' }}</td>
                        <td>{{ optional($item->created_at)->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ optional($item->processed_at)->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ $safeNotes !== '' ? $safeNotes : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-secondary">Belum ada billing request.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel p-3 p-lg-4 mt-3">
        <h2 class="h5 mb-3">Riwayat Redeem Trial</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Kode</th><th>Account</th><th>Trial</th><th>Redeemed At</th></tr></thead>
                <tbody>
                @forelse(($redeemHistory ?? collect()) as $item)
                    <tr>
                        <td>{{ $item->code }}</td>
                        <td>{{ $item->redeemed_account_id ?? '-' }}</td>
                        <td>{{ (int) ($item->trial_days ?? 0) }} hari</td>
                        <td>{{ optional($item->redeemed_at)->format('Y-m-d H:i') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-secondary">Belum ada redeem trial.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<button id="billing-chat-toggle" type="button" class="floating-chat-toggle" aria-label="Open chat">
    <svg class="floating-chat-icon is-chat" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M6.6 18.4L3.8 20l.8-3.1A7.5 7.5 0 1 1 12 20a7.4 7.4 0 0 1-5.4-1.6Z" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M8.5 11.4h7M8.5 8.9h5.4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
    </svg>
    <svg class="floating-chat-icon is-close" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
    </svg>
    <span id="billing-chat-unread" class="floating-chat-unread is-hidden">0</span>
</button>
<div id="billing-chat-card" class="floating-chat-card" aria-live="polite">
    <div class="floating-chat-head">
        <div class="floating-chat-actions">
            <div>
                <div class="fw-semibold">Live Chat Billing</div>
                <div class="small text-secondary">{{ $contactName !== '' ? $contactName : 'Admin Billing' }}</div>
            </div>
            <button id="billing-chat-close" type="button" class="floating-chat-close" aria-label="Close chat">x</button>
        </div>
        <div id="billing-chat-status" class="small text-secondary mt-2">Klik CHAT untuk buka percakapan.</div>
    </div>
    <div id="billing-chat-messages" class="floating-chat-messages"></div>
    <form id="billing-chat-form" class="floating-chat-form">
        <label for="billing-chat-input" class="form-label mb-2">Tulis pesan</label>
        <div class="floating-chat-compose">
            <textarea id="billing-chat-input" class="form-control" rows="3" placeholder="Contoh: Saya sudah transfer untuk account 205018733, mohon dicek ya admin."></textarea>
            <button id="billing-chat-send" type="submit" class="floating-chat-send-icon" aria-label="Kirim pesan">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M5 12h12M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
        <div class="small text-secondary mt-2">Pesan auto refresh.</div>
    </form>
</div>

<script>
const monthlyPrice = {{ number_format($monthlyPrice, 2, '.', '') }};
const billingDiscounts = {
    3: {{ number_format($discount3, 2, '.', '') }},
    6: {{ number_format($discount6, 2, '.', '') }},
    12: {{ number_format($discount12, 2, '.', '') }},
    24: {{ number_format($discount24, 2, '.', '') }},
};
const monthsInput = document.getElementById('months-input');
const requestedAmountInput = document.getElementById('requested-amount');
const requestedAmountDisplay = document.getElementById('requested-amount-display');
const discountSummaryEl = document.getElementById('discount-summary');
const monthsDiscountHintEl = document.getElementById('months-discount-hint');
const billingChatRoutes = {
    thread: @json(route('licenses.chat.thread')),
    unread: @json(route('licenses.chat.unread')),
    send: @json(route('licenses.chat.send')),
};
const billingChatCsrf = @json(csrf_token());
const billingChatState = {
    messages: @json($chatMessages ?? []),
    isOpen: false,
    unreadCount: 0,
    shouldForceScrollBottom: false,
};
const billingAccounts = @json(($accounts ?? collect())->map(function ($account) {
    return [
        'id' => (string) ($account->account_id ?? ''),
        'owner' => (string) (optional($account->user)->name ?? '-'),
    ];
})->values());

function escapeBillingChatHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function escapeBillingPickerHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatBillingLicenseCountdown(totalSeconds) {
    const safeSeconds = Math.max(0, Math.trunc(Number(totalSeconds) || 0));
    const days = Math.floor(safeSeconds / 86400);
    const hours = Math.floor((safeSeconds % 86400) / 3600);
    const minutes = Math.floor((safeSeconds % 3600) / 60);
    const seconds = safeSeconds % 60;

    return String(days) + 'd '
        + String(hours).padStart(2, '0') + 'h '
        + String(minutes).padStart(2, '0') + 'm '
        + String(seconds).padStart(2, '0') + 's';
}

function initBillingLicenseTimers() {
    const timers = Array.from(document.querySelectorAll('.billing-license-timer'));
    if (!timers.length) {
        return;
    }

    const anchors = timers.map((element) => {
        const isPerpetual = element.getAttribute('data-license-is-perpetual') === '1';
        const isActive = element.getAttribute('data-license-active') === '1';
        const remainingSeconds = Math.max(0, Math.trunc(Number(element.getAttribute('data-license-remaining-seconds') || '0')));

        if (isPerpetual) {
            element.textContent = 'Permanent';
            return { element, isPerpetual: true, isActive, remainingSeconds: 0, anchorMs: Date.now() };
        }

        if (!isActive || remainingSeconds <= 0) {
            element.textContent = 'No license';
            return { element, isPerpetual: false, isActive: false, remainingSeconds: 0, anchorMs: Date.now() };
        }

        element.textContent = formatBillingLicenseCountdown(remainingSeconds);
        return { element, isPerpetual: false, isActive: true, remainingSeconds, anchorMs: Date.now() };
    });

    const render = () => {
        const nowMs = Date.now();
        anchors.forEach((timer) => {
            if (timer.isPerpetual) {
                timer.element.textContent = 'Permanent';
                return;
            }

            if (!timer.isActive) {
                timer.element.textContent = 'No license';
                return;
            }

            const elapsed = Math.floor((nowMs - timer.anchorMs) / 1000);
            const currentSeconds = Math.max(0, timer.remainingSeconds - elapsed);
            timer.element.textContent = currentSeconds > 0 ? formatBillingLicenseCountdown(currentSeconds) : 'Expired';
        });
    };

    render();
    window.setInterval(render, 1000);
}

function renderBillingChatMessages(forceScrollBottom = false) {
    const target = document.getElementById('billing-chat-messages');
    if (!target) return;

    const previousBottomDistance = Math.max(0, target.scrollHeight - target.scrollTop - target.clientHeight);
    const shouldScrollToBottom = forceScrollBottom
        || billingChatState.shouldForceScrollBottom
        || previousBottomDistance <= 72;
    billingChatState.shouldForceScrollBottom = false;

    if (!Array.isArray(billingChatState.messages) || billingChatState.messages.length === 0) {
        target.innerHTML = '<div class="floating-chat-empty">Belum ada pesan. Mulai chat untuk tanya billing atau konfirmasi pembayaran ke admin.</div>';
        return;
    }

    target.innerHTML = billingChatState.messages.map((message) => {
        const isSelf = !Boolean(message.sender_is_admin);
        return '<div class="floating-chat-row' + (isSelf ? ' is-self' : '') + '">'
            + '<div class="floating-chat-bubble">'
            + '<div class="floating-chat-meta">' + escapeBillingChatHtml(message.sender_name) + ' • ' + escapeBillingChatHtml(message.created_label) + '</div>'
            + '<div class="floating-chat-text">' + escapeBillingChatHtml(message.message) + '</div>'
            + '</div>'
            + '</div>';
    }).join('');
    if (shouldScrollToBottom) {
        target.scrollTop = target.scrollHeight;
    } else {
        target.scrollTop = Math.max(0, target.scrollHeight - target.clientHeight - previousBottomDistance);
    }
}

function renderBillingChatUnreadBadge() {
    const badge = document.getElementById('billing-chat-unread');
    if (!(badge instanceof HTMLElement)) return;

    const count = Math.max(0, Number(billingChatState.unreadCount || 0));
    if (count <= 0 || billingChatState.isOpen) {
        badge.classList.add('is-hidden');
        badge.textContent = '0';
        return;
    }

    badge.classList.remove('is-hidden');
    badge.textContent = count > 99 ? '99+' : String(count);
}

async function loadBillingChatThread() {
    const statusEl = document.getElementById('billing-chat-status');
    try {
        const response = await fetch(billingChatRoutes.thread, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) {
            if (statusEl) statusEl.textContent = 'Gagal memuat chat (' + String(response.status) + ').';
            return;
        }
        const payload = await response.json();
        billingChatState.messages = Array.isArray(payload.messages) ? payload.messages : [];
        billingChatState.unreadCount = 0;
        billingChatState.shouldForceScrollBottom = true;
        renderBillingChatMessages();
        renderBillingChatUnreadBadge();
        if (statusEl) statusEl.textContent = 'Terhubung. Pesan baru tampil otomatis.';
    } catch (_error) {
        if (statusEl) statusEl.textContent = 'Koneksi chat terputus. Coba lagi...';
    }
}

async function loadBillingChatUnreadCount() {
    if (billingChatState.isOpen) {
        billingChatState.unreadCount = 0;
        renderBillingChatUnreadBadge();
        return;
    }

    try {
        const response = await fetch(billingChatRoutes.unread, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        if (!response.ok) return;

        const payload = await response.json();
        billingChatState.unreadCount = Math.max(0, Number(payload.unread_count || 0));
        renderBillingChatUnreadBadge();
    } catch (_error) {
    }
}

function setBillingChatOpen(open) {
    const card = document.getElementById('billing-chat-card');
    const toggle = document.getElementById('billing-chat-toggle');
    if (!(card instanceof HTMLElement) || !(toggle instanceof HTMLButtonElement)) return;

    billingChatState.isOpen = Boolean(open);
    card.classList.toggle('is-open', billingChatState.isOpen);
    toggle.classList.toggle('is-open', billingChatState.isOpen);
    renderBillingChatUnreadBadge();

    if (billingChatState.isOpen) {
        loadBillingChatThread();
        const input = document.getElementById('billing-chat-input');
        if (input instanceof HTMLTextAreaElement) {
            setTimeout(() => input.focus(), 60);
        }
    }
}

document.getElementById('billing-chat-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const input = document.getElementById('billing-chat-input');
    const sendBtn = document.getElementById('billing-chat-send');
    const statusEl = document.getElementById('billing-chat-status');
    if (!(input instanceof HTMLTextAreaElement) || !(sendBtn instanceof HTMLButtonElement)) return;

    const message = input.value.trim();
    if (!message) return;

    sendBtn.disabled = true;
    if (statusEl) statusEl.textContent = 'Mengirim pesan...';

    let response;
    try {
        response = await fetch(billingChatRoutes.send, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': billingChatCsrf,
            },
            credentials: 'same-origin',
            body: JSON.stringify({ message }),
        });
    } catch (_error) {
        sendBtn.disabled = false;
        if (statusEl) statusEl.textContent = 'Gagal kirim pesan. Cek koneksi lalu coba lagi.';
        return;
    }

    sendBtn.disabled = false;
    if (!response.ok) {
        if (statusEl) statusEl.textContent = 'Gagal kirim pesan (' + String(response.status) + ').';
        return;
    }

    const payload = await response.json();
    billingChatState.messages = Array.isArray(payload.messages) ? payload.messages : billingChatState.messages;
    billingChatState.unreadCount = 0;
    billingChatState.shouldForceScrollBottom = true;
    input.value = '';
    renderBillingChatMessages();
    renderBillingChatUnreadBadge();
    if (statusEl) statusEl.textContent = 'Pesan terkirim.';
});

document.getElementById('billing-chat-toggle')?.addEventListener('click', () => {
    setBillingChatOpen(!billingChatState.isOpen);
});

document.getElementById('billing-chat-close')?.addEventListener('click', () => {
    setBillingChatOpen(false);
});

document.getElementById('billing-chat-input')?.addEventListener('keydown', (event) => {
    if (!(event.target instanceof HTMLTextAreaElement)) return;
    if (event.key !== 'Enter' || event.shiftKey) return;
    event.preventDefault();
    document.getElementById('billing-chat-form')?.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
});

if (monthsInput && requestedAmountInput && requestedAmountDisplay) {
    const rupiahFormatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    });

    const monthLabelMap = {};
    Array.from(monthsInput.options).forEach((option) => {
        const resolvedMonths = Math.max(1, parseInt(option.value || '1', 10));
        monthLabelMap[resolvedMonths] = option.textContent ? option.textContent.split(' - ')[0] : (String(resolvedMonths) + ' Bulan');
    });

    const refreshMonthOptionLabels = () => {
        Array.from(monthsInput.options).forEach((option) => {
            const resolvedMonths = Math.max(1, parseInt(option.value || '1', 10));
            const baseLabel = monthLabelMap[resolvedMonths] || (String(resolvedMonths) + ' Bulan');
            const baseNominal = Math.max(0, resolvedMonths * monthlyPrice);
            const discountPct = Number(billingDiscounts[resolvedMonths] || 0);
            const finalNominal = Math.max(0, Math.round(baseNominal - ((baseNominal * discountPct) / 100)));

            if (discountPct > 0) {
                option.textContent = baseLabel + ' - Diskon ' + discountPct.toFixed(2).replace('.', ',') + '% (Est: ' + rupiahFormatter.format(finalNominal) + ')';
            } else {
                option.textContent = baseLabel + ' - Est: ' + rupiahFormatter.format(finalNominal);
            }
        });
    };

    const syncNominal = () => {
        const months = Math.max(1, parseInt(monthsInput.value || '1', 10));
        const baseNominal = Math.max(0, months * monthlyPrice);
        const discountPct = Number(billingDiscounts[months] || 0);
        const discountNominal = Math.max(0, Math.round((baseNominal * discountPct) / 100));
        const finalNominal = Math.max(0, Math.round(baseNominal - discountNominal));
        requestedAmountInput.value = finalNominal.toFixed(2);
        requestedAmountDisplay.value = rupiahFormatter.format(finalNominal);
        if (monthsDiscountHintEl) {
            if (discountPct > 0) {
                monthsDiscountHintEl.textContent = String(months) + ' bulan: diskon ' + discountPct.toFixed(2).replace('.', ',')
                    + '% (hemat ' + rupiahFormatter.format(discountNominal) + ') • estimasi bayar ' + rupiahFormatter.format(finalNominal) + '.';
            } else {
                monthsDiscountHintEl.textContent = String(months) + ' bulan: tanpa diskon • estimasi bayar ' + rupiahFormatter.format(finalNominal) + '.';
            }
        }
        if (discountSummaryEl) {
            if (discountPct > 0) {
                discountSummaryEl.textContent = 'Diskon ' + discountPct.toFixed(2).replace('.', ',') + '% aktif: harga normal '
                    + rupiahFormatter.format(Math.round(baseNominal)) + ' -> bayar ' + rupiahFormatter.format(finalNominal) + '.';
            } else {
                discountSummaryEl.textContent = 'Tidak ada diskon untuk ' + String(months) + ' bulan. Total bayar: ' + rupiahFormatter.format(finalNominal) + '.';
            }
        }
    };

    refreshMonthOptionLabels();
    monthsInput.addEventListener('change', syncNominal);
    syncNominal();
}

const initBillingAccountPicker = ({
    toggleId,
    searchId,
    optionsId,
    hiddenId,
    formId,
    defaultLabel,
}) => {
    const toggleEl = document.getElementById(toggleId);
    const searchEl = document.getElementById(searchId);
    const optionsEl = document.getElementById(optionsId);
    const hiddenEl = document.getElementById(hiddenId);
    const formEl = document.getElementById(formId);
    if (!(toggleEl instanceof HTMLElement) || !(searchEl instanceof HTMLInputElement) || !(optionsEl instanceof HTMLElement) || !(hiddenEl instanceof HTMLInputElement)) {
        return;
    }

    const menuEl = toggleEl.nextElementSibling;
    if (!(menuEl instanceof HTMLElement)) {
        return;
    }

    const isOpen = () => menuEl.classList.contains('show');
    const openMenu = () => {
        menuEl.classList.add('show');
        toggleEl.classList.add('show');
        toggleEl.setAttribute('aria-expanded', 'true');
        renderOptions();
        setTimeout(() => searchEl.focus(), 0);
    };
    const closeMenu = () => {
        menuEl.classList.remove('show');
        toggleEl.classList.remove('show');
        toggleEl.setAttribute('aria-expanded', 'false');
        searchEl.value = '';
    };

    const getLabel = (id) => {
        const normalized = String(id || '').trim();
        if (!normalized) return String(defaultLabel || 'Pilih account');
        const found = billingAccounts.find((item) => String(item?.id || '') === normalized);
        if (!found) return normalized;
        return String(found.owner || '').trim() !== '' && String(found.owner || '-') !== '-'
            ? normalized + ' - ' + String(found.owner)
            : normalized;
    };

    const renderOptions = () => {
        const keyword = String(searchEl.value || '').trim().toLowerCase();
        const selectedId = String(hiddenEl.value || '').trim();
        const filtered = billingAccounts.filter((item) => {
            const id = String(item?.id || '').toLowerCase();
            const owner = String(item?.owner || '').toLowerCase();
            if (keyword === '') return true;
            return id.includes(keyword) || owner.includes(keyword);
        });

        if (!filtered.length) {
            optionsEl.innerHTML = '<div class="small text-secondary px-2 py-1">Tidak ada account yang cocok.</div>';
            return;
        }

        optionsEl.innerHTML = filtered.map((item) => {
            const id = String(item?.id || '').trim();
            const owner = String(item?.owner || '-').trim() || '-';
            const label = owner !== '-' ? (id + ' - ' + owner) : id;
            const isActive = id !== '' && id === selectedId;
            return '<button type="button" class="dropdown-item billing-account-picker-item' + (isActive ? ' active' : '') + '" data-account-id="' + escapeBillingPickerHtml(id) + '">' + escapeBillingPickerHtml(label) + '</button>';
        }).join('');
    };

    const syncToggle = () => {
        const label = getLabel(hiddenEl.value);
        toggleEl.textContent = label;
        toggleEl.setAttribute('title', label);
    };

    searchEl.addEventListener('input', renderOptions);
    toggleEl.addEventListener('click', (event) => {
        event.preventDefault();
        if (isOpen()) {
            closeMenu();
            return;
        }
        openMenu();
    });

    optionsEl.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;
        const button = target.closest('[data-account-id]');
        if (!(button instanceof HTMLElement)) return;
        const selectedId = String(button.getAttribute('data-account-id') || '').trim();
        if (!selectedId) return;

        hiddenEl.value = selectedId;
        syncToggle();
        renderOptions();
        closeMenu();
    });

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (!(target instanceof Node)) return;
        if (toggleEl.contains(target) || menuEl.contains(target)) return;
        if (isOpen()) closeMenu();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        if (!isOpen()) return;
        closeMenu();
        toggleEl.focus();
    });

    if (formEl instanceof HTMLFormElement) {
        formEl.addEventListener('submit', (event) => {
            if (String(hiddenEl.value || '').trim() !== '') return;
            event.preventDefault();
            openMenu();
        });
    }

    syncToggle();
    renderOptions();
};

initBillingAccountPicker({
    toggleId: 'billing-account-picker-toggle',
    searchId: 'billing-account-picker-search',
    optionsId: 'billing-account-picker-options',
    hiddenId: 'billing-account-id',
    formId: 'billing-request-form',
    defaultLabel: 'Pilih account',
});

initBillingAccountPicker({
    toggleId: 'redeem-account-picker-toggle',
    searchId: 'redeem-account-picker-search',
    optionsId: 'redeem-account-picker-options',
    hiddenId: 'redeem-account-id',
    formId: 'billing-redeem-form',
    defaultLabel: 'Pilih account trial',
});

initBillingLicenseTimers();

renderBillingChatMessages();
renderBillingChatUnreadBadge();
loadBillingChatUnreadCount();
setInterval(() => {
    if (billingChatState.isOpen) {
        loadBillingChatThread();
    } else {
        loadBillingChatUnreadCount();
    }
}, 7000);

document.body.setAttribute('data-theme', document.documentElement.getAttribute('data-theme') || 'light');
</script>
</body>
</html>
