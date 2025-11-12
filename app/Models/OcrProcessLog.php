<?php

namespace App\Models;

use App\Enums\FastApiStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcrProcessLog extends Model
{
    protected $fillable = [
        'report_id',
        'request_id',
        'fastapi_status',
        'ocr_raw',
        'ocr_text_result',
        'received_at',
    ];

    protected $casts = [
        'fastapi_status' => FastApiStatus::class,
        'received_at' => 'datetime',
    ];

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class, 'report_id');
    }
}
