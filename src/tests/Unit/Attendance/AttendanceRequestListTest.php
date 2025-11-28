<?php

namespace Tests\Unit\Attendance;

use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceRequestListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 検証用のメール認証済みユーザーを作成
     */
    private function createVerifiedUser(string $email = 'user@example.com', string $name = 'テストユーザー'): User
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
     * 検証用の勤怠レコードを作成
     */
    private function createAttendance(User $user, string $date = '2025-12-25', array $override = []): Attendance
    {
        $base = [
            'user_id'   => $user->id,
            'date'      => $date,
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
            'status'    => Attendance::STATUS_CLOCKED_OUT,
        ];

        return Attendance::create(array_merge($base, $override));
    }

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     * 「出勤時間もしくは退勤時間が不適切な値です」
     */
    public function test_error_when_clock_in_is_after_clock_out(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0, $timezone));

        $user = $this->createVerifiedUser('clockin-after@example.com');
        $this->actingAs($user, 'web');

        $attendance = $this->createAttendance($user, '2025-12-25');

        // 出勤時間 > 退勤時間 になるように送信
        $response = $this->post("/attendance/detail/{$attendance->id}/request", [
            'clock_in'     => '19:00',  // 退勤（18:00）より後
            'clock_out'    => '18:00',
            'breakRecords' => [],
            'remarks'      => 'テスト備考',
        ]);

        $response->assertRedirect();

        // 本番リクエスト(AttendanceDetailUpdateRequest)に合わせたメッセージ
        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     * 「休憩時間が不適切な値です」
     */
    public function test_error_when_break_start_is_after_clock_out(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0, $timezone));

        $user = $this->createVerifiedUser('break-start-after@example.com');
        $this->actingAs($user, 'web');

        $attendance = $this->createAttendance($user, '2025-12-25');

        $response = $this->post("/attendance/detail/{$attendance->id}/request", [
            'clock_in'     => '09:00',
            'clock_out'    => '18:00',
            'breakRecords' => [
                [
                    'start' => '19:00', // 退勤後
                    'end'   => '19:30',
                ],
            ],
            'remarks' => 'テスト備考',
        ]);

        $response->assertRedirect();

        $response->assertSessionHasErrors([
            'breakRecords.0.start' => '休憩時間が不適切な値です',
        ]);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     * 「休憩時間もしくは退勤時間が不適切な値です」
     */
    public function test_error_when_break_end_is_after_clock_out(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0, $timezone));

        $user = $this->createVerifiedUser('break-end-after@example.com');
        $this->actingAs($user, 'web');

        $attendance = $this->createAttendance($user, '2025-12-25');

        $response = $this->post("/attendance/detail/{$attendance->id}/request", [
            'clock_in'     => '09:00',
            'clock_out'    => '18:00',
            'breakRecords' => [
                [
                    'start' => '10:00',
                    'end'   => '19:00', // 退勤後
                ],
            ],
            'remarks' => 'テスト備考',
        ]);

        $response->assertRedirect();

        $response->assertSessionHasErrors([
            'breakRecords.0.end' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示される
     * 「備考を記入してください」
     */
    public function test_error_when_remarks_is_empty(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0, $timezone));

        $user = $this->createVerifiedUser('remarks-empty@example.com');
        $this->actingAs($user, 'web');

        $attendance = $this->createAttendance($user, '2025-12-25');

        $response = $this->post("/attendance/detail/{$attendance->id}/request", [
            'clock_in'     => '09:00',
            'clock_out'    => '18:00',
            'breakRecords' => [],
            'remarks'      => '',   // 未入力
        ]);

        $response->assertRedirect();

        $response->assertSessionHasErrors([
            'remarks' => '備考を記入してください',
        ]);
    }

    /**
     * 修正申請処理が実行される（CorrectionRequest が作成される）
     */
    public function test_correction_request_is_created_successfully(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0, $timezone));

        $user = $this->createVerifiedUser('create-request@example.com');
        $this->actingAs($user, 'web');

        $attendance = $this->createAttendance($user, '2025-12-25');

        $response = $this->post("/attendance/detail/{$attendance->id}/request", [
            'clock_in'     => '09:30',
            'clock_out'    => '18:30',
            'breakRecords' => [
                [
                    'start'    => '12:00',
                    'end'      => '12:30',
                    'required' => true,
                ],
            ],
            'remarks' => '修正申請テスト',
        ]);

        $response->assertRedirect();

        // 修正申請レコードが作成されていること（細かい時間までは見ず、必須項目を確認）
        $this->assertDatabaseHas('correction_requests', [
            'user_id'       => $user->id,
            'attendance_id' => $attendance->id,
            'remarks'       => '修正申請テスト',
            'status'        => CorrectionRequest::STATUS_PENDING,
        ]);
    }

    /**
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     */
    public function test_pending_correction_requests_are_listed_for_user(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 0, 0, $timezone));

        $user      = $this->createVerifiedUser('pending-user@example.com', '申請ユーザー');
        $otherUser = $this->createVerifiedUser('other-user@example.com', '別ユーザー');

        $this->actingAs($user, 'web');

        $attendance1     = $this->createAttendance($user, '2025-12-25');
        $attendance2     = $this->createAttendance($user, '2025-12-26');
        $otherAttendance = $this->createAttendance($otherUser, '2025-12-25');

        // このユーザーの「承認待ち」申請 2 件
        CorrectionRequest::create([
            'attendance_id'       => $attendance1->id,
            'user_id'             => $user->id,
            'corrected_clock_in'  => '09:30',
            'corrected_clock_out' => '18:30',
            'remarks'             => 'ユーザー申請1',
            'status'              => CorrectionRequest::STATUS_PENDING,
        ]);

        CorrectionRequest::create([
            'attendance_id'       => $attendance2->id,
            'user_id'             => $user->id,
            'corrected_clock_in'  => '10:00',
            'corrected_clock_out' => '19:00',
            'remarks'             => 'ユーザー申請2',
            'status'              => CorrectionRequest::STATUS_PENDING,
        ]);

        // 別ユーザーの承認待ち（一覧に出てほしくない）
        CorrectionRequest::create([
            'attendance_id'       => $otherAttendance->id,
            'user_id'             => $otherUser->id,
            'corrected_clock_in'  => '08:00',
            'corrected_clock_out' => '17:00',
            'remarks'             => '別ユーザー申請',
            'status'              => CorrectionRequest::STATUS_PENDING,
        ]);

        // デフォルトタブか、または明示的に tab=pending を指定（実装に合わせて）
        $response = $this->get('/stamp_correction_request/list?tab=pending');

        $response->assertOk();

        // 自分の申請が表示されていること
        $response->assertSee('ユーザー申請1');
        $response->assertSee('ユーザー申請2');

        // 他人の申請は表示されないことを一応確認（UI仕様次第）
        $response->assertDontSee('別ユーザー申請');
    }

    /**
     * 「承認済み」に管理者が承認した修正申請が全て表示されていること
     */
    public function test_approved_correction_requests_are_listed(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 0, 0, $timezone));

        $user      = $this->createVerifiedUser('approved-user@example.com', '承認ユーザー');
        $otherUser = $this->createVerifiedUser('approved-other@example.com', '別ユーザー');

        $this->actingAs($user, 'web');

        $attendance1     = $this->createAttendance($user, '2025-12-25');
        $attendance2     = $this->createAttendance($user, '2025-12-26');
        $otherAttendance = $this->createAttendance($otherUser, '2025-12-27');

        // このユーザーの承認済み申請 2 件
        CorrectionRequest::create([
            'attendance_id'       => $attendance1->id,
            'user_id'             => $user->id,
            'corrected_clock_in'  => '09:30',
            'corrected_clock_out' => '18:30',
            'remarks'             => '承認済み申請1',
            'status'              => CorrectionRequest::STATUS_APPROVED,
        ]);

        CorrectionRequest::create([
            'attendance_id'       => $attendance2->id,
            'user_id'             => $user->id,
            'corrected_clock_in'  => '10:00',
            'corrected_clock_out' => '19:00',
            'remarks'             => '承認済み申請2',
            'status'              => CorrectionRequest::STATUS_APPROVED,
        ]);

        // このユーザーの承認待ち（承認済みタブには出ない想定）
        CorrectionRequest::create([
            'attendance_id'       => $attendance2->id,
            'user_id'             => $user->id,
            'corrected_clock_in'  => '08:00',
            'corrected_clock_out' => '17:00',
            'remarks'             => '承認待ち申請',
            'status'              => CorrectionRequest::STATUS_PENDING,
        ]);

        // 別ユーザーの承認済み（基本は表示されない想定）
        CorrectionRequest::create([
            'attendance_id'       => $otherAttendance->id,
            'user_id'             => $otherUser->id,
            'corrected_clock_in'  => '09:00',
            'corrected_clock_out' => '18:00',
            'remarks'             => '別ユーザー承認済み',
            'status'              => CorrectionRequest::STATUS_APPROVED,
        ]);

        // 承認済みタブを表示
        $response = $this->get('/stamp_correction_request/list?tab=approved');

        $response->assertOk();

        $response->assertSee('承認済み申請1');
        $response->assertSee('承認済み申請2');

        // 承認待ちや他ユーザーのものは出ない想定で確認
        $response->assertDontSee('承認待ち申請');
        $response->assertDontSee('別ユーザー承認済み');
    }

    /**
     * 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
     */
    public function test_each_request_detail_link_navigates_to_attendance_detail(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 0, 0, $timezone));

        $user = $this->createVerifiedUser('detail-link-user@example.com', 'リンクユーザー');
        $this->actingAs($user, 'web');

        $attendance = $this->createAttendance($user, '2025-12-25');

        $request = CorrectionRequest::create([
            'attendance_id'       => $attendance->id,
            'user_id'             => $user->id,
            'corrected_clock_in'  => '09:30',
            'corrected_clock_out' => '18:30',
            'remarks'             => 'リンクテスト申請',
            'status'              => CorrectionRequest::STATUS_PENDING,
        ]);

        // 申請一覧（承認待ちタブ）を表示
        $response = $this->get('/stamp_correction_request/list?tab=pending');

        $response->assertOk();

        $detailUrl = '/attendance/detail/' . $attendance->id;

        // 一覧に詳細リンクのURLが含まれていること
        $response->assertSee($detailUrl);

        // 実際に詳細画面へアクセスして 200 が返ること
        $detailResponse = $this->get($detailUrl);
        $detailResponse->assertOk();
        $detailResponse->assertSee('勤怠詳細');
        $detailResponse->assertSee('リンクユーザー');
    }
}
