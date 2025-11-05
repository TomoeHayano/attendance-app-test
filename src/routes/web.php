<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])
        ->name('register');        // 会員登録フォーム表示（FN004でログインへ導線）
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->name('register.post');   // 新規会員登録
});

// 例：登録直後の遷移先（FN005）
Route::middleware('auth')->get('/attendance/clock', function (): \Illuminate\View\View {
    return view('attendance.clock'); // 仮ビュー。後で本実装へ差し替え
})->name('attendance.clock');
