{{-- İlan Referans Numarası Generator --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 dark:bg-slate-900 dark:border-slate-700 dark:shadow-none" x-data="referenceGenerator()">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">İlan Referans Numarası</h3>
        <button type="button" @click="generateReference()"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <span class="material-symbols-outlined mr-2">sync</span>Yeni Referans
        </button>
    </div>

    {{-- Referans Numarası --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
            Referans Numarası
        </label>
        <div class="flex">
            <input type="text" x-model="referenceNumber" readonly
                class="flex-1 px-4 py-2.5 border border-gray-300 rounded-l-md bg-gray-50 font-mono text-lg dark:bg-slate-900">
            <button type="button" @click="copyToClipboard()"
                class="px-4 py-2.5 bg-gray-600 text-white rounded-r-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">
                <span class="material-symbols-outlined">content_copy</span>
            </button>
        </div>
        <div class="mt-1 text-sm text-gray-500">
            <span x-show="isGenerated" class="text-green-600">✓ Referans oluşturuldu</span>
            <span x-show="!isGenerated" class="text-gray-400">Referans oluşturmak için butona tıklayın</span>
        </div>
    </div>

    {{-- Referans Detayları --}}
    <div x-show="isGenerated" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h4 class="text-sm font-medium text-blue-800 mb-2">📋 Referans Detayları:</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-blue-600">Oluşturulma Tarihi:</span>
                <div class="font-medium" x-text="createdDate"></div>
            </div>
            <div>
                <span class="text-blue-600">Sıra Numarası:</span>
                <div class="font-medium" x-text="sequenceNumber"></div>
            </div>
            <div>
                <span class="text-blue-600">Kategori Kodu:</span>
                <div class="font-medium" x-text="categoryCode"></div>
            </div>
            <div>
                <span class="text-blue-600">Yıl:</span>
                <div class="font-medium" x-text="yearCode"></div>
            </div>
        </div>
    </div>

    {{-- Hidden Input for Form Submission --}}
    <input type="hidden" name="referans_no" x-bind:value="referenceNumber">
</div>

<script>
    function referenceGenerator() {
        return {
            referenceNumber: '',
            isGenerated: false,
            createdDate: '',
            sequenceNumber: '',
            categoryCode: '',
            yearCode: '',

            init() {
                // Mevcut referans numarasını yükle
                this.loadExistingReference();
            },

            loadExistingReference() {
                const existingRef = document.querySelector('input[name="referans_no"]');
                if (existingRef && existingRef.value) {
                    this.referenceNumber = existingRef.value;
                    this.isGenerated = true;
                    this.parseReference();
                }
            },

            generateReference() {
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');

                // Kategori kodu (varsayılan: 01 - Genel)
                const category = this.getCategoryCode();

                // Sıra numarası (günlük)
                const sequence = this.getSequenceNumber();

                // Referans formatı: YYMMDD-KK-SSSS
                this.yearCode = year.toString().slice(-2);
                this.categoryCode = category;
                this.sequenceNumber = sequence;
                this.createdDate = `${day}/${month}/${year}`;

                this.referenceNumber = `${this.yearCode}${month}${day}-${category}-${sequence}`;
                this.isGenerated = true;

                // Form input'unu güncelle
                this.updateFormInput();
            },

            getCategoryCode() {
                // Form'dan kategori bilgisini al
                const categorySelect = document.querySelector('select[name="category_id"]');
                if (categorySelect && categorySelect.value) {
                    const categoryId = categorySelect.value;
                    // Kategori ID'sine göre kod döndür
                    const categoryCodes = {
                        '1': '01', // Konut
                        '2': '02', // Arsa
                        '3': '03', // İşyeri
                        '4': '04', // Turistik Tesis
                        '5': '05' // Diğer
                    };
                    return categoryCodes[categoryId] || '01';
                }
                return '01'; // Varsayılan
            },

            getSequenceNumber() {
                // Günlük sıra numarası (4 haneli)
                const today = new Date().toISOString().split('T')[0];
                const storageKey = `ref_sequence_${today}`;

                let sequence = parseInt(localStorage.getItem(storageKey) || '0') + 1;
                localStorage.setItem(storageKey, sequence.toString());

                return String(sequence).padStart(4, '0');
            },

            parseReference() {
                if (this.referenceNumber) {
                    const parts = this.referenceNumber.split('-');
                    if (parts.length === 3) {
                        this.yearCode = parts[0].substring(0, 2);
                        this.categoryCode = parts[1];
                        this.sequenceNumber = parts[2];

                        const year = '20' + this.yearCode;
                        const month = parts[0].substring(2, 4);
                        const day = parts[0].substring(4, 6);
                        this.createdDate = `${day}/${month}/${year}`;
                    }
                }
            },

            copyToClipboard() {
                if (this.referenceNumber) {
                    navigator.clipboard.writeText(this.referenceNumber).then(() => {
                        // Başarı mesajı göster
                        this.showCopySuccess();
                    }).catch(() => {
                        // Fallback: textarea ile kopyala
                        const textArea = document.createElement('textarea');
                        textArea.value = this.referenceNumber;
                        document.body.appendChild(textArea);
                        textArea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textArea);
                        this.showCopySuccess();
                    });
                }
            },

            showCopySuccess() {
                // Geçici başarı mesajı
                const button = event.target.closest('button');
                const originalText = button.innerHTML;
                button.innerHTML = '<span class="material-symbols-outlined text-green-500">check</span>';
                button.classList.add('bg-green-600');

                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('bg-green-600');
                }, 2000);
            },

            updateFormInput() {
                const hiddenInput = document.querySelector('input[name="referans_no"]');
                if (hiddenInput) {
                    hiddenInput.value = this.referenceNumber;
                }
            }
        }
    }
</script>
