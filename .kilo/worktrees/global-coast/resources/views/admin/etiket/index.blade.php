@extends('admin.layouts.admin')

@section('title', 'Etiket Management - Yalıhan Emlak Pro')

@section('content')
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold flex items-center text-gray-800 dark:text-slate-200">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-tags text-white text-xl"></i>
                    </div>
                    Etiket Management
                </h1>
                <p class="text-lg text-gray-600 mt-2">Manage system labels and tags</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.etiket.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 font-medium shadow-md hover:shadow-lg hover:scale-105 active:scale-95 dark:shadow-none">
                    <i class="fas fa-plus mr-2"></i>
                    New Etiket
                </a>
                <a href="{{ route('admin.etiket.export') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-all duration-200 font-medium shadow-sm hover:shadow-md dark:shadow-none">
                    <i class="fas fa-download mr-2"></i>
                    Export
                </a>
            </div>
        </div>
    </div>

    <div class="px-6">
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2.5 rounded relative mb-4"
                    role="alert">
                    <strong class="font-bold">Success!</strong>
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2.5 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <!-- Search and Filter -->
            <form action="{{ route('admin.etiket.index') }}" method="GET" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="text" name="search" placeholder="Search etiketler..."
                        class="w-full px-3 py-2 rounded-md border border-gray-200 bg-white text-sm focus:ring-2 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-800 col-span-2 dark:border-slate-700"
                        value="{{ request('search') }}">
                    <select style="color-scheme: light dark;" name="aktiflik_durumu"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 form-select transition-all duration-200 dark:text-slate-100"
                        onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="1" {{ request('aktiflik_durumu') == '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('aktiflik_durumu') == '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 font-medium shadow-md hover:shadow-lg col-span-1 md:col-span-1 dark:shadow-none">
                        <i class="fas fa-search mr-2"></i>
                        Filter
                    </button>
                </div>
            </form>

            <!-- Bulk Actions -->
            <form id="bulkForm" action="{{ route('admin.etiket.bulk.action') }}" method="POST" class="mb-6">
                @csrf
                <div class="flex items-center gap-4">
                    <select style="color-scheme: light dark;" name="action"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 form-select transition-all duration-200 dark:text-slate-100">
                        <option value="">Bulk Actions</option>
                        <option value="activate">Activate</option>
                        <option value="deactivate">Deactivate</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition-all duration-200 font-medium shadow-md hover:shadow-lg dark:shadow-none"
                        onclick="return confirmBulkAction()">
                        <i class="fas fa-check mr-2"></i>
                        Apply
                    </button>
                </div>
            </form>

            <!-- Etiket Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" id="selectAll" class="rounded border-gray-300">
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Color
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Icon
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Usage Count
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-slate-900">
                        @forelse ($etiketler as $etiket)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" name="ids[]" value="{{ $etiket->id }}"
                                        class="rounded border-gray-300 etiket-checkbox">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                    {{ $etiket->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex items-center">
                                        @if ($etiket->icon)
                                            <i class="{{ $etiket->icon }} mr-2 text-lg"></i>
                                        @endif
                                        {{ $etiket->name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if ($etiket->color)
                                        <div class="flex items-center">
                                            <div class="w-4 h-4 rounded-full mr-2"
                                                style="background-color: {{ $etiket->color }}"></div>
                                            <span class="text-xs">{{ $etiket->color }}</span>
                                        </div>
                                    @else
                                        <span class="text-gray-400">No color</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $etiket->icon ?? 'No icon' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $etiket->kisiler_count ?? 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $etiket->aktiflik_durumu ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $etiket->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('admin.etiket.show', $etiket->id) }}"
                                        class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                                    <a href="{{ route('admin.etiket.edit', $etiket->id) }}"
                                        class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                    <form action="{{ route('admin.etiket.destroy', $etiket->id) }}" method="POST"
                                        class="inline-block"
                                        onsubmit="return confirm('Are you sure you want to delete this etiket?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    <div class="flex flex-col items-center py-8">
                                        <i class="fas fa-tags text-gray-400 text-4xl mb-4"></i>
                                        <p class="text-lg font-medium text-gray-900 mb-2 dark:text-slate-100 dark:text-white">No etiketler found</p>
                                        <p class="text-gray-500 mb-4">Create your first etiket to get started</p>
                                        <a href="{{ route('admin.etiket.create') }}"
                                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 font-medium shadow-md hover:shadow-lg dark:shadow-none">
                                            <i class="fas fa-plus mr-2"></i>
                                            Create Etiket
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $etiketler->links() }}
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Select All functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.etiket-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Bulk form handling
        document.getElementById('bulkForm').addEventListener('submit', function(e) {
            const checkedBoxes = document.querySelectorAll('.etiket-checkbox:checked');
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('Please select at least one etiket');
                return;
            }

            // Add selected IDs to form
            checkedBoxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = checkbox.value;
                this.appendChild(input);
            });
        });

        function confirmBulkAction() {
            const action = document.querySelector('select[name="action"]').value;
            const checkedBoxes = document.querySelectorAll('.etiket-checkbox:checked');

            if (!action) {
                alert('Please select an action');
                return false;
            }

            if (checkedBoxes.length === 0) {
                alert('Please select at least one etiket');
                return false;
            }

            const actionText = action.charAt(0).toUpperCase() + action.slice(1);
            return confirm(`Are you sure you want to ${actionText} ${checkedBoxes.length} selected etiket(s)?`);
        }
    </script>
@endpush
