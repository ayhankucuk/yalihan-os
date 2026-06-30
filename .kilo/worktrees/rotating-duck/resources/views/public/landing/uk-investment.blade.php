@extends('layouts.frontend')

@section('title', 'UK Property Investment - High Yield Buy-to-Let | Yalıhan Emlak')

@section('content')
    <div class="min-h-screen bg-white dark:bg-slate-900">
        {{-- Hero Section --}}
        <div class="relative overflow-hidden bg-slate-900 py-20">
            <div class="absolute inset-0 opacity-20 dark:opacity-40">
                <img src="https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80"
                    alt="London Skyline" class="h-full w-full object-cover transition-all dark:brightness-75">
            </div>
            <div class="relative z-10 mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <div class="mb-6 flex justify-center">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/a/ae/Flag_of_the_United_Kingdom.svg"
                        class="w-16 rounded shadow-lg" alt="UK Flag">
                </div>
                <h1 class="mb-6 text-4xl font-extrabold text-white md:text-6xl">
                    UK <span class="text-red-500">Property</span> Investment
                </h1>
                <p class="mx-auto mb-10 max-w-3xl text-xl text-slate-300">
                    Access premium Buy-to-Let opportunities in the UK's high-growth northern cities and London.
                </p>
                <div class="flex justify-center gap-4">
                    <a href="#strategy"
                        class="rounded-lg bg-red-600 px-8 py-3 font-bold text-white shadow-lg transition-all hover:bg-red-700">Investment
                        Strategy</a>
                    <a href="#offers"
                        class="rounded-lg bg-white px-8 py-3 font-bold text-slate-900 shadow-lg transition-all hover:bg-gray-100 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700">View
                        UK Portfolio</a>
                </div>
            </div>
        </div>

        {{-- Market Highlights --}}
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-6 text-center md:grid-cols-4">
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-6 dark:border-slate-700 dark:bg-slate-800">
                    <div class="mb-1 text-3xl font-bold text-red-600">£2.2B+</div>
                    <div class="text-xs font-bold uppercase text-gray-500">Market Transactions</div>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-6 dark:border-slate-700 dark:bg-slate-800">
                    <div class="mb-1 text-3xl font-bold text-blue-600">7.2%</div>
                    <div class="text-xs font-bold uppercase text-gray-500">Manchester Avg Yield</div>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-6 dark:border-slate-700 dark:bg-slate-800">
                    <div class="mb-1 text-3xl font-bold text-green-600">20%</div>
                    <div class="text-xs font-bold uppercase text-gray-500">5yr Price Projection</div>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50 p-6 dark:border-slate-700 dark:bg-slate-800">
                    <div class="mb-1 text-3xl font-bold text-slate-900 dark:text-white">£ / GBP</div>
                    <div class="text-xs font-bold uppercase text-gray-500">Strong Asset Base</div>
                </div>
            </div>
        </div>

        {{-- Strategy Section --}}
        <div class="bg-slate-50 py-20 dark:bg-slate-800/30" id="strategy">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-16 text-center">
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Our UK Investment Strategy</h2>
                    <p class="mt-4 text-gray-600 dark:text-slate-400">We focus on high-yield, low-maintenance properties in
                        regeneration areas.</p>
                </div>
                <div class="grid grid-cols-1 gap-12 md:grid-cols-3">
                    <div class="relative">
                        <div class="absolute -left-4 -top-10 text-6xl font-black text-slate-200 dark:text-slate-800">01
                        </div>
                        <div class="relative z-10">
                            <h3 class="mb-3 text-xl font-bold text-red-600">Northern Powerhouse</h3>
                            <p class="text-gray-600 dark:text-slate-400">Focusing on Manchester, Liverpool, and Sheffield
                                for yields exceeding 7%.</p>
                        </div>
                    </div>
                    <div class="relative">
                        <div class="absolute -left-4 -top-10 text-6xl font-black text-slate-200 dark:text-slate-800">02
                        </div>
                        <div class="relative z-10">
                            <h3 class="mb-3 text-xl font-bold text-red-600">Off-Market Access</h3>
                            <p class="text-gray-600 dark:text-slate-400">Direct relationships with developers to provide
                                below-market-value (BMV) units.</p>
                        </div>
                    </div>
                    <div class="relative">
                        <div class="absolute -left-4 -top-10 text-6xl font-black text-slate-200 dark:text-slate-800">03
                        </div>
                        <div class="relative z-10">
                            <h3 class="mb-3 text-xl font-bold text-red-600">End-to-End Management</h3>
                            <p class="text-gray-600 dark:text-slate-400">Complete property management including tenant
                                sourcing and maintenance.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- UK Portfolios --}}
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8" id="offers">
            <h2 class="mb-12 text-3xl font-bold text-gray-900 dark:text-white">Featured UK Units</h2>
            <div class="grid grid-cols-1 gap-10 md:grid-cols-3">
                @foreach ($report['top_properties'] ?? [] as $prop)
                    @php $ilan = \App\Models\Ilan::find($prop['id']); @endphp
                    @if ($ilan)
                        <div
                            class="group flex flex-col overflow-hidden rounded-lg border border-slate-100 bg-white transition-all duration-300 hover:-translate-y-2 hover:shadow-xl dark:border-slate-700 dark:bg-slate-800">
                            <div class="relative h-48">
                                <img src="{{ $ilan->kapak_foto ?? 'https://source.unsplash.com/random/800x600?london,building' }}"
                                    class="h-full w-full object-cover transition-all dark:brightness-75">
                            </div>
                            <div class="flex-1 p-6">
                                <div class="mb-2 text-xs font-bold uppercase text-red-600">Exclusive Unit</div>
                                <h3 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">{{ $ilan->baslik }}</h3>
                                <div class="mb-4 text-2xl font-black text-slate-900 dark:text-white">
                                    {{ number_format($ilan->fiyat) }} {{ $ilan->para_birimi }}
                                </div>
                                <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-4 dark:border-slate-700">
                                    <div>
                                        <div class="text-[10px] font-bold uppercase text-gray-400">NET YIELD</div>
                                        <div class="font-bold text-green-600">{{ $prop['yield'] }}%</div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-bold uppercase text-gray-400">ROI (5YR)</div>
                                        <div class="font-bold text-blue-600">{{ $prop['roi'] }}%</div>
                                    </div>
                                </div>
                            </div>
                            <a href="{{ route('ilanlar.show', $ilan->id) }}"
                                class="bg-slate-900 py-4 text-center font-bold text-white transition-colors hover:bg-red-600 dark:bg-slate-700">Request
                                Financial Info</a>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
@endsection
