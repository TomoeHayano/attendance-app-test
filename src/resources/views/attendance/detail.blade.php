@extends('layouts.app')

@section('title', '勤怠詳細')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance-detail.css') }}">
@endsection

@section('content')
@php
    $formatTime = static function ($time): string {
        if (empty($time) && $time !== '0') {
            return '';
        }

        return substr((string) $time, 0, 5);
    };

    $breakLabel = static function (int $order): string {
        return $order === 1 ? '休憩' : '休憩' . $order;
    };
@endphp

<main class="attendance-detail">
    <section class="attendance-detail__inner">
        <h1 class="attendance-detail__title">勤怠詳細</h1>

        @if (session('status'))
            <p class="attendance-detail__flash">{{ session('status') }}</p>
        @endif

        {{-- 修正可否フラグ --}}
        @php
            $readonly = $hasPendingRequest;
        @endphp

        <form
            action="{{ $readonly ? '#' : route('attendance.detail.request', $attendance->id) }}"
            method="post"
        >
            @csrf

            <table class="attendance-detail__table">
                <tbody>
                    {{-- 名前 --}}
                    <tr>
                        <th scope="row">名前</th>
                        <td>{{ $attendance->user->name }}</td>
                    </tr>

                    {{-- 日付 --}}
                    <tr>
                        <th scope="row">日付</th>
                        <td>
                            <div class="attendance-detail__date">
                                <span class="attendance-detail__date-year">
                                    {{ $attendance->date->format('Y年') }}
                                </span>
                                <span class="attendance-detail__date-day">
                                    {{ $attendance->date->format('n月j日') }}
                                </span>
                            </div>
                        </td>
                    </tr>

                    {{-- 出勤・退勤 --}}
                    @php
                        $clockMessages = collect($errors->get('clock_in'))
                            ->merge($errors->get('clock_out'))
                            ->unique()
                            ->values();
                    @endphp
                    <tr>
                        <th scope="row">出勤・退勤</th>
                        <td>
                            <div class="attendance-detail__field-group">
                                <div class="attendance-detail__time-row">
                                    <div class="attendance-detail__time-field">
                                        <input
                                            type="text"
                                            name="clock_in"
                                            class="attendance-detail__time-input"
                                            value="{{ old('clock_in', $formatTime($attendance->clock_in)) }}"
                                            inputmode="numeric"
                                            data-normalize-time
                                            @if($readonly) readonly @endif
                                        >
                                    </div>
                                    <span class="attendance-detail__time-separator">〜</span>
                                    <div class="attendance-detail__time-field">
                                        <input
                                            type="text"
                                            name="clock_out"
                                            class="attendance-detail__time-input"
                                            value="{{ old('clock_out', $formatTime($attendance->clock_out)) }}"
                                            inputmode="numeric"
                                            data-normalize-time
                                            @if($readonly) readonly @endif
                                        >
                                    </div>
                                </div>
                                @if ($clockMessages->isNotEmpty())
                                    <div class="attendance-detail__error-list">
                                        @foreach ($clockMessages as $message)
                                            <p class="form__error">{{ $message }}</p>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>

                    {{-- 休憩（既存レコード分） --}}
                    @foreach ($breakRecords as $index => $breakRecord)
                        @php
                            $breakMessages = collect($errors->get("breakRecords.$index.start"))
                                ->merge($errors->get("breakRecords.$index.end"))
                                ->unique()
                                ->values();
                        @endphp
                        <tr>
                            <th scope="row">{{ $breakLabel($index + 1) }}</th>
                            <td>
                                <div class="attendance-detail__field-group">
                                    <div class="attendance-detail__time-row">
                                        <input type="hidden" name="breakRecords[{{ $index }}][required]" value="1">
                                        <div class="attendance-detail__time-field">
                                            <input
                                                type="text"
                                                name="breakRecords[{{ $index }}][start]"
                                                class="attendance-detail__time-input"
                                                value="{{ old("breakRecords.$index.start", $formatTime($breakRecord->break_start)) }}"
                                                inputmode="numeric"
                                                data-normalize-time
                                                @if($readonly) readonly @endif
                                            >
                                        </div>
                                        <span class="attendance-detail__time-separator">〜</span>
                                        <div class="attendance-detail__time-field">
                                            <input
                                                type="text"
                                                name="breakRecords[{{ $index }}][end]"
                                                class="attendance-detail__time-input"
                                                value="{{ old("breakRecords.$index.end", $formatTime($breakRecord->break_end)) }}"
                                                inputmode="numeric"
                                                data-normalize-time
                                                @if($readonly) readonly @endif
                                            >
                                        </div>
                                    </div>
                                    @if ($breakMessages->isNotEmpty())
                                        <div class="attendance-detail__error-list">
                                            @foreach ($breakMessages as $message)
                                                <p class="form__error">{{ $message }}</p>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    {{-- 休憩：追加で1行分の空フィールド --}}
                    @php
                        $newIndex = $breakRecords->count();
                        $nextOrder = $newIndex + 1;
                    @endphp
                    @php
                        $newBreakMessages = collect($errors->get("breakRecords.$newIndex.start"))
                            ->merge($errors->get("breakRecords.$newIndex.end"))
                            ->unique()
                            ->values();
                    @endphp
                    <tr>
                        <th scope="row">{{ $breakLabel($nextOrder) }}</th>
                        <td>
                            <div class="attendance-detail__field-group">
                                <div class="attendance-detail__time-row">
                                    <div class="attendance-detail__time-field">
                                        <input
                                            type="text"
                                            name="breakRecords[{{ $newIndex }}][start]"
                                            class="attendance-detail__time-input"
                                            value="{{ old("breakRecords.$newIndex.start") }}"
                                            inputmode="numeric"
                                            data-normalize-time
                                            @if($readonly) readonly @endif
                                        >
                                    </div>
                                    <span class="attendance-detail__time-separator">〜</span>
                                    <div class="attendance-detail__time-field">
                                        <input
                                            type="text"
                                            name="breakRecords[{{ $newIndex }}][end]"
                                            class="attendance-detail__time-input"
                                            value="{{ old("breakRecords.$newIndex.end") }}"
                                            inputmode="numeric"
                                            data-normalize-time
                                            @if($readonly) readonly @endif
                                        >
                                    </div>
                                </div>
                                @if ($newBreakMessages->isNotEmpty())
                                    <div class="attendance-detail__error-list">
                                        @foreach ($newBreakMessages as $message)
                                            <p class="form__error">{{ $message }}</p>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>

                    {{-- 備考 --}}
                    <tr>
                        <th scope="row">備考</th>
                        <td>
                            <div class="attendance-detail__field-group attendance-detail__field-group--remarks">
                                <textarea
                                    name="remarks"
                                    class="attendance-detail__remarks"
                                    placeholder="修正したい理由を記載してください"
                                    maxlength="500"
                                    data-autosize
                                    @if($readonly) readonly @endif
                                >{{ old('remarks') }}</textarea>
                                @error('remarks')
                                    <div class="attendance-detail__error-list">
                                        <p class="form__error">{{ $message }}</p>
                                    </div>
                                @enderror
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            {{-- ボタン or メッセージ --}}
            <div class="attendance-detail__footer">
                @if ($readonly)
                    <p class="attendance-detail__pending-message" style="color: #FF000080;">
                        *承認待ちのため修正はできません。
                    </p>
                @else
                    <button type="submit" class="attendance-detail__submit">
                        修正
                    </button>
                @endif
            </div>
        </form>
    </section>
</main>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-autosize]').forEach((textarea) => {
        const resize = () => {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        };
        textarea.addEventListener('input', resize);
        resize();
    });

    const normalizeTimeValue = (value) => {
        if (!value) {
            return value;
        }
        return value
            .replace(/[\uFF10-\uFF19]/g, (char) =>
                String.fromCharCode(char.charCodeAt(0) - 0xFEE0)
            )
            .replace(/\uFF1A/g, ':');
    };

    document.querySelectorAll('[data-normalize-time]').forEach((input) => {
        const normalize = () => {
            const normalized = normalizeTimeValue(input.value);
            if (normalized !== input.value) {
                input.value = normalized;
            }
        };
        input.addEventListener('input', normalize);
        input.addEventListener('blur', normalize);
        normalize();
    });
});
</script>
@endpush
@endsection
