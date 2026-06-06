<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Api\V1\EaController as DashboardApiController;
use App\Models\DashboardSetting;
use App\Models\EaConfiguration;
use App\Models\EaStatusReport;
use App\Models\EconomicNews;
use App\Models\Mt5RiskConsent;
use App\Models\User;
use App\Services\EconomicCalendarService;
use App\Services\Mt5LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    private const BOOKKEEPING_ENABLED_KEY = 'bookkeeping_enabled';

    private const BOOKKEEPING_USER_WHITELIST_KEY = 'bookkeeping_user_whitelist';

    public function __construct(
        private readonly EconomicCalendarService $calendarService,
        private readonly Mt5LicenseService $licenseService
    )
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $isAdmin = (bool) ($user->is_admin || $role === 'admin');

        $accountsQuery = EaConfiguration::query()->orderBy('account_id');
        if (!$isAdmin) {
            $accountsQuery->where('user_id', $user->id);
        }
        $accounts = $accountsQuery->get();
        $accountIds = $accounts
            ->map(static fn (EaConfiguration $account): string => trim((string) ($account->account_id ?? '')))
            ->filter(static fn (string $accountId): bool => $accountId !== '')
            ->unique()
            ->values();

        $riskConsentAccountMap = Mt5RiskConsent::query()
            ->where('user_id', (int) $user->id)
            ->whereIn('account_id', $accountIds)
            ->pluck('accepted_at', 'account_id')
            ->map(static fn ($acceptedAt): bool => !empty($acceptedAt))
            ->all();

        $licenseSnapshots = [];
        foreach ($accounts as $account) {
            $licenseSnapshots[(string) $account->account_id] = $this->licenseService->getStatusByAccountId((string) $account->account_id);
        }

        $news = $this->buildForexFactoryNewsFeed();

        if ($news->isEmpty()) {
            $this->refreshNewsIfStale();
            $news = $this->buildForexFactoryNewsFeed();
        }

        $managedUsers = collect();
        if ($isAdmin) {
            $managedUsers = User::query()
                ->orderBy('id')
                ->get(['id', 'name', 'username', 'email', 'role', 'is_admin', 'created_at']);
        }

        $bulkControlPolicy = $this->loadBulkControlPolicy();
        $accountAliases = $this->loadAccountAliasMap($isAdmin, $accountIds->all());
        $bookkeepingPolicy = $this->loadBookkeepingTabPolicy();
        $canUseBookkeepingTab = $isAdmin || (
            $bookkeepingPolicy['enabled']
            && in_array((int) $user->id, $bookkeepingPolicy['user_whitelist'], true)
        );

        return view('dashboard', [
            'accounts' => $accounts,
            'news' => $news,
            'currentUser' => $user,
            'isAdmin' => $isAdmin,
            'managedUsers' => $managedUsers,
            'dashboardPatch' => (string) config('app.dashboard_patch', 'v209'),
            'bulkControlWhitelist' => $bulkControlPolicy['whitelist'],
            'bulkControlEnabled' => $bulkControlPolicy['enabled'],
            'accountAliases' => $accountAliases,
            'canUseBookkeepingTab' => $canUseBookkeepingTab,
            'licenseSnapshots' => $licenseSnapshots,
            'licenseEnforcementEnabled' => $this->licenseService->isEnforcementEnabled(),
            'riskConsentAccountMap' => $riskConsentAccountMap,
        ]);
    }

    private function loadBookkeepingTabPolicy(): array
    {
        try {
            $rows = DashboardSetting::query()
                ->whereIn('key', [
                    self::BOOKKEEPING_ENABLED_KEY,
                    self::BOOKKEEPING_USER_WHITELIST_KEY,
                ])
                ->pluck('value', 'key');

            $enabledRaw = $rows->get(self::BOOKKEEPING_ENABLED_KEY);
            $enabledParsed = filter_var($enabledRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $enabled = $enabledParsed === null ? false : (bool) $enabledParsed;

            $whitelistRaw = (string) ($rows->get(self::BOOKKEEPING_USER_WHITELIST_KEY) ?? '');
            $userWhitelist = collect(preg_split('/[\s,;\n\r\t]+/', trim($whitelistRaw)) ?: [])
                ->map(static fn (string $item): int => (int) trim($item))
                ->filter(static fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();

            return [
                'enabled' => $enabled,
                'user_whitelist' => $userWhitelist,
            ];
        } catch (\Throwable) {
            return [
                'enabled' => false,
                'user_whitelist' => [],
            ];
        }
    }

    private function loadAccountAliasMap(bool $isAdmin, array $ownedAccountIds = []): array
    {
        try {
            $raw = (string) (DashboardSetting::query()->where('key', 'account_alias_map')->value('value') ?? '');
            if ($raw === '') {
                return [];
            }

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                return [];
            }

            $sanitized = collect($decoded)
                ->mapWithKeys(static function ($value, $key): array {
                    $accountId = trim((string) $key);
                    $alias = trim((string) $value);
                    if ($accountId === '' || $alias === '') {
                        return [];
                    }

                    return [$accountId => $alias];
                })
                ->all();

            if ($isAdmin) {
                return $sanitized;
            }

            $allowed = collect($ownedAccountIds)
                ->map(static fn ($id): string => trim((string) $id))
                ->filter(static fn (string $id): bool => $id !== '')
                ->values()
                ->all();

            if ($allowed === []) {
                return [];
            }

            return collect($sanitized)
                ->only($allowed)
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function loadBulkControlPolicy(): array
    {
        $defaultEnabled = (bool) config('services.ea.bulk_toggle_enabled', true);
        $defaultWhitelist = $this->parseAccountWhitelist((string) config('services.ea.bulk_toggle_account_whitelist', ''));

        try {
            $rows = DashboardSetting::query()
                ->whereIn('key', ['bulk_toggle_enabled', 'bulk_toggle_account_whitelist'])
                ->pluck('value', 'key');

            $enabledValue = $rows->get('bulk_toggle_enabled');
            $whitelistValue = $rows->get('bulk_toggle_account_whitelist');

            $enabled = $enabledValue === null
                ? $defaultEnabled
                : (bool) filter_var($enabledValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($enabledValue !== null && filter_var($enabledValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
                $enabled = $defaultEnabled;
            }

            $whitelist = $whitelistValue === null
                ? $defaultWhitelist
                : $this->parseAccountWhitelist((string) $whitelistValue);

            return [
                'enabled' => $enabled,
                'whitelist' => $whitelist,
            ];
        } catch (\Throwable) {
            return [
                'enabled' => $defaultEnabled,
                'whitelist' => $defaultWhitelist,
            ];
        }
    }

    private function parseAccountWhitelist(string $raw): array
    {
        return collect(explode(',', $raw))
            ->map(static fn (string $item): string => trim($item))
            ->filter(static fn (string $item): bool => $item !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function monitoringLive(Request $request, DashboardApiController $apiController): JsonResponse
    {
        return $apiController->myMonitoringLive($request);
    }

    public function reportsLive(Request $request, DashboardApiController $apiController): JsonResponse
    {
        return $apiController->myReportLive($request);
    }

    public function liveStream(Request $request, DashboardApiController $apiController): StreamedResponse|JsonResponse
    {
        $accountId = trim((string) $request->query('account_id', ''));
        if ($accountId === '') {
            return response()->json([
                'success' => false,
                'message' => 'account_id is required.',
            ], 422);
        }

        $pairSymbol = strtoupper(trim((string) $request->query('pair_symbol', '')));

        $calcDebug = $request->boolean('calc_debug');
        $limit = max(5, (int) $request->query('limit', 10));
        $page = max(1, (int) $request->query('page', 1));
        $user = $request->user();
        $userId = (int) ($user->id ?? 0);

        $buildRequest = function (string $path, array $query) use ($user): Request {
            $subRequest = Request::create($path, 'GET', $query);
            $subRequest->setUserResolver(static fn () => $user);

            return $subRequest;
        };

        $monitoringRequest = $buildRequest('/dashboard/monitoring/live', [
            'account_id' => $accountId,
            'pair_symbol' => $pairSymbol,
            'calc_debug' => $calcDebug ? 1 : 0,
        ]);
        $reportRequest = $buildRequest('/dashboard/reports/live', [
            'account_id' => $accountId,
            'pair_symbol' => $pairSymbol,
            'limit' => $limit,
            'page' => $page,
            'calc_debug' => $calcDebug ? 1 : 0,
        ]);

        return response()->stream(function () use ($apiController, $monitoringRequest, $reportRequest, $accountId, $pairSymbol, $limit, $page, $calcDebug, $userId): void {
            @set_time_limit(0);
            @ignore_user_abort(false);

            if (function_exists('ob_get_level')) {
                while (ob_get_level() > 0) {
                    @ob_end_flush();
                }
            }

            $flushOutput = static function (): void {
                if (function_exists('ob_get_level') && ob_get_level() > 0) {
                    @ob_flush();
                }
                @flush();
            };

            $sendSse = static function (string $event, array $payload) use ($flushOutput): void {
                echo 'event: ' . $event . "\n";
                echo 'data: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
                $flushOutput();
            };

            echo "retry: 2000\n\n";
            $flushOutput();

            $lastHash = '';
            $lastPingAt = time();
            $startedAt = time();
            $lastUserCheckAt = 0;
            $maxStreamSeconds = (int) config('services.ea.sse_max_stream_seconds', 120);

            while (!connection_aborted()) {
                if ((time() - $startedAt) >= $maxStreamSeconds) {
                    $sendSse('close', [
                        'success' => true,
                        'reason' => 'stream_refresh_required',
                    ]);
                    break;
                }

                if ($userId > 0 && (time() - $lastUserCheckAt) >= 30) {
                    $lastUserCheckAt = time();
                    $userExists = User::query()->whereKey($userId)->exists();
                    if (!$userExists) {
                        $sendSse('close', [
                            'success' => false,
                            'reason' => 'user_inactive',
                        ]);
                        break;
                    }
                }

                try {
                    $cacheKey = sprintf(
                        'dashboard_live_stream_snapshot:%s:%s:%d:%d:%d',
                        $accountId,
                        $pairSymbol,
                        $limit,
                        $page,
                        $calcDebug ? 1 : 0
                    );

                    $payload = Cache::remember($cacheKey, now()->addSeconds(2), function () use ($apiController, $monitoringRequest, $reportRequest, $accountId): array {
                        $monitoring = $apiController->myMonitoringLive($monitoringRequest)->getData(true);
                        $report = $apiController->myReportLive($reportRequest)->getData(true);

                        return [
                            'success' => true,
                            'account_id' => $accountId,
                            'pair_symbol' => $pairSymbol,
                            'server_time' => Carbon::now()->toIso8601String(),
                            'monitoring' => $monitoring,
                            'report' => $report,
                        ];
                    });

                    $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $hash = md5((string) $encodedPayload);

                    if ($hash !== $lastHash) {
                        echo "event: update\n";
                        echo 'data: ' . $encodedPayload . "\n\n";
                        $lastHash = $hash;
                        $flushOutput();
                    }
                } catch (\Throwable $exception) {
                    Log::error('SSE live-stream loop failed', [
                        'account_id' => $accountId,
                        'user_id' => $userId,
                        'message' => $exception->getMessage(),
                    ]);

                    echo "retry: 4000\n";
                    echo "event: error\n";
                    echo 'data: ' . json_encode([
                        'success' => false,
                        'message' => 'stream_iteration_failed',
                        'server_time' => Carbon::now()->toIso8601String(),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
                    $flushOutput();
                }

                if ((time() - $lastPingAt) >= 15) {
                    echo ': ping ' . Carbon::now()->toIso8601String() . "\n\n";
                    $lastPingAt = time();
                    $flushOutput();
                }

                usleep(2000000);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function resetReportWr(Request $request, DashboardApiController $apiController): JsonResponse
    {
        return $apiController->myReportResetWr($request);
    }

    public function storeAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'pair_symbol' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_\/\.\-]+$/'],
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
            ->first();

        if ($existing !== null) {
            if ((int) $existing->user_id === (int) $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account MT5 ini sudah terdaftar di user Anda.',
                ], 422);
            }

            if (!$isAdmin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account MT5 ini sudah dipakai user lain. Hubungi admin jika perlu pemindahan ownership.',
                ], 409);
            }

            return response()->json([
                'success' => true,
                'message' => 'Account MT5 sudah terdaftar milik user lain dan berhasil ditautkan ke dashboard admin.',
                'data' => $existing->fresh(),
            ]);
        }

        $configuration = EaConfiguration::query()->create([
            'user_id' => $user->id,
            'account_id' => $accountId,
            'pair_symbol' => $pairSymbol,
            'base_lot' => $baseLot,
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
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account MT5 berhasil ditambahkan.',
            'data' => $configuration,
        ]);
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
        ]);

        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $isAdmin = (bool) ($user->is_admin || $role === 'admin');
        $accountId = trim((string) $validated['account_id']);

        $query = EaConfiguration::query()->where('account_id', $accountId);
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

        $hasActiveConfig = $configurations->contains(function (EaConfiguration $configuration): bool {
            $now = Carbon::now();
            $updatedAt = $configuration->updated_at instanceof Carbon ? $configuration->updated_at : null;
            $isFreshHeartbeat = $updatedAt !== null && $updatedAt->greaterThanOrEqualTo($now->copy()->subSeconds(60));

            $layers = max(0, (int) ($configuration->current_layers ?? 0));
            $accLot = max(0.0, (float) ($configuration->current_accumulative_lot ?? 0.0));
            if ($layers > 0 || $accLot > 0.0000001) {
                return true;
            }

            $latestReport = EaStatusReport::query()
                ->where('ea_configuration_id', $configuration->id)
                ->latest('id')
                ->first(['open_positions', 'updated_at', 'created_at']);

            if ($latestReport !== null) {
                $reportUpdatedAt = null;
                try {
                    $reportUpdatedAt = Carbon::parse((string) ($latestReport->updated_at ?? $latestReport->created_at));
                } catch (\Throwable) {
                    $reportUpdatedAt = null;
                }

                $reportFresh = $reportUpdatedAt !== null && $reportUpdatedAt->greaterThanOrEqualTo($now->copy()->subSeconds(90));
                if ($reportFresh && is_array($latestReport->open_positions) && count($latestReport->open_positions) > 0) {
                    return true;
                }
            }

            if (!$isFreshHeartbeat) {
                return false;
            }

            $guardStatus = strtoupper(trim((string) ($configuration->guard_status ?? '')));
            $liveGuardStatus = strtoupper(trim((string) ($configuration->live_guard_status ?? '')));

            return $guardStatus === 'LIVE' || $liveGuardStatus === 'LIVE';
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

    private function refreshNewsIfStale(): void
    {
        $now = Carbon::now();
        $hasUpcoming = EconomicNews::query()->where('event_at', '>=', $now)->exists();
        $latestUpdate = EconomicNews::query()->max('updated_at');
        $isStale = !$latestUpdate || Carbon::parse((string) $latestUpdate)->lt($now->copy()->subMinutes(45));

        if ($hasUpcoming && !$isStale) {
            return;
        }

        if (!Cache::add('dashboard_news_refresh_lock', '1', now()->addMinutes(3))) {
            return;
        }

        try {
            Artisan::call('news:fetch-analyze');
        } catch (\Throwable $exception) {
            Log::warning('Dashboard news refresh failed: ' . $exception->getMessage());
        } finally {
            Cache::forget('dashboard_news_refresh_lock');
        }
    }

    private function buildForexFactoryNewsFeed()
    {
        $now = Carbon::now();

        $dbFeed = EconomicNews::query()
            ->where('currency', 'USD')
            ->whereIn('impact', ['HIGH', 'MEDIUM', 'LOW'])
            ->where('event_at', '>=', $now)
            ->orderBy('event_at')
            ->limit(7)
            ->get()
            ->map(static function (EconomicNews $item) {
                $payload = is_array($item->raw_payload) ? $item->raw_payload : [];

                $pick = static function (array $keys) use ($payload): string {
                    foreach ($keys as $key) {
                        if (!array_key_exists($key, $payload)) {
                            continue;
                        }

                        $value = trim((string) ($payload[$key] ?? ''));
                        if ($value === '' || strtoupper($value) === 'N/A' || strtoupper($value) === 'NULL') {
                            continue;
                        }

                        return $value;
                    }

                    return '';
                };

                return (object) [
                    'title' => (string) ($item->title ?? 'USD Event'),
                    'impact' => strtoupper((string) ($item->impact ?? 'MEDIUM')),
                    'event_at' => $item->event_at,
                    'raw_payload' => [
                        'actual' => $pick(['actual', 'actual_value', 'actualValue', 'actual_formatted', 'actualFormatted']),
                        'forecast' => $pick(['forecast', 'consensus', 'estimate', 'forecast_value', 'forecastValue', 'forecast_formatted', 'forecastFormatted']),
                        'previous' => $pick(['previous', 'prior', 'previous_value', 'previousValue', 'previous_formatted', 'previousFormatted']),
                    ],
                    'ai_analysis' => (string) ($item->ai_analysis ?: ''),
                    'ai_verdict' => (string) ($item->ai_verdict ?: ''),
                ];
            })
            ->values();

        if ($dbFeed->isNotEmpty()) {
            return $dbFeed;
        }

        $events = $this->calendarService->getHighImpactEvents();

        $liveFeed = collect($events)
            ->filter(static function (array $event): bool {
                return strtoupper((string) ($event['country'] ?? '')) === 'USD';
            })
            ->map(static function (array $event) {
                $eventTime = (int) ($event['time'] ?? 0);
                if ($eventTime <= 0) {
                    return null;
                }

                $carbonTime = Carbon::createFromTimestampUTC($eventTime);

                return (object) [
                    'title' => (string) ($event['event'] ?? 'USD Event'),
                    'impact' => (string) ($event['importance'] ?? 'HIGH'),
                    'event_at' => $carbonTime,
                    'raw_payload' => [
                        'actual' => trim((string) ($event['actual'] ?? '')),
                        'forecast' => trim((string) ($event['forecast'] ?? '')),
                        'previous' => trim((string) ($event['previous'] ?? '')),
                    ],
                    'ai_analysis' => '',
                    'ai_verdict' => '',
                ];
            })
            ->filter()
            ->filter(static fn ($item): bool => $item->event_at !== null && $item->event_at->greaterThanOrEqualTo($now))
            ->sortBy('event_at')
            ->take(7)
            ->values();

        return $liveFeed;
    }
}
