<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト用の認証済みユーザーを作成する
     */
    private function createVerifiedUser(string $email = 'list-test@example.com'): User
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        $user                    = new User();
        $user->name              = 'テストユーザー';
        $user->email             = $email;
        $user->password          = Hash::make('password123');
        $user->email_verified_at = Carbon::now($timezone);
        $user->save();

        return $user;
    }

    /**
     * 自分が行った勤怠情報がすべて表示されている
     *
     * 1. 勤怠情報が登録されたユーザーにログインする
     * 2. 勤怠一覧ページを開く
     * 3. 自分の勤怠情報がすべて表示されていることを確認する
     */
    public function test_all_own_attendance_records_are_displayed(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        Carbon::setTestNow(Carbon::create(2025, 12, 15, 10, 0, 0, $timezone));

        $user = $this->createVerifiedUser('user1@example.com');
        $this->actingAs($user, 'web');

        $otherUser = $this->createVerifiedUser('other@example.com');

        $monthDate = Carbon::today($timezone);

        $firstDate  = $monthDate->copy()->startOfMonth()->toDateString();      // 2025-12-01
        $secondDate = $monthDate->copy()->startOfMonth()->addDay()->toDateString(); // 2025-12-02

        Attendance::create([
            'user_id'   => $user->id,
            'date'      => $firstDate,
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
            'status'    => Attendance::STATUS_CLOCKED_OUT,
        ]);

        Attendance::create([
            'user_id'   => $user->id,
            'date'      => $secondDate,
            'clock_in'  => '10:00:00',
            'clock_out' => '19:00:00',
            'status'    => Attendance::STATUS_CLOCKED_OUT,
        ]);

        // 他ユーザーの勤怠（混入していないか確認用）
        Attendance::create([
            'user_id'   => $otherUser->id,
            'date'      => $firstDate,
            'clock_in'  => '08:00:00',
            'clock_out' => '17:00:00',
            'status'    => Attendance::STATUS_CLOCKED_OUT,
        ]);

        $response = $this->get('/attendance/list');

        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('10:00');
        $response->assertSee('19:00');

        $response->assertDontSee('08:00');
    }

    /**
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     *
     * 1. ユーザーにログインをする
     * 2. 勤怠一覧ページを開く
     * → 現在の月（YYYY/MM）が表示される
     */
    public function test_current_month_is_displayed_on_attendance_list(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 0, 0, $timezone));

        $user = $this->createVerifiedUser();
        $this->actingAs($user, 'web');

        $response = $this->get('/attendance/list');

        $response->assertSee('2025/12');
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     *
     * ここでは /attendance/list?year=2025&month=11 を直接叩いて検証する
     */
    public function test_prev_month_attendance_is_displayed(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        Carbon::setTestNow(Carbon::create(2025, 12, 15, 10, 0, 0, $timezone));

        $user = $this->createVerifiedUser();
        $this->actingAs($user, 'web');

        $prevMonthDate = Carbon::create(2025, 11, 20, 0, 0, 0, $timezone)->toDateString();

        Attendance::create([
            'user_id'   => $user->id,
            'date'      => $prevMonthDate,
            'clock_in'  => '09:30:00',
            'clock_out' => '18:30:00',
            'status'    => Attendance::STATUS_CLOCKED_OUT,
        ]);

        // 「前月」ボタン相当：year, month パラメータ付きでアクセス
        $response = $this->get('/attendance/list?year=2025&month=11');

        // 年月ラベルが前月（2025/11）になっていること
        $response->assertSee('2025/11');

        // 前月の勤怠情報（09:30, 18:30）が表示されていること
        $response->assertSee('09:30');
        $response->assertSee('18:30');
    }

    /**
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     *
     * /attendance/list?year=2026&month=1 を直接叩いて検証
     */
    public function test_next_month_attendance_is_displayed(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        // 基準を 2025-12 に
        Carbon::setTestNow(Carbon::create(2025, 12, 15, 10, 0, 0, $timezone));

        $user = $this->createVerifiedUser();
        $this->actingAs($user, 'web');

        // 翌月（2026-01）の勤怠を1件作成
        $nextMonthDate = Carbon::create(2026, 1, 5, 0, 0, 0, $timezone)->toDateString();

        Attendance::create([
            'user_id'   => $user->id,
            'date'      => $nextMonthDate,
            'clock_in'  => '08:45:00',
            'clock_out' => '17:45:00',
            'status'    => Attendance::STATUS_CLOCKED_OUT,
        ]);

        // 「翌月」ボタン相当：year, month パラメータ付きでアクセス
        $response = $this->get('/attendance/list?year=2026&month=1');

        // 年月ラベルが翌月（2026/01）になっていること
        $response->assertSee('2026/01');

        // 翌月の勤怠情報（08:45, 17:45）が表示されていること
        $response->assertSee('08:45');
        $response->assertSee('17:45');
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     *
     * ここでは一覧に表示されるリンクと同じ形式の URL で 200 が返ることを確認する
     */
    public function test_detail_link_navigates_to_attendance_detail_page(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 0, 0, $timezone));

        $user = $this->createVerifiedUser();
        $this->actingAs($user, 'web');

        $date = Carbon::today($timezone)->toDateString(); // 2025-12-25

        // この日の勤怠を1件作成（id を detail リンクで利用する前提）
        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'date'      => $date,
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
            'status'    => Attendance::STATUS_CLOCKED_OUT,
        ]);

        // 一覧画面を開く
        $response = $this->get('/attendance/list');

        // 一覧内の「詳細」リンクが id ベースの場合:
        $expectedDetailPath = '/attendance/detail/' . $attendance->id;

        // HTML 内にリンクパスが含まれていることを確認
        $response->assertSee($expectedDetailPath);

        // 実際にその URL にアクセスして 200 が返ることを確認
        $detailResponse = $this->get($expectedDetailPath);
        $detailResponse->assertStatus(200);
    }
}
