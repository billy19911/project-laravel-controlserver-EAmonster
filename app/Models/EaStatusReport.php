<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EaStatusReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ea_configuration_id',
        'account_id',
        'current_layers',
        'current_accumulative_lot',
        'global_floating',
        'guard_status',
        'account_currency',
        'balance',
        'equity',
        'open_positions',
        'pending_orders',
        'closed_trades',
        'wins',
        'losses',
        'realized_profit',
        'daily_profit',
        'weekly_profit',
        'monthly_profit',
    ];

    protected $casts = [
        'current_layers' => 'integer',
        'current_accumulative_lot' => 'float',
        'global_floating' => 'float',
        'account_currency' => 'string',
        'balance' => 'float',
        'equity' => 'float',
        'open_positions' => 'array',
        'pending_orders' => 'array',
        'closed_trades' => 'array',
        'wins' => 'integer',
        'losses' => 'integer',
        'realized_profit' => 'float',
        'daily_profit' => 'float',
        'weekly_profit' => 'float',
        'monthly_profit' => 'float',
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
