<?php

namespace Tests\Unit\Auth;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者テストユーザー作成
     */
    private function createTestAdmin(): Admin
    {
        $admin           = new Admin();
        $admin->name     = '管理者テスト';
        $admin->email    = 'admin@example.com';
        $admin->password = Hash::make('password123');
        $admin->save();

        return $admin;
    }

    /**
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_email_is_required_for_admin_login(): void
    {
        // 1. 管理者作成
        $this->createTestAdmin();

        // 2. メールアドレス以外を入力
        $response = $this->from('/admin/login')->post('/admin/login', [
            'email'    => '',
            'password' => 'password123',
        ]);

        // 3. エラー時は /admin/login に戻る
        $response->assertRedirect('/admin/login');

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function test_password_is_required_for_admin_login(): void
    {
        // 1. 管理者作成
        $this->createTestAdmin();

        // 2. パスワード以外を入力
        $response = $this->from('/admin/login')->post('/admin/login', [
            'email'    => 'admin@example.com',
            'password' => '',
        ]);

        // 3. エラー時は /admin/login に戻る
        $response->assertRedirect('/admin/login');

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * 誤った情報でログインした場合、認証失敗メッセージが表示される
     */
    public function test_admin_login_fails_with_wrong_credentials(): void
    {
        // 1. 正しい管理者を登録
        $this->createTestAdmin();

        // 2. 誤ったメールアドレスでログイン試行
        $response = $this->from('/admin/login')->post('/admin/login', [
            'email'    => 'wrong@example.com',
            'password' => 'password123',
        ]);

        // 3. 認証失敗 → /admin/login に戻る
        $response->assertRedirect('/admin/login');

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}
