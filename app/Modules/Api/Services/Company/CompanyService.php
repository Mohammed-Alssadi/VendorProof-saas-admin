<?php

namespace App\Modules\Api\Services\Company;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class CompanyService
{
    /**
     * Atomically close the company account.
     *
     * Cancels all subscriptions, revokes every user token, marks the tenant
     * as closed, and soft-deletes users and the tenant record.
     */
    public function closeAccount(Tenant $tenant): void
    {
        DB::transaction(function () use ($tenant) {
            $tenant->subscriptions()->update(['status' => 'canceled']);

            foreach ($tenant->users as $user) {
                $user->tokens()->delete();
            }

            $tenant->update(['status' => 'closed']);
            $tenant->users()->delete();
            $tenant->delete();
        });
    }
}
