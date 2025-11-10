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
    // public const STATUS_REJECTED = 3; // 差戻し 等あれば

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
            // self::STATUS_REJECTED => '差戻し',
            default               => '不明',
        };
    }
}