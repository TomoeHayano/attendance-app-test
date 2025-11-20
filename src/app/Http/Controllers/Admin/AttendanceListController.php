<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceListController extends Controller
{
    private const DATE_COLUMN = 'date';

    /**
     * スタッフ別 月次勤怠一覧表示（管理者）.
     *
     * @param Request $request
     * @param int     $id       対象ユーザーID
     * @return View
     */
    public function monthlyByUser(Request $request, int $id): View
    {
        $user = User::findOrFail($id);

        $now = Carbon::now();

        // クエリから対象年月を取得（なければ現在年月）
        $year  = $this->resolveIntQuery($request, 'year', $now->year);
        $month = $this->resolveIntQuery($request, 'month', $now->month);

        if ($month < 1 || $month > 12) {
            $month = $now->month;
        }

        // 表示中の月（当月扱い）
        $targetDate = Carbon::createFromDate($year, $month, 1)->startOfDay();

        // 当月の勤怠データ（休憩レコードも一緒に取得）
        $attendances = Attendance::query()
            ->with('breakRecords')
            ->where('user_id', $user->id)
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

        foreach ($monthPeriod as $date) {
            $key        = $date->toDateString();
            $attendance = $attendances->get($key);

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
            ]);
        }

        // 前月・翌月
        $prevMonthDate = $targetDate->copy()->subMonthNoOverflow();
        $nextMonthDate = $targetDate->copy()->addMonthNoOverflow();

        return view('admin.user_list', [
            'user'           => $user,
            'attendanceRows' => $attendanceRows,
            'targetDate'     => $targetDate,
            'prevMonthDate'  => $prevMonthDate,
            'nextMonthDate'  => $nextMonthDate,
        ]);
    }

    /**
     * スタッフ別 月次勤怠一覧 CSV 出力（管理者）.
     *
     * @param Request $request
     * @param int     $id
     * @return StreamedResponse
     */
    public function exportMonthlyCsv(Request $request, int $id): StreamedResponse
    {
        $user = User::findOrFail($id);
        $now  = Carbon::now();

        $year  = $this->resolveIntQuery($request, 'year', $now->year);
        $month = $this->resolveIntQuery($request, 'month', $now->month);

        if ($month < 1 || $month > 12) {
            $month = $now->month;
        }

        $targetDate = Carbon::createFromDate($year, $month, 1)->startOfDay();

        // 対象月の勤怠（実績がある日のみCSVに出力）
        $attendances = Attendance::query()
            ->with('breakRecords')
            ->where('user_id', $user->id)
            ->whereYear(self::DATE_COLUMN, $targetDate->year)
            ->whereMonth(self::DATE_COLUMN, $targetDate->month)
            ->orderBy(self::DATE_COLUMN)
            ->get();

        $fileName = sprintf(
            'attendance_%s_%s_user_%d.csv',
            $targetDate->format('Y'),
            $targetDate->format('m'),
            $user->id
        );

        return response()->streamDownload(
            function () use ($attendances): void {
                $handle = fopen('php://output', 'w');

                // ヘッダー行
                fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

                foreach ($attendances as $attendance) {
                    $date = $attendance->{self::DATE_COLUMN};

                    if (! $date instanceof Carbon) {
                        $date = Carbon::parse((string) $date);
                    }

                    $clockInCarbon  = $this->normalizeToMinute($this->parseTime($attendance->clock_in));
                    $clockOutCarbon = $this->normalizeToMinute($this->parseTime($attendance->clock_out));

                    $breakMinutes   = $this->calculateBreakMinutes($attendance);
                    $workingMinutes = $this->calculateWorkingMinutes($clockInCarbon, $clockOutCarbon, $breakMinutes);

                    fputcsv($handle, [
                        $date->format('Y-m-d'),
                        $clockInCarbon ? $clockInCarbon->format('H:i') : '',
                        $clockOutCarbon ? $clockOutCarbon->format('H:i') : '',
                        $this->formatMinutes($breakMinutes),
                        $this->formatMinutes($workingMinutes),
                    ]);
                }

                fclose($handle);
            },
            $fileName
        );
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

        // 秒あり・秒なしどちらにも対応
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
     * @param int         $breakMinutes
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