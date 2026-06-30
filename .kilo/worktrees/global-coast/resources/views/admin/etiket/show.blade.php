@extends('admin.layouts.admin')

@section('title', 'Etiket Details - Yalıhan Emlak Pro')

@section('content')
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold flex items-center text-gray-800 dark:text-slate-200">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-tag text-white text-xl"></i>
                    </div>
                    {{ $etiket->name ?? 'Etiket Details' }}
                </h1>
                <p class="text-lg text-gray-600 mt-2">Etiket details and usage information</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.etiket.edit', $etiket->id) }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg dark:shadow-none">
                    <i class="fas fa-edit mr-2"></i>
                    Edit Etiket
                </a>
                <a href="{{ route('admin.etiket.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:text-slate-300">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Etiketler
                </a>
            </div>
        </div>
    </div>

    <div class="px-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Etiket Information -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Etiket Information</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Etiket Name</label>
                            <div class="flex items-center">
                                @if($etiket->icon)
                                    <i class="{{ $etiket->icon }} text-2xl mr-3"></i>
                                @endif
                                <span class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ $etiket->name }}</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Color</label>
                            <div class="flex items-center">
                                @if($etiket->color)
                                    <div class="w-6 h-6 rounded-full mr-3 border" style="background-color: {{ $etiket->color }}"></div>
                                    <span class="text-sm text-gray-600">{{ $etiket->color }}</span>
                                @else
                                    <span class="text-gray-400">No color set</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Icon</label>
                            <div class="flex items-center">
                                @if($etiket->icon)
                                    <i class="{{ $etiket->icon }} text-2xl mr-3"></i>
                                    <span class="text-sm text-gray-600">{{ $etiket->icon }}</span>
                                @else
                                    <i class="fas fa-tag text-2xl text-gray-400 mr-3"></i>
                                    <span class="text-gray-400">No icon set</span>
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Status</label>
                            @if($etiket->aktiflik_durumu)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-pause-circle mr-1"></i>
                                    Inactive
                                </span>
                            @endif
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Usage Count</label>
                            <div class="text-2xl font-bold text-blue-600">{{ $etiket->kisiler_count ?? 0 }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Slug</label>
                            <div class="text-sm text-gray-600 bg-gray-50 px-4 py-2.5 rounded dark:bg-slate-900">{{ $etiket->slug ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                @if($etiket->description)
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Description</h2>
                        <p class="text-gray-700 leading-relaxed dark:text-slate-300">{{ $etiket->description }}</p>
                    </div>
                @endif

                <!-- Associated Kisiler -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Associated Kisiler</h2>

                    @if($etiket->kisiler && count($etiket->kisiler) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 dark:bg-slate-900">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            ID
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Name
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Email
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Phone
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 dark:bg-slate-900">
                                    @foreach($etiket->kisiler as $kisi)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                                {{ $kisi->id }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $kisi->name ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $kisi->email ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $kisi->phone ?? 'N/A' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-500">No kisiler associated with this etiket</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="{{ route('admin.etiket.edit', $etiket->id) }}"
                           class="w-full flex items-center justify-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                            <i class="fas fa-edit mr-2"></i>
                            Edit Etiket
                        </a>

                        <button onclick="deleteEtiket({{ $etiket->id }})"
                                class="w-full flex items-center justify-center px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                            <i class="fas fa-trash mr-2"></i>
                            Delete Etiket
                        </button>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">Statistics</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Usage Count</span>
                            <span class="text-lg font-semibold text-blue-600">{{ $etiket->kisiler_count ?? 0 }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Status</span>
                            @if($etiket->aktiflik_durumu)
                                <span class="text-sm text-green-600 font-medium">Active</span>
                            @else
                                <span class="text-sm text-gray-600 font-medium">Inactive</span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Has Icon</span>
                            <span class="text-sm {{ $etiket->icon ? 'text-green-600' : 'text-gray-600' }} font-medium">
                                {{ $etiket->icon ? 'Yes' : 'No' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Has Color</span>
                            <span class="text-sm {{ $etiket->color ? 'text-green-600' : 'text-gray-600' }} font-medium">
                                {{ $etiket->color ? 'Yes' : 'No' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Timestamps -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">Timestamps</h3>
                    <div class="space-y-3">
                        <div>
                            <span class="text-sm text-gray-600">Created</span>
                            <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                {{ $etiket->created_at ? $etiket->created_at->format('d.m.Y H:i') : 'N/A' }}
                            </div>
                        </div>

                        <div>
                            <span class="text-sm text-gray-600">Updated</span>
                            <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                {{ $etiket->updated_at ? $etiket->updated_at->format('d.m.Y H:i') : 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">Preview</h3>
                    <div class="p-4 bg-gray-50 rounded-lg border dark:bg-slate-900">
                        <div class="flex items-center">
                            @if($etiket->icon)
                                <i class="{{ $etiket->icon }} text-lg mr-2"></i>
                            @endif
                            <span class="font-medium">{{ $etiket->name }}</span>
                            @if($etiket->color)
                                <div class="w-4 h-4 rounded-full ml-2" style="background-color: {{ $etiket->color }};"></div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function deleteEtiket(id) {
    if (confirm('Are you sure you want to delete this etiket?')) {
        fetch(`/admin/etiket/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '{{ route("admin.etiket.index") }}';
            } else {
                alert('Error deleting etiket: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting etiket');
        });
    }
}
</script>
@endpush
