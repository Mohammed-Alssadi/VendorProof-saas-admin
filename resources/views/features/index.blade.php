@extends('layouts.app')

@section('title', 'الميزات والحدود - معجم المنظومة')

@section('breadcrumbs')
    <span>/</span>
    <span class="current">الميزات والحدود</span>
@endsection

@section('content')
<!-- شريط عنوان الصفحة -->
<div class="page-title-bar">
    <div>
        <h1>الميزات والحدود التشغيلية</h1>
        <p>المعجم المركزي لكافة الميزات والحدود البرمجية التي تحكم باقات المنصة واستهلاك المشتركين</p>
    </div>
    <div>
        <a href="{{ route('features.create') }}" class="btn-action-primary">
            <i class="ri-add-line"></i>
            <span>إضافة ميزة جديدة</span>
        </a>
    </div>
</div>

<!-- جدول الميزات والحدود المؤسسي -->
<div class="card-panel">
    <!-- شريط الفلترة والبحث -->
    <div class="table-toolbar">
        <div class="table-filters">
            <div class="filter-search-box">
                <i class="ri-search-line"></i>
                <input type="text" id="featureSearchInput" class="filter-search-input" placeholder="بحث باسم الميزة أو الكود البرمجي...">
            </div>
            <select id="featureTypeFilter" class="filter-select">
                <option value="all">كافة الأنواع</option>
                <option value="limit">حد عددي (Limit)</option>
                <option value="feature">ميزة تشغيلية (Feature)</option>
            </select>
        </div>
        <div>
            <span class="trend-badge trend-stable">
                <i class="ri-equalizer-line"></i>
                <span>إجمالي الميزات: {{ count($features) }}</span>
            </span>
        </div>
    </div>

    <!-- جدول البيانات -->
    <div class="table-responsive">
        <table class="data-table" id="featuresDataTable">
            <thead>
                <tr>
                    <th>اسم الميزة</th>
                    <th>الرمز البرمجي</th>
                    <th>النوع</th>
                    <th>الباقات المرتبطة</th>
                    <th>الإجراء</th>
                </tr>
            </thead>
            <tbody>
                @forelse($features as $feature)
                    <tr data-type="{{ $feature->type }}">
                        <td>
                            <div class="subscriber-name">{{ $feature->name }}</div>
                        </td>
                        <td class="price-cell">
                            <span dir="ltr">{{ $feature->key }}</span>
                        </td>
                        <td class="price-cell">
                            <span>{{ $feature->type === 'limit' ? 'حد عددي' : 'ميزة تشغيلية' }}</span>
                        </td>
                        <td class="price-cell">
                            {{ $feature->plans_count ?? 0 }} باقات
                        </td>
                        <td>
                            <div class="table-dropdown">
                                <button type="button" class="table-dropdown-trigger" title="خيارات الميزة" aria-haspopup="true">
                                    <i class="ri-more-2-fill"></i>
                                </button>
                                <div class="table-dropdown-menu">
                                    <a href="{{ route('features.edit', $feature) }}" class="table-dropdown-item">
                                        <i class="ri-edit-line"></i>
                                        <span>تعديل الميزة</span>
                                    </a>
                                    <form action="{{ route('features.destroy', $feature) }}" method="POST" class="table-dropdown-form" data-confirm="هل أنت متأكد من رغبتك في حذف هذه الميزة من المنظومة؟">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="table-dropdown-item danger">
                                            <i class="ri-delete-bin-line"></i>
                                            <span>حذف الميزة</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="table-empty-row">
                        <td colspan="5">
                            <div class="table-empty-wrap">
                                <div class="empty-icon-box">
                                    <i class="ri-toggle-line"></i>
                                </div>
                                <h4 class="empty-title">لا توجد ميزات أو حدود مسجلة</h4>
                                <p class="empty-desc">لم يتم تعريف أي ميزات أو حدود تشغيلية للباقات في النظام حتى الآن. ابدأ بإنشاء أول ميزة وربطها بباقات الاشتراك.</p>
                                <div class="empty-actions">
                                    <a href="{{ route('features.create') }}" class="btn-action-primary">
                                        <i class="ri-add-line"></i>
                                        <span>إضافة أول ميزة</span>
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- تذييل الجدول -->
    <div class="table-footer">
        <div>عرض {{ count($features) }} ميزات معرفة في النظام</div>
        <div class="table-pagination">
            <button type="button" class="pagination-btn active">1</button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/table-filters.js') }}?v={{ filemtime(public_path('js/table-filters.js')) }}"></script>
@endsection

