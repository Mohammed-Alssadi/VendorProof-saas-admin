<?php

namespace App\Modules\Api\Services\Company;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Service to manage company lifecycle operations within the API module.
 */
class CompanyService
{
    /**
     * Terminate and close a company account with atomic consistency.
     *
     * Cancels subscriptions, revokes all user bearer tokens, marks status as closed,
     * and performs soft deletion on the tenant and user records.
     */
    public function closeAccount(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            // 1. إلغاء كافة الاشتراكات النشطة للمنشأة
            $tenant->subscriptions()->update([
                'status' => 'canceled',
            ]);

            // 2. إبطال وطرد كافة المستخدمين التابعين للمنشأة بإلغاء توكناتهم فوراً
            foreach ($tenant->users as $tenantUser) {
                $tenantUser->tokens()->delete();
            }

            // 3. تحديث حالة المنشأة وتطبيق الحذف الناعم (Soft Delete)
            $tenant->update(['status' => 'closed']);
            $tenant->users()->delete();
            $tenant->delete();
        });
    }
}
