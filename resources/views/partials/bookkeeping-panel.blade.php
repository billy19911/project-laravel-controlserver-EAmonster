<style>
    #bookkeepingSection.bookkeeping-panel {
        display: none;
        margin-top: 8px;
        border: 1px solid var(--monster-border, rgba(148, 163, 184, 0.28));
        border-radius: 14px;
        background: var(--monster-surface, rgba(255, 255, 255, 0.88));
        padding: 16px;
        color: var(--monster-ink, #0f172a);
    }

    #bookkeepingSection .bk-settings-box {
        border: 1px solid var(--monster-border, rgba(148, 163, 184, 0.28));
        border-radius: 10px;
        padding: 10px;
        margin-bottom: 12px;
        background: var(--monster-chip-bg, rgba(148, 163, 184, 0.1));
    }

    #bookkeepingSection .bk-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 12px;
        align-items: flex-end;
    }

    #bookkeepingSection .bk-control {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    #bookkeepingSection .bk-rate-meta {
        font-size: 12px;
        color: var(--monster-text-secondary, #64748b);
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
        margin-top: 4px;
    }

    #bookkeepingSection .bk-live-dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #f59e0b;
        display: inline-block;
        vertical-align: middle;
    }

    #bookkeepingSection .bk-live-dot.ok {
        background: #16a34a;
    }

    #bookkeepingSection .bk-table-wrap {
        overflow: auto;
        border: 1px solid var(--monster-border, rgba(148, 163, 184, 0.28));
        border-radius: 10px;
    }

    #bookkeepingSection table {
        width: 100%;
        border-collapse: collapse;
    }

    #bookkeepingSection th,
    #bookkeepingSection td {
        border-bottom: 1px solid var(--monster-border, rgba(148, 163, 184, 0.24));
        padding: 6px;
    }

    #bookkeepingSection input,
    #bookkeepingSection textarea,
    #bookkeepingSection button {
        border-radius: 8px;
    }

    #bookkeepingSection input,
    #bookkeepingSection textarea,
    #bookkeepingSection select {
        background: var(--monster-control-bg, #ffffff);
        border: 1px solid var(--monster-control-border, rgba(148, 163, 184, 0.3));
        color: var(--monster-ink, #0f172a);
        padding: 0.42rem 0.56rem;
    }

    #bookkeepingSection button {
        background: linear-gradient(120deg, rgba(37, 99, 235, 0.2), rgba(212, 160, 23, 0.2));
        border: 1px solid var(--monster-control-border, rgba(148, 163, 184, 0.32));
        color: var(--monster-ink, #0f172a);
        padding: 0.4rem 0.65rem;
        font-weight: 600;
        white-space: nowrap;
    }

    #bookkeepingSection button:hover {
        filter: brightness(1.06);
    }

    #bookkeepingSection .bk-user-picker {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
        margin-top: 6px;
    }

    #bookkeepingSection .bk-user-picker select {
        min-width: 240px;
    }

    #bookkeepingSection #bkSettingsSaveBtn,
    #bookkeepingSection #bkSaveBtn,
    #bookkeepingSection #bkRefreshBtn,
    #bookkeepingSection #bkAddAccountBtn,
    #bookkeepingSection #bkAddUserBtn {
        background: linear-gradient(120deg, rgba(37, 99, 235, 0.28), rgba(37, 99, 235, 0.08));
    }

    #bookkeepingSection #bkClearAccountsBtn {
        background: linear-gradient(120deg, rgba(148, 163, 184, 0.24), rgba(148, 163, 184, 0.08));
    }

    #bookkeepingSection .bk-chip-list {
        margin-top: 8px;
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }

    #bookkeepingSection .bk-chip {
        font-size: 12px;
        border: 1px solid var(--monster-border, rgba(148, 163, 184, 0.32));
        background: var(--monster-chip-bg, rgba(148, 163, 184, 0.12));
        padding: 3px 8px;
        border-radius: 999px;
    }

    #bookkeepingSection .bk-table-wrap table thead th {
        position: sticky;
        top: 0;
        z-index: 1;
        background: var(--monster-surface, rgba(255, 255, 255, 0.94));
    }

    body[data-theme='dark'] #bookkeepingSection .bk-table-wrap table thead th {
        background: rgba(9, 18, 34, 0.96);
    }

    @media (max-width: 768px) {
        #bookkeepingSection.bookkeeping-panel {
            padding: 12px;
        }

        #bookkeepingSection .bk-row {
            gap: 8px;
        }

        #bookkeepingSection .bk-user-picker select,
        #bookkeepingSection #bkAccountPicker {
            min-width: 0;
            width: 100%;
        }
    }

    #bookkeepingSection .bk-data-msg {
        display: none;
        padding: 10px;
        border: 1px dashed var(--monster-border, rgba(148, 163, 184, 0.28));
        border-radius: 8px;
        margin-bottom: 12px;
        color: var(--monster-text-secondary, #64748b);
        background: var(--monster-chip-bg, rgba(148, 163, 184, 0.1));
    }
