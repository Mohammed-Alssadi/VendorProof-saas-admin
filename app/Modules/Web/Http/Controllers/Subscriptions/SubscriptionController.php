<?php

namespace App\Modules\Web\Http\Controllers\Subscriptions;

use App\Models\Tenant;
use App\Modules\Web\Http\Controllers\Controller;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscribers = Tenant::with(['plan', 'currentSubscription.planPrice'])
            ->latest()
            ->paginate(15);

        return view('subscriptions.index', compact('subscribers'));
    }
}
