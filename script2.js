
const ACCOUNT_ROWS = [];
const ACCOUNTS = [];
const NEWS_PROVIDER_LABEL = "Finnhub";
const ACCOUNTS_BY_PAIR = {};
const ACCOUNT_PAIR_INDEX = {};
const SELECTED_PAIR_BY_ACCOUNT = {};
let NEWS_ITEMS = [{"title":"Average Hourly Earnings MoM","impact":"MEDIUM","event_at":"2026-06-05T12:30:00+00:00","event_clock":"19:30","actual":"","forecast":"0.3","previous":"0.2","ai_analysis":"","ai_verdict":""},{"title":"Average Hourly Earnings YoY","impact":"MEDIUM","event_at":"2026-06-05T12:30:00+00:00","event_clock":"19:30","actual":"","forecast":"3.4","previous":"3.6","ai_analysis":"","ai_verdict":""},{"title":"Average Weekly Hours","impact":"LOW","event_at":"2026-06-05T12:30:00+00:00","event_clock":"19:30","actual":"","forecast":"34.3","previous":"34.3","ai_analysis":"","ai_verdict":""},{"title":"Government Payrolls","impact":"LOW","event_at":"2026-06-05T12:30:00+00:00","event_clock":"19:30","actual":"","forecast":"","previous":"-8","ai_analysis":"","ai_verdict":""},{"title":"Manufacturing Payrolls","impact":"LOW","event_at":"2026-06-05T12:30:00+00:00","event_clock":"19:30","actual":"","forecast":"2","previous":"-2","ai_analysis":"","ai_verdict":""},{"title":"Non Farm Payrolls","impact":"HIGH","event_at":"2026-06-05T12:30:00+00:00","event_clock":"19:30","actual":"","forecast":"85","previous":"115","ai_analysis":"","ai_verdict":""},{"title":"Nonfarm Payrolls Private","impact":"LOW","event_at":"2026-06-05T12:30:00+00:00","event_clock":"19:30","actual":"","forecast":"85","previous":"123","ai_analysis":"","ai_verdict":""}];
const INITIAL_NEWS_ITEMS = Array.isArray(NEWS_ITEMS) ? NEWS_ITEMS.slice() : [];
let NEWS_HISTORY_ITEMS = [];
const NEWS_HISTORY_MAX_ITEMS = 7;
let NEWS_LAST_FETCH_MS = 0;
let NEWS_IS_LIVE = false;
const CURRENT_USER = {"name":"Temp User","username":"tempuser","email":"tempuser@example.com"};
const MANAGED_USERS = [];
const IS_ADMIN = false;
const LICENSE_SNAPSHOTS = [];
const SERVER_RISK_ACK = [];
const LICENSE_ENFORCEMENT_ENABLED = false;
const AUTH_SESSION_ID = "qiZWGlO8heDjhbLB8AgyVxNfKgPnC93vBXIDDfkW";
const DASHBOARD_TAB_STORAGE_KEY = 'ea_dashboard_active_tab_v1';
const DASHBOARD_TAB_SESSION_KEY = 'ea_dashboard_active_tab_session_v1';
const DASHBOARD_ACCOUNT_STORAGE_KEY = 'ea_dashboard_active_account_v1';
const DASHBOARD_ACCOUNT_SESSION_KEY = 'ea_dashboard_active_account_session_v1';
const DASHBOARD_ALIAS_ACCOUNT_STORAGE_KEY = 'ea_dashboard_alias_account_v1';
const DASHBOARD_ALIAS_ACCOUNT_SESSION_KEY = 'ea_dashboard_alias_account_session_v1';
const DASHBOARD_PAIR_STORAGE_KEY = 'ea_dashboard_selected_pair_map_v1';
const DASHBOARD_PAIR_SESSION_KEY = 'ea_dashboard_selected_pair_map_session_v1';
const DEFAULT_WORKSPACE_TAB = 'settings';
const WORKSPACE_TABS = Array.from(document.querySelectorAll('#workspace-tabs [data-workspace-tab]'))
    .map((button) => String(button.getAttribute('data-workspace-tab') || '').toLowerCase())
    .filter((name) => !!name);
let BULK_CONTROL_WHITELIST = [];
let BULK_CONTROL_ENABLED = true;
let ACCOUNT_ALIASES = [];
let ACCOUNT_SEARCH_QUERY = '';
const ROUTES = {
    profileUpdate: "http:\/\/localhost\/profile\/update",
    accountStore: "http:\/\/localhost\/dashboard\/accounts",
    accountDelete: "http:\/\/localhost\/dashboard\/accounts",
    licenseRenew: "http:\/\/localhost\/licenses",
    userStore: "http:\/\/localhost\/dashboard\/users",
    userUpdateBase: "http:\/\/localhost\/dashboard\/users",
    userDeleteBase: "http:\/\/localhost\/dashboard\/users",
    newsLive: "http:\/\/localhost\/dashboard\/news\/live",
    newsCalendarApi: "http:\/\/localhost\/api\/v1\/economic-calendar",
    liveStream: "http:\/\/localhost\/dashboard\/live-stream",
    monitoringLive: "http:\/\/localhost\/dashboard\/monitoring\/live",
    reportsLive: "http:\/\/localhost\/dashboard\/reports\/live",
    reportsResetWr: "http:\/\/localhost\/dashboard\/reports\/reset-wr",
    riskConsent: "http:\/\/localhost\/dashboard\/risk-consent",
    botToggle: "http:\/\/localhost\/dashboard\/bot\/toggle",
    botBulkToggle: "http:\/\/localhost\/dashboard\/bot\/toggle-all",
    botWhitelistGet: "http:\/\/localhost\/dashboard\/bot\/whitelist",
    botWhitelistUpdate: "http:\/\/localhost\/dashboard\/bot\/whitelist",
    accountAliasesGet: "http:\/\/localhost\/dashboard\/account-aliases",
    accountAliasesUpdate: "http:\/\/localhost\/dashboard\/account-aliases",
    botResetDd: "http:\/\/localhost\/dashboard\/bot\/reset-dd",
    positionsCloseAll: "http:\/\/localhost\/dashboard\/positions\/close-all",
    positionsCloseOne: "http:\/\/localhost\/dashboard\/positions\/close-one",
    billingChatThread: "http:\/\/localhost\/licenses\/chat",
    billingChatUnread: "http:\/\/localhost\/licenses\/chat\/unread",
    billingChatSend: "http:\/\/localhost\/licenses\/chat",
    billingAdminThreads: "http:\/\/localhost\/admin\/licenses\/chat\/threads",
    billingAdminDecisionBase: "http:\/\/localhost\/admin\/licenses\/billing",
};

let DASHBOARD_LIVE_SOURCE = null;
let DASHBOARD_FALLBACK_MONITORING_TIMER = null;
let DASHBOARD_FALLBACK_REPORT_TIMER = null;
let DASHBOARD_STREAM_WATCHDOG_TIMER = null;
let DASHBOARD_STREAM_RETRY_TIMER = null;
let DASHBOARD_PAIR_DISCOVERY_TIMER = null;
let DASHBOARD_ACTIVE_SYNC_TIMER = null;
let DASHBOARD_LAST_STREAM_EVENT_AT = 0;
let DASHBOARD_LAST_MONITORING_SYNC_AT = 0;
let DASHBOARD_LAST_REPORT_SYNC_AT = 0;
let DASHBOARD_LAST_REPORT_RECOVERY_AT = 0;
let DASHBOARD_LAST_PAIR_DISCOVERY_AT = 0;

const DASHBOARD_STALE_STREAM_MS = 4500;
const DASHBOARD_STALE_MONITORING_MS = 2500;
const DASHBOARD_STALE_REPORT_MS = 5500;
const DASHBOARD_FALLBACK_MONITORING_MS = 1300;
const DASHBOARD_FALLBACK_REPORT_MS = 2600;
const DASHBOARD_PAIR_DISCOVERY_MS = 2200;
const DASHBOARD_ACTIVE_MONITORING_MS = 1100;
const DASHBOARD_ACTIVE_REPORT_MS = 2100;

const DASHBOARD_PAIR_DISCOVERY_INFLIGHT = {};
const DASHBOARD_RENDER_SIGNATURE = {
    monitoringByKey: {},
    reportByKey: {},
};

const LIVE_LICENSE_COUNTDOWN = {
    accountId: '',
    active: false,
    perpetual: false,
    anchorSeconds: 0,
    anchorMs: 0,
    lastRenderedSeconds: null,
};

function touchDashboardStreamHeartbeat() {
    const now = Date.now();
    DASHBOARD_LAST_STREAM_EVENT_AT = now;
    DASHBOARD_LAST_MONITORING_SYNC_AT = now;
    DASHBOARD_LAST_REPORT_SYNC_AT = now;
}

function scheduleDashboardLiveReconnect(delayMs = 1200) {
    if (DASHBOARD_STREAM_RETRY_TIMER) return;
    DASHBOARD_STREAM_RETRY_TIMER = setTimeout(() => {
        DASHBOARD_STREAM_RETRY_TIMER = null;
        startDashboardLiveStream();
    }, Math.max(400, Number(delayMs) || 1200));
}

function stopDashboardWatchdog() {
    if (DASHBOARD_STREAM_WATCHDOG_TIMER) {
        clearInterval(DASHBOARD_STREAM_WATCHDOG_TIMER);
        DASHBOARD_STREAM_WATCHDOG_TIMER = null;
    }
    if (DASHBOARD_PAIR_DISCOVERY_TIMER) {
        clearInterval(DASHBOARD_PAIR_DISCOVERY_TIMER);
        DASHBOARD_PAIR_DISCOVERY_TIMER = null;
    }
}

function startDashboardWatchdog() {
    if (DASHBOARD_STREAM_WATCHDOG_TIMER) return;
    DASHBOARD_STREAM_WATCHDOG_TIMER = setInterval(() => {
        const accountId = currentAccount();
        if (!accountId || document.hidden) return;

        const now = Date.now();
        const streamAge = now - Number(DASHBOARD_LAST_STREAM_EVENT_AT || 0);
        const monitoringAge = now - Number(DASHBOARD_LAST_MONITORING_SYNC_AT || 0);
        const reportAge = now - Number(DASHBOARD_LAST_REPORT_SYNC_AT || 0);
        const streamOpen = Boolean(DASHBOARD_LIVE_SOURCE && DASHBOARD_LIVE_SOURCE.readyState === EventSource.OPEN);

        const streamHealthy = streamOpen && streamAge <= DASHBOARD_STALE_STREAM_MS;

        if (!streamHealthy) {
            startDashboardFallbackPolling();
        } else {
            stopDashboardFallbackPolling();
        }

        if (!streamHealthy && monitoringAge > DASHBOARD_STALE_MONITORING_MS) {
            refreshMonitoringOnly();
        }
        if (!streamHealthy && reportAge > DASHBOARD_STALE_REPORT_MS) {
            refreshReportOnly({ source: 'watchdog' });
        }

        // Some browsers can keep EventSource in CONNECTING forever without firing CLOSED.
        // If that state is stale, recycle the stream explicitly.
        if (DASHBOARD_LIVE_SOURCE
            && DASHBOARD_LIVE_SOURCE.readyState !== EventSource.OPEN
            && streamAge > DASHBOARD_STALE_STREAM_MS) {
            restartDashboardLiveStream();
            return;
        }

        if (!DASHBOARD_LIVE_SOURCE || DASHBOARD_LIVE_SOURCE.readyState === EventSource.CLOSED) {
            scheduleDashboardLiveReconnect(1000);
        }

        const pairDiscoveryAge = now - Number(DASHBOARD_LAST_PAIR_DISCOVERY_AT || 0);
        if (pairDiscoveryAge > DASHBOARD_PAIR_DISCOVERY_MS) {
            refreshConnectedPairsRealtime();
        }
    }, 1200);

    if (!DASHBOARD_PAIR_DISCOVERY_TIMER) {
        DASHBOARD_PAIR_DISCOVERY_TIMER = setInterval(() => {
            if (document.hidden) return;
            refreshConnectedPairsRealtime();
        }, DASHBOARD_PAIR_DISCOVERY_MS);
    }
}

function stopDashboardFallbackPolling() {
    if (DASHBOARD_FALLBACK_MONITORING_TIMER) {
        clearInterval(DASHBOARD_FALLBACK_MONITORING_TIMER);
        DASHBOARD_FALLBACK_MONITORING_TIMER = null;
    }
    if (DASHBOARD_FALLBACK_REPORT_TIMER) {
        clearInterval(DASHBOARD_FALLBACK_REPORT_TIMER);
        DASHBOARD_FALLBACK_REPORT_TIMER = null;
    }
}

function stopDashboardActiveSync() {
    if (DASHBOARD_ACTIVE_SYNC_TIMER) {
        clearInterval(DASHBOARD_ACTIVE_SYNC_TIMER);
        DASHBOARD_ACTIVE_SYNC_TIMER = null;
    }
}

function startDashboardActiveSync() {
    if (DASHBOARD_ACTIVE_SYNC_TIMER) return;
    DASHBOARD_ACTIVE_SYNC_TIMER = setInterval(() => {
        if (document.hidden) return;
        const accountId = currentAccount();
        if (!accountId) return;

        const now = Date.now();
        const monitoringAge = now - Number(DASHBOARD_LAST_MONITORING_SYNC_AT || 0);
        const reportAge = now - Number(DASHBOARD_LAST_REPORT_SYNC_AT || 0);

        if (monitoringAge > DASHBOARD_ACTIVE_MONITORING_MS) {
            refreshMonitoringOnly();
        }
        if (reportAge > DASHBOARD_ACTIVE_REPORT_MS) {
            refreshReportOnly({ source: 'active-sync' });
        }
    }, 900);
}

function startDashboardFallbackPolling() {
    const accountId = currentAccount();
    if (!accountId || document.hidden) return;

    const now = Date.now();
    const monitoringAge = now - Number(DASHBOARD_LAST_MONITORING_SYNC_AT || 0);
    const reportAge = now - Number(DASHBOARD_LAST_REPORT_SYNC_AT || 0);
    if (monitoringAge > 900) {
        refreshMonitoringOnly();
    }
    if (reportAge > 1400) {
        refreshReportOnly({ source: 'fallback-bootstrap' });
    }

    if (!DASHBOARD_FALLBACK_MONITORING_TIMER) {
        DASHBOARD_FALLBACK_MONITORING_TIMER = setInterval(() => {
            refreshMonitoringOnly();
        }, DASHBOARD_FALLBACK_MONITORING_MS);
    }

    if (!DASHBOARD_FALLBACK_REPORT_TIMER) {
        DASHBOARD_FALLBACK_REPORT_TIMER = setInterval(() => {
            refreshReportOnly({ source: 'fallback' });
        }, DASHBOARD_FALLBACK_REPORT_MS);
    }
}

function stopDashboardLiveStream() {
    if (DASHBOARD_LIVE_SOURCE) {
        try {
            DASHBOARD_LIVE_SOURCE.close();
        } catch (_e) {
        }
        DASHBOARD_LIVE_SOURCE = null;
    }
    if (DASHBOARD_STREAM_RETRY_TIMER) {
        clearTimeout(DASHBOARD_STREAM_RETRY_TIMER);
        DASHBOARD_STREAM_RETRY_TIMER = null;
    }
}

function buildLiveStreamUrl() {
    const accountId = currentAccount();
    if (!accountId) return '';
    const pairSymbol = currentPairSymbol();

    const limit = Math.max(5, Number(REPORTS_STATE.pendingPerPage ?? REPORTS_STATE.perPage ?? 10));
    const page = Math.max(1, Number(REPORTS_STATE.pendingPage ?? REPORTS_STATE.page ?? 1));

    return ROUTES.liveStream
        + '?account_id=' + encodeURIComponent(accountId)
        + '&pair_symbol=' + encodeURIComponent(pairSymbol)
        + '&limit=' + encodeURIComponent(String(limit))
        + '&page=' + encodeURIComponent(String(page))
        + (CALC_DEBUG ? '&calc_debug=1' : '')
        + '&_ts=' + Date.now();
}

function applyLiveStreamPayload(payload) {
    const accountId = String(payload?.account_id || currentAccount() || '').trim();
    const pairSymbol = normalizePairSymbol(payload?.pair_symbol || payload?.monitoring?.pair_symbol || payload?.report?.pair_symbol || currentPairSymbol());
    if (!accountId) return;
    DASHBOARD_LAST_STREAM_EVENT_AT = Date.now();

    ensureAccountPairRegistered(accountId, pairSymbol);
    setStateByAccountPair(accountId, pairSymbol, {});

    let monitoringChanged = false;
    let reportChanged = false;

    const monitoring = payload?.monitoring;
    if (monitoring && monitoring.success) {
        const monitoringPatch = { ...monitoring };
        delete monitoringPatch.success;
        delete monitoringPatch.message;
        ['daily_profit', 'weekly_profit', 'monthly_profit', 'realized_profit', 'wins', 'losses', 'win_rate_percent', 'history'].forEach((key) => {
            delete monitoringPatch[key];
        });
        const monitoringApplied = shouldApplyMonitoringPayload(accountId, pairSymbol, monitoringPatch);
        if (monitoringApplied) {
            setStateByAccountPair(accountId, pairSymbol, {
                ...monitoringPatch,
                _last_live_sync_at: Date.now(),
                _runtime_bootstrap_pending: false,
            });
            DASHBOARD_LAST_MONITORING_SYNC_AT = Date.now();
            const monitoringSignatureKey = accountPairKey(accountId, pairSymbol);
            const monitoringSignature = JSON.stringify({
                gf: monitoringPatch.global_floating ?? monitoringPatch.live_floating_pnl ?? 0,
                lay: monitoringPatch.current_layers ?? monitoringPatch.live_open_layers ?? 0,
                dd: monitoringPatch.drawdown_pct ?? 0,
                lic: monitoringPatch.license_status ?? payload?.license_status ?? '',
                guard: monitoringPatch.guard_status ?? monitoringPatch.live_guard_status ?? '',
                t: monitoringPatch.updated_at ?? '',
            });
            monitoringChanged = DASHBOARD_RENDER_SIGNATURE.monitoringByKey[monitoringSignatureKey] !== monitoringSignature;
            DASHBOARD_RENDER_SIGNATURE.monitoringByKey[monitoringSignatureKey] = monitoringSignature;
        }
        if (CALC_DEBUG) {
            setStateByAccountPair(accountId, pairSymbol, { monitoring_calc_debug: monitoring?.calc_debug || null });
        }

        const hasLatestClosedTrades = safeArray(monitoring?.closed_trades_latest).length > 0;
        const knownHistoryCount = safeArray(getStateByAccountPair(accountId, pairSymbol)?.history).length;
        if (hasLatestClosedTrades && knownHistoryCount === 0) {
            const now = Date.now();
            if ((now - Number(DASHBOARD_LAST_REPORT_RECOVERY_AT || 0)) > 1200) {
                DASHBOARD_LAST_REPORT_RECOVERY_AT = now;
                refreshReportOnly({ source: 'stream-monitoring-bridge' });
            }
        }
    }

    const report = payload?.report;
    if (report && report.success) {
        const prevState = getStateByAccountPair(accountId, pairSymbol) || {};
        const incomingHistory = safeArray(report.history);
        const incomingHistoryTotal = Number(report?.history_meta?.total ?? incomingHistory.length);
        const resolvedHistory = incomingHistory.length > 0
            ? incomingHistory
            : (incomingHistoryTotal === 0 ? [] : safeArray(prevState.history));
        const incomingAnalysis = (report?.analysis && typeof report.analysis === 'object') ? report.analysis : null;
        const resolvedAnalysis = hasMeaningfulAnalysisSnapshot(incomingAnalysis)
            ? incomingAnalysis
            : (prevState.analysis || null);
        const reportPatch = {
            history: resolvedHistory,
            wins: coalesceFiniteNumber(report?.wr?.wins, prevState.wins),
            losses: coalesceFiniteNumber(report?.wr?.losses, prevState.losses),
            win_rate_percent: coalesceFiniteNumber(report?.wr?.win_rate_percent, prevState.win_rate_percent),
            wr_reset_at: report?.wr?.reset_at || null,
            realized_profit: coalesceFiniteNumber(report?.profit?.realized, prevState.realized_profit),
            daily_profit: coalesceFiniteNumber(report?.profit?.daily, prevState.daily_profit),
            weekly_profit: coalesceFiniteNumber(report?.profit?.weekly, prevState.weekly_profit),
            monthly_profit: coalesceFiniteNumber(report?.profit?.monthly, prevState.monthly_profit),
            report_daily_profit: coalesceFiniteNumber(report?.profit?.daily, prevState.report_daily_profit ?? prevState.daily_profit),
            report_weekly_profit: coalesceFiniteNumber(report?.profit?.weekly, prevState.report_weekly_profit ?? prevState.weekly_profit),
            report_monthly_profit: coalesceFiniteNumber(report?.profit?.monthly, prevState.report_monthly_profit ?? prevState.monthly_profit),
            report_realized_profit: coalesceFiniteNumber(report?.profit?.realized, prevState.report_realized_profit ?? prevState.realized_profit),
            analysis: resolvedAnalysis,
        };
        setStateByAccountPair(accountId, pairSymbol, reportPatch);
        DASHBOARD_LAST_REPORT_SYNC_AT = Date.now();
        const reportSignatureKey = accountPairKey(accountId, pairSymbol);
        const reportSignature = JSON.stringify({
            d: reportPatch.daily_profit,
            w: reportPatch.weekly_profit,
            m: reportPatch.monthly_profit,
            r: reportPatch.realized_profit,
            wr: reportPatch.win_rate_percent,
            h: safeArray(reportPatch.history).length,
            p: Number(report?.history_meta?.current_page ?? REPORTS_STATE.page ?? 1),
        });
        reportChanged = DASHBOARD_RENDER_SIGNATURE.reportByKey[reportSignatureKey] !== reportSignature;
        DASHBOARD_RENDER_SIGNATURE.reportByKey[reportSignatureKey] = reportSignature;
        if (CALC_DEBUG) {
            setStateByAccountPair(accountId, pairSymbol, { report_calc_debug: report?.calc_debug || null });
        }
        if (report?.history_meta) {
            REPORTS_STATE.page = Number(report.history_meta.current_page ?? REPORTS_STATE.page ?? 1);
            REPORTS_STATE.lastPage = Number(report.history_meta.last_page ?? REPORTS_STATE.lastPage ?? 1);
            REPORTS_STATE.total = Number(report.history_meta.total ?? REPORTS_STATE.total ?? 0);
            REPORTS_STATE.perPage = Number(report.history_meta.per_page ?? REPORTS_STATE.perPage ?? 10);
        }
    } else {
        const now = Date.now();
        const reportAge = now - Number(DASHBOARD_LAST_REPORT_SYNC_AT || 0);
        if (reportAge > 2500 && (now - Number(DASHBOARD_LAST_REPORT_RECOVERY_AT || 0)) > 1500) {
            DASHBOARD_LAST_REPORT_RECOVERY_AT = now;
            refreshReportOnly({ source: 'stream-recover' });
        }
    }

    if (monitoringChanged || reportChanged) {
        renderMonitoring();
    }
    if (reportChanged) {
        renderReport(el('save-msg')?.textContent || '');
    }
}

function startDashboardLiveStream() {
    const accountId = currentAccount();
    if (!accountId) {
        stopDashboardLiveStream();
        stopDashboardFallbackPolling();
        stopDashboardWatchdog();
        return;
    }

    if (!window.EventSource) {
        stopDashboardLiveStream();
        startDashboardFallbackPolling();
        startDashboardWatchdog();
        return;
    }

    stopDashboardLiveStream();
    const streamUrl = buildLiveStreamUrl();
    if (!streamUrl) {
        startDashboardFallbackPolling();
        return;
    }

    try {
        const source = new EventSource(streamUrl);
        DASHBOARD_LIVE_SOURCE = source;
        touchDashboardStreamHeartbeat();
        startDashboardWatchdog();

        source.addEventListener('open', () => {
            touchDashboardStreamHeartbeat();
            stopDashboardFallbackPolling();
        });

        const handleEvent = (event) => {
            try {
                const payload = JSON.parse(event.data || '{}');
                applyLiveStreamPayload(payload);
            } catch (_e) {
            }
        };

        source.addEventListener('update', handleEvent);
        source.onmessage = handleEvent;
        source.onerror = () => {
            const sourceRef = DASHBOARD_LIVE_SOURCE;
            if (!sourceRef || sourceRef.readyState !== EventSource.OPEN) {
                startDashboardFallbackPolling();
            }
            // Retry regardless of CLOSED/CONNECTING state so stuck streams can recover.
            scheduleDashboardLiveReconnect(1200);
        };
    } catch (_e) {
        stopDashboardLiveStream();
        startDashboardFallbackPolling();
        startDashboardWatchdog();
        scheduleDashboardLiveReconnect(1500);
    }
}

function restartDashboardLiveStream() {
    stopDashboardLiveStream();
    startDashboardLiveStream();
}

// Signal Reason History Tracking
const SIGNAL_REASON_HISTORY = {};

function recordSignalReason(accountId, pairSymbol, bias, power, reason, meta = null) {
    if (!accountId) return;
    const scopeKey = accountPairKey(accountId, normalizePairSymbol(pairSymbol || currentPairSymbol()));
    if (!SIGNAL_REASON_HISTORY[scopeKey]) {
        SIGNAL_REASON_HISTORY[scopeKey] = [];
    }
    
    const entry = {
        timestamp: new Date(),
        bias: String(bias || 'NEUTRAL'),
        power: Number(power || 0),
        reason: String(reason || 'No reason provided'),
        meta: meta && typeof meta === 'object' ? { ...meta } : null,
    };
    
    SIGNAL_REASON_HISTORY[scopeKey].unshift(entry); // Add to beginning
    if (SIGNAL_REASON_HISTORY[scopeKey].length > 50) {
        SIGNAL_REASON_HISTORY[scopeKey].pop(); // Keep only last 50
    }
}

function buildSignalReasonNarrative(analysis, bias, power) {
    const narrative = [];
    const scoreSource = String(analysis?.score_source || '').toLowerCase();
    const scoreBuy = Number(analysis?.score_buy ?? 0);
    const scoreSell = Number(analysis?.score_sell ?? 0);
    const bullVotes = Number(analysis?.bull_votes ?? 0);
    const bearVotes = Number(analysis?.bear_votes ?? 0);
    const adx = Number(analysis?.adx ?? 0);
    const spreadExpensive = Boolean(analysis?.spread_is_expensive);
    const guardLiveText = String(analysis?.guard_status_live ?? '').toUpperCase();
    const newsBlocked = Boolean(analysis?.news_blocked) || guardLiveText.includes('PAUSED_NEWS');
    const remotePaused = Boolean(analysis?.remote_paused) || guardLiveText.includes('PAUSED_REMOTE');
    const session = String(analysis?.session_status ?? analysis?.session ?? analysis?.guard_status_live ?? analysis?.guard_status_commanded ?? '').toUpperCase();

    const scoreDiff = Math.trunc(scoreBuy - scoreSell);
    const voteDiff = Math.trunc(bullVotes - bearVotes);

    if (Number.isFinite(scoreBuy) && Number.isFinite(scoreSell)) {
        if (scoreDiff >= 2) {
            narrative.push(`Skor condong BUY (${Math.trunc(scoreBuy)} vs ${Math.trunc(scoreSell)}).`);
        } else if (scoreDiff <= -2) {
            narrative.push(`Skor condong SELL (${Math.trunc(scoreSell)} vs ${Math.trunc(scoreBuy)}).`);
        } else {
            narrative.push(`Skor masih imbang (${Math.trunc(scoreBuy)}:${Math.trunc(scoreSell)}).`);
        }
    }

    if (scoreSource === 'votes_fallback') {
        narrative.push('Nilai confluence sementara mengikuti vote karena skor engine belum update.');
    }

    if (bullVotes > 0 || bearVotes > 0) {
        if (voteDiff >= 1) {
            narrative.push(`Vote mengarah bullish (${bullVotes}:${bearVotes}).`);
        } else if (voteDiff <= -1) {
            narrative.push(`Vote mengarah bearish (${bearVotes}:${bullVotes}).`);
        } else {
            narrative.push(`Vote seimbang (${bullVotes}:${bearVotes}).`);
        }
    }

    if (Number.isFinite(adx) && adx > 0) {
        if (adx >= 25) {
            narrative.push(`Trend cukup kuat (ADX ${formatNumber(adx, 2)}).`);
        } else if (adx >= 20) {
            narrative.push(`Trend mulai terbentuk (ADX ${formatNumber(adx, 2)}).`);
        } else {
            narrative.push(`Trend masih lemah (ADX ${formatNumber(adx, 2)}).`);
        }
    }

    if (spreadExpensive) {
        narrative.push('Spread relatif mahal, entry dibuat lebih selektif.');
    }
    if (newsBlocked) {
        narrative.push('Ada news block aktif, entry ditahan sementara.');
    }
    if (remotePaused) {
        narrative.push('Remote pause aktif dari dashboard.');
    }

    if (session === 'OPEN') {
        narrative.push('Sesi trading sedang aktif.');
    } else if (session === 'CLOSED') {
        narrative.push('Sesi trading sedang nonaktif.');
    }

    if (narrative.length === 0) {
        return bias === 'NEUTRAL'
            ? 'Belum ada konfirmasi kuat, market masih menunggu validasi.'
            : `Bias ${bias} sudah terlihat, tapi data pendukung masih tipis.`;
    }

    return narrative.join(' ');
}

function buildSignalReasonEvidence(meta) {
    if (!meta || typeof meta !== 'object') {
        return '';
    }

    const parts = [];
    const scoreBuy = Number(meta.score_buy ?? NaN);
    const scoreSell = Number(meta.score_sell ?? NaN);
    const bullVotes = Number(meta.bull_votes ?? NaN);
    const bearVotes = Number(meta.bear_votes ?? NaN);
    const adx = Number(meta.adx ?? NaN);

    if (Number.isFinite(scoreBuy) || Number.isFinite(scoreSell)) {
        parts.push(`Score BUY/SELL ${Number.isFinite(scoreBuy) ? Math.trunc(scoreBuy) : '-'}:${Number.isFinite(scoreSell) ? Math.trunc(scoreSell) : '-'}`);
    }
    if (Number.isFinite(bullVotes) || Number.isFinite(bearVotes)) {
        parts.push(`Vote ${Number.isFinite(bullVotes) ? Math.trunc(bullVotes) : '-'}:${Number.isFinite(bearVotes) ? Math.trunc(bearVotes) : '-'}`);
    }
    if (Number.isFinite(adx)) {
        parts.push(`ADX ${formatNumber(adx, 2)}`);
    }
    if (meta.bias) {
        parts.push(`Bias ${String(meta.bias).toUpperCase()}`);
    }

    return parts.join(' | ');
}

const URL_QUERY = new URLSearchParams(window.location.search);
const CALC_DEBUG = URL_QUERY.get('calc_debug') === '1';

const DEFAULTS = {
    active_strategy: 0,
    base_lot: 0.01,
    timeframe_logic: 5,
    max_drawdown_pct: 10,
    max_drawdown_stop_delay: 0,
    dd_breach_hits_required: 15,
    daily_profit_target: 0,
    grid_max_layers: 10,
    grid_max_accumulative_lot: 5.0,
    grid_mode: 1,
    fix_grid_distance: 300,
    atr_multiplier: 1.5,
    grid_tp_points: 0,
    grid_sl_points: 0,
    grid_use_trailing_layer1: true,
    grid_use_basket_tp_percent: true,
    grid_basket_tp_percent: 60,
    grid_tp_mode: 0,
    grid_tier1_tp_percent: 60,
    grid_tier2_tp_percent: 60,
    grid_tier3_tp_percent: 60,
    grid_tier4_tp_percent: 55,
    zero_gap_tp_points: 50,
    zero_gap_sl_points: 100,
    zero_gap_max_layers: 3,
    mirror_pending_distance_points: 50,
    mirror_multiplier: 2,
    zero_gap_trailing_start_points: 30,
    zero_gap_trailing_step_points: 5,
    mart_tp_points: 100,
    mart_sl_points: 200,
    mart_max_steps: 7,
    mart_type: 0,
    mart_multiplier: 1.5,
    mart_addition: 0.01,
    mart_trailing_start_points: 50,
    mart_trailing_step_points: 10,
    use_mirror_trap: false,
    always_in_market: true,
    instant_reentry: true,
    min_confluence_score: 5,
    use_pending_guard: false,
    auto_flip: false,
    use_trend_filter: false,
    use_ai_core_sharpening: false,
    use_ema_ribbon: true,
    use_dmi: true,
    use_mkt_struct: true,
    use_early_trend: true,
    use_sniper_entry: true,
    bb_period: 20,
    bb_deviation: 2.0,
    rsi_period: 14,
    rsi_buy_level: 45,
    rsi_sell_level: 55,
    adx_period: 14,
    adx_level: 25,
    adx_bars: 3,
    adx_sideways: 18,
    ema_period: 50,
    ema_fast: 20,
    ema_slow: 50,
    ema_slope_min: 0.03,
    atr_period: 14,
    use_dxy_filter: false,
    use_us10y_filter: false,
    use_vix_filter: false,
    use_oil_filter: false,
    use_friday_market_close_window: true,
    friday_stop_day: 'friday',
    friday_stop_wib: '23:45',
    friday_resume_wib: '06:15',
    use_stealth_mode: true,
    show_indicator_fallback_logs: false,
    use_sydney_session: true,
    sydney_start_wib: '05:00',
    sydney_end_wib: '14:00',
    use_asia_session: true,
    asia_start_wib: '07:00',
    asia_end_wib: '14:00',
    use_europe_session: true,
    europe_start_wib: '14:00',
    europe_end_wib: '21:00',
    use_us_session: true,
    us_start_wib: '21:00',
    us_end_wib: '04:00',
    news_filter_severity: 'HIGH',
    news_pause_before_minutes: 10,
    news_pause_after_minutes: 10,
    filter_snr_activation: true,
    trail_start: 0,
    trail_stop: 0,
    trail_step: 0,
};

