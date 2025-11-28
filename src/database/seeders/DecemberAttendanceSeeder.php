<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\BreakRecord;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DecemberAttendanceSeeder extends Seeder
{
    /**
     * 12月の平日(月〜金)だけ
     * 09:00〜18:00（休憩 12:00〜13:00）の勤怠を登録する
     */
    public function run(): void
    {

        $userId = 1;
        $year   = 2025;
        $month  = 12;

        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate   = (clone $startDate)->endOfMonth();

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            // 土日(skip)
            if ($date->isWeekend()) {
                continue;
            }

            /** @var \App\Models\Attendance $attendance */
            $attendance = Attendance::updateOrCreate(
                [
                    'user_id' => $userId,
                    'date'    => $date->toDateString(),
                ],
                [
                    'clock_in'  => '09:00:00',
                    'clock_out' => '18:00:00',
                    'status'    => Attendance::STATUS_CLOCKED_OUT,
                ]
            );

            // 休憩登録
            $attendance->breakRecords()->delete();

            BreakRecord::create([
                'attendance_id' => $attendance->id,
                'break_start'   => '12:00:00',
                'break_end'     => '13:00:00',
            ]);
        }
    }
}
