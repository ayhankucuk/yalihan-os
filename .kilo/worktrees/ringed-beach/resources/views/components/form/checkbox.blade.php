@props(['name', 'label', 'value' => 1])
{{-- Unified checkbox component. Usage:
<x-form.checkbox name="aktiflik_durumu" label="Aktif" />
Supports Alpine: <x-form.checkbox name="havuz" label="Havuz" x-model="formData.havuz" />
Pass extra classes via class attribute; id can also be passed. --}}
<label
    class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm transition-colors hover:border-blue-400 dark:border-gray-600 dark:border-slate-700 dark:bg-slate-900">
    <input type="checkbox" name="{{ $name }}" value="{{ $value }}"
        @if (!$attributes->has('x-model')) @checked(old($name)) @endif
        {{ $attributes->merge(['class' => 'checkbox-input']) }}>
    <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">{!! $label !!}</span>
</label>
