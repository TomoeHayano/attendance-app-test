<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 現在の日時が勤怠打刻画面に正しく表示されることを確認する
     */
    public function test_current_datetime_is_displayed_with_japanese_date_and_time(): void
    {
        $timezone = config('app.timezone') ?: 'Asia/Tokyo';

        $user                    = new User();
        $user->name              = 'テストユーザー';
        $user->email             = 'test@example.com';
        $user->password          = Hash::make('password123');
        $user->email_verified_at = Carbon::now($timezone);
        $user->save();

        $this->actingAs($user, 'web');

        Carbon::setTestNow(Carbon::create(2025, 12, 25, 10, 30, $timezone));

        $response = $this->get('/attendance');

        $now = Carbon::now($timezone);

        $weekdayMap = [
            'Sun' => '日',
            'Mon' => '月',
            'Tue' => '火',
            'Wed' => '水',
            'Thu' => '木',
            'Fri' => '金',
            'Sat' => '土',
        ];
        $day = $weekdayMap[$now->format('D')] ?? $now->format('D');

        $expectedDate = $now->format('Y年n月j日') . '(' . $day . ')';
        $expectedTime = $now->format('H:i');

        $response->assertSee($expectedDate);

        $response->assertSee($expectedTime);
    }
}
