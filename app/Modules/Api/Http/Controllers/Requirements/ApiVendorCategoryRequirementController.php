<?php

namespace App\Modules\Api\Http\Controllers\Requirements;

use App\Modules\Api\Http\Controllers\ApiController;
use App\Modules\Api\Http\Requests\Requirements\StoreCategoryRequirementRequest;
use App\Modules\Api\Http\Requests\Requirements\UpdateCategoryRequirementRequest;
use App\Modules\Api\Models\VendorCategory;
use App\Modules\Api\Models\VendorCategoryRequirement;
use Illuminate\Http\JsonResponse;

/**
 * Manage compliance requirement templates for vendor categories.
 */
class ApiVendorCategoryRequirementController extends ApiController
{
    /**
     * List all requirements configured for a specific vendor category.
     */
    public function index(VendorCategory $vendorCategory): JsonResponse
    {
        $requirements = $vendorCategory->requirements()
            ->with('documentType')
            ->latest()
            ->get()
            ->map->toApiArray();

        return $this->successResponse($requirements, 'تم جلب متطلبات التصنيف بنجاح');
    }

    /**
     * Attach a document type requirement to a vendor category.
     */
    public function store(StoreCategoryRequirementRequest $request, VendorCategory $vendorCategory): JsonResponse
    {
        $requirement = $vendorCategory->requirements()->create($request->validated());
        $requirement->load('documentType');

        return $this->successResponse($requirement->toApiArray(), 'تمت إضافة متطلب التصنيف بنجاح', 201);
    }

    /**
     * Update requirement options (mandatory status or instructions).
     */
    public function update(UpdateCategoryRequirementRequest $request, VendorCategoryRequirement $categoryRequirement): JsonResponse
    {
        $categoryRequirement->update($request->validated());
        $categoryRequirement->load('documentType');

        return $this->successResponse($categoryRequirement->toApiArray(), 'تم تحديث متطلب التصنيف بنجاح');
    }

    /**
     * Remove a document requirement from a vendor category.
     */
    public function destroy(VendorCategoryRequirement $categoryRequirement): JsonResponse
    {
        $categoryRequirement->delete();

        return $this->successResponse(null, 'تم حذف متطلب التصنيف بنجاح');
    }
}
