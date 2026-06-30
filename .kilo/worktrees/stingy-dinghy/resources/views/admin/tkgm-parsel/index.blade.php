@extends('admin.layouts.admin')

@section('title', 'TKGM Parsel Sorgulama')

@section('content')
    <div class="min-h-screen bg-gray-50 dark:bg-slate-900" x-data="tkgmParselApp()" x-init="init()">
        <!-- Page Header -->
        <div class="bg-white dark:bg-slate-900 shadow-sm border-b dark:border-slate-800 dark:shadow-none">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">TKGM Parsel Sorgulama</h1>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Tapu Kadastro Genel Müdürlüğü parsel bilgileri sorgulama
                                sistemi</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button @click="showBulkQuery = true" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 dark:text-slate-300">
                                <i class="fas fa-list"></i>
                                Toplu Sorgulama
                            </button>
                            <button @click="showHistory = true" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 dark:text-slate-300">
                                <i class="fas fa-history"></i>
                                Sorgulama Geçmişi
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Sorgulama Formu -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all p-6 dark:shadow-none dark:border-slate-700">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 dark:text-slate-100">Parsel Bilgileri Sorgulama</h2>

                        <form @submit.prevent="queryParcel()" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Ada -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Ada</label>
                                    <input type="text" x-model="form.ada" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100"
                                        placeholder="Ada numarası (örn: 123)" required maxlength="20">
                                </div>

                                <!-- Parsel -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Parsel</label>
                                    <input type="text" x-model="form.parsel" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100"
                                        placeholder="Parsel numarası (örn: 45)" required maxlength="20">
                                </div>

                                <!-- İl -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">İl</label>
                                    <input type="text" x-model="form.il" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100"
                                        placeholder="İl adı (örn: İstanbul)" required maxlength="50">
                                </div>

                                <!-- İlçe -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">İlçe</label>
                                    <input type="text" x-model="form.ilce" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100"
                                        placeholder="İlçe adı (örn: Kadıköy)" required maxlength="50">
                                </div>

                                <!-- Mahalle (İsteğe Bağlı) -->
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Mahalle (İsteğe Bağlı)</label>
                                    <input type="text" x-model="form.mahalle" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100" placeholder="Mahalle adı"
                                        maxlength="100">
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex items-center justify-between">
                                <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 text-white hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50" :disabled="loading">
                                    <i class="fas fa-search"></i>
                                    <span x-show="!loading">Parsel Sorgula</span>
                                    <span x-show="loading">Sorgulanıyor...</span>
                                </button>

                                <button type="button" @click="resetForm()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 dark:text-slate-300">
                                    <i class="fas fa-redo"></i>
                                    Temizle
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Sonuç Alanı -->
                    <div x-show="result" class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all p-6 mt-6 dark:shadow-none dark:border-slate-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Sorgulama Sonucu</h3>

                        <!-- Başarılı Sonuç -->
                        <div x-show="result && result.success" class="space-y-4">
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                    <span class="text-green-800 font-medium">Parsel bilgileri başarıyla bulundu</span>
                                </div>
                            </div>

                            <!-- Parsel Detayları -->
                            <div x-show="result.data" class="bg-white dark:bg-slate-900 border dark:border-slate-800 rounded-lg overflow-hidden">
                                <div class="px-4 py-2.5 bg-gray-50 border-b dark:bg-slate-900">
                                    <h4 class="font-medium text-gray-900 dark:text-slate-100 dark:text-white">Parsel Detayları</h4>
                                </div>
                                <div class="p-4">
                                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Ada/Parsel</dt>
                                            <dd class="text-sm text-gray-900 dark:text-slate-100 dark:text-white"
                                                x-text="`${result.data.ada}/${result.data.parsel}`"></dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Lokasyon</dt>
                                            <dd class="text-sm text-gray-900 dark:text-slate-100 dark:text-white"
                                                x-text="`${result.data.il} / ${result.data.ilce}`"></dd>
                                        </div>
                                        <div x-show="result.data.mahalle">
                                            <dt class="text-sm font-medium text-gray-500">Mahalle</dt>
                                            <dd class="text-sm text-gray-900 dark:text-slate-100 dark:text-white" x-text="result.data.mahalle"></dd>
                                        </div>
                                        <div x-show="result.data.alan">
                                            <dt class="text-sm font-medium text-gray-500">Parsel Alanı</dt>
                                            <dd class="text-sm text-gray-900 dark:text-slate-100 dark:text-white" x-text="result.data.alan + ' m²'"></dd>
                                        </div>
                                        <div x-show="result.data.nitelik">
                                            <dt class="text-sm font-medium text-gray-500">Nitelik</dt>
                                            <dd class="text-sm text-gray-900 dark:text-slate-100 dark:text-white" x-text="result.data.nitelik"></dd>
                                        </div>
                                        <div x-show="result.data.malik_bilgi">
                                            <dt class="text-sm font-medium text-gray-500">Malik Bilgisi</dt>
                                            <dd class="text-sm text-gray-900 dark:text-slate-100 dark:text-white" x-text="result.data.malik_bilgi"></dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            <!-- Arsa Hesaplama Linki -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h5 class="font-medium text-blue-900">Arsa Hesaplama Yapmak İster Misiniz?</h5>
                                        <p class="text-sm text-blue-700 mt-1">Bu parsel için KAKS/TAKS hesaplama ve yatırım
                                            analizi yapabilirsiniz.</p>
                                    </div>
                                    <button @click="goToArsaCalculation()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 text-white hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200">
                                        <i class="fas fa-calculator"></i>
                                        Hesapla
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Hata Sonucu -->
                        <div x-show="result && !result.success" class="space-y-4">
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <div class="flex items-start">
                                    <i class="fas fa-exclamation-triangle text-red-500 mr-2 mt-0.5"></i>
                                    <div>
                                        <span class="text-red-800 font-medium">Sorgulama başarısız</span>
                                        <p class="text-red-700 text-sm mt-1" x-text="result.message"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Son Sorgular -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all p-6 dark:shadow-none dark:border-slate-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Son Sorgular</h3>

                        <div x-show="recentQueries.length === 0" class="text-center py-4">
                            <i class="fas fa-search text-gray-400 text-2xl mb-2"></i>
                            <p class="text-sm text-gray-500">Henüz sorgulama yapılmadı</p>
                        </div>

                        <div x-show="recentQueries.length > 0" class="space-y-3">
                            <template x-for="query in recentQueries" :key="query.id">
                                <div class="border rounded-lg p-3 cursor-pointer hover:bg-gray-50"
                                    @click="loadQuery(query)">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white"
                                                x-text="`${query.ada}/${query.parsel}`"></p>
                                            <p class="text-xs text-gray-500" x-text="`${query.il} / ${query.ilce}`"></p>
                                            <p class="text-xs text-gray-400" x-text="formatDate(query.timestamp)"></p>
                                        </div>
                                        <div>
                                            <span x-show="query.success"
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">
                                                <i class="fas fa-check mr-1"></i>
                                                Başarılı
                                            </span>
                                            <span x-show="!query.success"
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-red-100 text-red-800">
                                                <i class="fas fa-times mr-1"></i>
                                                Başarısız
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- İstatistikler -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all p-6 dark:shadow-none dark:border-slate-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">İstatistikler</h3>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Toplam Sorgulama</span>
                                <span class="text-sm font-medium" x-text="stats.total_queries || 0"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Başarı Oranı</span>
                                <span class="text-sm font-medium" x-text="(stats.success_rate || 0) + '%'"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Son 24 Saat</span>
                                <span class="text-sm font-medium" x-text="stats.recent_queries || 0"></span>
                            </div>
                        </div>

                        <button @click="loadStats()" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 w-full mt-4 dark:text-slate-300">
                            <i class="fas fa-sync"></i>
                            Güncelle
                        </button>
                    </div>

                    <!-- Yardım -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all p-6 dark:shadow-none dark:border-slate-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Kullanım Kılavuzu</h3>

                        <div class="space-y-3 text-sm text-gray-600">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-blue-500 mr-2 mt-0.5"></i>
                                <span>Ada ve parsel numaralarını doğru girin</span>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-map-marker-alt text-blue-500 mr-2 mt-0.5"></i>
                                <span>İl ve ilçe adlarını tam olarak yazın</span>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-clock text-blue-500 mr-2 mt-0.5"></i>
                                <span>Sorgulama 5-10 saniye sürebilir</span>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-calculator text-blue-500 mr-2 mt-0.5"></i>
                                <span>Başarılı sorgular için arsa hesaplama yapılabilir</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toplu Sorgulama Modal -->
        <div x-show="showBulkQuery" x-transition class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black opacity-50" @click="showBulkQuery = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-lg max-w-4xl w-full p-6 border dark:border-slate-800">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium">Toplu Parsel Sorgulama</h3>
                        <button @click="showBulkQuery = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <p class="text-sm text-gray-600">Aynı anda birden fazla parsel sorgulayabilirsiniz. Her satıra bir
                            parsel bilgisi girin.</p>

                        <textarea x-model="bulkQueryText" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 h-48 dark:text-slate-100"
                            placeholder="Format: ada,parsel,il,ilce,mahalle&#10;Örnek:&#10;123,45,İstanbul,Kadıköy,Fenerbahçe&#10;456,78,Ankara,Çankaya,Kızılay"></textarea>

                        <div class="flex items-center justify-between">
                            <button @click="processBulkQuery()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 text-white hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50" :disabled="bulkLoading">
                                <i class="fas fa-search"></i>
                                <span x-show="!bulkLoading">Toplu Sorgula</span>
                                <span x-show="bulkLoading">Sorgulanıyor...</span>
                            </button>
                            <span class="text-sm text-gray-500" x-text="`${bulkQueries.length} parsel`"></span>
                        </div>

                        <!-- Toplu Sonuçlar -->
                        <div x-show="bulkResults.length > 0" class="max-h-96 overflow-y-auto border rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 dark:bg-slate-900">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">
                                            Ada/Parsel</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">
                                            Lokasyon</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 uppercase">Durum
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <template x-for="result in bulkResults" :key="result.index">
                                        <tr>
                                            <td class="px-4 py-2.5 text-sm"
                                                x-text="`${result.query.ada}/${result.query.parsel}`"></td>
                                            <td class="px-4 py-2.5 text-sm"
                                                x-text="`${result.query.il} / ${result.query.ilce}`"></td>
                                            <td class="px-4 py-2.5 text-sm">
                                                <span x-show="result.success" class="text-green-600">✓ Başarılı</span>
                                                <span x-show="!result.success" class="text-red-600">✗ Başarısız</span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Geçmiş Modal -->
        <div x-show="showHistory" x-transition class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black opacity-50" @click="showHistory = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-lg max-w-4xl w-full p-6 border dark:border-slate-800">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium">Sorgulama Geçmişi</h3>
                        <button @click="showHistory = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div class="max-h-96 overflow-y-auto">
                            <div x-show="historyQueries.length === 0" class="text-center py-8">
                                <i class="fas fa-history text-gray-400 text-3xl mb-2"></i>
                                <p class="text-gray-500">Henüz sorgulama geçmişi bulunmuyor</p>
                            </div>

                            <div x-show="historyQueries.length > 0" class="space-y-3">
                                <template x-for="query in historyQueries" :key="query.id">
                                    <div class="border rounded-lg p-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-medium" x-text="`${query.ada}/${query.parsel}`"></p>
                                                <p class="text-sm text-gray-600" x-text="`${query.il} / ${query.ilce}`">
                                                </p>
                                                <p class="text-xs text-gray-400" x-text="formatDate(query.timestamp)"></p>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <span x-show="query.success"
                                                    class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">Başarılı</span>
                                                <span x-show="!query.success"
                                                    class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-800">Başarısız</span>
                                                <button @click="loadQuery(query)"
                                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 dark:text-slate-300">
                                                    <i class="fas fa-redo"></i>
                                                    Tekrar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex justify-center">
                            <button @click="loadHistory()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 dark:text-slate-300">
                                <i class="fas fa-sync"></i>
                                Geçmişi Yenile
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function tkgmParselApp() {
            return {
                // Form data
                form: {
                    ada: '',
                    parsel: '',
                    il: '',
                    ilce: '',
                    mahalle: ''
                },

                // State
                loading: false,
                result: null,
                recentQueries: @json($recentQueries ?? []),
                stats: {},

                // Modals
                showBulkQuery: false,
                showHistory: false,

                // Bulk query
                bulkQueryText: '',
                bulkQueries: [],
                bulkResults: [],
                bulkLoading: false,

                // History
                historyQueries: [],

                init() {
                    this.loadStats();
                },

                async queryParcel() {
                    this.loading = true;
                    this.result = null;

                    try {
                        const response = await fetch('/admin/api/tkgm-parsel/query', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            },
                            body: JSON.stringify(this.form)
                        });

                        const data = await response.json();
                        this.result = data;

                        if (data.success) {
                            // Başarılı sorgulama sonrası son sorguları güncelle
                            this.addToRecentQueries();
                            this.loadStats();
                        }

                    } catch (error) {
                        console.error('TKGM sorgulama hatası:', error);
                        this.result = {
                            success: false,
                            message: 'Sorgulama sırasında bir hata oluştu'
                        };
                    } finally {
                        this.loading = false;
                    }
                },

                async processBulkQuery() {
                    this.bulkLoading = true;
                    this.bulkResults = [];

                    try {
                        // Text'i parse et
                        const lines = this.bulkQueryText.trim().split('\n').filter(line => line.trim());
                        this.bulkQueries = lines.map(line => {
                            const parts = line.split(',').map(part => part.trim());
                            return {
                                ada: parts[0] || '',
                                parsel: parts[1] || '',
                                il: parts[2] || '',
                                ilce: parts[3] || '',
                                mahalle: parts[4] || ''
                            };
                        }).filter(query => query.ada && query.parsel && query.il && query.ilce);

                        if (this.bulkQueries.length === 0) {
                            alert('Geçerli parsel bilgisi bulunamadı');
                            return;
                        }

                        const response = await fetch('/admin/api/tkgm-parsel/bulk-query', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            },
                            body: JSON.stringify({
                                queries: this.bulkQueries
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.bulkResults = data.results;
                            this.loadStats();
                        }

                    } catch (error) {
                        console.error('Toplu sorgulama hatası:', error);
                        alert('Toplu sorgulama sırasında bir hata oluştu');
                    } finally {
                        this.bulkLoading = false;
                    }
                },

                async loadHistory() {
                    try {
                        const response = await fetch('/admin/api/tkgm-parsel/history', {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.historyQueries = data.data;
                        }

                    } catch (error) {
                        console.error('Geçmiş yükleme hatası:', error);
                    }
                },

                async loadStats() {
                    try {
                        const response = await fetch('/admin/api/tkgm-parsel/stats', {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.stats = data.stats;
                        }

                    } catch (error) {
                        console.error('İstatistik yükleme hatası:', error);
                    }
                },

                loadQuery(query) {
                    this.form = {
                        ada: query.ada,
                        parsel: query.parsel,
                        il: query.il,
                        ilce: query.ilce,
                        mahalle: query.mahalle || ''
                    };
                    this.result = null;
                },

                resetForm() {
                    this.form = {
                        ada: '',
                        parsel: '',
                        il: '',
                        ilce: '',
                        mahalle: ''
                    };
                    this.result = null;
                },

                addToRecentQueries() {
                    const queryRecord = {
                        id: Date.now().toString(),
                        ...this.form,
                        success: this.result.success,
                        timestamp: new Date().toISOString()
                    };

                    this.recentQueries.unshift(queryRecord);
                    this.recentQueries = this.recentQueries.slice(0, 10);
                },

                goToArsaCalculation() {
                    if (this.result && this.result.success && this.result.data) {
                        const params = new URLSearchParams({
                            ada: this.form.ada,
                            parsel: this.form.parsel,
                            il: this.form.il,
                            ilce: this.form.ilce,
                            alan: this.result.data.alan || ''
                        });
                        window.open(`/admin/ilanlar/arsa-calculation?${params.toString()}`, '_blank');
                    }
                },

                formatDate(timestamp) {
                    return new Date(timestamp).toLocaleString('tr-TR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            }
        }
    </script>
@endsection
