<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyAccountsSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => '管理者太郎',
                'password' => Hash::make('123456789'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => '一般花子',
                'password' => Hash::make('123456789'),
            ]
        );
    }
}
