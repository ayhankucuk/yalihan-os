@extends('admin.layouts.admin')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">AI Owner Discovery Engine</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Piyasa Analizi ve Portföy Kazanım Fırsatları</p>
            </div>
            <button id="run-discovery-btn"
                class="rounded bg-blue-600 px-4 py-2 font-medium text-white shadow hover:bg-blue-700">
                <span class="material-symbols-outlined mr-2">sync</span> Motoru Çalıştır
            </button>
        </div>

        <!-- Filters placeholder -->
        <div
            class="mb-6 flex gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-colors dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Risk & Değer Sınıfı</label>
                <select class="w-48 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <option value="">Tümü</option>
                    <option value="PRIME_OWNER_TARGET">PRIME_OWNER_TARGET</option>
                    <option value="HIGH_VALUE_OWNER">HIGH_VALUE_OWNER</option>
                    <option value="MEDIUM_OPPORTUNITY">MEDIUM_OPPORTUNITY</option>
                    <option value="LOW_PRIORITY">LOW_PRIORITY</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Profil Tipi</label>
                <select class="w-48 rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    <option value="">Tümü</option>
                    <option value="INVESTOR">INVESTOR</option>
                    <option value="INDIVIDUAL_SELLER">INDIVIDUAL_SELLER</option>
                    <option value="DEVELOPER">DEVELOPER</option>
                </select>
            </div>
        </div>

        <!-- Opportunities Data Grid -->
        <div
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition-colors dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-900/30">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                <thead class="bg-slate-50 dark:bg-slate-800">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                            Cluster ID</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                            Profil Tipi</th>
                        <th
                            class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                            İlan Sayısı</th>
                        <th
                            class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                            Acquisition Score</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                            Owner Tier</th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                            Aksiyon</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                    @forelse($opportunities as $opp)
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                #{{ $opp->id }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span
                                    class="@if ($opp->owner_profile_type == 'INVESTOR') bg-purple-100 text-purple-800
                            @elseif($opp->owner_profile_type == 'DEVELOPER') bg-blue-100 text-blue-800
                            @else bg-gray-100 text-gray-800 @endif inline-flex rounded-full px-2 text-xs font-semibold leading-5">
                                    {{ $opp->owner_profile_type }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-900 dark:text-gray-100">
                                {{ $opp->listing_count }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                <div class="flex items-center justify-center">
                                    <span
                                        class="{{ $opp->owner_acquisition_score >= 75 ? 'text-green-600' : 'text-gray-600' }} text-sm font-bold">
                                        {{ $opp->owner_acquisition_score }}
                                    </span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <!-- Owner Tier Badges -->
                                @if ($opp->owner_tier === 'PRIME_OWNER_TARGET')
                                    <span
                                        class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-700 dark:bg-red-900 dark:text-red-300">
                                        {{ $opp->owner_tier }}
                                    </span>
                                @elseif($opp->owner_tier === 'HIGH_VALUE_OWNER')
                                    <span
                                        class="inline-flex rounded-full bg-orange-100 px-2 text-xs font-semibold leading-5 text-orange-700 dark:bg-orange-900 dark:text-orange-300">
                                        {{ $opp->owner_tier }}
                                    </span>
                                @elseif($opp->owner_tier === 'MEDIUM_OPPORTUNITY')
                                    <span
                                        class="inline-flex rounded-full bg-yellow-100 px-2 text-xs font-semibold leading-5 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">
                                        {{ $opp->owner_tier }}
                                    </span>
                                @else
                                    <span
                                        class="inline-flex rounded-full bg-slate-100 px-2 text-xs font-semibold leading-5 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $opp->owner_tier }}
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                <a href="#"
                                    class="rounded border border-blue-600 px-3 py-1 text-xs text-blue-600 hover:text-blue-900 dark:border-blue-400 dark:text-blue-400 dark:hover:text-blue-300">Aksiyon
                                    Öner</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-500">
                                Henüz owner cluster/opportunity tespit edilmedi. "Motoru Çalıştır" butonuna tıklayarak ilk
                                analizi başlatabilirsiniz.
                            </td>
                        </tr>
                    @endforelse
            </table>
        </div>
    </div>

    @push('scripts')
        <script>
            document.getElementById('run-discovery-btn').addEventListener('click', function() {
                const btn = this;
                const originalHtml = btn.innerHTML;

                btn.innerHTML = '<span class="material-symbols-outlined mr-2">progress_activity</span> Analiz Ediliyor...';
                btn.disabled = true;

                fetch('{{ route('advisor.owner-opportunities.run') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            window.location.reload();
                        } else {
                            alert('Bir hata oluştu: ' + (data.message || 'Bilinmeyen hata'));
                            btn.innerHTML = originalHtml;
                            btn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Ağ veya sunucu hatası oluştu.');
                        btn.innerHTML = originalHtml;
                        btn.disabled = false;
                    });
            });
        </script>
    @endpush
@endsection
