@extends('admin.layouts.admin')

@section('content')
<div class="p-6 space-y-4 max-w-3xl">
    <h1 class="text-xl font-semibold">
        {{ $mode === 'create' ? 'Whitelist Ekle' : 'Whitelist Düzenle' }}
    </h1>

    @if ($errors->any())
        <div class="px-4 py-2 bg-red-100 text-red-800 rounded border border-red-200 dark:bg-red-900 dark:text-red-100 dark:border-red-800">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $mode === 'create' ? route('admin.ups-feature-whitelist.store') : route('admin.ups-feature-whitelist.update', $entry) }}" class="space-y-4">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <div class="space-y-1">
            <label class="block text-sm font-medium">Kategori</label>
            <select name="kategori_id" class="w-full rounded border-gray-300 dark:bg-slate-900 dark:border-slate-800 dark:text-white transition-all duration-200 focus:ring-2 focus:ring-blue-500" required>
                <option value="">Seçiniz</option>
                @foreach ($kategoriler as $kategori)
                    <option value="{{ $kategori->id }}" @selected(old('kategori_id', $entry->kategori_id) == $kategori->id)>{{ $kategori->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="space-y-1">
            <label class="block text-sm font-medium">Feature Category Slug</label>
            <input type="text" name="feature_category_slug" value="{{ old('feature_category_slug', $entry->feature_category_slug) }}" class="w-full rounded border-gray-300 dark:bg-slate-900 dark:border-slate-800 dark:text-white transition-all duration-200 focus:ring-2 focus:ring-blue-500" required placeholder="ornegin: ic-ozellikler" />
            <p class="text-xs text-gray-500">Küçük harf, rakam, tire veya alt tire kullanın.</p>
        </div>

        <div class="space-y-1">
            <label class="inline-flex items-center space-x-2">
                <input type="checkbox" name="aktiflik_durumu" value="1" class="rounded transition-all duration-200 focus:ring-2 focus:ring-blue-500" @checked(old('aktiflik_durumu', $entry->aktiflik_durumu ?? true))>
                <span>Aktiflik durumu</span>
            </label>
        </div>

        <div class="flex items-center space-x-3">
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded transition-all duration-200 dark:bg-blue-700 dark:hover:bg-blue-800">Kaydet</button>
            <a href="{{ route('admin.ups-feature-whitelist.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded transition-all duration-200 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-white dark:text-slate-200">İptal</a>
        </div>
    </form>
</div>
@endsection
