<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mt5LicenseBilling extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_id',
        'requested_plan',
        'requested_months',
        'requested_amount',
        'payment_method',
        'payment_reference',
        'mt5_server',
        'mt5_password_encrypted',
        'status',
        'processed_by_user_id',
        'processed_at',
        'tos_accepted_at',
        'notes',
    ];

    protected $casts = [
        'requested_months' => 'integer',
        'requested_amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'tos_accepted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_user_id');
    }
}
