@extends('layouts.frontend')

@section('title', 'Danışmanlarımız')

@section('content')
    <div class="container mx-auto px-4 py-12">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Uzman Danışmanlarımız</h1>
                <p class="text-xl text-gray-600">Size en iyi hizmeti sunmak için deneyimli ekibimizle çalışıyoruz</p>
                <div
                    class="inline-flex items-center mt-4 px-4 py-2 bg-gradient-to-r from-blue-100 to-purple-100 rounded-full">
                    <span
                        class="px-2 py-1 bg-gradient-to-r from-blue-500 to-purple-600 text-white text-xs rounded-full font-medium mr-2">
                        AI
                    </span>
                    <span class="text-sm font-medium text-gray-700 dark:text-slate-300">AI Destekli Danışmanlık Hizmeti</span>
                </div>
            </div>

            <!-- Advisors Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
                @for ($i = 1; $i <= 6; $i++)
                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2">
                        <!-- Image -->
                        <div class="relative h-64 bg-gradient-to-br from-blue-400 to-purple-600">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="w-32 h-32 bg-white dark:bg-slate-800 rounded-full flex items-center justify-center dark:bg-slate-900">
                                    <i class="fas fa-user text-6xl text-gray-400 dark:text-gray-500"></i>
                                </div>
                            </div>
                            @if ($i <= 2)
                                <div class="absolute top-4 right-4">
                                    <span class="px-3 py-1 bg-yellow-400 text-gray-900 dark:text-black text-xs font-bold rounded-full dark:text-white dark:text-slate-100">
                                        <i class="fas fa-star mr-1"></i> TOP DANIŞMAN
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- Content -->
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                {{ ['Ahmet Yılmaz', 'Mehmet Kaya', 'Ayşe Demir', 'Fatma Öztürk', 'Ali Başaran', 'Veli Çelik'][$i - 1] }}
                            </h3>
                            <p class="text-gray-600 mb-4">
                                {{ ['Kıdemli Emlak Danışmanı', 'Emlak Uzmanı', 'Satış Danışmanı', 'Kiralama Uzmanı', 'Yatırım Danışmanı', 'Müşteri İlişkileri'][$i - 1] }}
                            </p>

                            <!-- Stats -->
                            <div class="grid grid-cols-3 gap-2 mb-4 text-center">
                                <div class="bg-gray-50 dark:bg-slate-800 rounded-lg p-2 dark:bg-slate-900">
                                    <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ 30 + $i * 5 }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">İlan</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-slate-800 rounded-lg p-2 dark:bg-slate-900">
                                    <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ 50 + $i * 10 }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Satış</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-slate-800 rounded-lg p-2 dark:bg-slate-900">
                                    <div class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ 3 + $i }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">Yıl</div>
                                </div>
                            </div>

                            <!-- Rating -->
                            <div class="flex items-center justify-center mb-4">
                                <div class="flex text-yellow-400">
                                    @for ($j = 1; $j <= 5; $j++)
                                        @if ($j <= 4 || $i <= 3)
                                            <i class="fas fa-star"></i>
                                        @else
                                            <i class="far fa-star"></i>
                                        @endif
                                    @endfor
                                </div>
                                <span class="ml-2 text-sm text-gray-600">({{ 4.5 + ($i % 5) * 0.1 }})</span>
                            </div>

                            <!-- Contact Buttons -->
                            <div class="flex gap-2">
                                <a href="tel:05332090302"
                                    class="flex-1 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white text-center font-semibold rounded-lg hover:shadow-lg transition-all">
                                    <i class="fas fa-phone mr-1"></i> Ara
                                </a>
                                <a href="https://wa.me/905332090302"
                                    class="flex-1 py-2 bg-green-500 text-white text-center font-semibold rounded-lg hover:bg-green-600 transition-all">
                                    <i class="fab fa-whatsapp mr-1"></i> WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>

            <!-- AI Assistant Section -->
            <div class="bg-gradient-to-br from-blue-600 via-purple-600 to-pink-600 rounded-2xl shadow-2xl p-8 text-white">
                <div class="grid lg:grid-cols-2 gap-8 items-center">
                    <div>
                        <h2 class="text-3xl font-bold mb-4">
                            <i class="fas fa-robot mr-3"></i>
                            AI Destekli Danışmanlık
                        </h2>
                        <p class="text-lg mb-6 text-white/90">
                            Yapay zeka destekli sistemimiz sayesinde, size en uygun emlak seçeneklerini
                            anında buluyoruz. 7/24 hizmet veren AI asistanımız, tüm sorularınızı yanıtlamaya hazır.
                        </p>
                        <ul class="space-y-3 mb-6">
                            <li class="flex items-center">
                                <i class="fas fa-check-circle mr-3 text-green-400"></i>
                                <span>Anlık emlak önerileri</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check-circle mr-3 text-green-400"></i>
                                <span>Fiyat analizi ve tahminleme</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check-circle mr-3 text-green-400"></i>
                                <span>7/24 destek</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check-circle mr-3 text-green-400"></i>
                                <span>Kişiselleştirilmiş öneriler</span>
                            </li>
                        </ul>
                        <button
                            class="px-6 py-3 bg-white dark:bg-slate-100 text-gray-900 dark:text-black font-semibold rounded-xl hover:shadow-lg transform hover:scale-105 transition-all dark:bg-slate-900 dark:text-white dark:text-slate-100">
                            <i class="fas fa-comments mr-2"></i>
                            AI Asistan ile Konuş
                        </button>
                    </div>
                    <div class="flex justify-center">
                        <div class="relative">
                            <div
                                class="w-64 h-64 bg-gray-200 dark:bg-slate-900 backdrop-blur rounded-full flex items-center justify-center animate-pulse">
                                <i class="fas fa-brain text-8xl text-white/80"></i>
                            </div>
                            <div
                                class="absolute -top-4 -right-4 w-20 h-20 bg-green-400 dark:bg-green-600 rounded-full flex items-center justify-center animate-bounce">
                                <span class="text-2xl font-bold text-white">AI</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
