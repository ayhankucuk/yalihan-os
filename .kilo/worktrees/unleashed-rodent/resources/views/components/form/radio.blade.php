@props(['name', 'label', 'value', 'model' => null, 'checked' => false])
{{-- Usage: <x-form.radio name="status" value="1" label="Aktif" x-model="formData.status" /> --}}
<label
    class="flex items-center gap-2 px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-slate-900 hover:border-blue-400 transition-colors cursor-pointer text-sm select-none dark:border-slate-700">
    <input type="radio" name="{{ $name }}" value="{{ $value }}"
        @if ($checked) checked @endif {{ $attributes->merge(['class' => 'radio-input']) }}>
    <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">{!! $label !!}</span>
</label>
