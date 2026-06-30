@extends('layouts.frontend')

@section('title', 'ROI Calculator - Real Estate Investment Projection | Yalıhan Emlak')

@section('content')
    <div class="min-h-screen bg-gray-50 px-4 py-20 dark:bg-slate-950">
        <div class="mx-auto max-w-4xl">
            <div class="mb-12 text-center">
                <h1 class="mb-4 text-4xl font-extrabold text-gray-900 dark:text-white">Investor <span
                        class="text-blue-600">ROI Calculator</span></h1>
                <p class="mx-auto max-w-2xl text-gray-600 dark:text-slate-400">Calculate your potential returns and 5-year
                    growth projection with our professional calculator engine.</p>
            </div>

            <div class="overflow-hidden rounded-3xl border border-b border-gray-100 bg-white shadow-2xl dark:border-slate-800 dark:border-b-transparent dark:bg-slate-900"
                x-data="calculator()">
                <div class="grid grid-cols-1 md:grid-cols-2">
                    {{-- Inputs --}}
                    <div class="border-b border-gray-100 p-8 dark:border-slate-800 md:border-b-0 md:border-r md:p-12">
                        <div class="space-y-6">
                            <div>
                                <label
                                    class="mb-2 block text-sm font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400">Purchase
                                    Price</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-gray-400">$</span>
                                    <input type="number" x-model.number="price" @input="calculate()"
                                        class="w-full rounded-xl border-none bg-gray-50 py-4 pl-10 pr-4 text-xl font-bold outline-none transition-all focus:ring-4 focus:ring-blue-500/10 dark:bg-slate-800 dark:text-white">
                                </div>
                            </div>
                            <div>
                                <label
                                    class="mb-2 block text-sm font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400">Monthly
                                    Rent</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-gray-400">$</span>
                                    <input type="number" x-model.number="rent" @input="calculate()"
                                        class="w-full rounded-xl border-none bg-gray-50 py-4 pl-10 pr-4 text-xl font-bold outline-none transition-all focus:ring-4 focus:ring-blue-500/10 dark:bg-slate-800 dark:text-white">
                                </div>
                            </div>
                            <div>
                                <label
                                    class="mb-2 block text-sm font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400">Target
                                    Market</label>
                                <select x-model="market" @change="calculate()"
                                    class="w-full cursor-pointer rounded-xl border-none bg-gray-50 px-4 py-4 text-lg font-bold outline-none transition-all focus:ring-4 focus:ring-blue-500/10 dark:bg-slate-800 dark:text-white">
                                    <option value="TR">Turkey (Growth Focused)</option>
                                    <option value="GR">Greece (Residency Focused)</option>
                                    <option value="UK">UK (Yield Focused)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Results --}}
                    <div class="flex flex-col justify-center bg-blue-600 p-8 text-white md:p-12">
                        <div class="space-y-8">
                            <div>
                                <div class="mb-1 text-sm font-bold uppercase tracking-widest text-blue-100">Estimated Net
                                    Yield</div>
                                <div class="text-6xl font-black" x-text="yield + '%'">0%</div>
                            </div>

                            <div class="grid grid-cols-2 gap-6 border-t border-white/20 pt-8">
                                <div>
                                    <div class="mb-1 text-[10px] font-bold uppercase tracking-widest text-blue-100">5Y
                                        Projected Gain</div>
                                    <div class="text-2xl font-bold" x-text="'$' + gain.toLocaleString()">$0</div>
                                </div>
                                <div>
                                    <div class="mb-1 text-[10px] font-bold uppercase tracking-widest text-blue-100">Total
                                        Future Value</div>
                                    <div class="text-2xl font-bold" x-text="'$' + futureValue.toLocaleString()">$0</div>
                                </div>
                            </div>

                            <div class="mt-8 pt-6">
                                <a href="{{ route('contact') }}"
                                    class="block w-full rounded-xl bg-white py-4 text-center font-bold text-blue-600 shadow-xl transition-all hover:bg-gray-100 active:scale-95 dark:bg-slate-800 dark:text-blue-400 dark:hover:bg-slate-700">Get
                                    Detailed PDF Report</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="mt-8 text-center text-xs text-gray-400 dark:text-slate-500">
                * Calculations are estimates based on current market data and historical growth. Actual performance may
                vary.
            </p>
        </div>
    </div>

    <script>
        function calculator() {
            return {
                price: 500000,
                rent: 2500,
                market: 'TR',
                yield: 0,
                gain: 0,
                futureValue: 0,
                init() {
                    this.calculate();
                },
                calculate() {
                    // Yield calculation
                    this.yield = ((this.rent * 12) / this.price * 100).toFixed(2);

                    // Growth simulation (simplified for UI)
                    const growthRates = {
                        'TR': 0.12,
                        'GR': 0.05,
                        'UK': 0.04
                    };
                    const rate = growthRates[this.market] || 0.05;
                    const years = 5;

                    this.futureValue = Math.round(this.price * Math.pow((1 + rate), years));
                    this.gain = this.futureValue - this.price;
                }
            }
        }
    </script>
@endsection
