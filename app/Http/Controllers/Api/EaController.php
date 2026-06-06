<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EaConfiguration;
use App\Models\EconomicNews;
use App\Models\EaSettingAudit;
use App\Models\EaStatusReport;
use App\Services\Mt5LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class EaController extends Controller
{
    public function __construct(private readonly Mt5LicenseService $licenseService)
    {
    }

    public function getConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'pair_symbol' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_\/\.\-]+$/'],
        ]);

        $configuration = $this->resolveConfiguration($request, $validated['account_id']);

        if ($configuration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized account.',
            ], 401);
        }

        $newsBlocked = $this->isNewsBlockedNow($configuration);
        $licenseStatus = $this->licenseService->getStatusByAccountId((string) $configuration->account_id);
        $licenseEnforcementEnabled = $this->licenseService->isEnforcementEnabled();
        $licenseInactive = $licenseEnforcementEnabled && !(bool) ($licenseStatus['license_active'] ?? false);
        $runtimeLayers = max(0, (int) ($configuration->current_layers ?? 0));
        $runtimeAccLot = max(0.0, (float) ($configuration->current_accumulative_lot ?? 0));
        $hasActiveExposure = $runtimeLayers > 0 || $runtimeAccLot > 0.0000001;
        $effectiveGuardStatus = (string) ($configuration->guard_status ?? 'PAUSED');
        if ($licenseInactive) {
            if ($hasActiveExposure) {
                if (strtoupper($effectiveGuardStatus) !== 'DD_STOP') {
                    $effectiveGuardStatus = 'LIVE';
                }
            } else {
                $effectiveGuardStatus = 'PAUSED';
            }
        }

        $effectiveTradingEnabled = strtoupper($effectiveGuardStatus) === 'LIVE';
        $isDdStop = strtoupper($effectiveGuardStatus) === 'DD_STOP';
        $licenseExpiredWithExposure = $licenseInactive && $hasActiveExposure;
        $allowOpenNewCycle = !$isDdStop && !$licenseInactive && $effectiveTradingEnabled;
        $allowManageExistingCycle = !$isDdStop && ($effectiveTradingEnabled || $licenseExpiredWithExposure);
        $runtimeTradingMode = $isDdStop
            ? 'FORCE_CLOSE'
            : ($licenseExpiredWithExposure
                ? 'LIVE_FULL'
                : ($effectiveTradingEnabled ? 'LIVE_FULL' : 'PAUSED'));

        return response()->json([
            'success' => true,
            'account_id' => $configuration->account_id,
            'pair_symbol' => (string) ($configuration->pair_symbol ?? 'XAUUSD'),
            'strategy' => (int) $configuration->active_strategy,
            'timeframe' => (int) $configuration->timeframe_logic,
            'snr_filter' => (bool) $configuration->filter_snr_activation,
            'base_lot' => (float) $configuration->base_lot,
            'bb_period' => (int) ($configuration->bb_period ?? 20),
            'bb_deviation' => (float) ($configuration->bb_deviation ?? 2.0),
            'rsi_period' => (int) ($configuration->rsi_period ?? 14),
            'rsi_buy_level' => (float) ($configuration->rsi_buy_level ?? 45.0),
            'rsi_sell_level' => (float) ($configuration->rsi_sell_level ?? 55.0),
            'adx_period' => (int) ($configuration->adx_period ?? 14),
            'adx_level' => (float) ($configuration->adx_level ?? 25.0),
            'adx_bars' => (int) ($configuration->adx_bars ?? 3),
            'adx_sideways' => (float) ($configuration->adx_sideways ?? 18.0),
            'ema_period' => (int) ($configuration->ema_period ?? 50),
            'ema_fast' => (int) ($configuration->ema_fast ?? 20),
            'ema_slow' => (int) ($configuration->ema_slow ?? 50),
            'ema_slope_min' => (float) ($configuration->ema_slope_min ?? 0.03),
            'atr_period' => (int) ($configuration->atr_period ?? 14),
            'use_dxy_filter' => (bool) ($configuration->use_dxy_filter ?? false),
            'use_us10y_filter' => (bool) ($configuration->use_us10y_filter ?? false),
            'use_vix_filter' => (bool) ($configuration->use_vix_filter ?? false),
            'use_oil_filter' => (bool) ($configuration->use_oil_filter ?? false),
            'trail_start' => (float) ($configuration->trail_start ?? 0),
            'trail_stop' => (float) ($configuration->trail_stop ?? 0),
            'trail_step' => (float) ($configuration->trail_step ?? 0),
            'friday_stop_day' => (string) ($configuration->friday_stop_day ?? 'friday'),
            'friday_stop_wib' => (string) ($configuration->friday_stop_wib ?? '23:45'),
            'friday_resume_wib' => (string) ($configuration->friday_resume_wib ?? '06:15'),
            'runtime_market_group' => [
                'use_dxy_filter' => (bool) ($configuration->use_dxy_filter ?? false),
                'use_us10y_filter' => (bool) ($configuration->use_us10y_filter ?? false),
                'use_vix_filter' => (bool) ($configuration->use_vix_filter ?? false),
                'use_oil_filter' => (bool) ($configuration->use_oil_filter ?? false),
            ],
            'friday_market_close' => [
                'enabled' => (bool) ($configuration->use_friday_market_close_window ?? false),
                'stop_day' => (string) ($configuration->friday_stop_day ?? 'friday'),
                'stop_wib' => (string) ($configuration->friday_stop_wib ?? '23:45'),
                'resume_wib' => (string) ($configuration->friday_resume_wib ?? '06:15'),
            ],
            'strategy_params' => $this->strategyParams($configuration),
            'news_block' => [
                'severity' => (string) $configuration->news_filter_severity,
                'pause_before' => (int) $configuration->news_pause_before_minutes,
                'pause_after' => (int) $configuration->news_pause_after_minutes,
                'is_blocked' => $newsBlocked,
            ],
            'guard_status' => $effectiveGuardStatus,
            'trading_enabled' => $effectiveTradingEnabled,
            'allow_open_new_cycle' => $allowOpenNewCycle,
            'allow_manage_existing_cycle' => $allowManageExistingCycle,
            'force_close_required' => $isDdStop,
            'runtime_trading_mode' => $runtimeTradingMode,
            'license_status' => (string) ($licenseStatus['license_status'] ?? 'unlicensed'),
            'license_active' => (bool) ($licenseStatus['license_active'] ?? false),
            'license_remaining_seconds' => (int) ($licenseStatus['license_remaining_seconds'] ?? 0),
            'license_remaining_text' => (string) ($licenseStatus['license_remaining_text'] ?? 'No license'),
            'license_enforcement_enabled' => $licenseEnforcementEnabled,
            'server_time' => Carbon::now()->toIso8601String(),
        ]);
    }

    public function reportStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'pair_symbol' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_\/\.\-]+$/'],
            'current_layers' => ['required', 'integer', 'min:0'],
            'current_accumulative_lot' => ['required', 'numeric', 'min:0'],
            'global_floating' => ['required', 'numeric'],
            'guard_status' => ['required', 'string', 'max:32'],
            'balance' => ['nullable', 'numeric'],
            'equity' => ['nullable', 'numeric'],
            'wins' => ['nullable', 'integer', 'min:0'],
            'losses' => ['nullable', 'integer', 'min:0'],
            'realized_profit' => ['nullable', 'numeric'],
            'daily_profit' => ['nullable', 'numeric'],
            'weekly_profit' => ['nullable', 'numeric'],
            'monthly_profit' => ['nullable', 'numeric'],
        ]);

        $configuration = $this->resolveConfiguration($request, $validated['account_id']);

        if ($configuration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
            ], 404);
        }

        $configuration->update([
            'live_open_layers' => (int) $validated['current_layers'],
            'live_floating_pnl' => (float) $validated['global_floating'],
            'live_guard_status' => (string) $validated['guard_status'],
            'current_layers' => (int) $validated['current_layers'],
            'current_accumulative_lot' => (float) $validated['current_accumulative_lot'],
            'global_floating' => (float) $validated['global_floating'],
            'guard_status' => (string) $validated['guard_status'],
            'is_online' => true,
        ]);

        EaStatusReport::query()->create([
            'user_id' => $configuration->user_id,
            'ea_configuration_id' => $configuration->id,
            'account_id' => $configuration->account_id,
            'current_layers' => $validated['current_layers'],
            'current_accumulative_lot' => $validated['current_accumulative_lot'],
            'global_floating' => $validated['global_floating'],
            'guard_status' => $validated['guard_status'],
            'balance' => isset($validated['balance']) ? (float) $validated['balance'] : null,
            'equity' => isset($validated['equity']) ? (float) $validated['equity'] : null,
            'open_positions' => $this->readTelemetryArray($request, ['open_positions', 'positions', 'positions_data']),
            'pending_orders' => $this->readTelemetryArray($request, ['pending_orders', 'orders_pending']),
            'closed_trades' => $this->readTelemetryArray($request, ['closed_trades', 'history_trades', 'trade_history']),
            'wins' => (int) ($validated['wins'] ?? 0),
            'losses' => (int) ($validated['losses'] ?? 0),
            'realized_profit' => (float) ($validated['realized_profit'] ?? 0),
            'daily_profit' => (float) ($validated['daily_profit'] ?? 0),
            'weekly_profit' => (float) ($validated['weekly_profit'] ?? 0),
            'monthly_profit' => (float) ($validated['monthly_profit'] ?? 0),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Telemetry updated.',
        ]);
    }

    public function updateSetting(Request $request): JsonResponse
    {
        $request->merge([
            'fix_grid_distance' => $request->input('fix_grid_distance', $request->input('grid_fix_distance')),
            'atr_multiplier' => $request->input('atr_multiplier', $request->input('grid_atr_multiplier')),
            // Keep legacy MT5 pull keys in sync with dashboard strategy inputs.
            'max_layers' => $request->input('max_layers', $request->input('grid_max_layers')),
            'max_accumulative_lot' => $request->input('max_accumulative_lot', $request->input('grid_max_accumulative_lot')),
        ]);

        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'pair_symbol' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_\/\.\-]+$/'],
            'active_strategy' => ['required', 'integer', Rule::in([0, 1, 2])],
            'base_lot' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'timeframe_logic' => ['required', 'integer', Rule::in([1, 5, 15, 30, 60, 240, 1440])],
            'max_drawdown_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],

            'max_layers' => ['nullable', 'integer', 'min:1', 'max:200'],
            'max_accumulative_lot' => ['nullable', 'numeric', 'min:0.01', 'max:500'],

            'grid_max_layers' => ['nullable', 'integer', 'min:1', 'max:200'],
            'grid_max_accumulative_lot' => ['nullable', 'numeric', 'min:0.01', 'max:500'],
            'grid_mode' => ['nullable', 'integer', Rule::in([0, 1])],
            'fix_grid_distance' => ['nullable', 'integer', 'min:1', 'max:9999999'],
            'atr_multiplier' => ['nullable', 'numeric', 'min:0.1', 'max:100'],
            'grid_target_usd' => ['nullable', 'numeric', 'min:0'],
            'grid_tp_points' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'grid_sl_points' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'grid_use_trailing_layer1' => ['nullable', 'boolean'],
            'grid_use_basket_tp_percent' => ['nullable', 'boolean'],
            'grid_basket_tp_percent' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'grid_tp_mode' => ['nullable', 'integer', Rule::in([0, 1])],
            'grid_tier1_tp_percent' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'grid_tier2_tp_percent' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'grid_tier3_tp_percent' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'grid_tier4_tp_percent' => ['nullable', 'numeric', 'min:0', 'max:1000'],

            'mirror_active' => ['nullable', 'boolean'],
            'mirror_pending_distance_points' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'mirror_multiplier' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'zero_gap_tp_points' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'zero_gap_sl_points' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'zero_gap_max_layers' => ['nullable', 'integer', 'min:1', 'max:200'],
            'zero_gap_trailing_start_points' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'zero_gap_trailing_step_points' => ['nullable', 'integer', 'min:0', 'max:100000'],

            'mart_max_steps' => ['nullable', 'integer', 'min:1', 'max:100'],
            'mart_type' => ['nullable', 'integer', Rule::in([0, 1])],
            'mart_multiplier' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
            'mart_addition' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'mart_tp_points' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'mart_sl_points' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'mart_trailing_start_points' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'mart_trailing_step_points' => ['nullable', 'integer', 'min:0', 'max:100000'],

            'filter_snr_activation' => ['nullable', 'boolean'],
            'news_filter_severity' => ['nullable', 'string', Rule::in(['HIGH', 'MEDIUM', 'LOW', 'BOTH', 'ALL'])],
            'news_pause_before_minutes' => ['nullable', 'integer', 'min:0', 'max:240'],
            'news_pause_after_minutes' => ['nullable', 'integer', 'min:0', 'max:240'],
            'use_pending_guard' => ['nullable', 'boolean'],
            'auto_flip' => ['nullable', 'boolean'],
            'always_in_market' => ['nullable', 'boolean'],
            'instant_reentry' => ['nullable', 'boolean'],
            'min_confluence_score' => ['nullable', 'integer', 'min:0', 'max:10'],
            'use_trend_filter' => ['nullable', 'boolean'],
            'use_ai_core_sharpening' => ['nullable', 'boolean'],
            'use_ema_ribbon' => ['nullable', 'boolean'],
            'use_dmi' => ['nullable', 'boolean'],
            'use_mkt_struct' => ['nullable', 'boolean'],
            'use_early_trend' => ['nullable', 'boolean'],
            'use_sniper_entry' => ['nullable', 'boolean'],
            'show_indicator_fallback_logs' => ['nullable', 'boolean'],
            'bb_period' => ['nullable', 'integer', 'min:1'],
            'bb_deviation' => ['nullable', 'numeric', 'min:0.1'],
            'rsi_period' => ['nullable', 'integer', 'min:1'],
            'rsi_buy_level' => ['nullable', 'numeric', 'between:0,100'],
            'rsi_sell_level' => ['nullable', 'numeric', 'between:0,100'],
            'adx_period' => ['nullable', 'integer', 'min:1'],
            'adx_level' => ['nullable', 'numeric', 'min:0'],
            'adx_bars' => ['nullable', 'integer', 'min:1'],
            'adx_sideways' => ['nullable', 'numeric', 'min:0'],
            'ema_period' => ['nullable', 'integer', 'min:1'],
            'ema_fast' => ['nullable', 'integer', 'min:1'],
            'ema_slow' => ['nullable', 'integer', 'min:1'],
            'ema_slope_min' => ['nullable', 'numeric', 'min:0'],
            'atr_period' => ['nullable', 'integer', 'min:1'],
            'use_dxy_filter' => ['nullable', 'boolean'],
            'use_us10y_filter' => ['nullable', 'boolean'],
            'use_vix_filter' => ['nullable', 'boolean'],
            'use_oil_filter' => ['nullable', 'boolean'],
            'use_friday_market_close_window' => ['nullable', 'boolean'],
            'friday_stop_day' => ['nullable', Rule::in(['friday', 'saturday'])],
            'friday_stop_wib' => ['nullable', 'date_format:H:i'],
            'friday_resume_wib' => ['nullable', 'date_format:H:i'],
            'use_mirror_trap' => ['nullable', 'boolean'],
            'use_stealth_mode' => ['nullable', 'boolean'],
            'use_sydney_session' => ['nullable', 'boolean'],
            'sydney_start_wib' => ['nullable', 'date_format:H:i'],
            'sydney_end_wib' => ['nullable', 'date_format:H:i'],
            'use_asia_session' => ['nullable', 'boolean'],
            'asia_start_wib' => ['nullable', 'date_format:H:i'],
            'asia_end_wib' => ['nullable', 'date_format:H:i'],
            'use_europe_session' => ['nullable', 'boolean'],
            'europe_start_wib' => ['nullable', 'date_format:H:i'],
            'europe_end_wib' => ['nullable', 'date_format:H:i'],
            'use_us_session' => ['nullable', 'boolean'],
            'us_start_wib' => ['nullable', 'date_format:H:i'],
            'us_end_wib' => ['nullable', 'date_format:H:i'],
            'trail_start' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'trail_stop' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'trail_step' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'apply_logic_globally' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $isAdmin = (bool) ($user->is_admin || $role === 'admin');

        $logicOnlyFields = [
            'use_pending_guard', 'auto_flip', 'use_trend_filter', 'use_ai_core_sharpening',
            'use_ema_ribbon', 'use_dmi', 'use_mkt_struct', 'use_early_trend', 'use_sniper_entry',
            'bb_period', 'bb_deviation', 'rsi_period', 'rsi_buy_level', 'rsi_sell_level',
            'adx_period', 'adx_level', 'adx_bars', 'adx_sideways',
            'ema_period', 'ema_fast', 'ema_slow', 'ema_slope_min', 'atr_period', 'use_dxy_filter',
            'use_us10y_filter', 'use_vix_filter', 'use_oil_filter',
            'use_friday_market_close_window', 'friday_stop_day', 'friday_stop_wib', 'friday_resume_wib',
                'use_stealth_mode', 'show_indicator_fallback_logs', 'close_all_on_news',
                'use_sydney_session', 'sydney_start_wib', 'sydney_end_wib',
                'use_asia_session', 'asia_start_wib', 'asia_end_wib',
                'use_europe_session', 'europe_start_wib', 'europe_end_wib',
                'use_us_session', 'us_start_wib', 'us_end_wib',
                'trail_start', 'trail_stop', 'trail_step',
        ];

            $applyLogicGlobally = $isAdmin && (bool) ($validated['apply_logic_globally'] ?? false);
            unset($validated['apply_logic_globally']);

        if (!$isAdmin) {
            foreach ($logicOnlyFields as $field) {
                unset($validated[$field]);
            }

            // Keep testing strategies admin-only even if payload is tampered client-side.
            $validated['active_strategy'] = 0;
        }

        $pairSymbol = $this->requestedPairSymbol($request);

        $configurationQuery = EaConfiguration::query()
            ->where('account_id', $validated['account_id']);
        if ($pairSymbol !== null) {
            $configurationQuery->where('pair_symbol', $pairSymbol);
        }
        if (!$isAdmin) {
            $configurationQuery->where('user_id', $user->id);
        }

        $configuration = $configurationQuery->first();

        if ($configuration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Account tidak ditemukan atau tidak punya akses.',
            ], 404);
        }

        if ($applyLogicGlobally) {
            $logicPayload = [];
            foreach ($logicOnlyFields as $field) {
                if (array_key_exists($field, $validated)) {
                    $logicPayload[$field] = $validated[$field];
                }
            }

            if ($logicPayload === []) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada field logic yang dikirim untuk global update.',
                ], 422);
            }

            $globalConfigs = EaConfiguration::query()->get();
            $updatedCount = 0;
            foreach ($globalConfigs as $cfg) {
                $beforeValues = [];
                foreach (array_keys($logicPayload) as $field) {
                    $beforeValues[$field] = $cfg->getAttribute($field);
                }

                $cfg->fill($logicPayload);
                $dirty = $cfg->getDirty();
                if ($dirty === []) {
                    continue;
                }

                $cfg->save();
                $updatedCount++;

                $changedFields = array_keys($dirty);
                $afterValues = [];
                foreach ($changedFields as $field) {
                    $afterValues[$field] = $cfg->getAttribute($field);
                }

                EaSettingAudit::query()->create([
                    'user_id' => $request->user()->id,
                    'ea_configuration_id' => $cfg->id,
                    'account_id' => $cfg->account_id,
                    'changed_fields' => $changedFields,
                    'before_values' => array_intersect_key($beforeValues, array_flip($changedFields)),
                    'after_values' => $afterValues,
                    'ip_address' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                ]);

                $signalAccountId = (string) $cfg->account_id;
                $signalPairRaw = strtoupper((string) ($cfg->pair_symbol ?? ''));
                $signalPair = preg_replace('/[^A-Z0-9]/', '', $signalPairRaw) ?? '';
                $signalKey = $signalPair !== ''
                    ? ('ea:signal:' . $signalAccountId . ':' . $signalPair)
                    : ('ea:signal:' . $signalAccountId);

                Cache::put($signalKey, [
                    'action' => 'RELOAD_CONFIG',
                    'source' => 'dashboard_logic_global_save',
                    'updated_at' => Carbon::now()->toIso8601String(),
                ], Carbon::now()->addMinutes(3));
            }

            return response()->json([
                'success' => true,
                'message' => 'Global logic update berhasil diterapkan ke semua account.',
                'updated_count' => $updatedCount,
                'data' => $configuration->fresh(),
            ]);
        }

        $licenseStatus = $this->licenseService->getStatusByAccountId((string) $configuration->account_id);
        if ($this->licenseService->isEnforcementEnabled() && !(bool) ($licenseStatus['license_active'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Lisensi account tidak aktif. Perubahan setting diblokir.',
                'license' => $licenseStatus,
            ], 403);
        }

        $emaFast = isset($validated['ema_fast']) ? (int) $validated['ema_fast'] : (int) ($configuration->ema_fast ?? 20);
        $emaSlow = isset($validated['ema_slow']) ? (int) $validated['ema_slow'] : (int) ($configuration->ema_slow ?? 50);
        if ($emaFast >= $emaSlow) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: EMA Fast harus lebih kecil dari EMA Slow.',
            ], 422);
        }

        $before = [];
        foreach (array_keys($validated) as $field) {
            if ($field === 'account_id') {
                continue;
            }
            $before[$field] = $configuration->getAttribute($field);
        }

        $configuration->fill($validated);
        $dirty = $configuration->getDirty();

        if ($dirty === []) {
            return response()->json([
                'success' => true,
                'message' => 'No changes detected.',
                'data' => $configuration,
            ]);
        }

        $configuration->save();

        $changedFields = array_keys($dirty);
        $beforeValues = [];
        $afterValues = [];
        foreach ($changedFields as $field) {
            $beforeValues[$field] = $before[$field] ?? null;
            $afterValues[$field] = $configuration->getAttribute($field);
        }

        EaSettingAudit::query()->create([
            'user_id' => $request->user()->id,
            'ea_configuration_id' => $configuration->id,
            'account_id' => $configuration->account_id,
            'changed_fields' => $changedFields,
            'before_values' => $beforeValues,
            'after_values' => $afterValues,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $accountId = (string) $configuration->account_id;
        $pairRaw = strtoupper((string) ($configuration->pair_symbol ?? ''));
        $pairSymbol = preg_replace('/[^A-Z0-9]/', '', $pairRaw) ?? '';
        $signalKey = $pairSymbol !== ''
            ? ('ea:signal:' . $accountId . ':' . $pairSymbol)
            : ('ea:signal:' . $accountId);

        Cache::put($signalKey, [
            'action' => 'RELOAD_CONFIG',
            'source' => 'dashboard_save',
            'updated_at' => Carbon::now()->toIso8601String(),
        ], Carbon::now()->addMinutes(3));

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully.',
            'data' => $configuration->fresh(),
        ]);
    }

    private function strategyParams(EaConfiguration $configuration): array
    {
        return match ((int) $configuration->active_strategy) {
            0 => [
                'grid_max_layers' => (int) $configuration->grid_max_layers,
                'grid_max_accumulative_lot' => (float) $configuration->grid_max_accumulative_lot,
                'grid_mode' => (int) $configuration->grid_mode,
                'grid_fix_distance' => (int) $configuration->grid_fix_distance,
                'grid_atr_multiplier' => (float) $configuration->grid_atr_multiplier,
                'grid_target_usd' => (float) $configuration->grid_target_usd,
                'mart_max_steps' => (int) $configuration->mart_max_steps,
                'mart_type' => (int) $configuration->mart_type,
                'mart_multiplier' => (float) $configuration->mart_multiplier,
                'mart_addition' => (float) $configuration->mart_addition,
            ],
            1 => [
                'mirror_active' => (bool) $configuration->mirror_active,
                'mirror_pending_distance' => (int) $configuration->mirror_pending_distance_points,
                'mirror_multiplier' => (float) $configuration->mirror_multiplier,
            ],
            2 => [
                'mart_max_steps' => (int) $configuration->mart_max_steps,
                'mart_type' => (int) $configuration->mart_type,
                'mart_multiplier' => (float) $configuration->mart_multiplier,
                'mart_addition' => (float) $configuration->mart_addition,
            ],
            default => [],
        };
    }

    private function isNewsBlockedNow(EaConfiguration $configuration): bool
    {
        $now = Carbon::now('UTC');
        $severities = $this->severityFilters((string) $configuration->news_filter_severity);
        $currencies = $this->resolveCorrelatedCurrencies((string) ($configuration->pair_symbol ?? ''));

        $latest = EconomicNews::query()
            ->whereIn('impact', $severities)
            ->whereIn('currency', $currencies)
            ->whereBetween('event_at', [$now->copy()->subHours(6), $now->copy()->addHours(6)])
            ->orderByRaw('ABS(TIMESTAMPDIFF(SECOND, event_at, ?)) ASC', [$now->toDateTimeString()])
            ->first();

        if ($latest === null) {
            return false;
        }

        $from = $latest->event_at->copy()->subMinutes((int) $configuration->news_pause_before_minutes);
        $to = $latest->event_at->copy()->addMinutes((int) $configuration->news_pause_after_minutes);

        return $now->between($from, $to);
    }

    private function resolveCorrelatedCurrencies(string $pairSymbol): array
    {
        $normalized = strtoupper(preg_replace('/[^A-Z]/', '', $pairSymbol) ?? '');
        if ($normalized === '') {
            return ['USD'];
        }

        if (str_starts_with($normalized, 'XAU') || str_starts_with($normalized, 'GOLD')) {
            return ['USD', 'XAU', 'EUR', 'GBP'];
        }

        if (str_starts_with($normalized, 'BTC') || str_starts_with($normalized, 'ETH')) {
            return ['USD'];
        }

        if (strlen($normalized) >= 6) {
            $base = substr($normalized, 0, 3);
            $quote = substr($normalized, 3, 3);
            return array_values(array_unique(['USD', $base, $quote]));
        }

        return ['USD'];
    }

    private function severityFilters(string $severity): array
    {
        return match (strtoupper($severity)) {
            'HIGH' => ['HIGH'],
            'MEDIUM' => ['MEDIUM'],
            'LOW' => ['LOW'],
            'ALL' => ['HIGH', 'MEDIUM', 'LOW'],
            default => ['HIGH', 'MEDIUM'],
        };
    }

    private function readTelemetryArray(Request $request, array $keys): array
    {
        foreach ($keys as $key) {
            $value = $request->input($key);
            if (is_array($value)) {
                return $value;
            }

            if (is_string($value) && trim($value) !== '') {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return [];
    }

    private function requestedPairSymbol(Request $request): ?string
    {
        $rawPair = trim((string) $request->input('pair_symbol', $request->query('pair_symbol', '')));
        if ($rawPair === '') {
            return null;
        }

        return strtoupper($rawPair);
    }

    private function resolveConfiguration(Request $request, string $accountId): ?EaConfiguration
    {
        $pairSymbol = $this->requestedPairSymbol($request);

        $query = EaConfiguration::query()->where('account_id', $accountId);
        if ($pairSymbol !== null) {
            $query->where('pair_symbol', $pairSymbol);
        }

        $configuration = $query->first();
        if ($configuration !== null || $pairSymbol !== null) {
            return $configuration;
        }

        return EaConfiguration::query()
            ->where('account_id', $accountId)
            ->first();
    }
}
