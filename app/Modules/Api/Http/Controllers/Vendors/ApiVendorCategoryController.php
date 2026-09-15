<?php

namespace App\Modules\Api\Http\Controllers\Vendors;

use App\Modules\Api\Http\Controllers\ApiController;
use App\Modules\Api\Http\Requests\Vendors\StoreVendorCategoryRequest;
use App\Modules\Api\Http\Requests\Vendors\UpdateVendorCategoryRequest;
use App\Modules\Api\Models\Vendors\VendorCategory;
use Illuminate\Http\JsonResponse;

/**
 * Manage vendor classification categories for the authenticated tenant.
 *
 * Tenant isolation is enforced automatically via BelongsToTenant.
 * Route Model Binding restricts single-resource operations to owned records.
 */
class ApiVendorCategoryController extends ApiController
{
    /**
     * List all vendor categories belonging to the authenticated tenant.
     */
    public function index(): JsonResponse
    {
        $categories = VendorCategory::latest()->get()->map->toApiArray();

        return $this->successResponse($categories, 'تم جلب تصنيفات الموردين بنجاح');
    }

    /**
     * Create a new vendor category.
     */
    public function store(StoreVendorCategoryRequest $request): JsonResponse
    {
        $category = VendorCategory::create($request->validated());

        return $this->successResponse($category->toApiArray(), 'تم إنشاء التصنيف بنجاح', 201);
    }

    /**
     * Retrieve a single vendor category.
     */
    public function show(VendorCategory $vendorCategory): JsonResponse
    {
        return $this->successResponse($vendorCategory->toApiArray(), 'تم جلب تفاصيل التصنيف بنجاح');
    }

    /**
     * Update vendor category details.
     */
    public function update(UpdateVendorCategoryRequest $request, VendorCategory $vendorCategory): JsonResponse
    {
        $vendorCategory->update($request->validated());

        return $this->successResponse($vendorCategory->toApiArray(), 'تم تحديث التصنيف بنجاح');
    }

    /**
     * Permanently delete a vendor category.
     */
    public function destroy(VendorCategory $vendorCategory): JsonResponse
    {
        $vendorCategory->delete();

        return $this->successResponse(null, 'تم حذف التصنيف بنجاح');
    }
}
