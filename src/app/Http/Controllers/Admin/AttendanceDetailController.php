<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminDetailUpdateRequest;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceDetailController extends Controller
{
  public function show(int $id): View
  {
    /** @var \App\Models\Attendance $attendance */
    $attendance = Attendance::with(['user', 'breakRecords', 'correctionRequests'])
        ->findOrFail($id);

    $pendingCorrectionRequest = $attendance->correctionRequests()
        ->where('status', CorrectionRequest::STATUS_PENDING)
        ->with('correctionBreaks')
        ->latest()
        ->first();

    $hasPendingRequest = (bool) $pendingCorrectionRequest;

    /** @var \Illuminate\Support\Collection<int, \App\Models\BreakRecord> $breakRecords */
    if ($pendingCorrectionRequest) {
      $breakRecords = $pendingCorrectionRequest->correctionBreaks
          ->sortBy('corrected_break_start')
          ->values()
          ->map(static function ($break) {
            return (object) [
              'break_start' => $break->corrected_break_start,
              'break_end'   => $break->corrected_break_end,
            ];
          });
    } else {
      $breakRecords = $attendance->breakRecords()
          ->orderBy('break_start')
          ->get();
    }

    return $this->renderDetailView(
      $attendance,
      $breakRecords,
      $hasPendingRequest,
      $pendingCorrectionRequest
    );
  }

  private function renderDetailView(
    Attendance $attendance,
    Collection $breakRecords,
    bool $hasPendingRequest,
    ?CorrectionRequest $pendingCorrectionRequest
  ): View {
    return view('admin.detail', [
      'attendance'               => $attendance,
      'breakRecords'             => $breakRecords,
      'hasPendingRequest'        => $hasPendingRequest,
      'pendingCorrectionRequest' => $pendingCorrectionRequest,
    ]);
  }

  public function update(AdminDetailUpdateRequest $request, int $id): RedirectResponse
  {
    /** @var \App\Models\Attendance $attendance */
    $attendance = Attendance::with(['breakRecords', 'correctionRequests'])
        ->findOrFail($id);

    // 承認待ちのときは修正禁止
    if ($attendance->correctionRequests()
        ->where('status', CorrectionRequest::STATUS_PENDING)
        ->exists()
    ) {
      return redirect()
          ->route('admin.attendance.detail', ['id' => $attendance->id])
          ->with('status', '承認待ちのため修正はできません。');
    }

    DB::transaction(function () use ($attendance, $request): void {
      // 管理者は attendances を直接更新
      $attendance->update([
        'clock_in'  => $request->input('clock_in'),
        'clock_out' => $request->input('clock_out'),
        'remarks'   => $request->input('remarks'),
      ]);

      // 休憩は一旦全削除してから再登録
      $attendance->breakRecords()->delete();

      foreach ($request->input('breakRecords', []) as $break) {
        if (empty($break['start']) && empty($break['end'])) {
          continue;
        }

        $attendance->breakRecords()->create([
          'break_start' => $break['start'] ?: null,
          'break_end'   => $break['end'] ?: null,
        ]);
      }
    });

    return redirect()
        ->route('admin.attendance.staff.monthly', ['id' => $attendance->user_id])
        ->with('status', '勤怠情報を修正しました。');
  }
}
