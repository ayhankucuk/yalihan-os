@extends('admin.layouts.admin')

@section('content')
    <div
        class="min-h-screen bg-gray-50 dark:bg-[#0f172a] text-slate-800 dark:text-slate-200 px-4 py-8 overflow-hidden relative transition-colors duration-300 dark:bg-slate-900">
        <!-- Animated background glowing orbs (dark mode only) -->
        <div
            class="hidden dark:block absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-blue-600/10 rounded-full blur-[120px] animate-pulse">
        </div>
        <div class="hidden dark:block absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-indigo-600/10 rounded-full blur-[120px] animate-pulse"
            style="animation-delay: 2s;"></div>
        <div class="hidden dark:block absolute top-[50%] left-[50%] w-[30%] h-[30%] bg-emerald-600/5 rounded-full blur-[100px] animate-pulse"
            style="animation-delay: 4s;"></div>

        <div class="max-w-7xl mx-auto relative z-10 transition-all duration-700 animate-slide-in-bottom">
            <!-- Header Section with Progress Circle -->
            <div class="mb-12 flex flex-col lg:flex-row lg:items-center justify-between gap-8">
                <div class="flex items-center gap-8">
                    <!-- ★ PROGRESS CIRCLE - Health Score -->
                    <div class="relative shrink-0">
                        <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 120 120">
                            <!-- Background circle -->
                            <circle cx="60" cy="60" r="52" fill="none"
                                class="stroke-gray-200 dark:stroke-slate-700" stroke-width="8" />
                            <!-- Progress circle -->
                            <circle cx="60" cy="60" r="52" fill="none" stroke="url(#progressGradient)"
                                stroke-width="8" stroke-linecap="round" stroke-dasharray="{{ 2 * 3.14159 * 52 }}"
                                stroke-dashoffset="{{ 2 * 3.14159 * 52 * (1 - $healthScore / 100) }}"
                                class="transition-all duration-1000 {{ $healthScore == 100 ? 'animate-pulse-glow' : '' }}" />
                            <defs>
                                <linearGradient id="progressGradient" x1="0%" y1="0%" x2="100%"
                                    y2="0%">
                                    <stop offset="0%" stop-color="#10b981" />
                                    <stop offset="50%" stop-color="#06b6d4" />
                                    <stop offset="100%" stop-color="#3b82f6" />
                                </linearGradient>
                            </defs>
                        </svg>
                        <!-- Center text -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span
                                class="text-3xl font-black text-slate-900 dark:text-white {{ $healthScore == 100 ? 'text-emerald-600 dark:text-emerald-400' : '' }}">
                                {{ $stats['healthy_combinations'] }}
                            </span>
                            <span class="text-xs text-slate-500 font-bold">/{{ $stats['total_combinations'] }}</span>
                            @if ($healthScore == 100)
                                <span
                                    class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold uppercase mt-1 animate-pulse">Mükemmel!</span>
                            @endif
                        </div>
                        <!-- Glow effect for 100% -->
                        @if ($healthScore == 100)
                            <div class="absolute inset-0 rounded-full bg-emerald-500/20 blur-xl animate-pulse"></div>
                        @endif
                    </div>

                    <div class="space-y-2">
                        <div
                            class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 text-blue-600 dark:text-blue-400 text-xs font-bold uppercase tracking-widest">
                            <span class="relative flex h-2 w-2">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                            </span>
                            Sistem Tanılaması
                        </div>
                        <h1 class="text-4xl lg:text-5xl font-black tracking-tight text-slate-900 dark:text-white">
                            UPS <span
                                class="bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500 bg-clip-text text-transparent">Sağlık
                                Matrisi</span>
                        </h1>
                        <p class="text-slate-600 dark:text-slate-400 text-lg max-w-2xl">
                            Emlak şablonları ve özellik atamalarının gerçek zamanlı senkronizasyon haritası.
                        </p>
                    </div>
                </div>

                <form action="{{ route('admin.ups.health.repair') }}" method="POST" class="shrink-0">
                    @csrf
                    <button type="submit"
                        class="group relative inline-flex items-center gap-3 px-8 py-4 bg-blue-600 dark:bg-slate-100 text-white dark:text-slate-950 font-bold rounded-2xl shadow-lg dark:shadow-[0_0_20px_rgba(255,255,255,0.3)] hover:shadow-xl dark:hover:shadow-[0_0_30px_rgba(255,255,255,0.5)] transform hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-100 dark:to-white opacity-0 group-hover:opacity-100 transition-opacity">
                        </div>
                        <i
                            class="fas fa-bolt text-white dark:text-blue-600 group-hover:rotate-12 transition-transform relative z-10"></i>
                        <span class="relative z-10">Matrisi Optimize Et</span>
                    </button>
                    <p class="text-xs text-center mt-2 text-slate-500 dark:text-slate-400">
                        Eksik şablonları otomatik oluşturur
                    </p>
                </form>
            </div>

            <!-- Global Stats with Glassmorphism -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-12">
                @php
                    $statItems = [
                        [
                            'label' => 'Toplam Düğüm',
                            'value' => $stats['total_combinations'],
                            'icon' => 'fa-network-wired',
                            'gradient' => 'from-blue-500 to-indigo-500',
                        ],
                        [
                            'label' => 'Sağlıklı',
                            'value' => $stats['healthy_combinations'],
                            'icon' => 'fa-shield-check',
                            'gradient' => 'from-emerald-500 to-teal-500',
                        ],
                        [
                            'label' => 'Eksik Şablon',
                            'value' => $stats['missing_templates'],
                            'icon' => 'fa-ghost',
                            'gradient' => 'from-rose-500 to-pink-500',
                        ],
                        [
                            'label' => 'Özellik Atanmamış',
                            'value' => $stats['missing_features'],
                            'icon' => 'fa-database',
                            'gradient' => 'from-amber-500 to-orange-500',
                        ],
                    ];
                @endphp

                @foreach ($statItems as $index => $stat)
                    <div class="group relative bg-white dark:bg-slate-900/60 backdrop-blur-xl rounded-2xl p-5 border border-gray-200 dark:border-slate-700/50 hover:border-gray-300 dark:hover:border-slate-600/50 shadow-sm hover:shadow-md transition-all duration-300 animate-staggered-fade dark:shadow-none"
                        style="animation-delay: {{ $index * 100 }}ms">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $stat['gradient'] }} flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform duration-300">
                                <i class="fas {{ $stat['icon'] }} text-white text-lg"></i>
                            </div>
                            <div>
                                <div class="text-2xl font-black text-slate-900 dark:text-white">{{ $stat['value'] }}</div>
                                <div class="text-xs text-slate-500 font-bold uppercase tracking-wider">{{ $stat['label'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- ★ HEATMAP GRID: Category x Publication Type Matrix -->
            <div class="relative group mb-12">
                <div
                    class="hidden dark:block absolute -inset-1 bg-gradient-to-r from-blue-500/20 via-indigo-500/20 to-purple-500/20 rounded-[2rem] blur-2xl opacity-50 group-hover:opacity-100 transition duration-1000">
                </div>

                <div
                    class="relative bg-white dark:bg-slate-900/60 backdrop-blur-3xl rounded-2xl lg:rounded-[2rem] border border-gray-200 dark:border-slate-700/50 overflow-hidden shadow-sm dark:shadow-2xl">
                    <div
                        class="px-6 lg:px-8 py-6 border-b border-gray-200 dark:border-slate-700/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-4">
                            <div class="w-1.5 h-8 bg-gradient-to-b from-blue-500 to-indigo-500 rounded-full"></div>
                            Şablon Isı Haritası
                            <span class="text-xs text-slate-500 font-normal">(Kategori × Yayın Tipi)</span>
                        </h2>
                        <div class="flex items-center gap-4 text-xs">
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded-lg bg-emerald-500 shadow-sm dark:shadow-[0_0_8px_#10b981]">
                                </div>
                                <span class="text-slate-600 dark:text-slate-400">Sağlıklı</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded-lg bg-amber-500 shadow-sm dark:shadow-[0_0_8px_#f59e0b]"></div>
                                <span class="text-slate-600 dark:text-slate-400">Uyarı</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded-lg bg-rose-500 shadow-sm dark:shadow-[0_0_8px_#f43f5e]"></div>
                                <span class="text-slate-600 dark:text-slate-400">Eksik</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-4 h-4 rounded-lg bg-gray-200 dark:bg-slate-700 border border-gray-300 dark:border-slate-600 shadow-none dark:shadow-none">
                                </div>
                                <span class="text-slate-600 dark:text-slate-400">Yok</span>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto p-4 lg:p-6">
                        <table class="w-full border-separate border-spacing-1">
                            <thead>
                                <tr>
                                    <th
                                        class="text-left text-xs text-slate-600 dark:text-slate-500 font-bold uppercase tracking-wider pb-4 pr-4 sticky left-0 bg-white dark:bg-slate-900 bg-opacity-90 dark:bg-opacity-90 backdrop-blur-sm z-10 w-48">
                                        Kategori
                                    </th>
                                    @foreach ($allYayinTipleri as $yayinTipi)
                                        <th
                                            class="text-center text-[10px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider pb-4 px-2 min-w-[60px]">
                                            <span class="writing-mode-vertical">{{ Str::limit($yayinTipi, 12) }}</span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($heatmapData as $row)
                                    <tr class="group/row">
                                        <td
                                            class="py-2 pr-4 sticky left-0 bg-white dark:bg-slate-900 backdrop-blur-sm z-10 border-r border-gray-100 dark:border-slate-800">
                                            <div class="flex items-center gap-2">
                                                <div
                                                    class="w-6 h-6 rounded bg-gray-100 dark:bg-slate-800 flex items-center justify-center dark:bg-slate-900">
                                                    <i class="fas fa-folder text-slate-400 dark:text-slate-500 text-xs"></i>
                                                </div>
                                                <span
                                                    class="text-sm text-slate-700 dark:text-slate-300 font-medium group-hover/row:text-slate-900 dark:group-hover/row:text-white transition-colors truncate max-w-[150px]" title="{{ $row['category_name'] }}">
                                                    {{ Str::limit($row['category_name'], 20) }}
                                                </span>
                                            </div>
                                        </td>
                                        @foreach ($allYayinTipleri as $yayinTipi)
                                            @php
                                                $cell = $row['cells'][$yayinTipi] ?? [
                                                    'exists' => false,
                                                    'health_state' => 'empty',
                                                ];
                                            @endphp
                                            <td class="py-1 px-1 text-center">
                                                @if (!$cell['exists'])
                                                    <!-- Empty: No combination -->
                                                    <div
                                                        class="w-full h-8 rounded-lg bg-gray-100 dark:bg-slate-800/50 border border-gray-200 dark:border-slate-700/30 flex items-center justify-center opacity-30 dark:bg-slate-900">
                                                        <span class="text-gray-400 dark:text-slate-600 text-xs">—</span>
                                                    </div>
                                                @elseif($cell['health_state'] == 'healthy')
                                                    <!-- Healthy -->
                                                    <a href="{{ isset($cell['template_id']) ? route('admin.property-hub.templates.edit', $cell['template_id']) : '#' }}"
                                                        class="w-full h-8 rounded-lg bg-emerald-100 dark:bg-emerald-500/20 border border-emerald-300 dark:border-emerald-500/50 flex items-center justify-center hover:scale-110 hover:shadow-[0_0_15px_#10b981] transition-all duration-300 cursor-pointer group/cell"
                                                        title="{{ $row['category_name'] }} - {{ $yayinTipi }}: {{ $cell['feature_count'] ?? 0 }} özellik">
                                                        <span
                                                            class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 group-hover/cell:scale-110">{{ $cell['feature_count'] }}</span>
                                                    </a>
                                                @elseif($cell['health_state'] == 'missing_template')
                                                    <!-- Missing Template -->
                                                    <div class="w-full h-8 rounded-lg bg-rose-100 dark:bg-rose-500/20 border border-rose-300 dark:border-rose-500/50 flex items-center justify-center hover:scale-110 hover:shadow-[0_0_15px_#f43f5e] transition-all duration-300 cursor-pointer animate-pulse"
                                                        title="{{ $row['category_name'] }} - {{ $yayinTipi }}: Şablon eksik!">
                                                        <i
                                                            class="fas fa-times text-rose-600 dark:text-rose-400 text-xs"></i>
                                                    </div>
                                                @elseif($cell['health_state'] == 'partial_empty')
                                                    <!-- Partial Empty (Template exists but incomplete) -->
                                                    <a href="{{ isset($cell['template_id']) ? route('admin.property-hub.templates.edit', $cell['template_id']) : '#' }}"
                                                        class="w-full h-8 rounded-lg bg-orange-100 dark:bg-orange-500/20 border border-orange-300 dark:border-orange-500/50 flex items-center justify-center hover:scale-110 hover:shadow-[0_0_15px_#f97316] transition-all duration-300 cursor-pointer"
                                                        title="{{ $row['category_name'] }} - {{ $yayinTipi }}: Şablon var ama UI ipuçları boş!">
                                                        <i
                                                            class="fas fa-battery-half text-orange-600 dark:text-orange-400 text-xs"></i>
                                                    </a>
                                                @else
                                                    <!-- Warning: No features (Whitelist Drop) -->
                                                    <a href="{{ isset($cell['template_id']) ? route('admin.property-hub.templates.edit', $cell['template_id']) : '#' }}"
                                                        class="w-full h-8 rounded-lg bg-amber-100 dark:bg-amber-500/20 border border-amber-300 dark:border-amber-500/50 flex items-center justify-center hover:scale-110 hover:shadow-[0_0_15px_#f59e0b] transition-all duration-300 cursor-pointer"
                                                        title="{{ $row['category_name'] }} - {{ $yayinTipi }}: Özellik tanımlanmamış">
                                                        <i
                                                            class="fas fa-exclamation text-amber-600 dark:text-amber-400 text-xs"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ★ TERMINAL LOG PANEL -->
            <div class="relative group mb-12">
                <div
                    class="hidden dark:block absolute -inset-1 bg-gradient-to-r from-slate-800/50 to-slate-900/50 rounded-2xl blur-xl opacity-50">
                </div>

                <div
                    class="relative bg-slate-900 dark:bg-black/90 backdrop-blur-xl rounded-2xl border border-slate-700 dark:border-slate-700/50 overflow-hidden shadow-xl dark:shadow-2xl">
                    <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex gap-2">
                                <div class="w-3 h-3 rounded-full bg-rose-500"></div>
                                <div class="w-3 h-3 rounded-full bg-amber-500"></div>
                                <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                            </div>
                            <span class="text-slate-400 text-sm font-mono">ups-saglik-monitoru.log</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="relative flex h-2 w-2">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span class="text-emerald-400 text-xs font-mono">CANLI</span>
                        </div>
                    </div>

                    <div class="p-6 font-mono text-sm max-h-64 overflow-y-auto custom-scrollbar">
                        <div class="space-y-2">
                            <!-- System boot message -->
                            <div class="text-slate-500">
                                <span class="text-emerald-500">[{{ now()->subMinutes(5)->format('H:i:s') }}]</span>
                                <span class="text-blue-400">SİSTEM</span>
                                <span class="text-slate-400">UPS Sağlık Monitörü başlatıldı</span>
                            </div>
                            <div class="text-slate-500">
                                <span class="text-emerald-500">[{{ now()->subMinutes(4)->format('H:i:s') }}]</span>
                                <span class="text-cyan-400">TARAMA</span>
                                <span class="text-slate-400">Bütünlük taraması başlatılıyor... {{ $stats['total_combinations'] }}
                                    kombinasyon bulundu</span>
                            </div>

                            @if ($stats['healthy_combinations'] == $stats['total_combinations'])
                                <div class="text-slate-500">
                                    <span class="text-emerald-500">[{{ now()->subMinutes(2)->format('H:i:s') }}]</span>
                                    <span class="text-emerald-400">✓ BAŞARILI</span>
                                    <span class="text-emerald-300">Tüm {{ $stats['total_combinations'] }} şablon
                                        sağlıklı!</span>
                                </div>
                            @else
                                @if ($stats['missing_templates'] > 0)
                                    <div class="text-slate-500">
                                        <span
                                            class="text-emerald-500">[{{ now()->subMinutes(3)->format('H:i:s') }}]</span>
                                        <span class="text-rose-400">⚠ UYARI</span>
                                        <span class="text-rose-300">{{ $stats['missing_templates'] }} eksik şablon
                                            tespit edildi</span>
                                    </div>
                                @endif
                                @if ($stats['missing_features'] > 0)
                                    <div class="text-slate-500">
                                        <span
                                            class="text-emerald-500">[{{ now()->subMinutes(2)->format('H:i:s') }}]</span>
                                        <span class="text-amber-400">⚡ DİKKAT</span>
                                        <span class="text-amber-300">{{ $stats['missing_features'] }} şablonda özellik
                                            ataması yok</span>
                                    </div>
                                @endif
                            @endif

                            <!-- Repair logs from cache -->
                            @forelse($repairLogs ?? [] as $log)
                                <div class="text-slate-500">
                                    <span
                                        class="text-emerald-500">[{{ $log['timestamp'] ?? now()->format('H:i:s') }}]</span>
                                    <span
                                        class="{{ ($log['action'] ?? '') === 'ONARIM_TAMAMLANDI' ? 'text-emerald-400' : 'text-rose-400' }}">
                                        {{ $log['action'] ?? 'İŞLEM' }}
                                    </span>
                                    <span class="text-slate-400">{{ $log['message'] ?? '' }}</span>
                                    @if (isset($log['user']))
                                        <span class="text-slate-600">kullanıcı: {{ $log['user'] }}</span>
                                    @endif
                                </div>
                            @empty
                                <div class="text-slate-500">
                                    <span class="text-emerald-500">[{{ now()->subMinutes(1)->format('H:i:s') }}]</span>
                                    <span class="text-slate-600">BİLGİ</span>
                                    <span class="text-slate-500">Son zamanlarda onarım işlemi yok</span>
                                </div>
                            @endforelse

                            <!-- Current timestamp -->
                            <div class="text-slate-500">
                                <span class="text-emerald-500">[{{ now()->format('H:i:s') }}]</span>
                                <span class="text-blue-400">HAZIR</span>
                                <span class="text-slate-400">Komut bekleniyor...</span>
                                <span class="animate-pulse text-emerald-400">▌</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer: Neural Meta Info -->
            <div
                class="flex flex-col md:flex-row items-center justify-between gap-6 px-6 lg:px-8 py-6 bg-white dark:bg-slate-900/40 backdrop-blur-md rounded-2xl lg:rounded-3xl border border-gray-200 dark:border-slate-800/50 shadow-sm dark:shadow-none dark:border-slate-700">
                <div class="flex items-center gap-4">
                    <div
                        class="w-10 h-10 rounded-full bg-gray-100 dark:bg-slate-800 flex items-center justify-center text-blue-500 dark:text-blue-400 dark:bg-slate-900">
                        <i class="fas fa-brain"></i>
                    </div>
                    <div class="text-sm">
                        <div class="text-slate-800 dark:text-slate-200 font-bold">Cortex Senkronizasyon Motoru v2.5</div>
                        <div class="text-slate-500 font-medium">Son tarama: {{ now()->diffForHumans() }}</div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <div
                        class="px-4 py-2 bg-gray-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold rounded-lg border border-gray-200 dark:border-slate-700 dark:bg-slate-900">
                        Skor: {{ $healthScore }}%
                    </div>
                    @if ($healthScore == 100)
                        <div
                            class="px-4 py-2 bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 text-xs font-bold rounded-lg border border-emerald-200 dark:border-emerald-500/20 animate-pulse">
                            ★ Mükemmel Sağlık
                        </div>
                    @elseif($healthScore >= 80)
                        <div
                            class="px-4 py-2 bg-blue-100 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 text-xs font-bold rounded-lg border border-blue-200 dark:border-blue-500/20">
                            Sistem Stabil
                        </div>
                    @else
                        <div
                            class="px-4 py-2 bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 text-xs font-bold rounded-lg border border-amber-200 dark:border-amber-500/20">
                            İlgi Gerektiriyor
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes slide-in-bottom {
            0% {
                transform: translateY(30px);
                opacity: 0;
            }

            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes staggered-fade {
            0% {
                transform: scale(0.95);
                opacity: 0;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes pulse-glow {

            0%,
            100% {
                filter: drop-shadow(0 0 8px rgba(16, 185, 129, 0.5));
            }

            50% {
                filter: drop-shadow(0 0 20px rgba(16, 185, 129, 0.8));
            }
        }

        .animate-slide-in-bottom {
            animation: slide-in-bottom 0.8s cubic-bezier(0.165, 0.84, 0.44, 1) forwards;
        }

        .animate-staggered-fade {
            animation: staggered-fade 0.5s ease-out forwards;
            opacity: 0;
        }

        .animate-pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }

        .writing-mode-vertical {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            transform: rotate(180deg);
        }

        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #1e293b;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }

        /* Light mode scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Dark mode scrollbar */
        .dark ::-webkit-scrollbar-track {
            background: #0f172a;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #1e293b;
            border: 2px solid #0f172a;
        }

        .dark ::-webkit-scrollbar-thumb:hover {
            background: #334155;
        }
    </style>
@endsection
