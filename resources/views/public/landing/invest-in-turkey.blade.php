@extends('layouts.frontend')

@section('title', 'Invest in Turkey - Real Estate ROI & Yield Analysis | Yalıhan Emlak')

@section('content')
    <div class="min-h-screen bg-white dark:bg-slate-900">
        {{-- Hero Section --}}
        <div class="relative overflow-hidden bg-blue-900 py-20">
            <div class="absolute inset-0 opacity-20 dark:opacity-40">
                <img src="https://images.unsplash.com/photo-1524231757912-21f4fe3a7200?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80"
                    alt="Turkey Investment" class="h-full w-full object-cover grayscale dark:grayscale-0">
            </div>
            <div class="relative z-10 mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <h1 class="mb-6 text-4xl font-extrabold text-white md:text-6xl">
                    Invest in <span class="text-yellow-400">Turkey</span>
                </h1>
                <p class="mx-auto mb-10 max-w-3xl text-xl text-blue-100">
                    Unlock high-yield real estate opportunities in the Mediterranean's most dynamic market.
                </p>
                <div class="flex justify-center gap-4">
                    <a href="#calculator"
                        class="rounded-lg bg-blue-600 px-8 py-3 font-bold text-white shadow-lg transition-all hover:bg-blue-700">ROI
                        Calculator</a>
                    <a href="#opportunities"
                        class="rounded-lg bg-white px-8 py-3 font-bold text-blue-900 shadow-lg transition-all hover:bg-gray-100 dark:bg-slate-800 dark:text-blue-100 dark:hover:bg-slate-700">View
                        Opportunities</a>
                </div>
            </div>
        </div>

        {{-- Market Highlights --}}
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 text-center md:grid-cols-3">
                <div
                    class="rounded-2xl border border-gray-100 bg-gray-50 p-8 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="mb-2 text-4xl font-bold text-blue-600">{{ $report['avg_net_yield'] ?? '6.5' }}%</div>
                    <div class="text-sm font-medium uppercase tracking-wider text-gray-600 dark:text-slate-300">Average Net
                        Yield</div>
                </div>
                <div
                    class="rounded-2xl border border-gray-100 bg-gray-50 p-8 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="mb-2 text-4xl font-bold text-green-600">{{ $report['avg_growth_rate'] ?? '12' }}%</div>
                    <div class="text-sm font-medium uppercase tracking-wider text-gray-600 dark:text-slate-300">Yearly
                        Appreciation</div>
                </div>
                <div
                    class="rounded-2xl border border-gray-100 bg-gray-50 p-8 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="mb-2 text-4xl font-bold text-yellow-500">0%</div>
                    <div class="text-sm font-medium uppercase tracking-wider text-gray-600 dark:text-slate-300">Inheritance
                        Tax*</div>
                </div>
            </div>
        </div>

        {{-- Detailed Analysis --}}
        <div class="bg-gray-50 py-20 dark:bg-slate-800/50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 items-center gap-16 lg:grid-cols-2">
                    <div>
                        <h2 class="mb-6 text-3xl font-bold text-gray-900 dark:text-white">Why Invest in Turkish Real Estate?
                        </h2>
                        <div class="space-y-6">
                            <div class="flex items-start gap-4">
                                <div
                                    class="rounded-lg bg-blue-100 p-2 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white">Strategic Location</h3>
                                    <p class="text-gray-600 dark:text-slate-400">A bridge between Europe and Asia with a
                                        massive tourism appeal.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div
                                    class="rounded-lg bg-green-100 p-2 text-green-600 dark:bg-green-900/40 dark:text-green-400">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white">Currency Advantage</h3>
                                    <p class="text-gray-600 dark:text-slate-400">Strong purchasing power for USD/EUR/GBP
                                        holders.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-4">
                                <div
                                    class="rounded-lg bg-purple-100 p-2 text-purple-600 dark:bg-purple-900/40 dark:text-purple-400">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-10V4m0 10V4m-4 18h8">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white">Citizenship by Investment</h3>
                                    <p class="text-gray-600 dark:text-slate-400">Secure your Turkish passport with
                                        qualifying real estate purchases.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-3xl border border-gray-100 bg-white p-8 shadow-xl dark:border-slate-800 dark:bg-slate-900"
                        id="calculator">
                        <h3 class="mb-6 text-2xl font-bold text-gray-900 dark:text-white">ROI Estimator</h3>
                        <div class="space-y-4" x-data="{
                            price: 250000,
                            rent: 1500,
                            yield: 0,
                            calc() { this.yield = ((this.rent * 12) / this.price * 100).toFixed(2); }
                        }" x-init="calc()">
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-400">Purchase
                                    Price ($)</label>
                                <input type="number" x-model="price" @input="calc()"
                                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-lg outline-none transition-all focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-400">Monthly
                                    Rental Income ($)</label>
                                <input type="number" x-model="rent" @input="calc()"
                                    class="w-full rounded-xl border border-gray-200 px-4 py-3 text-lg outline-none transition-all focus:ring-2 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800">
                            </div>
                            <div class="mt-6 border-t pt-6 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-slate-400">Estimated Net Yield:</span>
                                    <span class="text-3xl font-extrabold text-blue-600" x-text="yield + '%'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Opportunities --}}
        <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8" id="opportunities">
            <h2 class="mb-12 text-center text-3xl font-bold text-gray-900 dark:text-white">Top Investment Opportunities</h2>
            <div class="grid grid-cols-1 gap-10 md:grid-cols-3">
                @foreach ($report['top_properties'] ?? [] as $prop)
                    @php
                        $ilan = \App\Models\Ilan::find($prop['id']);
                    @endphp
                    @if ($ilan)
                        <div
                            class="group overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-lg transition-all duration-300 hover:shadow-2xl dark:border-slate-700 dark:bg-slate-800">
                            <div class="relative h-48">
                                <img src="{{ $ilan->kapak_foto ?? 'https://source.unsplash.com/random/800x600?luxury,mansion' }}"
                                    class="h-full w-full object-cover transition-all dark:brightness-75">
                                <div
                                    class="absolute right-4 top-4 rounded-full bg-blue-600 px-3 py-1 text-xs font-bold text-white shadow-lg">
                                    {{ $prop['yield'] }}% Net Yield
                                </div>
                            </div>
                            <div class="p-6">
                                <h3 class="mb-2 line-clamp-1 font-bold text-gray-900 dark:text-white">{{ $ilan->baslik }}
                                </h3>
                                <div class="mb-4 text-xl font-extrabold text-blue-600">
                                    {{ number_format($ilan->fiyat) }} {{ $ilan->para_birimi }}
                                </div>
                                <a href="{{ route('ilanlar.show', $ilan->id) }}"
                                    class="block rounded-lg border-2 border-blue-600 py-2 text-center font-bold text-blue-600 transition-all hover:bg-blue-600 hover:text-white">View
                                    Details</a>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
@endsection
