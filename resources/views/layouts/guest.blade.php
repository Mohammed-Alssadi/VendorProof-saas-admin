<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'بوابة المشرفين') - لوحة إدارة المنصة</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    <!-- مكتبة Notyf للإشعارات التفاعلية العائمة -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <!-- كود منع الوميض: تطبيق الثيم فوراً قبل رسم الصفحة -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>
<body class="auth-page">
    <!-- زر تبديل المظهر العائم -->
    <div class="auth-theme-switch">
        <button id="themeToggleBtn" type="button" class="theme-toggle-btn" title="تبديل المظهر (ليلي / فاتح)">
            <i id="themeToggleIcon" class="ri-moon-line"></i>
        </button>
    </div>

    <div class="auth-card">
        @yield('content')
    </div>

    <!-- المحرك البرمجي الأساسي للمنصة -->
    <script src="{{ asset('js/admin-core.js') }}?v={{ filemtime(public_path('js/admin-core.js')) }}"></script>

    <!-- مكتبة Notyf للإشعارات التفاعلية العائمة -->
    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
    @include('partials.notyf')
</body>
</html>
