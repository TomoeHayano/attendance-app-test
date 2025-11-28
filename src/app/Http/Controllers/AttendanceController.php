<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\BreakRecord;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $timezone  = (string) config('app.timezone');
        $todayDate = Carbon::today($timezone)->toDateString();
        $now       = Carbon::now($timezone);

        $attendance = Attendance::query()
            ->where('user_id', $request->user()->id)
            ->whereDate('date', $todayDate)
            ->with('breakRecords')
            ->first();

        $statusLabel = $attendance?->statusLabel() ?? '勤務外';

        return view('attendance.index', [
            'now'           => $now,
            'displayDate'   => $this->formatJapaneseDate($now),
            'statusLabel'   => $statusLabel,
            'canClockIn'    => $attendance          === null || $attendance->status === Attendance::STATUS_OFF_DUTY,
            'canBreakStart' => $attendance?->status === Attendance::STATUS_WORKING,
            'canBreakEnd'   => $attendance?->status === Attendance::STATUS_ON_BREAK,
            'canClockOut'   => $attendance?->status === Attendance::STATUS_WORKING,
            'statusMessage' => $this->resolveStatusMessage($attendance),
        ]);
    }

    /**
     * 出勤処理（1日1回のみ）
     */
    public function clockIn(Request $request): RedirectResponse
    {
        $timezone    = (string) config('app.timezone');
        $todayDate   = Carbon::today($timezone)->toDateString();
        $currentTime = Carbon::now($timezone)->format('H:i:s');

        return DB::transaction(function () use ($request, $todayDate, $currentTime): RedirectResponse {
            $userId = (int) $request->user()->id;

            $attendance = Attendance::where('user_id', $userId)
                ->whereDate('date', $todayDate)
                ->lockForUpdate()
                ->first();

            if ($attendance === null) {
                Attendance::create([
                    'user_id'  => $userId,
                    'date'     => $todayDate,
                    'clock_in' => $currentTime,
                    'status'   => Attendance::STATUS_WORKING,
                ]);
            } elseif ((int) $attendance->status === Attendance::STATUS_OFF_DUTY) {
                $attendance->update([
                    'clock_in' => $currentTime,
                    'status'   => Attendance::STATUS_WORKING,
                ]);
            } else {
                return redirect()->route('attendance.action')
                    ->withErrors(['attendance' => '本日は既に出勤済みです。']);
            }

            return redirect()->route('attendance.action');
        });
    }

    /**
     * 休憩開始処理（複数回可）
     */
    public function breakStart(Request $request): RedirectResponse
    {
        $timezone    = (string) config('app.timezone');
        $todayDate   = Carbon::today($timezone)->toDateString();
        $currentTime = Carbon::now($timezone)->format('H:i:s');

        return DB::transaction(function () use ($request, $todayDate, $currentTime): RedirectResponse {
            $attendance = Attendance::where('user_id', $request->user()->id)
                ->whereDate('date', $todayDate)
                ->lockForUpdate()
                ->first();

            if ($attendance === null || $attendance->status !== Attendance::STATUS_WORKING) {
                return redirect()->route('attendance.action')
                    ->withErrors(['attendance' => '休憩入は出勤中のみ可能です。']);
            }

            BreakRecord::create([
                'attendance_id' => $attendance->id,
                'break_start'   => $currentTime,
            ]);

            $attendance->update(['status' => Attendance::STATUS_ON_BREAK]);

            return redirect()->route('attendance.action');
        });
    }

    /**
     * 休憩終了処理（直近の休憩レコードをクローズ）
     */
    public function breakEnd(Request $request): RedirectResponse
    {
        $timezone    = (string) config('app.timezone');
        $todayDate   = Carbon::today($timezone)->toDateString();
        $currentTime = Carbon::now($timezone)->format('H:i:s');

        return DB::transaction(function () use ($request, $todayDate, $currentTime): RedirectResponse {
            $attendance = Attendance::where('user_id', $request->user()->id)
                ->whereDate('date', $todayDate)
                ->lockForUpdate()
                ->first();

            if ($attendance === null || $attendance->status !== Attendance::STATUS_ON_BREAK) {
                return redirect()->route('attendance.action')
                    ->withErrors(['attendance' => '休憩戻は休憩中のみ可能です。']);
            }

            $latestBreak = BreakRecord::where('attendance_id', $attendance->id)
                ->whereNull('break_end')
                ->latest('id')
                ->first();

            if ($latestBreak === null) {
                return redirect()->route('attendance.action')
                    ->withErrors(['attendance' => '未終了の休憩が見つかりません。']);
            }

            $latestBreak->update(['break_end' => $currentTime]);
            $attendance->update(['status' => Attendance::STATUS_WORKING]);

            return redirect()->route('attendance.action');
        });
    }

    /**
     * 退勤処理（1日1回のみ）
     */
    public function clockOut(Request $request): RedirectResponse
    {
        $timezone    = (string) config('app.timezone');
        $todayDate   = Carbon::today($timezone)->toDateString();
        $currentTime = Carbon::now($timezone)->format('H:i:s');

        return DB::transaction(function () use ($request, $todayDate, $currentTime): RedirectResponse {
            $attendance = Attendance::where('user_id', $request->user()->id)
                ->whereDate('date', $todayDate)
                ->lockForUpdate()
                ->first();

            if ($attendance === null) {
                return redirect()->route('attendance.action')
                    ->withErrors(['attendance' => '本日は未出勤です。']);
            }

            if ($attendance->status === Attendance::STATUS_CLOCKED_OUT) {
                return redirect()->route('attendance.action')
                    ->withErrors(['attendance' => '本日は既に退勤済みです。']);
            }

            // 休憩中に退勤した場合は休憩を自動クローズ
            if ($attendance->status === Attendance::STATUS_ON_BREAK) {
                $latestBreak = BreakRecord::where('attendance_id', $attendance->id)
                    ->whereNull('break_end')
                    ->latest('id')
                    ->first();

                if ($latestBreak !== null) {
                    $latestBreak->update(['break_end' => $currentTime]);
                }
            }

            $attendance->update([
                'clock_out' => $currentTime,
                'status'    => Attendance::STATUS_CLOCKED_OUT,
            ]);

            return redirect()->route('attendance.action')
                ->with('clockedOut', 'お疲れ様でした。');
        });
    }

    private function formatJapaneseDate(Carbon $date): string
    {
        $weekdayMap = [
            'Sun' => '日',
            'Mon' => '月',
            'Tue' => '火',
            'Wed' => '水',
            'Thu' => '木',
            'Fri' => '金',
            'Sat' => '土',
        ];

        $day = $weekdayMap[$date->format('D')] ?? $date->format('D');

        return $date->format('Y年n月j日') . '(' . $day . ')';
    }

    private function resolveStatusMessage(?Attendance $attendance): ?string
    {
        if ($attendance?->status === Attendance::STATUS_CLOCKED_OUT) {
            return 'お疲れ様でした。';
        }

        return null;
    }
}
