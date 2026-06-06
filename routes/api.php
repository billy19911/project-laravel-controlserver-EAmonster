<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EaController;
use App\Http\Controllers\Api\V1\BookkeepingController;
use App\Http\Controllers\Api\EaController as CentralEaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('api.token')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
        Route::get('/my/accounts', [EaController::class, 'listMyAccounts']);
        Route::post('/my/accounts', [EaController::class, 'createMyAccount']);
        Route::delete('/my/accounts', [EaController::class, 'deleteMyAccount']);
        Route::get('/my/config', [EaController::class, 'getMyConfig']);
        Route::post('/update-setting', [EaController::class, 'updateSetting']);
        Route::post('/close-all-positions', [EaController::class, 'closeAllPositions']);
        Route::get('/my/status', [EaController::class, 'dashboardStatus']);
        Route::get('/my/statistics', [EaController::class, 'myStatistics']);
        Route::get('/my/reports', [EaController::class, 'myReports']);
        Route::get('/my/monitoring-live', [EaController::class, 'myMonitoringLive']);
        Route::get('/my/report-live', [EaController::class, 'myReportLive']);
        Route::post('/my/report-reset-wr', [EaController::class, 'myReportResetWr']);
        Route::get('/my/bookkeeping/visibility', [BookkeepingController::class, 'visibility']);
        Route::get('/my/bookkeeping/settings', [BookkeepingController::class, 'settings']);
        Route::post('/my/bookkeeping/settings', [BookkeepingController::class, 'updateSettings']);
        Route::get('/my/bookkeeping', [BookkeepingController::class, 'index']);
        Route::post('/my/bookkeeping/save-batch', [BookkeepingController::class, 'saveBatch']);
        Route::get('/debug/config-sync', [EaController::class, 'debugConfigSync']);
        Route::get('/admin/users', [EaController::class, 'adminUsers']);
        Route::get('/admin/users/{userId}/accounts', [EaController::class, 'adminUserAccounts']);
        Route::post('/admin/users/{userId}/role', [EaController::class, 'adminUpdateUserRole']);
        Route::post('/admin/users', [AuthController::class, 'adminCreateUser']);
        Route::put('/admin/users/{userId}', [AuthController::class, 'adminUpdateUser']);
    });

    Route::get('/get-config', [EaController::class, 'getConfig']);
    Route::post('/report-status', [EaController::class, 'reportStatus']);
    Route::get('/economic-calendar', [EaController::class, 'getEconomicCalendar']);
    Route::get('/next-high-impact-news', [EaController::class, 'getNextHighImpactNews']);
});

// Compatibility aliases for existing dashboard integrations.
Route::middleware('api.token')->group(function (): void {
    Route::get('/accounts', [EaController::class, 'listMyAccounts']);
    Route::post('/settings', [EaController::class, 'updateSetting']);
    Route::get('/status', [EaController::class, 'dashboardStatus']);
    Route::get('/debug/config-sync', [EaController::class, 'debugConfigSync']);
});

Route::get('/settings', [EaController::class, 'getConfig']);

Route::post('/report', [EaController::class, 'legacyStatusHeartbeat']);

Route::post('/status', [EaController::class, 'legacyStatusHeartbeat']);

Route::get('/signal/latest', function (Request $request) {
    $accountId = (string) $request->query('account_id', '');
    $pairRaw = strtoupper((string) $request->query('pair_symbol', ''));
    $pairSymbol = preg_replace('/[^A-Z0-9]/', '', $pairRaw) ?? '';

    $signal = null;
    if ($accountId !== '') {
        if ($pairSymbol !== '') {
            $pairKey = 'ea:signal:' . $accountId . ':' . $pairSymbol;
            $signal = Cache::get($pairKey);
        } else {
            $legacyKey = 'ea:signal:' . $accountId;
            $signal = Cache::get($legacyKey);
        }
    }

    return response()->json([
        'success' => true,
        'account_id' => $accountId,
        'pair_symbol' => $pairSymbol,
        'signal' => $signal,
    ]);
});

Route::post('/signal/clear', function (Request $request) {
    $accountId = (string) $request->query('account_id', $request->input('account_id'));
    $pairRaw = strtoupper((string) $request->query('pair_symbol', $request->input('pair_symbol', '')));
    $pairSymbol = preg_replace('/[^A-Z0-9]/', '', $pairRaw) ?? '';

    if ($accountId !== '') {
        if ($pairSymbol !== '') {
            Cache::forget('ea:signal:' . $accountId . ':' . $pairSymbol);
        } else {
            Cache::forget('ea:signal:' . $accountId);
        }
    }

    return response()->json([
        'success' => true,
        'account_id' => $accountId,
        'pair_symbol' => $pairSymbol,
        'cleared' => true,
    ]);
});

// Centralized EA controller endpoints (new architecture).
Route::prefix('ea')->middleware(['ea.security', 'ea.account_limit'])->group(function (): void {
    Route::get('/get-config', [CentralEaController::class, 'getConfig']);
    Route::post('/report-status', [CentralEaController::class, 'reportStatus']);
});

Route::prefix('ea')->middleware('auth')->group(function (): void {
    Route::post('/update-setting', [CentralEaController::class, 'updateSetting']);
});
