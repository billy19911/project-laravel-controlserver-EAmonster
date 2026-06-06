<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookkeepingEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_id',
        'entry_date',
        'daily_profit_usd',
        'exchange_rate_idr',
        'profit_idr',
        'notes',
    ];

    protected $casts = [
        'entry_date' => 'date:Y-m-d',
        'daily_profit_usd' => 'float',
        'exchange_rate_idr' => 'float',
        'profit_idr' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
