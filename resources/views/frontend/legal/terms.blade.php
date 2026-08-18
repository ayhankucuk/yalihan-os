@extends('layouts.frontend')

@section('content')
{{-- Terms of Service Page — Meta App Publish Compliance --}}

<div class="min-h-screen bg-gradient-to-b from-slate-50 to-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20">

        {{-- Header --}}
        <header class="text-center mb-12">
            <span class="inline-block px-4 py-1.5 bg-navy/5 text-navy text-sm font-medium rounded-full mb-4">
                Yasal Uyum
            </span>
            <h1 class="text-4xl lg:text-5xl font-display font-bold text-navy mb-4">
                Kullanım Şartları
            </h1>
            <p class="text-lg text-slate-600 max-w-2xl mx-auto">
                Yalıhan Emlak web sitesi ve hizmetlerini kullanmadan önce lütfen bu şartları dikkatlice okuyunuz.
            </p>
            <p class="text-sm text-slate-500 mt-2">
                Son güncelleme: {{ now()->format('d F Y') }}
            </p>
        </header>

        {{-- Content --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

            <div class="p-8 lg:p-12 space-y-8">

                {{-- Section 1 --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        1. Kabul Edilen Şartlar
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>
                            Yalıhan Emlak web sitesine (<strong>yalihanemlak.com.tr</strong>) erişerek veya hizmetlerimizi
                            kullanarak, bu Kullanım Şartlarını kabul etmiş sayılırsınız. Bu şartları kabul etmiyorsanız,
                            lütfen web sitemizi kullanmayınız.
                        </p>
                        <p class="mt-4">
                            Hizmetlerimiz, 18 yaşın üzerindeki kullanıcılar içindir. Hizmetlerimizi kullanarak,
                            18 yaşın üzerinde olduğunuzu ve yasal olarak sözleşme yapma kapasitenizin olduğunu beyan etmiş olursunuz.
                        </p>
                    </div>
                </section>

                {{-- Divider --}}
                <hr class="border-slate-200">

                {{-- Section 2 --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        2. Hizmet Açıklaması
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>Yalıhan Emlak, Bodrum bölgesinde lüks gayrimenkul danışmanlık hizmetleri sunmaktadır:</p>
                        <ul class="list-disc pl-6 space-y-2 mt-4">
                            <li>Villa, daire, arsa ve ticari gayrimenkul ilanları</li>
                            <li>Gayrimenkul değerleme danışmanlığı</li>
                            <li>Müşteri-gayrimenkul eşleştirme hizmetleri</li>
                            <li>Yatırım danışmanlığı</li>
                            <li>Turistik konaklama danışmanlığı</li>
                        </ul>
                        <p class="mt-4">
                            Gayrimenkul alım-satım işlemleri, ilgili mevzuat çerçevesinde lisanslı gayrimenkul danışmanları
                            tarafından yürütülmektedir.
                        </p>
                    </div>
                </section>

                {{-- Divider --}}
                <hr class="border-slate-200">

                {{-- Section 3 --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </span>
                        3. Kullanıcı Yükümlülükleri
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>Web sitemizi kullanırken aşağıdaki kurallara uymayı kabul etmektesiniz:</p>
                        <ul class="list-disc pl-6 space-y-2 mt-4">
                            <li>Doğru ve güncel bilgi vermeyi</li>
                            <li>Başkalarının gizliliğine saygı göstermeyi</li>
                            <li>Yasadışı faaliyetlerde bulunmamayı</li>
                            <li>Sistemi kötüye kullanmamayı</li>
                            <li>Telif haklarını ihlal etmemeyi</li>
                            <li>Güvenlik önlemlerini atlatmamayı</li>
                        </ul>
                    </div>
                </section>

                {{-- Divider --}}
                <hr class="border-slate-200">

                {{-- Section 4 --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </span>
                        4. Fiyatlandırma ve Komisyonlar
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>Gayrimenkul hizmetlerimiz için uygulanan komisyon oranları:</p>
                        <ul class="list-disc pl-6 space-y-2 mt-4">
                            <li>Alım-satım işlemlerinde standart komisyon oranı uygulanır</li>
                            <li>Komisyon oranları işlem değerine göre değişiklik gösterebilir</li>
                            <li>Kiralama işlemlerinde aylık kira bedeli üzerinden komisyon alınır</li>
                            <li>Tüm fiyatlandırma bilgileri işlem öncesinde sizinle paylaşılır</li>
                        </ul>
                    </div>
                </section>

                {{-- Divider --}}
                <hr class="border-slate-200">

                {{-- Section 5 --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                        </span>
                        5. Fikri Mülkiyet
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>
                            Web sitemizdeki tüm içerikler (metin, grafikler, logolar, görseller, yazılım) Yalıhan Emlak
                            veya içerik sağlayıcılarımızın mülkiyetindedir ve telif haklarıyla korunmaktadır.
                        </p>
                        <p class="mt-4">
                            İznimiz olmadan içeriklerimizi çoğaltamaz, dağıtamaz veya ticarileştiremezsiniz.
                        </p>
                    </div>
                </section>

                {{-- Divider --}}
                <hr class="border-slate-200">

                {{-- Section 6 --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                        </span>
                        6. Sorumluluk Sınırlaması
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>
                            Yalıhan Emlak, web sitesi ve hizmetlerinin kesintisiz veya hatasız olacağını garanti etmez.
                            Hizmetler "olduğu gibi" sunulmaktadır.
                        </p>
                        <p class="mt-4">
                            Gayrimenkul yatırımları risk içerir. Web sitemizdeki bilgiler yatırım tavsiyesi niteliğinde
                            değildir. Yatırım kararlarından önce profesyonel danışmanlık almanızı öneririz.
                        </p>
                    </div>
                </section>

                {{-- Divider --}}
                <hr class="border-slate-200">

                {{-- Section 7 --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </span>
                        7. Sözleşme Değişiklikleri
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>
                            Bu Kullanım Şartlarını zaman zaman güncelleyebiliriz. Önemli değişiklikler yapıldığında,
                            web sitemizde duyuru yapılacaktır. Güncellenmiş şartlar, web sitemizi kullanmaya devam etmeniz
                            halinde geçerli olacaktır.
                        </p>
                    </div>
                </section>

                {{-- Divider --}}
                <hr class="border-slate-200">

                {{-- Section 8 --}}
                <section>
                    <h2 class="text-2xl font-semibold text-navy mb-4 flex items-center gap-3">
                        <span class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        8. İletişim
                    </h2>
                    <div class="prose prose-slate max-w-none text-slate-600">
                        <p>Kullanım şartlarımız hakkında sorularınız için:</p>
                        <div class="bg-slate-50 rounded-lg p-4 mt-4 space-y-2">
                            <p><strong>E-posta:</strong> <a href="mailto:info@yalihanemlak.com.tr" class="text-gold hover:underline">info@yalihanemlak.com.tr</a></p>
                            <p><strong>Telefon:</strong> +90 (252) 000 00 00</p>
                            <p><strong>Adres:</strong> Bodrum, Muğla, Türkiye</p>
                        </div>
                    </div>
                </section>

            </div>
        </div>

        {{-- Footer navigation --}}
        <div class="mt-8 flex justify-center gap-4">
            <a href="{{ route('legal.privacy') }}" class="text-slate-600 hover:text-navy text-sm font-medium">
                Gizlilik Politikası
            </a>
            <span class="text-slate-300">|</span>
            <a href="{{ route('legal.data-deletion') }}" class="text-slate-600 hover:text-navy text-sm font-medium">
                Veri Silme Talimatı
            </a>
        </div>

    </div>
</div>
@endsection
