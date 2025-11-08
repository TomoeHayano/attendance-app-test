@extends('layouts.app')

@section('title', '勤怠登録（一般ユーザー）')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/header.css') }}">
  <link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
  {{-- @include('components.header') --}}
  <div class="header-spacer"></div>

  <main class="attendance" id="main" tabindex="-1">
    {{-- 画面上には出さないタイトル（セマンティック目的） --}}
    <h1 class="visually-hidden">勤怠登録</h1>

    {{-- ステータス（FN019） --}}
    <section class="status" aria-labelledby="status-heading">
      <h2 id="status-heading" class="visually-hidden">現在のステータス</h2>
      <p class="status__pill" aria-live="polite" aria-atomic="true">
        {{ $statusLabel }}
      </p>
    </section>

    {{-- 日付（FN018） --}}
    <section class="datetime" aria-labelledby="datetime-heading">
      <h2 id="datetime-heading" class="visually-hidden">現在日時</h2>
      <p class="datetime__date">{{ $displayDate }}</p>
      <p class="datetime__time">{{ $now->format('H:i') }}</p>
    </section>

    {{-- アクションボタン（FN020〜FN022） --}}
    <section class="actions" aria-labelledby="actions-heading">
      <h2 id="actions-heading" class="visually-hidden">打刻操作</h2>

      {{-- 退勤済メッセージ（リロードしても表示を維持） --}}
      @php
        $infoMessage = session('clockedOut') ?? $statusMessage;
      @endphp
      @if ($infoMessage)
        <p class="actions__message" role="status">{{ $infoMessage }}</p>
      @endif

      @if ($errors->has('attendance'))
        <p class="actions__message" role="alert">
          {{ $errors->first('attendance') }}
        </p>
      @endif

      <div class="actions__grid">
        @if ($canClockIn)
          <form method="post" action="{{ route('attendance.clockIn') }}" class="action-form">
            @csrf
            <button type="submit" class="btn btn--primary">出勤</button>
          </form>
        @endif

        @if ($canClockOut)
          <form method="post" action="{{ route('attendance.clockOut') }}" class="action-form">
            @csrf
            <button type="submit" class="btn btn--danger">退勤</button>
          </form>
        @endif

        @if ($canBreakStart)
          <form method="post" action="{{ route('attendance.breakStart') }}" class="action-form">
            @csrf
            <button type="submit" class="btn btn--secondary">休憩入</button>
          </form>
        @endif

        @if ($canBreakEnd)
          <form method="post" action="{{ route('attendance.breakEnd') }}" class="action-form">
            @csrf
            <button type="submit" class="btn btn--secondary">休憩戻</button>
          </form>
        @endif
      </div>
    </section>
  </main>
@endsection
