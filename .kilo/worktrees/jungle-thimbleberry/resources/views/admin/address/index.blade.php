@extends('admin.layouts.admin')

@section('title', 'Address Management - Yalıhan Emlak Pro')

@section('content')
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold flex items-center text-gray-800 dark:text-slate-200">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-map-marker-alt text-white text-xl"></i>
                    </div>
                    Address Management
                </h1>
                <p class="text-lg text-gray-600 mt-2">Manage property addresses and locations</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.address.create') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium font-medium-primary">
                    <i class="fas fa-plus mr-2"></i>
                    Add Address
                </a>
            </div>
        </div>
    </div>

    <div class="px-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-4 mb-8">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-map-marker-alt text-white"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-blue-600">{{ $addresses->total() }}</h3>
                        <p class="text-sm text-gray-600 font-medium">Total Addresses</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-check-circle text-white"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-green-600">{{ $addresses->where('aktiflik_durumu', true)->count() }}
                        </h3>
                        <p class="text-sm text-gray-600 font-medium">Active Addresses</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-map text-white"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-purple-600">{{ $iller->count() }}</h3>
                        <p class="text-sm text-gray-600 font-medium">Cities</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-orange-500 to-yellow-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-building text-white"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-2xl font-bold text-orange-600">{{ $ilceler->count() }}</h3>
                        <p class="text-sm text-gray-600 font-medium">Districts</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Address Table -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-800 dark:text-slate-200">Addresses</h2>
                <div class="flex gap-2">
                    <input type="text" name="search" placeholder="Search addresses..." value="{{ request('search') }}"
                        class="px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">

                    <select style="color-scheme: light dark;" name="il_id"
                        class="px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                        <option value="">All Cities</option>
                        @foreach ($iller as $il)
                            <option value="{{ $il->id }}" {{ request('il_id') == $il->id ? 'selected' : '' }}>
                                {{ $il->il_adi }}
                            </option>
                        @endforeach
                    </select>

                    <select style="color-scheme: light dark;" name="ilce_id"
                        class="px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                        <option value="">All Districts</option>
                        @foreach ($ilceler as $ilce)
                            <option value="{{ $ilce->id }}" {{ request('ilce_id') == $ilce->id ? 'selected' : '' }}>
                                {{ $ilce->ilce_adi }}
                            </option>
                        @endforeach
                    </select>

                    <button onclick="filterAddresses()" class="inline-flex items-center px-4 py-2.5 text-sm font-medium font-medium-outline">
                        <i class="fas fa-search mr-2"></i>
                        Filter
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-slate-700">
                            <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-slate-300">Address</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-slate-300">Location</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-slate-300">Postal Code</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-slate-300">Coordinates</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-slate-300">Status</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700 dark:text-slate-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($addresses as $address)
                            <tr class="border-b border-gray-100 hover:bg-gray-50 dark:border-slate-800">
                                <td class="py-4 px-4">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-slate-100 dark:text-white">{{ Str::limit($address->address, 50) }}
                                        </div>
                                        @if ($address->unique_id)
                                            <div class="text-sm text-gray-500">ID: {{ $address->unique_id }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="text-sm">
                                        <div class="font-medium">{{ $address->il->il_adi ?? 'N/A' }}</div>
                                        <div class="text-gray-500">{{ $address->ilce->ilce_adi ?? 'N/A' }}</div>
                                        @if ($address->mahalle)
                                            <div class="text-xs text-gray-400">{{ $address->mahalle->mahalle_adi }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="text-sm text-gray-600">{{ $address->postal_code ?? 'N/A' }}</span>
                                </td>
                                <td class="py-4 px-4">
                                    @if ($address->lat && $address->lng)
                                        <div class="text-xs text-gray-500">
                                            <div>{{ number_format($address->lat, 6) }}</div>
                                            <div>{{ number_format($address->lng, 6) }}</div>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">Not set</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4">
                                    @if ($address->aktiflik_durumu)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            Active
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-slate-900 dark:text-slate-200">
                                            <i class="fas fa-pause-circle mr-1"></i>
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('admin.address.show', $address) }}"
                                            class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.address.edit', $address) }}"
                                            class="text-green-600 hover:text-green-800">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteAddress({{ $address->id }})"
                                            class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center">
                                    <div class="text-gray-500">
                                        <i class="fas fa-map-marker-alt text-4xl mb-4"></i>
                                        <p class="text-lg">No addresses found</p>
                                        <p class="text-sm">Create your first address to get started</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($addresses->hasPages())
                <div class="mt-6">
                    {{ $addresses->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function filterAddresses() {
            const search = document.querySelector('input[name="search"]').value;
            const ilId = document.querySelector('select[name="il_id"]').value;
            const ilceId = document.querySelector('select[name="ilce_id"]').value;

            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (ilId) params.append('il_id', ilId);
            if (ilceId) params.append('ilce_id', ilceId);

            window.location.href = `{{ route('admin.address.index') }}?${params.toString()}`;
        }

        function deleteAddress(id) {
            if (confirm('Are you sure you want to delete this address?')) {
                fetch(`/admin/address/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error deleting address: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error deleting address');
                    });
            }
        }

        // Auto-submit on filter change
        document.querySelector('select[name="il_id"]').addEventListener('change', function() {
            // Update districts based on selected city
            const ilId = this.value;
            const districtSelect = document.querySelector('select[name="ilce_id"]');

            if (ilId) {
                fetch(`/admin/address/districts?il_id=${ilId}`)
                    .then(response => response.json())
                    .then(data => {
                        districtSelect.innerHTML = '<option value="">All Districts</option>';
                        data.forEach(district => {
                            const option = document.createElement('option');
                            option.value = district.id;
                            option.textContent = district.ilce_adi;
                            districtSelect.appendChild(option);
                        });
                    });
            }
        });
    </script>
@endpush
