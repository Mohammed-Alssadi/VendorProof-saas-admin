@extends('layouts.app')

@section('title', 'تعديل الخطة: ' . $plan->name)

@section('breadcrumbs')
    <span>/</span>
    <a href="{{ route('plans.index') }}">دليل الخدمات والكتالوج</a>
    <span>/</span>
    <span class="current">تعديل: {{ $plan->name }}</span>
@endsection

@section('content')
<!-- شريط عنوان الصفحة -->
<div class="page-title-bar">
    <div>
        <h1>تعديل خطة الاشتراك</h1>
        <p>تحديث مسميات الخطة، أسعار الفوترة، حدود استهلاك الموارد، والميزات التشغيلية المعتمدة</p>
    </div>
    <div>
        <a href="{{ route('plans.show', $plan) }}" class="btn-secondary">
            <i class="ri-eye-line"></i>
            <span>عرض تفاصيل الخطة</span>
        </a>
        <a href="{{ route('plans.index') }}" class="btn-secondary">
            <i class="ri-arrow-right-line"></i>
            <span>العودة لقائمة الخطط</span>
        </a>
    </div>
</div>

<!-- نموذج تعديل الخطة المتكامل -->
<div class="card-panel">
    <div class="card-panel-header">
        <div class="card-panel-title">
            <i class="ri-edit-line"></i>
            <span>تعديل بيانات الخطة: {{ $plan->name }}</span>
        </div>
        <a href="{{ route('plans.features', $plan) }}" class="btn-secondary">
            <i class="ri-list-check-2"></i>
            <span>شاشة مخصصة للميزات</span>
        </a>
    </div>

    <form action="{{ route('plans.update', $plan) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card-panel-body">
            <!-- 1. قسم البيانات الأساسية والتسعير -->
            <div class="form-section-title">
                <i class="ri-article-line"></i>
                <span>البيانات الأساسية والتسعير</span>
            </div>

            <!-- الصف الأول: الهوية والتعريف (عمودان 50% / 50%) -->
            <div class="form-grid">
                <div class="form-group">
                    <label for="editPlanName" class="form-label">
                        <span>اسم الباقة بالعربية</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="editPlanName" name="name" class="form-control" value="{{ $plan->name }}" required>
                </div>

                <div class="form-group">
                    <label for="editPlanSlug" class="form-label">
                        <span>المعرف البرمجي (Slug)</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="editPlanSlug" name="slug" class="form-control" value="{{ $plan->slug }}" dir="ltr" required>
                </div>
            </div>

            <!-- الصف الثاني: الأسعار، العملة، والحالة (4 أعمدة متوازنة تماماً بنسبة 1:1:1:1) -->
            <div class="form-grid-4">
                <div class="form-group">
                    <label for="editMonthlyPrice" class="form-label">
                        <span>السعر الشهري</span>
                        <span class="required">*</span>
                    </label>
                    <input type="number" id="editMonthlyPrice" name="monthly_price" class="form-control" value="{{ $plan->monthlyPrice?->price ?? 0 }}" min="0" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="editYearlyPrice" class="form-label">
                        <span>السعر السنوي</span>
                        <span class="required">*</span>
                    </label>
                    <input type="number" id="editYearlyPrice" name="yearly_price" class="form-control" value="{{ $plan->yearlyPrice?->price ?? 0 }}" min="0" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="editPlanCurrency" class="form-label">
                        <span>عملة التسعير</span>
                        <span class="required">*</span>
                    </label>
                    <select id="editPlanCurrency" name="currency" class="form-select" required>
                        <option value="SAR" {{ ($plan->monthlyPrice?->currency ?? 'SAR') === 'SAR' ? 'selected' : '' }}>ريال سعودي (SAR)</option>
                        <option value="USD" {{ ($plan->monthlyPrice?->currency ?? '') === 'USD' ? 'selected' : '' }}>دولار أمريكي (USD)</option>
                        <option value="EUR" {{ ($plan->monthlyPrice?->currency ?? '') === 'EUR' ? 'selected' : '' }}>يورو (EUR)</option>
                        <option value="AED" {{ ($plan->monthlyPrice?->currency ?? '') === 'AED' ? 'selected' : '' }}>درهم إماراتي (AED)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editPlanStatus" class="form-label">
                        <span>حالة إتاحة الخطة</span>
                        <span class="required">*</span>
                    </label>
                    <select id="editPlanStatus" name="status" class="form-select" required>
                        <option value="active" {{ $plan->status === 'active' ? 'selected' : '' }}>نشطة ومتاحة للاشتراك</option>
                        <option value="inactive" {{ $plan->status === 'inactive' ? 'selected' : '' }}>معطلة مؤقتاً (مخفية)</option>
                    </select>
                </div>
            </div>

            <!-- الصف الثالث: الوصف كآخر حقل بعرض كامل -->
            <div class="form-group">
                <label for="editPlanDesc" class="form-label">
                    <span>وصف الباقة</span>
                    <span class="required">*</span>
                </label>
                <textarea id="editPlanDesc" name="description" class="form-textarea" required>{{ $plan->description }}</textarea>
            </div>

            <!-- 2. جدول الحدود والميزات التشغيلية للباقة المحددة (بدون كاردات) -->
            <div class="form-section-title form-section-gap">
                <i class="ri-table-line"></i>
                <span>جدول الميزات والحدود التشغيلية للباقة</span>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>الميزة / الحد التشغيلي</th>
                            <th class="th-type">النوع</th>
                            <th class="th-value">القيمة / التفعيل للباقة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- صفوف الحدود العددية -->
                        @foreach($limits as $limit)
                            @php
                                $limitVal = $plan->getLimit($limit->key) ?? '0';
                                $isUnlimited = $plan->isUnlimited($limit->key);
                            @endphp
                            <tr>
                                <td>
                                    <span class="feature-title-name">{{ $limit->name }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="trend-badge trend-stable">حد عددي</span>
                                </td>
                                <td class="text-center">
                                    <div class="table-input-inline">
                                        <input type="number"
                                               id="edit_limit_{{ $limit->id }}"
                                               name="features[{{ $limit->id }}][value]"
                                               class="matrix-input-limit"
                                               value="{{ !$isUnlimited ? ($limitVal ?? '0') : '' }}"
                                               placeholder="{{ $isUnlimited ? 'غير محدود' : '0' }}"
                                               {{ $isUnlimited ? 'disabled' : '' }}
                                               min="0">
                                        <label class="feature-unlimited-label">
                                            <input type="checkbox"
                                                   name="features[{{ $limit->id }}][value]"
                                                   value="-1"
                                                   class="limit-unlimited-checkbox"
                                                   data-target="edit_limit_{{ $limit->id }}"
                                                   {{ $isUnlimited ? 'checked' : '' }}>
                                            <span>غير محدود</span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        @endforeach

                        <!-- صفوف الميزات التشغيلية -->
                        @foreach($features as $feature)
                            <tr>
                                <td>
                                    <span class="feature-title-name">{{ $feature->name }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="trend-badge trend-stable">ميزة تشغيلية</span>
                                </td>
                                <td class="text-center">
                                    <label class="switch-label switch-center">
                                        <input type="hidden" name="features[{{ $feature->id }}][value]" value="0">
                                        <input type="checkbox"
                                               name="features[{{ $feature->id }}][value]"
                                               value="1"
                                               class="switch-input"
                                               {{ $plan->hasFeature($feature->key) ? 'checked' : '' }}>
                                        <span class="switch-slider"></span>
                                    </label>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- أزرار الإجراءات في تذييل اللوحة -->
        <div class="card-panel-footer">
            <button type="submit" class="btn-action-primary">
                <i class="ri-check-line"></i>
                <span>حفظ التعديلات</span>
            </button>
            <a href="{{ route('plans.index') }}" class="btn-secondary">
                <span>إلغاء</span>
            </a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/plan-limits.js') }}?v={{ filemtime(public_path('js/plan-limits.js')) }}"></script>
@endsection
