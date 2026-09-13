@extends('layouts.app')

@section('title', 'إعدادات الحساب والأمان')

@section('breadcrumbs')
    <span>/</span>
    <span class="current">إعدادات الحساب</span>
@endsection

@section('content')
<!-- شريط عنوان الصفحة -->
<div class="page-title-bar">
    <div>
        <h1>إعدادات الحساب الشخصي والأمان</h1>
        <p>إدارة بيانات حساب السوبر أدمن، البريد الإلكتروني المعتمد، وكلمة مرور تسجيل الدخول</p>
    </div>
    <div>
        <span class="trend-badge trend-stable">
            <i class="ri-shield-check-line"></i>
            <span>مالك المنصة (Super Admin)</span>
        </span>
    </div>
</div>

<!-- 1. لوحة البيانات الأساسية للحساب -->
<div class="card-panel">
    <div class="card-panel-header">
        <div class="card-panel-title">
            <i class="ri-user-settings-line"></i>
            <span>البيانات الأساسية للمشرف</span>
        </div>
        <span class="trend-badge trend-stable">
            <i class="ri-mail-check-line"></i>
            <span>حساب مصادق ومحمي</span>
        </span>
    </div>
    <div class="card-panel-body">
        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div class="form-group">
                    <label for="adminNameInput" class="form-label">
                        <span>الاسم الكامل</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="adminNameInput" name="name" class="form-control" value="{{ $admin->name ?? 'مدير المنصة' }}" required>
                </div>

                <div class="form-group">
                    <label for="adminEmailInput" class="form-label">
                        <span>البريد الإلكتروني لتسجيل الدخول</span>
                        <span class="required">*</span>
                    </label>
                    <input type="email" id="adminEmailInput" name="email" class="form-control" value="{{ $admin->email ?? 'admin@vendorproof.sa' }}" required>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-action-primary">
                    <i class="ri-check-line"></i>
                    <span>حفظ التعديلات الشخصية</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 2. لوحة تحديث كلمة المرور والأمان -->
<div class="card-panel">
    <div class="card-panel-header">
        <div class="card-panel-title">
            <i class="ri-lock-password-line"></i>
            <span>تغيير كلمة المرور</span>
        </div>
        <span class="trend-badge trend-stable">
            <i class="ri-key-2-line"></i>
            <span>تأمين حساب الإدارة</span>
        </span>
    </div>
    <div class="card-panel-body">
        <form action="{{ route('profile.password') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-grid-3">
                <div class="form-group">
                    <label for="currentPasswordInput" class="form-label">
                        <span>كلمة المرور الحالية</span>
                        <span class="required">*</span>
                    </label>
                    <input type="password" id="currentPasswordInput" name="current_password" class="form-control" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label for="newPasswordInput" class="form-label">
                        <span>كلمة المرور الجديدة</span>
                        <span class="required">*</span>
                    </label>
                    <input type="password" id="newPasswordInput" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <div class="form-group">
                    <label for="newPasswordConfirmInput" class="form-label">
                        <span>تأكيد كلمة المرور الجديدة</span>
                        <span class="required">*</span>
                    </label>
                    <input type="password" id="newPasswordConfirmInput" name="password_confirmation" class="form-control" placeholder="••••••••" required>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-action-primary">
                    <i class="ri-shield-keyhole-line"></i>
                    <span>تحديث كلمة المرور</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
