<?php

namespace Tests\Unit\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceClockOutTest extends TestCase
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
    $user->email             = 'clockout-test@example.com';
    $user->password          = Hash::make('password123');
    $user->email_verified_at = Carbon::now($timezone);
    $user->save();

    return $user;
  }

  /**
   * 退勤ボタンが正しく機能する
   *
   * 1. ステータスが勤務中のユーザーにログインする
   * 2. 画面に「退勤」ボタンが表示されていることを確認する
   * 3. 退勤の処理後、ステータスが「退勤済」になること
   */
  public function test_clock_out_button_works_and_status_becomes_clocked_out(): void
  {
    $timezone = config('app.timezone') ?: 'Asia/Tokyo';

    $user = $this->createVerifiedUser();
    $this->actingAs($user, 'web');

    Carbon::setTestNow(Carbon::create(2025, 12, 25, 18, 0, $timezone));
    $todayDate = Carbon::today($timezone)->toDateString();

    Attendance::create([
      'user_id'  => $user->id,
      'date'     => $todayDate,
      'clock_in' => '09:00:00',
      'status'   => Attendance::STATUS_WORKING,
    ]);

    $response = $this->get('/attendance');

    $response->assertSee('退勤');

    $response = $this->followingRedirects()->post('/attendance/clock-out');

    $response->assertSee('退勤済');
  }

  /**
   * 退勤時刻が勤怠一覧画面で確認できる
   *
   * 1. ステータスが勤務外のユーザーにログインする（= 当日レコードなし）
   * 2. 出勤と退勤の処理を行う
   * 3. 勤怠一覧画面から退勤時刻を確認する
   *
   * → 勤怠一覧画面に退勤時刻が正確に記録されている
   */
  public function test_clock_out_time_is_recorded_and_visible_on_attendance_list(): void
  {
    $timezone = config('app.timezone') ?: 'Asia/Tokyo';

    $user = $this->createVerifiedUser();
    $this->actingAs($user, 'web');

    Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0, $timezone));
    $todayDate = Carbon::today($timezone)->toDateString();

    $this->post('/attendance/clock-in');

    Carbon::setTestNow(Carbon::create(2025, 12, 25, 18, 30, 0, $timezone));
    $expectedClockOutDb   = Carbon::now($timezone)->format('H:i:s');
    $expectedClockOutView = Carbon::now($timezone)->format('H:i');

    $this->post('/attendance/clock-out');

    $this->assertDatabaseHas('attendances', [
      'user_id'   => $user->id,
      'date'      => $todayDate,
      'clock_out' => $expectedClockOutDb,
    ]);

    $response = $this->get('/attendance/list');

    $response->assertSee($expectedClockOutView);
  }
}
