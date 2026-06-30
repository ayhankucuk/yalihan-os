@props(['yazlikDetail'])

@if ($yazlikDetail)
    @php
        $dahilHizmetler = [];
        $dahilOlmayanHizmetler = [];

        if ($yazlikDetail->elektrik_dahil ?? false) {
            $dahilHizmetler[] = ['name' => 'Elektrik Kullanımı', 'icon' => 'bolt'];
        } else {
            $dahilOlmayanHizmetler[] = ['name' => 'Elektrik', 'icon' => 'bolt'];
        }

        if ($yazlikDetail->su_dahil ?? false) {
            $dahilHizmetler[] = ['name' => 'Su Kullanımı', 'icon' => 'droplet'];
        } else {
            $dahilOlmayanHizmetler[] = ['name' => 'Su', 'icon' => 'droplet'];
        }

        if ($yazlikDetail->internet_dahil ?? false) {
            $dahilHizmetler[] = ['name' => 'İnternet Kullanımı', 'icon' => 'wifi'];
        } else {
            $dahilOlmayanHizmetler[] = ['name' => 'İnternet', 'icon' => 'wifi'];
        }

        if ($yazlikDetail->havlu_dahil ?? false) {
            $dahilHizmetler[] = ['name' => 'Havlu', 'icon' => 'square'];
        }

        if ($yazlikDetail->carsaf_dahil ?? false) {
            $dahilHizmetler[] = ['name' => 'Çarşaf', 'icon' => 'square'];
        }
    @endphp

    <div class="space-y-6">
        @if (count($dahilHizmetler) > 0)
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2 dark:text-slate-100">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Ücrete Dahil Olan Hizmetler
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach ($dahilHizmetler as $hizmet)
                        <div class="flex items-center gap-2 text-gray-700 dark:text-slate-200 dark:text-slate-300">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>{{ $hizmet['name'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if (count($dahilOlmayanHizmetler) > 0)
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2 dark:text-slate-100">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                    Ücrete Dahil Olmayan Hizmetler
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach ($dahilOlmayanHizmetler as $hizmet)
                        <div class="flex items-center gap-2 text-gray-700 dark:text-slate-200 dark:text-slate-300">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span>{{ $hizmet['name'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
