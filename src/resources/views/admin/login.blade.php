@extends('layouts.app')

@section('title', '管理者ログイン')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
    <main class="auth" role="main">
        </header>

        <section class="auth__body" aria-labelledby="admin-login-title">
            <h1 id="admin-login-title" class="auth__title">管理者ログイン</h1>

            <form method="post" action="{{ route('admin.login') }}" class="auth__form" novalidate>
                @csrf

                <div class="form__group">
                    <label for="admin-email" class="form__label">メールアドレス</label>
                    <input id="admin-email" name="email" type="email" class="form__input" value="{{ old('email') }}" required autocomplete="email" autofocus>
                    @error('email')
                        <p class="form__error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form__group">
                    <label for="admin-password" class="form__label">パスワード</label>
                    <input id="admin-password" name="password" type="password" class="form__input" required autocomplete="current-password">
                    @error('password')
                        <p class="form__error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn btn--primary">管理者ログインする</button>
            </form>
        </section>
    </main>
@endsection
