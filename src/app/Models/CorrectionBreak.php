<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CorrectionBreak extends Model
{
    protected $table = 'correction_breaks';

    protected $fillable = [
        'correction_request_id',
        'corrected_break_start',
        'corrected_break_end',
    ];

    public function correctionRequest()
    {
        return $this->belongsTo(CorrectionRequest::class, 'correction_request_id');
    }
}

