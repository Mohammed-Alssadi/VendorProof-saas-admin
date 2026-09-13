/**
 * VendorProof SaaS Admin - Dashboard Charts
 * Responsive, Dual-Theme Reactivity, IBM Plex Sans Arabic Typography
 */

document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') {
        return;
    }

    // تعيين الخط الافتراضي لـ Chart.js إلى خط المنصة الرسمي
    Chart.defaults.font.family = "'IBM Plex Sans Arabic', system-ui, -apple-system, sans-serif";

    const revenueCanvas = document.getElementById('revenueChart');
    const billingCanvas = document.getElementById('billingChart');

    if (!revenueCanvas || !billingCanvas) {
        return;
    }

    const chartsContainer = document.getElementById('dashboardChartsContainer');
    const currencySymbol = (chartsContainer && chartsContainer.getAttribute('data-currency-symbol')) || 'ر.س';
    let chartData = window.dashboardChartsData;

    if (!chartData && chartsContainer) {
        try {
            const revLabels = JSON.parse(chartsContainer.getAttribute('data-revenue-labels') || '[]');
            const revValues = JSON.parse(chartsContainer.getAttribute('data-revenue-values') || '[]');
            const billLabels = JSON.parse(chartsContainer.getAttribute('data-billing-labels') || '[]');
            const billPercentages = JSON.parse(chartsContainer.getAttribute('data-billing-percentages') || '[]');
            const billCounts = JSON.parse(chartsContainer.getAttribute('data-billing-counts') || '[]');

            if (revLabels.length > 0 && billLabels.length > 0) {
                chartData = {
                    revenue: {
                        labels: revLabels,
                        values: revValues
                    },
                    billing: {
                        labels: billLabels,
                        percentages: billPercentages,
                        counts: billCounts
                    }
                };
            }
        } catch (e) {
            chartData = null;
        }
    }

    if (!chartData) {
        chartData = {
            revenue: {
                labels: ['أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر'],
                values: [85000, 98000, 112000, 126000, 138000, 148500],
            },
            billing: {
                labels: ['اشتراكات سنوية', 'اشتراكات شهرية', 'تجارب مجانية'],
                percentages: [68, 26, 6],
                counts: [280, 107, 25],
            },
        };
    }

    let revenueChartInstance = null;
    let billingChartInstance = null;

    function getThemeTokens() {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';

        if (isDark) {
            return {
                isDark: true,
                textColor: '#a1a1aa',
                gridColor: 'rgba(255, 255, 255, 0.06)',
                borderColor: '#222328',
                tooltipBg: '#18191e',
                tooltipText: '#f4f4f5',
                tooltipBorder: '#27272a',
                lineColor: '#3b82f6',
                lineGradientTop: 'rgba(59, 130, 246, 0.22)',
                lineGradientBottom: 'rgba(59, 130, 246, 0.00)',
                doughnutColors: ['#3b82f6', '#10b981', '#f59e0b'],
                doughnutBorder: '#121316',
            };
        }

        return {
            isDark: false,
            textColor: '#475569',
            gridColor: 'rgba(0, 0, 0, 0.06)',
            borderColor: '#e2e8f0',
            tooltipBg: '#09090b',
            tooltipText: '#ffffff',
            tooltipBorder: '#27272a',
            lineColor: '#2563eb',
            lineGradientTop: 'rgba(37, 99, 235, 0.18)',
            lineGradientBottom: 'rgba(37, 99, 235, 0.00)',
            doughnutColors: ['#2563eb', '#059669', '#d97706'],
            doughnutBorder: '#ffffff',
        };
    }

    function createLinearGradient(ctx, topColor, bottomColor) {
        const gradient = ctx.createLinearGradient(0, 0, 0, 260);
        gradient.addColorStop(0, topColor);
        gradient.addColorStop(1, bottomColor);
        return gradient;
    }

    function renderRevenueChart() {
        const tokens = getThemeTokens();
        const ctx = revenueCanvas.getContext('2d');
        const fillGradient = createLinearGradient(ctx, tokens.lineGradientTop, tokens.lineGradientBottom);

        if (revenueChartInstance) {
            revenueChartInstance.destroy();
        }

        revenueChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.revenue.labels,
                datasets: [
                    {
                        label: 'المنشآت المسجلة',
                        data: chartData.revenue.values,
                        borderColor: tokens.lineColor,
                        backgroundColor: fillGradient,
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.38,
                        pointBackgroundColor: tokens.lineColor,
                        pointBorderColor: tokens.isDark ? '#121316' : '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 4.5,
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: tokens.lineColor,
                        pointHoverBorderColor: '#ffffff',
                        pointHoverBorderWidth: 2.5,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: tokens.tooltipBg,
                        titleColor: tokens.tooltipText,
                        bodyColor: tokens.tooltipText,
                        borderColor: tokens.tooltipBorder,
                        borderWidth: 1,
                        padding: { top: 10, right: 14, bottom: 10, left: 14 },
                        cornerRadius: 8,
                        displayColors: false,
                        titleFont: { weight: '600', size: 12 },
                        bodyFont: { size: 13 },
                        callbacks: {
                            label: function (context) {
                                return ' المنشآت: ' + Number(context.parsed.y).toLocaleString('en-US') + ' ' + currencySymbol;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: tokens.textColor,
                            font: { size: 12 },
                        },
                        border: {
                            color: tokens.borderColor,
                        },
                    },
                    y: {
                        position: 'right',
                        grid: {
                            color: tokens.gridColor,
                            drawBorder: false,
                        },
                        ticks: {
                            color: tokens.textColor,
                            font: { size: 11 },
                            callback: function (value) {
                                return (value / 1000) + ' ألف ' + currencySymbol;
                            },
                        },
                        border: {
                            display: false,
                        },
                    },
                },
            },
        });
    }

    function renderBillingChart() {
        const tokens = getThemeTokens();
        const ctx = billingCanvas.getContext('2d');

        if (billingChartInstance) {
            billingChartInstance.destroy();
        }

        billingChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: chartData.billing.labels,
                datasets: [
                    {
                        data: chartData.billing.percentages,
                        backgroundColor: tokens.doughnutColors,
                        borderColor: tokens.doughnutBorder,
                        borderWidth: 2.5,
                        hoverOffset: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        rtl: true,
                        labels: {
                            color: tokens.textColor,
                            font: { size: 12, weight: '500' },
                            padding: 16,
                            usePointStyle: true,
                            pointStyle: 'circle',
                        },
                    },
                    tooltip: {
                        backgroundColor: tokens.tooltipBg,
                        titleColor: tokens.tooltipText,
                        bodyColor: tokens.tooltipText,
                        borderColor: tokens.tooltipBorder,
                        borderWidth: 1,
                        padding: { top: 10, right: 14, bottom: 10, left: 14 },
                        cornerRadius: 8,
                        titleFont: { weight: '600', size: 12 },
                        bodyFont: { size: 13 },
                        callbacks: {
                            label: function (context) {
                                const index = context.dataIndex;
                                const count = chartData.billing.counts ? chartData.billing.counts[index] : 0;
                                return ' ' + context.parsed + '% (' + count + ' منشأة)';
                            },
                        },
                    },
                },
            },
        });
    }

    // الرسم المبدئي
    renderRevenueChart();
    renderBillingChart();

    // الاستماع لتغيير الثيم وتحديث ألوان الرسم البياني فورياً
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.attributeName === 'data-theme') {
                renderRevenueChart();
                renderBillingChart();
            }
        });
    });

    observer.observe(document.documentElement, { attributes: true });

    window.addEventListener('resize', function () {
        if (revenueChartInstance) revenueChartInstance.resize();
        if (billingChartInstance) billingChartInstance.resize();
    });
});
