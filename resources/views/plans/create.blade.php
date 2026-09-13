@extends('layouts.app')

@section('title', 'إنشاء خطة اشتراك جديدة')

@section('breadcrumbs')
    <span>/</span>
    <a href="{{ route('plans.index') }}">دليل الخدمات والكتالوج</a>
    <span>/</span>
    <span class="current">إنشاء خطة جديدة</span>
@endsection

@section('content')
<!-- شريط عنوان الصفحة -->
<div class="page-title-bar">
    <div>
        <h1>إنشاء خطة اشتراك جديدة</h1>
        <p>تحديد هوية الباقة السحابية، أسعار الفوترة، حدود استهلاك الموارد، والميزات التشغيلية المعتمدة</p>
    </div>
    <div>
        <a href="{{ route('plans.index') }}" class="btn-secondary">
            <i class="ri-arrow-right-line"></i>
            <span>العودة لقائمة الخطط</span>
        </a>
    </div>
</div>

<!-- نموذج إنشاء الخطة المتكامل -->
<div class="card-panel">
    <div class="card-panel-header">
        <div class="card-panel-title">
            <i class="ri-price-tag-3-line"></i>
            <span>بيانات الخطة وأسعار الفوترة والحدود</span>
        </div>
    </div>

    <form action="{{ route('plans.store') }}" method="POST">
        @csrf

        <div class="card-panel-body">
            <!-- 1. قسم البيانات الأساسية والتسعير -->
            <div class="form-section-title">
                <i class="ri-article-line"></i>
                <span>البيانات الأساسية والتسعير</span>
            </div>

            <!-- الصف الأول: الهوية والتعريف (عمودان 50% / 50%) -->
            <div class="form-grid">
                <div class="form-group">
                    <label for="planNameInput" class="form-label">
                        <span>اسم الباقة بالعربية</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="planNameInput" name="name" class="form-control" placeholder="مثال: باقة المؤسسات الموسعة" required>
                </div>

                <div class="form-group">
                    <label for="planSlugInput" class="form-label">
                        <span>المعرف البرمجي الفريد (Slug)</span>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="planSlugInput" name="slug" class="form-control" placeholder="enterprise-plus" dir="ltr" required>
                </div>
            </div>

            <!-- الصف الثاني: الأسعار، العملة، والحالة (4 أعمدة متوازنة تماماً بنسبة 1:1:1:1) -->
            <div class="form-grid-4">
                <div class="form-group">
                    <label for="planMonthlyPrice" class="form-label">
                        <span>السعر الشهري</span>
                        <span class="required">*</span>
                    </label>
                    <input type="number" id="planMonthlyPrice" name="monthly_price" class="form-control" placeholder="مثال: 490" min="0" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="planYearlyPrice" class="form-label">
                        <span>السعر السنوي</span>
                        <span class="required">*</span>
                    </label>
                    <input type="number" id="planYearlyPrice" name="yearly_price" class="form-control" placeholder="مثال: 4900" min="0" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="planCurrencySelect" class="form-label">
                        <span>عملة التسعير</span>
                        <span class="required">*</span>
                    </label>
                    <select id="planCurrencySelect" name="currency" class="form-select" required>
                        <option value="SAR" selected>ريال سعودي (SAR)</option>
                        <option value="USD">دولار أمريكي (USD)</option>
                        <option value="EUR">يورو (EUR)</option>
                        <option value="AED">درهم إماراتي (AED)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="planStatusSelect" class="form-label">
                        <span>حالة إتاحة الخطة</span>
                        <span class="required">*</span>
                    </label>
                    <select id="planStatusSelect" name="status" class="form-select" required>
                        <option value="active">نشطة ومتاحة للاشتراك</option>
                        <option value="inactive">معطلة مؤقتاً (مخفية)</option>
                    </select>
                </div>
            </div>

            <!-- الصف الثالث: الوصف كآخر حقل بعرض كامل -->
            <div class="form-group">
                <label for="planDescInput" class="form-label">
                    <span>وصف الباقة</span>
                    <span class="required">*</span>
                </label>
                <textarea id="planDescInput" name="description" class="form-textarea" placeholder="شرح موجز لمن توجه هذه الخطة وأهم القيمة المضافة التي تقدمها..." required></textarea>
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
                                               id="create_limit_{{ $limit->id }}"
                                               name="features[{{ $limit->id }}][value]"
                                               class="matrix-input-limit"
                                               value="{{ old('features.' . $limit->id . '.value', '0') }}"
                                               placeholder="0"
                                               min="0">
                                        <label class="feature-unlimited-label">
                                            <input type="checkbox"
                                                   name="features[{{ $limit->id }}][value]"
                                                   value="-1"
                                                   class="limit-unlimited-checkbox"
                                                   data-target="create_limit_{{ $limit->id }}"
                                                   {{ old('features.' . $limit->id . '.value') === '-1' ? 'checked' : '' }}>
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
                                               {{ old('features.' . $feature->id . '.value') == '1' ? 'checked' : '' }}>
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
                <span>حفظ ونشر الخطة</span>
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
