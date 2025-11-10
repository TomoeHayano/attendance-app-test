<?php

use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminLoginController;
use App\Http\Controllers\Auth\AuthenticatedSessionController as UserLoginController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceListController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\Auth\RequestListController;

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

Route::middleware(['guest:web'])->group(function (): void {
    Route::get('/login', [UserLoginController::class, 'create'])->name('login');
    Route::post('/login', [UserLoginController::class, 'store']);
});

 // ログイン後のメイン画面を勤怠打刻画面に変更（FN006）
Route::middleware(['auth:web'])->group(function (): void {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/action', [AttendanceController::class, 'index'])->name('attendance.action');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clockIn');
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])->name('attendance.breakStart');
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])->name('attendance.breakEnd');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clockOut');
});

Route::middleware('auth')->group(function () {
    Route::get('/attendance/list', [AttendanceListController::class, 'index'])
        ->name('attendance.list');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/attendance/detail/{id}', [AttendanceDetailController::class, 'show'])
        ->name('attendance.detail.show');

    Route::post('/attendance/detail/{id}/request', [AttendanceDetailController::class, 'requestCorrection'])
        ->name('attendance.detail.request');
});

Route::middleware('auth')->group(function () {
    Route::get('/stamp_correction_request/list', [RequestListController::class, 'index'])
    ->name('stamp_correction_request.list');
});

// === メール認証（一般ユーザー） ===
// 誘導画面
// Route::get('/email/verify', function () {
//     return view('auth.verify');
// })->middleware('auth:web')->name('verification.notice');

// // 認証リンクの処理（署名付きURL）
// Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
//     $request->fulfill();
//     return redirect()->route('dashboard');
// })->middleware(['auth:web', 'signed'])->name('verification.verify');

// // 再送ボタン
// Route::post('/email/verification-notification', function (Request $request) {
//     $request->user()->sendEmailVerificationNotification();
//     return back()->with('status', 'verification-link-sent');
// })->middleware(['auth:web', 'throttle:6,1'])->name('verification.send');

// === 管理者用（ログイン表示/処理） ===
Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware(['guest:admin'])->group(function (): void {
        Route::get('/login', [AdminLoginController::class, 'create'])->name('login');
        Route::post('/login', [AdminLoginController::class, 'store']);
    });

    Route::middleware(['auth:admin'])->group(function (): void {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');
    });
});

// ログアウト（両者）
Route::post('/logout', [UserLoginController::class, 'destroy'])->name('logout');
Route::post('/admin/logout', [AdminLoginController::class, 'destroy'])->name('admin.logout');
