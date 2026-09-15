<?php

namespace App\Modules\Api\Http\Controllers\Vendors;

use App\Modules\Api\Http\Controllers\ApiController;
use App\Modules\Api\Http\Requests\Vendors\StoreVendorRequest;
use App\Modules\Api\Http\Requests\Vendors\UpdateVendorRequest;
use App\Modules\Api\Models\Vendors\Vendor;
use Illuminate\Http\JsonResponse;

/**
 * Manage tenant vendors via a RESTful resource interface.
 *
 * All queries are automatically scoped to the authenticated tenant
 * via the BelongsToTenant global scope. Route Model Binding enforces
 * ownership on single-resource endpoints.
 */
class ApiVendorController extends ApiController
{
    /**
     * List all vendors belonging to the authenticated tenant.
     */
    public function index(): JsonResponse
    {
        $vendors = Vendor::with('category')->latest()->get()->map->toApiArray();

        return $this->successResponse($vendors, 'تم جلب الموردين بنجاح');
    }

    /**
     * Create a new vendor under the authenticated tenant.
     */
    public function store(StoreVendorRequest $request): JsonResponse
    {
        $vendor = Vendor::create($request->validated());

        return $this->successResponse($vendor->load('category')->toApiArray(), 'تم إضافة المورد بنجاح', 201);
    }

    /**
     * Retrieve a single vendor record.
     */
    public function show(Vendor $vendor): JsonResponse
    {
        return $this->successResponse($vendor->load('category')->toApiArray(), 'تم جلب تفاصيل المورد بنجاح');
    }

    /**
     * Update vendor profile and contact details.
     */
    public function update(UpdateVendorRequest $request, Vendor $vendor): JsonResponse
    {
        $vendor->update($request->validated());

        return $this->successResponse($vendor->load('category')->toApiArray(), 'تم تحديث بيانات المورد بنجاح');
    }

    /**
     * Permanently delete a vendor record.
     */
    public function destroy(Vendor $vendor): JsonResponse
    {
        $vendor->delete();

        return $this->successResponse(null, 'تم حذف المورد بنجاح');
    }
}
