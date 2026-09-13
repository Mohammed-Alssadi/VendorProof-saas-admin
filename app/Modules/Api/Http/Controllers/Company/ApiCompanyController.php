<?php

namespace App\Modules\Api\Http\Controllers\Company;

use App\Modules\Api\Http\Controllers\ApiController;
use App\Modules\Api\Http\Requests\Company\CloseCompanyAccountRequest;
use App\Modules\Api\Http\Requests\Company\UpdateCompanyProfileRequest;
use App\Modules\Api\Services\Company\CompanyService;
use Illuminate\Http\Request;

/**
 * Manage subscriber company identity, profile, and lifecycle.
 */
class ApiCompanyController extends ApiController
{
    /**
     * Get subscriber company profile.
     *
     * Returns company identity attributes, commercial registration, contact details,
     * and current subscription plan entitlements.
     */
    public function show(Request $request)
    {
        $tenant = $request->user()->tenant;

        if (! $tenant) {
            return $this->errorResponse('لا توجد منشأة مرتبطة بحسابك الحالي.', 404);
        }

        return $this->successResponse($tenant->toApiArray());
    }

    /**
     * Update company profile and contact details.
     *
     * Updates official company information such as commercial name, registration number,
     * and business contact information.
     */
    public function update(UpdateCompanyProfileRequest $request)
    {
        $tenant = $request->user()->tenant;

        if (! $tenant) {
            return $this->errorResponse('لا توجد منشأة مرتبطة بحسابك الحالي لتحديثها.', 404);
        }

        $tenant->update([
            'name'       => $request->name,
            'company_cr' => $request->company_cr,
            'email'      => $request->email,
            'phone'      => $request->phone,
        ]);

        return $this->successResponse($tenant->toApiArray(), 'تم تحديث بيانات المنشأة بنجاح.');
    }

    /**
     * Terminate and close subscriber company account.
     *
     * Soft-deletes the company profile, cancels active subscriptions, and revokes
     * all access tokens for every associated team member after credentials verification.
     */
    public function destroy(CloseCompanyAccountRequest $request, CompanyService $companyService)
    {
        $tenant = $request->user()->tenant;

        if (! $tenant) {
            return $this->errorResponse('لا توجد منشأة مرتبطة بحسابك الحالي لإغلاقها.', 404);
        }

        $companyService->closeAccount($tenant);

        return $this->successResponse(null, 'تم إغلاق وحذف حساب المنشأة بنجاح وإنهاء كافة جلسات الوصول.');
    }
}
