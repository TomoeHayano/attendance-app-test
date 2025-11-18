<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\View\View;

class VerifyEmailController extends Controller
{
    /**
     * メール認証誘導画面表示 (/email/verify)
     *
     * @return View|RedirectResponse
     */
    public function notice(Request $request)
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return redirect()->route('attendance.index');
        }

        return view('auth.verify-email-invite');
    }

    /**
     * メール認証案内画面（認証はこちらからボタン遷移先）
     *
     * @return View|RedirectResponse
     */
    public function prompt(Request $request)
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return redirect()->route('attendance.index');
        }

        return view('auth.verify-email');
    }

    /**
     * 認証メール再送処理
     */
    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            // 既に認証済みならホーム等に飛ばす
            return redirect()->route('attendance.index'); // ★行き先はアプリに合わせて
        }

        $request->user()->sendEmailVerificationNotification();

        // セッションにフラグを立てる → 同じ画面でボタン非表示に使う
        return back()->with('status', 'verification-link-sent');
    }

    /**
     * メール内リンクからの本認証
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('attendance.index');
        }

        $request->fulfill();
        
        return redirect()
            ->route('attendance.index')
            ->with('status', 'email-verified');
    }
}
