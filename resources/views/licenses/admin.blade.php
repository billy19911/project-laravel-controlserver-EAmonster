<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Lisensi Account MT5</title>
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
            --bg: #eef2ff;
            --bg-end: #f8fafc;
            --ink: #111827;
            --muted: #6b7280;
            --panel: #ffffff;
            --panel-shadow: 0 14px 32px rgba(15, 23, 42, 0.08);
            --table-border: rgba(17, 24, 39, 0.12);
            --table-head: #1f2937;
            --input-bg: #ffffff;
            --input-border: rgba(31, 41, 55, 0.18);
            --input-placeholder: #9ca3af;
            --outline-btn: #1f2937;
            --outline-btn-hover-bg: #1f2937;
            --outline-btn-hover-ink: #f9fafb;
        }
        html[data-theme='dark'] {
            --bg: #060d1a;
            --bg-end: #0b1220;
            --ink: #e5e7eb;
            --muted: #9ca3af;
            --panel: #111a2a;
            --panel-shadow: 0 16px 34px rgba(0, 0, 0, 0.35);
            --table-border: rgba(148, 163, 184, 0.24);
            --table-head: #cbd5e1;
            --input-bg: #0f172a;
            --input-border: rgba(148, 163, 184, 0.32);
            --input-placeholder: #6b7280;
            --outline-btn: #cbd5e1;
            --outline-btn-hover-bg: #334155;
            --outline-btn-hover-ink: #f8fafc;
        }
        body {
            font-family: 'Space Grotesk', sans-serif;
            background: linear-gradient(180deg, var(--bg), var(--bg-end) 45%);
            color: var(--ink);
            min-height: 100vh;
        }
        .admin-license-container {
            width: min(100%, 1680px);
            margin-inline: auto;
            padding-inline: clamp(0.7rem, 2vw, 1.9rem);
        }
        .panel {
            background: var(--panel);
            border-radius: 18px;
            box-shadow: var(--panel-shadow);
        }
        .text-secondary,
        .small.text-secondary {
            color: var(--muted) !important;
        }
        .table {
            --bs-table-color: var(--ink);
            --bs-table-bg: transparent;
            --bs-table-border-color: var(--table-border);
            --bs-table-striped-bg: transparent;
            --bs-table-hover-bg: transparent;
        }
        .table thead th {
            color: var(--table-head);
            font-weight: 700;
            border-bottom-color: var(--table-border);
        }
        .table > :not(caption) > * > * {
            background-color: transparent;
            border-bottom-color: var(--table-border);
        }
        .form-control,
        .form-select {
            background-color: var(--input-bg);
            border-color: var(--input-border);
            color: var(--ink);
        }
        .form-control::placeholder {
            color: var(--input-placeholder);
        }
        .form-control:focus,
        .form-select:focus {
            background-color: var(--input-bg);
            color: var(--ink);
            border-color: #f59e0b;
            box-shadow: 0 0 0 0.2rem color-mix(in srgb, #f59e0b 22%, transparent);
        }
        .form-control:disabled,
        .form-select:disabled {
            background-color: color-mix(in srgb, var(--input-bg) 76%, #94a3b8 24%);
            border-color: var(--input-border);
            color: var(--muted);
            opacity: 1;
        }
        .admin-license-account-picker {
            width: 100%;
            position: relative;
        }
        .admin-license-account-picker-toggle {
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
        .admin-license-account-picker-toggle:hover {
            border-color: color-mix(in srgb, #3b82f6 46%, var(--input-border));
        }
        .admin-license-account-picker-toggle:focus,
        .admin-license-account-picker-toggle.show {
            border-color: #f59e0b;
            box-shadow: 0 0 0 0.2rem color-mix(in srgb, #f59e0b 22%, transparent);
        }
        .admin-license-account-picker-menu {
            width: 100%;
            min-width: 100%;
            padding: 0.55rem;
            border-radius: 12px;
            border: 1px solid rgba(96, 165, 250, 0.32);
            box-shadow: 0 20px 34px rgba(2, 6, 23, 0.32);
        }
        .admin-license-account-picker-search {
            min-height: 38px;
            border-radius: 10px;
            font-size: 0.84rem;
            margin-bottom: 0.5rem;
            border-width: 1px;
            box-shadow: 0 0 0 1px rgba(37, 99, 235, 0.2);
        }
        .admin-license-account-picker-options {
            max-height: 260px;
            overflow-y: auto;
            display: grid;
            gap: 0.2rem;
            padding-top: 0.35rem;
            border-top: 1px solid rgba(148, 163, 184, 0.24);
            border-radius: 10px;
            background: color-mix(in srgb, var(--panel) 84%, transparent);
        }
        .admin-license-account-picker-item {
            border-radius: 9px;
            font-size: 0.84rem;
            white-space: normal;
            line-height: 1.35;
            padding: 0.48rem 0.62rem;
            text-align: left;
            color: #111827;
        }
        .admin-license-account-picker-item-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.55rem;
            width: 100%;
        }
        .admin-license-account-picker-item-label {
            min-width: 0;
            overflow-wrap: anywhere;
        }
        .admin-license-account-picker-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 0.16rem 0.5rem;
            font-size: 0.64rem;
            font-weight: 700;
            line-height: 1;
            letter-spacing: 0.02em;
            border: 1px solid transparent;
            white-space: nowrap;
        }
        .admin-license-account-picker-badge.is-active {
            color: #166534;
            background: rgba(34, 197, 94, 0.18);
            border-color: rgba(34, 197, 94, 0.38);
        }
        .admin-license-account-picker-badge.is-off {
            color: #92400e;
            background: rgba(245, 158, 11, 0.18);
            border-color: rgba(245, 158, 11, 0.4);
        }
        .admin-license-account-picker-badge.is-expired {
            color: #b91c1c;
            background: rgba(239, 68, 68, 0.16);
            border-color: rgba(239, 68, 68, 0.4);
        }
        .admin-license-account-picker-item:hover {
            background: rgba(37, 99, 235, 0.12);
            color: #0f172a;
        }
        .admin-license-account-picker-item.active,
        .admin-license-account-picker-item:active {
            background: rgba(37, 99, 235, 0.18);
            color: #0f172a;
        }
        .license-admin-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .license-admin-toolbar .form-control {
            min-width: min(100%, 320px);
        }
        .license-admin-pagination {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: center;
            justify-content: space-between;
            margin: 0.25rem 0 0.85rem;
        }
        .license-admin-page-size {
            width: auto;
            min-width: 92px;
        }
        .license-admin-page-nav {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .license-admin-page-btn {
            width: 2.1rem;
            height: 2.1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border-radius: 999px;
        }
        .license-admin-page-btn svg {
            width: 1rem;
            height: 1rem;
        }
        .license-admin-owner-flag {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            margin-left: 0.45rem;
            flex-wrap: wrap;
        }
        .license-admin-row-hidden {
            display: none;
        }
        .license-admin-table-wrap {
            overflow-x: hidden;
        }
        .license-admin-table {
            width: 100%;
            min-width: 0;
            table-layout: fixed;
        }
        .license-admin-table th,
        .license-admin-table td {
            vertical-align: top;
            overflow-wrap: anywhere;
            word-break: break-word;
            padding: 0.55rem 0.5rem;
            font-size: 0.85rem;
        }
        .license-admin-account-cell {
            white-space: nowrap;
            width: 14%;
        }
        .license-admin-owner-cell {
            width: 26%;
        }
        .license-admin-plan-cell {
            width: 14%;
        }
        .license-admin-expires-cell {
            width: 16%;
        }
        .license-admin-actions-cell {
            width: 26%;
        }
        .license-admin-actions {
            display: grid;
            gap: 0.45rem;
            justify-items: stretch;
        }
        .license-admin-reassign-form {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .license-admin-reassign-form .form-select {
            min-width: 0;
        }
        .license-admin-icon-btn {
            width: 2.1rem;
            height: 2.1rem;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            flex: 0 0 auto;
        }
        .license-admin-icon-btn svg {
            width: 1rem;
            height: 1rem;
        }
        .license-admin-icon-btn.is-danger {
            color: #dc2626;
        }
        .license-admin-icon-btn.is-danger:hover {
            color: #fff;
        }
        .license-admin-icon-btn.is-warning {
            color: #d97706;
        }
        .license-admin-icon-btn.is-warning:hover {
            color: #fff;
        }
        .license-admin-icon-btn.is-primary {
            color: #2563eb;
        }
        .license-admin-icon-btn.is-primary:hover {
            color: #fff;
        }
        .license-admin-icon-btn.is-primary:hover,
        .license-admin-icon-btn.is-warning:hover,
        .license-admin-icon-btn.is-danger:hover {
            box-shadow: none;
        }
        .license-admin-actions form {
            margin: 0;
        }
        html[data-theme='dark'] .admin-license-account-picker-menu,
        body[data-theme='dark'] .admin-license-account-picker-menu {
            background: rgba(7, 15, 30, 0.98);
            border-color: rgba(96, 165, 250, 0.38);
            box-shadow: 0 16px 30px rgba(2, 6, 23, 0.55);
        }
        html[data-theme='dark'] .admin-license-account-picker-search,
        body[data-theme='dark'] .admin-license-account-picker-search {
            background: rgba(9, 19, 39, 0.96);
            border-color: rgba(89, 156, 255, 0.58);
            color: #e2ecff;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }
        html[data-theme='dark'] .admin-license-account-picker-search::placeholder,
        body[data-theme='dark'] .admin-license-account-picker-search::placeholder {
            color: rgba(179, 203, 242, 0.68);
        }
        html[data-theme='dark'] .admin-license-account-picker-options,
        body[data-theme='dark'] .admin-license-account-picker-options {
            border-top-color: rgba(130, 163, 214, 0.32);
            background: rgba(9, 19, 39, 0.72);
        }
        html[data-theme='dark'] .admin-license-account-picker-item,
        body[data-theme='dark'] .admin-license-account-picker-item {
            color: #dbeafe;
        }
        html[data-theme='dark'] .admin-license-account-picker-item:hover,
        body[data-theme='dark'] .admin-license-account-picker-item:hover {
            background: rgba(59, 130, 246, 0.2);
            color: #f8fafc;
        }
        html[data-theme='dark'] .admin-license-account-picker-item.active,
        html[data-theme='dark'] .admin-license-account-picker-item:active,
        body[data-theme='dark'] .admin-license-account-picker-item.active,
        body[data-theme='dark'] .admin-license-account-picker-item:active {
            background: rgba(147, 197, 253, 0.24);
            color: #ffffff;
        }
        html[data-theme='dark'] .admin-license-account-picker-badge.is-active,
        body[data-theme='dark'] .admin-license-account-picker-badge.is-active {
            color: #bbf7d0;
            background: rgba(34, 197, 94, 0.22);
            border-color: rgba(74, 222, 128, 0.42);
        }
        html[data-theme='dark'] .admin-license-account-picker-badge.is-off,
        body[data-theme='dark'] .admin-license-account-picker-badge.is-off {
            color: #fcd34d;
            background: rgba(245, 158, 11, 0.2);
            border-color: rgba(251, 191, 36, 0.4);
        }
        html[data-theme='dark'] .admin-license-account-picker-badge.is-expired,
        body[data-theme='dark'] .admin-license-account-picker-badge.is-expired {
            color: #fecaca;
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(248, 113, 113, 0.45);
        }
        html[data-theme='dark'] .form-control:disabled,
        html[data-theme='dark'] .form-select:disabled {
            background-color: rgba(92, 125, 175, 0.24);
            color: rgba(203, 213, 225, 0.82);
            border-color: rgba(148, 163, 184, 0.38);
        }
        .btn-outline-dark {
            color: var(--outline-btn);
            border-color: var(--outline-btn);
        }
        .btn-outline-dark:hover {
            color: var(--outline-btn-hover-ink);
            background-color: var(--outline-btn-hover-bg);
            border-color: var(--outline-btn-hover-bg);
        }
        html[data-theme='dark'] .badge.bg-secondary {
            background-color: #334155 !important;
        }
        .billing-alert-widget {
            border: 1px solid rgba(245, 158, 11, 0.28);
            background: linear-gradient(135deg, rgba(255, 251, 235, 0.96), rgba(254, 243, 199, 0.92));
            border-radius: 16px;
            padding: 1rem 1.1rem;
        }
        .billing-alert-count {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
            color: #b45309;
        }
        .admin-tabs-nav {
            position: sticky;
            top: .45rem;
            z-index: 30;
            border: 1px solid var(--table-border);
            border-radius: 14px;
            background: color-mix(in srgb, var(--panel) 90%, transparent);
            backdrop-filter: blur(8px);
            margin-bottom: 1rem;
            overflow-x: auto;
            display: flex;
            gap: .35rem;
            padding: .45rem;
        }
        .admin-tab-btn {
            border: 1px solid var(--table-border);
            background: color-mix(in srgb, var(--panel) 92%, transparent);
            color: var(--muted);
            border-radius: 10px;
            padding: .48rem .82rem;
            font-size: .84rem;
            font-weight: 700;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            transition: transform .15s ease, border-color .15s ease, background-color .15s ease;
        }
        .admin-tab-btn:hover {
            transform: translateY(-1px);
            border-color: rgba(59, 130, 246, .45);
        }
        .admin-tab-btn.is-active {
            color: #fff;
            border-color: rgba(37, 99, 235, .6);
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
        }
        .admin-tab-badge {
            min-width: 22px;
            height: 22px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .68rem;
            font-weight: 700;
            padding: 0 .42rem;
            color: #1e40af;
            background: linear-gradient(145deg, #e0edff 0%, #bfdbfe 100%);
            border: 1px solid rgba(96, 165, 250, .58);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65), 0 4px 12px rgba(37, 99, 235, 0.2);
        }
        .admin-tab-btn.is-active .admin-tab-badge {
            color: #1e3a8a;
            background: linear-gradient(145deg, #ffffff 0%, #dbeafe 100%);
            border-color: rgba(191, 219, 254, .92);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9), 0 5px 14px rgba(14, 60, 150, 0.24);
        }
        html[data-theme='dark'] .admin-tab-badge,
        body[data-theme='dark'] .admin-tab-badge {
            color: #dbeafe;
            background: linear-gradient(145deg, rgba(37, 99, 235, 0.38) 0%, rgba(14, 116, 144, 0.5) 100%);
            border-color: rgba(125, 211, 252, .48);
            box-shadow: inset 0 1px 0 rgba(191, 219, 254, 0.14), 0 6px 14px rgba(2, 132, 199, 0.26);
        }
        html[data-theme='dark'] .admin-tab-btn.is-active .admin-tab-badge,
        body[data-theme='dark'] .admin-tab-btn.is-active .admin-tab-badge {
            color: #e0f2fe;
            background: linear-gradient(145deg, rgba(56, 189, 248, 0.34) 0%, rgba(37, 99, 235, 0.5) 100%);
            border-color: rgba(186, 230, 253, .62);
        }
        .admin-tabs-bottom {
            position: fixed;
            left: 50%;
            transform: translateX(-50%);
            bottom: max(.5rem, calc(.5rem + env(safe-area-inset-bottom)));
            z-index: 40;
            width: min(760px, calc(100vw - 1rem));
            border: 1px solid var(--table-border);
            border-radius: 14px;
            background: color-mix(in srgb, var(--panel) 88%, transparent);
            backdrop-filter: blur(10px);
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.34);
            display: none;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .35rem;
            padding: .4rem;
        }
        .admin-tabs-bottom .admin-tab-btn {
            justify-content: center;
            position: relative;
            overflow: visible;
            padding: .52rem;
            min-height: 42px;
            border-radius: 12px;
            border-color: color-mix(in srgb, var(--table-border) 78%, transparent);
            background: color-mix(in srgb, var(--panel) 84%, transparent);
            gap: 0;
        }
        .admin-tabs-bottom .admin-tab-icon {
            width: 18px;
            height: 18px;
            display: block;
        }
        .admin-tabs-bottom .admin-tab-btn .admin-tab-label {
            display: none;
        }
        .admin-tabs-bottom .admin-tab-btn.is-active {
            background: linear-gradient(135deg, rgba(37, 99, 235, .9), rgba(37, 99, 235, .75));
            border-color: rgba(96, 165, 250, .68);
            box-shadow: 0 10px 24px rgba(37, 99, 235, .32);
        }
        .admin-tabs-bottom .admin-tab-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 19px;
            height: 19px;
            padding: 0 .3rem;
            border-radius: 999px;
            font-size: .62rem;
            font-weight: 700;
            color: #f8fbff;
            background: linear-gradient(135deg, #22d3ee 0%, #2563eb 58%, #1d4ed8 100%);
            border: 1px solid rgba(191, 219, 254, .6);
            box-shadow: 0 6px 16px rgba(37, 99, 235, .42), 0 0 0 2px color-mix(in srgb, var(--panel) 88%, transparent);
        }
        .admin-tabs-bottom .admin-tab-btn.is-active .admin-tab-badge {
            background: linear-gradient(135deg, #38bdf8 0%, #3b82f6 56%, #1d4ed8 100%);
            border-color: rgba(224, 242, 254, .78);
        }
        html[data-theme='dark'] .admin-tabs-bottom .admin-tab-badge,
        body[data-theme='dark'] .admin-tabs-bottom .admin-tab-badge {
            color: #e0f2fe;
            border-color: rgba(125, 211, 252, .62);
            box-shadow: 0 8px 18px rgba(2, 132, 199, .45), 0 0 0 2px rgba(15, 23, 42, .72);
        }
        .admin-tabs-bottom .admin-tab-badge.is-hidden {
            display: none;
        }
        @media (max-width: 991.98px) {
            body {
                padding-bottom: 5.2rem;
            }
            .admin-tabs-nav {
                display: none;
            }
            .admin-tabs-bottom {
                display: grid;
            }
        }
        @media (max-width: 767.98px) {
            .admin-tabs-bottom {
                left: .4rem;
                right: .4rem;
                width: auto;
                transform: none;
            }
            .admin-tabs-bottom .admin-tab-btn {
                min-height: 40px;
            }
            .admin-license-container {
                padding-inline: 0.4rem;
            }
            .panel {
                border-radius: 14px;
            }
            .license-admin-table-wrap {
                overflow: visible;
            }
            .license-admin-table {
                table-layout: auto;
            }
            .license-admin-table thead {
                display: none;
            }
            .license-admin-table,
            .license-admin-table tbody,
            .license-admin-table tr,
            .license-admin-table td {
                display: block;
                width: 100%;
            }
            .license-admin-table tr {
                border: 1px solid var(--table-border);
                border-radius: 12px;
                padding: 0.65rem;
                margin-bottom: 0.7rem;
            }
            .license-admin-table tr.license-admin-row-hidden {
                display: none !important;
            }
            .license-admin-table td {
                border: 0;
                padding: 0.3rem 0;
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 0.75rem;
                font-size: 0.84rem;
            }
            .license-admin-table td::before {
                content: attr(data-label);
                font-weight: 700;
                color: var(--muted);
                min-width: 76px;
                flex: 0 0 auto;
            }
            .license-admin-actions-cell {
                text-align: left !important;
            }
            .license-admin-actions {
                width: 100%;
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 0.5rem;
            }
            .license-admin-reassign-form {
                display: contents;
            }
            .license-admin-reassign-form .form-select {
                grid-column: 1 / -1;
                width: 100%;
            }
            .license-admin-actions > form {
                width: auto;
            }
            .license-admin-actions .license-admin-icon-btn {
                width: 100%;
                min-height: 2.2rem;
                border-radius: 10px;
                justify-content: center;
                padding: 0.4rem 0.35rem;
                gap: 0.3rem;
            }
            .license-admin-actions .license-admin-icon-btn::after {
                content: attr(aria-label);
                font-size: 0.72rem;
                font-weight: 700;
                line-height: 1;
            }
            .license-admin-actions .license-admin-icon-btn.is-primary {
                background: rgba(37, 99, 235, 0.12);
                border-color: rgba(59, 130, 246, 0.55);
            }
            .license-admin-actions .license-admin-icon-btn.is-danger {
                background: rgba(220, 38, 38, 0.11);
                border-color: rgba(248, 113, 113, 0.55);
            }
            .license-admin-actions .license-admin-icon-btn.is-warning {
                background: rgba(217, 119, 6, 0.12);
                border-color: rgba(251, 191, 36, 0.55);
            }
            .license-admin-actions .license-admin-icon-btn svg {
                width: 0.88rem;
                height: 0.88rem;
            }
        }
        .admin-tab-pane {
            display: none;
        }
        .admin-tab-pane.is-active {
            display: block;
        }
        html[data-theme='dark'] .billing-alert-widget {
            border-color: rgba(245, 158, 11, 0.24);
            background: linear-gradient(135deg, rgba(69, 39, 5, 0.9), rgba(120, 53, 15, 0.72));
        }
        html[data-theme='dark'] .billing-alert-count {
            color: #fbbf24;
        }
        .chat-thread-list {
            display: grid;
            gap: 0.75rem;
            max-height: 560px;
            overflow-y: auto;
        }
        .chat-thread-item {
            border: 1px solid var(--table-border);
            border-radius: 14px;
            background: color-mix(in srgb, var(--panel) 92%, transparent);
            padding: 0.85rem 0.9rem;
            text-align: left;
            width: 100%;
        }
        .chat-thread-item.is-active {
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.14);
        }
        .chat-thread-top {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            align-items: flex-start;
        }
        .chat-thread-name {
            font-weight: 700;
        }
        .chat-thread-preview {
            color: var(--muted);
            font-size: 0.84rem;
            line-height: 1.4;
            margin-top: 0.4rem;
        }
        .chat-thread-badges {
            display: flex;
            gap: 0.4rem;
            flex-wrap: wrap;
            margin-top: 0.55rem;
        }
        .chat-thread-badge {
            border-radius: 999px;
            padding: 0.16rem 0.5rem;
            font-size: 0.72rem;
            font-weight: 700;
            background: rgba(59, 130, 246, 0.12);
            color: #1d4ed8;
        }
        .chat-thread-badge.is-alert {
            background: rgba(245, 158, 11, 0.16);
            color: #b45309;
        }
        .chat-window {
            border: 1px solid var(--table-border);
            border-radius: 16px;
            background: color-mix(in srgb, var(--panel) 96%, transparent);
            min-height: 560px;
            display: grid;
            grid-template-rows: auto 1fr auto;
        }
        .chat-window-head,
        .chat-window-form {
            padding: 1rem;
            border-bottom: 1px solid var(--table-border);
        }
        .chat-window-form {
            border-bottom: 0;
            border-top: 1px solid var(--table-border);
        }
        .chat-messages {
            padding: 1rem;
            display: grid;
            gap: 0.85rem;
            align-content: start;
            max-height: 100%;
            overflow-y: auto;
            overscroll-behavior: contain;
            touch-action: pan-y;
            -webkit-overflow-scrolling: touch;
        }
        .chat-message-row {
            display: flex;
        }
        .chat-message-row.is-admin {
            justify-content: flex-end;
        }
        .chat-bubble {
            max-width: min(88%, 520px);
            border-radius: 16px;
            padding: 0.8rem 0.9rem;
            background: rgba(148, 163, 184, 0.12);
        }
        .chat-message-row.is-admin .chat-bubble {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.16), rgba(191, 219, 254, 0.2));
        }
        .chat-bubble-meta {
            font-size: 0.72rem;
            color: var(--muted);
            margin-bottom: 0.35rem;
            font-weight: 700;
        }
        .chat-bubble-text {
            white-space: pre-wrap;
            line-height: 1.5;
            color: var(--ink);
        }
        .chat-empty {
            color: var(--muted);
            font-size: 0.9rem;
            border: 1px dashed var(--table-border);
            border-radius: 14px;
            padding: 1rem;
        }
        @media (max-width: 991.98px) {
            .chat-thread-list,
            .chat-window {
                max-height: none;
                min-height: 0;
            }
        }
        .admin-chat-float-toggle {
            position: fixed;
            right: 1rem;
            bottom: max(.55rem, calc(.55rem + env(safe-area-inset-bottom)));
            z-index: 1080;
            width: 36px;
            height: 36px;
            border-radius: 11px;
            border: 1px solid rgba(29, 78, 216, 0.28);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #1d4ed8;
            background: rgba(241, 245, 249, 0.92);
            backdrop-filter: blur(8px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.24);
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .admin-chat-float-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.32);
        }
        .admin-chat-float-icon {
            width: 22px;
            height: 22px;
            display: block;
        }
        .admin-chat-float-icon.is-close {
            display: none;
            width: 18px;
            height: 18px;
        }
        html[data-theme='dark'] .admin-chat-float-toggle,
        body[data-theme='dark'] .admin-chat-float-toggle {
            color: #93c5fd;
            background: rgba(15, 23, 42, 0.86);
            border-color: rgba(96, 165, 250, 0.45);
            box-shadow: 0 9px 20px rgba(2, 6, 23, 0.55);
        }
        .admin-chat-float-toggle.is-open .admin-chat-float-icon.is-chat {
            display: none;
        }
        .admin-chat-float-toggle.is-open .admin-chat-float-icon.is-close {
            display: block;
        }
        .admin-chat-float-badge {
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
        .admin-chat-float-badge.is-warning {
            top: auto;
            bottom: -4px;
            right: -6px;
            background: #f59e0b;
        }
        .admin-chat-float-badge.is-hidden {
            display: none;
        }
        .admin-chat-float-card {
            position: fixed;
            right: 1rem;
            bottom: max(4.9rem, calc(4.9rem + env(safe-area-inset-bottom)));
            z-index: 1080;
            width: min(980px, calc(100vw - 1.5rem));
            height: min(82vh, 720px);
            border: 1px solid var(--table-border);
            border-radius: 16px;
            background: var(--panel);
            box-shadow: var(--panel-shadow);
            overflow: hidden;
            display: none;
            grid-template-rows: auto 1fr;
        }
        .admin-chat-float-card.is-open {
            display: grid;
        }
        .admin-chat-float-head {
            padding: .75rem .85rem;
            padding-right: 2.7rem;
            border-bottom: 1px solid var(--table-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            position: relative;
        }
        .admin-chat-float-close {
            position: absolute;
            right: .55rem;
            top: .55rem;
            width: 28px;
            height: 28px;
            border: 0;
            background: transparent;
            color: var(--muted);
            font-size: 1.1rem;
            line-height: 1;
            padding: 0;
            border-radius: 9px;
            border: 1px solid var(--table-border);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .admin-chat-float-body {
            padding: .7rem;
            overflow: hidden;
            min-height: 0;
        }
        .admin-chat-inbox {
            display: grid;
            --admin-chat-rail: clamp(84px, 9vw, 96px);
            --admin-chat-avatar-btn: clamp(56px, 6vw, 60px);
            --admin-chat-avatar-img: clamp(44px, 4.8vw, 48px);
            grid-template-columns: var(--admin-chat-rail) minmax(0, 1fr);
            grid-template-rows: auto minmax(0, 1fr);
            grid-template-areas:
                'search search'
                'sidebar main';
            gap: .65rem;
            height: 100%;
            min-height: 0;
        }
        .admin-chat-search-top {
            grid-area: search;
        }
        #admin-chat-search {
            border-radius: 10px;
            font-size: .84rem;
        }
        .admin-chat-sidebar {
            grid-area: sidebar;
            border: 1px solid var(--table-border);
            border-radius: 12px;
            padding: .55rem .4rem;
            min-height: 0;
            overflow: hidden;
            background: color-mix(in srgb, var(--panel) 92%, transparent);
        }
        .admin-chat-user-list {
            min-height: 0;
            max-height: 100%;
            overflow-y: auto;
            overflow-x: hidden;
            display: grid;
            justify-items: center;
            align-content: start;
            gap: .45rem;
            padding: .1rem;
            scrollbar-width: none;
            -ms-overflow-style: none;
            touch-action: pan-y;
            -webkit-overflow-scrolling: touch;
        }
        .admin-chat-user-list::-webkit-scrollbar {
            width: 0;
            height: 0;
        }
        .admin-chat-user-item {
            border: 1px solid var(--table-border);
            border-radius: 999px;
            background: color-mix(in srgb, var(--panel) 84%, transparent);
            width: var(--admin-chat-avatar-btn);
            height: var(--admin-chat-avatar-btn);
            max-width: var(--admin-chat-avatar-btn);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-sizing: border-box;
            transition: border-color .16s ease, box-shadow .16s ease, background-color .16s ease;
        }
        .admin-chat-user-item:hover {
            border-color: rgba(59, 130, 246, .5);
            background: color-mix(in srgb, var(--panel) 74%, transparent);
        }
        .admin-chat-user-item.is-active {
            border-color: rgba(59, 130, 246, .58);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, .18);
        }
        .admin-chat-user-avatar {
            width: var(--admin-chat-avatar-img);
            height: var(--admin-chat-avatar-img);
            border-radius: 999px;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, .35);
            background: linear-gradient(135deg, rgba(30, 64, 175, .92), rgba(59, 130, 246, .75));
        }
        .admin-chat-user-avatar img {
            width: 94%;
            height: 94%;
            object-fit: contain;
            object-position: center;
            display: block;
            margin: 3%;
            border-radius: 999px;
        }
        .admin-chat-main {
            grid-area: main;
            border: 1px solid var(--table-border);
            border-radius: 12px;
            display: grid;
            grid-template-rows: auto minmax(0, 1fr) auto auto;
            min-height: 0;
            overflow: hidden;
            background: color-mix(in srgb, var(--panel) 96%, transparent);
        }
        .admin-chat-main-head {
            padding: .65rem .75rem;
            border-bottom: 1px solid var(--table-border);
        }
        .admin-chat-main-head-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .6rem;
        }
        .admin-chat-main-title-wrap {
            min-width: 0;
        }
        .admin-chat-thread-clear {
            border: 1px solid var(--table-border);
            background: color-mix(in srgb, var(--panel) 80%, transparent);
            color: var(--muted);
            border-radius: 9px;
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            flex: 0 0 auto;
        }
        .admin-chat-thread-clear:disabled {
            opacity: .45;
            cursor: not-allowed;
        }
        .admin-chat-pending-list {
            border-top: 1px solid var(--table-border);
            padding: .55rem .65rem;
            max-height: 96px;
            overflow-y: auto;
            overscroll-behavior: contain;
            touch-action: pan-y;
            -webkit-overflow-scrolling: touch;
        }
        .admin-chat-pending-item {
            border: 1px solid var(--table-border);
            border-radius: 10px;
            padding: .5rem .58rem;
            background: color-mix(in srgb, var(--panel) 88%, transparent);
        }
        .admin-chat-pending-item + .admin-chat-pending-item {
            margin-top: .45rem;
        }
        .admin-chat-pending-actions {
            display: flex;
            justify-content: flex-end;
            gap: .35rem;
            margin-top: .45rem;
        }
        .admin-chat-action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid var(--table-border);
            background: color-mix(in srgb, var(--panel) 78%, transparent);
            color: var(--ink);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .admin-chat-action-btn.is-approve {
            border-color: rgba(34, 197, 94, .45);
            color: #16a34a;
        }
        .admin-chat-action-btn.is-reject {
            border-color: rgba(239, 68, 68, .45);
            color: #dc2626;
        }
        .admin-chat-action-btn svg {
            width: 15px;
            height: 15px;
        }
        .admin-chat-compose {
            position: relative;
        }
        .admin-chat-compose textarea {
            padding-right: 3.1rem;
            min-height: 56px;
            resize: vertical;
            display: block;
        }
        .admin-chat-send-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            border: 1px solid rgba(59, 130, 246, 0.45);
            background: rgba(37, 99, 235, 0.18);
            color: #93c5fd;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform .16s ease, opacity .16s ease, border-color .16s ease;
            position: absolute;
            right: .45rem;
            bottom: .45rem;
            z-index: 2;
        }
        .admin-chat-send-icon:hover:not(:disabled) {
            transform: translateY(-1px);
            border-color: rgba(96, 165, 250, 0.6);
        }
        .admin-chat-send-icon:disabled {
            opacity: .55;
            cursor: not-allowed;
        }
        .admin-chat-send-icon svg {
            width: 15px;
            height: 15px;
        }
        .starts-at-live-note {
            margin-top: .35rem;
            font-size: .78rem;
            color: var(--muted);
        }
        html[data-theme='dark'] #admin-chat-head-status,
        html[data-theme='dark'] #admin-chat-status,
        html[data-theme='dark'] #admin-chat-subtitle,
        html[data-theme='dark'] .chat-bubble-meta {
            color: rgba(191, 219, 254, 0.9) !important;
        }
        html[data-theme='dark'] .chat-bubble {
            background: rgba(30, 41, 59, 0.9);
            border: 1px solid rgba(96, 165, 250, 0.2);
        }
        html[data-theme='dark'] .chat-message-row.is-admin .chat-bubble {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.28), rgba(30, 64, 175, 0.22));
            border-color: rgba(147, 197, 253, 0.24);
        }
        @media (max-width: 991.98px) {
            .admin-chat-float-toggle {
                right: .75rem;
                bottom: max(5.9rem, calc(5.9rem + env(safe-area-inset-bottom)));
            }
            .admin-chat-float-card {
                left: .55rem;
                right: .55rem;
                width: auto;
                height: auto;
                max-height: none;
                top: max(.6rem, env(safe-area-inset-top));
                bottom: max(.6rem, env(safe-area-inset-bottom));
                border-radius: 18px;
            }
            .admin-chat-float-body {
                padding: .58rem;
            }
            .admin-chat-inbox {
                --admin-chat-rail: 100%;
                --admin-chat-avatar-btn: 52px;
                --admin-chat-avatar-img: 40px;
                grid-template-columns: 1fr;
                grid-template-rows: auto auto minmax(0, 1fr);
                grid-template-areas:
                    'search'
                    'sidebar'
                    'main';
                gap: .45rem;
            }
            .admin-chat-user-list {
                grid-auto-flow: column;
                grid-auto-columns: minmax(var(--admin-chat-avatar-btn), var(--admin-chat-avatar-btn));
                justify-items: stretch;
                justify-content: flex-start;
                overflow-x: auto;
                overflow-y: hidden;
                min-height: 58px;
                max-height: none;
                padding-bottom: .2rem;
                touch-action: pan-x;
                -webkit-overflow-scrolling: touch;
            }
            .admin-chat-main {
                grid-template-rows: auto minmax(0, 1fr) auto auto;
            }
            .admin-chat-main-head {
                padding: .58rem .62rem;
            }
            .chat-messages {
                padding: .58rem .62rem;
                gap: .55rem;
                min-height: 0;
            }
            .chat-bubble {
                border-radius: 12px;
                padding: .58rem .64rem;
                max-width: min(92%, 420px);
            }
            .admin-chat-main .chat-window-form {
                position: sticky;
                bottom: 0;
                background: var(--panel);
                z-index: 2;
                padding: .55rem .62rem;
                border-top: 1px solid var(--table-border);
            }
            .admin-chat-pending-list {
                max-height: 74px;
            }
            .admin-chat-compose textarea {
                min-height: 74px;
                max-height: 28vh;
            }
        }
        @media (max-width: 575.98px) {
            .admin-chat-float-head {
                padding: .62rem .7rem;
                padding-right: 2.85rem;
            }
            .admin-chat-main-head-row {
                align-items: flex-start;
            }
            .admin-chat-thread-clear,
            .admin-chat-float-close {
                width: 34px;
                height: 34px;
                border-radius: 10px;
                border: 1px solid var(--table-border);
                background: color-mix(in srgb, var(--panel) 82%, transparent);
                flex: 0 0 auto;
            }
            .admin-chat-pending-list {
                max-height: 110px;
            }
        }
    </style>
