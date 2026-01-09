<!DOCTYPE html>
<html lang="en" dir="ltr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <title>@yield('title', 'Đăng nhập Admin')</title>
    <link rel="icon" type="image/png" href="{{ asset('dashcode/assets/images/logo/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('dashcode/assets/css/rt-plugins.css') }}">
    <link rel="stylesheet" href="{{ asset('dashcode/assets/css/app.css') }}">
    <script src="{{ asset('dashcode/assets/js/settings.js') }}" sync></script>
    @stack('styles')
</head>
<body class="font-inter skin-default">
    @yield('content')

    <script src="{{ asset('dashcode/assets/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('dashcode/assets/js/rt-plugins.js') }}"></script>
    <script src="{{ asset('dashcode/assets/js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
