<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStatistic extends Model
{
    protected $fillable = [
        'user_id',
        'total_langkah',
        'total_co2e_kg',
        'total_pohon',
        'current_streak',
        'last_update',
    ];

    protected $casts = [
        'total_langkah' => 'integer',
        'total_co2e_kg' => 'decimal:2',
        'total_pohon' => 'decimal:2',
        'current_streak' => 'integer',
        'last_update' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
