/**
 * سكربت تفاعلات شاشة المدفوعات (VendorProof Payments JS)
 *
 * معزول تماماً ومطابق لمعايير المنصة (Zero Inline JS & Zero Inline Handlers).
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. نسخ المعرف المرجعي إلى الحافظة بنقرة واحدة مع تغذية بصرية
    document.addEventListener('click', (e) => {
        const copyBtn = e.target.closest('.js-copy-ref');
        if (!copyBtn) return;

        const textToCopy = copyBtn.getAttribute('data-copy');
        if (!textToCopy) return;

        navigator.clipboard.writeText(textToCopy).then(() => {
            const icon = copyBtn.querySelector('i');
            if (icon) {
                const originalClass = icon.className;
                icon.className = 'ri-check-line';
                copyBtn.style.color = 'var(--color-success)';
                setTimeout(() => {
                    icon.className = originalClass;
                    copyBtn.style.color = '';
                }, 1500);
            }
        });
    });

    // نسخ المعرف المرجعي إلى الحافظة بنقرة واحدة
});
