<?php

namespace Tests\Unit\Attendance;

use App\Models\Attendance;
use App\Models\BreakRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
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
        $user->email             = 'detail-test@example.com';
        $user->password          = Hash::make('password123');
        $user->email_verified_at = Carbon::now($timezone);
        $user->save();

        return $user;
    }

    /**
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     */
    public function test_detail_page_shows_logged_in_user_name(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0, $timezone));

        $user = $this->createVerifiedUser();
        $this->actingAs($user, 'web');

        $date = Carbon::today($timezone)->toDateString(); // 2025-12-25

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => $date,
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
            'status'    => Attendance::STATUS_CLOCKED_OUT,
        ]);

        $response = $this->get('/attendance/detail/' . $attendance->id);

        $response->assertSee('テストユーザー');
    }

    /**
     * 勤怠詳細画面の「日付」が選択した日付になっている
     */
    public function test_detail_page_shows_selected_date(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0, $timezone));

        $user = $this->createVerifiedUser();
        $this->actingAs($user, 'web');

        $date = Carbon::today($timezone)->toDateString(); // 2025-12-25

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => $date,
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
            'status'    => Attendance::STATUS_CLOCKED_OUT,
        ]);

        $response = $this->get('/attendance/detail/' . $attendance->id);

        $response->assertSee('2025年');
        $response->assertSee('12月25日');
    }

    /**
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_detail_page_shows_clock_in_and_clock_out_times(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0, $timezone));

        $user = $this->createVerifiedUser();
        $this->actingAs($user, 'web');

        $date = Carbon::today($timezone)->toDateString(); // 2025-12-25

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => $date,
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
            'status'    => Attendance::STATUS_CLOCKED_OUT,
        ]);

        $response = $this->get('/attendance/detail/' . $attendance->id);

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_detail_page_shows_break_times(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0, $timezone));

        $user = $this->createVerifiedUser();
        $this->actingAs($user, 'web');

        $date = Carbon::today($timezone)->toDateString(); // 2025-12-25

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => $date,
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
            'status'    => Attendance::STATUS_CLOCKED_OUT,
        ]);

        BreakRecord::create([
            'attendance_id' => $attendance->id,
            'break_start'   => '12:15:00',
            'break_end'     => '12:45:00',
        ]);

        $response = $this->get('/attendance/detail/' . $attendance->id);

        $response->assertSee('12:15');
        $response->assertSee('12:45');
    }
}
