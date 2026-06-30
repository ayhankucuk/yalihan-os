export default function vacationPricingManager() {
    return {
        periods: [],
        activePeriod: {
            start_date: '',
            end_date: '',
            price: '',
            min_stay: 3,
        },
        jsonOutput: '',
        isModalOpen: false,

        init() {
            // Load initial data if any (e.g. from existing value)
            const initialData = document.getElementById('yazlik_fiyatlandirma_json')?.value;
            if (initialData) {
                try {
                    this.periods = JSON.parse(initialData);
                } catch (e) {
                    console.error('Failed to parse initial vacation pricing data', e);
                }
            }
            this.updateJson();

            // Watch for changes to update hidden input
            this.$watch('periods', () => this.updateJson());
        },

        openModal() {
            this.activePeriod = {
                start_date: '',
                end_date: '',
                price: '',
                min_stay: 3,
                season_type: 'kis', // Reset to default 'kis' when opening modal
            };
            this.isModalOpen = true;
        },

        closeModal() {
            this.isModalOpen = false;
        },

        addPeriod() {
            // Simple validation
            if (
                !this.activePeriod.start_date ||
                !this.activePeriod.end_date ||
                !this.activePeriod.price ||
                !this.activePeriod.season_type // Add validation for season_type
            ) {
                if (window.toast) window.toast.error('Lütfen tüm alanları doldurun.');
                else alert('Lütfen tüm alanları doldurun.');
                return;
            }

            if (new Date(this.activePeriod.start_date) > new Date(this.activePeriod.end_date)) {
                if (window.toast)
                    window.toast.error('Bitiş tarihi başlangıç tarihinden önce olamaz.');
                else alert('Bitiş tarihi başlangıç tarihinden önce olamaz.');
                return;
            }

            // Check overlap
            if (this.checkOverlap(this.activePeriod)) {
                if (window.toast)
                    window.toast.error('Seçilen tarih aralığı mevcut bir dönemle çakışıyor.');
                else alert('Seçilen tarih aralığı mevcut bir dönemle çakışıyor.');
                return;
            }

            this.periods.push({ ...this.activePeriod });

            // Sort by start date
            this.periods.sort((a, b) => new Date(a.start_date) - new Date(b.start_date));

            this.closeModal();
        },

        removePeriod(index) {
            this.periods.splice(index, 1);
        },

        checkOverlap(newPeriod) {
            const start = new Date(newPeriod.start_date);
            const end = new Date(newPeriod.end_date);

            return this.periods.some((period) => {
                const pStart = new Date(period.start_date);
                const pEnd = new Date(period.end_date);
                return start <= pEnd && end >= pStart;
            });
        },

        updateJson() {
            this.jsonOutput = JSON.stringify(this.periods);
            // Also update the hidden input directly for form submission
            // document.getElementById('yazlik_fiyatlandirma_json').value = this.jsonOutput;
            // x-model will handle this if bound correctly
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('tr-TR', options);
        },

        formatPrice(price) {
            return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(
                price
            );
        },
    };
}
