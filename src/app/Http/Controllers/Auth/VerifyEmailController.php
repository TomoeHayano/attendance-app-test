<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerifyEmailController extends Controller
{
    /**
     * メール認証誘導／案内画面表示 (/email/verify)
     *
     * @return View|RedirectResponse
     */
    public function notice(Request $request)
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return redirect()->route('attendance.index');
        }

        $isPrompt = $request->boolean('prompt');

        return $isPrompt
            ? view('auth.verify-email')
            : view('auth.verify-email-invite');
    }

    /**
     * 認証メール再送処理
     */
    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {

            return redirect()->route('attendance.index');
        }

        $request->user()->sendEmailVerificationNotification();

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
