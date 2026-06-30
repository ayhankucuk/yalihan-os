@extends('layouts.frontend')

@section('title', 'Hakkımızda')

@section('content')
    <div class="container mx-auto px-4 py-12">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-gray-900 mb-4 dark:text-slate-100 dark:text-white">Hakkımızda</h1>
                <p class="text-xl text-gray-600">Yalıhan Emlak - Bodrum'un Güvenilir Emlak Danışmanı</p>
            </div>

            <!-- Content -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-8 dark:bg-slate-900">
                <div class="prose prose-lg max-w-none">
                    <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-slate-100 dark:text-white">Biz Kimiz?</h2>
                    <p class="text-gray-700 mb-6 dark:text-slate-300">
                        Yalıhan Emlak olarak, 15 yılı aşkın deneyimimizle Bodrum'da gayrimenkul sektörünün öncü
                        firmalarından biriyiz.
                        Müşterilerimize en kaliteli hizmeti sunmak için profesyonel ekibimizle çalışıyoruz.
                    </p>

                    <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-slate-100 dark:text-white">Misyonumuz</h2>
                    <p class="text-gray-700 mb-6 dark:text-slate-300">
                        Bodrum'da emlak arayan müşterilerimize güvenilir, şeffaf ve profesyonel hizmet sunarak,
                        hayallerindeki evi bulmalarına yardımcı olmaktır.
                    </p>

                    <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-slate-100 dark:text-white">Vizyonumuz</h2>
                    <p class="text-gray-700 mb-6 dark:text-slate-300">
                        Teknoloji ve inovasyonu kullanarak emlak sektöründe öncü olmak ve müşteri memnuniyetinde
                        en yüksek standartları yakalamaktır.
                    </p>

                    <h2 class="text-2xl font-bold mb-4 text-gray-900 dark:text-slate-100 dark:text-white">Değerlerimiz</h2>
                    <ul class="list-disc list-inside text-gray-700 space-y-2 dark:text-slate-300">
                        <li>Güvenilirlik ve Şeffaflık</li>
                        <li>Müşteri Odaklı Yaklaşım</li>
                        <li>Profesyonellik</li>
                        <li>Sürekli Gelişim</li>
                        <li>Etik Değerler</li>
                    </ul>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6 text-center dark:bg-slate-900">
                    <div class="text-4xl font-bold text-blue-600 mb-2">500+</div>
                    <div class="text-gray-600">Aktif İlan</div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 text-center dark:bg-slate-900">
                    <div class="text-4xl font-bold text-green-600 mb-2">1200+</div>
                    <div class="text-gray-600">Mutlu Müşteri</div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 text-center dark:bg-slate-900">
                    <div class="text-4xl font-bold text-purple-600 mb-2">15+</div>
                    <div class="text-gray-600">Yıllık Deneyim</div>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl shadow-lg p-8 text-white">
                <h3 class="text-2xl font-bold mb-4">İletişime Geçin</h3>
                <div class="space-y-3">
                    <p class="flex items-center">
                        <span class="material-symbols-outlined mr-3" style="font-size:20px;vertical-align:middle">location_on</span>
                        Yalıkavak, Şeyhül İslam Ömer Lütfi Cd. No:10 D:C, 48400 Bodrum/Muğla
                    </p>
                    <p class="flex items-center">
                        <span class="material-symbols-outlined mr-3" style="font-size:20px;vertical-align:middle">call</span>
                        <a href="tel:05332090302" class="hover:underline">0533 209 03 02</a>
                    </p>
                    <p class="flex items-center">
                        <span class="material-symbols-outlined mr-3" style="font-size:20px;vertical-align:middle">mail</span>
                        <a href="mailto:info@yalihanemlak.com" class="hover:underline">info@yalihanemlak.com</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
