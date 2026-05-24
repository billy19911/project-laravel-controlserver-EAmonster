<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mt5AccountLicense extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'ea_configuration_id',
        'plan_name',
        'status',
        'is_perpetual',
        'starts_at',
        'expires_at',
        'granted_by_user_id',
        'notes',
    ];

    protected $casts = [
        'is_perpetual' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(EaConfiguration::class, 'ea_configuration_id');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }
}
