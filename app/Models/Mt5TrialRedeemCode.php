<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mt5TrialRedeemCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'trial_days',
        'is_active',
        'expires_at',
        'generated_by_user_id',
        'redeemed_by_user_id',
        'redeemed_account_id',
        'redeemed_at',
        'notes',
    ];

    protected $casts = [
        'trial_days' => 'integer',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'redeemed_at' => 'datetime',
    ];

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    public function redeemedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by_user_id');
    }
}