</style>

<section id="bookkeepingSection" class="bookkeeping-panel">
    <h3 style="margin:0 0 10px;">Pembukuan Daily Profit</h3>
    <div id="bkSettingsBox" class="bk-settings-box">
        <div style="font-weight:600; margin-bottom:8px;">Setting Pembukuan</div>
        <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:8px;">
            <label style="display:flex; align-items:center; gap:6px;">
                <input type="checkbox" id="bkEnabled" />
                Aktifkan fitur pembukuan
            </label>
        </div>
        <label style="display:block; margin-bottom:6px;">Whitelist user ID (pisahkan koma/baris baru)</label>
        <textarea id="bkWhitelistText" rows="3" style="width:100%;"></textarea>
        <div class="bk-user-picker">
            <select id="bkUserPicker"></select>
            <button type="button" id="bkAddUserBtn">Tambah User ke Whitelist</button>
        </div>
        <div id="bkWhitelistChips" class="bk-chip-list"></div>
        <div style="margin-top:8px; display:flex; gap:8px; align-items:center;">
            <button type="button" id="bkSettingsSaveBtn">Simpan Setting Pembukuan</button>
            <span id="bkSettingsMsg" style="font-size:12px; color:#4b5563;"></span>
        </div>
    </div>

    <div id="bkDataMsg" class="bk-data-msg"></div>

    <div id="bkDataArea">
    <div class="bk-row">
        <label class="bk-control">
            Tambah Akun ke Tabel
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <select id="bkAccountPicker" style="min-width:220px;"></select>
                <button type="button" id="bkAddAccountBtn">Tambah</button>
                <button type="button" id="bkClearAccountsBtn">Kosongkan Tabel</button>
            </div>
        </label>
        <label class="bk-control">
            Tanggal
            <input type="date" id="bkDate" />
        </label>
        <label class="bk-control">
            Kurs IDR
            <input type="number" id="bkRate" min="1" step="1" value="16000" />
            <div class="bk-rate-meta">
                <label style="display:flex; align-items:center; gap:6px; margin:0;">
                    <input type="checkbox" id="bkLiveRateEnabled" checked />
                    Live USD/IDR
                </label>
                <span><span id="bkLiveRateDot" class="bk-live-dot"></span> <span id="bkLiveRateStatus">Menunggu kurs live...</span></span>
                <span id="bkLiveRateTime"></span>
            </div>
        </label>
        <button type="button" id="bkRefreshBtn">Refresh</button>
        <button type="button" id="bkSaveBtn">Simpan Pembukuan</button>
    </div>

    <div class="bk-table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="text-align:left;">Akun</th>
                    <th style="text-align:left;">Alias</th>
                    <th style="text-align:right;">Balance</th>
                    <th style="text-align:right;">Equity</th>
                    <th style="text-align:right;">Growth Today (USD)</th>
                    <th style="text-align:center;">Currency</th>
                    <th style="text-align:right;">Daily Profit (USD)</th>
                    <th style="text-align:right;">Profit (IDR)</th>
                </tr>
            </thead>
            <tbody id="bkBody"></tbody>
        </table>
    </div>

    <div id="bkSummary" style="margin-top:10px; font-weight:600;"></div>
    </div>
</section>

