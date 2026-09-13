/**
 * VendorProof SaaS Admin - Master Platform Core Script
 * Comprehensive Layout, Drawer, Dropdown, Search & Theme Engine
 */

(function () {
    'use strict';

    /**
     * 1. إدارة تبديل الوضع الليلي والفاتح (Dual-Theme Engine)
     */
    function initThemeToggle() {
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const themeToggleIcon = document.getElementById('themeToggleIcon');

        function updateThemeIcon(theme) {
            if (!themeToggleIcon) return;
            if (theme === 'dark') {
                themeToggleIcon.className = 'ri-sun-line';
            } else {
                themeToggleIcon.className = 'ri-moon-line';
            }
        }

        // مزامنة الأيقونة فور تحميل الصفحة مع الثيم الحالي
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        updateThemeIcon(currentTheme);

        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', function () {
                const activeTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
                const newTheme = activeTheme === 'dark' ? 'light' : 'dark';

                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeIcon(newTheme);
            });
        }
    }

    /**
     * 2. إدارة القائمة الجانبية للشاشات الصغيرة (Off-Canvas Mobile Drawer)
     */
    function initSidebarDrawer() {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
        const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');

        function openSidebar() {
            if (sidebar) sidebar.classList.add('open');
            if (sidebarBackdrop) sidebarBackdrop.classList.add('show');
            document.body.classList.add('sidebar-open');
        }

        function closeSidebar() {
            if (sidebar) sidebar.classList.remove('open');
            if (sidebarBackdrop) sidebarBackdrop.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }

        if (sidebarToggleBtn) {
            sidebarToggleBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                if (sidebar && sidebar.classList.contains('open')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });
        }

        if (sidebarCloseBtn) {
            sidebarCloseBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                closeSidebar();
            });
        }

        if (sidebarBackdrop) {
            sidebarBackdrop.addEventListener('click', closeSidebar);
        }

        // إغلاق السايدبار عند الضغط على Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeSidebar();
            }
        });

        // إغلاق السايدبار تلقائياً عند النقر على أي رابط تنقل في الشاشات الصغيرة
        if (sidebar) {
            sidebar.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', function () {
                    if (window.innerWidth < 1024) {
                        closeSidebar();
                    }
                });
            });
        }

        // إغلاق السايدبار تلقائياً عند تكبير الشاشة لأكبر من 1024px
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 1024) {
                closeSidebar();
            }
        });
    }

    /**
     * 3. إدارة شريط البحث المنسدل أسفل الهيدر للشاشات الصغيرة (Mobile Dropdown Search)
     */
    function initMobileSearch() {
        const mobileSearchToggleBtn = document.getElementById('mobileSearchToggleBtn');
        const mobileSearchCloseBtn = document.getElementById('mobileSearchCloseBtn');
        const mobileSearchBar = document.getElementById('mobileSearchBar');
        const mobileSearchField = document.getElementById('mobileSearchField');

        function openMobileSearch() {
            if (mobileSearchBar) {
                mobileSearchBar.classList.add('open');
                if (mobileSearchToggleBtn) mobileSearchToggleBtn.classList.add('active');
                if (mobileSearchField) {
                    setTimeout(() => mobileSearchField.focus(), 60);
                }
            }
        }

        function closeMobileSearch() {
            if (mobileSearchBar) {
                mobileSearchBar.classList.remove('open');
                if (mobileSearchToggleBtn) mobileSearchToggleBtn.classList.remove('active');
            }
        }

        if (mobileSearchToggleBtn) {
            mobileSearchToggleBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                if (mobileSearchBar && mobileSearchBar.classList.contains('open')) {
                    closeMobileSearch();
                } else {
                    openMobileSearch();
                }
            });
        }

        if (mobileSearchCloseBtn) {
            mobileSearchCloseBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                closeMobileSearch();
            });
        }

        // إغلاق شريط البحث عند النقر في أي مكان خارجه
        document.addEventListener('click', function (e) {
            if (mobileSearchBar && mobileSearchBar.classList.contains('open')) {
                if (!mobileSearchBar.contains(e.target) && (!mobileSearchToggleBtn || !mobileSearchToggleBtn.contains(e.target))) {
                    closeMobileSearch();
                }
            }
        });

        // دعم مفتاح Escape وإغلاق البحث
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeMobileSearch();
            }
        });

        // إغلاق شريط بحث الجوال عند تكبير الشاشة لأكبر من 768px
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) {
                closeMobileSearch();
            }
        });

        // تفعيل البحث واختصار Ctrl+K
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                if (window.innerWidth < 768) {
                    openMobileSearch();
                } else {
                    const desktopSearchInput = document.getElementById('topbarSearchInput');
                    if (desktopSearchInput) desktopSearchInput.focus();
                }
            }
        });
    }

    /**
     * 4. إدارة القائمة المنبثقة لبطاقة المشرف (Profile Dropdown Menu)
     */
    function initProfileDropdown() {
        const profileBtn = document.getElementById('profileDropdownBtn');
        const profileMenu = document.getElementById('profileDropdownMenu');

        if (profileBtn && profileMenu) {
            profileBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                const isOpen = profileMenu.classList.contains('show');
                profileMenu.classList.toggle('show');
                profileBtn.classList.toggle('active');
                profileBtn.setAttribute('aria-expanded', !isOpen);
            });

            // إغلاق القائمة عند النقر في أي مكان خارجها
            document.addEventListener('click', function (e) {
                if (!profileMenu.contains(e.target) && !profileBtn.contains(e.target)) {
                    profileMenu.classList.remove('show');
                    profileBtn.classList.remove('active');
                    profileBtn.setAttribute('aria-expanded', 'false');
                }
            });

            // إغلاق القائمة عند الضغط على مفتاح Escape
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    profileMenu.classList.remove('show');
                    profileBtn.classList.remove('active');
                    profileBtn.setAttribute('aria-expanded', 'false');
                }
            });
        }
    }

    /**
     * 5. إدارة القوائم المنسدلة ثلاث نقاط في الجداول (Table Action Dropdowns - Floating Fixed Portal)
     */
    function initTableDropdowns() {
        function closeAllTableDropdowns() {
            document.querySelectorAll('.table-dropdown').forEach(dd => {
                dd.classList.remove('active');
                const m = dd.querySelector('.table-dropdown-menu');
                if (m) {
                    m.classList.remove('show', 'dropup');
                    m.style.position = '';
                    m.style.top = '';
                    m.style.bottom = '';
                    m.style.left = '';
                    m.style.right = '';
                    m.style.zIndex = '';
                }
            });
        }

        function positionTableDropdown(trigger, menu) {
            menu.style.position = 'fixed';
            menu.style.zIndex = '99999';
            menu.style.bottom = 'auto';

            const rect = trigger.getBoundingClientRect();
            const menuHeight = menu.offsetHeight || 160;
            const menuWidth = menu.offsetWidth || 180;
            const spaceBelow = window.innerHeight - rect.bottom;
            const spaceAbove = rect.top;

            // التوجيه الرأسي: فتح للأعلى إذا كانت المساحة السفلية ضيقة
            if (spaceBelow < menuHeight + 12 && spaceAbove > menuHeight + 12) {
                menu.classList.add('dropup');
                menu.style.top = Math.max(10, Math.round(rect.top - menuHeight - 4)) + 'px';
            } else {
                menu.classList.remove('dropup');
                menu.style.top = Math.round(rect.bottom + 4) + 'px';
            }

            // التوجيه الأفقي (RTL): محاذاة يسار القائمة وضمان بقائها داخل حدود النافذة
            let leftPos = Math.round(rect.left);
            if (leftPos + menuWidth > window.innerWidth - 12) {
                leftPos = window.innerWidth - menuWidth - 12;
            }
            if (leftPos < 12) {
                leftPos = 12;
            }
            menu.style.left = leftPos + 'px';
            menu.style.right = 'auto';
        }

        document.addEventListener('click', function (e) {
            const trigger = e.target.closest('.table-dropdown-trigger');

            if (trigger) {
                e.stopPropagation();
                const parentDropdown = trigger.closest('.table-dropdown');
                const menu = parentDropdown.querySelector('.table-dropdown-menu');
                const isAlreadyOpen = menu.classList.contains('show');

                closeAllTableDropdowns();

                if (!isAlreadyOpen) {
                    parentDropdown.classList.add('active');
                    menu.classList.add('show');
                    positionTableDropdown(trigger, menu);
                }
            } else {
                if (!e.target.closest('.table-dropdown-menu')) {
                    closeAllTableDropdowns();
                }
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeAllTableDropdowns();
            }
        });

        // إغلاق القوائم العائمة فوراً عند التمرير في أي حاوية أو تغيير حجم النافذة
        window.addEventListener('scroll', closeAllTableDropdowns, true);
        window.addEventListener('resize', closeAllTableDropdowns);
    }

    /**
     * 6. تفويض أزرار الإشعار السريع عبر سمة data-toast (Zero inline onclick)
     */
    function initDataToasts() {
        document.addEventListener('click', function (e) {
            const toastBtn = e.target.closest('[data-toast]');
            if (toastBtn && window.notyf) {
                const message = toastBtn.getAttribute('data-toast');
                const type = toastBtn.getAttribute('data-toast-type') || 'success';
                if (type === 'error') {
                    window.notyf.error(message);
                } else {
                    window.notyf.success(message);
                }
            }
        });
    }

    /**
     * 7. إدارة تأكيد العمليات والحذف عبر سمة data-confirm (Zero inline onsubmit)
     */
    function initDataConfirms() {
        document.addEventListener('submit', function (e) {
            const form = e.target.closest('form[data-confirm]');
            if (form) {
                const message = form.getAttribute('data-confirm') || 'هل أنت متأكد من تنفيذ هذا الإجراء؟';
                if (!confirm(message)) {
                    e.preventDefault();
                }
            }
        });
    }

    // تهيئة كافة المحركات فور جاهزية الـ DOM
    document.addEventListener('DOMContentLoaded', function () {
        initThemeToggle();
        initSidebarDrawer();
        initMobileSearch();
        initProfileDropdown();
        initTableDropdowns();
        initDataToasts();
        initDataConfirms();
    });
})();
