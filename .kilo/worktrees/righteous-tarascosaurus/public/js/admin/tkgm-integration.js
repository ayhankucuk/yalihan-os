/**
 * TKGM Parsel Sorgulama Entegrasyonu
 * EmlakPro Admin Panel - Arsa Modülü
 */

class TKGMIntegration {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.checkServiceStatus();
    }

    bindEvents() {
        // TKGM Sorgula butonu
        const tkgmButton = document.getElementById('tkgm-sorgula');
        if (tkgmButton) {
            tkgmButton.addEventListener('click', () => this.sorgulaParsel());
        }

        // Ada/Parsel input değişikliklerini dinle
        const adaInput = document.getElementById('ada_no');
        const parselInput = document.getElementById('parsel_no');

        if (adaInput && parselInput) {
            adaInput.addEventListener('input', () => this.validateInputs());
            parselInput.addEventListener('input', () => this.validateInputs());
        }
    }

    async checkServiceStatus() {
        try {
            const response = await fetch('/admin/tkgm/status');
            const data = await response.json();

            if (data.success && data.servis_aktif) {
                this.showServiceStatus('TKGM servisi aktif', 'success');
            } else {
                this.showServiceStatus('TKGM servisi kullanılamıyor', 'warning');
            }
        } catch (error) {
            console.error('TKGM servis durumu kontrol edilemedi:', error);
            this.showServiceStatus('TKGM servis durumu kontrol edilemedi', 'error');
        }
    }

    async sorgulaParsel() {
        const adaInput = document.getElementById('ada_no');
        const parselInput = document.getElementById('parsel_no');
        const tkgmButton = document.getElementById('tkgm-sorgula');
        const sonuclarDiv = document.getElementById('tkgm-sonuclar');
        const dataDiv = document.getElementById('tkgm-data');

        if (!adaInput || !parselInput || !tkgmButton) {
            console.error('TKGM form elemanları bulunamadı');
            return;
        }

        const ada = adaInput.value.trim();
        const parsel = parselInput.value.trim();

        if (!ada || !parsel) {
            this.showAlert('Ada ve Parsel numaralarını giriniz', 'error');
            return;
        }

        // Loading state
        tkgmButton.disabled = true;
        tkgmButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sorgulanıyor...';

        try {
            const response = await fetch('/admin/tkgm/sorgula', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ ada, parsel }),
            });

            const data = await response.json();

            if (data.success) {
                this.displayTKGMData(data.parsel_bilgisi);
                if (sonuclarDiv) sonuclarDiv.classList.remove('hidden');
                this.showAlert('Parsel bilgisi başarıyla alındı', 'success');
            } else {
                this.showAlert(data.message || 'Parsel bulunamadı', 'error');
            }
        } catch (error) {
            console.error('TKGM sorgulama hatası:', error);
            this.showAlert('TKGM sorgulama hatası oluştu', 'error');
        } finally {
            tkgmButton.disabled = false;
            tkgmButton.innerHTML = '<i class="fas fa-search mr-2"></i>TKGM\'den Sorgula';
        }
    }

    displayTKGMData(parselData) {
        const dataDiv = document.getElementById('tkgm-data');
        if (!dataDiv) return;

        const formatValue = (value) => {
            return value && value !== 'Bilinmiyor'
                ? value
                : '<span class="text-gray-500">Bilinmiyor</span>';
        };

        dataDiv.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-blue-50 p-3 rounded-lg">
                    <div class="text-sm font-medium text-blue-800 mb-1">Alan</div>
                    <div class="text-blue-900">${formatValue(parselData.alan)}</div>
                </div>

                <div class="bg-green-50 p-3 rounded-lg">
                    <div class="text-sm font-medium text-green-800 mb-1">İmar Durumu</div>
                    <div class="text-green-900">${formatValue(parselData.imar_durumu)}</div>
                </div>

                <div class="bg-purple-50 p-3 rounded-lg">
                    <div class="text-sm font-medium text-purple-800 mb-1">Tapu Durumu</div>
                    <div class="text-purple-900">${formatValue(parselData.tapu_durumu)}</div>
                </div>

                <div class="bg-orange-50 p-3 rounded-lg">
                    <div class="text-sm font-medium text-orange-800 mb-1">Sahip Bilgileri</div>
                    <div class="text-orange-900">${formatValue(parselData.sahip_bilgileri)}</div>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-slate-700">
                <div class="text-sm text-gray-600">
                    <strong>Ada:</strong> ${parselData.ada_no} |
                    <strong>Parsel:</strong> ${parselData.parsel_no} |
                    <strong>Sorgu Tarihi:</strong> ${parselData.sorgu_tarihi}
                </div>
            </div>
        `;
    }

    validateInputs() {
        const adaInput = document.getElementById('ada_no');
        const parselInput = document.getElementById('parsel_no');
        const tkgmButton = document.getElementById('tkgm-sorgula');

        if (!adaInput || !parselInput || !tkgmButton) return;

        const ada = adaInput.value.trim();
        const parsel = parselInput.value.trim();

        // Ada ve parsel numaraları sayısal olmalı
        const adaValid = /^\d+$/.test(ada) && parseInt(ada) > 0;
        const parselValid = /^\d+$/.test(parsel) && parseInt(parsel) > 0;

        // Input validation styling
        adaInput.classList.toggle('border-red-500', ada && !adaValid);
        parselInput.classList.toggle('border-red-500', parsel && !parselValid);

        // Button state
        tkgmButton.disabled = !(adaValid && parselValid);
        tkgmButton.classList.toggle('opacity-50', !(adaValid && parselValid));
    }

    showAlert(message, type = 'info') {
        // Toast notification sistemi
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 translate-x-full`;

        const bgColor =
            {
                success: 'bg-green-500',
                error: 'bg-red-500',
                warning: 'bg-yellow-500',
                info: 'bg-blue-500',
            }[type] || 'bg-blue-500';

        toast.className += ` ${bgColor} text-white`;
        toast.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-${this.getIconForType(type)} mr-2"></i>
                <span>${message}</span>
                <button class="ml-4 text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        document.body.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }

    showServiceStatus(message, type) {
        const statusDiv = document.getElementById('tkgm-service-status');
        if (!statusDiv) return;

        const bgColor =
            {
                success: 'bg-green-100 text-green-800 border-green-200',
                warning: 'bg-yellow-100 text-yellow-800 border-yellow-200',
                error: 'bg-red-100 text-red-800 border-red-200',
            }[type] || 'bg-blue-100 text-blue-800 border-blue-200';

        statusDiv.className = `p-3 rounded-lg border ${bgColor} text-sm`;
        statusDiv.innerHTML = `
            <i class="fas fa-${this.getIconForType(type)} mr-2"></i>
            ${message}
        `;
    }

    getIconForType(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle',
        };
        return icons[type] || 'info-circle';
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    // Check if we're on an arsa form page
    const arsaForm = document.querySelector('[data-form-type="arsa"]');
    if (arsaForm) {
        window.tkgmIntegration = new TKGMIntegration();
    }
});

// Export for global use
window.TKGMIntegration = TKGMIntegration;
