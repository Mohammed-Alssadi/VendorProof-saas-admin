<?php

namespace App\Modules\Web\Http\Controllers\Subscriptions;

use App\Models\Tenant;
use App\Modules\Web\Http\Controllers\Controller;
use App\Modules\Web\Models\Subscription;

/**
 * متحكم استعراض سجلات وعقود الاشتراكات بالمنصة (Subscriptions Contract Controller).
 *
 * مخصص لاستعراض السجلات التعاقدية والمالية للاشتراكات وتواريخ الفترات والتجديد.
 */
class SubscriptionController extends Controller
{
    /**
     * استعراض قائمة عقود واشتراكات المنشآت وتفاصيل الفترات والأسعار.
     */
    public function index()
    {
        $subscribers = Tenant::with(['plan', 'currentSubscription.planPrice'])
            ->latest()
            ->paginate(15);

        return view('subscriptions.index', compact('subscribers'));
    }
}
