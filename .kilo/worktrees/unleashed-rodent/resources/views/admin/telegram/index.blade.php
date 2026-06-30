@extends('admin.layouts.admin')

@section('title', 'Telegram Bot - Yalıhan Emlak Pro')

@section('content')
<div class="content-header mb-8">
    <h1 class="text-3xl font-bold text-gray-800 dark:text-slate-200">Telegram Bot</h1>
    <p class="text-lg text-gray-600 mt-2">Telegram bot yönetimi ve ayarları</p>
</div>

<div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
    <div class="text-center py-12">
        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2 dark:text-slate-100 dark:text-white">Telegram Bot Yönetimi</h3>
        <p class="text-gray-500 mb-6">Telegram bot özellikleri geliştirme aşamasındadır.</p>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 max-w-md mx-auto">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-800">
                        Bu özellik yakında status olacaktır.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
