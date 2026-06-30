@extends('layouts.owner')

@section('title', 'Ana Sayfa')

@section('content')

{{-- Karşılama --}}
<div class="mb-6">
    <h2 class="text-xl font-bold text-gray-800 dark:text-white">
        Hoş geldiniz, {{ auth()->user()?->name }} 👋
    </h2>
    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">
        Mülklerinizin durumunu buradan takip edebilirsiniz.
    </p>
</div>

{{-- Özet kartlar --}}
<div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">

    {{-- Toplam İlan --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-slate-500">Toplam İlan</p>
        <p class="mt-2 text-3xl font-bold text-gray-800 dark:text-white">{{ $ilanSayisi }}</p>
        <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">Sisteme kayıtlı mülk sayısı</p>
    </div>

    {{-- Aktif İlan --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-slate-500">Aktif İlan</p>
        <p class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">{{ $aktifIlanSayisi }}</p>
        <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">Şu an yayında olan</p>
    </div>

    {{-- Rapor kısayolu --}}
    <div class="rounded-xl border border-blue-100 bg-blue-50 p-5 shadow-sm dark:border-blue-900/30 dark:bg-blue-900/10">
        <p class="text-xs font-semibold uppercase tracking-wider text-blue-400">Raporlar</p>
        <p class="mt-2 text-sm font-medium text-blue-700 dark:text-blue-300">Gelir & Performans</p>
        <a href="{{ route('owner.reports.index') }}"
           class="mt-3 inline-block rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700 transition-colors">
            Raporları Görüntüle →
        </a>
    </div>

</div>

{{-- Yakında gelecek modüller --}}
<div class="rounded-xl border border-dashed border-gray-300 bg-white p-6 dark:border-slate-600 dark:bg-slate-800">
    <h3 class="mb-3 text-sm font-semibold text-gray-600 dark:text-slate-300">Yakında Aktif Olacak</h3>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach([
            ['icon' => '🏠', 'label' => 'İlanlarım'],
            ['icon' => '📩', 'label' => 'Teklifler'],
            ['icon' => '💬', 'label' => 'Danışman'],
            ['icon' => '📁', 'label' => 'Belgelerim'],
        ] as $modul)
            <div class="flex flex-col items-center rounded-lg bg-gray-50 p-3 text-center dark:bg-slate-700/50">
                <span class="text-2xl">{{ $modul['icon'] }}</span>
                <span class="mt-1 text-xs font-medium text-gray-500 dark:text-slate-400">{{ $modul['label'] }}</span>
            </div>
        @endforeach
    </div>
</div>

@endsection
