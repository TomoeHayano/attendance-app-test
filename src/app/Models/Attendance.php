<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $user_id
 * @property string $date
 * @property string|null $clock_in
 * @property string|null $clock_out
 * @property int $status            // 0:勤務外,1:出勤中,2:休憩中,3:退勤済
 * @property-read User $user
 */
class Attendance extends Model
{
    protected $table = 'attendances';

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'status',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'status' => 'int',
    ];

    // ステータス定数
    public const STATUS_OFF_DUTY   = 0; // 勤務外
    public const STATUS_WORKING    = 1; // 出勤中
    public const STATUS_ON_BREAK   = 2; // 休憩中
    public const STATUS_CLOCKED_OUT= 3; // 退勤済

    public function breakRecords(): HasMany
    {
        return $this->hasMany(BreakRecord::class, 'attendance_id');
    }

    public function correctionRequests(): HasMany
    {
        return $this->hasMany(CorrectionRequest::class, 'attendance_id');
    }

    /**
     * 勤怠に紐づくユーザー
     *
     * @return BelongsTo<User, Attendance>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_WORKING     => '出勤中',
            self::STATUS_ON_BREAK    => '休憩中',
            self::STATUS_CLOCKED_OUT => '退勤済',
            default                  => '勤務外',
        };
    }
}