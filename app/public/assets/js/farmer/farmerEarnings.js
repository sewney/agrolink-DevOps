// Farmer Earnings Page JavaScript
(function () {
    'use strict';

    const APP_ROOT = document.body.getAttribute('data-app-root') || '';

    function formatCurrency(amount) {
        const value = Number(amount || 0);
        return `Rs. ${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function formatAxisCurrency(amount) {
        const value = Number(amount || 0);
        if (value >= 1000) {
            return `Rs. ${Math.round(value).toLocaleString()}`;
        }
        if (value >= 1) {
            return `Rs. ${Math.round(value)}`;
        }
        return `Rs. ${value.toFixed(2)}`;
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function readChartData() {
        const el = document.getElementById('earningsChartData');
        if (!el) return { daily: [], monthly: [], yearly: [] };
        try {
            const payload = el.getAttribute('data-chart') || el.textContent || '{}';
            const parsed = JSON.parse(payload);
            return {
                daily: Array.isArray(parsed.daily) ? parsed.daily : [],
                monthly: Array.isArray(parsed.monthly) ? parsed.monthly : [],
                yearly: Array.isArray(parsed.yearly) ? parsed.yearly : [],
            };
        } catch (err) {
            console.error('Failed to parse chart data:', err);
            return { daily: [], monthly: [], yearly: [] };
        }
    }

    function renderChart(period, chartData) {
        const grid = document.getElementById('earningsChartGrid');
        if (!grid) return;

        const points = chartData[period] || [];
        if (!points.length) {
            grid.innerHTML = '<div style="padding: 24px; color: #667085;">No chart data available.</div>';
            return;
        }

        const maxValue = Math.max(...points.map(p => Number(p.earnings || 0)), 0);
        const axisMax = maxValue > 0 ? maxValue : 0;
        const tickCount = 4;
        const ticks = [];
        for (let i = tickCount; i >= 0; i--) {
            ticks.push(axisMax > 0 ? (axisMax * i) / tickCount : 0);
        }

        const yAxisHtml = ticks.map(value => (
            `<span class="earnings-chart-y-label">${formatAxisCurrency(value)}</span>`
        )).join('');

        const guidesHtml = ticks.map((_, index) => {
            const top = (index / tickCount) * 100;
            return `<div class="earnings-chart-guide-line" style="top:${top}%"></div>`;
        }).join('');

        const barsHtml = points.map(point => {
            const rawValue = Number(point.earnings || 0);
            const heightPercent = axisMax > 0 ? (rawValue / axisMax) * 100 : 0;
            const safeLabel = escapeHtml(point.label || '');
            const safeFullLabel = escapeHtml(point.fullLabel || point.label || '');
            return `
                <div class="earnings-chart-bar-col">
                    <div class="earnings-chart-bar" style="height:${Math.max(rawValue > 0 ? 4 : 0, heightPercent)}%" title="${safeFullLabel}: ${formatCurrency(rawValue)}"></div>
                    <span class="earnings-chart-label">${safeLabel}</span>
                </div>
            `;
        }).join('');

        grid.innerHTML = `
            <div class="earnings-chart-y-axis">${yAxisHtml}</div>
            <div class="earnings-chart-plot-area">
                <div class="earnings-chart-guides">${guidesHtml}</div>
                <div class="earnings-chart-bars" style="grid-template-columns:repeat(${points.length}, minmax(0, 1fr));">
                    ${barsHtml}
                </div>
            </div>
        `;
    }

    function setActiveTab(period) {
        document.querySelectorAll('#earningsPeriodTabs .earnings-tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.period === period);
        });
    }

    function syncRangeSelect(period) {
        const range = document.getElementById('earningsRangeSelect');
        if (!range) return;
        range.value = period;
    }

    function bindChartControls(chartData) {
        let currentPeriod = 'monthly';
        renderChart(currentPeriod, chartData);
        setActiveTab(currentPeriod);
        syncRangeSelect(currentPeriod);

        document.querySelectorAll('#earningsPeriodTabs .earnings-tab-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const period = this.dataset.period;
                if (!period) return;
                currentPeriod = period;
                renderChart(currentPeriod, chartData);
                setActiveTab(currentPeriod);
                syncRangeSelect(currentPeriod);
            });
        });

        const range = document.getElementById('earningsRangeSelect');
        if (range) {
            range.addEventListener('change', function () {
                const period = this.value;
                if (!period || !chartData[period]) return;
                currentPeriod = period;
                renderChart(currentPeriod, chartData);
                setActiveTab(currentPeriod);
            });
        }
    }

    function downloadReport() {
        const url = `${APP_ROOT}/farmerearnings/report?print=1`;
        window.open(url, '_blank');
    }

    function bindDownloadButton() {
        const button = document.getElementById('downloadEarningsReportBtn');
        if (!button) return;
        button.addEventListener('click', downloadReport);
    }

    function initEarningsPage() {
        const chartData = readChartData();
        bindChartControls(chartData);
        bindDownloadButton();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEarningsPage);
    } else {
        initEarningsPage();
    }

    window.FarmerEarnings = {
        downloadReport
    };
})();
