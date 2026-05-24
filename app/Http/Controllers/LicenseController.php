<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BillingChatMessage;
use App\Models\DashboardSetting;
use App\Models\EaConfiguration;
use App\Models\Mt5AccountLicense;
use App\Models\Mt5LicenseBilling;
use App\Models\Mt5TrialRedeemCode;
use App\Models\User;
use App\Services\Mt5LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LicenseController extends Controller
{
    public function __construct(private readonly Mt5LicenseService $licenseService)
    {
    }

    public function billingPage(Request $request)
    {
        $this->purgeExpiredBillingChatMessages();

        $user = $request->user();
        $accountConfigs = EaConfiguration::query()
            ->where('user_id', $user->id)
            ->orderBy('account_id')
            ->get();
        $accounts = $accountConfigs
            ->groupBy(fn (EaConfiguration $configuration) => (string) $configuration->account_id)
            ->map(function ($group) {
                /** @var EaConfiguration $account */
                $account = $group->sortByDesc('id')->first();
                return $account;
            })
            ->values();

        $licenses = [];
        foreach ($accounts as $account) {
            $licenses[(string) $account->account_id] = $this->licenseService->getStatusByAccountId((string) $account->account_id);
        }

        $billings = Mt5LicenseBilling::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $this->markBillingChatMessagesAsRead((int) $user->id, $user);

        $billingConfig = $this->loadBillingConfig();
        $redeemHistory = Mt5TrialRedeemCode::query()
            ->where('redeemed_by_user_id', (int) $user->id)
            ->whereNotNull('redeemed_at')
            ->orderByDesc('redeemed_at')
            ->limit(20)
            ->get();

        return view('licenses.billing', [
            'accounts' => $accounts,
            'licenses' => $licenses,
            'billings' => $billings,
            'billingConfig' => $billingConfig,
            'chatMessages' => $this->loadBillingChatMessages((int) $user->id),
            'redeemHistory' => $redeemHistory,
        ]);
    }

    public function createBilling(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'requested_plan' => ['required', 'string', 'in:monthly'],
            'requested_months' => ['nullable', 'integer', 'min:1', 'max:24'],
            'payment_method' => ['nullable', 'string', 'in:transfer_manual,qris_auto,va_auto'],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $configuration = EaConfiguration::query()
            ->where('account_id', $validated['account_id'])
            ->where('user_id', $user->id)
            ->first();

        if ($configuration === null) {
            return back()->with('error', 'Account ID tidak ditemukan pada akun Anda.');
        }

        $plan = (string) $validated['requested_plan'];
        $paymentMethod = (string) ($validated['payment_method'] ?? 'transfer_manual');

        $billingConfig = $this->loadBillingConfig();
        $autoQrisEnabled = (bool) ($billingConfig['auto_qris_enabled'] ?? false);
        $autoVaEnabled = (bool) ($billingConfig['auto_va_enabled'] ?? false);
        $monthlyPrice = max(0.0, round((float) ($billingConfig['monthly_price'] ?? 0), 2));

        if ($paymentMethod === 'qris_auto' && !$autoQrisEnabled) {
            return back()->with('error', 'Pembayaran otomatis QRIS belum aktif. Gunakan transfer manual terlebih dahulu.');
        }

        if ($paymentMethod === 'va_auto' && !$autoVaEnabled) {
            return back()->with('error', 'Pembayaran otomatis Virtual Account belum aktif. Gunakan transfer manual terlebih dahulu.');
        }

        $requestedMonths = $plan === 'monthly' ? max(1, (int) ($validated['requested_months'] ?? 1)) : 1;
        if ($monthlyPrice <= 0.0) {
            return back()->with('error', 'Harga bulanan belum diatur oleh admin. Hubungi admin untuk aktivasi billing.');
        }
        $amountMeta = $this->calculateBillingAmount($requestedMonths, $billingConfig);
        $requestedAmount = (float) ($amountMeta['final_amount'] ?? 0.0);

        Mt5LicenseBilling::query()->create([
            'user_id' => $user->id,
            'account_id' => (string) $configuration->account_id,
            'requested_plan' => $plan,
            'requested_months' => $requestedMonths,
            'requested_amount' => $requestedAmount,
            'payment_method' => $paymentMethod,
            'payment_reference' => $validated['payment_reference'] ?? null,
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Request billing lisensi berhasil dikirim. Menunggu approval admin.');
    }

    public function adminPage(Request $request)
    {
        if (!$this->isAdmin($request)) {
            abort(403);
        }

        $this->purgeExpiredBillingChatMessages();

        $accountConfigs = EaConfiguration::query()
            ->with('user:id,name,email,username')
            ->orderBy('account_id')
            ->get();
        $accounts = $accountConfigs
            ->groupBy(fn (EaConfiguration $configuration) => (string) $configuration->account_id)
            ->map(function ($group) {
                $sorted = $group->sortByDesc('id')->values();
                /** @var EaConfiguration $account */
                $account = $sorted->first();
                $ownerIds = $sorted
                    ->map(fn (EaConfiguration $configuration) => (int) ($configuration->user_id ?? 0))
                    ->filter(fn (int $userId) => $userId > 0)
                    ->unique()
                    ->values();
                $owners = $sorted
                    ->map(function (EaConfiguration $configuration): string {
                        $user = $configuration->user;
                        return trim((string) ($user?->name ?: $user?->email ?: $user?->username ?: ''));
                    })
                    ->filter(fn (string $name) => $name !== '')
                    ->unique()
                    ->values();
                $account->owner_names = $owners->isNotEmpty() ? $owners->implode(', ') : '-';
                $account->owner_count = $ownerIds->count();
                $account->has_multiple_owners = $ownerIds->count() > 1;
                return $account;
            })
            ->values();
        $users = User::query()
            ->select(['id', 'name', 'email', 'username'])
            ->orderBy('name')
            ->orderBy('email')
            ->get();

        $licenses = Mt5AccountLicense::query()
            ->orderBy('account_id')
            ->get()
            ->keyBy('account_id');

        $pendingBillings = Mt5LicenseBilling::query()
            ->with('user:id,name,email,username')
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->get();

        $processedBillings = Mt5LicenseBilling::query()
            ->with(['user:id,name,email,username', 'processedBy:id,name,email,username'])
            ->whereIn('status', ['approved', 'rejected'])
            ->orderByDesc('processed_at')
            ->orderByDesc('id')
            ->limit(120)
            ->get();
        $redeemCodes = Mt5TrialRedeemCode::query()
            ->with(['generatedBy:id,name,email,username', 'redeemedBy:id,name,email,username'])
            ->orderByDesc('id')
            ->limit(120)
            ->get();

        $chatThreads = $this->buildBillingChatSummaries();
        $selectedChatUserId = (int) ($request->integer('chat_user_id') ?: ($chatThreads[0]['user_id'] ?? 0));
        if ($selectedChatUserId > 0) {
            $this->markBillingChatMessagesAsRead($selectedChatUserId, $request->user());
        }

        return view('licenses.admin', [
            'accounts' => $accounts,
            'users' => $users,
            'licenses' => $licenses,
            'pendingBillings' => $pendingBillings,
            'licenseEnforcementEnabled' => $this->licenseService->isEnforcementEnabled(),
            'billingConfig' => $this->loadBillingConfig(),
            'chatThreads' => $chatThreads,
            'selectedChatUserId' => $selectedChatUserId,
            'initialChatMessages' => $selectedChatUserId > 0 ? $this->loadBillingChatMessages($selectedChatUserId) : [],
            'processedBillings' => $processedBillings,
            'redeemCodes' => $redeemCodes,
        ]);
    }

    public function billingChatThreadJson(Request $request): JsonResponse
    {
        $this->purgeExpiredBillingChatMessages();

        $viewer = $request->user();
        $threadUserId = $this->resolveBillingChatUserId($request);

        $this->markBillingChatMessagesAsRead($threadUserId, $viewer);

        return response()->json([
            'success' => true,
            'thread_user_id' => $threadUserId,
            'messages' => $this->loadBillingChatMessages($threadUserId),
            'pending_billings' => $this->isAdmin($request) ? $this->loadPendingBillingRequestsForUser($threadUserId) : [],
        ]);
    }

    public function billingChatSend(Request $request): JsonResponse
    {
        $this->purgeExpiredBillingChatMessages();

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'user_id' => ['nullable', 'integer'],
        ]);

        $viewer = $request->user();
        $threadUserId = $this->resolveBillingChatUserId($request, true);

        $threadUser = User::query()->find($threadUserId);
        if ($threadUser === null) {
            return response()->json([
                'success' => false,
                'message' => 'Thread user tidak ditemukan.',
            ], 404);
        }

        BillingChatMessage::query()->create([
            'user_id' => $threadUserId,
            'sender_user_id' => (int) $viewer->id,
            'message' => trim((string) $validated['message']),
        ]);

        $this->markBillingChatMessagesAsRead($threadUserId, $viewer);

        return response()->json([
            'success' => true,
            'thread_user_id' => $threadUserId,
            'messages' => $this->loadBillingChatMessages($threadUserId),
            'threads' => $this->isAdmin($request) ? $this->buildBillingChatSummaries() : null,
        ]);
    }

    public function billingChatThreadsJson(Request $request): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            abort(403);
        }

        $this->purgeExpiredBillingChatMessages();

        return response()->json([
            'success' => true,
            'threads' => $this->buildBillingChatSummaries(),
        ]);
    }

    public function billingChatUnreadJson(Request $request): JsonResponse
    {
        $this->purgeExpiredBillingChatMessages();

        $viewer = $request->user();
        $threadUserId = $this->resolveBillingChatUserId($request);

        $unreadCount = BillingChatMessage::query()
            ->where('user_id', $threadUserId)
            ->whereNull('read_at')
            ->where('sender_user_id', '!=', (int) $viewer->id)
            ->count();

        return response()->json([
            'success' => true,
            'thread_user_id' => $threadUserId,
            'unread_count' => (int) $unreadCount,
        ]);
    }

    public function adminSaveBillingConfig(Request $request): RedirectResponse
    {
        if (!$this->isAdmin($request)) {
            abort(403);
        }

        $validated = $request->validate([
            'bank_name' => ['required', 'string', 'max:80'],
            'bank_account_name' => ['required', 'string', 'max:120'],
            'bank_account_number' => ['required', 'string', 'max:50'],
            'bank_note' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:80'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'discount_3_month_pct' => ['nullable', 'numeric', 'min:0', 'max:95'],
            'discount_6_month_pct' => ['nullable', 'numeric', 'min:0', 'max:95'],
            'discount_12_month_pct' => ['nullable', 'numeric', 'min:0', 'max:95'],
            'discount_24_month_pct' => ['nullable', 'numeric', 'min:0', 'max:95'],
            'trial_days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $normalizedContactPhone = $this->normalizeBillingContactPhone((string) ($validated['contact_phone'] ?? ''));
        if ($normalizedContactPhone === null) {
            return back()
                ->withInput()
                ->withErrors([
                    'contact_phone' => 'Format nomor WhatsApp tidak valid. Gunakan format 628xxxxxxx (boleh tulis 08, otomatis dikonversi ke 62).',
                ]);
        }

        DashboardSetting::query()->updateOrCreate(
            ['key' => 'billing_bank_name'],
            ['value' => trim((string) $validated['bank_name'])]
        );
        DashboardSetting::query()->updateOrCreate(
            ['key' => 'billing_bank_account_name'],
            ['value' => trim((string) $validated['bank_account_name'])]
        );
        DashboardSetting::query()->updateOrCreate(
            ['key' => 'billing_bank_account_number'],
            ['value' => trim((string) $validated['bank_account_number'])]
        );
        DashboardSetting::query()->updateOrCreate(
            ['key' => 'billing_bank_note'],
            ['value' => trim((string) ($validated['bank_note'] ?? ''))]
        );
        DashboardSetting::query()->updateOrCreate(
            ['key' => 'billing_contact_name'],
            ['value' => trim((string) ($validated['contact_name'] ?? ''))]
        );
        DashboardSetting::query()->updateOrCreate(
            ['key' => 'billing_contact_phone'],
            ['value' => $normalizedContactPhone]
        );
        DashboardSetting::query()->updateOrCreate(
            ['key' => 'billing_monthly_price'],
            ['value' => (string) round((float) $validated['monthly_price'], 2)]
        );
        DashboardSetting::query()->updateOrCreate(
            ['key' => 'billing_discount_3_month_pct'],
            ['value' => (string) $this->normalizeDiscountPercent($validated['discount_3_month_pct'] ?? 0)]
        );
        DashboardSetting::query()->updateOrCreate(
            ['key' => 'billing_discount_6_month_pct'],
            ['value' => (string) $this->normalizeDiscountPercent($validated['discount_6_month_pct'] ?? 0)]
        );
        DashboardSetting::query()->updateOrCreate(
            ['key' => 'billing_discount_12_month_pct'],
            ['value' => (string) $this->normalizeDiscountPercent($validated['discount_12_month_pct'] ?? 0)]
        );
        DashboardSetting::query()->updateOrCreate(
            ['key' => 'billing_discount_24_month_pct'],
            ['value' => (string) $this->normalizeDiscountPercent($validated['discount_24_month_pct'] ?? 0)]
        );
        DashboardSetting::query()->updateOrCreate(
            ['key' => 'billing_trial_days'],
            ['value' => (string) max(1, (int) ($validated['trial_days'] ?? 3))]
        );
        DashboardSetting::query()->updateOrCreate(
            ['key' => 'billing_auto_gateway_enabled'],
            ['value' => $request->boolean('auto_gateway_enabled') ? 'true' : 'false']
        );
        DashboardSetting::query()->updateOrCreate(
            ['key' => 'billing_auto_qris_enabled'],
            ['value' => $request->boolean('auto_qris_enabled') ? 'true' : 'false']
        );
        DashboardSetting::query()->updateOrCreate(
            ['key' => 'billing_auto_va_enabled'],
            ['value' => $request->boolean('auto_va_enabled') ? 'true' : 'false']
        );

        return back()->with('success', 'Konfigurasi pembayaran berhasil disimpan.');
    }

    public function adminGenerateRedeemCodes(Request $request): RedirectResponse
    {
        if (!$this->isAdmin($request)) {
            abort(403);
        }

        $validated = $request->validate([
            'generate_count' => ['required', 'integer', 'min:1', 'max:100'],
            'trial_days' => ['required', 'integer', 'min:1', 'max:30'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $count = max(1, (int) $validated['generate_count']);
        $trialDays = max(1, (int) $validated['trial_days']);
        $expiresAt = isset($validated['expires_at']) ? Carbon::parse((string) $validated['expires_at']) : null;
        $notes = trim((string) ($validated['notes'] ?? ''));

        $generatedCodes = [];
        DB::transaction(function () use ($count, $trialDays, $expiresAt, $notes, $request, &$generatedCodes): void {
            for ($i = 0; $i < $count; $i++) {
                $code = $this->generateUniqueRedeemCode();
                Mt5TrialRedeemCode::query()->create([
                    'code' => $code,
                    'trial_days' => $trialDays,
                    'is_active' => true,
                    'expires_at' => $expiresAt,
                    'generated_by_user_id' => (int) $request->user()->id,
                    'notes' => $notes !== '' ? $notes : null,
                ]);
                $generatedCodes[] = $code;
            }
        });

        return back()->with('success', 'Berhasil generate ' . count($generatedCodes) . ' redeem code trial.');
    }

    public function adminDeleteLicense(Request $request, int $licenseId): RedirectResponse
    {
        if (!$this->isAdmin($request)) {
            abort(403);
        }

        $license = Mt5AccountLicense::query()->findOrFail($licenseId);
        $accountId = (string) ($license->account_id ?? '-');

        $license->delete();

        return back()->with('success', 'Plan lisensi untuk account ' . $accountId . ' berhasil dihapus.');
    }

    public function adminReassignAccount(Request $request): RedirectResponse
    {
        if (!$this->isAdmin($request)) {
            abort(403);
        }

        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $accountId = trim((string) $validated['account_id']);
        $targetUser = User::query()->find((int) $validated['target_user_id']);
        if ($targetUser === null) {
            return back()->with('error', 'User tujuan tidak ditemukan.');
        }

        $result = DB::transaction(function () use ($accountId, $targetUser): array {
            $configurations = EaConfiguration::query()
                ->where('account_id', $accountId)
                ->get();

            if ($configurations->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'Account tidak ditemukan.',
                ];
            }

            $hasActiveConfig = $configurations->contains(static function (EaConfiguration $configuration): bool {
                return (int) ($configuration->current_layers ?? 0) > 0 || (bool) ($configuration->is_online ?? false);
            });

            if ($hasActiveConfig) {
                return [
                    'success' => false,
                    'message' => 'Account masih aktif. Hentikan bot dan kosongkan layer sebelum pindah owner.',
                ];
            }

            $updatedCount = 0;
            foreach ($configurations as $configuration) {
                if ((int) $configuration->user_id === (int) $targetUser->id) {
                    continue;
                }

                $configuration->user_id = (int) $targetUser->id;
                $configuration->save();
                $updatedCount++;
            }

            return [
                'success' => true,
                'updated_count' => $updatedCount,
                'target_name' => trim((string) ($targetUser->name ?: $targetUser->email ?: $targetUser->username ?: ('User #' . $targetUser->id))),
            ];
        });

        if (!($result['success'] ?? false)) {
            return back()->with('error', (string) ($result['message'] ?? 'Pindah owner gagal diproses.'));
        }

        $updatedCount = (int) ($result['updated_count'] ?? 0);
        $targetName = (string) ($result['target_name'] ?? ('User #' . (int) $targetUser->id));

        if ($updatedCount === 0) {
            return back()->with('success', 'Account ' . $accountId . ' sudah dimiliki ' . $targetName . '.');
        }

        return back()->with('success', 'Owner account ' . $accountId . ' berhasil dipindahkan ke ' . $targetName . '.');
    }

    public function redeemTrialCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'redeem_code' => ['required', 'string', 'max:64'],
        ]);

        $user = $request->user();
        $accountId = trim((string) $validated['account_id']);
        $rawCode = trim((string) $validated['redeem_code']);
        $code = strtoupper(preg_replace('/\s+/', '', $rawCode) ?? '');

        $configuration = EaConfiguration::query()
            ->where('account_id', $accountId)
            ->where('user_id', (int) $user->id)
            ->first();

        if ($configuration === null) {
            return back()->with('error', 'Account ID tidak ditemukan pada akun Anda.');
        }

        $result = DB::transaction(function () use ($code, $accountId, $user, $configuration): array {
            $redeem = Mt5TrialRedeemCode::query()
                ->where('code', $code)
                ->lockForUpdate()
                ->first();

            if ($redeem === null) {
                return ['success' => false, 'message' => 'Redeem code tidak valid.'];
            }

            if (!$redeem->is_active) {
                return ['success' => false, 'message' => 'Redeem code sudah tidak aktif.'];
            }

            if ($redeem->redeemed_at !== null) {
                return ['success' => false, 'message' => 'Redeem code sudah pernah dipakai.'];
            }

            if ($redeem->expires_at !== null && $redeem->expires_at->isPast()) {
                return ['success' => false, 'message' => 'Redeem code sudah kedaluwarsa.'];
            }

            $existing = $this->licenseService->getByAccountId($accountId);
            if ($existing !== null) {
                if ($existing->is_perpetual) {
                    return ['success' => false, 'message' => 'Account sudah memakai lisensi permanent.'];
                }

                $isTrialPlan = str_starts_with(strtolower(trim((string) ($existing->plan_name ?? ''))), 'trial');
                if ($isTrialPlan && $existing->expires_at !== null && $existing->expires_at->greaterThan(Carbon::now())) {
                    return ['success' => false, 'message' => 'Account masih dalam masa trial aktif, tidak bisa redeem trial lagi.'];
                }

                if ($existing->expires_at !== null && $existing->expires_at->greaterThan(Carbon::now())) {
                    return ['success' => false, 'message' => 'Account masih punya lisensi aktif. Redeem trial hanya untuk akun tanpa lisensi aktif.'];
                }
            }

            $trialDays = max(1, (int) ($redeem->trial_days ?? 3));
            $startsAt = Carbon::now();
            $expiresAt = $startsAt->copy()->addDays($trialDays);

            $this->licenseService->upsertLicense(
                $configuration,
                $user,
                false,
                $startsAt,
                $expiresAt,
                'Trial ' . $trialDays . ' Hari',
                'Activated via redeem code ' . $redeem->code
            );

            $redeem->is_active = false;
            $redeem->redeemed_by_user_id = (int) $user->id;
            $redeem->redeemed_account_id = $accountId;
            $redeem->redeemed_at = Carbon::now();
            $redeem->save();

            return ['success' => true, 'message' => 'Redeem berhasil. Trial aktif ' . $trialDays . ' hari untuk account ' . $accountId . '.'];
        });

        if (!($result['success'] ?? false)) {
            return back()->with('error', (string) ($result['message'] ?? 'Redeem gagal diproses.'));
        }

        return back()->with('success', (string) $result['message']);
    }

    private function normalizeBillingContactPhone(string $rawPhone): ?string
    {
        $clean = preg_replace('/[^0-9+]/', '', trim($rawPhone)) ?? '';
        if ($clean === '') {
            return '';
        }

        $phone = ltrim($clean, '+');
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        if (!str_starts_with($phone, '62')) {
            return null;
        }

        if (!preg_match('/^62[1-9][0-9]{7,13}$/', $phone)) {
            return null;
        }

        return $phone;
    }

    public function adminSetEnforcement(Request $request): RedirectResponse
    {
        if (!$this->isAdmin($request)) {
            abort(403);
        }

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        DashboardSetting::query()->updateOrCreate(
            ['key' => 'license_enforcement_enabled'],
            ['value' => ((bool) $validated['enabled']) ? 'true' : 'false']
        );

        return back()->with(
            'success',
            (bool) $validated['enabled']
                ? 'License enforcement berhasil diaktifkan.'
                : 'License enforcement berhasil dimatikan (default OFF).'
        );
    }

    public function adminUpsert(Request $request): RedirectResponse
    {
        if (!$this->isAdmin($request)) {
            abort(403);
        }

        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
            'plan_name' => ['nullable', 'string', 'max:80'],
            'license_mode' => ['required', 'string', 'in:monthly,permanent'],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:36'],
            'starts_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $configuration = EaConfiguration::query()->where('account_id', $validated['account_id'])->first();
        if ($configuration === null) {
            return back()->with('error', 'Account tidak ditemukan.');
        }

        $isPerpetual = (string) $validated['license_mode'] === 'permanent';
        $startsAt = isset($validated['starts_at']) ? Carbon::parse($validated['starts_at']) : null;
        $expiresAt = null;

        if (!$isPerpetual) {
            $months = max(1, (int) ($validated['duration_months'] ?? 1));
            $durationDays = 30 * $months;
            $startsAt = $startsAt ?? Carbon::now();
            $expiresAt = $startsAt->copy()->addDays($durationDays);
        }

        $this->licenseService->upsertLicense(
            $configuration,
            $request->user(),
            $isPerpetual,
            $startsAt,
            $expiresAt,
            (string) ($validated['plan_name'] ?? ($isPerpetual ? 'Permanent Contract' : ('Monthly ' . (int) ($validated['duration_months'] ?? 1) . 'M'))),
            (string) ($validated['notes'] ?? '')
        );

        return back()->with('success', 'Lisensi account berhasil diperbarui.');
    }

    public function adminBillingDecision(Request $request, int $billingId): RedirectResponse
    {
        if (!$this->isAdmin($request)) {
            abort(403);
        }

        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:approve,reject'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = $this->processBillingDecision($request, $billingId, (string) $validated['decision'], (string) ($validated['notes'] ?? ''));
        if (!$result['success']) {
            return back()->with('error', (string) $result['message']);
        }

        return back()->with('success', (string) $result['message']);
    }

    public function adminBillingDecisionJson(Request $request, int $billingId): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            abort(403);
        }

        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:approve,reject'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'user_id' => ['nullable', 'integer'],
        ]);

        $result = $this->processBillingDecision($request, $billingId, (string) $validated['decision'], (string) ($validated['notes'] ?? ''));
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => (string) $result['message'],
            ], 422);
        }

        $threadUserId = (int) ($validated['user_id'] ?? 0);
        if ($threadUserId <= 0) {
            $threadUserId = (int) ($result['thread_user_id'] ?? 0);
        }
        if ($threadUserId <= 0) {
            $threadUserId = $this->resolveBillingChatUserId($request, true);
        }

        return response()->json([
            'success' => true,
            'message' => (string) $result['message'],
            'thread_user_id' => $threadUserId,
            'threads' => $this->buildBillingChatSummaries(),
            'messages' => $threadUserId > 0 ? $this->loadBillingChatMessages($threadUserId) : [],
            'pending_billings' => $threadUserId > 0 ? $this->loadPendingBillingRequestsForUser($threadUserId) : [],
        ]);
    }

    public function statusJson(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_id' => ['required', 'string', 'max:32'],
        ]);

        $configuration = EaConfiguration::query()->where('account_id', $validated['account_id'])->first();
        if ($configuration === null) {
            return response()->json([
                'success' => false,
                'message' => 'Account tidak ditemukan.',
            ], 404);
        }

        if (!$this->isAdmin($request) && (int) $configuration->user_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized account.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->licenseService->getStatusByAccountId((string) $configuration->account_id),
        ]);
    }

    private function loadBillingConfig(): array
    {
        $defaults = [
            'bank_name' => (string) config('services.billing.bank_name', 'BCA'),
            'bank_account_name' => (string) config('services.billing.bank_account_name', 'Nama Pemilik Rekening'),
            'bank_account_number' => (string) config('services.billing.bank_account_number', '-'),
            'bank_note' => (string) config('services.billing.bank_note', 'Transfer ke rekening pribadi di atas lalu isi referensi pembayaran.'),
            'contact_name' => (string) config('services.billing.contact_name', 'Admin Billing'),
            'contact_phone' => (string) config('services.billing.contact_phone', ''),
            'monthly_price' => max(0.0, round((float) config('services.billing.monthly_price', 0), 2)),
            'discount_3_month_pct' => 0.0,
            'discount_6_month_pct' => 0.0,
            'discount_12_month_pct' => 0.0,
            'discount_24_month_pct' => 0.0,
            'trial_days' => 3,
            'auto_gateway_enabled' => (bool) config('services.billing.auto_gateway_enabled', false),
            'auto_qris_enabled' => (bool) config('services.billing.auto_qris_enabled', false),
            'auto_va_enabled' => (bool) config('services.billing.auto_va_enabled', false),
        ];

        try {
            $rows = DashboardSetting::query()
                ->whereIn('key', [
                    'billing_bank_name',
                    'billing_bank_account_name',
                    'billing_bank_account_number',
                    'billing_bank_note',
                    'billing_contact_name',
                    'billing_contact_phone',
                    'billing_monthly_price',
                    'billing_discount_3_month_pct',
                    'billing_discount_6_month_pct',
                    'billing_discount_12_month_pct',
                    'billing_discount_24_month_pct',
                    'billing_trial_days',
                    'billing_auto_gateway_enabled',
                    'billing_auto_qris_enabled',
                    'billing_auto_va_enabled',
                ])
                ->pluck('value', 'key');

            $bankName = trim((string) ($rows->get('billing_bank_name') ?? $defaults['bank_name']));
            $bankAccountName = trim((string) ($rows->get('billing_bank_account_name') ?? $defaults['bank_account_name']));
            $bankAccountNumber = trim((string) ($rows->get('billing_bank_account_number') ?? $defaults['bank_account_number']));
            $bankNote = trim((string) ($rows->get('billing_bank_note') ?? $defaults['bank_note']));
            $contactName = trim((string) ($rows->get('billing_contact_name') ?? $defaults['contact_name']));
            $rawContactPhone = trim((string) ($rows->get('billing_contact_phone') ?? $defaults['contact_phone']));
            $normalizedContactPhone = $this->normalizeBillingContactPhone($rawContactPhone);
            $fallbackDefaultPhone = $this->normalizeBillingContactPhone((string) $defaults['contact_phone']);
            $contactPhone = $normalizedContactPhone !== null
                ? $normalizedContactPhone
                : ($fallbackDefaultPhone ?? '');
            $monthlyPrice = max(0.0, round((float) ($rows->get('billing_monthly_price') ?? $defaults['monthly_price']), 2));
            $discount3 = $this->normalizeDiscountPercent($rows->get('billing_discount_3_month_pct') ?? $defaults['discount_3_month_pct']);
            $discount6 = $this->normalizeDiscountPercent($rows->get('billing_discount_6_month_pct') ?? $defaults['discount_6_month_pct']);
            $discount12 = $this->normalizeDiscountPercent($rows->get('billing_discount_12_month_pct') ?? $defaults['discount_12_month_pct']);
            $discount24 = $this->normalizeDiscountPercent($rows->get('billing_discount_24_month_pct') ?? $defaults['discount_24_month_pct']);
            $trialDays = max(1, min(30, (int) ($rows->get('billing_trial_days') ?? $defaults['trial_days'])));

            $autoGatewayEnabled = $this->toBooleanSetting(
                $rows->get('billing_auto_gateway_enabled'),
                (bool) $defaults['auto_gateway_enabled']
            );
            $autoQrisEnabled = $autoGatewayEnabled && $this->toBooleanSetting(
                $rows->get('billing_auto_qris_enabled'),
                (bool) $defaults['auto_qris_enabled']
            );
            $autoVaEnabled = $autoGatewayEnabled && $this->toBooleanSetting(
                $rows->get('billing_auto_va_enabled'),
                (bool) $defaults['auto_va_enabled']
            );

            return [
                'bank_name' => $bankName !== '' ? $bankName : (string) $defaults['bank_name'],
                'bank_account_name' => $bankAccountName !== '' ? $bankAccountName : (string) $defaults['bank_account_name'],
                'bank_account_number' => $bankAccountNumber !== '' ? $bankAccountNumber : (string) $defaults['bank_account_number'],
                'bank_note' => $bankNote !== '' ? $bankNote : (string) $defaults['bank_note'],
                'contact_name' => $contactName !== '' ? $contactName : (string) $defaults['contact_name'],
                'contact_phone' => $contactPhone !== '' ? $contactPhone : (string) $defaults['contact_phone'],
                'monthly_price' => $monthlyPrice,
                'discount_3_month_pct' => $discount3,
                'discount_6_month_pct' => $discount6,
                'discount_12_month_pct' => $discount12,
                'discount_24_month_pct' => $discount24,
                'trial_days' => $trialDays,
                'auto_gateway_enabled' => $autoGatewayEnabled,
                'auto_qris_enabled' => $autoQrisEnabled,
                'auto_va_enabled' => $autoVaEnabled,
            ];
        } catch (\Throwable) {
            return $defaults;
        }
    }

    private function toBooleanSetting(mixed $value, bool $fallback): bool
    {
        if ($value === null) {
            return $fallback;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($parsed === null) {
            return $fallback;
        }

        return (bool) $parsed;
    }

    private function normalizeDiscountPercent(mixed $value): float
    {
        $parsed = is_numeric($value) ? (float) $value : 0.0;
        return max(0.0, min(95.0, round($parsed, 2)));
    }

    private function resolveDiscountPercent(int $months, array $billingConfig): float
    {
        return match ($months) {
            3 => $this->normalizeDiscountPercent($billingConfig['discount_3_month_pct'] ?? 0),
            6 => $this->normalizeDiscountPercent($billingConfig['discount_6_month_pct'] ?? 0),
            12 => $this->normalizeDiscountPercent($billingConfig['discount_12_month_pct'] ?? 0),
            24 => $this->normalizeDiscountPercent($billingConfig['discount_24_month_pct'] ?? 0),
            default => 0.0,
        };
    }

    private function calculateBillingAmount(int $months, array $billingConfig): array
    {
        $resolvedMonths = max(1, $months);
        $monthlyPrice = max(0.0, round((float) ($billingConfig['monthly_price'] ?? 0), 2));
        $baseAmount = round($resolvedMonths * $monthlyPrice, 2);
        $discountPercent = $this->resolveDiscountPercent($resolvedMonths, $billingConfig);
        $discountAmount = round(($baseAmount * $discountPercent) / 100, 2);
        $finalAmount = max(0.0, round($baseAmount - $discountAmount, 2));

        return [
            'months' => $resolvedMonths,
            'monthly_price' => $monthlyPrice,
            'base_amount' => $baseAmount,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
        ];
    }

    private function generateUniqueRedeemCode(): string
    {
        do {
            $code = 'TRIAL-' . Str::upper(Str::random(8));
        } while (Mt5TrialRedeemCode::query()->where('code', $code)->exists());

        return $code;
    }

    private function resolveBillingChatUserId(Request $request, bool $allowAdminSelection = false): int
    {
        $viewer = $request->user();
        if ($this->isAdmin($request) && $allowAdminSelection) {
            $targetUserId = (int) $request->integer('user_id');
            if ($targetUserId > 0) {
                return $targetUserId;
            }
        }

        if ($this->isAdmin($request)) {
            $targetUserId = (int) $request->integer('user_id');
            if ($targetUserId > 0) {
                return $targetUserId;
            }
        }

        return (int) $viewer->id;
    }

    private function loadBillingChatMessages(int $userId): array
    {
        return BillingChatMessage::query()
            ->with(['sender:id,name,username,is_admin,role'])
            ->where('user_id', $userId)
            ->orderBy('id')
            ->limit(120)
            ->get()
            ->map(function (BillingChatMessage $message): array {
                $sender = $message->sender;

                return [
                    'id' => (int) $message->id,
                    'user_id' => (int) $message->user_id,
                    'sender_user_id' => (int) $message->sender_user_id,
                    'sender_name' => (string) ($sender?->name ?? $sender?->username ?? 'Unknown'),
                    'sender_is_admin' => $this->isAdminUser($sender),
                    'message' => (string) $message->message,
                    'created_at' => optional($message->created_at)?->toIso8601String(),
                    'created_label' => optional($message->created_at)?->format('d M Y H:i') ?? '-',
                    'read_at' => optional($message->read_at)?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    private function markBillingChatMessagesAsRead(int $userId, User $viewer): void
    {
        BillingChatMessage::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->where('sender_user_id', '!=', (int) $viewer->id)
            ->update(['read_at' => Carbon::now()]);
    }

    private function buildBillingChatSummaries(): array
    {
        $messagesByUser = BillingChatMessage::query()
            ->with(['threadUser:id,name,email,username', 'sender:id,name,username,is_admin,role'])
            ->orderByDesc('id')
            ->get()
            ->groupBy('user_id');

        $pendingByUser = Mt5LicenseBilling::query()
            ->with('user:id,name,email,username')
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->get()
            ->groupBy('user_id');

        return collect($messagesByUser->keys())
            ->merge($pendingByUser->keys())
            ->unique()
            ->map(function ($userId) use ($messagesByUser, $pendingByUser): ?array {
                $userId = (int) $userId;
                $threadMessages = $messagesByUser->get($userId, collect());
                $pendingItems = $pendingByUser->get($userId, collect());
                $latestMessage = $threadMessages->first();
                $threadUser = $latestMessage?->threadUser ?? $pendingItems->first()?->user;

                if ($threadUser === null) {
                    return null;
                }

                $latestAt = $latestMessage?->created_at;
                $pendingLatestAt = $pendingItems->first()?->created_at;
                $sortAt = $latestAt && $pendingLatestAt
                    ? ($latestAt->greaterThan($pendingLatestAt) ? $latestAt : $pendingLatestAt)
                    : ($latestAt ?? $pendingLatestAt);

                return [
                    'user_id' => $userId,
                    'user_name' => (string) ($threadUser->name ?? $threadUser->username ?? ('User #' . $userId)),
                    'user_email' => (string) ($threadUser->email ?? '-'),
                    'latest_message' => $latestMessage ? (string) $latestMessage->message : 'Belum ada chat. Admin bisa mulai percakapan dari thread ini.',
                    'latest_at' => $sortAt?->toIso8601String(),
                    'latest_label' => $sortAt?->format('d M Y H:i') ?? '-',
                    'unread_count' => $threadMessages
                        ->filter(fn (BillingChatMessage $message): bool => $message->read_at === null && !$this->isAdminUser($message->sender))
                        ->count(),
                    'pending_billing_count' => $pendingItems->count(),
                ];
            })
            ->filter()
            ->sortByDesc(fn (array $item): string => (string) ($item['latest_at'] ?? ''))
            ->values()
            ->all();
    }

    private function loadPendingBillingRequestsForUser(int $userId): array
    {
        return Mt5LicenseBilling::query()
            ->with('user:id,name,username,email')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->limit(12)
            ->get()
            ->map(function (Mt5LicenseBilling $billing): array {
                return [
                    'id' => (int) $billing->id,
                    'user_id' => (int) $billing->user_id,
                    'account_id' => (string) $billing->account_id,
                    'requested_plan' => (string) $billing->requested_plan,
                    'requested_months' => (int) ($billing->requested_months ?? 0),
                    'requested_amount' => (float) ($billing->requested_amount ?? 0),
                    'payment_method' => (string) ($billing->payment_method ?? ''),
                    'payment_reference' => (string) ($billing->payment_reference ?? ''),
                    'notes' => (string) ($billing->notes ?? ''),
                    'created_label' => optional($billing->created_at)?->format('d M Y H:i') ?? '-',
                    'user_name' => (string) ($billing->user?->name ?? $billing->user?->username ?? ('User #' . (int) $billing->user_id)),
                ];
            })
            ->values()
            ->all();
    }

    private function processBillingDecision(Request $request, int $billingId, string $decision, string $notes = ''): array
    {
        $billing = Mt5LicenseBilling::query()->find($billingId);
        if ($billing === null) {
            return [
                'success' => false,
                'message' => 'Billing request tidak ditemukan.',
            ];
        }

        if ($billing->status !== 'pending') {
            return [
                'success' => false,
                'message' => 'Billing request sudah diproses sebelumnya.',
                'thread_user_id' => (int) $billing->user_id,
            ];
        }

        if ($decision === 'approve') {
            if (trim($notes) !== '') {
                $billing->notes = trim($notes);
                $billing->save();
            }

            $this->licenseService->approveBilling($billing, $request->user());
            return [
                'success' => true,
                'message' => 'Billing disetujui dan lisensi account diaktifkan.',
                'thread_user_id' => (int) $billing->user_id,
            ];
        }

        $billing->status = 'rejected';
        $billing->processed_by_user_id = (int) $request->user()->id;
        $billing->processed_at = Carbon::now();
        if (trim($notes) !== '') {
            $billing->notes = trim($notes);
        }
        $billing->save();

        return [
            'success' => true,
            'message' => 'Billing request ditolak.',
            'thread_user_id' => (int) $billing->user_id,
        ];
    }

    private function isAdminUser(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        $role = (string) ($user->role ?? '');

        return (bool) ($user->is_admin || $role === 'admin');
    }

    private function isAdmin(Request $request): bool
    {
        $user = $request->user();
        $role = (string) ($user->role ?? '');
        return (bool) ($user && ($user->is_admin || $role === 'admin'));
    }

    private function purgeExpiredBillingChatMessages(): void
    {
        BillingChatMessage::query()
            ->where('created_at', '<', Carbon::now()->subDays(30))
            ->delete();
    }
}
