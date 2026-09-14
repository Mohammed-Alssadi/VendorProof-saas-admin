<?php

namespace App\Modules\Api\Http\Controllers\Vendors;

use App\Modules\Api\Http\Controllers\ApiController;
use App\Modules\Api\Http\Requests\Vendors\StoreVendorCategoryRequest;
use App\Modules\Api\Http\Requests\Vendors\UpdateVendorCategoryRequest;
use App\Modules\Api\Models\Vendors\VendorCategory;
use Illuminate\Http\JsonResponse;

/**
 * متحكم واجهات تصنيفات الموردين (Vendor Categories API Controller).
 *
 * يعتمد على Route Model Binding وبساطة الكود ونظافته.
 */
class ApiVendorCategoryController extends ApiController
{
    /**
     * جلب كافة تصنيفات الموردين للمنشأة.
     */
    public function index(): JsonResponse
    {
        $categories = VendorCategory::latest()->get()->map->toApiArray();

        return $this->successResponse($categories, 'تم جلب تصنيفات الموردين بنجاح');
    }

    /**
     * حفظ تصنيف موردين جديد.
     */
    public function store(StoreVendorCategoryRequest $request): JsonResponse
    {
        $category = VendorCategory::create($request->validated());

        return $this->successResponse($category->toApiArray(), 'تم إنشاء التصنيف بنجاح', 201);
    }

    /**
     * عرض تفاصيل تصنيف محدد (Route Model Binding).
     */
    public function show(VendorCategory $vendorCategory): JsonResponse
    {
        return $this->successResponse($vendorCategory->toApiArray(), 'تم جلب تفاصيل التصنيف بنجاح');
    }

    /**
     * تحديث بيانات التصنيف (Route Model Binding).
     */
    public function update(UpdateVendorCategoryRequest $request, VendorCategory $vendorCategory): JsonResponse
    {
        $vendorCategory->update($request->validated());

        return $this->successResponse($vendorCategory->toApiArray(), 'تم تحديث التصنيف بنجاح');
    }

    /**
     * حذف التصنيف (Route Model Binding).
     */
    public function destroy(VendorCategory $vendorCategory): JsonResponse
    {
        $vendorCategory->delete();

        return $this->successResponse(null, 'تم حذف التصنيف بنجاح');
    }
}
