<?php

namespace App\Modules\Web\Http\Controllers;

use App\Models\Payment;
use App\Models\Tenant;
use App\Modules\Web\Models\Plan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $admin = Auth::user();

        $totalTenants  = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $freeTenants   = Tenant::whereHas('plan', fn ($q) => $q->free())
            ->orWhereNull('plan_id')
            ->count();
        $paidTenants = Tenant::whereHas('plan', fn ($q) => $q->paid())->count();

        $stats = [
            'total_tenants' => [
                'value'    => (string) $totalTenants,
                'currency' => 'منشأة',
                'change'   => 'إجمالي المشتركين',
                'trend'    => 'stable',
                'label'    => 'إجمالي المشتركين والمنشآت',
                'subtext'  => 'كافة الحسابات المسجلة بالمنصة',
                'icon'     => 'ri-building-line',
            ],
            'active_tenants' => [
                'value'    => (string) $activeTenants,
                'currency' => 'منشأة',
                'change'   => $totalTenants > 0 ? round(($activeTenants / $totalTenants) * 100, 1).'%' : '100%',
                'trend'    => 'stable',
                'label'    => 'المنشآت النشطة والفعالة',
                'subtext'  => 'حسابات مؤهلة وتعمل حالياً',
                'icon'     => 'ri-checkbox-circle-line',
            ],
            'free_subscribers' => [
                'value'    => (string) $freeTenants,
                'currency' => 'منشأة',
                'change'   => $totalTenants > 0 ? round(($freeTenants / $totalTenants) * 100, 1).'%' : '0%',
                'trend'    => 'stable',
                'label'    => 'مشتركو الباقة المجانية (Free)',
                'subtext'  => 'قاعدة عملاء مؤهلة للترقية',
                'icon'     => 'ri-gift-line',
            ],
            'paid_subscribers' => [
                'value'    => (string) $paidTenants,
                'currency' => 'منشأة',
                'change'   => $totalTenants > 0 ? round(($paidTenants / $totalTenants) * 100, 1).'%' : '0%',
                'trend'    => 'stable',
                'label'    => 'مشتركو الباقات المتقدمة',
                'subtext'  => 'منشآت على باقات النمو والمؤسسات',
                'icon'     => 'ri-vip-crown-line',
            ],
        ];

        $arabicMonths = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس',  4 => 'أبريل',
            5 => 'مايو',  6 => 'يونيو',  7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        $months       = collect(range(5, 0))->map(fn ($n) => Carbon::now()->subMonths($n));
        $growthLabels = [];
        $growthValues = [];

        foreach ($months as $m) {
            $growthLabels[] = $arabicMonths[$m->month];
            $growthValues[] = Tenant::whereYear('created_at', $m->year)
                ->whereMonth('created_at', $m->month)
                ->count();
        }

        $revenueChartData = ['labels' => $growthLabels, 'values' => $growthValues];

        $plans          = Plan::active()->withCount('tenants')->get();
        $planLabels     = [];
        $planCounts     = [];
        $planPercentages = [];

        foreach ($plans as $p) {
            $planLabels[]      = $p->name;
            $count             = $p->tenants_count;
            $planCounts[]      = $count;
            $planPercentages[] = $totalTenants > 0 ? round(($count / $totalTenants) * 100, 1) : 0;
        }

        if (empty($planLabels)) {
            $planLabels      = ['الباقة المجانية'];
            $planCounts      = [$totalTenants];
            $planPercentages = [100];
        }

        $billingDistribution = [
            'labels'      => $planLabels,
            'percentages' => $planPercentages,
            'counts'      => $planCounts,
        ];

        $latestTenants  = Tenant::with('plan')->latest()->take(5)->get();
        $latestPayments = Payment::with(['tenant', 'plan'])->latest()->take(5)->get();

        $activityFeed = $latestTenants->map(function ($tenant) {
            return [
                'title'       => 'انضمام منشأة جديدة',
                'description' => 'تم تسجيل '.$tenant->name.' بنجاح على '.($tenant->plan?->name ?? 'الباقة المجانية'),
                'time_ago'    => $tenant->created_at?->diffForHumans() ?? 'حديثاً',
                'icon'        => 'ri-building-line',
            ];
        });

        return view('dashboard', compact(
            'admin',
            'stats',
            'revenueChartData',
            'billingDistribution',
            'latestTenants',
            'latestPayments',
            'activityFeed'
        ));
    }
}
