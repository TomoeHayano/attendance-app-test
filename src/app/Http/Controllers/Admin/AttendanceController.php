<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AttendanceController extends Controller
{
    public function daily(Request $request): View
        {
            /** @var string|null $rawDate */
            $rawDate = $request->query('date') ?? $request->query('target_date');

            $targetDate = $rawDate !== null
                ? Carbon::parse($rawDate)
                : Carbon::today();

            /** @var Collection<int, Attendance> $attendances */
            $attendances = Attendance::with(['user', 'breakRecords'])
                ->whereDate('date', $targetDate->toDateString())
                ->orderBy('user_id')
                ->get()
                ->map(
                    /**
                     * @param  Attendance  $attendance
                     * @return Attendance
                     */
                    function (Attendance $attendance): Attendance {
                        $clockIn  = $this->normalizeToMinute($this->parseTime($attendance->clock_in));
                        $clockOut = $this->normalizeToMinute($this->parseTime($attendance->clock_out));

                        $totalBreakMinutes = $this->calculateBreakMinutes($attendance->breakRecords);

                        $workMinutes = null;

                        if ($clockIn !== null && $clockOut !== null) {
                            $workMinutes = $clockIn->diffInMinutes($clockOut, false) - $totalBreakMinutes;
                            if ($workMinutes < 0) {
                                $workMinutes = 0;
                            }
                        }

                        $attendance->total_break_minutes = $totalBreakMinutes;
                        $attendance->total_work_minutes  = $workMinutes;
                        $attendance->clock_in_formatted  = $clockIn?->format('H:i') ?? '';
                        $attendance->clock_out_formatted = $clockOut?->format('H:i') ?? '';
                        $attendance->break_time_formatted = $this->formatMinutes($totalBreakMinutes);
                        $attendance->work_time_formatted  = $workMinutes !== null
                            ? $this->formatMinutes($workMinutes)
                            : '';

                        return $attendance;
                    }
                )
                ->filter(
                    function (Attendance $attendance): bool {
                        return $attendance->clock_in !== null || $attendance->clock_out !== null;
                    }
                );

            return view('admin.daily', [
                'targetDate'  => $targetDate,
                'attendances' => $attendances,
            ]);
        }

        private function parseTime(?string $time): ?Carbon
        {
            if ($time === null || $time === '') {
                return null;
            }

            return Carbon::parse($time);
        }

        /**
         * @param Collection<int, \App\Models\BreakRecord> $breakRecords
         */
        private function calculateBreakMinutes(Collection $breakRecords): int
        {
            return $breakRecords->reduce(
                /**
                 * @param  int  $carry
                 * @param  \App\Models\BreakRecord  $break
                 * @return int
                 */
                function (int $carry, $break): int {
                    if ($break->break_start === null || $break->break_end === null) {
                        return $carry;
                    }

                    $start = $this->normalizeToMinute(Carbon::parse($break->break_start));
                    $end   = $this->normalizeToMinute(Carbon::parse($break->break_end));

                    $minutes = $start->diffInMinutes($end, false);

                    if ($minutes <= 0) {
                        return $carry;
                    }

                    return $carry + $minutes;
                },
                0
            );
        }

        private function formatMinutes(?int $minutes): string
        {
            if ($minutes === null || $minutes <= 0) {
                return '';
            }

            $hours = intdiv($minutes, 60);
            $mins  = $minutes % 60;

            return sprintf('%d:%02d', $hours, $mins);
        }

        private function normalizeToMinute(?Carbon $time): ?Carbon
        {
            if ($time === null) {
                return null;
            }

            return $time->copy()->setSeconds(0);
        }
}
