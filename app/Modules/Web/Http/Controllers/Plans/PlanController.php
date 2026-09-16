<?php

namespace App\Modules\Web\Http\Controllers\Plans;

use App\Modules\Web\Http\Controllers\Controller;
use App\Modules\Web\Http\Requests\Plans\StorePlanRequest;
use App\Modules\Web\Http\Requests\Plans\UpdatePlanFeaturesRequest;
use App\Modules\Web\Http\Requests\Plans\UpdatePlanRequest;
use App\Modules\Web\Models\Feature;
use App\Modules\Web\Models\Plan;
use App\Modules\Web\Services\Plans\PlanService;

class PlanController extends Controller
{
    public function __construct(
        protected PlanService $planService
    ) {}

    public function index()
    {
        $plans = Plan::with(['prices', 'features'])->withCount('subscriptions')->get();

        return view('plans.index', compact('plans'));
    }

    public function create()
    {
        extract($this->getDividedFeatures());

        return view('plans.create', compact('limits', 'features'));
    }

    public function store(StorePlanRequest $request)
    {
        $this->planService->createPlan($request);

        return redirect()->route('plans.index')
            ->with('success', 'تم إنشاء خطة الاشتراك والأسعار وحدودها بنجاح');
    }

    public function show(Plan $plan)
    {
        $plan->load(['prices', 'features'])->loadCount('tenants');
        extract($this->getDividedFeatures());

        return view('plans.show', compact('plan', 'limits', 'features'));
    }

    public function edit(Plan $plan)
    {
        $plan->load(['prices', 'features']);
        extract($this->getDividedFeatures());

        return view('plans.edit', compact('plan', 'limits', 'features'));
    }

    public function update(UpdatePlanRequest $request, Plan $plan)
    {
        $this->planService->updatePlan($plan, $request);

        return redirect()->route('plans.index')
            ->with('success', 'تم تحديث خطة الاشتراك وأسعارها بنجاح');
    }

    /**
     * Delete a plan only when no active tenants or subscriptions are linked.
     */
    public function destroy(Plan $plan)
    {
        if ($plan->tenants()->exists() || $plan->subscriptions()->exists()) {
            return redirect()->route('plans.index')
                ->with('error', 'لا يمكن حذف الباقة لوجود اشتراكات منشآت نشطة مرتبطة بها.');
        }

        $plan->delete();

        return redirect()->route('plans.index')
            ->with('success', 'تم حذف خطة الاشتراك بنجاح من النظام');
    }

    public function features(Plan $plan)
    {
        $plan->load('features');
        extract($this->getDividedFeatures());

        return view('plans.features', compact('plan', 'limits', 'features'));
    }

    public function updateFeatures(UpdatePlanFeaturesRequest $request, Plan $plan)
    {
        $this->planService->syncFeatures($plan, $request);

        return redirect()->route('plans.index')
            ->with('success', 'تم حفظ وتحديث ميزات وحدود الباقة بنجاح');
    }

    public function matrix()
    {
        $plans = Plan::with(['prices', 'features'])->get();
        $features = Feature::all();

        return view('plans.matrix', compact('plans', 'features'));
    }

    private function getDividedFeatures(): array
    {
        $allFeatures = Feature::all();

        return [
            'limits' => $allFeatures->filter->isLimit(),
            'features' => $allFeatures->filter->isFeature(),
        ];
    }
}
