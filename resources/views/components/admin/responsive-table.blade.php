{{-- 
    Responsive Table Component
    Usage: <x-admin.responsive-table>
        <thead>...</thead>
        <tbody>...</tbody>
    </x-admin.responsive-table>
    
    Props:
    - striped: Add zebra striping (default: true)
    - hoverable: Add row hover effect (default: true)
    - compact: Reduce padding for compact view (default: false)
--}}

@props([
    'striped' => true,
    'hoverable' => true,
    'compact' => false,
])

<div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-slate-700 shadow-sm dark:shadow-none">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        {{ $slot }}
    </table>
</div>

<style>
    .overflow-x-auto::-webkit-scrollbar {
        height: 6px;
    }
    .overflow-x-auto::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }
    .overflow-x-auto::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
    .overflow-x-auto::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    :is(.dark) .overflow-x-auto::-webkit-scrollbar-track {
        background: #1e293b;
    }
    :is(.dark) .overflow-x-auto::-webkit-scrollbar-thumb {
        background: #475569;
    }
    :is(.dark) .overflow-x-auto::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }
</style>
