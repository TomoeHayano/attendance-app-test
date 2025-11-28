<?php

namespace Tests\Unit\Attendance;

use App\Http\Requests\AttendanceDetailUpdateRequest;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者ユーザー作成
     */
    private function createAdmin(): Admin
    {
        $admin           = new Admin();
        $admin->name     = '管理者ユーザー';
        $admin->email    = 'admin-detail@example.com';
        $admin->password = 'password';
        $admin->save();

        return $admin;
    }

    /**
     * 一般ユーザー作成
     */
    private function createUser(): User
    {
        $user                    = new User();
        $user->name              = 'テストユーザー';
        $user->email             = 'user-detail@example.com';
        $user->password          = 'password';
        $user->email_verified_at = Carbon::now(config('app.timezone') ?: 'Asia/Tokyo');
        $user->save();

        return $user;
    }

    /**
     * 勤怠レコード作成
     */
    private function createAttendance(User $user): Attendance
    {
        return Attendance::create([
            'user_id'   => $user->id,
            'date'      => '2025-12-25',
            'clock_in'  => '09:00:00',
            'clock_out' => '18:00:00',
            'status'    => Attendance::STATUS_CLOCKED_OUT,
        ]);
    }

    /**
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     *
     * - 管理者でログイン
     * - /admin/attendance/{id} を開く
     * - 名前／出勤／退勤が一致していることを確認
     */
    public function test_admin_detail_page_shows_selected_attendance(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';
        Carbon::setTestNow(Carbon::create(2025, 12, 25, 9, 0, 0, $timezone));

        $admin      = $this->createAdmin();
        $user       = $this->createUser();
        $attendance = $this->createAttendance($user);

        $this->actingAs($admin, 'admin');

        $response = $this->get('/admin/attendance/' . $attendance->id);

        $response->assertOk();

        $response->assertSee('テストユーザー');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /**
     * Request バリデーション用ヘルパ
     *
     * AttendanceDetailUpdateRequest に対して Validator を実行し、
     * withValidator() の追加チェックも反映させる。
     */
    private function makeAttendanceDetailValidator(array $data)
    {
        $request = new AttendanceDetailUpdateRequest();

        // フォーム入力をセット（input() で参照される）
        $request->replace($data);

        $validator = Validator::make(
            $request->all(),
            $request->rules(),
            $request->messages()
        );

        // カスタムバリデーション（withValidator）を適用
        $request->withValidator($validator);

        return $validator;
    }

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     *
     * 「出勤時間もしくは退勤時間が不適切な値です」
     */
    public function test_error_when_clock_in_is_after_clock_out_for_admin(): void
    {
        $data = [
            'clock_in'     => '18:00',
            'clock_out'    => '09:00',
            'breakRecords' => [],
            'remarks'      => 'テスト備考',
        ];

        $validator = $this->makeAttendanceDetailValidator($data);

        $this->assertTrue($validator->fails());

        $this->assertContains(
            '出勤時間もしくは退勤時間が不適切な値です',
            $validator->errors()->get('clock_in')
        );
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     *
     * 「休憩時間が不適切な値です」
     */
    public function test_error_when_break_start_is_after_clock_out_for_admin(): void
    {
        $data = [
            'clock_in'     => '09:00',
            'clock_out'    => '18:00',
            'breakRecords' => [
                [
                    'start'    => '19:00',   // 退勤より後
                    'end'      => '19:30',
                    'required' => true,
                ],
            ],
            'remarks' => 'テスト備考',
        ];

        $validator = $this->makeAttendanceDetailValidator($data);

        $this->assertTrue($validator->fails());

        $this->assertContains(
            '休憩時間が不適切な値です',
            $validator->errors()->get('breakRecords.0.start')
        );
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     *
     * 「休憩時間もしくは退勤時間が不適切な値です」
     */
    public function test_error_when_break_end_is_after_clock_out_for_admin(): void
    {
        $data = [
            'clock_in'     => '09:00',
            'clock_out'    => '18:00',
            'breakRecords' => [
                [
                    'start'    => '10:00',
                    'end'      => '19:00', // 退勤より後
                    'required' => true,
                ],
            ],
            'remarks' => 'テスト備考',
        ];

        $validator = $this->makeAttendanceDetailValidator($data);

        $this->assertTrue($validator->fails());

        $this->assertContains(
            '休憩時間もしくは退勤時間が不適切な値です',
            $validator->errors()->get('breakRecords.0.end')
        );
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示される
     *
     * 「備考を記入してください」
     */
    public function test_error_when_remarks_is_empty_for_admin(): void
    {
        $data = [
            'clock_in'     => '09:00',
            'clock_out'    => '18:00',
            'breakRecords' => [],
            'remarks'      => '',
        ];

        $validator = $this->makeAttendanceDetailValidator($data);

        $this->assertTrue($validator->fails());

        $this->assertContains(
            '備考を記入してください',
            $validator->errors()->get('remarks')
        );
    }
}
