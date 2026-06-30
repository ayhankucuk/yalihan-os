{{--
🎨 KİŞİ SEÇİMİ - Ultra Modern Edition
Context7: %100, Tailwind CSS ONLY, Live Search Integration
--}}

<x-admin.ilanlar.components.elegant-form-wrapper sectionId="section-person" title="Kişi Bilgileri"
    subtitle="İlan sahibi, ilgili kişi ve danışman seçimi" badgeNumber="2" badgeColor="purple" :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'>
                  <path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\'
                        d=\'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z\' />
                </svg>'"
    glassEffect="false">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- İlan Sahibi --}}
        <div class="relative group" x-data="{ selected: null }">
            <label class="block text-sm font-semibold text-gray-700 dark:text-slate-200 mb-2 flex items-center gap-2 dark:text-slate-300">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                        clip-rule="evenodd" />
                </svg>
                İlan Sahibi
                <span class="text-red-500">*</span>
            </label>

            <div class="context7-live-search relative" data-search-type="kisiler"
                data-placeholder="İsim veya telefon ara..." data-endpoint="/api/v1/kisiler/search" data-max-results="15"
                data-creatable="true">

                <input type="hidden" name="ilan_sahibi_id" id="ilan_sahibi_id"
                    value="{{ old('ilan_sahibi_id', $ilan->ilan_sahibi_id ?? '') }}" required>

                <div class="relative">
                    <div
                        class="absolute left-4 top-1/2 -translate-y-1/2
                                text-gray-400 dark:text-gray-500
                                transition-colors duration-200
                                group-focus-within:text-purple-600 dark:group-focus-within:text-purple-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <input type="text" id="ilan_sahibi_search" placeholder="İsim, telefon veya email ile ara..."
                        autocomplete="off"
                        class="w-full pl-12 pr-4 py-3.5 text-base
                                  border-2 border-gray-300 dark:border-gray-600
                                  rounded-xl
                                  bg-gray-50 dark:bg-gray-800
                                  text-gray-900 dark:text-white
                                  placeholder-gray-400 dark:placeholder-gray-500
                                  focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10
                                  dark:focus:border-purple-400 dark:focus:ring-purple-400/10
                                  transition-all duration-300
                                  hover:border-gray-400 dark:hover:border-gray-500">
                </div>

                {{-- Search Results Dropdown --}}
                <div
                    class="context7-search-results
                            absolute z-[9999] w-full mt-2
                            bg-white dark:bg-gray-800
                            border-2 border-gray-200 dark:border-gray-700
                            rounded-xl shadow-2xl
                            hidden max-h-72 overflow-y-auto
                            backdrop-blur-xl">
                </div>

                {{-- Yeni Kişi Ekle Button --}}
                <button type="button" onclick="openAddPersonModal('owner')"
                    class="mt-3 inline-flex items-center gap-2 px-4 py-2
                               text-sm font-medium
                               text-purple-600 dark:text-purple-400
                               hover:text-purple-700 dark:hover:text-purple-300
                               bg-purple-50 dark:bg-purple-900/20
                               hover:bg-purple-100 dark:hover:bg-purple-900/30
                               rounded-lg
                               transition-all duration-200
                               hover:scale-105">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    Yeni Kişi Ekle
                </button>
            </div>

            <p class="mt-2 text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                        clip-rule="evenodd" />
                </svg>
                İlanın asıl sahibi (mülk sahibi)
            </p>
        </div>

        {{-- İlgili Kişi --}}
        <div class="relative group">
            <label class="block text-sm font-semibold text-gray-700 dark:text-slate-200 mb-2 flex items-center gap-2 dark:text-slate-300">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                </svg>
                İlgili Kişi
            </label>

            <div class="context7-live-search relative" data-search-type="kisiler" data-endpoint="/api/kisiler/search">

                <input type="hidden" name="ilgili_kisi_id" id="ilgili_kisi_id"
                    value="{{ old('ilgili_kisi_id', $ilan->ilgili_kisi_id ?? '') }}">

                <div class="relative">
                    <div
                        class="absolute left-4 top-1/2 -translate-y-1/2
                                text-gray-400 dark:text-gray-500
                                transition-colors duration-200
                                group-focus-within:text-purple-600 dark:group-focus-within:text-purple-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <input type="text" id="ilgili_kisi_search" placeholder="İlgili kişi ara (opsiyonel)..."
                        autocomplete="off"
                        class="w-full pl-12 pr-4 py-3.5 text-base
                                  border-2 border-gray-300 dark:border-gray-600
                                  rounded-xl
                                  bg-gray-50 dark:bg-gray-800
                                  text-gray-900 dark:text-white
                                  placeholder-gray-400 dark:placeholder-gray-500
                                  focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10
                                  dark:focus:border-purple-400 dark:focus:ring-purple-400/10
                                  transition-all duration-300
                                  hover:border-gray-400 dark:hover:border-gray-500">
                </div>

                <div
                    class="context7-search-results
                            absolute z-[9999] w-full mt-2
                            bg-white dark:bg-gray-800
                            border-2 border-gray-200 dark:border-gray-700
                            rounded-xl shadow-2xl
                            hidden max-h-72 overflow-y-auto">
                </div>
            </div>

            <p class="mt-2 text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                        clip-rule="evenodd" />
                </svg>
                İlanla ilgili iletişime geçilecek kişi
            </p>
        </div>

        {{-- Danışman --}}
        <div class="relative group">
            <label class="block text-sm font-semibold text-gray-700 dark:text-slate-200 mb-2 flex items-center gap-2 dark:text-slate-300">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                </svg>
                Danışman
            </label>

            <div class="relative">
                <div
                    class="absolute left-4 top-1/2 -translate-y-1/2
                            text-gray-400 dark:text-gray-500
                            transition-colors duration-200
                            group-focus-within:text-purple-600 dark:group-focus-within:text-purple-400">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z" />
                    </svg>
                </div>

                <select name="danisman_id" id="danisman_id"
                    class="w-full pl-12 pr-10 py-3.5 text-base
                               border-2 border-gray-300 dark:border-gray-600
                               rounded-xl
                               bg-gray-50 dark:bg-gray-800
                               text-gray-900 dark:text-white
                               focus:border-purple-500 focus:ring-4 focus:ring-purple-500/10
                               dark:focus:border-purple-400 dark:focus:ring-purple-400/10
                               transition-all duration-300
                               hover:border-gray-400 dark:hover:border-gray-500
                               cursor-pointer
                               appearance-none
                               bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20fill%3D%22none%22%20viewBox%3D%220%200%2020%2020%22%3E%3Cpath%20stroke%3D%22%236B7280%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-width%3D%221.5%22%20d%3D%22m6%208%204%204%204-4%22%2F%3E%3C%2Fsvg%3E')]
                               bg-[length:1.5rem] bg-[right_0.5rem_center] bg-no-repeat">
                    <option value="">-- Danışman Seçin --</option>
                    @if (isset($users))
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}"
                                {{ old('danisman_id', $ilan->danisman_id ?? auth()->id()) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>

            <p class="mt-2 text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                        clip-rule="evenodd" />
                </svg>
                İlandan sorumlu danışman
            </p>
        </div>
    </div>

    {{-- CRM Quick Stats (Optional) --}}
    <div
        class="mt-8 p-5 rounded-xl
                bg-gradient-to-br from-purple-50 to-pink-50
                dark:from-purple-900/20 dark:to-pink-900/20
                border border-purple-200 dark:border-purple-800">
        <div class="flex items-center gap-3 text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-medium">
                💡 <strong>İpucu:</strong> Kişi yoksa "Yeni Kişi Ekle" butonuyla hızlıca ekleyebilirsiniz.
            </span>
        </div>
    </div>
</x-admin.ilanlar.components.elegant-form-wrapper>

@push('scripts')
    <script>
        // Context7 Live Search Integration
        // TODO: Live search functionality will be integrated from existing system
        console.log('✅ Kişi Seçimi - Modern UI loaded');
    </script>
@endpush
