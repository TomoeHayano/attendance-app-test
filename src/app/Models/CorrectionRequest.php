<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectionRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING  = 1; // 承認待ち
    public const STATUS_APPROVED = 2; // 承認済み

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'attendance_id',
        'user_id',
        'corrected_clock_in',
        'corrected_clock_out',
        'remarks',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function correctionBreaks(): HasMany
    {
        return $this->hasMany(CorrectionBreak::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING  => '承認待ち',
            self::STATUS_APPROVED => '承認済み',
            default               => '不明',
        };
    }
}