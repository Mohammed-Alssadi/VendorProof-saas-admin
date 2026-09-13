@extends('layouts.app')

@section('title', 'إضافة مشترك جديد')

@section('breadcrumbs')
    <span>/</span>
    <a href="{{ route('tenants.index') }}">المشتركون</a>
    <span>/</span>
    <span class="current">إضافة مشترك جديد</span>
@endsection

@section('content')
<!-- شريط عنوان الصفحة -->
<div class="page-title-bar">
    <div>
        <h1>تسجيل منشأة جديدة</h1>
        <p>إدخال البيانات الرسمية للمنشأة، إعداد حساب المدير، وتعيين باقة الاشتراك</p>
    </div>
    <div>
        <a href="{{ route('tenants.index') }}" class="btn-secondary">
            <i class="ri-arrow-right-line"></i>
            <span>العودة لقائمة المشتركين</span>
        </a>
    </div>
</div>

<!-- نموذج تسجيل المنشأة -->
<div class="card-panel">
    <div class="card-panel-header">
        <div class="card-panel-title">
            <i class="ri-building-line"></i>
            <span>البيانات الأساسية واشتراك المنشأة</span>
        </div>
        <span class="trend-badge trend-stable">
            <i class="ri-shield-keyhole-line"></i>
            <span>سجل معتمد للمنصة</span>
        </span>
    </div>
    <div class="card-panel-body">
        <form action="{{ route('tenants.store') }}" method="POST">
            @csrf
            <div class="form-grid">
                <!-- 1. قسم بيانات المنشأة والسجل التجاري -->
                <div class="form-section-title">
                    <i class="ri-article-line"></i>
                    <span>بيانات المنشأة والسجل التجاري</span>
                </div>

                <div class="form-group">
                    <label for="tenantNameInput" class="form-label">
                        <span>اسم المنشأة أو الشركة</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="tenantNameInput" name="name" class="form-control" placeholder="مثال: شركة ركاز المتقدمة للتجارة" value="{{ old('name') }}" required>
                </div>

                <div class="form-group">
                    <label for="tenantSlugInput" class="form-label">
                        <span>المعرف البرمجي للمنشأة (Slug)</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="tenantSlugInput" name="slug" class="form-control" placeholder="مثال: rikaz-tech" value="{{ old('slug') }}" dir="ltr" required>
                    <span class="form-hint">يُستخدم كرابط وصول لمساحة عمل المنشأة (أحرف إنجليزية صغيرة، أرقام، وشرطات فقط).</span>
                </div>

                <div class="form-group">
                    <label for="tenantCrInput" class="form-label">
                        <span>رقم السجل التجاري (CR Number)</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="tenantCrInput" name="company_cr" class="form-control" placeholder="مثال: 1010884422" maxlength="20" value="{{ old('company_cr') }}" required>
                </div>

                <div class="form-group">
                    <label for="tenantEmailInput" class="form-label">
                        <span>البريد الإلكتروني الرسمي للمنشأة</span>
                        <span class="required">*</span>
                    </label>
                    <input type="email" id="tenantEmailInput" name="email" class="form-control" placeholder="contact@company.sa" value="{{ old('email') }}" dir="ltr" required>
                </div>

                <div class="form-group">
                    <label for="tenantPhoneInput" class="form-label">
                        <span>رقم الهاتف المعتمد</span>
                        <span class="required">*</span>
                    </label>
                    <input type="tel" id="tenantPhoneInput" name="phone" class="form-control" placeholder="0114567890" value="{{ old('phone') }}" dir="ltr" required>
                </div>

                <!-- 2. قسم بيانات حساب مدير المنشأة -->
                <div class="form-section-title">
                    <i class="ri-user-star-line"></i>
                    <span>حساب مدير المنشأة (بيانات الدخول للأداة)</span>
                </div>

                <div class="form-group">
                    <label for="adminNameInput" class="form-label">
                        <span>اسم المسؤول (المدير)</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="adminNameInput" name="admin_name" class="form-control" placeholder="مثال: سالم بن عبدالله" value="{{ old('admin_name') }}" required>
                </div>

                <div class="form-group">
                    <label for="adminEmailInput" class="form-label">
                        <span>البريد الإلكتروني لتسجيل الدخول</span>
                        <span class="required">*</span>
                    </label>
                    <input type="email" id="adminEmailInput" name="admin_email" class="form-control" placeholder="salem@company.sa" value="{{ old('admin_email') }}" dir="ltr" required>
                </div>

                <div class="form-group">
                    <label for="adminPasswordInput" class="form-label">
                        <span>كلمة المرور الأولية للحساب</span>
                        <span class="required">*</span>
                    </label>
                    <input type="password" id="adminPasswordInput" name="admin_password" class="form-control" placeholder="••••••••" minlength="8" required>
                    <span class="form-hint">كلمة مرور أولية لا تقل عن 8 خانات، يستطيع العميل تغييرها لاحقاً.</span>
                </div>

                <!-- 3. قسم باقة الاستخدام ودورة الفوترة -->
                <div class="form-section-title">
                    <i class="ri-vip-crown-line"></i>
                    <span>باقة الاستخدام ودورة الفوترة</span>
                </div>

                <div class="form-group">
                    <label for="tenantPlanSelect" class="form-label">
                        <span>باقة المنشأة</span>
                        <span class="required">*</span>
                    </label>
                    <select id="tenantPlanSelect" name="plan_id" class="form-select">
                        @foreach($plans as $plan)
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
                                {{ (old('plan_id') == $plan->id || (!old('plan_id') && $plan->isFree())) ? 'selected' : '' }}>
                                {{ $plan->name }} {{ $plan->isFree() ? '(افتراضية - مجانية)' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <span class="form-hint">يمكن ترقية باقة المنشأة في أي وقت لاحقاً من شاشة التعديل.</span>
                </div>

                <div class="form-group">
                    <label for="tenantBillingCycleSelect" class="form-label">
                        <span>دورة الفوترة والتجديد</span>
                        <span class="required">*</span>
                    </label>
                    <select id="tenantBillingCycleSelect" name="billing_cycle" class="form-select">
                        <option value="monthly" {{ old('billing_cycle', 'monthly') === 'monthly' ? 'selected' : '' }}>شهرياً</option>
                        <option value="yearly" {{ old('billing_cycle') === 'yearly' ? 'selected' : '' }}>سنوياً</option>
                    </select>
                    <span class="form-hint" id="billingCycleHint">تحدد دورة الفوترة تاريخ نهاية الفترة وتجديد العقد تلقائياً.</span>
                </div>
            </div>

            <!-- أزرار الإجراءات -->
            <div class="form-actions">
                <a href="{{ route('tenants.index') }}" class="btn-secondary">إلغاء</a>
                <button type="submit" class="btn-action-primary">
                    <i class="ri-check-line"></i>
                    <span>حفظ وتفعيل المشترك</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/tenant-billing-cycle.js') }}?v={{ filemtime(public_path('js/tenant-billing-cycle.js')) }}"></script>
@endsection
