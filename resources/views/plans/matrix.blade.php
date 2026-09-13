@extends('layouts.app')

@section('title', 'مصفوفة الميزات والحدود - مقارنة الباقات')

@section('breadcrumbs')
    <span>/</span>
    <a href="{{ route('plans.index') }}">الخطط والأسعار</a>
    <span>/</span>
    <span class="current">مصفوفة الميزات والحدود</span>
@endsection

@section('content')
<!-- شريط عنوان الصفحة -->
<div class="page-title-bar">
    <div>
        <h1>مصفوفة الميزات والحدود التشغيلية</h1>
        <p>المقارنة التفصيلية لحدود الموارد، ميزات محرك الامتثال، ومستويات الدعم الفني لكل باقة</p>
    </div>
    <div>
        <button type="button" class="btn-action-primary" data-toast="تم حفظ تحديثات مصفوفة الميزات بنجاح">
            <i class="ri-save-line"></i>
            <span>حفظ مصفوفة الصلاحيات</span>
        </button>
    </div>
</div>

<!-- شريط تبويبات التنقل -->
<div class="tab-nav">
    <a href="{{ route('plans.index') }}" class="tab-nav-item">
        <i class="ri-price-tag-3-line"></i>
        <span>قائمة الخطط والأسعار</span>
    </a>
    <a href="{{ route('plans.matrix') }}" class="tab-nav-item active">
        <i class="ri-table-line"></i>
        <span>مصفوفة الميزات والحدود</span>
    </a>
</div>

<!-- جدول مصفوفة المقارنة المتقدمة -->
<div class="card-panel">
    <div class="card-panel-header">
        <div>
            <div class="card-panel-title">جدول مقارنة الحدود والميزات</div>
            <div class="card-panel-subtitle">توزيع المزايا والحدود عبر باقات المنصة السحابية</div>
        </div>
        <div>
            <span class="trend-badge trend-stable">
                <i class="ri-shield-keyhole-line"></i>
                <span>3 باقات معتمدة</span>
            </span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="data-table matrix-table">
            <thead>
                <tr>
                    <th>الميزة / الحد التشغيلي</th>
                    <th>النوع</th>
                    @foreach($plans as $plan)
                        <th class="plan-col-header">{{ $plan->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($features as $feature)
                    <tr>
                        <td>
                            <span class="feature-title-name">{{ $feature->name }}</span>
                        </td>
                        <td>
                            <span class="trend-badge trend-stable">{{ $feature->type === 'limit' ? 'حد عددي' : 'ميزة تشغيلية' }}</span>
                        </td>
                        @foreach($plans as $plan)
                            <td class="plan-col-cell">
                                @if($feature->type !== 'limit')
                                    @if($plan->hasFeature($feature->key))
                                        <i class="ri-checkbox-circle-fill icon-success" title="مفعلة"></i>
                                    @else
                                        <i class="ri-close-circle-line icon-danger" title="غير متوفرة"></i>
                                    @endif
                                @else
                                    <span class="trend-badge trend-stable">{{ $plan->getLimitDisplay($feature->key) }}</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr class="table-empty-row">
                        <td colspan="{{ max(3, count($plans) + 2) }}">
                            <div class="table-empty-wrap">
                                <div class="empty-icon-box">
                                    <i class="ri-table-line"></i>
                                </div>
                                <h4 class="empty-title">لا توجد ميزات أو حدود معرفة في مصفوفة المقارنة</h4>
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
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
