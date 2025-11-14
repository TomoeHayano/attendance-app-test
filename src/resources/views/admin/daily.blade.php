@extends('layouts.app')

@section('title', '勤怠一覧（管理者）')

@section('content')
<main class="page">
    <header class="page__header">
        <h1 class="page__title">勤怠一覧（管理者）</h1>
    </header>

    <section class="attendance">
        <header class="attendance__header">
            <h2 class="attendance__heading">
                {{ $targetDate->format('Y年n月j日の勤怠') }}
            </h2>

            {{-- 日付変更フォーム --}}
            <form class="attendance-date" action="{{ route('admin.attendance.daily') }}" method="get">
                <div class="attendance-date__nav">
                    {{-- 前日 --}}
                    <button
                        type="submit"
                        name="date"
                        value="{{ $targetDate->copy()->subDay()->toDateString() }}"
                        class="attendance-date__button"
                    >
                        &lt; 前日
                    </button>

                    {{-- 日付ピッカー --}}
                    <div class="attendance-date__picker">
                        <label for="date" class="attendance-date__label">日付</label>
                        <input
                            id="date"
                            type="date"
                            name="date"
                            value="{{ $targetDate->toDateString() }}"
                            class="attendance-date__input"
                        >
                    </div>

                    {{-- 翌日 --}}
                    <button
                        type="submit"
                        name="date"
                        value="{{ $targetDate->copy()->addDay()->toDateString() }}"
                        class="attendance-date__button"
                    >
                        翌日 &gt;
                    </button>
                </div>
            </form>
        </header>

        <div class="attendance__table-wrapper">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th scope="col">名前</th>
                        <th scope="col">出勤</th>
                        <th scope="col">退勤</th>
                        <th scope="col">休憩</th>
                        <th scope="col">合計</th>
                        <th scope="col">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendances as $attendance)
                        <tr>
                            {{-- 名前 --}}
                            <td>{{ $attendance->user?->name }}</td>

                            {{-- 出勤 --}}
                            <td>
                                @if ($attendance->clock_in)
                                    {{ \Carbon\Carbon::createFromFormat('H:i:s', $attendance->clock_in)->format('H:i') }}
                                @endif
                            </td>

                            {{-- 退勤 --}}
                            <td>
                                @if ($attendance->clock_out)
                                    {{ \Carbon\Carbon::createFromFormat('H:i:s', $attendance->clock_out)->format('H:i') }}
                                @endif
                            </td>

                            {{-- 休憩合計（h:mm） --}}
                            <td>
                                @if (!is_null($attendance->total_break_minutes))
                                    @php
                                        $h = intdiv($attendance->total_break_minutes, 60);
                                        $m = $attendance->total_break_minutes % 60;
                                    @endphp
                                    {{ sprintf('%d:%02d', $h, $m) }}
                                @endif
                            </td>

                            {{-- 勤務合計（h:mm） --}}
                            <td>
                                @if (!is_null($attendance->total_work_minutes))
                                    @php
                                        $h = intdiv($attendance->total_work_minutes, 60);
                                        $m = $attendance->total_work_minutes % 60;
                                    @endphp
                                    {{ sprintf('%d:%02d', $h, $m) }}
                                @endif
                            </td>

                            {{-- 詳細リンク --}}
                            <td>
                                <a
                                    href="{{ route('admin.attendance.detail', ['attendance' => $attendance->id]) }}"
                                    class="attendance-table__link"
                                >
                                    詳細
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">この日に登録された勤怠はありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
@endsection