const FIELD_IDS = [
    'active_strategy', 'base_lot', 'timeframe_logic', 'max_drawdown_pct', 'max_drawdown_stop_delay', 'dd_breach_hits_required', 'daily_profit_target',
    'grid_max_layers', 'grid_max_accumulative_lot', 'grid_mode', 'fix_grid_distance', 'atr_multiplier',
    'grid_mart_type', 'grid_mart_addition', 'grid_mart_multiplier',
    'grid_tp_points', 'grid_sl_points', 'grid_use_trailing_layer1', 'grid_use_basket_tp_percent', 'grid_basket_tp_percent',
    'grid_tp_mode', 'grid_tier1_tp_percent', 'grid_tier2_tp_percent', 'grid_tier3_tp_percent', 'grid_tier4_tp_percent',
    'zero_gap_tp_points', 'zero_gap_sl_points', 'zero_gap_max_layers', 'mirror_pending_distance_points', 'mirror_multiplier',
    'zero_gap_trailing_start_points', 'zero_gap_trailing_step_points',
    'mart_tp_points', 'mart_sl_points', 'mart_max_steps', 'mart_type', 'mart_multiplier', 'mart_addition',
    'mart_trailing_start_points', 'mart_trailing_step_points',
    'use_mirror_trap', 'always_in_market', 'instant_reentry', 'min_confluence_score', 'use_pending_guard', 'auto_flip',
    'use_trend_filter', 'use_ai_core_sharpening', 'use_ema_ribbon', 'use_dmi', 'use_mkt_struct', 'use_early_trend', 'use_sniper_entry', 'use_stealth_mode', 'show_indicator_fallback_logs',
    'bb_period', 'bb_deviation', 'rsi_period', 'rsi_buy_level', 'rsi_sell_level',
    'adx_period', 'adx_level', 'adx_bars', 'adx_sideways',
    'ema_period', 'ema_fast', 'ema_slow', 'ema_slope_min', 'atr_period', 'use_dxy_filter',
    'use_us10y_filter', 'use_vix_filter', 'use_oil_filter',
    'use_friday_market_close_window', 'friday_stop_day', 'friday_stop_wib', 'friday_resume_wib',
    'use_sydney_session', 'sydney_start_wib', 'sydney_end_wib',
    'use_asia_session', 'asia_start_wib', 'asia_end_wib',
    'use_europe_session', 'europe_start_wib', 'europe_end_wib',
    'use_us_session', 'us_start_wib', 'us_end_wib',
    'news_filter_severity', 'news_pause_before_minutes', 'news_pause_after_minutes', 'filter_snr_activation', 'close_all_on_news',
    'trail_start', 'trail_stop', 'trail_step'
];

const CHECKBOX_FIELDS = [
    'grid_use_trailing_layer1', 'grid_use_basket_tp_percent', 'use_mirror_trap', 'always_in_market', 'instant_reentry', 'use_pending_guard', 'auto_flip',
    'use_trend_filter', 'use_ai_core_sharpening', 'use_ema_ribbon', 'use_dmi', 'use_mkt_struct', 'use_early_trend', 'use_sniper_entry', 'use_stealth_mode', 'show_indicator_fallback_logs',
    'use_dxy_filter', 'use_us10y_filter', 'use_vix_filter', 'use_oil_filter', 'use_friday_market_close_window',
    'use_sydney_session', 'use_asia_session', 'use_europe_session', 'use_us_session', 'filter_snr_activation', 'close_all_on_news'
];

const LOGIC_ONLY_FIELD_IDS = [
    'use_pending_guard', 'auto_flip', 'use_trend_filter', 'use_ai_core_sharpening',
    'use_ema_ribbon', 'use_dmi', 'use_mkt_struct', 'use_early_trend', 'use_sniper_entry',
    'bb_period', 'bb_deviation', 'rsi_period', 'rsi_buy_level', 'rsi_sell_level',
    'adx_period', 'adx_level', 'adx_bars', 'adx_sideways',
    'ema_period', 'ema_fast', 'ema_slow', 'ema_slope_min', 'atr_period', 'use_dxy_filter',
    'use_us10y_filter', 'use_vix_filter', 'use_oil_filter',
    'use_friday_market_close_window', 'friday_stop_day', 'friday_stop_wib', 'friday_resume_wib',
    'use_stealth_mode', 'show_indicator_fallback_logs', 'close_all_on_news',
    'trail_start', 'trail_stop', 'trail_step',
    'use_sydney_session', 'sydney_start_wib', 'sydney_end_wib',
    'use_asia_session', 'asia_start_wib', 'asia_end_wib',
    'use_europe_session', 'europe_start_wib', 'europe_end_wib',
    'use_us_session', 'us_start_wib', 'us_end_wib'
];

const INLINE_SAVE_BASELINE = {};
const TOGGLE_AUTOSAVE_TIMERS = {};

function isRiskAcknowledged(accountId) {
    if (!accountId) return false;
    return Boolean(SERVER_RISK_ACK[String(accountId)]);
}

async function setRiskAcknowledged(accountId, accepted) {
    if (!accountId) return;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const response = await fetch(ROUTES.riskConsent, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({ account_id: String(accountId), accepted: Boolean(accepted) }),
    });
    if (!response.ok) {
        throw new Error('Gagal menyimpan persetujuan ToS.');
    }
    SERVER_RISK_ACK[String(accountId)] = Boolean(accepted);
}

function syncRiskAckCheckbox() {
    const accountId = currentAccount();
    const checkbox = el('risk_acknowledged');
    if (!checkbox) return;
    checkbox.checked = isRiskAcknowledged(accountId);
}

function currentFieldValueById(id) {
    if (!el(id)) return undefined;
    if (isCheckbox(id)) return Boolean(el(id).checked);
    return String(el(id).value ?? '');
}

function captureInlineSaveBaseline() {
    FIELD_IDS.forEach((id) => {
        INLINE_SAVE_BASELINE[id] = currentFieldValueById(id);
    });
}

function isFieldDirty(id) {
    return INLINE_SAVE_BASELINE[id] !== currentFieldValueById(id);
}

function refreshInlineSaveButton(id) {
    const button = document.querySelector('.inline-save-btn[data-field-id="' + id + '"]');
    if (!(button instanceof HTMLButtonElement)) return;
    button.classList.toggle('is-visible', isFieldDirty(id));
}

function refreshInlineSaveButtons() {
    FIELD_IDS.forEach((id) => refreshInlineSaveButton(id));
}

function initInlineSaveButtons() {
    FIELD_IDS.forEach((id) => {
        const input = el(id);
        if (!(input instanceof HTMLElement)) return;
        if (isCheckbox(id)) return;

        let holder = input.closest('.field-stack');
        if (!(holder instanceof HTMLElement) && input.parentElement instanceof HTMLElement) {
            const parent = input.parentElement;
            if (parent.classList.contains('switch-tile') || parent.classList.contains('form-check') || parent.classList.contains('session-toggle')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'field-stack';
                parent.insertBefore(wrapper, input);
                wrapper.appendChild(input);
                holder = wrapper;
            } else {
                holder = parent;
                holder.classList.add('field-stack');
            }
        }
        if (!(holder instanceof HTMLElement)) return;
        holder.classList.add('has-inline-save');

        let row = holder.querySelector('.inline-save-row[data-field-id="' + id + '"]');
        if (!(row instanceof HTMLElement)) {
            row = document.createElement('div');
            row.className = 'inline-save-row';
            row.setAttribute('data-field-id', id);
            if (input.parentElement === holder) {
                holder.insertBefore(row, input);
                row.appendChild(input);
            } else if (input.parentElement instanceof HTMLElement) {
                input.parentElement.insertBefore(row, input);
                row.appendChild(input);
            } else {
                holder.appendChild(row);
                row.appendChild(input);
            }
        }

        if (row.querySelector('.inline-save-btn[data-field-id="' + id + '"]')) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'inline-save-btn';
        button.setAttribute('data-field-id', id);
        button.setAttribute('title', 'Simpan cepat');
        button.setAttribute('aria-label', 'Simpan cepat field ' + id);
        button.textContent = '✓';
        button.addEventListener('click', async () => {
            await saveSetting(id);
        });
        row.appendChild(button);
    });
}

const LOGIC_PRESETS = {
    default: {
        ema_period: 50,
        ema_fast: 20,
        ema_slow: 50,
        ema_slope_min: 0.03,
        bb_period: 20,
        bb_deviation: 2.0,
        rsi_period: 14,
        rsi_buy_level: 45,
        rsi_sell_level: 55,
        adx_period: 14,
        adx_level: 25,
        adx_bars: 3,
        adx_sideways: 18,
        atr_period: 14,
        use_trend_filter: false,
        use_ema_ribbon: true,
        use_dmi: true,
        use_mkt_struct: true,
        use_early_trend: true,
        use_sniper_entry: true,
    },
    scalper: {
        ema_period: 34,
        ema_fast: 8,
        ema_slow: 21,
        ema_slope_min: 0.02,
        bb_period: 14,
        bb_deviation: 1.8,
        rsi_period: 9,
        rsi_buy_level: 42,
        rsi_sell_level: 58,
        adx_period: 10,
        adx_level: 20,
        adx_bars: 2,
        adx_sideways: 15,
        atr_period: 10,
        use_trend_filter: true,
        use_ema_ribbon: true,
        use_dmi: true,
        use_mkt_struct: false,
        use_early_trend: true,
        use_sniper_entry: false,
    },
    medium: {
        ema_period: 50,
        ema_fast: 13,
        ema_slow: 34,
        ema_slope_min: 0.03,
        bb_period: 20,
        bb_deviation: 2.0,
        rsi_period: 14,
        rsi_buy_level: 45,
        rsi_sell_level: 55,
        adx_period: 14,
        adx_level: 24,
        adx_bars: 3,
        adx_sideways: 18,
        atr_period: 14,
        use_trend_filter: true,
        use_ema_ribbon: true,
        use_dmi: true,
        use_mkt_struct: true,
        use_early_trend: true,
        use_sniper_entry: true,
    },
    conservative: {
        ema_period: 100,
        ema_fast: 21,
        ema_slow: 55,
        ema_slope_min: 0.05,
        bb_period: 20,
        bb_deviation: 2.4,
        rsi_period: 21,
        rsi_buy_level: 40,
        rsi_sell_level: 60,
        adx_period: 18,
        adx_level: 28,
        adx_bars: 4,
        adx_sideways: 20,
        atr_period: 18,
        use_trend_filter: true,
        use_ema_ribbon: true,
        use_dmi: true,
        use_mkt_struct: true,
        use_early_trend: false,
        use_sniper_entry: true,
    },
};

function validateLogicInputs(showMessage = true) {
    const emaFast = Number(el('ema_fast')?.value || 0);
    const emaSlow = Number(el('ema_slow')?.value || 0);
    if (emaFast > 0 && emaSlow > 0 && emaFast >= emaSlow) {
        if (showMessage) {
            el('save-msg').textContent = 'Validasi gagal: EMA Fast harus lebih kecil dari EMA Slow.';
            el('save-msg').className = 'small mt-2 text-danger';
        }
        return false;
    }
    return true;
}

function applyLogicPreset(name) {
    if (!IS_ADMIN) {
        if (el('save-msg-logic')) {
            el('save-msg-logic').textContent = 'Tab Logic hanya bisa diubah oleh admin.';
            el('save-msg-logic').className = 'small text-danger';
        }
        return;
    }

    const preset = LOGIC_PRESETS[name];
    if (!preset) return;

    Object.entries(preset).forEach(([id, value]) => {
        if (!el(id)) return;
        if (isCheckbox(id)) {
            el(id).checked = Boolean(value);
        } else {
            el(id).value = value;
        }
    });

    toggleDependentState();
    markDirty();
    el('save-msg').textContent = 'Preset ' + name.toUpperCase() + ' diterapkan. Klik Simpan untuk menyimpan.';
    el('save-msg').className = 'small mt-2 text-info';
}

function el(id) {
    const found = document.getElementById(id);
    if (found) return found;

    const aliases = {
        'billing-float-chat-toggle': 'admin-chat-toggle',
        'billing-float-chat-card': 'admin-chat-card',
        'billing-float-chat-close': 'admin-chat-close',
        'billing-float-chat-unread': 'admin-chat-unread',
        'billing-float-chat-pending': 'admin-chat-pending',
        'billing-float-chat-status': 'admin-chat-status',
        'billing-float-chat-messages': 'admin-chat-messages',
        'billing-float-chat-form': 'admin-chat-form',
        'billing-float-chat-input': 'admin-chat-input',
        'billing-float-chat-send': 'admin-chat-send',
        'billing-admin-user-search': 'admin-chat-search',
        'billing-admin-user-list': 'admin-chat-threads',
        'billing-admin-thread-title': 'admin-chat-title',
        'billing-admin-thread-subtitle': 'admin-chat-subtitle',
        'billing-admin-clear-thread': 'admin-chat-clear-thread',
        'billing-admin-pending-list': 'admin-chat-pending-list',
    };

    const aliasId = aliases[id];
    return aliasId ? document.getElementById(aliasId) : null;
}
function currentAccount() { return el('account_id').value; }
function saveSelectedAccount(accountId) {
    const normalized = String(accountId || '').trim();
    if (!normalized) return;
    localStorage.setItem(DASHBOARD_ACCOUNT_STORAGE_KEY, normalized);
    localStorage.setItem(DASHBOARD_ACCOUNT_SESSION_KEY, String(AUTH_SESSION_ID || ''));
}
function loadSelectedAccount() {
    const sessionMarker = String(localStorage.getItem(DASHBOARD_ACCOUNT_SESSION_KEY) || '');
    if (sessionMarker !== String(AUTH_SESSION_ID || '')) return '';
    return String(localStorage.getItem(DASHBOARD_ACCOUNT_STORAGE_KEY) || '').trim();
}
function saveSelectedAliasModalAccount(accountId) {
    const normalized = String(accountId || '').trim();
    if (!normalized) return;
    localStorage.setItem(DASHBOARD_ALIAS_ACCOUNT_STORAGE_KEY, normalized);
    localStorage.setItem(DASHBOARD_ALIAS_ACCOUNT_SESSION_KEY, String(AUTH_SESSION_ID || ''));
}
function loadSelectedAliasModalAccount() {
    const sessionMarker = String(localStorage.getItem(DASHBOARD_ALIAS_ACCOUNT_SESSION_KEY) || '');
    if (sessionMarker !== String(AUTH_SESSION_ID || '')) return '';
    return String(localStorage.getItem(DASHBOARD_ALIAS_ACCOUNT_STORAGE_KEY) || '').trim();
}
function loadSelectedPairMap() {
    const sessionMarker = String(localStorage.getItem(DASHBOARD_PAIR_SESSION_KEY) || '');
    if (sessionMarker !== String(AUTH_SESSION_ID || '')) return {};
    const raw = String(localStorage.getItem(DASHBOARD_PAIR_STORAGE_KEY) || '').trim();
    if (!raw) return {};
    try {
        const parsed = JSON.parse(raw);
        return (parsed && typeof parsed === 'object') ? parsed : {};
    } catch (_error) {
        return {};
    }
}
function saveSelectedPair(accountId, pairSymbol) {
    const id = String(accountId || '').trim();
    if (!id) return;
    const pair = normalizePairSymbol(pairSymbol);
    const map = loadSelectedPairMap();
    map[id] = pair;
    localStorage.setItem(DASHBOARD_PAIR_STORAGE_KEY, JSON.stringify(map));
    localStorage.setItem(DASHBOARD_PAIR_SESSION_KEY, String(AUTH_SESSION_ID || ''));
}
function restoreSelectedPairMap() {
    const map = loadSelectedPairMap();
    Object.keys(map).forEach((accountId) => {
        const pair = normalizePairSymbol(map[accountId]);
        if (!accountId || !pair) return;
        SELECTED_PAIR_BY_ACCOUNT[String(accountId)] = pair;
    });
}
function normalizePairSymbol(raw) {
    const upper = String(raw || '').trim().toUpperCase();
    const cleaned = upper.replace(/[^A-Z0-9_\/\.\-]/g, '');
    return cleaned || 'XAUUSDC';
}
function accountPairKey(accountId, pairSymbol) {
    const id = String(accountId || '').trim();
    const pair = normalizePairSymbol(pairSymbol);
    return id + '::' + pair;
}
function ensureAccountPairRegistered(accountId, pairSymbol) {
    const id = String(accountId || '').trim();
    if (!id) return;
    const pair = normalizePairSymbol(pairSymbol);
    if (!ACCOUNT_PAIR_INDEX[id]) ACCOUNT_PAIR_INDEX[id] = [];
    if (!ACCOUNT_PAIR_INDEX[id].includes(pair)) {
        ACCOUNT_PAIR_INDEX[id].push(pair);
        ACCOUNT_PAIR_INDEX[id].sort();
    }
    if (!SELECTED_PAIR_BY_ACCOUNT[id]) {
        SELECTED_PAIR_BY_ACCOUNT[id] = pair;
    }
}
function getPairsForAccount(accountId) {
    const id = String(accountId || '').trim();
    if (!id) return [];
    const pairs = safeArray(ACCOUNT_PAIR_INDEX[id]).map((item) => normalizePairSymbol(item));
    return pairs.length ? pairs : ['XAUUSDC'];
}

function pairFamilyKey(pairSymbol) {
    const normalized = normalizePairSymbol(pairSymbol);
    const lettersOnly = String(normalized).replace(/[^A-Z]/g, '');
    if (lettersOnly.endsWith('USDC')) {
        return lettersOnly.slice(0, -1);
    }
    return lettersOnly;
}

function stateUpdatedAtMs(state) {
    const parsed = Date.parse(String(state?.updated_at || ''));
    return Number.isFinite(parsed) ? parsed : 0;
}
function isStateFreshOnline(state) {
    const heartbeatTs = Date.parse(String(state?.updated_at || ''));
    const staleHeartbeat = Number.isFinite(heartbeatTs) ? (Date.now() - heartbeatTs > 45000) : true;
    return Boolean(state?.is_online) && !staleHeartbeat;
}

function stateHeartbeatMs(value) {
    const ts = Date.parse(String(value?.updated_at || ''));
    return Number.isFinite(ts) ? ts : NaN;
}

function shouldApplyMonitoringPayload(accountId, pairSymbol, incomingPatch) {
    const existingState = getStateByAccountPair(accountId, pairSymbol) || {};
    const prevTs = stateHeartbeatMs(existingState);
    const nextTs = stateHeartbeatMs(incomingPatch || {});

    if (Number.isFinite(prevTs) && Number.isFinite(nextTs) && nextTs < prevTs) {
        return false;
    }

    if (Number.isFinite(prevTs) && Number.isFinite(nextTs) && nextTs === prevTs) {
        const prevOpenRows = safeArray(existingState?.open_positions);
        const nextOpenRows = safeArray(incomingPatch?.open_positions);
        const hasIncomingOpenRowsField = Object.prototype.hasOwnProperty.call(incomingPatch || {}, 'open_positions');
        const nextLayers = Number(incomingPatch?.current_layers ?? incomingPatch?.live_open_layers ?? 0);
        const nextFloating = Number(incomingPatch?.global_floating ?? incomingPatch?.live_floating_pnl ?? 0);
        const stillExposed = (Number.isFinite(nextLayers) && nextLayers > 0)
            || (Number.isFinite(nextFloating) && Math.abs(nextFloating) > 0.0000001);

        // Guard against transient partial payloads: keep previous rows only when exposure is still active.
        if (hasIncomingOpenRowsField && prevOpenRows.length > 0 && nextOpenRows.length === 0 && stillExposed) {
            return false;
        }
    }

    return true;
}
function getConnectedPairsForAccount(accountId) {
    const id = String(accountId || '').trim();
    if (!id) return [];
    const allPairs = getPairsForAccount(id);
    const connected = allPairs.filter((pair) => {
        const state = getStateByAccountPair(id, pair) || {};
        if (!isStateFreshOnline(state)) return false;
        const reportedPair = normalizePairSymbol(state?.pair_symbol || state?.symbol || '');
        return reportedPair === pair;
    });

    const bestByFamily = {};
    connected.forEach((pair) => {
        const state = getStateByAccountPair(id, pair) || {};
        const family = pairFamilyKey(pair);
        const current = bestByFamily[family];
        if (!current) {
            bestByFamily[family] = pair;
            return;
        }

        const currentState = getStateByAccountPair(id, current) || {};
        const currentTs = stateUpdatedAtMs(currentState);
        const nextTs = stateUpdatedAtMs(state);
        if (nextTs > currentTs) {
            bestByFamily[family] = pair;
            return;
        }

        if (nextTs === currentTs && pair.length > current.length) {
            bestByFamily[family] = pair;
        }
    });

    return Object.values(bestByFamily);
}
function currentPairSymbol() {
    const accountId = String(currentAccount() || '').trim();
    if (!accountId) return 'XAUUSDC';
    const pairs = getPairsForAccount(accountId);
    const selected = normalizePairSymbol(SELECTED_PAIR_BY_ACCOUNT[accountId] || pairs[0] || 'XAUUSDC');
    if (!pairs.includes(selected)) {
        SELECTED_PAIR_BY_ACCOUNT[accountId] = pairs[0] || 'XAUUSDC';
        return SELECTED_PAIR_BY_ACCOUNT[accountId];
    }
    SELECTED_PAIR_BY_ACCOUNT[accountId] = selected;
    return selected;
}
function getStateByAccountPair(accountId, pairSymbol) {
    const id = String(accountId || '').trim();
    if (!id) return {};
    const pair = normalizePairSymbol(pairSymbol);
    const key = accountPairKey(id, pair);
    return ACCOUNTS_BY_PAIR[key] || {};
}
function setStateByAccountPair(accountId, pairSymbol, patch) {
    const id = String(accountId || '').trim();
    if (!id) return {};
    const pair = normalizePairSymbol(pairSymbol);
    ensureAccountPairRegistered(id, pair);
    const key = accountPairKey(id, pair);
    const baseState = { ...DEFAULTS, pair_symbol: pair, ...(ACCOUNTS_BY_PAIR[key] || {}) };
    const nextState = assignDefined(baseState, patch || {});
    nextState.account_id = id;
    nextState.pair_symbol = pair;
    ACCOUNTS_BY_PAIR[key] = nextState;
    if (normalizePairSymbol(SELECTED_PAIR_BY_ACCOUNT[id] || '') === pair) {
        ACCOUNTS[id] = nextState;
    }
    return nextState;
}
function getActiveAccountState(accountId = currentAccount(), pairSymbol = currentPairSymbol()) {
    const id = String(accountId || '').trim();
    const pair = normalizePairSymbol(pairSymbol);
    const key = accountPairKey(id, pair);
    if (ACCOUNTS_BY_PAIR[key]) {
        return ACCOUNTS_BY_PAIR[key];
    }
    return ACCOUNTS[id] || {};
}
function hydrateAccountPairState() {
    Object.keys(ACCOUNTS_BY_PAIR).forEach((key) => delete ACCOUNTS_BY_PAIR[key]);
    Object.keys(ACCOUNT_PAIR_INDEX).forEach((key) => delete ACCOUNT_PAIR_INDEX[key]);

    safeArray(ACCOUNT_ROWS).forEach((row) => {
        const accountId = String(row?.account_id || '').trim();
        if (!accountId) return;
        const pair = normalizePairSymbol(row?.pair_symbol || row?.symbol || 'XAUUSD');
        setStateByAccountPair(accountId, pair, {
            ...row,
            account_id: accountId,
            pair_symbol: pair,
            _runtime_bootstrap_pending: true,
        });
    });

    allAccountIdsSorted().forEach((accountId) => {
        const selectedPair = normalizePairSymbol(SELECTED_PAIR_BY_ACCOUNT[accountId] || getPairsForAccount(accountId)[0] || 'XAUUSD');
        SELECTED_PAIR_BY_ACCOUNT[accountId] = selectedPair;
        ACCOUNTS[accountId] = getActiveAccountState(accountId, selectedPair);
    });
}

function neutralizeStaleBootstrapRuntimeState() {
    const now = Date.now();
    allAccountIdsSorted().forEach((accountId) => {
        const pairs = getPairsForAccount(accountId);
        pairs.forEach((pairSymbol) => {
            const state = getStateByAccountPair(accountId, pairSymbol) || {};
            const heartbeatTs = Date.parse(String(state?.updated_at || ''));
            const stale = !Number.isFinite(heartbeatTs) || (now - heartbeatTs > 45000);
            if (!stale) return;

            setStateByAccountPair(accountId, pairSymbol, {
                current_layers: 0,
                current_accumulative_lot: 0,
                global_floating: 0,
                live_floating_pnl: 0,
                drawdown_pct: 0,
                open_positions: [],
                pending_orders: [],
            });
        });
    });
}
function renderPairTabsForCurrentAccount() {
    const target = el('pair-tabs-settings');
    const note = el('pair-tabs-note');
    if (!target) return;

    const accountId = String(currentAccount() || '').trim();
    if (!accountId) {
        target.innerHTML = '<span class="small text-secondary">Belum ada account dipilih.</span>';
        if (note) note.textContent = 'Pilih account dulu untuk melihat tab pair yang terhubung.';
        return;
    }

    const pairs = getConnectedPairsForAccount(accountId);
    if (!pairs.length) {
        target.innerHTML = '<span class="small text-secondary">Belum ada EA pair yang aktif terhubung di account ini.</span>';
        if (note) note.textContent = 'Pair Terhubung hanya menampilkan pair yang sedang online dari EA pada account aktif.';
        return;
    }

    const activePairCandidate = normalizePairSymbol(SELECTED_PAIR_BY_ACCOUNT[accountId] || '');
    const activePair = pairs.includes(activePairCandidate) ? activePairCandidate : pairs[0];
    SELECTED_PAIR_BY_ACCOUNT[accountId] = activePair;
    saveSelectedPair(accountId, activePair);
    target.innerHTML = pairs.map((pair) => {
        const activeClass = pair === activePair ? ' active' : '';
        return '<button type="button" class="pair-tab-btn' + activeClass + '" data-pair-symbol="' + escapeHtml(pair) + '">' + escapeHtml(pair) + '</button>';
    }).join('');

    if (note) {
        note.textContent = 'Account ' + accountId + ' terhubung ke ' + pairs.length + ' pair. Pair aktif: ' + activePair + '.';
    }
}
function isCheckbox(id) { return CHECKBOX_FIELDS.includes(id); }

function accountUserLabelById(userId) {
    const id = Number(userId);
    if (!Number.isFinite(id) || id <= 0) return '';
    const user = safeArray(MANAGED_USERS).find((item) => Number(item?.id) === id);
    if (!user) return '';
    const username = String(user.username || '').trim();
    const name = String(user.name || '').trim();
    return username !== '' ? '@' + username : name;
}

async function loadAccountAliasesFromServer() {
    try {
        const response = await fetch(ROUTES.accountAliasesGet, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        });
        const json = await response.json();
        if (!response.ok || !json.success) {
            throw new Error(String(json?.message || 'Gagal memuat alias account.'));
        }
        ACCOUNT_ALIASES = (json?.aliases && typeof json.aliases === 'object') ? json.aliases : {};
        refreshAccountSelectOptions(currentAccount());
        renderBulkWhitelistList();
        updateBulkControlUi();
    } catch (_error) {
        ACCOUNT_ALIASES = (ACCOUNT_ALIASES && typeof ACCOUNT_ALIASES === 'object') ? ACCOUNT_ALIASES : {};
    }
}

async function saveAccountAliasToServer(accountId, alias) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const response = await fetch(ROUTES.accountAliasesUpdate, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf,
        },
        credentials: 'same-origin',
        body: JSON.stringify({ account_id: accountId, alias }),
    });
    const json = await response.json();
    if (!response.ok || !json.success) {
        throw new Error(String(json?.message || 'Gagal menyimpan alias account.'));
    }

    ACCOUNT_ALIASES = (json?.aliases && typeof json.aliases === 'object') ? json.aliases : {};
    return json;
}

function accountAliasById(accountId) {
    const key = String(accountId || '').trim();
    return String((ACCOUNT_ALIASES || {})[key] || '').trim();
}

function accountDisplayLabel(accountId, accountState = null) {
    const id = String(accountId || '').trim();
    if (!id) return '-';

    const state = accountState && typeof accountState === 'object' ? accountState : (ACCOUNTS[id] || {});
    const alias = accountAliasById(id);
    const owner = IS_ADMIN ? accountUserLabelById(state?.user_id) : '';
    const extra = [];

    if (alias) extra.push(alias);
    if (owner) extra.push(owner);

    return extra.length ? (id + ' - ' + extra.join(' | ')) : id;
}

function accountRuntimeState(accountId) {
    const id = String(accountId || '').trim();
    const state = getActiveAccountState(id, currentPairSymbol()) || ACCOUNTS[id] || {};
    const licenseState = LICENSE_SNAPSHOTS[id] || {};
    const licenseActive = Boolean(licenseState.license_active);
    if (!licenseActive) {
        return { css: 'account-state-expired', label: 'Expired' };
    }

    const guardStatus = String(state.guard_status || state.live_guard_status || '').toUpperCase();
    if (guardStatus === 'LIVE') {
        return { css: 'account-state-live', label: 'Live' };
    }

    return { css: 'account-state-stop', label: 'Stop' };
}

function allAccountIdsSorted() {
    return Object.keys(ACCOUNTS || {})
        .map((item) => String(item || '').trim())
        .filter(Boolean)
        .sort();
}

function filteredAccountIds() {
    const accountIds = allAccountIdsSorted();
    const keyword = String(ACCOUNT_SEARCH_QUERY || '').trim().toLowerCase();
    if (keyword === '') {
        return accountIds;
    }

    return accountIds.filter((id) => {
        const label = accountDisplayLabel(id, ACCOUNTS[id] || {}).toLowerCase();
        return label.includes(keyword) || id.toLowerCase().includes(keyword);
    });
}

function updateAccountPickerToggle() {
    const toggle = el('account-picker-toggle');
    if (!toggle) return;
    const accountId = String(currentAccount() || '').trim();
    const label = accountId ? accountDisplayLabel(accountId, ACCOUNTS[accountId] || {}) : 'Belum ada account terdaftar';
    toggle.textContent = label;
    toggle.title = label;
}

function renderAccountPickerOptions() {
    const optionsEl = el('account-picker-options');
    if (!optionsEl) return;

    const accountIds = filteredAccountIds();
    if (!accountIds.length) {
        optionsEl.innerHTML = '<div class="small text-secondary px-2 py-1">Tidak ada account yang cocok.</div>';
        return;
    }

    optionsEl.innerHTML = accountIds.map((id) => {
        const label = accountDisplayLabel(id, ACCOUNTS[id] || {});
        const isActive = id === String(currentAccount() || '').trim();
        const runtime = accountRuntimeState(id);
        return '<button type="button" class="dropdown-item account-picker-item' + (isActive ? ' active' : '') + '" data-account-id="' + escapeHtml(id) + '">' +
            '<span class="account-picker-item-label">' + escapeHtml(label) + '</span>' +
            '<span class="account-state-badge ' + escapeHtml(runtime.css) + '">' + escapeHtml(runtime.label) + '</span>' +
            '</button>';
    }).join('');
}

function refreshAccountSelectOptions(preferredAccountId = '') {
    const select = el('account_id');
    if (!select) return;

    const requested = String(preferredAccountId || '').trim();
    const previous = String(select.value || '').trim();
    const accountIds = allAccountIdsSorted();

    if (!accountIds.length) {
        select.innerHTML = '<option value="">Belum ada account terdaftar</option>';
        select.value = '';
        updateAccountPickerToggle();
        renderAccountPickerOptions();
        return;
    }

    select.innerHTML = accountIds.map((id) => {
        const label = accountDisplayLabel(id, ACCOUNTS[id] || {});
        return '<option value="' + escapeHtml(id) + '">' + escapeHtml(label) + '</option>';
    }).join('');

    const persisted = loadSelectedAccount();
    const target = requested || previous || persisted || accountIds[0];
    select.value = accountIds.includes(target) ? target : accountIds[0];
    saveSelectedAccount(select.value);
    updateAccountPickerToggle();
    renderAccountPickerOptions();
}

function setAccountAliasMessage(message, kind = 'secondary') {
    const target = el('account-alias-msg');
    if (!target) return;
    target.textContent = message;
    target.className = 'small text-' + kind;
}

function forceResetModalArtifacts() {
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('padding-right');
    document.body.style.removeProperty('overflow');
    document.querySelectorAll('.modal-backdrop').forEach((node) => node.remove());
}

function blurFocusedDescendant(containerEl) {
    if (!(containerEl instanceof HTMLElement)) return;
    const activeEl = document.activeElement;
    if (activeEl instanceof HTMLElement && containerEl.contains(activeEl)) {
        activeEl.blur();
    }
}

function ensureModalAttachedToBody(modalId) {
    const modalEl = el(modalId);
    if (!modalEl) return null;
    if (modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }
    return modalEl;
}

