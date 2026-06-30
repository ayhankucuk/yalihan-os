@props([
    'label' => 'Fiyat Türleri',
    'fields' => [
        ['name' => 'gunluk_fiyat', 'label' => 'Günlük (₺)', 'placeholder' => '500'],
        ['name' => 'haftalik_fiyat', 'label' => 'Haftalık (₺)', 'placeholder' => '3000'],
        ['name' => 'aylik_fiyat', 'label' => 'Aylık (₺)', 'placeholder' => '12000'],
        ['name' => 'sezonluk_fiyat', 'label' => 'Sezonluk (₺)', 'placeholder' => '50000'],
    ],
    'alpine' => true, // Alpine formData binding varsayıyor
])
<div class="md:col-span-2 lg:col-span-3 form-field">
    <label class="form-label">{{ $label }}</label>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ($fields as $f)
            @php($fname = $f['name'])
            <div class="form-field">
                <label for="{{ $fname }}" class="form-label text-xs">{{ $f['label'] }}</label>
                <input type="number" id="{{ $fname }}" name="{{ $fname }}"
                    @if ($alpine) x-model="formData.{{ $fname }}" @endif class="form-input"
                    placeholder="{{ $f['placeholder'] ?? '' }}" min="0">
            </div>
        @endforeach
    </div>
</div>
