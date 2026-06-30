@extends('admin.layouts.admin')

@section('title', 'Kullanıcı Yönetimi')
@section('page-title', 'Kullanıcılar')

@section('content')

    {{-- Flash Messages --}}
    @if (session('success'))
        <div
            class="mb-6 flex items-start gap-3 rounded-xl border-2 border-green-200 bg-gradient-to-br from-green-50 to-emerald-50 p-4 dark:border-green-800 dark:from-green-900/20 dark:to-emerald-900/20">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-600 dark:text-green-400" fill="currentColor"
                viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd" />
            </svg>
            <p class="font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div
            class="mb-6 flex items-start gap-3 rounded-xl border-2 border-red-200 bg-gradient-to-br from-red-50 to-rose-50 p-4 dark:border-red-800 dark:from-red-900/20 dark:to-rose-900/20">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd" />
            </svg>
            <p class="font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
        </div>
    @endif

    @php
        $duplicateEmails = \App\Models\User::select('email')
            ->whereNotNull('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('email')
            ->toArray();
        $passiveCount = \App\Models\User::where('aktiflik_durumu', false)->count();
        $passiveEmails = \App\Models\User::where('aktiflik_durumu', false)
            ->whereNotNull('email')
            ->limit(5)
            ->pluck('email')
            ->toArray();
    @endphp

    <div class="space-y-6">
        {{-- AI Analiz Kartı --}}
        <div
            class="rounded-2xl border-2 border-indigo-200 bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50 p-6 shadow-xl dark:border-indigo-800 dark:from-indigo-900/20 dark:via-purple-900/20 dark:to-pink-900/20">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 shadow-lg">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-indigo-900 dark:text-indigo-100">AI Kullanıcı Analizi</div>
                        <div class="text-sm text-indigo-700 dark:text-indigo-300">Mükerrer e‑posta ve pasif hesap önerileri
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if (count($duplicateEmails) > 0)
                        <span
                            class="rounded-full bg-red-100 px-3 py-1.5 text-xs font-semibold text-red-800 dark:bg-red-900/30 dark:text-red-400">⚠️
                            Mükerrer: {{ count($duplicateEmails) }}</span>
                    @else
                        <span
                            class="rounded-full bg-blue-100 px-3 py-1.5 text-xs font-semibold text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">✅
                            Temiz</span>
                    @endif
                    <span
                        class="rounded-full bg-amber-100 px-3 py-1.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">💤
                        Pasif: {{ $passiveCount }}</span>
                </div>
            </div>
            @if (count($duplicateEmails) > 0 || $passiveCount > 0)
                <div class="mt-4 space-y-2 text-xs">
                    @if (count($duplicateEmails) > 0)
                        <div
                            class="flex items-start gap-2 rounded-lg bg-red-50 p-3 text-red-700 dark:bg-red-900/20 dark:text-red-300">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            <div>
                                <span class="font-semibold">Mükerrer e-postalar:</span>
                                {{ implode(', ', array_slice($duplicateEmails, 0, 3)) }}@if (count($duplicateEmails) > 3)
                                    ve {{ count($duplicateEmails) - 3 }} tane daha...
                                @endif
                            </div>
                        </div>
                    @endif
                    @if ($passiveCount > 0)
                        <div
                            class="flex items-start gap-2 rounded-lg bg-amber-50 p-3 text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                            <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd" />
                            </svg>
                            <div>
                                <span class="font-semibold">Pasif hesaplar:</span>
                                {{ implode(', ', $passiveEmails) }}@if ($passiveCount > count($passiveEmails))
                                    ve {{ $passiveCount - count($passiveEmails) }} tane daha...
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Main Card --}}
        <div
            class="rounded-2xl border border-gray-200 bg-gradient-to-br from-white to-gray-50 shadow-xl dark:border-slate-700 dark:border-slate-800 dark:from-gray-800 dark:to-gray-900">
            {{-- Minimal Header --}}
            <div
                class="flex items-center justify-between border-b border-gray-200 p-5 dark:border-slate-700 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 to-purple-600 shadow-lg">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">Kullanıcılar</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $users->total() }} kullanıcı</p>
                    </div>
                </div>
                <button type="button" onclick="exportUsersCsv()"
                    class="flex items-center gap-2 rounded-lg border border-gray-300 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-900 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:bg-gray-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    CSV
                </button>
            </div>

            {{-- Minimal Filter Bar --}}
            <div
                class="border-b border-gray-200 bg-gray-50 p-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-3">
                    {{-- Quick Search --}}
                    <form method="GET" class="relative max-w-md flex-1">
                        <svg class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" name="search"
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 py-2 pl-10 pr-4 text-gray-900 placeholder-gray-500 transition-colors focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-400"
                            placeholder="İsim veya email ara..." value="{{ request('search') }}"
                            onchange="this.form.submit()">
                    </form>

                    {{-- Status Pills --}}
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.kullanicilar.index') }}"
                            class="{{ !request('kullanici_durumu') ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600' }} rounded-lg px-3 py-1.5 text-sm font-medium transition-all">
                            Tümü
                        </a>
                        <a href="{{ route('admin.kullanicilar.index', ['kullanici_durumu' => '1']) }}"
                            class="{{ request('kullanici_durumu') == '1' ? 'bg-green-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600' }} rounded-lg px-3 py-1.5 text-sm font-medium transition-all">
                            Aktif
                        </a>
                        <a href="{{ route('admin.kullanicilar.index', ['kullanici_durumu' => '0']) }}"
                            class="{{ request('kullanici_durumu') == '0' ? 'bg-red-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600' }} rounded-lg px-3 py-1.5 text-sm font-medium transition-all">
                            Pasif
                        </a>
                    </div>

                    {{-- Role Filter --}}
                    <form method="GET" class="flex items-center gap-2">
                        <input type="hidden" name="kullanici_durumu" value="{{ request('kullanici_durumu') }}">
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <select style="color-scheme: light dark;" name="role" onchange="this.form.submit()"
                            class="cursor-pointer rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 transition-colors focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                            <option value="">Tüm Roller</option>
                            <option value="superadmin" {{ request('role') == 'superadmin' ? 'selected' : '' }}>Super Admin
                            </option>
                            <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="danisman" {{ request('role') == 'danisman' ? 'selected' : '' }}>Danışman
                            </option>
                            <option value="editor" {{ request('role') == 'editor' ? 'selected' : '' }}>Editor</option>
                            <option value="musteri" {{ request('role') == 'musteri' ? 'selected' : '' }}>Müşteri</option>
                        </select>
                    </form>

                    {{-- New User Button (Moved Here) --}}
                    <a href="{{ route('admin.kullanicilar.create') }}"
                        class="flex transform items-center gap-2 rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 px-4 py-2 text-sm font-semibold text-white shadow-lg transition-all duration-200 hover:scale-105 hover:from-blue-700 hover:to-purple-700 hover:shadow-xl">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Yeni
                    </a>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gradient-to-r from-gray-100 to-gray-200 dark:from-gray-900 dark:to-gray-800">
                        <tr>
                            <th
                                class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-slate-100 dark:text-white">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => request('sort') === 'id_asc' ? 'id_desc' : 'id_asc']) }}"
                                    class="flex items-center gap-1 transition-colors hover:text-blue-600 dark:hover:text-blue-400">
                                    ID
                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M5 12a1 1 0 102 0V6.414l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L5 6.414V12zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z" />
                                    </svg>
                                </a>
                            </th>
                            <th
                                class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-slate-100 dark:text-white">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => request('sort') === 'name_asc' ? 'name_desc' : 'name_asc']) }}"
                                    class="flex items-center gap-1 transition-colors hover:text-blue-600 dark:hover:text-blue-400">
                                    İsim
                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M5 12a1 1 0 102 0V6.414l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L5 6.414V12zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z" />
                                    </svg>
                                </a>
                            </th>
                            <th
                                class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-slate-100 dark:text-white">
                                E‑posta</th>
                            <th
                                class="px-6 py-4 text-center text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-slate-100 dark:text-white">
                                Rol</th>
                            <th
                                class="px-6 py-4 text-center text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-slate-100 dark:text-white">
                                Durum</th>
                            <th
                                class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-slate-100 dark:text-white">
                                <a href="{{ request()->fullUrlWithQuery(['sort' => request('sort') === 'date_asc' ? 'date_desc' : 'date_asc']) }}"
                                    class="flex items-center gap-1 transition-colors hover:text-blue-600 dark:hover:text-blue-400">
                                    Kayıt Tarihi
                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M5 12a1 1 0 102 0V6.414l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L5 6.414V12zM15 8a1 1 0 10-2 0v5.586l-1.293-1.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L15 13.586V8z" />
                                    </svg>
                                </a>
                            </th>
                            <th
                                class="px-6 py-4 text-center text-xs font-bold uppercase tracking-wider text-gray-900 dark:text-slate-100 dark:text-white">
                                İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-slate-900">
                        @forelse($users as $user)
                            <tr class="transition-colors duration-150 hover:bg-blue-50/50 dark:hover:bg-blue-900/10">
                                {{-- ID --}}
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-sm font-bold text-white shadow-lg">
                                        {{ $user->id }}
                                    </span>
                                </td>

                                {{-- Name + Avatar --}}
                                <td class="px-6 py-4">
                                    <a href="{{ route('admin.kullanicilar.edit', $user) }}"
                                        class="group flex items-center gap-3">
                                        {{-- Avatar --}}
                                        <div
                                            class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-purple-500 to-pink-600 text-lg font-bold text-white shadow-lg ring-2 ring-white dark:ring-gray-700">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        {{-- Name --}}
                                        <span
                                            class="font-semibold text-gray-900 transition-colors group-hover:text-blue-600 dark:text-slate-100 dark:text-white dark:group-hover:text-blue-400">
                                            {{ $user->name }}
                                        </span>
                                    </a>
                                </td>

                                {{-- Email --}}
                                <td class="px-6 py-4">
                                    <a href="mailto:{{ $user->email }}"
                                        class="text-sm text-gray-600 transition-colors hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400">
                                        {{ $user->email }}
                                    </a>
                                </td>

                                {{-- Role --}}
                                <td class="px-6 py-4 text-center">
                                    @php
                                        $roleMapping = [
                                            'superadmin' => 'Super Admin',
                                            'admin' => 'Admin',
                                            'danisman' => 'Danışman',
                                            'editor' => 'Editor',
                                            'musteri' => 'Müşteri',
                                        ];
                                        // Use role_id instead of roles relationship (Spatie not available)
                                        $primaryRole = $user->role_id ? 'Role #' . $user->role_id : 'Rol Yok';

                                        $roleColors = [
                                            'Super Admin' =>
                                                'bg-gradient-to-br from-purple-100 to-fuchsia-100 dark:from-purple-900/30 dark:to-fuchsia-900/30 text-purple-800 dark:text-purple-400 border border-purple-300 dark:border-purple-700',
                                            'Admin' =>
                                                'bg-gradient-to-br from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 text-red-800 dark:text-red-400 border border-red-300 dark:border-red-700',
                                            'Danışman' =>
                                                'bg-gradient-to-br from-blue-100 to-cyan-100 dark:from-blue-900/30 dark:to-cyan-900/30 text-blue-800 dark:text-blue-400 border border-blue-300 dark:border-blue-700',
                                            'Editor' =>
                                                'bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-800 dark:text-green-400 border border-green-300 dark:border-green-700',
                                            'Müşteri' =>
                                                'bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30 text-amber-800 dark:text-amber-400 border border-amber-300 dark:border-amber-700',
                                            'Kullanıcı' =>
                                                'bg-gradient-to-br from-gray-100 to-slate-100 dark:from-gray-900/30 dark:to-slate-900/30 text-gray-800 dark:text-gray-400 border border-gray-300 dark:border-gray-700',
                                            'Rol Yok' =>
                                                'bg-gradient-to-br from-gray-100 to-slate-100 dark:from-gray-900/30 dark:to-slate-900/30 text-gray-600 dark:text-gray-500 border border-gray-300 dark:border-gray-700 border-dashed', // ✅ SAB: Rol Yok için özel stil
                                        ];
                                        $roleColor = $roleColors[$primaryRole] ?? $roleColors['Kullanıcı'];
                                    @endphp
                                    <div class="flex items-center justify-center gap-2">
                                        <span
                                            class="{{ $roleColor }} inline-flex items-center rounded-full px-3 py-1.5 text-xs font-bold">
                                            {{ $primaryRole }}
                                        </span>
                                        @if (!$user->role_id)
                                            <a href="{{ route('admin.kullanicilar.edit', $user) }}"
                                                class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-blue-600 transition-all duration-200 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50"
                                                title="Rol Ata">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 4v16m8-8H4" />
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="{{ $user->aktiflik_durumu
                                            ? 'bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/30 dark:to-emerald-900/30 text-green-800 dark:text-green-400 border border-green-300 dark:border-green-700'
                                            : 'bg-gradient-to-br from-red-100 to-rose-100 dark:from-red-900/30 dark:to-rose-900/30 text-red-800 dark:text-red-400 border border-red-300 dark:border-red-700' }} inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold">
                                        <span
                                            class="{{ $user->aktiflik_durumu ? 'bg-green-500 animate-pulse' : 'bg-red-500' }} h-2 w-2 rounded-full"></span>
                                        {{ $user->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
                                    </span>
                                </td>

                                {{-- Created At --}}
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                        {{ $user->created_at->format('d.m.Y') }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $user->created_at->format('H:i') }}
                                    </div>
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.kullanicilar.edit', $user) }}"
                                            class="group inline-flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-600 transition-all duration-200 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50"
                                            title="Düzenle">
                                            <svg class="h-4 w-4 transition-transform group-hover:scale-110" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>

                                        <form method="POST" action="{{ route('admin.kullanicilar.destroy', $user) }}"
                                            class="inline-block"
                                            onsubmit="return confirm('⚠️ Bu kullanıcıyı silmek istediğinizden emin misiniz?\n\nKullanıcı: {{ $user->name }}\nE-posta: {{ $user->email }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="group inline-flex h-9 w-9 items-center justify-center rounded-lg bg-red-100 text-red-600 transition-all duration-200 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50"
                                                title="Sil">
                                                <svg class="h-4 w-4 transition-transform group-hover:scale-110"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="mb-4 h-16 w-16 text-gray-400 dark:text-gray-600" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <h3
                                            class="mb-2 text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                                            Henüz
                                            kullanıcı bulunmuyor</h3>
                                        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">İlk kullanıcıyı eklemek
                                            için "Yeni Kullanıcı" butonuna tıklayın</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($users->hasPages())
                <div
                    class="border-t border-gray-200 bg-gray-50/50 px-6 py-4 dark:border-slate-700 dark:border-slate-800 dark:bg-gray-800/50">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Quick Filter Aktiflik (Vanilla JS)
        function quickFilterStatus(value) {
            const url = new URL(window.location.href);
            if (value === '') url.searchParams.delete('aktiflik');
            else url.searchParams.set('aktiflik', value);
            window.location.href = url.toString();
        }

        // Export to CSV (Vanilla JS)
        function exportUsersCsv() {
            const rows = Array.from(document.querySelectorAll('table tbody tr'));
            let csv = 'ID,İsim,E-posta,Rol,Aktiflik,Kayıt Tarihi\n';

            rows.forEach(r => {
                const cells = r.querySelectorAll('td');
                if (cells.length > 1) {
                    const id = cells[0].innerText.trim();
                    const name = cells[1].innerText.trim();
                    const email = cells[2].innerText.trim();
                    const role = cells[3].innerText.trim();
                    const aktiflik = cells[4].innerText.trim().replace(/\n/g, ' ').replace(/\s+/g, ' ');
                    const date = cells[5].innerText.trim().replace(/\n/g, ' ');
                    csv += `"${id}","${name}","${email}","${role}","${aktiflik}","${date}"\n`;
                }
            });

            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'kullanicilar_' + (new Date().toISOString().split('T')[0]) + '.csv';
            document.body.appendChild(a);
            a.click();
            a.remove();

            window.toast?.success('✅ Kullanıcılar CSV olarak indirildi');
        }
    </script>
@endpush