function themeIconSvg(theme) {
    if (theme === 'dark') {
        return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 4V2M12 22V20M4 12H2M22 12H20M6.34 6.34L4.93 4.93M19.07 19.07L17.66 17.66M17.66 6.34L19.07 4.93M4.93 19.07L6.34 17.66M16 12C16 14.2091 14.2091 16 12 16C9.79086 16 8 14.2091 8 12C8 9.79086 9.79086 8 12 8C14.2091 8 16 9.79086 16 12Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M20.354 15.354A9 9 0 0 1 8.646 3.646 9 9 0 1 0 20.354 15.354Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

function syncAliasModalForCurrentAccount() {
    const accountId = String(currentAccount() || '').trim();
    const rememberedAliasAccountId = loadSelectedAliasModalAccount();
    const aliasInput = el('alias_account_name');
    const accountInput = el('alias_account_id');
    const accountIds = allAccountIdsSorted();

    if (accountInput instanceof HTMLSelectElement) {
        if (!accountIds.length) {
            accountInput.innerHTML = '<option value="">Belum ada account terdaftar</option>';
            accountInput.value = '';
        } else {
            accountInput.innerHTML = accountIds.map((id) => {
                return '<option value="' + escapeHtml(id) + '">' + escapeHtml(accountDisplayLabel(id, ACCOUNTS[id] || {})) + '</option>';
            }).join('');
            const selectedId = accountIds.includes(accountId)
                ? accountId
                : (accountIds.includes(rememberedAliasAccountId) ? rememberedAliasAccountId : accountIds[0]);
            accountInput.value = selectedId;
            saveSelectedAliasModalAccount(selectedId);
        }
    } else if (accountInput) {
        accountInput.value = accountId;
        saveSelectedAliasModalAccount(accountId);
    }

    const selectedAccountId = String(accountInput?.value || accountId || '').trim();
    if (aliasInput) aliasInput.value = accountAliasById(selectedAccountId);
    setAccountAliasMessage(selectedAccountId ? 'Atur alias untuk account terpilih.' : 'Belum ada account yang bisa diberi alias.', 'secondary');
}

const _smoothCache = {};
function setSmooth(id, text) {
    const node = el(id);
    if (!node) return;
    const s = String(text ?? '');
    if (_smoothCache[id] === s) return;
    _smoothCache[id] = s;
    node.textContent = s;
}

const _smoothNumberState = {};
const _smoothNumberAnim = {};
function setSmoothNumber(id, value, options = {}) {
    const node = el(id);
    if (!node) return;

    const num = Number(value);
    const digits = Number.isFinite(Number(options?.digits)) ? Math.max(0, Math.trunc(Number(options.digits))) : 0;
    const suffix = String(options?.suffix ?? '');
    const fallback = String(options?.fallback ?? '-');
    const duration = Number.isFinite(Number(options?.durationMs)) ? Math.max(120, Math.trunc(Number(options.durationMs))) : 650;

    if (!Number.isFinite(num)) {
        if (_smoothNumberAnim[id]) {
            cancelAnimationFrame(_smoothNumberAnim[id]);
            _smoothNumberAnim[id] = null;
        }
        delete _smoothNumberState[id];
        setSmooth(id, fallback);
        return;
    }

    if (_smoothNumberAnim[id]) {
        cancelAnimationFrame(_smoothNumberAnim[id]);
        _smoothNumberAnim[id] = null;
    }

    const cached = Number(_smoothNumberState[id]);
    const currentFromNode = Number(node.dataset.smoothValue);
    const start = Number.isFinite(cached)
        ? cached
        : (Number.isFinite(currentFromNode) ? currentFromNode : num);

    if (Math.abs(start - num) < Math.pow(10, -digits) / 2) {
        _smoothNumberState[id] = num;
        node.dataset.smoothValue = String(num);
        setSmooth(id, num.toFixed(digits) + suffix);
        return;
    }

    const startedAt = performance.now();
    const step = (now) => {
        const p = Math.min(1, (now - startedAt) / duration);
        // Ease-out cubic: quick response, soft landing.
        const eased = 1 - Math.pow(1 - p, 3);
        const next = start + ((num - start) * eased);

        _smoothNumberState[id] = next;
        node.dataset.smoothValue = String(next);
        setSmooth(id, next.toFixed(digits) + suffix);

        if (p < 1) {
            _smoothNumberAnim[id] = requestAnimationFrame(step);
        } else {
            _smoothNumberAnim[id] = null;
            _smoothNumberState[id] = num;
            node.dataset.smoothValue = String(num);
            setSmooth(id, num.toFixed(digits) + suffix);
        }
    };

    _smoothNumberAnim[id] = requestAnimationFrame(step);
}

function applyTheme(theme) {
    const normalized = theme === 'dark' ? 'dark' : 'light';
    document.body.setAttribute('data-theme', normalized);
    document.documentElement.style.backgroundColor = normalized === 'dark' ? '#0e1a2f' : '#eef2f7';
    const btn = el('theme-toggle');
    if (btn) {
        btn.innerHTML = themeIconSvg(normalized);
        btn.title = normalized === 'dark' ? 'Switch ke Light Mode' : 'Switch ke Dark Mode';
        btn.setAttribute('aria-label', btn.title);
    }
    localStorage.setItem('ea_dashboard_theme', normalized);
    renderReport(el('save-msg')?.textContent || '');
}

function initTheme() {
    const saved = localStorage.getItem('ea_dashboard_theme');
    applyTheme(saved || 'light');
}

function setProfileMessage(message, kind = 'secondary') {
    const target = el('profile-msg');
    if (!target) return;
    target.textContent = message;
    target.className = 'small mt-2 text-' + kind;
}

function setUsersMessage(message, kind = 'secondary') {
    const target = el('users-msg');
    if (!target) return;
    target.textContent = message;
    target.className = 'small text-' + kind;
}

function setAccountMessage(message, kind = 'secondary') {
    const target = el('account-msg');
    if (!target) return;
    target.textContent = message;
    target.className = 'small mt-2 text-' + kind;
}

function showDashboardToast(message) {
    const stack = el('dashboard-toast-stack');
    if (!stack) return;
    const toast = document.createElement('div');
    toast.className = 'dashboard-toast';
    toast.textContent = String(message || 'Tersimpan');
    stack.appendChild(toast);
    requestAnimationFrame(() => {
        toast.classList.add('is-show');
    });
    setTimeout(() => {
        toast.classList.remove('is-show');
        setTimeout(() => {
            toast.remove();
        }, 220);
    }, 1800);
}

function defaultAccountMessage() {
    if (IS_ADMIN) {
        return 'Admin: bisa tambah account baru atau input account yang sudah dipakai user untuk ditautkan ke dashboard admin.';
    }

    return 'User: bisa tambah account milik sendiri yang belum terdaftar, atau ambil account yang sudah terdaftar.';
}

function syncDeleteAccountInput() {
    const input = el('delete_account_id');
    if (!input) return;
    input.value = currentAccount() || '';
}

function setNewsSource(source, note = '') {
    if (!el('news-source-badge')) return;
    const stamp = new Date().toLocaleTimeString('id-ID');
    const sourceLabel = String(source || 'LIVE').toUpperCase();
    const sub = (note ? String(note).toUpperCase() : 'NO NOTE') + ' • ' + stamp;
    el('news-source-badge').textContent = 'SOURCE ' + sourceLabel + '\n' + sub;
}

function toWibClock(isoString) {
    if (!isoString) return null;
    const date = new Date(isoString);
    if (Number.isNaN(date.getTime())) return null;
    return date.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
        timeZone: 'Asia/Jakarta',
    });
}

function itemClock(item) {
    return toWibClock(item?.event_at) || item?.event_clock || '--:--';
}

function toWibDayDate(isoString) {
    if (!isoString) return '-';
    const date = new Date(isoString);
    if (Number.isNaN(date.getTime())) return '-';
    return date.toLocaleDateString('id-ID', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        timeZone: 'Asia/Jakarta',
    });
}

function itemDayDate(item) {
    return toWibDayDate(item?.event_at);
}

function metricMeta(value, kind = 'generic') {
    const text = String(value ?? '').trim();
    const upper = text.toUpperCase();
    const missing = text === '' || text === '-' || upper === 'N/A' || upper === 'NULL';
    if (missing) {
        return {
            value: kind === 'actual' ? 'Belum rilis' : '-',
            missing: true,
        };
    }

    return {
        value: text,
        missing: false,
    };
}

function metricValue(value, kind = 'generic') {
    return metricMeta(value, kind).value;
}

function isGenericAiInsight(text) {
    const normalized = String(text || '').trim().toLowerCase();
    if (normalized === '') return true;

    return normalized.includes('data live dari')
        || normalized.includes('forexfactory live feed')
        || normalized.includes('event upcoming dari calendar api')
        || normalized.includes('data upcoming dari cache kalender')
        || normalized.includes('calendar parser');
}

function preferredNewsInsightSource() {
    const candidates = [];
    const nextItem = getNextNewsItem();
    if (nextItem) candidates.push(nextItem);
    if (Array.isArray(NEWS_ITEMS)) candidates.push(...NEWS_ITEMS);
    if (Array.isArray(INITIAL_NEWS_ITEMS)) candidates.push(...INITIAL_NEWS_ITEMS);

    return candidates.find((item) => {
        const analysis = String(item?.ai_analysis || '').trim();
        const verdict = String(item?.ai_verdict || '').trim();
        return !isGenericAiInsight(analysis) && verdict !== '';
    }) || (nextItem || NEWS_ITEMS[0] || INITIAL_NEWS_ITEMS[0] || null);
}

const USERS_STATE = {
    query: '',
    page: 1,
    pageSize: 6,
};

const REPORTS_STATE = {
    page: 1,
    lastPage: 1,
    total: 0,
    perPage: 10,
    period: 'all',
    isLoading: false,
    pendingRefresh: false,
    pendingPage: null,
    pendingPerPage: null,
    manualPauseUntil: 0,
    abortController: null,
    lastSuccessfulData: null,
};
function roleBadge(role) {
    const normalized = (role || 'user').toLowerCase();
    if (normalized === 'admin') return 'danger';
    if (normalized === 'manager') return 'warning';
    return 'primary';
}

function userRowTemplate(user) {
    return '<tr>' +
        '<td>' + user.id + '</td>' +
        '<td>' + (user.name || '') + '</td>' +
        '<td>' + (user.username || '') + '</td>' +
        '<td><span class="badge text-bg-' + roleBadge(user.role) + '">' + (user.role || 'user') + '</span></td>' +
        '<td class="d-flex flex-wrap gap-1">'
            + '<button type="button" class="btn btn-sm btn-outline-primary" data-edit-user="' + user.id + '">Edit</button>'
            + '<button type="button" class="btn btn-sm btn-outline-danger" data-delete-user="' + user.id + '">Delete</button>'
            + '</td>' +
        '</tr>';
}

function getFilteredUsers() {
    const query = USERS_STATE.query.trim().toLowerCase();
    if (!query) return [...MANAGED_USERS];

    return MANAGED_USERS.filter((user) => {
        const haystack = [user.name, user.username, user.email, user.role, String(user.id)]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();
        return haystack.includes(query);
    });
}

function renderUsersTable() {
    if (!IS_ADMIN || !el('users-tbody')) return;

    const filtered = getFilteredUsers();
    const totalPages = Math.max(1, Math.ceil(filtered.length / USERS_STATE.pageSize));
    if (USERS_STATE.page > totalPages) USERS_STATE.page = totalPages;

    const start = (USERS_STATE.page - 1) * USERS_STATE.pageSize;
    const rows = filtered.slice(start, start + USERS_STATE.pageSize);

    if (rows.length === 0) {
        el('users-tbody').innerHTML = '<tr><td colspan="5" class="text-secondary">Tidak ada user yang cocok.</td></tr>';
    } else {
        el('users-tbody').innerHTML = rows.map(userRowTemplate).join('');
    }

    if (el('users-page-info')) {
        el('users-page-info').textContent = 'Page ' + USERS_STATE.page + '/' + totalPages;
    }
    if (el('users-count-chip')) {
        el('users-count-chip').textContent = filtered.length + ' users';
    }
    if (el('users-prev')) el('users-prev').disabled = USERS_STATE.page <= 1;
    if (el('users-next')) el('users-next').disabled = USERS_STATE.page >= totalPages;
}

function resetManageForm() {
    if (!el('users-form')) return;
    el('manage_user_id').value = '';
    el('manage_name').value = '';
    el('manage_username').value = '';
    el('manage_email').value = '';
    el('manage_role').value = 'user';
    el('manage_password').value = '';
    el('btn-user-delete')?.classList.add('d-none');
}

function fillManageForm(userId) {
    const user = MANAGED_USERS.find((item) => Number(item.id) === Number(userId));
    if (!user) return;

    el('manage_user_id').value = user.id;
    el('manage_name').value = user.name || '';
    el('manage_username').value = user.username || '';
    el('manage_email').value = user.email || '';
    el('manage_role').value = user.role || 'user';
    el('manage_password').value = '';
    el('btn-user-delete')?.classList.remove('d-none');
    setUsersMessage('Mode edit user #' + user.id + '. Password boleh dikosongkan jika tidak diubah.', 'warning');
}

function normalizeWorkspaceTab(tabName) {
    const normalized = String(tabName || '').toLowerCase();
    return WORKSPACE_TABS.includes(normalized) ? normalized : DEFAULT_WORKSPACE_TAB;
}

function persistWorkspaceTab(tabName) {
    try {
        localStorage.setItem(DASHBOARD_TAB_STORAGE_KEY, normalizeWorkspaceTab(tabName));
        localStorage.setItem(DASHBOARD_TAB_SESSION_KEY, String(AUTH_SESSION_ID || ''));
    } catch (_e) {
    }
}

function getInitialWorkspaceTab() {
    try {
        const sessionMarker = String(localStorage.getItem(DASHBOARD_TAB_SESSION_KEY) || '');
        if (sessionMarker !== String(AUTH_SESSION_ID || '')) {
            return DEFAULT_WORKSPACE_TAB;
        }

        return normalizeWorkspaceTab(localStorage.getItem(DASHBOARD_TAB_STORAGE_KEY));
    } catch (_e) {
        return DEFAULT_WORKSPACE_TAB;
    }
}

function switchWorkspaceTab(tabName, options = {}) {
    const activeTab = normalizeWorkspaceTab(tabName);
    const shouldPersist = options.persist !== false;

    WORKSPACE_TABS.forEach((name) => {
        el('workspace-pane-' + name)?.classList.toggle('is-active', name === activeTab);
    });

    document.querySelectorAll('#workspace-tabs [data-workspace-tab]').forEach((button) => {
        button.classList.toggle('active', button.getAttribute('data-workspace-tab') === activeTab);
    });

    if (shouldPersist) {
        persistWorkspaceTab(activeTab);
    }

    if (activeTab === 'bookkeeping' && typeof window.loadBookkeepingPanel === 'function') {
        window.loadBookkeepingPanel().catch(function () {});
    }
}

function formatNumber(value, digits = 2) {
    const num = Number(value ?? 0);
    if (!Number.isFinite(num)) return '0';
    return num.toFixed(digits);
}

function formatNumberOrDash(value, digits = 2) {
    if (value === null || value === undefined || value === '') return '-';
    const num = Number(value);
    if (!Number.isFinite(num)) return '-';
    return num.toFixed(digits);
}

function analysisBiasClass(bias) {
    const value = String(bias || 'NEUTRAL').toUpperCase();
    if (value === 'BULLISH') return 'is-bull';
    if (value === 'BEARISH') return 'is-bear';
    return 'is-neutral';
}

function analysisBiasLabel(bias) {
    const value = String(bias || 'NEUTRAL').toUpperCase();
    if (value === 'BULLISH') return 'BULLISH';
    if (value === 'BEARISH') return 'BEARISH';
    return 'NEUTRAL';
}

function normalizeCurrencyCode(value) {
    const code = String(value ?? '').trim().toUpperCase();
    if (code === 'USDC') return 'USC';
    return code || 'USD';
}

function inferCurrencyFromPair(pairSymbol) {
    const pair = normalizePairSymbol(pairSymbol);
    const lettersOnly = String(pair).replace(/[^A-Z]/g, '');
    if (lettersOnly.endsWith('USDC')) return 'USC';
    if (lettersOnly.endsWith('USD')) return 'USD';
    return '';
}

function formatMoneyByCurrency(value, currency) {
    if (value === null || value === undefined || value === '') return '-';
    const num = Number(value);
    if (!Number.isFinite(num)) return '-';
    const code = normalizeCurrencyCode(currency);
    if (code === 'IDR') {
        return 'Rp' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(num);
    }
    if (code === 'USD') {
        return '$' + new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
    }
    return code + ' ' + new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
}

function formatSignedMoneyByCurrency(value, currency) {
    if (value === null || value === undefined || value === '') return '-';
    const num = Number(value);
    if (!Number.isFinite(num)) return '-';
    const absText = formatMoneyByCurrency(Math.abs(num), currency);
    if (num > 0) return '+' + absText;
    if (num < 0) return '-' + absText;
    return absText;
}

function formatSignedPercent(value, digits = 2) {
    const num = Number(value ?? 0);
    if (!Number.isFinite(num)) return '-';
    const abs = Math.abs(num).toFixed(Math.max(0, Math.trunc(digits)));
    if (num > 0) return '+' + abs + '%';
    if (num < 0) return '-' + abs + '%';
    return abs + '%';
}

function applySignedTone(id, value) {
    const node = el(id);
    if (!node) return;
    const num = Number(value ?? 0);
    node.classList.remove('text-success', 'text-danger');
    if (!Number.isFinite(num) || Math.abs(num) < 0.0000001) return;
    if (num > 0) {
        node.classList.add('text-success');
    } else {
        node.classList.add('text-danger');
    }
}

function accountCurrencyFor(state) {
    const reported = normalizeCurrencyCode(state?.account_currency || state?.currency || '');
    const inferred = inferCurrencyFromPair(state?.pair_symbol || state?.symbol || currentPairSymbol());

    if (inferred === 'USC') return 'USC';
    if (reported === 'USD' && inferred) return inferred;
    return reported || inferred || 'USD';
}

function boolText(value) {
    return value ? 'ON' : 'OFF';
}

function safeArray(value) {
    return Array.isArray(value) ? value : [];
}

function hasMagicField(row) {
    return row && typeof row === 'object' && Object.prototype.hasOwnProperty.call(row, 'magic');
}

function isBotManagedPositionRow(row) {
    if (!hasMagicField(row)) return true;
    const magic = Number(row?.magic);
    return Number.isFinite(magic) && Math.abs(magic) > 0.0000001;
}

function firstFiniteNumber(...values) {
    for (const value of values) {
        const num = Number(value);
        if (Number.isFinite(num)) return num;
    }
    return 0;
}

function coalesceFiniteNumber(value, fallback = 0) {
    const num = Number(value);
    if (Number.isFinite(num)) return num;
    const fb = Number(fallback);
    return Number.isFinite(fb) ? fb : 0;
}

function hasMeaningfulAnalysisSnapshot(snapshot) {
    if (!snapshot || typeof snapshot !== 'object') return false;
    const keys = Object.keys(snapshot);
    if (keys.length === 0) return false;

    const signalKeys = ['captured_at', 'reason_summary', 'bias', 'power_pct', 'confidence_pct', 'score_buy', 'score_sell', 'bull_votes', 'bear_votes'];
    return signalKeys.some((key) => isMeaningfulAnalysisValue(key, snapshot[key]));
}

function parseSnapshotTimestamp(value) {
    if (!value) return NaN;
    const asNumber = Number(value);
    if (Number.isFinite(asNumber) && asNumber > 0) return asNumber;
    const parsed = Date.parse(String(value));
    return Number.isFinite(parsed) ? parsed : NaN;
}

function isMeaningfulAnalysisValue(key, value) {
    if (value === undefined || value === null) return false;
    if (typeof value !== 'string') return true;

    const text = value.trim();
    if (!text) return false;

    if (key === 'reason_summary' || key === 'captured_at' || key === 'server_time' || key === 'strategy_name') {
        return true;
    }

    const normalized = text.toUpperCase();
    return !['-', 'N/A', 'NA', 'NONE', 'NULL', 'UNKNOWN'].includes(normalized);
}

function mergeAnalysisSnapshots(previousAnalysis, incomingAnalysis) {
    const prev = (previousAnalysis && typeof previousAnalysis === 'object') ? previousAnalysis : {};
    const incoming = (incomingAnalysis && typeof incomingAnalysis === 'object') ? incomingAnalysis : {};
    const next = { ...prev };

    const prevTs = parseSnapshotTimestamp(prev.captured_at);
    const incomingTs = parseSnapshotTimestamp(incoming.captured_at);
    const incomingIsOlder = Number.isFinite(prevTs) && Number.isFinite(incomingTs) && incomingTs < prevTs;
    if (incomingIsOlder) {
        return next;
    }

    Object.entries(incoming).forEach(([analysisKey, analysisValue]) => {
        if (analysisKey === 'sessions' && analysisValue && typeof analysisValue === 'object') {
            const prevSessions = (next.sessions && typeof next.sessions === 'object') ? next.sessions : {};
            const nextSessions = { ...prevSessions };
            Object.entries(analysisValue).forEach(([sessionKey, sessionValue]) => {
                if (sessionValue !== undefined && sessionValue !== null && sessionValue !== '') {
                    nextSessions[sessionKey] = sessionValue;
                }
            });
            next.sessions = nextSessions;
            return;
        }

        if (analysisKey === 'mtf_bias' && analysisValue && typeof analysisValue === 'object') {
            const prevMtf = (next.mtf_bias && typeof next.mtf_bias === 'object') ? next.mtf_bias : {};
            next.mtf_bias = { ...prevMtf, ...analysisValue };
            return;
        }

        if (isMeaningfulAnalysisValue(analysisKey, analysisValue)) {
            next[analysisKey] = analysisValue;
        }
    });

    ['dxy_status', 'micro_market_status', 'learning_status'].forEach((key) => {
        if (!isMeaningfulAnalysisValue(key, next[key]) && isMeaningfulAnalysisValue(key, prev[key])) {
            next[key] = prev[key];
        }
    });

    return next;
}

function assignDefined(target, source) {
    Object.entries(source || {}).forEach(([key, value]) => {
        if (key === 'analysis' && value && typeof value === 'object') {
            const prevAnalysis = (target && target.analysis && typeof target.analysis === 'object') ? target.analysis : {};
            target.analysis = mergeAnalysisSnapshots(prevAnalysis, value);
            return;
        }

        if (value !== undefined && value !== null) {
            target[key] = value;
        }
    });
    return target;
}

function parseDashboardDate(value) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    if (value instanceof Date) {
        return Number.isNaN(value.getTime()) ? null : value;
    }

    if (typeof value === 'number' && Number.isFinite(value)) {
        const millis = value > 1000000000000 ? value : value * 1000;
        const asDate = new Date(millis);
        return Number.isNaN(asDate.getTime()) ? null : asDate;
    }

    const raw = String(value).trim();
    if (raw === '') {
        return null;
    }

    if (/^\d{10,13}$/.test(raw)) {
        const num = Number(raw);
        if (Number.isFinite(num)) {
            const millis = raw.length >= 13 ? num : num * 1000;
            const asDate = new Date(millis);
            if (!Number.isNaN(asDate.getTime())) {
                return asDate;
            }
        }
    }

    if (/[zZ]$/.test(raw) || /[+-]\d{2}:?\d{2}$/.test(raw)) {
        const direct = new Date(raw);
        if (!Number.isNaN(direct.getTime())) {
            return direct;
        }
    }

    const cleaned = raw
        .replace(/\bWIB\b/gi, '')
        .replace(',', ' ')
        .replace(/\s+/g, ' ')
        .trim();

    const fromWibParts = (year, month, day, hour = 0, minute = 0, second = 0) => {
        const utcMillis = Date.UTC(year, month - 1, day, hour - 7, minute, second);
        const asDate = new Date(utcMillis);
        return Number.isNaN(asDate.getTime()) ? null : asDate;
    };

    const ymd = cleaned.match(/^(\d{4})[.\/-](\d{1,2})[.\/-](\d{1,2})(?:\s+(\d{1,2})[:.](\d{1,2})(?:[:.](\d{1,2}))?)?$/);
    if (ymd) {
        return fromWibParts(
            Number(ymd[1]),
            Number(ymd[2]),
            Number(ymd[3]),
            Number(ymd[4] || '0'),
            Number(ymd[5] || '0'),
            Number(ymd[6] || '0')
        );
    }

    const dmy = cleaned.match(/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})(?:\s+(\d{1,2})[:.](\d{1,2})(?:[:.](\d{1,2}))?)?$/);
    if (dmy) {
        return fromWibParts(
            Number(dmy[3]),
            Number(dmy[2]),
            Number(dmy[1]),
            Number(dmy[4] || '0'),
            Number(dmy[5] || '0'),
            Number(dmy[6] || '0')
        );
    }

    const fallbackRaw = new Date(raw);
    if (!Number.isNaN(fallbackRaw.getTime())) {
        return fallbackRaw;
    }

    const fallbackCleaned = new Date(cleaned);
    return Number.isNaN(fallbackCleaned.getTime()) ? null : fallbackCleaned;
}

function formatTime(value) {
    if (!value) return '-';
    const date = parseDashboardDate(value);
    if (!date) return String(value);
    if (Number.isNaN(date.getTime())) return String(value);
    return date.toLocaleString('id-ID', {
        timeZone: 'Asia/Jakarta',
        hour12: false,
    }) + ' WIB';
}

const ANALYSIS_SERVER_CLOCK = {
    rawText: '-',
    baseDate: null,
    baseClientMs: 0,
    calibrateOffsetMs: 0,
};

function updateAnalysisServerClock(rawValue, capturedAt = null) {
    const nextRaw = String(rawValue ?? '-').trim() || '-';
    ANALYSIS_SERVER_CLOCK.rawText = nextRaw;
    const parsedServer = parseDashboardDate(nextRaw);
    let calibrated = parsedServer;

    if (parsedServer instanceof Date && !Number.isNaN(parsedServer.getTime())) {
        let offsetMs = 0;
        const parsedCaptured = parseDashboardDate(capturedAt);
        if (parsedCaptured instanceof Date && !Number.isNaN(parsedCaptured.getTime())) {
            const diffMs = parsedCaptured.getTime() - parsedServer.getTime();
            // If snapshot time and server_time differ too far, treat as timezone mismatch.
            if (Math.abs(diffMs) >= (60 * 60 * 1000) && Math.abs(diffMs) <= (12 * 60 * 60 * 1000)) {
                offsetMs = diffMs;
            }
        }

        ANALYSIS_SERVER_CLOCK.calibrateOffsetMs = offsetMs;
        calibrated = new Date(parsedServer.getTime() + offsetMs);
    } else {
        ANALYSIS_SERVER_CLOCK.calibrateOffsetMs = 0;
    }

    ANALYSIS_SERVER_CLOCK.baseDate = calibrated;
    ANALYSIS_SERVER_CLOCK.baseClientMs = Date.now();
    renderAnalysisServerClockTick();
}

