<?php

use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminLoginController;
use App\Http\Controllers\Auth\AuthenticatedSessionController as UserLoginController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceListController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\RequestListController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\AttendanceDetailController as AdminAttendanceDetailController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Admin\AttendanceListController as AdminAttendanceListController;
use App\Http\Controllers\Admin\RequestListController as AdminRequestListController;
use App\Http\Controllers\Admin\RequestApproveController;


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
        ->name('register.post');
});

// 例：登録直後の遷移先（FN005）
Route::middleware(['auth', 'verified'])->get('/attendance/clock', function (): \Illuminate\View\View {
    return view('attendance.clock');
})->name('attendance.clock');

Route::middleware(['guest:web'])->group(function (): void {
    Route::get('/login', [UserLoginController::class, 'create'])->name('login');
    Route::post('/login', [UserLoginController::class, 'store']);
});

 // ログイン後のメイン画面を勤怠打刻画面に変更（FN006）
Route::middleware(['auth:web', 'verified'])->group(function (): void {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/action', [AttendanceController::class, 'index'])->name('attendance.action');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clockIn');
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])->name('attendance.breakStart');
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])->name('attendance.breakEnd');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clockOut');
});

Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::get('/attendance/list', [AttendanceListController::class, 'index'])
        ->name('attendance.list');
});

Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::get('/attendance/detail/{id}', [AttendanceDetailController::class, 'show'])
        ->name('attendance.detail.show');

    Route::post('/attendance/detail/{id}/request', [AttendanceDetailController::class, 'requestCorrection'])
        ->name('attendance.detail.request');
});

Route::middleware(['auth:web', 'verified'])->group(function () {
    Route::get('/stamp_correction_request/list', [RequestListController::class, 'index'])
    ->name('stamp_correction_request.list');
});

// === メール認証（一般ユーザー） ===
Route::get('/email/verify', [VerifyEmailController::class, 'notice'])
    ->middleware('auth:web')
    ->name('verification.notice');

// 認証メール再送
Route::post('/email/verification-notification', [VerifyEmailController::class, 'send'])
    ->middleware(['auth:web', 'throttle:6,1'])
    ->name('verification.send');

// メール内リンク（本認証）
Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
    ->middleware(['auth:web', 'signed'])
    ->name('verification.verify');

// === 管理者用（ログイン表示/処理） ===
Route::prefix('admin')->name('admin.')->group(function (): void {
    
// 未ログイン（管理者）
Route::middleware(['guest:admin'])->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// ログイン済み（管理者）
Route::middleware(['auth:admin'])->group(function (): void {
    Route::get('/attendance/list', [AdminAttendanceController::class, 'daily'])
        ->name('attendance.daily');
});
    Route::get('/attendance/detail/{attendance}', [AttendanceDetailController::class, 'show'])
            ->name('attendance.detail');
});

Route::prefix('admin')
    ->middleware('auth:admin')
    ->group(function (): void {
        // 管理者：勤怠詳細表示
        Route::get('attendance/{id}', [AdminAttendanceDetailController::class, 'show'])
            ->name('admin.attendance.detail')
            ->whereNumber('id');

        // 管理者：勤怠修正更新
        Route::put('attendance/{id}', [AdminAttendanceDetailController::class, 'update'])
            ->name('admin.attendance.detail.update')
            ->whereNumber('id');
    });

Route::middleware(['auth:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        // スタッフ一覧（管理者）
        Route::get('/staff/list', [StaffController::class, 'index'])
            ->name('staff.index');
    });

Route::middleware(['auth:admin'])->group(function () {
    // スタッフ別月次勤怠一覧（画面）
    Route::get('/admin/attendance/staff/{id}', [AdminAttendanceListController::class, 'monthlyByUser'])
        ->name('admin.attendance.staff.monthly');
    // スタッフ別月次勤怠一覧 CSV 出力
    Route::get('/admin/attendance/staff/{id}/csv', [AdminAttendanceListController::class, 'exportMonthlyCsv'])
        ->name('admin.attendance.staff.monthly.csv');
    });

Route::middleware(['auth:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/stamp_correction_request/list', [AdminRequestListController::class, 'index'])
            ->name('stamp_correction_request.list');

        // 修正申請承認画面（表示）
        Route::get('/stamp_correction_request/approve/{correction_request_id}', [RequestApproveController::class, 'show'])
            ->name('stamp_correction_request.approve.show');

        // 修正申請承認処理
        Route::post('/stamp_correction_request/approve/{correction_request_id}', [RequestApproveController::class, 'approve'])
            ->name('stamp_correction_request.approve');
    });
    
// ログアウト（両者）
Route::post('/logout', [UserLoginController::class, 'destroy'])->name('logout');
Route::post('/admin/logout', [AdminLoginController::class, 'destroy'])->name('admin.logout');
