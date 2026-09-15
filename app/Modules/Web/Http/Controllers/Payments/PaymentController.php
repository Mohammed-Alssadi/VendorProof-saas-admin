<?php

namespace App\Modules\Web\Http\Controllers\Payments;

use App\Models\Payment;
use App\Modules\Web\Http\Controllers\Controller;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with(['tenant', 'plan'])
            ->latest()
            ->paginate(15);

        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        $paidCount    = Payment::where('status', 'paid')->count();

        return view('payments.index', compact('payments', 'totalRevenue', 'paidCount'));
    }

    public function show(Payment $payment)
    {
        $payment->loadMissing(['tenant', 'plan']);

        return view('payments.show', compact('payment'));
    }
}
