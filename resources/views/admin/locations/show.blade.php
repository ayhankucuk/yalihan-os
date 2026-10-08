@extends('admin.layouts.admin')

@section('title', 'Location Details - Yalıhan Emlak Pro')

@section('content')
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold flex items-center text-gray-800 dark:text-slate-200">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    {{ $location['name'] ?? 'Location Details' }}
                </h1>
                <p class="text-lg text-gray-600 mt-2">Location information and details</p>
            </div>
            <a href="{{ route('admin.locations.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:text-slate-300">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Locations
            </a>
        </div>
    </div>

    <div class="px-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Location Information -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Location Information</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Location Name</label>
                            <div class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ $location['name'] ?? 'N/A' }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Type</label>
                            <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                {{ ucfirst($location['type'] ?? 'Unknown') }}
                            </span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Status</label>
                            @if (($location['status'] ?? '') === 'active')
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Active
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Inactive
                                </span>
                            @endif
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Location ID</label>
                            <div class="text-sm text-gray-600 bg-gray-50 px-4 py-2.5 rounded dark:bg-slate-900">#{{ $location['id'] ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Address Information -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Address Information</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Full Address</label>
                            <div class="text-gray-700 dark:text-slate-300">{{ $location['address'] ?? 'No address available' }}</div>
                        </div>

                        @if (isset($location['details']['postal_code']))
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Postal Code</label>
                                <div class="text-gray-700 dark:text-slate-300">{{ $location['details']['postal_code'] }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Coordinates -->
                @if (isset($location['coordinates']))
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Coordinates</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Latitude</label>
                                <div class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                                    {{ $location['coordinates']['latitude'] ?? 'N/A' }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Longitude</label>
                                <div class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                                    {{ $location['coordinates']['longitude'] ?? 'N/A' }}</div>
                            </div>
                        </div>

                        <!-- Map Preview -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">Map Preview</label>
                            <div class="h-64 bg-gray-100 rounded-lg flex items-center justify-center border dark:bg-slate-900">
                                <div class="text-center">
                                    <svg class="w-10 h-10 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                                    <p class="text-gray-500">Interactive map would be displayed here</p>
                                    <p class="text-sm text-gray-400">Map integration pending</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Location Details -->
                @if (isset($location['details']))
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Location Details</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @if (isset($location['details']['population']))
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Population</label>
                                    <div class="text-lg font-semibold text-blue-600">
                                        {{ number_format($location['details']['population']) }}</div>
                                </div>
                            @endif

                            @if (isset($location['details']['area']))
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Area</label>
                                    <div class="text-lg font-semibold text-green-600">{{ $location['details']['area'] }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">Quick Actions</h3>
                    <div class="space-y-3">
                        <button onclick="copyCoordinates()"
                            class="w-full flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-600 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Copy Coordinates
                        </button>

                        <button onclick="openInMaps()"
                            class="w-full flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-600 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            Open in Maps
                        </button>

                        <button onclick="getDirections()"
                            class="w-full flex items-center justify-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-600 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                            Get Directions
                        </button>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">Statistics</h3>
                    <div class="space-y-4">
                        @if (isset($location['details']['population']))
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Population</span>
                                <span
                                    class="text-lg font-semibold text-blue-600">{{ number_format($location['details']['population']) }}</span>
                            </div>
                        @endif

                        @if (isset($location['details']['area']))
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Area</span>
                                <span
                                    class="text-lg font-semibold text-green-600">{{ $location['details']['area'] }}</span>
                            </div>
                        @endif

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Status</span>
                            @if (($location['status'] ?? '') === 'active')
                                <span class="text-sm text-green-600 font-medium">Active</span>
                            @else
                                <span class="text-sm text-gray-600 font-medium">Inactive</span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Type</span>
                            <span
                                class="text-sm text-gray-600 font-medium">{{ ucfirst($location['type'] ?? 'Unknown') }}</span>
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
                                {{ isset($location['created_at']) ? \Carbon\Carbon::parse($location['created_at'])->format('d.m.Y H:i') : 'N/A' }}
                            </div>
                        </div>

                        <div>
                            <span class="text-sm text-gray-600">Updated</span>
                            <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                {{ isset($location['updated_at']) ? \Carbon\Carbon::parse($location['updated_at'])->format('d.m.Y H:i') : 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Coordinates Info -->
                @if (isset($location['coordinates']))
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">Coordinates</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm text-gray-600">Latitude</span>
                                <div class="text-sm font-medium text-gray-900 font-mono dark:text-slate-100 dark:text-white">
                                    {{ $location['coordinates']['latitude'] ?? 'N/A' }}</div>
                            </div>

                            <div>
                                <span class="text-sm text-gray-600">Longitude</span>
                                <div class="text-sm font-medium text-gray-900 font-mono dark:text-slate-100 dark:text-white">
                                    {{ $location['coordinates']['longitude'] ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function copyCoordinates() {
            const coords = {
                lat: {{ $location['coordinates']['latitude'] ?? 'null' }},
                lng: {{ $location['coordinates']['longitude'] ?? 'null' }}
            };

            if (coords.lat && coords.lng) {
                const coordText = `${coords.lat}, ${coords.lng}`;
                navigator.clipboard.writeText(coordText).then(() => {
                    showToast('Coordinates copied to clipboard!', 'success');
                }).catch(() => {
                    showToast('Failed to copy coordinates', 'error');
                });
            } else {
                showToast('No coordinates available', 'error');
            }
        }

        function openInMaps() {
            const coords = {
                lat: {{ $location['coordinates']['latitude'] ?? 'null' }},
                lng: {{ $location['coordinates']['longitude'] ?? 'null' }}
            };

            if (coords.lat && coords.lng) {
                const mapsUrl = `https://www.google.com/maps?q=${coords.lat},${coords.lng}`;
                window.open(mapsUrl, '_blank');
            } else {
                showToast('No coordinates available', 'error');
            }
        }

        function getDirections() {
            const coords = {
                lat: {{ $location['coordinates']['latitude'] ?? 'null' }},
                lng: {{ $location['coordinates']['longitude'] ?? 'null' }}
            };

            if (coords.lat && coords.lng) {
                const directionsUrl = `https://www.google.com/maps/dir/?api=1&destination=${coords.lat},${coords.lng}`;
                window.open(directionsUrl, '_blank');
            } else {
                showToast('No coordinates available', 'error');
            }
        }

        function showToast(message, type = 'info') {
            // Simple toast notification
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 px-4 py-2 rounded-lg text-white z-50 ${
        type === 'success' ? 'bg-green-600' :
        type === 'error' ? 'bg-red-600' : 'bg-blue-600'
    }`;
            toast.textContent = message;

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>
@endpush
