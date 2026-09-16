<?php

namespace App\Modules\Api\Http\Controllers\Plans;

use App\Modules\Api\Http\Controllers\ApiController;
use App\Modules\Web\Models\Plan;

/**
 * Public subscription plans and pricing catalog.
 */
class ApiPlanController extends ApiController
{
    /**
     * List active subscription plans.
     *
     * Returns publicly available subscription plans with monthly and yearly pricing,
     * currency symbols, and pre-formatted marketing checklists for landing pages.
     */
    public function index()
    {
        $plans = Plan::active()
            ->with(['monthlyPrice', 'yearlyPrice', 'features'])
            ->get();

        $data = $plans->map(function (Plan $plan) {
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'prices' => [
                    'monthly' => $plan->monthlyPrice ? [
                        'id' => $plan->monthlyPrice->id,
                        'price' => (string) $plan->monthlyPrice->price,
                        'currency' => $plan->monthlyPrice->currency,
                        'currency_symbol' => $plan->monthlyPrice->currency_symbol,
                        'billing_cycle' => 'monthly',
                    ] : null,
                    'yearly' => $plan->yearlyPrice ? [
                        'id' => $plan->yearlyPrice->id,
                        'price' => (string) $plan->yearlyPrice->price,
                        'currency' => $plan->yearlyPrice->currency,
                        'currency_symbol' => $plan->yearlyPrice->currency_symbol,
                        'billing_cycle' => 'yearly',
                    ] : null,
                ],
                'checklist' => $plan->checklist(),
            ];
        });

        return $this->successResponse($data, 'تم استرجاع باقات المنصة بنجاح.');
    }
}
