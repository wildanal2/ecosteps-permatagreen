<?php

namespace App\Models;

use App\Enums\StatusVerifikasi;
use App\Enums\VerifiedBy;
use Illuminate\Database\Eloquent\Model;

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
        'bukti_screenshot',
        'ocr_result',
        'count_document',
        'verified_at',
        'verified_id', //ADMIN / sistem
        'verified_by', // id admin auth()->user id
        'manual_verification_requested',
        'manual_verification_requested_at',
    ];

    protected $casts = [
        'tanggal_laporan' => 'date',
        'langkah' => 'integer',
        'co2e_reduction_kg' => 'decimal:2',
        'poin' => 'integer',
        'pohon' => 'decimal:2',
        'status_verifikasi' => StatusVerifikasi::class,
        'verified_at' => 'datetime',
        'verified_id' => VerifiedBy::class,
        'manual_verification_requested' => 'boolean',
        'manual_verification_requested_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function ocrProcessLogs()
    {
        return $this->hasMany(OcrProcessLog::class, 'report_id');
    }

    public function manualVerificationLogs()
    {
        return $this->hasMany(ManualVerificationLog::class, 'report_id');
    }
}
