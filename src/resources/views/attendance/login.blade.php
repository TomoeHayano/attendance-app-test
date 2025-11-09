@extends('layouts.app')

@section('body_class', 'body--plain')

@section('title', 'ログイン')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
    <main class="auth" role="main">
        </header>

        <section class="auth__body" aria-labelledby="login-title">
            <h1 id="login-title" class="auth__title">ログイン</h1>

            <form method="post" action="{{ route('login') }}" class="auth__form" novalidate>
                @csrf

                <div class="form__group">
                    <label for="email" class="form__label">メールアドレス</label>
                    <input id="email" name="email" type="email" class="form__input" value="{{ old('email') }}" required autocomplete="email" autofocus>
                    @error('email')
                        <p class="form__error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form__group">
                    <label for="password" class="form__label">パスワード</label>
                    <input id="password" name="password" type="password" class="form__input" required autocomplete="current-password">
                    @error('password')
                        <p class="form__error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn btn--primary">ログインする</button>

                <p class="auth__link">
                    <a href="{{ route('register') }}" class="link">会員登録はこちら</a>
                </p>
            </form>
        </section>
    </main>
@endsection
