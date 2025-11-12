<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklySummary extends Model
{
    protected $fillable = [
        'user_id',
        'week_start_date',
        'week_end_date',
        'total_langkah',
        'total_co2e_kg',
        'total_pohon',
        'poin_mingguan',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date' => 'date',
        'total_langkah' => 'integer',
        'total_co2e_kg' => 'decimal:2',
        'total_pohon' => 'decimal:2',
        'poin_mingguan' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