function renderAnalysisServerClockTick() {
    const baseDate = ANALYSIS_SERVER_CLOCK.baseDate;
    if (!(baseDate instanceof Date) || Number.isNaN(baseDate.getTime())) {
        setSmooth('analysis-server-time', ANALYSIS_SERVER_CLOCK.rawText || '-');
        return;
    }

    const elapsedSeconds = Math.max(0, Math.floor((Date.now() - ANALYSIS_SERVER_CLOCK.baseClientMs) / 1000));
    const tickDate = new Date(baseDate.getTime() + (elapsedSeconds * 1000));
    setSmooth('analysis-server-time', tickDate.toLocaleString('id-ID', {
        timeZone: 'Asia/Jakarta',
        hour12: false,
    }) + ' WIB');
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function syncTableBody(target, rows, emptyHtml, buildRow) {
    if (!target) return;
    const items = safeArray(rows);
    if (items.length === 0) {
        if (target.dataset.emptyState !== '1' || target.innerHTML !== emptyHtml) {
            target.dataset.emptyState = '1';
            target.innerHTML = emptyHtml;
        }
        return;
    }

    const nextRows = items.map((row, index) => buildRow(row, index));
    const existingRows = Array.from(target.querySelectorAll('tr[data-row-key]'));
    const sameShape = existingRows.length === nextRows.length
        && existingRows.every((row, index) => row.dataset.rowKey === nextRows[index].key);

    target.dataset.emptyState = '0';
    if (sameShape) {
        nextRows.forEach((nextRow, index) => {
            if (existingRows[index].outerHTML !== nextRow.html) {
                existingRows[index].outerHTML = nextRow.html;
            }
        });
        return;
    }

    const template = document.createElement('template');
    template.innerHTML = nextRows.map((row) => row.html).join('');
    target.replaceChildren(...Array.from(template.content.childNodes));
}

function computeBasketTpState(rows, state) {
    const basketEnabled = Boolean(state.grid_use_basket_tp_percent);
    const tpMode = Number(state.grid_tp_mode ?? 0);
    const balance = Number(state.balance ?? state.current_balance ?? 0);
    const hasMagicMeta = safeArray(rows).some((r) => hasMagicField(r));
    const botRows = hasMagicMeta
        ? safeArray(rows).filter((r) => isBotManagedPositionRow(r))
        : safeArray(rows);

    const eaLayerCount = Math.max(0, Math.trunc(Number(state.current_layers ?? state.live_open_layers ?? botRows.length ?? 0)));
    const eaAccLot = Math.max(0, Number(state.current_accumulative_lot ?? state.live_accumulative_lot ?? 0))
        || Math.max(0, botRows.reduce((acc, row) => acc + Math.max(0, Number(row?.lot ?? row?.volume ?? 0) || 0), 0));
    const layerCount = eaLayerCount > 0 ? eaLayerCount : botRows.length;

    let tpPercent;
    if (tpMode === 1) {
        if (layerCount <= 3) tpPercent = Number(state.grid_tier1_tp_percent ?? 60);
        else if (layerCount <= 5) tpPercent = Number(state.grid_tier2_tp_percent ?? 60);
        else if (layerCount <= 10) tpPercent = Number(state.grid_tier3_tp_percent ?? 60);
        else tpPercent = Number(state.grid_tier4_tp_percent ?? 55);
    } else {
        tpPercent = Number(state.grid_basket_tp_percent ?? 60);
    }

    const targetLot = eaAccLot > 0 ? (eaAccLot * tpPercent / 100) : 0;
    const orderedRows = botRows.slice().sort((a, b) => {
        const ta = Number(parseDashboardDate(a?.open_time ?? a?.time)?.getTime() ?? 0);
        const tb = Number(parseDashboardDate(b?.open_time ?? b?.time)?.getTime() ?? 0);
        if (ta !== tb) return ta - tb;
        return Number(a?.ticket ?? a?.order ?? a?.position ?? 0) - Number(b?.ticket ?? b?.order ?? b?.position ?? 0);
    });

    let cumulativeLot = 0;
    let cumulativeFloating = 0;
    let pivotIdx = -1;
    const anyProfit = orderedRows.some((r) => Number(r?.floating ?? (Number(r?.profit ?? 0) + Number(r?.swap ?? 0))) > 0);
    const layerData = orderedRows.map((row, idx) => {
        const lot = Math.max(0, Number(row?.lot ?? row?.volume ?? 0) || 0);
        const pnl = Number(row?.floating ?? (Number(row?.profit ?? 0) + Number(row?.swap ?? 0)));
        cumulativeLot += lot;
        cumulativeFloating += Number.isFinite(pnl) ? pnl : 0;
        if (pivotIdx === -1 && targetLot > 0 && cumulativeLot >= targetLot) pivotIdx = idx;
        const ticket = String(row.ticket ?? row.order ?? row.position ?? '');
        return { idx, ticket, lot, pnl, cumulativeLot, cumulativeFloating };
    });

    const totalBotLot = cumulativeLot;
    const totalBotFloating = cumulativeFloating;
    const lotProgressPct = targetLot > 0 ? Math.min(150, (totalBotLot / targetLot) * 100) : 0;
    const floatingThreshold = (balance > 0 && Number.isFinite(tpPercent) && tpPercent > 0) ? balance * tpPercent / 100 : 0;
    const floatingGap = floatingThreshold - totalBotFloating;
    const lotGap = targetLot - totalBotLot;

    const ticketMap = {};
    layerData.forEach((ld) => {
        if (!ld.ticket) return;
        ticketMap[ld.ticket] = {
            layerNum: ld.idx + 1,
            lot: ld.lot,
            pnl: ld.pnl,
            cumulativeLot: ld.cumulativeLot,
            cumulativeFloating: ld.cumulativeFloating,
            isNeeded: pivotIdx >= 0 && ld.idx < pivotIdx,
            isAtTrigger: ld.idx === pivotIdx,
            isExtra: pivotIdx >= 0 && ld.idx > pivotIdx,
        };
    });

    const pivotTicket = pivotIdx >= 0 ? (layerData[pivotIdx]?.ticket ?? '') : '';
    const pivotLayerNum = pivotTicket ? (ticketMap[pivotTicket]?.layerNum ?? (pivotIdx + 1)) : null;

    return {
        basketEnabled,
        tpMode,
        tpPercent,
        balance,
        threshold: floatingThreshold,
        totalBotFloating,
        progressPct: lotProgressPct,
        gap: floatingGap,
        lotGap,
        eaLayerCount,
        eaAccLot,
        totalBotLot,
        targetLot,
        layerCount,
        triggerIdx: pivotIdx,
        triggerLayerNum: pivotLayerNum,
        thresholdMet: targetLot > 0 && totalBotLot >= targetLot,
        allInLoss: !anyProfit,
        ticketMap,
        hasMagicMeta,
    };
}

function renderBasketTpEstimator(rows, state, basketState) {
    const panel = el('basket-tp-estimator');
    if (!panel) return;

    if (!basketState || !basketState.basketEnabled || basketState.layerCount === 0) {
        panel.style.display = 'none';
        return;
    }

    panel.style.display = 'block';

    const {
        tpMode, tpPercent, layerCount, triggerLayerNum, thresholdMet, allInLoss,
        eaLayerCount, eaAccLot, targetLot, lotGap, progressPct,
    } = basketState;

    let badgeClass = 'is-far';
    let badgeText = 'Belum tercapai';
    if (thresholdMet) {
        badgeClass = 'is-met';
        badgeText = '✓ THRESHOLD MET — EA AKAN CLOSE ALL';
    } else if (progressPct >= 70) {
        badgeClass = 'is-close';
        badgeText = '⚡ Mendekati TP';
    }

    const barColor = thresholdMet ? '#16a34a' : (progressPct >= 70 ? '#f59e0b' : '#3b82f6');
    const barWidth = Math.max(0, Math.min(100, progressPct < 0 ? 0 : progressPct));
    const tierLabel = tpMode === 1
        ? 'Tiered [' + (layerCount <= 3 ? 'Tier1 ≤3' : layerCount <= 5 ? 'Tier2 4-5' : layerCount <= 10 ? 'Tier3 6-10' : 'Tier4 ≥11') + ']'
        : 'Single Target';

    let triggerInfo;
    if (thresholdMet) {
        triggerInfo = '<b style="color:#16a34a;">Sudah terpenuhi — akumulasi lot EA melewati target exposure</b>';
    } else if (triggerLayerNum !== null) {
        triggerInfo = 'Estimasi TP ada di <b style="color:#1d4ed8;">Layer #' + triggerLayerNum + '</b> dari ' + layerCount + ' layer EA (berdasarkan akumulasi lot)';
    } else if (allInLoss) {
        triggerInfo = '<span style="color:#dc2626;">Semua ' + layerCount + ' bot layer sedang rugi — belum ada layer yang bisa jadi pivot TP</span>';
    } else {
        triggerInfo = '<span style="color:#dc2626;">Target exposure belum tercapai — tambahkan layer / lot atau turunkan % basket</span>';
    }

    const manualNote = basketState.hasMagicMeta
        ? '<span style="opacity:.6; font-size:0.75rem;">(posisi manual tidak dihitung)</span>'
        : '';

    panel.innerHTML =
        '<div class="btp-hrow">' +
            '<strong style="font-size:0.82rem; white-space:nowrap;">🎯 Basket TP Estimasi</strong>' +
            '<span class="btp-badge ' + badgeClass + '">' + badgeText + '</span>' +
            manualNote +
        '</div>' +
        '<div class="btp-hrow" style="gap:14px; font-size:0.81rem; color:var(--monster-text-secondary,#64748b);">' +
            '<span>Mode: <b style="color:var(--monster-ink,#0f172a);">' + escapeHtml(tierLabel) + '</b></span>' +
            '<span>EA Layer: <b style="color:var(--monster-ink,#0f172a);">' + formatNumber(eaLayerCount, 0) + '</b></span>' +
            '<span>EA Acc Lot: <b style="color:var(--monster-ink,#0f172a);">' + formatNumber(eaAccLot, 2) + '</b></span>' +
            '<span>Target Lot: <b style="color:#1d4ed8;">' + formatNumber(targetLot, 2) + '</b></span>' +
            '<span>Pivot Layer: <b style="color:#16a34a;">' + (triggerLayerNum !== null ? ('#' + triggerLayerNum) : '-') + '</b></span>' +
            '<span>Remaining Lot: <b style="color:' + (lotGap <= 0 ? '#16a34a' : '#dc2626') + ';">' + (lotGap <= 0 ? '0.00 ✓' : formatNumber(lotGap, 2)) + '</b></span>' +
        '</div>' +
        '<div class="btp-hrow" style="font-size:0.81rem;">' + triggerInfo + '</div>' +
        '<div style="display:flex; align-items:center; gap:10px; margin-top:4px;">' +
            '<div class="basket-tp-progress-wrap">' +
                '<div class="basket-tp-progress-bar" style="width:' + barWidth.toFixed(1) + '%; background:' + barColor + ';"></div>' +
            '</div>' +
            '<span style="font-size:0.79rem; color:var(--monster-text-secondary,#64748b);">' + formatNumber(Math.max(0, Math.min(100, progressPct)), 1) + '% dari exposure lot</span>' +
            '<span style="font-size:0.74rem; color:var(--monster-text-secondary,#64748b); display:flex; align-items:center; gap:4px;">' +
                '<span style="width:10px;height:10px;border-radius:2px;background:rgba(22,163,74,0.3);border:1px solid #16a34a;display:inline-block;"></span>Diperlukan / Pivot' +
                '<span style="width:10px;height:10px;border-radius:2px;background:rgba(148,163,184,0.25);border:1px solid #94a3b8;display:inline-block;margin-left:6px;"></span>Ekstra' +
            '</span>' +
        '</div>';
}

function buildOpenPositionRow(row, index, basketState) {
    const key = String(row.ticket ?? row.order ?? row.position ?? row.symbol ?? Math.random());
    const ticket = String(row.ticket ?? row.order ?? row.position ?? '').trim();
    const actionLocked = MONITOR_ACTIONS_LOCKED;
    const disabledAttr = actionLocked ? ' disabled aria-disabled="true"' : '';
    const closeButtonHtml = ticket
        ? '<button type="button" class="btn btn-sm btn-outline-danger mon-close-position-btn" data-close-ticket="' + escapeHtml(ticket) + '" title="Close ticket ' + escapeHtml(ticket) + '"' + disabledAttr + '>X</button>'
        : '<span class="text-secondary">-</span>';
    const pnl = Number(row.floating ?? (Number(row.profit ?? 0) + Number(row.swap ?? 0)));
    const pnlClass = pnl >= 0 ? 'text-success' : 'text-danger';

    let rowClass = '';
    let floatTag = '';

    const isBot = basketState && basketState.hasMagicMeta ? isBotManagedPositionRow(row) : true;

    if (basketState && basketState.basketEnabled) {
        if (!isBot) {
            // Manual position — dim it, badge it
            rowClass = 'btp-manual';
            floatTag = '<span class="btp-row-tag is-manual">manual</span>';
        } else if (ticket && basketState.ticketMap[ticket]) {
            const info = basketState.ticketMap[ticket];
            if (info.isAtTrigger) {
                rowClass = 'btp-trigger';
                floatTag = '<span class="btp-row-tag is-trigger">← PIVOT L#' + info.layerNum + '</span>';
            } else if (info.isNeeded) {
                rowClass = 'btp-needed';
                floatTag = '<span class="btp-row-tag is-needed">L#' + info.layerNum + '</span>';
            } else {
                rowClass = 'btp-extra';
                floatTag = '<span class="btp-row-tag is-extra">L#' + info.layerNum + '</span>';
            }
        }
    }

    return {
        key,
        html: '<tr data-row-key="' + escapeHtml(key) + '" class="' + rowClass + '">' +
            '<td>' + escapeHtml(row.ticket ?? row.order ?? '-') + '</td>' +
            '<td>' + escapeHtml(row.type ?? '-') + '</td>' +
            '<td>' + escapeHtml(row.symbol ?? '-') + '</td>' +
            '<td>' + escapeHtml(formatNumber(row.lot ?? row.volume ?? 0, 2)) + '</td>' +
            '<td>' + escapeHtml(formatNumber(row.open_price ?? row.price_open ?? 0, 3)) + '</td>' +
            '<td>' + escapeHtml(formatNumber(row.sl ?? 0, 3)) + '</td>' +
            '<td>' + escapeHtml(formatNumber(row.tp ?? 0, 3)) + '</td>' +
            '<td class="' + pnlClass + '">' + escapeHtml(formatNumber(pnl, 2)) + floatTag + '</td>' +
            '<td>' + escapeHtml(formatTime(row.open_time ?? row.time)) + '</td>' +
            '<td>' + closeButtonHtml + '</td>' +
            '</tr>',
    };
}

function buildPendingOrderRow(row) {
    const key = String(row.ticket ?? row.order ?? row.position ?? row.symbol ?? Math.random());
    return {
        key,
        html: '<tr data-row-key="' + escapeHtml(key) + '">' +
            '<td>' + escapeHtml(row.ticket ?? row.order ?? '-') + '</td>' +
            '<td>' + escapeHtml(row.type ?? '-') + '</td>' +
            '<td>' + escapeHtml(row.symbol ?? '-') + '</td>' +
            '<td>' + escapeHtml(formatNumber(row.lot ?? row.volume ?? 0, 2)) + '</td>' +
            '<td>' + escapeHtml(formatNumber(row.price ?? row.open_price ?? 0, 3)) + '</td>' +
            '<td>' + escapeHtml(formatNumber(row.sl ?? 0, 3)) + '</td>' +
            '<td>' + escapeHtml(formatNumber(row.tp ?? 0, 3)) + '</td>' +
            '<td>' + escapeHtml(formatTime(row.time ?? row.open_time)) + '</td>' +
            '</tr>',
    };
}

function buildHistoryRow(row) {
    const key = String(row.ticket ?? row.order ?? row.position ?? row.close_time ?? row.open_time ?? Math.random());
    const profit = Number(row.profit ?? 0);
    const profitClass = profit >= 0 ? 'text-success' : 'text-danger';
    return {
        key,
        html: '<tr data-row-key="' + escapeHtml(key) + '">' +
            '<td>' + escapeHtml(row.ticket ?? '-') + '</td>' +
            '<td>' + escapeHtml(row.symbol ?? '-') + '</td>' +
            '<td>' + escapeHtml(row.type ?? '-') + '</td>' +
            '<td>' + escapeHtml(formatNumber(row.lot ?? 0, 2)) + '</td>' +
            '<td>' + escapeHtml(formatNumber(row.open_price ?? 0, 3)) + '</td>' +
            '<td>' + escapeHtml(formatNumber(row.close_price ?? 0, 3)) + '</td>' +
            '<td class="' + profitClass + '">' + escapeHtml(formatNumber(profit, 2)) + '</td>' +
            '<td>' + escapeHtml(formatNumber(row.swap ?? 0, 2)) + '</td>' +
            '<td>' + escapeHtml(formatNumber(row.commission ?? 0, 2)) + '</td>' +
            '<td>' + escapeHtml(formatTime(row.open_time)) + '</td>' +
            '<td>' + escapeHtml(formatTime(row.close_time ?? row.open_time)) + '</td>' +
            '</tr>',
    };
}

function renderOpenPositionsTable(rows) {
    const target = el('mon-open-positions-body');
    if (!target) return;

    const accountId = currentAccount();
    const pairSymbol = currentPairSymbol();
    const accountState = getActiveAccountState(accountId, pairSymbol);
    const state = { ...DEFAULTS, ...accountState };
    const basketState = computeBasketTpState(rows, state);
    renderBasketTpEstimator(rows, state, basketState);

    syncTableBody(
        target,
        rows,
        '<tr><td colspan="10" class="text-secondary">Belum ada data open positions.</td></tr>',
        (row, index) => buildOpenPositionRow(row, index, basketState),
    );
}

function setMonitoringActionMessage(message, tone = 'secondary') {
    const target = el('monitor-action-msg');
    if (!target) return;
    const safeTone = ['secondary', 'success', 'warning', 'danger', 'info'].includes(String(tone)) ? tone : 'secondary';
    target.className = 'small text-' + safeTone;
    target.textContent = String(message || '');
}

function setCloseTicketButtonsDisabled(ticket, disabled) {
    const ticketKey = String(ticket || '').trim();
    if (!ticketKey) return;
    document.querySelectorAll('.mon-close-position-btn[data-close-ticket]').forEach((btn) => {
        if (!(btn instanceof HTMLButtonElement)) return;
        if (String(btn.dataset.closeTicket || '').trim() !== ticketKey) return;
        btn.disabled = Boolean(disabled);
    });
}

async function triggerCloseAllPositions() {
    const accountId = currentAccount();
    const pairSymbol = currentPairSymbol();
    if (!accountId) {
        setMonitoringActionMessage('Pilih account terlebih dahulu.', 'warning');
        return;
    }
    if (MONITOR_ACTIONS_LOCKED) {
        setMonitoringActionMessage('Lisensi expired. Action close dikunci.', 'danger');
        return;
    }

    if (!window.confirm('Close ALL positions untuk account ' + accountId + '?')) {
        return;
    }

    const btn = el('btn-close-all-positions');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const originalText = btn ? btn.textContent : 'Close All Positions';
    if (!basketState || !basketState.basketEnabled || basketState.layerCount === 0) {
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Closing...';
    }
    setMonitoringActionMessage('Mengirim sinyal close all positions...', 'info');

    try {
        tpMode, tpPercent, balance, threshold, totalBotFloating,
        progressPct, gap, lotGap, layerCount, triggerLayerNum, thresholdMet, allInLoss,
        eaLayerCount, eaAccLot, totalBotLot, targetLot, hasMagicMeta,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                account_id: accountId,
                pair_symbol: pairSymbol,
                reason: 'Manual close all from dashboard',
            }),
        });
        const json = await response.json();
        triggerInfo = '<b style="color:#16a34a;">Sudah terpenuhi — akumulasi lot EA melewati target exposure</b>';
            throw new Error(String(json?.message || 'Gagal mengirim close all positions.'));
        triggerInfo = 'Estimasi TP ada di <b style="color:#1d4ed8;">Layer #' + triggerLayerNum + '</b> dari ' + layerCount +
            ' layer EA (berdasarkan akumulasi lot)';
        await refreshMonitoringOnly();
        restartDashboardLiveStream();
            triggerInfo = '<span style="color:#dc2626;">Semua ' + layerCount + ' bot layer sedang rugi — belum ada layer yang bisa jadi pivot TP</span>';
        setMonitoringActionMessage('Close all gagal: ' + String(error?.message || 'unknown error'), 'danger');
            triggerInfo = '<span style="color:#dc2626;">Target exposure belum tercapai — tambahkan layer / lot atau turunkan % basket</span>';
        if (btn) {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    }
}

async function triggerCloseSinglePosition(ticket) {
    const accountId = currentAccount();
    const pairSymbol = currentPairSymbol();
    const ticketKey = String(ticket || '').trim();
    if (!accountId || !ticketKey) {
        return;
    }
            '<span>Mode: <b style="color:var(--monster-ink,#0f172a);">' + escapeHtml(tierLabel) + '</b></span>' +
            '<span>EA Layer: <b style="color:var(--monster-ink,#0f172a);">' + formatNumber(eaLayerCount, 0) + '</b></span>' +
            '<span>EA Acc Lot: <b style="color:var(--monster-ink,#0f172a);">' + formatNumber(eaAccLot, 2) + '</b></span>' +
            '<span>Target Lot: <b style="color:#1d4ed8;">' + formatNumber(targetLot, 2) + '</b></span>' +
            '<span>Pivot Layer: <b style="color:#16a34a;">' + (triggerLayerNum !== null ? ('#' + triggerLayerNum) : '-') + '</b></span>' +
            '<span>Remaining Lot: <b style="color:' + (lotGap <= 0 ? '#16a34a' : '#dc2626') + ';">' +
                (lotGap <= 0 ? '0.00 ✓' : formatNumber(lotGap, 2)) + '</b></span>' +

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    setCloseTicketButtonsDisabled(ticketKey, true);
    setMonitoringActionMessage('Mengirim sinyal close ticket ' + ticketKey + '...', 'info');

    try {
            '<span style="font-size:0.79rem; color:var(--monster-text-secondary,#64748b);">' + formatNumber(Math.max(0, Math.min(100, progressPct)), 1) + '% dari exposure lot</span>' +
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                account_id: accountId,
                pair_symbol: pairSymbol,
                ticket: ticketKey,
                reason: 'Manual close ticket from dashboard',
            }),
        });
        const json = await response.json();
        if (!response.ok || !json.success) {
            throw new Error(String(json?.message || 'Gagal close ticket.'));
        }

        setMonitoringActionMessage(String(json.message || ('Sinyal close ticket ' + ticketKey + ' dikirim.')), 'success');
        await refreshMonitoringOnly();
        restartDashboardLiveStream();
    } catch (error) {
        setMonitoringActionMessage('Close ticket gagal: ' + String(error?.message || 'unknown error'), 'danger');
    } finally {
        setCloseTicketButtonsDisabled(ticketKey, false);
    }
}

function renderPendingOrdersTable(rows) {
    const target = el('mon-pending-orders-body');
    if (!target) return;
    syncTableBody(
        target,
        rows,
        '<tr><td colspan="8" class="text-secondary">Belum ada data pending orders.</td></tr>',
        buildPendingOrderRow,
    );
}

function renderHistoryTable(rows) {
    const target = el('rep-history-body');
    if (!target) return;
    const items = safeArray(rows);
    if (items.length === 0) {
        if (REPORTS_STATE.isLoading) {
            target.innerHTML = '<tr><td colspan="11" class="text-secondary text-center" style="padding: 2rem 0;"><small>Loading trade history...</small></td></tr>';
        } else {
            target.innerHTML = '<tr><td colspan="11" class="text-secondary">Belum ada trade history.</td></tr>';
        }
        return;
    }

    REPORTS_STATE.lastSuccessfulData = items;
    syncTableBody(target, items, '', buildHistoryRow);
}

function renderHistoryPagination() {
    if (el('rep-history-page-info')) {
        el('rep-history-page-info').textContent = 'Page ' + REPORTS_STATE.page + '/' + REPORTS_STATE.lastPage + ' • ' + REPORTS_STATE.total + ' rows';
    }
    if (el('rep-history-limit')) {
        const selectedLimitRaw = REPORTS_STATE.pendingPerPage ?? REPORTS_STATE.perPage ?? 10;
        const selected = String(Math.max(5, Number(selectedLimitRaw)));
        if (el('rep-history-limit').value !== selected) {
            el('rep-history-limit').value = selected;
        }
    }
    if (el('rep-history-period')) {
        const selectedPeriod = String(REPORTS_STATE.period || 'all');
        if (el('rep-history-period').value !== selectedPeriod) {
            el('rep-history-period').value = selectedPeriod;
        }
    }
    if (el('rep-history-prev')) el('rep-history-prev').disabled = REPORTS_STATE.page <= 1;
    if (el('rep-history-next')) el('rep-history-next').disabled = REPORTS_STATE.page >= REPORTS_STATE.lastPage;
}

function renderCalcDebug() {
    const targetWrap = el('calc-debug-wrap');
    const targetOutput = el('calc-debug-output');
    if (!targetWrap || !targetOutput) return;

    if (!CALC_DEBUG) {
        targetWrap.style.display = 'none';
        return;
    }

    const accountId = currentAccount();
    const state = { ...DEFAULTS, ...(getActiveAccountState(accountId, currentPairSymbol()) || {}) };
    const payload = {
        account_id: accountId || '-',
        pair_symbol: currentPairSymbol(),
        monitoring: state.monitoring_calc_debug || null,
        report: state.report_calc_debug || null,
    };

    targetWrap.style.display = 'block';
    targetOutput.textContent = JSON.stringify(payload, null, 2);
}

let _positionsJson = '';
let _pendingJson = '';
let MONITOR_ACTIONS_LOCKED = false;
function renderSignalReasonTimeline() {
    const accountId = currentAccount();
    const signalScopeKey = accountPairKey(accountId, currentPairSymbol());
    const target = el('signal-reason-timeline');
    if (!target) return;
    
    const history = SIGNAL_REASON_HISTORY[signalScopeKey] || [];
    if (history.length === 0) {
        target.innerHTML = '<div class="text-secondary small p-3 text-center">No signal history yet. Waiting for first signal...</div>';
        return;
    }
    
    const displayItems = history.slice(0, 10); // Show only last 10
    target.innerHTML = displayItems.map((item, idx) => {
        const time = new Date(item.timestamp);
        const timeStr = time.toLocaleTimeString('id-ID');
        const biasNorm = String(item.bias || '').toUpperCase();
        const biasClass = (biasNorm === 'BULL' || biasNorm === 'BULLISH') ? 'is-bull' : ((biasNorm === 'BEAR' || biasNorm === 'BEARISH') ? 'is-bear' : 'is-neutral');
        const detailList = Array.isArray(item?.meta?.reason_details) ? item.meta.reason_details : [];
        const evidence = detailList.length > 0 ? detailList.slice(0, 2).join(' ') : buildSignalReasonEvidence(item.meta);
        return `<div class="signal-reason-item ${biasClass}">
            <span class="signal-reason-time">● ${timeStr}</span>
            <span class="signal-reason-text"><strong>${item.bias}</strong> - ${item.reason}</span>
            ${evidence ? `<span class="signal-reason-subtext">${evidence}</span>` : ''}
            <span class="signal-reason-power">Power: ${formatNumber(item.power, 2)}%</span>
        </div>`;
    }).join('');
}

function getEligibleBulkAccounts() {
    if (!BULK_CONTROL_ENABLED) {
        return [];
    }

    const whitelist = safeArray(BULK_CONTROL_WHITELIST).map((item) => String(item || '').trim()).filter(Boolean);
    if (whitelist.length === 0) {
        return [];
    }

    // Do not block bulk action by local cache availability.
    // Server side access checks remain the source of truth.
    return whitelist;
}

function updateBulkControlUi() {
    const hintEl = el('bot-bulk-hint');
    const startAllBtn = el('btn-bot-start-all');
    const stopAllBtn = el('btn-bot-stop-all');
    const whitelist = safeArray(BULK_CONTROL_WHITELIST).map((item) => String(item || '').trim()).filter(Boolean);
    const eligible = getEligibleBulkAccounts();
    const accountId = currentAccount();
    const licenseActive = Boolean((LICENSE_SNAPSHOTS[accountId] || {}).license_active);
    const licenseLocked = LICENSE_ENFORCEMENT_ENABLED && !licenseActive;

    if (hintEl) {
        if (!BULK_CONTROL_ENABLED) {
            hintEl.textContent = 'Whitelist bulk control sedang dinonaktifkan oleh admin.';
        } else if (whitelist.length === 0) {
            hintEl.textContent = 'Whitelist bulk control belum diset di server.';
        } else {
            hintEl.textContent = 'Whitelist bulk control: ' + eligible.length + '/' + whitelist.length + ' account siap.';
        }
    }

    const canRun = !licenseLocked && BULK_CONTROL_ENABLED && whitelist.length > 0 && eligible.length > 0;
    if (startAllBtn) startAllBtn.disabled = !canRun;
    if (stopAllBtn) stopAllBtn.disabled = !canRun;
}

function parseBulkWhitelistText(raw) {
    return String(raw || '')
        .split(/[\n,]/)
        .map((item) => item.trim())
        .filter(Boolean)
        .filter((value, index, arr) => arr.indexOf(value) === index);
}

    let BULK_AUTOSAVE_TIMER = null;

function normalizeAccountId(raw) {
    return String(raw || '').trim().replace(/\s+/g, '');
}

function normalizeBulkWhitelist(values) {
    return safeArray(values)
        .map((item) => normalizeAccountId(item))
        .filter(Boolean)
        .filter((value, index, arr) => arr.indexOf(value) === index);
}

function setBulkModalMessage(message, type = 'secondary') {
    const msgEl = el('bulk-whitelist-modal-msg');
    if (!msgEl) return;
    msgEl.textContent = message;
    msgEl.className = 'small mt-2 text-' + type;
}

function renderBulkWhitelistList() {
    const listEl = el('bulk-whitelist-list');
    if (!listEl) return;

    const whitelist = normalizeBulkWhitelist(BULK_CONTROL_WHITELIST);
    BULK_CONTROL_WHITELIST = whitelist;

    if (!whitelist.length) {
        listEl.innerHTML = '<span class="small text-secondary">Belum ada account di whitelist.</span>';
    } else {
        listEl.innerHTML = whitelist.map((accountId) => (
            '<span class="badge text-bg-light border d-inline-flex align-items-center gap-2">'
            + '<span title="' + escapeHtml(accountDisplayLabel(accountId)) + '">' + escapeHtml(accountDisplayLabel(accountId)) + '</span>'
            + '<button type="button" class="btn btn-sm btn-link p-0 text-danger" data-bulk-remove="' + escapeHtml(accountId) + '">x</button>'
            + '</span>'
        )).join('');
    }

    const selectEl = el('bulk-account-select');
    if (selectEl) {
        const accountIds = Object.keys(ACCOUNTS || {}).map((item) => normalizeAccountId(item)).filter(Boolean).sort();
        selectEl.innerHTML = '<option value="">-- Pilih account --</option>' + accountIds.map((id) => '<option value="' + escapeHtml(id) + '">' + escapeHtml(accountDisplayLabel(id)) + '</option>').join('');
    }

}

function scheduleBulkWhitelistAutosave(reason = '') {
    if (!IS_ADMIN) return;
    if (BULK_AUTOSAVE_TIMER) {
        clearTimeout(BULK_AUTOSAVE_TIMER);
        BULK_AUTOSAVE_TIMER = null;
    }

    BULK_AUTOSAVE_TIMER = setTimeout(() => {
        BULK_AUTOSAVE_TIMER = null;
        saveBulkWhitelistSettings({ silent: true, reason });
    }, 260);
}

function addAccountToWhitelist(raw) {
    const accountId = normalizeAccountId(raw);
    if (!accountId) {
        setBulkModalMessage('Account ID tidak boleh kosong.', 'warning');
        return;
    }

    const whitelist = normalizeBulkWhitelist(BULK_CONTROL_WHITELIST);
    if (whitelist.includes(accountId)) {
        setBulkModalMessage('Account ' + accountId + ' sudah ada di whitelist.', 'warning');
        return;
    }

    whitelist.push(accountId);
    BULK_CONTROL_WHITELIST = whitelist;
    renderBulkWhitelistList();
    updateBulkControlUi();
    setBulkModalMessage('Account ' + accountId + ' ditambahkan. Menyimpan otomatis...', 'success');
    scheduleBulkWhitelistAutosave('add');
}

function removeAccountFromWhitelist(accountId) {
    const normalized = normalizeAccountId(accountId);
    BULK_CONTROL_WHITELIST = normalizeBulkWhitelist(BULK_CONTROL_WHITELIST).filter((item) => item !== normalized);
    renderBulkWhitelistList();
    updateBulkControlUi();
    setBulkModalMessage('Account ' + normalized + ' dihapus. Menyimpan otomatis...', 'secondary');
    scheduleBulkWhitelistAutosave('remove');
}

function updateBulkSettingsForm(message, type = 'secondary') {
    const msgEl = el('bulk-whitelist-save-msg');
    if (!msgEl) return;
    msgEl.textContent = message;
    msgEl.className = 'small mt-2 text-' + type;
}

async function loadBulkWhitelistSettings() {
    if (!IS_ADMIN) {
        return;
    }

    try {
        const response = await fetch(ROUTES.botWhitelistGet, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        });
        const json = await response.json();
        if (!response.ok || !json.success) {
            throw new Error(String(json?.message || 'Gagal memuat pengaturan whitelist.'));
        }

        BULK_CONTROL_ENABLED = Boolean(json.enabled);
        BULK_CONTROL_WHITELIST = normalizeBulkWhitelist(json.whitelist);

        const enabledInput = el('bulk-enabled-input');
        if (enabledInput) enabledInput.checked = BULK_CONTROL_ENABLED;

        renderBulkWhitelistList();

        updateBulkSettingsForm('Pengaturan whitelist berhasil dimuat.', 'success');
        updateBulkControlUi();
    } catch (error) {
        updateBulkSettingsForm('Gagal memuat pengaturan whitelist: ' + String(error?.message || 'unknown error'), 'danger');
    }
}

async function saveBulkWhitelistSettings(options = {}) {
    if (!IS_ADMIN) {
        return;
    }

    const silent = Boolean(options?.silent);
    const enabledInput = el('bulk-enabled-input');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const enabled = Boolean(enabledInput?.checked);
    const whitelist = normalizeBulkWhitelist(BULK_CONTROL_WHITELIST);

    if (!silent) {
        updateBulkSettingsForm('Menyimpan pengaturan whitelist...', 'secondary');
    }

    try {
        const response = await fetch(ROUTES.botWhitelistUpdate, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                enabled,
                whitelist_text: whitelist.join(', '),
            }),
        });
        const json = await response.json();
        if (!response.ok || !json.success) {
            throw new Error(String(json?.message || 'Gagal menyimpan pengaturan whitelist.'));
        }

        BULK_CONTROL_ENABLED = Boolean(json.enabled);
        BULK_CONTROL_WHITELIST = normalizeBulkWhitelist(json.whitelist);

        if (enabledInput) enabledInput.checked = BULK_CONTROL_ENABLED;

        renderBulkWhitelistList();

        if (!silent) {
            updateBulkSettingsForm(String(json.message || 'Pengaturan whitelist berhasil disimpan.'), 'success');
        } else {
            updateBulkSettingsForm('Perubahan whitelist tersimpan otomatis.', 'success');
        }
        updateBulkControlUi();
    } catch (error) {
        updateBulkSettingsForm('Gagal menyimpan pengaturan whitelist: ' + String(error?.message || 'unknown error'), 'danger');
    }
}

function renderMonitoring() {
    const accountId = currentAccount();
    const pairSymbol = currentPairSymbol();
    const accountState = getActiveAccountState(accountId, pairSymbol);
    const licenseState = LICENSE_SNAPSHOTS[accountId] || {};
    const state = { ...DEFAULTS, ...accountState, ...licenseState };
    const currency = accountCurrencyFor(state);
    const heartbeatTs = Date.parse(String(state.updated_at || ''));
    const staleHeartbeat = Number.isFinite(heartbeatTs) ? (Date.now() - heartbeatTs > 45000) : true;
    const isOnlineFresh = Boolean(state.is_online) && !staleHeartbeat;
    const guardStatus = String(state.guard_status ?? state.live_guard_status ?? 'N/A').toUpperCase();
    const licenseActive = Boolean(state.license_active);
    const licenseLocked = LICENSE_ENFORCEMENT_ENABLED && !licenseActive;
    const settingsLockedByLicense = Boolean(accountId) && !licenseActive;
    const statusText = !isOnlineFresh
        ? 'OFFLINE'
        : (licenseLocked
            ? 'LICENSE EXPIRED'
            : (guardStatus === 'LIVE'
            ? 'ONLINE'
            : (guardStatus === 'DD_STOP' ? 'DD STOP' : 'PAUSED')));

    setSmooth('monitor-account-chip', 'Account: ' + (accountId || '-') + ' • Pair: ' + (pairSymbol || '-'));
    setSmooth('monitor-license-enforcement-chip', 'License Enforcement: ' + (LICENSE_ENFORCEMENT_ENABLED ? 'ON' : 'OFF'));
    const topbarLicenseCard = el('topbar-license-card');
    const topbarLicenseBadge = el('topbar-license-badge');
    const applyTopbarLicenseBadge = (text, stateClass) => {
        if (!topbarLicenseBadge) return;
        topbarLicenseBadge.textContent = text;
        topbarLicenseBadge.classList.remove('state-active', 'state-trial', 'state-expired');
        topbarLicenseBadge.classList.add(stateClass);
    };
    const isTrialPlan = String(state.license_plan_name ?? '').toLowerCase().includes('trial');

    if (!accountId) {
        syncLiveLicenseCountdown(accountId, state, false);
        setSmooth('topbar-license-status', 'Belum dipilih');
        setSmooth('topbar-license-remaining', 'Sisa: --:--:--');
        topbarLicenseCard?.setAttribute('data-license-state', 'inactive');
        applyTopbarLicenseBadge('NO LICENSE', 'state-expired');
    } else if (licenseActive) {
        syncLiveLicenseCountdown(accountId, state, true);
        setSmooth('topbar-license-status', 'Aktif');
        if (isTrialPlan) {
            topbarLicenseCard?.setAttribute('data-license-state', 'trial');
            applyTopbarLicenseBadge('TRIAL', 'state-trial');
        } else {
            topbarLicenseCard?.setAttribute('data-license-state', 'active');
            applyTopbarLicenseBadge('ACTIVE', 'state-active');
        }
        if (Boolean(state.license_is_perpetual)) {
            setSmooth('topbar-license-remaining', 'Countdown: Permanent');
            setSmooth('mon-license', 'Permanent');
        } else {
            renderLiveLicenseCountdownTick();
        }
    } else {
        syncLiveLicenseCountdown(accountId, state, false);
        setSmooth('topbar-license-status', 'Habis');
        setSmooth('topbar-license-remaining', 'Countdown: 00d 00h 00m 00s');
        topbarLicenseCard?.setAttribute('data-license-state', 'expired');
        applyTopbarLicenseBadge('NO LICENSE', 'state-expired');
    }
    setSmooth('mon-status', statusText);
    const bootstrapPending = Boolean(state._runtime_bootstrap_pending);
    const incomingOpenPositions = bootstrapPending ? [] : safeArray(state.open_positions);
    const hasMagicMetadata = incomingOpenPositions.some((row) => hasMagicField(row));
    const botOpenPositions = hasMagicMetadata
        ? incomingOpenPositions.filter((row) => isBotManagedPositionRow(row))
        : incomingOpenPositions;

    const derivedBotLayers = bootstrapPending
        ? 0
        : (hasMagicMetadata
        ? botOpenPositions.length
        : Number(state.current_layers ?? state.live_open_layers ?? 0));
    const derivedBotAccLot = bootstrapPending
        ? 0
        : (hasMagicMetadata
        ? botOpenPositions.reduce((acc, row) => {
            const lot = Number(row?.lot ?? row?.lots ?? row?.volume ?? 0);
            return acc + (Number.isFinite(lot) ? lot : 0);
        }, 0)
        : Number(state.current_accumulative_lot ?? state.live_accumulative_lot ?? 0));
    const derivedBotFloating = bootstrapPending
        ? 0
        : (hasMagicMetadata
        ? botOpenPositions.reduce((acc, row) => {
            const pnl = Number(row?.floating ?? (Number(row?.profit ?? 0) + Number(row?.swap ?? 0)));
            return acc + (Number.isFinite(pnl) ? pnl : 0);
        }, 0)
        : Number(state.global_floating ?? state.live_floating_pnl ?? 0));

    setSmooth('mon-layers', String(Math.max(0, Math.trunc(Number.isFinite(derivedBotLayers) ? derivedBotLayers : 0))));
    setSmooth('mon-lot', formatNumber(Number.isFinite(derivedBotAccLot) ? derivedBotAccLot : 0, 2));
    setSmooth('mon-balance', formatMoneyByCurrency(state.balance, currency));
    setSmooth('mon-equity', formatMoneyByCurrency(state.equity, currency));
    const liveDailyProfit = firstFiniteNumber(
        state.report_daily_profit,
        state.daily_profit,
    );
    setSmooth('mon-daily-profit', formatSignedMoneyByCurrency(liveDailyProfit, currency));
    applySignedTone('mon-daily-profit', liveDailyProfit);
    setSmooth('mon-winrate', formatNumber(state.win_rate_percent ?? 0, 2) + '%');
    setSmooth('mon-realized-profit', formatMoneyByCurrency(state.realized_profit ?? 0, currency));
    // Drawdown rendered after displayFloating is computed (see below)

    setSmooth('mon-strategy', strategyName(state.active_strategy ?? 0));
    setSmooth('mon-timeframe', 'M' + String(state.timeframe_logic ?? 1));
    setSmooth('mon-guard', guardStatus);
    if (!licenseActive) {
        setSmooth('mon-license', LICENSE_ENFORCEMENT_ENABLED ? 'Expired' : 'Not enforced');
    }
    setSmooth('mon-dd-debounce', String(Math.max(1, Math.trunc(Number(state.dd_breach_hits_required ?? DEFAULTS.dd_breach_hits_required ?? 15)))));
    setSmooth('mon-base-lot', formatNumber(state.base_lot ?? 0.01, 2));

    // Update bot toggle button based on guard_status
    const btnToggle = el('btn-bot-toggle');
    if (btnToggle) {
        btnToggle.disabled = !accountId || licenseLocked;
        const isLive = guardStatus === 'LIVE';
        if (isLive) {
            btnToggle.className = 'btn btn-danger bot-toggle-btn';
            const iconEl = el('btn-bot-icon');
            const labelEl = el('btn-bot-label');
            if (iconEl) iconEl.textContent = '||';
            if (labelEl) labelEl.textContent = 'Stop Bot';
        } else {
            btnToggle.className = 'btn btn-success bot-toggle-btn';
            const iconEl = el('btn-bot-icon');
            const labelEl = el('btn-bot-label');
            if (iconEl) iconEl.textContent = '>';
            if (labelEl) labelEl.textContent = 'Start Bot';
        }
    }

    // Handle DD status styling. Keep button clickable whenever an account is selected.
    const btnResetDd = el('btn-bot-reset-dd');
    if (btnResetDd) {
        const isDdStop = guardStatus === 'DD_STOP';
        btnResetDd.disabled = !accountId || licenseLocked;
        btnResetDd.style.display = 'inline-block';
        btnResetDd.title = isDdStop
            ? 'Reset Max Drawdown sekarang untuk mengaktifkan Start Bot lagi'
            : 'Klik untuk force reset guard ke LIVE';
        if (isDdStop) {
            btnResetDd.classList.remove('btn-outline-warning');
            btnResetDd.classList.add('btn-warning');
        } else {
            btnResetDd.classList.remove('btn-warning');
            btnResetDd.classList.add('btn-outline-warning');
        }
    }

    const closeAllBtn = el('btn-close-all-positions');
    if (closeAllBtn) {
        closeAllBtn.disabled = !accountId || licenseLocked;
    }
    const lockChanged = MONITOR_ACTIONS_LOCKED !== licenseLocked;
    MONITOR_ACTIONS_LOCKED = licenseLocked;

    const startAllBtn = el('btn-bot-start-all');
    if (startAllBtn) startAllBtn.disabled = !accountId || licenseLocked || startAllBtn.disabled;
    const stopAllBtn = el('btn-bot-stop-all');
    if (stopAllBtn) stopAllBtn.disabled = !accountId || licenseLocked || stopAllBtn.disabled;

    applyLicenseFormLock(settingsLockedByLicense);
    applyAccountSelectionLock(!accountId && !settingsLockedByLicense);

    updateBulkControlUi();

    setSmooth('mon-mirror', boolText(Boolean(state.use_mirror_trap || state.mirror_active)));
    setSmooth('mon-pending-distance', String(state.mirror_pending_distance_points ?? 0));
    setSmooth('mon-pending-multi', formatNumber(state.mirror_multiplier ?? 0, 2));
    setSmooth('mon-mode', Number(state.grid_mode ?? 0) === 0 ? 'Fix Points' : 'ATR');

    setSmooth('mon-session-sydney', boolText(Boolean(state.use_sydney_session)));
    setSmooth('mon-session-asia', boolText(Boolean(state.use_asia_session)));
    setSmooth('mon-session-europe', boolText(Boolean(state.use_europe_session)));
    setSmooth('mon-session-us', boolText(Boolean(state.use_us_session)));
    setSmooth('mon-stealth', boolText(Boolean(state.use_stealth_mode)));

    const cachedOpenPositions = safeArray(accountState._last_open_positions_rows);
    const nowMs = Date.now();
    const lastOpenRowsSeenAt = Number(accountState._last_open_positions_seen_at ?? 0);
    const holdWindowMs = 900;
    const activeLayers = Number.isFinite(derivedBotLayers) ? Number(derivedBotLayers) : 0;
    const botAccLot = Number.isFinite(derivedBotAccLot) ? Number(derivedBotAccLot) : 0;
    const incomingFloatingRaw = Number.isFinite(derivedBotFloating) ? Number(derivedBotFloating) : 0;
    const floatingExposureSignal = Number.isFinite(incomingFloatingRaw) && Math.abs(incomingFloatingRaw) > 0.0000001;
    const drawdownExposureSignal = Math.abs(Number(state.drawdown_pct ?? 0)) > 0.0000001;
    const shouldHoldByExposure = activeLayers > 0 || floatingExposureSignal || drawdownExposureSignal;
    const withinShortHoldWindow = (nowMs - lastOpenRowsSeenAt) <= holdWindowMs;
    const lastLiveSyncAt = Number(accountState._last_live_sync_at ?? 0);
    const recentlyLiveSynced = (nowMs - lastLiveSyncAt) <= 7000;
    const shouldHoldPreviousOpenRows = incomingOpenPositions.length === 0
        && shouldHoldByExposure
        && recentlyLiveSynced
        && withinShortHoldWindow
        && cachedOpenPositions.length > 0;
    const openRows = shouldHoldPreviousOpenRows ? cachedOpenPositions : incomingOpenPositions;

    let floatingFromRows = null;
    const botExposureActive = activeLayers > 0 || (Number.isFinite(botAccLot) && Math.abs(botAccLot) > 0.0000001);
    if (openRows.length > 0 && botExposureActive) {
        floatingFromRows = openRows.reduce((acc, row) => {
            const pnl = Number(row?.floating ?? (Number(row?.profit ?? 0) + Number(row?.swap ?? 0)));
            return acc + (Number.isFinite(pnl) ? pnl : 0);
        }, 0);
    }

    const incomingFloating = incomingFloatingRaw;
    const previousFloating = Number(accountState._last_monitor_floating ?? 0);
    let displayFloating = Number.isFinite(floatingFromRows)
        ? floatingFromRows
        : (Number.isFinite(incomingFloating) ? incomingFloating : previousFloating);
    if (activeLayers > 0 && !Number.isFinite(floatingFromRows) && Math.abs(displayFloating) < 0.0000001 && Math.abs(previousFloating) > 0.0000001) {
        displayFloating = previousFloating;
    }
    // Fallback: derive from equity-balance when no open_positions rows and global_floating is missing/zero
    const _fbBalance = Number(state.current_balance ?? state.balance ?? 0);
    const _fbEquity  = Number(state.current_equity  ?? state.equity  ?? 0);
    if (Math.abs(displayFloating) < 0.0000001 && _fbBalance > 0.0000001 && _fbEquity > 0.0000001) {
        const equityDerived = _fbEquity - _fbBalance;
        if (Math.abs(equityDerived) > 0.0000001) {
            displayFloating = equityDerived;
        }
    }
    accountState._last_monitor_floating = displayFloating;
    setSmooth('mon-floating', formatSignedMoneyByCurrency(displayFloating, currency));
    applySignedTone('mon-floating', displayFloating);

    // Drawdown: computed here (after displayFloating is ready) from floating/balance
    const _ddBalance = _fbBalance;
    let drawdownSigned;
    if (_ddBalance > 0.0000001) {
        drawdownSigned = (displayFloating / _ddBalance) * 100.0;
    } else {
        drawdownSigned = firstFiniteNumber(state.drawdown_pct, state.dd);
    }
    setSmooth('mon-drawdown', formatSignedPercent(drawdownSigned, 2));
    applySignedTone('mon-drawdown', drawdownSigned);

    if (incomingOpenPositions.length > 0) {
        accountState._last_open_positions_rows = incomingOpenPositions;
        accountState._last_open_positions_seen_at = nowMs;
    } else if (!shouldHoldPreviousOpenRows) {
        accountState._last_open_positions_rows = [];
        accountState._last_open_positions_seen_at = 0;
    }
    setStateByAccountPair(accountId, pairSymbol, accountState);

    if (licenseLocked) {
        const monitorMsg = el('monitor-action-msg');
        if (monitorMsg) {
            monitorMsg.textContent = 'Lisensi expired. Bot dan action kritikal dikunci sampai diperpanjang.';
            monitorMsg.className = 'small text-danger';
        }
    } else {
        const monitorMsg = el('monitor-action-msg');
        if (monitorMsg && String(monitorMsg.textContent || '').toLowerCase().includes('lisensi expired')) {
            monitorMsg.textContent = 'Gunakan tombol close untuk menutup semua posisi atau posisi per layer.';
            monitorMsg.className = 'small text-secondary mb-2';
        }
    }

    const newPosJson = JSON.stringify(openRows || []);
    if (newPosJson !== _positionsJson || lockChanged) {
        _positionsJson = newPosJson;
        renderOpenPositionsTable(openRows || []);
    }
    const newPendJson = JSON.stringify(state.pending_orders || []);
    if (newPendJson !== _pendingJson) {
        _pendingJson = newPendJson;
        renderPendingOrdersTable(state.pending_orders || []);
    }

    renderCalcDebug();
    renderAnalysis();
}

