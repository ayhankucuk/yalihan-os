@extends('admin.layouts.admin')

@section('content')
<div class="p-6 space-y-4">
    @if (session('success'))
        <div class="px-4 py-2 bg-green-100 text-green-800 rounded border border-green-200 dark:bg-green-900 dark:text-green-100 dark:border-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold">UPS Feature Whitelist</h1>
        <a href="{{ route('admin.ups-feature-whitelist.create') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded transition-all duration-200 dark:bg-blue-700 dark:hover:bg-blue-800">Yeni Ekle</a>
    </div>

    <div class="overflow-x-auto bg-white dark:bg-slate-900 rounded shadow dark:shadow-none">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-slate-100 dark:bg-slate-900 dark:text-slate-300">
                <tr>
                    <th class="px-4 py-2 text-left">Kategori</th>
                    <th class="px-4 py-2 text-left">Feature Category Slug</th>
                    <th class="px-4 py-2 text-left">Aktiflik</th>
                    <th class="px-4 py-2 text-left">Oluşturma</th>
                    <th class="px-4 py-2 text-right">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-900 dark:text-slate-100 dark:text-white">
                @forelse ($entries as $entry)
                    <tr>
                        <td class="px-4 py-2">{{ $entry->kategori->name ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $entry->feature_category_slug }}</td>
                        <td class="px-4 py-2">{{ $entry->aktiflik_durumu ? 'Aktif' : 'Pasif' }}</td>
                        <td class="px-4 py-2">{{ $entry->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-2 text-right space-x-2">
                            <a href="{{ route('admin.ups-feature-whitelist.edit', $entry) }}" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded transition-all duration-200 dark:bg-indigo-700 dark:hover:bg-indigo-800">Düzenle</a>
                            <form action="{{ route('admin.ups-feature-whitelist.destroy', $entry) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded transition-all duration-200 dark:bg-red-700 dark:hover:bg-red-800" onclick="return confirm('Silinsin mi?')">Sil</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-4 text-center text-gray-500">Kayıt bulunamadı.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $entries->links() }}
    </div>
</div>
@endsection
