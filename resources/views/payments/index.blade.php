@extends('layouts.app')

@section('title', 'المدفوعات - سجل العمليات والتحصيلات')

@section('breadcrumbs')
    <span>/</span>
    <span class="current">المدفوعات</span>
@endsection

@section('content')
<!-- شريط عنوان الصفحة -->
<div class="page-title-bar">
    <div>
        <h1>المدفوعات</h1>
        <p>سجل العمليات المالية والتحصيلات، فواتير الاشتراكات، ومتابعة بوابات الدفع الإلكتروني</p>
    </div>
    <div>
        <span class="table-inline-stats">
            <span>إجمالي التحصيلات:</span>
            <strong>{{ number_format((float) $totalRevenue, 2) }} ر.س</strong>
            <span>&bull;</span>
            <span>العمليات المسددة:</span>
            <strong>{{ $paidCount }}</strong>
        </span>
    </div>
</div>

<!-- بطاقة جدول سجل المدفوعات -->
<div class="card-panel">
    <!-- شريط الفلترة والبحث اللحظي (Client-Side Filtering Engine) -->
    <div class="table-toolbar">
        <div class="table-filters">
            <div class="filter-search-box">
                <i class="ri-search-line"></i>
                <input type="text" id="paymentSearchInput" class="filter-search-input" placeholder="بحث بالمرجع، رقم المعاملة، أو اسم المنشأة...">
            </div>

            <select id="paymentStatusFilter" class="filter-select">
                <option value="all">كافة الحالات</option>
                <option value="paid">مسددة</option>
                <option value="pending">بانتظار السداد</option>
                <option value="failed">تعثر السداد</option>
                <option value="refunded">مستردة</option>
            </select>

            <select id="paymentCycleFilter" class="filter-select">
                <option value="all">دورة الفوترة (الكل)</option>
                <option value="monthly">شهري</option>
                <option value="yearly">سنوي</option>
            </select>
        </div>

        <div>
            <span class="trend-badge trend-stable">
                <i class="ri-bank-card-line"></i>
                <span>إجمالي العمليات: {{ $payments->total() }}</span>
            </span>
        </div>
    </div>

    <!-- جدول البيانات المالي -->
    <div class="table-responsive">
        <table class="data-table" id="paymentsDataTable">
            <thead>
                <tr>
                    <th>المرجع / المعاملة</th>
                    <th>المنشأة</th>
                    <th>الباقة والفوترة</th>
                    <th>المبلغ المسدد</th>
                    <th>بوابة الدفع</th>
                    <th>الحالة</th>
                    <th>تاريخ السداد</th>
                    <th class="th-actions">الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr data-status="{{ $payment->status }}" data-cycle="{{ $payment->billing_cycle }}">
                        <!-- 1. المرجع / المعاملة -->
                        <td>
                            @if($payment->gateway_reference_id)
                                <span class="ref-badge" title="{{ $payment->gateway_reference_id }}">
                                    <span>{{ \Illuminate\Support\Str::limit($payment->gateway_reference_id, 16) }}</span>
                                    <button type="button" 
                                            class="ref-copy-btn js-copy-ref" 
                                            data-copy="{{ $payment->gateway_reference_id }}" 
                                            title="نسخ المعرف">
                                        <i class="ri-file-copy-line"></i>
                                    </button>
                                </span>
                            @else
                                <span class="ref-badge">
                                    <span>#INV-{{ $payment->created_at ? $payment->created_at->format('ymd') : '00' }}</span>
                                </span>
                            @endif
                        </td>

                        <!-- 2. المنشأة (اسم نقي وأفتار فقط دون حشو) -->
                        <td>
                            <div class="subscriber-cell">
                                <div class="subscriber-avatar">{{ mb_substr($payment->tenant?->name ?? '—', 0, 1) }}</div>
                                <div class="subscriber-name">{{ $payment->tenant?->name ?? '—' }}</div>
                            </div>
                        </td>

                        <!-- 3. الباقة والفوترة -->
                        <td>
                            <span class="trend-badge trend-stable">
                                <i class="ri-vip-diamond-line"></i>
                                <span>{{ $payment->plan?->name ?? 'باقة النظام' }} ({{ $payment->billingCycleArabic() }})</span>
                            </span>
                        </td>

                        <!-- 4. المبلغ المسدد -->
                        <td>
                            <span class="payment-amount">{{ $payment->formattedAmount() }}</span>
                        </td>

                        <!-- 5. بوابة الدفع -->
                        <td>
                            <span class="trend-badge trend-stable">
                                <i class="ri-bank-card-line"></i>
                                <span>{{ ucfirst($payment->gateway) }}</span>
                            </span>
                        </td>

                        <!-- 6. الحالة (نقطة دائرية هادئة فقط دون أي إطار أو بيدج) -->
                        <td>
                            <span class="status-pill {{ $payment->status }}">
                                <span class="status-dot"></span>
                                <span>{{ $payment->statusLabelArabic() }}</span>
                            </span>
                        </td>

                        <!-- 7. تاريخ السداد -->
                        <td class="date-cell">
                            {{ $payment->paid_at ? $payment->paid_at->format('Y/m/d - H:i') : ($payment->created_at ? $payment->created_at->format('Y/m/d - H:i') : '—') }}
                        </td>

                        <!-- 8. الإجراءات (نظام القوائم المنسدلة ثلاث نقاط الموحد) -->
                        <td class="td-actions">
                            <div class="table-dropdown">
                                <button type="button" class="table-dropdown-trigger" aria-label="قائمة العمليات">
                                    <i class="ri-more-2-fill"></i>
                                </button>
                                <div class="table-dropdown-menu">
                                    <a href="{{ route('payments.show', $payment) }}" class="table-dropdown-item">
                                        <i class="ri-file-list-3-line"></i>
                                        <span>عرض تفاصيل المعاملة</span>
                                    </a>
                                    @if($payment->tenant)
                                        <a href="{{ route('tenants.show', $payment->tenant) }}" class="table-dropdown-item">
                                            <i class="ri-building-line"></i>
                                            <span>عرض ملف المنشأة</span>
                                        </a>
                                    @endif
                                    @if($payment->receipt_url)
                                        <a href="{{ $payment->receipt_url }}" target="_blank" rel="noopener noreferrer" class="table-dropdown-item">
                                            <i class="ri-external-link-line"></i>
                                            <span>إيصال Stripe الإلكتروني</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="table-empty-row">
                        <td colspan="8">
                            <div class="table-empty-wrap">
                                <div class="empty-icon-box">
                                    <i class="ri-bank-card-line"></i>
                                </div>
                                <h4 class="empty-title">لا توجد عمليات دفع مسجلة</h4>
                                <p class="empty-desc">لم يتم تسجيل أي عمليات تحصيل مالي في قاعدة البيانات حتى الآن. ستظهر العمليات والفواتير تلقائياً فور سداد المنشآت لاشتراكاتها عبر بوابة الدفع.</p>
                                <div class="empty-actions">
                                    <a href="{{ route('subscriptions.index') }}" class="btn-action-primary">
                                        <i class="ri-file-list-3-line"></i>
                                        <span>استعراض سجل الاشتراكات</span>
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->total() > 0)
        <!-- تذييل الجدول والترقيم المطابق لصفحة الاشتراكات -->
        <div class="table-footer">
            <div>عرض {{ $payments->count() }} من أصل {{ $payments->total() }} عملية مسجلة</div>
            @if($payments->hasPages())
                <div class="table-pagination">
                    {{ $payments->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/table-filters.js') }}?v={{ filemtime(public_path('js/table-filters.js')) }}"></script>
@endsection
