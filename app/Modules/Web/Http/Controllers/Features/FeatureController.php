<?php

namespace App\Modules\Web\Http\Controllers\Features;

use App\Modules\Web\Http\Controllers\Controller;
use App\Modules\Web\Http\Requests\Features\StoreFeatureRequest;
use App\Modules\Web\Http\Requests\Features\UpdateFeatureRequest;
use App\Modules\Web\Models\Feature;

/**
 * Controller for managing system features and usage limits dictionary.
 */
class FeatureController extends Controller
{
    public function index()
    {
        $features = Feature::withCount('plans')->latest()->get();

        return view('features.index', compact('features'));
    }

    public function create()
    {
        return view('features.create');
    }

    public function store(StoreFeatureRequest $request)
    {
        Feature::create($request->validated());

        return redirect()->route('features.index')
            ->with('success', 'تمت إضافة الميزة التشغيلية بنجاح إلى معجم المنظومة');
    }

    public function show(Feature $feature)
    {
        return redirect()->route('features.edit', $feature);
    }

    public function edit(Feature $feature)
    {
        return view('features.edit', compact('feature'));
    }

    public function update(UpdateFeatureRequest $request, Feature $feature)
    {
        $feature->update($request->validated());

        return redirect()->route('features.index')
            ->with('success', 'تم تحديث بيانات الميزة التشغيلية بنجاح');
    }

    public function destroy(Feature $feature)
    {
        if ($feature->plans()->wherePivotNotIn('value', ['0', ''])->exists()) {
            return redirect()->route('features.index')
                ->with('error', 'لا يمكن حذف الميزة لأنها مرتبطة بباقات اشتراك حالية.');
        }

        $feature->delete();

        return redirect()->route('features.index')
            ->with('success', 'تم حذف الميزة التشغيلية بنجاح');
    }
}
