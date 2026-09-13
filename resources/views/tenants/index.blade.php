@extends('layouts.app')

@section('title', 'المشتركون - إدارة المنشآت')

@section('breadcrumbs')
    <span>/</span>
    <span class="current">المشتركون</span>
@endsection

@section('content')
<!-- شريط عنوان الصفحة وزر الإجراء الأساسي -->
<div class="page-title-bar">
    <div>
        <h1>إدارة المشتركين والمنشآت</h1>
        <p>متابعة وتدقيق الشركات المسجلة، السجلات التجارية، وحالات الامتثال والاشتراكات السحابية</p>
    </div>
    <div>
        <a href="{{ route('tenants.create') }}" class="btn-action-primary">
            <i class="ri-user-add-line"></i>
            <span>إضافة مشترك جديد</span>
        </a>
    </div>
</div>

<!-- لوحة جدول المشتركين المؤسسية -->
<div class="card-panel">
    <!-- شريط الفلترة والبحث -->
    <div class="table-toolbar">
        <div class="table-filters">
            <div class="filter-search-box">
                <i class="ri-search-line"></i>
                <input type="text" id="tenantSearchInput" class="filter-search-input" placeholder="بحث باسم المنشأة، السجل التجاري، أو البريد...">
            </div>
            <select id="statusFilter" class="filter-select">
                <option value="all">جميع الحالات</option>
                <option value="active">نشط</option>
                <option value="trial">تجربة مجانية</option>
                <option value="pending">بانتظار التجديد</option>
                <option value="canceled">ملغي</option>
            </select>
            <select id="cycleFilter" class="filter-select">
                <option value="all">كافة دورات الفوترة</option>
                <option value="سنوي">سنوي</option>
                <option value="شهري">شهري</option>
            </select>
        </div>
        <div>
            <span class="trend-badge trend-stable">
                <span>إجمالي المنشآت: {{ method_exists($tenants, 'total') ? $tenants->total() : count($tenants) }}</span>
                <i class="ri-building-line"></i>
            </span>
        </div>
    </div>

    <!-- جدول البيانات -->
    <div class="table-responsive">
        <table class="data-table" id="tenantsDataTable">
            <thead>
                <tr>
                    <th>الشركة / المنشأة</th>
                    <th>الخطة</th>
                    <th>الفوترة</th>
                    <th>حالة الاشتراك</th>
                    <th>تاريخ الانضمام</th>
                    <th class="th-actions">العمليات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $tenant)
                    <tr data-status="{{ $tenant->status }}">
                        <td>
                            <div class="subscriber-cell">
                                <div class="subscriber-avatar">{{ mb_substr($tenant->name, 0, 1) }}</div>
                                <div class="subscriber-name">{{ $tenant->name }}</div>
                            </div>
                        </td>
                        <td class="price-cell">
                            <span class="trend-badge trend-stable">
                                <span>{{ $tenant->plan?->name ?? 'الباقة المجانية' }}</span>
                            </span>
                        </td>
                        <td>
                            <span class="trend-badge trend-stable">
                                <span>{{ $tenant->plan?->isFree() ? 'دائم (Freemium)' : 'مفعل' }}</span>
                            </span>
                        </td>
                        <td>
                            <span class="status-pill {{ $tenant->status }}">
                                <span class="status-dot"></span>
                                <span>{{ $tenant->status === 'active' ? 'نشط' : 'معلق' }}</span>
                            </span>
                        </td>
                        <td class="date-cell">
                            <div>{{ $tenant->created_at?->format('Y/m/d') ?? '—' }}</div>
                        </td>
                        <td class="text-center">
                            <div class="table-dropdown">
                                <button type="button" class="table-dropdown-trigger" title="خيارات المنشأة" aria-haspopup="true">
                                    <i class="ri-more-2-fill"></i>
                                </button>
                                <div class="table-dropdown-menu">
                                    <a href="{{ route('tenants.show', $tenant) }}" class="table-dropdown-item">
                                        <i class="ri-eye-line"></i>
                                        <span>مشاهدة تفاصيل الشركة</span>
                                    </a>
                                    <a href="{{ route('tenants.edit', $tenant) }}" class="table-dropdown-item">
                                        <i class="ri-edit-line"></i>
                                        <span>تعديل بيانات المنشأة</span>
                                    </a>
                                    <form action="{{ route('tenants.toggle-status', $tenant) }}" method="POST" class="table-dropdown-form">
                                        @csrf
                                        <button type="submit" class="table-dropdown-item {{ $tenant->status === 'active' ? 'danger' : '' }}">
                                            <i class="{{ $tenant->status === 'active' ? 'ri-pause-circle-line' : 'ri-play-circle-line' }}"></i>
                                            <span>{{ $tenant->status === 'active' ? 'تعطيل المنشأة' : 'تفعيل المنشأة' }}</span>
                                        </button>
                                    </form>
                                    <form action="{{ route('tenants.destroy', $tenant) }}" method="POST" class="table-dropdown-form" data-confirm="هل أنت متأكد من رغبتك في أرشفة وحذف سجل هذه المنشأة؟">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="table-dropdown-item danger">
                                            <i class="ri-delete-bin-line"></i>
                                            <span>أرشفة المنشأة</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="table-empty-row">
                        <td colspan="6">
                            <div class="table-empty-wrap">
                                <div class="empty-icon-box">
                                    <i class="ri-building-line"></i>
                                </div>
                                <h4 class="empty-title">لا توجد منشآت مسجلة في المنظومة</h4>
                                <p class="empty-desc">لم يتم تسجيل أي منشآت أو مشتركين في قاعدة البيانات حتى الآن. ابدأ بتسجيل أول منشأة لتفعيل خدماتها والبدء في فحص الامتثال.</p>
                                <div class="empty-actions">
                                    <a href="{{ route('tenants.create') }}" class="btn-action-primary">
                                        <i class="ri-add-line"></i>
                                        <span>إضافة أول منشأة</span>
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if((method_exists($tenants, 'total') ? $tenants->total() : count($tenants)) > 0)
    <!-- تذييل الجدول وترقيم الصفحات -->
    <div class="table-footer">
        <div class="table-footer-info">
            عرض <span>{{ method_exists($tenants, 'count') ? $tenants->count() : count($tenants) }}</span> من أصل <span>{{ method_exists($tenants, 'total') ? $tenants->total() : count($tenants) }}</span> منشأة مسجلة
        </div>
        @if(method_exists($tenants, 'hasPages') && $tenants->hasPages())
        <div class="pagination-controls">
            {{ $tenants->links() }}
        </div>
        @endif
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/table-filters.js') }}?v={{ filemtime(public_path('js/table-filters.js')) }}"></script>
@endsection
