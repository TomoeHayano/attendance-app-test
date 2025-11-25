<?php

namespace Tests\Unit\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    private function createTestUser(): User
    {
        $user = new User();
        $user->name = 'テストユーザー';
        $user->email = 'test@example.com';
        $user->password = Hash::make('password123');
        $user->save();

        return $user;
    }

    public function test_email_is_required_for_login(): void
    {
        $this->createTestUser();

        $response = $this->from('/login')->post('/login', [
            'email'    => '',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_password_is_required_for_login(): void
    {
        $this->createTestUser();

        $response = $this->from('/login')->post('/login', [
            'email'    => 'test@example.com',
            'password' => '',
        ]);

        $response->assertRedirect('/login');

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        $this->createTestUser();

        $response = $this->from('/login')->post('/login', [
            'email'    => 'wrong@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }
}