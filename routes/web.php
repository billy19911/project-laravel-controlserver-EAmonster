<?php

use App\Http\Controllers\Api\EaController as CentralEaController;
use App\Http\Controllers\Api\V1\BookkeepingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LicenseController;
use App\Services\Mt5LicenseService;
use App\Models\DashboardSetting;
use App\Models\EaConfiguration;
use App\Models\EconomicNews;
use App\Models\Mt5RiskConsent;
use App\Services\EconomicCalendarService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

$parseAccountWhitelist = static function (string $raw): array {
    return collect(explode(',', $raw))
        ->map(static fn (string $item): string => trim($item))
        ->filter(static fn (string $item): bool => $item !== '')
        ->unique()
        ->values()
        ->all();
};

$loadBulkControlPolicy = static function () use ($parseAccountWhitelist): array {
    $defaultEnabled = (bool) config('services.ea.bulk_toggle_enabled', true);
    $defaultWhitelist = $parseAccountWhitelist((string) config('services.ea.bulk_toggle_account_whitelist', ''));

    try {
        $rows = DashboardSetting::query()
            ->whereIn('key', ['bulk_toggle_enabled', 'bulk_toggle_account_whitelist'])
            ->pluck('value', 'key');

        $enabledRaw = $rows->get('bulk_toggle_enabled');
        $enabledParsed = filter_var($enabledRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $enabled = $enabledRaw === null ? $defaultEnabled : ($enabledParsed ?? $defaultEnabled);

        $whitelistRaw = $rows->get('bulk_toggle_account_whitelist');
        $whitelist = $whitelistRaw === null
            ? $defaultWhitelist
            : $parseAccountWhitelist((string) $whitelistRaw);

        return [
            'enabled' => (bool) $enabled,
            'whitelist' => $whitelist,
        ];
    } catch (\Throwable) {
        return [
            'enabled' => $defaultEnabled,
            'whitelist' => $defaultWhitelist,
        ];
    }
};

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }

    return view('landing');
});

Route::get('/legal/risk-disclaimer', function () {
    return view('legal.risk-disclaimer');
})->name('legal.risk');

Route::get('/panduan/pengoperasian-bot', function (Request $request) {
    $stepImages = [];
    $disk = Storage::disk('public');

    for ($step = 1; $step <= 6; $step++) {
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $relativePath = "guides/operasional-bot/step-{$step}.{$ext}";
            if (!$disk->exists($relativePath)) {
                continue;
            }

            $stepImages[$step] = asset('storage/' . $relativePath);
            break;
        }
    }

    $user = $request->user();
    $role = strtolower((string) ($user->role ?? ''));
    $isAdmin = $user ? (bool) ($user->is_admin || $role === 'admin') : false;
    $requestedStep = max(1, min(6, (int) $request->query('step', 1)));

    return view('guides.operasional-bot', [
        'stepImages' => $stepImages,
        'isGuideAdmin' => $isAdmin,
        'requestedStep' => $requestedStep,
    ]);
})->name('guides.operasional-bot');

Route::post('/panduan/pengoperasian-bot/upload-image', function (Request $request) {
    $user = $request->user();
    $role = strtolower((string) ($user->role ?? ''));
    $isAdmin = (bool) ($user->is_admin || $role === 'admin');

    abort_unless($isAdmin, 403, 'Forbidden. Admin only.');

    $validated = $request->validate([
        'step' => ['required', 'integer', 'between:1,6'],
        'step_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
    ]);

    $step = (int) $validated['step'];
    $disk = Storage::disk('public');
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
        $disk->delete("guides/operasional-bot/step-{$step}.{$ext}");
    }

    $extension = strtolower((string) $request->file('step_image')->extension());
    $disk->putFileAs('guides/operasional-bot', $request->file('step_image'), "step-{$step}.{$extension}");

    return redirect()
        ->route('guides.operasional-bot', ['step' => $step])
        ->with('guide_upload_success', "Gambar Step {$step} berhasil diupload.");
})->middleware('auth')->name('guides.operasional-bot.upload-image');

Route::get('/ea-dashboard.html', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard.html', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard-v209.html', function () {
    return redirect('/dashboard');
});

Route::get('/favicon.ico', function () {
    return redirect('/favicon.svg', 301);
});

