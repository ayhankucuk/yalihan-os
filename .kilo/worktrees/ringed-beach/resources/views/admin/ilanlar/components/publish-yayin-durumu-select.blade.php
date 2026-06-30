{{-- publish-yayin-durumu-select.blade.php --}}
{{-- Context7 Compliant: Yayın Durumu Select Component --}}

<div class="relative">
    <select 
        name="yayin_durumu" 
        id="yayin_durumu"
        class="w-full px-4 py-2.5
               border-2 border-gray-300 dark:border-gray-600
               rounded-xl
               bg-white dark:bg-gray-800
               text-black dark:text-white
               focus:ring-4 focus:ring-blue-500 dark:focus:ring-blue-400/20 focus:border-blue-500 dark:focus:border-blue-400
               transition-all duration-200
               hover:border-gray-400 dark:hover:border-gray-500
               cursor-pointer
               shadow-sm hover:shadow-md focus:shadow-lg
               appearance-none"
        @change="$dispatch('yayin-durumu-changed', { value: $event.target.value })">
        <option value="aktif" 
            {{ old('yayin_durumu', $ilan->yayin_durumu ?? '') == 'aktif' ? 'selected' : '' }}
            class="text-green-600 dark:text-green-400 font-semibold">
            ✅ Aktif (Yayında)
        </option>
        <option value="pasif" 
            {{ old('yayin_durumu', $ilan->yayin_durumu ?? '') == 'pasif' ? 'selected' : '' }}
            class="text-gray-600 dark:text-gray-400">
            🔵 Pasif (CRM'de)
        </option>
        <option value="taslak" 
            {{ old('yayin_durumu', $ilan->yayin_durumu ?? '') == 'taslak' ? 'selected' : '' }}
            class="text-yellow-600 dark:text-yellow-400">
            📝 Taslak (Hazırlanıyor)
        </option>
        <option value="arsivlendi" 
            {{ old('yayin_durumu', $ilan->yayin_durumu ?? '') == 'arsivlendi' ? 'selected' : '' }}
            class="text-gray-500 dark:text-gray-500">
            📦 Arşivlendi
        </option>
    </select>

    {{-- Dropdown Icon --}}
    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
        <svg class="w-5 h-5 text-gray-400 group-focus-within:text-blue-500 transition-colors" 
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </div>
</div>

{{-- Status Indicator --}}
<div class="mt-3 p-3 rounded-lg border transition-all duration-200" 
     x-data="{ 
         currentDurumu: '{{ old('yayin_durumu', $ilan->yayin_durumu ?? 'taslak') }}',
         getStatusClass() {
             const statusMap = {
                 'aktif': 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
                 'pasif': 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
                 'taslak': 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800',
                 'arsivlendi': 'bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700'
             };
             return statusMap[this.currentDurumu] || statusMap['taslak'];
         },
         getStatusText() {
             const textMap = {
                 'aktif': '✅ İlan yayında ve aktif olarak gösteriliyor',
                 'pasif': '🔵 İlan sadece CRM\'de görünüyor, sitede yayınlanmıyor',
                 'taslak': '📝 İlan henüz tamamlanmadı, yayınlanmıyor',
                 'arsivlendi': '📦 İlan arşivlendi, görünmüyor'
             };
             return textMap[this.currentDurumu] || textMap['taslak'];
         }
     }"
     x-on:yayin-durumu-changed.window="currentDurumu = $event.detail.value"
     :class="getStatusClass()">
    <div class="flex items-start gap-2">
        <svg class="w-5 h-5 mt-0.5 flex-shrink-0" 
             :class="{
                 'text-green-600 dark:text-green-400': currentDurumu === 'aktif',
                 'text-blue-600 dark:text-blue-400': currentDurumu === 'pasif',
                 'text-yellow-600 dark:text-yellow-400': currentDurumu === 'taslak',
                 'text-gray-500 dark:text-gray-400': currentDurumu === 'arsivlendi'
             }"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm font-medium" 
           :class="{
               'text-green-800 dark:text-green-300': currentDurumu === 'aktif',
               'text-blue-800 dark:text-blue-300': currentDurumu === 'pasif',
               'text-yellow-800 dark:text-yellow-300': currentDurumu === 'taslak',
               'text-gray-700 dark:text-gray-300': currentDurumu === 'arsivlendi'
           }"
           x-text="getStatusText()"></p>
    </div>
</div>
