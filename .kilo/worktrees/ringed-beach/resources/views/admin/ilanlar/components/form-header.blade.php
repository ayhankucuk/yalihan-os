{{-- Page Header --}}
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white dark:text-slate-100">
            {{ isset($ilan) ? 'İlan Düzenle' : 'Yeni İlan Oluştur' }}
        </h1>
        <p class="mt-1.5 text-sm text-gray-600 dark:text-gray-400">
            {{ isset($ilan) ? 'İlan bilgilerini güncelleyin' : 'İlan bilgilerini doldurun ve yayınlayın' }}
        </p>
    </div>
    <a href="{{ route('admin.ilanlar.index') }}"
        class="inline-flex items-center px-4 py-2.5 bg-gray-600 text-white font-medium rounded-lg shadow-sm hover:bg-gray-700 hover:shadow-md active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 dark:shadow-none">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Geri Dön
    </a>
</div>

{{-- Draft Restore Banner --}}
<div id="draft-restore-banner"></div>

