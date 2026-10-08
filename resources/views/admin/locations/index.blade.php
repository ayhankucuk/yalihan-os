@extends('admin.layouts.admin')

@section('title', 'Locations - Yalıhan Emlak Pro')

@section('content')
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-slate-200">
                    Locations
                </h1>
                <p class="text-lg text-gray-600 mt-2">Location management</p>
            </div>
            <a href="{{ route('admin.locations.create') }}"
               class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Location
            </a>
        </div>
    </div>

    <div class="px-6">
        {{-- Empty State --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-12 text-center">
            <div class="w-16 h-16 mx-auto mb-4 text-gray-400">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-slate-200 mb-2">
                No locations found
            </h3>
            <p class="text-gray-500 dark:text-slate-400 mb-6">
                Get started by adding your first location.
            </p>
            <a href="{{ route('admin.locations.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Location
            </a>
        </div>
    </div>
@endsection
