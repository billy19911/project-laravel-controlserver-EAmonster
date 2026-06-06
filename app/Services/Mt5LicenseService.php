<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DashboardSetting;
use App\Models\EaConfiguration;
use App\Models\Mt5AccountLicense;
use App\Models\Mt5LicenseBilling;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class Mt5LicenseService
{
    public function isEnforcementEnabled(): bool
    {
        try {
            $raw = DashboardSetting::query()->where('key', 'license_enforcement_enabled')->value('value');
            if ($raw === null) {
                return false;
            }

            $parsed = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            return $parsed === null ? false : (bool) $parsed;
        } catch (\Throwable) {
            return false;
        }
    }

    public function getByAccountId(string $accountId): ?Mt5AccountLicense
    {
        $accountId = trim($accountId);
        if ($accountId === '') {
            return null;
        }

        return Mt5AccountLicense::query()->with('configuration')->where('account_id', $accountId)->first();
    }

    public function getStatusByAccountId(string $accountId): array
    {
        $enforcementEnabled = $this->isEnforcementEnabled();
        $license = $this->getByAccountId($accountId);
        if ($license === null) {
            return [
                'license_exists' => false,
                'license_status' => 'unlicensed',
                'license_active' => false,
                'license_is_perpetual' => false,
                'license_remaining_seconds' => 0,
                'license_remaining_text' => 'No license',
                'license_starts_at' => null,
                'license_expires_at' => null,
                'license_plan_name' => null,
                'license_message' => 'License belum aktif untuk akun ini.',
                'license_enforcement_enabled' => $enforcementEnabled,
            ];
        }

        return $this->buildSnapshot($license, $enforcementEnabled);
    }

    public function getStatusForConfiguration(EaConfiguration $configuration): array
    {
        $status = $this->getStatusByAccountId((string) $configuration->account_id);
        if (!$status['license_exists']) {
            return $status;
        }

        if ((int) ($status['ea_configuration_id'] ?? 0) === 0 && $configuration->id) {
            Mt5AccountLicense::query()
                ->where('account_id', $configuration->account_id)
                ->whereNull('ea_configuration_id')
                ->update(['ea_configuration_id' => $configuration->id]);
            $status['ea_configuration_id'] = (int) $configuration->id;
        }

        return $status;
    }

    public function getRuntimeStatusForConfiguration(
        EaConfiguration $configuration,
        ?int $runtimeLayers = null,
        ?float $runtimeAccLot = null
    ): array {
        $status = $this->getStatusForConfiguration($configuration);

        return $this->buildRuntimeStatus($configuration, $status, $runtimeLayers, $runtimeAccLot);
    }

    public function upsertLicense(
        EaConfiguration $configuration,
        User $grantedBy,
        bool $isPerpetual,
        ?Carbon $startsAt,
        ?Carbon $expiresAt,
        string $planName,
        string $notes = ''
    ): Mt5AccountLicense {
        $status = 'inactive';
        $now = Carbon::now();

        if ($isPerpetual) {
            $status = 'active';
            $expiresAt = null;
            if ($startsAt === null) {
                $startsAt = $now;
            }
        } elseif ($expiresAt !== null) {
            $status = $expiresAt->greaterThan($now) ? 'active' : 'expired';
            if ($startsAt === null) {
                $startsAt = $now;
            }
        }

        $license = Mt5AccountLicense::query()->updateOrCreate(
            ['account_id' => (string) $configuration->account_id],
            [
                'ea_configuration_id' => (int) $configuration->id,
                'plan_name' => trim($planName) !== '' ? trim($planName) : 'Monthly',
                'status' => $status,
                'is_perpetual' => $isPerpetual,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'granted_by_user_id' => (int) $grantedBy->id,
                'notes' => $notes,
            ]
        );

        if ($status === 'active') {
            $this->clearRuntimeRestrictionState($configuration);
        }

        return $license;
    }

    public function approveBilling(Mt5LicenseBilling $billing, User $processedBy): Mt5AccountLicense
    {
        $configuration = EaConfiguration::query()->where('account_id', $billing->account_id)->firstOrFail();
        $now = Carbon::now();

        $existing = $this->getByAccountId($billing->account_id);
        $isPerpetual = $billing->requested_plan === 'permanent';
        $startsAt = $now;
        $expiresAt = null;

        if ($isPerpetual) {
            $expiresAt = null;
        } else {
            $months = max(1, (int) $billing->requested_months);
            $durationDays = 30 * $months;
            $base = $now;
            if ($existing !== null && !$existing->is_perpetual && $existing->expires_at !== null && $existing->expires_at->greaterThan($now)) {
                $base = $existing->expires_at->copy();
            }
            $expiresAt = $base->copy()->addDays($durationDays);
            if ($existing !== null && $existing->starts_at !== null) {
                $startsAt = $existing->starts_at->copy();
            }
        }

        $license = $this->upsertLicense(
            $configuration,
            $processedBy,
            $isPerpetual,
            $startsAt,
            $expiresAt,
            $isPerpetual ? 'Permanent Contract' : 'Monthly Subscription',
            (string) ($billing->notes ?? '')
        );

        $billing->status = 'approved';
        $billing->processed_by_user_id = (int) $processedBy->id;
        $billing->processed_at = $now;
        $billing->save();

        Mt5LicenseBilling::query()
            ->where('id', '!=', (int) $billing->id)
            ->where('user_id', (int) $billing->user_id)
            ->where('account_id', (string) $billing->account_id)
            ->where('status', 'pending')
            ->update([
                'status' => 'rejected',
                'processed_by_user_id' => (int) $processedBy->id,
                'processed_at' => $now,
                'notes' => 'Auto-closed: duplicate pending request setelah approval billing lain untuk account ini.',
            ]);

        return $license;
    }

    private function buildSnapshot(Mt5AccountLicense $license, bool $enforcementEnabled): array
    {
        $now = Carbon::now();
        $status = strtolower((string) ($license->status ?? 'inactive'));
        $active = false;
        $remainingSeconds = 0;

        $configuration = $license->configuration;
        $currentLayers = max(0, (int) ($configuration?->current_layers ?? 0));
        $currentAccLot = max(0.0, (float) ($configuration?->current_accumulative_lot ?? 0.0));
        $hasActiveExposure = $currentLayers > 0 || $currentAccLot > 0.0000001;

        if ($license->is_perpetual) {
            $active = $status !== 'suspended';
            $remainingSeconds = 315360000;
        } elseif ($license->expires_at !== null) {
            $remainingSeconds = max(0, $now->diffInSeconds($license->expires_at, false));
            $active = $remainingSeconds > 0 && in_array($status, ['active', 'expired', 'inactive'], true);
            if ($status === 'expired' || $remainingSeconds <= 0 || $license->expires_at->lessThanOrEqualTo($now)) {
                $active = false;
                $status = 'expired';
            } elseif ($status === 'inactive') {
                $status = 'active';
            }
        }

        $licenseGracePeriod = $enforcementEnabled
            && !$license->is_perpetual
            && !$active
            && $license->expires_at !== null
            && $license->expires_at->lessThanOrEqualTo($now)
            && $hasActiveExposure;

        $effectiveStatus = $licenseGracePeriod ? 'expired_grace_period' : $status;
        $planName = (string) ($license->plan_name ?? '');
        $isTrialPlan = str_contains(strtolower($planName), 'trial');
        $isExpiredTrialVisibleAsNoLicense = $isTrialPlan && !$licenseGracePeriod && $effectiveStatus === 'expired';
        $canStartNewCycle = !$enforcementEnabled || $active;
        $canManageExistingCycle = !$enforcementEnabled || $active || $licenseGracePeriod;
        $runtimeActive = !$enforcementEnabled || $active || $licenseGracePeriod;

        return [
            'license_exists' => true,
            'ea_configuration_id' => (int) ($license->ea_configuration_id ?? 0),
            'license_status' => $isExpiredTrialVisibleAsNoLicense ? 'unlicensed' : $effectiveStatus,
            'license_active' => $active,
            'license_is_perpetual' => (bool) $license->is_perpetual,
            'license_remaining_seconds' => $isExpiredTrialVisibleAsNoLicense ? 0 : (int) $remainingSeconds,
            'license_remaining_text' => $isExpiredTrialVisibleAsNoLicense
                ? 'No license'
                : $this->humanDuration((int) $remainingSeconds, (bool) $license->is_perpetual),
            'license_starts_at' => optional($license->starts_at)?->toIso8601String(),
            'license_expires_at' => optional($license->expires_at)?->toIso8601String(),
            'license_plan_name' => $planName,
            'license_message' => $licenseGracePeriod
                ? 'Lisensi expired, tetapi cycle masih berjalan. New cycle diblokir sampai lisensi diperpanjang.'
                : ($active
                    ? ((bool) $license->is_perpetual ? 'Lisensi permanent aktif.' : 'Lisensi aktif.')
                    : ($isExpiredTrialVisibleAsNoLicense
                        ? 'Masa trial berakhir. Status lisensi berubah menjadi No License.'
                        : 'Lisensi expired / belum aktif. Silakan berlangganan.')),
            'license_enforcement_enabled' => $enforcementEnabled,
            'license_grace_period' => $licenseGracePeriod,
            'license_has_active_exposure' => $hasActiveExposure,
            'license_can_start_new_cycle' => $canStartNewCycle,
            'license_can_manage_existing_cycle' => $canManageExistingCycle,
            'license_requires_pause_after_cycle' => $enforcementEnabled && !$active && !$licenseGracePeriod,
            'is_active' => $runtimeActive ? 1 : 0,
            'is_trading_active' => $runtimeActive ? 1 : 0,
        ];
    }

    private function buildRuntimeStatus(
        EaConfiguration $configuration,
        array $licenseStatus,
        ?int $runtimeLayers = null,
        ?float $runtimeAccLot = null
    ): array {
        $enforcementEnabled = (bool) ($licenseStatus['license_enforcement_enabled'] ?? $this->isEnforcementEnabled());
        $licenseActive = (bool) ($licenseStatus['license_active'] ?? false);
        $expiresAtText = (string) ($licenseStatus['license_expires_at'] ?? '');
        $expiresAt = null;
        if ($expiresAtText !== '') {
            try {
                $expiresAt = Carbon::parse($expiresAtText);
            } catch (\Throwable) {
                $expiresAt = null;
            }
        }

        $layers = max(0, (int) ($runtimeLayers ?? $configuration->current_layers ?? 0));
        $accLot = max(0.0, (float) ($runtimeAccLot ?? $configuration->current_accumulative_lot ?? 0.0));
        $hasActiveExposure = $layers > 0 || $accLot > 0.0000001;

        $isExpiredGrace = $enforcementEnabled
            && !$licenseActive
            && $expiresAt !== null
            && Carbon::now()->greaterThan($expiresAt)
            && $hasActiveExposure;

        $runtimeActive = $enforcementEnabled ? ($licenseActive || $isExpiredGrace) : true;

        $effectiveStatus = (string) ($licenseStatus['license_status'] ?? 'unlicensed');
        if ($isExpiredGrace) {
            $effectiveStatus = 'expired_grace_period';
        }

        return array_merge($licenseStatus, [
            'license_status' => $effectiveStatus,
            'license_grace_period' => $isExpiredGrace,
            'license_has_active_exposure' => $hasActiveExposure,
            'license_can_start_new_cycle' => !$enforcementEnabled || $licenseActive,
            'license_can_manage_existing_cycle' => !$enforcementEnabled || $licenseActive || $isExpiredGrace,
            'license_requires_pause_after_cycle' => $enforcementEnabled && !$licenseActive && !$isExpiredGrace,
            'is_active' => $runtimeActive ? 1 : 0,
            'is_trading_active' => $runtimeActive ? 1 : 0,
        ]);
    }

    private function clearRuntimeRestrictionState(EaConfiguration $configuration): void
    {
        $keys = [
            'dd_breach_hits_user_' . $configuration->user_id . '_account_' . $configuration->account_id,
            'dd_reset_bypass_user_' . $configuration->user_id . '_account_' . $configuration->account_id,
            'ea_status_report_gate_' . $configuration->account_id,
            'ea_status_prune_lock_' . $configuration->account_id,
            'closed_trades_seed_last_report_account_' . $configuration->account_id,
            'wr_reset_ts_account_' . $configuration->account_id,
            'ea_license_last_active_' . $configuration->account_id,
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        Cache::forget($this->signalCacheKey((string) $configuration->account_id, (string) ($configuration->pair_symbol ?? '')));
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

    private function humanDuration(int $seconds, bool $perpetual): string
    {
        if ($perpetual) {
            return 'Permanent';
        }

        $seconds = max(0, $seconds);
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        return sprintf('%dd %02dh %02dm %02ds', $days, $hours, $minutes, $secs);
    }
}
