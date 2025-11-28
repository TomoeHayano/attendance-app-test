<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $attendance_id
 * @property string|\Illuminate\Support\Carbon $break_start // H:i:s
 * @property string|\Illuminate\Support\Carbon|null $break_end
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Attendance $attendance
 */
class BreakRecord extends Model
{
    protected $table = 'break_records';

    /** @var array<int, string> */
    protected $fillable = [
        'attendance_id',
        'break_start',
        'break_end',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}
