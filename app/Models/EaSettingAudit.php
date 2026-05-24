<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EaSettingAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ea_configuration_id',
        'account_id',
        'changed_fields',
        'before_values',
        'after_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changed_fields' => 'array',
        'before_values' => 'array',
        'after_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(EaConfiguration::class, 'ea_configuration_id');
    }
}
