<?php

namespace App\Modules\Web\Http\Controllers\Plans;

use App\Modules\Web\Http\Controllers\Controller;
use App\Modules\Web\Http\Requests\Plans\StorePlanRequest;
use App\Modules\Web\Http\Requests\Plans\UpdatePlanFeaturesRequest;
use App\Modules\Web\Http\Requests\Plans\UpdatePlanRequest;
use App\Modules\Web\Models\Feature;
use App\Modules\Web\Models\Plan;
use App\Modules\Web\Services\Plans\PlanService;

/**
 * متحكم إدارة باقات وخطط الاشتراك والأسعار ومصفوفة الميزات والحدود.
 * يحتفظ بالعمليات العادية (العرض، التحقق من الحذف، الفتح) ويفوّض العمليات المعقدة لـ PlanService.
 */
class PlanController extends Controller
{
    public function __construct(
        protected PlanService $planService
    ) {}

    /**
     * استعراض بطاقات الباقات وجدول الحوكمة بالأسعار والمشتركين.
     */
    public function index()
    {
        $plans = Plan::with(['prices', 'features'])->withCount('subscriptions')->get();

        return view('plans.index', compact('plans'));
    }

    /**
     * فتح نموذج إنشاء خطة اشتراك جديدة.
     */
    public function create()
    {
        extract($this->getDividedFeatures());

        return view('plans.create', compact('limits', 'features'));
    }

    /**
     * حفظ خطة الاشتراك وأسعارها ومصفوفة ميزاتها في قاعدة البيانات عبر السيرفز.
     */
    public function store(StorePlanRequest $request)
    {
        $this->planService->createPlan($request);

        return redirect()->route('plans.index')
            ->with('success', 'تم إنشاء خطة الاشتراك والأسعار وحدودها بنجاح');
    }

    /**
     * استعراض تفاصيل الخطة، أسعارها، وحدودها وميزاتها النشطة.
     */
    public function show(Plan $plan)
    {
        $plan->load(['prices', 'features'])->loadCount('tenants');
        extract($this->getDividedFeatures());

        return view('plans.show', compact('plan', 'limits', 'features'));
    }

    /**
     * فتح نموذج تعديل الخطة والأسعار والميزات.
     */
    public function edit(Plan $plan)
    {
        $plan->load(['prices', 'features']);
        extract($this->getDividedFeatures());

        return view('plans.edit', compact('plan', 'limits', 'features'));
    }

    /**
     * تحديث بيانات الخطة وأسعارها وميزاتها في قاعدة البيانات عبر السيرفز.
     */
    public function update(UpdatePlanRequest $request, Plan $plan)
    {
        $this->planService->updatePlan($plan, $request);

        return redirect()->route('plans.index')
            ->with('success', 'تم تحديث خطة الاشتراك وأسعارها بنجاح');
    }

    /**
     * حذف أو أرشفة باقة اشتراك من المنظومة بعد التحقق من عدم وجود مشتركين.
     */
    public function destroy(Plan $plan)
    {
        // فحص الأمان: منع حذف الباقة إذا كان هناك منشآت تشترك بها حالياً
        if ($plan->tenants()->exists() || $plan->subscriptions()->exists()) {
            return redirect()->route('plans.index')
                ->with('error', 'لا يمكن حذف الباقة لوجود اشتراكات منشآت نشطة مرتبطة بها.');
        }

        $plan->delete();

        return redirect()->route('plans.index')
            ->with('success', 'تم حذف خطة الاشتراك بنجاح من النظام');
    }

    /**
     * فتح الشاشة المخصصة لإسناد وتخصيص ميزات وحدود الباقة.
     */
    public function features(Plan $plan)
    {
        $plan->load('features');
        extract($this->getDividedFeatures());

        return view('plans.features', compact('plan', 'limits', 'features'));
    }

    /**
     * دالة مساعدة لتصنيف ميزات وحدود المنظومة باستعلام واحد نظيف.
     */
    private function getDividedFeatures(): array
    {
        $allFeatures = Feature::all();

        return [
            'limits'   => $allFeatures->filter->isLimit(),
            'features' => $allFeatures->filter->isFeature(),
        ];
    }

    /**
     * حفظ وتحديث ميزات وحدود الباقة من الشاشة المخصصة عبر السيرفز.
     */
    public function updateFeatures(UpdatePlanFeaturesRequest $request, Plan $plan)
    {
        $this->planService->syncFeatures($plan, $request);

        return redirect()->route('plans.index')
            ->with('success', 'تم حفظ وتحديث ميزات وحدود الباقة بنجاح');
    }

    /**
     * استعراض مصفوفة مقارنة الميزات والحدود التشغيلية لكافة الباقات.
     */
    public function matrix()
    {
        $plans = Plan::with(['prices', 'features'])->get();
        $features = Feature::all();

        return view('plans.matrix', compact('plans', 'features'));
    }
}