</head>
<body>
@php
    $billingCfg = $billingConfig ?? [];
    $gatewayEnabled = (bool) ($billingCfg['auto_gateway_enabled'] ?? false);
    $qrisEnabled = (bool) ($billingCfg['auto_qris_enabled'] ?? false);
    $vaEnabled = (bool) ($billingCfg['auto_va_enabled'] ?? false);
    $discount3 = max(0, min(95, (float) ($billingCfg['discount_3_month_pct'] ?? 0)));
    $discount6 = max(0, min(95, (float) ($billingCfg['discount_6_month_pct'] ?? 0)));
    $discount12 = max(0, min(95, (float) ($billingCfg['discount_12_month_pct'] ?? 0)));
    $discount24 = max(0, min(95, (float) ($billingCfg['discount_24_month_pct'] ?? 0)));
    $trialDays = max(1, min(30, (int) ($billingCfg['trial_days'] ?? 3)));
    $pendingBillingCount = $pendingBillings->count();
    $processedBillingRows = $processedBillings ?? collect();
    $redeemRows = $redeemCodes ?? collect();
    $remainingRedeemCount = (int) $redeemRows->whereNull('redeemed_at')->count();
@endphp
@php
    $adminLicenseAccountsPayload = ($accounts ?? collect())->map(function ($account) use ($licenses) {
        $license = $licenses[$account->account_id] ?? null;
        $licenseStatus = strtolower(trim((string) ($license->status ?? 'unlicensed')));
        if ($licenseStatus === '') {
            $licenseStatus = 'unlicensed';
        }

        return [
            'id' => (string) ($account->account_id ?? ''),
            'owner' => (string) (($account->owner_names ?? optional($account->user)->name) ?? '-'),
            'license_status' => $licenseStatus,
        ];
    })->values();
