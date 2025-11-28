<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisteredUserController extends Controller
{
    /** 会員登録フォーム表示 */
    public function create(): View
    {
        return view('attendance.register');
    }

    /** 新規会員登録処理 */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            'name'     => (string) $request->input('name'),
            'email'    => (string) $request->input('email'),
            'password' => Hash::make((string) $request->input('password')),
        ]);

        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('attendance.clock');
    }
}
