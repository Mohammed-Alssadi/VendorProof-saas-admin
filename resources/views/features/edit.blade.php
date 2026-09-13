@extends('layouts.app')

@section('title', 'تعديل الميزة: ' . $feature->name)

@section('breadcrumbs')
    <span>/</span>
    <a href="{{ route('features.index') }}">الميزات والحدود</a>
    <span>/</span>
    <span class="current">تعديل: {{ $feature->name }}</span>
@endsection

@section('content')
<!-- شريط عنوان الصفحة -->
<div class="page-title-bar">
    <div>
        <h1>تعديل الميزة التشغيلية</h1>
        <p>تحديث الاسم والرمز البرمجي والوصف التشغيلي للميزة في المنظومة</p>
    </div>
    <div>
        <a href="{{ route('features.index') }}" class="btn-secondary">
            <i class="ri-arrow-right-line"></i>
            <span>العودة لقائمة الميزات</span>
        </a>
    </div>
</div>

<!-- نموذج تعديل الميزة -->
<div class="card-panel">
    <div class="card-panel-header">
        <div class="card-panel-title">
            <i class="ri-edit-line"></i>
            <span>تعديل الميزة: {{ $feature->name }}</span>
        </div>
    </div>
    <div class="card-panel-body">
        <form action="{{ route('features.update', $feature) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-grid">
                <div class="form-section-title">
                    <i class="ri-article-line"></i>
                    <span>تعريف الميزة والنوع</span>
                </div>

                <div class="form-group">
                    <label for="editFeatureName" class="form-label">
                        <span>اسم الميزة بالعربية</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="editFeatureName" name="name" class="form-control" value="{{ old('name', $feature->name) }}" required>
                </div>

                <div class="form-group">
                    <label for="editFeatureKey" class="form-label">
                        <span>الرمز البرمجي الفريد (Feature Key)</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="editFeatureKey" name="key" class="form-control" value="{{ old('key', $feature->key) }}" dir="ltr" required>
                </div>

                <div class="form-group form-grid-full">
                    <label for="editFeatureType" class="form-label">
                        <span>نوع الميزة التشغيلية</span>
                        <span class="required">*</span>
                    </label>
                    <select id="editFeatureType" name="type" class="form-select" required>
                        <option value="limit" {{ old('type', $feature->type) === 'limit' ? 'selected' : '' }}>حد عددي (Limit) - قيمة رقمية قابلة للاستهلاك والتقييد</option>
                        <option value="feature" {{ old('type', $feature->type) === 'feature' ? 'selected' : '' }}>ميزة تشغيلية (Feature) - تفعيل أو تعطيل (Boolean)</option>
                    </select>
                </div>
            </div>

            <!-- أزرار الإجراءات -->
            <div class="form-actions">
                <a href="{{ route('features.index') }}" class="btn-secondary">إلغاء</a>
                <button type="submit" class="btn-action-primary">
                    <i class="ri-check-line"></i>
                    <span>حفظ التعديلات</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