@endphp
<div class="container-fluid admin-license-container py-4 py-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h3 mb-1">Admin Lisensi Account MT5</h1>
            <div class="text-secondary">Konfigurasi lisensi bulanan/permanent dan approval billing client.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('dashboard.index') }}" class="btn btn-outline-dark">Dashboard</a>
            <a href="{{ route('licenses.billing.page') }}" class="btn btn-dark">Billing Client</a>
        </div>
    </div>

    <div class="admin-tabs-nav" id="admin-license-tabs" role="tablist" aria-label="Admin License Sections">
        <button type="button" class="admin-tab-btn is-active" data-admin-tab="config">Konfigurasi</button>
        <button type="button" class="admin-tab-btn" data-admin-tab="license">Lisensi</button>
        <button type="button" class="admin-tab-btn" data-admin-tab="billing">Billing <span class="admin-tab-badge">{{ $pendingBillingCount }}</span></button>
        <button type="button" class="admin-tab-btn" data-admin-tab="redeem">Redeem Code <span class="admin-tab-badge">{{ $remainingRedeemCount }}</span></button>
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

    <div class="admin-tab-pane is-active" data-admin-tab-pane="config">

    <div class="panel p-3 p-lg-4 mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <div class="fw-semibold">Master Switch Lisensi</div>
                <div class="small text-secondary">Default OFF. Saat ON, rule lisensi langsung berlaku ke semua account yang belum valid.</div>
            </div>
            <form method="post" action="{{ route('licenses.admin.enforcement') }}" class="d-flex align-items-center gap-2">
                @csrf
                <input type="hidden" name="enabled" value="{{ $licenseEnforcementEnabled ? 0 : 1 }}">
                <span class="badge {{ $licenseEnforcementEnabled ? 'bg-success' : 'bg-secondary' }}">
                    {{ $licenseEnforcementEnabled ? 'ENFORCEMENT ON' : 'ENFORCEMENT OFF' }}
                </span>
                <button type="submit" class="btn {{ $licenseEnforcementEnabled ? 'btn-outline-danger' : 'btn-success' }}">
                    {{ $licenseEnforcementEnabled ? 'Turn OFF' : 'Turn ON' }}
                </button>
            </form>
        </div>
    </div>

    <div class="panel p-3 p-lg-4 mb-3 billing-alert-widget">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="small text-secondary text-uppercase fw-semibold mb-2">Notifikasi Billing</div>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="billing-alert-count">{{ $pendingBillingCount }}</div>
                    <div>
                        <div class="fw-semibold">Request billing menunggu review admin</div>
                        <div class="small text-secondary">Approve atau reject request client agar lisensi bisa langsung diproses lebih cepat.</div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-warning fw-semibold" data-admin-tab-target="billing">Lihat Request Billing</button>
        </div>
    </div>

    <div class="panel p-3 p-lg-4 mb-3">
        <h2 class="h5 mb-3">Konfigurasi Pembayaran Client</h2>
        <form method="post" action="{{ route('licenses.admin.payment.config') }}" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label mb-1">Bank</label>
                <input type="text" name="bank_name" class="form-control" value="{{ (string) ($billingCfg['bank_name'] ?? 'BCA') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1">Nama Rekening</label>
                <input type="text" name="bank_account_name" class="form-control" value="{{ (string) ($billingCfg['bank_account_name'] ?? '') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1">Nomor Rekening</label>
                <input type="text" name="bank_account_number" class="form-control" value="{{ (string) ($billingCfg['bank_account_number'] ?? '') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1">Harga Per Bulan</label>
                <input type="hidden" id="monthly-price" name="monthly_price" value="{{ number_format((float) ($billingCfg['monthly_price'] ?? 0), 2, '.', '') }}" required>
                <input type="text" id="monthly-price-display" class="form-control" inputmode="decimal" autocomplete="off" required>
                <div class="small text-secondary mt-1">Nominal billing client dihitung otomatis: jumlah bulan x harga per bulan.</div>
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1">Diskon 3 Bulan (%)</label>
                <input type="number" min="0" max="95" step="0.01" name="discount_3_month_pct" class="form-control" value="{{ number_format($discount3, 2, '.', '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1">Diskon 6 Bulan (%)</label>
                <input type="number" min="0" max="95" step="0.01" name="discount_6_month_pct" class="form-control" value="{{ number_format($discount6, 2, '.', '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1">Diskon 12 Bulan (%)</label>
                <input type="number" min="0" max="95" step="0.01" name="discount_12_month_pct" class="form-control" value="{{ number_format($discount12, 2, '.', '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1">Diskon 24 Bulan (%)</label>
                <input type="number" min="0" max="95" step="0.01" name="discount_24_month_pct" class="form-control" value="{{ number_format($discount24, 2, '.', '') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1">Default Trial (hari)</label>
                <input type="number" min="1" max="30" step="1" name="trial_days" class="form-control" value="{{ $trialDays }}" required>
            </div>
            <div class="col-12">
                <label class="form-label mb-1">Catatan Transfer</label>
                <input type="text" name="bank_note" class="form-control" value="{{ (string) ($billingCfg['bank_note'] ?? '') }}" placeholder="Instruksi untuk client saat transfer manual">
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1">Nama Kontak Admin</label>
                <input type="text" name="contact_name" class="form-control" value="{{ (string) ($billingCfg['contact_name'] ?? 'Admin Billing') }}" placeholder="Contoh: Admin Billing">
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1">No Kontak Admin</label>
                <input type="text" name="contact_phone" class="form-control" value="{{ (string) ($billingCfg['contact_phone'] ?? '') }}" placeholder="Contoh: 6281234567890">
                <div class="small text-secondary mt-1">Nomor ini dipakai untuk tombol live chat / hubungi admin di halaman billing client.</div>
            </div>
            <div class="col-12">
                <div class="d-flex flex-wrap gap-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="auto_gateway_enabled" name="auto_gateway_enabled" value="1" @if($gatewayEnabled) checked @endif>
                        <label class="form-check-label" for="auto_gateway_enabled">Aktifkan Gateway Otomatis</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="auto_qris_enabled" name="auto_qris_enabled" value="1" @if($qrisEnabled) checked @endif>
                        <label class="form-check-label" for="auto_qris_enabled">Aktifkan QRIS</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="auto_va_enabled" name="auto_va_enabled" value="1" @if($vaEnabled) checked @endif>
                        <label class="form-check-label" for="auto_va_enabled">Aktifkan Virtual Account</label>
                    </div>
                </div>
                <div class="small text-secondary mt-2">Jika Gateway Otomatis OFF, opsi QRIS/VA otomatis di halaman client akan nonaktif otomatis.</div>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Simpan Konfigurasi Pembayaran</button>
            </div>
        </form>
    </div>

