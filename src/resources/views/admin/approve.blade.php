@extends('layouts.app')

@section('title', '修正申請承認')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endsection

@section('body_class', 'body--tinted')

@section('content')
@php
    $formatTime = static fn($v) => $v ? substr($v,0,5) : '';
@endphp

<main class="attendance-detail">
    <section class="attendance-detail__inner">
        <h1 class="attendance-detail__title">勤怠詳細</h1>

        <table class="attendance-detail__table">
            <tbody>
                {{-- 名前 --}}
                <tr>
                    <th>名前</th>
                    <td>{{ $attendance->user->name }}</td>
                </tr>

                {{-- 日付 --}}
                <tr>
                    <th>日付</th>
                    <td>{{ $attendance->date->format('Y年 n月j日') }}</td>
                </tr>

                {{-- 出勤退勤 --}}
                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        {{ $formatTime($correctionRequest->corrected_clock_in) }}
                        〜
                        {{ $formatTime($correctionRequest->corrected_clock_out) }}
                    </td>
                </tr>

                {{-- 休憩 --}}
                @foreach ($breakRecords as $i => $br)
                <tr>
                    <th>{{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}</th>
                    <td>
                        {{ $formatTime($br->corrected_break_start) }}
                        〜
                        {{ $formatTime($br->corrected_break_end) }}
                    </td>
                </tr>
                @endforeach

                {{-- 備考 --}}
                <tr>
                    <th>備考</th>
                    <td>{{ $correctionRequest->remarks }}</td>
                </tr>
            </tbody>
        </table>

        {{-- 承認 --}}
        <div class="attendance-detail__footer">

            @if ($correctionRequest->status === 1)
                {{-- 承認待ち：承認ボタン（POST） --}}
                <form action="{{ route('admin.stamp_correction_request.approve', $correctionRequest->id) }}" method="post">
                    @csrf
                    <button type="submit" class="attendance-detail__submit">
                        承認
                    </button>
                </form>
            @else
                {{-- 承認済み：ボタンを無効化 --}}
                <button type="button" class="attendance-detail__submit" disabled>
                    承認済み
                </button>
            @endif

        </div>
    </section>
</main>
@endsection
