<?php

namespace Tests\Unit\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceBreakTest extends TestCase
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
    $user->email             = 'break-test@example.com';
    $user->password          = Hash::make('password123');
    $user->email_verified_at = Carbon::now($timezone);
    $user->save();

    return $user;
  }

  /**
   * 出勤中のとき、休憩入ボタンが機能し、ステータスが「休憩中」になる
   */
  public function test_break_start_button_works_and_status_becomes_on_break(): void
  {
    $timezone = config('app.timezone') ?: 'Asia/Tokyo';
    $user     = $this->createVerifiedUser();
    $this->actingAs($user, 'web');

    Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 0, $timezone));
    $todayDate = Carbon::today($timezone)->toDateString();

    Attendance::create([
      'user_id'  => $user->id,
      'date'     => $todayDate,
      'clock_in' => '09:00:00',
      'status'   => Attendance::STATUS_WORKING,
    ]);

    $response = $this->get('/attendance');
    $response->assertSee('休憩入');

    $response = $this->followingRedirects()->post('/attendance/break-start');

    $response->assertSee('休憩中');
  }

  /**
   * 休憩は一日に何回でもできる
   *
   * 1回目の「休憩入→休憩戻」後も、再度「休憩入」ボタンが表示されること
   */
  public function test_break_can_be_started_multiple_times_in_a_day(): void
  {
    $timezone = config('app.timezone') ?: 'Asia/Tokyo';
    $user     = $this->createVerifiedUser();
    $this->actingAs($user, 'web');

    Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 0, $timezone));
    $todayDate = Carbon::today($timezone)->toDateString();

    Attendance::create([
      'user_id'  => $user->id,
      'date'     => $todayDate,
      'clock_in' => '09:00:00',
      'status'   => Attendance::STATUS_WORKING,
    ]);

    $this->post('/attendance/break-start');

    $this->post('/attendance/break-end');

    $response = $this->get('/attendance');
    $response->assertSee('休憩入');
  }

  /**
   * 休憩戻ボタンが正しく機能し、ステータスが「出勤中」に戻る
   */
  public function test_break_end_button_works_and_status_becomes_working(): void
  {
    $timezone = config('app.timezone') ?: 'Asia/Tokyo';
    $user     = $this->createVerifiedUser();
    $this->actingAs($user, 'web');

    Carbon::setTestNow(Carbon::create(2025, 12, 25, 12, 0, $timezone));
    $todayDate = Carbon::today($timezone)->toDateString();

    Attendance::create([
      'user_id'  => $user->id,
      'date'     => $todayDate,
      'clock_in' => '09:00:00',
      'status'   => Attendance::STATUS_WORKING,
    ]);

    $this->post('/attendance/break-start');

    $response = $this->followingRedirects()->post('/attendance/break-end');

    $response->assertSee('出勤中');
  }

  /**
   * 休憩戻は一日に何回でもできる
   *
   * 1回目の「休憩入→休憩戻」のあと、再度「休憩入→休憩戻」としたときに
   * 「休憩戻」ボタンが表示されること（= 2回目も休憩に入れている）
   */
  public function test_break_end_can_be_done_multiple_times_in_a_day(): void
  {
    $timezone = config('app.timezone') ?: 'Asia/Tokyo';
    $user     = $this->createVerifiedUser();
    $this->actingAs($user, 'web');

    Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 0, $timezone));
    $todayDate = Carbon::today($timezone)->toDateString();

    Attendance::create([
      'user_id'  => $user->id,
      'date'     => $todayDate,
      'clock_in' => '09:00:00',
      'status'   => Attendance::STATUS_WORKING,
    ]);

    $this->post('/attendance/break-start');
    $this->post('/attendance/break-end');

    $this->post('/attendance/break-start');
    $response = $this->get('/attendance');

    $response->assertSee('休憩戻');
  }

  /**
   * 休憩時刻が勤怠一覧画面で確認できる
   *
   * ここでは「開始・終了の時刻」ではなく、
   * 一覧画面に表示される「合計休憩時間（例: 0:30）」が正しく反映されていることを確認する
   */
  public function test_break_time_is_recorded_and_visible_on_attendance_list(): void
  {
    $timezone = config('app.timezone') ?: 'Asia/Tokyo';
    $user     = $this->createVerifiedUser();
    $this->actingAs($user, 'web');

    Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, $timezone));
    $todayDate = Carbon::today($timezone)->toDateString();

    $attendance = \App\Models\Attendance::create([
      'user_id'  => $user->id,
      'date'     => $todayDate,
      'clock_in' => '09:00:00',
      'status'   => Attendance::STATUS_WORKING,
    ]);

    Carbon::setTestNow(Carbon::create(2025, 12, 25, 12, 15, 0, $timezone));
    $this->post('/attendance/break-start');
    $breakStartDb = Carbon::now($timezone)->format('H:i:s');

    Carbon::setTestNow(Carbon::create(2025, 12, 25, 12, 45, 0, $timezone));
    $this->post('/attendance/break-end');
    $breakEndDb = Carbon::now($timezone)->format('H:i:s');

    $this->assertDatabaseHas('break_records', [
      'attendance_id' => $attendance->id,
      'break_start'   => $breakStartDb,
      'break_end'     => $breakEndDb,
    ]);

    $expectedBreakTotalView = '0:30';

    $response = $this->get('/attendance/list');

    $response->assertSee($expectedBreakTotalView);
  }
}