</div>

<div class="admin-tab-pane" data-admin-tab-pane="license">

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="panel p-3 p-lg-4">
                <h2 class="h5 mb-3">Set Lisensi Per Account</h2>
                <form id="admin-license-upsert-form" method="post" action="{{ route('licenses.admin.upsert') }}" class="vstack gap-2">
                    @csrf
                    <input type="hidden" id="admin-upsert-account-id" name="account_id" value="{{ old('account_id', '') }}" required>
                    <label class="form-label mb-0">Account ID</label>
                    <div class="dropdown admin-license-account-picker">
                        <button id="admin-upsert-account-picker-toggle" class="btn admin-license-account-picker-toggle dropdown-toggle" type="button" aria-expanded="false">Pilih account</button>
                        <div class="dropdown-menu admin-license-account-picker-menu">
                            <input id="admin-upsert-account-picker-search" type="text" class="form-control admin-license-account-picker-search" placeholder="Cari account / owner" aria-label="Cari account">
                            <div id="admin-upsert-account-picker-options" class="admin-license-account-picker-options"></div>
                        </div>
                    </div>

                    <input type="hidden" id="plan-name" name="plan_name" value="Bulanan 1 Bulan">

                    <label class="form-label mb-0 mt-2">Mode Lisensi</label>
                    <select id="license-mode" name="license_mode" class="form-select" required>
                        <option value="monthly">Bulanan</option>
                        <option value="permanent">Permanent Contract</option>
                    </select>

                    <label class="form-label mb-0 mt-2">Durasi Bulan</label>
                    <select id="duration-months" name="duration_months" class="form-select">
                        <option value="1">1 Bulan</option>
                        <option value="3">3 Bulan</option>
                        <option value="6">6 Bulan</option>
                        <option value="12">12 Bulan</option>
                        <option value="24">24 Bulan</option>
                    </select>

                    <label class="form-label mb-0 mt-2">Starts At</label>
                    <input type="datetime-local" id="starts-at-input" name="starts_at" class="form-control" step="1">
                    <div id="starts-at-live-note" class="starts-at-live-note">Waktu lokal otomatis hari ini akan terus diperbarui per detik sampai Anda ubah manual.</div>

                    <label class="form-label mb-0 mt-2">Catatan</label>
                    <textarea name="notes" rows="3" class="form-control" placeholder="Opsional"></textarea>

                    <button type="submit" class="btn btn-primary mt-2">Simpan Lisensi</button>
                </form>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="panel p-3 p-lg-4 h-100">
                <h2 class="h5 mb-3">Daftar Lisensi Akun</h2>
                <div class="license-admin-toolbar">
                    <input type="text" id="admin-license-table-search" class="form-control" placeholder="Cari account / owner / status / plan" aria-label="Cari lisensi account">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" id="admin-license-mixed-only">
                        <label class="form-check-label" for="admin-license-mixed-only">Hanya owner campuran</label>
                    </div>
                </div>
                <div class="license-admin-pagination">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <label for="admin-license-table-page-size" class="small text-secondary mb-0">Tampil</label>
                        <select id="admin-license-table-page-size" class="form-select form-select-sm license-admin-page-size" aria-label="Jumlah baris per halaman">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                        </select>
                        <div id="admin-license-table-page-info" class="small text-secondary"></div>
                    </div>
                    <div class="license-admin-page-nav">
                        <button type="button" id="admin-license-page-prev" class="btn btn-outline-secondary btn-sm license-admin-page-btn" aria-label="Halaman sebelumnya" title="Sebelumnya">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M14.5 6 8.5 12l6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button type="button" id="admin-license-page-next" class="btn btn-outline-secondary btn-sm license-admin-page-btn" aria-label="Halaman berikutnya" title="Berikutnya">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="m9.5 6 6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                </div>
                <div class="license-admin-table-wrap">
                    <table class="table table-sm align-middle license-admin-table">
                        <thead><tr><th>Account</th><th>Owner</th><th>Status</th><th>Plan</th><th>Expires</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody id="admin-license-table-body">
                        @forelse($accounts as $account)
                            @php $license = $licenses[$account->account_id] ?? null; @endphp
                            <tr data-license-row="1" data-has-mixed-owner="{{ !empty($account->has_multiple_owners) ? '1' : '0' }}" data-license-search="{{ strtolower(trim(implode(' ', array_filter([
                                (string) $account->account_id,
                                (string) ($account->owner_names ?? optional($account->user)->name ?? '-'),
                                (string) ($license->status ?? 'unlicensed'),
                                (string) ($license->plan_name ?? '-'),
                            ])))) }}">
                                <td class="license-admin-account-cell" data-label="Account">{{ $account->account_id }}</td>
                                <td class="license-admin-owner-cell" data-label="Owner">
                                    {{ (string)($account->owner_names ?? optional($account->user)->name ?? '-') }}
                                    @if(!empty($account->has_multiple_owners))
                                        <span class="license-admin-owner-flag">
                                            <span class="badge text-bg-warning">Owner Campuran</span>
                                            <span class="small text-secondary">{{ (int) ($account->owner_count ?? 0) }} owner</span>
                                        </span>
                                    @endif
                                </td>
                                <td data-label="Status">{{ strtoupper((string)($license->status ?? 'unlicensed')) }}</td>
                                <td class="license-admin-plan-cell" data-label="Plan">{{ (string)($license->plan_name ?? '-') }}</td>
                                <td class="license-admin-expires-cell" data-label="Expires">{{ $license?->is_perpetual ? 'Permanent' : optional($license?->expires_at)->format('Y-m-d H:i') }}</td>
                                <td class="text-end license-admin-actions-cell" data-label="Aksi">
                                    <div class="license-admin-actions">
                                        <form method="post" action="{{ route('licenses.admin.reassign-account') }}" class="license-admin-reassign-form" onsubmit="return confirm('Pindahkan account {{ $account->account_id }} ke owner yang dipilih? Semua pair/config account ini akan ikut berpindah jika account tidak aktif.');">
                                            @csrf
                                            <input type="hidden" name="account_id" value="{{ $account->account_id }}">
                                            <select name="target_user_id" class="form-select form-select-sm" required>
                                                @foreach($users as $userOption)
                                                    @php
                                                        $userOptionLabel = trim((string) ($userOption->name ?: $userOption->email ?: $userOption->username ?: ('User #' . $userOption->id)));
                                                    @endphp
                                                    <option value="{{ $userOption->id }}" @selected((int) $userOption->id === (int) $account->user_id)>
                                                        {{ $userOptionLabel }}@if(!empty($userOption->email)) - {{ $userOption->email }}@endif
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-outline-primary license-admin-icon-btn is-primary" aria-label="Pindah owner" title="Pindah owner">
                                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 7h11M11 4l4 3-4 3M20 17H9M13 14l-4 3 4 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </button>
                                            <button type="submit" formaction="{{ route('licenses.admin.remove-owner') }}" formmethod="post" class="btn btn-sm btn-outline-danger license-admin-icon-btn is-danger" aria-label="Remove owner terpilih" title="Remove owner terpilih" onclick="return confirm('Hapus owner terpilih dari account {{ $account->account_id }}? Hanya config milik owner terpilih yang dihapus jika account tidak aktif.')">
                                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M15 8a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM4 20a6 6 0 0 1 12 0M20 8h-6M17 5v6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </button>
                                        </form>
                                        @if($license)
                                            <form method="post" action="{{ route('licenses.admin.delete', ['licenseId' => $license->id]) }}" onsubmit="return confirm('Hapus plan lisensi untuk account {{ $account->account_id }}?');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger license-admin-icon-btn is-danger" aria-label="Hapus lisensi" title="Hapus lisensi">
                                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 7h16M10 11v5M14 11v5M6 7l1 13h10l1-13M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                        <form method="post" action="{{ route('dashboard.accounts.delete') }}" onsubmit="return confirm('Hapus account {{ $account->account_id }} dari owner {{ (string)($account->owner_names ?? optional($account->user)->name ?? '-') }}? Semua pair/config untuk account ini akan ikut terhapus jika tidak aktif.');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="account_id" value="{{ $account->account_id }}">
                                            <button type="submit" class="btn btn-sm btn-outline-warning license-admin-icon-btn is-warning" aria-label="Hapus account" title="Hapus account">
                                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M5 7h14M10 11v5M14 11v5M7 7l1 13h8l1-13M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-secondary">Belum ada data account.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div id="admin-license-table-empty" class="small text-secondary mt-2 d-none">Tidak ada account yang cocok dengan filter.</div>
            </div>
        </div>
    </div>

