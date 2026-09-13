@extends('layouts.app')

@section('title', 'إدارة ميزات وحدود: ' . $plan->name)

@section('breadcrumbs')
    <span>/</span>
    <a href="{{ route('plans.index') }}">دليل الخدمات والكتالوج</a>
    <span>/</span>
    <span class="current">إدارة ميزات: {{ $plan->name }}</span>
@endsection

@section('content')
<!-- شريط عنوان الصفحة -->
<div class="page-title-bar">
    <div>
        <h1>إدارة ميزات وحدود الباقة: {{ $plan->name }}</h1>
        <p>تخصيص قيم الموارد والميزات التشغيلية المعتمدة لمشتركي هذه الباقة</p>
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

<!-- جدول إدارة الميزات والحدود المعتمدة للباقة المحددة -->
<div class="card-panel">
    <div class="card-panel-header">
        <div class="card-panel-title">
            <i class="ri-list-check-2"></i>
            <span>جدول الميزات والحدود المعتمدة لباقة {{ $plan->name }}</span>
        </div>
        <span class="trend-badge trend-stable">
            <i class="ri-shield-check-line"></i>
            <span>تحديث مباشر للصلاحيات</span>
        </span>
    </div>

    <form action="{{ route('plans.features.update', $plan) }}" method="POST">
        @csrf
        @method('PUT')

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
                    <!-- صفوف الحدود التشغيلية الرقمية -->
                    @forelse($limits as $limit)
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
                                           id="feature_limit_{{ $limit->id }}"
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
                                               data-target="feature_limit_{{ $limit->id }}"
                                               {{ $isUnlimited ? 'checked' : '' }}>
                                        <span>غير محدود</span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                    @empty
                    @endforelse

                    <!-- صفوف الميزات التشغيلية -->
                    @forelse($features as $feature)
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
                    @empty
                    @endforelse

                    @if($limits->isEmpty() && $features->isEmpty())
                        <tr class="table-empty-row">
                            <td colspan="3">
                                <div class="table-empty-wrap">
                                    <div class="empty-icon-box">
                                        <i class="ri-toggle-line"></i>
                                    </div>
                                    <h4 class="empty-title">لا توجد ميزات أو حدود معرفة في المنظومة</h4>
                                    <p class="empty-desc">لم يتم تعريف أي ميزات أو حدود تشغيلية في معجم النظام حتى الآن.</p>
                                    <div class="empty-actions">
                                        <a href="{{ route('features.create') }}" class="btn-action-primary">
                                            <i class="ri-add-line"></i>
                                            <span>إضافة ميزة تشغيلية</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="card-panel-footer">
            <button type="submit" class="btn-action-primary">
                <i class="ri-save-line"></i>
                <span>حفظ وتطبيق الميزات</span>
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
