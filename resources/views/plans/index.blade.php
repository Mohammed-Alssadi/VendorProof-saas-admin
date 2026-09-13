@extends('layouts.app')

@section('title', 'الخطط والأسعار - إدارة الباقات')

@section('breadcrumbs')
    <span>/</span>
    <span class="current">الخطط والأسعار</span>
@endsection

@section('content')
<!-- شريط عنوان الصفحة -->
<div class="page-title-bar">
    <div>
        <h1>الخطط وباقات الاشتراك</h1>
        <p>إدارة وتحديد باقات المنصة، سياسات التسعير الشهرية والسنوية، وحصص المشتركين</p>
    </div>
    <div>
        <a href="{{ route('plans.create') }}" class="btn-action-primary">
            <i class="ri-add-line"></i>
            <span>إضافة خطة جديدة</span>
        </a>
    </div>
</div>

<!-- شريط تبويبات التنقل بين الخطط ومصفوفة الميزات -->
<div class="tab-nav">
    <a href="{{ route('plans.index') }}" class="tab-nav-item active">
        <i class="ri-price-tag-3-line"></i>
        <span>قائمة الخطط والأسعار</span>
    </a>
    <a href="{{ route('plans.matrix') }}" class="tab-nav-item">
        <i class="ri-table-line"></i>
        <span>مصفوفة الميزات والحدود</span>
    </a>
</div>

@if(!empty($plans) && count($plans) > 0)
<!-- بطاقات الباقات السحابية -->
<div class="plans-grid">
    @foreach($plans as $plan)
        <div class="plan-card {{ $plan->is_popular ? 'popular' : '' }}">
            <div class="plan-card-header">
                <div class="plan-card-top-meta">
                    <span class="status-pill {{ $plan->status }}">
                        <span class="status-dot"></span>
                        <span>{{ $plan->status === 'active' ? 'نشطة' : 'معطلة' }}</span>
                    </span>
                    @if($plan->is_popular)
                        <span class="plan-popular-tag">
                            <i class="ri-sparkling-fill"></i>
                            <span>الأكثر طلباً</span>
                        </span>
                    @endif
                </div>
                <div class="plan-card-name">{{ $plan->name }}</div>
                <div class="plan-card-desc">{{ $plan->description }}</div>
            </div>

            <div class="plan-card-price-block">
                <div class="plan-price-main">
                    <span class="plan-price-amount">{{ $plan->monthlyPrice ? number_format((float) $plan->monthlyPrice->price, 0) : '0' }}</span>
                    <span class="plan-price-currency">{{ $plan->currency_symbol }}</span>
                    <span class="plan-price-period">/ شهرياً</span>
                </div>
                @if($plan->yearlyPrice)
                    <div class="plan-price-yearly">
                        <span class="plan-yearly-note">تُفوتر {{ $plan->yearlyPrice->formattedPrice() }} سنوياً</span>
                    </div>
                @endif
            </div>

            <div class="plan-features-section">
                <div class="plan-features-label">الميزات والقدرات التشغيلية:</div>
                <ul class="plan-features-list">
                    @forelse($plan->features->where('pivot.value', '!=', '0') as $featureItem)
                        @php
                            $pivotVal = (string) ($featureItem->pivot->value ?? '');
                            $isLimit = $featureItem->type === 'limit';
                        @endphp
                        <li class="plan-feature-item">
                            <span class="plan-feature-icon">
                                <i class="ri-check-line"></i>
                            </span>
                            <span class="plan-feature-text">
                                @if($isLimit)
                                    {{ $pivotVal === '-1' ? 'غير محدود' : $pivotVal }} {{ $featureItem->name }}
                                @else
                                    {{ $featureItem->name }}
                                @endif
                            </span>
                        </li>
                    @empty
                        <li class="plan-feature-item">
                            <span class="plan-feature-text">لا توجد ميزات إضافية مشمولة</span>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    @endforeach
</div>
@endif

<!-- جدول بيانات الباقات الإداري المتقدم -->
<div class="card-panel">
    <div class="card-panel-header">
        <div>
            <div class="card-panel-title">جدول حوكمة الخطط والفوترة</div>
            <div class="card-panel-subtitle">إحصائيات الاشتراك والتسعير الرسمية للباقات</div>
        </div>
        <a href="{{ route('plans.matrix') }}" class="card-panel-link">
            <i class="ri-table-line"></i>
            <span>الانتقال لمصفوفة مقارنة الميزات</span>
        </a>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>اسم الباقة</th>
                    <th>السعر الشهري</th>
                    <th>السعر السنوي</th>
                    <th>المشتركون النشطون</th>
                    <th>الحالة</th>
                    <th class="th-actions">الإجراء</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                    <tr>
                        <td>
                            <div class="subscriber-name">{{ $plan->name }}</div>
                        </td>
                        <td class="price-cell">{{ $plan->monthlyPrice ? $plan->monthlyPrice->formattedPrice() : '—' }}</td>
                        <td class="price-cell">{{ $plan->yearlyPrice ? $plan->yearlyPrice->formattedPrice() : '—' }}</td>
                        <td class="price-cell">{{ $plan->subscriptions_count ?? 0 }} مشترك</td>
                        <td>
                            <span class="status-pill {{ $plan->status }}">
                                <span class="status-dot"></span>
                                <span>{{ $plan->status === 'active' ? 'نشطة' : 'معطلة' }}</span>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="table-dropdown">
                                <button type="button" class="table-dropdown-trigger" title="خيارات الخطة" aria-haspopup="true">
                                    <i class="ri-more-2-fill"></i>
                                </button>
                                <div class="table-dropdown-menu">
                                    <a href="{{ route('plans.show', $plan) }}" class="table-dropdown-item">
                                        <i class="ri-eye-line"></i>
                                        <span>عرض تفاصيل الخطة</span>
                                    </a>
                                    <a href="{{ route('plans.edit', $plan) }}" class="table-dropdown-item">
                                        <i class="ri-edit-line"></i>
                                        <span>تعديل الخطة والأسعار</span>
                                    </a>
                                    <a href="{{ route('plans.features', $plan) }}" class="table-dropdown-item">
                                        <i class="ri-equalizer-line"></i>
                                        <span>إدارة ميزات وحدود الباقة</span>
                                    </a>
                                    <form action="{{ route('plans.destroy', $plan) }}" method="POST" class="table-dropdown-form" data-confirm="هل أنت متأكد من رغبتك في حذف هذه الخطة من المنظومة؟">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="table-dropdown-item danger">
                                            <i class="ri-delete-bin-line"></i>
                                            <span>حذف الخطة</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="table-empty-row">
                        <td colspan="6">
                            <div class="table-empty-wrap">
                                <div class="empty-icon-box">
                                    <i class="ri-price-tag-3-line"></i>
                                </div>
                                <h4 class="empty-title">لا توجد باقات اشتراك مسجلة</h4>
                                <p class="empty-desc">لم يتم إعداد أو تسجيل أي باقات في المنظومة بعد. ابدأ بإنشاء أول خطة لتحديد أسعارها الشهرية والسنوية وميزاتها.</p>
                                <div class="empty-actions">
                                    <a href="{{ route('plans.create') }}" class="btn-action-primary">
                                        <i class="ri-add-line"></i>
                                        <span>إضافة أول خطة</span>
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


