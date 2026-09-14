<?php

namespace App\Modules\Web\Http\Controllers\Payments;

use App\Models\Payment;
use App\Modules\Web\Http\Controllers\Controller;

/**
 * متحكم سجل المدفوعات والفواتير للمنصة (Payment Controller).
 *
 * مخصص حصرياً للمشرف في بيئة الويب لاستعراض العمليات المالية والفواتير بقوالب Blade الطبيعية.
 */
class PaymentController extends Controller
{
    /**
     * استعراض قائمة العمليات المالية.
     */
    public function index()
    {
        $payments = Payment::with(['tenant', 'plan'])
            ->latest()
            ->paginate(15);

        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        $paidCount    = Payment::where('status', 'paid')->count();

        return view('payments.index', compact('payments', 'totalRevenue', 'paidCount'));
    }

    /**
     * استعراض صفحة تفاصيل الفاتورة والمعاملة المحددة.
     */
    public function show(Payment $payment)
    {
        $payment->loadMissing(['tenant', 'plan']);

        return view('payments.show', compact('payment'));
    }
}
