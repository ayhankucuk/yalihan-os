@extends('admin.layouts.admin')

@section('title', 'Talebi Düzenle')

@section('content')
    <div class="content-header mb-6">
        <div class="container-fluid">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-slate-200">
                        <i class="fas fa-edit mr-2 text-yellow-500"></i>
                        Talebi Düzenle
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">
                        Mevcut talebin detaylarını güncelleyin.
                    </p>
                </div>
                <a href="{{ route('admin.talepler.index') }}"
                    class="inline-flex items-center px-4 py-2 btn-outline font-semibold rounded-lg transition-colors touch-target-optimized">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Taleplere Geri Dön
                </a>
            </div>
        </div>
    </div>

    <div class="bg-gray-50 dark:bg-slate-900 rounded-lg shadow-lg p-6">
        <form action="{{ route('admin.talepler.update', $talep->id) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.talepler.partials._form', [
                'talep' => $talep,
                'kisiler' => $kisiler,
                'kategoriler' => $kategoriler,
                'emlakTipleri' => $emlakTipleri,
                'buttonText' => 'Değişiklikleri Kaydet',
            ])
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        // Gerekirse bu sayfaya özel script'ler buraya eklenebilir.
    </script>
@endpush
