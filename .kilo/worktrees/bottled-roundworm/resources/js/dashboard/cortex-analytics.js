import ApexCharts from 'apexcharts';

window.cortexAnalytics = () => ({
    isLoading: false,
    period: 'this_month', // this_month, last_month, custom
    listingId: null, // Optional listing filter
    startDate: null,
    endDate: null,

    // KPI Data
    metrics: {
        occupancy_rate: 0,
        adr: 0,
        revpar: 0,
        revenue: 0, // If available from API, else calc from RevPAR * Available? Or needs API update?
        // Note: ReportingController returns occupancy, ADR, RevPAR.
        // We might need "Total Revenue" property in API or calc it roughly.
        // For now, let's use what we have.
    },

    // Trends (Mock for now or calc from compare)
    trends: {
        occupancy: 0,
        adr: 0,
        revpar: 0,
    },

    init() {
        this.setPeriod('this_month');
        this.$watch('listingId', () => this.fetchMetrics());
    },

    setPeriod(p) {
        this.period = p;
        const now = new Date();

        if (p === 'this_month') {
            this.startDate = new Date(now.getFullYear(), now.getMonth(), 1)
                .toISOString()
                .split('T')[0];
            this.endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0)
                .toISOString()
                .split('T')[0];
        } else if (p === 'last_month') {
            this.startDate = new Date(now.getFullYear(), now.getMonth() - 1, 1)
                .toISOString()
                .split('T')[0];
            this.endDate = new Date(now.getFullYear(), now.getMonth(), 0)
                .toISOString()
                .split('T')[0];
        }

        this.fetchMetrics();
    },

    async fetchMetrics() {
        if (!this.listingId) {
            // Dashboard needs at least one listing ID or "All" logic (API currently requires listing_id).
            // For v1, we might need to pick the first listing or update API to support aggregation.
            // Let's assume we select the first one or pass a default via Blade.
            // If no listing selected, maybe show empty state?
            console.warn('No listing selected');
            return;
        }

        this.isLoading = true;
        try {
            const url = `/api/v1/admin/reporting/metrics?listing_id=${this.listingId}&start_date=${this.startDate}&end_date=${this.endDate}`;
            const response = await fetch(url).then((res) => res.json());

            if (response.success === true) {
                this.metrics = response.data.metrics;
                this.updateCharts();
            }
        } catch (e) {
            console.error('Metrics fetch failed', e);
        } finally {
            this.isLoading = false;
        }
    },

    updateCharts() {
        // Here we would update ApexCharts instances
        // For v1 scaffold, we'll just log
        console.log('Updating charts with', this.metrics);

        // Example: Update Occupancy Circle
        // this.charts.occupancy.updateSeries([this.metrics.occupancy_rate]);
    },
});

// Auto-register for Alpine
document.addEventListener('alpine:init', () => {
    Alpine.data('cortexAnalytics', window.cortexAnalytics);
});
