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
            'name'     => ['required', 'string', 'max:20'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'password_confirmation' => ['required', 'string', 'min:8', 'same:password'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required'                  => 'ユーザー名を入力してください',
            'name.max'                       => 'ユーザー名は20文字以内で入力してください',
            'email.required'                 => 'メールアドレスを入力してください',
            'email.email'                    => 'メールアドレスの形式が正しくありません',
            'password.required'              => 'パスワードを入力してください',
            'password.min'                   => 'パスワードは8文字以上で入力してください',
            'password_confirmation.required' => '確認用パスワードを入力してください',
            'password_confirmation.min'      => '確認用パスワードは8文字以上で入力してください',
            'password_confirmation.same'     => '確認用パスワードが一致しません',
        ];
    }
}
