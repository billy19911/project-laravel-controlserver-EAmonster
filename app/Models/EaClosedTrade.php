<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EaClosedTrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ea_configuration_id',
        'account_id',
        'ticket',
        'symbol',
        'type',
        'lot',
        'open_price',
        'close_price',
        'profit',
        'swap',
        'commission',
        'open_time_text',
        'close_time_text',
        'open_at',
        'closed_at',
    ];

    protected $casts = [
        'lot' => 'float',
        'open_price' => 'float',
        'close_price' => 'float',
        'profit' => 'float',
        'swap' => 'float',
        'commission' => 'float',
        'open_at' => 'datetime',
        'closed_at' => 'datetime',
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
