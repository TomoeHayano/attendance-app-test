<?php

namespace Tests\Unit\Attendance;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
  use RefreshDatabase;

  /**
   * 管理者ユーザー作成
   */
  private function createAdmin(): Admin
  {
    $admin           = new Admin();
    $admin->name     = '管理者ユーザー';
    $admin->email    = 'admin@example.com';
    $admin->password = 'password';
    $admin->save();

    return $admin;
  }

  /**
   * 一般ユーザー作成
   */
  private function createUser(string $name, string $email): User
  {
    $user                    = new User();
    $user->name              = $name;
    $user->email             = $email;
    $user->password          = 'password';
    $user->email_verified_at = Carbon::now(config('app.timezone') ?: 'Asia/Tokyo');
    $user->save();

    return $user;
  }

  /**
   * 勤怠レコード作成
   */
  private function createAttendance(User $user, string $date, string $clockIn, string $clockOut): Attendance
  {
    return Attendance::create([
      'user_id'   => $user->id,
      'date'      => $date,
      'clock_in'  => $clockIn,
      'clock_out' => $clockOut,
      'status'    => Attendance::STATUS_CLOCKED_OUT,
    ]);
  }

  /**
   * その日になされた全ユーザーの勤怠情報が正確に確認できる
   */
  public function test_admin_can_see_all_users_attendance_for_today(): void
  {
    $timezone = config('app.timezone') ?: 'Asia/Tokyo';

    Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0, $timezone));

    $admin = $this->createAdmin();
    $this->actingAs($admin, 'admin');

    $userA = $this->createUser('ユーザーA', 'user-a@example.com');
    $userB = $this->createUser('ユーザーB', 'user-b@example.com');

    $this->createAttendance($userA, '2025-12-25', '09:00:00', '18:00:00');
    $this->createAttendance($userB, '2025-12-25', '10:00:00', '19:00:00');

    $userC = $this->createUser('ユーザーC', 'user-c@example.com');
    $this->createAttendance($userC, '2025-12-24', '07:00:00', '16:00:00');

    $response = $this->get('/admin/attendance/list');

    $response->assertOk();

    $response->assertSee('ユーザーA');
    $response->assertSee('09:00');
    $response->assertSee('18:00');

    $response->assertSee('ユーザーB');
    $response->assertSee('10:00');
    $response->assertSee('19:00');

    $response->assertDontSee('07:00');
  }

  /**
   * 遷移した際に現在の日付が表示される
   */
  public function test_admin_daily_page_shows_today_date(): void
  {
    $timezone = config('app.timezone') ?: 'Asia/Tokyo';

    Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0, $timezone));

    $admin = $this->createAdmin();
    $this->actingAs($admin, 'admin');

    $response = $this->get('/admin/attendance/list');

    $response->assertOk();

    // Blade の h1: 「2025年12月25日の勤怠」
    $response->assertSee('2025年12月25日の勤怠');
  }

  /**
   * 「前日」を押下した時に前の日の勤怠情報が表示される
   */
  public function test_prev_day_button_shows_previous_date_attendance(): void
  {
    $timezone = config('app.timezone') ?: 'Asia/Tokyo';

    Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0, $timezone));

    $admin = $this->createAdmin();
    $this->actingAs($admin, 'admin');

    $user = $this->createUser('前日ユーザー', 'prev@example.com');

    $this->createAttendance($user, '2025-12-24', '08:00:00', '17:00:00');

    $response = $this->get('/admin/attendance/list?date=2025-12-24');

    $response->assertOk();

    // Blade の h1: 「2025年12月24日の勤怠」
    $response->assertSee('2025年12月24日の勤怠');

    $response->assertSee('前日ユーザー');
    $response->assertSee('08:00');
    $response->assertSee('17:00');
  }

  /**
   * 「翌日」を押下した時に次の日の勤怠情報が表示される
   */
  public function test_next_day_button_shows_next_date_attendance(): void
  {
    $timezone = config('app.timezone') ?: 'Asia/Tokyo';

    Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0, $timezone));

    $admin = $this->createAdmin();
    $this->actingAs($admin, 'admin');

    $user = $this->createUser('翌日ユーザー', 'next@example.com');

    $this->createAttendance($user, '2025-12-26', '11:00:00', '20:00:00');

    $response = $this->get('/admin/attendance/list?date=2025-12-26');

    $response->assertOk();

    // Blade の h1: 「2025年12月26日の勤怠」
    $response->assertSee('2025年12月26日の勤怠');

    $response->assertSee('翌日ユーザー');
    $response->assertSee('11:00');
    $response->assertSee('20:00');
  }
}
