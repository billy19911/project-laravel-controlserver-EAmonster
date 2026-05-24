<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DashboardSetting;
use App\Models\EaConfiguration;
use App\Models\Mt5AccountLicense;
use App\Models\Mt5LicenseBilling;
use App\Models\User;
use Illuminate\Support\Carbon;

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

        return Mt5AccountLicense::query()->where('account_id', $accountId)->first();
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

        return Mt5AccountLicense::query()->updateOrCreate(
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

        return $license;
    }

    private function buildSnapshot(Mt5AccountLicense $license, bool $enforcementEnabled): array
    {
        $now = Carbon::now();
        $status = strtolower((string) ($license->status ?? 'inactive'));
        $active = false;
        $remainingSeconds = 0;

        if ($license->is_perpetual) {
            $active = $status !== 'suspended';
            $remainingSeconds = 315360000; // virtual 10 years for UI timer fallback
        } elseif ($license->expires_at !== null) {
            $remainingSeconds = max(0, $now->diffInSeconds($license->expires_at, false));
            $active = $remainingSeconds > 0 && in_array($status, ['active', 'expired', 'inactive'], true);
            if ($status === 'expired' || $remainingSeconds <= 0) {
                $active = false;
                $status = 'expired';
            } elseif ($status === 'inactive') {
                $status = 'active';
            }
        }

        return [
            'license_exists' => true,
            'ea_configuration_id' => (int) ($license->ea_configuration_id ?? 0),
            'license_status' => $status,
            'license_active' => $active,
            'license_is_perpetual' => (bool) $license->is_perpetual,
            'license_remaining_seconds' => (int) $remainingSeconds,
            'license_remaining_text' => $this->humanDuration((int) $remainingSeconds, (bool) $license->is_perpetual),
            'license_starts_at' => optional($license->starts_at)?->toIso8601String(),
            'license_expires_at' => optional($license->expires_at)?->toIso8601String(),
            'license_plan_name' => (string) ($license->plan_name ?? ''),
            'license_message' => $active
                ? ((bool) $license->is_perpetual ? 'Lisensi permanent aktif.' : 'Lisensi aktif.')
                : 'Lisensi expired / belum aktif. Silakan berlangganan.',
            'license_enforcement_enabled' => $enforcementEnabled,
        ];
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
