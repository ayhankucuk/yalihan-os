{{--
    DEPRECATED LAYOUT FILE
    Bu dosya Bootstrap kullanıyor ve artık kullanılmamalı.
    Yeni sayfalar için: @extends('admin.layouts.admin') kullanın.

    Bu dosya sadece geriye dönük uyumluluk için tutuluyor.
    Yeni geliştirmelerde KULLANMAYIN!

    Migration: Tüm sayfaları admin.layouts.admin'ya geçirin.
--}}
@extends('admin.layouts.admin')

@section('title')
    @yield('title', 'Yalihan Emlak Admin') | Context7 Uyumlu
@endsection

@section('content')
    <div class="p-4">
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700 dark:text-yellow-300">
                        <strong>DEPRECATED:</strong> Bu sayfa eski layout.blade.php kullanıyor.
                        Lütfen <code>@extends('admin.layouts.admin')</code> kullanarak güncelleyin.
                    </p>
                </div>
            </div>
        </div>

        @yield('content')
    </div>
@endsection
