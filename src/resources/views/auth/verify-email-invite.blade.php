@extends('layouts.app')

@section('body_class', 'body--plain')

@section('title', 'メール認証')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endsection

@section('content')
    <section class="verification" role="main" aria-labelledby="verification-message">
        <div class="verification__panel">
            <p id="verification-message" class="verification__message">
                登録していただいたメールアドレスに認証メールを送付しました。<br>
                メール認証を完了してください。
            </p>

            <a href="{{ route('verification.notice', ['prompt' => 1]) }}" class="verification__cta">
                認証はこちらから
            </a>

            <form method="POST" action="{{ route('verification.send') }}" class="verification__resend-form">
                @csrf
                <button type="submit" class="verification__resend-link">
                    認証メールを再送する
                </button>
            </form>

            @if (session('status') === 'verification-link-sent')
                <p class="verification__status" role="status">
                    認証メールを再送しました。メールをご確認ください。
                </p>
            @endif
        </div>
    </section>
@endsection
