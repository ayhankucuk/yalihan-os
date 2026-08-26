@extends('admin.layouts.admin')

@section('title', 'AI Destekli Lead Listesi')

@section('content')
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100">Lead Yönetimi</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">AI destekli potansiyel müşteri havuzu</p>
            </div>
            <!-- Actions -->
            <div class="flex gap-2">
                <a href="{{ route('admin.leads.index', ['sentiment' => 'positive']) }}"
                   class="inline-flex items-center px-4 py-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-medium text-sm rounded-lg hover:bg-emerald-100 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z" />
                    </svg>
                    Sıcak Lead'ler
                </a>
            </div>
        </div>

        <!-- Smart Filters -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm">
            <form action="{{ route('admin.leads.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="q" value="{{ request('q') }}"
                           placeholder="İsim, platform veya telefon ara..."
                           class="w-full rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-white px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>
                <div class="w-full md:w-48">
                    <select name="sentiment" class="w-full rounded-lg border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-white px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">Tüm Durumlar</option>
                        <option value="positive" {{ request('sentiment') == 'positive' ? 'selected' : '' }}>Sıcak & Olumlu</option>
                        <option value="negative" {{ request('sentiment') == 'negative' ? 'selected' : '' }}>Soğuk / Riskli</option>
                    </select>
                </div>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-lg shadow-sm hover:shadow transition-all duration-200">
                    Filtrele
                </button>
            </form>
        </div>

        <!-- Lead Table -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Müşteri</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Platform</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">AI Skoru</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kazanma %</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Etiketler</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tarih</th>
                            <th class="px-6 py-4 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($leads as $lead)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold">
                                            {{ substr($lead->name ?? '?', 0, 1) }}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ $lead->name ?? 'İsimsiz' }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $lead->phone ?? $lead->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 dark:bg-slate-800 text-gray-800 dark:text-slate-200">
                                        {{ ucfirst($lead->platform) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 w-48">
                                    @php
                                        $score = \App\Models\AILeadScore::where('lead_id', $lead->id)->value('skor_degeri') ?? 50;
                                        $color = $score >= 80 ? 'bg-emerald-500' : ($score >= 50 ? 'bg-amber-500' : 'bg-gray-400');
                                        $textColor = $score >= 80 ? 'text-emerald-600 dark:text-emerald-400' : ($score >= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-500');
                                    @endphp
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-bold {{ $textColor }}">{{ $score }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                        <div class="{{ $color }} h-1.5 rounded-full" style="width: {{ $score }}%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 w-32">
                                    @php
                                        $winProb = \App\Models\AILeadScore::where('lead_id', $lead->id)->value('win_probability') ?? 0;
                                    @endphp
                                    <span class="text-xs font-bold text-blue-600 dark:text-blue-400">{{ $winProb }}%</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($lead->tags ?? [] as $tag)
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300">
                                                {{ $tag }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $lead->created_at->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('admin.leads.show', $lead->id) }}"
                                       class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium text-sm">
                                        Detay
                                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                    <p>Henüz lead bulunmuyor.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800">
                {{ $leads->links() }}
            </div>
        </div>
    </div>
@endsection
