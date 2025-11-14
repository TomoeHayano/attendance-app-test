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
        $rawDate = $request->query('date');

        $targetDate = $rawDate !== null
            ? Carbon::parse($rawDate)
            : Carbon::today();

        /** @var Collection<int, Attendance> $attendances */
        $attendances = Attendance::with(['user', 'breaks'])
            ->whereDate('date', $targetDate->toDateString())
            ->orderBy('user_id')
            ->get()
            ->map(
                /**
                 * @param  Attendance  $attendance
                 * @return Attendance
                 */
                function (Attendance $attendance): Attendance {
                    $clockIn = $attendance->clock_in !== null
                        ? Carbon::createFromFormat('H:i:s', $attendance->clock_in)
                        : null;

                    $clockOut = $attendance->clock_out !== null
                        ? Carbon::createFromFormat('H:i:s', $attendance->clock_out)
                        : null;

                    $totalBreakMinutes = $attendance->breaks->reduce(
                        /**
                         * @param  int  $carry
                         * @param  \App\Models\BreakRecord  $break
                         * @return int
                         */
                        function (int $carry, $break): int {
                            if ($break->break_start === null || $break->break_end === null) {
                                return $carry;
                            }

                            $start = Carbon::createFromFormat('H:i:s', $break->break_start);
                            $end   = Carbon::createFromFormat('H:i:s', $break->break_end);

                            return $carry + $start->diffInMinutes($end);
                        },
                        0
                    );

                    $workMinutes = null;

                    if ($clockIn !== null && $clockOut !== null) {
                        $workMinutes = $clockIn->diffInMinutes($clockOut) - $totalBreakMinutes;
                    }

                    $attendance->total_break_minutes = $totalBreakMinutes ?: null;
                    $attendance->total_work_minutes  = $workMinutes;

                    return $attendance;
                }
            );

        return view('admin.daily', [
            'targetDate'  => $targetDate,
            'attendances' => $attendances,
        ]);
    }
}