function renderAnalysis() {
    const accountId = currentAccount();
    const pairSymbol = currentPairSymbol();
    const state = { ...DEFAULTS, ...(getActiveAccountState(accountId, pairSymbol) || {}) };
    const analysis = state.analysis || {};

    const bias = analysisBiasLabel(analysis.bias || analysis.stable_bias || analysis.signal_bias || 'NEUTRAL');
    const biasClass = analysisBiasClass(bias);
    const power = Number(analysis.power_pct ?? analysis.signal_power_pct ?? 0);
    const confidence = Number(analysis.confidence_pct ?? analysis.stability_pct ?? analysis.vote_power_pct ?? 0);

    setSmooth('analysis-account-chip', 'Account: ' + (accountId || '-') + ' • Pair: ' + (pairSymbol || '-'));
    setSmooth('analysis-bias', bias);
    setSmoothNumber('analysis-score-buy', Number(analysis.score_buy ?? 0), { digits: 0, fallback: '0', durationMs: 700 });
    setSmoothNumber('analysis-score-sell', Number(analysis.score_sell ?? 0), { digits: 0, fallback: '0', durationMs: 700 });
    setSmoothNumber('analysis-votes-bull', Number(analysis.bull_votes ?? 0), { digits: 0, fallback: '0', durationMs: 700 });
    setSmoothNumber('analysis-votes-bear', Number(analysis.bear_votes ?? 0), { digits: 0, fallback: '0', durationMs: 700 });

    setSmoothNumber('analysis-adx', analysis.adx, { digits: 2, fallback: '-', durationMs: 700 });
    const dxyStatusRaw = String(analysis.dxy_status ?? '').trim();
    const microStatusRaw = String(analysis.micro_market_status ?? '').trim();
    const learningStatusRaw = String(analysis.learning_status ?? '').trim();
    const isPlaceholderText = (value) => {
        const normalized = String(value || '').trim().toUpperCase();
        return !normalized || ['-', 'N/A', 'NA', 'NONE', 'NULL', 'UNKNOWN'].includes(normalized);
    };

    const accountState = getActiveAccountState(accountId, pairSymbol) || {};
    if (!isPlaceholderText(dxyStatusRaw)) accountState._last_dxy_status = dxyStatusRaw;
    if (!isPlaceholderText(microStatusRaw)) accountState._last_micro_status = microStatusRaw;
    if (!isPlaceholderText(learningStatusRaw)) accountState._last_learning_status = learningStatusRaw;
    setStateByAccountPair(accountId, pairSymbol, accountState);

    const dxyStable = !isPlaceholderText(dxyStatusRaw)
        ? dxyStatusRaw
        : String(accountState._last_dxy_status || 'NO DATA');
    const microStable = !isPlaceholderText(microStatusRaw)
        ? microStatusRaw
        : String(accountState._last_micro_status || 'NO DATA');
    const learningStable = !isPlaceholderText(learningStatusRaw)
        ? learningStatusRaw
        : String(accountState._last_learning_status || 'NO DATA');

    setSmooth('analysis-dxy', dxyStable);
    setSmooth('analysis-micro', microStable);
    setSmooth('analysis-learning', learningStable);
    setSmooth('analysis-wait', String(Math.max(0, Number(analysis.signal_wait_seconds ?? 0))) + 's');
    setSmooth('analysis-queue', String(Math.max(0, Math.trunc(Number(analysis.api_queue_depth ?? 0)))));

    setSmooth('analysis-guard-commanded', String(analysis.guard_status_commanded ?? state.guard_status ?? '-'));
    setSmooth('analysis-guard-live', String(analysis.guard_status_live ?? state.live_guard_status ?? state.guard_status ?? '-'));
    setSmooth('analysis-dd-debounce', String(Math.max(1, Math.trunc(Number(analysis.dd_breach_hits_required ?? state.dd_breach_hits_required ?? DEFAULTS.dd_breach_hits_required ?? 15)))));
    const guardLiveText = String(analysis.guard_status_live ?? state.live_guard_status ?? state.guard_status ?? '').toUpperCase();
    const newsBlockedActive = Boolean(analysis.news_blocked) || guardLiveText.includes('PAUSED_NEWS');
    const remotePausedActive = Boolean(analysis.remote_paused) || guardLiveText.includes('PAUSED_REMOTE');
    setSmooth('analysis-news', boolText(newsBlockedActive));
    setSmooth('analysis-remote', boolText(remotePausedActive));
    setSmooth('analysis-strategy', strategyName(analysis.active_strategy ?? state.active_strategy ?? 0));
    setSmooth('analysis-timeframe', 'M' + String(analysis.timeframe_logic ?? state.timeframe_logic ?? 1));

    setSmoothNumber('analysis-spread', analysis.spread_points, { digits: 2, fallback: '-', durationMs: 700 });
    setSmoothNumber('analysis-atr', analysis.atr_points, { digits: 2, fallback: '-', durationMs: 700 });
    setSmoothNumber('analysis-spread-ratio', analysis.spread_atr_ratio, { digits: 3, fallback: '-', durationMs: 700 });
    setSmooth('analysis-spread-expensive', boolText(Boolean(analysis.spread_is_expensive)));
    setSmoothNumber('analysis-support', analysis.support_level, { digits: 3, fallback: '-', durationMs: 700 });
    setSmoothNumber('analysis-resistance', analysis.resistance_level, { digits: 3, fallback: '-', durationMs: 700 });

    setSmooth('analysis-session-sydney', boolText(Boolean(analysis?.sessions?.use_sydney_session ?? state.use_sydney_session)));
    setSmooth('analysis-session-asia', boolText(Boolean(analysis?.sessions?.use_asia_session ?? state.use_asia_session)));
    setSmooth('analysis-session-europe', boolText(Boolean(analysis?.sessions?.use_europe_session ?? state.use_europe_session)));
    setSmooth('analysis-session-us', boolText(Boolean(analysis?.sessions?.use_us_session ?? state.use_us_session)));
    updateAnalysisServerClock(analysis.server_time ?? '-', analysis.captured_at ?? null);
    setSmooth('analysis-age', Number.isFinite(Number(analysis.age_seconds)) ? String(Math.max(0, Math.trunc(Number(analysis.age_seconds)))) + 's' : '-');

    const mtf = (analysis.mtf_bias && typeof analysis.mtf_bias === 'object') ? analysis.mtf_bias : {};
    const mtfSummary = (mtf.summary && typeof mtf.summary === 'object') ? mtf.summary : {};
    const strictFinite = (value) => {
        if (value === null || value === undefined || value === '') return null;
        const num = Number(value);
        return Number.isFinite(num) ? num : null;
    };
    const mtfNodeLabel = (node) => {
        if (!node || typeof node !== 'object') return '-';
        const biasText = analysisBiasLabel(String(node.bias || 'NEUTRAL'));
        const scoreNum = strictFinite(node.score);
        const adxNum = strictFinite(node.adx);
        const rsiNum = strictFinite(node.rsi);
        const scoreText = scoreNum !== null ? (formatNumber(scoreNum, 2) + '%') : '-';
        const adxText = adxNum !== null ? formatNumber(adxNum, 1) : '-';
        const rsiText = rsiNum !== null ? formatNumber(rsiNum, 1) : '-';
        return biasText + ' | score ' + scoreText + ' | ADX ' + adxText + ' | RSI ' + rsiText;
    };

    setSmooth('analysis-mtf-m1', mtfNodeLabel(mtf.m1));
    setSmooth('analysis-mtf-m5', mtfNodeLabel(mtf.m5));
    setSmooth('analysis-mtf-m15', mtfNodeLabel(mtf.m15));
    setSmooth('analysis-mtf-h1', mtfNodeLabel(mtf.h1));
    setSmooth('analysis-mtf-summary-bias', analysisBiasLabel(String(mtfSummary.bias || analysis.mtf_summary_bias || 'NEUTRAL')));
    setSmooth('analysis-mtf-summary-score', Number.isFinite(Number(mtfSummary.score ?? analysis.mtf_summary_score))
        ? (formatNumber(Number(mtfSummary.score ?? analysis.mtf_summary_score), 2) + '%')
        : '-');

    const reasonSummary = String(analysis.reason_summary || mtfSummary.reason || buildSignalReasonNarrative(analysis, bias, power));
    const reasonDetails = Array.isArray(analysis.reason_details) ? analysis.reason_details : [];
    setSmooth('analysis-reason-summary', reasonSummary);
    setSmooth(
        'analysis-reason-details',
        reasonDetails.length
            ? reasonDetails.map((item) => '- ' + String(item)).join(' | ')
            : (String(mtfSummary.reason || '-') || '-')
    );

    const biasChip = el('analysis-bias-chip');
    if (biasChip) {
        biasChip.className = 'analysis-chip ' + biasClass;
        biasChip.textContent = 'POWER ' + formatNumber(power, 2) + '%';
    }

    const confChip = el('analysis-confidence-chip');
    if (confChip) {
        confChip.className = 'analysis-chip ' + biasClass;
        confChip.textContent = 'CONF ' + formatNumber(confidence, 2) + '%';
    }

    const updateChip = el('analysis-last-update');
    if (updateChip) {
        updateChip.className = 'analysis-chip ' + biasClass;
        updateChip.textContent = analysis.captured_at ? ('UPDATED ' + formatTime(analysis.captured_at)) : 'WAITING SIGNAL';
    }

    // Record signal reason only when signature changes (or after cooldown)
    const currentBias = bias;
    const score = Number(analysis.score_buy ?? 0);
    const scoreSell = Number(analysis.score_sell ?? 0);
    const bullVotes = Number(analysis.bull_votes ?? 0);
    const bearVotes = Number(analysis.bear_votes ?? 0);
    const adx = Number(analysis.adx ?? 0);
    const signalSignature = [
        currentBias,
        Math.round(power / 5) * 5,
        Math.round(confidence / 5) * 5,
        Math.trunc(score),
        Math.trunc(scoreSell),
        Math.trunc(bullVotes),
        Math.trunc(bearVotes),
        Math.round(adx),
        Boolean(analysis.spread_is_expensive) ? 1 : 0,
        newsBlockedActive ? 1 : 0,
        remotePausedActive ? 1 : 0,
    ].join('|');
    const lastSignature = String(accountState?._lastRecordedSignalSignature || '');
    const lastRecordedAt = Number(accountState?._lastRecordedSignalAt || 0);
    const prevBias = String(accountState?._lastRecordedBias || '');
    const prevPower = Number(accountState?._lastRecordedPower ?? 0);
    const prevNewsBlocked = Boolean(accountState?._lastRecordedNewsBlocked ?? false);
    const prevRemotePaused = Boolean(accountState?._lastRecordedRemotePaused ?? false);
    const majorShift = (prevBias !== '' && prevBias !== currentBias && Math.abs(power - prevPower) >= 8)
        || (Math.abs(power - prevPower) >= 15)
        || (newsBlockedActive !== prevNewsBlocked)
        || (remotePausedActive !== prevRemotePaused);
    const cooldownMs = currentBias === 'NEUTRAL' ? 180000 : 120000;
    const shouldRecord = lastSignature === '' || signalSignature !== lastSignature || majorShift || (Date.now() - lastRecordedAt) >= cooldownMs;

    if (shouldRecord) {
        const reason = String(analysis.reason_summary || buildSignalReasonNarrative(analysis, currentBias, power));
        recordSignalReason(accountId, pairSymbol, currentBias, power, reason, {
            score_buy: score,
            score_sell: scoreSell,
            bull_votes: bullVotes,
            bear_votes: bearVotes,
            adx,
            power,
            bias: currentBias,
            reason_details: Array.isArray(analysis.reason_details) ? analysis.reason_details : [],
        });
        setStateByAccountPair(accountId, pairSymbol, {
            ...(getActiveAccountState(accountId, pairSymbol) || {}),
            _lastRecordedBias: currentBias,
            _lastRecordedPower: power,
            _lastRecordedNewsBlocked: newsBlockedActive,
            _lastRecordedRemotePaused: remotePausedActive,
            _lastRecordedSignalSignature: signalSignature,
            _lastRecordedSignalAt: Date.now(),
        });
    }

    renderSignalReasonTimeline();
}

function renderReport(lastSave = '') {
    const accountId = currentAccount();
    const pairSymbol = currentPairSymbol();
    const state = { ...DEFAULTS, ...(getActiveAccountState(accountId, pairSymbol) || {}) };
    const currency = accountCurrencyFor(state);

    setSmooth('rep-account-id', accountId || '-');
    setSmooth('rep-strategy', strategyName(state.active_strategy ?? 0));
    setSmooth('rep-news-severity', String(state.news_filter_severity ?? 'HIGH'));
    setSmooth('rep-snr', boolText(Boolean(state.filter_snr_activation)));
    setSmooth('rep-last-save', lastSave || 'Belum ada aksi simpan.');
    setSmooth('rep-before-news', String(state.news_pause_before_minutes ?? 0));
    setSmooth('rep-after-news', String(state.news_pause_after_minutes ?? 0));
    setSmooth('rep-widget-time', new Date().toLocaleTimeString('id-ID'));
    setSmooth('rep-news-count', String(NEWS_ITEMS.length));
    setSmooth('rep-theme', (document.body.getAttribute('data-theme') || 'light') === 'dark' ? 'Dark' : 'Light');
    setSmooth('rep-winrate', formatNumber(state.win_rate_percent ?? 0, 2) + '%');
    setSmooth('rep-wl', String(state.wins ?? 0) + '/' + String(state.losses ?? 0));
    setSmooth('rep-profit-daily', formatSignedMoneyByCurrency(state.daily_profit ?? 0, currency));
    applySignedTone('rep-profit-daily', state.daily_profit ?? 0);
    setSmooth('rep-profit-weekly', formatSignedMoneyByCurrency(state.weekly_profit ?? 0, currency));
    applySignedTone('rep-profit-weekly', state.weekly_profit ?? 0);
    setSmooth('rep-profit-monthly', formatSignedMoneyByCurrency(state.monthly_profit ?? 0, currency));
    applySignedTone('rep-profit-monthly', state.monthly_profit ?? 0);
    setSmooth('rep-profit-realized', formatMoneyByCurrency(state.realized_profit ?? 0, currency));
    setSmooth('rep-reset-at', state.wr_reset_at ? formatTime(state.wr_reset_at) : 'Belum pernah');
    renderHistoryTable(state.history || []);
    renderHistoryPagination();
    renderCalcDebug();
    renderAnalysis();
}

let _monitoringInflight = false;
let _monitoringAbortController = null;

async function refreshMonitoringOnly() {
    const accountId = currentAccount();
    if (!accountId) return;
    const pairSymbol = currentPairSymbol();

    if (_monitoringInflight) return;

    _monitoringInflight = true;
    const controller = new AbortController();
    _monitoringAbortController = controller;
    const timeoutHandle = setTimeout(() => {
        try {
            controller.abort();
        } catch (_e) {
        }
    }, 7000);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    try {
        const url = ROUTES.monitoringLive
            + '?account_id=' + encodeURIComponent(accountId)
            + '&pair_symbol=' + encodeURIComponent(pairSymbol)
            + (CALC_DEBUG ? '&calc_debug=1' : '')
            + '&_ts=' + Date.now();
        const res = await fetch(url, {
            method: 'GET',
            cache: 'no-store',
            signal: controller.signal,
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'Cache-Control': 'no-cache' },
        });
        const json = await res.json();
        if (res.ok && json.success) {
            const REPORT_FIELDS = ['daily_profit','weekly_profit','monthly_profit',
                                   'realized_profit','win_rate_percent','wins','losses',
                                   'history'];
            REPORT_FIELDS.forEach(k => delete json[k]);
            if (shouldApplyMonitoringPayload(accountId, pairSymbol, json)) {
                setStateByAccountPair(accountId, pairSymbol, {
                    ...json,
                    _last_live_sync_at: Date.now(),
                    _runtime_bootstrap_pending: false,
                });
                DASHBOARD_LAST_MONITORING_SYNC_AT = Date.now();
                if (CALC_DEBUG) {
                    setStateByAccountPair(accountId, pairSymbol, { monitoring_calc_debug: json?.calc_debug || null });
                }
            }
            renderMonitoring();
        }
    } catch (_e) {
    } finally {
        clearTimeout(timeoutHandle);
        if (_monitoringAbortController === controller) {
            _monitoringAbortController = null;
            _monitoringInflight = false;
        }
    }
}

async function refreshConnectedPairsRealtime() {
    const accountId = String(currentAccount() || '').trim();
    if (!accountId) return;

    const now = Date.now();
    if ((now - Number(DASHBOARD_LAST_PAIR_DISCOVERY_AT || 0)) < 700) return;
    DASHBOARD_LAST_PAIR_DISCOVERY_AT = now;

    const activePair = normalizePairSymbol(currentPairSymbol());
    const knownPairs = getPairsForAccount(accountId).filter((pair) => pair !== activePair);
    if (!knownPairs.length) {
        renderPairTabsForCurrentAccount();
        return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let hasAnyUpdate = false;

    await Promise.all(knownPairs.map(async (pairSymbol) => {
        const inflightKey = accountPairKey(accountId, pairSymbol);
        if (DASHBOARD_PAIR_DISCOVERY_INFLIGHT[inflightKey]) return;

        DASHBOARD_PAIR_DISCOVERY_INFLIGHT[inflightKey] = true;
        try {
            const url = ROUTES.monitoringLive
                + '?account_id=' + encodeURIComponent(accountId)
                + '&pair_symbol=' + encodeURIComponent(pairSymbol)
                + '&_ts=' + Date.now();

            const response = await fetch(url, {
                method: 'GET',
                cache: 'no-store',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'Cache-Control': 'no-cache' },
            });
            const json = await response.json();
            if (!response.ok || !json.success) return;

            const prevState = getStateByAccountPair(accountId, pairSymbol) || {};
            if (shouldApplyMonitoringPayload(accountId, pairSymbol, json)) {
                setStateByAccountPair(accountId, pairSymbol, {
                    ...json,
                    _last_live_sync_at: Date.now(),
                    _runtime_bootstrap_pending: false,
                });
            }

            const prevOnline = isStateFreshOnline(prevState);
            const nextOnline = isStateFreshOnline(getStateByAccountPair(accountId, pairSymbol) || {});
            const prevUpdated = String(prevState.updated_at || '');
            const nextUpdated = String((json && json.updated_at) || '');
            if (prevOnline !== nextOnline || (nextUpdated && nextUpdated !== prevUpdated)) {
                hasAnyUpdate = true;
            }
        } catch (_error) {
        } finally {
            delete DASHBOARD_PAIR_DISCOVERY_INFLIGHT[inflightKey];
        }
    }));

    if (hasAnyUpdate) {
        renderPairTabsForCurrentAccount();
    }
}

async function refreshReportOnly(options = {}) {
    const source = String(options.source || 'manual');
    const accountId = currentAccount();
    if (!accountId) return;
    const pairSymbol = currentPairSymbol();

    if (REPORTS_STATE.isLoading) {
        REPORTS_STATE.pendingRefresh = true;
        return;
    }

    const controller = new AbortController();
    const timeoutHandle = setTimeout(() => {
        try {
            controller.abort();
        } catch (_e) {
        }
    }, 8000);
    REPORTS_STATE.isLoading = true;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    try {
        const selectedLimitRaw = REPORTS_STATE.pendingPerPage
            ?? el('rep-history-limit')?.value
            ?? REPORTS_STATE.perPage
            ?? 10;
        const selectedPeriodRaw = REPORTS_STATE.period || el('rep-history-period')?.value || 'all';
        const selectedPeriod = ['all', 'today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_30_days'].includes(String(selectedPeriodRaw))
            ? String(selectedPeriodRaw)
            : 'all';
        REPORTS_STATE.period = selectedPeriod;
        const limit = Math.max(5, Number(selectedLimitRaw));
        const requestedPageRaw = Number(REPORTS_STATE.pendingPage ?? REPORTS_STATE.page);
        const requestedPage = Number.isFinite(requestedPageRaw) ? Math.max(1, requestedPageRaw) : 1;

        REPORTS_STATE.pendingPerPage = null;
        REPORTS_STATE.pendingPage = null;

        const url = ROUTES.reportsLive
            + '?account_id=' + encodeURIComponent(accountId)
            + '&pair_symbol=' + encodeURIComponent(pairSymbol)
            + '&limit=' + encodeURIComponent(String(limit))
            + '&page=' + encodeURIComponent(String(requestedPage))
            + '&period=' + encodeURIComponent(selectedPeriod)
            + (CALC_DEBUG ? '&calc_debug=1' : '')
            + '&_ts=' + Date.now();
        const res = await fetch(url, {
            method: 'GET',
            cache: 'no-store',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'Cache-Control': 'no-cache' },
            signal: controller.signal,
        });
        const json = await res.json();
        if (res.ok && json.success) {
            REPORTS_STATE.page     = Number(json?.history_meta?.current_page ?? 1);
            REPORTS_STATE.lastPage = Number(json?.history_meta?.last_page ?? 1);
            REPORTS_STATE.total    = Number(json?.history_meta?.total ?? 0);
            REPORTS_STATE.perPage  = Number(json?.history_meta?.per_page ?? limit);
            const prevState = getStateByAccountPair(accountId, pairSymbol) || {};
            const incomingHistory = safeArray(json.history);
            const incomingHistoryTotal = Number(json?.history_meta?.total ?? incomingHistory.length);
            const resolvedHistory = incomingHistory.length > 0
                ? incomingHistory
                : (incomingHistoryTotal === 0 ? [] : safeArray(prevState.history));
            const incomingAnalysis = (json?.analysis && typeof json.analysis === 'object') ? json.analysis : null;
            const resolvedAnalysis = hasMeaningfulAnalysisSnapshot(incomingAnalysis)
                ? incomingAnalysis
                : (prevState.analysis || null);
            setStateByAccountPair(accountId, pairSymbol, {
                history: resolvedHistory,
                wins: coalesceFiniteNumber(json?.wr?.wins, prevState.wins),
                losses: coalesceFiniteNumber(json?.wr?.losses, prevState.losses),
                win_rate_percent: coalesceFiniteNumber(json?.wr?.win_rate_percent, prevState.win_rate_percent),
                wr_reset_at: json?.wr?.reset_at || null,
                realized_profit: coalesceFiniteNumber(json?.profit?.realized, prevState.realized_profit),
                daily_profit: coalesceFiniteNumber(json?.profit?.daily, prevState.daily_profit),
                weekly_profit: coalesceFiniteNumber(json?.profit?.weekly, prevState.weekly_profit),
                monthly_profit: coalesceFiniteNumber(json?.profit?.monthly, prevState.monthly_profit),
                report_daily_profit: coalesceFiniteNumber(json?.profit?.daily, prevState.report_daily_profit ?? prevState.daily_profit),
                report_weekly_profit: coalesceFiniteNumber(json?.profit?.weekly, prevState.report_weekly_profit ?? prevState.weekly_profit),
                report_monthly_profit: coalesceFiniteNumber(json?.profit?.monthly, prevState.report_monthly_profit ?? prevState.monthly_profit),
                report_realized_profit: coalesceFiniteNumber(json?.profit?.realized, prevState.report_realized_profit ?? prevState.realized_profit),
                analysis: resolvedAnalysis,
            });
            DASHBOARD_LAST_REPORT_SYNC_AT = Date.now();
            if (CALC_DEBUG) {
                setStateByAccountPair(accountId, pairSymbol, { report_calc_debug: json?.calc_debug || null });
            }
            renderReport(el('save-msg')?.textContent || '');
            renderMonitoring();
        } else {
            DASHBOARD_LAST_REPORT_SYNC_AT = Date.now();
            const failMessage = String(json?.message || 'Report live gagal dimuat.');
            renderReport(failMessage);
        }
    } catch (_e) {
        DASHBOARD_LAST_REPORT_SYNC_AT = Date.now();
        const failMessage = source === 'manual'
            ? 'Report timeout/gagal. Cek koneksi atau account mapping.'
            : (el('save-msg')?.textContent || '');
        renderReport(failMessage);
    } finally {
        clearTimeout(timeoutHandle);
        REPORTS_STATE.isLoading = false;

        if (REPORTS_STATE.pendingRefresh) {
            REPORTS_STATE.pendingRefresh = false;
            refreshReportOnly({ source: 'queued' });
        }
    }
}

async function refreshLiveTelemetry(force = false) {
    const streamHealthy = Boolean(DASHBOARD_LIVE_SOURCE && DASHBOARD_LIVE_SOURCE.readyState === EventSource.OPEN)
        && (Date.now() - Number(DASHBOARD_LAST_STREAM_EVENT_AT || 0) <= DASHBOARD_STALE_STREAM_MS);
    if (!force && streamHealthy) {
        return;
    }
    await refreshMonitoringOnly();
    await refreshReportOnly();
}

async function resetWrBaseline() {
    const accountId = currentAccount();
    if (!accountId) return;
    const pairSymbol = currentPairSymbol();
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    try {
        const res = await fetch(ROUTES.reportsResetWr, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ account_id: accountId, pair_symbol: pairSymbol }),
        });

        const json = await res.json();
        if (!res.ok || !json.success) {
            return;
        }

        await refreshLiveTelemetry(true);
    } catch (_error) {
    }
}

function strategyName(strategy) {
    if (Number(strategy) === 0) return 'GRID SPAM';
    if (Number(strategy) === 1) return 'ZERO GAP';
    if (Number(strategy) === 2) return 'PURE MARTINGALE';
    return 'UNKNOWN';
}

function applyLicenseFormLock(locked) {
    const licenseBannerHtml = (title, copy) => {
        return '<div class="license-warning-banner">'
            + '<div class="license-warning-copy">'
            + '<div class="license-warning-title">' + escapeHtml(title) + '</div>'
            + '<div class="license-warning-text">' + escapeHtml(copy) + '</div>'
            + '</div>'
            + '<a class="license-warning-action" href="' + escapeHtml(ROUTES.licenseRenew || '#') + '">' + escapeHtml(IS_ADMIN ? 'Kelola Lisensi' : 'Perbarui ke Billing') + '</a>'
            + '</div>';
    };

    const paneConfigs = [
        { id: 'workspace-pane-settings', visualLock: true },
        { id: 'workspace-pane-logic', visualLock: true },
        { id: 'workspace-pane-monitoring', visualLock: false },
        { id: 'workspace-pane-analysis', visualLock: false },
        { id: 'workspace-pane-bookkeeping', visualLock: false },
        { id: 'workspace-pane-reports', visualLock: false },
    ];

    paneConfigs.forEach(({ id: paneId, visualLock }) => {
        const pane = el(paneId);
        if (!pane) return;

        pane.classList.toggle('pane-license-locked', Boolean(locked) && Boolean(visualLock));
        pane.setAttribute('aria-disabled', locked ? 'true' : 'false');

        pane.querySelectorAll('input, select, textarea, button').forEach((control) => {
            if (!(control instanceof HTMLInputElement || control instanceof HTMLSelectElement || control instanceof HTMLTextAreaElement || control instanceof HTMLButtonElement)) {
                return;
            }

            if (locked) {
                if (!control.dataset.licensePrevDisabled) {
                    control.dataset.licensePrevDisabled = control.disabled ? '1' : '0';
                }
                control.disabled = true;
            } else if (control.dataset.licensePrevDisabled) {
                control.disabled = control.dataset.licensePrevDisabled === '1';
                delete control.dataset.licensePrevDisabled;
            }
        });
    });

    const saveMsg = el('save-msg');
    if (saveMsg) {
        if (locked) {
            saveMsg.textContent = 'Lisensi account tidak aktif. Form Settings dikunci sampai lisensi aktif kembali.';
            saveMsg.className = 'small mt-2 text-danger';
        } else {
            saveMsg.textContent = 'Belum ada perubahan tersimpan.';
            saveMsg.className = 'small mt-2 text-secondary';
        }
    }

    const saveLogicMsg = el('save-msg-logic');
    if (saveLogicMsg) {
        if (locked) {
            saveLogicMsg.textContent = 'Lisensi account tidak aktif. Form Logic dikunci sampai lisensi aktif kembali.';
            saveLogicMsg.className = 'small text-danger';
        } else if (!IS_ADMIN) {
            saveLogicMsg.textContent = 'Tab Logic hanya bisa diubah oleh admin.';
            saveLogicMsg.className = 'small text-danger';
        } else {
            saveLogicMsg.textContent = 'Klik untuk menyimpan perubahan logic di panel ini.';
            saveLogicMsg.className = 'small text-secondary';
        }
    }

    const settingsLockMsg = el('license-lock-msg-settings');
    if (settingsLockMsg) {
        settingsLockMsg.innerHTML = locked
            ? licenseBannerHtml(
                'Lisensi account sudah habis',
                'Tab Settings dikunci sampai lisensi diperpanjang. Klik tombol di samping untuk langsung membuka halaman pembaruan lisensi.'
            )
            : '';
    }

    const logicLockMsg = el('license-lock-msg-logic');
    if (logicLockMsg) {
        logicLockMsg.innerHTML = locked
            ? licenseBannerHtml(
                'Lisensi account sudah habis',
                'Tab Logic dikunci sampai lisensi aktif kembali. Perbarui lisensi agar pengaturan logic bisa dipakai lagi.'
            )
            : '';
    }

    const monitorLockMsg = el('license-lock-msg-monitor');
    if (monitorLockMsg) {
        monitorLockMsg.innerHTML = locked
            ? licenseBannerHtml(
                'Lisensi account sudah habis',
                'Aksi Monitoring yang mengubah state bot diproteksi. Perbarui lisensi agar Start, Stop, dan kontrol aktif kembali.'
            )
            : '';
        monitorLockMsg.className = locked ? 'mt-3' : 'small text-secondary mt-1';
    }

    const analysisLockMsg = el('license-lock-msg-analysis');
    if (analysisLockMsg) {
        analysisLockMsg.innerHTML = locked
            ? licenseBannerHtml(
                'Lisensi account sudah habis',
                'Analysis hanya tampil read-only selama lisensi tidak aktif. Perbarui lisensi untuk mengaktifkan kembali seluruh fitur terkait.'
            )
            : '';
        analysisLockMsg.className = locked ? 'mt-3' : 'small text-secondary mt-1';
    }

    const reportsLockMsg = el('license-lock-msg-reports');
    if (reportsLockMsg) {
        reportsLockMsg.innerHTML = locked
            ? licenseBannerHtml(
                'Lisensi account sudah habis',
                'Kontrol refresh dan reset report dikunci sampai lisensi aktif kembali. Gunakan tombol berikut untuk memperbarui lisensi dengan cepat.'
            )
            : '';
        reportsLockMsg.className = locked ? 'mt-3' : 'small text-secondary mt-1';
    }
}

