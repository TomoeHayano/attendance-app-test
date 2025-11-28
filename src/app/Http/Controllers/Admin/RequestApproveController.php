<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RequestApproveController extends Controller
{
    /**
     * 修正申請承認画面（詳細表示）
     */
    public function show(int $correction_request_id): View
    {
        $correctionRequest = CorrectionRequest::with([
            'attendance.user',
            'correctionBreaks',
        ])->findOrFail($correction_request_id);

        return view('admin.approve', [
            'correctionRequest' => $correctionRequest,
            'attendance'        => $correctionRequest->attendance,
            'breakRecords'      => $correctionRequest->correctionBreaks,
        ]);
    }

    /**
     * 承認処理
     */
    public function approve(int $correction_request_id): RedirectResponse
    {
        DB::transaction(function () use ($correction_request_id): void {

            $correctionRequest = CorrectionRequest::with([
                'attendance',
                'correctionBreaks',
            ])->lockForUpdate()->findOrFail($correction_request_id);

            // ▼ 正しい「承認待ち」チェック（1でなければ何もしない）
            if ($correctionRequest->status !== CorrectionRequest::STATUS_PENDING) {
                return;
            }

            $attendance = $correctionRequest->attendance;

            /**
             * 1) 勤怠情報の上書き
             */
            $attendance->clock_in  = $correctionRequest->corrected_clock_in;
            $attendance->clock_out = $correctionRequest->corrected_clock_out;
            $attendance->save();

            /**
             * 2) 休憩の上書き
             */
            $attendance->breakRecords()->delete();

            foreach ($correctionRequest->correctionBreaks as $cb) {
                $attendance->breakRecords()->create([
                    'attendance_id' => $attendance->id,
                    'break_start'   => $cb->corrected_break_start,
                    'break_end'     => $cb->corrected_break_end,
                ]);
            }

            /**
             * 3) 修正申請のステータス更新
             */
            $correctionRequest->status      = CorrectionRequest::STATUS_APPROVED; // ここを「2」扱いに
            $correctionRequest->approved_by = Auth::id();
            $correctionRequest->approved_at = now();
            $correctionRequest->save();
        });

        return redirect()->route('admin.stamp_correction_request.list', ['tab' => 'approved']);
    }
}