<script>
(function () {
    const BK_BASE = '/dashboard/bookkeeping';
    const bkSection = document.getElementById('bookkeepingSection');
    const bkBody = document.getElementById('bkBody');
    const bkDate = document.getElementById('bkDate');
    const bkRate = document.getElementById('bkRate');
    const bkSummary = document.getElementById('bkSummary');
    const bkSettingsBox = document.getElementById('bkSettingsBox');
    const bkEnabled = document.getElementById('bkEnabled');
    const bkWhitelistText = document.getElementById('bkWhitelistText');
    const bkSettingsSaveBtn = document.getElementById('bkSettingsSaveBtn');
    const bkSettingsMsg = document.getElementById('bkSettingsMsg');
    const bkDataMsg = document.getElementById('bkDataMsg');
    const bkDataArea = document.getElementById('bkDataArea');
    const bkLiveRateEnabled = document.getElementById('bkLiveRateEnabled');
    const bkLiveRateDot = document.getElementById('bkLiveRateDot');
    const bkLiveRateStatus = document.getElementById('bkLiveRateStatus');
    const bkLiveRateTime = document.getElementById('bkLiveRateTime');
    const bkAccountPicker = document.getElementById('bkAccountPicker');
    const bkAddAccountBtn = document.getElementById('bkAddAccountBtn');
    const bkClearAccountsBtn = document.getElementById('bkClearAccountsBtn');
    const bkUserPicker = document.getElementById('bkUserPicker');
    const bkAddUserBtn = document.getElementById('bkAddUserBtn');
    const bkWhitelistChips = document.getElementById('bkWhitelistChips');
    let bkRateTimer = null;
    let bkRateRequestInFlight = false;
    let bkLastLiveRate = 0;
    let bkAllRows = [];
    let bkSelectedAccountIds = [];
    let bkPolicy = { enabled: false, user_whitelist: [], can_manage_settings: false, available_users: [] };

    function getSelectedAccountId() {
        const accountSelect = document.getElementById('account_id');
        return accountSelect ? String(accountSelect.value || '').trim() : '';
    }

    function todayYmd() {
        return new Date().toISOString().slice(0, 10);
    }

    function toNum(v) {
        const n = Number(v);
        return Number.isFinite(n) ? n : 0;
    }

    function fmtMoney(v) {
        return toNum(v).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function fmtIdr(v) {
        return toNum(v).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }

    function updateLiveRateUi(ok, message, rateValue) {
        if (ok) {
            bkLiveRateDot.classList.add('ok');
            bkLiveRateStatus.textContent = message || 'Kurs live aktif';
            if (Number.isFinite(rateValue) && rateValue > 0) {
                bkLiveRateTime.textContent = 'USD/IDR: ' + fmtIdr(rateValue) + ' • ' + new Date().toLocaleTimeString('id-ID');
            }
        } else {
            bkLiveRateDot.classList.remove('ok');
            bkLiveRateStatus.textContent = message || 'Gagal ambil kurs live';
        }
    }

    async function fetchLiveUsdIdrRate() {
        if (bkRateRequestInFlight) return null;
        bkRateRequestInFlight = true;

        const sources = [
            {
                url: 'https://open.er-api.com/v6/latest/USD',
                parse: (data) => Number(data && data.rates ? data.rates.IDR : 0),
                name: 'ER-API'
            },
            {
                url: 'https://api.exchangerate.host/latest?base=USD&symbols=IDR',
                parse: (data) => Number(data && data.rates ? data.rates.IDR : 0),
                name: 'ExchangeRateHost'
            },
            {
                url: 'https://latest.currency-api.pages.dev/v1/currencies/usd.json',
                parse: (data) => Number(data && data.usd ? data.usd.idr : 0),
                name: 'CurrencyAPI'
            }
        ];

        try {
            for (const src of sources) {
                try {
                    const controller = new AbortController();
                    const timer = setTimeout(function () { controller.abort(); }, 5000);
                    const res = await fetch(src.url, { signal: controller.signal, headers: { 'Accept': 'application/json' } });
                    clearTimeout(timer);
                    if (!res.ok) continue;
                    const data = await res.json();
                    const rate = src.parse(data);
                    if (Number.isFinite(rate) && rate > 1000) {
                        bkLastLiveRate = rate;
                        return { rate: rate, source: src.name };
                    }
                } catch (_err) {
                }
            }
            return null;
        } finally {
            bkRateRequestInFlight = false;
        }
    }

    async function refreshLiveRateIfNeeded(force) {
        if (!bkLiveRateEnabled.checked && !force) {
            bkRate.readOnly = false;
            updateLiveRateUi(false, 'Live dimatikan (manual).');
            return;
        }

        bkRate.readOnly = true;
        updateLiveRateUi(false, 'Mengambil kurs live...');
        const live = await fetchLiveUsdIdrRate();
        if (!live) {
            updateLiveRateUi(false, 'Kurs live belum tersedia, pakai nilai terakhir.');
            return;
        }

        bkRate.value = String(Math.round(live.rate));
        recalcSummary();
        updateLiveRateUi(true, 'Live dari ' + live.source, live.rate);
    }

    function startLiveRateLoop() {
        if (bkRateTimer) {
            clearInterval(bkRateTimer);
            bkRateTimer = null;
        }

        refreshLiveRateIfNeeded(true).catch(function () {});
        bkRateTimer = setInterval(function () {
            refreshLiveRateIfNeeded(false).catch(function () {});
        }, 30000);
    }

    async function fetchJson(url, options) {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const method = String((options && options.method) || 'GET').toUpperCase();
        const r = await fetch(url, {
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(method !== 'GET' ? { 'X-CSRF-TOKEN': csrf } : {}),
                ...(options && options.headers ? options.headers : {})
            },
            ...(options || {})
        });
        return r.json();
    }

    function setSettingsMessage(msg, isError) {
        bkSettingsMsg.textContent = msg || '';
        bkSettingsMsg.style.color = isError ? '#ef4444' : 'var(--monster-text-secondary, #64748b)';
    }

    function setDataMessage(msg, show) {
        bkDataMsg.textContent = msg || '';
        bkDataMsg.style.display = show ? 'block' : 'none';
    }

    function parseWhitelistTextToIds(raw) {
        const parts = String(raw || '').split(/[\s,;\n\r\t]+/).filter(Boolean);
        const ids = [];
        parts.forEach((part) => {
            const id = Number(part);
            if (Number.isFinite(id) && id > 0) ids.push(String(Math.trunc(id)));
        });
        return Array.from(new Set(ids));
    }

    function syncWhitelistTextFromIds(ids) {
        bkWhitelistText.value = ids.join(',');
    }

    function renderWhitelistChips() {
        const idSet = parseWhitelistTextToIds(bkWhitelistText.value);
        if (idSet.length === 0) {
            bkWhitelistChips.innerHTML = '<span class="bk-chip">Belum ada user di whitelist</span>';
            return;
        }

        const map = new Map((bkPolicy.available_users || []).map((u) => [String(u.id), u]));
        bkWhitelistChips.innerHTML = idSet.map((id) => {
            const user = map.get(id);
            const label = user ? (id + ' - ' + (user.username || user.name || 'user')) : id;
            return '<span class="bk-chip">' + label + '</span>';
        }).join('');
    }

    function renderUserPickerOptions() {
        if (!bkUserPicker) return;
        const users = Array.isArray(bkPolicy.available_users) ? bkPolicy.available_users : [];
        if (users.length === 0) {
            bkUserPicker.innerHTML = '<option value="">Tidak ada user</option>';
            return;
        }

        bkUserPicker.innerHTML = '<option value="">Pilih user...</option>' + users.map((u) => {
            const label = (u.id + ' - ' + (u.username || u.name || u.email || 'user'));
            return '<option value="' + String(u.id) + '">' + label + '</option>';
        }).join('');
    }

    async function loadBookkeepingSettings() {
        const data = await fetchJson(BK_BASE + '/settings');
        if (!data.success) {
            bkPolicy.can_manage_settings = false;
            bkSettingsBox.style.display = 'none';
            return;
        }

        const s = data.settings || {};
        bkPolicy = {
            enabled: !!s.enabled,
            user_whitelist: Array.isArray(s.user_whitelist) ? s.user_whitelist : [],
            can_manage_settings: true,
            available_users: Array.isArray(s.available_users) ? s.available_users : []
        };

        bkEnabled.checked = !!bkPolicy.enabled;
        bkWhitelistText.value = String(s.user_whitelist_text || '');
        renderUserPickerOptions();
        renderWhitelistChips();
        bkSettingsBox.style.display = 'block';
        setSettingsMessage('Setting pembukuan dimuat.', false);
    }

    async function saveBookkeepingSettings() {
        const payload = {
            enabled: !!bkEnabled.checked,
            user_whitelist_text: String(bkWhitelistText.value || '')
        };

        const data = await fetchJson(BK_BASE + '/settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (!data.success) {
            setSettingsMessage(data.message || 'Gagal menyimpan setting pembukuan.', true);
            return;
        }

        const s = data.settings || {};
        bkPolicy = {
            enabled: !!s.enabled,
            user_whitelist: Array.isArray(s.user_whitelist) ? s.user_whitelist : [],
            can_manage_settings: true,
            available_users: Array.isArray(s.available_users) ? s.available_users : (bkPolicy.available_users || [])
        };
        bkWhitelistText.value = String(s.user_whitelist_text || '');
        renderWhitelistChips();
        setSettingsMessage(data.message || 'Setting pembukuan berhasil disimpan.', false);
        await loadBookkeeping();
    }

    function renderRows(rows, rate) {
        if (!Array.isArray(rows) || rows.length === 0) {
            bkBody.innerHTML = '<tr><td colspan="8" style="padding:10px; color:var(--monster-text-secondary, #64748b);">Tabel kosong. Tambah akun dulu dari dropdown di atas.</td></tr>';
            bkSummary.textContent = 'Total USD: 0.00 | Total IDR: 0';
            return;
        }

        bkBody.innerHTML = rows.map((row) => {
            const growthToday = toNum(row.growth_today_usd || 0);
            const usd = row.daily_profit_usd == null ? growthToday : toNum(row.daily_profit_usd);
            const idr = row.profit_idr == null ? toNum(usd) * rate : toNum(row.profit_idr);
            const growthColor = growthToday >= 0 ? '#16a34a' : '#dc2626';
            return '<tr>' +
                '<td style="padding:6px; border-bottom:1px solid var(--monster-border, rgba(148, 163, 184, 0.24));">' + row.account_id + '</td>' +
                '<td style="padding:6px; border-bottom:1px solid var(--monster-border, rgba(148, 163, 184, 0.24));">' + (row.account_alias || '-') + '</td>' +
                '<td style="padding:6px; border-bottom:1px solid var(--monster-border, rgba(148, 163, 184, 0.24)); text-align:right;">' + fmtMoney(row.balance) + '</td>' +
                '<td style="padding:6px; border-bottom:1px solid var(--monster-border, rgba(148, 163, 184, 0.24)); text-align:right;">' + fmtMoney(row.equity) + '</td>' +
                '<td style="padding:6px; border-bottom:1px solid var(--monster-border, rgba(148, 163, 184, 0.24)); text-align:right; color:' + growthColor + '; font-weight:600;">' + fmtMoney(growthToday) + '</td>' +
                '<td style="padding:6px; border-bottom:1px solid var(--monster-border, rgba(148, 163, 184, 0.24)); text-align:center;">' + (row.account_currency || 'USD') + '</td>' +
                '<td style="padding:6px; border-bottom:1px solid var(--monster-border, rgba(148, 163, 184, 0.24)); text-align:right;">' +
                    '<input type="number" step="0.01" data-account="' + row.account_id + '" class="bk-profit" value="' + usd + '" style="width:130px; text-align:right;" />' +
                '</td>' +
                '<td style="padding:6px; border-bottom:1px solid var(--monster-border, rgba(148, 163, 184, 0.24)); text-align:right;" data-idr="' + row.account_id + '">' + fmtIdr(idr) + '</td>' +
            '</tr>';
        }).join('');

        recalcSummary();
    }

    function renderAccountPicker() {
        const selected = new Set(bkSelectedAccountIds.map(String));
        const options = (bkAllRows || []).filter((row) => !selected.has(String(row.account_id)));
        if (options.length === 0) {
            bkAccountPicker.innerHTML = '<option value="">Tidak ada akun tersisa</option>';
            return;
        }

        bkAccountPicker.innerHTML = '<option value="">Pilih akun...</option>' + options.map((row) => {
            const alias = row.account_alias ? (' - ' + row.account_alias) : '';
            return '<option value="' + row.account_id + '">' + row.account_id + alias + '</option>';
        }).join('');
    }

    function renderSelectedAccounts() {
        const selectedSet = new Set(bkSelectedAccountIds.map(String));
        const rows = (bkAllRows || []).filter((row) => selectedSet.has(String(row.account_id)));
        renderRows(rows, toNum(bkRate.value));
        renderAccountPicker();
    }

    function recalcSummary() {
        const rate = toNum(bkRate.value || 0);
        let totalUsd = 0;
        let totalIdr = 0;
        document.querySelectorAll('.bk-profit[data-account]').forEach((el) => {
            const usd = toNum(el.value || 0);
            const accountId = el.getAttribute('data-account');
            const idr = usd * rate;
            totalUsd += usd;
            totalIdr += idr;
            const td = document.querySelector('td[data-idr="' + accountId + '"]');
            if (td) td.textContent = fmtIdr(idr);
        });
        bkSummary.textContent = 'Total USD: ' + fmtMoney(totalUsd) + ' | Total IDR: ' + fmtIdr(totalIdr);
    }

    async function loadBookkeeping() {
        bkSection.style.display = 'block';
        await loadBookkeepingSettings();

        const accountId = getSelectedAccountId();
        if (!accountId) {
            setDataMessage('Pilih account terlebih dahulu untuk membuka data pembukuan.', true);
            bkDataArea.style.display = 'none';
            return;
        }

        const vis = await fetchJson(BK_BASE + '/visibility?account_id=' + encodeURIComponent(accountId));
        if (!vis.can_manage_settings) {
            bkSettingsBox.style.display = 'none';
        }
        if (!vis.allowed) {
            setDataMessage(vis.message || 'Tab pembukuan hanya untuk admin atau user yang di-whitelist.', true);
            bkDataArea.style.display = 'none';
            return;
        }

        setDataMessage('', false);
        bkDataArea.style.display = 'block';
        if (!bkDate.value) bkDate.value = todayYmd();
        startLiveRateLoop();

        const data = await fetchJson(BK_BASE + '?account_id=' + encodeURIComponent(accountId) + '&date=' + encodeURIComponent(bkDate.value));
        if (!data.success) {
            bkBody.innerHTML = '<tr><td colspan="8" style="padding:10px; color:#b91c1c;">Gagal memuat data pembukuan.</td></tr>';
            return;
        }

        if (!bkRate.value || toNum(bkRate.value) <= 0) {
            bkRate.value = String(toNum(data.rate_idr_default || 16000));
        }

        bkAllRows = Array.isArray(data.rows) ? data.rows.slice() : [];
        bkSelectedAccountIds = (bkAllRows || [])
            .filter((row) => row.daily_profit_usd != null)
            .map((row) => String(row.account_id));

        renderSelectedAccounts();
        if (data.summary && data.summary.total_growth_today_usd != null) {
            bkSummary.textContent = bkSummary.textContent + ' | Total Growth Today: ' + fmtMoney(data.summary.total_growth_today_usd);
        }
    }

    async function saveBookkeeping() {
        const accountId = getSelectedAccountId();
        if (!accountId) return;

        const entries = Array.from(document.querySelectorAll('.bk-profit[data-account]')).map((el) => ({
            account_id: String(el.getAttribute('data-account') || ''),
            daily_profit_usd: toNum(el.value || 0)
        }));

        if (entries.length === 0) {
            alert('Tambahkan akun ke tabel terlebih dahulu.');
            return;
        }

        const payload = {
            account_id: accountId,
            date: bkDate.value || todayYmd(),
            exchange_rate_idr: toNum(bkRate.value || 0),
            entries: entries
        };

        const data = await fetchJson(BK_BASE + '/save-batch', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (!data.success) {
            alert(data.message || 'Gagal simpan pembukuan');
            return;
        }

        await loadBookkeeping();
    }

    document.getElementById('bkRefreshBtn').addEventListener('click', loadBookkeeping);
    document.getElementById('bkSaveBtn').addEventListener('click', saveBookkeeping);
    bkSettingsSaveBtn.addEventListener('click', saveBookkeepingSettings);
    bkAddAccountBtn.addEventListener('click', function () {
        const id = String((bkAccountPicker && bkAccountPicker.value) || '').trim();
        if (!id) return;
        if (!bkSelectedAccountIds.includes(id)) bkSelectedAccountIds.push(id);
        renderSelectedAccounts();
    });
    bkClearAccountsBtn.addEventListener('click', function () {
        bkSelectedAccountIds = [];
        renderSelectedAccounts();
    });
    bkAddUserBtn.addEventListener('click', function () {
        const picked = String((bkUserPicker && bkUserPicker.value) || '').trim();
        if (!picked) return;
        const ids = parseWhitelistTextToIds(bkWhitelistText.value);
        if (!ids.includes(picked)) ids.push(picked);
        syncWhitelistTextFromIds(ids);
        renderWhitelistChips();
    });
    bkWhitelistText.addEventListener('input', renderWhitelistChips);
    bkDate.addEventListener('change', loadBookkeeping);
    bkLiveRateEnabled.addEventListener('change', function () {
        if (bkLiveRateEnabled.checked) {
            refreshLiveRateIfNeeded(true).catch(function () {});
        } else {
            bkRate.readOnly = false;
            updateLiveRateUi(false, 'Live dimatikan (manual).');
        }
    });
    bkRate.addEventListener('input', renderSelectedAccounts);
    document.addEventListener('input', function (e) {
        if (e.target && e.target.classList.contains('bk-profit')) {
            recalcSummary();
        }
    });

    const accountSelect = document.getElementById('account_id');
    if (accountSelect) {
        accountSelect.addEventListener('change', function () {
            loadBookkeeping().catch(function () {});
        });
    }

    window.loadBookkeepingPanel = loadBookkeeping;
})();
</script>
