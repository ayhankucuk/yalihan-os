@extends('layouts.frontend')

@section('title', 'Greece Golden Visa - Investment & Residency | Yalıhan Emlak')

@section('content')
    <div class="min-h-screen bg-white dark:bg-slate-900">
        {{-- Hero Section --}}
        <div class="relative overflow-hidden bg-emerald-900 py-20">
            <div class="absolute inset-0 opacity-20 dark:opacity-40">
                <img src="https://images.unsplash.com/photo-1503152397445-afc6139f7ad7?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80"
                    alt="Greece Investment" class="h-full w-full object-cover transition-all dark:brightness-75">
            </div>
            <div class="relative z-10 mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <h1 class="mb-6 text-4xl font-extrabold text-white md:text-6xl">
                    Golden Visa <span class="text-yellow-400">Greece</span>
                </h1>
                <p class="mx-auto mb-10 max-w-3xl text-xl text-emerald-100">
                    Secure your European residency through strategic real estate investment in the cradle of civilization.
                </p>
                <div class="flex justify-center gap-4">
                    <a href="#benefits"
                        class="rounded-lg bg-emerald-600 px-8 py-3 font-bold text-white shadow-lg transition-all hover:bg-emerald-700">Visa
                        Benefits</a>
                    <a href="#properties"
                        class="rounded-lg bg-white px-8 py-3 font-bold text-emerald-900 shadow-lg transition-all hover:bg-gray-100 dark:bg-slate-800 dark:text-emerald-100 dark:hover:bg-slate-700">View
                        Listings</a>
                </div>
            </div>
        </div>

        {{-- Market Highlights --}}
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 text-center md:grid-cols-3">
                <div
                    class="rounded-2xl border border-gray-100 bg-gray-50 p-8 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="mb-2 text-4xl font-bold text-emerald-600">€250k - €800k</div>
                    <div class="text-sm font-medium uppercase tracking-wider text-gray-600 dark:text-slate-300">Investment
                        Threshold</div>
                </div>
                <div
                    class="rounded-2xl border border-gray-100 bg-gray-50 p-8 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="mb-2 text-4xl font-bold text-blue-600">{{ $report['avg_net_yield'] ?? '4.5' }}%</div>
                    <div class="text-sm font-medium uppercase tracking-wider text-gray-600 dark:text-slate-300">Average Net
                        Yield</div>
                </div>
                <div
                    class="rounded-2xl border border-gray-100 bg-gray-50 p-8 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="mb-2 text-4xl font-bold text-purple-600">Schengen</div>
                    <div class="text-sm font-medium uppercase tracking-wider text-gray-600 dark:text-slate-300">Visa-Free
                        Travel</div>
                </div>
            </div>
        </div>

        {{-- Benefits Section --}}
        <div class="bg-gray-50 py-20 dark:bg-slate-800/50" id="benefits">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h2 class="mb-12 text-center text-3xl font-bold text-gray-900 dark:text-white">Program Benefits</h2>
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl bg-white p-6 shadow-md dark:bg-slate-900">
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400">
                            <i class="fas fa-passport text-xl"></i>
                        </div>
                        <h3 class="mb-2 font-bold text-gray-900 dark:text-white">EU Residency</h3>
                        <p class="text-sm text-gray-600 dark:text-slate-400">Immediate residency for the principal investor
                            and immediate family.</p>
                    </div>
                    <div class="rounded-xl bg-white p-6 shadow-md dark:bg-slate-900">
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400">
                            <i class="fas fa-plane text-xl"></i>
                        </div>
                        <h3 class="mb-2 font-bold text-gray-900 dark:text-white">Schengen Access</h3>
                        <p class="text-sm text-gray-600 dark:text-slate-400">Travel freely across 27 European countries
                            without additional visas.</p>
                    </div>
                    <div class="rounded-xl bg-white p-6 shadow-md dark:bg-slate-900">
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/40 dark:text-purple-400">
                            <i class="fas fa-home text-xl"></i>
                        </div>
                        <h3 class="mb-2 font-bold text-gray-900 dark:text-white">No Residence Requirement</h3>
                        <p class="text-sm text-gray-600 dark:text-slate-400">Maintain your residency without needing to live
                            in Greece full-time.</p>
                    </div>
                    <div class="rounded-xl bg-white p-6 shadow-md dark:bg-slate-900">
                        <div
                            class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-yellow-100 text-yellow-600 dark:bg-yellow-900/40 dark:text-yellow-400">
                            <i class="fas fa-euro-sign text-xl"></i>
                        </div>
                        <h3 class="mb-2 font-bold text-gray-900 dark:text-white">Investment Return</h3>
                        <p class="text-sm text-gray-600 dark:text-slate-400">Benefit from Greece's rebounding real estate
                            market and tourism income.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Properties Section --}}
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8" id="properties">
            <h2 class="mb-12 text-center text-3xl font-bold text-gray-900 dark:text-white">Qualifying Greek Portfolios</h2>
            <div class="grid grid-cols-1 gap-10 md:grid-cols-3">
                @foreach ($report['top_properties'] ?? [] as $prop)
                    @php $ilan = \App\Models\Ilan::find($prop['id']); @endphp
                    @if ($ilan)
                        <div
                            class="group overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-lg transition-all duration-300 hover:shadow-2xl dark:border-slate-700 dark:bg-slate-800">
                            <div class="relative h-48">
                                <img src="{{ $ilan->kapak_foto ?? 'https://source.unsplash.com/random/800x600?greece,island' }}"
                                    class="h-full w-full object-cover transition-all dark:brightness-75">
                            </div>
                            <div class="p-6">
                                <h3 class="mb-2 line-clamp-1 font-bold text-gray-900 dark:text-white">{{ $ilan->baslik }}
                                </h3>
                                <div class="mb-4 text-xl font-extrabold text-emerald-600">
                                    {{ number_format($ilan->fiyat) }} {{ $ilan->para_birimi }}
                                </div>
                                <div class="mb-4 flex items-center justify-between text-sm text-gray-500">
                                    <span>Yield: {{ $prop['yield'] }}%</span>
                                    <span>ROI: {{ $prop['roi'] }}%</span>
                                </div>
                                <a href="{{ route('ilanlar.show', $ilan->id) }}"
                                    class="block rounded-lg border-2 border-emerald-600 py-2 text-center font-bold text-emerald-600 transition-all hover:bg-emerald-600 hover:text-white">Investment
                                    Details</a>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
@endsection
