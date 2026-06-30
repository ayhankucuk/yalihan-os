{{-- resources/views/admin/ilanlar/components/filter-panel.blade.php --}}
@props(['categories'])

<div class="p-4">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Status Filter -->
        <div class="relative">
            <label for="aktiflik-durumu-filter"
                class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400 mb-1.5 ml-0.5">@lang('admin.durum')</label>
            <div class="relative">
                <select id="aktiflik-durumu-filter" aria-label="@lang('admin.durum')"
                    class="w-full h-10 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-900 text-slate-700 dark:text-white text-sm focus:ring-1 focus:ring-indigo-500 transition-all appearance-none pl-3 pr-8">
                    <option value="">@lang('admin.all')</option>
                    @foreach (\App\Enums\IlanDurumu::cases() as $durum)
                        <option value="{{ $durum->value }}">{{ $durum->label() }}</option>
                    @endforeach
                </select>
                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </div>

        <!-- Category Filter -->
        <div class="relative">
            <label for="category-filter"
                class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400 mb-1.5 ml-0.5">@lang('admin.category')</label>
            <div class="relative">
                <select id="category-filter" aria-label="@lang('admin.category_filter')"
                    class="w-full h-10 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-900 text-slate-700 dark:text-white text-sm focus:ring-1 focus:ring-indigo-500 transition-all appearance-none pl-3 pr-8">
                    <option value="">@lang('admin.all')</option>
                    @if(isset($categories))
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    @endif
                </select>
                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </div>

        <!-- Search Input -->
        <div class="relative">
            <label for="search-input"
                class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400 mb-1.5 ml-0.5">@lang('admin.search')</label>
            <div class="relative">
                <input type="text" id="search-input" aria-label="@lang('admin.search_listings')" placeholder="@lang('admin.search_listings')..."
                    class="w-full h-10 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-900 text-slate-700 dark:text-white text-sm placeholder-slate-400 dark:placeholder-gray-500 focus:ring-1 focus:ring-indigo-500 transition-all pl-3 pr-8">
                <div class="absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
        </div>

        <!-- Filter Button -->
        <div class="flex items-end">
            <button id="filter-button" onclick="applyFilters()"
                class="w-full h-10 rounded-xl bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-bold shadow-sm hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-all active:scale-95 disabled:opacity-50 dark:shadow-none">
                <div class="flex items-center justify-center">
                    <svg id="filter-spinner" class="hidden animate-spin h-4 w-4 text-white mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg id="filter-icon" class="w-4 h-4 text-white mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <span id="filter-text">@lang('admin.filter')</span>
                </div>
            </button>
        </div>
    </div>
</div>
