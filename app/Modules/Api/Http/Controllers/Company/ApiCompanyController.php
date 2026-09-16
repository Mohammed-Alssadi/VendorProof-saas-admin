<?php

namespace App\Modules\Api\Http\Controllers\Company;

use App\Modules\Api\Http\Controllers\ApiController;
use App\Modules\Api\Http\Requests\Company\CloseCompanyAccountRequest;
use App\Modules\Api\Http\Requests\Company\UpdateCompanyProfileRequest;
use App\Modules\Api\Services\Company\CompanyService;
use Illuminate\Http\Request;

class ApiCompanyController extends ApiController
{
    public function show(Request $request)
    {
        $tenant = $request->user()->tenant;

        if (! $tenant) {
            return $this->errorResponse('لا توجد منشأة مرتبطة بحسابك الحالي.', 404);
        }

        return $this->successResponse($tenant->toApiArray());
    }

    public function update(UpdateCompanyProfileRequest $request)
    {
        $tenant = $request->user()->tenant;

        if (! $tenant) {
            return $this->errorResponse('لا توجد منشأة مرتبطة بحسابك الحالي لتحديثها.', 404);
        }

        $tenant->update([
            'name' => $request->name,
            'company_cr' => $request->company_cr,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return $this->successResponse($tenant->toApiArray(), 'تم تحديث بيانات المنشأة بنجاح.');
    }

    /**
     * Terminate the company account after credentials verification.
     *
     * Cancels subscriptions, revokes all tokens, and soft-deletes the tenant.
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
