@props([
    'striped' => true,
])

<div class="overflow-hidden">
    <div class="overflow-x-auto">
        <table {{ $attributes->merge(['class' => 'c7-table min-w-full']) }}>
            <thead class="c7-thead">
                {{ $head ?? '' }}
            </thead>
            <tbody class="c7-tbody">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
