{{-- 🛰️ CORTEX TAB NAVIGATOR --}}
<div class="flex items-center p-1.5 bg-slate-100 dark:bg-slate-900/50 rounded-2xl border border-slate-200 dark:border-slate-800 w-fit overflow-x-auto max-w-full">
    @php $activeTab = request('tab', 'active'); @endphp
    @php $counts = $tabCounts ?? []; @endphp

    <a href="{{ route('admin.ilanlar.index', array_merge(request()->except('page'), ['tab' => 'active'])) }}"
        class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-tighter transition-all duration-300 {{ $activeTab === 'active' ? 'bg-[#0A1628] text-[#C9A84C] shadow-lg scale-105 dark:bg-slate-800 dark:text-[#C9A84C]' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
        AKTİF <span class="ml-1 opacity-70">({{ $counts['active'] ?? 0 }})</span>
    </a>
    <a href="{{ route('admin.ilanlar.index', array_merge(request()->except('page'), ['tab' => 'expired'])) }}"
        class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-tighter transition-all duration-300 {{ $activeTab === 'expired' ? 'bg-[#0A1628] text-amber-400 shadow-lg scale-105 dark:bg-slate-800' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
        SÜRESİ DOLAN <span class="ml-1 opacity-70">({{ $counts['expired'] ?? 0 }})</span>
    </a>
    <a href="{{ route('admin.ilanlar.index', array_merge(request()->except('page'), ['tab' => 'passive'])) }}"
        class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-tighter transition-all duration-300 {{ $activeTab === 'passive' ? 'bg-[#0A1628] text-rose-400 shadow-lg scale-105 dark:bg-slate-800' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
        PASİF <span class="ml-1 opacity-70">({{ $counts['passive'] ?? 0 }})</span>
    </a>
    <a href="{{ route('admin.ilanlar.index', array_merge(request()->except('page'), ['tab' => 'office'])) }}"
        class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-tighter transition-all duration-300 {{ $activeTab === 'office' ? 'bg-[#0A1628] text-indigo-400 shadow-lg scale-105 dark:bg-slate-800' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
        OFİS <span class="ml-1 opacity-70">({{ $counts['office'] ?? 0 }})</span>
    </a>
    <a href="{{ route('admin.ilanlar.index', array_merge(request()->except('page'), ['tab' => 'drafts'])) }}"
        class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-tighter transition-all duration-300 {{ $activeTab === 'drafts' ? 'bg-[#0A1628] text-white shadow-lg scale-105 dark:bg-slate-800' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
        TASLAK <span class="ml-1 opacity-70">({{ $counts['drafts'] ?? 0 }})</span>
    </a>
    <a href="{{ route('admin.ilanlar.index', array_merge(request()->except('page'), ['tab' => 'deleted'])) }}"
        class="px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-tighter transition-all duration-300 {{ $activeTab === 'deleted' ? 'bg-[#0A1628] text-slate-400 shadow-lg scale-105 dark:bg-slate-800' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white' }}">
        SİLİNEN <span class="ml-1 opacity-70">({{ $counts['deleted'] ?? 0 }})</span>
    </a>
</div>
