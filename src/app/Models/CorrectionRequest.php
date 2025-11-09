<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CorrectionRequest extends Model
{
    public const STATUS_PENDING  = 1; // 承認待ち
    public const STATUS_APPROVED = 2;
    public const STATUS_REJECTED = 3;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'corrected_clock_in',
        'corrected_clock_out',
        'remarks',
        'status',
    ];

    public function breakRecords(): HasMany
    {
        return $this->hasMany(CorrectionBreak::class, 'correction_request_id');
    }
}