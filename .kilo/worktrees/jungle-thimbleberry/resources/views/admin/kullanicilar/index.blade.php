@extends('layouts.app')

@section('content')
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Kullanıcılar</h1>
    
    <div class="overflow-x-auto">
        <table class="w-full border-collapse border border-gray-300 dark:border-gray-600">
            <thead class="bg-gray-100 dark:bg-slate-900">
                <tr>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-2">ID</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-2">Ad</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-2">Email</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-2">Role ID</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-2">Aktif</th>
                    <th class="border border-gray-300 dark:border-gray-600 px-4 py-2">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-2">{{ $user->id }}</td>
                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-2">{{ $user->name }}</td>
                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-2">{{ $user->email }}</td>
                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-2">{{ $user->role_id ?? '—' }}</td>
                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-2">
                        <span class="px-2 py-1 rounded {{ $user->aktiflik_durumu ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                            {{ $user->aktiflik_durumu ? 'Evet' : 'Hayır' }}
                        </span>
                    </td>
                    <td class="border border-gray-300 dark:border-gray-600 px-4 py-2">
                        <a href="{{ route('admin.kullanicilar.edit', $user->id) }}" class="text-blue-500 hover:text-blue-700">Düzenle</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="border border-gray-300 dark:border-gray-600 px-4 py-2 text-center text-gray-500">Kullanıcı bulunamadı</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
@endsection
