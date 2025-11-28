<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $attendance_id
 * @property int $user_id
 * @property string|null $corrected_clock_in
 * @property string|null $corrected_clock_out
 * @property string|null $remarks
 * @property int $status
 * @property int|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Attendance $attendance
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CorrectionBreak> $correctionBreaks
 * @property-read string $statusLabel
 */
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

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    /** @var array<string, string> */
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
