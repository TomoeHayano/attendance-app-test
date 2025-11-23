<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceDetailUpdateRequest;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceDetailController extends Controller
{
    public function show(string $id)
    {
        if (! ctype_digit($id)) {
            try {
                $targetDate = Carbon::createFromFormat('Y-m-d', $id)->toDateString();
            } catch (\Throwable $e) {
                abort(404);
            }

            $attendance = Attendance::firstOrCreate(
                [
                    'user_id' => auth()->id(),
                    'date'    => $targetDate,
                ],
                [
                    'clock_in'  => null,
                    'clock_out' => null,
                    'status'    => Attendance::STATUS_OFF_DUTY,
                ]
            );

            return redirect()->route('attendance.detail.show', $attendance->id);
        }

        $attendance = Attendance::with(['user', 'breakRecords', 'correctionRequests'])
            ->where('user_id', auth()->id())
            ->findOrFail((int) $id);

        $pendingCorrectionRequest = $attendance->correctionRequests()
            ->where('status', CorrectionRequest::STATUS_PENDING)
            ->with('correctionBreaks')
            ->latest()
            ->first();

        $hasPendingRequest = (bool) $pendingCorrectionRequest;

        if ($pendingCorrectionRequest) {
            // 申請中は修正内容をそのまま閲覧できるようにする
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
            $breakRecords = $attendance->breakRecords()->orderBy('break_start')->get();
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
        ?CorrectionRequest $pendingCorrectionRequest = null
    ) {
        return view('attendance.detail', [
            'attendance'                => $attendance,
            'breakRecords'              => $breakRecords,
            'hasPendingRequest'         => $hasPendingRequest,
            'pendingCorrectionRequest'  => $pendingCorrectionRequest,
        ]);
    }

    public function requestCorrection(AttendanceDetailUpdateRequest $request, int $id)
    {
        $attendance = Attendance::where('user_id', auth()->id())->findOrFail($id);

        // すでに承認待ちがある場合は弾く
        if ($attendance->correctionRequests()
            ->where('status', CorrectionRequest::STATUS_PENDING)
            ->exists()
        ) {
            return redirect()
                ->route('attendance.detail.show', $attendance->id)
                ->with('status', '既に承認待ちの修正申請があります。');
        }

        DB::transaction(function () use ($attendance, $request) {
            /** @var \App\Models\CorrectionRequest $correction */
            $correction = $attendance->correctionRequests()->create([
                'user_id'            => auth()->id(),
                'corrected_clock_in' => $request->input('clock_in'),
                'corrected_clock_out'=> $request->input('clock_out'),
                'remarks'            => $request->input('remarks'),
                'status'             => CorrectionRequest::STATUS_PENDING,
            ]);

            foreach ($request->input('breakRecords', []) as $break) {
                if (empty($break['start']) && empty($break['end'])) {
                    continue;
                }

                $correction->correctionBreaks()->create([
                    'corrected_break_start' => $break['start'] ?: null,
                    'corrected_break_end'   => $break['end'] ?: null,
                ]);
            }
        });

        return redirect()
            ->route('attendance.detail.show', $attendance->id)
            ->with('status', '修正申請を受け付けました。');
    }
}
