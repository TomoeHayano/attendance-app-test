<header class="site-header" aria-label="サイトヘッダー">
  <div class="site-header__inner">
    <div class="site-header__brand">CoachTech</div>

    @auth
      @php
          $user = Auth::user();
          $isAdmin = isset($user->role) && $user->role === 'admin';
          $isClockedOut = isset($user->attendance_status) && $user->attendance_status === '退勤済';
      @endphp

      <nav class="site-header__nav" aria-label="ナビゲーション">
        <ul class="nav-list">
          @if ($isAdmin)
            {{-- 管理者用 --}}
            <li><a href="{{ route('admin.attendance.index') }}">勤怠一覧</a></li>
            <li><a href="{{ route('admin.staff.index') }}">スタッフ一覧</a></li>
            <li><a href="{{ route('admin.requests.index') }}">申請一覧</a></li>
          @else
            {{-- 一般ユーザー用 --}}
            @if ($isClockedOut)
              <li><a href="{{ route('attendance.monthlyRequests') }}">今月の申請一覧</a></li>
              <li><a href="{{ route('attendance.requests') }}">申請一覧</a></li>
            @else
              <li><a href="{{ route('attendance.index') }}">勤怠</a></li>
              <li><a href="{{ route('attendance.list') }}">勤怠一覧</a></li>
              <li><a href="{{ route('attendance.requests') }}">申請</a></li>
            @endif
          @endif
          <li><a href="{{ route('logout') }}">ログアウト</a></li>
        </ul>
      </nav>
    @endauth
  </div>
</header>