@extends('layouts.app')

@section('title', 'تفاصيل الفاتورة - ' . ($payment->tenant?->name ?? 'معاملة مالية'))

@section('breadcrumbs')
    <span>/</span>
    <a href="{{ route('payments.index') }}">المدفوعات</a>
    <span>/</span>
    <span class="current">تفاصيل الفاتورة</span>
@endsection

@section('content')
<!-- شريط عنوان الصفحة الرسمي وإجراءات المعاملة -->
<div class="page-title-bar">
    <div>
        <div class="page-title-with-status">
            <h1>فاتورة سداد: {{ $payment->tenant?->name ?? 'معاملة مالية' }}</h1>
            <span class="status-pill {{ $payment->status }}">
                <span class="status-dot"></span>
                <span>{{ $payment->statusLabelArabic() }}</span>
            </span>
        </div>
    </div>
    <div class="table-actions">
        <a href="{{ route('payments.index') }}" class="btn-secondary">
            <i class="ri-arrow-right-line"></i>
            <span>العودة للمدفوعات</span>
        </a>
        @if($payment->tenant)
            <a href="{{ route('tenants.show', $payment->tenant) }}" class="btn-secondary">
                <i class="ri-building-line"></i>
                <span>ملف المنشأة</span>
            </a>
        @endif
        @if($payment->receipt_url)
            <a href="{{ $payment->receipt_url }}" target="_blank" rel="noopener noreferrer" class="btn-action-primary">
                <i class="ri-external-link-line"></i>
                <span>إيصال Stripe الرسمي</span>
            </a>
        @endif
    </div>
</div>

<!-- لوحة تفاصيل الفاتورة والمعاملة الموحدة في كارد واحد متماسك -->
<div class="card-panel">
    <!-- 1. القسم الأول: البيانات المالية الأساسية -->
    <div class="card-panel-header">
        <div class="card-panel-title">
            <i class="ri-money-dollar-circle-line"></i>
            <span>البيانات المالية الأساسية</span>
        </div>
    </div>
    <div class="info-grid">
        <div class="info-block">
            <span class="info-label">المبلغ المسدد</span>
            <div class="info-val" style="font-size: 1.25rem; font-weight: 700; color: var(--color-text-main);">
                {{ $payment->formattedAmount() }}
            </div>
        </div>
        <div class="info-block">
            <span class="info-label">حالة السداد</span>
            <div class="info-val">
                <span class="status-pill {{ $payment->status }}">
                    <span class="status-dot"></span>
                    <span>{{ $payment->statusLabelArabic() }}</span>
                </span>
            </div>
        </div>
        <div class="info-block">
            <span class="info-label">دورة الفوترة</span>
            <div class="info-val">
                <span class="trend-badge trend-stable">
                    <i class="ri-calendar-line"></i>
                    <span>{{ $payment->billingCycleArabic() }}</span>
                </span>
            </div>
        </div>
        <div class="info-block">
            <span class="info-label">تاريخ ووقت السداد الفعلي</span>
            <div class="info-val date-cell">
                {{ $payment->paid_at ? $payment->paid_at->format('Y/m/d - H:i:s') : ($payment->created_at ? $payment->created_at->format('Y/m/d - H:i:s') : '—') }}
            </div>
        </div>
    </div>

    <!-- فاصل هندسي ناعم بين الأقسام -->
    <div style="height: 1px; background-color: var(--color-border); margin: 1.5rem 0 0.5rem 0;"></div>

    <!-- 2. القسم الثاني: بيانات بوابة الدفع -->
    <div class="card-panel-header" style="border-top: none; padding-top: 1rem;">
        <div class="card-panel-title">
            <i class="ri-bank-card-line"></i>
            <span>بيانات بوابة الدفع (Stripe Gateway)</span>
        </div>
    </div>
    <div class="info-grid">
        <div class="info-block">
            <span class="info-label">بوابة الدفع</span>
            <div class="info-val">
                <span class="trend-badge trend-stable">
                    <i class="ri-shield-check-line"></i>
                    <span>{{ ucfirst($payment->gateway) }}</span>
                </span>
            </div>
        </div>
        <div class="info-block">
            <span class="info-label">وسيلة السداد المستخدمة</span>
            <div class="info-val">
                <span class="trend-badge trend-stable">
                    <i class="ri-bank-card-fill"></i>
                    <span>{{ $payment->payment_method ? ucfirst($payment->payment_method) : 'بطاقة بنكية (Card)' }}</span>
                </span>
            </div>
        </div>
        <div class="info-block">
            <span class="info-label">رقم المرجع في Stripe (Payment Intent ID)</span>
            <div class="info-val">
                @if($payment->gateway_reference_id)
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <span class="ref-badge">
                            <span>{{ $payment->gateway_reference_id }}</span>
                            <button type="button" class="ref-copy-btn js-copy-ref" data-copy="{{ $payment->gateway_reference_id }}" title="نسخ المرجع">
                                <i class="ri-file-copy-line"></i>
                            </button>
                        </span>
                        @if($payment->receipt_url)
                            <a href="{{ $payment->receipt_url }}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.82rem; color: var(--color-primary); text-decoration: none; font-weight: 600;">
                                <i class="ri-external-link-line"></i>
                                <span>فتح السند</span>
                            </a>
                        @endif
                    </div>
                @else
                    <span class="text-muted">—</span>
                @endif
            </div>
        </div>
        <div class="info-block">
            <span class="info-label">العملة المسدد بها</span>
            <div class="info-val">
                <span class="trend-badge trend-stable">
                    <span>{{ $payment->currency }} (ريال سعودي)</span>
                </span>
            </div>
        </div>
    </div>

    <!-- فاصل هندسي ناعم بين الأقسام -->
    <div style="height: 1px; background-color: var(--color-border); margin: 1.5rem 0 0.5rem 0;"></div>

    <!-- 3. القسم الثالث: بيانات المنشأة المشتركة والاشتراك -->
    <div class="card-panel-header" style="border-top: none; padding-top: 1rem;">
        <div class="card-panel-title">
            <i class="ri-building-line"></i>
            <span>بيانات المنشأة المشتركة والاشتراك</span>
        </div>
    </div>
    <div class="info-grid">
        <div class="info-block">
            <span class="info-label">اسم المنشأة المشتركة</span>
            <div class="info-val">
                @if($payment->tenant)
                    <a href="{{ route('tenants.show', $payment->tenant) }}" style="color: var(--color-primary); text-decoration: none; font-weight: 600;">
                        {{ $payment->tenant->name }}
                    </a>
                @else
                    <span class="text-muted">—</span>
                @endif
            </div>
        </div>
        <div class="info-block">
            <span class="info-label">الباقة المشترك بها</span>
            <div class="info-val">
                <span class="trend-badge trend-stable">
                    <i class="ri-vip-diamond-line"></i>
                    <span>{{ $payment->plan?->name ?? '—' }}</span>
                </span>
            </div>
        </div>
        <div class="info-block">
            <span class="info-label">رقم السجل التجاري</span>
            <div class="info-val">
                <span class="code-badge">{{ $payment->tenant?->company_cr ?? '—' }}</span>
            </div>
        </div>
        <div class="info-block">
            <span class="info-label">البريد الإلكتروني للمنشأة</span>
            <div class="info-val">{{ $payment->tenant?->email ?? '—' }}</div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/payments.js') }}"></script>
@endsection