function applyAccountSelectionLock(locked) {
    const paneIds = ['workspace-pane-settings', 'workspace-pane-logic'];

    paneIds.forEach((paneId) => {
        const pane = el(paneId);
        if (!pane) return;

        pane.setAttribute('data-account-required-locked', locked ? '1' : '0');
        pane.querySelectorAll('input, select, textarea, button').forEach((control) => {
            if (!(control instanceof HTMLInputElement || control instanceof HTMLSelectElement || control instanceof HTMLTextAreaElement || control instanceof HTMLButtonElement)) {
                return;
            }

            if (locked) {
                if (!control.dataset.accountPrevDisabled) {
                    control.dataset.accountPrevDisabled = control.disabled ? '1' : '0';
                }
                control.disabled = true;
            } else if (control.dataset.accountPrevDisabled) {
                control.disabled = control.dataset.accountPrevDisabled === '1';
                delete control.dataset.accountPrevDisabled;
            }
        });
    });

    const settingsLockMsg = el('license-lock-msg-settings');
    if (settingsLockMsg) {
        if (locked) {
            settingsLockMsg.innerHTML = '<div class="alert alert-secondary py-2 px-3 mb-0" data-account-lock="1">Pilih account MT5 dulu untuk membuka tab Settings.</div>';
        } else if (settingsLockMsg.querySelector('[data-account-lock="1"]')) {
            settingsLockMsg.innerHTML = '';
        }
    }

    const logicLockMsg = el('license-lock-msg-logic');
    if (logicLockMsg) {
        if (locked) {
            logicLockMsg.innerHTML = '<div class="alert alert-secondary py-2 px-3 mb-0" data-account-lock="1">Pilih account MT5 dulu untuk membuka tab Logic.</div>';
        } else if (logicLockMsg.querySelector('[data-account-lock="1"]')) {
            logicLockMsg.innerHTML = '';
        }
    }

    const saveMsg = el('save-msg');
    if (saveMsg && locked) {
        saveMsg.textContent = 'Pilih account MT5 dulu untuk mengubah Settings.';
        saveMsg.className = 'small mt-2 text-warning';
    }

    const saveLogicMsg = el('save-msg-logic');
    if (saveLogicMsg && locked) {
        saveLogicMsg.textContent = 'Pilih account MT5 dulu untuk mengubah Logic.';
        saveLogicMsg.className = 'small text-warning';
    }
}

function toggleStrategyPanels() {
    const strategy = Number(el('active_strategy').value || 0);
    el('panel-grid').classList.toggle('is-hidden', strategy !== 0);
    el('panel-zero-gap').classList.toggle('is-hidden', strategy !== 1);
    el('panel-martingale').classList.toggle('is-hidden', strategy !== 2);
}

function toggleWorkspaceThemeCard() {
    const theme = (document.body.getAttribute('data-theme') || 'light') === 'dark' ? 'dark' : 'light';
    document.querySelectorAll('.switch-tile, .meta-card, .settings-card, .report-card').forEach((node) => {
        node.classList.toggle('logic-accent-card', theme === 'dark');
    });
}

function syncGridLotScalingInputsFromCore() {
    if (el('grid_mart_type') && el('mart_type')) {
        el('grid_mart_type').value = String(el('mart_type').value || '0');
    }
    if (el('grid_mart_addition') && el('mart_addition')) {
        el('grid_mart_addition').value = String(el('mart_addition').value || '0');
    }
    if (el('grid_mart_multiplier') && el('mart_multiplier')) {
        el('grid_mart_multiplier').value = String(el('mart_multiplier').value || '1');
    }
}

function syncCoreLotScalingInputsFromGrid() {
    if (el('mart_type') && el('grid_mart_type')) {
        el('mart_type').value = String(el('grid_mart_type').value || '0');
    }
    if (el('mart_addition') && el('grid_mart_addition')) {
        el('mart_addition').value = String(el('grid_mart_addition').value || '0');
    }
    if (el('mart_multiplier') && el('grid_mart_multiplier')) {
        el('mart_multiplier').value = String(el('grid_mart_multiplier').value || '1');
    }
}

function toggleGridLotScalingMode() {
    const mode = Number(el('grid_mart_type')?.value || el('mart_type')?.value || 0);
    const isAddition = mode === 0;
    if (el('grid-mart-addition-wrap')) {
        el('grid-mart-addition-wrap').classList.toggle('disabled-block', !isAddition);
    }
    if (el('grid-mart-multiplier-wrap')) {
        el('grid-mart-multiplier-wrap').classList.toggle('disabled-block', isAddition);
    }
    if (el('grid_mart_addition')) {
        el('grid_mart_addition').disabled = !isAddition;
    }
    if (el('grid_mart_multiplier')) {
        el('grid_mart_multiplier').disabled = isAddition;
    }
}

function toggleDependentState() {
    const basketEnabled = el('grid_use_basket_tp_percent').checked;
    el('grid-basket-field-wrap').classList.toggle('disabled-block', !basketEnabled);
    el('grid_basket_tp_percent').disabled = !basketEnabled;

    if (el('grid_tp_mode')) {
        el('grid_tp_mode').disabled = !basketEnabled;
    }

    const tierMode = Number(el('grid_tp_mode')?.value || 0) === 1;
    const tierEditable = basketEnabled && tierMode;
    el('grid-tier-wrap').classList.toggle('disabled-block', !basketEnabled);
    ['grid_tier1_tp_percent', 'grid_tier2_tp_percent', 'grid_tier3_tp_percent', 'grid_tier4_tp_percent'].forEach((id) => {
        if (el(id)) el(id).disabled = !tierEditable;
    });

    ['sydney', 'asia', 'europe', 'us'].forEach((key) => {
        const enabled = el('use_' + key + '_session').checked;
        document.querySelectorAll('.session-box[data-session="' + key + '"] .session-time-wrap').forEach((wrap) => {
            wrap.classList.toggle('disabled-block', !enabled);
        });
        ['start_wib', 'end_wib'].forEach((suffix) => {
            const input = el(key + '_' + suffix);
            input.disabled = !enabled;
        });
    });

    toggleGridLotScalingMode();
}

function loadAccountForm(accountId) {
    let options = {};
    if (typeof arguments[1] === 'object' && arguments[1] !== null) {
        options = arguments[1];
    }
    const deferInitialRender = Boolean(options.deferInitialRender);
    const account = getActiveAccountState(accountId, currentPairSymbol()) || {};
    const state = { ...DEFAULTS, ...account };

    FIELD_IDS.forEach((id) => {
        if (!el(id)) return;
        if (isCheckbox(id)) {
            el(id).checked = Boolean(state[id]);
        } else {
            el(id).value = state[id] ?? DEFAULTS[id] ?? '';
        }
    });

    if (el('use_mirror_trap_mart')) {
        el('use_mirror_trap_mart').checked = Boolean(state.use_mirror_trap);
    }

    if (!IS_ADMIN && el('active_strategy') && String(el('active_strategy').value || '0') !== '0') {
        el('active_strategy').value = '0';
    }

    syncGridLotScalingInputsFromCore();

    toggleStrategyPanels();
    toggleDependentState();
    toggleWorkspaceThemeCard();
    syncRiskAckCheckbox();
    captureInlineSaveBaseline();
    refreshInlineSaveButtons();
    markClean();
    if (deferInitialRender) {
        refreshLiveTelemetry(true).finally(() => {
            renderMonitoring();
            renderAnalysis();
            renderReport(el('save-msg')?.textContent || '');
        });
    } else {
        renderMonitoring();
        renderAnalysis();
        renderReport(el('save-msg')?.textContent || '');
        refreshLiveTelemetry(true);
    }
}

function buildPayload() {
    syncCoreLotScalingInputsFromGrid();

    const payload = { account_id: currentAccount(), pair_symbol: currentPairSymbol() };

    FIELD_IDS.forEach((id) => {
        if (!el(id)) return;
        if (!IS_ADMIN && LOGIC_ONLY_FIELD_IDS.includes(id)) return;
        payload[id] = isCheckbox(id) ? el(id).checked : el(id).value;
    });

    // Client feature-flag: strategy selector is restricted to Grid Spam.
    if (!IS_ADMIN) {
        payload.active_strategy = 0;
    }

    // Protective override logic: Only override fields NOT already in FIELD_IDS to avoid double-set
    // Kombo field untuk Mirror Trap strategy
    if (!FIELD_IDS.includes('mirror_active')) {
        payload.use_mirror_trap = el('use_mirror_trap').checked || el('use_mirror_trap_mart').checked;
        payload.mirror_active = payload.use_mirror_trap;
    }

    // Re-confirm use_pending_guard bila overridden oleh mirror_trap logic
    if (payload.use_mirror_trap) {
        payload.use_pending_guard = true; // Mirror trap strategy always needs pending guard
    } else {
        // Otherwise use explicit use_pending_guard checkbox
        payload.use_pending_guard = el('use_pending_guard')?.checked ?? false;
    }

    return payload;
}

function applyLogicRoleLock() {
    const logicPane = el('workspace-pane-logic');
    if (!logicPane) return;

    const lockByRole = !IS_ADMIN;
    logicPane.classList.toggle('logic-admin-locked', lockByRole);

    logicPane.querySelectorAll('input, select, textarea, button').forEach((control) => {
        if (!(control instanceof HTMLInputElement || control instanceof HTMLSelectElement || control instanceof HTMLTextAreaElement || control instanceof HTMLButtonElement)) {
            return;
        }

        if (lockByRole) {
            if (!control.dataset.rolePrevDisabled) {
                control.dataset.rolePrevDisabled = control.disabled ? '1' : '0';
            }
            control.disabled = true;
        } else if (control.dataset.rolePrevDisabled) {
            control.disabled = control.dataset.rolePrevDisabled === '1';
            delete control.dataset.rolePrevDisabled;
        }
    });

    if (lockByRole && el('save-msg-logic')) {
        el('save-msg-logic').textContent = 'Tab Logic hanya bisa diubah oleh admin.';
        el('save-msg-logic').className = 'small text-danger';
    }
}

function markDirty() {
    el('save-bar-settings')?.classList.remove('is-hidden');
    el('save-msg').textContent = 'Perubahan terdeteksi. Simpan untuk menerapkan konfigurasi baru.';
    el('save-msg').className = 'small mt-2 text-warning';
    refreshInlineSaveButtons();
}

function scheduleToggleAutoSave(fieldId) {
    if (!fieldId || !isCheckbox(fieldId) || !currentAccount()) return;
    if (!isFieldDirty(fieldId)) return;

    if (TOGGLE_AUTOSAVE_TIMERS[fieldId]) {
        clearTimeout(TOGGLE_AUTOSAVE_TIMERS[fieldId]);
    }

    TOGGLE_AUTOSAVE_TIMERS[fieldId] = setTimeout(async () => {
        delete TOGGLE_AUTOSAVE_TIMERS[fieldId];
        if (!isFieldDirty(fieldId)) return;
        await saveSetting(fieldId);
    }, 280);
}

function markClean(message = 'Pengaturan sudah sinkron dengan data terakhir account ini.') {
    el('save-bar-settings')?.classList.add('is-hidden');
    el('save-msg').textContent = message;
    el('save-msg').className = 'small mt-2 text-secondary';
    captureInlineSaveBaseline();
    refreshInlineSaveButtons();
}

async function saveSetting(changedFieldId = '') {
    if (!currentAccount()) return;

    const accountId = currentAccount();
    const licenseState = LICENSE_SNAPSHOTS[accountId] || {};
    if (!Boolean(licenseState.license_active)) {
        const lockMessage = 'Lisensi account tidak aktif. Simpan pengaturan diblokir.';
        if (el('save-msg')) {
            el('save-msg').textContent = lockMessage;
            el('save-msg').className = 'small mt-2 text-danger';
        }
        if (el('save-msg-logic')) {
            el('save-msg-logic').textContent = lockMessage;
            el('save-msg-logic').className = 'small text-danger';
        }
        return;
    }

    if (!validateLogicInputs(true)) return;

    const payload = buildPayload();
    const activeWorkspaceTab = normalizeWorkspaceTab(document.querySelector('#workspace-tabs [data-workspace-tab].active')?.getAttribute('data-workspace-tab') || DEFAULT_WORKSPACE_TAB);
    if (IS_ADMIN && activeWorkspaceTab === 'logic') {
        payload.apply_logic_globally = true;
    }
    el('btn-save').disabled = true;
    el('btn-save').textContent = 'Menyimpan...';

    try {
        const response = await fetch('http://localhost/dashboard/settings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const json = await response.json();
        if (!response.ok || !json.success) {
            throw new Error(json.message || 'Gagal menyimpan pengaturan.');
        }

        setStateByAccountPair(accountId, currentPairSymbol(), { ...payload, ...(json.data || {}) });
        markClean(json.message || 'Setting updated successfully.');
        showDashboardToast(json.message || 'Pengaturan tersimpan.');
        renderMonitoring();
        renderReport(json.message || 'Setting updated successfully.');
    } catch (error) {
        el('save-bar-settings')?.classList.remove('is-hidden');
        el('save-msg').textContent = error.message || 'Terjadi kesalahan saat menyimpan.';
        el('save-msg').className = 'small mt-2 text-danger';
        renderReport(error.message || 'Terjadi kesalahan saat menyimpan.');
    } finally {
        el('btn-save').disabled = false;
        el('btn-save').textContent = 'Simpan Pengaturan Dashboard';
    }
}

async function saveProfile(event) {
    event.preventDefault();
    if (!el('profile-form')) return;

    const payload = {
        name: el('profile_name').value.trim(),
        username: el('profile_username').value.trim(),
        email: el('profile_email').value.trim(),
        current_password: el('profile_current_password').value,
        new_password: el('profile_new_password').value,
        new_password_confirmation: el('profile_new_password_confirmation').value,
    };

    const btn = el('btn-profile-save');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        const response = await fetch(ROUTES.profileUpdate, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const json = await response.json();
        if (!response.ok || !json.success) {
            throw new Error(json.message || 'Gagal menyimpan profile.');
        }

        setProfileMessage(json.message || 'Profile berhasil diperbarui.', 'success');
        el('profile_current_password').value = '';
        el('profile_new_password').value = '';
        el('profile_new_password_confirmation').value = '';
    } catch (error) {
        setProfileMessage(error.message || 'Terjadi kesalahan saat menyimpan profile.', 'danger');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Simpan Profile';
    }
}

async function saveAccount(event) {
    event.preventDefault();
    if (!el('account-form')) return;

    const payload = {
        account_id: el('new_account_id').value.trim(),
        pair_symbol: el('new_pair_symbol').value.trim().toUpperCase() || 'XAUUSDC',
        base_lot: el('new_base_lot').value,
    };

    if (!payload.account_id) {
        setAccountMessage('Account ID wajib diisi.', 'danger');
        return;
    }

    const btn = el('btn-account-save');
    btn.disabled = true;
    btn.textContent = 'Menambahkan...';

    try {
        const response = await fetch(ROUTES.accountStore, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(payload)
        });

        const json = await response.json();
        if (!response.ok || !json.success) {
            throw new Error(json.message || 'Gagal menambah account.');
        }

        const accountData = json.data || payload;
        const createdPair = normalizePairSymbol(accountData?.pair_symbol || payload.pair_symbol || 'XAUUSDC');
        ensureAccountPairRegistered(payload.account_id, createdPair);
        setStateByAccountPair(payload.account_id, createdPair, { ...DEFAULTS, ...accountData, pair_symbol: createdPair });
        SELECTED_PAIR_BY_ACCOUNT[payload.account_id] = createdPair;

        refreshAccountSelectOptions(payload.account_id);
        renderPairTabsForCurrentAccount();
        loadAccountForm(payload.account_id);

        setAccountMessage(json.message || 'Account berhasil ditambahkan.', 'success');
        el('new_account_id').value = '';

        const modalEl = el('account-modal');
        const modal = modalEl ? bootstrap.Modal.getInstance(modalEl) : null;
        if (modal) modal.hide();
    } catch (error) {
        setAccountMessage(error.message || 'Terjadi kesalahan saat menambah account.', 'danger');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Tambah Account';
    }
}

async function deleteAccount() {
    const accountId = (el('delete_account_id')?.value || currentAccount() || '').trim();
    if (!accountId) {
        setAccountMessage('Pilih atau isi Account ID yang ingin dihapus.', 'danger');
        return;
    }

    if (!window.confirm('Yakin ingin menghapus account MT5 ' + accountId + '?')) {
        return;
    }

    const btn = el('btn-account-delete');
    const previousLabel = btn ? btn.textContent : 'Hapus Account';
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Menghapus...';
    }

    try {
        const response = await fetch(ROUTES.accountDelete, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ account_id: accountId })
        });

        const json = await response.json();
        if (!response.ok || !json.success) {
            throw new Error(json.message || 'Gagal menghapus account.');
        }

        delete ACCOUNTS[accountId];
        Object.keys(ACCOUNTS_BY_PAIR).forEach((key) => {
            if (key.startsWith(accountId + '::')) {
                delete ACCOUNTS_BY_PAIR[key];
            }
        });
        delete ACCOUNT_PAIR_INDEX[accountId];
        delete SELECTED_PAIR_BY_ACCOUNT[accountId];

        refreshAccountSelectOptions();
        renderPairTabsForCurrentAccount();
        const nextAccount = currentAccount();
        if (nextAccount) {
            loadAccountForm(nextAccount);
        } else {
            renderMonitoring();
            renderAnalysis();
            renderReport('Belum ada account aktif. Tambahkan account MT5 baru.');
        }

        syncDeleteAccountInput();
        setAccountMessage(json.message || 'Account berhasil dihapus.', 'success');
    } catch (error) {
        setAccountMessage(error.message || 'Terjadi kesalahan saat menghapus account.', 'danger');
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.textContent = previousLabel;
        }
    }
}

async function saveManagedUser(event) {
    event.preventDefault();
    if (!IS_ADMIN || !el('users-form')) return;

    const editId = el('manage_user_id').value;
    const payload = {
        name: el('manage_name').value.trim(),
        username: el('manage_username').value.trim(),
        email: el('manage_email').value.trim(),
        role: el('manage_role').value,
        password: el('manage_password').value,
    };

    if (!editId && !payload.password) {
        setUsersMessage('Password wajib diisi saat membuat user baru.', 'danger');
        return;
    }

    const btn = el('btn-user-save');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        const response = await fetch(editId ? (ROUTES.userUpdateBase + '/' + editId) : ROUTES.userStore, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const json = await response.json();
        if (!response.ok || !json.success) {
            throw new Error(json.message || 'Gagal menyimpan user.');
        }

        const user = json.user || null;
        if (user) {
            const index = MANAGED_USERS.findIndex((item) => Number(item.id) === Number(user.id));
            if (index >= 0) {
                MANAGED_USERS[index] = { ...MANAGED_USERS[index], ...user };
            } else {
                MANAGED_USERS.push(user);
            }
            renderUsersTable();
        }

        resetManageForm();
        setUsersMessage(json.message || 'User berhasil disimpan.', 'success');
    } catch (error) {
        setUsersMessage(error.message || 'Terjadi kesalahan saat menyimpan user.', 'danger');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Simpan User';
    }
}

async function deleteManagedUser(userId) {
    if (!IS_ADMIN) return;
    const normalizedId = Number(userId);
    if (!Number.isFinite(normalizedId) || normalizedId <= 0) return;
    if (!window.confirm('Hapus user #' + normalizedId + '? Semua account MT5 user ini juga akan dihapus.')) {
        return;
    }

    const btnDelete = el('btn-user-delete');
    const previousText = btnDelete ? btnDelete.textContent : 'Hapus User';
    if (btnDelete) {
        btnDelete.disabled = true;
        btnDelete.textContent = 'Menghapus...';
    }

    try {
        const response = await fetch(ROUTES.userDeleteBase + '/' + normalizedId, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });

        const json = await response.json();
        if (!response.ok || !json.success) {
            throw new Error(json.message || 'Gagal menghapus user.');
        }

        const idx = MANAGED_USERS.findIndex((item) => Number(item.id) === normalizedId);
        if (idx >= 0) {
            MANAGED_USERS.splice(idx, 1);
        }

        renderUsersTable();
        resetManageForm();
        setUsersMessage(json.message || 'User berhasil dihapus.', 'success');
    } catch (error) {
        setUsersMessage(error.message || 'Terjadi kesalahan saat menghapus user.', 'danger');
    } finally {
        if (btnDelete) {
            btnDelete.disabled = false;
            btnDelete.textContent = previousText;
        }
    }
}

function parseNewsDate(item) {
    if (item?.event_at) {
        const parsed = new Date(item.event_at);
        if (!Number.isNaN(parsed.getTime())) return parsed;
    }

    if (!item?.event_clock || !String(item.event_clock).includes(':')) {
        return null;
    }

    const now = new Date();
    const [hours, minutes] = String(item.event_clock).split(':').map((part) => Number(part));
    if (!Number.isFinite(hours) || !Number.isFinite(minutes)) {
        return null;
    }

    const candidate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), hours, minutes, 0, 0);
    if (candidate.getTime() < now.getTime()) {
        candidate.setDate(candidate.getDate() + 1);
    }
    return candidate;
}

let lastNewsCloseTime = 0;

async function checkNewsApproaching(newsItem, distanceMs) {
    if (!currentAccount()) return;
    
    const enableCloseOnNews = el('close_all_on_news')?.checked;
    if (!enableCloseOnNews) return;

    const pauseBeforeMinutes = parseInt(el('news_pause_before_minutes')?.value || 0);
    if (!pauseBeforeMinutes || pauseBeforeMinutes <= 0) return;

    const pauseWindowMs = pauseBeforeMinutes * 60 * 1000;
    const now = Date.now();

    // Only trigger if news is approaching (within pause window) and not already triggered recently
    if (distanceMs > 0 && distanceMs <= pauseWindowMs && (now - lastNewsCloseTime) > 60000) {
        lastNewsCloseTime = now;
        
        // Send close all positions command
        try {
            const response = await fetch('/api/v1/ea/close-all-positions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    account_id: currentAccount(),
                    reason: 'News event approaching: ' + (newsItem?.title || 'Economic Event')
                })
            });

            const json = await response.json();
            if (response.ok && json.success) {
                console.log('✅ Close all positions triggered:', json.message);
                renderMonitoring();
            }
        } catch (error) {
            console.error('❌ Error closing positions:', error);
        }
    }
}

function getNextNewsItem() {
    const now = Date.now();
    const normalized = NEWS_ITEMS
        .map((item) => ({ ...item, eventDate: parseNewsDate(item) }))
        .filter((item) => item.eventDate && item.eventDate.getTime() >= now)
        .sort((a, b) => a.eventDate.getTime() - b.eventDate.getTime());

    return normalized[0] || null;
}

function pad(num) {
    return String(num).padStart(2, '0');
}

function formatCountdown(ms) {
    if (ms <= 0) return '00:00:00';
    const totalSeconds = Math.floor(ms / 1000);
    const hh = Math.floor(totalSeconds / 3600);
    const mm = Math.floor((totalSeconds % 3600) / 60);
    const ss = totalSeconds % 60;
    return pad(hh) + ':' + pad(mm) + ':' + pad(ss);
}

function formatLicenseCountdown(seconds) {
    const total = Math.max(0, Math.trunc(Number(seconds) || 0));
    const days = Math.floor(total / 86400);
    const hours = Math.floor((total % 86400) / 3600);
    const minutes = Math.floor((total % 3600) / 60);
    const secs = total % 60;
    return `${days}d ${pad(hours)}h ${pad(minutes)}m ${pad(secs)}s`;
}

function estimateLiveLicenseSeconds() {
    if (!LIVE_LICENSE_COUNTDOWN.active || LIVE_LICENSE_COUNTDOWN.perpetual) {
        return 0;
    }

    const elapsed = Math.max(0, Math.floor((Date.now() - Number(LIVE_LICENSE_COUNTDOWN.anchorMs || 0)) / 1000));
    return Math.max(0, Number(LIVE_LICENSE_COUNTDOWN.anchorSeconds || 0) - elapsed);
}

function syncLiveLicenseCountdown(accountId, state, licenseActive) {
    if (!accountId || !licenseActive) {
        LIVE_LICENSE_COUNTDOWN.accountId = '';
        LIVE_LICENSE_COUNTDOWN.active = false;
        LIVE_LICENSE_COUNTDOWN.perpetual = false;
        LIVE_LICENSE_COUNTDOWN.anchorSeconds = 0;
        LIVE_LICENSE_COUNTDOWN.anchorMs = 0;
        LIVE_LICENSE_COUNTDOWN.lastRenderedSeconds = null;
        return;
    }

    LIVE_LICENSE_COUNTDOWN.accountId = String(accountId);
    LIVE_LICENSE_COUNTDOWN.active = true;
    LIVE_LICENSE_COUNTDOWN.perpetual = Boolean(state?.license_is_perpetual);

    if (LIVE_LICENSE_COUNTDOWN.perpetual) {
        LIVE_LICENSE_COUNTDOWN.anchorSeconds = 0;
        LIVE_LICENSE_COUNTDOWN.anchorMs = Date.now();
        LIVE_LICENSE_COUNTDOWN.lastRenderedSeconds = null;
        return;
    }

    const reportedSeconds = Math.max(0, Math.trunc(Number(state?.license_remaining_seconds) || 0));
    const hasAnchor = Number(LIVE_LICENSE_COUNTDOWN.anchorMs || 0) > 0;

    if (!hasAnchor) {
        LIVE_LICENSE_COUNTDOWN.anchorSeconds = reportedSeconds;
        LIVE_LICENSE_COUNTDOWN.anchorMs = Date.now();
        LIVE_LICENSE_COUNTDOWN.lastRenderedSeconds = reportedSeconds;
        return;
    }

    const estimatedSeconds = estimateLiveLicenseSeconds();
    const drift = reportedSeconds - estimatedSeconds;
    const shouldResyncDown = drift <= -2;
    const shouldResyncUp = drift >= 3600;

    if (shouldResyncDown || shouldResyncUp) {
        LIVE_LICENSE_COUNTDOWN.anchorSeconds = reportedSeconds;
        LIVE_LICENSE_COUNTDOWN.anchorMs = Date.now();
        LIVE_LICENSE_COUNTDOWN.lastRenderedSeconds = reportedSeconds;
    }
}

function renderLiveLicenseCountdownTick() {
    const accountId = String(currentAccount() || '');
    if (accountId === '' || !LIVE_LICENSE_COUNTDOWN.active || LIVE_LICENSE_COUNTDOWN.accountId !== accountId) {
        return;
    }

    if (LIVE_LICENSE_COUNTDOWN.perpetual) {
        setSmooth('topbar-license-remaining', 'Countdown: Permanent');
        setSmooth('mon-license', 'Permanent');
        return;
    }

    let seconds = estimateLiveLicenseSeconds();
    if (Number.isFinite(LIVE_LICENSE_COUNTDOWN.lastRenderedSeconds)) {
        seconds = Math.min(seconds, Number(LIVE_LICENSE_COUNTDOWN.lastRenderedSeconds));
    }
    LIVE_LICENSE_COUNTDOWN.lastRenderedSeconds = seconds;
    const remainingText = formatLicenseCountdown(seconds);
    setSmooth('topbar-license-remaining', 'Countdown: ' + remainingText);
    setSmooth('mon-license', remainingText);
}

function renderNewsList() {
    if (!el('news-list')) return;

    if (!Array.isArray(NEWS_ITEMS) || NEWS_ITEMS.length === 0) {
        el('news-list').innerHTML = '<div class="news-item text-secondary small">Belum ada data berita ekonomi hari ini.</div>';
        setNewsSource('EMPTY');
        return;
    }

    const visibleItems = NEWS_ITEMS.slice(0, 7);

    el('news-list').innerHTML = visibleItems.map((item, idx) => {
        const actualMeta = metricMeta(item?.actual, 'actual');
        const forecastMeta = metricMeta(item?.forecast, 'forecast');
        const previousMeta = metricMeta(item?.previous, 'previous');

        const dataHtml = [
            { label: 'Actual', css: 'actual', meta: actualMeta },
            { label: 'Forecast', css: 'forecast', meta: forecastMeta },
            { label: 'Previous', css: 'previous', meta: previousMeta },
        ].map((metric) => {
            const emptyClass = metric.meta.missing ? ' is-empty' : '';
            return '<div class="news-data-item' + emptyClass + '">' +
                '<div class="news-metric-label news-metric-label-' + metric.css + '">' + metric.label + '</div>' +
                '<div class="news-metric-value">' + escapeHtml(metric.meta.value) + '</div>' +
                '</div>';
        }).join('');

        const nextClass = idx === 0 ? ' news-item-next' : '';
        return '<div class="news-item' + nextClass + ' py-2">' +
            '<div class="fw-semibold news-item-title mb-2">' + escapeHtml(item.title || 'USD Event') + '</div>' +
            '<div class="small news-item-meta mb-3">' + escapeHtml(itemDayDate(item)) + ' | ' + escapeHtml(itemClock(item)) + ' WIB</div>' +
            '<div class="d-flex flex-wrap gap-2">' + dataHtml + '</div>' +
            '</div>';
    }).join('');
}

function normalizeNewsHistoryItem(item) {
    const eventDate = parseNewsDate(item);
    return {
        title: item?.title || 'USD Event',
        impact: String(item?.impact || 'MEDIUM').toUpperCase(),
        event_at: item?.event_at || (eventDate ? eventDate.toISOString() : null),
        event_clock: item?.event_clock || (eventDate ? eventDate.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false, timeZone: 'Asia/Jakarta' }) : '--:--'),
        actual: metricValue(item?.actual),
        forecast: metricValue(item?.forecast),
        previous: metricValue(item?.previous),
        ai_analysis: item?.ai_analysis || 'Data history event USD yang sudah lewat.',
        ai_verdict: item?.ai_verdict || 'GOLD NEUTRAL',
        eventDate,
    };
}

function newsHistoryKey(item) {
    return [
        String(item?.title || '').trim().toLowerCase(),
        String(item?.event_at || '').trim(),
        String(item?.event_clock || '').trim(),
        String(item?.impact || '').trim().toUpperCase(),
    ].join('|');
}

function setNewsHistoryItems(items, merge = true) {
    const normalizedIncoming = safeArray(items)
        .map((item) => normalizeNewsHistoryItem(item))
        .filter((item) => item.eventDate && !Number.isNaN(item.eventDate.getTime()));

    const normalizedExisting = merge
        ? safeArray(NEWS_HISTORY_ITEMS)
            .map((item) => normalizeNewsHistoryItem(item))
            .filter((item) => item.eventDate && !Number.isNaN(item.eventDate.getTime()))
        : [];

    const combined = normalizedIncoming.concat(normalizedExisting)
        .sort((a, b) => b.eventDate.getTime() - a.eventDate.getTime());

    const deduped = [];
    const seen = new Set();
    for (const row of combined) {
        const key = newsHistoryKey(row);
        if (!key || seen.has(key)) {
            continue;
        }
        seen.add(key);
        deduped.push(row);
        if (deduped.length >= NEWS_HISTORY_MAX_ITEMS) {
            break;
        }
    }

    NEWS_HISTORY_ITEMS = deduped;
}

function archivePassedNewsItems() {
    if (!Array.isArray(NEWS_ITEMS) || NEWS_ITEMS.length === 0) {
        return;
    }

    const now = Date.now();
    const normalized = NEWS_ITEMS
        .map((item) => ({ ...item, eventDate: parseNewsDate(item) }));

    const resolved = normalized
        .filter((item) => item.eventDate && !Number.isNaN(item.eventDate.getTime()));

    const passed = resolved.filter((item) => item.eventDate.getTime() < now);
    if (passed.length > 0) {
        setNewsHistoryItems(passed, true);
    }

    const upcomingResolved = resolved
        .filter((item) => item.eventDate.getTime() >= now)
        .sort((a, b) => a.eventDate.getTime() - b.eventDate.getTime());

    const unresolved = normalized.filter((item) => !item.eventDate || Number.isNaN(item.eventDate.getTime()));
    NEWS_ITEMS = upcomingResolved.concat(unresolved).slice(0, 7);
}

