<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト用の認証済みユーザーを作成する
     */
    private function createVerifiedUser(): User
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        $user                    = new User();
        $user->name              = 'テストユーザー';
        $user->email             = 'status-test@example.com';
        $user->password          = Hash::make('password123');
        $user->email_verified_at = Carbon::now($timezone);
        $user->save();

        return $user;
    }

    /**
     * 勤務外の場合、勤怠ステータスが「勤務外」と表示される
     */
    public function test_status_is_off_duty_when_no_attendance_record(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        $user = $this->createVerifiedUser();
        $this->actingAs($user, 'web');

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, $timezone));

        // Attendance レコードなし → 勤務外
        $response = $this->get('/attendance');

        $response->assertSee('勤務外');
    }

    /**
     * 出勤中の場合、勤怠ステータスが「出勤中」と表示される
     */
    public function test_status_is_working_when_attendance_status_working(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        $user = $this->createVerifiedUser();
        $this->actingAs($user, 'web');

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, $timezone));
        $todayDate = Carbon::today($timezone)->toDateString();

        Attendance::create([
            'user_id'  => $user->id,
            'date'     => $todayDate,
            'clock_in' => '09:00:00',
            'status'   => Attendance::STATUS_WORKING,
        ]);

        $response = $this->get('/attendance');

        $response->assertSee('出勤中');
    }

    /**
     * 休憩中の場合、勤怠ステータスが「休憩中」と表示される
     */
    public function test_status_is_on_break_when_attendance_status_on_break(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        $user = $this->createVerifiedUser();
        $this->actingAs($user, 'web');

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 12, 0, $timezone));
        $todayDate = Carbon::today($timezone)->toDateString();

        Attendance::create([
            'user_id'  => $user->id,
            'date'     => $todayDate,
            'clock_in' => '09:00:00',
            'status'   => Attendance::STATUS_ON_BREAK,
        ]);

        $response = $this->get('/attendance');

        $response->assertSee('休憩中');
    }

    /**
     * 退勤済の場合、勤怠ステータスが「退勤済」と表示される
     */
    public function test_status_is_clocked_out_when_attendance_status_clocked_out(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        $user = $this->createVerifiedUser();
        $this->actingAs($user, 'web');

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 18, 0, $timezone));
        $todayDate = Carbon::today($timezone)->toDateString();

        Attendance::create([
            'user_id'   => $user->id,
            'date'      => $todayDate,
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
            'status'    => Attendance::STATUS_CLOCKED_OUT,
        ]);

        $response = $this->get('/attendance');

        $response->assertSee('退勤済');
    }
}
