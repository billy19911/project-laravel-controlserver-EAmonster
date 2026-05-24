<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Api\V1\EaController as DashboardApiController;
use App\Models\DashboardSetting;
use App\Models\EaConfiguration;
use App\Models\EconomicNews;
use App\Models\User;
use App\Services\EconomicCalendarService;
use App\Services\Mt5LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
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
        $accountAliases = $this->loadAccountAliasMap($isAdmin);

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
            'licenseSnapshots' => $licenseSnapshots,
            'licenseEnforcementEnabled' => $this->licenseService->isEnforcementEnabled(),
        ]);
    }

    private function loadAccountAliasMap(bool $isAdmin): array
    {
        if (!$isAdmin) {
            return [];
        }

        try {
            $raw = (string) (DashboardSetting::query()->where('key', 'account_alias_map')->value('value') ?? '');
            if ($raw === '') {
                return [];
            }

            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                return [];
            }

            return collect($decoded)
                ->mapWithKeys(static function ($value, $key): array {
                    $accountId = trim((string) $key);
                    $alias = trim((string) $value);
                    if ($accountId === '' || $alias === '') {
                        return [];
                    }

                    return [$accountId => $alias];
                })
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
        $pairSymbol = strtoupper((string) ($validated['pair_symbol'] ?? 'XAUUSD'));
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

        $hasActiveConfig = $configurations->contains(static function (EaConfiguration $configuration): bool {
            return (int) ($configuration->current_layers ?? 0) > 0 || (bool) ($configuration->is_online ?? false);
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
        $events = $this->calendarService->getHighImpactEvents();
        $now = Carbon::now();

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
                        'actual' => null,
                        'forecast' => null,
                        'previous' => null,
                    ],
                    'ai_analysis' => 'ForexFactory live feed',
                    'ai_verdict' => 'GOLD NEUTRAL',
                ];
            })
            ->filter()
            ->filter(static fn ($item): bool => $item->event_at !== null && $item->event_at->greaterThanOrEqualTo($now))
            ->sortBy('event_at')
            ->take(7)
            ->values();

        if ($liveFeed->isNotEmpty()) {
            return $liveFeed;
        }

        return EconomicNews::query()
            ->where('currency', 'USD')
            ->whereIn('impact', ['HIGH', 'MEDIUM', 'LOW'])
            ->where('event_at', '>=', $now)
            ->orderBy('event_at')
            ->limit(7)
            ->get()
            ->map(static function (EconomicNews $item) {
                return (object) [
                    'title' => (string) ($item->title ?? 'USD Event'),
                    'impact' => strtoupper((string) ($item->impact ?? 'MEDIUM')),
                    'event_at' => $item->event_at,
                    'raw_payload' => [
                        'actual' => (string) data_get($item->raw_payload, 'actual', 'Menunggu rilis'),
                        'forecast' => (string) data_get($item->raw_payload, 'forecast', 'Menunggu rilis'),
                        'previous' => (string) data_get($item->raw_payload, 'previous', 'Menunggu rilis'),
                    ],
                    'ai_analysis' => (string) ($item->ai_analysis ?: 'Data upcoming dari cache kalender ForexFactory.'),
                    'ai_verdict' => (string) ($item->ai_verdict ?: 'GOLD NEUTRAL'),
                ];
            })
            ->values();
    }
}
