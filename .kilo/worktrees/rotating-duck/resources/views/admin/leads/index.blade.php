@extends('admin.layouts.admin')

@section('title', 'AI Destekli Lead Listesi')

@section('content')
    <div class="p-6">
        <!-- Page Header -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Lead Yönetimi</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">AI destekli potansiyel müşteri havuzu</p>
            </div>
            <!-- Actions -->
            <div class="flex gap-2">
                <a href="{{ route('admin.leads.index', ['sentiment' => 'positive']) }}"
                   class="inline-flex items-center px-4 py-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 font-semibold rounded-lg hover:bg-green-200 transition-colors">
                    <i class="fas fa-fire mr-2"></i> Sıcak Lead'ler
                </a>
            </div>
        </div>

        <!-- Smart Filters -->
        <div class="bg-white dark:bg-slate-900 p-4 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm mb-6 dark:shadow-none dark:border-slate-700">
            <form action="{{ route('admin.leads.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="q" value="{{ request('q') }}"
                           placeholder="İsim, platform veya telefon ara..."
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:shadow-none">
                </div>
                <div class="w-full md:w-48">
                    <select name="sentiment" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:shadow-none">
                        <option value="">Tüm Durumlar</option>
                        <option value="positive" {{ request('sentiment') == 'positive' ? 'selected' : '' }}>🔥 Sıcak & Mutlu</option>
                        <option value="negative" {{ request('sentiment') == 'negative' ? 'selected' : '' }}>❄️ Soğuk / Riskli</option>
                    </select>
                </div>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                    Filtrele
                </button>
            </form>
        </div>

        <!-- Lead Table -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
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
                                            <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">{{ $lead->name ?? 'İsimsiz' }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $lead->phone ?? $lead->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-slate-200 dark:bg-slate-900">
                                        {{ ucfirst($lead->platform) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 w-48">
                                    <!-- AI Score retrieval needs eager loading or optimizing,
                                         but for now we assume it's calculated or we query relation if available.
                                         Ideally controller passed it or we use relationship.
                                         Let's assume Lead has 'aiScore' relation or we mock it. -->
                                    @php
                                        // Quick retrieval for list view, ideal is Eager Loading in controller
                                        $score = \App\Models\AILeadScore::where('lead_id', $lead->id)->value('skor_degeri') ?? 50;
                                        $color = $score >= 80 ? 'bg-green-500' : ($score >= 50 ? 'bg-yellow-500' : 'bg-gray-400');
                                        $textColor = $score >= 80 ? 'text-green-600' : ($score >= 50 ? 'text-yellow-600' : 'text-gray-500');
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
                                       class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium text-sm">
                                        Detay <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-inbox text-4xl mb-3 opacity-20"></i>
                                    <p>Henüz lead bulunmuyor.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                {{ $leads->links() }}
            </div>
        </div>
    </div>
@endsection
