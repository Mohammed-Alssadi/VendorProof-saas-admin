/**
 * VendorProof SaaS Admin - Plan Limits & Features Toggle Engine
 * Clean event delegation for unlimited toggles without inline onchange handlers
 */

(function () {
    'use strict';

    function initPlanLimits() {
        // تهيئة الحالة الأولية عند تحميل الصفحة (في حال كانت الحقول معبأة مسبقاً أو في شاشات التعديل)
        document.querySelectorAll('.limit-unlimited-checkbox').forEach(function (checkbox) {
            const targetId = checkbox.getAttribute('data-target');
            if (!targetId) return;
            const targetInput = document.getElementById(targetId);
            if (targetInput && checkbox.checked) {
                targetInput.disabled = true;
                targetInput.placeholder = 'غير محدود';
            }
        });

        // تفويض الأحداث عند تغيير حالة التحديد لخانة "غير محدود"
        document.addEventListener('change', function (e) {
            const checkbox = e.target.closest('.limit-unlimited-checkbox');
            if (!checkbox) return;

            const targetId = checkbox.getAttribute('data-target');
            if (!targetId) return;

            const targetInput = document.getElementById(targetId);
            if (targetInput) {
                targetInput.disabled = checkbox.checked;
                if (checkbox.checked) {
                    targetInput.placeholder = 'غير محدود';
                } else {
                    targetInput.placeholder = '0';
                    if (targetInput.value === '') {
                        targetInput.value = '0';
                    }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initPlanLimits);
})();
