<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class AuthenticatedSessionController extends Controller
{
    /**
     * ログインフォーム表示
     * @return View
     */
    public function create(): View
    {
        return view('attendance.login');
    }

    /**
     * @param LoginRequest $request
     * @return RedirectResponse
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = (bool) $request->boolean('remember');

        if (! Auth::guard('web')->attempt($credentials, $remember)) {
            // 仕様：入力情報が誤っている場合の共通エラー
            return back()
                ->withErrors(['email' => 'ログイン情報が登録されていません'])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        // メール未認証 → 誘導画面へ
        $user = Auth::guard('web')->user();
        if (method_exists($user, 'hasVerifiedEmail') && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return Redirect::intended(route('dashboard'));
    }

    /**
     * ログアウト
     * @return RedirectResponse
     */
    public function destroy(): RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
