<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @yield('css') 
</head>
<body class="@yield('body_class', '')">
<header class="global-header">
    <div class="header-inner">
        <img src="{{ asset('storage/images/coachtech_white.png') }}" alt="COACHTECH" class="header-logo">
        @auth
            @php
                $navLinks = [
                    ['type' => 'link', 'label' => '勤怠', 'url' => route('attendance.index')],
                    ['type' => 'link', 'label' => '勤怠一覧', 'url' => route('attendance.list')],
                    ['type' => 'link', 'label' => '申請', 'url' => route('stamp_correction_request.list')],
                    ['type' => 'logout', 'label' => 'ログアウト'],
                ];

                if (\Illuminate\Support\Facades\Route::currentRouteName() === 'attendance.action') {
                    $timezone   = (string) config('app.timezone', 'Asia/Tokyo');
                    $today      = \Carbon\Carbon::today($timezone);
                    $attendance = \App\Models\Attendance::query()
                        ->where('user_id', auth()->id())
                        ->whereDate('date', $today)
                        ->first();

                    if ($attendance && (int) $attendance->status === \App\Models\Attendance::STATUS_CLOCKED_OUT) {
                        $attendanceDate = $attendance->date instanceof \Carbon\Carbon
                            ? $attendance->date->copy()
                            : \Carbon\Carbon::parse((string) $attendance->date, $timezone);

                        $navLinks = [
                            [
                                'type'  => 'link',
                                'label' => '今月の出勤一覧',
                                'url'   => route('attendance.list', [
                                    'year'  => $attendanceDate->year,
                                    'month' => $attendanceDate->month,
                                ]),
                            ],
                            ['type' => 'link', 'label' => '申請一覧', 'url' => route('stamp_correction_request.list')],
                            ['type' => 'logout', 'label' => 'ログアウト'],
                        ];
                    }
                }
            @endphp

            @if (!empty($navLinks))
                <nav class="global-nav" aria-label="メインナビゲーション">
                    <ul class="nav-list">
                        @foreach ($navLinks as $link)
                            @if ($link['type'] === 'link')
                                <li><a href="{{ $link['url'] }}">{{ $link['label'] }}</a></li>
                            @elseif ($link['type'] === 'logout')
                                <li>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="nav-link-button">{{ $link['label'] }}</button>
                                    </form>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </nav>
            @endif
        @endauth
    </div>
</header>

<main class="page-wrap">
    @yield('content')
</main>

@stack('scripts')
</body>
</html>
