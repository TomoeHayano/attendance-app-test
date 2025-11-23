@extends('layouts.app')
@php
    use Carbon\Carbon;

    $attendanceDate = $attendance->date instanceof Carbon
        ? $attendance->date
        : Carbon::parse($attendance->date);

    $formatTime = static function ($value): string {
        if ($value instanceof Carbon) {
            return $value->format('H:i');
        }

        $stringValue = trim((string) ($value ?? ''));

        if ($stringValue === '') {
            return '';
        }

        return Carbon::parse($stringValue)->format('H:i');
    };

    $breakLabel = static function (int $order): string {
        return $order === 1 ? '休憩' : '休憩' . $order;
    };
@endphp

@section('title', '勤怠詳細（管理者）')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin-attendance-detail.css') }}">
@endsection

@section('body_class', 'body--tinted')

@section('content')
@php
    $readonly = $hasPendingRequest;
    $displayClockIn  = $pendingCorrectionRequest?->corrected_clock_in ?? $attendance->clock_in;
    $displayClockOut = $pendingCorrectionRequest?->corrected_clock_out ?? $attendance->clock_out;
    $displayRemarks  = $pendingCorrectionRequest->remarks ?? $attendance->remarks;
@endphp

<main class="attendance-detail">
    <section class="attendance-detail__inner">
        <h1 class="attendance-detail__title">勤怠詳細</h1>

        <form
            action="{{ $readonly ? '#' : route('admin.attendance.detail.update', ['id' => $attendance->id]) }}"
            method="post"
        >
            @csrf
            @method('PUT')

            <table class="attendance-detail__table">
                <tbody>
                    <tr>
                        <th scope="row">名前</th>
                        <td>{{ $attendance->user->name ?? '不明' }}</td>
                    </tr>

                    <tr>
                        <th scope="row">日付</th>
                        <td>
                            <div class="attendance-detail__date">
                                <span class="attendance-detail__date-year">
                                    {{ $attendanceDate->format('Y年') }}
                                </span>
                                <span class="attendance-detail__date-day">
                                    {{ $attendanceDate->format('n月j日') }}
                                </span>
                            </div>
                        </td>
                    </tr>

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
                                            value="{{ old('clock_in', $formatTime($displayClockIn)) }}"
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
                                            value="{{ old('clock_out', $formatTime($displayClockOut)) }}"
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
                                        @if ($index === 0)
                                            <input type="hidden" name="breakRecords[{{ $index }}][required]" value="1">
                                        @endif
                                        <div class="attendance-detail__time-field">
                                            <input
                                                type="text"
                                                name="breakRecords[{{ $index }}][start]"
                                                class="attendance-detail__time-input"
                                                value="{{ old("breakRecords.$index.start", $formatTime($breakRecord->break_start ?? null)) }}"
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
                                                value="{{ old("breakRecords.$index.end", $formatTime($breakRecord->break_end ?? null)) }}"
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

                    @php
                        $newIndex = $breakRecords->count();
                        $nextOrder = $newIndex + 1;
                        $newBreakMessages = collect($errors->get("breakRecords.$newIndex.start"))
                            ->merge($errors->get("breakRecords.$newIndex.end"))
                            ->unique()
                            ->values();
                    @endphp
                    @unless($readonly)
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
                    @endunless

                    <tr>
                        <th scope="row">備考</th>
                        <td>
                            <div class="attendance-detail__field-group attendance-detail__field-group--remarks">
                                <textarea
                                    name="remarks"
                                    class="attendance-detail__remarks"
                                    maxlength="255"
                                    data-autosize
                                    @if($readonly) readonly @endif
                                >{{ old('remarks', $displayRemarks) }}</textarea>
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

            <div class="attendance-detail__footer">
                @if ($readonly)
                    <p class="attendance-detail__pending-message" style="color: #FF000080;">
                        *承認待ちのため修正はできません。
                    </p>
                @else
                    <button type="submit" class="attendance-detail__submit">
                        修正する
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
