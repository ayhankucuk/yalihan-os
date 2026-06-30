{{--
    🏗️ Dynamic Field Render Loop — Server-Side Schema Renderer
    
    Bu component, FieldRenderer servisinden gelen grouped HTML'i render eder.
    Server-side rendering: dependency rules PHP tarafında evaluate edilir.
    
    Kullanım (Controller'dan):
        $resolver = app(FieldResolver::class);
        $renderer = app(FieldRenderer::class);
        $fields = $resolver->resolve($kategoriId, $yayinTipiId);
        $groups = $renderer->renderGrouped($fields, $formValues);
        return view('...', compact('groups', 'fields'));
    
    Kullanım (Blade'den):
        @include('components.fields.render-loop', ['groups' => $groups])
    
    @version 2.0.0 — Field Engine
--}}

@props(['groups' => [], 'fields' => []])

@if(empty($groups))
    {{-- Empty State --}}
    <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-200 py-16 dark:border-slate-700">
        <span class="mb-3 text-5xl">📋</span>
        <p class="text-base font-semibold text-gray-500 dark:text-slate-400">
            Bu kombinasyon için tanımlı alan bulunamadı
        </p>
        <p class="mt-1 text-sm text-gray-400 dark:text-slate-500">
            Temel bilgiler ile devam edebilirsiniz
        </p>
    </div>
@else
    <div class="space-y-6">
        @foreach($groups as $group)
            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white/70 shadow-sm backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/70">
                {{-- Group Header --}}
                <div class="border-b border-gray-100 bg-gray-50/80 px-6 py-4 dark:border-slate-800 dark:bg-slate-800/50">
                    <h4 class="flex items-center gap-2 text-sm font-bold text-gray-800 dark:text-slate-200">
                        <svg class="h-4 w-4 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        {{ $group['name'] }}
                    </h4>
                    <p class="mt-0.5 text-[10px] text-gray-400 dark:text-slate-500">
                        {{ $group['field_count'] }} alan
                    </p>
                </div>

                {{-- Fields Grid --}}
                <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2 lg:grid-cols-3">
                    {!! $group['html'] !!}
                </div>
            </div>
        @endforeach
    </div>
@endif
