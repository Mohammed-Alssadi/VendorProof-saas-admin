@extends('layouts.app')

@section('title', $tenant->name . ' - تفاصيل المنشأة')

@section('breadcrumbs')
    <span>/</span>
    <a href="{{ route('tenants.index') }}">المشتركون</a>
    <span>/</span>
    <span class="current">{{ $tenant->name }}</span>
@endsection

@section('content')
<!-- شريط عنوان الصفحة الرسمي وإجراءات المنشأة -->
<div class="page-title-bar">
    <div>
        <div class="page-title-with-status">
            <h1>{{ $tenant->name }}</h1>
            <span class="status-pill {{ $tenant->status }}">
                <span class="status-dot"></span>
                <span>{{ $tenant->status === 'active' ? 'نشط' : 'معلق' }}</span>
            </span>
        </div>
    </div>
    <div class="table-actions">
        <a href="{{ route('tenants.index') }}" class="btn-secondary">
            <i class="ri-arrow-right-line"></i>
            <span>العودة للمشتركين</span>
        </a>
        <a href="{{ route('tenants.edit', $tenant) }}" class="btn-action-primary">
            <i class="ri-edit-line"></i>
            <span>تعديل السجل</span>
        </a>
        <form action="{{ route('tenants.toggle-status', $tenant) }}" method="POST" class="inline-form">
            @csrf
            <button type="submit" class="btn-secondary">
                <i class="ri-pause-circle-line"></i>
                <span>{{ $tenant->status === 'active' ? 'تعليق الحساب' : 'تنشيط الحساب' }}</span>
            </button>
        </form>
    </div>
</div>

<!-- 1. لوحة البيانات الرسمية للمنشأة -->
<div class="card-panel">
    <div class="card-panel-header">
        <div class="card-panel-title">
            <i class="ri-building-line"></i>
            <span>البيانات الرسمية للمنشأة</span>
        </div>
    </div>
    <div class="info-grid">
        <div class="info-block">
            <span class="info-label">اسم المنشأة الرسمي</span>
            <div class="info-val">{{ $tenant->name }}</div>
        </div>
        <div class="info-block">
            <span class="info-label">رقم السجل التجاري</span>
            <div class="info-val">
                <span class="code-badge">{{ $tenant->company_cr ?? '—' }}</span>
            </div>
        </div>
        <div class="info-block">
            <span class="info-label">البريد الإلكتروني المعتمد</span>
            <div class="info-val">{{ $tenant->email }}</div>
        </div>
        <div class="info-block">
            <span class="info-label">رقم الهاتف الرسمي</span>
            <div class="info-val">
                <bdi dir="ltr">{{ $tenant->phone ?? '—' }}</bdi>
            </div>
        </div>
        <div class="info-block">
            <span class="info-label">تاريخ الانضمام للنظام</span>
            <div class="info-val">{{ $tenant->created_at?->format('Y/m/d') ?? '—' }}</div>
        </div>
        <div class="info-block">
            <span class="info-label">حالة الحساب الإدارية</span>
            <div class="info-val">
                <span class="status-pill {{ $tenant->status }}">
                    <span class="status-dot"></span>
                    <span>{{ $tenant->status === 'active' ? 'نشط' : 'معلق' }}</span>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- 2. لوحة باقة الاستخدام والصلاحيات -->
<div class="card-panel">
    <div class="card-panel-header">
        <div class="card-panel-title">
            <i class="ri-vip-crown-line"></i>
            <span>باقة الاستخدام والصلاحيات</span>
        </div>
        <div>
            <a href="{{ route('tenants.edit', $tenant) }}" class="btn-secondary btn-sm">
                <i class="ri-arrow-up-circle-line"></i>
                <span>ترقية / تغيير الباقة</span>
            </a>
        </div>
    </div>
    <div class="info-grid">
        <div class="info-block">
            <span class="info-label">باقة المنشأة الحالية</span>
            <div class="info-val">
                <span class="trend-badge trend-stable">
                    <span>{{ $tenant->currentPlan()?->name ?? 'الباقة المجانية' }}</span>
                </span>
            </div>
        </div>
        <div class="info-block">
            <span class="info-label">طبيعة الباقة والفوترة</span>
            <div class="info-val">
                @if($tenant->currentPlan()?->isFree())
                    <span>دائمة (Freemium)</span>
                @else
                    <span>مدفوعة ({{ $tenant->currentSubscription?->planPrice?->billing_cycle === 'yearly' ? 'سنوياً' : 'شهرياً' }})</span>
                @endif
            </div>
        </div>
        <div class="info-block">
            <span class="info-label">تاريخ التجديد / نهاية الفترة</span>
            <div class="info-val">
                {{ $tenant->currentSubscription?->current_period_end ? $tenant->currentSubscription->current_period_end->format('Y/m/d') : 'دائم (غير محدد)' }}
            </div>
        </div>
        <div class="info-block">
            <span class="info-label">سعة الموردين المتاحة</span>
            <div class="info-val">{{ $tenant->currentPlan()?->getLimitDisplay('max_vendors') ?? '5' }}</div>
        </div>
        <div class="info-block">
            <span class="info-label">سعة الوثائق والشهادات</span>
            <div class="info-val">{{ $tenant->currentPlan()?->getLimitDisplay('max_certificates') ?? '10' }}</div>
        </div>
    </div>
</div>

<!-- 3. لوحة المستخدمين المصرح لهم بالمنشأة -->
<div class="card-panel">
    <div class="card-panel-header">
        <div>
            <div class="card-panel-title">المستخدمون المصرح لهم بالمنشأة</div>
            <div class="card-panel-subtitle">المسؤولون والموظفون المتاح لهم الوصول لإدارة حساب المنشأة</div>
        </div>
        <span class="trend-badge trend-stable">
            <i class="ri-team-line"></i>
            <span>{{ $tenant->users->count() }} مستخدمين مسجلين</span>
        </span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>المستخدم</th>
                    <th>البريد الإلكتروني</th>
                    <th>توثيق البريد الإلكتروني</th>
                    <th>تاريخ التسجيل</th>
                    <th>حالة الحساب</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenant->users as $user)
                    <tr>
                        <td>
                            <div class="subscriber-cell">
                                <div class="subscriber-avatar">{{ mb_substr($user->name, 0, 1) }}</div>
                                <div class="subscriber-name">{{ $user->name }}</div>
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->email_verified_at)
                                <span class="status-pill active">
                                    <span class="status-dot"></span>
                                    <span>موثق ({{ $user->email_verified_at->format('Y/m/d') }})</span>
                                </span>
                            @else
                                <span class="status-pill pending">
                                    <span class="status-dot"></span>
                                    <span>بانتظار التوثيق</span>
                                </span>
                            @endif
                        </td>
                        <td class="date-cell">{{ $user->created_at?->format('Y/m/d') ?? '—' }}</td>
                        <td>
                            <span class="status-pill active">
                                <span class="status-dot"></span>
                                <span>{{ $user->status ?? 'نشط' }}</span>
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr class="table-empty-row">
                        <td colspan="5">
                            <div class="table-empty-wrap">
                                <div class="empty-icon-box">
                                    <i class="ri-user-line"></i>
                                </div>
                                <h4 class="empty-title">لا يوجد مستخدمون مسجلون لهذه المنشأة</h4>
                                <p class="empty-desc">لم يتم ربط أي مستخدمين أو مدراء تشغيليين بحساب هذه المنشأة حتى الآن.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