function renderNewsHistoryList() {
    if (!el('news-history-list')) return;

    const items = safeArray(NEWS_HISTORY_ITEMS);
    if (!items.length) {
        el('news-history-list').innerHTML = '<div class="small text-secondary">Belum ada riwayat news yang sudah lewat.</div>';
        return;
    }

    el('news-history-list').innerHTML = items.map((item) => {
        const impact = String(item?.impact || 'MEDIUM').toUpperCase();
        const actualMeta = metricMeta(item?.actual, 'actual');
        const forecastMeta = metricMeta(item?.forecast, 'forecast');
        const previousMeta = metricMeta(item?.previous, 'previous');
        return '<div class="news-item news-history-card py-2">'
            + '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">'
            + '<div class="fw-semibold text-body">' + escapeHtml(item.title || 'USD Event') + '</div>'
            + '<span class="news-impact-badge">' + escapeHtml(impact) + '</span>'
            + '</div>'
            + '<div class="small text-secondary mb-3">' + escapeHtml(itemDayDate(item)) + ' | ' + escapeHtml(itemClock(item)) + ' WIB</div>'
            + '<div class="d-flex flex-wrap gap-2 mb-2">'
            + '<div class="news-data-item' + (actualMeta.missing ? ' is-empty' : '') + '"><div class="news-metric-label news-metric-label-actual">Actual</div><div class="news-metric-value">' + escapeHtml(actualMeta.value) + '</div></div>'
            + '<div class="news-data-item' + (forecastMeta.missing ? ' is-empty' : '') + '"><div class="news-metric-label news-metric-label-forecast">Forecast</div><div class="news-metric-value">' + escapeHtml(forecastMeta.value) + '</div></div>'
            + '<div class="news-data-item' + (previousMeta.missing ? ' is-empty' : '') + '"><div class="news-metric-label news-metric-label-previous">Previous</div><div class="news-metric-value">' + escapeHtml(previousMeta.value) + '</div></div>'
            + '</div>'
            + '<div class="news-history-note">' + escapeHtml(item.ai_analysis || '-') + '</div>'
            + '</div>';
    }).join('');
}

function normalizeCalendarApiEvents(events) {
    if (!Array.isArray(events)) {
        return [];
    }

    return events
        .map((item) => {
            const timestamp = Number(item?.time || 0);
            if (!Number.isFinite(timestamp) || timestamp <= 0) {
                return null;
            }

            const eventDate = new Date(timestamp * 1000);
            if (Number.isNaN(eventDate.getTime())) {
                return null;
            }

            return {
                title: item?.event || item?.event_name || 'USD Event',
                impact: item?.importance || item?.impact || 'MEDIUM',
                event_at: eventDate.toISOString(),
                event_clock: eventDate.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false,
                    timeZone: 'Asia/Jakarta',
                }),
                actual: item?.actual ?? '',
                forecast: item?.forecast ?? '',
                previous: item?.previous ?? '',
                ai_analysis: item?.ai_analysis || '',
                ai_verdict: item?.ai_verdict || '',
                eventDate,
            };
        })
        .filter((item) => item && item.eventDate && item.eventDate.getTime() >= Date.now())
        .sort((a, b) => a.eventDate.getTime() - b.eventDate.getTime())
        .slice(0, 7);
}

function restoreInitialNewsItems() {
    const normalized = safeArray(INITIAL_NEWS_ITEMS)
        .map((item) => ({ ...item, eventDate: parseNewsDate(item) }))
        .filter((item) => item.eventDate && item.eventDate.getTime() >= Date.now())
        .sort((a, b) => a.eventDate.getTime() - b.eventDate.getTime())
        .slice(0, 7);

    if (normalized.length <= 0) {
        return false;
    }

    NEWS_ITEMS = normalized;
    NEWS_IS_LIVE = false;
    renderNewsList();
    setNewsSource('LIVE', String(NEWS_ITEMS.length) + ' events | rendered payload');
    return true;
}

async function fetchEconomicCalendarApiFallback() {
    try {
        const response = await fetch(ROUTES.newsCalendarApi, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
        });

        const json = await response.json();
        if (!response.ok || !json.success) {
            return false;
        }

        const normalized = normalizeCalendarApiEvents(json.events || []);
        if (normalized.length <= 0) {
            return false;
        }

        NEWS_ITEMS = normalized;
        NEWS_IS_LIVE = true;
        renderNewsList();
        setNewsSource('LIVE', String(NEWS_ITEMS.length) + ' upcoming | ' + NEWS_PROVIDER_LABEL + '-API');
        return true;
    } catch (_error) {
        return false;
    }
}

async function fetchLiveNewsFallback(force = false) {
    const now = Date.now();
    if (!force && now - NEWS_LAST_FETCH_MS < 180000) {
        return;
    }

    NEWS_LAST_FETCH_MS = now;
    try {
        const endpoint = force ? (ROUTES.newsLive + '?force=1') : ROUTES.newsLive;
        const response = await fetch(endpoint, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const json = await response.json();
        if (!response.ok || !json.success || !Array.isArray(json.data)) {
            if (restoreInitialNewsItems()) {
                return;
            }
            setNewsSource('LIVE', json?.message || 'request failed');
            return;
        }

        if (Array.isArray(json.history_recent)) {
            setNewsHistoryItems(json.history_recent, true);
            renderNewsHistoryList();
        }

        if (json.data.length > 0) {
            const normalized = json.data
                .map((item) => ({ ...item, eventDate: parseNewsDate(item) }))
                .filter((item) => item.eventDate);
            normalized.sort((a, b) => a.eventDate.getTime() - b.eventDate.getTime());
            NEWS_ITEMS = normalized.length > 0 ? normalized.slice(0, 7) : json.data.slice(0, 7);
            NEWS_IS_LIVE = true;
            renderNewsList();
            const provider = json.provider ? String(json.provider).replace('https://', '').replace('http://', '') : 'LIVE';
            const cacheTag = json.cached ? 'cached' : 'fresh';
            setNewsSource('LIVE', String(NEWS_ITEMS.length) + ' events | ' + cacheTag + ' | ' + provider);
            return;
        }

        const apiRecovered = await fetchEconomicCalendarApiFallback();
        if (apiRecovered) {
            return;
        }

        if (restoreInitialNewsItems()) {
            return;
        }

        NEWS_ITEMS = [];
        NEWS_IS_LIVE = false;
        renderNewsList();
        const provider = json.provider ? String(json.provider).replace('https://', '').replace('http://', '') : 'LIVE';
        setNewsSource('LIVE', '0 upcoming | ' + provider);
    } catch (_error) {
        const apiRecovered = await fetchEconomicCalendarApiFallback();
        if (apiRecovered) {
            return;
        }
        if (restoreInitialNewsItems()) {
            return;
        }
        setNewsSource('LIVE', 'network error');
    }
}

function updateNewsWidget() {
    archivePassedNewsItems();

    const nextItem = getNextNewsItem();
    if (!nextItem) {
        el('next-news-countdown').textContent = '00:00:00';
        el('next-news-meta').textContent = 'Belum ada jadwal event berikutnya.';
        const shouldForceRetry = (Date.now() - NEWS_LAST_FETCH_MS) > 15000;
        fetchLiveNewsFallback(shouldForceRetry);
    } else {
        const distance = nextItem.eventDate.getTime() - Date.now();
        el('next-news-countdown').textContent = formatCountdown(distance);
        el('next-news-meta').textContent = itemDayDate(nextItem) + ' | ' + itemClock(nextItem) + ' WIB | ' + (nextItem.impact || '-') + ' | ' + (nextItem.title || 'Event ekonomi');
        
        // Check if news is approaching and close_all_on_news is enabled
        checkNewsApproaching(nextItem, distance);
    }

    const verdictSource = preferredNewsInsightSource();
    const verdictLabel = String(verdictSource?.ai_verdict || '').trim();
    const verdictCopy = String(verdictSource?.ai_analysis || '').trim();
    el('ai-verdict-label').textContent = verdictLabel !== '' ? verdictLabel : 'WAITING DATA';
    el('ai-verdict-copy').textContent = !isGenericAiInsight(verdictCopy)
        ? verdictCopy
        : 'Analisa AI belum tersedia untuk event terdekat. Menunggu update model setelah data ekonomi masuk.';

    if (el('rep-widget-time')) {
        el('rep-widget-time').textContent = new Date().toLocaleTimeString('id-ID');
    }

    if (nextItem && !NEWS_IS_LIVE) {
        setNewsSource('LIVE', String(NEWS_ITEMS.length) + ' events');
    }
}

el('account_id')?.addEventListener('change', (event) => {
    const accountId = String(event.target.value || '').trim();
    saveSelectedAccount(accountId);
    const availablePairs = getConnectedPairsForAccount(accountId);
    if (availablePairs.length > 0 && !availablePairs.includes(normalizePairSymbol(SELECTED_PAIR_BY_ACCOUNT[accountId] || ''))) {
        SELECTED_PAIR_BY_ACCOUNT[accountId] = availablePairs[0];
        saveSelectedPair(accountId, availablePairs[0]);
    }
    renderPairTabsForCurrentAccount();
    loadAccountForm(event.target.value, { deferInitialRender: true });
    updateAccountPickerToggle();
    syncDeleteAccountInput();
    syncAliasModalForCurrentAccount();
    restartDashboardLiveStream();
    refreshConnectedPairsRealtime();
});
el('active_strategy')?.addEventListener('change', () => {
    if (!IS_ADMIN && el('active_strategy') && String(el('active_strategy').value || '0') !== '0') {
        el('active_strategy').value = '0';
    }
    toggleStrategyPanels();
    markDirty();
});

['grid_use_basket_tp_percent', 'grid_tp_mode', 'use_sydney_session', 'use_asia_session', 'use_europe_session', 'use_us_session'].forEach((id) => {
    el(id)?.addEventListener('change', () => {
        toggleDependentState();
        markDirty();
    });
});

['grid_mart_type', 'grid_mart_addition', 'grid_mart_multiplier'].forEach((id) => {
    el(id)?.addEventListener('input', () => {
        syncCoreLotScalingInputsFromGrid();
        toggleGridLotScalingMode();
        markDirty();
    });
    el(id)?.addEventListener('change', () => {
        syncCoreLotScalingInputsFromGrid();
        toggleGridLotScalingMode();
        markDirty();
    });
});

['mart_type', 'mart_addition', 'mart_multiplier'].forEach((id) => {
    el(id)?.addEventListener('change', () => {
        syncGridLotScalingInputsFromCore();
        toggleGridLotScalingMode();
    });
});

el('use_mirror_trap')?.addEventListener('change', () => {
    el('use_mirror_trap_mart').checked = el('use_mirror_trap').checked;
    markDirty();
});
el('use_mirror_trap_mart')?.addEventListener('change', () => {
    el('use_mirror_trap').checked = el('use_mirror_trap_mart').checked;
    markDirty();
});

FIELD_IDS.forEach((id) => {
    el(id)?.addEventListener('input', markDirty);
    el(id)?.addEventListener('change', markDirty);
    if (isCheckbox(id)) {
        el(id)?.addEventListener('change', () => {
            scheduleToggleAutoSave(id);
        });
    }
});

el('risk_acknowledged')?.addEventListener('change', async (event) => {
    const accountId = currentAccount();
    const accepted = Boolean(event?.target?.checked);
    try {
        await setRiskAcknowledged(accountId, accepted);
    } catch (_error) {
        if (event?.target) {
            event.target.checked = !accepted;
        }
        alert('Persetujuan ToS gagal disimpan. Coba lagi.');
    }
});

['ema_fast', 'ema_slow'].forEach((id) => {
    el(id)?.addEventListener('input', () => validateLogicInputs(false));
});

el('logic-preset-default')?.addEventListener('click', () => applyLogicPreset('default'));
el('logic-preset-scalper')?.addEventListener('click', () => applyLogicPreset('scalper'));
el('logic-preset-medium')?.addEventListener('click', () => applyLogicPreset('medium'));
el('logic-preset-conservative')?.addEventListener('click', () => applyLogicPreset('conservative'));

el('btn-save')?.addEventListener('click', saveSetting);
el('btn-save-logic')?.addEventListener('click', saveSetting);

el('btn-bot-toggle')?.addEventListener('click', async () => {
    const accountId = currentAccount();
    if (!accountId) return;
    const pairSymbol = currentPairSymbol();
    const state = getActiveAccountState(accountId, pairSymbol) || {};
    const licenseState = LICENSE_SNAPSHOTS[accountId] || {};
    if (LICENSE_ENFORCEMENT_ENABLED && !Boolean(licenseState.license_active)) {
        alert('Lisensi account tidak aktif. Start/Stop bot diblokir.');
        return;
    }
    const guardStatus = String(state.guard_status ?? state.live_guard_status ?? '').toUpperCase();
    const isLive = guardStatus === 'LIVE';
    const action = isLive ? 'stop' : 'start';
    const btn = el('btn-bot-toggle');
    const iconEl = el('btn-bot-icon');
    const labelEl = el('btn-bot-label');
    const origIcon = iconEl ? iconEl.textContent : '';
    const origLabel = labelEl ? labelEl.textContent : '';
    btn.disabled = true;
    if (iconEl) iconEl.textContent = '...';
    if (labelEl) labelEl.textContent = action === 'start' ? 'Starting...' : 'Stopping...';
    try {
        const res = await fetch(ROUTES.botToggle, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ account_id: accountId, pair_symbol: pairSymbol, action }),
        });
        const json = await res.json();
        if (json.success) {
            const runtimeReset = action === 'start'
                ? {
                    current_layers: 0,
                    current_accumulative_lot: 0,
                    global_floating: 0,
                    live_floating_pnl: 0,
                    open_positions: [],
                    pending_orders: [],
                }
                : {};
            setStateByAccountPair(accountId, pairSymbol, {
                guard_status: json.guard_status,
                live_guard_status: json.guard_status,
                ...runtimeReset,
            });
            const msg = json.guard_status === 'LIVE' ? '✅ Bot diaktifkan.' : '⏹ Bot dihentikan.';
            const saveMsgEl = el('save-msg');
            if (saveMsgEl) {
                saveMsgEl.className = 'small mt-2 ' + (json.guard_status === 'LIVE' ? 'text-success' : 'text-danger');
                saveMsgEl.textContent = msg;
            }
            renderMonitoring();
            restartDashboardLiveStream();
        } else {
            alert('Gagal: ' + (json.message ?? 'Unknown error'));
        }
    } catch (e) {
        alert('Network error: ' + e.message);
    } finally {
        if (iconEl) iconEl.textContent = origIcon;
        if (labelEl) labelEl.textContent = origLabel;
        btn.disabled = false;
        renderMonitoring();
    }
});

async function triggerBulkBotToggle(action) {
    const startAllBtn = el('btn-bot-start-all');
    const stopAllBtn = el('btn-bot-stop-all');
    const saveMsgEl = el('save-msg');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const eligible = getEligibleBulkAccounts();

    if (!BULK_CONTROL_ENABLED) {
        if (saveMsgEl) {
            saveMsgEl.textContent = 'Start All/Stop All sedang dinonaktifkan oleh admin.';
            saveMsgEl.className = 'small mt-2 text-warning';
        }
        return;
    }

    if (!eligible.length) {
        if (saveMsgEl) {
            saveMsgEl.textContent = 'Tidak ada account whitelist yang tersedia untuk aksi bulk.';
            saveMsgEl.className = 'small mt-2 text-warning';
        }
        return;
    }

    const actionLabel = action === 'start' ? 'Start All' : 'Stop All';
    const confirmMessage = action === 'stop'
        ? ('Stop All + Force Close ALL posisi untuk ' + eligible.length + ' account whitelist?')
        : ('Start All serentak (sinkron) untuk ' + eligible.length + ' account whitelist?');
    if (!window.confirm(confirmMessage)) {
        return;
    }

    const originalStartText = startAllBtn ? startAllBtn.textContent : 'Start All';
    const originalStopText = stopAllBtn ? stopAllBtn.textContent : 'Stop All';

    if (startAllBtn) startAllBtn.disabled = true;
    if (stopAllBtn) stopAllBtn.disabled = true;
    if (action === 'start' && startAllBtn) startAllBtn.textContent = 'Starting...';
    if (action === 'stop' && stopAllBtn) stopAllBtn.textContent = 'Stopping + Closing...';

    try {
        const response = await fetch(ROUTES.botBulkToggle, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ action }),
        });
        const json = await response.json();

        if (!response.ok || !json.success) {
            throw new Error(String(json?.message || 'Aksi bulk gagal diproses.'));
        }

        const updatedAccounts = safeArray(json.updated_accounts).map((item) => String(item || '').trim()).filter(Boolean);
        const nextGuardStatus = String(json.guard_status || (action === 'start' ? 'LIVE' : 'PAUSED'));

        updatedAccounts.forEach((accountId) => {
            const pairs = getPairsForAccount(accountId);
            pairs.forEach((pairSymbol) => {
                const runtimeReset = action === 'start'
                    ? {
                        current_layers: 0,
                        current_accumulative_lot: 0,
                        global_floating: 0,
                        live_floating_pnl: 0,
                        open_positions: [],
                        pending_orders: [],
                    }
                    : {};
                setStateByAccountPair(accountId, pairSymbol, {
                    guard_status: nextGuardStatus,
                    live_guard_status: nextGuardStatus,
                    ...runtimeReset,
                });
            });
        });

        if (saveMsgEl) {
            const reloadQueuedCount = safeArray(json.reload_queued_accounts).length;
            const syncCount = safeArray(json.start_sync_accounts).length;
            const syncAt = json.start_sync_at ? (' | Sync at: ' + formatTime(json.start_sync_at)) : '';
            const extraText = action === 'stop'
                ? (' | Graceful stop queued: ' + reloadQueuedCount + ' account')
                : (' | Sync queued: ' + syncCount + ' account' + syncAt);
            saveMsgEl.textContent = String(json.message || (actionLabel + ' berhasil.')) + ' (' + updatedAccounts.length + ' account)' + extraText;
            saveMsgEl.className = 'small mt-2 text-success';
        }

        renderMonitoring();
        restartDashboardLiveStream();
    } catch (error) {
        if (saveMsgEl) {
            saveMsgEl.textContent = 'Aksi bulk gagal: ' + String(error?.message || 'unknown error');
            saveMsgEl.className = 'small mt-2 text-danger';
        }
    } finally {
        if (startAllBtn) {
            startAllBtn.disabled = false;
            startAllBtn.textContent = originalStartText;
        }
        if (stopAllBtn) {
            stopAllBtn.disabled = false;
            stopAllBtn.textContent = originalStopText;
        }
        updateBulkControlUi();
    }
}

el('btn-bot-start-all')?.addEventListener('click', () => {
    triggerBulkBotToggle('start');
});

el('btn-bot-stop-all')?.addEventListener('click', () => {
    triggerBulkBotToggle('stop');
});

el('btn-close-all-positions')?.addEventListener('click', () => {
    triggerCloseAllPositions();
});

el('mon-open-positions-body')?.addEventListener('click', (event) => {
    const target = event.target instanceof HTMLElement
        ? event.target.closest('.mon-close-position-btn[data-close-ticket]')
        : null;
    if (!(target instanceof HTMLElement)) return;
    const ticket = String(target.getAttribute('data-close-ticket') || '').trim();
    if (!ticket) return;
    event.preventDefault();
    triggerCloseSinglePosition(ticket);
});

el('bulk-whitelist-manage-btn')?.addEventListener('click', () => {
    const modalEl = ensureModalAttachedToBody('bulkWhitelistModal');
    if (!modalEl) return;
    forceResetModalArtifacts();
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl, {
        backdrop: true,
        keyboard: true,
    });
    modal.show();
});

el('bulk-account-add-btn')?.addEventListener('click', () => {
    const inputEl = el('bulk-account-input');
    const selectEl = el('bulk-account-select');
    const typed = normalizeAccountId(inputEl?.value || '');
    const selected = normalizeAccountId(selectEl?.value || '');
    const value = typed || selected;
    addAccountToWhitelist(value);
    if (inputEl) inputEl.value = '';
    if (selectEl) selectEl.value = '';
});

el('bulk-account-input')?.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter') return;
    event.preventDefault();
    const inputEl = el('bulk-account-input');
    addAccountToWhitelist(inputEl?.value || '');
    if (inputEl) inputEl.value = '';
});

el('bulk-account-clear-btn')?.addEventListener('click', () => {
    BULK_CONTROL_WHITELIST = [];
    renderBulkWhitelistList();
    updateBulkControlUi();
    setBulkModalMessage('Whitelist dibersihkan. Menyimpan otomatis...', 'warning');
    scheduleBulkWhitelistAutosave('clear');
});

el('bulk-enabled-input')?.addEventListener('change', () => {
    updateBulkControlUi();
    updateBulkSettingsForm('Perubahan switch terdeteksi. Menyimpan otomatis...', 'secondary');
    scheduleBulkWhitelistAutosave('toggle');
});

el('bulk-whitelist-list')?.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    const accountId = target.getAttribute('data-bulk-remove');
    if (!accountId) return;
    removeAccountFromWhitelist(accountId);
});

el('bulkWhitelistModal')?.addEventListener('shown.bs.modal', () => {
    renderBulkWhitelistList();
    setBulkModalMessage('Kelola daftar account. Perubahan disimpan otomatis.', 'secondary');
    el('bulk-account-input')?.focus();
});

el('bulkWhitelistModal')?.addEventListener('hide.bs.modal', (event) => {
    blurFocusedDescendant(event.currentTarget);
});

el('bulkWhitelistModal')?.addEventListener('hidden.bs.modal', () => {
    forceResetModalArtifacts();
    el('bulk-whitelist-manage-btn')?.focus();
});

document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        stopDashboardActiveSync();
        stopDashboardFallbackPolling();
        return;
    }
    startDashboardActiveSync();
    const now = Date.now();
    if ((now - Number(DASHBOARD_LAST_MONITORING_SYNC_AT || 0)) > 1800) {
        refreshMonitoringOnly();
    }
    if ((now - Number(DASHBOARD_LAST_REPORT_SYNC_AT || 0)) > 3500) {
        refreshReportOnly({ source: 'visibility' });
    }
    startDashboardLiveStream();
});

el('btn-bot-reset-dd')?.addEventListener('click', async () => {
    const accountId = currentAccount();
    if (!accountId) return;
    const pairSymbol = currentPairSymbol();

    const btn = el('btn-bot-reset-dd');
    const saveMsgEl = el('save-msg');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const origText = btn ? btn.textContent : '↻ Reset DD';

    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Resetting...';
    }

    try {
        const res = await fetch(ROUTES.botResetDd, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ account_id: accountId, pair_symbol: pairSymbol }),
        });
        const json = await res.json();

        if (res.ok && json.success) {
            setStateByAccountPair(accountId, pairSymbol, { guard_status: String(json.guard_status || 'LIVE') });
            if (saveMsgEl) {
                saveMsgEl.textContent = String(json.message || 'Max Drawdown reset berhasil. Bot bisa di-start lagi.');
                saveMsgEl.className = 'small mt-2 text-success';
            }
            renderMonitoring();
            await refreshLiveTelemetry(true);
            restartDashboardLiveStream();
        } else if (saveMsgEl) {
            saveMsgEl.textContent = 'Reset DD gagal: ' + String(json?.message || 'Unknown error');
            saveMsgEl.className = 'small mt-2 text-danger';
        }
    } catch (_error) {
        if (saveMsgEl) {
            saveMsgEl.textContent = 'Network error saat reset DD.';
            saveMsgEl.className = 'small mt-2 text-danger';
        }
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.textContent = origText;
        }
    }
});

el('theme-toggle')?.addEventListener('click', () => {
    const current = document.body.getAttribute('data-theme') || 'light';
    applyTheme(current === 'dark' ? 'light' : 'dark');
    toggleWorkspaceThemeCard();
});

el('account-alias-form')?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const accountId = String(el('alias_account_id')?.value || '').trim();
    const alias = String(el('alias_account_name')?.value || '').trim();
    if (!accountId) {
        setAccountAliasMessage('Account belum dipilih.', 'danger');
        return;
    }

    const saveBtn = el('btn-alias-save');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.textContent = 'Menyimpan...';
    }

    try {
        const json = await saveAccountAliasToServer(accountId, alias);
        refreshAccountSelectOptions(accountId);
        renderBulkWhitelistList();
        updateBulkControlUi();
        setAccountAliasMessage(String(json?.message || 'Alias account berhasil disimpan.'), 'success');
    } catch (error) {
        setAccountAliasMessage(String(error?.message || 'Gagal menyimpan alias.'), 'danger');
    } finally {
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Simpan Alias';
        }
    }
});

el('btn-alias-clear')?.addEventListener('click', async () => {
    const accountId = String(el('alias_account_id')?.value || '').trim();
    if (!accountId) {
        setAccountAliasMessage('Account belum dipilih.', 'danger');
        return;
    }

    const clearBtn = el('btn-alias-clear');
    if (clearBtn) {
        clearBtn.disabled = true;
        clearBtn.textContent = 'Menghapus...';
    }

    try {
        const json = await saveAccountAliasToServer(accountId, '');
        if (el('alias_account_name')) {
            el('alias_account_name').value = '';
        }

        refreshAccountSelectOptions(accountId);
        renderBulkWhitelistList();
        updateBulkControlUi();
        setAccountAliasMessage(String(json?.message || 'Alias account dihapus.'), 'warning');
    } catch (error) {
        setAccountAliasMessage(String(error?.message || 'Gagal menghapus alias.'), 'danger');
    } finally {
        if (clearBtn) {
            clearBtn.disabled = false;
            clearBtn.textContent = 'Hapus Alias';
        }
    }
});

el('account-alias-modal')?.addEventListener('shown.bs.modal', () => {
    syncAliasModalForCurrentAccount();
    el('alias_account_name')?.focus();
});

el('alias_account_id')?.addEventListener('change', (event) => {
    const accountId = String(event.target?.value || '').trim();
    saveSelectedAliasModalAccount(accountId);
    if (el('alias_account_name')) {
        el('alias_account_name').value = accountAliasById(accountId);
    }
    setAccountAliasMessage(accountId ? 'Atur alias untuk account terpilih.' : 'Belum ada account yang bisa diberi alias.', 'secondary');
});

el('account-search')?.addEventListener('input', (event) => {
    ACCOUNT_SEARCH_QUERY = String(event.target?.value || '').trim();
    renderAccountPickerOptions();
});

el('pair-tabs-settings')?.addEventListener('click', (event) => {
    const target = event.target instanceof HTMLElement ? event.target.closest('.pair-tab-btn[data-pair-symbol]') : null;
    if (!(target instanceof HTMLElement)) return;

    const accountId = String(currentAccount() || '').trim();
    if (!accountId) return;

    const pairSymbol = normalizePairSymbol(target.getAttribute('data-pair-symbol') || 'XAUUSD');
    if (!getConnectedPairsForAccount(accountId).includes(pairSymbol)) return;

    SELECTED_PAIR_BY_ACCOUNT[accountId] = pairSymbol;
    saveSelectedPair(accountId, pairSymbol);
    ACCOUNTS[accountId] = getActiveAccountState(accountId, pairSymbol);
    renderPairTabsForCurrentAccount();
    loadAccountForm(accountId);
    restartDashboardLiveStream();
});

el('account-picker-options')?.addEventListener('click', (event) => {
    const target = event.target instanceof HTMLElement
        ? event.target.closest('.account-picker-item[data-account-id]')
        : null;
    if (!(target instanceof HTMLElement)) return;
    const accountId = String(target.getAttribute('data-account-id') || '').trim();
    if (!accountId) return;

    const select = el('account_id');
    if (!select) return;
    select.value = accountId;
    select.dispatchEvent(new Event('change', { bubbles: true }));

    const toggle = el('account-picker-toggle');
    if (toggle) {
        const dropdown = bootstrap.Dropdown.getOrCreateInstance(toggle);
        dropdown.hide();
    }
});

el('account-picker-toggle')?.addEventListener('show.bs.dropdown', () => {
    renderAccountPickerOptions();
    setTimeout(() => {
        el('account-search')?.focus();
    }, 0);
});

el('btn-open-account-modal')?.addEventListener('click', () => {
    const pickerToggle = el('account-picker-toggle');
    if (pickerToggle) {
        const pickerDropdown = bootstrap.Dropdown.getOrCreateInstance(pickerToggle);
        pickerDropdown.hide();
    }

    const modalEl = el('account-modal');
    if (!modalEl) return;
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
});

el('profile-form')?.addEventListener('submit', saveProfile);
el('account-form')?.addEventListener('submit', saveAccount);
el('btn-account-delete')?.addEventListener('click', deleteAccount);

el('account-modal')?.addEventListener('show.bs.modal', () => {
    syncDeleteAccountInput();
    setAccountMessage(defaultAccountMessage(), 'secondary');
});

el('users-form')?.addEventListener('submit', saveManagedUser);
el('btn-user-reset')?.addEventListener('click', () => {
    resetManageForm();
    setUsersMessage('Form direset ke mode create.', 'secondary');
});
el('btn-user-delete')?.addEventListener('click', async () => {
    const userId = Number(el('manage_user_id')?.value || 0);
    if (!userId) {
        setUsersMessage('Pilih user yang ingin dihapus dari tabel.', 'warning');
        return;
    }
    await deleteManagedUser(userId);
});

el('btn-refresh-news')?.addEventListener('click', () => {
    fetchLiveNewsFallback(true);
});

el('btn-news-history')?.addEventListener('click', () => {
    renderNewsHistoryList();
});

if (el('newsHistoryModalLabel')) {
    el('newsHistoryModalLabel').textContent = 'Riwayat News Terakhir (' + NEWS_PROVIDER_LABEL + ')';
}

el('rep-refresh-btn')?.addEventListener('click', () => {
    refreshLiveTelemetry(true);
});

el('rep-reset-wr-btn')?.addEventListener('click', async () => {
    const accountId = currentAccount();
    if (!accountId) return;
    const pairSymbol = currentPairSymbol();

    if (!window.confirm('Hard reset profit untuk account ' + accountId + '? Baseline profit akan direset, history trade tetap disimpan.')) {
        return;
    }

    const btn = el('rep-reset-wr-btn');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const originalText = btn ? btn.textContent : 'Hard Reset Profit';

    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Hard Reset...';
    }

    try {
        const res = await fetch(ROUTES.reportsResetWr, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ account_id: accountId, pair_symbol: pairSymbol }),
        });
        const json = await res.json();

        if (res.ok && json.success) {
            setStateByAccountPair(accountId, pairSymbol, {
                history: [],
                wins: 0,
                losses: 0,
                win_rate_percent: 0,
                wr_reset_at: json.reset_at || new Date().toISOString(),
                realized_profit: 0,
                daily_profit: 0,
                weekly_profit: 0,
                monthly_profit: 0,
                report_daily_profit: 0,
                report_weekly_profit: 0,
                report_monthly_profit: 0,
                report_realized_profit: 0,
            });
            REPORTS_STATE.page = 1;
            REPORTS_STATE.pendingPage = 1;
            REPORTS_STATE.pendingPerPage = null;
            REPORTS_STATE.lastSuccessfulData = [];
            renderReport(el('save-msg')?.textContent || '');
            renderMonitoring();
            await refreshReportOnly({ priority: true, source: 'manual' });
            restartDashboardLiveStream();
        }
    } catch (_error) {
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    }
});

el('rep-history-limit')?.addEventListener('change', () => {
    REPORTS_STATE.pendingPerPage = Math.max(5, Number(el('rep-history-limit')?.value || REPORTS_STATE.perPage || 10));
    REPORTS_STATE.page = 1;
    REPORTS_STATE.pendingPage = 1;
    refreshReportOnly({ priority: true, source: 'manual' });
    restartDashboardLiveStream();
});

el('rep-history-period')?.addEventListener('change', () => {
    REPORTS_STATE.period = String(el('rep-history-period')?.value || 'all');
    REPORTS_STATE.page = 1;
    REPORTS_STATE.pendingPage = 1;
    refreshReportOnly({ priority: true, source: 'manual' });
    restartDashboardLiveStream();
});

el('rep-history-prev')?.addEventListener('click', () => {
    if (REPORTS_STATE.page <= 1) return;
    REPORTS_STATE.page -= 1;
    REPORTS_STATE.pendingPage = REPORTS_STATE.page;
    refreshReportOnly({ priority: true, source: 'manual' });
    restartDashboardLiveStream();
});

el('rep-history-next')?.addEventListener('click', () => {
    if (REPORTS_STATE.page >= REPORTS_STATE.lastPage) return;
    REPORTS_STATE.page += 1;
    REPORTS_STATE.pendingPage = REPORTS_STATE.page;
    refreshReportOnly({ priority: true, source: 'manual' });
    restartDashboardLiveStream();
});

el('users-search')?.addEventListener('input', (event) => {
    USERS_STATE.query = String(event.target.value || '');
    USERS_STATE.page = 1;
    renderUsersTable();
});

el('users-prev')?.addEventListener('click', () => {
    USERS_STATE.page = Math.max(1, USERS_STATE.page - 1);
    renderUsersTable();
});

el('users-next')?.addEventListener('click', () => {
    USERS_STATE.page += 1;
    renderUsersTable();
});

document.querySelectorAll('#workspace-tabs [data-workspace-tab]').forEach((button) => {
    button.addEventListener('click', () => {
        switchWorkspaceTab(button.getAttribute('data-workspace-tab') || 'settings');
    });
});

el('users-tbody')?.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement)) return;
    const deleteUserId = target.getAttribute('data-delete-user');
    if (deleteUserId) {
        deleteManagedUser(deleteUserId);
        return;
    }
    const userId = target.getAttribute('data-edit-user');
    if (!userId) return;
    fillManageForm(userId);
});

hydrateAccountPairState();
restoreSelectedPairMap();
neutralizeStaleBootstrapRuntimeState();
const initialSavedAccountId = loadSelectedAccount();
if (initialSavedAccountId) {
    const accountSelect = el('account_id');
    if (accountSelect) {
        const optionExists = safeArray(Array.from(accountSelect.options || [])).some((option) => String(option?.value || '').trim() === initialSavedAccountId);
        if (optionExists) {
            accountSelect.value = initialSavedAccountId;
        }
    }
}
if (el('account_id')?.value) {
    renderPairTabsForCurrentAccount();
    loadAccountForm(el('account_id').value, { deferInitialRender: true });
}
syncDeleteAccountInput();

if (el('profile_name')) {
    el('profile_name').value = CURRENT_USER.name || '';
    el('profile_username').value = CURRENT_USER.username || '';
    el('profile_email').value = CURRENT_USER.email || '';
}

initTheme();
initInlineSaveButtons();
applyLogicRoleLock();
ensureModalAttachedToBody('bulkWhitelistModal');
ensureModalAttachedToBody('account-alias-modal');
ensureModalAttachedToBody('account-modal');
ensureModalAttachedToBody('profile-modal');
ensureModalAttachedToBody('users-modal');
refreshAccountSelectOptions(currentAccount());
renderPairTabsForCurrentAccount();
switchWorkspaceTab(getInitialWorkspaceTab());
renderUsersTable();
renderMonitoring();
renderAnalysis();
renderReport(el('save-msg')?.textContent || '');
syncRiskAckCheckbox();
renderNewsList();
fetchLiveNewsFallback();
syncAliasModalForCurrentAccount();
loadAccountAliasesFromServer();
if (IS_ADMIN) {
    loadBulkWhitelistSettings();
}
startDashboardLiveStream();
startDashboardActiveSync();
refreshMonitoringOnly();
refreshReportOnly({ source: 'bootstrap' });
refreshConnectedPairsRealtime();

