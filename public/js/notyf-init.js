/**
 * VendorProof SaaS Admin - Centralized Notyf Notification Engine
 * High-End Toast Toasts with Seamless Laravel Session Binding
 */

(function () {
    'use strict';

    function initNotyf() {
        if (typeof Notyf === 'undefined') return;

        // تهيئة كائن Notyf المركزي بهوية وتصميم المنصة
        window.notyf = new Notyf({
            duration: 3500,
            position: { x: 'left', y: 'top' },
            dismissible: false,
            ripple: false,
            types: [
                {
                    type: 'success',
                    background: '#15803d',
                    icon: {
                        className: 'ri-checkbox-circle-fill',
                        tagName: 'i',
                        color: '#ffffff'
                    }
                },
                {
                    type: 'error',
                    background: '#b91c1c',
                    icon: {
                        className: 'ri-error-warning-fill',
                        tagName: 'i',
                        color: '#ffffff'
                    }
                }
            ]
        });

        // قراءة رسائل الجلسة من حاوية البيانات الصامتة flashToastData
        const flashEl = document.getElementById('flashToastData');
        if (flashEl) {
            const successMsg = flashEl.getAttribute('data-success');
            const errorMsg = flashEl.getAttribute('data-error');
            const validationErrorsJson = flashEl.getAttribute('data-errors');

            if (successMsg && successMsg.trim() !== '') {
                window.notyf.success(successMsg);
            }

            if (errorMsg && errorMsg.trim() !== '') {
                window.notyf.error(errorMsg);
            }

            if (validationErrorsJson && validationErrorsJson.trim() !== '') {
                try {
                    const validationErrors = JSON.parse(validationErrorsJson);
                    if (Array.isArray(validationErrors)) {
                        validationErrors.forEach(msg => {
                            if (msg) window.notyf.error(msg);
                        });
                    }
                } catch (e) {
                    // تجاهل أي خطأ في التحليل
                }
            }
        }
    }

    document.addEventListener('DOMContentLoaded', initNotyf);
})();
