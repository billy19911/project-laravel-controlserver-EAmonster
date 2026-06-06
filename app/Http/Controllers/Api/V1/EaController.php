<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EaClosedTrade;
use App\Models\EaConfiguration;
use App\Models\EaStatusReport;
use App\Models\EconomicNews;
use App\Models\User;
use App\Services\EconomicCalendarService;
use App\Services\Mt5LicenseService;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class EaController extends Controller
{
    private static ?bool $hasEaConfigCurrencyColumn = null;
    private static ?bool $hasEaReportCurrencyColumn = null;
    private EconomicCalendarService $calendarService;
    private Mt5LicenseService $licenseService;

    public function __construct(EconomicCalendarService $calendarService, Mt5LicenseService $licenseService)
    {
        $this->calendarService = $calendarService;
        $this->licenseService = $licenseService;
    }
    public function adminUsers(Request $request): JsonResponse
    {
        $guard = $this->ensureStaff($request);
        if ($guard !== null) {
            return $guard;
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $q = trim((string) ($validated['q'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 10);

        $usersQuery = User::query()
            ->withCount('eaConfigurations')
            ->orderByDesc('id');

        if ($q !== '') {
            $usersQuery->where(function ($query) use ($q): void {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('username', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $paginator = $usersQuery->paginate($perPage, ['id', 'name', 'username', 'email', 'is_admin', 'role', 'created_at']);

        return response()->json([
            'success' => true,
            'users' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function adminUserAccounts(Request $request, int $userId): JsonResponse
    {
        $guard = $this->ensureStaff($request);
        if ($guard !== null) {
            return $guard;
        }

        $user = User::query()->find($userId);
        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        $accounts = EaConfiguration::query()
            ->where('user_id', $user->id)
            ->orderBy('account_id')
            ->get([
                'account_id',
                'is_online',
                'guard_status',
                'current_layers',
                'current_accumulative_lot',
                'global_floating',
                'updated_at',
            ])
            ->map(fn (EaConfiguration $configuration): array => $this->transformAccountSummary($configuration))
            ->values();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'is_admin' => (bool) $user->is_admin,
                'role' => $user->role ?: ((bool) $user->is_admin ? 'admin' : 'user'),
            ],
            'accounts' => $accounts,
        ]);
    }

    public function adminUpdateUserRole(Request $request, int $userId): JsonResponse
    {
        $guard = $this->ensureAdmin($request);
        if ($guard !== null) {
            return $guard;
        }

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:user,manager,admin'],
        ]);

        $user = User::query()->find($userId);
        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        $user->role = $validated['role'];
        $user->is_admin = $validated['role'] === 'admin';
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Role user berhasil diperbarui.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'is_admin' => (bool) $user->is_admin,
            ],
        ]);
    }

    public function createMyAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'pair_symbol' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_\/.\-]+$/'],
            'base_lot' => ['nullable', 'numeric', 'min:0.01'],
        ]);

        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $isAdmin = (bool) ($user->is_admin || $role === 'admin');

        $accountId = trim((string) $validated['account_id']);
        $pairSymbol = strtoupper((string) ($validated['pair_symbol'] ?? 'XAUUSDC'));
        $baseLot = (float) ($validated['base_lot'] ?? 0.01);

        $existing = EaConfiguration::query()
            ->where('account_id', $accountId)
            ->where('pair_symbol', $pairSymbol)
            ->first();

        if ($existing !== null) {
            if ((int) $existing->user_id === (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account MT5 ini sudah terdaftar di user Anda.',
                ], 422);
            }

            if (!$isAdmin) {
                $existing->fill([
                    'user_id' => $user->id,
                    'base_lot' => $baseLot,
                ]);
                $existing->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Account MT5 yang sudah terdaftar berhasil diambil ke user Anda.',
                    'data' => $this->transformConfig($existing->fresh()),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Account MT5 sudah terdaftar milik user lain dan berhasil ditautkan ke dashboard admin.',
                'data' => $this->transformConfig($existing->fresh()),
            ]);
        }

        $configuration = EaConfiguration::query()->create([
            'user_id' => $user->id,
            'account_id' => $accountId,
            'pair_symbol' => $pairSymbol,
            'base_lot' => $baseLot,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account MT5 berhasil ditambahkan.',
            'data' => $this->transformConfig($configuration),
        ], 201);
    }

    public function deleteMyAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'pair_symbol' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_\/\.\-]+$/'],
        ]);

        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $isAdmin = (bool) ($user->is_admin || $role === 'admin');
        $accountId = trim((string) $validated['account_id']);
        $pairSymbol = isset($validated['pair_symbol']) ? strtoupper(trim((string) $validated['pair_symbol'])) : null;

        $query = EaConfiguration::query()->where('account_id', $accountId);
        if ($pairSymbol !== null && $pairSymbol !== '') {
            $query->where('pair_symbol', $pairSymbol);
        }
        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        $configurations = $query->get();
        if ($configurations->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Account MT5 tidak ditemukan atau tidak punya akses.',
            ], 404);
        }

        $hasActiveConfig = $configurations->contains(static function (EaConfiguration $configuration): bool {
            if ((int) ($configuration->current_layers ?? 0) > 0) {
                return true;
            }

            $guardStatus = strtoupper(trim((string) ($configuration->guard_status ?? '')));
            if ($guardStatus === 'LIVE') {
                return true;
            }

            $liveGuardStatus = strtoupper(trim((string) ($configuration->live_guard_status ?? '')));

            return $liveGuardStatus === 'LIVE';
        });
        if ($hasActiveConfig) {
            return response()->json([
                'success' => false,
                'message' => 'Account MT5 masih aktif. Hentikan bot dan kosongkan layer sebelum hapus account.',
            ], 409);
        }

        $deletedCount = 0;
        foreach ($configurations as $configuration) {
            $configuration->delete();
            $deletedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => 'Account MT5 berhasil dihapus.',
            'account_id' => $accountId,
            'deleted_rows' => $deletedCount,
        ]);
    }

    public function listMyAccounts(Request $request): JsonResponse
    {
        $accounts = EaConfiguration::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('account_id')
            ->orderBy('pair_symbol')
            ->get([
                'account_id',
                'pair_symbol',
                'is_online',
                'guard_status',
                'current_layers',
                'current_accumulative_lot',
                'global_floating',
                'updated_at',
            ])
            ->map(fn (EaConfiguration $configuration): array => $this->transformAccountSummary($configuration))
            ->values();

        return response()->json([
            'success' => true,
            'accounts' => $accounts,
        ]);
    }

    public function getMyConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'pair_symbol' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_\/\.\-]+$/'],
        ]);

        $configurationQuery = EaConfiguration::query()
            ->where('user_id', $request->user()->id)
            ->where('account_id', $validated['account_id']);

        if (!empty($validated['pair_symbol'])) {
            $configurationQuery->where('pair_symbol', strtoupper((string) $validated['pair_symbol']));
        }

        $configuration = $configurationQuery->first();

        if ($configuration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Akun MT5 tidak ditemukan untuk user ini.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformConfig($configuration),
        ]);
    }

    public function debugConfigSync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'pair_symbol' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_\/\.\-]+$/'],
        ]);

        $user = $request->user();
        $query = EaConfiguration::query()->where('account_id', $validated['account_id']);
        if (!empty($validated['pair_symbol'])) {
            $query->where('pair_symbol', strtoupper((string) $validated['pair_symbol']));
        }
        if (!$this->isStaff($user)) {
            $query->where('user_id', $user->id);
        }

        $configuration = $query->first();
        if ($configuration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Akun MT5 tidak ditemukan untuk user ini.',
            ], 404);
        }

        $isGridStrategy = (int) ($configuration->active_strategy ?? 0) === 0;
        $effectiveMaxLayers = (int) ($isGridStrategy
            ? ($configuration->grid_max_layers ?: $configuration->max_layers ?: 0)
            : ($configuration->max_layers ?: $configuration->grid_max_layers ?: 0));
        $effectiveMaxAccLot = (float) ($isGridStrategy
            ? ($configuration->grid_max_accumulative_lot ?: $configuration->max_accumulative_lot ?: 0)
            : ($configuration->max_accumulative_lot ?: $configuration->grid_max_accumulative_lot ?: 0));
        $effectiveMaxMartSteps = (int) ($configuration->mart_max_steps ?? 0);

        return response()->json([
            'success' => true,
            'account_id' => $configuration->account_id,
            'db_raw' => [
                'max_layers' => $configuration->max_layers,
                'grid_max_layers' => $configuration->grid_max_layers,
                'max_accumulative_lot' => $configuration->max_accumulative_lot,
                'grid_max_accumulative_lot' => $configuration->grid_max_accumulative_lot,
                'mart_max_steps' => $configuration->mart_max_steps,
            ],
            'ea_effective' => [
                'max_layers' => $effectiveMaxLayers,
                'max_accumulative_lot' => $effectiveMaxAccLot,
                'max_mart_steps' => $effectiveMaxMartSteps,
            ],
            'mismatch_flags' => [
                'max_layers' => (int) ($configuration->max_layers ?? 0) !== (int) ($configuration->grid_max_layers ?? 0),
                'max_accumulative_lot' => (float) ($configuration->max_accumulative_lot ?? 0) !== (float) ($configuration->grid_max_accumulative_lot ?? 0),
            ],
            'ea_payload_sample' => [
                'max_layers' => data_get($this->transformConfig($configuration), 'max_layers'),
                'max_accumulative_lot' => data_get($this->transformConfig($configuration), 'max_accumulative_lot'),
                'max_mart_steps' => data_get($this->transformConfig($configuration), 'max_mart_steps'),
            ],
            'updated_at' => optional($configuration->updated_at)?->toISOString(),
        ]);
    }

    public function updateSetting(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'pair_symbol' => ['sometimes', 'string', 'max:20', 'regex:/^[A-Za-z0-9_\/#\.-]+$/'],
            'timeframe_logic' => ['sometimes', 'integer', 'min:1'],
            'max_layers' => ['sometimes', 'integer', 'min:1'],
            'max_accumulative_lot' => ['sometimes', 'numeric', 'min:0.01'],
            'grid_max_layers' => ['sometimes', 'integer', 'min:1'],
            'grid_max_accumulative_lot' => ['sometimes', 'numeric', 'min:0.01'],
            'base_lot' => ['sometimes', 'numeric', 'min:0.01'],
            'max_spread' => ['sometimes', 'integer', 'min:0'],
            'max_drawdown_pct' => ['sometimes', 'numeric', 'min:0'],
            'max_drawdown_stop_delay' => ['sometimes', 'integer', 'min:0', 'max:3600'],
            'dd_breach_hits_required' => ['sometimes', 'integer', 'min:1', 'max:120'],
            'daily_profit_target' => ['sometimes', 'numeric', 'min:0'],
            'use_martingale' => ['sometimes', 'boolean'],
            'target_tp_percentage' => ['sometimes', 'numeric', 'min:0.1'],
            'grid_tp_mode' => ['sometimes', 'integer', 'in:0,1'],
            'grid_tier1_tp_percent' => ['sometimes', 'numeric', 'min:0'],
            'grid_tier2_tp_percent' => ['sometimes', 'numeric', 'min:0'],
            'grid_tier3_tp_percent' => ['sometimes', 'numeric', 'min:0'],
            'grid_tier4_tp_percent' => ['sometimes', 'numeric', 'min:0'],
            'mart_type' => ['sometimes', 'integer', 'in:0,1'],
            'mart_addition' => ['sometimes', 'numeric', 'min:0'],
            'mart_multiplier' => ['sometimes', 'numeric', 'min:0.01'],
            'grid_mode' => ['sometimes', 'integer', 'in:0,1'],
            'fix_grid_distance' => ['sometimes', 'integer', 'min:1'],
            'atr_period_grid' => ['sometimes', 'integer', 'min:1'],
            'atr_timeframe_grid' => ['sometimes', 'integer', 'min:1'],
            'atr_multiplier' => ['sometimes', 'numeric', 'min:0.1'],
            'min_grid_distance' => ['sometimes', 'integer', 'min:1'],
            'farming_gap' => ['sometimes', 'numeric', 'min:0'],
            'mart_start_layer' => ['sometimes', 'integer', 'min:1'],
            'initial_sl' => ['sometimes', 'numeric', 'min:0'],
            'trail_start' => ['sometimes', 'numeric', 'min:0'],
            'trail_stop' => ['sometimes', 'numeric', 'min:0'],
            'trail_step' => ['sometimes', 'numeric', 'min:0'],
            'use_breakeven' => ['sometimes', 'boolean'],
            'be_distance' => ['sometimes', 'numeric', 'min:0'],
            'be_buffer' => ['sometimes', 'numeric', 'min:0'],
            'start_hour' => ['sometimes', 'integer', 'between:0,23'],
            'end_hour' => ['sometimes', 'integer', 'between:0,23'],
            'always_in_market' => ['sometimes', 'boolean'],
            'instant_reentry' => ['sometimes', 'boolean'],
            'min_confluence_score' => ['nullable', 'integer', 'min:0', 'max:10'],
            'auto_flip' => ['sometimes', 'boolean'],
            'use_pending_guard' => ['sometimes', 'boolean'],
            'use_trend_filter' => ['sometimes', 'boolean'],
            'use_ai_core_sharpening' => ['sometimes', 'boolean'],
            'use_ema_ribbon' => ['sometimes', 'boolean'],
            'use_dmi' => ['sometimes', 'boolean'],
            'use_mkt_struct' => ['sometimes', 'boolean'],
            'use_early_trend' => ['sometimes', 'boolean'],
            'use_sniper_entry' => ['sometimes', 'boolean'],
            'show_indicator_fallback_logs' => ['sometimes', 'boolean'],
            'bb_period' => ['sometimes', 'integer', 'min:1'],
            'bb_deviation' => ['sometimes', 'numeric', 'min:0.1'],
            'rsi_period' => ['sometimes', 'integer', 'min:1'],
            'rsi_buy_level' => ['sometimes', 'numeric', 'between:0,100'],
            'rsi_sell_level' => ['sometimes', 'numeric', 'between:0,100'],
            'adx_period' => ['sometimes', 'integer', 'min:1'],
            'adx_level' => ['sometimes', 'numeric', 'min:0'],
            'adx_bars' => ['sometimes', 'integer', 'min:1'],
            'adx_sideways' => ['sometimes', 'numeric', 'min:0'],
            'ema_period' => ['sometimes', 'integer', 'min:1'],
            'ema_fast' => ['sometimes', 'integer', 'min:1'],
            'ema_slow' => ['sometimes', 'integer', 'min:1'],
            'ema_slope_min' => ['sometimes', 'numeric', 'min:0'],
            'atr_period' => ['sometimes', 'integer', 'min:1'],
            'use_dxy_filter' => ['sometimes', 'boolean'],
            'use_us10y_filter' => ['sometimes', 'boolean'],
            'use_vix_filter' => ['sometimes', 'boolean'],
            'use_oil_filter' => ['sometimes', 'boolean'],
            'use_friday_market_close_window' => ['sometimes', 'boolean'],
            'friday_stop_day' => ['sometimes', Rule::in(['friday', 'saturday'])],
            'friday_stop_wib' => ['sometimes', 'date_format:H:i'],
            'friday_resume_wib' => ['sometimes', 'date_format:H:i'],
            'use_sydney_session' => ['sometimes', 'boolean'],
            'sydney_start_wib' => ['sometimes', 'date_format:H:i'],
            'sydney_end_wib' => ['sometimes', 'date_format:H:i'],
            'use_asia_session' => ['sometimes', 'boolean'],
            'asia_start_wib' => ['sometimes', 'date_format:H:i'],
            'asia_end_wib' => ['sometimes', 'date_format:H:i'],
            'use_europe_session' => ['sometimes', 'boolean'],
            'europe_start_wib' => ['sometimes', 'date_format:H:i'],
            'europe_end_wib' => ['sometimes', 'date_format:H:i'],
            'use_us_session' => ['sometimes', 'boolean'],
            'us_start_wib' => ['sometimes', 'date_format:H:i'],
            'us_end_wib' => ['sometimes', 'date_format:H:i'],
        ]);

        $configuration = $this->resolveUserAccessibleConfiguration($request, (string) $validated['account_id']);

        if ($configuration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Akun MT5 tidak ditemukan atau tidak punya akses.',
            ], 404);
        }

        $licenseStatus = $this->licenseService->getStatusForConfiguration($configuration);
        $licenseEnforcementEnabled = $this->licenseService->isEnforcementEnabled();
        if ($licenseEnforcementEnabled && !(bool) ($licenseStatus['license_active'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Lisensi account tidak aktif. Pengaturan bot dikunci sampai lisensi aktif.',
                'license' => $licenseStatus,
            ], 403);
        }

        if (array_key_exists('pair_symbol', $validated)) {
            $normalizedPair = $this->normalizePairSymbol((string) $validated['pair_symbol']);
            if ($normalizedPair === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pair symbol tidak valid setelah normalisasi.',
                ], 422);
            }

            $validated['pair_symbol'] = $normalizedPair;
        }

        $emaFast = isset($validated['ema_fast']) ? (int) $validated['ema_fast'] : (int) ($configuration->ema_fast ?? 20);
        $emaSlow = isset($validated['ema_slow']) ? (int) $validated['ema_slow'] : (int) ($configuration->ema_slow ?? 50);
        if ($emaFast >= $emaSlow) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: EMA Fast harus lebih kecil dari EMA Slow.',
            ], 422);
        }

        $configuration->fill($validated);
        $configuration->save();

        if (array_key_exists('dd_breach_hits_required', $validated)) {
            $this->setDdBreachHitsRequired($configuration, (int) $validated['dd_breach_hits_required']);
        }

        // Ensure all boolean fields stored consistently (safeguard for always_in_market, instant_reentry, etc.)
        $this->ensureBooleanFieldsCast($configuration);

        return response()->json([
            'success' => true,
            'message' => 'Konfigurasi berhasil diperbarui.',
            'data' => $this->transformConfig($configuration->fresh()),
        ]);
    }

    public function getConfig(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'pair_symbol' => ['nullable', 'string', 'max:20'],
            'account_currency' => ['nullable', 'string', 'max:8'],
        ]);

        $apiKeyError = $this->ensureEaApiKey($request);
        if ($apiKeyError !== null) {
            return $apiKeyError;
        }

        $configuration = $this->resolveOrCreateEaConfiguration($request, $validated['account_id']);

        if ($configuration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Akun MT5 tidak ditemukan dan auto-register belum bisa menentukan owner user.',
            ], 404);
        }

        return response()->json($this->transformConfig($configuration));
    }

    public function reportStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'pair_symbol' => ['nullable', 'string', 'max:20'],
            'account_currency' => ['nullable', 'string', 'max:8'],
            'current_layers' => ['nullable', 'integer', 'min:0'],
            'current_accumulative_lot' => ['nullable', 'numeric', 'min:0'],
            'global_floating' => ['nullable', 'numeric'],
            'guard_status' => ['nullable', 'string', 'max:32'],
            'balance' => ['nullable', 'numeric'],
            'equity' => ['nullable', 'numeric'],
            'wins' => ['nullable', 'integer', 'min:0'],
            'losses' => ['nullable', 'integer', 'min:0'],
            'realized_profit' => ['nullable', 'numeric'],
            'daily_profit' => ['nullable', 'numeric'],
            'weekly_profit' => ['nullable', 'numeric'],
            'monthly_profit' => ['nullable', 'numeric'],
        ]);

        $apiKeyError = $this->ensureEaApiKey($request);
        if ($apiKeyError !== null) {
            return $apiKeyError;
        }

        $configuration = $this->resolveOrCreateEaConfiguration($request, $validated['account_id']);

        if ($configuration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Akun MT5 tidak ditemukan dan auto-register belum bisa menentukan owner user.',
            ], 404);
        }

        $licenseStatus = $this->licenseService->getStatusForConfiguration($configuration);

        $accountCurrency = strtoupper(trim((string) ($validated['account_currency'] ?? $request->input('account_currency', $configuration->account_currency ?? 'USD'))));
        if ($accountCurrency === '') {
            $accountCurrency = 'USD';
        }

        $openPositions = $this->readTelemetryArray($request, ['open_positions', 'positions', 'positions_data']);
        $pendingOrders = $this->readTelemetryArray($request, ['pending_orders', 'orders_pending', 'pending_orders_list']);
        $hasOpenPositionsPayload = $request->exists('open_positions') || $request->exists('positions') || $request->exists('positions_data');
        $closedTrades = $this->readTelemetryArray($request, [
            'closed_trades',
            'history_trades',
            'trade_history',
            'closedTrades',
            'historyTrades',
            'tradeHistory',
            'history',
            'closed_positions',
        ]);

        $balance = $this->readTelemetryFloat($request, ['balance', 'account_balance']);
        $equity = $this->readTelemetryFloat($request, ['equity', 'account_equity']);
        $derivedFloating = ($balance !== null && $equity !== null) ? ($equity - $balance) : null;

        $hasExplicitCurrentLayers = $request->has('current_layers');
        $computedLayers = $this->readTelemetryInt($request, ['current_layers', 'open_positions', 'positions_total']);
        if ($computedLayers === null) {
            $computedLayers = count($openPositions);
        } elseif (!$hasExplicitCurrentLayers && $computedLayers <= 0 && $openPositions !== []) {
            $computedLayers = count($openPositions);
        }

        $hasExplicitCurrentAccLot = $request->has('current_accumulative_lot');
        $computedAccLot = $this->readTelemetryFloat($request, ['current_accumulative_lot', 'accumulative_lot', 'total_lot']);
        if ($computedAccLot === null) {
            $computedAccLot = $this->sumLots($openPositions);
        } elseif (!$hasExplicitCurrentAccLot && $computedAccLot <= 0.0 && $openPositions !== []) {
            $computedAccLot = $this->sumLots($openPositions);
        }

        $licenseRuntime = $this->licenseService->getRuntimeStatusForConfiguration(
            $configuration,
            (int) $computedLayers,
            (float) $computedAccLot
        );

        $computedFloating = $this->readTelemetryFloat($request, ['global_floating', 'floating', 'floating_pnl', 'live_floating_pnl']);
        if ($computedFloating === null) {
            if ($derivedFloating !== null) {
                $computedFloating = $derivedFloating;
            } elseif ($openPositions !== []) {
                $computedFloating = $this->sumFloating($openPositions);
            } elseif ((int) $computedLayers <= 0) {
                $computedFloating = 0.0;
            } else {
                $computedFloating = (float) ($configuration->global_floating ?? 0.0);
            }
        }

        $todayPnlTelemetry = $this->readTelemetryFloat($request, ['today_pnl', 'daily_profit']);
        if ($todayPnlTelemetry === null) {
            $todayPnlTelemetry = (float) ($configuration->today_pnl ?? 0.0);
        }

        $drawdownTelemetry = $this->readTelemetryFloat($request, ['drawdown_pct']);
        $shouldDeriveDrawdown = $drawdownTelemetry === null;
        if (!$shouldDeriveDrawdown && $balance !== null && $balance > 0) {
            $incomingDd = (float) $drawdownTelemetry;
            $floatingSignal = abs((float) $computedFloating) > 0.0000001;
            $equitySignal = $equity !== null && abs((float) $equity - (float) $balance) > 0.0000001;
            $incomingDdZeroLike = abs($incomingDd) <= 0.0000001;

            // Some EA builds always send drawdown_pct=0; derive it when other telemetry clearly shows exposure.
            if ($incomingDdZeroLike && ($floatingSignal || $equitySignal)) {
                $shouldDeriveDrawdown = true;
            }
        }

        if ($shouldDeriveDrawdown) {
            $drawdownTelemetry = 0.0;
            if ($balance !== null && $balance > 0 && $computedFloating < 0) {
                $drawdownTelemetry = -abs(($computedFloating / $balance) * 100.0);
            } elseif ($balance !== null && $balance > 0 && $equity !== null && $equity > 0 && $equity < $balance) {
                $drawdownTelemetry = -(($balance - $equity) / $balance) * 100.0;
            } elseif ($balance !== null && $balance > 0 && $equity !== null && $equity > $balance) {
                $drawdownTelemetry = (($equity - $balance) / $balance) * 100.0;
            }
        }

        $storedBalance = ($balance !== null)
            ? (float) $balance
            : (float) ($configuration->current_balance ?? 0.0);
        $storedEquity = ($equity !== null)
            ? (float) $equity
            : (float) ($configuration->current_equity ?? 0.0);

        $reportedGuardStatus = $this->readTelemetryString($request, ['guard_status', 'status']);
        if ($reportedGuardStatus === null || trim($reportedGuardStatus) === '') {
            $reportedGuardStatus = ((bool) $request->input('remote_paused', false)) ? 'PAUSED' : ($configuration->live_guard_status ?: 'LIVE');
        }

        $commandedGuardStatus = (string) ($configuration->guard_status ?: 'LIVE');

        $ddResetBypassKey = 'dd_reset_bypass_user_' . $configuration->user_id . '_account_' . $configuration->account_id;
        $ddResetBypassUntilIso = Cache::get($ddResetBypassKey);
        $ddResetBypassActive = false;
        if (is_string($ddResetBypassUntilIso) && trim($ddResetBypassUntilIso) !== '') {
            try {
                $ddResetBypassActive = Carbon::now()->lessThanOrEqualTo(Carbon::parse($ddResetBypassUntilIso));
            } catch (\Throwable) {
                $ddResetBypassActive = false;
            }
        }

        if ($ddResetBypassActive && $commandedGuardStatus === 'DD_STOP') {
            $commandedGuardStatus = 'LIVE';
        }

        // Auto-stop when max drawdown is breached (debounced to avoid short-lived spike lock).
        $maxDdPct = (float) ($configuration->max_drawdown_pct ?? 0);
        $ddBreachKey = 'dd_breach_hits_user_' . $configuration->user_id . '_account_' . $configuration->account_id;
        if (!$ddResetBypassActive && $maxDdPct > 0 && $balance > 0) {
            $currentDdPct = 0.0;
            if ($computedFloating < 0) {
                $currentDdPct = abs($computedFloating / $balance) * 100.0;
            } elseif ($equity > 0 && $equity < $balance) {
                $currentDdPct = (($balance - $equity) / $balance) * 100.0;
            }
            if ($currentDdPct >= $maxDdPct && $commandedGuardStatus === 'LIVE') {
                $hits = (int) Cache::increment($ddBreachKey);
                Cache::put($ddBreachKey, $hits, now()->addMinutes(10));

                // Require sustained breach before DD_STOP to avoid transient-spike lock.
                $requiredHits = $this->getDdBreachHitsRequired($configuration);
                if ($hits >= $requiredHits) {
                    $commandedGuardStatus = 'DD_STOP';
                    Cache::forget($ddBreachKey);
                    \Log::warning('Max drawdown triggered', [
                        'account_id' => $configuration->account_id,
                        'max_dd_pct' => $maxDdPct,
                        'current_dd_pct' => round($currentDdPct, 4),
                        'breach_hits' => $hits,
                        'required_hits' => $requiredHits,
                    ]);
                }
            } else {
                Cache::forget($ddBreachKey);
            }
        } else {
            Cache::forget($ddBreachKey);
        }

        $licenseEnforcementEnabled = (bool) ($licenseRuntime['license_enforcement_enabled'] ?? $this->licenseService->isEnforcementEnabled());
        $licenseGracePeriod = (bool) ($licenseRuntime['license_grace_period'] ?? false);
        $licenseInactive = $licenseEnforcementEnabled && !(bool) ($licenseRuntime['license_active'] ?? false) && !$licenseGracePeriod;
        if ($licenseInactive) {
            $hasActiveExposure = max(0, (int) $computedLayers) > 0 || max(0.0, (float) $computedAccLot) > 0.0000001;
            if ($hasActiveExposure) {
                // Keep runtime guard LIVE while positions are still open so EA can finish
                // the active strategy cycle and close naturally.
                if (strtoupper($commandedGuardStatus) !== 'DD_STOP') {
                    $commandedGuardStatus = 'LIVE';
                }
            } else {
                $commandedGuardStatus = 'PAUSED';
                $reportedGuardStatus = 'PAUSED';
            }
        } elseif ($licenseGracePeriod && strtoupper($commandedGuardStatus) !== 'DD_STOP') {
            $commandedGuardStatus = 'LIVE';
        }

        $tradingEnabled = strtoupper($commandedGuardStatus) === 'LIVE';

        $manualLayerReset = $this->persistClosedTrades($configuration, $closedTrades);
        if ($manualLayerReset) {
            $computedLayers = 0;
            $computedAccLot = 0.0;
        }

        $configPayload = [
            'current_layers' => max(0, (int) $computedLayers),
            'current_accumulative_lot' => max(0.0, (float) $computedAccLot),
            'global_floating' => (float) $computedFloating,
            'current_balance' => $storedBalance,
            'current_equity' => $storedEquity,
            'today_pnl' => (float) $todayPnlTelemetry,
            'drawdown_pct' => (float) $drawdownTelemetry,
            'guard_status' => (string) $commandedGuardStatus,
            'live_guard_status' => (string) $reportedGuardStatus,
            'is_online' => true,
            'updated_at' => Carbon::now(),
        ];

        if ($this->hasEaConfigCurrencyColumn()) {
            $configPayload['account_currency'] = $accountCurrency;
        }

        $configuration->update($configPayload);

        $latestMetricsReport = EaStatusReport::query()
            ->where('ea_configuration_id', $configuration->id)
            ->latest('id')
            ->first();

        $winsValue = array_key_exists('wins', $validated)
            ? (int) $validated['wins']
            : (int) ($latestMetricsReport?->wins ?? 0);
        $lossesValue = array_key_exists('losses', $validated)
            ? (int) $validated['losses']
            : (int) ($latestMetricsReport?->losses ?? 0);
        $realizedProfitValue = array_key_exists('realized_profit', $validated)
            ? (float) $validated['realized_profit']
            : (float) ($latestMetricsReport?->realized_profit ?? 0.0);
        $dailyProfitValue = array_key_exists('daily_profit', $validated)
            ? (float) $validated['daily_profit']
            : (float) ($latestMetricsReport?->daily_profit ?? 0.0);
        $weeklyProfitValue = array_key_exists('weekly_profit', $validated)
            ? (float) $validated['weekly_profit']
            : (float) ($latestMetricsReport?->weekly_profit ?? 0.0);
        $monthlyProfitValue = array_key_exists('monthly_profit', $validated)
            ? (float) $validated['monthly_profit']
            : (float) ($latestMetricsReport?->monthly_profit ?? 0.0);

        $reportPayload = [
            'user_id' => $configuration->user_id,
            'ea_configuration_id' => $configuration->id,
            'account_id' => $configuration->account_id,
            'current_layers' => max(0, (int) $computedLayers),
            'current_accumulative_lot' => max(0.0, (float) $computedAccLot),
            'global_floating' => (float) $computedFloating,
            'guard_status' => (string) $reportedGuardStatus,
            'balance' => $balance,
            'equity' => $equity,
            'open_positions' => $this->compactTelemetryRows($openPositions, 40),
            'pending_orders' => $this->compactTelemetryRows($pendingOrders, 40),
            'closed_trades' => $this->compactClosedTradesForReport($closedTrades, 25),
            'wins' => $winsValue,
            'losses' => $lossesValue,
            'realized_profit' => $realizedProfitValue,
            'daily_profit' => $dailyProfitValue,
            'weekly_profit' => $weeklyProfitValue,
            'monthly_profit' => $monthlyProfitValue,
        ];

        if ($this->hasEaReportCurrencyColumn()) {
            $reportPayload['account_currency'] = $accountCurrency;
        }

        if ($this->shouldWriteStatusReport(
            $configuration,
            (int) $reportPayload['current_layers'],
            (float) $reportPayload['current_accumulative_lot'],
            (float) $reportPayload['global_floating'],
            (string) $reportPayload['guard_status'],
            (array) ($reportPayload['open_positions'] ?? []),
            (array) ($reportPayload['pending_orders'] ?? []),
            (int) ($reportPayload['wins'] ?? 0),
            (int) ($reportPayload['losses'] ?? 0),
            (float) ($reportPayload['realized_profit'] ?? 0.0),
            (float) ($reportPayload['daily_profit'] ?? 0.0),
            (float) ($reportPayload['weekly_profit'] ?? 0.0),
            (float) ($reportPayload['monthly_profit'] ?? 0.0)
        )) {
            EaStatusReport::query()->create($reportPayload);
            $this->pruneStatusReportsIfNeeded($configuration);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status live berhasil diperbarui.',
            'data' => [
                'account_id' => $configuration->account_id,
                'current_layers' => max(0, (int) $computedLayers),
                'current_accumulative_lot' => max(0.0, (float) $computedAccLot),
                'global_floating' => (float) $computedFloating,
                'guard_status' => (string) $commandedGuardStatus,
                'live_guard_status' => (string) $reportedGuardStatus,
                'trading_enabled' => $tradingEnabled,
                'license_status' => (string) ($licenseRuntime['license_status'] ?? $licenseStatus['license_status'] ?? 'unlicensed'),
                'is_active' => (int) ($licenseRuntime['is_active'] ?? 0),
                'is_trading_active' => (int) ($licenseRuntime['is_trading_active'] ?? 0),
                'allow_open_new_cycle' => (bool) ($licenseRuntime['license_can_start_new_cycle'] ?? false),
                'allow_manage_existing_cycle' => (bool) ($licenseRuntime['license_can_manage_existing_cycle'] ?? false),
                'license' => array_merge($licenseStatus, $licenseRuntime),
                'manual_layer_reset' => $manualLayerReset,
                'account_currency' => $accountCurrency,
                'balance' => $balance,
                'equity' => $equity,
            ],
        ]);
    }

    public function myMonitoringLive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'calc_debug' => ['nullable', 'boolean'],
        ]);
        $includeCalcDebug = (bool) ($validated['calc_debug'] ?? false);

        $configuration = $this->resolveUserAccessibleConfiguration($request, (string) $validated['account_id']);

        if ($configuration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Akun MT5 tidak ditemukan untuk user ini.',
            ], 404);
        }

        $latest = EaStatusReport::query()
            ->where('ea_configuration_id', $configuration->id)
            ->latest('id')
            ->first();

        $latestWithFunds = EaStatusReport::query()
            ->where('ea_configuration_id', $configuration->id)
            ->where(function ($query): void {
                $query->whereNotNull('balance')->orWhereNotNull('equity');
            })
            ->latest('id')
            ->first();

        $latestWithMetrics = EaStatusReport::query()
            ->where('ea_configuration_id', $configuration->id)
            ->latest('id')
            ->first();

        $latestReportHeartbeatAt = null;
        if ($latest !== null) {
            $latestReportHeartbeatRaw = $latest->updated_at ?? $latest->created_at;
            if ($latestReportHeartbeatRaw !== null) {
                try {
                    $latestReportHeartbeatAt = Carbon::parse((string) $latestReportHeartbeatRaw);
                } catch (\Throwable) {
                    $latestReportHeartbeatAt = null;
                }
            }
        }

        $freshWindowSec = max(20, (int) config('services.ea.online_fresh_window_sec', 30));
        $freshThreshold = Carbon::now()->subSeconds($freshWindowSec);
        $latestReportIsFresh = $latestReportHeartbeatAt !== null
            && $latestReportHeartbeatAt->greaterThanOrEqualTo($freshThreshold);

        $balance = (float) ($latest?->balance ?? $latestWithFunds?->balance ?? 0.0);
        $equity = (float) ($latest?->equity ?? $latestWithFunds?->equity ?? 0.0);
        $openPositions = $latestReportIsFresh && is_array($latest?->open_positions)
            ? $latest->open_positions
            : [];

        $hasMagicMetadata = false;
        $botOpenPositions = [];
        foreach ($openPositions as $positionRow) {
            if (!is_array($positionRow)) {
                continue;
            }

            if (array_key_exists('magic', $positionRow)) {
                $hasMagicMetadata = true;
                $magicValue = $positionRow['magic'];
                $magicNumber = is_numeric($magicValue) ? (float) $magicValue : 0.0;
                if (abs($magicNumber) > 0.0000001) {
                    $botOpenPositions[] = $positionRow;
                }
            }
        }

        $botLayers = $hasMagicMetadata
            ? count($botOpenPositions)
            : ($latestReportIsFresh ? (int) ($configuration->current_layers ?? 0) : 0);
        $botAccLot = $hasMagicMetadata
            ? $this->sumLots($botOpenPositions)
            : ($latestReportIsFresh ? (float) ($configuration->current_accumulative_lot ?? 0.0) : 0.0);
        $botFloating = $hasMagicMetadata
            ? $this->sumFloating($botOpenPositions)
            : ($latestReportIsFresh ? (float) ($configuration->global_floating ?? 0.0) : 0.0);

        $liveFloatingPnl = $hasMagicMetadata
            ? (float) $botFloating
            : ($openPositions !== []
                ? $this->sumFloating($openPositions)
                : ($latestReportIsFresh ? (float) ($configuration->global_floating ?? 0.0) : 0.0));

        $floatingLoss = 0.0;
        $lossRows = $hasMagicMetadata ? $botOpenPositions : $openPositions;
        if ($lossRows !== []) {
            foreach ($lossRows as $position) {
                if (!is_array($position)) {
                    continue;
                }

                $pnl = (float) ($position['floating'] ?? ((float) ($position['profit'] ?? 0.0) + (float) ($position['swap'] ?? 0.0)));
                if ($pnl < 0.0) {
                    $floatingLoss += $pnl;
                }
            }
        } elseif ($liveFloatingPnl < 0.0) {
            $floatingLoss = $liveFloatingPnl;
        }

        $drawdownPct = 0.0;
        $drawdownMethod = 'none';
        if ($balance > 0.0) {
            if ($floatingLoss < 0.0) {
                $drawdownPct = -abs(($floatingLoss / $balance) * 100.0);
                $drawdownMethod = 'floating_loss_over_balance';
            } elseif ($equity > $balance) {
                $drawdownPct = (($equity - $balance) / $balance) * 100.0;
                $drawdownMethod = 'equity_minus_balance_over_balance';
            } elseif ($equity > 0.0 && $equity < $balance) {
                $drawdownPct = -(($balance - $equity) / $balance) * 100.0;
                $drawdownMethod = 'balance_minus_equity_over_balance';
            } elseif ($liveFloatingPnl > 0.0) {
                $drawdownPct = ($liveFloatingPnl / $balance) * 100.0;
                $drawdownMethod = 'floating_gain_over_balance';
            } elseif ($liveFloatingPnl < 0.0) {
                $drawdownPct = -abs(($liveFloatingPnl / $balance) * 100.0);
                $drawdownMethod = 'floating_loss_over_balance_fallback';
            }
        }

        if (abs($drawdownPct) < 0.0000001) {
            $storedDrawdown = (float) ($configuration->drawdown_pct ?? 0.0);
            if (abs($storedDrawdown) > 0.0000001) {
                $drawdownPct = $storedDrawdown;
                $drawdownMethod = 'stored_drawdown_fallback';
            }
        }

        $wins = (int) ($latest?->wins ?? 0);
        $losses = (int) ($latest?->losses ?? 0);
        $totalTrades = $wins + $losses;
        $winRatePercent = $totalTrades > 0 ? round(($wins / $totalTrades) * 100, 2) : 0.0;

        $freshHeartbeatAt = $configuration->updated_at instanceof Carbon ? $configuration->updated_at->copy() : null;
        if ($latestReportHeartbeatAt !== null && ($freshHeartbeatAt === null || $latestReportHeartbeatAt->greaterThan($freshHeartbeatAt))) {
            $freshHeartbeatAt = $latestReportHeartbeatAt;
        }

        $response = [
            'success' => true,
            'account_id' => $configuration->account_id,
            'is_online' => $this->isRecentlyOnline($configuration),
            'account_currency' => strtoupper((string) ($configuration->account_currency ?? 'USD')),
            'current_layers' => max(0, (int) $botLayers),
            'current_accumulative_lot' => max(0.0, (float) $botAccLot),
            'global_floating' => (float) $botFloating,
            'live_floating_pnl' => (float) $liveFloatingPnl,
            'drawdown_pct' => round($drawdownPct, 4),
            'guard_status' => (string) ($configuration->guard_status ?? 'N/A'),
            'updated_at' => $freshHeartbeatAt?->toISOString(),
            'balance' => $balance,
            'equity' => $equity,
            'wins' => $wins,
            'losses' => $losses,
            'win_rate_percent' => $winRatePercent,
            'realized_profit' => (float) ($latestWithMetrics?->realized_profit ?? 0),
            'daily_profit' => (float) ($latestWithMetrics?->daily_profit ?? 0),
            'weekly_profit' => (float) ($latestWithMetrics?->weekly_profit ?? 0),
            'monthly_profit' => (float) ($latestWithMetrics?->monthly_profit ?? 0),
            'open_positions' => $openPositions,
            'pending_orders' => $latestReportIsFresh ? ($latest?->pending_orders ?? []) : [],
            'closed_trades_latest' => $latestReportIsFresh ? ($latest?->closed_trades ?? []) : [],
            'analysis' => $this->readSignalSnapshot($configuration),
        ];

        $response = array_merge(
            $response,
            $this->licenseService->getRuntimeStatusForConfiguration(
                $configuration,
                max(0, (int) $botLayers),
                max(0.0, (float) $botAccLot)
            )
        );

        if ($includeCalcDebug) {
            $response['calc_debug'] = [
                'source' => 'monitoring',
                'drawdown_method' => $drawdownMethod,
                'balance' => $balance,
                'equity' => $equity,
                'floating_loss_total' => $floatingLoss,
                'open_positions_count' => count($openPositions),
                'drawdown_pct_raw' => $drawdownPct,
                'drawdown_pct_rounded' => round($drawdownPct, 4),
                'formula' => 'drawdown_signed = negative on loss, positive on gain; based on floating/balance, fallback equity-vs-balance',
            ];
        }

        return response()->json($response);
    }

    public function myReportLive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'limit' => ['nullable', 'integer', 'min:5', 'max:500'],
            'page' => ['nullable', 'integer', 'min:1', 'max:500'],
            'period' => ['nullable', 'string', 'in:all,today,yesterday,this_week,last_week,this_month,last_30_days'],
            'calc_debug' => ['nullable', 'boolean'],
        ]);
        $includeCalcDebug = (bool) ($validated['calc_debug'] ?? false);

        $configuration = $this->resolveUserAccessibleConfiguration($request, (string) $validated['account_id']);

        if ($configuration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Akun MT5 tidak ditemukan untuk user ini.',
            ], 404);
        }

        $latestMetricsReport = null;
        try {
            $latestMetricsReport = EaStatusReport::query()
                ->where('ea_configuration_id', $configuration->id)
                ->latest('id')
                ->first();
        } catch (\Throwable) {
            $latestMetricsReport = null;
        }
        $pickProfitMetric = static function (?float $metricValue, float $calculatedValue): float {
            if ($metricValue === null) {
                return $calculatedValue;
            }

            if (abs($metricValue) < 0.0000001 && abs($calculatedValue) > 0.0000001) {
                return $calculatedValue;
            }

            return $metricValue;
        };

        $limit = (int) ($validated['limit'] ?? 50);
        $page = (int) ($validated['page'] ?? 1);
        $period = (string) ($validated['period'] ?? 'all');
        $nowJakarta = Carbon::now('Asia/Jakarta');
        $periodStart = null;
        $periodEnd = null;
        switch ($period) {
            case 'today':
                $periodStart = $nowJakarta->copy()->startOfDay();
                $periodEnd = $nowJakarta->copy()->endOfDay();
                break;
            case 'yesterday':
                $periodStart = $nowJakarta->copy()->subDay()->startOfDay();
                $periodEnd = $nowJakarta->copy()->subDay()->endOfDay();
                break;
            case 'this_week':
                $periodStart = $nowJakarta->copy()->startOfWeek(Carbon::MONDAY);
                $periodEnd = $nowJakarta->copy()->endOfWeek(Carbon::SUNDAY);
                break;
            case 'last_week':
                $periodStart = $nowJakarta->copy()->subWeek()->startOfWeek(Carbon::MONDAY);
                $periodEnd = $nowJakarta->copy()->subWeek()->endOfWeek(Carbon::SUNDAY);
                break;
            case 'this_month':
                $periodStart = $nowJakarta->copy()->startOfMonth();
                $periodEnd = $nowJakarta->copy()->endOfMonth();
                break;
            case 'last_30_days':
                $periodStart = $nowJakarta->copy()->subDays(29)->startOfDay();
                $periodEnd = $nowJakarta->copy()->endOfDay();
                break;
            default:
                $period = 'all';
                break;
        }
        $reportsQuery = EaStatusReport::query()
            ->where('ea_configuration_id', $configuration->id)
            ->latest('id');
        $reportsStatsQuery = clone $reportsQuery;

        $resetKey = 'wr_reset_ts_account_' . $configuration->account_id;
        $resetAtIso = Cache::get($resetKey);
        if (is_string($resetAtIso) && trim($resetAtIso) !== '') {
            try {
                $reportsStatsQuery->where('created_at', '>=', Carbon::parse($resetAtIso));
            } catch (\Throwable) {
            }
        }

        $persistentHistoryQuery = EaClosedTrade::query()
            ->where('ea_configuration_id', $configuration->id);
        $persistentStatsQuery = clone $persistentHistoryQuery;

        if ($periodStart instanceof Carbon) {
            $persistentHistoryQuery->where(function ($query) use ($periodStart, $periodEnd): void {
                $query->where(function ($sub) use ($periodStart, $periodEnd): void {
                    $sub->whereNotNull('closed_at')
                        ->where('closed_at', '>=', $periodStart);
                    if ($periodEnd instanceof Carbon) {
                        $sub->where('closed_at', '<=', $periodEnd);
                    }
                })->orWhere(function ($sub) use ($periodStart, $periodEnd): void {
                    $sub->whereNull('closed_at')
                        ->where('created_at', '>=', $periodStart);
                    if ($periodEnd instanceof Carbon) {
                        $sub->where('created_at', '<=', $periodEnd);
                    }
                });
            });

        }

        if (is_string($resetAtIso) && trim($resetAtIso) !== '') {
            try {
                $resetAt = Carbon::parse($resetAtIso);
                $persistentStatsQuery->where(function ($query) use ($resetAt): void {
                    $query->where('closed_at', '>=', $resetAt)
                        ->orWhere(function ($sub) use ($resetAt): void {
                            $sub->whereNull('closed_at')->where('created_at', '>=', $resetAt);
                        });
                });
            } catch (\Throwable) {
            }
        }

        $persistentTotal = (clone $persistentHistoryQuery)->count();
        if ($persistentTotal < 1000) {
            $seedStateKey = 'closed_trades_seed_last_report_account_' . $configuration->account_id;
            $lastSeededReportId = (int) Cache::get($seedStateKey, 0);

            // If cache claims seeding is done but table is still empty, the previous seeding
            // timed out or failed — reset so it retries from scratch.
            if ($lastSeededReportId > 0 && $persistentTotal === 0) {
                $lastSeededReportId = 0;
                Cache::forget($seedStateKey);
            }

            $seedQuery = EaStatusReport::query()
                ->where('ea_configuration_id', $configuration->id)
                ->latest('id');

            if ($lastSeededReportId > 0) {
                $seedQuery->where('id', '>', $lastSeededReportId);
            }

            // Fetch a small batch per call (EA already sends all recent trades in each report,
            // so 10 reports is enough; incremental cache advances on each tick).
            $seedReports = $seedQuery
                ->limit(10)
                ->get(['id', 'closed_trades']);

            $maxSeededReportId = $lastSeededReportId;
            $bulkSeedRows = [];
            $nowStr = now()->toDateTimeString();

            foreach ($seedReports as $seedReport) {
                $maxSeededReportId = max($maxSeededReportId, (int) ($seedReport->id ?? 0));
                $trades = (array) ($seedReport->closed_trades ?? []);

                // Handle wrapped payloads
                foreach (['trades', 'data', 'items', 'history', 'closed_trades'] as $key) {
                    if (isset($trades[$key]) && is_array($trades[$key])) {
                        $trades = $trades[$key];
                        break;
                    }
                }

                foreach ($trades as $trade) {
                    if (!is_array($trade)) {
                        continue;
                    }

                    $ticketText = trim((string) ($trade['ticket'] ?? $trade['order'] ?? ''));
                    if ($ticketText === '') {
                        $ticketText = 'hash:' . md5(json_encode([
                            $trade['symbol'] ?? '',
                            $trade['type'] ?? '',
                            $trade['close_time'] ?? '',
                            $trade['profit'] ?? 0,
                            $trade['lot'] ?? $trade['volume'] ?? 0,
                        ]));
                    }

                    if (isset($bulkSeedRows[$ticketText])) {
                        continue; // dedup within batch
                    }

                    $openTimeText = trim((string) ($trade['open_time'] ?? ''));
                    $closeTimeText = trim((string) ($trade['close_time'] ?? ''));
                    $openAt = $this->parseTelemetryTimestamp($openTimeText);
                    $closedAt = $this->parseTelemetryTimestamp($closeTimeText);

                    $bulkSeedRows[$ticketText] = [
                        'user_id' => (int) $configuration->user_id,
                        'ea_configuration_id' => (int) $configuration->id,
                        'account_id' => $configuration->account_id,
                        'ticket' => $ticketText,
                        'symbol' => strtoupper((string) ($trade['symbol'] ?? $configuration->pair_symbol ?? '')),
                        'type' => strtoupper((string) ($trade['type'] ?? '')),
                        'lot' => (float) ($trade['lot'] ?? $trade['volume'] ?? 0),
                        'open_price' => (float) ($trade['open_price'] ?? 0),
                        'close_price' => (float) ($trade['close_price'] ?? 0),
                        'profit' => (float) ($trade['profit'] ?? 0),
                        'swap' => (float) ($trade['swap'] ?? 0),
                        'commission' => (float) ($trade['commission'] ?? 0),
                        'open_time_text' => $openTimeText !== '' ? $openTimeText : null,
                        'close_time_text' => $closeTimeText !== '' ? $closeTimeText : null,
                        'open_at' => $openAt?->toDateTimeString(),
                        'closed_at' => $closedAt?->toDateTimeString(),
                        'created_at' => $nowStr,
                        'updated_at' => $nowStr,
                    ];
                }
            }

            $insertSucceeded = true;
            if (!empty($bulkSeedRows)) {
                try {
                    EaClosedTrade::query()->insertOrIgnore(array_values($bulkSeedRows));
                } catch (\Illuminate\Database\QueryException $e) {
                    // DB user lacks INSERT privilege — skip seeding, serve from EaStatusReport directly
                    $insertSucceeded = false;
                    \Illuminate\Support\Facades\Log::warning('[Report] insertOrIgnore ea_closed_trades denied: ' . $e->getCode());
                }
            }

            if ($insertSucceeded && $maxSeededReportId > $lastSeededReportId) {
                Cache::put($seedStateKey, $maxSeededReportId, now()->addDays(7));
            }

            $persistentTotal = (clone $persistentHistoryQuery)->count();
        }

        if ($persistentTotal > 0) {
            $lastPage = max(1, (int) ceil($persistentTotal / max($limit, 1)));
            $page = min($page, $lastPage);

            $historyRows = (clone $persistentHistoryQuery)
                ->orderByDesc('closed_at')
                ->orderByDesc('id')
                ->forPage($page, $limit)
                ->get();

            $history = $historyRows->map(function (EaClosedTrade $trade): array {
                return [
                    'ticket' => (string) $trade->ticket,
                    'symbol' => (string) ($trade->symbol ?? '-'),
                    'type' => (string) ($trade->type ?? '-'),
                    'lot' => (float) ($trade->lot ?? 0),
                    'open_price' => (float) ($trade->open_price ?? 0),
                    'close_price' => (float) ($trade->close_price ?? 0),
                    'profit' => (float) ($trade->profit ?? 0),
                    'swap' => (float) ($trade->swap ?? 0),
                    'commission' => (float) ($trade->commission ?? 0),
                    'open_time' => (string) ($trade->open_time_text ?? optional($trade->open_at)?->toDateTimeString() ?? ''),
                    'close_time' => (string) ($trade->close_time_text ?? optional($trade->closed_at)?->toDateTimeString() ?? ''),
                ];
            })->values();

            $statsRows = (clone $persistentStatsQuery)
                ->get(['profit', 'swap', 'commission', 'closed_at', 'close_time_text', 'created_at']);

            $wins = 0;
            $losses = 0;
            $realized = 0.0;
            $dailyCalculated = 0.0;
            $weeklyCalculated = 0.0;
            $monthlyCalculated = 0.0;
            $now = Carbon::now('Asia/Jakarta');
            $startOfDay = $now->copy()->startOfDay();
            $startOfWeek = $now->copy()->startOfWeek(Carbon::MONDAY);
            $startOfMonth = $now->copy()->startOfMonth();

            foreach ($statsRows as $trade) {
                $netProfit = (float) ($trade->profit ?? 0) + (float) ($trade->swap ?? 0) + (float) ($trade->commission ?? 0);
                $realized += $netProfit;
                if ($netProfit > 0) {
                    $wins++;
                } elseif ($netProfit < 0) {
                    $losses++;
                }

                $closedAt = $trade->closed_at instanceof Carbon ? $trade->closed_at : null;
                if ($closedAt === null) {
                    $closeTimeText = trim((string) ($trade->close_time_text ?? ''));
                    if ($closeTimeText !== '') {
                        $closedAt = $this->parseTelemetryTimestamp($closeTimeText);
                    }
                }
                if ($closedAt === null && $trade->created_at instanceof Carbon) {
                    // Last fallback only when trade close time is truly unavailable.
                    $closedAt = $trade->created_at;
                }
                if ($closedAt === null) {
                    continue;
                }

                if ($closedAt->greaterThanOrEqualTo($startOfDay)) {
                    $dailyCalculated += $netProfit;
                }
                if ($closedAt->greaterThanOrEqualTo($startOfWeek)) {
                    $weeklyCalculated += $netProfit;
                }
                if ($closedAt->greaterThanOrEqualTo($startOfMonth)) {
                    $monthlyCalculated += $netProfit;
                }
            }

            $total = $wins + $losses;
            $winRate = $total > 0 ? round(($wins / $total) * 100, 2) : 0.0;

            $response = [
                'success' => true,
                'account_id' => $configuration->account_id,
                'account_currency' => strtoupper((string) ($configuration->account_currency ?? 'USD')),
                'wr' => [
                    'wins' => $wins,
                    'losses' => $losses,
                    'total' => $total,
                    'win_rate_percent' => $winRate,
                    'reset_at' => $resetAtIso,
                ],
                'profit' => [
                    'daily' => $pickProfitMetric(
                        $latestMetricsReport?->daily_profit !== null ? (float) $latestMetricsReport->daily_profit : null,
                        $dailyCalculated
                    ),
                    'weekly' => $pickProfitMetric(
                        $latestMetricsReport?->weekly_profit !== null ? (float) $latestMetricsReport->weekly_profit : null,
                        $weeklyCalculated
                    ),
                    'monthly' => $pickProfitMetric(
                        $latestMetricsReport?->monthly_profit !== null ? (float) $latestMetricsReport->monthly_profit : null,
                        $monthlyCalculated
                    ),
                    'realized' => $pickProfitMetric(
                        $latestMetricsReport?->realized_profit !== null ? (float) $latestMetricsReport->realized_profit : null,
                        $realized
                    ),
                ],
                'history' => $history,
                'history_meta' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $persistentTotal,
                    'last_page' => $lastPage,
                    'period' => $period,
                ],
                'analysis' => $this->readSignalSnapshot($configuration),
            ];

            if ($includeCalcDebug) {
                $response['calc_debug'] = [
                    'source' => 'report',
                    'server_now' => Carbon::now()->toISOString(),
                    'start_of_day' => $startOfDay->toISOString(),
                    'start_of_week' => $startOfWeek->toISOString(),
                    'start_of_month' => $startOfMonth->toISOString(),
                    'history_total_rows' => $persistentTotal,
                    'history_page_rows' => $history->count(),
                    'wins' => $wins,
                    'losses' => $losses,
                    'realized' => $realized,
                    'daily' => $dailyCalculated,
                    'weekly' => $weeklyCalculated,
                    'monthly' => $monthlyCalculated,
                    'formula' => 'net_profit = profit + swap + commission; period sums use close_time boundaries',
                ];
            }

            return response()->json($response);
        }

        $reports = $reportsQuery->limit(max($limit * $page, 500))->get();

        $historyRows = [];
        $historyKeys = [];
        foreach ($reports as $report) {
            foreach (($report->closed_trades ?? []) as $trade) {
                if (!is_array($trade)) {
                    continue;
                }

                $ticket = (string) ($trade['ticket'] ?? $trade['order'] ?? '-');
                $closeTime = (string) ($trade['close_time'] ?? '');
                $symbol = (string) ($trade['symbol'] ?? $configuration->pair_symbol ?? '-');
                $profit = (float) ($trade['profit'] ?? 0);
                $uniqueKey = $ticket !== '-'
                    ? $ticket
                    : md5(json_encode([$symbol, $closeTime, $profit, $trade['lot'] ?? $trade['volume'] ?? 0]));

                if (isset($historyKeys[$uniqueKey])) {
                    continue;
                }

                $historyKeys[$uniqueKey] = true;

                $historyRows[] = [
                    'ticket' => $ticket,
                    'symbol' => $symbol,
                    'type' => (string) ($trade['type'] ?? '-'),
                    'lot' => (float) ($trade['lot'] ?? $trade['volume'] ?? 0),
                    'open_price' => (float) ($trade['open_price'] ?? 0),
                    'close_price' => (float) ($trade['close_price'] ?? 0),
                    'profit' => $profit,
                    'swap' => (float) ($trade['swap'] ?? 0),
                    'commission' => (float) ($trade['commission'] ?? 0),
                    'open_time' => (string) ($trade['open_time'] ?? ''),
                    'close_time' => $closeTime,
                ];
            }
        }

        $allHistoryCollection = collect($historyRows)
            ->sortByDesc(function (array $row) {
                $timestamp = $this->parseTelemetryTimestamp((string) ($row['close_time'] ?: $row['open_time']));

                return $timestamp?->getTimestamp() ?? 0;
            })
            ->values();

        $historyCollection = $allHistoryCollection;

        if ($periodStart instanceof Carbon) {
            $historyCollection = $historyCollection->filter(function (array $row) use ($periodStart, $periodEnd): bool {
                $closedTimeText = (string) ($row['close_time'] ?: $row['open_time'] ?: '');
                if ($closedTimeText === '') {
                    return false;
                }

                $closedAt = $this->parseTelemetryTimestamp($closedTimeText);
                if ($closedAt === null) {
                    return false;
                }

                if ($closedAt->lessThan($periodStart)) {
                    return false;
                }

                if ($periodEnd instanceof Carbon && $closedAt->greaterThan($periodEnd)) {
                    return false;
                }

                return true;
            })->values();
        }

        $statsHistoryCollection = $allHistoryCollection;
        if (is_string($resetAtIso) && trim($resetAtIso) !== '') {
            try {
                $resetAt = Carbon::parse($resetAtIso);
                $statsHistoryCollection = $statsHistoryCollection->filter(function (array $row) use ($resetAt): bool {
                    $closedTimeText = (string) ($row['close_time'] ?: $row['open_time'] ?: '');
                    if ($closedTimeText === '') {
                        return true;
                    }

                    $closedAt = $this->parseTelemetryTimestamp($closedTimeText);
                    return $closedAt !== null && $closedAt->greaterThanOrEqualTo($resetAt);
                })->values();
            } catch (\Throwable) {
            }
        }

        $totalHistory = $historyCollection->count();
        $lastPage = max(1, (int) ceil($totalHistory / max($limit, 1)));
        $page = min($page, $lastPage);
        $history = $historyCollection
            ->slice(($page - 1) * $limit, $limit)
            ->values();

        $wins = 0;
        $losses = 0;
        $realized = 0.0;
        $dailyCalculated = 0.0;
        $weeklyCalculated = 0.0;
        $monthlyCalculated = 0.0;
        $now = Carbon::now('Asia/Jakarta');
        $startOfDay = $now->copy()->startOfDay();
        $startOfWeek = $now->copy()->startOfWeek(Carbon::MONDAY);
        $startOfMonth = $now->copy()->startOfMonth();
        foreach ($statsHistoryCollection as $trade) {
            $profit = (float) ($trade['profit'] ?? 0);
            $swap = (float) ($trade['swap'] ?? 0);
            $commission = (float) ($trade['commission'] ?? 0);
            $netProfit = $profit + $swap + $commission;
            $realized += $netProfit;
            if ($netProfit > 0) {
                $wins++;
            } elseif ($netProfit < 0) {
                $losses++;
            }

            $closedTimeText = (string) ($trade['close_time'] ?? $trade['open_time'] ?? '');
            if ($closedTimeText === '') {
                continue;
            }

            $closedAt = $this->parseTelemetryTimestamp($closedTimeText);
            if ($closedAt === null) {
                continue;
            }

            if ($closedAt->greaterThanOrEqualTo($startOfDay)) {
                $dailyCalculated += $netProfit;
            }
            if ($closedAt->greaterThanOrEqualTo($startOfWeek)) {
                $weeklyCalculated += $netProfit;
            }
            if ($closedAt->greaterThanOrEqualTo($startOfMonth)) {
                $monthlyCalculated += $netProfit;
            }
        }

        $latest = $reports->first();
        if ($latest !== null) {
            $wins = max($wins, (int) ($latest->wins ?? 0));
            $losses = max($losses, (int) ($latest->losses ?? 0));
            if ($realized == 0.0) {
                $realized = (float) ($latest->realized_profit ?? 0);
            }
        }

        $total = $wins + $losses;
        $winRate = $total > 0 ? round(($wins / $total) * 100, 2) : 0.0;

        $response = [
            'success' => true,
            'account_id' => $configuration->account_id,
            'account_currency' => strtoupper((string) ($configuration->account_currency ?? 'USD')),
            'wr' => [
                'wins' => $wins,
                'losses' => $losses,
                'total' => $total,
                'win_rate_percent' => $winRate,
                'reset_at' => $resetAtIso,
            ],
            'profit' => [
                'daily' => $pickProfitMetric(
                    $latestMetricsReport?->daily_profit !== null ? (float) $latestMetricsReport->daily_profit : null,
                    $dailyCalculated
                ),
                'weekly' => $pickProfitMetric(
                    $latestMetricsReport?->weekly_profit !== null ? (float) $latestMetricsReport->weekly_profit : null,
                    $weeklyCalculated
                ),
                'monthly' => $pickProfitMetric(
                    $latestMetricsReport?->monthly_profit !== null ? (float) $latestMetricsReport->monthly_profit : null,
                    $monthlyCalculated
                ),
                'realized' => $pickProfitMetric(
                    $latestMetricsReport?->realized_profit !== null ? (float) $latestMetricsReport->realized_profit : null,
                    $realized
                ),
            ],
            'history' => $history,
            'history_meta' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $totalHistory,
                'last_page' => $lastPage,
                'period' => $period,
            ],
            'analysis' => $this->readSignalSnapshot($configuration),
        ];

        if ($includeCalcDebug) {
            $response['calc_debug'] = [
                'source' => 'report',
                'server_now' => Carbon::now()->toISOString(),
                'start_of_day' => $startOfDay->toISOString(),
                'start_of_week' => $startOfWeek->toISOString(),
                'start_of_month' => $startOfMonth->toISOString(),
                'history_total_rows' => $totalHistory,
                'history_page_rows' => $history->count(),
                'wins' => $wins,
                'losses' => $losses,
                'realized' => $realized,
                'daily' => $dailyCalculated,
                'weekly' => $weeklyCalculated,
                'monthly' => $monthlyCalculated,
                'formula' => 'net_profit = profit + swap + commission; period sums use close_time boundaries',
            ];
        }

        return response()->json($response);
    }

    public function myReportResetWr(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
        ]);

        $configuration = $this->resolveUserAccessibleConfiguration($request, (string) $validated['account_id']);

        if ($configuration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Akun MT5 tidak ditemukan untuk user ini.',
            ], 404);
        }

        $resetAtIso = Carbon::now()->toIso8601String();
        $resetKey = 'wr_reset_ts_account_' . $configuration->account_id;
        Cache::put($resetKey, $resetAtIso, now()->addDays(365));

        $latestReport = EaStatusReport::query()
            ->where('ea_configuration_id', $configuration->id)
            ->latest('id')
            ->first();

        if ($latestReport !== null) {
            $latestReport->forceFill([
                'wins' => 0,
                'losses' => 0,
                'realized_profit' => 0,
                'updated_at' => Carbon::now(),
            ])->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Hard reset profit account berhasil. Baseline profit direset tanpa menghapus history trade.',
            'reset_at' => $resetAtIso,
        ]);
    }

    private function resolveUserAccessibleConfiguration(Request $request, string $accountId): ?EaConfiguration
    {
        $user = $request->user();
        $role = $user ? (string) ($user->role ?? '') : '';
        $isAdmin = $user ? (bool) (($user->is_admin ?? false) || $role === 'admin') : false;
        $pairSymbol = $this->requestedPairSymbol($request);
        $rawPair = strtoupper(trim((string) $request->input('pair_symbol', $request->query('pair_symbol', ''))));

        $query = EaConfiguration::query()->where('account_id', $accountId);
        if ($pairSymbol !== null) {
            $query->where(function ($pairQuery) use ($pairSymbol, $rawPair): void {
                $pairQuery->where('pair_symbol', $pairSymbol);

                if ($rawPair !== '' && $rawPair !== $pairSymbol) {
                    $pairQuery->orWhere('pair_symbol', $rawPair);
                }

                // Tolerate decorated broker symbols (e.g. #BTCUSD, BTCUSD.m) that still map to same canonical pair.
                $pairQuery->orWhere('pair_symbol', 'like', '%' . $pairSymbol);
            });
        }
        if ($user !== null && !$isAdmin) {
            $query->where('user_id', $user->id);
        }

        $configuration = $query->first();
        if ($configuration !== null || $pairSymbol !== null) {
            return $configuration;
        }

        $fallbackQuery = EaConfiguration::query()->where('account_id', $accountId);
        if ($user !== null && !$isAdmin) {
            $fallbackQuery->where('user_id', $user->id);
        }

        return $fallbackQuery->first();
    }

    public function myStatistics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'pair_symbol' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_\/\.\-]+$/'],
        ]);

        $configuration = $this->resolveUserAccessibleConfiguration($request, (string) $validated['account_id']);

        if ($configuration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Akun MT5 tidak ditemukan untuk user ini.',
            ], 404);
        }

        $reports = EaStatusReport::query()
            ->where('ea_configuration_id', $configuration->id);

        $totalReports = (clone $reports)->count();
        $avgFloating = (float) ((clone $reports)->avg('global_floating') ?? 0.0);
        $bestFloating = (float) ((clone $reports)->max('global_floating') ?? 0.0);
        $worstFloating = (float) ((clone $reports)->min('global_floating') ?? 0.0);
        $maxLayers = (int) ((clone $reports)->max('current_layers') ?? 0);

        return response()->json([
            'success' => true,
            'data' => [
                'account_id' => $configuration->account_id,
                'is_online' => $this->isRecentlyOnline($configuration),
                'latest_floating' => (float) $configuration->global_floating,
                'latest_layers' => (int) $configuration->current_layers,
                'account_currency' => strtoupper((string) ($configuration->account_currency ?? 'USD')),
                'total_reports' => $totalReports,
                'avg_floating' => $avgFloating,
                'best_floating' => $bestFloating,
                'worst_floating' => $worstFloating,
                'max_layers_seen' => $maxLayers,
            ],
        ]);
    }

    public function myReports(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'pair_symbol' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_\/\.\-]+$/'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $configuration = $this->resolveUserAccessibleConfiguration($request, (string) $validated['account_id']);

        if ($configuration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Akun MT5 tidak ditemukan untuk user ini.',
            ], 404);
        }

        $limit = (int) ($validated['limit'] ?? 50);

        $rows = EaStatusReport::query()
            ->where('ea_configuration_id', $configuration->id)
            ->latest('id')
            ->limit($limit)
            ->get([
                'account_id',
                'current_layers',
                'current_accumulative_lot',
                'global_floating',
                'guard_status',
                'created_at',
            ]);

        return response()->json([
            'success' => true,
            'account_id' => $configuration->account_id,
            'reports' => $rows,
        ]);
    }

    public function dashboardStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'pair_symbol' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_\/\.\-]+$/'],
        ]);

        $configuration = $this->resolveUserAccessibleConfiguration($request, (string) $validated['account_id']);

        if ($configuration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Akun MT5 tidak ditemukan untuk user ini.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            ...$this->transformStatus($configuration),
        ]);
    }

    public function legacyStatusHeartbeat(Request $request): JsonResponse
    {
        $accountId = trim((string) ($request->input('account_id', $request->query('account_id', ''))));
        if ($accountId === '') {
            return response()->json([
                'success' => false,
                'message' => 'account_id wajib diisi.',
            ], 422);
        }

        $apiKeyError = $this->ensureEaApiKey($request);
        if ($apiKeyError !== null) {
            return $apiKeyError;
        }

        $configuration = $this->resolveUserAccessibleConfiguration($request, $accountId);

        if ($configuration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Akun MT5 tidak ditemukan.',
            ], 404);
        }

        $balance = $request->input('balance');
        $equity = $request->input('equity');
        $derivedFloating = null;
        if (is_numeric($balance) && is_numeric($equity)) {
            $derivedFloating = (float) $equity - (float) $balance;
        }

        $openPositions = $this->readTelemetryArray($request, ['open_positions', 'positions', 'positions_data']);
        $pendingOrders = $this->readTelemetryArray($request, ['pending_orders', 'orders_pending', 'pending_orders_list']);
        $closedTrades = $this->readTelemetryArray($request, [
            'closed_trades',
            'history_trades',
            'trade_history',
            'closedTrades',
            'historyTrades',
            'tradeHistory',
            'history',
            'closed_positions',
        ]);

        $hasExplicitCurrentLayers = $request->has('current_layers');
        $currentLayers = (int) ($request->input('current_layers', $request->input('open_positions', $request->input('positions_total', $configuration->current_layers))));
        if (!$hasExplicitCurrentLayers) {
            if ($openPositions !== []) {
                $currentLayers = count($openPositions);
            } elseif ($hasOpenPositionsPayload) {
                $currentLayers = 0;
            }
        }
        $hasExplicitCurrentAccLot = $request->has('current_accumulative_lot');
        $currentAccLot = (float) ($request->input('current_accumulative_lot', $configuration->current_accumulative_lot));
        if (!$hasExplicitCurrentAccLot) {
            if ($openPositions !== []) {
                $currentAccLot = $this->sumLots($openPositions);
            } elseif ($hasOpenPositionsPayload) {
                $currentAccLot = 0.0;
            }
        }
        $explicitFloating = $request->input('global_floating');
        if (is_numeric($explicitFloating)) {
            $globalFloating = (float) $explicitFloating;
        } elseif ($derivedFloating !== null) {
            $globalFloating = (float) $derivedFloating;
        } elseif ($openPositions !== []) {
            $globalFloating = $this->sumFloating($openPositions);
        } elseif ($hasOpenPositionsPayload) {
            $globalFloating = 0.0;
        } elseif ($currentLayers <= 0) {
            $globalFloating = 0.0;
        } else {
            $globalFloating = (float) ($configuration->global_floating ?? 0.0);
        }
        $reportedGuardStatus = (string) ($request->input('guard_status', ((bool) $request->input('remote_paused', false)) ? 'PAUSED' : ($configuration->live_guard_status ?: 'LIVE')));
        $symbol = trim((string) $request->input('symbol', ''));
        $commandedGuardStatus = (string) ($configuration->guard_status ?: 'LIVE');
        $tradingEnabled = strtoupper($commandedGuardStatus) === 'LIVE';
        $licenseStatus = $this->licenseService->getStatusForConfiguration($configuration);
        $licenseRuntime = $this->licenseService->getRuntimeStatusForConfiguration($configuration, $currentLayers, $currentAccLot);

        $licenseEnforcementEnabled = (bool) ($licenseRuntime['license_enforcement_enabled'] ?? $this->licenseService->isEnforcementEnabled());
        $licenseGracePeriod = (bool) ($licenseRuntime['license_grace_period'] ?? false);
        $licenseInactive = $licenseEnforcementEnabled && !(bool) ($licenseRuntime['license_active'] ?? false) && !$licenseGracePeriod;

        if ($licenseGracePeriod && strtoupper($commandedGuardStatus) !== 'DD_STOP') {
            $commandedGuardStatus = 'LIVE';
        } elseif ($licenseInactive) {
            $hasActiveExposure = $currentLayers > 0 || $currentAccLot > 0.0000001;
            if (!$hasActiveExposure) {
                $commandedGuardStatus = 'PAUSED';
                $reportedGuardStatus = 'PAUSED';
            }
        }

        try {
            $configuration->update([
                'current_layers' => max(0, $currentLayers),
                'current_accumulative_lot' => max(0, $currentAccLot),
                'global_floating' => $globalFloating,
                'guard_status' => $commandedGuardStatus,
                'live_guard_status' => $reportedGuardStatus,
                'is_online' => true,
                'updated_at' => Carbon::now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::warning('[Heartbeat] config update denied: ' . $e->getCode());
        }

        try {
            $this->persistClosedTrades($configuration, $closedTrades);
        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::warning('[Heartbeat] persistClosedTrades denied: ' . $e->getCode());
        }

        try {
            $reportOpenPositions = $this->compactTelemetryRows($openPositions, 40);
            $reportPendingOrders = $this->compactTelemetryRows($pendingOrders, 40);

            $latestMetricsReport = EaStatusReport::query()
                ->where('ea_configuration_id', $configuration->id)
                ->latest('id')
                ->first();

            $winsValue = $request->has('wins')
                ? (int) $request->input('wins', 0)
                : (int) ($latestMetricsReport?->wins ?? 0);
            $lossesValue = $request->has('losses')
                ? (int) $request->input('losses', 0)
                : (int) ($latestMetricsReport?->losses ?? 0);
            $realizedProfitValue = $request->has('realized_profit')
                ? (float) $request->input('realized_profit', 0)
                : (float) ($latestMetricsReport?->realized_profit ?? 0.0);
            $dailyProfitValue = $request->has('daily_profit')
                ? (float) $request->input('daily_profit', 0)
                : (float) ($latestMetricsReport?->daily_profit ?? 0.0);
            $weeklyProfitValue = $request->has('weekly_profit')
                ? (float) $request->input('weekly_profit', 0)
                : (float) ($latestMetricsReport?->weekly_profit ?? 0.0);
            $monthlyProfitValue = $request->has('monthly_profit')
                ? (float) $request->input('monthly_profit', 0)
                : (float) ($latestMetricsReport?->monthly_profit ?? 0.0);

            if ($this->shouldWriteStatusReport(
                $configuration,
                max(0, $currentLayers),
                max(0, $currentAccLot),
                $globalFloating,
                (string) $reportedGuardStatus,
                $reportOpenPositions,
                $reportPendingOrders,
                $winsValue,
                $lossesValue,
                $realizedProfitValue,
                $dailyProfitValue,
                $weeklyProfitValue,
                $monthlyProfitValue
            )) {
                EaStatusReport::query()->create([
                'user_id' => $configuration->user_id,
                'ea_configuration_id' => $configuration->id,
                'account_id' => $configuration->account_id,
                'current_layers' => max(0, $currentLayers),
                'current_accumulative_lot' => max(0, $currentAccLot),
                'global_floating' => $globalFloating,
                'guard_status' => $reportedGuardStatus,
                'balance' => is_numeric($balance) ? (float) $balance : null,
                'equity' => is_numeric($equity) ? (float) $equity : null,
                'open_positions' => $reportOpenPositions,
                'pending_orders' => $reportPendingOrders,
                'closed_trades' => $this->compactClosedTradesForReport($closedTrades, 25),
                'wins' => $winsValue,
                'losses' => $lossesValue,
                'realized_profit' => $realizedProfitValue,
                'daily_profit' => $dailyProfitValue,
                'weekly_profit' => $weeklyProfitValue,
                'monthly_profit' => $monthlyProfitValue,
                ]);
                $this->pruneStatusReportsIfNeeded($configuration);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::warning('[Heartbeat] EaStatusReport create denied: ' . $e->getCode());
        }

        $this->storeSignalSnapshot(
            $request,
            $configuration,
            (string) $commandedGuardStatus,
            (string) $reportedGuardStatus
        );

        return response()->json([
            'success' => true,
            'message' => 'Heartbeat kompatibel diterima.',
            'guard_status' => $commandedGuardStatus,
            'live_guard_status' => $reportedGuardStatus,
            'trading_enabled' => $tradingEnabled,
            'is_active' => (int) ($licenseRuntime['is_active'] ?? 0),
            'is_trading_active' => (int) ($licenseRuntime['is_trading_active'] ?? 0),
            'license_status' => (string) ($licenseRuntime['license_status'] ?? $licenseStatus['license_status'] ?? 'unlicensed'),
            'license' => array_merge($licenseStatus, $licenseRuntime),
        ]);
    }

    private function transformConfig(EaConfiguration $configuration): array
    {
        $newsBlocked = $this->isNewsBlockedNow($configuration);
        $licenseStatus = $this->licenseService->getStatusForConfiguration($configuration);
        $isGridStrategy = (int) ($configuration->active_strategy ?? 0) === 0;
        $effectiveMaxLayers = (int) ($isGridStrategy
            ? ($configuration->grid_max_layers ?: $configuration->max_layers ?: 0)
            : ($configuration->max_layers ?: $configuration->grid_max_layers ?: 0));
        $effectiveMaxAccLot = (float) ($isGridStrategy
            ? ($configuration->grid_max_accumulative_lot ?: $configuration->max_accumulative_lot ?: 0)
            : ($configuration->max_accumulative_lot ?: $configuration->grid_max_accumulative_lot ?: 0));
        $runtimeLayers = max(0, (int) ($configuration->current_layers ?? 0));
        $runtimeAccLot = max(0.0, (float) ($configuration->current_accumulative_lot ?? 0));
        $licenseRuntime = $this->licenseService->getRuntimeStatusForConfiguration($configuration, $runtimeLayers, $runtimeAccLot);
        $licenseEnforcementEnabled = (bool) ($licenseRuntime['license_enforcement_enabled'] ?? $this->licenseService->isEnforcementEnabled());
        $licenseGracePeriod = (bool) ($licenseRuntime['license_grace_period'] ?? false);
        $licenseInactive = $licenseEnforcementEnabled && !(bool) ($licenseRuntime['license_active'] ?? false) && !$licenseGracePeriod;

        $effectiveGuardStatus = (string) ($configuration->guard_status ?? 'PAUSED');
        if ($licenseGracePeriod && strtoupper($effectiveGuardStatus) !== 'DD_STOP') {
            $effectiveGuardStatus = 'LIVE';
        } elseif ($licenseInactive) {
            $effectiveGuardStatus = 'PAUSED';
        }

        $effectiveTradingEnabled = strtoupper($effectiveGuardStatus) === 'LIVE';
        $isDdStop = strtoupper($effectiveGuardStatus) === 'DD_STOP';
        $licenseExpiredWithExposure = $licenseGracePeriod && ($runtimeLayers > 0 || $runtimeAccLot > 0.0000001);
        $allowOpenNewCycle = !$isDdStop && (bool) ($licenseRuntime['license_can_start_new_cycle'] ?? false) && $effectiveTradingEnabled;
        // Graceful stop: when dashboard pauses (guard_status='PAUSED'), allow the current
        // cycle to finish before blocking new entries. Only hard-block for DD_STOP and
        // truly inactive (non-grace) licenses.
        $allowManageExistingCycle = !$isDdStop && !$licenseInactive && ((bool) ($licenseRuntime['license_can_manage_existing_cycle'] ?? false) || $licenseExpiredWithExposure);
        $runtimeTradingMode = $isDdStop
            ? 'FORCE_CLOSE'
            : ($licenseExpiredWithExposure
                ? 'LIVE_FULL'
                : ($licenseInactive
                    ? 'PAUSED'
                    : ($effectiveTradingEnabled ? 'LIVE_FULL' : 'PAUSED')));
        $effectiveAlwaysInMarket = $licenseInactive ? 0 : (int) $configuration->always_in_market;
        $effectiveInstantReentry = $licenseInactive ? 0 : (int) $configuration->instant_reentry;
        $effectiveAutoFlip = $licenseInactive ? 0 : (int) $configuration->auto_flip;

        return [
            'success' => true,
            'account_id' => $configuration->account_id,
            'strategy' => (int) $configuration->active_strategy,
            'timeframe' => (int) $configuration->timeframe_logic,
            'snr_filter' => (bool) $configuration->filter_snr_activation,
            'use_stealth_mode' => (bool) $configuration->use_stealth_mode,
            'use_mirror_trap' => (bool) $configuration->use_mirror_trap,
            'strategy_params' => $this->transformStrategyParams($configuration),
            'sessions' => $this->transformSessions($configuration),
            'news_block' => [
                'severity' => (string) ($configuration->news_filter_severity ?? 'HIGH'),
                'pause_before' => (int) $configuration->news_pause_before_minutes,
                'pause_after' => (int) $configuration->news_pause_after_minutes,
                'is_blocked' => $newsBlocked,
            ],
            'server_time' => Carbon::now()->toIso8601String(),
            'pair_symbol' => $configuration->pair_symbol,
            'account_currency' => strtoupper((string) ($configuration->account_currency ?? 'USD')),
            'max_layers' => $effectiveMaxLayers,
            'max_accumulative_lot' => $effectiveMaxAccLot,
            'max_mart_steps' => (int) ($configuration->mart_max_steps ?? 0),
            'base_lot' => $configuration->base_lot,
            'max_spread' => $configuration->max_spread,
            'max_drawdown_pct' => $configuration->max_drawdown_pct,
            'max_drawdown_stop_delay' => (int) ($configuration->max_drawdown_stop_delay ?? 0),
            'dd_breach_hits_required' => $this->getDdBreachHitsRequired($configuration),
            'daily_profit_target' => $configuration->daily_profit_target,
            'use_martingale' => (int) $configuration->use_martingale,
            'target_tp_percentage' => $configuration->target_tp_percentage,
            'grid_tp_mode' => (int) ($configuration->grid_tp_mode ?? 0),
            'grid_tier1_tp_percent' => (float) ($configuration->grid_tier1_tp_percent ?? 60),
            'grid_tier2_tp_percent' => (float) ($configuration->grid_tier2_tp_percent ?? 45),
            'grid_tier3_tp_percent' => (float) ($configuration->grid_tier3_tp_percent ?? 30),
            'grid_tier4_tp_percent' => (float) ($configuration->grid_tier4_tp_percent ?? 20),
            'mart_type' => $configuration->mart_type,
            'mart_addition' => $configuration->mart_addition,
            'mart_multiplier' => $configuration->mart_multiplier,
            'grid_mode' => $configuration->grid_mode,
            'fix_grid_distance' => $configuration->fix_grid_distance,
            'atr_period_grid' => $configuration->atr_period_grid,
            'atr_timeframe_grid' => $configuration->atr_timeframe_grid,
            'atr_multiplier' => $configuration->atr_multiplier,
            'min_grid_distance' => $configuration->min_grid_distance,
            'farming_gap' => $configuration->farming_gap,
            'mart_start_layer' => $configuration->mart_start_layer,
            'initial_sl' => $configuration->initial_sl,
            'trail_start' => $configuration->trail_start,
            'trail_stop' => $configuration->trail_stop,
            'trail_step' => $configuration->trail_step,
            'use_breakeven' => (int) $configuration->use_breakeven,
            'be_distance' => $configuration->be_distance,
            'be_buffer' => $configuration->be_buffer,
            'start_hour' => $configuration->start_hour,
            'end_hour' => $configuration->end_hour,
            'always_in_market' => $effectiveAlwaysInMarket,
            'instant_reentry' => $effectiveInstantReentry,
            'min_confluence_score' => (int) ($configuration->min_confluence_score ?? 5),
            'auto_flip' => $effectiveAutoFlip,
            'use_pending_guard' => (int) $configuration->use_pending_guard,
            'use_trend_filter' => (int) $configuration->use_trend_filter,
            'use_ai_core_sharpening' => (int) $configuration->use_ai_core_sharpening,
            'use_ema_ribbon' => (int) $configuration->use_ema_ribbon,
            'use_dmi' => (int) $configuration->use_dmi,
            'use_mkt_struct' => (int) $configuration->use_mkt_struct,
            'use_early_trend' => (int) $configuration->use_early_trend,
            'use_sniper_entry' => (int) $configuration->use_sniper_entry,
            'show_indicator_fallback_logs' => (int) ($configuration->show_indicator_fallback_logs ?? 0),
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
            'use_dxy_filter' => (int) ($configuration->use_dxy_filter ?? 0),
            'use_us10y_filter' => (int) ($configuration->use_us10y_filter ?? 0),
            'use_vix_filter' => (int) ($configuration->use_vix_filter ?? 0),
            'use_oil_filter' => (int) ($configuration->use_oil_filter ?? 0),
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
            'filter_snr_activation' => (int) $configuration->filter_snr_activation,
            'news_filter_severity' => (string) ($configuration->news_filter_severity ?? 'HIGH'),
            'news_pause_before_minutes' => (int) $configuration->news_pause_before_minutes,
            'news_pause_after_minutes' => (int) $configuration->news_pause_after_minutes,
            'use_stealth_mode_flat' => (int) $configuration->use_stealth_mode,
            'use_sydney_session' => (int) $configuration->use_sydney_session,
            'sydney_start_wib' => $configuration->sydney_start_wib,
            'sydney_end_wib' => $configuration->sydney_end_wib,
            'use_asia_session' => (int) $configuration->use_asia_session,
            'asia_start_wib' => $configuration->asia_start_wib,
            'asia_end_wib' => $configuration->asia_end_wib,
            'use_europe_session' => (int) $configuration->use_europe_session,
            'europe_start_wib' => $configuration->europe_start_wib,
            'europe_end_wib' => $configuration->europe_end_wib,
            'use_us_session' => (int) $configuration->use_us_session,
            'us_start_wib' => $configuration->us_start_wib,
            'us_end_wib' => $configuration->us_end_wib,
            'current_layers' => $configuration->current_layers,
            'current_accumulative_lot' => $configuration->current_accumulative_lot,
            'global_floating' => $configuration->global_floating,
            'guard_status' => $effectiveGuardStatus,
            'trading_enabled' => $effectiveTradingEnabled,
            'allow_open_new_cycle' => $allowOpenNewCycle,
            'allow_manage_existing_cycle' => $allowManageExistingCycle,
            'force_close_required' => $isDdStop,
            'runtime_trading_mode' => $runtimeTradingMode,
            'is_online' => $this->isRecentlyOnline($configuration),
            'updated_at' => optional($configuration->updated_at)?->toISOString(),
            'license_exists' => (bool) ($licenseStatus['license_exists'] ?? false),
            'license_status' => (string) ($licenseRuntime['license_status'] ?? $licenseStatus['license_status'] ?? 'unlicensed'),
            'license_active' => (bool) ($licenseRuntime['license_active'] ?? $licenseStatus['license_active'] ?? false),
            'license_grace_period' => (bool) ($licenseRuntime['license_grace_period'] ?? false),
            'license_can_start_new_cycle' => (bool) ($licenseRuntime['license_can_start_new_cycle'] ?? false),
            'license_can_manage_existing_cycle' => (bool) ($licenseRuntime['license_can_manage_existing_cycle'] ?? false),
            'is_active' => (int) ($licenseRuntime['is_active'] ?? 0),
            'is_trading_active' => (int) ($licenseRuntime['is_trading_active'] ?? 0),
            'license_is_perpetual' => (bool) ($licenseStatus['license_is_perpetual'] ?? false),
            'license_remaining_seconds' => (int) ($licenseStatus['license_remaining_seconds'] ?? 0),
            'license_remaining_text' => (string) ($licenseStatus['license_remaining_text'] ?? 'No license'),
            'license_expires_at' => $licenseStatus['license_expires_at'] ?? null,
            'license_plan_name' => $licenseStatus['license_plan_name'] ?? null,
            'license_message' => (string) ($licenseRuntime['license_message'] ?? $licenseStatus['license_message'] ?? ''),
            'license_enforcement_enabled' => $licenseEnforcementEnabled,
        ];
    }

    private function transformStrategyParams(EaConfiguration $configuration): array
    {
        return [
            'dd_breach_hits_required' => $this->getDdBreachHitsRequired($configuration),
            'grid_max_layers' => (int) $configuration->grid_max_layers,
            'grid_max_accumulative_lot' => (float) $configuration->grid_max_accumulative_lot,
            'grid_mode' => (int) $configuration->grid_mode,
            'grid_fix_distance' => (int) $configuration->fix_grid_distance,
            'grid_atr_multiplier' => (float) $configuration->atr_multiplier,
            'grid_target_usd' => (float) $configuration->grid_target_usd,
            'grid_tp_points' => (int) $configuration->grid_tp_points,
            'grid_sl_points' => (int) $configuration->grid_sl_points,
            'grid_use_trailing_layer1' => (bool) $configuration->grid_use_trailing_layer1,
            'grid_use_basket_tp_percent' => (bool) $configuration->grid_use_basket_tp_percent,
            'grid_basket_tp_percent' => (float) $configuration->grid_basket_tp_percent,
            'grid_tp_mode' => (int) ($configuration->grid_tp_mode ?? 0),
            'grid_tier1_tp_percent' => (float) ($configuration->grid_tier1_tp_percent ?? 60),
            'grid_tier2_tp_percent' => (float) ($configuration->grid_tier2_tp_percent ?? 45),
            'grid_tier3_tp_percent' => (float) ($configuration->grid_tier3_tp_percent ?? 30),
            'grid_tier4_tp_percent' => (float) ($configuration->grid_tier4_tp_percent ?? 20),
            'mirror_active' => (bool) $configuration->mirror_active,
            'mirror_pending_distance' => (int) $configuration->mirror_pending_distance_points,
            'mirror_pending_distance_points' => (int) $configuration->mirror_pending_distance_points,
            'mirror_multiplier' => (float) $configuration->mirror_multiplier,
            'zero_gap_tp_points' => (int) $configuration->zero_gap_tp_points,
            'zero_gap_sl_points' => (int) $configuration->zero_gap_sl_points,
            'zero_gap_max_layers' => (int) $configuration->zero_gap_max_layers,
            'zero_gap_trailing_start_points' => (int) $configuration->zero_gap_trailing_start_points,
            'zero_gap_trailing_step_points' => (int) $configuration->zero_gap_trailing_step_points,
            'mart_max_steps' => (int) $configuration->mart_max_steps,
            'mart_type' => (int) $configuration->mart_type,
            'mart_multiplier' => (float) $configuration->mart_multiplier,
            'mart_addition' => (float) $configuration->mart_addition,
            'mart_tp_points' => (int) $configuration->mart_tp_points,
            'mart_sl_points' => (int) $configuration->mart_sl_points,
            'mart_trailing_start_points' => (int) $configuration->mart_trailing_start_points,
            'mart_trailing_step_points' => (int) $configuration->mart_trailing_step_points,
            'use_mirror_trap' => (bool) $configuration->use_mirror_trap,
        ];
    }

    private function transformSessions(EaConfiguration $configuration): array
    {
        return [
            'use_sydney_session' => (bool) $configuration->use_sydney_session,
            'sydney_start_wib' => (string) ($configuration->sydney_start_wib ?? ''),
            'sydney_end_wib' => (string) ($configuration->sydney_end_wib ?? ''),
            'use_asia_session' => (bool) $configuration->use_asia_session,
            'asia_start_wib' => (string) ($configuration->asia_start_wib ?? ''),
            'asia_end_wib' => (string) ($configuration->asia_end_wib ?? ''),
            'use_europe_session' => (bool) $configuration->use_europe_session,
            'europe_start_wib' => (string) ($configuration->europe_start_wib ?? ''),
            'europe_end_wib' => (string) ($configuration->europe_end_wib ?? ''),
            'use_us_session' => (bool) $configuration->use_us_session,
            'us_start_wib' => (string) ($configuration->us_start_wib ?? ''),
            'us_end_wib' => (string) ($configuration->us_end_wib ?? ''),
        ];
    }

    private function transformAccountSummary(EaConfiguration $configuration): array
    {
        $licenseStatus = $this->licenseService->getRuntimeStatusForConfiguration(
            $configuration,
            (int) ($configuration->current_layers ?? 0),
            (float) ($configuration->current_accumulative_lot ?? 0)
        );
        return [
            'account_id' => $configuration->account_id,
            'pair_symbol' => $configuration->pair_symbol,
            'is_online' => $this->isRecentlyOnline($configuration),
            'guard_status' => $configuration->guard_status,
            'current_layers' => $configuration->current_layers,
            'current_accumulative_lot' => $configuration->current_accumulative_lot,
            'global_floating' => $configuration->global_floating,
            'balance' => (float) ($configuration->current_balance ?? 0.0),
            'equity' => (float) ($configuration->current_equity ?? 0.0),
            'account_currency' => strtoupper((string) ($configuration->account_currency ?? 'USD')),
            'today_pnl' => (float) ($configuration->today_pnl ?? 0.0),
            'drawdown_pct' => (float) ($configuration->drawdown_pct ?? 0.0),
            'updated_at' => optional($configuration->updated_at)?->toISOString(),
            'last_seen_seconds' => $this->lastSeenSeconds($configuration),
            'license_active' => (bool) ($licenseStatus['license_active'] ?? false),
            'license_status' => (string) ($licenseStatus['license_status'] ?? 'unlicensed'),
            'license_remaining_seconds' => (int) ($licenseStatus['license_remaining_seconds'] ?? 0),
            'license_remaining_text' => (string) ($licenseStatus['license_remaining_text'] ?? 'No license'),
        ];
    }

    private function transformStatus(EaConfiguration $configuration): array
    {
        $licenseStatus = $this->licenseService->getStatusForConfiguration($configuration);
        $licenseRuntime = $this->licenseService->getRuntimeStatusForConfiguration(
            $configuration,
            (int) ($configuration->current_layers ?? 0),
            (float) ($configuration->current_accumulative_lot ?? 0)
        );
        return [
            'account_id' => $configuration->account_id,
            'pair_symbol' => $configuration->pair_symbol,
            'is_online' => $this->isRecentlyOnline($configuration),
            'current_layers' => $configuration->current_layers,
            'current_accumulative_lot' => $configuration->current_accumulative_lot,
            'global_floating' => $configuration->global_floating,
            'balance' => (float) ($configuration->current_balance ?? 0.0),
            'equity' => (float) ($configuration->current_equity ?? 0.0),
            'account_currency' => strtoupper((string) ($configuration->account_currency ?? 'USD')),
            'today_pnl' => (float) ($configuration->today_pnl ?? 0.0),
            'drawdown_pct' => (float) ($configuration->drawdown_pct ?? 0.0),
            'guard_status' => $configuration->guard_status,
            'updated_at' => optional($configuration->updated_at)?->toISOString(),
            'last_seen_seconds' => $this->lastSeenSeconds($configuration),
            'license_active' => (bool) ($licenseRuntime['license_active'] ?? $licenseStatus['license_active'] ?? false),
            'license_status' => (string) ($licenseRuntime['license_status'] ?? $licenseStatus['license_status'] ?? 'unlicensed'),
            'license_grace_period' => (bool) ($licenseRuntime['license_grace_period'] ?? false),
            'license_can_start_new_cycle' => (bool) ($licenseRuntime['license_can_start_new_cycle'] ?? false),
            'license_can_manage_existing_cycle' => (bool) ($licenseRuntime['license_can_manage_existing_cycle'] ?? false),
            'is_active' => (int) ($licenseRuntime['is_active'] ?? 0),
            'is_trading_active' => (int) ($licenseRuntime['is_trading_active'] ?? 0),
            'license_remaining_seconds' => (int) ($licenseStatus['license_remaining_seconds'] ?? 0),
            'license_remaining_text' => (string) ($licenseStatus['license_remaining_text'] ?? 'No license'),
        ];
    }

    private function isNewsBlockedNow(EaConfiguration $configuration): bool
    {
        $now = Carbon::now('UTC');
        $severities = $this->severityFilters((string) ($configuration->news_filter_severity ?? 'HIGH'));
        $correlatedCurrencies = $this->resolveCorrelatedCurrencies((string) ($configuration->pair_symbol ?? ''));
        $pauseBefore = max(0, (int) ($configuration->news_pause_before_minutes ?? 0));
        $pauseAfter = max(0, (int) ($configuration->news_pause_after_minutes ?? 0));

        $latest = EconomicNews::query()
            ->whereIn('impact', $severities)
            ->whereIn('currency', $correlatedCurrencies)
            ->whereBetween('event_at', [$now->copy()->subMinutes($pauseAfter), $now->copy()->addMinutes($pauseBefore)])
            ->orderByRaw('ABS(TIMESTAMPDIFF(SECOND, event_at, ?)) ASC', [$now->toDateTimeString()])
            ->first();

        if ($latest === null) {
            return false;
        }

        $from = $latest->event_at->copy()->subMinutes($pauseBefore);
        $to = $latest->event_at->copy()->addMinutes($pauseAfter);

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
            'LOW' => ['LOW'],
            'MEDIUM' => ['MEDIUM'],
            'BOTH' => ['HIGH', 'MEDIUM'],
            'ALL' => ['HIGH', 'MEDIUM', 'LOW'],
            default => ['HIGH'],
        };
    }

    private function isRecentlyOnline(EaConfiguration $configuration, ?int $freshWindowSec = null): bool
    {
        $windowSec = $freshWindowSec ?? (int) config('services.ea.online_fresh_window_sec', 30);
        if ($windowSec < 20) {
            $windowSec = 20;
        }

        $threshold = Carbon::now()->subSeconds($windowSec);

        if ($configuration->updated_at !== null
            && $configuration->updated_at->greaterThanOrEqualTo($threshold)) {
            return true;
        }

        $latestReport = EaStatusReport::query()
            ->where('ea_configuration_id', $configuration->id)
            ->latest('id')
            ->first(['created_at', 'updated_at']);

        if ($latestReport !== null) {
            $candidate = $latestReport->updated_at ?? $latestReport->created_at;
            if ($candidate !== null) {
                try {
                    if (Carbon::parse((string) $candidate)->greaterThanOrEqualTo($threshold)) {
                        return true;
                    }
                } catch (\Throwable) {
                    // ignore parse issues and continue to fallback
                }
            }
        }

        return (bool) $configuration->is_online;
    }

    private function lastSeenSeconds(EaConfiguration $configuration): ?int
    {
        if ($configuration->updated_at === null) {
            return null;
        }

        return abs((int) $configuration->updated_at->diffInSeconds(Carbon::now(), false));
    }

    private function parseTelemetryTimestamp(?string $value): ?Carbon
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        // Handle EA format like "18/5/2026, 07.30.27" and other single-digit day/month variants.
        $normalized = preg_replace('/\s+/', ' ', str_replace(',', ' ', $text));
        if (is_string($normalized)
            && preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2})[\.:](\d{1,2})(?:[\.:](\d{1,2}))?$/', $normalized, $m) === 1) {
            try {
                $day = (int) $m[1];
                $month = (int) $m[2];
                $year = (int) $m[3];
                $hour = (int) $m[4];
                $minute = (int) $m[5];
                $second = isset($m[6]) ? (int) $m[6] : 0;

                return Carbon::createFromFormat(
                    'j/n/Y H:i:s',
                    sprintf('%d/%d/%d %02d:%02d:%02d', $day, $month, $year, $hour, $minute, $second),
                    'Asia/Jakarta'
                );
            } catch (\Throwable) {
            }
        }

        $formats = [
            // MQL5 TimeToString() native format: "2026.05.18 08:08:45"
            'Y.m.d H:i:s',
            'Y.m.d H:i',
            // ISO / MySQL
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d\TH:i:sP',
            // Indonesian locale variants (d/m/Y)
            'j/n/Y, H.i.s',
            'j/n/Y H.i.s',
            'j/n/Y, H:i:s',
            'j/n/Y H:i:s',
            'j/n/Y, H.i',
            'j/n/Y H.i',
            'd/m/Y, H.i.s',
            'd/m/Y H.i.s',
            'd/m/Y, H:i:s',
            'd/m/Y H:i:s',
            'd/m/Y, H.i',
            'd/m/Y H.i',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $text, 'Asia/Jakarta');
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($text, 'Asia/Jakarta');
        } catch (\Throwable) {
            return null;
        }
    }

    private function compactTelemetryRows(array $rows, int $maxRows = 40): array
    {
        $items = array_values(array_filter($rows, static fn ($item): bool => is_array($item)));

        if ($maxRows > 0 && count($items) > $maxRows) {
            $items = array_slice($items, 0, $maxRows);
        }

        return $items;
    }

    private function compactClosedTradesForReport(array $closedTrades, int $maxRows = 25): array
    {
        if (isset($closedTrades['trades']) && is_array($closedTrades['trades'])) {
            $closedTrades = $closedTrades['trades'];
        } elseif (isset($closedTrades['data']) && is_array($closedTrades['data'])) {
            $closedTrades = $closedTrades['data'];
        } elseif (isset($closedTrades['items']) && is_array($closedTrades['items'])) {
            $closedTrades = $closedTrades['items'];
        } elseif (isset($closedTrades['history']) && is_array($closedTrades['history'])) {
            $closedTrades = $closedTrades['history'];
        } elseif (isset($closedTrades['closed_trades']) && is_array($closedTrades['closed_trades'])) {
            $closedTrades = $closedTrades['closed_trades'];
        }

        $items = [];
        foreach ($closedTrades as $trade) {
            if (!is_array($trade)) {
                continue;
            }

            $items[] = [
                'ticket' => (string) ($trade['ticket'] ?? $trade['order'] ?? ''),
                'symbol' => (string) ($trade['symbol'] ?? ''),
                'type' => (string) ($trade['type'] ?? ''),
                'lot' => (float) ($trade['lot'] ?? $trade['volume'] ?? 0),
                'open_price' => (float) ($trade['open_price'] ?? 0),
                'close_price' => (float) ($trade['close_price'] ?? 0),
                'profit' => (float) ($trade['profit'] ?? 0),
                'swap' => (float) ($trade['swap'] ?? 0),
                'commission' => (float) ($trade['commission'] ?? 0),
                'open_time' => (string) ($trade['open_time'] ?? ''),
                'close_time' => (string) ($trade['close_time'] ?? ''),
            ];
        }

        if ($maxRows > 0 && count($items) > $maxRows) {
            $items = array_slice($items, 0, $maxRows);
        }

        return $items;
    }

    private function shouldWriteStatusReport(
        EaConfiguration $configuration,
        int $layers,
        float $accLot,
        float $floating,
        string $guardStatus,
        array $openPositions = [],
        array $pendingOrders = [],
        int $wins = 0,
        int $losses = 0,
        float $realizedProfit = 0.0,
        float $dailyProfit = 0.0,
        float $weeklyProfit = 0.0,
        float $monthlyProfit = 0.0
    ): bool
    {
        $cacheKey = 'ea_status_report_gate_cfg_' . (int) $configuration->id;
        $openPositionsSig = $this->telemetryRowsFingerprint($openPositions);
        $pendingOrdersSig = $this->telemetryRowsFingerprint($pendingOrders);
        $currentState = [
            'ts' => time(),
            'sig' => implode('|', [
                $layers,
                round($accLot, 2),
                round($floating, 2),
                strtoupper(trim($guardStatus)),
                $openPositionsSig,
                $pendingOrdersSig,
                $wins,
                $losses,
                round($realizedProfit, 2),
                round($dailyProfit, 2),
                round($weeklyProfit, 2),
                round($monthlyProfit, 2),
            ]),
        ];

        $last = Cache::get($cacheKey);
        if (is_array($last)) {
            $lastTs = (int) ($last['ts'] ?? 0);
            $lastSig = (string) ($last['sig'] ?? '');
            $withinWindow = (time() - $lastTs) < 20;
            if ($withinWindow && $lastSig === $currentState['sig']) {
                return false;
            }
        }

        Cache::put($cacheKey, $currentState, now()->addHours(12));
        return true;
    }

    private function telemetryRowsFingerprint(array $rows): string
    {
        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalized[] = [
                'ticket' => (string) ($row['ticket'] ?? $row['order'] ?? $row['position'] ?? ''),
                'symbol' => strtoupper((string) ($row['symbol'] ?? '')),
                'type' => strtoupper((string) ($row['type'] ?? '')),
                'lot' => round((float) ($row['lot'] ?? $row['lots'] ?? $row['volume'] ?? 0), 2),
                'price' => round((float) ($row['open_price'] ?? $row['price'] ?? 0), 5),
                'floating' => round((float) ($row['floating'] ?? $row['profit'] ?? 0), 2),
                'swap' => round((float) ($row['swap'] ?? 0), 2),
            ];
        }

        if (count($normalized) > 60) {
            $normalized = array_slice($normalized, 0, 60);
        }

        return md5((string) json_encode($normalized));
    }

    private function pruneStatusReportsIfNeeded(EaConfiguration $configuration): void
    {
        $lockKey = 'ea_status_prune_lock_' . $configuration->account_id;
        if (!Cache::add($lockKey, '1', now()->addMinutes(30))) {
            return;
        }

        try {
            // Hard retention by age.
            EaStatusReport::query()
                ->where('ea_configuration_id', $configuration->id)
                ->where('created_at', '<', now()->subDays(14))
                ->delete();

            // Keep only latest 2000 rows per account to cap table growth.
            $keepRows = 2000;
            $minKeepId = EaStatusReport::query()
                ->where('ea_configuration_id', $configuration->id)
                ->latest('id')
                ->skip($keepRows - 1)
                ->value('id');

            if (is_numeric($minKeepId)) {
                EaStatusReport::query()
                    ->where('ea_configuration_id', $configuration->id)
                    ->where('id', '<', (int) $minKeepId)
                    ->delete();
            }
        } catch (\Throwable) {
            // Keep heartbeat robust even when DB user cannot DELETE rows.
        } finally {
            Cache::forget($lockKey);
        }
    }

    private function persistClosedTrades(EaConfiguration $configuration, array $closedTrades): bool
    {
        $manualCloseDetected = false;
        if ($closedTrades === []) {
            return false;
        }

        // Some EA builds send wrapped payloads: {"trades": [...]}, {"data": [...]}, etc.
        if (isset($closedTrades['trades']) && is_array($closedTrades['trades'])) {
            $closedTrades = $closedTrades['trades'];
        } elseif (isset($closedTrades['data']) && is_array($closedTrades['data'])) {
            $closedTrades = $closedTrades['data'];
        } elseif (isset($closedTrades['items']) && is_array($closedTrades['items'])) {
            $closedTrades = $closedTrades['items'];
        } elseif (isset($closedTrades['history']) && is_array($closedTrades['history'])) {
            $closedTrades = $closedTrades['history'];
        } elseif (isset($closedTrades['closed_trades']) && is_array($closedTrades['closed_trades'])) {
            $closedTrades = $closedTrades['closed_trades'];
        }

        foreach ($closedTrades as $trade) {
            if (!is_array($trade)) {
                continue;
            }

            $ticketText = trim((string) ($trade['ticket'] ?? $trade['order'] ?? ''));
            if ($ticketText === '') {
                $ticketText = 'hash:' . md5(json_encode([
                    $trade['symbol'] ?? '',
                    $trade['type'] ?? '',
                    $trade['close_time'] ?? '',
                    $trade['profit'] ?? 0,
                    $trade['lot'] ?? $trade['volume'] ?? 0,
                ]));
            }

            $openTimeText = trim((string) ($trade['open_time'] ?? ''));
            $closeTimeText = trim((string) ($trade['close_time'] ?? ''));
            $openAt = $this->parseTelemetryTimestamp($openTimeText);
            $closedAt = $this->parseTelemetryTimestamp($closeTimeText);

            $exists = EaClosedTrade::query()
                ->where('account_id', $configuration->account_id)
                ->where('ticket', $ticketText)
                ->exists();

            if (!$exists) {
                EaClosedTrade::query()->create([
                    'account_id' => $configuration->account_id,
                    'ticket' => $ticketText,
                    'user_id' => (int) $configuration->user_id,
                    'ea_configuration_id' => (int) $configuration->id,
                    'symbol' => strtoupper((string) ($trade['symbol'] ?? $configuration->pair_symbol ?? '')),
                    'type' => strtoupper((string) ($trade['type'] ?? '')),
                    'lot' => (float) ($trade['lot'] ?? $trade['volume'] ?? 0),
                    'open_price' => (float) ($trade['open_price'] ?? 0),
                    'close_price' => (float) ($trade['close_price'] ?? 0),
                    'profit' => (float) ($trade['profit'] ?? 0),
                    'swap' => (float) ($trade['swap'] ?? 0),
                    'commission' => (float) ($trade['commission'] ?? 0),
                    'open_time_text' => $openTimeText !== '' ? $openTimeText : null,
                    'close_time_text' => $closeTimeText !== '' ? $closeTimeText : null,
                    'open_at' => $openAt,
                    'closed_at' => $closedAt,
                ]);
            }

            if (!$exists && $this->isManualCloseTrade($trade)) {
                $manualCloseDetected = true;
            }
        }

        return $manualCloseDetected;
    }

    private function isManualCloseTrade(array $trade): bool
    {
        if (array_key_exists('manual', $trade)) {
            $manualRaw = $trade['manual'];
            if (is_bool($manualRaw)) {
                return $manualRaw;
            }
            if (is_numeric($manualRaw)) {
                return (int) $manualRaw === 1;
            }
            if (is_string($manualRaw)) {
                $manualText = strtoupper(trim($manualRaw));
                if (in_array($manualText, ['1', 'TRUE', 'YES', 'Y'], true)) {
                    return true;
                }
            }
        }

        $reason = trim((string) (
            $trade['reason']
            ?? $trade['close_reason']
            ?? $trade['deal_reason']
            ?? $trade['exit_reason']
            ?? $trade['closeReason']
            ?? $trade['dealReason']
            ?? $trade['exitReason']
            ?? ''
        ));

        if ($reason === '') {
            return false;
        }

        if (is_numeric($reason)) {
            return in_array((int) $reason, [0, 1, 2], true);
        }

        $reasonUpper = strtoupper($reason);
        foreach (['MANUAL', 'CLIENT', 'MOBILE', 'WEB', 'USER'] as $needle) {
            if (str_contains($reasonUpper, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function ensureEaApiKey(Request $request): ?JsonResponse
    {
        $expectedApiKey = (string) config('services.ea.api_key', '');

        if ($expectedApiKey === '') {
            return null;
        }

        $providedApiKey = (string) $request->header('X-EA-KEY', $request->header('X-API-Key', ''));

        if (!hash_equals($expectedApiKey, $providedApiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key.',
            ], 401);
        }

        return null;
    }

    private function resolveOrCreateEaConfiguration(Request $request, string $accountId): ?EaConfiguration
    {
        $accountId = trim($accountId);
        if ($accountId === '') {
            return null;
        }

        $pairSymbol = $this->requestedPairSymbol($request) ?? 'XAUUSDC';

        $existing = EaConfiguration::query()
            ->where('account_id', $accountId)
            ->where('pair_symbol', $pairSymbol)
            ->first();
        if ($existing !== null) {
            return $existing;
        }

        if (!(bool) config('services.ea.auto_register_account', true)) {
            return null;
        }

        $requestedUserId = (int) $request->input('user_id', 0);
        $ownerUserId = 0;

        // If this MT5 account already exists on another pair, keep the same owner.
        $existingAccountOwner = EaConfiguration::query()
            ->where('account_id', $accountId)
            ->orderBy('id')
            ->first(['user_id']);
        if ($existingAccountOwner !== null) {
            $ownerUserId = (int) $existingAccountOwner->user_id;
        }

        if ($ownerUserId <= 0 && $requestedUserId > 0 && User::query()->whereKey($requestedUserId)->exists()) {
            $ownerUserId = $requestedUserId;
        }

        if ($ownerUserId <= 0) {
            $defaultUserId = (int) config('services.ea.default_user_id', 0);
            if ($defaultUserId > 0 && User::query()->whereKey($defaultUserId)->exists()) {
                $ownerUserId = $defaultUserId;
            }
        }

        if ($ownerUserId <= 0) {
            $fallbackUser = User::query()->orderBy('id')->first(['id']);
            $ownerUserId = (int) ($fallbackUser->id ?? 0);
        }

        if ($ownerUserId <= 0) {
            return null;
        }

        $accountCurrency = strtoupper(trim((string) $request->input('account_currency', 'USD')));
        if ($accountCurrency === '') {
            $accountCurrency = 'USD';
        }

        $payload = [
            'user_id' => $ownerUserId,
            'account_id' => $accountId,
            'pair_symbol' => $pairSymbol,
            'base_lot' => 0.01,
            'atr_multiplier' => 1.5,
            'grid_mode' => 1,
            'max_drawdown_pct' => 10,
            'timeframe_logic' => 5,
            'fix_grid_distance' => 300,
            'grid_tp_mode' => 0,
            'mart_type' => 0,
            'grid_tier1_tp_percent' => 60,
            'grid_tier2_tp_percent' => 60,
            'grid_tier3_tp_percent' => 60,
            'grid_tier4_tp_percent' => 55,
            'use_friday_market_close_window' => true,
            'always_in_market' => true,
            'instant_reentry' => true,
            'news_pause_before_minutes' => 10,
            'news_pause_after_minutes' => 10,
        ];

        if ($this->isHighSpreadCryptoPair($pairSymbol)) {
            $payload = array_merge($payload, [
                'grid_mode' => 0,
                'atr_multiplier' => 0.40,
                'max_spread' => 90000,
                'fix_grid_distance' => 20000,
                'min_grid_distance' => 20000,
                'grid_max_layers' => 4,
                'grid_max_accumulative_lot' => 1.2,
                'grid_tier1_tp_percent' => 45,
                'grid_tier2_tp_percent' => 50,
                'grid_tier3_tp_percent' => 55,
                'grid_tier4_tp_percent' => 60,
            ]);
        }

        if ($this->hasEaConfigCurrencyColumn()) {
            $payload['account_currency'] = $accountCurrency;
        }

        return EaConfiguration::query()->firstOrCreate(
            [
                'account_id' => $accountId,
                'pair_symbol' => $pairSymbol,
            ],
            $payload
        );
    }

    private function requestedPairSymbol(Request $request): ?string
    {
        $rawPair = trim((string) $request->input('pair_symbol', $request->query('pair_symbol', '')));
        if ($rawPair === '') {
            $rawPair = trim((string) $request->input('symbol', $request->query('symbol', '')));
        }
        return $this->normalizePairSymbol($rawPair);
    }

    private function normalizePairSymbol(?string $value): ?string
    {
        $raw = strtoupper(trim((string) $value));
        if ($raw === '') {
            return null;
        }

        $normalized = preg_replace('/[^A-Z0-9_\/\.\-]/', '', $raw);
        $normalized = is_string($normalized) ? trim($normalized) : '';

        return $normalized !== '' ? $normalized : null;
    }

    private function isHighSpreadCryptoPair(string $pairSymbol): bool
    {
        $normalized = strtoupper(preg_replace('/[^A-Z]/', '', $pairSymbol) ?? '');
        return str_starts_with($normalized, 'BTC') || str_starts_with($normalized, 'ETH');
    }

    private function hasEaConfigCurrencyColumn(): bool
    {
        if (self::$hasEaConfigCurrencyColumn === null) {
            self::$hasEaConfigCurrencyColumn = Schema::hasColumn('ea_configurations', 'account_currency');
        }

        return self::$hasEaConfigCurrencyColumn;
    }

    private function hasEaReportCurrencyColumn(): bool
    {
        if (self::$hasEaReportCurrencyColumn === null) {
            self::$hasEaReportCurrencyColumn = Schema::hasColumn('ea_status_reports', 'account_currency');
        }

        return self::$hasEaReportCurrencyColumn;
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

    private function readTelemetryFloat(Request $request, array $keys): ?float
    {
        foreach ($keys as $key) {
            $value = $request->input($key);
            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }

    private function readTelemetryInt(Request $request, array $keys): ?int
    {
        foreach ($keys as $key) {
            $value = $request->input($key);
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    private function readTelemetryString(Request $request, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $request->input($key);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function sumLots(array $rows): float
    {
        $total = 0.0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $value = $row['lot'] ?? $row['lots'] ?? $row['volume'] ?? 0;
            if (is_numeric($value)) {
                $total += (float) $value;
            }
        }

        return $total;
    }

    private function sumFloating(array $rows): float
    {
        $total = 0.0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $profit = $row['floating'] ?? $row['profit'] ?? $row['current_profit'] ?? 0;
            $swap = $row['swap'] ?? 0;
            if (is_numeric($profit)) {
                $total += (float) $profit;
            }
            if (is_numeric($swap)) {
                $total += (float) $swap;
            }
        }

        return $total;
    }

    private function signalCacheKey(string $accountId, ?string $pairSymbol = null): string
    {
        $normalizedAccount = trim($accountId);
        $rawPair = strtoupper((string) ($pairSymbol ?? ''));
        $normalizedPair = preg_replace('/[^A-Z0-9]/', '', $rawPair) ?? '';
        if ($normalizedPair !== '') {
            return 'ea:signal:' . $normalizedAccount . ':' . $normalizedPair;
        }

        return 'ea:signal:' . $normalizedAccount;
    }

    private function storeSignalSnapshot(
        Request $request,
        EaConfiguration $configuration,
        string $commandedGuardStatus,
        string $reportedGuardStatus
    ): void {
        $bullVotes = max(0, (int) ($this->readTelemetryInt($request, ['bull_votes']) ?? 0));
        $bearVotes = max(0, (int) ($this->readTelemetryInt($request, ['bear_votes']) ?? 0));
        $scoreBuy = max(0, (int) ($this->readTelemetryInt($request, ['score_buy']) ?? 0));
        $scoreSell = max(0, (int) ($this->readTelemetryInt($request, ['score_sell']) ?? 0));

        $voteTotal = $bullVotes + $bearVotes;
        $scoreTotal = $scoreBuy + $scoreSell;
        $votePowerPct = $voteTotal > 0 ? (abs($bullVotes - $bearVotes) / $voteTotal) * 100.0 : 0.0;
        $scorePowerPct = $scoreTotal > 0 ? (abs($scoreBuy - $scoreSell) / $scoreTotal) * 100.0 : 0.0;
        $mtfBias = $this->sanitizeMtfBias($request->input('mtf_bias'));
        $mtfSummary = is_array($mtfBias['summary'] ?? null) ? $mtfBias['summary'] : [];
        $mtfSummaryBias = strtoupper(trim((string) ($mtfSummary['bias'] ?? 'NEUTRAL')));
        $mtfSummaryScore = max(0.0, min(100.0, (float) ($mtfSummary['score'] ?? 0.0)));

        $rawBias = 'NEUTRAL';
        if ($bullVotes > $bearVotes || $scoreBuy > $scoreSell) {
            $rawBias = 'BULLISH';
        } elseif ($bearVotes > $bullVotes || $scoreSell > $scoreBuy) {
            $rawBias = 'BEARISH';
        }

        if (in_array($mtfSummaryBias, ['BULLISH', 'BEARISH'], true) && $mtfSummaryScore >= 55.0) {
            if ($rawBias === 'NEUTRAL' || $rawBias === $mtfSummaryBias) {
                $rawBias = $mtfSummaryBias;
            } elseif ($mtfSummaryScore >= 70.0) {
                $rawBias = $mtfSummaryBias;
            }
        }

        $spreadPoints = $this->readTelemetryFloat($request, ['spread_points', 'spread', 'spread_now', 'spread_pts']);
        $atrPoints = $this->readTelemetryFloat($request, ['atr_points', 'atr_pts']);
        $spreadAtrRatio = $this->readTelemetryFloat($request, ['spread_atr_ratio', 'spread_atr', 'spread_to_atr_ratio']);
        if ($spreadAtrRatio === null && $spreadPoints !== null && $atrPoints !== null && $atrPoints > 0.0) {
            $spreadAtrRatio = $spreadPoints / $atrPoints;
        }

        $spreadIsExpensiveRaw = $request->input('spread_is_expensive', $request->input('spread_expensive', $request->input('is_spread_expensive', false)));
        $spreadIsExpensive = filter_var($spreadIsExpensiveRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($spreadIsExpensive === null) {
            $spreadIsExpensive = false;
        }
        if (!$spreadIsExpensive && $spreadAtrRatio !== null) {
            $spreadIsExpensive = $spreadAtrRatio >= 1.2;
        }

        $rawPowerPct = round((($votePowerPct * 0.6) + ($scorePowerPct * 0.4)) * 0.7 + ($mtfSummaryScore * 0.3), 2);

        $prevCached = Cache::get($this->signalCacheKey((string) $configuration->account_id, (string) ($configuration->pair_symbol ?? '')));
        $previousSnapshot = is_array($prevCached) ? $prevCached : [];
        $resolveStableStatus = function (array $keys, string $snapshotKey) use ($request, $previousSnapshot): string {
            $current = trim((string) ($this->readTelemetryString($request, $keys) ?? ''));
            $currentUpper = strtoupper($current);
            if ($current !== '' && !in_array($currentUpper, ['-', 'N/A', 'NA', 'NONE', 'NULL', 'UNKNOWN'], true)) {
                return $current;
            }

            $previous = trim((string) ($previousSnapshot[$snapshotKey] ?? ''));
            $previousUpper = strtoupper($previous);
            if ($previous !== '' && !in_array($previousUpper, ['-', 'N/A', 'NA', 'NONE', 'NULL', 'UNKNOWN'], true)) {
                return $previous;
            }

            return 'NO DATA';
        };

        $stableDxyStatus = $resolveStableStatus(['dxy_status'], 'dxy_status');
        $stableMicroStatus = $resolveStableStatus(['micro_market_status'], 'micro_market_status');
        $stableLearningStatus = $resolveStableStatus(['current_learning_status', 'learning_status'], 'learning_status');

        $previousPower = is_numeric($previousSnapshot['power_pct'] ?? null)
            ? (float) $previousSnapshot['power_pct']
            : $rawPowerPct;
        $smoothedPowerPct = round(($previousPower * 0.7) + ($rawPowerPct * 0.3), 2);

        $previousBias = strtoupper(trim((string) ($previousSnapshot['bias'] ?? '')));
        $bias = $rawBias;
        if ($previousBias !== '' && in_array($previousBias, ['BULLISH', 'BEARISH', 'NEUTRAL'], true) && $previousBias !== $rawBias) {
            $voteDelta = abs($bullVotes - $bearVotes);
            $scoreDelta = abs($scoreBuy - $scoreSell);
            if ($smoothedPowerPct < 62.5 && $voteDelta <= 1 && $scoreDelta <= 1) {
                $bias = $previousBias;
            }
        }

        $remotePaused = $this->normalizeBooleanInput(
            $request->input('remote_paused', $request->input('remote_pause', false)),
            false
        );

        $adx = $this->readTelemetryFloat($request, ['adx']);

        if ($this->isMtfBiasPlaceholder($mtfBias)) {
            $mtfBias = $this->buildDerivedMtfBias(
                $rawBias,
                $adx,
                $scoreBuy,
                $scoreSell,
                $bullVotes,
                $bearVotes,
                $stableMicroStatus
            );
            $mtfSummary = is_array($mtfBias['summary'] ?? null) ? $mtfBias['summary'] : [];
            $mtfSummaryBias = strtoupper(trim((string) ($mtfSummary['bias'] ?? 'NEUTRAL')));
            $mtfSummaryScore = max(0.0, min(100.0, (float) ($mtfSummary['score'] ?? 0.0)));
        }

        $confidencePct = $this->calculateSignalConfidence(
            $bullVotes,
            $bearVotes,
            $scoreBuy,
            $scoreSell,
            $adx,
            (bool) $spreadIsExpensive,
            $this->isNewsBlockedNow($configuration),
            $remotePaused
        );

        $reasonPack = $this->buildSignalReasonSummary(
            $bias,
            $scoreBuy,
            $scoreSell,
            $bullVotes,
            $bearVotes,
            $adx,
            (bool) $spreadIsExpensive,
            $this->isNewsBlockedNow($configuration),
            $remotePaused,
            $stableMicroStatus
        );

        if (!empty($mtfBias)) {
            $reasonPack['details'][] = sprintf(
                'Multi-TF summary: %s (score %.2f).',
                $mtfSummaryBias,
                $mtfSummaryScore
            );

            if (is_string($mtfSummary['reason'] ?? null) && trim((string) $mtfSummary['reason']) !== '') {
                $reasonPack['details'][] = 'Multi-TF reason: ' . trim((string) $mtfSummary['reason']) . '.';
            }
        }

        $snapshot = [
            'captured_at' => Carbon::now()->toIso8601String(),
            'account_id' => (string) $configuration->account_id,
            'symbol' => strtoupper(trim((string) $request->input('symbol', $configuration->pair_symbol ?? ''))),
            'active_strategy' => (int) ($this->readTelemetryInt($request, ['active_mode']) ?? $configuration->active_strategy ?? 0),
            'strategy_name' => (string) $request->input('active_strategy', ''),
            'timeframe_logic' => (int) ($configuration->timeframe_logic ?? 1),
            'raw_bias' => $rawBias,
            'bias' => $bias,
            'power_raw_pct' => $rawPowerPct,
            'power_pct' => $smoothedPowerPct,
            'confidence_pct' => $confidencePct,
            'vote_power_pct' => round($votePowerPct, 2),
            'score_power_pct' => round($scorePowerPct, 2),
            'stability_pct' => max(0.0, min(100.0, round(($smoothedPowerPct * 0.6) + ($confidencePct * 0.4), 2))),
            'bull_votes' => $bullVotes,
            'bear_votes' => $bearVotes,
            'score_buy' => $scoreBuy,
            'score_sell' => $scoreSell,
            'adx' => $adx,
            'support_level' => $this->readTelemetryFloat($request, ['support_level']),
            'resistance_level' => $this->readTelemetryFloat($request, ['resistance_level']),
            'dxy_status' => $stableDxyStatus,
            'micro_market_status' => $stableMicroStatus,
            'learning_status' => $stableLearningStatus,
            'signal_wait_seconds' => max(0, (int) ($this->readTelemetryInt($request, ['signal_wait_seconds']) ?? 0)),
            'api_queue_depth' => max(0, (int) ($this->readTelemetryInt($request, ['api_queue_depth']) ?? 0)),
            'spread_points' => $spreadPoints,
            'atr_points' => $atrPoints,
            'spread_atr_ratio' => $spreadAtrRatio,
            'spread_is_expensive' => (bool) $spreadIsExpensive,
            'guard_status_commanded' => (string) $commandedGuardStatus,
            'guard_status_live' => (string) $reportedGuardStatus,
            'remote_paused' => $remotePaused,
            'news_blocked' => $this->isNewsBlockedNow($configuration),
            'mtf_bias' => $mtfBias,
            'mtf_summary_bias' => $mtfSummaryBias,
            'mtf_summary_score' => $mtfSummaryScore,
            'reason_summary' => $reasonPack['summary'],
            'reason_details' => $reasonPack['details'],
            'sessions' => [
                'use_sydney_session' => (bool) $configuration->use_sydney_session,
                'use_asia_session' => (bool) $configuration->use_asia_session,
                'use_europe_session' => (bool) $configuration->use_europe_session,
                'use_us_session' => (bool) $configuration->use_us_session,
            ],
            'server_time' => (string) ($request->input('server_time') ?: Carbon::now()->toIso8601String()),
        ];

        Cache::put(
            $this->signalCacheKey((string) $configuration->account_id, (string) ($configuration->pair_symbol ?? '')),
            $snapshot,
            now()->addHours(8)
        );
    }

    private function readSignalSnapshot(EaConfiguration $configuration): array
    {
        $cached = Cache::get($this->signalCacheKey((string) $configuration->account_id, (string) ($configuration->pair_symbol ?? '')));
        $snapshot = is_array($cached) ? $cached : [];

        if (!isset($snapshot['captured_at'])) {
            $snapshot['captured_at'] = optional($configuration->updated_at)?->toIso8601String();
        }

        $snapshot['account_id'] = (string) $configuration->account_id;
        $snapshot['active_strategy'] = (int) ($snapshot['active_strategy'] ?? $configuration->active_strategy ?? 0);
        $snapshot['timeframe_logic'] = (int) ($snapshot['timeframe_logic'] ?? $configuration->timeframe_logic ?? 1);
        $snapshot['guard_status_commanded'] = (string) ($snapshot['guard_status_commanded'] ?? $configuration->guard_status ?? 'LIVE');
        $snapshot['guard_status_live'] = (string) ($snapshot['guard_status_live'] ?? $configuration->live_guard_status ?? $configuration->guard_status ?? 'LIVE');
        $snapshot['news_blocked'] = (bool) ($snapshot['news_blocked'] ?? $this->isNewsBlockedNow($configuration));
        $snapshot['mtf_bias'] = $this->sanitizeMtfBias($snapshot['mtf_bias'] ?? []);
        $snapshot['mtf_summary_bias'] = strtoupper(trim((string) ($snapshot['mtf_summary_bias'] ?? data_get($snapshot['mtf_bias'], 'summary.bias', 'NEUTRAL'))));
        $snapshot['mtf_summary_score'] = max(0.0, min(100.0, (float) ($snapshot['mtf_summary_score'] ?? data_get($snapshot['mtf_bias'], 'summary.score', 0.0))));
        $sessionSnapshot = is_array($snapshot['sessions'] ?? null) ? $snapshot['sessions'] : [];
        $snapshot['sessions'] = [
            'use_sydney_session' => (bool) ($sessionSnapshot['use_sydney_session'] ?? $configuration->use_sydney_session),
            'use_asia_session' => (bool) ($sessionSnapshot['use_asia_session'] ?? $configuration->use_asia_session),
            'use_europe_session' => (bool) ($sessionSnapshot['use_europe_session'] ?? $configuration->use_europe_session),
            'use_us_session' => (bool) ($sessionSnapshot['use_us_session'] ?? $configuration->use_us_session),
        ];

        $bullVotes = max(0, (int) ($snapshot['bull_votes'] ?? 0));
        $bearVotes = max(0, (int) ($snapshot['bear_votes'] ?? 0));
        $scoreBuy = max(0, (int) ($snapshot['score_buy'] ?? 0));
        $scoreSell = max(0, (int) ($snapshot['score_sell'] ?? 0));

        // Fallback: when engine score is still 0/0 but vote telemetry already exists,
        // use votes as temporary confluence score so dashboard doesn't look blank.
        $usingVoteFallback = false;
        if ($scoreBuy === 0 && $scoreSell === 0 && ($bullVotes > 0 || $bearVotes > 0)) {
            $scoreBuy = min(10, $bullVotes);
            $scoreSell = min(10, $bearVotes);
            $usingVoteFallback = true;
        }

        $snapshot['bull_votes'] = $bullVotes;
        $snapshot['bear_votes'] = $bearVotes;
        $snapshot['score_buy'] = $scoreBuy;
        $snapshot['score_sell'] = $scoreSell;
        $snapshot['score_source'] = $usingVoteFallback ? 'votes_fallback' : (string) ($snapshot['score_source'] ?? 'engine');
        $snapshot['power_pct'] = is_numeric($snapshot['power_pct'] ?? null)
            ? (float) $snapshot['power_pct']
            : 0.0;
        $snapshot['confidence_pct'] = is_numeric($snapshot['confidence_pct'] ?? null)
            ? (float) $snapshot['confidence_pct']
            : 0.0;
        $snapshot['stability_pct'] = is_numeric($snapshot['stability_pct'] ?? null)
            ? (float) $snapshot['stability_pct']
            : round(($snapshot['power_pct'] * 0.6) + ($snapshot['confidence_pct'] * 0.4), 2);

        if (!isset($snapshot['reason_summary'])) {
            $reasonPack = $this->buildSignalReasonSummary(
                (string) ($snapshot['bias'] ?? 'NEUTRAL'),
                $scoreBuy,
                $scoreSell,
                $bullVotes,
                $bearVotes,
                is_numeric($snapshot['adx'] ?? null) ? (float) $snapshot['adx'] : null,
                (bool) ($snapshot['spread_is_expensive'] ?? false),
                (bool) ($snapshot['news_blocked'] ?? false),
                $this->normalizeBooleanInput($snapshot['remote_paused'] ?? false, false),
                (string) ($snapshot['micro_market_status'] ?? '-')
            );
            $snapshot['reason_summary'] = $reasonPack['summary'];
            $snapshot['reason_details'] = $reasonPack['details'];
        }

        $capturedAtText = trim((string) ($snapshot['captured_at'] ?? ''));
        if ($capturedAtText !== '') {
            try {
                $snapshot['age_seconds'] = max(0, (int) Carbon::parse($capturedAtText)->diffInSeconds(Carbon::now()));
            } catch (\Throwable) {
                $snapshot['age_seconds'] = null;
            }
        } else {
            $snapshot['age_seconds'] = null;
        }

        return $snapshot;
    }

    private function isMtfBiasPlaceholder(array $mtfBias): bool
    {
        if (empty($mtfBias)) {
            return true;
        }

        $summaryScore = (float) data_get($mtfBias, 'summary.score', 0.0);
        $nodes = [
            data_get($mtfBias, 'm1', []),
            data_get($mtfBias, 'm5', []),
            data_get($mtfBias, 'm15', []),
            data_get($mtfBias, 'h1', []),
        ];

        $hasMeaningfulNode = false;
        foreach ($nodes as $node) {
            if (!is_array($node) || $node === []) {
                continue;
            }

            $nodeScore = (float) ($node['score'] ?? 0.0);
            $nodeReason = trim((string) ($node['reason'] ?? ''));
            if ($nodeScore > 0.0 || $nodeReason !== '') {
                $hasMeaningfulNode = true;
                break;
            }
        }

        return $summaryScore <= 0.0 && !$hasMeaningfulNode;
    }

    private function buildDerivedMtfBias(
        string $rawBias,
        ?float $adx,
        int $scoreBuy,
        int $scoreSell,
        int $bullVotes,
        int $bearVotes,
        string $microMarket
    ): array {
        $bias = strtoupper(trim($rawBias));
        if (!in_array($bias, ['BULLISH', 'BEARISH', 'NEUTRAL'], true)) {
            $bias = 'NEUTRAL';
        }

        $voteDelta = abs($bullVotes - $bearVotes);
        $scoreDelta = abs($scoreBuy - $scoreSell);
        if ($bias === 'NEUTRAL' && $voteDelta >= 2) {
            $bias = $bullVotes > $bearVotes ? 'BULLISH' : 'BEARISH';
        }

        $base = 36.0 + ($scoreDelta * 3.0) + ($voteDelta * 5.0);
        if ($adx !== null) {
            $base += max(0.0, min(18.0, $adx - 14.0));
        }
        $summaryScore = max(20.0, min(92.0, round($base, 2)));

        $rsiBase = $bias === 'BULLISH' ? 56.0 : ($bias === 'BEARISH' ? 44.0 : 50.0);
        $adxBase = $adx ?? 18.0;

        $buildNode = static function (string $nodeBias, float $nodeScore, float $nodeAdx, float $nodeRsi, string $reason): array {
            return [
                'bias' => $nodeBias,
                'score' => max(0.0, min(100.0, round($nodeScore, 2))),
                'adx' => round($nodeAdx, 2),
                'rsi' => round($nodeRsi, 2),
                'reason' => $reason,
            ];
        };

        $m1Bias = $bias;
        $m5Bias = $bias;
        $m15Bias = ($summaryScore >= 48.0) ? $bias : 'NEUTRAL';
        $h1Bias = ($summaryScore >= 58.0) ? $bias : 'NEUTRAL';

        $summaryReason = sprintf(
            'Derived fallback from vote %d/%d, score %d/%d, ADX %s, micro %s.',
            $bullVotes,
            $bearVotes,
            $scoreBuy,
            $scoreSell,
            $adx !== null ? number_format($adx, 2, '.', '') : 'N/A',
            trim($microMarket) !== '' ? $microMarket : '-'
        );

        return [
            'm1' => $buildNode($m1Bias, $summaryScore + 4.0, $adxBase + 1.5, $rsiBase + ($bias === 'BULLISH' ? 1.0 : ($bias === 'BEARISH' ? -1.0 : 0.0)), 'Lower TF impulse from vote/score confluence.'),
            'm5' => $buildNode($m5Bias, $summaryScore + 1.5, $adxBase + 0.8, $rsiBase, 'Primary execution TF weighted by confluence stability.'),
            'm15' => $buildNode($m15Bias, $summaryScore - 2.0, $adxBase, $rsiBase + ($bias === 'NEUTRAL' ? 0.0 : ($bias === 'BULLISH' ? 0.5 : -0.5)), 'Mid TF confirmation from score-vote direction.'),
            'h1' => $buildNode($h1Bias, $summaryScore - 6.0, max(12.0, $adxBase - 1.0), $rsiBase, 'Higher TF alignment inferred from persistent bias.'),
            'summary' => [
                'bias' => $bias,
                'score' => $summaryScore,
                'reason' => $summaryReason,
                'bull_weight' => $bias === 'BULLISH' ? round($summaryScore * 1.1, 2) : round(max(0.0, $summaryScore * 0.45), 2),
                'bear_weight' => $bias === 'BEARISH' ? round($summaryScore * 1.1, 2) : round(max(0.0, $summaryScore * 0.45), 2),
            ],
        ];
    }

    private function sanitizeMtfBias(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $safeTfNode = static function (mixed $node): array {
            if (!is_array($node)) {
                return [];
            }

            $bias = strtoupper(trim((string) ($node['bias'] ?? 'NEUTRAL')));
            if (!in_array($bias, ['BULLISH', 'BEARISH', 'NEUTRAL'], true)) {
                $bias = 'NEUTRAL';
            }

            return [
                'bias' => $bias,
                'score' => max(0.0, min(100.0, (float) ($node['score'] ?? 0.0))),
                'adx' => is_numeric($node['adx'] ?? null) ? (float) $node['adx'] : null,
                'rsi' => is_numeric($node['rsi'] ?? null) ? (float) $node['rsi'] : null,
                'reason' => trim((string) ($node['reason'] ?? '')),
            ];
        };

        $summary = is_array($raw['summary'] ?? null) ? $raw['summary'] : [];
        $summaryBias = strtoupper(trim((string) ($summary['bias'] ?? 'NEUTRAL')));
        if (!in_array($summaryBias, ['BULLISH', 'BEARISH', 'NEUTRAL'], true)) {
            $summaryBias = 'NEUTRAL';
        }

        return [
            'm1' => $safeTfNode($raw['m1'] ?? []),
            'm5' => $safeTfNode($raw['m5'] ?? []),
            'm15' => $safeTfNode($raw['m15'] ?? []),
            'h1' => $safeTfNode($raw['h1'] ?? []),
            'summary' => [
                'bias' => $summaryBias,
                'score' => max(0.0, min(100.0, (float) ($summary['score'] ?? 0.0))),
                'reason' => trim((string) ($summary['reason'] ?? '')),
                'bull_weight' => is_numeric($summary['bull_weight'] ?? null) ? (float) $summary['bull_weight'] : null,
                'bear_weight' => is_numeric($summary['bear_weight'] ?? null) ? (float) $summary['bear_weight'] : null,
            ],
        ];
    }

    private function normalizeBooleanInput(mixed $value, bool $default = false): bool
    {
        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($parsed === null) {
            return $default;
        }

        return (bool) $parsed;
    }

    private function calculateSignalConfidence(
        int $bullVotes,
        int $bearVotes,
        int $scoreBuy,
        int $scoreSell,
        ?float $adx,
        bool $spreadIsExpensive,
        bool $newsBlocked,
        bool $remotePaused
    ): float {
        $voteDirection = $bullVotes <=> $bearVotes;
        $scoreDirection = $scoreBuy <=> $scoreSell;
        $agreement = ($voteDirection !== 0 && $voteDirection === $scoreDirection) ? 22.0 : (($voteDirection === 0 && $scoreDirection === 0) ? 8.0 : 0.0);

        $adxBoost = 0.0;
        if ($adx !== null) {
            if ($adx >= 25.0) {
                $adxBoost = 22.0;
            } elseif ($adx >= 20.0) {
                $adxBoost = 12.0;
            } elseif ($adx >= 15.0) {
                $adxBoost = 6.0;
            }
        }

        $voteTotal = max(1, $bullVotes + $bearVotes);
        $scoreTotal = max(1, $scoreBuy + $scoreSell);
        $voteConviction = (abs($bullVotes - $bearVotes) / $voteTotal) * 26.0;
        $scoreConviction = (abs($scoreBuy - $scoreSell) / $scoreTotal) * 30.0;

        $penalty = 0.0;
        if ($spreadIsExpensive) {
            $penalty += 8.0;
        }
        if ($newsBlocked) {
            $penalty += 12.0;
        }
        if ($remotePaused) {
            $penalty += 18.0;
        }

        $confidence = 18.0 + $agreement + $voteConviction + $scoreConviction + $adxBoost - $penalty;
        return max(0.0, min(100.0, round($confidence, 2)));
    }

    private function buildSignalReasonSummary(
        string $bias,
        int $scoreBuy,
        int $scoreSell,
        int $bullVotes,
        int $bearVotes,
        ?float $adx,
        bool $spreadIsExpensive,
        bool $newsBlocked,
        bool $remotePaused,
        string $microMarket
    ): array {
        $biasUpper = strtoupper(trim($bias));
        if (!in_array($biasUpper, ['BULLISH', 'BEARISH'], true)) {
            $biasUpper = 'NEUTRAL';
        }

        $details = [];
        $details[] = sprintf('Skor BUY/SELL %d/%d, vote Bull/Bear %d/%d.', $scoreBuy, $scoreSell, $bullVotes, $bearVotes);

        if ($adx !== null) {
            $details[] = sprintf('ADX %.2f (%s).', $adx, $adx >= 25.0 ? 'trend kuat' : ($adx >= 20.0 ? 'trend mulai valid' : 'trend masih lemah'));
        }

        if ($spreadIsExpensive) {
            $details[] = 'Spread sedang mahal, eksekusi dipersempit.';
        }
        if ($newsBlocked) {
            $details[] = 'News block aktif, entry ditahan sementara.';
        }
        if ($remotePaused) {
            $details[] = 'Remote pause aktif dari dashboard.';
        }

        if ($microMarket !== '' && $microMarket !== '-') {
            $details[] = 'Kondisi mikro market: ' . $microMarket . '.';
        }

        $summary = match ($biasUpper) {
            'BULLISH' => 'Bias bullish lebih dominan karena skor dan vote lebih mendukung arah naik.',
            'BEARISH' => 'Bias bearish lebih dominan karena skor dan vote lebih mendukung arah turun.',
            default => 'Bias netral karena skor dan vote masih berimbang, belum ada konfirmasi arah yang cukup kuat.',
        };

        return [
            'summary' => $summary,
            'details' => array_values(array_unique($details)),
        ];
    }

    private function ensureAdmin(Request $request): ?JsonResponse
    {
        $role = (string) ($request->user()->role ?? '');
        if (!$request->user() || (!($request->user()->is_admin) && $role !== 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Admin only.',
            ], 403);
        }

        return null;
    }

    private function ensureStaff(Request $request): ?JsonResponse
    {
        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $isStaff = (bool) ($user && ($user->is_admin || in_array($role, ['admin', 'manager'], true)));

        if (!$isStaff) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Staff only.',
            ], 403);
        }

        return null;
    }

    private function ddBreachHitsRequiredKey(EaConfiguration $configuration): string
    {
        return 'dd_breach_hits_required_user_' . $configuration->user_id . '_account_' . $configuration->account_id;
    }

    private function getDdBreachHitsRequired(EaConfiguration $configuration): int
    {
        $value = Cache::get($this->ddBreachHitsRequiredKey($configuration), 15);
        $hits = (int) $value;
        if ($hits < 1) $hits = 1;
        if ($hits > 120) $hits = 120;

        return $hits;
    }

    private function setDdBreachHitsRequired(EaConfiguration $configuration, int $hits): void
    {
        $safe = max(1, min(120, $hits));
        Cache::put($this->ddBreachHitsRequiredKey($configuration), $safe, now()->addDays(365));
    }

    private function ensureBooleanFieldsCast(EaConfiguration $configuration): void
    {
        // Safeguard: Re-cast all boolean fields to ensure correct storage.
        // This protects against JSON serialization quirks (true/false vs 1/0 mismatch).
        $booleanFields = [
            'use_martingale', 'use_breakeven', 'always_in_market', 'instant_reentry',
            'auto_flip', 'use_pending_guard', 'use_trend_filter', 'use_ai_core_sharpening',
            'use_ema_ribbon', 'use_dmi', 'use_mkt_struct', 'use_early_trend', 'use_sniper_entry',
            'show_indicator_fallback_logs',
            'use_stealth_mode', 'use_dxy_filter', 'use_us10y_filter', 'use_vix_filter',
            'use_oil_filter', 'use_friday_market_close_window',
            'use_sydney_session', 'use_asia_session',
            'use_europe_session', 'use_us_session', 'close_all_on_news', 'filter_snr_activation',
            'grid_use_trailing_layer1', 'grid_use_basket_tp_percent'
        ];

        foreach ($booleanFields as $field) {
            if ($configuration->offsetExists($field)) {
                $val = $configuration->getAttribute($field);
                // Cast to bool: null/false/0/"0"/"false" → false; everything else → true if truthy
                $safeVal = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $configuration->setAttribute($field, $safeVal === null ? false : $safeVal);
            }
        }
    }

    public function closeAllPositions(Request $request): JsonResponse
    {
        $accountId = trim((string) $request->input('account_id', ''));
        $reason = (string) $request->input('reason', 'Manual close all positions');

        if ($accountId === '') {
            return response()->json([
                'success' => false,
                'message' => 'Account ID is required.',
            ], 422);
        }

        $configuration = EaConfiguration::where('account_id', $accountId)->first();

        if (!$configuration) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration not found for this account.',
            ], 404);
        }

        $licenseStatus = $this->licenseService->getStatusForConfiguration($configuration);
        $licenseRuntime = $this->licenseService->getRuntimeStatusForConfiguration(
            $configuration,
            (int) ($configuration->current_layers ?? 0),
            (float) ($configuration->current_accumulative_lot ?? 0)
        );
        if ($this->licenseService->isEnforcementEnabled() && !(bool) ($licenseRuntime['license_can_manage_existing_cycle'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Lisensi account tidak aktif. Aksi close all diblokir.',
                'license' => array_merge($licenseStatus, $licenseRuntime),
            ], 403);
        }

        // Queue one-shot CLOSE_ALL command to EA; do not persist guard_status to
        // CLOSING_ALL because that can keep trading disabled after all positions close.
        Cache::put($this->signalCacheKey((string) $configuration->account_id, (string) ($configuration->pair_symbol ?? '')), [
            'action' => 'CLOSE_ALL',
            'source' => 'close_all_positions_api',
            'reason' => $reason,
            'updated_at' => Carbon::now()->toIso8601String(),
        ], now()->addMinutes(3));

        $configuration->update([
            'updated_at' => Carbon::now(),
        ]);

        // Log the action
        \Log::info("Close all positions signal queued for account {$accountId}. Reason: {$reason}");

        return response()->json([
            'success' => true,
            'message' => 'Close all positions signal queued to EA.',
            'account_id' => (string) $accountId,
        ], 200);
    }

    /**
     * Get economic calendar from ForexFactory
     * Returns all high-impact USD events for next 14 days
     */
    public function getEconomicCalendar(Request $request): JsonResponse
    {
        $events = $this->calendarService->getHighImpactEvents();
        $providerKey = $this->calendarService->providerKey();

        return response()->json([
            'success' => true,
            'count' => count($events),
            'events' => $events,
            'provider_key' => $providerKey,
            'provider_label' => $this->calendarService->providerLabel(),
            'has_finnhub_api_key' => $this->calendarService->hasFinnhubApiKey(),
            'timestamp' => now()->toDateTimeString(),
        ], 200);
    }

    /**
     * Get next high-impact USD economic event
     * Lightweight endpoint for EA polling
     */
    public function getNextHighImpactNews(Request $request): JsonResponse
    {
        $nextEvent = $this->calendarService->getNextHighImpactUsdEvent();

        if (!$nextEvent) {
            return response()->json([
                'success' => true,
                'has_event' => false,
                'event' => null,
                'timestamp' => now()->toDateTimeString(),
            ], 200);
        }

        // Calculate seconds until event
        $secondsUntil = max(0, $nextEvent['time'] - now()->timestamp);

        return response()->json([
            'success' => true,
            'has_event' => true,
            'event' => [
                'time' => $nextEvent['time'],
                'time_formatted' => $nextEvent['time_formatted'],
                'country' => $nextEvent['country'],
                'event_name' => $nextEvent['event'],
                'seconds_until' => $secondsUntil,
                'minutes_until' => (int)ceil($secondsUntil / 60),
            ],
            'timestamp' => now()->toDateTimeString(),
        ], 200);
    }
}
