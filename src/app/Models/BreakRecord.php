<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $attendance_id
 * @property string $break_start  // H:i:s
 * @property string|null $break_end
 */
class BreakRecord extends Model
{
    protected $table = 'break_records';

    protected $fillable = [
        'attendance_id', 'break_start', 'break_end',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}