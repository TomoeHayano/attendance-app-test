@extends('layouts.app')

@section('title', '申請一覧')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/request_list.css') }}">
@endsection

@section('body_class', 'page--request-list')

@section('content')
<section class="request-list">
    <h1 class="page-title">申請一覧</h1>

    <nav class="request-list__tabs" aria-label="申請ステータスタブ">
        <ul class="request-list__tabs-list">
            <li class="request-list__tabs-item">
                <a href="{{ route('stamp_correction_request.list', ['tab' => 'pending']) }}"
                   class="request-list__tab {{ $activeTab === 'pending' ? 'is-active' : '' }}"
                   @if($activeTab === 'pending') aria-current="page" @endif>
                    承認待ち
                </a>
            </li>
            <li class="request-list__tabs-item">
                <a href="{{ route('stamp_correction_request.list', ['tab' => 'approved']) }}"
                   class="request-list__tab {{ $activeTab === 'approved' ? 'is-active' : '' }}"
                   @if($activeTab === 'approved') aria-current="page" @endif>
                    承認済み
                </a>
            </li>
        </ul>
    </nav>

    <div class="request-list__table-wrapper">
        <table class="request-table">
            <thead>
                <tr>
                    <th scope="col">状態</th>
                    <th scope="col">名前</th>
                    <th scope="col">対象日時</th>
                    <th scope="col">申請理由</th>
                    <th scope="col">申請日時</th>
                    <th scope="col">詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($displayRequests as $requestItem)
                    <tr>
                        {{-- 状態（ステータス） --}}
                        <td>{{ $requestItem->statusLabel() }}</td>

                        {{-- 名前：ログインユーザー自身なので user.name でも OK --}}
                        <td>{{ $requestItem->user->name }}</td>

                        {{-- 対象日時（修正した日付）→ 対象勤怠日を表示 --}}
                        <td>
                            {{ $requestItem->attendance?->date?->format('Y/m/d') }}
                        </td>

                        {{-- 申請理由（備考） --}}
                        <td>{{ $requestItem->remarks }}</td>

                        {{-- 申請日時（秒なし） --}}
                        <td>
                            {{ $requestItem->created_at?->copy()->setSecond(0)->format('Y/m/d') }}
                        </td>

                        {{-- 詳細リンク：勤怠詳細（申請詳細）画面へ --}}
                        <td>
                            <a
                                href="{{ route('attendance.detail.show', ['id' => $requestItem->attendance_id]) }}"
                            >
                                詳細
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">表示する申請はありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