const BILLING_FLOAT_CHAT = {
    isOpen: false,
    messages: [],
    unreadCount: 0,
    pendingCount: 0,
    adminThreads: [],
    selectedUserId: 0,
    pendingBillings: [],
    adminFilterQuery: '',
    threadClearedManually: false,
    shouldForceScrollBottom: false,
    timer: null,
};

function clearBillingFloatAdminSelection(statusMessage = 'Thread ditutup. Pilih user untuk membuka chat.') {
    BILLING_FLOAT_CHAT.selectedUserId = 0;
    BILLING_FLOAT_CHAT.messages = [];
    BILLING_FLOAT_CHAT.pendingBillings = [];
    BILLING_FLOAT_CHAT.threadClearedManually = true;
    renderBillingFloatAdminNotifications();
    const statusEl = el('billing-float-chat-status');
    if (statusEl) {
        statusEl.textContent = statusMessage;
    }
}

function escapeBillingFloatChatHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function buildBillingAdminUserInitials(userName, userEmail, userId) {
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

function buildBillingAdminAvatarUrl(thread) {
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

    const seed = encodeURIComponent(String(thread?.user_email || thread?.user_name || thread?.user_id || 'user'));
    return 'https://api.dicebear.com/9.x/personas/svg?seed=' + seed;
}

function buildBillingAdminFallbackAvatarUrl(thread, initials) {
    const seed = String(thread?.user_email || thread?.user_name || thread?.user_id || 'user');
    let hash = 0;
    for (let i = 0; i < seed.length; i += 1) {
        hash = ((hash << 5) - hash + seed.charCodeAt(i)) | 0;
    }

    const hueA = Math.abs(hash) % 360;
    const hueB = (hueA + 36) % 360;
    const safeInitials = escapeBillingFloatChatHtml(initials || 'U');
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

function bindBillingAdminAvatarFallbacks(scope) {
    const root = scope instanceof HTMLElement ? scope : document;
    root.querySelectorAll('img[data-avatar-fallback]').forEach((imageEl) => {
        if (!(imageEl instanceof HTMLImageElement)) return;
        if (imageEl.dataset.avatarBound === '1') return;
        imageEl.dataset.avatarBound = '1';
        imageEl.addEventListener('error', () => {
            const fallback = String(imageEl.getAttribute('data-avatar-fallback') || '');
            if (fallback !== '' && imageEl.src !== fallback) {
                imageEl.src = fallback;
            }
        });
    });
}

function renderBillingFloatChatMessages(forceScrollBottom = false) {
    const target = el('billing-float-chat-messages');
    if (!target) return;

    const previousBottomDistance = Math.max(0, target.scrollHeight - target.scrollTop - target.clientHeight);
    const shouldScrollToBottom = forceScrollBottom
        || BILLING_FLOAT_CHAT.shouldForceScrollBottom
        || previousBottomDistance <= 72;
    BILLING_FLOAT_CHAT.shouldForceScrollBottom = false;

    if (!Array.isArray(BILLING_FLOAT_CHAT.messages) || BILLING_FLOAT_CHAT.messages.length === 0) {
        target.innerHTML = '<div class="billing-float-chat-empty">Belum ada pesan. Kirim chat untuk follow up billing.</div>';
        return;
    }

    target.innerHTML = BILLING_FLOAT_CHAT.messages.map((message) => {
        const isSelf = !Boolean(message.sender_is_admin);
        const text = String(message?.message ?? '').trim();
        const sender = String(message?.sender_name ?? 'User');
        const label = String(message?.created_label ?? '-');
        return '<div class="billing-float-chat-row' + (isSelf ? ' is-self' : '') + '">'
            + '<div class="billing-float-chat-bubble">'
            + '<div class="billing-float-chat-meta">' + escapeBillingFloatChatHtml(sender) + ' • ' + escapeBillingFloatChatHtml(label) + '</div>'
            + '<div class="billing-float-chat-text">' + escapeBillingFloatChatHtml(text !== '' ? text : '-') + '</div>'
            + '</div>'
            + '</div>';
    }).join('');
    if (shouldScrollToBottom) {
        target.scrollTop = target.scrollHeight;
    } else {
        target.scrollTop = Math.max(0, target.scrollHeight - target.clientHeight - previousBottomDistance);
    }
}

function renderBillingFloatChatUnreadBadge() {
    const unreadBadge = el('billing-float-chat-unread');
    const pendingBadge = el('billing-float-chat-pending');

    if (unreadBadge instanceof HTMLElement) {
        const unreadCount = Math.max(0, Number(BILLING_FLOAT_CHAT.unreadCount || 0));
        if (unreadCount <= 0 || BILLING_FLOAT_CHAT.isOpen) {
            unreadBadge.classList.add('is-hidden');
            unreadBadge.textContent = '0';
        } else {
            unreadBadge.classList.remove('is-hidden');
            unreadBadge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
        }
    }

    if (pendingBadge instanceof HTMLElement) {
        const pendingCount = Math.max(0, Number(BILLING_FLOAT_CHAT.pendingCount || 0));
        if (!IS_ADMIN || pendingCount <= 0 || BILLING_FLOAT_CHAT.isOpen) {
            pendingBadge.classList.add('is-hidden');
            pendingBadge.textContent = '0';
        } else {
            pendingBadge.classList.remove('is-hidden');
            pendingBadge.textContent = pendingCount > 99 ? '99+' : String(pendingCount);
        }
    }
}

function renderBillingFloatAdminNotifications() {
    const target = el('billing-float-chat-messages');
    const userListEl = el('billing-admin-user-list');
    const pendingListEl = el('billing-admin-pending-list');
    const titleEl = el('billing-admin-thread-title');
    const subtitleEl = el('billing-admin-thread-subtitle');
    const sendBtn = el('billing-float-chat-send');
    const inputEl = el('billing-float-chat-input');
    const clearThreadBtn = el('billing-admin-clear-thread');
    const statusEl = el('billing-float-chat-status');
    if (!target) return;

    const unreadTotal = Math.max(0, Number(BILLING_FLOAT_CHAT.unreadCount || 0));
    const pendingTotal = Math.max(0, Number(BILLING_FLOAT_CHAT.pendingCount || 0));

    if (statusEl) {
        statusEl.textContent = 'Unread chat: ' + String(unreadTotal) + ' • Pending billing: ' + String(pendingTotal);
    }

    const allThreads = Array.isArray(BILLING_FLOAT_CHAT.adminThreads) ? BILLING_FLOAT_CHAT.adminThreads : [];
    const filter = String(BILLING_FLOAT_CHAT.adminFilterQuery || '').trim().toLowerCase();
    const filteredThreads = filter === ''
        ? allThreads
        : allThreads.filter((thread) => {
            const haystack = [thread.user_name, thread.user_email, thread.latest_message]
                .map((item) => String(item || '').toLowerCase())
                .join(' ');
            return haystack.includes(filter);
        });

    if (BILLING_FLOAT_CHAT.selectedUserId <= 0 && filteredThreads.length > 0 && !BILLING_FLOAT_CHAT.threadClearedManually) {
        BILLING_FLOAT_CHAT.selectedUserId = Number(filteredThreads[0].user_id || 0);
    }

    if (userListEl) {
        if (filteredThreads.length === 0) {
            userListEl.innerHTML = '<div class="billing-float-chat-empty">Tidak ada user yang cocok.</div>';
        } else {
            userListEl.innerHTML = filteredThreads.map((thread) => {
                const userId = Number(thread.user_id || 0);
                const isActive = userId === Number(BILLING_FLOAT_CHAT.selectedUserId || 0);
                const initials = buildBillingAdminUserInitials(thread.user_name, thread.user_email, userId);
                const avatarUrl = buildBillingAdminAvatarUrl(thread);
                const fallbackAvatar = buildBillingAdminFallbackAvatarUrl(thread, initials);
                const label = [thread.user_name, thread.user_email].filter(Boolean).join(' • ');
                return '<button type="button" class="billing-admin-user-item' + (isActive ? ' is-active' : '') + '" data-admin-user-id="' + String(userId) + '">'
                    + '<span class="billing-admin-user-avatar" title="' + escapeBillingFloatChatHtml(label || ('User #' + String(userId))) + '">'
                    + '<img src="' + escapeBillingFloatChatHtml(avatarUrl !== '' ? avatarUrl : fallbackAvatar) + '" data-avatar-fallback="' + escapeBillingFloatChatHtml(fallbackAvatar) + '" alt="' + escapeBillingFloatChatHtml(initials) + '" loading="lazy" referrerpolicy="no-referrer">'
                    + '</span>'
                    + '</button>';
            }).join('');
            bindBillingAdminAvatarFallbacks(userListEl);
        }
    }

    const activeThread = allThreads.find((thread) => Number(thread.user_id || 0) === Number(BILLING_FLOAT_CHAT.selectedUserId || 0));
    if (titleEl) titleEl.textContent = activeThread ? String(activeThread.user_name || 'User') : 'Pilih user';
    if (subtitleEl) subtitleEl.textContent = activeThread
        ? (String(activeThread.user_email || '-') + ' • Last update ' + String(activeThread.latest_label || '-'))
        : 'Thread chat user muncul di sini.';
    if (sendBtn instanceof HTMLButtonElement) {
        sendBtn.disabled = !activeThread;
    }
    if (clearThreadBtn instanceof HTMLButtonElement) {
        clearThreadBtn.disabled = !activeThread;
    }
    if (inputEl instanceof HTMLTextAreaElement) {
        inputEl.disabled = !activeThread;
        inputEl.placeholder = activeThread ? 'Tulis balasan admin...' : 'Pilih user dulu untuk mulai balas chat...';
    }

    if (!activeThread) {
        target.innerHTML = '<div class="billing-float-chat-empty">Belum ada chat aktif. Pilih user di kiri untuk membuka thread.</div>';
        if (pendingListEl) {
            pendingListEl.classList.add('is-empty');
            pendingListEl.innerHTML = '<div class="small text-secondary">Belum ada pending billing untuk user terpilih.</div>';
        }
        return;
    }

    renderBillingFloatChatMessages();

    if (pendingListEl) {
        if (!Array.isArray(BILLING_FLOAT_CHAT.pendingBillings) || BILLING_FLOAT_CHAT.pendingBillings.length === 0) {
            pendingListEl.classList.add('is-empty');
            pendingListEl.innerHTML = '<div class="small text-secondary">Tidak ada pending billing untuk user ini.</div>';
        } else {
            pendingListEl.classList.remove('is-empty');
            pendingListEl.innerHTML = BILLING_FLOAT_CHAT.pendingBillings.map((item) => {
                const amount = Number(item.requested_amount || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                return '<div class="billing-admin-pending-item">'
                    + '<div class="fw-semibold">Account ' + escapeBillingFloatChatHtml(item.account_id) + ' • ' + escapeBillingFloatChatHtml(String(item.requested_plan || '').toUpperCase()) + '</div>'
                    + '<div class="small text-secondary">' + escapeBillingFloatChatHtml(String(item.requested_months || 0)) + ' bulan • Rp ' + escapeBillingFloatChatHtml(amount) + ' • ' + escapeBillingFloatChatHtml(item.created_label || '-') + '</div>'
                    + '<div class="billing-admin-pending-actions">'
                    + '<button type="button" class="billing-admin-action-btn is-approve" data-billing-id="' + escapeBillingFloatChatHtml(item.id) + '" data-billing-decision="approve" title="Approve billing" data-bs-toggle="tooltip" data-bs-placement="top">'
                    + '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M5 12.5 9.2 17 19 7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                    + '</button>'
                    + '<button type="button" class="billing-admin-action-btn is-reject" data-billing-id="' + escapeBillingFloatChatHtml(item.id) + '" data-billing-decision="reject" title="Reject billing" data-bs-toggle="tooltip" data-bs-placement="top">'
                    + '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>'
                    + '</button>'
                    + '</div>'
                    + '</div>';
            }).join('');
            initBillingAdminActionTooltips();
        }
    }
}

function initBillingAdminActionTooltips() {
    if (typeof bootstrap === 'undefined' || !bootstrap?.Tooltip) return;
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        const existing = bootstrap.Tooltip.getInstance(element);
        if (existing) {
            existing.dispose();
        }
        bootstrap.Tooltip.getOrCreateInstance(element, { trigger: 'hover focus' });
    });
}

async function loadBillingFloatChatThread() {
    const statusEl = el('billing-float-chat-status');
    try {
        const response = await fetch(ROUTES.billingChatThread, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store',
        });
        if (!response.ok) {
            if (statusEl) statusEl.textContent = 'Gagal memuat chat (' + String(response.status) + ').';
            return;
        }

        const payload = await response.json();
        BILLING_FLOAT_CHAT.messages = Array.isArray(payload.messages) ? payload.messages : [];
        BILLING_FLOAT_CHAT.unreadCount = 0;
        BILLING_FLOAT_CHAT.shouldForceScrollBottom = true;
        renderBillingFloatChatMessages();
        renderBillingFloatChatUnreadBadge();
        if (statusEl) statusEl.textContent = 'Terhubung. Pesan baru tampil otomatis.';
    } catch (_error) {
        if (statusEl) statusEl.textContent = 'Koneksi chat terputus. Coba lagi...';
    }
}

async function loadBillingFloatChatUnreadCount() {
    if (IS_ADMIN) {
        await loadBillingFloatAdminNotifications();
        return;
    }

    if (BILLING_FLOAT_CHAT.isOpen) {
        BILLING_FLOAT_CHAT.unreadCount = 0;
        renderBillingFloatChatUnreadBadge();
        return;
    }

    try {
        const response = await fetch(ROUTES.billingChatUnread, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store',
        });
        if (!response.ok) return;

        const payload = await response.json();
        BILLING_FLOAT_CHAT.unreadCount = Math.max(0, Number(payload.unread_count || 0));
        renderBillingFloatChatUnreadBadge();
    } catch (_error) {
    }
}

async function loadBillingFloatAdminNotifications() {
    try {
        const response = await fetch(ROUTES.billingAdminThreads, {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store',
        });
        if (!response.ok) return;

        const payload = await response.json();
        BILLING_FLOAT_CHAT.adminThreads = Array.isArray(payload.threads) ? payload.threads : [];
        if (BILLING_FLOAT_CHAT.selectedUserId > 0) {
            const selectedStillExists = BILLING_FLOAT_CHAT.adminThreads.some((thread) => Number(thread.user_id || 0) === Number(BILLING_FLOAT_CHAT.selectedUserId || 0));
            if (!selectedStillExists) {
                BILLING_FLOAT_CHAT.selectedUserId = 0;
                BILLING_FLOAT_CHAT.messages = [];
                BILLING_FLOAT_CHAT.pendingBillings = [];
                BILLING_FLOAT_CHAT.threadClearedManually = false;
            }
        }
        if (BILLING_FLOAT_CHAT.selectedUserId <= 0 && BILLING_FLOAT_CHAT.adminThreads.length > 0 && !BILLING_FLOAT_CHAT.threadClearedManually) {
            BILLING_FLOAT_CHAT.selectedUserId = Number(BILLING_FLOAT_CHAT.adminThreads[0].user_id || 0);
        }
        BILLING_FLOAT_CHAT.unreadCount = BILLING_FLOAT_CHAT.adminThreads.reduce((sum, thread) => sum + Math.max(0, Number(thread.unread_count || 0)), 0);
        BILLING_FLOAT_CHAT.pendingCount = BILLING_FLOAT_CHAT.adminThreads.reduce((sum, thread) => sum + Math.max(0, Number(thread.pending_billing_count || 0)), 0);
        renderBillingFloatChatUnreadBadge();

        if (BILLING_FLOAT_CHAT.isOpen) {
            renderBillingFloatAdminNotifications();
        }
    } catch (_error) {
    }
}

async function loadBillingFloatAdminThread(userId, options = {}) {
    const resolvedUserId = Math.max(0, Number(userId || 0));
    if (!resolvedUserId) return;

    const statusEl = el('billing-float-chat-status');
    if (!options.silent && statusEl) {
        statusEl.textContent = 'Memuat thread user...';
    }

    try {
        const response = await fetch(ROUTES.billingChatThread + '?user_id=' + encodeURIComponent(String(resolvedUserId)), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store',
        });
        if (!response.ok) {
            if (statusEl) statusEl.textContent = 'Gagal memuat thread (' + String(response.status) + ').';
            return;
        }

        const payload = await response.json();
        BILLING_FLOAT_CHAT.selectedUserId = Number(payload.thread_user_id || resolvedUserId);
        BILLING_FLOAT_CHAT.messages = Array.isArray(payload.messages) ? payload.messages : [];
        BILLING_FLOAT_CHAT.pendingBillings = Array.isArray(payload.pending_billings) ? payload.pending_billings : [];
        BILLING_FLOAT_CHAT.threadClearedManually = false;
        BILLING_FLOAT_CHAT.shouldForceScrollBottom = true;
        if (statusEl) statusEl.textContent = 'Thread user aktif.';
        renderBillingFloatAdminNotifications();
    } catch (_error) {
        if (statusEl) statusEl.textContent = 'Koneksi thread terputus. Coba lagi...';
    }
}

async function processBillingFloatAdminDecision(billingId, decision) {
    const resolvedBillingId = Math.max(0, Number(billingId || 0));
    const resolvedDecision = String(decision || '').toLowerCase();
    if (!resolvedBillingId || !['approve', 'reject'].includes(resolvedDecision)) return;

    const statusEl = el('billing-float-chat-status');
    if (statusEl) statusEl.textContent = resolvedDecision === 'approve' ? 'Memproses approve...' : 'Memproses reject...';

    try {
        const response = await fetch(ROUTES.billingAdminDecisionBase + '/' + String(resolvedBillingId) + '/decision-json', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ decision: resolvedDecision, user_id: BILLING_FLOAT_CHAT.selectedUserId || 0 }),
        });

        const payload = await response.json();
        if (!response.ok || !payload.success) {
            if (statusEl) statusEl.textContent = String(payload?.message || 'Gagal memproses request billing.');
            return;
        }

        BILLING_FLOAT_CHAT.adminThreads = Array.isArray(payload.threads) ? payload.threads : BILLING_FLOAT_CHAT.adminThreads;
        BILLING_FLOAT_CHAT.messages = Array.isArray(payload.messages) ? payload.messages : BILLING_FLOAT_CHAT.messages;
        BILLING_FLOAT_CHAT.pendingBillings = Array.isArray(payload.pending_billings) ? payload.pending_billings : BILLING_FLOAT_CHAT.pendingBillings;
        BILLING_FLOAT_CHAT.selectedUserId = Number(payload.thread_user_id || BILLING_FLOAT_CHAT.selectedUserId || 0);
        BILLING_FLOAT_CHAT.unreadCount = BILLING_FLOAT_CHAT.adminThreads.reduce((sum, thread) => sum + Math.max(0, Number(thread.unread_count || 0)), 0);
        BILLING_FLOAT_CHAT.pendingCount = BILLING_FLOAT_CHAT.adminThreads.reduce((sum, thread) => sum + Math.max(0, Number(thread.pending_billing_count || 0)), 0);
        BILLING_FLOAT_CHAT.shouldForceScrollBottom = true;
        renderBillingFloatChatUnreadBadge();
        renderBillingFloatAdminNotifications();
        if (statusEl) statusEl.textContent = String(payload.message || 'Request billing berhasil diproses.');
    } catch (_error) {
        if (statusEl) statusEl.textContent = 'Koneksi gagal. Coba ulangi aksi.';
    }
}

function setBillingFloatChatOpen(open) {
    const card = el('billing-float-chat-card');
    const toggle = el('billing-float-chat-toggle');
    if (!(card instanceof HTMLElement) || !(toggle instanceof HTMLButtonElement)) return;

    BILLING_FLOAT_CHAT.isOpen = Boolean(open);
    card.classList.toggle('is-open', BILLING_FLOAT_CHAT.isOpen);
    toggle.classList.toggle('is-open', BILLING_FLOAT_CHAT.isOpen);
    document.body.classList.toggle('billing-chat-lock-scroll', BILLING_FLOAT_CHAT.isOpen);
    renderBillingFloatChatUnreadBadge();

    if (BILLING_FLOAT_CHAT.isOpen) {
        if (IS_ADMIN) {
            renderBillingFloatAdminNotifications();
            loadBillingFloatAdminNotifications();
            if (BILLING_FLOAT_CHAT.selectedUserId > 0) {
                loadBillingFloatAdminThread(BILLING_FLOAT_CHAT.selectedUserId, { silent: true });
            }
            return;
        }

        loadBillingFloatChatThread();
        const input = el('billing-float-chat-input');
        if (input instanceof HTMLTextAreaElement) {
            setTimeout(() => input.focus(), 60);
        }
    }
}

function initFloatingBillingChatWidget() {
    if (!el('billing-float-chat-toggle') || !el('billing-float-chat-card')) return;

    el('billing-float-chat-toggle')?.addEventListener('click', () => {
        setBillingFloatChatOpen(!BILLING_FLOAT_CHAT.isOpen);
    });
    el('billing-float-chat-close')?.addEventListener('click', () => {
        setBillingFloatChatOpen(false);
    });

    document.addEventListener('pointerdown', (event) => {
        if (!BILLING_FLOAT_CHAT.isOpen) return;
        const card = el('billing-float-chat-card');
        const toggle = el('billing-float-chat-toggle');
        const target = event.target;
        if (!(target instanceof Node) || !(card instanceof HTMLElement) || !(toggle instanceof HTMLElement)) return;
        if (card.contains(target) || toggle.contains(target)) return;
        setBillingFloatChatOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && BILLING_FLOAT_CHAT.isOpen) {
            setBillingFloatChatOpen(false);
        }
    });

    el('billing-float-chat-input')?.addEventListener('keydown', (event) => {
        if (!(event.target instanceof HTMLTextAreaElement)) return;
        if (event.key !== 'Enter' || event.shiftKey) return;
        event.preventDefault();
        el('billing-float-chat-form')?.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    });

    if (IS_ADMIN) {
        renderBillingFloatAdminNotifications();

        el('billing-admin-user-search')?.addEventListener('input', (event) => {
            BILLING_FLOAT_CHAT.adminFilterQuery = String(event.target?.value || '');
            renderBillingFloatAdminNotifications();
        });

        el('billing-admin-user-list')?.addEventListener('click', (event) => {
            const target = event.target instanceof Element ? event.target.closest('[data-admin-user-id]') : null;
            if (!(target instanceof HTMLElement)) return;
            const userId = Number(target.getAttribute('data-admin-user-id') || 0);
            if (!userId) return;
            BILLING_FLOAT_CHAT.threadClearedManually = false;
            BILLING_FLOAT_CHAT.selectedUserId = userId;
            BILLING_FLOAT_CHAT.messages = [];
            BILLING_FLOAT_CHAT.pendingBillings = [];
            renderBillingFloatAdminNotifications();
            loadBillingFloatAdminThread(userId);
        });

        el('billing-admin-clear-thread')?.addEventListener('click', () => {
            clearBillingFloatAdminSelection();
        });

        el('billing-admin-pending-list')?.addEventListener('click', (event) => {
            const target = event.target instanceof Element ? event.target.closest('[data-billing-id][data-billing-decision]') : null;
            if (!(target instanceof HTMLElement)) return;
            const billingId = Number(target.getAttribute('data-billing-id') || 0);
            const decision = String(target.getAttribute('data-billing-decision') || '');
            if (decision === 'reject') {
                const ok = window.confirm('Reject request billing ini? User harus submit ulang jika ditolak.');
                if (!ok) return;
            }
            processBillingFloatAdminDecision(billingId, decision);
        });

        el('billing-float-chat-form')?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const input = el('billing-float-chat-input');
            const sendBtn = el('billing-float-chat-send');
            const statusEl = el('billing-float-chat-status');
            const userId = Math.max(0, Number(BILLING_FLOAT_CHAT.selectedUserId || 0));
            if (!(input instanceof HTMLTextAreaElement) || !(sendBtn instanceof HTMLButtonElement) || userId <= 0) return;

            const message = String(input.value || '').trim();
            if (!message) return;

            sendBtn.disabled = true;
            if (statusEl) statusEl.textContent = 'Mengirim balasan admin...';
            try {
                const response = await fetch(ROUTES.billingChatSend, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ user_id: userId, message }),
                });
                if (!response.ok) {
                    if (statusEl) statusEl.textContent = 'Gagal kirim balasan (' + String(response.status) + ').';
                    return;
                }

                const payload = await response.json();
                BILLING_FLOAT_CHAT.messages = Array.isArray(payload.messages) ? payload.messages : BILLING_FLOAT_CHAT.messages;
                if (Array.isArray(payload.threads)) {
                    BILLING_FLOAT_CHAT.adminThreads = payload.threads;
                }
                BILLING_FLOAT_CHAT.pendingCount = BILLING_FLOAT_CHAT.adminThreads.reduce((sum, thread) => sum + Math.max(0, Number(thread.pending_billing_count || 0)), 0);
                BILLING_FLOAT_CHAT.unreadCount = BILLING_FLOAT_CHAT.adminThreads.reduce((sum, thread) => sum + Math.max(0, Number(thread.unread_count || 0)), 0);
                BILLING_FLOAT_CHAT.shouldForceScrollBottom = true;
                input.value = '';
                renderBillingFloatChatUnreadBadge();
                renderBillingFloatAdminNotifications();
                if (statusEl) statusEl.textContent = 'Balasan terkirim.';
            } catch (_error) {
                if (statusEl) statusEl.textContent = 'Koneksi gagal. Coba kirim ulang.';
            } finally {
                sendBtn.disabled = false;
            }
        });

        loadBillingFloatAdminNotifications();
        setInterval(() => {
            loadBillingFloatAdminNotifications();
            if (BILLING_FLOAT_CHAT.isOpen && BILLING_FLOAT_CHAT.selectedUserId > 0) {
                loadBillingFloatAdminThread(BILLING_FLOAT_CHAT.selectedUserId, { silent: true });
            }
        }, 7000);
        return;
    }

    renderBillingFloatChatMessages();
    loadBillingFloatChatThread();

    el('billing-float-chat-form')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        const input = el('billing-float-chat-input');
        const sendBtn = el('billing-float-chat-send');
        const statusEl = el('billing-float-chat-status');
        if (!(input instanceof HTMLTextAreaElement) || !(sendBtn instanceof HTMLButtonElement)) return;

        const message = String(input.value || '').trim();
        if (!message) return;

        sendBtn.disabled = true;
        if (statusEl) statusEl.textContent = 'Mengirim pesan...';
        try {
            const response = await fetch(ROUTES.billingChatSend, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ message }),
            });
            if (!response.ok) {
                if (statusEl) statusEl.textContent = 'Gagal kirim pesan (' + String(response.status) + ').';
                return;
            }

            const payload = await response.json();
            BILLING_FLOAT_CHAT.messages = Array.isArray(payload.messages) ? payload.messages : BILLING_FLOAT_CHAT.messages;
            BILLING_FLOAT_CHAT.unreadCount = 0;
            BILLING_FLOAT_CHAT.shouldForceScrollBottom = true;
            input.value = '';
            renderBillingFloatChatMessages();
            renderBillingFloatChatUnreadBadge();
            if (statusEl) statusEl.textContent = 'Pesan terkirim.';
        } catch (_error) {
            if (statusEl) statusEl.textContent = 'Gagal kirim pesan. Coba lagi.';
        } finally {
            sendBtn.disabled = false;
        }
    });

    BILLING_FLOAT_CHAT.timer = setInterval(() => {
        if (!BILLING_FLOAT_CHAT.isOpen) return;
        loadBillingFloatChatThread();
    }, 7000);

    loadBillingFloatChatUnreadCount();
    setInterval(() => {
        if (BILLING_FLOAT_CHAT.isOpen) return;
        loadBillingFloatChatUnreadCount();
    }, 7000);
}

function syncDashboardFloatingChatOffsets() {
    const root = document.documentElement;
    if (!(root instanceof HTMLElement)) return;

    const defaultBtn = 'max(0.55rem, calc(0.55rem + env(safe-area-inset-bottom)))';
    const defaultCard = 'max(4.9rem, calc(4.9rem + env(safe-area-inset-bottom)))';

    const nav = document.querySelector('.workspace-nav');
    if (!(nav instanceof HTMLElement)) {
        root.style.setProperty('--billing-float-bottom-offset', defaultBtn);
        root.style.setProperty('--billing-float-card-bottom-offset', defaultCard);
        return;
    }

    const navRect = nav.getBoundingClientRect();
    if (navRect.height <= 0) {
        root.style.setProperty('--billing-float-bottom-offset', defaultBtn);
        root.style.setProperty('--billing-float-card-bottom-offset', defaultCard);
        return;
    }

    const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
    if (viewportHeight <= 0) {
        root.style.setProperty('--billing-float-bottom-offset', defaultBtn);
        root.style.setProperty('--billing-float-card-bottom-offset', defaultCard);
        return;
    }

    // Only adjust when the workspace nav is rendered in the lower viewport area.
    if (navRect.top < viewportHeight * 0.55) {
        root.style.setProperty('--billing-float-bottom-offset', defaultBtn);
        root.style.setProperty('--billing-float-card-bottom-offset', defaultCard);
        return;
    }

    const navBottomGap = Math.max(0, viewportHeight - navRect.bottom);
    const rawBtnOffset = Math.max(8, Math.round(navRect.height + navBottomGap + 8));
    const btnOffsetPx = Math.min(120, rawBtnOffset);
    const cardOffsetPx = Math.min(240, btnOffsetPx + 86);

    root.style.setProperty('--billing-float-bottom-offset', String(btnOffsetPx) + 'px');
    root.style.setProperty('--billing-float-card-bottom-offset', String(cardOffsetPx) + 'px');
}

function attachInputHelpTooltips() {
    const helpByField = {
        active_strategy: 'Pilih strategi aktif yang dipakai bot saat ini.',
        base_lot: 'Lot awal order pertama; makin besar, risiko dan profit ikut naik.',
        timeframe_logic: 'Timeframe baca sinyal; kecil lebih cepat, besar lebih stabil.',
        max_drawdown_pct: 'Batas DD untuk stop entry baru; isi 0 jika ingin nonaktif.',
        dd_breach_hits_required: 'Jumlah konfirmasi DD sebelum stop agar tidak mudah false alarm.',
        max_drawdown_stop_delay: 'Jeda sebelum DD stop dieksekusi; 0 berarti langsung stop.',
        daily_profit_target: 'Target profit harian; saat tercapai, bot bisa berhenti entry baru.',
        grid_max_layers: 'Batas maksimum jumlah layer grid yang boleh terbuka.',
        grid_max_accumulative_lot: 'Batas total lot semua layer agar akumulasi tetap terkendali.',
        grid_mode: 'Pilih jarak grid tetap (Fix) atau mengikuti volatilitas (ATR).',
        fix_grid_distance: 'Jarak antar layer saat mode Fix Points aktif.',
        atr_multiplier: 'Pengali ATR untuk jarak grid dinamis; makin besar makin renggang.',
        grid_mart_type: 'Cara naik lot per layer: tambah tetap atau kali lipat.',
        grid_mart_addition: 'Nilai tambahan lot untuk setiap layer baru pada mode Penambahan.',
        grid_mart_multiplier: 'Nilai pengali lot untuk setiap layer baru pada mode Perkalian.',
        grid_tp_points: 'Target take profit grid dalam points; isi 0 untuk nonaktif.',
        grid_sl_points: 'Batas stop loss grid dalam points; isi 0 untuk nonaktif.',
        grid_basket_tp_percent: 'Target close semua posisi basket berdasarkan persentase profit.',
        grid_tp_mode: 'Mode TP basket: satu target atau bertingkat sesuai jumlah layer.',
        zero_gap_tp_points: 'Target take profit Zero Gap dalam points.',
        zero_gap_sl_points: 'Batas stop loss Zero Gap dalam points.',
        zero_gap_max_layers: 'Batas layer maksimum khusus strategi Zero Gap.',
        mirror_pending_distance_points: 'Jarak pending order pelindung dari harga sekarang.',
        mirror_multiplier: 'Pengali lot untuk Mirror Trap; naikkan perlahan sesuai modal.',
        mart_tp_points: 'Target take profit Pure Martingale dalam points.',
        mart_sl_points: 'Batas stop loss Pure Martingale dalam points.',
        mart_max_steps: 'Jumlah langkah martingale maksimum yang diizinkan.',
        mart_type: 'Jenis martingale: linear atau geometris.',
        mart_multiplier: 'Nilai pengali lot saat martingale geometris aktif.',
        mart_addition: 'Nilai tambahan lot saat martingale linear aktif.',
        min_confluence_score: 'Ambang minimal kualitas sinyal; makin tinggi makin selektif.',
        sydney_start_wib: 'Jam mulai sesi Sydney dalam WIB.',
        sydney_end_wib: 'Jam selesai sesi Sydney dalam WIB.',
        asia_start_wib: 'Jam mulai sesi Asia/Tokyo dalam WIB.',
        asia_end_wib: 'Jam selesai sesi Asia/Tokyo dalam WIB.',
        europe_start_wib: 'Jam mulai sesi London dalam WIB.',
        europe_end_wib: 'Jam selesai sesi London dalam WIB.',
        us_start_wib: 'Jam mulai sesi New York/US dalam WIB.',
        us_end_wib: 'Jam selesai sesi New York/US dalam WIB; waspadai news 19.00-21.00.'
    };

    const infoIconSvg = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><circle cx="12" cy="8" r="1.2" fill="currentColor"/><path d="M12 11.2V16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';

    Object.keys(helpByField).forEach((fieldId) => {
        const label = document.querySelector('label[for="' + fieldId + '"]');
        if (!(label instanceof HTMLElement)) return;
        if (label.querySelector('.tooltip-info-icon')) return;

        const icon = document.createElement('span');
        icon.className = 'text-secondary ms-1 tooltip-info-icon';
        icon.setAttribute('data-bs-toggle', 'tooltip');
        icon.setAttribute('data-bs-placement', 'top');
        icon.setAttribute('title', helpByField[fieldId]);
        icon.setAttribute('aria-label', 'Info ' + fieldId);
        icon.setAttribute('role', 'button');
        icon.setAttribute('tabindex', '0');
        icon.innerHTML = infoIconSvg;
        label.appendChild(icon);
    });
}

attachInputHelpTooltips();
initBillingAdminActionTooltips();
initFloatingBillingChatWidget();
syncDashboardFloatingChatOffsets();
window.addEventListener('resize', syncDashboardFloatingChatOffsets, { passive: true });
window.addEventListener('orientationchange', syncDashboardFloatingChatOffsets, { passive: true });

updateNewsWidget();
renderLiveLicenseCountdownTick();
setInterval(() => {
    updateNewsWidget();
    renderLiveLicenseCountdownTick();
    renderAnalysisServerClockTick();
}, 1000);
// Polling remains as fallback when SSE cannot be established.
