<?php

namespace Tests\Unit\Attendance;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminStaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者ユーザー作成
     */
    private function createAdmin(): Admin
    {
        $admin           = new Admin();
        $admin->name     = '管理者ユーザー';
        $admin->email    = 'admin-staff@example.com';
        $admin->password = 'password';
        $admin->save();

        return $admin;
    }

    /**
     * 一般ユーザー作成（名前・メール指定版）
     */
    private function createUser(string $name, string $email): User
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        $user                    = new User();
        $user->name              = $name;
        $user->email             = $email;
        $user->password          = 'password';
        $user->email_verified_at = Carbon::now($timezone);
        $user->save();

        return $user;
    }

    /**
     * 指定ユーザーの勤怠レコードを 1 件作成
     */
    private function createAttendance(
        User $user,
        string $date,
        string $clockIn,
        ?string $clockOut = null
    ): Attendance {
        return Attendance::create([
            'user_id'   => $user->id,
            'date'      => $date,
            'clock_in'  => $clockIn,
            'clock_out' => $clockOut,
            'status'    => $clockOut === null
                ? Attendance::STATUS_WORKING
                : Attendance::STATUS_CLOCKED_OUT,
        ]);
    }

    /**
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     *
     * 1. 管理者でログイン
     * 2. /admin/staff/list を開く
     * 3. 全ユーザーの氏名・メールアドレスが表示されていること
     */
    public function test_admin_can_see_all_users_name_and_email_on_staff_list(): void
    {
        $admin = $this->createAdmin();

        $userA = $this->createUser('ユーザーA', 'userA@example.com');
        $userB = $this->createUser('ユーザーB', 'userB@example.com');

        $this->actingAs($admin, 'admin');

        $response = $this->get('/admin/staff/list');

        $response->assertOk();

        $response->assertSee($userA->name);
        $response->assertSee($userA->email);

        $response->assertSee($userB->name);
        $response->assertSee($userB->email);
    }

    /**
     * ユーザーの勤怠情報が正しく表示される
     *
     * 1. 管理者でログイン
     * 2. 管理者の日次勤怠一覧ページを開く
     * 3. 対象ユーザーの勤怠情報が表示されていること
     *
     * ここでは「2025-12-25」の勤怠を表示する想定。
     */
    public function test_admin_can_see_selected_users_monthly_attendance(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0, $timezone));

        $admin = $this->createAdmin();
        $user  = $this->createUser('ターゲットユーザー', 'target@example.com');

        $this->createAttendance($user, '2025-12-25', '09:00:00', '18:00:00');

        $this->actingAs($admin, 'admin');

        $response = $this->get('/admin/attendance/list?date=2025-12-25');

        $response->assertOk();

        $response->assertSee('ターゲットユーザー');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertSee('2025年12月25日');
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される想定
     *
     * 実装は日付パラメータ (?date=YYYY-MM-DD) なので、
     * ここでは「2025-11-10」の勤怠を指定日で表示できることを確認する。
     */
    public function test_admin_can_see_previous_month_attendance_for_user(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';
        Carbon::setTestNow(Carbon::create(2025, 12, 15, 9, 0, 0, $timezone));

        $admin = $this->createAdmin();
        $user  = $this->createUser('前月ユーザー', 'prev@example.com');

        $this->createAttendance($user, '2025-11-10', '10:00:00', '19:00:00');

        $this->actingAs($admin, 'admin');

        $response = $this->get('/admin/attendance/list?date=2025-11-10');

        $response->assertOk();

        $response->assertSee('前月ユーザー');
        $response->assertSee('10:00');
        $response->assertSee('19:00');

        $response->assertSee('2025年11月10日');
    }

    /**
     * 「翌月」を押下した時に表示月の翌月の情報が表示される想定
     *
     * 実装は日付パラメータ (?date=YYYY-MM-DD) なので、
     * ここでは「2026-01-05」の勤怠を指定日で表示できることを確認する。
     */
    public function test_admin_can_see_next_month_attendance_for_user(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';
        Carbon::setTestNow(Carbon::create(2025, 12, 15, 9, 0, 0, $timezone));

        $admin = $this->createAdmin();
        $user  = $this->createUser('翌月ユーザー', 'next@example.com');

        $this->createAttendance($user, '2026-01-05', '08:30:00', '17:30:00');

        $this->actingAs($admin, 'admin');

        $response = $this->get('/admin/attendance/list?date=2026-01-05');

        $response->assertOk();

        $response->assertSee('翌月ユーザー');
        $response->assertSee('08:30');
        $response->assertSee('17:30');

        $response->assertSee('2026年1月5日');
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     *
     * 1. 管理者ユーザーでログイン
     * 2. ユーザーの勤怠一覧ページを開く
     * 3. 「admin/attendance/{id}」への「詳細」リンクが含まれていること
     */
    public function test_admin_monthly_list_has_link_to_attendance_detail(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0, $timezone));

        $admin = $this->createAdmin();
        $user  = $this->createUser('リンクテストユーザー', 'link@example.com');

        $attendance = $this->createAttendance($user, '2025-12-25', '09:00:00', '18:00:00');

        $this->actingAs($admin, 'admin');

        $response = $this->get('/admin/attendance/list?date=2025-12-25');

        $response->assertOk();

        $response->assertSee('admin/attendance/' . $attendance->id);
        $response->assertSee('詳細');
    }
}
