@extends('layouts.app')

@section('title', 'スタッフ一覧（管理者）')

@section('body_class', 'body--tinted body--staff')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/staff-list.css') }}">
@endsection

@section('content')
<main class="staff" aria-labelledby="staff-index-title">
    <div class="staff__inner">
        <header class="staff__header">
            <h1 id="staff-index-title" class="staff__title">
                スタッフ一覧
            </h1>
        </header>

        <section class="staff__section" aria-label="スタッフ一覧テーブル">
            <div class="staff__table-wrapper">
                <table class="staff-table">
                    <thead class="staff-table__head">
                        <tr>
                            <th scope="col" class="staff-table__heading staff-table__heading--name">
                                名前
                            </th>
                            <th scope="col" class="staff-table__heading staff-table__heading--email">
                                メールアドレス
                            </th>
                            <th scope="col" class="staff-table__heading staff-table__heading--attendance">
                                月次勤怠
                            </th>
                        </tr>
                    </thead>
                    <tbody class="staff-table__body">
                        @forelse ($users as $user)
                            <tr class="staff-table__row">
                                <td class="staff-table__cell staff-table__cell--name">
                                    {{ $user->name }}
                                </td>
                                <td class="staff-table__cell staff-table__cell--email">
                                    {{ $user->email }}
                                </td>
                                <td class="staff-table__cell staff-table__cell--attendance">
                                    <a href="{{ url('/admin/attendance/staff/' . $user->id) }}"
                                        class="staff-table__detail-link">
                                        <span class="staff-table__detail-text">詳細</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr class="staff-table__row staff-table__row--empty">
                                <td class="staff-table__cell" colspan="3">
                                    登録されているスタッフはいません。
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