</div>

<div class="admin-tab-pane" data-admin-tab-pane="billing">

    <div id="pending-billing-requests" class="panel p-3 p-lg-4 mt-3">
        <h2 class="h5 mb-3">Pending Billing Requests</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>#</th><th>User</th><th>Account</th><th>Plan</th><th>Bulan</th><th>Nominal</th><th>MT5 Server</th><th>MT5 Password</th><th>Request At</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse($pendingBillings as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ optional($item->user)->name ?? '-' }}<br><small class="text-secondary">{{ optional($item->user)->email }}</small></td>
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
                        <td>{{ trim((string) ($item->mt5_server ?? '')) !== '' ? (string) $item->mt5_server : '-' }}</td>
                        <td>
                            @if(!empty($item->mt5_password_encrypted))
                                <div class="d-flex align-items-center gap-2">
                                    <span data-billing-password-value="{{ $item->id }}">Provided (Encrypted)</span>
                                    <button type="button" class="btn btn-sm btn-outline-light" data-billing-password-toggle="{{ $item->id }}" title="Lihat password MT5" aria-label="Lihat password MT5">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
                                    </button>
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ optional($item->created_at)->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <form method="post" action="{{ route('licenses.admin.billing.decision', ['billingId' => $item->id]) }}">
                                    @csrf
                                    <input type="hidden" name="decision" value="approve">
                                    <button class="btn btn-sm btn-success w-100" type="submit">Approve</button>
                                </form>
                                <form method="post" action="{{ route('licenses.admin.billing.decision', ['billingId' => $item->id]) }}">
                                    @csrf
                                    <input type="hidden" name="decision" value="reject">
                                    <button class="btn btn-sm btn-outline-danger w-100" type="submit">Reject</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-secondary">Tidak ada pending billing.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel p-3 p-lg-4 mt-3">
        <h2 class="h5 mb-3">Riwayat Billing Diproses</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>#</th><th>User</th><th>Account</th><th>Status</th><th>Nominal</th><th>MT5 Server</th><th>MT5 Password</th><th>Request At</th><th>Processed At</th><th>Admin</th><th>Catatan</th></tr></thead>
                <tbody>
                @forelse($processedBillingRows as $item)
                    @php
                        $rawNotes = trim((string) ($item->notes ?? ''));
                        $safeNotes = trim((string) preg_replace('/^MT5\s+Password\s*:\s*.*$/mi', 'MT5 Password: [MASKED]', $rawNotes));
                    @endphp
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ optional($item->user)->name ?? '-' }}<br><small class="text-secondary">{{ optional($item->user)->email }}</small></td>
                        <td>{{ $item->account_id }}</td>
                        <td>
                            <span class="badge {{ $item->status === 'approved' ? 'bg-success' : 'bg-danger' }}">
                                {{ strtoupper((string) $item->status) }}
                            </span>
                        </td>
                        <td>
                            @if($item->requested_amount !== null)
                                Rp {{ number_format((float) $item->requested_amount, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ trim((string) ($item->mt5_server ?? '')) !== '' ? (string) $item->mt5_server : '-' }}</td>
                        <td>
                            @if(!empty($item->mt5_password_encrypted))
                                <div class="d-flex align-items-center gap-2">
                                    <span data-billing-password-value="{{ $item->id }}">Provided (Encrypted)</span>
                                    <button type="button" class="btn btn-sm btn-outline-light" data-billing-password-toggle="{{ $item->id }}" title="Lihat password MT5" aria-label="Lihat password MT5">
                                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/></svg>
                                    </button>
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ optional($item->created_at)->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ optional($item->processed_at)->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ optional($item->processedBy)->name ?? '-' }}</td>
                        <td>{{ $safeNotes !== '' ? $safeNotes : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-secondary">Belum ada billing yang diproses.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<div class="admin-tab-pane" data-admin-tab-pane="redeem">
    <div class="panel p-3 p-lg-4 mt-3">
        <h2 class="h5 mb-3">Generate Redeem Code Trial</h2>
        <form method="post" action="{{ route('licenses.admin.redeem.generate') }}" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label mb-1">Jumlah Code</label>
                <input type="number" min="1" max="100" step="1" name="generate_count" class="form-control" value="5" required>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">Trial Hari</label>
                <input type="number" min="1" max="30" step="1" name="trial_days" class="form-control" value="{{ $trialDays }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">Expired At (opsional)</label>
                <input type="datetime-local" name="expires_at" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label mb-1">Catatan (opsional)</label>
                <input type="text" name="notes" class="form-control" placeholder="Contoh: Promo komunitas">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Generate Redeem Code</button>
            </div>
        </form>
    </div>

    <div class="panel p-3 p-lg-4 mt-3">
        <h2 class="h5 mb-3">Riwayat Redeem Code</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>#</th><th>Code</th><th>Trial</th><th>Status</th><th>Expired</th><th>Redeemed By</th><th>Account</th><th>Redeemed At</th></tr></thead>
                <tbody>
                @forelse($redeemRows as $item)
                    <tr>
                        <td>{{ $item->id }}</td>
                        <td>{{ $item->code }}</td>
                        <td>{{ (int) ($item->trial_days ?? 0) }} hari</td>
                        <td>
                            @if($item->redeemed_at)
                                <span class="badge bg-success">REDEEMED</span>
                            @elseif($item->is_active)
                                <span class="badge bg-warning text-dark">ACTIVE</span>
                            @else
                                <span class="badge bg-secondary">INACTIVE</span>
                            @endif
                        </td>
                        <td>{{ optional($item->expires_at)->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>{{ optional($item->redeemedBy)->name ?? '-' }}</td>
                        <td>{{ $item->redeemed_account_id ?? '-' }}</td>
                        <td>{{ optional($item->redeemed_at)->format('Y-m-d H:i') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-secondary">Belum ada redeem code.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<div class="admin-tabs-bottom" id="admin-license-tabs-bottom" role="navigation" aria-label="Admin License Quick Tabs">
    <button type="button" class="admin-tab-btn is-active" data-admin-tab="config" aria-label="Konfigurasi" title="Konfigurasi">
        <svg class="admin-tab-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M4 7h8M4 17h4M14 17h6M16 7h4M12 5v4M10 15v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        <span class="admin-tab-label">Konfigurasi</span>
    </button>
    <button type="button" class="admin-tab-btn" data-admin-tab="license" aria-label="Lisensi" title="Lisensi">
        <svg class="admin-tab-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 3 5 6v6c0 4.6 3.1 8.8 7 9.9 3.9-1.1 7-5.3 7-9.9V6l-7-3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
        <span class="admin-tab-label">Lisensi</span>
    </button>
    <button type="button" class="admin-tab-btn" data-admin-tab="billing" aria-label="Billing" title="Billing">
        <svg class="admin-tab-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M3 7h18v10H3zM3 11h18" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
        <span class="admin-tab-label">Billing</span>
        <span class="admin-tab-badge {{ $pendingBillingCount > 0 ? '' : 'is-hidden' }}">{{ $pendingBillingCount > 99 ? '99+' : $pendingBillingCount }}</span>
    </button>
    <button type="button" class="admin-tab-btn" data-admin-tab="redeem" aria-label="Redeem Code" title="Redeem Code">
        <svg class="admin-tab-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8 5h8l3 4-7 10L5 9l3-4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
        <span class="admin-tab-label">Redeem</span>
        <span class="admin-tab-badge {{ $remainingRedeemCount > 0 ? '' : 'is-hidden' }}">{{ $remainingRedeemCount > 99 ? '99+' : $remainingRedeemCount }}</span>
    </button>
</div>

@include('partials.admin-livechat-shell', ['chatVariant' => 'admin'])
<script>
const modeEl = document.getElementById('license-mode');
const monthsEl = document.getElementById('duration-months');
const planNameEl = document.getElementById('plan-name');
const startsAtInput = document.getElementById('starts-at-input');
const startsAtLiveNote = document.getElementById('starts-at-live-note');
const monthlyPriceInput = document.getElementById('monthly-price');
const monthlyPriceDisplay = document.getElementById('monthly-price-display');
const adminTabStorageKey = 'ea_admin_license_active_tab';
const adminLicenseAccounts = @json($adminLicenseAccountsPayload);

const setAdminTab = (tabName) => {
    const normalized = String(tabName || 'config').toLowerCase();
    if (!['config', 'license', 'billing', 'redeem'].includes(normalized)) {
        return;
    }
    document.querySelectorAll('[data-admin-tab]').forEach((btn) => {
        const isActive = String(btn.getAttribute('data-admin-tab') || '') === normalized;
        btn.classList.toggle('is-active', isActive);
        btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });
    document.querySelectorAll('[data-admin-tab-pane]').forEach((pane) => {
        pane.classList.toggle('is-active', String(pane.getAttribute('data-admin-tab-pane') || '') === normalized);
    });
    if (window.location.hash !== '#tab-' + normalized) {
        history.replaceState(null, '', '#tab-' + normalized);
    }
    try {
        localStorage.setItem(adminTabStorageKey, normalized);
    } catch (_error) {
    }

    window.dispatchEvent(new CustomEvent('admin-tab-changed', {
        detail: { tab: normalized },
    }));
};

document.querySelectorAll('[data-admin-tab]').forEach((button) => {
    button.addEventListener('click', () => {
        const targetTab = String(button.getAttribute('data-admin-tab') || 'config');
        setAdminTab(targetTab);
    });
});

document.querySelectorAll('[data-admin-tab-target]').forEach((button) => {
    button.addEventListener('click', () => {
        const targetTab = String(button.getAttribute('data-admin-tab-target') || 'config');
        setAdminTab(targetTab);
    });
});

const escapeAdminHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

const initAdminLicenseAccountPicker = () => {
    const toggleEl = document.getElementById('admin-upsert-account-picker-toggle');
    const searchEl = document.getElementById('admin-upsert-account-picker-search');
    const optionsEl = document.getElementById('admin-upsert-account-picker-options');
    const hiddenEl = document.getElementById('admin-upsert-account-id');
    const formEl = document.getElementById('admin-license-upsert-form');
    const menuEl = document.querySelector('#admin-upsert-account-picker-toggle + .dropdown-menu');
    if (!(toggleEl instanceof HTMLElement) || !(searchEl instanceof HTMLInputElement) || !(optionsEl instanceof HTMLElement) || !(hiddenEl instanceof HTMLInputElement)) {
        return;
    }
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
        if (!normalized) return 'Pilih account';
        const found = adminLicenseAccounts.find((item) => String(item?.id || '') === normalized);
        if (!found) return normalized;
        return String(found.owner || '').trim() !== '' && String(found.owner || '-') !== '-'
            ? normalized + ' - ' + String(found.owner)
            : normalized;
    };

    const renderOptions = () => {
        const keyword = String(searchEl.value || '').trim().toLowerCase();
        const selectedId = String(hiddenEl.value || '').trim();
        const filtered = adminLicenseAccounts.filter((item) => {
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
            const statusRaw = String(item?.license_status || 'unlicensed').trim().toLowerCase();
            const statusLabel = (statusRaw || 'unlicensed').toUpperCase();
            let statusClass = 'is-off';
            if (statusRaw === 'active') {
                statusClass = 'is-active';
            } else if (statusRaw === 'expired') {
                statusClass = 'is-expired';
            }
            return '<button type="button" class="dropdown-item admin-license-account-picker-item' + (isActive ? ' active' : '') + '" data-account-id="' + escapeAdminHtml(id) + '">'
                + '<span class="admin-license-account-picker-item-row">'
                + '<span class="admin-license-account-picker-item-label">' + escapeAdminHtml(label) + '</span>'
                + '<span class="admin-license-account-picker-badge ' + statusClass + '">' + escapeAdminHtml(statusLabel) + '</span>'
                + '</span>'
                + '</button>';
        }).join('');
    };

    const syncToggle = () => {
        const label = getLabel(hiddenEl.value);
        toggleEl.textContent = label;
        toggleEl.setAttribute('title', label);
    };

    searchEl.addEventListener('input', renderOptions);
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

    toggleEl.addEventListener('click', (event) => {
        event.preventDefault();
        if (isOpen()) {
            closeMenu();
            return;
        }
        openMenu();
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

initAdminLicenseAccountPicker();

const initAdminLicenseTableFilter = () => {
    const searchEl = document.getElementById('admin-license-table-search');
    const mixedOnlyEl = document.getElementById('admin-license-mixed-only');
    const emptyEl = document.getElementById('admin-license-table-empty');
    const pageSizeEl = document.getElementById('admin-license-table-page-size');
    const pageInfoEl = document.getElementById('admin-license-table-page-info');
    const prevPageEl = document.getElementById('admin-license-page-prev');
    const nextPageEl = document.getElementById('admin-license-page-next');
    const rows = Array.from(document.querySelectorAll('[data-license-row="1"]'));
    if (!(searchEl instanceof HTMLInputElement) || !(mixedOnlyEl instanceof HTMLInputElement) || !(emptyEl instanceof HTMLElement) || !(pageSizeEl instanceof HTMLSelectElement) || !(pageInfoEl instanceof HTMLElement) || !(prevPageEl instanceof HTMLButtonElement) || !(nextPageEl instanceof HTMLButtonElement) || !rows.length) {
        return;
    }

    const mobilePageSizeMedia = window.matchMedia('(max-width: 767.98px)');
    const applyMobilePageSizeDefault = () => {
        if (!mobilePageSizeMedia.matches) {
            return false;
        }
        if (!Array.from(pageSizeEl.options).some((option) => String(option.value) === '5')) {
            return false;
        }
        if (pageSizeEl.value === '5') {
            return false;
        }
        pageSizeEl.value = '5';
        return true;
    };
    applyMobilePageSizeDefault();

    const state = {
        page: 1,
        pageSize: Math.max(1, Number(pageSizeEl.value || 10)),
    };

    const updateButtons = (totalPages) => {
        prevPageEl.disabled = state.page <= 1;
        nextPageEl.disabled = state.page >= totalPages;
    };

    const applyFilter = () => {
        const keyword = String(searchEl.value || '').trim().toLowerCase();
        const mixedOnly = mixedOnlyEl.checked;
        state.pageSize = Math.max(1, Number(pageSizeEl.value || 10));

        const matchingRows = rows.filter((row) => {
            if (!(row instanceof HTMLElement)) {
                return false;
            }

            const haystack = String(row.getAttribute('data-license-search') || '').toLowerCase();
            const hasMixedOwner = String(row.getAttribute('data-has-mixed-owner') || '0') === '1';
            const matchesKeyword = keyword === '' || haystack.includes(keyword);
            const matchesMixed = !mixedOnly || hasMixedOwner;
            return matchesKeyword && matchesMixed;
        });

        const totalCount = matchingRows.length;
        const totalPages = Math.max(1, Math.ceil(totalCount / state.pageSize));
        state.page = Math.min(state.page, totalPages);
        if (state.page < 1) {
            state.page = 1;
        }

        const startIndex = totalCount === 0 ? 0 : (state.page - 1) * state.pageSize;
        const endIndex = startIndex + state.pageSize;
        const activeRows = new Set(matchingRows.slice(startIndex, endIndex));

        rows.forEach((row) => {
            if (!(row instanceof HTMLElement)) {
                return;
            }
            row.classList.toggle('license-admin-row-hidden', !activeRows.has(row));
        });

        emptyEl.classList.toggle('d-none', totalCount > 0);
        pageInfoEl.textContent = totalCount === 0
            ? '0 hasil'
            : 'Halaman ' + String(state.page) + ' dari ' + String(totalPages) + ' • ' + String(totalCount) + ' hasil';
        updateButtons(totalPages);
    };

    window.addEventListener('admin-tab-changed', (event) => {
        if (String(event?.detail?.tab || '') === 'license') {
            applyFilter();
        }
    });

    searchEl.addEventListener('input', applyFilter);
    mixedOnlyEl.addEventListener('change', applyFilter);
    pageSizeEl.addEventListener('change', () => {
        state.page = 1;
        applyFilter();
    });
    prevPageEl.addEventListener('click', () => {
        state.page = Math.max(1, state.page - 1);
        applyFilter();
    });
    nextPageEl.addEventListener('click', () => {
        state.page += 1;
        applyFilter();
    });
    mobilePageSizeMedia.addEventListener('change', () => {
        if (applyMobilePageSizeDefault()) {
            state.page = 1;
            applyFilter();
        }
    });
    applyFilter();
};

initAdminLicenseTableFilter();

const initAdminBillingPasswordReveal = () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const endpointBase = @json(url('/admin/licenses/billing'));

    const toggles = Array.from(document.querySelectorAll('[data-billing-password-toggle]'));
    if (!toggles.length) {
        return;
    }

    toggles.forEach((button) => {
        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        button.addEventListener('click', async () => {
            const billingId = String(button.getAttribute('data-billing-password-toggle') || '').trim();
            if (!billingId) {
                return;
            }

            const valueEl = document.querySelector('[data-billing-password-value="' + billingId + '"]');
            if (!(valueEl instanceof HTMLElement)) {
                return;
            }

            const revealed = button.getAttribute('data-revealed') === '1';
            const cached = button.getAttribute('data-password-cache') || '';

            if (revealed) {
                valueEl.textContent = 'Provided (Encrypted)';
                button.setAttribute('data-revealed', '0');
                button.setAttribute('title', 'Lihat password MT5');
                return;
            }

            if (cached !== '') {
                valueEl.textContent = cached;
                button.setAttribute('data-revealed', '1');
                button.setAttribute('title', 'Sembunyikan password MT5');
                return;
            }

            const originalLabel = valueEl.textContent || 'Provided (Encrypted)';
            valueEl.textContent = 'Loading...';
            button.disabled = true;

            try {
                const response = await fetch(endpointBase + '/' + encodeURIComponent(billingId) + '/credential', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({}),
                });

                const payload = await response.json().catch(() => ({}));
                if (!response.ok || !payload?.success) {
                    throw new Error(String(payload?.message || 'Gagal membuka password MT5.'));
                }

                const password = String(payload?.mt5_password || '').trim();
                if (password === '') {
                    throw new Error('Password MT5 kosong.');
                }

                button.setAttribute('data-password-cache', password);
                button.setAttribute('data-revealed', '1');
                button.setAttribute('title', 'Sembunyikan password MT5');
                valueEl.textContent = password;
            } catch (error) {
                valueEl.textContent = originalLabel;
                alert((error instanceof Error ? error.message : 'Gagal membuka password MT5.'));
            } finally {
                button.disabled = false;
            }
        });
    });
};

initAdminBillingPasswordReveal();

const initialTabFromHash = String(window.location.hash || '').replace(/^#tab-/, '').trim().toLowerCase();
let initialTabFromStorage = '';
try {
    initialTabFromStorage = String(localStorage.getItem(adminTabStorageKey) || '').trim().toLowerCase();
} catch (_error) {
    initialTabFromStorage = '';
}
if (['config', 'license', 'billing', 'redeem'].includes(initialTabFromHash)) {
    setAdminTab(initialTabFromHash);
} else if (['config', 'license', 'billing', 'redeem'].includes(initialTabFromStorage)) {
    setAdminTab(initialTabFromStorage);
} else {
    setAdminTab('config');
}

if (modeEl && monthsEl) {
    const buildPlanName = () => {
        if (modeEl.value === 'permanent') {
            return 'Permanent Contract';
        }

        const months = Math.max(1, parseInt(monthsEl.value || '1', 10));
        return 'Bulanan ' + String(months) + ' Bulan';
    };

    const sync = () => {
        monthsEl.disabled = modeEl.value === 'permanent';
        if (planNameEl) {
            planNameEl.value = buildPlanName();
        }
    };

    modeEl.addEventListener('change', sync);
    monthsEl.addEventListener('change', sync);
    sync();
}

if (monthlyPriceInput && monthlyPriceDisplay) {
    const rupiahFormatter = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    const toNumeric = (raw) => {
        const cleaned = String(raw ?? '').trim().replace(/[^0-9,.-]/g, '');
        if (cleaned === '') {
            return 0;
        }

        let normalized = cleaned;
        if (normalized.includes(',') && normalized.includes('.')) {
            normalized = normalized.replace(/\./g, '').replace(',', '.');
        } else if (normalized.includes(',')) {
            normalized = normalized.replace(',', '.');
        }

        const parsed = parseFloat(normalized);
        if (!Number.isFinite(parsed) || parsed < 0) {
            return 0;
        }

        return parsed;
    };

    const toIndoDecimal = (num) => num.toFixed(2).replace('.', ',');

    const syncHiddenFromDisplay = () => {
        const value = toNumeric(monthlyPriceDisplay.value);
        monthlyPriceInput.value = value.toFixed(2);
        return value;
    };

    const renderCurrencyFromHidden = () => {
        const value = toNumeric(monthlyPriceInput.value);
        monthlyPriceInput.value = value.toFixed(2);
        monthlyPriceDisplay.value = rupiahFormatter.format(value);
    };

    monthlyPriceDisplay.addEventListener('focus', () => {
        const value = toNumeric(monthlyPriceInput.value);
        monthlyPriceDisplay.value = toIndoDecimal(value);
        monthlyPriceDisplay.select();
    });

    monthlyPriceDisplay.addEventListener('input', () => {
        syncHiddenFromDisplay();
    });

    monthlyPriceDisplay.addEventListener('blur', () => {
        const value = syncHiddenFromDisplay();
        monthlyPriceDisplay.value = rupiahFormatter.format(value);
    });

    const paymentConfigForm = monthlyPriceInput.closest('form');
    if (paymentConfigForm) {
        paymentConfigForm.addEventListener('submit', () => {
            syncHiddenFromDisplay();
        });
    }

    renderCurrencyFromHidden();
}

if (startsAtInput) {
    let startsAtLockedByUser = false;

    const padStartsAt = (value) => String(value).padStart(2, '0');
    const buildNowLocalDateTimeValue = () => {
        const now = new Date();
        const yyyy = String(now.getFullYear());
        const mm = padStartsAt(now.getMonth() + 1);
        const dd = padStartsAt(now.getDate());
        const hh = padStartsAt(now.getHours());
        const mi = padStartsAt(now.getMinutes());
        const ss = padStartsAt(now.getSeconds());
        return yyyy + '-' + mm + '-' + dd + 'T' + hh + ':' + mi + ':' + ss;
    };

    const renderStartsAtLiveState = () => {
        if (!(startsAtLiveNote instanceof HTMLElement)) return;
        startsAtLiveNote.textContent = startsAtLockedByUser
            ? 'Starts At dikunci sesuai input manual Anda.'
            : 'Starts At otomatis mengikuti waktu lokal hari ini dan berjalan per detik.';
    };

    const syncStartsAtNow = () => {
        if (startsAtLockedByUser) return;
        startsAtInput.value = buildNowLocalDateTimeValue();
    };

    startsAtInput.addEventListener('focus', () => {
        if (!startsAtInput.value) {
            syncStartsAtNow();
        }
    });

    startsAtInput.addEventListener('input', () => {
        startsAtLockedByUser = String(startsAtInput.value || '').trim() !== '';
        renderStartsAtLiveState();
    });

    startsAtInput.addEventListener('change', () => {
        startsAtLockedByUser = String(startsAtInput.value || '').trim() !== '';
        renderStartsAtLiveState();
    });

    syncStartsAtNow();
    renderStartsAtLiveState();
    setInterval(() => {
        syncStartsAtNow();
    }, 1000);
}

const adminChatState = {
    threads: @json($chatThreads ?? []),
    selectedUserId: Number(@json($selectedChatUserId ?? 0)) || 0,
    messages: @json($initialChatMessages ?? []),
    pendingBillings: [],
    filterQuery: '',
    threadClearedManually: false,
    isOpen: false,
    unreadTotal: 0,
    pendingTotal: 0,
};
const adminChatRoutes = {
    threads: @json(route('licenses.admin.chat.threads')),
    thread: @json(route('licenses.chat.thread')),
    send: @json(route('licenses.chat.send')),
    decisionBase: @json(url('/admin/licenses/billing')),
};
const adminChatCsrf = @json(csrf_token());

function escapeAdminChatHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function buildAdminChatInitials(userName, userEmail, userId) {
    const source = String(userName || '').trim() || String(userEmail || '').trim() || ('U' + String(userId || ''));
    const words = source.split(/\s+/).filter(Boolean);
    if (words.length >= 2) {
        return (words[0][0] + words[1][0]).toUpperCase();
    }

    const compact = source.replace(/[^a-zA-Z0-9]/g, '');
    if (compact.length >= 2) {
        return compact.slice(0, 2).toUpperCase();
    }

    return source.slice(0, 2).toUpperCase();
}

function buildAdminChatAvatarUrl(thread, initials) {
    const direct = String(
        thread?.avatar_url
        || thread?.user_avatar_url
        || thread?.user_avatar
        || thread?.profile_photo_url
        || ''
    ).trim();
    if (direct !== '') {
        return direct;
    }

    const seed = String(thread?.user_email || thread?.user_name || thread?.user_id || 'user');
    let hash = 0;
    for (let i = 0; i < seed.length; i += 1) {
        hash = ((hash << 5) - hash + seed.charCodeAt(i)) | 0;
    }
    const hueA = Math.abs(hash) % 360;
    const hueB = (hueA + 36) % 360;
    const safeInitials = escapeAdminChatHtml(initials || 'U');
    const svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96">'
        + '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
        + '<stop offset="0" stop-color="hsl(' + String(hueA) + ',72%,56%)"/>'
        + '<stop offset="1" stop-color="hsl(' + String(hueB) + ',72%,40%)"/>'
        + '</linearGradient></defs>'
        + '<rect width="96" height="96" rx="48" fill="url(#g)"/>'
        + '<text x="48" y="56" text-anchor="middle" font-size="28" font-family="Segoe UI, Arial" font-weight="700" fill="rgba(255,255,255,0.95)">' + safeInitials + '</text>'
        + '</svg>';
    return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg);
}

function clearAdminChatSelection(statusMessage = 'Thread ditutup. Pilih user untuk membuka chat.') {
    adminChatState.selectedUserId = 0;
    adminChatState.messages = [];
    adminChatState.pendingBillings = [];
    adminChatState.threadClearedManually = true;
    renderAdminChatThreads();
    renderAdminChatMessages();
    const statusEl = document.getElementById('admin-chat-status');
    if (statusEl) {
        statusEl.textContent = statusMessage;
    }
}

function renderAdminChatThreads() {
    const target = document.getElementById('admin-chat-threads');
    if (!target) return;

    adminChatState.unreadTotal = Array.isArray(adminChatState.threads)
        ? adminChatState.threads.reduce((sum, thread) => sum + Math.max(0, Number(thread.unread_count || 0)), 0)
        : 0;
    adminChatState.pendingTotal = Array.isArray(adminChatState.threads)
        ? adminChatState.threads.reduce((sum, thread) => sum + Math.max(0, Number(thread.pending_billing_count || 0)), 0)
        : 0;
    renderAdminChatBadges();

    const allThreads = Array.isArray(adminChatState.threads) ? adminChatState.threads : [];
    const filter = String(adminChatState.filterQuery || '').trim().toLowerCase();
    const filteredThreads = filter === ''
        ? allThreads
        : allThreads.filter((thread) => {
            const haystack = [thread.user_name, thread.user_email, thread.latest_message]
                .map((item) => String(item || '').toLowerCase())
                .join(' ');
            return haystack.includes(filter);
        });

    if (adminChatState.selectedUserId <= 0 && filteredThreads.length > 0 && !adminChatState.threadClearedManually) {
        adminChatState.selectedUserId = Number(filteredThreads[0].user_id || 0);
    }

    if (filteredThreads.length === 0) {
        target.innerHTML = '<div class="chat-empty">Tidak ada user yang cocok.</div>';
        return;
    }

    target.innerHTML = filteredThreads.map((thread) => {
        const isActive = Number(thread.user_id || 0) === Number(adminChatState.selectedUserId || 0);
        const initials = buildAdminChatInitials(thread.user_name, thread.user_email, thread.user_id);
        const avatarUrl = buildAdminChatAvatarUrl(thread, initials);
        const label = [thread.user_name, thread.user_email].filter(Boolean).join(' • ');
        return '<button type="button" class="admin-chat-user-item' + (isActive ? ' is-active' : '') + '" data-chat-user-id="' + escapeAdminChatHtml(thread.user_id) + '">'
            + '<span class="admin-chat-user-avatar" title="' + escapeAdminChatHtml(label || ('User #' + String(thread.user_id || ''))) + '">'
            + '<img src="' + escapeAdminChatHtml(avatarUrl) + '" alt="' + escapeAdminChatHtml(initials) + '" loading="lazy" referrerpolicy="no-referrer">'
            + '</span>'
            + '</button>';
    }).join('');
}

function renderAdminChatBadges() {
    const unreadEl = document.getElementById('admin-chat-unread');
    const pendingEl = document.getElementById('admin-chat-pending');
    const headStatusEl = document.getElementById('admin-chat-head-status');

    if (headStatusEl) {
        headStatusEl.textContent = 'Unread chat: ' + String(adminChatState.unreadTotal || 0) + ' • Pending billing: ' + String(adminChatState.pendingTotal || 0);
    }

    if (unreadEl instanceof HTMLElement) {
        const count = Math.max(0, Number(adminChatState.unreadTotal || 0));
        if (count <= 0 || adminChatState.isOpen) {
            unreadEl.classList.add('is-hidden');
            unreadEl.textContent = '0';
        } else {
            unreadEl.classList.remove('is-hidden');
            unreadEl.textContent = count > 99 ? '99+' : String(count);
        }
    }

    if (pendingEl instanceof HTMLElement) {
        const count = Math.max(0, Number(adminChatState.pendingTotal || 0));
        if (count <= 0 || adminChatState.isOpen) {
            pendingEl.classList.add('is-hidden');
            pendingEl.textContent = '0';
        } else {
            pendingEl.classList.remove('is-hidden');
            pendingEl.textContent = count > 99 ? '99+' : String(count);
        }
    }
}

function renderAdminChatMessages(forceScrollBottom = false) {
    const target = document.getElementById('admin-chat-messages');
    const pendingListEl = document.getElementById('admin-chat-pending-list');
    const title = document.getElementById('admin-chat-title');
    const subtitle = document.getElementById('admin-chat-subtitle');
    const status = document.getElementById('admin-chat-status');
    const sendBtn = document.getElementById('admin-chat-send');
    const inputEl = document.getElementById('admin-chat-input');
    const clearBtn = document.getElementById('admin-chat-clear-thread');
    const activeThread = (Array.isArray(adminChatState.threads) ? adminChatState.threads : [])
        .find((thread) => Number(thread.user_id || 0) === Number(adminChatState.selectedUserId || 0));

    if (title) title.textContent = activeThread ? activeThread.user_name : 'Pilih percakapan';
    if (subtitle) subtitle.textContent = activeThread
        ? ((activeThread.user_email || '-') + ' • Last update ' + (activeThread.latest_label || '-'))
        : 'Thread chat billing user akan tampil di sini.';
    if (status) status.textContent = activeThread ? 'Balas sebagai admin billing.' : 'Pilih user dulu untuk mulai chat.';
    if (sendBtn) sendBtn.disabled = !activeThread;
    if (clearBtn instanceof HTMLButtonElement) {
        clearBtn.disabled = !activeThread;
    }
    if (inputEl instanceof HTMLTextAreaElement) {
        inputEl.disabled = !activeThread;
        inputEl.placeholder = activeThread ? 'Tulis balasan admin...' : 'Pilih user dulu untuk mulai balas chat...';
    }

    if (!target) return;
    const previousBottomDistance = Math.max(0, target.scrollHeight - target.scrollTop - target.clientHeight);
    const shouldScrollToBottom = forceScrollBottom || previousBottomDistance <= 72;
    if (!activeThread) {
        target.innerHTML = '<div class="chat-empty">Pilih salah satu user di daftar kiri untuk membuka percakapan.</div>';
        if (pendingListEl) {
            pendingListEl.innerHTML = '<div class="small text-secondary">Belum ada pending billing untuk user terpilih.</div>';
        }
        return;
    }

    if (!Array.isArray(adminChatState.messages) || adminChatState.messages.length === 0) {
        target.innerHTML = '<div class="chat-empty">Belum ada pesan. Admin bisa mulai percakapan dari form di bawah.</div>';
        return;
    }

    target.innerHTML = adminChatState.messages.map((message) => {
        const isAdmin = Boolean(message.sender_is_admin);
        return '<div class="chat-message-row' + (isAdmin ? ' is-admin' : '') + '">'
            + '<div class="chat-bubble">'
            + '<div class="chat-bubble-meta">' + escapeAdminChatHtml(message.sender_name) + ' • ' + escapeAdminChatHtml(message.created_label) + '</div>'
            + '<div class="chat-bubble-text">' + escapeAdminChatHtml(message.message) + '</div>'
            + '</div>'
            + '</div>';
    }).join('');
    if (shouldScrollToBottom) {
        target.scrollTop = target.scrollHeight;
    } else {
        target.scrollTop = Math.max(0, target.scrollHeight - target.clientHeight - previousBottomDistance);
    }

    if (pendingListEl) {
        if (!Array.isArray(adminChatState.pendingBillings) || adminChatState.pendingBillings.length === 0) {
            pendingListEl.innerHTML = '<div class="small text-secondary">Tidak ada pending billing untuk user ini.</div>';
        } else {
            pendingListEl.innerHTML = adminChatState.pendingBillings.map((item) => {
                const amount = Number(item.requested_amount || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                return '<div class="admin-chat-pending-item">'
                    + '<div class="fw-semibold">Account ' + escapeAdminChatHtml(item.account_id) + ' • ' + escapeAdminChatHtml(String(item.requested_plan || '').toUpperCase()) + '</div>'
                    + '<div class="small text-secondary">' + escapeAdminChatHtml(String(item.requested_months || 0)) + ' bulan • Rp ' + escapeAdminChatHtml(amount) + ' • ' + escapeAdminChatHtml(item.created_label || '-') + '</div>'
                    + '<div class="admin-chat-pending-actions">'
                    + '<button type="button" class="admin-chat-action-btn is-approve" data-billing-id="' + escapeAdminChatHtml(item.id) + '" data-billing-decision="approve" title="Approve billing">'
                    + '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M5 12.5 9.2 17 19 7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                    + '</button>'
                    + '<button type="button" class="admin-chat-action-btn is-reject" data-billing-id="' + escapeAdminChatHtml(item.id) + '" data-billing-decision="reject" title="Reject billing">'
                    + '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>'
                    + '</button>'
                    + '</div>'
                    + '</div>';
            }).join('');
        }
    }
}

async function loadAdminChatThreads() {
    const response = await fetch(adminChatRoutes.threads, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
        cache: 'no-store',
    });
    if (!response.ok) return;
    const payload = await response.json();
    adminChatState.threads = Array.isArray(payload.threads) ? payload.threads : [];
    if (adminChatState.selectedUserId > 0) {
        const selectedExists = adminChatState.threads.some((thread) => Number(thread.user_id || 0) === Number(adminChatState.selectedUserId || 0));
        if (!selectedExists) {
            adminChatState.selectedUserId = 0;
            adminChatState.messages = [];
            adminChatState.pendingBillings = [];
            adminChatState.threadClearedManually = false;
        }
    }
    if (!adminChatState.selectedUserId && adminChatState.threads.length > 0 && !adminChatState.threadClearedManually) {
        adminChatState.selectedUserId = Number(adminChatState.threads[0].user_id || 0);
    }
    renderAdminChatThreads();
}

function setAdminChatOpen(open) {
    const card = document.getElementById('admin-chat-card');
    const toggle = document.getElementById('admin-chat-toggle');
    if (!(card instanceof HTMLElement) || !(toggle instanceof HTMLButtonElement)) return;

    adminChatState.isOpen = Boolean(open);
    card.classList.toggle('is-open', adminChatState.isOpen);
    toggle.classList.toggle('is-open', adminChatState.isOpen);
    renderAdminChatBadges();

    if (adminChatState.isOpen) {
        loadAdminChatThreads();
        if (adminChatState.selectedUserId) {
            loadAdminChatThread(adminChatState.selectedUserId);
        }
        const input = document.getElementById('admin-chat-input');
        if (input instanceof HTMLTextAreaElement) {
            setTimeout(() => input.focus(), 60);
        }
    }

    syncAdminChatViewport();
}

function syncAdminChatViewport() {
    const card = document.getElementById('admin-chat-card');
    if (!(card instanceof HTMLElement)) return;

    if (!adminChatState.isOpen) {
        card.style.removeProperty('height');
        card.style.removeProperty('bottom');
        card.style.removeProperty('top');
        return;
    }

    const isMobileViewport = window.matchMedia('(max-width: 991.98px)').matches;
    if (!isMobileViewport) {
        card.style.removeProperty('height');
        card.style.removeProperty('bottom');
        card.style.removeProperty('top');
        return;
    }

    const viewport = window.visualViewport;
    if (!viewport) {
        card.style.height = 'calc(100dvh - 1.1rem - env(safe-area-inset-top) - env(safe-area-inset-bottom))';
        card.style.bottom = 'max(.6rem, env(safe-area-inset-bottom))';
        return;
    }

    const viewportHeight = Math.max(320, viewport.height);
    const layoutHeight = Math.max(window.innerHeight || 0, viewportHeight);
    const keyboardInset = Math.max(0, layoutHeight - viewportHeight - viewport.offsetTop);
    const bottomGap = Math.max(8, keyboardInset + 8);
    const topGap = Math.max(8, viewport.offsetTop + 8);
    const nextHeight = Math.max(300, viewportHeight - 16);

    card.style.bottom = bottomGap + 'px';
    card.style.height = 'calc(' + String(Math.round(nextHeight)) + 'px - env(safe-area-inset-bottom))';
    card.style.top = topGap + 'px';
}

async function loadAdminChatThread(userId) {
    if (!userId) return;
    const response = await fetch(adminChatRoutes.thread + '?user_id=' + encodeURIComponent(String(userId)), {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
        cache: 'no-store',
    });
    if (!response.ok) return;
    const payload = await response.json();
    adminChatState.selectedUserId = Number(payload.thread_user_id || userId || 0);
    adminChatState.messages = Array.isArray(payload.messages) ? payload.messages : [];
    adminChatState.pendingBillings = Array.isArray(payload.pending_billings) ? payload.pending_billings : [];
    adminChatState.threadClearedManually = false;
    renderAdminChatThreads();
    renderAdminChatMessages(true);
}

async function processAdminChatDecision(billingId, decision) {
    const resolvedBillingId = Math.max(0, Number(billingId || 0));
    const resolvedDecision = String(decision || '').toLowerCase();
    if (!resolvedBillingId || !['approve', 'reject'].includes(resolvedDecision)) return;

    const statusEl = document.getElementById('admin-chat-status');
    if (statusEl) {
        statusEl.textContent = resolvedDecision === 'approve' ? 'Memproses approve...' : 'Memproses reject...';
    }

    const response = await fetch(adminChatRoutes.decisionBase + '/' + String(resolvedBillingId) + '/decision-json', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': adminChatCsrf,
        },
        credentials: 'same-origin',
        body: JSON.stringify({ decision: resolvedDecision, user_id: adminChatState.selectedUserId || 0 }),
    });

    const payload = await response.json();
    if (!response.ok || !payload.success) {
        if (statusEl) {
            statusEl.textContent = String(payload?.message || 'Gagal memproses request billing.');
        }
        return;
    }

    adminChatState.threads = Array.isArray(payload.threads) ? payload.threads : adminChatState.threads;
    adminChatState.messages = Array.isArray(payload.messages) ? payload.messages : adminChatState.messages;
    adminChatState.pendingBillings = Array.isArray(payload.pending_billings) ? payload.pending_billings : adminChatState.pendingBillings;
    adminChatState.selectedUserId = Number(payload.thread_user_id || adminChatState.selectedUserId || 0);
    adminChatState.threadClearedManually = false;
    renderAdminChatThreads();
    renderAdminChatMessages(true);
    if (statusEl) {
        statusEl.textContent = String(payload.message || 'Request billing berhasil diproses.');
    }
}

document.getElementById('admin-chat-search')?.addEventListener('input', (event) => {
    adminChatState.filterQuery = String(event.target?.value || '');
    renderAdminChatThreads();
});

document.getElementById('admin-chat-clear-thread')?.addEventListener('click', () => {
    clearAdminChatSelection();
});

document.getElementById('admin-chat-threads')?.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target.closest('[data-chat-user-id]') : null;
    if (!(target instanceof HTMLElement)) return;
    const userId = Number(target.getAttribute('data-chat-user-id') || 0);
    if (!userId) return;
    adminChatState.threadClearedManually = false;
    adminChatState.selectedUserId = userId;
    adminChatState.messages = [];
    adminChatState.pendingBillings = [];
    renderAdminChatThreads();
    renderAdminChatMessages();
    loadAdminChatThread(userId);
});

document.getElementById('admin-chat-pending-list')?.addEventListener('click', async (event) => {
    const target = event.target instanceof Element ? event.target.closest('[data-billing-id][data-billing-decision]') : null;
    if (!(target instanceof HTMLElement)) return;
    const billingId = Number(target.getAttribute('data-billing-id') || 0);
    const decision = String(target.getAttribute('data-billing-decision') || '');
    if (decision === 'reject') {
        const ok = window.confirm('Reject request billing ini? User harus submit ulang jika ditolak.');
        if (!ok) return;
    }
    await processAdminChatDecision(billingId, decision);
});

document.getElementById('admin-chat-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!adminChatState.selectedUserId) return;

    const input = document.getElementById('admin-chat-input');
    const sendBtn = document.getElementById('admin-chat-send');
    if (!(input instanceof HTMLTextAreaElement) || !(sendBtn instanceof HTMLButtonElement)) return;

    const message = input.value.trim();
    if (!message) return;

    sendBtn.disabled = true;
    const response = await fetch(adminChatRoutes.send, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': adminChatCsrf,
        },
        credentials: 'same-origin',
        body: JSON.stringify({ user_id: adminChatState.selectedUserId, message }),
    });
    sendBtn.disabled = false;
    if (!response.ok) return;

    const payload = await response.json();
    adminChatState.messages = Array.isArray(payload.messages) ? payload.messages : adminChatState.messages;
    if (Array.isArray(payload.threads)) {
        adminChatState.threads = payload.threads;
    }
    input.value = '';
    renderAdminChatThreads();
    renderAdminChatMessages(true);
});

