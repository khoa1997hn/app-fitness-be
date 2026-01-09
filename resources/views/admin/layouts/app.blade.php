<!DOCTYPE html>
<html lang="zxx" dir="ltr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <title>@yield('title', 'Bảng quản trị')</title>
    <link rel="icon" type="image/png" href="{{ asset('dashcode/assets/images/logo/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('dashcode/assets/css/rt-plugins.css') }}">
    <link rel="stylesheet" href="{{ asset('dashcode/assets/css/app.css') }}">
    <script src="{{ asset('dashcode/assets/js/settings.js') }}" sync></script>
    @stack('styles')
</head>
<body class="font-inter dashcode-app" id="body_class">
    <main class="app-wrapper">
        @include('admin.components.sidebar')

        <div class="flex flex-col justify-between min-h-screen">
            <div>
                @include('admin.components.header')

                <div class="content-wrapper transition-all duration-150 ltr:ml-[248px] rtl:mr-[248px]" id="content_wrapper">
                    @include('admin.components.alert')
                    <div class="page-content">
                        <div class="transition-all duration-150 container-fluid" id="page_layout">
                            <div id="content_layout">
                                @yield('content')
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @include('admin.components.footer')
        </div>
    </main>

    <script src="{{ asset('dashcode/assets/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('dashcode/assets/js/rt-plugins.js') }}"></script>
    <script src="{{ asset('dashcode/assets/js/app.js') }}"></script>
    <script>
        document.getElementById('thisYear').textContent = new Date().getFullYear();
    </script>
    @stack('scripts')
</body>
</html>
