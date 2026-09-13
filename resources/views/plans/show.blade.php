@extends('layouts.app')

@section('title', 'تفاصيل الخطة: ' . $plan->name)

@section('breadcrumbs')
    <span>/</span>
    <a href="{{ route('plans.index') }}">دليل الخدمات والكتالوج</a>
    <span>/</span>
    <span class="current">{{ $plan->name }}</span>
@endsection

@section('content')
<!-- شريط عنوان الصفحة وأزرار الإجراءات -->
<div class="page-title-bar">
    <div>
        <h1>{{ $plan->name }}</h1>
        <p>استعراض هوية الخطة السحابية، أسعار الفوترة، حدود استهلاك الموارد، والميزات النشطة</p>
    </div>
    <div class="plan-show-actions">
        <a href="{{ route('plans.edit', $plan) }}" class="btn-action-primary">
            <i class="ri-edit-line"></i>
            <span>تعديل الخطة</span>
        </a>
        <a href="{{ route('plans.features', $plan) }}" class="btn-secondary">
            <i class="ri-list-check-2"></i>
            <span>تخصيص الميزات</span>
        </a>
        <a href="{{ route('plans.index') }}" class="btn-secondary">
            <i class="ri-arrow-right-line"></i>
            <span>العودة لقائمة الخطط</span>
        </a>
    </div>
</div>

<!-- بطاقات إحصائيات وتسعير الباقة (KPI Grid) -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-label">المنشآت المشتركة بالباقة</span>
            <div class="kpi-icon-box">
                <i class="ri-building-line"></i>
            </div>
        </div>
        <div class="kpi-value">{{ $plan->tenants_count }}</div>
        <div class="kpi-footer">
            <span class="trend-badge trend-stable">
                <i class="ri-shield-check-line"></i>
                <span>اشتراكات نشطة</span>
            </span>
            <span>منشآت معتمدة</span>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-label">السعر الشهري</span>
            <div class="kpi-icon-box">
                <i class="ri-money-dollar-circle-line"></i>
            </div>
        </div>
        <div class="kpi-value">{{ number_format($plan->monthlyPrice?->price ?? 0) }} <span class="kpi-unit">{{ $plan->monthlyPrice?->currency_symbol ?? $plan->currency_symbol }}</span></div>
        <div class="kpi-footer">
            <span>لكل دورة فوترة شهرية</span>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-label">السعر السنوي</span>
            <div class="kpi-icon-box">
                <i class="ri-calendar-check-line"></i>
            </div>
        </div>
        <div class="kpi-value">{{ number_format($plan->yearlyPrice?->price ?? 0) }} <span class="kpi-unit">{{ $plan->yearlyPrice?->currency_symbol ?? $plan->currency_symbol }}</span></div>
        <div class="kpi-footer">
            <span class="trend-badge trend-stable">وفر شهرين</span>
            <span>فوترة سنوية موحدة</span>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-header">
            <span class="kpi-label">حالة إتاحة الخطة</span>
            <div class="kpi-icon-box">
                <i class="ri-checkbox-circle-line"></i>
            </div>
        </div>
        <div class="kpi-value kpi-value-sm">
            <span class="status-pill {{ $plan->status }}">
                <span class="status-dot"></span>
                <span>{{ $plan->status === 'active' ? 'نشطة ومتاحة' : 'معطلة مؤقتاً' }}</span>
            </span>
        </div>
        <div class="kpi-footer">
            <span>متاحة في صفحة التسجيل والترقية</span>
        </div>
    </div>
</div>

<!-- لوحة تفاصيل الهوية والوصف -->
<div class="card-panel">
    <div class="card-panel-header">
        <div class="card-panel-title">
            <i class="ri-article-line"></i>
            <span>الهوية التشغيلية والتوصيف</span>
        </div>
    </div>
    <div class="card-panel-body">
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">اسم الباقة التجاري</span>
                <span class="detail-value">{{ $plan->name }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">المعرف البرمجي الفريد (Slug)</span>
                <span class="detail-value"><code>{{ $plan->slug }}</code></span>
            </div>
            <div class="detail-item detail-item-wide">
                <span class="detail-label">وصف الباقة والقيمة المضافة</span>
                <span class="detail-value detail-desc">{{ $plan->description ?? 'لا يوجد وصف محدد' }}</span>
            </div>
        </div>
    </div>
</div>

<!-- جدول الميزات والحدود التشغيلية المعتمدة للباقة -->
<div class="card-panel">
    <div class="card-panel-header">
        <div class="card-panel-title">
            <i class="ri-table-line"></i>
            <span>جدول الميزات والحدود المعتمدة لباقة {{ $plan->name }}</span>
        </div>
        <a href="{{ route('plans.features', $plan) }}" class="card-panel-link">
            <i class="ri-edit-line"></i>
            <span>تعديل الحدود والميزات</span>
        </a>
    </div>

    <div class="table-responsive">
        <table class="data-table matrix-table">
            <thead>
                <tr>
                    <th>الميزة / الحد التشغيلي</th>
                    <th class="th-type">النوع</th>
                    <th class="th-value">القيمة الحالية للباقة</th>
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
                        <td class="plan-col-cell">
                            <span class="trend-badge trend-stable">{{ $plan->getLimitDisplay($limit->key) }}</span>
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
                        <td class="plan-col-cell">
                            @if($plan->hasFeature($feature->key))
                                <i class="ri-checkbox-circle-fill icon-success" title="مفعلة"></i>
                            @else
                                <i class="ri-close-circle-line icon-danger" title="غير متوفرة"></i>
                            @endif
                        </td>
                    </tr>
                @endforeach

                @if($limits->isEmpty() && $features->isEmpty())
                    <tr class="table-empty-row">
                        <td colspan="3">
                            <div class="table-empty-wrap">
                                <div class="empty-icon-box">
                                    <i class="ri-toggle-line"></i>
                                </div>
                                <h4 class="empty-title">لا توجد ميزات أو حدود معرفة لهذه الباقة</h4>
                                <p class="empty-desc">لم يتم ربط أي ميزات أو حدود تشغيلية بهذه الباقة حتى الآن.</p>
                                <div class="empty-actions">
                                    <a href="{{ route('plans.features', $plan) }}" class="btn-action-primary">
                                        <i class="ri-equalizer-line"></i>
                                        <span>تعديل الحدود والميزات</span>
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
