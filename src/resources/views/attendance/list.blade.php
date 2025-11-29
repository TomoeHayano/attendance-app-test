@extends('layouts.app')

@section('title', '勤怠一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance-list.css') }}">
@endsection

@section('content')
    <main class="attendance-list">
        <div class="attendance-list__inner">

            <header class="attendance-list__header" aria-label="ページヘッダー">
                <div class="attendance-list__title-wrap">
                    <span class="attendance-list__title-line"></span>
                    <h1 class="attendance-list__title">勤怠一覧</h1>
                </div>
            </header>

            {{-- 月ナビゲーションエリア（前月・当月・翌月 + 矢印 + カレンダーアイコン） --}}
            <section class="attendance-list__month-bar" aria-label="月選択エリア">
                <div class="attendance-list__month-inner">
                    {{-- 前月ボタン --}}
                    <a href="{{ route('attendance.list', ['year' => $prevMonthDate->year, 'month' => $prevMonthDate->month]) }}"
                       class="attendance-list__month-nav attendance-list__month-nav--prev">
                        <img src="{{ asset('images/左矢印.png') }}"
                             alt="前月へ"
                             class="attendance-list__month-arrow">
                        <span class="attendance-list__month-text">前月</span>
                    </a>

                    {{-- 当月（表示中の月） --}}
                    <div class="attendance-list__month-current">
                        <img src="{{ asset('images/カレンダー.png') }}"
                            alt="カレンダー"
                            class="attendance-list__month-calendar">
                        <span class="attendance-list__month-label">
                            {{ $targetDate->format('Y/m') }}
                        </span>
                    </div>

                    {{-- 翌月ボタン --}}
                    <a href="{{ route('attendance.list', ['year' => $nextMonthDate->year, 'month' => $nextMonthDate->month]) }}"
                       class="attendance-list__month-nav attendance-list__month-nav--next">
                        <span class="attendance-list__month-text">翌月</span>
                        <img src="{{ asset('images/右矢印.png') }}"
                             alt="翌月へ"
                             class="attendance-list__month-arrow attendance-list__month-arrow--right">
                    </a>
                </div>
            </section>

            {{-- 一覧エリア --}}
            <section class="attendance-list__card" aria-label="月次勤怠一覧">
                <div class="attendance-list__table-wrap">
                    <table class="attendance-table">
                        <colgroup>
                            <col class="attendance-table__col attendance-table__col--date">
                            <col class="attendance-table__col attendance-table__col--time">
                            <col class="attendance-table__col attendance-table__col--time">
                            <col class="attendance-table__col attendance-table__col--time">
                            <col class="attendance-table__col attendance-table__col--time">
                            <col class="attendance-table__col attendance-table__col--detail">
                        </colgroup>
                        <thead class="attendance-table__head">
                        <tr>
                            <th scope="col" class="attendance-table__cell attendance-table__cell--date">日付</th>
                            <th scope="col" class="attendance-table__cell attendance-table__cell--time">出勤</th>
                            <th scope="col" class="attendance-table__cell attendance-table__cell--time">退勤</th>
                            <th scope="col" class="attendance-table__cell attendance-table__cell--time">休憩</th>
                            <th scope="col" class="attendance-table__cell attendance-table__cell--time">合計</th>
                            <th scope="col" class="attendance-table__cell attendance-table__cell--detail">詳細</th>
                        </tr>
                        </thead>

                        <tbody class="attendance-table__body">
                            @forelse ($attendanceRows as $row)
                                <tr class="attendance-table__row">
                                    <td class="attendance-table__cell attendance-table__cell--date">
                                        {{ $row['date_label'] }}
                                    </td>
                                    <td class="attendance-table__cell">
                                        {{ $row['clock_in'] }}
                                    </td>
                                    <td class="attendance-table__cell">
                                        {{ $row['clock_out'] }}
                                    </td>
                                    <td class="attendance-table__cell">
                                        {{ $row['break_time'] }}
                                    </td>
                                    <td class="attendance-table__cell">
                                        {{ $row['working_time'] }}
                                    </td>
                                    <td class="attendance-table__cell attendance-table__cell--detail">
                                        @if ($row['can_view_detail'])
                                            <a
                                                href="{{ route('attendance.detail.show', $row['detail_id']) }}"
                                                class="attendance-table__detail-link">
                                                <span class="attendance-table__detail-text">詳細</span>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="attendance-table__cell attendance-table__cell--empty" colspan="6">
                                        勤怠情報はありません。
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
