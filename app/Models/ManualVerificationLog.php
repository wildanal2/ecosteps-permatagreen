<?php

namespace App\Models;

use App\Enums\StatusVerifikasi;
use Illuminate\Database\Eloquent\Model;

class ManualVerificationLog extends Model
{
    protected $fillable = [
        'report_id',
        'image_url',
        'valid_step',
        'app_name',
        'status_verifikasi',
        'validated_by',
    ];

    protected $casts = [
        'valid_step' => 'integer',
        'status_verifikasi' => StatusVerifikasi::class,
    ];

    public function report()
    {
        return $this->belongsTo(DailyReport::class, 'report_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
