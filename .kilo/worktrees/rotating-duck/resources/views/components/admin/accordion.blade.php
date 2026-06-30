{{--
    Modern Accordion Component
    Context7 compliant, Tailwind CSS, Alpine.js

    Kullanım:
    <x-admin.accordion>
        <x-admin.accordion-item title="Bölüm 1" :open="true">
            İçerik 1
        </x-admin.accordion-item>

        <x-admin.accordion-item title="Bölüm 2">
            İçerik 2
        </x-admin.accordion-item>
    </x-admin.accordion>

    @context7-compliant true
    @tailwind-only true
--}}

@props([
    'allowMultiple' => false, // Birden fazla açık olabilir mi?
    'bordered' => true,
    'spacing' => 'normal' // compact, normal, relaxed
])

@php
    $spacingClasses = [
        'compact' => 'space-y-1',
        'normal' => 'space-y-4',
        'relaxed' => 'space-y-6'
    ];
@endphp

<div
    x-data="{
        activeItems: @json($allowMultiple ? [] : null),
        allowMultiple: {{ $allowMultiple ? 'true' : 'false' }},
        toggle(id) {
            if (this.allowMultiple) {
                // Multiple mode: toggle array
                if (this.activeItems.includes(id)) {
                    this.activeItems = this.activeItems.filter(item => item !== id);
                } else {
                    this.activeItems.push(id);
                }
            } else {
                // Single mode: toggle single value
                this.activeItems = this.activeItems === id ? null : id;
            }
        },
        isOpen(id) {
            if (this.allowMultiple) {
                return this.activeItems.includes(id);
            }
            return this.activeItems === id;
        }
    }"
    class="{{ $spacingClasses[$spacing] ?? $spacingClasses['normal'] }}"
    role="region"
    aria-label="Accordion bölümleri">

    {{ $slot }}
</div>
