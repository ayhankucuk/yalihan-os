@extends('admin.layouts.admin')

@section('title', 'AI Monitoring Dashboard')

@section('content')
    <div class="container mx-auto p-6" x-data="monitorUI()" x-init="init()">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold">AI Monitoring</h1>
                <span class="text-sm text-gray-500">(Yerel Geliştirici İzleme)</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-sm">Genel Aktiflik Durumu:</span>
                    <span :class="overallBadgeClass()"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-all duration-300">
                        <span x-show="overall?.level === 'green'">🟢 İyi</span>
                        <span x-show="overall?.level === 'yellow'">🟡 Uyarı</span>
                        <span x-show="overall?.level === 'red'">🔴 Kritik</span>
                        <span x-show="!overall?.level || overall?.level === 'unknown'">⚪ Bilinmiyor</span>
                    </span>
                </div>
                <div class="hidden md:flex items-center gap-2">
                    <label class="text-xs flex items-center gap-1">
                        <input type="checkbox" x-model="autoRefresh"
                            class="w-4 h-4 text-blue-600 bg-gray-50 dark:bg-slate-900 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all duration-200 cursor-pointer"
                            aria-label="Otomatik yenilemeyi aç/kapat" />
                        Otomatik
                    </label>
                    <select style="color-scheme: light dark;" x-model.number="refreshInterval"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 h-7 text-xs transition-all duration-200 dark:text-slate-100">
                        <option :value="15000">15s</option>
                        <option :value="30000">30s</option>
                        <option :value="60000">60s</option>
                    </select>
                    <span class="text-xs text-gray-500" x-show="lastUpdated" x-text="'Son: ' + lastUpdated"></span>
                </div>
                <button @click="refreshAll()"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg text-xs dark:bg-blue-700 dark:hover:bg-blue-800 dark:shadow-none"
                    aria-label="Tüm verileri yenile">
                    Yenile
                </button>
            </div>
        </div>

        <!-- Overview + Mini Usage Chart -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div
                class="rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-all duration-200 dark:border-slate-800 dark:bg-slate-900 p-4 lg:col-span-2 dark:shadow-none dark:border-slate-700">
                <div class="grid grid-cols-4 gap-4">
                    <div
                        class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-3 transition-all duration-300 hover:shadow-md">
                        <div class="text-xs text-blue-600 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                            </svg>
                            Aktif MCP
                        </div>
                        <div class="text-2xl font-semibold text-blue-800" x-text="overall?.mcp_count ?? 0">
                        </div>
                    </div>
                    <div
                        class="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-3 transition-all duration-300 hover:shadow-md">
                        <div class="text-xs text-green-600 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                            API OK
                        </div>
                        <div class="text-2xl font-semibold text-green-800" x-text="overall?.api_ok ?? 0"></div>
                    </div>
                    <div
                        class="bg-gradient-to-r from-purple-50 to-purple-100 rounded-lg p-3 transition-all duration-300 hover:shadow-md">
                        <div class="text-xs text-purple-600 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd" />
                            </svg>
                            Uptime %
                        </div>
                        <div class="text-2xl font-semibold text-purple-800" x-text="uptimePercent() + '%'">
                        </div>
                    </div>
                    <div
                        class="bg-gradient-to-r from-orange-50 to-orange-100 rounded-lg p-3 transition-all duration-300 hover:shadow-md">
                        <div class="text-xs text-orange-600 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                    clip-rule="evenodd" />
                            </svg>
                            Ort. Latency
                        </div>
                        <div class="text-2xl font-semibold text-orange-800" x-text="avgLatency() + 'ms'"></div>
                    </div>
                </div>

                <!-- Finansal Metrikler (Cortex ROI & Cost) -->
                <div class="grid grid-cols-3 gap-4 mt-4 border-t border-gray-100 pt-4 dark:border-slate-800">
                    <div
                        class="bg-gradient-to-r from-red-50 to-red-100 rounded-lg p-3 transition-all duration-300 hover:shadow-md">
                        <div class="text-xs text-red-600 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m.599-1a1.994 1.994 0 01-3.598 0M9 15h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            AI Operasyonel Maliyet (7G)
                        </div>
                        <div class="text-2xl font-semibold text-red-800" x-text="'$' + (totalCost || 0).toFixed(4)"></div>
                    </div>
                    <div
                        class="bg-gradient-to-r from-emerald-50 to-emerald-100 rounded-lg p-3 transition-all duration-300 hover:shadow-md">
                        <div class="text-xs text-emerald-600 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                            Cortex ROI Etkisi (Ort.)
                        </div>
                        <div class="text-2xl font-semibold text-emerald-800"
                            x-text="'%' + (roiSummary?.avg_roi || 0).toFixed(1)"></div>
                    </div>
                    <div
                        class="bg-gradient-to-r from-amber-50 to-amber-100 rounded-lg p-3 transition-all duration-300 hover:shadow-md">
                        <div class="text-xs text-amber-600 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Yüksek ROI'li Fırsatlar
                        </div>
                        <div class="text-2xl font-semibold text-amber-800" x-text="roiSummary?.high_roi_count || 0"></div>
                    </div>
                </div>
            </div>
            <div
                class="rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-all duration-200 dark:border-slate-800 dark:bg-slate-900 p-4 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="font-semibold">MCP Kullanım Mini-Chart</h2>
                    <button @click="refreshMcp()"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 text-xs dark:text-slate-300"
                        aria-label="MCP kullanımını yenile">
                        Yenile
                    </button>
                </div>
                <div class="space-y-2" x-show="overall?.mcp_usage">
                    <template x-for="[key, count] in sortedUsage()" :key="key">
                        <div>
                            <div class="flex justify-between text-xs text-gray-600">
                                <span class="font-mono" x-text="key"></span>
                                <span x-text="count"></span>
                            </div>
                            <div class="w-full h-2 bg-gray-200 rounded-lg">
                                <div class="h-2 rounded-lg" :class="mcpTypeColor(key)"
                                    :style="{ width: usageWidth(count) }">
                                </div>
                            </div>
                        </div>
                    </template>
                    <div x-show="!overall?.mcp_usage" class="text-xs text-gray-400">Veri yok</div>
                </div>
            </div>
        </div>

        <!-- MCP + API -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- MCP Table -->
            <div
                class="rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-all duration-200 dark:border-slate-800 dark:bg-slate-900 p-4 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="font-semibold">MCP Server Aktiflik Durumu</h2>
                    <button @click="refreshMcp()"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 text-xs dark:text-slate-300"
                        aria-label="MCP kullanımını yenile">
                        Yenile
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <!-- Skeleton Loader -->
                    <div x-show="loadingMcp" class="space-y-2 mb-2">
                        <div class="animate-pulse h-6 bg-gray-100 rounded-lg dark:bg-slate-900"></div>
                        <div class="animate-pulse h-6 bg-gray-100 rounded-lg dark:bg-slate-900"></div>
                        <div class="animate-pulse h-6 bg-gray-100 rounded-lg dark:bg-slate-900"></div>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 w-full text-xs">
                        <thead>
                            <tr>
                                <th>Kullanıcı</th>
                                <th>PID</th>
                                <th>CPU</th>
                                <th>MEM</th>
                                <th>Komut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="mcp.length === 0">
                                <tr>
                                    <td colspan="5" class="text-center text-gray-400">Kayıt yok</td>
                                </tr>
                            </template>
                            <template x-for="proc in mcp" :key="proc.pid">
                                <tr>
                                    <td x-text="proc.user"></td>
                                    <td x-text="proc.pid"></td>
                                    <td x-text="proc.cpu"></td>
                                    <td x-text="proc.mem"></td>
                                    <td class="break-all" x-text="proc.command"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- API Health -->
            <div
                class="rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-all duration-200 dark:border-slate-800 dark:bg-slate-900 p-4 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="font-semibold">API Health Check</h2>
                    <button @click="refreshApis()"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 text-xs dark:text-slate-300"
                        aria-label="API durumunu yenile">
                        Yenile
                    </button>
                </div>
                <ul class="space-y-2">
                    <template x-for="(aktiflik_durumu, name) in apis" :key="name">
                        <li
                            class="flex items-center justify-between text-sm bg-gray-50 dark:bg-gray-700 rounded-lg px-2 py-1 dark:bg-slate-900">
                            <span class="font-mono dark:text-slate-200" x-text="name"></span>
                            <div class="flex items-center gap-2">
                                <span :class="statusBadgeClass(aktiflik_durumu?.aktiflik_durumu)" x-text="aktiflik_durumu?.aktiflik_durumu || 'UNKNOWN'"></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-show="aktiflik_durumu?.latency_ms"
                                    x-text="(aktiflik_durumu?.latency_ms || '') + 'ms'"></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-show="aktiflik_durumu?.http_code"
                                    x-text="'HTTP ' + (aktiflik_durumu?.http_code || '')"></span>
                            </div>
                        </li>
                    </template>
                    <li x-show="Object.keys(apis).length===0" class="text-center text-gray-400 text-sm">Veri
                        yok
                    </li>
                </ul>
            </div>
        </div>

        <!-- Ekosistem Analizi -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            <!-- Duplike Dosyalar -->
            <div
                class="rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-all duration-200 dark:border-slate-800 dark:bg-slate-900 p-4 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="font-semibold flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 2a1 1 0 000 2h2a1 1 0 100-2H8z" />
                            <path
                                d="M3 5a2 2 0 012-2 3 3 0 003 3h6a3 3 0 003-3 2 2 0 012 2v6h-4.586l1.293-1.293a1 1 0 00-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L10.414 13H15v3a2 2 0 01-2 2H5a2 2 0 01-2-2V5zM15 11.586l-1.293 1.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 00-1.414 1.414L15 8.414V11.586z" />
                        </svg>
                        Duplike Dosyalar
                    </h2>
                    <button @click="refreshDuplicates()"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 text-xs dark:text-slate-300"
                        aria-label="Duplike dosyaları yenile">
                        Yenile
                    </button>
                </div>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    <template x-for="dup in duplicateFiles" :key="dup.name">
                        <div class="text-xs bg-yellow-50 rounded-lg p-2">
                            <div class="font-semibold text-yellow-800">@{{ dup.name }}</div>
                            <div class="text-yellow-600">@{{ dup.count }} dosya</div>
                        </div>
                    </template>
                    <div x-show="duplicateFiles.length === 0" class="text-xs text-gray-400">Duplike dosya yok
                    </div>
                </div>
            </div>

            <!-- Çakışan Rotalar -->
            <div
                class="rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-all duration-200 dark:border-slate-800 dark:bg-slate-900 p-4 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="font-semibold flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                        Rota Çakışmaları
                    </h2>
                    <button @click="refreshConflicts()"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 text-xs dark:text-slate-300"
                        aria-label="Rota çakışmalarını yenile">
                        Yenile
                    </button>
                </div>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    <template x-for="conflict in conflictingRoutes" :key="conflict.uri_methods">
                        <div class="text-xs bg-red-50 rounded-lg p-2">
                            <div class="font-semibold text-red-800">@{{ conflict.uri_methods }}</div>
                            <div class="text-red-600">@{{ conflict.count }} çakışma</div>
                        </div>
                    </template>
                    <div x-show="conflictingRoutes.length === 0" class="text-xs text-gray-400">Çakışma yok</div>
                </div>
            </div>

            <!-- Sayfa Sağlığı -->
            <div
                class="rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-all duration-200 dark:border-slate-800 dark:bg-slate-900 p-4 lg:col-span-3 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="font-semibold flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-9V7a1 1 0 112 0v2h2a1 1 0 110 2h-2v2a1 1 0 11-2 0v-2H7a1 1 0 110-2h2z"
                                clip-rule="evenodd" />
                        </svg>
                        Sayfa Sağlığı
                    </h2>
                    <button @click="refreshPagesHealth()"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 text-xs dark:text-slate-300"
                        aria-label="Sayfa sağlığını yenile">
                        Yenile
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 w-full text-xs">
                        <thead>
                            <tr>
                                <th>Sayfa</th>
                                <th>URL</th>
                                <th>Aktiflik Durumu</th>
                                <th>HTTP</th>
                                <th>Latency</th>
                                <th>İşaretler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in pagesHealth" :key="row.url">
                                <tr>
                                    <td class="font-semibold">@{{ row.name }}</td>
                                    <td class="font-mono text-[11px] max-w-[360px] truncate" :title="row.url">
                                        @{{ row.url }}</td>
                                    <td><span :class="statusBadgeClass(row.aktiflik_durumu)">@{{ row.aktiflik_durumu }}</span></td>
                                    <td>@{{ row.http_code ?? '—' }}</td>
                                    <td>@{{ row.latency_ms ?? '—' }}ms</td>
                                    <td>
                                        <span x-show="row.markers_found"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-200 text-green-800 mr-1">Bulundu</span>
                                        <span x-show="!row.markers_found"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-200 text-yellow-800 mr-1">Eksik</span>
                                        <template x-for="m in (row.missing_markers || [])" :key="m">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-800 mr-1 dark:text-slate-200">@{{ m }}</span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="pagesHealth.length === 0">
                                <td colspan="6" class="text-center text-gray-400">Kayıt yok</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Logs -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <!-- Logs -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div
                    class="rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-all duration-200 dark:border-slate-800 dark:bg-slate-900 p-4 dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="font-semibold">Self-Healing Log (Son 10)</h2>
                        <div class="flex items-center gap-2">
                            <input x-model.trim="filterText" type="text" placeholder="Filtrele..."
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 h-8 text-xs dark:text-slate-100"
                                maxlength="60" pattern="[a-zA-ZğüşıöçĞÜŞİÖÇ0-9\s\-_]+"
                                title="Sadece harf, rakam ve temel karakterler kullanın" />
                            <button @click="refreshSelf()"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 text-xs dark:text-slate-300"
                                aria-label="Self-healing loglarını yenile">
                                Yenile
                            </button>
                        </div>
                    </div>
                    <pre class="bg-gray-50 p-2 rounded-lg text-xs overflow-x-auto h-56 dark:bg-slate-900"><template x-for="(line, idx) in filteredSelfHealing()" :key="idx">@{{ line + '\n' }}</template></pre>
                </div>
                <div
                    class="rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-all duration-200 dark:border-slate-800 dark:bg-slate-900 p-4 dark:shadow-none dark:border-slate-700">
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="font-semibold">Son 10 Hata</h2>
                        <button @click="refreshErrors()"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 text-xs dark:text-slate-300"
                            aria-label="Hata loglarını yenile">
                            Yenile
                        </button>
                    </div>
                    <pre class="bg-gray-50 p-2 rounded-lg text-xs overflow-x-auto h-56 dark:bg-slate-900"><template x-for="(line, idx) in recentErrors" :key="idx">@{{ line + '\n' }}</template></pre>
                </div>
            </div>
        </div>
    @endsection

    @push('scripts')
        <script>
            // Alpine.js component function - defined globally
            window.monitorUI = function() {
                return {
                    // State
                    mcp: {!! json_encode(($mcpStatus ?? collect())->values(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!},
                    apis: {!! json_encode($apiDurumu ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!},
                    selfHealing: {!! json_encode(($selfHealing ?? collect())->values(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!},
                    recentErrors: {!! json_encode(($recentErrors ?? collect())->values(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!},
                    overall: {!! json_encode($overall ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!},
                    roiSummary: {!! json_encode($roiSummary ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!},
                    totalCost: {{ $totalCost ?? 0 }},
                    filterText: '',
                    autoRefresh: false,
                    refreshInterval: 30000,
                    timerId: null,
                    lastUpdated: '',
                    loadingMcp: false,
                    duplicateFiles: [],
                    conflictingRoutes: [],
                    pagesHealth: [],

                    init() {
                        // İlk açılışta verileri getir
                        this.refreshAll();
                        this.refreshDuplicates();
                        this.refreshConflicts();
                        this.refreshPagesHealth();
                        // Otomatik yenileme izleyicisi
                        this.$watch('autoRefresh', (val) => this.toggleTimer(val));
                        this.$watch('refreshInterval', () => this.toggleTimer(this.autoRefresh));
                    },

                    // UI Helpers
                    overallBadgeClass() {
                        const level = this.overall?.level || 'unknown';
                        if (level === 'green') return 'bg-green-200';
                        if (level === 'yellow') return 'bg-yellow-200';
                        if (level === 'red') return 'bg-red-200';
                        return 'bg-gray-200';
                    },
                    statusBadgeClass(st) {
                        const s = (st || '').toUpperCase();
                        if (s === 'OK')
                            return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium rounded-lg px-2 py-0.5 bg-green-200';
                        if (s === 'ERROR')
                            return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium rounded-lg px-2 py-0.5 bg-yellow-200';
                        return 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium rounded-lg px-2 py-0.5 bg-red-200';
                    },
                    usageWidth(count) {
                        const max = Math.max(1, ...Object.values(this.overall?.mcp_usage || {
                            other: 1
                        }).map(v => Number(v) || 0));
                        const val = Math.min(max, Number(count) || 0);
                        return (val / max * 100).toFixed(0) + '%';
                    },
                    filteredSelfHealing() {
                        const q = (this.filterText || '').toLowerCase();
                        if (!q) return this.selfHealing;
                        return this.selfHealing.filter(l => (l || '').toLowerCase().includes(q));
                    },

                    // Networking
                    async refreshAll() {
                        await Promise.all([
                            this.refreshMcp(),
                            this.refreshApis(),
                            this.refreshSelf(),
                            this.refreshErrors(),
                        ]);
                        this.lastUpdated = new Date().toLocaleTimeString();
                    },
                    async refreshMcp() {
                        try {
                            this.loadingMcp = true;
                            const res = await fetch('{{ route('api.admin.ai-monitor.mcp') }}', {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            if (res.ok) {
                                const json = await res.json();
                                this.mcp = Array.isArray(json?.data) ? json.data : [];
                                if (json?.overview) this.overall = json.overview;
                                if (json?.roi_summary) this.roiSummary = json.roi_summary;
                                if (json?.total_cost !== undefined) this.totalCost = json.total_cost;
                            }
                        } catch (e) {
                            /* yut */
                        } finally {
                            this.loadingMcp = false;
                        }
                    },
                    async refreshApis() {
                        try {
                            const res = await fetch('{{ route('api.admin.ai-monitor.apis') }}', {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            if (res.ok) {
                                const json = await res.json();
                                this.apis = json?.data || {};
                            }
                        } catch (e) {
                            /* yut */
                        }
                    },
                    async refreshSelf() {
                        try {
                            const res = await fetch('{{ route('api.admin.ai-monitor.self-healing') }}', {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            if (res.ok) {
                                const json = await res.json();
                                this.selfHealing = Array.isArray(json?.data) ? json.data : [];
                            }
                        } catch (e) {
                            /* yut */
                        }
                    },
                    async refreshErrors() {
                        try {
                            const res = await fetch('{{ route('api.admin.ai-monitor.errors') }}', {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            if (res.ok) {
                                const json = await res.json();
                                this.recentErrors = Array.isArray(json?.data) ? json.data : [];
                            }
                        } catch (e) {
                            /* yut */
                        }
                    },
                    toggleTimer(enable) {
                        if (this.timerId) {
                            clearInterval(this.timerId);
                            this.timerId = null;
                        }
                        if (enable) {
                            this.timerId = setInterval(() => this.refreshAll(), this.refreshInterval);
                        }
                    },
                    sortedUsage() {
                        const obj = this.overall?.mcp_usage || {};
                        return Object.entries(obj)
                            .filter(([, v]) => Number(v) > 0)
                            .sort((a, b) => Number(b[1]) - Number(a[1]));
                    },
                    mcpTypeColor(key) {
                        const colors = {
                            'context7': 'bg-teal-500',
                            'puppeteer': 'bg-purple-500',
                            'memory': 'bg-indigo-500',
                            'filesystem': 'bg-green-500',
                            'yalihan-bekci': 'bg-blue-500',
                            'laravel': 'bg-orange-500',
                            'git': 'bg-gray-500',
                            'ollama': 'bg-red-500'
                        };
                        return colors[key] || 'bg-blue-500';
                    },
                    uptimePercent() {
                        const total = this.overall?.api_total || 0;
                        const ok = this.overall?.api_ok || 0;
                        return total > 0 ? Math.round((ok / total) * 100) : 0;
                    },
                    avgLatency() {
                        const apis = Object.values(this.apis);
                        const latencies = apis.filter(a => a?.latency_ms).map(a => Number(a.latency_ms) || 0);
                        return latencies.length > 0 ? Math.round(latencies.reduce((a, b) => a + b, 0) /
                                latencies.length) :
                            0;
                    },
                    // Ecosystem Analysis Methods
                    async refreshDuplicates() {
                        try {
                            const res = await fetch('{{ route('api.admin.ai-monitor.duplicates') }}', {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            if (res.ok) {
                                const json = await res.json();
                                this.duplicateFiles = json.data || [];
                            }
                        } catch (e) {
                            console.warn('Duplicates fetch error:', e);
                        }
                    },
                    async refreshConflicts() {
                        try {
                            const res = await fetch('{{ route('api.admin.ai-monitor.conflicts') }}', {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            if (res.ok) {
                                const json = await res.json();
                                this.conflictingRoutes = json.data || [];
                            }
                        } catch (e) {
                            console.warn('Conflicts fetch error:', e);
                        }
                    },
                    async refreshPagesHealth() {
                        try {
                            const res = await fetch('{{ route('api.admin.ai-monitor.pages-health') }}', {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            if (res.ok) {
                                const json = await res.json();
                                this.pagesHealth = json.data || [];
                            }
                        } catch (e) {
                            console.warn('Pages health fetch error:', e);
                        }
                    },
                    severityClass(severity) {
                        switch (severity) {
                            case 'critical':
                                return 'text-red-700';
                            case 'high':
                                return 'text-orange-700';
                            case 'medium':
                                return 'text-yellow-700';
                            case 'low':
                                return 'text-gray-700 dark:text-slate-300';
                            default:
                                return 'text-gray-600';
                        }
                    }
                }
            }
        </script>
    @endpush
