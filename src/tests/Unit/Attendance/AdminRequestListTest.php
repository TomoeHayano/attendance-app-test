<?php

namespace Tests\Unit\Attendance;

use App\Models\Admin;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminRequestListTest extends TestCase
{
  use RefreshDatabase;

  private function createAdmin(): Admin
  {
    return Admin::create([
      'name'     => '管理者',
      'email'    => 'admin@example.com',
      'password' => Hash::make('password123'),
    ]);
  }

  private function createUser($name, $email): User
  {
    return User::create([
      'name'     => $name,
      'email'    => $email,
      'password' => Hash::make('password123'),
    ]);
  }

  /** @test 承認待ちの修正申請が全て表示される */
  public function pending_requests_are_listed_for_admin(): void
  {
    $admin = $this->createAdmin();
    $this->actingAs($admin, 'admin');

    $user1 = $this->createUser('ユーザー1', 'u1@example.com');
    $user2 = $this->createUser('ユーザー2', 'u2@example.com');

    $attendance1 = Attendance::create([
      'user_id'   => $user1->id,
      'date'      => '2025-12-25',
      'clock_in'  => '10:00',
      'clock_out' => '19:00',
    ]);

    $attendance2 = Attendance::create([
      'user_id'   => $user2->id,
      'date'      => '2025-12-26',
      'clock_in'  => '09:00',
      'clock_out' => '18:00',
    ]);

    CorrectionRequest::create([
      'attendance_id' => $attendance1->id,
      'user_id'       => $user1->id,
      'clock_in'      => '08:30',
      'clock_out'     => '17:30',
      'remarks'       => '修正理由1',
      'status'        => CorrectionRequest::STATUS_PENDING, // ← 承認待ち
    ]);

    CorrectionRequest::create([
      'attendance_id' => $attendance2->id,
      'user_id'       => $user2->id,
      'clock_in'      => '09:30',
      'clock_out'     => '18:30',
      'remarks'       => '修正理由2',
      'status'        => CorrectionRequest::STATUS_PENDING,
    ]);

    $response = $this->get('/admin/stamp_correction_request/list?tab=pending');

    $response->assertOk();
    $response->assertSee('修正理由1');
    $response->assertSee('修正理由2');
  }

  /** @test 承認済みの修正申請が全て表示される */
  public function approved_requests_are_listed_for_admin(): void
  {
    $admin = $this->createAdmin();
    $this->actingAs($admin, 'admin');

    $user1       = $this->createUser('ユーザー1', 'u1@example.com');
    $attendance1 = Attendance::create([
      'user_id'   => $user1->id,
      'date'      => '2025-12-20',
      'clock_in'  => '10:00',
      'clock_out' => '19:00',
    ]);

    CorrectionRequest::create([
      'attendance_id' => $attendance1->id,
      'user_id'       => $user1->id,
      'clock_in'      => '08:00',
      'clock_out'     => '17:00',
      'remarks'       => '承認済み理由1',
      'status'        => CorrectionRequest::STATUS_APPROVED,
    ]);

    $response = $this->get('/admin/stamp_correction_request/list?tab=approved');

    $response->assertOk();
    $response->assertSee('承認済み理由1');
  }

  /**
   * 修正申請の詳細内容が正しく表示されている（管理者）
   */
  public function test_correction_request_detail_is_displayed(): void
  {
    $admin = Admin::create([
      'name'     => '管理者ユーザー',
      'email'    => 'admin@example.com',
      'password' => bcrypt('password'),
    ]);

    $user = User::create([
      'name'     => 'テストユーザー',
      'email'    => 'test@example.com',
      'password' => bcrypt('password'),
    ]);

    $attendance = Attendance::create([
      'user_id'   => $user->id,
      'date'      => '2025-12-25',
      'clock_in'  => '09:00',
      'clock_out' => '18:00',
      'status'    => Attendance::STATUS_CLOCKED_OUT,
    ]);

    $request = CorrectionRequest::create([
      'attendance_id' => $attendance->id,
      'user_id'       => $user->id,
      'clock_in'      => '08:30',
      'clock_out'     => '17:30',
      'remarks'       => '詳細理由',
      'status'        => CorrectionRequest::STATUS_PENDING,
      'requested_at'  => Carbon::create(2025, 12, 25, 12, 0),
    ]);

    $this->actingAs($admin, 'admin');

    $response = $this->get('/admin/stamp_correction_request/approve/' . $request->id);

    $response->assertOk();

    $response->assertSee('テストユーザー');
    $response->assertSee('2025年 12月25日');
    $response->assertSee('詳細理由');
  }

  /**
   * 修正申請の承認処理が正しく行われる（管理者）
   */
  public function test_admin_can_approve_correction_request(): void
  {
    $admin = Admin::create([
      'name'     => '管理者ユーザー',
      'email'    => 'admin@example.com',
      'password' => bcrypt('password'),
    ]);

    $user = User::create([
      'name'     => 'テストユーザー',
      'email'    => 'test@example.com',
      'password' => bcrypt('password'),
    ]);

    $attendance = Attendance::create([
      'user_id'   => $user->id,
      'date'      => '2025-12-25',
      'clock_in'  => null,
      'clock_out' => null,
      'status'    => Attendance::STATUS_CLOCKED_OUT,
    ]);

    $request = CorrectionRequest::create([
      'attendance_id' => $attendance->id,
      'user_id'       => $user->id,
      'clock_in'      => '08:30',
      'clock_out'     => '17:30',
      'remarks'       => '承認テスト',
      'status'        => CorrectionRequest::STATUS_PENDING,
      'requested_at'  => Carbon::create(2025, 12, 25, 12, 0),
    ]);

    $this->actingAs($admin, 'admin');

    $response = $this->post('/admin/stamp_correction_request/approve/' . $request->id);

    $response->assertRedirect();

    $this->assertDatabaseHas('correction_requests', [
      'id'     => $request->id,
      'status' => CorrectionRequest::STATUS_APPROVED,
    ]);
  }
}
