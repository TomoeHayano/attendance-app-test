@extends('layouts.app')

@section('title', '勤怠一覧（管理者）')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin-attendance-list.css') }}">
@endsection

@section('content')
    <main class="admin-attendance">
        <div class="admin-attendance__inner">
            <header class="admin-attendance__header" aria-label="ページ見出し">
                <span class="admin-attendance__title-line" aria-hidden="true"></span>
                <h1 class="admin-attendance__title">
                    {{ $targetDate->format('Y年n月j日の勤怠') }}
                </h1>
            </header>

            {{-- 日付ナビゲーション --}}
            <section class="admin-attendance__controls" aria-label="日付選択">
                <form action="{{ route('admin.attendance.daily') }}" method="get" class="admin-attendance__nav-form">
                    {{-- 前日 --}}
                    <button
                        type="submit"
                        name="date"
                        value="{{ $targetDate->copy()->subDay()->toDateString() }}"
                        class="admin-attendance__nav-button admin-attendance__nav-button--prev"
                    >
                        <img
                            src="{{ asset('images/左矢印.png') }}"
                            alt=""
                            aria-hidden="true"
                            class="admin-attendance__nav-icon"
                        >
                        <span class="admin-attendance__nav-text">前日</span>
                    </button>

                    {{-- カレンダー＆日付入力 --}}
                    <div class="admin-attendance__current-date">
                        <img
                            src="{{ asset('images/カレンダー.png') }}"
                            alt=""
                            aria-hidden="true"
                            class="admin-attendance__calendar-icon"
                        >
                        <label for="target-date" class="visually-hidden">表示する日付を選択</label>
                        <input
                            id="target-date"
                            type="date"
                            name="target_date"
                            value="{{ $targetDate->toDateString() }}"
                            onchange="this.form.submit()"
                            class="admin-attendance__date-input"
                        >
                    </div>

                    {{-- 翌日 --}}
                    <button
                        type="submit"
                        name="date"
                        value="{{ $targetDate->copy()->addDay()->toDateString() }}"
                        class="admin-attendance__nav-button admin-attendance__nav-button--next"
                    >
                        <span class="admin-attendance__nav-text">翌日</span>
                        <img
                            src="{{ asset('images/右矢印.png') }}"
                            alt=""
                            aria-hidden="true"
                            class="admin-attendance__nav-icon"
                        >
                    </button>
                </form>
            </section>

            {{-- 勤怠一覧 --}}
            <section class="admin-attendance__table-card" aria-label="勤怠一覧">
                <div class="admin-attendance__table-wrap">
                    <table class="admin-attendance-table">
                        <colgroup>
                            <col class="admin-attendance-table__col admin-attendance-table__col--name">
                            <col class="admin-attendance-table__col admin-attendance-table__col--time">
                            <col class="admin-attendance-table__col admin-attendance-table__col--time">
                            <col class="admin-attendance-table__col admin-attendance-table__col--time">
                            <col class="admin-attendance-table__col admin-attendance-table__col--time">
                            <col class="admin-attendance-table__col admin-attendance-table__col--detail">
                        </colgroup>
                        <thead class="admin-attendance-table__head">
                            <tr>
                                <th scope="col">名前</th>
                                <th scope="col">出勤</th>
                                <th scope="col">退勤</th>
                                <th scope="col">休憩</th>
                                <th scope="col">合計</th>
                                <th scope="col" class="admin-attendance-table__cell--detail">詳細</th>
                            </tr>
                        </thead>
                        <tbody class="admin-attendance-table__body">
                            @forelse ($attendances as $attendance)
                                <tr>
                                    <td class="admin-attendance-table__cell admin-attendance-table__cell--name">
                                        {{ $attendance->user?->name }}
                                    </td>
                                    <td class="admin-attendance-table__cell">
                                        {{ $attendance->clock_in_formatted }}
                                    </td>
                                    <td class="admin-attendance-table__cell">
                                        {{ $attendance->clock_out_formatted }}
                                    </td>
                                    <td class="admin-attendance-table__cell">
                                        {{ $attendance->break_time_formatted }}
                                    </td>
                                    <td class="admin-attendance-table__cell">
                                        {{ $attendance->work_time_formatted }}
                                    </td>
                                    <td class="admin-attendance-table__cell admin-attendance-table__cell--detail">
                                        @if ($attendance->can_view_detail)
                                            <a
                                                href="{{ route('admin.attendance.detail', ['id' => $attendance->id]) }}"
                                                class="admin-attendance-table__detail-link"
                                            >
                                                詳細
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="admin-attendance-table__cell admin-attendance-table__cell--empty" colspan="6">
                                        この日に登録された勤怠はありません。
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
@endsection
