{{--
    Neo Design System Skeleton Loading Component
    Context7 uyumlu skeleton loader

    Kullanım:
    <x-admin.bg-gray-200 dark:bg-gray-700 type="card" />
    <x-admin.bg-gray-200 dark:bg-gray-700 type="table" rows="5" />
    <x-admin.bg-gray-200 dark:bg-gray-700 type="list" items="3" />

    @context7-compliant true
    @space-y-4 true
--}}

@props([
    'type' => 'text',
    'rows' => 3,
    'items' => 3,
    'height' => 'auto',
    'width' => 'full',
    'rounded' => 'md',
    'animate' => true
])

@php
    $animateClass = $animate ? 'animate-pulse' : '';
    $widthClass = $width === 'full' ? 'w-full' : $width;
    $roundedClass = 'rounded-' . $rounded;
@endphp

{{-- Text Skeleton --}}
@if($type === 'text')
    <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} $widthClass $roundedClass"
         style="height: {{ $height === 'auto' ? '1rem' : $height }}"
         role="presentation"
         aria-label="Yükleniyor...">
        <span class="sr-only">Yükleniyor...</span>
    </div>
@endif

{{-- Heading Skeleton --}}
@if($type === 'heading')
    <div class="space-y-3">
        <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-3/4 h-8 $roundedClass" role="presentation" aria-label="Başlık yükleniyor...">
            <span class="sr-only">Başlık yükleniyor...</span>
        </div>
        <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-1/2 h-4 $roundedClass" role="presentation" aria-label="Alt başlık yükleniyor...">
            <span class="sr-only">Alt başlık yükleniyor...</span>
        </div>
    </div>
@endif

{{-- Paragraph Skeleton --}}
@if($type === 'paragraph')
    <div class="space-y-2">
        @for($i = 0; $i < $rows; $i++)
            <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} $i === $rows - 1 ? 'w-3/4' : 'w-full' h-4 $roundedClass"
                 role="presentation"
                 aria-label="Paragraf yükleniyor...">
                <span class="sr-only">Paragraf yükleniyor...</span>
            </div>
        @endfor
    </div>
@endif

{{-- Card Skeleton --}}
@if($type === 'card')
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 space-y-4 dark:shadow-none dark:border-slate-700" role="presentation" aria-label="Kart yükleniyor...">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-12 h-12 rounded-full"></div>
            <div class="flex-1 space-y-2">
                <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-1/2 h-5 $roundedClass"></div>
                <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-1/3 h-4 $roundedClass"></div>
            </div>
        </div>

        <!-- Body -->
        <div class="space-y-2">
            @for($i = 0; $i < 3; $i++)
                <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-full h-4 $roundedClass"></div>
            @endfor
            <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-3/4 h-4 $roundedClass"></div>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-24 h-8 $roundedClass"></div>
            <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-24 h-8 $roundedClass"></div>
        </div>

        <span class="sr-only">Kart yükleniyor...</span>
    </div>
@endif

{{-- Table Skeleton --}}
@if($type === 'table')
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700" role="presentation" aria-label="Tablo yükleniyor...">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/50 dark:bg-slate-900">
                <tr>
                    @for($i = 0; $i < 5; $i++)
                        <th class="px-4 py-2.5">
                            <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-full h-3 $roundedClass"></div>
                        </th>
                    @endfor
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                @for($r = 0; $r < $rows; $r++)
                    <tr>
                        @for($c = 0; $c < 5; $c++)
                            <td class="px-4 py-2.5">
                                <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-full h-3 $roundedClass"></div>
                            </td>
                        @endfor
                    </tr>
                @endfor
            </tbody>
        </table>
        <span class="sr-only">Tablo yükleniyor...</span>
    </div>
@endif

{{-- List Skeleton --}}
@if($type === 'list')
    <div class="space-y-3" role="presentation" aria-label="Liste yükleniyor...">
        @for($i = 0; $i < $items; $i++)
            <div class="flex items-center gap-3 p-4 bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-10 h-10 rounded-full"></div>
                <div class="flex-1 space-y-2">
                    <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-3/4 h-4 $roundedClass"></div>
                    <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-1/2 h-3 $roundedClass"></div>
                </div>
                <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-20 h-8 $roundedClass"></div>
            </div>
        @endfor
        <span class="sr-only">Liste yükleniyor...</span>
    </div>
@endif

{{-- Avatar Skeleton --}}
@if($type === 'avatar')
    <div class="flex items-center gap-3" role="presentation" aria-label="Profil yükleniyor...">
        <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-12 h-12 rounded-full"></div>
        <div class="flex-1 space-y-2">
            <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-32 h-4 $roundedClass"></div>
            <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-24 h-3 $roundedClass"></div>
        </div>
        <span class="sr-only">Profil yükleniyor...</span>
    </div>
@endif

{{-- Image Skeleton --}}
@if($type === 'image')
    <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} $widthClass $roundedClass"
         style="height: {{ $height === 'auto' ? '200px' : $height }}"
         role="presentation"
         aria-label="Görsel yükleniyor...">
        <div class="flex items-center justify-center h-full">
            <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
            </svg>
        </div>
        <span class="sr-only">Görsel yükleniyor...</span>
    </div>
@endif

{{-- Stats Card Skeleton --}}
@if($type === 'stats')
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        @for($i = 0; $i < 4; $i++)
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700" role="presentation" aria-label="İstatistik yükleniyor...">
                <div class="flex items-center justify-between">
                    <div class="flex-1 space-y-3">
                        <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-24 h-4 $roundedClass"></div>
                        <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-16 h-8 $roundedClass"></div>
                    </div>
                    <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-12 h-12 rounded-full"></div>
                </div>
                <span class="sr-only">İstatistik kartı yükleniyor...</span>
            </div>
        @endfor
    </div>
@endif

{{-- Form Skeleton --}}
@if($type === 'form')
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 space-y-6 dark:shadow-none dark:border-slate-700" role="presentation" aria-label="Form yükleniyor...">
        @for($i = 0; $i < $rows; $i++)
            <div class="space-y-2">
                <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-32 h-4 $roundedClass"></div>
                <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-full h-10 $roundedClass"></div>
            </div>
        @endfor

        <div class="flex items-center gap-3 pt-4">
            <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-32 h-10 $roundedClass"></div>
            <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} w-32 h-10 $roundedClass"></div>
        </div>

        <span class="sr-only">Form yükleniyor...</span>
    </div>
@endif

{{-- Custom Skeleton --}}
@if($type === 'custom')
    <div class="bg-gray-200 dark:bg-gray-700 {{ $animateClass }} $widthClass $roundedClass"
         style="height: {{ $height }}"
         role="presentation"
         aria-label="İçerik yükleniyor...">
        {{ $slot }}
        <span class="sr-only">İçerik yükleniyor...</span>
    </div>
@endif