Route::get('/dasnboard', function () {
    return redirect('/dashboard');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::get('/register', function () {
        return redirect('/?register=1');
    })->name('register');

    Route::post('/login', function (Request $request) {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $login = trim((string) $validated['login']);

        $user = User::query()
            ->where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if ($user === null || !Hash::check($validated['password'], $user->password)) {
            return back()->withErrors([
                'login' => 'Email/username atau password tidak valid.',
            ])->withInput();
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $intended = (string) $request->session()->get('url.intended', '');
        if ($intended !== '') {
            $path = (string) (parse_url($intended, PHP_URL_PATH) ?? '');
            $shouldForceDashboard = str_starts_with($path, '/dashboard/live-stream')
                || str_starts_with($path, '/api/')
                || str_starts_with($path, '/dashboard/monitoring/live')
                || str_starts_with($path, '/dashboard/reports/live');

            if ($shouldForceDashboard) {
                $request->session()->forget('url.intended');
            }
        }

        return redirect()->intended('/dashboard');
    })->name('login.attempt');

    Route::post('/register', function (Request $request) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_admin' => false,
            'role' => 'user',
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect('/dashboard');
    })->name('register.attempt');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect('/');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () use ($parseAccountWhitelist, $loadBulkControlPolicy): void {
    $pickNewsMetric = static function (EconomicNews $item, array $keys): string {
        $payload = is_array($item->raw_payload) ? $item->raw_payload : [];
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

    $buildEconomicNewsUpcomingRows = static function () use ($pickNewsMetric) {
        return EconomicNews::query()
            ->where('currency', 'USD')
            ->whereIn('impact', ['HIGH', 'MEDIUM', 'LOW'])
            ->where('event_at', '>=', Carbon::now())
            ->orderBy('event_at')
            ->limit(7)
            ->get()
            ->map(static function (EconomicNews $item) use ($pickNewsMetric): array {
                $actual = $pickNewsMetric($item, ['actual', 'actual_value', 'actualValue', 'actual_formatted', 'actualFormatted']);
                $forecast = $pickNewsMetric($item, ['forecast', 'consensus', 'estimate', 'forecast_value', 'forecastValue', 'forecast_formatted', 'forecastFormatted']);
                $previous = $pickNewsMetric($item, ['previous', 'prior', 'previous_value', 'previousValue', 'previous_formatted', 'previousFormatted']);

                return [
                    'title' => (string) ($item->title ?? 'USD Event'),
                    'impact' => strtoupper((string) ($item->impact ?? 'MEDIUM')),
                    'event_at' => optional($item->event_at)->toIso8601String(),
                    'event_clock' => optional($item->event_at)?->copy()->timezone('Asia/Jakarta')->format('H:i'),
                    'actual' => $actual,
                    'forecast' => $forecast,
                    'previous' => $previous,
                    'ai_analysis' => (string) ($item->ai_analysis ?: ''),
                    'ai_verdict' => (string) ($item->ai_verdict ?: ''),
                ];
            })
            ->filter(static fn (array $item): bool => !empty($item['event_at']))
            ->values();
    };

    $buildEconomicNewsRecentHistoryRows = static function () use ($pickNewsMetric) {
        return EconomicNews::query()
            ->where('currency', 'USD')
            ->whereIn('impact', ['HIGH', 'MEDIUM', 'LOW'])
            ->where('event_at', '<', Carbon::now())
            ->orderByDesc('event_at')
            ->limit(7)
            ->get()
            ->map(static function (EconomicNews $item) use ($pickNewsMetric): array {
                $actual = $pickNewsMetric($item, ['actual', 'actual_value', 'actualValue', 'actual_formatted', 'actualFormatted']);
                $forecast = $pickNewsMetric($item, ['forecast', 'consensus', 'estimate', 'forecast_value', 'forecastValue', 'forecast_formatted', 'forecastFormatted']);
                $previous = $pickNewsMetric($item, ['previous', 'prior', 'previous_value', 'previousValue', 'previous_formatted', 'previousFormatted']);

                return [
                    'title' => (string) ($item->title ?? 'USD Event'),
                    'impact' => strtoupper((string) ($item->impact ?? 'MEDIUM')),
                    'event_at' => optional($item->event_at)->toIso8601String(),
                    'event_clock' => optional($item->event_at)?->copy()->timezone('Asia/Jakarta')->format('H:i'),
                    'actual' => $actual !== '' ? $actual : 'N/A',
                    'forecast' => $forecast !== '' ? $forecast : 'N/A',
                    'previous' => $previous !== '' ? $previous : 'N/A',
                    'ai_analysis' => (string) ($item->ai_analysis ?: 'Data history event USD yang sudah lewat.'),
                    'ai_verdict' => (string) ($item->ai_verdict ?: 'GOLD NEUTRAL'),
                ];
            })
            ->filter(static fn (array $item): bool => !empty($item['event_at']))
            ->values();
    };

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::prefix('dashboard/bookkeeping')->group(function (): void {
        Route::get('/visibility', [BookkeepingController::class, 'visibility'])->name('dashboard.bookkeeping.visibility');
        Route::get('/settings', [BookkeepingController::class, 'settings'])->name('dashboard.bookkeeping.settings');
        Route::post('/settings', [BookkeepingController::class, 'updateSettings'])->name('dashboard.bookkeeping.settings.update');
        Route::get('/', [BookkeepingController::class, 'index'])->name('dashboard.bookkeeping.index');
        Route::post('/save-batch', [BookkeepingController::class, 'saveBatch'])->name('dashboard.bookkeeping.save-batch');
    });
    Route::get('/licenses', [LicenseController::class, 'billingPage'])->name('licenses.billing.page');
    Route::post('/licenses/billing', [LicenseController::class, 'createBilling'])->name('licenses.billing.store');
    Route::post('/licenses/redeem', [LicenseController::class, 'redeemTrialCode'])->name('licenses.redeem.store');
    Route::get('/licenses/chat', [LicenseController::class, 'billingChatThreadJson'])->name('licenses.chat.thread');
    Route::get('/licenses/chat/unread', [LicenseController::class, 'billingChatUnreadJson'])->name('licenses.chat.unread');
    Route::post('/licenses/chat', [LicenseController::class, 'billingChatSend'])->name('licenses.chat.send');
    Route::get('/admin/licenses', [LicenseController::class, 'adminPage'])->name('licenses.admin.page');
    Route::get('/admin/licenses/chat/threads', [LicenseController::class, 'billingChatThreadsJson'])->name('licenses.admin.chat.threads');
    Route::post('/admin/licenses/payment-config', [LicenseController::class, 'adminSaveBillingConfig'])->name('licenses.admin.payment.config');
    Route::post('/admin/licenses/redeem-codes/generate', [LicenseController::class, 'adminGenerateRedeemCodes'])->name('licenses.admin.redeem.generate');
    Route::post('/admin/licenses/upsert', [LicenseController::class, 'adminUpsert'])->name('licenses.admin.upsert');
    Route::post('/admin/licenses/reassign-account', [LicenseController::class, 'adminReassignAccount'])->name('licenses.admin.reassign-account');
    Route::post('/admin/licenses/remove-owner', [LicenseController::class, 'adminRemoveAccountOwner'])->name('licenses.admin.remove-owner');
    Route::post('/admin/licenses/{licenseId}/delete', [LicenseController::class, 'adminDeleteLicense'])->name('licenses.admin.delete');
    Route::post('/admin/licenses/enforcement', [LicenseController::class, 'adminSetEnforcement'])->name('licenses.admin.enforcement');
    Route::post('/admin/licenses/billing/{billingId}/decision', [LicenseController::class, 'adminBillingDecision'])->name('licenses.admin.billing.decision');
    Route::post('/admin/licenses/billing/{billingId}/decision-json', [LicenseController::class, 'adminBillingDecisionJson'])->name('licenses.admin.billing.decision.json');
    Route::post('/admin/licenses/billing/{billingId}/credential', [LicenseController::class, 'adminBillingCredentialJson'])->name('licenses.admin.billing.credential');
    Route::get('/licenses/status', [LicenseController::class, 'statusJson'])->name('licenses.status.json');
    Route::post('/dashboard/accounts', [DashboardController::class, 'storeAccount'])->name('dashboard.accounts.store');
    Route::delete('/dashboard/accounts', [DashboardController::class, 'deleteAccount'])->name('dashboard.accounts.delete');
    Route::post('/dashboard/settings', [CentralEaController::class, 'updateSetting'])->name('dashboard.settings.update');
    Route::get('/dashboard/news/live', function (Request $request) use ($buildEconomicNewsUpcomingRows, $buildEconomicNewsRecentHistoryRows) {
        $calendarService = app(EconomicCalendarService::class);
        $activeProvider = strtolower($calendarService->providerLabel());
        $userId = (int) $request->user()->id;
        $force = $request->boolean('force');
        $cacheKey = 'dashboard_news_live_user_' . $userId;
        $recentHistory = $buildEconomicNewsRecentHistoryRows();
        if ($force) {
            Cache::forget($cacheKey);
            $calendarService->clearCache();
        }
        $cachedSnapshot = Cache::get($cacheKey);

        if (!$force) {
            $cachedProvider = strtolower((string) ($cachedSnapshot['provider'] ?? ''));
            $providerMatches = $activeProvider === 'finnhub'
                ? str_starts_with($cachedProvider, 'finnhub')
                : str_contains($cachedProvider, 'forex');

            if (is_array($cachedSnapshot) && !$providerMatches) {
                Cache::forget($cacheKey);
                $cachedSnapshot = null;
            }

            if (
                is_array($cachedSnapshot)
                && !empty($cachedSnapshot['data'])
            ) {
                return response()->json($cachedSnapshot);
            }
        }

        if ($calendarService->providerKey() !== 'forexfactory') {
            $providerLabel = $calendarService->providerLabel();
            $buildProviderRows = static function () use ($calendarService, $providerLabel) {
                return collect($calendarService->getHighImpactEvents())
                ->filter(static fn (array $item): bool => strtoupper((string) ($item['country'] ?? '')) === 'USD')
                ->filter(static function (array $item): bool {
                    $impact = strtoupper((string) ($item['importance'] ?? ''));

                    return in_array($impact, ['HIGH', 'MEDIUM', 'LOW'], true);
                })
                ->map(static function (array $item) use ($providerLabel): ?array {
                    $timestamp = (int) ($item['time'] ?? 0);
                    if ($timestamp <= 0) {
                        return null;
                    }

                    $eventAt = Carbon::createFromTimestamp($timestamp);
                    $actualRaw = trim((string) ($item['actual'] ?? ''));
                    $forecastRaw = trim((string) ($item['forecast'] ?? ''));
                    $previousRaw = trim((string) ($item['previous'] ?? ''));

                    return [
                        'title' => (string) ($item['event'] ?? 'USD Event'),
                        'impact' => strtoupper((string) ($item['importance'] ?? 'MEDIUM')),
                        'event_at' => $eventAt->toIso8601String(),
                        'event_clock' => $eventAt->copy()->timezone('Asia/Jakarta')->format('H:i'),
                        'actual' => $actualRaw,
                        'forecast' => $forecastRaw,
                        'previous' => $previousRaw,
                        'ai_analysis' => '',
                        'ai_verdict' => '',
                    ];
                })
                ->filter(static fn ($item): bool => is_array($item))
                ->sortBy('event_at')
                ->take(7)
                ->values();

            };

            $serviceRows = $buildProviderRows();

            // Self-heal stale empty provider cache: clear and retry once before falling back.
            if ($serviceRows->isEmpty()) {
                $calendarService->clearCache();
                $serviceRows = $buildProviderRows();
            }

            if ($serviceRows->isNotEmpty()) {
                $dbInsightRows = $buildEconomicNewsUpcomingRows();
                $insightMap = $dbInsightRows
                    ->mapWithKeys(static function (array $row): array {
                        $title = strtolower(trim((string) ($row['title'] ?? '')));
                        $eventAt = (string) ($row['event_at'] ?? '');
                        if ($title === '' || $eventAt === '') {
                            return [];
                        }

                        $eventDate = Carbon::parse($eventAt)->timezone('Asia/Jakarta')->format('Y-m-d H:i');
                        return [$title . '|' . $eventDate => $row];
                    });

                $serviceRows = $serviceRows->map(static function (array $row) use ($insightMap): array {
                    $title = strtolower(trim((string) ($row['title'] ?? '')));
                    $eventAt = (string) ($row['event_at'] ?? '');
                    $key = $title !== '' && $eventAt !== ''
                        ? ($title . '|' . Carbon::parse($eventAt)->timezone('Asia/Jakarta')->format('Y-m-d H:i'))
                        : '';
                    $insight = $key !== '' ? $insightMap->get($key) : null;

                    if (is_array($insight)) {
                        $row['ai_analysis'] = (string) ($insight['ai_analysis'] ?? '');
                        $row['ai_verdict'] = (string) ($insight['ai_verdict'] ?? '');
                        if (trim((string) ($row['actual'] ?? '')) === '') {
                            $row['actual'] = (string) ($insight['actual'] ?? '');
                        }
                        if (trim((string) ($row['forecast'] ?? '')) === '') {
                            $row['forecast'] = (string) ($insight['forecast'] ?? '');
                        }
                        if (trim((string) ($row['previous'] ?? '')) === '') {
                            $row['previous'] = (string) ($insight['previous'] ?? '');
                        }
                    }

                    return $row;
                })->values();

                $result = [
                    'success' => true,
                    'data' => $serviceRows,
                    'history_recent' => $recentHistory,
                    'provider' => $providerLabel,
                    'cached' => false,
                    'fallback_mode' => false,
                ];

                Cache::put($cacheKey, $result, now()->addMinutes(10));

                return response()->json($result);
            }

            $dbFallbackRows = $buildEconomicNewsUpcomingRows();
            if ($dbFallbackRows->isNotEmpty()) {
                $result = [
                    'success' => true,
                    'data' => $dbFallbackRows,
                    'history_recent' => $recentHistory,
                    'provider' => $providerLabel . '-DBCache',
                    'cached' => false,
                    'fallback_mode' => false,
                ];

                Cache::put($cacheKey, $result, now()->addMinutes(10));

                return response()->json($result);
            }

            return response()->json([
                'success' => true,
                'data' => [],
                'history_recent' => $recentHistory,
                'provider' => $providerLabel,
                'cached' => false,
                'fallback_mode' => false,
                'message' => 'Feed live ' . $providerLabel . ' tidak tersedia saat ini.',
            ]);
        }

        $urls = [
            'https://nfs.faireconomy.media/ff_calendar_thisweek.json',
            'https://nfs.faireconomy.media/ff_calendar_thismonth.json',
        ];

        $payloadChunks = [];
        $lastError = 'unknown';
        $provider = 'ForexFactory';

        foreach ($urls as $url) {
            $decodedPayload = null;

            $response = Http::timeout(20)
                ->acceptJson()
                ->withHeaders(['User-Agent' => 'EA-Monster-Dashboard/1.0'])
                ->get($url);
            if ($response->ok() && is_array($response->json())) {
                $decodedPayload = $response->json();
            } else {
                $lastError = 'http:' . $response->status();
            }

            if ($decodedPayload === null) {
                $response = Http::withoutVerifying()
                    ->timeout(20)
                    ->acceptJson()
                    ->withHeaders(['User-Agent' => 'EA-Monster-Dashboard/1.0'])
                    ->get($url);
                if ($response->ok() && is_array($response->json())) {
                    $decodedPayload = $response->json();
                } else {
                    $lastError = 'http-noverify:' . $response->status();
                }
            }

            if ($decodedPayload === null) {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 20,
                        'header' => "User-Agent: EA-Monster-Dashboard/1.0\r\nAccept: application/json\r\n",
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]);

                $raw = @file_get_contents($url, false, $context);
                $decoded = is_string($raw) ? json_decode($raw, true) : null;
                if (is_array($decoded)) {
                    $decodedPayload = $decoded;
                }
            }

            if (is_array($decodedPayload)) {
                $payloadChunks = array_merge($payloadChunks, $decodedPayload);
            }
        }

        $payload = $payloadChunks;

        if (!is_array($payload) || count($payload) === 0) {
            $serviceFallbackRows = collect(app(EconomicCalendarService::class)->getHighImpactEvents())
                ->filter(static fn (array $item): bool => strtoupper((string) ($item['country'] ?? '')) === 'USD')
                ->filter(static function (array $item): bool {
                    $impact = strtoupper((string) ($item['importance'] ?? ''));

                    return in_array($impact, ['HIGH', 'MEDIUM', 'LOW'], true);
                })
                ->map(static function (array $item): ?array {
                    $timestamp = (int) ($item['time'] ?? 0);
                    if ($timestamp <= 0) {
                        return null;
                    }

                    $eventAt = Carbon::createFromTimestamp($timestamp);
                    $actualRaw = trim((string) ($item['actual'] ?? ''));
                    $forecastRaw = trim((string) ($item['forecast'] ?? ''));
                    $previousRaw = trim((string) ($item['previous'] ?? ''));

                    return [
                        'title' => (string) ($item['event'] ?? 'USD Event'),
                        'impact' => strtoupper((string) ($item['importance'] ?? 'MEDIUM')),
                        'event_at' => $eventAt->toIso8601String(),
                        'event_clock' => $eventAt->copy()->timezone('Asia/Jakarta')->format('H:i'),
                        'actual' => $actualRaw,
                        'forecast' => $forecastRaw,
                        'previous' => $previousRaw,
                        'ai_analysis' => '',
                        'ai_verdict' => '',
                    ];
                })
                ->filter(static fn ($item): bool => is_array($item))
                ->sortBy('event_at')
                ->take(7)
                ->values();

            if ($serviceFallbackRows->isNotEmpty()) {
                $result = [
                    'success' => true,
                    'data' => $serviceFallbackRows,
                    'history_recent' => $recentHistory,
                    'provider' => 'ForexFactory-CalendarService',
                    'cached' => false,
                    'fallback_mode' => false,
                ];

                Cache::put($cacheKey, $result, now()->addMinutes(10));

                return response()->json($result);
            }

            $dbFallbackRows = $buildEconomicNewsUpcomingRows();
            if ($dbFallbackRows->isNotEmpty()) {
                $result = [
                    'success' => true,
                    'data' => $dbFallbackRows,
                    'history_recent' => $recentHistory,
                    'provider' => 'ForexFactory-DBCache',
                    'cached' => false,
                    'fallback_mode' => false,
                ];

                Cache::put($cacheKey, $result, now()->addMinutes(10));

                return response()->json($result);
            }

            $cachedProvider = strtolower((string) ($cachedSnapshot['provider'] ?? ''));
            if (
                is_array($cachedSnapshot)
                && (!empty($cachedSnapshot['data']) || !empty($cachedSnapshot['history_recent']))
                && in_array($cachedProvider, ['forexfactory', 'forex-factory'], true)
            ) {
                $cachedSnapshot['cached'] = true;
                $cachedSnapshot['message'] = 'Feed live ForexFactory gagal (' . $lastError . '), menampilkan cache terakhir.';
                return response()->json($cachedSnapshot);
            }

            return response()->json([
                'success' => true,
                'data' => [],
                'history_recent' => $recentHistory,
                'provider' => 'ForexFactory',
                'cached' => false,
                'fallback_mode' => false,
                'message' => 'Feed live ForexFactory tidak tersedia (' . $lastError . ').',
            ]);
        }

        $now = Carbon::now();
        $rows = collect($payload)
            ->filter(fn (array $row): bool => strtoupper((string) ($row['country'] ?? '')) === 'USD')
            ->filter(function (array $row): bool {
                $impact = strtoupper((string) ($row['impact'] ?? ''));
                return in_array($impact, ['HIGH', 'MEDIUM', 'LOW'], true);
            })
            ->map(function (array $row) {
                try {
                    $eventAt = Carbon::parse((string) ($row['date'] ?? ''));
                } catch (\Throwable) {
                    return null;
                }

                $title = (string) ($row['title'] ?? $row['event'] ?? 'USD Event');

                $pickMetric = static function (array $source, array $keys): string {
                    foreach ($keys as $key) {
                        if (!array_key_exists($key, $source)) {
                            continue;
                        }

                        $value = trim((string) ($source[$key] ?? ''));
                        if ($value === '' || strtoupper($value) === 'N/A' || strtoupper($value) === 'NULL') {
                            continue;
                        }

                        return $value;
                    }

                    return '';
                };

                $actualRaw = $pickMetric($row, ['actual', 'Actual', 'actual_value', 'actualValue', 'actual_formatted', 'actualFormatted']);
                $forecastRaw = $pickMetric($row, ['forecast', 'Forecast', 'consensus', 'estimate', 'forecast_value', 'forecastValue', 'forecast_formatted', 'forecastFormatted']);
                $previousRaw = $pickMetric($row, ['previous', 'Previous', 'prior', 'previous_value', 'previousValue', 'previous_formatted', 'previousFormatted']);
                $isPastEvent = $eventAt->lessThan(Carbon::now());

                $actualText = $actualRaw !== ''
                    ? $actualRaw
                    : ($isPastEvent ? 'N/A' : 'Menunggu rilis');
                $forecastText = $forecastRaw !== ''
                    ? $forecastRaw
                    : ($isPastEvent ? 'N/A' : 'Menunggu rilis');
                $previousText = $previousRaw !== ''
                    ? $previousRaw
                    : ($isPastEvent ? 'N/A' : 'Menunggu rilis');

                $actualSanitized = str_replace([',', '%'], ['', ''], $actualRaw);
                $forecastSanitized = str_replace([',', '%'], ['', ''], $forecastRaw);
                $actual = is_numeric($actualSanitized) ? (float) $actualSanitized : null;
                $forecast = is_numeric($forecastSanitized) ? (float) $forecastSanitized : null;

                $upper = strtoupper($title);
                $score = 0;
                $isPolicyEvent = str_contains($upper, 'FOMC') || str_contains($upper, 'MINUTES') || str_contains($upper, 'SPEECH');
                $isLaborClaim = str_contains($upper, 'CLAIMS');
                foreach (['CPI', 'NFP', 'PAYROLLS', 'PCE', 'RETAIL SALES'] as $hawkish) {
                    if (str_contains($upper, $hawkish)) {
                        $score--;
                    }
                }
                foreach (['UNEMPLOYMENT', 'JOBLESS CLAIMS', 'PMI', 'CONSUMER CONFIDENCE'] as $dovish) {
                    if (str_contains($upper, $dovish)) {
                        $score++;
                    }
                }

                if ($actual !== null && $forecast !== null) {
                    if ($isLaborClaim) {
                        if ($actual > $forecast) {
                            $score += 1;
                        } elseif ($actual < $forecast) {
                            $score -= 1;
                        }
                    } else {
                        if ($actual > $forecast) {
                            $score -= 1;
                        } elseif ($actual < $forecast) {
                            $score += 1;
                        }
                    }
                }

                $verdict = 'GOLD NEUTRAL';
                if ($score >= 1) {
                    $verdict = 'GOLD BULLISH';
                } elseif ($score <= -1) {
                    $verdict = 'GOLD BEARISH';
                }
                if ($isPolicyEvent && $actual === null) {
                    $verdict = 'GOLD NEUTRAL';
                }

                $surpriseText = ($actual !== null && $forecast !== null)
                    ? ($actual > $forecast ? 'actual di atas forecast' : ($actual < $forecast ? 'actual di bawah forecast' : 'actual sejalan forecast'))
                    : 'actual belum rilis';

                $biasLine = $verdict === 'GOLD NEUTRAL'
                    ? 'Bias awal netral dengan potensi spike dua arah.'
                    : ('Bias awal ' . $verdict . ' selama momentum data masih valid.');

                $analysis = 'Pembacaan ' . $surpriseText . '. ' . $biasLine;

                return [
                    'title' => $title,
                    'impact' => strtoupper((string) ($row['impact'] ?? 'MEDIUM')),
                    'event_at' => $eventAt->toIso8601String(),
                    'event_clock' => $eventAt->copy()->timezone('Asia/Jakarta')->format('H:i'),
                    'actual' => $actualText,
                    'forecast' => $forecastText,
                    'previous' => $previousText,
                    'ai_analysis' => $analysis,
                    'ai_verdict' => $verdict,
                ];
            })
            ->filter(fn ($item) => is_array($item))
            ->unique(function (array $item): string {
                return strtolower(trim((string) ($item['event_at'] ?? '')) . '|' . trim((string) ($item['title'] ?? '')) . '|' . trim((string) ($item['impact'] ?? '')));
            })
            ->sortBy('event_at')
            ->values();

        $upcomingRows = $rows
            ->filter(fn (array $item): bool => Carbon::parse($item['event_at'])->greaterThanOrEqualTo($now))
            ->take(7)
            ->values();

        $recentHistory = $rows
            ->filter(fn (array $item): bool => Carbon::parse($item['event_at'])->lessThan($now))
            ->sortByDesc('event_at')
            ->take(4)
            ->values();

        $dataRows = $upcomingRows;
        if ($dataRows->isEmpty()) {
            $serviceUpcomingRows = collect(app(EconomicCalendarService::class)->getHighImpactEvents())
                ->filter(static fn (array $item): bool => strtoupper((string) ($item['country'] ?? '')) === 'USD')
                ->filter(static function (array $item): bool {
                    $impact = strtoupper((string) ($item['importance'] ?? ''));

                    return in_array($impact, ['HIGH', 'MEDIUM', 'LOW'], true);
                })
                ->map(static function (array $item): ?array {
                    $timestamp = (int) ($item['time'] ?? 0);
                    if ($timestamp <= 0) {
                        return null;
                    }

                    $eventAt = Carbon::createFromTimestamp($timestamp);
                    $actualRaw = trim((string) ($item['actual'] ?? ''));
                    $forecastRaw = trim((string) ($item['forecast'] ?? ''));
                    $previousRaw = trim((string) ($item['previous'] ?? ''));

                    return [
                        'title' => (string) ($item['event'] ?? 'USD Event'),
                        'impact' => strtoupper((string) ($item['importance'] ?? 'MEDIUM')),
                        'event_at' => $eventAt->toIso8601String(),
                        'event_clock' => $eventAt->copy()->timezone('Asia/Jakarta')->format('H:i'),
                        'actual' => $actualRaw,
                        'forecast' => $forecastRaw,
                        'previous' => $previousRaw,
                        'ai_analysis' => '',
                        'ai_verdict' => '',
                    ];
                })
                ->filter(static fn ($item): bool => is_array($item))
                ->sortBy('event_at')
                ->take(7)
                ->values();

            if ($serviceUpcomingRows->isNotEmpty()) {
                $dataRows = $serviceUpcomingRows;
                $provider = 'ForexFactory-CalendarService';
            }
        }

        if ($dataRows->isEmpty()) {
            $dbUpcomingRows = $buildEconomicNewsUpcomingRows();
            if ($dbUpcomingRows->isNotEmpty()) {
                $dataRows = $dbUpcomingRows;
                $provider = 'ForexFactory-DBCache';
            }
        }

        if ($dataRows->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'history_recent' => $recentHistory,
                'provider' => $provider,
                'cached' => false,
                'fallback_mode' => false,
                'message' => 'Belum ada event upcoming dari ForexFactory saat ini.',
            ]);
        }

        $result = [
            'success' => true,
            'data' => $dataRows,
            'history_recent' => $recentHistory,
            'provider' => $provider,
            'cached' => false,
            'fallback_mode' => false,
        ];

        Cache::put($cacheKey, $result, now()->addMinutes(15));

        return response()->json($result);
    })->name('dashboard.news.live');

    Route::get('/dashboard/news/debug', function (Request $request) {
        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $isAdmin = (bool) ($user->is_admin || $role === 'admin');
        if (!$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
            ], 403);
        }

        $now = Carbon::now();
        $urls = [
            'https://nfs.faireconomy.media/ff_calendar_thisweek.json',
            'https://nfs.faireconomy.media/ff_calendar_thismonth.json',
        ];

        $rawRows = collect();
        $sourceStatuses = [];
        foreach ($urls as $url) {
            try {
                $response = Http::timeout(20)
                    ->acceptJson()
                    ->withHeaders(['User-Agent' => 'EA-Monster-Dashboard/1.0'])
                    ->get($url);

                if ($response->ok() && is_array($response->json())) {
                    $decoded = $response->json();
                    $rawRows = $rawRows->concat($decoded);
                    $sourceStatuses[] = [
                        'url' => $url,
                        'status' => $response->status(),
                        'ok' => true,
                        'rows' => count($decoded),
                    ];
                    continue;
                }

                $sourceStatuses[] = [
                    'url' => $url,
                    'status' => $response->status(),
                    'ok' => false,
                    'rows' => 0,
                ];
            } catch (\Throwable $exception) {
                $sourceStatuses[] = [
                    'url' => $url,
                    'status' => null,
                    'ok' => false,
                    'rows' => 0,
                    'error' => $exception->getMessage(),
                ];
            }
        }

        $usdRows = $rawRows->filter(static fn ($row): bool => is_array($row) && strtoupper((string) ($row['country'] ?? '')) === 'USD')->values();
        $severityCounts = [
            'HIGH' => 0,
            'MEDIUM' => 0,
            'LOW' => 0,
            'OTHER' => 0,
        ];

        foreach ($usdRows as $row) {
            $impact = strtoupper((string) ($row['impact'] ?? ''));
            if (array_key_exists($impact, $severityCounts)) {
                $severityCounts[$impact]++;
            } else {
                $severityCounts['OTHER']++;
            }
        }

        $windowStart = $now->copy()->startOfDay();
        $windowEnd = $now->copy()->addDays(10)->endOfDay();
        $inWindowCount = $usdRows->filter(static function ($row) use ($windowStart, $windowEnd): bool {
            try {
                $eventAt = Carbon::parse((string) ($row['date'] ?? ''));
            } catch (\Throwable) {
                return false;
            }

            return $eventAt->betweenIncluded($windowStart, $windowEnd);
        })->count();

        $rawUpcomingRows = $usdRows
            ->map(static function ($row) {
                try {
                    $eventAt = Carbon::parse((string) ($row['date'] ?? ''));
                } catch (\Throwable) {
                    return null;
                }

                return [
                    'title' => (string) ($row['title'] ?? $row['event'] ?? 'USD Event'),
                    'impact' => strtoupper((string) ($row['impact'] ?? '')),
                    'event_at' => $eventAt->toIso8601String(),
                    'event_wib' => $eventAt->copy()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
                    'source_date' => (string) ($row['date'] ?? ''),
                ];
            })
            ->filter(static fn ($row): bool => is_array($row))
            ->filter(static fn (array $row) => Carbon::parse((string) ($row['event_at'] ?? ''))->greaterThanOrEqualTo($now))
            ->sortBy('event_at')
            ->values();

        $dbUpcomingCount = EconomicNews::query()
            ->where('currency', 'USD')
            ->whereIn('impact', ['HIGH', 'MEDIUM', 'LOW'])
            ->where('event_at', '>=', $now)
            ->count();

        $calendarService = app(EconomicCalendarService::class);
        $serviceRows = collect($calendarService->getHighImpactEvents());
        $serviceUsdRows = $serviceRows
            ->filter(static fn ($row): bool => strtoupper((string) ($row['country'] ?? '')) === 'USD')
            ->values();
        $serviceUpcomingRows = $serviceUsdRows
            ->filter(static fn ($row): bool => (int) ($row['time'] ?? 0) >= $now->timestamp)
            ->sortBy('time')
            ->values();

        $userCacheKey = 'dashboard_news_live_user_' . (int) $user->id;
        $cachedSnapshot = Cache::get($userCacheKey);
        $cacheSummary = is_array($cachedSnapshot)
            ? [
                'provider' => (string) ($cachedSnapshot['provider'] ?? ''),
                'cached_flag' => (bool) ($cachedSnapshot['cached'] ?? false),
                'fallback_mode' => (bool) ($cachedSnapshot['fallback_mode'] ?? false),
                'data_count' => is_array($cachedSnapshot['data'] ?? null) ? count($cachedSnapshot['data']) : 0,
                'history_count' => is_array($cachedSnapshot['history_recent'] ?? null) ? count($cachedSnapshot['history_recent']) : 0,
                'message' => (string) ($cachedSnapshot['message'] ?? ''),
            ]
            : null;

        return response()->json([
            'success' => true,
            'now' => $now->toIso8601String(),
            'active_provider_key' => $calendarService->providerKey(),
            'active_provider_label' => $calendarService->providerLabel(),
            'configured_provider' => (string) config('services.news.provider', ''),
            'sources' => $sourceStatuses,
            'raw_total_rows' => $rawRows->count(),
            'usd_rows' => $usdRows->count(),
            'usd_severity_counts' => $severityCounts,
            'usd_rows_in_next_10_days' => $inWindowCount,
            'raw_upcoming_usd_rows' => $rawUpcomingRows->count(),
            'raw_upcoming_sample' => $rawUpcomingRows->take(8)->values(),
            'db_upcoming_usd_rows' => $dbUpcomingCount,
            'calendar_service_rows' => $serviceRows->count(),
            'calendar_service_usd_rows' => $serviceUsdRows->count(),
            'calendar_service_upcoming_usd_rows' => $serviceUpcomingRows->count(),
            'calendar_service_upcoming_sample' => $serviceUpcomingRows->take(8)->values(),
            'user_live_cache_key' => $userCacheKey,
            'user_live_cache_snapshot' => $cacheSummary,
            'raw_sample' => $usdRows->take(5)->values(),
            'calendar_service_sample' => $serviceRows->take(5)->values(),
        ]);
    })->name('dashboard.news.debug');

    Route::get('/dashboard/monitoring/live', [DashboardController::class, 'monitoringLive'])->name('dashboard.monitoring.live');

    Route::get('/dashboard/reports/live', [DashboardController::class, 'reportsLive'])->name('dashboard.reports.live');

    Route::get('/dashboard/live-stream', [DashboardController::class, 'liveStream'])->name('dashboard.live-stream');

    Route::post('/dashboard/reports/reset-wr', [DashboardController::class, 'resetReportWr'])->name('dashboard.reports.reset-wr');

    Route::post('/dashboard/risk-consent', function (Request $request) {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'accepted' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $isAdmin = (bool) ($user->is_admin || $role === 'admin');
        $accountId = trim((string) $validated['account_id']);
        $accepted = (bool) $validated['accepted'];

        $configQuery = EaConfiguration::query()->where('account_id', $accountId);
        if (!$isAdmin) {
            $configQuery->where('user_id', (int) $user->id);
        }
        $config = $configQuery->first();

        if ($config === null) {
            return response()->json([
                'success' => false,
                'message' => 'Account tidak ditemukan untuk user ini.',
            ], 404);
        }

        if ($accepted) {
            Mt5RiskConsent::query()->updateOrCreate(
                [
                    'user_id' => (int) $user->id,
                    'account_id' => $accountId,
                ],
                [
                    'accepted_at' => Carbon::now(),
                ]
            );
        } else {
            Mt5RiskConsent::query()
                ->where('user_id', (int) $user->id)
                ->where('account_id', $accountId)
                ->delete();
        }

        return response()->json([
            'success' => true,
            'account_id' => $accountId,
            'accepted' => $accepted,
        ]);
    })->name('dashboard.risk-consent');

    Route::post('/dashboard/bot/toggle', function (Request $request, Mt5LicenseService $licenseService) {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'pair_symbol' => ['nullable', 'string', 'max:32'],
            'action'     => ['required', 'string', 'in:start,stop'],
        ]);

        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $isAdmin = (bool) ($user->is_admin || $role === 'admin');

        $query = EaConfiguration::query()->where('account_id', $validated['account_id']);
        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        $configs = $query->get();
        if ($configs->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Account MT5 tidak ditemukan atau tidak punya akses.',
            ], 404);
        }

        $config = $configs->first();
        if ($config === null) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi account tidak ditemukan.',
            ], 404);
        }

        $licenseStatus = $licenseService->getStatusByAccountId((string) $config->account_id);
        if ($licenseService->isEnforcementEnabled() && !(bool) ($licenseStatus['license_active'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Lisensi account tidak aktif. Start/Stop bot diblokir.',
                'license' => $licenseStatus,
            ], 403);
        }

        $newStatus = $validated['action'] === 'start' ? 'LIVE' : 'PAUSED';

        foreach ($configs as $cfg) {
            if ($newStatus === 'LIVE') {
                $ddBypassKey = 'dd_reset_bypass_user_' . $cfg->user_id . '_account_' . $cfg->account_id;
                Cache::put($ddBypassKey, Carbon::now()->addMinutes(15)->toIso8601String(), now()->addMinutes(20));
            }

            $cfg->update([
                'guard_status' => $newStatus,
                'live_guard_status' => $newStatus,
            ]);

            // Signal EA to refresh config immediately (instead of waiting up to 30s for next poll).
            // For graceful stop: EA will get allow_open_new_cycle=false from the updated config
            // and won't start new cycles, but will finish the current running cycle first.
            if ($newStatus === 'PAUSED') {
                $accountId = (string) $cfg->account_id;
                $pairRaw   = strtoupper((string) ($cfg->pair_symbol ?? ''));
                $pairSymbol = preg_replace('/[^A-Z0-9]/', '', $pairRaw) ?? '';
                $signalKey  = $pairSymbol !== ''
                    ? ('ea:signal:' . $accountId . ':' . $pairSymbol)
                    : ('ea:signal:' . $accountId);
                Cache::put($signalKey, [
                    'action' => 'RELOAD_CONFIG',
                    'source' => 'dashboard_stop_bot',
                    'reason' => 'Stop Bot: graceful stop, selesaikan cycle berjalan',
                    'at'     => now()->toIso8601String(),
                ], now()->addMinutes(5));
            }
        }

        return response()->json([
            'success'      => true,
            'guard_status' => $newStatus,
            'pair_symbol'  => (string) ($config->pair_symbol ?? ''),
            'updated_count' => $configs->count(),
            'message'      => $newStatus === 'LIVE' ? 'Bot diaktifkan.' : 'Bot dihentikan.',
            'license'      => $licenseStatus,
        ]);
    })->name('dashboard.bot.toggle');

    Route::post('/dashboard/bot/toggle-all', function (Request $request) use ($parseAccountWhitelist, $loadBulkControlPolicy) {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:start,stop'],
        ]);

        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $isAdmin = (bool) ($user->is_admin || $role === 'admin');
        if (!$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Start All/Stop All khusus admin.',
            ], 403);
        }

        $policy = $loadBulkControlPolicy();

        if (!(bool) ($policy['enabled'] ?? true)) {
            return response()->json([
                'success' => false,
                'message' => 'Fitur Start All/Stop All sedang dinonaktifkan di server.',
            ], 403);
        }

        $whitelist = is_array($policy['whitelist'] ?? null)
            ? $policy['whitelist']
            : $parseAccountWhitelist((string) config('services.ea.bulk_toggle_account_whitelist', ''));
        if ($whitelist === []) {
            return response()->json([
                'success' => false,
                'message' => 'Whitelist account untuk Start All/Stop All belum diisi.',
            ], 422);
        }

        $query = EaConfiguration::query();

        $eligibleAccounts = $query
            ->whereIn('account_id', $whitelist)
            ->orderBy('account_id')
            ->get();

        if ($eligibleAccounts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada account whitelist yang bisa Anda kontrol.',
                'whitelist' => $whitelist,
            ], 403);
        }

        $newStatus = $validated['action'] === 'start' ? 'LIVE' : 'PAUSED';
        $isStartSync = $validated['action'] === 'start';
        $startSyncAt = $isStartSync ? now()->addSeconds(6) : null;
        $updatedAccounts = [];
        $reloadQueuedAccounts = [];
        $startQueuedAccounts = [];
        $licenseEnforcementEnabled = app(Mt5LicenseService::class)->isEnforcementEnabled();

        foreach ($eligibleAccounts as $config) {
            $licenseStatus = app(Mt5LicenseService::class)->getStatusByAccountId((string) $config->account_id);
            if ($licenseEnforcementEnabled && !(bool) ($licenseStatus['license_active'] ?? false)) {
                continue;
            }

            if ($newStatus === 'LIVE') {
                $ddBypassKey = 'dd_reset_bypass_user_' . $config->user_id . '_account_' . $config->account_id;
                Cache::put($ddBypassKey, Carbon::now()->addMinutes(15)->toIso8601String(), now()->addMinutes(20));
            }

            $config->update([
                'guard_status' => $newStatus,
                'live_guard_status' => $newStatus,
            ]);

            // Graceful stop: signal EA to reload config immediately.
            // EA will get allow_open_new_cycle=false and won't start new cycles,
            // but will finish the current running cycle before fully stopping.
            if ($newStatus === 'PAUSED') {
                $accountId = (string) $config->account_id;
                $pairRaw = strtoupper((string) ($config->pair_symbol ?? ''));
                $pairSymbol = preg_replace('/[^A-Z0-9]/', '', $pairRaw) ?? '';
                $signalKey = $pairSymbol !== ''
                    ? ('ea:signal:' . $accountId . ':' . $pairSymbol)
                    : ('ea:signal:' . $accountId);
                Cache::put($signalKey, [
                    'action' => 'RELOAD_CONFIG',
                    'source' => 'dashboard_stop_all_graceful',
                    'reason' => 'Stop All admin: graceful stop, selesaikan cycle berjalan',
                    'at' => now()->toIso8601String(),
                ], now()->addMinutes(5));
                $reloadQueuedAccounts[] = $accountId;
            }

            if ($isStartSync && $startSyncAt !== null) {
                $accountId = (string) $config->account_id;
                $pairRaw = strtoupper((string) ($config->pair_symbol ?? ''));
                $pairSymbol = preg_replace('/[^A-Z0-9]/', '', $pairRaw) ?? '';
                $signalKey = $pairSymbol !== ''
                    ? ('ea:signal:' . $accountId . ':' . $pairSymbol)
                    : ('ea:signal:' . $accountId);
                Cache::put($signalKey, [
                    'action' => 'START_SYNC',
                    'source' => 'dashboard_start_all_sync',
                    'reason' => 'Start All admin: synchronized start',
                    'start_at' => $startSyncAt->toIso8601String(),
                    'start_unix' => $startSyncAt->timestamp,
                    'at' => now()->toIso8601String(),
                ], now()->addMinutes(5));
                $startQueuedAccounts[] = $accountId;
            }

            $updatedAccounts[] = (string) $config->account_id;
        }

        return response()->json([
            'success' => true,
            'guard_status' => $newStatus,
            'updated_accounts' => $updatedAccounts,
            'reload_queued_accounts' => $reloadQueuedAccounts,
            'start_sync_accounts' => $startQueuedAccounts,
            'start_sync_at' => $startSyncAt?->toIso8601String(),
            'updated_count' => count($updatedAccounts),
            'message' => $newStatus === 'LIVE'
                ? 'Start All sync berhasil, start serentak sudah diantrikan.'
                : 'Stop All berhasil: bot akan berhenti setelah cycle berjalan selesai.',
        ]);
    })->name('dashboard.bot.toggle-all');

    Route::post('/dashboard/positions/close-all', function (Request $request, Mt5LicenseService $licenseService) {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $isAdmin = (bool) ($user->is_admin || $role === 'admin');

        $query = EaConfiguration::query()->where('account_id', $validated['account_id']);
        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        $config = $query->first();
        if ($config === null) {
            return response()->json([
                'success' => false,
                'message' => 'Account MT5 tidak ditemukan atau tidak punya akses.',
            ], 404);
        }

        $licenseStatus = $licenseService->getStatusByAccountId((string) $config->account_id);
        if ($licenseService->isEnforcementEnabled() && !(bool) ($licenseStatus['license_active'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Lisensi account tidak aktif. Close all diblokir.',
                'license' => $licenseStatus,
            ], 403);
        }

        $reason = trim((string) ($validated['reason'] ?? ''));
        if ($reason === '') {
            $reason = 'Manual close all positions from dashboard';
        }

        $accountId = (string) $config->account_id;
        $pairRaw = strtoupper((string) ($config->pair_symbol ?? ''));
        $pairSymbol = preg_replace('/[^A-Z0-9]/', '', $pairRaw) ?? '';
        $signalKey = $pairSymbol !== ''
            ? ('ea:signal:' . $accountId . ':' . $pairSymbol)
            : ('ea:signal:' . $accountId);
        Cache::put($signalKey, [
            'action' => 'CLOSE_ALL',
            'source' => 'dashboard_close_all_positions',
            'reason' => $reason,
            'updated_at' => Carbon::now()->toIso8601String(),
        ], now()->addMinutes(3));

        $config->update([
            'updated_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'account_id' => (string) $config->account_id,
            'message' => 'Sinyal close all positions berhasil dikirim ke EA.',
        ]);
    })->name('dashboard.positions.close-all');

    Route::post('/dashboard/positions/close-one', function (Request $request, Mt5LicenseService $licenseService) {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'ticket' => ['required', 'string', 'max:32', 'regex:/^[0-9]+$/'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $isAdmin = (bool) ($user->is_admin || $role === 'admin');

        $query = EaConfiguration::query()->where('account_id', $validated['account_id']);
        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        $config = $query->first();
        if ($config === null) {
            return response()->json([
                'success' => false,
                'message' => 'Account MT5 tidak ditemukan atau tidak punya akses.',
            ], 404);
        }

        $licenseStatus = $licenseService->getStatusByAccountId((string) $config->account_id);
        if ($licenseService->isEnforcementEnabled() && !(bool) ($licenseStatus['license_active'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Lisensi account tidak aktif. Close one diblokir.',
                'license' => $licenseStatus,
            ], 403);
        }

        $ticket = trim((string) $validated['ticket']);
        $ticketNumeric = (int) $ticket;
        $reason = trim((string) ($validated['reason'] ?? ''));
        if ($reason === '') {
            $reason = 'Manual close ticket ' . $ticket . ' from dashboard';
        }

        $accountId = (string) $config->account_id;
        $pairRaw = strtoupper((string) ($config->pair_symbol ?? ''));
        $pairSymbol = preg_replace('/[^A-Z0-9]/', '', $pairRaw) ?? '';
        $signalKey = $pairSymbol !== ''
            ? ('ea:signal:' . $accountId . ':' . $pairSymbol)
            : ('ea:signal:' . $accountId);
        Cache::put($signalKey, [
            'action' => 'CLOSE_TICKET',
            'ticket' => $ticketNumeric,
            'source' => 'dashboard_close_one_position',
            'reason' => $reason,
            'updated_at' => Carbon::now()->toIso8601String(),
        ], now()->addMinutes(3));

        $config->update([
            'updated_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'account_id' => (string) $config->account_id,
            'ticket' => $ticket,
            'signal_action' => 'CLOSE_TICKET',
            'message' => 'Sinyal close posisi ticket ' . $ticket . ' berhasil dikirim ke EA (butuh dukungan action CLOSE_TICKET di EA).',
        ]);
    })->name('dashboard.positions.close-one');

    Route::get('/dashboard/bot/whitelist', function (Request $request) use ($loadBulkControlPolicy) {
        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $isAdmin = (bool) ($user->is_admin || $role === 'admin');
        if (!$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Admin only.',
            ], 403);
        }

        $policy = $loadBulkControlPolicy();
        return response()->json([
            'success' => true,
            'enabled' => (bool) ($policy['enabled'] ?? true),
            'whitelist' => array_values(array_unique(array_map('strval', $policy['whitelist'] ?? []))),
        ]);
    })->name('dashboard.bot.whitelist.get');

    Route::post('/dashboard/bot/whitelist', function (Request $request) use ($parseAccountWhitelist) {
        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $isAdmin = (bool) ($user->is_admin || $role === 'admin');
        if (!$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Admin only.',
            ], 403);
        }

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'whitelist_text' => ['nullable', 'string', 'max:10000'],
        ]);

        $whitelist = $parseAccountWhitelist((string) ($validated['whitelist_text'] ?? ''));
        $enabled = (bool) $validated['enabled'];

        DashboardSetting::query()->updateOrCreate(
            ['key' => 'bulk_toggle_enabled'],
            ['value' => $enabled ? '1' : '0']
        );
        DashboardSetting::query()->updateOrCreate(
            ['key' => 'bulk_toggle_account_whitelist'],
            ['value' => implode(',', $whitelist)]
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan whitelist bulk control berhasil disimpan.',
            'enabled' => $enabled,
            'whitelist' => $whitelist,
        ]);
    })->name('dashboard.bot.whitelist.update');

    Route::get('/dashboard/account-aliases', function (Request $request) {
        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $isAdmin = (bool) ($user->is_admin || $role === 'admin');

        $ownedAccountIds = [];
        if (!$isAdmin) {
            $ownedAccountIds = EaConfiguration::query()
                ->where('user_id', $user->id)
                ->pluck('account_id')
                ->map(static fn ($id): string => trim((string) $id))
                ->filter(static fn (string $id): bool => $id !== '')
                ->values()
                ->all();
        }

        $raw = (string) (DashboardSetting::query()->where('key', 'account_alias_map')->value('value') ?? '');
        $decoded = $raw !== '' ? json_decode($raw, true) : [];
        $map = is_array($decoded) ? $decoded : [];

        $sanitized = collect($map)
            ->mapWithKeys(static function ($value, $key): array {
                $accountId = trim((string) $key);
                $alias = trim((string) $value);
                if ($accountId === '' || $alias === '') {
                    return [];
                }
                return [$accountId => $alias];
            })
            ->all();

        if (!$isAdmin) {
            $sanitized = collect($sanitized)
                ->only($ownedAccountIds)
                ->all();
        }

        return response()->json([
            'success' => true,
            'aliases' => $sanitized,
        ]);
    })->name('dashboard.account-aliases.get');

    Route::post('/dashboard/account-aliases', function (Request $request) {
        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $isAdmin = (bool) ($user->is_admin || $role === 'admin');

        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'alias' => ['nullable', 'string', 'max:60'],
        ]);

        $accountId = trim((string) $validated['account_id']);
        $alias = trim((string) ($validated['alias'] ?? ''));

        $ownedAccountIds = [];
        if (!$isAdmin) {
            $ownedAccountIds = EaConfiguration::query()
                ->where('user_id', $user->id)
                ->pluck('account_id')
                ->map(static fn ($id): string => trim((string) $id))
                ->filter(static fn (string $id): bool => $id !== '')
                ->values()
                ->all();

            if (!in_array($accountId, $ownedAccountIds, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account ID tidak ditemukan pada akun Anda.',
                ], 403);
            }
        }

        $raw = (string) (DashboardSetting::query()->where('key', 'account_alias_map')->value('value') ?? '');
        $decoded = $raw !== '' ? json_decode($raw, true) : [];
        $map = is_array($decoded) ? $decoded : [];

        if ($alias === '') {
            unset($map[$accountId]);
        } else {
            $map[$accountId] = $alias;
        }

        $sanitized = collect($map)
            ->mapWithKeys(static function ($value, $key): array {
                $id = trim((string) $key);
                $name = trim((string) $value);
                if ($id === '' || $name === '') {
                    return [];
                }
                return [$id => $name];
            })
            ->all();

        $visibleAliases = $isAdmin
            ? $sanitized
            : collect($sanitized)->only($ownedAccountIds)->all();

        DashboardSetting::query()->updateOrCreate(
            ['key' => 'account_alias_map'],
            ['value' => json_encode($sanitized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]
        );

        return response()->json([
            'success' => true,
            'message' => $alias === '' ? 'Alias berhasil dihapus.' : 'Alias berhasil disimpan.',
            'aliases' => $visibleAliases,
        ]);
    })->name('dashboard.account-aliases.update');

    Route::post('/dashboard/bot/reset-dd', function (Request $request) {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
        ]);

        $user = $request->user();
        $role = (string) ($user->role ?? '');
        $isAdmin = (bool) ($user->is_admin || $role === 'admin');

        $query = EaConfiguration::query()->where('account_id', $validated['account_id']);
        if (!$isAdmin) {
            $query->where('user_id', $user->id);
        }

        $config = $query->first();
        if ($config === null) {
            return response()->json([
                'success' => false,
                'message' => 'Account MT5 tidak ditemukan atau tidak punya akses.',
            ], 404);
        }

        // Reset dari DD_STOP ke LIVE + grace period agar tidak langsung auto-lock lagi.
        $ddBypassKey = 'dd_reset_bypass_user_' . $config->user_id . '_account_' . $config->account_id;
        Cache::put($ddBypassKey, Carbon::now()->addMinutes(15)->toIso8601String(), now()->addMinutes(20));

        $config->update([
            'guard_status' => 'LIVE',
            'live_guard_status' => 'LIVE',
        ]);

        return response()->json([
            'success'      => true,
            'guard_status' => 'LIVE',
            'message'      => 'Max Drawdown reset berhasil. Bot dibuka kembali (grace 15 menit).',
        ]);
    })->name('dashboard.bot.reset-dd');

    Route::post('/profile/update', function (Request $request) {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['nullable', 'string'],
            'new_password' => ['nullable', 'string', 'min:8', 'different:current_password'],
            'new_password_confirmation' => ['nullable', 'string'],
        ]);

        if (!empty($validated['new_password'])) {
            if (empty($validated['current_password']) || !Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password saat ini tidak valid.',
                ], 422);
            }

            if (($validated['new_password_confirmation'] ?? '') !== $validated['new_password']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Konfirmasi password baru tidak cocok.',
                ], 422);
            }

            $user->password = Hash::make($validated['new_password']);
        }

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $validated['email'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil diperbarui.',
            'user' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
            ],
        ]);
    })->name('profile.update');

    Route::post('/dashboard/users', function (Request $request) {
        $actor = $request->user();
        $role = (string) ($actor->role ?? '');
        if (!$actor->is_admin && $role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Admin only.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', 'in:user,manager,admin'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'is_admin' => $validated['role'] === 'admin',
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dibuat.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'is_admin' => (bool) $user->is_admin,
            ],
        ]);
    })->name('dashboard.users.store');

    Route::post('/dashboard/users/{user}', function (Request $request, User $user) {
        $actor = $request->user();
        $role = (string) ($actor->role ?? '');
        if (!$actor->is_admin && $role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Admin only.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string', 'in:user,manager,admin'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if ((int) $actor->id === (int) $user->id && $validated['role'] !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Role akun Anda sendiri tidak bisa diturunkan dari admin.',
            ], 422);
        }

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->is_admin = $validated['role'] === 'admin';
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User berhasil diperbarui.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'is_admin' => (bool) $user->is_admin,
            ],
        ]);
    })->name('dashboard.users.update');

    Route::delete('/dashboard/users/{user}', function (Request $request, User $user) {
        $actor = $request->user();
        $role = (string) ($actor->role ?? '');
        if (!$actor->is_admin && $role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Admin only.',
            ], 403);
        }

        if ((int) $actor->id === (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Akun admin yang sedang login tidak bisa dihapus.',
            ], 422);
        }

        EaConfiguration::query()->where('user_id', $user->id)->delete();
        Mt5RiskConsent::query()->where('user_id', $user->id)->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus.',
            'deleted_user_id' => (int) $user->id,
        ]);
    })->name('dashboard.users.delete');
});
