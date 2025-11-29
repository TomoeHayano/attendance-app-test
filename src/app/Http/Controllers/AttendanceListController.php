<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceListController extends Controller
{
    private const DATE_COLUMN = 'date';

    /**
     * 月次勤怠一覧表示.
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        /** @var int $userId */
        $userId = Auth::id();

        $now = Carbon::now();

        $year  = $this->resolveIntQuery($request, 'year', $now->year);
        $month = $this->resolveIntQuery($request, 'month', $now->month);

        if ($month < 1 || $month > 12) {
            $month = $now->month;
        }

        $targetDate = Carbon::createFromDate($year, $month, 1)->startOfDay();

        $attendances = Attendance::query()
            ->with('breakRecords')
            ->where('user_id', $userId)
            ->whereYear(self::DATE_COLUMN, $targetDate->year)
            ->whereMonth(self::DATE_COLUMN, $targetDate->month)
            ->orderBy(self::DATE_COLUMN)
            ->get()
            ->keyBy(function (Attendance $attendance): string {
                $date = $attendance->{self::DATE_COLUMN};

                if (! $date instanceof Carbon) {
                    $date = Carbon::parse((string) $date);
                }

                return $date->toDateString();
            });

        $monthPeriod = CarbonPeriod::create(
            $targetDate->copy()->startOfMonth(),
            $targetDate->copy()->endOfMonth()
        );

        $attendanceRows = collect();
        $today          = Carbon::today();

        foreach ($monthPeriod as $date) {
            $key        = $date->toDateString();
            $attendance = $attendances->get($key);
            $hasRecord  = $attendance !== null
                && ($attendance->clock_in !== null || $attendance->clock_out !== null);

            $clockInCarbon  = $attendance ? $this->normalizeToMinute($this->parseTime($attendance->clock_in)) : null;
            $clockOutCarbon = $attendance ? $this->normalizeToMinute($this->parseTime($attendance->clock_out)) : null;

            $breakMinutes   = $attendance ? $this->calculateBreakMinutes($attendance) : 0;
            $workingMinutes = $attendance ? $this->calculateWorkingMinutes($clockInCarbon, $clockOutCarbon, $breakMinutes) : 0;

            $attendanceRows->push([
                'date'         => $date,
                'date_label'   => $this->formatJapaneseDate($date),
                'clock_in'     => $clockInCarbon ? $clockInCarbon->format('H:i') : '',
                'clock_out'    => $clockOutCarbon ? $clockOutCarbon->format('H:i') : '',
                'break_time'   => $attendance ? $this->formatMinutes($breakMinutes) : '',
                'working_time' => $attendance ? $this->formatMinutes($workingMinutes) : '',
                'detail_id'    => $attendance?->id ?? $key,
                'can_view_detail' => ! $date->gt($today) || $hasRecord,
            ]);
        }

        $prevMonthDate = $targetDate->copy()->subMonthNoOverflow();
        $nextMonthDate = $targetDate->copy()->addMonthNoOverflow();

        return view('attendance.list', [
            'attendanceRows' => $attendanceRows,
            'targetDate'     => $targetDate,
            'prevMonthDate'  => $prevMonthDate,
            'nextMonthDate'  => $nextMonthDate,
        ]);
    }

    /**
     * クエリパラメータを int として解決する（不正な値ならデフォルト）.
     */
    private function resolveIntQuery(Request $request, string $key, int $default): int
    {
        $value = $request->query($key);

        if ($value === null || $value === '') {
            return $default;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    /**
     * time（HH:MM:SS or HH:MM）文字列を Carbon にパース.
     *
     * @param mixed $time
     * @return Carbon|null
     */
    private function parseTime(mixed $time): ?Carbon
    {
        if ($time === null || $time === '') {
            return null;
        }

        if ($time instanceof Carbon) {
            return $time;
        }

        $timeString = (string) $time;

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $timeString) === 1) {
            return Carbon::createFromFormat('H:i:s', $timeString);
        }

        if (preg_match('/^\d{2}:\d{2}$/', $timeString) === 1) {
            return Carbon::createFromFormat('H:i', $timeString);
        }

        // 想定外フォーマットは parse に任せる
        return Carbon::parse($timeString);
    }

    /**
     * 対象勤怠の休憩時間（分）を合計する.
     *
     * @param Attendance $attendance
     * @return int
     */
    private function calculateBreakMinutes(Attendance $attendance): int
    {
        $total = 0;

        foreach ($attendance->breakRecords as $breakRecord) {
            if ($breakRecord->break_start === null || $breakRecord->break_end === null) {
                continue;
            }

            $start = $this->normalizeToMinute($this->parseTime($breakRecord->break_start));
            $end   = $this->normalizeToMinute($this->parseTime($breakRecord->break_end));

            if ($start === null || $end === null) {
                continue;
            }

            $minutes = $start->diffInMinutes($end, false);

            if ($minutes > 0) {
                $total += $minutes;
            }
        }

        return $total;
    }

    /**
     * 勤務時間（分）を計算する.
     *
     * @param Carbon|null $clockIn
     * @param Carbon|null $clockOut
     * @param int $breakMinutes
     * @return int
     */
    private function calculateWorkingMinutes(?Carbon $clockIn, ?Carbon $clockOut, int $breakMinutes): int
    {
        if ($clockIn === null || $clockOut === null) {
            return 0;
        }

        $total = $clockIn->diffInMinutes($clockOut, false) - $breakMinutes;

        if ($total < 0) {
            return 0;
        }

        return $total;
    }

    /**
     * 分を "H:MM" 形式の文字列に整形する（0 のときは空文字）.
     *
     * @param int $minutes
     * @return string
     */
    private function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '';
        }

        $hours = intdiv($minutes, 60);
        $mins  = $minutes % 60;

        return sprintf('%d:%02d', $hours, $mins);
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

        $weekday = $weekdayMap[$date->format('D')] ?? $date->format('D');

        return $date->format('m/d') . '(' . $weekday . ')';
    }

    private function normalizeToMinute(?Carbon $time): ?Carbon
    {
        if ($time === null) {
            return null;
        }

        return $time->copy()->setSeconds(0);
    }
}
