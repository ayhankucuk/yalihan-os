@extends('admin.layouts.admin')

@section('title', 'Marketing Asset Templates')

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Marketing Asset Templates</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Sosyal medya görselleri için template yönetimi ve düzenleme
            </p>
        </div>
        <a href="{{ route('admin.marketing.templates.edit', ['format' => 'instagram_post', 'template' => 'default']) }}"
           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none">
            <i class="fas fa-plus mr-2"></i>Yeni Template
        </a>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
        <p class="text-green-800 dark:text-green-200">{{ session('success') }}</p>
    </div>
    @endif

    {{-- Templates Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-6">
        @foreach($templates as $format => $formatData)
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-md hover:shadow-xl transition-all duration-200 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            {{-- Format Header --}}
            <div class="p-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                            {{ $formatData['name'] }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $formatData['dimensions'] }}
                        </p>
                    </div>
                    <div class="text-2xl">
                        @if($formatData['icon'] === 'instagram')
                            <i class="fab fa-instagram text-pink-500"></i>
                        @elseif($formatData['icon'] === 'facebook')
                            <i class="fab fa-facebook text-blue-600"></i>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Templates List --}}
            <div class="p-4">
                @if(count($formatData['templates']) > 0)
                <ul class="space-y-2">
                    @foreach($formatData['templates'] as $template)
                    <li class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200">
                        <span class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ $template }}</span>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.marketing.templates.edit', ['format' => $format, 'template' => $template]) }}"
                               class="px-3 py-1 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-all duration-200">
                                <i class="fas fa-edit"></i> Düzenle
                            </a>
                            @if($template !== 'default')
                            <form action="{{ route('admin.marketing.templates.destroy') }}" method="POST" class="inline"
                                  onsubmit="return confirm('Bu template\'i silmek istediğinizden emin misiniz?');">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="format" value="{{ $format }}">
                                <input type="hidden" name="template_name" value="{{ $template }}">
                                <button type="submit"
                                        class="px-3 py-1 text-xs bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded hover:bg-red-200 dark:hover:bg-red-900/50 transition-all duration-200">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </li>
                    @endforeach
                </ul>
                @else
                <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                    Henüz template yok
                </p>
                @endif

                {{-- Add New Template Button --}}
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <a href="{{ route('admin.marketing.templates.edit', ['format' => $format, 'template' => 'new']) }}"
                       class="block w-full text-center px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200 dark:bg-slate-900 dark:text-slate-300">
                        <i class="fas fa-plus mr-2"></i>Yeni Template Ekle
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

