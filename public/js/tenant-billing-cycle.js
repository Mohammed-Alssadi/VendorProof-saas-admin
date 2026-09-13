/**
 * VendorProof SaaS Admin - Tenant Plan & Billing Cycle Dynamic Controller
 * Pure Vanilla JS, zero inline handlers, strict event delegation.
 */
(function () {
    'use strict';

    function initTenantBillingCycle() {
        const planSelect = document.getElementById('tenantPlanSelect') || document.getElementById('editTenantPlan');
        const cycleSelect = document.getElementById('tenantBillingCycleSelect') || document.getElementById('editTenantBillingCycle');
        const cycleHint = document.getElementById('billingCycleHint');

        if (!planSelect || !cycleSelect) {
            return;
        }

        function updateBillingCycleUI() {
            const selectedOption = planSelect.options[planSelect.selectedIndex];
            if (!selectedOption) return;

            const isFree = selectedOption.getAttribute('data-is-free') === '1';
            const monthlyPrice = selectedOption.getAttribute('data-monthly-price');
            const yearlyPrice = selectedOption.getAttribute('data-yearly-price');
            const currency = selectedOption.getAttribute('data-currency') || 'ر.س';

            const monthlyOption = cycleSelect.querySelector('option[value="monthly"]');
            const yearlyOption = cycleSelect.querySelector('option[value="yearly"]');

            if (isFree) {
                cycleSelect.disabled = true;
                cycleSelect.value = 'monthly';
                if (monthlyOption) monthlyOption.textContent = 'شهري - غير مطبق (مجاني)';
                if (yearlyOption) yearlyOption.textContent = 'سنوي - غير مطبق (مجاني)';
                if (cycleHint) {
                    cycleHint.textContent = 'باقة مجانية دائمة مدى الحياة؛ لا تتطلب دورة فوترة ولا تنتهي صلاحيتها.';
                }
            } else {
                cycleSelect.disabled = false;
                if (monthlyOption) {
                    monthlyOption.textContent = monthlyPrice 
                        ? `شهرياً (${monthlyPrice} ${currency} / شهر)`
                        : 'شهرياً';
                }
                if (yearlyOption) {
                    yearlyOption.textContent = yearlyPrice 
                        ? `سنوياً (${yearlyPrice} ${currency} / سنة)`
                        : 'سنوياً';
                }
                if (cycleHint) {
                    cycleHint.textContent = 'تحدد دورة الفوترة تاريخ نهاية الفترة وتجديد العقد تلقائياً.';
                }
            }
        }

        // الاستماع لتغيير الباقة
        planSelect.addEventListener('change', updateBillingCycleUI);

        // تهيئة الحالة الأولية عند تحميل الصفحة
        updateBillingCycleUI();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTenantBillingCycle);
    } else {
        initTenantBillingCycle();
    }
})();
