@extends('layouts.app')

@section('title', 'الاشتراكات - سجلات العقود والفوترة')

@section('breadcrumbs')
    <span>/</span>
    <span class="current">سجلات الاشتراكات</span>
@endsection

@section('content')
<!-- شريط عنوان الصفحة -->
<div class="page-title-bar">
    <div>
        <h1>سجل المشتركين والباقات</h1>
        <p>متابعة وتدقيق عقود اشتراكات المنشآت، دورات الفوترة، وقيم الرسوم، وتواريخ الفترات والتجديد</p>
    </div>
    <div>
        <a href="{{ route('tenants.create') }}" class="btn-action-primary">
            <i class="ri-user-add-line"></i>
            <span>تسجيل منشأة جديدة</span>
        </a>
    </div>
</div>

<!-- بطاقة جدول سجلات الاشتراكات -->
<div class="card-panel">
    <!-- شريط الفلترة والبحث -->
    <div class="table-toolbar">
        <div class="table-filters">
            <div class="filter-search-box">
                <i class="ri-search-line"></i>
                <input type="text" id="subSearchInput" class="filter-search-input" placeholder="بحث باسم المنشأة أو الباقة...">
            </div>
            <select id="subStatusFilter" class="filter-select">
                <option value="all">كافة الحالات</option>
                <option value="active">نشط</option>
                <option value="canceled">ملغي</option>
            </select>
        </div>
        <div>
            <span class="trend-badge trend-stable">
                <i class="ri-file-list-3-line"></i>
                <span>إجمالي المشتركين: {{ $subscribers->total() }}</span>
            </span>
        </div>
    </div>

    <!-- جدول البيانات -->
    <div class="table-responsive">
        <table class="data-table" id="subscriptionsDataTable">
            <thead>
                <tr>
                    <th>المنشأة / المشترك</th>
                    <th>الباقة المعتمدة</th>
                    <th>الفوترة والرسوم</th>
                    <th>الحالة</th>
                    <th>تاريخ البدء</th>
                    <th>نهاية الفترة / التجديد</th>
                    <th class="th-actions">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscribers as $subscriber)
                    <tr data-status="{{ $subscriber->status }}">
                        <td>
                            <div class="subscriber-cell">
                                <div class="subscriber-avatar">{{ mb_substr($subscriber->name ?? '—', 0, 1) }}</div>
                                <div class="subscriber-name">{{ $subscriber->name ?? '—' }}</div>
                            </div>
                        </td>
                        <td>
                            <span class="trend-badge trend-stable">
                                <i class="ri-vip-diamond-line"></i>
                                <span>{{ $subscriber->plan?->name ?? 'الباقة المجانية' }}</span>
                            </span>
                        </td>
                        <td class="price-cell">
                            @php
                                $price = $subscriber->currentSubscription?->planPrice;
                            @endphp
                            @if($price && (float) $price->price > 0)
                                <span>{{ number_format($price->price, 2) }} {{ $price->currency_symbol }}</span>
                                <span class="text-muted" style="font-size: 0.8rem;">/ {{ $price->billing_cycle === 'yearly' ? 'سنوياً' : 'شهرياً' }}</span>
                            @else
                                <span>مجاني (0.00 ر.س)</span>
                            @endif
                        </td>
                        <td>
                            <span class="status-pill {{ $subscriber->status }}">
                                <span class="status-dot"></span>
                                <span>{{ $subscriber->status === 'active' ? 'نشط' : 'معلق' }}</span>
                            </span>
                        </td>
                        <td class="date-cell">{{ $subscriber->currentSubscription?->starts_at?->format('Y/m/d') ?? $subscriber->created_at?->format('Y/m/d') ?? '—' }}</td>
                        <td class="date-cell">
                            {{ $subscriber->currentSubscription?->current_period_end ? $subscriber->currentSubscription->current_period_end->format('Y/m/d') : 'دائم (غير محدد)' }}
                        </td>
                        <td class="text-center">
                            <div class="table-dropdown">
                                <button type="button" class="table-dropdown-trigger" aria-label="قائمة العمليات">
                                    <i class="ri-more-2-fill"></i>
                                </button>
                                <div class="table-dropdown-menu">
                                    <a href="{{ route('tenants.show', $subscriber) }}" class="table-dropdown-item">
                                        <i class="ri-building-line"></i>
                                        <span>عرض ملف المنشأة</span>
                                    </a>
                                    <a href="{{ route('tenants.edit', $subscriber) }}" class="table-dropdown-item">
                                        <i class="ri-edit-line"></i>
                                        <span>تعديل الباقة والبيانات</span>
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="table-empty-row">
                        <td colspan="7">
                            <div class="table-empty-wrap">
                                <div class="empty-icon-box">
                                    <i class="ri-file-list-line"></i>
                                </div>
                                <h4 class="empty-title">لا يوجد مشتركون مسجلون حالياً</h4>
                                <p class="empty-desc">لم تسجل أي منشأة في المنصة حتى الآن. سيبدأ ظهور المشتركين فور قيامهم بالتسجيل الذاتي أو عند إضافتهم من قبل الإدارة.</p>
                                <div class="empty-actions">
                                    <a href="{{ route('tenants.create') }}" class="btn-action-primary">
                                        <i class="ri-user-add-line"></i>
                                        <span>تسجيل منشأة جديدة</span>
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($subscribers->total() > 0)
    <!-- تذييل الجدول والترقيم -->
    <div class="table-footer">
        <div>عرض {{ $subscribers->count() }} من أصل {{ $subscribers->total() }} مشترك مسجل</div>
        @if($subscribers->hasPages())
        <div class="table-pagination">
            {{ $subscribers->links() }}
        </div>
        @endif
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/table-filters.js') }}?v={{ filemtime(public_path('js/table-filters.js')) }}"></script>
@endsection

