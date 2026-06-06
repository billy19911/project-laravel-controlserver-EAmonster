<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BookkeepingEntry;
use App\Models\DashboardSetting;
use App\Models\EaConfiguration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BookkeepingController extends Controller
{
    private const DEFAULT_RATE = 16000.0;

    private const SETTING_ENABLED = 'bookkeeping_enabled';

    private const SETTING_USER_WHITELIST = 'bookkeeping_user_whitelist';

    public function visibility(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $accountId = (string) $request->query('account_id', '');
        $policy = $this->loadGlobalPolicy();
        $canAccessTab = $this->canAccessBookkeepingTab($user, $policy);
        $canManageSettings = $this->isAdminUser($user);
        $allowed = $canAccessTab && $accountId !== '';

        return response()->json([
            'success' => true,
            'enabled' => (bool) ($policy['enabled'] ?? false),
            'user_whitelist' => array_values(array_map('intval', $policy['user_whitelist'] ?? [])),
            'can_access_tab' => $canAccessTab,
            'can_manage_settings' => $canManageSettings,
            'allowed' => $allowed,
            'message' => $allowed ? 'OK' : ($canAccessTab ? 'Pilih account terlebih dahulu.' : 'Tab pembukuan hanya untuk admin atau user yang di-whitelist.'),
            'account_id' => $accountId,
        ]);
    }

    public function settings(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$this->isAdminUser($user)) {
            return response()->json(['success' => false, 'message' => 'Hanya admin yang dapat mengatur whitelist pembukuan.'], 403);
        }

        $policy = $this->loadGlobalPolicy();
        $availableUsers = User::query()
            ->orderBy('id')
            ->get(['id', 'name', 'username', 'email'])
            ->map(static function (User $row): array {
                return [
                    'id' => (int) $row->id,
                    'name' => (string) ($row->name ?? ''),
                    'username' => (string) ($row->username ?? ''),
                    'email' => (string) ($row->email ?? ''),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'settings' => [
                'enabled' => (bool) ($policy['enabled'] ?? false),
                'user_whitelist' => array_values(array_map('intval', $policy['user_whitelist'] ?? [])),
                'user_whitelist_text' => implode(',', array_values(array_map('intval', $policy['user_whitelist'] ?? []))),
                'available_users' => $availableUsers,
            ],
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$this->isAdminUser($user)) {
            return response()->json(['success' => false, 'message' => 'Hanya admin yang dapat mengatur whitelist pembukuan.'], 403);
        }

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'user_whitelist_text' => ['nullable', 'string', 'max:10000'],
        ]);

        $candidateUserIds = $this->parseUserIdList((string) ($validated['user_whitelist_text'] ?? ''));

        $existingUserIds = User::query()
            ->whereIn('id', $candidateUserIds)
            ->pluck('id')
            ->all();

        $safeUserWhitelist = array_values(array_unique(array_map('intval', $existingUserIds)));
        $availableUsers = User::query()
            ->orderBy('id')
            ->get(['id', 'name', 'username', 'email'])
            ->map(static function (User $row): array {
                return [
                    'id' => (int) $row->id,
                    'name' => (string) ($row->name ?? ''),
                    'username' => (string) ($row->username ?? ''),
                    'email' => (string) ($row->email ?? ''),
                ];
            })
            ->values();

        $this->savePolicy(
            (bool) $validated['enabled'],
            $safeUserWhitelist
        );

        return response()->json([
            'success' => true,
            'message' => 'Setting pembukuan berhasil disimpan.',
            'settings' => [
                'enabled' => (bool) $validated['enabled'],
                'user_whitelist' => $safeUserWhitelist,
                'user_whitelist_text' => implode(',', $safeUserWhitelist),
                'available_users' => $availableUsers,
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'account_id' => ['nullable', 'string', 'max:32'],
        ]);

        $policy = $this->loadGlobalPolicy();
        if (!$this->canAccessBookkeepingTab($user, $policy)) {
            return response()->json([
                'success' => false,
                'message' => 'Tab pembukuan hanya untuk admin atau user yang di-whitelist.',
            ], 403);
        }

        $date = (string) ($validated['date'] ?? Carbon::now()->format('Y-m-d'));

        $configs = EaConfiguration::query()
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get([
                'account_id',
                'current_balance',
                'current_equity',
                'today_pnl',
                'account_currency',
                'updated_at',
            ]);

        $configs = $configs
            ->unique('account_id')
            ->sortBy('account_id')
            ->values();

        $aliasMap = $this->loadAccountAliasMap();

        $entries = BookkeepingEntry::query()
            ->where('user_id', $user->id)
            ->whereDate('entry_date', $date)
            ->get()
            ->keyBy('account_id');

        $rows = $configs->map(function (EaConfiguration $cfg) use ($entries, $aliasMap): array {
            $entry = $entries->get($cfg->account_id);
            return [
                'account_id' => $cfg->account_id,
                'account_alias' => (string) ($aliasMap[(string) $cfg->account_id] ?? ''),
                'balance' => (float) ($cfg->current_balance ?? 0.0),
                'equity' => (float) ($cfg->current_equity ?? 0.0),
                'growth_today_usd' => (float) ($cfg->today_pnl ?? 0.0),
                'account_currency' => (string) ($cfg->account_currency ?: 'USD'),
                'daily_profit_usd' => $entry ? (float) $entry->daily_profit_usd : null,
                'exchange_rate_idr' => $entry ? (float) $entry->exchange_rate_idr : null,
                'profit_idr' => $entry ? (float) $entry->profit_idr : null,
                'notes' => $entry?->notes,
            ];
        })->values();

        $latestRate = BookkeepingEntry::query()
            ->where('user_id', $user->id)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->value('exchange_rate_idr');

        $totalUsd = $rows->sum(fn (array $row): float => (float) ($row['daily_profit_usd'] ?? 0.0));
        $totalIdr = $rows->sum(fn (array $row): float => (float) ($row['profit_idr'] ?? 0.0));
        $totalGrowthTodayUsd = $rows->sum(fn (array $row): float => (float) ($row['growth_today_usd'] ?? 0.0));

        return response()->json([
            'success' => true,
            'date' => $date,
            'rate_idr_default' => (float) ($latestRate ?? self::DEFAULT_RATE),
            'rows' => $rows,
            'summary' => [
                'total_usd' => $totalUsd,
                'total_idr' => $totalIdr,
                'total_growth_today_usd' => $totalGrowthTodayUsd,
            ],
        ]);
    }

    public function saveBatch(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'account_id' => ['nullable', 'string', 'max:32'],
            'exchange_rate_idr' => ['required', 'numeric', 'min:1'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.account_id' => ['required', 'string', 'max:32'],
            'entries.*.daily_profit_usd' => ['required', 'numeric'],
            'entries.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $policy = $this->loadGlobalPolicy();
        if (!$this->canAccessBookkeepingTab($user, $policy)) {
            return response()->json([
                'success' => false,
                'message' => 'Tab pembukuan hanya untuk admin atau user yang di-whitelist.',
            ], 403);
        }

        $ownedAccounts = EaConfiguration::query()
            ->where('user_id', $user->id)
            ->pluck('account_id')
            ->all();
        $ownedSet = array_fill_keys($ownedAccounts, true);

        $saved = [];
        foreach ($validated['entries'] as $entry) {
            $entryAccount = (string) $entry['account_id'];
            if (!isset($ownedSet[$entryAccount])) {
                continue;
            }

            $dailyProfitUsd = (float) $entry['daily_profit_usd'];
            $rateIdr = (float) $validated['exchange_rate_idr'];
            $profitIdr = $dailyProfitUsd * $rateIdr;

            $row = BookkeepingEntry::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'account_id' => $entryAccount,
                    'entry_date' => $validated['date'],
                ],
                [
                    'daily_profit_usd' => $dailyProfitUsd,
                    'exchange_rate_idr' => $rateIdr,
                    'profit_idr' => $profitIdr,
                    'notes' => $entry['notes'] ?? null,
                ]
            );

            $saved[] = [
                'account_id' => $row->account_id,
                'daily_profit_usd' => (float) $row->daily_profit_usd,
                'exchange_rate_idr' => (float) $row->exchange_rate_idr,
                'profit_idr' => (float) $row->profit_idr,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Pembukuan harian berhasil disimpan.',
            'date' => $validated['date'],
            'saved_count' => count($saved),
            'entries' => $saved,
        ]);
    }

    private function loadGlobalPolicy(): array
    {
        $rows = DashboardSetting::query()
            ->whereIn('key', [
                self::SETTING_ENABLED,
                self::SETTING_USER_WHITELIST,
            ])
            ->pluck('value', 'key');

        $enabledRaw = $rows->get(self::SETTING_ENABLED);
        $whitelistRaw = (string) ($rows->get(self::SETTING_USER_WHITELIST) ?? '');

        return [
            'enabled' => $this->toBool($enabledRaw),
            'user_whitelist' => $this->parseUserIdList($whitelistRaw),
        ];
    }

    private function savePolicy(bool $enabled, array $userWhitelist): void
    {
        DashboardSetting::query()->updateOrCreate(
            ['key' => self::SETTING_ENABLED],
            ['value' => $enabled ? '1' : '0']
        );

        DashboardSetting::query()->updateOrCreate(
            ['key' => self::SETTING_USER_WHITELIST],
            ['value' => implode(',', array_values(array_unique(array_map('intval', $userWhitelist))))]
        );
    }

    private function canAccessBookkeepingTab(?User $user, array $policy): bool
    {
        if ($user === null) {
            return false;
        }

        if ($this->isAdminUser($user)) {
            return true;
        }

        if (!(bool) ($policy['enabled'] ?? false)) {
            return false;
        }

        $list = array_values(array_unique(array_map('intval', $policy['user_whitelist'] ?? [])));
        return in_array((int) $user->id, $list, true);
    }

    private function isAdminUser(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return (bool) ($user->is_admin || (string) ($user->role ?? '') === 'admin');
    }

    private function parseUserIdList(string $raw): array
    {
        $items = preg_split('/[\s,;\n\r\t]+/', trim($raw)) ?: [];
        $clean = [];
        foreach ($items as $item) {
            $id = (int) trim((string) $item);
            if ($id > 0) {
                $clean[] = $id;
            }
        }

        return array_values(array_unique($clean));
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }

    private function loadAccountAliasMap(): array
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
}
