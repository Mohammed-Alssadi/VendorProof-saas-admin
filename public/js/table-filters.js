/**
 * VendorProof SaaS Admin - Table Filtering Engine
 * Live search, multi-criteria dropdown filters, and dynamic empty search states
 */

(function () {
    'use strict';

    /**
     * تحديث أو إظهار/إخفاء صف عدم وجود نتائج للفلترة
     */
    function updateNoResultsState(tbody, visibleCount, colSpan, onReset) {
        if (!tbody) return;
        let noResultsRow = tbody.querySelector('.table-no-results-row');

        // إذا كان الجدول يحتوي أصلاً على صف خلو بيانات أساسي (Empty State)، لا نتدخل
        const hasBaseEmptyRow = tbody.querySelector('.table-empty-row');
        if (hasBaseEmptyRow && tbody.querySelectorAll('tr:not(.table-empty-row):not(.table-no-results-row)').length === 0) {
            if (noResultsRow) noResultsRow.remove();
            return;
        }

        if (visibleCount === 0) {
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.className = 'table-no-results-row';
                noResultsRow.innerHTML = `
                    <td colspan="${colSpan}">
                        <div class="table-empty-wrap">
                            <div class="empty-icon-box">
                                <i class="ri-search-line"></i>
                            </div>
                            <h4 class="empty-title">لم يتم العثور على أي نتائج</h4>
                            <p class="empty-desc">لا توجد سجلات تطابق كلمات البحث أو الفلاتر المحددة حالياً. يمكنك تعديل البحث أو إعادة ضبط التصفية.</p>
                            <div class="empty-actions">
                                <button type="button" class="btn-empty-reset">
                                    <i class="ri-refresh-line"></i>
                                    <span>إعادة ضبط الفلاتر</span>
                                </button>
                            </div>
                        </div>
                    </td>
                `;
                const resetBtn = noResultsRow.querySelector('.btn-empty-reset');
                if (resetBtn && typeof onReset === 'function') {
                    resetBtn.addEventListener('click', onReset);
                }
                tbody.appendChild(noResultsRow);
            } else {
                noResultsRow.style.display = '';
            }
        } else {
            if (noResultsRow) {
                noResultsRow.style.display = 'none';
            }
        }
    }

    function initTableFilters() {
        // 1. فلترة جدول المنشآت والمشتركين (Tenants Table)
        const tenantSearch = document.getElementById('tenantSearchInput');
        const tenantStatus = document.getElementById('statusFilter');
        const tenantCycle = document.getElementById('cycleFilter');
        const tenantTable = document.getElementById('tenantsDataTable');
        const tenantTbody = tenantTable ? tenantTable.querySelector('tbody') : null;

        function filterTenants() {
            if (!tenantTbody) return;
            const rows = tenantTbody.querySelectorAll('tr:not(.table-no-results-row):not(.table-empty-row)');
            const query = tenantSearch ? tenantSearch.value.toLowerCase().trim() : '';
            const status = tenantStatus ? tenantStatus.value : 'all';
            const cycle = tenantCycle ? tenantCycle.value : 'all';
            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const rowStatus = row.getAttribute('data-status');
                const rowCycle = row.getAttribute('data-cycle');

                const matchesQuery = query === '' || text.includes(query);
                const matchesStatus = status === 'all' || rowStatus === status;
                const matchesCycle = cycle === 'all' || rowCycle === cycle;

                if (matchesQuery && matchesStatus && matchesCycle) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            updateNoResultsState(tenantTbody, visibleCount, 6, resetTenants);
        }

        function resetTenants() {
            if (tenantSearch) tenantSearch.value = '';
            if (tenantStatus) tenantStatus.value = 'all';
            if (tenantCycle) tenantCycle.value = 'all';
            filterTenants();
        }

        if (tenantSearch) tenantSearch.addEventListener('input', filterTenants);
        if (tenantStatus) tenantStatus.addEventListener('change', filterTenants);
        if (tenantCycle) tenantCycle.addEventListener('change', filterTenants);

        // 2. فلترة جدول الاشتراكات والعمليات المالية (Subscriptions Table)
        const subSearch = document.getElementById('subSearchInput');
        const subStatus = document.getElementById('subStatusFilter');
        const subCycle = document.getElementById('subCycleFilter');
        const subTable = document.getElementById('subscriptionsDataTable');
        const subTbody = subTable ? subTable.querySelector('tbody') : null;

        function filterSubscriptions() {
            if (!subTbody) return;
            const rows = subTbody.querySelectorAll('tr:not(.table-no-results-row):not(.table-empty-row)');
            const query = subSearch ? subSearch.value.toLowerCase().trim() : '';
            const status = subStatus ? subStatus.value : 'all';
            const cycle = subCycle ? subCycle.value : 'all';
            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const rowStatus = row.getAttribute('data-status');
                const rowCycle = row.getAttribute('data-cycle');

                const matchesQuery = query === '' || text.includes(query);
                const matchesStatus = status === 'all' || rowStatus === status;
                const matchesCycle = cycle === 'all' || rowCycle === cycle;

                if (matchesQuery && matchesStatus && matchesCycle) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            updateNoResultsState(subTbody, visibleCount, 8, resetSubscriptions);
        }

        function resetSubscriptions() {
            if (subSearch) subSearch.value = '';
            if (subStatus) subStatus.value = 'all';
            if (subCycle) subCycle.value = 'all';
            filterSubscriptions();
        }

        if (subSearch) subSearch.addEventListener('input', filterSubscriptions);
        if (subStatus) subStatus.addEventListener('change', filterSubscriptions);
        if (subCycle) subCycle.addEventListener('change', filterSubscriptions);

        // 3. فلترة جدول ميزات النظام وحدود الباقات (Features Table)
        const featSearch = document.getElementById('featureSearchInput');
        const featType = document.getElementById('featureTypeFilter');
        const featTable = document.getElementById('featuresDataTable');
        const featTbody = featTable ? featTable.querySelector('tbody') : null;

        function filterFeatures() {
            if (!featTbody) return;
            const rows = featTbody.querySelectorAll('tr:not(.table-no-results-row):not(.table-empty-row)');
            const query = featSearch ? featSearch.value.toLowerCase().trim() : '';
            const type = featType ? featType.value : 'all';
            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const rowType = row.getAttribute('data-type');

                const matchesQuery = query === '' || text.includes(query);
                const matchesType = type === 'all' || rowType === type;

                if (matchesQuery && matchesType) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            updateNoResultsState(featTbody, visibleCount, 5, resetFeatures);
        }

        function resetFeatures() {
            if (featSearch) featSearch.value = '';
            if (featType) featType.value = 'all';
            filterFeatures();
        }

        if (featSearch) featSearch.addEventListener('input', filterFeatures);
        if (featType) featType.addEventListener('change', filterFeatures);

        // 4. فلترة جدول المدفوعات والعمليات المالية (Payments Table)
        const paymentSearch = document.getElementById('paymentSearchInput');
        const paymentStatus = document.getElementById('paymentStatusFilter');
        const paymentCycle = document.getElementById('paymentCycleFilter');
        const paymentTable = document.getElementById('paymentsDataTable');
        const paymentTbody = paymentTable ? paymentTable.querySelector('tbody') : null;

        function filterPayments() {
            if (!paymentTbody) return;
            const rows = paymentTbody.querySelectorAll('tr:not(.table-no-results-row):not(.table-empty-row)');
            const query = paymentSearch ? paymentSearch.value.toLowerCase().trim() : '';
            const status = paymentStatus ? paymentStatus.value : 'all';
            const cycle = paymentCycle ? paymentCycle.value : 'all';
            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const rowStatus = row.getAttribute('data-status');
                const rowCycle = row.getAttribute('data-cycle');

                const matchesQuery = query === '' || text.includes(query);
                const matchesStatus = status === 'all' || rowStatus === status;
                const matchesCycle = cycle === 'all' || rowCycle === cycle;

                if (matchesQuery && matchesStatus && matchesCycle) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            updateNoResultsState(paymentTbody, visibleCount, 8, resetPayments);
        }

        function resetPayments() {
            if (paymentSearch) paymentSearch.value = '';
            if (paymentStatus) paymentStatus.value = 'all';
            if (paymentCycle) paymentCycle.value = 'all';
            filterPayments();
        }

        if (paymentSearch) paymentSearch.addEventListener('input', filterPayments);
        if (paymentStatus) paymentStatus.addEventListener('change', filterPayments);
        if (paymentCycle) paymentCycle.addEventListener('change', filterPayments);
    }

    // نسخ المرجع بنقرة واحدة (Copy Reference Handler)
    document.addEventListener('click', function (e) {
        const copyBtn = e.target.closest('.js-copy-ref');
        if (!copyBtn) return;

        const textToCopy = copyBtn.getAttribute('data-copy');
        if (!textToCopy) return;

        navigator.clipboard.writeText(textToCopy).then(function () {
            const icon = copyBtn.querySelector('i');
            if (icon) {
                const orig = icon.className;
                icon.className = 'ri-check-line';
                copyBtn.style.color = 'var(--color-success)';
                setTimeout(function () {
                    icon.className = orig;
                    copyBtn.style.color = '';
                }, 1500);
            }
        });
    });

    document.addEventListener('DOMContentLoaded', initTableFilters);
})();
