<div x-data="{
        open: false,
        targetType: 'owner',
        loading: false,
        form: {
            ad: '',
            soyad: '',
            telefon: ''
        },
        save() {
            if (!this.form.ad || !this.form.soyad || !this.form.telefon) {
                alert('Lütfen tüm alanları doldurun.');
                return;
            }

            this.loading = true;
            const csrfToken = document.querySelector('meta[name=\'csrf-token\']')?.getAttribute('content');

            fetch('/api/v1/kisiler', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    ...this.form,
                    kisi_tipi: 'DIGER',
                    il_id: 34,
                    aktiflik_durumu: 1
                })
            })
            .then(res => res.json())
            .then(data => {
                const person = data.data || data;
                if (!person.id) throw new Error('Kayıt başarısız');

                // Hedef inputu güncelle
                const targetId = this.targetType === 'owner' ? 'ilan_sahibi' : 'ilgili_kisi';

                // 1. Hidden ID input güncelle
                const hiddenInput = document.getElementById(`${targetId}_id`);
                if (hiddenInput) hiddenInput.value = person.id;

                // 2. Search input (görünen isim) güncelle
                const searchInput = document.getElementById(`${targetId}_search`);
                if (searchInput) {
                    searchInput.value = `${person.ad} ${person.soyad}`;
                    // Tetiklemek gerekiyorsa event dispatch et
                    searchInput.dispatchEvent(new Event('input'));
                }

                // Toast veya alert
                if (window.toast) window.toast.success('Kişi başarıyla eklendi');

                // Reset & Close
                this.form = { ad: '', soyad: '', telefon: '' };
                this.open = false;
            })
            .catch(err => {
                console.error(err);
                alert('Bir hata oluştu: ' + err.message);
            })
            .finally(() => {
                this.loading = false;
            });
        }
    }"
     @open-quick-client-modal.window="open = true; targetType = $event.detail.type || 'owner'"
     class="relative z-50"
     aria-labelledby="modal-title"
     role="dialog"
     aria-modal="true"
     x-cloak
     x-show="open">

    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
         x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-slate-900 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg"
                 @click.away="open = false"
                 x-show="open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                <div class="bg-white dark:bg-slate-900 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-user-plus text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                            <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-slate-100 dark:text-white" id="modal-title">
                                <span x-text="targetType === 'owner' ? 'Yeni İlan Sahibi Ekle' : 'Yeni İlgili Kişi Ekle'"></span>
                            </h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Ad <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="form.ad" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:shadow-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Soyad <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="form.soyad" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:shadow-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Telefon <span class="text-red-500">*</span></label>
                                    <input type="text" x-model="form.telefon" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm dark:shadow-none" placeholder="0555 555 55 55">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 dark:bg-slate-900">
                    <button type="button"
                            @click="save()"
                            :disabled="loading"
                            class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto disabled:opacity-50 dark:shadow-none">
                        <span x-show="!loading">Kaydet</span>
                        <span x-show="loading">Kaydediliyor...</span>
                    </button>
                    <button type="button"
                            @click="open = false"
                            class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-gray-600 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-slate-200 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-500 hover:bg-gray-50 dark:hover:bg-gray-500 sm:mt-0 sm:w-auto dark:shadow-none dark:bg-slate-900 dark:text-white">
                        İptal
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
