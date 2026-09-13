<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'لوحة التحكم') - إدارة المنصة</title>

    <!-- الخط الرسمي: IBM Plex Sans Arabic -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- مكتبة الأيقونات الرسمية: Remix Icon (بدون أي إيموجي) -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />

    <!-- ملف التنسيقات المركزي -->
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    <!-- مكتبة Notyf للإشعارات التفاعلية العائمة -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    @yield('styles')

    <!-- كود منع الوميض: تطبيق الثيم فوراً قبل رسم الصفحة -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>
<body>
    <div class="app-wrapper">
        <!-- طبقة التعتيم الخلفية للشاشات الصغيرة (Mobile Backdrop) -->
        <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

        <!-- 1. القائمة الجانبية المستقرة (Persistent Sidebar & Mobile Drawer) -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand-icon">
                    <i class="ri-shield-check-fill"></i>
                </div>
                <div class="sidebar-brand-text">
                    <span class="brand-name">Vendor<span class="brand-accent">Proof</span></span>
                    <span class="brand-tagline">امتثال واعتماد الموردين</span>
                </div>
                <!-- زر إغلاق السايدبار على الجوال -->
                <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="إغلاق القائمة">
                    <i class="ri-close-line"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                <!-- الرئيسية -->
                <div>
                    <div class="nav-group-title">الرئيسية</div>
                    <ul class="nav-list">
                        <li>
                            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="ri-dashboard-3-line"></i>
                                <span>لوحة التحكم</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- العملاء والشركات (Customers) -->
                <div>
                    <div class="nav-group-title">العملاء والشركات</div>
                    <ul class="nav-list">
                        <li>
                            <a href="{{ route('tenants.index') }}" class="nav-link {{ request()->routeIs('tenants.*') ? 'active' : '' }}">
                                <i class="ri-building-line"></i>
                                <span>الشركات والمنشآت</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- دليل الخدمات والكتالوج (Catalog) -->
                <div>
                    <div class="nav-group-title">دليل الخدمات والكتالوج</div>
                    <ul class="nav-list">
                        <li>
                            <a href="{{ route('plans.index') }}" class="nav-link {{ request()->routeIs('plans.*') ? 'active' : '' }}">
                                <i class="ri-price-tag-3-line"></i>
                                <span>الخطط والأسعار</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('features.index') }}" class="nav-link {{ request()->routeIs('features.*') ? 'active' : '' }}">
                                <i class="ri-equalizer-line"></i>
                                <span>الميزات والحدود</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- الفوترة والمالية (Billing) -->
                <div>
                    <div class="nav-group-title">الفوترة والمالية</div>
                    <ul class="nav-list">
                        <li>
                            <a href="{{ route('subscriptions.index') }}" class="nav-link {{ request()->routeIs('subscriptions.*') ? 'active' : '' }}">
                                <i class="ri-file-list-3-line"></i>
                                <span>سجلات الاشتراكات</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- الإعدادات والنظام (Settings) -->
                <div>
                    <div class="nav-group-title">الإعدادات والنظام</div>
                    <ul class="nav-list">
                        <li>
                            <a href="{{ route('profile.index') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                                <i class="ri-settings-4-line"></i>
                                <span>إعدادات الحساب</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- بطاقة المشرف الثابتة أسفل السايدبار مع القائمة المنبثقة -->
            <div class="sidebar-footer">
                <!-- القائمة المنبثقة التفاعلية -->
                <div class="profile-dropdown-menu" id="profileDropdownMenu">
                    <div class="dropdown-header">
                        <div class="profile-name">{{ Auth::user()->name }}</div>
                        <div class="email">{{ Auth::user()->email }}</div>
                      
                    </div>
                    <a href="{{ route('profile.index') }}" class="dropdown-item">
                        <i class="ri-user-settings-line"></i>
                        <span>إعدادات الحساب</span>
                    </a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item logout">
                            <i class="ri-logout-box-r-line"></i>
                            <span>تسجيل الخروج</span>
                        </button>
                    </form>
                </div>

                <!-- زر بطاقة المشرف لفتح القائمة -->
                <button type="button" class="profile-button" id="profileDropdownBtn" aria-haspopup="true" aria-expanded="false">
                    <div class="profile-user-info">
                        <div class="profile-avatar">
                            {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <div class="profile-details">
                            <div class="profile-name">{{ Auth::user()->name }}</div>
                            <div class="profile-role">السوبر أدمن</div>
                        </div>
                    </div>
                    <i class="ri-more-2-fill profile-toggle-icon"></i>
                </button>
            </div>
        </aside>

        <!-- 2. منطقة المحتوى والشريط العلوي -->
        <div class="main-wrapper">
            <!-- الشريط العلوي (Topbar) -->
            <header class="topbar">
                <div class="topbar-start">
                    <button type="button" class="sidebar-toggle-btn" id="sidebarToggleBtn" aria-label="فتح القائمة">
                        <i class="ri-menu-2-line"></i>
                    </button>
                    <div class="breadcrumbs">
                        <a href="{{ route('dashboard') }}"><i class="ri-home-4-line"></i></a>
                        @hasSection('breadcrumbs')
                            @yield('breadcrumbs')
                        @else
                            <span>/</span>
                            <span class="current">لوحة التحكم</span>
                        @endif
                    </div>
                </div>

                <div class="topbar-actions">
                    <!-- زر تفعيل البحث للشاشات الصغيرة -->
                    <button type="button" class="mobile-search-toggle-btn" id="mobileSearchToggleBtn" title="بحث سريع" aria-label="بحث سريع في المنصة">
                        <i class="ri-search-line"></i>
                    </button>

                    <!-- شريط البحث المكتبي الافتراضي -->
                    <div class="search-box" id="topbarSearchBox">
                        <i class="ri-search-line"></i>
                        <input type="text" class="search-input" id="topbarSearchInput" placeholder="بحث سريع في المنصة...">
                        <span class="kbd-shortcut">Ctrl+K</span>
                    </div>

                    <!-- زر تبديل الوضع الليلي / الفاتح -->
                    <button type="button" class="theme-toggle-btn" id="themeToggleBtn" title="تبديل مظهر المنصة" aria-label="تبديل مظهر المنصة">
                        <i class="ri-moon-line" id="themeToggleIcon"></i>
                    </button>
                </div>

                <!-- شريط البحث المنسدل للشاشات الصغيرة - ينزل أسفل الهيدر مباشرة ويبقى الهيدر ثابتاً بكامل أزراره -->
                <div class="mobile-search-bar" id="mobileSearchBar">
                    <div class="mobile-search-inner">
                        <i class="ri-search-line mobile-search-icon"></i>
                        <input type="text" class="mobile-search-field" id="mobileSearchField" placeholder="بحث سريع في المنصة..." autocomplete="off">
                        <button type="button" class="mobile-search-close-btn" id="mobileSearchCloseBtn" aria-label="إغلاق شريط البحث">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                </div>
            </header>

            <!-- حاوية المحتوى الرئيسي والتمرير الداخلي المستقل -->
            <div class="main-scroll-area">
                <main class="content-container">
                    @yield('content')
                </main>

                <!-- التذييل الهادئ -->
                <footer class="content-footer">
                    نظام إدارة المنصة السحابية &copy; {{ date('Y') }} - جميع الحقوق محفوظة
                </footer>
            </div>
        </div>
    </div>

    <!-- المحرك البرمجي الأساسي للوحة التحكم -->
    <script src="{{ asset('js/admin-core.js') }}?v={{ filemtime(public_path('js/admin-core.js')) }}"></script>
    @yield('scripts')

    <!-- مكتبة Notyf للإشعارات التفاعلية العائمة -->
    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
    @include('partials.notyf')
</body>
</html>
