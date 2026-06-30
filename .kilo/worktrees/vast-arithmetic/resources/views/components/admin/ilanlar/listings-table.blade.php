{{-- resources/views/components/admin/ilanlar/listings-table.blade.php --}}
@props(['listings'])

<div class="bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-900 rounded-xl shadow-sm dark:shadow-none overflow-hidden dark:bg-slate-900">
    <!-- Header -->
    <div class="p-4 border-b border-slate-100 dark:border-white/5 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                @lang('admin.listing_list')
            </h2>
        </div>
        <div class="flex items-center gap-2">
            <div class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-white/10 text-[10px] font-bold text-slate-600 dark:text-gray-400 uppercase tracking-wider">
                {{ $listings->total() }} @lang('admin.listings')
            </div>
        </div>
    </div>

    <!-- Table View -->
    <div x-show="viewMode === 'table'" class="overflow-x-auto" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-white/5">
            <thead>
                <tr class="bg-slate-50 dark:bg-gray-900/50 border-b border-slate-100 dark:border-white/5 dark:bg-slate-900">
                    <th class="px-4 py-3 text-left">
                        <input type="checkbox" id="select-all-listings"
                            class="rounded border-slate-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 transition-all bg-white dark:bg-slate-900" x-model="selectAll"
                            @change="toggleSelectAll()">
                    </th>
                    <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">@lang('admin.listing')</th>
                    <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">@lang('admin.category')</th>
                    <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">@lang('admin.yayin_durumu')</th>
                    <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">@lang('admin.risk')</th>
                    <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">@lang('admin.price')</th>
                    <th class="px-4 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">@lang('admin.actions')</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/5" id="listings-table-body">
                @forelse($listings ?? [] as $listing)
                    @php /** @var \App\Models\Ilan $listing */ @endphp
                    <tr class="group hover:bg-slate-100 dark:hover:bg-slate-900 border-b border-slate-50 dark:border-slate-900 transition-all duration-200">
                        <td class="px-4 py-3">
                            <input type="checkbox"
                                class="row-checkbox rounded border-slate-300 dark:border-white/10 text-indigo-600 focus:ring-indigo-500 transition-all bg-white dark:bg-slate-900"
                                value="{{ $listing->id }}" x-model="selectedIds" @change="updateSelectAll()">
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="relative flex-shrink-0">
                                    <img class="h-10 w-10 rounded-lg object-cover border border-slate-200 dark:border-white/10"
                                        src="{{ $listing->fotograflar?->first() ? Storage::url($listing->fotograflar->first()->dosya_yolu) : asset('images/default-property.jpg') }}"
                                        alt="{{ $listing->baslik }}" loading="lazy">
                                    @if($listing->goruntulenme > 100)
                                        <span class="absolute -top-1.5 -right-1.5 px-1 py-0.5 bg-rose-500 text-white text-[8px] font-bold rounded shadow-sm dark:shadow-none">HOT</span>
                                    @endif
                                </div>
                                <div class="space-y-1">
                                    <div class="text-sm font-bold text-slate-800 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                        {{ Str::limit($listing->baslik, 40) }}
                                    </div>
                                    <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        <span>#{{ $listing->id }}</span>
                                        <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                        <span>{{ $listing->created_at?->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            <span class="px-3 py-1 rounded-xl bg-slate-100 dark:bg-gray-900/40 text-xs font-bold text-slate-600 dark:text-slate-200 border border-slate-200/50 dark:border-white/5">
                                {{ $listing->altKategori?->name ?? '—' }}
                            </span>
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            @php
                                $yayinDurumu = \App\Enums\IlanDurumu::tryFrom($listing->yayin_durumu);
                                $yayinDurumuBadgeClass = match ($yayinDurumu?->color()) {
                                    'green' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                    'blue' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                    'yellow' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                                    'slate' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                };
                            @endphp
                            @if($yayinDurumu)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-black uppercase tracking-tighter {{ $yayinDurumuBadgeClass }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>
                                    {{ $yayinDurumu->label() }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap">
                            @if(isset($listing->churn_risk))
                                @php
                                    $riskLevel = $listing->churn_risk['level'] ?? 'low';
                                    $riskScore = $listing->churn_risk['score'] ?? 0;
                                    $riskColor = match($riskLevel) {
                                        'critical' => 'from-rose-500 to-red-600 shadow-rose-500/30',
                                        'warning' => 'from-amber-500 to-orange-600 shadow-amber-500/30',
                                        default => 'from-emerald-500 to-green-600 shadow-emerald-500/30'
                                    };
                                @endphp
                                <div class="group/risk relative">
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-gradient-to-br {{ $riskColor }} text-white text-[10px] font-black shadow-lg dark:shadow-none cursor-help group-hover:scale-105 transition-transform">
                                        {{ $riskScore }}% RISK
                                    </div>
                                    @if(!empty($listing->churn_risk['reasons']))
                                        <div class="absolute bottom-full left-0 mb-3 w-56 p-4 rounded-2xl backdrop-blur-2xl bg-slate-900/90 text-white text-xs border border-white/10 opacity-0 group-hover/risk:opacity-100 pointer-events-none transition-all duration-300 z-50 translate-y-2 group-hover/risk:translate-y-0 shadow-2xl">
                                            <div class="font-black uppercase tracking-widest mb-2 text-indigo-400">Risk Analizi</div>
                                            <ul class="space-y-1.5 opacity-80 font-medium">
                                                @foreach($listing->churn_risk['reasons'] as $reason)
                                                    <li class="flex gap-2"><span>•</span> {{ $reason }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap font-black text-slate-800 dark:text-white">
                            {{ number_format($listing->fiyat, 0, ',', '.') }}
                            <span class="text-[10px] text-slate-400 ml-0.5">{{ $listing->para_birimi }}</span>
                        </td>
                        <td class="px-6 py-5 whitespace-nowrap text-sm">
                            <div class="flex gap-2 opacity-40 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.ilanlar.edit', $listing->id) }}" class="p-2 rounded-xl bg-white dark:bg-slate-900 text-indigo-600 dark:text-indigo-400 shadow-sm border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-gray-700 transition-all dark:shadow-none" title="@lang('admin.edit')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </a>
                                <a href="{{ route('admin.ilanlar.show', $listing->id) }}" class="p-2 rounded-xl bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-sm border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-gray-700 transition-all dark:shadow-none" title="@lang('admin.show_details')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </a>
                                <button onclick="deleteRow({{ $listing->id }})" class="p-2 rounded-xl bg-white dark:bg-slate-900 text-rose-500 dark:text-rose-400 shadow-sm border border-slate-200 dark:border-white/10 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-all dark:shadow-none" title="@lang('admin.delete')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-20 text-center font-bold text-slate-400 italic">@lang('admin.no_listings_found')</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Grid View -->
    <div x-show="viewMode === 'grid'" class="p-4" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
        <div id="listings-grid-body" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            @forelse($listings ?? [] as $listing)
                @php /** @var \App\Models\Ilan $listing */ @endphp
                <div class="group relative bg-white dark:bg-gray-950 rounded-xl border border-slate-200 dark:border-white/10 overflow-hidden shadow-sm dark:shadow-none hover:shadow-md dark:hover:shadow-none transition-all duration-300 dark:bg-slate-900">
                    <div class="relative h-56 overflow-hidden">
                        <img class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                            src="{{ $listing->fotograflar?->first() ? Storage::url($listing->fotograflar->first()->dosya_yolu) : asset('images/default-property.jpg') }}" alt="{{ $listing->baslik }}">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 dark:from-black/80 to-transparent"></div>
                        <div class="absolute top-4 left-4 flex gap-2">
                             <input type="checkbox" value="{{ $listing->id }}" x-model="selectedIds" class="rounded-lg border-white/20 dark:border-white/10 bg-black/20 text-indigo-500 dark:text-indigo-400 pointer-events-auto transition-colors">
                        </div>
                        <div class="absolute bottom-4 left-4 right-4 flex justify-between items-end">
                            <div>
                                <div class="text-white font-black text-lg tracking-tight leading-tight">{{ number_format($listing->fiyat, 0, ',', '.') }} <span class="text-xs">{{ $listing->para_birimi }}</span></div>
                                <div class="text-white/60 text-[10px] font-bold uppercase tracking-widest mt-0.5">{{ $listing->altKategori?->name }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <h3 class="font-bold text-slate-800 dark:text-white leading-snug line-clamp-2 min-h-[3rem]">{{ $listing->baslik }}</h3>

                        <!-- Mini Icons / Features -->
                        <div class="flex items-center gap-3 py-2 border-y border-white/20 dark:border-white/5">
                            <div class="flex items-center gap-1.5 text-slate-500 dark:text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <span class="text-xs font-bold">{{ $listing->goruntulenme }}</span>
                            </div>
                            <span class="w-1 h-1 rounded-full bg-slate-200 dark:bg-gray-700"></span>
                            <div class="flex items-center gap-1.5 text-slate-500 dark:text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                <span class="text-xs font-bold">Portföy#{{ $listing->id }}</span>
                            </div>
                        </div>

                        <div class="flex justify-between items-center gap-2">
                            <a href="{{ route('admin.ilanlar.edit', $listing->id) }}" class="flex-1 py-2 bg-indigo-600 dark:bg-indigo-700 hover:bg-indigo-700 dark:hover:bg-indigo-600 text-white text-[10px] font-black rounded-lg text-center transition-all shadow-lg dark:shadow-none active:scale-95">DÜZENLE</a>
                            <a href="{{ route('admin.ilanlar.show', $listing->id) }}" class="flex-1 py-2 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-white/10 text-[10px] font-bold rounded-lg text-center transition-all hover:bg-slate-50 dark:hover:bg-gray-700">DETAY GÖR</a>
                            <button onclick="deleteRow({{ $listing->id }})" class="p-2 bg-rose-50 dark:bg-rose-950/30 text-rose-500 dark:text-rose-400 rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/40 transition-all active:scale-90">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-20 text-center font-bold text-slate-400 italic">@lang('admin.no_listings_found')</div>
            @endforelse
        </div>
    </div>
</div>

