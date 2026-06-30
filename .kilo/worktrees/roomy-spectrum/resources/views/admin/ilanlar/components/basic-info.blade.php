{{-- 🎨 Section 1: Temel Bilgiler (Context7 Optimized) --}}
<div
    class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200 p-5 dark:shadow-none dark:border-slate-700">
    <!-- Section Header -->
    <div
        class="px-5 py-3 border-b border-gray-200 dark:border-gray-700
                bg-gradient-to-r from-gray-50 to-white
                dark:from-gray-800 dark:to-gray-800
                rounded-t-lg
                flex items-center gap-3 mb-4">
        <div
            class="flex items-center justify-center w-9 h-9 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-md font-semibold text-sm dark:shadow-none">
            1
        </div>
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Temel Bilgiler
            </h2>
            <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">İlanınızın başlık ve açıklamasını girin</p>
        </div>
    </div>

    <div class="space-y-4">
        {{-- İlan Başlığı - Enhanced --}}
        <div class="group">
            <label for="baslik"
                class="block text-sm font-medium text-gray-900 dark:text-white mb-1.5 flex items-center gap-2 dark:text-slate-100">
                <span
                    class="flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-semibold">
                    1
                </span>
                İlan Başlığı
                <span class="text-red-500 font-semibold">*</span>
                <span class="ml-auto text-xs text-gray-500 dark:text-gray-400">(Maksimum 255 karakter)</span>
            </label>
            <div class="relative">
                <input type="text" name="baslik" id="baslik" value="{{ old('baslik', $ilan->baslik ?? '') }}"
                    required aria-required="true" data-context7-field="baslik" data-validation="required|string|max:255"
                    placeholder="Örn: Bodrum Yalıkavak'ta Deniz Manzaralı Satılık Villa"
                    @error('baslik') aria-invalid="true" aria-describedby="baslik-error" data-error="true" @enderror
                    class="w-full px-4 py-2.5 text-base
                           border border-gray-300 dark:border-gray-600
                           rounded-lg
                           bg-white dark:bg-gray-900
                           text-black dark:text-white
                           placeholder-gray-400 dark:placeholder-gray-500
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400
                           transition-all duration-200
                           hover:border-gray-400 dark:hover:border-gray-500
                           disabled:bg-gray-100 disabled:cursor-not-allowed
                           data-[error=true]:border-red-500 data-[error=true]:focus:ring-red-500">
                <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400 group-focus-within:text-blue-500 transition-colors" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>
            </div>
            @error('baslik')
                <div id="baslik-error" role="alert" aria-live="assertive"
                    class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 px-4 py-2 rounded-lg">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ $message }}
                </div>
            @enderror
        </div>

        {{-- İlan Açıklaması - Enhanced --}}
        <div class="group">
            <label for="aciklama"
                class="block text-sm font-medium text-gray-900 dark:text-white mb-1.5 flex items-center gap-2 dark:text-slate-100">
                <span
                    class="flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs font-semibold">
                    2
                </span>
                İlan Açıklaması
                <span class="ml-auto text-xs text-gray-500 dark:text-gray-400">(Opsiyonel)</span>
            </label>
            <div class="relative">
                <textarea id="aciklama" name="aciklama" rows="6" data-context7-field="aciklama" data-validation="nullable|string"
                    placeholder="İlan açıklamasını buraya yazın... (AI ile otomatik oluşturabilirsiniz)"
                    @error('aciklama') aria-invalid="true" aria-describedby="aciklama-error" data-error="true" @enderror
                    class="w-full px-4 py-2.5 text-base
                           border border-gray-300 dark:border-gray-600
                           rounded-lg
                           bg-white dark:bg-gray-900
                           text-black dark:text-white
                           placeholder-gray-400 dark:placeholder-gray-500
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400
                           transition-all duration-200
                           hover:border-gray-400 dark:hover:border-gray-500
                           resize-y min-h-[100px] max-h-[300px]
                           data-[error=true]:border-red-500 data-[error=true]:focus:ring-red-500">{{ old('aciklama', $ilan->aciklama ?? '') }}</textarea>
                <div class="absolute top-4 right-4 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400 group-focus-within:text-blue-500 transition-colors" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16m-7 6h7" />
                    </svg>
                </div>
            </div>
            @error('aciklama')
                <div id="aciklama-error" role="alert" aria-live="assertive"
                    class="mt-2 flex items-center gap-2 text-sm text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 px-4 py-2 rounded-lg">
                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ $message }}
                </div>
            @enderror

            {{-- AI Hint - Enhanced --}}
            <div
                class="mt-3 flex items-start gap-3 p-4 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/10 dark:to-blue-900/10 border border-purple-200 dark:border-purple-800/30 rounded-xl">
                <div class="flex-shrink-0">
                    <div
                        class="flex items-center justify-center w-8 h-8 rounded-lg bg-gradient-to-br from-purple-500 to-blue-500 shadow-lg">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-purple-900 dark:text-purple-100 mb-1">
                        💡 AI İpucu
                    </p>
                    <p class="text-xs text-purple-700 dark:text-purple-300">
                        İlan açıklamanızı otomatik oluşturmak için aşağıdaki "AI İçerik" bölümünü kullanabilirsiniz. AI,
                        ilanınızı analiz ederek profesyonel bir açıklama oluşturacaktır.
                    </p>
                </div>
            </div>
        </div>

        {{-- ✅ SAB: Metrekare ve Oda Sayısı kaldırıldı (Field Dependencies'te var) --}}
    </div>
</div>
