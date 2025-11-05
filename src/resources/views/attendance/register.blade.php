@extends('layouts.app')

@section('title', '会員登録')

@section('css')
<link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endsection

@section('content')
<main class="register-page" aria-labelledby="page-title">
  <section class="register-stage" role="region" aria-label="会員登録フォーム領域">
    <h1 id="page-title" class="reg-title">会員登録</h1>

    <form class="reg-form" action="{{ route('register.post') }}" method="post" novalidate>
      @csrf

      <label for="name" class="label-name">名前</label>
      <input id="name" name="name" type="text" class="input-name" value="{{ old('name') }}" required>
      @error('name') <p class="form-error err-name">{{ $message }}</p> @enderror

      <label for="email" class="label-email">メールアドレス</label>
      <input id="email" name="email" type="email" class="input-email" value="{{ old('email') }}" required>
      @error('email') <p class="form-error err-email">{{ $message }}</p> @enderror

      <label for="password" class="label-password">パスワード</label>
      <input id="password" name="password" type="password" class="input-password" minlength="8" required>
      @error('password') <p class="form-error err-password">{{ $message }}</p> @enderror

      <label for="password_confirmation" class="label-password-confirm">パスワード確認</label>
      <input id="password_confirmation" name="password_confirmation" type="password" class="input-password-confirm" minlength="8" required>
      @error('password_confirmation') <p class="form-error err-password-confirm">{{ $message }}</p> @enderror
      
      <button type="submit" class="btn-submit">登録する</button>

      <nav class="login-nav" aria-label="ログイン導線">
        <a href="{{ route('login') }}" class="login-link">ログインはこちら</a>
      </nav>
    </form>
  </section>
</main>
@endsection