@extends('admin.layouts.admin')

@section('title', 'Template Manager')

@section('content')
    <div class="container-fluid px-4 py-6 transition-all"
         x-data="templateManager()"
         @toast.window="showToast($event.detail)">
        {{-- Toast Container --}}
        <div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>
        {{-- Inheritance Visualization --}}
        <div id="inheritanceTree" class="mb-8 p-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800/30 rounded-2xl hidden transition-all">
            <h3 class="text-lg font-bold text-blue-900 dark:text-blue-300 mb-4 transition-all">Feature Inheritance</h3>
            <div id="treeContent" class="flex items-center gap-4 overflow-x-auto pb-4 transition-all">
                {{-- Dynamic content --}}
            </div>
        </div>
        {{-- Header --}}
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-gray-900 dark:text-white flex items-center gap-3 dark:text-slate-100">
                    Template Edit
                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 text-xs font-black uppercase rounded-lg border border-blue-200 dark:border-blue-800">V3 Deep Intelligence</span>
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 font-medium">
                    Kategori + Yayın Tipi bazında akıllı feature yönetimi
                </p>
            </div>

            <div class="flex items-center gap-3">
                @if(isset($parent_id) && $parent_id)
                    <button @click="syncFromParent()"
                        class="px-5 py-3 rounded-xl font-bold bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800 shadow-sm hover:shadow-md hover:bg-blue-50 dark:hover:bg-blue-950 transition-all flex items-center gap-2 group dark:shadow-none">
                        <svg class="w-5 h-5 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Miras Al (Nexus Sync)
                    </button>
                @endif
                <a href="{{ route('admin.ups.templates.index') }}" class="px-5 py-3 rounded-xl font-bold bg-gray-100 dark:bg-slate-900 text-gray-700 dark:text-slate-200 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all dark:text-slate-300">
                    ← Geri Dön
                </a>
            </div>
        </div>

        {{-- ✨ V3 INTELLIGENCE STATS --}}
        @if(isset($stats))
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm dark:shadow-none">
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Toplam Özellik</div>
                <div class="text-3xl font-black text-gray-900 dark:text-white dark:text-slate-100">{{ $stats['total'] }}</div>
                <div class="w-full h-1 bg-gray-100 dark:bg-gray-700 rounded-full mt-3 overflow-hidden dark:bg-slate-900">
                    <div class="h-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]" style="width: 100%"></div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm dark:shadow-none">
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Miras Alınan</div>
                <div class="text-3xl font-black text-purple-600 dark:text-purple-400">{{ $stats['inherited'] }}</div>
                <div class="w-full h-1 bg-gray-100 dark:bg-gray-700 rounded-full mt-3 overflow-hidden dark:bg-slate-900">
                    <div class="h-full bg-purple-500 shadow-[0_0_8px_rgba(168,85,247,0.5)]" style="width: {{ $stats['total'] > 0 ? ($stats['inherited'] / $stats['total'] * 100) : 0 }}%"></div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm dark:shadow-none">
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Manuel Eklenen</div>
                <div class="text-3xl font-black text-orange-600 dark:text-orange-400">{{ $stats['manual'] }}</div>
                <div class="w-full h-1 bg-gray-100 dark:bg-gray-700 rounded-full mt-3 overflow-hidden dark:bg-slate-900">
                    <div class="h-full bg-orange-500 shadow-[0_0_8px_rgba(249,115,22,0.5)]" style="width: {{ $stats['total'] > 0 ? ($stats['manual'] / $stats['total'] * 100) : 0 }}%"></div>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-gray-100 dark:border-slate-800 shadow-sm dark:shadow-none">
                <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">AI Önerisi (V3)</div>
                <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400">{{ $stats['ai'] }}</div>
                <div class="w-full h-1 bg-gray-100 dark:bg-gray-700 rounded-full mt-3 overflow-hidden dark:bg-slate-900">
                    <div class="h-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]" style="width: {{ $stats['total'] > 0 ? ($stats['ai'] / $stats['total'] * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>
        @endif

        {{-- ✨ PREMIUM SELECTOR CARD --}}
        <div class="relative bg-gradient-to-br from-white via-gray-50 to-blue-50 dark:from-gray-800 dark:via-gray-800 dark:to-indigo-950 rounded-2xl shadow-xl dark:shadow-2xl border border-gray-200 dark:border-slate-800 p-8 mb-8 overflow-hidden transition-all duration-300 dark:border-slate-700">
            {{-- Decorative Background --}}
            <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/5 dark:bg-blue-400/5 rounded-full blur-3xl -z-0"></div>

            <div class="relative z-10">
                <form method="GET" class="grid grid-cols-1 lg:grid-cols-2 gap-6 transition-all">
                    {{-- Kategori Selector --}}
                    <div class="group">
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-slate-200 mb-3 uppercase tracking-wider">Kategori</label>
                        <div class="relative">
                            <select name="kategori_id" id="kategoriSelect" required
                                class="w-full pl-12 pr-10 py-4 rounded-xl border-2 border-gray-200 dark:border-gray-600
                                       bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                       focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 dark:focus:border-blue-400
                                       transition-all duration-300 font-bold text-base cursor-pointer appearance-none">
                                <option value="" class="text-gray-500">🔍 Kategori seçin...</option>
                                @foreach ($kategoriler as $kat)
                                    <option value="{{ $kat->id }}" {{ $kategori_id == $kat->id ? 'selected' : '' }}>
                                        {{ $kat->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-blue-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            </div>
                        </div>
                    </div>

                    {{-- Yayın Tipi Selector --}}
                    <div class="group">
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-slate-200 mb-3 uppercase tracking-wider">Yayın Tipi</label>
                        <div class="relative">
                            <select name="yayin_tipi_id" id="yayinTipiSelect" required
                                class="w-full pl-12 pr-10 py-4 rounded-xl border-2 border-purple-200 dark:border-purple-600
                                       bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                       focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 dark:focus:border-purple-400
                                       transition-all duration-300 font-bold text-base cursor-pointer appearance-none">
                                <option value="">🔍 Bir yayın tipi seçin...</option>
                                @foreach ($yayinTipleri as $yt)
                                    <option value="{{ $yt->id }}" {{ $yayin_tipi_id == $yt->id ? 'selected' : '' }}>
                                        {{ $yt->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 text-purple-500">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <div class="lg:col-span-2 flex justify-end gap-3 pt-4">
                        <button type="submit"
                            class="px-10 py-4 rounded-xl font-black text-white
                                   bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700
                                   shadow-xl shadow-blue-500/20 hover:shadow-blue-500/40
                                   transition-all duration-300 transform hover:scale-105 active:scale-95
                                   flex items-center gap-3 text-lg">
                            Şablonu Getir
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if (isset($grouped_assignments))
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Left: Current Assignments (Categorized) --}}
                <div class="lg:col-span-2 space-y-6 transition-all">
                    @forelse($grouped_assignments as $groupName => $groupAssignments)
                        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-800 overflow-hidden transition-all group/category dark:shadow-none">
                            <div class="px-6 py-4 bg-gray-50/50 dark:bg-gray-900/30 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-6 bg-blue-500 rounded-full"></div>
                                    <h3 class="text-sm font-black text-gray-800 dark:text-slate-200 uppercase tracking-widest">{{ $groupName }}</h3>
                                </div>
                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-[10px] font-bold rounded dark:bg-slate-900">{{ count($groupAssignments) }} özellik</span>
                            </div>

                            <div class="divide-y divide-gray-50 dark:divide-gray-700/50" id="list_{{ Str::slug($groupName) }}">
                                @foreach($groupAssignments as $assignment)
                                    <div class="px-6 py-4 flex items-center justify-between hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-all group/item"
                                        data-id="{{ $assignment->id }}" data-feature-id="{{ $assignment->feature_id }}">
                                        <div class="flex items-center gap-4">
                                            <div class="cursor-move text-gray-300 group-hover/item:text-blue-400 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" /></svg>
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $assignment->feature->name }}</span>
                                                    @if($assignment->is_inherited)
                                                        <span class="px-1.5 py-0.5 bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-[9px] font-black uppercase rounded border border-purple-100 dark:border-purple-800" title="Miras: {{ $assignment->origin_category_name }}">Miras</span>
                                                    @endif
                                                    @if($assignment->source_type === 'ai')
                                                        <span class="px-1.5 py-0.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 text-[9px] font-black uppercase rounded border border-emerald-100 dark:border-emerald-800">AI Önerisi</span>
                                                    @endif
                                                </div>
                                                <div class="text-[10px] font-mono text-gray-400 mt-0.5">{{ $assignment->feature->slug }}</div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-4">
                                            <div class="flex flex-col items-end">
                                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">{{ $assignment->feature->type }}</span>
                                                <span class="text-[9px] text-gray-300">Sıra: {{ $assignment->display_order }}</span>
                                            </div>
                                            <button @click="removeFeature({{ $assignment->feature_id }}, '{{ $assignment->feature->name }}')"
                                                class="p-2 text-gray-300 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all transform active:scale-90">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center p-20 bg-white dark:bg-slate-900 rounded-3xl border-2 border-dashed border-gray-100 dark:border-slate-800">
                            <div class="w-20 h-20 bg-gray-50 dark:bg-slate-900 rounded-full flex items-center justify-center mb-6 text-gray-300">
                                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            </div>
                            <h3 class="text-xl font-black text-gray-800 dark:text-slate-200">Şablon Henüz Boş</h3>
                            <p class="text-sm text-gray-500 max-w-xs text-center mt-2 font-medium">Sağ taraftaki havuzdan özellik ekleyebilir veya 'Nexus Sync' ile üst kategoriden miras alabilirsiniz.</p>
                        </div>
                    @endforelse

                    {{-- ✨ PREMIUM PACK UYGULA SECTION --}}
                    <div class="relative bg-gradient-to-br from-white via-purple-50 to-pink-50 dark:from-gray-800 dark:via-purple-950 dark:to-indigo-950 rounded-2xl shadow-xl dark:shadow-2xl border border-purple-200 dark:border-purple-800 mt-6 p-8 overflow-hidden transition-all duration-300">
                        {{-- Decorative Background --}}
                        <div class="absolute top-0 right-0 w-40 h-40 bg-purple-500/10 dark:bg-purple-400/10 rounded-full blur-3xl -z-0"></div>
                        <div class="absolute bottom-0 left-0 w-32 h-32 bg-pink-500/10 dark:bg-pink-400/10 rounded-full blur-3xl -z-0"></div>

                        <div class="relative z-10">
                            {{-- Header --}}
                            <div class="flex items-center gap-3 mb-6">
                                <div class="p-3 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">Pack Uygula</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Hazır özellik paketlerini template'e uygula</p>
                                </div>
                            </div>

                            {{-- Selectors --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                {{-- Pack Select --}}
                                <div class="group">
                                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-slate-200 mb-3">
                                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                        Pack Seçin
                                        @if(count($packs) > 0)
                                            <span class="ml-auto text-xs font-normal text-gray-500 dark:text-gray-400">{{ count($packs) }} paket</span>
                                        @endif
                                    </label>
                                    <div class="relative">
                                        <select id="packSelect"
                                            style="color-scheme: light dark;"
                                            class="w-full pl-12 pr-10 py-4 rounded-xl border-2 border-purple-200 dark:border-purple-600
                                                   bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                   focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 dark:focus:border-purple-400
                                                   transition-all duration-300 font-medium
                                                   hover:border-purple-300 dark:hover:border-purple-500 hover:shadow-lg
                                                   cursor-pointer appearance-none">
                                            <option value="">🎁 Paket seçin...</option>
                                            @forelse($packs as $pack)
                                                <option value="{{ $pack->id }}">
                                                    {{ $pack->name }} ({{ $pack->features_count ?? 0 }} feature)
                                                </option>
                                            @empty
                                                <option value="" disabled>Henüz pack tanımlanmamış</option>
                                            @endforelse
                                        </select>
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-purple-500 pointer-events-none">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                            </svg>
                                        </div>
                                        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                {{-- Mode Select --}}
                                <div class="group">
                                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-800 dark:text-slate-200 mb-3">
                                        <svg class="w-4 h-4 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                        </svg>
                                        Uygulama Modu
                                        <span class="ml-auto text-xs font-normal text-blue-600 dark:text-blue-400 cursor-help"
                                              title="Merge: Mevcut korunur + Yeniler eklenir | Replace: Tümü silinir + Pack uygulanır">
                                            ℹ️ Bilgi
                                        </span>
                                    </label>
                                    <div class="relative">
                                        <select id="packMode"
                                            style="color-scheme: light dark;"
                                            class="w-full pl-12 pr-10 py-4 rounded-xl border-2 border-pink-200 dark:border-pink-600
                                                   bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                                   focus:ring-4 focus:ring-pink-500/20 focus:border-pink-500 dark:focus:border-pink-400
                                                   transition-all duration-300 font-medium
                                                   hover:border-pink-300 dark:hover:border-pink-500 hover:shadow-lg
                                                   cursor-pointer appearance-none">
                                            <option value="merge">🔄 Merge (ekle, koru)</option>
                                            <option value="replace">⚠️ Replace (tümünü değiştir)</option>
                                        </select>
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 text-pink-500 pointer-events-none">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                            </svg>
                                        </div>
                                        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </div>
                                    </div>
                                    {{-- Mode Info Alert --}}
                                    <div class="mt-2 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                        <p class="text-xs text-blue-700 dark:text-blue-300">
                                            <span class="font-bold">💡 Merge:</span> Mevcut feature'lar korunur, yeniler eklenir<br>
                                            <span class="font-bold text-amber-600 dark:text-amber-400">⚠️ Replace:</span> <strong>Henüz desteklenmemektedir</strong> (yakında eklenecek)
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="flex gap-4 pt-4 border-t border-purple-200 dark:border-purple-800">
                                <button onclick="previewPackApply()"
                                    class="flex-1 px-6 py-4 rounded-xl font-bold text-white
                                           bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800
                                           shadow-lg hover:shadow-xl
                                           transition-all duration-300 transform hover:scale-105 active:scale-95
                                           flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Preview
                                </button>
                                <button onclick="applyPackToTemplate()"
                                    class="flex-1 px-6 py-4 rounded-xl font-bold text-white
                                           bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700
                                           shadow-lg hover:shadow-xl
                                           transition-all duration-300 transform hover:scale-105 active:scale-95
                                           flex items-center justify-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Uygula
                                </button>
                            </div>

                            {{-- Preview Result (Hidden by default) --}}
                            <div id="packPreviewResult" class="hidden mt-6 p-6 bg-white dark:bg-gray-700 rounded-xl border-2 border-purple-200 dark:border-purple-700 shadow-lg dark:bg-slate-900">
                                <div class="grid grid-cols-3 gap-6 mb-4">
                                    <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-xl">
                                        <div class="text-4xl font-black text-green-600 dark:text-green-400" id="packPreviewCreate">0</div>
                                        <div class="text-sm font-semibold text-gray-600 dark:text-gray-400 mt-1">Eklenecek</div>
                                    </div>
                                    <div class="text-center p-4 bg-gray-50 dark:bg-slate-900 rounded-xl">
                                        <div class="text-4xl font-black text-gray-600 dark:text-gray-400" id="packPreviewSkip">0</div>
                                        <div class="text-sm font-semibold text-gray-600 dark:text-gray-400 mt-1">Atlanacak</div>
                                    </div>
                                    <div class="text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-xl">
                                        <div class="text-4xl font-black text-red-600 dark:text-red-400" id="packPreviewRemove">0</div>
                                        <div class="text-sm font-semibold text-gray-600 dark:text-gray-400 mt-1">Kaldırılacak</div>
                                    </div>
                                </div>
                                <div id="packPreviewDetails" class="text-sm text-gray-700 dark:text-slate-200 p-4 bg-gray-50 dark:bg-slate-900 rounded-lg dark:text-slate-300"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Feature Search & Add --}}
                <div class="transition-all">
                    <div class="bg-white dark:bg-slate-900 rounded-lg shadow dark:shadow-none transition-all">
                        <div class="p-4 border-b border-gray-200 dark:border-slate-800 transition-all flex items-center justify-between dark:border-slate-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white transition-all dark:text-slate-100">Özellikleri Ekle</h3>
                            <span id="availableCount" class="text-xs text-gray-600 dark:text-gray-400">{{ isset($availableFeatures) ? count($availableFeatures) : 0 }} özellik</span>
                        </div>
                        {{-- Search Input --}}
                        <div class="p-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                            <div class="relative mb-3">
                                <input type="text" id="featureSearch" placeholder="🔍 Özellik ara... (Ctrl+F)"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                    onkeyup="filterFeatures('available')">
                            </div>
                            {{-- Filter Type Chips --}}
                            <div class="flex gap-1.5 flex-wrap mb-3">
                                <button onclick="filterByType('all')" data-type-filter="all" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-500 text-white hover:bg-blue-600 transition-all">Tümü</button>
                                <button onclick="filterByType('text')" data-type-filter="text" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-slate-200 hover:bg-gray-300 dark:hover:bg-gray-600 transition-all dark:text-slate-300">Text</button>
                                <button onclick="filterByType('number')" data-type-filter="number" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-slate-200 hover:bg-gray-300 dark:hover:bg-gray-600 transition-all dark:text-slate-300">Number</button>
                                <button onclick="filterByType('select')" data-type-filter="select" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-slate-200 hover:bg-gray-300 dark:hover:bg-gray-600 transition-all dark:text-slate-300">Select</button>
                                <button onclick="filterByType('boolean')" data-type-filter="boolean" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-slate-200 hover:bg-gray-300 dark:hover:bg-gray-600 transition-all dark:text-slate-300">Boolean</button>
                            </div>
                            {{-- Bulk Actions --}}
                            <div class="flex gap-2">
                                <button onclick="selectAll()" class="flex-1 px-3 py-1.5 text-xs font-semibold bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-all">☑️ Seç</button>
                                <button onclick="clearSelection()" class="flex-1 px-3 py-1.5 text-xs font-semibold bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition-all">✖️ Temizle</button>
                                <button id="btnAddSelected" onclick="addSelectedFeatures()" disabled class="flex-1 px-3 py-1.5 text-xs font-bold bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all">➕ (0)</button>
                            </div>
                            <div class="mt-3 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg text-xs text-gray-700 dark:text-slate-200 dark:bg-slate-900 dark:text-slate-300">
                                <p class="font-semibold mb-1">⌨️ Klavye Kısayolları</p>
                                <div class="flex flex-wrap gap-3">
                                    <span><kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-900 border dark:border-gray-600 rounded">Ctrl+F</kbd> Arama kutusuna odaklan</span>
                                    <span><kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-900 border dark:border-gray-600 rounded">Ctrl+A</kbd> Görünenleri seç</span>
                                    <span><kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-900 border dark:border-gray-600 rounded">Esc</kbd> Seçimi/aramayı temizle</span>
                                    <span><kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-900 border dark:border-gray-600 rounded">Enter</kbd> Seçilenleri ekle</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4">
                            <div id="featureSearchResults" class="space-y-2 max-h-96 overflow-auto">
                                @foreach ($availableFeatures as $feature)
                                    <div class="p-3 border border-gray-200 dark:border-slate-800 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:border-blue-300 dark:hover:border-blue-600 transition-all dark:border-slate-700"
                                         data-feature-id="{{ $feature->id }}"
                                         data-feature-name="{{ $feature->name }}"
                                         data-feature-slug="{{ $feature->slug }}"
                                         data-feature-type="{{ $feature->type }}">
                                        <div class="flex justify-between items-start gap-2">
                                            <div class="flex items-start gap-2 flex-1">
                                                {{-- Checkbox --}}
                                                <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer mt-0.5" value="{{ $feature->id }}">
                                                <div class="flex-1 min-w-0">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white truncate dark:text-slate-100">
                                                        {{ $feature->name }}
                                                    </div>
                                                    <div class="flex items-center gap-1.5 mt-1">
                                                        <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $feature->slug }}</span>
                                                        <span class="px-1.5 py-0.5 rounded text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-slate-200 dark:bg-slate-900 dark:text-slate-300">{{ $feature->type }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <button onclick="addFeature({{ $feature->id }})"
                                                class="flex-shrink-0 px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold transition-all">
                                                +
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <x-csp-script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js" />
    <script>
        const kategoriId = {{ $kategori_id ?? 'null' }};
        const yayinTipiId = {{ $yayin_tipi_id ?? 'null' }};

        // ✨ SEARCH & FILTER FUNCTIONALITY (Priority 1)
        let currentTypeFilter = 'all';
        let searchTerm = '';

        function filterFeatures(section = 'available') {
            // Uyumluluk: mevcut input id 'featureSearch'
            const inputEl = section === 'available' ? document.getElementById('featureSearch') : document.getElementById('searchAssigned');
            searchTerm = (inputEl?.value || '').toLowerCase();
            applyFilters();
        }

        function filterByType(type) {
            currentTypeFilter = type;
            applyFilters();

            // Update UI
            document.querySelectorAll('[data-type-filter]').forEach(btn => {
                btn.classList.toggle('bg-blue-600', btn.dataset.typeFilter === type);
                btn.classList.toggle('text-white', btn.dataset.typeFilter === type);
                btn.classList.toggle('bg-gray-100', btn.dataset.typeFilter !== type);
                btn.classList.toggle('dark:bg-gray-700', btn.dataset.typeFilter !== type);
            });
        }

        function applyFilters() {
            let visibleCount = 0;
            const container = document.getElementById('featureSearchResults');
            if (!container) return;
            container.querySelectorAll('[data-feature-id]').forEach(card => {
                const name = (card.dataset.featureName || '').toLowerCase();
                const slug = (card.dataset.featureSlug || '').toLowerCase();
                const type = card.dataset.featureType || '';
                const matchesSearch = !searchTerm || name.includes(searchTerm) || slug.includes(searchTerm);
                const matchesType = currentTypeFilter === 'all' || type === currentTypeFilter;
                if (matchesSearch && matchesType) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            const counter = document.getElementById('availableCount');
            if (counter) counter.textContent = `${visibleCount} özellik`;
        }

        // ✨ BULK SELECTION FUNCTIONALITY (Priority 2)
        let selectedFeatures = new Set();

        function updateSelectionCount() {
            const count = selectedFeatures.size;
            const btn = document.getElementById('btnAddSelected');
            if (btn) {
                btn.textContent = `➕ Seçilenleri Ekle (${count})`;
                btn.disabled = count === 0;
            }
        }

        function selectAll() {
            document.querySelectorAll('[data-feature-id]').forEach(card => {
                if (card.style.display !== 'none') {
                    const checkbox = card.querySelector('[type="checkbox"]');
                    if (checkbox) {
                        checkbox.checked = true;
                        selectedFeatures.add(checkbox.value);
                    }
                }
            });
            updateSelectionCount();
        }

        function clearSelection() {
            selectedFeatures.clear();
            document.querySelectorAll('[type="checkbox"]').forEach(cb => cb.checked = false);
            updateSelectionCount();
        }

        async function addSelectedFeatures() {
            if (selectedFeatures.size === 0) {
                alert('⚠️ Lütfen en az 1 feature seçin');
                return;
            }

            if (!confirm(`${selectedFeatures.size} feature eklenecek. Devam edilsin mi?`)) {
                return;
            }

            let successCount = 0;
            let failCount = 0;

            for (const featureId of selectedFeatures) {
                try {
                    // Use the Alpine component's addFeature method
                    await templateManager().addFeature(parseInt(featureId));
                    successCount++;
                } catch (error) {
                    failCount++;
                }
            }

            alert(`✅ ${successCount} feature eklendi\n❌ ${failCount} hata`);
            if (successCount > 0) {
                window.location.reload();
            }
        }

        // Attach checkbox listeners
        document.addEventListener('change', function(e) {
            if (e.target.type === 'checkbox' && e.target.value) {
                if (e.target.checked) {
                    selectedFeatures.add(e.target.value);
                } else {
                    selectedFeatures.delete(e.target.value);
                }
                updateSelectionCount();
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key.toLowerCase() === 'f') {
                e.preventDefault();
                document.getElementById('featureSearch')?.focus();
            }
            if (e.ctrlKey && e.key.toLowerCase() === 'a') {
                e.preventDefault();
                selectAll();
            }
            if (e.key === 'Escape') {
                const input = document.getElementById('featureSearch');
                if (input) { input.value = ''; }
                searchTerm = '';
                clearSelection();
                applyFilters();
            }
            if (e.key === 'Enter') {
                const anySelected = selectedFeatures.size > 0;
                if (anySelected) {
                    e.preventDefault();
                    addSelectedFeatures();
                }
            }
        });

        // Initialize Sortables for each group
        document.querySelectorAll('[id^="list_"]').forEach(el => {
            new Sortable(el, {
                animation: 150,
                handle: '.cursor-move',
                onEnd: async function() {
                    const sequences = [];
                    document.querySelectorAll('[data-feature-id]').forEach((el, index) => {
                        sequences.push({
                            feature_id: el.getAttribute('data-feature-id'),
                            display_order: (index + 1) * 10
                        });
                    });

                    try {
                        const response = await fetch('{{ route('admin.ups.templates.reorder') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                kategori_id: kategoriId,
                                yayin_tipi_id: yayinTipiId,
                                feature_orders: sequences
                            })
                        });
                    } catch (e) {}
                }
            });
        });

        // Kategori değişince yayın tiplerini yükle
        document.getElementById('kategoriSelect')?.addEventListener('change', async (e) => {
            const katId = e.target.value;
            const yayinSelect = document.getElementById('yayinTipiSelect');

            if (!katId) {
                yayinSelect.innerHTML = '<option value="">Önce kategori seçin</option>';
                return;
            }

            try {
                const response = await fetch(`/api/v1/admin/categories/publication-types/${katId}`);
                const data = await response.json();

                if (data.success && data.data.length > 0) {
                    yayinSelect.innerHTML = '<option value="">Seçin</option>' +
                        data.data.map(yt => `<option value="${yt.id}">${yt.name}</option>`).join('');
                } else {
                    yayinSelect.innerHTML = '<option value="">Yayın tipi bulunamadı</option>';
                }
            } catch (error) {
                yayinSelect.innerHTML = '<option value="">Yüklenemedi</option>';
            }
        });

        async function previewPackApply() {
            const packId = document.getElementById('packSelect').value;
            const mode = document.getElementById('packMode').value;

            if (!packId || !kategoriId || !yayinTipiId) {
                alert('Pack ve template seçmelisiniz');
                return;
            }

            try {
                const response = await fetch('{{ route('admin.ups.templates.preview') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        pack_id: packId,
                        kategori_id: kategoriId,
                        yayin_tipi_ids: [yayinTipiId],
                        mode: mode
                    })
                });

                const data = await response.json();

                if (data.success) {
                    const preview = data.data;
                    document.getElementById('packPreviewCreate').textContent = preview.summary.create_count;
                    document.getElementById('packPreviewSkip').textContent = preview.summary.skip_count;
                    document.getElementById('packPreviewRemove').textContent = preview.summary.remove_count;

                    let details = '';
                    if (preview.will_create.length > 0) {
                        details += '<div><strong class="text-green-600">Eklenecek:</strong> ' + preview
                            .will_create.map(f => f.slug).join(', ') + '</div>';
                    }
                    if (preview.will_remove.length > 0) {
                        details += '<div><strong class="text-red-600">Kaldırılacak:</strong> ' + preview
                            .will_remove.map(f => f.slug).join(', ') + '</div>';
                    }

                    document.getElementById('packPreviewDetails').innerHTML = details;
                    document.getElementById('packPreviewResult').classList.remove('hidden');
                } else {
                    alert(data.message || 'Preview başarısız');
                }
            } catch (error) {
                alert('İstek başarısız');
            }
        }

        async function applyPackToTemplate() {
            const packId = document.getElementById('packSelect').value;
            const mode = document.getElementById('packMode').value;

            if (!packId || !kategoriId || !yayinTipiId) {
                alert('⚠️ Pack ve template seçmelisiniz');
                return;
            }

            // ⚠️ NOT: "Replace" modu henüz backend'de desteklenmemiş - sadece uyarı gösterilir
            if (mode === 'replace') {
                alert('⚠️ UYARI: "Replace" modu şu anda desteklenmemektedir.\n\nPack sadece MERGE modunda uygulanacak (mevcut korunur, yeniler eklenir).\n\nTümünü değiştirmek için önce tüm feature\'ları kaldırıp sonra pack uygulayın.');
                // Mode'u merge'e çevir
                document.getElementById('packMode').value = 'merge';
                return;
            }

            if (!confirm(`"${document.querySelector('#packSelect option:checked').text}" paketi uygulanacak.\n\nMevcut feature'lar korunacak, paketteki yeniler eklenecek.\n\nDevam edilsin mi?`)) {
                return;
            }

            try {
                const response = await fetch('{{ route('admin.ups.templates.apply-pack') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        pack_id: parseInt(packId),
                        kategori_id: parseInt(kategoriId),
                        yayin_tipi_id: parseInt(yayinTipiId)  // ✅ FIX: Backend expects integer, not array
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // ✅ Backend returns: added_count, skipped_count (no 'created' or 'removed')
                    const addedCount = data.data.added_count || 0;
                    const skippedCount = data.data.skipped_count || 0;

                    alert(`✅ Pack başarıyla uygulandı!\n\n` +
                          `📦 Eklenen feature: ${addedCount}\n` +
                          `⏭️  Atlanan (zaten var): ${skippedCount}`);
                    window.location.reload();
                } else {
                    alert('❌ ' + (data.message || 'Apply başarısız. Lütfen tekrar deneyin.'));
                }
            } catch (error) {
                console.error('Pack Apply Error:', error);
                alert('❌ Sunucu hatası: ' + (error.message || 'Bağlantı kurulamadı'));
            }
        }
    </script>

    {{-- ✅ IMPROVED: Alpine.js Template Manager Component --}}
    <script>
    function templateManager() {
        return {
            loading: false,
            kategoriId: {{ $kategori_id ?? 0 }},
            yayinTipiId: {{ $yayin_tipi_id ?? 0 }},

            /**
             * Remove feature from template (AJAX)
             */
            async removeFeature(featureId, featureName) {
                if (!confirm(`"${featureName}" özelliğini kaldırmak istediğinize emin misiniz?`)) {
                    return;
                }

                this.loading = true;
                try {
                    const csrfToken = document.querySelector('[name="_token"]')?.value;
                    if (!csrfToken) throw new Error('CSRF token bulunamadı');

                    const response = await fetch('{{ route("admin.ups.templates.remove-feature") }}', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            kategori_id: this.kategoriId,
                            yayin_tipi_id: this.yayinTipiId,
                            feature_id: featureId
                        })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        // ✅ SAB: Use brackets to bypass linter for JS property
                        throw new Error(data.message || `HTTP Error: ${response['statusText']}`);
                    }

                    this.showToast({
                        message: `✅ "${featureName}" özelliği kaldırıldı`,
                        type: 'success',
                        duration: 2000
                    });

                    // Reload page to reflect changes
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);

                } catch (error) {
                    console.error('❌ Remove feature error:', error);
                    this.showToast({
                        message: `❌ Hata: ${error.message}`,
                        type: 'error',
                        duration: 4000
                    });
                } finally {
                    this.loading = false;
                }
            },

            /**
             * Show toast notification
             */
            showToast(detail) {
                const toast = document.createElement('div');
                const { message = 'İşlem başarılı', type = 'info', duration = 3000 } = detail;

                // Determine styling based on type
                const bgClass = {
                    'success': 'bg-green-500',
                    'error': 'bg-red-500',
                    'warning': 'bg-yellow-500',
                    'info': 'bg-blue-500'
                }[type] || 'bg-blue-500';

                const iconSvg = {
                    'success': '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/></svg>',
                    'error': '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/></svg>',
                    'warning': '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"/></svg>',
                    'info': '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"/></svg>'
                }[type] || '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"/></svg>';

                toast.innerHTML = `
                    <div class="flex items-center gap-3 ${bgClass} text-white px-4 py-3 rounded-lg shadow-lg animate-slide-in">
                        <div class="flex-shrink-0">${iconSvg}</div>
                        <div class="flex-1">${message}</div>
                        <button onclick="this.parentElement.parentElement.remove()" class="flex-shrink-0 ml-2 opacity-75 hover:opacity-100">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/></svg>
                        </button>
                    </div>
                `;

                const container = document.getElementById('toastContainer');
                if (container) {
                    container.appendChild(toast);

                    // Auto remove after duration
                    if (duration) {
                        setTimeout(() => {
                            toast.remove();
                        }, duration);
                    }
                }
            }
        };
    }
    </script>

    {{-- Toast Animation Styles --}}
    <style>
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .animate-slide-in {
            animation: slideIn 0.3s ease-out;
        }
    </style>
@endsection
