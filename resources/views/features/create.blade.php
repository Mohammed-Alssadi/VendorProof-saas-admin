@extends('layouts.app')

@section('title', 'إضافة ميزة أو حد تشغيلي')

@section('breadcrumbs')
    <span>/</span>
    <a href="{{ route('features.index') }}">الميزات والحدود</a>
    <span>/</span>
    <span class="current">إضافة ميزة جديدة</span>
@endsection

@section('content')
<!-- شريط عنوان الصفحة -->
<div class="page-title-bar">
    <div>
        <h1>إضافة ميزة تشغيلية جديدة</h1>
        <p>تسجيل ميزة برمجية أو حد تشغيلي جديد في معجم المنظومة المركزي</p>
    </div>
    <div>
        <a href="{{ route('features.index') }}" class="btn-secondary">
            <i class="ri-arrow-right-line"></i>
            <span>العودة لقائمة الميزات</span>
        </a>
    </div>
</div>

<!-- نموذج إضافة الميزة -->
<div class="card-panel">
    <div class="card-panel-header">
        <div class="card-panel-title">
            <i class="ri-equalizer-line"></i>
            <span>البيانات التقنية للميزة</span>
        </div>
        <span class="trend-badge trend-stable">
            <i class="ri-code-line"></i>
            <span>معجم ميزات النظام</span>
        </span>
    </div>
    <div class="card-panel-body">
        <form action="{{ route('features.store') }}" method="POST">
            @csrf
            <div class="form-grid">
                <div class="form-section-title">
                    <i class="ri-article-line"></i>
                    <span>تعريف الميزة والنوع</span>
                </div>

                <div class="form-group">
                    <label for="featureNameInput" class="form-label">
                        <span>اسم الميزة بالعربية</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="featureNameInput" name="name" class="form-control" placeholder="مثال: عدد الموردين المفحوصين شهرياً" value="{{ old('name') }}" required>
                </div>

                <div class="form-group">
                    <label for="featureKeyInput" class="form-label">
                        <span>الرمز البرمجي الفريد (Feature Key)</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="featureKeyInput" name="key" class="form-control" placeholder="max_vendors" value="{{ old('key') }}" dir="ltr" required>
                </div>

                <div class="form-group form-grid-full">
                    <label for="featureTypeSelect" class="form-label">
                        <span>نوع الميزة التشغيلية</span>
                        <span class="required">*</span>
                    </label>
                    <select id="featureTypeSelect" name="type" class="form-select" required>
                        <option value="limit" {{ old('type') === 'limit' ? 'selected' : '' }}>حد عددي (Limit) - قيمة رقمية قابلة للاستهلاك والتقييد</option>
                        <option value="feature" {{ old('type') === 'feature' ? 'selected' : '' }}>ميزة تشغيلية (Feature) - تفعيل أو تعطيل (Boolean)</option>
                    </select>
                </div>
            </div>

            <!-- أزرار الإجراءات -->
            <div class="form-actions">
                <a href="{{ route('features.index') }}" class="btn-secondary">إلغاء</a>
                <button type="submit" class="btn-action-primary">
                    <i class="ri-check-line"></i>
                    <span>حفظ وإضافة الميزة</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
