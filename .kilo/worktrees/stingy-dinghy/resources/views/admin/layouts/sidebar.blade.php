<aside class="w-72 h-screen text-white flex flex-col flex-shrink-0 relative z-50 transition-all duration-300" style="background-color: #0B1120; border-right: 1px solid rgba(255,255,255,0.05);">
    <!-- Branding -->
    <div class="p-6 pb-8">
        <div class="flex items-center gap-3.5">
            <div class="relative group cursor-pointer">
                <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-cyan-500 rounded-xl blur opacity-25 group-hover:opacity-50 transition duration-200"></div>
                <div class="relative w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl flex items-center justify-center shadow-inner border border-white/10">
                    <span class="text-white font-bold text-xl tracking-tight">Y</span>
                </div>
            </div>
            <div class="min-w-0 flex-1">
                <h2 class="font-bold text-lg tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white to-white/70">Yalıhan Emlak</h2>
                <div class="flex items-center gap-1.5">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                    <p class="text-xs text-slate-400 font-medium tracking-wide">Yönetim Paneli</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 pb-6 overflow-y-auto scrollbar-thin scrollbar-thumb-white/10 scrollbar-track-transparent" x-data="{ query: '' }">
        <!-- Search -->
        <div class="mb-6 sticky top-0 z-10 bg-[#0B1120] pt-2 pb-1">
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-slate-500 group-focus-within:text-blue-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input x-model.debounce.200ms="query" type="text" placeholder="Menüde ara..."
                    class="block w-full h-10 pl-10 pr-3 rounded-xl bg-slate-800/40 dark:bg-slate-800/50 border border-slate-700/40 dark:border-slate-700/50 text-sm text-slate-200 dark:text-slate-200 placeholder-slate-500 dark:placeholder-slate-500 focus:bg-slate-800/55 dark:focus:bg-slate-800/65 focus:border-blue-500/50 dark:focus:border-blue-500/50 focus:ring-1 focus:ring-blue-500/50 dark:focus:ring-blue-500/50 transition-all duration-200"
                    autocomplete="off" />
            </div>
        </div>

        <ul class="space-y-1.5" role="menu">
            @foreach (config('menus.admin.sidebar', []) as $item)
                @if ($item['type'] === 'link')
                    <li x-show="!query || ($el.innerText||'').toLowerCase().includes(query.toLowerCase())">
                        <a href="{{ isset($item['route']) && \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route']) : url($item['url'] ?? '#') }}"
                            class="group flex items-center gap-3.5 h-11 px-3.5 rounded-xl text-sm font-medium transition-all duration-200
                            {{ isset($item['route']) && request()->routeIs($item['route'])
                                ? 'bg-gradient-to-r from-blue-600 to-blue-500 text-white shadow-lg shadow-blue-900/40 border border-blue-400/20'
                                : 'text-slate-300 dark:text-slate-300 hover:text-white dark:hover:text-white hover:bg-slate-800/45 dark:hover:bg-slate-800/60 hover:translate-x-1 dark:hover:translate-x-1' }}"
                            role="menuitem">
                            <span class="flex-shrink-0 transition-transform duration-200 {{ isset($item['route']) && request()->routeIs($item['route']) ? 'text-white' : 'group-hover:text-blue-400 group-hover:scale-110' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    {!! config('menus.icons.' . $item['icon'], '') !!}
                                </svg>
                            </span>
                            <span class="flex-1 truncate">{{ $item['name'] }}</span>

                            @if (isset($item['badge']))
                                <span class="flex-shrink-0 ml-auto text-[10px] font-bold bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded-full border border-emerald-500/20 shadow-sm shadow-emerald-500/10 dark:shadow-none">
                                    {{ $item['badge'] }}
                                </span>
                            @endif
                        </a>
                    </li>
                @elseif($item['type'] === 'group')
                    <li x-show="!query || ($el.innerText||'').toLowerCase().includes(query.toLowerCase())"
                        class="group"
                        x-data="{ open: {{ collect($item['children'])->contains(fn($c) => isset($c['route']) && request()->routeIs($c['route'] . '*')) ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open"
                            class="flex items-center gap-3.5 h-11 px-3.5 w-full rounded-xl text-sm font-medium transition-all duration-200 text-slate-300 dark:text-slate-300 hover:text-white dark:hover:text-white hover:bg-slate-800/45 dark:hover:bg-slate-800/60"
                            :class="{ 'bg-slate-800/40 dark:bg-slate-800/55 text-white dark:text-white': open }"
                            :aria-expanded="open">
                            <span class="flex-shrink-0 transition-colors duration-200" :class="{ 'text-blue-400': open, 'group-hover:text-blue-400': !open }">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    {!! config('menus.icons.' . $item['icon'], '') !!}
                                </svg>
                            </span>
                            <span class="flex-1 text-left truncate">{{ $item['name'] }}</span>
                            <svg class="w-4 h-4 ml-auto text-slate-500 transition-transform duration-200" :class="{ 'rotate-180 text-white': open, 'group-hover:text-white': !open }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="open" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="relative mt-2 space-y-1 pl-3">
                            <!-- Connection Line -->
                            <div class="absolute left-[22px] top-0 bottom-4 w-px bg-gradient-to-b from-white/10 to-transparent"></div>

                            @foreach ($item['children'] as $child)
                                <a href="{{ isset($child['route']) && \Illuminate\Support\Facades\Route::has($child['route']) ? route($child['route']) : url($child['url'] ?? '#') }}"
                                    class="relative flex items-center gap-3 h-9 pl-9 pr-3 rounded-lg text-sm font-medium transition-all duration-200
                                    {{ isset($child['route']) && request()->routeIs($child['route'] . '*')
                                        ? 'text-white'
                                        : 'text-slate-300 dark:text-slate-300 hover:text-white dark:hover:text-white hover:translate-x-1' }}"
                                    role="menuitem">

                                    <!-- Dot Indicator -->
                                    <div class="absolute left-[19px] top-1/2 -translate-y-1/2 w-1.5 h-1.5 rounded-full border transition-all duration-200
                                        {{ isset($child['route']) && request()->routeIs($child['route'] . '*')
                                            ? 'bg-blue-500 border-blue-400 ring-2 ring-blue-500/20'
                                            : 'bg-slate-700 border-slate-600 group-hover:bg-slate-500 group-hover:border-slate-400' }}"></div>

                                    <span class="truncate {{ isset($child['route']) && request()->routeIs($child['route'] . '*') ? 'text-blue-100' : '' }}">
                                        {{ $child['name'] }}
                                    </span>

                                    @if (isset($child['badge']))
                                      <span class="ml-auto text-[10px] font-bold bg-slate-700/35 dark:bg-slate-800/50 text-slate-200 dark:text-slate-200 px-1.5 py-0.5 rounded border border-slate-600/40 dark:border-slate-600/60">
                                            {{ $child['badge'] }}
                                        </span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </li>
                @endif
            @endforeach
        </ul>
    </nav>

    <!-- Footer User Info -->
    <div class="mt-auto p-4 border-t border-white/5 bg-[#080d19]">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-xs ring-2 ring-white/10">
                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                <p class="text-xs text-slate-500 truncate">Süper Yönetici</p>
            </div>
        </div>
    </div>
</aside>
