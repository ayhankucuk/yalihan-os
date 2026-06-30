@extends('admin.layouts.admin')

@section('title', 'Proje Raporu')

@section('content')
    <div class="container mx-auto px-4 py-6">
        {{-- Başlık --}}
        <div class="mb-6">
            <nav class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-2">
                <a href="{{ route('admin.takim.projeler.index') }}"
                    class="hover:text-gray-700 dark:hover:text-gray-200">Projeler</a>
                <svg class="w-4 h-4 mx-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                        clip-rule="evenodd" />
                </svg>
                <span>{{ $proje->proje_adi ?? 'Proje' }}</span>
                <svg class="w-4 h-4 mx-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                        clip-rule="evenodd" />
                </svg>
                <span>Rapor</span>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Proje Raporu: {{ $proje->proje_adi ?? '—' }}</h1>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 dark:shadow-none">
            <p class="text-gray-500 dark:text-gray-400">Proje raporu henüz hazırlanıyor...</p>
        </div>
    </div>
@endsection
