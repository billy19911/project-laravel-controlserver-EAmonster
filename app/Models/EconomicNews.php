<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EconomicNews extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_at',
        'currency',
        'impact',
        'title',
        'ai_analysis',
        'ai_verdict',
        'raw_payload',
    ];

    protected $casts = [
        'event_at' => 'datetime',
        'raw_payload' => 'array',
    ];
}