document.getElementById('admin-chat-input')?.addEventListener('keydown', (event) => {
    if (!(event.target instanceof HTMLTextAreaElement)) return;
    if (event.key !== 'Enter' || event.shiftKey) return;
    event.preventDefault();
    document.getElementById('admin-chat-form')?.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
});

document.getElementById('admin-chat-toggle')?.addEventListener('click', () => {
    setAdminChatOpen(!adminChatState.isOpen);
});

document.getElementById('admin-chat-close')?.addEventListener('click', () => {
    setAdminChatOpen(false);
});

document.addEventListener('pointerdown', (event) => {
    if (!adminChatState.isOpen) return;
    const card = document.getElementById('admin-chat-card');
    const toggle = document.getElementById('admin-chat-toggle');
    const target = event.target;
    if (!(target instanceof Node) || !(card instanceof HTMLElement) || !(toggle instanceof HTMLElement)) return;
    if (card.contains(target) || toggle.contains(target)) return;
    setAdminChatOpen(false);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && adminChatState.isOpen) {
        setAdminChatOpen(false);
    }
});

window.addEventListener('resize', syncAdminChatViewport, { passive: true });
window.visualViewport?.addEventListener('resize', syncAdminChatViewport, { passive: true });
window.visualViewport?.addEventListener('scroll', syncAdminChatViewport, { passive: true });
document.getElementById('admin-chat-input')?.addEventListener('focus', () => {
    setTimeout(syncAdminChatViewport, 120);
});
document.getElementById('admin-chat-input')?.addEventListener('blur', () => {
    setTimeout(syncAdminChatViewport, 120);
});

renderAdminChatThreads();
renderAdminChatMessages();
syncAdminChatViewport();
setInterval(() => {
    loadAdminChatThreads();
    if (adminChatState.isOpen && adminChatState.selectedUserId) {
        loadAdminChatThread(adminChatState.selectedUserId);
    }
}, 7000);

document.body.setAttribute('data-theme', document.documentElement.getAttribute('data-theme') || 'light');
</script>
</body>
</html>
