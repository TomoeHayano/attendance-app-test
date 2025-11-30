<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceClockInTest extends TestCase
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
        $user->email             = 'clockin-test@example.com';
        $user->password          = Hash::make('password123');
        $user->email_verified_at = Carbon::now($timezone);
        $user->save();

        return $user;
    }

    /**
     * 出勤ボタンが正しく機能する
     *
     * 1. ステータスが勤務外（本日レコードなし）のユーザーでログイン
     * 2. 画面に「出勤」ボタンが表示されていること
     * 3. 出勤処理後、ステータスが「出勤中」になること
     */
    public function test_clock_in_button_works_and_status_becomes_working(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        $user = $this->createVerifiedUser();
        $this->actingAs($user, 'web');

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, $timezone));

        $response = $this->get('/attendance');

        $response->assertSee('出勤');

        $response = $this->followingRedirects()->post('/attendance/clock-in');

        $response->assertSee('出勤中');
    }

    /**
     * 出勤は一日一回のみできる（退勤済ユーザーには出勤ボタンが表示されない）
     *
     * 1. ステータスが退勤済のユーザーにログインする
     * 2. 勤怠打刻画面を開く
     * 3. 画面上に「出勤」ボタンが表示されないこと
     */
    public function test_clock_in_button_is_hidden_when_status_clocked_out(): void
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

        $response->assertDontSee('出勤');
    }

    /**
     * 出勤時刻が勤怠一覧画面で確認できる
     *
     * 1. ステータスが勤務外のユーザーにログインする
     * 2. 出勤の処理を行う
     * 3. 勤怠一覧画面から出勤の日付を確認する
     *
     * → 勤怠一覧画面に出勤時刻が正確に記録されている
     */
    public function test_clock_in_time_is_recorded_and_visible_on_attendance_list(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        $user = $this->createVerifiedUser();
        $this->actingAs($user, 'web');

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 15, 30, $timezone));
        $todayDate                  = Carbon::today($timezone)->toDateString();
        $expectedClockInTimeForDb   = Carbon::now($timezone)->format('H:i:s');
        $expectedClockInTimeForView = Carbon::now($timezone)->format('H:i');
        $this->post('/attendance/clock-in');

        $this->assertDatabaseHas('attendances', [
            'user_id'  => $user->id,
            'date'     => $todayDate,
            'clock_in' => $expectedClockInTimeForDb,
        ]);

        $response = $this->get('/attendance/list');

        $response->assertSee($expectedClockInTimeForView);
    }
}
