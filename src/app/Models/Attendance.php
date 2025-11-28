<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $date
 * @property string|\Illuminate\Support\Carbon|null $clock_in
 * @property string|\Illuminate\Support\Carbon|null $clock_out
 * @property int $status // 0:勤務外,1:出勤中,2:休憩中,3:退勤済
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BreakRecord> $breakRecords
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CorrectionRequest> $correctionRequests
 * @property-read string $statusLabel
 */
class Attendance extends Model
{
  protected $table = 'attendances';

  /** @var array<int, string> */
  protected $fillable = [
    'user_id',
    'date',
    'clock_in',
    'clock_out',
    'status',
  ];

  /** @var array<string, string> */
  protected $casts = [
    'date'   => 'date:Y-m-d',
    'status' => 'int',
  ];

  public const STATUS_OFF_DUTY    = 0; // 勤務外
  public const STATUS_WORKING     = 1; // 出勤中
  public const STATUS_ON_BREAK    = 2; // 休憩中
  public const STATUS_CLOCKED_OUT = 3; // 退勤済

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
