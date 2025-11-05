<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    @yield('css') 
</head>
<body>
<header class="global-header">
    <div class="header-inner">
        <img src="{{ asset('storage/images/coachtech_white.png') }}" alt="COACHTECH" class="header-logo">
    </div>
</header>

<main class="page-wrap">
    @yield('content')
</main>
</body>
</html>