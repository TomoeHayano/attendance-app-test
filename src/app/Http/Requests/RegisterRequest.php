<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'password_confirmation' => ['required', 'same:password'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required'                  => 'お名前を入力してください',
            'email.required'                 => 'メールアドレスを入力してください',
            'password.required'              => 'パスワードを入力してください',
            'password.min'                   => 'パスワードは8文字以上で入力してください',
            'password.confirmed'             => 'パスワードと一致しません',
            'password_confirmation.same'     => 'パスワードと一致しません',
            'password_confirmation.required' => 'パスワードを入力してください',
        ];
    }
}
