<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $correction_request_id
 * @property \Illuminate\Support\Carbon|null $corrected_break_start
 * @property \Illuminate\Support\Carbon|null $corrected_break_end
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\CorrectionRequest $correctionRequest
 */
class CorrectionBreak extends Model
{
  protected $table = 'correction_breaks';

  /** @var array<string> */
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
