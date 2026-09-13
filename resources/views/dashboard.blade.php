@extends('layouts.app')

@section('title', 'مركز القيادة والتحكم - نظرة عامة')

@section('content')
<!-- 1. ترويسة لوحة التحكم الرسمية -->
<div class="page-title-bar">
    <div>
        <h1>نظرة عامة على المنصة</h1>
        <p>مركز القيادة والتحكم: متابعة الإيراد الشهري المتكرر، الاشتراكات النشطة، والتنبيهات التشغيلية العاجلة</p>
    </div>
    <div>
        <a href="{{ route('tenants.create') }}" class="btn-action-primary">
            <i class="ri-user-add-line"></i>
            <span>إضافة مشترك جديد</span>
        </a>
    </div>
</div>

<!-- 2. بطاقات المؤشرات الاستراتيجية الأربعة (Core SaaS KPIs) -->
<div class="kpi-grid">
    @foreach($stats as $key => $stat)
        <div class="kpi-card">
            <div class="kpi-header">
                <span class="kpi-label">{{ $stat['label'] }}</span>
                <div class="kpi-icon-box">
                    <i class="{{ $stat['icon'] }}"></i>
                </div>
            </div>
            <div class="kpi-value">
                {{ $stat['value'] }} <span class="kpi-unit">{{ $stat['currency'] }}</span>
            </div>
            <div class="kpi-footer">
                <span class="trend-badge trend-stable">
                    @if($stat['trend'] === 'up')
                        <i class="ri-arrow-up-line"></i>
                    @elseif($stat['trend'] === 'warning')
                        <i class="ri-time-line"></i>
                    @else
                        <i class="ri-check-line"></i>
                    @endif
                    <span>{{ $stat['change'] }}</span>
                </span>
                <span>{{ $stat['subtext'] }}</span>
            </div>
        </div>
    @endforeach
</div>

<!-- 3. القسم البصري والرسومات البيانية التفاعلية (Visual Analytics Grid) -->
<div class="charts-grid" 
     id="dashboardChartsContainer"
     data-revenue-labels="{{ json_encode($revenueChartData['labels']) }}"
     data-revenue-values="{{ json_encode($revenueChartData['values']) }}"
     data-billing-labels="{{ json_encode($billingDistribution['labels']) }}"
     data-billing-percentages="{{ json_encode($billingDistribution['percentages']) }}"
     data-billing-counts="{{ json_encode($billingDistribution['counts']) }}"
     data-currency-symbol="منشأة">
    <!-- رسم نمو تسجيل المنشآت (Tenant Growth Curve) -->
    <div class="card-panel chart-card">
        <div class="card-panel-header">
            <div>
                <div class="card-panel-title">منحنى نمو تسجيل المنشآت</div>
                <div class="card-panel-subtitle">متابعة وتيرة انضمام المشتركين الجدد عبر آخر 6 أشهر</div>
            </div>
            <div class="chart-badge-group">
                <span class="trend-badge trend-stable">
                    <i class="ri-line-chart-line"></i>
                    <span>معدل نمو المنشآت</span>
                </span>
            </div>
        </div>
        <div class="chart-card-body">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- رسم توزيع المشتركين على الباقات (Plan Distribution Breakdown) -->
    <div class="card-panel chart-card">
        <div class="card-panel-header">
            <div>
                <div class="card-panel-title">توزيع المشتركين بحسب الباقات</div>
                <div class="card-panel-subtitle">نسبة المنشآت الموزعة على باقات المنظومة</div>
            </div>
            <div class="chart-badge-group">
                <span class="trend-badge trend-stable">
                    <i class="ri-pie-chart-line"></i>
                    <span>توزيع الباقات</span>
                </span>
            </div>
        </div>
        <div class="chart-card-body">
            <canvas id="billingChart"></canvas>
        </div>
    </div>
</div>

