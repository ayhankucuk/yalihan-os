@extends('admin.layouts.admin')

@section('title', 'Create Address - Yalıhan Emlak Pro')

@section('content')
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold flex items-center text-gray-800 dark:text-slate-200">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-plus text-white text-xl"></i>
                    </div>
                    Create Address
                </h1>
                <p class="text-lg text-gray-600 mt-2">Add a new address</p>
            </div>
            <a href="{{ route('admin.address.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:text-slate-300">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Addresses
            </a>
        </div>
    </div>

    <div class="px-6">
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-all duration-200 dark:border-slate-800 dark:bg-slate-900 p-6 max-w-4xl dark:shadow-none dark:border-slate-700">
            <form action="{{ route('admin.address.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-6">
                        <!-- Address -->
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                                Address <span class="text-red-500">*</span>
                            </label>
                            <textarea id="address" name="address" rows="3"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('address') border-red-500 @enderror"
                                placeholder="Enter full address" required>{{ old('address') }}</textarea>
                            @error('address')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- City Selection -->
                        <div>
                            <label for="il_id" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                                City <span class="text-red-500">*</span>
                            </label>
                            <select style="color-scheme: light dark;" id="il_id" name="il_id"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('il_id') border-red-500 @enderror transition-all duration-200"
                                required>
                                <option value="">Select City</option>
                                @foreach ($iller as $il)
                                    <option value="{{ $il->id }}" {{ old('il_id') == $il->id ? 'selected' : '' }}>
                                        {{ $il->il_adi }}
                                    </option>
                                @endforeach
                            </select>
                            @error('il_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- District Selection -->
                        <div>
                            <label for="ilce_id" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                                District <span class="text-red-500">*</span>
                            </label>
                            <select style="color-scheme: light dark;" id="ilce_id" name="ilce_id"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('ilce_id') border-red-500 @enderror transition-all duration-200"
                                required>
                                <option value="">Select District</option>
                                @foreach ($ilceler as $ilce)
                                    <option value="{{ $ilce->id }}" {{ old('ilce_id') == $ilce->id ? 'selected' : '' }}>
                                        {{ $ilce->ilce_adi }}
                                    </option>
                                @endforeach
                            </select>
                            @error('ilce_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Neighborhood Selection -->
                        <div>
                            <label for="mahalle_id" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                                Neighborhood
                            </label>
                            <select style="color-scheme: light dark;" id="mahalle_id" name="mahalle_id"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('mahalle_id') border-red-500 @enderror transition-all duration-200">
                                <option value="">Select Neighborhood</option>
                                @foreach ($mahalleler as $mahalle)
                                    <option value="{{ $mahalle->id }}"
                                        {{ old('mahalle_id') == $mahalle->id ? 'selected' : '' }}>
                                        {{ $mahalle->mahalle_adi }}
                                    </option>
                                @endforeach
                            </select>
                            @error('mahalle_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Postal Code -->
                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                                Postal Code
                            </label>
                            <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code') }}"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('postal_code') border-red-500 @enderror"
                                placeholder="Enter postal code">
                            @error('postal_code')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6">
                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <select style="color-scheme: light dark;" id="status" name="status"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('status') border-red-500 @enderror transition-all duration-200"
                                required>
                                <option value="">Select Status</option>
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive
                                </option>
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Coordinates -->
                        <div class="border border-gray-200 rounded-lg p-4 dark:border-slate-700">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 dark:text-slate-100 dark:text-white">Coordinates</h3>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="latitude" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                                        Latitude
                                    </label>
                                    <input type="number" id="latitude" name="latitude" value="{{ old('latitude') }}"
                                        step="any" min="-90" max="90"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('latitude') border-red-500 @enderror"
                                        placeholder="e.g., 41.0082">
                                    @error('latitude')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="longitude" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                                        Longitude
                                    </label>
                                    <input type="number" id="longitude" name="longitude" value="{{ old('longitude') }}"
                                        step="any" min="-180" max="180"
                                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('longitude') border-red-500 @enderror"
                                        placeholder="e.g., 28.9784">
                                    @error('longitude')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="button" onclick="getCurrentLocation()" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 focus:ring-offset-2-outline">
                                    <i class="fas fa-location-arrow mr-2"></i>
                                    Get Current Location
                                </button>
                                <button type="button" onclick="openMapPicker()" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 focus:ring-offset-2-outline ml-2">
                                    <i class="fas fa-map mr-2"></i>
                                    Pick on Map
                                </button>
                            </div>
                        </div>

                        <!-- Unique ID -->
                        <div>
                            <label for="unique_id" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                                Unique ID
                            </label>
                            <input type="text" id="unique_id" name="unique_id" value="{{ old('unique_id') }}"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('unique_id') border-red-500 @enderror"
                                placeholder="Leave empty for auto-generation">
                            <p class="mt-1 text-sm text-gray-500">Leave empty for auto-generation</p>
                            @error('unique_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200 mt-6 dark:border-slate-700">
                    <a href="{{ route('admin.address.index') }}"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors dark:text-slate-300">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                        <i class="fas fa-save mr-2"></i>
                        Create Address
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Auto-update districts when city changes
        document.getElementById('il_id').addEventListener('change', function() {
            const ilId = this.value;
            const districtSelect = document.getElementById('ilce_id');
            const neighborhoodSelect = document.getElementById('mahalle_id');

            // Clear dependent selects
            districtSelect.innerHTML = '<option value="">Select District</option>';
            neighborhoodSelect.innerHTML = '<option value="">Select Neighborhood</option>';

            if (ilId) {
                fetch(`/admin/address/districts?il_id=${ilId}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(district => {
                            const option = document.createElement('option');
                            option.value = district.id;
                            option.textContent = district.ilce_adi;
                            districtSelect.appendChild(option);
                        });
                    });
            }
        });

        // Auto-update neighborhoods when district changes
        document.getElementById('ilce_id').addEventListener('change', function() {
            const ilceId = this.value;
            const neighborhoodSelect = document.getElementById('mahalle_id');

            // Clear neighborhood select
            neighborhoodSelect.innerHTML = '<option value="">Select Neighborhood</option>';

            if (ilceId) {
                fetch(`/admin/address/neighborhoods?ilce_id=${ilceId}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(neighborhood => {
                            const option = document.createElement('option');
                            option.value = neighborhood.id;
                            option.textContent = neighborhood.mahalle_adi;
                            neighborhoodSelect.appendChild(option);
                        });
                    });
            }
        });

        // Get current location
        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    document.getElementById('latitude').value = position.coords.latitude;
                    document.getElementById('longitude').value = position.coords.longitude;
                }, function(error) {
                    alert('Error getting location: ' + error.message);
                });
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        }

        // Open map picker (placeholder)
        function openMapPicker() {
            alert('Map picker functionality will be implemented here');
        }
    </script>
@endpush
