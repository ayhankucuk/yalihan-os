@extends('admin.layouts.admin')

@section('title', 'Kullanıcı Detayı - ' . $user->name)
@section('page-title', 'Kullanıcı Detayı')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kullanıcı detayları</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.kullanicilar.index') }}"
                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Listeye Dön
                </a>
                <a href="{{ route('admin.kullanicilar.edit', $user) }}"
                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Düzenle
                </a>
            </div>
        </div>

        <!-- Status Alert -->
        @if(!$user->aktiflik_durumu)
        <div class="rounded-xl border-2 border-red-200 bg-gradient-to-br from-red-50 to-rose-50 p-4 dark:border-red-800 dark:from-red-900/20 dark:to-rose-900/20">
            <div class="flex items-center gap-3">
                <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <p class="font-medium text-red-800 dark:text-red-200">Bu kullanıcı pasif durumda</p>
            </div>
        </div>
        @endif

        <!-- User Details Card -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Kullanıcı Bilgileri</h2>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <!-- ID -->
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kullanıcı ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->id }}</dd>
                    </div>

                    <!-- Name -->
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">İsim</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->name }}</dd>
                    </div>

                    <!-- Email -->
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">E-posta</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            <a href="mailto:{{ $user->email }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                {{ $user->email }}
                            </a>
                        </dd>
                    </div>

                    <!-- Role -->
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Rol</dt>
                        <dd class="mt-1">
                            @if($user->role)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($user->role->name === 'super-admin')
                                        bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300
                                    @elseif($user->role->name === 'admin')
                                        bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300
                                    @elseif($user->role->name === 'danisman')
                                        bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                    @else
                                        bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300
                                    @endif">
                                    {{ $user->role->display_name ?? $user->role->name }}
                                </span>
                            @else
                                <span class="text-sm text-gray-400 dark:text-gray-500">Rol atanmamış</span>
                            @endif
                        </dd>
                    </div>

                    <!-- Status -->
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Durum</dt>
                        <dd class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($user->aktiflik_durumu)
                                    bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                @else
                                    bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                                @endif">
                                {{ $user->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
                            </span>
                        </dd>
                    </div>

                    <!-- Created At -->
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Oluşturulma Tarihi</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->created_at->format('d.m.Y H:i') }}</dd>
                    </div>

                    <!-- Updated At -->
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Son Güncelleme</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->updated_at->format('d.m.Y H:i') }}</dd>
                    </div>

                    <!-- Tenant -->
                    @if($user->tenant_id)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tenant ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->tenant_id }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        <!-- Danger Zone -->
        @can('delete', $user)
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-red-200 dark:border-red-800 shadow-sm">
            <div class="px-6 py-4 border-b border-red-200 dark:border-red-700">
                <h2 class="text-lg font-semibold text-red-800 dark:text-red-300">Tehlikeli Bölge</h2>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">Kullanıcıyı Sil</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Bu işlem geri alınamaz. Kullanıcı kalıcı olarak silinecektir.</p>
                    </div>
                    <form action="{{ route('admin.kullanicilar.destroy', $user) }}" method="POST" onsubmit="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Sil
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endcan
    </div>
@endsection