<!-- 4. قسم العمليات والنشاط الحي (Operations & Live Stream Grid) -->
<div class="operations-grid">
    <!-- قائمة أحدث المنشآت المشتركة بالمنصة -->
    <div class="card-panel">
        <div class="card-panel-header">
            <div>
                <div class="card-panel-title">أحدث المنشآت المنضمة للمنصة</div>
                <div class="card-panel-subtitle">سجل بآخر الحسابات المسجلة وحالة باقاتها الحالية</div>
            </div>
            <a href="{{ route('tenants.index') }}" class="card-panel-link">
                <span>عرض كافة المنشآت</span>
                <i class="ri-arrow-left-line"></i>
            </a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>المنشأة</th>
                        <th>الباقة</th>
                        <th>الحالة</th>
                        <th>تاريخ الانضمام</th>
                        <th class="text-center">الإجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latestTenants as $tenant)
                        <tr>
                            <td>
                                <div class="subscriber-cell">
                                    <div class="subscriber-avatar">{{ mb_substr($tenant->name ?? '—', 0, 1) }}</div>
                                    <div class="subscriber-name">{{ $tenant->name ?? '—' }}</div>
                                </div>
                            </td>
                            <td>
                                <span class="trend-badge trend-stable">
                                    <i class="ri-vip-diamond-line"></i>
                                    <span>{{ $tenant->plan?->name ?? 'الباقة المجانية' }}</span>
                                </span>
                            </td>
                            <td>
                                <span class="status-pill {{ $tenant->status }}">
                                    <span class="status-dot"></span>
                                    <span>{{ $tenant->status === 'active' ? 'نشط' : 'معلق' }}</span>
                                </span>
                            </td>
                            <td class="date-cell">
                                {{ $tenant->created_at?->format('Y/m/d') ?? '—' }}
                            </td>
                            <td class="text-center">
                                <a href="{{ route('tenants.show', $tenant) }}" class="btn-table-action">
                                    <i class="ri-eye-line"></i>
                                    <span>معاينة</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr class="table-empty-row">
                            <td colspan="5">
                                <div class="table-empty-wrap">
                                    <div class="empty-icon-box">
                                        <i class="ri-shield-check-line"></i>
                                    </div>
                                    <h4 class="empty-title">لا توجد منشآت مسجلة بعد</h4>
                                    <p class="empty-desc">ستظهر هنا أحدث المنشآت بمجرد تسجيلها بالمنصة.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- شريط التدفق الزمني للنشاط الحي -->
    <div class="card-panel">
        <div class="card-panel-header">
            <div>
                <div class="card-panel-title">شريط النشاط والعمليات الحية</div>
                <div class="card-panel-subtitle">آخر التحديثات والتجديدات في المنصة</div>
            </div>
            <span class="trend-badge trend-stable">
                <i class="ri-broadcast-line"></i>
                <span>بث مباشر</span>
            </span>
        </div>
        <div class="timeline-stream">
            @forelse($activityFeed as $activity)
                <div class="timeline-entry">
                    <div class="timeline-icon-box">
                        <i class="{{ $activity['icon'] }}"></i>
                    </div>
                    <div class="timeline-details">
                        <div class="timeline-header-row">
                            <span class="timeline-title">{{ $activity['title'] }}</span>
                            <span class="timeline-time">{{ $activity['time_ago'] }}</span>
                        </div>
                        <p class="timeline-desc">{{ $activity['description'] }}</p>
                    </div>
                </div>
            @empty
                <div class="table-empty-wrap">
                    <div class="empty-icon-box">
                        <i class="ri-inbox-line"></i>
                    </div>
                    <h4 class="empty-title">لا توجد نشاطات مسجلة حديثاً</h4>
                    <p class="empty-desc">لم تسجل أي أحداث أو عمليات تشغيلية في البث المباشر حتى الآن.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/dashboard-charts.js') }}?v={{ filemtime(public_path('js/dashboard-charts.js')) }}"></script>
@endsection
