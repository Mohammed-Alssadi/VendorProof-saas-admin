<?php

namespace App\Modules\Api\Http\Controllers\Requirements;

use App\Modules\Api\Http\Controllers\ApiController;
use App\Modules\Api\Http\Requests\Requirements\StoreVendorRequirementRequest;
use App\Modules\Api\Http\Requests\Requirements\UpdateVendorRequirementRequest;
use App\Modules\Api\Http\Requests\Requirements\WaiveVendorRequirementRequest;
use App\Modules\Api\Models\Vendor;
use App\Modules\Api\Models\VendorRequirement;
use Illuminate\Http\JsonResponse;

/**
 * Manage vendor-specific compliance requirements, waivers, and score calculations.
 */
class ApiVendorRequirementController extends ApiController
{
    /**
     * List all requirements for a specific vendor with live compliance summary stats.
     */
    public function index(Vendor $vendor): JsonResponse
    {
        $requirements = $vendor->requirements()
            ->with('documentType')
            ->latest()
            ->get();

        $total = $requirements->count();
        $valid = $requirements->where('status', VendorRequirement::STATUS_VALID)->count();
        $waived = $requirements->where('status', VendorRequirement::STATUS_WAIVED)->count();
        $missing = $requirements->where('status', VendorRequirement::STATUS_MISSING)->count();
        $mandatory = $requirements->where('is_mandatory', true)->count();

        $fulfilled = $valid + $waived;
        $compliancePercentage = $total > 0 ? (int) round(($fulfilled / $total) * 100) : 100;

        $payload = [
            'requirements' => $requirements->map->toApiArray()->values(),
            'summary' => [
                'total' => $total,
                'mandatory' => $mandatory,
                'valid' => $valid,
                'waived' => $waived,
                'missing' => $missing,
                'compliance_percentage' => $compliancePercentage,
            ],
        ];

        return $this->successResponse($payload, 'تم جلب متطلبات المورد بنجاح');
    }

    /**
     * Attach a custom document requirement to a vendor.
     */
    public function store(StoreVendorRequirementRequest $request, Vendor $vendor): JsonResponse
    {
        $requirement = $vendor->requirements()->create($request->validated());
        $requirement->load('documentType');

        return $this->successResponse($requirement->toApiArray(), 'تمت إضافة المتطلب للمورد بنجاح', 201);
    }

    /**
     * Re-synchronize vendor requirements with the assigned category policy template.
     */
    public function sync(Vendor $vendor): JsonResponse
    {
        $vendor->syncCategoryRequirements();

        $requirements = $vendor->requirements()
            ->with('documentType')
            ->latest()
            ->get()
            ->map->toApiArray();

        return $this->successResponse($requirements, 'تمت مزامنة متطلبات المورد مع التصنيف بنجاح');
    }

    /**
     * Update individual requirement options (mandatory flag, notes, or document type).
     */
    public function update(UpdateVendorRequirementRequest $request, VendorRequirement $vendorRequirement): JsonResponse
    {
        $vendorRequirement->update($request->validated());
        $vendorRequirement->load('documentType');

        return $this->successResponse($vendorRequirement->toApiArray(), 'تم تحديث متطلب المورد بنجاح');
    }

    /**
     * Administratively waive a requirement with an official recorded reason.
     */
    public function waive(WaiveVendorRequirementRequest $request, VendorRequirement $vendorRequirement): JsonResponse
    {
        $vendorRequirement->waive($request->validated('reason'));
        $vendorRequirement->load('documentType');

        return $this->successResponse($vendorRequirement->toApiArray(), 'تم استثناء المتطلب إدارياً بنجاح');
    }

    /**
     * Revoke administrative waiver and return requirement to missing status.
     */
    public function unwaive(VendorRequirement $vendorRequirement): JsonResponse
    {
        $vendorRequirement->unwaive();
        $vendorRequirement->load('documentType');

        return $this->successResponse($vendorRequirement->toApiArray(), 'تم إلغاء الاستثناء الإداري بنجاح');
    }

    /**
     * Permanently remove a requirement from a vendor.
     */
    public function destroy(VendorRequirement $vendorRequirement): JsonResponse
    {
        $vendorRequirement->delete();

        return $this->successResponse(null, 'تم حذف المتطلب بنجاح');
    }
}
