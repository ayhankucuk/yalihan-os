@props([
    'count' => 0,
])

<div x-show="{{ $count > 0 ? 'true' : 'selectedItems.length > 0' }}" x-transition
    class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <span class="font-medium text-blue-800">
                <span x-text="selectedItems ? selectedItems.length : {{ $count }}"></span> kayıt seçildi
            </span>
            <div class="flex space-x-2">
                {{ $slot }}
            </div>
        </div>
        <button @click="selectedItems = []" class="text-blue-600 hover:text-blue-800">
            Seçimi Temizle
        </button>
    </div>
</div>
