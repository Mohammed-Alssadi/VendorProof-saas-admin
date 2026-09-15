<?php

namespace App\Modules\Web\Services\Plans;

use App\Modules\Web\Http\Requests\Plans\StorePlanRequest;
use App\Modules\Web\Http\Requests\Plans\UpdatePlanRequest;
use App\Modules\Web\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanService
{
    /**
     * Create a new subscription plan with prices and features atomically.
     */
    public function createPlan(StorePlanRequest $request): Plan
    {
        return DB::transaction(function () use ($request) {
            $plan = Plan::create([
                'name'        => $request->name,
                'slug'        => $request->slug,
                'description' => $request->description,
                'status'      => $request->status,
            ]);

            $plan->prices()->create([
                'billing_cycle' => 'monthly',
                'price'         => $request->monthly_price,
                'currency'      => $request->currency,
                'status'        => 'active',
            ]);

            $plan->prices()->create([
                'billing_cycle' => 'yearly',
                'price'         => $request->yearly_price,
                'currency'      => $request->currency,
                'status'        => 'active',
            ]);

            $this->syncFeatures($plan, $request);

            return $plan;
        });
    }

    /**
     * Update an existing plan, its pricing tiers, and attached features.
     */
    public function updatePlan(Plan $plan, UpdatePlanRequest $request): Plan
    {
        return DB::transaction(function () use ($plan, $request) {
            $plan->update([
                'name'        => $request->name,
                'slug'        => $request->slug,
                'description' => $request->description,
                'status'      => $request->status,
            ]);

            $plan->prices()->updateOrCreate(
                ['billing_cycle' => 'monthly'],
                [
                    'price'    => $request->monthly_price,
                    'currency' => $request->currency,
                    'status'   => 'active',
                ]
            );

            $plan->prices()->updateOrCreate(
                ['billing_cycle' => 'yearly'],
                [
                    'price'    => $request->yearly_price,
                    'currency' => $request->currency,
                    'status'   => 'active',
                ]
            );

            $this->syncFeatures($plan, $request);

            return $plan;
        });
    }

    /**
     * Sync features to the plan, ignoring empty or zero values.
     */
    public function syncFeatures(Plan $plan, Request|array $request): void
    {
        $rawFeatures = is_array($request) ? ($request['features'] ?? $request) : $request->input('features', []);

        $cleanFeatures = [];
        foreach ($rawFeatures as $featureId => $data) {
            $value = is_array($data) ? ($data['value'] ?? null) : $data;

            if ($value === null || trim((string) $value) === '' || (string) $value === '0') {
                continue;
            }

            $cleanFeatures[$featureId] = ['value' => (string) $value];
        }

        $plan->features()->sync($cleanFeatures);
    }
}
