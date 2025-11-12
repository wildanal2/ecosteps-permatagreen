<?php

namespace App\Models;

use App\Enums\StatusVerifikasi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyReport extends Model
{
    protected $fillable = [
        'user_id',
        'tanggal_laporan',
        'langkah',
        'co2e_reduction_kg',
        'poin',
        'pohon',
        'status_verifikasi',
        'ocr_result',
        'bukti_screenshot', // url s3
        'count_document',
        'verified_at',
    ];

    protected $casts = [
        'tanggal_laporan' => 'date',
        'langkah' => 'integer',
        'co2e_reduction_kg' => 'decimal:2',
        'poin' => 'integer',
        'pohon' => 'decimal:2',
        'status_verifikasi' => StatusVerifikasi::class,
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ocrProcessLogs(): HasMany
    {
        return $this->hasMany(OcrProcessLog::class, 'report_id');
    }
}
