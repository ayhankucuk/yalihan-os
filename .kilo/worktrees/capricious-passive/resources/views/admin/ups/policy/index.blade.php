@extends('admin.layouts.admin')

@section('title', 'UPS Policy Manager')

@section('content')
<div class="w-full px-6 py-6 transition-all">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8 transition-all">
        <div class="transition-all">
            <h1 class="text-3xl font-bold transition-all text-gray-900 dark:text-white tracking-tight dark:text-slate-100">UPS Policy Manager</h1>
            <p class="mt-1 transition-all text-gray-500 dark:text-gray-400">Manage Category vs. Publication Type matrix overrides.</p>
        </div>
        <div class="flex items-center gap-3 transition-all">
            <span class="flex items-center gap-2 px-3 py-1 transition-all bg-amber-50 dark:bg-amber-900 text-amber-700 dark:text-amber-400 rounded-full text-sm font-medium border border-amber-100 dark:border-amber-800">
                <span class="w-2 h-2 bg-amber-500 dark:bg-amber-400 rounded-full animate-pulse transition-all"></span>
                <span class="text-gray-600 dark:text-gray-400 transition-all">Context7 Compliant</span>
            </span>
        </div>
    </div>

    <!-- Alert / Info -->
    <div class="mb-8 p-4 flex gap-4 items-start transition-all bg-indigo-50 dark:bg-indigo-900 border border-indigo-100 dark:border-indigo-800 rounded-2xl shadow-none dark:shadow-none">
        <div class="p-2 bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 rounded-lg transition-all">
            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <div class="transition-all">
            <h3 class="font-semibold transition-all text-indigo-900 dark:text-indigo-300">Policy SSOT Logic</h3>
            <p class="text-sm transition-all text-indigo-800 dark:text-indigo-400 leading-relaxed">
                Database overrides take precedence over the code-based slug matrix. Removing an override will revert that category to the default system logic.
            </p>
        </div>
    </div>

    <!-- Grid Layout -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 transition-all">
        @foreach($kategoriler as $kategori)
            @php
                $override = $overrides[$kategori->id] ?? null;
                $isOverridden = $override !== null;
                $currentAllowed = $isOverridden ? $override->allowed_publication_types : [];
            @endphp
            <div id="pbox-{{ $kategori->id }}"
                 x-data="{
                    allowed: @json($currentAllowed),
                    toggle(slug) {
                        if (this.allowed.includes(slug)) {
                            this.allowed = this.allowed.filter(s => s !== slug);
                        } else {
                            this.allowed.push(slug);
                        }
                    },
                    async save() {
                        const response = await fetch('{{ route('admin.ups.policy.store') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ kategori_id: {{ $kategori->id }}, allowed_publication_types: this.allowed, status: true })
                        });
                        const result = await response.json();
                        if (result.success) location.reload();
                    }
                 }"
                 class="group relative flex flex-col transition-all duration-300 bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 rounded-3xl overflow-hidden hover:shadow-xl dark:hover:shadow-none">

                <!-- Card Header -->
                <div class="p-6 border-b border-gray-100 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 transition-all">
                    <div class="flex items-start justify-between transition-all">
                        <div class="transition-all">
                            <div class="flex items-center gap-2 mb-1 transition-all">
                                <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-[10px] font-bold uppercase tracking-wider rounded transition-all">ID: {{ $kategori->id }}</span>
                                @if($isOverridden)
                                    <span class="px-2 py-0.5 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-400 text-[10px] font-bold uppercase tracking-wider rounded transition-all">Override Active</span>
                                @endif
                            </div>
                            <h2 class="text-xl font-bold text-gray-800 dark:text-white transition-all dark:text-slate-200">{{ $kategori->name }}</h2>
                            <p class="text-xs text-gray-400 dark:text-gray-500 font-medium transition-all">Slug: /{{ $kategori->slug }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-2xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 flex items-center justify-center text-gray-400 dark:text-gray-500 group-hover:text-amber-500 dark:group-hover:text-amber-400 transition-all dark:border-slate-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-6 flex-grow transition-all">
                    <label class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-4 transition-all">Allowed Publication Types</label>
                    <div class="flex flex-wrap gap-2 transition-all">
                        @php
                            $allPossible = ['satilik', 'kiralik', 'gunluk', 'haftalik', 'aylik', 'sezonluk', 'kat-karsiligi', 'devren'];
                        @endphp

                        @foreach($allPossible as $slug)
                            <button
                                @click="toggle('{{ $slug }}')"
                                :class="allowed.includes('{{ $slug }}')
                                    ? 'bg-amber-600 dark:bg-amber-600 text-white dark:text-white border-amber-600 dark:border-amber-600 shadow-sm dark:shadow-none'
                                    : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-600'"
                                class="inline-flex items-center px-4 py-2 border rounded-xl text-sm font-semibold transition-all duration-300 hover:border-amber-500 dark:hover:border-amber-400"
                            >
                                {{ ucfirst(str_replace('-', ' ', $slug)) }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Card Footer -->
                <div class="p-4 px-6 flex items-center justify-between transition-all bg-gray-50 dark:bg-slate-900 border-t border-gray-100 dark:border-slate-800">
                    <div class="flex items-center gap-2 transition-all">
                         <button @click="save" class="px-4 py-2 transition-all transform active:scale-95 bg-gray-900 dark:bg-amber-600 text-white dark:text-gray-900 text-xs font-bold rounded-xl hover:bg-black dark:hover:bg-amber-500">
                            Apply Overrides
                        </button>
                    </div>
                    @if($isOverridden)
                        <button onclick="resetPolicy({{ $kategori->id }})" class="text-xs font-bold text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-all uppercase tracking-widest leading-none">
                            Reset to Default
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
<script>
    async function resetPolicy(categoryId) {
        if (!confirm('Revert this category to default system matrix?')) return;

        try {
            const response = await fetch(`{{ url('/admin/ups/policy') }}/${categoryId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const result = await response.json();
            if (result.success) {
                location.reload();
            }
        } catch (error) {
            console.error('Reset failed:', error);
        }
    }
</script>
@endsection
