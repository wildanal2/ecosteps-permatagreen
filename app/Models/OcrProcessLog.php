<?php

namespace App\Models;

use App\Enums\FastApiStatus;
use App\Enums\StatusVerifikasi;
use App\Enums\VerifiedBy;
use App\Enums\WalkAppSupport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcrProcessLog extends Model
{
    protected $fillable = [
        'report_id',
        'request_id',
        'fastapi_status',
        'detect_aplication_id',
        'ocr_raw',
        'ocr_text_result',
        'status_verifikasi',
        'received_at',
        'img_url',
        'verified_id',
        'verified_by',
        'ocr_process_time_ms',
    ];

    protected $casts = [
        'fastapi_status' => FastApiStatus::class,
        'status_verifikasi' => StatusVerifikasi::class,
        'detect_aplication_id' => WalkAppSupport::class,
        'received_at' => 'datetime',
        'verified_id' => VerifiedBy::class,
    ];

    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class, 'report_id');
    }
}
