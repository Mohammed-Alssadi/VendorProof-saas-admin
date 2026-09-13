@extends('layouts.app')

@section('title', 'تعديل بيانات المنشأة: ' . $tenant->name)

@section('breadcrumbs')
    <span>/</span>
    <a href="{{ route('tenants.index') }}">المشتركون</a>
    <span>/</span>
    <a href="{{ route('tenants.show', $tenant) }}">{{ $tenant->name }}</a>
    <span>/</span>
    <span class="current">تعديل السجل</span>
@endsection

@section('content')
<!-- شريط عنوان الصفحة -->
<div class="page-title-bar">
    <div>
        <h1>تعديل بيانات المنشأة</h1>
        <p>تحديث السجل الرسمي، تفاصيل الاتصال، وباقة الاشتراك المعتمدة في النظام</p>
    </div>
    <div>
        <a href="{{ route('tenants.show', $tenant) }}" class="btn-secondary">
            <i class="ri-arrow-right-line"></i>
            <span>العودة لملف المنشأة</span>
        </a>
    </div>
</div>

@php
    $currentPlanId = old('plan_id', $tenant->plan_id ?? $tenant->currentPlan()?->id);
@endphp

<!-- نموذج تعديل المنشأة -->
<div class="card-panel">
    <div class="card-panel-header">
        <div class="card-panel-title">
            <i class="ri-edit-line"></i>
            <span>تعديل السجل: {{ $tenant->name }}</span>
        </div>
        <span class="trend-badge trend-stable">
            <i class="ri-building-line"></i>
            <span>سجل منشأة معتمد</span>
        </span>
    </div>
    <div class="card-panel-body">
        <form action="{{ route('tenants.update', $tenant) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <!-- قسم البيانات الرسمية -->
                <div class="form-section-title">
                    <i class="ri-article-line"></i>
                    <span>البيانات الرسمية للمنشأة</span>
                </div>

                <div class="form-group">
                    <label for="editTenantName" class="form-label">
                        <span>اسم المنشأة أو الشركة</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="editTenantName" name="name" class="form-control" value="{{ old('name', $tenant->name) }}" required>
                </div>

                <div class="form-group">
                    <label for="editTenantSlug" class="form-label">
                        <span>المعرف البرمجي الفريد (Slug)</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="editTenantSlug" name="slug" class="form-control" value="{{ old('slug', $tenant->slug) }}" dir="ltr" required>
                    <span class="form-hint">أحرف إنجليزية صغيرة وأرقام وشرطات فقط.</span>
                </div>

                <div class="form-group">
                    <label for="editTenantCr" class="form-label">
                        <span>رقم السجل التجاري</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="editTenantCr" name="company_cr" class="form-control" value="{{ old('company_cr', $tenant->company_cr) }}" dir="ltr" required>
                </div>

                <div class="form-group">
                    <label for="editTenantEmail" class="form-label">
                        <span>البريد الإلكتروني المعتمد</span>
                        <span class="required">*</span>
                    </label>
                    <input type="email" id="editTenantEmail" name="email" class="form-control" value="{{ old('email', $tenant->email) }}" dir="ltr" required>
                </div>

                <div class="form-group">
                    <label for="editTenantPhone" class="form-label">
                        <span>رقم الهاتف المعتمد</span>
                        <span class="required">*</span>
                    </label>
                    <input type="tel" id="editTenantPhone" name="phone" class="form-control" value="{{ old('phone', $tenant->phone) }}" dir="ltr" required>
                </div>
            </div>

            <!-- قسم باقة الاستخدام والحالة -->
            <div class="form-section-title">
                <i class="ri-vip-crown-line"></i>
                <span>باقة الاستخدام وحالة الحساب</span>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="editTenantPlan" class="form-label">
                        <span>باقة المنشأة (الترقية الفورية)</span>
                        <span class="required">*</span>
                    </label>
                    <select id="editTenantPlan" name="plan_id" class="form-select" required>
                        @forelse($plans as $plan)
                            @php
                                $monthlyPrice = $plan->prices->firstWhere('billing_cycle', 'monthly')?->price ?? 0;
                                $yearlyPrice = $plan->prices->firstWhere('billing_cycle', 'yearly')?->price ?? 0;
                                $symbol = $plan->currency_symbol ?? 'ر.س';
                            @endphp
                            <option value="{{ $plan->id }}"
                                data-is-free="{{ $plan->isFree() ? '1' : '0' }}"
                                data-monthly-price="{{ number_format((float) $monthlyPrice, 2) }}"
                                data-yearly-price="{{ number_format((float) $yearlyPrice, 2) }}"
                                data-currency="{{ $symbol }}"
                                {{ $currentPlanId == $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }} {{ $plan->isFree() ? '(مجانية)' : '' }}
                            </option>
                        @empty
                            <option value="">لا توجد باقات معرفة</option>
                        @endforelse
                    </select>
                    <span class="form-hint">تعديل الباقة هنا يمنح المنشأة صلاحيات وحدود الباقة الجديدة فوراً.</span>
                </div>

                <div class="form-group">
                    <label for="editTenantBillingCycle" class="form-label">
                        <span>دورة الفوترة والتجديد</span>
                        <span class="required">*</span>
                    </label>
                    <select id="editTenantBillingCycle" name="billing_cycle" class="form-select">
                        <option value="monthly" {{ old('billing_cycle', $currentBillingCycle) === 'monthly' ? 'selected' : '' }}>شهرياً</option>
                        <option value="yearly" {{ old('billing_cycle', $currentBillingCycle) === 'yearly' ? 'selected' : '' }}>سنوياً</option>
                    </select>
                    <span class="form-hint" id="billingCycleHint">تحدد دورة الفوترة تاريخ نهاية الفترة وتجديد العقد تلقائياً.</span>
                </div>

                <div class="form-group">
                    <label for="editTenantStatus" class="form-label">
                        <span>حالة المنشأة في المنصة</span>
                        <span class="required">*</span>
                    </label>
                    <select id="editTenantStatus" name="status" class="form-select" required>
                        <option value="active" {{ old('status', $tenant->status) === 'active' ? 'selected' : '' }}>نشط (مفعل بالكامل)</option>
                        <option value="suspended" {{ old('status', $tenant->status) === 'suspended' ? 'selected' : '' }}>موقوف مؤقتاً</option>
                    </select>
                </div>
            </div>

            <!-- أزرار الإجراءات -->
            <div class="form-actions">
                <a href="{{ route('tenants.show', $tenant) }}" class="btn-secondary">إلغاء</a>
                <button type="submit" class="btn-action-primary">
                    <i class="ri-check-line"></i>
                    <span>حفظ التعديلات</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/tenant-billing-cycle.js') }}?v={{ filemtime(public_path('js/tenant-billing-cycle.js')) }}"></script>
@endsection
